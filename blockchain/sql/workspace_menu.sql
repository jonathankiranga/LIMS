-- ============================================
-- WORKSPACE MODULE MENU SETUP
-- For Blockchain LIMS
-- Run this SQL to add workspace menu items
-- ============================================

-- Step 1: Add main "Workspace" menu item
-- This will have security_id = 0 (visible to all who have submenu access)
INSERT INTO menu_items (title, icon, url, parent_id, security_id) 
VALUES ('Workspace', 'fas fa-layer-group', NULL, NULL, 0);

-- Get the ID of the Workspace menu item we just created
SET @workspace_parent_id = LAST_INSERT_ID();

-- Step 2: Add submenu items
INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Workspaces', NULL, 'workspace.php', @workspace_parent_id, @workspace_parent_id);

INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Weekly Report', NULL, 'weekly_report.php', @workspace_parent_id, @workspace_parent_id + 1);


INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Email & Reminders Config', NULL, 'workspace_configs.php', @workspace_parent_id, @workspace_parent_id + 2);


INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Email & Reminders', NULL, 'email_rules.php', @workspace_parent_id, @workspace_parent_id + 3);

-- Get the security IDs for the submenus
SET @workspace_security_id = @workspace_parent_id;
SET @report_security_id = @workspace_parent_id + 1;
SET @email_security_id = @workspace_parent_id + 2;
SET @email_security_config_id = @workspace_parent_id + 3;

-- Step 3: Assign permissions to roles (assuming role IDs 1, 2, 3 exist)
-- Adjust these based on your actual role IDs
-- Admin role (id=1) gets all permissions
INSERT IGNORE INTO role_security (role_id, security_id) VALUES (1, @workspace_security_id);
INSERT IGNORE INTO role_security (role_id, security_id) VALUES (1, @report_security_id);
INSERT IGNORE INTO role_security (role_id, security_id) VALUES (1, @email_security_id);

-- Manager role (id=2) gets workspace and report permissions
INSERT IGNORE INTO role_security (role_id, security_id) VALUES (2, @workspace_security_id);
INSERT IGNORE INTO role_security (role_id, security_id) VALUES (2, @report_security_id);

-- Staff role (id=3) gets basic workspace access
INSERT IGNORE INTO role_security (role_id, security_id) VALUES (3, @workspace_security_id);
INSERT IGNORE INTO role_security (role_id, security_id) VALUES (3, @email_security_config_id);

-- ============================================
-- VERIFICATION
-- ============================================
-- Run these to verify:
-- SELECT * FROM menu_items WHERE title = 'Workspace';
-- SELECT * FROM role_security WHERE security_id >= @workspace_security_id;
