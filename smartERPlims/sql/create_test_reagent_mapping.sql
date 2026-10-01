CREATE TABLE IF NOT EXISTS `test_reagent_mapping` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `test_itemcode` VARCHAR(20) NOT NULL,
  `reagent_itemcode` VARCHAR(20) NOT NULL,
  `quantity` DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_test` (`test_itemcode`),
  INDEX `idx_reagent` (`reagent_itemcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
