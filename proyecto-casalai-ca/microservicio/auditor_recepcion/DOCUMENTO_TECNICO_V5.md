# Documento Técnico Unificado V5
## Microservicio de Recepción y Auditoría Asistida por CAI

**Fecha de revisión:** noviembre de 2026  
**Estado:** descripción del código encontrado en el repositorio durante la revisión  
**Alcance:** flujo de lectura de facturas para registrar recepciones, integración PHP/JavaScript, catálogo, feedback y entrenamiento.

> Este documento separa el flujo que usa actualmente la pantalla de Recepción de endpoints y módulos heredados que aún existen en el repositorio. La presencia de una ruta o archivo no significa que participe en el flujo activo. Cuando una implementación no está conectada o devuelve una respuesta provisional, se indica expresamente.

---

## Índice

1. [Propósito y cambios respecto al V4](#1-propósito-y-cambios-respecto-al-v4)
2. [Resumen de arquitectura](#2-resumen-de-arquitectura)
3. [Fases y orden de ejecución](#3-fases-y-orden-de-ejecución)
4. [Herramientas y dependencias](#4-herramientas-y-dependencias)
5. [Configuración y puesta en marcha](#5-configuración-y-puesta-en-marcha)
6. [API: rutas, contratos y estado real](#6-api-rutas-contratos-y-estado-real)
7. [Integración con el sistema web](#7-integración-con-el-sistema-web)
8. [Procesamiento OCR y extracción](#8-procesamiento-ocr-y-extracción)
9. [Catálogo y base de conocimiento](#9-catálogo-y-base-de-conocimiento)
10. [Validación, discrepancias y confirmación humana](#10-validación-discrepancias-y-confirmación-humana)
11. [Modelo de confiabilidad](#11-modelo-de-confiabilidad)
12. [Feedback, aprendizaje y reentrenamiento](#12-feedback-aprendizaje-y-reentrenamiento)
13. [Ciclo de vida de datos y artefactos](#13-ciclo-de-vida-de-datos-y-artefactos)
14. [Seguridad y límites operativos actuales](#14-seguridad-y-límites-operativos-actuales)
15. [Archivos involucrados](#15-archivos-involucrados)
16. [Pruebas y mantenimiento](#16-pruebas-y-mantenimiento)
17. [Diagnóstico de problemas](#17-diagnóstico-de-problemas)
18. [Cambios históricos y consideraciones futuras](#18-cambios-históricos-y-consideraciones-futuras)

---

## 1. Propósito y cambios respecto al V4

El microservicio asiste el registro de recepciones leyendo facturas mediante OCR local, interpretando campos con reglas y expresiones regulares, y cotejando proveedores y productos contra el catálogo del sistema cuando existe conexión a la base de datos. El usuario revisa y confirma el resultado; la automatización no debe sustituir esa revisión.

La aplicación de recepción puede utilizarse **con o sin CAI**. La selección de un archivo inicia el análisis asistido; el formulario manual sigue siendo el mecanismo alternativo si el usuario no desea usar la lectura o el servicio no está disponible.

### 1.1 Correcciones factuales incorporadas en V5

| Tema | Estado observado en el código |
|---|---|
| Token | PHP lo reenvía al servicio en `X-Auditor-Token`; el navegador usa el proxy PHP y no debe recibir el secreto. PHP carga el `.env` privado del proyecto y FastAPI consulta el entorno y, como alternativa, el `.env` de la raíz. |
| Número de factura | La extracción acepta exactamente seis dígitos ASCII (`[0-9]{6}`), asociados a contexto de factura/número. Letras, símbolos, cinco/siete dígitos y dígitos Unicode de ancho completo se rechazan. |
| Proveedor | Solo se devuelve un proveedor que coincida de forma inequívoca con el catálogo cargado; si no hay evidencia suficiente, el campo queda vacío para selección manual. |
| Producto | La canonicalización solo ocurre ante una coincidencia segura. Cuando catálogo y OCR permiten distinguirlo, el nombre se conserva como nombre del catálogo y el modelo se separa como atributo. |
| Base de conocimiento | El código busca archivos bajo `microservicio/conocimiento_externo/`; en la revisión, esa carpeta contiene su guía Markdown, pero no los YAML/CSV que describe. En consecuencia, el servicio usa patrones por defecto y depende de la carga del catálogo configurado. |
| Confianza OCR | No es una puntuación de confianza palabra por palabra de Tesseract. Es una heurística derivada de campos/productos detectados, que después sirve como única característica del clasificador. |
| Verificación HTTP | La ruta `/fase1/verificar` protegida por token actualmente devuelve una respuesta fija de éxito y lista vacía. No invoca la verificación de coherencia del modelo. |
| Discrepancias del formulario | El flujo actual de la vista compara localmente los valores extraídos con los campos que el usuario dejó en el formulario antes del registro. No depende de la ruta HTTP provisional `/fase1/verificar`. |
| Aprendizaje | No hay actualización online por factura. El feedback confirmado se guarda al registrar una recepción y después puede generar un bundle entrenado: clasificador de confiabilidad y sugerencias de corrección sujetas a confirmación humana. |
| PDF | Se procesan como máximo las primeras cinco páginas, a 150 DPI. |
| Rutas históricas | Permanecen endpoints y componentes heredados (por ejemplo, `/feedback`, `/api/v1/auditar`, `FeedbackStore` y `ModelRegistry`); no son el camino usado por el formulario actual para extraer y almacenar feedback. |

### 1.2 Qué significa “IA” en el flujo actual

El recorrido implementado para una factura es:

1. OCR local mediante Tesseract.
2. Preprocesamiento local con OpenCV (imágenes) o conversión local del PDF con Poppler/pdf2image.
3. Extracción determinista con expresiones regulares, análisis de líneas y normalización numérica.
4. Enriquecimiento/cotejo con catálogos de MySQL o, si existe el archivo correspondiente, CSV.
5. Predicción de confiabilidad con un `RandomForestClassifier` opcional.
6. Generación de sugerencias a partir de feedback histórico, sin aplicar cambios automáticamente.

No se encontró una llamada a un modelo generativo remoto en el recorrido de extracción. El OCR y el análisis se ejecutan en el microservicio local.

---

## 2. Resumen de arquitectura

```text
Usuario
  │ elige factura (opcional)
  ▼
Vista/VistaNew/recepcion.php + assets/javascript/recepcion.js
  │ FormData: accion=ia_extraer, imagen=<archivo>
  ▼
Modelo/Controlador/recepcion.php
  │ RecepcionIAProxy; agrega X-Auditor-Token
  │ conexión local, reintento acotado y circuit breaker
  ▼
FastAPI: POST /fase1/extraer
  │ validar token + límite de solicitudes
  │ guarda temporalmente el archivo
  ▼
AuditorRecepcion
  ├─ prepara imagen/PDF y ejecuta Tesseract
  ├─ extrae y valida campos
  ├─ coteja proveedor/productos con catálogo
  ├─ calcula confianza heurística
  ├─ predice confiabilidad ML si hay modelo válido
  └─ genera sugerencias de feedback con soporte suficiente
  ▼
JSON → PHP proxy → JavaScript
  │ llena campos; el usuario revisa y corrige
  ▼
POST de registro PHP
  ├─ valida y registra recepción en la base principal
  ├─ escribe feedback CSV si se confirmó el flujo asistido
  └─ intenta reentrenar cuando el dataset llega al umbral
```

La API FastAPI se comparte con la analítica predictiva del mismo proyecto. Este documento describe únicamente la parte de Auditoría de Recepción, salvo donde la aplicación unificada afecta autenticación, arranque o rutas.

---

## 3. Fases y orden de ejecución

### Fase 0 — Inicio de la aplicación y carga de componentes

1. Uvicorn importa `main.py`.
2. `main.py` carga `microservicio/.env` para variables locales del servicio.
3. Si no hay `AUDITOR_RECEPCION_TOKEN` heredado del proceso, `main.py` intenta obtenerlo del `.env` privado ubicado en la raíz del proyecto.
4. Se crean FastAPI, el limitador global, CORS y los routers.
5. Al importar `controlador/api_recepcion.py` se crea una instancia global de `AuditorRecepcion`.
6. El auditor carga configuración, patrones, reglas, catálogo y el modelo una vez al inicio. El modelo puede volver a revisarse durante inferencia si cambia el archivo en disco.

**Consecuencia:** cambios en patrones, reglas, archivos de catálogo o variables de conexión requieren reiniciar FastAPI para que el auditor los vuelva a leer. El archivo del modelo tiene una ruta de recarga específica.

### Fase 1 — Selección opcional y validación inicial del archivo

1. La persona abre el modal de recepción y puede usar el formulario sin CAI.
2. Si elige factura, JavaScript acepta imagen o PDF y verifica un máximo de 5 MiB en el navegador.
3. Se crea una URL de vista previa y se presenta el archivo en el modal.
4. La selección inicia `analizarFacturaRecepcion()` y el cliente `AsistenteRecepcionIA`.

La comprobación de tamaño observada está en el navegador. La ruta FastAPI no establece en el código revisado un límite equivalente de bytes; no debe considerarse que la comprobación de la interfaz es un límite de seguridad del servidor.

### Fase 2 — Envío navegador → PHP

1. El cliente adjunta el archivo en el campo multipart `imagen`.
2. Como la instancia activa tiene `proxyUrl`, envía `accion=ia_extraer` al controlador PHP de Recepción.
3. El navegador no envía ni conoce el token de FastAPI.
4. El cliente JavaScript tiene timeout predeterminado de 30 segundos y un circuit breaker en `localStorage` (3 fallos; enfriamiento de 120 segundos).

### Fase 3 — Proxy PHP → FastAPI

1. PHP carga variables de entorno desde la raíz del proyecto usando `Modelo/Config/loadEnv.php`.
2. `RecepcionIAProxy` obtiene `RECEPCION_IA_URL` (por defecto `http://127.0.0.1:8000`) y `AUDITOR_RECEPCION_TOKEN`.
3. Comprueba que el token tenga entre 32 y 256 caracteres, excepto para el health check.
4. Reenvía la factura a `/fase1/extraer` con cURL, como multipart, en el campo `imagen`.
5. Agrega el token privado en `X-Auditor-Token`.
6. Para ciertos fallos de conexión y respuestas 502/503/504 realiza un segundo intento tras 200 ms. El límite total configurado en PHP es 17,5 segundos.
7. Un circuito compartido entre solicitudes PHP se abre después de tres fallos y permanece abierto 120 segundos; una respuesta normal lo restablece.

### Fase 4 — Autenticación, limitación y archivo temporal en FastAPI

1. FastAPI valida el encabezado con `hmac.compare_digest`.
2. Si la configuración del secreto no cumple longitud, devuelve HTTP 503; si no coincide, devuelve HTTP 403.
3. La extracción está limitada a 10 solicitudes por minuto por dirección remota.
4. La ruta escribe la carga en `microservicio/temp/<nombre recibido>`, llama al auditor y elimina el archivo en el bloque `finally`.

### Fase 5 — OCR y extracción

1. Si el archivo es PDF, convierte hasta cinco páginas a imágenes.
2. Aplica preprocesamiento a cada imagen cuando está habilitado.
3. Ejecuta Tesseract con `spa+eng`, `--psm 6 --oem 3`.
4. Une el texto OCR de las páginas y extrae campos.
5. Canonicaliza proveedores/productos cuando la evidencia del catálogo lo permite.
6. Calcula la confianza heurística y ejecuta el modelo de confiabilidad si existe y es válido.
7. Calcula sugerencias aprendidas, si el bundle contiene reglas aplicables.
8. Devuelve campos y metadatos al endpoint.

### Fase 6 — Respuesta y revisión humana

1. FastAPI serializa el resultado en el contrato compatible con JavaScript.
2. PHP conserva el código HTTP y reenvía el JSON.
3. JavaScript verifica `exito`, revisa `es_factura_valida`, y rellena solo datos que cumplen las condiciones de la interfaz.
4. El número se asigna al campo solo si tiene exactamente seis dígitos ASCII.
5. Proveedor y producto se seleccionan únicamente por una coincidencia exacta y única con las opciones del formulario.
6. Campos no resueltos quedan para corrección manual; una sugerencia aprendida no se aplica hasta que el usuario la acepta.

### Fase 7 — Discrepancias y registro

1. Antes de registrar, la vista compara la extracción conservada con el formulario actual.
2. Un proveedor vacío se considera pendiente, aunque la factura no haya producido proveedor.
3. El formulario puede permitir registrar con discrepancias si el usuario confirma explícitamente; la pantalla comunica el estado y la lista de diferencias.
4. Cuando la extracción asistida se completó y el usuario confirma el registro, el navegador envía `usar_ia=true` y la predicción original al backend PHP.
5. El controlador valida y registra la recepción en la base principal.
6. Si el registro tiene éxito, PHP escribe predicción frente a valores confirmados en el CSV de feedback y puede ejecutar el reentrenamiento.

### Fase 8 — Anulación y limpieza del feedback

Cuando una recepción registrada se anula, el controlador PHP intenta retirar del CSV el renglón asociado al `id_recepcion`. Si quedan al menos cinco muestras, intenta reentrenar con el dataset restante. Un fallo de este proceso se registra en el log PHP; la anulación de la recepción no se revierte automáticamente.

---

## 4. Herramientas y dependencias

### 4.1 Software externo

| Herramienta | Uso |
|---|---|
| Python 3.10 o superior | Runtime del microservicio y scripts de entrenamiento. |
| FastAPI | Endpoints HTTP y validación/dependencias de API. |
| Uvicorn | Servidor ASGI. |
| Tesseract OCR | Reconocimiento de texto local. Debe disponer de idiomas español e inglés. |
| Poppler | Conversión de páginas PDF para `pdf2image`. |
| OpenCV | Preprocesamiento de imágenes. |
| Pillow | Representación de páginas convertidas. |
| pdf2image | Puente Python para conversión PDF → imágenes. |
| scikit-learn | Clasificador Random Forest. |
| joblib | Lectura y escritura del bundle del modelo. |
| NumPy y pandas | Validación, transformación y cálculo del dataset de entrenamiento. |
| PyYAML | Lectura de configuración YAML si se instala en la ruta y estructura esperadas. |
| mysql-connector-python | Acceso al catálogo MySQL/MariaDB. |
| slowapi | Límite de solicitudes FastAPI por dirección. |
| PHP cURL | Proxy entre PHP y FastAPI. |
| MySQL/MariaDB | Catálogo de productos/proveedores y persistencia de recepciones. |

Las versiones de `requirements.txt` son mínimos (`>=`), no un lockfile reproducible. Una instalación puede resolver versiones posteriores.

### 4.2 Dependencias Python declaradas

El manifiesto es `microservicio/requirements.txt`. Incluye FastAPI/Uvicorn, Pydantic Settings, Pillow, pytesseract, OpenCV, pdf2image, multipart, Pydantic, NumPy, pandas, scikit-learn, joblib, slowapi, PyYAML y mysql-connector-python. EasyOCR y structlog aparecen como opciones comentadas, no como dependencias activas.

---

## 5. Configuración y puesta en marcha

### 5.1 Variables principales

| Variable | Consumidor | Función |
|---|---|---|
| `AUDITOR_RECEPCION_TOKEN` | PHP y FastAPI | Secreto servicio-a-servicio para las rutas protegidas. Debe ser el mismo en ambos procesos; rango aceptado de 32 a 256 caracteres. |
| `RECEPCION_IA_URL` | PHP | URL base del microservicio; por defecto `http://127.0.0.1:8000`. |
| `BD_TIPO` | FastAPI/Auditor | `mysql` o `csv`; en código, el valor por defecto es `csv`. |
| `BD_HOST`, `BD_PORT`, `BD_USER`, `BD_PASSWORD`, `BD_DATABASE` | Adaptador MySQL | Credenciales y destino del catálogo. |
| `BD_RUTA_PRODUCTOS_CSV` | Ejemplo de entorno | Ruta sugerida por `.env.example`; el código del auditor construye las rutas CSV desde `conocimiento_externo`, por lo que hay que verificar que coincidan antes de activar CSV. |
| `BD_RUTA_PROVEEDORES_CSV` | Ejemplo de entorno | Ruta sugerida para CSV de proveedores; el auditor usa su propia ruta interna bajo `conocimiento_externo`. |
| `PYTHON_EXECUTABLE` | PHP/reentrenamiento | Ruta opcional al Python que PHP ejecutará al reentrenar. |

`microservicio/config.py` también declara configuración general y nombres de tokens, pero la dependencia de auditoría valida `AUDITOR_RECEPCION_TOKEN` directamente desde `os.environ`.

### 5.2 Ubicación del secreto

- En producción, define el token en el entorno de los procesos Apache/PHP y FastAPI.
- Para desarrollo, PHP carga el `.env` de la raíz del proyecto; FastAPI consulta primero el entorno y usa el `.env` raíz como respaldo.
- `microservicio/.env.example` indica que el token de auditoría debe permanecer en el `.env` privado de la raíz.
- No debe enviarse al navegador, escribirse en documentación ni incluirse en repositorios.
- Reinicia los procesos después de cambiarlo.

Los scripts `iniciar_servicio.bat` y `iniciar_servicio.ps1` del repositorio contienen valores estáticos de token en su versión actual. No los uses sin retirarlos y migrar su configuración al entorno privado. Si esos valores estuvieron accesibles a terceros, deben rotarse.

### 5.3 Configuración de base de datos

El ejemplo `microservicio/.env.example` está preparado para `BD_TIPO=mysql` y usa variables `BD_*`. Para el funcionamiento de MySQL, el adaptador consulta:

- `tbl_productos`, filtrando `estado = 'habilitado'`.
- `tbl_modelos`, unido por `id_modelo`.
- `tbl_marcas`, unido desde el modelo.
- `tbl_proveedores`, filtrando también `estado = 'habilitado'`.

El proceso de auditoría necesita permisos de lectura para cargar el catálogo. Las operaciones de registro se realizan desde PHP con la conexión principal de la aplicación, no por el adaptador Python.

### 5.4 Arranque recomendado

Desde PowerShell, con el directorio de trabajo en `microservicio` y las variables configuradas privadamente:

```powershell
python -m pip install -r requirements.txt
python -m uvicorn main:app --host 127.0.0.1 --port 8000
```

`--reload` puede añadirse solo para desarrollo. Mantener el servicio en loopback cuando PHP corre en el mismo host reduce su exposición de red.

No se recomienda ejecutar `python main.py`: el bloque opcional de `main.py` apunta a `main:main` y escucha en `0.0.0.0`, mientras que el objeto ASGI definido es `app`. Usa el comando Uvicorn explícito anterior.

### 5.5 Health checks

- `GET /` responde el estado general de la aplicación FastAPI.
- `GET /health` está implementado en el router de recepción.
- El proxy PHP permite `ia_health` sin exigir token.
- El health check no prueba por sí mismo el funcionamiento de OCR, Poppler, la base de datos ni el modelo.

---

## 6. API: rutas, contratos y estado real

### 6.1 Montaje de rutas

`main.py` incluye el router de recepción dos veces: una vez sin prefijo y otra con `settings.API_V1_STR` (`/api/v1`). Por eso existen rutas con y sin ese prefijo. El proxy PHP activo llama las rutas sin prefijo.

| Método y ruta usada por PHP | Autenticación | Límite | Estado/función |
|---|---:|---:|---|
| `GET /health` | No | No definido | Health check sencillo. |
| `POST /fase1/extraer` | Sí | 10/min | Ruta activa de extracción; recibe multipart `imagen`. |
| `POST /fase1/verificar` | Sí | 30/min | **Provisional:** recibe JSON, pero responde con lista de discrepancias vacía sin comparar los datos. |
| `POST /fase1/comparar-directo` | Sí | 10/min | Extrae la factura y llama al comparador del auditor. |
| `GET /fase1/estadisticas` | No | No definido | **Estático:** devuelve total 0 y tasa 1.0; no representa telemetría real. |
| `POST /api/v1/auditar` | Sí | 10/min | Ruta heredada; intenta llamar `auditor.procesar_documento()`, método que no está definido en la clase auditora actual. No forma parte de la integración activa. |
| `POST /feedback` | No | No definido | Ruta heredada que escribe JSONL en `data/feedback/feedback_dataset.jsonl`; no es el CSV usado por el flujo PHP activo. |
| `POST /modelo/recargar` | No en el código actual | No definido | Recarga el modelo del auditor, pero actualmente carece de dependencia `validar_token_auditor`. Restringir el acceso por red hasta corregirlo. |

Al duplicarse el montaje del router, las rutas del router también quedan disponibles bajo `/api/v1/` (por ejemplo, `/api/v1/fase1/extraer`). La ruta declarada `/api/v1/auditar` puede quedar además duplicada como `/api/v1/api/v1/auditar`. El cliente de Recepción no utiliza estas variantes prefijadas.

### 6.2 Extracción: `POST /fase1/extraer`

**Entrada:** `multipart/form-data`, campo de archivo `imagen`.  
**Protección:** header `X-Auditor-Token`.  
**Respuesta exitosa:** `exito`, `factura_id`, campos extraídos y sugerencias. Ejemplo esquemático:

```json
{
  "exito": true,
  "factura_id": "id-de-proceso",
  "numero_factura": "849201",
  "nombre_proveedor": "Nombre canónico del catálogo",
  "fecha_factura": "01/05/2026",
  "productos": [
    {
      "nombre": "Nombre de catálogo",
      "modelo": "Modelo separado",
      "marca": "Marca",
      "serial": "",
      "cantidad": 2,
      "costo": 125.5,
      "confianza": 0.9
    }
  ],
  "subtotal_factura": 251.0,
  "porcentaje_iva": 16.0,
  "monto_iva": 40.16,
  "total_factura": 291.16,
  "confianza_promedio": 0.83,
  "es_factura_valida": true,
  "mensaje_validacion": "",
  "sugerencias_autocorreccion": []
}
```

`confiabilidad_ml` solo se agrega cuando el modelo se encuentra cargado y es válido. En caso de excepción dentro de la extracción, el handler captura el error y devuelve `{ "exito": false, "error": "..." }`; ese camino puede conservar HTTP 200 al no lanzar una excepción HTTP explícita.

### 6.3 Verificación y comparación

- El método `AuditorRecepcion.verificar_coherencia()` existe y compara una factura guardada en la caché en memoria con valores del formulario.
- El endpoint `/fase1/verificar` **no llama** a ese método en su implementación actual.
- `/fase1/comparar-directo` sí ejecuta `auditor.comparar_directo()`, que extrae y luego verifica, pero no es el camino activo de `recepcion.js`.
- La pantalla actual usa su lógica JavaScript de discrepancias. No se debe tomar la respuesta fija de `/fase1/verificar` como una verificación real.

### 6.4 Rutas heredadas

`/feedback`, `/api/v1/auditar`, `/fase1/estadisticas` y el registro en `ModelRegistry` pertenecen a una estructura anterior que permanece junto a la implementación actual. Antes de integrarlos, hay que corregir y probar sus contratos, autorización y persistencia; no se deben presentar como funciones activas ya verificadas.

---

## 7. Integración con el sistema web

### 7.1 Cliente JavaScript

`microservicio/javascript/asistente_recepcion.js` proporciona `AsistenteRecepcionIA`, con métodos para:

- comprobar conectividad (`_verificarConexionAPI`);
- extraer una factura (`extraerDesdeImagen`);
- solicitar una verificación (`verificarCoherencia`);
- comparar directamente (`compararDirecto`);
- mantener un estado de circuito y un timeout en el navegador.

La vista activa crea el cliente con `proxyUrl: 'Modelo/Controlador/recepcion.php'`. La opción directa a FastAPI existe en la clase, pero no es el patrón de autenticación usado por la pantalla.

### 7.2 Proxy PHP

`Modelo/Servicio/RecepcionIAProxy.php`:

- usa `RECEPCION_IA_URL` o `127.0.0.1:8000`;
- comprueba el formato del token antes de solicitudes que no sean health;
- reenvía el token en `X-Auditor-Token`;
- usa cURL;
- aplica reintento limitado y circuito compartido mediante un archivo JSON en el directorio temporal del sistema;
- traduce los fallos de transporte/servicio a respuestas con `contingencia=true`.

El frontend nunca necesita conocer el token. Todas las personas usuarias comparten la credencial privada del servicio que ejecuta PHP.

### 7.3 Formulario y resolución de opciones

`assets/javascript/recepcion.js` aplica las siguientes reglas después de recibir la factura:

- Un número de factura solo se asigna al input si coincide con `^[0-9]{6}$`.
- Proveedor: búsqueda por nombre normalizado y coincidencia exacta con una opción única.
- Producto: selección exacta y única por el nombre que devuelve el catálogo.
- El modelo, marca y demás atributos viajan como atributos de las opciones del selector y se rellenan desde la opción seleccionada.
- Una sugerencia aprendida se presenta para revisión y requiere una acción explícita de aceptación.
- Las sugerencias de subtotal, monto de IVA y total se muestran como referencia, ya que el formulario calcula esos importes a partir de sus componentes.
- Si el proveedor no se resolvió, el selector queda vacío y el estado de discrepancias informa el problema.
- Si el usuario cambia un producto, la discrepancia refleja el producto elegido; la eliminación se registra como tal solamente cuando se usa la acción explícita de remover.

### 7.4 Registro y feedback desde PHP

El controlador `Modelo/Controlador/recepcion.php`:

1. valida los campos y el permiso/sesión de usuario;
2. exige que el flujo asistido haya pasado por la confirmación correspondiente si `usar_ia=true` y no se usa contingencia;
3. registra la recepción mediante el modelo PHP y la base de datos principal;
4. obtiene los valores finales guardados para compararlos con la predicción;
5. llama `agregarFeedbackDataset()` solo si la operación de recepción tuvo éxito y el microservicio fue usado;
6. captura y registra fallos del feedback/reentrenamiento sin convertirlos en una falsa reversión del registro ya guardado.

El modo manual y el modo de contingencia no se deben confundir con una extracción IA confirmada para entrenamiento.

---

## 8. Procesamiento OCR y extracción

### 8.1 Preprocesamiento

El código de `modelo/auditor_recepcion.py` define:

- Para PDF: `pdf2image` y Poppler; en Windows busca una instalación local de Poppler y, si no la encuentra, intenta resolverlo desde `PATH`.
- Conversión: 150 DPI, formato JPEG y páginas 1–5 como máximo.
- En PDF, procesa las páginas convertidas con un `ThreadPoolExecutor` de hasta cuatro workers.
- Para una imagen: `cv2.imread`; si OpenCV no puede leerla, se produce error.
- Si `preprocesamiento` está habilitado: conversión a escala de grises, Gaussian blur 3×3, CLAHE y umbral de Otsu.

Los valores por defecto del auditor incluyen `idioma_ocr='spa+eng'`, `preprocesamiento=True`, caché habilitada, tolerancia de costo 5% y tolerancia de cantidad 0. En Windows intenta usar `C:\Program Files\Tesseract-OCR\tesseract.exe` si ese archivo existe; en otros casos deja que Tesseract se resuelva desde `PATH`.

### 8.2 Ejecución Tesseract

La llamada observada es `pytesseract.image_to_string()` con:

- idiomas `spa+eng`;
- `--psm 6`;
- `--oem 3`.

El texto reconocido se divide y normaliza antes de extraer los campos. La llamada utilizada no obtiene puntuaciones de confianza por palabra mediante `image_to_data`; la confianza presentada por la aplicación se calcula posteriormente con una heurística del auditor.

### 8.3 Número de factura

La configuración por defecto contiene patrones contextuales para etiquetas como factura, invoice, inv, bill, receipt, folio, correlativo, comprobante/documento y términos de número. Se permite una distancia corta entre etiqueta y valor; además, si la etiqueta queda en una línea y el número aparece inmediatamente en las siguientes líneas no vacías, el auditor intenta encontrarlo.

La aceptación final se hace con `re.fullmatch(r"[0-9]{6}", candidato)`:

- válido: exactamente seis dígitos ASCII;
- inválido: número sin contexto, longitud distinta, letras, símbolos, sufijos, barras o dígitos Unicode no ASCII.

La extracción de número no inventa un valor a partir de un número aislado. Si no se encuentra de forma segura, devuelve cadena vacía; el formulario requiere revisión manual.

### 8.4 Proveedor

El extractor no devuelve directamente el texto que parece un proveedor. Primero filtra candidatos del texto y luego los compara con `catalogo_proveedores`:

1. examina líneas iniciales con etiquetas del tipo proveedor/vendedor/supplier/vendor, razón social/company/empresa o sold by/billed by/from;
2. busca coincidencias normalizadas únicas en las primeras 20 líneas;
3. si no hay coincidencia directa, puede aceptar una coincidencia aproximada etiquetada solo cuando alcanza el umbral implementado y supera suficientemente la segunda mejor;
4. ante falta de catálogo, ambigüedad o evidencia insuficiente, retorna `""`.

La respuesta contiene el nombre canónico del proveedor, no un texto libre que el selector no pueda elegir.

### 8.5 Productos

El extractor intenta, en orden:

1. detectar filas tabulares con descripción, marca, serial, precio unitario, cantidad y total;
2. si no hay filas tabulares, procesar líneas con encabezados de descripción/artículo/código;
3. intentar formatos de estilo libre;
4. usar una extracción general como último recurso.

Para filas tabulares:

- normaliza signos y separadores en la descripción;
- si el total de línea no concuerda con precio unitario × cantidad por más de 2% (o 0,01), recalcula el unitario como total/cantidad;
- puede asignar una línea siguiente como modelo, salvo si corresponde a subtotales/impuestos/totales.

Después compara con el catálogo. La coincidencia considera nombre exacto, nombre incluido en descripción y evidencia adicional de modelo/marca. La comparación aproximada exige evidencia de modelo o marca y un umbral de similitud; si el mejor resultado es ambiguo, no asigna producto.

Cuando no existe coincidencia segura:

- no fuerza un `id_producto`;
- intenta separar nombre/modelo con heurísticas;
- conserva datos OCR para su revisión, pero el frontend solo selecciona una opción si existe coincidencia exacta con el nombre de una opción.

### 8.6 Montos, subtotal e impuestos

- El subtotal se busca por etiquetas como subtotal, amount o amount before tax.
- Si no aparece, se estima como suma de cantidad × costo unitario de productos extraídos.
- IVA/tax se lee solo cuando aparece de forma explícita; se aceptan porcentaje y monto. Si hay monto, no hay porcentaje y existe subtotal, calcula el porcentaje derivado.
- La ausencia de IVA no provoca que se invente un impuesto.
- El total se busca con etiquetas específicas que evitan tomar subtotal como total. Si no aparece, se calcula subtotal + monto de IVA.
- `_parsear_monto()` contempla puntos/comas como separador decimal o de miles, eligiendo el separador decimal según la posición cuando aparecen ambos.

Las estimaciones calculadas deben distinguirse de valores literalmente impresos en la factura. La respuesta actual no siempre expone por separado el origen de cada monto.

### 8.7 Validación de documento

`_validar_es_factura()` devuelve válido si se cumplen todos estos filtros:

1. aparecen al menos dos palabras clave reconocidas;
2. hay al menos dos campos esenciales entre número, proveedor, fecha y productos;
3. subtotal y total no son ambos cero.

Es una heurística y no una validación fiscal o legal. No garantiza autenticidad, ni exige por sí sola que el número de factura cumpla el formato de seis dígitos; la validación del campo se hace aparte.

---

## 9. Catálogo y base de conocimiento

### 9.1 Fuente primaria: catálogo MySQL

Al construir `AuditorRecepcion`, `FabricaAdaptadores` crea un adaptador conforme a `BD_TIPO`:

- **MySQL:** usa `tbl_productos` + `tbl_modelos` + `tbl_marcas` y `tbl_proveedores`; solo registros habilitados.
- **CSV:** usa archivos esperados por la configuración interna bajo `microservicio/conocimiento_externo/`.

Los productos incluyen identificador, nombre, modelo, marca y precio/costo unitario. Los proveedores incluyen identificador y nombre. El catálogo cargado se mantiene en memoria en la instancia global del auditor; los cambios en BD no se reflejan hasta reiniciar el proceso.

Si el constructor MySQL falla, el auditor intenta cargar CSV cuando el archivo de productos existe. Si la consulta del adaptador MySQL devuelve una lista vacía sin lanzar excepción, el código recibe esa lista vacía y no necesariamente recurre al CSV. Si no hay datos cargados, producto/proveedor no pueden canonicalizarse.

### 9.2 Archivos YAML opcionales

Las rutas internas que busca `auditor_recepcion.py` son:

```text
microservicio/conocimiento_externo/patrones_factura.yaml
microservicio/conocimiento_externo/reglas_validacion.yaml
microservicio/conocimiento_externo/configuracion_ml.yaml
microservicio/conocimiento_externo/catalogo_productos.csv
microservicio/conocimiento_externo/proveedores.csv
```

Para patrones, el cargador espera una estructura con `campos`, y cada campo contiene una lista `patrones`. Si falta el YAML, usa `PATRONES_POR_DEFECTO`. Para reglas y configuración ML, la ausencia produce diccionarios vacíos y el auditor continúa con sus valores por defecto.

**Estado de archivos al preparar V5:** no se encontraron los tres YAML ni los CSV en `conocimiento_externo`; esa carpeta contiene una guía de configuración. No asumir que las reglas YAML descritas en dicha guía están instaladas o activas.

### 9.3 Consideraciones de cambio

- La configuración externa no se recarga por solicitud; reiniciar FastAPI tras modificarla.
- Los patrones YAML deben cumplir el esquema que entiende `cargar_patrones_yaml`; no basta con copiar el ejemplo de la guía si el nivel `campos` no coincide.
- El modelo/catalogo de proveedores no forma una “base de entrenamiento OCR”: es conocimiento de catálogo para resolución de entidades.
- El CSV fallback solo funciona si existen ambos archivos con encabezados aceptados por `AdaptadorCSV`.

---

## 10. Validación, discrepancias y confirmación humana

### 10.1 Diferencia entre tres validaciones

1. **Validez aproximada del documento:** keywords, campos esenciales y montos en `_validar_es_factura()`.
2. **Resolución de entidad al catálogo:** el backend Python puede canonicalizar productos/proveedor solo con evidencia suficiente.
3. **Comparación entre factura y formulario:** en la pantalla actual se hace en JavaScript contra el resultado OCR y los campos editados.

No deben presentarse como una única verificación server-side completa. La ruta HTTP `/fase1/verificar` todavía es provisional.

### 10.2 Reglas visibles para el operador

- Número no detectado o que no coincide con lo escrito: discrepancia pendiente.
- Proveedor vacío: discrepancia pendiente aunque no exista nombre OCR para comparar.
- Proveedor cambiado a otro del catálogo: se informa el valor de factura frente al valor del selector.
- Producto cambiado: se compara el nombre seleccionado con el nombre extraído.
- Producto eliminado: solo se informa como removido si el usuario usó el botón de quitar; un reemplazo no equivale a eliminación.
- IVA/totales: se comprueban con la lógica del formulario y sus valores calculados.
- El registro puede continuar manualmente o con confirmación explícita de las diferencias, conforme a las validaciones del controlador.

### 10.3 Límites

- Una coincidencia de OCR/catálogo no certifica que el producto facturado sea legítimo o que la factura sea auténtica.
- La lista de discrepancias de la UI no debe interpretarse como una auditoría legal o contable.
- La persona usuaria debe revisar los valores antes del registro.
- La regla de seis dígitos es requisito del sistema actual; no es una regla universal de numeración de facturas.

---

## 11. Modelo de confiabilidad

### 11.1 Propósito y feature

El `RandomForestClassifier` estima una clase/probabilidad de extracción confiable en función de **una sola característica**, `confianza_ocr_promedio`. Los errores del documento actual no se conocen antes de la revisión del usuario, por lo que no se usan como features durante inferencia.

La confianza entregada al modelo se calcula en `AuditorRecepcion._calcular_confianza_promedio()`:

- si no hay productos, devuelve 0,5;
- de lo contrario, combina el promedio de `ProductoExtraido.confianza` (60%) y la proporción de campos principales presentes —número, proveedor, fecha— (40%).

Las confianzas de producto provienen de los métodos de extracción. No son probabilidades calibradas del OCR.

### 11.2 Etiqueta de entrenamiento

Para cada fila, `retrain.py` calcula el promedio de:

```text
error_proveedor
error_correlativo
error_productos
error_subtotal
error_total
```

- etiqueta `1` (confiable), si el promedio es menor que `0,3`;
- etiqueta `0` (no confiable), si el promedio es igual o mayor que `0,3`.

Se necesitan como mínimo cinco registros válidos y ejemplos de ambas clases. Si falta una clase, el entrenamiento falla y no publica el nuevo artefacto.

### 11.3 Algoritmo y métricas

- `RandomForestClassifier(n_estimators=100, max_depth=10, random_state=42)`.
- Métrica `accuracy_entrenamiento` se calcula sobre las mismas filas utilizadas para entrenar.
- No equivale a una métrica de generalización: el clasificador no usa hold-out ni validación cruzada para su puntuación.
- El módulo de reglas de corrección sí implementa una evaluación leave-one-reception-out; esa evaluación corresponde a las sugerencias, no al Random Forest de confiabilidad.

### 11.4 Inferencia y recarga

1. `_predecir_confiabilidad()` solicita revisar el modelo en disco.
2. `recargar_modelo()` compara tamaño y `mtime_ns`; si cambió, carga y valida el bundle.
3. Se aceptan modelos con clases exactamente `{0,1}` y una feature. Los bundles actuales admiten versión 2 o 3; la rutina de entrenamiento actual crea versión 3.
4. Si el archivo falta o no es válido, se desactiva la predicción ML, pero extracción OCR continúa.
5. Si la predicción falla, el método retorna 0 y registra advertencia. La respuesta HTTP solo agrega `confiabilidad_ml` si `modelo_ml` quedó cargado.

El endpoint de recarga manual existe como `POST /modelo/recargar`, pero actualmente no exige el token del auditor. No lo expongas fuera del host hasta protegerlo.

---

## 12. Feedback, aprendizaje y reentrenamiento

### 12.1 Qué datos se guardan

El flujo activo de feedback está en `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php`, no en el endpoint heredado `/feedback`.

Después de un registro asistido exitoso, almacena una fila en:

```text
microservicio/entrenamiento/feedback_dataset.csv
```

La fila conserva timestamp, ids de recepción/factura, predicción y valor confirmado para proveedor, correlativo, productos, subtotal y total, errores por campo, snapshots de campos predichos/confirmados, discrepancias y `confianza_ocr_promedio`. No almacena la imagen de factura en esa fila.

Los nombres de productos confirmados vienen de las opciones seleccionadas en el sistema; los valores de la predicción vienen de la extracción. El endpoint de extracción serializa el costo como `costo`, que es el nombre esperado por el feedback PHP.

### 12.2 Cómo se calculan los errores

- Proveedor y correlativo: comparación normalizada y distancia de Levenshtein relativa.
- Subtotal y total: diferencia relativa respecto al valor confirmado.
- Productos: empareja por nombre suficientemente parecido y promedia errores de cantidad/costo; un producto sin pareja cuenta como error completo.
- El CSV debe mantener valores de error/confianza finitos y dentro de `[0,1]` para que `retrain.py` acepte el dataset.

### 12.3 Aprendizaje de correcciones

`modelo/corrector_feedback.py` deriva reglas de corrección del mismo CSV:

- requiere id de recepción para contar evidencia independiente;
- exige al menos dos recepciones distintas con el mismo origen y destino;
- solo conserva un destino unánime; ante destinos contradictorios se abstiene;
- normaliza texto (casefold, eliminación de acentos y puntuación) para claves;
- empareja productos con señales de nombre, modelo, marca y serial;
- las correcciones numéricas de documento están condicionadas por el proveedor y el conjunto/contexto de productos; evita transferir importes a facturas de productos distintos;
- `fecha_factura` no forma parte de los campos que se aprenden;
- las sugerencias se devuelven con `requiere_confirmacion=true`; no modifican el OCR ni el formulario por sí solas.

En JavaScript:

- proveedor solo se aplica al selector si hay opción exacta;
- número solo se aplica si es exactamente seis dígitos ASCII;
- campos de productos verifican que la opción del catálogo sea identificable;
- importes derivados pueden mostrarse como referencia y no sustituir el cálculo del formulario.

Esto es aprendizaje basado en reglas/supervisión acumulada, no entrenamiento de un modelo de lenguaje ni aprendizaje automático en línea por cada llamada OCR.

### 12.4 Reentrenamiento

Desde PHP se invoca `microservicio/retrain.py` cuando:

- se añade feedback y el CSV tiene cinco registros o más; o
- se elimina feedback por anulación y quedan cinco registros o más.

El script:

1. lee el CSV y comprueba columnas requeridas;
2. exige cinco o más filas;
3. valida errores y confianza como números finitos en `[0,1]`;
4. calcula clase por promedio de errores y exige ambas clases;
5. entrena el clasificador con confianza OCR como única feature;
6. construye reglas de corrección y sus métricas;
7. serializa un bundle `artifact_version=3` con `reliability_model`, `corrections` y `correction_policy`;
8. valida el bundle generado;
9. publica el `.pkl` y el JSON de métricas mediante archivos temporales y `os.replace`.

Salidas:

```text
microservicio/modelo/modelo_actual.pkl
microservicio/entrenamiento/metricas_reentrenamiento.json
```

El reentrenamiento se ejecuta en el proceso PHP de registro mediante `exec`, por lo que puede añadir latencia. Deben estar habilitados `exec`, el intérprete Python y permisos de lectura/escritura adecuados. Definir `PYTHON_EXECUTABLE` cuando `python` no sea el intérprete correcto.

Si el entrenamiento no puede completarse, PHP deja un error en el log; el registro de recepción ya guardado no debe considerarse revertido. Si al anular quedan menos de cinco filas, no se reentrena y el artefacto anterior puede conservar reglas/modelo que se generaron con feedback ya eliminado.

### 12.5 Métricas

El JSON incluye, entre otros:

- timestamp y número de filas;
- distribución de clases;
- error medio global y por campo;
- confianza media;
- accuracy de entrenamiento;
- cantidad de reglas activas por campo;
- política de confirmación;
- evaluación leave-one-out de sugerencias.

Con pocos registros, las métricas son exploratorias; no justifican automatizar el registro sin revisión humana.

---

## 13. Ciclo de vida de datos y artefactos

### 13.1 Archivo de factura

- El navegador mantiene una vista previa local para la sesión del modal.
- PHP reenvía el upload a FastAPI.
- FastAPI lo coloca temporalmente en `microservicio/temp/` y lo intenta eliminar tras procesarlo.
- El flujo PHP de registro puede conservar una copia de la factura como comprobante según el modo de registro; esa persistencia es distinta del archivo temporal de OCR y del CSV de feedback.

### 13.2 Caché del auditor

`AuditorRecepcion` define `cache_facturas` y `historial_verificaciones` en memoria. `extraer_desde_imagen()` guarda la factura en caché solo si recibe `id_cache` y `guardar_cache` está habilitado. El método `limpiar_cache()` permite eliminar entradas antiguas, pero no se encontró un scheduler que lo invoque automáticamente.

La ruta `/fase1/verificar` provisional no recupera esa caché; por eso no debe inferirse que la existencia de la caché implique que la ruta verifique correctamente.

### 13.3 Persistencia de entrenamiento

| Artefacto | Persistencia | Productor/consumidor |
|---|---|---|
| `entrenamiento/feedback_dataset.csv` | CSV incremental | Escrito y eliminado por el modelo PHP; leído por `retrain.py`. |
| `modelo/modelo_actual.pkl` | Bundle joblib | Escrito por `retrain.py`; leído por `AuditorRecepcion`. |
| `entrenamiento/metricas_reentrenamiento.json` | JSON | Escrito por `retrain.py` para diagnóstico. |
| `logs_metricas/auditor_recepcion.log` | Log local | Escrito por el módulo auditor. |
| `data/feedback/feedback_dataset.jsonl` | JSONL heredado | Escrito por endpoint `/feedback`; distinto del CSV activo. |

El modelo se carga al iniciar y puede detectar sustituciones atómicas del archivo durante inferencia. Las métricas se publican en otro archivo; la publicación de ambos artefactos no constituye una transacción conjunta.

---

## 14. Seguridad y límites operativos actuales

### 14.1 Controles implementados

- Token de servicio en header `X-Auditor-Token` para `/fase1/extraer`, `/fase1/verificar`, `/fase1/comparar-directo` y `/api/v1/auditar`.
- Comparación del token en tiempo constante mediante `hmac.compare_digest`.
- Longitud aceptada del token: 32–256 caracteres.
- Rate limit para OCR y comparación.
- PHP actúa como proxy para que el secreto no se exponga al navegador.
- Uvicorn se recomienda enlazado a `127.0.0.1`.
- Los temporales de extracción se eliminan al finalizar las rutas cubiertas por el `finally`.

### 14.2 Limitaciones que deben tenerse presentes

1. `POST /modelo/recargar` y otras rutas auxiliares no requieren el token en el código revisado.
2. `/feedback` también carece de autenticación y acepta `ruta_archivo` en su payload; es una ruta heredada, distinta del feedback activo. No exponerla públicamente.
3. `/fase1/estadisticas` no es una métrica real.
4. `/fase1/verificar` devuelve resultado fijo y no debe usarse como control efectivo.
5. `main.py` configura CORS con `allow_origins=["*"]`; restringirlo si existen clientes web directos.
6. La ruta API escribe con nombre de archivo recibido en un directorio relativo `temp`; la capa del microservicio no valida explícitamente tamaño, extensión real ni contenido MIME. La validación de 5 MiB observada en la interfaz es evadible si no hay una política de servidor.
7. El API y la UI aceptan archivos para OCR sin una validación de autenticidad fiscal; el resultado es una ayuda de captura.
8. Los scripts de inicio contienen tokens estáticos; deben migrarse a variables privadas y sus valores rotarse si se expusieron.
9. La conexión MySQL configurada en el adaptador no muestra configuración TLS en el código.
10. El servicio no sustituye permisos ni validaciones del backend PHP al registrar la recepción.

### 14.3 Recomendaciones operativas

- Mantener FastAPI accesible solo desde el host PHP cuando sea posible.
- Proteger todas las rutas auxiliares antes de publicarlas o habilitar acceso de red.
- Mover archivos recibidos a nombres aleatorios controlados por servidor; validar tamaño y tipo tanto en PHP como en FastAPI.
- Restringir CORS a orígenes necesarios o desactivarlo si solo se accede mediante PHP.
- Usar usuario MySQL de solo lectura para el catálogo y guardar sus credenciales en variables privadas.
- Rotar secretos; no copiarlos a ejemplos, scripts o documentación.
- Respaldar el CSV de feedback con acceso limitado; contiene datos operativos de proveedores, productos y recepciones.
- Mantener revisión humana de datos, discrepancias y sugerencias.

---

## 15. Archivos involucrados

### 15.1 Camino activo de extracción y registro

| Archivo | Responsabilidad |
|---|---|
| `Vista/VistaNew/recepcion.php` | Formulario, selector de factura, sección opcional CAI y selectores alimentados desde PHP. |
| `assets/javascript/recepcion.js` | Invoca OCR vía proxy, rellena los controles, valida opciones del catálogo, presenta sugerencias y calcula discrepancias antes del registro. |
| `microservicio/javascript/asistente_recepcion.js` | Cliente HTTP del navegador; usa `proxyUrl` en la vista actual. |
| `Modelo/Controlador/recepcion.php` | Acciones `ia_health`, `ia_extraer`, `ia_verificar`, `ia_comparar`, registro y anulación; guarda feedback después del registro exitoso. |
| `Modelo/Servicio/RecepcionIAProxy.php` | Proxy cURL, token, timeout/reintento acotado y circuit breaker PHP. |
| `Modelo/Config/loadEnv.php` | Carga `.env` de la raíz del proyecto en PHP si las variables aún no existen. |
| `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php` | Errores de feedback, append/eliminación CSV y ejecución de `retrain.py`. |

### 15.2 Camino Python activo

| Archivo | Responsabilidad |
|---|---|
| `microservicio/main.py` | Carga entorno, crea FastAPI, CORS, slowapi y monta routers. |
| `microservicio/config.py` | Configuración general mediante Pydantic Settings. |
| `microservicio/controlador/api_recepcion.py` | Endpoints de recepción, autenticación, serialización y límites por ruta. |
| `microservicio/modelo/auditor_recepcion.py` | OCR, extracción, validación, catálogo, confianza, caché y carga del modelo. |
| `microservicio/modelo/adaptador_bd.py` | Abstracción, MySQL y lectura CSV para catálogos. |
| `microservicio/modelo/corrector_feedback.py` | Construcción, sugerencia y evaluación de reglas aprendidas. |
| `microservicio/retrain.py` | Valida feedback, entrena clasificador y publica el bundle y métricas. |
| `microservicio/entrenamiento/feedback_dataset.csv` | Dataset de comparación predicción/confirmación usado por el flujo PHP. |
| `microservicio/modelo/modelo_actual.pkl` | Bundle entrenado usado por el auditor cuando pasa validación. |
| `microservicio/entrenamiento/metricas_reentrenamiento.json` | Métricas del último entrenamiento. |
| `microservicio/logs_metricas/auditor_recepcion.log` | Log del auditor. |
| `microservicio/requirements.txt` | Dependencias Python. |
| `microservicio/.env.example` | Nombres y ejemplos de variables; no es un almacén de secretos. |

### 15.3 Componentes presentes pero no equivalentes al camino activo

| Archivo/elemento | Observación |
|---|---|
| `modelo/feedback_store.py` | Implementa escritura JSONL independiente; el flujo activo de registro usa el CSV desde PHP. |
| `controlador/api_recepcion.py` → `/feedback` | Escribe `data/feedback/feedback_dataset.jsonl`; no alimenta por sí mismo el reentrenamiento CSV activo. |
| `modelo/model_registry.py` y `models/active_metadata.json` | Registro heredado de versiones; el camino actual del auditor carga `modelo/modelo_actual.pkl` directamente. |
| `modelo/logger.py` | Módulo auxiliar de registro de predicciones; no se encontró llamado desde la ruta activa de extracción. |
| `entrenamiento/entrenar_modelo.py` | Wrapper que delega en `retrain.run_retraining()`. |
| `conocimiento_externo/conocimiento_externo.md` | Guía de configuración; los YAML/CSV que documenta no están presentes en esa carpeta durante esta revisión. |
| `README.md` y documentos V1–V4 | Referencias históricas; algunas describen rutas, límites o archivos que no coinciden con la implementación actual. Contrastar siempre con el código. |

### 15.4 Pruebas existentes relevantes

- `tests/test_auditor_recepcion.py`: productos multilínea, montos, número de seis dígitos, proveedor/producto de catálogo.
- `tests/test_api_auditor_token.py`: token correcto, token no coincidente y configuración inválida.
- `tests/test_corrector_feedback.py`: soporte independiente, consenso, contexto numérico y evaluación leave-one-out.
- `tests/test_retrain.py`: umbral de muestras, clases, rangos, bundle y reglas.
- `tests/test_model_reload.py`: carga/actualización en caliente y validación del artefacto.
- `tests/test_api_model_reload.py`: endpoint de recarga y serialización del costo del producto.

---

## 16. Pruebas y mantenimiento

### 16.1 Ejecutar pruebas unitarias Python

Desde `microservicio`:

```powershell
python -m unittest discover -s tests -p "test_*.py"
```

Las pruebas usan fakes y temporales para varios componentes; no implican por sí solas que MySQL, Tesseract, Poppler o la interfaz estén disponibles en el entorno real.

### 16.2 Comprobaciones manuales de integración

1. Confirmar que Uvicorn inicia en `127.0.0.1:8000`.
2. Probar `GET /health` a través de `ia_health` del proxy PHP.
3. Probar una imagen pequeña válida y un PDF de pocas páginas mediante la pantalla.
4. Comprobar que el JSON de extracción se traduce a los campos esperados y que el valor del producto corresponde exactamente a una opción del selector.
5. Probar número de seis dígitos válido, número de longitud diferente, texto con letras y número sin contexto.
6. Probar proveedor que existe en el catálogo, proveedor ambiguo y catálogo vacío.
7. Revisar discrepancias tras cambiar proveedor/producto, corregir cantidades/costos y quitar una fila explícitamente.
8. Registrar una recepción de prueba asistida y confirmar que solo entonces se añade feedback.
9. Con dataset de prueba aislado, probar reentrenamiento y recarga del bundle.
10. Verificar los logs PHP y Python sin imprimir ni copiar secretos.

No usar registros/facturas reales para pruebas destructivas de anulación o limpieza de datasets. Respaldar primero los datos.

### 16.3 Revisiones operativas periódicas

- Estado del servicio y dependencias OCR externas.
- Conexión y volumen de los catálogos MySQL; confirmar que los estados sean `habilitado`.
- Permisos y crecimiento del CSV de feedback.
- Errores/estado de reentrenamiento y frescura de métricas.
- Tamaño, fecha y validez del bundle del modelo.
- Tamaño y limpieza de temporales.
- Intentos fallidos de autenticación, HTTP 429 y fallos del proxy.
- Exactitud de sugerencias y necesidad de confirmación humana.

---

## 17. Diagnóstico de problemas

| Síntoma | Causa probable / comprobación |
|---|---|
| La UI permite registro manual, pero el OCR no inicia | Verificar que `AsistenteRecepcionIA` esté cargado, que `recepcion.php` esté accesible y que el proxy PHP use el URL correcto. |
| Proxy indica token mal configurado | Revisar que PHP y FastAPI reciben el mismo secreto privado y que tiene entre 32 y 256 caracteres; reiniciar procesos tras cambiarlo. |
| HTTP 403 | Token enviado no coincide con el de FastAPI. |
| HTTP 503 al validar token | Token ausente o fuera del rango aceptado en FastAPI. |
| HTTP 429 | Se excedió el límite del endpoint; esperar el periodo de rate limit. |
| El proxy informa circuito abierto | Fallos repetidos; esperar 120 segundos y comprobar servicio/URL antes de reintentar repetidamente. |
| La carga da error de cURL | Habilitar extensión cURL en PHP y comprobar conectividad del host. |
| PDF falla | Instalar Poppler y pdf2image; confirmar que Poppler se localice en Windows o `PATH`. Solo se procesan las primeras cinco páginas. |
| Imagen no se lee | Probar que OpenCV pueda abrirla, verificar formato real y calidad/resolución. |
| Número queda vacío | Comprobar etiqueta/contexto y que el OCR haya leído exactamente seis dígitos ASCII; no se aceptan prefijos/sufijos. |
| Proveedor queda sin seleccionar | El nombre no coincide de forma única con catálogo activo; revisar MySQL, estado habilitado y coincidencia OCR. |
| Producto no selecciona opción | El producto no tuvo coincidencia segura o el nombre devuelto no coincide exactamente con una opción del selector; revisar catálogo y modelo. |
| Se detecta proveedor/producto incorrecto | Confirmar catálogo cargado al arranque y evitar CSV vacío/desactualizado; revisar logs del auditor. |
| `es_factura_valida=false` | Documento insuficiente para las heurísticas de palabras clave, campos y montos; revisar manualmente el documento. |
| `confiabilidad_ml` no aparece | El bundle no existe, no es válido, tiene clases/características incorrectas o no se pudo cargar. OCR puede seguir funcionando sin esa propiedad. |
| `sugerencias_autocorreccion` está vacío | No hay dos recepciones independientes coincidentes, existe conflicto entre correcciones, el contexto no coincide o los campos no tienen evidencia suficiente. |
| Reentrenamiento falla por muestras | Se requieren al menos cinco filas válidas. |
| Reentrenamiento falla por clases | Se necesitan ejemplos confiables y no confiables conforme a promedio de errores `<0,3`/`>=0,3`. |
| PHP registra recepción, pero entrenamiento no termina | Revisar permiso de `exec`, `PYTHON_EXECUTABLE`, paquetes Python, logs PHP y permisos de archivos. El entrenamiento es secundario al registro ya guardado. |
| La ruta `/fase1/verificar` no muestra diferencias | Es una ruta provisional de respuesta fija; el frontend actual calcula discrepancias en JavaScript. |
| Estadísticas API no cambian | `/fase1/estadisticas` devuelve constantes; no es telemetría real. |
| El catálogo está vacío pese a BD accesible | Revisar estado `habilitado`, nombres de tablas, permisos, configuración, errores SQL y que FastAPI se reinicie después de cambios. |

---

## 18. Cambios históricos y consideraciones futuras

### 18.1 Evolución documentada

Las versiones anteriores describen la evolución desde OCR básico y configuraciones fijas hacia:

- autenticación entre PHP y FastAPI;
- reglas y extracción enriquecida;
- catálogo de proveedores/productos;
- validación de documentos;
- feedback comparativo;
- reentrenamiento del clasificador;
- reglas de corrección basadas en evidencia y confirmación explícita.

V5 mantiene esos antecedentes, pero prioriza el comportamiento observado actualmente. En particular, los valores de configuración y ejemplos que aparecen en V1–V4 deben tratarse como históricos si contradicen rutas/archivos actuales.

### 18.2 Mejoras necesarias para cerrar la brecha entre diseño y producción

1. Completar `/fase1/verificar` para usar una identidad de factura segura y la caché, o eliminar la promesa de verificación server-side.
2. Proteger `/modelo/recargar`, `/feedback` y cualquier ruta auxiliar no destinada a acceso público.
3. Corregir/desactivar `/api/v1/auditar` hasta que exista una función auditora compatible.
4. Aplicar límite de tamaño y validación de tipo/contenido de archivo en PHP y FastAPI; usar nombres aleatorios en temporales.
5. Limitar CORS a los orígenes necesarios.
6. Crear y versionar archivos YAML/CSV externos válidos si se desea operarlos; alinear los nombres y esquemas documentados con el código.
7. Añadir recarga controlada del catálogo o política explícita de reinicio/caché.
8. Añadir evaluación de generalización separada para el clasificador; no usar accuracy de entrenamiento como garantía.
9. Evitar ejecutar entrenamiento síncrono dentro de la solicitud web si afecta latencia; diseñar un worker/cola y estado observable.
10. Mejorar limpieza y sincronización concurrente del CSV al registrar/eliminar/reentrenar.
11. Asegurar que al eliminar feedback y quedar menos del mínimo no se siga presentando un modelo que aún incorpora esa evidencia eliminada; establecer una política de retrain o invalidación.
12. Mantener una política explícita de revisión humana para toda sugerencia aprendida.

---

## Referencias internas

- [Documento técnico V4](./DOCUMENTO_TECNICO_UNIFICADO_V4.md)
- [Guía de entrenamiento V2](./GUIA_ENTRENAMIENTO_V2.md)
- [README del microservicio](../README.md)
- [`main.py`](../main.py)
- [`api_recepcion.py`](../controlador/api_recepcion.py)
- [`auditor_recepcion.py`](../modelo/auditor_recepcion.py)
- [`adaptador_bd.py`](../modelo/adaptador_bd.py)
- [`corrector_feedback.py`](../modelo/corrector_feedback.py)
- [`retrain.py`](../retrain.py)
- [`requirements.txt`](../requirements.txt)
- [Cliente JavaScript del asistente](../javascript/asistente_recepcion.js)
- [Proxy PHP de recepción](../../Modelo/Servicio/RecepcionIAProxy.php)
- [Controlador PHP de recepción](../../Modelo/Controlador/recepcion.php)
- [Modelo PHP de recepción y feedback](../../Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php)
- [JavaScript de la vista de recepción](../../assets/javascript/recepcion.js)
- [Vista de recepción](../../Vista/VistaNew/recepcion.php)

---

**Versión del documento:** 5.0  
**Base de revisión:** código y archivos presentes en el repositorio al momento de esta actualización.  
**Nota:** cuando una descripción del README o de documentos anteriores difiera del comportamiento aquí señalado, comprobar la implementación y las pruebas actuales antes de operar o desarrollar sobre esa función.
