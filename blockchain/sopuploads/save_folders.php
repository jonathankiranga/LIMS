<?php
$config = include('../include/config.php'); // Load the config file
// Database connection
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new PDO("mysql:host=$db_host;dbname=$db_name",$db_username,$db_password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$targetDir = "sopuploads/";
$targetFile = $targetDir . basename($_FILES["pdfFile"]["name"]);
$uploadOk = 1;
$fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

// Check if file is a pdf
if($fileType != "pdf") {
    echo "Sorry, only PDF files are allowed.";
    $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
} else {
    if (move_uploaded_file($_FILES["pdfFile"]["tmp_name"], $targetFile)) {
        // Save the file reference to the database
        $folderId = $_POST['folderId'];
        $customName=$_POST['Title'];
        $stmt = $conn->prepare("INSERT INTO files (folder_id, file_name, file_path) VALUES (:folder_id, :file_name, :file_path)");
        $stmt->bindParam(':folder_id', $folderId);
        $stmt->bindParam(':file_name', $customName);
        $stmt->bindParam(':file_path', $targetFile);
        $stmt->execute();
        echo "The file has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
?>