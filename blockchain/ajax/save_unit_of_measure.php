<?php

require '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => 'Invalid request.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

$unit = trim($_POST['unitOfMeasure'] ?? '');

if ($unit === '') {
    $response['message'] = 'Unit of measure is required.';
    echo json_encode($response);
    exit;
}

if (mb_strlen($unit, 'UTF-8') > 50) {
    $response['message'] = 'Unit of measure cannot exceed 50 characters.';
    echo json_encode($response);
    exit;
}

try {
    $check = $conn->prepare(
        "SELECT UnitID
         FROM units_of_measure
         WHERE UnitOfMeasure = ?
         LIMIT 1"
    );

    if (!$check) {
        throw new RuntimeException('SQL prepare failed: ' . $conn->error);
    }

    $check->bind_param('s', $unit);

    if (!$check->execute()) {
        throw new RuntimeException('SQL execute failed: ' . $check->error);
    }

    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($existingID);
        $check->fetch();
        $check->close();

        $response = [
            'success' => true,
            'message' => 'Unit of measure already exists.',
            'UnitID' => (int)$existingID,
            'UnitOfMeasure' => $unit
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    $check->close();

    $description = null;
    $createdBy = null;

    if (isset($_SESSION['UserID'])) {
        $createdBy = (int)$_SESSION['UserID'];
    } elseif (isset($_SESSION['userid'])) {
        $createdBy = (int)$_SESSION['userid'];
    }

    $stmt = $conn->prepare(
        "INSERT INTO units_of_measure
            (UnitOfMeasure, Description, Active, CreatedBy, CreatedDate)
         VALUES (?, ?, 1, ?, NOW())"
    );

    if (!$stmt) {
        throw new RuntimeException('SQL prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('ssi', $unit, $description, $createdBy);

    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to save unit of measure: ' . $stmt->error);
    }

    $newID = $stmt->insert_id;
    $stmt->close();

    $response = [
        'success' => true,
        'message' => 'Unit of measure added successfully.',
        'UnitID' => (int)$newID,
        'UnitOfMeasure' => $unit
    ];

} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
