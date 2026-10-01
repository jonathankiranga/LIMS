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
// Get search term
$searchTerm = isset($_GET['searchTerm']) ? $mysqli->real_escape_string($_GET['searchTerm']) : '';
$sql = "SELECT 
            SampleID
        FROM 
            sample_tests 
        WHERE 
            HeaderID ='$searchTerm' ";
$result = $mysqli->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $sampleid = $row['SampleID'];
} else {
    $sampleid = 'No result found'; // Handle cases with no matching rows
}

// autoloader when using Composer
require ('../vendor/autoload.php');

$barcode = new \Com\Tecnick\Barcode\Barcode();
$bobj = $barcode->getBarcodeObj(
    'QRCODE,H',
    $sampleid,
    -4,
    -4,
    'black',
    array(-2, -2, -2, -2)
)->setBackgroundColor('#f0f0f0');

header('Content-Type: image/png');
echo $bobj->getPngData(); // Output barcode image
?>
