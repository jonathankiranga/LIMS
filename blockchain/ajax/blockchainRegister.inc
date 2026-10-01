<?php


// Function to gene

// Simulate collecting registration data from the POST request
$sampleId = $_POST['sampleId'] ?? null;
$testDate = $_POST['testDate'] ?? null;
$substance = $_POST['substance'] ?? null;
$concentration = $_POST['concentration'] ?? null;
$mrl = $_POST['mrl'] ?? null;
$temperature = $_POST['temperature'] ?? null;
$humidity = $_POST['humidity'] ?? null;
$sampleAge = $_POST['sampleAge'] ?? null;
$batchSource = $_POST['batchSource'] ?? null;
$machineId = $_POST['machineId'] ?? null;
$testMethod = $_POST['testMethod'] ?? null;
$aboveMrl = $_POST['aboveMrl'] ?? null;

// Collect the previous block hash (for linking to previous block in the chain)
$previousBlockQuery = "SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1";
$result = $mysqli->query($previousBlockQuery);
$previousHash = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['current_hash'] : '0'; // First block

// Prepare data for blockchain ledger
$sampleData = [
    'sampleId' => $sampleId,
    'testDate' => $testDate,
    'substance' => $substance,
    'concentration' => $concentration,
    'mrl' => $mrl,
    'temperature' => $temperature,
    'humidity' => $humidity,
    'sampleAge' => $sampleAge,
    'batchSource' => $batchSource,
    'machineId' => $machineId,
    'testMethod' => $testMethod,
    'aboveMrl' => $aboveMrl
];

// Generate the current and previous block hashes
$currentHash = calculateHash($sampleData);
$digitalSignature = signData(json_encode($sampleData), $privateKey);
// Insert the sample data into the blockchain_ledger table
$stmt = $mysqli->prepare("
    INSERT INTO blockchain_ledger 
    (sample_id, test_date, substance, concentration, mrl, temperature, humidity, sample_age, batch_source, machine_id, test_method, above_mrl, previous_hash, current_hash, digital_signature) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param('ssssddddssssss', $sampleId, $testDate, $substance, $concentration, $mrl, $temperature, $humidity, $sampleAge, $batchSource, $machineId, $testMethod, $aboveMrl, $previousHash, $currentHash, $digitalSignature);

if ($stmt->execute()) {
    // Log action in the audit_log
    $userId = 'system';  // Assume system for automated insert; change as needed
    $logStmt = $mysqli->prepare("
        INSERT INTO audit_log (block_id, action_type, user_id, status) 
        VALUES (?, 'INSERT', ?, 'SUCCESS')
    ");
    $blockId = $mysqli->insert_id;  // Get the ID of the newly inserted block
    $logStmt->bind_param('is', $blockId, $userId);
    $logStmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Sample registered successfully in blockchain.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save sample registration.']);
}

// Close the prepared statements and the connection
$stmt->close();
$logStmt->close();
$mysqli->close();
?>
