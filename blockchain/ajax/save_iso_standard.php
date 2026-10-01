<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

// Enable error reporting for debugging
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if (isset($_POST['standard_method'])) {
    $name = trim($_POST['standard_method']);
   // Check if already exists
    $stmt = $conn->prepare("SELECT `MethodID` FROM `standard_methods`  WHERE `standard_method` = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();

    if ($stmt->num_rows == 0) {
        $stmt->close();
        
        $stmt3 = $conn->prepare("INSERT INTO `standard_methods` (`standard_method`) VALUES (?)");
        $stmt3->bind_param("s", $name);
        $stmt3->execute();
        $stmt3->close();
    }
   

    $stmt = $conn->prepare("SELECT MethodID,standard_method FROM standard_methods WHERE standard_method = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Row exists
        echo json_encode([
            "id" => $row['MethodID'],
            "standard_method" => $row['standard_method'],
            "success" => true
        ]);
    } else {
         // Row exists
        echo json_encode([
            "id" => "",
            "standard_method" => 'name not set',
            "success" => false
        ]);
    }
  $stmt->close();
 
}else {
        // Row exists
        echo json_encode([
            "id" => "",
            "standard_method" => 'name not set',
            "success" => false
        ]);
    }
?>