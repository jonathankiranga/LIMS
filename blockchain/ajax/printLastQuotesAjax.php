<?php
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../functions/quote_support.php';

header('Content-Type: application/json; charset=utf-8');

function print_last_quotes_json_exit(array $payload, $statusCode = 200)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        print_last_quotes_json_exit([
            'success' => true,
            'quotes' => get_recent_quotes(
                $conn,
                (string)($_GET['query'] ?? ''),
                (int)($_GET['limit'] ?? 100)
            ),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        print_last_quotes_json_exit(['success' => false, 'message' => 'Invalid request method.'], 405);
    }

    $action = trim((string)($_POST['action'] ?? ''));
    if ($action !== 'email_quote') {
        print_last_quotes_json_exit(['success' => false, 'message' => 'Unsupported action.'], 400);
    }

    $quoteId = (int)($_POST['quote_id'] ?? 0);
    $recipientEmail = trim((string)($_POST['recipient_email'] ?? ''));
    $result = send_quote_email($conn, $quoteId, $recipientEmail);

    print_last_quotes_json_exit($result);
} catch (InvalidArgumentException $exception) {
    print_last_quotes_json_exit(['success' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $throwable) {
    print_last_quotes_json_exit(['success' => false, 'message' => 'Server error: ' . $throwable->getMessage()], 500);
}
