-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-04-2026 a las 14:15:45
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
-- Base de datos: `casalai_seguridad`
--
CREATE DATABASE IF NOT EXISTS `casalai_seguridad` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `casalai_seguridad`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguridad_ip`
--

CREATE TABLE `seguridad_ip` (
  `id_seguridad_ip` int(11) NOT NULL,
  `direccion_ip` varchar(45) NOT NULL COMMENT 'Dirección IP del cliente',
  `username` varchar(50) DEFAULT NULL COMMENT 'Usuario asociado si está logueado',
  `tipo_bloqueo` enum('ip','usuario') NOT NULL DEFAULT 'ip' COMMENT 'Tipo de bloqueo aplicado',
  `motivo_bloqueo` varchar(200) DEFAULT NULL COMMENT 'Motivo del bloqueo',
  `peticiones_totales` int(11) DEFAULT 0 COMMENT 'Total de peticiones registradas',
  `peticiones sospechosas` int(11) DEFAULT 0 COMMENT 'Peticiones consideradas sospechosas',
  `fecha_ultima_peticion` datetime DEFAULT NULL COMMENT 'Fecha y hora de última petición',
  `fecha_bloqueo` datetime DEFAULT NULL COMMENT 'Fecha y hora del bloqueo',
  `fecha_desbloqueo` datetime DEFAULT NULL COMMENT 'Fecha y hora de desbloqueo automático',
  `esta_bloqueado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 si está bloqueado, 0 si no',
  `nivel_riesgo` enum('bajo','medio','alto','critico') NOT NULL DEFAULT 'bajo' COMMENT 'Nivel de riesgo asignado',
  `agente_usuario` text DEFAULT NULL COMMENT 'User Agent del navegador',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro',
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Fecha de última actualización'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla para control de seguridad y bloqueo de IPs';

--
-- Volcado de datos para la tabla `seguridad_ip`
--

INSERT INTO `seguridad_ip` (`id_seguridad_ip`, `direccion_ip`, `username`, `tipo_bloqueo`, `motivo_bloqueo`, `peticiones_totales`, `peticiones sospechosas`, `fecha_ultima_peticion`, `fecha_bloqueo`, `fecha_desbloqueo`, `esta_bloqueado`, `nivel_riesgo`, `agente_usuario`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, '127.0.0.1', NULL, 'ip', 'IP local - confianza', 0, 0, NULL, NULL, NULL, 0, 'bajo', NULL, '2026-04-23 07:40:25', '2026-04-23 07:40:25'),
(2, '::1', NULL, 'ip', 'IPv6 local - confianza', 0, 0, NULL, NULL, NULL, 0, 'bajo', NULL, '2026-04-23 07:40:25', '2026-04-23 07:40:25'),
(3, '127.0.0.1', NULL, 'ip', 'IP local - confianza', 0, 0, NULL, NULL, NULL, 0, 'bajo', NULL, '2026-04-23 07:41:15', '2026-04-23 07:41:15'),
(4, '::1', NULL, 'ip', 'IPv6 local - confianza', 0, 0, NULL, NULL, NULL, 0, 'bajo', NULL, '2026-04-23 07:41:15', '2026-04-23 07:41:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_bitacora`
--

CREATE TABLE IF NOT EXISTS `tbl_bitacora` (
  `id_bitacora` int(11) NOT NULL,
  `fecha_hora` DATETIME NOT NULL,
  `nombre_modulo` varchar(50) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `datos_viejos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_viejos`)),
  `id_usuario` int(11) NOT NULL,
  `prioridad` enum('baja','media','alta') NOT NULL DEFAULT 'media',
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_bitacora`
--

INSERT INTO `tbl_bitacora` (`id_bitacora`, `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`) VALUES
(2200, '2026-03-05 21:50:52', 'Catalogo', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Catálogo'),
(2201, '2026-03-05 22:10:39', 'Usuario', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Usuarios'),
(2202, '2026-03-05 22:10:45', 'Recepcion', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Recepcion'),
(2203, '2026-03-05 22:11:13', 'Productos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Productos'),
(2204, '2026-03-05 22:11:15', 'Productos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Productos'),
(2205, '2026-03-05 22:11:35', 'Productos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Productos'),
(2206, '2026-03-05 22:11:37', 'Productos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Productos'),
(2207, '2026-03-05 22:38:44', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2208, '2026-03-05 22:38:57', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 986598 a Pago Procesado'),
(2209, '2026-03-05 22:39:03', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 876585 a Pago Procesado'),
(2210, '2026-03-05 22:39:16', 'Pedidos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pedidos'),
(2211, '2026-03-05 22:39:31', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2212, '2026-03-05 22:40:08', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2213, '2026-03-05 22:47:37', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2214, '2026-03-05 22:47:40', 'Pedidos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pedidos'),
(2215, '2026-03-05 22:47:49', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2216, '2026-03-05 22:48:13', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2217, '2026-03-05 22:48:33', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2218, '2026-03-05 22:48:36', 'Pedidos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pedidos'),
(2219, '2026-03-05 22:48:42', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2220, '2026-03-05 22:49:03', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2221, '2026-03-05 22:49:54', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2222, '2026-03-05 22:49:56', 'Pedidos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pedidos'),
(2223, '2026-03-05 22:50:01', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2224, '2026-03-05 22:50:22', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2225, '2026-03-05 22:54:14', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2226, '2026-03-05 22:54:16', 'Pedidos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pedidos'),
(2227, '2026-03-05 22:54:22', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2228, '2026-03-05 22:55:01', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2229, '2026-03-05 22:56:17', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2230, '2026-03-05 22:56:23', 'Pedidos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pedidos'),
(2231, '2026-03-05 22:56:28', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2232, '2026-03-05 22:56:48', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2233, '2026-03-05 22:56:49', 'Pasarela', 'INGRESAR', NULL, NULL, 3, 'alta', 'Ingreso de pago con referencia 123321'),
(2234, '2026-03-05 22:56:52', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2235, '2026-03-05 22:57:04', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 123321 a Pago Procesado'),
(2236, '2026-03-05 22:57:10', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2237, '2026-03-05 22:57:32', '3', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Despachos'),
(2238, '2026-03-05 22:58:28', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2239, '2026-03-05 23:12:08', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2240, '2026-03-05 23:12:18', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2241, '2026-03-05 23:12:26', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 8747857544 a Pago Procesado'),
(2242, '2026-03-05 23:12:38', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 8747857544 a Pago Procesado'),
(2243, '2026-03-05 23:14:00', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2244, '2026-03-05 23:14:19', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 884675465463 a Pago Procesado'),
(2245, '2026-03-05 23:14:31', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 884675465463 a Pago Procesado'),
(2246, '2026-03-05 23:16:36', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2247, '2026-03-05 23:16:44', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2248, '2026-03-05 23:16:45', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2249, '2026-03-05 23:16:47', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2250, '2026-03-05 23:16:48', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2251, '2026-03-05 23:16:49', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2252, '2026-03-05 23:16:50', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2253, '2026-03-05 23:17:19', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 123456 a Pago Procesado'),
(2254, '2026-03-05 23:17:38', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2255, '2026-03-05 23:17:53', 'Pedidos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pedidos'),
(2256, '2026-03-05 23:18:09', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2257, '2026-03-05 23:18:42', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2258, '2026-03-05 23:18:43', 'Pasarela', 'INGRESAR', NULL, NULL, 3, 'alta', 'Ingreso de pago con referencia 747474'),
(2259, '2026-03-05 23:18:45', 'Pasarela', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Pagos'),
(2260, '2026-03-05 23:19:23', 'Pasarela', 'MODIFICAR', NULL, NULL, 3, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 747474 a Pago Procesado'),
(2261, '2026-03-05 23:20:53', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2262, '2026-03-05 23:21:05', 'Ordenes de despacho', 'CAMBIAR ESTADO', NULL, NULL, 3, 'media', 'El usuario cambió el estado de la orden de despacho con ID: 8 a Entregada'),
(2263, '2026-03-06 08:32:37', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2264, '2026-03-06 08:43:09', 'Recepcion', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Recepcion'),
(2265, '2026-03-06 08:43:39', 'Recepcion', 'INCLUIR', NULL, NULL, 3, 'media', 'El usuario incluyó una nueva recepción: 097448'),
(2266, '2026-03-06 08:43:51', 'Recepcion', 'ANULAR', NULL, NULL, 3, 'media', 'El usuario anuló la recepción: 097448'),
(2267, '2026-03-08 21:15:50', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2268, '2026-03-08 21:18:09', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2269, '2026-03-08 21:18:10', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2270, '2026-03-08 21:18:11', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2271, '2026-03-08 21:18:11', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2272, '2026-03-08 21:18:12', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2273, '2026-03-08 21:19:15', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2274, '2026-03-08 21:19:16', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2275, '2026-03-08 21:19:16', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2276, '2026-03-08 21:22:24', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2277, '2026-03-08 21:23:06', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2278, '2026-03-08 21:25:36', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2279, '2026-03-08 21:27:43', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2280, '2026-03-08 21:28:12', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2281, '2026-03-08 21:28:28', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2282, '2026-03-08 21:28:46', 'Marcas', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de marcas'),
(2283, '2026-03-08 21:29:01', 'Modelos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Modelos'),
(2284, '2026-03-08 21:29:19', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2285, '2026-03-08 21:29:31', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2286, '2026-03-08 21:29:41', 'Proveedores', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Proveedores'),
(2287, '2026-03-08 21:29:59', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2288, '2026-03-08 21:30:39', 'Catalogo', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Catálogo'),
(2289, '2026-03-08 22:00:50', 'Catalogo', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Catálogo'),
(2290, '2026-03-08 22:01:08', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2291, '2026-03-08 22:02:34', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2292, '2026-03-08 22:02:37', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2293, '2026-03-08 22:09:08', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2294, '2026-03-08 22:19:28', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2295, '2026-03-08 22:20:12', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2296, '2026-03-08 22:20:54', 'Permisos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó los permisos de los roles del sistema'),
(2297, '2026-03-08 22:20:55', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2298, '2026-03-08 22:34:25', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2299, '2026-03-08 22:34:57', 'Pedidos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pedidos'),
(2300, '2026-03-08 22:35:02', 'Pasarela', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pagos'),
(2301, '2026-03-08 22:35:10', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2302, '2026-03-08 22:35:18', '3', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Despachos'),
(2303, '2026-03-08 22:35:28', '15', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Cuentas Bancarias'),
(2304, '2026-03-08 22:35:40', 'Finanzas', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Finanzas'),
(2305, '2026-03-08 22:35:52', 'Roles', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Roles'),
(2306, '2026-03-09 06:37:45', 'Marcas', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de marcas'),
(2307, '2026-03-09 06:47:01', 'Despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Compra Física'),
(2308, '2026-03-09 06:50:33', 'Despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Compra Física'),
(2309, '2026-03-09 06:50:52', 'Despacho', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Compra Física'),
(2310, '2026-03-09 06:53:04', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2311, '2026-03-11 21:04:47', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2312, '2026-03-11 21:05:18', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2313, '2026-03-11 21:07:47', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2314, '2026-03-11 21:17:16', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2315, '2026-03-11 21:19:58', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2316, '2026-03-11 21:22:57', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2317, '2026-03-11 21:24:01', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2318, '2026-03-11 21:24:49', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2319, '2026-03-11 21:26:33', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2320, '2026-03-11 21:27:20', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2321, '2026-03-11 21:27:41', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2322, '2026-03-11 21:29:45', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2323, '2026-03-11 21:32:00', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2324, '2026-03-11 21:32:48', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2325, '2026-03-11 21:36:24', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2326, '2026-03-11 21:37:09', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2327, '2026-03-11 21:37:10', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2328, '2026-03-11 21:37:10', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2329, '2026-03-11 21:37:11', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2330, '2026-03-11 21:37:13', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2331, '2026-03-11 21:37:14', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2332, '2026-03-11 21:37:15', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2333, '2026-03-11 21:37:16', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2334, '2026-03-11 21:37:17', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2335, '2026-03-11 21:37:24', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2336, '2026-03-11 21:37:26', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2337, '2026-03-11 21:37:29', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2338, '2026-03-11 21:37:30', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2339, '2026-03-11 21:37:32', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2340, '2026-03-11 21:41:09', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2341, '2026-03-11 21:43:16', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2342, '2026-03-11 21:45:49', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2343, '2026-03-11 21:51:29', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2344, '2026-03-11 21:52:23', 'Clientes', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el cliente ID: 17'),
(2345, '2026-03-11 21:52:35', 'Clientes', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el cliente ID: 16'),
(2346, '2026-03-11 21:52:52', 'Clientes', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el cliente ID: 16'),
(2347, '2026-03-11 21:52:58', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2348, '2026-03-11 21:53:45', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2349, '2026-03-11 21:56:10', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2350, '2026-03-11 21:56:46', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2351, '2026-03-11 21:57:50', 'Clientes', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el cliente ID: 19'),
(2352, '2026-03-11 22:00:36', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2353, '2026-03-11 22:00:37', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2354, '2026-03-11 22:00:38', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2355, '2026-03-11 22:05:58', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2356, '2026-03-11 22:05:59', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2357, '2026-03-11 22:06:00', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2358, '2026-03-11 22:06:02', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2359, '2026-03-11 22:06:03', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2360, '2026-03-11 22:06:05', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2361, '2026-03-11 22:06:06', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2362, '2026-03-11 22:06:08', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2363, '2026-03-11 22:06:09', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2364, '2026-03-11 22:08:23', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2365, '2026-03-11 22:09:20', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2366, '2026-03-11 22:14:09', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2367, '2026-03-11 22:16:29', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2368, '2026-03-11 22:16:33', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2369, '2026-03-11 22:16:34', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2370, '2026-03-11 22:16:38', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2371, '2026-03-11 22:16:39', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2372, '2026-03-11 22:16:42', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2373, '2026-03-11 22:16:45', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2374, '2026-03-11 22:17:51', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2375, '2026-03-11 22:19:42', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2376, '2026-03-11 22:20:07', 'Clientes', 'INCLUIR', NULL, NULL, 9, 'alta', 'El usuario incluyó al cliente: Simon Freitez'),
(2377, '2026-03-11 22:20:21', 'Clientes', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el cliente ID: 18'),
(2378, '2026-03-12 20:28:36', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2379, '2026-03-12 20:29:40', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2380, '2026-03-12 20:31:06', 'Usuario', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo usuario: Cualquiera0'),
(2381, '2026-03-12 20:31:28', 'Usuario', 'MODIFICAR', NULL, NULL, 9, 'media', 'Actualización de usuario (ID: 26). Usuario: Diego Lopez (antes) -> Diego Lopezss (después)'),
(2382, '2026-03-12 20:31:34', 'Usuario', 'ELIMINAR', NULL, NULL, 9, 'media', 'Eliminación del usuario: Array'),
(2383, '2026-03-12 20:31:39', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2384, '2026-03-12 20:32:19', 'Recepcion', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva recepción: 131468'),
(2385, '2026-03-12 20:32:26', 'Recepcion', 'ANULAR', NULL, NULL, 9, 'media', 'El usuario anuló la recepción: 131468'),
(2386, '2026-03-12 20:32:49', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2387, '2026-03-12 20:33:52', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2388, '2026-03-12 20:43:05', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2389, '2026-03-12 20:54:17', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2390, '2026-03-12 20:55:41', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2391, '2026-03-12 20:57:35', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2392, '2026-03-12 22:04:47', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2393, '2026-03-12 22:05:00', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2394, '2026-03-12 22:05:11', 'Categorias', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó la categoría ID: 17'),
(2395, '2026-03-12 22:06:38', 'Categorias', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó la categoría: Herramientas123'),
(2396, '2026-03-12 22:08:24', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2397, '2026-03-12 22:08:32', 'Categorias', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó la categoría ID: 17'),
(2398, '2026-03-12 22:08:36', 'Categorias', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó la categoría ID: 18'),
(2399, '2026-03-20 20:00:31', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2400, '2026-03-20 20:01:12', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2401, '2026-03-20 20:02:33', 'Usuario', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo usuario: PatoCuak'),
(2402, '2026-03-20 20:02:55', 'Usuario', 'MODIFICAR', NULL, NULL, 9, 'media', 'Actualización de usuario (ID: 27). Usuario: Diego Lopez (antes) -> Diego Lopez (después)'),
(2403, '2026-03-20 20:03:03', 'Usuario', 'ELIMINAR', NULL, NULL, 9, 'media', 'Eliminación del usuario: Array'),
(2404, '2026-03-20 20:03:36', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2405, '2026-03-20 20:04:13', 'Recepcion', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva recepción: 998654'),
(2406, '2026-03-20 20:04:50', 'Marcas', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de marcas'),
(2407, '2026-03-20 20:05:08', 'Marcas', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva marca: DK'),
(2408, '2026-03-20 20:05:17', 'Marcas', 'MODIFICAR', NULL, NULL, 9, 'alta', 'El usuario modifico  la marca DK a DK1'),
(2409, '2026-03-20 20:05:21', 'Marcas', 'ELIMINAR', NULL, NULL, 9, 'alta', 'El usuario elimino de los registros la marca DK1'),
(2410, '2026-03-20 20:05:40', 'Modelos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Modelos'),
(2411, '2026-03-20 20:05:56', 'Modelos', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo modelo: Pikachu'),
(2412, '2026-03-20 20:06:08', 'Modelos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modifico el modelo Raiachu'),
(2413, '2026-03-20 20:06:11', 'Modelos', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el modelo ID: 80'),
(2414, '2026-03-20 20:06:21', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2415, '2026-03-20 20:06:24', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2416, '2026-03-20 20:07:26', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2417, '2026-03-20 20:07:28', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2418, '2026-03-20 20:19:57', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2419, '2026-03-20 20:20:08', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2420, '2026-03-20 20:26:40', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2421, '2026-03-20 20:26:44', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2422, '2026-03-20 20:26:49', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2423, '2026-03-20 20:26:53', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2424, '2026-03-20 20:29:15', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2425, '2026-03-20 20:29:20', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2426, '2026-03-20 20:29:58', 'Productos', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo producto: GigaVolt'),
(2427, '2026-03-20 20:33:23', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2428, '2026-03-20 20:33:28', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2429, '2026-03-20 20:39:01', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2430, '2026-03-20 20:39:06', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2431, '2026-03-20 20:39:38', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2432, '2026-03-20 20:39:41', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2433, '2026-03-20 20:43:41', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2434, '2026-03-20 20:43:44', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2435, '2026-03-20 20:44:47', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2436, '2026-03-20 20:44:50', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2437, '2026-03-20 20:51:34', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2438, '2026-03-20 20:51:39', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2439, '2026-03-20 20:57:42', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2440, '2026-03-20 20:57:49', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2441, '2026-03-20 21:03:40', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2442, '2026-03-20 21:03:45', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2443, '2026-03-20 21:04:29', 'Productos', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo producto: GigaVol'),
(2444, '2026-03-20 21:06:00', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2445, '2026-03-20 21:06:05', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2446, '2026-03-20 21:07:15', 'Productos', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo producto: Noexiste'),
(2447, '2026-03-20 21:07:17', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2448, '2026-03-20 21:07:24', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2449, '2026-03-20 21:07:41', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2450, '2026-03-20 21:07:46', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2451, '2026-03-20 21:12:06', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2452, '2026-03-20 21:12:11', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2453, '2026-03-20 21:15:15', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2454, '2026-03-20 21:15:17', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2455, '2026-03-20 21:15:18', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2456, '2026-03-20 21:15:20', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2457, '2026-03-20 21:15:22', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2458, '2026-03-20 21:15:27', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2459, '2026-03-20 21:19:06', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2460, '2026-03-20 21:19:13', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2461, '2026-03-20 21:19:40', 'Productos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el producto: Cortadoras de papel | Antes: {\"id_producto\":32,\"serial\":\"0005\",\"nombre_producto\":\"Cortadoras de papel\",\"descripcion_producto\":\"Cortadoras especiales para papeler\\u00eda.\",\"id_modelo\":33,\"id_categoria\":15,\"stock\":20,\"stock_minimo\":5,\"stock_maximo\":50,\"clausula_garantia\":\"Sin Garant\\u00eda\",\"precio\":8,\"estado\":\"habilitado\",\"imagen\":\"assets\\\\img\\\\productos\\\\producto_44.jpg\"} | Después: {\"id_producto\":32,\"serial\":\"\",\"nombre_producto\":\"\",\"descripcion_producto\":null,\"id_modelo\":null,\"id_categoria\":15,\"stock\":null,\"stock_minimo\":null,\"stock_maximo\":null,\"clausula_garantia\":\"\",\"precio\":null,\"estado\":\"habilitado\",\"imagen\":\"assets\\\\img\\\\productos\\\\producto_44.jpg\"}'),
(2462, '2026-03-20 21:19:42', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2463, '2026-03-20 21:19:47', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2464, '2026-03-20 21:21:52', 'Proveedores', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Proveedores'),
(2465, '2026-03-20 21:22:09', 'Proveedores', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el proveedor: Aliexpres que tenia los datos{\"id_proveedor\":1,\"nombre_proveedor\":\"Aliexpres\",\"rif_proveedor\":\"V-12332125-7\",\"nombre_representante\":\"Brayan Mendoza\",\"rif_representante\":\"J-98778954-7\",\"correo_proveedor\":\"ejemplo@gmail.com\",\"direccion_proveedor\":\"calle 32 con carrera 18 y 19\",\"telefono_1\":\"0412-258-8989\",\"telefono_2\":\"0424-654-4554\",\"observacion\":\"Buena calidad de productos, envio gratis\",\"estado\":\"habilitado\"} con los datos {\"id_proveedor\":1,\"nombre_proveedor\":\"Aliexpres\",\"rif_proveedor\":\"V-12332125-7\",\"nombre_representante\":\"Brayan Mendoza\",\"rif_representante\":\"J-98778954-7\",\"correo_proveedor\":\"ejemplo@gmail.com\",\"direccion_proveedor\":\"calle 32 con carrera 18 y 19\",\"telefono_1\":\"0412-258-9898\",\"telefono_2\":\"0424-654-4554\",\"observacion\":\"Buena calidad de productos, envio gratis\",\"estado\":\"habilitado\"}'),
(2466, '2026-03-20 21:22:25', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2467, '2026-03-20 21:29:07', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2468, '2026-03-20 21:33:02', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2469, '2026-03-20 21:40:39', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2470, '2026-03-20 21:43:30', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2471, '2026-03-20 21:44:07', 'Despacho', 'INCLUIR', NULL, NULL, 9, 'alta', 'El usuario CasaLai incluyó la compra física: 65'),
(2472, '2026-03-20 21:51:25', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2473, '2026-03-20 21:54:08', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2474, '2026-03-20 21:54:20', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2475, '2026-03-20 21:58:15', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2476, '2026-03-20 21:59:42', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2477, '2026-03-20 22:00:42', 'Usuario', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo usuario: Clarividente'),
(2478, '2026-03-20 22:02:22', 'Pedidos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pedidos'),
(2479, '2026-03-20 22:02:29', 'Pasarela', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pagos'),
(2480, '2026-03-20 22:02:37', 'Pasarela', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 0000000000087 a Pago Procesado'),
(2481, '2026-03-20 22:02:43', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2482, '2026-03-20 22:02:47', 'Ordenes de despacho', 'CAMBIAR ESTADO', NULL, NULL, 9, 'media', 'El usuario cambió el estado de la orden de despacho con ID: 9 a Entregada'),
(2483, '2026-03-20 22:02:55', '3', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Despachos'),
(2484, '2026-03-20 22:02:59', 'Despacho', 'CAMBIAR ESTADO', NULL, NULL, 9, 'media', 'El usuario cambió el estado del despacho con ID: 5 a Despachado'),
(2485, '2026-03-20 22:02:59', 'Despacho', 'CAMBIAR ESTADO', NULL, NULL, 9, 'media', 'El usuario cambió el estado del despacho con ID: 5 a Despachado'),
(2486, '2026-03-20 22:03:25', '15', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Cuentas Bancarias'),
(2487, '2026-03-20 22:08:35', '15', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Cuentas Bancarias'),
(2488, '2026-03-20 22:13:18', '15', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Cuentas Bancarias'),
(2489, '2026-03-20 22:13:55', 'Cuentas bancarias', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva cuenta bancaria: Mercantil'),
(2490, '2026-03-20 22:14:16', 'Cuentas bancarias', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó la cuenta bancaria ID: 36'),
(2491, '2026-03-21 08:49:25', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2492, '2026-03-21 08:49:51', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2493, '2026-03-21 08:50:45', 'Categorias', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó la categoría: Imaginaria'),
(2494, '2026-03-21 08:50:58', 'Categorias', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó la categoría ID: 19'),
(2495, '2026-03-21 08:51:07', 'Categorias', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó la categoría ID: 19'),
(2496, '2026-03-21 08:51:21', 'Proveedores', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Proveedores'),
(2497, '2026-03-21 08:51:42', 'Proveedores', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo proveedor: Brayan Cable'),
(2498, '2026-03-21 08:52:02', 'Proveedores', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el proveedor: Brayan Cable que tenia los datos{\"id_proveedor\":1003,\"nombre_proveedor\":\"Brayan Cable\",\"rif_proveedor\":\"J-12121212-1\",\"nombre_representante\":\"Brayan Mendoza\",\"rif_representante\":\"V-31766316-0\",\"correo_proveedor\":\"diego00lopez@gmail.com\",\"direccion_proveedor\":\"Venezuela estado Zulia\\r\\nMaracaibo\",\"telefono_1\":\"0414-575-3363\",\"telefono_2\":\"0414-575-3363\",\"observacion\":\"hiii\",\"estado\":\"habilitado\"} con los datos {\"id_proveedor\":1003,\"nombre_proveedor\":\"Brayan Cable\",\"rif_proveedor\":\"J-12121212-9\",\"nombre_representante\":\"Brayan Mendoza\",\"rif_representante\":\"V-31766316-0\",\"correo_proveedor\":\"diego00lopez@gmail.com\",\"direccion_proveedor\":\"Venezuela estado Zulia\\r\\nMaracaibo\",\"telefono_1\":\"0414-575-3363\",\"telefono_2\":\"0414-575-3363\",\"observacion\":\"hiii\",\"estado\":\"habilitado\"}'),
(2499, '2026-03-21 08:52:19', 'Proveedores', 'CAMBIAR ESTATUS', NULL, NULL, 9, 'media', 'El usuario cambió el estatus del proveedor ID 1003 a inhabilitado'),
(2500, '2026-03-21 08:52:20', 'Proveedores', 'CAMBIAR ESTATUS', NULL, NULL, 9, 'media', 'El usuario cambió el estatus del proveedor ID 1003 a habilitado'),
(2501, '2026-03-21 08:52:46', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2502, '2026-03-21 08:52:55', 'Clientes', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el cliente ID: 34'),
(2503, '2026-03-21 08:52:59', 'Clientes', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el cliente ID: 33'),
(2504, '2026-03-21 08:53:03', 'Clientes', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el cliente ID: 13'),
(2505, '2026-03-21 08:53:28', 'Clientes', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el cliente ID: 14'),
(2506, '2026-03-21 08:54:21', 'Clientes', 'INCLUIR', NULL, NULL, 9, 'alta', 'El usuario incluyó al cliente: Paula Medina'),
(2507, '2026-03-21 08:55:25', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2508, '2026-03-21 08:56:09', '3', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Despachos'),
(2509, '2026-03-21 08:56:54', 'Roles', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Roles'),
(2510, '2026-03-21 08:57:03', 'Roles', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo rol: Imaginario'),
(2511, '2026-03-21 08:57:11', 'Roles', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el rol: Ima (Antes: Imaginario)'),
(2512, '2026-03-21 08:57:15', 'Roles', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el rol: Ima'),
(2513, '2026-03-21 09:12:25', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2514, '2026-03-21 09:12:29', 'Permisos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó los permisos de los roles del sistema'),
(2515, '2026-03-21 09:12:30', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2516, '2026-03-21 09:16:15', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2517, '2026-03-21 09:16:26', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2518, '2026-03-21 09:16:40', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2519, '2026-03-21 09:16:59', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2520, '2026-03-21 09:17:01', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2521, '2026-03-21 09:17:05', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2522, '2026-03-21 09:17:06', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2523, '2026-03-21 09:17:18', 'Catalogo', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Catálogo'),
(2524, '2026-03-21 09:18:10', 'Catalogo', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Catálogo'),
(2525, '2026-03-21 09:18:16', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2526, '2026-03-21 09:19:28', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2527, '2026-03-21 09:19:50', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2528, '2026-03-21 09:23:07', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2529, '2026-03-21 09:24:59', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2530, '2026-03-21 09:26:38', 'Permisos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó los permisos de los roles del sistema'),
(2531, '2026-03-21 09:26:38', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2532, '2026-03-21 09:28:13', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2533, '2026-03-21 09:28:23', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2534, '2026-03-21 09:28:30', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2535, '2026-03-21 09:28:46', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2536, '2026-03-21 09:32:03', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2537, '2026-03-21 09:32:08', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2538, '2026-03-21 09:32:24', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2539, '2026-03-21 09:38:19', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2540, '2026-03-21 09:38:47', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Tinta HP Original GT (Cantidad: 1)'),
(2541, '2026-03-21 09:38:49', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió a detalle de producto: Pendrive Kingston 64'),
(2542, '2026-03-21 09:39:04', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Pendrive Kingston 64 (Cantidad: 1)'),
(2543, '2026-03-21 09:39:18', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2544, '2026-03-21 09:39:34', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2545, '2026-03-21 09:47:09', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2546, '2026-03-21 09:47:12', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió a detalle de producto: Pendrive Kingston 64'),
(2547, '2026-03-21 09:47:18', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Pendrive Kingston 64 (Cantidad: 1)'),
(2548, '2026-03-21 09:47:24', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió a detalle de producto: Resma de papel HP ca'),
(2549, '2026-03-21 09:47:30', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Resma de papel HP ca (Cantidad: 1)'),
(2550, '2026-03-21 09:47:34', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2551, '2026-03-21 09:49:38', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2552, '2026-03-21 09:49:42', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió a detalle de producto: Resma de papel HP ca'),
(2553, '2026-03-21 09:49:44', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió a detalle de producto: Pendrive Kingston 64'),
(2554, '2026-03-21 09:49:50', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2555, '2026-03-21 09:49:55', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Tinta HP Original GT (Cantidad: 1)'),
(2556, '2026-03-21 09:49:57', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió a detalle de producto: Resma de papel HP ca'),
(2557, '2026-03-21 09:49:59', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2558, '2026-03-21 09:53:12', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2559, '2026-03-21 09:53:28', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2560, '2026-03-21 09:53:29', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2561, '2026-03-21 09:53:30', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2562, '2026-03-21 09:53:31', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2563, '2026-03-21 09:53:33', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2564, '2026-03-21 09:53:36', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2565, '2026-03-21 09:53:37', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2566, '2026-03-21 09:53:38', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2567, '2026-03-21 09:53:40', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2568, '2026-03-21 09:53:41', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2569, '2026-03-21 09:53:43', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2570, '2026-03-21 09:53:44', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2571, '2026-03-21 09:53:46', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2572, '2026-03-21 09:53:48', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2573, '2026-03-21 09:53:55', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2574, '2026-03-21 09:54:01', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Tinta HP Original GT (Cantidad: 1)'),
(2575, '2026-03-21 09:54:04', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Tarjeta SD Kingston  (Cantidad: 1)'),
(2576, '2026-03-21 09:54:08', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2577, '2026-03-21 09:54:46', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2578, '2026-03-21 09:56:04', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2579, '2026-03-21 09:56:09', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Tinta HP Original GT (Cantidad: 1)'),
(2580, '2026-03-21 09:56:12', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Tarjeta SD Kingston  (Cantidad: 1)');
INSERT INTO `tbl_bitacora` (`id_bitacora`, `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`) VALUES
(2581, '2026-03-21 09:56:15', 'Catalogo', 'INCLUIR', NULL, NULL, 11, 'alta', 'El usuario agregó producto al carrito: Pendrive Kingston 64 (Cantidad: 1)'),
(2582, '2026-03-21 09:56:20', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2583, '2026-03-21 09:56:32', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2584, '2026-03-21 09:59:03', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2585, '2026-03-21 10:00:47', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2586, '2026-03-21 10:01:54', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2587, '2026-03-21 10:02:18', 'Pedidos', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Pedidos'),
(2588, '2026-03-21 10:02:38', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2589, '2026-03-21 10:02:49', 'Pedidos', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Pedidos'),
(2590, '2026-03-21 10:05:36', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2591, '2026-03-21 10:06:47', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2592, '2026-03-21 10:12:07', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2593, '2026-03-21 10:37:38', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2594, '2026-03-21 10:37:39', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2595, '2026-03-21 10:37:41', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2596, '2026-03-21 10:37:42', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2597, '2026-03-21 10:37:44', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2598, '2026-03-21 10:37:45', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2599, '2026-03-21 10:37:47', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2600, '2026-03-21 10:37:48', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2601, '2026-03-21 10:37:50', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2602, '2026-03-21 10:37:52', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2603, '2026-03-21 10:38:10', 'Pedidos', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Pedidos'),
(2604, '2026-03-21 10:38:36', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2605, '2026-03-21 10:40:18', 'Despacho', 'INCLUIR', NULL, NULL, 9, 'alta', 'El usuario CasaLai incluyó la compra física: 67'),
(2606, '2026-03-21 10:41:04', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2607, '2026-03-21 10:48:04', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2608, '2026-03-21 10:50:32', 'Pasarela', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2609, '2026-03-21 10:51:04', 'Pasarela', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2610, '2026-03-21 10:51:05', 'Pasarela', 'INGRESAR', NULL, NULL, 11, 'alta', 'Ingreso de pago con referencia 8663552'),
(2611, '2026-03-21 10:51:07', 'Pasarela', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Pagos'),
(2612, '2026-03-21 11:42:12', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2613, '2026-04-06 21:26:40', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2614, '2026-04-06 21:26:50', '1', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Reportes de Usuarios'),
(2615, '2026-04-06 21:26:55', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2616, '2026-04-06 21:28:07', 'Usuario', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo usuario: Desp'),
(2617, '2026-04-06 21:28:28', 'Usuario', 'MODIFICAR', NULL, NULL, 9, 'media', 'Actualización de usuario (ID: 29). Usuario: Diego Lopez (antes) -> Diego Lopez (después)'),
(2618, '2026-04-06 21:28:44', 'Usuario', 'ELIMINAR', NULL, NULL, 9, 'media', 'Eliminación del usuario: Array'),
(2619, '2026-04-06 21:28:51', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2620, '2026-04-06 21:29:21', 'Recepcion', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva recepción: 000226'),
(2621, '2026-04-06 21:29:37', 'Recepcion', 'ANULAR', NULL, NULL, 9, 'media', 'El usuario anuló la recepción: 1235'),
(2622, '2026-04-06 21:30:20', 'Marcas', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de marcas'),
(2623, '2026-04-06 21:30:32', 'Marcas', 'MODIFICAR', NULL, NULL, 9, 'alta', 'El usuario modifico  la marca Digimon a Digimon Super'),
(2624, '2026-04-06 21:30:36', 'Marcas', 'ELIMINAR', NULL, NULL, 9, 'alta', 'El usuario elimino de los registros la marca Digimon Super'),
(2625, '2026-04-06 21:30:42', 'Marcas', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva marca: Digimon'),
(2626, '2026-04-06 21:31:56', 'Modelos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Modelos'),
(2627, '2026-04-06 21:32:21', 'Modelos', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo modelo: Bakumon'),
(2628, '2026-04-06 21:32:29', 'Modelos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modifico el modelo Baku'),
(2629, '2026-04-06 21:32:34', 'Modelos', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el modelo ID: 81'),
(2630, '2026-04-06 21:32:52', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2631, '2026-04-06 21:32:55', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2632, '2026-04-06 21:34:03', 'Productos', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo producto: Voltio'),
(2633, '2026-04-06 21:34:05', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2634, '2026-04-06 21:34:11', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2635, '2026-04-06 21:34:26', 'Productos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el producto: Volt | Antes: {\"id_producto\":47,\"serial\":\"54D354E\",\"nombre_producto\":\"Voltio\",\"descripcion_producto\":\"yguy uygy\",\"id_modelo\":6,\"id_categoria\":15,\"stock\":8,\"stock_minimo\":5,\"stock_maximo\":10,\"clausula_garantia\":\"gftfuyuyt6875\",\"precio\":120,\"estado\":\"habilitado\",\"imagen\":\"..\\/assets\\/img\\/productos\\/producto_47.jpeg\"} | Después: {\"id_producto\":47,\"serial\":\"\",\"nombre_producto\":\"\",\"descripcion_producto\":null,\"id_modelo\":null,\"id_categoria\":15,\"stock\":null,\"stock_minimo\":null,\"stock_maximo\":null,\"clausula_garantia\":\"\",\"precio\":null,\"estado\":\"habilitado\",\"imagen\":\"..\\/assets\\/img\\/productos\\/producto_47.jpeg\"}'),
(2636, '2026-04-06 21:34:28', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2637, '2026-04-06 21:34:31', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2638, '2026-04-06 21:35:27', 'Productos', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo producto: Impresora '),
(2639, '2026-04-06 21:35:29', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2640, '2026-04-06 21:35:33', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2641, '2026-04-06 21:35:41', 'Productos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el producto: Impresora  | Antes: {\"id_producto\":48,\"serial\":\"5R5R\",\"nombre_producto\":\"Impresora \",\"descripcion_producto\":\"rthrhhhhhh\",\"id_modelo\":6,\"id_categoria\":15,\"stock\":5,\"stock_minimo\":5,\"stock_maximo\":21,\"clausula_garantia\":\"yyyyu       y\",\"precio\":60,\"estado\":\"habilitado\",\"imagen\":\"..\\/assets\\/img\\/productos\\/producto_48.jpeg\"} | Después: {\"id_producto\":48,\"serial\":\"\",\"nombre_producto\":\"\",\"descripcion_producto\":null,\"id_modelo\":null,\"id_categoria\":15,\"stock\":null,\"stock_minimo\":null,\"stock_maximo\":null,\"clausula_garantia\":\"\",\"precio\":null,\"estado\":\"habilitado\",\"imagen\":\"..\\/assets\\/img\\/productos\\/producto_48.jpeg\"}'),
(2642, '2026-04-06 21:35:43', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2643, '2026-04-06 21:35:46', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2644, '2026-04-06 21:36:38', 'Productos', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo producto: Colorm'),
(2645, '2026-04-06 21:36:43', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2646, '2026-04-06 21:36:47', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2647, '2026-04-06 21:36:53', 'Productos', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el producto ID: 49'),
(2648, '2026-04-06 21:37:01', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2649, '2026-04-06 21:37:04', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2650, '2026-04-06 21:43:58', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2651, '2026-04-06 21:44:01', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2652, '2026-04-06 21:45:11', 'Productos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el producto: Auriculares Redmi Bu | Antes: {\"id_producto\":38,\"serial\":\"0011\",\"nombre_producto\":\"Auriculares Redmi Bu\",\"descripcion_producto\":\"Sonido de alta fidelidad con cancelaci\\u00f3n de ruido.\",\"id_modelo\":31,\"id_categoria\":15,\"stock\":16,\"stock_minimo\":5,\"stock_maximo\":20,\"clausula_garantia\":\"Garant\\u00eda de 1 mes de duraci\\u00f3n\",\"precio\":37.97,\"estado\":\"habilitado\",\"imagen\":\"assets\\\\img\\\\productos\\\\producto_50.jpg\"} | Después: {\"id_producto\":38,\"serial\":\"\",\"nombre_producto\":\"\",\"descripcion_producto\":null,\"id_modelo\":null,\"id_categoria\":15,\"stock\":null,\"stock_minimo\":null,\"stock_maximo\":null,\"clausula_garantia\":\"\",\"precio\":null,\"estado\":\"habilitado\",\"imagen\":\"assets\\\\img\\\\productos\\\\producto_50.jpg\"}'),
(2653, '2026-04-06 21:45:13', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2654, '2026-04-06 21:45:15', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2655, '2026-04-06 21:45:59', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2656, '2026-04-06 21:46:01', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2657, '2026-04-06 21:48:27', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2658, '2026-04-06 21:48:28', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2659, '2026-04-06 21:48:55', 'Productos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el producto: Cutter  | Antes: {\"id_producto\":30,\"serial\":\"0003\",\"nombre_producto\":\"Cutter 360\",\"descripcion_producto\":\"Corta en todas direcciones. Ideal para manualidades.\",\"id_modelo\":31,\"id_categoria\":15,\"stock\":50,\"stock_minimo\":10,\"stock_maximo\":100,\"clausula_garantia\":\"Garant\\u00eda valida en los primeros 365 d\\u00edas\",\"precio\":10.75,\"estado\":\"habilitado\",\"imagen\":\"assets\\\\img\\\\productos\\\\producto_42.jpg\"} | Después: {\"id_producto\":30,\"serial\":\"0003\",\"nombre_producto\":\"Cutter \",\"descripcion_producto\":\"Corta en todas direcciones Ideal para manualidades\",\"id_modelo\":31,\"id_categoria\":15,\"stock\":50,\"stock_minimo\":10,\"stock_maximo\":100,\"clausula_garantia\":\"Garant\\u00eda valida en los primeros 365 d\\u00edas\",\"precio\":10.75,\"estado\":\"habilitado\",\"imagen\":\"assets\\\\img\\\\productos\\\\producto_42.jpg\"}'),
(2660, '2026-04-06 21:50:18', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2661, '2026-04-06 21:50:20', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2662, '2026-04-06 21:52:01', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2663, '2026-04-06 21:52:03', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2664, '2026-04-06 21:52:40', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2665, '2026-04-06 21:52:41', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2666, '2026-04-06 21:53:00', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2667, '2026-04-06 21:53:02', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2668, '2026-04-06 21:53:24', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2669, '2026-04-06 21:53:26', 'Productos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Productos'),
(2670, '2026-04-06 21:53:39', 'Categorias', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Categorias'),
(2671, '2026-04-06 21:53:49', 'Categorias', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó la categoría ID: 16'),
(2672, '2026-04-06 21:54:19', 'Categorias', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó la categoría: ejemplo'),
(2673, '2026-04-06 21:54:24', 'Categorias', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó la categoría ID: 20'),
(2674, '2026-04-06 21:54:31', 'Categorias', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó la categoría ID: 20'),
(2675, '2026-04-06 21:54:41', 'Proveedores', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Proveedores'),
(2676, '2026-04-06 21:54:58', 'Proveedores', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el proveedor: Brayan Cable que tenia los datos{\"id_proveedor\":1003,\"nombre_proveedor\":\"Brayan Cable\",\"rif_proveedor\":\"J-12121212-9\",\"nombre_representante\":\"Brayan Mendoza\",\"rif_representante\":\"V-31766316-0\",\"correo_proveedor\":\"diego00lopez@gmail.com\",\"direccion_proveedor\":\"Venezuela estado Zulia\\r\\nMaracaibo\",\"telefono_1\":\"0414-575-3363\",\"telefono_2\":\"0414-575-3363\",\"observacion\":\"hiii\",\"estado\":\"habilitado\"} con los datos {\"id_proveedor\":1003,\"nombre_proveedor\":\"Brayan Cable\",\"rif_proveedor\":\"J-12121212-9\",\"nombre_representante\":\"Brayan Mendoza\",\"rif_representante\":\"V-31766316-0\",\"correo_proveedor\":\"diego00lopez@gmail.com\",\"direccion_proveedor\":\"Venezuela estado Zulia\\r\\n\",\"telefono_1\":\"0414-575-3363\",\"telefono_2\":\"0414-575-3363\",\"observacion\":\"hiii\",\"estado\":\"habilitado\"}'),
(2677, '2026-04-06 21:55:30', 'Proveedores', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo proveedor: Thunder Net'),
(2678, '2026-04-06 21:55:35', 'Proveedores', 'ELIMINAR', NULL, NULL, 9, 'alta', 'El usuario eliminó el proveedor Thunder Net'),
(2679, '2026-04-06 21:55:57', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2680, '2026-04-06 22:00:28', 'Clientes', 'INCLUIR', NULL, NULL, 9, 'alta', 'El usuario incluyó al cliente: Diego'),
(2681, '2026-04-06 22:00:42', 'Clientes', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el cliente ID: 36'),
(2682, '2026-04-06 22:00:47', 'Clientes', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el cliente ID: 36'),
(2683, '2026-04-06 22:01:06', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2684, '2026-04-06 22:03:01', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2685, '2026-04-06 22:03:17', 'Clientes', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el cliente ID: 15'),
(2686, '2026-04-06 22:03:25', '9', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Clientes'),
(2687, '2026-04-06 22:03:39', 'Catalogo', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Catálogo'),
(2688, '2026-04-06 22:03:44', 'Catalogo', 'INCLUIR', NULL, NULL, 9, 'alta', 'El usuario agregó producto al carrito: Tarjeta SD Kingston  (Cantidad: 1)'),
(2689, '2026-04-06 22:04:02', 'Pedidos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pedidos'),
(2690, '2026-04-06 22:04:15', 'Pasarela', 'ACCESAR', NULL, NULL, 9, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2691, '2026-04-06 22:04:40', 'Pasarela', 'ACCESAR', NULL, NULL, 9, 'baja', 'El usuario accedió al módulo de pasarela de pagos'),
(2692, '2026-04-06 22:04:42', 'Pasarela', 'INGRESAR', NULL, NULL, 9, 'alta', 'Ingreso de pago con referencia 8768968'),
(2693, '2026-04-06 22:04:44', 'Pasarela', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pagos'),
(2694, '2026-04-06 22:04:54', 'Pasarela', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 8768968 a Pago Incompleto'),
(2695, '2026-04-06 22:05:00', 'Pasarela', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario cambió el estatus del pago de la referencia bancaria: 8768968 a Pago Procesado'),
(2696, '2026-04-06 22:05:28', 'Pedidos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pedidos'),
(2697, '2026-04-06 22:05:33', 'Pedidos', 'CANCELAR', NULL, NULL, 9, 'media', 'El usuario canceló la factura con ID: 37'),
(2698, '2026-04-06 22:05:35', 'Pedidos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pedidos'),
(2699, '2026-04-06 22:05:48', 'Pedidos', 'CANCELAR', NULL, NULL, 9, 'media', 'El usuario canceló la factura con ID: 51'),
(2700, '2026-04-06 22:05:50', 'Pedidos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Pedidos'),
(2701, '2026-04-06 22:06:04', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2702, '2026-04-06 22:06:13', 'Ordenes de despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(2703, '2026-04-06 22:06:23', 'Ordenes de despacho', 'CAMBIAR ESTADO', NULL, NULL, 9, 'media', 'El usuario cambió el estado de la orden de despacho con ID: 10 a Entregada'),
(2704, '2026-04-06 22:06:38', '3', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Despachos'),
(2705, '2026-04-06 22:06:45', 'Despacho', 'CAMBIAR ESTADO', NULL, NULL, 9, 'media', 'El usuario cambió el estado del despacho con ID: 41 a Despachado'),
(2706, '2026-04-06 22:06:45', 'Despacho', 'CAMBIAR ESTADO', NULL, NULL, 9, 'media', 'El usuario cambió el estado del despacho con ID: 41 a Despachado'),
(2707, '2026-04-06 22:06:50', 'Despacho', 'ANULAR', NULL, NULL, 9, 'media', 'El usuario anuló el despacho con ID: 11'),
(2708, '2026-04-06 22:06:59', 'Despacho', 'ANULAR', NULL, NULL, 9, 'media', 'El usuario anuló el despacho con ID: 25'),
(2709, '2026-04-06 22:07:12', 'Despacho', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Compra Física'),
(2710, '2026-04-06 22:07:58', 'Despacho', 'INCLUIR', NULL, NULL, 9, 'alta', 'El usuario CasaLai incluyó la compra física: 66'),
(2711, '2026-04-06 22:08:13', '15', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Cuentas Bancarias'),
(2712, '2026-04-06 22:08:35', 'Cuentas bancarias', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó la cuenta bancaria: Mercantil (ID: 29)'),
(2713, '2026-04-06 22:08:44', 'Cuentas bancarias', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó la cuenta bancaria: Mercantil (ID: 29)'),
(2714, '2026-04-06 22:08:49', 'Cuentas bancarias', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó la cuenta bancaria ID: 29'),
(2715, '2026-04-06 22:09:20', 'Cuentas bancarias', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva cuenta bancaria: BNC'),
(2716, '2026-04-06 22:09:43', 'Cuentas bancarias', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó la cuenta bancaria: BNC (ID: 37)'),
(2717, '2026-04-06 22:10:06', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2718, '2026-04-06 22:10:15', 'Permisos', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó los permisos de los roles del sistema'),
(2719, '2026-04-06 22:10:16', 'Permisos', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Permisos'),
(2720, '2026-04-06 22:10:29', 'Roles', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Roles'),
(2721, '2026-04-06 22:10:39', 'Roles', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó un nuevo rol: ejemplo'),
(2722, '2026-04-06 22:10:48', 'Roles', 'MODIFICAR', NULL, NULL, 9, 'media', 'El usuario modificó el rol: ejemploq (Antes: ejemplo)'),
(2723, '2026-04-06 22:10:51', 'Roles', 'ELIMINAR', NULL, NULL, 9, 'media', 'El usuario eliminó el rol: ejemploq'),
(2724, '2026-04-06 22:13:13', 'Catalogo', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Catálogo'),
(2725, '2026-04-06 22:13:21', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2726, '2026-04-06 22:13:53', 'Carrito', 'ACCESAR', NULL, NULL, 11, 'baja', 'El usuario accedió al módulo de Carrito'),
(2727, '2026-04-06 22:14:02', 'Pedidos', 'ACCESAR', NULL, NULL, 11, 'media', 'El usuario accedió al módulo de Pedidos'),
(2728, '2026-04-22 17:20:01', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2729, '2026-04-22 17:21:16', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2730, '2026-04-22 17:21:53', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2731, '2026-04-22 17:35:10', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2732, '2026-04-22 17:37:05', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2734, '2026-04-22 17:40:56', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2735, '2026-04-22 17:42:12', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2736, '2026-04-22 17:42:26', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2737, '2026-04-22 17:42:35', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2738, '2026-04-22 17:43:19', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2739, '2026-04-22 17:44:58', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2740, '2026-04-22 17:45:05', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2741, '2026-04-22 17:46:20', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2743, '2026-04-22 17:49:57', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2744, '2026-04-22 17:54:51', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2745, '2026-04-22 17:54:58', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2746, '2026-04-22 17:54:59', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2747, '2026-04-22 17:55:04', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2748, '2026-04-22 17:55:04', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2749, '2026-04-22 17:55:14', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2750, '2026-04-22 17:55:15', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2751, '2026-04-22 17:55:17', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2752, '2026-04-22 17:55:20', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2753, '2026-04-22 17:55:24', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2754, '2026-04-22 17:55:31', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2755, '2026-04-22 17:55:34', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2756, '2026-04-22 17:55:37', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2757, '2026-04-22 17:55:38', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2758, '2026-04-22 17:55:41', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2759, '2026-04-22 17:55:42', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2760, '2026-04-22 17:55:44', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2761, '2026-04-22 17:55:47', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2762, '2026-04-22 17:55:50', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2763, '2026-04-22 17:55:52', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2764, '2026-04-22 17:55:54', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2765, '2026-04-22 17:55:59', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2766, '2026-04-22 17:56:04', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2767, '2026-04-22 17:56:05', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2768, '2026-04-22 17:56:15', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2769, '2026-04-22 17:56:17', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2770, '2026-04-22 17:56:18', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2771, '2026-04-22 17:56:19', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2772, '2026-04-22 17:56:20', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2773, '2026-04-22 17:56:24', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2774, '2026-04-22 17:56:34', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2775, '2026-04-22 17:56:40', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2776, '2026-04-22 17:56:45', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2777, '2026-04-22 17:56:47', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2778, '2026-04-22 17:56:49', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2779, '2026-04-22 17:56:50', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2780, '2026-04-22 17:56:51', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2781, '2026-04-22 17:56:54', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2782, '2026-04-22 17:56:59', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2783, '2026-04-22 17:57:02', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2784, '2026-04-22 17:57:04', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2785, '2026-04-22 17:57:15', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2786, '2026-04-22 17:57:32', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2787, '2026-04-22 17:57:42', 'Usuario', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Usuarios'),
(2788, '2026-04-22 17:57:54', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2789, '2026-04-22 17:59:07', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2790, '2026-04-22 17:59:19', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2791, '2026-04-22 18:00:53', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2792, '2026-04-22 18:01:52', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2793, '2026-04-22 18:04:02', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2794, '2026-04-22 18:04:05', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2795, '2026-04-22 18:05:26', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2796, '2026-04-22 18:05:28', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2797, '2026-04-22 18:05:32', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2798, '2026-04-22 18:06:43', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2799, '2026-04-22 18:09:14', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2800, '2026-04-22 18:09:22', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2801, '2026-04-22 18:10:12', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2802, '2026-04-22 18:11:11', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2803, '2026-04-22 18:16:07', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2804, '2026-04-22 18:18:04', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2805, '2026-04-22 18:21:48', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2806, '2026-04-22 18:43:33', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2807, '2026-04-22 18:43:38', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2808, '2026-04-22 18:45:06', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2809, '2026-04-22 18:48:21', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2810, '2026-04-22 18:49:30', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2811, '2026-04-22 18:51:00', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2812, '2026-04-22 18:52:28', 'Recepcion', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva recepción: 988709'),
(2813, '2026-04-22 18:54:08', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2814, '2026-04-22 19:18:20', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2815, '2026-04-22 19:19:15', 'Recepcion', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva recepción: 573543'),
(2816, '2026-04-22 19:19:24', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2817, '2026-04-22 19:22:13', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2818, '2026-04-22 19:40:25', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2819, '2026-04-22 19:42:43', 'Recepcion', 'INCLUIR', NULL, NULL, 9, 'media', 'El usuario incluyó una nueva recepción: 765676'),
(2820, '2026-04-22 19:54:26', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2821, '2026-04-22 20:03:27', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2822, '2026-04-22 20:05:23', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2823, '2026-04-22 20:08:08', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion'),
(2824, '2026-04-22 20:21:45', 'Recepcion', 'ACCESAR', NULL, NULL, 9, 'media', 'El usuario accedió al módulo de Recepcion');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_modulos`
--

CREATE TABLE `tbl_modulos` (
  `id_modulo` int(11) NOT NULL,
  `nombre_modulo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_modulos`
--

INSERT INTO `tbl_modulos` (`id_modulo`, `nombre_modulo`) VALUES
(1, 'Usuario'),
(2, 'Recepcion'),
(3, 'Despacho'),
(4, 'Marcas'),
(5, 'Modelos'),
(6, 'Productos'),
(7, 'Categorias'),
(8, 'Proveedores'),
(9, 'Clientes'),
(10, 'Catalogo'),
(11, 'Carrito'),
(12, 'Pasarela'),
(13, 'Pedidos'),
(14, 'Ordenes de despacho'),
(15, 'Cuentas bancarias'),
(16, 'Finanzas'),
(17, 'Permisos'),
(18, 'Roles'),
(19, 'Bitacora'),
(20, 'Respaldo'),
(21, 'Compra Fisica'),
(22, 'Perfil de Usuario'),
(23, 'Notificaciones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_notificaciones`
--

CREATE TABLE `tbl_notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('pago','factura','despacho','sistema') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `id_referencia` int(11) DEFAULT NULL COMMENT 'ID en la otra base de datos',
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `prioridad` enum('baja','media','alta') NOT NULL DEFAULT 'media'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_notificaciones`
--

INSERT INTO `tbl_notificaciones` (`id_notificacion`, `id_usuario`, `tipo`, `titulo`, `mensaje`, `id_referencia`, `fecha_hora`, `leido`, `prioridad`) VALUES
(1, 17, '', 'Recepción modificada', 'Has modificado la recepción #1235', 10, '2025-07-27 19:27:03', 0, 'media'),
(5, 17, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(6, 3, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 1, 'media'),
(7, 5, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(8, 9, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 1, 'media'),
(9, 16, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(10, 4, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Simon', NULL, '2025-10-14 08:14:47', 0, 'media'),
(14, 17, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Simon', NULL, '2025-10-14 08:14:47', 0, 'media'),
(15, 3, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Simon', NULL, '2025-10-14 08:14:47', 1, 'media'),
(16, 5, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Simon', NULL, '2025-10-14 08:14:47', 0, 'media'),
(17, 9, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Simon', NULL, '2025-10-14 08:14:47', 1, 'media'),
(18, 16, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Simon', NULL, '2025-10-14 08:14:47', 0, 'media'),
(28, 17, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:11:25', 0, 'media'),
(29, 3, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:11:25', 1, 'media'),
(30, 5, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:11:25', 0, 'media'),
(31, 9, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:11:25', 1, 'media'),
(32, 16, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:11:25', 0, 'media'),
(43, 17, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:33:29', 0, 'media'),
(44, 3, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:33:29', 1, 'media'),
(45, 5, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:33:29', 0, 'media'),
(46, 9, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:33:29', 1, 'media'),
(47, 16, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Diego', NULL, '2025-10-14 21:33:29', 0, 'media'),
(51, 17, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Ben10', NULL, '2025-10-15 21:46:13', 0, 'media'),
(52, 3, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Ben10', NULL, '2025-10-15 21:46:13', 1, 'media'),
(53, 5, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Ben10', NULL, '2025-10-15 21:46:13', 0, 'media'),
(54, 9, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Ben10', NULL, '2025-10-15 21:46:13', 1, 'media'),
(55, 16, '', 'Permisos actualizados', 'Se han actualizado los permisos de los roles del sistema por el usuario Ben10', NULL, '2025-10-15 21:46:13', 0, 'media'),
(59, 17, '', 'Recepción anulada', 'Se ha anulado la recepción #1235 por parte del usuario Diego', 10, '2025-11-02 10:35:43', 0, 'media'),
(60, 3, '', 'Recepción anulada', 'Se ha anulado la recepción #1235 por parte del usuario Diego', 10, '2025-11-02 10:35:43', 1, 'media'),
(61, 5, '', 'Recepción anulada', 'Se ha anulado la recepción #1235 por parte del usuario Diego', 10, '2025-11-02 10:35:43', 0, 'media'),
(62, 9, '', 'Recepción anulada', 'Se ha anulado la recepción #1235 por parte del usuario Diego', 10, '2025-11-02 10:35:43', 0, 'media'),
(63, 16, '', 'Recepción anulada', 'Se ha anulado la recepción #1235 por parte del usuario Diego', 10, '2025-11-02 10:35:43', 0, 'media'),
(74, 17, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #897964 con 35 unidades por el usuario Diego', 12, '2025-11-02 10:36:46', 0, 'media'),
(75, 3, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #897964 con 35 unidades por el usuario Diego', 12, '2025-11-02 10:36:46', 1, 'media'),
(76, 5, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #897964 con 35 unidades por el usuario Diego', 12, '2025-11-02 10:36:46', 0, 'media'),
(77, 9, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #897964 con 35 unidades por el usuario Diego', 12, '2025-11-02 10:36:46', 1, 'media'),
(78, 16, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #897964 con 35 unidades por el usuario Diego', 12, '2025-11-02 10:36:46', 0, 'media'),
(89, 17, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #096843 con 1 unidades por el usuario Diego', 13, '2025-11-02 10:43:36', 0, 'media'),
(90, 3, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #096843 con 1 unidades por el usuario Diego', 13, '2025-11-02 10:43:36', 1, 'media'),
(91, 5, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #096843 con 1 unidades por el usuario Diego', 13, '2025-11-02 10:43:36', 0, 'media'),
(92, 9, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #096843 con 1 unidades por el usuario Diego', 13, '2025-11-02 10:43:36', 1, 'media'),
(93, 16, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #096843 con 1 unidades por el usuario Diego', 13, '2025-11-02 10:43:36', 0, 'media'),
(104, 17, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #976978 con 1 unidades por el usuario Diego', 14, '2025-11-02 10:46:20', 0, 'media'),
(105, 3, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #976978 con 1 unidades por el usuario Diego', 14, '2025-11-02 10:46:20', 1, 'media'),
(106, 5, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #976978 con 1 unidades por el usuario Diego', 14, '2025-11-02 10:46:20', 0, 'media'),
(107, 9, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #976978 con 1 unidades por el usuario Diego', 14, '2025-11-02 10:46:20', 1, 'media'),
(108, 16, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #976978 con 1 unidades por el usuario Diego', 14, '2025-11-02 10:46:20', 0, 'media'),
(119, 17, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #867674 con 1 unidades por el usuario Diego', 15, '2025-11-02 10:50:16', 0, 'media'),
(120, 3, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #867674 con 1 unidades por el usuario Diego', 15, '2025-11-02 10:50:16', 1, 'media'),
(121, 5, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #867674 con 1 unidades por el usuario Diego', 15, '2025-11-02 10:50:16', 0, 'media'),
(122, 9, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #867674 con 1 unidades por el usuario Diego', 15, '2025-11-02 10:50:16', 1, 'media'),
(123, 16, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #867674 con 1 unidades por el usuario Diego', 15, '2025-11-02 10:50:16', 0, 'media'),
(124, 17, '', 'Recepción anulada', 'Se ha anulado la recepción #976978 por parte del usuario Diego', 14, '2025-11-06 10:00:49', 0, 'media'),
(128, 3, '', 'Recepción anulada', 'Se ha anulado la recepción #976978 por parte del usuario Diego', 14, '2025-11-06 10:00:49', 1, 'media'),
(129, 9, '', 'Recepción anulada', 'Se ha anulado la recepción #976978 por parte del usuario Diego', 14, '2025-11-06 10:00:49', 1, 'media'),
(130, 16, '', 'Recepción anulada', 'Se ha anulado la recepción #976978 por parte del usuario Diego', 14, '2025-11-06 10:00:49', 0, 'media'),
(131, 17, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #098978 con 21 unidades por el usuario Diego', 16, '2025-11-06 10:01:24', 0, 'media'),
(135, 3, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #098978 con 21 unidades por el usuario Diego', 16, '2025-11-06 10:01:24', 1, 'media'),
(136, 9, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #098978 con 21 unidades por el usuario Diego', 16, '2025-11-06 10:01:24', 1, 'media'),
(137, 16, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #098978 con 21 unidades por el usuario Diego', 16, '2025-11-06 10:01:24', 0, 'media'),
(138, 3, '', 'Orden de despacho anulada', 'Se ha anulado la orden de despacho con ID 5 por parte del usuario Diego', 5, '2025-11-06 12:21:07', 1, 'media'),
(139, 9, '', 'Orden de despacho anulada', 'Se ha anulado la orden de despacho con ID 5 por parte del usuario Diego', 5, '2025-11-06 12:21:07', 1, 'media'),
(140, 16, '', 'Orden de despacho anulada', 'Se ha anulado la orden de despacho con ID 5 por parte del usuario Diego', 5, '2025-11-06 12:21:07', 0, 'media'),
(141, 17, '', 'Recepción anulada', 'Se ha anulado la recepción #00012 por parte del usuario Diego', 11, '2025-11-18 21:54:05', 0, 'media'),
(145, 3, '', 'Recepción anulada', 'Se ha anulado la recepción #00012 por parte del usuario Diego', 11, '2025-11-18 21:54:05', 1, 'media'),
(146, 9, '', 'Recepción anulada', 'Se ha anulado la recepción #00012 por parte del usuario Diego', 11, '2025-11-18 21:54:05', 1, 'media'),
(147, 16, '', 'Recepción anulada', 'Se ha anulado la recepción #00012 por parte del usuario Diego', 11, '2025-11-18 21:54:05', 0, 'media');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_permisos`
--

CREATE TABLE `tbl_permisos` (
  `id` int(11) NOT NULL,
  `accion` varchar(10) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `estatus` enum('Permitido','No Permitido') NOT NULL DEFAULT 'Permitido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_permisos`
--

INSERT INTO `tbl_permisos` (`id`, `accion`, `id_rol`, `id_modulo`, `estatus`) VALUES
(5, 'ingresar', 6, 1, 'Permitido'),
(10, 'consultar', 6, 1, 'Permitido'),
(15, 'modificar', 6, 1, 'Permitido'),
(20, 'incluir', 6, 1, 'Permitido'),
(25, 'eliminar', 6, 1, 'Permitido'),
(30, 'reportar', 6, 1, 'Permitido'),
(35, 'ingresar', 6, 2, 'Permitido'),
(40, 'consultar', 6, 2, 'Permitido'),
(45, 'modificar', 6, 2, 'Permitido'),
(50, 'incluir', 6, 2, 'Permitido'),
(55, 'eliminar', 6, 2, 'Permitido'),
(60, 'reportar', 6, 2, 'Permitido'),
(65, 'ingresar', 6, 3, 'Permitido'),
(70, 'consultar', 6, 3, 'Permitido'),
(75, 'modificar', 6, 3, 'Permitido'),
(80, 'incluir', 6, 3, 'Permitido'),
(85, 'eliminar', 6, 3, 'Permitido'),
(90, 'reportar', 6, 3, 'Permitido'),
(95, 'ingresar', 6, 4, 'Permitido'),
(100, 'consultar', 6, 4, 'Permitido'),
(105, 'modificar', 6, 4, 'Permitido'),
(110, 'incluir', 6, 4, 'Permitido'),
(115, 'eliminar', 6, 4, 'Permitido'),
(120, 'reportar', 6, 4, 'Permitido'),
(125, 'ingresar', 6, 5, 'Permitido'),
(130, 'consultar', 6, 5, 'Permitido'),
(135, 'modificar', 6, 5, 'Permitido'),
(140, 'incluir', 6, 5, 'Permitido'),
(145, 'eliminar', 6, 5, 'Permitido'),
(150, 'reportar', 6, 5, 'Permitido'),
(155, 'ingresar', 6, 6, 'Permitido'),
(160, 'consultar', 6, 6, 'Permitido'),
(165, 'modificar', 6, 6, 'Permitido'),
(170, 'incluir', 6, 6, 'Permitido'),
(175, 'eliminar', 6, 6, 'Permitido'),
(180, 'reportar', 6, 6, 'Permitido'),
(185, 'ingresar', 6, 7, 'Permitido'),
(190, 'consultar', 6, 7, 'Permitido'),
(195, 'modificar', 6, 7, 'Permitido'),
(200, 'incluir', 6, 7, 'Permitido'),
(205, 'eliminar', 6, 7, 'Permitido'),
(210, 'reportar', 6, 7, 'Permitido'),
(215, 'ingresar', 6, 8, 'Permitido'),
(220, 'consultar', 6, 8, 'Permitido'),
(225, 'modificar', 6, 8, 'Permitido'),
(230, 'incluir', 6, 8, 'Permitido'),
(235, 'eliminar', 6, 8, 'Permitido'),
(240, 'reportar', 6, 8, 'Permitido'),
(245, 'ingresar', 6, 9, 'Permitido'),
(250, 'consultar', 6, 9, 'Permitido'),
(255, 'modificar', 6, 9, 'Permitido'),
(260, 'incluir', 6, 9, 'Permitido'),
(265, 'eliminar', 6, 9, 'Permitido'),
(270, 'reportar', 6, 9, 'Permitido'),
(275, 'ingresar', 6, 10, 'Permitido'),
(280, 'consultar', 6, 10, 'Permitido'),
(285, 'modificar', 6, 10, 'Permitido'),
(290, 'incluir', 6, 10, 'Permitido'),
(295, 'eliminar', 6, 10, 'Permitido'),
(300, 'reportar', 6, 10, 'Permitido'),
(305, 'ingresar', 6, 11, 'Permitido'),
(310, 'consultar', 6, 11, 'Permitido'),
(315, 'modificar', 6, 11, 'Permitido'),
(320, 'incluir', 6, 11, 'Permitido'),
(325, 'eliminar', 6, 11, 'Permitido'),
(330, 'reportar', 6, 11, 'Permitido'),
(335, 'ingresar', 6, 12, 'Permitido'),
(340, 'consultar', 6, 12, 'Permitido'),
(345, 'modificar', 6, 12, 'Permitido'),
(350, 'incluir', 6, 12, 'Permitido'),
(355, 'eliminar', 6, 12, 'Permitido'),
(360, 'reportar', 6, 12, 'Permitido'),
(365, 'ingresar', 6, 13, 'Permitido'),
(370, 'consultar', 6, 13, 'Permitido'),
(375, 'modificar', 6, 13, 'Permitido'),
(380, 'incluir', 6, 13, 'Permitido'),
(385, 'eliminar', 6, 13, 'Permitido'),
(390, 'reportar', 6, 13, 'Permitido'),
(395, 'ingresar', 6, 14, 'Permitido'),
(400, 'consultar', 6, 14, 'Permitido'),
(405, 'modificar', 6, 14, 'Permitido'),
(410, 'incluir', 6, 14, 'Permitido'),
(415, 'eliminar', 6, 14, 'Permitido'),
(420, 'reportar', 6, 14, 'Permitido'),
(425, 'ingresar', 6, 15, 'Permitido'),
(430, 'consultar', 6, 15, 'Permitido'),
(435, 'modificar', 6, 15, 'Permitido'),
(440, 'incluir', 6, 15, 'Permitido'),
(445, 'eliminar', 6, 15, 'Permitido'),
(450, 'reportar', 6, 15, 'Permitido'),
(455, 'ingresar', 6, 16, 'Permitido'),
(460, 'consultar', 6, 16, 'Permitido'),
(465, 'modificar', 6, 16, 'Permitido'),
(470, 'incluir', 6, 16, 'Permitido'),
(475, 'eliminar', 6, 16, 'Permitido'),
(480, 'reportar', 6, 16, 'Permitido'),
(485, 'ingresar', 6, 17, 'Permitido'),
(490, 'consultar', 6, 17, 'Permitido'),
(495, 'modificar', 6, 17, 'Permitido'),
(500, 'incluir', 6, 17, 'Permitido'),
(505, 'eliminar', 6, 17, 'Permitido'),
(510, 'reportar', 6, 17, 'Permitido'),
(515, 'ingresar', 6, 18, 'Permitido'),
(520, 'consultar', 6, 18, 'Permitido'),
(525, 'modificar', 6, 18, 'Permitido'),
(530, 'incluir', 6, 18, 'Permitido'),
(535, 'eliminar', 6, 18, 'Permitido'),
(540, 'reportar', 6, 18, 'Permitido'),
(545, 'ingresar', 6, 19, 'Permitido'),
(550, 'consultar', 6, 19, 'Permitido'),
(555, 'modificar', 6, 19, 'Permitido'),
(560, 'incluir', 6, 19, 'Permitido'),
(565, 'eliminar', 6, 19, 'Permitido'),
(570, 'reportar', 6, 19, 'Permitido'),
(575, 'ingresar', 6, 20, 'Permitido'),
(580, 'consultar', 6, 20, 'Permitido'),
(585, 'modificar', 6, 20, 'Permitido'),
(590, 'incluir', 6, 20, 'Permitido'),
(595, 'eliminar', 6, 20, 'Permitido'),
(600, 'reportar', 6, 20, 'Permitido'),
(10413, 'ingresar', 6, 21, 'Permitido'),
(10414, 'ingresar', 6, 22, 'Permitido'),
(10415, 'ingresar', 6, 23, 'Permitido'),
(10416, 'consultar', 6, 21, 'Permitido'),
(10417, 'consultar', 6, 22, 'Permitido'),
(10418, 'consultar', 6, 23, 'Permitido'),
(10419, 'modificar', 6, 21, 'Permitido'),
(10420, 'modificar', 6, 22, 'Permitido'),
(10421, 'modificar', 6, 23, 'Permitido'),
(10422, 'incluir', 6, 21, 'Permitido'),
(10423, 'incluir', 6, 22, 'Permitido'),
(10424, 'incluir', 6, 23, 'Permitido'),
(10425, 'eliminar', 6, 21, 'Permitido'),
(10426, 'eliminar', 6, 22, 'Permitido'),
(10427, 'eliminar', 6, 23, 'Permitido'),
(10428, 'generar re', 6, 21, 'Permitido'),
(10429, 'generar re', 6, 22, 'Permitido'),
(10430, 'generar re', 6, 23, 'Permitido'),
(10492, 'ingresar', 6, 1, ''),
(10493, 'consultar', 6, 1, ''),
(10494, 'modificar', 6, 1, ''),
(10495, 'incluir', 6, 1, ''),
(10496, 'eliminar', 6, 1, ''),
(10497, 'generar re', 6, 1, ''),
(10528, 'ingresar', 6, 2, ''),
(10529, 'consultar', 6, 2, ''),
(10530, 'modificar', 6, 2, ''),
(10531, 'incluir', 6, 2, ''),
(10532, 'eliminar', 6, 2, ''),
(10533, 'generar re', 6, 2, ''),
(10564, 'ingresar', 6, 3, ''),
(10565, 'consultar', 6, 3, ''),
(10566, 'modificar', 6, 3, ''),
(10567, 'incluir', 6, 3, ''),
(10568, 'eliminar', 6, 3, ''),
(10569, 'generar re', 6, 3, ''),
(10600, 'ingresar', 6, 4, ''),
(10601, 'consultar', 6, 4, ''),
(10602, 'modificar', 6, 4, ''),
(10603, 'incluir', 6, 4, ''),
(10604, 'eliminar', 6, 4, ''),
(10605, 'generar re', 6, 4, ''),
(10636, 'ingresar', 6, 5, ''),
(10637, 'consultar', 6, 5, ''),
(10638, 'modificar', 6, 5, ''),
(10639, 'incluir', 6, 5, ''),
(10640, 'eliminar', 6, 5, ''),
(10641, 'generar re', 6, 5, ''),
(10672, 'ingresar', 6, 6, ''),
(10673, 'consultar', 6, 6, ''),
(10674, 'modificar', 6, 6, ''),
(10675, 'incluir', 6, 6, ''),
(10676, 'eliminar', 6, 6, ''),
(10677, 'generar re', 6, 6, ''),
(10708, 'ingresar', 6, 7, ''),
(10709, 'consultar', 6, 7, ''),
(10710, 'modificar', 6, 7, ''),
(10711, 'incluir', 6, 7, ''),
(10712, 'eliminar', 6, 7, ''),
(10713, 'generar re', 6, 7, ''),
(10744, 'ingresar', 6, 8, ''),
(10745, 'consultar', 6, 8, ''),
(10746, 'modificar', 6, 8, ''),
(10747, 'incluir', 6, 8, ''),
(10748, 'eliminar', 6, 8, ''),
(10749, 'generar re', 6, 8, ''),
(10780, 'ingresar', 6, 9, ''),
(10781, 'consultar', 6, 9, ''),
(10782, 'modificar', 6, 9, ''),
(10783, 'incluir', 6, 9, ''),
(10784, 'eliminar', 6, 9, ''),
(10785, 'generar re', 6, 9, ''),
(10816, 'ingresar', 6, 10, ''),
(10817, 'consultar', 6, 10, ''),
(10818, 'modificar', 6, 10, ''),
(10819, 'incluir', 6, 10, ''),
(10820, 'eliminar', 6, 10, ''),
(10821, 'generar re', 6, 10, ''),
(10852, 'ingresar', 6, 11, ''),
(10853, 'consultar', 6, 11, ''),
(10854, 'modificar', 6, 11, ''),
(10855, 'incluir', 6, 11, ''),
(10856, 'eliminar', 6, 11, ''),
(10857, 'generar re', 6, 11, ''),
(10888, 'ingresar', 6, 12, ''),
(10889, 'consultar', 6, 12, ''),
(10890, 'modificar', 6, 12, ''),
(10891, 'incluir', 6, 12, ''),
(10892, 'eliminar', 6, 12, ''),
(10893, 'generar re', 6, 12, ''),
(10924, 'ingresar', 6, 13, ''),
(10925, 'consultar', 6, 13, ''),
(10926, 'modificar', 6, 13, ''),
(10927, 'incluir', 6, 13, ''),
(10928, 'eliminar', 6, 13, ''),
(10929, 'generar re', 6, 13, ''),
(10960, 'ingresar', 6, 14, ''),
(10961, 'consultar', 6, 14, ''),
(10962, 'modificar', 6, 14, ''),
(10963, 'incluir', 6, 14, ''),
(10964, 'eliminar', 6, 14, ''),
(10965, 'generar re', 6, 14, ''),
(10996, 'ingresar', 6, 15, ''),
(10997, 'consultar', 6, 15, ''),
(10998, 'modificar', 6, 15, ''),
(10999, 'incluir', 6, 15, ''),
(11000, 'eliminar', 6, 15, ''),
(11001, 'generar re', 6, 15, ''),
(11032, 'ingresar', 6, 16, ''),
(11033, 'consultar', 6, 16, ''),
(11034, 'modificar', 6, 16, ''),
(11035, 'incluir', 6, 16, ''),
(11036, 'eliminar', 6, 16, ''),
(11037, 'generar re', 6, 16, ''),
(11068, 'ingresar', 6, 17, ''),
(11069, 'consultar', 6, 17, ''),
(11070, 'modificar', 6, 17, ''),
(11071, 'incluir', 6, 17, ''),
(11072, 'eliminar', 6, 17, ''),
(11073, 'generar re', 6, 17, ''),
(11104, 'ingresar', 6, 18, ''),
(11105, 'consultar', 6, 18, ''),
(11106, 'modificar', 6, 18, ''),
(11107, 'incluir', 6, 18, ''),
(11108, 'eliminar', 6, 18, ''),
(11109, 'generar re', 6, 18, ''),
(11140, 'ingresar', 6, 19, ''),
(11141, 'consultar', 6, 19, ''),
(11142, 'modificar', 6, 19, ''),
(11143, 'incluir', 6, 19, ''),
(11144, 'eliminar', 6, 19, ''),
(11145, 'generar re', 6, 19, ''),
(11176, 'ingresar', 6, 20, ''),
(11177, 'consultar', 6, 20, ''),
(11178, 'modificar', 6, 20, ''),
(11179, 'incluir', 6, 20, ''),
(11180, 'eliminar', 6, 20, ''),
(11181, 'generar re', 6, 20, ''),
(11248, 'ingresar', 6, 22, ''),
(11249, 'consultar', 6, 22, ''),
(11250, 'modificar', 6, 22, ''),
(11251, 'incluir', 6, 22, ''),
(11252, 'eliminar', 6, 22, ''),
(11253, 'generar re', 6, 22, ''),
(11284, 'ingresar', 6, 23, ''),
(11285, 'consultar', 6, 23, ''),
(11286, 'modificar', 6, 23, ''),
(11287, 'incluir', 6, 23, ''),
(11288, 'eliminar', 6, 23, ''),
(11289, 'generar re', 6, 23, ''),
(12814, 'ingresar', 1, 1, 'No Permitido'),
(12815, 'consultar', 1, 1, 'No Permitido'),
(12816, 'incluir', 1, 1, 'No Permitido'),
(12817, 'modificar', 1, 1, 'No Permitido'),
(12818, 'eliminar', 1, 1, 'No Permitido'),
(12819, 'generar re', 1, 1, 'No Permitido'),
(12820, 'ingresar', 1, 2, 'No Permitido'),
(12821, 'consultar', 1, 2, 'No Permitido'),
(12822, 'incluir', 1, 2, 'No Permitido'),
(12823, 'modificar', 1, 2, 'No Permitido'),
(12824, 'eliminar', 1, 2, 'No Permitido'),
(12825, 'generar re', 1, 2, 'No Permitido'),
(12826, 'ingresar', 1, 3, 'No Permitido'),
(12827, 'consultar', 1, 3, 'No Permitido'),
(12828, 'incluir', 1, 3, 'No Permitido'),
(12829, 'modificar', 1, 3, 'No Permitido'),
(12830, 'eliminar', 1, 3, 'No Permitido'),
(12831, 'generar re', 1, 3, 'No Permitido'),
(12832, 'ingresar', 1, 4, 'No Permitido'),
(12833, 'consultar', 1, 4, 'No Permitido'),
(12834, 'incluir', 1, 4, 'No Permitido'),
(12835, 'modificar', 1, 4, 'No Permitido'),
(12836, 'eliminar', 1, 4, 'No Permitido'),
(12837, 'generar re', 1, 4, 'No Permitido'),
(12838, 'ingresar', 1, 5, 'No Permitido'),
(12839, 'consultar', 1, 5, 'No Permitido'),
(12840, 'incluir', 1, 5, 'No Permitido'),
(12841, 'modificar', 1, 5, 'No Permitido'),
(12842, 'eliminar', 1, 5, 'No Permitido'),
(12843, 'generar re', 1, 5, 'No Permitido'),
(12844, 'ingresar', 1, 6, 'No Permitido'),
(12845, 'consultar', 1, 6, 'No Permitido'),
(12846, 'incluir', 1, 6, 'No Permitido'),
(12847, 'modificar', 1, 6, 'No Permitido'),
(12848, 'eliminar', 1, 6, 'No Permitido'),
(12849, 'generar re', 1, 6, 'No Permitido'),
(12850, 'ingresar', 1, 7, 'No Permitido'),
(12851, 'consultar', 1, 7, 'No Permitido'),
(12852, 'incluir', 1, 7, 'No Permitido'),
(12853, 'modificar', 1, 7, 'No Permitido'),
(12854, 'eliminar', 1, 7, 'No Permitido'),
(12855, 'generar re', 1, 7, 'No Permitido'),
(12856, 'ingresar', 1, 8, 'No Permitido'),
(12857, 'consultar', 1, 8, 'No Permitido'),
(12858, 'incluir', 1, 8, 'No Permitido'),
(12859, 'modificar', 1, 8, 'No Permitido'),
(12860, 'eliminar', 1, 8, 'No Permitido'),
(12861, 'generar re', 1, 8, 'No Permitido'),
(12862, 'ingresar', 1, 9, 'No Permitido'),
(12863, 'consultar', 1, 9, 'No Permitido'),
(12864, 'incluir', 1, 9, 'No Permitido'),
(12865, 'modificar', 1, 9, 'No Permitido'),
(12866, 'eliminar', 1, 9, 'No Permitido'),
(12867, 'generar re', 1, 9, 'No Permitido'),
(12868, 'ingresar', 1, 10, 'No Permitido'),
(12869, 'consultar', 1, 10, 'No Permitido'),
(12870, 'incluir', 1, 10, 'No Permitido'),
(12871, 'modificar', 1, 10, 'No Permitido'),
(12872, 'eliminar', 1, 10, 'No Permitido'),
(12873, 'generar re', 1, 10, 'No Permitido'),
(12874, 'ingresar', 1, 11, 'No Permitido'),
(12875, 'consultar', 1, 11, 'No Permitido'),
(12876, 'incluir', 1, 11, 'No Permitido'),
(12877, 'modificar', 1, 11, 'No Permitido'),
(12878, 'eliminar', 1, 11, 'No Permitido'),
(12879, 'generar re', 1, 11, 'No Permitido'),
(12880, 'ingresar', 1, 12, 'No Permitido'),
(12881, 'consultar', 1, 12, 'No Permitido'),
(12882, 'incluir', 1, 12, 'No Permitido'),
(12883, 'modificar', 1, 12, 'No Permitido'),
(12884, 'eliminar', 1, 12, 'No Permitido'),
(12885, 'generar re', 1, 12, 'No Permitido'),
(12886, 'ingresar', 1, 13, 'No Permitido'),
(12887, 'consultar', 1, 13, 'No Permitido'),
(12888, 'incluir', 1, 13, 'No Permitido'),
(12889, 'modificar', 1, 13, 'No Permitido'),
(12890, 'eliminar', 1, 13, 'No Permitido'),
(12891, 'generar re', 1, 13, 'No Permitido'),
(12892, 'ingresar', 1, 14, 'No Permitido'),
(12893, 'consultar', 1, 14, 'No Permitido'),
(12894, 'incluir', 1, 14, 'No Permitido'),
(12895, 'modificar', 1, 14, 'No Permitido'),
(12896, 'eliminar', 1, 14, 'No Permitido'),
(12897, 'generar re', 1, 14, 'No Permitido'),
(12898, 'ingresar', 1, 15, 'No Permitido'),
(12899, 'consultar', 1, 15, 'No Permitido'),
(12900, 'incluir', 1, 15, 'No Permitido'),
(12901, 'modificar', 1, 15, 'No Permitido'),
(12902, 'eliminar', 1, 15, 'No Permitido'),
(12903, 'generar re', 1, 15, 'No Permitido'),
(12904, 'ingresar', 1, 16, 'No Permitido'),
(12905, 'consultar', 1, 16, 'No Permitido'),
(12906, 'incluir', 1, 16, 'No Permitido'),
(12907, 'modificar', 1, 16, 'No Permitido'),
(12908, 'eliminar', 1, 16, 'No Permitido'),
(12909, 'generar re', 1, 16, 'No Permitido'),
(12910, 'ingresar', 1, 17, 'No Permitido'),
(12911, 'consultar', 1, 17, 'No Permitido'),
(12912, 'incluir', 1, 17, 'No Permitido'),
(12913, 'modificar', 1, 17, 'No Permitido'),
(12914, 'eliminar', 1, 17, 'No Permitido'),
(12915, 'generar re', 1, 17, 'No Permitido'),
(12916, 'ingresar', 1, 18, 'No Permitido'),
(12917, 'consultar', 1, 18, 'No Permitido'),
(12918, 'incluir', 1, 18, 'No Permitido'),
(12919, 'modificar', 1, 18, 'No Permitido'),
(12920, 'eliminar', 1, 18, 'No Permitido'),
(12921, 'generar re', 1, 18, 'No Permitido'),
(12922, 'ingresar', 1, 19, 'No Permitido'),
(12923, 'consultar', 1, 19, 'No Permitido'),
(12924, 'incluir', 1, 19, 'No Permitido'),
(12925, 'modificar', 1, 19, 'No Permitido'),
(12926, 'eliminar', 1, 19, 'No Permitido'),
(12927, 'generar re', 1, 19, 'No Permitido'),
(12928, 'ingresar', 1, 20, 'No Permitido'),
(12929, 'consultar', 1, 20, 'No Permitido'),
(12930, 'incluir', 1, 20, 'No Permitido'),
(12931, 'modificar', 1, 20, 'No Permitido'),
(12932, 'eliminar', 1, 20, 'No Permitido'),
(12933, 'generar re', 1, 20, 'No Permitido'),
(12934, 'ingresar', 1, 21, 'Permitido'),
(12935, 'consultar', 1, 21, 'Permitido'),
(12936, 'incluir', 1, 21, 'Permitido'),
(12937, 'modificar', 1, 21, 'Permitido'),
(12938, 'eliminar', 1, 21, 'Permitido'),
(12939, 'generar re', 1, 21, 'No Permitido'),
(12940, 'ingresar', 1, 22, 'Permitido'),
(12941, 'consultar', 1, 22, 'Permitido'),
(12942, 'incluir', 1, 22, 'Permitido'),
(12943, 'modificar', 1, 22, 'Permitido'),
(12944, 'eliminar', 1, 22, 'Permitido'),
(12945, 'generar re', 1, 22, 'No Permitido'),
(12946, 'ingresar', 1, 23, 'Permitido'),
(12947, 'consultar', 1, 23, 'Permitido'),
(12948, 'incluir', 1, 23, 'Permitido'),
(12949, 'modificar', 1, 23, 'Permitido'),
(12950, 'eliminar', 1, 23, 'Permitido'),
(12951, 'generar re', 1, 23, 'No Permitido'),
(12952, 'ingresar', 2, 1, 'Permitido'),
(12953, 'consultar', 2, 1, 'Permitido'),
(12954, 'incluir', 2, 1, 'Permitido'),
(12955, 'modificar', 2, 1, 'Permitido'),
(12956, 'eliminar', 2, 1, 'Permitido'),
(12957, 'generar re', 2, 1, 'Permitido'),
(12958, 'ingresar', 2, 2, 'No Permitido'),
(12959, 'consultar', 2, 2, 'No Permitido'),
(12960, 'incluir', 2, 2, 'No Permitido'),
(12961, 'modificar', 2, 2, 'No Permitido'),
(12962, 'eliminar', 2, 2, 'No Permitido'),
(12963, 'generar re', 2, 2, 'No Permitido'),
(12964, 'ingresar', 2, 3, 'No Permitido'),
(12965, 'consultar', 2, 3, 'No Permitido'),
(12966, 'incluir', 2, 3, 'No Permitido'),
(12967, 'modificar', 2, 3, 'No Permitido'),
(12968, 'eliminar', 2, 3, 'No Permitido'),
(12969, 'generar re', 2, 3, 'No Permitido'),
(12970, 'ingresar', 2, 4, 'No Permitido'),
(12971, 'consultar', 2, 4, 'No Permitido'),
(12972, 'incluir', 2, 4, 'No Permitido'),
(12973, 'modificar', 2, 4, 'No Permitido'),
(12974, 'eliminar', 2, 4, 'No Permitido'),
(12975, 'generar re', 2, 4, 'No Permitido'),
(12976, 'ingresar', 2, 5, 'No Permitido'),
(12977, 'consultar', 2, 5, 'No Permitido'),
(12978, 'incluir', 2, 5, 'No Permitido'),
(12979, 'modificar', 2, 5, 'No Permitido'),
(12980, 'eliminar', 2, 5, 'No Permitido'),
(12981, 'generar re', 2, 5, 'No Permitido'),
(12982, 'ingresar', 2, 6, 'No Permitido'),
(12983, 'consultar', 2, 6, 'No Permitido'),
(12984, 'incluir', 2, 6, 'No Permitido'),
(12985, 'modificar', 2, 6, 'No Permitido'),
(12986, 'eliminar', 2, 6, 'No Permitido'),
(12987, 'generar re', 2, 6, 'No Permitido'),
(12988, 'ingresar', 2, 7, 'No Permitido'),
(12989, 'consultar', 2, 7, 'No Permitido'),
(12990, 'incluir', 2, 7, 'No Permitido'),
(12991, 'modificar', 2, 7, 'No Permitido'),
(12992, 'eliminar', 2, 7, 'No Permitido'),
(12993, 'generar re', 2, 7, 'No Permitido'),
(12994, 'ingresar', 2, 8, 'No Permitido'),
(12995, 'consultar', 2, 8, 'No Permitido'),
(12996, 'incluir', 2, 8, 'No Permitido'),
(12997, 'modificar', 2, 8, 'No Permitido'),
(12998, 'eliminar', 2, 8, 'No Permitido'),
(12999, 'generar re', 2, 8, 'No Permitido'),
(13000, 'ingresar', 2, 9, 'No Permitido'),
(13001, 'consultar', 2, 9, 'No Permitido'),
(13002, 'incluir', 2, 9, 'No Permitido'),
(13003, 'modificar', 2, 9, 'No Permitido'),
(13004, 'eliminar', 2, 9, 'No Permitido'),
(13005, 'generar re', 2, 9, 'No Permitido'),
(13006, 'ingresar', 2, 10, 'No Permitido'),
(13007, 'consultar', 2, 10, 'No Permitido'),
(13008, 'incluir', 2, 10, 'No Permitido'),
(13009, 'modificar', 2, 10, 'No Permitido'),
(13010, 'eliminar', 2, 10, 'No Permitido'),
(13011, 'generar re', 2, 10, 'No Permitido'),
(13012, 'ingresar', 2, 11, 'No Permitido'),
(13013, 'consultar', 2, 11, 'No Permitido'),
(13014, 'incluir', 2, 11, 'No Permitido'),
(13015, 'modificar', 2, 11, 'No Permitido'),
(13016, 'eliminar', 2, 11, 'No Permitido'),
(13017, 'generar re', 2, 11, 'No Permitido'),
(13018, 'ingresar', 2, 12, 'No Permitido'),
(13019, 'consultar', 2, 12, 'No Permitido'),
(13020, 'incluir', 2, 12, 'No Permitido'),
(13021, 'modificar', 2, 12, 'No Permitido'),
(13022, 'eliminar', 2, 12, 'No Permitido'),
(13023, 'generar re', 2, 12, 'No Permitido'),
(13024, 'ingresar', 2, 13, 'No Permitido'),
(13025, 'consultar', 2, 13, 'No Permitido'),
(13026, 'incluir', 2, 13, 'No Permitido'),
(13027, 'modificar', 2, 13, 'No Permitido'),
(13028, 'eliminar', 2, 13, 'No Permitido'),
(13029, 'generar re', 2, 13, 'No Permitido'),
(13030, 'ingresar', 2, 14, 'No Permitido'),
(13031, 'consultar', 2, 14, 'No Permitido'),
(13032, 'incluir', 2, 14, 'No Permitido'),
(13033, 'modificar', 2, 14, 'No Permitido'),
(13034, 'eliminar', 2, 14, 'No Permitido'),
(13035, 'generar re', 2, 14, 'No Permitido'),
(13036, 'ingresar', 2, 15, 'No Permitido'),
(13037, 'consultar', 2, 15, 'No Permitido'),
(13038, 'incluir', 2, 15, 'No Permitido'),
(13039, 'modificar', 2, 15, 'No Permitido'),
(13040, 'eliminar', 2, 15, 'No Permitido'),
(13041, 'generar re', 2, 15, 'No Permitido'),
(13042, 'ingresar', 2, 16, 'No Permitido'),
(13043, 'consultar', 2, 16, 'No Permitido'),
(13044, 'incluir', 2, 16, 'No Permitido'),
(13045, 'modificar', 2, 16, 'No Permitido'),
(13046, 'eliminar', 2, 16, 'No Permitido'),
(13047, 'generar re', 2, 16, 'No Permitido'),
(13048, 'ingresar', 2, 17, 'No Permitido'),
(13049, 'consultar', 2, 17, 'No Permitido'),
(13050, 'incluir', 2, 17, 'No Permitido'),
(13051, 'modificar', 2, 17, 'No Permitido'),
(13052, 'eliminar', 2, 17, 'No Permitido'),
(13053, 'generar re', 2, 17, 'No Permitido'),
(13054, 'ingresar', 2, 18, 'No Permitido'),
(13055, 'consultar', 2, 18, 'No Permitido'),
(13056, 'incluir', 2, 18, 'No Permitido'),
(13057, 'modificar', 2, 18, 'No Permitido'),
(13058, 'eliminar', 2, 18, 'No Permitido'),
(13059, 'generar re', 2, 18, 'No Permitido'),
(13060, 'ingresar', 2, 19, 'No Permitido'),
(13061, 'consultar', 2, 19, 'No Permitido'),
(13062, 'incluir', 2, 19, 'No Permitido'),
(13063, 'modificar', 2, 19, 'No Permitido'),
(13064, 'eliminar', 2, 19, 'No Permitido'),
(13065, 'generar re', 2, 19, 'No Permitido'),
(13066, 'ingresar', 2, 20, 'No Permitido'),
(13067, 'consultar', 2, 20, 'No Permitido'),
(13068, 'incluir', 2, 20, 'No Permitido'),
(13069, 'modificar', 2, 20, 'No Permitido'),
(13070, 'eliminar', 2, 20, 'No Permitido'),
(13071, 'generar re', 2, 20, 'No Permitido'),
(13072, 'ingresar', 2, 21, 'Permitido'),
(13073, 'consultar', 2, 21, 'Permitido'),
(13074, 'incluir', 2, 21, 'Permitido'),
(13075, 'modificar', 2, 21, 'Permitido'),
(13076, 'eliminar', 2, 21, 'Permitido'),
(13077, 'generar re', 2, 21, 'No Permitido'),
(13078, 'ingresar', 2, 22, 'Permitido'),
(13079, 'consultar', 2, 22, 'Permitido'),
(13080, 'incluir', 2, 22, 'Permitido'),
(13081, 'modificar', 2, 22, 'Permitido'),
(13082, 'eliminar', 2, 22, 'Permitido'),
(13083, 'generar re', 2, 22, 'No Permitido'),
(13084, 'ingresar', 2, 23, 'Permitido'),
(13085, 'consultar', 2, 23, 'Permitido'),
(13086, 'incluir', 2, 23, 'Permitido'),
(13087, 'modificar', 2, 23, 'Permitido'),
(13088, 'eliminar', 2, 23, 'Permitido'),
(13089, 'generar re', 2, 23, 'No Permitido'),
(13090, 'ingresar', 3, 1, 'No Permitido'),
(13091, 'consultar', 3, 1, 'No Permitido'),
(13092, 'incluir', 3, 1, 'No Permitido'),
(13093, 'modificar', 3, 1, 'No Permitido'),
(13094, 'eliminar', 3, 1, 'No Permitido'),
(13095, 'generar re', 3, 1, 'No Permitido'),
(13096, 'ingresar', 3, 2, 'No Permitido'),
(13097, 'consultar', 3, 2, 'No Permitido'),
(13098, 'incluir', 3, 2, 'No Permitido'),
(13099, 'modificar', 3, 2, 'No Permitido'),
(13100, 'eliminar', 3, 2, 'No Permitido'),
(13101, 'generar re', 3, 2, 'No Permitido'),
(13102, 'ingresar', 3, 3, 'No Permitido'),
(13103, 'consultar', 3, 3, 'No Permitido'),
(13104, 'incluir', 3, 3, 'No Permitido'),
(13105, 'modificar', 3, 3, 'No Permitido'),
(13106, 'eliminar', 3, 3, 'No Permitido'),
(13107, 'generar re', 3, 3, 'No Permitido'),
(13108, 'ingresar', 3, 4, 'No Permitido'),
(13109, 'consultar', 3, 4, 'No Permitido'),
(13110, 'incluir', 3, 4, 'No Permitido'),
(13111, 'modificar', 3, 4, 'No Permitido'),
(13112, 'eliminar', 3, 4, 'No Permitido'),
(13113, 'generar re', 3, 4, 'No Permitido'),
(13114, 'ingresar', 3, 5, 'No Permitido'),
(13115, 'consultar', 3, 5, 'No Permitido'),
(13116, 'incluir', 3, 5, 'No Permitido'),
(13117, 'modificar', 3, 5, 'No Permitido'),
(13118, 'eliminar', 3, 5, 'No Permitido'),
(13119, 'generar re', 3, 5, 'No Permitido'),
(13120, 'ingresar', 3, 6, 'No Permitido'),
(13121, 'consultar', 3, 6, 'No Permitido'),
(13122, 'incluir', 3, 6, 'No Permitido'),
(13123, 'modificar', 3, 6, 'No Permitido'),
(13124, 'eliminar', 3, 6, 'No Permitido'),
(13125, 'generar re', 3, 6, 'No Permitido'),
(13126, 'ingresar', 3, 7, 'No Permitido'),
(13127, 'consultar', 3, 7, 'No Permitido'),
(13128, 'incluir', 3, 7, 'No Permitido'),
(13129, 'modificar', 3, 7, 'No Permitido'),
(13130, 'eliminar', 3, 7, 'No Permitido'),
(13131, 'generar re', 3, 7, 'No Permitido'),
(13132, 'ingresar', 3, 8, 'No Permitido'),
(13133, 'consultar', 3, 8, 'No Permitido'),
(13134, 'incluir', 3, 8, 'No Permitido'),
(13135, 'modificar', 3, 8, 'No Permitido'),
(13136, 'eliminar', 3, 8, 'No Permitido'),
(13137, 'generar re', 3, 8, 'No Permitido'),
(13138, 'ingresar', 3, 9, 'No Permitido'),
(13139, 'consultar', 3, 9, 'No Permitido'),
(13140, 'incluir', 3, 9, 'No Permitido'),
(13141, 'modificar', 3, 9, 'No Permitido'),
(13142, 'eliminar', 3, 9, 'No Permitido'),
(13143, 'generar re', 3, 9, 'No Permitido'),
(13144, 'ingresar', 3, 10, 'Permitido'),
(13145, 'consultar', 3, 10, 'Permitido'),
(13146, 'incluir', 3, 10, 'No Permitido'),
(13147, 'modificar', 3, 10, 'No Permitido'),
(13148, 'eliminar', 3, 10, 'No Permitido'),
(13149, 'generar re', 3, 10, 'No Permitido'),
(13150, 'ingresar', 3, 11, 'Permitido'),
(13151, 'consultar', 3, 11, 'Permitido'),
(13152, 'incluir', 3, 11, 'Permitido'),
(13153, 'modificar', 3, 11, 'Permitido'),
(13154, 'eliminar', 3, 11, 'Permitido'),
(13155, 'generar re', 3, 11, 'No Permitido'),
(13156, 'ingresar', 3, 12, 'Permitido'),
(13157, 'consultar', 3, 12, 'Permitido'),
(13158, 'incluir', 3, 12, 'No Permitido'),
(13159, 'modificar', 3, 12, 'No Permitido'),
(13160, 'eliminar', 3, 12, 'No Permitido'),
(13161, 'generar re', 3, 12, 'No Permitido'),
(13162, 'ingresar', 3, 13, 'Permitido'),
(13163, 'consultar', 3, 13, 'Permitido'),
(13164, 'incluir', 3, 13, 'No Permitido'),
(13165, 'modificar', 3, 13, 'No Permitido'),
(13166, 'eliminar', 3, 13, 'No Permitido'),
(13167, 'generar re', 3, 13, 'No Permitido'),
(13168, 'ingresar', 3, 14, 'No Permitido'),
(13169, 'consultar', 3, 14, 'No Permitido'),
(13170, 'incluir', 3, 14, 'No Permitido'),
(13171, 'modificar', 3, 14, 'No Permitido'),
(13172, 'eliminar', 3, 14, 'No Permitido'),
(13173, 'generar re', 3, 14, 'No Permitido'),
(13174, 'ingresar', 3, 15, 'No Permitido'),
(13175, 'consultar', 3, 15, 'No Permitido'),
(13176, 'incluir', 3, 15, 'No Permitido'),
(13177, 'modificar', 3, 15, 'No Permitido'),
(13178, 'eliminar', 3, 15, 'No Permitido'),
(13179, 'generar re', 3, 15, 'No Permitido'),
(13180, 'ingresar', 3, 16, 'No Permitido'),
(13181, 'consultar', 3, 16, 'No Permitido'),
(13182, 'incluir', 3, 16, 'No Permitido'),
(13183, 'modificar', 3, 16, 'No Permitido'),
(13184, 'eliminar', 3, 16, 'No Permitido'),
(13185, 'generar re', 3, 16, 'No Permitido'),
(13186, 'ingresar', 3, 17, 'No Permitido'),
(13187, 'consultar', 3, 17, 'No Permitido'),
(13188, 'incluir', 3, 17, 'No Permitido'),
(13189, 'modificar', 3, 17, 'No Permitido'),
(13190, 'eliminar', 3, 17, 'No Permitido'),
(13191, 'generar re', 3, 17, 'No Permitido'),
(13192, 'ingresar', 3, 18, 'No Permitido'),
(13193, 'consultar', 3, 18, 'No Permitido'),
(13194, 'incluir', 3, 18, 'No Permitido'),
(13195, 'modificar', 3, 18, 'No Permitido'),
(13196, 'eliminar', 3, 18, 'No Permitido'),
(13197, 'generar re', 3, 18, 'No Permitido'),
(13198, 'ingresar', 3, 19, 'No Permitido'),
(13199, 'consultar', 3, 19, 'No Permitido'),
(13200, 'incluir', 3, 19, 'No Permitido'),
(13201, 'modificar', 3, 19, 'No Permitido'),
(13202, 'eliminar', 3, 19, 'No Permitido'),
(13203, 'generar re', 3, 19, 'No Permitido'),
(13204, 'ingresar', 3, 20, 'No Permitido'),
(13205, 'consultar', 3, 20, 'No Permitido'),
(13206, 'incluir', 3, 20, 'No Permitido'),
(13207, 'modificar', 3, 20, 'No Permitido'),
(13208, 'eliminar', 3, 20, 'No Permitido'),
(13209, 'generar re', 3, 20, 'No Permitido'),
(13210, 'ingresar', 3, 21, 'No Permitido'),
(13211, 'consultar', 3, 21, 'No Permitido'),
(13212, 'incluir', 3, 21, 'No Permitido'),
(13213, 'modificar', 3, 21, 'No Permitido'),
(13214, 'eliminar', 3, 21, 'No Permitido'),
(13215, 'generar re', 3, 21, 'No Permitido'),
(13216, 'ingresar', 3, 22, 'Permitido'),
(13217, 'consultar', 3, 22, 'Permitido'),
(13218, 'incluir', 3, 22, 'Permitido'),
(13219, 'modificar', 3, 22, 'Permitido'),
(13220, 'eliminar', 3, 22, 'Permitido'),
(13221, 'generar re', 3, 22, 'No Permitido'),
(13222, 'ingresar', 3, 23, 'Permitido'),
(13223, 'consultar', 3, 23, 'Permitido'),
(13224, 'incluir', 3, 23, 'Permitido'),
(13225, 'modificar', 3, 23, 'Permitido'),
(13226, 'eliminar', 3, 23, 'Permitido'),
(13227, 'generar re', 3, 23, 'No Permitido'),
(13228, 'ingresar', 4, 1, 'No Permitido'),
(13229, 'consultar', 4, 1, 'No Permitido'),
(13230, 'incluir', 4, 1, 'No Permitido'),
(13231, 'modificar', 4, 1, 'No Permitido'),
(13232, 'eliminar', 4, 1, 'No Permitido'),
(13233, 'generar re', 4, 1, 'No Permitido'),
(13234, 'ingresar', 4, 2, 'No Permitido'),
(13235, 'consultar', 4, 2, 'No Permitido'),
(13236, 'incluir', 4, 2, 'No Permitido'),
(13237, 'modificar', 4, 2, 'No Permitido'),
(13238, 'eliminar', 4, 2, 'No Permitido'),
(13239, 'generar re', 4, 2, 'No Permitido'),
(13240, 'ingresar', 4, 3, 'No Permitido'),
(13241, 'consultar', 4, 3, 'No Permitido'),
(13242, 'incluir', 4, 3, 'No Permitido'),
(13243, 'modificar', 4, 3, 'No Permitido'),
(13244, 'eliminar', 4, 3, 'No Permitido'),
(13245, 'generar re', 4, 3, 'No Permitido'),
(13246, 'ingresar', 4, 4, 'No Permitido'),
(13247, 'consultar', 4, 4, 'No Permitido'),
(13248, 'incluir', 4, 4, 'No Permitido'),
(13249, 'modificar', 4, 4, 'No Permitido'),
(13250, 'eliminar', 4, 4, 'No Permitido'),
(13251, 'generar re', 4, 4, 'No Permitido'),
(13252, 'ingresar', 4, 5, 'No Permitido'),
(13253, 'consultar', 4, 5, 'No Permitido'),
(13254, 'incluir', 4, 5, 'No Permitido'),
(13255, 'modificar', 4, 5, 'No Permitido'),
(13256, 'eliminar', 4, 5, 'No Permitido'),
(13257, 'generar re', 4, 5, 'No Permitido'),
(13258, 'ingresar', 4, 6, 'No Permitido'),
(13259, 'consultar', 4, 6, 'No Permitido'),
(13260, 'incluir', 4, 6, 'No Permitido'),
(13261, 'modificar', 4, 6, 'No Permitido'),
(13262, 'eliminar', 4, 6, 'No Permitido'),
(13263, 'generar re', 4, 6, 'No Permitido'),
(13264, 'ingresar', 4, 7, 'No Permitido'),
(13265, 'consultar', 4, 7, 'No Permitido'),
(13266, 'incluir', 4, 7, 'No Permitido'),
(13267, 'modificar', 4, 7, 'No Permitido'),
(13268, 'eliminar', 4, 7, 'No Permitido'),
(13269, 'generar re', 4, 7, 'No Permitido'),
(13270, 'ingresar', 4, 8, 'No Permitido'),
(13271, 'consultar', 4, 8, 'No Permitido'),
(13272, 'incluir', 4, 8, 'No Permitido'),
(13273, 'modificar', 4, 8, 'No Permitido'),
(13274, 'eliminar', 4, 8, 'No Permitido'),
(13275, 'generar re', 4, 8, 'No Permitido'),
(13276, 'ingresar', 4, 9, 'No Permitido'),
(13277, 'consultar', 4, 9, 'No Permitido'),
(13278, 'incluir', 4, 9, 'No Permitido'),
(13279, 'modificar', 4, 9, 'No Permitido'),
(13280, 'eliminar', 4, 9, 'No Permitido'),
(13281, 'generar re', 4, 9, 'No Permitido'),
(13282, 'ingresar', 4, 10, 'No Permitido'),
(13283, 'consultar', 4, 10, 'No Permitido'),
(13284, 'incluir', 4, 10, 'No Permitido'),
(13285, 'modificar', 4, 10, 'No Permitido'),
(13286, 'eliminar', 4, 10, 'No Permitido'),
(13287, 'generar re', 4, 10, 'No Permitido'),
(13288, 'ingresar', 4, 11, 'No Permitido'),
(13289, 'consultar', 4, 11, 'No Permitido'),
(13290, 'incluir', 4, 11, 'No Permitido'),
(13291, 'modificar', 4, 11, 'No Permitido'),
(13292, 'eliminar', 4, 11, 'No Permitido'),
(13293, 'generar re', 4, 11, 'No Permitido'),
(13294, 'ingresar', 4, 12, 'No Permitido'),
(13295, 'consultar', 4, 12, 'No Permitido'),
(13296, 'incluir', 4, 12, 'No Permitido'),
(13297, 'modificar', 4, 12, 'No Permitido'),
(13298, 'eliminar', 4, 12, 'No Permitido'),
(13299, 'generar re', 4, 12, 'No Permitido'),
(13300, 'ingresar', 4, 13, 'No Permitido'),
(13301, 'consultar', 4, 13, 'No Permitido'),
(13302, 'incluir', 4, 13, 'No Permitido'),
(13303, 'modificar', 4, 13, 'No Permitido'),
(13304, 'eliminar', 4, 13, 'No Permitido'),
(13305, 'generar re', 4, 13, 'No Permitido'),
(13306, 'ingresar', 4, 14, 'No Permitido'),
(13307, 'consultar', 4, 14, 'No Permitido'),
(13308, 'incluir', 4, 14, 'No Permitido'),
(13309, 'modificar', 4, 14, 'No Permitido'),
(13310, 'eliminar', 4, 14, 'No Permitido'),
(13311, 'generar re', 4, 14, 'No Permitido'),
(13312, 'ingresar', 4, 15, 'No Permitido'),
(13313, 'consultar', 4, 15, 'No Permitido'),
(13314, 'incluir', 4, 15, 'No Permitido'),
(13315, 'modificar', 4, 15, 'No Permitido'),
(13316, 'eliminar', 4, 15, 'No Permitido'),
(13317, 'generar re', 4, 15, 'No Permitido'),
(13318, 'ingresar', 4, 16, 'No Permitido'),
(13319, 'consultar', 4, 16, 'No Permitido'),
(13320, 'incluir', 4, 16, 'No Permitido'),
(13321, 'modificar', 4, 16, 'No Permitido'),
(13322, 'eliminar', 4, 16, 'No Permitido'),
(13323, 'generar re', 4, 16, 'No Permitido'),
(13324, 'ingresar', 4, 17, 'No Permitido'),
(13325, 'consultar', 4, 17, 'No Permitido'),
(13326, 'incluir', 4, 17, 'No Permitido'),
(13327, 'modificar', 4, 17, 'No Permitido'),
(13328, 'eliminar', 4, 17, 'No Permitido'),
(13329, 'generar re', 4, 17, 'No Permitido'),
(13330, 'ingresar', 4, 18, 'No Permitido'),
(13331, 'consultar', 4, 18, 'No Permitido'),
(13332, 'incluir', 4, 18, 'No Permitido'),
(13333, 'modificar', 4, 18, 'No Permitido'),
(13334, 'eliminar', 4, 18, 'No Permitido'),
(13335, 'generar re', 4, 18, 'No Permitido'),
(13336, 'ingresar', 4, 19, 'No Permitido'),
(13337, 'consultar', 4, 19, 'No Permitido'),
(13338, 'incluir', 4, 19, 'No Permitido'),
(13339, 'modificar', 4, 19, 'No Permitido'),
(13340, 'eliminar', 4, 19, 'No Permitido'),
(13341, 'generar re', 4, 19, 'No Permitido'),
(13342, 'ingresar', 4, 20, 'No Permitido'),
(13343, 'consultar', 4, 20, 'No Permitido'),
(13344, 'incluir', 4, 20, 'No Permitido'),
(13345, 'modificar', 4, 20, 'No Permitido'),
(13346, 'eliminar', 4, 20, 'No Permitido'),
(13347, 'generar re', 4, 20, 'No Permitido'),
(13348, 'ingresar', 4, 21, 'Permitido'),
(13349, 'consultar', 4, 21, 'Permitido'),
(13350, 'incluir', 4, 21, 'Permitido'),
(13351, 'modificar', 4, 21, 'Permitido'),
(13352, 'eliminar', 4, 21, 'Permitido'),
(13353, 'generar re', 4, 21, 'No Permitido'),
(13354, 'ingresar', 4, 22, 'Permitido'),
(13355, 'consultar', 4, 22, 'Permitido'),
(13356, 'incluir', 4, 22, 'Permitido'),
(13357, 'modificar', 4, 22, 'Permitido'),
(13358, 'eliminar', 4, 22, 'Permitido'),
(13359, 'generar re', 4, 22, 'No Permitido'),
(13360, 'ingresar', 4, 23, 'Permitido'),
(13361, 'consultar', 4, 23, 'Permitido'),
(13362, 'incluir', 4, 23, 'Permitido'),
(13363, 'modificar', 4, 23, 'Permitido'),
(13364, 'eliminar', 4, 23, 'Permitido'),
(13365, 'generar re', 4, 23, 'No Permitido'),
(13366, 'ingresar', 7, 1, 'No Permitido'),
(13367, 'consultar', 7, 1, 'No Permitido'),
(13368, 'incluir', 7, 1, 'No Permitido'),
(13369, 'modificar', 7, 1, 'No Permitido'),
(13370, 'eliminar', 7, 1, 'No Permitido'),
(13371, 'generar re', 7, 1, 'No Permitido'),
(13372, 'ingresar', 7, 2, 'No Permitido'),
(13373, 'consultar', 7, 2, 'No Permitido'),
(13374, 'incluir', 7, 2, 'No Permitido'),
(13375, 'modificar', 7, 2, 'No Permitido'),
(13376, 'eliminar', 7, 2, 'No Permitido'),
(13377, 'generar re', 7, 2, 'No Permitido'),
(13378, 'ingresar', 7, 3, 'No Permitido'),
(13379, 'consultar', 7, 3, 'No Permitido'),
(13380, 'incluir', 7, 3, 'No Permitido'),
(13381, 'modificar', 7, 3, 'No Permitido'),
(13382, 'eliminar', 7, 3, 'No Permitido'),
(13383, 'generar re', 7, 3, 'No Permitido'),
(13384, 'ingresar', 7, 4, 'No Permitido'),
(13385, 'consultar', 7, 4, 'No Permitido'),
(13386, 'incluir', 7, 4, 'No Permitido'),
(13387, 'modificar', 7, 4, 'No Permitido'),
(13388, 'eliminar', 7, 4, 'No Permitido'),
(13389, 'generar re', 7, 4, 'No Permitido'),
(13390, 'ingresar', 7, 5, 'No Permitido'),
(13391, 'consultar', 7, 5, 'No Permitido'),
(13392, 'incluir', 7, 5, 'No Permitido'),
(13393, 'modificar', 7, 5, 'No Permitido'),
(13394, 'eliminar', 7, 5, 'No Permitido'),
(13395, 'generar re', 7, 5, 'No Permitido'),
(13396, 'ingresar', 7, 6, 'No Permitido'),
(13397, 'consultar', 7, 6, 'No Permitido'),
(13398, 'incluir', 7, 6, 'No Permitido'),
(13399, 'modificar', 7, 6, 'No Permitido'),
(13400, 'eliminar', 7, 6, 'No Permitido'),
(13401, 'generar re', 7, 6, 'No Permitido'),
(13402, 'ingresar', 7, 7, 'No Permitido'),
(13403, 'consultar', 7, 7, 'No Permitido'),
(13404, 'incluir', 7, 7, 'No Permitido'),
(13405, 'modificar', 7, 7, 'No Permitido'),
(13406, 'eliminar', 7, 7, 'No Permitido'),
(13407, 'generar re', 7, 7, 'No Permitido'),
(13408, 'ingresar', 7, 8, 'No Permitido'),
(13409, 'consultar', 7, 8, 'No Permitido'),
(13410, 'incluir', 7, 8, 'No Permitido'),
(13411, 'modificar', 7, 8, 'No Permitido'),
(13412, 'eliminar', 7, 8, 'No Permitido'),
(13413, 'generar re', 7, 8, 'No Permitido'),
(13414, 'ingresar', 7, 9, 'No Permitido'),
(13415, 'consultar', 7, 9, 'No Permitido'),
(13416, 'incluir', 7, 9, 'No Permitido'),
(13417, 'modificar', 7, 9, 'No Permitido'),
(13418, 'eliminar', 7, 9, 'No Permitido'),
(13419, 'generar re', 7, 9, 'No Permitido'),
(13420, 'ingresar', 7, 10, 'No Permitido'),
(13421, 'consultar', 7, 10, 'No Permitido'),
(13422, 'incluir', 7, 10, 'No Permitido'),
(13423, 'modificar', 7, 10, 'No Permitido'),
(13424, 'eliminar', 7, 10, 'No Permitido'),
(13425, 'generar re', 7, 10, 'No Permitido'),
(13426, 'ingresar', 7, 11, 'No Permitido'),
(13427, 'consultar', 7, 11, 'No Permitido'),
(13428, 'incluir', 7, 11, 'No Permitido'),
(13429, 'modificar', 7, 11, 'No Permitido'),
(13430, 'eliminar', 7, 11, 'No Permitido'),
(13431, 'generar re', 7, 11, 'No Permitido'),
(13432, 'ingresar', 7, 12, 'No Permitido'),
(13433, 'consultar', 7, 12, 'No Permitido'),
(13434, 'incluir', 7, 12, 'No Permitido'),
(13435, 'modificar', 7, 12, 'No Permitido'),
(13436, 'eliminar', 7, 12, 'No Permitido'),
(13437, 'generar re', 7, 12, 'No Permitido'),
(13438, 'ingresar', 7, 13, 'No Permitido'),
(13439, 'consultar', 7, 13, 'No Permitido'),
(13440, 'incluir', 7, 13, 'No Permitido'),
(13441, 'modificar', 7, 13, 'No Permitido'),
(13442, 'eliminar', 7, 13, 'No Permitido'),
(13443, 'generar re', 7, 13, 'No Permitido'),
(13444, 'ingresar', 7, 14, 'No Permitido'),
(13445, 'consultar', 7, 14, 'No Permitido'),
(13446, 'incluir', 7, 14, 'No Permitido'),
(13447, 'modificar', 7, 14, 'No Permitido'),
(13448, 'eliminar', 7, 14, 'No Permitido'),
(13449, 'generar re', 7, 14, 'No Permitido'),
(13450, 'ingresar', 7, 15, 'No Permitido'),
(13451, 'consultar', 7, 15, 'No Permitido'),
(13452, 'incluir', 7, 15, 'No Permitido'),
(13453, 'modificar', 7, 15, 'No Permitido'),
(13454, 'eliminar', 7, 15, 'No Permitido'),
(13455, 'generar re', 7, 15, 'No Permitido'),
(13456, 'ingresar', 7, 16, 'No Permitido'),
(13457, 'consultar', 7, 16, 'No Permitido'),
(13458, 'incluir', 7, 16, 'No Permitido'),
(13459, 'modificar', 7, 16, 'No Permitido'),
(13460, 'eliminar', 7, 16, 'No Permitido'),
(13461, 'generar re', 7, 16, 'No Permitido'),
(13462, 'ingresar', 7, 17, 'No Permitido'),
(13463, 'consultar', 7, 17, 'No Permitido'),
(13464, 'incluir', 7, 17, 'No Permitido'),
(13465, 'modificar', 7, 17, 'No Permitido'),
(13466, 'eliminar', 7, 17, 'No Permitido'),
(13467, 'generar re', 7, 17, 'No Permitido'),
(13468, 'ingresar', 7, 18, 'No Permitido'),
(13469, 'consultar', 7, 18, 'No Permitido'),
(13470, 'incluir', 7, 18, 'No Permitido'),
(13471, 'modificar', 7, 18, 'No Permitido'),
(13472, 'eliminar', 7, 18, 'No Permitido'),
(13473, 'generar re', 7, 18, 'No Permitido'),
(13474, 'ingresar', 7, 19, 'No Permitido'),
(13475, 'consultar', 7, 19, 'No Permitido'),
(13476, 'incluir', 7, 19, 'No Permitido'),
(13477, 'modificar', 7, 19, 'No Permitido'),
(13478, 'eliminar', 7, 19, 'No Permitido'),
(13479, 'generar re', 7, 19, 'No Permitido'),
(13480, 'ingresar', 7, 20, 'No Permitido'),
(13481, 'consultar', 7, 20, 'No Permitido'),
(13482, 'incluir', 7, 20, 'No Permitido'),
(13483, 'modificar', 7, 20, 'No Permitido'),
(13484, 'eliminar', 7, 20, 'No Permitido'),
(13485, 'generar re', 7, 20, 'No Permitido'),
(13486, 'ingresar', 7, 21, 'Permitido'),
(13487, 'consultar', 7, 21, 'Permitido'),
(13488, 'incluir', 7, 21, 'Permitido'),
(13489, 'modificar', 7, 21, 'Permitido'),
(13490, 'eliminar', 7, 21, 'Permitido'),
(13491, 'generar re', 7, 21, 'No Permitido'),
(13492, 'ingresar', 7, 22, 'Permitido'),
(13493, 'consultar', 7, 22, 'Permitido'),
(13494, 'incluir', 7, 22, 'Permitido'),
(13495, 'modificar', 7, 22, 'Permitido'),
(13496, 'eliminar', 7, 22, 'Permitido'),
(13497, 'generar re', 7, 22, 'No Permitido'),
(13498, 'ingresar', 7, 23, 'Permitido'),
(13499, 'consultar', 7, 23, 'Permitido'),
(13500, 'incluir', 7, 23, 'Permitido'),
(13501, 'modificar', 7, 23, 'Permitido'),
(13502, 'eliminar', 7, 23, 'Permitido'),
(13503, 'generar re', 7, 23, 'No Permitido');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_recuperar`
--

CREATE TABLE `tbl_recuperar` (
  `id_recuperar` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `expiracion` datetime NOT NULL,
  `utilizado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_rol`
--

CREATE TABLE `tbl_rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_rol`
--

INSERT INTO `tbl_rol` (`id_rol`, `nombre_rol`) VALUES
(1, 'Administrador'),
(2, 'Almacenista'),
(3, 'Cliente'),
(4, 'Desarrollador'),
(6, 'SuperUsuario'),
(7, 'Vendedor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_usuarios`
--

CREATE TABLE `tbl_usuarios` (
  `id_usuario` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `cedula` varchar(10) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `correo` varchar(2048) DEFAULT NULL,
  `nombres` varchar(2048) DEFAULT NULL,
  `apellidos` varchar(2048) DEFAULT NULL,
  `telefono` varchar(2048) DEFAULT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `estatus` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado',
  `foto_perfil` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_usuarios`
--

INSERT INTO `tbl_usuarios` (`id_usuario`, `username`, `password`, `cedula`, `id_rol`, `correo`, `nombres`, `apellidos`, `telefono`, `intentos_fallidos`, `estatus`, `foto_perfil`) VALUES
(3, 'Diego', '$2y$10$KXRg/AUD.9Y7KubEvzy71e5dDR1GvGNy23XegAYwLjYWOBdcxzqx2', '30123123', 1, 'ejemplo@gmail.com', 'Diego', 'Compa', '0414-575-3363', 1, 'habilitado', 'avatar_691a939b55ec7_Foto_de_Asesor_De_Ventas_De_Thunder_Net.jpg'),
(4, 'Simon', '$2y$10$bJfY45blf5qV66WzNf5.OOTPFjgCEePpBz07GQUc3B0qlKMNzJd8W', '29123123', 7, 'ejemplo@gmail.com', 'Simon Freitez', 'Cliente', '0414-000-0000', 0, 'habilitado', NULL),
(5, 'edit', '$2y$10$w7nQw5p6Qw6nQw5p6Qw6nOQw5p6Qw6nQw5p6Qw6nQw5p6Qw6nQw6n', 'V-101', 3, 'e@test.com', 'Eva', 'Perez', '0412', 0, 'inhabilitado', NULL),
(9, 'CasaLai', '$2y$10$KXRg/AUD.9Y7KubEvzy71e5dDR1GvGNy23XegAYwLjYWOBdcxzqx2', '30456789', 6, 'diego0510lopez@gmail.com', 'Casa', 'Lai', '0414-575-3363', 0, 'habilitado', NULL),
(10, 'Gmujica', '$2y$10$iZNeKonr6qr.P109rwgEFOCc7Y.0E47sD/88YfB.Jyx6niGpf4CQi', '29958676', 2, 'fhhggjjkkkj@gmail.com', 'Gabriel', 'Mujica', '0424-678-8765', 0, 'habilitado', NULL),
(11, 'edithu', '$2y$10$YfEtJDHi9CNZR1Xpx7J9Ze8CMx3g99o1dJ3h.RRZPXqlJjxWbT5Fi', '10844463', 3, 'urdavedith.pnfi@gmail.com', 'Edith', 'Urdaneta', '0416-747-4336', 0, 'inhabilitado', NULL),
(16, 'Darckort', '$2y$10$1xavkBCftrr0QLclZTk77eduhFhvGa3uWiuCva2qHKMQ/otwoGYaa', '28406324', 6, 'darckortgame@gmail.com', 'Braynt de Jesus', 'Medina Bricno', '0426-150-4714', 0, 'habilitado', 'avatar_68edb74fbaaa4_file_000000005b786246b5a28d3be60c28d6.png'),
(17, 'Juanlai', '$2y$10$NAPB.g70SJM0juLf9jTha.LbRejgTZFWD87GfYgATpp2k./KfciK2', '25.874.676', 4, 'juanlai@gmail.com', 'Juan', 'Lai', '0412-125-6985', 0, 'habilitado', NULL),
(24, 'Susan', '$2y$10$L7kgXy339cVPwrHwr0UPweXkN5VK.LMZ9WqCMR3Nnrw0rAQc4yrHe', '12.313.313', 3, 'diego0510lopez@gmail.com', 'Susan', 'Lopez', '0414-575-3363', 0, 'habilitado', NULL),
(25, 'DarckAlm', '$2y$10$dKPVEabWzH.7L9GAKBp6N.HDpRVNB25/sz0Z5VmGBkZFM0aj2rlfK', '10.101.010', 2, 'diego0510lo23@gmail.com', 'Bray', 'Med', '0414-575-3363', 0, 'habilitado', NULL),
(28, 'Clarividente', '$2y$10$oOTHfSQGG4GzPIkN7BIt0.z6EnUvvGwDB.5WKOyd/oBojqkM7PYNS', '30.335.418', 1, 'diego0lopez@gmail.com', 'Diego', 'Lopez', '0414-575-3363', 0, 'habilitado', NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_ips_bloqueadas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_ips_bloqueadas` (
`direccion_ip` varchar(45)
,`username` varchar(50)
,`motivo_bloqueo` varchar(200)
,`fecha_bloqueo` datetime
,`fecha_desbloqueo` datetime
,`nivel_riesgo` enum('bajo','medio','alto','critico')
,`minutos_bloqueado` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_ips_bloqueadas`
--
DROP TABLE IF EXISTS `v_ips_bloqueadas`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `v_ips_bloqueadas`  AS SELECT `si`.`direccion_ip` AS `direccion_ip`, `si`.`username` AS `username`, `si`.`motivo_bloqueo` AS `motivo_bloqueo`, `si`.`fecha_bloqueo` AS `fecha_bloqueo`, `si`.`fecha_desbloqueo` AS `fecha_desbloqueo`, `si`.`nivel_riesgo` AS `nivel_riesgo`, timestampdiff(MINUTE,`si`.`fecha_bloqueo`,current_timestamp()) AS `minutos_bloqueado` FROM `seguridad_ip` AS `si` WHERE `si`.`esta_bloqueado` = 1 AND (`si`.`fecha_desbloqueo` is null OR `si`.`fecha_desbloqueo` > current_timestamp()) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `seguridad_ip`
--
ALTER TABLE `seguridad_ip`
  ADD PRIMARY KEY (`id_seguridad_ip`),
  ADD UNIQUE KEY `unique_ip_username` (`direccion_ip`,`username`),
  ADD KEY `idx_direccion_ip` (`direccion_ip`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_esta_bloqueado` (`esta_bloqueado`),
  ADD KEY `idx_fecha_bloqueo` (`fecha_bloqueo`),
  ADD KEY `idx_nivel_riesgo` (`nivel_riesgo`);

--
-- Indices de la tabla `tbl_bitacora`
--
ALTER TABLE `tbl_bitacora`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `id_modulo` (`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_modulos`
--
ALTER TABLE `tbl_modulos`
  ADD PRIMARY KEY (`id_modulo`);

--
-- Indices de la tabla `tbl_notificaciones`
--
ALTER TABLE `tbl_notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_rol` (`id_rol`,`id_modulo`),
  ADD KEY `id_modulo` (`id_modulo`);

--
-- Indices de la tabla `tbl_recuperar`
--
ALTER TABLE `tbl_recuperar`
  ADD PRIMARY KEY (`id_recuperar`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_rol`
--
ALTER TABLE `tbl_rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `seguridad_ip`
--
ALTER TABLE `seguridad_ip`
  MODIFY `id_seguridad_ip` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tbl_bitacora`
--
ALTER TABLE `tbl_bitacora`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2825;

--
-- AUTO_INCREMENT de la tabla `tbl_modulos`
--
ALTER TABLE `tbl_modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `tbl_notificaciones`
--
ALTER TABLE `tbl_notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT de la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13642;

--
-- AUTO_INCREMENT de la tabla `tbl_recuperar`
--
ALTER TABLE `tbl_recuperar`
  MODIFY `id_recuperar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_rol`
--
ALTER TABLE `tbl_rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tbl_bitacora`
--
ALTER TABLE `tbl_bitacora`
  ADD CONSTRAINT `tbl_bitacora_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_notificaciones`
--
ALTER TABLE `tbl_notificaciones`
  ADD CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  ADD CONSTRAINT `tbl_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `tbl_rol` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_permisos_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `tbl_modulos` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_recuperar`
--
ALTER TABLE `tbl_recuperar`
  ADD CONSTRAINT `tbl_recuperar_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `tbl_rol` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

ALTER TABLE `seguridad_ip` ADD INDEX `idx_seguridad_direccion_ip` (`direccion_ip`);
ALTER TABLE `seguridad_ip` ADD INDEX `idx_seguridad_username` (`username`);
ALTER TABLE `seguridad_ip` ADD INDEX `idx_seguridad_estatus_bloqueo` (`esta_bloqueado`, `fecha_desbloqueo`);

ALTER TABLE `tbl_bitacora` ADD INDEX `idx_bitacora_id_usuario` (`id_usuario`);
ALTER TABLE `tbl_bitacora` ADD INDEX `idx_bitacora_modulo_accion` (`nombre_modulo`, `accion`);
ALTER TABLE `tbl_bitacora` ADD INDEX `idx_bitacora_fecha` (`fecha_hora`);

-- ---------------------------------------------------------------------
-- 1. PROCEDIMIENTO PARA REGISTRAR ROL
-- ---------------------------------------------------------------------

DELIMITER $$

CREATE PROCEDURE sp_registrar_rol(
    IN p_nombre_rol VARCHAR(15),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nuevo_id INT;

    -- Manejador de excepciones unificado para garantizar la atomicidad total
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo registrar el rol y sus permisos de forma atómica.';
    END;

    -- Nivel de aislamiento estricto
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- 1. Insertar el nuevo rol físico
    INSERT INTO `tbl_rol` (`nombre_rol`) 
    VALUES (p_nombre_rol);

    -- Captura inmediata del ID autogenerado
    SET v_nuevo_id = LAST_INSERT_ID();

    -- 2. Inserción masiva de permisos (Reemplaza los bucles foreach de PHP)
    -- Cruza dinámicamente cada id_modulo con las 6 acciones deseadas
    INSERT INTO `tbl_permisos` (`accion`, `id_rol`, `id_modulo`, `estatus`)
    SELECT a.accion, v_nuevo_id, m.id_modulo, 'No Permitido'
    FROM `tbl_modulos` m
    CROSS JOIN (
        SELECT 'Ingresar' AS accion UNION ALL
        SELECT 'Incluir'   UNION ALL
        SELECT 'Consultar' UNION ALL
        SELECT 'Modificar' UNION ALL
        SELECT 'Eliminar'  UNION ALL
        SELECT 'Reportar'
    ) a;

    -- 3. Inserción obligatoria en Bitácora (Permanece intacta)
    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Roles', 
        'INCLUIR', 
        JSON_OBJECT('id_rol', v_nuevo_id, 'nombre_rol', p_nombre_rol), 
        NULL, 
        p_id_usuario_auditor, 
        'media', 
        CONCAT('Se incluyó un nuevo rol en el sistema: ', p_nombre_rol)
    );

    COMMIT;
END $$

DELIMITER ;

-- =========================================================================
-- 2. PROCEDIMIENTO: CONSULTAR ROL
-- =========================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_consultar_rol $$

CREATE PROCEDURE sp_consultar_rol()
BEGIN
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT `id_rol`, `nombre_rol` 
    FROM `tbl_rol` 
    ORDER BY `id_rol`
    FOR UPDATE;

    COMMIT;
END $$

DELIMITER ;

-- -----------------------------------------------------------------------------
-- PROCEDIMIENTO: OBTENER CUENTA POR ID
-- -----------------------------------------------------------------------------

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_obtener_rol_por_id $$

CREATE PROCEDURE sp_obtener_rol_por_id(
    IN p_id_rol INT
)
BEGIN
    -- Manejador de fallas generales con mensaje personalizado
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo obtener la información del rol.';
    END;

    SELECT * FROM `tbl_rol` 
    WHERE `id_rol` = p_id_rol
    LIMIT 1
    FOR UPDATE;
END $$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 3. PROCEDIMIENTO PARA MODIFICAR ROL
-- ---------------------------------------------------------------------

DELIMITER $$

CREATE PROCEDURE sp_modificar_rol(
    IN p_id_rol INT,
    IN p_nuevo_nombre VARCHAR(15),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_viejo_nombre VARCHAR(15);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo modificar el rol de forma atómica.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- CONSULTA CON BLOQUEO EXCLUSIVO (FOR UPDATE)
    -- Rescatamos el estado antiguo protegiendo la fila de modificaciones concurrentes
    SELECT `nombre_rol` INTO v_viejo_nombre 
    FROM `tbl_rol` 
    WHERE `id_rol` = p_id_rol 
    FOR UPDATE;

    -- Operación DML de actualización
    UPDATE `tbl_rol` 
    SET `nombre_rol` = p_nuevo_nombre 
    WHERE `id_rol` = p_id_rol;

    -- Inserción en Bitácora guardando estados Viejo y Nuevo en JSON
    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Roles', 
        'MODIFICAR', 
        JSON_OBJECT('id_rol', p_id_rol, 'nombre_rol', p_nuevo_nombre), 
        JSON_OBJECT('id_rol', p_id_rol, 'nombre_rol', v_viejo_nombre), 
        p_id_usuario_auditor, 
        'media', 
        CONCAT('Se modificó el rol ID ', p_id_rol, ' de "', v_viejo_nombre, '" a "', p_nuevo_nombre, '".')
    );

    COMMIT;
END $$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 4. PROCEDIMIENTO PARA ELIMINAR ROL (CON CONTROL DE RESTRICCIÓN)
-- ---------------------------------------------------------------------

DELIMITER $$

CREATE PROCEDURE sp_eliminar_rol(
    IN p_id_rol INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    -- 1. TODAS las declaraciones de variables al inicio estricto
    DECLARE v_nombre_rol_eliminado VARCHAR(15);
    DECLARE v_cantidad_permisos INT;
    DECLARE v_cantidad_usuarios INT;

    -- 2. Declaraciones de manejadores de errores (Handlers)
    -- MANEJADOR ESPECÍFICO PARA EL ESCENARIO #1 (Error 1451: Llave foránea restrictiva)
    DECLARE EXIT HANDLER FOR 1451
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación denegada: No se puede eliminar el rol porque tiene usuarios activos asignados.';
    END;

    -- Manejador general para cualquier otro tipo de error
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo procesar la eliminación del rol.';
    END;

    -- 3. Inicio de la lógica operativa y transaccional
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo exclusivo del rol antes de destruirlo
    SELECT `nombre_rol` INTO v_nombre_rol_eliminado 
    FROM `tbl_rol` 
    WHERE `id_rol` = p_id_rol 
    FOR UPDATE;

    -- Contamos las dependencias en cascada antes de que desaparezcan
    SELECT COUNT(*) INTO v_cantidad_permisos 
    FROM `tbl_permisos` 
    WHERE `id_rol` = p_id_rol;

    -- Contamos cuántos usuarios tienen este rol asignado
    SELECT COUNT(*) INTO v_cantidad_usuarios 
    FROM `tbl_usuarios` 
    WHERE `id_rol` = p_id_rol;

    -- Si hay usuarios asignados, disparamos una excepción manual
    IF v_cantidad_usuarios > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error: No se puede eliminar el rol porque tiene usuarios activos asignados.';
    END IF;

    -- Operación DML de eliminación física (Dispara cascada en tbl_permisos)
    DELETE FROM `tbl_rol` 
    WHERE `id_rol` = p_id_rol;

    -- Registro atómico en bitácora con prioridad ALTA
    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Roles', 
        'ELIMINAR', 
        NULL, 
        JSON_OBJECT('id_rol', p_id_rol, 'nombre_rol', v_nombre_rol_eliminado, 'permisos_eliminados_en_cascada', v_cantidad_permisos), 
        p_id_usuario_auditor, 
        'alta', 
        CONCAT('Se eliminó el rol "', v_nombre_rol_eliminado, '" (ID: ', p_id_rol, ').')
    );

    COMMIT;
END $$

DELIMITER ;

-- =========================================================================
-- 1. PROCEDIMIENTO: INCLUIR USUARIO
-- =========================================================================

DELIMITER $$

CREATE PROCEDURE `sp_incluir_usuario`(
    IN p_username VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_cedula VARCHAR(10),
    IN p_id_rol INT,
    IN p_correo VARCHAR(2048),
    IN p_nombres VARCHAR(2048),
    IN p_apellidos VARCHAR(2048),
    IN p_telefono VARCHAR(2048),
    IN p_usuario_auditor INT
)
BEGIN
    DECLARE v_nuevo_id INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo registrar el usuario.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Validación concurrente: Asegura que el rol no sea eliminado mientras registramos
    SELECT `id_rol` FROM `tbl_rol` WHERE `id_rol` = p_id_rol LOCK IN SHARE MODE;

    INSERT INTO `tbl_usuarios` 
    (`username`, `password`, `cedula`, `id_rol`, `correo`, `nombres`, `apellidos`, `telefono`, `intentos_fallidos`, `estatus`, `foto_perfil`)
    VALUES 
    (p_username, p_password, p_cedula, p_id_rol, p_correo, p_nombres, p_apellidos, p_telefono, 0, 'habilitado', NULL);

    SET v_nuevo_id = LAST_INSERT_ID();

    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Usuario', 
        'INCLUIR', 
        JSON_OBJECT('id_usuario', v_nuevo_id, 'username', p_username, 'cedula', p_cedula, 'id_rol', p_id_rol, 'correo', p_correo, 'estatus', 'habilitado'), 
        NULL, 
        p_usuario_auditor,
        'media', 
        CONCAT('Se incluyó un nuevo usuario en el sistema: ', p_username, ' (C.I: ', p_cedula, ')')
    );

    COMMIT;
END $$

DELIMITER ;

-- =========================================================================
-- 2. PROCEDIMIENTO: CONSULTAR USUARIO
-- =========================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_consultar_usuario $$

CREATE PROCEDURE sp_consultar_usuario(
    IN p_estatus VARCHAR(50)
)
BEGIN
    -- Manejador general de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo realizar la consulta de los usuarios.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Consulta con INNER JOIN y filtro dinámico integrado
    SELECT 
        u.`id_usuario`, 
        u.`username`, 
        u.`cedula`, 
        u.`id_rol`, 
        r.`nombre_rol`,
        u.`correo`, 
        u.`nombres`, 
        u.`apellidos`, 
        u.`telefono`, 
        u.`estatus` 
    FROM `tbl_usuarios` AS u
    INNER JOIN `tbl_rol` AS r ON u.`id_rol` = r.`id_rol`
    WHERE (p_estatus = 'todos' OR u.`estatus` = p_estatus)
    ORDER BY u.`id_usuario` DESC
    FOR UPDATE;

    COMMIT;
END $$

DELIMITER ;

-- -----------------------------------------------------------------------------
-- PROCEDIMIENTO: OBTENER CUENTA POR ID
-- -----------------------------------------------------------------------------

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_obtener_usuario_por_id $$

CREATE PROCEDURE sp_obtener_usuario_por_id(
    IN p_id_usuario INT
)
BEGIN
    -- Manejador de fallas generales con mensaje personalizado
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo obtener la información del usuario.';
    END;

    SELECT
        u.`id_usuario`,
        u.`username`,
        u.`cedula`,
        u.`id_rol`,
        r.`nombre_rol`,
        u.`correo`,
        u.`nombres`,
        u.`apellidos`,
        u.`telefono`,
        u.`estatus`,
        u.`foto_perfil`
    FROM `tbl_usuarios` AS u
    INNER JOIN `tbl_rol` AS r ON u.`id_rol` = r.`id_rol`
    WHERE u.`id_usuario` = p_id_usuario
    LIMIT 1
    FOR UPDATE;
END $$

DELIMITER ;

-- =========================================================================
-- 3. PROCEDIMIENTO: MODIFICAR USUARIO
-- =========================================================================

DELIMITER $$

CREATE PROCEDURE `sp_modificar_usuario`(
    IN p_id_usuario INT,
    IN p_username VARCHAR(255),
    IN p_cedula VARCHAR(10),
    IN p_id_rol INT,
    IN p_correo VARCHAR(2048),
    IN p_nombres VARCHAR(2048),
    IN p_apellidos VARCHAR(2048),
    IN p_telefono VARCHAR(2048),
    IN p_id_usuario_auditor INT
)
BEGIN
    -- Variables para almacenar el respaldo histórico
    DECLARE v_username VARCHAR(255);
    DECLARE v_cedula VARCHAR(10);
    DECLARE v_id_rol INT;
    DECLARE v_correo VARCHAR(2048);
    DECLARE v_nombres VARCHAR(2048);
    DECLARE v_apellidos VARCHAR(2048);
    DECLARE v_telefono VARCHAR(2048);

    -- Manejador de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo modificar el usuario.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Validar que el nuevo rol exista antes de proceder
    SELECT `id_rol` FROM `tbl_rol` WHERE `id_rol` = p_id_rol LOCK IN SHARE MODE;

    -- Bloqueo pesimista y captura del estado anterior completo
    SELECT `username`, `cedula`, `id_rol`, `correo`, `nombres`, `apellidos`, `telefono` 
    INTO v_username, v_cedula, v_id_rol, v_correo, v_nombres, v_apellidos, v_telefono
    FROM `tbl_usuarios`
    WHERE `id_usuario` = p_id_usuario
    LIMIT 1 FOR UPDATE;

    -- Actualización física (Excluyendo explícitamente el campo de contraseña)
    UPDATE `tbl_usuarios` 
    SET `username`  = p_username, 
        `cedula`    = p_cedula, 
        `id_rol`    = p_id_rol, 
        `correo`    = p_correo, 
        `nombres`   = p_nombres, 
        `apellidos` = p_apellidos, 
        `telefono`  = p_telefono 
    WHERE `id_usuario` = p_id_usuario;

    -- Volcado a bitácora mapeando los estados en JSON
    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(),
        'Usuario',
        'MODIFICAR',
        JSON_OBJECT(
            'id_usuario', p_id_usuario, 
            'username', p_username, 
            'cedula', p_cedula, 
            'id_rol', p_id_rol, 
            'correo', p_correo, 
            'nombres', p_nombres,
            'apellidos', p_apellidos,
            'telefono', p_telefono
        ),
        JSON_OBJECT(
            'id_usuario', p_id_usuario, 
            'username', v_username, 
            'cedula', v_cedula, 
            'id_rol', v_id_rol, 
            'correo', v_correo,
            'nombres', v_nombres,
            'apellidos', v_apellidos,
            'telefono', v_telefono
        ),
        p_id_usuario_auditor, 
        'media',
        CONCAT('Se modificaron los datos de identidad y contacto del usuario con ID: ', p_id_usuario, '.')
    );

    COMMIT;
END $$

DELIMITER ;


-- =========================================================================
-- 4. PROCEDIMIENTO: CAMBIAR ESTATUS (Habilitar/Inhabilitar)
-- =========================================================================

DELIMITER $$

CREATE PROCEDURE `sp_cambiar_estatus_usuario`(
    IN p_id_usuario INT,
    IN p_nuevo_estatus ENUM('habilitado','inhabilitado'),
    IN p_usuario_auditor INT
)
BEGIN
    DECLARE v_username VARCHAR(255);
    DECLARE v_estatus ENUM('habilitado','inhabilitado');

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo cambiar el estatus del usuario.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT `username`, `estatus` INTO v_username, v_estatus
    FROM `tbl_usuarios`
    WHERE `id_usuario` = p_id_usuario
    LIMIT 1 FOR UPDATE;

    UPDATE `tbl_usuarios`
    SET `estatus` = p_nuevo_estatus
    WHERE `id_usuario` = p_id_usuario;

    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Usuario', 
        'MODIFICAR', 
        JSON_OBJECT('id_usuario', p_id_usuario, 'estatus', p_nuevo_estatus), 
        JSON_OBJECT('id_usuario', p_id_usuario, 'estatus', v_estatus), 
        p_usuario_auditor, 
        'media',
        CONCAT('Se cambió el estatus del usuario "', IFNULL(v_username, 'Desconocido'), '" de ', IFNULL(v_estatus, 'Desconocido'), ' a ', p_nuevo_estatus, '.')
    );

    COMMIT;
END $$

DELIMITER ;

-- =========================================================================
-- 5. PROCEDIMIENTO: ELIMINAR USUARIO
-- =========================================================================

DELIMITER $$

CREATE PROCEDURE `sp_eliminar_usuario`(
    IN p_id_usuario INT,
    IN p_usuario_auditor INT
)
BEGIN
    DECLARE v_username VARCHAR(255);
    DECLARE v_cedula VARCHAR(10);
    DECLARE v_cant_notificaciones INT;
    DECLARE v_cant_recuperaciones INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo eliminar el usuario.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT `username`, `cedula` INTO v_username, v_cedula
    FROM `tbl_usuarios`
    WHERE `id_usuario` = p_id_usuario
    LIMIT 1 FOR UPDATE;

    SET v_cant_notificaciones = (SELECT COUNT(*) FROM `tbl_notificaciones` WHERE `id_usuario` = p_id_usuario);
    SET v_cant_recuperaciones = (SELECT COUNT(*) FROM `tbl_recuperar` WHERE `id_usuario` = p_id_usuario);

    DELETE FROM `tbl_usuarios`
    WHERE `id_usuario` = p_id_usuario;

    INSERT INTO `tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Usuario', 
        'ELIMINAR', 
        NULL, 
        JSON_OBJECT('id_usuario', p_id_usuario, 'username', v_username, 'cedula', v_cedula), 
        p_usuario_auditor, 
        'alta', 
        CONCAT('Se eliminó el usuario "', IFNULL(v_username, 'Desconocido'), '" (C.I: ', IFNULL(v_cedula, 'Desconocido'), ').')
    );

    COMMIT;
END $$

DELIMITER ;
-- -----------------------------------------------------------------------------
-- . PROCEDIMIENTO PERMISOS CON LOCK IN SHARE
-- -----------------------------------------------------------------------------

DELIMITER $$

CREATE PROCEDURE sp_guardar_permisos(
    IN p_permisos_json JSON,
    IN p_roles_json JSON,
    IN p_modulos_json JSON,
    IN p_acciones_json JSON
)
BEGIN
    DECLARE v_id_rol INT;
    DECLARE v_id_modulo INT;
    DECLARE v_accion VARCHAR(50);
    DECLARE v_estatus VARCHAR(20);
    DECLARE v_i INT;
    DECLARE v_j INT;
    DECLARE v_k INT;
    DECLARE v_roles_count INT;
    DECLARE v_modulos_count INT;
    DECLARE v_acciones_count INT;
    DECLARE v_permiso_valor TEXT;

    -- Manejador de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- No hacer ROLLBACK aquí, dejar que PHP lo maneje
        RESIGNAL;
    END;

    -- Bloqueo exclusivo de la tabla de permisos para evitar modificaciones concurrentes
    -- FOR UPDATE bloquea todas las filas para lectura y escritura
    SELECT * FROM tbl_permisos LOCK IN SHARE MODE;

    -- Eliminar permisos existentes de los roles especificados (excepto rol 6)
    SET v_roles_count = JSON_LENGTH(p_roles_json);
    SET v_i = 0;
    
    WHILE v_i < v_roles_count DO
        SET v_id_rol = JSON_UNQUOTE(JSON_EXTRACT(p_roles_json, CONCAT('$[', v_i, '].id_rol')));
        
        IF v_id_rol != 6 THEN
            DELETE FROM tbl_permisos WHERE id_rol = v_id_rol;
        END IF;
        
        SET v_i = v_i + 1;
    END WHILE;

    -- Insertar nuevos permisos
    SET v_roles_count = JSON_LENGTH(p_roles_json);
    SET v_modulos_count = JSON_LENGTH(p_modulos_json);
    SET v_acciones_count = JSON_LENGTH(p_acciones_json);
    
    SET v_i = 0;
    WHILE v_i < v_roles_count DO
        SET v_id_rol = JSON_UNQUOTE(JSON_EXTRACT(p_roles_json, CONCAT('$[', v_i, '].id_rol')));
        
        IF v_id_rol != 6 THEN
            SET v_j = 0;
            WHILE v_j < v_modulos_count DO
                SET v_id_modulo = JSON_UNQUOTE(JSON_EXTRACT(p_modulos_json, CONCAT('$[', v_j, '].id_modulo')));
                
                SET v_k = 0;
                WHILE v_k < v_acciones_count DO
                    SET v_accion = JSON_UNQUOTE(JSON_EXTRACT(p_acciones_json, CONCAT('$[', v_k, ']')));
                    
                    -- Verificar si el permiso está marcado como 'on' en el JSON de permisos
                    SET v_permiso_valor = JSON_UNQUOTE(JSON_EXTRACT(p_permisos_json, CONCAT('$.', v_id_rol, '.', v_id_modulo, '.', v_accion)));
                    
                    IF v_permiso_valor = 'on' THEN
                        SET v_estatus = 'Permitido';
                    ELSE
                        SET v_estatus = 'No Permitido';
                    END IF;
                    
                    INSERT INTO tbl_permisos (id_rol, id_modulo, accion, estatus)
                    VALUES (v_id_rol, v_id_modulo, v_accion, v_estatus);
                    
                    SET v_k = v_k + 1;
                END WHILE;
                
                SET v_j = v_j + 1;
            END WHILE;
        END IF;
        
        SET v_i = v_i + 1;
    END WHILE;
END $$

DELIMITER ;