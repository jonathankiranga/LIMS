<?php
header('Content-Type: application/json');
include('../includes/ConnectAjax.php');

$PeriodID = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;

$sql = "SELECT car.*, sm.descrip as test_name, a.accdesc as gl_desc
    FROM cost_allocation_results car
    JOIN stockmaster sm ON sm.itemcode = car.test_itemcode
    JOIN acct a ON a.accno = car.gl_account";

if ($PeriodID > 0) {
    $sql .= " WHERE car.period_id = $PeriodID";
}

$sql .= " ORDER BY sm.descrip, car.cost_component";

$Result = DB_query($sql, $db);

$results = array();
while ($row = DB_fetch_array($Result)) {
    $results[] = $row;
}

echo json_encode(array('success' => true, 'results' => $results));
