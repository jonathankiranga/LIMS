-- Add rollback column to test_results table
ALTER TABLE `test_results`
ADD COLUMN `rollback` INT DEFAULT 0 NULL;

-- Add index for faster queries
ALTER TABLE `test_results`
ADD INDEX `idx_rollback` (`rollback`);