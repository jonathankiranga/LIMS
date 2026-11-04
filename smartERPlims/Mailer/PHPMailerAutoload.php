<?php
/**
 * Backward-compatible loader for the PHPMailer 7.x upgrade.
 *
 * Existing ERP code continues to use the legacy global class names
 * PHPMailer and phpmailerException. The implementation is provided by
 * the namespaced PHPMailer 7.1.1 classes.
 */

$mailerPath = dirname(__FILE__);

require_once $mailerPath . '/src/Exception.php';
require_once $mailerPath . '/src/PHPMailer.php';
require_once $mailerPath . '/src/SMTP.php';

if (!class_exists('PHPMailer', false)) {
    class_alias('PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer');
}

if (!class_exists('phpmailerException', false)) {
    class_alias('PHPMailer\\PHPMailer\\Exception', 'phpmailerException');
}

/**
 * Retain the old autoloader function name for legacy includes.
 * The PHPMailer classes are loaded eagerly above.
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
