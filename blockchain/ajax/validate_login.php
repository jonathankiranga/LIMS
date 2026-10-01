<?php
  // Get the request payload (username and password)
$input = json_decode(file_get_contents('php://input'), true);

require '../db_connection.php'; // Ensure this connects to your database
require '../functions/functions.php'; // Include PHPMailer via Composer

$username = trim($input['username']) ?? '';
$password = trim($input['password']) ?? '';

$query = "SELECT confvalue FROM config WHERE confname = 'expire_time'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $expireTime = $row['confvalue']; // e.g., "20*60"
} else {
    die("Error: 'expire_time' not found in the config table.");
}

// Evaluate the `expire_time` (e.g., '20*60' -> 1200 seconds)
$expireInSeconds = eval("return $expireTime;"); // Co
// Function to decrypt the private key using the password
try {
  
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
        exit;
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT "
            . "`password_hash`,"
            . "`role`,"
            . "`user_id`,"
            . "`full_name`,"
            . "`department`,"
            . "`email`,"
            . "`telephone`,"
            . "`status`"
            . "FROM users WHERE username = ? ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed.']);
        exit;
    }

    // Bind parameters and execute the statement
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid username.']);
        exit;
    }
  // Fetch the user data
     $user = $result->fetch_assoc();
    // Decrypt the private key using the password
    
     if($user['status']!=='active'){
         echo json_encode(['success' => false, 'message' => 'This account is '.$user['status']]);
         exit;
     }
     
     $tablehashpassword = $user['password_hash'];
     if(password_verify($password,$tablehashpassword)) { // Use !== for strict comparison
                echo json_encode([
                'success'    => true,
                'message'    => 'welcome '. $user['full_name'],
                'full_name'  => $user['full_name'],
                'department' => $user['department'],
                'username'   => $username,
                'role'       => $user['role'], // Include the role in the response
                'user_id'    => $user['user_id'] ,
                'telephone'  => $user['telephone'], 
                'email'      => $user['email'] 
            ]);
     }else{
            echo json_encode(['success' => false, 'message' => 'invalid password.']);
             exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred during login.', 'error' => $e->getMessage()]);
} finally {
    // Close the database connection
    $conn->close();
}
?>
