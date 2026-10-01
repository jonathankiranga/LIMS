<?php
header('Content-Type: application/json');
include('../includes/ConnectAjax.php');

$ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($ID <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid rule ID'));
    exit;
}

DB_query("DELETE FROM cost_allocation_rules WHERE id=$ID", $db);

echo json_encode(array('success' => true, 'message' => 'Rule deleted'));
