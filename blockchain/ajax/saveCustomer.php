<?php
// Call smartERPlims API for customer operations (single source of truth)

$config = include('../include/config.php');
$erpApiUrl = $config['ERP_API_URL'] ;

$customerCode = $_POST['customerCode'] ?? $_POST['itemcode'] ?? $_POST['code'] ?? '';

// Map fields by meaning (not by order)
// Blockchain fields → ERP fields
$customerData = [
    'customer' => $_POST['customer'] ?? $_POST['name'] ?? $_POST['CustomerName'] ?? '',
    'company' => $_POST['company'] ?? $_POST['address'] ?? $_POST['Address'] ?? '',
    'phone' => $_POST['phone'] ?? $_POST['telephone'] ?? $_POST['Phone'] ?? '',
    'fax' => $_POST['fax'] ?? '',
    'email' => $_POST['email'] ?? $_POST['Email'] ?? '',
    'city' => $_POST['city'] ?? $_POST['City'] ?? '',
    'country' => $_POST['country'] ?? $_POST['Country'] ?? '',
    'postcode' => $_POST['postcode'] ?? $_POST['postalcode'] ?? '',
    'altcontact' => $_POST['altcontact'] ?? $_POST['alt_phone'] ?? '',
    'contact' => $_POST['contact'] ?? $_POST['person'] ?? '',
    'middlen' => $_POST['middlen'] ?? $_POST['middlename'] ?? '',
    'creditlimit' => isset($_POST['creditlimit']) && is_numeric($_POST['creditlimit']) ? $_POST['creditlimit'] : 0,
    'inactive' => $_POST['inactive'] ?? '0',
    'curr_cod' => $_POST['curr_cod'] ?? $_POST['currency'] ?? 'KES',
    'customerposting' => $_POST['customerposting'] ?? 'GEN',
    'salesman' => $_POST['salesman'] ?? ''
];

// Build post data
if (isset($_POST['deleteaccount'])) {
    $postData = json_encode([
        'action' => 'delete_customer',
        'itemcode' => $customerCode
    ]);
} elseif (!empty($customerCode)) {
    // Update - has existing itemcode
    $customerData['itemcode'] = $customerCode;
    $customerData['action'] = 'update_customer';
    $postData = json_encode($customerData);
} else {
    // Create new - no itemcode
    $customerData['action'] = 'create_customer';
    $postData = json_encode($customerData);
}

$debug['postData'] = $postData;
$debug['apiUrl'] = $erpApiUrl;

$ch = curl_init($erpApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$debug['httpCode'] = $httpCode;
$debug['response'] = $response;
$debug['curlError'] = $curlError;

$result = json_decode($response, true);

// Check for success OR 200 response
if (($result['success'] ?? false) === true || $httpCode === 200) {
    echo json_encode(['status' => 'success', 'message' => $result['message'] ?? 'Customer saved successfully!']);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => $result['message'] ?? 'Failed to save customer',
        'debug' => $debug
    ]);
}

?>