<?php
// fetch_latest_env_factors.php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance


header('Content-Type: application/json');
try {
    $query = "SELECT temperature, humidity, notes, recorded_at "
            . "FROM environmental_parameters "
            . "ORDER BY recorded_at DESC LIMIT 1";
    $result = $conn->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => [
                'temperature' => $row['temperature'],
                'humidity' => $row['humidity'],
                'notes' => $row['notes'],
                'date' => $row['recorded_at']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'temperature' => 0,
                'humidity' => 0,
                'notes' => '',
                'date' => ''
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching data: ' . $e->getMessage()
    ]);
} finally {
    $conn->close(); // Close the database connection
}

?>
