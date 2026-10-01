
DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

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
) ENGINE=InnoDB  CHARSET=utf8mb3 COLLATE=utf8_general_ci;

-- Main Menu: Sample Management
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Sample Management', 'fas fa-vial', NULL, NULL, 0);  
-- Submenus under Sample Management
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Register a New Sample', NULL, 'sampleregistration.php', 1, 2),-- done
('Schedule Samples', NULL, 'Schedule_Sample.php', 1, 2),-- done
('Receives Sample By department', NULL, 'Sample_Received.php', 1, 3),-- done
('Microbiological Samples Tests', NULL, 'labresults_biological.php', 1, 3),
('Chemical Samples Tests', NULL, 'labresults_chemical.php', 1, 2);

-- Main Menu: Environmental Monitoring
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Environmental Monitoring', 'fas fa-seedling', NULL, NULL, 0), -- ID 2
('Record Enviromental Factors', NULL, 'recordEnvFactors.php', 7, 3);-- done

-- Main Menu: Chain of Custody
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Chain of Custody', 'fas fa-link', NULL, NULL, 0); -- ID 3

-- Submenus under Chain of Custody
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Log Custody Transfers', NULL, 'chainOfCustody.php', 9, 4),
('Dispose of Samples', NULL, 'disposeSamples.php',9, 3);

-- Main Menu: Quality Assurance
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Quality Assurance', 'fas fa-balance-scale', NULL, NULL, 0); -- ID 12

-- Submenus under Quality Assurance
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Perform QA Checks', NULL, 'qa-checks.php', 4, 2),
('View QA Reports', NULL, 'qa-reports.php', 4, 2);

-- Main Menu: Reports
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Reports', 'fas fa-chart-pie', NULL, NULL, 0); -- ID 5

-- Main Menu: Alerts and Notifications
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Alerts and Notifications', 'fas fa-sms',NULL, NULL, 0);

-- Main Menu: Blockchain Ledger
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Blockchain Ledger', 'fas fa-lock',NULL, NULL, 0);

-- Main Menu: Predictive Analysis
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Predictive Analysis', 'fas fa-chart-line',NULL, NULL,0);

-- Main Menu: Dashboard
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Admin', 'fas fa-user-circle', NULL, NULL, 0);

INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Assign Roles', 'fas fa-user-circle', 'rolemanager.php', 19, 0),
('Assign Departments', 'fas fa-user-circle', 'editusers.php',19, 0),
('Maintain Sample Standards','fas fa-vial', 'teststandards.php', 19, 3),-- done
('Maintain Sample Parameters','fas fa-vial', 'parameters.php', 19, 3);-- done
-- add menus

INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('SOPs',
'fas fa-balance-scale',
'sop.php',
12,
0);

INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Predictive Calibration',
'fas fa-chart-line',
'predictivecalibration.php',
18,
0);

INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Control Samples',
'fas fa-chart-line',
'caliberationinput.php',
18,
0);
----04012025
INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Company Label',
'fas fa-user-circle',
'companymaster.php',
19,
0);


INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Review Test Results',
'fas fa-user-circle',
'reviewtestresults.php',
1,
0);

---05012025
ALTER TABLE `test_results` 
drop COLUMN `StandardLimit_Result` ;
ALTER TABLE `test_results` 
add column `approvedby` INT NULL DEFAULT NULL ;
ALTER TABLE `test_results` 
add COLUMN  `reviewedby` INT NULL DEFAULT NULL ;
ALTER TABLE `test_results` 
add COLUMN  `alteredby` INT NULL DEFAULT NULL ;

--07012025

INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Edit Sample Registration',
'fas fa-user-circle',
'editsampleregistration.php',
1,
0);


CREATE TABLE transaction_metadata (
    metadata_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(255),
    record_id INT,
    block_id INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

drop table audit_log;
CREATE TABLE audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50),
    document_no VARCHAR(255),
    hash_value CHAR(64),
    user_id INT,
    status VARCHAR(20),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--09012025

INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Quick Lab Report',
'fas fa-chart-pie',
'labrestreportmanager.php',
15,
0);


INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Data Verification',
'fas fa-lock',
'blockchain.php',
17,
0);

ALTER TABLE `blockchain_ledger` 
ADD COLUMN `userid` INT  NULL AFTER `status`;



INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Email Alert Templates',
'fas fa-sms',
'emailtemplate.php',
16,
0);

INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Alert logs',
'fas fa-sms',
'eventreport.php',
16,
0);


INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Import From Excel',
'fas fa-user-circle',
'import_excel.php',
19,
0);

--16012025


INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('System Configuration',
'fas fa-user-circle',
'updatesystemconfig.php',
19,
0);



INSERT INTO `menu_items`
(`title`,
`icon`,
`url`,
`parent_id`,
`security_id`)
VALUES
('Certificate of Assesment',
'fas fa-chart-pie',
'CertificateOfAssesment.php',
15,
0);

----- 2025 july

INSERT INTO `menu_items` (`title`, `icon`, `url`, `parent_id`, `security_id`) 
VALUES ('Global Parameters', 'fas fa-user-circle', 'Baseparameters.php', '19', '0');


INSERT INTO `menu_items` (`title`, `icon`, `url`, `parent_id`, `security_id`) 
VALUES ('Parameter Matrix', 'fas fa-user-circle', 'parametergroups.php', '19', '0');


INSERT INTO `menu_items` (`title`, `icon`, `url`, `parent_id`, `security_id`) 
VALUES ('Matrix Config', 'fas fa-user-circle', 'MatrixConfig.php', '19', '0');
-- 18 /08/2025

INSERT INTO `menu_items` (`title`, `icon`, `url`, `parent_id`, `security_id`) 
VALUES ('Approve Chemical', 'fas fa-user-circle', 'approvetestresultschemical.php', '1', '0');

INSERT INTO `menu_items` (`title`, `icon`, `url`, `parent_id`, `security_id`) 
VALUES ('Approve MicroBiological', 'fas fa-user-circle', 'approvetestresultsbio.php', '1', '0');


INSERT INTO `menu_items` (`id`, `title`, `icon`, `security_id`) VALUES ('50', 'Supervisor', 'fas fa-vial', '0');
UPDATE `menu_items` SET `parent_id` = '50' WHERE (`id` = '47');
UPDATE `menu_items` SET `parent_id` = '50' WHERE (`id` = '46');
UPDATE `menu_items` SET `parent_id` = '50' WHERE (`id` = '45');
UPDATE `menu_items` SET `parent_id` = '50' WHERE (`id` = '23');
UPDATE `menu_items` SET `parent_id` = '50' WHERE (`id` = '22');
UPDATE `menu_items` SET `parent_id` = '50' WHERE (`id` = '35');


INSERT INTO `menu_items` (`id`, `title`, `icon`, `security_id`) VALUES ('51', 'Reception', 'fas fa-user-circle', '0');
UPDATE `menu_items` SET `parent_id` = '51' WHERE (`id` = '41');
UPDATE `menu_items` SET `parent_id` = '51' WHERE (`id` = '40');

/*2026*/


INSERT INTO `menu_items` (`title`, `icon`, `url`, `parent_id`, `security_id`) 
VALUES ('Sample Summary by date', 'fas fa-chart-pie', 'SampleReportsbydate.php', '15', '0');


/**2026/april*/
INSERT INTO `menu_items` (`title`, `icon`, `url`, `parent_id`, `security_id`) 
VALUES ('Sales Quotation', 'fas fa-chart-pie', 'QuoteDocument.php', '1', '0');

INSERT INTO `menu_items` (`title`, `icon`, `url`, `parent_id`, `security_id`) 
VALUES ('Reprint Sales Quotation', 'fas fa-chart-pie', 'Printlastquotes.php', '1', '0');






