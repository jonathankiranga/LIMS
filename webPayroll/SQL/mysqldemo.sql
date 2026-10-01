CREATE DATABASE  IF NOT EXISTS `webpayroll` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `webpayroll`;
-- MySQL dump 10.13  Distrib 8.0.26, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: webpayroll
-- ------------------------------------------------------
-- Server version	8.0.26

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auditprlmatrix`
--

DROP TABLE IF EXISTS `auditprlmatrix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditprlmatrix` (
  `userid` varchar(50) DEFAULT NULL,
  `posted` datetime(6) DEFAULT NULL,
  `pfno` char(20) NOT NULL,
  `prodid` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `employeramount` decimal(10,2) DEFAULT NULL,
  `deduction` tinyint(1) NOT NULL,
  `nonrecuring` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditprlmatrix`
--

LOCK TABLES `auditprlmatrix` WRITE;
/*!40000 ALTER TABLE `auditprlmatrix` DISABLE KEYS */;
INSERT INTO `auditprlmatrix` VALUES ('SALOME MUGAMBI','2022-12-05 16:32:30.633000','PR0001',32,'N.S.S.F',200.00,NULL,0,0),('SALOME MUGAMBI','2022-12-05 16:32:47.160000','PR0001',8,'N.H.I.F.',750.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:07:25.863000','PR0002',8,'N.H.I.F.',750.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:07:25.890000','TMP0001',8,'N.H.I.F.',600.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:07:25.897000','PR0001',8,'N.H.I.F.',850.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:07:25.903000','TMP0004',8,'N.H.I.F.',600.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:07:25.910000','TMP0003',8,'N.H.I.F.',600.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:07:25.913000','TMP0002',8,'N.H.I.F.',600.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:26:03.620000','PR0002',53,'ADVANCE SAL',0.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:26:03.650000','TMP0001',53,'ADVANCE SAL',0.00,0.00,0,0),('SALOME MUGAMBI','2022-12-16 11:44:45.530000','PR0002',32,'N.S.S.F',200.00,NULL,0,0),('SALOME MUGAMBI','2022-12-16 11:44:45.570000','TMP0001',32,'N.S.S.F',200.00,NULL,0,0),('SALOME MUGAMBI','2022-12-16 11:44:45.577000','PR0001',32,'N.S.S.F',200.00,NULL,0,0),('SALOME MUGAMBI','2022-12-16 11:44:45.583000','TMP0004',32,'N.S.S.F',200.00,NULL,0,0),('SALOME MUGAMBI','2022-12-16 11:44:45.590000','TMP0002',32,'N.S.S.F',200.00,NULL,0,0),('SALOME MUGAMBI','2022-12-16 11:44:45.597000','TMP0003',32,'N.S.S.F',200.00,NULL,0,0),('Programer user','2022-12-16 11:54:14.260000','PR0002',4,'Gift Token',2000.00,0.00,1,1),('Programer user','2022-12-16 11:54:14.267000','TMP0001',4,'Gift Token',2000.00,0.00,1,1),('Programer user','2022-12-16 11:54:14.273000','PR0001',4,'Gift Token',2000.00,0.00,1,1),('Programer user','2022-12-16 11:54:14.280000','TMP0004',4,'Gift Token',2000.00,0.00,1,1),('Programer user','2022-12-16 11:54:14.283000','TMP0002',4,'Gift Token',2000.00,0.00,1,1),('Programer user','2022-12-16 11:54:14.290000','TMP0003',4,'Gift Token',2000.00,0.00,1,1),('SALOME MUGAMBI','2022-12-16 11:55:06.793000','PR0002',4,'Gift Token',2000.00,0.00,1,1),('SALOME MUGAMBI','2022-12-16 11:55:06.797000','TMP0001',4,'Gift Token',2000.00,0.00,1,1),('SALOME MUGAMBI','2022-12-16 11:55:06.800000','PR0001',4,'Gift Token',2000.00,0.00,1,1),('SALOME MUGAMBI','2022-12-16 11:55:06.803000','TMP0004',4,'Gift Token',2000.00,0.00,1,1),('SALOME MUGAMBI','2022-12-16 11:55:06.807000','TMP0002',4,'Gift Token',2000.00,0.00,1,1),('SALOME MUGAMBI','2022-12-16 11:55:06.810000','TMP0003',4,'Gift Token',2000.00,0.00,1,1),('SALOME MUGAMBI','2023-01-24 09:15:11.470000','PR0002',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:15:11.530000','TMP0001',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:15:11.537000','PR0001',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:15:11.543000','TMP0004',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:15:11.547000','TMP0002',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:15:11.553000','TMP0003',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:17:04.770000','PR0002',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:17:04.807000','TMP0001',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:17:04.813000','PR0001',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:17:04.820000','TMP0004',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:17:04.823000','TMP0002',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:17:04.830000','TMP0003',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:26:19.400000','PR0002',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:26:19.423000','TMP0001',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:26:19.430000','PR0001',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:26:19.433000','TMP0004',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:26:19.440000','TMP0002',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:26:19.443000','TMP0003',1000000,'CHAMA',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:26:50.750000','PR0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:26:50.790000','TMP0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:05.047000','TMP0004',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:33.590000','PR0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:33.650000','TMP0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:33.657000','PR0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:33.663000','TMP0004',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:33.670000','TMP0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:33.673000','TMP0003',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:49.910000','PR0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:49.960000','TMP0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:49.963000','PR0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:49.970000','TMP0004',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:49.977000','TMP0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:27:49.983000','TMP0003',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:28:08.213000','PR0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:28:08.257000','TMP0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:28:08.263000','PR0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:28:08.270000','TMP0004',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:28:08.273000','TMP0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:28:08.280000','TMP0003',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:29:19.673000','TMP0003',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:35:46.100000','PR0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:35:46.133000','TMP0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:35:46.140000','PR0001',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:35:46.147000','TMP0004',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:35:46.150000','TMP0002',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:35:46.157000','TMP0003',53,'ADVANCE SAL',0.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:47:07.857000','PR0002',1000000,'CHAMA',3000.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:47:07.883000','TMP0001',1000000,'CHAMA',3000.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:47:07.890000','PR0001',1000000,'CHAMA',3000.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:47:07.897000','TMP0004',1000000,'CHAMA',3000.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:47:07.903000','TMP0002',1000000,'CHAMA',3000.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:47:07.907000','TMP0003',1000000,'CHAMA',3000.00,0.00,0,1),('SALOME MUGAMBI','2023-01-24 09:47:41.310000','PR0002',53,'ADVANCE SAL',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:47:41.340000','TMP0001',53,'ADVANCE SAL',0.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:48:44.973000','PR0002',53,'ADVANCE SAL',5000.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:48:45.030000','TMP0001',53,'ADVANCE SAL',5000.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:48:59.083000','TMP0004',53,'ADVANCE SAL',5000.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:48:59.113000','TMP0002',53,'ADVANCE SAL',5000.00,0.00,0,0),('SALOME MUGAMBI','2023-01-24 09:48:59.120000','TMP0003',53,'ADVANCE SAL',5000.00,0.00,0,0),('Programer user','2023-01-25 10:42:23.003000','PR0002',1000000,'CHAMA',3000.00,0.00,0,0),('Programer user','2023-01-25 10:42:23.063000','TMP0001',1000000,'CHAMA',3000.00,0.00,0,0),('Programer user','2023-01-25 10:42:23.070000','PR0001',1000000,'CHAMA',3000.00,0.00,0,0),('Programer user','2023-01-25 10:42:23.077000','TMP0004',1000000,'CHAMA',3000.00,0.00,0,0),('Programer user','2023-01-25 10:42:23.087000','TMP0002',1000000,'CHAMA',3000.00,0.00,0,0),('Programer user','2023-01-25 10:42:23.093000','TMP0003',1000000,'CHAMA',3000.00,0.00,0,0),('Programer user','2024-07-18 13:53:09.837000','PR0002',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:09.970000','TMP0001',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:09.983000','PR0001',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:10.000000','TMP0004',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:10.017000','TMP0002',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:10.020000','TMP0003',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:10.037000','CON0001',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:33.330000','PR0002',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:33.400000','TMP0001',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:33.417000','PR0001',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:33.447000','TMP0004',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:33.463000','TMP0002',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:33.470000','TMP0003',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:33.483000','CON0001',1000001,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 13:53:57.610000','PR0002',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:53:57.663000','TMP0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:53:57.680000','PR0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:53:57.693000','TMP0004',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:53:57.710000','TMP0002',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:53:57.723000','TMP0003',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:53:57.740000','CON0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:54:55.923000','PR0002',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:54:55.993000','TMP0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:54:55.993000','PR0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:54:56.007000','TMP0004',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:54:56.047000','TMP0002',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:54:56.060000','TMP0003',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:54:56.077000','CON0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:45.357000','PR0002',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:45.410000','TMP0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:45.427000','PR0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:45.427000','TMP0004',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:45.440000','TMP0002',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:45.457000','TMP0003',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:45.473000','CON0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:56.293000','PR0002',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:56.360000','TMP0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:56.360000','PR0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:56.377000','TMP0004',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:56.393000','TMP0002',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:56.410000','TMP0003',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 13:55:56.423000','CON0001',1000002,'AHL',1500.15,3000.30,0,0),('Programer user','2024-07-18 14:01:31.923000','PR0002',1000003,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 14:01:32.010000','TMP0001',1000003,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 14:01:32.023000','PR0001',1000003,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 14:01:32.040000','TMP0004',1000003,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 14:01:32.057000','TMP0002',1000003,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 14:01:32.057000','TMP0003',1000003,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 14:01:32.070000','CON0001',1000003,'AHL',0.00,0.00,0,0),('Programer user','2024-07-18 14:23:53.187000','PR0002',1000003,'AHL',331.51,331.51,0,0),('Programer user','2024-07-18 14:23:53.223000','TMP0001',1000003,'AHL',273.01,273.01,0,0),('Programer user','2024-07-18 14:23:53.223000','PR0001',1000003,'AHL',450.01,450.01,0,0),('Programer user','2024-07-18 14:23:53.240000','TMP0004',1000003,'AHL',273.01,273.01,0,0),('Programer user','2024-07-18 14:23:53.253000','TMP0002',1000003,'AHL',273.01,273.01,0,0),('Programer user','2024-07-18 14:23:53.270000','TMP0003',1000003,'AHL',273.01,273.01,0,0),('Programer user','2024-07-18 14:23:53.287000','CON0001',1000003,'AHL',525.01,525.01,0,0);
/*!40000 ALTER TABLE `auditprlmatrix` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audittrail`
--

DROP TABLE IF EXISTS `audittrail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audittrail` (
  `transactiondate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` varchar(20) NOT NULL,
  `querystring` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audittrail`
--

LOCK TABLES `audittrail` WRITE;
/*!40000 ALTER TABLE `audittrail` DISABLE KEYS */;
INSERT INTO `audittrail` VALUES ('2024-07-18 10:33:27','admin','DELETE FROM audittrail	WHERE  transactiondate &lt;= \'2024-06-18\''),('2024-07-18 10:50:49','admin','INSERT INTO [prlitemaintenace]'),('2024-07-18 10:53:09','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:09','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:09','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:09','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:09','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:10','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:10','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:10','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:10','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:10','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:10','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:33','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:40','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0002              \'  and  [prodid]=\'1000001\''),('2024-07-18 10:53:40','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0001             \'  and  [prodid]=\'1000001\''),('2024-07-18 10:53:40','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0001              \'  and  [prodid]=\'1000001\''),('2024-07-18 10:53:40','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0004             \'  and  [prodid]=\'1000001\''),('2024-07-18 10:53:40','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0002             \'  and  [prodid]=\'1000001\''),('2024-07-18 10:53:40','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0003             \'  and  [prodid]=\'1000001\''),('2024-07-18 10:53:40','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'CON0001             \'  and  [prodid]=\'1000001\''),('2024-07-18 10:53:40','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:40','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:10','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:10','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:10','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:10','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:10','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:33','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0002              \' and  [prodid]=\'1000001\''),('2024-07-18 10:53:33','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:33','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0001             \' and  [prodid]=\'1000001\''),('2024-07-18 10:53:33','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:33','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0001              \' and  [prodid]=\'1000001\''),('2024-07-18 10:53:33','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0004             \' and  [prodid]=\'1000001\''),('2024-07-18 10:53:33','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0002             \' and  [prodid]=\'1000001\''),('2024-07-18 10:53:33','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0003             \' and  [prodid]=\'1000001\''),('2024-07-18 10:53:33','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'CON0001             \' and  [prodid]=\'1000001\''),('2024-07-18 10:53:33','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:33','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:33','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:46','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:46','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:57','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:57','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:57','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 10:55:45','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 10:55:48','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:55:48','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 11:01:04','admin','UPDATE www_users SET lastvisitdate=\'2024-07-18 14:01:04\''),('2024-07-18 11:01:13','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 11:01:13','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 10:53:57','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:01:25','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 11:01:25','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 11:23:23','admin','INSERT INTO [prlitemaintenace]'),('2024-07-18 11:23:39','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 11:23:39','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 11:23:53','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0002              \' and  [prodid]=\'1000003\''),('2024-07-18 11:23:53','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0001             \' and  [prodid]=\'1000003\''),('2024-07-18 11:23:53','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0001              \' and  [prodid]=\'1000003\''),('2024-07-18 10:53:57','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:53:57','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:54:55','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:54:55','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0002              \' and  [prodid]=\'1000002\''),('2024-07-18 10:54:55','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:54:55','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:54:55','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0001             \' and  [prodid]=\'1000002\''),('2024-07-18 10:54:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:54:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:54:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0001              \' and  [prodid]=\'1000002\''),('2024-07-18 10:54:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:54:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0004             \' and  [prodid]=\'1000002\''),('2024-07-18 10:54:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0002             \' and  [prodid]=\'1000002\''),('2024-07-18 10:54:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0003             \' and  [prodid]=\'1000002\''),('2024-07-18 10:54:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'CON0001             \' and  [prodid]=\'1000002\''),('2024-07-18 10:54:56','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:54:56','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 11:23:53','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0004             \' and  [prodid]=\'1000003\''),('2024-07-18 11:23:53','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0002             \' and  [prodid]=\'1000003\''),('2024-07-18 10:54:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:54:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:54:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:54:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:54:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:54:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:54:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:54:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:45','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0002              \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:45','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:45','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0001             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:45','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0001              \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:45','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0004             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:45','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0002             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:45','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0003             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:45','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'CON0001             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:45','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:55:45','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:45','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0002              \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0001             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'PR0001              \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0004             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0002             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0003             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:56','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'CON0001             \' and  [prodid]=\'1000002\''),('2024-07-18 10:55:56','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 10:55:56','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 10:55:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 10:55:56','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:01:32','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 11:01:32','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\''),('2024-07-18 11:01:32','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:01:32','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:23:53','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'TMP0003             \' and  [prodid]=\'1000003\''),('2024-07-18 11:23:53','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:23:53','admin','INSERT INTO [auditprlmatrix]'),('2024-07-18 11:23:53','admin','DELETE FROM [prlmatrix] WHERE [pfno]=\'CON0001             \' and  [prodid]=\'1000003\''),('2024-07-18 11:23:53','admin','INSERT INTO [prlmatrix]'),('2024-07-18 11:23:53','admin','DELETE FROM prlpayroltransfile where [payroll_id]=\'8\''),('2024-07-18 11:23:53','admin','DELETE FROM prlpaydetailstransfile where [payroll_id]=\'8\'');
/*!40000 ALTER TABLE `audittrail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `coycode` int NOT NULL DEFAULT '1',
  `coyname` varchar(50) NOT NULL,
  `gstno` varchar(20) NOT NULL,
  `companynumber` varchar(20) NOT NULL,
  `regoffice1` varchar(40) NOT NULL,
  `regoffice2` varchar(40) NOT NULL,
  `regoffice3` varchar(40) NOT NULL,
  `regoffice4` varchar(40) NOT NULL,
  `regoffice5` varchar(20) NOT NULL,
  `regoffice6` varchar(15) NOT NULL,
  `telephone` varchar(25) NOT NULL,
  `fax` varchar(25) NOT NULL,
  `email` varchar(55) NOT NULL,
  `currencydefault` varchar(4) NOT NULL,
  `debtorsact` varchar(20) DEFAULT NULL,
  `pytdiscountact` varchar(20) DEFAULT NULL,
  `creditorsact` varchar(20) DEFAULT NULL,
  `payrollact` varchar(20) DEFAULT NULL,
  `grnact` varchar(20) DEFAULT NULL,
  `exchangediffact` varchar(20) DEFAULT NULL,
  `purchasesexchangediffact` varchar(20) DEFAULT NULL,
  `retainedearnings` varchar(20) DEFAULT NULL,
  `gllink_debtors` smallint DEFAULT '1',
  `gllink_creditors` smallint DEFAULT '1',
  `gllink_stock` smallint DEFAULT '1',
  `freightact` varchar(20) DEFAULT NULL,
  `lastjournalno` bigint DEFAULT NULL,
  PRIMARY KEY (`coycode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `confname` varchar(35) NOT NULL,
  `confvalue` longtext NOT NULL,
  PRIMARY KEY (`confname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES ('CalenderStartdate','01/11/2022'),('CountryOfOperation','KE'),('DB_Maintenance','0'),('DB_Maintenance_LastRun','2022-11-01'),('DefaultDateFormat','d/m/Y'),('DefaultTheme','xenos'),('EDI_Incoming_Orders','companies/HRpayroll/EDI_Incoming_Orders'),('EDI_MsgPending','companies/HRpayroll/EDI_Pending'),('EDI_MsgSent','companies/HRpayroll/EDI__Sent'),('EDIHeaderMsgId','D:01B:UN:EAN010'),('EDIReference','webERP'),('ExchangeRateFeed','ECB'),('Extended_CustomerInfo','0'),('Extended_SupplierInfo','1'),('FactoryManagerEmail','manager@company.com'),('FreightChargeAppliesIfLessThan','1000'),('FreightTaxCategory','1'),('FrequentlyOrderedItems','0'),('geocode_integration','0'),('HTTPS_Only','0'),('InventoryManagerEmail','test@company.com'),('InvoicePortraitFormat','0'),('LeaveDaysYear','26'),('LogSeverity','0'),('MaxImageSize','300'),('MonthJumpBymonths','1'),('MonthsAuditTrail','1'),('MonthTypePayroll','2023-03-01 00:00:00.000'),('NumberOfMonthMustBeShown','6'),('NumberOfPeriodsOfStockUsage','12'),('OverChargeProportion','30'),('OverReceiveProportion','20'),('PackNoteFormat','1'),('PageLength','48'),('part_pics_dir','companies/HRpayroll/EDI_Incoming_Orders'),('PastDueDays1','30'),('PastDueDays2','60'),('payroll_month','10'),('payroll_week','2'),('payroll_year','2014'),('PO_AllowSameItemMultipleTimes','1'),('ProhibitPostingsBefore','0'),('PurchasingManagerEmail','test@company.com'),('RadioBeaconFileCounter','/home/RadioBeacon/FileCounter'),('RadioBeaconFTP_user_name','RadioBeacon ftp server user name'),('RadioBeaconHomeDir','/home/RadioBeacon'),('RadioBeaconStockLocation','BL'),('RadioBraconFTP_server','192.168.2.2'),('RadioBreaconFilePrefix','ORDXX'),('RadionBeaconFTP_user_pass','Radio Beacon remote ftp server password'),('reports_dir','companies/HRpayroll/reports'),('RequirePickingNote','1'),('RomalpaClause','Ownership will not pass to the buyer until the goods have been paid for in full.'),('ShopAboutUs','This web-shop software has been developed by Logic Works Ltd for SKYWAVEHRSYSTEM. For support contact Phil Daintree by rn&lt;a href=&quot;mailto:support@logicworks.co.nz&quot;&gt;email&lt;/a&gt;rn'),('ShopAllowBankTransfer','1'),('ShopAllowCreditCards','1'),('ShopAllowPayPal','1'),('ShopAllowSurcharges','1'),('ShopBankTransferSurcharge','0.0'),('ShopBranchCode','ANGRY'),('ShopContactUs','For support contact Logic Works Ltd by rn&lt;a href=&quot;mailto:support@logicworks.co.nz&quot;&gt;email&lt;/a&gt;'),('ShopCreditCardBankAccount','1030'),('ShopCreditCardGateway','SwipeHQ'),('ShopCreditCardSurcharge','2.5'),('ShopDebtorNo','ANGRY'),('ShopFreightMethod','NoFreight'),('ShopFreightPolicy','Shipping information'),('ShopManagerEmail','shopmanager@yourdomain.com'),('ShopMode','test'),('ShopName','SKYWAVEHRSYSTEM Demo Store'),('ShopPayPalBankAccount','1040'),('ShopPaypalCommissionAccount','7220'),('ShopPayPalSurcharge','3.4'),('ShopShowOnlyAvailableItems','0'),('ShopShowQOHColumn','1'),('ShopSurchargeStockID','PAYTSURCHARGE'),('ShopTitle','Shop Home'),('Show_Settled_LastMonth','1'),('ShowStockidOnImages','0'),('ShowValueOnGRN','1'),('SmtpSetting','0'),('SO_AllowSameItemMultipleTimes','1'),('StandardCostDecimalPlaces','2'),('TaxAuthorityReferenceName','VAT'),('UpdateCurrencyRatesDaily','2015-02-24'),('VersionNumber','4.11.3'),('WeekJumpBydays','7'),('WeekTypePayroll','2022-11-01'),('WeightedAverageCosting','0'),('WikiApp','MediaWiki'),('WikiPath','wiki'),('WorkingDaysWeek','5'),('YearEnd','12');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `currency` char(20) NOT NULL,
  `currabrev` char(3) NOT NULL,
  `country` char(50) NOT NULL,
  `hundredsname` char(15) NOT NULL,
  `decimalplaces` smallint NOT NULL DEFAULT '2',
  `rate` double NOT NULL DEFAULT '1',
  `webcart` smallint NOT NULL DEFAULT '1',
  PRIMARY KEY (`currabrev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES ('Australian Dollars','AUD','Australia','cents',2,0.0133,0),('Swiss Francs','CHF','Swizerland','centimes',2,0.01091,0),('Euro','EUR','Euroland','cents',2,0.009064,1),('Pounds','GBP','England','Pence',2,0.007183,0),('Kenyian Shillings','KES','Kenya','none',0,1,0),('Ugandan shilling','UGX','Uganda','cents',0,31.5885,1),('US Dollars','USD','United States','Cents',2,0,1);
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dayoftheweeks`
--

DROP TABLE IF EXISTS `dayoftheweeks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dayoftheweeks` (
  `Dayoftheweek` varchar(50) NOT NULL,
  `isworkingday` tinyint(1) DEFAULT NULL,
  UNIQUE KEY `IX_Dayoftheweek` (`Dayoftheweek`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dayoftheweeks`
--

LOCK TABLES `dayoftheweeks` WRITE;
/*!40000 ALTER TABLE `dayoftheweeks` DISABLE KEYS */;
/*!40000 ALTER TABLE `dayoftheweeks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emailsettings`
--

DROP TABLE IF EXISTS `emailsettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emailsettings` (
  `id` int NOT NULL,
  `host` varchar(30) NOT NULL,
  `port` char(5) NOT NULL,
  `heloaddress` varchar(20) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` longtext,
  `timeout` int DEFAULT '5',
  `companyname` varchar(50) DEFAULT NULL,
  `auth` smallint DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emailsettings`
--

LOCK TABLES `emailsettings` WRITE;
/*!40000 ALTER TABLE `emailsettings` DISABLE KEYS */;
/*!40000 ALTER TABLE `emailsettings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employeebanks`
--

DROP TABLE IF EXISTS `employeebanks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employeebanks` (
  `code` varchar(20) NOT NULL,
  `bankname` varchar(50) DEFAULT NULL,
  `firstitem` tinyint(1) DEFAULT NULL,
  `pkey` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employeebanks`
--

LOCK TABLES `employeebanks` WRITE;
/*!40000 ALTER TABLE `employeebanks` DISABLE KEYS */;
INSERT INTO `employeebanks` VALUES ('115','National Bank of Kenya',1,1),('120','National Bank of Kenya',1,2),('125','National Bank of Kenya',1,3),('130','National Bank of Kenya',1,4),('135','National Bank of Kenya',1,5),('140','National Bank of Kenya',1,6),('145','National Bank of Kenya',1,7),('150','National Bank of Kenya',1,8),('155','National Bank of Kenya',1,9),('160','National Bank of Kenya',1,10),('165','National Bank of Kenya',1,11),('170','National Bank of Kenya',1,12),('175','National Bank of Kenya',1,13),('205','Family Finance Bldg Society',1,14),('210','Family Finance Bldg Society',1,15),('215','Family Finance Bldg Society',1,16),('220','Family Finance Bldg Society',1,17),('225','Family Finance Bldg Society',1,18),('230','Family Finance Bldg Society',1,19),('235','Family Finance Bldg Society',1,20),('240','Family Finance Bldg Society',1,21),('241','Family Finance Bldg Society',1,22),('242','Family Finance Bldg Society',1,23),('305','Equity Bank',1,24),('310','Equity Bank',1,25),('315','Equity Bank',1,26),('320','Equity Bank',1,27),('325','Equity Bank',1,28),('330','Equity Bank',1,29),('335','Equity Bank',1,30),('340','Equity Bank',1,31),('345','Equity Bank',1,32),('346','Equity Bank',1,33),('350','Equity Bank',1,34),('355','Equity Bank',1,35),('356','Equity Bank',1,36),('357','Equity Bank',1,37),('358','Equity Bank',1,38),('359','Equity Bank',1,39),('360','Equity Bank',1,40),('361','Equity Bank',1,41),('362','Equity Bank',1,42),('363','Equity Bank',1,43),('364','Equity Bank',1,44),('366','Equity Bank',1,45),('367','Equity Bank',1,46),('368','Equity Bank',1,47),('369','Equity Bank',1,48),('370','Equity Bank',1,49),('371','Equity Bank',1,50),('372','Equity Bank',1,51),('373','Equity Bank',1,52),('410','Co-operative Bank (K) Ltd',1,53),('420','Co-operative Bank (K) Ltd',1,54),('430','Co-operative Bank (K) Ltd',1,55),('440','Co-operative Bank (K) Ltd',1,56),('445','Co-operative Bank (K) Ltd',1,57),('446','Co-operative Bank (K) Ltd',1,58),('447','Co-operative Bank (K) Ltd',1,59),('448','Co-operative Bank (K) Ltd',1,60),('449','Co-operative Bank (K) Ltd',1,61),('450','Co-operative Bank (K) Ltd',1,62),('451','Co-operative Bank (K) Ltd',1,63),('452','Co-operative Bank (K) Ltd',1,64),('453','Co-operative Bank (K) Ltd',1,65),('454','Co-operative Bank (K) Ltd',1,66),('455','Co-operative Bank (K) Ltd',1,67),('456','Co-operative Bank (K) Ltd',1,68),('457','Co-operative Bank (K) Ltd',1,69),('458','Co-operative Bank (K) Ltd',1,70),('459','Co-operative Bank (K) Ltd',1,71),('460','Co-operative Bank (K) Ltd',1,72),('461','Co-operative Bank (K) Ltd',1,73),('462','Co-operative Bank (K) Ltd',1,74),('505','Standard Chartered Bank',1,75),('510','Standard Chartered Bank',1,76),('515','Standard Chartered Bank',1,77),('520','Standard Chartered Bank',1,78),('525','Standard Chartered Bank',1,79),('530','Standard Chartered Bank',1,80),('535','Standard Chartered Bank',1,81),('540','Standard Chartered Bank',1,82),('545','Standard Chartered Bank',1,83),('550','Standard Chartered Bank',1,84),('555','Standard Chartered Bank',1,85),('560','Standard Chartered Bank',1,86),('570','Standard Chartered Bank',1,87),('575','Standard Chartered Bank',1,88),('580','Standard Chartered Bank',1,89),('610','Barclays Bank Kenya Ltd',1,90),('620','Barclays Bank Kenya Ltd',1,91),('625','Barclays Bank Kenya Ltd',1,92),('626','Barclays Bank Kenya Ltd',1,93),('627','Barclays Bank Kenya Ltd',1,94),('628','Barclays Bank Kenya Ltd',1,95),('629','Barclays Bank Kenya Ltd',1,96),('631','Barclays Bank Kenya Ltd',1,97),('634','Barclays Bank Kenya Ltd',1,98),('635','Barclays Bank Kenya Ltd',1,99),('636','Barclays Bank Kenya Ltd',1,100),('638','Barclays Bank Kenya Ltd',1,101),('639','Barclays Bank Kenya Ltd',1,102),('640','Barclays Bank Kenya Ltd',1,103),('641','Barclays Bank Kenya Ltd',1,104),('643','Barclays Bank Kenya Ltd',1,105),('644','Barclays Bank Kenya Ltd',1,106),('645','Barclays Bank Kenya Ltd',1,107),('646','Barclays Bank Kenya Ltd',1,108),('647','Barclays Bank Kenya Ltd',1,109),('648','Barclays Bank Kenya Ltd',1,110),('649','Barclays Bank Kenya Ltd',1,111),('650','Barclays Bank Kenya Ltd',1,112),('700','Kenya Commercial Bank Ltd',1,113),('705','Kenya Commercial Bank Ltd',1,114),('710','Kenya Commercial Bank Ltd',1,115),('715','Kenya Commercial Bank Ltd',1,116),('720','Kenya Commercial Bank Ltd',1,117),('721','Kenya Commercial Bank Ltd',1,118),('722','Kenya Commercial Bank Ltd',1,119),('723','Kenya Commercial Bank Ltd',1,120),('724','Kenya Commercial Bank Ltd',1,121),('725','Kenya Commercial Bank Ltd',1,122),('726','Kenya Commercial Bank Ltd',1,123),('727','Kenya Commercial Bank Ltd',1,124),('728','Kenya Commercial Bank Ltd',1,125),('729','Kenya Commercial Bank Ltd',1,126),('730','Kenya Commercial Bank Ltd',1,127),('731','Kenya Commercial Bank Ltd',1,128),('732','Kenya Commercial Bank Ltd',1,129),('733','Kenya Commercial Bank Ltd',1,130),('734','Kenya Commercial Bank Ltd',1,131),('735','Kenya Commercial Bank Ltd',1,132),('736','Kenya Commercial Bank Ltd',1,133),('737','Kenya Commercial Bank Ltd',1,134),('738','Kenya Commercial Bank Ltd',1,135),('739','Kenya Commercial Bank Ltd',1,136),('740','Kenya Commercial Bank Ltd',1,137),('741','Kenya Commercial Bank Ltd',1,138),('742','Kenya Commercial Bank Ltd',1,139),('743','Kenya Commercial Bank Ltd',1,140),('744','Kenya Commercial Bank Ltd',1,141),('745','Kenya Commercial Bank Ltd',1,142),('746','Kenya Commercial Bank Ltd',1,143),('747','Kenya Commercial Bank Ltd',1,144),('748','Kenya Commercial Bank Ltd',1,145),('749','Kenya Commercial Bank Ltd',1,146),('750','Kenya Commercial Bank Ltd',1,147),('751','Kenya Commercial Bank Ltd',1,148),('752','Kenya Commercial Bank Ltd',1,149),('753','Kenya Commercial Bank Ltd',1,150),('754','Kenya Commercial Bank Ltd',1,151),('755','Kenya Commercial Bank Ltd',1,152),('756','Kenya Commercial Bank Ltd',1,153),('757','Kenya Commercial Bank Ltd',1,154),('758','Kenya Commercial Bank Ltd',1,155),('759','Kenya Commercial Bank Ltd',1,156),('760','Kenya Commercial Bank Ltd',1,157),('770','Kenya Commercial Bank Ltd',1,158),('780','Kenya Commercial Bank Ltd',1,159),('781','Kenya Commercial Bank Ltd',1,160),('782','Kenya Commercial Bank Ltd',1,161),('810','Ukulima Sasa Account',1,162),('820','Ukulima Sasa Account',1,163),('830','Ukulima Sasa Account',1,164),('840','Ukulima Sasa Account',1,165),('850','Ukulima Sasa Account',1,166),('900','Teachers SACCO',1,167),('1000','Sasa Cooperative Bank',1,168),('1010','Sasa Cooperative Bank',1,169),('1100','Post Bank',1,170),('1110','Post Bank',1,171),('1120','Post Bank',1,172),('1130','Post Bank',1,173),('1140','Post Bank',1,174),('1150','Post Bank',1,175),('1160','Post Bank',1,176),('1161','POST BANK',1,177),('1200','NECO FOSA Nanyuki',1,178),('1300','K-Rep Bank',1,179),('1301','K-Rep Bank',1,180),('1400','Kipsigis Teachers Sacco',1,181),('1410','Housing Finance',1,182),('1411','Housing Finance',1,183),('1510','FOSA Muramati Tea Growers',1,184),('1520','FOSA Kirinyaga',1,185),('1600','Family Finance Build. Society',1,186),('1700','Embu Farmers Sacco',1,187),('1800','Consolidated Bank',1,188),('2000','Bank of Baroda',1,189),('01000','Kenya Commercial Bank',1,190),('02000','Standard Chartered Bank',1,289),('03000','Barclays Bank Of Kenya Limited',1,321),('05000','Bank Of India',1,362),('06000','Bank Of Baroba (Kenya) Ltd',1,363),('07000','Commercial Bank Of Africa Ltd',1,370),('08000','Habib Bank Ltd',1,372),('09000','Central Bank Of Kenya',1,377),('10000','Prime Bank Ltd',1,381),('11000','Co-Operative Bank Of Kenya Ltd',1,389),('12000','National Bank Of Kenya',1,430),('12100','Family Finance',1,455),('12102','Kirinyaga Dist. Farmers Sacco',1,456),('12107','East African Building Society',1,457),('12110','Savings And Loan',1,465),('12122','Gusii Farmers Rural Sacco',1,473),('12123','Meru Central Farmers Union Ltd',1,474),('12124','Murata Farmers Sacco Muranga',1,475),('12125','Nyeri Farmers Sacco Society',1,476),('12126','Cooperative Union Othaya',1,477),('12127','Transcom Sacco Ltd',1,478),('12128','Aembu Farmers Sacco Society',1,479),('12129','Nakuru Teachers Sacco Society',1,480),('12133','Muhigia Cooperative W S S',1,481),('12134','Kilifi Teachers Sacco Society',1,482),('12135','Muramati D Tea Growers Sacco',1,483),('12136','Kiambu Unity Fin. Coop Union',1,484),('12137','Baringo Teachers Sacco',1,485),('12138','Afya Cooperative Sacco Ltd',1,486),('12139','Ardhi Sacco Society Ltd',1,487),('12140','Nyambene Arimi Sacco Society',1,488),('12141','Jamii Cooperative Sacco',1,489),('12142','Harambee Cooperative Sacco',1,490),('12143','Buret Tea Growers Sacco Societ',1,491),('12144','Kirinyaga Tea Growers Sacco',1,492),('12145','Aberdare Farmers Sacco Society',1,493),('12146','Kiambu Teachers Sacco Society',1,494),('12147','Muranga Teachers Sacco Society',1,495),('12148','Nyeri Teachers Sacco Society',1,496),('12149','Muhigia Teachers Sacco Society',1,497),('12150','Nyandarua Teachers Sacco Socie',1,498),('12151','Masaku Teachers Sacco Society',1,499),('12152','Kitui Teachers Sacco Society',1,500),('12153','Meru Mwalimu Sacco Society Ltd',1,501),('12154','Embu Teachers Sacco Society',1,502),('12155','Isiolo Teachers Sacco Society',1,503),('12156','Marsabit Teachers Sacco Societ',1,504),('12157','Kwale Teachers Sacco Society',1,505),('12158','Taita Taveta Teachers Sacco',1,506),('12159','Lamu Teachers Sacco Society',1,507),('12160','Tana River Teachers Sacco',1,508),('12161','Kipsigis Teachers Sacco Societ',1,509),('12162','Laikipia Teachers Sacco',1,510),('12163','Ol Kejuado Teachers Sacco Soci',1,511),('12164','Narok Teachers Sacco Society',1,512),('12165','Samburu Teachers Sacco Society',1,513),('12166','Turkana Teachers Sacco Society',1,514),('12167','Transnzoia Teachers Sacco Soci',1,515),('12168','Wareng Teachers Sacco Society',1,516),('12169','Nandi Teachers Sacco Society',1,517),('12170','Keiyo Teachers Sacco Society',1,518),('12171','Kapenguria Teachers Sacco Soci',1,519),('12172','Kakamega Teachers Sacco So',1,520),('12173','Bungoma Teachers Sacco Society',1,521),('12174','Busia Teachers Sacco Society',1,522),('12175','Gusii Mwalimu Sacco Society',1,523),('12176','Kisumu Teachers Sacco Society',1,524),('12177','Siaya Teachers Sacco Society',1,525),('12178','South Nyanza Teachers Sacco',1,526),('12179','Garissa Teachers Sacco Society',1,527),('12180','Mandera Teachers Sacco Society',1,528),('12181','Wajir Teachers Sacco Society',1,529),('12182','Tena Sacco Society Ltd',1,530),('12183','Mombasa Teachers Sacco Society',1,531),('12184','Tharaka Nithi Teachers Sacco',1,532),('12185','Vihiga Teachers Sacco Society',1,533),('12186','Migori Teachers Sacco Society',1,534),('12187','Mwingi Mwalimu Sacco Society',1,535),('12188','Elgon Teachers Sacco Society',1,536),('12189','Kuria Teachers Sacco Society',1,537),('12190','Marakwet Teachers Sacco Societ',1,538),('12191','Thika District Teachers Sacco',1,539),('12192','Suba Teachers Sacco Society',1,540),('12201','Mwalimu Sacco Society Ltd',1,541),('12204','Elimu Sacco Society Ltd',1,542),('12206','Ukulima Co-op Savings & Credit',1,543),('12214','Kimute Sacco Society Ltd',1,544),('12215','Magereza Fosa Society',1,545),('12216','Mulot Teachers Sacco Society',1,546),('12217','Nyamira Tea Farmers Sacco Ltd',1,547),('12218','Meru North Farmers Sacco Ltd',1,548),('12219','Baringo Teachers Sacco',1,549),('12220','Savings And Credit Bank Thika',1,550),('12221','Narok Teachers Sacco Society',1,551),('12222','Muki Savings & Credit Society',1,552),('12223','Necco Fosa Sacco Ltd',1,553),('12224','Nyeri Farmers Sacco Society',1,554),('12226','Gitanga Building Society Ltd',1,555),('12227','Shelter Building Society Ltd',1,556),('12228','Regional Loans Building Societ',1,557),('12229','Continental Credit Finance',1,558),('12230','Kiambu Tea Growers Sacco Ltd',1,559),('12231','Kagwe Catholic Church Dev Sacc',1,560),('12232','Bobasi Growers Tea Sacco Ltd',1,561),('12233','Mathira Tea Growers Sacco Ltd',1,562),('12234','Meru South Farmers Sacco Ltd',1,563),('12235','Laikipia Teachers Sacco',1,564),('12236','Nyeri Tea Growers Sacco Ltd',1,565),('12237','Sasa Account Narok Branch',1,566),('12238','Kiambu Coffee Growers Co-op Un',1,567),('12239','Olkalou Farmers Sacco',1,568),('12240','Kitui Teachers Sacco',1,569),('12241','Puan Savings And Credit Narok',1,570),('12242','Kiambu Coffee Growers Coop Uni',1,571),('12243','Rachuonyo Teachers Sacco',1,572),('12244','Bondo Teachers Sacco',1,573),('12245','Ufanisi Sacco Society',1,574),('12246','Samburu Teachers Sacco Ltd',1,575),('12247','Mugania Tea Growers Sacco',1,576),('14000','The Oriental Bank Ltd',1,577),('16000','Citibank N.A',1,582),('17000','Habib Bank A.G Zurich',1,584),('18000','Middle East Bank Kenya Ltd',1,588),('19000','Bank Of Africa Ltd',1,591),('20000','Dubai Bank Kenya Ltd',1,593),('25000','Credit Bank Ltd',1,607),('26000','Trans-National Bank',1,608),('30000','Chase Bank (K) Ltd',1,613),('31000','Stanbic Bank Kenya Ltd',1,615),('32000','Cfc Bank',1,617),('39000','Imperial Bank',1,630),('41000','N.I.C Bank',1,633),('42000','Giro Bank',1,640),('43000','Akiba Bank Ltd',1,646),('43004','EABS Bank',1,650),('49000','Equitorial Commercial Bank Ltd',1,656),('50000','Paramount Bank',1,657),('53000','Fina Bank Ltd',1,662),('54000','Victoria Commercial Bank Ltd',1,667),('55000','Guardian Bank',1,669),('55001','Guardian Bank',1,670),('55002','Guardian Bank',1,671),('55003','Guardian Bank',1,672),('57000','Investment & Mortgages Bank',1,673),('58000','Southern Credit Banking Corp.',1,682),('59000','Development Bank Of Kenya Ltd',1,691),('60000','Fidelity Commercial Bank',1,692),('63000','Diamond Trust Bank',1,695),('64000','CharterHouse Bank',1,701),('66000','K-Rep Bank',1,704),('68000','Equity Bank',1,722),('70000','Family Bank',1,767),('97000','Post Bank',1,768),('98000','Housing Finance',1,854),('01000A','Kenya Commercial Bank',1,863),('03000A','Barclays Bank Of Kenya Limited',1,864),('12100A','Family Finance',1,865),('12100B','Family Finance',1,866),('12100C','Family Finance',1,867),('12100D','Family Finance',1,868),('12100E','Family Finance',1,869),('12100F','Family Finance',1,870),('12100G','Family Finance',1,871),('12100H','Family Finance',1,872),('12100I','Family Finance',1,873),('12100J','Family Finance',1,874);
/*!40000 ALTER TABLE `employeebanks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employeebnkbranch`
--

DROP TABLE IF EXISTS `employeebnkbranch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employeebnkbranch` (
  `parentcode` varchar(20) NOT NULL,
  `code` varchar(20) NOT NULL,
  `bankbranch` varchar(50) DEFAULT NULL,
  `address` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employeebnkbranch`
--

LOCK TABLES `employeebnkbranch` WRITE;
/*!40000 ALTER TABLE `employeebnkbranch` DISABLE KEYS */;
INSERT INTO `employeebnkbranch` VALUES ('737','1027','Kenya Commercial Bank Ltd','Garissa'),('738','1026','Kenya Commercial Bank Ltd','Embu'),('739','1025','Kenya Commercial Bank Ltd','Baringo'),('740','1024','Kenya Commercial Bank Ltd','Wundanyi'),('741','1023','Kenya Commercial Bank Ltd','Wote'),('742','1022','Kenya Commercial Bank Ltd','Voi'),('743','1021','Kenya Commercial Bank Ltd','University Way'),('744','1020','Kenya Commercial Bank Ltd','Ukunda'),('745','1019','Kenya Commercial Bank Ltd','Machakos'),('746','1018','Kenya Commercial Bank Ltd','Kilindini'),('747','1017','Kenya Commercial Bank Ltd','KICC'),('748','1016','Kenya Commercial Bank Ltd','Kajiado'),('749','1015','Kenya Commercial Bank Ltd','Siaya'),('750','1014','Kenya Commercial Bank Ltd','Mumias'),('751','1013','Kenya Commercial Bank Ltd','Milimani'),('752','1012','Kenya Commercial Bank Ltd','Kakamega'),('753','1030','Kenya Commercial Bank Ltd','Capital Hill Branch'),('754','1031','Kenya Commercial Bank Ltd','Karatina'),('755','1032','Kenya Commercial Bank Ltd','Lamu'),('756','1033','Kenya Commercial Bank Ltd','Mandera'),('757','1034','Kenya Commercial Bank Ltd','Marsabit'),('758','1035','Kenya Commercial Bank Ltd','Nyeri Branch'),('759','1036','Kenya Commercial Bank Ltd','River Road'),('760','1037','Kenya Commercial Bank Ltd','Tala Branch'),('770','1038','Kenya Commercial Bank Ltd','Eldama Ravine'),('780','1039','Kenya Commercial Bank Ltd','Kapenguria'),('781','1040','Kenya Commercial Bank Ltd','MOYALE'),('782','1050','Kenya Commercial Bank Ltd','Kericho Branch'),('810','96091','Ukulima Sasa Account','Haile Selassie Branch'),('820','26003','Ukulima Sasa Account','Ukulima'),('830','26002','Ukulima Sasa Account','Nairobi'),('840','26001','Ukulima Sasa Account','Ukulima House Nairobi'),('850','26000','Ukulima Sasa Account','Ukulima'),('900','25001','Teachers SACCO','Kabarnet'),('1000','23001','Sasa Cooperative Bank','Ukulima House'),('1010','23000','Sasa Cooperative Bank','Ukulima'),('1100','22007','Post Bank','Nakuru Branch'),('1110','22006','Post Bank','Machakos'),('1120','22005','Post Bank','Kisumu Branch'),('1130','22004','Post Bank','Kiambu'),('1140','22003','Post Bank','Kakamega'),('1150','22002','Post Bank','Garissa'),('1160','22001','Post Bank','Embu'),('1161','22002','POST BANK','BUSIA'),('1200','21001','NECO FOSA Nanyuki','Nanyuki Branch'),('1300','8701','K-Rep Bank','Embu'),('1301','8705','K-Rep Bank','Nairobi'),('1400','8601','Kipsigis Teachers Sacco','Bomet'),('1410','17002','Housing Finance','Nakuru Branch'),('1411','17003','Housing Finance','Eldoret Branch'),('1510','11002','FOSA Muramati Tea Growers','Muranga'),('1520','11001','FOSA Kirinyaga','Kerugoya'),('1600','1401','Family Finance Build. Society','Sonulax'),('1700','1301','Embu Farmers Sacco','Embu'),('1800','1001','Consolidated Bank','Embu'),('2000','1000','Bank of Baroda','Kisumu Branch'),('01000','01091','Kenya Commercial Bank','Eastleigh Branch'),('01000','01092','Kenya Commercial Bank','Central Clearing Centre'),('01000','01093','Kenya Commercial Bank','Mtwapa Branch'),('01000','01094','Kenya Commercial Bank','Head Office'),('01000','01095','Kenya Commercial Bank','Wote Branch'),('01000','01097','Kenya Commercial Bank','Forex Centre'),('01000','01101','Kenya Commercial Bank','Kipande House'),('01000','01102','Kenya Commercial Bank','Treasury Sq.Msa'),('01000','01103','Kenya Commercial Bank','Nakuru Br.'),('01000','01104','Kenya Commercial Bank','K.I.C.C Br.'),('01000','01105','Kenya Commercial Bank','Kisumu Br.'),('01000','01106','Kenya Commercial Bank','Kericho Br'),('01000','01107','Kenya Commercial Bank','Tom Mboya St'),('01000','01108','Kenya Commercial Bank','Thika Br.'),('01000','01109','Kenya Commercial Bank','Eldoret Br.'),('01000','01110','Kenya Commercial Bank','Kakamega Br'),('01000','01111','Kenya Commercial Bank','Kilindini Br.'),('01000','01112','Kenya Commercial Bank','Nyeri Br.'),('01000','01113','Kenya Commercial Bank','Industrial Area'),('01000','01114','Kenya Commercial Bank','River Road'),('01000','01115','Kenya Commercial Bank','Muranga Br.'),('01000','01116','Kenya Commercial Bank','Embu Br'),('01000','01117','Kenya Commercial Bank','Kangema Br.'),('01000','01118','Kenya Commercial Bank','City Centre'),('01000','01119','Kenya Commercial Bank','Kiambu Br.'),('01000','01120','Kenya Commercial Bank','Karatina Br.'),('01000','01121','Kenya Commercial Bank','Siaya Br.'),('01000','01122','Kenya Commercial Bank','Nyahururu Br.'),('01000','01123','Kenya Commercial Bank','Meru Br.'),('01000','01124','Kenya Commercial Bank','Mumias Br.'),('01000','01125','Kenya Commercial Bank','Nanyuki'),('01000','01127','Kenya Commercial Bank','Moyale'),('01000','01128','Kenya Commercial Bank','Kenyatta Ave.'),('01000','01129','Kenya Commercial Bank','Kikuyu'),('01000','01130','Kenya Commercial Bank','Tala'),('01000','01131','Kenya Commercial Bank','Kajiado'),('01000','01133','Kenya Commercial Bank','Trustee'),('01000','01134','Kenya Commercial Bank','Matuu'),('01000','01135','Kenya Commercial Bank','Kitui'),('01000','01136','Kenya Commercial Bank','Mvita'),('01000','01137','Kenya Commercial Bank','Jogoo Road Branch'),('01000','01138','Kenya Commercial Bank','Sabasaba'),('01000','01139','Kenya Commercial Bank','Card Center'),('01000','01140','Kenya Commercial Bank','Marsabit'),('01000','01141','Kenya Commercial Bank','Sarit Centre Br'),('01000','01142','Kenya Commercial Bank','Loitokitok Br.'),('01000','01143','Kenya Commercial Bank','Nandi Hills'),('01000','01144','Kenya Commercial Bank','Lodwar Br'),('01000','01145','Kenya Commercial Bank','U.N. Gigiri'),('01000','01146','Kenya Commercial Bank','Hola Br.'),('01000','01147','Kenya Commercial Bank','Ruiru Br'),('01000','01148','Kenya Commercial Bank','Mwingi'),('01000','01149','Kenya Commercial Bank','Kitale Br.'),('01000','01150','Kenya Commercial Bank','Mandera Br.'),('01000','01151','Kenya Commercial Bank','Kapenguria Br.'),('01000','01152','Kenya Commercial Bank','Kabarnet Br.'),('01000','01153','Kenya Commercial Bank','Wajir Br.'),('01000','01154','Kenya Commercial Bank','Maralal'),('01000','01155','Kenya Commercial Bank','Limuru Branch'),('01000','01157','Kenya Commercial Bank','Ukunda'),('01000','01158','Kenya Commercial Bank','Iten'),('01000','01159','Kenya Commercial Bank','Gilgil'),('01000','01160','Kenya Commercial Bank','Menegai Crater'),('01000','01161','Kenya Commercial Bank','Ongata Rongai'),('01000','01162','Kenya Commercial Bank','Kitengela Branch'),('01000','01163','Kenya Commercial Bank','Eldama Ravine'),('01000','01164','Kenya Commercial Bank','Kibwezi'),('01000','01165','Kenya Commercial Bank','Gatundu'),('01000','01166','Kenya Commercial Bank','Kapsabet'),('01000','01167','Kenya Commercial Bank','University Way'),('01000','01169','Kenya Commercial Bank','Garissa'),('01000','01172','Kenya Commercial Bank','Kisumu West'),('01000','01173','Kenya Commercial Bank','Lamu'),('115','10003','National Bank of Kenya','Harambee Avenue'),('120','10005','National Bank of Kenya','Hill Branch'),('125','10002','National Bank of Kenya','Busia'),('130','10003','National Bank of Kenya','Eldoret'),('135','10004','National Bank of Kenya','Kapsabet'),('140','10006','National Bank of Kenya','Kitale'),('145','10007','National Bank of Kenya','Kitui'),('150','10008','National Bank of Kenya','Meru Branch'),('155','10009','National Bank of Kenya','Eldoret Branch'),('160','10011','National Bank of Kenya','Kisii Branch'),('165','10012','National Bank of Kenya','Kisumu Branch'),('170','10013','National Bank of Kenya','Nakuru Branch'),('175','10014','National Bank of Kenya','Narok Branch'),('205','15017','Family Finance Bldg Society','Kisii Branch'),('210','12005','Family Finance Bldg Society','KTDA Branch'),('215','15016','Family Finance Bldg Society','Kiambu'),('220','15015','Family Finance Bldg Society','Kerugoya'),('225','15014','Family Finance Bldg Society','Kangari'),('230','15013','Family Finance Bldg Society','Embu'),('235','15012','Family Finance Bldg Society','Kitale Branch'),('240','15011','Family Finance Bldg Society','Eldoret Branch'),('241','15012','Family Finance Bldg Society','SONULAX'),('242','15012','Family Finance Bldg Society','Machakos'),('305','16034','Equity Bank','Nyahururu'),('310','2','Equity Bank','Nairobi'),('315','16035','Equity Bank','Nyeri Branch'),('320','16010','Equity Bank','Nyeri Branch'),('325','16033','Equity Bank','Nanyuki Branch'),('330','16032','Equity Bank','Meru Branch'),('335','16031','Equity Bank','Isiolo'),('340','16030','Equity Bank','Isiolo'),('345','16029','Equity Bank','Tom Mboya'),('346','16036','Equity Bank','Community'),('350','16028','Equity Bank','Thika'),('355','16027','Equity Bank','Narok Branch'),('356','16026','Equity Bank','Nakuru Branch'),('357','16025','Equity Bank','Naivasha Branch'),('358','16024','Equity Bank','Moi Avenue'),('359','16023','Equity Bank','Meru'),('360','16022','Equity Bank','Kitale Branch'),('361','16021','Equity Bank','Kisumu Branch'),('362','16020','Equity Bank','Kerugoya'),('363','16019','Equity Bank','Kericho Branch'),('364','16018','Equity Bank','Karatina Branch'),('366','16016','Equity Bank','Kangema'),('367','16015','Equity Bank','Harambee Avenue'),('368','16014','Equity Bank','Embu'),('369','16013','Equity Bank','Community'),('370','16012','Equity Bank','Chuka'),('371','16011','Equity Bank','Eldoret'),('372','16012','Equity Bank','Fourway Branch'),('373','16013','Equity Bank','KENYATTA AVENUE NAKURU'),('410','8016','Co-operative Bank (K) Ltd','Ukulima House'),('420','8006','Co-operative Bank (K) Ltd','Kisumu'),('430','8004','Co-operative Bank (K) Ltd','Canon House'),('440','8024','Co-operative Bank (K) Ltd','Co-operative Bank House'),('445','1201','Co-operative Bank (K) Ltd','Machakos'),('446','1201','Co-operative Bank (K) Ltd','Kiambu'),('447','1201','Co-operative Bank (K) Ltd','Nyeri Branch'),('448','1201','Co-operative Bank (K) Ltd','Nyahururu'),('449','1201','Co-operative Bank (K) Ltd','Meru Branch'),('450','1201','Co-operative Bank (K) Ltd','Nakuru Branch'),('451','1201','Co-operative Bank (K) Ltd','Kisumu Branch'),('452','1201','Co-operative Bank (K) Ltd','Kisii Branch'),('453','1201','Co-operative Bank (K) Ltd','Kericho Branch'),('454','1201','Co-operative Bank (K) Ltd','Homa Bay Branch'),('455','1201','Co-operative Bank (K) Ltd','Bungoma Branch'),('456','1201','Co-operative Bank (K) Ltd','Ukulima'),('457','1201','Co-operative Bank (K) Ltd','Meru'),('458','1201','Co-operative Bank (K) Ltd','Karatina'),('459','1201','Co-operative Bank (K) Ltd','Embu'),('460','1201','Co-operative Bank (K) Ltd','Chuka'),('461','1201','Co-operative Bank (K) Ltd','Bomet'),('462','1201','Co-operative Bank (K) Ltd','Industrial Area-Nairobi'),('505','24015','Standard Chartered Bank','Nakuru Branch'),('510','1','Standard Chartered Bank','Kenyatta Avenue Branch'),('515','24014','Standard Chartered Bank','Koinange Street Branch'),('520','24013','Standard Chartered Bank','Kitale Branch'),('525','24012','Standard Chartered Bank','Kisumu Branch'),('530','24011','Standard Chartered Bank','Kericho Branch'),('535','24010','Standard Chartered Bank','Kakamega Branch'),('540','24009','Standard Chartered Bank','Harambe Avenue'),('545','24008','Standard Chartered Bank','Haile Selassie Branch'),('550','24007','Standard Chartered Bank','Eldoret Branch'),('555','24006','Standard Chartered Bank','Treasury'),('560','24005','Standard Chartered Bank','Maritime'),('570','24003','Standard Chartered Bank','Kitale'),('575','24002','Standard Chartered Bank','Kiambu'),('580','24001','Standard Chartered Bank','Kabarnet'),('610','2066','Barclays Bank Kenya Ltd','NIC House'),('620','2037','Barclays Bank Kenya Ltd','Nyeri'),('625','2026','Barclays Bank Kenya Ltd','Queensway Branch'),('626','2025','Barclays Bank Kenya Ltd','Queens Way Branch'),('627','2024','Barclays Bank Kenya Ltd','Nakuru Branch'),('628','2023','Barclays Bank Kenya Ltd','Lodwar Branch'),('629','2022','Barclays Bank Kenya Ltd','Kitale Branch'),('631','2020','Barclays Bank Kenya Ltd','Kericho Branch'),('634','2017','Barclays Bank Kenya Ltd','Baringo'),('635','2016','Barclays Bank Kenya Ltd','Westlands Nrb'),('636','2015','Barclays Bank Kenya Ltd','Thika'),('638','2013','Barclays Bank Kenya Ltd','Nkurumah Road'),('639','2012','Barclays Bank Kenya Ltd','Nakuru East'),('640','2011','Barclays Bank Kenya Ltd','Moi Avenue'),('641','2010','Barclays Bank Kenya Ltd','Meru Branch'),('643','2008','Barclays Bank Kenya Ltd','Malindi'),('644','2007','Barclays Bank Kenya Ltd','Machakos'),('645','2006','Barclays Bank Kenya Ltd','Kisumu'),('646','2005','Barclays Bank Kenya Ltd','Kisii'),('647','2004','Barclays Bank Kenya Ltd','Kakamega'),('648','2003','Barclays Bank Kenya Ltd','Haille Selassie'),('649','2002','Barclays Bank Kenya Ltd','Embu'),('650','2001','Barclays Bank Kenya Ltd','Eldoret'),('700','1045','Kenya Commercial Bank Ltd','River Road'),('705','1046','Kenya Commercial Bank Ltd','Tala Branch'),('710','1036','Kenya Commercial Bank Ltd','Industrial Area'),('715','1044','Kenya Commercial Bank Ltd','Nanyuki Branch'),('720','1011','Kenya Commercial Bank Ltd','Bungoma'),('721','1043','Kenya Commercial Bank Ltd','Meru Branch'),('722','1042','Kenya Commercial Bank Ltd','Marsabit'),('723','1041','Kenya Commercial Bank Ltd','Mandera Branch'),('724','1040','Kenya Commercial Bank Ltd','Lodwar Branch'),('725','1039','Kenya Commercial Bank Ltd','Lamu'),('726','1038','Kenya Commercial Bank Ltd','Karatina Branch'),('727','1037','Kenya Commercial Bank Ltd','Bomet'),('728','1036','Kenya Commercial Bank Ltd','WOTE'),('729','1035','Kenya Commercial Bank Ltd','Sotik Branch'),('730','1034','Kenya Commercial Bank Ltd','Ongata Rongai'),('731','1033','Kenya Commercial Bank Ltd','Nakuru Branch'),('732','1032','Kenya Commercial Bank Ltd','Mwingi'),('733','1031','Kenya Commercial Bank Ltd','Muranga'),('734','1030','Kenya Commercial Bank Ltd','Lodwar Branch'),('735','1029','Kenya Commercial Bank Ltd','Kisumu Branch'),('736','1028','Kenya Commercial Bank Ltd','Kerugoya'),('01000','01174','Kenya Commercial Bank','Kilifi'),('01000','01175','Kenya Commercial Bank','Milimani'),('01000','01176','Kenya Commercial Bank','Nyamira'),('01000','01177','Kenya Commercial Bank','Mukuruweini'),('01000','01180','Kenya Commercial Bank','Village Market'),('01000','01181','Kenya Commercial Bank','Bomet'),('01000','01182','Kenya Commercial Bank','Kapsokwony'),('01000','01183','Kenya Commercial Bank','Mbale'),('01000','01184','Kenya Commercial Bank','Narok'),('01000','01185','Kenya Commercial Bank','Othaya'),('01000','01186','Kenya Commercial Bank','Voi'),('01000','01188','Kenya Commercial Bank','Webuye'),('01000','01189','Kenya Commercial Bank','Sotik'),('01000','01190','Kenya Commercial Bank','Naivasha'),('01000','01191','Kenya Commercial Bank','Kisii'),('01000','01192','Kenya Commercial Bank','Migori'),('01000','01193','Kenya Commercial Bank','Githunguri'),('01000','01194','Kenya Commercial Bank','Machakos'),('01000','01195','Kenya Commercial Bank','Kerugoya'),('01000','01196','Kenya Commercial Bank','Chuka'),('01000','01197','Kenya Commercial Bank','Bungoma'),('01000','01198','Kenya Commercial Bank','Wundanyi'),('01000','01199','Kenya Commercial Bank','Malindi'),('02000','02001','Standard Chartered Bank','Kericho'),('02000','02002','Standard Chartered Bank','Kisumu'),('02000','02003','Standard Chartered Bank','Kitale'),('02000','02005','Standard Chartered Bank','Kilindini'),('02000','02007','Standard Chartered Bank','Kimathi'),('02000','02009','Standard Chartered Bank','Nakuru'),('02000','02010','Standard Chartered Bank','Nanyuki'),('02000','02011','Standard Chartered Bank','Nyeri'),('02000','02012','Standard Chartered Bank','Thika'),('02000','02015','Standard Chartered Bank','Westlands'),('02000','02016','Standard Chartered Bank','Machakos'),('02000','02017','Standard Chartered Bank','Meru'),('02000','02019','Standard Chartered Bank','Harambee'),('02000','02020','Standard Chartered Bank','Kiambu'),('02000','02054','Standard Chartered Bank','Kakamega'),('02000','02060','Standard Chartered Bank','Malindi'),('02000','02061','Standard Chartered Bank','Gatundu'),('02000','02065','Standard Chartered Bank','Kabarnet'),('02000','02070','Standard Chartered Bank','Eldoret'),('02000','02072','Standard Chartered Bank','Ruaraka'),('02000','02073','Standard Chartered Bank','Langata'),('02000','02074','Standard Chartered Bank','Makupa'),('02000','02075','Standard Chartered Bank','Karen'),('02000','02076','Standard Chartered Bank','Muthaiga'),('02000','02078','Standard Chartered Bank','Head Office'),('03000','03001','Barclays Bank Of Kenya Limited','Head Office'),('03000','03003','Barclays Bank Of Kenya Limited','Eldoret'),('03000','03004','Barclays Bank Of Kenya Limited','Embu'),('03000','03005','Barclays Bank Of Kenya Limited','Muranga'),('03000','03007','Barclays Bank Of Kenya Limited','Kericho Br..'),('03000','03008','Barclays Bank Of Kenya Limited','Kisii'),('03000','03009','Barclays Bank Of Kenya Limited','Kisumu Br.'),('03000','03011','Barclays Bank Of Kenya Limited','Limuru'),('03000','03012','Barclays Bank Of Kenya Limited','Malindi'),('03000','03013','Barclays Bank Of Kenya Limited','Meru'),('03000','03014','Barclays Bank Of Kenya Limited','Eastleigh Branch'),('03000','03017','Barclays Bank Of Kenya Limited','Garissa Branch'),('03000','03018','Barclays Bank Of Kenya Limited','Nyamira'),('03000','03019','Barclays Bank Of Kenya Limited','Kilifi'),('03000','03020','Barclays Bank Of Kenya Limited','Kiambu'),('03000','03021','Barclays Bank Of Kenya Limited','Card Centre'),('03000','03022','Barclays Bank Of Kenya Limited','Nafex Branch'),('03000','03023','Barclays Bank Of Kenya Limited','Gilgil'),('03000','03027','Barclays Bank Of Kenya Limited','Nakuru East'),('03000','03030','Barclays Bank Of Kenya Limited','Nyeri'),('03000','03031','Barclays Bank Of Kenya Limited','Thika Br.'),('03000','03035','Barclays Bank Of Kenya Limited','Homabay'),('03000','03036','Barclays Bank Of Kenya Limited','Premier Banking'),('03000','03040','Barclays Bank Of Kenya Limited','Machakos'),('03000','03043','Barclays Bank Of Kenya Limited','Ngong Branch'),('03000','03045','Barclays Bank Of Kenya Limited','Hurlingham'),('03000','03049','Barclays Bank Of Kenya Limited','Sotik'),('03000','03051','Barclays Bank Of Kenya Limited','Homabay'),('03000','03054','Barclays Bank Of Kenya Limited','Voi Branch'),('03000','03055','Barclays Bank Of Kenya Limited','Muthaiga'),('03000','03062','Barclays Bank of Kenya Limited','Kabarnet'),('03000','03065','Barclays Bank Of Kenya Limited','Karen'),('03000','03067','Barclays Bank Of Kenya Limited','Ruaraka'),('03000','03070','Barclays Bank Of Kenya Limited','Enterprise Road Nairobi'),('03000','03072','Barclays Bank Of Kenya Limited','Changamwe Nairobi'),('03000','03073','Barclays Bank Of Kenya Limited','Westlands'),('03000','03077','Barclays Bank Of Kenya Limited','Plaza Br.'),('03000','03082','Barclays Bank Of Kenya Limited','Haile Selassie'),('03000','03094','Barclays Bank Of Kenya Limited','Queensway Hse'),('05000','05001','Bank Of India','Mombasa'),('06000','06000','Bank Of Baroba (Kenya) Ltd','Main'),('06000','06002','Bank Of Baroba (Kenya) Ltd','Digo Rd Msa'),('06000','06003','Bank Of Baroba (Kenya) Ltd','Makadara Rd.'),('06000','06004','Bank Of Baroba (Kenya) Ltd','Thika'),('06000','06005','Bank Of Baroba (Kenya) Ltd','Central Square Kisumu'),('06000','06006','Bank Of Baroba (Kenya) Ltd','Sarit Centre'),('06000','06007','Bank Of Baroba (Kenya) Ltd','Industrial Area'),('07000','07000','Commercial Bank Of Africa Ltd','Nairobi'),('07000','07020','Commercial Bank Of Africa Ltd','Mombasa Br.'),('08000','08046','Habib Bank Ltd','Mombasa'),('08000','08047','Habib Bank Ltd','Malindi Br.'),('08000','08048','Habib Bank Ltd','Kimathi St.'),('08000','08049','Habib Bank Ltd','Kenyatta Ave Nbi'),('08000','08086','Habib Bank Ltd','Kisumu Br.'),('09000','09001','Central Bank Of Kenya','Head Office'),('09000','09002','Central Bank Of Kenya','Mombasa'),('09000','09003','Central Bank Of Kenya','Kisumu'),('09000','09004','Central Bank Of Kenya','Eldoret'),('10000','10001','Prime Bank Ltd','Biashara Street'),('10000','10002','Prime Bank Ltd','Mombasa'),('10000','10003','Prime Bank Ltd','Westland'),('10000','10004','Prime Bank Ltd','Westlands Branch'),('10000','10005','Prime Bank Ltd','Prime Bank'),('10000','10006','Prime Bank Ltd','Kisumu Branch'),('10000','10007','Prime Bank Ltd','Parklands Branch'),('10000','10008','Prime Bank Ltd','Riverside Drive'),('11000','11002','Co-Operative Bank Of Kenya Ltd','Co-Op House'),('11000','11003','Co-Operative Bank Of Kenya Ltd','Kisumu'),('11000','11004','Co-Operative Bank Of Kenya Ltd','Mombasa'),('11000','11005','Co-Operative Bank Of Kenya Ltd','Meru'),('11000','11006','Co-Operative Bank Of Kenya Ltd','Nakuru'),('11000','11007','Co-Operative Bank Of Kenya Ltd','Industrial Area'),('11000','11008','Co-Operative Bank Of Kenya Ltd','Kisii'),('11000','11009','Co-Operative Bank Of Kenya Ltd','Machakos'),('11000','11010','Co-Operative Bank Of Kenya Ltd','Nyeri'),('11000','11011','Co-Operative Bank Of Kenya Ltd','Ukulima'),('11000','11012','Co-Operative Bank Of Kenya Ltd','Kerugoya'),('11000','11013','Co-Operative Bank Of Kenya Ltd','Eldoret'),('11000','11016','Co-Operative Bank Of Kenya Ltd','Staff Trainning Centre'),('11000','11017','Co-Operative Bank Of Kenya Ltd','Nyahururu'),('11000','11018','Co-Operative Bank Of Kenya Ltd','Chuka'),('11000','11021','Co-Operative Bank Of Kenya Ltd','Kiambu'),('11000','11022','Co-Operative Bank Of Kenya Ltd','Homabay'),('11000','11023','Co-Operative Bank Of Kenya Ltd','Embu'),('11000','11024','Co-Operative Bank Of Kenya Ltd','Kericho'),('11000','11025','Co-Operative Bank Of Kenya Ltd','Bungoma'),('11000','11026','Co-Operative Bank Of Kenya Ltd','Muranga'),('11000','11028','Co-Operative Bank Of Kenya Ltd','Karatina'),('11000','11031','Co-Operative Bank Of Kenya Ltd','University Way'),('11000','11033','Co-Operative Bank Of Kenya Ltd','Athi River'),('11000','11034','Co-Operative Bank Of Kenya Ltd','Mumias'),('11000','11035','Co-Operative Bank Of Kenya Ltd','Stima Plaza'),('11000','11039','Co-Operative Bank Of Kenya Ltd','Thika'),('11000','11040','Co-Operative Bank Of Kenya Ltd','Nacico Hse Branch'),('11000','11041','Co-Operative Bank Of Kenya Ltd','Kariobangi Branch.'),('11000','11042','Co-Operative Bank Of Kenya Ltd','Kawangware Agency'),('11000','11043','Co-Operative Bank Of Kenya Ltd','Makutano Agency'),('11000','11044','Co-Operative Bank Of Kenya Ltd','Cannon House'),('11000','11045','Co-Operative Bank Of Kenya Ltd','Kimanthi Street'),('11000','11046','Co-Operative Bank Of Kenya Ltd','Kitale'),('11000','11047','Co-Operative Bank Of Kenya Ltd','Githurai Agency'),('11000','11048','Co-Operative Bank Of Kenya Ltd','Maua Branch'),('11000','11049','Co-Operative Bank Of Kenya Ltd','City Hall Branch'),('11000','11050','Co-Operative Bank Of Kenya Ltd','digo road branch'),('11000','11051','Co-Operative Bank Of Kenya Ltd','Nairobi Business Centre'),('11000','11052','Co-Operative Bank Of Kenya Ltd','Kakamega'),('11000','11097','Co-Operative Bank Of Kenya Ltd','C.C.C'),('12000','12002','National Bank Of Kenya','Kenyatta Ave Nbi'),('12000','12003','National Bank Of Kenya','Harambee'),('12000','12004','National Bank Of Kenya','Hill Br.'),('12000','12005','National Bank Of Kenya','Busia Br.'),('12000','12007','National Bank Of Kenya','Meru'),('12000','12008','National Bank Of Kenya','Karatina'),('12000','12009','National Bank Of Kenya','Narok'),('12000','12010','National Bank Of Kenya','Kisii Br.'),('12000','12012','National Bank Of Kenya','Nyeri'),('12000','12013','National Bank Of Kenya','Kitale'),('12000','12016','National Bank Of Kenya','Limuru'),('12000','12017','National Bank Of Kenya','Kitui'),('12000','12018','National Bank Of Kenya','Molo'),('12000','12019','National Bank Of Kenya','Bungoma'),('12000','12020','National Bank Of Kenya','Mombasa Br.'),('12000','12021','National Bank Of Kenya','Kapsabet'),('12000','12022','National Bank Of Kenya','Awendo'),('12000','12023','National Bank Of Kenya','Portway Branch Mombasa'),('12000','12025','National Bank Of Kenya','Hospital Branch'),('12000','12026','National Bank Of Kenya','Ruiru Branch'),('12000','12030','National Bank Of Kenya','Nakuru'),('12000','12050','National Bank Of Kenya','Kisumu'),('12000','12098','National Bank Of Kenya','Card Centre'),('12000','12099','National Bank Of Kenya','Head Office Finance'),('12100','12100','Family Finance','All Branches'),('12102','12102','Kirinyaga Dist. Farmers Sacco','Kirinyaga Dist. Farmers Sacco'),('12107','12103','East African Building Society','East African Building Society'),('12107','12107','East African Building Society','Fedha Towers Nairobi'),('12107','12108','East African Building Society','Westminster Branch'),('12107','12109','East African Building Society','Kisumu Branch'),('12107','12113','East African Building Society','Chambers Branch'),('12107','12114','East African Building Society','Emperor Plaza'),('12107','12115','East African Building Society','Thika Branch'),('12107','12116','East African Building Society','Eldoret Branch'),('12110','12104','Savings And Loan','Haile Selassie Avenue'),('12110','12105','Savings And Loan','Mombasa'),('12110','12110','Savings And Loan','Salama House Branch'),('12110','12117','Savings And Loan','Kisumu Branch'),('12110','12118','Savings And Loan','Nakuru Branch'),('12110','12119','Savings And Loan','Garden Plaza Branch'),('12110','12120','Savings And Loan','Thika Branch'),('12110','12121','Savings And Loan','Sarit Centre'),('12122','12122','Gusii Farmers Rural Sacco','Gusii Farmers Rural Sacco'),('12123','12123','Meru Central Farmers Union Ltd','Meru Central Farmers Union Ltd'),('12124','12124','Murata Farmers Sacco Muranga','Murata Farmers Sacco Muranga'),('12125','12125','Nyeri Farmers Sacco Society','Nyeri Farmers Sacco Society'),('12126','12126','Cooperative Union Othaya','Cooperative Union Othaya'),('12127','12127','Transcom Sacco Ltd','Transcom Sacco Ltd'),('12128','12128','Aembu Farmers Sacco Society','Aembu Farmers Sacco Society'),('12129','12129','Nakuru Teachers Sacco Society','Nakuru Teachers Sacco Society'),('12133','12133','Muhigia Cooperative W S S','Muhigia Cooperative W S S'),('12134','12134','Kilifi Teachers Sacco Society','Kilifi Teachers Sacco Society'),('12135','12135','Muramati D Tea Growers Sacco','Muramati D Tea Growers Sacco'),('12136','12136','Kiambu Unity Fin. Coop Union','Kiambu Unity Fin. Coop Union'),('12137','12137','Baringo Teachers Sacco','Baringo Teachers Sacco'),('12138','12138','Afya Cooperative Sacco Ltd','Afya Cooperative Sacco Ltd'),('12139','12139','Ardhi Sacco Society Ltd','Ardhi Sacco Society Ltd'),('12140','12140','Nyambene Arimi Sacco Society','Nyambene Arimi Sacco Society'),('12141','12141','Jamii Cooperative Sacco','Jamii Cooperative Sacco'),('12142','12142','Harambee Cooperative Sacco','Harambee Cooperative Sacco'),('12143','12143','Buret Tea Growers Sacco Societ','Buret Tea Growers Sacco Societ'),('12144','12144','Kirinyaga Tea Growers Sacco','Kirinyaga Tea Growers Sacco'),('12145','12145','Aberdare Farmers Sacco Society','Aberdare Farmers Sacco Society'),('12146','12146','Kiambu Teachers Sacco Society','Kiambu Teachers Sacco Society'),('12147','12147','Muranga Teachers Sacco Society','Muranga Teachers Sacco Society'),('12148','12148','Nyeri Teachers Sacco Society','Nyeri Teachers Sacco Society'),('12149','12149','Muhigia Teachers Sacco Society','Muhigia Teachers Sacco Society'),('12150','12150','Nyandarua Teachers Sacco Socie','Nyandarua Teachers Sacco Socie'),('12151','12151','Masaku Teachers Sacco Society','Masaku Teachers Sacco Society'),('12152','12152','Kitui Teachers Sacco Society','Kitui Teachers Sacco Society'),('12153','12153','Meru Mwalimu Sacco Society Ltd','Meru Mwalimu Sacco Society Ltd'),('12154','12154','Embu Teachers Sacco Society','Embu Teachers Sacco Society'),('12155','12155','Isiolo Teachers Sacco Society','Isiolo Teachers Sacco Society'),('12156','12156','Marsabit Teachers Sacco Societ','Marsabit Teachers Sacco Societ'),('12157','12157','Kwale Teachers Sacco Society','Kwale Teachers Sacco Society'),('12158','12158','Taita Taveta Teachers Sacco','Taita Taveta Teachers Sacco'),('12159','12159','Lamu Teachers Sacco Society','Lamu Teachers Sacco Society'),('12160','12160','Tana River Teachers Sacco','Tana River Teachers Sacco'),('12161','12161','Kipsigis Teachers Sacco Societ','Kipsigis Teachers Sacco Societ'),('12162','12162','Laikipia Teachers Sacco','Laikipia Teachers Sacco'),('12163','12163','Ol Kejuado Teachers Sacco Soci','Ol Kejuado Teachers Sacco Soci'),('12164','12164','Narok Teachers Sacco Society','Narok Teachers Sacco Society'),('12165','12165','Samburu Teachers Sacco Society','Samburu Teachers Sacco Society'),('12166','12166','Turkana Teachers Sacco Society','Turkana Teachers Sacco Society'),('12167','12167','Transnzoia Teachers Sacco Soci','Transnzoia Teachers Sacco Soci'),('12168','12168','Wareng Teachers Sacco Society','Wareng Teachers Sacco Society'),('12169','12169','Nandi Teachers Sacco Society','Nandi Teachers Sacco Society'),('12170','12170','Keiyo Teachers Sacco Society','Keiyo Teachers Sacco Society'),('12171','12171','Kapenguria Teachers Sacco Soci','Kapenguria Teachers Sacco Soci'),('12172','12172','Kakamega Teachers Sacco So','Kakamega Teachers Sacco So'),('12173','12173','Bungoma Teachers Sacco Society','Bungoma Teachers Sacco Society'),('12174','12174','Busia Teachers Sacco Society','Busia Teachers Sacco Society'),('12175','12175','Gusii Mwalimu Sacco Society','Gusii Mwalimu Sacco Society'),('12176','12176','Kisumu Teachers Sacco Society','Kisumu Teachers Sacco Society'),('12177','12177','Siaya Teachers Sacco Society','Siaya Teachers Sacco Society'),('12178','12178','South Nyanza Teachers Sacco','South Nyanza Teachers Sacco'),('12179','12179','Garissa Teachers Sacco Society','Garissa Teachers Sacco Society'),('12180','12180','Mandera Teachers Sacco Society','Mandera Teachers Sacco Society'),('12181','12181','Wajir Teachers Sacco Society','Wajir Teachers Sacco Society'),('12182','12182','Tena Sacco Society Ltd','Tena Sacco Society Ltd'),('12183','12183','Mombasa Teachers Sacco Society','Mombasa Teachers Sacco Society'),('12184','12184','Tharaka Nithi Teachers Sacco','Tharaka Nithi Teachers Sacco'),('12185','12185','Vihiga Teachers Sacco Society','Vihiga Teachers Sacco Society'),('12186','12186','Migori Teachers Sacco Society','Migori Teachers Sacco Society'),('12187','12187','Mwingi Mwalimu Sacco Society','Mwingi Mwalimu Sacco Society'),('12188','12188','Elgon Teachers Sacco Society','Elgon Teachers Sacco Society'),('12189','12189','Kuria Teachers Sacco Society','Kuria Teachers Sacco Society'),('12190','12190','Marakwet Teachers Sacco Societ','Marakwet Teachers Sacco Societ'),('12191','12191','Thika District Teachers Sacco','Thika District Teachers Sacco'),('12192','12192','Suba Teachers Sacco Society','Suba Teachers Sacco Society'),('12201','12201','Mwalimu Sacco Society Ltd','Mwalimu Sacco Society Ltd'),('12204','12204','Elimu Sacco Society Ltd','Elimu Sacco Society Ltd'),('12206','12206','Ukulima Co-op Savings & Credit','Ukulima Co-op Savings & Credit'),('12214','12214','Kimute Sacco Society Ltd','Kimute Sacco Society Ltd'),('12215','12215','Magereza Fosa Society','Magereza  Fosa Society'),('12216','12216','Mulot Teachers Sacco Society','Mulot Teachers Sacco Society'),('12217','12217','Nyamira Tea Farmers Sacco Ltd','Nyamira Tea Farmers Sacco Ltd'),('12218','12218','Meru North Farmers Sacco Ltd','Meru North Farmers Sacco Ltd'),('12219','12219','Baringo Teachers Sacco','Baringo Teachers Sacco'),('12220','12220','Savings And Credit Bank Thika','Savings And Credit Bank Thika'),('12221','12221','Narok Teachers Sacco Society','Narok Teachers Sacco Society'),('12222','12222','Muki Savings & Credit Society','Muki Savings & Credit Society'),('12223','12223','Necco Fosa Sacco Ltd','Necco Fosa Sacco Ltd'),('12224','12224','Nyeri Farmers Sacco Society','Nyeri Farmers Sacco Society'),('12226','12226','Gitanga Building Society Ltd','Gitanga Building Society Ltd'),('12227','12227','Shelter Building Society Ltd','Shelter Building Society Ltd'),('12228','12228','Regional Loans Building Societ','Regional Loans Building Societ'),('12229','12229','Continental Credit Finance','Continental Credit Finance'),('12230','12230','Kiambu Tea Growers Sacco Ltd','Kiambu Tea Growers Sacco Ltd'),('12231','12231','Kagwe Catholic Church Dev Sacc','Kagwe Catholic Church Dev Sacc'),('12232','12232','Bobasi Growers Tea Sacco Ltd','Bobasi Growers Tea Sacco Ltd'),('12233','12233','Mathira Tea Growers Sacco Ltd','Mathira Tea Growers Sacco Ltd'),('12234','12234','Meru South Farmers Sacco Ltd','Meru South Farmers Sacco Ltd'),('12235','12235','Laikipia Teachers Sacco','Laikipia Teachers Sacco'),('12236','12236','Nyeri Tea Growers Sacco Ltd','Nyeri Tea Growers Sacco Ltd'),('12237','12237','Sasa Account Narok Branch','Sasa Account Narok Branch'),('12238','12238','Kiambu Coffee Growers Co-op Un','Kiambu Coffee Growers Co-op Un'),('12239','12239','Olkalou Farmers Sacco','Olkalou Farmers Sacco'),('12240','12240','Kitui Teachers Sacco','Mwingi Branch'),('12241','12241','Puan Savings And Credit Narok','Puan Savings And Credit Narok'),('12242','12242','Kiambu Coffee Growers Coop Uni','Kiambu Coffee Growers Coop Uni'),('12243','12243','Rachuonyo Teachers Sacco','Rachuonyo Teachers Sacco'),('12244','12244','Bondo Teachers Sacco','Bondo Teachers Sacco'),('12245','12245','Ufanisi Sacco Society','Ufanisi Sacco Society'),('12246','12246','Samburu Teachers Sacco Ltd','Samburu Teachers Sacco Ltd'),('12247','12247','Mugania Tea Growers Sacco','Mugania Tea Growers Sacco'),('14000','14001','The Oriental Bank Ltd','Nairobi'),('14000','14002','The Oriental Bank Ltd','Mombasa'),('14000','14003','The Oriental Bank Ltd','Nakuru'),('14000','14004','The Oriental Bank Ltd','Kisumu'),('14000','14005','The Oriental Bank Ltd','Eldoret'),('16000','16400','Citibank N.A','Mombasa'),('16000','16500','Citibank N.A','Gigiri Agency'),('17000','17000','Habib Bank A.G Zurich','Main Branch'),('17000','17001','Habib Bank A.G Zurich','Mombasa'),('17000','17002','Habib Bank A.G Zurich','Industrial Area'),('17000','17003','Habib Bank A.G Zurich','Westlands'),('18000','18001','Middle East Bank Kenya Ltd','Nairobi'),('18000','18002','Middle East Bank Kenya Ltd','Mombasa'),('18000','18003','Middle East Bank Kenya Ltd','Milimani Road'),('19000','19000','Bank Of Africa Ltd','Head Office'),('19000','19001','Bank Of Africa Ltd','Mombasa'),('20000','20001','Dubai Bank Kenya Ltd','Kenyatta'),('20000','23000','Consolidated Bank','Harambee Avenue'),('20000','23001','Consolidated Bank','Muranga'),('20000','23002','Consolidated Bank','Embu'),('20000','23003','Consolidated Bank','Nkrumah Road Mombasa'),('20000','23004','Consolidated Bank','Koinange Street'),('20000','23005','Consolidated Bank','Thika'),('20000','23006','Consolidated Bank','Meru'),('20000','23007','Consolidated Bank','Nyeri'),('20000','23008','Consolidated Bank','Nyerere Avenue Mombasa'),('20000','23009','Consolidated Bank','Maua Branch'),('20000','23010','Consolidated Bank','Isiolo Branch'),('20000','23011','Consolidated Bank','Head Office'),('20000','23999','Consolidated Bank','Muranga'),('25000','25000','Credit Bank Ltd','Credit Bank Ltd'),('26000','26001','Trans-National Bank','Head Office'),('26000','26002','Trans-National Bank','Mombasa'),('26000','26003','Trans-National Bank','Eldoret'),('26000','26004','Trans-National Bank','Nakuru Br.'),('26000','26009','Trans-National Bank','lenguroini'),('30000','30001','Chase Bank (K) Ltd','Nairobi'),('30000','30002','Chase Bank (K) Ltd','Nairobi'),('31000','31001','Stanbic Bank Kenya Ltd','Mombasa'),('31000','31002','Stanbic Bank Kenya Ltd','Stanbic Bank Kenya Ltd'),('32000','32000','Cfc Bank','Chiromo Road'),('32000','32001','Cfc Bank','Kimathi Street'),('32000','32002','Cfc Bank','Mombasa'),('32000','32003','Cfc Bank','Upper Hill Medical Centre'),('32000','32004','Cfc Bank','Naivasha'),('32000','35000','Abc Bank','Koinange'),('32000','35001','Abc Bank','Westlands'),('32000','35002','Abc Bank','Industrial Area'),('32000','35003','Abc Bank','Mombasa'),('32000','35004','Abc Bank','Kisumu'),('32000','35005','Abc Bank','Eldoret'),('32000','35006','Abc Bank','Meru'),('32000','35999','Abc Bank','Clearing Centre'),('39000','39001','Imperial Bank','Ips Branch'),('39000','39002','Imperial Bank','Mombasa'),('39000','39003','Imperial Bank','Hill Branch'),('41000','41000','N.I.C Bank','Head Office'),('41000','41101','N.I.C Bank','City Centre'),('41000','41102','N.I.C Bank','N.I.C House'),('41000','41103','N.I.C Bank','Harbor Branch (mombasa)'),('41000','41105','N.I.C Bank','Westlands Branch'),('41000','41106','N.I.C Bank','The Junction Branch'),('41000','41107','N.I.C Bank','Nakuru'),('42000','42000','Giro Bank','Banda Street'),('42000','42001','Giro Bank','Mombasa'),('42000','42002','Giro Bank','Industrial Area'),('42000','42003','Giro Bank','Kimathi'),('42000','42004','Giro Bank','Kisumu'),('42000','42005','Giro Bank','Westlands'),('43000','43000','Akiba Bank Ltd','Fedha Towers'),('43000','43001','Akiba Bank Ltd','Moi Ave Nbi'),('43000','43002','Akiba Bank Ltd','Mombasa'),('43000','43003','Akiba Bank Ltd','Plaza 2000'),('43004','43004','EABS Bank','Westminster House'),('43004','43005','EABS Bank','Chambers Tom Mboya Street'),('43004','43006','EABS Bank','Thika'),('43004','43007','EABS Bank','Eldoret'),('43004','43008','EABS Bank','Kisumu'),('43004','43100','EABS Bank','Head Office'),('49000','49001','Equitorial Commercial Bank Ltd','Equitorial Commercial Bank Ltd'),('50000','50001','Paramount Bank','Westlands Br.'),('50000','50002','Paramount Bank','Parklands'),('50000','50003','Paramount Bank','City Centre'),('50000','51000','City Finance Bank','ead Office'),('50000','51001','City Finance Bank','Koinange Street'),('53000','53001','Fina Bank Ltd','Fina Bank Ltd'),('53000','53002','Fina Bank','Industrial Area'),('53000','53003','Fina Bank','Westlands Branch'),('53000','53004','Fina Bank','Lavington Branch'),('53000','53005','Fina Bank','Mombasa'),('54000','54001','Victoria Commercial Bank Ltd','Upper Hill'),('54000','54002','Victoria Commercial Bank Ltd','Kisumu'),('55000','55001','Guardian Bank','Guardian Bank'),('55001','55003','Guardian Bank','Mombasa'),('55002','55004','Guardian Bank','Guardian Bank'),('55003','55005','Guardian Bank','Kisumu'),('57000','57000','Investment & Mortgages Bank','Kenyatta Avenue Street'),('57000','57001','Investment & Mortgages Bank','2nd Ngong Avenue'),('57000','57002','Investment & Mortgages Bank','Sarit Centre'),('57000','57003','Investment & Mortgages Bank','Bank H/O'),('57000','57004','Investment & Mortgages Bank','Biashara Street'),('57000','57005','Investment & Mortgages Bank','Mombasa'),('57000','57006','Investment & Mortgages Bank','Industrial Area'),('57000','57007','Investment & Mortgages Bank','Kisumu Branch'),('57000','57009','Investment & Mortgages Bank','Panari Branch'),('58000','58001','Southern Credit Banking Corp.','Main Branch'),('58000','58002','Southern Credit Banking Corp.','Mombasa'),('58000','58003','Southern Credit Banking Corp.','City Centre'),('58000','58004','Southern Credit Banking Corp.','Westlands'),('58000','58005','Southern Credit Banking Corp.','Kisumu'),('58000','58006','Southern Credit Banking Corp.','Industrial Area'),('58000','58007','Southern Credit Banking Corp.','Kakamega Branch'),('58000','58008','Southern Credit Banking Corp.','Eldoret'),('58000','58009','Southern Credit Banking Corp.','Nyali Beach'),('59000','59001','Development Bank Of Kenya Ltd','Loita Street'),('60000','60001','Fidelity Commercial Bank','Kimathi Street'),('60000','60002','Fidelity Commercial Bank','Westlands'),('60000','60003','Fidelity Commercial Bank','Industrial Area'),('63000','63001','Diamond Trust Bank','Nation Centre'),('63000','63002','Diamond Trust Bank','Mombasa'),('63000','63003','Diamond Trust Bank','Kisumu'),('63000','63004','Diamond Trust Bank','Kisumu'),('63000','63005','Diamond Trust Bank','Parklands Branch'),('63000','63008','Diamond Trust Bank','Mombasa Road Branch'),('64000','64007','CharterHouse Bank','Nyali Branch'),('64000','64008','CharterHouse Bank','Likoni Branch'),('64000','64009','CharterHouse Bank','Mega City Branch'),('66000','66001','K-Rep Bank','Headoffice'),('66000','66002','K-Rep Bank','Mombasa'),('66000','66003','K-Rep Bank','Kenyatta Avenue'),('66000','66004','K-Rep Bank','Nakuru Branch'),('66000','66005','K-Rep Bank','Nyeri Branch'),('66000','66006','K-Rep Bank','Buru Buru Branch'),('66000','66007','K-Rep Bank','Embu Branch'),('66000','66008','K-Rep Bank','Eldoret Branch'),('66000','66009','K-Rep Bank','kisumu branch'),('66000','66012','K-Rep Bank','Thika'),('66000','66017','K-Rep Bank','Kitui'),('66000','66021','K-Rep Bank','Kongowea Agency'),('66000','66024','K-Rep Bank','Isiolo'),('66000','66027','K-Rep Bank','Kibwezi'),('66000','66029','K-Rep Bank','Kajiando Branch'),('66000','66030','K-Rep Bank','K-Rep Bank'),('66000','66031','K-Rep Bank','Mtwapa'),('66000','66033','K-Rep Bank','Harambee Avenue'),('68000','68000','Equity Bank','Head Office'),('68000','68001','Equity Bank','Corporate Branch'),('68000','68002','Equity Bank','Fourway Towers'),('68000','68003','Equity Bank','Kangema Branch'),('68000','68004','Equity Bank','Karatina Branch'),('68000','68005','Equity Bank','Kiriani Branch'),('68000','68006','Equity Bank','Murarandia Branch'),('68000','68007','Equity Bank','Kangari Branch'),('68000','68008','Equity Bank','Othaya Branch'),('68000','68009','Equity Bank','Thika'),('68000','68010','Equity Bank','Kerugoya'),('68000','68011','Equity Bank','Nyeri'),('68000','68012','Equity Bank','Tom Mboya Street Branch'),('68000','68013','Equity Bank','Nakuru Gate House Branch'),('68000','68014','Equity Bank','Meru Branch'),('68000','68015','Equity Bank','Mama Ngina'),('68000','68016','Equity Bank','Nyahururu Branch'),('68000','68017','Equity Bank','Community Branch'),('68000','68018','Equity Bank','Community Coporate'),('68000','68019','Equity Bank','Embu Branch'),('68000','68020','Equity Bank','Naivasha Branch'),('68000','68021','Equity Bank','Chuka Branch'),('68000','68022','Equity Bank','Muranga'),('68000','68023','Equity Bank','Molo'),('68000','68024','Equity Bank','Harambee'),('68000','68025','Equity Bank','Mombasa'),('68000','68026','Equity Bank','Kimathi'),('68000','68027','Equity Bank','Nanyuki'),('68000','68028','Equity Bank','Kericho'),('68000','68029','Equity Bank','Kisumu'),('68000','68030','Equity Bank','Eldoret'),('68000','68031','Equity Bank','Nakuru Kenyatta Avenue'),('68000','68032','Equity Bank','Kariobagi'),('68000','68033','Equity Bank','Kitale'),('68000','68034','Equity Bank','Thika Bank 2'),('68000','68035','Equity Bank','Knut Bank Hse'),('68000','68036','Equity Bank','Narok'),('68000','68037','Equity Bank','Nkubu Bank Branch'),('68000','68038','Equity Bank','Mwea'),('68000','68039','Equity Bank','Cannon House'),('68000','68040','Equity Bank','Maua'),('68000','68041','Equity Bank','Isiolo'),('68000','68042','Equity Bank','Kagio'),('68000','68051','Equity Bank','kisii'),('68000','68058','Equity Bank','Garissa'),('70000','70077','Family Bank','Bungoma'),('97000','97001','Post Bank','Head Office'),('97000','97002','Post Bank','Githurai'),('97000','97003','Post Bank','Cannon House'),('97000','97004','Post Bank','Tom Mboya Street'),('97000','97005','Post Bank','Ronald Ngala'),('97000','97006','Post Bank','Ngara'),('97000','97007','Post Bank','Juja'),('97000','97008','Post Bank','Jogoo Road'),('97000','97009','Post Bank','Kenyatta Market'),('97000','97010','Post Bank','Uthiru'),('97000','97011','Post Bank','Afya Centre'),('97000','97012','Post Bank','Viwandani'),('97000','97013','Post Bank','Westlands'),('97000','97014','Post Bank','Karen'),('97000','97015','Post Bank','Kericho'),('97000','97016','Post Bank','Ruiru'),('97000','97017','Post Bank','Eldoret Branch'),('97000','97018','Post Bank','Kiambu'),('97000','97019','Post Bank','Nakuru'),('97000','97020','Post Bank','Kitui'),('97000','97021','Post Bank','Kisii'),('97000','97022','Post Bank','Ngong'),('97000','97023','Post Bank','Madaraka (Thika)'),('97000','97024','Post Bank','Meru'),('97000','97025','Post Bank','Mombasa'),('97000','97026','Post Bank','Nyeri'),('97000','97027','Post Bank','Embu'),('97000','97028','Post Bank','Kitale'),('97000','97029','Post Bank','Kabarnet'),('97000','97030','Post Bank','Docks Mombasa'),('97000','97031','Post Bank','Savani House'),('97000','97032','Post Bank','Moi Avenue Mombasa'),('97000','97033','Post Bank','Chaani Mombasa'),('97000','97034','Post Bank','Thika'),('97000','97035','Post Bank','Narok'),('97000','97036','Post Bank','Enterprise Road'),('97000','97037','Post Bank','Kisumu'),('97000','97038','Post Bank','Limuru'),('97000','97039','Post Bank','Garissa'),('97000','97040','Post Bank','Eastleigh'),('97000','97041','Post Bank','wingi Branch'),('97000','97042','Post Bank','Kitui'),('97000','97043','Post Bank','Nacico Plaza'),('97000','97044','Post Bank','Export Processing Centre'),('97000','97045','Post Bank','Machakos Branch'),('97000','97046','Post Bank','Nyahururu'),('97000','97048','Post Bank','Kapsabet Branch'),('97000','97049','Post Bank','Molo'),('97000','97050','Post Bank','Kerugoya'),('97000','97051','Post Bank','Muranga'),('97000','97052','Post Bank','Nyali Branch'),('97000','97053','Post Bank','Likoni'),('97000','97055','Post Bank','Voi'),('97000','97056','Post Bank','Malindi Branch'),('97000','97057','Post Bank','Ukunda'),('97000','97058','Post Bank','Mtwapa'),('97000','97059','Post Bank','Kilifi Branch'),('97000','97060','Post Bank','Bungoma'),('97000','97061','Post Bank','Homa Bay Branch'),('97000','97062','Post Bank','Kakamega Branch'),('97000','97063','Post Bank','Busia Branch'),('97000','97064','Post Bank','Mumias Branch'),('97000','97066','Post Bank','Siaya'),('97000','97067','Post Bank','Naivasha'),('97000','97068','Post Bank','Molo Branch'),('97000','97069','Post Bank','Karatina'),('97000','97070','Post Bank','Kikuyu'),('97000','97071','Post Bank','Wabera'),('97000','97072','Post Bank','Bondo'),('97000','97073','Post Bank','Migori'),('97000','97074','Post Bank','Webuye'),('97000','97075','Post Bank','Makueni'),('97000','97076','Post Bank','Nanyuki'),('97000','97078','Post Bank','Bomet'),('97000','97079','Post Bank','Nandi Hills'),('97000','97080','Post Bank','Luanda'),('97000','97081','Post Bank','Kangundo'),('97000','97082','Post Bank','Rongai'),('97000','97089','Post Bank','Eldoret'),('97000','97500','Post Bank','Machakos Branch'),('97000','97501','Post Bank','Mwingi Branch'),('97000','97502','Post Bank','Malindi Branch'),('97000','97503','Post Bank','Kapsabet Branch'),('97000','97504','Post Bank','Ukunda Branch'),('97000','97505','Post Bank','Voi Branch'),('97000','97506','Post Bank','Kikuyu Branch'),('98000','98001','Housing Finance','Nyeri'),('98000','98002','Housing Finance','Nakuru'),('98000','98003','Housing Finance','Rehani House'),('98000','98004','Housing Finance','Mombasa'),('98000','98005','Housing Finance','Eldoret'),('98000','98006','Housing Finance','Kisumu'),('98000','98007','Housing Finance','Thika'),('98000','98008','Housing Finance','Kenyatta Market'),('98000','98009','Housing Finance','Gill House Branch'),('01000A','01175','Kenya Commercial Bank','Capital Hill'),('03000A','03003','Barclays Bank Of Kenya Limited','Kitale'),('12100A','12100','Family Finance','Eldoret'),('12100B','12100','Family Finance','Embu'),('12100C','12100','Family Finance','Kangari'),('12100D','12100','Family Finance','Kerugoya'),('12100E','12100','Family Finance','Kiambu'),('12100F','12100','Family Finance','Kisii'),('12100G','12100','Family Finance','Kitale'),('12100H','12100','Family Finance','KTDA'),('12100I','12100','Family Finance','Machakos'),('12100J','12100','Family Finance','Sonulax'),('70000','70024','Family Bank','Ruiru');
/*!40000 ALTER TABLE `employeebnkbranch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `geocode_param`
--

DROP TABLE IF EXISTS `geocode_param`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geocode_param` (
  `geocodeid` smallint NOT NULL,
  `geocode_key` varchar(200) NOT NULL,
  `center_long` varchar(20) NOT NULL,
  `center_lat` varchar(20) NOT NULL,
  `map_height` varchar(10) NOT NULL,
  `map_width` varchar(10) NOT NULL,
  `map_host` varchar(50) NOT NULL,
  PRIMARY KEY (`geocodeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `geocode_param`
--

LOCK TABLES `geocode_param` WRITE;
/*!40000 ALTER TABLE `geocode_param` DISABLE KEYS */;
/*!40000 ALTER TABLE `geocode_param` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mailgroupdetails`
--

DROP TABLE IF EXISTS `mailgroupdetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailgroupdetails` (
  `groupname` varchar(100) NOT NULL,
  `userid` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mailgroupdetails`
--

LOCK TABLES `mailgroupdetails` WRITE;
/*!40000 ALTER TABLE `mailgroupdetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `mailgroupdetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mailgroups`
--

DROP TABLE IF EXISTS `mailgroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailgroups` (
  `id` int NOT NULL,
  `groupname` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailgroups$mailgroups$groupname` (`groupname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mailgroups`
--

LOCK TABLES `mailgroups` WRITE;
/*!40000 ALTER TABLE `mailgroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `mailgroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `netprlproducts`
--

DROP TABLE IF EXISTS `netprlproducts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `netprlproducts` (
  `code` int NOT NULL,
  `description` varchar(50) NOT NULL,
  `deduction` tinyint(1) NOT NULL,
  `hastable` int NOT NULL,
  `employerfactor` int DEFAULT NULL,
  `pens_tax` int DEFAULT NULL,
  `membership_no` varchar(150) DEFAULT NULL,
  `codenav` varchar(20) DEFAULT NULL,
  `codenavd` varchar(20) DEFAULT NULL,
  `Pagefilter` varchar(50) DEFAULT NULL,
  `glaccountlink` varchar(20) DEFAULT NULL,
  `bal_Pagefilter` varchar(50) DEFAULT NULL,
  `bal_Glaccount` varchar(20) DEFAULT NULL,
  `DimensionId` int DEFAULT NULL,
  `DimensionValue` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `netprlproducts`
--

LOCK TABLES `netprlproducts` WRITE;
/*!40000 ALTER TABLE `netprlproducts` DISABLE KEYS */;
/*!40000 ALTER TABLE `netprlproducts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periods`
--

DROP TABLE IF EXISTS `periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periods` (
  `periodno` smallint NOT NULL DEFAULT '0',
  `lastdate_in_period` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`periodno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periods`
--

LOCK TABLES `periods` WRITE;
/*!40000 ALTER TABLE `periods` DISABLE KEYS */;
INSERT INTO `periods` VALUES (0,'2017-12-31 21:00:00'),(1,'2018-02-27 21:00:00'),(2,'2018-03-30 21:00:00'),(3,'2018-04-29 21:00:00'),(4,'2018-05-30 21:00:00'),(5,'2018-06-29 21:00:00'),(6,'2018-07-30 21:00:00'),(7,'2018-08-30 21:00:00'),(8,'2018-09-29 21:00:00'),(9,'2018-10-30 21:00:00'),(10,'2018-11-29 21:00:00'),(11,'2018-12-30 21:00:00'),(12,'2019-01-30 21:00:00'),(13,'2019-02-27 21:00:00'),(14,'2019-03-30 21:00:00'),(15,'2019-04-29 21:00:00'),(16,'2019-05-30 21:00:00'),(17,'2019-06-29 21:00:00'),(18,'2019-07-30 21:00:00'),(19,'2019-08-30 21:00:00'),(20,'2019-09-29 21:00:00'),(21,'2019-10-30 21:00:00'),(22,'2019-11-29 21:00:00'),(23,'2019-12-30 21:00:00'),(24,'2020-01-30 21:00:00'),(25,'2020-02-28 21:00:00'),(26,'2020-03-30 21:00:00'),(27,'2020-04-29 21:00:00'),(28,'2020-05-30 21:00:00'),(29,'2020-06-29 21:00:00'),(30,'2020-07-30 21:00:00'),(31,'2020-08-30 21:00:00'),(32,'2020-09-29 21:00:00'),(33,'2020-10-30 21:00:00'),(34,'2020-11-29 21:00:00'),(35,'2020-12-30 21:00:00'),(36,'2021-01-30 21:00:00'),(37,'2021-02-27 21:00:00'),(38,'2021-03-30 21:00:00'),(39,'2021-04-29 21:00:00'),(40,'2021-05-30 21:00:00'),(41,'2021-06-29 21:00:00'),(42,'2021-07-30 21:00:00'),(43,'2021-08-30 21:00:00'),(44,'2021-09-29 21:00:00'),(45,'2021-10-30 21:00:00'),(46,'2021-11-29 21:00:00'),(47,'2021-12-30 21:00:00'),(48,'2022-01-30 21:00:00'),(49,'2022-02-27 21:00:00'),(50,'2022-03-30 21:00:00'),(51,'2022-04-29 21:00:00'),(52,'2022-05-30 21:00:00'),(53,'2022-06-29 21:00:00'),(54,'2022-07-30 21:00:00'),(55,'2022-08-30 21:00:00'),(56,'2022-09-29 21:00:00'),(57,'2022-10-30 21:00:00'),(58,'2022-11-29 21:00:00'),(59,'2022-12-30 21:00:00'),(60,'2023-01-30 21:00:00'),(61,'2023-02-27 21:00:00'),(62,'2023-03-30 21:00:00'),(63,'2023-04-29 21:00:00'),(64,'2023-05-30 21:00:00');
/*!40000 ALTER TABLE `periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlauthorizers`
--

DROP TABLE IF EXISTS `prlauthorizers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlauthorizers` (
  `userdepartment` int NOT NULL,
  `positionapprover` int NOT NULL,
  `positionaplevel` int NOT NULL DEFAULT '9',
  `uniq` varchar(64) NOT NULL,
  KEY `FK_prlauthorizers_prldepartments` (`userdepartment`),
  KEY `FK_prlauthorizers_prlpositions` (`positionapprover`),
  CONSTRAINT `FK_prlauthorizers_prldepartments` FOREIGN KEY (`userdepartment`) REFERENCES `prldepartments` (`code`),
  CONSTRAINT `FK_prlauthorizers_prlpositions` FOREIGN KEY (`positionapprover`) REFERENCES `prlpositions` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlauthorizers`
--

LOCK TABLES `prlauthorizers` WRITE;
/*!40000 ALTER TABLE `prlauthorizers` DISABLE KEYS */;
INSERT INTO `prlauthorizers` VALUES (1,1,1,'93BE2CF0-DF14-466F-81CF-A9CC06E2DF54');
/*!40000 ALTER TABLE `prlauthorizers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prldepartments`
--

DROP TABLE IF EXISTS `prldepartments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prldepartments` (
  `code` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `hod` char(20) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prldepartments`
--

LOCK TABLES `prldepartments` WRITE;
/*!40000 ALTER TABLE `prldepartments` DISABLE KEYS */;
INSERT INTO `prldepartments` VALUES (1,'ACCOUNTS','1'),(2,'Production','1');
/*!40000 ALTER TABLE `prldepartments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlemployeemaster`
--

DROP TABLE IF EXISTS `prlemployeemaster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlemployeemaster` (
  `Inactive` tinyint(1) DEFAULT NULL,
  `pf_no` char(20) NOT NULL,
  `status` varchar(10) DEFAULT NULL,
  `fname` varchar(50) NOT NULL,
  `mname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `idno` varchar(50) DEFAULT NULL,
  `pin_no` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nssf_no` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nhif_no` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `emppersonalno` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `dateob` datetime(6) DEFAULT NULL,
  `dateemployed` datetime(6) DEFAULT NULL,
  `dateterminated` datetime(6) DEFAULT NULL,
  `freqcode` char(1) NOT NULL,
  `branch` char(10) DEFAULT NULL,
  `bankacno` char(50) DEFAULT NULL,
  `bankcode2` varchar(20) DEFAULT NULL,
  `bankcode` varchar(20) DEFAULT NULL,
  `basicpay` decimal(18,2) DEFAULT NULL,
  `telno` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `noofhrsperday` int DEFAULT NULL,
  `position` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `salaryscale2` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `salaryscale` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `property1` int DEFAULT NULL,
  `property2` int DEFAULT NULL,
  `property3` int DEFAULT NULL,
  `property4` int DEFAULT NULL,
  `property5` int DEFAULT NULL,
  `property6` int DEFAULT NULL,
  `property7` int DEFAULT NULL,
  `property8` int DEFAULT NULL,
  `property9` int DEFAULT NULL,
  `property10` int DEFAULT NULL,
  `department` int DEFAULT NULL,
  `insurancerelief` double DEFAULT NULL,
  `mortagerelief` double DEFAULT NULL,
  `personalrelief` double DEFAULT NULL,
  `userid` varchar(50) DEFAULT NULL,
  `userpassword` longtext,
  `lastlogin` date DEFAULT NULL,
  PRIMARY KEY (`pf_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlemployeemaster`
--

LOCK TABLES `prlemployeemaster` WRITE;
/*!40000 ALTER TABLE `prlemployeemaster` DISABLE KEYS */;
INSERT INTO `prlemployeemaster` VALUES (NULL,'CON0001',NULL,'Evelyn','Njeri','Muringo','22654664','A003862206Z',NULL,NULL,NULL,'1984-12-13 00:00:00.000000','2023-03-10 00:00:00.000000',NULL,'2','1','0150192738835','16024','330',35000.00,'0714088887','muringoevelyn@gmail.com',0,'2',NULL,NULL,0,0,0,0,0,0,0,0,0,0,2,0,0,NULL,'muringoevelyn@gmail.com','25837380',NULL),(NULL,'PR0001',NULL,'SALOME','WAMBURAH','MUGAMBI','35832167','A011292266Z','2018151081','15474507',NULL,'1997-10-25 00:00:00.000000','2022-05-15 00:00:00.000000',NULL,'1',NULL,'024000043583','70024','70000',30000.00,'0748011756','salomemugambi17@gmail.com',8,'1',NULL,NULL,0,0,0,0,0,0,0,0,0,0,1,0,0,NULL,'salomemugambi17@gmail.com','73559581',NULL),(NULL,'PR0002',NULL,'EDWARD','MUSYOKI','SILA','28810452','A013522183R','2015240295','9927756',NULL,'1990-12-13 00:00:00.000000','2022-03-10 00:00:00.000000',NULL,'1','1','01116868269500','8016','410',22100.00,'0724927368',NULL,8,NULL,NULL,NULL,0,0,0,0,0,0,0,0,0,0,2,0,0,NULL,NULL,'8614508',NULL),(NULL,'TMP0001',NULL,'DENNIS','NZIOKI','KITUNGO','38869612','A017228063W','2036406895','16897008',NULL,'1999-08-15 00:00:00.000000','2022-03-10 00:00:00.000000',NULL,'1',NULL,'01116948146400','1201','452',18200.00,'0112201510',NULL,8,NULL,NULL,NULL,0,0,0,0,0,0,0,0,0,0,2,NULL,NULL,NULL,NULL,'24897097',NULL),(NULL,'TMP0002',NULL,'DENNIS','KARATU','MAINA','36181162',NULL,NULL,'19012044',NULL,'1998-08-13 00:00:00.000000','2022-10-01 00:00:00.000000',NULL,'1',NULL,'0710182679540','16035','315',18200.00,'0741467715',NULL,8,NULL,NULL,NULL,0,0,0,0,0,0,0,0,0,0,2,NULL,NULL,NULL,NULL,'5416439',NULL),(NULL,'TMP0003',NULL,'MOHAMMED','NICHOLUS','ONGONGO','34185490',NULL,NULL,NULL,NULL,'1996-04-15 00:00:00.000000','2022-10-01 00:00:00.000000',NULL,'1',NULL,NULL,NULL,NULL,18200.00,'0726319561',NULL,8,NULL,NULL,NULL,0,0,0,0,0,0,0,0,0,0,2,NULL,NULL,NULL,NULL,'12689223',NULL),(NULL,'TMP0004',NULL,'GIDEON','ELIJAH','KAIRANGA','2440038',NULL,NULL,NULL,NULL,'1984-08-17 00:00:00.000000','2022-09-15 00:00:00.000000',NULL,'1',NULL,'01109372526210','11002','11000',18200.00,'070004901',NULL,8,NULL,NULL,NULL,0,0,0,0,0,0,0,0,0,0,2,NULL,NULL,NULL,NULL,'80156913',NULL),(NULL,'TMP0006',NULL,'WACHIRA','REUBEN','MWANGI','27307518','A006810934T','40815991X','5248701',NULL,'1988-07-14 00:00:00.000000','2023-03-10 00:00:00.000000','2023-05-31 00:00:00.000000','2',NULL,'0150195473930','68012','305',25000.00,'0745956540','mwanggi@gmail.com',8,'2',NULL,NULL,0,0,0,0,0,0,0,0,0,0,2,0,0,NULL,'mwanggi@gmail.com','48136191',NULL);
/*!40000 ALTER TABLE `prlemployeemaster` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlestablishment`
--

DROP TABLE IF EXISTS `prlestablishment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlestablishment` (
  `idcode` char(10) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `code` int NOT NULL,
  `levels` varchar(5) DEFAULT NULL,
  `county` varchar(5) DEFAULT NULL,
  `address1` varchar(50) DEFAULT NULL,
  `address2` varchar(50) DEFAULT NULL,
  `address3` varchar(50) DEFAULT NULL,
  `administrator` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlestablishment`
--

LOCK TABLES `prlestablishment` WRITE;
/*!40000 ALTER TABLE `prlestablishment` DISABLE KEYS */;
INSERT INTO `prlestablishment` VALUES (NULL,'PG Headoffice',1,'0','47',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `prlestablishment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlestablishment_details`
--

DROP TABLE IF EXISTS `prlestablishment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlestablishment_details` (
  `establishcode` char(10) NOT NULL,
  `positions` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `no` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlestablishment_details`
--

LOCK TABLES `prlestablishment_details` WRITE;
/*!40000 ALTER TABLE `prlestablishment_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlestablishment_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlgeneralitems`
--

DROP TABLE IF EXISTS `prlgeneralitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlgeneralitems` (
  `type` int NOT NULL,
  `code` int NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`code`),
  KEY `FK_prlgeneralitems_prltypes` (`type`),
  CONSTRAINT `FK_prlgeneralitems_prltypes` FOREIGN KEY (`type`) REFERENCES `prltypes` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlgeneralitems`
--

LOCK TABLES `prlgeneralitems` WRITE;
/*!40000 ALTER TABLE `prlgeneralitems` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlgeneralitems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlhumanresource`
--

DROP TABLE IF EXISTS `prlhumanresource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlhumanresource` (
  `pfno` char(20) NOT NULL,
  `prlgenitemscode` int NOT NULL,
  `prlgenitemsvalue` varchar(50) DEFAULT NULL,
  KEY `FK_prlhumanresource_prlemployeemaster` (`pfno`),
  CONSTRAINT `FK_prlhumanresource_prlemployeemaster` FOREIGN KEY (`pfno`) REFERENCES `prlemployeemaster` (`pf_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlhumanresource`
--

LOCK TABLES `prlhumanresource` WRITE;
/*!40000 ALTER TABLE `prlhumanresource` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlhumanresource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlitemaintenace`
--

DROP TABLE IF EXISTS `prlitemaintenace`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlitemaintenace` (
  `code` int NOT NULL,
  `description` varchar(30) NOT NULL,
  `taxband` int DEFAULT NULL,
  `onbasicpay` tinyint(1) DEFAULT NULL,
  `taxableamount` decimal(18,2) DEFAULT NULL,
  `percentageamount` decimal(18,2) DEFAULT NULL,
  `lowerlimit` decimal(18,2) DEFAULT NULL,
  `upperlimit` decimal(18,2) DEFAULT NULL,
  `ceiling` bigint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlitemaintenace`
--

LOCK TABLES `prlitemaintenace` WRITE;
/*!40000 ALTER TABLE `prlitemaintenace` DISABLE KEYS */;
INSERT INTO `prlitemaintenace` VALUES (32,'N.S.S.F',NULL,NULL,200.00,0.00,NULL,NULL,20000),(7,'P.A.Y.E.',1,NULL,NULL,10.00,0.00,11180.00,1118),(7,'P.A.Y.E.',2,NULL,NULL,15.00,11180.00,21714.00,1580),(7,'P.A.Y.E.',3,NULL,NULL,20.00,21714.00,32248.00,2107),(7,'P.A.Y.E.',4,NULL,NULL,25.00,32248.00,42781.00,2634),(7,'P.A.Y.E.',5,NULL,NULL,30.00,42781.00,999999.00,0),(54,'ICEA PENSION',NULL,NULL,0.00,10.00,NULL,NULL,0),(8,'NHIF',1,NULL,NULL,NULL,1.00,5999.00,150),(8,'NHIF',2,NULL,NULL,NULL,6000.00,7999.00,300),(8,'NHIF',3,NULL,NULL,NULL,8000.00,11999.00,400),(8,'NHIF',4,NULL,NULL,NULL,12000.00,14999.00,500),(8,'NHIF',5,NULL,NULL,NULL,15000.00,19999.00,600),(8,'NHIF',6,NULL,NULL,NULL,20000.00,24999.00,750),(8,'NHIF',7,NULL,NULL,NULL,25000.00,29999.00,850),(8,'NHIF',8,NULL,NULL,NULL,30000.00,34999.00,900),(8,'NHIF',9,NULL,NULL,NULL,35000.00,39999.00,950),(8,'NHIF',10,NULL,NULL,NULL,40000.00,44999.00,1000),(8,'NHIF',11,NULL,NULL,NULL,45000.00,49999.00,1100),(8,'NHIF',12,NULL,NULL,NULL,50000.00,59999.00,1200),(8,'NHIF',13,NULL,NULL,NULL,60000.00,69999.00,1300),(8,'NHIF',14,NULL,NULL,NULL,70000.00,79999.00,1400),(8,'NHIF',15,NULL,NULL,NULL,80000.00,89999.00,1500),(8,'NHIF',16,NULL,NULL,NULL,90000.00,99999.00,1600),(8,'NHIF',17,NULL,NULL,0.00,100000.00,1000000.00,1700),(4,'Gift Token',NULL,NULL,2000.00,0.00,NULL,NULL,0),(1000000,'CHAMA',NULL,NULL,3000.00,0.00,NULL,NULL,0),(53,'ADVANCE SAL',NULL,NULL,5000.00,0.00,NULL,NULL,0),(1000002,'AHL',1,NULL,NULL,15.00,0.00,10000.00,0),(1000003,'AHL',1,NULL,NULL,1.50,0.00,100000.00,0);
/*!40000 ALTER TABLE `prlitemaintenace` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prljobgroup`
--

DROP TABLE IF EXISTS `prljobgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prljobgroup` (
  `name` char(50) NOT NULL,
  `qualification` char(50) DEFAULT NULL,
  `rowid` int NOT NULL,
  PRIMARY KEY (`name`),
  KEY `FK_prljobgroup_prlqualifications` (`qualification`),
  CONSTRAINT `FK_prljobgroup_prlqualifications` FOREIGN KEY (`qualification`) REFERENCES `prlqualifications` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prljobgroup`
--

LOCK TABLES `prljobgroup` WRITE;
/*!40000 ALTER TABLE `prljobgroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `prljobgroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlleaveapprovaltrans`
--

DROP TABLE IF EXISTS `prlleaveapprovaltrans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlleaveapprovaltrans` (
  `docno` char(10) NOT NULL,
  `userdepartment` int NOT NULL,
  `position` int NOT NULL,
  `authoritylevel` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlleaveapprovaltrans`
--

LOCK TABLES `prlleaveapprovaltrans` WRITE;
/*!40000 ALTER TABLE `prlleaveapprovaltrans` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlleaveapprovaltrans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlloantrans`
--

DROP TABLE IF EXISTS `prlloantrans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlloantrans` (
  `loanindex` bigint NOT NULL,
  `amount` double NOT NULL,
  `interest` double DEFAULT NULL,
  `payroll_id` int NOT NULL,
  `pfno` char(20) DEFAULT NULL,
  KEY `FK_prlloantrans_prlstaffloans` (`loanindex`),
  CONSTRAINT `FK_prlloantrans_prlstaffloans` FOREIGN KEY (`loanindex`) REFERENCES `prlstaffloans` (`loanindex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlloantrans`
--

LOCK TABLES `prlloantrans` WRITE;
/*!40000 ALTER TABLE `prlloantrans` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlloantrans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlmatrix`
--

DROP TABLE IF EXISTS `prlmatrix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlmatrix` (
  `pfno` char(20) NOT NULL,
  `prodid` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `amount` double NOT NULL,
  `employeramount` double DEFAULT NULL,
  `deduction` tinyint(1) NOT NULL,
  `nonrecuring` tinyint(1) DEFAULT NULL,
  `posted` tinyint(1) DEFAULT NULL,
  KEY `FK_prlmatrix_prlemployeemaster` (`pfno`),
  CONSTRAINT `FK_prlmatrix_prlemployeemaster` FOREIGN KEY (`pfno`) REFERENCES `prlemployeemaster` (`pf_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlmatrix`
--

LOCK TABLES `prlmatrix` WRITE;
/*!40000 ALTER TABLE `prlmatrix` DISABLE KEYS */;
INSERT INTO `prlmatrix` VALUES ('PR0001',32,'N.S.S.F',200,NULL,0,0,NULL),('PR0001',8,'N.H.I.F.',850,0,0,0,NULL),('PR0002',32,'N.S.S.F',200,NULL,0,0,NULL),('TMP0001',32,'N.S.S.F',200,NULL,0,0,NULL),('TMP0004',32,'N.S.S.F',200,NULL,0,0,NULL),('TMP0003',32,'N.S.S.F',200,NULL,0,0,NULL),('TMP0002',32,'N.S.S.F',200,NULL,0,0,NULL),('PR0002',1000000,'CHAMA',1500,0,0,0,NULL),('TMP0001',1000000,'CHAMA',1500,0,0,0,NULL),('PR0001',1000000,'CHAMA',1500,0,0,0,NULL),('TMP0004',1000000,'CHAMA',1500,0,0,0,NULL),('TMP0002',1000000,'CHAMA',1500,0,0,0,NULL),('PR0002',8,'N.H.I.F.',750,0,0,0,NULL),('TMP0001',8,'N.H.I.F.',600,0,0,0,NULL),('TMP0004',8,'N.H.I.F.',600,0,0,0,NULL),('TMP0003',8,'N.H.I.F.',600,0,0,0,NULL),('TMP0002',8,'N.H.I.F.',600,0,0,0,NULL),('TMP0002',53,'ADVANCE SAL',5000,0,0,1,1),('PR0002',53,'ADVANCE SAL',5000,0,0,0,NULL),('TMP0001',53,'ADVANCE SAL',5000,0,0,0,NULL),('TMP0004',53,'ADVANCE SAL',5000,0,0,0,NULL),('TMP0003',1000000,'CHAMA',1500,0,0,0,NULL),('TMP0003',53,'ADVANCE SAL',5000,0,0,0,NULL),('PR0002',1000002,'AHL',1500.1499999999999,3000.2999999999997,0,0,NULL),('TMP0001',1000002,'AHL',1500.1499999999999,3000.2999999999997,0,0,NULL),('PR0001',1000002,'AHL',1500.1499999999999,3000.2999999999997,0,0,NULL),('TMP0004',1000002,'AHL',1500.1499999999999,3000.2999999999997,0,0,NULL),('TMP0002',1000002,'AHL',1500.1499999999999,3000.2999999999997,0,0,NULL),('TMP0003',1000002,'AHL',1500.1499999999999,3000.2999999999997,0,0,NULL),('CON0001',1000002,'AHL',1500.1499999999999,3000.2999999999997,0,0,NULL),('PR0002',1000003,'AHL',331.515,331.515,0,0,NULL),('TMP0001',1000003,'AHL',273.015,273.015,0,0,NULL),('PR0001',1000003,'AHL',450.015,450.015,0,0,NULL),('TMP0004',1000003,'AHL',273.015,273.015,0,0,NULL),('TMP0002',1000003,'AHL',273.015,273.015,0,0,NULL),('TMP0003',1000003,'AHL',273.015,273.015,0,0,NULL),('CON0001',1000003,'AHL',525.015,525.015,0,0,NULL);
/*!40000 ALTER TABLE `prlmatrix` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlmrollperiods`
--

DROP TABLE IF EXISTS `prlmrollperiods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlmrollperiods` (
  `pkey` bigint NOT NULL,
  `type` int NOT NULL,
  `fromdate` datetime(6) NOT NULL,
  `todate` datetime(6) NOT NULL,
  `open` tinyint(1) DEFAULT '0',
  `Printed` tinyint(1) DEFAULT NULL,
  `printedby` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlmrollperiods`
--

LOCK TABLES `prlmrollperiods` WRITE;
/*!40000 ALTER TABLE `prlmrollperiods` DISABLE KEYS */;
INSERT INTO `prlmrollperiods` VALUES (1,1,'2022-11-01 00:00:00.000000','2022-11-30 00:00:00.000000',0,NULL,NULL),(4,1,'2022-12-01 00:00:00.000000','2022-12-31 00:00:00.000000',0,NULL,NULL),(5,1,'2023-01-01 00:00:00.000000','2023-01-31 00:00:00.000000',0,NULL,NULL),(7,1,'2023-02-01 00:00:00.000000','2023-02-28 00:00:00.000000',0,NULL,NULL),(8,1,'2023-03-01 00:00:00.000000','2023-03-31 00:00:00.000000',1,NULL,NULL);
/*!40000 ALTER TABLE `prlmrollperiods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlnavwebserviceupdate`
--

DROP TABLE IF EXISTS `prlnavwebserviceupdate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlnavwebserviceupdate` (
  `payrollid` bigint NOT NULL,
  `code` int NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `pagetype` varchar(50) DEFAULT NULL,
  `navcode` varchar(20) DEFAULT NULL,
  `pagetype_balancing` varchar(50) DEFAULT NULL,
  `navcode_balancing` varchar(20) DEFAULT NULL,
  `posted` datetime(6) DEFAULT NULL,
  `DimensionId` int DEFAULT NULL,
  `DimensionValue` varchar(20) DEFAULT NULL,
  KEY `FK_prlnavwebserviceupdate_prlmrollperiods` (`payrollid`),
  CONSTRAINT `FK_prlnavwebserviceupdate_prlmrollperiods` FOREIGN KEY (`payrollid`) REFERENCES `prlmrollperiods` (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlnavwebserviceupdate`
--

LOCK TABLES `prlnavwebserviceupdate` WRITE;
/*!40000 ALTER TABLE `prlnavwebserviceupdate` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlnavwebserviceupdate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlpaydetailstransfile`
--

DROP TABLE IF EXISTS `prlpaydetailstransfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlpaydetailstransfile` (
  `payroll_id` bigint NOT NULL,
  `pfno` char(20) NOT NULL,
  `code` int NOT NULL,
  `description` varchar(50) NOT NULL,
  `amount` double NOT NULL,
  `employercontribution` double DEFAULT NULL,
  `deduction` tinyint(1) NOT NULL,
  `reliefdeducted` double DEFAULT NULL,
  `non_cash_benefits` double DEFAULT NULL,
  KEY `FK_prlpaydetailstransfile_prlemployeemaster` (`pfno`),
  CONSTRAINT `FK_prlpaydetailstransfile_prlemployeemaster` FOREIGN KEY (`pfno`) REFERENCES `prlemployeemaster` (`pf_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlpaydetailstransfile`
--

LOCK TABLES `prlpaydetailstransfile` WRITE;
/*!40000 ALTER TABLE `prlpaydetailstransfile` DISABLE KEYS */;
INSERT INTO `prlpaydetailstransfile` VALUES (3,'PR0001',32,'N.S.S.F',200,NULL,0,0,2),(3,'PR0001',8,'N.H.I.F.',750,0,0,0,0),(1,'PR0001',32,'N.S.S.F',200,NULL,0,0,2),(1,'PR0001',8,'N.H.I.F.',850,0,0,0,0),(1,'PR0002',32,'N.S.S.F',200,NULL,0,0,2),(1,'TMP0001',32,'N.S.S.F',200,NULL,0,0,2),(1,'TMP0004',32,'N.S.S.F',200,NULL,0,0,2),(1,'TMP0003',32,'N.S.S.F',200,NULL,0,0,2),(1,'TMP0002',32,'N.S.S.F',200,NULL,0,0,2),(1,'PR0002',1000000,'CHAMA',3000,0,0,0,0),(1,'TMP0001',1000000,'CHAMA',3000,0,0,0,0),(1,'PR0001',1000000,'CHAMA',3000,0,0,0,0),(1,'TMP0004',1000000,'CHAMA',3000,0,0,0,0),(1,'TMP0002',1000000,'CHAMA',3000,0,0,0,0),(1,'PR0002',8,'N.H.I.F.',750,0,0,0,0),(1,'TMP0001',8,'N.H.I.F.',600,0,0,0,0),(1,'TMP0004',8,'N.H.I.F.',600,0,0,0,0),(1,'TMP0003',8,'N.H.I.F.',600,0,0,0,0),(4,'PR0001',32,'N.S.S.F',200,NULL,0,0,2),(4,'PR0001',8,'N.H.I.F.',850,0,0,0,0),(4,'PR0002',32,'N.S.S.F',200,NULL,0,0,2),(4,'TMP0001',32,'N.S.S.F',200,NULL,0,0,2),(4,'TMP0004',32,'N.S.S.F',200,NULL,0,0,2),(4,'TMP0003',32,'N.S.S.F',200,NULL,0,0,2),(4,'TMP0002',32,'N.S.S.F',200,NULL,0,0,2),(4,'PR0002',8,'N.H.I.F.',750,0,0,0,0),(4,'TMP0001',8,'N.H.I.F.',600,0,0,0,0),(4,'TMP0004',8,'N.H.I.F.',600,0,0,0,0),(4,'TMP0003',8,'N.H.I.F.',600,0,0,0,0),(4,'TMP0002',8,'N.H.I.F.',600,0,0,0,0),(4,'PR0002',53,'ADVANCE SAL',5000,0,0,0,0),(4,'TMP0001',53,'ADVANCE SAL',5000,0,0,0,0),(4,'TMP0004',53,'ADVANCE SAL',5000,0,0,0,0),(4,'TMP0003',53,'ADVANCE SAL',5000,0,0,0,0),(5,'PR0001',32,'N.S.S.F',200,NULL,0,0,2),(5,'PR0001',8,'N.H.I.F.',850,0,0,0,0),(5,'PR0002',32,'N.S.S.F',200,NULL,0,0,2),(5,'TMP0001',32,'N.S.S.F',200,NULL,0,0,2),(5,'TMP0004',32,'N.S.S.F',200,NULL,0,0,2),(5,'TMP0003',32,'N.S.S.F',200,NULL,0,0,2),(5,'TMP0002',32,'N.S.S.F',200,NULL,0,0,2),(5,'PR0002',1000000,'CHAMA',3000,0,0,0,0),(5,'TMP0001',1000000,'CHAMA',3000,0,0,0,0),(5,'PR0001',1000000,'CHAMA',3000,0,0,0,0),(5,'TMP0004',1000000,'CHAMA',3000,0,0,0,0),(5,'TMP0002',1000000,'CHAMA',3000,0,0,0,0),(5,'PR0002',8,'N.H.I.F.',750,0,0,0,0),(5,'TMP0001',8,'N.H.I.F.',600,0,0,0,0),(5,'TMP0004',8,'N.H.I.F.',600,0,0,0,0),(5,'TMP0003',8,'N.H.I.F.',600,0,0,0,0),(5,'TMP0002',8,'N.H.I.F.',600,0,0,0,0),(6,'PR0001',32,'N.S.S.F',200,NULL,0,0,2),(6,'PR0001',8,'N.H.I.F.',850,0,0,0,0),(6,'PR0002',32,'N.S.S.F',200,NULL,0,0,2),(6,'TMP0001',32,'N.S.S.F',200,NULL,0,0,2),(6,'TMP0004',32,'N.S.S.F',200,NULL,0,0,2),(6,'TMP0003',32,'N.S.S.F',200,NULL,0,0,2),(6,'TMP0002',32,'N.S.S.F',200,NULL,0,0,2),(6,'PR0002',1000000,'CHAMA',3000,0,0,0,0),(6,'TMP0001',1000000,'CHAMA',3000,0,0,0,0),(6,'PR0001',1000000,'CHAMA',3000,0,0,0,0),(6,'TMP0004',1000000,'CHAMA',3000,0,0,0,0),(6,'TMP0002',1000000,'CHAMA',3000,0,0,0,0),(6,'PR0002',8,'N.H.I.F.',750,0,0,0,0),(6,'TMP0001',8,'N.H.I.F.',600,0,0,0,0),(1,'TMP0002',8,'N.H.I.F.',600,0,0,0,0),(1,'TMP0003',1000000,'CHAMA',3000,0,0,0,0),(6,'TMP0004',8,'N.H.I.F.',600,0,0,0,0),(6,'TMP0003',8,'N.H.I.F.',600,0,0,0,0),(6,'TMP0002',8,'N.H.I.F.',600,0,0,0,0),(6,'TMP0003',1000000,'CHAMA',3000,0,0,0,0),(6,'PR0002',53,'ADVANCE SAL',5000,0,0,0,0),(6,'TMP0001',53,'ADVANCE SAL',5000,0,0,0,0),(6,'TMP0004',53,'ADVANCE SAL',5000,0,0,0,0),(6,'TMP0003',53,'ADVANCE SAL',5000,0,0,0,0),(5,'TMP0003',1000000,'CHAMA',3000,0,0,0,0),(5,'PR0002',53,'ADVANCE SAL',5000,0,0,0,0),(5,'TMP0001',53,'ADVANCE SAL',5000,0,0,0,0),(5,'TMP0004',53,'ADVANCE SAL',5000,0,0,0,0),(5,'TMP0003',53,'ADVANCE SAL',5000,0,0,0,0),(7,'PR0001',32,'N.S.S.F',200,NULL,0,0,2),(7,'PR0001',8,'N.H.I.F.',900,0,0,0,0),(7,'PR0002',32,'N.S.S.F',200,NULL,0,0,2),(1,'PR0002',53,'ADVANCE SAL',5000,0,0,0,0),(1,'TMP0001',53,'ADVANCE SAL',5000,0,0,0,0),(1,'TMP0004',53,'ADVANCE SAL',5000,0,0,0,0),(1,'TMP0003',53,'ADVANCE SAL',5000,0,0,0,0),(7,'TMP0001',32,'N.S.S.F',200,NULL,0,0,2),(7,'TMP0004',32,'N.S.S.F',200,NULL,0,0,2),(7,'TMP0003',32,'N.S.S.F',200,NULL,0,0,2),(7,'TMP0002',32,'N.S.S.F',200,NULL,0,0,2),(7,'PR0002',1000000,'CHAMA',1500,0,0,0,0),(7,'TMP0001',1000000,'CHAMA',1500,0,0,0,0),(7,'PR0001',1000000,'CHAMA',1500,0,0,0,0),(7,'TMP0004',1000000,'CHAMA',1500,0,0,0,0),(7,'TMP0002',1000000,'CHAMA',1500,0,0,0,0),(7,'PR0002',8,'N.H.I.F.',750,0,0,0,0),(7,'TMP0001',8,'N.H.I.F.',600,0,0,0,0),(7,'TMP0004',8,'N.H.I.F.',600,0,0,0,0),(7,'TMP0003',8,'N.H.I.F.',600,0,0,0,0),(7,'TMP0002',8,'N.H.I.F.',600,0,0,0,0),(7,'TMP0002',53,'ADVANCE SAL',5000,0,0,0,0),(7,'PR0002',53,'ADVANCE SAL',5000,0,0,0,0),(7,'TMP0001',53,'ADVANCE SAL',5000,0,0,0,0),(7,'TMP0004',53,'ADVANCE SAL',5000,0,0,0,0),(7,'TMP0003',1000000,'CHAMA',1500,0,0,0,0),(7,'TMP0003',53,'ADVANCE SAL',5000,0,0,0,0),(8,'PR0001',32,'N.S.S.F',200,NULL,0,0,2),(8,'PR0001',8,'N.H.I.F.',900,0,0,0,0),(8,'PR0002',32,'N.S.S.F',200,NULL,0,0,2),(8,'TMP0001',32,'N.S.S.F',200,NULL,0,0,2),(8,'TMP0004',32,'N.S.S.F',200,NULL,0,0,2),(8,'TMP0003',32,'N.S.S.F',200,NULL,0,0,2),(8,'TMP0002',32,'N.S.S.F',200,NULL,0,0,2),(8,'PR0002',1000000,'CHAMA',1500,0,0,0,0),(8,'TMP0001',1000000,'CHAMA',1500,0,0,0,0),(8,'PR0001',1000000,'CHAMA',1500,0,0,0,0),(8,'TMP0004',1000000,'CHAMA',1500,0,0,0,0),(8,'TMP0002',1000000,'CHAMA',1500,0,0,0,0),(8,'PR0002',8,'N.H.I.F.',750,0,0,0,0),(8,'TMP0001',8,'N.H.I.F.',600,0,0,0,0),(8,'TMP0004',8,'N.H.I.F.',600,0,0,0,0),(8,'TMP0003',8,'N.H.I.F.',600,0,0,0,0),(8,'TMP0002',8,'N.H.I.F.',600,0,0,0,0),(8,'PR0002',53,'ADVANCE SAL',5000,0,0,0,0),(8,'TMP0001',53,'ADVANCE SAL',5000,0,0,0,0),(8,'TMP0004',53,'ADVANCE SAL',5000,0,0,0,0),(8,'TMP0003',1000000,'CHAMA',1500,0,0,0,0),(8,'TMP0003',53,'ADVANCE SAL',5000,0,0,0,0),(8,'PR0002',1000003,'AHL',331.515,331.515,0,0,2),(8,'TMP0001',1000003,'AHL',273.015,273.015,0,0,2),(8,'PR0001',1000003,'AHL',450.015,450.015,0,0,2),(8,'TMP0004',1000003,'AHL',273.015,273.015,0,0,2),(8,'TMP0002',1000003,'AHL',273.015,273.015,0,0,2),(8,'TMP0003',1000003,'AHL',273.015,273.015,0,0,2),(8,'CON0001',1000003,'AHL',525.015,525.015,0,0,2);
/*!40000 ALTER TABLE `prlpaydetailstransfile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlpayroltransfile`
--

DROP TABLE IF EXISTS `prlpayroltransfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlpayroltransfile` (
  `pfno` char(20) NOT NULL,
  `payroll_id` bigint NOT NULL,
  `basicpay` double NOT NULL,
  `allowances` double DEFAULT NULL,
  `deductions` double DEFAULT NULL,
  `non_cash_benefits` double DEFAULT NULL,
  `overtime` double DEFAULT NULL,
  `lateness_absent` double DEFAULT NULL,
  `pension` double DEFAULT NULL,
  `personalrelief` double DEFAULT NULL,
  `insurancerelief` double DEFAULT NULL,
  `rowid` bigint NOT NULL,
  KEY `FK_prlpayroltransfile_prlemployeemaster` (`pfno`),
  CONSTRAINT `FK_prlpayroltransfile_prlemployeemaster` FOREIGN KEY (`pfno`) REFERENCES `prlemployeemaster` (`pf_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlpayroltransfile`
--

LOCK TABLES `prlpayroltransfile` WRITE;
/*!40000 ALTER TABLE `prlpayroltransfile` DISABLE KEYS */;
INSERT INTO `prlpayroltransfile` VALUES ('PR0001',2,0,0,NULL,0,0,0,0,1280,0,9),('PR0002',2,0,0,NULL,0,0,0,0,1280,0,10),('TMP0001',2,0,0,NULL,0,0,0,0,1280,0,11),('TMP0002',2,0,0,NULL,0,0,0,0,1280,0,12),('TMP0003',2,0,0,NULL,0,0,0,0,1280,0,13),('TMP0004',2,0,0,NULL,0,0,0,0,1280,0,14),('PR0001',3,20000,0,NULL,0,0,0,200,1280,0,33),('PR0002',3,0,0,NULL,0,0,0,0,1280,0,34),('TMP0001',3,0,0,NULL,0,0,0,0,1280,0,35),('TMP0002',3,0,0,NULL,0,0,0,0,1280,0,36),('TMP0003',3,0,0,NULL,0,0,0,0,1280,0,37),('TMP0004',3,0,0,NULL,0,0,0,0,1280,0,38),('PR0001',1,25000,0,NULL,0,0,0,200,1280,0,855),('PR0002',1,22100,0,NULL,0,0,0,200,1280,0,856),('TMP0001',1,18200,0,NULL,0,0,0,200,1280,0,857),('TMP0002',1,18200,0,NULL,0,0,0,200,1280,0,858),('TMP0003',1,18200,0,NULL,0,0,0,200,1280,0,859),('TMP0004',1,18200,0,NULL,0,0,0,200,1280,0,860),('PR0001',4,25000,0,NULL,0,0,0,200,1280,0,867),('PR0002',4,22100,0,NULL,0,0,0,200,1280,0,868),('TMP0001',4,18200,0,NULL,0,0,0,200,1280,0,869),('TMP0002',4,18200,0,NULL,0,0,0,200,1280,0,870),('TMP0003',4,18200,0,NULL,0,0,0,200,1280,0,871),('TMP0004',4,18200,0,NULL,0,0,0,200,1280,0,872),('PR0001',5,25000,0,NULL,0,0,0,200,1280,0,945),('PR0002',5,22100,0,NULL,0,0,0,200,1280,0,946),('TMP0001',5,18200,0,NULL,0,0,0,200,1280,0,947),('TMP0002',5,18200,0,NULL,0,0,0,200,1280,0,948),('TMP0003',5,18200,0,NULL,0,0,0,200,1280,0,949),('TMP0004',5,18200,0,NULL,0,0,0,200,1280,0,950),('PR0001',6,25000,0,NULL,0,0,0,200,1280,0,813),('PR0002',6,22100,0,NULL,0,0,0,200,1280,0,814),('TMP0001',6,18200,0,NULL,0,0,0,200,1280,0,815),('TMP0002',6,18200,0,NULL,0,0,0,200,1280,0,816),('TMP0003',6,18200,0,NULL,0,0,0,200,1280,0,817),('TMP0004',6,18200,0,NULL,0,0,0,200,1280,0,818),('PR0001',7,30000,0,NULL,0,0,0,200,1280,0,1053),('PR0002',7,22100,0,NULL,0,0,0,200,1280,0,1054),('TMP0001',7,18200,0,NULL,0,0,0,200,1280,0,1055),('TMP0002',7,18200,0,NULL,0,0,0,200,1280,0,1056),('TMP0003',7,18200,0,NULL,0,0,0,200,1280,0,1057),('TMP0004',7,18200,0,NULL,0,0,0,200,1280,0,1058),('CON0001',8,35000,0,NULL,0,0,0,525.015,1280,0,1157),('PR0001',8,30000,0,NULL,0,0,0,650.015,1280,0,1158),('PR0002',8,22100,0,NULL,0,0,0,531.515,1280,0,1159),('TMP0001',8,18200,0,NULL,0,0,0,473.015,1280,0,1160),('TMP0002',8,18200,0,NULL,0,0,0,473.015,1280,0,1161),('TMP0003',8,18200,0,NULL,0,0,0,473.015,1280,0,1162),('TMP0004',8,18200,0,NULL,0,0,0,473.015,1280,0,1163);
/*!40000 ALTER TABLE `prlpayroltransfile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlpositions`
--

DROP TABLE IF EXISTS `prlpositions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlpositions` (
  `code` int NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `jobgroup` char(50) DEFAULT NULL,
  `department` int DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `FK_prlpositions_prljobgroup` (`jobgroup`),
  CONSTRAINT `FK_prlpositions_prljobgroup` FOREIGN KEY (`jobgroup`) REFERENCES `prljobgroup` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlpositions`
--

LOCK TABLES `prlpositions` WRITE;
/*!40000 ALTER TABLE `prlpositions` DISABLE KEYS */;
INSERT INTO `prlpositions` VALUES (1,'Accounts',NULL,NULL),(2,'SALES',NULL,NULL);
/*!40000 ALTER TABLE `prlpositions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlproducts`
--

DROP TABLE IF EXISTS `prlproducts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlproducts` (
  `code` int NOT NULL,
  `description` varchar(50) NOT NULL,
  `deduction` tinyint(1) NOT NULL,
  `hastable` int NOT NULL,
  `employerfactor` int DEFAULT NULL,
  `pens_tax` int DEFAULT NULL,
  `membership_no` varchar(150) DEFAULT NULL,
  `codenav` varchar(20) DEFAULT NULL,
  `codenavd` varchar(20) DEFAULT NULL,
  `Pagefilter` varchar(50) DEFAULT NULL,
  `glaccountlink` varchar(20) DEFAULT NULL,
  `bal_Pagefilter` varchar(50) DEFAULT NULL,
  `bal_Glaccount` varchar(20) DEFAULT NULL,
  `DimensionId` int DEFAULT NULL,
  `DimensionValue` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlproducts`
--

LOCK TABLES `prlproducts` WRITE;
/*!40000 ALTER TABLE `prlproducts` DISABLE KEYS */;
INSERT INTO `prlproducts` VALUES (1,'HOUSE ALLOWANCE',1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'MEDICAL ALLOWANCE',1,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,'COMMUTER ALLOWANCE',1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,'Gift Token',1,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'Overtime',1,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,'P.A.Y.E.',0,2,0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,'N.H.I.F.',0,1,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(32,'N.S.S.F',0,0,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(53,'ADVANCE SAL',0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(54,'ICEA PENSION',0,0,0,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(1000000,'CHAMA',0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(1000003,'AHL',0,2,1,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `prlproducts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlqualifications`
--

DROP TABLE IF EXISTS `prlqualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlqualifications` (
  `name` char(50) NOT NULL,
  `code` int NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlqualifications`
--

LOCK TABLES `prlqualifications` WRITE;
/*!40000 ALTER TABLE `prlqualifications` DISABLE KEYS */;
INSERT INTO `prlqualifications` VALUES ('K',1);
/*!40000 ALTER TABLE `prlqualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlreliefs`
--

DROP TABLE IF EXISTS `prlreliefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlreliefs` (
  `code` int NOT NULL,
  `productcode` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `reliefamount` decimal(18,2) DEFAULT NULL,
  `reliefaspercent` int DEFAULT NULL,
  `maxrelief` decimal(18,2) DEFAULT NULL,
  `type` tinyint(1) DEFAULT NULL,
  KEY `FK_prlreliefs_prlproducts` (`productcode`),
  CONSTRAINT `FK_prlreliefs_prlproducts` FOREIGN KEY (`productcode`) REFERENCES `prlproducts` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlreliefs`
--

LOCK TABLES `prlreliefs` WRITE;
/*!40000 ALTER TABLE `prlreliefs` DISABLE KEYS */;
INSERT INTO `prlreliefs` VALUES (11,7,'Personal Relief',1280.00,0,0.00,0);
/*!40000 ALTER TABLE `prlreliefs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlsalaryscale`
--

DROP TABLE IF EXISTS `prlsalaryscale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlsalaryscale` (
  `jbgroup` char(50) NOT NULL,
  `code` char(50) NOT NULL,
  `qualifications` char(50) DEFAULT NULL,
  `min` decimal(18,2) DEFAULT NULL,
  `annual_inc` decimal(10,2) DEFAULT NULL,
  `max` decimal(18,2) DEFAULT NULL,
  `lastupdate_year` int DEFAULT NULL,
  `lastupdate` datetime DEFAULT NULL,
  KEY `FK_prlsalaryscale_prljobgroup` (`jbgroup`),
  KEY `FK_prlsalaryscale_prlqualifications` (`qualifications`),
  CONSTRAINT `FK_prlsalaryscale_prljobgroup` FOREIGN KEY (`jbgroup`) REFERENCES `prljobgroup` (`name`),
  CONSTRAINT `FK_prlsalaryscale_prlqualifications` FOREIGN KEY (`qualifications`) REFERENCES `prlqualifications` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlsalaryscale`
--

LOCK TABLES `prlsalaryscale` WRITE;
/*!40000 ALTER TABLE `prlsalaryscale` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlsalaryscale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlspecialdays`
--

DROP TABLE IF EXISTS `prlspecialdays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlspecialdays` (
  `name` char(50) NOT NULL,
  `day` int DEFAULT NULL,
  `month` int DEFAULT NULL,
  `week` int DEFAULT NULL,
  `rowid` int NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlspecialdays`
--

LOCK TABLES `prlspecialdays` WRITE;
/*!40000 ALTER TABLE `prlspecialdays` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlspecialdays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlstaffleavemaster`
--

DROP TABLE IF EXISTS `prlstaffleavemaster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlstaffleavemaster` (
  `refno` int NOT NULL,
  `pfno` char(20) NOT NULL,
  `year` int NOT NULL,
  `days` int NOT NULL,
  PRIMARY KEY (`refno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlstaffleavemaster`
--

LOCK TABLES `prlstaffleavemaster` WRITE;
/*!40000 ALTER TABLE `prlstaffleavemaster` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlstaffleavemaster` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlstaffleaveplanner`
--

DROP TABLE IF EXISTS `prlstaffleaveplanner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlstaffleaveplanner` (
  `refno` char(10) NOT NULL,
  `pfno` char(20) NOT NULL,
  `handover` char(20) NOT NULL,
  `year` int NOT NULL,
  `leavedue` datetime(6) NOT NULL,
  `leavend` datetime(6) NOT NULL,
  `days` int DEFAULT NULL,
  `typeofleave` int NOT NULL,
  `status` int NOT NULL,
  `approvelevel` int DEFAULT NULL,
  PRIMARY KEY (`refno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlstaffleaveplanner`
--

LOCK TABLES `prlstaffleaveplanner` WRITE;
/*!40000 ALTER TABLE `prlstaffleaveplanner` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlstaffleaveplanner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prlstaffloans`
--

DROP TABLE IF EXISTS `prlstaffloans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prlstaffloans` (
  `loanindex` bigint NOT NULL,
  `pfno` char(20) NOT NULL,
  `deductcode` int NOT NULL,
  `principal` decimal(18,2) NOT NULL,
  `instalment` decimal(18,2) NOT NULL,
  `interest` decimal(18,2) DEFAULT NULL,
  `startdate` datetime(6) NOT NULL,
  `closed` tinyint(1) DEFAULT NULL,
  `saving` tinyint(1) DEFAULT NULL,
  `interesttype` int DEFAULT NULL,
  `openbalance` double DEFAULT NULL,
  PRIMARY KEY (`loanindex`),
  KEY `FK_prlstaffloans_prlproducts` (`deductcode`),
  CONSTRAINT `FK_prlstaffloans_prlproducts` FOREIGN KEY (`deductcode`) REFERENCES `prlproducts` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prlstaffloans`
--

LOCK TABLES `prlstaffloans` WRITE;
/*!40000 ALTER TABLE `prlstaffloans` DISABLE KEYS */;
/*!40000 ALTER TABLE `prlstaffloans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prltimesheet`
--

DROP TABLE IF EXISTS `prltimesheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prltimesheet` (
  `pfno` char(20) NOT NULL,
  `timein` datetime DEFAULT NULL,
  `timeout` datetime DEFAULT NULL,
  `ShouldLogIn` tinyint(1) NOT NULL,
  `date` datetime(6) NOT NULL,
  `period` int NOT NULL,
  `week` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `noofmin` int DEFAULT NULL,
  `noofdays` int DEFAULT NULL,
  `noofhours` int DEFAULT NULL,
  `recommended` int DEFAULT NULL,
  `id` int NOT NULL,
  `DidtheylogIn` int DEFAULT NULL,
  UNIQUE KEY `IX_prltimesheet` (`pfno`,`date`),
  CONSTRAINT `FK_prltimesheet_prlemployeemaster` FOREIGN KEY (`pfno`) REFERENCES `prlemployeemaster` (`pf_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prltimesheet`
--

LOCK TABLES `prltimesheet` WRITE;
/*!40000 ALTER TABLE `prltimesheet` DISABLE KEYS */;
/*!40000 ALTER TABLE `prltimesheet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prltypes`
--

DROP TABLE IF EXISTS `prltypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prltypes` (
  `code` int NOT NULL,
  `type` varchar(30) NOT NULL,
  `setup` tinyint(1) NOT NULL,
  `system` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prltypes`
--

LOCK TABLES `prltypes` WRITE;
/*!40000 ALTER TABLE `prltypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `prltypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scripts`
--

DROP TABLE IF EXISTS `scripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scripts` (
  `script` varchar(78) NOT NULL,
  `pagesecurity` int NOT NULL DEFAULT '1',
  `description` longtext NOT NULL,
  PRIMARY KEY (`script`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scripts`
--

LOCK TABLES `scripts` WRITE;
/*!40000 ALTER TABLE `scripts` DISABLE KEYS */;
INSERT INTO `scripts` VALUES ('AnnualleaveRollover.php',15,'Payroll leave settings'),('AuditTrail.php',15,'Shows the activity with SQL statements and who performed the changes'),('Companyestablishment.php',3,'Hr Create offices and branches'),('createpayrollperiod.php',1,'Close Payroll settings'),('daysoftheweek.php',0,'Register working days of the week'),('employeelink.php',9,'modifies employee and filters a particular employee'),('Employeenetpay.php',15,'Maintains table bankaccountusers (Authorized users to work with a bank account)'),('establishmentlink.php',3,'establishment shortcut'),('index.php',1,'The main menu from where all functions available to the user are accessed by clicking on the links'),('Logout.php',1,'Shows when the user logs out'),('PDFauditallowances.php',15,'Audit Changes in Allowances'),('PDFauditbasicpay.php',11,'Audit trail for basic pay'),('PDFauditloans.php',15,'Audit Loan changes'),('PDFauditnewemployees.php',15,'Audit new employees added in the payroll'),('PDFBankingSummary.php',3,'Creates a pdf showing the amounts entered as receipts on a specified date together with references for the purposes of banking'),('PDFbirthdayreminder.php',3,'Hr report'),('PDFChequeListing.php',3,'Creates a pdf showing all payments that have been made from a specified bank account over a specified period. This can be emailed to an email account defined in config.php - ie a financial controller'),('PDFconsumptionanalysis.php',5,'Reports on billing'),('PDFcustomreports.php',15,'Hr report'),('PDFDeliveryDifferences.php',3,'Creates a pdf report listing the delivery differences from what the customer requested as recorded in the order entry. The report calculates a percentage of order fill based on the number of orders filled in full on time'),('PDFDIFOT.php',3,'Produces a pdf showing the delivery in full on time performance'),('PDFGLJournal.php',15,'General Ledger Journal Print'),('PDFGrn.php',2,'Produces a GRN report on the receipt of stock'),('PDFleaveschedule.php',2,'Hr Reports'),('PeriodsInquiry.php',2,'Shows a list of all the system defined periods'),('prl_pdf_byproducts.php',5,'Payroll by products'),('prl_pdf_loans.php',15,'Loan report'),('prl_pdf_sacco.php',15,'Sacco reports'),('prlapproveleave.php',5,'Approve leave'),('PrlAuthorizers.php',15,'Payroll Authorizers'),('prlbankbranches.php',15,'Meant to be run as a scheduled process to email the stock valuation off to a specified person. Creates the same stock valuation report as InventoryValuation.php'),('prlbanks.php',15,'Mailing the sales report'),('prlcreditloans.php',5,'Payroll staff Loans and credit'),('prldepartments.php',2,'Creates a pdf for the customer statements in the selected range'),('prlgenerateslips.php',5,'Payroll Reports'),('prlhr.php',5,'Human Resource'),('prlhrlistview.php',0,'View Employees'),('prljobgroup.php',2,'Human Resource job groups'),('prlmasterroll.php',5,'master roll reports'),('prlmatrixscript.php',5,'Payroll matrix'),('prlmiselleneous.php',15,'Payroll miscelleneous'),('prlnhif.php',5,'Human Resource nhif deductions'),('prlnssf.php',5,'Human Resource nssf deductions'),('prlpaye.php',5,'Human Resource paye deductions'),('prlpayePten.php',0,'PAYE P10'),('prlpayrollines.php',5,'Payroll lineitems'),('prlpdfPayslip.php',5,'PaySlips'),('prlpositions.php',1,'Human Resource positions'),('prlproperties.php',5,'payroll Properties'),('prlqualification.php',1,'payroll qualifications'),('prlsaccosavings.php',15,'Defines the sales areas - asetup areas'),('prlsalary.php',5,'Create salaries'),('prlsalaryinrement.php',3,'Salary inrement'),('prlsalaryscale.php',8,'Human Resource salary scale'),('prlspecialdays.php',0,'Register off days'),('prlstaffleave.php',5,'leave module'),('prltimesheet.php',5,'Payroll time sheet'),('ReportCreator.php',13,'Report Writer and Form Creator script that creates templates for user defined reports and forms'),('ReportMaker.php',1,'Produces reports from the report writer templates created'),('searchaccount.php',1,'Ajax utility'),('SecurityTokens.php',15,'Administration of security tokens'),('SelectEmployee.php',1,'Select Employee'),('SelectEstablishment.php',5,'Establisment'),('WWW_Users.php',15,'Entry of users and security settings of users'),('z_dynamicsimport.php',15,'Import Setup data'),('z_payrollbyproducts.php',15,'configure Nav payables to payroll lines'),('z_Postpayrollbyproducts.php',15,'Produces item pricing labels in a pdf from a range of selected criteria');
/*!40000 ALTER TABLE `scripts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `securitygroups`
--

DROP TABLE IF EXISTS `securitygroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `securitygroups` (
  `secroleid` int NOT NULL DEFAULT '0',
  `tokenid` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`secroleid`,`tokenid`),
  KEY `securitygroups$securitygroups$securitygroups_tokenid_fk` (`tokenid`),
  CONSTRAINT `securitygroups$securitygroups$securitygroups_secroleid_fk` FOREIGN KEY (`secroleid`) REFERENCES `securityroles` (`secroleid`),
  CONSTRAINT `securitygroups$securitygroups$securitygroups_tokenid_fk` FOREIGN KEY (`tokenid`) REFERENCES `securitytokens` (`tokenid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `securitygroups`
--

LOCK TABLES `securitygroups` WRITE;
/*!40000 ALTER TABLE `securitygroups` DISABLE KEYS */;
INSERT INTO `securitygroups` VALUES (1,0),(8,0),(9,0),(1,1),(8,1),(1,2),(8,2),(8,3),(8,4),(1,5),(8,5),(8,6),(8,7),(8,8),(8,9),(9,9),(8,10),(8,11),(8,12),(8,13),(8,15);
/*!40000 ALTER TABLE `securitygroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `securityroles`
--

DROP TABLE IF EXISTS `securityroles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `securityroles` (
  `secroleid` int NOT NULL,
  `secrolename` longtext NOT NULL,
  PRIMARY KEY (`secroleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `securityroles`
--

LOCK TABLES `securityroles` WRITE;
/*!40000 ALTER TABLE `securityroles` DISABLE KEYS */;
INSERT INTO `securityroles` VALUES (1,'Inquiries/Order Entry'),(8,'System Administrator'),(9,'Employee Log On Only');
/*!40000 ALTER TABLE `securityroles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `securitytokens`
--

DROP TABLE IF EXISTS `securitytokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `securitytokens` (
  `tokenid` int NOT NULL DEFAULT '0',
  `tokenname` longtext NOT NULL,
  PRIMARY KEY (`tokenid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `securitytokens`
--

LOCK TABLES `securitytokens` WRITE;
/*!40000 ALTER TABLE `securitytokens` DISABLE KEYS */;
INSERT INTO `securitytokens` VALUES (0,'Main Index Page'),(1,'Order Entry/Inquiries customer access only'),(2,'Basic Reports and Inquiries with selection options'),(3,'Credit notes and AR management'),(4,'Purchasing data/PO Entry/Reorder Levels'),(5,'Accounts Payable'),(6,'Petty Cash'),(7,'Bank Reconciliations'),(8,'General ledger reports/inquiries'),(9,'Supplier centre - Supplier access only'),(11,'Inventory Management and Pricing'),(12,'Prices Security'),(13,'Customer services Price modifications'),(15,'User Management and System Administration');
/*!40000 ALTER TABLE `securitytokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `systypes_1`
--

DROP TABLE IF EXISTS `systypes_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `systypes_1` (
  `typeid` smallint NOT NULL DEFAULT '0',
  `typename` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `typeno` int NOT NULL DEFAULT '1',
  `prefix` char(10) DEFAULT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `systypes_1`
--

LOCK TABLES `systypes_1` WRITE;
/*!40000 ALTER TABLE `systypes_1` DISABLE KEYS */;
INSERT INTO `systypes_1` VALUES (0,'Journal - GL',0,NULL);
/*!40000 ALTER TABLE `systypes_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `www_users`
--

DROP TABLE IF EXISTS `www_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `www_users` (
  `userid` varchar(20) NOT NULL,
  `password` longtext,
  `realname` varchar(35) DEFAULT NULL,
  `payrollid` char(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(55) DEFAULT NULL,
  `fullaccess` int DEFAULT '1',
  `lastvisitdate` datetime(6) DEFAULT NULL,
  `branchcode` varchar(10) DEFAULT NULL,
  `pagesize` varchar(20) DEFAULT NULL,
  `modulesallowed` varchar(40) DEFAULT NULL,
  `blocked` smallint DEFAULT '0',
  `displayrecordsmax` int DEFAULT '0',
  `theme` varchar(30) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `pdflanguage` smallint DEFAULT '0',
  `departcode` int DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `www_users`
--

LOCK TABLES `www_users` WRITE;
/*!40000 ALTER TABLE `www_users` DISABLE KEYS */;
INSERT INTO `www_users` VALUES ('admin','9999','Programer user','','','jonathankiranga@gmail.com',8,'2024-01-01 00:00:00.000000','1','A4','1,1,1,',0,50,'xenos','en_GB.utf8',3,5),('MUGAMBI25','883ff74df756fe4fed7f095cd7c8b13c9205fa0d','SALOME MUGAMBI','','0748011756','salomemugambi17@gmail.com',8,'2024-01-01 00:00:00.000000','','A4','1,1,1,',0,0,'xenos','en_GB.utf8',0,0);
/*!40000 ALTER TABLE `www_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-02-19 16:09:56
