<?php

// Function to get the next transaction number and increment it in the database
function GetNextTransNo($TransType) {
    global $conn; // Use the MySQLi connection

    // Increment the transaction number
    $SQL = sprintf("UPDATE systypes SET typeno = typeno + 1 WHERE typeid = '%s'", $TransType);
    if (!$conn->query($SQL)) {
        die(_('CRITICAL ERROR') . ': ' . _('The transaction number could not be incremented. MySQL error: ') . $conn->error);
    }

    // Retrieve the new transaction number
    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno FROM systypes WHERE typeid = '%s'", $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = trim($myrow[1]);

    return $prefix . $nexno;
}
// Function to preview the next transaction number without updating it
function GetTempNextNo($TransType) {
    global $conn; // Use the MySQLi connection

    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno + 1 FROM systypes WHERE typeid = '%s'",$TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = trim($myrow[1]);

    return $prefix . $nexno;
}
// Function to get the next lab reference number and increment it in the database
function GetNextLabrefNo($TransType) {
    global $conn; // Use the MySQLi connection

    // Increment the transaction number
    $SQL = sprintf("UPDATE systypes SET typeno = typeno + 1 WHERE typeid = '%s'", $TransType);
    if (!$conn->query($SQL)) {
        die(_('CRITICAL ERROR') . ': ' . _('The transaction number could not be incremented. MySQL error: ') . $conn->error);
    }

    // Retrieve the new transaction number
    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno FROM systypes WHERE typeid = '%s'", $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = str_pad(trim($myrow[1]), 6, '0', STR_PAD_LEFT); // Pad with zeros if required

    return $prefix . $nexno;
}
// Function to preview the next lab reference number without updating it
function GetTempLabrefNo($TransType) {
    global $conn; // Use the MySQLi connection

    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno + 1 FROM systypes WHERE typeid = '%s'", $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = str_pad(trim($myrow[1]), 6, '0', STR_PAD_LEFT); // Pad with zeros if required

    return $prefix . $nexno;
}
// Function to preview a barcode reference number with a custom seed
function GetTempBarcoderfNo($TransType, $seed = 1) {
    global $conn; // Use the MySQLi connection

    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, %d + typeno FROM systypes WHERE typeid = '%s'",  $seed,  $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = str_pad(trim($myrow[1]), 6, '0', STR_PAD_LEFT); // Pad with zeros if required

    return $prefix . $nexno;
}