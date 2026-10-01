<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
// Check if search parameters are provided
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
// Prepare the SQL query to fetch SOPs based on the search query
$query = "SELECT sop_id, title, description, document_url, version_number FROM SOPs WHERE title LIKE ? OR description LIKE ?";
$stmt = $conn->prepare($query);
$searchTerm = '%' . $searchQuery . '%';
$stmt->bind_param("ss", $searchTerm, $searchTerm);
// Execute the query
$stmt->execute();
$result = $stmt->get_result();
// Prepare the data to be returned
$sops = [];
while ($row = $result->fetch_assoc()) {
    $SampleFileKey=trim($row['document_url']);
    if(file_exists($SampleFileKey)){
        $fileName = basename($SampleFileKey);
        $row['document_url']="sopuploads/$fileName";
    }else{
        $row['document_url']='uploads/icons8-no-image-100.png';
    }
    $sops[] = $row;
}
// Close the statement
$stmt->close();
// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($sops);
?>
