<?php
header('Content-Type: application/json');
include('../includes/ConnectAjax.php');
include('../includes/CostAllocationEngine.php');

$Input = json_decode(file_get_contents('php://input'), true);
if (!$Input) {
    $Input = $_POST;
}

$PeriodStart = isset($Input['period_start']) ? $Input['period_start'] : '';
$PeriodEnd   = isset($Input['period_end'])   ? $Input['period_end'] : '';

if (empty($PeriodStart) || empty($PeriodEnd)) {
    echo json_encode(array('success' => false, 'message' => 'Missing period dates'));
    exit;
}

$engine = new CostAllocationEngine($db);
$result = $engine->runAllocation($PeriodStart, $PeriodEnd);

if (!$result['success']) {
    echo json_encode(array('success' => false, 'message' => $result['message']));
    exit;
}

echo json_encode(array(
    'success' => true,
    'message' => "Allocation completed. {$result['entries_created']} entries across {$result['tests_count']} test instances.",
    'period_id' => $result['period_id'],
    'tests_count' => $result['tests_count'],
    'entries_created' => $result['entries_created'],
    'summary' => $result['summary']
));
