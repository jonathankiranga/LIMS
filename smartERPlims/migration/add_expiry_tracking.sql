-- Add expiry tracking to StockRegister
ALTER TABLE `StockRegister` 
ADD COLUMN `expiry_date` DATE NULL AFTER `GRN`,
ADD COLUMN `batch_reference` VARCHAR(100) NULL AFTER `expiry_date`;

ALTER TABLE `StockRegister` 
ADD INDEX `idx_expiry_date` (`expiry_date`),
ADD INDEX `idx_itemcode_expiry` (`itemcode`, `expiry_date`);

-- Add shelf life to stockmaster (for default expiry)
ALTER TABLE `stockmaster`
ADD COLUMN `shelf_life_days` INT NULL AFTER `partperunit`;

-- Add expiry date to PurchaseLine
ALTER TABLE `PurchaseLine`
ADD COLUMN `expiry_date` DATE NULL AFTER `shipping`;

-- Add expiry tracking table for manual entry (backup/manual method)
CREATE TABLE IF NOT EXISTS `stock_expiry_tracker` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `itemcode` VARCHAR(20) NOT NULL,
    `GRN` VARCHAR(20) NULL,
    `batch_reference` VARCHAR(100) NULL,
    `expiry_date` DATE NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `remaining_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_itemcode` (`itemcode`),
    INDEX `idx_expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

//// 2026


CREATE TABLE `serialnumber_counter` (
  `YearCode` VARCHAR(2) PRIMARY KEY,
  `Counter` INT DEFAULT 0
);

ALTER TABLE stockmaster ADD COLUMN `requireserial` TINYINT(1) DEFAULT 0 AFTER `production`;

ALTER TABLE StockRegister  ADD COLUMN `serial` varchar(10) DEFAULT '' AFTER `GRN`;
ALTER TABLE StockRegister  ADD COLUMN `printed` TINYINT(1) DEFAULT 0 AFTER `serial`;
