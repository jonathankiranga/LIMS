<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = include('include/config.php');

$conn = new mysqli($config['DB_HOST'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected to: {$config['DB_NAME']}\n\n";

echo "=== Column Check ===\n";
$result = $conn->query('DESCRIBE baseparameters');
while ($row = $result->fetch_assoc()) {
    if (in_array($row['Field'], ['NeutralityID', 'TdsID', 'ResultType'])) {
        echo "baseparameters.{$row['Field']}: {$row['Type']}\n";
    }
}

$result = $conn->query('DESCRIBE testparameters');
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'ResultType') {
        echo "testparameters.{$row['Field']}: {$row['Type']}\n";
    }
}

echo "\n=== Duplicate Check ===\n";
$sql = "SELECT NeutralityID, COUNT(*) as cnt FROM baseparameters WHERE NeutralityID > 0 GROUP BY NeutralityID HAVING cnt > 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "WARNING: Duplicate NeutralityID found:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  NeutralityID={$row['NeutralityID']} used {$row['cnt']} times\n";
    }
} else {
    echo "OK: No duplicate NeutralityID\n";
}

$sql = "SELECT TdsID, COUNT(*) as cnt FROM baseparameters WHERE TdsID > 0 GROUP BY TdsID HAVING cnt > 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "WARNING: Duplicate TdsID found:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  TdsID={$row['TdsID']} used {$row['cnt']} times\n";
    }
} else {
    echo "OK: No duplicate TdsID\n";
}

echo "\n=== Parameters with NeutralityID/TdsID ===\n";
$sql = "SELECT ParameterID, ParameterName, NeutralityID, TdsID, ResultType FROM baseparameters WHERE NeutralityID > 0 OR TdsID > 0 LIMIT 20";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "{$row['ParameterID']} | {$row['ParameterName']} | N:{$row['NeutralityID']} | T:{$row['TdsID']} | RT:" . ($row['ResultType'] ?: 'NULL') . "\n";
    }
} else {
    echo "No parameters with NeutralityID or TdsID set\n";
}

echo "\n=== Sample test_results with linked testparameters ===\n";
$sql = "SELECT tr.SampleID, tp.ParameterName, bp.ParameterName as BaseName, bp.NeutralityID, bp.TdsID, tr.MRL_Result
FROM test_results tr
JOIN testparameters tp ON tr.ParameterID = tp.ParameterID
LEFT JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
WHERE bp.NeutralityID > 0 OR bp.TdsID > 0
LIMIT 5";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Sample: {$row['SampleID']} | TestParam: {$row['ParameterName']} | Base: {$row['BaseName']} | N:{$row['NeutralityID']} | T:{$row['TdsID']} | Value: {$row['MRL_Result']}\n";
    }
} else {
    echo "No test results with NeutralityID/TdsID linked\n";
}

$conn->close();
echo "\nDone.";