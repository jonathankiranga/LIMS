<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
use Kreait\Firebase\Factory;

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Image Upload</title>
</head>
<body>
<?php 


if(isset($_GET['firebase'])){
// Initialize Firebase with service account
$factory = (new Factory)
    ->withServiceAccount(__DIR__ .'/vendor/google-services.json');
//gs://pgholdingsandroid.appspot.com
$storage = $factory->createStorage();
$bucket = $storage->getBucket('pgholdingsandroid.appspot.com');
$prefix = 'images/';
$objects = $bucket->objects([
    'prefix' => $prefix
]);

$downloadDir = __DIR__ . '/repository/';
if (!is_dir($downloadDir)) {
    mkdir($downloadDir, 0777, true);
}
// Get the objects (files) in the "images" folder
$imageFolder = 'images/';
$objects = $bucket->objects([
    'prefix' => $imageFolder
]);

foreach ($objects as $object) {
    // Download the file
    $filePath = $downloadDir . basename($object->name());
    $object->downloadToFile($filePath);
    echo 'Downloaded ' . $object->name() . ' to ' . $filePath . "\n";
    // Delete the file from the bucket
    $object->delete();
    echo "Deleted file:".$object->name()." \n";
}


}


$downloadDir = __DIR__ . '/repository/';
$UploadTheFile = 'Yes'; //Assume all is well to start off with
//But check for the worst
// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the maximum allowed file size from the hidden input
    $maxFileSize = $_POST['MAX_FILE_SIZE'];
   // Loop through the uploaded files
    foreach ($_FILES['userfile']['tmp_name'] as $key => $tmp_name) {
        $fileName = $_FILES['userfile']['name'][$key];
        $fileSize = $_FILES['userfile']['size'][$key];
       // Validate the file size
        if ($fileSize <= $maxFileSize) {
            // Move the uploaded file to a permanent location
            $destination = $downloadDir . $fileName;
            if (move_uploaded_file($tmp_name, $destination)) {
               $msg[]= "File '$fileName' uploaded successfully.";
            } else {
               $msg[]= "Error uploading file '$fileName'.";
            }
        } else {
            $msg[]= "Error: File '$fileName' exceeds the maximum allowed size of " . ($maxFileSize / 1024) . " KB.";
        }
    }
}


if (empty($msg)) { ?>
    <form autocomplete="off"method="post" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="100000">
        Select one or more files:
        <input name="userfile[]" type="file" multiple="multiple">
        <input type="submit" value="Send Files">
    </form>
<?php } else {
    echo var_dump($msg);
} ?>
</body>
</html>
