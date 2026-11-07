# Texto para la memoria técnica

## Capítulo de Arquitectura de Software: degradación grácil

La lectura automática de facturas se integra como una capacidad auxiliar, no como requisito de disponibilidad para registrar una recepción. La solución aplica degradación grácil (*Graceful Degradation*): si el servicio de IA no responde, el formulario conserva los datos ingresados, permite continuar manualmente con confirmación y envía el archivo junto con el registro para su resguardo y auditoría.

La capa PHP actúa como proxy de mismo origen y *circuit breaker*. El proxy usa un timeout total de **7,5 segundos**, realiza como máximo un reintento acotado ante errores transitorios de conexión/HTTP y abre el circuito después de **3 fallos consecutivos**. El período de enfriamiento configurado es de **120 segundos**; mientras está abierto, PHP responde sin iniciar otra llamada remota. El estado se comparte entre procesos PHP del mismo host mediante un archivo bloqueado durante su actualización. El navegador agrega un timeout de **8 segundos** y un corte rápido por navegador.

Errores de conexión, timeout, HTTP 408/429 y respuestas 5xx habilitan el modo manual. La indisponibilidad no equivale a una discrepancia de datos: una discrepancia crítica en una verificación exitosa sigue bloqueando el alta. La confirmación manual y el registro diferido se deben auditar por separado.

La API predictiva no forma parte de esta integración. El reporte actual de rotación es descriptivo; no debe presentarse como forecast ni como cálculo de agotamiento o reorden.

## Capítulo de Seguridad: archivos adjuntos

El backend PHP valida el tamaño máximo de **5 MB** y el MIME detectado por Fileinfo frente a una lista permitida de PDF e imágenes. No usa el nombre de archivo enviado por el usuario como ruta: construye un nombre aleatorio con proveedor/correlativo saneados y una extensión determinada por el MIME.

Los adjuntos se guardan en `comprobantes/recepcion/`, fuera de Git y con acceso web directo denegado por `.htaccess`; la configuración de Apache debe permitir las reglas de este archivo (`AllowOverride` compatible). La regla también deniega extensiones ejecutables. En sistemas POSIX el directorio se crea con modo **0750** y el archivo con **0640**. En Windows/XAMPP esos bits no sustituyen una ACL NTFS: el despliegue debe conceder lectura/escritura solo a la identidad de Apache y a los operadores autorizados. La configuración PHP debe permitir al menos 5 MB por archivo (`upload_max_filesize`) y un `post_max_size` mayor que el archivo más los campos del formulario.

Cada archivo tiene un manifiesto JSON con correlativo, proveedor, usuario, hash SHA-256, fecha, estado e identificador de recepción cuando el alta concluye. El manifiesto sirve como catálogo auditable. El estado `pendiente_ocr` identifica documentos conservados durante una contingencia; **no implica que exista un worker batch**. La retención, revisión, reprocesamiento y eliminación de pendientes deben definirse como procedimiento operativo.

## Capítulo de Inteligencia Artificial / OCR: dataset y pipeline

Las facturas varían por proveedor y por formato. La [ficha de caracterización](DATASET_FACTURAS.md) separa los datos medidos de los objetivos: el corpus etiquetado todavía no está inventariado, por lo que no se declara un N ni una distribución observada. El reparto recomendado de **70% digital / 30% escaneado o fotografiado** es una meta inicial, no una medición. Antes de entrenar, se debe cerrar el inventario, anonimizar, etiquetar, revisar ground truth y separar entrenamiento/validación/prueba por proveedor o plantilla para evitar fuga de datos.

El pipeline implementado es **PDF → conversión de cada página con `pdf2image`/Poppler; imagen → OpenCV (escala de grises, reducción de ruido, CLAHE y umbral adaptativo); ambos → Tesseract (`spa+eng`, `--psm 6 --oem 3`) → extracción heurística mediante patrones/reglas → autocompletado y verificación asistida**. Actualmente no hay una CNN entrenada, clasificación documental ni detector de regiones. Si se incorporan CNN, su función propuesta es clasificar tipos documentales o localizar regiones (cabecera, tabla, totales); OCR/layout o un modelo multimodal se ocuparía de reconocer y asociar el contenido.

La extracción fiscal actual reconoce subtotal, una tasa de IVA, el monto del impuesto y el total de factura. En cada nueva recepción se persisten `subtotal_factura`, `porcentaje_iva`, `monto_iva` y `total_factura`; el servidor calcula el monto con los productos y la tasa recibida. Las columnas se agregan mediante `agregar_iva_recepcion.sql`. Las recepciones anteriores quedan con valores `NULL`: sin la factura original no se puede reconstruir si tenían IVA ni cuánto era, por lo que la interfaz muestra «No registrado» en vez de inferirlo.

La validación final y los umbrales deben medirse por campo, formato y proveedor. La confianza OCR es una señal para priorizar revisión, no garantía de exactitud.