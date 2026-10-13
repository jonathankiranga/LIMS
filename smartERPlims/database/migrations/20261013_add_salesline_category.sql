-- Migration: Add SalesLine.category for ERP standard category (e.g. TS####).
-- Target database: the same ERP database used by the quotation UI and LimsSalesApi.php.
-- Safe to rerun: adds the column only when it does not already exist.
-- Review the target schema and take a database backup before applying.

SET @salesline_category_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'SalesLine'
      AND COLUMN_NAME = 'category'
);

SET @salesline_category_sql := IF(
    @salesline_category_exists = 0,
    'ALTER TABLE SalesLine ADD COLUMN category VARCHAR(50) NULL',
    'SELECT ''SalesLine.category already exists; no change made'' AS migration_status'
);

PREPARE salesline_category_stmt FROM @salesline_category_sql;
EXECUTE salesline_category_stmt;
DEALLOCATE PREPARE salesline_category_stmt;

-- Verify the resulting schema:
SHOW COLUMNS FROM SalesLine LIKE 'category';
