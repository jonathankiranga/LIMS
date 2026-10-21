-- Add the qualitative result options manager under the existing Admin menu.
-- Safe to rerun: skips insertion when the menu URL already exists.
INSERT INTO menu_items (title, icon, url, parent_id, security_id)
SELECT
    'Manage Qualitative Result Options',
    'fas fa-list-check',
    'manage_qualitative_result_options.php',
    admin_menu.id,
    0
FROM menu_items AS admin_menu
WHERE admin_menu.title = 'Admin'
  AND admin_menu.parent_id IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM menu_items AS existing_menu
      WHERE existing_menu.url = 'manage_qualitative_result_options.php'
  )
ORDER BY admin_menu.id
LIMIT 1;
