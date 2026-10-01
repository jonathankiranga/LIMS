<?php
require 'config.php';
$db = new mysqli($host, $DBUser, $DBPassword, $DefaultDatabase);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Add TAT column to SalesLine
$sql = "ALTER TABLE PriceList ADD COLUMN TAT INT DEFAULT NULL COMMENT 'Turn Around Time in days'";

if ($db->query($sql)) {
    echo "Added TAT column to SalesLine successfully!";
} else {
    echo "Error: " . $db->error;
}

$db->close();
?>