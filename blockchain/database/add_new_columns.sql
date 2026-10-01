-- MySQL script to add new columns to sample_tests table
-- Run this script in your database

-- Add new columns to sample_tests table
ALTER TABLE `sample_tests` 
ADD COLUMN `sample_name` VARCHAR(255) NULL AFTER `ExternalSample`,
ADD COLUMN `sample_method` VARCHAR(255) NULL AFTER `sample_name`,
ADD COLUMN `condition_of_sample` VARCHAR(100) NULL AFTER `sample_method`,
ADD COLUMN `chilled_date_of_expiry` DATE NULL AFTER `ExpDate`,
ADD COLUMN `frozen_date_of_expiry` DATE NULL AFTER `chilled_date_of_expiry`;

-- Optional: Add indexes for new columns if frequently queried
ALTER TABLE `sample_tests` 
ADD INDEX `idx_sample_name` (`sample_name`),
ADD INDEX `idx_condition_of_sample` (`condition_of_sample`),
ADD INDEX `idx_sample_source` (`ExternalSample`);

-- Sample source quick add table (optional - for autocomplete functionality)
-- Uncomment if you want to create a dedicated table for sample sources
-- CREATE TABLE IF NOT EXISTS `sample_sources` (
--     `id` INT AUTO_INCREMENT PRIMARY KEY,
--     `source_name` VARCHAR(255) NOT NULL UNIQUE,
--     `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample_source records from existing data (if needed)
-- INSERT INTO sample_sources (source_name)
-- SELECT DISTINCT ExternalSample FROM sample_tests 
-- WHERE ExternalSample IS NOT NULL AND ExternalSample != ''
-- ON DUPLICATE KEY UPDATE source_name = VALUES(source_name);