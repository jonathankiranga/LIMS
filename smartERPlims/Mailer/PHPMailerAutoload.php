<?php
/**
 * Backward-compatible loader for the PHPMailer 7.x upgrade.
 *
 * The application still uses the legacy global class names. These aliases
 * allow existing callers to continue using PHPMailer, SMTP and
 * phpmailerException while the implementation comes from PHPMailer 7.1.1.
 */

require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';

if (!class_exists('PHPMailer', false)) {
    class_alias('PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer');
}

if (!class_exists('SMTP', false)) {
    class_alias('PHPMailer\\PHPMailer\\SMTP', 'SMTP');
}

if (!class_exists('phpmailerException', false)) {
    class_alias('PHPMailer\\PHPMailer\\Exception', 'phpmailerException');
}

/**
 * Retain the old autoloader function name for legacy includes.
 * Classes are loaded eagerly above, so no further work is required here.
 *
 * @param string $classname
 * @return void
 */
if (!function_exists('PHPMailerAutoload')) {
    function PHPMailerAutoload($classname)
    {
        // Compatibility entry point; the namespaced classes are already loaded.
    }
}
