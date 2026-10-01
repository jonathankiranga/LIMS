<?php
header('Content-Type: application/json');
include('../includes/ConnectAjax.php');
include('../includes/AccountBudgets.inc');

$PeriodID = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;

$result = db_AccountProject($PeriodID);

$rows = array();
$totalBudget = 0;
$totalExpenses = 0;
$totalCommitted = 0;
$totalTotal = 0;
$totalBalance = 0;

while ($row = DB_fetch_array($result)) {
    $b = floatval($row['BudgetAmount']);
    $e = floatval($row['Expeses']);
    $c = floatval($row['Committed']);
    $t = floatval($row['Total']);
    $bal = floatval($row['Balance']);
    $pct = floatval($row['percent']);

    $rows[] = array(
        'code' => $row['Code'],
        'budget_name' => $row['BudgetName'],
        'budget_amount' => round($b, 2),
        'expenses' => round($e, 2),
        'committed' => round($c, 2),
        'total' => round($t, 2),
        'balance' => round($bal, 2),
        'percent' => round($pct, 1),
    );

    $totalBudget += $b;
    $totalExpenses += $e;
    $totalCommitted += $c;
    $totalTotal += $t;
    $totalBalance += $bal;
}

echo json_encode(array(
    'success' => true,
    'rows' => $rows,
    'summary' => array(
        'total_budget'    => round($totalBudget, 2),
        'total_expenses'  => round($totalExpenses, 2),
        'total_committed' => round($totalCommitted, 2),
        'total_total'     => round($totalTotal, 2),
        'total_balance'   => round($totalBalance, 2),
    ),
));
