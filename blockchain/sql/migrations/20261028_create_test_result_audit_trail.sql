-- Result-entry audit trail for chemical and biological result grids.
-- Apply once to the LIMS database before deploying the updated PHP files.
CREATE TABLE IF NOT EXISTS test_result_audit_trail (
    AuditID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    resultsID VARCHAR(64) NOT NULL,
    SampleID VARCHAR(64) NOT NULL,
    ParameterName VARCHAR(255) NOT NULL,
    ResultType VARCHAR(40) NOT NULL,
    ResultValue TEXT NOT NULL,
    ActionType ENUM('continue', 'review') NOT NULL,
    UserID VARCHAR(255) NOT NULL,
    UserName VARCHAR(255) NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (AuditID),
    KEY idx_result_audit_resultsid (resultsID),
    KEY idx_result_audit_sample (SampleID),
    KEY idx_result_audit_created (CreatedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
