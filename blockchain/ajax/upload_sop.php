<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

// Check if the form was submitted and if required fields are set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files']) && isset($_POST['title']) && isset($_POST['description']) && isset($_POST['version'])) {
    $uploadDirectory ='../sopuploads/';

    $files = $_FILES['files'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $version = $_POST['version'];
    $user_id = $_POST['user_id'];
  // Define allowed file types and size limit
    $allowedExtensions = ['pdf'];
    $maxFileSize = 10 * 1024 * 1024; // 10 MB in bytes
   // Initialize response array
    $response = [];

    // Loop through each uploaded file
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = basename($files['name'][$i]);
        $fileTmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileError = $files['error'][$i];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
      // Check file size
        if ($fileSize > $maxFileSize) {
            $response[] = [
                'message' => "File '$fileName' exceeds the maximum size limit of 10 MB."
            ];
            continue;
        }

        // Check if the file has a valid extension
        if (!in_array($fileExt, $allowedExtensions)) {
            $response[] = [
                'message' => "Invalid file type for '$fileName'. Only PDF files are allowed."
            ];
            continue;
        }

        if ($fileError === 0) {
            // Generate a unique file name to prevent overwriting
            $uniqueFileName = uniqid('', true) . '.' . $fileExt;
            $destination = $uploadDirectory . $uniqueFileName;

            // Move the uploaded file to the desired directory
            if (move_uploaded_file($fileTmpName, $destination)) {
                // Insert the SOP record into the SOPs table
                $stmt = $conn->prepare("INSERT INTO SOPs (title, description, document_url, version_number) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    $response[] = [
                       'message' => "Database error: " . $conn->error
                    ];
                    continue;
                }

                $stmt->bind_param("ssss", $title, $description, $destination, $version);

                if ($stmt->execute()) {
                    $sop_id = $stmt->insert_id; // Get the inserted SOP ID

                    // Log the SOP upload in the SOP_Access_Log table (for audit purposes)
                    $action = 'uploaded';
                    $logStmt = $conn->prepare("INSERT INTO SOP_Access_Log (sop_id, user_id, action) VALUES (?, ?, ?)");
                    $logStmt->bind_param("iis", $sop_id, $user_id, $action);
                    $logStmt->execute();

                    $response[] = [
                        'success' => true,
                        'message' => "File '$fileName' uploaded and saved to the database successfully.",
                        'file' => $uniqueFileName
                    ];
                } else {
                    $response[] = [
                        'message' => "Error saving SOP data for '$fileName'. " . $stmt->error
                    ];
                }

                $stmt->close();
                $logStmt->close();
            } else {
                $response[] = [
                    'message' => "Error moving the file '$fileName' to the upload directory."
                ];
            }
        } else {
            $response[] = [
                'message' => "There was an error uploading the file '$fileName'. Please try again."
            ];
        }
    }

    // Send the response back as JSON
    echo json_encode($response);
    exit;
} else {
    echo json_encode([
        'message' => 'Invalid request.'
    ]);
    exit;
}
?>
