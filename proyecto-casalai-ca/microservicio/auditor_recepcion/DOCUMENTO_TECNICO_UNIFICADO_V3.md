# Documento Técnico Unificado: Microservicio de Auditoría de Recepción (Versión 3)
## Guía Completa de Arquitectura, Entrenamiento y Operación

---

## Índice

1. [Resumen de Cambios](#1-resumen-de-cambios)
2. [Configuración y Ejecución](#2-configuración-y-ejecución)
3. [Arquitectura del Modelo](#3-arquitectura-del-modelo)
4. [Flujo de Extracción OCR](#4-flujo-de-extracción-ocr)
5. [Verificación de Coherencia](#5-verificación-de-coherencia)
6. [Registro de Feedback](#6-registro-de-feedback)
7. [Reentrenamiento del Modelo](#7-reentrenamiento-del-modelo)
8. [Validaciones de Factura](#8-validaciones-de-factura)
9. [Seguridad y Rate Limiting](#9-seguridad-y-rate-limiting)
10. [Métricas y Monitoreo](#10-métricas-y-monitoreo)
11. [Pruebas y Mantenimiento](#11-pruebas-y-mantenimiento)
12. [Solución de Problemas](#12-solución-de-problemas)

---

## 1. Resumen de Cambios

### 1.1 Cambios desde Versión 2

Esta versión unificada incorpora las siguientes mejoras implementadas:

1. **Validación de formato de factura**: Detección automática de documentos que no son facturas válidas
2. **Validación de productos en catálogo**: Verificación de que los productos extraídos están en el catálogo del sistema
3. **Bloqueo de botón Guardar**: Prevención de registro con facturas inválidas
4. **Rate limiting con slowapi**: Protección contra abuso de API con límites por IP
5. **Mejoras en patrones regex**: Soporte expandido para diferentes formatos de factura internacionales
6. **Mejoras en parsing de montos**: Manejo robusto de formatos numéricos con separadores variados

### 1.2 Cambios desde Versión 1

| Aspecto | Versión 1 | Versión 2 | Versión 3 |
|---------|-----------|-----------|-----------|
| Features de entrenamiento | 6 (errores + confianza) | 1 (solo confianza) | 1 (solo confianza) |
| Modelo en inferencia | No usado | Sí usado | Sí usado |
| Autenticación API | No | Token HMAC | Token HMAC |
| Alertas de confiabilidad | No | Sí (basadas en ML) | Sí (basadas en ML) |
| Validación de factura | No | No | Sí |
| Validación de catálogo | No | No | Sí |
| Rate limiting | No | No | Sí (slowapi) |
| Degradación sin modelo | No definido | Graceful | Graceful |

---

## 2. Configuración y Ejecución

### 2.1 Requisitos Previos

- PHP 7.4 o superior con `curl` y `pdo_mysql`
- Python 3.10 o superior y las dependencias de `microservicio/requirements.txt`
- FastAPI con acceso local a `127.0.0.1:8000`
- Tesseract OCR instalado (Windows: `C:\Program Files\Tesseract-OCR\tesseract.exe`)
- Poppler (para procesamiento de PDFs)
- Un usuario autenticado con permiso de registro de Recepciones
- El microservicio debe estar ejecutándose para usar la extracción OCR

### 2.2 Configurar Autenticación

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

**En Windows**:
- Define las variables para Apache/PHP en el entorno del servicio o mediante `SetEnv` en `.htaccess`
- Reinicia Apache después del cambio
- No guardes el secreto en un archivo versionado
- Para una prueba local, define el token en la sesión de PowerShell:

```powershell
$env:AUDITOR_RECEPCION_TOKEN = "pega-aqui-el-token-generado"
```

**Configuración en .htaccess**:
```apache
SetEnv AUDITOR_RECEPCION_TOKEN "tu-token-aqui"
```

**Configuración en .env (FastAPI)**:
```text
AUDITOR_RECEPCION_TOKEN=tu-token-aqui
ANALITICA_PREDICTIVA_TOKEN=tu-token-aqui
```

### 2.3 Iniciar el Servidor

Desde la carpeta `microservicio`, inicia el servidor enlazado solo a loopback:

```powershell
uvicorn main:app --host 127.0.0.1 --port 8000 --reload
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
  "project": "Microservicio de Recepción y Verificación Asistida - Casalai AI",
  "message": "El microservicio está operando correctamente."
}
```

### 2.4 Configurar el Cliente JavaScript

Al inicializar el asistente en el frontend, pasa el token de autenticación:

```javascript
const aiClient = new AsistenteRecepcionIA({
    apiUrl: 'http://127.0.0.1:8000',
    authToken: 'tu-token-aqui',  // Token de autenticación
    debug: true
});
```

El cliente enviará automáticamente el token en el header `X-Auditor-Token` en cada solicitud.

---

## 3. Arquitectura del Modelo

### 3.1 Algoritmo y Características

**Algoritmo**: RandomForestClassifier para clasificación de confiabilidad de extracción

**Características del Modelo**:
- **Tipo de modelo**: Clasificación binaria (Random Forest)
- **Objetivo**: Predecir si una extracción OCR es confiable (error_total < 0.3) basándose únicamente en la confianza OCR
- **Features de entrada**: 1 característica (confianza_ocr_promedio)
- **Mínimo de muestras**: 10 registros en feedback_dataset.csv
- **Algoritmo base**: Tesseract OCR + Regex contextual
- **Integración**: Modelo cargado y usado durante la extracción en tiempo real

**Ecuación del Modelo**:
```
target = 1 si error_total < 0.3 (extracción confiable)
target = 0 si error_total >= 0.3 (extracción no confiable)

feature = confianza_ocr_promedio (0.0 a 1.0)
prediccion = modelo.predict_proba([[confianza_ocr_promedio]])
confiabilidad_ml = prediccion[1] (probabilidad de ser confiable)
```

**Features de entrada**:
- `confianza_ocr_promedio`: Confianza promedio de Tesseract (0.0 a 1.0)
  - **Rango**: 0.0 (baja) a 1.0 (alta)
  - **Justificación**: Los errores no están disponibles durante la extracción (no hay ground_truth)
  - **Beneficio**: Permite predicción en tiempo real durante la extracción

### 3.2 Métricas de Entrenamiento

#### 3.2.1 Métricas Calculadas por el Modelo

**Error por Campo**:
- **Fórmula**: `error = 1 si prediccion != ground_truth, 0 si coinciden`
- **Implementación**: Calculado en PHP (`agregarFeedbackDataset()`)
- **Significado**: Proporción de extracciones incorrectas por campo
- **Uso**: Target para entrenamiento del modelo de clasificación

**Confianza OCR Promedio**:
- **Fórmula**: Promedio de confianza de Tesseract por campo extraído
- **Implementación**: Línea 143 de `auditor_recepcion.py`
- **Rango**: 0.0 (baja) a 1.0 (alta)
- **Uso**: Feature principal para entrenamiento y predicción

**Muestras de Entrenamiento**:
- **Requisito mínimo**: 10 registros en feedback_dataset.csv
- **Fuente de datos**: Recepciones registradas con microservicio activado
- **Acumulación**: Cada registro de recepción añade una fila al dataset

#### 3.2.2 Métricas Disponibles en la API

El modelo expone las siguientes métricas en el artefacto JSON (`metricas_reentrenamiento.json`):

```json
{
  "fecha_reentrenamiento": "2026-10-23T...",
  "num_registros": 15,
  "error_promedio_proveedor": 0.33,
  "error_promedio_correlativo": 0.0,
  "error_promedio_productos": 0.67,
  "error_promedio_subtotal": 0.0,
  "error_promedio_total": 0.0,
  "confianza_promedio": 0.85,
  "precision_modelo": 0.93
}
```

**Descripción de campos**:
- `fecha_reentrenamiento`: Timestamp del último reentrenamiento
- `num_registros`: Cantidad de muestras usadas para entrenar
- `error_promedio_*`: Promedio de error por campo en el dataset
- `confianza_promedio`: Confianza OCR promedio del dataset
- `precision_modelo`: Precisión del RandomForest en el conjunto de entrenamiento

### 3.3 Métricas de Validación

#### 3.3.1 Validación por Hold-out

**Método actual**: El modelo no implementa validación explícita. Usa todos los datos disponibles para entrenamiento.

**Limitación**: No hay partición train/test, por lo que las métricas de precisión son de entrenamiento, no de validación generalizada.

**Recomendación para mejora**: Implementar validación temporal con rolling window:
- Usar primeros 70% de registros para entrenar
- Validar en últimos 30%
- Calcular métricas de generalización

#### 3.3.2 Métricas de Predicción (Inferencia)

**Estado actual**: El modelo RandomForest entrenado **SÍ se usa en el flujo de inferencia**.

**Ubicación**: `auditor_recepcion.py`, método `_predecir_confiabilidad()`

**Momento de predicción**: Inmediatamente después de la extracción OCR, antes de devolver los datos al frontend.

**Métricas de extracción OCR**:
- **Confianza por campo**: Calculada por Tesseract
- **Confianza promedio**: Promedio de todas las confianzas
- **Confiabilidad ML predicha**: Probabilidad de que la extracción sea confiable (0.0 a 1.0)
- **Discrepancias detectadas**: Campos donde OCR != formulario
- **Severidad de discrepancias**: CRÍTICA, ALTA, MEDIA, BAJA

**Uso del modelo en inferencia**:
1. Después de extracción OCR, se calcula `confianza_ocr_promedio`
2. Se llama a `_predecir_confiabilidad(confianza_ocr_promedio)`
3. El modelo retorna probabilidad de que la extracción sea confiable
4. Esta probabilidad se incluye en la respuesta JSON como `confiabilidad_ml`
5. El JavaScript muestra alertas basándose en esta probabilidad

**Umbrales de alerta en el frontend**:
- `confiabilidad_ml < 0.5`: Alerta de advertencia (warning) - "El modelo indica que esta extracción puede tener errores"
- `0.5 <= confiabilidad_ml < 0.7`: Alerta informativa (info) - "Confiabilidad moderada, se recomienda revisar"
- `confiabilidad_ml >= 0.7`: Sin alerta - Confiabilidad alta

### 3.4 Análisis de Matriz de Confusión

**Nota Importante**: El modelo actual es de **clasificación binaria** (RandomForestClassifier), no de regresión. La matriz de confusión es aplicable.

**Métricas de clasificación relevantes**:

| Métrica | Cálculo Actual | Estado |
|---------|----------------|--------|
| Precisión (Accuracy) | ✅ Calculado en línea 89 de retrain.py | Disponible |
| Matriz de Confusión | ❌ No calculada explícitamente | Pendiente |
| Precisión (Precision) | ❌ No calculada | Pendiente |
| Recall (Sensibilidad) | ❌ No calculada | Pendiente |
| F1-Score | ❌ No calculada | Pendiente |

**Matriz de Confusión esperada**:

| | Predicción: Confiable (1) | Predicción: No Confiable (0) |
|--|---------------------------|------------------------------|
| **Real: Confiable (1)** | Verdaderos Positivos (VP) | Falsos Negativos (FN) |
| **Real: No Confiable (0)** | Falsos Positivos (FP) | Verdaderos Negativos (VN) |

**Definición de clases**:
- **Clase positiva (1)**: Extracción con error_total < 0.3 (confiable)
- **Clase negativa (0)**: Extracción con error_total >= 0.3 (no confiable)

**Métricas derivadas**:
- **Precisión (Precision)**: VP / (VP + FP) - De las predichas como confiables, cuántas lo son realmente
- **Recall (Sensitivity)**: VP / (VP + FN) - De las realmente confiables, cuántas se detectaron
- **F1-Score**: 2 × (Precision × Recall) / (Precision + Recall)
- **Specificity**: VN / (VN + FP) - De las realmente no confiables, cuántas se detectaron

**Estado actual**: Solo se calcula la precisión general (accuracy) del modelo en el conjunto de entrenamiento. Se recomienda implementar cálculo completo de matriz de confusión y métricas derivadas.

---

## 4. Flujo de Extracción OCR

### 4.1 Recorrido de los Datos

Sigue este orden al leer el código:

1. **Controles de la página**: `Vista/VistaNew/recepcion.php` contiene el formulario de recepción, el selector de archivo de factura, y el botón de registro.
2. **Solicitud del navegador**: la función JavaScript `AsistenteRecepcionIA` en `microservicio/javascript/asistente_recepcion.js` envía la imagen al microservicio con el token de autenticación.
3. **Controlador API**: `microservicio/controlador/api_recepcion.py` valida el token, recibe el archivo en el endpoint `/fase1/extraer`.
4. **Procesamiento OCR**: `microservicio/modelo/auditor_recepcion.py` ejecuta Tesseract OCR para extraer texto de la imagen.
5. **Extracción de campos**: El mismo módulo usa expresiones regulares para extraer número de factura, proveedor, productos, montos, etc.
6. **Validación de factura (NUEVO)**: Verifica si el documento parece ser una factura válida.
7. **Predicción de confiabilidad**: El modelo ML predice si la extracción es confiable basándose en la confianza OCR.
8. **Verificación de coherencia**: Compara los datos extraídos con los datos del formulario del usuario.
9. **Registro de recepción**: `Modelo/Controlador/recepcion.php` registra la recepción en la base de datos MariaDB.
10. **Guardado de feedback**: `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php` calcula errores entre predicción y ground truth, y guarda en `microservicio/entrenamiento/feedback_dataset.csv`.
11. **Reentrenamiento**: `microservicio/retrain.py` lee el feedback_dataset.csv, entrena un modelo RandomForest con solo la confianza OCR como feature, y guarda en `microservicio/modelo/modelo_actual.pkl`.

### 4.2 Selección de Factura por el Usuario

1. El usuario accede al módulo de Recepciones.
2. Selecciona un archivo de factura (PDF, JPG, PNG).
3. El JavaScript `asistente_recepcion.js` envía la imagen al microservicio con el token de autenticación.

### 4.3 Procesamiento en el Microservicio

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
   - Número de factura (patrones como "Factura #12345", "Invoice #12345")
   - Nombre de proveedor (patrones como "Proveedor: Amazon", "Sold by: eBay")
   - Fecha (formatos DD/MM/YYYY, YYYY-MM-DD, "15 de enero 2026", "January 15, 2026")
   - Productos (métodos tabulares y estilo libre)
   - Subtotal, IVA, Total (con sinónimos múltiples)

4. **Cálculo de confianza**:
   - Tesseract devuelve confianza por campo
   - Se calcula promedio de todas las confianzas
   - Rango: 0.0 (baja) a 1.0 (alta)

5. **Validación de factura (NUEVO)**:
   - Verifica palabras clave (factura, invoice, total, iva, precio, cantidad, etc.)
   - Valida campos esenciales (número, proveedor, fecha, productos)
   - Valida montos monetarios (subtotal y total)
   - Si no cumple criterios, marca como no válida

6. **Predicción de confiabilidad ML**:
   - Si el modelo está cargado, se llama a `_predecir_confiabilidad(confianza_ocr)`
   - El modelo retorna probabilidad de que la extracción sea confiable (0.0 a 1.0)
   - Esta probabilidad se incluye en los metadatos de extracción

7. **Guardado en caché**:
   - Los datos extraídos se guardan en memoria con un ID único
   - Permite verificación posterior sin reextraer

### 4.4 Patrones Regex Mejorados

**Número de factura**:
```python
r'(?:factura|n[°º]|nro|numero|#)\s*[.:]?\s*([A-Z0-9\-]{5,20})'
r'(?:invoice|inv)\s*(?:#|n[°º])?\s*[.:]?\s*([A-Z0-9\-]+)'
r'(?:bill)\s*(?:#)?\s*[.:]?\s*([A-Z0-9\-]+)'
r'(?:receipt)\s*(?:#)?\s*[.:]?\s*([A-Z0-9\-]+)'
r'(\d{6,10})'
r'#\s*([A-Z0-9\-]+)'
```

**Proveedor**:
```python
r'(?:proveedor|vendedor|supplier|vendor|de|from)[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+(?:S\.A|S\.R\.L|C\.A|LLC|INC|LTDA|S\.A\.S|GmbH|Ltd|Pty)?)'
r'(?:razon social|nombre|company|empresa)[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+)'
r'(?:sold\s*by|billed\s*by)[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+)'
r'eBay|Amazon|MercadoLibre|AliExpress|Walmart|BestBuy|Newegg|Staples|Office Depot'
r'(?:HP|Canon|Epson|Brother|Xerox|Dell|Lenovo|Asus|Samsung|LG|Sony|Apple|Microsoft|Logitech|Intel|AMD|NVIDIA)\s+(?:Inc|Corp|LLC|GmbH|Ltd)?'
```

**Fecha**:
```python
r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})'
r'(\d{4}[/-]\d{1,2}[/-]\d{1,2})'
r'(\d{1,2}\s+(?:de|of|del)\s+[A-Za-z]+\s+\d{2,4})'
r'([A-Za-z]+\s+\d{1,2},?\s+\d{4})'
r'(\d{1,2}\s+[A-Za-z]{3}\s+\d{4})'
r'(\d{8})'  # Formato compacto DDMMYYYY
```

**Producto**:
```python
r'(?:descripcion|producto|articulo|item|product|description)[:\s]+([^\n]+)'
r'([A-Z][a-zA-Z0-9\s\-\+]+(?:Printer|Laptop|Desktop|Monitor|Phone|Tablet|Camera|Headphones|Speaker|Mouse|Keyboard|Router|Modem|Scanner|Copier|Ink|Toner|Cartridge|Memory|SSD|HDD|CPU|GPU))'
r'([A-Z][a-zA-Z0-9\s\-\+]{10,100})'
```

**Modelo**:
```python
r'(?:modelo|model|mod|type)[:\s]+([A-Z0-9\-\s]+)'
r'([A-Z0-9]{3,15}(?:\s*[A-Z0-9\-]+)*)'
r'(?:model\s*no|model\s*number)[:\s]*([A-Z0-9\-\s]+)'
```

**Marca**:
```python
r'(?:marca|brand|fabricante|manufacturer|maker)[:\s]+([A-Za-z0-9\s]+)'
r'(HP|Canon|Epson|Brother|Xerox|Dell|Lenovo|Asus|Samsung|LG|Sony|Apple|Microsoft|Logitech|Intel|AMD|NVIDIA|Kingston|SanDisk|Crucial|Seagate|Western Digital|Toshiba|Fujitsu|Kyocera|Lexmark|Ricoh|Konica|Oki)'
r'(?:by|from)\s+([A-Z][A-Za-z0-9]+)'
```

**Serial**:
```python
r'(?:serial|s/n|serie|sn)[:\s]+([A-Z0-9\-]+)'
r'(?:SN|S/N)[:\s]*([A-Z0-9\-]+)'
r'(?:serial\s*number)[:\s]*([A-Z0-9\-]+)'
r'([A-Z0-9]{8,20})'
```

**Cantidad**:
```python
r'(?:cantidad|qty|quantity|cant)[:\s]+(\d+)'
r'(?:und|unidades|units?|pcs|pieces)[:\s]+(\d+)'
r'(\d+)\s*(?:und|unidades|units?|pcs|pieces)'
r'(\d+)\s*x\s*'  # Formato "2x" para cantidad
```

**Costo**:
```python
r'(?:precio|costo|price|valor|unitario|p/u|unit\s*price)[:\s]+[\$]?\s*(\d+[.,]?\d*)'
r'[\$]\s*(\d+[.,]?\d{2})'
r'(\d+[.,]\d{2})'
r'(?:rate)[:\s]+[\$]?\s*(\d+[.,]?\d*)'
```

**Total**:
```python
r'(?:total|monto total|importe)[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
r'total\s*[\$]?\s*(\d+[.,]?\d{2})'
r'(?:grand\s*total|amount\s*due|balance\s*due)[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
```

### 4.5 Parsing de Montos Mejorado

**Función `_parsear_monto`**:

```python
def _parsear_monto(self, texto: str) -> float:
    if not texto: return 0.0
    # Eliminar caracteres no numéricos excepto punto, coma, signos
    numero = re.sub(r'[^\d,.+-]', '', str(texto)).strip()
    # Eliminar comas/puntos al final del string (común en OCR)
    numero = numero.rstrip('.,')
    if not numero:
        return 0.0

    if ',' in numero and '.' in numero:
        separador_decimal = ',' if numero.rfind(',') > numero.rfind('.') else '.'
        separador_miles = '.' if separador_decimal == ',' else ','
        numero = numero.replace(separador_miles, '')
        numero = numero.replace(separador_decimal, '.')
    elif ',' in numero:
        decimales = len(numero.rsplit(',', 1)[-1])
        numero = numero.replace(',', '.' if decimales in (1, 2) else '')
    elif numero.count('.') > 1:
        ultimo_punto = numero.rfind('.')
        numero = numero[:ultimo_punto].replace('.', '') + numero[ultimo_punto:]

    try:
        return float(numero)
    except ValueError:
        return 0.0
```

**Mejoras**:
- Elimina comas/puntos al final del string (común en OCR)
- Maneja formatos con ambos separadores (1,234.56 y 1.234,56)
- Detecta automáticamente el separador decimal basándose en la posición
- Maneja múltiples puntos como separadores de miles

### 4.6 Respuesta al Frontend

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
  "subtotal_factura": 5340.0,
  "porcentaje_iva": 16.0,
  "monto_iva": 854.4,
  "total_factura": 6194.4,
  "confianza_promedio": 0.94,
  "confiabilidad_ml": 0.87,
  "es_factura_valida": true,
  "mensaje_validacion": ""
}
```

**Campos nuevos en V3**:
- `subtotal_factura`, `porcentaje_iva`, `monto_iva`, `total_factura`: Montos extraídos
- `es_factura_valida`: Indica si el documento parece ser una factura válida
- `mensaje_validacion`: Mensaje de error si la validación falla

### 4.7 Relleno del Formulario y Alertas

El JavaScript recibe los datos y:
- Rellena automáticamente los campos del formulario
- Muestra un panel con los datos extraídos
- Calcula subtotales y totales
- **Verifica la confiabilidad ML y muestra alertas**:
  - Si `confiabilidad_ml < 0.5`: Alerta de advertencia "El modelo indica que esta extracción puede tener errores"
  - Si `0.5 <= confiabilidad_ml < 0.7`: Alerta informativa "Confiabilidad moderada, se recomienda revisar"
  - Si `confiabilidad_ml >= 0.7`: Sin alerta
- **Valida si el documento es una factura (NUEVO)**:
  - Si `es_factura_valida === false`: Muestra error y bloquea botón Guardar
- **Valida productos en catálogo (NUEVO)**:
  - Si todos los productos están fuera del catálogo: Muestra error y bloquea botón Guardar
- Permite al usuario corregir manualmente si es necesario

---

## 5. Verificación de Coherencia

### 5.1 Endpoint de Verificación

**Endpoint**: `/fase1/verificar`

**Método**: POST

**Autenticación**: Requiere token en header `X-Auditor-Token`

**Rate Limiting**: 30 solicitudes por minuto

**Payload**:
```json
{
  "factura_id": "fact_20261022_abc123",
  "numero_factura": "498231",
  "nombre_proveedor": "Amazon",
  "porcentaje_iva": 16.0,
  "productos": [
    {
      "nombre": "HP DeskJet 2775",
      "modelo": "DeskJet 2775",
      "marca": "HP",
      "serial": "0002",
      "cantidad": 2,
      "costo": 1520.0
    }
  ]
}
```

**Respuesta**:
```json
{
  "exito": true,
  "discrepancias": [],
  "reporte_detallado": "Verificación completada",
  "hash_verificacion": "abc123"
}
```

### 5.2 Criterios de Discrepancia

**Niveles de severidad**:

| Severidad | Criterio | Acción recomendada |
|-----------|----------|-------------------|
| CRÍTICA | Número de factura incorrecto | Bloquear registro |
| ALTA | Proveedor o total incorrecto | Alerta fuerte |
| MEDIA | Producto o subtotal incorrecto | Alerta moderada |
| BAJA | Campo secundario incorrecto | Alerta suave |

**Cálculo de discrepancias**:
- Comparación exacta para campos de texto (normalizando mayúsculas/minúsculas)
- Comparación numérica con tolerancia para montos (tolerancia 5%)
- Comparación de listas para productos (nombre, cantidad, costo)

---

## 6. Registro de Feedback

### 6.1 Cálculo de Errores

**Archivo**: `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php`

**Método**: `agregarFeedbackDataset()`

**Proceso**:
1. Compara datos extraídos por IA con datos confirmados por usuario
2. Calcula error por campo (0 si coincide, 1 si no coincide)
3. Calcula error_total como promedio de errores por campo
4. Guarda fila en `microservicio/entrenamiento/feedback_dataset.csv`

**Formato del CSV**:
```csv
timestamp,id_recepcion,id_factura,error_proveedor,error_correlativo,error_productos,error_subtotal,error_total,confianza_ocr
2026-10-23T10:30:00,REC001,498231,0,0,1,0,0,0.94
```

### 6.2 Validar que Feedback se Acumula

Verificar que el archivo `feedback_dataset.csv` está creciendo:

```powershell
wc -l microservicio/entrenamiento/feedback_dataset.csv
```

Debería incrementarse con cada recepción registrada con `usar_ia = true`.

---

## 7. Reentrenamiento del Modelo

### 7.1 Requisitos para Reentrenar

**Archivo**: `microservicio/retrain.py`

**Requisitos**:
- Al menos 10 registros en `feedback_dataset.csv`
- Python con dependencias instaladas
- El microservicio no necesita estar ejecutándose

### 7.2 Ejecutar Reentrenamiento

Desde la carpeta `microservicio`:

```powershell
python retrain.py
```

### 7.3 Proceso de Reentrenamiento

1. **Lectura del dataset**:
   - Lee `entrenamiento/feedback_dataset.csv`
   - Verifica que haya al menos 10 registros

2. **Cálculo de métricas**:
   - Calcula error promedio por campo
   - Calcula confianza OCR promedio

3. **Construcción de features**:
   - Crea matriz X con 1 feature:
     - confianza_ocr_promedio
   - **Cambio desde V1**: Antes se usaban 6 features (errores por campo + confianza)
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

### 7.4 Resultado Esperado

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

### 7.5 Uso del Modelo Entrenado

**Estado actual**: El modelo entrenado **SÍ se usa en el flujo de extracción**.

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

---

## 8. Validaciones de Factura

### 8.1 Validación de Formato de Factura

**Método**: `_validar_es_factura()` en `auditor_recepcion.py`

**Criterios de validación**:

1. **Palabras clave**:
   - Debe contener al menos 2 palabras clave de factura
   - Palabras: factura, invoice, bill, receipt, total, subtotal, iva, tax, impuesto, precio, costo, price, cantidad, quantity, proveedor, vendor, supplier

2. **Campos esenciales**:
   - Debe tener al menos 2 de los siguientes campos:
     - Número de factura
     - Nombre de proveedor
     - Fecha de factura
     - Productos

3. **Montos monetarios**:
   - Debe tener al menos uno de:
     - Subtotal > 0
     - Total > 0

**Mensajes de error**:
- "El documento no parece ser una factura. Por favor seleccione una factura válida."
- "El documento no tiene suficiente información para ser considerado una factura válida."
- "El documento no contiene montos monetarios. Verifique que sea una factura."

### 8.2 Validación de Productos en Catálogo

**Ubicación**: Frontend JavaScript en `recepcion.js`

**Proceso**:
1. Después de extraer productos, verifica si están en el catálogo del sistema
2. Usa función `buscarOpcionCatalogo()` para buscar coincidencias
3. Cuenta productos no encontrados en catálogo

**Criterios**:
- Si **todos** los productos están fuera del catálogo → Factura inválida
- Si **algunos** productos están fuera del catálogo → Advertencia para revisión manual
- Si **todos** los productos están en catálogo → Factura válida

**Mensaje de error**:
"La factura seleccionada no se relaciona con los productos del catálogo. Factura inválida para este sistema."

### 8.3 Bloqueo de Botón Guardar

**Implementación**:
- Variable global `facturaValidaParaRegistro` controla el estado
- Función `bloquearBotonGuardar(bloquear)` habilita/deshabilita el botón
- Validación en `saveRecepcion()` verifica el estado antes de permitir registro

**Comportamiento**:
- Factura no válida → Botón bloqueado con tooltip "No se puede registrar con una factura inválida"
- Intento de registro con factura inválida → Mensaje de error y retorno
- Factura válida → Botón desbloqueado
- Limpieza de formulario → Estado reiniciado y botón desbloqueado

---

## 9. Seguridad y Rate Limiting

### 9.1 Autenticación con Token

**Implementación**: Token HMAC en header `X-Auditor-Token`

**Validación**:
```python
def validar_token_auditor(x_auditor_token: str = Header(default="", max_length=256)) -> None:
    expected = os.getenv("AUDITOR_RECEPCION_TOKEN", "")
    if len(expected) < 32:
        raise HTTPException(status_code=503, detail="AUDITOR_RECEPCION_TOKEN debe tener al menos 32 caracteres.")
    if not hmac.compare_digest(x_auditor_token.encode("utf-8"), expected.encode("utf-8")):
        raise HTTPException(status_code=403, detail="Token de auditoría inválido.")
```

**Errores**:
- **403 Forbidden**: Token inválido
- **503 Service Unavailable**: Token no configurado o muy corto

### 9.2 Rate Limiting con slowapi

**Librería**: slowapi==0.1.9

**Configuración**:
```python
from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.util import get_remote_address
from slowapi.errors import RateLimitExceeded

limiter = Limiter(key_func=get_remote_address)
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)
```

**Límites por endpoint**:

| Endpoint | Límite | Justificación |
|----------|--------|--------------|
| `/api/v1/auditar` | 10/min | Operación costosa de OCR |
| `/fase1/extraer` | 10/min | Operación costosa de OCR |
| `/fase1/verificar` | 30/min | Operación más ligera |
| `/fase1/comparar-directo` | 10/min | Operación costosa de OCR |

**Comportamiento al exceder límite**:
- HTTP 429 (Too Many Requests)
- Mensaje: "Rate limit exceeded"
- Header `Retry-After` indica segundos hasta próximo intento

### 9.3 Recomendaciones de Seguridad

- Mantén FastAPI en loopback (127.0.0.1) cuando PHP comparte el mismo host
- Configura `AUDITOR_RECEPCION_TOKEN` en el entorno de PHP/Apache y FastAPI
- El token debe tener al menos 32 caracteres
- No guardes el secreto en un archivo versionado
- Considera rotar el token periódicamente
- Conserva los archivos del modelo y de feedback fuera de Git
- Respálda `feedback_dataset.csv` periódicamente
- El endpoint de entrenamiento escribe un único artefacto, así que ejecuta FastAPI con un solo worker si se activa entrenamiento concurrente
- Las imágenes de facturas se eliminan del directorio temporal después del procesamiento
- Los datos de feedback no contienen la imagen de la factura, solo los datos extraídos y confirmados
- El sistema puede funcionar sin el modelo ML (degradación graceful)

---

## 10. Métricas y Monitoreo

### 10.1 Métricas de Calidad del Sistema

**Estados del sistema**:

| Estado | Significado | Condición |
|--------|-------------|-----------|
| `online` | Servicio disponible | FastAPI ejecutándose |
| `exito` | Extracción exitosa | OCR completó sin errores |
| `discrepancias` | Diferencias detectadas | Al menos un campo con discrepancia |
| `error` | Error crítico | Excepción durante procesamiento |
| `modelo_ml_cargado` | Modelo ML disponible | modelo_actual.pkl existe y se cargó |
| `autenticacion_requerida` | Token necesario | AUDITOR_RECEPCION_TOKEN configurado |

**Niveles de confianza OCR**:

| Nivel | Rango | Criterio |
|-------|-------|----------|
| Alta | confianza ≥ 0.85 | Extracción muy confiable |
| Media | 0.60 ≤ confianza < 0.85 | Extracción moderadamente confiable |
| Baja | confianza < 0.60 | Extracción poco confiable |

**Niveles de confiabilidad ML**:

| Nivel | Rango | Acción en UI |
|-------|-------|-------------|
| Alta | confiabilidad_ml ≥ 0.70 | Sin alerta |
| Media | 0.50 ≤ confiabilidad_ml < 0.70 | Alerta informativa |
| Baja | confiabilidad_ml < 0.50 | Alerta de advertencia |

### 10.2 Métricas a Monitorear

**Sistema**:
- CPU de FastAPI (OCR es CPU-bound)
- Memoria de FastAPI (carga de imágenes grandes)
- Latencia de endpoints (p50, p95, p99)
- Tasa de errores HTTP (4xx, 5xx)
- Tasa de errores de autenticación (403)

**Negocio**:
- Extracciones por día
- Recepciones registradas con microservicio
- Promedio de confianza OCR
- Promedio de confiabilidad ML
- Tasa de discrepancias por campo
- Registros en feedback_dataset.csv

**Datos**:
- Tamaño de feedback_dataset.csv
- Tamaño de modelo_actual.pkl
- Espacio en directorio temp/
- Espacio en factura_cache/

### 10.3 Umbrales de Alerta

| Métrica | Umbral Advertencia | Umbral Crítico | Acción |
|---------|-------------------|----------------|--------|
| Latencia extracción (p95) | > 3s | > 10s | Revisar carga Tesseract |
| Latencia verificación (p95) | > 200ms | > 500ms | Revisar lógica de comparación |
| Tasa de errores HTTP | > 5% | > 15% | Revisar logs, verificar dependencias |
| Tasa de errores 403 | > 10% | > 25% | Verificar configuración de token |
| CPU FastAPI | > 70% | > 90% | Escalar verticalmente |
| Memoria FastAPI | > 1GB | > 2GB | Revisar leaks, escalar |
| Tamaño feedback_dataset.csv | > 10MB | > 50MB | Considerar rotación |
| Confianza OCR promedio | < 0.60 | < 0.40 | Revisar calidad de imágenes |
| Confiabilidad ML promedio | < 0.50 | < 0.30 | Revisar calidad de entrenamiento |
| Tasa de discrepancias | > 50% | > 80% | Revisar patrones regex |

### 10.4 Logs Implementados

**Nivel INFO**:
- Inicio de extracción OCR
- Extracción completada con confianza
- Modelo ML cargado exitosamente
- Confiabilidad ML predicha
- Verificación de coherencia
- Reentrenamiento completado

**Nivel WARNING**:
- pdf2image no disponible
- Precio unitario inconsistente (recalculado)
- Insuficientes datos para reentrenamiento
- No se pudo cargar el modelo ML
- Error prediciendo confiabilidad

**Nivel ERROR**:
- Imagen no encontrada
- Factura no encontrada en caché
- Error crítico procesando archivo

**Recomendación**:
- Agregar logs estructurados (JSON)
- Incluir correlation_id para tracing
- Centralizar logs (ELK stack o similar)
- Registrar intentos de autenticación fallidos

---

## 11. Pruebas y Mantenimiento

### 11.1 Estrategia de Pruebas

#### 11.1.1 Pruebas Unitarias

**Archivo recomendado**: `microservicio/tests/test_auditor_recepcion.py`

**Casos de prueba recomendados**:

1. **test_extraer_factura_desde_imagen**
   - Objetivo: Verificar extracción OCR de factura simple
   - Valida: Campos extraídos no vacíos, confianza > 0
   - Cobertura: Imagen JPEG, PNG, PDF

2. **test_extraer_factura_pdf_multipagina**
   - Objetivo: Verificar extracción de PDF con múltiples páginas
   - Valida: Todas las páginas procesadas, texto concatenado
   - Cobertura: PDF de 2-5 páginas

3. **test_verificar_coherencia_sin_discrepancias**
   - Objetivo: Verificar que coincidencia exacta no genera discrepancias
   - Valida: exito = True, discrepancias = []
   - Cobertura: Todos los campos coinciden

4. **test_verificar_coherencia_con_discrepancias**
   - Objetivo: Verificar detección de discrepancias
   - Valida: exito = False, discrepancias no vacías
   - Cobertura: Diferencias en proveedor, productos, montos

5. **test_extraer_productos_tabulares**
   - Objetivo: Verificar extracción de productos en formato tabla
   - Valida: Productos extraídos con nombre, cantidad, costo
   - Cobertura: Facturas con formato tabular

6. **test_extraer_productos_estilo**
   - Objetivo: Verificar extracción de productos en formato estilo
   - Valida: Productos extraídos con nombre, cantidad, costo
   - Cobertura: Facturas con formato estilo libre

7. **test_cache_factura**
   - Objetivo: Verificar guardado y recuperación de factura en caché
   - Valida: Factura guardada con ID, recuperada correctamente
   - Cobertura: Operaciones de caché

8. **test_reentrenamiento_con_suficientes_datos**
   - Objetivo: Verificar reentrenamiento con 10+ registros
   - Valida: Modelo entrenado, métricas guardadas
   - Cobertura: Script retrain.py

9. **test_reentrenamiento_con_insuficientes_datos**
   - Objetivo: Verificar que reentrenamiento falla con <10 registros
   - Valida: No se entrena, mensaje de error
   - Cobertura: Script retrain.py

10. **test_carga_modelo_ml**
    - Objetivo: Verificar que el modelo ML se carga correctamente al iniciar
    - Valida: self.modelo_ml no es None si modelo_actual.pkl existe
    - Cobertura: Inicialización de AuditorRecepcion

11. **test_prediccion_confiabilidad**
    - Objetivo: Verificar que el modelo predice confiabilidad correctamente
    - Valida: _predecir_confiabilidad() retorna valor entre 0 y 1
    - Cobertura: Método _predecir_confiabilidad()

12. **test_prediccion_sin_modelo**
    - Objetivo: Verificar comportamiento cuando no hay modelo cargado
    - Valida: _predecir_confiabilidad() retorna 0.0 si modelo_ml es None
    - Cobertura: Manejo de ausencia de modelo

13. **test_validacion_token**
    - Objetivo: Verificar que la autenticación funciona correctamente
    - Valida: Token válido permite acceso, token inválido rechaza
    - Cobertura: Función validar_token_auditor()

14. **test_validacion_factura** (NUEVO)
    - Objetivo: Verificar validación de formato de factura
    - Valida: Documento no factura retorna es_factura_valida=False
    - Cobertura: Método _validar_es_factura()

15. **test_rate_limiting** (NUEVO)
    - Objetivo: Verificar que rate limiting funciona
    - Valida: Más de 10 solicitudes/min retorna 429
    - Cobertura: Decorador @limiter.limit()

**Ejecución recomendada**:
```bash
python -m unittest microservicio.tests.test_auditor_recepcion -v
```

#### 11.1.2 Pruebas de Integración

**Prueba 1: Flujo completo JavaScript → FastAPI con autenticación**
```javascript
// Escenario: Usuario selecciona factura en formulario de recepción
// 1. JavaScript envía imagen a /api/v1/auditar con token de autenticación
// 2. FastAPI valida token
// 3. FastAPI procesa con auditor_recepcion.py
// 4. FastAPI devuelve datos extraídos + confiabilidad_ml + es_factura_valida
// 5. JavaScript rellena formulario y muestra alertas de confiabilidad
// Validar:
// - Token enviado en header X-Auditor-Token
// - Token válido permite acceso
// - Token inválido retorna 403
// - Respuesta JSON válida con datos de factura
// - Campos extraídos no vacíos
// - Confianza calculada
// - Confiabilidad ML incluida en respuesta
// - es_factura_valida incluido en respuesta
// - Alertas mostradas según confiabilidad ML
// - Botón Guardar bloqueado si factura inválida
```

**Prueba 2: Validación de productos en catálogo**
- Cargar factura con productos no relacionados
- Validar: Mensaje de error, botón Guardar bloqueado

**Prueba 3: Rate limiting**
- Enviar 11 solicitudes en 1 minuto
- Validar: 11ª solicitud retorna 429

#### 11.1.3 Pruebas de Seguridad

**Prueba 1: Validación de archivos**
- Enviar archivo no válido (ej: .exe)
- Validar: Rechazado

**Prueba 2: Validación de esquema**
- Enviar JSON malformado
- Validar: Rechazado

**Prueba 3: Inyección de datos**
- Enviar datos con SQL injection
- Validar: Rechazado

**Prueba 4: Validación de autenticación**
- Enviar solicitud sin token
- Validar: Rechazada con 403
- Enviar solicitud con token incorrecto
- Validar: Rechazada con 403
- Enviar solicitud con token correcto
- Validar: Aceptada

### 11.2 Mantenimiento Rutinario

**Frecuencia**: Semanal

**Tareas**:

1. **Verificar salud del servicio**
   ```bash
   curl http://127.0.0.1:8000/
   ```
   - Esperar: HTTP 200
   - Rechazar: HTTP 500, 503

2. **Verificar configuración de autenticación**
   ```bash
   echo $AUDITOR_RECEPCION_TOKEN
   ```
   - Debe tener al menos 32 caracteres

3. **Revisar tamaño de artefactos**
   ```bash
   ls -lh microservicio/entrenamiento/feedback_dataset.csv
   ls -lh microservicio/modelo/modelo_actual.pkl
   ```
   - Alerta si > 10MB (feedback_dataset.csv)

4. **Verificar cantidad de registros de feedback**
   ```bash
   wc -l microservicio/entrenamiento/feedback_dataset.csv
   ```
   - Recomendado: Mantener al menos 10 registros

5. **Limpiar directorio temporal**
   ```bash
   rm -rf microservicio/temp/*
   ```

6. **Limpiar caché de facturas**
   - Si factura_cache/ crece demasiado
   - Eliminar entradas antiguas (> 7 días)

### 11.3 Mantenimiento Mensual

**Tareas**:

1. **Reentrenar modelo**
   ```bash
   cd microservicio
   python retrain.py
   ```
   - Validar: Modelo entrenado exitosamente
   - Validar: Métricas guardadas
   - Validar: Precisión del modelo > 0.8

2. **Análisis de calidad de extracciones**
   - Revisar error_promedio por campo
   - Identificar campos con error sistemático alto
   - Revisar confiabilidad ML promedio
   - Ajustar patrones regex si es necesario

3. **Revisión de patrones regex**
   - Verificar PATRONES en auditor_recepcion.py
   - Agregar nuevos patrones si se detectan formatos nuevos
   - Probar con muestras reales

4. **Actualización de Tesseract**
   - Verificar versión instalada
   - Actualizar si hay versión más reciente
   - Probar con imágenes de prueba

5. **Rotación de token de autenticación**
   - Considerar rotar AUDITOR_RECEPCION_TOKEN periódicamente
   - Actualizar en entorno de PHP/Apache
   - Actualizar en entorno de FastAPI
   - Reiniciar Apache después del cambio

### 11.4 Mantenimiento Trimestral

**Tareas**:

1. **Actualización de dependencias**
   ```bash
   cd microservicio
   pip list --outdated
   pip install --upgrade -r requirements.txt
   ```
   - Probar en entorno de desarrollo primero
   - Ejecutar pruebas unitarias después de upgrade

2. **Optimización de patrones regex**
   - Revisar eficiencia de PATRONES
   - Eliminar patrones redundantes
   - Combinar patrones similares

3. **Rotación de feedback_dataset.csv**
   - Si archivo > 10MB:
     - Renombrar a feedback_dataset_YYYY-MM-DD.csv
     - Comprimir con gzip
     - Archivar en almacenamiento externo
     - Crear nuevo archivo con encabezado

4. **Limpieza de logs**
   - Rotar logs antiguos (> 30 días)
   - Comprimir logs archivados
   - Mantener espacio en disco

5. **Auditoría de seguridad**
   - Revisar logs de autenticación
   - Verificar intentos fallidos
   - Revisar configuración de token
   - Validar que el servicio no esté expuesto externamente

---

## 12. Solución de Problemas

### 12.1 Problemas Frecuentes

| Mensaje o síntoma | Comprobación |
|---|---|
| Servicio no disponible | FastAPI está iniciado y escucha en `127.0.0.1:8000`. |
| Error 403 - Token inválido | AUDITOR_RECEPCION_TOKEN está configurado y coincide en PHP y FastAPI. |
| Error 503 - Token muy corto | AUDITOR_RECEPCION_TOKEN tiene al menos 32 caracteres. |
| Error 429 - Rate limit exceeded | Esperar antes de hacer más solicitudes. |
| OCR no disponible | Tesseract está instalado en `C:\Program Files\Tesseract-OCR\tesseract.exe`. |
| PDF no se procesa | Poppler está instalado y en el PATH. |
| No se extraen datos | Imagen es legible, no está borrosa ni rotada. |
| Confianza muy baja | Imagen de buena calidad, resolución adecuada. |
| Discrepancias CRÍTICAS | Número de factura en la imagen coincide con el formulario. |
| feedback_dataset.csv vacío | Se han registrado recepciones con `usar_ia = true`. |
| Reentrenamiento falla | Hay al menos 10 registros en feedback_dataset.csv. |
| No se muestra confiabilidad_ml | modelo_actual.pkl existe y se cargó correctamente. |
| Modelo ML no se carga | Verificar que modelo_actual.pkl existe en microservicio/modelo/. |
| Documento no reconocido como factura | Verificar que el documento tenga palabras clave de factura. |
| Productos no encontrados en catálogo | Verificar que los productos estén en el catálogo del sistema. |
| Botón Guardar bloqueado | Verificar que la factura sea válida y los productos estén en catálogo. |

### 12.2 Respuestas ante Fallos

#### 12.2.1 Fallos de Conectividad

**Detección**: Timeout al conectar con FastAPI

**Respuesta en API**:
- Activar modo contingencia
- Mostrar mensaje: "El servicio de lectura automática por IA no está disponible temporalmente. Se ha habilitado el formulario manual."

**Tratamiento en UI**:
- Deshabilitar botón de verificación IA
- Permitir ingreso manual de datos
- Guardar factura en modo contingencia

#### 12.2.2 Fallos de OCR

**Detección**: Tesseract retorna error o texto vacío

**Respuesta en API**:
- Registrar error en logs
- Retornar mensaje de error

**Tratamiento en UI**:
- Mostrar: "Error al procesar la imagen. Por favor intente con otra imagen."
- Activar modo contingencia

#### 12.2.3 Fallos del Modelo ML

**Escenario 1**: modelo_actual.pkl no existe

**Detección**: Archivo no encontrado en auditor_recepcion.py

**Respuesta**:
```python
model_path = Path(__file__).parent / "modelo_actual.pkl"
if model_path.exists():
    try:
        self.modelo_ml = joblib.load(model_path)
        logger.info("Modelo ML cargado exitosamente desde modelo_actual.pkl")
    except Exception as e:
        logger.warning(f"No se pudo cargar el modelo ML: {e}")
else:
    logger.info("No se encontró modelo_actual.pkl. El modelo ML no estará disponible.")
```

**Tratamiento en UI**:
- El sistema sigue funcionando sin el modelo ML
- No se muestra confiabilidad_ml en la respuesta
- No se muestran alertas de confiabilidad ML
- El usuario puede continuar usando OCR + regex

**Escenario 2**: Error durante predicción

**Detección**: Excepción en _predecir_confiabilidad()

**Respuesta**:
```python
try:
    features = [[confianza_ocr]]
    prediccion =self.modelo_ml.predict_proba(features)[0]
    return prediccion[1] if len(prediccion) > 1 else prediccion[0]
except Exception as e:
    logger.warning(f"Error prediciendo confiabilidad: {e}")
    return 0.0
```

**Tratamiento en UI**:
- Si confiabilidad_ml = 0.0, no se muestra alerta
- El sistema continúa funcionando
- Se registra el error en logs

#### 12.2.4 Fallos de Autenticación

**Escenario**: Token de autenticación no configurado o incorrecto

**Detección**: HTTP 403 o 503

**Respuesta en API**:
```python
if len(expected) < 32:
    raise HTTPException(status_code=503, detail="AUDITOR_RECEPCION_TOKEN debe tener al menos 32 caracteres.")
if not hmac.compare_digest(x_auditor_token.encode("utf-8"), expected.encode("utf-8")):
    raise HTTPException(status_code=403, detail="Token de auditoría inválido.")
```

**Tratamiento en UI**:
- Mostrar: "Error de autenticación con el microservicio. Contacte al administrador."
- Activar modo contingencia
- Deshabilitar botón de verificación IA

**Recomendación de mejora**:
- Implementar reintentos con token actualizado
- Registrar intentos fallidos en logs
- Alertar al administrador después de múltiples fallos

#### 12.2.5 Compromiso de Seguridad (Token filtrado)

**Síntomas**:
- Solicitudes no autorizadas al endpoint
- Comportamiento anómalo en logs
- Tasa de errores 403 alta

**Recuperación**:

1. **Rotar token inmediatamente**
   ```powershell
   python -c "import secrets; print(secrets.token_urlsafe(32))"
   ```

2. **Actualizar en entorno PHP/Apache**
   ```powershell
   $env:AUDITOR_RECEPCION_TOKEN = "nuevo-token-generado"
   # O en configuración de Apache
   SetEnv AUDITOR_RECEPCION_TOKEN "nuevo-token-generado"
   ```

3. **Actualizar en entorno FastAPI**
   ```powershell
   $env:AUDITOR_RECEPCION_TOKEN = "nuevo-token-generado"
   # Reiniciar FastAPI
   ```

4. **Investigar incidente**
   - Revisar logs de acceso FastAPI
   - Identificar IPs sospechosas
   - Verificar no hay otros compromisos

5. **Preventivo**:
   - Configurar rotación periódica de tokens
   - Implementar alertas por múltiples fallos de autenticación
   - Considerar rate limiting por IP

---

## 13. Archivos del Microservicio y su Rol

### 13.1 Archivos ACTIVOS en el flujo

| Archivo | Rol | Estado |
|---------|-----|--------|
| `recepcion.js` | Frontend JavaScript | ✅ Activo - Maneja formulario y envía al microservicio |
| `asistente_recepcion.js` | Cliente JS del microservicio | ✅ Activo - Interfaz con API FastAPI, envía token |
| `api_recepcion.py` | Controlador API FastAPI | ✅ Activo - Endpoints con autenticación y rate limiting |
| `auditor_recepcion.py` | Modelo OCR y extracción | ✅ Activo - Tesseract + regex + predicción ML + validaciones |
| `recepcion.php` (controlador) | Backend PHP | ✅ Activo - Registra recepción y captura feedback |
| `Recepcion.php` (modelo) | Modelo PHP | ✅ Activo - `agregarFeedbackDataset()` |
| `feedback_dataset.csv` | Dataset de feedback | ✅ Activo - Acumula predicciones vs ground truth |
| `retrain.py` | Script de reentrenamiento | ✅ Activo - Entrena RandomForest con 1 feature |
| `modelo_actual.pkl` | Modelo entrenado | ✅ Activo - Usado en extracción |
| `main.py` | Punto de entrada FastAPI | ✅ Activo - Configura rate limiting global |
| `config.py` | Configuración Pydantic | ✅ Activo - Carga tokens desde variables de entorno |
| `.env` | Variables de entorno | ✅ Activo - Almacena tokens para FastAPI |
| `.htaccess` | Configuración Apache | ✅ Activo - Define token para PHP |

### 13.2 Archivos NO UTILIZADOS actualmente

| Archivo | Rol | Estado |
|---------|-----|--------|
| `feedback_store.py` | Almacenamiento de feedback en JSONL | ❌ No usado - PHP escribe directamente al CSV |
| `model_registry.py` | Gestión de versiones de modelos | ❌ No usado - Modelo se guarda directamente como .pkl |
| `entrenar_modelo.py` | Orquestador de reentrenamiento | ❌ No usado - `retrain.py` hace el reentrenamiento |
| `logger.py` | Función de logging de predicciones | ❌ No usado - PHP escribe directamente al CSV |

---

## 14. Comparación de Versiones

### 14.1 Cambios Principales

| Aspecto | Versión 1 | Versión 2 | Versión 3 |
|---------|-----------|-----------|-----------|
| Features de entrenamiento | 6 (errores + confianza) | 1 (solo confianza) | 1 (solo confianza) |
| Modelo en inferencia | No usado | Sí usado | Sí usado |
| Autenticación API | No | Token HMAC | Token HMAC |
| Alertas de confiabilidad | No | Sí (basadas en ML) | Sí (basadas en ML) |
| Validación de factura | No | No | Sí |
| Validación de catálogo | No | No | Sí |
| Rate limiting | No | No | Sí (slowapi) |
| Bloqueo de botón Guardar | No | No | Sí |
| Degradación sin modelo | No definido | Graceful | Graceful |
| Patrones regex | Básicos | Mejorados | Expandidos (internacional) |
| Parsing de montos | Básico | Mejorado | Robusto (múltiples formatos) |

### 14.2 Beneficios de la Actualización

1. **Predicción en tiempo real**: El modelo ahora predice la confiabilidad durante la extracción
2. **Mejor experiencia de usuario**: Alertas contextuales ayudan al usuario a identificar extracciones problemáticas
3. **Mayor seguridad**: Autenticación protege los endpoints del microservicio
4. **Protección contra abuso**: Rate limiting previene uso excesivo de la API
5. **Validación inteligente**: Detecta documentos que no son facturas válidas
6. **Integración con catálogo**: Verifica que los productos estén en el sistema
7. **Prevención de errores**: Bloquea registro con facturas inválidas
8. **Simplificación**: Menos features = entrenamiento más rápido y más fácil de mantener
9. **Resiliencia**: El sistema puede funcionar sin el modelo ML si es necesario
10. **Soporte internacional**: Patrones regex expandidos para diferentes formatos de factura

---

## 15. Próximos Pasos Recomendados

### 15.1 Mejoras Técnicas

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
   - Probar validación de factura
   - Probar rate limiting

4. **Implementar monitoreo avanzado**
   - Configurar alertas para latencia alta
   - Monitorear tasa de discrepancias por campo
   - Revisar confianza OCR promedio periódicamente
   - Monitorear confiabilidad ML promedio
   - Monitorear tasa de errores 403

### 15.2 Mejoras de Negocio

1. **Integrar modelo en priorización de revisiones**
   - Usar confiabilidad ML para priorizar revisiones manuales
   - Marcar extracciones de baja confianza para revisión humana

2. **Implementar aprendizaje activo**
   - Identificar casos de borde donde el modelo es incierto
   - Solicitar feedback humano en esos casos
   - Usar feedback para mejorar el modelo

3. **Analizar patrones de error**
   - Identificar campos con error sistemático alto
   - Ajustar umbrales de confianza

4. **Implementar notificaciones automáticas**
   - Alertar al administrador cuando tasa de discrepancias sea alta
   - Notificar cuando modelo necesite reentrenamiento
   - Alertar sobre intentos fallidos de autenticación

### 15.3 Mejoras de Seguridad

1. **Implementar rotación automática de tokens**
   - Script para rotar tokens periódicamente
   - Integración con sistema de gestión de secretos

2. **Implementar IP whitelisting**
   - Restringir acceso a IPs específicas
   - Configurar en Apache y FastAPI

3. **Implementar auditoría completa**
   - Registrar todas las solicitudes a la API
   - Incluir timestamp, IP, usuario, acción
   - Almacenar en base de datos para análisis

---

## 16. Referencias

### 16.1 Documentación Relacionada

- `DOCUMENTO_TECNICO_CAPITULO_IV_V2.md` - Documento técnico anterior (Versión 2)
- `GUIA_ENTRENAMIENTO_V2.md` - Guía de entrenamiento anterior (Versión 2)
- `requirements.txt` - Dependencias Python del microservicio
- `main.py` - Punto de entrada de FastAPI
- `config.py` - Configuración de Pydantic Settings

### 16.2 Enlaces Útiles

- [FastAPI Documentation](https://fastapi.tiangolo.com/)
- [Tesseract OCR Documentation](https://tesseract-ocr.github.io/)
- [scikit-learn Documentation](https://scikit-learn.org/)
- [slowapi Documentation](https://slowapi.readthedocs.io/)

---

**Versión del documento**: 3.0  
**Fecha de actualización**: Octubre 2026  
**Estado**: Activo - Refleja el estado actual del microservicio
