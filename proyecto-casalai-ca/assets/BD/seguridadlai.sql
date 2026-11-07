-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-03-2026 a las 03:12:57
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
-- Base de datos: `seguridadlai`
CREATE DATABASE IF NOT EXISTS `seguridadlai` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `seguridadlai`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_alertas`
--

CREATE TABLE `tbl_alertas` (
  `id_alerta` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `mensaje` varchar(150) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_bitacora`
--

CREATE TABLE `tbl_bitacora` (
  `id_bitacora` int(11) NOT NULL,
  `fecha_hora` text NOT NULL,
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
(2206, '2026-03-05 22:11:37', 'Productos', 'ACCESAR', NULL, NULL, 3, 'media', 'El usuario accedió al módulo de Productos');

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
(77, 9, '', 'Nueva recepción registrada', 'Se ha registrado una nueva recepción #897964 con 35 unidades por el usuario Diego', 12, '2025-11-02 10:36:46', 0, 'media'),
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
(9681, 'ingresar', 1, 1, 'Permitido'),
(9682, 'consultar', 1, 1, 'Permitido'),
(9683, 'incluir', 1, 1, 'Permitido'),
(9684, 'modificar', 1, 1, 'Permitido'),
(9685, 'eliminar', 1, 1, 'Permitido'),
(9686, 'generar re', 1, 1, 'No Permitido'),
(9687, 'ingresar', 1, 2, 'Permitido'),
(9688, 'consultar', 1, 2, 'Permitido'),
(9689, 'incluir', 1, 2, 'Permitido'),
(9690, 'modificar', 1, 2, 'Permitido'),
(9691, 'eliminar', 1, 2, 'Permitido'),
(9692, 'generar re', 1, 2, 'No Permitido'),
(9693, 'ingresar', 1, 3, 'Permitido'),
(9694, 'consultar', 1, 3, 'Permitido'),
(9695, 'incluir', 1, 3, 'Permitido'),
(9696, 'modificar', 1, 3, 'Permitido'),
(9697, 'eliminar', 1, 3, 'Permitido'),
(9698, 'generar re', 1, 3, 'No Permitido'),
(9699, 'ingresar', 1, 4, 'Permitido'),
(9700, 'consultar', 1, 4, 'Permitido'),
(9701, 'incluir', 1, 4, 'Permitido'),
(9702, 'modificar', 1, 4, 'Permitido'),
(9703, 'eliminar', 1, 4, 'Permitido'),
(9704, 'generar re', 1, 4, 'No Permitido'),
(9705, 'ingresar', 1, 5, 'Permitido'),
(9706, 'consultar', 1, 5, 'Permitido'),
(9707, 'incluir', 1, 5, 'Permitido'),
(9708, 'modificar', 1, 5, 'Permitido'),
(9709, 'eliminar', 1, 5, 'Permitido'),
(9710, 'generar re', 1, 5, 'No Permitido'),
(9711, 'ingresar', 1, 6, 'Permitido'),
(9712, 'consultar', 1, 6, 'Permitido'),
(9713, 'incluir', 1, 6, 'Permitido'),
(9714, 'modificar', 1, 6, 'Permitido'),
(9715, 'eliminar', 1, 6, 'Permitido'),
(9716, 'generar re', 1, 6, 'No Permitido'),
(9717, 'ingresar', 1, 7, 'Permitido'),
(9718, 'consultar', 1, 7, 'Permitido'),
(9719, 'incluir', 1, 7, 'Permitido'),
(9720, 'modificar', 1, 7, 'Permitido'),
(9721, 'eliminar', 1, 7, 'Permitido'),
(9722, 'generar re', 1, 7, 'No Permitido'),
(9723, 'ingresar', 1, 8, 'Permitido'),
(9724, 'consultar', 1, 8, 'Permitido'),
(9725, 'incluir', 1, 8, 'Permitido'),
(9726, 'modificar', 1, 8, 'Permitido'),
(9727, 'eliminar', 1, 8, 'Permitido'),
(9728, 'generar re', 1, 8, 'No Permitido'),
(9729, 'ingresar', 1, 9, 'Permitido'),
(9730, 'consultar', 1, 9, 'Permitido'),
(9731, 'incluir', 1, 9, 'Permitido'),
(9732, 'modificar', 1, 9, 'Permitido'),
(9733, 'eliminar', 1, 9, 'Permitido'),
(9734, 'generar re', 1, 9, 'No Permitido'),
(9735, 'ingresar', 1, 10, 'Permitido'),
(9736, 'consultar', 1, 10, 'Permitido'),
(9737, 'incluir', 1, 10, 'Permitido'),
(9738, 'modificar', 1, 10, 'Permitido'),
(9739, 'eliminar', 1, 10, 'Permitido'),
(9740, 'generar re', 1, 10, 'No Permitido'),
(9741, 'ingresar', 1, 11, 'Permitido'),
(9742, 'consultar', 1, 11, 'Permitido'),
(9743, 'incluir', 1, 11, 'Permitido'),
(9744, 'modificar', 1, 11, 'Permitido'),
(9745, 'eliminar', 1, 11, 'Permitido'),
(9746, 'generar re', 1, 11, 'No Permitido'),
(9747, 'ingresar', 1, 12, 'Permitido'),
(9748, 'consultar', 1, 12, 'Permitido'),
(9749, 'incluir', 1, 12, 'Permitido'),
(9750, 'modificar', 1, 12, 'Permitido'),
(9751, 'eliminar', 1, 12, 'Permitido'),
(9752, 'generar re', 1, 12, 'No Permitido'),
(9753, 'ingresar', 1, 13, 'Permitido'),
(9754, 'consultar', 1, 13, 'Permitido'),
(9755, 'incluir', 1, 13, 'Permitido'),
(9756, 'modificar', 1, 13, 'Permitido'),
(9757, 'eliminar', 1, 13, 'Permitido'),
(9758, 'generar re', 1, 13, 'No Permitido'),
(9759, 'ingresar', 1, 14, 'Permitido'),
(9760, 'consultar', 1, 14, 'Permitido'),
(9761, 'incluir', 1, 14, 'Permitido'),
(9762, 'modificar', 1, 14, 'Permitido'),
(9763, 'eliminar', 1, 14, 'Permitido'),
(9764, 'generar re', 1, 14, 'No Permitido'),
(9765, 'ingresar', 1, 15, 'Permitido'),
(9766, 'consultar', 1, 15, 'Permitido'),
(9767, 'incluir', 1, 15, 'Permitido'),
(9768, 'modificar', 1, 15, 'Permitido'),
(9769, 'eliminar', 1, 15, 'Permitido'),
(9770, 'generar re', 1, 15, 'No Permitido'),
(9771, 'ingresar', 1, 16, 'Permitido'),
(9772, 'consultar', 1, 16, 'Permitido'),
(9773, 'incluir', 1, 16, 'Permitido'),
(9774, 'modificar', 1, 16, 'Permitido'),
(9775, 'eliminar', 1, 16, 'Permitido'),
(9776, 'generar re', 1, 16, 'No Permitido'),
(9777, 'ingresar', 1, 17, 'Permitido'),
(9778, 'consultar', 1, 17, 'Permitido'),
(9779, 'incluir', 1, 17, 'Permitido'),
(9780, 'modificar', 1, 17, 'Permitido'),
(9781, 'eliminar', 1, 17, 'Permitido'),
(9782, 'generar re', 1, 17, 'No Permitido'),
(9783, 'ingresar', 1, 18, 'Permitido'),
(9784, 'consultar', 1, 18, 'Permitido'),
(9785, 'incluir', 1, 18, 'Permitido'),
(9786, 'modificar', 1, 18, 'Permitido'),
(9787, 'eliminar', 1, 18, 'Permitido'),
(9788, 'generar re', 1, 18, 'No Permitido'),
(9789, 'ingresar', 1, 19, 'Permitido'),
(9790, 'consultar', 1, 19, 'Permitido'),
(9791, 'incluir', 1, 19, 'Permitido'),
(9792, 'modificar', 1, 19, 'Permitido'),
(9793, 'eliminar', 1, 19, 'Permitido'),
(9794, 'generar re', 1, 19, 'No Permitido'),
(9795, 'ingresar', 1, 20, 'No Permitido'),
(9796, 'consultar', 1, 20, 'No Permitido'),
(9797, 'incluir', 1, 20, 'No Permitido'),
(9798, 'modificar', 1, 20, 'No Permitido'),
(9799, 'eliminar', 1, 20, 'No Permitido'),
(9800, 'generar re', 1, 20, 'No Permitido'),
(9801, 'ingresar', 1, 21, 'Permitido'),
(9802, 'consultar', 1, 21, 'Permitido'),
(9803, 'incluir', 1, 21, 'Permitido'),
(9804, 'modificar', 1, 21, 'Permitido'),
(9805, 'eliminar', 1, 21, 'Permitido'),
(9806, 'generar re', 1, 21, 'No Permitido'),
(9807, 'ingresar', 1, 22, 'No Permitido'),
(9808, 'consultar', 1, 22, 'No Permitido'),
(9809, 'incluir', 1, 22, 'No Permitido'),
(9810, 'modificar', 1, 22, 'No Permitido'),
(9811, 'eliminar', 1, 22, 'No Permitido'),
(9812, 'generar re', 1, 22, 'No Permitido'),
(9813, 'ingresar', 2, 1, 'No Permitido'),
(9814, 'consultar', 2, 1, 'No Permitido'),
(9815, 'incluir', 2, 1, 'No Permitido'),
(9816, 'modificar', 2, 1, 'No Permitido'),
(9817, 'eliminar', 2, 1, 'No Permitido'),
(9818, 'generar re', 2, 1, 'No Permitido'),
(9819, 'ingresar', 2, 2, 'Permitido'),
(9820, 'consultar', 2, 2, 'Permitido'),
(9821, 'incluir', 2, 2, 'Permitido'),
(9822, 'modificar', 2, 2, 'Permitido'),
(9823, 'eliminar', 2, 2, 'Permitido'),
(9824, 'generar re', 2, 2, 'No Permitido'),
(9825, 'ingresar', 2, 3, 'Permitido'),
(9826, 'consultar', 2, 3, 'Permitido'),
(9827, 'incluir', 2, 3, 'Permitido'),
(9828, 'modificar', 2, 3, 'Permitido'),
(9829, 'eliminar', 2, 3, 'Permitido'),
(9830, 'generar re', 2, 3, 'No Permitido'),
(9831, 'ingresar', 2, 4, 'No Permitido'),
(9832, 'consultar', 2, 4, 'No Permitido'),
(9833, 'incluir', 2, 4, 'No Permitido'),
(9834, 'modificar', 2, 4, 'No Permitido'),
(9835, 'eliminar', 2, 4, 'No Permitido'),
(9836, 'generar re', 2, 4, 'No Permitido'),
(9837, 'ingresar', 2, 5, 'No Permitido'),
(9838, 'consultar', 2, 5, 'No Permitido'),
(9839, 'incluir', 2, 5, 'No Permitido'),
(9840, 'modificar', 2, 5, 'No Permitido'),
(9841, 'eliminar', 2, 5, 'No Permitido'),
(9842, 'generar re', 2, 5, 'No Permitido'),
(9843, 'ingresar', 2, 6, 'No Permitido'),
(9844, 'consultar', 2, 6, 'No Permitido'),
(9845, 'incluir', 2, 6, 'No Permitido'),
(9846, 'modificar', 2, 6, 'No Permitido'),
(9847, 'eliminar', 2, 6, 'No Permitido'),
(9848, 'generar re', 2, 6, 'No Permitido'),
(9849, 'ingresar', 2, 7, 'No Permitido'),
(9850, 'consultar', 2, 7, 'No Permitido'),
(9851, 'incluir', 2, 7, 'No Permitido'),
(9852, 'modificar', 2, 7, 'No Permitido'),
(9853, 'eliminar', 2, 7, 'No Permitido'),
(9854, 'generar re', 2, 7, 'No Permitido'),
(9855, 'ingresar', 2, 8, 'No Permitido'),
(9856, 'consultar', 2, 8, 'No Permitido'),
(9857, 'incluir', 2, 8, 'No Permitido'),
(9858, 'modificar', 2, 8, 'No Permitido'),
(9859, 'eliminar', 2, 8, 'No Permitido'),
(9860, 'generar re', 2, 8, 'No Permitido'),
(9861, 'ingresar', 2, 9, 'No Permitido'),
(9862, 'consultar', 2, 9, 'No Permitido'),
(9863, 'incluir', 2, 9, 'No Permitido'),
(9864, 'modificar', 2, 9, 'No Permitido'),
(9865, 'eliminar', 2, 9, 'No Permitido'),
(9866, 'generar re', 2, 9, 'No Permitido'),
(9867, 'ingresar', 2, 10, 'No Permitido'),
(9868, 'consultar', 2, 10, 'No Permitido'),
(9869, 'incluir', 2, 10, 'No Permitido'),
(9870, 'modificar', 2, 10, 'No Permitido'),
(9871, 'eliminar', 2, 10, 'No Permitido'),
(9872, 'generar re', 2, 10, 'No Permitido'),
(9873, 'ingresar', 2, 11, 'No Permitido'),
(9874, 'consultar', 2, 11, 'No Permitido'),
(9875, 'incluir', 2, 11, 'No Permitido'),
(9876, 'modificar', 2, 11, 'No Permitido'),
(9877, 'eliminar', 2, 11, 'No Permitido'),
(9878, 'generar re', 2, 11, 'No Permitido'),
(9879, 'ingresar', 2, 12, 'No Permitido'),
(9880, 'consultar', 2, 12, 'No Permitido'),
(9881, 'incluir', 2, 12, 'No Permitido'),
(9882, 'modificar', 2, 12, 'No Permitido'),
(9883, 'eliminar', 2, 12, 'No Permitido'),
(9884, 'generar re', 2, 12, 'No Permitido'),
(9885, 'ingresar', 2, 13, 'No Permitido'),
(9886, 'consultar', 2, 13, 'No Permitido'),
(9887, 'incluir', 2, 13, 'No Permitido'),
(9888, 'modificar', 2, 13, 'No Permitido'),
(9889, 'eliminar', 2, 13, 'No Permitido'),
(9890, 'generar re', 2, 13, 'No Permitido'),
(9891, 'ingresar', 2, 14, 'No Permitido'),
(9892, 'consultar', 2, 14, 'No Permitido'),
(9893, 'incluir', 2, 14, 'No Permitido'),
(9894, 'modificar', 2, 14, 'No Permitido'),
(9895, 'eliminar', 2, 14, 'No Permitido'),
(9896, 'generar re', 2, 14, 'No Permitido'),
(9897, 'ingresar', 2, 15, 'No Permitido'),
(9898, 'consultar', 2, 15, 'No Permitido'),
(9899, 'incluir', 2, 15, 'No Permitido'),
(9900, 'modificar', 2, 15, 'No Permitido'),
(9901, 'eliminar', 2, 15, 'No Permitido'),
(9902, 'generar re', 2, 15, 'No Permitido'),
(9903, 'ingresar', 2, 16, 'No Permitido'),
(9904, 'consultar', 2, 16, 'No Permitido'),
(9905, 'incluir', 2, 16, 'No Permitido'),
(9906, 'modificar', 2, 16, 'No Permitido'),
(9907, 'eliminar', 2, 16, 'No Permitido'),
(9908, 'generar re', 2, 16, 'No Permitido'),
(9909, 'ingresar', 2, 17, 'No Permitido'),
(9910, 'consultar', 2, 17, 'No Permitido'),
(9911, 'incluir', 2, 17, 'No Permitido'),
(9912, 'modificar', 2, 17, 'No Permitido'),
(9913, 'eliminar', 2, 17, 'No Permitido'),
(9914, 'generar re', 2, 17, 'No Permitido'),
(9915, 'ingresar', 2, 18, 'No Permitido'),
(9916, 'consultar', 2, 18, 'No Permitido'),
(9917, 'incluir', 2, 18, 'No Permitido'),
(9918, 'modificar', 2, 18, 'No Permitido'),
(9919, 'eliminar', 2, 18, 'No Permitido'),
(9920, 'generar re', 2, 18, 'No Permitido'),
(9921, 'ingresar', 2, 19, 'No Permitido'),
(9922, 'consultar', 2, 19, 'No Permitido'),
(9923, 'incluir', 2, 19, 'No Permitido'),
(9924, 'modificar', 2, 19, 'No Permitido'),
(9925, 'eliminar', 2, 19, 'No Permitido'),
(9926, 'generar re', 2, 19, 'No Permitido'),
(9927, 'ingresar', 2, 20, 'No Permitido'),
(9928, 'consultar', 2, 20, 'No Permitido'),
(9929, 'incluir', 2, 20, 'No Permitido'),
(9930, 'modificar', 2, 20, 'No Permitido'),
(9931, 'eliminar', 2, 20, 'No Permitido'),
(9932, 'generar re', 2, 20, 'No Permitido'),
(9933, 'ingresar', 2, 21, 'No Permitido'),
(9934, 'consultar', 2, 21, 'No Permitido'),
(9935, 'incluir', 2, 21, 'No Permitido'),
(9936, 'modificar', 2, 21, 'No Permitido'),
(9937, 'eliminar', 2, 21, 'No Permitido'),
(9938, 'generar re', 2, 21, 'No Permitido'),
(9939, 'ingresar', 2, 22, 'No Permitido'),
(9940, 'consultar', 2, 22, 'No Permitido'),
(9941, 'incluir', 2, 22, 'No Permitido'),
(9942, 'modificar', 2, 22, 'No Permitido'),
(9943, 'eliminar', 2, 22, 'No Permitido'),
(9944, 'generar re', 2, 22, 'No Permitido'),
(9945, 'ingresar', 3, 1, 'No Permitido'),
(9946, 'consultar', 3, 1, 'No Permitido'),
(9947, 'incluir', 3, 1, 'No Permitido'),
(9948, 'modificar', 3, 1, 'No Permitido'),
(9949, 'eliminar', 3, 1, 'No Permitido'),
(9950, 'generar re', 3, 1, 'No Permitido'),
(9951, 'ingresar', 3, 2, 'No Permitido'),
(9952, 'consultar', 3, 2, 'No Permitido'),
(9953, 'incluir', 3, 2, 'No Permitido'),
(9954, 'modificar', 3, 2, 'No Permitido'),
(9955, 'eliminar', 3, 2, 'No Permitido'),
(9956, 'generar re', 3, 2, 'No Permitido'),
(9957, 'ingresar', 3, 3, 'No Permitido'),
(9958, 'consultar', 3, 3, 'No Permitido'),
(9959, 'incluir', 3, 3, 'No Permitido'),
(9960, 'modificar', 3, 3, 'No Permitido'),
(9961, 'eliminar', 3, 3, 'No Permitido'),
(9962, 'generar re', 3, 3, 'No Permitido'),
(9963, 'ingresar', 3, 4, 'No Permitido'),
(9964, 'consultar', 3, 4, 'No Permitido'),
(9965, 'incluir', 3, 4, 'No Permitido'),
(9966, 'modificar', 3, 4, 'No Permitido'),
(9967, 'eliminar', 3, 4, 'No Permitido'),
(9968, 'generar re', 3, 4, 'No Permitido'),
(9969, 'ingresar', 3, 5, 'No Permitido'),
(9970, 'consultar', 3, 5, 'No Permitido'),
(9971, 'incluir', 3, 5, 'No Permitido'),
(9972, 'modificar', 3, 5, 'No Permitido'),
(9973, 'eliminar', 3, 5, 'No Permitido'),
(9974, 'generar re', 3, 5, 'No Permitido'),
(9975, 'ingresar', 3, 6, 'No Permitido'),
(9976, 'consultar', 3, 6, 'No Permitido'),
(9977, 'incluir', 3, 6, 'No Permitido'),
(9978, 'modificar', 3, 6, 'No Permitido'),
(9979, 'eliminar', 3, 6, 'No Permitido'),
(9980, 'generar re', 3, 6, 'No Permitido'),
(9981, 'ingresar', 3, 7, 'No Permitido'),
(9982, 'consultar', 3, 7, 'No Permitido'),
(9983, 'incluir', 3, 7, 'No Permitido'),
(9984, 'modificar', 3, 7, 'No Permitido'),
(9985, 'eliminar', 3, 7, 'No Permitido'),
(9986, 'generar re', 3, 7, 'No Permitido'),
(9987, 'ingresar', 3, 8, 'No Permitido'),
(9988, 'consultar', 3, 8, 'No Permitido'),
(9989, 'incluir', 3, 8, 'No Permitido'),
(9990, 'modificar', 3, 8, 'No Permitido'),
(9991, 'eliminar', 3, 8, 'No Permitido'),
(9992, 'generar re', 3, 8, 'No Permitido'),
(9993, 'ingresar', 3, 9, 'No Permitido'),
(9994, 'consultar', 3, 9, 'No Permitido'),
(9995, 'incluir', 3, 9, 'No Permitido'),
(9996, 'modificar', 3, 9, 'No Permitido'),
(9997, 'eliminar', 3, 9, 'No Permitido'),
(9998, 'generar re', 3, 9, 'No Permitido'),
(9999, 'ingresar', 3, 10, 'Permitido'),
(10000, 'consultar', 3, 10, 'Permitido'),
(10001, 'incluir', 3, 10, 'Permitido'),
(10002, 'modificar', 3, 10, 'Permitido'),
(10003, 'eliminar', 3, 10, 'Permitido'),
(10004, 'generar re', 3, 10, 'No Permitido'),
(10005, 'ingresar', 3, 11, 'Permitido'),
(10006, 'consultar', 3, 11, 'Permitido'),
(10007, 'incluir', 3, 11, 'Permitido'),
(10008, 'modificar', 3, 11, 'Permitido'),
(10009, 'eliminar', 3, 11, 'Permitido'),
(10010, 'generar re', 3, 11, 'No Permitido'),
(10011, 'ingresar', 3, 12, 'Permitido'),
(10012, 'consultar', 3, 12, 'Permitido'),
(10013, 'incluir', 3, 12, 'Permitido'),
(10014, 'modificar', 3, 12, 'Permitido'),
(10015, 'eliminar', 3, 12, 'Permitido'),
(10016, 'generar re', 3, 12, 'No Permitido'),
(10017, 'ingresar', 3, 13, 'Permitido'),
(10018, 'consultar', 3, 13, 'Permitido'),
(10019, 'incluir', 3, 13, 'Permitido'),
(10020, 'modificar', 3, 13, 'Permitido'),
(10021, 'eliminar', 3, 13, 'Permitido'),
(10022, 'generar re', 3, 13, 'No Permitido'),
(10023, 'ingresar', 3, 14, 'No Permitido'),
(10024, 'consultar', 3, 14, 'No Permitido'),
(10025, 'incluir', 3, 14, 'No Permitido'),
(10026, 'modificar', 3, 14, 'No Permitido'),
(10027, 'eliminar', 3, 14, 'No Permitido'),
(10028, 'generar re', 3, 14, 'No Permitido'),
(10029, 'ingresar', 3, 15, 'No Permitido'),
(10030, 'consultar', 3, 15, 'No Permitido'),
(10031, 'incluir', 3, 15, 'No Permitido'),
(10032, 'modificar', 3, 15, 'No Permitido'),
(10033, 'eliminar', 3, 15, 'No Permitido'),
(10034, 'generar re', 3, 15, 'No Permitido'),
(10035, 'ingresar', 3, 16, 'No Permitido'),
(10036, 'consultar', 3, 16, 'No Permitido'),
(10037, 'incluir', 3, 16, 'No Permitido'),
(10038, 'modificar', 3, 16, 'No Permitido'),
(10039, 'eliminar', 3, 16, 'No Permitido'),
(10040, 'generar re', 3, 16, 'No Permitido'),
(10041, 'ingresar', 3, 17, 'No Permitido'),
(10042, 'consultar', 3, 17, 'No Permitido'),
(10043, 'incluir', 3, 17, 'No Permitido'),
(10044, 'modificar', 3, 17, 'No Permitido'),
(10045, 'eliminar', 3, 17, 'No Permitido'),
(10046, 'generar re', 3, 17, 'No Permitido'),
(10047, 'ingresar', 3, 18, 'No Permitido'),
(10048, 'consultar', 3, 18, 'No Permitido'),
(10049, 'incluir', 3, 18, 'No Permitido'),
(10050, 'modificar', 3, 18, 'No Permitido'),
(10051, 'eliminar', 3, 18, 'No Permitido'),
(10052, 'generar re', 3, 18, 'No Permitido'),
(10053, 'ingresar', 3, 19, 'No Permitido'),
(10054, 'consultar', 3, 19, 'No Permitido'),
(10055, 'incluir', 3, 19, 'No Permitido'),
(10056, 'modificar', 3, 19, 'No Permitido'),
(10057, 'eliminar', 3, 19, 'No Permitido'),
(10058, 'generar re', 3, 19, 'No Permitido'),
(10059, 'ingresar', 3, 20, 'No Permitido'),
(10060, 'consultar', 3, 20, 'No Permitido'),
(10061, 'incluir', 3, 20, 'No Permitido'),
(10062, 'modificar', 3, 20, 'No Permitido'),
(10063, 'eliminar', 3, 20, 'No Permitido'),
(10064, 'generar re', 3, 20, 'No Permitido'),
(10065, 'ingresar', 3, 21, 'No Permitido'),
(10066, 'consultar', 3, 21, 'No Permitido'),
(10067, 'incluir', 3, 21, 'No Permitido'),
(10068, 'modificar', 3, 21, 'No Permitido'),
(10069, 'eliminar', 3, 21, 'No Permitido'),
(10070, 'generar re', 3, 21, 'No Permitido'),
(10071, 'ingresar', 3, 22, 'No Permitido'),
(10072, 'consultar', 3, 22, 'No Permitido'),
(10073, 'incluir', 3, 22, 'No Permitido'),
(10074, 'modificar', 3, 22, 'No Permitido'),
(10075, 'eliminar', 3, 22, 'No Permitido'),
(10076, 'generar re', 3, 22, 'No Permitido'),
(10077, 'ingresar', 4, 1, 'No Permitido'),
(10078, 'consultar', 4, 1, 'No Permitido'),
(10079, 'incluir', 4, 1, 'No Permitido'),
(10080, 'modificar', 4, 1, 'No Permitido'),
(10081, 'eliminar', 4, 1, 'No Permitido'),
(10082, 'generar re', 4, 1, 'No Permitido'),
(10083, 'ingresar', 4, 2, 'No Permitido'),
(10084, 'consultar', 4, 2, 'No Permitido'),
(10085, 'incluir', 4, 2, 'No Permitido'),
(10086, 'modificar', 4, 2, 'No Permitido'),
(10087, 'eliminar', 4, 2, 'No Permitido'),
(10088, 'generar re', 4, 2, 'No Permitido'),
(10089, 'ingresar', 4, 3, 'No Permitido'),
(10090, 'consultar', 4, 3, 'No Permitido'),
(10091, 'incluir', 4, 3, 'No Permitido'),
(10092, 'modificar', 4, 3, 'No Permitido'),
(10093, 'eliminar', 4, 3, 'No Permitido'),
(10094, 'generar re', 4, 3, 'No Permitido'),
(10095, 'ingresar', 4, 4, 'No Permitido'),
(10096, 'consultar', 4, 4, 'No Permitido'),
(10097, 'incluir', 4, 4, 'No Permitido'),
(10098, 'modificar', 4, 4, 'No Permitido'),
(10099, 'eliminar', 4, 4, 'No Permitido'),
(10100, 'generar re', 4, 4, 'No Permitido'),
(10101, 'ingresar', 4, 5, 'No Permitido'),
(10102, 'consultar', 4, 5, 'No Permitido'),
(10103, 'incluir', 4, 5, 'No Permitido'),
(10104, 'modificar', 4, 5, 'No Permitido'),
(10105, 'eliminar', 4, 5, 'No Permitido'),
(10106, 'generar re', 4, 5, 'No Permitido'),
(10107, 'ingresar', 4, 6, 'No Permitido'),
(10108, 'consultar', 4, 6, 'No Permitido'),
(10109, 'incluir', 4, 6, 'No Permitido'),
(10110, 'modificar', 4, 6, 'No Permitido'),
(10111, 'eliminar', 4, 6, 'No Permitido'),
(10112, 'generar re', 4, 6, 'No Permitido'),
(10113, 'ingresar', 4, 7, 'No Permitido'),
(10114, 'consultar', 4, 7, 'No Permitido'),
(10115, 'incluir', 4, 7, 'No Permitido'),
(10116, 'modificar', 4, 7, 'No Permitido'),
(10117, 'eliminar', 4, 7, 'No Permitido'),
(10118, 'generar re', 4, 7, 'No Permitido'),
(10119, 'ingresar', 4, 8, 'No Permitido'),
(10120, 'consultar', 4, 8, 'No Permitido'),
(10121, 'incluir', 4, 8, 'No Permitido'),
(10122, 'modificar', 4, 8, 'No Permitido'),
(10123, 'eliminar', 4, 8, 'No Permitido'),
(10124, 'generar re', 4, 8, 'No Permitido'),
(10125, 'ingresar', 4, 9, 'No Permitido'),
(10126, 'consultar', 4, 9, 'No Permitido'),
(10127, 'incluir', 4, 9, 'No Permitido'),
(10128, 'modificar', 4, 9, 'No Permitido'),
(10129, 'eliminar', 4, 9, 'No Permitido'),
(10130, 'generar re', 4, 9, 'No Permitido'),
(10131, 'ingresar', 4, 10, 'No Permitido'),
(10132, 'consultar', 4, 10, 'No Permitido'),
(10133, 'incluir', 4, 10, 'No Permitido'),
(10134, 'modificar', 4, 10, 'No Permitido'),
(10135, 'eliminar', 4, 10, 'No Permitido'),
(10136, 'generar re', 4, 10, 'No Permitido'),
(10137, 'ingresar', 4, 11, 'No Permitido'),
(10138, 'consultar', 4, 11, 'No Permitido'),
(10139, 'incluir', 4, 11, 'No Permitido'),
(10140, 'modificar', 4, 11, 'No Permitido'),
(10141, 'eliminar', 4, 11, 'No Permitido'),
(10142, 'generar re', 4, 11, 'No Permitido'),
(10143, 'ingresar', 4, 12, 'No Permitido'),
(10144, 'consultar', 4, 12, 'No Permitido'),
(10145, 'incluir', 4, 12, 'No Permitido'),
(10146, 'modificar', 4, 12, 'No Permitido'),
(10147, 'eliminar', 4, 12, 'No Permitido'),
(10148, 'generar re', 4, 12, 'No Permitido'),
(10149, 'ingresar', 4, 13, 'No Permitido'),
(10150, 'consultar', 4, 13, 'No Permitido'),
(10151, 'incluir', 4, 13, 'No Permitido'),
(10152, 'modificar', 4, 13, 'No Permitido'),
(10153, 'eliminar', 4, 13, 'No Permitido'),
(10154, 'generar re', 4, 13, 'No Permitido'),
(10155, 'ingresar', 4, 14, 'No Permitido'),
(10156, 'consultar', 4, 14, 'No Permitido'),
(10157, 'incluir', 4, 14, 'No Permitido'),
(10158, 'modificar', 4, 14, 'No Permitido'),
(10159, 'eliminar', 4, 14, 'No Permitido'),
(10160, 'generar re', 4, 14, 'No Permitido'),
(10161, 'ingresar', 4, 15, 'No Permitido'),
(10162, 'consultar', 4, 15, 'No Permitido'),
(10163, 'incluir', 4, 15, 'No Permitido'),
(10164, 'modificar', 4, 15, 'No Permitido'),
(10165, 'eliminar', 4, 15, 'No Permitido'),
(10166, 'generar re', 4, 15, 'No Permitido'),
(10167, 'ingresar', 4, 16, 'No Permitido'),
(10168, 'consultar', 4, 16, 'No Permitido'),
(10169, 'incluir', 4, 16, 'No Permitido'),
(10170, 'modificar', 4, 16, 'No Permitido'),
(10171, 'eliminar', 4, 16, 'No Permitido'),
(10172, 'generar re', 4, 16, 'No Permitido'),
(10173, 'ingresar', 4, 17, 'No Permitido'),
(10174, 'consultar', 4, 17, 'No Permitido'),
(10175, 'incluir', 4, 17, 'No Permitido'),
(10176, 'modificar', 4, 17, 'No Permitido'),
(10177, 'eliminar', 4, 17, 'No Permitido'),
(10178, 'generar re', 4, 17, 'No Permitido'),
(10179, 'ingresar', 4, 18, 'No Permitido'),
(10180, 'consultar', 4, 18, 'No Permitido'),
(10181, 'incluir', 4, 18, 'No Permitido'),
(10182, 'modificar', 4, 18, 'No Permitido'),
(10183, 'eliminar', 4, 18, 'No Permitido'),
(10184, 'generar re', 4, 18, 'No Permitido'),
(10185, 'ingresar', 4, 19, 'No Permitido'),
(10186, 'consultar', 4, 19, 'No Permitido'),
(10187, 'incluir', 4, 19, 'No Permitido'),
(10188, 'modificar', 4, 19, 'No Permitido'),
(10189, 'eliminar', 4, 19, 'No Permitido'),
(10190, 'generar re', 4, 19, 'No Permitido'),
(10191, 'ingresar', 4, 20, 'No Permitido'),
(10192, 'consultar', 4, 20, 'No Permitido'),
(10193, 'incluir', 4, 20, 'No Permitido'),
(10194, 'modificar', 4, 20, 'No Permitido'),
(10195, 'eliminar', 4, 20, 'No Permitido'),
(10196, 'generar re', 4, 20, 'No Permitido'),
(10197, 'ingresar', 4, 21, 'No Permitido'),
(10198, 'consultar', 4, 21, 'No Permitido'),
(10199, 'incluir', 4, 21, 'No Permitido'),
(10200, 'modificar', 4, 21, 'No Permitido'),
(10201, 'eliminar', 4, 21, 'No Permitido'),
(10202, 'generar re', 4, 21, 'No Permitido'),
(10203, 'ingresar', 4, 22, 'No Permitido'),
(10204, 'consultar', 4, 22, 'No Permitido'),
(10205, 'incluir', 4, 22, 'No Permitido'),
(10206, 'modificar', 4, 22, 'No Permitido'),
(10207, 'eliminar', 4, 22, 'No Permitido'),
(10208, 'generar re', 4, 22, 'No Permitido'),
(10209, 'ingresar', 7, 1, 'No Permitido'),
(10210, 'consultar', 7, 1, 'No Permitido'),
(10211, 'incluir', 7, 1, 'No Permitido'),
(10212, 'modificar', 7, 1, 'No Permitido'),
(10213, 'eliminar', 7, 1, 'No Permitido'),
(10214, 'generar re', 7, 1, 'No Permitido'),
(10215, 'ingresar', 7, 2, 'No Permitido'),
(10216, 'consultar', 7, 2, 'No Permitido'),
(10217, 'incluir', 7, 2, 'No Permitido'),
(10218, 'modificar', 7, 2, 'No Permitido'),
(10219, 'eliminar', 7, 2, 'No Permitido'),
(10220, 'generar re', 7, 2, 'No Permitido'),
(10221, 'ingresar', 7, 3, 'No Permitido'),
(10222, 'consultar', 7, 3, 'No Permitido'),
(10223, 'incluir', 7, 3, 'No Permitido'),
(10224, 'modificar', 7, 3, 'No Permitido'),
(10225, 'eliminar', 7, 3, 'No Permitido'),
(10226, 'generar re', 7, 3, 'No Permitido'),
(10227, 'ingresar', 7, 4, 'No Permitido'),
(10228, 'consultar', 7, 4, 'No Permitido'),
(10229, 'incluir', 7, 4, 'No Permitido'),
(10230, 'modificar', 7, 4, 'No Permitido'),
(10231, 'eliminar', 7, 4, 'No Permitido'),
(10232, 'generar re', 7, 4, 'No Permitido'),
(10233, 'ingresar', 7, 5, 'No Permitido'),
(10234, 'consultar', 7, 5, 'No Permitido'),
(10235, 'incluir', 7, 5, 'No Permitido'),
(10236, 'modificar', 7, 5, 'No Permitido'),
(10237, 'eliminar', 7, 5, 'No Permitido'),
(10238, 'generar re', 7, 5, 'No Permitido'),
(10239, 'ingresar', 7, 6, 'No Permitido'),
(10240, 'consultar', 7, 6, 'No Permitido'),
(10241, 'incluir', 7, 6, 'No Permitido'),
(10242, 'modificar', 7, 6, 'No Permitido'),
(10243, 'eliminar', 7, 6, 'No Permitido'),
(10244, 'generar re', 7, 6, 'No Permitido'),
(10245, 'ingresar', 7, 7, 'No Permitido'),
(10246, 'consultar', 7, 7, 'No Permitido'),
(10247, 'incluir', 7, 7, 'No Permitido'),
(10248, 'modificar', 7, 7, 'No Permitido'),
(10249, 'eliminar', 7, 7, 'No Permitido'),
(10250, 'generar re', 7, 7, 'No Permitido'),
(10251, 'ingresar', 7, 8, 'No Permitido'),
(10252, 'consultar', 7, 8, 'No Permitido'),
(10253, 'incluir', 7, 8, 'No Permitido'),
(10254, 'modificar', 7, 8, 'No Permitido'),
(10255, 'eliminar', 7, 8, 'No Permitido'),
(10256, 'generar re', 7, 8, 'No Permitido'),
(10257, 'ingresar', 7, 9, 'No Permitido'),
(10258, 'consultar', 7, 9, 'No Permitido'),
(10259, 'incluir', 7, 9, 'No Permitido'),
(10260, 'modificar', 7, 9, 'No Permitido'),
(10261, 'eliminar', 7, 9, 'No Permitido'),
(10262, 'generar re', 7, 9, 'No Permitido'),
(10263, 'ingresar', 7, 10, 'No Permitido'),
(10264, 'consultar', 7, 10, 'No Permitido'),
(10265, 'incluir', 7, 10, 'No Permitido'),
(10266, 'modificar', 7, 10, 'No Permitido'),
(10267, 'eliminar', 7, 10, 'No Permitido'),
(10268, 'generar re', 7, 10, 'No Permitido'),
(10269, 'ingresar', 7, 11, 'No Permitido'),
(10270, 'consultar', 7, 11, 'No Permitido'),
(10271, 'incluir', 7, 11, 'No Permitido'),
(10272, 'modificar', 7, 11, 'No Permitido'),
(10273, 'eliminar', 7, 11, 'No Permitido'),
(10274, 'generar re', 7, 11, 'No Permitido'),
(10275, 'ingresar', 7, 12, 'No Permitido'),
(10276, 'consultar', 7, 12, 'No Permitido'),
(10277, 'incluir', 7, 12, 'No Permitido'),
(10278, 'modificar', 7, 12, 'No Permitido'),
(10279, 'eliminar', 7, 12, 'No Permitido'),
(10280, 'generar re', 7, 12, 'No Permitido'),
(10281, 'ingresar', 7, 13, 'No Permitido'),
(10282, 'consultar', 7, 13, 'No Permitido'),
(10283, 'incluir', 7, 13, 'No Permitido'),
(10284, 'modificar', 7, 13, 'No Permitido'),
(10285, 'eliminar', 7, 13, 'No Permitido'),
(10286, 'generar re', 7, 13, 'No Permitido'),
(10287, 'ingresar', 7, 14, 'No Permitido'),
(10288, 'consultar', 7, 14, 'No Permitido'),
(10289, 'incluir', 7, 14, 'No Permitido'),
(10290, 'modificar', 7, 14, 'No Permitido'),
(10291, 'eliminar', 7, 14, 'No Permitido'),
(10292, 'generar re', 7, 14, 'No Permitido'),
(10293, 'ingresar', 7, 15, 'No Permitido'),
(10294, 'consultar', 7, 15, 'No Permitido'),
(10295, 'incluir', 7, 15, 'No Permitido'),
(10296, 'modificar', 7, 15, 'No Permitido'),
(10297, 'eliminar', 7, 15, 'No Permitido'),
(10298, 'generar re', 7, 15, 'No Permitido'),
(10299, 'ingresar', 7, 16, 'No Permitido'),
(10300, 'consultar', 7, 16, 'No Permitido'),
(10301, 'incluir', 7, 16, 'No Permitido'),
(10302, 'modificar', 7, 16, 'No Permitido'),
(10303, 'eliminar', 7, 16, 'No Permitido'),
(10304, 'generar re', 7, 16, 'No Permitido'),
(10305, 'ingresar', 7, 17, 'No Permitido'),
(10306, 'consultar', 7, 17, 'No Permitido'),
(10307, 'incluir', 7, 17, 'No Permitido'),
(10308, 'modificar', 7, 17, 'No Permitido'),
(10309, 'eliminar', 7, 17, 'No Permitido'),
(10310, 'generar re', 7, 17, 'No Permitido'),
(10311, 'ingresar', 7, 18, 'No Permitido'),
(10312, 'consultar', 7, 18, 'No Permitido'),
(10313, 'incluir', 7, 18, 'No Permitido'),
(10314, 'modificar', 7, 18, 'No Permitido'),
(10315, 'eliminar', 7, 18, 'No Permitido'),
(10316, 'generar re', 7, 18, 'No Permitido'),
(10317, 'ingresar', 7, 19, 'No Permitido'),
(10318, 'consultar', 7, 19, 'No Permitido'),
(10319, 'incluir', 7, 19, 'No Permitido'),
(10320, 'modificar', 7, 19, 'No Permitido'),
(10321, 'eliminar', 7, 19, 'No Permitido'),
(10322, 'generar re', 7, 19, 'No Permitido'),
(10323, 'ingresar', 7, 20, 'No Permitido'),
(10324, 'consultar', 7, 20, 'No Permitido'),
(10325, 'incluir', 7, 20, 'No Permitido'),
(10326, 'modificar', 7, 20, 'No Permitido'),
(10327, 'eliminar', 7, 20, 'No Permitido'),
(10328, 'generar re', 7, 20, 'No Permitido'),
(10329, 'ingresar', 7, 21, 'Permitido'),
(10330, 'consultar', 7, 21, 'Permitido'),
(10331, 'incluir', 7, 21, 'Permitido'),
(10332, 'modificar', 7, 21, 'Permitido'),
(10333, 'eliminar', 7, 21, 'Permitido'),
(10334, 'generar re', 7, 21, 'No Permitido'),
(10335, 'ingresar', 7, 22, 'No Permitido'),
(10336, 'consultar', 7, 22, 'No Permitido'),
(10337, 'incluir', 7, 22, 'No Permitido'),
(10338, 'modificar', 7, 22, 'No Permitido'),
(10339, 'eliminar', 7, 22, 'No Permitido'),
(10340, 'generar re', 7, 22, 'No Permitido');

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
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `cedula` varchar(10) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `correo` varchar(50) DEFAULT NULL,
  `nombres` varchar(50) DEFAULT NULL,
  `apellidos` varchar(50) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `estatus` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado',
  `foto_perfil` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_usuarios`
--

INSERT INTO `tbl_usuarios` (`id_usuario`, `username`, `password`, `cedula`, `id_rol`, `correo`, `nombres`, `apellidos`, `telefono`, `estatus`, `foto_perfil`) VALUES
(3, 'Diego', '$2y$10$bJfY45blf5qV66WzNf5.OOTPFjgCEePpBz07GQUc3B0qlKMNzJd8W', '30123123', 1, 'ejemplo@gmail.com', 'Diego', 'Compa', '0414-575-3363', 'habilitado', 'avatar_691a939b55ec7_Foto_de_Asesor_De_Ventas_De_Thunder_Net.jpg'),
(4, 'Simon', '$2y$10$bJfY45blf5qV66WzNf5.OOTPFjgCEePpBz07GQUc3B0qlKMNzJd8W', '29123123', 7, 'ejemplo@gmail.com', 'Simon Freitez', 'Cliente', '0414-000-0000', 'habilitado', NULL),
(5, 'edit', '$2y$10$w7nQw5p6Qw6nQw5p6Qw6nOQw5p6Qw6nQw5p6Qw6nQw5p6Qw6nQw6n', 'V-101', 3, 'e@test.com', 'Eva', 'Perez', '0412', 'inhabilitado', NULL),
(9, 'CasaLai', '$2y$10$KXRg/AUD.9Y7KubEvzy71e5dDR1GvGNy23XegAYwLjYWOBdcxzqx2', '30456789', 6, 'diego0510lopez@gmail.com', 'Casa', 'Lai', '0414-575-3363', 'habilitado', NULL),
(10, 'Gmujica', '$2y$10$iZNeKonr6qr.P109rwgEFOCc7Y.0E47sD/88YfB.Jyx6niGpf4CQi', '29958676', 2, 'fhhggjjkkkj@gmail.com', 'Gabriel', 'Mujica', '0424-678-8765', 'habilitado', NULL),
(11, 'edithu', '$2y$10$YfEtJDHi9CNZR1Xpx7J9Ze8CMx3g99o1dJ3h.RRZPXqlJjxWbT5Fi', '10844463', 3, 'urdavedith.pnfi@gmail.com', 'Edith', 'Urdaneta', '0416-747-4336', 'inhabilitado', NULL),
(16, 'Darckort', '$2y$10$1xavkBCftrr0QLclZTk77eduhFhvGa3uWiuCva2qHKMQ/otwoGYaa', '28406324', 6, 'darckortgame@gmail.com', 'Braynt de Jesus', 'Medina Bricno', '0426-150-4714', 'habilitado', 'avatar_68edb74fbaaa4_file_000000005b786246b5a28d3be60c28d6.png'),
(17, 'Juanlai', '$2y$10$NAPB.g70SJM0juLf9jTha.LbRejgTZFWD87GfYgATpp2k./KfciK2', '25.874.676', 4, 'juanlai@gmail.com', 'Juan', 'Lai', '0412-125-6985', 'habilitado', NULL),
(24, 'Susan', '$2y$10$L7kgXy339cVPwrHwr0UPweXkN5VK.LMZ9WqCMR3Nnrw0rAQc4yrHe', '12.313.313', 3, 'diego0510lopez@gmail.com', 'Susan', 'Lopez', '0414-575-3363', 'habilitado', NULL),
(25, 'DarckAlm', '$2y$10$dKPVEabWzH.7L9GAKBp6N.HDpRVNB25/sz0Z5VmGBkZFM0aj2rlfK', '10.101.010', 2, 'diego0510lo23@gmail.com', 'Bray', 'Med', '0414-575-3363', 'habilitado', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tbl_alertas`
--
ALTER TABLE `tbl_alertas`
  ADD PRIMARY KEY (`id_alerta`),
  ADD KEY `id_usuario` (`id_usuario`);

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
-- AUTO_INCREMENT de la tabla `tbl_alertas`
--
ALTER TABLE `tbl_alertas`
  MODIFY `id_alerta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_bitacora`
--
ALTER TABLE `tbl_bitacora`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2207;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10341;

--
-- AUTO_INCREMENT de la tabla `tbl_recuperar`
--
ALTER TABLE `tbl_recuperar`
  MODIFY `id_recuperar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_rol`
--
ALTER TABLE `tbl_rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tbl_alertas`
--
ALTER TABLE `tbl_alertas`
  ADD CONSTRAINT `tbl_alertas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

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
