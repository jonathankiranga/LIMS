<?php
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../functions/quote_support.php';

function quote_pdf_error($message, $statusCode = 400)
{
    http_response_code((int)$statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => (string)$message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        quote_pdf_error('Invalid request method.', 405);
    }

    $quoteId = (int)($_GET['quote_id'] ?? 0);
    if ($quoteId <= 0) {
        quote_pdf_error('Quote ID is required.', 400);
    }

    $quote = get_quote_by_id($conn, $quoteId);
    if (!$quote) {
        quote_pdf_error('Quote not found.', 404);
    }

    $pdfContent = render_quote_pdf($conn, $quote);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="quote-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)$quote['quote_number']) . '.pdf"');
    echo $pdfContent;
    exit;
} catch (Throwable $throwable) {
    quote_pdf_error('Server error: ' . $throwable->getMessage(), 500);
}
