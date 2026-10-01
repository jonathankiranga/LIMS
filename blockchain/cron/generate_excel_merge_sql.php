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

$standards = [];
$res = $conn->query("SELECT StandardID, StandardName FROM teststandards");
while ($row = $res->fetch_assoc()) $standards[strtolower(trim($row["StandardName"]))] = $row["StandardID"];

$testparams = [];
$res = $conn->query("SELECT ParameterID, ParameterName, StandardID, Limits, MinLimit, MaxLimit, UnitOfMeasure FROM testparameters");
while ($row = $res->fetch_assoc()) {
    $key = strtolower(trim($row["ParameterName"])) . "|" . $row["StandardID"];
    $testparams[$key] = $row;
}

$excelData = [];
for ($row = 2; $row <= $highestRow; $row++) {
    $stdCode = trim((string)$sheet->getCell("A".$row)->getValue());
    $stdName = trim((string)$sheet->getCell("B".$row)->getValue());
    $param = trim((string)$sheet->getCell("D".$row)->getValue());
    $limits = trim((string)$sheet->getCell("E".$row)->getValue());
    $unit = trim((string)$sheet->getCell("F".$row)->getValue());
    $minLim = $sheet->getCell("G".$row)->getValue();
    $maxLim = $sheet->getCell("H".$row)->getValue();
    $testType = trim((string)$sheet->getCell("I".$row)->getValue());
    if (empty($param) || empty($stdCode)) continue;
    $excelData[] = compact('stdCode','stdName','param','limits','unit','minLim','maxLim','testType');
}

function normalizeParam($s) {
    $s = strtolower(trim($s));
    // Strip trailing min/max
    $s = preg_replace("/\s*,\s*(min|max|Max|Min)\s*$/i", "", $s);
    // Strip parenthetical content
    $s = preg_replace("/\s*\([^)]*\)/", "", $s);
    // Strip "as X" suffixes
    $s = preg_replace("/\s+as\s+[a-z0-9]+$/i", "", $s);
    // Strip embedded max/min
    $s = preg_replace("/\s*(max|min)\s*$/i", "", $s);
    // Strip units (order matters - longer patterns first)
    $s = preg_replace("/\s*(mg\/kg|mg\/l|ug\/kg|%m\/m|%|ppm|IU\/kg|Kcal\/kg|Mj\/Kg|\xc2\xb5g\/l|g\/ml|oC|\xc2\xb0C|Bq\/L|cfu\/\w+|MPN\/\w+|g\/\d+\w*|vol\.|by Vol\.|by volume)\s*/i", " ", $s);
    // Normalize whitespace
    $s = preg_replace("/\s+/", " ", trim($s));
    return $s;
}

// Load baseparameters as the parameter dictionary
$baseparams = [];
$res = $conn->query("SELECT ParameterID, ParameterName FROM baseparameters");
while ($row = $res->fetch_assoc()) {
    $norm = strtolower(trim($row["ParameterName"]));
    if (!empty($norm)) $baseparams[$row["ParameterID"]] = $norm;
}

// Build reverse lookup: normalized name => baseparameter IDs
$baseByName = [];
foreach ($baseparams as $bid => $bname) {
    if (!isset($baseByName[$bname])) $baseByName[$bname] = [];
    $baseByName[$bname][] = $bid;
}

$updates = []; $skipped = []; $unmatched = [];

foreach ($excelData as $ex) {
    // Match standard
    $stdKey = strtolower(trim($ex['stdName']));
    if (!isset($standards[$stdKey])) {
        $found = false;
        foreach ($standards as $sk => $sid) {
            if (strpos($sk, $stdKey) !== false || strpos($stdKey, $sk) !== false) { $stdKey = $sk; $found = true; break; }
        }
        if (!$found) { $unmatched[] = "Standard: {$ex['stdName']}"; continue; }
    }
    $stdID = $standards[$stdKey];
    $normParam = normalizeParam($ex['param']);

    // Try exact match first
    $tpKey = $normParam . "|" . $stdID;
    if (isset($testparams[$tpKey])) {
        $tp = $testparams[$tpKey];
        $shouldUpdate = false;
        if (!empty($ex['limits']) && $tp['Limits'] !== $ex['limits']) $shouldUpdate = true;
        if (floatval($ex['minLim']) > 0 && $tp['MinLimit'] != floatval($ex['minLim'])) $shouldUpdate = true;
        if (floatval($ex['maxLim']) > 0 && $tp['MaxLimit'] != floatval($ex['maxLim'])) $shouldUpdate = true;
        if (!empty($ex['unit']) && $tp['UnitOfMeasure'] !== $ex['unit']) $shouldUpdate = true;
        if ($shouldUpdate) {
            $updates[] = [
                'paramID'=>$tp['ParameterID'], 'paramName'=>$tp['ParameterName'], 'stdID'=>$stdID,
                'limits'=>$ex['limits'], 'min'=>floatval($ex['minLim']), 'max'=>floatval($ex['maxLim']), 'unit'=>$ex['unit'],
                'method'=>'exact_tp'
            ];
        }
        continue;
    }

    // Try matching via baseparameters dictionary
    $matchedBase = false;
    foreach ($baseByName as $bname => $bids) {
        if ($bname === $normParam || levenshtein($bname, $normParam) <= 2) {
            foreach ($bids as $bid) {
                foreach ($testparams as $k => $tp) {
                    if ($tp['BaseID'] == $bid && $tp['StandardID'] == $stdID) {
                        $shouldUpdate = false;
                        if (!empty($ex['limits']) && $tp['Limits'] !== $ex['limits']) $shouldUpdate = true;
                        if (floatval($ex['minLim']) > 0 && $tp['MinLimit'] != floatval($ex['minLim'])) $shouldUpdate = true;
                        if (floatval($ex['maxLim']) > 0 && $tp['MaxLimit'] != floatval($ex['maxLim'])) $shouldUpdate = true;
                        if (!empty($ex['unit']) && $tp['UnitOfMeasure'] !== $ex['unit']) $shouldUpdate = true;
                        if ($shouldUpdate) {
                            $updates[] = [
                                'paramID'=>$tp['ParameterID'], 'paramName'=>$tp['ParameterName'], 'stdID'=>$stdID,
                                'limits'=>$ex['limits'], 'min'=>floatval($ex['minLim']), 'max'=>floatval($ex['maxLim']), 'unit'=>$ex['unit'],
                                'method'=>"base:$bid($bname)"
                            ];
                        }
                        $matchedBase = true;
                        break 2;
                    }
                }
            }
            if ($matchedBase) break;
        }
    }

    if (!$matchedBase) {
        $skipped[] = "{$ex['param']} (StdID=$stdID)";
    }
}

echo "-- Lab Foxx Excel Merge SQL (UPDATE ONLY) | Updates: " . count($updates) . " | Skipped: " . count($skipped) . PHP_EOL . PHP_EOL;

foreach ($updates as $u) {
    $l = addslashes($u['limits']); $un = addslashes($u['unit']);
    $minS = $u['min'] > 0 ? "MinLimit={$u['min']}" : "MinLimit=MinLimit";
    $maxS = $u['max'] > 0 ? "MaxLimit={$u['max']}" : "MaxLimit=MaxLimit";
    $unS = !empty($u['unit']) ? "UnitOfMeasure='{$un}'" : "UnitOfMeasure=UnitOfMeasure";
    $lS = !empty($u['limits']) ? "Limits='{$l}'" : "Limits=Limits";
    echo "UPDATE testparameters SET {$lS}, {$minS}, {$maxS}, {$unS}, UpdatedAt=NOW() WHERE ParameterID={$u['paramID']}; -- [{$u['method']}] {$u['paramName']}" . PHP_EOL;
}

echo PHP_EOL . "-- UNMATCHED STANDARDS:" . PHP_EOL;
foreach (array_unique($unmatched) as $u) echo "-- {$u}" . PHP_EOL;

echo PHP_EOL . "-- SKIPPED (no matching testparameter found): " . count($skipped) . PHP_EOL;
foreach (array_unique($skipped) as $s) echo "-- {$s}" . PHP_EOL;

$conn->close();
