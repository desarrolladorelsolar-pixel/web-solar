-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: solar_web
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

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
-- Table structure for table `apertura_caja`
--

DROP TABLE IF EXISTS `apertura_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apertura_caja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caja_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL,
  `monto_inicial` decimal(10,2) DEFAULT 0.00,
  `estado` enum('abierta','cerrada','anulada') DEFAULT 'abierta',
  `observaciones_apertura` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `caja_id` (`caja_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `apertura_caja_ibfk_1` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`),
  CONSTRAINT `apertura_caja_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apertura_caja`
--

LOCK TABLES `apertura_caja` WRITE;
/*!40000 ALTER TABLE `apertura_caja` DISABLE KEYS */;
/*!40000 ALTER TABLE `apertura_caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cajas`
--

DROP TABLE IF EXISTS `cajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cajas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sucursal_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `sucursal_id` (`sucursal_id`),
  CONSTRAINT `cajas_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cajas`
--

LOCK TABLES `cajas` WRITE;
/*!40000 ALTER TABLE `cajas` DISABLE KEYS */;
/*!40000 ALTER TABLE `cajas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `icono` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Combos del dia',NULL,1),(2,'combos del mes','',1),(3,'platos','',1);
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cierre_caja`
--

DROP TABLE IF EXISTS `cierre_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cierre_caja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apertura_caja_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_cierre` datetime NOT NULL,
  `monto_esperado` decimal(10,2) NOT NULL,
  `monto_real` decimal(10,2) NOT NULL,
  `diferencia` decimal(10,2) GENERATED ALWAYS AS (`monto_real` - `monto_esperado`) STORED,
  `observaciones_cierre` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `apertura_caja_id` (`apertura_caja_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `cierre_caja_ibfk_1` FOREIGN KEY (`apertura_caja_id`) REFERENCES `apertura_caja` (`id`),
  CONSTRAINT `cierre_caja_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cierre_caja`
--

LOCK TABLES `cierre_caja` WRITE;
/*!40000 ALTER TABLE `cierre_caja` DISABLE KEYS */;
/*!40000 ALTER TABLE `cierre_caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `tipo_documento` varchar(50) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Esteban','esteban@gmail.com','141414134','14043455','CI','an;dovbdnvdvdsvdsvsd','2026-07-15 07:40:28',1),(2,'javi','javi@gmail.com','65472472726','9721137','CI','r4tqrthfgsntynrttr','2026-07-15 07:41:09',1),(4,'andres','andres@gmail.com','67517377','140434','CI','rebFSNNF','2026-07-15 08:44:32',1),(5,'LIZETH','lizeth@gmail.com','7656767','123456','CI','btrbtrhtrtrhtr','2026-07-15 09:44:38',1),(6,'naty','naty@gmail.com','43414314','111111','CI','wfewfewfewf','2026-07-15 13:15:51',1),(7,'ESPINOZA MALLON NOEMI',NULL,NULL,'8226062',NULL,NULL,'2026-07-16 06:35:59',1),(8,'MORON ROCA JAMIL GUILLERMO',NULL,NULL,'8253193',NULL,NULL,'2026-07-16 06:39:25',1),(9,'CUELLAR ADOLFO PERFECTO',NULL,NULL,'3884991',NULL,NULL,'2026-07-16 06:41:27',1),(10,'MACIAS FIGUEROA PABLO EDUARDO',NULL,NULL,'8976732',NULL,NULL,'2026-07-16 07:21:07',1),(11,'Estudiante UAGRM',NULL,NULL,'2021110109',NULL,NULL,'2026-07-16 08:27:08',1),(12,'Estudiante UAGRM',NULL,NULL,'2021115381',NULL,NULL,'2026-07-16 08:34:34',1),(13,'Estudiante UPSA',NULL,NULL,'2025116993',NULL,NULL,'2026-07-16 11:59:35',1),(14,'Estudiante UAGRM',NULL,NULL,'2021116697',NULL,NULL,'2026-07-16 12:12:56',1),(15,'Estudiante UAGRM',NULL,NULL,'2017117994',NULL,NULL,'2026-07-16 12:13:22',1),(16,'Estudiante UAGRM',NULL,NULL,'2024115349',NULL,NULL,'2026-07-18 06:38:51',1);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cupon_uso`
--

DROP TABLE IF EXISTS `cupon_uso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cupon_uso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cupon_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `cliente_nombre` varchar(150) DEFAULT NULL,
  `cliente_ci` varchar(20) DEFAULT NULL,
  `fecha_uso` datetime DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cupon_id` (`cupon_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `cupon_uso_ibfk_1` FOREIGN KEY (`cupon_id`) REFERENCES `cupones` (`id`),
  CONSTRAINT `cupon_uso_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `cupon_uso_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cupon_uso`
--

LOCK TABLES `cupon_uso` WRITE;
/*!40000 ALTER TABLE `cupon_uso` DISABLE KEYS */;
INSERT INTO `cupon_uso` VALUES (12,21,1,1,'Esteban','14043455','2026-07-15 12:06:53',''),(13,22,1,4,'andres','140434','2026-07-15 12:25:29',''),(14,23,1,2,'javi','9721137','2026-07-15 12:25:58',''),(15,24,1,5,'LIZETH','123456','2026-07-15 12:31:43',''),(16,25,1,6,'naty','111111','2026-07-15 14:09:21',''),(22,31,1,7,'ESPINOZA MALLON NOEMI','8226062','2026-07-16 06:35:59',''),(24,33,1,8,'MORON ROCA JAMIL GUILLERMO','8253193','2026-07-16 06:39:25',''),(25,34,1,9,'CUELLAR ADOLFO PERFECTO','3884991','2026-07-16 06:41:27',''),(28,37,1,10,'MACIAS FIGUEROA PABLO EDUARDO','8976732','2026-07-16 07:21:07','dgergerge'),(29,38,1,10,'MACIAS FIGUEROA PABLO EDUARDO','8976732','2026-07-16 07:21:43','gregergererg'),(32,32,1,NULL,'Estudiante UPSA','2024115349','2026-07-16 08:20:58','Generado desde convenio UPSA'),(33,35,1,11,'Estudiante UAGRM','2021110109','2026-07-16 08:27:08','wrgergreger'),(34,36,1,NULL,'Estudiante UAGRM','2021110109','2026-07-16 08:27:22','Generado desde convenio UAGRM'),(35,39,1,12,'Estudiante UAGRM','2021115381','2026-07-16 08:34:34',''),(36,40,1,12,'Estudiante UAGRM','2021115381','2026-07-16 08:34:57',''),(37,41,1,NULL,'Estudiante UAGRM','2025110511','2026-07-16 09:09:19','Generado desde convenio UAGRM'),(38,42,1,13,'Estudiante UPSA','2025116993','2026-07-16 11:59:35',''),(39,43,1,13,'Estudiante UPSA','2025116993','2026-07-16 11:59:57',''),(40,44,1,NULL,'Estudiante UPSA','2025115831','2026-07-16 12:02:37','Generado desde convenio UPSA'),(41,45,1,NULL,'Estudiante UPSA','2022111338','2026-07-16 12:10:37','Generado desde convenio UPSA'),(42,46,1,NULL,'Estudiante UAGRM','2024115349','2026-07-16 12:11:42','Generado desde convenio UAGRM'),(43,47,1,14,'Estudiante UAGRM','2021116697','2026-07-16 12:12:56',''),(44,48,1,15,'Estudiante UAGRM','2017117994','2026-07-16 12:13:22',''),(45,49,1,15,'Estudiante UAGRM','2017117994','2026-07-16 12:13:39',''),(46,50,1,NULL,'Estudiante UAGRM','2022117034','2026-07-16 12:14:10','Generado desde convenio UAGRM'),(47,51,1,NULL,'Estudiante UAGRM','2026118345','2026-07-16 12:41:52','Generado desde convenio UAGRM'),(48,52,1,NULL,'Estudiante UAGRM','2026118159','2026-07-16 12:45:55','Generado desde convenio UAGRM'),(49,53,1,NULL,'Estudiante UPSA','2024114911','2026-07-16 12:46:35','Generado desde convenio UPSA'),(50,93,1,16,'Estudiante UAGRM','2024115349','2026-07-18 06:38:51','');
/*!40000 ALTER TABLE `cupon_uso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cupones`
--

DROP TABLE IF EXISTS `cupones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cupones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `tipo_cupon_id` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `tipo_cupon_id` (`tipo_cupon_id`),
  KEY `idx_busqueda` (`codigo`,`estado`,`usado`,`fecha_expiracion`),
  CONSTRAINT `cupones_ibfk_1` FOREIGN KEY (`tipo_cupon_id`) REFERENCES `tipo_cupon` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cupones`
--

LOCK TABLES `cupones` WRITE;
/*!40000 ALTER TABLE `cupones` DISABLE KEYS */;
INSERT INTO `cupones` VALUES (21,'MST2Z7',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',1,0,'2026-07-15 12:04:33'),(22,'VUCYPB',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',1,1,'2026-07-15 12:04:33'),(23,'FN4FQS',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',1,1,'2026-07-15 12:04:33'),(24,'QZJCEC',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',1,1,'2026-07-15 12:04:33'),(25,'YTFF4E',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',1,1,'2026-07-15 12:04:33'),(26,'9JWC75',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',0,0,'2026-07-15 12:04:33'),(27,'KS54F3',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',0,0,'2026-07-15 12:04:33'),(28,'MNDJDQ',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',0,0,'2026-07-15 12:04:33'),(29,'C77D7F',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',0,0,'2026-07-15 12:04:33'),(30,'M4D2BT',1,'Cupón convenio diario','2026-07-15 00:00:00','2026-07-15 23:59:59',0,0,'2026-07-15 12:04:33'),(31,'27NG27',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 05:37:36'),(32,'R55P5E',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 05:37:36'),(33,'TVPA82',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 05:37:36'),(34,'M5XHQ7',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 05:37:36'),(35,'BGRMK9',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 05:37:36'),(36,'3ATKH5',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 05:37:36'),(37,'VYQD94',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 05:37:36'),(38,'R47T5P',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 05:37:36'),(39,'VKRS9Y',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 05:37:36'),(40,'G28E89',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 05:37:36'),(41,'V2A9LM',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(42,'4L5DRZ',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 06:22:21'),(43,'DBWUAY',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 06:22:21'),(44,'BA862X',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(45,'R7PYP6',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(46,'9EAZFU',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(47,'679GEJ',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 06:22:21'),(48,'35YK9P',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 06:22:21'),(49,'HLMQ75',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',1,1,'2026-07-16 06:22:21'),(50,'EBEDU6',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(51,'ZE76KL',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(52,'QPRPYV',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(53,'3ELMV2',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(54,'RL43XX',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(55,'9CGVWC',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(56,'3HCD9R',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(57,'BDAAY6',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(58,'DL39L4',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(59,'345TC3',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(60,'F8FQ47',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(61,'7AMW5F',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(62,'3VXPEV',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(63,'HJ2NSU',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(64,'PY9SJT',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(65,'J8HWBL',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(66,'T9TU7W',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(67,'6EQYNW',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(68,'8PZ4WG',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(69,'ANDG3G',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(70,'VH9BUA',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(71,'KA6EN8',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(72,'MGLNZ8',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(73,'MURJ6U',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(74,'FC56YC',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(75,'YMXRCC',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(76,'MYRGTA',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(77,'TLWGMR',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(78,'JJCYW6',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(79,'CF3S65',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(80,'HVDTP9',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(81,'RZSBYX',1,'Cupón convenio diario','2026-07-16 00:00:00','2026-07-16 23:59:59',0,0,'2026-07-16 06:22:21'),(82,'TA2N9E',1,'decuento 8%','2026-07-16 06:35:00','2026-07-16 23:59:00',0,0,'2026-07-16 06:35:26'),(83,'9GAQU5',1,'decuento 8%','2026-07-16 06:35:00','2026-07-16 23:59:00',0,0,'2026-07-16 06:35:26'),(84,'AYHXGV',1,'decuento 8%','2026-07-16 06:35:00','2026-07-16 23:59:00',0,0,'2026-07-16 06:35:26'),(85,'XALWA9',1,'decuento 8%','2026-07-16 06:35:00','2026-07-16 23:59:00',0,0,'2026-07-16 06:35:26'),(86,'N6SYJ4',1,'decuento 8%','2026-07-16 06:35:00','2026-07-16 23:59:00',0,0,'2026-07-16 06:35:26'),(87,'DB65C4',1,'descuento del 100%','2026-07-16 07:20:00','2026-07-16 23:59:00',0,0,'2026-07-16 07:20:20'),(88,'9MAPAV',1,'descuento del 100%','2026-07-16 07:20:00','2026-07-16 23:59:00',0,0,'2026-07-16 07:20:20'),(89,'5ETXER',1,'descuento del 100%','2026-07-16 07:20:00','2026-07-16 23:59:00',0,0,'2026-07-16 07:20:20'),(90,'5488NT',1,'descuento del 100%','2026-07-16 07:20:00','2026-07-16 23:59:00',0,0,'2026-07-16 07:20:20'),(91,'CCXEGB',1,'descuento del 100%','2026-07-16 07:20:00','2026-07-16 23:59:00',0,0,'2026-07-16 07:20:20'),(92,'WAY2MA',1,'descuento del 100%','2026-07-16 07:20:00','2026-07-16 23:59:00',0,0,'2026-07-16 07:20:20'),(93,'NLG78D',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',1,1,'2026-07-18 06:38:04'),(94,'ZTYJXM',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(95,'DFTGDZ',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(96,'JB6WET',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(97,'KBGF7J',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(98,'AEBGUS',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(99,'83ZKRX',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(100,'A5DFUT',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(101,'PZEG2E',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(102,'5XY7PC',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(103,'W9FL4Q',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(104,'8FE6PB',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(105,'52FYM4',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(106,'RXR39L',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(107,'G26CJB',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(108,'X66SE8',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(109,'LLM8F9',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(110,'F5HKW8',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(111,'QN8GNZ',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(112,'4NWCZ3',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(113,'M8HES5',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(114,'D9RXWJ',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(115,'76N7TY',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(116,'FFJ6FG',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(117,'YFT6CJ',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(118,'68UB8Y',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(119,'JDVXBC',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(120,'QSAKXH',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(121,'GKBU7D',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04'),(122,'4JGA96',1,'100%','2026-07-18 06:37:00','2026-08-17 06:37:00',0,1,'2026-07-18 06:38:04');
/*!40000 ALTER TABLE `cupones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_actividad`
--

DROP TABLE IF EXISTS `logs_actividad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs_actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `logs_actividad_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_actividad`
--

LOCK TABLES `logs_actividad` WRITE;
/*!40000 ALTER TABLE `logs_actividad` DISABLE KEYS */;
INSERT INTO `logs_actividad` VALUES (1,1,'INSERTAR_CLIENTE','Se creó el cliente: Esteban','::1','2026-07-15 07:40:28'),(2,1,'INSERTAR_CLIENTE','Se creó el cliente: javi','::1','2026-07-15 07:41:09'),(3,1,'INSERTAR_CLIENTE','Se creó el cliente: andres','::1','2026-07-15 07:41:28'),(4,1,'INSERTAR_CATEGORIA','Se creó la categoría: combos del mes','::1','2026-07-15 07:42:46'),(5,1,'INSERTAR_CATEGORIA','Se creó la categoría: platos','::1','2026-07-15 07:42:53'),(6,1,'INSERTAR_CUPONES_MASIVOS','Se crearon 3 cupones del tipo ID: 1 - Descripción: 8% en cuarto de pollo','::1','2026-07-15 08:04:58'),(7,1,'EDITAR_CLIENTE','Se actualizó el cliente: Esteban (ID: 1)','::1','2026-07-15 08:06:06'),(8,1,'CANJEAR_CUPON','Cupón canjeado: Código \'6QEPUT\' (ID: 3) - Cliente: Esteban (CI: 14043455)','::1','2026-07-15 08:06:51'),(9,1,'EDITAR_CLIENTE','Se actualizó el cliente: javi (ID: 2)','::1','2026-07-15 08:07:42'),(10,1,'EDITAR_CLIENTE','Se actualizó el cliente: andres (ID: 3)','::1','2026-07-15 08:07:54'),(11,1,'EDITAR_CLIENTE','Se actualizó el cliente: andres (ID: 3)','::1','2026-07-15 08:44:07'),(12,1,'INSERTAR_CLIENTE','Se creó el cliente: andres','::1','2026-07-15 08:44:32'),(13,1,'CANJEAR_CUPON','Cupón canjeado: \'MYPY54\' (ID: 1) — CI: 140434 — Cajero ID: 1','::1','2026-07-15 08:50:31'),(14,1,'EDITAR_CLIENTE','Se actualizó el cliente: javi (ID: 2)','::1','2026-07-15 09:07:25'),(15,1,'EDITAR_CLIENTE','Se actualizó el cliente: javi (ID: 2)','::1','2026-07-15 09:07:54'),(16,1,'CANJEAR_CUPON','Cupón canjeado: \'8TNWJ5\' (ID: 2) — CI: 9721137 — Cajero ID: 1','::1','2026-07-15 09:09:56'),(17,1,'INSERTAR_CLIENTE','Se creó el cliente: LIZETH','::1','2026-07-15 09:44:38'),(18,1,'INSERTAR_CUPONES_MASIVOS','Se crearon 2 cupones del tipo ID: 2 - Descripción: 8% de descuento','::1','2026-07-15 09:46:25'),(19,1,'CANJEAR_CUPON','Cupón canjeado: \'98OKPF\' (ID: 4) — CI: 123456 — Cajero ID: 1','::1','2026-07-15 09:47:50'),(20,1,'INSERTAR_CUPONES_MASIVOS','Se crearon 2 cupones del tipo ID: 2 - Descripción: vale por un economico','::1','2026-07-15 10:02:31'),(21,1,'ELIMINAR_CUPON','Cupón ID: 4 eliminado (estado=0)','::1','2026-07-15 11:12:21'),(22,1,'ELIMINAR_CUPON','Cupón ID: 5 eliminado (estado=0)','::1','2026-07-15 11:12:28'),(23,1,'ELIMINAR_CUPON','Cupón ID: 7 eliminado (estado=0)','::1','2026-07-15 11:12:29'),(24,1,'ELIMINAR_CUPON','Cupón ID: 6 eliminado (estado=0)','::1','2026-07-15 11:12:31'),(25,1,'ELIMINAR_CUPON','Cupón ID: 3 eliminado (estado=0)','::1','2026-07-15 11:12:34'),(26,1,'ELIMINAR_CUPON','Cupón ID: 2 eliminado (estado=0)','::1','2026-07-15 11:12:36'),(27,1,'ELIMINAR_CUPON','Cupón ID: 1 eliminado (estado=0)','::1','2026-07-15 11:12:38'),(28,1,'INSERTAR_CUPONES_MASIVOS','Se crearon 3 cupones del tipo ID: 1 - Descripción: 9% en cuarto de pollo','::1','2026-07-15 11:13:16'),(29,1,'CANJEAR_CUPON','Cupón canjeado: \'MST2Z7\' (ID: 21) — CI: 14043455 — Cajero ID: 1','::1','2026-07-15 12:06:53'),(30,1,'CANJEAR_CUPON','Cupón canjeado: \'VUCYPB\' (ID: 22) — CI: 140434 — Cajero ID: 1','::1','2026-07-15 12:25:29'),(31,1,'CANJEAR_CUPON','Cupón canjeado: \'FN4FQS\' (ID: 23) — CI: 9721137 — Cajero ID: 1','::1','2026-07-15 12:25:58'),(32,1,'CANJEAR_CUPON','Cupón canjeado: \'QZJCEC\' (ID: 24) — CI: 123456 — Cajero ID: 1','::1','2026-07-15 12:31:43'),(33,1,'INSERTAR_CLIENTE','Se creó el cliente: naty','::1','2026-07-15 13:15:51'),(34,1,'CANJEAR_CUPON','Cupón canjeado: \'YTFF4E\' (ID: 25) — CI: 111111 — Cajero ID: 1','::1','2026-07-15 14:09:21'),(35,1,'INSERTAR_CUPONES_MASIVOS','Se crearon 5 cupones del tipo ID: 1 - Descripción: decuento 8%','::1','2026-07-16 06:35:26'),(36,1,'CANJEAR_CUPON','Cupón canjeado: \'27NG27\' (ID: 31) — CI: 8226062 — Cajero ID: 1','::1','2026-07-16 06:35:59'),(37,1,'CANJEAR_CUPON','Cupón canjeado: \'TVPA82\' (ID: 33) — CI: 8253193 — Cajero ID: 1','::1','2026-07-16 06:39:25'),(38,1,'CANJEAR_CUPON','Cupón canjeado: \'M5XHQ7\' (ID: 34) — CI: 3884991 — Cajero ID: 1','::1','2026-07-16 06:41:27'),(39,1,'INSERTAR_CUPONES_MASIVOS','Se crearon 6 cupones del tipo ID: 1 - Descripción: descuento del 100%','::1','2026-07-16 07:20:20'),(40,1,'CANJEAR_CUPON','Cupón canjeado: \'VYQD94\' (ID: 37) — CI: 8976732 — Cajero ID: 1','::1','2026-07-16 07:21:07'),(41,1,'CANJEAR_CUPON','Cupón canjeado: \'R47T5P\' (ID: 38) — CI: 8976732 — Cajero ID: 1','::1','2026-07-16 07:21:43'),(42,1,'CANJEAR_CUPON','Cupón canjeado: \'BGRMK9\' (ID: 35) — CI: 2021110109 — Cajero ID: 1','::1','2026-07-16 08:27:08'),(43,1,'ELIMINAR_CUPON','Cupón ID: 21 eliminado (estado=0)','::1','2026-07-16 08:33:46'),(44,1,'CANJEAR_CUPON','Cupón canjeado: \'VKRS9Y\' (ID: 39) — CI: 2021115381 — Cajero ID: 1','::1','2026-07-16 08:34:34'),(45,1,'CANJEAR_CUPON','Cupón canjeado: \'G28E89\' (ID: 40) — CI: 2021115381 — Cajero ID: 1','::1','2026-07-16 08:34:57'),(46,1,'CANJEAR_CUPON','Cupón canjeado: Código \'4L5DRZ\' (ID: 42) - Cliente: Estudiante UPSA (CI: 2025116993) - Usuario ID: 1','::1','2026-07-16 11:59:35'),(47,1,'CANJEAR_CUPON','Cupón canjeado: Código \'DBWUAY\' (ID: 43) - Cliente: Estudiante UPSA (CI: 2025116993) - Usuario ID: 1','::1','2026-07-16 11:59:57'),(48,1,'CANJEAR_CUPON','Cupón canjeado: Código \'679GEJ\' (ID: 47) - Cliente: Estudiante UAGRM (CI: 2021116697) - Usuario ID: 1','::1','2026-07-16 12:12:56'),(49,1,'CANJEAR_CUPON','Cupón canjeado: Código \'35YK9P\' (ID: 48) - Cliente: Estudiante UAGRM (CI: 2017117994) - Usuario ID: 1','::1','2026-07-16 12:13:22'),(50,1,'CANJEAR_CUPON','Cupón canjeado: Código \'HLMQ75\' (ID: 49) - Cliente: Estudiante UAGRM (CI: 2017117994) - Usuario ID: 1','::1','2026-07-16 12:13:39'),(51,1,'TOGGLE_VISIBLE_SUCURSAL','Sucursal ID: 1 - Visible Web: 0','::1','2026-07-16 13:07:16'),(52,1,'TOGGLE_VISIBLE_SUCURSAL','Sucursal ID: 1 - Visible Web: 1','::1','2026-07-16 13:07:21'),(53,1,'INSERTAR_CUPONES_MASIVOS','Se crearon 30 cupones del tipo ID: 1 - Descripción: 100%','::1','2026-07-18 06:38:04'),(54,1,'CANJEAR_CUPON','Cupón canjeado: Código \'NLG78D\' (ID: 93) - Cliente: Estudiante UAGRM (CI: 2024115349) - Usuario ID: 1','::1','2026-07-18 06:38:51');
/*!40000 ALTER TABLE `logs_actividad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metodos_pago`
--

DROP TABLE IF EXISTS `metodos_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metodos_pago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metodos_pago`
--

LOCK TABLES `metodos_pago` WRITE;
/*!40000 ALTER TABLE `metodos_pago` DISABLE KEYS */;
INSERT INTO `metodos_pago` VALUES (1,'Efectivo',1),(2,'QR',1),(3,'Tarjeta',1);
/*!40000 ALTER TABLE `metodos_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `popups`
--

DROP TABLE IF EXISTS `popups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `popups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `ruta_foto` varchar(255) NOT NULL,
  `url_destino` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `lunes` tinyint(1) DEFAULT 0,
  `martes` tinyint(1) DEFAULT 0,
  `miercoles` tinyint(1) DEFAULT 0,
  `jueves` tinyint(1) DEFAULT 0,
  `viernes` tinyint(1) DEFAULT 0,
  `sabado` tinyint(1) DEFAULT 0,
  `domingo` tinyint(1) DEFAULT 0,
  `hora_inicio` time DEFAULT '00:00:00',
  `hora_cierre` time DEFAULT '23:59:59',
  `visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `popups`
--

LOCK TABLES `popups` WRITE;
/*!40000 ALTER TABLE `popups` DISABLE KEYS */;
INSERT INTO `popups` VALUES (1,'Promo Solar','uploads/popups/popups_1.jpeg',NULL,'Promoción especial Pollo El Solar','2026-07-01','2026-12-31',1,1,1,1,1,1,1,'00:00:00','23:59:59',1,'2026-07-15 15:01:25');
/*!40000 ALTER TABLE `popups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_fotos`
--

DROP TABLE IF EXISTS `producto_fotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `ruta_foto` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `producto_fotos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_fotos`
--

LOCK TABLES `producto_fotos` WRITE;
/*!40000 ALTER TABLE `producto_fotos` DISABLE KEYS */;
INSERT INTO `producto_fotos` VALUES (1,1,'../items/CUARTO PECHO 2025 CENITAL.png',0,1),(2,1,'../items/CUARTO PECHO SOLAR.png',1,1),(3,1,'../items/cuartopecho.png',2,1),(4,2,'../items/CUARTO PIERNA 2025.png',0,1),(5,3,'../items/CUARTO CRUZADO - CONTRA + ALA.png',0,1),(6,3,'../items/cuartocontraala.png',1,1),(7,4,'../items/CUARTO CRUZADO - PECHO + PIERNA.png',0,1),(8,5,'../items/ALITAS SOLAR.png',0,1),(9,5,'../items/ALITAS TRAD.png',1,1),(10,5,'../items/ALITAS.png',2,1),(11,6,'../items/CHICHARRON PERSONAL SOLAR.png',0,1),(12,6,'../items/CHICHARRON PERSONAL.png',1,1),(13,7,'../items/CHICHARRON PREMIUM.png',0,1),(14,8,'../items/PIERNITAS.png',0,1),(15,9,'../items/HAMBURGUESA DE POLLO.png',0,1),(16,9,'../items/HAMURGUESA DE POLLO FRITO SOLAR SIN PAPAS.png',1,1),(17,9,'../items/HAMURGUESA DE POLLO FRITO.png',2,1),(18,9,'../items/hamburguesa.png',3,1),(19,10,'../items/hamburguesasinpapa.png',0,1),(20,11,'../items/PIPOCAS DE POLLO SOLAR.png',0,1),(21,11,'../items/PIPOCAS DE POLLO.png',1,1),(22,12,'../items/PIPOCAS DE POLLO XL  SOLAR.png',0,1),(23,12,'../items/PIPOCAS XL.png',1,1),(24,13,'../items/ECO PECHO.png',0,1),(25,14,'../items/ECO ALA SOLAR.png',0,1),(26,14,'../items/ECO ALA.png',1,1),(27,15,'../items/ECO CONTRA SOLAR.png',0,1),(28,15,'../items/ECO CONTRA.png',1,1),(29,16,'../items/ECO PIERNITAS SOLAR.png',0,1),(30,17,'../items/PEQUE.png',0,1),(31,18,'../items/PEQUE ALA.png',0,1),(32,19,'../items/PEQUE ALITA SOLAR.png',0,1),(33,20,'../items/PEQUE CHICHARRON SOLAR.png',0,1),(34,21,'../items/PORCION DE ARROZ.png',0,1),(35,22,'../items/PORCION DE PAPAS.png',0,1),(36,23,'../items/PORCION DE YUCA.png',0,1),(37,24,'../items/TRIO PECHO SOLAR.png',0,1),(38,24,'../items/TRIO PECHO.png',1,1),(39,25,'../items/TRIO PIERNA SOLAR.png',0,1),(40,25,'../items/TRIO PIERNA.png',1,1);
/*!40000 ALTER TABLE `producto_fotos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) DEFAULT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_oferta` decimal(10,2) DEFAULT NULL,
  `etiqueta_oferta` varchar(50) DEFAULT NULL,
  `moneda` enum('BOB','USD','PYG','BRL') DEFAULT 'BOB',
  `es_combo` tinyint(1) DEFAULT 0,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo','Todos') DEFAULT 'Todos',
  `visible` tinyint(1) DEFAULT 1,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `destacado` tinyint(1) DEFAULT 0,
  `fecha_publicacion` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `sucursal_id` (`sucursal_id`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,3,NULL,'Cuarto Pecho','Cuarto de pecho de pollo a la brasa',25.00,NULL,NULL,'BOB',0,'Todos',1,1,1,NULL,'2026-07-15 08:01:25'),(2,3,NULL,'Cuarto Pierna','Cuarto de pierna de pollo a la brasa',25.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(3,3,NULL,'Cuarto Cruzado Pecho','Contra + Ala',25.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(4,3,NULL,'Cuarto Cruzado Pierna','Pecho + Pierna',25.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(5,3,NULL,'Alitas Solar','Porción de alitas al estilo Solar',22.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(6,3,NULL,'Chicharrón Personal','Chicharrón personal de pollo',20.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(7,3,NULL,'Chicharrón Premium','Chicharrón premium de pollo',28.00,NULL,NULL,'BOB',0,'Todos',1,1,1,NULL,'2026-07-15 08:01:25'),(8,3,NULL,'Piernitas','Porción de piernitas de pollo',20.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(9,3,NULL,'Hamburguesa de Pollo','Hamburguesa de pollo frito con papas',30.00,NULL,NULL,'BOB',0,'Todos',1,1,1,NULL,'2026-07-15 08:01:25'),(10,3,NULL,'Hamburguesa sin Papas','Hamburguesa de pollo frito sin papas',25.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(11,3,NULL,'Pipocas de Pollo','Pipocas de pollo crujientes',18.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(12,3,NULL,'Pipocas de Pollo XL','Pipocas de pollo XL tamaño extra',25.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(13,3,NULL,'Eco Pecho','Porción económica de pecho',15.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(14,3,NULL,'Eco Ala','Porción económica de ala',12.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(15,3,NULL,'Eco Contra','Porción económica de contra',12.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(16,3,NULL,'Eco Piernitas','Porción económica de piernitas',12.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(17,3,NULL,'Peque','Porción pequeña de pollo',10.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(18,3,NULL,'Peque Ala','Porción pequeña de ala',10.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(19,3,NULL,'Peque Alita Solar','Porción pequeña de alita al estilo Solar',10.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(20,3,NULL,'Peque Chicharrón','Porción pequeña de chicharrón',10.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(21,3,NULL,'Porción de Arroz','Porción individual de arroz',5.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(22,3,NULL,'Porción de Papas','Porción individual de papas fritas',8.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(23,3,NULL,'Porción de Yuca','Porción individual de yuca frita',8.00,NULL,NULL,'BOB',0,'Todos',1,1,0,NULL,'2026-07-15 08:01:25'),(24,1,NULL,'Trío Pecho','Combo trío con pecho de pollo',45.00,NULL,NULL,'BOB',1,'Todos',1,1,1,NULL,'2026-07-15 08:01:25'),(25,1,NULL,'Trío Pierna','Combo trío con pierna de pollo',45.00,NULL,NULL,'BOB',1,'Todos',1,1,0,NULL,'2026-07-15 08:01:25');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sucursal_fotos`
--

DROP TABLE IF EXISTS `sucursal_fotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sucursal_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sucursal_id` int(11) NOT NULL,
  `ruta_foto` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `sucursal_id` (`sucursal_id`),
  CONSTRAINT `sucursal_fotos_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sucursal_fotos`
--

LOCK TABLES `sucursal_fotos` WRITE;
/*!40000 ALTER TABLE `sucursal_fotos` DISABLE KEYS */;
INSERT INTO `sucursal_fotos` VALUES (1,1,'uploads/sucursales/fachadacanoto.jpeg',0,1),(2,1,'uploads/sucursales/CAÑOTO.png',1,1),(3,1,'uploads/sucursales/CAÑOTO CAJA.png',2,1);
/*!40000 ALTER TABLE `sucursal_fotos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sucursales`
--

DROP TABLE IF EXISTS `sucursales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `pais` enum('Bolivia','Paraguay','Brasil') NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `hora_apertura` time DEFAULT NULL,
  `hora_cierre` time DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sucursales`
--

LOCK TABLES `sucursales` WRITE;
/*!40000 ALTER TABLE `sucursales` DISABLE KEYS */;
INSERT INTO `sucursales` VALUES (1,'canoto','Bolivia','primer anillo',-17.78644037,-63.18810054,'11:00:00','23:59:00',1,1);
/*!40000 ALTER TABLE `sucursales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_cupon`
--

DROP TABLE IF EXISTS `tipo_cupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_cupon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo_descuento` enum('porcentaje','monto_fijo') NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_cupon`
--

LOCK TABLES `tipo_cupon` WRITE;
/*!40000 ALTER TABLE `tipo_cupon` DISABLE KEYS */;
INSERT INTO `tipo_cupon` VALUES (1,'cupon %','porcentaje',1),(2,'cuponMonto','monto_fijo',1);
/*!40000 ALTER TABLE `tipo_cupon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(250) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','editor') DEFAULT 'admin',
  `estado` tinyint(1) DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'andres','andres','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',1,'2026-07-15 07:18:05','2026-07-15 06:50:06');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_detalle`
--

DROP TABLE IF EXISTS `venta_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venta_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL CHECK (`cantidad` > 0),
  `precio_unitario` decimal(10,2) NOT NULL,
  `descuento_linea` decimal(10,2) DEFAULT 0.00,
  `subtotal_linea` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `venta_detalle_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venta_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_detalle`
--

LOCK TABLES `venta_detalle` WRITE;
/*!40000 ALTER TABLE `venta_detalle` DISABLE KEYS */;
/*!40000 ALTER TABLE `venta_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_pagos`
--

DROP TABLE IF EXISTS `venta_pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venta_pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `metodo_pago_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `metodo_pago_id` (`metodo_pago_id`),
  CONSTRAINT `venta_pagos_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venta_pagos_ibfk_2` FOREIGN KEY (`metodo_pago_id`) REFERENCES `metodos_pago` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_pagos`
--

LOCK TABLES `venta_pagos` WRITE;
/*!40000 ALTER TABLE `venta_pagos` DISABLE KEYS */;
/*!40000 ALTER TABLE `venta_pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sucursal_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `apertura_caja_id` int(11) NOT NULL,
  `fecha_venta` datetime DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `moneda` enum('BOB','USD','PYG','BRL') DEFAULT 'BOB',
  `estado` tinyint(1) DEFAULT 1,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sucursal_id` (`sucursal_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `apertura_caja_id` (`apertura_caja_id`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ventas_ibfk_4` FOREIGN KEY (`apertura_caja_id`) REFERENCES `apertura_caja` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 11:33:24
