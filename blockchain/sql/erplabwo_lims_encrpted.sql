-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 03, 2026 at 11:59 AM
-- Server version: 11.8.3-MariaDB
-- PHP Version: 8.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `erplabwo_lims_encrpted`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `document_no` varchar(255) DEFAULT NULL,
  `hash_value` char(64) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `baseparameters`
--

CREATE TABLE `baseparameters` (
  `ParameterID` int(11) NOT NULL,
  `ParameterName` varchar(255) NOT NULL,
  `NeutralityID` int(11) DEFAULT NULL,
  `TdsID` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blockchain_ledger`
--

CREATE TABLE `blockchain_ledger` (
  `block_id` int(11) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `previous_hash` char(64) NOT NULL,
  `current_hash` char(64) NOT NULL,
  `digital_signature` varchar(512) NOT NULL,
  `encrypted_data` text DEFAULT NULL,
  `previous_version_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `status` enum('active','superseded') DEFAULT 'active',
  `userid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Triggers `blockchain_ledger`
--
DELIMITER $$
CREATE TRIGGER `prevent_delete` BEFORE DELETE ON `blockchain_ledger` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'Deletes are not allowed on blockchain records';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `prevent_update` BEFORE UPDATE ON `blockchain_ledger` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'Updates are not allowed on blockchain records';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `chain_of_custody`
--

CREATE TABLE `chain_of_custody` (
  `custody_id` int(11) NOT NULL,
  `SampleID` varchar(20) NOT NULL,
  `handler_name` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_master`
--

CREATE TABLE `company_master` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `address1` text DEFAULT NULL,
  `address2` text DEFAULT NULL,
  `address3` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `technician` int(11) DEFAULT NULL,
  `technician2` int(11) DEFAULT NULL,
  `authorisation` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `confname` varchar(35) NOT NULL,
  `confvalue` longtext NOT NULL,
  `type` enum('number','string','path','text','date') DEFAULT 'string'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Triggers `config`
--
DELIMITER $$
CREATE TRIGGER `prevent_delete_config` BEFORE DELETE ON `config` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'Deletes are not allowed on config records';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `controlsampleresults`
--

CREATE TABLE `controlsampleresults` (
  `result_id` int(11) NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `sample_name` varchar(255) DEFAULT NULL,
  `known_value` decimal(10,2) DEFAULT NULL,
  `measured_value` decimal(10,2) DEFAULT NULL,
  `deviation` decimal(10,2) DEFAULT NULL,
  `result_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debtors`
--

CREATE TABLE `debtors` (
  `type` char(1) DEFAULT NULL,
  `istaff` int(11) DEFAULT NULL,
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
  `age1` bigint(20) DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` bigint(20) NOT NULL,
  `islocal` tinyint(1) DEFAULT NULL,
  `username` char(20) DEFAULT NULL,
  `customerposting` varchar(20) DEFAULT NULL,
  `salesman` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `environmental_parameters`
--

CREATE TABLE `environmental_parameters` (
  `param_id` int(11) NOT NULL,
  `temperature` float NOT NULL,
  `humidity` float NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) DEFAULT NULL,
  `last_calibration` date DEFAULT NULL,
  `predicted_calibration` date DEFAULT NULL,
  `deviation_trend` varchar(50) DEFAULT NULL,
  `usage_hours` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipmentusage`
--

CREATE TABLE `equipmentusage` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `usage_start_time` datetime DEFAULT NULL,
  `usage_end_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_logs`
--

CREATE TABLE `event_logs` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `status` enum('success','failure') DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `triggered_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `folder_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

CREATE TABLE `machines` (
  `machine_id` int(11) NOT NULL,
  `machine_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `security_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parametermatrix`
--

CREATE TABLE `parametermatrix` (
  `ParameterID` int(11) NOT NULL,
  `ParameterName` varchar(255) NOT NULL,
  `ParentID` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `role_description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_security`
--

CREATE TABLE `role_security` (
  `role_id` int(11) NOT NULL,
  `security_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `samples_received`
--

CREATE TABLE `samples_received` (
  `id` int(11) NOT NULL,
  `sample_id` varchar(20) NOT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `assigned_department` enum('chemical','biological','admin','guest','microbiological') NOT NULL,
  `condition` enum('intact','damaged','other') NOT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_custody`
--

CREATE TABLE `sample_custody` (
  `CustodyID` int(11) NOT NULL,
  `SampleID` varchar(20) DEFAULT NULL,
  `HandlerName` varchar(255) DEFAULT NULL,
  `Action` varchar(255) DEFAULT NULL,
  `DateTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `Location` varchar(255) DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_header`
--

CREATE TABLE `sample_header` (
  `HeaderID` int(11) NOT NULL,
  `Date` datetime NOT NULL,
  `DocumentNo` varchar(50) NOT NULL,
  `CustomerName` varchar(255) NOT NULL,
  `CustomerID` varchar(20) NOT NULL,
  `SampledBy` varchar(100) NOT NULL,
  `SamplingMethod` varchar(255) NOT NULL,
  `SamplingDate` datetime NOT NULL,
  `OrderNo` varchar(50) NOT NULL,
  `ScopeOfWork` text DEFAULT NULL,
  `User_name` varchar(255) NOT NULL,
  `previous_version_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_schedule`
--

CREATE TABLE `sample_schedule` (
  `ScheduleID` int(11) NOT NULL,
  `SampleID` varchar(20) NOT NULL,
  `ScheduleDate` date NOT NULL,
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_statuses`
--

CREATE TABLE `sample_statuses` (
  `StatusID` int(11) NOT NULL,
  `StatusOrder` int(11) NOT NULL,
  `StatusName` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `ColorCode` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_tests`
--

CREATE TABLE `sample_tests` (
  `TestID` int(11) NOT NULL,
  `HeaderID` int(11) NOT NULL,
  `SampleID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `StandardID` int(11) NOT NULL,
  `SampleFileKey` varchar(255) DEFAULT 'icons8-no-image-100.png',
  `SampleFee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `SKU` varchar(100) DEFAULT NULL,
  `BatchNo` varchar(50) DEFAULT NULL,
  `BatchSize` int(11) DEFAULT NULL,
  `ManufactureDate` datetime DEFAULT NULL,
  `ExpDate` datetime DEFAULT NULL,
  `ExternalSample` varchar(255) DEFAULT NULL,
  `MRL_Result` decimal(10,4) DEFAULT NULL,
  `StandardLimit_Result` decimal(10,4) DEFAULT NULL,
  `ResultStatus` enum('ND','Absent','Detected','Below Limit','Detected Range','Trace','Above Limit','Inconclusive','Error','Invalid') DEFAULT NULL,
  `RangeResult` varchar(255) DEFAULT NULL,
  `User_name` varchar(255) NOT NULL,
  `previous_version_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `disposal_reason` text DEFAULT NULL,
  `disposal_timestamp` datetime DEFAULT NULL,
  `disposed_by` int(11) DEFAULT NULL,
  `environmental_id` int(11) DEFAULT NULL,
  `datetestended` datetime DEFAULT NULL,
  `BaseID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sops`
--

CREATE TABLE `sops` (
  `sop_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `document_url` text DEFAULT NULL,
  `version_number` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sop_access_log`
--

CREATE TABLE `sop_access_log` (
  `log_id` int(11) NOT NULL,
  `sop_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `standard_methods`
--

CREATE TABLE `standard_methods` (
  `MethodID` int(11) NOT NULL,
  `standard_method` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `standard_parameter_matrix_config`
--

CREATE TABLE `standard_parameter_matrix_config` (
  `ConfigID` int(11) NOT NULL,
  `ParameterID` int(11) NOT NULL,
  `MatrixID` int(11) NOT NULL,
  `IsDefault` tinyint(1) DEFAULT 0,
  `Notes` text DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subcontractors`
--

CREATE TABLE `subcontractors` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `address` varchar(50) DEFAULT NULL,
  `address2` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `alt_contact` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `systypes`
--

CREATE TABLE `systypes` (
  `typeid` smallint(6) NOT NULL DEFAULT 0,
  `typename` char(50) NOT NULL,
  `typeno` int(11) NOT NULL DEFAULT 1,
  `prefix` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `testparameters`
--

CREATE TABLE `testparameters` (
  `ParameterID` int(11) NOT NULL,
  `ParameterName` varchar(255) NOT NULL,
  `StandardID` int(11) NOT NULL,
  `Limits` varchar(255) DEFAULT NULL,
  `MinLimit` decimal(10,2) DEFAULT NULL,
  `MaxLimit` decimal(10,2) DEFAULT NULL,
  `Method` varchar(255) DEFAULT NULL,
  `Vital` tinyint(1) DEFAULT 0,
  `Category` enum('microbiological','chemical') DEFAULT 'chemical',
  `MRL` decimal(10,2) DEFAULT NULL,
  `MRLUnit` varchar(50) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `UnitOfMeasure` varchar(50) DEFAULT NULL,
  `BaseID` int(11) DEFAULT NULL,
  `matrixID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teststandards`
--

CREATE TABLE `teststandards` (
  `StandardID` int(11) NOT NULL,
  `StandardCode` varchar(100) DEFAULT NULL,
  `StandardName` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `ApplicableRegulation` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `sm` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_assignments`
--

CREATE TABLE `test_assignments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `resultsID` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subcontractor` int(11) DEFAULT NULL,
  `emailsent` timestamp NULL DEFAULT NULL,
  `category` enum('chemical','admin','microbiological') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_results`
--

CREATE TABLE `test_results` (
  `resultsID` int(11) NOT NULL,
  `TestID` int(11) NOT NULL,
  `HeaderID` int(11) NOT NULL,
  `SampleID` varchar(20) NOT NULL,
  `StandardID` int(11) NOT NULL,
  `ParameterID` int(11) NOT NULL,
  `MRL_Result` varchar(20) DEFAULT NULL,
  `ResultStatus` enum('ND','Absent','Detected','Below Limit','Detected Range','Trace','Above Limit','Inconclusive','Error','Invalid') DEFAULT NULL,
  `RangeResult` varchar(255) DEFAULT NULL,
  `User_name` varchar(255) NOT NULL,
  `StatusID` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approvedby` int(11) DEFAULT NULL,
  `reviewedby` int(11) DEFAULT NULL,
  `alteredby` int(11) DEFAULT NULL,
  `BaseID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_metadata`
--

CREATE TABLE `transaction_metadata` (
  `metadata_id` int(11) NOT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `block_id` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `telephone` varchar(12) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` int(11) DEFAULT 10,
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `full_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department` enum('chemical','microbiological','admin','guest') NOT NULL DEFAULT 'guest',
  `signature_path` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `baseparameters`
--
ALTER TABLE `baseparameters`
  ADD PRIMARY KEY (`ParameterID`),
  ADD UNIQUE KEY `uq_parametername` (`ParameterName`);

--
-- Indexes for table `blockchain_ledger`
--
ALTER TABLE `blockchain_ledger`
  ADD PRIMARY KEY (`block_id`);

--
-- Indexes for table `chain_of_custody`
--
ALTER TABLE `chain_of_custody`
  ADD PRIMARY KEY (`custody_id`),
  ADD KEY `SampleID` (`SampleID`);

--
-- Indexes for table `company_master`
--
ALTER TABLE `company_master`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`confname`);

--
-- Indexes for table `controlsampleresults`
--
ALTER TABLE `controlsampleresults`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `environmental_parameters`
--
ALTER TABLE `environmental_parameters`
  ADD PRIMARY KEY (`param_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `machine_id` (`machine_id`);

--
-- Indexes for table `equipmentusage`
--
ALTER TABLE `equipmentusage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_logs`
--
ALTER TABLE `event_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`machine_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Uniqueurls` (`url`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `parametermatrix`
--
ALTER TABLE `parametermatrix`
  ADD PRIMARY KEY (`ParameterID`),
  ADD UNIQUE KEY `ParameterName` (`ParameterName`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`reset_id`),
  ADD UNIQUE KEY `reset_token` (`reset_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_security`
--
ALTER TABLE `role_security`
  ADD PRIMARY KEY (`role_id`,`security_id`);

--
-- Indexes for table `samples_received`
--
ALTER TABLE `samples_received`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sample_custody`
--
ALTER TABLE `sample_custody`
  ADD PRIMARY KEY (`CustodyID`),
  ADD KEY `fk_sample_id` (`SampleID`);

--
-- Indexes for table `sample_header`
--
ALTER TABLE `sample_header`
  ADD PRIMARY KEY (`HeaderID`);

--
-- Indexes for table `sample_schedule`
--
ALTER TABLE `sample_schedule`
  ADD PRIMARY KEY (`ScheduleID`),
  ADD KEY `SampleID` (`SampleID`);

--
-- Indexes for table `sample_statuses`
--
ALTER TABLE `sample_statuses`
  ADD PRIMARY KEY (`StatusID`),
  ADD UNIQUE KEY `StatusOrder` (`StatusOrder`);

--
-- Indexes for table `sample_tests`
--
ALTER TABLE `sample_tests`
  ADD PRIMARY KEY (`TestID`),
  ADD UNIQUE KEY `uq_sample_tests` (`SampleID`,`StandardID`,`HeaderID`),
  ADD KEY `HeaderID` (`HeaderID`),
  ADD KEY `StandardID` (`StandardID`),
  ADD KEY `SampleID` (`SampleID`),
  ADD KEY `fk_disposed_by_user` (`disposed_by`),
  ADD KEY `idx_environmental_id` (`environmental_id`);

--
-- Indexes for table `sops`
--
ALTER TABLE `sops`
  ADD PRIMARY KEY (`sop_id`);

--
-- Indexes for table `sop_access_log`
--
ALTER TABLE `sop_access_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `sop_id` (`sop_id`);

--
-- Indexes for table `standard_methods`
--
ALTER TABLE `standard_methods`
  ADD PRIMARY KEY (`MethodID`);

--
-- Indexes for table `standard_parameter_matrix_config`
--
ALTER TABLE `standard_parameter_matrix_config`
  ADD PRIMARY KEY (`ConfigID`),
  ADD KEY `fk_spm_param_idx` (`ParameterID`),
  ADD KEY `fk_spm_matrix` (`MatrixID`);

--
-- Indexes for table `subcontractors`
--
ALTER TABLE `subcontractors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `systypes`
--
ALTER TABLE `systypes`
  ADD PRIMARY KEY (`typeid`);

--
-- Indexes for table `testparameters`
--
ALTER TABLE `testparameters`
  ADD PRIMARY KEY (`ParameterID`),
  ADD KEY `StandardID` (`StandardID`),
  ADD KEY `BaseID` (`BaseID`),
  ADD KEY `matrixID` (`matrixID`);

--
-- Indexes for table `teststandards`
--
ALTER TABLE `teststandards`
  ADD PRIMARY KEY (`StandardID`),
  ADD KEY `sm` (`sm`);

--
-- Indexes for table `test_assignments`
--
ALTER TABLE `test_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_test` (`user_id`,`resultsID`);

--
-- Indexes for table `test_results`
--
ALTER TABLE `test_results`
  ADD PRIMARY KEY (`resultsID`),
  ADD UNIQUE KEY `uq_test_results` (`TestID`,`HeaderID`,`SampleID`,`StandardID`,`ParameterID`),
  ADD KEY `HeaderID` (`HeaderID`),
  ADD KEY `ParameterID` (`ParameterID`),
  ADD KEY `StandardID` (`StandardID`),
  ADD KEY `StatusID` (`StatusID`),
  ADD KEY `BaseID` (`BaseID`);

--
-- Indexes for table `transaction_metadata`
--
ALTER TABLE `transaction_metadata`
  ADD PRIMARY KEY (`metadata_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `baseparameters`
--
ALTER TABLE `baseparameters`
  MODIFY `ParameterID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blockchain_ledger`
--
ALTER TABLE `blockchain_ledger`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chain_of_custody`
--
ALTER TABLE `chain_of_custody`
  MODIFY `custody_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_master`
--
ALTER TABLE `company_master`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `controlsampleresults`
--
ALTER TABLE `controlsampleresults`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `environmental_parameters`
--
ALTER TABLE `environmental_parameters`
  MODIFY `param_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipmentusage`
--
ALTER TABLE `equipmentusage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_logs`
--
ALTER TABLE `event_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `machines`
--
ALTER TABLE `machines`
  MODIFY `machine_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parametermatrix`
--
ALTER TABLE `parametermatrix`
  MODIFY `ParameterID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `samples_received`
--
ALTER TABLE `samples_received`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sample_custody`
--
ALTER TABLE `sample_custody`
  MODIFY `CustodyID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sample_header`
--
ALTER TABLE `sample_header`
  MODIFY `HeaderID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sample_schedule`
--
ALTER TABLE `sample_schedule`
  MODIFY `ScheduleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sample_statuses`
--
ALTER TABLE `sample_statuses`
  MODIFY `StatusID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sample_tests`
--
ALTER TABLE `sample_tests`
  MODIFY `TestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sops`
--
ALTER TABLE `sops`
  MODIFY `sop_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sop_access_log`
--
ALTER TABLE `sop_access_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `standard_methods`
--
ALTER TABLE `standard_methods`
  MODIFY `MethodID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `standard_parameter_matrix_config`
--
ALTER TABLE `standard_parameter_matrix_config`
  MODIFY `ConfigID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subcontractors`
--
ALTER TABLE `subcontractors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `testparameters`
--
ALTER TABLE `testparameters`
  MODIFY `ParameterID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teststandards`
--
ALTER TABLE `teststandards`
  MODIFY `StandardID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_assignments`
--
ALTER TABLE `test_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_results`
--
ALTER TABLE `test_results`
  MODIFY `resultsID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction_metadata`
--
ALTER TABLE `transaction_metadata`
  MODIFY `metadata_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chain_of_custody`
--
ALTER TABLE `chain_of_custody`
  ADD CONSTRAINT `chain_of_custody_ibfk_1` FOREIGN KEY (`SampleID`) REFERENCES `sample_tests` (`SampleID`);

--
-- Constraints for table `controlsampleresults`
--
ALTER TABLE `controlsampleresults`
  ADD CONSTRAINT `controlsampleresults_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `machines` (`machine_id`);

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`machine_id`);

--
-- Constraints for table `equipmentusage`
--
ALTER TABLE `equipmentusage`
  ADD CONSTRAINT `equipmentusage_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `machines` (`machine_id`);

--
-- Constraints for table `event_logs`
--
ALTER TABLE `event_logs`
  ADD CONSTRAINT `event_logs_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `folders_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`);

--
-- Constraints for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `password_reset_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `role_security`
--
ALTER TABLE `role_security`
  ADD CONSTRAINT `role_security_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `sample_custody`
--
ALTER TABLE `sample_custody`
  ADD CONSTRAINT `fk_sample_id` FOREIGN KEY (`SampleID`) REFERENCES `sample_tests` (`SampleID`);

--
-- Constraints for table `sample_schedule`
--
ALTER TABLE `sample_schedule`
  ADD CONSTRAINT `sample_schedule_ibfk_1` FOREIGN KEY (`SampleID`) REFERENCES `sample_tests` (`SampleID`);

--
-- Constraints for table `sample_tests`
--
ALTER TABLE `sample_tests`
  ADD CONSTRAINT `fk_disposed_by_user` FOREIGN KEY (`disposed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `sample_tests_ibfk_1` FOREIGN KEY (`HeaderID`) REFERENCES `sample_header` (`HeaderID`),
  ADD CONSTRAINT `sample_tests_ibfk_3` FOREIGN KEY (`StandardID`) REFERENCES `testparameters` (`StandardID`),
  ADD CONSTRAINT `sample_tests_ibfk_5` FOREIGN KEY (`environmental_id`) REFERENCES `environmental_parameters` (`param_id`);

--
-- Constraints for table `sop_access_log`
--
ALTER TABLE `sop_access_log`
  ADD CONSTRAINT `sop_access_log_ibfk_1` FOREIGN KEY (`sop_id`) REFERENCES `sops` (`sop_id`);

--
-- Constraints for table `standard_parameter_matrix_config`
--
ALTER TABLE `standard_parameter_matrix_config`
  ADD CONSTRAINT `fk_spm_matrix` FOREIGN KEY (`MatrixID`) REFERENCES `parametermatrix` (`ParameterID`) ON DELETE CASCADE;

--
-- Constraints for table `testparameters`
--
ALTER TABLE `testparameters`
  ADD CONSTRAINT `testparameters_ibfk_1` FOREIGN KEY (`StandardID`) REFERENCES `teststandards` (`StandardID`),
  ADD CONSTRAINT `testparameters_ibfk_2` FOREIGN KEY (`BaseID`) REFERENCES `baseparameters` (`ParameterID`),
  ADD CONSTRAINT `testparameters_ibfk_3` FOREIGN KEY (`matrixID`) REFERENCES `parametermatrix` (`ParameterID`);

--
-- Constraints for table `teststandards`
--
ALTER TABLE `teststandards`
  ADD CONSTRAINT `teststandards_ibfk_1` FOREIGN KEY (`sm`) REFERENCES `standard_methods` (`MethodID`);

--
-- Constraints for table `test_results`
--
ALTER TABLE `test_results`
  ADD CONSTRAINT `test_results_ibfk_1` FOREIGN KEY (`TestID`) REFERENCES `sample_tests` (`TestID`),
  ADD CONSTRAINT `test_results_ibfk_2` FOREIGN KEY (`HeaderID`) REFERENCES `sample_header` (`HeaderID`),
  ADD CONSTRAINT `test_results_ibfk_3` FOREIGN KEY (`ParameterID`) REFERENCES `testparameters` (`ParameterID`),
  ADD CONSTRAINT `test_results_ibfk_4` FOREIGN KEY (`StandardID`) REFERENCES `testparameters` (`StandardID`),
  ADD CONSTRAINT `test_results_ibfk_5` FOREIGN KEY (`StatusID`) REFERENCES `sample_statuses` (`StatusID`),
  ADD CONSTRAINT `test_results_ibfk_6` FOREIGN KEY (`BaseID`) REFERENCES `baseparameters` (`ParameterID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
