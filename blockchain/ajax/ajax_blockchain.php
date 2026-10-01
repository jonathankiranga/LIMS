<?php

//decryptPrivateKey($encryptedData, $password)
// Fetch paginated blocks
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

// Get current page from AJAX request, default to page 1 if not set
$currentPage = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$itemsPerPage = 10; // Items per page

// Calculate offset
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch total number of blocks
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM blockchain_ledger");
$totalResult = $totalQuery->fetch_assoc();
$totalBlocks = $totalResult['total'];
$totalPages = ceil($totalBlocks / $itemsPerPage);

// Fetch paginated blocks
$resultsQuery = $conn->query("
    SELECT `block_id`, `timestamp`, `previous_hash`, `current_hash`, `digital_signature`, `encrypted_data`, `userid` 
    FROM blockchain_ledger 
    ORDER BY block_id 
    LIMIT $itemsPerPage OFFSET $offset
");

    $blocks = $resultsQuery->fetch_all(MYSQLI_ASSOC);
    // Initialize an array to store decrypted blocks
    $decryptedBlocks = [];

    foreach ($blocks as $block) {
        $user_id = ($block['userid']==0)?10:$block['userid'];
        $dir = "userkeys/$user_id";
        if(file_exists("$dir/private_key.pem")){
            $privateKeyPath ="$dir/private_key.pem";
            $privateKey = file_get_contents($privateKeyPath); // Read the file content
            $decryptedData=decryptPrivateKey($block['encrypted_data'],$privateKey);
                if($decryptedData !== '') {
                    $block['decrypted_data'] = $decryptedData; // Add decrypted data to the block
                } else {
                    $block['decrypted_data'] = 'Decryption failed'; // Handle decryption failure
                }
        }else{
        $block['decrypted_data'] = 'private key not found'; // Handle decryption failure
        }
        $decryptedBlocks[] = $block;
    }

    function decryptPrivateKey($encryptedData, $password) {
        $method = 'aes-256-cbc';
       // Derive the 32-byte key from the password
        $key = substr(hash('sha256', $password), 0, 32);
       // Decode the Base64-encoded encrypted data
        $data = base64_decode($encryptedData);
       // Extract the IV (first 16 bytes) and the actual encrypted data
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
       // Decrypt the data
        $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);
 
        return $decrypted;
    }
    // Return JSON response with decrypted data
    echo json_encode([
        'blocks' => $decryptedBlocks,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ]);
 


