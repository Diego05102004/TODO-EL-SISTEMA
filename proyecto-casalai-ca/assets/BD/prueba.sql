-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-10-2025 a las 03:02:29
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `prueba`
--
CREATE DATABASE IF NOT EXISTS `casalai` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `casalai`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_cartucho_de_tinta`
--

CREATE TABLE `cat_cartucho_de_tinta` (
  `id` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_cartucho_de_tinta`
--

INSERT INTO `cat_cartucho_de_tinta` (`id`, `id_producto`, `numero`, `color`, `capacidad`) VALUES
('Cartucho-0001', 'Prod-0007', 1004, 'Multicolor', 1000),
('Cartucho-0002', 'Prod-0008', 1005, 'Multicolor', 1000),
('Cartucho-0003', 'Prod-0009', 1006, 'Multicolor', 1500);

--
-- Disparadores `cat_cartucho_de_tinta`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarCartuchoID` BEFORE INSERT ON `cat_cartucho_de_tinta` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id IS NULL OR NEW.id = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id, 'Cartucho-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM cat_cartucho_de_tinta
    WHERE id LIKE 'Cartucho-%';

    SET nuevoStr = CONCAT('Cartucho-', LPAD(nuevo, 4, '0'));
    SET NEW.id = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_impresoras`
--

CREATE TABLE `cat_impresoras` (
  `id` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `peso` float DEFAULT NULL,
  `alto` float DEFAULT NULL,
  `ancho` float DEFAULT NULL,
  `largo` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_impresoras`
--

INSERT INTO `cat_impresoras` (`id`, `id_producto`, `peso`, `alto`, `ancho`, `largo`) VALUES
('Impresora-0001', 'Prod-0001', 10, 10, 10, 10),
('Impresora-0002', 'Prod-0002', 20, 20, 20, 20),
('Impresora-0003', 'Prod-0003', 30, 15, 15, 15);

--
-- Disparadores `cat_impresoras`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarImpresoraID` BEFORE INSERT ON `cat_impresoras` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id IS NULL OR NEW.id = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id, 'Impresora-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM cat_impresoras
    WHERE id LIKE 'Impresora-%';

    SET nuevoStr = CONCAT('Impresora-', LPAD(nuevo, 4, '0'));
    SET NEW.id = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_otros`
--

CREATE TABLE `cat_otros` (
  `id` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_otros`
--

INSERT INTO `cat_otros` (`id`, `id_producto`, `descripcion`) VALUES
('Otro-0001', 'Prod-0013', 'De Acero Inoxidable'),
('Otro-0002', 'Prod-0014', 'Tamaño 4A');

--
-- Disparadores `cat_otros`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarOtroID` BEFORE INSERT ON `cat_otros` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id IS NULL OR NEW.id = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id, 'Otro-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM cat_otros
    WHERE id LIKE 'Otro-%';

    SET nuevoStr = CONCAT('Otro-', LPAD(nuevo, 4, '0'));
    SET NEW.id = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_protector_de_voltaje`
--

CREATE TABLE `cat_protector_de_voltaje` (
  `id` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `voltaje_de_entrada` varchar(50) DEFAULT NULL,
  `voltaje_de_salida` varchar(50) DEFAULT NULL,
  `tomas` int(11) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_protector_de_voltaje`
--

INSERT INTO `cat_protector_de_voltaje` (`id`, `id_producto`, `voltaje_de_entrada`, `voltaje_de_salida`, `tomas`, `capacidad`) VALUES
('Protector-0001', 'Prod-0010', '1200W', '800W', 3, 3),
('Protector-0002', 'Prod-0011', '1500W', '1000W', 1, 5),
('Protector-0003', 'Prod-0012', '3200W', '1800W', 6, 12);

--
-- Disparadores `cat_protector_de_voltaje`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarProtectorID` BEFORE INSERT ON `cat_protector_de_voltaje` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id IS NULL OR NEW.id = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id, 'Protector-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM cat_protector_de_voltaje
    WHERE id LIKE 'Protector-%';

    SET nuevoStr = CONCAT('Protector-', LPAD(nuevo, 4, '0'));
    SET NEW.id = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_tintas`
--

CREATE TABLE `cat_tintas` (
  `id` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `volumen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_tintas`
--

INSERT INTO `cat_tintas` (`id`, `id_producto`, `numero`, `color`, `tipo`, `volumen`) VALUES
('Tinta-0001', 'Prod-0004', 1001, 'Multicolor', 'Líquidas', 100),
('Tinta-0002', 'Prod-0005', 1002, 'Multicolor', 'Líquidas', 450),
('Tinta-0003', 'Prod-0006', 1003, 'Multicolor', 'Inyección', 750);

--
-- Disparadores `cat_tintas`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarTintaID` BEFORE INSERT ON `cat_tintas` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id IS NULL OR NEW.id = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id, 'Tinta-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM cat_tintas
    WHERE id LIKE 'Tinta-%';

    SET nuevoStr = CONCAT('Tinta-', LPAD(nuevo, 4, '0'));
    SET NEW.id = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dolar_cache`
--

CREATE TABLE `dolar_cache` (
  `id` int(11) NOT NULL,
  `precio` decimal(10,4) NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_carrito`
--

CREATE TABLE `tbl_carrito` (
  `id_carrito` varchar(20) NOT NULL,
  `id_cliente` varchar(20) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_carrito`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarCarritoID` BEFORE INSERT ON `tbl_carrito` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_carrito IS NULL OR NEW.id_carrito = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_carrito, 'Carrito-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_carrito
    WHERE id_carrito LIKE 'Carrito-%';

    SET nuevoStr = CONCAT('Carrito-', LPAD(nuevo, 4, '0'));
    SET NEW.id_carrito = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_carritodetalle`
--

CREATE TABLE `tbl_carritodetalle` (
  `id_carrito_detalle` varchar(20) NOT NULL,
  `id_carrito` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `estatus` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_carritodetalle`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarCarritoDetalleID` BEFORE INSERT ON `tbl_carritodetalle` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_carrito_detalle IS NULL OR NEW.id_carrito_detalle = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_carrito_detalle, 'CartDet-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_carritodetalle
    WHERE id_carrito_detalle LIKE 'CartDet-%';

    SET nuevoStr = CONCAT('CartDet-', LPAD(nuevo, 4, '0'));
    SET NEW.id_carrito_detalle = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_categoria`
--

CREATE TABLE `tbl_categoria` (
  `id_categoria` varchar(20) NOT NULL,
  `nombre_categoria` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_categoria`
--

INSERT INTO `tbl_categoria` (`id_categoria`, `nombre_categoria`) VALUES
('Cat-0001', 'Impresoras'),
('Cat-0002', 'Tintas'),
('Cat-0003', 'Cartucho de Tinta'),
('Cat-0004', 'Protector de Voltaje'),
('Cat-0005', 'Otros');

--
-- Disparadores `tbl_categoria`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarCategoriaID` BEFORE INSERT ON `tbl_categoria` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_categoria IS NULL OR NEW.id_categoria = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_categoria, 'Cat-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_categoria
    WHERE id_categoria LIKE 'Cat-%';

    SET nuevoStr = CONCAT('Cat-', LPAD(nuevo, 4, '0'));
    SET NEW.id_categoria = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_clientes`
--

CREATE TABLE `tbl_clientes` (
  `id_clientes` varchar(20) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_clientes`
--

INSERT INTO `tbl_clientes` (`id_clientes`, `nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo`) VALUES
('Cliente-0001', 'Gabriel Mujica', '29958676', 'Carrera 25 entre calles 23 y 24 Casa N5', '0424-678-8765', 'gmujica12345@gmail.com', 1),
('Cliente-0002', 'Edith Urdaneta', '10844463', 'Los Horcones', '0416-747-4336', 'urdavedith.pnfi@gmail.com', 1),
('Cliente-0003', 'Diego Lopez', '31766917', 'Venezuela estado Zulia\r\nMaracaibo', '0414-575-3363', 'diego0510lopez@gmail.com', 1);

--
-- Disparadores `tbl_clientes`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarClienteID` BEFORE INSERT ON `tbl_clientes` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_clientes IS NULL OR NEW.id_clientes = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_clientes, 'Cliente-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_clientes
    WHERE id_clientes LIKE 'Cliente-%';

    SET nuevoStr = CONCAT('Cliente-', LPAD(nuevo, 4, '0'));
    SET NEW.id_clientes = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_combo`
--

CREATE TABLE `tbl_combo` (
  `id_combo` varchar(20) NOT NULL,
  `nombre_combo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_combo`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarComboID` BEFORE INSERT ON `tbl_combo` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_combo IS NULL OR NEW.id_combo = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_combo, 'Combo-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_combo
    WHERE id_combo LIKE 'Combo-%';

    SET nuevoStr = CONCAT('Combo-', LPAD(nuevo, 4, '0'));
    SET NEW.id_combo = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_combo_detalle`
--

CREATE TABLE `tbl_combo_detalle` (
  `id_detalle` varchar(20) NOT NULL,
  `id_combo` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_combo_detalle`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarComboDetalleID` BEFORE INSERT ON `tbl_combo_detalle` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_detalle IS NULL OR NEW.id_detalle = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_detalle, 'CombDet-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_combo_detalle
    WHERE id_detalle LIKE 'CombDet-%';

    SET nuevoStr = CONCAT('CombDet-', LPAD(nuevo, 4, '0'));
    SET NEW.id_detalle = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_cuentas`
--

CREATE TABLE `tbl_cuentas` (
  `id_cuenta` varchar(20) NOT NULL,
  `nombre_banco` varchar(20) NOT NULL,
  `numero_cuenta` varchar(25) DEFAULT NULL,
  `rif_cuenta` varchar(15) NOT NULL,
  `telefono_cuenta` varchar(15) DEFAULT NULL,
  `correo_cuenta` varchar(50) DEFAULT NULL,
  `metodos` set('Pago Movil','Transferencia','Zelle','Efectivo $','Efectivo') NOT NULL,
  `estado` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_cuentas`
--

INSERT INTO `tbl_cuentas` (`id_cuenta`, `nombre_banco`, `numero_cuenta`, `rif_cuenta`, `telefono_cuenta`, `correo_cuenta`, `metodos`, `estado`) VALUES
('Cuenta-0001', 'Caja en Bs', NULL, 'J406452157', NULL, NULL, 'Efectivo', 'habilitado'),
('Cuenta-0002', 'Caja en $', NULL, 'J406452157', NULL, NULL, 'Efectivo $', 'habilitado'),
('Cuenta-0003', 'Banesco', '1234567890', '0123456789', '0990812808', 'ejemplo@gmail.com', 'Transferencia', 'habilitado'),
('Cuenta-0004', 'Bancamiga', '1234-5678-90-5857575765', 'J-01234567-8', '0990-812-8088', 'ejemplo@gmail.com68', 'Pago Movil', 'habilitado'),
('Cuenta-0005', 'Venezuela', '87654321', '0123456789', '04141580151', 'ejemplo@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
('Cuenta-0006', 'Mercantil', '1247-8624-44-4444355559', 'J-12345678-9', '0414-158-0151', 'diego0510lopez@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
('Cuenta-0007', 'Zelle', '1247-8624-56-0876896596', 'J-12345678-9', '0414-158-0151', 'diego0510lopez@gmail.com', 'Zelle', 'habilitado'),
('Cuenta-0008', 'BNC', '1247862', '143123423442', '24141243241', 'EJEMPLO@GMAIL.COM', 'Pago Movil,Transferencia', 'habilitado');

--
-- Disparadores `tbl_cuentas`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarCuentaID` BEFORE INSERT ON `tbl_cuentas` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_cuenta IS NULL OR NEW.id_cuenta = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_cuenta, 'Cuenta-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_cuentas
    WHERE id_cuenta LIKE 'Cuenta-%';

    SET nuevoStr = CONCAT('Cuenta-', LPAD(nuevo, 4, '0'));
    SET NEW.id_cuenta = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_despachos`
--

CREATE TABLE `tbl_despachos` (
  `id_despachos` varchar(20) NOT NULL,
  `id_clientes` varchar(20) NOT NULL,
  `fecha_despacho` date NOT NULL,
  `tipocompra` varchar(10) NOT NULL,
  `estado` enum('Por Despachar','Despachado') NOT NULL DEFAULT 'Por Despachar',
  `activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_despachos`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarDespachoID` BEFORE INSERT ON `tbl_despachos` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_despachos IS NULL OR NEW.id_despachos = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_despachos, 'Desp-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_despachos
    WHERE id_despachos LIKE 'Desp-%';

    SET nuevoStr = CONCAT('Desp-', LPAD(nuevo, 4, '0'));
    SET NEW.id_despachos = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_despacho_detalle`
--

CREATE TABLE `tbl_despacho_detalle` (
  `id_detalle` varchar(20) NOT NULL,
  `id_despacho` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_despacho_detalle`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarDespachoDetalleID` BEFORE INSERT ON `tbl_despacho_detalle` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_detalle IS NULL OR NEW.id_detalle = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_detalle, 'DespDet-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_despacho_detalle
    WHERE id_detalle LIKE 'DespDet-%';

    SET nuevoStr = CONCAT('DespDet-', LPAD(nuevo, 4, '0'));
    SET NEW.id_detalle = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_detalles_pago`
--

CREATE TABLE `tbl_detalles_pago` (
  `id_detalles` varchar(20) NOT NULL,
  `id_factura` varchar(20) NOT NULL,
  `id_cuenta` varchar(20) NOT NULL,
  `observaciones` varchar(200) NOT NULL,
  `referencia` varchar(30) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `monto` float(8,2) NOT NULL,
  `comprobante` varchar(255) NOT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'En Proceso'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_detalles_pago`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarDetallePagoID` BEFORE INSERT ON `tbl_detalles_pago` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_detalles IS NULL OR NEW.id_detalles = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_detalles, 'PagoDet-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_detalles_pago
    WHERE id_detalles LIKE 'PagoDet-%';

    SET nuevoStr = CONCAT('PagoDet-', LPAD(nuevo, 4, '0'));
    SET NEW.id_detalles = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_detalle_recepcion_productos`
--

CREATE TABLE `tbl_detalle_recepcion_productos` (
  `id_detalle_recepcion_productos` varchar(20) NOT NULL,
  `id_recepcion` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `costo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_detalle_recepcion_productos`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarDetalleRecepcionID` BEFORE INSERT ON `tbl_detalle_recepcion_productos` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_detalle_recepcion_productos IS NULL OR NEW.id_detalle_recepcion_productos = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_detalle_recepcion_productos, 'RecDet-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_detalle_recepcion_productos
    WHERE id_detalle_recepcion_productos LIKE 'RecDet-%';

    SET nuevoStr = CONCAT('RecDet-', LPAD(nuevo, 4, '0'));
    SET NEW.id_detalle_recepcion_productos = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_facturas`
--

CREATE TABLE `tbl_facturas` (
  `id_factura` varchar(20) NOT NULL,
  `fecha` date NOT NULL,
  `cliente` varchar(20) NOT NULL,
  `descuento` int(3) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Borrador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_facturas`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarFacturaID` BEFORE INSERT ON `tbl_facturas` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_factura IS NULL OR NEW.id_factura = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_factura, 'Fact-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_facturas
    WHERE id_factura LIKE 'Fact-%';

    SET nuevoStr = CONCAT('Fact-', LPAD(nuevo, 4, '0'));
    SET NEW.id_factura = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_factura_detalle`
--

CREATE TABLE `tbl_factura_detalle` (
  `id` varchar(20) NOT NULL,
  `factura_id` varchar(20) NOT NULL,
  `id_producto` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_factura_detalle`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarFacturaDetalleID` BEFORE INSERT ON `tbl_factura_detalle` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id IS NULL OR NEW.id = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id, 'FactDet-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_factura_detalle
    WHERE id LIKE 'FactDet-%';

    SET nuevoStr = CONCAT('FactDet-', LPAD(nuevo, 4, '0'));
    SET NEW.id = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ingresos_egresos`
--

CREATE TABLE `tbl_ingresos_egresos` (
  `id_finanzas` varchar(20) NOT NULL,
  `id_despacho` varchar(20) DEFAULT NULL,
  `id_detalle_recepcion_productos` varchar(20) DEFAULT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `monto` float(6,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha` date NOT NULL,
  `estado` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_ingresos_egresos`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarFinanzaID` BEFORE INSERT ON `tbl_ingresos_egresos` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_finanzas IS NULL OR NEW.id_finanzas = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_finanzas, 'Finanza-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_ingresos_egresos
    WHERE id_finanzas LIKE 'Finanza-%';

    SET nuevoStr = CONCAT('Finanza-', LPAD(nuevo, 4, '0'));
    SET NEW.id_finanzas = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_marcas`
--

CREATE TABLE `tbl_marcas` (
  `id_marca` varchar(20) NOT NULL,
  `nombre_marca` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_marcas`
--

INSERT INTO `tbl_marcas` (`id_marca`, `nombre_marca`) VALUES
('Marca-0001', 'Epson'),
('Marca-0002', 'HP'),
('Marca-0003', 'Canon'),
('Marca-0004', 'Inktec'),
('Marca-0005', 'TexPrint'),
('Marca-0006', 'Sawgrass'),
('Marca-0007', 'Cosmos Ink'),
('Marca-0008', 'Azon'),
('Marca-0009', 'Sublimagic'),
('Marca-0010', 'Brother'),
('Marca-0011', 'Forza'),
('Marca-0012', 'Tripp Lite'),
('Marca-0013', 'CDP'),
('Marca-0014', 'Koblenz'),
('Marca-0015', 'Epson'),
('Marca-0016', 'HP'),
('Marca-0017', 'Canon'),
('Marca-0018', 'Inktec'),
('Marca-0019', 'TexPrint'),
('Marca-0020', 'Sawgrass'),
('Marca-0021', 'Cosmos Ink'),
('Marca-0022', 'Azon'),
('Marca-0023', 'Sublimagic'),
('Marca-0024', 'Brother'),
('Marca-0025', 'Forza'),
('Marca-0026', 'Tripp Lite'),
('Marca-0027', 'CDP'),
('Marca-0028', 'Koblenz'),
('Marca-0029', 'Pokemon'),
('Marca-0030', 'Digimon');

--
-- Disparadores `tbl_marcas`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarMarcaID` BEFORE INSERT ON `tbl_marcas` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_marca IS NULL OR NEW.id_marca = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_marca, 'Marca-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_marcas
    WHERE id_marca LIKE 'Marca-%';

    SET nuevoStr = CONCAT('Marca-', LPAD(nuevo, 4, '0'));
    SET NEW.id_marca = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_modelos`
--

CREATE TABLE `tbl_modelos` (
  `id_modelo` varchar(20) NOT NULL,
  `nombre_modelo` varchar(25) NOT NULL,
  `id_marca` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_modelos`
--

INSERT INTO `tbl_modelos` (`id_modelo`, `nombre_modelo`, `id_marca`) VALUES
('Modelo-0001', 'L32508', NULL),
('Modelo-0002', 'L32106', NULL),
('Modelo-0003', 'L8055', NULL),
('Modelo-0004', 'L18001', NULL),
('Modelo-0005', 'L13001', NULL),
('Modelo-0006', 'F170911', 'Marca-0002'),
('Modelo-0007', 'F5709', 'Marca-0002'),
('Modelo-0008', 'Smart Tank 515', 'Marca-0002'),
('Modelo-0009', 'DeskJet 2775', 'Marca-0002'),
('Modelo-0010', 'LaserJet Pro M404dn', 'Marca-0002'),
('Modelo-0011', 'PIXMA G3110', 'Marca-0003'),
('Modelo-0012', 'PIXMA G6010', 'Marca-0003'),
('Modelo-0013', 'i-SENSYS MF445dw', 'Marca-0003'),
('Modelo-0014', 'Sublinova', 'Marca-0004'),
('Modelo-0015', 'SubliJet', 'Marca-0006'),
('Modelo-0016', 'L3250', 'Marca-0001'),
('Modelo-0017', 'L3210', 'Marca-0001'),
('Modelo-0018', 'L805', 'Marca-0001'),
('Modelo-0019', 'L1800', 'Marca-0001'),
('Modelo-0020', 'L1300', 'Marca-0001'),
('Modelo-0021', 'F170', 'Marca-0001'),
('Modelo-0022', 'F570', 'Marca-0001'),
('Modelo-0023', 'Smart Tank 515', 'Marca-0002'),
('Modelo-0024', 'DeskJet 2775', 'Marca-0002'),
('Modelo-0025', 'LaserJet Pro M404dn', 'Marca-0002'),
('Modelo-0026', 'PIXMA G3110', 'Marca-0003'),
('Modelo-0027', 'PIXMA G6010', 'Marca-0003'),
('Modelo-0028', 'i-SENSYS MF445dw', 'Marca-0003'),
('Modelo-0029', 'Sublinova', 'Marca-0004'),
('Modelo-0030', 'SubliJet', 'Marca-0006'),
('Modelo-0031', 'Sublime', 'Marca-0008'),
('Modelo-0032', 'Durabrite', 'Marca-0015'),
('Modelo-0033', 'Innobella', 'Marca-0010'),
('Modelo-0034', 'ChromaLife 100+', 'Marca-0003'),
('Modelo-0035', 'T664', 'Marca-0001'),
('Modelo-0036', 'T673', 'Marca-0001'),
('Modelo-0037', 'T774', 'Marca-0001'),
('Modelo-0038', '664', 'Marca-0002'),
('Modelo-0039', '662', 'Marca-0002'),
('Modelo-0040', '680', 'Marca-0002'),
('Modelo-0041', '955', 'Marca-0002'),
('Modelo-0042', '950', 'Marca-0002'),
('Modelo-0043', 'PG-145', 'Marca-0003'),
('Modelo-0044', 'CL-146', 'Marca-0003'),
('Modelo-0045', 'GI-190', 'Marca-0003'),
('Modelo-0046', 'FVR-1211', 'Marca-0011'),
('Modelo-0047', 'FVR-2202', 'Marca-0011'),
('Modelo-0048', 'LR2000', 'Marca-0012'),
('Modelo-0049', 'AVR750U', 'Marca-0012'),
('Modelo-0050', 'R2-1200', 'Marca-0013'),
('Modelo-0051', 'UPS 600VA', 'Marca-0013'),
('Modelo-0052', '1000VA', 'Marca-0013'),
('Modelo-0053', 'AVR-1000', 'Marca-0014'),
('Modelo-0054', '520 Joules', 'Marca-0014'),
('Modelo-0055', 'Sublime', 'Marca-0008'),
('Modelo-0056', 'Durabrite', 'Marca-0015'),
('Modelo-0057', 'Innobella', 'Marca-0010'),
('Modelo-0058', 'ChromaLife 100+', 'Marca-0003'),
('Modelo-0059', 'T664', 'Marca-0001'),
('Modelo-0060', 'T673', 'Marca-0001'),
('Modelo-0061', 'T774', 'Marca-0001'),
('Modelo-0062', '664', 'Marca-0002'),
('Modelo-0063', '662', 'Marca-0002'),
('Modelo-0064', '680', 'Marca-0002'),
('Modelo-0065', '955', 'Marca-0002'),
('Modelo-0066', '950', 'Marca-0002'),
('Modelo-0067', 'PG-145', 'Marca-0003'),
('Modelo-0068', 'CL-146', 'Marca-0003'),
('Modelo-0069', 'GI-190', 'Marca-0003'),
('Modelo-0070', 'FVR-1211', 'Marca-0011'),
('Modelo-0071', 'FVR-2202', 'Marca-0011'),
('Modelo-0072', 'LR2000', 'Marca-0012'),
('Modelo-0073', 'AVR750U', 'Marca-0012'),
('Modelo-0074', 'R2-1200', 'Marca-0013'),
('Modelo-0075', 'UPS 600VA', 'Marca-0013'),
('Modelo-0076', '1000VA', 'Marca-0013'),
('Modelo-0077', 'AVR-1000', 'Marca-0014'),
('Modelo-0078', '520 Joulesj', 'Marca-0003'),
('Modelo-0079', 'Ejemplo', 'Marca-0003');

--
-- Disparadores `tbl_modelos`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarModeloID` BEFORE INSERT ON `tbl_modelos` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_modelo IS NULL OR NEW.id_modelo = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_modelo, 'Modelo-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_modelos
    WHERE id_modelo LIKE 'Modelo-%';

    SET nuevoStr = CONCAT('Modelo-', LPAD(nuevo, 4, '0'));
    SET NEW.id_modelo = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_orden_despachos`
--

CREATE TABLE `tbl_orden_despachos` (
  `id_orden_despachos` varchar(20) NOT NULL,
  `id_factura` varchar(20) NOT NULL,
  `cliente` varchar(50) NOT NULL,
  `fecha_despacho` date NOT NULL,
  `estado` enum('Por Entregar','Entregada') NOT NULL DEFAULT 'Por Entregar',
  `activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_orden_despachos`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarOrdenDespachoID` BEFORE INSERT ON `tbl_orden_despachos` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_orden_despachos IS NULL OR NEW.id_orden_despachos = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_orden_despachos, 'OrdDesp-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_orden_despachos
    WHERE id_orden_despachos LIKE 'OrdDesp-%';

    SET nuevoStr = CONCAT('OrdDesp-', LPAD(nuevo, 4, '0'));
    SET NEW.id_orden_despachos = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_productos`
--

CREATE TABLE `tbl_productos` (
  `id_producto` varchar(20) NOT NULL,
  `serial` varchar(20) NOT NULL,
  `nombre_producto` varchar(20) NOT NULL,
  `descripcion_producto` varchar(255) DEFAULT NULL,
  `id_modelo` varchar(20) DEFAULT NULL,
  `id_categoria` varchar(20) DEFAULT NULL,
  `stock` int(3) DEFAULT NULL,
  `stock_minimo` int(3) DEFAULT NULL,
  `stock_maximo` int(3) DEFAULT NULL,
  `clausula_garantia` varchar(150) NOT NULL,
  `precio` float(10,2) DEFAULT NULL,
  `estado` varchar(20) DEFAULT '1',
  `imagen` varchar(255) DEFAULT NULL COMMENT 'Ruta de la imagen del producto en formato IMGProductosproducto_X.jpeg donde X es el id_producto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_productos`
--

INSERT INTO `tbl_productos` (`id_producto`, `serial`, `nombre_producto`, `descripcion_producto`, `id_modelo`, `id_categoria`, `stock`, `stock_minimo`, `stock_maximo`, `clausula_garantia`, `precio`, `estado`, `imagen`) VALUES
('Prod-0001', '0001', 'Impresora Super', 'Impresora multifuncional con función Wi-Fi', 'Modelo-0001', 'Cat-0001', 50, 10, 100, 'Garantía válida por 3 meses', 1000.00, 'habilitado', 'assets/img/productos/producto_28.jpg'),
('Prod-0002', '0002', 'Impresora Maxi', 'Impresora con punta de fibra de vidrio para oficina', 'Modelo-0002', 'Cat-0001', 50, 10, 100, 'Garantía por 1 mes', 1500.00, 'habilitado', 'assets/img/productos/impresora_maxi.jpg'),
('Prod-0003', '0003', 'Impresora KING', 'Impresora láser de última generación con escáner', 'Modelo-0003', 'Cat-0001', 50, 10, 100, 'Garantía válida por 365 días', 2000.00, 'habilitado', 'assets/img/productos/impresora_king.jpg'),
('Prod-0004', '0004', 'Colormedia', 'Tintas multicolor para impresoras Epson', 'Modelo-0004', 'Cat-0002', 20, 10, 50, 'Sin garantía', 10.00, 'habilitado', 'assets/img/productos/tinta_colormedia.jpg'),
('Prod-0005', '0005', 'Tinta Arcoíris', 'Tintas de múltiples colores duraderas para impresoras', 'Modelo-0005', 'Cat-0002', 20, 5, 50, 'Sin garantía', 8.00, 'habilitado', 'assets/img/productos/tinta_arcoiris.jpg'),
('Prod-0006', '0006', 'ImpriColor', 'Tintas profesionales de 4 colores', 'Modelo-0006', 'Cat-0002', 30, 10, 70, 'Sin garantía', 12.00, 'habilitado', 'assets/img/productos/impricolor.jpg'),
('Prod-0007', '0007', 'Caja de Color', 'Cartuchos de tinta para impresión', 'Modelo-0007', 'Cat-0003', 10, 5, 20, 'Garantía de 1 mes de duración', 120.00, 'habilitado', 'assets/img/productos/caja_color.jpg'),
('Prod-0008', '0008', 'ColorBox', 'Cartuchos de tinta profesional tamaño XL', 'Modelo-0008', 'Cat-0003', 7, 5, 20, 'Garantía de 1 mes de duración', 100.00, 'habilitado', 'assets/img/productos/colorbox.jpg'),
('Prod-0009', '0009', 'Colors Pandora', 'Cartuchos de tinta para impresoras HP', 'Modelo-0009', 'Cat-0003', 10, 5, 25, 'Garantía de 1 mes de duración', 130.00, 'habilitado', 'assets/img/productos/colors_pandora.jpg'),
('Prod-0010', '0010', 'GigaVoltio', 'Protector de voltaje para uso doméstico', 'Modelo-0010', 'Cat-0004', 12, 10, 40, 'Garantía de 1 mes de duración', 60.00, 'habilitado', 'assets/img/productos/gigavoltio.jpg'),
('Prod-0011', '0011', 'ProtecVoltorb', 'Protector de voltaje para neveras', 'Modelo-0011', 'Cat-0004', 16, 5, 20, 'Garantía de 1 mes de duración', 25.00, 'habilitado', 'assets/img/productos/protecvoltorb.jpg'),
('Prod-0012', '0012', 'ThunderBolt', 'Protector de voltaje de uso empresarial', 'Modelo-0012', 'Cat-0004', 7, 3, 15, 'Garantía de 1 mes de duración', 250.00, 'habilitado', 'assets/img/productos/thunderbolt.jpg'),
('Prod-0013', '0013', 'Clips de papel', 'Clips para actividades académicas', 'Modelo-0013', 'Cat-0005', 20, 10, 100, 'Sin garantía', 5.00, 'habilitado', 'assets/img/productos/clips_papel.jpg'),
('Prod-0014', '0014', 'Rema de Papel', 'Rema de papel de oficina con 200 hojas blancas', 'Modelo-0014', 'Cat-0005', 15, 5, 50, 'Sin garantía', 3.00, 'habilitado', 'assets/img/productos/rema_papel.jpg');

--
-- Disparadores `tbl_productos`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarProductoID` BEFORE INSERT ON `tbl_productos` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_producto IS NULL OR NEW.id_producto = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_producto, 'Prod-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_productos
    WHERE id_producto LIKE 'Prod-%';

    SET nuevoStr = CONCAT('Prod-', LPAD(nuevo, 4, '0'));
    SET NEW.id_producto = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_proveedores`
--

CREATE TABLE `tbl_proveedores` (
  `id_proveedor` varchar(20) NOT NULL,
  `nombre_proveedor` varchar(50) NOT NULL,
  `rif_proveedor` varchar(15) DEFAULT NULL,
  `nombre_representante` varchar(50) DEFAULT NULL,
  `rif_representante` varchar(15) DEFAULT NULL,
  `correo_proveedor` varchar(50) DEFAULT NULL,
  `direccion_proveedor` text DEFAULT NULL,
  `telefono_1` varchar(15) DEFAULT NULL,
  `telefono_2` varchar(15) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `estado` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_proveedores`
--

INSERT INTO `tbl_proveedores` (`id_proveedor`, `nombre_proveedor`, `rif_proveedor`, `nombre_representante`, `rif_representante`, `correo_proveedor`, `direccion_proveedor`, `telefono_1`, `telefono_2`, `observacion`, `estado`) VALUES
('Prov-0001', 'Aliexpres', 'V-12332125-7', 'Brayan Mendoza', 'J-98778954-7', 'ejemplo@gmail.com', 'calle 32 con carrera 18 y 19', '0412-258-8989', '0424-654-4554', 'Buena calidad de productos, envio gratis', 'habilitado');

--
-- Disparadores `tbl_proveedores`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarProveedorID` BEFORE INSERT ON `tbl_proveedores` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_proveedor IS NULL OR NEW.id_proveedor = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_proveedor, 'Prov-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_proveedores
    WHERE id_proveedor LIKE 'Prov-%';

    SET nuevoStr = CONCAT('Prov-', LPAD(nuevo, 4, '0'));
    SET NEW.id_proveedor = nuevoStr;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_recepcion_productos`
--

CREATE TABLE `tbl_recepcion_productos` (
  `id_recepcion` varchar(20) NOT NULL,
  `id_proveedor` varchar(20) NOT NULL,
  `fecha` date NOT NULL,
  `correlativo` varchar(255) NOT NULL,
  `tamanocompra` enum('Pequeño','Mediano','Grande') NOT NULL,
  `estado` enum('habilitado','anulado') NOT NULL DEFAULT 'habilitado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tbl_recepcion_productos`
--
DELIMITER $$
CREATE TRIGGER `tr_InsertarRecepcionID` BEFORE INSERT ON `tbl_recepcion_productos` FOR EACH ROW BEGIN
  DECLARE nuevo INT DEFAULT 0;
  DECLARE nuevoStr VARCHAR(50);

  IF NEW.id_recepcion IS NULL OR NEW.id_recepcion = '' THEN
    SELECT COALESCE(MAX(CAST(REPLACE(id_recepcion, 'Rec-', '') AS UNSIGNED)), 0) + 1
    INTO nuevo
    FROM tbl_recepcion_productos
    WHERE id_recepcion LIKE 'Rec-%';

    SET nuevoStr = CONCAT('Rec-', LPAD(nuevo, 4, '0'));
    SET NEW.id_recepcion = nuevoStr;
  END IF;
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cat_cartucho_de_tinta`
--
ALTER TABLE `cat_cartucho_de_tinta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `cat_impresoras`
--
ALTER TABLE `cat_impresoras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `cat_otros`
--
ALTER TABLE `cat_otros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `cat_protector_de_voltaje`
--
ALTER TABLE `cat_protector_de_voltaje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `cat_tintas`
--
ALTER TABLE `cat_tintas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `dolar_cache`
--
ALTER TABLE `dolar_cache`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_carrito`
--
ALTER TABLE `tbl_carrito`
  ADD PRIMARY KEY (`id_carrito`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `tbl_carritodetalle`
--
ALTER TABLE `tbl_carritodetalle`
  ADD PRIMARY KEY (`id_carrito_detalle`),
  ADD KEY `id_carrito` (`id_carrito`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `tbl_categoria`
--
ALTER TABLE `tbl_categoria`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `tbl_clientes`
--
ALTER TABLE `tbl_clientes`
  ADD PRIMARY KEY (`id_clientes`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `tbl_combo`
--
ALTER TABLE `tbl_combo`
  ADD PRIMARY KEY (`id_combo`);

--
-- Indices de la tabla `tbl_combo_detalle`
--
ALTER TABLE `tbl_combo_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_combo` (`id_combo`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `tbl_cuentas`
--
ALTER TABLE `tbl_cuentas`
  ADD PRIMARY KEY (`id_cuenta`);

--
-- Indices de la tabla `tbl_despachos`
--
ALTER TABLE `tbl_despachos`
  ADD PRIMARY KEY (`id_despachos`),
  ADD KEY `id_clientes` (`id_clientes`);

--
-- Indices de la tabla `tbl_despacho_detalle`
--
ALTER TABLE `tbl_despacho_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_despacho` (`id_despacho`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `tbl_detalles_pago`
--
ALTER TABLE `tbl_detalles_pago`
  ADD PRIMARY KEY (`id_detalles`),
  ADD KEY `tbl_detalles_pago` (`id_factura`),
  ADD KEY `tbl_detalles_pago1` (`id_cuenta`);

--
-- Indices de la tabla `tbl_detalle_recepcion_productos`
--
ALTER TABLE `tbl_detalle_recepcion_productos`
  ADD PRIMARY KEY (`id_detalle_recepcion_productos`),
  ADD KEY `fk_detalle_recepcion` (`id_recepcion`),
  ADD KEY `fk_detalle_producto` (`id_producto`);

--
-- Indices de la tabla `tbl_facturas`
--
ALTER TABLE `tbl_facturas`
  ADD PRIMARY KEY (`id_factura`),
  ADD KEY `cliente` (`cliente`);

--
-- Indices de la tabla `tbl_factura_detalle`
--
ALTER TABLE `tbl_factura_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `tbl_factura_detalle` (`id_producto`);

--
-- Indices de la tabla `tbl_ingresos_egresos`
--
ALTER TABLE `tbl_ingresos_egresos`
  ADD PRIMARY KEY (`id_finanzas`),
  ADD KEY `id_despacho` (`id_despacho`,`id_detalle_recepcion_productos`),
  ADD KEY `id_detalle_recepcion_productos` (`id_detalle_recepcion_productos`);

--
-- Indices de la tabla `tbl_marcas`
--
ALTER TABLE `tbl_marcas`
  ADD PRIMARY KEY (`id_marca`);

--
-- Indices de la tabla `tbl_modelos`
--
ALTER TABLE `tbl_modelos`
  ADD PRIMARY KEY (`id_modelo`),
  ADD KEY `fk_modelo_marca` (`id_marca`);

--
-- Indices de la tabla `tbl_orden_despachos`
--
ALTER TABLE `tbl_orden_despachos`
  ADD PRIMARY KEY (`id_orden_despachos`),
  ADD KEY `id_factura` (`id_factura`);

--
-- Indices de la tabla `tbl_productos`
--
ALTER TABLE `tbl_productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `fk_producto_categoria` (`id_categoria`),
  ADD KEY `fk_producto_modelo` (`id_modelo`);

--
-- Indices de la tabla `tbl_proveedores`
--
ALTER TABLE `tbl_proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `tbl_recepcion_productos`
--
ALTER TABLE `tbl_recepcion_productos`
  ADD PRIMARY KEY (`id_recepcion`),
  ADD KEY `fk_recepcion_proveedor` (`id_proveedor`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `dolar_cache`
--
ALTER TABLE `dolar_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cat_cartucho_de_tinta`
--
ALTER TABLE `cat_cartucho_de_tinta`
  ADD CONSTRAINT `cat_cartucho_de_tinta_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cat_impresoras`
--
ALTER TABLE `cat_impresoras`
  ADD CONSTRAINT `cat_impresoras_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cat_otros`
--
ALTER TABLE `cat_otros`
  ADD CONSTRAINT `cat_otros_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cat_protector_de_voltaje`
--
ALTER TABLE `cat_protector_de_voltaje`
  ADD CONSTRAINT `cat_protector_de_voltaje_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cat_tintas`
--
ALTER TABLE `cat_tintas`
  ADD CONSTRAINT `cat_tintas_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_carritodetalle`
--
ALTER TABLE `tbl_carritodetalle`
  ADD CONSTRAINT `tbl_carritodetalle_ibfk_1` FOREIGN KEY (`id_carrito`) REFERENCES `tbl_carrito` (`id_carrito`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_carritodetalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_combo_detalle`
--
ALTER TABLE `tbl_combo_detalle`
  ADD CONSTRAINT `tbl_combo_detalle_ibfk_1` FOREIGN KEY (`id_combo`) REFERENCES `tbl_combo` (`id_combo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_combo_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_despachos`
--
ALTER TABLE `tbl_despachos`
  ADD CONSTRAINT `tbl_despachos_ibfk_1` FOREIGN KEY (`id_clientes`) REFERENCES `tbl_clientes` (`id_clientes`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_despacho_detalle`
--
ALTER TABLE `tbl_despacho_detalle`
  ADD CONSTRAINT `tbl_despacho_detalle_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `tbl_despachos` (`id_despachos`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_despacho_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`);

--
-- Filtros para la tabla `tbl_detalles_pago`
--
ALTER TABLE `tbl_detalles_pago`
  ADD CONSTRAINT `fk_id_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `tbl_cuentas` (`id_cuenta`),
  ADD CONSTRAINT `fk_id_factura` FOREIGN KEY (`id_factura`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_detalle_recepcion_productos`
--
ALTER TABLE `tbl_detalle_recepcion_productos`
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`),
  ADD CONSTRAINT `fk_detalle_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `tbl_recepcion_productos` (`id_recepcion`),
  ADD CONSTRAINT `tbl_detalles_recepcion_productos` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_facturas`
--
ALTER TABLE `tbl_facturas`
  ADD CONSTRAINT `tbl_facturas_ibfk_1` FOREIGN KEY (`cliente`) REFERENCES `tbl_clientes` (`id_clientes`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_factura_detalle`
--
ALTER TABLE `tbl_factura_detalle`
  ADD CONSTRAINT `factura_detalle_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_factura_detalle` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_ingresos_egresos`
--
ALTER TABLE `tbl_ingresos_egresos`
  ADD CONSTRAINT `tbl_ingresos_egresos_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `tbl_despachos` (`id_despachos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ingresos_egresos_ibfk_2` FOREIGN KEY (`id_detalle_recepcion_productos`) REFERENCES `tbl_detalle_recepcion_productos` (`id_detalle_recepcion_productos`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_modelos`
--
ALTER TABLE `tbl_modelos`
  ADD CONSTRAINT `fk_modelo_marca` FOREIGN KEY (`id_marca`) REFERENCES `tbl_marcas` (`id_marca`),
  ADD CONSTRAINT `modelo_ibfk_1` FOREIGN KEY (`id_marca`) REFERENCES `tbl_marcas` (`id_marca`);

--
-- Filtros para la tabla `tbl_orden_despachos`
--
ALTER TABLE `tbl_orden_despachos`
  ADD CONSTRAINT `tbl_orden_despachos_ibfk_1` FOREIGN KEY (`id_factura`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_productos`
--
ALTER TABLE `tbl_productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `tbl_categoria` (`id_categoria`),
  ADD CONSTRAINT `fk_producto_modelo` FOREIGN KEY (`id_modelo`) REFERENCES `tbl_modelos` (`id_modelo`),
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_modelo`) REFERENCES `tbl_modelos` (`id_modelo`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `tbl_categoria` (`id_categoria`);

--
-- Filtros para la tabla `tbl_recepcion_productos`
--
ALTER TABLE `tbl_recepcion_productos`
  ADD CONSTRAINT `fk_recepcion_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `tbl_proveedores` (`id_proveedor`),
  ADD CONSTRAINT `tbl_recepcion_productos` FOREIGN KEY (`id_proveedor`) REFERENCES `tbl_proveedores` (`id_proveedor`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
