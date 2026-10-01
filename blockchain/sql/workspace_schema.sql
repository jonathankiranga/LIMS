-- ============================================
-- WORKSPACE MODULE SCHEMA
-- For Blockchain LIMS
-- Run this SQL to create the necessary tables
-- ============================================

-- ============================================
-- WORKSPACES
-- ============================================
CREATE TABLE IF NOT EXISTS workspaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    color VARCHAR(20) DEFAULT '#6b4fd6',
    icon VARCHAR(50) DEFAULT 'folder',
    visibility ENUM('public', 'private') DEFAULT 'private',
    created_by VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE MEMBERS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    privilege ENUM('owner', 'admin', 'member', 'viewer') DEFAULT 'member',
    added_by VARCHAR(100),
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_workspace_user (workspace_id, user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE TASKS
-- ============================================
-- WORKSPACE BOARDS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_default TINYINT(1) DEFAULT 0,
    created_by VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_default_board (workspace_id, is_default),
    INDEX idx_workspace_id (workspace_id),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE LISTS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(20) DEFAULT '#6b4fd6',
    position INT DEFAULT 0,
    created_by VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_board_id (board_id),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE CARDS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    list_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    priority INT DEFAULT 2,
    due_date DATETIME,
    cover_color VARCHAR(20) DEFAULT NULL,
    position INT DEFAULT 0,
    archived TINYINT(1) DEFAULT 0,
    created_by VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_board_id (board_id),
    INDEX idx_list_id (list_id),
    INDEX idx_position (position),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE CARD MEMBERS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_card_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    added_by VARCHAR(100),
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_card_member (card_id, user_id),
    INDEX idx_card_id (card_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE LABELS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT '#6b4fd6',
    created_by VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_board_id (board_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE CARD LABELS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_card_labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    label_id INT NOT NULL,
    UNIQUE KEY unique_card_label (card_id, label_id),
    INDEX idx_card_id (card_id),
    INDEX idx_label_id (label_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE CHECKLISTS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_checklists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    position INT DEFAULT 0,
    created_by VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_card_id (card_id),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE CHECKLIST ITEMS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_checklist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checklist_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    position INT DEFAULT 0,
    completed_by VARCHAR(100) DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_checklist_id (checklist_id),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- LEGACY WORKSPACE TASKS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    status ENUM('todo', 'in_progress', 'review', 'done') DEFAULT 'todo',
    priority INT DEFAULT 2,
    due_date DATETIME,
    assigned_to VARCHAR(100),
    created_by VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workspace_id (workspace_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE COMMENTS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE CARD COMMENTS
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_card_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_card_id (card_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WORKSPACE CARD ACTIVITY
-- ============================================
CREATE TABLE IF NOT EXISTS workspace_card_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_card_id (card_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- EMAIL TEMPLATES
-- ============================================
CREATE TABLE IF NOT EXISTS ws_email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'general',
    variables JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- EMAIL RULES (Automation)
-- ============================================
CREATE TABLE IF NOT EXISTS ws_email_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    description TEXT,
    trigger_type VARCHAR(50) NOT NULL,
    trigger_conditions JSON,
    email_template_id INT,
    recipients JSON,
    cc_addresses TEXT,
    bcc_addresses TEXT,
    subject_override VARCHAR(500),
    body_override TEXT,
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 0,
    created_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (email_template_id) REFERENCES ws_email_templates(id) ON DELETE SET NULL,
    INDEX idx_trigger (trigger_type),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SCHEDULED REMINDERS
-- ============================================
CREATE TABLE IF NOT EXISTS ws_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reminder_type ENUM('task', 'lead', 'opportunity', 'custom', 'meeting') NOT NULL,
    reference_id INT DEFAULT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    reminder_date DATETIME NOT NULL,
    remind_before_minutes INT DEFAULT 30,
    recipient_user VARCHAR(100),
    recipient_email VARCHAR(255),
    send_notification TINYINT(1) DEFAULT 1,
    send_email TINYINT(1) DEFAULT 1,
    include_calendar TINYINT(1) DEFAULT 0,
    status ENUM('pending', 'sent', 'cancelled', 'completed') DEFAULT 'pending',
    sent_at DATETIME,
    created_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reminder_date (reminder_date),
    INDEX idx_status (status),
    INDEX idx_reference (reminder_type, reference_id),
    INDEX idx_recipient (recipient_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- EMAIL QUEUE
-- ============================================
CREATE TABLE IF NOT EXISTS ws_email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_rule_id INT,
    reminder_id INT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    cc_addresses TEXT,
    bcc_addresses TEXT,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    attachments JSON,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    send_at DATETIME,
    sent_at DATETIME,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_rule_id) REFERENCES ws_email_rules(id) ON DELETE SET NULL,
    FOREIGN KEY (reminder_id) REFERENCES ws_reminders(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_send_at (send_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- EMAIL LOG
-- ============================================
CREATE TABLE IF NOT EXISTS ws_email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_rule_id INT,
    reminder_id INT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255),
    subject VARCHAR(500),
    status ENUM('sent', 'failed', 'opened', 'clicked', 'bounced') DEFAULT 'sent',
    error_message TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rule (email_rule_id),
    INDEX idx_reminder (reminder_id),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WEEKLY REPORT TABLES
-- ============================================
CREATE TABLE IF NOT EXISTS weekly_report_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_week DATE NOT NULL,
    activity_date DATE NOT NULL,
    company VARCHAR(255),
    activity_type VARCHAR(100),
    contact_person VARCHAR(255),
    contact_phone VARCHAR(50),
    contact_email VARCHAR(255),
    designation VARCHAR(100),
    current_lab VARCHAR(255),
    contact_result TEXT,
    created_by VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_week (report_week),
    INDEX idx_activity_date (activity_date),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS weekly_report_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_week DATE NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    leads_created INT DEFAULT 0,
    leads_converted INT DEFAULT 0,
    opportunities_created INT DEFAULT 0,
    opportunities_won INT DEFAULT 0,
    pipeline_value DECIMAL(15,2) DEFAULT 0,
    tasks_completed INT DEFAULT 0,
    tasks_created INT DEFAULT 0,
    visits_planned INT DEFAULT 0,
    visits_completed INT DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_week (report_week, user_id),
    INDEX idx_report_week (report_week),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT DEFAULT EMAIL TEMPLATES
-- ============================================
INSERT INTO ws_email_templates (template_name, subject, body, category, variables) VALUES
('Task Due Reminder', 'Reminder: {{task_title}} is due on {{due_date}}', 
'<p>Hello {{user_name}},</p><p>This is a reminder that the following task is due soon:</p><p><strong>{{task_title}}</strong></p><p><strong>Due Date:</strong> {{due_date}}</p><p>Please take the necessary action.</p>', 
'reminder', '["task_title", "due_date", "user_name"]'),

('Task Overdue Alert', 'OVERDUE: {{task_title}} was due on {{due_date}}',
'<p>Hello {{user_name}},</p><p><strong style="color: red;">This task is overdue!</strong></p><p><strong>{{task_title}}</strong></p><p>Please address this task immediately.</p>',
'reminder', '["task_title", "due_date", "user_name"]'),

('Meeting Reminder', 'Meeting Reminder: {{meeting_title}}',
'<p>Hello {{user_name}},</p><p>Reminder about your upcoming meeting:</p><p><strong>{{meeting_title}}</strong></p><p><strong>Date:</strong> {{meeting_date}}</p>',
'meeting', '["meeting_title", "meeting_date", "user_name"]');

-- ============================================
-- INSERT DEFAULT EMAIL TEMPLATES
-- ============================================
INSERT INTO ws_email_templates (template_name, subject, body, category, is_active, variables) VALUES
('Task Due Reminder', 'Reminder: {{task_title}} is due on {{due_date}}', 
'<p>Hello {{user_name}},</p><p>This is a reminder that the following task is due soon:</p><p><strong>{{task_title}}</strong></p><p><strong>Due Date:</strong> {{due_date}}</p><p>Please take the necessary action.</p>', 
'reminder', 1, '["task_title","due_date","user_name"]'),
('Task Overdue Alert', 'OVERDUE: {{task_title}} was due on {{due_date}}',
'<p>Hello {{user_name}},</p><p><strong style="color: red;">This task is overdue!</strong></p><p><strong>{{task_title}}</strong></p><p>Please address this task immediately.</p>',
'reminder', 1, '["task_title","due_date","user_name"]');

-- INSERT DEFAULT EMAIL RULES
-- ============================================
INSERT INTO ws_email_rules (rule_name, description, trigger_type, email_template_id, recipients, is_active, priority) VALUES
('Task Due Reminder', 'Send reminder 1 day before task is due', 'task_due_soon', 1, '[]', 1, 10),
('Task Overdue Alert', 'Send alert when task becomes overdue', 'task_overdue', 2, '[]', 1, 9),
('Daily Digest', 'Send daily summary to users', 'daily_digest', 0, '[]', 1, 5);

-- ============================================
-- INSERT SAMPLE WORKSPACE
-- ============================================
INSERT INTO workspaces (name, description, color, created_by) 
VALUES ('General', 'Default workspace for all users', '#6b4fd6', 'admin');
