<?php
$config = include('../include/config.php'); // Load the config file
// Database connection
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
// Example PHP code to fetch paginated data
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$itemsPerPage = 10; // Set the number of items per page

// Fetch the total number of items from your database
$totalItemsQuery = "SELECT COUNT(*) FROM sample_header"; // Replace with your actual query
$totalItemsResult = mysqli_query($conn, $totalItemsQuery);
$totalItems = mysqli_fetch_row($totalItemsResult)[0];

// Calculate the total number of pages
$totalPages = ceil($totalItems / $itemsPerPage);

// Calculate the starting index for the current page
$startIndex = ($page - 1) * $itemsPerPage;

// Fetch the data for the current page
$dataQuery = "SELECT * FROM sample_header LIMIT $startIndex, $itemsPerPage"; // Replace with your actual query
$dataResult = mysqli_query($conn, $dataQuery);

$data = [];
while ($row = mysqli_fetch_assoc($dataResult)) {
    $data[] = $row;
}

// Prepare the response
$response = [
    'dateRange' => '2025-01-01 to 2025-12-31',
    'status' => 'Completed',
    'data' => $data,
    'pageNumber' => $page,
    'totalPages' => $totalPages
];

// Send the response as JSON
echo json_encode($response);
?>
