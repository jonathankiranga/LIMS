<?php
header('Content-Type: application/json');
include('../includes/ConnectAjax.php');

$Result = DB_query("
    SELECT car.*, a.accdesc
    FROM cost_allocation_rules car
    JOIN acct a ON a.accno = car.gl_account
    ORDER BY car.cost_component, car.gl_account
", $db);

$rules = array();
while ($row = DB_fetch_array($Result)) {
    $rules[] = $row;
}

echo json_encode(array('success' => true, 'rules' => $rules));
