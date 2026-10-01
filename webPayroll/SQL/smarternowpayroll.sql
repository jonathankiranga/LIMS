-- MySQL dump 10.13  Distrib 8.0.26, for Win64 (x86_64)
--
-- Host: localhost    Database: webpayroll
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-24 11:58:54
