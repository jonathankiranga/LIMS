-- Audit trail for parameter changes
CREATE TABLE IF NOT EXISTS `parameter_audit_log` (
  `audit_id` INT NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(50) NOT NULL,
  `record_id` INT NOT NULL,
  `action` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
  `field_changed` VARCHAR(50) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `script_name` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  INDEX `idx_record` (`table_name`, `record_id`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;