<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../db_connection.php';

$options = array();
$sql = "SELECT OptionValue, OptionLabel
        FROM qualitative_result_options
        WHERE IsActive = 1
        ORDER BY DisplayOrder ASC, OptionLabel ASC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Unable to load qualitative result options. Confirm the qualitative_result_options migration has been applied.'
    ));
    exit;
}

while ($row = $result->fetch_assoc()) {
    $options[] = array(
        'value' => $row['OptionValue'],
        'label' => $row['OptionLabel']
    );
}

echo json_encode(array('success' => true, 'options' => $options));
$conn->close();
?>