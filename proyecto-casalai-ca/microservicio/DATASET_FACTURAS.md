# Ficha de caracterización del dataset de facturas

Documento base para el capítulo de desarrollo/metodología. Los datos de volumen y distribución deben completarse con el inventario real del corpus; no deben presentarse las proporciones objetivo como resultados medidos.

## Caracterización

| Parámetro | Descripción / especificación |
| --- | --- |
| Propósito | Extraer y verificar número de factura, proveedor, fecha, productos, cantidades, precios unitarios, subtotales e impuestos/total cuando estén presentes. |
| Unidad de análisis | Un documento de factura. Las páginas de un mismo PDF pertenecen al mismo documento y no se cuentan como muestras independientes. |
| Origen | Facturas reales de proveedores de Casa Lai, anonimizadas y autorizadas para uso interno; completar con datasets públicos solo tras comprobar licencia, idioma y adecuación al dominio. CORD y SROIE son referencias para recibos/documentos estructurados, no sustitutos automáticos de facturas venezolanas. |
| Volumen medido actual | No hay un corpus versionado con ground truth ni inventario formal; N y sus porcentajes todavía no se pueden reportar. Los archivos temporales usados en pruebas OCR no cuentan como muestras de entrenamiento hasta verificarlos, anonimizarlos y etiquetarlos. |
| Formatos | PDF digital, PDF escaneado, imagen escaneada y fotografía de factura impresa. Registrar formato y resolución de cada archivo. |
| Variabilidad por proveedor | No estandarizada. Clasificar cada plantilla como rígida/estandarizada, semiestructurada o variable; anotar proveedor y versión de plantilla sin incluirlos en conjuntos públicos. |
| Distribución objetivo | Meta inicial: aproximadamente 70% de documentos digitales y 30% escaneados/fotografiados. Es una proporción recomendada, no un resultado observado. |
| Distribución medida | Pendiente de inventariar y clasificar el corpus; reportar porcentajes reales por separado de la meta objetivo, con fecha de corte y denominador N. |
| Etiquetado | Ground truth en JSON por documento: campos de cabecera y arreglo de ítems; conservar offsets/cajas de texto cuando se entrene detección de layout. Registrar responsable, fecha y estado de revisión. |
| Partición | 70% entrenamiento, 15% validación y 15% prueba como punto de partida. Separar por proveedor/plantilla y, cuando aplique, por período; nunca repartir páginas o copias del mismo documento entre particiones. |

## Esquema de etiquetado

Propuesta de estructura, ampliable según los campos que efectivamente aparezcan en las facturas:

```json
{
  "document_id": "identificador_anonimo",
  "tipo_documento": "factura",
  "formato": "pdf_digital",
  "proveedor_id": "proveedor_anonimizado",
  "numero_factura": "F-000123",
  "rif_proveedor": "J-00000000-0",
  "fecha": "2026-01-31",
  "items": [
    {
      "descripcion": "Producto de ejemplo",
      "cantidad": 2,
      "precio_unitario": 25.5,
      "subtotal": 51.0
    }
  ],
  "iva": 6.12,
  "total": 57.12,
  "revision": {
    "estado": "validado",
    "anotadores": 2
  }
}
```

El esquema actual del modelo Python (`FacturaExtraida`) soporta subtotal, una tasa y un monto de IVA y total de factura; `ProductoExtraido` representa importe unitario por línea. Todavía no soporta RIF ni impuestos múltiples/exentos por línea, por lo que esos campos del ejemplo son una propuesta de etiquetado, no capacidades disponibles.

## Pipeline y rol de CNN

La implementación actual usa OpenCV para preprocesamiento, Tesseract para OCR y expresiones regulares heurísticas para extraer campos. En imágenes, el pipeline convierte a escala de grises, aplica reducción de ruido Non-Local Means, mejora contraste con CLAHE y usa umbral adaptativo antes de Tesseract (`spa+eng`, `--psm 6 --oem 3`). Para PDF convierte cada página con `pdf2image`/Poppler y procesa las páginas con el mismo flujo OCR. La extracción de campos y líneas de producto se hace después con patrones/reglas heurísticas.

No contiene una CNN entrenada ni un clasificador de documentos. Por tanto, el informe debe describir CNN como una mejora futura, no como tecnología actualmente implementada.

En una evolución del pipeline, una CNN puede clasificar facturas frente a notas de entrega/recibos o detectar regiones de interés (cabecera, tabla de ítems y totales). OCR/LayoutLM/EasyOCR o un modelo multimodal se encargaría luego de transcribir y asociar texto con la estructura. Esta separación evita entrenar un extractor rígido para cada proveedor.

La corrección de orientación/inclinación no está implementada actualmente. Se puede evaluar como mejora futura junto con la detección de regiones; documentar los métodos y parámetros por experimento y medir si la binarización deteriora tipografías o fondos tenues.

## Control de calidad y evaluación

- Verificar doblemente un subconjunto de etiquetas y resolver desacuerdos antes de cerrar el ground truth.
- Reportar precisión, recall y F1 por campo; exactitud de coincidencia completa para número de factura/proveedor y métricas CER/WER para texto OCR.
- Reportar errores de cantidad y precio por separado, incluyendo tasa de campos ausentes y documentos que requieren revisión manual.
- Comparar resultados por tipo de formato y proveedor para detectar degradación en plantillas poco representadas.
- Definir los umbrales de aceptación después de medir una línea base representativa; la confianza OCR no reemplaza la verificación humana.

## Privacidad y trazabilidad

Anonimizar identificadores personales y datos bancarios no requeridos. Limitar acceso a originales y etiquetas, registrar propósito/consentimiento de uso, controlar retención y eliminación, y mantener identificadores anonimizados para relacionar etiqueta, predicción y resultado de auditoría. Para datos públicos, conservar la cita y cumplir la licencia original.