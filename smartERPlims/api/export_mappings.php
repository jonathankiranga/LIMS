<?php
include('../config.php');
$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if (!$db) { die("Database connection failed"); }

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="test_reagent_mappings.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['test_itemcode', 'reagent_itemcode', 'qty']);

$testCode = isset($_GET['test']) ? trim($_GET['test']) : '';
if ($testCode) {
  $stmt = mysqli_prepare($db, "SELECT test_itemcode, reagent_itemcode, quantity FROM test_reagent_mapping WHERE test_itemcode = ? ORDER BY reagent_itemcode");
  mysqli_stmt_bind_param($stmt, 's', $testCode);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
} else {
  $result = mysqli_query($db, "SELECT test_itemcode, reagent_itemcode, quantity FROM test_reagent_mapping ORDER BY test_itemcode, reagent_itemcode");
}

while ($row = mysqli_fetch_assoc($result)) {
  fputcsv($out, [trim($row['test_itemcode']), trim($row['reagent_itemcode']), $row['quantity']]);
}

fclose($out);
mysqli_close($db);
