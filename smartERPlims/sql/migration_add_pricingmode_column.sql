-- Migration: Add Pricing Mode Column to SalesHeader
-- Date: 2026-09-28
-- Purpose: Persist the per-sample-standard manual pricing toggle
-- (Auto 80/20 vs forced Standard price vs forced Test price) on sales quotations.
-- Stored as JSON: {"TS0001":"standard","TS0002":"pertest"}. Missing/empty = Auto.

-- Add pricingmode column to SalesHeader table.
-- NOTE: MySQL does not support IF NOT EXISTS for ADD COLUMN (that is MariaDB
-- syntax), so check first and run only if the column is missing:
--   SHOW COLUMNS FROM SalesHeader LIKE 'pricingmode';
ALTER TABLE SalesHeader
ADD COLUMN pricingmode TEXT NULL;

-- Verify the column was added:
-- DESCRIBE SalesHeader;
