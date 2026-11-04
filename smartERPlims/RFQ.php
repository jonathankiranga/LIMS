<?php
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$Title = 'Request for Quotation (RFQ)';
include('includes/header.inc');

function rfq_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function rfq_post($key, $default = '') {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}
function rfq_send_invitation($email, $supplier, $rfqNo, $title, $url) {
    $attempts = array();
    if (!file_exists('chats/EmailConfig.php') || !file_exists('Mailer/PHPMailerAutoload.php')) {
        return array('success' => false, 'attempts' => array(array('mode' => 'configuration', 'error' => 'Email configuration or PHPMailer files are missing.')));
    }
    include_once('includes/encrypt.inc');
    include_once('chats/EmailConfig.php');
    include_once('Mailer/PHPMailerAutoload.php');

    $transports = array(
        array('mode' => 'SSL', 'secure' => 'ssl', 'port' => 465),
        array('mode' => 'TLS', 'secure' => 'tls', 'port' => 587)
    );
    foreach ($transports as $transport) {
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host = HOST;
        $mail->Port = $transport['port'];
        $mail->SMTPSecure = $transport['secure'];
        $mail->SMTPAuth = true;
        $mail->Username = username;
        $mail->Password = trim(decrypt(password));
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(username, 'Smarternow ERP - Procurement');
        $mail->addAddress($email, $supplier);
        $mail->Subject = 'Request for Quotation ' . $rfqNo;
        $mail->isHTML(true);
        $mail->Body = '<p>Dear ' . rfq_h($supplier) . ',</p><p>You are invited to submit a quotation for <strong>' . rfq_h($title) . '</strong> (' . rfq_h($rfqNo) . ').</p><p>Please review the specifications and terms in the supplier portal and submit your quotation before the stated deadline.</p><p><a href="' . rfq_h($url) . '">Open RFQ and submit quotation</a></p><p>This invitation permits one final submission. Please ensure your prices and terms are correct before submitting.</p>';
        $mail->AltBody = 'You are invited to submit a quotation for ' . $title . ' (' . $rfqNo . '). Open: ' . $url;

        if ($mail->send()) {
            $attempts[] = array('mode' => $transport['mode'], 'error' => '');
            return array('success' => true, 'attempts' => $attempts);
        }
        $reason = trim((string)$mail->ErrorInfo);
        if ($reason === '') { $reason = 'PHPMailer returned false without an error message.'; }
        $attempts[] = array('mode' => $transport['mode'], 'error' => $reason);
        error_log('RFQ email failed via ' . $transport['mode'] . ' to ' . $email . ': ' . $reason);
    }
    return array('success' => false, 'attempts' => $attempts);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_rfq'])) {
    if (!isset($_POST['FormID']) || !hash_equals((string)$_SESSION['FormID'], (string)$_POST['FormID'])) {
        $error = 'Your session validation has expired. Reload the page and try again.';
    }
    $title = rfq_post('title');
    $description = rfq_post('description');
    $terms = rfq_post('terms_conditions');
    $currency = rfq_post('currencycode');
    $closing = rfq_post('closing_at');
    $lineDescriptions = isset($_POST['line_description']) ? $_POST['line_description'] : array();
    $lineSpecs = isset($_POST['line_specification']) ? $_POST['line_specification'] : array();
    $lineQty = isset($_POST['line_quantity']) ? $_POST['line_quantity'] : array();
    $lineUnits = isset($_POST['line_unit']) ? $_POST['line_unit'] : array();
    $lineCodes = isset($_POST['line_itemcode']) ? $_POST['line_itemcode'] : array();

    if ($error === '' && ($title === '' || $terms === '' || $currency === '' || $closing === '' || count($lineDescriptions) === 0)) {
        $error = 'Complete the RFQ title, terms and conditions, currency, closing date, and at least one line.';
    } elseif ($error === '' && strtotime($closing) <= time()) {
        $error = 'The quotation closing date and time must be in the future.';
    } elseif ($error === '') {
        $validLines = array();
        foreach ($lineDescriptions as $i => $descriptionLine) {
            $descriptionLine = trim($descriptionLine);
            $qty = isset($lineQty[$i]) ? (float)$lineQty[$i] : 0;
            if ($descriptionLine !== '' && $qty > 0) {
                $validLines[] = array(
                    'description' => $descriptionLine,
                    'specification' => isset($lineSpecs[$i]) ? trim($lineSpecs[$i]) : '',
                    'quantity' => $qty,
                    'unit' => isset($lineUnits[$i]) ? trim($lineUnits[$i]) : '',
                    'itemcode' => isset($lineCodes[$i]) ? trim($lineCodes[$i]) : ''
                );
            }
        }
        if (count($validLines) === 0) {
            $error = 'At least one RFQ line must have a description and a quantity greater than zero.';
        } else {
            $rfqNo = 'RFQ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $sql = "INSERT INTO rfq_header (rfq_no,title,description,terms_conditions,currencycode,closing_at,status,created_by)
                    VALUES ('" . DB_escape_string($rfqNo) . "','" . DB_escape_string($title) . "','" .
                    DB_escape_string($description) . "','" . DB_escape_string($terms) . "','" .
                    DB_escape_string($currency) . "','" . DB_escape_string(str_replace('T', ' ', $closing)) .
                    ":00','draft','" . DB_escape_string(isset($_SESSION['UsersRealName']) ? $_SESSION['UsersRealName'] : $_SESSION['UserID']) . "')";
            DB_query($sql, $db);
            $newRfqResult = DB_query("SELECT id FROM rfq_header WHERE rfq_no='" . DB_escape_string($rfqNo) . "' LIMIT 1", $db);
            $newRfqRow = DB_fetch_array($newRfqResult);
            $rfqId = (int)$newRfqRow['id'];
            foreach ($validLines as $i => $line) {
                DB_query("INSERT INTO rfq_lines (rfq_id,itemcode,description,specification,quantity,unit,line_no)
                    VALUES (" . (int)$rfqId . ",'" . DB_escape_string($line['itemcode']) . "','" .
                    DB_escape_string($line['description']) . "','" . DB_escape_string($line['specification']) . "'," .
                    $line['quantity'] . ",'" . DB_escape_string($line['unit']) . "'," . ($i + 1) . ")", $db);
            }
            $message = 'RFQ ' . $rfqNo . ' created as a draft. Select suppliers and issue it when ready.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_rfq'])) {
    if (!isset($_POST['FormID']) || !hash_equals((string)$_SESSION['FormID'], (string)$_POST['FormID'])) {
        $error = 'Your session validation has expired. Reload the page and try again.';
    }
    $rfqId = (int)$_POST['rfq_id'];
    $selected = isset($_POST['supplier_codes']) && is_array($_POST['supplier_codes']) ? array_unique($_POST['supplier_codes']) : array();
    $rfqResult = DB_query("SELECT * FROM rfq_header WHERE id=" . $rfqId . " AND status='draft'", $db);
    if ($error !== '' || DB_num_rows($rfqResult) !== 1 || count($selected) === 0) {
        $error = 'Select at least one supplier and a valid draft RFQ.';
    } else {
        $rfq = DB_fetch_array($rfqResult);
        $invitesCreated = 0;
        $emailsSent = 0;
        $mailErrors = array();
        foreach ($selected as $supplierCode) {
            $supplierCode = DB_escape_string($supplierCode);
            $supplierResult = DB_query("SELECT itemcode,customer,email FROM creditors WHERE itemcode='" . $supplierCode . "' AND inactive=0", $db);
            if (DB_num_rows($supplierResult) !== 1) { continue; }
            $supplier = DB_fetch_array($supplierResult);
            if (!filter_var($supplier['email'], FILTER_VALIDATE_EMAIL)) { continue; }
            $token = bin2hex(random_bytes(32));
            DB_query("INSERT IGNORE INTO rfq_invites (rfq_id,supplier_code,supplier_email,token_hash)
                VALUES (" . $rfqId . ",'" . DB_escape_string($supplier['itemcode']) . "','" .
                DB_escape_string($supplier['email']) . "','" . hash('sha256', $token) . "')", $db);
            if (mysqli_affected_rows($db) !== 1) { continue; }
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            if (!preg_match('/^[a-zA-Z0-9.-]+(?::[0-9]{1,5})?$/', $host)) { $mailErrors[] = 'Invalid web host; email skipped for ' . $supplier['customer']; continue; }
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $url = 'https://' . $host . $basePath . '/SupplierRFQ.php?company=' . rawurlencode($_SESSION['DatabaseName']) . '&token=' . rawurlencode($token);
            $invitesCreated++;
            $sendResult = rfq_send_invitation($supplier['email'], $supplier['customer'], $rfq['rfq_no'], $rfq['title'], $url);
            foreach ($sendResult['attempts'] as $attempt) {
                DB_query("INSERT INTO rfq_email_log (rfq_id,supplier_code,supplier_email,transport,success,error_message)
                    VALUES (" . $rfqId . ",'" . DB_escape_string($supplier['itemcode']) . "','" .
                    DB_escape_string($supplier['email']) . "','" . DB_escape_string($attempt['mode']) . "'," .
                    (!empty($sendResult['success']) && $attempt['error'] === '' ? 1 : 0) . ",'" .
                    DB_escape_string($attempt['error']) . "')", $db);
            }
            if (!empty($sendResult['success'])) {
                $emailsSent++;
            } else {
                $reasons = array();
                foreach ($sendResult['attempts'] as $attempt) {
                    $reasons[] = $attempt['mode'] . ': ' . $attempt['error'];
                }
                $mailErrors[] = $supplier['customer'] . ' (' . $supplier['email'] . '): ' . implode(' | ', $reasons);
            }
        }
        if ($invitesCreated > 0) {
            DB_query("UPDATE rfq_header SET status='issued',issue_date=NOW() WHERE id=" . $rfqId, $db);
            $message = 'RFQ invitations created for ' . $invitesCreated . ' supplier(s); SMTP accepted ' . $emailsSent . ' email(s).';
        } else {
            $error = 'No supplier invitations were created. Check supplier email addresses and whether they were already invited.';
        }
        if (count($mailErrors)) { $error .= ($error ? ' ' : '') . 'Email sending failed: ' . implode(' | ', $mailErrors); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award_quote'])) {
    if (!isset($_POST['FormID']) || !hash_equals((string)$_SESSION['FormID'], (string)$_POST['FormID'])) {
        $error = 'Your session validation has expired. Reload the page and try again.';
    }
    $rfqId = (int)$_POST['rfq_id'];
    $quoteId = (int)$_POST['quote_id'];
    $qr = DB_query("SELECT q.id,q.supplier_code,q.rfq_id,h.status FROM rfq_quotes q
        JOIN rfq_header h ON h.id=q.rfq_id WHERE q.id=" . $quoteId . " AND q.rfq_id=" . $rfqId, $db);
    if ($error !== '' || DB_num_rows($qr) !== 1) {
        $error = 'The selected quotation does not belong to this RFQ.';
    } else {
        $selectedQuote = DB_fetch_array($qr);
        if (!in_array($selectedQuote['status'], array('issued','closed'), true)) {
            $error = 'This RFQ cannot be awarded in its current status.';
        } else {
            DB_query("UPDATE rfq_header SET status='awarded',awarded_supplier_code='" .
                DB_escape_string($selectedQuote['supplier_code']) . "',awarded_quote_id=" . $quoteId .
                " WHERE id=" . $rfqId . " AND status IN ('issued','closed')", $db);
            if (mysqli_affected_rows($db) === 1) {
                $message = 'Quotation selected. RFQ award recorded against supplier ' . $selectedQuote['supplier_code'] . '. PO conversion is the next integration step.';
            } else {
                $error = 'The RFQ has already been awarded or its status changed. Refresh and review it.';
            }
        }
    }
}

$currencies = DB_query("SELECT currabrev,currency FROM currencies ORDER BY currency", $db);
$stockItems = DB_query("SELECT itemcode,descrip FROM stockmaster WHERE (inactive=0 OR inactive IS NULL) AND COALESCE(is_test_parameter,0)=0 AND COALESCE(is_standard_method,0)=0 ORDER BY descrip LIMIT 500", $db);
$rfqs = DB_query("SELECT h.*, (SELECT COUNT(*) FROM rfq_invites i WHERE i.rfq_id=h.id) AS invite_count,
    (SELECT COUNT(*) FROM rfq_quotes q WHERE q.rfq_id=h.id) AS quote_count
    FROM rfq_header h ORDER BY h.created_at DESC LIMIT 100", $db);
$suppliers = DB_query("SELECT itemcode,customer,email FROM creditors WHERE inactive=0 ORDER BY customer", $db);

echo '<style>
.rfq-page{max-width:1180px;margin:0 auto;padding:0 12px}
.rfq-page .rfq-card{border:1px solid #dbe2ea;border-radius:8px;background:#fff;margin:14px 0;overflow:hidden}
.rfq-page .rfq-card-heading{padding:12px 16px;background:#f5f7fa;border-bottom:1px solid #dbe2ea;font-weight:600}
.rfq-page .rfq-card-body{padding:16px}
.rfq-page .rfq-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px 16px}
.rfq-page .rfq-field{min-width:0;display:flex;flex-direction:column;gap:5px}
.rfq-page .rfq-field label{font-size:13px;font-weight:600;margin:0}
.rfq-page .form-control{width:100%;max-width:100%;box-sizing:border-box}
.rfq-page textarea{resize:vertical}
.rfq-page .rfq-lines th{font-size:12px;white-space:normal}
.rfq-page .rfq-lines td{vertical-align:top}
.rfq-page .rfq-lines input,.rfq-page .rfq-lines select,.rfq-page .rfq-lines textarea{min-width:0}
.rfq-page .rfq-lines .rfq-description{min-width:160px}
.rfq-page .rfq-lines .rfq-specification{min-width:180px}
.rfq-page .rfq-register{font-size:13px}
@media(max-width:768px){.rfq-page{padding:0 8px}.rfq-page .rfq-card-body{padding:12px}.rfq-page .rfq-form-grid{grid-template-columns:1fr}}
</style><div class="rfq-page">';
echo '<h2>Request for Quotation (RFQ)</h2>';
if ($message !== '') { echo '<div class="alert alert-success">' . rfq_h($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-warning">' . rfq_h($error) . '</div>'; }
echo '<section class="rfq-card"><div class="rfq-card-heading">Create RFQ</div><div class="rfq-card-body">';
echo '<form method="post"><input type="hidden" name="FormID" value="' . rfq_h($_SESSION['FormID']) . '">';
echo '<div class="rfq-form-grid"><div class="rfq-field"><label>RFQ title</label><input class="form-control" name="title" required maxlength="150"></div>';
echo '<div class="rfq-field"><label>Currency</label><select class="form-control" name="currencycode" required>';
while ($c = DB_fetch_array($currencies)) { echo '<option value="' . rfq_h($c['currabrev']) . '">' . rfq_h($c['currency']) . ' (' . rfq_h($c['currabrev']) . ')</option>'; }
echo '</select></div><div class="rfq-field"><label>Quotation closing date/time</label><input class="form-control" type="datetime-local" name="closing_at" required></div>';
echo '<div class="rfq-field"><label>Purpose / description</label><textarea class="form-control" name="description" rows="2"></textarea></div></div>';
echo '<h4>Requested products / reagents</h4><div class="table-responsive"><table class="table table-bordered table-sm rfq-lines" id="rfqLines"><thead><tr><th>Inventory item (optional)</th><th>Description</th><th>Technical specifications / grade / pack size</th><th>Quantity</th><th>Unit</th><th></th></tr></thead><tbody>';
echo '<tr><td><select class="form-control" name="line_itemcode[]"><option value="">Not linked</option>';
while ($st = DB_fetch_array($stockItems)) { echo '<option value="' . rfq_h($st['itemcode']) . '">' . rfq_h($st['itemcode'] . ' - ' . $st['descrip']) . '</option>'; }
echo '</select></td><td><input class="form-control rfq-description" name="line_description[]" required></td><td><textarea class="form-control rfq-specification" name="line_specification[]" rows="2"></textarea></td><td><input class="form-control" style="width:100px" type="number" min="0.0001" step="0.0001" name="line_quantity[]" required></td><td><input class="form-control" style="width:100px" name="line_unit[]" placeholder="e.g. bottle, kg"></td><td><button type="button" class="btn btn-default" onclick="this.closest(\'tr\').remove()">Remove</button></td></tr>';
echo '</tbody></table></div><button type="button" class="btn btn-default" id="addRfqLine">Add product line</button>';
echo '<div class="rfq-field" style="margin:16px 0"><label>Terms and conditions (technical, quality, delivery, payment, packaging, shelf life, certificates, MSDS/SDS, returns, etc.)</label><textarea class="form-control" name="terms_conditions" rows="5" required></textarea></div>';
echo '<button class="btn btn-primary" name="create_rfq" value="1">Save RFQ as Draft</button></form></div></section>';

echo '<section class="rfq-card"><div class="rfq-card-heading">RFQ Register</div><div class="rfq-card-body"><div class="table-responsive"><table class="table table-striped table-bordered table-sm rfq-register"><thead><tr><th>RFQ No.</th><th>Title</th><th>Closing</th><th>Status</th><th>Invited</th><th>Quotes</th><th>Action</th></tr></thead><tbody>';
while ($h = DB_fetch_array($rfqs)) {
    echo '<tr><td>' . rfq_h($h['rfq_no']) . '</td><td>' . rfq_h($h['title']) . '</td><td>' . rfq_h($h['closing_at']) . '</td><td>' . rfq_h(ucfirst($h['status'])) . '</td><td>' . (int)$h['invite_count'] . '</td><td>' . (int)$h['quote_count'] . '</td><td>';
    if ($h['status'] === 'draft') {
        echo '<button type="button" class="btn btn-sm btn-primary" data-toggle="collapse" data-target="#issue' . (int)$h['id'] . '">Select suppliers / Issue</button>';
    } else {
        echo '<a class="btn btn-sm btn-default" href="RFQ.php?compare=' . (int)$h['id'] . '">Compare quotations</a>';
    }
    echo '</td></tr>';
    if ($h['status'] === 'draft') {
        echo '<tr class="collapse" id="issue' . (int)$h['id'] . '"><td colspan="7"><form method="post"><input type="hidden" name="FormID" value="' . rfq_h($_SESSION['FormID']) . '"><input type="hidden" name="rfq_id" value="' . (int)$h['id'] . '"><strong>Invite suppliers</strong><div class="row">';
        $sr = DB_query("SELECT itemcode,customer,email FROM creditors WHERE inactive=0 ORDER BY customer", $db);
        while ($sp = DB_fetch_array($sr)) {
            echo '<div class="col-md-4"><label><input type="checkbox" name="supplier_codes[]" value="' . rfq_h($sp['itemcode']) . '"> ' . rfq_h($sp['customer']) . ' <small>(' . rfq_h($sp['email']) . ')</small></label></div>';
        }
        echo '</div><button class="btn btn-success" name="issue_rfq" value="1">Issue RFQ and email invitations</button></form></td></tr>';
    }
}
echo '</tbody></table></div></div></section>';

if (isset($_GET['compare'])) {
    $compareId = (int)$_GET['compare'];
    $hr = DB_query("SELECT * FROM rfq_header WHERE id=" . $compareId, $db);
    if (DB_num_rows($hr) === 1) {
        $head = DB_fetch_array($hr);
        echo '<h3>Quotation comparison — ' . rfq_h($head['rfq_no']) . '</h3>';
        if ($head['status'] === 'awarded') {
            echo '<div class="alert alert-success">Awarded to supplier ' . rfq_h($head['awarded_supplier_code']) . '. Selected quotation ID: ' . (int)$head['awarded_quote_id'] . '.</div>';
            if (empty($head['po_documentno'])) {
                echo '<p><a class="btn btn-primary" href="PurchaseOder.php?rfq_quote_id=' . (int)$head['awarded_quote_id'] . '">Create Purchase Order from awarded quotation</a></p>';
            } else {
                echo '<p class="alert alert-info">Purchase Order created: ' . rfq_h($head['po_documentno']) . '</p>';
            }
        }
        $lines = DB_query("SELECT * FROM rfq_lines WHERE rfq_id=" . $compareId . " ORDER BY line_no", $db);
        $quotes = DB_query("SELECT q.*,c.customer FROM rfq_quotes q JOIN creditors c ON c.itemcode COLLATE utf8mb4_unicode_ci = q.supplier_code COLLATE utf8mb4_unicode_ci WHERE q.rfq_id=" . $compareId . " ORDER BY c.customer", $db);
        echo '<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Supplier / product</th>';
        $quoteRows = array();
        while ($q = DB_fetch_array($quotes)) {
            $quoteRows[] = $q;
            echo '<th>' . rfq_h($q['customer']) . '<br><small>' . rfq_h($q['quote_reference']) . '</small>';
            if ($head['status'] !== 'awarded') {
                echo '<form method="post" style="margin-top:6px"><input type="hidden" name="FormID" value="' . rfq_h($_SESSION['FormID']) . '"><input type="hidden" name="rfq_id" value="' . $compareId . '"><input type="hidden" name="quote_id" value="' . (int)$q['id'] . '"><button class="btn btn-xs btn-success" name="award_quote" value="1" onclick="return confirm(\'Select this supplier quotation?\')">Select supplier</button></form>';
            }
            echo '</th>';
        }
        echo '</tr></thead><tbody>';
        $quoteTotals = array_fill(0, count($quoteRows), 0);
        while ($line = DB_fetch_array($lines)) {
            echo '<tr><th>' . rfq_h($line['description']) . '<br><small>Qty: ' . rfq_h($line['quantity']) . ' ' . rfq_h($line['unit']) . '</small></th>';
            foreach ($quoteRows as $quoteIndex => $q) {
                $qr = DB_query("SELECT unit_price,tax_rate,availability_notes FROM rfq_quote_lines WHERE quote_id=" . (int)$q['id'] . " AND rfq_line_id=" . (int)$line['id'], $db);
                if (DB_num_rows($qr)) {
                    $v = DB_fetch_array($qr);
                    $lineTotal = (float)$line['quantity'] * (float)$v['unit_price'];
                    if (empty($q['tax_inclusive'])) { $lineTotal *= (1 + ((float)$v['tax_rate'] / 100)); }
                    $quoteTotals[$quoteIndex] += $lineTotal;
                    echo '<td>' . rfq_h($head['currencycode']) . ' ' . number_format((float)$v['unit_price'], 4) .
                        '<br><small>Tax: ' . number_format((float)$v['tax_rate'], 2) . '%<br>' . rfq_h($v['availability_notes']) . '</small></td>';
                } else { echo '<td>—</td>'; }
            }
            echo '</tr>';
        }
        echo '<tr><th>Indicative quotation total (' . 'quoted tax treatment applied' . ')</th>';
        foreach ($quoteRows as $quoteIndex => $q) {
            echo '<td><strong>' . rfq_h($head['currencycode']) . ' ' . number_format($quoteTotals[$quoteIndex], 2) . '</strong></td>';
        }
        echo '</tr></tbody></table></div>';

        echo '<h4>Commercial terms comparison</h4><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Term</th>';
        foreach ($quoteRows as $q) { echo '<th>' . rfq_h($q['customer']) . '</th>'; }
        echo '</tr></thead><tbody>';
        $commercialFields = array(
            'Tax treatment' => function($q) { return !empty($q['tax_inclusive']) ? 'Prices inclusive of tax' : 'Tax additional'; },
            'Quotation validity' => function($q) { return $q['validity_date'] ? $q['validity_date'] : 'Not specified'; },
            'Delivery lead time' => function($q) { return $q['delivery_days'] !== null ? $q['delivery_days'] . ' days' : 'Not specified'; },
            'Payment terms' => function($q) { return $q['payment_terms'] !== '' ? $q['payment_terms'] : 'Not specified'; },
            'Supplier notes / deviations' => function($q) { return $q['supplier_notes'] !== '' ? $q['supplier_notes'] : 'None stated'; }
        );
        foreach ($commercialFields as $label => $valueFunction) {
            echo '<tr><th>' . rfq_h($label) . '</th>';
            foreach ($quoteRows as $q) { echo '<td>' . nl2br(rfq_h($valueFunction($q))) . '</td>'; }
            echo '</tr>';
        }
        echo '</tbody></table></div><p><strong>Indicative totals</strong> use each supplier’s declared tax-inclusive or tax-exclusive pricing treatment. Confirm freight and any other excluded charges before awarding.</p>';
    }
}
echo '</div>';
?>
<script>
(function(){
 var add=document.getElementById('addRfqLine');
 if(add){add.addEventListener('click',function(){
  var body=document.querySelector('#rfqLines tbody');
  var row=body.querySelector('tr').cloneNode(true);
  row.querySelectorAll('input,textarea,select').forEach(function(el){el.value='';});
  body.appendChild(row);
 });}
})();
</script>
<?php include('includes/footer.inc'); ?>