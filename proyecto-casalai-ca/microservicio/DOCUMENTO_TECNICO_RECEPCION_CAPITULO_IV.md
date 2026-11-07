# Documento Técnico: Microservicio de Recepción y Auditoría IA
## Capítulo IV - Análisis de Arquitectura y Métricas

---

## 1. Reporte Técnico de Métricas de Entrenamiento y Validación

### 1.1 Arquitectura del Sistema

**Fase Actual**: Fase 1 - Verificación Asistida ("El Guardián")

**Componentes Principales**:
1. **Motor OCR**: Tesseract OCR con preprocesamiento de imagen (OpenCV)
2. **Extractor de Campos**: Regex contextuales para extracción de datos de facturas
3. **Verificador de Coherencia**: Comparación de datos extraídos vs formulario
4. **Circuit Breaker**: Protección contra fallos en cascada (JavaScript y PHP)
5. **Registry de Modelos**: Gestión de versiones de modelos ML (preparado para fases futuras)

**Tecnologías**:
- **Backend**: FastAPI (Python 3.10+)
- **OCR**: Tesseract OCR + pytesseract
- **Procesamiento de Imagen**: OpenCV (cv2), PIL (Pillow)
- **PDF**: pdf2image + Poppler
- **Frontend**: JavaScript + Bootstrap
- **Proxy PHP**: cURL con circuit breaker

### 1.2 Flujo de Datos

```
┌─────────────┐
│   Usuario   │
│ (Navegador) │
└──────┬──────┘
       │ Sube factura
       ↓
┌─────────────────┐
│  JavaScript     │
│  (asistente_    │
│   recepcion.js) │
│  - Circuit     │
│    Breaker     │
└──────┬──────────┘
       │ POST /fase1/extraer
       ↓
┌─────────────────┐
│  PHP Proxy      │
│ (RecepcionIA    │
│  Proxy.php)     │
│  - Circuit      │
│    Breaker      │
└──────┬──────────┘
       │ cURL (local)
       ↓
┌─────────────────┐
│  FastAPI        │
│ (api_recepcion) │
│ 127.0.0.1:8000  │
└──────┬──────────┘
       │
   ┌───┴───┐
   ↓       ↓
┌──────┐ ┌──────────┐
│ OCR  │ │ Auditor  │
│Tess. │ │ Regex    │
└──────┘ └──────────┘
```

### 1.3 Métricas de OCR (Extracción)

#### 1.3.1 Métricas Calculadas

**Confianza Promedio**:
- **Fórmula**: `confianza_promedio = (confianza_productos * 0.6) + (campos_principales * 0.4)`
- **Cálculo de confianza de producto**: `campos_llenos / 6.0` (6 campos: nombre, modelo, marca, serial, cantidad, costo)
- **Cálculo de campos principales**: `campos_principales / 3.0` (3 campos: número_factura, proveedor, fecha)
- **Implementación**: Líneas 497-502 de `auditor_recepcion.py`
- **Rango**: 0.0 a 1.0 (0% a 100%)

**Niveles de Confianza**:
- **Alta**: ≥ 0.85 (85%)
- **Media**: 0.60 - 0.84 (60% - 84%)
- **Baja**: < 0.60 (< 60%)

**Campos Extraídos**:
- Número de factura (patrones regex)
- Nombre del proveedor (patrones regex + similitud)
- Fecha de factura (formatos DD/MM/AAAA, AAAA/MM/DD)
- Productos (múltiples métodos de extracción)
  - Nombre
  - Modelo
  - Marca
  - Serial
  - Cantidad
  - Costo unitario
- Totales (subtotal, IVA, total)

#### 1.3.2 Patrones de Extracción (Regex)

**Patrones Implementados** (líneas 80-91 de `auditor_recepcion.py`):

```python
PATRONES = {
    'numero_factura': [
        r'(?:factura|n[°º]|nro|numero|#)\s*[.:]?\s*([A-Z0-9\-]{5,20})',
        r'(?:invoice|inv)\s*(?:#|n[°º])?\s*[.:]?\s*([A-Z0-9\-]+)',
        r'(\d{6,10})'
    ],
    'proveedor': [
        r'(?:proveedor|vendedor|supplier|vendor|de|from)[:\s]+([A-Z][A-Za-z0-9\s&.,]+(?:S\.A|S\.R\.L|C\.A|LLC|INC|LTDA)?)',
        r'eBay|Amazon|MercadoLibre|AliExpress'
    ],
    'fecha': [
        r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})',
        r'(\d{4}[/-]\d{1,2}[/-]\d{1,2})'
    ],
    'producto': [
        r'(?:descripcion|producto|articulo|item)[:\s]+([^\n]+)',
        r'([A-Z][a-zA-Z0-9\s\-\+]+(?:Printer|Laptop|Desktop|Monitor|Phone|Tablet|Camera|Headphones|Speaker|Mouse|Keyboard|Router|Modem|Scanner|Copier))'
    ],
    'modelo': [
        r'(?:modelo|model|mod)[:\s]+([A-Z0-9\-]+)',
        r'([A-Z0-9]{3,15}(?:\s*[A-Z0-9\-]+)*)'
    ],
    'marca': [
        r'(?:marca|brand|fabricante)[:\s]+([A-Za-z0-9\s]+)',
        r'(HP|Canon|Epson|Brother|Xerox|Dell|Lenovo|Asus|Samsung|LG|Sony|Apple|Microsoft|Logitech|Intel|AMD|NVIDIA)'
    ],
    'serial': [
        r'(?:serial|s/n|serie|sn)[:\s]+([A-Z0-9\-]+)',
        r'(?:SN|S/N)[:\s]*([A-Z0-9\-]+)'
    ],
    'cantidad': [
        r'(?:cantidad|qty|quantity|cant)[:\s]+(\d+)',
        r'(?:und|unidades|units?)[:\s]+(\d+)',
        r'(\d+)\s*(?:und|unidades|units?)'
    ],
    'costo': [
        r'(?:precio|costo|price|valor|unitario|p/u)[:\s]+[\$]?\s*(\d+[.,]?\d*)',
        r'[\$]\s*(\d+[.,]\d{2})',
        r'(\d+[.,]\d{2})'
    ],
    'total': [
        r'(?:total|monto total|importe)[:\s]+[\$]?\s*([\d.,]+)',
        r'total\s*[\$]?\s*([\d.,]+)'
    ]
}
```

#### 1.3.3 Preprocesamiento de Imagen

**Pasos de Preprocesamiento** (líneas 178-185 de `auditor_recepcion.py`):

1. **Conversión a escala de grises**: `cv2.cvtColor(imagen, cv2.COLOR_BGR2GRAY)`
2. **Reducción de ruido**: `cv2.fastNlMeansDenoising(gris, None, 10, 7, 21)`
3. **Mejora de contraste**: CLAHE (Contrast Limited Adaptive Histogram Equalization)
   - `clipLimit=2.0`, `tileGridSize=(8, 8)`
4. **Binarización adaptativa**: `cv2.adaptiveThreshold`
   - Método: Gaussiano
   - Tamaño de bloque: 11
   - Constante C: 2

**Configuración Tesseract** (línea 192):
```python
config_tesseract = '--psm 6 --oem 3'
```
- `--psm 6`: Assume a single uniform block of text (apropiado para facturas)
- `--oem 3`: Default LSTM OCR engine

**Idiomas**: `spa+eng` (Español + Inglés)

### 1.4 Métricas de Verificación

#### 1.4.1 Métricas de Comparación

**Similitud de Texto (Levenshtein)**:
- **Fórmula**: `similitud = 1.0 - (distancia_levenshtein / max_len)`
- **Implementación**: Líneas 514-535 de `auditor_recepcion.py`
- **Uso**: Comparación de nombres de proveedor y productos
- **Umbral de coincidencia**: ≥ 0.70 (70%)

**Tolerancia de Costo**:
- **Configuración**: `tolerancia_costo = 0.05` (5%)
- **Cálculo**: `diferencia = abs(cost_factura - cost_formulario) / max(cost_formulario, 0.01)`
- **Implementación**: Líneas 472-475 de `auditor_recepcion.py`
- **Discrepancia si**: `diferencia > 0.05`

**Tolerancia de Cantidad**:
- **Configuración**: `tolerancia_cantidad = 0` (coincidencia exacta requerida)
- **Comparación**: Igualdad exacta de enteros
- **Severidad**: CRÍTICA si difiere

#### 1.4.2 Niveles de Severidad de Discrepancias

| Severidad | Condición | Acción |
|-----------|-----------|--------|
| CRÍTICA | Cantidad diferente, Número de factura diferente | Bloqueo de registro |
| ALTA | Serial diferente, Costo fuera de tolerancia (5%), Proveedor con similitud < 80% | Alerta fuerte |
| MEDIA | Modelo diferente, Marca diferente | Alerta moderada |
| BAJA | Diferencias menores (no implementado actualmente) | Informativo |

**Implementación**: Líneas 432-476 de `auditor_recepcion.py`

#### 1.4.3 Métricas de Estadísticas del Sistema

**Métricas Disponibles** (líneas 560-564 de `auditor_recepcion.py`):

```python
{
    "total_verificaciones": int,        # Total de verificaciones realizadas
    "tasa_exito": float,                # Porcentaje de verificaciones exitosas (0.0 - 1.0)
    "verificaciones_exitosas": int,     # Cantidad de verificaciones sin discrepancias
    "verificaciones_fallidas": int      # Cantidad de verificaciones con discrepancias
}
```

**Endpoint de estadísticas**: `GET /fase1/estadisticas`

### 1.5 Métricas de Latencia

#### 1.5.1 Latencia de Extracción OCR

**Componentes**:
1. **Subida de archivo**: Usuario → JavaScript → PHP → FastAPI
2. **Validación de archivo**: Tamaño (< 5MB), extensión permitida
3. **Preprocesamiento de imagen**: OpenCV (escalas de grises, denoising, CLAHE, binarización)
4. **Conversión PDF** (si aplica): pdf2image + Poppler
5. **OCR**: Tesseract (depende de resolución y complejidad)
6. **Extracción de campos**: Regex y parsing
7. **Construcción de respuesta**: JSON
8. **Respuesta HTTP**: FastAPI → PHP → JavaScript

**Timeout configurado**:
- JavaScript: 8000ms (8 segundos) - línea 18 de `asistente_recepcion.js`
- PHP cURL: 17.5 segundos total (con reintento) - línea 69 de `RecepcionIAProxy.php`

**Estimación de tiempos**:
- Imagen simple (800x600 px, 1 página): ~1-3 segundos
- Imagen compleja (factura densa): ~3-6 segundos
- PDF multi-página (3 páginas): ~5-10 segundos
- PDF multi-página (10+ páginas): ~10-20 segundos

**Cuello de botella típico**: Tesseract OCR (CPU-bound)

#### 1.5.2 Latencia de Verificación

**Componentes**:
1. **Validación de caché**: Verificar factura_id en cache
2. **Comparación de campos**: Iteración sobre productos
3. **Cálculo de similitud**: Levenshtein para textos
4. **Generación de reporte**: Formato de discrepancias
5. **Respuesta HTTP**

**Estimación de tiempos**:
- Sin productos (solo cabecera): ~50-100ms
- 1-5 productos: ~100-300ms
- 6-20 productos: ~300-800ms
- 20+ productos: ~800-2000ms

**Cuello de botella típico**: Cálculo de similitud Levenshtein (O(n²) por comparación)

#### 1.5.3 Latencia de Comparación Directa

**Estimación**: Suma de extracción + verificación
- Imagen simple: ~1.5-4 segundos
- Imagen compleja: ~4-8 segundos
- PDF multi-página: ~6-15 segundos

### 1.6 Análisis de Matriz de Confusión

**Nota Importante**: El sistema actual es de **extracción y verificación**, no de clasificación supervisada. No hay modelo de ML entrenado con etiquetas ground truth en la Fase 1.

**Sin embargo, se puede evaluar la calidad de la extracción**:

#### 1.6.1 Métricas de Calidad de Extracción (si se tiene Ground Truth)

Para evaluar la precisión del OCR, se pueden calcular las siguientes métricas:

**A nivel de campo** (por cada campo extraído):

| Métrica | Fórmula | Descripción |
|---------|---------|-------------|
| Exactitud (Accuracy) | TP + TN / (TP + TN + FP + FN) | Coincidencia exacta del campo |
| Precisión (Precision) | TP / (TP + FP) | Campos correctos de los extraídos |
| Recall (Sensibilidad) | TP / (TP + FN) | Campos extraídos de los existentes |
| F1-Score | 2 × (Prec × Rec) / (Prec + Rec) | Balance entre precisión y recall |

**Donde**:
- **TP (True Positive)**: Campo extraído correctamente y coincide con ground truth
- **TN (True Negative)**: Campo no existe en documento y no se extrajo
- **FP (False Positive)**: Campo extraído pero no existe o es incorrecto
- **FN (False Negative)**: Campo existe pero no se extrajo

**Matriz de Confusión (ejemplo para número de factura)**:

| | Extraído Correcto | Extraído Incorrecto/No Extraído |
|--|-------------------|----------------------------------|
| **Existe en documento** | TP | FN |
| **No existe en documento** | TN | FP |

**Ejemplo de cálculo**:
- Documentos procesados: 100
- Números de factura existentes: 100
- Extraídos correctamente: 85 → TP = 85
- No extraídos: 15 → FN = 15
- Extraídos incorrectamente: 3 → FP = 3
- No existen y no extraídos: 0 → TN = 0

```
Accuracy = (85 + 0) / (85 + 0 + 3 + 15) = 85 / 103 = 82.5%
Precision = 85 / (85 + 3) = 85 / 88 = 96.6%
Recall = 85 / (85 + 15) = 85 / 100 = 85.0%
F1-Score = 2 × (0.966 × 0.85) / (0.966 + 0.85) = 0.904 = 90.4%
```

#### 1.6.2 Métricas Actuales del Sistema

**Métricas disponibles sin ground truth**:

1. **Confianza de extracción**: Basada en campos llenos (heurística)
2. **Tasa de éxito de verificación**: Porcentaje de verificaciones sin discrepancias
3. **Número de discrepancias**: Por verificación
4. **Similitud de texto**: Distancia Levenshtein normalizada

**Estado actual**: El sistema NO calcula matriz de confusión porque:
- No hay dataset de ground truth anotado
- La Fase 1 es asistida, no automatizada
- El feedback manual se guarda pero no se usa para entrenar aún

**Recomendación para Fase 2**:
- Implementar recolección sistemática de ground truth
- Calcular métricas de precisión por campo
- Generar matriz de confusión por tipo de campo
- Usar feedback para mejorar patrones regex

### 1.7 Métricas del Circuit Breaker

#### 1.7.1 Configuración del Circuit Breaker

**JavaScript (Cliente)** (líneas 19-21 de `asistente_recepcion.js`):
```javascript
maxFallosCircuito: 3
enfriamientoCircuitoMs: 120000  // 2 minutos
```

**PHP (Proxy)** (líneas 9-10 de `RecepcionIAProxy.php`):
```php
maxFallos = 3
enfriamientoSegundos = 120  // 2 minutos
```

#### 1.7.2 Comportamiento del Circuit Breaker

**Estados**:
1. **Cerrado (Normal)**: Requests pasan normalmente
2. **Abierto (Enfriamiento)**: Requests fallan inmediatamente sin llamar al servicio
3. **Semi-abierto (Prueba)**: Primer request tras enfriamiento para probar recuperación

**Transiciones**:
- **Cerrado → Abierto**: Después de 3 fallos consecutivos
- **Abierto → Semi-abierto**: Después de 2 minutos de enfriamiento
- **Semi-abierto → Cerrado**: Si el request de prueba tiene éxito
- **Semi-abierto → Abierto**: Si el request de prueba falla

**Implementación**:
- JavaScript: localStorage para persistencia (líneas 61-88)
- PHP: Archivo JSON en temp dir con locking (líneas 136-158)

#### 1.7.3 Métricas del Circuit Breaker

**Métricas que deberían monitorearse**:
- Tasa de apertura del circuito (veces por hora/día)
- Tiempo promedio en estado abierto
- Tasa de éxito tras recuperación
- Causas más comunes de fallos (timeout, HTTP 5xx, etc.)

**Estado actual**: No hay métricas expuestas, solo comportamiento interno.

---

## 2. Plan de Pruebas y Respuestas ante Fallos

### 2.1 Estrategia de Pruebas

#### 2.1.1 Pruebas Unitarias (Pendiente de Implementación)

**Archivo recomendado**: `microservicio/tests/test_auditor_recepcion.py`

**Casos de prueba recomendados**:

1. **test_extraer_numero_factura_con_patrones_variados**
   - Objetivo: Verificar extracción de número de factura con diferentes formatos
   - Casos: "Factura N° 012345", "INV-2023-001", "012345"
   - Validar: ID correcto extraído

2. **test_extraer_proveedor_con_similitud**
   - Objetivo: Verificar coincidencia de proveedor con similitud
   - Casos: "Casa Lai C.A." vs "Casa Lai CA"
   - Validar: Similitud ≥ 0.70

3. **test_extraer_productos_tabulares**
   - Objetivo: Verificar extracción de productos en formato tabla
   - Casos: Factura con formato tabular estándar
   - Validar: Todos los productos extraídos con confianza > 0.8

4. **test_verificar_coherencia_sin_discrepancias**
   - Objetivo: Verificar que datos idénticos no generan discrepancias
   - Casos: Factura y formulario con mismos datos
   - Validar: resultado.exito = True

5. **test_verificar_coherencia_con_discrepancia_critica**
   - Objetivo: Verificar detección de discrepancias críticas
   - Casos: Cantidad diferente (5 vs 3)
   - Validar: Severidad = CRITICA, resultado.exito = False

6. **test_calculo_similitud_levenshtein**
   - Objetivo: Verificar cálculo correcto de similitud
   - Casos: "HP DeskJet" vs "HP DeskJet" → 1.0, "HP DeskJet" vs "Canon" → 0.0
   - Validar: Valores esperados

7. **test_preprocesamiento_imagen**
   - Objetivo: Verificar pipeline de preprocesamiento
   - Casos: Imagen de entrada → verificar salida binaria
   - Validar: Dimensiones, tipo de datos, rango de valores

8. **test_conversion_pdf_a_imagen**
   - Objetivo: Verificar conversión de PDF
   - Casos: PDF de 3 páginas
   - Validar: 3 imágenes generadas, DPI correcto

9. **test_circuit_breaker_apertura**
   - Objetivo: Verificar apertura tras 3 fallos
   - Casos: 3 requests fallidos consecutivos
   - Validar: Estado = abierto, enfriamiento activo

10. **test_circuit_breaker_recuperacion**
    - Objetivo: Verificar recuperación tras éxito
    - Casos: Circuito abierto → request exitoso
    - Validar: Estado = cerrado

#### 2.1.2 Pruebas de Integración

**Prueba 1: Flujo completo JavaScript → FastAPI**
```javascript
// Escenario: Usuario sube factura y extrae datos
// 1. Usuario selecciona archivo
// 2. JavaScript envía POST /fase1/extraer
// 3. PHP proxy reenvía a FastAPI
// 4. FastAPI procesa OCR
// 5. JavaScript muestra resultados
// Validar:
// - Circuit breaker no interfiere
// - Timeout de 8s es suficiente
// - Response JSON válido
// - Preview de imagen mostrado
// - Datos extraídos llenan formulario
```

**Prueba 2: Verificación de coherencia**
```javascript
// Escenario: Usuario completa formulario y verifica
// 1. Usuario ingresa datos manuales
// 2. JavaScript envía POST /fase1/verificar
// 3. FastAPI compara con extracción en cache
// 4. JavaScript muestra discrepancias si existen
// Validar:
// - Discrepancias críticas bloquean
// - Discrepancias no críticas alertan
// - Sin discrepancias permite registro
```

**Prueba 3: Comparación directa**
```javascript
// Escenario: Usuario carga factura y datos en un paso
// 1. Usuario selecciona archivo e ingresa datos
// 2. JavaScript envía POST /fase1/comparar-directo
// 3. FastAPI extrae y verifica en un solo request
// Validar:
// - Funcionalidad equivalente a extraer + verificar
// - Latencia menor (un solo round-trip)
```

#### 2.1.3 Pruebas de Carga (Stress Testing)

**Herramienta recomendada**: Apache JMeter o Locust

**Escenario 1: Extracción concurrente**
- 50 usuarios suben facturas en paralelo
- Tamaño promedio: 500KB
- Validar: Tiempo de respuesta < 8s por solicitud
- Validar: CPU no satura al 100%

**Escenario 2: Verificación concurrente**
- 100 usuarios verifican datos en paralelo
- Cache con 10 facturas diferentes
- Validar: Tiempo de respuesta < 1s por solicitud
- Validar: No hay race conditions en cache

**Escenario 3: Circuit breaker bajo carga**
- 1000 requests, 30% fallan deliberadamente
- Validar: Circuit breaker se abre tras 3 fallos
- Validar: Requests posteriores fallan rápido (sin timeout)
- Validar: Recuperación tras enfriamiento

#### 2.1.4 Pruebas de Seguridad

**Prueba 1: Validación de archivo**
- Enviar archivo > 5MB
- Validar: HTTP 413 Payload Too Large
- Enviar archivo no permitido (.exe)
- Validar: HTTP 400 Bad Request

**Prueba 2: Validación de esquema**
- Enviar JSON malformado en /fase1/verificar
- Validar: HTTP 422 Unprocessable Entity
- Enviar factura_id no existente en cache
- Validar: Discrepancia de sistema, no crash

**Prueba 3: Inyección de datos**
- Enviar nombre de proveedor con caracteres especiales
- Validar: Normalización correcta, no SQL injection
- Enviar datos_json con código malicioso
- Validar: Rechazado por validación de esquema

**Prueba 4: Acceso no autorizado**
- Intentar acceso desde IP externa (si FastAPI no está en loopback)
- Validar: Rechazado por configuración de red

### 2.2 Respuestas ante Fallos

#### 2.2.1 Fallos de Conectividad

**Escenario**: FastAPI no está ejecutándose

**Detección**:
- JavaScript: Timeout de 8s
- PHP cURL: Timeout de 17.5s con reintento
- Circuit breaker: Se abre tras 3 fallos

**Respuesta JavaScript** (líneas 116-119 de `asistente_recepcion.js`):
```javascript
if (error.name === 'AbortError') {
    error = new Error('Tiempo de espera agotado al consultar el microservicio.');
    error.contingencia = true;
}
```

**Respuesta PHP** (líneas 103-106 de `RecepcionIAProxy.php`):
```php
if ($respuesta === false || $codigoHttp === 0 || $codigoHttp === 408 || 
    $codigoHttp === 429 || $codigoHttp >= 500) {
    $this->registrarFalloCircuito();
    $mensaje = $errorCurl ?: 'El microservicio de recepción no está disponible temporalmente.';
    return $this->respuestaError(503, $mensaje);
}
```

**Tratamiento en UI**:
- Mostrar: "El servicio de IA no está disponible. Puede completar el formulario manualmente."
- Habilitar entrada manual de datos (contingencia)
- No bloquear el flujo, solo deshabilitar características de IA

**Circuit breaker**:
- Tras 3 fallos: Estado abierto, período de enfriamiento 2 minutos
- Requests posteriores: Fallan inmediatamente sin esperar timeout
- Recuperación automática tras éxito

#### 2.2.2 Fallos de OCR

**Escenario 1**: Tesseract no instalado

**Detección**: `pytesseract.TesseractNotFoundError` durante ejecución

**Respuesta actual**: Excepción no capturada, HTTP 500

**Recomendación de mejora**:
```python
try:
    texto = pytesseract.image_to_string(imagen, lang=idioma, config=config)
except pytesseract.TesseractNotFoundError:
    logger.error("Tesseract no está instalado")
    return {
        "exito": False,
        "error": "Tesseract OCR no está disponible en el servidor",
        "contingencia": True
    }
```

**Escenario 2**: Imagen corrupta o no legible

**Detección**: `cv2.imread()` retorna None (línea 174)

**Respuesta** (líneas 174-176):
```python
imagen = cv2.imread(ruta_imagen)
if imagen is None:
    raise ValueError(f"No se pudo cargar: {ruta_imagen}")
```

**Tratamiento en UI**:
- Mostrar: "No se pudo procesar la imagen. Verifique que el archivo sea válido."
- Sugerir: "Intente con otra imagen o complete el formulario manualmente."

#### 2.2.3 Fallos de Extracción

**Escenario**: OCR devuelve texto vacío o sin datos reconocibles

**Detección**: `texto_extraido` vacío o todos los campos vacíos

**Respuesta actual**: Retorna factura con campos vacíos, confianza = 0.5

**Tratamiento en UI**:
- Mostrar: "No se pudieron extraer datos de la factura. Confianza baja."
- Habilitar edición manual de todos los campos
- Mostrar advertencia de que datos deben ser verificados

**Recomendación de mejora**:
```python
if not texto_extraido.strip():
    return {
        "exito": False,
        "confianza": 0.0,
        "mensaje": "El OCR no detectó texto en la imagen",
        "contingencia": True
    }
```

#### 2.2.4 Fallos de Verificación

**Escenario 1**: Factura no encontrada en cache

**Detección**: `id_factura_cache` no en `self.cache_facturas` (línea 139)

**Respuesta** (líneas 142-143):
```python
return ResultadoVerificacion(
    exito=False,
    discrepancias=[Discrepancia(
        campo="SISTEMA",
        valor_factura="N/A",
        valor_formulario="N/A",
        severidad="CRITICA",
        mensaje=error_msg
    )],
    factura_extraida=None,
    datos_formulario=datos_formulario,
    reporte_detallado=error_msg
)
```

**Tratamiento en UI**:
- Mostrar: "La factura expiró del cache. Por favor, extráigala nuevamente."
- Habilitar botón "Extraer nuevamente"

**Escenario 2**: Cache expirado (más de 24 horas)

**Detección**: Función `limpiar_cache()` elimina entradas antiguas (líneas 566-580)

**Respuesta**: Factura eliminada silenciosamente

**Tratamiento**: Similar a factura no encontrada

#### 2.2.5 Fallos de Conversión de PDF

**Escenario**: pdf2image no instalado

**Detección**: Import falla (líneas 25-31)

**Respuesta** (líneas 28-31):
```python
except ImportError:
    PDF_AVAILABLE = False
    convert_from_path = None
    logger.warning("pdf2image no disponible. Los PDFs no podrán procesarse.")
```

**Tratamiento en UI**:
- Mostrar: "El procesamiento de PDF no está disponible. Use imágenes (JPG, PNG)."
- Rechazar archivos PDF en validación de cliente

**Escenario 2**: Poppler no encontrado (Windows)

**Detección**: Ruta de Poppler no existe (línea 591)

**Respuesta** (líneas 589-595):
```python
if platform.system() == 'Windows':
    poppler_path = r"C:\xampp\htdocs\proyecto-casalai-ca\microservicio\Poppler\poppler-26.09.0\Library\bin"
    if Path(poppler_path).exists():
        imagenes = convert_from_path(ruta_pdf, dpi=200, fmt='jpeg', poppler_path=poppler_path)
    else:
        imagenes = convert_from_path(ruta_pdf, dpi=200, fmt='jpeg')
```

**Tratamiento**:
- Si Poppler no existe: Intenta sin especificar ruta (puede funcionar si está en PATH)
- Si falla: Error de conversión

#### 2.2.6 Fallos de Persistencia

**Escenario**: No se puede escribir en directorio temporal

**Detección**: OSError durante escritura de archivo temporal

**Respuesta actual**: Excepción no capturada

**Recomendación de mejora**:
```python
try:
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)
except OSError as e:
    logger.error(f"Error escribiendo archivo temporal: {e}")
    return {
        "exito": False,
        "error": "No se pudo guardar el archivo temporal",
        "contingencia": True
    }
```

### 2.3 Monitoreo y Alertas

#### 2.3.1 Métricas a Monitorear

**Sistema**:
- CPU de FastAPI (OCR es CPU-bound)
- Memoria de FastAPI (carga de imágenes grandes)
- Latencia de endpoints (p50, p95, p99)
- Tasa de errores HTTP (4xx, 5xx)
- Estado del circuit breaker (abierto/cerrado)

**Negocio**:
- Facturas procesadas por día
- Tasa de éxito de extracción (confianza promedio)
- Tasa de éxito de verificación (sin discrepancias)
- Número promedio de discrepancias por verificación
- Tiempo promedio de procesamiento por factura

**OCR**:
- Confianza promedio de extracción
- Campos extraídos correctamente vs intentados
- Tiempo de OCR por página
- Tasa de fallos de OCR (texto vacío)

**Circuit Breaker**:
- Veces abierto por día
- Tiempo promedio en estado abierto
- Tasa de recuperación exitosa

#### 2.3.2 Umbrales de Alerta

| Métrica | Umbral Advertencia | Umbral Crítico | Acción |
|---------|-------------------|----------------|--------|
| Latencia extracción (p95) | > 6s | > 10s | Revisar CPU, optimizar imágenes |
| Latencia verificación (p95) | > 500ms | > 2s | Revisar cache, optimizar algoritmos |
| Tasa de errores HTTP | > 5% | > 15% | Revisar logs, verificar dependencias |
| CPU FastAPI | > 70% | > 90% | Escalar verticalmente |
| Memoria FastAPI | > 1GB | > 2GB | Revisar leaks, escalar |
| Confianza promedio | < 0.70 | < 0.50 | Revisar patrones regex, ajustar preprocesamiento |
| Circuit breaker abierto | > 3 veces/día | > 10 veces/día | Investigar causa raíz |
| Tasa de éxito verificación | < 80% | < 60% | Revisar calidad de extracción |

#### 2.3.3 Logs Implementados

**Nivel INFO**:
- Inicialización del auditor (línea 97)
- Inicio de extracción OCR (línea 120)
- Extracción completada con confianza (línea 134)
- Inicio de verificación (línea 138)
- Resultado de verificación (línea 160)
- Conversión de PDF (línea 602)

**Nivel WARNING**:
- pdf2image no disponible (línea 31)
- Error cargando configuración (línea 116)
- Precio unitario inconsistente (línea 296-299)
- Error parseando línea de producto (línea 402)

**Nivel ERROR**:
- Error crítico procesando archivo (línea 77)
- Factura no encontrada en cache (línea 141)
- Error convirtiendo PDF (línea 606)
- Error exportando historial (línea 616)

**Archivo de logs**: `microservicio/logs_metricas/auditor_recepcion.log`

**Recomendación**:
- Agregar logs estructurados (JSON)
- Incluir correlation_id para tracing
- Centralizar logs (ELK stack o similar)
- Agregar métricas de negocio en logs

---

## 3. Pruebas de Mantenimiento y Actualización

### 3.1 Estrategia de Mantenimiento

#### 3.1.1 Mantenimiento Rutinario

**Frecuencia**: Semanal

**Tareas**:
1. **Verificar salud del servicio**
   ```bash
   curl http://127.0.0.1:8000/health
   ```
   - Esperar: HTTP 200 con `{"status": "ok", "auditor_listo": true}`
   - Rechazar: HTTP 503, 500

2. **Revisar tamaño de cache**
   ```bash
   # Revisar entradas en cache (estadísticas internas)
   curl http://127.0.0.1:8000/fase1/estadisticas
   ```
   - Alerta si cache crece indefinidamente (memory leak)

3. **Limpiar cache antiguo**
   ```python
   # Llamada programada
   auditor.limpiar_cache(max_edad_horas=24)
   ```
   - Eliminar facturas con más de 24 horas en cache

4. **Verificar tamaño de logs**
   ```bash
   ls -lh microservicio/logs_metricas/auditor_recepcion.log
   ```
   - Alerta si > 100MB
   - Rotar si necesario

5. **Verificar espacio en disco**
   ```bash
   df -h microservicio/temp/
   ```
   - Alerta si > 80% usado
   - Limpiar archivos temporales antiguos

#### 3.1.2 Mantenimiento Mensual

**Tareas**:
1. **Exportar historial de verificaciones**
   ```python
   auditor.exportar_historial("backups/historial_YYYYMM.json")
   ```
   - Archivar para análisis posterior
   - Mantener últimos 12 meses

2. **Analizar confianza promedio**
   - Extraer métricas de logs
   - Identificar facturas con confianza < 0.70
   - Revisar patrones regex que fallan

3. **Revisar patrones de fallos del circuit breaker**
   - Leer archivo de estado del circuit breaker
   - Identificar causas comunes
   - Ajustar timeouts si es necesario

4. **Actualizar dependencias de Python**
   ```bash
   cd microservicio
   pip list --outdated
   pip install --upgrade -r requirements.txt
   ```
   - Probar en entorno de desarrollo primero

#### 3.1.3 Mantenimiento Trimestral

**Tareas**:
1. **Revisar y actualizar patrones regex**
   - Analizar facturas que fallaron
   - Agregar nuevos patrones según necesidad
   - Pruebas unitarias de nuevos patrones

2. **Optimizar configuración de Tesseract**
   - Probar diferentes modos PSM
   - Ajustar parámetros de OCR
   - Evaluar impacto en latencia y precisión

3. **Auditoría de seguridad**
   - Verificar que FastAPI escucha solo en 127.0.0.1
   - Revisar permisos de directorios
   - Verificar que no hay exposición de datos sensibles

4. **Capacidad planeada**
   - Revisar métricas de carga
   - Planificar escalado si es necesario
   - Evaluar necesidad de balanceo de carga

### 3.2 Procedimiento de Actualización

#### 3.2.1 Actualización de Patrones Regex

**Escenario**: Agregar nuevo patrón para un proveedor específico

**Pasos**:

1. **Identificar patrón faltante**
   - Revisar logs de facturas fallidas
   - Analizar formato del proveedor
   - Diseñar regex apropiado

2. **Agregar patrón a PATRONES**
   ```python
   PATRONES = {
       'proveedor': [
           # ... patrones existentes ...
           r'(?:NUEVO_PROVEEDOR)[:\s]+([A-Z][A-Za-z0-9\s&.,]+)',
       ]
   }
   ```

3. **Agregar prueba unitaria**
   ```python
   def test_extraer_nuevo_proveedor():
       texto = "PROVEEDOR: Empresa XYZ S.A."
       auditor = AuditorRecepcion()
       resultado = auditor._extraer_con_patrones(texto, auditor.PATRONES['proveedor'])
       assert resultado == "Empresa XYZ S.A."
   ```

4. **Ejecutar pruebas**
   ```bash
   python -m unittest microservicio.tests.test_auditor_recepcion -v
   ```

5. **Probar en desarrollo**
   - Iniciar FastAPI
   - Subir factura de prueba
   - Validar extracción correcta

6. **Deploy en producción**
   - Backup del código actual
   - Deploy del nuevo código
   - Monitorear logs por 24 horas

#### 3.2.2 Actualización de Versión de Tesseract

**Pasos**:

1. **Descargar nueva versión**
   - Visitar sitio oficial de Tesseract
   - Descargar installer para Windows

2. **Instalar en entorno de desarrollo**
   - Ejecutar installer
   - Verificar instalación: `tesseract --version`

3. **Actualizar ruta en configuración**
   ```python
   tesseract_path = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
   ```

4. **Probar con facturas de muestra**
   - Comparar calidad de OCR antes/después
   - Medir latencia antes/después

5. **Evaluar impacto**
   - Si mejora significativa: Planificar deploy
   - Si empeora: Revertir

6. **Deploy en producción**
   - Coordinar con equipo de infraestructura
   - Instalar en servidores
   - Reiniciar FastAPI

#### 3.2.3 Actualización de Dependencias de Python

**Pasos**:

1. **Revisar dependencias actuales**
   ```bash
   cat microservicio/requirements.txt
   ```

2. **Identificar actualizaciones disponibles**
   ```bash
   pip list --outdated
   ```

3. **Actualizar en entorno de desarrollo**
   ```bash
   pip install --upgrade <paquete>
   pip freeze > requirements.txt
   ```

4. **Ejecutar pruebas**
   ```bash
   python -m unittest discover microservicio/tests
   ```

5. **Probar flujo completo**
   - Iniciar FastAPI
   - Ejecutar extracción de factura
   - Ejecutar verificación
   - Validar comportamiento

6. **Deploy en producción**
   - Solo si pruebas pasan
   - Con rollback preparado
   - Monitorear errores

### 3.3 Procedimiento de Recuperación ante Desastres

#### 3.3.1 Pérdida de Cache de Facturas

**Síntomas**:
- Error: "Factura no encontrada en cache"
- Cache vacío tras reinicio

**Impacto**:
- Usuarios deben reextraer facturas
- Sin pérdida de datos (solo caché temporal)

**Recuperación**:

1. **Reinicio del servicio**
   ```bash
   # Reiniciar FastAPI
   # Cache se reconstruye desde cero
   ```

2. **Si hay backup de cache** (opcional):
   - Restaurar desde backup si es crítico
   - Generalmente no necesario (cache es volátil)

3. **Preventivo**:
   - Implementar persistencia de cache en Redis o BD
   - Configurar TTL automático
   - Documentar que cache es volátil

#### 3.3.2 Corrupción de Logs

**Síntomas**:
- Archivo de logs corrupto
- No se pueden escribir nuevos logs

**Impacto**:
- Pérdida de visibilidad de errores
- Sistema sigue funcionando

**Recuperación**:

1. **Rotar logs manualmente**
   ```bash
   mv microservicio/logs_metricas/auditor_recepcion.log \
      microservicio/logs_metricas/auditor_recepcion.log.corrupto
   # Reiniciar FastAPI para crear nuevo archivo
   ```

2. **Analizar logs corruptos** (si es posible)
   - Intentar reparar con herramientas de texto
   - Extraer información recuperable

3. **Preventivo**:
   - Configurar logrotate automático
   - Tamaño máximo de archivo (ej: 50MB)
   - Retener N archivos anteriores

#### 3.3.3 Fallo de Tesseract

**Síntomas**:
- Error: "Tesseract not found"
- Extracción falla completamente

**Impacto**:
- Característica de IA no disponible
- Usuarios deben completar manualmente

**Recuperación**:

1. **Verificar instalación**
   ```bash
   tesseract --version
   ```

2. **Reinstalar si es necesario**
   - Descargar installer oficial
   - Reinstalar en ruta correcta

3. **Actualizar configuración**
   ```python
   tesseract_path = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
   ```

4. **Preventivo**:
   - Monitorear salud de Tesseract
   - Tener installer en repositorio interno
   - Documentar procedimiento de reinstalación

#### 3.3.4 Compromiso de Seguridad

**Síntomas**:
- Acceso no autorizado a endpoints
- Comportamiento anómalo en logs

**Recuperación**:

1. **Bloquear acceso externo**
   - Verificar que FastAPI escucha solo en 127.0.0.1
   - Configurar firewall si es necesario

2. **Rotar secretos** (si se agregan en el futuro)
   - Implementar autenticación si no existe
   - Rotar tokens de API

3. **Investigar incidente**
   - Revisar logs de acceso
   - Identificar IPs sospechosas
   - Verificar no hay otros compromisos

4. **Preventivo**:
   - Implementar autenticación JWT
   - Usar HTTPS en producción
   - Configurar CORS restrictivo
   - Auditoría de accesos

### 3.4 Pruebas de Regresión

#### 3.4.1 Suite de Pruebas de Regresión

**Objetivo**: Asegurar que actualizaciones no rompen funcionalidad existente

**Implementación recomendada**:

```python
# tests/test_regression_auditor.py
import unittest
from microservicio.modelo import auditor_recepcion as auditor

class RegressionTest(unittest.TestCase):
    def test_patrones_regex_backward_compatible(self):
        """Patrones existentes deben seguir funcionando"""
        auditor_inst = auditor.AuditorRecepcion()
        texto = "FACTURA N° 012345"
        resultado = auditor_inst._extraer_con_patrones(
            texto, auditor_inst.PATRONES['numero_factura']
        )
        self.assertEqual(resultado, "012345")

    def test_extraccion_estable_para_misma_imagen(self):
        """Misma imagen debe producir misma extracción"""
        auditor_inst = auditor.AuditorRecepcion()
        resultado1 = auditor_inst.extraer_desde_imagen("test_image.jpg", "test_id")
        resultado2 = auditor_inst.extraer_desde_imagen("test_image.jpg", "test_id2")
        self.assertEqual(resultado1.numero_factura, resultado2.numero_factura)

    def test_verificacion_consistente(self):
        """Mismos datos deben producir mismo resultado"""
        auditor_inst = auditor.AuditorRecepcion()
        # ... setup de prueba ...
        resultado1 = auditor_inst.verificar_coherencia(id_cache, datos_form)
        resultado2 = auditor_inst.verificar_coherencia(id_cache, datos_form)
        self.assertEqual(resultado1.exito, resultado2.exito)
        self.assertEqual(len(resultado1.discrepancias), len(resultado2.discrepancias))
```

#### 3.4.2 Pruebas de Canary

**Objetivo**: Validar actualización en subset de usuarios antes de deploy completo

**Implementación**:

1. **Deploy canary en nuevo puerto**
   ```bash
   uvicorn controlador.api_recepcion:app --host 127.0.0.1 --port 8001
   ```

2. **Configurar proxy PHP para direcionar 10% del tráfico**
   ```php
   if (rand(1, 10) == 1) {
       $proxy = new RecepcionIAProxy('http://127.0.0.1:8001');
   } else {
       $proxy = new RecepcionIAProxy('http://127.0.0.1:8000');
   }
   ```

3. **Monitorear errores en canary**
   - Si tasa de errores > baseline: rollback inmediato
   - Si tasa de errores similar: expandir a 50%
   - Si estable: migrar 100% y desactivar puerto 8001

### 3.5 Documentación de Cambios

#### 3.5.1 Changelog

**Formato recomendado** (Keep a Changelog):

```markdown
# Changelog - Microservicio Recepción IA

## [Unreleased]

### Added
- Nuevo patrón regex para proveedor XYZ
- Endpoint de health check mejorado
- Métricas de confidence por campo

### Changed
- Timeout de extracción aumentado de 8s a 10s
- Umbral de similitud de proveedor ajustado de 0.70 a 0.75

### Fixed
- Bug: División por cero en cálculo de confianza cuando no hay productos
- Bug: Circuit breaker no se cerraba tras recuperación exitosa

### Deprecated
- Campo legacy_metadatos en respuesta (eliminar en v2.0)

## [1.2.0] - 2026-10-21

### Added
- Sistema de circuit breaker en JavaScript y PHP
- Endpoint de comparación directa
- Registro de versiones de modelos (ModelRegistry)

### Changed
- Mejorada robustez de extracción de productos tabulares
- Optimizado preprocesamiento de imagen

## [1.1.0] - 2026-09-15

### Added
- Primer release oficial
- Extracción OCR con Tesseract
- Verificación de coherencia
- Endpoints de fase 1

### Changed
- Implementación inicial de preprocesamiento OpenCV
```

#### 3.5.2 Registro de Incidentes

**Plantilla**:

```markdown
# Incidente #001: Caída del servicio por fallo de Tesseract

**Fecha**: 2026-10-20 10:15
**Duración**: 45 minutos
**Severidad**: Alta

**Descripción**:
Tesseract OCR dejó de responder tras actualización de Windows. Todas las extracciones fallaban con timeout.

**Causa raíz**:
- Actualización de Windows modificó PATH del sistema
- Ruta de Tesseract ya no era accesible
- Configuración hardcoded no detectó cambio

**Resolución**:
- Restaurar PATH del sistema
- Actualizar configuración con ruta absoluta
- Agregar verificación de disponibilidad al inicio

**Acciones preventivas**:
- Usar ruta absoluta en configuración
- Agregar health check de Tesseract al inicio
- Monitorear disponibilidad de Tesseract

**Lessons learned**:
- Dependencias externas deben verificarse al inicio
- Rutas absolutas son más robustas que PATH
- Health checks deben incluir dependencias críticas
```

---

## 4. Métricas de Éxito y KPIs

### 4.1 KPIs Técnicos

| KPI | Métrica | Objetivo | Actual |
|-----|---------|----------|--------|
| Disponibilidad del servicio | % tiempo online | ≥ 99.5% | Pendiente |
| Latencia extracción (p95) | Segundos | < 8s | Pendiente |
| Latencia verificación (p95) | Segundos | < 1s | Pendiente |
| Tasa de errores HTTP | % requests | < 1% | Pendiente |
| Confianza promedio de extracción | % (0-100) | ≥ 75% | Pendiente |
| Tasa de éxito de verificación | % sin discrepancias | ≥ 80% | Pendiente |
| Circuit breaker abierto | Veces por día | < 3 | Pendiente |

### 4.2 KPIs de Negocio

| KPI | Métrica | Objetivo | Actual |
|-----|---------|----------|--------|
| Adopción de IA | % recepciones con IA | ≥ 70% | Pendiente |
| Reducción de tiempo de recepción | % reducción vs manual | ≥ 50% | Pendiente |
| Reducción de errores de data entry | % reducción de errores | ≥ 60% | Pendiente |
| Satisfacción de usuarios | Encuesta (1-5) | ≥ 4.0 | Pendiente |
| Facturas procesadas por día | Total | ≥ 50 | Pendiente |
| Discrepancias detectadas | % de recepciones bloqueadas | 10-20% | Pendiente |

### 4.3 Monitoreo Continuo

**Dashboard recomendado** (Grafana o similar):

- Panel 1: Salud del servicio (CPU, memoria, latencia, errores)
- Panel 2: Métricas de OCR (confianza, campos extraídos, tiempo de OCR)
- Panel 3: Métricas de verificación (tasa de éxito, discrepancias por tipo)
- Panel 4: Circuit breaker (estado, aperturas, tiempo en estado abierto)
- Panel 5: Métricas de negocio (recepciones, adopción, satisfacción)
- Panel 6: Alertas y incidentes recientes

---

## 5. Conclusiones y Recomendaciones

### 5.1 Fortalezas del Diseño Actual

1. **Arquitectura modular**: Separación clara entre OCR, extracción y verificación
2. **Resiliencia**: Circuit breaker en dos niveles (JavaScript y PHP)
3. **Contingencia**: Sistema permite operación manual si IA falla
4. **Extensibilidad**: ModelRegistry preparado para fases futuras con ML
5. **Flexibilidad**: Múltiples métodos de extracción de productos
6. **Preprocesamiento robusto**: Pipeline de OpenCV mejora calidad de OCR

### 5.2 Áreas de Mejora Identificadas

1. **Sin pruebas unitarias**: No hay suite de pruebas automatizada
2. **Sin métricas de precisión**: No hay ground truth para calcular accuracy/precision/recall
3. **Caché volátil**: Se pierde al reiniciar el servicio
4. **Logs no estructurados**: Difícil de analizar a gran escala
5. **Sin autenticación**: Endpoints no protegidos (asumido por loopback)
6. **Sin monitoreo**: No hay métricas expuestas para Prometheus/Grafana
7. **Configuración hardcoded**: Rutas de Tesseract y Poppler hardcodeadas
8. **Sin A/B testing**: No hay forma de probar cambios en subset de usuarios

### 5.3 Roadmap de Mejoras

**Corto plazo (1-2 meses)**:
- [ ] Implementar suite de pruebas unitarias
- [ ] Agregar health check de dependencias (Tesseract, Poppler)
- [ ] Implementar logs estructurados (JSON)
- [ ] Configurar rotación automática de logs
- [ ] Agregar métricas básicas (Prometheus)

**Mediano plazo (3-6 meses)**:
- [ ] Implementar persistencia de cache en Redis
- [ ] Recolectar ground truth sistemático
- [ ] Calcular métricas de precisión por campo
- [ ] Implementar autenticación JWT
- [ ] Configurar monitoreo con Grafana + Prometheus
- [ ] Implementar canary deployments

**Largo plazo (6-12 meses)**:
- [ ] Fase 2: Modelo ML para extracción (reemplazar regex)
- [ ] Fase 3: Autenticación de facturas (detección de falsificaciones)
- [ ] Sistema de A/B testing de algoritmos
- [ ] API pública para integraciones externas
- [ ] Análisis de efectividad comercial (reducción de errores, ahorro de tiempo)

### 5.4 Recomendaciones de Operación

1. **Monitorear confianza promedio**: Alertar si < 0.70 por más de 3 días consecutivos
2. **Limpiar cache semanalmente**: Evitar memory leaks
3. **Revisar logs diariamente**: Buscar patrones de fallos recurrentes
4. **Documentar facturas problemáticas**: Agregar a dataset de pruebas
5. **Actualizar patrones trimestralmente**: Basado en análisis de fallos
6. **Capacitar usuarios**: En uso de IA y manejo de contingencias
7. **Establecer SLA**: Definir tiempos de respuesta y disponibilidad esperados

---

## Anexos

### Anexo A: Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────┐
│                   Navegador (Usuario)                   │
│                  recepcion.php + JS                      │
└────────────────────┬────────────────────────────────────┘
                     │
            ┌────────┴────────┐
            │  HTTP/HTTPS     │
            └────────┬────────┘
                     │
        ┌────────────┴────────────┐
        │   Servidor Web (Apache) │
        │        PHP 7.4+         │
        │  RecepcionIAProxy.php   │
        │  - Circuit Breaker      │
        │  - cURL                 │
        └────────────┬────────────┘
                     │ cURL (localhost)
                     │
        ┌────────────┴────────────┐
        │   FastAPI (Python)     │
        │   api_recepcion.py      │
        │   127.0.0.1:8000        │
        └────────────┬────────────┘
                     │
         ┌───────────┼───────────┐
         ↓           ↓           ↓
    ┌────────┐  ┌────────┐  ┌──────────┐
    │  OCR   │  │ Auditor│  │ Registry │
    │Tesseract│  │ Regex  │  │ Modelo   │
    └────────┘  └────────┘  └──────────┘
         │           │
         ↓           ↓
    ┌────────┐  ┌────────┐
    │ OpenCV │  │ Cache  │
    │ PDF2Img│  │ (RAM)  │
    └────────┘  └────────┘
```

### Anexo B: Esquema de Datos

**FacturaExtraida** (dataclass):
```python
{
    "numero_factura": str,
    "nombre_proveedor": str,
    "fecha_factura": str,
    "productos": [
        {
            "nombre": str,
            "modelo": str,
            "marca": str,
            "serial": str,
            "cantidad": int,
            "costo_unitario": float,
            "confianza": float
        }
    ],
    "subtotal_factura": float,
    "porcentaje_iva": float,
    "monto_iva": float,
    "total_factura": float,
    "metadatos_extraccion": {
        "timestamp": str (ISO-8601),
        "longitud_texto": int,
        "confianza_promedio": float,
        "lineas_detectadas": int,
        "metodo": str
    }
}
```

**ResultadoVerificacion** (dataclass):
```python
{
    "exito": bool,
    "discrepancias": [
        {
            "campo": str,
            "valor_factura": str,
            "valor_formulario": str,
            "severidad": str ("CRITICA"|"ALTA"|"MEDIA"|"BAJA"),
            "mensaje": str
        }
    ],
    "factura_extraida": FacturaExtraida,
    "datos_formulario": dict,
    "reporte_detallado": str,
    "timestamp": str (ISO-8601),
    "hash_verificacion": str (SHA256, primeros 16 caracteres)
}
```

**Estado del Circuit Breaker**:
```json
{
    "fallos": int,
    "abierto_hasta": int (timestamp Unix)
}
```

### Anexo C: Endpoints de la API

| Método | Endpoint | Descripción | Request | Response |
|--------|----------|-------------|---------|----------|
| GET | `/` | Estado general | - | `{status, service, active_version, model_metrics}` |
| GET | `/health` | Health check | - | `{status, auditor_listo}` |
| POST | `/api/v1/auditar` | Auditar documento (v1.2) | `multipart/form-data: file` | `{status, version_modelo, resultado_auditoria}` |
| POST | `/fase1/extraer` | Extraer factura (v1.0) | `multipart/form-data: imagen` | `{exito, factura_id, numero_factura, nombre_proveedor, ...}` |
| POST | `/fase1/verificar` | Verificar coherencia | `{factura_id, datos_formulario}` | `{exito, discrepancias, reporte_detallado, hash_verificacion, ...}` |
| POST | `/fase1/comparar-directo` | Comparación directa | `multipart/form-data: imagen, datos_json` | `{exito, discrepancias, reporte, mensaje}` |
| GET | `/fase1/cache/{factura_id}` | Obtener cache | - | `{factura_id, numero, proveedor, productos}` |
| GET | `/fase1/estadisticas` | Estadísticas | - | `{total_verificaciones, tasa_exito}` |
| POST | `/api/v1/feedback` | Registrar feedback | `{documento_id, prediccion_ocr, ground_truth, ...}` | `{status, message}` |
| POST | `/api/v1/modelo/recargar` | Recargar modelo | - | `{status, active_version, detalles, message}` |

### Anexo D: Comandos Útiles

**Iniciar el servicio**:
```bash
cd microservicio
uvicorn controlador.api_recepcion:app --host 127.0.0.1 --port 8000
```

**Verificar health**:
```bash
curl http://127.0.0.1:8000/health
```

**Probar extracción**:
```bash
curl -X POST http://127.0.0.1:8000/fase1/extraer \
  -F "imagen=@/ruta/a/factura.jpg"
```

**Ver estadísticas**:
```bash
curl http://127.0.0.1:8000/fase1/estadisticas
```

**Limpiar cache manualmente** (vía Python):
```python
from microservicio.modelo.auditor_recepcion import AuditorRecepcion
auditor = AuditorRecepcion()
eliminados = auditor.limpiar_cache(max_edad_horas=24)
print(f"Eliminados: {eliminados}")
```

**Exportar historial**:
```python
from microservicio.modelo.auditor_recepcion import AuditorRecepcion
auditor = AuditorRecepcion()
auditor.exportar_historial("historial_backup.json")
```

**Ver logs**:
```bash
tail -f microservicio/logs_metricas/auditor_recepcion.log
```

**Probar Tesseract**:
```bash
tesseract --version
tesseract imagen.jpg stdout -l spa+eng
```

---

**Documento preparado para**: Capítulo IV - Proyecto Universitario
**Fecha**: 2026-10-21
**Versión**: 1.0
**Autor**: Ingeniero de Software (Devin AI)
