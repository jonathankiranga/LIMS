<?php

ini_set('session.gc_maxlifetime', 3600);
session_name('ErpWithCRM');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (isset($_POST['dbtype'])) {
    $newType = trim($_POST['dbtype']);
    if ($newType == 'LIMS' || $newType == 'General') {
        $_SESSION['DatabaseType'] = $newType;
        echo 'success';
        exit;
    }
}

echo 'error';
exit;
?>