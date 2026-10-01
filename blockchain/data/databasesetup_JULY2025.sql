CREATE SCHEMA `lims_encrpted` ;
use `lims_encrpted` ;

CREATE TABLE blockchain_ledger (
    block_id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    previous_hash CHAR(64) NOT NULL,
    current_hash CHAR(64) NOT NULL
);

ALTER TABLE blockchain_ledger 
ADD COLUMN digital_signature VARCHAR(512) NOT NULL;

ALTER TABLE blockchain_ledger
ADD COLUMN previous_version_id INT NULL,   -- Link to the previous version (if updated)
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE, -- Flag to mark soft-deletion
ADD COLUMN status ENUM('active', 'superseded') DEFAULT 'active'; -- Marks active or outdated record

ALTER TABLE blockchain_ledger
ADD COLUMN `encrypted_data` TEXT AFTER digital_signature;

CREATE TABLE audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    block_id INT NOT NULL,
    action_type ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id VARCHAR(255) NOT NULL,
    status ENUM('SUCCESS', 'FAILED') NOT NULL,
    reason TEXT
);

-- Alter the existing audit_log table
ALTER TABLE audit_log
    ADD COLUMN sample_id VARCHAR(255) AFTER action_type, -- Link actions to specific samples
    ADD COLUMN data_hash VARCHAR(64) AFTER sample_id   -- Store the hash of the associated data
 

DELIMITER //

CREATE TRIGGER prevent_update
BEFORE UPDATE ON blockchain_ledger
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'Updates are not allowed on blockchain records';
END//

CREATE TRIGGER prevent_delete
BEFORE DELETE ON blockchain_ledger
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'Deletes are not allowed on blockchain records';
END//

DELIMITER ;


-- Users Table (Stores user information)
-- Table to store user information
CREATE TABLE `users` (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    telephone varchar(12) null,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role INT DEFAULT 10,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    full_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



-- Password Reset Table (Stores password reset tokens)
CREATE TABLE Password_Reset (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reset_token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    is_used TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)  ON DELETE RESTRICT
);


CREATE TABLE `systypes` (
  `typeid` smallint NOT NULL DEFAULT '0',
  `typename` char(50) NOT NULL,
  `typeno` int NOT NULL DEFAULT '1',
  `prefix` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE standard_methods (
    MethodID INT AUTO_INCREMENT PRIMARY KEY,  -- Automatically increment StatusID
    standard_method varchar(100) 
);

-- Test Standards Master Table
CREATE TABLE TestStandards (
    StandardID INT AUTO_INCREMENT PRIMARY KEY,
    StandardCode varchar(100) DEFAULT NULL,
    StandardName VARCHAR(255) NOT NULL,
    Description TEXT,
    ApplicableRegulation VARCHAR(255),
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME ON UPDATE CURRENT_TIMESTAMP
);
ALTER TABLE `TestStandards` 
ADD COLUMN sm INT REFERENCES standard_methods(MethodID);

-- Test Parameters Table
CREATE TABLE TestParameters (
    ParameterID INT AUTO_INCREMENT PRIMARY KEY,
    BaseID INT NOT NULL,
    ParameterName VARCHAR(255) NOT NULL,           -- Name of the parameter
    StandardID INT NOT NULL,                       -- Links to the Test Standards table
    Limits VARCHAR(255),                           -- General acceptable range (e.g., "6.5-8.5")
    MinLimit DECIMAL(10, 2),                       -- Minimum acceptable value for the parameter
    MaxLimit DECIMAL(10, 2),                       -- Maximum acceptable value for the parameter
    Method VARCHAR(255),                           -- Testing method or procedure
    Vital BOOLEAN DEFAULT 0,                       -- Indicates if the parameter is critical
    Category ENUM('microbiological','chemical') DEFAULT 'chemical',  -- Parameter category (e.g., Chemistry, Physics)
    MRL DECIMAL(10, 2) DEFAULT NULL,               -- Method Reporting Limit (MRL)
    MRLUnit VARCHAR(50) DEFAULT NULL,              -- Unit for MRL (e.g., "ppm", "µg/L")
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,  -- Record creation timestamp
    UpdatedAt DATETIME ON UPDATE CURRENT_TIMESTAMP,-- Record update timestamp
    UnitOfMeasure varchar(50);
    FOREIGN KEY (StandardID) REFERENCES TestStandards(StandardID) ON DELETE RESTRICT ,
    FOREIGN KEY (BaseID) REFERENCES BaseParameters(ParameterID) ON DELETE RESTRICT
);



CREATE TABLE `debtors` (
  `type` char(1) DEFAULT NULL,
  `istaff` int DEFAULT NULL,
  `cleared` tinyint(1) DEFAULT NULL,
  `pinno` char(20) DEFAULT NULL,
  `itemcode` varchar(20) DEFAULT NULL,
  `class` char(10) DEFAULT NULL,
  `cardadd` char(10) DEFAULT NULL,
  `contact` char(100) DEFAULT NULL,
  `defaultgl` char(10) DEFAULT NULL,
  `currbal` decimal(13,2) DEFAULT NULL,
  `flag` char(6) DEFAULT NULL,
  `creditlimit` decimal(14,2) DEFAULT NULL,
  `customer` varchar(50) DEFAULT NULL,
  `status` char(5) DEFAULT NULL,
  `firstn` char(10) DEFAULT NULL,
  `middlen` char(15) DEFAULT NULL,
  `lastn` char(10) DEFAULT NULL,
  `phone` char(15) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `company` char(100) DEFAULT NULL,
  `altcontact` char(100) DEFAULT NULL,
  `email` char(100) DEFAULT NULL,
  `city` char(100) DEFAULT NULL,
  `country` char(100) DEFAULT NULL,
  `preffpay` char(10) DEFAULT NULL,
  `crdcardno` char(10) DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT NULL,
  `namecrd` char(10) DEFAULT NULL,
  `postcode` char(100) DEFAULT NULL,
  `curr_cod` char(10) DEFAULT NULL,
  `curr_rat` decimal(9,5) DEFAULT NULL,
  `id` char(1) DEFAULT NULL,
  `i_n_t` char(1) DEFAULT NULL,
  `typ` char(2) DEFAULT NULL,
  `balance` decimal(18,4) DEFAULT NULL,
  `age1` bigint DEFAULT NULL,
  `age2` decimal(18,4) DEFAULT NULL,
  `age3` decimal(18,4) DEFAULT NULL,
  `age4` decimal(18,4) DEFAULT NULL,
  `pkey` bigint NOT NULL,
  `islocal` tinyint(1) DEFAULT NULL,
  `username` char(20) DEFAULT NULL,
  `customerposting` varchar(20) DEFAULT NULL,
  `salesman` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*
SELECT tp.ParameterName, tp.Limits, tp.Method, ts.StandardName 
FROM TestParameters tp
INNER JOIN TestStandards ts ON tp.StandardID = ts.StandardID
WHERE ts.StandardID = 2;
*/
CREATE TABLE `Sample_Header` (
    `HeaderID` INT AUTO_INCREMENT PRIMARY KEY,
    `Date` DATETIME NOT NULL,
    `DocumentNo` VARCHAR(50) NOT NULL,
    `CustomerName` VARCHAR(255) NOT NULL,
    `CustomerID` VARCHAR(50) NOT NULL,
    `SampledBy` VARCHAR(100) NOT NULL,
    `SamplingMethod` VARCHAR(255) NOT NULL,
    `SamplingDate` DATETIME NOT NULL,
    `OrderNo` VARCHAR(50) NOT NULL,
    `ScopeOfWork` TEXT,
    `NumberOfSamples` INT NOT NULL,
    `User_name` VARCHAR(255) NOT NULL  -- User name (can be base64 encoded or raw signature)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Sample_Tests` (
    `TestID` INT AUTO_INCREMENT PRIMARY KEY,
    `HeaderID` INT NOT NULL,
    `SampleID` varchar(20) NOT NULL,
    `StandardID` INT NOT NULL,,
    `SampleFileKey` VARCHAR(255) NOT NULL,
    `SampleFee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `SKU` VARCHAR(100),
    `BatchNo` VARCHAR(50),
    `BatchSize` INT,
    `ManufactureDate` DATETIME,
    `ExpDate` DATETIME,
    `ExternalSample` VARCHAR(255),
    `coa_email_send_count` INT NOT NULL DEFAULT 0,
    `coa_last_emailed_at` DATETIME NULL,
    `coa_last_email_to` VARCHAR(255) NULL,
    `coa_last_email_status` VARCHAR(20) NULL,
    `coa_last_email_message` TEXT NULL,
    `User_name` VARCHAR(255) NOT NULL,  -- User name (can be base64 encoded or raw signature)
    FOREIGN KEY (`HeaderID`) REFERENCES `Sample_Header`(`HeaderID`)  ON DELETE RESTRICT,
    FOREIGN KEY (`StandardID`) REFERENCES `testparameters`(`StandardID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*
ALTER TABLE `sample_tests` DROP FOREIGN KEY `sample_tests_ibfk_2`; 
ALTER TABLE `sample_tests`
  DROP `ParameterID`,
  DROP `MRL_Result`,
  DROP `StandardLimit_Result`,
  DROP `ResultStatus`,
  DROP `RangeResult`; 
*/
    CREATE TABLE `Test_Results` (
    `resultsID` INT AUTO_INCREMENT PRIMARY KEY,
    `TestID` INT NOT NULL,
    `HeaderID` INT NOT NULL,
    `SampleID` VARCHAR(20) NOT NULL,
    `StandardID` INT NOT NULL,
    `ParameterID` INT NOT NULL,
    `MRL_Result` DECIMAL(10, 4) NULL,
    `ResultStatus` ENUM('ND', 'Absent', 'Detected', 'Below Limit', 'Detected Range', 'Trace', 'Above Limit', 'Inconclusive', 'Error', 'Invalid') NULL,
    `RangeResult` VARCHAR(255) NULL,
    `User_name` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`TestID`) REFERENCES `Sample_Tests`(`TestID`) ON DELETE RESTRICT,
    FOREIGN KEY (`HeaderID`) REFERENCES `Sample_Header`(`HeaderID`) ON DELETE RESTRICT,
    FOREIGN KEY (`ParameterID`) REFERENCES `testparameters`(`ParameterID`) ON DELETE RESTRICT,
    FOREIGN KEY (`StandardID`) REFERENCES `testparameters`(`StandardID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `Test_Results` 
ADD COLUMN StatusID INT REFERENCES sample_statuses(StatusID);

ALTER TABLE test_results
ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Modify testparameters add unitsofmeasure

/*To verify bloch chain*/


CREATE TABLE sample_statuses (
    StatusID INT AUTO_INCREMENT PRIMARY KEY,  -- Automatically increment StatusID
    StatusOrder INT UNIQUE NOT NULL,           -- Ensure unique and not NULL for order
    StatusName VARCHAR(255) NOT NULL,          -- Ensure that StatusName is required
    Description TEXT,                          -- Optional: Store a description of the status
    ColorCode VARCHAR(7)                       -- Optional: Store a hex color code for UI
);

INSERT INTO sample_statuses (StatusOrder, StatusName, Description, ColorCode) VALUES 
(1, 'Sample Registration', 'Sample has been formally registered in the system', '#3366FF'),
(2, 'Scheduled', 'Sample testing has been scheduled', '#CCCC00'),
(3, 'Received', 'Sample has been received at the laboratory', '#0099FF'),
(4, 'In Transit', 'Sample is being transported to another location', '#6600FF'),
(5, 'Under Testing', 'Sample is being tested in the lab', '#FF9900'),
(6, 'Re-Test Required', 'Sample needs to be re-tested due to initial testing failure', '#F1A100'),
(7, 'Failed', 'Sample did not meet requirements', '#FF0000'),
(8, 'On Hold', 'Sample testing is on hold due to unforeseen circumstances', '#FF3399'),
(9, 'Under Review', 'Sample results are under review by the team', '#FF6600'),
(10, 'Approved', 'Sample testing results have been approved', '#008000'),
(11, 'Cancelled', 'Sample testing has been cancelled', '#B22222'),
(12, 'Completed', 'Sample testing is complete', '#33CC33'),
(13, 'Chain of Custody Verified', 'The sample’s chain of custody has been verified', '#0044CC'),
(14, 'Finalized', 'Sample results are finalized and documented', '#003366'),
(15, 'Archived', 'Sample testing records are archived for future reference', '#808080');


ALTER TABLE `Sample_Header` 
ADD COLUMN previous_version_id INT NULL,   -- Link to the previous version (if updated)
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE -- Flag to mark soft-deletion

ALTER TABLE Sample_Tests
ADD COLUMN previous_version_id INT NULL,   -- Link to the previous version (if updated)
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE, -- Flag to mark soft-deletion
--ADD COLUMN StatusID INT REFERENCES sample_statuses(StatusID);

CREATE INDEX idx_sampleid ON sample_tests(SampleID);

ALTER TABLE sample_tests
ADD COLUMN disposal_reason TEXT DEFAULT NULL, -- Reason for disposal
ADD COLUMN disposal_timestamp DATETIME DEFAULT NULL, -- Timestamp of disposal
ADD COLUMN disposed_by INT DEFAULT NULL, -- User ID of the person disposing of the sample
ADD CONSTRAINT fk_disposed_by_user FOREIGN KEY (disposed_by) REFERENCES users(user_id); -- Foreign key to users table


--ALTER TABLE `sample_tests` 
--DROP COLUMN `StatusID`;


CREATE TABLE sample_custody (
    `CustodyID` INT AUTO_INCREMENT PRIMARY KEY,
    `SampleID` varchar(20),  -- Foreign key to Sample Table
    `HandlerName` VARCHAR(255),  -- Person who is handling the sample
    `Action` VARCHAR(255),  -- Action taken (e.g., Received, Transported, Tested, etc.)
    `DateTime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Date and time of action
    `Location` VARCHAR(255),  -- Where the sample is located or where the action occurred
    `Notes` TEXT
);

ALTER TABLE sample_custody
ADD CONSTRAINT fk_sample_id
FOREIGN KEY (SampleID) REFERENCES sample_tests(SampleID);


CREATE TABLE environmental_parameters (
    `param_id` INT AUTO_INCREMENT PRIMARY KEY,
    `temperature` FLOAT NOT NULL,
    `humidity` FLOAT NOT NULL,
    `notes` TEXT,
    `recorded_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE Sample_Tests
ADD COLUMN environmental_id INT REFERENCES environmental_parameters(param_id);

CREATE INDEX idx_environmental_id ON Sample_Tests(environmental_id);

CREATE TABLE chain_of_custody (
    `custody_id` INT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each custody record
    `SampleID` VARCHAR(20) NOT NULL,            -- ID of the sample
    `handler_name` VARCHAR(255) NOT NULL,        -- Name of the handler
    `action` VARCHAR(255) NOT NULL,              -- Description of the action taken
    `location` VARCHAR(255) NOT NULL,            -- Current location of the sample
    `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP, -- Automatically record the timestamp
    `notes` TEXT DEFAULT NULL,                   -- Optional additional notes
    FOREIGN KEY (SampleID) REFERENCES sample_tests(SampleID)
) ENGINE=InnoDB;


CREATE TABLE sample_schedule (
    ScheduleID INT AUTO_INCREMENT PRIMARY KEY,
    SampleID VARCHAR(20) NOT NULL,
    ScheduleDate DATE NOT NULL,
    Notes TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SampleID) REFERENCES sample_tests(SampleID)
);


ALTER TABLE users
ADD COLUMN department ENUM('chemical', 'biological', 'admin', 'guest') NOT NULL DEFAULT 'guest';

CREATE TABLE samples_received (
    id INT AUTO_INCREMENT PRIMARY KEY,       -- Unique identifier for each record
    sample_id VARCHAR(20) NOT NULL,          -- Sample ID, assuming it's a string
    storage_location VARCHAR(100),           -- Location where the sample is stored
    remarks TEXT,                            -- Remarks about the sample
    assigned_department ENUM('chemical', 'biological', 'admin', 'guest') NOT NULL, -- Department options
    condition ENUM('intact', 'damaged', 'other') NOT NULL, -- Condition upon arrival
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Timestamp for when the record was created
);

ALTER TABLE `samples_received` 
CHANGE COLUMN `sample_id` `sample_id` VARCHAR(20) NOT NULL ;


ALTER TABLE `test_results` 
CHANGE COLUMN `MRL_Result` `MRL_Result` VARCHAR(20) NULL DEFAULT NULL ,
CHANGE COLUMN `StandardLimit_Result` `StandardLimit_Result` VARCHAR(20) NULL DEFAULT NULL ;





-- SOP Table
CREATE TABLE SOPs (
    sop_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    document_url TEXT,  -- Link to SOP document, or you can store the document as a BLOB
    version_number VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- SOP Access Log Table (for audit and tracking)
CREATE TABLE SOP_Access_Log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    sop_id INT,
    user_id INT,
    action VARCHAR(50),  -- 'viewed', 'updated', 'approved'
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sop_id) REFERENCES SOPs(sop_id)
);

-- alert templates 15/01/2025

CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,  -- e.g., 'test_approved'
    subject VARCHAR(255) NOT NULL,      -- Email subject
    body TEXT NOT NULL,                -- Email body
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,   -- Auto-incrementing unique ID for each event
    name VARCHAR(255) NOT NULL,          -- Name of the event (e.g., "Test Approved", "Sample Received")
    description TEXT,                    -- Optional description of what the event does
    action VARCHAR(255),                 -- The action that should occur (e.g., "send_email", "generate_report")
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when the event was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP  -- Timestamp when the event was last updated
);

INSERT INTO events (name, description, action) VALUES
('Test Approved', 'Triggered when a test is approved', 'send_email'),
('Sample Received', 'Triggered when a sample is received in the lab', 'send_email'),
('Payment Request', 'Triggered when payment is requested after test approval', 'send_email');

CREATE TABLE event_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,   -- Auto-incrementing unique ID for each event log
    event_id INT,                        -- The event that was triggered (foreign key from events table)
    status ENUM('success', 'failure') DEFAULT 'success',  -- The result of the event action (success or failure)
    error_message TEXT,                  -- Any error message in case of failure
    triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- Timestamp of when the event was triggered
    FOREIGN KEY (event_id) REFERENCES events(id)  -- Link to the events table
);

CREATE TABLE certificate_email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    TestID INT NOT NULL,
    report_option TINYINT NOT NULL,
    recipient_email VARCHAR(255) NULL,
    subject VARCHAR(255) NULL,
    status ENUM('success', 'failure') NOT NULL,
    message TEXT NULL,
    sent_by VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_certificate_email_logs_testid (TestID)
);

CREATE TABLE quote_headers (
    quote_id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(30) NOT NULL,
    customer_id VARCHAR(20) NULL,
    client_name VARCHAR(255) NOT NULL,
    client_attention VARCHAR(255) NULL,
    client_address TEXT NULL,
    client_city VARCHAR(255) NULL,
    project_name VARCHAR(255) NULL,
    matrix_name VARCHAR(255) NULL,
    turnaround_time VARCHAR(255) NULL,
    quote_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_percent DECIMAL(7,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    terms_text TEXT NULL,
    created_by VARCHAR(100) NULL,
    updated_by VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_quote_number (quote_number)
);

CREATE TABLE quote_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    line_order INT NOT NULL DEFAULT 1,
    parameter_id INT NULL,
    standard_id INT NULL,
    test_code VARCHAR(100) NULL,
    description_text TEXT NOT NULL,
    method_text VARCHAR(255) NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    INDEX idx_quote_items_quote_id (quote_id),
    FOREIGN KEY (quote_id) REFERENCES quote_headers(quote_id) ON DELETE CASCADE
);

----- ADD a MASTER TABLE BASE FOR PARAMTER definations

-- Test Parameters Table
CREATE TABLE BaseParameters (
    ParameterID INT AUTO_INCREMENT PRIMARY KEY,
    ParameterName VARCHAR(255) NOT NULL,   -- Name of the parameter
    NeutralityID INT,   
    TdsID INT,   
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,  -- Record creation timestamp
    UpdatedAt DATETIME ON UPDATE CURRENT_TIMESTAMP -- Record update timestamp
 );
  
ALTER TABLE `test_results`
ADD COLUMN BaseID INT REFERENCES BaseParameters(ParameterID) ON DELETE RESTRICT;

ALTER TABLE `sample_tests`  ADD COLUMN BaseID INT ;

 
-- ParameterMatrix Table 31.07.2025
CREATE TABLE ParameterMatrix (
    ParameterID INT AUTO_INCREMENT PRIMARY KEY,
    ParameterName VARCHAR(255) NOT NULL,   -- Name of the parameter
    ParentID INT,   
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,  -- Record creation timestamp
    UpdatedAt DATETIME ON UPDATE CURRENT_TIMESTAMP -- Record update timestamp
 );

ALTER TABLE `TestParameters` ADD COLUMN BaseID INT REFERENCES BaseParameters(ParameterID) ON DELETE RESTRICT;
ALTER TABLE `TestParameters` ADD COLUMN matrixID INT REFERENCES ParameterMatrix(ParameterID) ON DELETE RESTRICT;

 

CREATE TABLE standard_parameter_matrix_config (
  ConfigID INT AUTO_INCREMENT PRIMARY KEY,
  StandardID INT NOT NULL,
  ParameterID INT NOT NULL,
  MatrixID INT NOT NULL,
  IsDefault TINYINT(1) DEFAULT 0,
  Notes TEXT,
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_spm_standard FOREIGN KEY (StandardID) REFERENCES teststandards(StandardID) ON DELETE CASCADE,
  CONSTRAINT fk_spm_param FOREIGN KEY (ParameterID) REFERENCES testparameters(ParameterID) ON DELETE CASCADE,
  CONSTRAINT fk_spm_matrix FOREIGN KEY (MatrixID) REFERENCES parametermatrix(ParameterID) ON DELETE CASCADE,

  UNIQUE KEY uq_std_param_matrix (StandardID, ParameterID, MatrixID)
);

--10/08/2025

ALTER TABLE teststandards ADD UNIQUE (StandardName);
ALTER TABLE parametermatrix ADD UNIQUE (ParameterName);

ALTER TABLE `sample_header` 
DROP COLUMN `NumberOfSamples`;
