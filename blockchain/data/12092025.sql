ALTER TABLE test_results
ADD UNIQUE KEY uq_test_results (TestID, HeaderID, SampleID, StandardID, ParameterID);

ALTER TABLE sample_tests
ADD UNIQUE KEY uq_sample_tests (SampleID, StandardID, HeaderID);

ALTER TABLE `sample_tests` 
CHANGE COLUMN `SampleFileKey` `SampleFileKey` VARCHAR(255) NULL DEFAULT 'icons8-no-image-100.png' ;

ALTER TABLE `baseparameters` 
ADD UNIQUE INDEX `ParameterName_UNIQUE` (`ParameterName` ASC) VISIBLE;

INSERT INTO `baseparameters` (`ParameterName`) 
SELECT ParameterName FROM testparameters 
ON DUPLICATE KEY UPDATE  ParameterName = VALUES(ParameterName) 



UPDATE testparameters T
JOIN baseparameters B ON B.ParameterName = T.ParameterName
SET T.BaseID = B.ParameterID;



CREATE TABLE `standard_parameter_matrix_config` (
  `ConfigID` int NOT NULL AUTO_INCREMENT,
  `ParameterID` int NOT NULL,
  `MatrixID` int NOT NULL,
  `IsDefault` tinyint(1) DEFAULT '0',
  `Notes` text,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ConfigID`),
  KEY `fk_spm_param_idx` (`ParameterID`),
  KEY `fk_spm_matrix` (`MatrixID`),
  CONSTRAINT `fk_spm_matrix` FOREIGN KEY (`MatrixID`) REFERENCES `parametermatrix` (`ParameterID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 

