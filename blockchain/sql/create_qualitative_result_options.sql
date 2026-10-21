-- Qualitative result options for ResultType = 'qualitativeField'.
-- Run once against the LIMS database. Existing test_results rows are not modified.
-- Add options by INSERTing rows; hide options by setting IsActive = 0.
-- Do not delete options that may already be referenced by historical results.

CREATE TABLE IF NOT EXISTS qualitative_result_options (
    OptionID INT NOT NULL AUTO_INCREMENT,
    OptionValue VARCHAR(100) NOT NULL,
    OptionLabel VARCHAR(150) NOT NULL,
    DisplayOrder INT NOT NULL DEFAULT 0,
    IsActive TINYINT(1) NOT NULL DEFAULT 1,
    CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (OptionID),
    UNIQUE KEY uq_qualitative_result_options_value (OptionValue),
    KEY idx_qualitative_result_options_active_order (IsActive, DisplayOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO qualitative_result_options
    (OptionValue, OptionLabel, DisplayOrder, IsActive)
VALUES
    ('ND', 'Not Detected', 1, 1),
    ('Absent', 'Absent', 2, 1),
    ('Detected', 'Detected', 3, 1),
    ('Below Limit', 'Below Limit', 4, 1),
    ('Trace', 'Trace', 5, 1),
    ('Above Limit', 'Above Limit', 6, 1),
    ('Inconclusive', 'Inconclusive', 7, 1),
    ('Error', 'Error', 8, 1),
    ('Invalid', 'Invalid', 9, 1);

-- Example: hide an option without deleting it:
-- UPDATE qualitative_result_options SET IsActive = 0 WHERE OptionValue = 'Trace';

-- Example: add a new option:
-- INSERT INTO qualitative_result_options (OptionValue, OptionLabel, DisplayOrder, IsActive)
-- VALUES ('Not Applicable', 'Not Applicable', 10, 1);

-- Qualitative result forms should load selectable options using:
-- SELECT OptionValue, OptionLabel
-- FROM qualitative_result_options
-- WHERE IsActive = 1
-- ORDER BY DisplayOrder, OptionLabel;
