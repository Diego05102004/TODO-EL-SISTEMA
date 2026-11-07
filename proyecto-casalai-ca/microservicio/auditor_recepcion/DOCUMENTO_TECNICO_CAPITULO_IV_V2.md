# Documento Técnico: Microservicio de Auditoría de Recepción (Versión 2)
## Capítulo IV - Análisis de Arquitectura y Métricas (Actualizado)

---

## Resumen de Cambios desde Versión 1

Esta versión actualizada del documento técnico refleja las siguientes mejoras implementadas:

1. **Integración del modelo ML en el flujo de extracción**: El modelo RandomForest entrenado ahora se usa para predecir la confiabilidad de las extracciones OCR en tiempo real.
2. **Autenticación de API**: Implementación de token de autenticación para proteger los endpoints del microservicio.
3. **Alertas de confiabilidad en el frontend**: El JavaScript muestra alertas al usuario basándose en la confiabilidad predicha por el modelo ML.
4. **Simplificación del entrenamiento**: El modelo ahora se entrena usando únicamente la confianza OCR como feature, permitiendo predicciones durante la extracción.

---

## 1. Reporte Técnico de Métricas de Entrenamiento y Validación

### 1.1 Arquitectura del Modelo (Actualizada)

**Algoritmo**: RandomForestClassifier para clasificación de confiabilidad de extracción

**Características del Modelo (Versión 2)**:
- **Tipo de modelo**: Clasificación binaria (Random Forest)
- **Objetivo**: Predecir si una extracción OCR es confiable (error_total < 0.3) basándose únicamente en la confianza OCR
- **Features de entrada**: 1 característica (confianza_ocr_promedio)
- **Mínimo de muestras**: 10 registros en feedback_dataset.csv
- **Algoritmo base**: Tesseract OCR + Regex contextual
- **Integración**: Modelo cargado y usado durante la extracción en tiempo real

**Ecuación del Modelo (Versión 2)**:
```
target = 1 si error_total < 0.3 (extracción confiable)
target = 0 si error_total >= 0.3 (extracción no confiable)

feature = confianza_ocr_promedio (0.0 a 1.0)
prediccion = modelo.predict_proba([[confianza_ocr_promedio]])
confiabilidad_ml = prediccion[1] (probabilidad de ser confiable)
```

**Features de entrada (Versión 2)**:
- `confianza_ocr_promedio`: Confianza promedio de Tesseract (0.0 a 1.0)
  - **Cambio**: Antes se usaban 6 features (errores por campo + confianza)
  - **Justificación**: Los errores no están disponibles durante la extracción (no hay ground_truth)
  - **Beneficio**: Permite predicción en tiempo real durante la extracción

### 1.2 Métricas de Entrenamiento (Actualizada)

#### 1.2.1 Métricas Calculadas por el Modelo

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

#### 1.2.2 Métricas Disponibles en la API

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

### 1.3 Métricas de Validación (Actualizada)

#### 1.3.1 Validación por Hold-out

**Método actual**: El modelo no implementa validación explícita. Usa todos los datos disponibles para entrenamiento.

**Limitación**: No hay partición train/test, por lo que las métricas de precisión son de entrenamiento, no de validación generalizada.

**Recomendación para mejora**: Implementar validación temporal con rolling window:
- Usar primeros 70% de registros para entrenar
- Validar en últimos 30%
- Calcular métricas de generalización

#### 1.3.2 Métricas de Predicción (Inferencia) - NUEVO

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

### 1.4 Métricas de Latencia (Actualizada)

#### 1.4.1 Latencia de Extracción OCR (Actualizada)

**Componentes**:
1. **Carga de imagen**: Lectura del archivo desde disco
2. **Preprocesamiento**: OpenCV (escala grises, denoising, CLAHE, threshold)
3. **Conversión PDF**: Si es PDF, conversión a imágenes (pdf2image)
4. **OCR Tesseract**: Extracción de texto
5. **Parsing regex**: Extracción de campos con expresiones regulares
6. **Cálculo de confianza**: Promedio de confianzas por campo
7. **Predicción ML**: **NUEVO** - Predicción de confiabilidad usando RandomForest
8. **Guardado en caché**: Almacenamiento en memoria

**Estimación de tiempos (Actualizada)**:
- Imagen JPEG/PNG simple: ~500ms - 2s (+ ~5ms por predicción ML)
- PDF de 1 página: ~1s - 3s (+ ~5ms por predicción ML)
- PDF de múltiples páginas: ~2s - 5s por página (+ ~5ms por predicción ML)
- Cuello de botella típico: Tesseract OCR
- **Impacto de ML**: Despreciable (< 10ms por predicción)

#### 1.4.2 Latencia de Verificación de Coherencia

Sin cambios respecto a la versión anterior.

#### 1.4.3 Latencia de Registro de Feedback

Sin cambios respecto a la versión anterior.

#### 1.4.4 Latencia de Reentrenamiento (Actualizada)

**Componentes**:
1. **Lectura de CSV**: Carga de feedback_dataset.csv
2. **Cálculo de métricas**: Promedios de error por campo
3. **Construcción de features**: **SIMPLIFICADO** - Solo confianza OCR (1 feature)
4. **Entrenamiento RandomForest**: fit(X, y) con 1 feature
5. **Guardado de modelo**: Escritura de modelo_actual.pkl
6. **Guardado de métricas**: Escritura de metricas_reentrenamiento.json

**Estimación de tiempos (Actualizada)**:
- 10 registros: ~0.5s - 1s (reducido por simplificación de features)
- 100 registros: ~1s - 3s (reducido)
- 1000 registros: ~5s - 10s (reducido)
- Cuello de botella típico: Entrenamiento RandomForest (pero escala bien)
- **Mejora**: Menos features = entrenamiento más rápido

### 1.5 Análisis de Matriz de Confusión (Actualizada)

**Nota Importante**: El modelo actual es de **clasificación binaria** (RandomForestClassifier), no de regresión. La matriz de confusión es aplicable.

**Métricas de clasificación relevantes (Actualizadas)**:

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

### 1.6 Métricas de Calidad del Sistema (Actualizada)

**Estados del sistema** (no expuestos actualmente en API, pero implementados):

| Estado | Significado | Condición |
|--------|-------------|-----------|
| `online` | Servicio disponible | FastAPI ejecutándose |
| `exito` | Extracción exitosa | OCR completó sin errores |
| `discrepancias` | Diferencias detectadas | Al menos un campo con discrepancia |
| `error` | Error crítico | Excepción durante procesamiento |
| `modelo_ml_cargado` | Modelo ML disponible | modelo_actual.pkl existe y se cargó | NUEVO |
| `autenticacion_requerida` | Token necesario | AUDITOR_RECEPCION_TOKEN configurado | NUEVO |

**Niveles de confianza OCR**:

| Nivel | Rango | Criterio |
|-------|-------|----------|
| Alta | confianza ≥ 0.85 | Extracción muy confiable |
| Media | 0.60 ≤ confianza < 0.85 | Extracción moderadamente confiable |
| Baja | confianza < 0.60 | Extracción poco confiable |

**Niveles de confiabilidad ML (NUEVO)**:

| Nivel | Rango | Acción en UI |
|-------|-------|-------------|
| Alta | confiabilidad_ml ≥ 0.70 | Sin alerta |
| Media | 0.50 ≤ confiabilidad_ml < 0.70 | Alerta informativa |
| Baja | confiabilidad_ml < 0.50 | Alerta de advertencia |

**Niveles de severidad de discrepancias**:

| Severidad | Criterio | Acción recomendada |
|-----------|----------|-------------------|
| CRÍTICA | Número de factura incorrecto | Bloquear registro |
| ALTA | Proveedor o total incorrecto | Alerta fuerte |
| MEDIA | Producto o subtotal incorrecto | Alerta moderada |
| BAJA | Campo secundario incorrecto | Alerta suave |

---

## 2. Plan de Pruebas y Respuestas ante Fallos (Actualizado)

### 2.1 Estrategia de Pruebas (Actualizada)

#### 2.1.1 Pruebas Unitarias (Actualizadas)

**Archivo recomendado**: `microservicio/tests/test_auditor_recepcion.py`

**Casos de prueba recomendados (Actualizados)**:

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

10. **test_carga_modelo_ml** - NUEVO
    - Objetivo: Verificar que el modelo ML se carga correctamente al iniciar
    - Valida: self.modelo_ml no es None si modelo_actual.pkl existe
    - Cobertura: Inicialización de AuditorRecepcion

11. **test_prediccion_confiabilidad** - NUEVO
    - Objetivo: Verificar que el modelo predice confiabilidad correctamente
    - Valida: _predecir_confiabilidad() retorna valor entre 0 y 1
    - Cobertura: Método _predecir_confiabilidad()

12. **test_prediccion_sin_modelo** - NUEVO
    - Objetivo: Verificar comportamiento cuando no hay modelo cargado
    - Valida: _predecir_confiabilidad() retorna 0.0 si modelo_ml es None
    - Cobertura: Manejo de ausencia de modelo

13. **test_validacion_token** - NUEVO
    - Objetivo: Verificar que la autenticación funciona correctamente
    - Valida: Token válido permite acceso, token inválido rechaza
    - Cobertura: Función validar_token_auditor()

**Ejecución recomendada**:
```bash
python -m unittest microservicio.tests.test_auditor_recepcion -v
```

#### 2.1.2 Pruebas de Integración (Actualizadas)

**Prueba 1: Flujo completo JavaScript → FastAPI con autenticación** - ACTUALIZADA
```javascript
// Escenario: Usuario selecciona factura en formulario de recepción
// 1. JavaScript envía imagen a /api/v1/auditar con token de autenticación
// 2. FastAPI valida token
// 3. FastAPI procesa con auditor_recepcion.py
// 4. FastAPI devuelve datos extraídos + confiabilidad_ml
// 5. JavaScript rellena formulario y muestra alertas de confiabilidad
// Validar:
// - Token enviado en header X-Auditor-Token
// - Token válido permite acceso
// - Token inválido retorna 403
// - Respuesta JSON válida con datos de factura
// - Campos extraídos no vacíos
// - Confianza calculada
// - Confiabilidad ML incluida en respuesta
// - Alertas mostradas según confiabilidad ML
```

**Prueba 2: Verificación de coherencia**
Sin cambios respecto a la versión anterior.

**Prueba 3: Registro con feedback**
Sin cambios respecto a la versión anterior.

#### 2.1.3 Pruebas de Carga (Stress Testing)

Sin cambios significativos respecto a la versión anterior, pero considerar el impacto adicional de la predicción ML (despreciable).

#### 2.1.4 Pruebas de Seguridad (Actualizadas)

**Prueba 1: Validación de archivos**
Sin cambios respecto a la versión anterior.

**Prueba 2: Validación de esquema**
Sin cambios respecto a la versión anterior.

**Prueba 3: Inyección de datos**
Sin cambios respecto a la versión anterior.

**Prueba 4: Validación de autenticación** - NUEVA
- Enviar solicitud sin token de autenticación
- Validar: Rechazada con 403
- Enviar solicitud con token incorrecto
- Validar: Rechazada con 403
- Enviar solicitud con token correcto
- Validar: Aceptada

### 2.2 Respuestas ante Fallos (Actualizadas)

#### 2.2.1 Fallos de Conectividad

Sin cambios significativos respecto a la versión anterior.

#### 2.2.2 Fallos de OCR

Sin cambios significativos respecto a la versión anterior.

#### 2.2.3 Fallos de Extracción

Sin cambios significativos respecto a la versión anterior.

#### 2.2.4 Fallos de Verificación

Sin cambios significativos respecto a la versión anterior.

#### 2.2.5 Fallos de Feedback

Sin cambios significativos respecto a la versión anterior.

#### 2.2.6 Fallos de Reentrenamiento

Sin cambios significativos respecto a la versión anterior.

#### 2.2.7 Fallos de Autenticación - NUEVO

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

#### 2.2.8 Fallos del Modelo ML - NUEVO

**Escenario 1**: modelo_actual.pkl no existe

**Detección**: Archivo no encontrado en auditor_recepcion.py

**Respuesta** (líneas 100-109 de auditor_recepcion.py):
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

**Respuesta** (líneas 194-217 de auditor_recepcion.py):
```python
try:
    features = [[confianza_ocr]]
    prediccion = self.modelo_ml.predict_proba(features)[0]
    return prediccion[1] if len(prediccion) > 1 else prediccion[0]
except Exception as e:
    logger.warning(f"Error prediciendo confiabilidad: {e}")
    return 0.0
```

**Tratamiento en UI**:
- Si confiabilidad_ml = 0.0, no se muestra alerta
- El sistema continúa funcionando
- Se registra el error en logs

### 2.3 Monitoreo y Alertas (Actualizadas)

#### 2.3.1 Métricas a Monitorear (Actualizadas)

**Sistema**:
- CPU de FastAPI (OCR es CPU-bound)
- Memoria de FastAPI (carga de imágenes grandes)
- Latencia de endpoints (p50, p95, p99)
- Tasa de errores HTTP (4xx, 5xx)
- **Tasa de errores de autenticación (403)** - NUEVO

**Negocio**:
- Extracciones por día
- Recepciones registradas con microservicio
- Promedio de confianza OCR
- **Promedio de confiabilidad ML** - NUEVO
- Tasa de discrepancias por campo
- Registros en feedback_dataset.csv

**Datos**:
- Tamaño de feedback_dataset.csv
- Tamaño de modelo_actual.pkl
- Espacio en directorio temp/
- Espacio en factura_cache/

#### 2.3.2 Umbrales de Alerta (Actualizadas)

| Métrica | Umbral Advertencia | Umbral Crítico | Acción |
|---------|-------------------|----------------|--------|
| Latencia extracción (p95) | > 3s | > 10s | Revisar carga Tesseract |
| Latencia verificación (p95) | > 200ms | > 500ms | Revisar lógica de comparación |
| Tasa de errores HTTP | > 5% | > 15% | Revisar logs, verificar dependencias |
| **Tasa de errores 403** | **> 10%** | **> 25%** | **Verificar configuración de token** | NUEVO |
| CPU FastAPI | > 70% | > 90% | Escalar verticalmente |
| Memoria FastAPI | > 1GB | > 2GB | Revisar leaks, escalar |
| Tamaño feedback_dataset.csv | > 10MB | > 50MB | Considerar rotación |
| Confianza OCR promedio | < 0.60 | < 0.40 | Revisar calidad de imágenes |
| **Confiabilidad ML promedio** | **< 0.50** | **< 0.30** | **Revisar calidad de entrenamiento** | NUEVO |
| Tasa de discrepancias | > 50% | > 80% | Revisar patrones regex |

#### 2.3.3 Logs Implementados (Actualizados)

**Nivel INFO**:
- Inicio de extracción OCR
- Extracción completada con confianza
- **Modelo ML cargado exitosamente** - NUEVO
- **Confiabilidad ML predicha** - NUEVO
- Verificación de coherencia
- Reentrenamiento completado

**Nivel WARNING**:
- pdf2image no disponible
- Precio unitario inconsistente (recalculado)
- Insuficientes datos para reentrenamiento
- **No se pudo cargar el modelo ML** - NUEVO
- **Error prediciendo confiabilidad** - NUEVO

**Nivel ERROR**:
- Imagen no encontrada
- Factura no encontrada en caché
- Error crítico procesando archivo

**Recomendación**:
- Agregar logs estructurados (JSON)
- Incluir correlation_id para tracing
- Centralizar logs (ELK stack o similar)
- **Registrar intentos de autenticación fallidos** - NUEVO

---

## 3. Pruebas de Mantenimiento y Actualización (Actualizado)

### 3.1 Estrategia de Mantenimiento (Actualizada)

#### 3.1.1 Mantenimiento Rutinario (Actualizado)

**Frecuencia**: Semanal

**Tareas**:
1. **Verificar salud del servicio**
   ```bash
   curl http://127.0.0.1:8000/
   ```
   - Esperar: HTTP 200
   - Rechazar: HTTP 500, 503

2. **Verificar configuración de autenticación** - NUEVO
   ```bash
   # Verificar que AUDITOR_RECEPCION_TOKEN esté configurado
   echo $AUDITOR_RECEPCION_TOKEN
   # Debe tener al menos 32 caracteres
   ```

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
   - Recomendado: Mantener al menos 10 registros para reentrenamiento

5. **Limpiar directorio temporal**
   ```bash
   rm -rf microservicio/temp/*
   ```
   - Eliminar archivos temporales antiguos

6. **Limpiar caché de facturas**
   - Si factura_cache/ crece demasiado
   - Eliminar entradas antiguas (> 7 días)

#### 3.1.2 Mantenimiento Mensual (Actualizado)

**Tareas**:
1. **Reentrenar modelo**
   ```bash
   cd microservicio
   python retrain.py
   ```
   - Validar: Modelo entrenado exitosamente
   - Validar: Métricas guardadas en metricas_reentrenamiento.json
   - Validar: Precisión del modelo > 0.8
   - **Validar: Modelo usa solo 1 feature (confianza OCR)** - NUEVO

2. **Análisis de calidad de extracciones**
   - Revisar error_promedio por campo
   - Identificar campos con error sistemático alto
   - **Revisar confiabilidad ML promedio** - NUEVO
   - Ajustar patrones regex si es necesario

3. **Revisión de patrones regex**
   - Verificar PATRONES en auditor_recepcion.py
   - Agregar nuevos patrones si se detectan formatos de factura nuevos
   - Probar con muestras reales

4. **Actualización de Tesseract**
   - Verificar versión instalada
   - Actualizar si hay versión más reciente
   - Probar con imágenes de prueba

5. **Rotación de token de autenticación** - NUEVO
   - Considerar rotar AUDITOR_RECEPCION_TOKEN periódicamente
   - Actualizar en entorno de PHP/Apache
   - Actualizar en entorno de FastAPI
   - Reiniciar Apache después del cambio

#### 3.1.3 Mantenimiento Trimestral (Actualizado)

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

5. **Auditoría de seguridad** - NUEVO
   - Revisar logs de autenticación
   - Verificar intentos fallidos
   - Revisar configuración de token
   - Validar que el servicio no esté expuesto externamente

### 3.2 Procedimiento de Actualización (Actualizado)

#### 3.2.1 Actualización del Código (Actualizada)

**Escenario**: Actualizar patrones regex o lógica de extracción

**Pasos**:

1. **Crear rama de desarrollo**
   ```bash
   git checkout -b feature/mejora-ocr-v2
   ```

2. **Implementar cambios en `auditor_recepcion.py`**
   - Modificar PATRONES para nuevos formatos
   - Actualizar lógica de extracción
   - Mantener compatibilidad hacia atrás si es posible

3. **Actualizar pruebas unitarias**
   - Modificar `test_auditor_recepcion.py`
   - Agregar casos de prueba para nuevos patrones
   - **Agregar pruebas para predicción ML** - NUEVO
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
   - **Validar que autenticación funciona** - NUEVO

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
   - **Verificar que autenticación funciona** - NUEVO
   - Si hay errores: restaurar backup y rollback

#### 3.2.2 Migración de Datos (Actualizada)

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

#### 3.2.3 Actualización de Dependencias (Actualizada)

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
   - **Validar autenticación** - NUEVO

6. **Deploy en producción**
   - Solo si pruebas pasan
   - Con rollback preparado
   - Monitorear errores

### 3.3 Procedimiento de Recuperación ante Desastres (Actualizado)

#### 3.3.1 Pérdida del Modelo (modelo_actual.pkl)

Sin cambios significativos respecto a la versión anterior, pero ahora el sistema puede funcionar sin el modelo (degradación graceful).

#### 3.3.2 Pérdida de Feedback (feedback_dataset.csv)

Sin cambios significativos respecto a la versión anterior.

#### 3.3.3 Corrupción de Base de Datos

Sin cambios significativos respecto a la versión anterior.

#### 3.3.4 Compromiso de Seguridad (Token filtrado) - NUEVO

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

### 3.4 Pruebas de Regresión (Actualizadas)

#### 3.4.1 Suite de Pruebas de Regresión (Actualizada)

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

    def test_ml_prediction_consistent(self):  # NUEVO
        """Predicción ML debe ser consistente para misma confianza"""
        if auditor.modelo_ml:
            pred1 = auditor._predecir_confiabilidad(0.85)
            pred2 = auditor._predecir_confiabilidad(0.85)
            self.assertEqual(pred1, pred2)

    def test_authentication_required(self):  # NUEVO
        """Endpoints deben requerir autenticación"""
        response = client.post("/api/v1/auditar", files={"file": test_image})
        # Sin token, debe retornar 403
        self.assertEqual(response.status_code, 403)

    def test_graceful_degradation_without_model(self):  # NUEVO
        """Sistema debe funcionar sin modelo ML"""
        # Guardar modelo actual
        model_backup = auditor.modelo_ml
        # Eliminar modelo
        auditor.modelo_ml = None
        # Intentar extracción
        result = auditor.extraer_desde_imagen(test_image)
        # Debe funcionar sin errores
        self.assertIsNotNone(result.numero_factura)
        # Restaurar modelo
        auditor.modelo_ml = model_backup
```

#### 3.4.2 Pruebas de Canary

Sin cambios significativos respecto a la versión anterior.

---

## 4. Comparación con Versión 1

### 4.1 Cambios en Arquitectura

| Aspecto | Versión 1 | Versión 2 | Cambio |
|---------|-----------|-----------|--------|
| Features de entrenamiento | 6 (errores + confianza) | 1 (solo confianza) | Simplificado |
| Modelo en inferencia | No usado | Sí usado | Integrado |
| Autenticación API | No implementada | Implementada | Agregado |
| Alertas de confiabilidad | No | Sí | Agregado |
| Degradación sin modelo | No definido | Graceful | Mejorado |

### 4.2 Cambios en Flujo de Datos

| Paso | Versión 1 | Versión 2 |
|------|-----------|-----------|
| Extracción OCR | OCR → Regex → Caché | OCR → Regex → Predicción ML → Caché |
| Respuesta API | Datos extraídos | Datos extraídos + confiabilidad_ml |
| Frontend | Muestra datos | Muestra datos + alertas de confiabilidad |
| Autenticación | No requerida | Token requerido |

### 4.3 Cambios en Archivos

| Archivo | Cambios en Versión 2 |
|---------|---------------------|
| `retrain.py` | Simplificado a 1 feature (confianza OCR) |
| `auditor_recepcion.py` | Carga modelo, implementa predicción |
| `api_recepcion.py` | Agrega autenticación, devuelve confiabilidad_ml |
| `asistente_recepcion.js` | Envía token, muestra alertas de confiabilidad |
| `modelo_actual.pkl` | Ahora se usa en inferencia |

### 4.4 Mejoras en Seguridad

| Aspecto | Versión 1 | Versión 2 |
|---------|-----------|-----------|
| Autenticación | No | Token HMAC |
| Exposición externa | Riesgo alto | Riesgo mitigado |
| Validación de acceso | No | Sí |

### 4.5 Mejoras en Experiencia de Usuario

| Aspecto | Versión 1 | Versión 2 |
|---------|-----------|-----------|
| Alertas de calidad | No | Sí (basadas en ML) |
| Feedback de confiabilidad | No | Sí (probabilidad ML) |
| Guía de revisión | No | Sí (alertas contextuales) |

---

## 5. Próximos Pasos Recomendados

### 5.1 Mejoras Técnicas

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

### 5.2 Mejoras de Negocio

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
   - Mejorar patrones regex para campos problemáticos

### 5.3 Mejoras de Seguridad

1. **Implementar rate limiting**
   - Limitar solicitudes por IP
   - Prevenir abuso del servicio

2. **Implementar auditoría de accesos**
   - Registrar todas las solicitudes con timestamp
   - Registrar usuario/IP/token
   - Analizar patrones de uso anómalos

3. **Implementar rotación automática de tokens**
   - Configurar rotación periódica
   - Notificar administradores antes de rotación
