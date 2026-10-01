<?php
include 'config.php';
// Create connection
$conn = new mysqli($host,$DBUser, $DBPassword, $DefaultDatabase);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$csvFolder = __DIR__ . '/csv/';
// Loop through all CSV files in the folder
$csvFiles = glob($csvFolder . '*.csv');
if (empty($csvFiles)) {
    die("No CSV files found in $csvFolder\n");
}
$conn->query("SET FOREIGN_KEY_CHECKS=0");
// Your INSERT / UPDATE / DELETE queries here

foreach ($csvFiles as $csvFilePath) {
    $tableName = pathinfo($csvFilePath, PATHINFO_FILENAME); // CSV filename = Table name
   // Fetch column names from the table in the exact order
    $columns = [];
    $conn->query("Delete FROM `$tableName`");
 
    $result = $conn->query("SHOW COLUMNS FROM `$tableName`");
    if (!$result) {
        echo "Skipping file: $csvFilePath - Table `$tableName` not found in database.\n";
        continue;
    }
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    $columnList = '`' . implode('`, `', $columns) . '`';

    // Open CSV file for reading
    $fileHandle = fopen($csvFilePath, 'r');
    if (!$fileHandle) {
        echo "Failed to open $csvFilePath\n";
        continue;
    }

    $insertCount = 0;

    while (($data = fgetcsv($fileHandle)) !== FALSE) {
        // Skip rows if column count doesn't match
        if (count($data) != count($columns)) {
            echo "Skipping row in `$tableName` (column mismatch): " . implode(',', $data) . "\n";
            continue;
        }

        $values = [];
        foreach ($data as $value) {
            if (trim($value) === '') {
                $values[] = 'NULL';
            } else {
                $values[] = "'" . $conn->real_escape_string(trim($value)) . "'";
            }
        }

        $valuesList = implode(', ', $values);
        $sql = "INSERT INTO `$tableName` ($columnList) VALUES ($valuesList)";

        if ($conn->query($sql) === TRUE) {
            $insertCount++;
        } else {
            echo "Error inserting into `$tableName`: " . $conn->error . "\nSQL: $sql\n";
        }
    }

    fclose($fileHandle);
    echo "Inserted $insertCount rows into `$tableName` from " . basename($csvFilePath) . "\n";
}

$conn->query("SET FOREIGN_KEY_CHECKS=1");
$conn->close();
?>
 
