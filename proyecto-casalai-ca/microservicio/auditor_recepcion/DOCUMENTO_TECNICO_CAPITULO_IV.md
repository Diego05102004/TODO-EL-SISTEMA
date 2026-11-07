# Documento Técnico: Microservicio de Auditoría de Recepción
## Capítulo IV - Análisis de Arquitectura y Métricas

---

## 1. Reporte Técnico de Métricas de Entrenamiento y Validación

### 1.1 Arquitectura del Modelo

**Algoritmo**: RandomForestClassifier para clasificación de confiabilidad de extracción

**Características del Modelo**:
- **Tipo de modelo**: Clasificación binaria (Random Forest)
- **Objetivo**: Predecir si una extracción OCR es confiable (error_total < 0.3)
- **Features de entrada**: 6 características basadas en errores históricos
- **Mínimo de muestras**: 10 registros en feedback_dataset.csv
- **Algoritmo base**: Tesseract OCR + Regex contextual

**Ecuación del Modelo**:
```
target = 1 si error_total < 0.3 (extracción confiable)
target = 0 si error_total >= 0.3 (extracción no confiable)
```

**Features de entrada**:
- `error_proveedor`: Error en extracción del nombre del proveedor (0 o 1)
- `error_correlativo`: Error en extracción del número de factura (0 o 1)
- `error_productos`: Error en extracción de productos (0 o 1)
- `error_subtotal`: Error en extracción del subtotal (0 o 1)
- `error_total`: Error en extracción del total (0 o 1)
- `confianza_ocr_promedio`: Confianza promedio de Tesseract (0.0 a 1.0)

### 1.2 Métricas de Entrenamiento

#### 1.2.1 Métricas Calculadas por el Modelo

**Error por Campo**:
- **Fórmula**: `error = 1 si prediccion != ground_truth, 0 si coinciden`
- **Implementación**: Calculado en PHP (`agregarFeedbackDataset()`)
- **Significado**: Proporción de extracciones incorrectas por campo
- **Uso**: Features para entrenamiento del modelo de clasificación

**Confianza OCR Promedio**:
- **Fórmula**: Promedio de confianza de Tesseract por campo extraído
- **Implementación**: Línea 130 de `auditor_recepcion.py`
- **Rango**: 0.0 (baja) a 1.0 (alta)
- **Uso**: Feature para determinar calidad de extracción

**Muestras de Entrenamiento**:
- **Requisito mínimo**: 10 registros en feedback_dataset.csv
- **Fuente de datos**: Recepciones registradas con microservicio activado
- **Acumulación**: Cada registro de recepción añade una fila al dataset

#### 1.2.2 Métricas Disponibles en la API

El modelo expone las siguientes métricas en el artefacto JSON (`metricas_reentrenamiento.json`):

```json
{
  "fecha_reentrenamiento": "2026-10-22T...",
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

### 1.3 Métricas de Validación

#### 1.3.1 Validación por Hold-out

**Método actual**: El modelo no implementa validación explícita. Usa todos los datos disponibles para entrenamiento.

**Limitación**: No hay partición train/test, por lo que las métricas de precisión son de entrenamiento, no de validación generalizada.

**Recomendación para mejora**: Implementar validación temporal con rolling window:
- Usar primeros 70% de registros para entrenar
- Validar en últimos 30%
- Calcular métricas de generalización

#### 1.3.2 Métricas de Predicción (Inferencia)

**Nota Importante**: El modelo RandomForest entrenado actualmente **NO se usa en el flujo de inferencia**. La extracción depende únicamente de:
- Tesseract OCR
- Expresiones regulares contextuales
- Reglas de negocio en `auditor_recepcion.py`

**Métricas de extracción OCR**:
- **Confianza por campo**: Calculada por Tesseract
- **Confianza promedio**: Promedio de todas las confianzas
- **Discrepancias detectadas**: Campos donde OCR != formulario
- **Severidad de discrepancias**: CRÍTICA, ALTA, MEDIA, BAJA

**Uso potencial del modelo entrenado**:
El modelo podría integrarse para:
- Predecir si una extracción es confiable antes de mostrarla
- Priorizar revisiones manuales de extracciones de baja confianza
- Ajustar umbrales de confianza dinámicamente
- Sugerir campos que requieren revisión manual

### 1.4 Métricas de Latencia

#### 1.4.1 Latencia de Extracción OCR

**Componentes**:
1. **Carga de imagen**: Lectura del archivo desde disco
2. **Preprocesamiento**: OpenCV (escala grises, denoising, CLAHE, threshold)
3. **Conversión PDF**: Si es PDF, conversión a imágenes (pdf2image)
4. **OCR Tesseract**: Extracción de texto
5. **Parsing regex**: Extracción de campos con expresiones regulares
6. **Cálculo de confianza**: Promedio de confianzas por campo
7. **Guardado en caché**: Almacenamiento en memoria

**Estimación de tiempos**:
- Imagen JPEG/PNG simple: ~500ms - 2s
- PDF de 1 página: ~1s - 3s
- PDF de múltiples páginas: ~2s - 5s por página
- Cuello de botella típico: Tesseract OCR

#### 1.4.2 Latencia de Verificación de Coherencia

**Componentes**:
1. **Recuperación de caché**: Lectura de factura extraída
2. **Comparación de campos**: Comparación campo por campo
3. **Verificación de productos**: Comparación de listas de productos
4. **Cálculo de discrepancias**: Asignación de severidades
5. **Generación de reporte**: Construcción de reporte detallado

**Estimación de tiempos**:
- Sin productos: ~10ms - 50ms
- Con 1-3 productos: ~50ms - 150ms
- Con 4+ productos: ~150ms - 300ms

#### 1.4.3 Latencia de Registro de Feedback

**Componentes**:
1. **Captura de datos PHP**: Lectura de POST fields
2. **Cálculo de errores**: Comparación prediccion vs ground_truth
3. **Escritura CSV**: Append a feedback_dataset.csv
4. **Persistencia**: fsync para garantizar escritura

**Estimación**: <50ms (operación I/O simple)

#### 1.4.4 Latencia de Reentrenamiento

**Componentes**:
1. **Lectura de CSV**: Carga de feedback_dataset.csv
2. **Cálculo de métricas**: Promedios de error por campo
3. **Construcción de features**: Creación de matriz X (n_samples, 6 features)
4. **Entrenamiento RandomForest**: fit(X, y)
5. **Guardado de modelo**: Escritura de modelo_actual.pkl
6. **Guardado de métricas**: Escritura de metricas_reentrenamiento.json

**Estimación de tiempos**:
- 10 registros: ~1s - 2s
- 100 registros: ~2s - 5s
- 1000 registros: ~10s - 20s
- Cuello de botella típico: Entrenamiento RandomForest (pero escala bien)

### 1.5 Análisis de Matriz de Confusión

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

### 1.6 Métricas de Calidad del Sistema

**Estados del sistema** (no expuestos actualmente en API, pero implementados):

| Estado | Significado | Condición |
|--------|-------------|-----------|
| `online` | Servicio disponible | FastAPI ejecutándose |
| `exito` | Extracción exitosa | OCR completó sin errores |
| `discrepancias` | Diferencias detectadas | Al menos un campo con discrepancia |
| `error` | Error crítico | Excepción durante procesamiento |

**Niveles de confianza OCR**:

| Nivel | Rango | Criterio |
|-------|-------|----------|
| Alta | confianza ≥ 0.85 | Extracción muy confiable |
| Media | 0.60 ≤ confianza < 0.85 | Extracción moderadamente confiable |
| Baja | confianza < 0.60 | Extracción poco confiable |

**Niveles de severidad de discrepancias**:

| Severidad | Criterio | Acción recomendada |
|-----------|----------|-------------------|
| CRÍTICA | Número de factura incorrecto | Bloquear registro |
| ALTA | Proveedor o total incorrecto | Alerta fuerte |
| MEDIA | Producto o subtotal incorrecto | Alerta moderada |
| BAJA | Campo secundario incorrecto | Alerta suave |

---

## 2. Plan de Pruebas y Respuestas ante Fallos

### 2.1 Estrategia de Pruebas

#### 2.1.1 Pruebas Unitarias (Pendientes)

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

**Ejecución recomendada**:
```bash
python -m unittest microservicio.tests.test_auditor_recepcion -v
```

#### 2.1.2 Pruebas de Integración

**Prueba 1: Flujo completo JavaScript → FastAPI**
```javascript
// Escenario: Usuario selecciona factura en formulario de recepción
// 1. JavaScript envía imagen a /api/v1/auditar
// 2. FastAPI procesa con auditor_recepcion.py
// 3. FastAPI devuelve datos extraídos
// 4. JavaScript rellena formulario
// Validar:
// - Imagen se envía correctamente
// - Respuesta JSON válida con datos de factura
// - Campos extraídos no vacíos
// - Confianza calculada
```

**Prueba 2: Verificación de coherencia**
```javascript
// Escenario: Usuario modifica datos del formulario y pulsa verificar
// 1. JavaScript envía datos del formulario a /api/v1/verificar
// 2. FastAPI compara con factura extraída
// 3. FastAPI devuelve discrepancias
// 4. JavaScript muestra alertas
// Validar:
// - Discrepancias detectadas correctamente
// - Severidades asignadas apropiadamente
// - Reporte detallado generado
```

**Prueba 3: Registro con feedback**
```php
// Escenario: Usuario registra recepción con microservicio activado
// 1. JavaScript envía ia_prediccion al backend PHP
// 2. PHP registra recepción en BD
// 3. PHP calcula errores y guarda en feedback_dataset.csv
// 4. Próximo reentrenamiento usa estos datos
// Validar:
// - ia_prediccion enviado correctamente
// - Recepción registrada en BD
// - Fila agregada a feedback_dataset.csv
// - Errores calculados correctamente
```

#### 2.1.3 Pruebas de Carga (Stress Testing)

**Herramienta recomendada**: Apache JMeter o Locust

**Escenario 1: Extracciones concurrentes**
- 10 usuarios envían facturas simultáneamente
- Configurar FastAPI con workers=1 (recomendado)
- Validar: Todas las extracciones completan, no hay timeouts

**Escenario 2: Verificaciones concurrentes**
- 100 usuarios verifican coherencia en paralelo
- Validar: Tiempo de respuesta < 500ms por solicitud

**Escenario 3: Registros masivos de feedback**
- 1000 registros de recepción en 1 hora
- Validar: feedback_dataset.csv crece sin corrupción
- Validar: Escritura concurrente no causa conflictos

#### 2.1.4 Pruebas de Seguridad

**Prueba 1: Validación de archivos**
- Enviar archivo no válido (ej: .exe)
- Validar: Rechazado por extensión
- Enviar archivo muy grande (>10MB)
- Validar: Rechazado por tamaño

**Prueba 2: Validación de esquema**
- Enviar JSON malformado en ia_prediccion
- Validar: PHP maneja error, no corrompe CSV
- Enviar datos de formulario inválidos
- Validar: Pydantic rechaza si se usa validación

**Prueba 3: Inyección de datos**
- Enviar nombre de proveedor con caracteres especiales
- Validar: Escapado correctamente en CSV
- Enviar SQL injection en campos
- Validar: No ejecuta comandos SQL

### 2.2 Respuestas ante Fallos

#### 2.2.1 Fallos de Conectividad

**Escenario**: FastAPI no está ejecutándose

**Detección**:
- JavaScript: Error de conexión fetch
- PHP: cURL timeout

**Respuesta JavaScript** (líneas 116-119 de asistente_recepcion.py):
```javascript
error.contingencia = true;
throw new Error('El microservicio respondió con error.');
```

**Tratamiento en UI**:
- Mostrar mensaje: "El servicio de auditoría no está disponible. Usando modo manual."
- Activar modo contingencia
- Deshabilitar botón de verificación IA

**Recomendación de mejora**:
- Implementar circuit breaker en JavaScript
- Reintentar con backoff exponencial
- Registrar intentos en logs

#### 2.2.2 Fallos de OCR

**Escenario 1**: Tesseract no instalado

**Detección**: ImportError de pytesseract

**Respuesta** (líneas 25-31 de auditor_recepcion.py):
```python
PDF_AVAILABLE = False
logger.warning("pdf2image no disponible. Los PDFs no podrán procesarse.")
```

**Tratamiento en UI**:
- Mostrar: "OCR no disponible. Use modo manual."
- Activar modo contingencia

**Escenario 2**: Imagen corrupta o ilegible

**Detección**: OpenCV no puede cargar imagen

**Respuesta** (líneas 173-176 de auditor_recepcion.py):
```python
imagen = cv2.imread(ruta_imagen)
if imagen is None:
    raise ValueError(f"No se pudo cargar: {ruta_imagen}")
```

**Tratamiento en UI**:
- Mostrar: "No se pudo leer la imagen. Verifique el archivo."
- Permitir reintentar con otro archivo

#### 2.2.3 Fallos de Extracción

**Escenario**: OCR no extrae ningún campo

**Detección**: Todos los campos vacíos después de extracción

**Respuesta**: Retorna factura con campos vacíos

**Tratamiento en UI**:
- Mostrar: "No se pudieron extraer datos de la factura. Ingrese manualmente."
- Rellenar formulario con valores vacíos
- Permitir ingreso manual

**Recomendación de mejora**:
- Detectar confianza muy baja (< 0.3)
- Sugerir reescanear la factura
- Ofrecer rotación de imagen

#### 2.2.4 Fallos de Verificación

**Escenario**: Factura no encontrada en caché

**Detección**: ID de factura no en cache_facturas

**Respuesta** (líneas 139-143 de auditor_recepcion.py):
```python
return ResultadoVerificacion(
    exito=False,
    discrepancias=[Discrepancia(
        campo="SISTEMA",
        severidad="CRITICA",
        mensaje="Factura no encontrada en cache"
    )]
)
```

**Tratamiento en UI**:
- Mostrar: "Error: Factura no encontrada. Vuelva a extraer."
- Forzar reextracción de la factura

#### 2.2.5 Fallos de Feedback

**Escenario**: feedback_dataset.csv bloqueado o permisos insuficientes

**Detección**: OSError durante escritura en PHP

**Respuesta actual**: Excepción no capturada

**Recomendación de mejora**:
```php
try {
    $resultado = $k->agregarFeedbackDataset($datosFeedback);
} catch (Exception $e) {
    error_log('Error guardando feedback: ' . $e->getMessage());
    // No fallar el registro, solo loggear el error
}
```

**Tratamiento en UI**:
- No mostrar error al usuario (feedback es secundario)
- Registrar en logs para revisión administrativa

#### 2.2.6 Fallos de Reentrenamiento

**Escenario 1**: Insuficientes datos de feedback

**Detección**: len(df) < 10 en retrain.py

**Respuesta** (líneas 18-21 de retrain.py):
```python
if len(df) < 10:
    print(f"Solo hay {len(df)} registros de feedback. Se requieren al menos 10.")
    return
```

**Tratamiento**:
- No entrenar modelo
- Mostrar mensaje al administrador
- Acumular más registros

**Escenario 2**: Error durante entrenamiento

**Detección**: Excepción durante model.fit()

**Respuesta actual**: Excepción no capturada

**Recomendación de mejora**:
```python
try:
    model.fit(X, y)
except Exception as e:
    logger.error(f"Error entrenando modelo: {e}")
    return False
```

### 2.3 Monitoreo y Alertas

#### 2.3.1 Métricas a Monitorear

**Sistema**:
- CPU de FastAPI (OCR es CPU-bound)
- Memoria de FastAPI (carga de imágenes grandes)
- Latencia de endpoints (p50, p95, p99)
- Tasa de errores HTTP (4xx, 5xx)

**Negocio**:
- Extracciones por día
- Recepciones registradas con microservicio
- Promedio de confianza OCR
- Tasa de discrepancias por campo
- Registros en feedback_dataset.csv

**Datos**:
- Tamaño de feedback_dataset.csv
- Tamaño de modelo_actual.pkl
- Espacio en directorio temp/
- Espacio en factura_cache/

#### 2.3.2 Umbrales de Alerta

| Métrica | Umbral Advertencia | Umbral Crítico | Acción |
|---------|-------------------|----------------|--------|
| Latencia extracción (p95) | > 3s | > 10s | Revisar carga Tesseract |
| Latencia verificación (p95) | > 200ms | > 500ms | Revisar lógica de comparación |
| Tasa de errores HTTP | > 5% | > 15% | Revisar logs, verificar dependencias |
| CPU FastAPI | > 70% | > 90% | Escalar verticalmente |
| Memoria FastAPI | > 1GB | > 2GB | Revisar leaks, escalar |
| Tamaño feedback_dataset.csv | > 10MB | > 50MB | Considerar rotación |
| Confianza OCR promedio | < 0.60 | < 0.40 | Revisar calidad de imágenes |
| Tasa de discrepancias | > 50% | > 80% | Revisar patrones regex |

#### 2.3.3 Logs Implementados

**Nivel INFO**:
- Inicio de extracción OCR
- Extracción completada con confianza
- Verificación de coherencia
- Reentrenamiento completado

**Nivel WARNING**:
- pdf2image no disponible
- Precio unitario inconsistente (recalculado)
- Insuficientes datos para reentrenamiento

**Nivel ERROR**:
- Imagen no encontrada
- Factura no encontrada en caché
- Error crítico procesando archivo

**Recomendación**:
- Agregar logs estructurados (JSON)
- Incluir correlation_id para tracing
- Centralizar logs (ELK stack o similar)

---

## 3. Pruebas de Mantenimiento y Actualización

### 3.1 Estrategia de Mantenimiento

#### 3.1.1 Mantenimiento Rutinario

**Frecuencia**: Semanal

**Tareas**:
1. **Verificar salud del servicio**
   ```bash
   curl http://127.0.0.1:8000/
   ```
   - Esperar: HTTP 200
   - Rechazar: HTTP 500, 503

2. **Revisar tamaño de artefactos**
   ```bash
   ls -lh microservicio/entrenamiento/feedback_dataset.csv
   ls -lh microservicio/modelo/modelo_actual.pkl
   ```
   - Alerta si > 10MB (feedback_dataset.csv)

3. **Verificar cantidad de registros de feedback**
   ```bash
   wc -l microservicio/entrenamiento/feedback_dataset.csv
   ```
   - Recomendado: Mantener al menos 10 registros para reentrenamiento

4. **Limpiar directorio temporal**
   ```bash
   rm -rf microservicio/temp/*
   ```
   - Eliminar archivos temporales antiguos

5. **Limpiar caché de facturas**
   - Si factura_cache/ crece demasiado
   - Eliminar entradas antiguas (> 7 días)

#### 3.1.2 Mantenimiento Mensual

**Tareas**:
1. **Reentrenar modelo**
   ```bash
   cd microservicio
   python retrain.py
   ```
   - Validar: Modelo entrenado exitosamente
   - Validar: Métricas guardadas en metricas_reentrenamiento.json
   - Validar: Precisión del modelo > 0.8

2. **Análisis de calidad de extracciones**
   - Revisar error_promedio por campo
   - Identificar campos con error sistemático alto
   - Ajustar patrones regex si es necesario

3. **Revisión de patrones regex**
   - Verificar PATRONES en auditor_recepcion.py
   - Agregar nuevos patrones si se detectan formatos de factura nuevos
   - Probar con muestras reales

4. **Actualización de Tesseract**
   - Verificar versión instalada
   - Actualizar si hay versión más reciente
   - Probar con imágenes de prueba

#### 3.1.3 Mantenimiento Trimestral

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

### 3.2 Procedimiento de Actualización

#### 3.2.1 Actualización del Código

**Escenario**: Actualizar patrones regex o lógica de extracción

**Pasos**:

1. **Crear rama de desarrollo**
   ```bash
   git checkout -b feature/mejora-ocr
   ```

2. **Implementar cambios en `auditor_recepcion.py`**
   - Modificar PATRONES para nuevos formatos
   - Actualizar lógica de extracción
   - Mantener compatibilidad hacia atrás si es posible

3. **Actualizar pruebas unitarias**
   - Modificar `test_auditor_recepcion.py`
   - Agregar casos de prueba para nuevos patrones
   - Asegurar que pruebas existentes sigan pasando

4. **Ejecutar pruebas**
   ```bash
   python -m unittest microservicio.tests.test_auditor_recepcion -v
   ```

5. **Pruebas de integración**
   - Iniciar FastAPI en entorno de desarrollo
   - Probar extracción con facturas reales
   - Validar que nuevos patrones funcionan
   - Validar que patrones antiguos siguen funcionando

6. **Deploy en producción**
   - Preparar script de rollback
   - Programar ventana de mantenimiento
   - Realizar backup de auditor_recepcion.py
   - Desplegar nuevo código
   - Monitorear errores por 24 horas

7. **Validación post-deploy**
   - Verificar que el servicio responde
   - Ejecutar una extracción de prueba
   - Verificar que la extracción funciona
   - Si hay errores: restaurar backup y rollback

#### 3.2.2 Migración de Datos

**Escenario**: Cambiar estructura de feedback_dataset.csv

**Pasos**:

1. **Implementar script de migración**
   ```python
   # migrate_feedback_v1_to_v2.py
   import pandas as pd
   
   old_df = pd.read_csv('feedback_dataset.csv')
   new_columns = ['timestamp', 'id_recepcion', 'id_factura', ...]
   # Transformar estructura
   new_df = old_df[new_columns]
   new_df.to_csv('feedback_dataset_v2.csv', index=False)
   ```

2. **Probar migración con backup**
   ```bash
   cp feedback_dataset.csv feedback_dataset.csv.backup
   python migrate_feedback_v1_to_v2.py
   # Validar resultado
   ```

3. **Ejecutar en producción**
   - Durante ventana de mantenimiento
   - Con backup disponible
   - Validar resultado

#### 3.2.3 Actualización de Dependencias

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
   python -m unittest microservicio.tests.test_auditor_recepcion -v
   ```

5. **Probar flujo completo**
   - Iniciar FastAPI
   - Ejecutar extracción
   - Ejecutar verificación
   - Validar comportamiento

6. **Deploy en producción**
   - Solo si pruebas pasan
   - Con rollback preparado
   - Monitorear errores

### 3.3 Procedimiento de Recuperación ante Desastres

#### 3.3.1 Pérdida del Modelo (modelo_actual.pkl)

**Síntomas**:
- Script retrain.py falla al cargar modelo
- Archivo modelo_actual.pkl eliminado o corrupto

**Recuperación**:

1. **Si hay backup disponible**
   ```bash
   cp backups/modelo_actual_YYYYMMDD.pkl \
      microservicio/modelo/modelo_actual.pkl
   ```

2. **Si no hay backup disponible**:
   - El sistema sigue funcionando (modelo no se usa en extracción actual)
   - Ejecutar retrain.py para generar nuevo modelo
   - Requiere al menos 10 registros en feedback_dataset.csv

3. **Preventivo**:
   - Configurar backup automático semanal
   - Almacenar backups en ubicación externa
   - Implementar detección temprana de corrupción

#### 3.3.2 Pérdida de Feedback (feedback_dataset.csv)

**Síntomas**:
- Archivo eliminado o corrupto
- Reentrenamiento no puede ejecutarse

**Impacto**:
- No se puede reentrenar el modelo
- Sistema sigue funcionando para extracciones
- Nuevas recepciones no generan feedback

**Recuperación**:

1. **Restaurar desde backup**
   ```bash
   cp backups/feedback_dataset_YYYYMMDD.csv \
      microservicio/entrenamiento/feedback_dataset.csv
   ```

2. **Si no hay backup**:
   - Sistema continúa funcionando para extracciones
   - Nuevas recepciones generarán nuevo feedback
   - Acumular 10+ registros antes de reentrenar

3. **Preventivo**:
   - Rotar archivo mensualmente
   - Archivar versiones antiguas
   - Considerar base de datos para feedback crítico

#### 3.3.3 Corrupción de Base de Datos

**Síntomas**:
- Consultas de recepciones fallan
- Datos inconsistentes

**Recuperación**:

1. **Identificar corrupción**
   ```sql
   CHECK TABLE tbl_recepcion;
   CHECK TABLE tbl_recepcion_detalle;
   ```

2. **Restaurar desde backup MariaDB**
   - Usar backup diario/horario
   - Aplicar logs binarios para punto de recuperación

3. **Si no hay backup**:
   - Contactar administrador de base de datos
   - Evaluar opciones de reparación
   - Considerar reconstrucción desde facturas físicas

#### 3.3.4 Compromiso de Seguridad (Token filtrado)

**Nota**: El sistema actual de auditor_recepcion **no usa token de autenticación** como el de analítica predictiva. Esta sección es para referencia futura si se implementa.

**Síntomas**:
- Solicitudes no autorizadas al endpoint
- Comportamiento anómalo en logs

**Recuperación**:

1. **Implementar autenticación si no existe**
   - Agregar validación de token en api_recepcion.py
   - Usar patrón similar a analítica predictiva

2. **Rotar token inmediatamente**
   ```powershell
   python -c "import secrets; print(secrets.token_urlsafe(32))"
   ```

3. **Investigar incidente**
   - Revisar logs de acceso FastAPI
   - Identificar IPs sospechosas
   - Verificar no hay otros compromisos

### 3.4 Pruebas de Regresión

#### 3.4.1 Suite de Pruebas de Regresión

**Objetivo**: Asegurar que actualizaciones no rompen funcionalidad existente

**Implementación recomendada**:

```python
# tests/test_regression_auditor.py
import unittest
from microservicio.modelo import auditor_recepcion as auditor

class RegressionTest(unittest.TestCase):
    def test_backward_compatibility_old_patterns(self):
        """Patrones antiguos deben seguir funcionando"""
        # Usar fixture de factura con formato antiguo
        result = auditor.extraer_desde_imagen(fixture_old_format)
        self.assertIsNotNone(result.numero_factura)
        self.assertIsNotNone(result.nombre_proveedor)

    def test_predictions_stable_for_same_input(self):
        """Misma imagen debe producir misma extracción"""
        result1 = auditor.extraer_desde_imagen(test_image)
        result2 = auditor.extraer_desde_imagen(test_image)
        self.assertEqual(result1.numero_factura, result2.numero_factura)

    def test_api_schema_backward_compatible(self):
        """Cambios en schema no deben romper clientes JavaScript"""
        # Enviar payload viejo, validar respuesta
        old_payload = {"file": test_image}
        response = client.post("/api/v1/auditar", files=old_payload)
        self.assertEqual(response.status_code, 200)
```

#### 3.4.2 Pruebas de Canary

**Objetivo**: Validar actualización en subset de usuarios antes de deploy completo

**Implementación**:

1. **Deploy canary en nuevo puerto**
   ```bash
   uvicorn controlador.api_recepcion:app --host 127.0.0.1 --port 8001
   ```

2. **Configurar proxy JavaScript para direcionar 10% del tráfico**
   ```javascript
   if (Math.random() < 0.1) {
       apiUrl = 'http://127.0.0.1:8001';
   } else {
       apiUrl = 'http://127.0.0.1:8000';
   }
   ```

3. **Monitorear métricas**
   - Latencia de extracción
   - Tasa de errores
   - Confianza OCR promedio

4. **Si métricas son aceptables**
   - Expandir a 50% del tráfico
   - Finalmente 100%

5. **Si hay problemas**
   - Revertir a versión anterior
   - Investigar causa
   - Corregir y reintentar
