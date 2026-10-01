-- CRM Database Schema Enhancement
-- Run this SQL to add Lead Management, Opportunity Tracking, Communication Logging, and Automation

-- ============================================
-- PIPELINE STAGES (Configurable Sales Stages)
-- ============================================
CREATE TABLE IF NOT EXISTS crm_pipeline_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_name VARCHAR(100) NOT NULL,
    stage_order INT NOT NULL DEFAULT 0,
    probability INT NOT NULL DEFAULT 0,
    is_default BOOLEAN DEFAULT FALSE,
    created_by VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default pipeline stages
INSERT INTO crm_pipeline_stages (stage_name, stage_order, probability, is_default) VALUES
('New Lead', 1, 10, TRUE),
('Contacted', 2, 20, FALSE),
('Qualified', 3, 40, FALSE),
('Proposal', 4, 60, FALSE),
('Negotiation', 5, 80, FALSE),
('Closed Won', 6, 100, FALSE),
('Closed Lost', 7, 0, FALSE)
ON DUPLICATE KEY UPDATE stage_order=stage_order;

-- ============================================
-- LEADS
-- ============================================
CREATE TABLE IF NOT EXISTS crm_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    mobile VARCHAR(50) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    industry VARCHAR(100) DEFAULT NULL,
    source VARCHAR(100) DEFAULT NULL,
    source_details VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'new',
    assigned_to VARCHAR(50) DEFAULT NULL,
    lead_score INT DEFAULT 0,
    notes TEXT DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    pin_vat VARCHAR(50) DEFAULT NULL,
    converted_to_contact_id INT DEFAULT NULL,
    converted_to_opportunity_id INT DEFAULT NULL,
    converted_at DATETIME DEFAULT NULL,
    last_activity_at DATETIME DEFAULT NULL,
    next_followup DATE DEFAULT NULL,
    created_by VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to),
    INDEX idx_source (source),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lead status options
CREATE TABLE IF NOT EXISTS crm_lead_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL UNIQUE,
    status_order INT NOT NULL DEFAULT 0,
    is_converted BOOLEAN DEFAULT FALSE,
    color VARCHAR(20) DEFAULT '#6c757d'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO crm_lead_statuses (status_name, status_order, is_converted, color) VALUES
('new', 1, FALSE, '#6c757d'),
('contacted', 2, FALSE, '#0d6efd'),
('qualified', 3, FALSE, '#6610f2'),
('proposal', 4, FALSE, '#6f42c1'),
('negotiation', 5, FALSE, '#d63384'),
('converted', 6, TRUE, '#198754'),
('lost', 7, TRUE, '#dc3545')
ON DUPLICATE KEY UPDATE status_order=status_order;

-- Lead sources
CREATE TABLE IF NOT EXISTS crm_lead_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(100) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO crm_lead_sources (source_name) VALUES
('Website'),
('Referral'),
('Google'),
('Facebook'),
('LinkedIn'),
('Trade Show'),
('Cold Call'),
('Email Campaign'),
('Partner'),
('Other')
ON DUPLICATE KEY UPDATE source_name=source_name;

-- ============================================
-- OPPORTUNITIES / DEALS
-- ============================================
CREATE TABLE IF NOT EXISTS crm_opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_name VARCHAR(255) NOT NULL,
    lead_id INT DEFAULT NULL,
    contact_id INT DEFAULT NULL,
    pipeline_stage_id INT DEFAULT NULL,
    assigned_to VARCHAR(50) DEFAULT NULL,
    expected_value DECIMAL(15,2) DEFAULT 0,
    probability INT DEFAULT 50,
    expected_close_date DATE DEFAULT NULL,
    actual_close_date DATE DEFAULT NULL,
    closed_value DECIMAL(15,2) DEFAULT NULL,
    won_reason TEXT DEFAULT NULL,
    lost_reason TEXT DEFAULT NULL,
    competitor VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    next_step TEXT DEFAULT NULL,
    lost_to_competitor VARCHAR(255) DEFAULT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_cycle VARCHAR(20) DEFAULT NULL,
    recurring_value DECIMAL(15,2) DEFAULT NULL,
    created_by VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stage (pipeline_stage_id),
    INDEX idx_assigned (assigned_to),
    INDEX idx_lead (lead_id),
    INDEX idx_contact (contact_id),
    FOREIGN KEY (pipeline_stage_id) REFERENCES crm_pipeline_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (lead_id) REFERENCES crm_leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- OPPORTUNITY LINE ITEMS (Products/Services)
-- ============================================
CREATE TABLE IF NOT EXISTS crm_opportunity_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    quantity DECIMAL(10,2) DEFAULT 1,
    unit_price DECIMAL(15,2) DEFAULT 0,
    total_price DECIMAL(15,2) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (opportunity_id) REFERENCES crm_opportunities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- COMMUNICATIONS (Email, Call, Meeting, Note)
-- ============================================
CREATE TABLE IF NOT EXISTS crm_communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT DEFAULT NULL,
    opportunity_id INT DEFAULT NULL,
    contact_id INT DEFAULT NULL,
    task_id INT DEFAULT NULL,
    communication_type ENUM('email', 'call', 'meeting', 'note', 'sms', 'meeting_scheduled') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT DEFAULT NULL,
    direction ENUM('inbound', 'outbound') DEFAULT 'outbound',
    from_email VARCHAR(255) DEFAULT NULL,
    to_email VARCHAR(255) DEFAULT NULL,
    from_phone VARCHAR(50) DEFAULT NULL,
    to_phone VARCHAR(50) DEFAULT NULL,
    duration_seconds INT DEFAULT NULL,
    outcome VARCHAR(100) DEFAULT NULL,
    result VARCHAR(255) DEFAULT NULL,
    next_action TEXT DEFAULT NULL,
    next_followup DATE DEFAULT NULL,
    attachments JSON DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_by VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lead (lead_id),
    INDEX idx_opportunity (opportunity_id),
    INDEX idx_contact (contact_id),
    INDEX idx_type (communication_type),
    INDEX idx_created (created_at),
    FOREIGN KEY (lead_id) REFERENCES crm_leads(id) ON DELETE SET NULL,
    FOREIGN KEY (opportunity_id) REFERENCES crm_opportunities(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- AUTOMATION RULES
-- ============================================
CREATE TABLE IF NOT EXISTS crm_automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    trigger_type VARCHAR(50) NOT NULL,
    trigger_conditions JSON DEFAULT NULL,
    actions JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0,
    execute_after_seconds INT DEFAULT 0,
    created_by VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_trigger (trigger_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Automation trigger types
CREATE TABLE IF NOT EXISTS crm_automation_triggers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_name VARCHAR(100) NOT NULL UNIQUE,
    trigger_description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO crm_automation_triggers (trigger_name, trigger_description) VALUES
('lead_created', 'When a new lead is created'),
('lead_status_changed', 'When lead status changes'),
('lead_assigned', 'When a lead is assigned'),
('opportunity_stage_changed', 'When opportunity stage changes'),
('opportunity_created', 'When new opportunity created'),
('opportunity_won', 'When opportunity is closed won'),
('opportunity_lost', 'When opportunity is closed lost'),
('task_due', 'When a task is due'),
('followup_overdue', 'When follow-up is overdue');

-- ============================================
-- AUTOMATION LOG (Execution History)
-- ============================================
CREATE TABLE IF NOT EXISTS crm_automation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT NOT NULL,
    trigger_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    execution_result VARCHAR(50) DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    executed_by VARCHAR(50) DEFAULT NULL,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rule (rule_id),
    INDEX idx_entity (entity_type, entity_id),
    FOREIGN KEY (rule_id) REFERENCES crm_automation_rules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- EMAIL TEMPLATES
-- ============================================
CREATE TABLE IF NOT EXISTS crm_email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    variables JSON DEFAULT NULL,
    created_by VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SALES REP QUOTA / TARGETS
-- ============================================
CREATE TABLE IF NOT EXISTS crm_sales_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_value DECIMAL(15,2) NOT NULL,
    achieved_value DECIMAL(15,2) DEFAULT 0,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ENHANCED NEWCONTACTS (Add missing fields)
-- ============================================
SET @dbname = DATABASE();
SET @tablename = 'newcontacts';
SET @columnname = 'industry';
SET @sql = (SELECT IF( (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', 'ALTER TABLE newcontacts ADD COLUMN industry VARCHAR(100) DEFAULT NULL' ));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'lead_source';
SET @sql = (SELECT IF( (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', 'ALTER TABLE newcontacts ADD COLUMN lead_source VARCHAR(100) DEFAULT NULL' ));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'contact_status';
SET @sql = (SELECT IF( (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', 'ALTER TABLE newcontacts ADD COLUMN contact_status VARCHAR(50) DEFAULT ''active''' ));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'owner';
SET @sql = (SELECT IF( (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', 'ALTER TABLE newcontacts ADD COLUMN owner VARCHAR(50) DEFAULT NULL' ));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'lifetime_value';
SET @sql = (SELECT IF( (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', 'ALTER TABLE newcontacts ADD COLUMN lifetime_value DECIMAL(15,2) DEFAULT 0' ));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'last_contacted_at';
SET @sql = (SELECT IF( (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', 'ALTER TABLE newcontacts ADD COLUMN last_contacted_at DATETIME DEFAULT NULL' ));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnname = 'next_followup';
SET @sql = (SELECT IF( (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', 'ALTER TABLE newcontacts ADD COLUMN next_followup DATE DEFAULT NULL' ));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
