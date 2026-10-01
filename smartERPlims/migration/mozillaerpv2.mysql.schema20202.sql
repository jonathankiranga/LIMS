DROP TABLE IF EXISTS `Accountsetup`;
CREATE TABLE `Accountsetup` (
  `salesfrom` CHAR(10) NOT NULL,
  `salesto` CHAR(10) NOT NULL,
  `stockfrom` CHAR(10) NOT NULL,
  `stockto` CHAR(10) NOT NULL,
  `purchasesfrom` CHAR(10) NOT NULL,
  `purchasesto` CHAR(10) NOT NULL,
  `expensefrom` CHAR(10) NOT NULL,
  `expenseto` CHAR(10) NOT NULL,
  `fixedassetsFrom` CHAR(10) NULL,
  `fixedassetsTo` CHAR(10) NULL,
  `inventoryFrom` CHAR(10) NULL,
  `inventoryTo` CHAR(10) NULL,
  `DebtorsFrom` CHAR(10) NULL,
  `DebtorsTo` CHAR(10) NULL,
  `BankFrom` CHAR(10) NULL,
  `BankTo` CHAR(10) NULL,
  `CurLiabFrom` CHAR(10) NULL,
  `CurLiabTo` CHAR(10) NULL,
  `LongLiabFrom` CHAR(10) NULL,
  `LongLiabTo` CHAR(10) NULL,
  `CapitalFrom` CHAR(10) NULL,
  `CapitalTo` CHAR(10) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `acct`;
CREATE TABLE `acct` (
  `ReportStyle` INT NULL,
  `ReportCode` VARCHAR(10) NULL,
  `Sale_Purchase_Neither` INT NULL,
  `Calculation` LONGTEXT NULL,
  `system` TINYINT(1) NULL,
  `direct` TINYINT(1) NULL,
  `balance_income` INT NULL,
  `accgrp` CHAR(2) NULL,
  `currency` CHAR(3) NULL,
  `accno` CHAR(20) NULL,
  `accdesc` CHAR(30) NOT NULL,
  `oldaccno` CHAR(20) NULL,
  `postinggroup` CHAR(20) NULL,
  `inactive` TINYINT(1) NOT NULL,
  `PKey` BIGINT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`PKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `arpostinggroups`;
CREATE TABLE `arpostinggroups` (
  `code` VARCHAR(20) NOT NULL,
  `purchaseaccount` CHAR(20) NOT NULL,
  `creditorsaccount` CHAR(20) NOT NULL,
  `VATinclusive` TINYINT(1) NULL,
  `IsTaxed` TINYINT(1) NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `AssetsHeader`;
CREATE TABLE `AssetsHeader` (
  `documenttype` INT NOT NULL,
  `documentno` VARCHAR(20) NOT NULL,
  `docdate` DATETIME NULL,
  `oderdate` DATETIME NULL,
  `duedate` DATETIME NULL,
  `postingdate` DATETIME NULL,
  `vendorcode` VARCHAR(20) NOT NULL,
  `vendorname` VARCHAR(50) NOT NULL,
  `yourreference` VARCHAR(30) NULL,
  `externaldocumentno` VARCHAR(30) NULL,
  `locationcode` CHAR(10) NULL,
  `paymentterms` CHAR(10) NULL,
  `postinggroup` CHAR(20) NOT NULL,
  `currencycode` CHAR(10) NOT NULL,
  `printed` INT NULL,
  `released` INT NULL,
  `status` INT NULL,
  `userid` CHAR(20) NOT NULL,
  `period` INT NOT NULL,
  `entryno` BIGINT NOT NULL AUTO_INCREMENT,
  `vatinclusive` TINYINT(1) NULL,
  `Dimension_1` CHAR(10) NULL,
  `Dimension_2` CHAR(10) NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `audittrail`;
CREATE TABLE `audittrail` (
  `transactiondate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` VARCHAR(20) NOT NULL,
  `querystring` LONGTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `BankAccounts`;
CREATE TABLE `BankAccounts` (
  `PKey` INT NOT NULL AUTO_INCREMENT,
  `accountcode` CHAR(10) NULL,
  `bankName` VARCHAR(50) NOT NULL,
  `currency` CHAR(3) NOT NULL,
  `lastreconcileddate` DATE NULL,
  `AccountNo` CHAR(20) NULL,
  `BranchCode` CHAR(10) NULL,
  `BranchName` VARCHAR(50) NULL,
  `lastreconbalance` DECIMAL(18,2) NULL,
  `StatementNo` INT NULL,
  `lastChequeno` INT NULL,
  `PostingGroup` CHAR(20) NOT NULL,
  `Makeinactive` TINYINT(1) NULL,
  `Fluctuation` CHAR(20) NULL,
  `AcctName` VARCHAR(100) NULL,
  `bankCode` VARCHAR(20) NULL,
  `swiftcode` VARCHAR(20) NULL,
  PRIMARY KEY (`PKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `BankReconciliation`;
CREATE TABLE `BankReconciliation` (
  `PKey` INT NOT NULL AUTO_INCREMENT,
  `StatementNo` CHAR(10) NULL,
  `bankcode` CHAR(10) NULL,
  `narration` VARCHAR(50) NULL,
  `amount` DECIMAL(10,2) NULL,
  PRIMARY KEY (`PKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `BankTransactions`;
CREATE TABLE `BankTransactions` (
  `bankcode` CHAR(10) NOT NULL,
  `DocDate` DATE NOT NULL,
  `doctype` INT NULL,
  `DocumentNo` CHAR(10) NOT NULL,
  `TransType` CHAR(10) NOT NULL,
  `itemcode` VARCHAR(20) NOT NULL,
  `journal` VARCHAR(20) NOT NULL,
  `amount` DOUBLE NOT NULL,
  `ClearedAmount` DOUBLE NULL,
  `narrative` LONGTEXT NULL,
  `exchangerate` DECIMAL(10,4) NULL,
  `cleared` TINYINT(1) NULL,
  `ChequePrinted` TINYINT(1) NULL,
  `reconciled` CHAR(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `billofmaterial`;
CREATE TABLE `billofmaterial` (
  `parent_itemcode` CHAR(20) NOT NULL,
  `parent_description` VARCHAR(100) NULL,
  `parent_UOM` CHAR(10) NULL,
  `batchsize` DOUBLE NULL,
  PRIMARY KEY (`parent_itemcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `BOM_items`;
CREATE TABLE `BOM_items` (
  `parent_itemcode` CHAR(20) NOT NULL,
  `sequence` INT NULL,
  `itemcode` CHAR(20) NOT NULL,
  `description` VARCHAR(100) NOT NULL,
  `uom` CHAR(10) NOT NULL,
  `qty` DOUBLE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Budgets`;
CREATE TABLE `Budgets` (
  `periodno` INT NOT NULL,
  `dimecode` CHAR(10) NOT NULL,
  `amount` DECIMAL(18,0) NOT NULL,
  `dimecode2` CHAR(10) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `buffertable`;
CREATE TABLE `buffertable` (
  `averagestock` DOUBLE NULL,
  `partperunit` INT NULL,
  `units` INT NULL,
  `uom` VARCHAR(10) NULL,
  `date` DATE NULL,
  `batchno` VARCHAR(10) NULL,
  `doctype` INT NULL,
  `itemcode` VARCHAR(20) NULL,
  `TABLE` LONGTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Commision`;
CREATE TABLE `Commision` (
  `rownumber` INT NULL,
  `commisionabove` DOUBLE NULL,
  `commisionbelow` DOUBLE NULL,
  `ceiling` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `coycode` INT NOT NULL DEFAULT 1,
  `coyname` VARCHAR(50) NOT NULL,
  `PIN` VARCHAR(20) NOT NULL,
  `vat` VARCHAR(20) NOT NULL,
  `regoffice1` VARCHAR(40) NOT NULL,
  `regoffice2` VARCHAR(40) NOT NULL,
  `regoffice3` VARCHAR(40) NOT NULL,
  `regoffice4` VARCHAR(40) NOT NULL,
  `regoffice5` VARCHAR(20) NOT NULL,
  `regoffice6` VARCHAR(15) NOT NULL,
  `telephone` VARCHAR(25) NOT NULL,
  `fax` VARCHAR(25) NOT NULL,
  `email` VARCHAR(55) NOT NULL,
  `currencydefault` VARCHAR(4) NOT NULL,
  `DefaultDimension_1` INT NULL,
  `DefaultDimension_2` INT NULL,
  `shiftno` INT NULL,
  `Commision` DOUBLE NULL,
  `CommissionRetention` DOUBLE NULL,
  `ReduceCommissionRetention` DOUBLE NULL,
  `PeriodRollover` DATE NULL,
  `CoyAuthorisedBy` VARCHAR(100) NULL,
  `last_depreciation` DATETIME  NULL,
  PRIMARY KEY (`coycode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `confname` VARCHAR(35) NOT NULL,
  `confvalue` LONGTEXT NOT NULL,
  PRIMARY KEY (`confname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Containers`;
CREATE TABLE `Containers` (
  `itemcode` VARCHAR(20) NOT NULL,
  `ContainerCode` VARCHAR(10) NULL,
  `ContainerQTY` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `creditors`;
CREATE TABLE `creditors` (
  `itemcode` VARCHAR(20) NULL,
  `class` CHAR(10) NULL,
  `defaultgl` CHAR(10) NULL,
  `vat` TINYINT(1) NULL,
  `contact` CHAR(10) NULL,
  `flag` CHAR(6) NULL,
  `date` DATETIME NULL,
  `customer` CHAR(30) NULL,
  `status` CHAR(5) NULL,
  `firstn` CHAR(50) NULL,
  `middlen` CHAR(50) NULL,
  `lastn` CHAR(50) NULL,
  `phone` CHAR(50) NULL,
  `fax` CHAR(50) NULL,
  `company` CHAR(50) NULL,
  `altcontact` CHAR(50) NULL,
  `email` CHAR(50) NULL,
  `city` CHAR(50) NULL,
  `country` CHAR(50) NULL,
  `inactive` TINYINT(1) NULL,
  `vatregno` CHAR(50) NULL,
  `postcode` CHAR(100) NULL,
  `curr_cod` CHAR(10) NULL,
  `curr_rat` DECIMAL(9,5) NULL,
  `sns` CHAR(10) NULL,
  `balance` DECIMAL(18,4) NULL,
  `age1` DECIMAL(18,4) NULL,
  `age2` DECIMAL(18,4) NULL,
  `age3` DECIMAL(18,4) NULL,
  `age4` DECIMAL(18,4) NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `saved` TINYINT(1) NULL,
  `supplierposting` VARCHAR(20) NULL,
  `IsEmployee` TINYINT(1) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `creditorsledger`;
CREATE TABLE `creditorsledger` (
  `date` DATETIME NOT NULL,
  `whenpaid` DATETIME NULL,
  `acctfolio` CHAR(20) NOT NULL,
  `details` CHAR(100) NOT NULL,
  `flag` CHAR(6) NULL,
  `invref` CHAR(10) NULL,
  `vatamt` DECIMAL(9,2) NULL,
  `vatc` CHAR(2) NULL,
  `taxamt` DECIMAL(9,2) NULL,
  `pamount` DECIMAL(13,2) NULL,
  `allocat` DECIMAL(13,2) NULL,
  `amount` DECIMAL(13,2) NULL,
  `module` CHAR(5) NULL,
  `type` CHAR(1) NULL,
  `cash` TINYINT(1) NULL,
  `del` CHAR(2) NULL,
  `id` CHAR(1) NULL,
  `i_n_t` CHAR(1) NOT NULL,
  `journal` CHAR(20) NOT NULL,
  `typ` CHAR(2) NOT NULL,
  `contractno` CHAR(20) NULL,
  `lpolso` CHAR(20) NULL,
  `curr_cod` CHAR(10) NULL,
  `curr_rat` DECIMAL(9,5) NULL,
  `period` INT NULL,
  `systypes_1` INT NULL,
  `ledger` CHAR(20) NULL,
  `rowid` BIGINT NOT NULL AUTO_INCREMENT,
  `whtax` TINYINT(1) NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `currency` CHAR(20) NOT NULL,
  `currabrev` CHAR(3) NOT NULL,
  `country` CHAR(50) NOT NULL,
  `hundredsname` CHAR(15) NOT NULL,
  `decimalplaces` SMALLINT NOT NULL DEFAULT 2,
  `rate` DOUBLE NOT NULL DEFAULT 1,
  `webcart` SMALLINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`currabrev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `CustomerStatement`;
CREATE TABLE `CustomerStatement` (
  `Date` DATE NOT NULL,
  `Documentno` CHAR(20) NOT NULL,
  `Documenttype` INT NOT NULL,
  `Accountno` CHAR(20) NOT NULL,
  `Grossamount` DOUBLE NOT NULL,
  `JournalNo` CHAR(20) NOT NULL,
  `Dimension_One` CHAR(10) NULL,
  `Dimension_Two` CHAR(10) NULL,
  `Currency` CHAR(3) NULL,
  `Datewhenpaid` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `CustomerVisits`;
CREATE TABLE `CustomerVisits` (
  `itemcode` VARCHAR(20) NOT NULL,
  `lastcompleted` DATE NOT NULL,
  `today` DATE NOT NULL,
  `taskid` CHAR(10) NOT NULL,
  `taskdescription` LONGTEXT NOT NULL,
  `comments` LONGTEXT NULL,
  `userresponsible` VARCHAR(20) NOT NULL,
  `manager` VARCHAR(20) NOT NULL,
  `invoiced` TINYINT(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `debtors`;
CREATE TABLE `debtors` (
  `type` CHAR(1) NULL,
  `istaff` INT NULL,
  `cleared` TINYINT(1) NULL,
  `pinno` CHAR(20) NULL,
  `itemcode` VARCHAR(20) NULL,
  `class` CHAR(10) NULL,
  `cardadd` CHAR(10) NULL,
  `contact` CHAR(100) NULL,
  `defaultgl` CHAR(10) NULL,
  `currbal` DECIMAL(13,2) NULL,
  `flag` CHAR(6) NULL,
  `date` DATETIME NULL,
  `creditlimit` DECIMAL(14,2) NULL,
  `customer` LONGTEXT NULL,
  `status` CHAR(5) NULL,
  `firstn` CHAR(10) NULL,
  `middlen` CHAR(15) NULL,
  `lastn` CHAR(10) NULL,
  `phone` CHAR(10) NULL,
  `fax` VARCHAR(50) NULL,
  `company` CHAR(100) NULL,
  `altcontact` CHAR(100) NULL,
  `email` CHAR(100) NULL,
  `city` CHAR(100) NULL,
  `country` CHAR(100) NULL,
  `preffpay` CHAR(10) NULL,
  `crdcardno` CHAR(10) NULL,
  `inactive` TINYINT(1) NULL,
  `namecrd` CHAR(10) NULL,
  `postcode` CHAR(100) NULL,
  `curr_cod` CHAR(10) NULL,
  `curr_rat` DECIMAL(9,5) NULL,
  `id` CHAR(1) NULL,
  `i_n_t` CHAR(1) NULL,
  `typ` CHAR(2) NULL,
  `sns` CHAR(10) NULL,
  `balance` DECIMAL(18,4) NULL,
  `age1` DECIMAL(18,0) NULL,
  `age2` DECIMAL(18,4) NULL,
  `age3` DECIMAL(18,4) NULL,
  `age4` DECIMAL(18,4) NULL,
  `pkey` bigint NOT NULL AUTO_INCREMENT,
  `islocal` TINYINT(1) NULL,
  `username` CHAR(20) NULL,
  `customerposting` VARCHAR(20) NULL,
  `salesman` VARCHAR(10) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `debtorsledger`;
CREATE TABLE `debtorsledger` (
  `date` DATETIME NOT NULL,
  `details` CHAR(100) NOT NULL,
  `cleared` TINYINT(1) NULL,
  `flag` CHAR(6) NULL,
  `invref` CHAR(20) NULL,
  `acctfolio` CHAR(20) NOT NULL,
  `allocat` DOUBLE NULL,
  `amount` DOUBLE NULL,
  `pamount` DOUBLE NULL,
  `module` CHAR(5) NULL,
  `type` CHAR(1) NULL,
  `cash` TINYINT(1) NULL,
  `del` CHAR(2) NULL,
  `curr_cod` CHAR(10) NULL,
  `curr_rat` DOUBLE NULL,
  `id` CHAR(1) NULL,
  `i_n_t` CHAR(1) NOT NULL,
  `period` INT NULL,
  `journal` CHAR(20) NOT NULL,
  `typ` CHAR(2) NOT NULL,
  `sns` CHAR(10) NULL,
  `vatamt` DOUBLE NULL,
  `vatc` CHAR(2) NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `systypes_1` INT NULL,
  `ledger` CHAR(20) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Dimensions`;
CREATE TABLE `Dimensions` (
  `id` INT NOT NULL,
  `Code` CHAR(10) NOT NULL,
  `Dimension` VARCHAR(100) NULL,
  `PARENT` CHAR(10) NULL,
  `LEVEL` INT NULL,
  `BLOCKED` TINYINT(1) NULL,
  PRIMARY KEY (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `DimensionSetUp`;
CREATE TABLE `DimensionSetUp` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `Dimension_type` VARCHAR(100) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Discouts`;
CREATE TABLE `Discouts` (
  `Rate` FLOAT NOT NULL,
  `QTY` DOUBLE NOT NULL,
  `rowid` INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `emailsettings`;
CREATE TABLE `emailsettings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `host` VARCHAR(30) NOT NULL,
  `port` CHAR(5) NOT NULL,
  `heloaddress` VARCHAR(20) NOT NULL,
  `username` VARCHAR(50) NULL DEFAULT NULL,
  `password` VARCHAR(30) NULL DEFAULT NULL,
  `timeout` INT NULL DEFAULT 5,
  `companyname` VARCHAR(50) NULL DEFAULT NULL,
  `auth` SMALLINT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `EmployeeProductionRates`;
CREATE TABLE `EmployeeProductionRates` (
  `StaffID` VARCHAR(10) NOT NULL,
  `StockID` VARCHAR(50) NOT NULL,
  `Rate` DOUBLE NOT NULL,
  `UOM` VARCHAR(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `EnterbillHeaders`;
CREATE TABLE `EnterbillHeaders` (
  `date` DATE NULL,
  `documenttype` INT NULL,
  `documentno` CHAR(20) NULL,
  `narration` LONGTEXT NULL,
  `journalno` CHAR(20) NULL,
  `whtax` TINYINT(1) NULL,
  `VendorID` VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `EnterbillsLines`;
CREATE TABLE `EnterbillsLines` (
  `documenttype` INT NULL,
  `documentno` CHAR(20) NULL,
  `journalno` CHAR(20) NULL,
  `account` CHAR(20) NULL,
  `vatamount` DECIMAL(10,2) NULL,
  `grossamount` DECIMAL(10,2) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fellowships`;
CREATE TABLE `fellowships` (
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `fellowships` VARCHAR(50) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `FinancialPeriods`;
CREATE TABLE `FinancialPeriods` (
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `Name` VARCHAR(20) NOT NULL,
  `newyear` TINYINT(1) NULL,
  `closed` TINYINT(1) NULL,
  `periodno` INT NULL,
  PRIMARY KEY (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixedassetcategories`;
CREATE TABLE `fixedassetcategories` (
  `categoryid` CHAR(6) NOT NULL,
  `categorydescription` CHAR(20) NOT NULL,
  `costact` VARCHAR(20) NOT NULL,
  `depnact` VARCHAR(20) NOT NULL,
  `disposalact` VARCHAR(20) NOT NULL,
  `accumdepnact` VARCHAR(20) NOT NULL,
  `defaultdepnrate` DOUBLE NOT NULL DEFAULT 0.2,
  `defaultdepntype` INT NOT NULL DEFAULT 1,
  `Equipment_hired_act` VARCHAR(20) NULL,
  `defaultgl_vat_act` VARCHAR(20) NULL,
  `vatcategorycode` CHAR(10) NULL,
  PRIMARY KEY (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixedassetlocations`;
CREATE TABLE `fixedassetlocations` (
  `locationid` CHAR(6) NOT NULL,
  `locationdescription` CHAR(20) NOT NULL,
  `parentlocationid` CHAR(6) NULL DEFAULT NULL,
  PRIMARY KEY (`locationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixedassets`;
CREATE TABLE `fixedassets` (
  `assetid` INT NOT NULL AUTO_INCREMENT,
  `serialno` VARCHAR(30) NOT NULL,
  `barcode` VARCHAR(20) NOT NULL,
  `assetlocation` VARCHAR(6) NOT NULL,
  `cost` DOUBLE NOT NULL DEFAULT 0,
  `accumdepn` DOUBLE NOT NULL DEFAULT 0,
  `datepurchased` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disposalproceeds` DOUBLE NOT NULL DEFAULT 0,
  `assetcategoryid` VARCHAR(6) NOT NULL,
  `description` VARCHAR(50) NOT NULL,
  `longdescription` LONGTEXT NOT NULL,
  `depntype` INT NOT NULL DEFAULT 1,
  `depnrate` DOUBLE NOT NULL,
  `disposaldate` DATETIME NULL,
  PRIMARY KEY (`assetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `FixedAssetsLine`;
CREATE TABLE `FixedAssetsLine` (
  `entryno` BIGINT NOT NULL AUTO_INCREMENT,
  `documenttype` INT NOT NULL,
  `docdate` DATETIME NOT NULL,
  `documentno` VARCHAR(20) NOT NULL,
  `locationcode` CHAR(10) NULL,
  `stocktype` INT NULL,
  `code` VARCHAR(20) NULL,
  `description` VARCHAR(50) NULL,
  `unitofmeasure` VARCHAR(10) NULL,
  `Quantity` DECIMAL(10,2) NULL,
  `Quantity_toinvoice` DECIMAL(10,2) NULL,
  `Qunatity_delivered` DECIMAL(10,2) NULL,
  `UnitPrice` DECIMAL(10,2) NULL,
  `vatamount` DECIMAL(10,2) NULL,
  `invoiceamount` DECIMAL(10,2) NULL,
  `completed` TINYINT(1) NULL,
  `printed` TINYINT(1) NULL,
  `containerprice` DECIMAL(10,2) NULL,
  `containersunits` DECIMAL(10,0) NULL,
  `totalchargedcontainers` DECIMAL(10,2) NULL,
  `containercode` VARCHAR(20) NULL,
  `vatrate` DECIMAL(10,2) NULL,
  `inclusive` TINYINT(1) NULL,
  `UOM` VARCHAR(10) NULL,
  `shipping` DOUBLE NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixedassettasks`;
CREATE TABLE `fixedassettasks` (
  `taskid` INT NOT NULL AUTO_INCREMENT,
  `assetid` INT NOT NULL,
  `taskdescription` LONGTEXT NOT NULL,
  `frequencydays` INT NOT NULL DEFAULT 365,
  `lastcompleted` DATETIME NOT NULL,
  `userresponsible` VARCHAR(20) NOT NULL,
  `manager` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fixedassettrans`;
CREATE TABLE `fixedassettrans` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `assetid` INT NOT NULL,
  `transtype` SMALLINT NOT NULL,
  `transdate` DATETIME NOT NULL,
  `transno` INT NOT NULL,
  `periodno` SMALLINT NOT NULL,
  `inputdate` DATETIME NOT NULL,
  `fixedassettranstype` VARCHAR(8) NOT NULL,
  `amount` DOUBLE NOT NULL,
  `units` DOUBLE NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Generalledger`;
CREATE TABLE `Generalledger` (
  `rowid` BIGINT NOT NULL AUTO_INCREMENT,
  `journalno` VARCHAR(20) NOT NULL,
  `Docdate` DATETIME NOT NULL,
  `period` INT NOT NULL,
  `DocumentNo` VARCHAR(20) NOT NULL,
  `DocumentType` INT NOT NULL,
  `accountcode` CHAR(20) NOT NULL,
  `balaccountcode` CHAR(20) NOT NULL,
  `amount` DOUBLE NOT NULL,
  `currencycode` CHAR(3) NOT NULL,
  `ExchangeRate` DOUBLE NULL,
  `cutomercode` VARCHAR(20) NULL,
  `suppliercode` VARCHAR(20) NULL,
  `bankcode` VARCHAR(20) NULL,
  `reconcilled` TINYINT(1) NULL,
  `narration` LONGTEXT NULL,
  `ExchangeRateDiff` DOUBLE NULL,
  `VATaccountcode` CHAR(20) NULL,
  `VATamount` DECIMAL(10,2) NULL,
  `dimension` CHAR(10) NULL,
  `dimension2` CHAR(10) NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `geocode_param`;
CREATE TABLE `geocode_param` (
  `geocodeid` SMALLINT NOT NULL AUTO_INCREMENT,
  `geocode_key` VARCHAR(200) NOT NULL,
  `center_long` VARCHAR(20) NOT NULL,
  `center_lat` VARCHAR(20) NOT NULL,
  `map_height` VARCHAR(10) NOT NULL,
  `map_width` VARCHAR(10) NOT NULL,
  `map_host` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`geocodeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `GLpostinggroup`;
CREATE TABLE `GLpostinggroup` (
  `code` CHAR(20) NOT NULL,
  `defaultgl_vat` CHAR(20) NULL,
  `vatcategory` CHAR(1) NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `inventorypostinggroup`;
CREATE TABLE `inventorypostinggroup` (
  `code` CHAR(20) NOT NULL,
  `defaultgl_sales` CHAR(20) NULL,
  `defaultgl_purch` CHAR(20) NULL,
  `defaultgl_vat` CHAR(20) NULL,
  `balancesheet` CHAR(20) NULL,
  `vatcategory` CHAR(1) NULL,
  `wip` CHAR(20) NULL,
  `stockvariance` CHAR(20) NULL,
  `productionexpense` CHAR(20) NULL,
  `CostOfSales` VARCHAR(100) NULL,
  `spoilage` VARCHAR(20) NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `JournalEntries`;
CREATE TABLE `JournalEntries` (
  `Docdate` DATE NOT NULL,
  `JournalNo` CHAR(20) NOT NULL,
  `Account` CHAR(20) NOT NULL,
  `BalAccount` CHAR(20) NOT NULL,
  `transtype` CHAR(10) NULL,
  `itemcode` CHAR(20) NULL,
  `amount` DOUBLE NOT NULL,
  `Currency` CHAR(3) NOT NULL,
  `Dimension_1` CHAR(10) NULL,
  `Dimension_2` CHAR(10) NULL,
  `narration` VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `LaboratoryStandards`;
CREATE TABLE `LaboratoryStandards` (
  `itemcode` CHAR(20) NOT NULL,
  `ParameterID` VARCHAR(10) NULL,
  `Parameter` VARCHAR(100) NULL,
  `Limits_min` VARCHAR(10) NULL,
  `Limits_max` VARCHAR(10) NULL,
  `Method` VARCHAR(50) NULL,
  `Units` VARCHAR(50) NULL,
  `vital` TINYINT(1) NULL,
  `category` VARCHAR(1) NULL,
  `NoStandardlimit` TINYINT(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `LabPostingDetail`;
CREATE TABLE `LabPostingDetail` (
  `DocumentNo` VARCHAR(20) NOT NULL,
  `SampleID` VARCHAR(20) NOT NULL,
  `SampleTypeID` VARCHAR(20) NOT NULL,
  `ParameterID` VARCHAR(20) NULL,
  `Method` VARCHAR(50) NULL,
  `Limits_min` VARCHAR(10) NULL,
  `Limits_max` VARCHAR(10) NULL,
  `Results` VARCHAR(50) NULL,
  `lastuserid` VARCHAR(20) NULL,
  `lastdatetime` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Member`;
CREATE TABLE `Member` (
  `type` CHAR(1) NULL,
  `istaff` INT NULL,
  `cleared` TINYINT(1) NULL,
  `pinno` CHAR(20) NULL,
  `itemcode` VARCHAR(20) NULL,
  `class` CHAR(10) NULL,
  `cardadd` CHAR(10) NULL,
  `contact` CHAR(100) NULL,
  `defaultgl` CHAR(10) NULL,
  `currbal` DECIMAL(13,2) NULL,
  `flag` CHAR(6) NULL,
  `date` DATETIME NULL,
  `creditlimit` DECIMAL(14,2) NULL,
  `customer` CHAR(30) NULL,
  `status` CHAR(5) NULL,
  `firstn` CHAR(10) NULL,
  `middlen` CHAR(11) NULL,
  `lastn` CHAR(10) NULL,
  `phone` CHAR(10) NULL,
  `fax` VARCHAR(50) NULL,
  `company` CHAR(100) NULL,
  `altcontact` CHAR(100) NULL,
  `email` CHAR(100) NULL,
  `city` CHAR(100) NULL,
  `country` CHAR(100) NULL,
  `preffpay` CHAR(10) NULL,
  `crdcardno` CHAR(10) NULL,
  `inactive` TINYINT(1) NULL,
  `namecrd` CHAR(10) NULL,
  `postcode` CHAR(100) NULL,
  `curr_cod` CHAR(10) NULL,
  `curr_rat` DECIMAL(9,5) NULL,
  `id` CHAR(1) NULL,
  `i_n_t` CHAR(1) NULL,
  `typ` CHAR(2) NULL,
  `sns` CHAR(10) NULL,
  `balance` DECIMAL(18,4) NULL,
  `age1` DECIMAL(18,0) NULL,
  `age2` DECIMAL(18,4) NULL,
  `age3` DECIMAL(18,4) NULL,
  `age4` DECIMAL(18,4) NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `islocal` TINYINT(1) NULL,
  `username` CHAR(20) NULL,
  `customerposting` VARCHAR(20) NULL,
  `salesman` VARCHAR(10) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `naturalElements`;
CREATE TABLE `naturalElements` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` CHAR(10) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `NewActivity`;
CREATE TABLE `NewActivity` (
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `ActivityOwner` VARCHAR(20) NULL,
  `Activityname` LONGTEXT NULL,
  `fromdue` DATE NULL,
  `todue` DATE NULL,
  `Contact` VARCHAR(20) NULL,
  `Status` INT NULL,
  `valueofbusiness` DOUBLE NULL,
  `taskdetails` LONGTEXT NULL,
  `createdby` VARCHAR(20) NULL,
  `createdon` DATETIME NULL,
  `lastactivity` DATETIME NULL,
  `Sart_time_from` VARCHAR(25) NULL,
  `Sart_time_to` VARCHAR(25) NULL,
  `End_time_from` VARCHAR(25) NULL,
  `End_time_to` VARCHAR(25) NULL,
  `location` LONGTEXT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `NewContacts`;
CREATE TABLE `NewContacts` (
  `company` LONGTEXT NULL,
  `postcode` LONGTEXT NULL,
  `city` CHAR(20) NULL,
  `country` CHAR(20) NULL,
  `Physical_Address` LONGTEXT NULL,
  `PIN_VAT` LONGTEXT NULL,
  `phone` CHAR(15) NULL,
  `email` LONGTEXT NULL,
  `salesman` CHAR(10) NULL,
  `Contact_Name` LONGTEXT NULL,
  `Contact_Designation` LONGTEXT NULL,
  `Contact_Telephone` CHAR(15) NULL,
  `Contact_email` LONGTEXT NULL,
  `Alt_Contact_Name` LONGTEXT NULL,
  `Alt_Contact_Designation` LONGTEXT NULL,
  `Alt_Contact_Telephone` CHAR(15) NULL,
  `Alt_Contact_email` LONGTEXT NULL,
  `createdby` CHAR(20) NULL,
  `Date_Created` DATETIME NULL,
  `Last_Activity` DATETIME NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `PaymentsAllocation`;
CREATE TABLE `PaymentsAllocation` (
  `itemcode` VARCHAR(20) NOT NULL,
  `date` DATE NOT NULL,
  `invoiceno` CHAR(20) NOT NULL,
  `journalno` CHAR(20) NOT NULL,
  `doctype` INT NOT NULL,
  `receiptno` CHAR(20) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `receiptjournal` CHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `paymentvoucherheader`;
CREATE TABLE `paymentvoucherheader` (
  `docno` CHAR(20) NOT NULL,
  `date` DATE NOT NULL,
  `itemcode` CHAR(20) NOT NULL,
  `externalref` VARCHAR(20) NULL,
  `narrative` VARCHAR(50) NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `printed` TINYINT(1) NULL,
  `journal` VARCHAR(20) NULL,
  `currency` CHAR(10) NULL,
  `status` INT NULL,
  `Comments` LONGTEXT NULL,
  `ChequePrinted` TINYINT(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `paymentvoucherline`;
CREATE TABLE `paymentvoucherline` (
  `docno` CHAR(20) NOT NULL,
  `itemcode` CHAR(20) NOT NULL,
  `narrative` VARCHAR(50) NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `journal` VARCHAR(20) NULL,
  `invoice_journal` VARCHAR(20) NULL,
  `Dimension_1` CHAR(20) NOT NULL,
  `Dimension_2` CHAR(20) NOT NULL,
  `Budget` DECIMAL(18,2) NULL,
  `Committed` DECIMAL(18,2) NULL,
  `Expensed` DECIMAL(18,2) NULL,
  `Balance` DECIMAL(18,2) NULL,
  `whtax` DECIMAL(18,2) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `periods`;
CREATE TABLE `periods` (
  `periodno` SMALLINT NOT NULL DEFAULT 0,
  `lastdate_in_period` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`periodno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `pettdoc`;
CREATE TABLE `pettdoc` (
  `date` DATETIME NULL,
  `userid` VARCHAR(20) NULL,
  `moneyin` DECIMAL(10,2) NULL,
  `moneyout` DECIMAL(10,2) NULL,
  `balance` DECIMAL(10,2) NULL,
  `account` CHAR(10) NULL,
  `expensedetails` CHAR(200) NULL,
  `petteycashno` CHAR(10) NULL,
  `journal` CHAR(10) NULL,
  `transtype` INT NULL,
  `shiftno` DOUBLE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `postinggroups`;
CREATE TABLE `postinggroups` (
  `code` VARCHAR(20) NOT NULL,
  `salesaccount` CHAR(20) NOT NULL,
  `debtorsaccount` CHAR(20) NOT NULL,
  `VATinclusive` TINYINT(1) NULL,
  `IsTaxed` TINYINT(1) NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `PriceList`;
CREATE TABLE `PriceList` (
  `customerCode` VARCHAR(20) NOT NULL,
  `stockcode` VARCHAR(20) NULL,
  `approved` TINYINT(1) NULL,
  `approvedby` VARCHAR(20) NULL,
  `DateTime` DATETIME NULL,
  `units_code` VARCHAR(20) NULL,
  `quantity` INT NULL,
  `price` FLOAT NULL,
  `id` INT NOT NULL AUTO_INCREMENT,
  `container` VARCHAR(20) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ProdcutionMasterLine`;
CREATE TABLE `ProdcutionMasterLine` (
  `Batchno` VARCHAR(20) NOT NULL,
  `itemcode` VARCHAR(10) NOT NULL,
  `qty` DOUBLE NOT NULL,
  `cost` DOUBLE NULL,
  `volcorfactor` DOUBLE NULL,
  `temperature` DOUBLE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `productionconfig`;
CREATE TABLE `productionconfig` (
  `categoryid` VARCHAR(5) NOT NULL,
  `rawmatid` VARCHAR(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `productionEmployee`;
CREATE TABLE `productionEmployee` (
  `istaff` INT NULL,
  `pinno` CHAR(20) NULL,
  `code` VARCHAR(10) NULL,
  `currbal` DECIMAL(13,2) NULL,
  `date` DATETIME NULL,
  `manager` VARCHAR(10) NULL,
  `salesman` CHAR(50) NULL,
  `status` CHAR(5) NULL,
  `firstn` CHAR(10) NULL,
  `middlen` CHAR(10) NULL,
  `lastn` CHAR(10) NULL,
  `phone` CHAR(10) NULL,
  `fax` VARCHAR(50) NULL,
  `company` CHAR(100) NULL,
  `altcontact` CHAR(100) NULL,
  `email` CHAR(100) NULL,
  `city` CHAR(100) NULL,
  `country` CHAR(100) NULL,
  `inactive` TINYINT(1) NULL,
  `postcode` CHAR(100) NULL,
  `curr_cod` CHAR(10) NULL,
  `curr_rat` DECIMAL(9,5) NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `commissionposting` VARCHAR(20) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `productionManager`;
CREATE TABLE `productionManager` (
  `istaff` INT NULL,
  `pinno` CHAR(20) NULL,
  `code` VARCHAR(10) NULL,
  `currbal` DECIMAL(13,2) NULL,
  `date` DATETIME NULL,
  `commission` DECIMAL(14,2) NULL,
  `salesman` CHAR(50) NULL,
  `status` CHAR(5) NULL,
  `firstn` CHAR(10) NULL,
  `middlen` CHAR(10) NULL,
  `lastn` CHAR(10) NULL,
  `phone` CHAR(10) NULL,
  `fax` VARCHAR(50) NULL,
  `company` CHAR(100) NULL,
  `altcontact` CHAR(100) NULL,
  `email` CHAR(100) NULL,
  `city` CHAR(100) NULL,
  `country` CHAR(100) NULL,
  `inactive` TINYINT(1) NULL,
  `postcode` CHAR(100) NULL,
  `curr_cod` CHAR(10) NULL,
  `curr_rat` DECIMAL(9,5) NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `commissionposting` VARCHAR(20) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ProductionMaster`;
CREATE TABLE `ProductionMaster` (
  `Batchno` VARCHAR(20) NULL,
  `date` DATETIME NULL,
  `userid` VARCHAR(50) NULL,
  `averagestock` DOUBLE NULL,
  `itemcode` VARCHAR(20) NULL,
  `production` INT NULL,
  `testreport` VARCHAR(20) NULL,
  `DateTestended` DATETIME NULL,
  `Interpretation` LONGTEXT NULL,
  `Passed` INT NULL,
  `Status` INT NULL,
  `modified` DOUBLE NULL,
  `bitumenph` DOUBLE NULL,
  `SalesHeader` VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ProductionRates`;
CREATE TABLE `ProductionRates` (
  `LabourID` VARCHAR(10) NOT NULL,
  `LabourDescription` VARCHAR(50) NOT NULL,
  `Rate` DOUBLE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ProductionUnit`;
CREATE TABLE `ProductionUnit` (
  `itemcode` CHAR(20) NOT NULL,
  `capacity` DOUBLE NOT NULL,
  `tankname` CHAR(30) NOT NULL,
  `UOM` CHAR(10) NULL,
  `CapacityUOM` CHAR(10) NULL,
  `units` DOUBLE NULL,
  `balance` DOUBLE NULL,
  `status` TINYINT(1) NULL,
  PRIMARY KEY (`tankname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `PurchaseHeader`;
CREATE TABLE `PurchaseHeader` (
  `documenttype` INT NOT NULL,
  `documentno` VARCHAR(20) NOT NULL,
  `docdate` DATETIME NULL,
  `oderdate` DATETIME NULL,
  `duedate` DATETIME NULL,
  `postingdate` DATETIME NULL,
  `vendorcode` VARCHAR(20) NOT NULL,
  `vendorname` VARCHAR(50) NOT NULL,
  `yourreference` VARCHAR(30) NULL,
  `externaldocumentno` VARCHAR(30) NULL,
  `locationcode` CHAR(10) NULL,
  `paymentterms` CHAR(10) NULL,
  `postinggroup` CHAR(20) NOT NULL,
  `currencycode` CHAR(10) NOT NULL,
  `printed` INT NULL,
  `released` INT NULL,
  `status` INT NULL,
  `userid` CHAR(20) NOT NULL,
  `period` INT NOT NULL,
  `entryno` BIGINT NOT NULL AUTO_INCREMENT,
  `vatinclusive` TINYINT(1) NULL,
  `Dimension_1` CHAR(10) NULL,
  `Dimension_2` CHAR(10) NULL,
  `freight` DOUBLE NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `PurchaseLine`;
CREATE TABLE `PurchaseLine` (
  `entryno` BIGINT NOT NULL AUTO_INCREMENT,
  `documenttype` INT NOT NULL,
  `docdate` DATETIME NOT NULL,
  `documentno` VARCHAR(20) NOT NULL,
  `locationcode` CHAR(10) NULL,
  `stocktype` INT NULL,
  `code` VARCHAR(20) NULL,
  `description` VARCHAR(50) NULL,
  `unitofmeasure` VARCHAR(10) NULL,
  `Quantity` DECIMAL(10,2) NULL,
  `Quantity_toinvoice` DECIMAL(10,2) NULL,
  `Qunatity_delivered` DECIMAL(10,2) NULL,
  `UnitPrice` DOUBLE NULL,
  `vatamount` DECIMAL(10,2) NULL,
  `invoiceamount` DECIMAL(10,2) NULL,
  `completed` TINYINT(1) NULL,
  `printed` TINYINT(1) NULL,
  `containerprice` DECIMAL(10,2) NULL,
  `containersunits` DECIMAL(10,0) NULL,
  `totalchargedcontainers` DECIMAL(10,2) NULL,
  `containercode` VARCHAR(20) NULL,
  `vatrate` DECIMAL(10,2) NULL,
  `inclusive` TINYINT(1) NULL,
  `UOM` VARCHAR(10) NULL,
  `shipping` DOUBLE NULL,
  `discount` DOUBLE NULL,
  `PartPerUnit` DOUBLE NULL,
  `QuantityToReceive` DOUBLE NULL,
  `PriceToReceive` DOUBLE NULL,
  `unitofreceivedIn` VARCHAR(20) NULL,
  `packzizegrn` DOUBLE NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `receiptheader`;
CREATE TABLE `receiptheader` (
  `docno` CHAR(20) NOT NULL,
  `date` DATE NOT NULL,
  `itemcode` CHAR(20) NOT NULL,
  `externalref` VARCHAR(20) NULL,
  `narrative` VARCHAR(50) NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `printed` TINYINT(1) NULL,
  `journal` VARCHAR(20) NULL,
  `currency` CHAR(10) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ReceiptsAllocation`;
CREATE TABLE `ReceiptsAllocation` (
  `itemcode` VARCHAR(20) NOT NULL,
  `date` DATE NOT NULL,
  `invoiceno` CHAR(20) NOT NULL,
  `journalno` CHAR(20) NOT NULL,
  `doctype` INT NOT NULL,
  `receiptno` CHAR(20) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `receiptjournal` CHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





DROP TABLE IF EXISTS `SalesHeader`;
CREATE TABLE `SalesHeader` (
  `documenttype` INT NOT NULL,
  `documentno` VARCHAR(20) NOT NULL,
  `docdate` DATETIME NULL,
  `oderdate` DATETIME NULL,
  `duedate` DATETIME NULL,
  `postingdate` DATETIME NULL,
  `customercode` VARCHAR(20) NOT NULL,
  `customername` VARCHAR(50) NOT NULL,
  `yourreference` VARCHAR(30) NULL,
  `externaldocumentno` VARCHAR(30) NULL,
  `locationcode` CHAR(10) NULL,
  `paymentterms` VARCHAR(100) NULL,
  `postinggroup` CHAR(20) NOT NULL,
  `currencycode` CHAR(10) NOT NULL,
  `salespersoncode` CHAR(10) NULL,
  `printed` INT NULL,
  `released` INT NULL,
  `status` INT NULL,
  `userid` CHAR(20) NOT NULL,
  `period` INT NOT NULL,
  `entryno` BIGINT NOT NULL AUTO_INCREMENT,
  `vatinclusive` TINYINT(1) NULL,
  `QtyDiscount` DOUBLE NULL,
  `journal` VARCHAR(20) NULL,
  `shipping` DOUBLE NULL,
  `packagescharge` DOUBLE NULL,
  `picture` LONGTEXT NULL,
  `pricingmode` TEXT NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `SalesLine`;
CREATE TABLE `SalesLine` (
  `entryno` BIGINT NOT NULL AUTO_INCREMENT,
  `documenttype` INT NOT NULL,
  `docdate` DATETIME NOT NULL,
  `documentno` VARCHAR(20) NOT NULL,
  `locationcode` CHAR(10) NULL,
  `stocktype` INT NULL,
  `code` VARCHAR(20) NULL,
  `description` VARCHAR(50) NULL,
  `unitofmeasure` VARCHAR(10) NULL,
  `Quantity` DECIMAL(10,2) NULL,
  `Quantity_toinvoice` DECIMAL(10,2) NULL,
  `Qunatity_delivered` DECIMAL(10,2) NULL,
  `UnitPrice` DECIMAL(10,2) NULL,
  `vatamount` DECIMAL(10,2) NULL,
  `invoiceamount` DECIMAL(10,2) NULL,
  `completed` TINYINT(1) NULL,
  `printed` TINYINT(1) NULL,
  `containerprice` DECIMAL(10,2) NULL,
  `containersunits` DECIMAL(10,0) NULL,
  `totalchargedcontainers` DECIMAL(10,2) NULL,
  `containercode` VARCHAR(20) NULL,
  `vatrate` DECIMAL(10,2) NULL,
  `inclusive` TINYINT(1) NULL,
  `UOM` VARCHAR(10) NULL,
  `shipping` DOUBLE NULL,
  `PriceInPricelist` DOUBLE NULL,
  `Increase` DOUBLE NULL,
  `PartPerUnit` DOUBLE NULL,
  `modified` INT NULL,
  `Qunatity_replaced` INT NULL,
  `packagescharge` DOUBLE NULL,
  PRIMARY KEY (`entryno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `salesrepsinfo`;
CREATE TABLE `salesrepsinfo` (
  `istaff` INT NULL,
  `pinno` CHAR(20) NULL,
  `code` VARCHAR(10) NULL,
  `currbal` DECIMAL(13,2) NULL,
  `date` DATETIME NULL,
  `target` DECIMAL(14,2) NULL,
  `commission` DECIMAL(14,2) NULL,
  `salesman` CHAR(50) NULL,
  `status` CHAR(5) NULL,
  `firstn` CHAR(10) NULL,
  `middlen` CHAR(10) NULL,
  `lastn` CHAR(10) NULL,
  `phone` CHAR(10) NULL,
  `fax` VARCHAR(50) NULL,
  `company` CHAR(100) NULL,
  `altcontact` CHAR(100) NULL,
  `email` CHAR(100) NULL,
  `city` CHAR(100) NULL,
  `country` CHAR(100) NULL,
  `inactive` TINYINT(1) NULL,
  `postcode` CHAR(100) NULL,
  `curr_cod` CHAR(10) NULL,
  `curr_rat` DECIMAL(9,5) NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `commissionposting` VARCHAR(20) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scripts`;
CREATE TABLE `scripts` (
  `script` VARCHAR(78) NOT NULL,
  `pagesecurity` INT NOT NULL DEFAULT 1,
  `description` LONGTEXT NOT NULL,
  PRIMARY KEY (`script`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `securitygroups`;
CREATE TABLE `securitygroups` (
  `secroleid` INT NOT NULL DEFAULT 0,
  `tokenid` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`secroleid`, `tokenid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `securityroles`;
CREATE TABLE `securityroles` (
  `secroleid` INT NOT NULL AUTO_INCREMENT,
  `secrolename` LONGTEXT NOT NULL,
  PRIMARY KEY (`secroleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `securitytokens`;
CREATE TABLE `securitytokens` (
  `tokenid` INT NOT NULL DEFAULT 0,
  `tokenname` LONGTEXT NOT NULL,
  PRIMARY KEY (`tokenid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stockcategory`;
CREATE TABLE `stockcategory` (
  `categoryid` CHAR(6) NOT NULL,
  `categorydescription` VARCHAR(50) NULL,
  PRIMARY KEY (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stockexchange`;
CREATE TABLE `stockexchange` (
  `docdate` DATETIME NOT NULL,
  `docno` CHAR(10) NOT NULL,
  `item` CHAR(20) NOT NULL,
  `uom` CHAR(10) NOT NULL,
  `store` CHAR(10) NOT NULL,
  `qty` INT NOT NULL,
  `userid` CHAR(50) NOT NULL,
  `acctfolio` VARCHAR(20) NULL,
  `reason` CHAR(50) NULL,
  `authorised` INT NULL,
  `authorisedby` CHAR(50) NULL,
  `authoriseddate` DATETIME NULL,
  `direction` INT NULL,
  `cost` DOUBLE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stockledger`;
CREATE TABLE `stockledger` (
  `date` DATETIME NOT NULL,
  `stname` CHAR(50) NULL,
  `doctyp` CHAR(2) NOT NULL,
  `batch` CHAR(20) NULL,
  `itemcode` CHAR(20) NOT NULL,
  `invref` CHAR(20) NULL,
  `netamt` DECIMAL(13,2) NULL,
  `vat` DECIMAL(13,2) NULL,
  `amount` DECIMAL(18,4) NULL,
  `fulqty` DECIMAL(13,4) NOT NULL,
  `loosqty` DECIMAL(13,4) NOT NULL,
  `price` DECIMAL(9,2) NULL,
  `curr_rat` DECIMAL(9,4) NULL,
  `stockvalue` DECIMAL(18,4) NULL,
  `store` CHAR(10) NOT NULL,
  `journal` CHAR(20) NOT NULL,
  `curr_cod` CHAR(10) NULL,
  `period` INT NULL,
  `del` CHAR(2) NULL,
  `factory` TINYINT(1) NULL,
  `jobcard` CHAR(10) NULL,
  `len` DECIMAL(10,2) NULL,
  `wid` DECIMAL(10,2) NULL,
  `sman` CHAR(10) NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `BQitemcode` CHAR(10) NULL,
  `dimension` VARCHAR(10) NULL,
  `PartPerUnit` DOUBLE NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stockmaster`;
CREATE TABLE `stockmaster` (
  `isstock` INT NOT NULL,
  `barcode` CHAR(20) NULL,
  `itemcode` CHAR(20) NULL,
  `descrip` CHAR(100) NOT NULL,
  `postinggroup` CHAR(20) NOT NULL,
  `averagestock` DECIMAL(10,2) NULL,
  `partperunit` DECIMAL(10,2) NULL,
  `reorderlevel` DECIMAL(10,0) NULL,
  `eoq` DECIMAL(10,0) NULL,
  `sellingprice` DECIMAL(10,2) NULL,
  `category` VARCHAR(6) NULL,
  `units` CHAR(10) NULL,
  `subunits` CHAR(10) NULL,
  `container` CHAR(20) NULL,
  `nextserialno` INT NULL,
  `inactive` TINYINT(1) NULL,
  `pkey` BIGINT NOT NULL AUTO_INCREMENT,
  `isstock_1` TINYINT(1) NULL,
  `isstock_2` TINYINT(1) NULL,
  `isstock_3` TINYINT(1) NULL,
  `isstock_4` TINYINT(1) NULL,
  `isstock_5` TINYINT(1) NULL,
  `isstock_6` TINYINT(1) NULL,
  `production` VARCHAR(2) NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `StockRegister`;
CREATE TABLE `StockRegister` (
  `itemcode` CHAR(20) NULL,
  `StockIn` DOUBLE NULL,
  `cost` DOUBLE NULL,
  `StockOut` DOUBLE NULL,
  `StockBalance` DOUBLE NULL,
  `rowid` BIGINT NOT NULL AUTO_INCREMENT,
  `journal` CHAR(20) NULL,
  `GRN` CHAR(20) NULL,
  `PartPerUnit` DOUBLE NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Stores`;
CREATE TABLE `Stores` (
  `code` CHAR(10) NOT NULL,
  `Storename` VARCHAR(50) NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `SupplierStatement`;
CREATE TABLE `SupplierStatement` (
  `Date` DATE NOT NULL,
  `Documentno` CHAR(20) NOT NULL,
  `Documenttype` INT NOT NULL,
  `Accountno` CHAR(20) NOT NULL,
  `Grossamount` DECIMAL(18,2) NOT NULL,
  `JournalNo` CHAR(20) NOT NULL,
  `Dimension_One` CHAR(10) NULL,
  `Dimension_Two` CHAR(10) NULL,
  `Currency` CHAR(3) NULL,
  `whtax` TINYINT(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sysdiagrams`;
CREATE TABLE `sysdiagrams` (
  `name` LONGTEXT NOT NULL,
  `principal_id` INT NOT NULL,
  `diagram_id` INT NOT NULL AUTO_INCREMENT,
  `version` INT NULL,
  `definition` LONGBLOB NULL,
  PRIMARY KEY (`diagram_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `system_printers`;
CREATE TABLE `system_printers` (
  `ipaddress` VARCHAR(50) NULL,
  `printerName` LONGTEXT NULL,
  `defaultprinter` TINYINT(1) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `systypes_1`;
CREATE TABLE `systypes_1` (
  `typeid` SMALLINT NOT NULL DEFAULT 0,
  `typename` CHAR(50) NOT NULL DEFAULT '',
  `typeno` INT NOT NULL DEFAULT 1,
  `prefix` VARCHAR(5) NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tanktrans`;
CREATE TABLE `tanktrans` (
  `tankname` CHAR(30) NULL,
  `units` DOUBLE NULL,
  `uom` CHAR(10) NULL,
  `date` DATETIME NULL,
  `batchno` CHAR(10) NULL,
  `variance` DOUBLE NULL,
  `doctype` INT NULL,
  `itemcode` VARCHAR(20) NULL,
  `index` BIGINT NOT NULL AUTO_INCREMENT,
  `BQitemcode` CHAR(10) NULL,
  PRIMARY KEY (`index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `Tasks`;
CREATE TABLE `Tasks` (
  `pkey` INT NOT NULL AUTO_INCREMENT,
  `userid` VARCHAR(20) NULL,
  `datecreated` DATETIME NULL,
  `TaskOwner` VARCHAR(20) NULL,
  `Taskname` LONGTEXT NULL,
  `datedue` DATE NULL,
  `Status` INT NULL,
  `Priority` INT NULL,
  `frequency` INT NULL,
  `taskdetails` LONGTEXT NULL,
  `alertedbymail` TINYINT(1) NULL,
  `lastactivity` DATETIME NULL,
  `time_from` VARCHAR(25) NULL,
  `time_to` VARCHAR(25) NULL,
  `location` LONGTEXT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `unit`;
CREATE TABLE `unit` (
  `code` CHAR(10) NULL,
  `descrip` CHAR(50) NOT NULL,
  `sns` CHAR(10) NULL,
  `pkey` INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `vatcategory`;
CREATE TABLE `vatcategory` (
  `vatc` CHAR(10) NOT NULL,
  `vat` DECIMAL(10,2) NOT NULL,
  `vatdecrip` CHAR(100) NOT NULL,
  `taxcatid` SMALLINT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`taxcatid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `www_users`;
CREATE TABLE `www_users` (
  `userid` VARCHAR(20) NOT NULL,
  `password` LONGTEXT NOT NULL,
  `realname` VARCHAR(35) NOT NULL,
  `customerid` VARCHAR(10) NULL,
  `supplierid` VARCHAR(10) NULL,
  `salesman` CHAR(3) NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(55) NULL DEFAULT NULL,
  `defaultlocation` VARCHAR(10) NOT NULL,
  `fullaccess` INT NOT NULL DEFAULT 1,
  `cancreatetender` SMALLINT NOT NULL DEFAULT 0,
  `lastvisitdate` DATETIME NULL DEFAULT NULL,
  `branchcode` VARCHAR(10) NOT NULL,
  `pagesize` VARCHAR(20) NOT NULL,
  `modulesallowed` VARCHAR(40) NOT NULL,
  `blocked` SMALLINT NOT NULL DEFAULT 0,
  `displayrecordsmax` INT NOT NULL DEFAULT 0,
  `theme` VARCHAR(30) NOT NULL,
  `language` VARCHAR(10) NOT NULL,
  `pdflanguage` SMALLINT NOT NULL DEFAULT 0,
  `department` INT NOT NULL DEFAULT 0,
  `Currentshiftno` INT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Unique indexes
ALTER TABLE `acct` ADD UNIQUE KEY `IX_acct` (`accno`);
ALTER TABLE `Dimensions` ADD UNIQUE KEY `IX_Dimensions` (`id`, `Code`);
ALTER TABLE `ProductionMaster` ADD UNIQUE KEY `IX_ProductionMaster` (`Batchno`);
ALTER TABLE `AssetsHeader` ADD INDEX(`documentno`);
ALTER TABLE `FixedAssetsLine` ADD INDEX(`documentno`);

-- Foreign keys
ALTER TABLE `audittrail` ADD CONSTRAINT `audittrail_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `www_users` (`userid`);
ALTER TABLE `Dimensions` ADD CONSTRAINT `FK_Dimensions_DimensionSetUp` FOREIGN KEY (`id`) REFERENCES `DimensionSetUp` (`id`);
ALTER TABLE `FixedAssetsLine` ADD CONSTRAINT `FK_FixedAssetsLine_AssetsHeader` FOREIGN KEY (`documentno`) REFERENCES `AssetsHeader` (`documentno`);
ALTER TABLE `Generalledger` ADD CONSTRAINT `FK_Generalledger_acct` FOREIGN KEY (`accountcode`) REFERENCES `acct` (`accno`);
ALTER TABLE `Generalledger` ADD CONSTRAINT `FK_Generalledger_acct1` FOREIGN KEY (`balaccountcode`) REFERENCES `acct` (`accno`);
ALTER TABLE `Generalledger` ADD CONSTRAINT `FK_Generalledger_currencies` FOREIGN KEY (`currencycode`) REFERENCES `currencies` (`currabrev`);
ALTER TABLE `ProdcutionMasterLine` ADD CONSTRAINT `FK_ProdcutionMasterLine_ProductionMaster` FOREIGN KEY (`Batchno`) REFERENCES `ProductionMaster` (`Batchno`);
ALTER TABLE `PurchaseLine` ADD CONSTRAINT `FK_PurchaseLine_PurchaseHeader` FOREIGN KEY (`documentno`) REFERENCES `PurchaseHeader` (`documentno`);
ALTER TABLE `reportcolumns` ADD CONSTRAINT `reportcolumns_ibfk_1` FOREIGN KEY (`reportid`) REFERENCES `reportheaders` (`reportid`);
ALTER TABLE `securitygroups` ADD CONSTRAINT `securitygroups_secroleid_fk` FOREIGN KEY (`secroleid`) REFERENCES `securityroles` (`secroleid`);
ALTER TABLE `securitygroups` ADD CONSTRAINT `securitygroups_tokenid_fk` FOREIGN KEY (`tokenid`) REFERENCES `securitytokens` (`tokenid`);
ALTER TABLE `stockmaster` ADD CONSTRAINT `FK_stockmaster_inventorypostinggroup` FOREIGN KEY (`postinggroup`) REFERENCES `inventorypostinggroup` (`code`);

SET FOREIGN_KEY_CHECKS=1;