<?php
// Public supplier quotation portal. Access is granted only by an invitation token.
// Resolve the tenant database from the invitation URL before the normal ERP bootstrap.
$SessionName = 'ErpRFQPortal';
include('config.php');
$requestedDatabase = isset($_GET['company']) ? trim($_GET['company']) : (isset($_POST['company']) ? trim($_POST['company']) : '');
$allowedDatabases = array();
if (isset($CompanyList) && is_array($CompanyList)) {
    foreach ($CompanyList as $companyEntry) {
        if (isset($companyEntry['database'])) { $allowedDatabases[] = $companyEntry['database']; }
    }
}
if ($requestedDatabase === '' || !in_array($requestedDatabase, $allowedDatabases, true)) {
    http_response_code(403);
    die('The supplier invitation does not identify a valid company database.');
}
$DatabaseName = $requestedDatabase;
$AllowAnyone = true;
include('includes/session.inc');
$_SESSION['UserID'] = 'SUPPLIER_PORTAL';

function supplier_rfq_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function supplier_rfq_fail($message, $code = 400) {
    http_response_code($code);
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Supplier RFQ Portal</title><link rel="stylesheet" href="javascripts/bootstrap.min.css"></head><body style="background:#f4f6f9"><div class="container" style="max-width:850px;margin:45px auto"><div class="panel panel-default"><div class="panel-body"><h3>Supplier Quotation Portal</h3><div class="alert alert-warning">' . supplier_rfq_h($message) . '</div></div></div></div></body></html>';
    exit;
}

$token = isset($_POST['token']) ? trim($_POST['token']) : (isset($_GET['token']) ? trim($_GET['token']) : '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    supplier_rfq_fail('This invitation link is invalid. Please contact the purchasing department for assistance.', 403);
}
$tokenHash = hash('sha256', $token);
$sql = "SELECT i.id AS invite_id,i.status AS invite_status,i.supplier_code,i.supplier_email,
               h.id AS rfq_id,h.rfq_no,h.title,h.description,h.terms_conditions,h.currencycode,
               h.closing_at,h.status AS rfq_status,c.customer
        FROM rfq_invites i
        JOIN rfq_header h ON h.id=i.rfq_id
        JOIN creditors c ON c.itemcode = i.supplier_code COLLATE utf8mb4_unicode_ci
        WHERE i.token_hash='" . DB_escape_string($tokenHash) . "'
        LIMIT 1";
$result = DB_query($sql, $db);
if (DB_num_rows($result) !== 1) {
    supplier_rfq_fail('This invitation could not be found. Please contact the purchasing department.', 404);
}
$invitation = DB_fetch_array($result);
if ($invitation['invite_status'] === 'submitted') {
    supplier_rfq_fail('A quotation has already been submitted for this invitation. The system accepts only one submission per supplier per RFQ.', 409);
}
if ($invitation['rfq_status'] !== 'issued' || strtotime($invitation['closing_at']) < time()) {
    supplier_rfq_fail('This RFQ is no longer accepting quotations because it has been closed or its submission deadline has passed.', 410);
}
$lineResult = DB_query("SELECT rl.*,sm.descrip AS inventory_name,u.descrip AS requested_unit_description
    FROM rfq_lines rl
    LEFT JOIN stockmaster sm ON sm.itemcode COLLATE utf8mb4_unicode_ci = rl.itemcode COLLATE utf8mb4_unicode_ci
    LEFT JOIN unit u ON u.code=rl.requested_pack
    WHERE rl.rfq_id=" . (int)$invitation['rfq_id'] . " ORDER BY rl.line_no", $db);
$lines = array();
while ($line = DB_fetch_array($lineResult)) { $lines[] = $line; }
if (!count($lines)) {
    supplier_rfq_fail('This RFQ has no product lines. Please contact the purchasing department.', 409);
}

$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quote'])) {
    $prices = isset($_POST['unit_price']) && is_array($_POST['unit_price']) ? $_POST['unit_price'] : array();
    $taxes = isset($_POST['tax_rate']) && is_array($_POST['tax_rate']) ? $_POST['tax_rate'] : array();
    $availability = isset($_POST['availability_notes']) && is_array($_POST['availability_notes']) ? $_POST['availability_notes'] : array();
    $accepted = isset($_POST['accept_terms']) && $_POST['accept_terms'] === '1';
    $validity = isset($_POST['validity_date']) ? trim($_POST['validity_date']) : '';
    $deliveryDays = isset($_POST['delivery_days']) ? (int)$_POST['delivery_days'] : 0;
    $reference = isset($_POST['quote_reference']) ? trim($_POST['quote_reference']) : '';
    $paymentTerms = isset($_POST['payment_terms']) ? trim($_POST['payment_terms']) : '';
    $notes = isset($_POST['supplier_notes']) ? trim($_POST['supplier_notes']) : '';
    $taxInclusive = (isset($_POST['tax_inclusive']) && $_POST['tax_inclusive'] === '1') ? 1 : 0;

    if (!$accepted) {
        $error = 'You must confirm acceptance of the RFQ terms and conditions before submitting.';
    } elseif (!isset($_POST['tax_inclusive']) || !in_array((string)$_POST['tax_inclusive'], array('0', '1'), true)) {
        $error = 'Select whether the quoted prices include tax.';
    } elseif (count($prices) !== count($lines)) {
        $error = 'A unit price is required for every requested product.';
    } else {
        foreach ($lines as $idx => $line) {
            if (!isset($prices[$idx]) || !is_numeric($prices[$idx]) || (float)$prices[$idx] < 0 ||
                !isset($taxes[$idx]) || !is_numeric($taxes[$idx]) || (float)$taxes[$idx] < 0 || (float)$taxes[$idx] > 100) {
                $error = 'Enter a valid non-negative unit price and tax percentage for every product.';
                break;
            }
        }
    }

    if ($error === '') {
        mysqli_begin_transaction($db);
        try {
            // Lock invitation and RFQ to serialize concurrent submit attempts.
            $lock = DB_query("SELECT i.id,h.status,h.closing_at,i.status AS invite_status
                FROM rfq_invites i JOIN rfq_header h ON h.id=i.rfq_id
                WHERE i.id=" . (int)$invitation['invite_id'] . " AND i.token_hash='" . DB_escape_string($tokenHash) . "'
                FOR UPDATE", $db);
            if (DB_num_rows($lock) !== 1) { throw new Exception('Invitation is no longer valid.'); }
            $locked = DB_fetch_array($lock);
            if ($locked['status'] !== 'issued' || $locked['invite_status'] !== 'invited' || strtotime($locked['closing_at']) < time()) {
                throw new Exception('This RFQ has closed or a quotation has already been submitted.');
            }
            DB_query("INSERT INTO rfq_quotes
                (invite_id,rfq_id,supplier_code,quote_reference,validity_date,delivery_days,payment_terms,supplier_notes,accepted_terms,tax_inclusive,submit_ip)
                VALUES (" . (int)$invitation['invite_id'] . "," . (int)$invitation['rfq_id'] . ",'" .
                DB_escape_string($invitation['supplier_code']) . "','" . DB_escape_string($reference) . "'," .
                ($validity !== '' ? "'" . DB_escape_string($validity) . "'" : "NULL") . "," . max(0, $deliveryDays) . ",'" .
                DB_escape_string($paymentTerms) . "','" . DB_escape_string($notes) . "',1," . $taxInclusive . ",'" .
                DB_escape_string(isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '') . "')", $db);
            $newQuoteResult = DB_query("SELECT id FROM rfq_quotes WHERE invite_id=" . (int)$invitation['invite_id'] . " LIMIT 1", $db);
            $newQuoteRow = DB_fetch_array($newQuoteResult);
            $quoteId = (int)$newQuoteRow['id'];
            foreach ($lines as $idx => $line) {
                DB_query("INSERT INTO rfq_quote_lines (quote_id,rfq_line_id,unit_price,tax_rate,availability_notes)
                    VALUES (" . (int)$quoteId . "," . (int)$line['id'] . "," . (float)$prices[$idx] . "," .
                    (float)$taxes[$idx] . ",'" . DB_escape_string(isset($availability[$idx]) ? $availability[$idx] : '') . "')", $db);
            }
            DB_query("UPDATE rfq_invites SET status='submitted',submitted_at=NOW() WHERE id=" . (int)$invitation['invite_id'] . " AND status='invited'", $db);
            if (mysqli_affected_rows($db) !== 1) { throw new Exception('This invitation has already been used.'); }
            mysqli_commit($db);
            $success = true;
        } catch (Exception $e) {
            mysqli_rollback($db);
            $error = $e->getMessage();
        }
    }
}

if ($success) {
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quotation submitted</title><link rel="stylesheet" href="javascripts/bootstrap.min.css"></head><body style="background:#f4f6f9"><div class="container" style="max-width:850px;margin:45px auto"><div class="panel panel-default"><div class="panel-body"><h2>Quotation submitted successfully</h2><div class="alert alert-success">Thank you. Your quotation for ' . supplier_rfq_h($invitation['rfq_no']) . ' has been recorded. This invitation cannot be used to submit another quotation.</div>You may close this page.</div></div></div></body></html>';
    exit;
}

echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Supplier RFQ Portal</title><link rel="stylesheet" href="javascripts/bootstrap.min.css"><style>body{background:#f4f6f9}.rfq-wrap{max-width:1150px;margin:30px auto}.panel{border-radius:7px}.muted{color:#657080}.terms{background:#f8f9fb;border:1px solid #dfe4ea;padding:16px;border-radius:5px;white-space:pre-wrap;max-height:320px;overflow:auto}</style></head><body><div class="container rfq-wrap">';
echo '<div class="panel panel-default"><div class="panel-heading"><h3>Supplier Quotation Submission</h3></div><div class="panel-body">';
if ($error !== '') { echo '<div class="alert alert-danger">' . supplier_rfq_h($error) . '</div>'; }
echo '<div class="row"><div class="col-sm-8"><h4>' . supplier_rfq_h($invitation['rfq_no']) . ' — ' . supplier_rfq_h($invitation['title']) . '</h4><p class="muted">Invited supplier: <strong>' . supplier_rfq_h($invitation['customer']) . '</strong></p>';
if ($invitation['description'] !== '') { echo '<p>' . nl2br(supplier_rfq_h($invitation['description'])) . '</p>'; }
echo '</div><div class="col-sm-4 text-right"><strong>Quotation deadline</strong><br>' . supplier_rfq_h($invitation['closing_at']) . '<br><strong>Currency:</strong> ' . supplier_rfq_h($invitation['currencycode']) . '</div></div><hr>';
echo '<h4>Terms and conditions</h4><div class="terms">' . supplier_rfq_h($invitation['terms_conditions']) . '</div>';
echo '<form method="post" style="margin-top:20px"><input type="hidden" name="token" value="' . supplier_rfq_h($token) . '"><input type="hidden" name="company" value="' . supplier_rfq_h($requestedDatabase) . '"><h4>Quoted prices</h4><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Laboratory inventory item</th><th>Laboratory specification / grade</th><th>Requested quantity / unit</th><th>Price per requested unit (' . supplier_rfq_h($invitation['currencycode']) . ')</th><th>Tax %</th><th>Brand / availability</th></tr></thead><tbody>';
foreach ($lines as $idx => $line) {
    $inventoryName = trim((string)$line['inventory_name']) !== '' ? $line['inventory_name'] : $line['description'];
    $requestedDescription = trim((string)$line['description']);
    echo '<tr><td><strong>' . supplier_rfq_h($inventoryName) . '</strong>' .
        ($requestedDescription !== '' && strcasecmp($requestedDescription, $inventoryName) !== 0 ? '<br><small>Requested description: ' . supplier_rfq_h($requestedDescription) . '</small>' : '') .
        '</td><td>' . nl2br(supplier_rfq_h($line['specification'])) . '</td><td>' . supplier_rfq_h($line['quantity'] . ' ' . (!empty($line['requested_unit_description']) ? $line['requested_unit_description'] : $line['requested_pack'])) . '</td>';
    echo '<td><input class="form-control" type="number" min="0" step="0.0001" name="unit_price[' . $idx . ']" required></td><td><input class="form-control" type="number" min="0" max="100" step="0.01" name="tax_rate[' . $idx . ']" value="0" required></td><td><input class="form-control" name="availability_notes[' . $idx . ']" placeholder="Brand and stock availability"></td></tr>';
}
echo '</tbody></table></div><h4>Commercial quotation details</h4><div class="row"><div class="col-md-4"><label>Supplier quotation reference</label><input class="form-control" name="quote_reference" maxlength="80"></div><div class="col-md-4"><label>Quotation valid until</label><input class="form-control" type="date" name="validity_date"></div><div class="col-md-4"><label>Delivery lead time (days)</label><input class="form-control" type="number" min="0" name="delivery_days"></div></div>';
echo '<div class="form-group" style="margin-top:12px"><label>Are the unit prices inclusive of tax?</label><div><label class="radio-inline"><input type="radio" name="tax_inclusive" value="1" required> Yes, prices include tax</label> <label class="radio-inline"><input type="radio" name="tax_inclusive" value="0" required checked> No, tax is additional</label></div></div>';
echo '<div class="form-group"><label>Payment terms offered</label><textarea class="form-control" name="payment_terms" rows="2"></textarea></div><div class="form-group"><label>Additional notes / deviations / exclusions</label><textarea class="form-control" name="supplier_notes" rows="3"></textarea></div>';
echo '<div class="checkbox"><label><input type="checkbox" name="accept_terms" value="1" required> I confirm that I have reviewed and accept the RFQ requirements and terms and conditions, and that the prices and information submitted are accurate.</label></div>';
echo '<div class="alert alert-info">Important: You can submit this quotation only once. Review all prices, tax rates, delivery information and terms before final submission.</div><button class="btn btn-primary btn-lg" type="submit" name="submit_quote" value="1" onclick="return confirm(\'Submit this quotation now? It cannot be edited or submitted again.\')">Submit Final Quotation</button></form>';
echo '</div></div></div></body></html>';
?>