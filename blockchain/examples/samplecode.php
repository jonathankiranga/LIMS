<?php

$blockData = [
    'sample_id' => '001',
    'test_date' => '2024-10-01',
    'substance' => 'Pesticide',
    'concentration' => 0.5,
    'mrl' => 0.8,
    'temperature' => 25,
    'humidity' => 60,
    'sample_age' => 10,
    'batch_source' => 'Farm A',
    'machine_id' => 'Machine-01',
    'test_method' => 'Method A',
    'above_mrl' => false
];



// Usage Example
logAction($pdo, $newBlockId, 'INSERT', 'user123', 'SUCCESS');


try {
    // Retrieve and decrypt the private key
    $privateKey = getPrivateKeyForUser($pdo, $userId, $password);

    // Call the function to create a new block with the decrypted private key
    createNewBlock($pdo, $blockData, $userId, $privateKey);

    echo "New block created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
