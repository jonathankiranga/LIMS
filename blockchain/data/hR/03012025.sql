

CREATE TABLE Machines (
    machine_id INT AUTO_INCREMENT PRIMARY KEY,
    machine_name VARCHAR(255) NOT NULL
);

-- Control Sample Results table to track test results for control samples
CREATE TABLE ControlSampleResults (
    result_id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT,
    sample_name VARCHAR(255),
    known_value DECIMAL(10, 2),
    measured_value DECIMAL(10, 2),
    deviation DECIMAL(10, 2), -- Measured - Known value
    result_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES Machines(machine_id)
);

CREATE TABLE equipmentusage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT,               -- Foreign key to the Equipment table
    usage_start_time DATETIME,
    usage_end_time DATETIME,
    duration_minutes INT,           -- Duration of equipment usage in minutes
    user VARCHAR(255),              -- User/Technician who used the equipment
    remarks TEXT,                   -- Additional comments
    FOREIGN KEY (equipment_id) REFERENCES Machines(machine_id)
);

CREATE TABLE Equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    machine_id INT,
    last_calibration DATE,
    predicted_calibration DATE,
    deviation_trend VARCHAR(50),
    usage_hours INT,
    FOREIGN KEY (machine_id) REFERENCES Machines(machine_id)  
);

CREATE TABLE company_master (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    telephone VARCHAR(50),
    authorized_signature_image LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Insert Example Data into the Company Master Table --

INSERT INTO company_master (company_name, address, telephone, authorized_signature_image)
VALUES ('', ', Kenya', '', LOAD_FILE('/path/to/signature2.png'));


ALTER TABLE `users`
ADD COLUMN signature_path varchar(50);

ALTER TABLE `users`
MODIFY COLUMN signature_image VARCHAR(50);

ALTER TABLE `users`
add COLUMN signature_path varchar(50);


--11102025
ALTER TABLE `samples_received` 
CHANGE COLUMN `assigned_department` `assigned_department` 
ENUM('chemical', 'biological', 'admin', 'guest', 'microbiological') NOT NULL ;



--14/01/2025
ALTER TABLE `sample_header` 
CHANGE COLUMN `CustomerID` `CustomerID` VARCHAR(20) NOT NULL ;

--14/01/2025

CREATE TABLE standard_methods (
    MethodID INT AUTO_INCREMENT PRIMARY KEY,  -- Automatically increment StatusID
    standard_method varchar(100) 
);


ALTER TABLE `TestStandards` 
ADD COLUMN sm INT REFERENCES standard_methods(MethodID);

--16022025

alter table company_master
ADD COLUMN address1 TEXT  NULL;
ADD COLUMN address2 TEXT  NULL;
ADD COLUMN address3 TEXT  NULL;
ADD COLUMN email VARCHAR(100)  NULL;
ADD COLUMN technician int  NULL;
ADD COLUMN technician2  int  NULL;

ALTER TABLE config ADD COLUMN type ENUM('number', 'string', 'path', 'text', 'date') DEFAULT 'string';

UPDATE `config` SET `type` = 'text' WHERE (`confname` = 'Conformity');
UPDATE `config` SET `type` = 'date' WHERE (`confname` = 'DB_Maintenance_LastRun');
UPDATE `config` SET `type` = 'number' WHERE (`confname` = 'DefaultDisplayRecordsMax');
UPDATE `config` SET `type` = 'date' WHERE (`confname` = 'FinancialYearBegins');
UPDATE `config` SET `type` = 'path' WHERE (`confname` = 'HighFile');
UPDATE `config` SET `type` = 'path' WHERE (`confname` = 'LowFile');
UPDATE `config` SET `type` = 'text' WHERE (`confname` = 'RomalpaClause');
UPDATE `config` SET `type` = 'date' WHERE (`confname` = 'UpdateCurrencyRatesDaily');

--17102025
alter table sample_tests
ADD COLUMN datetestended  DATETIME  NULL;
--17012025
alter table company_master
ADD COLUMN authorisation  int  NULL;

--20012025


CREATE TABLE test_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resultsID INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE test_assignments ADD UNIQUE KEY unique_user_test (user_id, resultsID);

UPDATE `menu_items` SET `parent_id` = '9' WHERE (`id` = '4');
