<?php
// Example PHP code to fetch DAQ data from a database
// This is just a placeholder; you would query your actual database.
$daq_data = [
    ['Timestamp' => '2024-11-14 12:00:00', 'Measurement' => 1.5],
    ['Timestamp' => '2024-11-14 12:05:00', 'Measurement' => 1.8],
    ['Timestamp' => '2024-11-14 12:10:00', 'Measurement' => 2.0],
    ['Timestamp' => '2024-11-14 12:15:00', 'Measurement' => 2.2],
];

// Convert the data to JSON format for use in JavaScript
echo json_encode($daq_data);
?>
