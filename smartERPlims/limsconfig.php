<?php
// LIMS database connection for AJAX endpoints inside smartERPlims.
// Reuses the blockchain config so credentials are maintained in one place.

$limsConfig = [
    'DB_HOST' => 'localhost',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => 'mysqlpassword',
    'DB_NAME' => 'lims_encrpted'
];


$limsHost = $limsConfig['DB_HOST'] ?? 'localhost';
$limsUser = $limsConfig['DB_USERNAME'] ?? '';
$limsPass = $limsConfig['DB_PASSWORD'] ?? '';
$limsName = $limsConfig['DB_NAME'] ?? '';

$limsConn = new mysqli($limsHost, $limsUser, $limsPass, $limsName);
if ($limsConn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'LIMS database connection failed']);
    exit;
}

$limsConn->query("SET time_zone = '+03:00'");

function lims_prepare($sql) {
    global $limsConn;
    return $limsConn->prepare($sql);
}

function lims_query($sql) {
    global $limsConn;
    return $limsConn->query($sql);
}
