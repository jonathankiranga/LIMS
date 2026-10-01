CREATE TABLE IF NOT EXISTS `test_spreadsheet` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `test_itemcode` VARCHAR(20) NOT NULL,
  `sheet_data` LONGTEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_test` (`test_itemcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spreadsheet_permissions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `test_itemcode` VARCHAR(20) NOT NULL,
  `user_id` VARCHAR(20) NOT NULL,
  `can_write` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_test_user` (`test_itemcode`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
