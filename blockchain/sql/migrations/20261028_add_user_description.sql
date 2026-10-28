-- Migration: Add user description for COA signature designation
-- Apply this migration once to the LIMS database before deploying the updated PHP files.

ALTER TABLE `users`
    ADD COLUMN `user_description` VARCHAR(255) NULL DEFAULT NULL
    AFTER `department`;
