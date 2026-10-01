<?php
header('Content-Type: application/json');
include('../includes/ConnectAjax.php');
include('../limsconfig.php');

$DateFrom = isset($_GET['date_from']) ? mysqli_real_escape_string($db, $_GET['date_from']) : date('Y-m-01');
$DateTo   = isset($_GET['date_to'])   ? mysqli_real_escape_string($db, $_GET['date_to'])   : date('Y-m-t');
$PeriodID = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;

// Check if allocation results exist for this period/date range
if ($PeriodID > 0) {
    $sql = "SELECT
        car.test_itemcode,
        sm.descrip as test_name,
        car.cost_component,
        SUM(car.allocated_amount) as amount,
        SUM(car.tests_count) as tests_count,
        GROUP_CONCAT(DISTINCT car.gl_account ORDER BY car.gl_account SEPARATOR ', ') as gl_accounts,
        (SELECT price FROM PriceList WHERE stockcode = sm.itemcode AND approved = 1 LIMIT 1) as sales_price
    FROM cost_allocation_results car
    JOIN stockmaster sm ON sm.itemcode = car.test_itemcode
    WHERE car.period_id = $PeriodID
    GROUP BY car.test_itemcode, sm.descrip, car.cost_component
    ORDER BY sm.descrip, car.cost_component";
} else {
    $sql = "SELECT
        car.test_itemcode,
        sm.descrip as test_name,
        car.cost_component,
        SUM(car.allocated_amount) as amount,
        SUM(car.tests_count) as tests_count,
        GROUP_CONCAT(DISTINCT car.gl_account ORDER BY car.gl_account SEPARATOR ', ') as gl_accounts,
        (SELECT price FROM PriceList WHERE stockcode = sm.itemcode AND approved = 1 LIMIT 1) as sales_price
    FROM cost_allocation_results car
    JOIN stockmaster sm ON sm.itemcode = car.test_itemcode
    JOIN cost_allocation_periods cap ON cap.id = car.period_id
    WHERE cap.period_start >= '$DateFrom' AND cap.period_end <= '$DateTo'
    GROUP BY car.test_itemcode, sm.descrip, car.cost_component
    ORDER BY sm.descrip, car.cost_component";
}

$result = DB_query($sql, $db);

// Pivot results by test
$tests = array();
$totalMaterial = 0;
$totalLabor = 0;
$totalEquipment = 0;
$totalOverhead = 0;
$totalTests = 0;

while ($row = DB_fetch_array($result)) {
    $code = $row['test_itemcode'];
    if (!isset($tests[$code])) {
        $tests[$code] = array(
            'test_name' => $row['test_name'],
            'material' => 0,
            'labor' => 0,
            'equipment' => 0,
            'overhead' => 0,
            'tests_count' => 0,
            'sales_price' => floatval($row['sales_price']),
            'gl_accounts' => array()
        );
    }

    $amt = floatval($row['amount']);
    $tests[$code][$row['cost_component']] += $amt;
    $tests[$code]['tests_count'] = max($tests[$code]['tests_count'], intval($row['tests_count']));

    if (!empty($row['gl_accounts'])) {
        $glArr = array_map('trim', explode(',', $row['gl_accounts']));
        $tests[$code]['gl_accounts'] = array_unique(array_merge($tests[$code]['gl_accounts'], $glArr));
    }

    switch ($row['cost_component']) {
        case 'material':    $totalMaterial += $amt; break;
        case 'labor':       $totalLabor += $amt; break;
        case 'equipment':   $totalEquipment += $amt; break;
        case 'overhead':    $totalOverhead += $amt; break;
    }
}

// Build rows for spreadsheet
$rows = array();
foreach ($tests as $code => $t) {
    $total = $t['material'] + $t['labor'] + $t['equipment'] + $t['overhead'];
    $cpt = $t['tests_count'] > 0 ? $total / $t['tests_count'] : 0;
    $profit = $t['sales_price'] > 0 ? $t['sales_price'] - $total : 0;
    $margin = $t['sales_price'] > 0 ? round($profit / $t['sales_price'] * 100, 1) : 0;
    $rows[] = array(
        'test_name'   => $t['test_name'],
        'tests_count' => $t['tests_count'],
        'sales_price' => $t['sales_price'],
        'profit'      => round($profit, 2),
        'profit_margin' => $margin,
        'material'    => round($t['material'], 2),
        'labor'       => round($t['labor'], 2),
        'equipment'   => round($t['equipment'], 2),
        'overhead'    => round($t['overhead'], 2),
        'total'       => round($total, 2),
        'cost_per_test' => round($cpt, 2),
        'gl_accounts' => implode(', ', $t['gl_accounts'])
    );
}

// Sort by total descending
usort($rows, function($a, $b) { return $b['total'] <=> $a['total']; });

$grandTotal = $totalMaterial + $totalLabor + $totalEquipment + $totalOverhead;
$totalTestsCount = 0;
foreach ($rows as $r) { $totalTestsCount += $r['tests_count']; }

echo json_encode(array(
    'success' => true,
    'rows' => $rows,
    'summary' => array(
        'total_tests'     => $totalTestsCount,
        'total_material'  => round($totalMaterial, 2),
        'total_labor'     => round($totalLabor, 2),
        'total_equipment' => round($totalEquipment, 2),
        'total_overhead'  => round($totalOverhead, 2),
        'grand_total'     => round($grandTotal, 2)
    )
));
