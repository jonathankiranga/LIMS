<?php

$config = include('../include/config.php'); // Load the config file
// Database connection
$secretKey = $config['SECRET_KEY'];  // Access the SECRET_KEY from config
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Optional: Retrieve filter parameters (if any)
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$eventFilter = isset($_GET['event_triggered']) ? $_GET['event_triggered'] : '';

// Build the SQL query to fetch the logs
$query = "SELECT el.id, el.event_id, e.name AS event_name, el.status, el.error_message, el.triggered_at
          FROM event_logs el
          JOIN events e ON el.event_id = e.id";

// Apply filters if provided
if ($statusFilter || $eventFilter) {
    $query .= " WHERE";
    $filters = [];
    if ($statusFilter) {
        $filters[] = "el.status = '$statusFilter'";
    }
    if ($eventFilter) {
        $filters[] = "e.name LIKE '%$eventFilter%'";
    }
    $query .= " " . implode(" AND ", $filters);
}

$query .= " ORDER BY el.triggered_at DESC";  // Order logs by most recent

// Execute the query
$result = $conn->query($query);

// Prepare the response
$response = [];

if ($result->num_rows > 0) {
    // Fetch each log entry and add it to the response array
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'event_id' => $row['event_id'],
            'event_name' => $row['event_name'],
            'status' => $row['status'],
            'error_message' => $row['error_message'] ? $row['error_message'] : 'N/A',
            'triggered_at' => $row['triggered_at'],
        ];
    }
} else {
    $response = ['success' => false, 'message' => 'No event logs found.'];
}

// Close the connection
$conn->close();

// Return the response as JSON
echo json_encode($response);
?>
