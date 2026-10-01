-- Add columns to fixedassets table for full Asset Register import
ALTER TABLE fixedassets
  ADD COLUMN `equipment_code` VARCHAR(30) DEFAULT NULL AFTER `assetid`,
  ADD COLUMN `quantity` INT DEFAULT 1 AFTER `serialno`,
  ADD COLUMN `manufacturer` VARCHAR(100) DEFAULT '' AFTER `description`,
  ADD COLUMN `modelno` VARCHAR(50) DEFAULT '' AFTER `serialno`,
  ADD COLUMN `status` VARCHAR(30) DEFAULT '' AFTER `modelno`,
  ADD COLUMN `remarks` TEXT DEFAULT NULL AFTER `status`;
