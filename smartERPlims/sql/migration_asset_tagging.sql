-- Migration: Add asset_id tagging to General Ledger
-- Run this once on existing databases

ALTER TABLE `generalledger`
  ADD COLUMN `asset_id` int DEFAULT NULL AFTER `dimension2`,
  ADD KEY `FK_GL_fixedassets` (`asset_id`);

ALTER TABLE `test_equipment_mapping`
  DROP COLUMN `hours_per_use`;

ALTER TABLE `enterbillslines`
  ADD COLUMN `assetid` int DEFAULT NULL AFTER `grossamount`;
