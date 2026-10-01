-- MySQL dump 10.13  Distrib 8.0.26, for Win64 (x86_64)
--
-- Host: localhost    Database: mozillaerpv2
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
-- Table structure for table `accountsetup`
--

DROP TABLE IF EXISTS `accountsetup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accountsetup` (
  `salesfrom` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salesto` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stockfrom` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stockto` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchasesfrom` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchasesto` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expensefrom` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expenseto` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fixedassetsFrom` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fixedassetsTo` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inventoryFrom` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inventoryTo` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DebtorsFrom` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DebtorsTo` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BankFrom` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BankTo` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CurLiabFrom` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CurLiabTo` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `LongLiabFrom` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `LongLiabTo` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CapitalFrom` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CapitalTo` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct`
--

DROP TABLE IF EXISTS `acct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acct` (
  `ReportStyle` int DEFAULT NULL,
  `ReportCode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Sale_Purchase_Neither` int DEFAULT NULL,
  `Calculation` longtext COLLATE utf8mb4_unicode_ci,
  `system` tinyint(1) DEFAULT NULL,
  `direct` tinyint(1) DEFAULT NULL,
  `balance_income` int DEFAULT NULL,
  `accgrp` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accdesc` char(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oldaccno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postinggroup` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) NOT NULL,
  `PKey` bigint NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`PKey`),
  UNIQUE KEY `IX_acct` (`accno`)
) ENGINE=InnoDB AUTO_INCREMENT=30407 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `arpostinggroups`
--

DROP TABLE IF EXISTS `arpostinggroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `arpostinggroups` (
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchaseaccount` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creditorsaccount` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `VATinclusive` tinyint(1) DEFAULT NULL,
  `IsTaxed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assetsheader`
--

DROP TABLE IF EXISTS `assetsheader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assetsheader` (
  `documenttype` int NOT NULL,
  `documentno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `docdate` datetime DEFAULT NULL,
  `oderdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `postingdate` datetime DEFAULT NULL,
  `vendorcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendorname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `yourreference` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `externaldocumentno` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coa_documentno` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locationcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paymentterms` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postinggroup` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currencycode` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `printed` int DEFAULT NULL,
  `released` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `userid` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` int NOT NULL,
  `entryno` bigint NOT NULL AUTO_INCREMENT,
  `vatinclusive` tinyint(1) DEFAULT NULL,
  `Dimension_1` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Dimension_2` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`entryno`),
  KEY `documentno` (`documentno`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audittrail`
--

DROP TABLE IF EXISTS `audittrail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audittrail` (
  `transactiondate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `querystring` longtext COLLATE utf8mb4_unicode_ci,
  KEY `audittrail_ibfk_1` (`userid`),
  CONSTRAINT `audittrail_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `www_users` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bankaccounts`
--

DROP TABLE IF EXISTS `bankaccounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bankaccounts` (
  `PKey` int NOT NULL AUTO_INCREMENT,
  `accountcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bankName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastreconcileddate` date DEFAULT NULL,
  `AccountNo` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BranchCode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BranchName` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastreconbalance` decimal(18,2) DEFAULT NULL,
  `StatementNo` int DEFAULT NULL,
  `lastChequeno` int DEFAULT NULL,
  `PostingGroup` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Makeinactive` tinyint(1) DEFAULT NULL,
  `Fluctuation` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `AcctName` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bankCode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `swiftcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`PKey`)
) ENGINE=InnoDB AUTO_INCREMENT=1389 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bankreconciliation`
--

DROP TABLE IF EXISTS `bankreconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bankreconciliation` (
  `PKey` int NOT NULL AUTO_INCREMENT,
  `StatementNo` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bankcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `narration` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`PKey`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banktransactions`
--

DROP TABLE IF EXISTS `banktransactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banktransactions` (
  `bankcode` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DocDate` date NOT NULL,
  `doctype` int DEFAULT NULL,
  `DocumentNo` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `TransType` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `journal` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` double NOT NULL,
  `ClearedAmount` double DEFAULT NULL,
  `narrative` longtext COLLATE utf8mb4_unicode_ci,
  `exchangerate` decimal(10,4) DEFAULT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `ChequePrinted` tinyint(1) DEFAULT NULL,
  `reconciled` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `billofmaterial`
--

DROP TABLE IF EXISTS `billofmaterial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `billofmaterial` (
  `parent_itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_description` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_UOM` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batchsize` double DEFAULT NULL,
  PRIMARY KEY (`parent_itemcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bom_items`
--

DROP TABLE IF EXISTS `bom_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bom_items` (
  `parent_itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` int DEFAULT NULL,
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uom` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgets` (
  `periodno` int NOT NULL,
  `dimecode` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(18,0) NOT NULL,
  `dimecode2` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `buffertable`
--

DROP TABLE IF EXISTS `buffertable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buffertable` (
  `averagestock` double DEFAULT NULL,
  `partperunit` int DEFAULT NULL,
  `units` int DEFAULT NULL,
  `uom` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `batchno` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctype` int DEFAULT NULL,
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TABLE` longtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commision`
--

DROP TABLE IF EXISTS `commision`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commision` (
  `rownumber` int DEFAULT NULL,
  `commisionabove` double DEFAULT NULL,
  `commisionbelow` double DEFAULT NULL,
  `ceiling` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `coycode` int NOT NULL DEFAULT '1',
  `coyname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PIN` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vat` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regoffice1` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regoffice2` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regoffice3` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regoffice4` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regoffice5` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regoffice6` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fax` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(55) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currencydefault` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DefaultDimension_1` int DEFAULT NULL,
  `DefaultDimension_2` int DEFAULT NULL,
  `shiftno` int DEFAULT NULL,
  `Commision` double DEFAULT NULL,
  `CommissionRetention` double DEFAULT NULL,
  `ReduceCommissionRetention` double DEFAULT NULL,
  `PeriodRollover` date DEFAULT NULL,
  `CoyAuthorisedBy` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`coycode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `confname` varchar(35) COLLATE utf8mb4_unicode_ci NOT NULL,
  `confvalue` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`confname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `containers`
--

DROP TABLE IF EXISTS `containers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `containers` (
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ContainerCode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ContainerQTY` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `creditors`
--

DROP TABLE IF EXISTS `creditors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creditors` (
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defaultgl` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vat` tinyint(1) DEFAULT NULL,
  `contact` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `customer` char(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstn` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middlen` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastn` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `altcontact` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `vatregno` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `sns` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` decimal(18,4) DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  `saved` tinyint(1) DEFAULT NULL,
  `supplierposting` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `IsEmployee` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `creditorsledger`
--

DROP TABLE IF EXISTS `creditorsledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creditorsledger` (
  `date` datetime NOT NULL,
  `whenpaid` datetime DEFAULT NULL,
  `acctfolio` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invref` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatamt` decimal(9,2) DEFAULT NULL,
  `vatc` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taxamt` decimal(9,2) DEFAULT NULL,
  `pamount` decimal(13,2) DEFAULT NULL,
  `allocat` decimal(13,2) DEFAULT NULL,
  `amount` decimal(13,2) DEFAULT NULL,
  `module` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cash` tinyint(1) DEFAULT NULL,
  `del` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `i_n_t` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `journal` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `typ` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contractno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lpolso` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `period` int DEFAULT NULL,
  `systypes_1` int DEFAULT NULL,
  `ledger` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rowid` bigint NOT NULL AUTO_INCREMENT,
  `whtax` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `currency` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currabrev` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hundredsname` char(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decimalplaces` smallint NOT NULL DEFAULT '2',
  `rate` double NOT NULL DEFAULT '1',
  `webcart` smallint NOT NULL DEFAULT '1',
  PRIMARY KEY (`currabrev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customerstatement`
--

DROP TABLE IF EXISTS `customerstatement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customerstatement` (
  `Date` date NOT NULL,
  `Documentno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Documenttype` int NOT NULL,
  `Accountno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Grossamount` double NOT NULL,
  `JournalNo` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Dimension_One` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Dimension_Two` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Datewhenpaid` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customervisits`
--

DROP TABLE IF EXISTS `customervisits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customervisits` (
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastcompleted` date NOT NULL,
  `today` date NOT NULL,
  `taskid` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taskdescription` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` longtext COLLATE utf8mb4_unicode_ci,
  `userresponsible` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manager` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoiced` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtors`
--

DROP TABLE IF EXISTS `debtors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtors` (
  `type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `istaff` int DEFAULT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `pinno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cardadd` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defaultgl` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `flag` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `creditlimit` decimal(14,2) DEFAULT NULL,
  `customer` longtext COLLATE utf8mb4_unicode_ci,
  `status` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middlen` char(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `altcontact` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preffpay` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `crdcardno` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `namecrd` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `id` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `i_n_t` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `typ` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sns` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` decimal(18,0) DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` bigint NOT NULL AUTO_INCREMENT,
  `islocal` tinyint(1) DEFAULT NULL,
  `username` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customerposting` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salesman` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debtorsledger`
--

DROP TABLE IF EXISTS `debtorsledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debtorsledger` (
  `date` datetime NOT NULL,
  `details` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `flag` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invref` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acctfolio` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allocat` double DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `pamount` double DEFAULT NULL,
  `module` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cash` tinyint(1) DEFAULT NULL,
  `del` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_rat` double DEFAULT NULL,
  `id` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `i_n_t` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` int DEFAULT NULL,
  `journal` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `typ` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sns` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatamt` double DEFAULT NULL,
  `vatc` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  `systypes_1` int DEFAULT NULL,
  `ledger` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dimensions`
--

DROP TABLE IF EXISTS `dimensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dimensions` (
  `id` int NOT NULL,
  `Code` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Dimension` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PARENT` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `LEVEL` int DEFAULT NULL,
  `BLOCKED` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`Code`),
  UNIQUE KEY `IX_Dimensions` (`id`,`Code`),
  CONSTRAINT `FK_Dimensions_DimensionSetUp` FOREIGN KEY (`id`) REFERENCES `dimensionsetup` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dimensionsetup`
--

DROP TABLE IF EXISTS `dimensionsetup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dimensionsetup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Dimension_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `discouts`
--

DROP TABLE IF EXISTS `discouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discouts` (
  `Rate` float NOT NULL,
  `QTY` double NOT NULL,
  `rowid` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `emailsettings`
--

DROP TABLE IF EXISTS `emailsettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emailsettings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `host` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` char(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `heloaddress` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timeout` int DEFAULT '5',
  `companyname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auth` smallint DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employeeproductionrates`
--

DROP TABLE IF EXISTS `employeeproductionrates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employeeproductionrates` (
  `StaffID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `StockID` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Rate` double NOT NULL,
  `UOM` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enterbillheaders`
--

DROP TABLE IF EXISTS `enterbillheaders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enterbillheaders` (
  `date` date DEFAULT NULL,
  `documenttype` int DEFAULT NULL,
  `documentno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `narration` longtext COLLATE utf8mb4_unicode_ci,
  `journalno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whtax` tinyint(1) DEFAULT NULL,
  `VendorID` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enterbillslines`
--

DROP TABLE IF EXISTS `enterbillslines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enterbillslines` (
  `documenttype` int DEFAULT NULL,
  `documentno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `journalno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatamount` decimal(10,2) DEFAULT NULL,
  `grossamount` decimal(10,2) DEFAULT NULL,
  `assetid` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fellowships`
--

DROP TABLE IF EXISTS `fellowships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fellowships` (
  `pkey` int NOT NULL AUTO_INCREMENT,
  `fellowships` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `financialperiods`
--

DROP TABLE IF EXISTS `financialperiods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financialperiods` (
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `Name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `newyear` tinyint(1) DEFAULT NULL,
  `closed` tinyint(1) DEFAULT NULL,
  `periodno` int DEFAULT NULL,
  PRIMARY KEY (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixedassetcategories`
--

DROP TABLE IF EXISTS `fixedassetcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixedassetcategories` (
  `categoryid` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorydescription` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `costact` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `depnact` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disposalact` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accumdepnact` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `defaultdepnrate` double NOT NULL DEFAULT '0.2',
  `defaultdepntype` int NOT NULL DEFAULT '1',
  `Equipment_hired_act` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defaultgl_vat_act` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatcategorycode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixedassetlocations`
--

DROP TABLE IF EXISTS `fixedassetlocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixedassetlocations` (
  `locationid` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locationdescription` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parentlocationid` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`locationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixedassets`
--

DROP TABLE IF EXISTS `fixedassets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixedassets` (
  `assetid` int NOT NULL AUTO_INCREMENT,
  `serialno` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assetlocation` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost` double NOT NULL DEFAULT '0',
  `accumdepn` double NOT NULL DEFAULT '0',
  `datepurchased` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disposalproceeds` double NOT NULL DEFAULT '0',
  `assetcategoryid` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `longdescription` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `depntype` int NOT NULL DEFAULT '1',
  `depnrate` double NOT NULL,
  `disposaldate` datetime DEFAULT NULL,
  PRIMARY KEY (`assetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixedassetsline`
--

DROP TABLE IF EXISTS `fixedassetsline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixedassetsline` (
  `entryno` bigint NOT NULL AUTO_INCREMENT,
  `documenttype` int NOT NULL,
  `docdate` datetime NOT NULL,
  `documentno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locationcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stocktype` int DEFAULT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unitofmeasure` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Quantity` decimal(10,2) DEFAULT NULL,
  `Quantity_toinvoice` decimal(10,2) DEFAULT NULL,
  `Qunatity_delivered` decimal(10,2) DEFAULT NULL,
  `UnitPrice` decimal(10,2) DEFAULT NULL,
  `vatamount` decimal(10,2) DEFAULT NULL,
  `invoiceamount` decimal(10,2) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `containerprice` decimal(10,2) DEFAULT NULL,
  `containersunits` decimal(10,0) DEFAULT NULL,
  `totalchargedcontainers` decimal(10,2) DEFAULT NULL,
  `containercode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatrate` decimal(10,2) DEFAULT NULL,
  `inclusive` tinyint(1) DEFAULT NULL,
  `UOM` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping` double DEFAULT NULL,
  PRIMARY KEY (`entryno`),
  KEY `documentno` (`documentno`),
  CONSTRAINT `FK_FixedAssetsLine_AssetsHeader` FOREIGN KEY (`documentno`) REFERENCES `assetsheader` (`documentno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixedassettasks`
--

DROP TABLE IF EXISTS `fixedassettasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixedassettasks` (
  `taskid` int NOT NULL AUTO_INCREMENT,
  `assetid` int NOT NULL,
  `taskdescription` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencydays` int NOT NULL DEFAULT '365',
  `lastcompleted` datetime NOT NULL,
  `userresponsible` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manager` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fixedassettrans`
--

DROP TABLE IF EXISTS `fixedassettrans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixedassettrans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assetid` int NOT NULL,
  `transtype` smallint NOT NULL,
  `transdate` datetime NOT NULL,
  `transno` int NOT NULL,
  `periodno` smallint NOT NULL,
  `inputdate` datetime NOT NULL,
  `fixedassettranstype` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` double NOT NULL,
  `units` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `generalledger`
--

DROP TABLE IF EXISTS `generalledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `generalledger` (
  `rowid` bigint NOT NULL AUTO_INCREMENT,
  `journalno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Docdate` datetime NOT NULL,
  `period` int NOT NULL,
  `DocumentNo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DocumentType` int NOT NULL,
  `accountcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `balaccountcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` double NOT NULL,
  `currencycode` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ExchangeRate` double DEFAULT NULL,
  `cutomercode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suppliercode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bankcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reconcilled` tinyint(1) DEFAULT NULL,
  `narration` longtext COLLATE utf8mb4_unicode_ci,
  `ExchangeRateDiff` double DEFAULT NULL,
  `VATaccountcode` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `VATamount` decimal(10,2) DEFAULT NULL,
  `dimension` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dimension2` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asset_id` int DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `FK_Generalledger_acct` (`accountcode`),
  KEY `FK_Generalledger_acct1` (`balaccountcode`),
  KEY `FK_GL_fixedassets` (`asset_id`),
  CONSTRAINT `FK_Generalledger_acct` FOREIGN KEY (`accountcode`) REFERENCES `acct` (`accno`),
  CONSTRAINT `FK_Generalledger_acct1` FOREIGN KEY (`balaccountcode`) REFERENCES `acct` (`accno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geocode_param`
--

DROP TABLE IF EXISTS `geocode_param`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geocode_param` (
  `geocodeid` smallint NOT NULL AUTO_INCREMENT,
  `geocode_key` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `center_long` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `center_lat` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `map_height` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `map_width` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `map_host` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`geocodeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpostinggroup`
--

DROP TABLE IF EXISTS `glpostinggroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpostinggroup` (
  `code` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `defaultgl_vat` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatcategory` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventorypostinggroup`
--

DROP TABLE IF EXISTS `inventorypostinggroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventorypostinggroup` (
  `code` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `defaultgl_sales` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defaultgl_purch` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defaultgl_vat` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balancesheet` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatcategory` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wip` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stockvariance` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `productionexpense` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CostOfSales` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spoilage` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `journalentries`
--

DROP TABLE IF EXISTS `journalentries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `journalentries` (
  `Docdate` date NOT NULL,
  `JournalNo` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Account` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `BalAccount` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transtype` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` double NOT NULL,
  `Currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Dimension_1` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Dimension_2` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `narration` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `laboratorystandards`
--

DROP TABLE IF EXISTS `laboratorystandards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `laboratorystandards` (
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ParameterID` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Parameter` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Limits_min` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Limits_max` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Units` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vital` tinyint(1) DEFAULT NULL,
  `category` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NoStandardlimit` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `labpostingdetail`
--

DROP TABLE IF EXISTS `labpostingdetail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `labpostingdetail` (
  `DocumentNo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `SampleID` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `SampleTypeID` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ParameterID` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Limits_min` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Limits_max` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Results` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastuserid` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastdatetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member` (
  `type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `istaff` int DEFAULT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `pinno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cardadd` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defaultgl` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `flag` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `creditlimit` decimal(14,2) DEFAULT NULL,
  `customer` char(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middlen` char(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `altcontact` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preffpay` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `crdcardno` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `namecrd` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `id` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `i_n_t` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `typ` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sns` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` decimal(18,0) DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  `islocal` tinyint(1) DEFAULT NULL,
  `username` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customerposting` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salesman` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `naturalelements`
--

DROP TABLE IF EXISTS `naturalelements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `naturalelements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `newactivity`
--

DROP TABLE IF EXISTS `newactivity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newactivity` (
  `pkey` int NOT NULL AUTO_INCREMENT,
  `ActivityOwner` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Activityname` longtext COLLATE utf8mb4_unicode_ci,
  `fromdue` date DEFAULT NULL,
  `todue` date DEFAULT NULL,
  `Contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Status` int DEFAULT NULL,
  `valueofbusiness` double DEFAULT NULL,
  `taskdetails` longtext COLLATE utf8mb4_unicode_ci,
  `createdby` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdon` datetime DEFAULT NULL,
  `lastactivity` datetime DEFAULT NULL,
  `Sart_time_from` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Sart_time_to` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `End_time_from` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `End_time_to` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `newcontacts`
--

DROP TABLE IF EXISTS `newcontacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newcontacts` (
  `company` longtext COLLATE utf8mb4_unicode_ci,
  `postcode` longtext COLLATE utf8mb4_unicode_ci,
  `city` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Physical_Address` longtext COLLATE utf8mb4_unicode_ci,
  `PIN_VAT` longtext COLLATE utf8mb4_unicode_ci,
  `phone` char(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` longtext COLLATE utf8mb4_unicode_ci,
  `salesman` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Contact_Name` longtext COLLATE utf8mb4_unicode_ci,
  `Contact_Designation` longtext COLLATE utf8mb4_unicode_ci,
  `Contact_Telephone` char(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Contact_email` longtext COLLATE utf8mb4_unicode_ci,
  `Alt_Contact_Name` longtext COLLATE utf8mb4_unicode_ci,
  `Alt_Contact_Designation` longtext COLLATE utf8mb4_unicode_ci,
  `Alt_Contact_Telephone` char(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Alt_Contact_email` longtext COLLATE utf8mb4_unicode_ci,
  `createdby` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Date_Created` datetime DEFAULT NULL,
  `Last_Activity` datetime DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `paymentsallocation`
--

DROP TABLE IF EXISTS `paymentsallocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paymentsallocation` (
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `invoiceno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `journalno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctype` int NOT NULL,
  `receiptno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receiptjournal` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `paymentvoucherheader`
--

DROP TABLE IF EXISTS `paymentvoucherheader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paymentvoucherheader` (
  `docno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `externalref` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `narrative` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `journal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int DEFAULT NULL,
  `Comments` longtext COLLATE utf8mb4_unicode_ci,
  `ChequePrinted` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `paymentvoucherline`
--

DROP TABLE IF EXISTS `paymentvoucherline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paymentvoucherline` (
  `docno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `narrative` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `journal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_journal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Dimension_1` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Dimension_2` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Budget` decimal(18,2) DEFAULT NULL,
  `Committed` decimal(18,2) DEFAULT NULL,
  `Expensed` decimal(18,2) DEFAULT NULL,
  `Balance` decimal(18,2) DEFAULT NULL,
  `whtax` decimal(18,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `periods`
--

DROP TABLE IF EXISTS `periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periods` (
  `periodno` smallint NOT NULL DEFAULT '0',
  `lastdate_in_period` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`periodno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pettdoc`
--

DROP TABLE IF EXISTS `pettdoc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pettdoc` (
  `date` datetime DEFAULT NULL,
  `userid` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moneyin` decimal(10,2) DEFAULT NULL,
  `moneyout` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `account` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expensedetails` char(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `petteycashno` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `journal` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transtype` int DEFAULT NULL,
  `shiftno` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `postinggroups`
--

DROP TABLE IF EXISTS `postinggroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `postinggroups` (
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salesaccount` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `debtorsaccount` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `VATinclusive` tinyint(1) DEFAULT NULL,
  `IsTaxed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pricelist`
--

DROP TABLE IF EXISTS `pricelist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricelist` (
  `customerCode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stockcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved` tinyint(1) DEFAULT NULL,
  `approvedby` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DateTime` datetime DEFAULT NULL,
  `units_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `price` float DEFAULT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `container` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prodcutionmasterline`
--

DROP TABLE IF EXISTS `prodcutionmasterline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prodcutionmasterline` (
  `Batchno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `itemcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` double NOT NULL,
  `cost` double DEFAULT NULL,
  `volcorfactor` double DEFAULT NULL,
  `temperature` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productionconfig`
--

DROP TABLE IF EXISTS `productionconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productionconfig` (
  `categoryid` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rawmatid` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productionemployee`
--

DROP TABLE IF EXISTS `productionemployee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productionemployee` (
  `istaff` int DEFAULT NULL,
  `pinno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `manager` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salesman` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middlen` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `altcontact` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `postcode` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  `commissionposting` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productionmanager`
--

DROP TABLE IF EXISTS `productionmanager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productionmanager` (
  `istaff` int DEFAULT NULL,
  `pinno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `commission` decimal(14,2) DEFAULT NULL,
  `salesman` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middlen` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `altcontact` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `postcode` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  `commissionposting` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productionmaster`
--

DROP TABLE IF EXISTS `productionmaster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productionmaster` (
  `Batchno` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `userid` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `averagestock` double DEFAULT NULL,
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `production` int DEFAULT NULL,
  `testreport` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DateTestended` datetime DEFAULT NULL,
  `Interpretation` longtext COLLATE utf8mb4_unicode_ci,
  `Passed` int DEFAULT NULL,
  `Status` int DEFAULT NULL,
  `modified` double DEFAULT NULL,
  `bitumenph` double DEFAULT NULL,
  `SalesHeader` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  UNIQUE KEY `IX_ProductionMaster` (`Batchno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productionrates`
--

DROP TABLE IF EXISTS `productionrates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productionrates` (
  `LabourID` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LabourDescription` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Rate` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `productionunit`
--

DROP TABLE IF EXISTS `productionunit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productionunit` (
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` double NOT NULL,
  `tankname` char(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `UOM` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CapacityUOM` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `units` double DEFAULT NULL,
  `balance` double DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`tankname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchaseheader`
--

DROP TABLE IF EXISTS `purchaseheader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchaseheader` (
  `documenttype` int NOT NULL,
  `documentno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `docdate` datetime DEFAULT NULL,
  `oderdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `postingdate` datetime DEFAULT NULL,
  `vendorcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendorname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `yourreference` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `externaldocumentno` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locationcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paymentterms` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postinggroup` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currencycode` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `printed` int DEFAULT NULL,
  `released` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `userid` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` int NOT NULL,
  `entryno` bigint NOT NULL AUTO_INCREMENT,
  `vatinclusive` tinyint(1) DEFAULT NULL,
  `Dimension_1` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Dimension_2` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `freight` double DEFAULT NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchaseline`
--

DROP TABLE IF EXISTS `purchaseline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchaseline` (
  `entryno` bigint NOT NULL AUTO_INCREMENT,
  `documenttype` int NOT NULL,
  `docdate` datetime NOT NULL,
  `documentno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locationcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stocktype` int DEFAULT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unitofmeasure` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Quantity` decimal(10,2) DEFAULT NULL,
  `Quantity_toinvoice` decimal(10,2) DEFAULT NULL,
  `Qunatity_delivered` decimal(10,2) DEFAULT NULL,
  `UnitPrice` double DEFAULT NULL,
  `vatamount` decimal(10,2) DEFAULT NULL,
  `invoiceamount` decimal(10,2) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `containerprice` decimal(10,2) DEFAULT NULL,
  `containersunits` decimal(10,0) DEFAULT NULL,
  `totalchargedcontainers` decimal(10,2) DEFAULT NULL,
  `containercode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatrate` decimal(10,2) DEFAULT NULL,
  `inclusive` tinyint(1) DEFAULT NULL,
  `UOM` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping` double DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `PartPerUnit` double DEFAULT NULL,
  `QuantityToReceive` double DEFAULT NULL,
  `PriceToReceive` double DEFAULT NULL,
  `unitofreceivedIn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `packzizegrn` double DEFAULT NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `receiptheader`
--

DROP TABLE IF EXISTS `receiptheader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receiptheader` (
  `docno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `externalref` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `narrative` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `journal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `receiptsallocation`
--

DROP TABLE IF EXISTS `receiptsallocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receiptsallocation` (
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `invoiceno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `journalno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctype` int NOT NULL,
  `receiptno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receiptjournal` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salesheader`
--

DROP TABLE IF EXISTS `salesheader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salesheader` (
  `documenttype` int NOT NULL,
  `documentno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `docdate` datetime DEFAULT NULL,
  `oderdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `postingdate` datetime DEFAULT NULL,
  `customercode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customername` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `yourreference` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `externaldocumentno` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locationcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paymentterms` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postinggroup` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currencycode` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salespersoncode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `printed` int DEFAULT NULL,
  `released` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `userid` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` int NOT NULL,
  `entryno` bigint NOT NULL AUTO_INCREMENT,
  `vatinclusive` tinyint(1) DEFAULT NULL,
  `QtyDiscount` double DEFAULT NULL,
  `journal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping` double DEFAULT NULL,
  `packagescharge` double DEFAULT NULL,
  `picture` longtext COLLATE utf8mb4_unicode_ci,
  `pricingmode` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `etims_invoice`
--

DROP TABLE IF EXISTS `etims_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etims_invoice` (
  `entryno` bigint NOT NULL AUTO_INCREMENT,
  `documenttype` int NOT NULL,
  `documentno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kra_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cu_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `control_unit_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kra_pin` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code_text` longtext COLLATE utf8mb4_unicode_ci,
  `verification_url` longtext COLLATE utf8mb4_unicode_ci,
  `etims_issue_datetime` datetime DEFAULT NULL,
  `etims_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etims_message` longtext COLLATE utf8mb4_unicode_ci,
  `etims_raw_response` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entryno`),
  UNIQUE KEY `uniq_etims_invoice` (`documenttype`,`documentno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salesline`
--

DROP TABLE IF EXISTS `salesline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salesline` (
  `entryno` bigint NOT NULL AUTO_INCREMENT,
  `documenttype` int NOT NULL,
  `docdate` datetime NOT NULL,
  `documentno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locationcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stocktype` int DEFAULT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unitofmeasure` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Quantity` decimal(10,2) DEFAULT NULL,
  `Quantity_toinvoice` decimal(10,2) DEFAULT NULL,
  `Qunatity_delivered` decimal(10,2) DEFAULT NULL,
  `UnitPrice` decimal(10,2) DEFAULT NULL,
  `vatamount` decimal(10,2) DEFAULT NULL,
  `invoiceamount` decimal(10,2) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  `printed` tinyint(1) DEFAULT NULL,
  `containerprice` decimal(10,2) DEFAULT NULL,
  `containersunits` decimal(10,0) DEFAULT NULL,
  `totalchargedcontainers` decimal(10,2) DEFAULT NULL,
  `containercode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vatrate` decimal(10,2) DEFAULT NULL,
  `inclusive` tinyint(1) DEFAULT NULL,
  `UOM` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping` double DEFAULT NULL,
  `PriceInPricelist` double DEFAULT NULL,
  `Increase` double DEFAULT NULL,
  `PartPerUnit` double DEFAULT NULL,
  `modified` int DEFAULT NULL,
  `Qunatity_replaced` int DEFAULT NULL,
  `packagescharge` double DEFAULT NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salesrepsinfo`
--

DROP TABLE IF EXISTS `salesrepsinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salesrepsinfo` (
  `istaff` int DEFAULT NULL,
  `pinno` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `target` decimal(14,2) DEFAULT NULL,
  `commission` decimal(14,2) DEFAULT NULL,
  `salesman` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` char(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middlen` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastn` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `altcontact` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `postcode` char(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  `commissionposting` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scripts`
--

DROP TABLE IF EXISTS `scripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scripts` (
  `script` varchar(78) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pagesecurity` int NOT NULL DEFAULT '1',
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`script`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  PRIMARY KEY (`secroleid`,`tokenid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `securityroles`
--

DROP TABLE IF EXISTS `securityroles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `securityroles` (
  `secroleid` int NOT NULL AUTO_INCREMENT,
  `secrolename` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`secroleid`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `securitytokens`
--

DROP TABLE IF EXISTS `securitytokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `securitytokens` (
  `tokenid` int NOT NULL DEFAULT '0',
  `tokenname` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tokenid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stockcategory`
--

DROP TABLE IF EXISTS `stockcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockcategory` (
  `categoryid` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorydescription` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stockexchange`
--

DROP TABLE IF EXISTS `stockexchange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockexchange` (
  `docdate` datetime NOT NULL,
  `docno` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uom` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int NOT NULL,
  `userid` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acctfolio` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorised` int DEFAULT NULL,
  `authorisedby` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authoriseddate` datetime DEFAULT NULL,
  `direction` int DEFAULT NULL,
  `cost` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stockledger`
--

DROP TABLE IF EXISTS `stockledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockledger` (
  `date` datetime NOT NULL,
  `stname` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctyp` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invref` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `netamt` decimal(13,2) DEFAULT NULL,
  `vat` decimal(13,2) DEFAULT NULL,
  `amount` decimal(18,4) DEFAULT NULL,
  `fulqty` decimal(13,4) NOT NULL,
  `loosqty` decimal(13,4) NOT NULL,
  `price` decimal(9,2) DEFAULT NULL,
  `curr_rat` decimal(9,4) DEFAULT NULL,
  `stockvalue` decimal(18,4) DEFAULT NULL,
  `store` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `journal` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curr_cod` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period` int DEFAULT NULL,
  `del` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `factory` tinyint(1) DEFAULT NULL,
  `jobcard` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `len` decimal(10,2) DEFAULT NULL,
  `wid` decimal(10,2) DEFAULT NULL,
  `sman` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  `BQitemcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dimension` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PartPerUnit` double DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stockmaster`
--

DROP TABLE IF EXISTS `stockmaster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockmaster` (
  `isstock` int NOT NULL,
  `barcode` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descrip` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postinggroup` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `averagestock` decimal(10,2) DEFAULT NULL,
  `partperunit` decimal(10,2) DEFAULT NULL,
  `reorderlevel` decimal(10,0) DEFAULT NULL,
  `eoq` decimal(10,0) DEFAULT NULL,
  `sellingprice` decimal(10,2) DEFAULT NULL,
  `category` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `units` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subunits` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `container` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nextserialno` int DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `pkey` bigint NOT NULL AUTO_INCREMENT,
  `isstock_1` tinyint(1) DEFAULT NULL,
  `isstock_2` tinyint(1) DEFAULT NULL,
  `isstock_3` tinyint(1) DEFAULT NULL,
  `isstock_4` tinyint(1) DEFAULT NULL,
  `isstock_5` tinyint(1) DEFAULT NULL,
  `isstock_6` tinyint(1) DEFAULT NULL,
  `production` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stockregister`
--

DROP TABLE IF EXISTS `stockregister`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockregister` (
  `itemcode` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `StockIn` double DEFAULT NULL,
  `cost` double DEFAULT NULL,
  `StockOut` double DEFAULT NULL,
  `StockBalance` double DEFAULT NULL,
  `rowid` bigint NOT NULL AUTO_INCREMENT,
  `journal` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `GRN` char(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PartPerUnit` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stores` (
  `code` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Storename` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `supplierstatement`
--

DROP TABLE IF EXISTS `supplierstatement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplierstatement` (
  `Date` date NOT NULL,
  `Documentno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Documenttype` int NOT NULL,
  `Accountno` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Grossamount` decimal(18,2) NOT NULL,
  `JournalNo` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Dimension_One` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Dimension_Two` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whtax` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sysdiagrams`
--

DROP TABLE IF EXISTS `sysdiagrams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sysdiagrams` (
  `name` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `principal_id` int NOT NULL,
  `diagram_id` int NOT NULL AUTO_INCREMENT,
  `version` int DEFAULT NULL,
  `definition` longblob,
  PRIMARY KEY (`diagram_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_printers`
--

DROP TABLE IF EXISTS `system_printers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_printers` (
  `ipaddress` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `printerName` longtext COLLATE utf8mb4_unicode_ci,
  `defaultprinter` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `systypes_1`
--

DROP TABLE IF EXISTS `systypes_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `systypes_1` (
  `typeid` smallint NOT NULL DEFAULT '0',
  `typename` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `typeno` int NOT NULL DEFAULT '1',
  `prefix` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tanktrans`
--

DROP TABLE IF EXISTS `tanktrans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tanktrans` (
  `tankname` char(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `units` double DEFAULT NULL,
  `uom` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `batchno` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `variance` double DEFAULT NULL,
  `doctype` int DEFAULT NULL,
  `itemcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `index` bigint NOT NULL AUTO_INCREMENT,
  `BQitemcode` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `pkey` int NOT NULL AUTO_INCREMENT,
  `userid` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datecreated` datetime DEFAULT NULL,
  `TaskOwner` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Taskname` longtext COLLATE utf8mb4_unicode_ci,
  `datedue` date DEFAULT NULL,
  `Status` int DEFAULT NULL,
  `Priority` int DEFAULT NULL,
  `frequency` int DEFAULT NULL,
  `taskdetails` longtext COLLATE utf8mb4_unicode_ci,
  `alertedbymail` tinyint(1) DEFAULT NULL,
  `lastactivity` datetime DEFAULT NULL,
  `time_from` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time_to` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unit`
--

DROP TABLE IF EXISTS `unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit` (
  `code` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descrip` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sns` char(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pkey` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vatcategory`
--

DROP TABLE IF EXISTS `vatcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vatcategory` (
  `vatc` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vat` decimal(10,2) NOT NULL,
  `vatdecrip` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taxcatid` smallint NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`taxcatid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `www_users`
--

DROP TABLE IF EXISTS `www_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `www_users` (
  `userid` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `realname` varchar(35) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customerid` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplierid` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salesman` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(55) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defaultlocation` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullaccess` int NOT NULL DEFAULT '1',
  `cancreatetender` smallint NOT NULL DEFAULT '0',
  `lastvisitdate` datetime DEFAULT NULL,
  `branchcode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pagesize` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulesallowed` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `blocked` smallint NOT NULL DEFAULT '0',
  `displayrecordsmax` int NOT NULL DEFAULT '0',
  `theme` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pdflanguage` smallint NOT NULL DEFAULT '0',
  `department` int NOT NULL DEFAULT '0',
  `Currentshiftno` int DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-24 11:54:59

ALTER TABLE `Tasks`
  ADD COLUMN `lastalert` DATETIME NULL DEFAULT NULL
  COMMENT 'Timestamp of last follow-up alert sent';

CREATE INDEX idx_tasks_lastalert ON `Tasks` (`lastalert`);

CREATE TABLE RecurringActivities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  every_n INT NOT NULL DEFAULT 1,          -- interval size
  unit ENUM('hour','day','week','month') NOT NULL DEFAULT 'day',
  max_count INT NULL,                      -- stop after N occurrences
  until_date DATE NULL,                    -- or stop by date
  next_run DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rec_task FOREIGN KEY (task_id) REFERENCES Tasks(pkey)
);

--
-- Seed data for a LIMS company
--

INSERT INTO `stockcategory` (`categoryid`, `categorydescription`)
SELECT src.`categoryid`, src.`categorydescription`
FROM (
  SELECT 'SOLV' AS `categoryid`, 'Solvents and organics' AS `categorydescription`
  UNION ALL SELECT 'ACID', 'Acids and acid blends'
  UNION ALL SELECT 'BASE', 'Bases and alkalis'
  UNION ALL SELECT 'BUFFR', 'Buffers and reference solutions'
  UNION ALL SELECT 'QCSTD', 'QC and CRM standards'
  UNION ALL SELECT 'FILTR', 'Filters and membranes'
  UNION ALL SELECT 'GLASS', 'Glassware and lab bottles'
  UNION ALL SELECT 'CONS', 'General lab consumables'
  UNION ALL SELECT 'PPE', 'Safety and PPE'
  UNION ALL SELECT 'SAMP', 'Sampling supplies'
  UNION ALL SELECT 'LABEL', 'Labels and controlled forms'
  UNION ALL SELECT 'WATER', 'Purified water'
  UNION ALL SELECT 'CLEAN', 'Cleaning supplies'
) AS src
LEFT JOIN `stockcategory` sc ON sc.`categoryid` = src.`categoryid`
WHERE sc.`categoryid` IS NULL;

INSERT INTO `unit` (`code`, `descrip`)
SELECT src.`code`, src.`descrip`
FROM (
  SELECT 'LTR' AS `code`, 'Litre' AS `descrip`
  UNION ALL SELECT 'ML', 'Millilitre'
  UNION ALL SELECT 'KG', 'Kilogram'
  UNION ALL SELECT 'GM', 'Gram'
  UNION ALL SELECT 'PCS', 'Pieces'
  UNION ALL SELECT 'BX', 'Box'
  UNION ALL SELECT 'PK', 'Pack'
  UNION ALL SELECT 'BTL', 'Bottle'
  UNION ALL SELECT 'RL', 'Roll'
) AS src
LEFT JOIN `unit` u ON u.`code` = src.`code`
WHERE u.`code` IS NULL;

INSERT INTO `glpostinggroup` (`code`, `defaultgl_vat`, `vatcategory`)
SELECT src.`code`, src.`defaultgl_vat`, src.`vatcategory`
FROM (
  SELECT 'Business' AS `code`, 'LIMTX0402' AS `defaultgl_vat`, 'x' AS `vatcategory`
  UNION ALL SELECT 'vat', 'LIMTX0402', 'V'
) AS src
LEFT JOIN `glpostinggroup` g ON g.`code` = src.`code`
WHERE g.`code` IS NULL;

INSERT INTO `acct` (
  `ReportStyle`, `ReportCode`, `Sale_Purchase_Neither`, `Calculation`, `system`,
  `direct`, `balance_income`, `accgrp`, `currency`, `accno`, `accdesc`,
  `oldaccno`, `postinggroup`, `inactive`
)
SELECT
  src.`ReportStyle`, src.`ReportCode`, src.`Sale_Purchase_Neither`, src.`Calculation`, src.`system`,
  src.`direct`, src.`balance_income`, src.`accgrp`, src.`currency`, src.`accno`, src.`accdesc`,
  src.`oldaccno`, src.`postinggroup`, src.`inactive`
FROM (
  SELECT 0 AS `ReportStyle`, '000001' AS `ReportCode`, 0 AS `Sale_Purchase_Neither`, '' AS `Calculation`, NULL AS `system`, 1 AS `direct`, 0 AS `balance_income`, NULL AS `accgrp`, NULL AS `currency`, 'LIMFA0001' AS `accno`, 'Lab Equipment' AS `accdesc`, 'LIMFA0001' AS `oldaccno`, 'Business' AS `postinggroup`, 0 AS `inactive`
  UNION ALL SELECT 0, '000002', 0, '', NULL, 1, 0, NULL, NULL, 'LIMFA0002', 'Computers and IT Equip', 'LIMFA0002', 'Business', 0
  UNION ALL SELECT 0, '000003', 0, '', NULL, 1, 0, NULL, NULL, 'LIMFA0003', 'Office Furniture', 'LIMFA0003', 'Business', 0
  UNION ALL SELECT 0, '000004', 0, '', NULL, 1, 0, NULL, NULL, 'LIMFA0004', 'Field Sampling Gear', 'LIMFA0004', 'Business', 0
  UNION ALL SELECT 0, '000005', 0, '', NULL, 1, 0, NULL, NULL, 'LIMFA0005', 'Accum Depn-Lab Equip', 'LIMFA0005', 'Business', 0
  UNION ALL SELECT 0, '000100', 0, '', NULL, 1, 0, NULL, NULL, 'LIMIN0100', 'Reagents Inventory', 'LIMIN0100', 'vat', 0
  UNION ALL SELECT 0, '000101', 0, '', NULL, 1, 0, NULL, NULL, 'LIMIN0101', 'Glassware Inventory', 'LIMIN0101', 'vat', 0
  UNION ALL SELECT 0, '000102', 0, '', NULL, 1, 0, NULL, NULL, 'LIMIN0102', 'Sampling Matl Inventory', 'LIMIN0102', 'vat', 0
  UNION ALL SELECT 0, '000103', 0, '', NULL, 1, 0, NULL, NULL, 'LIMIN0103', 'QC Std Inventory', 'LIMIN0103', 'vat', 0
  UNION ALL SELECT 0, '000200', 0, '', NULL, 1, 0, NULL, NULL, 'LIMAR0200', 'Trade Receivables', 'LIMAR0200', 'Business', 0
  UNION ALL SELECT 0, '000201', 0, '', NULL, 1, 0, NULL, NULL, 'LIMAR0201', 'Staff Receivables', 'LIMAR0201', 'Business', 0
  UNION ALL SELECT 0, '000210', 0, '', NULL, 1, 0, NULL, NULL, 'LIMPA0210', 'Prepaid Insurance', 'LIMPA0210', 'Business', 0
  UNION ALL SELECT 0, '000211', 0, '', NULL, 1, 0, NULL, NULL, 'LIMPA0211', 'Prepaid Accreditation', 'LIMPA0211', 'Business', 0
  UNION ALL SELECT 0, '000300', 1, '', NULL, 1, 0, NULL, NULL, 'LIMBK0300', 'Bank-Operating', 'LIMBK0300', 'Business', 0
  UNION ALL SELECT 0, '000301', 1, '', NULL, 1, 0, NULL, NULL, 'LIMBK0301', 'Bank-Payroll', 'LIMBK0301', 'Business', 0
  UNION ALL SELECT 0, '000350', 0, '', NULL, 1, 0, NULL, NULL, 'LIMCA0350', 'Petty Cash', 'LIMCA0350', 'Business', 0
  UNION ALL SELECT 0, '000400', 0, '', NULL, 1, 0, NULL, NULL, 'LIMAP0400', 'Trade Payables', 'LIMAP0400', 'Business', 0
  UNION ALL SELECT 0, '000401', 0, '', NULL, 1, 0, NULL, NULL, 'LIMAP0401', 'Accrued Expenses', 'LIMAP0401', 'Business', 0
  UNION ALL SELECT 0, '000402', 0, '', NULL, 1, 0, NULL, NULL, 'LIMTX0402', 'VAT Payable', 'LIMTX0402', 'vat', 0
  UNION ALL SELECT 0, '000403', 0, '', NULL, 1, 0, NULL, NULL, 'LIMTX0403', 'Withholding Tax', 'LIMTX0403', 'Business', 0
  UNION ALL SELECT 0, '000404', 0, '', NULL, 1, 0, NULL, NULL, 'LIMTX0404', 'Payroll Taxes Payable', 'LIMTX0404', 'Business', 0
  UNION ALL SELECT 0, '000600', 0, '', NULL, 1, 0, NULL, NULL, 'LIMLN0600', 'Equip Lease Liability', 'LIMLN0600', 'Business', 0
  UNION ALL SELECT 0, '000700', 0, '', 1, 0, 0, NULL, NULL, 'LIMEQ0700', 'Owner Capital', 'LIMEQ0700', 'Business', 0
  UNION ALL SELECT 0, '000701', 0, '', 1, 0, 0, NULL, NULL, 'LIMEQ0701', 'Retained Earnings', 'LIMEQ0701', 'Business', 0
  UNION ALL SELECT 0, '100000', 0, '', NULL, 1, 1, NULL, NULL, 'LIMRV1000', 'Lab Testing Revenue', 'LIMRV1000', 'vat', 0
  UNION ALL SELECT 0, '100001', 0, '', NULL, 1, 1, NULL, NULL, 'LIMRV1001', 'Sample Collection Rev', 'LIMRV1001', 'vat', 0
  UNION ALL SELECT 0, '100002', 0, '', NULL, 1, 1, NULL, NULL, 'LIMRV1002', 'Consulting Revenue', 'LIMRV1002', 'vat', 0
  UNION ALL SELECT 0, '100003', 0, '', NULL, 1, 1, NULL, NULL, 'LIMRV1003', 'Training Revenue', 'LIMRV1003', 'vat', 0
  UNION ALL SELECT 0, '150000', 0, '', NULL, 1, 1, NULL, NULL, 'LIMCO1500', 'Direct Lab Consumables', 'LIMCO1500', 'vat', 0
  UNION ALL SELECT 0, '150001', 0, '', NULL, 1, 1, NULL, NULL, 'LIMCO1501', 'Sampling Materials Used', 'LIMCO1501', 'vat', 0
  UNION ALL SELECT 0, '150002', 0, '', NULL, 1, 1, NULL, NULL, 'LIMCO1502', 'QC Standards Consumed', 'LIMCO1502', 'vat', 0
  UNION ALL SELECT 0, '190000', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1900', 'Salaries and Wages', 'LIMEX1900', 'Business', 0
  UNION ALL SELECT 0, '190001', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1901', 'Lab Overtime', 'LIMEX1901', 'Business', 0
  UNION ALL SELECT 0, '190002', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1902', 'Equip Calibration Maint', 'LIMEX1902', 'Business', 0
  UNION ALL SELECT 0, '190003', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1903', 'Accreditation Fees', 'LIMEX1903', 'Business', 0
  UNION ALL SELECT 0, '190004', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1904', 'LIMS Software Subscr', 'LIMEX1904', 'Business', 0
  UNION ALL SELECT 0, '190005', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1905', 'Courier and Transport', 'LIMEX1905', 'Business', 0
  UNION ALL SELECT 0, '190006', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1906', 'Utilities-Water Power', 'LIMEX1906', 'Business', 0
  UNION ALL SELECT 0, '190007', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1907', 'Rent and Rates', 'LIMEX1907', 'Business', 0
  UNION ALL SELECT 0, '190008', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1908', 'Repairs and Maint', 'LIMEX1908', 'Business', 0
  UNION ALL SELECT 0, '190009', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1909', 'PPE and Safety', 'LIMEX1909', 'Business', 0
  UNION ALL SELECT 0, '190010', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1910', 'Cleaning and Disposal', 'LIMEX1910', 'Business', 0
  UNION ALL SELECT 0, '190011', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1911', 'Marketing', 'LIMEX1911', 'Business', 0
  UNION ALL SELECT 0, '190012', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1912', 'Insurance Expense', 'LIMEX1912', 'Business', 0
  UNION ALL SELECT 0, '190013', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1913', 'Depreciation Expense', 'LIMEX1913', 'Business', 0
  UNION ALL SELECT 0, '190014', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1914', 'Bank Charges', 'LIMEX1914', 'Business', 0
  UNION ALL SELECT 0, '190015', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1915', 'Prof Fees and Audit', 'LIMEX1915', 'Business', 0
  UNION ALL SELECT 0, '190016', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1916', 'Inventory Variance', 'LIMEX1916', 'Business', 0
  UNION ALL SELECT 0, '190017', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1917', 'Lab Prep Expense', 'LIMEX1917', 'Business', 0
  UNION ALL SELECT 0, '190018', 0, '', NULL, 1, 1, NULL, NULL, 'LIMEX1918', 'Stock Write-Off', 'LIMEX1918', 'Business', 0
) AS src
LEFT JOIN `acct` a ON a.`accno` = src.`accno`
WHERE a.`accno` IS NULL;

INSERT INTO `inventorypostinggroup` (
  `code`, `defaultgl_sales`, `defaultgl_purch`, `defaultgl_vat`, `balancesheet`,
  `vatcategory`, `wip`, `stockvariance`, `productionexpense`, `CostOfSales`, `spoilage`
)
SELECT
  src.`code`, src.`defaultgl_sales`, src.`defaultgl_purch`, src.`defaultgl_vat`, src.`balancesheet`,
  src.`vatcategory`, src.`wip`, src.`stockvariance`, src.`productionexpense`, src.`CostOfSales`, src.`spoilage`
FROM (
  SELECT 'default' AS `code`, 'LIMRV1000' AS `defaultgl_sales`, 'LIMCO1500' AS `defaultgl_purch`, 'LIMTX0402' AS `defaultgl_vat`, 'LIMIN0100' AS `balancesheet`, 'V' AS `vatcategory`, 'LIMIN0103' AS `wip`, 'LIMEX1916' AS `stockvariance`, 'LIMEX1917' AS `productionexpense`, 'LIMCO1500' AS `CostOfSales`, 'LIMEX1918' AS `spoilage`
  UNION ALL
  SELECT 'novat', 'LIMRV1000', 'LIMCO1500', 'LIMTX0402', 'LIMIN0100', 'x', 'LIMIN0103', 'LIMEX1916', 'LIMEX1917', 'LIMCO1500', 'LIMEX1918'
) AS src
LEFT JOIN `inventorypostinggroup` ipg ON ipg.`code` = src.`code`
WHERE ipg.`code` IS NULL;

INSERT INTO `stockmaster` (
  `isstock`, `barcode`, `itemcode`, `descrip`, `postinggroup`, `averagestock`,
  `partperunit`, `reorderlevel`, `eoq`, `sellingprice`, `category`, `units`,
  `subunits`, `container`, `nextserialno`, `inactive`, `isstock_1`, `isstock_2`,
  `isstock_3`, `isstock_4`, `isstock_5`, `isstock_6`, `production`
)
SELECT
  src.`isstock`, src.`barcode`, src.`itemcode`, src.`descrip`, src.`postinggroup`, src.`averagestock`,
  src.`partperunit`, src.`reorderlevel`, src.`eoq`, src.`sellingprice`, src.`category`, src.`units`,
  src.`subunits`, src.`container`, src.`nextserialno`, src.`inactive`, src.`isstock_1`, src.`isstock_2`,
  src.`isstock_3`, src.`isstock_4`, src.`isstock_5`, src.`isstock_6`, src.`production`
FROM (
  SELECT 0 AS `isstock`, NULL AS `barcode`, 'LIM-CH-001' AS `itemcode`, 'HPLC Grade Methanol' AS `descrip`, 'default' AS `postinggroup`, 18.50 AS `averagestock`, NULL AS `partperunit`, 20 AS `reorderlevel`, 40 AS `eoq`, 0.00 AS `sellingprice`, 'SOLV' AS `category`, 'LTR' AS `units`, NULL AS `subunits`, NULL AS `container`, 1 AS `nextserialno`, 0 AS `inactive`, 0 AS `isstock_1`, 1 AS `isstock_2`, 0 AS `isstock_3`, 0 AS `isstock_4`, 0 AS `isstock_5`, 0 AS `isstock_6`, NULL AS `production`
  UNION ALL SELECT 0, NULL, 'LIM-CH-002', 'HPLC Grade Acetonitrile', 'default', 22.75, NULL, 20, 40, 0.00, 'SOLV', 'LTR', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-CH-003', 'Nitric Acid 65%', 'default', 15.20, NULL, 10, 20, 0.00, 'ACID', 'LTR', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-CH-004', 'Hydrochloric Acid 37%', 'default', 10.80, NULL, 10, 20, 0.00, 'ACID', 'LTR', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-CH-005', 'Sodium Hydroxide Pellets', 'default', 8.90, NULL, 10, 25, 0.00, 'BASE', 'KG', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-CH-006', 'Potassium Hydroxide Pel', 'default', 11.40, NULL, 10, 20, 0.00, 'BASE', 'KG', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-BF-001', 'Buffer Solution pH 4.00', 'default', 6.50, NULL, 6, 12, 0.00, 'BUFFR', 'BTL', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-BF-002', 'Buffer Solution pH 7.00', 'default', 6.50, NULL, 6, 12, 0.00, 'BUFFR', 'BTL', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-BF-003', 'Buffer Solution pH 10.00', 'default', 6.80, NULL, 6, 12, 0.00, 'BUFFR', 'BTL', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-QC-001', 'Conductivity Std 1413uS', 'default', 14.25, NULL, 4, 8, 0.00, 'QCSTD', 'BTL', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-QC-002', 'Turbidity Standard Set', 'default', 28.00, NULL, 2, 4, 0.00, 'QCSTD', 'BTL', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-QC-003', 'CRM Multi-Element Std', 'default', 48.00, NULL, 2, 4, 0.00, 'QCSTD', 'BTL', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-FL-001', 'Syringe Filters 0.45um', 'default', 12.60, NULL, 5, 10, 0.00, 'FILTR', 'BX', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-FL-002', 'Membrane Filters 47mm', 'default', 19.80, NULL, 4, 8, 0.00, 'FILTR', 'BX', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-GL-001', 'Glass Vials 20ml', 'default', 9.40, NULL, 10, 20, 0.00, 'GLASS', 'PK', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-GL-002', 'Volumetric Flask 100ml', 'default', 7.25, NULL, 6, 12, 0.00, 'GLASS', 'PCS', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-SM-001', 'Sample Bottles 500ml', 'default', 16.00, NULL, 8, 16, 0.00, 'SAMP', 'PK', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL
  UNION ALL SELECT 0, NULL, 'LIM-SM-002', 'Chain Custody Forms', 'default', 4.20, NULL, 5, 10, 0.00, 'LABEL', 'PK', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-SM-003', 'Label Roll 50x25mm', 'default', 3.95, NULL, 10, 20, 0.00, 'LABEL', 'RL', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-SM-004', 'Cooler Ice Packs', 'default', 2.75, NULL, 20, 40, 0.00, 'SAMP', 'PCS', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-SM-005', 'Sample Cooler Box', 'default', 18.00, NULL, 4, 8, 0.00, 'SAMP', 'PCS', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL
  UNION ALL SELECT 0, NULL, 'LIM-CN-001', 'Micropipette Tips 1ml', 'default', 7.30, NULL, 8, 16, 0.00, 'CONS', 'BX', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-CN-002', 'Centrifuge Tubes 50ml', 'default', 6.95, NULL, 8, 16, 0.00, 'CONS', 'PK', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-PPE-01', 'Nitrile Gloves Medium', 'default', 5.60, NULL, 12, 24, 0.00, 'PPE', 'BX', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-PPE-02', 'Disposable Face Masks', 'default', 4.10, NULL, 10, 20, 0.00, 'PPE', 'BX', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-WT-001', 'Distilled Water', 'default', 1.25, NULL, 50, 100, 0.00, 'WATER', 'LTR', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
  UNION ALL SELECT 0, NULL, 'LIM-CL-001', 'Lab Detergent', 'default', 4.85, NULL, 6, 12, 0.00, 'CLEAN', 'LTR', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 0, NULL
) AS src
LEFT JOIN `stockmaster` sm ON sm.`itemcode` = src.`itemcode`
WHERE sm.`itemcode` IS NULL;
