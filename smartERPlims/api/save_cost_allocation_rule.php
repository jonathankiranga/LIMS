<?php
header('Content-Type: application/json');
include('../includes/ConnectAjax.php');

$Input = json_decode(file_get_contents('php://input'), true);
if (!$Input) {
    $Input = $_POST;
}

$GLAccount     = isset($Input['gl_account'])      ? mysqli_real_escape_string($db, $Input['gl_account']) : '';
$Driver        = isset($Input['allocation_driver']) ? mysqli_real_escape_string($db, $Input['allocation_driver']) : '';
$Component     = isset($Input['cost_component'])   ? mysqli_real_escape_string($db, $Input['cost_component']) : '';
$Description   = isset($Input['description'])      ? mysqli_real_escape_string($db, $Input['description']) : '';
$IsActive      = isset($Input['is_active'])        ? intval($Input['is_active']) : 1;
$ID            = isset($Input['id'])                ? intval($Input['id']) : 0;

if (empty($GLAccount) || empty($Driver) || empty($Component)) {
    echo json_encode(array('success' => false, 'message' => 'Missing required fields'));
    exit;
}

// Validate GL account exists
$chk = DB_query("SELECT accno FROM acct WHERE accno='$GLAccount'", $db);
if (DB_num_rows($chk) == 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid GL account'));
    exit;
}

if ($ID > 0) {
    DB_query("UPDATE cost_allocation_rules
        SET gl_account='$GLAccount', allocation_driver='$Driver', cost_component='$Component',
            description='$Description', is_active=$IsActive, updated_at=NOW()
        WHERE id=$ID", $db);
    $msg = 'Rule updated';
} else {
    DB_query("INSERT INTO cost_allocation_rules (gl_account, allocation_driver, cost_component, description, is_active)
        VALUES ('$GLAccount','$Driver','$Component','$Description',$IsActive)", $db);
    $msg = 'Rule created';
}

echo json_encode(array('success' => true, 'message' => $msg));
