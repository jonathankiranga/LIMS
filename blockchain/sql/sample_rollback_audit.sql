-- Sample rollback audit history.
-- Run once against the LIMS database before deploying the updated rollback endpoint.
CREATE TABLE IF NOT EXISTS sample_rollback_audit (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sample_id VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    initiated_by VARCHAR(100) NOT NULL,
    initiated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    previous_status INT NOT NULL,
    new_status INT NOT NULL,
    affected_rows INT UNSIGNED NOT NULL DEFAULT 0,
    approved_by VARCHAR(100) DEFAULT NULL,
    approval_decision VARCHAR(30) DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_sample_rollback_audit_sample (sample_id),
    KEY idx_sample_rollback_audit_initiated_at (initiated_at),
    KEY idx_sample_rollback_audit_initiated_by (initiated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
