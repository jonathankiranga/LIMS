-- ===================================================================
-- Insert new leave management scripts into the scripts table
-- ===================================================================

INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES
('prlleavetypes.php', 15, 'Configure leave types with Kenyan statutory settings'),
('prlkenyaholidays.php', 15, 'Manage Kenya gazetted public holidays'),
('prlleavebalances.php', 15, 'View and manage employee leave balances'),
('prlleaveapplication.php', 1, 'Submit new leave application request'),
('prlleaveapproval.php', 15, 'Approve or reject leave applications'),
('prlleavecarrovers.php', 15, 'Manage end-of-year leave carryover'),
('prlleaveencashment.php', 15, 'Process leave encashment requests'),
('prlleavereports.php', 15, 'Leave management reports and analytics'),
('prlleaveescalations.php', 15, 'Configure leave approval escalation rules'),
('prlleavenotifications.php', 15, 'View and manage leave notification queue'),
('AjaxLeaveCalculator.php', 1, 'AJAX API for leave calculations and validations'),
('cron_populate_holidays.php', 15, 'Cron script to auto-populate Kenya holidays');

-- ===================================================================
-- Update page security for existing leave scripts
-- ===================================================================

UPDATE `scripts` SET `description` = 'Submit new leave request (Enhanced)' WHERE `script` = 'prlstaffleave.php';
UPDATE `scripts` SET `description` = 'Leave schedule calendar report' WHERE `script` = 'PDFleaveschedule.php';

-- ===================================================================
