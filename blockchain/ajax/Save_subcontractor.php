<?php
// Call smartERPlims API for supplier/subcontractor operations (single source of truth)

$config = include('../include/config.php');
$erpApiUrl = $config['ERP_API_URL'] ;

if (isset($_POST['delete'])) {
    // Delete supplier
    $postData = json_encode([
        'action' => 'delete_supplier',
        'editcode' => $_POST['itemcode']
    ]);
} elseif (isset($_POST['itemcode'])) {
    // Update supplier
    $postData = json_encode([
        'action' => 'update_supplier',
        'editcode' => $_POST['itemcode'],
        'customer' => $_POST['customer'] ?? '',
        'contact' => $_POST['contact'] ?? '',
        'vatregno' => $_POST['vatregno'] ?? '',
        'firstn' => $_POST['firstn'] ?? '',
        'middlen' => $_POST['middlen'] ?? '',
        'lastn' => $_POST['lastn'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'fax' => $_POST['fax'] ?? '',
        'company' => $_POST['company'] ?? '',
        'altcontact' => $_POST['altcontact'] ?? '',
        'email' => $_POST['email'] ?? '',
        'city' => $_POST['city'] ?? '',
        'country' => $_POST['country'] ?? '',
        'inactive' => $_POST['inactive'] ?? '0',
        'postcode' => $_POST['postcode'] ?? '',
        'curr_cod' => $_POST['curr_cod'] ?? 'KES',
        'supplierposting' => $_POST['supplierposting'] ?? 'GEN'
    ]);
} else {
    // Create new supplier
    $postData = json_encode([
        'action' => 'create_supplier',
        'customer' => $_POST['customer'] ?? '',
        'contact' => $_POST['contact'] ?? '',
        'vatregno' => $_POST['vatregno'] ?? '',
        'firstn' => $_POST['firstn'] ?? '',
        'middlen' => $_POST['middlen'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'fax' => $_POST['fax'] ?? '',
        'company' => $_POST['company'] ?? '',
        'altcontact' => $_POST['altcontact'] ?? '',
        'email' => $_POST['email'] ?? '',
        'city' => $_POST['city'] ?? '',
        'country' => $_POST['country'] ?? '',
        'inactive' => $_POST['inactive'] ?? '0',
        'postcode' => $_POST['postcode'] ?? '',
        'curr_cod' => $_POST['curr_cod'] ?? 'KES',
        'supplierposting' => $_POST['supplierposting'] ?? 'GEN'
    ]);
}

$ch = curl_init($erpApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $result = json_decode($response, true);
    if ($result['success'] ?? false) {
        echo json_encode(['status' => 'success', 'message' => $result['message'] ?? ' successful']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $result['message'] ?? 'Failed to save supplier']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to connect to ERP API']);
}

?>