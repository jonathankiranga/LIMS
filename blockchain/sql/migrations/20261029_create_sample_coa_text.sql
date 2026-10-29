-- Persist editable COA text at sample approval level.
-- Apply once to the LIMS database before deploying the application changes.
CREATE TABLE IF NOT EXISTS sample_coa_text (
    HeaderID INT NOT NULL,
    SampleID VARCHAR(100) NOT NULL,
    ResultsInterpretation MEDIUMTEXT NULL,
    StatementOfConformity MEDIUMTEXT NULL,
    Notes MEDIUMTEXT NULL,
    UpdatedBy VARCHAR(100) NULL,
    UpdatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (HeaderID, SampleID),
    KEY idx_sample_coa_text_sample (SampleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
