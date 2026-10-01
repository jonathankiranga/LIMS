-- ============================================================
-- Cost Allocation Module — Migration Script
-- ============================================================
-- Run this ONCE to set up the cost allocation module.
-- Adds UNIQUE keys, creates tables, seeds rules (where GL accounts exist), registers scripts.
-- ============================================================

-- ============================================================
-- STEP 1: Add UNIQUE keys to existing tables (required for FKs)
-- ============================================================

-- stockmaster.itemcode must be unique for FK references
SET @existing_uq = (SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'stockmaster' AND index_name = 'uq_itemcode');
SET @sql := IF(@existing_uq = 0,
  'ALTER TABLE stockmaster ADD UNIQUE KEY uq_itemcode (itemcode)',
  'SELECT "uq_itemcode already exists" AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- productionrates needs a primary key on LabourID
SET @existing_pk = (SELECT COUNT(*) FROM information_schema.table_constraints
  WHERE table_schema = DATABASE() AND table_name = 'productionrates' AND constraint_type = 'PRIMARY KEY');
SET @sql := IF(@existing_pk = 0,
  'ALTER TABLE productionrates ADD PRIMARY KEY (LabourID)',
  'SELECT "productionrates PK already exists" AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- STEP 2: Create cost allocation tables
-- ============================================================

-- 2a. Allocation rules
CREATE TABLE IF NOT EXISTS `cost_allocation_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gl_account` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allocation_driver` enum('labor_hours','equipment_hours','reagent_cost','direct','fixed_per_test') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'labor_hours',
  `cost_component` enum('material','labor','equipment','overhead') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_car_gl_account` (`gl_account`),
  CONSTRAINT `FK_CAR_acct` FOREIGN KEY (`gl_account`) REFERENCES `acct` (`accno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2b. Test to labor cost mapping (amount per test, not role-based)
CREATE TABLE IF NOT EXISTS `test_labor_mapping` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_itemcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Labor cost per test instance',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_test` (`test_itemcode`),
  CONSTRAINT `FK_TLM_stockmaster` FOREIGN KEY (`test_itemcode`) REFERENCES `stockmaster` (`itemcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2c. Test to equipment mapping
CREATE TABLE IF NOT EXISTS `test_equipment_mapping` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_itemcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assetid` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_test_asset` (`test_itemcode`, `assetid`),
  CONSTRAINT `FK_TEM_stockmaster` FOREIGN KEY (`test_itemcode`) REFERENCES `stockmaster` (`itemcode`),
  CONSTRAINT `FK_TEM_fixedassets` FOREIGN KEY (`assetid`) REFERENCES `fixedassets` (`assetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2d. Allocation periods
CREATE TABLE IF NOT EXISTS `cost_allocation_periods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `total_labor_hours` decimal(10,2) DEFAULT '0',
  `total_equipment_hours` decimal(10,2) DEFAULT '0',
  `total_reagent_cost` decimal(12,2) DEFAULT '0',
  `tests_completed` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2e. Allocation results
CREATE TABLE IF NOT EXISTS `cost_allocation_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `period_id` int NOT NULL,
  `test_itemcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_descrip` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_component` enum('material','labor','equipment','overhead') COLLATE utf8mb4_unicode_ci NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL DEFAULT '0',
  `basis_value` decimal(10,2) DEFAULT NULL,
  `basis_total` decimal(12,2) DEFAULT NULL,
  `gl_account` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gl_account_desc` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tests_count` int DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_car_period` (`period_id`),
  KEY `idx_car_test` (`test_itemcode`),
  KEY `idx_car_gl` (`gl_account`),
  CONSTRAINT `FK_CAR_period` FOREIGN KEY (`period_id`) REFERENCES `cost_allocation_periods` (`id`),
  CONSTRAINT `FK_CAR_stockmaster` FOREIGN KEY (`test_itemcode`) REFERENCES `stockmaster` (`itemcode`),
  CONSTRAINT `FK_CAR_gl_acct` FOREIGN KEY (`gl_account`) REFERENCES `acct` (`accno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- STEP 3: Seed allocation rules (only where GL account exists)
-- ============================================================

INSERT INTO `cost_allocation_rules` (`gl_account`, `allocation_driver`, `cost_component`, `description`, `is_active`)
SELECT src.* FROM (
  SELECT 'LIMEX1900' AS gl_account, 'labor_hours'     AS allocation_driver, 'labor'     AS cost_component, 'Salaries and Wages'              AS description, 1 AS is_active UNION ALL
  SELECT 'LIMEX1901', 'labor_hours',     'labor',     'Lab Overtime',                    1 UNION ALL
  SELECT 'LIMEX1902', 'equipment_hours', 'equipment', 'Equip Calibration & Maintenance',  1 UNION ALL
  SELECT 'LIMEX1913', 'equipment_hours', 'equipment', 'Depreciation Expense',             1 UNION ALL
  SELECT 'LIMEX1906', 'labor_hours',     'overhead',  'Utilities-Water Power',            1 UNION ALL
  SELECT 'LIMEX1907', 'labor_hours',     'overhead',  'Rent and Rates',                   1 UNION ALL
  SELECT 'LIMEX1908', 'equipment_hours', 'overhead',  'Repairs and Maint',                1 UNION ALL
  SELECT 'LIMEX1917', 'labor_hours',     'overhead',  'Lab Prep Expense',                 1 UNION ALL
  SELECT 'LIMEX1904', 'fixed_per_test',  'overhead',  'LIMS Software Subscription',       1 UNION ALL
  SELECT 'LIMEX1912', 'labor_hours',     'overhead',  'Insurance Expense',                1
) src
LEFT JOIN `cost_allocation_rules` car ON car.`gl_account` = src.`gl_account`
LEFT JOIN `acct` a ON a.`accno` = src.`gl_account`
WHERE car.`id` IS NULL AND a.`accno` IS NOT NULL;


-- ============================================================
-- STEP 4: Register pages in scripts table
-- ============================================================

INSERT IGNORE INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('CostAllocation.php',          0, 'Cost Allocation Rules and Run'),
('CostAccountingReport.php',    0, 'Cost Accounting Report Spreadsheet'),
('TestLaborMapping.php',        0, 'Test to Labor Role Mapping'),
('TestEquipmentMapping.php',    0, 'Test to Equipment Mapping');
