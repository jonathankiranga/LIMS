<?php
// upload.php

// Ensure the uploads directory exists
$uploadDir = 'uploads/';

// Check if the folder exists, otherwise create it
$folder = isset($_POST['folder']) ? $_POST['folder'] : '';
$folderPath = $uploadDir . $folder . '/';

if (!is_dir($folderPath)) {
    mkdir($folderPath, 0777, true);
}

// Handle the file upload
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $targetFile = $folderPath . basename($file['name']);

    // Check for upload errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            echo "File uploaded successfully!";
        } else {
            echo "Error saving the file.";
        }
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "No file uploaded.";
}
?>
