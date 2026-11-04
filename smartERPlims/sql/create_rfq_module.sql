-- RFQ module: additive schema migration.
-- Run against the same database used by smartERPlims.
-- Existing supplier, inventory and purchase tables are not modified.

CREATE TABLE IF NOT EXISTS rfq_header (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rfq_no VARCHAR(30) NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  terms_conditions MEDIUMTEXT NOT NULL,
  currencycode CHAR(3) NOT NULL,
  issue_date DATETIME NULL,
  closing_at DATETIME NOT NULL,
  status ENUM('draft','issued','closed','awarded','cancelled') NOT NULL DEFAULT 'draft',
  awarded_supplier_code VARCHAR(20) NULL,
  awarded_quote_id BIGINT UNSIGNED NULL,
  created_by VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rfq_no (rfq_no),
  KEY idx_rfq_status_close (status, closing_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rfq_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rfq_id BIGINT UNSIGNED NOT NULL,
  itemcode VARCHAR(50) NULL,
  description VARCHAR(255) NOT NULL,
  specification MEDIUMTEXT NULL,
  quantity DECIMAL(18,4) NOT NULL,
  unit VARCHAR(30) NULL,
  required_delivery_date DATE NULL,
  line_no INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_rfq_lines_rfq (rfq_id),
  CONSTRAINT fk_rfq_lines_header FOREIGN KEY (rfq_id) REFERENCES rfq_header(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rfq_invites (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rfq_id BIGINT UNSIGNED NOT NULL,
  supplier_code VARCHAR(20) NOT NULL,
  supplier_email VARCHAR(150) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  status ENUM('invited','submitted','declined','expired') NOT NULL DEFAULT 'invited',
  invited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rfq_supplier (rfq_id, supplier_code),
  UNIQUE KEY uq_rfq_token_hash (token_hash),
  KEY idx_rfq_invites_status (rfq_id, status),
  CONSTRAINT fk_rfq_invites_header FOREIGN KEY (rfq_id) REFERENCES rfq_header(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rfq_quotes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  invite_id BIGINT UNSIGNED NOT NULL,
  rfq_id BIGINT UNSIGNED NOT NULL,
  supplier_code VARCHAR(20) NOT NULL,
  quote_reference VARCHAR(80) NULL,
  validity_date DATE NULL,
  delivery_days INT NULL,
  payment_terms TEXT NULL,
  supplier_notes MEDIUMTEXT NULL,
  accepted_terms TINYINT(1) NOT NULL DEFAULT 0,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submit_ip VARCHAR(45) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rfq_quote_invite (invite_id),
  KEY idx_rfq_quotes_rfq (rfq_id),
  CONSTRAINT fk_rfq_quotes_invite FOREIGN KEY (invite_id) REFERENCES rfq_invites(id),
  CONSTRAINT fk_rfq_quotes_header FOREIGN KEY (rfq_id) REFERENCES rfq_header(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rfq_quote_lines (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  quote_id BIGINT UNSIGNED NOT NULL,
  rfq_line_id BIGINT UNSIGNED NOT NULL,
  unit_price DECIMAL(18,4) NOT NULL,
  tax_rate DECIMAL(8,4) NOT NULL DEFAULT 0,
  availability_notes VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rfq_quote_line (quote_id, rfq_line_id),
  KEY idx_rfq_quote_lines_line (rfq_line_id),
  CONSTRAINT fk_rfq_quote_lines_quote FOREIGN KEY (quote_id) REFERENCES rfq_quotes(id) ON DELETE CASCADE,
  CONSTRAINT fk_rfq_quote_lines_rfq_line FOREIGN KEY (rfq_line_id) REFERENCES rfq_lines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Register the staff page in the existing page-security catalogue.
INSERT IGNORE INTO scripts (script, pagesecurity, description)
VALUES ('RFQ.php', 0, 'Request for Quotation management');

-- Link an awarded RFQ quotation to the resulting purchase order.
SET @has_rfq_quote_id = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'PurchaseHeader' AND column_name = 'rfq_quote_id'
);
SET @rfq_alter_sql = IF(@has_rfq_quote_id = 0,
  'ALTER TABLE PurchaseHeader ADD COLUMN rfq_quote_id BIGINT UNSIGNED NULL, ADD KEY idx_purchaseheader_rfq_quote (rfq_quote_id)',
  'SELECT ''PurchaseHeader.rfq_quote_id already exists'' AS status');
PREPARE rfq_stmt FROM @rfq_alter_sql; EXECUTE rfq_stmt; DEALLOCATE PREPARE rfq_stmt;

SET @has_rfq_po_documentno = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'rfq_header' AND column_name = 'po_documentno'
);
SET @rfq_alter_sql = IF(@has_rfq_po_documentno = 0,
  'ALTER TABLE rfq_header ADD COLUMN po_documentno VARCHAR(20) NULL',
  'SELECT ''rfq_header.po_documentno already exists'' AS status');
PREPARE rfq_stmt FROM @rfq_alter_sql; EXECUTE rfq_stmt; DEALLOCATE PREPARE rfq_stmt;

SET @has_rfq_tax_inclusive = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'rfq_quotes' AND column_name = 'tax_inclusive'
);
SET @rfq_alter_sql = IF(@has_rfq_tax_inclusive = 0,
  'ALTER TABLE rfq_quotes ADD COLUMN tax_inclusive TINYINT(1) NOT NULL DEFAULT 0 AFTER accepted_terms',
  'SELECT ''rfq_quotes.tax_inclusive already exists'' AS status');
PREPARE rfq_stmt FROM @rfq_alter_sql; EXECUTE rfq_stmt; DEALLOCATE PREPARE rfq_stmt;


-- Internal inventory classification used by LIMS sync and RFQ filtering.
SET @has_is_test_parameter = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'stockmaster' AND column_name = 'is_test_parameter'
);
SET @rfq_tag_sql = IF(@has_is_test_parameter = 0,
  'ALTER TABLE stockmaster ADD COLUMN is_test_parameter TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT ''stockmaster.is_test_parameter already exists'' AS status');
PREPARE rfq_tag_stmt FROM @rfq_tag_sql; EXECUTE rfq_tag_stmt; DEALLOCATE PREPARE rfq_tag_stmt;

SET @has_is_standard_method = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'stockmaster' AND column_name = 'is_standard_method'
);
SET @rfq_tag_sql = IF(@has_is_standard_method = 0,
  'ALTER TABLE stockmaster ADD COLUMN is_standard_method TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT ''stockmaster.is_standard_method already exists'' AS status');
PREPARE rfq_tag_stmt FROM @rfq_tag_sql; EXECUTE rfq_tag_stmt; DEALLOCATE PREPARE rfq_tag_stmt;


-- Record SMTP transport attempts and exact failure reasons for RFQ invitations.
CREATE TABLE IF NOT EXISTS rfq_email_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rfq_id BIGINT UNSIGNED NOT NULL,
  supplier_code VARCHAR(20) NOT NULL,
  supplier_email VARCHAR(150) NOT NULL,
  transport VARCHAR(20) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rfq_email_log_rfq (rfq_id),
  KEY idx_rfq_email_log_attempted (attempted_at),
  CONSTRAINT fk_rfq_email_log_header FOREIGN KEY (rfq_id) REFERENCES rfq_header(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
