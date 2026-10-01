<?php
// Database connection
require '../db_connection.php'; // Ensure this connects to your PDO instance and sets $pdo
// Set the character set to UTF-8
$conn->set_charset("utf8mb4");

if (isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']); // Escape user input to prevent SQL injection
    // Fetch paginated records
    $stmt = $conn->prepare("SELECT * FROM TestStandards WHERE StandardName LIKE CONCAT('%', ?, '%')  LIMIT 10");
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    echo json_encode(['message' => 'Query parameter is required']);
}

// Close the connection
$conn->close();


?>


