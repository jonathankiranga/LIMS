<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance

// Ensure the database connection is successful
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Validate and sanitize input
    $company_name  = $_POST['company_name'] ?? null;
    $address1      = $_POST['address1'] ?? null;
    $address2      = $_POST['address2'] ?? null;
    $address3      = $_POST['address3'] ?? null;
    $address4      = $_POST['address4'] ?? null;
    $telephone     = $_POST['telephone'] ?? null;
    $email         = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $userid1       = $_POST['userid1'] ?? null;
    $userid2       = $_POST['userid2'] ?? null;
    $authorisation = $_POST['authorisation'] ?? null;
    $companyId     = $_POST['company_id'] ?? null; // primary key of company_master

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit();
    }

    // Register or update using prepared statements to prevent SQL injection
    if ($companyId) {
        // Update existing company
        $sql = "UPDATE company_master 
                SET 
                    company_name = ?, 
                    address = ?, 
                    address1 = ?, 
                    address2 = ?, 
                    address3 = ?, 
                    telephone = ?, 
                    email = ?, 
                    authorisation = ?, 
                    technician = ?, 
                    technician2 = ?
                WHERE company_id = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param(
            "ssssssssssi",
            $company_name,
            $address1,
            $address2,
            $address3,
            $address4,
            $telephone,
            $email,
            $authorisation,
            $userid1,
            $userid2,
            $companyId
        );
    } else {
        // Register a new company
        $sql = "INSERT INTO company_master (
                    company_name, address, address1, address2, address3,
                    telephone, email, authorisation, technician, technician2
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param(
            "ssssssssss",
            $company_name,
            $address1,
            $address2,
            $address3,
            $address4,
            $telephone,
            $email,
            $authorisation,
            $userid1,
            $userid2
        );
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $companyId ? 'Company data updated successfully' : 'Company registered successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    header('Content-Type: application/json');

    $sql = "SELECT * FROM company_master ORDER BY company_id DESC LIMIT 1";
    $result = $conn->query($sql);
    $companies = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $companies[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $companies]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found']);
    }

    $conn->close();
}
?>
