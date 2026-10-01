<?php

require '../db_connection.php';
header('Content-Type: application/json');
// Parse incoming JSON
$input = json_decode(file_get_contents("php://input"), true);
// Validate input
if ( !isset($input['matrix_id']) || !isset($input['parameter_ids']) || !is_array($input['parameter_ids'])) {
  echo json_encode(["success" => false, "message" => "Invalid input."]);
  exit;
}

$matrix_id = intval($input['matrix_id']);
$parameter_ids = $input['parameter_ids']; // array
$notes = isset($input['notes']) ? trim($input['notes']) : '';

$conn->autocommit(0);
try {
  // Optional: clear previous configuration
  $stmt = $conn->prepare("DELETE FROM standard_parameter_matrix_config WHERE MatrixID = ?");
  $stmt->bind_param("i",$matrix_id);
  $stmt->execute();
  $stmt->close();

  // Insert new parameter config
  $stmt = $conn->prepare("INSERT INTO standard_parameter_matrix_config (MatrixID, ParameterID, Notes) VALUES (?, ?, ?)");
  if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => "Prepare statement standard_parameter_matrix_config: " . $conn->error
            ]);
            exit; // Stop further processing
        }
  foreach ($parameter_ids as $param_id) {
    $param_id = intval($param_id);
    $stmt->bind_param("iis",$matrix_id, $param_id, $notes);
    if (!$stmt->execute()) {
                  echo json_encode([
                   'success' => false,
                   'message' => "Error executing statement standard_parameter_matrix_config" . $stmt->error
                  ]);
                 exit; // Stop further processing
             } 
  }
  $stmt->close();

  $conn->commit();
  echo json_encode(["success" => true, "message" => "Configuration saved successfully."]);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(["success" => false, "message" => "Error saving configuration: " . $e->getMessage()]);
}
    $conn->autocommit(1);
    $conn->close();
?>
