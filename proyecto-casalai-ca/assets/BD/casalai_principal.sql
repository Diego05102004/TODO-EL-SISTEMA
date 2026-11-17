-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: casalai_principal
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `casalai_principal`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `casalai_principal` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `casalai_principal`;

--
-- Table structure for table `cat_cartucho_de_tinta`
--

DROP TABLE IF EXISTS `cat_cartucho_de_tinta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cat_cartucho_de_tinta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `cat_cartucho_de_tinta_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_cartucho_de_tinta`
--

LOCK TABLES `cat_cartucho_de_tinta` WRITE;
/*!40000 ALTER TABLE `cat_cartucho_de_tinta` DISABLE KEYS */;
INSERT INTO `cat_cartucho_de_tinta` VALUES (1,34,1004,'Multicolor',1000),(2,35,1005,'Multicolor',1000),(3,36,1006,'Multicolor',1500);
/*!40000 ALTER TABLE `cat_cartucho_de_tinta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cat_impresoras`
--

DROP TABLE IF EXISTS `cat_impresoras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cat_impresoras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `peso` float DEFAULT NULL,
  `alto` float DEFAULT NULL,
  `ancho` float DEFAULT NULL,
  `largo` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `cat_impresoras_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_impresoras`
--

LOCK TABLES `cat_impresoras` WRITE;
/*!40000 ALTER TABLE `cat_impresoras` DISABLE KEYS */;
INSERT INTO `cat_impresoras` VALUES (1,28,10,10,10,10),(2,29,20,20,20,20),(3,30,30,15,15,15);
/*!40000 ALTER TABLE `cat_impresoras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cat_otros`
--

DROP TABLE IF EXISTS `cat_otros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cat_otros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `cat_otros_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_otros`
--

LOCK TABLES `cat_otros` WRITE;
/*!40000 ALTER TABLE `cat_otros` DISABLE KEYS */;
INSERT INTO `cat_otros` VALUES (1,40,NULL),(2,41,NULL);
/*!40000 ALTER TABLE `cat_otros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cat_protector_de_voltaje`
--

DROP TABLE IF EXISTS `cat_protector_de_voltaje`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cat_protector_de_voltaje` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `voltaje_de_entrada` varchar(50) DEFAULT NULL,
  `voltaje_de_salida` varchar(50) DEFAULT NULL,
  `tomas` int(11) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `cat_protector_de_voltaje_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_protector_de_voltaje`
--

LOCK TABLES `cat_protector_de_voltaje` WRITE;
/*!40000 ALTER TABLE `cat_protector_de_voltaje` DISABLE KEYS */;
INSERT INTO `cat_protector_de_voltaje` VALUES (1,37,'1200W','800W',3,3),(2,38,'1500W','1000W',1,5),(3,39,'3200W','1800W',6,12);
/*!40000 ALTER TABLE `cat_protector_de_voltaje` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cat_tintas`
--

DROP TABLE IF EXISTS `cat_tintas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cat_tintas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `volumen` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `cat_tintas_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cat_tintas`
--

LOCK TABLES `cat_tintas` WRITE;
/*!40000 ALTER TABLE `cat_tintas` DISABLE KEYS */;
INSERT INTO `cat_tintas` VALUES (1,31,1001,'Multicolor','Liquidas',100),(2,32,1002,'Multicolor','Liquidas',450),(3,33,1003,'Multicolor','Inyeccion',750);
/*!40000 ALTER TABLE `cat_tintas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dolar_cache`
--

DROP TABLE IF EXISTS `dolar_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dolar_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `precio` decimal(10,4) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7081 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dolar_cache`
--

LOCK TABLES `dolar_cache` WRITE;
/*!40000 ALTER TABLE `dolar_cache` DISABLE KEYS */;
INSERT INTO `dolar_cache` VALUES (7079,226.1305,'2025-11-04 22:02:43'),(7080,915.7476,'2026-11-17 05:37:49');
/*!40000 ALTER TABLE `dolar_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_carrito`
--

DROP TABLE IF EXISTS `tbl_carrito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_carrito` (
  `id_carrito` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_carrito`),
  KEY `id_cliente` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_carrito`
--

LOCK TABLES `tbl_carrito` WRITE;
/*!40000 ALTER TABLE `tbl_carrito` DISABLE KEYS */;
INSERT INTO `tbl_carrito` VALUES (10,11,'2025-07-11 16:50:50'),(11,3,'2025-09-02 01:21:08');
/*!40000 ALTER TABLE `tbl_carrito` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_carritodetalle`
--

DROP TABLE IF EXISTS `tbl_carritodetalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_carritodetalle` (
  `id_carrito_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_carrito` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` float NOT NULL DEFAULT 0,
  `id_combo` int(11) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL,
  PRIMARY KEY (`id_carrito_detalle`),
  KEY `id_carrito` (`id_carrito`),
  KEY `id_producto` (`id_producto`),
  KEY `id_combo` (`id_combo`),
  CONSTRAINT `tbl_carritodetalle_ibfk_1` FOREIGN KEY (`id_carrito`) REFERENCES `tbl_carrito` (`id_carrito`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tbl_carritodetalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_carritodetalle`
--

LOCK TABLES `tbl_carritodetalle` WRITE;
/*!40000 ALTER TABLE `tbl_carritodetalle` DISABLE KEYS */;
INSERT INTO `tbl_carritodetalle` VALUES (43,11,34,1,7.16,NULL,'pendiente'),(44,11,40,1,31.52,NULL,'pendiente'),(45,11,32,1,8,NULL,'pendiente'),(46,11,41,1,57.32,NULL,'pendiente'),(47,11,30,1,10.75,NULL,'pendiente'),(55,10,32,1,8,NULL,'pendiente');
/*!40000 ALTER TABLE `tbl_carritodetalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_categoria`
--

DROP TABLE IF EXISTS `tbl_categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_categoria` (
  `id_categoria` int(2) NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(20) NOT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_categoria`
--

LOCK TABLES `tbl_categoria` WRITE;
/*!40000 ALTER TABLE `tbl_categoria` DISABLE KEYS */;
INSERT INTO `tbl_categoria` VALUES (11,'Impresoras'),(12,'Tintas'),(13,'Cartucho de Tinta'),(14,'Protector de Voltaje'),(15,'Otros');
/*!40000 ALTER TABLE `tbl_categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_clientes`
--

DROP TABLE IF EXISTS `tbl_clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_clientes` (
  `id_clientes` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(600) DEFAULT NULL,
  `cedula` varchar(10) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(600) DEFAULT NULL,
  `correo` varchar(600) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_clientes`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_clientes`
--

LOCK TABLES `tbl_clientes` WRITE;
/*!40000 ALTER TABLE `tbl_clientes` DISABLE KEYS */;
INSERT INTO `tbl_clientes` VALUES (10,'Gabriel Mujica','29958676','mi casa','0424-678-8765','fhhggjjkkkj@gmail.com',1),(11,'Edith Urdaneta','10844463','Los Horcones','0416-747-4336','urdavedith.pnfi@gmail.com',1),(12,'Diego Lopez','31766917','Venezuela estado Zulia\r\nMaracaibo','0414-575-3363','diego0510lopez@gmail.com',1),(13,'Diego Lopez','5322432','','0414-575-3363','diego0510lopez@gmail.com',1),(14,'Juan Lai','25874668','','0412-125-6985','juanlai@gmail.com',1);
/*!40000 ALTER TABLE `tbl_clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_combo`
--

DROP TABLE IF EXISTS `tbl_combo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_combo` (
  `id_combo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_combo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `descuento` float NOT NULL DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_combo`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_combo`
--

LOCK TABLES `tbl_combo` WRITE;
/*!40000 ALTER TABLE `tbl_combo` DISABLE KEYS */;
INSERT INTO `tbl_combo` VALUES (14,'COMBO ANIVERSARIO','TODO PARA IMPRIMIR','2025-09-02 01:59:53',0,1);
/*!40000 ALTER TABLE `tbl_combo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_combo_detalle`
--

DROP TABLE IF EXISTS `tbl_combo_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_combo_detalle` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_combo` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_detalle`),
  KEY `id_combo` (`id_combo`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `tbl_combo_detalle_ibfk_1` FOREIGN KEY (`id_combo`) REFERENCES `tbl_combo` (`id_combo`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tbl_combo_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_combo_detalle`
--

LOCK TABLES `tbl_combo_detalle` WRITE;
/*!40000 ALTER TABLE `tbl_combo_detalle` DISABLE KEYS */;
INSERT INTO `tbl_combo_detalle` VALUES (15,14,32,1,8),(16,14,41,1,57.32),(17,14,30,1,10.75);
/*!40000 ALTER TABLE `tbl_combo_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_cuentas`
--

DROP TABLE IF EXISTS `tbl_cuentas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_cuentas` (
  `id_cuenta` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_banco` varchar(500) NOT NULL,
  `numero_cuenta` varchar(25) DEFAULT NULL,
  `rif_cuenta` varchar(15) NOT NULL,
  `telefono_cuenta` varchar(500) DEFAULT NULL,
  `correo_cuenta` varchar(500) DEFAULT NULL,
  `metodos` set('Pago Movil','Transferencia','Zelle','Efectivo','Efectivo $') NOT NULL,
  `estado` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado',
  PRIMARY KEY (`id_cuenta`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_cuentas`
--

LOCK TABLES `tbl_cuentas` WRITE;
/*!40000 ALTER TABLE `tbl_cuentas` DISABLE KEYS */;
INSERT INTO `tbl_cuentas` VALUES (0,'Caja en Bs',NULL,'J406452157',NULL,NULL,'Efectivo','habilitado'),(1,'Caja en $',NULL,'J406452157',NULL,NULL,'Efectivo $','habilitado'),(8,'Banesco','1234567890','0123456789','0990812808','ejemplo@gmail.com','Transferencia','habilitado'),(9,'Bancamiga','1234-5678-90-5857575765','J-01234567-8','0990-812-8088','ejemplo@gmail.com68','Pago Movil','habilitado'),(10,'Venezuela','87654321','0123456789','04141580151','ejemplo@gmail.com','Pago Movil,Transferencia','habilitado'),(24,'Mercantil','1247-8624-44-4444355559','J-12345678-9','0414-158-0151','diego0510lopez@gmail.com','Pago Movil,Transferencia','habilitado'),(25,'Mercantil','1247-8624-44-4444355389','J-12345678-9','0414-158-0151','diego0510lopez@gmail.com','Pago Movil,Transferencia','habilitado'),(26,'Mercantil','1247-8624-56-3452253234','J-12345678-9','0414-158-0151','diego0510lopez@gmail.com','Pago Movil,Transferencia','habilitado'),(27,'Mercantil','1247-8624-44-4444355554','J-12345678-9','2414-124-3241','diego0510lopez@gmail.com','Pago Movil,Transferencia','habilitado'),(28,'Mercantil','1247-8624-44-4444355550','J-12345678-9','2414-124-3241','diego0510lopez@gmail.com','Pago Movil,Transferencia','habilitado'),(29,'Mercantil','1247-8624-44-4444355504','J-12345678-9','2414-124-3241','diego0510lopez@gmail.com','Pago Movil,Transferencia','habilitado'),(30,'Mercantil','1247-8624-44-4444359550','J-12345678-9','2414-124-3241','diego0510lopez@gmail.com','Pago Movil,Transferencia','habilitado'),(31,'Zelle','1247-8624-56-0876896596','J-12345678-9','0414-158-0151','diego0510lopez@gmail.com','Zelle','habilitado'),(34,'BNC','1247862','143123423442','24141243241','EJEMPLO@GMAIL.COM','Pago Movil,Transferencia','habilitado');
/*!40000 ALTER TABLE `tbl_cuentas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_despacho_detalle`
--

DROP TABLE IF EXISTS `tbl_despacho_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_despacho_detalle` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_despacho` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_despacho` (`id_despacho`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `tbl_despacho_detalle_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `tbl_despachos` (`id_despachos`) ON DELETE CASCADE,
  CONSTRAINT `tbl_despacho_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_despacho_detalle`
--

LOCK TABLES `tbl_despacho_detalle` WRITE;
/*!40000 ALTER TABLE `tbl_despacho_detalle` DISABLE KEYS */;
INSERT INTO `tbl_despacho_detalle` VALUES (2,3,31,1),(3,3,32,1),(4,4,28,1),(5,5,41,1),(7,7,41,1),(8,8,40,1),(9,9,41,1),(11,11,41,1),(12,12,41,1),(13,13,41,1),(14,14,28,1),(15,15,28,1),(16,16,31,1),(17,17,31,1),(18,18,31,1),(19,19,31,1),(20,20,31,1),(21,21,31,1),(22,22,31,1),(23,23,31,1),(24,24,31,1),(25,25,31,1),(26,26,41,1),(32,32,41,1),(34,34,41,1);
/*!40000 ALTER TABLE `tbl_despacho_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_despachos`
--

DROP TABLE IF EXISTS `tbl_despachos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_despachos` (
  `id_despachos` int(11) NOT NULL AUTO_INCREMENT,
  `id_clientes` int(11) NOT NULL,
  `fecha_despacho` date NOT NULL,
  `tipocompra` varchar(10) NOT NULL,
  `estado` enum('Por Despachar','Despachado') NOT NULL DEFAULT 'Por Despachar',
  `activo` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_despachos`),
  KEY `id_clientes` (`id_clientes`),
  KEY `idx_despachos_estado_fecha` (`estado`,`fecha_despacho`),
  CONSTRAINT `tbl_despachos_ibfk_1` FOREIGN KEY (`id_clientes`) REFERENCES `tbl_clientes` (`id_clientes`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_despachos`
--

LOCK TABLES `tbl_despachos` WRITE;
/*!40000 ALTER TABLE `tbl_despachos` DISABLE KEYS */;
INSERT INTO `tbl_despachos` VALUES (3,12,'2025-07-23','Presencial','Despachado',1),(4,12,'2025-09-01','Online','Por Despachar',1),(5,12,'2025-09-01','Presencial','Por Despachar',1),(7,12,'2025-10-12','Presencial','Por Despachar',0),(8,12,'2025-10-12','Presencial','Por Despachar',0),(9,12,'2025-10-12','Presencial','Por Despachar',0),(11,12,'2025-10-12','Presencial','Por Despachar',0),(12,12,'2025-10-13','Presencial','Por Despachar',0),(13,12,'2025-10-13','Presencial','Por Despachar',0),(14,12,'2025-10-13','Presencial','Por Despachar',0),(15,12,'2025-10-13','Presencial','Por Despachar',0),(16,12,'2025-10-13','Presencial','Por Despachar',0),(17,12,'2025-10-13','Presencial','Por Despachar',0),(18,12,'2025-10-13','Presencial','Por Despachar',0),(19,12,'2025-10-13','Presencial','Por Despachar',0),(20,12,'2025-10-13','Presencial','Por Despachar',0),(21,12,'2025-10-13','Presencial','Por Despachar',0),(22,12,'2025-10-13','Presencial','Por Despachar',0),(23,12,'2025-10-13','Presencial','Por Despachar',0),(24,12,'2025-10-13','Presencial','Por Despachar',0),(25,12,'2025-10-13','Presencial','Por Despachar',0),(26,12,'2025-10-13','Presencial','Por Despachar',0),(32,12,'2025-10-13','Presencial','Por Despachar',0),(34,12,'2025-10-13','Presencial','Por Despachar',0);
/*!40000 ALTER TABLE `tbl_despachos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_detalle_recepcion_productos`
--

DROP TABLE IF EXISTS `tbl_detalle_recepcion_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_detalle_recepcion_productos` (
  `id_detalle_recepcion_productos` int(11) NOT NULL AUTO_INCREMENT,
  `id_recepcion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `costo` decimal(12,2) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detalle_recepcion_productos`),
  KEY `fk_detalle_recepcion` (`id_recepcion`),
  KEY `fk_detalle_producto` (`id_producto`),
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`),
  CONSTRAINT `fk_detalle_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `tbl_recepcion_productos` (`id_recepcion`),
  CONSTRAINT `tbl_detalles_recepcion_productos` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_detalle_recepcion_productos`
--

LOCK TABLES `tbl_detalle_recepcion_productos` WRITE;
/*!40000 ALTER TABLE `tbl_detalle_recepcion_productos` DISABLE KEYS */;
INSERT INTO `tbl_detalle_recepcion_productos` VALUES (12,10,33,20.00,2),(13,10,32,30.00,4),(14,11,32,2500.00,1),(15,11,29,123.00,1),(16,11,28,1.00,1),(17,12,28,295.00,2),(18,12,29,135.00,6),(19,12,30,22.00,15),(20,12,31,16.00,20),(21,12,32,120.00,3),(22,12,33,42.00,12),(23,12,36,14.50,10),(24,12,35,10.50,15),(25,12,34,8.00,30),(26,12,37,6.60,25),(27,12,38,35.00,8),(28,12,39,12.50,12),(29,12,40,20.00,10),(30,12,41,45.00,8),(31,13,28,280.00,3),(32,13,29,150.00,4),(33,13,30,25.00,10),(34,13,31,18.50,12),(35,13,32,115.00,2),(36,13,33,45.00,8),(37,13,36,16.00,15),(38,13,35,12.00,20),(39,13,34,9.50,25),(40,13,37,7.00,15),(41,13,38,32.00,6),(42,13,39,14.00,10),(43,13,40,22.00,8),(44,13,41,48.00,5),(45,14,28,295.00,2),(46,14,29,145.00,6),(47,14,37,6.50,20),(48,14,39,13.50,12),(49,14,41,50.00,4),(50,14,33,42.00,10),(51,14,40,24.00,6),(52,15,28,280.00,3),(53,15,29,150.00,4),(54,15,37,7.00,15),(55,15,39,14.00,10),(56,15,41,48.00,5),(57,15,33,45.00,8),(58,15,40,22.00,8),(59,16,38,32.00,6),(60,16,37,7.00,15),(61,16,36,16.00,15),(62,16,34,9.50,25),(63,16,35,12.00,20),(64,17,28,250.00,15);
/*!40000 ALTER TABLE `tbl_detalle_recepcion_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_detalles_pago`
--

DROP TABLE IF EXISTS `tbl_detalles_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_detalles_pago` (
  `id_detalles` int(11) NOT NULL AUTO_INCREMENT,
  `id_factura` int(11) NOT NULL,
  `id_cuenta` int(11) NOT NULL,
  `observaciones` varchar(200) NOT NULL,
  `referencia` varchar(30) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `monto` float(8,2) NOT NULL,
  `comprobante` varchar(255) NOT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'En Proceso',
  PRIMARY KEY (`id_detalles`),
  KEY `tbl_detalles_pago` (`id_factura`),
  KEY `tbl_detalles_pago1` (`id_cuenta`),
  KEY `idx_pagos_referencia` (`referencia`),
  CONSTRAINT `fk_id_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `tbl_cuentas` (`id_cuenta`),
  CONSTRAINT `fk_id_factura` FOREIGN KEY (`id_factura`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_detalles_pago`
--

LOCK TABLES `tbl_detalles_pago` WRITE;
/*!40000 ALTER TABLE `tbl_detalles_pago` DISABLE KEYS */;
INSERT INTO `tbl_detalles_pago` VALUES (43,33,29,'','2423452','2025-08-28','Pago Movil',170000.00,'assets/img/comprobantes/comprobante_1756429769_0.jpg','En Proceso'),(44,33,25,'','123456','2025-08-28','Transferencia',615.70,'assets/img/comprobantes/comprobante_1756428648_0.jpg','En Proceso'),(45,35,25,'','1253257578','2025-09-01','Pago Movil',148444.00,'assets/img/comprobantes/1756772165_imagen_2025-09-01_201134160.png','Pago Procesado'),(46,36,27,'','098098','2025-09-01','Transferencia',448.40,'assets/img/comprobantes/1756773692_Imagen_de_WhatsApp_2025-08-06_a_las_20.29.40_9e358cd4-removebg-previ','Pago Procesado'),(47,33,30,'','998787','2025-10-12','Pago Movil',226488.95,'assets/img/comprobantes/comprobante_1760307198_0.png','En Proceso'),(48,33,10,'','0979633','2025-10-12','Pago Movil',226488.95,'assets/img/comprobantes/comprobante_1760308540_0.png','En Proceso'),(49,33,28,'','754353','2025-10-12','Pago Movil',226488.95,'assets/img/comprobantes/comprobante_1760308787_0.png','En Proceso'),(50,60,30,'','884675465463','2025-10-13','Pago Movil',686.41,'assets/img/comprobantes/1760406723_Grafico_Despachos_Estado.png','En Proceso'),(52,62,30,'','8747857544','2025-10-13','Pago Movil',686.00,'assets/img/comprobantes/1760407024_Grafico_Despachos_Estado.png','En Proceso');
/*!40000 ALTER TABLE `tbl_detalles_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_factura_detalle`
--

DROP TABLE IF EXISTS `tbl_factura_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_factura_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` float NOT NULL DEFAULT 0,
  `id_combo` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  KEY `tbl_factura_detalle` (`id_producto`),
  KEY `id_combo` (`id_combo`),
  CONSTRAINT `factura_detalle_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE,
  CONSTRAINT `tbl_factura_detalle` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_factura_detalle`
--

LOCK TABLES `tbl_factura_detalle` WRITE;
/*!40000 ALTER TABLE `tbl_factura_detalle` DISABLE KEYS */;
INSERT INTO `tbl_factura_detalle` VALUES (21,33,28,1,372.66,NULL),(22,35,28,1,372.66,NULL),(23,36,41,1,57.32,NULL),(24,37,32,3,8,NULL),(25,37,41,1,57.32,NULL),(26,37,30,1,10.75,NULL),(27,38,28,1,372.66,NULL),(28,38,32,1,8,NULL),(29,38,41,1,57.32,NULL),(30,38,30,1,10.75,NULL),(31,39,41,1,57.32,NULL),(32,40,41,1,57.32,NULL),(33,41,41,1,57.32,NULL),(34,42,28,1,372.66,NULL),(35,43,28,1,372.66,NULL),(36,44,31,1,7.16,NULL),(37,45,31,1,7.16,NULL),(38,46,31,1,7.16,NULL),(39,47,31,1,7.16,NULL),(40,48,31,1,7.16,NULL),(41,49,31,1,7.16,NULL),(42,50,31,1,7.16,NULL),(43,51,31,1,7.16,NULL),(44,52,31,1,7.16,NULL),(45,53,31,1,7.16,NULL),(46,54,41,1,57.32,NULL),(52,60,41,1,57.32,NULL),(54,62,41,1,57.32,NULL);
/*!40000 ALTER TABLE `tbl_factura_detalle` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_descontar_stock_detalle_insert` AFTER INSERT ON `tbl_factura_detalle` FOR EACH ROW BEGIN
    DECLARE v_estatus VARCHAR(50);
    DECLARE v_stock_actual INT;
    DECLARE v_mensaje_error VARCHAR(255);

    -- 1. Obtener el estatus de la factura a la que pertenece este detalle
    SELECT estatus INTO v_estatus 
    FROM tbl_facturas 
    WHERE id_factura = NEW.factura_id;

    -- 2. Si la factura está "Pagada en Oficina", procesamos el inventario
    IF v_estatus = 'Pagada en Oficina' THEN
        
        -- Obtener el stock actual del producto que se está intentando insertar
        SELECT stock INTO v_stock_actual 
        FROM tbl_productos 
        WHERE id_producto = NEW.id_producto;

        -- 3. Validar si hay suficiente stock para la cantidad solicitada
        IF v_stock_actual < NEW.cantidad THEN
            SET v_mensaje_error = CONCAT('Error: Stock insuficiente para el producto ID ', NEW.id_producto, '. Disponible: ', v_stock_actual, ', Solicitado: ', NEW.cantidad);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje_error;
        ELSE
            -- 4. Si hay stock, se descuenta de inmediato
            UPDATE tbl_productos 
            SET stock = stock - NEW.cantidad
            WHERE id_producto = NEW.id_producto;
        END IF;

    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_descontar_stock_online` AFTER INSERT ON `tbl_factura_detalle` FOR EACH ROW BEGIN
    DECLARE v_estatus VARCHAR(50);
    DECLARE v_stock_actual INT;
    DECLARE v_mensaje_error VARCHAR(255);

    -- 1. Obtener el estatus de la factura a la que pertenece este detalle
    SELECT estatus INTO v_estatus 
    FROM tbl_facturas 
    WHERE id_factura = NEW.factura_id;

    -- 2. Si la factura está en "Borrador", procesamos el inventario
    IF v_estatus = 'Borrador' THEN
        
        -- Obtener el stock actual del producto que se está intentando insertar
        SELECT stock INTO v_stock_actual 
        FROM tbl_productos 
        WHERE id_producto = NEW.id_producto;

        -- 3. Validar si hay suficiente stock para la cantidad solicitada
        IF v_stock_actual < NEW.cantidad THEN
            SET v_mensaje_error = CONCAT('Error: Stock insuficiente para el producto ID ', NEW.id_producto, '. Disponible: ', v_stock_actual, ', Solicitado: ', NEW.cantidad);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje_error;
        ELSE
            -- 4. Si hay stock, se descuenta de inmediato
            UPDATE tbl_productos 
            SET stock = stock - NEW.cantidad
            WHERE id_producto = NEW.id_producto;
        END IF;

    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `tbl_facturas`
--

DROP TABLE IF EXISTS `tbl_facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_facturas` (
  `id_factura` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `cliente` int(11) NOT NULL,
  `descuento` int(3) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Borrador',
  PRIMARY KEY (`id_factura`),
  KEY `cliente` (`cliente`),
  KEY `idx_facturas_estatus_fecha` (`estatus`,`fecha`),
  CONSTRAINT `tbl_facturas_ibfk_1` FOREIGN KEY (`cliente`) REFERENCES `tbl_clientes` (`id_clientes`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_facturas`
--

LOCK TABLES `tbl_facturas` WRITE;
/*!40000 ALTER TABLE `tbl_facturas` DISABLE KEYS */;
INSERT INTO `tbl_facturas` VALUES (33,'2025-07-11',11,0,'En Proceso'),(34,'2025-07-22',10,0,'Borrador'),(35,'2025-09-01',12,0,'Pagada'),(36,'2025-09-01',12,0,'Pagada'),(37,'2025-10-12',11,0,'Borrador'),(38,'2025-10-12',11,0,'Borrador'),(39,'2025-10-12',12,0,'Borrador'),(40,'2025-10-13',12,0,'Borrador'),(41,'2025-10-13',12,0,'Borrador'),(42,'2025-10-13',12,0,'Borrador'),(43,'2025-10-13',12,0,'Borrador'),(44,'2025-10-13',12,0,'Borrador'),(45,'2025-10-13',12,0,'Borrador'),(46,'2025-10-13',12,0,'Borrador'),(47,'2025-10-13',12,0,'Borrador'),(48,'2025-10-13',12,0,'Borrador'),(49,'2025-10-13',12,0,'Borrador'),(50,'2025-10-13',12,0,'Borrador'),(51,'2025-10-13',12,0,'Borrador'),(52,'2025-10-13',12,0,'Borrador'),(53,'2025-10-13',12,0,'Borrador'),(54,'2025-10-13',12,0,'Borrador'),(60,'2025-10-13',12,0,'Borrador'),(62,'2025-10-13',12,0,'Borrador');
/*!40000 ALTER TABLE `tbl_facturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_ingresos_egresos`
--

DROP TABLE IF EXISTS `tbl_ingresos_egresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_ingresos_egresos` (
  `id_finanzas` int(11) NOT NULL AUTO_INCREMENT,
  `id_despacho` int(11) DEFAULT NULL,
  `id_detalle_recepcion_productos` int(11) DEFAULT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `monto` float(12,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha` date NOT NULL,
  `estado` int(1) NOT NULL,
  PRIMARY KEY (`id_finanzas`),
  KEY `id_despacho` (`id_despacho`,`id_detalle_recepcion_productos`),
  KEY `id_detalle_recepcion_productos` (`id_detalle_recepcion_productos`),
  KEY `idx_finanzas_fecha` (`fecha`),
  CONSTRAINT `tbl_ingresos_egresos_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `tbl_despachos` (`id_despachos`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tbl_ingresos_egresos_ibfk_2` FOREIGN KEY (`id_detalle_recepcion_productos`) REFERENCES `tbl_detalle_recepcion_productos` (`id_detalle_recepcion_productos`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_ingresos_egresos`
--

LOCK TABLES `tbl_ingresos_egresos` WRITE;
/*!40000 ALTER TABLE `tbl_ingresos_egresos` DISABLE KEYS */;
INSERT INTO `tbl_ingresos_egresos` VALUES (8,NULL,12,'egreso',40.00,'Compra: ImpriColor (x2)','2025-07-22',1),(9,NULL,13,'egreso',120.00,'Compra: Tinta Arcoiris (x4)','2025-07-22',1),(10,3,NULL,'ingreso',18.00,'Venta: Colormedia (x1), Tinta Arcoiris (x1)','2025-07-23',1),(11,NULL,14,'egreso',2500.00,'Compra: Tinta Arcoiris (x1)','2025-07-27',1),(12,NULL,15,'egreso',123.00,'Compra: Impresora Maxi (x1)','2025-07-27',1),(13,NULL,16,'egreso',1.00,'Compra: Impresora Super (x1)','2025-07-27',1),(14,4,NULL,'ingreso',1000.00,'Venta: Impresora Super (x1)','2025-09-01',1),(15,5,NULL,'ingreso',3.00,'Venta: Rema de Papel  (x1)','2025-09-01',1),(16,NULL,17,'egreso',590.00,'Compra: Impresora Digital (x2)','2026-11-17',1),(17,NULL,18,'egreso',810.00,'Compra: Botellas de Tinta Epson (x6)','2026-11-17',1),(18,NULL,19,'egreso',330.00,'Compra: Cutter 360 (x15)','2026-11-17',1),(19,NULL,20,'egreso',320.00,'Compra: Pegamento para Papel (x20)','2026-11-17',1),(20,NULL,21,'egreso',360.00,'Compra: Cortadoras de papel (x3)','2026-11-17',1),(21,NULL,22,'egreso',504.00,'Compra: Resma de Papel (x12)','2026-11-17',1),(22,NULL,23,'egreso',145.00,'Compra: Pendrive 128GB (x10)','2026-11-17',1),(23,NULL,24,'egreso',157.50,'Compra: Tarjeta microSD (x15)','2026-11-17',1),(24,NULL,25,'egreso',240.00,'Compra: Pendrive 64GB (x30)','2026-11-17',1),(25,NULL,26,'egreso',165.00,'Compra: Cable USB (x25)','2026-11-17',1),(26,NULL,27,'egreso',280.00,'Compra: Auriculares Bluetooth (x8)','2026-11-17',1),(27,NULL,28,'egreso',150.00,'Compra: Cinta de Impresora (x12)','2026-11-17',1),(28,NULL,29,'egreso',200.00,'Compra: Botellas de Tinta HP (x10)','2026-11-17',1),(29,NULL,30,'egreso',360.00,'Compra: Botellas de Tinta Canon (x8)','2026-11-17',1),(30,NULL,31,'egreso',840.00,'Compra: Impresora Digital (x3)','2026-11-17',1),(31,NULL,32,'egreso',600.00,'Compra: Botellas de Tinta Epson (x4)','2026-11-17',1),(32,NULL,33,'egreso',250.00,'Compra: Cutter 360 (x10)','2026-11-17',1),(33,NULL,34,'egreso',222.00,'Compra: Pegamento para Papel (x12)','2026-11-17',1),(34,NULL,35,'egreso',230.00,'Compra: Cortadoras de papel (x2)','2026-11-17',1),(35,NULL,36,'egreso',360.00,'Compra: Resma de Papel (x8)','2026-11-17',1),(36,NULL,37,'egreso',240.00,'Compra: Pendrive 128GB (x15)','2026-11-17',1),(37,NULL,38,'egreso',240.00,'Compra: Tarjeta microSD (x20)','2026-11-17',1),(38,NULL,39,'egreso',237.50,'Compra: Pendrive 64GB (x25)','2026-11-17',1),(39,NULL,40,'egreso',105.00,'Compra: Cable USB (x15)','2026-11-17',1),(40,NULL,41,'egreso',192.00,'Compra: Auriculares Bluetooth (x6)','2026-11-17',1),(41,NULL,42,'egreso',140.00,'Compra: Cinta de Impresora (x10)','2026-11-17',1),(42,NULL,43,'egreso',176.00,'Compra: Botellas de Tinta HP (x8)','2026-11-17',1),(43,NULL,44,'egreso',240.00,'Compra: Botellas de Tinta Canon (x5)','2026-11-17',1),(44,NULL,45,'egreso',590.00,'Compra: Impresora Digital (x2)','2026-11-17',1),(45,NULL,46,'egreso',870.00,'Compra: Botellas de Tinta Epson (x6)','2026-11-17',1),(46,NULL,47,'egreso',130.00,'Compra: Cable USB (x20)','2026-11-17',1),(47,NULL,48,'egreso',162.00,'Compra: Cinta de Impresora (x12)','2026-11-17',1),(48,NULL,49,'egreso',200.00,'Compra: Botellas de Tinta Canon (x4)','2026-11-17',1),(49,NULL,50,'egreso',420.00,'Compra: Resma de Papel (x10)','2026-11-17',1),(50,NULL,51,'egreso',144.00,'Compra: Botellas de Tinta HP (x6)','2026-11-17',1),(51,NULL,52,'egreso',840.00,'Compra: Impresora Digital (x3)','2026-11-17',1),(52,NULL,53,'egreso',600.00,'Compra: Botellas de Tinta Epson (x4)','2026-11-17',1),(53,NULL,54,'egreso',105.00,'Compra: Cable USB (x15)','2026-11-17',1),(54,NULL,55,'egreso',140.00,'Compra: Cinta de Impresora (x10)','2026-11-17',1),(55,NULL,56,'egreso',240.00,'Compra: Botellas de Tinta Canon (x5)','2026-11-17',1),(56,NULL,57,'egreso',360.00,'Compra: Resma de Papel (x8)','2026-11-17',1),(57,NULL,58,'egreso',176.00,'Compra: Botellas de Tinta HP (x8)','2026-11-17',1),(58,NULL,59,'egreso',192.00,'Compra: Auriculares Bluetooth (x6)','2026-11-17',1),(59,NULL,60,'egreso',105.00,'Compra: Cable USB (x15)','2026-11-17',1),(60,NULL,61,'egreso',240.00,'Compra: Pendrive 128GB (x15)','2026-11-17',1),(61,NULL,62,'egreso',237.50,'Compra: Pendrive 64GB (x25)','2026-11-17',1),(62,NULL,63,'egreso',240.00,'Compra: Tarjeta microSD (x20)','2026-11-17',1),(63,NULL,64,'egreso',3750.00,'Compra: Impresora Digital (x15)','2026-11-17',1);
/*!40000 ALTER TABLE `tbl_ingresos_egresos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_marcas`
--

DROP TABLE IF EXISTS `tbl_marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_marcas` (
  `id_marca` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_marca` varchar(25) NOT NULL,
  PRIMARY KEY (`id_marca`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_marcas`
--

LOCK TABLES `tbl_marcas` WRITE;
/*!40000 ALTER TABLE `tbl_marcas` DISABLE KEYS */;
INSERT INTO `tbl_marcas` VALUES (1,'Epson'),(2,'HP'),(3,'Canon'),(4,'Inktec'),(5,'TexPrint'),(6,'Sawgrass'),(7,'Cosmos Ink'),(8,'Azon'),(9,'Sublimagic'),(10,'Brother'),(11,'Forza'),(12,'Tripp Lite'),(13,'CDP'),(14,'Koblenz'),(15,'Redmi'),(16,'Simbi'),(17,'Kingston');
/*!40000 ALTER TABLE `tbl_marcas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_modelos`
--

DROP TABLE IF EXISTS `tbl_modelos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_modelos` (
  `id_modelo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_modelo` varchar(25) NOT NULL,
  `id_marca` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_modelo`),
  KEY `fk_modelo_marca` (`id_marca`),
  CONSTRAINT `fk_modelo_marca` FOREIGN KEY (`id_marca`) REFERENCES `tbl_marcas` (`id_marca`),
  CONSTRAINT `modelo_ibfk_1` FOREIGN KEY (`id_marca`) REFERENCES `tbl_marcas` (`id_marca`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_modelos`
--

LOCK TABLES `tbl_modelos` WRITE;
/*!40000 ALTER TABLE `tbl_modelos` DISABLE KEYS */;
INSERT INTO `tbl_modelos` VALUES (1,'L32508',1),(2,'L32106',1),(3,'L8055',1),(4,'L18001',1),(5,'L13001',1),(6,'F170911',2),(7,'F5709',2),(8,'Smart Tank 515',2),(9,'DeskJet 2775',2),(10,'LaserJet Pro M404dn',2),(11,'PIXMA G3110',3),(12,'PIXMA G6010',3),(13,'i-SENSYS MF445dw',3),(14,'Sublinova',4),(15,'SubliJet',6),(16,'L3250',1),(17,'L3210',1),(18,'L805',1),(19,'L1800',1),(20,'L1300',1),(21,'F170',1),(22,'F570',1),(23,'Buds 6 Lite',15),(24,'S0156312',1),(25,'544',1),(26,'GT52',2),(27,'Pegamento',16),(28,'64GB',17),(29,'128GB',17),(30,'Office20',2),(31,'Sublime',8),(32,'Durabrite',1),(33,'Innobella',10),(34,'ChromaLife 100+',3),(35,'T664 ',1),(36,'T673 ',1),(37,'T774',1),(38,'664 ',2),(39,'662 ',2),(40,'680 ',2),(41,'955 ',2),(42,'950',2),(43,'PG-145 ',3),(44,'CL-146 ',3),(45,'GI-190',3),(46,'FVR-1211',11),(47,'FVR-2202',11),(48,'LR2000',12),(49,'AVR750U',12),(50,'R2-1200 ',13),(51,'UPS 600VA',13),(52,'1000VA',13),(53,'AVR-1000',14),(54,'520 Joules',14),(55,'GT52',3),(78,'520 Joulesj',3);
/*!40000 ALTER TABLE `tbl_modelos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_modulos`
--

DROP TABLE IF EXISTS `tbl_modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_modulos` (
  `id_modulo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_modulo` varchar(50) NOT NULL,
  PRIMARY KEY (`id_modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_modulos`
--

LOCK TABLES `tbl_modulos` WRITE;
/*!40000 ALTER TABLE `tbl_modulos` DISABLE KEYS */;
INSERT INTO `tbl_modulos` VALUES (1,'Usuario'),(2,'Recepcion'),(3,'Despacho'),(4,'Marcas'),(5,'Modelos'),(6,'Productos'),(7,'Categorias'),(8,'Proveedores'),(9,'Clientes'),(10,'Catalogo'),(11,'Carrito'),(12,'Pasarela'),(13,'Prefactura'),(14,'Ordenes de despacho'),(15,'Cuentas bancarias'),(16,'Finanzas'),(17,'Permisos'),(18,'Roles'),(19,'Bitacora'),(20,'Backup'),(21,'Venta Presencial');
/*!40000 ALTER TABLE `tbl_modulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_orden_despachos`
--

DROP TABLE IF EXISTS `tbl_orden_despachos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_orden_despachos` (
  `id_orden_despachos` int(11) NOT NULL AUTO_INCREMENT,
  `id_factura` int(11) NOT NULL,
  `cliente` varchar(50) NOT NULL,
  `fecha_despacho` date NOT NULL,
  `estado` enum('Por Entregar','Entregada') NOT NULL DEFAULT 'Por Entregar',
  `activo` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_orden_despachos`),
  KEY `id_factura` (`id_factura`),
  CONSTRAINT `tbl_orden_despachos_ibfk_1` FOREIGN KEY (`id_factura`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_orden_despachos`
--

LOCK TABLES `tbl_orden_despachos` WRITE;
/*!40000 ALTER TABLE `tbl_orden_despachos` DISABLE KEYS */;
INSERT INTO `tbl_orden_despachos` VALUES (3,33,'David Medina','2025-07-23','Por Entregar',1),(4,33,'David Medina','2025-07-24','Por Entregar',1),(5,33,'David Medina','2025-07-24','Por Entregar',1);
/*!40000 ALTER TABLE `tbl_orden_despachos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_productos`
--

DROP TABLE IF EXISTS `tbl_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `serial` varchar(20) NOT NULL,
  `nombre_producto` varchar(40) NOT NULL,
  `descripcion_producto` varchar(255) DEFAULT NULL,
  `id_modelo` int(11) DEFAULT NULL,
  `id_categoria` int(2) DEFAULT NULL,
  `stock` int(3) DEFAULT NULL,
  `stock_minimo` int(3) DEFAULT NULL,
  `stock_maximo` int(3) DEFAULT NULL,
  `clausula_garantia` varchar(150) NOT NULL,
  `precio` float(10,2) DEFAULT NULL,
  `estado` varchar(20) DEFAULT '1',
  `imagen` varchar(255) DEFAULT NULL COMMENT 'Ruta de la imagen del producto en formato IMGProductosproducto_X.jpeg donde X es el id_producto',
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `uq_productos_serial` (`serial`),
  KEY `fk_producto_categoria` (`id_categoria`),
  KEY `fk_producto_modelo` (`id_modelo`),
  KEY `idx_productos_nombre` (`nombre_producto`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `tbl_categoria` (`id_categoria`),
  CONSTRAINT `fk_producto_modelo` FOREIGN KEY (`id_modelo`) REFERENCES `tbl_modelos` (`id_modelo`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_modelo`) REFERENCES `tbl_modelos` (`id_modelo`),
  CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `tbl_categoria` (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_productos`
--

LOCK TABLES `tbl_productos` WRITE;
/*!40000 ALTER TABLE `tbl_productos` DISABLE KEYS */;
INSERT INTO `tbl_productos` VALUES (28,'0001','Impresora Digital','Impresora multifuncional imprime, copia, escanea y Wi‑Fi',16,11,50,10,100,'Garantia Valida hasta los 3 meses ',372.66,'habilitado','assets\\img\\productos\\producto_29.jpg'),(29,'0002','Botellas de Tinta Epson','Botellas de tinta, colores negro, azul, rosado y amarillo.',25,12,50,10,100,'Garantía para 1 mes',243.33,'habilitado','assets\\img\\productos\\producto_31.jpg'),(30,'0003','Cutter 360','Corta en todas direcciones. Ideal para manualidades.',31,15,50,10,100,'Garantía valida en los primeros 365 días',10.75,'habilitado','assets\\img\\productos\\producto_42.jpg'),(31,'0004','Pegamento para Papel','Pegamento para papel. Ideal para reparar billetes.',27,15,20,10,50,'Sin Garantía',7.16,'habilitado','assets\\img\\productos\\producto_43.jpg'),(32,'0005','Cortadoras de papel','Cortadoras especiales para papelería.',33,15,20,5,50,'Sin Garantía',8.00,'habilitado','assets\\img\\productos\\producto_44.jpg'),(33,'0006','Resma de Papel','Resmas de papel originales HP. Calidad en cada hoja, perfecta para oficina (tamaño carta).',30,15,30,10,70,'Sin Garantía',7.16,'habilitado','assets\\img\\productos\\producto_45.jpg'),(34,'0007','Pendrive 64GB','Unidad flash USB 3.2 Gen 1. Compatible y de alto rendimiento de 64GB.',28,15,10,5,20,'Garantía de 1 mes de duración',7.16,'habilitado','assets\\img\\productos\\producto_46.jpg'),(35,'0008','Tarjeta microSD','Tarjeta microSD con adaptador. Velocidades de hasta 150MB/s en lectura.',28,15,7,5,20,'Garantía de 1 mes de duración',8.84,'habilitado','assets\\img\\productos\\producto_47.jpg'),(36,'0009','Pendrive 128GB','Unidad flash USB 3.2 Gen 1 con capacidad de 128GB para tus archivos.',29,15,10,5,25,'Garantía de 1 mes de duración',10.96,'habilitado','assets\\img\\productos\\producto_48.jpg'),(37,'0010','Cable USB','Cable USB para impresoras, dos metros de largo.',31,11,12,10,40,'Garantía de 1 mes de duración',7.16,'habilitado','assets\\img\\productos\\producto_49.jpg'),(38,'0011','Auriculares Bluetooth','Sonido de alta fidelidad con cancelación de ruido.',23,15,16,5,20,'Garantía de 1 mes de duración',37.97,'habilitado','assets\\img\\productos\\producto_50.jpg'),(39,'0012','Cinta de Impresora','Cinta original para impresoras Epson LX300 y LX350.',24,15,7,3,15,'Garantía de 1 mes de duración',20.00,'habilitado','assets\\img\\productos\\producto_51.jpg'),(40,'0013','Botellas de Tinta HP','Botella de tinta original HP GT52.',26,12,20,10,100,'Garantía de 1 mes de duración',31.52,'habilitado','assets\\img\\productos\\producto_52.jpg'),(41,'0014','Botellas de Tinta Canon','Botella de tinta Canon GT52.',55,12,15,5,50,'Sin Garantia',57.32,'habilitado','assets\\img\\productos\\producto_53.jpg');
/*!40000 ALTER TABLE `tbl_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_proveedores`
--

DROP TABLE IF EXISTS `tbl_proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_proveedor` varchar(2048) NOT NULL,
  `rif_proveedor` varchar(15) DEFAULT NULL,
  `nombre_representante` varchar(2048) DEFAULT NULL,
  `rif_representante` varchar(15) DEFAULT NULL,
  `correo_proveedor` varchar(2048) DEFAULT NULL,
  `direccion_proveedor` varchar(2048) DEFAULT NULL,
  `telefono_1` varchar(2048) DEFAULT NULL,
  `telefono_2` varchar(2048) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `estado` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado',
  PRIMARY KEY (`id_proveedor`),
  KEY `idx_proveedores_nombre` (`nombre_proveedor`(768))
) ENGINE=InnoDB AUTO_INCREMENT=1003 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_proveedores`
--

LOCK TABLES `tbl_proveedores` WRITE;
/*!40000 ALTER TABLE `tbl_proveedores` DISABLE KEYS */;
INSERT INTO `tbl_proveedores` VALUES (1,'Grupo PC Depot','V-12332125-7','Brayan Mendoza','J-98778954-7','ejemplo@gmail.com','calle 32 con carrera 18 y 19','0412-258-8989','0424-654-4554','Buena calidad de productos','habilitado'),(2,'DISOFI','V-12878897-7','Paula Rivero','J-97781346-2','paula@gmail.com','AV. Lara, Calle Los Mangos','0412-554-0466','0424-181-4733','Envio gratis','habilitado'),(3,'HP Paper','V-12354557-7','Simón Freitez','J-93423334-2','simon@gmail.com','AV. Los Leones, Trinitarias','0424-545-9876','0424-889-4013','Calidad, precio','habilitado'),(4,'Amazon Business','J-00234578-2','Diego Lopez','J-93423334-2','amazon@gmail.com','EE. UU.','0424-511-4156','0424-748-9837','Se consigue de todo','habilitado');
/*!40000 ALTER TABLE `tbl_proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_recepcion_productos`
--

DROP TABLE IF EXISTS `tbl_recepcion_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_recepcion_productos` (
  `id_recepcion` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `correlativo` varchar(255) NOT NULL,
  `estado` enum('habilitado','anulado') NOT NULL DEFAULT 'habilitado',
  `subtotal_factura` decimal(12,2) DEFAULT NULL,
  `porcentaje_iva` decimal(7,2) DEFAULT NULL,
  `monto_iva` decimal(12,2) DEFAULT NULL,
  `total_factura` decimal(12,2) DEFAULT NULL,
  `stock_aplicado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_recepcion`),
  KEY `fk_recepcion_proveedor` (`id_proveedor`),
  CONSTRAINT `fk_recepcion_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `tbl_proveedores` (`id_proveedor`),
  CONSTRAINT `tbl_recepcion_productos` FOREIGN KEY (`id_proveedor`) REFERENCES `tbl_proveedores` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_recepcion_productos`
--

LOCK TABLES `tbl_recepcion_productos` WRITE;
/*!40000 ALTER TABLE `tbl_recepcion_productos` DISABLE KEYS */;
INSERT INTO `tbl_recepcion_productos` VALUES (10,2,'2025-07-22','938215','habilitado',2624.00,16.00,419.84,3043.84,0),(11,1,'2025-07-27','012537','habilitado',160.00,16.00,25.60,185.60,0),(12,4,'2026-11-17','852914','habilitado',4611.50,16.00,737.84,5349.34,0),(13,4,'2026-11-17','749281','habilitado',4072.50,16.00,651.60,4724.10,0),(14,2,'2026-11-17','841030','habilitado',2516.00,16.00,402.56,2918.56,0),(15,2,'2026-11-17','841029','habilitado',2461.00,16.00,393.76,2854.76,0),(16,1,'2026-11-17','938210','habilitado',1014.50,16.00,162.32,1176.82,0),(17,4,'2026-11-17','216573','habilitado',3750.00,16.00,600.00,4350.00,0);
/*!40000 ALTER TABLE `tbl_recepcion_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `vw_catalogo`
--

DROP TABLE IF EXISTS `vw_catalogo`;
/*!50001 DROP VIEW IF EXISTS `vw_catalogo`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_catalogo` AS SELECT
 1 AS `tipo_item`,
  1 AS `id`,
  1 AS `nombre`,
  1 AS `descripcion`,
  1 AS `categoria`,
  1 AS `marca`,
  1 AS `precio`,
  1 AS `stock`,
  1 AS `estado`,
  1 AS `imagen` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'casalai_principal'
--
/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;
/*!50106 DROP EVENT IF EXISTS `evt_liberar_stock_borradores` */;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = utf8mb4 */ ;;
/*!50003 SET character_set_results = utf8mb4 */ ;;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = '+00:00' */ ;;
/*!50106 CREATE*/ /*!50117 DEFINER=`root`@`localhost`*/ /*!50106 EVENT `evt_liberar_stock_borradores` ON SCHEDULE EVERY 5 MINUTE STARTS '2026-06-16 21:20:47' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- 1. Devolver los productos al stock de las facturas en 'Borrador' vencidas (más de 2 horas)
    UPDATE tbl_productos p
    JOIN tbl_factura_detalle df ON p.id_producto = df.id_producto
    JOIN tbl_facturas f ON df.factura_id = f.id_factura
    SET p.stock = p.stock + df.cantidad
    WHERE f.estatus = 'Borrador'
      AND f.fecha < NOW() - INTERVAL 1 HOUR;

    -- 2. Cambiar el estatus de esas facturas para que no se procesen de nuevo
    UPDATE tbl_facturas
    SET estatus = 'Anulada por Tiempo'
    WHERE estatus = 'Borrador'
      AND fecha < NOW() - INTERVAL 1 HOUR;

END */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
DELIMITER ;
/*!50106 SET TIME_ZONE= @save_time_zone */ ;

--
-- Dumping routines for database 'casalai_principal'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_anular_despacho` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_anular_despacho`(
    IN p_id_despacho INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_estado_anterior VARCHAR(20);
    DECLARE v_activo_anterior TINYINT;

    -- Concurrencia Avanzada: Bloqueamos la fila en la tabla de despachos
    SELECT estado, activo INTO v_estado_anterior, v_activo_anterior
    FROM tbl_despachos
    WHERE id_despachos = p_id_despacho
    FOR UPDATE;

    -- Verificación de Existencia
    IF v_estado_anterior IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El despacho especificado no existe en el sistema.';
    END IF;

    -- Verificación de doble anulación
    IF v_activo_anterior = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación denegada: El despacho ya se encuentra anulado.';
    END IF;

    -- Regla de Negocio Crítica: Si ya fue despachado, no debería poder anularse sin un proceso de devolución
    IF v_estado_anterior = 'Despachado' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede anular un despacho que ya figura como "Despachado".';
    END IF;

    -- Actualización Atómica (Eliminación Lógica)
    UPDATE tbl_despachos
    SET activo = 0
    WHERE id_despachos = p_id_despacho;

    -- Auditoría Síncrona Estructurada en JSON (Prioridad ALTA por alteración de integridad)
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, 
        `nombre_modulo`, 
        `accion`, 
        `datos_nuevos`, 
        `datos_viejos`, 
        `id_usuario`, 
        `prioridad`, 
        `descripcion`
    )
    VALUES (
        NOW(),
        'Despachos',
        'ANULAR',
        JSON_OBJECT('id_despachos', p_id_despacho, 'activo', 0),
        JSON_OBJECT('id_despachos', p_id_despacho, 'activo', v_activo_anterior, 'estado', v_estado_anterior),
        p_id_usuario_auditor,
        'alta',
        CONCAT('Se realizó la anulación lógica del despacho con ID: ', p_id_despacho, '.')
    );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_anular_orden_despacho` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_anular_orden_despacho`(
    IN p_id_orden INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    -- 1. Declaración de variables locales para auditoría e integridad
    DECLARE v_activo_anterior INT DEFAULT NULL;
    DECLARE v_estado_anterior VARCHAR(50);

    -- 2. Manejador de excepciones transaccionales
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        -- Si ocurre un fallo, se deshacen todos los cambios concurrentes
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error transaccional: No se pudo anular la orden de despacho.'; 
    END;

    -- Configuración estricta de aislamiento
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- CONTROL DE CONCURRENCIA: Bloqueamos la fila y extraemos el estado actual
    SELECT activo, estado INTO v_activo_anterior, v_estado_anterior
    FROM tbl_orden_despachos 
    WHERE id_orden_despachos = p_id_orden 
    FOR UPDATE;

    -- CONTROL DE INTEGRIDAD 1: Verificar si la orden realmente existe
    IF v_activo_anterior IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La orden de despacho solicitada no existe o ya fue eliminada.';
    END IF;

    -- CONTROL DE INTEGRIDAD 2: Verificar si ya se encuentra anulada para evitar redundancia
    IF v_activo_anterior = 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La orden de despacho ya se encuentra anulada en el sistema.';
    END IF;

    -- 3. Ejecución de la eliminación lógica
    UPDATE tbl_orden_despachos 
    SET activo = 0 
    WHERE id_orden_despachos = p_id_orden;

    -- 4. Registro síncrono de auditoría en la base de datos de seguridad
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    )
    VALUES (
        NOW(), 
        'Ordenes de despacho', 
        'ANULAR', 
        JSON_OBJECT('id_orden_despachos', p_id_orden, 'activo', 0), 
        JSON_OBJECT('id_orden_despachos', p_id_orden, 'activo', v_activo_anterior, 'estado', v_estado_anterior), 
        p_id_usuario_auditor, 
        'alta', 
        CONCAT('Se anuló lógicamente la orden de despacho ID: ', p_id_orden, '.')
    );

    COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_anular_recepcion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_anular_recepcion`(
    IN p_correlativo VARCHAR(255),
    IN p_id_usuario_auditor INT,
    OUT p_resultado INT
)
BEGIN
    DECLARE v_id_recepcion INT DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT NULL;
    DECLARE v_stock_aplicado TINYINT DEFAULT 0;
    DECLARE v_productos_reversion INT DEFAULT 0;
    DECLARE v_existe INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        RESIGNAL;
    END;

    SET p_resultado = 0;
    START TRANSACTION;

    SELECT COUNT(*) INTO v_existe
    FROM tbl_recepcion_productos
    WHERE correlativo = p_correlativo;
    IF v_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La recepción no existe.';
    END IF;

    SELECT id_recepcion, estado, stock_aplicado
    INTO v_id_recepcion, v_estado, v_stock_aplicado
    FROM tbl_recepcion_productos
    WHERE correlativo = p_correlativo
    ORDER BY id_recepcion DESC
    LIMIT 1
    FOR UPDATE;

    IF v_estado = 'anulado' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La recepción ya está anulada.';
    END IF;

    IF v_stock_aplicado = 1 THEN
        SELECT COUNT(DISTINCT id_producto)
        INTO v_productos_reversion
        FROM tbl_detalle_recepcion_productos
        WHERE id_recepcion = v_id_recepcion;

        UPDATE tbl_productos p
        INNER JOIN (
            SELECT id_producto, SUM(cantidad) AS cantidad
            FROM tbl_detalle_recepcion_productos
            WHERE id_recepcion = v_id_recepcion
            GROUP BY id_producto
        ) d ON d.id_producto = p.id_producto
        SET p.stock = COALESCE(p.stock, 0) - d.cantidad
        WHERE COALESCE(p.stock, 0) >= d.cantidad;

        IF ROW_COUNT() <> v_productos_reversion THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede anular la recepción porque el stock actual no alcanza para revertirla.';
        END IF;
    END IF;

    UPDATE tbl_recepcion_productos
    SET estado = 'anulado'
    WHERE id_recepcion = v_id_recepcion;

    INSERT INTO `casalai_seguridad`.`tbl_bitacora`
        (fecha_hora, nombre_modulo, accion, datos_nuevos, datos_viejos, id_usuario, prioridad, descripcion)
    VALUES
        (NOW(), 'Recepcion', 'ANULAR',
         JSON_OBJECT('id_recepcion', v_id_recepcion, 'correlativo', p_correlativo, 'estado', 'anulado'),
         JSON_OBJECT('id_recepcion', v_id_recepcion, 'correlativo', p_correlativo, 'estado', v_estado),
         p_id_usuario_auditor, 'media', CONCAT('Se anuló la recepción ', p_correlativo, '.'));

    SET p_resultado = 1;
    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_cambiar_estado_cuenta` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cambiar_estado_cuenta`(
    IN p_id_cuenta INT,
    IN p_nuevo_estado ENUM('habilitado','inhabilitado'),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_banco VARCHAR(255);
    DECLARE v_estado_previo ENUM('habilitado','inhabilitado');

    -- Manejador general de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo alterar el estado de la cuenta.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo exclusivo y extracción del estado previo
    SELECT `nombre_banco`, `estado` INTO v_nombre_banco, v_estado_previo
    FROM `tbl_cuentas`
    WHERE `id_cuenta` = p_id_cuenta
    LIMIT 1 
    FOR UPDATE;

    -- Modificación física del campo ENUM
    UPDATE `tbl_cuentas`
    SET `estado` = p_nuevo_estado
    WHERE `id_cuenta` = p_id_cuenta;

    -- Registro conciso en bitácora
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Cuentas bancarias', 
        'MODIFICAR', 
        JSON_OBJECT('id_cuenta', p_id_cuenta, 'estado', p_nuevo_estado), 
        JSON_OBJECT('id_cuenta', p_id_cuenta, 'estado', v_estado_previo), 
        p_id_usuario_auditor, 
        'media',
        CONCAT('Se cambió el estado de la cuenta bancaria "', IFNULL(v_nombre_banco, 'Desconocido'), '" a: ', p_nuevo_estado, '.')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_cambiar_estado_despacho` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cambiar_estado_despacho`(
    IN p_id_despacho INT,
    IN p_nuevo_estado VARCHAR(20),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_estado_anterior VARCHAR(20);
    DECLARE v_activo_anterior TINYINT;

    -- Manejador de excepciones para garantizar el ROLLBACK ante errores imprevistos
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo modificar el estado del despacho.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Concurrencia Avanzada: Bloqueo de fila exclusivo para evitar actualizaciones fantasmas
    SELECT estado, activo INTO v_estado_anterior, v_activo_anterior
    FROM tbl_despachos
    WHERE id_despachos = p_id_despacho
    FOR UPDATE;

    -- Control de flujo de datos (Verificación de existencia y consistencia)
    IF v_estado_anterior IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El despacho especificado no existe en el sistema.';
    END IF;

    IF v_activo_anterior = 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación inválida: El despacho se encuentra anulado.';
    END IF;

    -- Actualización de estado atómica
    UPDATE tbl_despachos
    SET estado = p_nuevo_estado
    WHERE id_despachos = p_id_despacho;

    -- Auditoría Síncrona: Inserción directa en la base de datos de seguridad
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, 
        `nombre_modulo`, 
        `accion`, 
        `datos_nuevos`, 
        `datos_viejos`, 
        `id_usuario`, 
        `prioridad`, 
        `descripcion`
    )
    VALUES (
        NOW(),
        'Despacho',
        'CAMBIAR ESTADO',
        JSON_OBJECT('id_despachos', p_id_despacho, 'estado', p_nuevo_estado),
        JSON_OBJECT('id_despachos', p_id_despacho, 'estado', v_estado_anterior),
        p_id_usuario_auditor,
        'media',
        CONCAT('Se cambió el estado del despacho con ID: ', p_id_despacho, ' a ', p_nuevo_estado, '.')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_cambiar_estado_orden_despacho` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cambiar_estado_orden_despacho`(
    IN p_id_orden INT,
    IN p_nuevo_estado ENUM('Por Entregar','Entregada'),
    IN p_id_usuario_auditor INT
)
BEGIN
    -- 1. Declaración de variables locales
    DECLARE v_id_factura INT DEFAULT NULL;
    DECLARE v_fecha_despacho DATE;
    DECLARE v_id_cliente INT;
    DECLARE v_id_despacho INT;
    DECLARE v_id_producto INT;
    DECLARE v_cantidad INT;
    DECLARE v_estado_anterior VARCHAR(50);
    DECLARE v_fin INT DEFAULT 0;

    -- 2. Declaración del Cursor para procesar los detalles de la factura en lote
    DECLARE cur_detalles CURSOR FOR 
        SELECT id_producto, cantidad 
        FROM tbl_factura_detalle 
        WHERE factura_id = v_id_factura;

    -- 3. Manejadores de condiciones y errores (Mantiene la integridad ante fallos)
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_fin = 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        -- Si algo falla, deshace absolutamente todos los cambios concurrentes
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error transaccional: No se pudo actualizar el estado ni procesar el despacho.'; 
    END;

    -- Configuración estricta de aislamiento de transacciones concurrentes
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- CONTROL DE CONCURRENCIA: Bloqueamos la fila de la orden con FOR UPDATE
    SELECT id_factura, fecha_despacho, estado 
    INTO v_id_factura, v_fecha_despacho, v_estado_anterior
    FROM tbl_orden_despachos 
    WHERE id_orden_despachos = p_id_orden 
    FOR UPDATE;

    -- CONTROL DE INTEGRIDAD: Verificar si la orden realmente existe
    IF v_id_factura IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: La orden de despacho solicitada no existe o fue eliminada.';
    END IF;

    -- Reiniciamos la bandera de control por si el SELECT anterior levantó un flag NOT FOUND
    SET v_fin = 0;

    -- Actualización directa del estado
    UPDATE tbl_orden_despachos 
    SET estado = p_nuevo_estado 
    WHERE id_orden_despachos = p_id_orden;

    -- Lógica interna optimizada si pasa a "Entregada"
    IF p_nuevo_estado = 'Entregada' THEN
        
        -- Obtener el cliente de la factura asociada
        SELECT cliente INTO v_id_cliente 
        FROM tbl_facturas 
        WHERE id_factura = v_id_factura;
        
        -- Insertar cabecera en la tabla de despachos finales
        INSERT INTO tbl_despachos (id_clientes, fecha_despacho, tipocompra, estado, activo) 
        VALUES (v_id_cliente, v_fecha_despacho, 'Online', 'Por Despachar', 1);
        
        -- Capturamos el ID generado de forma aislada
        SET v_id_despacho = LAST_INSERT_ID();
        
        -- Reiniciamos la bandera de control antes de abrir el cursor de detalles
        SET v_fin = 0;
        
        -- Procesamiento del bucle directamente en la Base de Datos mediante el Cursor
        OPEN cur_detalles;
        read_loop: LOOP
            FETCH cur_detalles INTO v_id_producto, v_cantidad;
            IF v_fin = 1 THEN 
                LEAVE read_loop; 
            END IF;
            
            -- Inserción del detalle del despacho
            INSERT INTO tbl_despacho_detalle (id_despacho, id_producto, cantidad) 
            VALUES (v_id_despacho, v_id_producto, v_cantidad);
        END LOOP;
        CLOSE cur_detalles;
        
    END IF;

    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    )
    VALUES (
        NOW(), 
        'Ordenes de despacho', 
        'CAMBIAR ESTADO', 
        JSON_OBJECT('id_orden_despachos', p_id_orden, 'estado', p_nuevo_estado), 
        JSON_OBJECT('id_orden_despachos', p_id_orden, 'estado', v_estado_anterior), 
        p_id_usuario_auditor, 
        'media', 
        CONCAT('Se cambió el estado de la orden de despacho ID: ', p_id_orden, ' a ', p_nuevo_estado, '.')
    );

    COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_cambiar_estado_proveedor` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cambiar_estado_proveedor`(
    IN p_id_proveedor INT,
    IN p_nuevo_estado ENUM('habilitado', 'inhabilitado'),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_proveedor VARCHAR(2048);
    DECLARE v_rif_proveedor VARCHAR(15);
    DECLARE v_estado_viejo ENUM('habilitado', 'inhabilitado');

    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        ROLLBACK; 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo conmutar el estado del proveedor.'; 
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo exclusivo y obtención forense corta
    SELECT `nombre_proveedor`, `rif_proveedor`, `estado`
    INTO v_nombre_proveedor, v_rif_proveedor, v_estado_viejo
    FROM `tbl_proveedores`
    WHERE `id_proveedor` = p_id_proveedor
    LIMIT 1
    FOR UPDATE;

    -- Modificación de estado energético
    UPDATE `tbl_proveedores` SET `estado` = p_nuevo_estado WHERE `id_proveedor` = p_id_proveedor;

    -- Reporte de auditoría síncrono
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    ) VALUES (
        NOW(), 
        'Proveedores', 
        'MODIFICAR', 
        JSON_OBJECT('id_proveedor', p_id_proveedor, 'estado', p_nuevo_estado), 
        JSON_OBJECT('id_proveedor', p_id_proveedor, 'estado', v_estado_viejo), 
        p_id_usuario_auditor, 
        'media', 
        CONCAT('Se cambió el estado del proveedor "', IFNULL(v_nombre_proveedor, 'Desconocido'), '" a: ', UPPER(p_nuevo_estado), '.')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_cliente` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_cliente`()
BEGIN
    -- Manejador general de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo realizar la consulta de los clientes.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Seleccionamos los datos visibles + los campos de control del Frontend
    SELECT `id_clientes`, `nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo` 
    FROM `tbl_clientes` 
    ORDER BY `id_clientes` DESC
    FOR UPDATE;

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_cuenta` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_cuenta`()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error estructural al cargar la tabla de cuentas.';
    END;

    SELECT * FROM `tbl_cuentas` 
    ORDER BY `id_cuenta` DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_despachos_activos` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_despachos_activos`()
BEGIN
    SELECT 
        r.id_despachos,
        r.fecha_despacho AS fecha,
        r.tipocompra,
        r.estado,
        c.nombre AS nombre_cliente,
        c.cedula AS cedula_cliente,
        SUM(d.cantidad) AS total_productos,
        SUM(d.cantidad * p.precio) AS valor_total
    FROM tbl_despachos AS r
    INNER JOIN tbl_despacho_detalle AS d ON d.id_despacho = r.id_despachos
    INNER JOIN tbl_clientes AS c ON c.id_clientes = r.id_clientes
    INNER JOIN tbl_productos AS p ON p.id_producto = d.id_producto
    WHERE r.activo = 1
    GROUP BY r.id_despachos, r.fecha_despacho, r.tipocompra, r.estado, c.nombre, c.cedula
    ORDER BY r.fecha_despacho DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_marcas` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_marcas`()
BEGIN
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT `id_marca`, `nombre_marca` 
    FROM `tbl_marcas` 
    ORDER BY `nombre_marca` ASC
    FOR UPDATE;

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_modelos` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_modelos`()
BEGIN
    -- Selección estructurada con INNER JOIN y ordenamiento descendente por ID
    SELECT 
        mo.`id_modelo`,
        mo.`id_marca`,
        mo.`nombre_modelo`,
        ma.`nombre_marca` 
    FROM `tbl_modelos` AS mo
    INNER JOIN `tbl_marcas` AS ma ON mo.`id_marca` = ma.`id_marca`
    ORDER BY mo.`id_modelo` DESC
    FOR UPDATE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_ordenes_despacho` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_ordenes_despacho`()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo cargar el listado de órdenes de despacho.'; 
    END;

    -- Consulta optimizada con indices nativos
    SELECT 
        od.id_orden_despachos,
        od.id_factura,
        c.cedula,
        od.cliente,
        od.fecha_despacho,
        od.estado,
        od.activo
    FROM tbl_orden_despachos AS od
    INNER JOIN tbl_facturas f ON f.id_factura = od.id_factura
    INNER JOIN tbl_clientes c ON c.id_clientes = f.cliente
    WHERE od.activo = 1
    ORDER BY od.fecha_despacho DESC, od.id_orden_despachos DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_productos_recepcion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_productos_recepcion`()
BEGIN
    SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
    FROM tbl_productos p
    INNER JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
    INNER JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
    ORDER BY p.nombre_producto, p.id_producto;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_proveedores` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_proveedores`()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo obtener la lista de proveedores.'; 
    END;

    SELECT 
        `id_proveedor`, 
        `nombre_proveedor`, 
        `rif_proveedor`, 
        `nombre_representante`, 
        `rif_representante`, 
        `correo_proveedor`, 
        `direccion_proveedor`, 
        `telefono_1`, 
        `telefono_2`, 
        `observacion`, 
        `estado`
    FROM `tbl_proveedores`
    ORDER BY `nombre_proveedor`
    FOR UPDATE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_proveedores_recepcion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_proveedores_recepcion`()
BEGIN
    SELECT id_proveedor, nombre_proveedor
    FROM tbl_proveedores
    ORDER BY nombre_proveedor, id_proveedor;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_consultar_recepciones` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_consultar_recepciones`(
    IN p_id_recepcion INT,
    IN p_correlativo VARCHAR(255),
    IN p_id_proveedor INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_estado VARCHAR(20),
    IN p_pagina INT,
    IN p_limite INT,
    OUT p_total INT
)
BEGIN
    DECLARE v_offset INT DEFAULT 0;
    SET p_pagina = IFNULL(NULLIF(p_pagina, 0), 1);
    SET p_limite = IFNULL(NULLIF(p_limite, 0), 100);
    SET v_offset = (p_pagina - 1) * p_limite;

    SELECT COUNT(*) INTO p_total
    FROM tbl_recepcion_productos r
    INNER JOIN tbl_proveedores p ON p.id_proveedor = r.id_proveedor
    WHERE (p_id_recepcion IS NULL OR r.id_recepcion = p_id_recepcion)
      AND (p_correlativo IS NULL OR r.correlativo LIKE CONCAT('%', p_correlativo, '%'))
      AND (p_id_proveedor IS NULL OR r.id_proveedor = p_id_proveedor)
      AND (p_fecha_inicio IS NULL OR r.fecha >= p_fecha_inicio)
      AND (p_fecha_fin IS NULL OR r.fecha <= p_fecha_fin)
      AND (p_estado IS NULL OR r.estado = p_estado);

    SELECT r.id_recepcion, r.fecha, r.correlativo, p.nombre_proveedor,
           COALESCE(SUM(d.cantidad * d.costo), 0) AS costo_inversion,
           r.subtotal_factura, r.porcentaje_iva, r.monto_iva, r.total_factura, r.estado
    FROM tbl_recepcion_productos r
    INNER JOIN tbl_proveedores p ON p.id_proveedor = r.id_proveedor
    LEFT JOIN tbl_detalle_recepcion_productos d ON d.id_recepcion = r.id_recepcion
    WHERE (p_id_recepcion IS NULL OR r.id_recepcion = p_id_recepcion)
      AND (p_correlativo IS NULL OR r.correlativo LIKE CONCAT('%', p_correlativo, '%'))
      AND (p_id_proveedor IS NULL OR r.id_proveedor = p_id_proveedor)
      AND (p_fecha_inicio IS NULL OR r.fecha >= p_fecha_inicio)
      AND (p_fecha_fin IS NULL OR r.fecha <= p_fecha_fin)
      AND (p_estado IS NULL OR r.estado = p_estado)
    GROUP BY r.id_recepcion, r.fecha, r.correlativo, p.nombre_proveedor,
             r.subtotal_factura, r.porcentaje_iva, r.monto_iva, r.total_factura, r.estado
    ORDER BY r.fecha DESC, r.correlativo DESC
    LIMIT v_offset, p_limite;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_eliminar_categoria` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_categoria`(IN `p_id_categoria` INT, IN `p_nombre_tabla` VARCHAR(255), IN `p_id_usuario_auditor` INT)
BEGIN
    -- 1. DECLARACIÓN DE VARIABLES LOCALES AL INICIO ESTRICTO
    DECLARE v_nombre_categoria VARCHAR(255);
    DECLARE v_productos_count INT;
    DECLARE v_datos_viejos JSON;
    DECLARE v_error_msg VARCHAR(500);

    -- 2. MANEJAdores de EXCEPCIONES (Deben ir antes de cualquier comando ejecutable)
    -- MANEJADOR ESPECÍFICO PARA EL ESCENARIO DE LLAVE FORÁNEA (Error 1451)
    DECLARE EXIT HANDLER FOR 1451
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación denegada: No se puede eliminar la categoría porque posee registros históricos asociados.';
    END;

    -- Manejador general para cualquier otro tipo de error
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        GET DIAGNOSTICS CONDITION 1
        @sqlstate = RETURNED_SQLSTATE, @errno = MYSQL_ERRNO, @text = MESSAGE_TEXT;
        
        -- Mensajes específicos según el código de error
        IF @errno = 1146 THEN
            SET v_error_msg = 'Error: La tabla especificada no existe en la base de datos';
        ELSEIF @errno = 1050 THEN
            SET v_error_msg = 'Error: La tabla ya existe (conflicto de nombres)';
        ELSEIF @errno = 1062 THEN
            SET v_error_msg = 'Error: Duplicidad de datos en la base de datos';
        ELSEIF @errno = 1213 THEN
            SET v_error_msg = 'Error: Conflicto de bloqueo, intente nuevamente';
        ELSE
            SET v_error_msg = CONCAT('Error interno (Código: ', @errno, '): ', @text);
        END IF;
        
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END;

    -- 3. INICIO DE LÓGICA Y TRANSACCIONES

    -- Validación de parámetros de entrada (Movido aquí de forma segura)
    IF p_id_categoria IS NULL OR p_id_categoria <= 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID de categoría inválido';
    END IF;
    
    IF p_nombre_tabla IS NULL OR p_nombre_tabla = '' THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nombre de tabla inválido';
    END IF;
    
    IF p_id_usuario_auditor IS NULL OR p_id_usuario_auditor <= 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID de usuario auditor inválido';
    END IF;

    -- Bloqueo exclusivo de seguridad y captura pre-mortem
    SELECT nombre_categoria INTO v_nombre_categoria 
    FROM tbl_categoria 
    WHERE id_categoria = p_id_categoria
    LIMIT 1
    FOR UPDATE;
        
    -- Validación de existencia
    IF v_nombre_categoria IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Categoría no encontrada';
    END IF;
        
    -- Preparación del objeto JSON de auditoría
    SET v_datos_viejos = JSON_OBJECT('id_categoria', p_id_categoria, 'nombre_categoria', v_nombre_categoria, 'tabla', p_nombre_tabla);
        
    -- Verificar si la tabla dinámica existe
    SET @table_exists = 0;
    SELECT COUNT(*) INTO @table_exists
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = 'casalai_principal' 
    AND TABLE_NAME = p_nombre_tabla;
        
    IF @table_exists > 0 THEN
        -- Inicializamos la variable de usuario para el conteo
        SET @prod_count = 0;
        
        -- Construcción limpia del SQL dinámico
        SET @count_sql = CONCAT('SELECT COUNT(*) INTO @prod_count FROM `', p_nombre_tabla, '`');
        PREPARE stmt FROM @count_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
                
        SET v_productos_count = @prod_count;
                
        IF v_productos_count > 0 THEN
            ROLLBACK;
            SET v_error_msg = CONCAT('No se puede eliminar porque tiene ', v_productos_count, ' productos asociados');
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
        END IF;
                
        -- Eliminar la tabla dinámica
        SET @drop_sql = CONCAT('DROP TABLE IF EXISTS `', p_nombre_tabla, '`');
        PREPARE stmt FROM @drop_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Verificar que la tabla se eliminó correctamente
        SET @table_exists_after = 0;
        SELECT COUNT(*) INTO @table_exists_after
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'casalai_principal' 
        AND TABLE_NAME = p_nombre_tabla;
        
        IF @table_exists_after > 0 THEN
            ROLLBACK;
            SET v_error_msg = CONCAT('No se pudo eliminar la tabla dinámica: ', p_nombre_tabla);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
        END IF;
    END IF;
        
    -- Remoción física del registro
    DELETE FROM tbl_categoria WHERE id_categoria = p_id_categoria;
    
    -- Verificar que la categoría se eliminó correctamente
    SET @categoria_deleted = 0;
    SELECT COUNT(*) INTO @categoria_deleted
    FROM tbl_categoria 
    WHERE id_categoria = p_id_categoria;
    
    IF @categoria_deleted > 0 THEN
        ROLLBACK;
        SET v_error_msg = CONCAT('No se pudo eliminar el registro de la categoría con ID: ', p_id_categoria);
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END IF;
        
    -- Registro de expulsión en bitácora con prioridad ALTA
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Categorías', 
        'ELIMINAR', 
        NULL, 
        v_datos_viejos, 
        p_id_usuario_auditor, 
        'alta', 
        CONCAT('Se eliminó físicamente la categoría "', IFNULL(v_nombre_categoria, 'Desconocido'), '" del sistema y su tabla asociada: ', p_nombre_tabla)
    );


END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_eliminar_cliente` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_cliente`(
    IN p_id_cliente INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_eliminado VARCHAR(255);
    DECLARE v_cedula_eliminado VARCHAR(10);

    -- MANEJADOR ESPECÍFICO PARA EL ESCENARIO DE LLAVE FORÁNEA (Error 1451)
    DECLARE EXIT HANDLER FOR 1451
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación denegada: No se puede eliminar el cliente porque posee registros históricos asociados (Facturas o Despachos).';
    END;

    -- Manejador general para cualquier otro tipo de error
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo procesar la eliminación física del cliente.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo exclusivo de seguridad y captura pre-mortem
    SELECT `nombre`, `cedula` INTO v_nombre_eliminado, v_cedula_eliminado
    FROM `tbl_clientes`
    WHERE `id_clientes` = p_id_cliente
    LIMIT 1 
    FOR UPDATE;

    -- Remoción física del registro
    DELETE FROM `tbl_clientes`
    WHERE `id_clientes` = p_id_cliente;

    -- Registro de expulsión en bitácora con prioridad ALTA
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Clientes', 
        'ELIMINAR', 
        NULL, 
        JSON_OBJECT('id_clientes', p_id_cliente, 'nombre', v_nombre_eliminado, 'cedula', v_cedula_eliminado), 
        p_id_usuario_auditor, 
        'alta', 
        CONCAT('Se eliminó físicamente al cliente "', IFNULL(v_nombre_eliminado, 'Desconocido'), '" (C.I: ', IFNULL(v_cedula_eliminado, 'Desconocido'), ') del sistema.')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_eliminar_cuenta` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_cuenta`(
    IN p_id_cuenta INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_banco_eliminar VARCHAR(255);
    DECLARE v_rif_cuenta_eliminar VARCHAR(15);

    -- MANEJADOR ESPECÍFICO PARA RESTRICCIÓN RELACIONAL (Error 1451)
    -- Si la cuenta ya está vinculada a pagos de facturas o compras, impide el borrado.
    DECLARE EXIT HANDLER FOR 1451
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación denegada: No se puede eliminar la cuenta porque registra movimientos históricos en el sistema. Considere inhabilitarla.';
    END;

    -- Manejador de fallas generales
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo procesar la eliminación física de la cuenta.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo exclusivo de seguridad y extracción forense pre-mortem
    SELECT `nombre_banco`, `rif_cuenta` INTO v_nombre_banco_eliminar, v_rif_cuenta_eliminar
    FROM `tbl_cuentas`
    WHERE `id_cuenta` = p_id_cuenta
    LIMIT 1 
    FOR UPDATE;

    -- Remoción física del registro
    DELETE FROM `tbl_cuentas`
    WHERE `id_cuenta` = p_id_cuenta;

    -- Envío síncrono a bitácora con prioridad ALTA
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Cuentas bancarias', 
        'ELIMINAR', 
        NULL, 
        JSON_OBJECT('id_cuenta', p_id_cuenta, 'nombre_banco', v_nombre_banco_eliminar, 'rif_cuenta', v_rif_cuenta_eliminar), 
        p_id_usuario_auditor, 
        'alta', 
        CONCAT('Se eliminó físicamente del sistema la cuenta bancaria "', IFNULL(v_nombre_banco_eliminar, 'Desconocido'), '" (RIF: ', IFNULL(v_rif_cuenta_eliminar, 'Desconocido'), ').')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_eliminar_marca` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_marca`(
    IN p_id_marca INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_marca_eliminar VARCHAR(25);

    -- El manejador interceptará también fallos por restricción de clave foránea (FK)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede eliminar la marca. Es posible que esté asociada a productos existentes.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo defensivo FOR UPDATE del registro candidato a eliminación
    SELECT `nombre_marca` INTO v_nombre_marca_eliminar 
    FROM `tbl_marcas` 
    WHERE `id_marca` = p_id_marca 
    FOR UPDATE;

    DELETE FROM `tbl_marcas` 
    WHERE `id_marca` = p_id_marca;

    -- Envío síncrono a bitácora con prioridad ALTA por ser destrucción de datos
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    )
    VALUES (
        NOW(),
        'Marcas',
        'ELIMINAR',
        NULL,
        JSON_OBJECT('id_marca', p_id_marca, 'nombre_marca', v_nombre_marca_eliminar),
        p_id_usuario_auditor,
        'alta',
        CONCAT('Se eliminó físicamente del sistema la marca "', v_nombre_marca_eliminar, '" (ID: ', p_id_marca, ').')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_eliminar_modelo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_modelo`(
    IN p_id_modelo INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_modelo_eliminar VARCHAR(25);
    DECLARE v_id_marca_eliminar INT;

    -- Intercepta excepciones de integridad referencial si el modelo está en uso por otra tabla (FK)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede eliminar el modelo. Es posible que esté asociado a productos u otras entidades del sistema.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo defensivo FOR UPDATE antes de la destrucción del registro
    SELECT `nombre_modelo`, `id_marca` INTO v_nombre_modelo_eliminar, v_id_marca_eliminar
    FROM `tbl_modelos` 
    WHERE `id_modelo` = p_id_modelo 
    FOR UPDATE;

    -- Eliminación física
    DELETE FROM `tbl_modelos` 
    WHERE `id_modelo` = p_id_modelo;

    -- Auditoría de destrucción física con prioridad Alta
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    )
    VALUES (
        NOW(),
        'Modelos',
        'ELIMINAR',
        NULL,
        JSON_OBJECT('id_modelo', p_id_modelo, 'nombre_modelo', v_nombre_modelo_eliminar, 'id_marca', v_id_marca_eliminar),
        p_id_usuario_auditor,
        'alta',
        CONCAT('Se eliminó físicamente del sistema el modelo "', v_nombre_modelo_eliminar, '" (ID: ', p_id_modelo, ').')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_eliminar_producto` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_producto`(
    IN p_id_producto INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_producto_eliminar VARCHAR(255);
    DECLARE v_serial_producto_eliminar VARCHAR(15);

    -- MANEJADOR ESPECÍFICO PARA RESTRICCIÓN RELACIONAL (Error 1451)
    -- Si la cuenta ya está vinculada a pagos de facturas o compras, impide el borrado.
    DECLARE EXIT HANDLER FOR 1451
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación denegada: No se puede eliminar producto porque registra movimientos históricos en el sistema. Considere inhabilitarla.';
    END;

    -- Manejador de fallas generales
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo procesar la eliminación física del producto';
    END;

    START TRANSACTION;

    -- Bloqueo exclusivo de seguridad y extracción forense pre-mortem
    SELECT `nombre_producto`, `serial` INTO v_nombre_producto_eliminar, v_serial_producto_eliminar
    FROM `tbl_productos`
    WHERE `id_producto` = p_id_producto
    LIMIT 1 
    FOR UPDATE;

    -- Remoción física del registro
    DELETE FROM `tbl_productos`
    WHERE `id_producto` = p_id_producto;

    -- Envío síncrono a bitácora con prioridad ALTA
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Productos', 
        'ELIMINAR', 
        NULL, 
        JSON_OBJECT('id_producto', p_id_producto, 'nombre_producto', v_nombre_producto_eliminar, 'serial', v_serial_producto_eliminar), 
        p_id_usuario_auditor, 
        'alta', 
        CONCAT('Se eliminó físicamente del sistema el producto "', IFNULL(v_nombre_producto_eliminar, 'Desconocido'), '" (SERIAL: ', IFNULL(v_serial_producto_eliminar, 'Desconocido'), ').')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_eliminar_proveedor` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_proveedor`(
    IN p_id_proveedor INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_proveedor_eliminar VARCHAR(50);
    DECLARE v_rif_proveedor_eliminar VARCHAR(15);

    -- Manejo controlado de fallas de integridad referencial (Error MySQL 1451)
    DECLARE EXIT HANDLER FOR 1451
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operación denegada: El proveedor registra dependencias o movimientos históricos en el inventario. Considere inhabilitarlo.';
    END;

    -- Manejador general
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        ROLLBACK; 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo completar la eliminación del proveedor.'; 
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo atómico pre-mortem para asegurar consistencia del registro a borrar
    SELECT `nombre_proveedor`, `rif_proveedor` 
    INTO v_nombre_proveedor_eliminar, v_rif_proveedor_eliminar
    FROM `tbl_proveedores`
    WHERE `id_proveedor` = p_id_proveedor
    LIMIT 1
    FOR UPDATE;

    -- Remoción física
    DELETE FROM `tbl_proveedores` WHERE `id_proveedor` = p_id_proveedor;

    -- Registro forense completo en bitácora de seguridad
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    ) VALUES (
        NOW(), 
        'Proveedores', 
        'ELIMINAR', 
        NULL, 
        JSON_OBJECT('id_proveedor', p_id_proveedor, 'nombre_proveedor', v_nombre_proveedor_eliminar, 'rif_proveedor', v_rif_proveedor_eliminar), 
        p_id_usuario_auditor, 
        'alta', 
        CONCAT('Se eliminó físicamente del sistema el proveedor "', IFNULL(v_nombre_proveedor_eliminar, 'Desconocido'), '" (RIF: ', IFNULL(v_rif_proveedor_eliminar, 'N/A'), ').')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_modificar_categoria` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modificar_categoria`(
    IN p_id_categoria INT,
    IN p_nuevo_nombre VARCHAR(255),
    IN p_caracteristicas JSON,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_actual VARCHAR(255);
    DECLARE v_tabla_antigua VARCHAR(255);
    DECLARE v_tabla_nueva VARCHAR(255);
    DECLARE v_datos_viejos JSON;
    DECLARE v_contador INT DEFAULT 0;
    DECLARE v_total_caracteristicas INT;
    DECLARE v_nombre_campo VARCHAR(255);
    DECLARE v_tipo_campo VARCHAR(50);
    DECLARE v_max_length INT;
    DECLARE v_max_length_raw JSON;
    DECLARE v_tipo_sql VARCHAR(100);
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_column_name VARCHAR(255);
    DECLARE v_column_type VARCHAR(100);
    DECLARE v_column_exists INT;
    
    
    DECLARE cur_columns CURSOR FOR 
        SELECT COLUMN_NAME, COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'casalai_principal' 
        AND TABLE_NAME = v_tabla_antigua 
        AND COLUMN_NAME NOT IN ('id', 'id_producto');
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
        @sqlstate = RETURNED_SQLSTATE, @errno = MYSQL_ERRNO, @text = MESSAGE_TEXT;
        SET @error_message = CONCAT('Error interno: ', @text, ' (SQLSTATE: ', @sqlstate, ', Errno: ', @errno, ')');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @error_message;
    END;

    
    SELECT nombre_categoria INTO v_nombre_actual 
    FROM tbl_categoria 
    WHERE id_categoria = p_id_categoria;
    
    IF v_nombre_actual IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Categor??a no encontrada';
    END IF;
    
    
    IF p_nuevo_nombre IS NULL OR TRIM(p_nuevo_nombre) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El nombre de la categor??a no puede estar vac??o';
    END IF;
    
    
    SET v_tabla_antigua = CONCAT('cat_', LOWER(REPLACE(v_nombre_actual, ' ', '_')));
    SET v_tabla_nueva = CONCAT('cat_', LOWER(REPLACE(p_nuevo_nombre, ' ', '_')));
    
    
    SET v_datos_viejos = JSON_OBJECT('id_categoria', p_id_categoria, 'nombre_categoria', v_nombre_actual, 'tabla', v_tabla_antigua);
    
    
    SET done = FALSE;
    OPEN cur_columns;
    
    read_loop: LOOP
        FETCH cur_columns INTO v_column_name, v_column_type;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        
        SET v_contador = 0;
        SET v_total_caracteristicas = JSON_LENGTH(p_caracteristicas);
        SET v_column_exists = 0;
        
        WHILE v_contador < v_total_caracteristicas DO
            SET v_nombre_campo = LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$[', v_contador, '].nombre'))), ' ', '_'));
            
            IF v_nombre_campo = v_column_name THEN
                SET v_column_exists = 1;
            END IF;
            
            SET v_contador = v_contador + 1;
        END WHILE;
        
        
        IF v_column_exists = 0 THEN
            SET @drop_sql = CONCAT('ALTER TABLE `', v_tabla_antigua, '` DROP COLUMN `', v_column_name, '`');
            PREPARE stmt FROM @drop_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END LOOP;
    
    CLOSE cur_columns;
    
    
    UPDATE tbl_categoria 
    SET nombre_categoria = p_nuevo_nombre 
    WHERE id_categoria = p_id_categoria;
    
    
    IF v_tabla_antigua != v_tabla_nueva THEN
        SET @rename_sql = CONCAT('RENAME TABLE `', v_tabla_antigua, '` TO `', v_tabla_nueva, '`');
        PREPARE stmt FROM @rename_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
    
    
    SET v_total_caracteristicas = JSON_LENGTH(p_caracteristicas);
    SET v_contador = 0;
    
    WHILE v_contador < v_total_caracteristicas DO
        SET v_nombre_campo = LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$[', v_contador, '].nombre'))), ' ', '_'));
        SET v_tipo_campo = JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$[', v_contador, '].tipo')));
        SET v_max_length_raw = JSON_EXTRACT(p_caracteristicas, CONCAT('$[', v_contador, '].max'));
        
        
        IF v_max_length_raw IS NULL THEN
            SET v_max_length = 255;
        ELSE
            
            SET v_max_length = CAST(JSON_UNQUOTE(v_max_length_raw) AS UNSIGNED);
            IF v_max_length = 0 OR v_max_length > 255 THEN
                SET v_max_length = 255;
            END IF;
        END IF;
        
        
        CASE v_tipo_campo
            WHEN 'int' THEN
                SET v_tipo_sql = 'INT';
            WHEN 'float' THEN
                SET v_tipo_sql = 'FLOAT';
            WHEN 'string' THEN
                SET v_tipo_sql = CONCAT('VARCHAR(', v_max_length, ')');
            ELSE
                SET v_tipo_sql = CONCAT('VARCHAR(', v_max_length, ')');
        END CASE;
        
        
        SELECT COUNT(*) INTO v_column_exists
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'casalai_principal' 
        AND TABLE_NAME = v_tabla_nueva 
        AND COLUMN_NAME = v_nombre_campo;
        
        IF v_column_exists = 0 THEN
            
            SET @add_sql = CONCAT('ALTER TABLE `', v_tabla_nueva, '` ADD COLUMN `', v_nombre_campo, '` ', v_tipo_sql);
            PREPARE stmt FROM @add_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ELSE
            
            SELECT COLUMN_TYPE INTO v_column_type
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = 'casalai_principal' 
            AND TABLE_NAME = v_tabla_nueva 
            AND COLUMN_NAME = v_nombre_campo;
            
            
            IF v_column_type != v_tipo_sql THEN
                SET @modify_sql = CONCAT('ALTER TABLE `', v_tabla_nueva, '` MODIFY COLUMN `', v_nombre_campo, '` ', v_tipo_sql);
                PREPARE stmt FROM @modify_sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;
        END IF;
        
        SET v_contador = v_contador + 1;
    END WHILE;
    
    
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Categor??as', 
        'MODIFICAR', 
        JSON_OBJECT('id_categoria', p_id_categoria, 'nombre_categoria', p_nuevo_nombre, 'tabla', v_tabla_nueva, 'caracteristicas', p_caracteristicas), 
        v_datos_viejos, 
        p_id_usuario_auditor,
        'media', 
        CONCAT('Se modific?? la categor??a: ', v_nombre_actual, ' -> ', p_nuevo_nombre)
    );
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_modificar_cliente` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modificar_cliente`(
    IN p_id_cliente INT,
    IN p_nombre VARCHAR(400),
    IN p_cedula VARCHAR(10),
    IN p_direccion TEXT,
    IN p_telefono VARCHAR(400),
    IN p_correo VARCHAR(550),
    IN p_activo TINYINT,
    IN p_id_usuario_auditor INT
)
BEGIN
    -- Declaración estricta de variables de respaldo histórico al inicio
    DECLARE v_nombre_viejo VARCHAR(255);
    DECLARE v_cedula_viejo VARCHAR(10);
    DECLARE v_direccion_viejo TEXT;
    DECLARE v_telefono_viejo VARCHAR(255);
    DECLARE v_correo_viejo VARCHAR(255);
    DECLARE v_activo_viejo TINYINT;

    -- Manejador general de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudieron actualizar los datos del cliente.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo exclusivo de fila y extracción del estado previo completo
    SELECT `nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo`
    INTO v_nombre_viejo, v_cedula_viejo, v_direccion_viejo, v_telefono_viejo, v_correo_viejo, v_activo_viejo
    FROM `tbl_clientes`
    WHERE `id_clientes` = p_id_cliente
    LIMIT 1 
    FOR UPDATE;

    -- Actualización física de la fila
    UPDATE `tbl_clientes` 
    SET `nombre` = p_nombre, 
        `cedula` = p_cedula, 
        `direccion` = p_direccion, 
        `telefono` = p_telefono, 
        `correo` = p_correo,
        `activo` = p_activo 
    WHERE `id_clientes` = p_id_cliente;

    -- Volcado síncrono a bitácora mapeando estados (JSON Viejo vs JSON Nuevo)
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(),
        'Clientes',
        'MODIFICAR',
        JSON_OBJECT('id_clientes', p_id_cliente, 'nombre', p_nombre, 'cedula', p_cedula, 'direccion', p_direccion, 'telefono', p_telefono, 'correo', p_correo, 'activo', p_activo),
        JSON_OBJECT('id_clientes', p_id_cliente, 'nombre', v_nombre_viejo, 'cedula', v_cedula_viejo, 'direccion', v_direccion_viejo, 'telefono', v_telefono_viejo, 'correo', v_correo_viejo, 'activo', v_activo_viejo),
        p_id_usuario_auditor, 
        'media',
        CONCAT('Se actualizaron los datos de contacto del cliente: ', p_nombre, '.')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_modificar_cuenta` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modificar_cuenta`(
    IN p_id_cuenta INT,
    IN p_nombre_banco VARCHAR(500),
    IN p_numero_cuenta VARCHAR(25),
    IN p_rif_cuenta VARCHAR(15),
    IN p_telefono_cuenta VARCHAR(500),
    IN p_correo_cuenta VARCHAR(500),
    IN p_metodos SET('Pago Movil','Transferencia','Zelle','Efectivo','Efectivo $'),
    IN p_id_usuario_auditor INT
)
BEGIN
    -- Agrupación estricta de declaraciones de variables al inicio del bloque
    DECLARE v_nombre_banco_viejo VARCHAR(500);
    DECLARE v_numero_cuenta_viejo VARCHAR(25);
    DECLARE v_rif_cuenta_viejo VARCHAR(15);
    DECLARE v_telefono_cuenta_viejo VARCHAR(500);
    DECLARE v_correo_cuenta_viejo VARCHAR(500);
    DECLARE v_metodos_viejo SET('Pago Movil','Transferencia','Zelle','Efectivo','Efectivo $');
    DECLARE v_estado_viejo ENUM('habilitado','inhabilitado');

    -- Manejador general de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudieron actualizar los datos de la cuenta.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo exclusivo de fila (FOR UPDATE) y extracción del estado histórico viejo
    SELECT `nombre_banco`, `numero_cuenta`, `rif_cuenta`, `telefono_cuenta`, `correo_cuenta`, `metodos`, `estado`
    INTO v_nombre_banco_viejo, v_numero_cuenta_viejo, v_rif_cuenta_viejo, v_telefono_cuenta_viejo, v_correo_cuenta_viejo, v_metodos_viejo, v_estado_viejo
    FROM `tbl_cuentas`
    WHERE `id_cuenta` = p_id_cuenta
    LIMIT 1 
    FOR UPDATE;

    -- Actualización física de la tupla
    UPDATE `tbl_cuentas` 
    SET `nombre_banco` = p_nombre_banco, 
        `numero_cuenta` = p_numero_cuenta, 
        `rif_cuenta` = p_rif_cuenta, 
        `telefono_cuenta` = p_telefono_cuenta, 
        `correo_cuenta` = p_correo_cuenta, 
        `metodos` = p_metodos 
    WHERE `id_cuenta` = p_id_cuenta;

    -- Volcado a bitácora mapeando los objetos JSON de estado antiguo y nuevo
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(),
        'Cuentas bancarias',
        'MODIFICAR',
        JSON_OBJECT('id_cuenta', p_id_cuenta, 'nombre_banco', p_nombre_banco, 'numero_cuenta', p_numero_cuenta, 'rif_cuenta', p_rif_cuenta, 'telefono_cuenta', p_telefono_cuenta, 'correo_cuenta', p_correo_cuenta, 'metodos', p_metodos, 'estado', v_estado_viejo),
        JSON_OBJECT('id_cuenta', p_id_cuenta, 'nombre_banco', v_nombre_banco_viejo, 'numero_cuenta', v_numero_cuenta_viejo, 'rif_cuenta', v_rif_cuenta_viejo, 'telefono_cuenta', v_telefono_cuenta_viejo, 'correo_cuenta', v_correo_cuenta_viejo, 'metodos', v_metodos_viejo, 'estado', v_estado_viejo),
        p_id_usuario_auditor, 
        'media',
        CONCAT('Se actualizaron los datos de la cuenta bancaria: ', p_nombre_banco, '.')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_modificar_marca` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modificar_marca`(
    IN p_id_marca INT,
    IN p_nombre_marca VARCHAR(25),
    IN p_id_usuario_auditor INT
)
BEGIN
    -- Variables para almacenar estados anteriores y validaciones
    DECLARE v_nombre_marca_viejo VARCHAR(25);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error al modificar la marca. Cambios revertidos.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    SELECT `nombre_marca` INTO v_nombre_marca_viejo 
    FROM `tbl_marcas` 
    WHERE `id_marca` = p_id_marca 
    FOR UPDATE;

    -- Actualización efectiva del registro
    UPDATE `tbl_marcas` 
    SET `nombre_marca` = p_nombre_marca 
    WHERE `id_marca` = p_id_marca;

    -- Registro detallado en bitácora mapeando el estado de antes y después
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    )
    VALUES (
        NOW(),
        'Marcas',
        'MODIFICAR',
        JSON_OBJECT('id_marca', p_id_marca, 'nombre_marca', p_nombre_marca),
        JSON_OBJECT('id_marca', p_id_marca, 'nombre_marca', v_nombre_marca_viejo),
        p_id_usuario_auditor,
        'media',
        CONCAT('Se modificó la marca con ID ', p_id_marca, '. Nombre anterior: "', v_nombre_marca_viejo, '", Nombre nuevo: "', p_nombre_marca, '".')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_modificar_modelo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modificar_modelo`(
    IN p_id_modelo INT,
    IN p_nombre_modelo VARCHAR(25),
    IN p_id_marca INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nombre_modelo_viejo VARCHAR(25);
    DECLARE v_id_marca_viejo INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno en el servidor de datos al modificar el modelo.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- CONCURRENCIA: Bloqueo de fila exclusivo para evitar lecturas sucias o sobreescritura asíncrona
    SELECT `nombre_modelo`, `id_marca` INTO v_nombre_modelo_viejo, v_id_marca_viejo
    FROM `tbl_modelos` 
    WHERE `id_modelo` = p_id_modelo 
    FOR UPDATE;

    -- Actualización de los campos
    UPDATE `tbl_modelos` 
    SET `nombre_modelo` = p_nombre_modelo, 
        `id_marca` = p_id_marca 
    WHERE `id_modelo` = p_id_modelo;

    -- Registro en la bitácora guardando estados anteriores y nuevos
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    )
    VALUES (
        NOW(),
        'Modelos',
        'MODIFICAR',
        JSON_OBJECT('id_modelo', p_id_modelo, 'nombre_modelo', p_nombre_modelo, 'id_marca', p_id_marca),
        JSON_OBJECT('id_modelo', p_id_modelo, 'nombre_modelo', v_nombre_modelo_viejo, 'id_marca', v_id_marca_viejo),
        p_id_usuario_auditor,
        'media',
        CONCAT('Se modificó el modelo con ID ', p_id_modelo, '. Nombre anterior: "', v_nombre_modelo_viejo, '", Nombre nuevo: "', p_nombre_modelo, '".')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_modificar_producto` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modificar_producto`(
    IN p_id_producto INT,
    IN p_serial VARCHAR(20),
    IN p_nombre_producto VARCHAR(20),
    IN p_descripcion_producto VARCHAR(255),
    IN p_id_modelo INT(11),
    IN p_stock INT(3),
    IN p_stock_minimo INT(3),
    IN p_stock_maximo INT(3),
    IN p_clausula_garantia VARCHAR(255),
    IN p_precio FLOAT(10,2),
    IN p_estado VARCHAR(20),
    IN p_nombre_categoria VARCHAR(20),
    IN p_id_usuario_auditor INT,
    IN p_caracteristicas JSON,
    IN p_imagen VARCHAR(255),
    OUT p_resultado INT
)
BEGIN
    DECLARE v_id_categoria INT;
    DECLARE v_nombre_categoria_normalizado VARCHAR(50);
    DECLARE v_nombre_tabla_caracteristicas VARCHAR(100);
    DECLARE v_nombre_categoria_actual VARCHAR(50);
    DECLARE v_nombre_tabla_caracteristicas_actual VARCHAR(100);

    -- Manejador de excepciones simplificado
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    SET v_nombre_categoria_normalizado = LOWER(TRIM(p_nombre_categoria));
    
    SET v_id_categoria = (SELECT id_categoria FROM tbl_categoria WHERE LOWER(TRIM(nombre_categoria)) = v_nombre_categoria_normalizado LIMIT 1);

    IF v_id_categoria IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La categoría especificada no existe.';
    END IF;

    -- Obtener categoría actual del producto
    SELECT c.nombre_categoria INTO v_nombre_categoria_actual
    FROM tbl_productos p
    LEFT JOIN tbl_categoria c ON p.id_categoria = c.id_categoria
    WHERE p.id_producto = p_id_producto;

    -- Si la categoría cambió, eliminar de la tabla anterior
    IF v_nombre_categoria_actual IS NOT NULL AND v_nombre_categoria_actual != v_nombre_categoria_normalizado THEN
        SET v_nombre_tabla_caracteristicas_actual = CONCAT('cat_', REPLACE(LOWER(v_nombre_categoria_actual), ' ', '_'));
        
        IF EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = v_nombre_tabla_caracteristicas_actual
        ) THEN
            SET @sql = CONCAT('DELETE FROM ', v_nombre_tabla_caracteristicas_actual, ' WHERE id_producto = ', p_id_producto);
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;

    -- Actualizar producto principal
    UPDATE tbl_productos 
    SET serial = p_serial,
        nombre_producto = p_nombre_producto,
        descripcion_producto = p_descripcion_producto,
        id_modelo = p_id_modelo,
        id_categoria = v_id_categoria,
        stock = p_stock,
        stock_minimo = p_stock_minimo,
        stock_maximo = p_stock_maximo,
        clausula_garantia = p_clausula_garantia,
        precio = p_precio,
        estado = p_estado,
        imagen = p_imagen
    WHERE id_producto = p_id_producto;

    -- Insertar o actualizar características en la nueva tabla de categoría
    SET v_nombre_tabla_caracteristicas = CONCAT('cat_', REPLACE(v_nombre_categoria_normalizado, ' ', '_'));
    
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = v_nombre_tabla_caracteristicas
    ) THEN
        IF p_caracteristicas IS NOT NULL AND JSON_LENGTH(p_caracteristicas) > 0 THEN
            -- Verificar si ya existe registro de características
            SET @sql = CONCAT('SELECT COUNT(*) FROM ', v_nombre_tabla_caracteristicas, ' WHERE id_producto = ', p_id_producto);
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usar la variable de sesión para obtener el resultado
            SET @existe = FOUND_ROWS();
            
            IF @existe > 0 THEN
                -- Actualizar características existentes
                SET @sql = CONCAT('UPDATE ', v_nombre_tabla_caracteristicas, ' SET ');
                
                SET @keys = JSON_KEYS(p_caracteristicas);
                SET @key_count = JSON_LENGTH(@keys);
                SET @i = 0;
                
                WHILE @i < @key_count DO
                    SET @key = JSON_UNQUOTE(JSON_EXTRACT(@keys, CONCAT('$[', @i, ']')));
                    SET @value = JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$.', @key)));
                    
                    IF @i > 0 THEN
                        SET @sql = CONCAT(@sql, ', ');
                    END IF;
                    
                    SET @sql = CONCAT(@sql, @key, ' = ', QUOTE(@value));
                    
                    SET @i = @i + 1;
                END WHILE;
                
                SET @sql = CONCAT(@sql, ' WHERE id_producto = ', p_id_producto);
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            ELSE
                -- Insertar nuevas características
                SET @sql = CONCAT('INSERT INTO ', v_nombre_tabla_caracteristicas, ' SET id_producto = ', p_id_producto);
                
                SET @keys = JSON_KEYS(p_caracteristicas);
                SET @key_count = JSON_LENGTH(@keys);
                SET @i = 0;
                
                WHILE @i < @key_count DO
                    SET @key = JSON_UNQUOTE(JSON_EXTRACT(@keys, CONCAT('$[', @i, ']')));
                    SET @value = JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$.', @key)));
                    
                    SET @sql = CONCAT(@sql, ', ', @key, ' = ', QUOTE(@value));
                    
                    SET @i = @i + 1;
                END WHILE;
                
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;
        ELSE
            -- Asegurar que exista un registro aunque no haya características
            SET @sql = CONCAT('INSERT IGNORE INTO ', v_nombre_tabla_caracteristicas, ' (id_producto) VALUES (', p_id_producto, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;

    INSERT INTO casalai_seguridad.tbl_bitacora (fecha_hora, nombre_modulo, accion, datos_nuevos, datos_viejos, id_usuario, prioridad, descripcion)
    VALUES (
        NOW(), 
        'Productos', 
        'MODIFICAR', 
        JSON_OBJECT('id_producto', p_id_producto, 'serial', p_serial, 'nombre_producto', p_nombre_producto, 'descripcion_producto', p_descripcion_producto, 'id_modelo', p_id_modelo, 'id_categoria', v_id_categoria, 'stock', p_stock, 'stock_minimo', p_stock_minimo, 'stock_maximo', p_stock_maximo, 'clausula_garantia', p_clausula_garantia, 'precio', p_precio, 'estado', p_estado, 'caracteristicas', p_caracteristicas, 'imagen', p_imagen), 
        NULL, 
        p_id_usuario_auditor,
        'media', 
        CONCAT('Se modificó el producto: ', p_nombre_producto, ' (ID: ', p_id_producto, ', Serial: ', p_serial, ', Categoría: ', p_nombre_categoria, ')')
    );

    COMMIT;
    SET p_resultado = 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_modificar_proveedor` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modificar_proveedor`(
    IN p_id_proveedor INT,
    IN p_nombre_proveedor VARCHAR(2048),
    IN p_rif_proveedor VARCHAR(15),
    IN p_nombre_representante VARCHAR(2048),
    IN p_rif_representante VARCHAR(15),
    IN p_correo_proveedor VARCHAR(2048),
    IN p_direccion_proveedor VARCHAR(2048),
    IN p_telefono_1 VARCHAR(2048),
    IN p_telefono_2 VARCHAR(2048),
    IN p_observacion TEXT,
    IN p_id_usuario_auditor INT
)
BEGIN
    -- Variables para la extracción forense de los datos anteriores
    DECLARE v_nombre_proveedor_viejo VARCHAR(2048);
    DECLARE v_rif_proveedor_viejo VARCHAR(15);
    DECLARE v_nombre_representante_viejo VARCHAR(2048);
    DECLARE v_rif_representante_viejo VARCHAR(15);
    DECLARE v_correo_proveedor_viejo VARCHAR(2048);
    DECLARE v_direccion_proveedor_viejo VARCHAR(2048);
    DECLARE v_telefono_1_viejo VARCHAR(2048);
    DECLARE v_telefono_2_viejo VARCHAR(2048);
    DECLARE v_observacion_viejo TEXT;
    DECLARE v_estado_viejo ENUM('habilitado','inhabilitado');

    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        ROLLBACK; 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudieron guardar los cambios del proveedor.'; 
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Bloqueo exclusivo de la fila (X-Lock) para resguardar la consistencia y capturar datos viejos
    SELECT 
        `nombre_proveedor`, `rif_proveedor`, `nombre_representante`, `rif_representante`, 
        `correo_proveedor`, `direccion_proveedor`, `telefono_1`, `telefono_2`, `observacion`, `estado`
    INTO 
        v_nombre_proveedor_viejo, v_rif_proveedor_viejo, v_nombre_representante_viejo, v_rif_representante_viejo, 
        v_correo_proveedor_viejo, v_direccion_proveedor_viejo, v_telefono_1_viejo, v_telefono_2_viejo, v_observacion_viejo, v_estado_viejo
    FROM `tbl_proveedores` 
    WHERE `id_proveedor` = p_id_proveedor 
    LIMIT 1 
    FOR UPDATE;

    -- Ejecución de la actualización física
    UPDATE `tbl_proveedores` SET 
        `nombre_proveedor` = p_nombre_proveedor,
        `rif_proveedor` = p_rif_proveedor,
        `nombre_representante` = p_nombre_representante,
        `rif_representante` = p_rif_representante,
        `correo_proveedor` = p_correo_proveedor,
        `direccion_proveedor` = p_direccion_proveedor,
        `telefono_1` = p_telefono_1,
        `telefono_2` = p_telefono_2,
        `observacion` = p_observacion
    WHERE `id_proveedor` = p_id_proveedor;

    -- Inserción en bitácora mapeando los cambios en formato JSON
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    ) VALUES (
        NOW(), 
        'Proveedores', 
        'MODIFICAR', 
        JSON_OBJECT(
            'id_proveedor', p_id_proveedor, 'nombre_proveedor', p_nombre_proveedor, 'rif_proveedor', p_rif_proveedor,
            'nombre_representante', p_nombre_representante, 'rif_representante', p_rif_representante,
            'correo_proveedor', p_correo_proveedor, 'direccion_proveedor', p_direccion_proveedor,
            'telefono_1', p_telefono_1, 'telefono_2', p_telefono_2, 'observacion', p_observacion, 'estado', v_estado_viejo
        ), 
        JSON_OBJECT(
            'id_proveedor', p_id_proveedor, 'nombre_proveedor', v_nombre_proveedor_viejo, 'rif_proveedor', v_rif_proveedor_viejo,
            'nombre_representante', v_nombre_representante_viejo, 'rif_representante', v_rif_representante_viejo,
            'correo_proveedor', v_correo_proveedor_viejo, 'direccion_proveedor', v_direccion_proveedor_viejo,
            'telefono_1', v_telefono_1_viejo, 'telefono_2', v_telefono_2_viejo, 'observacion', v_observacion_viejo, 'estado', v_estado_viejo
        ), 
        p_id_usuario_auditor, 
        'media', 
        CONCAT('Se actualizaron los datos comerciales del proveedor: "', p_nombre_proveedor, '" (ID: ', p_id_proveedor, ').')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_cliente_por_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_cliente_por_id`(
    IN p_id_cliente INT
)
BEGIN
    -- Manejador de fallas generales con mensaje personalizado
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo obtener la información del cliente.';
    END;

    SELECT * FROM `tbl_clientes` 
    WHERE `id_clientes` = p_id_cliente
    LIMIT 1
    FOR UPDATE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_cuenta_por_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_cuenta_por_id`(
    IN p_id_cuenta INT
)
BEGIN
    -- Manejador de fallas generales con mensaje personalizado
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo obtener la información de la cuenta bancaria.';
    END;

    SELECT * FROM `tbl_cuentas` 
    WHERE `id_cuenta` = p_id_cuenta
    LIMIT 1
    FOR UPDATE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_despacho_por_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_despacho_por_id`(
    IN p_id_despacho INT
)
BEGIN
    SELECT 
        r.id_despachos,
        r.fecha_despacho AS fecha,
        r.tipocompra,
        r.estado,
        r.id_clientes,
        c.nombre AS nombre_cliente,
        c.cedula AS cedula_cliente,
        SUM(d.cantidad) AS total_productos,
        SUM(d.cantidad * p.precio) AS valor_total
    FROM tbl_despachos AS r
    INNER JOIN tbl_despacho_detalle AS d ON d.id_despacho = r.id_despachos
    INNER JOIN tbl_clientes AS c ON c.id_clientes = r.id_clientes
    INNER JOIN tbl_productos AS p ON p.id_producto = d.id_producto
    WHERE r.id_despachos = p_id_despacho AND r.activo = 1
    GROUP BY r.id_despachos, r.fecha_despacho, r.tipocompra, r.estado, c.nombre, c.cedula;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_detalle_despacho` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_detalle_despacho`(
    IN p_id_despacho INT
)
BEGIN
    SELECT 
        p.id_producto AS codigo,
        p.nombre_producto AS producto,
        m.nombre_modelo AS modelo,
        mar.nombre_marca AS marca,
        p.serial,
        d.cantidad,
        d.id_detalle AS id_detalle,
        p.precio AS precio_unitario,
        (d.cantidad * p.precio) AS subtotal
    FROM tbl_despacho_detalle AS d
    INNER JOIN tbl_productos AS p ON p.id_producto = d.id_producto
    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo
    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
    WHERE d.id_despacho = p_id_despacho;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_marca_por_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_marca_por_id`(
    IN p_id_marca INT
)
BEGIN
    -- Manejador de fallas generales con mensaje personalizado
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo obtener la información de la marca.';
    END;

    SELECT * FROM `tbl_marcas` 
    WHERE `id_marca` = p_id_marca
    LIMIT 1
    FOR UPDATE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_modelo_por_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_modelo_por_id`(
    IN p_id_modelo INT
)
BEGIN
    SELECT 
        mo.`id_modelo`, 
        mo.`nombre_modelo`, 
        mo.`id_marca`,
        ma.`nombre_marca`
    FROM `tbl_modelos` mo
    LEFT JOIN `tbl_marcas` ma ON mo.`id_marca` = ma.`id_marca`
    WHERE mo.`id_modelo` = p_id_modelo
    LIMIT 1
    FOR UPDATE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_orden_despacho_por_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_orden_despacho_por_id`(
    IN p_id_orden INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se encontró la orden de despacho solicitada.'; 
    END;

    SELECT * FROM tbl_orden_despachos 
    WHERE id_orden_despachos = p_id_orden 
    LIMIT 1
    FOR UPDATE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_productos_factura_despacho` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_productos_factura_despacho`(
    IN p_id_factura INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudieron extraer los productos del despacho.'; 
    END;

    SELECT 
        p.imagen,
        p.id_producto AS codigo,
        p.nombre_producto AS producto,
        m.nombre_modelo AS modelo,
        mar.nombre_marca AS marca,
        p.serial,
        d.cantidad,
        d.id AS id_detalle,
        p.precio AS precio_unitario,
        (d.cantidad * p.precio) AS subtotal
    FROM tbl_factura_detalle AS d
    INNER JOIN tbl_productos AS p ON p.id_producto = d.id_producto
    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo
    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
    WHERE d.factura_id = p_id_factura;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_productos_recepcion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_productos_recepcion`(IN p_id_recepcion INT)
BEGIN
    SELECT p.id_producto AS codigo, p.nombre_producto AS nombre_producto,
           m.nombre_modelo AS modelo, mar.nombre_marca AS marca, p.serial,
           d.cantidad, d.costo
    FROM tbl_detalle_recepcion_productos d
    INNER JOIN tbl_productos p ON p.id_producto = d.id_producto
    INNER JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
    INNER JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
    WHERE d.id_recepcion = p_id_recepcion
    ORDER BY d.id_detalle_recepcion_productos;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_proveedor_por_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_proveedor_por_id`(
    IN p_id_proveedor INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo extraer la información del proveedor solicitado.'; 
    END;

    -- Consulta unitaria limpia
    SELECT * FROM `tbl_proveedores`
    WHERE `id_proveedor` = p_id_proveedor
    LIMIT 1 FOR UPDATE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_recepcion_por_correlativo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_recepcion_por_correlativo`(IN p_correlativo VARCHAR(255))
BEGIN
    SELECT r.id_recepcion, r.id_proveedor, p.nombre_proveedor, r.fecha, r.correlativo, r.estado,
           (SELECT COALESCE(SUM(d.cantidad * d.costo), 0)
            FROM tbl_detalle_recepcion_productos d WHERE d.id_recepcion = r.id_recepcion) AS costo_inversion,
           r.subtotal_factura, r.porcentaje_iva, r.monto_iva, r.total_factura
    FROM tbl_recepcion_productos r
    INNER JOIN tbl_proveedores p ON p.id_proveedor = r.id_proveedor
    WHERE r.correlativo = p_correlativo
    ORDER BY r.id_recepcion DESC
    LIMIT 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_obtener_ultima_recepcion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_ultima_recepcion`()
BEGIN
    SELECT r.id_recepcion, r.id_proveedor, p.nombre_proveedor, r.fecha, r.correlativo, r.estado,
           (SELECT COALESCE(SUM(d.cantidad * d.costo), 0)
            FROM tbl_detalle_recepcion_productos d WHERE d.id_recepcion = r.id_recepcion) AS costo_inversion,
           r.subtotal_factura, r.porcentaje_iva, r.monto_iva, r.total_factura
    FROM tbl_recepcion_productos r
    INNER JOIN tbl_proveedores p ON p.id_proveedor = r.id_proveedor
    ORDER BY r.id_recepcion DESC
    LIMIT 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_categoria` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_categoria`(
    IN p_nombre_categoria VARCHAR(255),
    IN p_caracteristicas JSON,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nuevo_id_categoria INT;
    DECLARE v_nombre_tabla VARCHAR(255);
    DECLARE v_sql_create TEXT;
    DECLARE v_contador INT DEFAULT 0;
    DECLARE v_total_caracteristicas INT;
    DECLARE v_nombre_campo VARCHAR(255);
    DECLARE v_tipo_campo VARCHAR(50);
    DECLARE v_max_length INT;
    DECLARE v_max_length_raw JSON;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
        @sqlstate = RETURNED_SQLSTATE, @errno = MYSQL_ERRNO, @text = MESSAGE_TEXT;
        SET @error_message = CONCAT('Error interno: ', @text, ' (SQLSTATE: ', @sqlstate, ', Errno: ', @errno, ')');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @error_message;
    END;

    SET v_nombre_tabla = CONCAT('cat_', LOWER(REPLACE(p_nombre_categoria, ' ', '_')));

    INSERT INTO `tbl_categoria` (`nombre_categoria`)
    VALUES (p_nombre_categoria);

    SET v_nuevo_id_categoria = LAST_INSERT_ID();

    SET v_sql_create = CONCAT('CREATE TABLE IF NOT EXISTS `', v_nombre_tabla, '` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_producto INT NOT NULL,');
    
    SET v_total_caracteristicas = JSON_LENGTH(p_caracteristicas);
    
    WHILE v_contador < v_total_caracteristicas DO
        SET v_nombre_campo = LOWER(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$[', v_contador, '].nombre'))), ' ', '_'));
        SET v_tipo_campo = JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$[', v_contador, '].tipo')));
        SET v_max_length_raw = JSON_EXTRACT(p_caracteristicas, CONCAT('$[', v_contador, '].max'));
        
        IF v_max_length_raw IS NULL THEN
            SET v_max_length = 255;
        ELSE
            SET v_max_length = CAST(JSON_UNQUOTE(v_max_length_raw) AS UNSIGNED);
            IF v_max_length = 0 OR v_max_length > 255 THEN
                SET v_max_length = 255;
            END IF;
        END IF;
        
        CASE v_tipo_campo
            WHEN 'int' THEN
                SET v_sql_create = CONCAT(v_sql_create, CONCAT(' `', v_nombre_campo, '` INT,'));
            WHEN 'float' THEN
                SET v_sql_create = CONCAT(v_sql_create, CONCAT(' `', v_nombre_campo, '` FLOAT,'));
            WHEN 'string' THEN
                SET v_sql_create = CONCAT(v_sql_create, CONCAT(' `', v_nombre_campo, '` VARCHAR(', v_max_length, '),'));
        END CASE;
        
        SET v_contador = v_contador + 1;
    END WHILE;
    
    SET v_sql_create = CONCAT(LEFT(v_sql_create, LENGTH(v_sql_create) - 1), 
        ', FOREIGN KEY (id_producto) REFERENCES tbl_productos(id_producto) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    
    SET @sql = v_sql_create;
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Categor??as', 
        'INCLUIR', 
        JSON_OBJECT('id_categoria', v_nuevo_id_categoria, 'nombre_categoria', p_nombre_categoria, 'tabla', v_nombre_tabla, 'caracteristicas', p_caracteristicas), 
        NULL, 
        p_id_usuario_auditor,
        'media', 
        CONCAT('Se incluy?? una nueva categor??a en el sistema: ', p_nombre_categoria)
    );
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_cliente` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_cliente`(
    IN p_nombre VARCHAR(400),
    IN p_cedula VARCHAR(10),
    IN p_direccion TEXT,
    IN p_telefono VARCHAR(400),
    IN p_correo VARCHAR(550),
    IN p_activo TINYINT(1),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nuevo_id_cliente INT;

    -- Manejador general de excepciones
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo procesar el registro del cliente.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Inserción física (Por defecto inicia activo = 1)
    INSERT INTO `tbl_clientes` (`nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo`)
    VALUES (p_nombre, p_cedula, p_direccion, p_telefono, p_correo, 1);

    -- Captura síncrona del ID asignado
    SET v_nuevo_id_cliente = LAST_INSERT_ID();

    -- Auditoría atómica en bitácora
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Clientes', 
        'INCLUIR', 
        JSON_OBJECT('id_clientes', v_nuevo_id_cliente, 'nombre', p_nombre, 'cedula', p_cedula, 'correo', p_correo, 'activo', 1), 
        NULL, 
        p_id_usuario_auditor,
        'media', 
        CONCAT('Se incluyó un nuevo cliente en el sistema: ', p_nombre, ' (C.I: ', p_cedula, ')')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_compra_presencial` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_compra_presencial`(
    IN p_id_cliente INT,
    IN p_productos JSON,
    IN p_pagos JSON,
    IN p_fecha DATE,
    IN p_id_usuario_auditor INT,
    OUT p_resultado JSON
)
BEGIN
    DECLARE v_id_despacho INT;
    DECLARE v_id_factura INT;
    DECLARE v_monto_total DECIMAL(10,2) DEFAULT 0;
    DECLARE v_descripcion TEXT DEFAULT 'Venta: ';
    DECLARE v_contador INT DEFAULT 0;
    DECLARE v_total_productos INT;
    DECLARE v_total_pagos INT;
    DECLARE v_id_producto INT;
    DECLARE v_cantidad INT;
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_nombre_producto VARCHAR(255);
    DECLARE v_nombre_modelo VARCHAR(255);
    DECLARE v_nombre_marca VARCHAR(255);
    DECLARE v_serial VARCHAR(100);
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_nombre_cliente VARCHAR(255);
    DECLARE v_cedula VARCHAR(50);
    DECLARE v_telefono VARCHAR(50);
    DECLARE v_correo VARCHAR(255);
    DECLARE v_pago_tipo VARCHAR(50);
    DECLARE v_pago_monto DECIMAL(10,2);
    DECLARE v_pago_referencia VARCHAR(100);
    DECLARE v_pago_id_cuenta INT;
    DECLARE v_pago_comprobante VARCHAR(255);
    DECLARE v_error_msg VARCHAR(500);
    DECLARE v_productos_json JSON DEFAULT '[]';
    DECLARE v_pagos_json JSON DEFAULT '[]';
    DECLARE v_producto_item JSON;
    DECLARE v_pago_item JSON;
    DECLARE v_debug_log TEXT DEFAULT '';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;

        SET v_debug_log = CONCAT(v_debug_log, '[ERROR] Código: ', @errno, ', Mensaje: ', @text);
        SET p_resultado = JSON_OBJECT(
            'status', 'error',
            'mensaje', CONCAT('Error interno: ', @text),
            'debug', v_debug_log,
            'codigo_error', @errno
        );
        ROLLBACK;
    END;

    IF p_id_usuario_auditor IS NULL OR p_id_usuario_auditor <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El usuario auditor de la venta no es válido.';
    END IF;

    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Iniciando lógica dentro de transacción existente\n');

    INSERT INTO `tbl_despachos` (`id_clientes`, `fecha_despacho`, `tipocompra`, `activo`)
    VALUES (p_id_cliente, p_fecha, 'Presencial', 1);
    SET v_id_despacho = LAST_INSERT_ID();
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Despacho creado ID: ', v_id_despacho, '\n');

    SET v_total_productos = JSON_LENGTH(p_productos);
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Total productos: ', v_total_productos, '\n');

    WHILE v_contador < v_total_productos DO
        SET v_id_producto = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_contador, '].id_producto')));
        SET v_cantidad = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_contador, '].cantidad')));
        SET v_cantidad = CAST(v_cantidad AS UNSIGNED);

        SET v_debug_log = CONCAT(v_debug_log, '[INFO] Procesando producto ID: ', v_id_producto, ', Cantidad: ', v_cantidad, '\n');

        INSERT INTO `tbl_despacho_detalle` (`id_despacho`, `id_producto`, `cantidad`)
        VALUES (v_id_despacho, v_id_producto, v_cantidad);

        SELECT p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial, p.precio
        INTO v_nombre_producto, v_nombre_modelo, v_nombre_marca, v_serial, v_precio
        FROM tbl_productos p
        INNER JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
        INNER JOIN tbl_marcas mar ON m.id_marca = mar.id_marca
        WHERE p.id_producto = v_id_producto
        FOR UPDATE;

        SET v_subtotal = v_precio * v_cantidad;
        SET v_monto_total = v_monto_total + v_subtotal;
        SET v_descripcion = CONCAT(v_descripcion, v_nombre_producto, ' (', v_cantidad, '), ');
        SET v_producto_item = JSON_OBJECT(
            'id_producto', v_id_producto,
            'codigo', v_id_producto,
            'nombre', v_nombre_producto,
            'modelo', v_nombre_modelo,
            'marca', v_nombre_marca,
            'serial', v_serial,
            'precio', v_precio,
            'cantidad', v_cantidad,
            'subtotal', v_subtotal
        );
        SET v_productos_json = JSON_ARRAY_APPEND(v_productos_json, '$', v_producto_item);
        SET v_debug_log = CONCAT(v_debug_log, '[INFO] Producto: ', v_nombre_producto, ', Subtotal: ', v_subtotal, '\n');
        SET v_contador = v_contador + 1;
    END WHILE;

    SET v_descripcion = TRIM(TRAILING ', ' FROM v_descripcion);
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Monto total: ', v_monto_total, '\n');

    INSERT INTO `tbl_facturas` (`cliente`, `fecha`, `descuento`, `estatus`)
    VALUES (p_id_cliente, p_fecha, 0, 'Pagada en Oficina');
    SET v_id_factura = LAST_INSERT_ID();
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Factura creada ID: ', v_id_factura, '\n');

    SET v_contador = 0;
    WHILE v_contador < v_total_productos DO
        SET v_id_producto = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_contador, '].id_producto')));
        SET v_cantidad = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_contador, '].cantidad')));
        SET v_cantidad = CAST(v_cantidad AS UNSIGNED);

        INSERT INTO `tbl_factura_detalle` (`factura_id`, `id_producto`, `cantidad`)
        VALUES (v_id_factura, v_id_producto, v_cantidad);
        SET v_contador = v_contador + 1;
    END WHILE;
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Detalles de factura insertados\n');

    SELECT nombre, cedula, telefono, correo
    INTO v_nombre_cliente, v_cedula, v_telefono, v_correo
    FROM tbl_clientes
    WHERE id_clientes = p_id_cliente;
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Cliente: ', v_nombre_cliente, '\n');

    IF p_pagos IS NOT NULL AND JSON_LENGTH(p_pagos) > 0 THEN
        SET v_contador = 0;
        SET v_total_pagos = JSON_LENGTH(p_pagos);

        WHILE v_contador < v_total_pagos DO
            SET v_pago_tipo = JSON_UNQUOTE(JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].tipo')));
            SET v_pago_monto = JSON_UNQUOTE(JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].monto')));
            SET v_pago_referencia = JSON_UNQUOTE(JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].referencia')));
            SET v_pago_id_cuenta = JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].cuenta'));
            SET v_pago_comprobante = JSON_UNQUOTE(JSON_EXTRACT(p_pagos, CONCAT('$[', v_contador, '].comprobante')));
            SET v_pago_monto = CAST(v_pago_monto AS DECIMAL(10,2));

            INSERT INTO `tbl_detalles_pago` (`id_factura`, `tipo`, `id_cuenta`, `referencia`, `monto`, `comprobante`, `fecha`)
            VALUES (v_id_factura, v_pago_tipo, v_pago_id_cuenta, v_pago_referencia, v_pago_monto, v_pago_comprobante, NOW());

            SET v_pago_item = JSON_OBJECT(
                'tipo', v_pago_tipo,
                'monto', v_pago_monto,
                'referencia', v_pago_referencia,
                'id_cuenta', v_pago_id_cuenta,
                'comprobante', v_pago_comprobante,
                'estatus', 'Pago Procesado'
            );
            SET v_pagos_json = JSON_ARRAY_APPEND(v_pagos_json, '$', v_pago_item);
            SET v_debug_log = CONCAT(v_debug_log, '[INFO] Pago registrado: ', v_pago_tipo, ', Monto: ', v_pago_monto, '\n');
            SET v_contador = v_contador + 1;
        END WHILE;
    END IF;

    INSERT INTO `tbl_ingresos_egresos` (`id_despacho`, `tipo`, `monto`, `descripcion`, `fecha`, `estado`)
    VALUES (v_id_despacho, 'ingreso', v_monto_total, CONCAT('Venta presencial #', v_id_factura, ' - ', v_descripcion), NOW(), 1);
    SET v_debug_log = CONCAT(v_debug_log, '[INFO] Ingreso registrado en finanzas\n');

    SET p_resultado = JSON_OBJECT(
        'status', 'success',
        'id_factura', v_id_factura,
        'fecha_factura', p_fecha,
        'id_despacho', v_id_despacho,
        'nombre_cliente', v_nombre_cliente,
        'cedula', v_cedula,
        'telefono', v_telefono,
        'correo', v_correo,
        'monto_total', v_monto_total,
        'descripcion', v_descripcion,
        'debug', v_debug_log
    );
    SET p_resultado = JSON_SET(p_resultado, '$.productos', v_productos_json);
    SET p_resultado = JSON_SET(p_resultado, '$.pagos', v_pagos_json);

    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`,
        `id_usuario`, `prioridad`, `descripcion`
    ) VALUES (
        NOW(),
        'Venta Presencial',
        'INCLUIR',
        JSON_OBJECT(
            'id_despacho', v_id_despacho,
            'id_factura', v_id_factura,
            'id_cliente', p_id_cliente,
            'nombre_cliente', v_nombre_cliente,
            'productos', JSON_EXTRACT(v_productos_json, '$'),
            'pagos', JSON_EXTRACT(v_pagos_json, '$'),
            'monto_total', v_monto_total,
            'fecha', p_fecha,
            'descripcion', v_descripcion
        ),
        NULL,
        p_id_usuario_auditor,
        'media',
        CONCAT(
            'Se registró una venta presencial #', v_id_factura,
            ' para el cliente "', IFNULL(v_nombre_cliente, 'N/A'), '".'
        )
    );
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_cuenta` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_cuenta`(
    IN p_nombre_banco VARCHAR(500),
    IN p_numero_cuenta VARCHAR(25),
    IN p_rif_cuenta VARCHAR(15),
    IN p_telefono_cuenta VARCHAR(500),
    IN p_correo_cuenta VARCHAR(500),
    IN p_metodos SET('Pago Movil','Transferencia','Zelle','Efectivo','Efectivo $'),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nueva_id_cuenta INT;

    -- Manejador general de excepciones transaccionales
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo registrar la cuenta bancaria.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Inserción física en la tabla (Inicia por defecto como 'habilitado')
    INSERT INTO `tbl_cuentas` 
    (`nombre_banco`, `numero_cuenta`, `rif_cuenta`, `telefono_cuenta`, `correo_cuenta`, `metodos`, `estado`)
    VALUES 
    (p_nombre_banco, p_numero_cuenta, p_rif_cuenta, p_telefono_cuenta, p_correo_cuenta, p_metodos, 'habilitado');

    -- Captura síncrona del ID autogenerado
    SET v_nueva_id_cuenta = LAST_INSERT_ID();

    -- Registro atómico en la bitácora de seguridad
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (`fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`)
    VALUES (
        NOW(), 
        'Cuentas bancarias', 
        'INCLUIR', 
        JSON_OBJECT('id_cuenta', v_nueva_id_cuenta, 'nombre_banco', p_nombre_banco, 'numero_cuenta', p_numero_cuenta, 'rif_cuenta', p_rif_cuenta, 'metodos', p_metodos, 'estado', 'habilitado'), 
        NULL, 
        p_id_usuario_auditor,
        'media', 
        CONCAT('Se registró una nueva cuenta bancaria: ', p_nombre_banco, ' (RIF: ', p_rif_cuenta, ')')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_marca` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_marca`(
    IN p_nombre_marca VARCHAR(25),
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_id_marca INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error al registrar la marca. Operación cancelada.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    INSERT INTO `tbl_marcas` (`nombre_marca`) 
    VALUES (p_nombre_marca);

    SET v_id_marca = LAST_INSERT_ID();

    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    )
    VALUES (
        NOW(),
        'Marcas',
        'INCLUIR',
        JSON_OBJECT('id_marca', v_id_marca, 'nombre_marca', TRIM(p_nombre_marca)),
        NULL,
        p_id_usuario_auditor,
        'media',
        CONCAT('El usuario incluyó una nueva marca en el sistema: "', TRIM(p_nombre_marca), '".')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_modelo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_modelo`(
    IN p_nombre_modelo VARCHAR(25),
    IN p_id_marca INT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_id_modelo INT;

    -- Manejador de excepciones para asegurar la atomicidad ante fallas del motor
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno en el servidor de datos al registrar el modelo.';
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Inserción directa (la validación de datos viene resuelta desde PHP)
    INSERT INTO `tbl_modelos` (`nombre_modelo`, `id_marca`) 
    VALUES (p_nombre_modelo, p_id_marca);

    SET v_id_modelo = LAST_INSERT_ID();

    -- Registro síncrono en la bitácora con mapeo estructurado JSON
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    )
    VALUES (
        NOW(),
        'Modelos',
        'INCLUIR',
        JSON_OBJECT('id_modelo', v_id_modelo, 'nombre_modelo', p_nombre_modelo, 'id_marca', p_id_marca),
        NULL,
        p_id_usuario_auditor,
        'media',
        CONCAT('El usuario incluyó un nuevo modelo en el sistema: "', p_nombre_modelo, '".')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_producto` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_producto`(
    IN p_serial VARCHAR(20),
    IN p_nombre_producto VARCHAR(20),
    IN p_descripcion_producto VARCHAR(255),
    IN p_id_modelo INT(11),
    IN p_stock INT(3),
    IN p_stock_minimo INT(3),
    IN p_stock_maximo INT(3),
    IN p_clausula_garantia VARCHAR(255),
    IN p_precio FLOAT(10,2),
    IN p_estado VARCHAR(20),
    IN p_nombre_categoria VARCHAR(20),
    IN p_id_usuario_auditor INT,
    IN p_caracteristicas JSON,
    IN p_imagen VARCHAR(255),
    OUT p_id_producto INT
)
BEGIN
    DECLARE v_id_categoria INT;
    DECLARE v_nombre_categoria_normalizado VARCHAR(50);
    DECLARE v_nombre_tabla_caracteristicas VARCHAR(100);

    -- Manejador de excepciones simplificado
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    SET v_nombre_categoria_normalizado = LOWER(TRIM(p_nombre_categoria));
    
    SET v_id_categoria = (SELECT id_categoria FROM tbl_categoria WHERE LOWER(TRIM(nombre_categoria)) = v_nombre_categoria_normalizado LIMIT 1);

    IF v_id_categoria IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La categoría especificada no existe.';
    END IF;

    INSERT INTO tbl_productos 
    (serial, nombre_producto, descripcion_producto, id_modelo, id_categoria, stock, stock_minimo, stock_maximo, clausula_garantia, precio, estado, imagen)
    VALUES 
    (p_serial, p_nombre_producto, p_descripcion_producto, p_id_modelo, v_id_categoria, p_stock, p_stock_minimo, p_stock_maximo, p_clausula_garantia, p_precio, p_estado, p_imagen);

    SET p_id_producto = LAST_INSERT_ID();

    SET v_nombre_tabla_caracteristicas = CONCAT('cat_', REPLACE(v_nombre_categoria_normalizado, ' ', '_'));
    
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = v_nombre_tabla_caracteristicas
    ) THEN
        IF p_caracteristicas IS NOT NULL AND JSON_LENGTH(p_caracteristicas) > 0 THEN
            SET @sql = CONCAT('INSERT INTO ', v_nombre_tabla_caracteristicas, ' SET id_producto = ', p_id_producto);
            
            SET @keys = JSON_KEYS(p_caracteristicas);
            SET @key_count = JSON_LENGTH(@keys);
            SET @i = 0;
            
            WHILE @i < @key_count DO
                SET @key = JSON_UNQUOTE(JSON_EXTRACT(@keys, CONCAT('$[', @i, ']')));
                SET @value = JSON_UNQUOTE(JSON_EXTRACT(p_caracteristicas, CONCAT('$.', @key)));
                
                SET @sql = CONCAT(@sql, ', ', @key, ' = ', QUOTE(@value));
                
                SET @i = @i + 1;
            END WHILE;
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ELSE
            SET @sql = CONCAT('INSERT INTO ', v_nombre_tabla_caracteristicas, ' (id_producto) VALUES (', p_id_producto, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;

    INSERT INTO casalai_seguridad.tbl_bitacora (fecha_hora, nombre_modulo, accion, datos_nuevos, datos_viejos, id_usuario, prioridad, descripcion)
    VALUES (
        NOW(), 
        'Productos', 
        'INCLUIR', 
        JSON_OBJECT('id_producto', p_id_producto, 'serial', p_serial, 'nombre_producto', p_nombre_producto, 'descripcion_producto', p_descripcion_producto, 'id_modelo', p_id_modelo, 'id_categoria', v_id_categoria, 'stock', p_stock, 'stock_minimo', p_stock_minimo, 'stock_maximo', p_stock_maximo, 'clausula_garantia', p_clausula_garantia, 'precio', p_precio, 'estado', p_estado, 'caracteristicas', p_caracteristicas, 'imagen', p_imagen), 
        NULL, 
        p_id_usuario_auditor,
        'media', 
        CONCAT('Se registró un nuevo producto: ', p_nombre_producto, ' (Serial: ', p_serial, ', Categoría: ', p_nombre_categoria, ')')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_proveedor` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_proveedor`(
    IN p_nombre_proveedor VARCHAR(2048),
    IN p_rif_proveedor VARCHAR(15),
    IN p_nombre_representante VARCHAR(2048),
    IN p_rif_representante VARCHAR(15),
    IN p_correo_proveedor VARCHAR(2048),
    IN p_direccion_proveedor VARCHAR(2048),
    IN p_telefono_1 VARCHAR(2048),
    IN p_telefono_2 VARCHAR(2048),
    IN p_observacion TEXT,
    IN p_id_usuario_auditor INT
)
BEGIN
    DECLARE v_nuevo_id INT;

    -- Manejador de fallas generales con reversión completa
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        ROLLBACK; 
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error interno: No se pudo registrar el proveedor de forma segura.'; 
    END;

    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;

    -- Inserción física en la tabla (Inicia habilitado por defecto)
    INSERT INTO `tbl_proveedores` (
        `nombre_proveedor`, `rif_proveedor`, `nombre_representante`, `rif_representante`, 
        `correo_proveedor`, `direccion_proveedor`, `telefono_1`, `telefono_2`, `observacion`
    ) VALUES (
        p_nombre_proveedor, p_rif_proveedor, p_nombre_representante, p_rif_representante, 
        p_correo_proveedor, p_direccion_proveedor, p_telefono_1, p_telefono_2, p_observacion
    );

    -- Captura atómica del ID asignado
    SET v_nuevo_id = LAST_INSERT_ID();

    -- Auditoría síncrona en la base de datos de seguridad
    INSERT INTO `casalai_seguridad`.`tbl_bitacora` (
        `fecha_hora`, `nombre_modulo`, `accion`, `datos_nuevos`, `datos_viejos`, `id_usuario`, `prioridad`, `descripcion`
    ) VALUES (
        NOW(), 
        'Proveedores', 
        'INCLUIR', 
        JSON_OBJECT(
            'id_proveedor', v_nuevo_id, 'nombre_proveedor', p_nombre_proveedor, 'rif_proveedor', p_rif_proveedor,
            'nombre_representante', p_nombre_representante, 'rif_representante', p_rif_representante,
            'correo_proveedor', p_correo_proveedor, 'direccion_proveedor', p_direccion_proveedor,
            'telefono_1', p_telefono_1, 'telefono_2', p_telefono_2, 'observacion', p_observacion
        ), 
        NULL, 
        p_id_usuario_auditor, 
        'media', 
        CONCAT('Se registró un nuevo proveedor en el sistema: "', p_nombre_proveedor, '" (RIF: ', IFNULL(p_rif_proveedor, 'N/A'), ').')
    );

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_registrar_recepcion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_recepcion`(
    IN p_id_proveedor INT,
    IN p_correlativo VARCHAR(255),
    IN p_estado VARCHAR(20),
    IN p_porcentaje_iva DECIMAL(7,2),
    IN p_productos JSON,
    IN p_id_usuario_auditor INT,
    OUT p_id_recepcion INT
)
BEGIN
    DECLARE v_fecha DATE;
    DECLARE v_indice INT DEFAULT 0;
    DECLARE v_cantidad_items INT DEFAULT 0;
    DECLARE v_id_producto INT;
    DECLARE v_cantidad INT;
    DECLARE v_costo DECIMAL(12,2);
    DECLARE v_nombre_producto VARCHAR(255);
    DECLARE v_producto_existe INT DEFAULT 0;
    DECLARE v_subtotal DECIMAL(12,2) DEFAULT 0;
    DECLARE v_monto_iva DECIMAL(12,2) DEFAULT 0;
    DECLARE v_total_factura DECIMAL(12,2) DEFAULT 0;
    DECLARE v_id_detalle INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_recepcion = NULL;
        RESIGNAL;
    END;

    SET p_id_recepcion = NULL;
    SET v_fecha = CURDATE();
    SET v_cantidad_items = JSON_LENGTH(p_productos);

    IF p_id_proveedor IS NULL OR NOT EXISTS (
        SELECT 1 FROM tbl_proveedores WHERE id_proveedor = p_id_proveedor
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El proveedor de la recepción no existe.';
    END IF;
    IF p_correlativo IS NULL OR TRIM(p_correlativo) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El número de factura es obligatorio.';
    END IF;
    IF EXISTS (
        SELECT 1 FROM tbl_recepcion_productos
        WHERE correlativo = p_correlativo AND estado = 'habilitado'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya existe una recepción activa con ese número de factura.';
    END IF;
    IF p_porcentaje_iva IS NULL OR p_porcentaje_iva < 0 OR p_porcentaje_iva > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El porcentaje de IVA debe estar entre 0 y 100.';
    END IF;
    IF p_estado NOT IN ('habilitado', 'anulado') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El estado de la recepción no es válido.';
    END IF;
    IF p_productos IS NULL OR JSON_VALID(p_productos) = 0 OR v_cantidad_items = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La recepción debe incluir al menos un producto.';
    END IF;

    START TRANSACTION;

    WHILE v_indice < v_cantidad_items DO
        SET v_id_producto = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].id_producto'))) AS UNSIGNED);
        SET v_cantidad = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].cantidad'))) AS UNSIGNED);
        SET v_costo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].costo'))) AS DECIMAL(12,2));

        SELECT COUNT(*), MAX(nombre_producto)
        INTO v_producto_existe, v_nombre_producto
        FROM tbl_productos
        WHERE id_producto = v_id_producto;

        IF v_producto_existe = 0 OR v_cantidad IS NULL OR v_cantidad < 1 OR v_costo IS NULL OR v_costo <= 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Uno de los productos, cantidades o costos de la recepción no es válido.';
        END IF;

        SET v_subtotal = v_subtotal + (v_cantidad * v_costo);
        SET v_indice = v_indice + 1;
    END WHILE;

    SET v_subtotal = ROUND(v_subtotal, 2);
    SET v_monto_iva = ROUND(v_subtotal * p_porcentaje_iva / 100, 2);
    SET v_total_factura = ROUND(v_subtotal + v_monto_iva, 2);

    INSERT INTO tbl_recepcion_productos
        (id_proveedor, fecha, correlativo, estado, subtotal_factura, porcentaje_iva, monto_iva, total_factura, stock_aplicado)
    VALUES
        (p_id_proveedor, v_fecha, TRIM(p_correlativo), p_estado, v_subtotal, p_porcentaje_iva, v_monto_iva, v_total_factura, IF(p_estado = 'habilitado', 1, 0));
    SET p_id_recepcion = LAST_INSERT_ID();

    SET v_indice = 0;
    WHILE v_indice < v_cantidad_items DO
        SET v_id_producto = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].id_producto'))) AS UNSIGNED);
        SET v_cantidad = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].cantidad'))) AS UNSIGNED);
        SET v_costo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_indice, '].costo'))) AS DECIMAL(12,2));

        INSERT INTO tbl_detalle_recepcion_productos (id_recepcion, id_producto, cantidad, costo)
        VALUES (p_id_recepcion, v_id_producto, v_cantidad, v_costo);
        SET v_id_detalle = LAST_INSERT_ID();

        IF p_estado = 'habilitado' THEN
            UPDATE tbl_productos
            SET stock = COALESCE(stock, 0) + v_cantidad
            WHERE id_producto = v_id_producto;
        END IF;

        SELECT nombre_producto INTO v_nombre_producto
        FROM tbl_productos
        WHERE id_producto = v_id_producto;

        INSERT INTO tbl_ingresos_egresos
            (tipo, monto, descripcion, fecha, estado, id_detalle_recepcion_productos)
        VALUES
            ('egreso', v_costo * v_cantidad, CONCAT('Compra: ', v_nombre_producto, ' (x', v_cantidad, ')'), v_fecha, 1, v_id_detalle);

        SET v_indice = v_indice + 1;
    END WHILE;

    INSERT INTO `casalai_seguridad`.`tbl_bitacora`
        (fecha_hora, nombre_modulo, accion, datos_nuevos, datos_viejos, id_usuario, prioridad, descripcion)
    VALUES
        (NOW(), 'Recepcion', 'INCLUIR',
         JSON_OBJECT('id_recepcion', p_id_recepcion, 'correlativo', p_correlativo, 'subtotal', v_subtotal,
                     'porcentaje_iva', p_porcentaje_iva, 'monto_iva', v_monto_iva, 'total_factura', v_total_factura),
         NULL, p_id_usuario_auditor, 'media', CONCAT('Se registró la recepción ', p_correlativo, '.'));

    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_reporte_productos_recepcion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_reporte_productos_recepcion`(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_id_proveedor INT
)
BEGIN
    SELECT pr.nombre_producto AS label, r.fecha, d.cantidad AS value, p.nombre_proveedor AS proveedor
    FROM tbl_detalle_recepcion_productos d
    INNER JOIN tbl_productos pr ON pr.id_producto = d.id_producto
    INNER JOIN tbl_recepcion_productos r ON r.id_recepcion = d.id_recepcion
    INNER JOIN tbl_proveedores p ON p.id_proveedor = r.id_proveedor
    WHERE (p_fecha_inicio IS NULL OR r.fecha >= p_fecha_inicio)
      AND (p_fecha_fin IS NULL OR r.fecha <= p_fecha_fin)
      AND (p_id_proveedor IS NULL OR p_id_proveedor = r.id_proveedor)
    ORDER BY r.fecha DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_reporte_recepciones_mensuales` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_reporte_recepciones_mensuales`(IN p_anio INT)
BEGIN
    SELECT MONTH(r.fecha) AS mes_num, YEAR(r.fecha) AS anio, COUNT(*) AS value
    FROM tbl_recepcion_productos r
    WHERE (p_anio IS NULL OR YEAR(r.fecha) = p_anio)
    GROUP BY YEAR(r.fecha), MONTH(r.fecha)
    ORDER BY anio, mes_num;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_reporte_recepciones_proveedor` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_reporte_recepciones_proveedor`(IN p_fecha_inicio DATE, IN p_fecha_fin DATE)
BEGIN
    SELECT p.nombre_proveedor AS label, r.fecha, r.id_recepcion, 1 AS value
    FROM tbl_recepcion_productos r
    INNER JOIN tbl_proveedores p ON p.id_proveedor = r.id_proveedor
    WHERE (p_fecha_inicio IS NULL OR r.fecha >= p_fecha_inicio)
      AND (p_fecha_fin IS NULL OR r.fecha <= p_fecha_fin)
    ORDER BY r.fecha DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Current Database: `casalai_principal`
--

USE `casalai_principal`;

--
-- Final view structure for view `vw_catalogo`
--

/*!50001 DROP VIEW IF EXISTS `vw_catalogo`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_catalogo` AS select 'producto' AS `tipo_item`,`p`.`id_producto` AS `id`,`p`.`nombre_producto` AS `nombre`,`p`.`descripcion_producto` AS `descripcion`,coalesce(`c`.`nombre_categoria`,'General') AS `categoria`,coalesce(`m`.`nombre_marca`,'Sin Marca') AS `marca`,`p`.`precio` AS `precio`,coalesce(`p`.`stock`,0) AS `stock`,case when `p`.`estado` in ('1',1,'habilitado','activo') then '1' else '0' end AS `estado`,`p`.`imagen` AS `imagen` from (((`tbl_productos` `p` left join `tbl_categoria` `c` on(`p`.`id_categoria` = `c`.`id_categoria`)) left join `tbl_modelos` `mo` on(`p`.`id_modelo` = `mo`.`id_modelo`)) left join `tbl_marcas` `m` on(`mo`.`id_marca` = `m`.`id_marca`)) union all select 'combo' AS `tipo_item`,`cb`.`id_combo` AS `id`,`cb`.`nombre_combo` AS `nombre`,`cb`.`descripcion` AS `descripcion`,'Combos' AS `categoria`,'CasaLai' AS `marca`,coalesce(sum(`p`.`precio` * `cd`.`cantidad`),0) AS `precio`,1 AS `stock`,case when `cb`.`activo` = 1 then '1' else '0' end AS `estado`,NULL AS `imagen` from ((`tbl_combo` `cb` left join `tbl_combo_detalle` `cd` on(`cb`.`id_combo` = `cd`.`id_combo`)) left join `tbl_productos` `p` on(`cd`.`id_producto` = `p`.`id_producto`)) group by `cb`.`id_combo`,`cb`.`nombre_combo`,`cb`.`descripcion`,`cb`.`activo` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-17 11:29:26
