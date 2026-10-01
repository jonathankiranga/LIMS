<?php
require '../db_connection.php';

$sql = "ALTER TABLE baseparameters ADD COLUMN ResultType ENUM('quantitativeField','qualitativeField','rangeField') DEFAULT NULL AFTER TdsID";

if ($conn->query($sql)) {
    echo "Added ResultType column to baseparameters successfully!";
} else {
    echo "Error: " . $conn->error;
}
?>