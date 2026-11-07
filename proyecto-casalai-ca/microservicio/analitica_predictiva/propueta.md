## Descripcion del Microservicio de Analitica Predictiva

Dentro de la vista de Productos debe haber un contenedor que muestre un analisis de los productos que han sido vendidos, los que no, cuales se suelen vender juntos para dar recomendaciones al usuario sobre que combos se deben de crear, lo que se busca con este microservicio es un microservicio que analise los patrones de compra de los usuarios, para ayudar a la comunidad a mejorar sus ventas y evitar el congelamiento de los productos en stock

## Requerimientos de la Universidad

El microservicio debe de ser "inteligente" aplicando patrones de busqueda de informacion de la IA y debe poder entrenarse para que tenga una evolucion visible (Requiero instrucciones por separado en formato pdf de como se lleva a cabo este entrenamiento para una IA local), Utilizar python, PHP, javascript y otras herramientas que ayuden con la predicion

## Clases de la Documentacion

para cumplir con la documentacion tecnica de los diagramas de la propuesta se deben de trabajar con 5 Clases: 

1. Clase: AnalisAbastecimiento

• Atributos: Ninguno
• Métodos / Operaciones:
	• +analizarComprasProveedor(id_producto:int, id_proveedor:int)
	• +evaluarTiemposDespacho(id_despacho:int)

2. Clase: AnalistaVentas

• Atributos:
	• +UMBRAL_CONFIANZA_ALTA
	• +UMBRAL_CONFIANZA_MEDIA
	• +PATRONES
	• +configuracion
• Métodos / Operaciones:
	• +obtenerPrediccionDemanda(id_producto:int)
	• +obtenerPatronesCompraCliente(id_cliente:int)
	• +obtenerReporteRendimientoProveedor(id_proveedor:int)

3. Clase: PatronCompra

• Atributos: Ninguno
• Métodos / Operaciones:
	• +analizarHabitosCliente(cliente_Id)
	• +predecirProximaCompra(cliente_Id)

4. Clase: ServicioPromociones

• Atributos:
	• +historicoPromociones: List<MetricasPromocion>
	• +umbralEfectividadMinima: float
	• +topCombosActuales: List<int>
• Métodos / Operaciones:
	• +evaluarRendimientoCombo(id_combo:int)
	• +sugerirNuevosCombos(id_cliente:int)
	• +predecirImpactoDescuento(id_producto:int, descuento:float)
	• +identificarCombosEstancados()

5. Clase: LogicaPredictiva

• Atributos: Ninguno
• Métodos / Operaciones:
	• +entrenarModeloPatrones(datosHistoricos:List<HistoricoVentas>)
	• +ejecutarInferencia(input:Datoscliente)

## Ruta de la Vista que utilizara el microservicio de Analisis Predictivo

Vista/VistaNew/producto.php

## Tablas de la Base de Datos Involucrada

`tbl_productos` (`id_producto` int(11) NOT NULL,`serial` varchar(20) NOT NULL,`nombre_producto` varchar(20) NOT NULL,`descripcion_producto` varchar(255) DEFAULT NULL,`id_modelo` int(11) DEFAULT NULL,`id_categoria` int(2) DEFAULT NULL,`stock` int(3) DEFAULT NULL,`stock_minimo` int(3) DEFAULT NULL,`stock_maximo` int(3) DEFAULT NULL,`clausula_garantia` varchar(150) NOT NULL,`precio` float(10,2) DEFAULT NULL,`estado` varchar(20) DEFAULT '1',`imagen` varchar(255) DEFAULT NULL COMMENT 'Ruta de la imagen del producto en formato IMGProductosproducto_X.jpeg donde X es el id_producto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

`tbl_combo` (`id_combo` int(11) NOT NULL,`nombre_combo` varchar(255) NOT NULL,`descripcion` text DEFAULT NULL,`fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),`activo` tinyint(1) DEFAULT 1
)

`tbl_combo_detalle` (
  `id_detalle` int(11) NOT NULL,`id_combo` int(11) NOT NULL,`id_producto` int(11) NOT NULL,`cantidad` int(11) NOT NULL DEFAULT 1
)

-- Tablas con el prefijo de cat_[nombre] que son las tablas donde se guardan las caracteristicas del producto de acuerdo a su categoria, Ejemplo: un producto de la categoria impresora, tiene sus caracteristicas especificas en la tabla cat_impresora

`tbl_recepcion_productos` (
  `id_recepcion` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `correlativo` varchar(255) NOT NULL,
    `estado` enum('habilitado','anulado') NOT NULL DEFAULT 'habilitado',
    `subtotal_factura` decimal(12,2) DEFAULT NULL,
    `porcentaje_iva` decimal(7,2) DEFAULT NULL,
    `monto_iva` decimal(12,2) DEFAULT NULL,
    `total_factura` decimal(12,2) DEFAULT NULL)

  `tbl_detalle_recepcion_productos` (
  `id_detalle_recepcion_productos` int(11) NOT NULL,
  `id_recepcion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
    `costo` decimal(12,2) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
)

`tbl_facturas` (
  `id_factura` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `cliente` int(11) NOT NULL,
  `descuento` int(3) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Borrador'
)
`tbl_factura_detalle` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` int(11) NOT NULL
)

`tbl_clientes` (
  `id_clientes` int(11) NOT NULL,
  `nombre` varchar(600) DEFAULT NULL,
  `cedula` varchar(10) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(600) DEFAULT NULL,
  `correo` varchar(600) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL
)

`tbl_despachos` (
  `id_despachos` int(11) NOT NULL,
  `id_clientes` int(11) NOT NULL,
  `fecha_despacho` date NOT NULL,
  `tipocompra` varchar(10) NOT NULL,
  `estado` enum('Por Despachar','Despachado') NOT NULL DEFAULT 'Por Despachar',
  `activo` tinyint(1) DEFAULT NULL
)

`tbl_despacho_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_despacho` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
)

## Idea de Entrenamiento de componente Inteligente (Corrigeme si hay algun error en la idea);

El componente a traves de una card le proporcionara al usuario la creacion de un combo, modificacion de precio u otro consejo en base a los patrones de compra de los clientes y a los de recepcion de productos de la empresa, esto con el motivo de que no haya productos en stock congelado ni que se generen perdidas en la empresa, el usuario ve la respuesta e interactua dandole un valor de 1 a 5 "estrellas", el componente guarda en un archivo log la propuesta generada y la retroalimentacion del usuario, permitiendo que al momento de generar otra propuesta analice las retroalimentaciones de las respuestas anteriores ycrea las propuestas en base al administrador

## Especificación del dashboard

- Chart.js (ya utilizado en reportes PHP) con histórico real como línea continua y predicción como línea discontinua.
- Banda de incertidumbre sombreada entre límites inferior y superior; indicar unidades y horizonte en tooltip/leyenda.
- KPI de venta proyectada, días hasta agotamiento, punto de reorden, cantidad sugerida y rotación, con unidades y explicación breve.
- Filtros dependientes de producto/categoría, frecuencia y horizontes de 15/30/60/90 días.
- Estados de carga, sin datos, historial insuficiente y error de API; no presentar valores nulos como cero.
- Tabla de acciones recomendadas con producto, señal, cantidad sugerida, nivel de confianza y fecha de cálculo.

La vista PHP y su conexión con Chart.js deben implementarse cuando se definan la fuente histórica y el endpoint; hasta entonces, esta especificación es un contrato de trabajo y no una predicción disponible para usuarios.