<?php
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../functions/quote_support.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim((string)($_GET['query'] ?? ''));
$limit = (int)($_GET['limit'] ?? 40);
$standardId = (int)($_GET['standard_id'] ?? 0);

if ($standardId > 0) {
    echo json_encode(
        get_quote_parameters_by_standard($conn, $standardId, $limit),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

echo json_encode(
    search_quote_parameters($conn, $query, $limit),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
