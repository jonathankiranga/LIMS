<?php
require '../db_connection.php';

$page       = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$size       = isset($_GET['size']) ? (int) $_GET['size'] : 10;
$offset     = ($page - 1) * $size;

$filterCol  = isset($_GET['filter_col']) ? $_GET['filter_col'] : '';
$filterOp   = isset($_GET['filter_op']) ? $_GET['filter_op'] : '';
$filterVal  = isset($_GET['filter_val']) ? trim($_GET['filter_val']) : '';

$sort       = isset($_GET['sort']) ? trim($_GET['sort']) : 'ParameterID';
$sortDir    = isset($_GET['sort_dir']) ? strtoupper(trim($_GET['sort_dir'])) : 'ASC';

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

$colMap = [
    '0' => ['name' => 'ParameterID', 'type' => 'numeric'],
    '1' => ['name' => 'ParameterName', 'type' => 'text'],
    '2' => ['name' => 'Limits', 'type' => 'text'],
    '3' => ['name' => 'MinLimit', 'type' => 'numeric'],
    '4' => ['name' => 'MaxLimit', 'type' => 'numeric'],
    '5' => ['name' => 'UnitOfMeasure', 'type' => 'text'],
    '6' => ['name' => 'Category', 'type' => 'text'],
    '7' => ['name' => 'ResultType', 'type' => 'text'],
];

$where = '';
$countWhere = '';
$bindTypes = '';
$bindValues = [];
$countBindTypes = '';
$countBindValues = [];

if ($filterCol !== '' && $filterVal !== '') {
    $conditions = [];
    $params = [];
    $types = '';

    if ($filterCol === '-1') {
        $likePattern = '%' . $filterVal . '%';
        $textCols = ['1', '2', '5', '6', '7'];
        foreach ($textCols as $c) {
            if (isset($colMap[$c])) {
                $conditions[] = $colMap[$c]['name'] . " LIKE ?";
                $types .= 's';
                $params[] = $likePattern;
            }
        }
        $conditions[] = "CAST(ParameterID AS CHAR) LIKE ?";
        $types .= 's';
        $params[] = $likePattern;

        $where = "WHERE " . implode(' OR ', $conditions);
        $countWhere = $where;
    } elseif (isset($colMap[$filterCol])) {
        $col = $colMap[$filterCol];

        if ($col['type'] === 'text') {
            switch ($filterOp) {
                case 'contains':
                    $conditions[] = $col['name'] . " LIKE ?";
                    $types .= 's';
                    $params[] = '%' . $filterVal . '%';
                    break;
                case 'starts':
                    $conditions[] = $col['name'] . " LIKE ?";
                    $types .= 's';
                    $params[] = $filterVal . '%';
                    break;
                case 'ends':
                    $conditions[] = $col['name'] . " LIKE ?";
                    $types .= 's';
                    $params[] = '%' . $filterVal;
                    break;
                case '=':
                    $conditions[] = $col['name'] . " = ?";
                    $types .= 's';
                    $params[] = $filterVal;
                    break;
                case '!=':
                    $conditions[] = $col['name'] . " != ?";
                    $types .= 's';
                    $params[] = $filterVal;
                    break;
            }
        } else {
            $numVal = is_numeric($filterVal) ? $filterVal : 0;
            switch ($filterOp) {
                case '=':
                    $conditions[] = $col['name'] . " = ?";
                    $types .= 's';
                    $params[] = $numVal;
                    break;
                case '!=':
                    $conditions[] = $col['name'] . " != ?";
                    $types .= 's';
                    $params[] = $numVal;
                    break;
                case '>':
                    $conditions[] = $col['name'] . " > ?";
                    $types .= 's';
                    $params[] = $numVal;
                    break;
                case '<':
                    $conditions[] = $col['name'] . " < ?";
                    $types .= 's';
                    $params[] = $numVal;
                    break;
                case '>=':
                    $conditions[] = $col['name'] . " >= ?";
                    $types .= 's';
                    $params[] = $numVal;
                    break;
                case '<=':
                    $conditions[] = $col['name'] . " <= ?";
                    $types .= 's';
                    $params[] = $numVal;
                    break;
            }
        }

        if (!empty($conditions)) {
            $where = "WHERE " . implode(' AND ', $conditions);
            $countWhere = $where;
        }
    }

    $bindTypes = $types . 'ii';
    $bindValues = array_merge($params, [$offset, $size]);
    $countBindTypes = $types;
    $countBindValues = $params;
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
