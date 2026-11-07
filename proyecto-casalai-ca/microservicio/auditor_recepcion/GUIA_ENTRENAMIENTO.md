# Guía de entrenamiento del microservicio de auditoría de recepción

## Objetivo

Esta guía explica cómo el microservicio de auditoría de recepción extrae datos de facturas mediante OCR, cómo se genera el dataset de feedback para aprendizaje, y cómo reentrenar el modelo para mejorar extracciones futuras. El proceso es completamente local y no envía datos de facturas a servicios externos.

## Requisitos previos

- PHP 7.4 o superior con `curl` y `pdo_mysql`.
- Python 3.10 o superior y las dependencias de `microservicio/requirements.txt`.
- FastAPI con acceso local a `127.0.0.1:8000`.
- Tesseract OCR instalado (Windows: `C:\Program Files\Tesseract-OCR\tesseract.exe`).
- Poppler (para procesamiento de PDFs).
- Un usuario autenticado con permiso de registro de Recepciones.
- El microservicio debe estar ejecutándose para usar la extracción OCR.

## 1. Configurar y ejecutar FastAPI

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

## 2. Entender el recorrido de los datos

Sigue este orden al leer el código:

1. **Interfaz de usuario:** `Vista/VistaNew/recepcion.php` contiene el formulario de recepción, el selector de archivo de factura, y el botón de registro.
2. **Solicitud del navegador:** la función JavaScript `AsistenteRecepcionIA` en `microservicio/javascript/asistente_recepcion.js` envía la imagen al microservicio.
3. **Controlador API:** `microservicio/controlador/api_recepcion.py` recibe el archivo en el endpoint `/api/v1/auditar`.
4. **Procesamiento OCR:** `microservicio/modelo/auditor_recepcion.py` ejecuta Tesseract OCR para extraer texto de la imagen.
5. **Extracción de campos:** El mismo módulo usa expresiones regulares para extraer número de factura, proveedor, productos, montos, etc.
6. **Verificación de coherencia:** Compara los datos extraídos con los datos del formulario del usuario.
7. **Registro de recepción:** `Modelo/Controlador/recepcion.php` registra la recepción en la base de datos MariaDB.
8. **Guardado de feedback:** `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php` calcula errores entre predicción y ground truth, y guarda en `microservicio/entrenamiento/feedback_dataset.csv`.
9. **Reentrenamiento:** `microservicio/retrain.py` lee el feedback_dataset.csv, entrena un modelo RandomForest, y guarda en `microservicio/modelo/modelo_actual.pkl`.

## 3. Flujo de extracción OCR

### 3.1 Selección de factura por el usuario

1. El usuario accede al módulo de Recepciones.
2. Selecciona un archivo de factura (PDF, JPG, PNG).
3. El JavaScript `asistente_recepcion.js` envía la imagen al microservicio.

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

5. **Guardado en caché**:
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
  "subtotal_factura": 5340.0,
  "porcentaje_iva": 16.0,
  "monto_iva": 854.4,
  "total_factura": 6194.4,
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
  "confianza_promedio": 0.94,
  "mensaje": "Extracción completada"
}
```

### 3.4 Relleno del formulario

El JavaScript recibe los datos y:
- Rellena automáticamente los campos del formulario
- Muestra un panel con los datos extraídos
- Calcula subtotales y totales
- Permite al usuario corregir manualmente si es necesario

## 4. Verificación de coherencia

### 4.1 Comparación de datos

**Archivo**: `microservicio/modelo/auditor_recepcion.py`

**Método**: `verificar_coherencia()`

Compara los datos extraídos de la factura con los datos del formulario:

| Campo | Comparación | Severidad si discrepan |
|-------|-------------|------------------------|
| Número de factura | Exacta | CRÍTICA |
| Nombre de proveedor | Similitud | ALTA |
| Productos (nombre) | Exacta | MEDIA |
| Productos (cantidad) | Numérica | MEDIA |
| Productos (costo) | Numérica (tolerancia 5%) | MEDIA |
| Subtotal | Numérica (tolerancia 5%) | MEDIA |
| Total | Numérica (tolerancia 5%) | ALTA |

### 4.2 Detección de discrepancias

Si hay diferencias:
- Se genera una lista de discrepancias
- Cada discrepancia tiene: campo, valor_factura, valor_formulario, severidad, mensaje
- El usuario recibe una alerta con las diferencias

### 4.3 Acción del usuario

El usuario puede:
- **Corregir los datos** en el formulario para que coincidan con la factura
- **Registrar a pesar de discrepancias** (si está seguro de que sus datos son correctos)

Si el usuario registra a pesar de discrepancias, estas se guardan como feedback para aprendizaje.

## 5. Registro de feedback de aprendizaje

### 5.1 Captura de datos en el backend

**Archivo**: `Modelo/Controlador/recepcion.php`

Cuando el usuario registra una recepción con el microservicio activado:

1. Captura `ia_prediccion`: JSON con todos los datos extraídos por la IA
2. Captura `usar_ia`: Flag que indica si se usó el microservicio
3. Registra la recepción en la base de datos
4. Si `usar_ia = true` y `ia_prediccion` no es null, guarda feedback

### 5.2 Cálculo de errores

**Archivo**: `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php`

**Método**: `agregarFeedbackDataset()`

Calcula errores por campo:

```php
$error_proveedor = ($prediccion_proveedor !== $ground_truth_proveedor) ? 1 : 0;
$error_correlativo = ($prediccion_correlativo !== $ground_truth_correlativo) ? 1 : 0;
$error_productos = ($prediccion_productos !== $ground_truth_productos) ? 1 : 0;
$error_subtotal = ($prediccion_subtotal !== $ground_truth_subtotal) ? 1 : 0;
$error_total = ($prediccion_total !== $ground_truth_total) ? 1 : 0;
```

### 5.3 Guardado en CSV

**Archivo**: `microservicio/entrenamiento/feedback_dataset.csv`

Formato del CSV:

```csv
timestamp,id_recepcion,id_factura,prediccion_proveedor,ground_truth_proveedor,error_proveedor,prediccion_correlativo,ground_truth_correlativo,error_correlativo,prediccion_productos,ground_truth_productos,error_productos,prediccion_subtotal,ground_truth_subtotal,error_subtotal,prediccion_total,ground_truth_total,error_total,discrepancias_corregidas,confianza_ocr_promedio
"2026-10-22 23:33:46",13,512943,"Amazon Commercial Services","Amazon",0,512943,512943,0,"[...]","[...]",1,5340,5340,0,6194.4,6194.4,0,"[...]",0.76
```

**Descripción de columnas**:
- `timestamp`: Fecha/hora del registro
- `id_recepcion`: ID de la recepción en la base de datos
- `id_factura`: Número de factura
- `prediccion_*`: Valores predichos por la IA
- `ground_truth_*`: Valores confirmados por el usuario
- `error_*`: 1 si hay discrepancia, 0 si coinciden
- `discrepancias_corregidas`: JSON con lista de discrepancias que el usuario corrigió
- `confianza_ocr_promedio`: Confianza promedio de Tesseract (0.0 a 1.0)

## 6. Reentrenamiento del modelo

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

### 6.3 Proceso de reentrenamiento

1. **Lectura del dataset**:
   - Lee `entrenamiento/feedback_dataset.csv`
   - Verifica que haya al menos 10 registros

2. **Cálculo de métricas**:
   - Calcula error promedio por campo
   - Calcula confianza OCR promedio

3. **Construcción de features**:
   - Crea matriz X con 6 features:
     - error_proveedor
     - error_correlativo
     - error_productos
     - error_subtotal
     - error_total
     - confianza_ocr_promedio

4. **Construcción de target**:
   - target = 1 si error_total < 0.3 (extracción confiable)
   - target = 0 si error_total >= 0.3 (extracción no confiable)

5. **Entrenamiento del modelo**:
   - Usa RandomForestClassifier
   - n_estimators=100, max_depth=10
   - Entrena para predecir confiabilidad de extracciones

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

### 6.5 Uso del modelo entrenado

**Nota importante**: El modelo entrenado actualmente **NO se usa en el flujo de extracción**. El sistema depende únicamente de OCR + regex.

El modelo podría integrarse en el futuro para:
- Predecir si una extracción es confiable antes de mostrarla
- Priorizar revisiones manuales de extracciones de baja confianza
- Ajustar umbrales de confianza dinámicamente

## 7. Leer estados y resultados

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

## 8. Validar que feedback se está acumulando

Después de cada registro de recepción con microservicio activado, confirma:

1. El archivo `microservicio/entrenamiento/feedback_dataset.csv` tiene una nueva fila
2. La fila contiene:
   - `timestamp` actual
   - `id_recepcion` de la recepción registrada
   - `prediccion_*` con datos de la IA
   - `ground_truth_*` con datos confirmados
   - `error_*` con 0 o 1 según coincidencias
   - `confianza_ocr_promedio` con valor entre 0 y 1

3. Verificar cantidad de registros:
   ```sql
   SELECT COUNT(*) FROM microservicio/entrenamiento/feedback_dataset.csv
   ```
   O en PowerShell:
   ```powershell
   (Get-Content microservicio/entrenamiento/feedback_dataset.csv).Count
   ```

## 9. Problemas frecuentes

| Mensaje o síntoma | Comprobación |
|---|---|
| Servicio no disponible | FastAPI está iniciado y escucha en `127.0.0.1:8000`. |
| OCR no disponible | Tesseract está instalado en `C:\Program Files\Tesseract-OCR\tesseract.exe`. |
| PDF no se procesa | Poppler está instalado y en el PATH. |
| No se extraen datos | Imagen es legible, no está borrosa ni rotada. |
| Confianza muy baja | Imagen de buena calidad, resolución adecuada. |
| Discrepancias CRÍTICAS | Número de factura en la imagen coincide con el formulario. |
| feedback_dataset.csv vacío | Se han registrado recepciones con `usar_ia = true`. |
| Reentrenamiento falla | Hay al menos 10 registros en feedback_dataset.csv. |
| Modelo no se usa | El modelo entrenado es para referencia, no se usa en extracción actual. |

## 10. Archivos del microservicio y su rol

### 10.1 Archivos ACTIVOS en el flujo

| Archivo | Rol | Estado |
|---------|-----|--------|
| `recepcion.js` | Frontend JavaScript | ✅ Activo - Maneja formulario y envía al microservicio |
| `asistente_recepcion.js` | Cliente JS del microservicio | ✅ Activo - Interfaz con API FastAPI |
| `api_recepcion.py` | Controlador API FastAPI | ✅ Activo - Endpoint `/api/v1/auditar` |
| `auditor_recepcion.py` | Modelo OCR y extracción | ✅ Activo - Tesseract + regex |
| `recepcion.php` (controlador) | Backend PHP | ✅ Activo - Registra recepción y captura feedback |
| `Recepcion.php` (modelo) | Modelo PHP | ✅ Activo - `agregarFeedbackDataset()` |
| `feedback_dataset.csv` | Dataset de feedback | ✅ Activo - Acumula predicciones vs ground truth |
| `retrain.py` | Script de reentrenamiento | ✅ Activo - Entrena RandomForest |

### 10.2 Archivos NO UTILIZADOS actualmente

| Archivo | Rol | Estado |
|---------|-----|--------|
| `feedback_store.py` | Almacenamiento de feedback en JSONL | ❌ No usado - PHP escribe directamente al CSV |
| `model_registry.py` | Gestión de versiones de modelos | ❌ No usado - Modelo se guarda directamente como .pkl |
| `entrenar_modelo.py` | Orquestador de reentrenamiento | ❌ No usado - `retrain.py` hace el reentrenamiento |
| `logger.py` | Función de logging de predicciones | ❌ No usado - PHP escribe directamente al CSV |
| `modelo_actual.pkl` | Modelo entrenado | ⚠️ Entrenado pero no usado en extracción |

## 11. Seguridad y operación

- Mantén FastAPI en loopback (127.0.0.1) cuando PHP comparte el mismo host.
- El microservicio actual de auditoría **no usa token de autenticación** (a diferencia de analítica predictiva).
- Considera implementar autenticación si se expone el servicio externamente.
- Conserva los archivos del modelo y de feedback fuera de Git.
- Respálda `feedback_dataset.csv` periódicamente.
- El endpoint de entrenamiento escribe un único artefacto, así que ejecuta FastAPI con un solo worker si se activa entrenamiento concurrente.
- Las imágenes de facturas se eliminan del directorio temporal después del procesamiento.
- Los datos de feedback no contienen la imagen de la factura, solo los datos extraídos y confirmados.

## 12. Próximos pasos recomendados

1. **Integrar el modelo entrenado en el flujo de extracción**:
   - Usar `modelo_actual.pkl` para predecir confiabilidad
   - Mostrar advertencia si el modelo predice extracción no confiable
   - Priorizar revisiones manuales basadas en predicción del modelo

2. **Implementar autenticación**:
   - Agregar token compartido entre PHP y FastAPI
   - Similar al sistema de analítica predictiva

3. **Mejorar patrones regex**:
   - Agregar más patrones para diferentes formatos de factura
   - Probar con muestras reales de proveedores variados

4. **Implementar pruebas unitarias**:
   - Crear `microservicio/tests/test_auditor_recepcion.py`
   - Probar extracción con imágenes de prueba
   - Probar verificación de coherencia

5. **Monitoreo**:
   - Configurar alertas para latencia alta
   - Monitorear tasa de discrepancias por campo
   - Revisar confianza OCR promedio periódicamente
