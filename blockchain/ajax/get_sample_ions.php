<?php
require '../db_connection.php';

$sampleID = $_GET['sampleID'] ?? '';
$response = ['success' => false, 'neutralityIons' => [], 'tdsElements' => []];

if (empty($sampleID)) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$NEUTRALITY = [
    1 => "Na⁺", 2 => "K⁺", 3 => "Ca²⁺", 4 => "Mg²⁺", 5 => "NH₄⁺",
    6 => "Cl⁻", 7 => "HCO₃⁻", 8 => "NO₃⁻", 9 => "SO₄²⁻", 10 => "CO₃²⁻"
];

$TDS = [
    1 => "Hydrogen", 2 => "Oxygen", 3 => "Nitrogen", 4 => "Carbon", 5 => "Sodium",
    6 => "Potassium", 7 => "Calcium", 8 => "Magnesium", 9 => "Iron", 10 => "Copper",
    11 => "Lead", 12 => "Zinc", 13 => "Manganese", 14 => "Chlorine", 15 => "Fluorine",
    16 => "Boron", 17 => "Sulfur", 18 => "Phosphorus"
];

$neutralityIons = [];
$tdsElements = [];

foreach ($NEUTRALITY as $index => $ionName) {
    $stmt = $conn->prepare("
        SELECT tr.MRL_Result, tp.ParameterName
        FROM test_results tr
        JOIN testparameters tp ON tr.ParameterID = tp.ParameterID AND tr.StandardID = tp.StandardID
        JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
        WHERE tr.SampleID = ? AND bp.NeutralityID = ?
          AND tr.MRL_Result IS NOT NULL AND tr.MRL_Result <> ''
        LIMIT 1
    ");
    $stmt->bind_param("si", $sampleID, $index);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (is_numeric($row['MRL_Result'])) {
            $key = strtolower(str_replace(['⁺', '²⁺', '⁻', '²⁻'], ['+', '2+', '-', '2-'], $ionName));
            $neutralityIons[$key] = (float)$row['MRL_Result'];
        }
    }
    $stmt->close();
}

foreach ($TDS as $index => $elementName) {
    $stmt = $conn->prepare("
        SELECT tr.MRL_Result, tp.ParameterName
        FROM test_results tr
        JOIN testparameters tp ON tr.ParameterID = tp.ParameterID AND tr.StandardID = tp.StandardID
        JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
        WHERE tr.SampleID = ? AND bp.TdsID = ?
          AND tr.MRL_Result IS NOT NULL AND tr.MRL_Result <> ''
        LIMIT 1
    ");
    $stmt->bind_param("si", $sampleID, $index);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (is_numeric($row['MRL_Result'])) {
            $key = strtolower($elementName);
            $tdsElements[$key] = (float)$row['MRL_Result'];
        }
    }
    $stmt->close();
}

$response['success'] = true;
$response['neutralityIons'] = $neutralityIons;
$response['tdsElements'] = $tdsElements;
$response['sampleID'] = $sampleID;

header('Content-Type: application/json');
echo json_encode($response);