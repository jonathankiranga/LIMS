<?php
$PageSecurity = 0;
include('includes/session.inc');
$RootPath = isset($RootPath) ? $RootPath : '.';
header('Location: ' . $RootPath . '/homepage.php');
exit;
