-- Add the editable COA version identifier to the existing system settings.
-- Default value is exactly 10 characters: COA-V1.0.0.
-- Safe to run more than once; an existing user-defined value is preserved.
INSERT INTO config (confname, confvalue, type)
VALUES ('COA Version ID', 'COA-V1.0.0', 'string')
ON DUPLICATE KEY UPDATE confname = VALUES(confname);
