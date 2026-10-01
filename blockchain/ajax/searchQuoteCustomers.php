<?php
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../functions/quote_support.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim((string)($_GET['query'] ?? ''));
if ($query === '') {
    echo json_encode([]);
    exit;
}

if (!quote_table_exists($conn, 'debtors')) {
    echo json_encode([]);
    exit;
}

$searchPattern = '%' . $query . '%';
$stmt = $conn->prepare("
    SELECT
        itemcode,
        customer,
        contact,
        company,
        postcode,
        city,
        phone,
        email
    FROM debtors
    WHERE itemcode LIKE ? OR customer LIKE ? OR company LIKE ? OR city LIKE ?
    ORDER BY customer ASC
    LIMIT 10
");

if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('ssss', $searchPattern, $searchPattern, $searchPattern, $searchPattern);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];

while ($result && ($row = $result->fetch_assoc())) {
    $rows[] = [
        'itemcode' => (string)($row['itemcode'] ?? ''),
        'customer' => (string)($row['customer'] ?? ''),
        'contact' => (string)($row['contact'] ?? ''),
        'company' => (string)($row['company'] ?? ''),
        'postcode' => (string)($row['postcode'] ?? ''),
        'city' => (string)($row['city'] ?? ''),
        'phone' => (string)($row['phone'] ?? ''),
        'email' => (string)($row['email'] ?? ''),
    ];
}

$stmt->close();
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
