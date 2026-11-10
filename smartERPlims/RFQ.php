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

    $mailerPath = __DIR__ . '/Mailer/PHPMailerAutoload.php';
    $smtpConfigPath = __DIR__ . '/chats/EmailConfig.php';

    if (!is_file($mailerPath) || !is_file($smtpConfigPath)) {
        return array('success' => false, 'attempts' => array(
            array('mode' => 'configuration', 'error' => 'The SmartERPlims mailer or SMTP configuration could not be found.')
        ));
    }

    require_once $mailerPath;
    require_once $smtpConfigPath;

    if (!defined('HOST') || !defined('PORT') || !defined('username') ||
        !defined('password') || !defined('SECURE')) {
        return array('success' => false, 'attempts' => array(
            array('mode' => 'configuration', 'error' => 'The SmartERPlims SMTP configuration is incomplete.')
        ));
    }

    $mail = new PHPMailer(true);
    $port = (int)PORT;
    $secure = strtolower(trim((string)SECURE));
    $transport = strtoupper($secure) . ':' . $port;

    try {
        // SmartERPlims uses its own PHPMailer 7.1.1 installation and SMTP configuration.
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host = trim((string)HOST);
        $mail->Port = $port > 0 ? $port : 587;
        $mail->SMTPAuth = (string)auth === '1' || (string)auth === 'true';
        $mail->Username = trim((string)username);
        $mail->Password = (string)password;
        $mail->Helo = defined('heloaddress') && trim((string)heloaddress) !== ''
            ? trim((string)heloaddress)
            : $mail->Host;
        $mail->Timeout = defined('timeout') && (int)timeout > 0 ? (int)timeout : 20;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        if ($secure === 'ssl' || $mail->Port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'tls' || $mail->Port === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $fromEmail = filter_var($mail->Username, FILTER_VALIDATE_EMAIL) ? $mail->Username : '';
        if ($fromEmail === '') {
            throw new RuntimeException('The configured SMTP sender address is invalid.');
        }

        $companyName = isset($_SESSION['CompanyRecord']['coyname']) ? trim((string)$_SESSION['CompanyRecord']['coyname']) : '';
        $mail->setFrom($fromEmail, $companyName);
        $mail->addAddress($email, $supplier);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = 'Request for Quotation ' . $rfqNo;
        $mail->Body = '<p>Dear ' . rfq_h($supplier) . ',</p><p>You are invited to submit a quotation for <strong>' . rfq_h($title) . '</strong> (' . rfq_h($rfqNo) . ').</p><p>Please review the specifications and terms in the supplier portal and submit your quotation before the stated deadline.</p><p><a href="' . rfq_h($url) . '">Open RFQ and submit quotation</a></p><p>This invitation permits one final submission. Please ensure your prices and terms are correct before submitting.</p>';
        $mail->AltBody = 'You are invited to submit a quotation for ' . $title . ' (' . $rfqNo . '). Open: ' . $url;

        $mail->send();
        $attempts[] = array('mode' => $transport, 'error' => '');
        return array('success' => true, 'attempts' => $attempts);
    } catch (Throwable $exception) {
        $reason = trim((string)$mail->ErrorInfo);
        if ($reason === '') {
            $reason = $exception->getMessage();
        }
        if ($reason === '') {
            $reason = 'PHPMailer could not send the message.';
        }
        $attempts[] = array('mode' => $transport, 'error' => $reason);
        error_log('RFQ email failed via ' . $transport . ' to ' . $email . ': ' . $reason);
        return array('success' => false, 'attempts' => $attempts);
    }
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
                $itemCode = isset($lineCodes[$i]) ? trim($lineCodes[$i]) : '';
                $requestedUnit = isset($lineUnits[$i]) ? trim($lineUnits[$i]) : '';
                if ($itemCode === '' || $requestedUnit === '') {
                    $error = 'Select an inventory item and one of its configured Pack / Each options for RFQ line ' . ((int)$i + 1) . '.';
                    break;
                }
                $itemCheck = DB_query("SELECT itemcode,units,subunits,partperunit FROM stockmaster
                    WHERE itemcode='" . DB_escape_string($itemCode) . "'
                      AND (inactive=0 OR inactive IS NULL)
                      AND COALESCE(is_test_parameter,0)=0 AND COALESCE(is_standard_method,0)=0 LIMIT 1", $db);
                if (DB_num_rows($itemCheck) !== 1) {
                    $error = 'The selected inventory item is not available for RFQ line ' . ((int)$i + 1) . '.';
                    break;
                }
                $itemMapping = DB_fetch_array($itemCheck);
                $fullUnit = trim((string)$itemMapping['units']);
                $looseUnit = trim((string)$itemMapping['subunits']);
                if ($requestedUnit !== $fullUnit && ($looseUnit === '' || $requestedUnit !== $looseUnit)) {
                    $error = 'The selected unit is not configured as the Pack or Each for inventory item ' . $itemCode . '.';
                    break;
                }
                $validLines[] = array(
                    'description' => $descriptionLine,
                    'specification' => isset($lineSpecs[$i]) ? trim($lineSpecs[$i]) : '',
                    'quantity' => $qty,
                    'requested_pack' => $requestedUnit,
                    'itemcode' => $itemCode
                );
            }
        }
        if ($error !== '') {
            // Do not save an RFQ with an unspecified supplier pack.
        } elseif (count($validLines) === 0) {
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
                DB_query("INSERT INTO rfq_lines (rfq_id,itemcode,description,specification,quantity,requested_pack,line_no)
                    VALUES (" . (int)$rfqId . ",'" . DB_escape_string($line['itemcode']) . "','" .
                    DB_escape_string($line['description']) . "','" . DB_escape_string($line['specification']) . "'," .
                    $line['quantity'] . ",'" . DB_escape_string($line['requested_pack']) . "'," . ($i + 1) . ")", $db);
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
        if ($emailsSent > 0) {
            DB_query("UPDATE rfq_header SET status='issued',issue_date=IFNULL(issue_date,NOW()) WHERE id=" . $rfqId . " AND status='draft'", $db);
            $message = 'RFQ invitations created for ' . $invitesCreated . ' supplier(s); SMTP accepted ' . $emailsSent . ' email(s).';
        } elseif ($invitesCreated > 0) {
            $error = 'Supplier invitations were created, but no email was accepted by SMTP. The RFQ remains Draft. Use Retry failed emails after correcting the mail configuration.';
        } else {
            $error = 'No supplier invitations were created. Check supplier email addresses and whether they were already invited.';
        }
        if (count($mailErrors)) { $error .= ($error ? ' ' : '') . 'Email sending failed: ' . implode(' | ', $mailErrors); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retry_rfq_emails'])) {
    if (!isset($_POST['FormID']) || !hash_equals((string)$_SESSION['FormID'], (string)$_POST['FormID'])) {
        $error = 'Your session validation has expired. Reload the page and try again.';
    } else {
        $rfqId = isset($_POST['rfq_id']) ? (int)$_POST['rfq_id'] : 0;
        $rfqResult = DB_query("SELECT * FROM rfq_header WHERE id=" . $rfqId . " AND status IN ('draft','issued')", $db);
        if (DB_num_rows($rfqResult) !== 1) {
            $error = 'The RFQ does not exist or cannot accept email retries in its current status.';
        } else {
            $rfq = DB_fetch_array($rfqResult);
            $failedInvites = DB_query("SELECT i.id,i.supplier_code,i.supplier_email
                FROM rfq_invites i
                WHERE i.rfq_id=" . $rfqId . " AND i.status='invited'
                  AND NOT EXISTS (
                    SELECT 1 FROM rfq_email_log l
                    WHERE l.rfq_id=i.rfq_id AND l.supplier_code=i.supplier_code AND l.success=1
                  )
                ORDER BY i.id", $db);
            $retried = 0;
            $emailsSent = 0;
            $mailErrors = array();
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $validHost = preg_match('/^[a-zA-Z0-9.-]+(?::[0-9]{1,5})?$/', $host);
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            while ($invite = DB_fetch_array($failedInvites)) {
                $retried++;
                $supplierName = $invite['supplier_code'];
                $supplierLookup = DB_query("SELECT customer FROM creditors WHERE itemcode COLLATE utf8mb4_unicode_ci='" .
                    DB_escape_string($invite['supplier_code']) . "' COLLATE utf8mb4_unicode_ci LIMIT 1", $db);
                if (DB_num_rows($supplierLookup) === 1) {
                    $supplierRow = DB_fetch_array($supplierLookup);
                    $supplierName = $supplierRow['customer'];
                }
                if (!filter_var($invite['supplier_email'], FILTER_VALIDATE_EMAIL)) {
                    $reason = 'Stored supplier email address is invalid: ' . $invite['supplier_email'];
                    DB_query("INSERT INTO rfq_email_log (rfq_id,supplier_code,supplier_email,transport,success,error_message)
                        VALUES (" . $rfqId . ",'" . DB_escape_string($invite['supplier_code']) . "','" .
                        DB_escape_string($invite['supplier_email']) . "','configuration',0,'" . DB_escape_string($reason) . "')", $db);
                    $mailErrors[] = $supplierName . ': ' . $reason;
                    continue;
                }
                if (!$validHost) {
                    $reason = 'Invalid web host; email was not attempted.';
                    DB_query("INSERT INTO rfq_email_log (rfq_id,supplier_code,supplier_email,transport,success,error_message)
                        VALUES (" . $rfqId . ",'" . DB_escape_string($invite['supplier_code']) . "','" .
                        DB_escape_string($invite['supplier_email']) . "','configuration',0,'" . DB_escape_string($reason) . "')", $db);
                    $mailErrors[] = $supplierName . ': ' . $reason;
                    continue;
                }
                $token = bin2hex(random_bytes(32));
                DB_query("UPDATE rfq_invites SET token_hash='" . hash('sha256', $token) .
                    "',supplier_email='" . DB_escape_string($invite['supplier_email']) . "' WHERE id=" . (int)$invite['id'], $db);
                $url = 'https://' . $host . $basePath . '/SupplierRFQ.php?company=' .
                    rawurlencode($_SESSION['DatabaseName']) . '&token=' . rawurlencode($token);
                $sendResult = rfq_send_invitation($invite['supplier_email'], $supplierName, $rfq['rfq_no'], $rfq['title'], $url);
                foreach ($sendResult['attempts'] as $attempt) {
                    DB_query("INSERT INTO rfq_email_log (rfq_id,supplier_code,supplier_email,transport,success,error_message)
                        VALUES (" . $rfqId . ",'" . DB_escape_string($invite['supplier_code']) . "','" .
                        DB_escape_string($invite['supplier_email']) . "','" . DB_escape_string($attempt['mode']) . "'," .
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
                    $mailErrors[] = $supplierName . ' (' . $invite['supplier_email'] . '): ' . implode(' | ', $reasons);
                }
            }
            if ($emailsSent > 0) {
                DB_query("UPDATE rfq_header SET status='issued',issue_date=IFNULL(issue_date,NOW()) WHERE id=" . $rfqId . " AND status='draft'", $db);
                $message = 'Retried ' . $retried . ' failed invitation(s); SMTP accepted ' . $emailsSent . ' email(s).';
            } elseif ($retried === 0) {
                $message = 'There are no failed or pending invitation emails to retry.';
            } else {
                $error = 'Retry attempted for ' . $retried . ' invitation(s), but SMTP accepted none. The RFQ remains ' .
                    ucfirst($rfq['status']) . '. Check the email log for the recorded errors.';
            }
            if (count($mailErrors)) { $error .= ($error ? ' ' : '') . 'Email sending failed: ' . implode(' | ', $mailErrors); }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award_quote'])) {
    if (!isset($_POST['FormID']) || !hash_equals((string)$_SESSION['FormID'], (string)$_POST['FormID'])) {
        $error = 'Your session validation has expired. Reload the page and try again.';
    }
    $rfqId = (int)$_POST['rfq_id'];
    $selectedQuoteIds = isset($_POST['quote_choice']) && is_array($_POST['quote_choice'])
        ? array_values(array_unique(array_map('intval', $_POST['quote_choice']))) : array();
    $quoteId = count($selectedQuoteIds) === 1 ? $selectedQuoteIds[0] : 0;
    $qr = DB_query("SELECT q.id,q.supplier_code,q.rfq_id,h.status FROM rfq_quotes q
        JOIN rfq_header h ON h.id=q.rfq_id WHERE q.id=" . $quoteId . " AND q.rfq_id=" . $rfqId, $db);
    if ($error !== '' || count($selectedQuoteIds) !== 1 || $quoteId < 1 || DB_num_rows($qr) !== 1) {
        $error = 'Select exactly one supplier quotation belonging to this RFQ.';
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
$stockItems = DB_query("SELECT sm.itemcode,sm.descrip,sm.units,sm.subunits,sm.partperunit,
    uf.descrip AS full_unit_description,ul.descrip AS loose_unit_description
    FROM stockmaster sm
    LEFT JOIN unit uf ON uf.code=sm.units
    LEFT JOIN unit ul ON ul.code=sm.subunits
    WHERE (sm.inactive=0 OR sm.inactive IS NULL)
      AND COALESCE(sm.is_test_parameter,0)=0 AND COALESCE(sm.is_standard_method,0)=0
    ORDER BY sm.descrip LIMIT 500", $db);
$rfqStockItems = array();
while ($stockItem = DB_fetch_array($stockItems)) { $rfqStockItems[] = $stockItem; }
$rfqs = DB_query("SELECT h.*, (SELECT COUNT(*) FROM rfq_invites i WHERE i.rfq_id=h.id) AS invite_count,
    (SELECT COUNT(*) FROM rfq_quotes q WHERE q.rfq_id=h.id) AS quote_count,
    (SELECT COUNT(*) FROM rfq_invites i WHERE i.rfq_id=h.id AND i.status='invited'
        AND NOT EXISTS (SELECT 1 FROM rfq_email_log l WHERE l.rfq_id=i.rfq_id
            AND l.supplier_code=i.supplier_code AND l.success=1)) AS failed_email_count
    FROM rfq_header h ORDER BY h.created_at DESC LIMIT 100", $db);
$suppliers = DB_query("SELECT itemcode,customer,email FROM creditors WHERE inactive=0 ORDER BY customer", $db);

echo '<style>
.rfq-page{max-width:1500px;margin:0 auto;padding:0 16px;color:#202532}
.rfq-page .rfq-card{border:1px solid #e0e5ed;border-radius:12px;background:#fff;margin:16px 0;overflow:hidden;box-shadow:0 2px 8px rgba(22,34,58,.035)}
.rfq-page .rfq-card-heading{padding:14px 18px;background:#f8f9fc;border-bottom:1px solid #e8ebf1;font-weight:700;color:#252b3a}
.rfq-page .rfq-card-body{padding:18px}
.rfq-page .rfq-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px 18px}
.rfq-page .rfq-field{min-width:0;display:flex;flex-direction:column;gap:6px}
.rfq-page .rfq-field label{font-size:13px;font-weight:650;margin:0;color:#50596a}
.rfq-page .form-control{width:100%;max-width:100%;box-sizing:border-box;border:1px solid #dce2eb;border-radius:7px;box-shadow:none;min-height:38px}
.rfq-page .form-control:focus{border-color:#6478d3;box-shadow:0 0 0 3px rgba(91,111,204,.12)}
.rfq-page textarea{resize:vertical}
.rfq-page .rfq-lines th{font-size:12px;white-space:normal;background:#f8f9fc;color:#4b5362}
.rfq-page .rfq-lines td{vertical-align:top}
.rfq-page .rfq-lines input,.rfq-page .rfq-lines select,.rfq-page .rfq-lines textarea{min-width:0}
.rfq-page .rfq-lines .rfq-description{min-width:160px}
.rfq-page .rfq-lines .rfq-specification{min-width:180px}
.rfq-page .rfq-register{font-size:13px}
.rfq-page .rfq-compare-head{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin:18px 0 20px}
.rfq-page .rfq-compare-title{margin:0;font-size:25px;font-weight:700;color:#202638}
.rfq-page .rfq-compare-subtitle{margin:5px 0 0;color:#7b8494;font-size:13px}
.rfq-page .rfq-status{display:inline-block;border-radius:20px;padding:5px 11px;background:#eef1ff;color:#5365bd;font-size:12px;font-weight:700;text-transform:capitalize}
.rfq-page .rfq-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.rfq-page .rfq-actions .btn,.rfq-page .rfq-card .btn{border-radius:7px;font-weight:600}
.rfq-page .rfq-supplier-board{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:0 0 24px}
.rfq-page .rfq-lane{background:#f7f8fb;border:1px solid #edf0f5;border-radius:10px;padding:14px;min-width:0;min-height:105px}
.rfq-page .rfq-lane-title{font-size:14px;font-weight:700;margin:0 0 12px;color:#454d5d}
.rfq-page .rfq-lane-title .glyphicon{color:#8a93a3;margin-right:7px}
.rfq-page .rfq-supplier-chip{background:#fff;border:1px solid #e3e7ee;border-radius:8px;padding:10px 12px;margin-top:8px;box-shadow:0 1px 3px rgba(20,30,50,.04);font-size:13px}
.rfq-page .rfq-supplier-chip small{display:block;color:#8a93a3;margin-top:4px}
.rfq-page .rfq-quotes-wrap{border:1px solid #dfe4ec;border-radius:10px;overflow:auto;background:#fff}
.rfq-page .rfq-quotes{margin:0;min-width:760px;border:0}
.rfq-page .rfq-quotes>thead>tr>th{background:#fff;border-bottom:1px solid #dfe4ec;border-top:0;padding:14px 13px;vertical-align:middle;white-space:normal}
.rfq-page .rfq-quotes>thead>tr>th:first-child{min-width:235px}
.rfq-page .rfq-quotes>thead>tr>th:not(:first-child){min-width:175px}
.rfq-page .rfq-quotes>tbody>tr>th{background:#f8f9fb;font-weight:650;vertical-align:middle;min-width:235px}
.rfq-page .rfq-quotes>tbody>tr>td{vertical-align:middle;min-width:175px}
.rfq-page .rfq-supplier-head{font-weight:700;color:#272e3c;font-size:13px}
.rfq-page .rfq-supplier-head small{display:block;color:#8992a1;font-weight:400;margin-top:4px}
.rfq-page .rfq-price{font-size:14px;font-weight:600;color:#343b49;white-space:nowrap}
.rfq-page .rfq-cell-meta{display:block;color:#7d8796;font-size:12px;margin-top:7px;line-height:1.55}
.rfq-page .rfq-best{background:#f0f5ff!important;box-shadow:inset 0 0 0 2px #6385e6;border-radius:8px}
.rfq-page .rfq-best .rfq-price{font-weight:800;color:#25375f}
.rfq-page .rfq-total-row th,.rfq-page .rfq-total-row td{background:#f8f9fc;font-weight:700}
.rfq-page .rfq-commercial{min-width:760px;margin-top:18px}
.rfq-page .rfq-commercial th{background:#f8f9fc}
.rfq-page .rfq-legend{font-size:12px;color:#7c8695;margin:10px 0 0}
.rfq-page .rfq-legend span{display:inline-block;width:11px;height:11px;border:2px solid #6385e6;background:#f0f5ff;border-radius:3px;vertical-align:-1px;margin-right:5px}
@media(max-width:768px){.rfq-page{padding:0 8px}.rfq-page .rfq-card-body{padding:12px}.rfq-page .rfq-form-grid{grid-template-columns:1fr}.rfq-page .rfq-supplier-board{grid-template-columns:1fr}.rfq-page .rfq-compare-title{font-size:21px}}

.rfq-workspace{max-width:1600px;margin:0 auto;padding:20px 22px 40px;color:#202a3b}
.rfq-workspace .rfq-topbar{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:4px 0 24px}
.rfq-workspace .rfq-eyebrow{font-size:10px;letter-spacing:1.8px;font-weight:800;color:#77849a;margin-bottom:7px}
.rfq-workspace .rfq-topbar h1{font-size:27px;line-height:1.2;font-weight:750;margin:0;color:#1d293b}
.rfq-workspace .rfq-topbar p{font-size:13px;color:#788498;margin:7px 0 0}
.rfq-workspace .rfq-top-actions{display:flex;gap:10px;align-items:center}
.rfq-workspace .rfq-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
.rfq-workspace .rfq-metric{background:#fff;border:1px solid #e5eaf1;border-radius:12px;padding:16px 18px;box-shadow:0 3px 12px rgba(23,39,64,.035)}
.rfq-workspace .rfq-metric-label{display:block;font-size:10px;letter-spacing:1px;font-weight:800;color:#8490a2}
.rfq-workspace .rfq-metric strong{display:block;font-size:27px;line-height:1.35;color:#26344a;margin-top:6px}
.rfq-workspace .rfq-metric small{font-size:11px;color:#8994a4}
.rfq-workspace .rfq-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid transparent;border-radius:7px;padding:9px 13px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;cursor:pointer}
.rfq-workspace .rfq-btn-primary{background:#315ee8;color:#fff}
.rfq-workspace .rfq-btn-primary:hover{background:#244bc5;color:#fff}
.rfq-workspace .rfq-btn-secondary{background:#fff;color:#34445e;border-color:#dce3ed}
.rfq-workspace .rfq-btn-success{background:#16845b;color:#fff}
.rfq-workspace .rfq-btn-warning{background:#fff8e9;color:#9b6410;border-color:#f1dfb9}
.rfq-workspace .rfq-create-panel,.rfq-workspace .rfq-register-panel{background:#fff;border:1px solid #e4e9f0;border-radius:12px;margin-bottom:20px;box-shadow:0 3px 12px rgba(23,39,64,.035);overflow:hidden}
.rfq-workspace .rfq-create-panel>summary{list-style:none;display:flex;align-items:center;justify-content:space-between;padding:16px 19px;cursor:pointer}
.rfq-workspace .rfq-create-panel>summary::-webkit-details-marker{display:none}
.rfq-workspace .rfq-create-panel>summary strong{display:block;font-size:14px;color:#2a374c}
.rfq-workspace .rfq-create-panel>summary small{display:block;font-size:11px;color:#8490a2;margin-top:4px}
.rfq-workspace .rfq-create-toggle{font-size:23px;color:#526be0}
.rfq-workspace .rfq-create-panel[open]>summary{border-bottom:1px solid #e9edf3;background:#fafbfe}
.rfq-workspace .rfq-create-content{padding:20px}
.rfq-workspace .rfq-register-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:19px 20px;border-bottom:1px solid #e9edf3}
.rfq-workspace .rfq-register-heading h2{font-size:17px;font-weight:750;margin:0;color:#26344a}
.rfq-workspace .rfq-register-heading p{font-size:11px;color:#8490a2;margin:5px 0 0}
.rfq-workspace .rfq-register-tools{display:flex;gap:9px;width:min(470px,55%)}
.rfq-workspace .rfq-register-tools .form-control{height:36px;border:1px solid #dfe5ee;border-radius:7px;box-shadow:none;font-size:12px}
.rfq-workspace .rfq-card-list{padding:4px 20px 18px}
.rfq-workspace .rfq-register-card{border:1px solid #e7ebf1;border-radius:10px;margin-top:14px;background:#fff;overflow:hidden;transition:border-color .15s,box-shadow .15s}
.rfq-workspace .rfq-register-card:hover{border-color:#cbd5e5;box-shadow:0 4px 14px rgba(26,43,70,.055)}
.rfq-workspace .rfq-card-main{display:grid;grid-template-columns:minmax(220px,1.5fr) minmax(150px,.75fr) auto;align-items:center;gap:20px;padding:17px 18px}
.rfq-workspace .rfq-number{font-size:10px;font-weight:800;letter-spacing:.5px;color:#5570d9}
.rfq-workspace .rfq-card-identity h3{font-size:14px;font-weight:750;margin:5px 0;color:#27354a}
.rfq-workspace .rfq-card-date{font-size:11px;color:#8792a2}
.rfq-workspace .rfq-card-status{display:flex;align-items:center;gap:13px;flex-wrap:wrap}
.rfq-workspace .rfq-status-pill{display:inline-block;padding:5px 10px;border-radius:20px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;background:#eef1f5;color:#647084}
.rfq-workspace .rfq-status-pill.draft{background:#fff4df;color:#a16b13}
.rfq-workspace .rfq-status-pill.issued{background:#eaf0ff;color:#4562c8}
.rfq-workspace .rfq-status-pill.closed{background:#edf0f4;color:#59677b}
.rfq-workspace .rfq-status-pill.awarded{background:#e6f6ef;color:#18764f}
.rfq-workspace .rfq-response-counts{display:flex;gap:10px;font-size:10px;color:#8590a0}
.rfq-workspace .rfq-response-counts strong{color:#34445d;font-size:12px}
.rfq-workspace .rfq-card-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px;flex-wrap:wrap}
.rfq-workspace .rfq-inline-action{display:inline-block;margin:0}
.rfq-workspace .rfq-invite-panel{padding:16px 18px;background:#f8faff;border-top:1px solid #e7edf7}
.rfq-workspace .rfq-invite-panel>form>strong{display:block;font-size:12px;margin-bottom:12px;color:#35435a}
.rfq-workspace .rfq-supplier-select-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-bottom:14px}
.rfq-workspace .rfq-supplier-option{display:flex;gap:9px;align-items:flex-start;padding:9px 10px;background:#fff;border:1px solid #e5eaf2;border-radius:7px;font-weight:400}
.rfq-workspace .rfq-supplier-option input{margin-top:3px}
.rfq-workspace .rfq-supplier-option strong{display:block;font-size:11px;color:#34435a}
.rfq-workspace .rfq-supplier-option small{display:block;font-size:10px;color:#8994a4;margin-top:3px;overflow-wrap:anywhere}
.rfq-workspace .rfq-empty,.rfq-workspace .rfq-no-results{padding:36px 20px;text-align:center;color:#7d899a;font-size:12px}
.rfq-workspace .rfq-empty strong{display:block;color:#34435a;font-size:14px;margin-bottom:5px}
.rfq-workspace .rfq-empty p{margin:0}
.rfq-workspace .rfq-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px}
.rfq-workspace .rfq-field{min-width:0;display:flex;flex-direction:column;gap:6px}
.rfq-workspace .rfq-field label{font-size:12px;font-weight:650;margin:0;color:#50596a}
.rfq-workspace .form-control{width:100%;max-width:100%;box-sizing:border-box;border:1px solid #dce2eb;border-radius:7px;box-shadow:none;min-height:38px}
.rfq-workspace .form-control:focus{border-color:#6478d3;box-shadow:0 0 0 3px rgba(91,111,204,.12)}
.rfq-workspace textarea{resize:vertical}
.rfq-workspace .rfq-lines th{font-size:12px;white-space:normal;background:#f8f9fc;color:#4b5362}
.rfq-workspace .rfq-lines td{vertical-align:top}
.rfq-workspace .rfq-lines input,.rfq-workspace .rfq-lines select,.rfq-workspace .rfq-lines textarea{min-width:0}
.rfq-workspace .rfq-lines .rfq-description{min-width:160px}
.rfq-workspace .rfq-lines .rfq-specification{min-width:180px}
@media(max-width:900px){.rfq-workspace .rfq-card-main{grid-template-columns:minmax(0,1fr) auto}.rfq-workspace .rfq-card-status{grid-column:1}.rfq-workspace .rfq-card-actions{grid-column:2;grid-row:1/3}.rfq-workspace .rfq-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:620px){.rfq-workspace{padding:12px 8px 28px}.rfq-workspace .rfq-topbar{align-items:flex-start;flex-direction:column}.rfq-workspace .rfq-topbar h1{font-size:22px}.rfq-workspace .rfq-metric-grid{gap:8px}.rfq-workspace .rfq-metric{padding:12px}.rfq-workspace .rfq-metric strong{font-size:22px}.rfq-workspace .rfq-register-heading{align-items:stretch;flex-direction:column}.rfq-workspace .rfq-register-tools{width:100%}.rfq-workspace .rfq-card-list{padding:0 10px 12px}.rfq-workspace .rfq-card-main{display:flex;align-items:flex-start;flex-direction:column;gap:12px;padding:14px}.rfq-workspace .rfq-card-actions{justify-content:flex-start;width:100%}.rfq-workspace .rfq-supplier-select-grid,.rfq-workspace .rfq-form-grid{grid-template-columns:1fr}.rfq-workspace .rfq-create-content{padding:12px}}

</style><div class="rfq-page">';

// Load the existing RFQ register rows once; all actions below continue to post to
// the existing handlers above and use the existing database records.
$rfqRows = array();
while ($rfqRow = DB_fetch_array($rfqs)) { $rfqRows[] = $rfqRow; }
$rfqCounts = array('draft' => 0, 'issued' => 0, 'closed' => 0, 'awarded' => 0);
foreach ($rfqRows as $rfqRow) {
    if (isset($rfqCounts[$rfqRow['status']])) { $rfqCounts[$rfqRow['status']]++; }
}
$totalRfqs = count($rfqRows);
$openRfqs = $rfqCounts['draft'] + $rfqCounts['issued'];
$receivedQuotes = 0;
foreach ($rfqRows as $rfqRow) { $receivedQuotes += (int)$rfqRow['quote_count']; }

echo '<div class="rfq-workspace">';
echo '<header class="rfq-topbar"><div><div class="rfq-eyebrow">PROCUREMENT WORKSPACE</div><h1>Requests for Quotation</h1><p>Manage supplier invitations, track responses and compare submitted quotations.</p></div><div class="rfq-top-actions"><button type="button" class="rfq-btn rfq-btn-primary" onclick="document.getElementById(\'rfqCreatePanel\').open=true;document.getElementById(\'rfqCreatePanel\').scrollIntoView({behavior:\'smooth\',block:\'start\'});"><span class="glyphicon glyphicon-plus"></span> Create new RFQ</button></div></header>';
if ($message !== '') { echo '<div class="alert alert-success">' . rfq_h($message) . '</div>'; }
if ($error !== '') { echo '<div class="alert alert-warning">' . rfq_h($error) . '</div>'; }

echo '<div class="rfq-metric-grid">';
echo '<div class="rfq-metric"><span class="rfq-metric-label">TOTAL REQUESTS</span><strong>' . $totalRfqs . '</strong><small>All recorded RFQs</small></div>';
echo '<div class="rfq-metric"><span class="rfq-metric-label">ACTIVE</span><strong>' . $openRfqs . '</strong><small>Draft and issued</small></div>';
echo '<div class="rfq-metric"><span class="rfq-metric-label">QUOTATIONS RECEIVED</span><strong>' . $receivedQuotes . '</strong><small>Supplier submissions</small></div>';
echo '<div class="rfq-metric"><span class="rfq-metric-label">AWARDED</span><strong>' . $rfqCounts['awarded'] . '</strong><small>Completed selections</small></div>';
echo '</div>';

echo '<details class="rfq-create-panel" id="rfqCreatePanel"><summary><span><strong>Create a request</strong><small>Define laboratory items, quantities, closing date and supplier terms</small></span><span class="rfq-create-toggle">＋</span></summary><div class="rfq-create-content">';
echo '<form method="post"><input type="hidden" name="FormID" value="' . rfq_h($_SESSION['FormID']) . '">';
echo '<div class="rfq-form-grid"><div class="rfq-field"><label>RFQ title</label><input class="form-control" name="title" required maxlength="150"></div>';
echo '<div class="rfq-field"><label>Currency</label><select class="form-control" name="currencycode" required>';
while ($c = DB_fetch_array($currencies)) { echo '<option value="' . rfq_h($c['currabrev']) . '">' . rfq_h($c['currency']) . ' (' . rfq_h($c['currabrev']) . ')</option>'; }
echo '</select></div><div class="rfq-field"><label>Quotation closing date/time</label><input class="form-control" type="datetime-local" name="closing_at" required></div>';
echo '<div class="rfq-field"><label>Purpose / description</label><textarea class="form-control" name="description" rows="2"></textarea></div></div>';
echo '<h4>Requested laboratory inventory items</h4><p class="text-muted">Select the laboratory inventory item, then choose its configured full or loose unit. The unit conversion is taken from the inventory record; suppliers cannot change the requested unit or conversion.</p><div class="table-responsive"><table class="table table-bordered table-sm rfq-lines" id="rfqLines"><thead><tr><th>Inventory item</th><th>Description</th><th>Laboratory specification / grade</th><th>Quantity</th><th>Requested unit (Full / Loose)</th><th></th></tr></thead><tbody>';
echo '<tr><td><select class="form-control rfq-item-select" name="line_itemcode[]" required><option value="">Select inventory item</option>';
foreach ($rfqStockItems as $st) {
    echo '<option value="' . rfq_h($st['itemcode']) . '" data-full-unit="' . rfq_h($st['units']) . '" data-full-label="' . rfq_h($st['full_unit_description']) . '" data-loose-unit="' . rfq_h($st['subunits']) . '" data-loose-label="' . rfq_h($st['loose_unit_description']) . '" data-factor="' . rfq_h($st['partperunit']) . '" data-description="' . rfq_h($st['descrip']) . '">' . rfq_h($st['itemcode'] . ' - ' . $st['descrip']) . '</option>';
}
echo '</select></td><td><input class="form-control rfq-description" name="line_description[]" required></td><td><textarea class="form-control rfq-specification" name="line_specification[]" rows="2"></textarea></td><td><input class="form-control" style="width:100px" type="number" min="0.0001" step="0.0001" name="line_quantity[]" required></td><td><select class="form-control rfq-unit-select" name="line_unit[]" required disabled><option value="">Select item first</option></select><small class="text-muted rfq-unit-factor"></small></td><td><button type="button" class="btn btn-default" onclick="this.closest(&quot;tr&quot;).remove()">Remove</button></td></tr>';
echo '</tbody></table></div><button type="button" class="btn btn-default" id="addRfqLine">Add product line</button>';
echo '<div class="rfq-field" style="margin:16px 0"><label>Laboratory requirements (purity or concentration, analytical/reagent grade, pack size, certificate of analysis, SDS, shelf life, storage/transport, delivery and payment terms, as applicable)</label><textarea class="form-control" name="terms_conditions" rows="5" required></textarea></div>';
echo '<button class="btn btn-primary" name="create_rfq" value="1">Save RFQ as Draft</button></form></div></details>';

echo '<section class="rfq-register-panel"><div class="rfq-register-heading"><div><h2>RFQ register</h2><p>Recent requests and supplier response progress</p></div><div class="rfq-register-tools"><input type="search" id="rfqSearch" class="form-control" placeholder="Search RFQ number or title"><select id="rfqStatusFilter" class="form-control"><option value="">All statuses</option><option value="draft">Draft</option><option value="issued">Issued</option><option value="closed">Closed</option><option value="awarded">Awarded</option></select></div></div>';
if (!$totalRfqs) {
    echo '<div class="rfq-empty"><strong>No RFQs created yet</strong><p>Create your first request to begin inviting suppliers and collecting quotations.</p></div>';
} else {
    echo '<div class="rfq-card-list" id="rfqCardList">';
    foreach ($rfqRows as $h) {
        $statusKey = strtolower($h['status']);
        $statusClass = preg_replace('/[^a-z0-9_-]/', '', $statusKey);
        $searchText = strtolower($h['rfq_no'] . ' ' . $h['title']);
        echo '<article class="rfq-register-card" data-status="' . rfq_h($statusKey) . '" data-search="' . rfq_h($searchText) . '">';
        echo '<div class="rfq-card-main"><div class="rfq-card-identity"><span class="rfq-number">' . rfq_h($h['rfq_no']) . '</span><h3>' . rfq_h($h['title']) . '</h3><span class="rfq-card-date">Closing ' . rfq_h($h['closing_at']) . '</span></div>';
        echo '<div class="rfq-card-status"><span class="rfq-status-pill ' . rfq_h($statusClass) . '">' . rfq_h(ucfirst($h['status'])) . '</span><div class="rfq-response-counts"><span><strong>' . (int)$h['invite_count'] . '</strong> invited</span><span><strong>' . (int)$h['quote_count'] . '</strong> quotes</span></div></div>';
        echo '<div class="rfq-card-actions">';
        echo '<a class="rfq-btn rfq-btn-secondary" target="_blank" href="RFQInviteExcel.php?rfq_id=' . (int)$h['id'] . '">Download supplier responses (Excel)</a>';
        if ($h['status'] === 'draft') {
            echo '<button type="button" class="rfq-btn rfq-btn-primary" data-toggle="collapse" data-target="#issue' . (int)$h['id'] . '">Invite suppliers</button>';
        } else {
            echo '<a class="rfq-btn rfq-btn-secondary" href="RFQ.php?compare=' . (int)$h['id'] . '">Compare quotations <span aria-hidden="true">→</span></a>';
        }
        if (in_array($h['status'], array('draft','issued'), true) && (int)$h['failed_email_count'] > 0) {
            echo '<form method="post" class="rfq-inline-action"><input type="hidden" name="FormID" value="' . rfq_h($_SESSION['FormID']) . '"><input type="hidden" name="rfq_id" value="' . (int)$h['id'] . '"><button class="rfq-btn rfq-btn-warning" name="retry_rfq_emails" value="1">Retry email (' . (int)$h['failed_email_count'] . ')</button></form>';
        }
        echo '</div></div>';
        if ($h['status'] === 'draft') {
            echo '<div class="collapse rfq-invite-panel" id="issue' . (int)$h['id'] . '"><form method="post"><input type="hidden" name="FormID" value="' . rfq_h($_SESSION['FormID']) . '"><input type="hidden" name="rfq_id" value="' . (int)$h['id'] . '"><strong>Choose suppliers to invite</strong><div class="rfq-supplier-select-grid">';
            $sr = DB_query("SELECT itemcode,customer,email FROM creditors WHERE inactive=0 ORDER BY customer", $db);
            while ($sp = DB_fetch_array($sr)) {
                echo '<label class="rfq-supplier-option"><input type="checkbox" name="supplier_codes[]" value="' . rfq_h($sp['itemcode']) . '"><span><strong>' . rfq_h($sp['customer']) . '</strong><small>' . rfq_h($sp['email']) . '</small></span></label>';
            }
            echo '</div><button class="rfq-btn rfq-btn-success" name="issue_rfq" value="1">Issue RFQ and email invitations</button></form></div>';
        }
        echo '</article>';
    }
    echo '</div><div class="rfq-no-results" id="rfqNoResults" style="display:none">No RFQs match the selected search or status.</div>';
}
echo '</section></div>';

if (isset($_GET['compare'])) {
    $compareId = (int)$_GET['compare'];
    $hr = DB_query("SELECT * FROM rfq_header WHERE id=" . $compareId, $db);
    if (DB_num_rows($hr) === 1) {
        $head = DB_fetch_array($hr);
        if ($head['status'] === 'awarded') {
            echo '<div class="alert alert-success">Awarded to supplier ' . rfq_h($head['awarded_supplier_code']) . '. Selected quotation ID: ' . (int)$head['awarded_quote_id'] . '.</div>';
            if (empty($head['po_documentno'])) {
                echo '<p><a class="btn btn-primary" href="PurchaseOder.php?rfq_quote_id=' . (int)$head['awarded_quote_id'] . '">Create Purchase Order from awarded quotation</a></p>';
            } else {
                echo '<p class="alert alert-info">Purchase Order created: ' . rfq_h($head['po_documentno']) . '</p>';
            }
        }
        echo '<div class="rfq-compare-head"><div><h2 class="rfq-compare-title">Request ' . rfq_h($head['rfq_no']) . '</h2><p class="rfq-compare-subtitle">' . rfq_h($head['title']) . ' &nbsp;·&nbsp; Closing ' . rfq_h($head['closing_at']) . '</p></div><div class="rfq-actions"><span class="rfq-status">' . rfq_h($head['status']) . '</span><a class="btn btn-default" href="RFQ.php">Back to RFQ Register</a></div></div>';
        $inviteResult = DB_query("SELECT i.status,i.invited_at,i.submitted_at,c.customer
            FROM rfq_invites i JOIN creditors c ON c.itemcode COLLATE utf8mb4_unicode_ci = i.supplier_code COLLATE utf8mb4_unicode_ci
            WHERE i.rfq_id=" . $compareId . " ORDER BY c.customer", $db);
        $supplierLanes = array('declined' => array(), 'expired' => array(), 'invited' => array(), 'submitted' => array());
        while ($inviteRow = DB_fetch_array($inviteResult)) {
            $laneKey = $inviteRow['status'];
            if (!isset($supplierLanes[$laneKey])) { $supplierLanes[$laneKey] = array(); }
            $supplierLanes[$laneKey][] = $inviteRow;
        }
        echo '<div class="rfq-supplier-board">';
        echo '<section class="rfq-lane"><h4 class="rfq-lane-title"><span class="glyphicon glyphicon-ban-circle"></span>No Bid <small>(' . (count($supplierLanes['declined']) + count($supplierLanes['expired'])) . ')</small></h4>';
        foreach (array_merge($supplierLanes['declined'], $supplierLanes['expired']) as $supplierRow) {
            echo '<div class="rfq-supplier-chip">' . rfq_h($supplierRow['customer']) . '<small>' . ($supplierRow['status'] === 'declined' ? 'Supplier declined' : 'Invitation expired') . '</small></div>';
        }
        if (!count($supplierLanes['declined']) && !count($supplierLanes['expired'])) { echo '<div class="rfq-supplier-chip text-muted">No suppliers in this category</div>'; }
        echo '</section><section class="rfq-lane"><h4 class="rfq-lane-title"><span class="glyphicon glyphicon-refresh"></span>Open <small>(' . count($supplierLanes['invited']) . ')</small></h4>';
        foreach ($supplierLanes['invited'] as $supplierRow) {
            echo '<div class="rfq-supplier-chip">' . rfq_h($supplierRow['customer']) . '<small>Invitation sent ' . rfq_h($supplierRow['invited_at']) . '</small></div>';
        }
        if (!count($supplierLanes['invited'])) { echo '<div class="rfq-supplier-chip text-muted">No suppliers awaiting a response</div>'; }
        echo '</section><section class="rfq-lane"><h4 class="rfq-lane-title"><span class="glyphicon glyphicon-ok-circle"></span>Completed <small>(' . count($supplierLanes['submitted']) . ')</small></h4>';
        foreach ($supplierLanes['submitted'] as $supplierRow) {
            echo '<div class="rfq-supplier-chip">' . rfq_h($supplierRow['customer']) . '<small>Quotation submitted ' . rfq_h($supplierRow['submitted_at']) . '</small></div>';
        }
        if (!count($supplierLanes['submitted'])) { echo '<div class="rfq-supplier-chip text-muted">No quotations submitted yet</div>'; }
        echo '</section></div>';

        $lines = DB_query("SELECT rl.*,sm.descrip AS inventory_name,u.descrip AS requested_unit_description
            FROM rfq_lines rl
            LEFT JOIN stockmaster sm ON sm.itemcode COLLATE utf8mb4_unicode_ci = rl.itemcode COLLATE utf8mb4_unicode_ci
            LEFT JOIN unit u ON u.code=rl.requested_pack
            WHERE rl.rfq_id=" . $compareId . " ORDER BY rl.line_no", $db);
        $quotes = DB_query("SELECT q.*,c.customer FROM rfq_quotes q JOIN creditors c ON c.itemcode COLLATE utf8mb4_unicode_ci = q.supplier_code COLLATE utf8mb4_unicode_ci WHERE q.rfq_id=" . $compareId . " ORDER BY c.customer", $db);
        $quoteRows = array();
        while ($q = DB_fetch_array($quotes)) { $quoteRows[] = $q; }
        if (!count($quoteRows)) {
            echo '<div class="alert alert-info">No supplier quotations have been submitted for this RFQ yet.</div>';
        } else {
            echo '<h3 style="font-size:19px;font-weight:700;margin:4px 0 12px">Quotes</h3>';
            if ($head['status'] !== 'awarded') {
                echo '<form method="post" id="rfqAwardForm" style="display:flex;justify-content:flex-end;align-items:center;gap:10px;margin:0 0 12px"><input type="hidden" name="FormID" value="' . rfq_h($_SESSION['FormID']) . '"><input type="hidden" name="rfq_id" value="' . $compareId . '"><button class="btn btn-primary" type="submit" name="award_quote" value="1" onclick="return confirm(&quot;Award the selected supplier quotation?&quot;)">Award selected quotation</button></form>';
            }
            echo '<div class="rfq-quotes-wrap"><table class="table rfq-quotes"><thead><tr><th>Item / requested quantity</th>';
            foreach ($quoteRows as $q) {
                echo '<th><div class="rfq-supplier-head">' . rfq_h($q['customer']) . '<small>' . rfq_h($q['quote_reference'] ? $q['quote_reference'] : 'Quotation submitted') . '</small></div>';
                if ($head['status'] !== 'awarded') {
                    echo '<label class="rfq-quote-choice" style="display:inline-flex;align-items:center;gap:6px;margin-top:9px;font-weight:600"><input type="checkbox" form="rfqAwardForm" name="quote_choice[]" value="' . (int)$q['id'] . '" class="rfq-quote-choice-input"> Select quotation</label>';
                }
                echo '</th>';
            }
            echo '</tr></thead><tbody>';
            $quoteTotals = array_fill(0, count($quoteRows), 0);
            $lineRows = array();
            while ($line = DB_fetch_array($lines)) { $lineRows[] = $line; }
            foreach ($lineRows as $line) {
                $values = array();
                $lowestComparablePrice = null;
                foreach ($quoteRows as $quoteIndex => $q) {
                    $qr = DB_query("SELECT unit_price,tax_rate,availability_notes FROM rfq_quote_lines WHERE quote_id=" . (int)$q['id'] . " AND rfq_line_id=" . (int)$line['id'], $db);
                    if (DB_num_rows($qr)) {
                        $v = DB_fetch_array($qr);
                        $effectiveUnitPrice = (float)$v['unit_price'];
                        if (empty($q['tax_inclusive'])) { $effectiveUnitPrice *= (1 + ((float)$v['tax_rate'] / 100)); }
                        $lineTotal = (float)$line['quantity'] * $effectiveUnitPrice;
                        $quoteTotals[$quoteIndex] += $lineTotal;
                        $values[$quoteIndex] = array('unit_price' => (float)$v['unit_price'], 'tax_rate' => (float)$v['tax_rate'], 'notes' => $v['availability_notes'], 'effective' => $effectiveUnitPrice);
                        if ($lowestComparablePrice === null || $effectiveUnitPrice < $lowestComparablePrice) { $lowestComparablePrice = $effectiveUnitPrice; }
                    } else { $values[$quoteIndex] = null; }
                }
                $inventoryName = trim((string)$line['inventory_name']) !== '' ? $line['inventory_name'] : $line['description'];
                $requestedDescription = trim((string)$line['description']);
                echo '<tr><th>' . rfq_h($inventoryName) .
                    ($requestedDescription !== '' && strcasecmp($requestedDescription, $inventoryName) !== 0 ? '<br><small>Requested description: ' . rfq_h($requestedDescription) . '</small>' : '') .
                    '<br><small>Qty: ' . rfq_h($line['quantity']) . ' ' . rfq_h($line['requested_pack']) . '</small></th>';
                foreach ($quoteRows as $quoteIndex => $q) {
                    $v = $values[$quoteIndex];
                    if ($v !== null) {
                        $isBest = abs($v['effective'] - $lowestComparablePrice) < 0.00001;
                        echo '<td' . ($isBest ? ' class="rfq-best"' : '') . '><span class="rfq-price">' . rfq_h($head['currencycode']) . ' ' . number_format($v['unit_price'], 2) . '</span><span class="rfq-cell-meta">' . (isset($q['delivery_days']) && $q['delivery_days'] !== null ? rfq_h($q['delivery_days']) . ' days' : 'Delivery not specified') . '<br>Tax: ' . number_format($v['tax_rate'], 2) . '%<br>' . rfq_h($v['notes']) . '</span></td>';
                    } else { echo '<td class="text-muted">—</td>'; }
                }
                echo '</tr>';
            }
            echo '<tr class="rfq-total-row"><th>Indicative quotation total<br><small>Tax treatment applied</small></th>';
            foreach ($quoteRows as $quoteIndex => $q) {
                echo '<td><span class="rfq-price">' . rfq_h($head['currencycode']) . ' ' . number_format($quoteTotals[$quoteIndex], 2) . '</span><span class="rfq-cell-meta">' . (isset($q['delivery_days']) && $q['delivery_days'] !== null ? rfq_h($q['delivery_days']) . ' days delivery' : 'Delivery not specified') . '</span></td>';
            }
            echo '</tr></tbody></table></div>';
            echo '<p class="rfq-legend"><span></span> Lowest comparable price per requested unit, including applicable tax.</p>';
        }

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
 function configureRfqUnit(row){
  var item=row.querySelector('.rfq-item-select'),unit=row.querySelector('.rfq-unit-select'),factorLabel=row.querySelector('.rfq-unit-factor');
  if(!item||!unit){return;}
  var selected=item.options[item.selectedIndex];unit.innerHTML='';unit.disabled=true;
  if(!item.value||!selected){unit.add(new Option('Select item first',''));if(factorLabel){factorLabel.textContent='';}return;}
  var full=selected.getAttribute('data-full-unit')||'',fullLabel=selected.getAttribute('data-full-label')||full;
  var loose=selected.getAttribute('data-loose-unit')||'',looseLabel=selected.getAttribute('data-loose-label')||loose;
  var factor=parseFloat(selected.getAttribute('data-factor')||'1');
  unit.add(new Option('Select requested unit',''));
  if(full){unit.add(new Option('Pack — '+fullLabel,full));}
  if(loose&&loose!==full){unit.add(new Option('Each — '+looseLabel,loose));}
  unit.disabled=false;
  if(factorLabel){factorLabel.textContent='1 Pack = '+(isFinite(factor)&&factor>0?factor:1)+' '+(looseLabel||fullLabel);}
 }
 function bindRfqItem(row){
  var item=row.querySelector('.rfq-item-select');
  if(item){item.addEventListener('change',function(){configureRfqUnit(row);var opt=item.options[item.selectedIndex],desc=row.querySelector('.rfq-description');if(opt&&opt.value&&desc){desc.value=opt.getAttribute('data-description')||desc.value;}});}
 }
 document.querySelectorAll('#rfqLines tbody tr').forEach(bindRfqItem);
 var add=document.getElementById('addRfqLine');
 if(add){add.addEventListener('click',function(){
  var body=document.querySelector('#rfqLines tbody'),row=body.querySelector('tr').cloneNode(true);
  row.querySelectorAll('input,textarea,select').forEach(function(el){el.value='';});
  var unit=row.querySelector('.rfq-unit-select');if(unit){unit.innerHTML='<option value="">Select item first</option>';unit.disabled=true;}
  var factor=row.querySelector('.rfq-unit-factor');if(factor){factor.textContent='';}
  body.appendChild(row);bindRfqItem(row);
 });}
 var quoteChoices=document.querySelectorAll('.rfq-quote-choice-input');
 quoteChoices.forEach(function(choice){
  choice.addEventListener('change',function(){
   if(this.checked){
    quoteChoices.forEach(function(other){if(other!==choice){other.checked=false;}});
   }
  });
 });
 var search=document.getElementById('rfqSearch');
 var status=document.getElementById('rfqStatusFilter');
 var cards=document.querySelectorAll('#rfqCardList .rfq-register-card');
 var noResults=document.getElementById('rfqNoResults');
 function filterRfqs(){
  var term=search?search.value.toLowerCase().trim():'';
  var selected=status?status.value:'';
  var visible=0;
  cards.forEach(function(card){
   var matchesText=!term||(card.getAttribute('data-search')||'').indexOf(term)!==-1;
   var matchesStatus=!selected||card.getAttribute('data-status')===selected;
   var show=matchesText&&matchesStatus;
   card.style.display=show?'':'none';
   if(show){visible++;}
  });
  if(noResults){noResults.style.display=visible?'none':'';}
 }
 if(search){search.addEventListener('input',filterRfqs);}
 if(status){status.addEventListener('change',filterRfqs);}
})();
</script>
<?php include('includes/footer.inc'); ?>