# Configuración Externa del Microservicio de Recepción

Esta carpeta contiene los archivos de configuración externa que permiten adaptar el microservicio de recepción a diferentes sistemas ERP o bases de datos.

## Archivos de Configuración

El microservicio puede cargar configuración externa desde los siguientes archivos:

1. **patrones_factura.yaml** - Patrones de extracción de campos de facturas
2. **reglas_validacion.yaml** - Reglas de validación de recepciones
3. **configuracion_ml.yaml** - Configuración del modelo de Machine Learning
4. **catalogo_productos.csv** - Catálogo de productos desde sistema externo

## Cómo Crear los Archivos

### 1. patrones_factura.yaml

Este archivo define los patrones regex para extraer información de facturas PDF.

**Estructura básica:**

```yaml
numero_factura:
  - "Factura No\\.?\\s*[:#]?\\s*(\\d+)"
  - "Invoice\\s*[:#]?\\s*(\\d+)"
  - "#(\\d+)"

proveedor:
  - "Proveedor:\\s*([^\n]+)"
  - "Vendor:\\s*([^\n]+)"
  - "De:\\s*([^\n]+)"

fecha:
  - "\\d{2}/\\d{2}/\\d{4}"
  - "\\d{4}-\\d{2}-\\d{2}"
```

**Instrucciones:**
- Cada campo tiene una lista de patrones regex
- El sistema probará cada patrón hasta encontrar una coincidencia
- Usa grupos de captura `(...)` para extraer el valor
- Escapa caracteres especiales con `\\` (ej: `\\.` para punto)

### 2. reglas_validacion.yaml

Este archivo define las reglas de validación para las recepciones.

**Estructura básica:**

```yaml
validaciones:
  numero_factura:
    requerido: true
    longitud_minima: 5
    longitud_maxima: 20
    patron: "^\\d+$"
    mensaje_error: "El número de factura debe contener solo dígitos"

  proveedor:
    requerido: true
    longitud_minima: 3
    mensaje_error: "El proveedor es requerido"

  fecha:
    requerido: true
    formato: "YYYY-MM-DD"
    mensaje_error: "La fecha debe tener formato YYYY-MM-DD"
```

**Instrucciones:**
- Define qué campos son obligatorios
- Establece restricciones de longitud y formato
- Personaliza los mensajes de error

### 3. configuracion_ml.yaml

Este archivo configura el modelo de Machine Learning para predicción de confianza.

**Estructura básica:**

```yaml
modelo:
  ruta_archivo: "modelo_actual.pkl"
  tipo: "random_forest"
  caracteristicas:
    - "confianza_ocr"
    - "numero_paginas"
    - "cantidad_productos"
    - "longitud_texto"

umbral_confianza:
  minimo: 0.70
  aceptable: 0.85
  excelente: 0.95

reentrenamiento:
  activo: false
  intervalo_dias: 30
  minimo_muestras: 100
```

**Instrucciones:**
- Especifica la ruta del modelo entrenado
- Define los umbrales de confianza
- Configura el reentrenamiento automático

### 4. catalogo_productos.csv

Este archivo contiene el catálogo de productos desde el sistema ERP externo.

**Estructura básica:**

```csv
id,nombre,modelo,marca,costo_unitario
1,Impresora Digital,L3250,Epson,150.00
2,Botellas de Tinta Epson,544,Epson,25.00
3,Cutter 360,Sublime,Azon,8.50
```

**Instrucciones:**
- Primera fila: encabezados de columnas
- Columnas requeridas: `id`, `nombre`, `modelo`, `marca`, `costo_unitario`
- Usa codificación UTF-8
- Separador de campos: coma (`,`)
- Separador decimal: punto (`.`)

## Cómo Activar la Configuración Externa

Para usar estos archivos en lugar de la configuración por defecto:

1. **Coloca los archivos en la carpeta correcta:**
   - Mueve esta carpeta `conocimiento_externo` a `conocimiento`
   - O copia los archivos a la carpeta `microservicio/conocimiento/`

2. **El microservicio detectará automáticamente:**
   - Si los archivos existen, los usará
   - Si no existen, usa la configuración por defecto del código

3. **Reinicia el microservicio:**
   ```bash
   uvicorn main:app --host 127.0.0.1 --port 8000 --reload
   ```

## Integración con Sistemas Externos

### Desde MySQL

Para exportar el catálogo desde MySQL a CSV:

```sql
SELECT 
    id_producto as id,
    nombre_producto as nombre,
    modelo_producto as modelo,
    marca_producto as marca,
    costo_unitario as costo_unitario
FROM productos
INTO OUTFILE '/tmp/catalogo_productos.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

### Desde PostgreSQL

```sql
COPY (
    SELECT 
        id_producto as id,
        nombre_producto as nombre,
        modelo_producto as modelo,
        marca_producto as marca,
        costo_unitario as costo_unitario
    FROM productos
) TO '/tmp/catalogo_productos.csv' WITH CSV HEADER;
```

### Desde SQL Server

```sql
bcp "SELECT id_producto, nombre_producto, modelo_producto, marca_producto, costo_unitario FROM productos" 
queryout catalogo_productos.csv -c -t, -T -S servidor
```

## Personalización por Sistema

### Sistema SAP

Para SAP, los patrones de factura pueden requerir ajustes específicos:

```yaml
numero_factura:
  - "Document\\s*[:#]?\\s*(\\d{10})"
  - "Billing\\s*Doc\\s*[:#]?\\s*(\\d+)"

proveedor:
  - "Vendor\\s*[:#]?\\s*([A-Z0-9]+)"
  - "Sold-to\\s*Party\\s*[:#]?\\s*([^\n]+)"
```

### Sistema Oracle EBS

```yaml
numero_factura:
  - "Invoice\\s*Number\\s*[:#]?\\s*(\\d+)"

proveedor:
  - "Supplier\\s*[:#]?\\s*([^\n]+)"
```

## Validación de Archivos

Antes de usar los archivos, valida:

1. **YAML válido:** Usa un validador YAML online
2. **CSV válido:** Verifica que tenga encabezados y datos correctos
3. **Sintaxis regex:** Prueba los patrones en herramientas como regex101.com
4. **Codificación:** Asegúrate de que los archivos estén en UTF-8

## Solución de Problemas

### Los archivos no se cargan

- Verifica que estén en la carpeta `microservicio/conocimiento/`
- Revisa los logs del microservicio para errores de carga
- Asegúrate de que los nombres de archivo sean exactos

### Errores de sintaxis YAML

- Usa indentación con espacios (no tabs)
- Verifica que no haya caracteres especiales sin escape
- Valida el YAML antes de usarlo

### El catálogo no se carga

- Verifica que el CSV tenga las columnas requeridas
- Revisa la codificación del archivo (debe ser UTF-8)
- Verifica que el separador sea coma (`,`)

## Soporte

Para más información sobre la configuración del microservicio, consulta la documentación técnica en la carpeta `microservicio/auditor_recepcion/`.
