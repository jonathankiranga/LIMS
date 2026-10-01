<?php
require '../db_connection.php'; // Ensure this connects to your MySQLi instance
if(isset($_POST['LabPrefix'])){
    $SQL = SPRINTF("UPDATE systypes_1 SET typeno = 0,prefix='%s' WHERE typeid = '%d' ",trim($_POST['LabPrefix']),'10');
    $conn->query($SQL);
    $response['status'] = 'success';
}else{
     $response['status'] = 'error';
     $response['message'] = 'No setting found';
}
 

header('Content-Type: application/json'); // Set the response header to JSON
echo json_encode($response); // Return the response as JSON
