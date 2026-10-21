<?php

require_once '../db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Something went wrong.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $new_email = isset($_POST['newemail']) ? trim($_POST['newemail']) : '';
    $new_telephone = isset($_POST['newtelephone']) ? trim($_POST['newtelephone']) : '';

    if ($user_id <= 0) {
        $response['message'] = 'Invalid user.';
    } elseif (!isset($_FILES['signature_image']) || $_FILES['signature_image']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Please select a transparent PNG signature image.';
    } else {
        $fileTmpPath = $_FILES['signature_image']['tmp_name'];
        $fileName = $_FILES['signature_image']['name'];
        $fileSize = (int) $_FILES['signature_image']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'png' || $fileSize <= 0 || $fileSize > 5242880) {
            $response['message'] = 'Only transparent PNG files up to 5 MB are allowed.';
        } elseif (!function_exists('imagecreatefrompng')) {
            $response['message'] = 'PNG validation is unavailable on the server because PHP GD is not enabled. No file was saved.';
        } else {
            $imageInfo = @getimagesize($fileTmpPath);
            if ($imageInfo === false || $imageInfo[2] !== IMAGETYPE_PNG || $imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
                $response['message'] = 'The uploaded file is not a valid PNG image or its dimensions exceed 5000 x 5000 pixels.';
            } else {
                $image = @imagecreatefrompng($fileTmpPath);
                $hasTransparency = false;

                if ($image !== false) {
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    $width = imagesx($image);
                    $height = imagesy($image);

                    for ($y = 0; $y < $height && !$hasTransparency; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $pixel = imagecolorat($image, $x, $y);
                            if (imageistruecolor($image)) {
                                $alpha = ($pixel >> 24) & 0x7F;
                                if ($alpha > 0) {
                                    $hasTransparency = true;
                                    break;
                                }
                            } else {
                                $color = imagecolorsforindex($image, $pixel);
                                if (isset($color['alpha']) && $color['alpha'] > 0) {
                                    $hasTransparency = true;
                                    break;
                                }
                            }
                        }
                    }
                    imagedestroy($image);
                }

                if (!$hasTransparency) {
                    $response['message'] = 'The PNG must contain actual transparent pixels. Please remove the background and upload the transparent PNG.';
                } else {
                    $uploadDirectory = '../images/';
                    $newFileName = 'sig' . $user_id . '.png';
                    $uploadPath = $uploadDirectory . $newFileName;

                    if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
                        $response['message'] = 'The signature image directory is missing or not writable.';
                    } elseif (move_uploaded_file($fileTmpPath, $uploadPath)) {
                        $sql = "UPDATE users SET full_name = ?, email = ?, telephone = ?, signature_path = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);

                        if ($stmt) {
                            $stmt->bind_param('ssssi', $full_name, $new_email, $new_telephone, $newFileName, $user_id);

                            if ($stmt->execute()) {
                                $response['success'] = true;
                                $response['message'] = 'User details and transparent PNG signature updated successfully.';
                            } else {
                                $response['message'] = 'Error updating the database: ' . $stmt->error;
                                if (file_exists($uploadPath)) {
                                    unlink($uploadPath);
                                }
                            }
                            $stmt->close();
                        } else {
                            $response['message'] = 'Database error: Could not prepare statement.';
                            if (file_exists($uploadPath)) {
                                unlink($uploadPath);
                            }
                        }
                    } else {
                        $response['message'] = 'Error saving the uploaded signature image.';
                    }
                }
            }
        }
    }

    echo json_encode($response);
}
?>
