INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('LaboratoryOder.php', 0, 'Sales Order Lab');

ALTER TABLE SalesHeader 
ADD COLUMN coa_documentno VARCHAR(20) DEFAULT NULL;

ALTER TABLE SalesLine 
ADD COLUMN sampleID VARCHAR(20) DEFAULT NULL;

INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('import_excel.php', 0, 'Quick Start To Excel');

INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('ExpiryReport.php', 0, 'Stock Dates by expiry Report');

INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('stockExpiryTracker.php', 0, 'Stock Dates by expiry Window');


INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('PDFPrintSerialNumbers.php', 0, 'Stock Serial numbers');

////29.05.2026

INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('FixedAssetDisposal.php', 0, 'Fixed assets disposal');


INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('PricingCalculator.php', 0, 'Pricing Calculator');



INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('BulkImportMappings.php', 0, 'Pricing Calculator Bulk');


INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('AccountBalances.php', 0, 'Pivot Reports');

INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('InventorySpreadsheet.php', 0, 'Stock Pivot Reports');


INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('DiscountSetup.php', 0, 'Sales Category Discounts');
