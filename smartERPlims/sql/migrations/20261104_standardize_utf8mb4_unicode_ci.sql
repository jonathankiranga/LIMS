-- SmartERPlims character-set/collation standardization.
-- Target: utf8mb4 / utf8mb4_unicode_ci.
-- Run this AFTER create_rfq_module.sql against the tenant database.
-- This changes the database default and converts the RFQ module tables.
-- Legacy ERP tables are intentionally not bulk-converted here: their foreign
-- keys, indexes and existing column definitions must be audited before a
-- database-wide conversion is applied.

-- Apply to whichever tenant database is currently selected.
SET @rfq_db_name = DATABASE();
SET @rfq_alter_database = CONCAT(
  'ALTER DATABASE `',
  REPLACE(@rfq_db_name, '`', '``'),
  '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
);
PREPARE rfq_collation_stmt FROM @rfq_alter_database;
EXECUTE rfq_collation_stmt;
DEALLOCATE PREPARE rfq_collation_stmt;

ALTER TABLE rfq_header
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE rfq_lines
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE rfq_invites
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE rfq_quotes
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE rfq_quote_lines
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE rfq_email_log
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verify the RFQ tables now use the standard collation.
SELECT TABLE_NAME, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'rfq_header',
    'rfq_lines',
    'rfq_invites',
    'rfq_quotes',
    'rfq_quote_lines',
    'rfq_email_log'
  )
ORDER BY TABLE_NAME;
