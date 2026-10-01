-- Drop foreign key constraint on teststandards.sm that references standard_methods
-- Run this on the server database (erplabwo_lims_encrpted)

SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'teststandards'
      AND COLUMN_NAME = 'sm'
      AND REFERENCED_TABLE_NAME IS NOT NULL
);

SET @sql = IFNULL(
    CONCAT('ALTER TABLE teststandards DROP FOREIGN KEY ', @constraint_name),
    'SELECT "No FK constraint on sm column found" AS result'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make sm column nullable (already nullable in some databases)
ALTER TABLE teststandards MODIFY COLUMN sm INT NULL;
