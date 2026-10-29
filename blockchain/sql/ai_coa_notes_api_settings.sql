-- AI-assisted COA Notes API settings.
-- API key is stored encrypted by the PHP settings page, never in this migration.
CREATE TABLE IF NOT EXISTS ai_api_settings (
    setting_name VARCHAR(64) NOT NULL,
    encrypted_value MEDIUMTEXT NOT NULL,
    nonce VARCHAR(32) NOT NULL,
    auth_tag VARCHAR(32) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add the settings page under the existing Admin menu; safe to rerun.
INSERT INTO menu_items (title, icon, url, parent_id, security_id)
SELECT
    'AI API Settings',
    'fas fa-key',
    'ai_api_settings.php',
    admin_menu.id,
    0
FROM menu_items AS admin_menu
WHERE admin_menu.title = 'Admin'
  AND admin_menu.parent_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_items AS existing_menu
      WHERE existing_menu.url = 'ai_api_settings.php'
  )
ORDER BY admin_menu.id
LIMIT 1;

-- Grant access to the existing administrator role (role ID 1).
INSERT IGNORE INTO role_security (role_id, security_id)
SELECT 1, id
FROM menu_items
WHERE url = 'ai_api_settings.php'
LIMIT 1;
