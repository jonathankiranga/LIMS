-- ===================================================================
-- Kenyan Statutory Leave Management - Fix Migration
-- Drop and recreate tables in correct order to avoid FK issues
-- Run this AFTER the partial migration failed
-- ===================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------
-- Step 1: Drop all leave management tables in correct order
-- -------------------------------------------------------------------

DROP TABLE IF EXISTS `prlleaveescalations`;
DROP TABLE IF EXISTS `prlleaveaudit`;
DROP TABLE IF EXISTS `prlleavenotifications`;
DROP TABLE IF EXISTS `prlleaveapprovaltrans`;
DROP TABLE IF EXISTS `prlleaveencashments`;
DROP TABLE IF EXISTS `prlleavecarrovers`;
DROP TABLE IF EXISTS `prlleaveapplications`;
DROP TABLE IF EXISTS `prlleavebalances`;
DROP TABLE IF EXISTS `prlleaveworkflows`;
DROP TABLE IF EXISTS `prlweekends`;
DROP TABLE IF EXISTS `prlkenyaholidays`;
DROP TABLE IF EXISTS `prlleavetypes`;

-- -------------------------------------------------------------------
-- Step 2: Recreate all tables in correct order
-- -------------------------------------------------------------------

-- 1. LEAVE TYPES
CREATE TABLE `prlleavetypes` (
  `leavetype_id` INT NOT NULL AUTO_INCREMENT,
  `leavetype_code` VARCHAR(20) NOT NULL UNIQUE,
  `leavetype_name` VARCHAR(100) NOT NULL,
  `leavetype_name_short` VARCHAR(30) NOT NULL,
  `default_days` INT NOT NULL DEFAULT 0,
  `paid_leave` TINYINT(1) NOT NULL DEFAULT 1,
  `requires_medical_certificate` TINYINT(1) NOT NULL DEFAULT 0,
  `requires_approval` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_carryover` TINYINT(1) NOT NULL DEFAULT 1,
  `max_carryover_days` INT NOT NULL DEFAULT 0,
  `carryover_expiry_months` INT DEFAULT NULL,
  `pro_rata_applicable` TINYINT(1) NOT NULL DEFAULT 0,
  `encashable` TINYINT(1) NOT NULL DEFAULT 0,
  `max_encashment_days` INT DEFAULT NULL,
  `requires_handover` TINYINT(1) NOT NULL DEFAULT 0,
  `min_service_months` INT NOT NULL DEFAULT 0,
  `statutory_leave` TINYINT(1) NOT NULL DEFAULT 0,
  `can_be_advanced` TINYINT(1) NOT NULL DEFAULT 0,
  `gender_applicable` ENUM('ALL','MALE','FEMALE') DEFAULT 'ALL',
  `color_code` VARCHAR(7) DEFAULT '#007bff',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`leavetype_id`),
  KEY `idx_leave_code` (`leavetype_code`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `prlleavetypes` (`leavetype_code`, `leavetype_name`, `leavetype_name_short`, `default_days`, `paid_leave`, `requires_medical_certificate`, `allow_carryover`, `max_carryover_days`, `pro_rata_applicable`, `encashable`, `requires_handover`, `min_service_months`, `statutory_leave`, `can_be_advanced`, `gender_applicable`, `color_code`, `sort_order`, `description`) VALUES
('ANNUAL', 'Annual Leave', 'Annual', 28, 1, 0, 1, 7, 1, 1, 1, 1, 1, 1, 'ALL', '#28a745', 1, 'Annual leave as per Employment Act 2007 - minimum 28 working days'),
('MATERNITY', 'Maternity Leave', 'Maternity', 90, 1, 1, 0, 0, 0, 0, 0, 0, 12, 1, 'FEMALE', '#e83e8c', 2, 'Maternity leave as per Employment Act 2007 - 90 working days with pay'),
('PATERNITY', 'Paternity Leave', 'Paternity', 10, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 'MALE', '#17a2b8', 3, 'Paternity leave as per Employment Act 2007 - 10 working days'),
('SICK_FULL', 'Sick Leave - Full Pay', 'Sick (Full)', 30, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 'ALL', '#dc3545', 4, 'Sick leave with full pay - first 30 days per year'),
('SICK_HALF', 'Sick Leave - Half Pay', 'Sick (Half)', 30, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 'ALL', '#ffc107', 5, 'Sick leave with half pay - next 30 days per year'),
('COMPASSIONATE', 'Compassionate Leave', 'Compassionate', 10, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 'ALL', '#6c757d', 6, 'Leave on death of immediate family member'),
('STUDY', 'Study Leave', 'Study', 21, 1, 0, 0, 0, 0, 0, 0, 0, 12, 0, 'ALL', '#6610f2', 7, 'Leave for professional development and examinations'),
('OFF', 'Off/Weekend', 'Off', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'ALL', '#ffffff', 8, 'Non-working days (weekends)');

-- 2. KENYA HOLIDAYS
CREATE TABLE `prlkenyaholidays` (
  `holiday_id` INT NOT NULL AUTO_INCREMENT,
  `holiday_name` VARCHAR(100) NOT NULL,
  `holiday_date` DATE NOT NULL,
  `holiday_type` ENUM('FIXED','FLOATING','SUBSTITUTE') DEFAULT 'FIXED',
  `year` INT NOT NULL,
  `substitute_date` DATE DEFAULT NULL,
  `region` VARCHAR(50) DEFAULT 'NATIONAL',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`holiday_id`),
  KEY `idx_holiday_date` (`holiday_date`),
  KEY `idx_year` (`year`),
  UNIQUE KEY `uk_holiday_year` (`holiday_name`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `prlkenyaholidays` (`holiday_name`, `holiday_date`, `holiday_type`, `year`, `region`) VALUES
('New Year''s Day', '2024-01-01', 'FIXED', 2024, 'NATIONAL'),
('Good Friday', '2024-03-29', 'FIXED', 2024, 'NATIONAL'),
('Easter Monday', '2024-04-01', 'FIXED', 2024, 'NATIONAL'),
('Madaraka Day', '2024-06-01', 'FIXED', 2024, 'NATIONAL'),
('Mashujaa Day', '2024-10-20', 'FIXED', 2024, 'NATIONAL'),
('Jamhuri Day', '2024-12-12', 'FIXED', 2024, 'NATIONAL'),
('Christmas Day', '2024-12-25', 'FIXED', 2024, 'NATIONAL'),
('Boxing Day', '2024-12-26', 'FIXED', 2024, 'NATIONAL'),
('New Year''s Day', '2025-01-01', 'FIXED', 2025, 'NATIONAL'),
('Good Friday', '2025-04-18', 'FIXED', 2025, 'NATIONAL'),
('Easter Monday', '2025-04-21', 'FIXED', 2025, 'NATIONAL'),
('Labour Day', '2025-05-01', 'FIXED', 2025, 'NATIONAL'),
('Madaraka Day', '2025-06-01', 'FIXED', 2025, 'NATIONAL'),
('Mashujaa Day', '2025-10-20', 'FIXED', 2025, 'NATIONAL'),
('Jamhuri Day', '2025-12-12', 'FIXED', 2025, 'NATIONAL'),
('Christmas Day', '2025-12-25', 'FIXED', 2025, 'NATIONAL'),
('Boxing Day', '2025-12-26', 'FIXED', 2025, 'NATIONAL'),
('New Year''s Day', '2026-01-01', 'FIXED', 2026, 'NATIONAL'),
('Good Friday', '2026-04-03', 'FIXED', 2026, 'NATIONAL'),
('Easter Monday', '2026-04-06', 'FIXED', 2026, 'NATIONAL'),
('Labour Day', '2026-05-01', 'FIXED', 2026, 'NATIONAL'),
('Madaraka Day', '2026-06-01', 'FIXED', 2026, 'NATIONAL'),
('Mashujaa Day', '2026-10-20', 'FIXED', 2026, 'NATIONAL'),
('Jamhuri Day', '2026-12-12', 'FIXED', 2026, 'NATIONAL'),
('Christmas Day', '2026-12-25', 'FIXED', 2026, 'NATIONAL'),
('Boxing Day', '2026-12-26', 'FIXED', 2026, 'NATIONAL');

-- 3. LEAVE BALANCES
CREATE TABLE `prlleavebalances` (
  `balance_id` BIGINT NOT NULL AUTO_INCREMENT,
  `pfno` CHAR(20) NOT NULL,
  `leavetype_id` INT NOT NULL,
  `year` INT NOT NULL,
  `entitled_days` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `carried_over_days` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `used_days` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `pending_days` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `available_days` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `encashed_days` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `forfeited_days` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`balance_id`),
  UNIQUE KEY `uk_employee_type_year` (`pfno`, `leavetype_id`, `year`),
  KEY `idx_pfno` (`pfno`),
  KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 4. LEAVE APPLICATIONS
CREATE TABLE `prlleaveapplications` (
  `application_id` BIGINT NOT NULL AUTO_INCREMENT,
  `application_ref` VARCHAR(30) NOT NULL UNIQUE,
  `pfno` CHAR(20) NOT NULL,
  `leavetype_id` INT NOT NULL,
  `applied_days` DECIMAL(5,2) NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `expected_return_date` DATE,
  `handover_to` CHAR(20) DEFAULT NULL,
  `reason` TEXT,
  `medical_certificate_no` VARCHAR(50) DEFAULT NULL,
  `medical_certificate_date` DATE DEFAULT NULL,
  `medical_certificate_hospital` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('DRAFT','PENDING','PENDING_HOD','PENDING_HR','APPROVED','REJECTED','CANCELLED','COMPLETED') DEFAULT 'PENDING',
  `rejection_reason` TEXT,
  `current_approval_level` INT DEFAULT 1,
  `requires_hr_approval` TINYINT(1) DEFAULT 0,
  `is_paid` TINYINT(1) DEFAULT 1,
  `is_advance_leave` TINYINT(1) DEFAULT 0,
  `encashment_amount` DECIMAL(10,2) DEFAULT NULL,
  `year` INT NOT NULL,
  `actual_return_date` DATE DEFAULT NULL,
  `actual_days_taken` DECIMAL(5,2) DEFAULT NULL,
  `escalated` TINYINT(1) DEFAULT 0,
  `escalated_at` DATETIME DEFAULT NULL,
  `created_by` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  KEY `idx_pfno` (`pfno`),
  KEY `idx_leavetype` (`leavetype_id`),
  KEY `idx_status` (`status`),
  KEY `idx_from_date` (`from_date`),
  KEY `idx_year` (`year`),
  KEY `idx_escalated` (`escalated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 5. LEAVE APPROVAL TRANSACTIONS
CREATE TABLE `prlleaveapprovaltrans` (
  `approval_id` BIGINT NOT NULL AUTO_INCREMENT,
  `application_ref` VARCHAR(30) NOT NULL,
  `approval_level` INT NOT NULL DEFAULT 1,
  `approver_pfno` CHAR(20) DEFAULT NULL,
  `approver_position` INT DEFAULT NULL,
  `department` INT DEFAULT NULL,
  `approval_status` ENUM('PENDING','APPROVED','REJECTED','ESCALATED') DEFAULT 'PENDING',
  `approval_date` DATETIME DEFAULT NULL,
  `comments` TEXT,
  `sequence_order` INT NOT NULL DEFAULT 1,
  `due_date` DATE DEFAULT NULL,
  `reminder_sent` TINYINT(1) DEFAULT 0,
  `escalated` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_id`),
  KEY `idx_application` (`application_ref`),
  KEY `idx_approver` (`approver_pfno`),
  KEY `idx_status` (`approval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 6. LEAVE CARRYOVER
CREATE TABLE `prlleavecarrovers` (
  `carryover_id` BIGINT NOT NULL AUTO_INCREMENT,
  `pfno` CHAR(20) NOT NULL,
  `leavetype_id` INT NOT NULL,
  `from_year` INT NOT NULL,
  `to_year` INT NOT NULL,
  `days_carried` DECIMAL(5,2) NOT NULL,
  `days_used` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `days_forfeited` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `expiry_date` DATE DEFAULT NULL,
  `status` ENUM('ACTIVE','EXPIRED','FULFILLED') DEFAULT 'ACTIVE',
  `processed_by` VARCHAR(50) DEFAULT NULL,
  `processed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`carryover_id`),
  KEY `idx_employee_year` (`pfno`, `from_year`, `to_year`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 7. LEAVE ENCASHMENTS
CREATE TABLE `prlleaveencashments` (
  `encashment_id` BIGINT NOT NULL AUTO_INCREMENT,
  `encashment_ref` VARCHAR(30) NOT NULL UNIQUE,
  `pfno` CHAR(20) NOT NULL,
  `leavetype_id` INT NOT NULL,
  `application_ref` VARCHAR(30) DEFAULT NULL,
  `days_encashed` DECIMAL(5,2) NOT NULL,
  `daily_rate` DECIMAL(10,2) NOT NULL,
  `gross_amount` DECIMAL(12,2) NOT NULL,
  `tax_deducted` DECIMAL(12,2) DEFAULT 0,
  `net_amount` DECIMAL(12,2) NOT NULL,
  `pay_period` INT DEFAULT NULL,
  `request_date` DATE NOT NULL,
  `approval_date` DATE DEFAULT NULL,
  `payment_date` DATE DEFAULT NULL,
  `status` ENUM('PENDING','APPROVED','REJECTED','PROCESSED','PAID') DEFAULT 'PENDING',
  `rejection_reason` TEXT,
  `approved_by` VARCHAR(50) DEFAULT NULL,
  `processed_by` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`encashment_id`),
  KEY `idx_pfno` (`pfno`),
  KEY `idx_status` (`status`),
  KEY `idx_request_date` (`request_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 8. LEAVE NOTIFICATIONS
CREATE TABLE `prlleavenotifications` (
  `notification_id` BIGINT NOT NULL AUTO_INCREMENT,
  `application_ref` VARCHAR(30) DEFAULT NULL,
  `notification_type` ENUM('APPLICATION','APPROVAL','REJECTION','REMINDER','CANCELLATION','RETURN_REMINDER','ESCALATION') NOT NULL,
  `recipient_pfno` CHAR(20) NOT NULL,
  `recipient_email` VARCHAR(100) DEFAULT NULL,
  `recipient_phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(200) DEFAULT NULL,
  `message` TEXT,
  `channel` ENUM('EMAIL','SMS','BOTH') DEFAULT 'EMAIL',
  `status` ENUM('QUEUED','SENT','FAILED','READ') DEFAULT 'QUEUED',
  `sent_at` DATETIME DEFAULT NULL,
  `read_at` DATETIME DEFAULT NULL,
  `error_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_recipient` (`recipient_pfno`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 9. LEAVE AUDIT
CREATE TABLE `prlleaveaudit` (
  `audit_id` BIGINT NOT NULL AUTO_INCREMENT,
  `audit_table` VARCHAR(50) NOT NULL,
  `audit_action` ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  `record_id` VARCHAR(50) NOT NULL,
  `pfno` CHAR(20) DEFAULT NULL,
  `field_name` VARCHAR(50) DEFAULT NULL,
  `old_value` TEXT,
  `new_value` TEXT,
  `user_id` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `idx_pfno` (`pfno`),
  KEY `idx_audit_table` (`audit_table`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 10. LEAVE WORKFLOWS
CREATE TABLE `prlleaveworkflows` (
  `workflow_id` INT NOT NULL AUTO_INCREMENT,
  `leavetype_id` INT NOT NULL,
  `approval_level` INT NOT NULL DEFAULT 1,
  `approver_type` ENUM('HOD','HR','MANAGER','SPECIFIC_USER') NOT NULL DEFAULT 'HOD',
  `approver_pfno` CHAR(20) DEFAULT NULL,
  `department_id` INT DEFAULT NULL,
  `min_days_threshold` DECIMAL(5,2) DEFAULT NULL,
  `max_days_threshold` DECIMAL(5,2) DEFAULT NULL,
  `requires_all_levels` TINYINT(1) DEFAULT 1,
  `allow_escalation` TINYINT(1) DEFAULT 1,
  `escalation_days` INT DEFAULT 3,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`workflow_id`),
  KEY `idx_leavetype` (`leavetype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `prlleaveworkflows` (`leavetype_id`, `approval_level`, `approver_type`, `requires_all_levels`, `allow_escalation`, `escalation_days`) VALUES
(1, 1, 'HOD', 1, 1, 3),
(1, 2, 'HR', 0, 1, 5),
(2, 1, 'HOD', 1, 1, 2),
(2, 2, 'HR', 1, 1, 2),
(3, 1, 'HOD', 1, 1, 2),
(4, 1, 'HOD', 1, 1, 2),
(4, 2, 'HR', 0, 1, 5),
(5, 1, 'HOD', 1, 1, 2),
(5, 2, 'HR', 1, 1, 3),
(6, 1, 'HOD', 1, 1, 1),
(6, 2, 'HR', 1, 1, 2),
(7, 1, 'HOD', 1, 1, 3),
(7, 2, 'HR', 1, 1, 5);

-- 11. LEAVE ESCALATIONS
CREATE TABLE `prlleaveescalations` (
  `escalation_id` INT NOT NULL AUTO_INCREMENT,
  `leavetype_id` INT NOT NULL,
  `escalation_level` INT NOT NULL DEFAULT 1,
  `escalation_days` INT NOT NULL DEFAULT 3,
  `escalation_to` VARCHAR(50) NOT NULL,
  `escalation_position` INT DEFAULT NULL,
  `message` VARCHAR(255) DEFAULT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`escalation_id`),
  KEY `idx_leavetype` (`leavetype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `prlleaveescalations` (`leavetype_id`, `escalation_level`, `escalation_days`, `escalation_to`, `message`) VALUES
(1, 1, 3, 'HR', 'Annual leave pending approval escalated to HR'),
(2, 1, 2, 'HR', 'Maternity leave pending approval escalated to HR'),
(3, 1, 2, 'HR', 'Paternity leave pending approval escalated to HR'),
(4, 1, 2, 'HR', 'Sick leave pending approval escalated to HR'),
(5, 1, 2, 'HR', 'Sick leave pending approval escalated to HR'),
(6, 1, 1, 'HR', 'Compassionate leave pending approval escalated to HR'),
(7, 1, 5, 'HR', 'Study leave pending approval escalated to HR');

-- 12. WEEKENDS
CREATE TABLE `prlweekends` (
  `day_id` INT NOT NULL AUTO_INCREMENT,
  `day_name` VARCHAR(20) NOT NULL,
  `day_number` INT NOT NULL,
  `is_working_day` TINYINT(1) NOT NULL DEFAULT 0,
  `default_hours` DECIMAL(4,2) DEFAULT 8.00,
  `active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`day_id`),
  UNIQUE KEY `uk_day_name` (`day_name`),
  UNIQUE KEY `uk_day_number` (`day_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `prlweekends` (`day_name`, `day_number`, `is_working_day`, `default_hours`) VALUES
('Sunday', 0, 0, 0),
('Monday', 1, 1, 8),
('Tuesday', 2, 1, 8),
('Wednesday', 3, 1, 8),
('Thursday', 4, 1, 8),
('Friday', 5, 1, 8),
('Saturday', 6, 0, 0);

SET FOREIGN_KEY_CHECKS = 1;

-- ===================================================================
-- Migration Complete
-- ===================================================================
