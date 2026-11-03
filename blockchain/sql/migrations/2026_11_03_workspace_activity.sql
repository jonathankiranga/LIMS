-- Migration: workspace-wide activity stream for sample reversals
-- Run once against the LIMS database before deploying the updated PHP files.
-- Sample IDs are intentionally plain text; no card/sample foreign key is created.
CREATE TABLE IF NOT EXISTS workspace_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sample_id VARCHAR(100) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sample_id (sample_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
