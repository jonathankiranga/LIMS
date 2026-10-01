<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Set number of records per page
function getstandardname($StandardID) {
    global $conn;

    $query = "SELECT StandardName FROM TestStandards WHERE StandardID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $StandardID);
    $stmt->execute();
    $result = $stmt->get_result();

    $description = null;
    if ($row = $result->fetch_assoc()) {
        $description = $row['StandardName'];
    }

    $stmt->close();
    return $description;
}

function getmatrixname($ParameterID) {
    global $conn;

    $query = "SELECT ParameterName FROM parametermatrix WHERE ParameterID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $ParameterID);
    $stmt->execute();
    $result = $stmt->get_result();

    $description = null;
    if ($row = $result->fetch_assoc()) {
        $description = $row['ParameterName'];
    }

    $stmt->close();
    return $description;
}


function get_test_results($testID) {
    global $conn;

    $query = "SELECT tr.*,tp.ParameterName "
            . " FROM `test_results` tr "
            . " join testparameters tp on tp.ParameterID=tr.ParameterID and tp.StandardID=tr.StandardID  "
            . " WHERE `TestID` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i",$testID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $test_results = [];
    while ($row = $result->fetch_assoc()) {
       $test_results[] = $row;
    }

    $stmt->close();

    return $test_results;
}

 
   
// Ensure `query` is sanitized and defaults to an empty string
$searchtext = isset($_GET['documentno']) ? trim($_GET['documentno']) : '';
$query = "SELECT `HeaderID`,
    `Date`,
    `DocumentNo`,
    `CustomerName`,
    `CustomerID`,
    `SampledBy`,
    `SamplingMethod`,
    `SamplingDate`,
    `OrderNo`,
    `ScopeOfWork`,
    `User_name`
FROM `sample_header` where `HeaderID`=?" ;
$stmt = $conn->prepare($query);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]));
}
$stmt->bind_param("i",$searchtext);
$stmt->execute();
if ($stmt->error) {
    die(json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]));
}
// Fetch the results
$result = $stmt->get_result();
$header = [];
while ($row = $result->fetch_assoc()) {
   $header[] = $row;
}

$stmt->close();



$querry="SELECT 
        `TestID`,
        `HeaderID`,
        `SampleID`,
        `StandardID`,
        `SampleFileKey`,
        `SKU` as standard_kit_units,
        `BatchNo` as product_batch_no,
        `BatchSize` as batch_size,
        `ManufactureDate` as date_of_manufacture ,
        `ExpDate` as date_of_expiry,
        `chilled_date_of_expiry`,
        `frozen_date_of_expiry`,
        `ExternalSample` as sample_source ,
        `sample_name`,
        `sample_method`,
        `condition_of_sample`,
        `User_name`,
        `BaseID`
FROM `sample_tests`  where  `HeaderID`= ? " ;
$stmt = $conn->prepare($querry);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]));
}
$stmt->bind_param("i",$searchtext);
$stmt->execute();
if ($stmt->error) {
    die(json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]));
}
// Fetch the results
$result = $stmt->get_result();
$samples = [];
$sort = [];
$n=1;
while ($row = $result->fetch_assoc()) {
    $id =(int) $row['HeaderID'];
    $TestID =(int) $row['TestID'];
    
    $sort[$id] = $row;
    $sort[$id]['standardname'] = getstandardname(trim($row['StandardID']));
    $sort[$id]['matrixname'] = getmatrixname(trim($row['BaseID']));
    $sort[$id][$TestID]['parameters'] = get_test_results(trim($row['TestID']));
    $sort[$id]['samples'] = $n;
    $n++;
}

foreach ($sort as $value) {
    $samples[] = $value;
}

$stmt->close();

$results['status']= 'success';
$results['data'] = array('header'=>$header,'samples'=>$samples);
// Send the response
header('Content-Type: application/json');
echo json_encode($results);
 