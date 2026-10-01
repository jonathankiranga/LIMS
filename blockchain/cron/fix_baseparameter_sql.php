<?php
$inputFile = "E:/Projects_FilesAndDocuments/LabworksNew/baseparameters_updates.sql";
$outputFile = "E:/Projects_FilesAndDocuments/LabworksNew/baseparameters_updates_fixed.sql";

$lines = file($inputFile, FILE_IGNORE_NEW_LINES);

$pidOverrides = [
    9   => ['limits'=>NULL, 'min'=>NULL, 'max'=>NULL, 'unit'=>NULL, 'rt'=>NULL],
    52  => ['limits'=>'0.2', 'min'=>0.2, 'max'=>NULL, 'unit'=>'%', 'rt'=>'quantitativeField'],
    54  => ['limits'=>'0.15', 'min'=>0.15, 'max'=>NULL, 'unit'=>'%', 'rt'=>'quantitativeField'],
    96  => ['limits'=>'1 - 3', 'min'=>1, 'max'=>3, 'unit'=>'%', 'rt'=>'rangeField'],
    132 => ['limits'=>'6', 'min'=>NULL, 'max'=>6, 'unit'=>'%', 'rt'=>'quantitativeField'],
    136 => ['limits'=>'0.35 - 0.50', 'min'=>0.35, 'max'=>0.50, 'unit'=>'%', 'rt'=>'rangeField'],
    153 => ['limits'=>'0.6', 'min'=>NULL, 'max'=>0.6, 'unit'=>'mg/kg', 'rt'=>'quantitativeField'],
    207 => ['limits'=>'10', 'min'=>10, 'max'=>NULL, 'unit'=>'%', 'rt'=>'quantitativeField'],
    265 => ['limits'=>'0.2', 'min'=>NULL, 'max'=>0.2, 'unit'=>'ppm', 'rt'=>'quantitativeField'],
    266 => ['limits'=>'0.4', 'min'=>NULL, 'max'=>0.4, 'unit'=>'ppm', 'rt'=>'quantitativeField'],
    334 => ['limits'=>'4.0', 'min'=>4.0, 'max'=>NULL, 'unit'=>'%', 'rt'=>'quantitativeField'],
    390 => ['limits'=>'20', 'min'=>NULL, 'max'=>20, 'unit'=>'cfu/g', 'rt'=>'quantitativeField'],
    403 => ['limits'=>'0.007', 'min'=>0.007, 'max'=>NULL, 'unit'=>'mg/kg', 'rt'=>'quantitativeField'],
];

function inferResultType($limits) {
    if (empty($limits)) return NULL;
    $l = trim($limits);
    $qualitative = ['absent','not detected','not detectable','negative','nil','not objectionable','odourless','not detected'];
    if (in_array(strtolower($l), $qualitative)) return 'qualitativeField';
    if (preg_match('/^\-?[\d.]+\s*(to|-)\s*\-?[\d.]+$/i', $l)) return 'rangeField';
    if (preg_match('/^[\d.]+$/i', $l)) {
        if (floatval($l) == 0) return 'qualitativeField';
        return 'quantitativeField';
    }
    if (preg_match('/^[<>≤≥]?\s*[\d.]+$/i', $l)) return 'quantitativeField';
    if (preg_match('/^10\^[\d]+$/i', $l)) return 'quantitativeField';
    return NULL;
}

function fixUnit($unit) {
    if (empty($unit)) return $unit;
    $unit = str_replace('?', 'µ', $unit);
    $unit = str_replace('¡', '°', $unit);
    return $unit;
}

function fixLimits($limits) {
    if (empty($limits)) return $limits;
    $limits = preg_replace('/(\d)\.\s+(\d)/', '$1.$2', $limits);
    return $limits;
}

function formatVal($v) {
    if ($v === NULL) return 'NULL';
    if (is_string($v)) return "'" . addslashes($v) . "'";
    return (string)$v;
}

function formatMin($v) {
    if ($v === NULL) return 'MinLimit=MinLimit';
    return 'MinLimit=' . $v;
}

function formatMax($v) {
    if ($v === NULL) return 'MaxLimit=MaxLimit';
    return 'MaxLimit=' . $v;
}

function formatUnit($v) {
    if ($v === NULL) return 'UnitOfMeasure=UnitOfMeasure';
    return "UnitOfMeasure='" . addslashes($v) . "'";
}

function formatLimits($v) {
    if ($v === NULL) return 'Limits=Limits';
    return "Limits='" . addslashes($v) . "'";
}

function formatRT($v) {
    if ($v === NULL) return 'ResultType=ResultType';
    return "ResultType='" . $v . "'";
}

$output = [];
$fixed = 0;

foreach ($lines as $line) {
    if (!preg_match('/^UPDATE baseparameters SET (.+) WHERE ParameterID=(\d+);(.*)$/', $line, $m)) {
        $output[] = $line;
        continue;
    }
    $setClause = $m[1];
    $pid = intval($m[2]);
    $comment = $m[3];

    preg_match_all("/(\w+)\s*=\s*(?:'([^']*)'|(\S+))/", $setClause, $fields, PREG_SET_ORDER);
    $data = [];
    foreach ($fields as $f) {
        $name = $f[1];
        $val = $f[2] !== '' ? $f[2] : $f[3];
        $data[$name] = $val;
    }

    $limits = $data['Limits'] ?? NULL;
    $min = isset($data['MinLimit']) && $data['MinLimit'] !== 'MinLimit' ? floatval($data['MinLimit']) : NULL;
    $max = isset($data['MaxLimit']) && $data['MaxLimit'] !== 'MaxLimit' ? floatval($data['MaxLimit']) : NULL;
    $unit = $data['UnitOfMeasure'] ?? NULL;
    if ($unit === 'NULL' || $unit === 'UnitOfMeasure') $unit = NULL;

    $limits = fixLimits($limits);
    $unit = fixUnit($unit);

    if (isset($pidOverrides[$pid])) {
        $ov = $pidOverrides[$pid];
        if ($ov['limits'] !== NULL || array_key_exists('limits', $ov)) $limits = $ov['limits'];
        if (array_key_exists('min', $ov)) $min = $ov['min'];
        if (array_key_exists('max', $ov)) $max = $ov['max'];
        if (array_key_exists('unit', $ov)) $unit = $ov['unit'];
        $rt = $ov['rt'];
    } else {
        $rt = inferResultType($limits);
    }

    $parts = [
        formatLimits($limits),
        formatMin($min),
        formatMax($max),
        formatUnit($unit),
        formatRT($rt),
        'UpdatedAt=NOW()'
    ];

    $newLine = "UPDATE baseparameters SET " . implode(', ', $parts) . " WHERE ParameterID={$pid};{$comment}";
    $output[] = $newLine;
    $fixed++;
}

file_put_contents($outputFile, implode(PHP_EOL, $output) . PHP_EOL);
echo "Fixed {$fixed} UPDATE statements" . PHP_EOL;
echo "Output: {$outputFile}" . PHP_EOL;
