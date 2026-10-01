<?php
$host = "localhost";
$user = "root";
$pass = "mysqlpassword";
$db = "lims_encrpted";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error . PHP_EOL);

require "E:/limsISO/blockchain/vendor/autoload.php";
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
$spreadsheet = $reader->load("E:/limsISO/blockchain/Lab_foxx_data_29.4.2024.xlsx");
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();

$excelData = [];
for ($row = 2; $row <= $highestRow; $row++) {
    $param = trim((string)$sheet->getCell("D".$row)->getValue());
    $limits = trim((string)$sheet->getCell("E".$row)->getValue());
    $unit = trim((string)$sheet->getCell("F".$row)->getValue());
    $minLim = $sheet->getCell("G".$row)->getValue();
    $maxLim = $sheet->getCell("H".$row)->getValue();
    $testType = trim((string)$sheet->getCell("I".$row)->getValue());
    if (empty($param)) continue;
    $excelData[] = compact('param','limits','unit','minLim','maxLim','testType');
}

function normalizeParam($s) {
    $s = strtolower(trim($s));
    $s = preg_replace("/\s*,\s*(min|max|Max|Min)\s*$/i", "", $s);
    $s = preg_replace("/\s*\([^)]*\)/", "", $s);
    $s = preg_replace("/\s+as\s+[a-z0-9]+$/i", "", $s);
    $s = preg_replace("/\s*(max|min)\s*$/i", "", $s);
    $s = preg_replace("/\s*(mg\/kg|mg\/l|ug\/kg|%m\/m|%|ppm|IU\/kg|Kcal\/kg|Mj\/Kg|\xc2\xb5g\/l|g\/ml|oC|\xc2\xb0C|Bq\/L|cfu\/\w+|MPN\/\w+|g\/\d+\w*|vol\.|by Vol\.|by volume)\s*/i", " ", $s);
    $s = preg_replace("/\s+/", " ", trim($s));
    return $s;
}

function inferResultType($limits) {
    if (empty($limits)) return null;
    $l = trim($limits);
    if (preg_match("/^[\d.]+\s*-\s*[\d.]+$/", $l)) return 'rangeField';
    if (preg_match("/^[<>≤≥]?\s*[\d.]+$/", $l)) return 'quantitativeField';
    if (preg_match("/^\d+(\.\d+)?$/", $l) && floatval($l) == 0) return 'qualitativeField';
    if (preg_match("/^\d+(\.\d+)?$/", $l)) return 'quantitativeField';
    return null;
}

$baseparams = [];
$res = $conn->query("SELECT ParameterID, ParameterName FROM baseparameters");
while ($row = $res->fetch_assoc()) {
    $baseparams[] = [
        'id' => $row["ParameterID"],
        'name' => $row["ParameterName"],
        'norm' => normalizeParam($row["ParameterName"])
    ];
}

$rawUpdates = []; $unmatched = [];

foreach ($excelData as $ex) {
    $normParam = normalizeParam($ex['param']);
    $matchedBase = null;
    foreach ($baseparams as $bp) {
        if ($bp['norm'] === $normParam || levenshtein($bp['norm'], $normParam) <= 2) {
            $matchedBase = $bp;
            break;
        }
    }
    if (!$matchedBase) {
        $unmatched[] = $ex['param'];
        continue;
    }
    $rt = inferResultType($ex['limits']);
    $rawUpdates[$matchedBase['id']] = [
        'id' => $matchedBase['id'],
        'name' => $matchedBase['name'],
        'limits' => $ex['limits'],
        'min' => floatval($ex['minLim']),
        'max' => floatval($ex['maxLim']),
        'unit' => $ex['unit'],
        'resultType' => $rt
    ];
}

$updates = array_values($rawUpdates);
usort($updates, function($a, $b) { return $a['id'] - $b['id']; });

echo "-- baseparameters merge from Lab Foxx Excel | Unique parameters: " . count($updates) . " | Unmatched: " . count($unmatched) . PHP_EOL . PHP_EOL;

foreach ($updates as $u) {
    $l = addslashes($u['limits']);
    $un = addslashes($u['unit']);
    $minS = $u['min'] > 0 ? "MinLimit={$u['min']}" : "MinLimit=MinLimit";
    $maxS = $u['max'] > 0 ? "MaxLimit={$u['max']}" : "MaxLimit=MaxLimit";
    $unS = !empty($u['unit']) ? "UnitOfMeasure='{$un}'" : "UnitOfMeasure=UnitOfMeasure";
    $lS = !empty($u['limits']) ? "Limits='{$l}'" : "Limits=Limits";
    $rtS = $u['resultType'] ? "ResultType='{$u['resultType']}'" : "ResultType=ResultType";
    echo "UPDATE baseparameters SET {$lS}, {$minS}, {$maxS}, {$unS}, {$rtS}, UpdatedAt=NOW() WHERE ParameterID={$u['id']}; -- {$u['name']}" . PHP_EOL;
}

echo PHP_EOL . "-- UNMATCHED (" . count($unmatched) . "):" . PHP_EOL;
foreach (array_unique($unmatched) as $u) echo "-- {$u}" . PHP_EOL;

$conn->close();
