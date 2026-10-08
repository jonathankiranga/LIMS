-- Add accreditation classification to each standard-specific test parameter.
-- Existing parameters default to NOT ACCREDITED until explicitly classified.

ALTER TABLE `testparameters`
ADD COLUMN `AccreditationStatus` VARCHAR(30)
NOT NULL DEFAULT 'not_accredited'
AFTER `Category`;

-- Application values:
-- accredited
-- not_accredited
-- contracted
