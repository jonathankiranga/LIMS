-- MySQL dump 10.13  Distrib 8.0.26, for Win64 (x86_64)
--
-- Host: localhost    Database: lims_encrpted
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
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `action_type` varchar(50) DEFAULT NULL,
  `document_no` varchar(255) DEFAULT NULL,
  `hash_value` char(64) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `baseparameters`
--

DROP TABLE IF EXISTS `baseparameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `baseparameters` (
  `ParameterID` int NOT NULL AUTO_INCREMENT,
  `ParameterName` varchar(255) NOT NULL,
  `NeutralityID` int DEFAULT NULL,
  `TdsID` int DEFAULT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ParameterID`),
  UNIQUE KEY `ParameterName_UNIQUE` (`ParameterName`)
) ENGINE=InnoDB AUTO_INCREMENT=982 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blockchain_ledger`
--

DROP TABLE IF EXISTS `blockchain_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blockchain_ledger` (
  `block_id` int NOT NULL AUTO_INCREMENT,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `previous_hash` char(64) NOT NULL,
  `current_hash` char(64) NOT NULL,
  `digital_signature` varchar(512) NOT NULL,
  `encrypted_data` text,
  `previous_version_id` int DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  `status` enum('active','superseded') DEFAULT 'active',
  `userid` int NOT NULL,
  PRIMARY KEY (`block_id`)
) ENGINE=InnoDB AUTO_INCREMENT=260 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chain_of_custody`
--

DROP TABLE IF EXISTS `chain_of_custody`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chain_of_custody` (
  `custody_id` int NOT NULL AUTO_INCREMENT,
  `SampleID` varchar(20) NOT NULL,
  `handler_name` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  PRIMARY KEY (`custody_id`),
  KEY `SampleID` (`SampleID`),
  CONSTRAINT `chain_of_custody_ibfk_1` FOREIGN KEY (`SampleID`) REFERENCES `sample_tests` (`SampleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_master`
--

DROP TABLE IF EXISTS `company_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_master` (
  `company_id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `address1` text,
  `address2` text,
  `address3` text,
  `email` varchar(100) DEFAULT NULL,
  `technician` int DEFAULT NULL,
  `technician2` int DEFAULT NULL,
  `authorisation` int DEFAULT NULL,
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `confname` varchar(35) NOT NULL,
  `confvalue` longtext NOT NULL,
  `type` enum('number','string','path','text','date') DEFAULT 'string',
  PRIMARY KEY (`confname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `config` (`confname`, `confvalue`, `type`) VALUES
('quote_default_terms', '1. Sample retention is 30 days post-reporting unless otherwise negotiated.
2. Analysis will be performed in accordance with ISO 17025 standards where applicable.
3. Quote is valid for the matrices and quantities listed above only.', 'text');

--
-- Table structure for table `controlsampleresults`
--

DROP TABLE IF EXISTS `controlsampleresults`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `controlsampleresults` (
  `result_id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int DEFAULT NULL,
  `sample_name` varchar(255) DEFAULT NULL,
  `known_value` decimal(10,2) DEFAULT NULL,
  `measured_value` decimal(10,2) DEFAULT NULL,
  `deviation` decimal(10,2) DEFAULT NULL,
  `result_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`result_id`),
  KEY `equipment_id` (`equipment_id`),
  CONSTRAINT `controlsampleresults_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `machines` (`machine_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtors`
--

DROP TABLE IF EXISTS `debtors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtors` (
  `type` char(1) DEFAULT NULL,
  `istaff` int DEFAULT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `pinno` char(20) DEFAULT NULL,
  `itemcode` varchar(20) DEFAULT NULL,
  `class` char(10) DEFAULT NULL,
  `cardadd` char(10) DEFAULT NULL,
  `contact` char(100) DEFAULT NULL,
  `defaultgl` char(10) DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `flag` char(6) DEFAULT NULL,
  `creditlimit` decimal(14,2) DEFAULT NULL,
  `customer` varchar(50) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(10) DEFAULT NULL,
  `middlen` char(15) DEFAULT NULL,
  `lastn` char(10) DEFAULT NULL,
  `phone` char(15) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `company` char(100) DEFAULT NULL,
  `altcontact` char(100) DEFAULT NULL,
  `email` char(100) DEFAULT NULL,
  `city` char(100) DEFAULT NULL,
  `country` char(100) DEFAULT NULL,
  `preffpay` char(10) DEFAULT NULL,
  `crdcardno` char(10) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `namecrd` char(10) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `id` char(1) DEFAULT NULL,
  `i_n_t` char(1) DEFAULT NULL,
  `typ` char(2) DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` bigint DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` bigint NOT NULL,
  `islocal` tinyint(1) DEFAULT NULL,
  `username` char(20) DEFAULT NULL,
  `customerposting` varchar(20) DEFAULT NULL,
  `salesman` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `environmental_parameters`
--

DROP TABLE IF EXISTS `environmental_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `environmental_parameters` (
  `param_id` int NOT NULL AUTO_INCREMENT,
  `temperature` float NOT NULL,
  `humidity` float NOT NULL,
  `notes` text,
  `recorded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`param_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `machine_id` int DEFAULT NULL,
  `last_calibration` date DEFAULT NULL,
  `predicted_calibration` date DEFAULT NULL,
  `deviation_trend` varchar(50) DEFAULT NULL,
  `usage_hours` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`machine_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `equipmentusage`
--

DROP TABLE IF EXISTS `equipmentusage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipmentusage` (
  `usage_id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int DEFAULT NULL,
  `usage_start_time` timestamp NULL DEFAULT NULL,
  `usage_end_time` timestamp NULL DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `control_sample_test_results` json DEFAULT NULL,
  PRIMARY KEY (`usage_id`),
  KEY `equipment_id` (`equipment_id`),
  CONSTRAINT `equipmentusage_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `machines` (`machine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `event_logs`
--

DROP TABLE IF EXISTS `event_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int DEFAULT NULL,
  `status` enum('success','failure') DEFAULT 'success',
  `error_message` text,
  `triggered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `event_logs_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `action` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `folder_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `folder_id` (`folder_id`),
  CONSTRAINT `files_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `folders`
--

DROP TABLE IF EXISTS `folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `parent_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `folders_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `machines`
--

DROP TABLE IF EXISTS `machines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `machines` (
  `machine_id` int NOT NULL AUTO_INCREMENT,
  `machine_name` varchar(255) NOT NULL,
  PRIMARY KEY (`machine_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `security_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parametermatrix`
--

DROP TABLE IF EXISTS `parametermatrix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametermatrix` (
  `ParameterID` int NOT NULL AUTO_INCREMENT,
  `ParameterName` varchar(255) NOT NULL,
  `ParentID` int DEFAULT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ParameterID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quote_headers`
--

DROP TABLE IF EXISTS `quote_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_headers` (
  `quote_id` int NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(30) NOT NULL,
  `customer_id` varchar(20) DEFAULT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_attention` varchar(255) DEFAULT NULL,
  `client_address` text,
  `client_city` varchar(255) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `matrix_name` varchar(255) DEFAULT NULL,
  `turnaround_time` varchar(255) DEFAULT NULL,
  `quote_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(7,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `terms_text` text,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`quote_id`),
  UNIQUE KEY `uq_quote_number` (`quote_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quote_items`
--

DROP TABLE IF EXISTS `quote_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `quote_id` int NOT NULL,
  `line_order` int NOT NULL DEFAULT '1',
  `parameter_id` int DEFAULT NULL,
  `standard_id` int DEFAULT NULL,
  `test_code` varchar(100) DEFAULT NULL,
  `description_text` text NOT NULL,
  `method_text` varchar(255) DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`item_id`),
  KEY `idx_quote_items_quote_id` (`quote_id`),
  CONSTRAINT `fk_quote_items_quote_id` FOREIGN KEY (`quote_id`) REFERENCES `quote_headers` (`quote_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset`
--

DROP TABLE IF EXISTS `password_reset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset` (
  `reset_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `reset_token` (`reset_token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_reset_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_security`
--

DROP TABLE IF EXISTS `role_security`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_security` (
  `role_id` int NOT NULL,
  `security_id` int NOT NULL,
  PRIMARY KEY (`role_id`,`security_id`),
  CONSTRAINT `role_security_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `role_description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sample_custody`
--

DROP TABLE IF EXISTS `sample_custody`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sample_custody` (
  `CustodyID` int NOT NULL AUTO_INCREMENT,
  `SampleID` varchar(20) DEFAULT NULL,
  `HandlerName` varchar(255) DEFAULT NULL,
  `Action` varchar(255) DEFAULT NULL,
  `DateTime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Location` varchar(255) DEFAULT NULL,
  `Notes` text,
  PRIMARY KEY (`CustodyID`),
  KEY `fk_sample_id` (`SampleID`),
  CONSTRAINT `fk_sample_id` FOREIGN KEY (`SampleID`) REFERENCES `sample_tests` (`SampleID`),
  CONSTRAINT `sample_custody_ibfk_1` FOREIGN KEY (`SampleID`) REFERENCES `sample_tests` (`SampleID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sample_header`
--

DROP TABLE IF EXISTS `sample_header`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sample_header` (
  `HeaderID` int NOT NULL AUTO_INCREMENT,
  `Date` datetime NOT NULL,
  `DocumentNo` varchar(50) NOT NULL,
  `CustomerName` varchar(255) NOT NULL,
  `CustomerID` varchar(20) NOT NULL,
  `SampledBy` varchar(100) NOT NULL,
  `SamplingMethod` varchar(255) NOT NULL,
  `SamplingDate` datetime NOT NULL,
  `OrderNo` varchar(50) NOT NULL,
  `ScopeOfWork` text,
  `User_name` varchar(255) NOT NULL,
  `previous_version_id` int DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`HeaderID`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sample_schedule`
--

DROP TABLE IF EXISTS `sample_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sample_schedule` (
  `ScheduleID` int NOT NULL AUTO_INCREMENT,
  `SampleID` varchar(20) NOT NULL,
  `ScheduleDate` date NOT NULL,
  `Notes` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ScheduleID`),
  KEY `SampleID` (`SampleID`),
  CONSTRAINT `sample_schedule_ibfk_1` FOREIGN KEY (`SampleID`) REFERENCES `sample_tests` (`SampleID`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sample_statuses`
--

DROP TABLE IF EXISTS `sample_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sample_statuses` (
  `StatusID` int NOT NULL AUTO_INCREMENT,
  `StatusOrder` int NOT NULL,
  `StatusName` varchar(255) NOT NULL,
  `Description` text,
  `ColorCode` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`StatusID`),
  UNIQUE KEY `StatusOrder` (`StatusOrder`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sample_tests`
--

DROP TABLE IF EXISTS `sample_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sample_tests` (
  `TestID` int NOT NULL AUTO_INCREMENT,
  `HeaderID` int NOT NULL,
  `SampleID` varchar(20) NOT NULL,
  `StandardID` int NOT NULL,
  `SampleFileKey` varchar(255) DEFAULT 'icons8-no-image-100.png',
  `SampleFee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `SKU` varchar(100) DEFAULT NULL,
  `BatchNo` varchar(50) DEFAULT NULL,
  `BatchSize` int DEFAULT NULL,
  `ManufactureDate` datetime DEFAULT NULL,
  `ExpDate` datetime DEFAULT NULL,
  `ExternalSample` varchar(255) DEFAULT NULL,
  `User_name` varchar(255) NOT NULL,
  `previous_version_id` int DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  `disposal_reason` text,
  `disposal_timestamp` datetime DEFAULT NULL,
  `disposed_by` int DEFAULT NULL,
  `environmental_id` int DEFAULT NULL,
  `datetestended` datetime DEFAULT NULL,
  `BaseID` int DEFAULT NULL,
  `coa_email_send_count` int NOT NULL DEFAULT '0',
  `coa_last_emailed_at` datetime DEFAULT NULL,
  `coa_last_email_to` varchar(255) DEFAULT NULL,
  `coa_last_email_status` varchar(20) DEFAULT NULL,
  `coa_last_email_message` text,
  PRIMARY KEY (`TestID`),
  UNIQUE KEY `uq_sample_tests` (`SampleID`,`StandardID`,`HeaderID`),
  KEY `HeaderID` (`HeaderID`),
  KEY `StandardID` (`StandardID`),
  KEY `idx_sampleid` (`SampleID`),
  KEY `fk_disposed_by_user` (`disposed_by`),
  KEY `idx_environmental_id` (`environmental_id`),
  CONSTRAINT `fk_disposed_by_user` FOREIGN KEY (`disposed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `sample_tests_ibfk_1` FOREIGN KEY (`HeaderID`) REFERENCES `sample_header` (`HeaderID`) ON DELETE RESTRICT,
  CONSTRAINT `sample_tests_ibfk_3` FOREIGN KEY (`StandardID`) REFERENCES `testparameters` (`StandardID`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `certificate_email_logs`
--

DROP TABLE IF EXISTS `certificate_email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificate_email_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `TestID` int NOT NULL,
  `report_option` tinyint NOT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `status` enum('success','failure') NOT NULL,
  `message` text,
  `sent_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_certificate_email_logs_testid` (`TestID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `samples_received`
--

DROP TABLE IF EXISTS `samples_received`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `samples_received` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sample_id` varchar(20) NOT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `remarks` text,
  `assigned_department` enum('chemical','biological','admin','guest','microbiological') NOT NULL,
  `condition` enum('intact','damaged','other') NOT NULL,
  `received_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sop_access_log`
--

DROP TABLE IF EXISTS `sop_access_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sop_access_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `sop_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `sop_id` (`sop_id`),
  CONSTRAINT `sop_access_log_ibfk_1` FOREIGN KEY (`sop_id`) REFERENCES `sops` (`sop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sops`
--

DROP TABLE IF EXISTS `sops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sops` (
  `sop_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `document_url` text,
  `version_number` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `standard_methods`
--

DROP TABLE IF EXISTS `standard_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `standard_methods` (
  `MethodID` int NOT NULL AUTO_INCREMENT,
  `standard_method` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`MethodID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `standard_parameter_matrix_config`
--

DROP TABLE IF EXISTS `standard_parameter_matrix_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `standard_parameter_matrix_config` (
  `ConfigID` int NOT NULL AUTO_INCREMENT,
  `ParameterID` int NOT NULL,
  `MatrixID` int NOT NULL,
  `IsDefault` tinyint(1) DEFAULT '0',
  `Notes` text,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ConfigID`),
  KEY `fk_spm_param_idx` (`ParameterID`),
  KEY `fk_spm_matrix` (`MatrixID`),
  CONSTRAINT `fk_spm_matrix` FOREIGN KEY (`MatrixID`) REFERENCES `parametermatrix` (`ParameterID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=491 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subcontractors`
--

DROP TABLE IF EXISTS `subcontractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subcontractors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `address` varchar(50) DEFAULT NULL,
  `address2` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `alt_contact` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `systypes`
--

DROP TABLE IF EXISTS `systypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `systypes` (
  `typeid` smallint NOT NULL DEFAULT '0',
  `typename` char(50) NOT NULL,
  `typeno` int NOT NULL DEFAULT '1',
  `prefix` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `test_assignments`
--

DROP TABLE IF EXISTS `test_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_assignments` (
  `user_id` int DEFAULT NULL,
  `resultsID` int NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `assigned_at` varchar(45) DEFAULT 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
  `subcontractor` int DEFAULT NULL,
  `category` enum('chemical','admin','microbiological') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_test` (`user_id`,`resultsID`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `test_results`
--

DROP TABLE IF EXISTS `test_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_results` (
  `resultsID` int NOT NULL AUTO_INCREMENT,
  `TestID` int NOT NULL,
  `HeaderID` int NOT NULL,
  `SampleID` varchar(20) NOT NULL,
  `StandardID` int NOT NULL,
  `ParameterID` int NOT NULL,
  `MRL_Result` varchar(20) DEFAULT NULL,
  `ResultStatus` enum('ND','Absent','Detected','Below Limit','Detected Range','Trace','Above Limit','Inconclusive','Error','Invalid') DEFAULT NULL,
  `RangeResult` varchar(255) DEFAULT NULL,
  `User_name` varchar(255) NOT NULL,
  `StatusID` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approvedby` int DEFAULT NULL,
  `reviewedby` int DEFAULT NULL,
  `alteredby` int DEFAULT NULL,
  `BaseID` int DEFAULT NULL,
  PRIMARY KEY (`resultsID`),
  UNIQUE KEY `uq_test_results` (`TestID`,`HeaderID`,`SampleID`,`StandardID`,`ParameterID`),
  KEY `HeaderID` (`HeaderID`),
  KEY `ParameterID` (`ParameterID`),
  KEY `StandardID` (`StandardID`),
  CONSTRAINT `test_results_ibfk_1` FOREIGN KEY (`TestID`) REFERENCES `sample_tests` (`TestID`) ON DELETE RESTRICT,
  CONSTRAINT `test_results_ibfk_2` FOREIGN KEY (`HeaderID`) REFERENCES `sample_header` (`HeaderID`) ON DELETE RESTRICT,
  CONSTRAINT `test_results_ibfk_3` FOREIGN KEY (`ParameterID`) REFERENCES `testparameters` (`ParameterID`) ON DELETE RESTRICT,
  CONSTRAINT `test_results_ibfk_4` FOREIGN KEY (`StandardID`) REFERENCES `testparameters` (`StandardID`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `testparameters`
--

DROP TABLE IF EXISTS `testparameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testparameters` (
  `ParameterID` int NOT NULL AUTO_INCREMENT,
  `ParameterName` varchar(255) NOT NULL,
  `StandardID` int NOT NULL,
  `Limits` varchar(255) DEFAULT NULL,
  `MinLimit` decimal(10,2) DEFAULT NULL,
  `MaxLimit` decimal(10,2) DEFAULT NULL,
  `Method` varchar(255) DEFAULT NULL,
  `Vital` tinyint(1) DEFAULT '0',
  `Category` enum('microbiological','chemical') DEFAULT 'chemical',
  `MRL` decimal(10,2) DEFAULT NULL,
  `MRLUnit` varchar(50) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `UnitOfMeasure` varchar(50) DEFAULT NULL,
  `BaseID` int DEFAULT NULL,
  `matrixID` int DEFAULT NULL,
  PRIMARY KEY (`ParameterID`),
  KEY `StandardID` (`StandardID`),
  CONSTRAINT `testparameters_ibfk_1` FOREIGN KEY (`StandardID`) REFERENCES `teststandards` (`StandardID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1987 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teststandards`
--

DROP TABLE IF EXISTS `teststandards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teststandards` (
  `StandardID` int NOT NULL AUTO_INCREMENT,
  `StandardName` varchar(255) NOT NULL,
  `Description` text,
  `ApplicableRegulation` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `StandardCode` varchar(100) DEFAULT NULL,
  `sm` int DEFAULT NULL,
  PRIMARY KEY (`StandardID`)
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transaction_metadata`
--

DROP TABLE IF EXISTS `transaction_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_metadata` (
  `metadata_id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(255) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `block_id` int DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`metadata_id`)
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `telephone` varchar(12) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` int NOT NULL DEFAULT '10',
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `full_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `department` enum('chemical','microbiological','admin','guest') NOT NULL DEFAULT 'guest',
  `signature_image` longblob,
  `signature_path` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-24 11:56:59
