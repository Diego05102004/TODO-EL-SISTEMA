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
8. **Recomendaciones:** las parejas de productos se detectan por coocurrencia en una misma factura pagada (mínimo dos facturas). Las señales de reposición comparan stock real, mínimo configurado y demanda prevista. El cálculo de compra sugerida es `max(0, demanda del horizonte + stock mínimo - stock actual)`; no descuenta órdenes abiertas porque no existe una fuente conectada para ellas.
9. **Retroalimentación — `/api/v1/analitica/feedback`:** las estrellas 1–5 se guardan como JSONL en `microservicio/logs_metricas/feedback_analitica.jsonl`. La última valoración de cada usuario para cada recomendación se incorpora al promedio por tipo (`combo`/`reposicion`) para ajustar la prioridad relativa futura; no reemplaza la medición de ventas ni vuelve a entrenar el modelo de demanda.

## Contrato del dashboard

Endpoint interno: `POST /api/v1/analitica/dashboard`.

El endpoint requiere `X-Analitica-Token` y recibe un único `producto_id` o `categoria_id`, `frecuencia`, `horizonte`, el catálogo habilitado y líneas históricas de facturas. Su respuesta incluye:

- `historico`: cantidades reales agregadas según frecuencia.
- `prediccion`: demanda estimada y límites inferior/superior; banda ilustrativa `estimación ± 1.96 × RMSE`.
- `metricas`: venta proyectada, días estimados hasta agotamiento, punto de reorden, compra sugerida y rotación. Cuando falta un dato necesario se conserva `null`.
- Al consultar una categoría, la demanda sí se agrega, pero las métricas de inventario quedan `null`: el stock de distintos productos no es intercambiable. Las recomendaciones de reposición siguen calculándose por producto.
- `calidad.estado`: `disponible`, `modelo_pendiente`, `insuficiente_historial`, `sin_ventas` (el filtro coincide con productos, pero no hay ventas elegibles) o `sin_datos` (ningún producto coincide con el filtro).
- `recomendaciones`: señal, producto, cantidad cuando aplica, confianza, fecha de cálculo y clave para valorar.

La banda usa el error residual del entrenamiento y no es una garantía de cobertura estadística. La rotación se expresa como demanda proyectada dividida entre inventario promedio estimado para el horizonte de un producto. La confianza de reposición es una heurística basada en el error del modelo frente a la demanda semanal media; la confianza de combos es la frecuencia conjunta relativa a la frecuencia de uno de los productos. Ninguna está calibrada como probabilidad. Las reglas son orientativas y deben revisarse con el negocio antes de usarse para emitir órdenes automáticas.

## Operación local

1. Define el mismo secreto aleatorio, privado y de al menos 32 caracteres en el entorno de PHP/Apache y FastAPI como `ANALITICA_PREDICTIVA_TOKEN`. Define en PHP `ANALITICA_PREDICTIVA_URL=http://127.0.0.1:8000`.
2. Inicia FastAPI en loopback: desde `microservicio`, ejecuta `uvicorn controlador.api_recepcion:app --host 127.0.0.1 --port 8000`.
3. Abre Productos con un usuario con permiso de consulta. Selecciona un producto o categoría y consulta primero las ventas históricas.
4. Un usuario con permiso de modificación puede pulsar **Entrenar modelo local**. Una vez terminado, vuelve a consultar para ver el pronóstico. El entrenamiento refresca el archivo local, omite los productos que no alcanzan el mínimo y reporta cuántos no pudieron entrenarse.
5. Valora las recomendaciones con estrellas. La retroalimentación queda en un log local excluido de Git.

Si el servicio, token o cURL no están configurados, la vista informa el error y el inventario normal sigue disponible. No se requieren dependencias nuevas: la implementación usa NumPy, que ya forma parte de `microservicio/requirements.txt`.

## Límites del MVP

- La base no aporta órdenes abiertas ni lead time de proveedor a este dashboard. El mínimo de inventario ya configurado se usa como punto de reorden; la cantidad sugerida advierte que no descuenta órdenes pendientes.
- La valoración es una ponderación de prioridad por tipo, no una señal causal de ventas.
- El entrenamiento supervisado local es un Ridge autoregresivo ligero, no un LLM ni una red neuronal. La calidad debe evaluarse con datos reales antes de automatizar decisiones.
- Los análisis de abastecimiento y tiempos de despacho de proveedor quedan fuera de este dashboard porque no hay fecha de solicitud de compra para medir el lead time.
