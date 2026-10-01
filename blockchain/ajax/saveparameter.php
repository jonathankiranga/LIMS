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
    $ParameterID   = isset($_POST['ParameterID']) ? intval($_POST['ParameterID']) : null;
    $name          = $conn->real_escape_string($_POST['ParameterName']);
    $StandardID    = $conn->real_escape_string($_POST['StandardID']);
    $UnitOfMeasure = $conn->real_escape_string($_POST['UnitOfMeasure']);
    $MinLimit      = $conn->real_escape_string($_POST['MinLimit']); // Corrected field name
    $MaxLimit      = $conn->real_escape_string($_POST['MaxLimit']); // Corrected field name
    $Limits        = $conn->real_escape_string($_POST['Limits']); // Corrected field name
    $Method        = $conn->real_escape_string($_POST['Method']); // Corrected field name
    $matrixID      = $conn->real_escape_string($_POST['GlobalParameterID']); // Corrected field name
    $Category      = $conn->real_escape_string($_POST['Category']); // Corrected field name
    $MRL           = $conn->real_escape_string($_POST['MRL']); // Corrected field name
    $MRLUnit       = $conn->real_escape_string($_POST['MRLUnit']); // Corrected field name
    $updatedAt     = date('Y-m-d H:i:s');

    // Validate required fields
    if (empty($name)) {
        $response['message'] = 'Name is required.';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields (only CREATE needs a base parameter; UPDATE preserves the existing BaseID)
    if (empty($ParameterID) && empty($matrixID)) {
        $response['message'] = 'Select a parameter from the list of parameters.';
        echo json_encode($response);
        exit;
    }
    
    // Validate matrixID exists in baseparameters when one is supplied (foreign key integrity; never writes baseparameters)
    if ($matrixID !== '' && $matrixID !== null) {
        $checkBase = $conn->prepare("SELECT ParameterID FROM baseparameters WHERE ParameterID = ?");
        $checkBase->bind_param("i", $matrixID);
        $checkBase->execute();
        $baseResult = $checkBase->get_result();
        
        if ($baseResult->num_rows === 0) {
            $response['message'] = 'Invalid BaseID: ' . $matrixID . ' does not exist in baseparameters table.';
            echo json_encode($response);
            exit;
        }
        $checkBase->close();
    }

    try {
        // Determine whether to CREATE or UPDATE
        if ($ParameterID) {
            // UPDATE operation
            $query = "UPDATE `testparameters`
                        SET 
                        `ParameterName` = ?,
                        `Limits` = ?,
                        `MinLimit` = ?,
                        `MaxLimit` = ?,
                        `Method` = ?,
                        `BaseID` = COALESCE(NULLIF(?, ''), BaseID),
                        `Category` = ?,
                        `MRL` = ?,
                        `MRLUnit` = ?,
                        `UpdatedAt` = ?,
                        `UnitOfMeasure` = ?
                        WHERE `ParameterID` = ? and  StandardID = ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ssddsdsdsssii", $name , $Limits,$MinLimit, $MaxLimit,$Method,$matrixID,
                        $Category,$MRL,$MRLUnit, $updatedAt, $UnitOfMeasure, $ParameterID,$StandardID);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Test Parameters updated successfully.';
                } else {
                    $response['message'] = $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'Database error: ' . $conn->error; // Provide error for debugging
            }
        } else {
            // CREATE operation
            $query = "INSERT INTO TestParameters 
                    (ParameterName,
                    StandardID,
                    MinLimit,
                    MaxLimit, 
                    Limits,
                    UnitOfMeasure, 
                    Method, 
                    BaseID, 
                    Category, 
                    MRL, 
                    MRLUnit,
                    `CreatedAt`)
                    VALUES (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?,?)";
            $createdAt = date('Y-m-d H:i:s');
            $stmt = $conn->prepare($query);
                       
            if ($stmt) {
                $stmt->bind_param("siddsssdsdss", $name,$StandardID, $MinLimit, $MaxLimit, $Limits, $UnitOfMeasure
                        ,$Method,$matrixID,$Category,$MRL,$MRLUnit, $createdAt);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Test Parameters created successfully.';
                } else {
                    $response['message'] = 'Failed to create Test Parameters.';
                }
                $stmt->close();
            } else {
                $response['message'] = 'Database error: ' . $conn->error; // Provide error for debugging
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }

    // Return JSON response
    echo json_encode($response);
}
?>
