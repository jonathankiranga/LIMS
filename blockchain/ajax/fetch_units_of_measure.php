<?php

require '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'data' => []
];

try {
    $sql = "SELECT UnitID, UnitOfMeasure, Description
            FROM units_of_measure
            WHERE Active = 1
            ORDER BY UnitOfMeasure ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new RuntimeException('Failed to fetch units of measure: ' . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $response['data'][] = [
            'UnitID' => (int)$row['UnitID'],
            'UnitOfMeasure' => $row['UnitOfMeasure'],
            'Description' => $row['Description']
        ];
    }

    $response['success'] = true;

} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
