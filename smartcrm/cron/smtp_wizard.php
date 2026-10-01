<?php
/**
 * SMTP config wizard.
 * Run via CLI: php smtp_wizard.php > smtp_config.php
 * Then include/require the generated smtp_config.php from your scripts.
 */

if (php_sapi_name() !== 'cli') {
    echo "Run this script from the command line: php smtp_wizard.php > smtp_config.php\n";
    exit(1);
}

function ask($prompt, $default = '')
{
    $suffix = $default !== '' ? " [$default]" : '';
    echo "$prompt$suffix: ";
    $line = trim(fgets(STDIN));
    return $line === '' ? $default : $line;
}

$host     = ask('SMTP host', 'smtp.example.com');
$auth     = strtolower(ask('Use SMTP auth? (yes/no)', 'yes')) === 'yes';
$user     = $auth ? ask('SMTP username', 'user@example.com') : '';
$pass     = $auth ? ask('SMTP password', '') : '';
$secure   = ask('Security (tls/ssl/none)', 'tls');
$portDefault = $secure === 'ssl' ? 465 : 587;
$port     = (int)ask('Port', $portDefault);
$fromEmail = ask('From email', 'no-reply@example.com');
$fromName  = ask('From name', 'Smarternow CRM');

$config = <<<'PHP'
<?php
// Generated SMTP configuration
$SMTP_CONFIG = [
    'Host'       => '__HOST__',
    'SMTPAuth'   => __AUTH__,
    'Username'   => '__USER__',
    'Password'   => '__PASS__',
    'SMTPSecure' => '__SECURE__',
    'Port'       => __PORT__,
    'FromEmail'  => '__FROM_EMAIL__',
    'FromName'   => '__FROM_NAME__',
];
PHP;

$replacements = [
    '__HOST__'       => addslashes($host),
    '__AUTH__'       => $auth ? 'true' : 'false',
    '__USER__'       => addslashes($user),
    '__PASS__'       => addslashes($pass),
    '__SECURE__'     => $secure === 'none' ? '' : addslashes($secure),
    '__PORT__'       => $port,
    '__FROM_EMAIL__' => addslashes($fromEmail),
    '__FROM_NAME__'  => addslashes($fromName),
];

foreach ($replacements as $k => $v) {
    $config = str_replace($k, $v, $config);
}

echo $config;
echo "\n";
