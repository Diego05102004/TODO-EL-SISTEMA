# Documento Técnico Unificado: Microservicio de Auditoría de Recepción (Versión 4)
## Guía Completa de Arquitectura, Entrenamiento y Operación

---

## Índice

1. [Resumen de Cambios](#1-resumen-de-cambios)
2. [Configuración y Ejecución](#2-configuración-y-ejecución)
3. [Arquitectura del Modelo](#3-arquitectura-del-modelo)
4. [Base de Conocimiento Externa](#4-base-de-conocimiento-externa)
5. [Adaptador de Base de Datos](#5-adaptador-de-base-de-datos)
6. [Flujo de Extracción OCR](#6-flujo-de-extracción-ocr)
7. [Verificación de Coherencia](#7-verificación-de-coherencia)
8. [Registro de Feedback](#8-registro-de-feedback)
9. [Eliminación de Feedback al Anular Recepción](#9-eliminación-de-feedback-al-anular-recepción)
10. [Reentrenamiento del Modelo](#10-reentrenamiento-del-modelo)
11. [Validaciones de Factura](#11-validaciones-de-factura)
12. [Seguridad y Rate Limiting](#12-seguridad-y-rate-limiting)
13. [Métricas y Monitoreo](#13-métricas-y-monitoreo)
14. [Pruebas y Mantenimiento](#14-pruebas-y-mantenimiento)
15. [Solución de Problemas](#15-solución-de-problemas)

---

## 1. Resumen de Cambios

### 1.1 Cambios desde Versión 3

Esta versión unificada incorpora las siguientes mejoras implementadas:

1. **Base de conocimiento externa**: Externalización de patrones, reglas y configuración a archivos YAML editables
2. **Adaptador de base de datos dinámico**: Sistema de adaptadores para cargar catálogo de productos y proveedores desde MySQL
3. **Sistema de adaptadores versátil**: Arquitectura extensible para integrar con diferentes sistemas (MySQL, CSV, otros)
4. **Eliminación de feedback al anular**: Eliminación automática de registros del feedback_dataset.csv al anular recepciones
5. **Reentrenamiento automático**: Reentrenamiento del modelo cuando se elimina feedback y quedan >=10 registros
6. **Prevención de contaminación de datos**: Garantía de que el dataset de entrenamiento solo contenga recepciones válidas

### 1.2 Cambios desde Versión 2

1. **Validación de formato de factura**: Detección automática de documentos que no son facturas válidas
2. **Validación de productos en catálogo**: Verificación de que los productos extraídos están en el catálogo del sistema
3. **Bloqueo de botón Guardar**: Prevención de registro con facturas inválidas
4. **Rate limiting con slowapi**: Protección contra abuso de API con límites por IP
5. **Mejoras en patrones regex**: Soporte expandido para diferentes formatos de factura internacionales
6. **Mejoras en parsing de montos**: Manejo robusto de formatos numéricos con separadores variados

### 1.3 Cambios desde Versión 1

| Aspecto | Versión 1 | Versión 2 | Versión 3 | Versión 4 |
|---------|-----------|-----------|-----------|-----------|
| Features de entrenamiento | 6 (errores + confianza) | 1 (solo confianza) | 1 (solo confianza) | 1 (solo confianza) |
| Modelo en inferencia | No usado | Sí usado | Sí usado | Sí usado |
| Autenticación API | No | Token HMAC | Token HMAC | Token HMAC |
| Alertas de confiabilidad | No | Sí (basadas en ML) | Sí (basadas en ML) | Sí (basadas en ML) |
| Validación de factura | No | No | Sí | Sí |
| Validación de catálogo | No | No | Sí (CSV estático) | Sí (MySQL dinámico) |
| Base de conocimiento | Hardcoded | Hardcoded | YAML externo | YAML externo + MySQL |
| Rate limiting | No | No | Sí (slowapi) | Sí (slowapi) |
| Degradación sin modelo | No definido | Graceful | Graceful | Graceful |
| Eliminación de feedback | No | No | No | Sí (al anular) |
| Reentrenamiento automático | Manual | Manual | Manual | Automático |

---

## 2. Configuración y Ejecución

### 2.1 Requisitos Previos

- PHP 7.4 o superior con `curl` y `pdo_mysql`
- Python 3.10 o superior y las dependencias de `microservicio/requirements.txt`
- FastAPI con acceso local a `127.0.0.1:8000`
- Tesseract OCR instalado (Windows: `C:\Program Files\Tesseract-OCR\tesseract.exe`)
- Poppler (para procesamiento de PDFs)
- MySQL/MariaDB con base de datos `casalai_principal`
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

### 2.3 Configurar Base de Datos MySQL

**Configuración en .env (FastAPI)**:
```text
BD_TIPO=mysql
BD_HOST=localhost
BD_PORT=3306
BD_USER=MicroserviceCAI
BD_PASSWORD=MSagente#26
BD_DATABASE=casalai_principal
```

**Estructura de tablas requeridas**:

**tbl_productos**:
- `id_producto` (INT, PK)
- `nombre_producto` (VARCHAR)
- `descripcion_producto` (VARCHAR)
- `id_modelo` (INT, FK a tbl_modelos)
- `id_categoria` (INT)
- `precio` (DECIMAL)
- `estado` (VARCHAR, 'habilitado' para activos)

**tbl_modelos**:
- `id_modelo` (INT, PK)
- `nombre_modelo` (VARCHAR)
- `id_marca` (INT, FK a tbl_marcas)

**tbl_marcas**:
- `id_marca` (INT, PK)
- `nombre_marca` (VARCHAR)

**tbl_proveedores**:
- `id_proveedor` (INT, PK)
- `nombre_proveedor` (VARCHAR)
- `estado` (VARCHAR, 'habilitado' para activos)

### 2.4 Iniciar el Servidor

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

### 2.5 Configurar el Cliente JavaScript

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

## 4. Base de Conocimiento Externa

### 4.1 Arquitectura de Base de Conocimiento

La base de conocimiento del microservicio se ha externalizado en archivos editables, permitiendo modificar el comportamiento sin cambiar el código.

**Estructura de directorios**:
```
microservicio/
├── conocimiento/
│   ├── patrones_factura.yaml      # Patrones regex para extracción
│   ├── reglas_validacion.yaml     # Reglas de validación de factura
│   ├── configuracion_ml.yaml      # Configuración del modelo ML
│   └── catalogo_productos.csv     # Catálogo de productos (fallback)
└── modelo/
    ├── adaptador_bd.py            # Adaptador de base de datos
    └── auditor_recepcion.py       # Auditor principal
```

### 4.2 Archivos de Configuración YAML

#### 4.2.1 patrones_factura.yaml

**Propósito**: Define patrones regex para extraer campos de la factura.

**Estructura**:
```yaml
patrones:
  numero_factura:
    - 'factura[:\s]*n[°º]?[:\s]*([A-Z0-9\-]{5,20})'
    - 'invoice[:\s]*#?\s*([A-Z0-9\-]+)'
    - 'bill[:\s]*#?\s*([A-Z0-9\-]+)'
    - 'receipt[:\s]*#?\s*([A-Z0-9\-]+)'
    - '#\s*([A-Z0-9\-]+)'
  
  proveedor:
    - 'proveedor[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+)'
    - 'supplier[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+)'
    - 'sold\s*by[:\s]+([A-Z][A-Za-z0-9\s&.,\-]+)'
    - 'eBay|Amazon|MercadoLibre|AliExpress'
  
  fecha:
    - '(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})'
    - '(\d{4}[/-]\d{1,2}[/-]\d{1,2})'
    - '(\d{1,2}\s+de\s+[A-Za-z]+\s+\d{2,4})'
  
  productos:
    tabulares:
      - 'descripcion[:\s]+([^\n]+)'
      - 'producto[:\s]+([^\n]+)'
    estilo_libre:
      - '([A-Z][a-zA-Z0-9\s\-\+]{10,100})'
  
  montos:
    subtotal:
      - 'subtotal[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
      - 'sub\s*total[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
    total:
      - 'total[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
      - 'grand\s*total[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
    iva:
      - 'iva[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
      - 'tax[:\s]+[\$]?\s*(\d+[.,]?\d{2})'
```

**Beneficios**:
- Patrones editables sin modificar código
- Fácil agregar nuevos formatos de factura
- Soporte para múltiples idiomas y formatos internacionales
- Versionamiento de patrones con Git

#### 4.2.2 reglas_validacion.yaml

**Propósito**: Define reglas para validar que un documento sea una factura válida.

**Estructura**:
```yaml
validacion_factura:
  palabras_clave_requeridas:
    - factura
    - invoice
    - bill
    - receipt
    - total
    - subtotal
    - iva
    - tax
    - precio
    - costo
    - cantidad
    - proveedor
    - vendor
  
  min_palabras_clave: 2
  
  campos_esenciales:
    - numero_factura
    - nombre_proveedor
    - fecha_factura
    - productos
  
  min_campos_esenciales: 2
  
  montos_requeridos:
    - subtotal
    - total
  
  min_montos: 1
  
  umbrales:
    confianza_minima: 0.5
    productos_minimos: 1
    productos_maximos: 50
```

**Beneficios**:
- Reglas de validación configurables
- Ajuste de umbrales sin recompilar
- Fácil adaptación a diferentes tipos de factura

#### 4.2.3 configuracion_ml.yaml

**Propósito**: Configura parámetros del modelo de Machine Learning.

**Estructura**:
```yaml
modelo_ml:
  algoritmo: RandomForestClassifier
  n_estimators: 100
  max_depth: 10
  min_samples_split: 2
  min_samples_leaf: 1
  random_state: 42
  
entrenamiento:
  min_registros: 10
  umbral_error: 0.3
  test_size: 0.3
  
features:
  - confianza_ocr_promedio
  
target:
  nombre: error_total
  umbral_confiable: 0.3
```

**Beneficios**:
- Hiperparámetros del modelo configurables
- Fácil experimentación con diferentes configuraciones
- Versionamiento de configuraciones

### 4.3 Catálogo de Productos (Fallback)

**Archivo**: `conocimiento/catalogo_productos.csv`

**Propósito**: Catálogo estático de productos como fallback cuando no hay conexión a MySQL.

**Estructura**:
```csv
id,nombre,modelo,marca,costo_unitario
1,HP DeskJet 2775,DeskJet 2775,HP,1520.00
2,Tinta HP Original GT,662,HP,250.00
3,Tarjeta SD Kingston,Sublime,Azon,80.00
```

**Uso**:
- Se carga solo si falla la conexión a MySQL
- Permite funcionamiento offline
- Útil para sistemas externos que no tienen base de datos MySQL

### 4.4 Carga de Base de Conocimiento

**Ubicación**: `auditor_recepcion.py`, método `__init__()`

**Proceso**:
```python
def __init__(self):
    # Cargar patrones desde YAML
    with open(PATRONES_FILE, 'r') as f:
        self.patrones = yaml.safe_load(f)
    
    # Cargar reglas desde YAML
    with open(REGLAS_FILE, 'r') as f:
        self.reglas = yaml.safe_load(f)
    
    # Cargar configuración ML desde YAML
    with open(CONFIG_ML_FILE, 'r') as f:
        self.config_ml = yaml.safe_load(f)
    
    # Crear adaptador de base de datos
    self.adaptador_bd = self._crear_adaptador_bd()
    
    # Cargar catálogo dinámico desde BD
    self.catalogo_productos = self._cargar_catalogo()
    self.catalogo_proveedores = self._cargar_proveedores()
```

**Beneficios**:
- Separación de configuración y código
- Fácil mantenimiento y actualización
- Versionamiento de configuraciones con Git
- Permite múltiples entornos (dev, staging, prod)

---

## 5. Adaptador de Base de Datos

### 5.1 Arquitectura de Adaptadores

**Patrón de diseño**: Abstract Factory + Strategy

**Propósito**: Permitir integración con diferentes sistemas de base de datos de manera transparente.

**Clases principales**:
- `AdaptadorBaseDatos`: Clase abstracta base
- `AdaptadorMySQL`: Implementación para MySQL/MariaDB
- `AdaptadorCSV`: Implementación para archivos CSV (fallback)
- `FabricaAdaptadores`: Fábrica para crear adaptadores según configuración

### 5.2 Clase Abstracta: AdaptadorBaseDatos

**Ubicación**: `modelo/adaptador_bd.py`

**Métodos abstractos**:
```python
from abc import ABC, abstractmethod
from dataclasses import dataclass
from typing import List, Optional

@dataclass
class Producto:
    id: str
    nombre: str
    modelo: str
    marca: str
    costo_unitario: float

@dataclass
class Proveedor:
    id: str
    nombre: str

class AdaptadorBaseDatos(ABC):
    @abstractmethod
    def obtener_productos(self) -> List[Producto]:
        """Obtener todos los productos del catálogo."""
        pass
    
    @abstractmethod
    def obtener_proveedores(self) -> List[Proveedor]:
        """Obtener todos los proveedores del catálogo."""
        pass
    
    @abstractmethod
    def buscar_producto_por_nombre(self, nombre: str) -> Optional[Producto]:
        """Buscar producto por nombre (búsqueda aproximada)."""
        pass
    
    @abstractmethod
    def buscar_proveedor_por_nombre(self, nombre: str) -> Optional[Proveedor]:
        """Buscar proveedor por nombre (búsqueda aproximada)."""
        pass
    
    @abstractmethod
    def cerrar_conexion(self):
        """Cerrar conexión a la base de datos."""
        pass
```

### 5.3 Implementación MySQL: AdaptadorMySQL

**Propósito**: Cargar catálogo de productos y proveedores desde base de datos MySQL/MariaDB.

**Configuración**:
```python
BD_HOST=localhost
BD_PORT=3306
BD_USER=root
BD_PASSWORD=
BD_DATABASE=casalai_principal
```

**Query para productos**:
```sql
SELECT p.id_producto, p.nombre_producto, p.descripcion_producto, 
       p.id_modelo, p.id_categoria, p.precio, p.estado,
       m.nombre_modelo, ma.nombre_marca
FROM tbl_productos p
LEFT JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
LEFT JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
WHERE p.estado = 'habilitado'
```

**Query para proveedores**:
```sql
SELECT id_proveedor, nombre_proveedor, estado
FROM tbl_proveedores 
WHERE estado = 'habilitado'
```

**Mapeo de campos**:
- `id_producto` → `id`
- `nombre_producto` → `nombre`
- `nombre_modelo` → `modelo`
- `nombre_marca` → `marca`
- `precio` → `costo_unitario`
- `id_proveedor` → `id`
- `nombre_proveedor` → `nombre`

**Implementación**:
```python
class AdaptadorMySQL(AdaptadorBaseDatos):
    def __init__(self, host, port, user, password, database):
        import mysql.connector
        self.conn = mysql.connector.connect(
            host=host,
            port=port,
            user=user,
            password=password,
            database=database
        )
        self.cursor = self.conn.cursor(dictionary=True)
    
    def obtener_productos(self) -> List[Producto]:
        query = """
            SELECT p.id_producto, p.nombre_producto, p.descripcion_producto, 
                   p.id_modelo, p.id_categoria, p.precio, p.estado,
                   m.nombre_modelo, ma.nombre_marca
            FROM tbl_productos p
            LEFT JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
            LEFT JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
            WHERE p.estado = 'habilitado'
        """
        self.cursor.execute(query)
        rows = self.cursor.fetchall()
        
        productos = []
        for row in rows:
            productos.append(Producto(
                id=str(row['id_producto']),
                nombre=row['nombre_producto'],
                modelo=row['nombre_modelo'] or '',
                marca=row['nombre_marca'] or '',
                costo_unitario=float(row['precio'])
            ))
        return productos
    
    def obtener_proveedores(self) -> List[Proveedor]:
        query = """
            SELECT id_proveedor, nombre_proveedor, estado
            FROM tbl_proveedores 
            WHERE estado = 'habilitado'
        """
        self.cursor.execute(query)
        rows = self.cursor.fetchall()
        
        proveedores = []
        for row in rows:
            proveedores.append(Proveedor(
                id=str(row['id_proveedor']),
                nombre=row['nombre_proveedor']
            ))
        return proveedores
    
    def buscar_producto_por_nombre(self, nombre: str) -> Optional[Producto]:
        query = """
            SELECT p.id_producto, p.nombre_producto, p.descripcion_producto, 
                   p.id_modelo, p.id_categoria, p.precio, p.estado,
                   m.nombre_modelo, ma.nombre_marca
            FROM tbl_productos p
            LEFT JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
            LEFT JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
            WHERE p.estado = 'habilitado' 
            AND p.nombre_producto LIKE %s
        """
        self.cursor.execute(query, (f'%{nombre}%',))
        row = self.cursor.fetchone()
        
        if row:
            return Producto(
                id=str(row['id_producto']),
                nombre=row['nombre_producto'],
                modelo=row['nombre_modelo'] or '',
                marca=row['nombre_marca'] or '',
                costo_unitario=float(row['precio'])
            )
        return None
    
    def buscar_proveedor_por_nombre(self, nombre: str) -> Optional[Proveedor]:
        query = """
            SELECT id_proveedor, nombre_proveedor, estado
            FROM tbl_proveedores 
            WHERE estado = 'habilitado' 
            AND nombre_proveedor LIKE %s
        """
        self.cursor.execute(query, (f'%{nombre}%',))
        row = self.cursor.fetchone()
        
        if row:
            return Proveedor(
                id=str(row['id_proveedor']),
                nombre=row['nombre_proveedor']
            )
        return None
    
    def cerrar_conexion(self):
        if self.cursor:
            self.cursor.close()
        if self.conn:
            self.conn.close()
```

### 5.4 Implementación CSV: AdaptadorCSV

**Propósito**: Fallback para cargar catálogo desde archivos CSV cuando no hay conexión a MySQL.

**Implementación**:
```python
class AdaptadorCSV(AdaptadorBaseDatos):
    def __init__(self, ruta_productos: str, ruta_proveedores: Optional[str] = None):
        self.ruta_productos = ruta_productos
        self.ruta_proveedores = ruta_proveedores
        self._productos: List[Producto] = []
        self._proveedores: List[Proveedor] = []
        self._cargar_datos()
    
    def _cargar_datos(self):
        import csv
        from pathlib import Path
        
        # Cargar productos
        if Path(self.ruta_productos).exists():
            with open(self.ruta_productos, 'r', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for row in reader:
                    self._productos.append(Producto(
                        id=row.get('id', ''),
                        nombre=row.get('nombre', ''),
                        modelo=row.get('modelo', ''),
                        marca=row.get('marca', ''),
                        costo_unitario=float(row.get('costo_unitario', 0))
                    ))
        
        # Cargar proveedores si existe el archivo
        if self.ruta_proveedores and Path(self.ruta_proveedores).exists():
            with open(self.ruta_proveedores, 'r', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for row in reader:
                    self._proveedores.append(Proveedor(
                        id=row.get('id', ''),
                        nombre=row.get('nombre', '')
                    ))
    
    def obtener_productos(self) -> List[Producto]:
        return self._productos
    
    def obtener_proveedores(self) -> List[Proveedor]:
        return self._proveedores
    
    def buscar_producto_por_nombre(self, nombre: str) -> Optional[Producto]:
        nombre_lower = nombre.lower()
        for producto in self._productos:
            if nombre_lower in producto.nombre.lower():
                return producto
        return None
    
    def buscar_proveedor_por_nombre(self, nombre: str) -> Optional[Proveedor]:
        nombre_lower = nombre.lower()
        for proveedor in self._proveedores:
            if nombre_lower in proveedor.nombre.lower():
                return proveedor
        return None
    
    def cerrar_conexion(self):
        """No hay conexión que cerrar para CSV."""
        pass
```

### 5.5 Fábrica de Adaptadores: FabricaAdaptadores

**Propósito**: Crear el adaptador apropiado según la configuración de variables de entorno.

**Implementación**:
```python
class FabricaAdaptadores:
    @staticmethod
    def crear() -> Optional[AdaptadorBaseDatos]:
        bd_tipo = os.getenv('BD_TIPO', '').lower()
        
        if bd_tipo == 'mysql':
            try:
                return AdaptadorMySQL(
                    host=os.getenv('BD_HOST', 'localhost'),
                    port=int(os.getenv('BD_PORT', 3306)),
                    user=os.getenv('BD_USER', 'root'),
                    password=os.getenv('BD_PASSWORD', ''),
                    database=os.getenv('BD_DATABASE', 'casalai_principal')
                )
            except Exception as e:
                logger.error(f"Error conectando a MySQL: {e}")
                logger.info("Usando adaptador CSV como fallback")
                return AdaptadorCSV(
                    ruta_productos=CONOCIMIENTO_DIR / 'catalogo_productos.csv'
                )
        else:
            # Por defecto usar CSV
            return AdaptadorCSV(
                ruta_productos=CONOCIMIENTO_DIR / 'catalogo_productos.csv'
            )
```

### 5.6 Integración con AuditorRecepcion

**Ubicación**: `auditor_recepcion.py`

**Carga del adaptador**:
```python
def _crear_adaptador_bd(self) -> Optional[AdaptadorBaseDatos]:
    """Crea el adaptador de base de datos según configuración."""
    try:
        adaptador = FabricaAdaptadores.crear()
        if adaptador:
            logger.info("Adaptador de base de datos creado exitosamente")
        return adaptador
    except Exception as e:
        logger.error(f"Error creando adaptador de base de datos: {e}")
        return None

def _cargar_catalogo(self) -> List[Dict]:
    """Carga el catálogo de productos desde el adaptador de base de datos."""
    if self.adaptador_bd:
        try:
            productos = self.adaptador_bd.obtener_productos()
            return [
                {
                    'id': p.id,
                    'nombre': p.nombre,
                    'modelo': p.modelo,
                    'marca': p.marca,
                    'costo_unitario': p.costo_unitario
                }
                for p in productos
            ]
        except Exception as e:
            logger.error(f"Error cargando productos desde adaptador: {e}")
            return []
    else:
        logger.warning("No hay adaptador BD, catálogo vacío")
        return []

def _cargar_proveedores(self) -> List[Dict]:
    """Carga el catálogo de proveedores desde el adaptador de base de datos."""
    if self.adaptador_bd:
        try:
            proveedores = self.adaptador_bd.obtener_proveedores()
            return [
                {
                    'id': p.id,
                    'nombre': p.nombre
                }
                for p in proveedores
            ]
        except Exception as e:
            logger.error(f"Error cargando proveedores desde adaptador: {e}")
            return []
    else:
        logger.warning("No hay adaptador BD, proveedores vacíos")
        return []
```

**Beneficios**:
- Catálogo siempre actualizado desde base de datos
- Fallback a CSV si falla conexión MySQL
- Arquitectura extensible para otros sistemas (PostgreSQL, MongoDB, etc.)
- Separación de lógica de acceso a datos

---

## 6. Flujo de Extracción OCR

### 6.1 Recorrido de los Datos

Sigue este orden al leer el código:

1. **Controles de la página**: `Vista/VistaNew/recepcion.php` contiene el formulario de recepción, el selector de archivo de factura, y el botón de registro.
2. **Solicitud del navegador**: la función JavaScript `AsistenteRecepcionIA` en `microservicio/javascript/asistente_recepcion.js` envía la imagen al microservicio con el token de autenticación.
3. **Controlador API**: `microservicio/controlador/api_recepcion.py` valida el token, recibe el archivo en el endpoint `/fase1/extraer`.
4. **Carga de base de conocimiento**: `auditor_recepcion.py` carga patrones, reglas y configuración desde archivos YAML.
5. **Carga de catálogo dinámico**: `auditor_recepcion.py` carga productos y proveedores desde MySQL vía adaptador.
6. **Procesamiento OCR**: `auditor_recepcion.py` ejecuta Tesseract OCR para extraer texto de la imagen.
7. **Extracción de campos**: El mismo módulo usa expresiones regulares (desde YAML) para extraer número de factura, proveedor, productos, montos, etc.
8. **Validación de factura**: Verifica si el documento parece ser una factura válida (usando reglas YAML).
9. **Validación de catálogo**: Verifica que los productos extraídos estén en el catálogo del sistema (MySQL).
10. **Predicción de confiabilidad**: El modelo ML predice si la extracción es confiable basándose en la confianza OCR.
11. **Verificación de coherencia**: Compara los datos extraídos con los datos del formulario del usuario.
12. **Registro de recepción**: `Modelo/Controlador/recepcion.php` registra la recepción en la base de datos MariaDB.
13. **Guardado de feedback**: `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php` calcula errores entre predicción y ground truth, y guarda en `microservicio/entrenamiento/feedback_dataset.csv`.
14. **Eliminación de feedback**: Al anular una recepción, se elimina el registro correspondiente del feedback_dataset.csv.
15. **Reentrenamiento automático**: Si al eliminar feedback quedan >=10 registros, se reentrena automáticamente el modelo.
16. **Reentrenamiento manual**: `microservicio/retrain.py` lee el feedback_dataset.csv, entrena un modelo RandomForest con solo la confianza OCR como feature, y guarda en `microservicio/modelo/modelo_actual.pkl`.

### 6.2 Selección de Factura por el Usuario

1. El usuario accede al módulo de Recepciones.
2. Selecciona un archivo de factura (PDF, JPG, PNG).
3. El JavaScript `asistente_recepcion.js` envía la imagen al microservicio con el token de autenticación.

### 6.3 Procesamiento en el Microservicio

**Archivo**: `microservicio/modelo/auditor_recepcion.py`

**Pasos**:

1. **Carga de base de conocimiento**:
   - Lee `patrones_factura.yaml` para obtener patrones regex
   - Lee `reglas_validacion.yaml` para obtener reglas de validación
   - Lee `configuracion_ml.yaml` para obtener configuración del modelo

2. **Carga de catálogo dinámico**:
   - Crea adaptador de base de datos (MySQL o CSV)
   - Carga productos desde `tbl_productos` (con JOIN a `tbl_modelos` y `tbl_marcas`)
   - Carga proveedores desde `tbl_proveedores`

3. **Preprocesamiento de imagen**:
   - Si es PDF: convierte a imágenes usando `pdf2image`
   - Aplica OpenCV: escala de grises, denoising, CLAHE, threshold adaptativo

4. **OCR con Tesseract**:
   - Ejecuta Tesseract con configuración `--psm 6 --oem 3`
   - Idiomas: `spa+eng` (español e inglés)
   - Extrae todo el texto de la imagen

5. **Extracción de campos usando regex (desde YAML)**:
   - Número de factura (patrones cargados desde YAML)
   - Nombre de proveedor (patrones cargados desde YAML)
   - Fecha (patrones cargados desde YAML)
   - Productos (métodos tabulares y estilo libre)
   - Subtotal, IVA, Total (patrones cargados desde YAML)

6. **Validación de factura (usando reglas YAML)**:
   - Verifica palabras clave (factura, invoice, total, iva, precio, cantidad, etc.)
   - Valida campos esenciales (número, proveedor, fecha, productos)
   - Valida montos monetarios (subtotal y total)
   - Si no cumple criterios, marca como no válida

7. **Validación de catálogo (usando MySQL)**:
   - Verifica que los productos extraídos estén en el catálogo
   - Si todos los productos están fuera del catálogo → Factura inválida

8. **Cálculo de confianza**:
   - Tesseract devuelve confianza por campo
   - Se calcula promedio de todas las confianzas
   - Rango: 0.0 (baja) a 1.0 (alta)

9. **Predicción de confiabilidad ML**:
   - Si el modelo está cargado, se llama a `_predecir_confiabilidad(confianza_ocr)`
   - El modelo retorna probabilidad de que la extracción sea confiable (0.0 a 1.0)
   - Esta probabilidad se incluye en los metadatos de extracción

10. **Guardado en caché**:
    - Los datos extraídos se guardan en memoria con un ID único
    - Permite verificación posterior sin reextraer

### 6.4 Respuesta al Frontend

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

**Campos en V4**:
- `subtotal_factura`, `porcentaje_iva`, `monto_iva`, `total_factura`: Montos extraídos
- `es_factura_valida`: Indica si el documento parece ser una factura válida
- `mensaje_validacion`: Mensaje de error si la validación falla
- **Productos con marca y modelo desde MySQL**: Datos del catálogo real

### 6.5 Relleno del Formulario y Alertas

El JavaScript recibe los datos y:
- Rellena automáticamente los campos del formulario
- Muestra un panel con los datos extraídos
- Calcula subtotales y totales
- **Verifica la confiabilidad ML y muestra alertas**:
  - Si `confiabilidad_ml < 0.5`: Alerta de advertencia "El modelo indica que esta extracción puede tener errores"
  - Si `0.5 <= confiabilidad_ml < 0.7`: Alerta informativa "Confiabilidad moderada, se recomienda revisar"
  - Si `confiabilidad_ml >= 0.7`: Sin alerta
- **Valida si el documento es una factura**:
  - Si `es_factura_valida === false`: Muestra error y bloquea botón Guardar
- **Valida productos en catálogo**:
  - Si todos los productos están fuera del catálogo: Muestra error y bloquea botón Guardar
- Permite al usuario corregir manualmente si es necesario

---

## 7. Verificación de Coherencia

### 7.1 Endpoint de Verificación

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

### 7.2 Criterios de Discrepancia

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

## 8. Registro de Feedback

### 8.1 Cálculo de Errores

**Archivo**: `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php`

**Método**: `agregarFeedbackDataset()`

**Proceso**:
1. Compara datos extraídos por IA con datos confirmados por usuario
2. Calcula error por campo (0 si coincide, 1 si no coincide)
3. Calcula error_total como promedio de errores por campo
4. Guarda fila en `microservicio/entrenamiento/feedback_dataset.csv`

**Formato del CSV**:
```csv
timestamp,id_recepcion,id_factura,prediccion_proveedor,ground_truth_proveedor,error_proveedor,prediccion_correlativo,ground_truth_correlativo,error_correlativo,prediccion_productos,ground_truth_productos,error_productos,prediccion_subtotal,ground_truth_subtotal,error_subtotal,prediccion_total,ground_truth_total,error_total,discrepancias_corregidas,confianza_ocr_promedio
2026-10-27 22:13:19,12,498231,eBay,ebay,0,498231,498231,0,"[{""nombre"":""HP DeskJet 2775"",""modelo"":""DeskJet 2775"",""marca"":""HP"",""serial"":""0002"",""cantidad"":2,""costo_unitario"":1520,""confianza"":0.9}]","[{""nombre"":"""",""cantidad"":2,""costo"":1520}]",1,5340,5340,0,6194.4,6194.4,0,[],0.94
```

### 8.2 Validar que Feedback se Acumula

Verificar que el archivo `feedback_dataset.csv` está creciendo:

```powershell
wc -l microservicio/entrenamiento/feedback_dataset.csv
```

Debería incrementarse con cada recepción registrada con `usar_ia = true`.

---

## 9. Eliminación de Feedback al Anular Recepción

### 9.1 Problema que Resuelve

**Problema**: Si el usuario comete un error al confirmar una recepción (ej: selecciona producto incorrecto), ese error se guarda en `feedback_dataset.csv` y contamina el dataset de entrenamiento.

**Solución**: Al anular una recepción, eliminar automáticamente el registro correspondiente del `feedback_dataset.csv` para evitar contaminación del entrenamiento.

### 9.2 Implementación en PHP

**Archivo**: `Modelo/Usuario/ProyectoCasalaiCa/Clases/Recepcion.php`

**Método público**:
```php
public function eliminarFeedbackDataset($idRecepcion) {
    return $this->del_FeedbackDataset($idRecepcion);
}
```

**Método privado**:
```php
private function del_FeedbackDataset($idRecepcion) {
    // Ruta hacia el feedback_dataset.csv dentro del microservicio
    $rutaCsv = dirname(__DIR__, 4) . '/microservicio/entrenamiento/feedback_dataset.csv';

    if (!file_exists($rutaCsv)) {
        error_log("ADVERTENCIA: No se encontró el feedback_dataset.csv en: " . $rutaCsv);
        return false;
    }

    // Leer todo el archivo
    $filas = file($rutaCsv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($filas === false) {
        error_log("CRítico: No se pudo leer el archivo feedback_dataset.csv.");
        return false;
    }

    // Convertir a array de arrays
    $datos = [];
    $encabezado = null;
    foreach ($filas as $indice => $linea) {
        $fila = str_getcsv($linea);
        if ($indice === 0) {
            $encabezado = $fila; // Guardar encabezado
            continue;
        }
        // El id_recepcion está en la columna 1 (índice 1)
        if (isset($fila[1]) && $fila[1] != $idRecepcion) {
            $datos[] = $fila; // Mantener filas que no coinciden con el id_recepcion
        }
    }

    // Reescribir el archivo con los datos filtrados
    $fp = fopen($rutaCsv, 'w');
    if ($fp === false) {
        error_log("CRítico: No se pudo abrir el archivo feedback_dataset.csv para escritura.");
        return false;
    }

    // Escribir encabezado
    if ($encabezado !== null) {
        fputcsv($fp, $encabezado);
    }

    // Escribir datos filtrados
    foreach ($datos as $fila) {
        fputcsv($fp, $fila);
    }

    fclose($fp);
    
    // Contar registros restantes (sin encabezado)
    $registrosRestantes = count($datos);
    error_log("Eliminado registro de feedback para recepción ID: $idRecepcion. Registros restantes: $registrosRestantes");
    
    // Si hay más de 10 registros, reentrenar el modelo
    if ($registrosRestantes >= 10) {
        $this->reentrenarModelo();
    }
    
    return true;
}
```

**Método de reentrenamiento**:
```php
private function reentrenarModelo() {
    // Ruta hacia el script de reentrenamiento
    $rutaScript = dirname(__DIR__, 4) . '/microservicio/retrain.py';
    
    if (!file_exists($rutaScript)) {
        error_log("ADVERTENCIA: No se encontró el script retrain.py en: " . $rutaScript);
        return false;
    }

    // Ejecutar el script de reentrenamiento
    $comando = 'python "' . $rutaScript . '"';
    $salida = [];
    $codigoRetorno = 0;
    
    exec($comando, $salida, $codigoRetorno);
    
    if ($codigoRetorno !== 0) {
        error_log("CRítico: Error al reentrenar el modelo. Código de retorno: $codigoRetorno");
        error_log("Salida: " . implode("\n", $salida));
        return false;
    }
    
    error_log("Modelo reentrenado exitosamente después de eliminar feedback.");
    return true;
}
```

### 9.3 Integración en Controlador

**Archivo**: `Modelo/Controlador/recepcion.php`

**Caso 'anular'**:
```php
case 'anular':
    header('Content-Type: application/json; charset=utf-8');
    $k = new Recepcion();
    $correlativo = $_POST['correlativo'] ?? '';
    $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
        
    if (!$id_usuario_sesion) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error de autenticación: Sesión de usuario no válida.'
        ]);
        exit;
    }
    
    $datos_validacion = ['correlativo' => $correlativo];
    $errores = $k->validarAnular($correlativo);
    
    if (!empty($errores)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error en los datos para anular la recepción',
            'errors' => $errores
        ]);
        exit;
    }
    
    // Verificar que la recepción exista antes de anular
    $k->setcorrelativo($correlativo);
    $recepcionExistente = $k->buscar();
    if (!$recepcionExistente || $recepcionExistente['resultado'] !== 'encontró') {
        echo json_encode([
            'status' => 'error',
            'message' => 'La recepción no existe o ya fue anulada'
        ]);
        exit;
    }
    
    // Obtener id_recepcion antes de anular para eliminar del feedback
    $id_recepcion = $k->obtenerIdRecepcionPorCorrelativo($correlativo);
    
    $resultado = $k->anularRecepcion($correlativo, $id_usuario_sesion);

    if (($resultado['status'] ?? '') === 'success') {
        // Eliminar registro del feedback_dataset.csv para evitar contaminación del entrenamiento
        try {
            $k->eliminarFeedbackDataset($id_recepcion);
        } catch (Throwable $e) {
            error_log('No se pudo eliminar el registro del feedback_dataset: ' . $e->getMessage());
        }
        
        // Crear notificación
        if (!defined('SKIP_SIDE_EFFECTS')) {
            try {
                $bd_seguridad = new BD('S');
                $pdo_seguridad = $bd_seguridad->getConexion();
                $notificacionModel = new NotificacionModel($pdo_seguridad);
                $notificacionModel->crear(
                    $_SESSION['id_usuario'],
                    'recepcion',
                    'Recepción Anulada',
                    "Se ha anulado la recepción #".$correlativo." por parte del usuario ".($_SESSION['name'] ?? ''),
                    'media',
                    MODULO_RECEPCION,
                    'eliminar',
                    $id_recepcion
                );
            } catch (Throwable $e) {
                error_log('No se pudo crear la notificación de recepción anulada: ' . $e->getMessage());
            }
        }
    }
    echo json_encode($resultado);
break;
```

### 9.4 Flujo Completo

```
Usuario anula recepción
    ↓
Obtener id_recepcion
    ↓
Anular recepción en BD (estado = 'anulado')
    ↓
Eliminar registro de feedback_dataset.csv
    ↓
¿Registros restantes >= 10?
    ↓ Sí
Ejecutar retrain.py (reentrenar modelo)
    ↓
Modelo actualizado sin el registro erróneo
    ↓
Notificación creada
```

### 9.5 Beneficios

1. **Prevención de contaminación**: Registros anulados (posibles errores del usuario) no afectan el entrenamiento
2. **Reentrenamiento automático**: El modelo se actualiza automáticamente cuando hay suficientes datos
3. **Logging**: Todos los pasos se registran en logs para auditoría
4. **Manejo de errores**: Si falla la eliminación del feedback, no afecta la anulación de la recepción
5. **Dataset limpio**: Garantiza que el dataset de entrenamiento solo contenga recepciones válidas

---

## 10. Reentrenamiento del Modelo

### 10.1 Requisitos para Reentrenar

**Archivo**: `microservicio/retrain.py`

**Requisitos**:
- Al menos 10 registros en `feedback_dataset.csv`
- Python con dependencias instaladas
- El microservicio no necesita estar ejecutándose

### 10.2 Ejecutar Reentrenamiento

Desde la carpeta `microservicio`:

```powershell
python retrain.py
```

### 10.3 Proceso de Reentrenamiento

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

### 10.4 Resultado Esperado

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

### 10.5 Uso del Modelo Entrenado

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
- **Dataset limpio**: Reentrenamiento automático al eliminar registros erróneos

---

## 11. Validaciones de Factura

### 11.1 Validación de Formato de Factura

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

### 11.2 Validación de Productos en Catálogo

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

### 11.3 Bloqueo de Botón Guardar

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

## 12. Seguridad y Rate Limiting

### 12.1 Autenticación con Token

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

### 12.2 Rate Limiting con slowapi

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

### 12.3 Recomendaciones de Seguridad

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
- **Base de datos MySQL**: Configurar usuario con permisos mínimos necesarios (SELECT en tbl_productos, tbl_modelos, tbl_marcas, tbl_proveedores)

---

## 13. Métricas y Monitoreo

### 13.1 Métricas de Calidad del Sistema

**Estados del sistema**:

| Estado | Significado | Condición |
|--------|-------------|-----------|
| `online` | Servicio disponible | FastAPI ejecutándose |
| `exito` | Extracción exitosa | OCR completó sin errores |
| `discrepancias` | Diferencias detectadas | Al menos un campo con discrepancia |
| `error` | Error crítico | Excepción durante procesamiento |
| `modelo_ml_cargado` | Modelo ML disponible | modelo_actual.pkl existe y se cargó |
| `autenticacion_requerida` | Token necesario | AUDITOR_RECEPCION_TOKEN configurado |
| `adaptador_bd_activo` | Adaptador BD conectado | MySQL o CSV cargado correctamente |

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

### 13.2 Métricas a Monitorear

**Sistema**:
- CPU de FastAPI (OCR es CPU-bound)
- Memoria de FastAPI (carga de imágenes grandes)
- Latencia de endpoints (p50, p95, p99)
- Tasa de errores HTTP (4xx, 5xx)
- Tasa de errores de autenticación (403)
- **Estado de conexión MySQL**: Tiempo de respuesta, errores de conexión

**Negocio**:
- Extracciones por día
- Recepciones registradas con microservicio
- Promedio de confianza OCR
- Promedio de confiabilidad ML
- Tasa de discrepancias por campo
- Registros en feedback_dataset.csv
- **Registros eliminados de feedback**: Contador de anulaciones

**Datos**:
- Tamaño de feedback_dataset.csv
- Tamaño de modelo_actual.pkl
- Espacio en directorio temp/
- Espacio en factura_cache/
- **Tamaño de base de datos MySQL**: tbl_productos, tbl_proveedores

### 13.3 Umbrales de Alerta

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
| **Tiempo respuesta MySQL** | > 500ms | > 2s | Revisar índices, optimizar queries |
| **Errores de conexión MySQL** | > 1% | > 5% | Verificar estado del servidor MySQL |

### 13.4 Logs Implementados

**Nivel INFO**:
- Inicio de extracción OCR
- Extracción completada con confianza
- Modelo ML cargado exitosamente
- Confiabilidad ML predicha
- Verificación de coherencia
- Reentrenamiento completado
- **Adaptador de base de datos creado**
- **Catálogo cargado desde MySQL**
- **Registro eliminado de feedback**
- **Modelo reentrenado automáticamente**

**Nivel WARNING**:
- pdf2image no disponible
- Precio unitario inconsistente (recalculado)
- Insuficientes datos para reentrenamiento
- No se pudo cargar el modelo ML
- Error prediciendo confiabilidad
- **Error conectando a MySQL, usando CSV fallback**
- **No hay adaptador BD, catálogo vacío**

**Nivel ERROR**:
- Imagen no encontrada
- Factura no encontrada en caché
- Error crítico procesando archivo
- **Error al eliminar registro de feedback**
- **Error al reentrenar modelo automáticamente**

**Recomendación**:
- Agregar logs estructurados (JSON)
- Incluir correlation_id para tracing
- Centralizar logs (ELK stack o similar)
- Registrar intentos de autenticación fallidos
- **Registrar operaciones de base de datos (queries, tiempos)**

---

## 14. Pruebas y Mantenimiento

### 14.1 Estrategia de Pruebas

#### 14.1.1 Pruebas Unitarias

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

14. **test_validacion_factura**
    - Objetivo: Verificar validación de formato de factura
    - Valida: Documento no factura retorna es_factura_valida=False
    - Cobertura: Método _validar_es_factura()

15. **test_rate_limiting**
    - Objetivo: Verificar que rate limiting funciona
    - Valida: Más de 10 solicitudes/min retorna 429
    - Cobertura: Decorador @limiter.limit()

16. **test_carga_base_conocimiento_yaml** (NUEVO)
    - Objetivo: Verificar carga de archivos YAML
    - Valida: patrones, reglas y config_ml cargados correctamente
    - Cobertura: Carga de YAML

17. **test_adaptador_mysql** (NUEVO)
    - Objetivo: Verificar conexión y carga desde MySQL
    - Valida: Productos y proveedores cargados desde BD
    - Cobertura: AdaptadorMySQL

18. **test_adaptador_csv_fallback** (NUEVO)
    - Objetivo: Verificar fallback a CSV cuando MySQL falla
    - Valida: AdaptadorCSV usado cuando MySQL no disponible
    - Cobertura: FabricaAdaptadores

19. **test_eliminacion_feedback** (NUEVO)
    - Objetivo: Verificar eliminación de registro de feedback
    - Valida: Registro eliminado correctamente de CSV
    - Cobertura: del_FeedbackDataset()

20. **test_reentrenamiento_automatico** (NUEVO)
    - Objetivo: Verificar reentrenamiento automático al eliminar feedback
    - Valida: Modelo reentrenado cuando quedan >=10 registros
    - Cobertura: reentrenarModelo()

**Ejecución recomendada**:
```bash
python -m unittest microservicio.tests.test_auditor_recepcion -v
```

#### 14.1.2 Pruebas de Integración

**Prueba 1: Flujo completo JavaScript → FastAPI con autenticación**
```javascript
// Escenario: Usuario selecciona factura en formulario de recepción
// 1. JavaScript envía imagen a /api/v1/auditar con token de autenticación
// 2. FastAPI valida token
// 3. FastAPI carga base de conocimiento YAML
// 4. FastAPI carga catálogo desde MySQL
// 5. FastAPI procesa con auditor_recepcion.py
// 6. FastAPI devuelve datos extraídos + confiabilidad_ml + es_factura_valida
// 7. JavaScript rellena formulario y muestra alertas de confiabilidad
// Validar:
// - Token enviado en header X-Auditor-Token
// - Token válido permite acceso
// - Token inválido retorna 403
// - Respuesta JSON válida con datos de factura
// - Campos extraídos no vacíos
// - Confianza calculada
// - Confiabilidad ML incluida en respuesta
// - es_factura_valida incluido en respuesta
// - Productos con marca y modelo desde MySQL
// - Alertas mostradas según confiabilidad ML
// - Botón Guardar bloqueado si factura inválida
```

**Prueba 2: Validación de productos en catálogo**
- Cargar factura con productos no relacionados
- Validar: Mensaje de error, botón Guardar bloqueado

**Prueba 3: Rate limiting**
- Enviar 11 solicitudes en 1 minuto
- Validar: 11ª solicitud retorna 429

**Prueba 4: Eliminación de feedback al anular** (NUEVO)
- Registrar recepción con microservicio
- Anular recepción
- Validar: Registro eliminado de feedback_dataset.csv
- Validar: Si quedan >=10 registros, modelo reentrenado

#### 14.1.3 Pruebas de Seguridad

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

**Prueba 5: Validación de conexión MySQL** (NUEVO)
- Intentar conectar con credenciales incorrectas
- Validar: Fallback a CSV, sistema sigue funcionando

### 14.2 Mantenimiento Rutinario

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

3. **Verificar configuración de base de datos**
   ```bash
   echo $BD_DATABASE
   echo $BD_USER
   ```
   - Debe apuntar a casalai_principal

4. **Verificar conexión MySQL**
   ```bash
   mysql -u root -p casalai_principal -e "SELECT COUNT(*) FROM tbl_productos WHERE estado='habilitado';"
   ```
   - Debe retornar número de productos activos

5. **Revisar tamaño de artefactos**
   ```bash
   ls -lh microservicio/entrenamiento/feedback_dataset.csv
   ls -lh microservicio/modelo/modelo_actual.pkl
   ```
   - Alerta si > 10MB (feedback_dataset.csv)

6. **Verificar cantidad de registros de feedback**
   ```bash
   wc -l microservicio/entrenamiento/feedback_dataset.csv
   ```
   - Recomendado: Mantener al menos 10 registros

7. **Limpiar directorio temporal**
   ```bash
   rm -rf microservicio/temp/*
   ```

8. **Limpiar caché de facturas**
   - Si factura_cache/ crece demasiado
   - Eliminar entradas antiguas (> 7 días)

9. **Verificar archivos YAML** (NUEVO)
   ```bash
   ls -lh microservicio/conocimiento/*.yaml
   ```
   - Validar que existan y sean válidos

### 14.3 Mantenimiento Mensual

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
   - Verificar PATRONES en patrones_factura.yaml
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

6. **Optimización de base de datos MySQL** (NUEVO)
   - Verificar índices en tbl_productos, tbl_proveedores
   - Analizar queries lentos
   - Optimizar si es necesario

7. **Verificar integridad de catálogo** (NUEVO)
   - Validar que productos en BD coincidan con catálogo real
   - Validar que proveedores estén actualizados
   - Limpiar registros obsoletos

### 14.4 Mantenimiento Trimestral

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
   - Revisar eficiencia de PATRONES en YAML
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
   - **Auditar acceso a MySQL**: Verificar usuarios y permisos

6. **Backup de base de datos** (NUEVO)
   - Hacer backup de casalai_principal
   - Almacenar en ubicación segura
   - Probar restauración

---

## 15. Solución de Problemas

### 15.1 Problemas Frecuentes

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
| **Error conectando a MySQL** | Verificar que MySQL esté ejecutándose y credenciales correctas. |
| **Catálogo vacío** | Verificar que tbl_productos tenga registros con estado='habilitado'. |
| **Adaptador no creado** | Verificar variables de entorno BD_* configuradas correctamente. |
| **Feedback no se elimina al anular** | Verificar permisos de escritura en feedback_dataset.csv. |
| **Reentrenamiento automático falla** | Verificar que retrain.py tenga permisos de ejecución. |

### 15.2 Respuestas ante Fallos

#### 15.2.1 Fallos de Conectividad

**Detección**: Timeout al conectar con FastAPI

**Respuesta en API**:
- Activar modo contingencia
- Mostrar mensaje: "El servicio de lectura automática por IA no está disponible temporalmente. Se ha habilitado el formulario manual."

**Tratamiento en UI**:
- Deshabilitar botón de verificación IA
- Permitir ingreso manual de datos
- Guardar factura en modo contingencia

#### 15.2.2 Fallos de OCR

**Detección**: Tesseract retorna error o texto vacío

**Respuesta en API**:
- Registrar error en logs
- Retornar mensaje de error

**Tratamiento en UI**:
- Mostrar: "Error al procesar la imagen. Por favor intente con otra imagen."
- Activar modo contingencia

#### 15.2.3 Fallos del Modelo ML

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

#### 15.2.4 Fallos de Autenticación

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

#### 15.2.5 Fallos de Base de Datos MySQL (NUEVO)

**Escenario 1**: MySQL no disponible

**Detección**: Error de conexión en AdaptadorMySQL

**Respuesta**:
```python
try:
    return AdaptadorMySQL(
        host=os.getenv('BD_HOST', 'localhost'),
        port=int(os.getenv('BD_PORT', 3306)),
        user=os.getenv('BD_USER', 'root'),
        password=os.getenv('BD_PASSWORD', ''),
        database=os.getenv('BD_DATABASE', 'casalai_principal')
    )
except Exception as e:
    logger.error(f"Error conectando a MySQL: {e}")
    logger.info("Usando adaptador CSV como fallback")
    return AdaptadorCSV(
        ruta_productos=CONOCIMIENTO_DIR / 'catalogo_productos.csv'
    )
```

**Tratamiento en UI**:
- El sistema sigue funcionando con catálogo CSV
- Se registra advertencia en logs
- El usuario puede continuar usando el sistema

**Escenario 2**: Tablas no existen o estructura incorrecta

**Detección**: Error de query SQL

**Respuesta**:
- Registrar error en logs
- Usar adaptador CSV como fallback

**Tratamiento en UI**:
- El sistema sigue funcionando con catálogo CSV
- Alertar al administrador sobre estructura de BD incorrecta

#### 15.2.6 Fallos de Eliminación de Feedback (NUEVO)

**Escenario**: No se puede eliminar registro de feedback_dataset.csv

**Detección**: OSError durante escritura en PHP

**Respuesta**:
```php
try {
    $k->eliminarFeedbackDataset($id_recepcion);
} catch (Throwable $e) {
    error_log('No se pudo eliminar el registro del feedback_dataset: ' . $e->getMessage());
}
```

**Tratamiento**:
- La anulación de la recepción continúa
- Se registra error en logs
- Alertar al administrador para limpieza manual del dataset

**Recomendación**:
- Verificar permisos de escritura en feedback_dataset.csv
- Implementar reintentos con backoff
- Alertar al administrador después de múltiples fallos

#### 15.2.7 Compromiso de Seguridad (Token filtrado)

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

## 16. Archivos del Microservicio y su Rol

### 16.1 Archivos ACTIVOS en el flujo

| Archivo | Rol | Estado |
|---------|-----|--------|
| `recepcion.js` | Frontend JavaScript | ✅ Activo - Maneja formulario y envía al microservicio |
| `asistente_recepcion.js` | Cliente JS del microservicio | ✅ Activo - Interfaz con API FastAPI, envía token |
| `api_recepcion.py` | Controlador API FastAPI | ✅ Activo - Endpoints con autenticación y rate limiting |
| `auditor_recepcion.py` | Modelo OCR y extracción | ✅ Activo - Tesseract + regex + predicción ML + validaciones |
| `adaptador_bd.py` | Adaptador de base de datos | ✅ Activo - MySQL + CSV, carga catálogo dinámico |
| `recepcion.php` (controlador) | Backend PHP | ✅ Activo - Registra recepción y captura feedback |
| `Recepcion.php` (modelo) | Modelo PHP | ✅ Activo - `agregarFeedbackDataset()`, `eliminarFeedbackDataset()` |
| `feedback_dataset.csv` | Dataset de feedback | ✅ Activo - Acumula predicciones vs ground truth |
| `retrain.py` | Script de reentrenamiento | ✅ Activo - Entrena RandomForest con 1 feature |
| `modelo_actual.pkl` | Modelo entrenado | ✅ Activo - Usado en extracción |
| `main.py` | Punto de entrada FastAPI | ✅ Activo - Configura rate limiting global |
| `config.py` | Configuración Pydantic | ✅ Activo - Carga tokens desde variables de entorno |
| `.env` | Variables de entorno | ✅ Activo - Almacena tokens y configuración BD para FastAPI |
| `.htaccess` | Configuración Apache | ✅ Activo - Define token para PHP |
| `patrones_factura.yaml` | Patrones regex | ✅ Activo - Define patrones de extracción |
| `reglas_validacion.yaml` | Reglas de validación | ✅ Activo - Define reglas de factura válida |
| `configuracion_ml.yaml` | Configuración ML | ✅ Activo - Define hiperparámetros del modelo |
| `catalogo_productos.csv` | Catálogo fallback | ✅ Activo - Catálogo estático cuando MySQL falla |

### 16.2 Archivos NO UTILIZADOS actualmente

| Archivo | Rol | Estado |
|---------|-----|--------|
| `feedback_store.py` | Almacenamiento de feedback en JSONL | ❌ No usado - PHP escribe directamente al CSV |
| `model_registry.py` | Gestión de versiones de modelos | ❌ No usado - Modelo se guarda directamente como .pkl |
| `entrenar_modelo.py` | Orquestador de reentrenamiento | ❌ No usado - `retrain.py` hace el reentrenamiento |
| `logger.py` | Función de logging de predicciones | ❌ No usado - PHP escribe directamente al CSV |

---

## 17. Comparación de Versiones

### 17.1 Cambios Principales

| Aspecto | Versión 1 | Versión 2 | Versión 3 | Versión 4 |
|---------|-----------|-----------|-----------|-----------|
| Features de entrenamiento | 6 (errores + confianza) | 1 (solo confianza) | 1 (solo confianza) | 1 (solo confianza) |
| Modelo en inferencia | No usado | Sí usado | Sí usado | Sí usado |
| Autenticación API | No | Token HMAC | Token HMAC | Token HMAC |
| Alertas de confiabilidad | No | Sí (basadas en ML) | Sí (basadas en ML) | Sí (basadas en ML) |
| Validación de factura | No | No | Sí | Sí |
| Validación de catálogo | No | No | Sí (CSV estático) | Sí (MySQL dinámico) |
| Base de conocimiento | Hardcoded | Hardcoded | YAML externo | YAML externo + MySQL |
| Rate limiting | No | No | Sí (slowapi) | Sí (slowapi) |
| Bloqueo de botón Guardar | No | No | Sí | Sí |
| Degradación sin modelo | No definido | Graceful | Graceful | Graceful |
| Patrones regex | Básicos | Mejorados | Expandidos (internacional) | Expandidos (en YAML) |
| Parsing de montos | Básico | Mejorado | Robusto (múltiples formatos) | Robusto (múltiples formatos) |
| Eliminación de feedback | No | No | No | Sí (al anular) |
| Reentrenamiento automático | Manual | Manual | Manual | Automático |
| Adaptador de BD | No | No | No | Sí (MySQL + CSV) |
| Fallback a CSV | No | No | No | Sí |

### 17.2 Beneficios de la Actualización

1. **Predicción en tiempo real**: El modelo ahora predice la confiabilidad durante la extracción
2. **Mejor experiencia de usuario**: Alertas contextuales ayudan al usuario a identificar extracciones problemáticas
3. **Mayor seguridad**: Autenticación protege los endpoints del microservicio
4. **Protección contra abuso**: Rate limiting previene uso excesivo de la API
5. **Validación inteligente**: Detecta documentos que no son facturas válidas
6. **Integración con catálogo dinámico**: Verifica productos en base de datos MySQL en tiempo real
7. **Prevención de errores**: Bloquea registro con facturas inválidas
8. **Simplificación**: Menos features = entrenamiento más rápido y más fácil de mantener
9. **Resiliencia**: El sistema puede funcionar sin el modelo ML si es necesario
10. **Soporte internacional**: Patrones regex expandidos para diferentes formatos de factura
11. **Configuración externa**: Patrones y reglas en YAML editables sin modificar código
12. **Catálogo siempre actualizado**: Productos y proveedores desde MySQL en tiempo real
13. **Arquitectura extensible**: Sistema de adaptadores permite integrar con otros sistemas
14. **Dataset limpio**: Eliminación automática de feedback erróneo al anular recepciones
15. **Reentrenamiento automático**: Modelo se actualiza automáticamente cuando se elimina feedback
16. **Fallback robusto**: Si MySQL falla, usa CSV automáticamente

---

## 18. Próximos Pasos Recomendados

### 18.1 Mejoras Técnicas

1. **Implementar validación explícita del modelo**
   - Agregar partición train/test
   - Calcular métricas de generalización
   - Implementar validación temporal

2. **Mejorar patrones regex**
   - Agregar más patrones para diferentes formatos de factura
   - Probar con muestras reales de proveedores variados
   - Mantener patrones en YAML para fácil edición

3. **Implementar pruebas unitarias**
   - Crear `microservicio/tests/test_auditor_recepcion.py`
   - Probar extracción con imágenes de prueba
   - Probar verificación de coherencia
   - Probar predicción ML
   - Probar validación de factura
   - Probar rate limiting
   - **Probar adaptador de base de datos**
   - **Probar eliminación de feedback**

4. **Implementar monitoreo avanzado**
   - Configurar alertas para latencia alta
   - Monitorear tasa de discrepancias por campo
   - Revisar confianza OCR promedio periódicamente
   - Monitorear confiabilidad ML promedio
   - Monitorear tasa de errores 403
   - **Monitorear conexión MySQL**

5. **Optimizar queries MySQL**
   - Agregar índices en tbl_productos, tbl_proveedores
   - Analizar performance de queries
   - Implementar caché de catálogo si es necesario

### 18.2 Mejoras de Negocio

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
   - **Alertar cuando MySQL no esté disponible**

### 18.3 Mejoras de Seguridad

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

4. **Seguridad de MySQL**
   - Crear usuario específico para el microservicio con permisos mínimos
   - Encriptar conexión SSL/TLS
   - Implementar rotación de credenciales

---

## 19. Referencias

### 19.1 Documentación Relacionada

- `DOCUMENTO_TECNICO_UNIFICADO_V3.md` - Documento técnico anterior (Versión 3)
- `DOCUMENTO_TECNICO_CAPITULO_IV_V2.md` - Documento técnico anterior (Versión 2)
- `GUIA_ENTRENAMIENTO_V2.md` - Guía de entrenamiento anterior (Versión 2)
- `requirements.txt` - Dependencias Python del microservicio
- `main.py` - Punto de entrada de FastAPI
- `config.py` - Configuración de Pydantic Settings
- `adaptador_bd.py` - Adaptador de base de datos (NUEVO)

### 19.2 Enlaces Útiles

- [FastAPI Documentation](https://fastapi.tiangolo.com/)
- [Tesseract OCR Documentation](https://tesseract-ocr.github.io/)
- [scikit-learn Documentation](https://scikit-learn.org/)
- [slowapi Documentation](https://slowapi.readthedocs.io/)
- [PyYAML Documentation](https://pyyaml.org/)
- [MySQL Connector Python](https://dev.mysql.com/doc/connector-python/en/)

---

**Versión del documento**: 4.0  
**Fecha de actualización**: Octubre 2026  
**Estado**: Activo - Refleja el estado actual del microservicio con base de conocimiento externa y adaptador de base de datos
