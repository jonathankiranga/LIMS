<?php

// Include database connection
require_once '../db_connection.php';

// Initialize response
$response = ['success' => false, 'message' => 'Something went wrong.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get user ID and other form data
    $user_id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $new_email = $_POST['newemail'];
    $new_telephone = $_POST['newtelephone'];

    // Handle the uploaded signature image
    if (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] == UPLOAD_ERR_OK) {
        // Get file details
        $fileTmpPath = $_FILES['signature_image']['tmp_name'];
        $fileName = $_FILES['signature_image']['name'];
        $fileSize = $_FILES['signature_image']['size'];
        $fileType = $_FILES['signature_image']['type'];
        
        // Get the file extension and validate image type (optional)
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Move the file to the server directory (optional: use a unique name)
            $uploadDirectory = '../images/';
            $newFileName = "sig$user_id" . '.' . $fileExtension;
            $uploadPath = $uploadDirectory . $newFileName;

            if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                // Prepare SQL to update user details and image
                $sql = "UPDATE users SET full_name = ?,  email = ?, telephone = ?,signature_path = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssssi',$full_name,$new_email, $new_telephone,$newFileName, $user_id);

                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'User details and signature updated successfully.';
                    } else {
                        $response['message'] = 'Error updating the database.'.$stmt->error;
                    }
                    
                    $stmt->close();
                } else {
                    $response['message'] = 'Database error: Could not prepare statement.'.$stmt->error;
                }
            } else {
                $response['message'] = 'Error moving the uploaded file.';
            }
        } else {
            $response['message'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
        }
    } else {
        $response['message'] = 'No image uploaded or error during file upload.';
    }

    // Send the response as JSON
    echo json_encode($response);
}
?>
