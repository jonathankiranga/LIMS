<?php
require_once __DIR__ . '/../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

function qro_json_response($success, $message, $options = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = array('success' => $success, 'message' => $message);
    if ($options !== null) {
        $response['options'] = $options;
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $options = array();
    $result = $conn->query('SELECT OptionID, OptionValue, OptionLabel, DisplayOrder, IsActive FROM qualitative_result_options ORDER BY DisplayOrder ASC, OptionLabel ASC');
    if (!$result) {
        qro_json_response(false, 'Could not load options. Confirm the qualitative_result_options migration has been applied.', null, 500);
    }
    while ($row = $result->fetch_assoc()) {
        $options[] = array(
            'OptionID' => (int) $row['OptionID'],
            'OptionValue' => $row['OptionValue'],
            'OptionLabel' => $row['OptionLabel'],
            'DisplayOrder' => (int) $row['DisplayOrder'],
            'IsActive' => (int) $row['IsActive']
        );
    }
    $result->free();
    $conn->close();
    qro_json_response(true, 'Options loaded.', $options);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    qro_json_response(false, 'Invalid request method.', null, 405);
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if ($action === 'add') {
    $value = isset($_POST['OptionValue']) ? trim($_POST['OptionValue']) : '';
    $label = isset($_POST['OptionLabel']) ? trim($_POST['OptionLabel']) : '';
    $order = isset($_POST['DisplayOrder']) && $_POST['DisplayOrder'] !== '' ? (int) $_POST['DisplayOrder'] : 0;

    if ($value === '' || $label === '') {
        qro_json_response(false, 'Option value and display label are required.', null, 400);
    }
    if (strlen($value) > 100 || strlen($label) > 150) {
        qro_json_response(false, 'Option value must be 100 characters or fewer and label 150 characters or fewer.', null, 400);
    }

    $stmt = $conn->prepare('INSERT INTO qualitative_result_options (OptionValue, OptionLabel, DisplayOrder, IsActive) VALUES (?, ?, ?, 1)');
    if (!$stmt) {
        qro_json_response(false, 'Could not prepare the add operation.', null, 500);
    }
    $stmt->bind_param('ssi', $value, $label, $order);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        qro_json_response(true, 'Qualitative result option added.');
    }
    $duplicate = $stmt->errno === 1062;
    $stmt->close();
    $conn->close();
    qro_json_response(false, $duplicate ? 'That option value already exists. Use a unique stored value.' : 'Could not add the option.', null, 400);
}

if ($action === 'update') {
    $id = isset($_POST['OptionID']) ? (int) $_POST['OptionID'] : 0;
    $label = isset($_POST['OptionLabel']) ? trim($_POST['OptionLabel']) : '';
    $order = isset($_POST['DisplayOrder']) && $_POST['DisplayOrder'] !== '' ? (int) $_POST['DisplayOrder'] : 0;
    if ($id <= 0 || $label === '' || strlen($label) > 150) {
        qro_json_response(false, 'A valid option and display label of 150 characters or fewer are required.', null, 400);
    }
    $stmt = $conn->prepare('UPDATE qualitative_result_options SET OptionLabel = ?, DisplayOrder = ?, UpdatedAt = NOW() WHERE OptionID = ?');
    if (!$stmt) {
        qro_json_response(false, 'Could not prepare the update operation.', null, 500);
    }
    $stmt->bind_param('sii', $label, $order, $id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    qro_json_response($ok, $ok ? 'Option updated.' : 'Could not update the option.', null, $ok ? 200 : 500);
}

if ($action === 'toggle') {
    $id = isset($_POST['OptionID']) ? (int) $_POST['OptionID'] : 0;
    if ($id <= 0) {
        qro_json_response(false, 'Invalid option.', null, 400);
    }
    $stmt = $conn->prepare('UPDATE qualitative_result_options SET IsActive = IF(IsActive = 1, 0, 1), UpdatedAt = NOW() WHERE OptionID = ?');
    if (!$stmt) {
        qro_json_response(false, 'Could not prepare the status update.', null, 500);
    }
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute() && $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    qro_json_response($ok, $ok ? 'Option status changed.' : 'Option not found or status was unchanged.', null, $ok ? 200 : 400);
}

$conn->close();
qro_json_response(false, 'Unknown action.', null, 400);
?>