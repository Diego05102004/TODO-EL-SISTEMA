# Analítica predictiva en productos

## Estado e intención

La vista `Vista/VistaNew/producto.php` consume un MVP de analítica local. La API ya entrega demanda estimada únicamente para modelos entrenados con suficiente historial; no presenta predicciones inventadas si el modelo no existe o los datos son insuficientes.

## Fuente de ventas acordada

La única fuente oficial de demanda es `tbl_facturas` + `tbl_factura_detalle`, con estos estados:

- `Pagada`
- `Pagada Presencialmente`
- `Pago Procesado` (el flujo de pagos también guarda este estado en `tbl_facturas.estatus`).

Se excluyen borradores, facturas canceladas y otros estados. `tbl_despacho_detalle` no se mezcla: el esquema no enlaza sus registros de producto con `tbl_facturas`, por lo que no es posible deduplicar las dos fuentes de manera fiable. La vista PHP envía al microservicio IDs, cantidades, fechas y metadatos del inventario; no envía nombres ni datos personales de clientes.

## Flujo de datos en orden

1. **Interfaz — `Vista/VistaNew/producto.php`:** el usuario selecciona un producto o una categoría, frecuencia diaria/semanal/mensual y horizonte de 15/30/60/90 días. La vista envía `accion=analitica_dashboard` al controlador de la página y representa estados de carga, errores, historial insuficiente y resultados.
2. **Autorización — `Modelo/Controlador/producto.php`:** las consultas y valoraciones exigen permiso `consultar`; el botón de entrenamiento y su acción exigen `modificar`. El controlador valida los filtros y decide qué operación ejecutar.
3. **Lectura histórica — `Modelo/Usuario/ProyectoCasalaiCa/Clases/Productos.php::obtenerDatosAnaliticaPredictiva`:** una consulta preparada recupera hasta 365 días de facturas pagadas y agrega cantidades por factura, fecha y producto. Otra consulta obtiene stock, mínimo, máximo y categoría de productos habilitados.
4. **Proxy de mismo host — `Modelo/Servicio/AnaliticaPredictivaProxy.php`:** PHP entrega el conjunto al servicio por HTTP local con `ANALITICA_PREDICTIVA_TOKEN`; el navegador nunca conecta directamente a FastAPI ni recibe el secreto.
5. **API — `microservicio/controlador/api_analitica.py`:** valida el token, filtros y estructura de los datos antes de llamar a la lógica predictiva.
6. **Entrenamiento — `microservicio/modelo/analitica_predictiva.py::train_models`:** el botón autorizado entrena modelos Ridge autoregresivos por producto y categoría. Cada modelo usa las últimas 24 semanas completas y cuatro semanas anteriores como variables; requiere al menos seis semanas con ventas. Guarda coeficientes y metadatos en `microservicio/analitica_predictiva/modelos/demanda.json`, fuera de Git.
7. **Inferencia — `build_dashboard`:** calcula el histórico, carga el artefacto local y proyecta la demanda recursivamente. Sin modelo o con historial insuficiente, `prediccion` queda vacío y las métricas dependientes del forecast quedan `null`.
8. **Recomendaciones:** se devuelve el listado completo de productos clasificados con stock congelado junto con stock, excedente, rotación, días sin venta y categoría; está disponible también cuando no hay ventas elegibles para el pronóstico. La vista conserva el listado y los combos estratégicos del análisis general para no perder esas recomendaciones al filtrar el pronóstico por producto. Al seleccionar un producto congelado, solo presenta combos estratégicos asociados a ese producto; el backend genera sus combos aunque no tenga ventas propias y no mezcla sugerencias de venta cruzada ni reposición en ese filtro. El límite general de diez congelados no aplica al producto seleccionado. Antes de mostrar una propuesta, el controlador PHP busca un combo existente con exactamente el mismo conjunto de productos; si lo encuentra, reemplaza la tarjeta de sugerencia por los datos del combo, su composición, descuento, estado y ventas de facturas pagadas, con acciones de gestión según permisos. El descuento vigente se toma de `tbl_combo.descuento`, no del texto histórico de la descripción. Al ajustar el descuento desde CAIN, el backend compara el precio actual de cada producto con el costo unitario de su recepción habilitada más reciente y calcula el precio por unidad con el descuento para cada componente. CAIN advierte si algún componente queda por debajo de su costo, muestra el margen bruto total y el descuento máximo aproximado antes de que un producto quede bajo costo; si falta el historial de recepción de algún producto, informa que la rentabilidad no puede confirmarse y pide confirmación para continuar. El cálculo no incluye impuestos ni otros gastos operativos. Las recomendaciones generales se ordenan primero por combos estratégicos para esos productos, luego por reposición y finalmente por venta cruzada; dentro de cada grupo se conserva la prioridad ajustada. Las parejas de venta cruzada se detectan por coocurrencia en una misma factura pagada (mínimo dos facturas). Las señales de reposición comparan stock real, mínimo configurado y demanda prevista. El cálculo de compra sugerida es `max(0, demanda del horizonte + stock mínimo - stock actual)`; no descuenta órdenes abiertas porque no existe una fuente conectada para ellas.
9. **Retroalimentación — `/api/v1/analitica/feedback`:** se guardan como JSONL la decisión de aprobar/rechazar, estrellas, motivo, descuento propuesto/aplicado y respuesta sobre el descuento. Las decisiones y respuestas de descuento no requieren estrellas; solo los eventos que incluyen una valoración numérica participan en el promedio de estrellas. Las valoraciones y la proporción personal de aprobaciones/rechazos ajustan la prioridad relativa por tipo; las respuestas «mucho»/«poco» ajustan el descuento de combos estratégicos para ese usuario en un máximo de ±3 puntos porcentuales (`3 × promedio de preferencias × n/(n+2)`). «Adecuado» aporta una señal neutra. El efecto se ve en la siguiente consulta del mismo usuario. El texto libre del motivo se guarda para análisis cualitativo, no se interpreta automáticamente. El feedback no reemplaza la medición de ventas ni vuelve a entrenar Ridge.
10. **Interacción CAIN:** `producto.php` presenta un asistente accesible con los estados alegre, pensativo, entusiasmado y triste. El JavaScript actualiza su diálogo durante la carga/análisis, tras crear un combo y tras recibir un rechazo o una valoración baja. Las ilustraciones adjuntas se sirven desde `microservicio/analitica_predictiva/CAIN (ESTADO-{ALEGRE,PENSATIVO,ENTUSIASMADO,TRISTE}).jpg`; si falta alguna, la vista conserva un avatar textual de respaldo.

## Contrato del dashboard

Endpoint interno: `POST /api/v1/analitica/dashboard`.

El endpoint requiere `X-Analitica-Token` y recibe un único `producto_id` o `categoria_id`, `frecuencia`, `horizonte`, el catálogo habilitado y líneas históricas de facturas. Su respuesta incluye:

- `historico`: cantidades reales agregadas según frecuencia.
- `prediccion`: demanda estimada y límites inferior/superior; banda ilustrativa `estimación ± 1.96 × RMSE`.
- `metricas`: venta proyectada, días estimados hasta agotamiento, punto de reorden, compra sugerida y rotación. Cuando falta un dato necesario se conserva `null`.
- Al consultar una categoría, la demanda sí se agrega, pero las métricas de inventario quedan `null`: el stock de distintos productos no es intercambiable. Las recomendaciones de reposición siguen calculándose por producto.
- `calidad.estado`: `disponible`, `modelo_pendiente`, `insuficiente_historial`, `sin_ventas` (el filtro coincide con productos, pero no hay ventas elegibles) o `sin_datos` (ningún producto coincide con el filtro).
- `recomendaciones`: señal, producto, cantidad cuando aplica, confianza, fecha de cálculo y clave para valorar.
- El dashboard también recibe el ID del usuario autenticado desde PHP (nunca confía en un ID enviado por el navegador) para aplicar su preferencia de descuento.

## Metodología de aprendizaje y reglas de combos

El sistema es **híbrido y semi-automatizado con Human-in-the-Loop**:

- **Demanda (supervisado):** `train_models` ajusta un modelo Ridge autoregresivo independiente por producto y categoría. La variable objetivo son las unidades vendidas por semana y las cuatro semanas completas previas son las características. Usa 24 semanas completas, al menos seis semanas con ventas y una penalización Ridge pequeña para estabilizar coeficientes. El entrenamiento es local y se inicia manualmente por un usuario con permiso de modificación; consultar el dashboard sí es automático al abrir Productos.
- **Asociaciones (no supervisado/descriptivo):** se cuentan pares de productos presentes en una misma factura pagada. Se muestran con al menos dos facturas conjuntas, soporte y confianza por antecedente. La implementación actual es conteo directo de pares, no Apriori ni FP-Growth: el límite de hasta 100.000 líneas permite el cálculo en este MVP y evita afirmar que se ejecuta un algoritmo que no está implementado. Para historiales mayores convendría evaluar FP-Growth.
- **Combo para stock lento:** reglas deterministas identifican exceso frente al stock mínimo, baja rotación en el periodo y pocas ventas recientes. Los candidatos a pareja se rankean por facturas distintas y unidades vendidas en los últimos 90 días; se prefieren productos que no estén clasificados como congelados y, si no hay candidatos alternativos, se usa el ranking disponible. Así, una venta activa puede competir aunque no alcance un umbral fijo de cinco líneas o una rotación por stock arbitrariamente alta. La coocurrencia histórica se usa para calcular la confianza cuando existe; si no existe, la confianza se deriva del ranking de popularidad. El descuento base parte de 15%, sube según el potencial de desbloqueo y queda limitado a 40%; la preferencia del usuario lo ajusta suavemente. Son reglas heurísticas de negocio, no un clasificador entrenado.
- **Descuento y priorización con feedback:** cada decisión/respuesta se registra como evento auditable en JSONL. La última respuesta de un usuario por recomendación evita contar clics repetidos como muestras independientes. Un rechazo/valoración baja reduce la prioridad del tipo de recomendación; «mucho» baja y «poco» eleva gradualmente el descuento recomendado para ese usuario. Los feedbacks no modifican retroactivamente la venta real ni actualizan pesos Ridge en línea.

**Justificación de selección:** Ridge se ajusta al historial corto y disperso disponible: es rápido, determinista, barato de recalcular y la regularización reduce coeficientes extremos entre cuatro rezagos correlacionados. Un árbol de decisión tendería a crear cortes inestables con solo 24 observaciones semanales; redes profundas y modelos estacionales más complejos requieren más series, temporadas o covariables de las que provee este contrato. Para asociaciones, contar pares es fácil de auditar y suficiente para el catálogo acotado del MVP; Apriori puede generar muchos candidatos y FP-Growth es una alternativa de escala cuando crezca el volumen, no una dependencia que el servicio ya esté usando.

Por tanto, el aprendizaje no es aprendizaje por refuerzo: no existe política de acciones, recompensa observada a largo plazo ni exploración. El componente supervisado aprende ventas al pulsar entrenamiento; las reglas de asociación describen compras históricas; la persona valida recomendaciones y el sistema ajusta heurísticas limitadas a partir de sus respuestas. No se crean combos sin confirmación: CAIN requiere aprobación y la creación continúa por el controlador existente de combos.

La banda usa el error residual del entrenamiento y no es una garantía de cobertura estadística. La rotación se expresa como demanda proyectada dividida entre inventario promedio estimado para el horizonte de un producto. La confianza de reposición es una heurística basada en el error del modelo frente a la demanda semanal media; la confianza de combos es la frecuencia conjunta relativa a la frecuencia de uno de los productos. Ninguna está calibrada como probabilidad. Las reglas son orientativas y deben revisarse con el negocio antes de usarse para emitir órdenes automáticas.

## Operación local

1. Define el mismo secreto aleatorio, privado y de al menos 32 caracteres en el entorno de PHP/Apache y FastAPI como `ANALITICA_PREDICTIVA_TOKEN`. Define en PHP `ANALITICA_PREDICTIVA_URL=http://127.0.0.1:8000`.
2. Inicia FastAPI en loopback: desde `microservicio`, ejecuta `uvicorn controlador.api_recepcion:app --host 127.0.0.1 --port 8000`.
3. Abre Productos con un usuario con permiso de consulta. Selecciona un producto o categoría y consulta primero las ventas históricas.
4. Un usuario con permiso de modificación puede pulsar **Entrenar modelo local**. Una vez terminado, vuelve a consultar para ver el pronóstico. El entrenamiento refresca el archivo local, omite los productos que no alcanzan el mínimo y reporta cuántos no pudieron entrenarse.
5. Aprueba o rechaza combos estratégicos, indica si el descuento es adecuado/mucho/poco y, opcionalmente, deja una valoración de estrellas y motivo. La retroalimentación queda en un log local excluido de Git y se aplica en la siguiente consulta del usuario.

Si el servicio, token o cURL no están configurados, la vista informa el error y el inventario normal sigue disponible. No se requieren dependencias nuevas: la implementación usa NumPy, que ya forma parte de `microservicio/requirements.txt`.

## Límites del MVP

- La base no aporta órdenes abiertas ni lead time de proveedor a este dashboard. El mínimo de inventario ya configurado se usa como punto de reorden; la cantidad sugerida advierte que no descuenta órdenes pendientes.
- La valoración es una ponderación de prioridad por tipo, no una señal causal de ventas; el ajuste de descuento es una preferencia declarada, no una estimación de elasticidad.
- El entrenamiento supervisado local es un Ridge autoregresivo ligero, no un LLM ni una red neuronal. La calidad debe evaluarse con datos reales antes de automatizar decisiones.
- Los análisis de abastecimiento y tiempos de despacho de proveedor quedan fuera de este dashboard porque no hay fecha de solicitud de compra para medir el lead time.
