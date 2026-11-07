-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: casalai
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `casalai`
--

USE `casalai`;

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: casalai
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
) ENGINE=InnoDB AUTO_INCREMENT=7080 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dolar_cache`
--

LOCK TABLES `dolar_cache` WRITE;
/*!40000 ALTER TABLE `dolar_cache` DISABLE KEYS */;
INSERT INTO `dolar_cache` VALUES (7079,226.1305,'2025-11-04 22:02:43');
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
  `estatus` varchar(20) NOT NULL,
  PRIMARY KEY (`id_carrito_detalle`),
  KEY `id_carrito` (`id_carrito`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `tbl_carritodetalle_ibfk_1` FOREIGN KEY (`id_carrito`) REFERENCES `tbl_carrito` (`id_carrito`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tbl_carritodetalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_carritodetalle`
--

LOCK TABLES `tbl_carritodetalle` WRITE;
/*!40000 ALTER TABLE `tbl_carritodetalle` DISABLE KEYS */;
INSERT INTO `tbl_carritodetalle` VALUES (43,11,34,1,'pendiente'),(44,11,40,1,'pendiente'),(45,11,32,1,'pendiente'),(46,11,41,1,'pendiente'),(47,11,30,1,'pendiente'),(55,10,32,1,'pendiente');
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
  `nombre` varchar(255) DEFAULT NULL,
  `cedula` varchar(10) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
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
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_combo`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_combo`
--

LOCK TABLES `tbl_combo` WRITE;
/*!40000 ALTER TABLE `tbl_combo` DISABLE KEYS */;
INSERT INTO `tbl_combo` VALUES (14,'COMBO ANIVERSARIO','TODO PARA IMPRIMIR','2025-09-02 01:59:53',1);
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
INSERT INTO `tbl_combo_detalle` VALUES (15,14,32,1),(16,14,41,1),(17,14,30,1);
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
  `nombre_banco` varchar(20) NOT NULL,
  `numero_cuenta` varchar(25) DEFAULT NULL,
  `rif_cuenta` varchar(15) NOT NULL,
  `telefono_cuenta` varchar(15) DEFAULT NULL,
  `correo_cuenta` varchar(50) DEFAULT NULL,
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
  CONSTRAINT `tbl_despachos_ibfk_1` FOREIGN KEY (`id_clientes`) REFERENCES `tbl_clientes` (`id_clientes`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_despachos`
--

LOCK TABLES `tbl_despachos` WRITE;
/*!40000 ALTER TABLE `tbl_despachos` DISABLE KEYS */;
INSERT INTO `tbl_despachos` VALUES (3,12,'2025-07-23','Presencial','Despachado',1),(4,12,'2025-09-01','Online','Despachado',1),(5,12,'2025-09-01','Presencial','Por Despachar',1),(7,12,'2025-10-12','','Por Despachar',1),(8,12,'2025-10-12','','Por Despachar',1),(9,12,'2025-10-12','','Por Despachar',1),(11,12,'2025-10-12','','Por Despachar',1),(12,12,'2025-10-13','','Por Despachar',1),(13,12,'2025-10-13','','Por Despachar',1),(14,12,'2025-10-13','','Por Despachar',1),(15,12,'2025-10-13','','Por Despachar',1),(16,12,'2025-10-13','','Por Despachar',1),(17,12,'2025-10-13','','Por Despachar',1),(18,12,'2025-10-13','','Por Despachar',1),(19,12,'2025-10-13','','Por Despachar',1),(20,12,'2025-10-13','','Por Despachar',1),(21,12,'2025-10-13','','Por Despachar',1),(22,12,'2025-10-13','','Por Despachar',1),(23,12,'2025-10-13','','Por Despachar',1),(24,12,'2025-10-13','','Por Despachar',1),(25,12,'2025-10-13','','Por Despachar',1),(26,12,'2025-10-13','','Por Despachar',1),(32,12,'2025-10-13','','Por Despachar',1),(34,12,'2025-10-13','','Por Despachar',1);
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
  `costo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detalle_recepcion_productos`),
  KEY `fk_detalle_recepcion` (`id_recepcion`),
  KEY `fk_detalle_producto` (`id_producto`),
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`),
  CONSTRAINT `fk_detalle_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `tbl_recepcion_productos` (`id_recepcion`),
  CONSTRAINT `tbl_detalles_recepcion_productos` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_detalle_recepcion_productos`
--

LOCK TABLES `tbl_detalle_recepcion_productos` WRITE;
/*!40000 ALTER TABLE `tbl_detalle_recepcion_productos` DISABLE KEYS */;
INSERT INTO `tbl_detalle_recepcion_productos` VALUES (12,10,33,20,2),(13,10,32,30,4),(14,11,32,2500,1),(15,11,29,123,1),(16,11,28,1,1);
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
  CONSTRAINT `fk_id_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `tbl_cuentas` (`id_cuenta`),
  CONSTRAINT `fk_id_factura` FOREIGN KEY (`id_factura`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_detalles_pago`
--

LOCK TABLES `tbl_detalles_pago` WRITE;
/*!40000 ALTER TABLE `tbl_detalles_pago` DISABLE KEYS */;
INSERT INTO `tbl_detalles_pago` VALUES (43,33,29,'','2423452','2025-08-28','Pago Movil',170000.00,'comprobante_1756429769_0.jpg','En Proceso'),(44,33,25,'','123456','2025-08-28','Transferencia',615.70,'comprobante_1756428648_0.jpg','En Proceso'),(45,35,25,'','1253257578','2025-09-01','Pago Movil',148444.00,'assets/img/uploads/comprobantes/1756772165_imagen_2025-09-01_201134160.png','Pago Procesado'),(46,36,27,'','098098','2025-09-01','Transferencia',448.40,'assets/img/uploads/comprobantes/1756773692_Imagen_de_WhatsApp_2025-08-06_a_las_20.29.40_9e358cd4-removebg-previ','Pago Procesado'),(47,33,30,'','998787','2025-10-12','Pago Movil',226488.95,'comprobante_1760307198_0.png','En Proceso'),(48,33,10,'','0979633','2025-10-12','Pago Movil',226488.95,'comprobante_1760308540_0.png','En Proceso'),(49,33,28,'','754353','2025-10-12','Pago Movil',226488.95,'comprobante_1760308787_0.png','En Proceso'),(50,60,30,'','884675465463','2025-10-13','Pago Movil',686.41,'assets/img/uploads/comprobantes/1760406723_Grafico_Despachos_Estado.png','En Proceso'),(52,62,30,'','8747857544','2025-10-13','Pago Movil',686.00,'assets/img/uploads/comprobantes/1760407024_Grafico_Despachos_Estado.png','En Proceso');
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
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  KEY `tbl_factura_detalle` (`id_producto`),
  CONSTRAINT `factura_detalle_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE,
  CONSTRAINT `tbl_factura_detalle` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_factura_detalle`
--

LOCK TABLES `tbl_factura_detalle` WRITE;
/*!40000 ALTER TABLE `tbl_factura_detalle` DISABLE KEYS */;
INSERT INTO `tbl_factura_detalle` VALUES (21,33,28,1),(22,35,28,1),(23,36,41,1),(24,37,32,3),(25,37,41,1),(26,37,30,1),(27,38,28,1),(28,38,32,1),(29,38,41,1),(30,38,30,1),(31,39,41,1),(32,40,41,1),(33,41,41,1),(34,42,28,1),(35,43,28,1),(36,44,31,1),(37,45,31,1),(38,46,31,1),(39,47,31,1),(40,48,31,1),(41,49,31,1),(42,50,31,1),(43,51,31,1),(44,52,31,1),(45,53,31,1),(46,54,41,1),(52,60,41,1),(54,62,41,1);
/*!40000 ALTER TABLE `tbl_factura_detalle` ENABLE KEYS */;
UNLOCK TABLES;

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
  CONSTRAINT `tbl_facturas_ibfk_1` FOREIGN KEY (`cliente`) REFERENCES `tbl_clientes` (`id_clientes`) ON DELETE CASCADE ON UPDATE CASCADE
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
  `monto` float(6,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha` date NOT NULL,
  `estado` int(1) NOT NULL,
  PRIMARY KEY (`id_finanzas`),
  KEY `id_despacho` (`id_despacho`,`id_detalle_recepcion_productos`),
  KEY `id_detalle_recepcion_productos` (`id_detalle_recepcion_productos`),
  CONSTRAINT `tbl_ingresos_egresos_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `tbl_despachos` (`id_despachos`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tbl_ingresos_egresos_ibfk_2` FOREIGN KEY (`id_detalle_recepcion_productos`) REFERENCES `tbl_detalle_recepcion_productos` (`id_detalle_recepcion_productos`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_ingresos_egresos`
--

LOCK TABLES `tbl_ingresos_egresos` WRITE;
/*!40000 ALTER TABLE `tbl_ingresos_egresos` DISABLE KEYS */;
INSERT INTO `tbl_ingresos_egresos` VALUES (8,NULL,12,'egreso',40.00,'Compra: ImpriColor (x2)','2025-07-22',1),(9,NULL,13,'egreso',120.00,'Compra: Tinta Arcoiris (x4)','2025-07-22',1),(10,3,NULL,'ingreso',18.00,'Venta: Colormedia (x1), Tinta Arcoiris (x1)','2025-07-23',1),(11,NULL,14,'egreso',2500.00,'Compra: Tinta Arcoiris (x1)','2025-07-27',1),(12,NULL,15,'egreso',123.00,'Compra: Impresora Maxi (x1)','2025-07-27',1),(13,NULL,16,'egreso',1.00,'Compra: Impresora Super (x1)','2025-07-27',1),(14,4,NULL,'ingreso',1000.00,'Venta: Impresora Super (x1)','2025-09-01',1),(15,5,NULL,'ingreso',3.00,'Venta: Rema de Papel  (x1)','2025-09-01',1);
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
INSERT INTO `tbl_marcas` VALUES (1,'Epson'),(2,'HP'),(3,'Canon'),(4,'Inktec'),(5,'TexPrint'),(6,'Sawgrass'),(7,'Cosmos Ink'),(8,'Azon'),(9,'Sublimagic'),(10,'Brother'),(11,'Forza'),(12,'Tripp Lite'),(13,'CDP'),(14,'Koblenz'),(15,'Epson'),(16,'HP'),(17,'Canon'),(18,'Inktec'),(19,'TexPrint'),(20,'Sawgrass'),(21,'Cosmos Ink'),(22,'Azon'),(23,'Sublimagic'),(24,'Brother'),(25,'Forza'),(26,'Tripp Lite'),(27,'CDP'),(28,'Koblenz'),(29,'Pokemon'),(30,'Digimon'),(31,'Nintendo');
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
INSERT INTO `tbl_modelos` VALUES (1,'L32508',NULL),(2,'L32106',NULL),(3,'L8055',NULL),(4,'L18001',NULL),(5,'L13001',NULL),(6,'F170911',2),(7,'F5709',2),(8,'Smart Tank 515',2),(9,'DeskJet 2775',2),(10,'LaserJet Pro M404dn',2),(11,'PIXMA G3110',3),(12,'PIXMA G6010',3),(13,'i-SENSYS MF445dw',3),(14,'Sublinova',4),(15,'SubliJet',6),(16,'L3250',1),(17,'L3210',1),(18,'L805',1),(19,'L1800',1),(20,'L1300',1),(21,'F170',1),(22,'F570',1),(23,'Smart Tank 515',2),(24,'DeskJet 2775',2),(25,'LaserJet Pro M404dn',2),(26,'PIXMA G3110',3),(27,'PIXMA G6010',3),(28,'i-SENSYS MF445dw',3),(29,'Sublinova',4),(30,'SubliJet',6),(31,'Sublime',8),(32,'Durabrite',15),(33,'Innobella',10),(34,'ChromaLife 100+',3),(35,'T664 ',1),(36,'T673 ',1),(37,'T774',1),(38,'664 ',2),(39,'662 ',2),(40,'680 ',2),(41,'955 ',2),(42,'950',2),(43,'PG-145 ',3),(44,'CL-146 ',3),(45,'GI-190',3),(46,'FVR-1211',11),(47,'FVR-2202',11),(48,'LR2000',12),(49,'AVR750U',12),(50,'R2-1200 ',13),(51,'UPS 600VA',13),(52,'1000VA',13),(53,'AVR-1000',14),(54,'520 Joules',14),(55,'Sublime',8),(56,'Durabrite',15),(57,'Innobella',10),(58,'ChromaLife 100+',3),(59,'T664 ',1),(60,'T673 ',1),(61,'T774',1),(62,'664 ',2),(63,'662 ',2),(64,'680 ',2),(65,'955 ',2),(66,'950',2),(67,'PG-145 ',3),(68,'CL-146 ',3),(69,'GI-190',3),(70,'FVR-1211',11),(71,'FVR-2202',11),(72,'LR2000',12),(73,'AVR750U',12),(74,'R2-1200 ',13),(75,'UPS 600VA',13),(76,'1000VA',13),(77,'AVR-1000',14),(78,'520 Joulesj',3),(79,'Ejemplo',3);
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_modulos`
--

LOCK TABLES `tbl_modulos` WRITE;
/*!40000 ALTER TABLE `tbl_modulos` DISABLE KEYS */;
INSERT INTO `tbl_modulos` VALUES (1,'Usuario'),(2,'Recepcion'),(3,'Despacho'),(4,'Marcas'),(5,'Modelos'),(6,'Productos'),(7,'Categorias'),(8,'Proveedores'),(9,'Clientes'),(10,'Catalogo'),(11,'Carrito'),(12,'Pasarela'),(13,'Prefactura'),(14,'Ordenes de despacho'),(15,'Cuentas bancarias'),(16,'Finanzas'),(17,'Permisos'),(18,'Roles'),(19,'Bitacora'),(20,'Backup');
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
INSERT INTO `tbl_orden_despachos` VALUES (3,33,'David Medina','2025-07-23','Entregada',1),(4,33,'David Medina','2025-07-24','Por Entregar',1),(5,33,'David Medina','2025-07-24','Entregada',1);
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
  `nombre_producto` varchar(20) NOT NULL,
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
  KEY `fk_producto_categoria` (`id_categoria`),
  KEY `fk_producto_modelo` (`id_modelo`),
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
INSERT INTO `tbl_productos` VALUES (28,'0001','EPSON EcoTank L3250','Impresora multifuncional imprime, copia, escanea y Wi‑Fi',16,11,50,10,100,'Garantia Valida hasta los 3 meses ',372.66,'habilitado','img\\productos\\producto_29.jpg'),(29,'0002','HP DeskJet 2775','Imprime, copia y escanea. Garantía de 1 año. Imprime desde el teléfono. Recarga fácil.',24,11,50,10,100,'Garantía para 1 mes',243.33,'habilitado','img\\productos\\producto_31.jpg'),(30,'0003','Cutter 360','Corta en todas direcciones. Ideal para manualidades.',31,15,50,10,100,'Garantía valida en los primeros 365 días',10.75,'habilitado','img\\productos\\producto_42.jpg'),(31,'0004','Pegamento Simbi para','Pegamento para papel. Ideal para reparar billetes.',32,15,20,10,50,'Sin Garantía',7.16,'habilitado','img\\productos\\producto_43.jpg'),(32,'0005','Cortadoras de papel','Cortadoras especiales para papelería.',33,15,20,5,50,'Sin Garantía',8.00,'habilitado','img\\productos\\producto_44.jpg'),(33,'0006','Resma de papel HP ca','Resmas de papel originales HP. Calidad en cada hoja, perfecta para oficina (tamaño carta).',31,15,30,10,70,'Sin Garantía',7.16,'habilitado','img\\productos\\producto_45.jpg'),(34,'0007','Pendrive Kingston 64','Unidad flash USB 3.2 Gen 1. Compatible y de alto rendimiento.',31,15,10,5,20,'Garantía de 1 mes de duración',7.16,'habilitado','img\\productos\\producto_46.jpg'),(35,'0008','Tarjeta SD Kingston ','Tarjeta microSD con adaptador. Velocidades de hasta 150MB/s en lectura.',31,15,7,5,20,'Garantía de 1 mes de duración',8.84,'habilitado','img\\productos\\producto_47.jpg'),(36,'0009','Pendrive Kingston 12','Unidad flash USB 3.2 Gen 1. Gran capacidad para tus archivos.',31,15,10,5,25,'Garantía de 1 mes de duración',10.96,'habilitado','img\\productos\\producto_48.jpg'),(37,'0010','Cable USB para impre','Cable USB para impresoras, dos metros de largo.',31,11,12,10,40,'Garantía de 1 mes de duración',7.16,'habilitado','img\\productos\\producto_49.jpg'),(38,'0011','Auriculares Redmi Bu','Sonido de alta fidelidad con cancelación de ruido.',31,15,16,5,20,'Garantía de 1 mes de duración',37.97,'habilitado','img\\productos\\producto_50.jpg'),(39,'0012','Cinta Epson S0156312','Cinta original para impresoras Epson LX300 y LX350.',31,15,7,3,15,'Garantía de 1 mes de duración',20.00,'habilitado','img\\productos\\producto_51.jpg'),(40,'0013','Tinta HP Original GT','Botella de tinta negra original HP GT52/GT53.',39,12,20,10,100,'Garantía de 1 mes de duración',31.52,'habilitado','img\\productos\\producto_52.jpg'),(41,'0014','Kit de tintas CasaLa','Kit de tintas para impresoras con sistema adaptado en CasaLai.',55,12,15,5,50,'Sin Garantia',57.32,'habilitado','img\\productos\\producto_53.jpg');
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
  `nombre_proveedor` varchar(50) NOT NULL,
  `rif_proveedor` varchar(15) DEFAULT NULL,
  `nombre_representante` varchar(50) DEFAULT NULL,
  `rif_representante` varchar(15) DEFAULT NULL,
  `correo_proveedor` varchar(50) DEFAULT NULL,
  `direccion_proveedor` text DEFAULT NULL,
  `telefono_1` varchar(15) DEFAULT NULL,
  `telefono_2` varchar(15) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `estado` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado',
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=1003 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_proveedores`
--

LOCK TABLES `tbl_proveedores` WRITE;
/*!40000 ALTER TABLE `tbl_proveedores` DISABLE KEYS */;
INSERT INTO `tbl_proveedores` VALUES (1,'Aliexpres','V-12332125-7','Brayan Mendoza','J-98778954-7','ejemplo@gmail.com','calle 32 con carrera 18 y 19','0412-258-8989','0424-654-4554','Buena calidad de productos, envio gratis','habilitado');
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
  `tamanocompra` enum('Pequeño','Mediano','Grande') NOT NULL,
  `estado` enum('habilitado','anulado') NOT NULL DEFAULT 'habilitado',
  PRIMARY KEY (`id_recepcion`),
  KEY `fk_recepcion_proveedor` (`id_proveedor`),
  CONSTRAINT `fk_recepcion_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `tbl_proveedores` (`id_proveedor`),
  CONSTRAINT `tbl_recepcion_productos` FOREIGN KEY (`id_proveedor`) REFERENCES `tbl_proveedores` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_recepcion_productos`
--

LOCK TABLES `tbl_recepcion_productos` WRITE;
/*!40000 ALTER TABLE `tbl_recepcion_productos` DISABLE KEYS */;
INSERT INTO `tbl_recepcion_productos` VALUES (10,1,'2025-07-22','1235','Mediano','habilitado'),(11,1,'2025-07-27','00012','Grande','habilitado');
/*!40000 ALTER TABLE `tbl_recepcion_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'casalai'
--

--
-- Dumping routines for database 'casalai'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-16  8:44:59
