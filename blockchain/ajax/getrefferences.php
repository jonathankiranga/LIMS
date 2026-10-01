<?php
$json_data = file_get_contents('php://input');
// Decode the JSON data into a PHP associative array
$data = json_decode($json_data, true); // 'true' converts it into an associative array
// Database connection
require '../db_connection.php'; 
// Function to get the next transaction number and increment it in the database
function GetNextTransNo($TransType) {
    global $conn; // Use the MySQLi connection

    // Increment the transaction number
    $SQL = sprintf("UPDATE systypes SET typeno = typeno + 1 WHERE typeid = '%s'", $TransType);
    if (!$conn->query($SQL)) {
        die(_('CRITICAL ERROR') . ': ' . _('The transaction number could not be incremented. MySQL error: ') . $conn->error);
    }

    // Retrieve the new transaction number
    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno FROM systypes WHERE typeid = '%s'", $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = trim($myrow[1]);

    return $prefix . $nexno;
}
// Function to preview the next transaction number without updating it
function GetTempNextNo($TransType) {
    global $conn; // Use the MySQLi connection

    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno + 1 FROM systypes WHERE typeid = '%s'", $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = trim($myrow[1]);

    return $prefix . $nexno;
}
// Function to get the next lab reference number and increment it in the database
function GetNextLabrefNo($TransType) {
    global $conn; // Use the MySQLi connection

    // Increment the transaction number
    $SQL = sprintf("UPDATE systypes SET typeno = typeno + 1 WHERE typeid = '%s'", $TransType);
    if (!$conn->query($SQL)) {
        die(_('CRITICAL ERROR') . ': ' . _('The transaction number could not be incremented. MySQL error: ') . $conn->error);
    }

    // Retrieve the new transaction number
    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno FROM systypes WHERE typeid = '%s'", $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = str_pad(trim($myrow[1]), 6, '0', STR_PAD_LEFT); // Pad with zeros if required

    return $prefix . $nexno;
}
// Function to preview the next lab reference number without updating it
function GetTempLabrefNo($TransType) {
    global $conn; // Use the MySQLi connection

    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno + 1 FROM systypes WHERE typeid = '%s'", $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $nexno  = str_pad(trim($myrow[1]), 6, '0', STR_PAD_LEFT); // Pad with zeros if required

    return $prefix . $nexno;
}
// Function to preview a barcode reference number with a custom seed

function GetTempLabrefprefix($TransType) {
    global $conn; // Use the MySQLi connection

    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno + 1 FROM systypes WHERE typeid = '%s'", $TransType);
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
 
    return $prefix ;
}


function GetTempBarcoderfNo($TransType, $seed = 1) {
    global $conn; // Use the MySQLi connection

    $SQL = sprintf("SELECT IFNULL(prefix, '') AS prefix, typeno FROM systypes WHERE typeid = '%s'", 
      $conn->real_escape_string($TransType)
    );
    
    $result = $conn->query($SQL);
    if (!$result) {
        die(_('CRITICAL ERROR') . ': ' . _('The next transaction number could not be retrieved from the database. MySQL error: ') . $conn->error);
    }

    $myrow = $result->fetch_row();
    $prefix = trim($myrow[0]);
    $added  =(int) $myrow[1];
    $float  =(int) $added+ $seed;
    
    $nexno  = str_pad(trim($float), 6, '0', STR_PAD_LEFT); // Pad with zeros if required

    return $prefix . ($nexno);
}
// Check if the necessary data is set
if (isset($data['action']) && isset($data['TransType'])) {
$action = $data['action'];
$TransType = $data['TransType'];
$response = []; // Initialize response array

    try {
        // Call the appropriate function based on the action
        switch ($action) {
            case 'GetNextTransNo':
                $response['data'] = GetNextTransNo($TransType);
                break;
            case 'GetTempNextNo':
                $response['data'] = GetTempNextNo($TransType);
                break;
            case 'GetNextLabrefNo':
                $response['data'] = GetNextLabrefNo($TransType);
                break;
            case 'GetTempLabrefNo':
                $response['data'] = GetTempLabrefNo($TransType);
                break;
            case 'GetTempLabrefprefix':
                $response['data'] = GetTempLabrefprefix($TransType);
                break;
            case 'GetTempBarcoderfNo':
                $seed = isset($data['seed']) ? intval($data['seed']) : 1;
                $response['data'] = GetTempBarcoderfNo($TransType, $seed);
                break;
            default:
                throw new Exception("Invalid action specified");
        }

        $response['status'] = 'success';
    } catch (Exception $e) {
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Missing required parameters';
}


header('Content-Type: application/json'); // Set the response header to JSON
echo json_encode($response); // Return the response as JSON
