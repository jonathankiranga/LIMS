<?php
require '../db_connection.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$standards = $input['standards'] ?? [];
$deleted = $input['deleted'] ?? [];

$saved = 0;
$errors = [];

// Process deletions first
foreach ($deleted as $sid) {
    $sid = intval($sid);
    if ($sid > 0) {
        if (!$conn->query("DELETE FROM TestStandards WHERE StandardID=$sid")) {
            $errors[] = "Failed to delete StandardID $sid: " . $conn->error;
        }
    }
}

// Process upserts
foreach ($standards as $s) {
    $id = intval($s['StandardID'] ?? 0);
    $code = $conn->real_escape_string($s['StandardCode'] ?? '');
    $name = $conn->real_escape_string($s['StandardName'] ?? '');
    $desc = $conn->real_escape_string($s['Description'] ?? '');
    $reg = $conn->real_escape_string($s['ApplicableRegulation'] ?? '');
    $sm = intval($s['sm'] ?? 0);
    $now = date('Y-m-d H:i:s');

    if (empty($name)) continue;

    $smVal = $sm > 0 ? $sm : 'NULL';

    if ($id > 0) {
        if (empty($code)) {
            $code = 'STD-' . str_pad($id, 4, '0', STR_PAD_LEFT);
        }
        $sql = "UPDATE TestStandards SET StandardCode='$code', StandardName='$name', Description='$desc', 
                ApplicableRegulation='$reg', sm=$smVal, UpdatedAt='$now' WHERE StandardID=$id";
    } else {
        $sql = "INSERT INTO TestStandards (StandardCode, StandardName, Description, ApplicableRegulation, sm, CreatedAt) 
                VALUES ('$code', '$name', '$desc', '$reg', $smVal, '$now')";
    }

    if ($conn->query($sql)) {
        $newId = ($id > 0) ? $id : $conn->insert_id;
        if (empty($code)) {
            $autoCode = 'STD-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
            $conn->query("UPDATE TestStandards SET StandardCode='$autoCode' WHERE StandardID=$newId");
        }
        $saved++;
    } else {
        $errors[] = "Row '$name': " . $conn->error;
    }
}

echo json_encode(['ok' => true, 'saved' => $saved, 'deleted' => count($deleted), 'errors' => $errors]);
