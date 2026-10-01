<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$query = "SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1";
$result = $pdo->query($query);
$previousHash = $result->fetchColumn() ?? str_repeat('0', 64); // Use all zeros for the genesis block

 // Fetch Sample_Header data
// Collect Sample_Header data
$headerData = json_encode([
    'Date' => $date,
    'DocumentNo' => $documentNo,
    'CustomerName' => $customerName,
    'CustomerID' => $customerId,
    'SampledBy' => $sampledBy,
    'SamplingMethod' => $samplingMethod,
    'SamplingDate' => $samplingDate,
    'OrderNo' => $orderNo,
    'ScopeOfWork' => $scopeOfWork,
    'NumberOfSamples' => $numberOfSamples
]);

// Collect Sample_Tests data
$sampleTestsData = [];
for ($i = 1; $i <= $numberOfSamples; $i++) {
    $sampleTestsData[] = [
        'SampleFileKey' => $_POST["sampleFile[$i]"] ?? null,
        'SampleFee' => $_POST["sampleFEE[$i]"] ?? 0.00,
        'InternalSample' => $_POST["internalsamples[$i]"] ?? null,
        'SKU' => $_POST["SKU[$i]"] ?? null,
        'BatchNo' => $_POST["Batchno[$i]"] ?? null,
        'BatchSize' => $_POST["Batchsize[$i]"] ?? null,
        'ManufactureDate' => $_POST["MANDATE[$i]"] ?? null,
        'ExpDate' => $_POST["EXPDATE[$i]"] ?? null,
        'ExternalSample' => $_POST["externalsamples[$i]"] ?? null
    ];
}
$testsData = json_encode($sampleTestsData);



try {
    $mysqli->autocommit(false);

    // Insert Sample_Header
    $query = "INSERT INTO Sample_Header (
        `Date`, `DocumentNo`, `CustomerName`, `CustomerID`, `SampledBy`, 
        `SamplingMethod`, `SamplingDate`, `OrderNo`, `ScopeOfWork`, `NumberOfSamples`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param(
        "sssssssssi",
        $date,
        $documentNo,
        $customerName,
        $customerId,
        $sampledBy,
        $samplingMethod,
        $samplingDate,
        $orderNo,
        $scopeOfWork,
        $numberOfSamples
    );
    $stmt->execute();
    $headerID = $mysqli->insert_id;

    // Insert Sample_Tests
    $query = "INSERT INTO Sample_Tests (
        `HeaderID`, `SampleFileKey`, `SampleFee`, `InternalSample`, `SKU`, 
        `BatchNo`, `BatchSize`, `ManufactureDate`, `ExpDate`, `ExternalSample`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);

    foreach ($sampleTestsData as $test) {
        $stmt->bind_param(
            "isdsssssss",
            $headerID,
            $test['SampleFileKey'],
            $test['SampleFee'],
            $test['InternalSample'],
            $test['SKU'],
            $test['BatchNo'],
            $test['BatchSize'],
            $test['ManufactureDate'],
            $test['ExpDate'],
            $test['ExternalSample']
        );
        $stmt->execute();
    }

    // Retrieve Previous Hash
    $previousHash = str_repeat('0', 64); // Default for genesis block
    $query = "SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1";
    $result = $mysqli->query($query);

    if ($row = $result->fetch_assoc()) {
        $previousHash = $row['current_hash'];
    }

    // Calculate Current Hash
    $blockData = $headerData . $testsData . $previousHash;
    $currentHash = hash('sha256', $blockData);

    // Insert into Blockchain Ledger
    $query = "INSERT INTO blockchain_ledger (previous_hash, current_hash) VALUES (?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $previousHash, $currentHash);
    $stmt->execute();

    // Commit the transaction
    $mysqli->commit();

    echo "Transaction completed successfully!";
} catch (Exception $e) {
    $mysqli->rollback();
    echo "Transaction failed: " . $e->getMessage();
}

$mysqli->autocommit(true);