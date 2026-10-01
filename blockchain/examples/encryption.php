<?php

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

// Helper function to encrypt the private key
function encryptPrivateKey($privateKey, $password) {
    $method = 'aes-256-cbc';
   // Derive a 32-byte key from the password
    $key = substr(hash('sha256', $password), 0, 32);
   // Generate a random 16-byte IV
    $iv = openssl_random_pseudo_bytes(16);
   // Encrypt the private key
    $encrypted = openssl_encrypt($privateKey, $method, $key, 0, $iv);
// Combine IV and encrypted data, and encode as Base64
    return base64_encode($iv . $encrypted);
}

/*
$password = 'my_secure_password';
$data = 'This is a secret message';

// Encrypt
$encryptedData = encryptPrivateKey($data, $password);
echo "Encrypted Data: $encryptedData" . PHP_EOL;
echo "<br/>";
// Decrypt
$decryptedData = decryptPrivateKey($encryptedData, $password);
echo "Decrypted Data: $decryptedData" . PHP_EOL;

*/

$hashed_password = '$2y$10$bY8uIsZbD4KLzzrjooZXtORI5bq4tp.msNCtk2d.8WU6f8W6L7Qxm';
$plain_password = 'erp';

if (password_verify($plain_password, $hashed_password)) {
    echo 'Password is valid!';
} else {
    echo 'Invalid password.';
}

