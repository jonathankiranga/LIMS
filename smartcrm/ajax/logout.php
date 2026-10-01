<?php
session_write_close(); //in case a previous session is not closed
session_name('ErpWithCRM');
include('includes/session.inc');
// Cleanup
session_unset();
session_destroy();

// Do not assume php.ini defaults are in use on other systems using this app.
$Name = session_name();

// We do not use session_set_cookie_params(), so fetch the php.ini values.
// This information is needed for proper handling to avoid the "common pitfalls"
// referenced within the PHP setcookie() documentation:
// "Cookies must be deleted with the same parameters as they were set with."
$CookieInfo = session_get_cookie_params();

/////////////////// OWASP

// Destroy the cookie handling based on OWASP recommendations:
// https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet#Proper_Deletion

setcookie($Name, '', 1, $CookieInfo['path']);
setcookie($Name, false);
unset($_COOKIE[$Name]);

/////////////////// END OWASP



header('Location: index.php');
?>