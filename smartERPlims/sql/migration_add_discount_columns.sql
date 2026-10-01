-- Migration: Add Discount Columns to Sales Module
-- Date: 2026-05-01
-- Purpose: Enable discount calculation at both line and header level for sales quotations

-- Add LineDiscountPercent column to SalesLine table (if it doesn't exist)
-- This stores the discount percentage applied to each line item
ALTER TABLE SalesLine 
ADD COLUMN IF NOT EXISTS LineDiscountPercent DECIMAL(5,2) DEFAULT 0 
AFTER TAT;

-- Add QtyDiscount column to SalesHeader table (if it doesn't exist)  
-- This stores the header-level discount percentage applied to the entire quote
ALTER TABLE SalesHeader
ADD COLUMN IF NOT EXISTS QtyDiscount DECIMAL(5,2) DEFAULT 0;

-- Create index on LineDiscountPercent for better query performance
ALTER TABLE SalesLine
ADD INDEX idx_line_discount (documentno, LineDiscountPercent);

-- Create index on QtyDiscount for better query performance
ALTER TABLE SalesHeader
ADD INDEX idx_qty_discount (documentno, QtyDiscount);

-- Note: If the above commands fail due to MySQL version compatibility,
-- use these alternative syntax commands:

-- For older MySQL versions (without IF NOT EXISTS):
-- ALTER TABLE SalesLine ADD COLUMN LineDiscountPercent DECIMAL(5,2) DEFAULT 0 AFTER TAT;
-- ALTER TABLE SalesHeader ADD COLUMN QtyDiscount DECIMAL(5,2) DEFAULT 0;

-- Verify the columns were added:
-- DESCRIBE SalesLine;
-- DESCRIBE SalesHeader;
