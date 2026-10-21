-- Safe, additive migration: qualitative result options only.
-- Target database: lims_encrpted (verify the selected database before running).
-- This migration does NOT ALTER existing tables or modify test_results.
-- Back up the database before applying any production migration.
-- Safe to rerun: table creation is conditional and seed rows are inserted only if absent.
--
-- After running, verify with:
-- SELECT OptionID, OptionValue, OptionLabel, IsActive
-- FROM qualitative_result_options ORDER BY DisplayOrder, OptionLabel;

CREATE TABLE IF NOT EXISTS qualitative_result_options (
    OptionID INT NOT NULL AUTO_INCREMENT,
    OptionValue VARCHAR(100) NOT NULL,
    OptionLabel VARCHAR(150) NOT NULL,
    DisplayOrder INT NOT NULL DEFAULT 0,
    IsActive TINYINT(1) NOT NULL DEFAULT 1,
    CreatedAt DATETIME NULL DEFAULT NULL,
    UpdatedAt DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (OptionID),
    UNIQUE KEY uq_qualitative_result_options_value (OptionValue),
    KEY idx_qualitative_result_options_active_order (IsActive, DisplayOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preserve existing rows and any admin changes if this migration is rerun.
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

-- Hide an option without deleting it:
-- UPDATE qualitative_result_options SET IsActive = 0 WHERE OptionValue = 'Trace';

-- Add an option:
-- INSERT INTO qualitative_result_options
--     (OptionValue, OptionLabel, DisplayOrder, IsActive)
-- VALUES ('Not Applicable', 'Not Applicable', 10, 1);

-- Forms should select active options only:
-- SELECT OptionValue, OptionLabel
-- FROM qualitative_result_options
-- WHERE IsActive = 1
-- ORDER BY DisplayOrder, OptionLabel;
