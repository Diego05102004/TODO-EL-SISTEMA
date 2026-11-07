# Documento Técnico: Microservicio de Análisis Predictivo
## Capítulo IV - Análisis de Arquitectura y Métricas

---

## 1. Reporte Técnico de Métricas de Entrenamiento y Validación

### 1.1 Arquitectura del Modelo

**Algoritmo**: Ridge Regression con autoregresión de 4 lags (ridge-lag4-v1)

**Características del Modelo**:
- **Tipo de modelo**: Regresión Ridge con regularización L2
- **Ventana de entrenamiento**: 24 semanas completas (6 meses)
- **Lags temporales**: 4 semanas anteriores para predecir la siguiente
- **Agregación temporal**: Semanal (ventas diarias sumadas por semana)
- **Regularización**: Matriz diagonal [0.0, 0.25, 0.25, 0.25, 0.25]

**Ecuación del Modelo**:
```
y_pred = β₀ + β₁·y_{t-4} + β₂·y_{t-3} + β₃·y_{t-2} + β₄·y_{t-1}
```

Donde:
- y_pred: Ventas estimadas para la semana siguiente
- y_{t-n}: Ventas reales de las n semanas anteriores
- β: Coeficientes aprendidos (5 coeficientes: intercepto + 4 lags)

### 1.2 Métricas de Entrenamiento

#### 1.2.1 Métricas Calculadas por el Modelo

**Error Residual (RMSE - Root Mean Square Error)**:
- **Fórmula**: `error = sqrt(mean(residuals²))`
- **Implementación**: Línea 63 de `analitica_predictiva.py`
- **Significado**: Desviación estándar de los errores de predicción durante el entrenamiento
- **Unidad**: Unidades de producto por semana
- **Uso**: Para construir intervalos de confianza (±1.96 × error)

**Muestras de Entrenamiento**:
- **Requisito mínimo**: 6 semanas con ventas dentro de la ventana de 24 semanas
- **Filas de entrenamiento**: `len(target)` (típicamente 20 filas, 24 semanas - 4 lags)
- **Historial de semanas**: 24 semanas completas

**R² (Coeficiente de Determinación)**:
- No se calcula explícitamente en el código actual
- Puede derivarse de: `R² = 1 - (SS_res / SS_tot)`

#### 1.2.2 Métricas Disponibles en la API

El modelo expone las siguientes métricas en el artefacto JSON (`demanda.json`):

```json
{
  "version": "ridge-lag4-v1",
  "trained_at": "2026-10-21T...",
  "source": "facturas_pagadas",
  "models": {
    "producto:123": {
      "coefficients": [0.5, 0.2, 0.15, 0.1, 0.05],
      "error": 2.34,
      "training_rows": 20,
      "history_weeks": 24
    }
  }
}
```

**Descripción de campos**:
- `coefficients`: Vector de 5 coeficientes [β₀, β₁, β₂, β₃, β₄]
- `error`: RMSE del entrenamiento (unidades/semana)
- `training_rows`: Número de muestras usadas (20 = 24 semanas - 4 lags)
- `history_weeks`: Ventana temporal completa (24 semanas)

### 1.3 Métricas de Validación

#### 1.3.1 Validación por Hold-out

**Método actual**: El modelo no implementa validación explícita. Usa todos los datos disponibles.

**Limitación**: No hay partición train/test, por lo que las métricas de error son de entrenamiento, no de validación generalizada.

**Recomendación para mejora**: Implementar validación temporal con rolling window:
- Usar primeras 20 semanas para entrenar
- Validar en últimas 4 semanas
- Calcular métricas de generalización

#### 1.3.2 Métricas de Predicción (Inferencia)

**Venta Proyectada**:
- **Cálculo**: Suma de predicciones diarias en el horizonte
- **Fórmula**: `venta_proyectada = Σ predicciones_diarias`
- **Unidad**: Unidades de producto
- **Archivo**: Líneas 393-399 de `analitica_predictiva.py`

**Días Estimados de Agotamiento**:
- **Cálculo**: Día acumulado donde la demanda proyectada ≥ stock actual
- **Condiciones**: Solo para productos individuales con stock conocido
- **Caso especial**: 0 días si stock=0 y demanda>0
- **Archivo**: Líneas 401-409 de `analitica_predictiva.py`

**Rotación Proyectada**:
- **Fórmula**: `rotación = demanda_proyectada / inventario_promedio`
- **Inventario promedio**: `(stock_inicial + stock_final) / 2`
- **Stock final**: `max(0, stock_inicial - demanda_proyectada)`
- **Archivo**: Líneas 410-412 de `analitica_predictiva.py`

**Punto de Reorden**:
- **Valor**: stock mínimo configurado por el usuario
- **No calculado**: Se lee directamente del inventario
- **Archivo**: Líneas 413-414 de `analitica_predictiva.py`

**Cantidad Sugerida de Compra**:
- **Fórmula**: `max(0, ceil(demanda + stock_minimo - stock_actual))`
- **Lógica**: Demanda horizonte + buffer mínimo - stock actual
- **Nota**: No descuenta órdenes de compra abiertas
- **Archivo**: Líneas 415-418 de `analitica_predictiva.py`

### 1.4 Métricas de Latencia

#### 1.4.1 Latencia de Entrenamiento

**Componentes**:
1. **Extracción de datos**: Consulta a MariaDB (PHP)
2. **Serialización JSON**: PHP → FastAPI
3. **Validación de esquema**: Pydantic en FastAPI
4. **Preprocesamiento**: Agrupación semanal, construcción de features
5. **Cálculo Ridge**: `np.linalg.solve()` (O(n³) pero n=5, muy rápido)
6. **Persistencia**: Escritura atómica de JSON
7. **Respuesta HTTP**: FastAPI → PHP

**Timeout configurado**: 30 segundos (línea 24 de `AnaliticaPredictivaProxy.php`)

**Estimación de tiempos**:
- 100 productos con historial: ~2-5 segundos
- 1000 productos con historial: ~10-20 segundos
- Cuello de botella típico: Consulta SQL de ventas históricas

#### 1.4.2 Latencia de Inferencia (Dashboard)

**Componentes**:
1. **Extracción de datos**: Consulta a MariaDB (últimos 365 días)
2. **Serialización JSON**: PHP → FastAPI
3. **Validación de esquema**: Pydantic
4. **Carga del modelo**: Lectura de `demanda.json`
5. **Proyección**: Multiplicación matriz-vector (O(n) por día proyectado)
6. **Cálculo de KPIs**: Aritmética simple
7. **Respuesta HTTP**: FastAPI → PHP

**Timeout configurado**: 12 segundos (línea 19 de `AnaliticaPredictivaProxy.php`)

**Estimación de tiempos**:
- Producto individual, horizonte 30 días: ~100-300ms
- Categoría con 50 productos, horizonte 90 días: ~500-1000ms
- Cuello de botella típico: Transferencia de datos (JSON grande)

#### 1.4.3 Latencia de Retroalimentación

**Componentes**:
1. **Validación de esquema**: Pydantic
2. **Escritura atómica**: Append a `feedback_analitica.jsonl`
3. **fsync**: Garantizar persistencia inmediata

**Timeout configurado**: 8 segundos (línea 29 de `AnaliticaPredictivaProxy.php`)

**Estimación**: <50ms (operación I/O simple)

### 1.5 Análisis de Matriz de Confusión

**Nota Importante**: El modelo actual es de **regresión**, no de clasificación. Por lo tanto, la matriz de confusión tradicional no es aplicable directamente.

**Métricas de regresión relevantes**:

| Métrica | Cálculo Actual | Estado |
|---------|----------------|--------|
| RMSE (Root Mean Square Error) | ✅ Calculado en línea 63 | Disponible |
| MAE (Mean Absolute Error) | ❌ No calculado | Pendiente |
| MAPE (Mean Absolute Percentage Error) | ❌ No calculado | Pendiente |
| R² (Coeficiente de Determinación) | ❌ No calculado | Pendiente |

**Para convertir a clasificación (si se requiere)**:
Podría binarizar las predicciones en categorías como:
- "Stock suficiente" vs "Stock insuficiente"
- "Alta rotación" vs "Baja rotación"
- "Comprar" vs "No comprar"

En ese caso, la matriz de confusión sería:

| | Predicción: Stock Suficiente | Predicción: Stock Insuficiente |
|--|------------------------------|--------------------------------|
| **Real: Stock Suficiente** | Verdaderos Positivos (VP) | Falsos Positivos (FP) |
| **Real: Stock Insuficiente** | Falsos Negativos (FN) | Verdaderos Negativos (VN) |

**Métricas derivadas**:
- **Precisión**: VP / (VP + FP)
- **Recall (Sensibilidad)**: VP / (VP + FN)
- **F1-Score**: 2 × (Precisión × Recall) / (Precisión + Recall)

**Estado actual**: No implementado, ya que el modelo es de regresión pura.

### 1.6 Métricas de Calidad del Sistema

**Estados del sistema** (expuestos en la API):

| Estado | Significado | Condición |
|--------|-------------|-----------|
| `disponible` | Modelo entrenado y listo | saved_model ≠ None |
| `modelo_pendiente` | Hay datos pero sin modelo | saved_model = None, histórico existe |
| `insuficiente_historial` | Menos de 6 semanas con ventas | `_fit_weekly_model()` retorna None |
| `sin_ventas` | No hay ventas en el periodo | `daily_total` vacío |
| `sin_datos` | Producto/categoría no existe | `selected_ids` vacío |

**Niveles de confianza** (para recomendaciones):

| Nivel | Rango | Criterio |
|-------|-------|----------|
| Alta | confianza ≥ 0.7 | Error bajo respecto a la media |
| Media | 0.4 ≤ confianza < 0.7 | Error moderado |
| Baja | confianza < 0.4 | Error alto |

**Cálculo de confianza** (líneas 443-446):
```python
confidence = max(0.0, min(0.99, 1.0 - error / (mean_weekly_sales + 1.0)))
```

---

## 2. Plan de Pruebas y Respuestas ante Fallos

### 2.1 Estrategia de Pruebas

#### 2.1.1 Pruebas Unitarias (Existente)

**Archivo**: `microservicio/tests/test_analitica_predictiva.py`

**Casos de prueba implementados**:

1. **test_trains_and_returns_nonnegative_forecast_with_interval**
   - Objetivo: Verificar que el entrenamiento produce predicciones no negativas con intervalos
   - Valida: Predicciones ≥ 0, límite_inferior ≤ valor ≤ límite_superior
   - Cobertura: Producto individual y categoría

2. **test_missing_inventory_data_stays_unavailable**
   - Objetivo: Verificar que datos faltantes de inventario no generan KPIs falsos
   - Valida: KPIs = None cuando stock o stock_minimo = None
   - Principio: "Mejor no disponible que dato falso"

3. **test_requires_training_and_reports_insufficient_history**
   - Objetivo: Verificar que sin entrenamiento se reporta estado correcto
   - Valida: Estado = "insuficiente_historial" sin historial suficiente

4. **test_empty_sales_report_no_sales_instead_of_zero_history**
   - Objetivo: Distinguir entre "sin ventas" y "historial cero"
   - Valida: Estado = "sin_ventas" cuando no hay datos

5. **test_unknown_product_reports_no_matching_active_products**
   - Objetivo: Manejo de producto inexistente
   - Valida: Estado = "sin_datos" para ID inválido

6. **test_insufficient_retraining_does_not_overwrite_a_trained_model**
   - Objetivo: Protección contra sobreescritura accidental
   - Valida: Artefacto preservado si reentrenamiento falla

7. **test_feedback_is_saved_and_aggregated_for_recommendations**
   - Objetivo: Verificar persistencia y agregación de feedback
   - Valida: Promedio de estrellas por tipo de recomendación

8. **test_invoice_pairs_are_recommended_from_repeated_baskets**
   - Objetivo: Verificar detección de combos en facturas
   - Valida: Parejas de productos comprados juntos ≥ 2 veces

**Ejecución**:
```bash
python -m unittest microservicio.tests.test_analitica_predictiva -v
```

#### 2.1.2 Pruebas de Integración (Recomendadas)

**Prueba 1: Flujo completo PHP → FastAPI**
```php
// Escenario: Usuario pulsa "Entrenar modelo local"
// 1. PHP consulta ventas de 365 días
// 2. PHP envía a FastAPI /api/v1/analitica/entrenar
// 3. FastAPI entrena y devuelve respuesta
// 4. PHP muestra resultado al usuario
// Validar: 
// - Token compartido funciona
// - Timeout de 30s es suficiente
// - Response JSON válido
// - Archivo demanda.json creado
```

**Prueba 2: Consulta de dashboard**
```php
// Escenario: Usuario selecciona producto y pulsa "Analizar"
// 1. PHP consulta productos y ventas
// 2. PHP envía a FastAPI /api/v1/analitica/dashboard
// 3. FastAPI devuelve predicciones y KPIs
// 4. JavaScript actualiza gráfico y tabla
// Validar:
// - Gráfico muestra histórico + predicción
// - Banda de error visible
// - KPIs calculados correctamente
// - Recomendaciones priorizadas
```

**Prueba 3: Retroalimentación**
```php
// Escenario: Usuario valora recomendación con 5 estrellas
// 1. JavaScript envía POST a PHP
// 2. PHP reenvía a FastAPI /api/v1/analitica/feedback
// 3. FastAPI guarda en feedback_analitica.jsonl
// 4. Próxima consulta incorpora promedio
// Validar:
// - Valoración persiste en archivo
// - Promedio se recalcula
// - Prioridad de recomendación ajustada
```

#### 2.1.3 Pruebas de Carga (Stress Testing)

**Herramienta recomendada**: Apache JMeter o Locust

**Escenario 1: Entrenamiento concurrente**
- 10 usuarios solicitan entrenamiento simultáneo
- Configurar FastAPI con workers=1 (recomendado en guía)
- Validar: Solo un entrenamiento ejecuta, otros esperan

**Escenario 2: Consultas de dashboard concurrentes**
- 100 usuarios consultan dashboard en paralelo
- Horizonte: 90 días (máximo)
- Validar: Tiempo de respuesta < 3s por solicitud

**Escenario 3: Retroalimentación masiva**
- 1000 valoraciones en 1 minuto
- Validar: Archivo feedback_analitica.jsonl crece sin corrupción
- Validar: fsync no bloquea excesivamente

#### 2.1.4 Pruebas de Seguridad

**Prueba 1: Autenticación de token**
- Solicitar sin token X-Analitica-Token
- Validar: HTTP 403 Forbidden
- Solicitar con token incorrecto
- Validar: HTTP 403 Forbidden
- Solicitar con token < 32 caracteres
- Validar: HTTP 503 Service Unavailable

**Prueba 2: Validación de esquema**
- Enviar producto_id = -1
- Validar: Pydantic rechaza (gt=0)
- Enviar frecuencia = "hora" (inválido)
- Validar: Pydantic rechaza (Literal["diaria", "semanal", "mensual"])
- Enviar horizonte = 45 (inválido)
- Validar: Pydantic rechaza (Literal[15, 30, 60, 90])

**Prueba 3: Inyección de datos**
- Enviar JSON malformado
- Validar: HTTP 422 Unprocessable Entity
- Enviar ventas = 200,000 registros (límite)
- Validar: Rechazado por max_length=100000

### 2.2 Respuestas ante Fallos

#### 2.2.1 Fallos de Conectividad

**Escenario**: FastAPI no está ejecutándose

**Detección**:
- PHP cURL timeout (3s conexión + 12s dashboard / 30s entrenamiento)
- Excepción: `RuntimeException('No se pudo conectar con el microservicio de analítica')`

**Respuesta PHP** (líneas 60-62 de `AnaliticaPredictivaProxy.php`):
```php
throw new RuntimeException('No se pudo conectar con el microservicio de analítica: ' . $errorCurl);
```

**Tratamiento en UI**:
- Mostrar mensaje: "El servicio de analítica no está disponible en este momento"
- Deshabilitar botones "Analizar" y "Entrenar"
- No recargar página, mantener estado actual

**Recomendación de mejora**:
- Implementar circuit breaker en PHP
- Reintentar con backoff exponencial (1s, 2s, 4s, 8s)
- Registrar intentos en bitácora

#### 2.2.2 Fallos de Base de Datos

**Escenario**: MariaDB no responde durante extracción de datos

**Detección**:
- PDOException en PHP
- Timeout de consulta (> 30s)

**Respuesta PHP**:
```php
try {
    $productos = Productos::obtenerDatosAnaliticaPredictiva(...);
} catch (PDOException $e) {
    error_log("Error BD analítica: " . $e->getMessage());
    return ['status' => 'error', 'message' => 'Error al obtener datos históricos'];
}
```

**Tratamiento en UI**:
- Mostrar: "No se pudieron cargar los datos históricos. Intente más tarde."
- Opción: Reintentar consulta

#### 2.2.3 Fallos de Entrenamiento

**Escenario 1**: Historial insuficiente

**Detección**: Función `_fit_weekly_model()` retorna None (línea 50)

**Respuesta API** (líneas 119-126 de `analitica_predictiva.py`):
```python
return {
    "estado": "insuficiente_historial",
    "version": "ridge-lag4-v1",
    "trained_at": None,
    "modelos_entrenados": 0,
    "productos_sin_historial_suficiente": non_trainable,
    "minimo_semanas": _MIN_TRAINING_WEEKS,
}
```

**Tratamiento en UI**:
- Mostrar: "No se pudo entrenar: insuficiente historial de ventas"
- Mostrar: "Productos sin historial suficiente: X"
- Mostrar: "Mínimo requerido: 24 semanas con al menos 6 semanas con ventas"
- Preservar modelo anterior (no sobrescribir)

**Escenario 2**: Error durante escritura del modelo

**Detección**: OSError durante `os.replace()` (línea 137)

**Respuesta**: Excepción no capturada, FastAPI retorna HTTP 500

**Recomendación de mejora**:
```python
try:
    os.replace(temporary_path, MODEL_PATH)
except OSError as e:
    logging.error("Error persistiendo modelo: %s", e)
    return {
        "estado": "error_persistencia",
        "detalle": "No se pudo guardar el modelo entrenado"
    }
```

#### 2.2.4 Fallos de Inferencia

**Escenario 1**: Modelo corrupto (JSON inválido)

**Detección**: `json.JSONDecodeError` en `_load_artifact()` (línea 153)

**Respuesta** (líneas 154-155):
```python
raise RuntimeError("El modelo guardado está dañado; vuelva a entrenarlo.")
```

**Tratamiento en UI**:
- Mostrar: "El modelo de analítica está dañado. Por favor, entrene el modelo nuevamente."
- Habilitar botón "Entrenar modelo local"

**Escenario 2**: Formato de modelo inválido

**Detección**: Validación de estructura (líneas 156-157)

**Respuesta**:
```python
raise RuntimeError("El formato del modelo guardado no es válido.")
```

**Tratamiento**: Similar a modelo corrupto

#### 2.2.5 Fallos de Retroalimentación

**Escenario**: Archivo de feedback bloqueado o permisos insuficientes

**Detección**: OSError durante escritura (línea 531)

**Respuesta actual**: Excepción no capturada

**Recomendación de mejora**:
```python
try:
    with _feedback_lock, FEEDBACK_PATH.open("a", encoding="utf-8") as feedback_file:
        feedback_file.write(json.dumps(record, ensure_ascii=False) + "\n")
        feedback_file.flush()
        os.fsync(feedback_file.fileno())
except OSError as e:
    logging.error("Error guardando feedback: %s", e)
    return {"guardado": False, "error": "No se pudo guardar la valoración"}
```

**Tratamiento en UI**:
- Mostrar: "No se pudo guardar la valoración. Intente nuevamente."
- No afectar funcionalidad principal (solo prioridad futura)

#### 2.2.6 Fallos de Autenticación

**Escenario**: Token no configurado o desincronizado

**Detección**: Validación en `validar_token_analitica()` (líneas 21-26)

**Respuesta FastAPI**:
- HTTP 503 si token < 32 caracteres
- HTTP 403 si token no coincide

**Tratamiento en UI**:
- HTTP 503: "Servicio de analítica no configurado correctamente. Contacte al administrador."
- HTTP 403: "Error de autenticación del servicio. Contacte al administrador."

**Resolución**:
- Administrador debe sincronizar `ANALITICA_PREDICTIVA_TOKEN` en:
  - Entorno de PHP/Apache
  - Entorno de FastAPI
- Reiniciar Apache después del cambio

### 2.3 Monitoreo y Alertas

#### 2.3.1 Métricas a Monitorear

**Sistema**:
- CPU de FastAPI (entrenamiento es CPU-bound)
- Memoria de FastAPI (carga de JSON grande)
- Latencia de endpoints (p50, p95, p99)
- Tasa de errores HTTP (4xx, 5xx)

**Negocio**:
- Modelos entrenados por día
- Consultas de dashboard por día
- Productos con modelo disponible vs total
- Promedio de confianza de recomendaciones
- Tasa de valoraciones de usuarios

**Datos**:
- Consultas SQL a facturas (duración)
- Tamaño de JSON transferido (PHP ↔ FastAPI)
- Tamaño de archivo demanda.json
- Tamaño de archivo feedback_analitica.jsonl

#### 2.3.2 Umbrales de Alerta

| Métrica | Umbral Advertencia | Umbral Crítico | Acción |
|---------|-------------------|----------------|--------|
| Latencia dashboard (p95) | > 2s | > 5s | Revisar carga, optimizar queries |
| Latencia entrenamiento | > 20s | > 60s | Revisar historial, considerar workers |
| Tasa de errores HTTP | > 5% | > 15% | Revisar logs, verificar dependencias |
| CPU FastAPI | > 70% | > 90% | Escalar verticalmente |
| Memoria FastAPI | > 1GB | > 2GB | Revisar leaks, escalar |
| Tamaño demanda.json | > 10MB | > 50MB | Considerar partición |
| Tamaño feedback.jsonl | > 100MB | > 500MB | Rotar archivo |

#### 2.3.3 Logs Implementados

**Nivel INFO**:
- Fecha de entrenamiento
- Cantidad de modelos entrenados
- Productos sin historial suficiente

**Nivel WARNING**:
- Feedback inválido en línea (línea 286)
- Producto sin ventas diarias (línea 436)

**Nivel ERROR**:
- Modelo corrupto (línea 154)
- Formato inválido (línea 157)

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
   curl -H "X-Analitica-Token: $TOKEN" http://127.0.0.1:8000/api/v1/analitica/dashboard
   ```
   - Esperar: HTTP 200 o 422 (por validación de esquema)
   - Rechazar: HTTP 500, 503, 403

2. **Revisar tamaño de artefactos**
   ```bash
   ls -lh microservicio/analitica_predictiva/modelos/demanda.json
   ls -lh microservicio/logs_metricas/feedback_analitica.jsonl
   ```
   - Alerta si > 10MB (demanda.json) o > 100MB (feedback.jsonl)

3. **Verificar última fecha de entrenamiento**
   ```bash
   jq '.trained_at' microservicio/analitica_predictiva/modelos/demanda.json
   ```
   - Recomendado: Reentrenar al menos mensualmente

4. **Rotar logs de feedback**
   - Si feedback_analitica.jsonl > 100MB:
     - Renombrar a feedback_analitica_YYYY-MM-DD.jsonl
     - Comprimir con gzip
     - Archivar en almacenamiento externo
     - Crear nuevo archivo vacío

5. **Backup del modelo**
   ```bash
   cp microservicio/analitica_predictiva/modelos/demanda.json \
      backups/demanda_$(date +%Y%m%d).json
   ```
   - Retener últimos 12 backups (anual)

#### 3.1.2 Mantenimiento Mensual

**Tareas**:
1. **Reentrenar modelo completo**
   - Desde UI: "Gestión de Productos" → "Entrenar modelo local"
   - Validar: `modelos_entrenados` aumenta o se mantiene
   - Validar: `trained_at` se actualiza

2. **Análisis de calidad de predicciones**
   - Comparar predicciones de hace 30 días con ventas reales
   - Calcular MAPE manualmente si hay interés
   - Identificar productos con error sistemático alto

3. **Revisión de permisos**
   - Verificar usuarios con permiso de entrenamiento
   - Remover accesos innecesarios

4. **Auditoría de seguridad**
   - Verificar que token no esté comprometido
   - Rotar token si hay sospecha
   - Verificar que FastAPI escucha solo en 127.0.0.1

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

2. **Optimización de consultas SQL**
   - Revisar índices en tbl_factura_detalle
   - Verificar query de `Productos::obtenerDatosAnaliticaPredictiva`
   - Analizar plan de ejecución con EXPLAIN

3. **Limpieza de backups antiguos**
   - Eliminar backups > 12 meses
   - Archivar en almacenamiento externo si se requiere retención

### 3.2 Procedimiento de Actualización

#### 3.2.1 Actualización del Código

**Escenario**: Actualizar versión de Ridge o cambiar algoritmo

**Pasos**:

1. **Crear rama de desarrollo**
   ```bash
   git checkout -b feature/nuevo-modelo
   ```

2. **Implementar cambios en `analitica_predictiva.py`**
   - Modificar `_fit_weekly_model()` para nuevo algoritmo
   - Actualizar `version` en el artefacto (ej: "ridge-lag4-v2")
   - Mantener compatibilidad hacia atrás si es posible

3. **Actualizar pruebas unitarias**
   - Modificar `test_analitica_predictiva.py`
   - Agregar casos de prueba para nuevo comportamiento
   - Asegurar que pruebas existentes sigan pasando

4. **Ejecutar pruebas**
   ```bash
   python -m unittest microservicio.tests.test_analitica_predictiva -v
   ```

5. **Pruebas de integración**
   - Iniciar FastAPI en entorno de desarrollo
   - Ejecutar flujo completo desde UI
   - Validar que el modelo se entrena correctamente
   - Validar que predicciones son razonables

6. **Deploy en staging**
   - Copiar código a servidor de staging
   - Reentrenar modelo con datos de staging
   - Validar comportamiento

7. **Deploy en producción**
   - Preparar script de rollback
   - Programar ventana de mantenimiento (fuera de horario pico)
   - Realizar backup de demanda.json actual
   - Desplegar nuevo código
   - Reentrenar modelo inmediatamente
   - Monitorear errores por 24 horas

8. **Validación post-deploy**
   - Verificar que el servicio responde
   - Ejecutar una consulta de dashboard
   - Verificar que el modelo nuevo funciona
   - Si hay errores: restaurar backup y rollback

#### 3.2.2 Migración de Datos

**Escenario**: Cambiar estructura del artefacto JSON

**Pasos**:

1. **Implementar script de migración**
   ```python
   # migrate_v1_to_v2.py
   import json
   from pathlib import Path

   old_artifact = json.loads(Path("demanda.json").read_text())
   new_artifact = {
       "version": "ridge-lag4-v2",
       "trained_at": old_artifact["trained_at"],
       "source": old_artifact["source"],
       "models": {},
       # ... nueva estructura
   }

   for key, model in old_artifact["models"].items():
       new_artifact["models"][key] = {
           # transformar estructura
       }

   Path("demanda.json").write_text(json.dumps(new_artifact))
   ```

2. **Probar migración con backup**
   ```bash
   cp demanda.json demanda.json.backup
   python migrate_v1_to_v2.py
   # validar resultado
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
   python -m unittest microservicio.tests.test_analitica_predictiva -v
   ```

5. **Probar flujo completo**
   - Iniciar FastAPI
   - Ejecutar entrenamiento
   - Ejecutar dashboard
   - Validar comportamiento

6. **Deploy en producción**
   - Solo si pruebas pasan
   - Con rollback preparado
   - Monitorear errores

### 3.3 Procedimiento de Recuperación ante Desastres

#### 3.3.1 Pérdida del Modelo (demanda.json)

**Síntomas**:
- Error: "El modelo guardado está dañado"
- Archivo demanda.json eliminado o corrupto

**Recuperación**:

1. **Restaurar desde backup más reciente**
   ```bash
   cp backups/demanda_YYYYMMDD.json \
      microservicio/analitica_predictiva/modelos/demanda.json
   ```

2. **Si no hay backup disponible**:
   - El sistema seguirá funcionando pero sin predicciones
   - Estado será "modelo_pendiente" o "insuficiente_historial"
   - Usuario debe reentrenar desde UI
   - El reentrenamiento generará nuevo modelo

3. **Preventivo**:
   - Configurar backup automático diario
   - Almacenar backups en ubicación externa
   - Implementar detección temprana de corrupción

#### 3.3.2 Pérdida de Feedback (feedback_analitica.jsonl)

**Síntomas**:
- Archivo eliminado o corrupto
- Valoraciones históricas perdidas

**Impacto**:
- Prioridad de recomendaciones vuelve a baseline (sin ajuste por feedback)
- Sistema sigue funcionando, pero con menos personalización

**Recuperación**:

1. **Restaurar desde backup**
   ```bash
   cp backups/feedback_analitica_YYYYMMDD.jsonl \
      microservicio/logs_metricas/feedback_analitica.jsonl
   ```

2. **Si no hay backup**:
   - Sistema continúa funcionando
   - Nuevas valoraciones se guardan normalmente
   - Pérdida es acumulativa pero no crítica

3. **Preventivo**:
   - Rotar archivo mensualmente
   - Archivar versiones antiguas
   - Considerar base de datos para feedback crítico

#### 3.3.3 Corrupción de Base de Datos

**Síntomas**:
- Consultas de ventas fallan
- Datos inconsistentes

**Recuperación**:

1. **Identificar corrupción**
   ```sql
   CHECK TABLE tbl_factura_detalle;
   CHECK TABLE tbl_factura;
   ```

2. **Restaurar desde backup MariaDB**
   - Usar backup diario/horario
   - Aplicar logs binarios para punto de recuperación

3. **Si no hay backup**:
   - Contactar administrador de base de datos
   - Evaluar opciones de reparación
   - Considerar reconstrucción desde facturas físicas

#### 3.3.4 Compromiso de Seguridad (Token filtrado)

**Síntomas**:
- Solicitudes no autorizadas al endpoint
- Comportamiento anómalo en logs

**Recuperación**:

1. **Rotar token inmediatamente**
   ```powershell
   # Generar nuevo token
   python -c "import secrets; print(secrets.token_urlsafe(32))"
   ```

2. **Actualizar en ambos entornos**
   - Entorno PHP/Apache: `SetEnv ANALITICA_PREDICTIVA_TOKEN=nuevo-token`
   - Entorno FastAPI: `$env:ANALITICA_PREDICTIVA_TOKEN = "nuevo-token"`
   - Reiniciar Apache

3. **Investigar incidente**
   - Revisar logs de acceso FastAPI
   - Identificar IPs sospechosas
   - Verificar no hay otros compromisos

4. **Preventivo**:
   - Usar variables de entorno, no archivos
   - No incluir token en Git
   - Rotar token periódicamente (trimestral)

### 3.4 Pruebas de Regresión

#### 3.4.1 Suite de Pruebas de Regresión

**Objetivo**: Asegurar que actualizaciones no rompen funcionalidad existente

**Implementación recomendada**:

```python
# tests/test_regression.py
import unittest
from microservicio.modelo import analitica_predictiva as analytics

class RegressionTest(unittest.TestCase):
    def test_backward_compatibility_v1_model(self):
        """Cargar modelo v1 debe funcionar en código actual"""
        # Usar fixture de modelo v1 guardado
        old_model = json.loads(fixture_v1)
        result = analytics.build_dashboard_with_loaded_model(old_model, ...)
        self.assertEqual(result["calidad"]["estado"], "disponible")

    def test_predictions_stable_for_same_input(self):
        """Mismos datos deben producir mismas predicciones"""
        # Reproducibilidad determinista
        result1 = analytics.build_dashboard(test_payload)
        result2 = analytics.build_dashboard(test_payload)
        self.assertEqual(result1["prediccion"], result2["prediccion"])

    def test_api_schema_backward_compatible(self):
        """Cambios en schema no deben romper clientes PHP"""
        # Enviar payload viejo, validar respuesta
        old_payload = {"producto_id": 1, "frecuencia": "diaria", ...}
        response = client.post("/api/v1/analitica/dashboard", json=old_payload)
        self.assertEqual(response.status_code, 200)
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
       $proxy = new AnaliticaPredictivaProxy('http://127.0.0.1:8001');
   } else {
       $proxy = new AnaliticaPredictivaProxy('http://127.0.0.1:8000');
   }
   ```

3. **Monitorear errores en canary**
   - Si tasa de errores > baseline: rollback inmediato
   - Si tasa de errores similar: expandir a 50%
   - Si estable: migrar 100% y desactivar puerto 8000

### 3.5 Documentación de Cambios

#### 3.5.1 Changelog

**Formato recomendado** (Keep a Changelog):

```markdown
# Changelog - Microservicio Analítica Predictiva

## [Unreleased]

### Added
- Nueva métrica MAPE en respuesta de entrenamiento
- Endpoint de health check

### Changed
- Timeout de entrenamiento aumentado de 30s a 60s
- Versión del modelo actualizada a ridge-lag4-v2

### Fixed
- Bug: División por cero en cálculo de rotación cuando stock=0
- Bug: Feedback con estrellas inválidas causaba crash

### Deprecated
- Campo `legacy_metric` en respuesta (eliminar en v2.0)

## [1.1.0] - 2026-10-21

### Added
- Sistema de retroalimentación de usuarios
- Recomendaciones de combos basadas en canasta de compras

### Changed
- Mejorada precisión con ajuste de regularización

## [1.0.0] - 2026-09-15

### Added
- Primer release oficial
- Modelo Ridge con 4 lags
- Endpoints de dashboard y entrenamiento
```

#### 3.5.2 Registro de Incidentes

**Plantilla**:

```markdown
# Incidente #001: Caída del servicio durante reentrenamiento

**Fecha**: 2026-10-20 14:30
**Duración**: 15 minutos
**Severidad**: Alta

**Descripción**:
Durante reentrenamiento programado, FastAPI consumió 100% CPU y dejó de responder a peticiones de dashboard.

**Causa raíz**:
- Entrenamiento concurrente no estaba limitado a 1 worker
- 5 usuarios iniciaron entrenamiento simultáneo
- Cada worker procesó 500 productos, saturando CPU

**Resolución**:
- Configurar uvicorn con --workers 1
- Agregar mutex en código para prevenir entrenamiento concurrente

**Acciones preventivas**:
- Actualizar guía de entrenamiento con workers=1
- Agregar alerta de CPU > 80%
- Implementar queue de entrenamiento

**Lessons learned**:
- Single worker es esencial para escritura atómica de demanda.json
- Monitoreo de CPU debe alertar antes de saturación total
```

---

## 4. Métricas de Éxito y KPIs

### 4.1 KPIs Técnicos

| KPI | Métrica | Objetivo | Actual |
|-----|---------|----------|--------|
| Disponibilidad del servicio | % tiempo online | ≥ 99.5% | Pendiente |
| Latencia dashboard (p95) | Segundos | < 2s | Pendiente |
| Latencia entrenamiento | Segundos | < 30s | Pendiente |
| Tasa de errores HTTP | % requests | < 1% | Pendiente |
| Cobertura de pruebas | % líneas | ≥ 80% | Pendiente |
| Modelos entrenados | Total | ≥ 80% de productos | Pendiente |

### 4.2 KPIs de Negocio

| KPI | Métrica | Objetivo | Actual |
|-----|---------|----------|--------|
| Adopción de recomendaciones | % usuarios que usan | ≥ 60% | Pendiente |
| Precisión de stock bajo | % predicciones correctas | ≥ 70% | Pendiente |
| Reducción de stockouts | % reducción vs baseline | ≥ 20% | Pendiente |
| Satisfacción de usuarios | Promedio estrellas | ≥ 4.0 | Pendiente |
| Frecuencia de reentrenamiento | Días entre entrenamientos | ≤ 30 | Pendiente |

### 4.3 Monitoreo Continuo

**Dashboard recomendado** (Grafana o similar):

- Panel 1: Salud del servicio (CPU, memoria, latencia, errores)
- Panel 2: Métricas de negocio (consultas, entrenamientos, valoraciones)
- Panel 3: Calidad del modelo (cobertura, confianza promedio)
- Panel 4: Alertas y incidentes recientes

---

## 5. Conclusiones y Recomendaciones

### 5.1 Fortalezas del Diseño Actual

1. **Arquitectura simple y mantenible**: Ridge regression con 4 lags es interpretable y computacionalmente eficiente
2. **Seguridad por diseño**: Token compartido, validación de esquema, FastAPI en loopback
3. **Resiliencia**: No sobrescribe modelo si reentrenamiento falla, maneja datos faltantes correctamente
4. **Extensibilidad**: Sistema de feedback permite mejora continua basada en usuario
5. **Pruebas automatizadas**: Suite de pruebas unitarias cubre casos principales

### 5.2 Áreas de Mejora Identificadas

1. **Validación de modelo**: No hay partición train/test, métricas son solo de entrenamiento
2. **Métricas de clasificación**: No hay matriz de confusión, F1-score (modelo es regresión)
3. **Circuit breaker**: PHP reintenta sin backoff, no hay protección contra cascada de fallos
4. **Monitoreo**: No hay métricas estructuradas ni centralización de logs
5. **Feedback en BD**: Archivo JSONL no escala bien, debería migrar a tabla de BD
6. **Pruebas de carga**: No hay pruebas de estrés documentadas
7. **Documentación de errores**: Falta registro estructurado de incidentes

### 5.3 Roadmap de Mejoras

**Corto plazo (1-2 meses)**:
- [ ] Implementar validación temporal con rolling window
- [ ] Agregar métricas MAE y MAPE al entrenamiento
- [ ] Implementar circuit breaker en PHP con backoff
- [ ] Agregar health check endpoint en FastAPI
- [ ] Centralizar logs en formato JSON

**Mediano plazo (3-6 meses)**:
- [ ] Migrar feedback a tabla en MariaDB
- [ ] Implementar pruebas de carga con JMeter
- [ ] Configurar monitoreo con Grafana + Prometheus
- [ ] Implementar canary deployments
- [ ] Agregar métricas de clasificación (binarizar predicciones)

**Largo plazo (6-12 meses)**:
- [ ] Evaluar modelos más avanzados (Prophet, LSTM)
- [ ] Implementar A/B testing de algoritmos
- [ ] Sistema de alertas automáticas
- [ ] API pública para integraciones externas
- [ ] Análisis de efectividad comercial de recomendaciones

### 5.4 Recomendaciones de Operación

1. **Reentrenar mensualmente**: Al menos una vez al mes para incorporar nuevas ventas
2. **Monitorear cobertura**: Asegurar que ≥ 80% de productos tengan modelo disponible
3. **Revisar confianza**: Alertar si confianza promedio < 0.5
4. **Backup diario**: Automatizar backup de demanda.json
5. **Rotar token trimestralmente**: Por seguridad, aunque el servicio sea interno
6. **Documentar incidentes**: Cada fallo debe tener post-mortem documentado
7. **Revisar logs semanalmente**: Buscar patrones anómalos antes de que se conviertan en incidentes

---

## Anexos

### Anexo A: Arquitectura del Sistema

```
┌─────────────────┐
│   Navegador     │
│   (Usuario)     │
└────────┬────────┘
         │ HTTP
         ↓
┌─────────────────┐
│   PHP/Apache    │
│ producto.php    │
│ Productos.php   │
└────────┬────────┘
         │ cURL (local)
         ↓
┌─────────────────┐
│   FastAPI       │
│ api_analitica.py│
│ 127.0.0.1:8000  │
└────────┬────────┘
         │
    ┌────┴────┐
    ↓         ↓
┌──────┐  ┌──────────┐
│Modelo│  │MariaDB   │
│Ridge │  │(Ventas)  │
└──────┘  └──────────┘
```

### Anexo B: Esquema de Datos

**demanda.json**:
```json
{
  "version": "ridge-lag4-v1",
  "trained_at": "ISO-8601 timestamp",
  "source": "facturas_pagadas",
  "models": {
    "producto:{id}": {
      "coefficients": [β₀, β₁, β₂, β₃, β₄],
      "error": RMSE,
      "training_rows": N,
      "history_weeks": 24
    },
    "categoria:{id}": { ... }
  }
}
```

**feedback_analitica.jsonl** (una línea por registro):
```json
{"recomendacion_id": "combo:1,2", "tipo": "combo", "estrellas": 5, "usuario_id": 7, "fecha": "ISO-8601"}
```

### Anexo C: Comandos Útiles

**Entrenar modelo**:
```bash
cd microservicio
uvicorn controlador.api_recepcion:app --host 127.0.0.1 --port 8000
# Luego desde UI: "Entrenar modelo local"
```

**Ejecutar pruebas**:
```bash
python -m unittest microservicio.tests.test_analitica_predictiva -v
```

**Generar historial sintético**:
```bash
php microservicio/analitica_predictiva/seed_historial_local.php --apply
```

**Ver modelo actual**:
```bash
cat microservicio/analitica_predictiva/modelos/demanda.json | jq
```

**Ver feedback**:
```bash
cat microservicio/logs_metricas/feedback_analitica.jsonl
```

---

**Documento preparado para**: Capítulo IV - Proyecto Universitario
**Fecha**: 2026-10-21
**Versión**: 1.0
**Autor**: Ingeniero de Software (Devin AI)
