-- Weekly Report Field Activities Schema
-- Add to your database

CREATE TABLE IF NOT EXISTS weekly_report_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_week DATE NOT NULL,
    activity_date DATE NOT NULL,
    company VARCHAR(255),
    activity_type VARCHAR(100),
    activity_status VARCHAR(30) NOT NULL DEFAULT 'planned',
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
    INDEX idx_activity_status (activity_status),
    INDEX idx_created_by (created_by)
);

-- For existing databases, run this once if the column does not exist yet:
-- ALTER TABLE weekly_report_activities ADD COLUMN activity_status VARCHAR(30) NOT NULL DEFAULT 'planned' AFTER activity_type;
-- CREATE INDEX idx_activity_status ON weekly_report_activities (activity_status);

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
);
