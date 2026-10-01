-- ============================================================
-- ALTER baseparameters: Add limits, unit, method, category columns
-- ============================================================
-- Run this ONCE to add the columns to the baseparameters table.

ALTER TABLE baseparameters
  ADD COLUMN Limits VARCHAR(255) NULL AFTER ResultType,
  ADD COLUMN MinLimit DECIMAL(10,2) NULL AFTER Limits,
  ADD COLUMN MaxLimit DECIMAL(10,2) NULL AFTER MinLimit,
  ADD COLUMN UnitOfMeasure VARCHAR(50) NULL AFTER MaxLimit,
  ADD COLUMN Method VARCHAR(255) NULL AFTER UnitOfMeasure,
  ADD COLUMN Category ENUM('microbiological','chemical') NULL DEFAULT 'chemical' AFTER Method;
