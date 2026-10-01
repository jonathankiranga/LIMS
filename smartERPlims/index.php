<?php
// Slim index: ensure login, then send user into the new JS dashboard (homepage.php).
$PageSecurity = 0;
include('includes/session.inc');

// If login required, session.inc will render Login.php and exit.

$RootPath = isset($RootPath) ? $RootPath : '.';
header('Location: ' . $RootPath . '/homepage.php');
exit;
