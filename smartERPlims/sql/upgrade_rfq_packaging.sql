-- Upgrade existing RFQ installations to store buyer-defined packaging separately
-- from the legacy manually entered unit field.
-- Run against the same database used by smartERPlims.

SET @has_requested_pack = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'rfq_lines'
    AND column_name = 'requested_pack'
);
SET @rfq_pack_sql = IF(@has_requested_pack = 0,
  'ALTER TABLE rfq_lines ADD COLUMN requested_pack VARCHAR(100) NULL AFTER unit',
  'SELECT ''rfq_lines.requested_pack already exists'' AS status');
PREPARE rfq_pack_stmt FROM @rfq_pack_sql;
EXECUTE rfq_pack_stmt;
DEALLOCATE PREPARE rfq_pack_stmt;
