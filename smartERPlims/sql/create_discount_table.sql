CREATE TABLE IF NOT EXISTS `discounttable` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `categoryid` CHAR(6) NOT NULL,
  `itemcode` CHAR(20) NOT NULL,
  `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_category_item` (`categoryid`, `itemcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
