<?php
// Include database connection
require '../db_connection.php'; // mysqli connection $conn

cleanparameters();
cleanparameters2();
// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [
        'success' => false,
        'message' => 'Invalid request'
    ];
    
  
    try {
            // CREATE operation
            $query = "INSERT INTO baseparameters (ParameterName, CreatedAt)
                      SELECT t.ParameterName, NOW()
                      FROM testparameters t
                      LEFT JOIN baseparameters b 
                             ON b.ParameterName = t.ParameterName
                      WHERE b.ParameterName IS NULL";

            if ($conn->query($query) === TRUE) {
                $response['success'] = true;
                $response['message'] = 'Test Parameters created successfully.';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to create Test Parameters. Error: ' . $conn->error;
            }
            
 // CREATE operation
            $query = "
                UPDATE testparameters t
                    LEFT JOIN baseparameters b
                           ON t.ParameterName = b.ParameterName
                    SET t.BaseID = b.ParameterID
                    ";

            if ($conn->query($query) === TRUE) {
                $response['success'] = true;
                $response['message'] = 'Test Parameters created successfully.';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to create Test Parameters. Error: ' . $conn->error;
            }
            
           
        
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }

    // Return JSON response
    echo json_encode($response);
}


function cleanparameters2(){
    global $conn;
    // Fetch all parameter names
$sql = "UPDATE baseparameters
SET ParameterName = TRIM(SUBSTRING_INDEX(ParameterName, ',', 1))";
$result = $conn->query($sql);

}


function cleanparameters(){
 global $conn;
 
$sql = "UPDATE testparameters
SET ParameterName = TRIM(SUBSTRING_INDEX(ParameterName, ',', 1))";
$result = $conn->query($sql);

}

?>
