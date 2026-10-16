-- ImaraLIMS: soft-hide test parameters instead of deleting them.
-- Run once against the lims_encrpted database before deploying the PHP changes.
-- Existing rows remain active; no sample_tests or test_results rows are modified.

ALTER TABLE testparameters
    ADD COLUMN IsActive TINYINT(1) NOT NULL DEFAULT 1;

-- Verify status values after migration:
-- SELECT IsActive, COUNT(*) FROM testparameters GROUP BY IsActive;
