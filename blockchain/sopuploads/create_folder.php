<?php
$config = include('../include/config.php'); // Load the config file
// Database connection
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new PDO("mysql:host=$db_host;dbname=$db_name",$db_username,$db_password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Get the folder name and parent ID from POST request
$folderName = $_POST['name'];
$parentId = $_POST['parent_id'] ?? NULL;
if($parentId){
    // Insert the new folder into the database
    $stmt = $conn->prepare("INSERT INTO folders (name, parent_id) VALUES (:name, :parent_id)");
    $stmt->bindParam(':name', $folderName);
    $stmt->bindParam(':parent_id', $parentId);
}else{
    // Insert the new folder into the database
    $stmt = $conn->prepare("INSERT INTO folders (name) VALUES (:name)");
    $stmt->bindParam(':name', $folderName);
}

$stmt->execute();
?>