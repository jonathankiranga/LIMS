<?php
require '../db_connection.php';

$page       = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$size       = isset($_GET['size']) ? (int) $_GET['size'] : 10;
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort       = isset($_GET['sort']) ? trim($_GET['sort']) : 'ParameterID';
$sortDir    = isset($_GET['sort_dir']) ? strtoupper(trim($_GET['sort_dir'])) : 'ASC';
$offset     = ($page - 1) * $size;

$allowedSort = ['ParameterID', 'ParameterName', 'NeutralityID', 'TdsID'];
if (!in_array($sort, $allowedSort)) {
    $sort = 'ParameterID';
}
if (!in_array($sortDir, ['ASC', 'DESC'])) {
    $sortDir = 'ASC';
}

$NEUTRALITY = [
    ["id" => "1","ion_name" => "Na⁺"],
    ["id" => "2","ion_name" => "K⁺"],
    ["id" => "3","ion_name" => "Ca²⁺"],
    ["id" => "4","ion_name" => "Mg²⁺"],
    ["id" => "5","ion_name" => "NH₄⁺"],
    ["id" => "6","ion_name" => "Cl⁻"],
    ["id" => "7","ion_name" => "HCO₃⁻"],
    ["id" => "8","ion_name" => "NO₃⁻"],
    ["id" => "9","ion_name" => "SO₄²⁻"],
    ["id" => "10","ion_name" => "CO₃²⁻"]
];
$findn = [];
foreach ($NEUTRALITY as $value) {
    $findn[$value['id']] = $value['ion_name'];
}

$TDS = [
    ["id" => "1",'Element' => 'Hydrogen'],
    ["id" => "2",'Element' => 'Oxygen'],
    ["id" => "3",'Element' => 'Nitrogen'],
    ["id" => "4",'Element' => 'Carbon'],
    ["id" => "5",'Element' => 'Sodium'],
    ["id" => "6",'Element' => 'Potassium'],
    ["id" => "7",'Element' => 'Calcium'],
    ["id" => "8",'Element' => 'Magnesium'],
    ["id" => "9",'Element' => 'Iron'],
    ["id" => "10",'Element' => 'Copper'],
    ["id" => "11",'Element' => 'Lead'],
    ["id" => "12",'Element' => 'Zinc'],
    ["id" => "13",'Element' => 'Manganese'],
    ["id" => "14",'Element' => 'Chlorine'],
    ["id" => "15",'Element' => 'Fluorine'],
    ["id" => "16",'Element' => 'Boron'],
    ["id" => "17",'Element' => 'Sulfur'],
    ["id" => "18",'Element' => 'Phosphorus'],
];
$findt = [];
foreach ($TDS as $value) {
    $findt[$value['id']] = $value['Element'];
}

$where = '';
$countWhere = '';
$bindTypes = '';
$bindValues = [];
$countBindTypes = '';
$countBindValues = [];

if ($searchTerm !== '') {
    $where = "WHERE ts.ParameterID LIKE ? OR ts.ParameterName LIKE ? ";
    $countWhere = "WHERE ParameterID LIKE ? OR ParameterName LIKE ? ";
    $searchPattern = '%' . $searchTerm . '%';
    $bindTypes = 'ssii';
    $bindValues = [$searchPattern, $searchPattern, $offset, $size];
    $countBindTypes = 'ss';
    $countBindValues = [$searchPattern, $searchPattern];
} else {
    $bindTypes = 'ii';
    $bindValues = [$offset, $size];
}

$countSql = "SELECT COUNT(*) FROM baseparameters $countWhere";
$stmt = $conn->prepare($countSql);
if ($countBindTypes !== '') {
    $stmt->bind_param($countBindTypes, ...$countBindValues);
}
$stmt->execute();
$stmt->bind_result($totalRecords);
$stmt->fetch();
$stmt->close();
$lastPage = max(1, ceil($totalRecords / $size));

$query = $conn->prepare("SELECT ts.* FROM baseparameters ts $where ORDER BY $sort $sortDir LIMIT ?, ?");
$query->bind_param($bindTypes, ...$bindValues);
$query->execute();
$result = $query->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $nid = isset($row['NeutralityID']) ? trim($row['NeutralityID']) : '';
    $tid = isset($row['TdsID']) ? trim($row['TdsID']) : '';
    $row['Neutrality'] = isset($findn[$nid]) ? $findn[$nid] : '';
    $row['Tds'] = isset($findt[$tid]) ? $findt[$tid] : '';
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode([
    'last_page' => $lastPage,
    'data' => $data,
], JSON_INVALID_UTF8_SUBSTITUTE);
