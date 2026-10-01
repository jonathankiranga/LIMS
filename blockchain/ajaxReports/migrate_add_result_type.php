<?php
require 'db_connection.php';

$sql = "ALTER TABLE testparameters ADD COLUMN ResultType ENUM('quantitativeField','qualitativeField','rangeField') DEFAULT NULL AFTER UnitOfMeasure";

if ($conn->query($sql)) {
    echo "Added ResultType column to testparameters successfully!";
} else {
    echo "Error: " . $conn->error;
}
?>