# Guía de entrenamiento del microservicio de auditoría de recepción (Versión 2)

## Objetivo

Esta guía explica cómo el microservicio de auditoría de recepción extrae datos de facturas mediante OCR, cómo se genera el dataset de feedback para aprendizaje, y cómo reentrenar el modelo para mejorar extracciones futuras. El proceso es completamente local y no envía datos de facturas a servicios externos.

**Novedades en Versión 2**:
- El modelo ML ahora se usa en tiempo real para predecir la confiabilidad de las extracciones
- Implementación de autenticación con token para proteger los endpoints
- Alertas en el frontend basadas en la confiabilidad predicha por el modelo
- Simplificación del entrenamiento: el modelo ahora usa solo la confianza OCR como feature

## Requisitos previos

- PHP 7.4 o superior con `curl` y `pdo_mysql`.
- Python 3.10 o superior y las dependencias de `microservicio/requirements.txt`.
- FastAPI con acceso local a `127.0.0.1:8000`.
- Tesseract OCR instalado (Windows: `C:\Program Files\Tesseract-OCR\tesseract.exe`).
- Poppler (para procesamiento de PDFs).
- Un usuario autenticado con permiso de registro de Recepciones.
- El microservicio debe estar ejecutándose para usar la extracción OCR.
- **Variable de entorno AUDITOR_RECEPCION_TOKEN configurada** (NUEVO en V2)

## 1. Configurar y ejecutar FastAPI

### 1.1 Configurar autenticación (NUEVO en V2)

Configura en el entorno que ejecuta PHP:

```text
AUDITOR_RECEPCION_TOKEN=<secreto-aleatorio-privado>
```

Configura exactamente el mismo valor para FastAPI:

```text
AUDITOR_RECEPCION_TOKEN=<secreto-aleatorio-privado>
```

El token debe tener al menos 32 caracteres. Puedes generar uno con:

```powershell
python -c "import secrets; print(secrets.token_urlsafe(32))"
```

En Windows, define las variables para Apache/PHP en el entorno del servicio o mediante `SetEnv` en la configuración local de Apache; reinicia Apache después del cambio. No guardes el secreto en un archivo versionado. Para una prueba local, define el token también en la sesión de PowerShell donde iniciarás FastAPI:

```powershell
$env:AUDITOR_RECEPCION_TOKEN = "pega-aqui-el-token-generado"
```

### 1.2 Iniciar el servidor

Desde la carpeta `microservicio`, inicia el servidor enlazado solo a loopback:

```powershell
uvicorn controlador.api_recepcion:app --host 127.0.0.1 --port 8000
```

Mantén esta terminal activa. El servicio de auditoría de recepción comparte esta aplicación FastAPI con el servicio de analítica predictiva.

**Verificar que el servicio está activo**:
```powershell
curl http://127.0.0.1:8000/
```

Deberías recibir una respuesta JSON con:
```json
{
  "status": "online",
  "service": "Auditoría Casa Lai, C.A.",
  "active_version": "v1.0.0",
  "model_metrics": {...}
}
```

### 1.3 Configurar el cliente JavaScript (NUEVO en V2)

Al inicializar el asistente en el frontend, pasa el token de autenticación:

```javascript
const aiClient = new AsistenteRecepcionIA({
    apiUrl: 'http://127.0.0.1:8000',
    authToken: 'tu-token-aqui',  // Token de autenticación
    debug: true
});
```

El cliente enviará automáticamente el token en el header `X-Auditor-Token` en cada solicitud.

## 2. Entender el recorrido de los datos (Actualizado)

Sigue este orden al leer el código:

1. **Controles de la página:** `Vista/VistaNew/recepcion.php` contiene el formulario de recepción, el selector de archivo de factura, y el botón de registro.
2. **Solicitud del navegador:** la función JavaScript `AsistenteRecepcionIA` en `microservicio/javascript/asistente_recepcion.js` envía la imagen al microservicio con el token de autenticación.
3. **Controlador API:** `microservicio/controlador/api_recepcion.py` valida el token, recibe el archivo en el endpoint `/fase1/extraer`.
4. **Procesamiento OCR:** `microservicio/modelo/auditor_recepcion.py` ejecuta Tesseract OCR para extraer texto de la imagen.
5. **Extracción de campos:** El mismo módulo usa expresiones regulares para extraer número de factura, proveedor, productos, montos, etc.
6. **Predicción de confiabilidad (NUEVO en V2):** El modelo ML predice si la extracción es confiable basándose en la confianza OCR.
7. **Verificación de coherencia:** Compara los datos extraídos con los datos del formulario del usuario.
8. **Registro de recepción:** `Modelo/Controlador/recepcion.php` registra la recepción en la base de datos MariaDB.
9. **Guardado de feedback:** `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php` calcula errores entre predicción y ground truth, y guarda en `microservicio/entrenamiento/feedback_dataset.csv`.
10. **Reentrenamiento:** `microservicio/retrain.py` lee el feedback_dataset.csv, entrena un modelo RandomForest con solo la confianza OCR como feature, y guarda en `microservicio/modelo/modelo_actual.pkl`.

## 3. Flujo de extracción OCR (Actualizado)

### 3.1 Selección de factura por el usuario

1. El usuario accede al módulo de Recepciones.
2. Selecciona un archivo de factura (PDF, JPG, PNG).
3. El JavaScript `asistente_recepcion.js` envía la imagen al microservicio con el token de autenticación.

### 3.2 Procesamiento en el microservicio

**Archivo**: `microservicio/modelo/auditor_recepcion.py`

**Pasos**:

1. **Preprocesamiento de imagen**:
   - Si es PDF: convierte a imágenes usando `pdf2image`
   - Aplica OpenCV: escala de grises, denoising, CLAHE, threshold adaptativo

2. **OCR con Tesseract**:
   - Ejecuta Tesseract con configuración `--psm 6 --oem 3`
   - Idiomas: `spa+eng` (español e inglés)
   - Extrae todo el texto de la imagen

3. **Extracción de campos usando regex**:
   - Número de factura (patrones como "Factura #12345")
   - Nombre de proveedor (patrones como "Proveedor: Amazon")
   - Fecha (formatos DD/MM/YYYY, YYYY-MM-DD)
   - Productos (métodos tabulares y estilo libre)
   - Subtotal, IVA, Total

4. **Cálculo de confianza**:
   - Tesseract devuelve confianza por campo
   - Se calcula promedio de todas las confianzas
   - Rango: 0.0 (baja) a 1.0 (alta)

5. **Predicción de confiabilidad ML (NUEVO en V2)**:
   - Si el modelo está cargado, se llama a `_predecir_confiabilidad(confianza_ocr)`
   - El modelo retorna probabilidad de que la extracción sea confiable (0.0 a 1.0)
   - Esta probabilidad se incluye en los metadatos de extracción

6. **Guardado en caché**:
   - Los datos extraídos se guardan en memoria con un ID único
   - Permite verificación posterior sin reextraer

### 3.3 Respuesta al frontend

El microservicio devuelve un JSON con:
```json
{
  "exito": true,
  "factura_id": "fact_20261022_abc123",
  "numero_factura": "498231",
  "nombre_proveedor": "Amazon",
  "fecha_factura": "01/05/2026",
  "productos": [
    {
      "nombre": "HP DeskJet 2775",
      "modelo": "DeskJet 2775",
      "marca": "HP",
      "serial": "0002",
      "cantidad": 2,
      "costo_unitario": 1520.0,
      "confianza": 0.9
    }
  ],
  "subtotal": 5340.0,
  "total": 6194.4,
  "confianza_promedio": 0.94,
  "confiabilidad_ml": 0.87
}
```

**Campo nuevo en V2**: `confiabilidad_ml` - Probabilidad predicha por el modelo ML de que la extracción sea confiable.

### 3.4 Relleno del formulario y alertas (Actualizado)

El JavaScript recibe los datos y:
- Rellena automáticamente los campos del formulario
- Muestra un panel con los datos extraídos
- Calcula subtotales y totales
- **Verifica la confiabilidad ML y muestra alertas (NUEVO en V2)**:
  - Si `confiabilidad_ml < 0.5`: Alerta de advertencia "El modelo indica que esta extracción puede tener errores"
  - Si `0.5 <= confiabilidad_ml < 0.7`: Alerta informativa "Confiabilidad moderada, se recomienda revisar"
  - Si `confiabilidad_ml >= 0.7`: Sin alerta
- Permite al usuario corregir manualmente si es necesario

## 4. Verificación de coherencia

Sin cambios significativos respecto a la versión anterior.

## 5. Registro de feedback de aprendizaje

Sin cambios significativos respecto a la versión anterior.

## 6. Reentrenamiento del modelo (Actualizado)

### 6.1 Requisitos para reentrenar

**Archivo**: `microservicio/retrain.py`

**Requisitos**:
- Al menos 10 registros en `feedback_dataset.csv`
- Python con dependencias instaladas
- El microservicio no necesita estar ejecutándose

### 6.2 Ejecutar reentrenamiento

Desde la carpeta `microservicio`:

```powershell
python retrain.py
```

### 6.3 Proceso de reentrenamiento (Actualizado)

1. **Lectura del dataset**:
   - Lee `entrenamiento/feedback_dataset.csv`
   - Verifica que haya al menos 10 registros

2. **Cálculo de métricas**:
   - Calcula error promedio por campo
   - Calcula confianza OCR promedio

3. **Construcción de features (SIMPLIFICADO en V2)**:
   - Crea matriz X con 1 feature:
     - confianza_ocr_promedio
   - **Cambio**: Antes se usaban 6 features (errores por campo + confianza)
   - **Justificación**: Los errores no están disponibles durante la extracción

4. **Construcción de target**:
   - target = 1 si error_total < 0.3 (extracción confiable)
   - target = 0 si error_total >= 0.3 (extracción no confiable)

5. **Entrenamiento del modelo**:
   - Usa RandomForestClassifier
   - n_estimators=100, max_depth=10
   - Entrena para predecir confiabilidad basándose solo en confianza OCR

6. **Guardado del modelo**:
   - Guarda en `modelo/modelo_actual.pkl`
   - Guarda métricas en `entrenamiento/metricas_reentrenamiento.json`

### 6.4 Resultado esperado

```
Procesando 15 registros de feedback para reentrenamiento...

=== MÉTRICAS DE ERROR ACTUALES ===
Error Proveedor: 33.33%
Error Correlativo: 0.00%
Error Productos: 66.67%
Error Subtotal: 0.00%
Error Total: 0.00%
Confianza OCR Promedio: 85.00%

=== REENTRENAMIENTO COMPLETADO ===
Modelo guardado en: modelo/modelo_actual.pkl
Métricas guardadas en: entrenamiento/metricas_reentrenamiento.json
Precisión del modelo: 93.33%
```

### 6.5 Uso del modelo entrenado (Actualizado)

**Estado actual en V2**: El modelo entrenado **SÍ se usa en el flujo de extracción**.

**Ubicación**: `auditor_recepcion.py`, método `_predecir_confiabilidad()`

**Momento de predicción**: Inmediatamente después de la extracción OCR.

**Flujo**:
1. Después de extracción OCR, se calcula `confianza_ocr_promedio`
2. Se llama a `_predecir_confiabilidad(confianza_ocr_promedio)`
3. El modelo retorna probabilidad de que la extracción sea confiable
4. Esta probabilidad se incluye en la respuesta JSON como `confiabilidad_ml`
5. El JavaScript muestra alertas basándose en esta probabilidad

**Beneficios**:
- El usuario recibe feedback inmediato sobre la calidad de la extracción
- Extracciones de baja confianza se marcan para revisión manual
- El sistema aprende continuamente de las correcciones del usuario

## 7. Leer estados y resultados (Actualizado)

- **Extracción exitosa**: Todos los campos extraídos con confianza > 0.6
- **Extracción parcial**: Algunos campos extraídos, otros vacíos
- **Extracción fallida**: Ningún campo extraído, confianza muy baja
- **Discrepancias CRÍTICAS**: Número de factura incorrecto (bloquea registro)
- **Discrepancias ALTAS**: Proveedor o total incorrecto (alerta fuerte)
- **Discrepancias MEDIAS**: Producto o subtotal incorrecto (alerta moderada)
- **Discrepancias BAJAS**: Campo secundario incorrecto (alerta suave)
- **Confianza alta**: ≥ 0.85 (extracción muy confiable)
- **Confianza media**: 0.60 ≤ confianza < 0.85 (extracción moderadamente confiable)
- **Confianza baja**: < 0.60 (extracción poco confiable)
- **Confiabilidad ML alta (NUEVO)**: ≥ 0.70 (modelo predice extracción confiable)
- **Confiabilidad ML media (NUEVO)**: 0.50 ≤ confiabilidad < 0.70 (modelo predige confiabilidad moderada)
- **Confiabilidad ML baja (NUEVO)**: < 0.50 (modelo predice extracción no confiable)

## 8. Validar que feedback se está acumulando

Sin cambios significativos respecto a la versión anterior.

## 9. Validar que el modelo ML se está usando (NUEVO en V2)

Después de cada extracción con el microservicio activado, confirma:

1. El archivo `microservicio/modelo/modelo_actual.pkl` existe
2. Los logs de `auditor_recepcion.log` muestran:
   - "Modelo ML cargado exitosamente desde modelo_actual.pkl"
   - "Confiabilidad ML predicha: X.XX%"
3. La respuesta JSON del endpoint `/fase1/extraer` incluye `confiabilidad_ml`
4. El JavaScript muestra alertas basándose en `confiabilidad_ml`

## 10. Problemas frecuentes (Actualizados)

| Mensaje o síntoma | Comprobación |
|---|---|
| Servicio no disponible | FastAPI está iniciado y escucha en `127.0.0.1:8000`. |
| **Error 403 - Token inválido (NUEVO)** | AUDITOR_RECEPCION_TOKEN está configurado y coincide en PHP y FastAPI. |
| **Error 503 - Token muy corto (NUEVO)** | AUDITOR_RECEPCION_TOKEN tiene al menos 32 caracteres. |
| OCR no disponible | Tesseract está instalado en `C:\Program Files\Tesseract-OCR\tesseract.exe`. |
| PDF no se procesa | Poppler está instalado y en el PATH. |
| No se extraen datos | Imagen es legible, no está borrosa ni rotada. |
| Confianza muy baja | Imagen de buena calidad, resolución adecuada. |
| Discrepancias CRÍTICAS | Número de factura en la imagen coincide con el formulario. |
| feedback_dataset.csv vacío | Se han registrado recepciones con `usar_ia = true`. |
| Reentrenamiento falla | **Hay al menos 10 registros en feedback_dataset.csv** y el token está configurado. |
| **No se muestra confiabilidad_ml (NUEVO)** | modelo_actual.pkl existe y se cargó correctamente. |
| **Modelo ML no se carga (NUEVO)** | Verificar que modelo_actual.pkl existe en microservicio/modelo/. |

## 11. Archivos del microservicio y su rol (Actualizados)

### 11.1 Archivos ACTIVOS en el flujo

| Archivo | Rol | Estado |
|---------|-----|--------|
| `recepcion.js` | Frontend JavaScript | ✅ Activo - Maneja formulario y envía al microservicio |
| `asistente_recepcion.js` | Cliente JS del microservicio | ✅ Activo - Interfaz con API FastAPI, envía token |
| `api_recepcion.py` | Controlador API FastAPI | ✅ Activo - Endpoint `/fase1/extraer` con autenticación |
| `auditor_recepcion.py` | Modelo OCR y extracción | ✅ Activo - Tesseract + regex + predicción ML |
| `recepcion.php` (controlador) | Backend PHP | ✅ Activo - Registra recepción y captura feedback |
| `Recepcion.php` (modelo) | Modelo PHP | ✅ Activo - `agregarFeedbackDataset()` |
| `feedback_dataset.csv` | Dataset de feedback | ✅ Activo - Acumula predicciones vs ground truth |
| `retrain.py` | Script de reentrenamiento | ✅ Activo - Entrena RandomForest con 1 feature |
| `modelo_actual.pkl` | Modelo entrenado | ✅ Activo - **Ahora se usa en extracción** |

### 11.2 Archivos NO UTILIZADOS actualmente

| Archivo | Rol | Estado |
|---------|-----|--------|
| `feedback_store.py` | Almacenamiento de feedback en JSONL | ❌ No usado - PHP escribe directamente al CSV |
| `model_registry.py` | Gestión de versiones de modelos | ❌ No usado - Modelo se guarda directamente como .pkl |
| `entrenar_modelo.py` | Orquestador de reentrenamiento | ❌ No usado - `retrain.py` hace el reentrenamiento |
| `logger.py` | Función de logging de predicciones | ❌ No usado - PHP escribe directamente al CSV |

## 12. Seguridad y operación (Actualizado)

- Mantén FastAPI en loopback (127.0.0.1) cuando PHP comparte el mismo host.
- **El microservicio ahora usa autenticación con token (NUEVO en V2)**.
- Configura `AUDITOR_RECEPCION_TOKEN` en el entorno de PHP/Apache y FastAPI.
- El token debe tener al menos 32 caracteres.
- No guardes el secreto en un archivo versionado.
- Considera rotar el token periódicamente.
- Conserva los archivos del modelo y de feedback fuera de Git.
- Respálda `feedback_dataset.csv` periódicamente.
- El endpoint de entrenamiento escribe un único artefacto, así que ejecuta FastAPI con un solo worker si se activa entrenamiento concurrente.
- Las imágenes de facturas se eliminan del directorio temporal después del procesamiento.
- Los datos de feedback no contienen la imagen de la factura, solo los datos extraídos y confirmados.
- **El sistema puede funcionar sin el modelo ML (degradación graceful)** - Si modelo_actual.pkl no existe, el sistema continúa funcionando con OCR + regex, pero sin predicciones de confiabilidad.

## 13. Comparación con Versión 1

### 13.1 Cambios principales

| Aspecto | Versión 1 | Versión 2 |
|---------|-----------|-----------|
| Features de entrenamiento | 6 (errores + confianza) | 1 (solo confianza) |
| Modelo en inferencia | No usado | Sí usado |
| Autenticación API | No | Token HMAC |
| Alertas de confiabilidad | No | Sí (basadas en ML) |
| Degradación sin modelo | No definido | Graceful |

### 13.2 Beneficios de la actualización

1. **Predicción en tiempo real**: El modelo ahora predice la confiabilidad durante la extracción
2. **Mejor experiencia de usuario**: Alertas contextuales ayudan al usuario a identificar extracciones problemáticas
3. **Mayor seguridad**: Autenticación protege los endpoints del microservicio
4. **Simplificación**: Menos features = entrenamiento más rápido y más fácil de mantener
5. **Resiliencia**: El sistema puede funcionar sin el modelo ML si es necesario

## 14. Próximos pasos recomendados

1. **Implementar validación explícita del modelo**
   - Agregar partición train/test
   - Calcular métricas de generalización
   - Implementar validación temporal

2. **Mejorar patrones regex**
   - Agregar más patrones para diferentes formatos de factura
   - Probar con muestras reales de proveedores variados

3. **Implementar pruebas unitarias**
   - Crear `microservicio/tests/test_auditor_recepcion.py`
   - Probar extracción con imágenes de prueba
   - Probar verificación de coherencia
   - Probar predicción ML

4. **Implementar monitoreo avanzado**
   - Configurar alertas para latencia alta
   - Monitorear tasa de discrepancias por campo
   - Revisar confianza OCR promedio periódicamente
   - Monitorear confiabilidad ML promedio

5. **Integrar modelo en priorización de revisiones**
   - Usar confiabilidad ML para priorizar revisiones manuales
   - Marcar extracciones de baja confianza para revisión humana

6. **Implementar aprendizaje activo**
   - Identificar casos de borde donde el modelo es incierto
   - Solicitar feedback humano en esos casos
   - Usar feedback para mejorar el modelo
