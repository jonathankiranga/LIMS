<?php
// Include database connection
require '../db_connection.php'; // mysqli connection $conn
// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [
        'success' => false,
        'message' => 'Invalid request'
    ];

    // Get data from POST request
    $StandardCloneID = $_POST['StandardCloneID'] ;
    $Code = $conn->real_escape_string($_POST['Code']);
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']); // Corrected field name
    $ApplicableRegulation = $conn->real_escape_string($_POST['applicableregulation']); // Corrected field name
    $standardmethod=$conn->real_escape_string($_POST['standardmethod']); // Corrected field name
    $updatedAt = date('Y-m-d H:i:s');

    // Validate required fields
    if (empty($StandardCloneID)) {
        $response['message'] = 'You must select a clone category.';
        echo json_encode($response);
        exit;
    }

    try {
        // Determine whether to CREATE or UPDATE
        if ($StandardCloneID>0) {
           // CREATE operation
            $query = "INSERT INTO TestStandards (StandardCode, StandardName, Description, ApplicableRegulation, CreatedAt,sm)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $createdAt = date('Y-m-d H:i:s');
            $stmt = $conn->prepare($query);
            if($stmt) {
                $stmt->bind_param("sssssi", $Code, $name, $description, $ApplicableRegulation, $createdAt,$standardmethod);
                if ($stmt->execute()) {
                    $StandardID = $conn->insert_id;
                }
                $stmt->close();
                
            } else {
                $response['message'] = 'Database error: ' . $conn->error; // Provide error for debugging
            }
            
            if($StandardID>0){
                // CREATE operation
                $query = "INSERT INTO TestParameters
                (
                    ParameterName, StandardID,MinLimit,MaxLimit,Limits,UnitOfMeasure,Method, BaseID,Category,MRL,MRLUnit,CreatedAt
                )
                SELECT  ParameterName,?,MinLimit,MaxLimit,Limits,UnitOfMeasure,Method,BaseID,Category,MRL,MRLUnit,NOW()   
                FROM TestParameters WHERE StandardID = ?;
                ";

                $stmt = $conn->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("ii", $StandardID, $StandardCloneID);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = '✅ Test standard created and parameters cloned successfully.';
                   } else {
                         $response['success'] = false;
                         $response['message'] = '❌ Test standard created, but database error during parameter clone: ' . $conn->error;
                   }
                    $stmt->close();
                } else {
                    $response['message'] = 'Database error: ' . $conn->error; // Provide error for debugging
                }
            }else{
                $response['success'] = false;
                $response['message'] = '❌ Failed to create test standard. Parameters not cloned.';
           }  
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }

    // Return JSON response
    echo json_encode($response);
}
?>
