<?php
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../functions/quote_support.php';

header('Content-Type: application/json; charset=utf-8');

function quote_json_exit(array $payload, $statusCode = 200)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function quote_build_post_payload()
{
    $sessionUser = quote_get_session_user();
    $postedUser = trim((string)($_POST['username'] ?? ''));

    return [
        'quote_id' => trim((string)($_POST['quote_id'] ?? '')),
        'quote_number' => trim((string)($_POST['quote_number'] ?? '')),
        'customer_id' => trim((string)($_POST['customer_id'] ?? '')),
        'client_name' => trim((string)($_POST['client_name'] ?? '')),
        'client_attention' => trim((string)($_POST['client_attention'] ?? '')),
        'client_address' => trim((string)($_POST['client_address'] ?? '')),
        'client_city' => trim((string)($_POST['client_city'] ?? '')),
        'project_name' => trim((string)($_POST['project_name'] ?? '')),
        'matrix_name' => trim((string)($_POST['matrix_name'] ?? '')),
        'turnaround_time' => trim((string)($_POST['turnaround_time'] ?? '')),
        'quote_date' => trim((string)($_POST['quote_date'] ?? '')),
        'expiry_date' => trim((string)($_POST['expiry_date'] ?? '')),
        'discount_percent' => trim((string)($_POST['discount_percent'] ?? '0')),
        'terms_text' => trim((string)($_POST['terms_text'] ?? '')),
        'user_name' => $postedUser !== '' ? $postedUser : $sessionUser,
    ];
}

function quote_build_bootstrap_payload(mysqli $conn, $quoteId = 0)
{
    $company = get_quote_company_profile($conn);
    $defaultTerms = get_quote_default_terms($conn);
    $customerLookup = '';

    if ($quoteId > 0) {
        $quote = get_quote_by_id($conn, $quoteId);
        if (!$quote) {
            throw new InvalidArgumentException('Quote not found.');
        }
        $state = normalize_quote_form_state($conn, $quote);
        $customer = get_quote_customer_by_id($conn, $state['formData']['customer_id'] ?? '');
        $customerLookup = (string)($customer['customer'] ?? ($state['formData']['client_name'] ?? ''));
    } else {
        $quote = null;
        $state = normalize_quote_form_state($conn);
        $customerLookup = '';
    }

    return [
        'success' => true,
        'company' => $company,
        'defaultTerms' => $defaultTerms,
        'customer_lookup' => $customerLookup,
        'quote_id' => (int)($state['formData']['quote_id'] ?? 0),
        'quote_number' => (string)($state['formData']['quote_number'] ?? ''),
        'quote' => $quote,
        'formData' => $state['formData'],
        'items' => $state['formItems'],
        'pdf_url' => !empty($state['formData']['quote_id']) ? 'ajax/quoteDocumentPdf.php?quote_id=' . (int)$state['formData']['quote_id'] : '',
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = trim((string)($_GET['action'] ?? 'bootstrap'));
        if ($action === 'bootstrap') {
            $quoteId = (int)($_GET['quote_id'] ?? 0);
            quote_json_exit(quote_build_bootstrap_payload($conn, $quoteId));
        }

        if ($action === 'my_quotes') {
            quote_json_exit([
                'success' => true,
                'quotes' => get_quotes_for_user(
                    $conn,
                    quote_get_session_user(),
                    (string)($_GET['query'] ?? ''),
                    (int)($_GET['limit'] ?? 100)
                ),
            ]);
        }

        if ($action === 'next_number') {
            quote_json_exit([
                'success' => true,
                'quote_number' => generate_quote_number($conn),
            ]);
        }

        $quoteId = (int)($_GET['quote_id'] ?? 0);
        if ($quoteId <= 0) {
            quote_json_exit(['success' => false, 'message' => 'Quote ID is required.'], 400);
        }

        $quote = get_quote_by_id($conn, $quoteId);
        if (!$quote) {
            quote_json_exit(['success' => false, 'message' => 'Quote not found.'], 404);
        }

        $state = normalize_quote_form_state($conn, $quote);
        quote_json_exit([
            'success' => true,
            'quote_id' => $quoteId,
            'quote_number' => $quote['quote_number'] ?? '',
            'quote' => $quote,
            'formData' => $state['formData'],
            'items' => $state['formItems'],
            'pdf_url' => 'ajax/quoteDocumentPdf.php?quote_id=' . $quoteId,
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        quote_json_exit(['success' => false, 'message' => 'Invalid request method.'], 405);
    }

    $action = trim((string)($_POST['action'] ?? 'save_quote'));
    if ($action === 'save_default_terms') {
        save_quote_default_terms($conn, (string)($_POST['terms_text'] ?? ''));
        quote_json_exit([
            'success' => true,
            'message' => 'Default quote terms saved successfully.',
            'default_terms' => get_quote_default_terms($conn),
        ]);
    }

    $formData = array_merge(get_quote_form_defaults($conn), quote_build_post_payload());
    $formItems = collect_quote_items_from_request($_POST);
    $quoteId = save_quote($conn, $formData, $formItems);
    $quote = get_quote_by_id($conn, $quoteId);

    if (!$quote) {
        throw new RuntimeException('The quote was saved, but it could not be reloaded.');
    }

    $state = normalize_quote_form_state($conn, $quote);
    quote_json_exit([
        'success' => true,
        'message' => 'Quote saved successfully.',
        'quote_id' => $quoteId,
        'quote_number' => $quote['quote_number'] ?? '',
        'quote' => $quote,
        'formData' => $state['formData'],
        'items' => $state['formItems'],
        'pdf_url' => 'ajax/quoteDocumentPdf.php?quote_id=' . $quoteId,
    ]);
} catch (InvalidArgumentException $exception) {
    quote_json_exit(['success' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $throwable) {
    quote_json_exit(['success' => false, 'message' => 'Server error: ' . $throwable->getMessage()], 500);
}
