<?php

function etimsGetConfigValue($name, $default = '') {
    return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
}

function etimsSetConfigValue($name, $value) {
    global $db;

    $sql = "INSERT INTO config (confname, confvalue)
            VALUES ('" . DB_escape_string($name) . "', '" . DB_escape_string($value) . "')
            ON DUPLICATE KEY UPDATE confvalue = VALUES(confvalue)";
    DB_query($sql, $db);
    $_SESSION[$name] = $value;
}

/*
 * FIX 2: Load all ETIMS_* settings from the config table into $_SESSION.
 * Call this once at the top of any page that uses eTIMS functions so that
 * settings survive a session restart without needing to be re-saved first.
 */
function etimsBootSettings() {
    global $db;

    $keys = [
        'ETIMS_BaseUrl', 'ETIMS_AuthUrl', 'ETIMS_InvoiceUrl',
        'ETIMS_ClientId', 'ETIMS_ClientSecret', 'ETIMS_Scope',
        'ETIMS_GrantType', 'ETIMS_KraPin', 'ETIMS_ControlUnitId',
        'ETIMS_DeviceSerial', 'ETIMS_BranchId', 'ETIMS_AllowInsecure',
        'ETIMS_AccessToken', 'ETIMS_TokenExpiresAt'
    ];

    $in = implode(',', array_map(function($k) { return "'" . DB_escape_string($k) . "'"; }, $keys));
    $res = DB_query("SELECT confname, confvalue FROM config WHERE confname IN ($in)", $db);

    while ($row = DB_fetch_array($res)) {
        $_SESSION[$row['confname']] = $row['confvalue'];
    }
}

function etimsLoadSettings() {
    return array(
        'base_url'         => trim(etimsGetConfigValue('ETIMS_BaseUrl', '')),
        'auth_url'         => trim(etimsGetConfigValue('ETIMS_AuthUrl', '')),
        'invoice_url'      => trim(etimsGetConfigValue('ETIMS_InvoiceUrl', '')),
        'client_id'        => trim(etimsGetConfigValue('ETIMS_ClientId', '')),
        'client_secret'    => trim(etimsGetConfigValue('ETIMS_ClientSecret', '')),
        'scope'            => trim(etimsGetConfigValue('ETIMS_Scope', '')),
        'grant_type'       => trim(etimsGetConfigValue('ETIMS_GrantType', 'client_credentials')),
        'kra_pin'          => trim(etimsGetConfigValue('ETIMS_KraPin', '')),
        'control_unit_id'  => trim(etimsGetConfigValue('ETIMS_ControlUnitId', '')),
        'device_serial'    => trim(etimsGetConfigValue('ETIMS_DeviceSerial', '')),
        'branch_id'        => trim(etimsGetConfigValue('ETIMS_BranchId', '')),
        'allow_insecure'   => etimsGetConfigValue('ETIMS_AllowInsecure', '0') === '1',
        'access_token'     => trim(etimsGetConfigValue('ETIMS_AccessToken', '')),
        'token_expires_at' => trim(etimsGetConfigValue('ETIMS_TokenExpiresAt', ''))
    );
}

function etimsIsTokenValid($expiresAt) {
    if ($expiresAt === '') {
        return false;
    }

    $expiresTs = strtotime($expiresAt);
    if ($expiresTs === false) {
        return false;
    }

    return $expiresTs > (time() + 60);
}

function etimsCurlRequest($url, $method = 'POST', $body = null, $headers = array(), $allowInsecure = false) {
    if (!function_exists('curl_init')) {
        return array(
            'success'   => false,
            'http_code' => 0,
            'body'      => '',
            'json'      => null,
            'message'   => 'cURL is not available on this server.'
        );
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    if ($allowInsecure) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    $responseBody = curl_exec($ch);
    $error        = curl_error($ch);
    $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseBody === false) {
        return array(
            'success'   => false,
            'http_code' => $httpCode,
            'body'      => '',
            'json'      => null,
            'message'   => $error !== '' ? $error : 'Unknown cURL error.'
        );
    }

    $json    = json_decode($responseBody, true);
    $success = $httpCode >= 200 && $httpCode < 300;

    return array(
        'success'   => $success,
        'http_code' => $httpCode,
        'body'      => $responseBody,
        'json'      => is_array($json) ? $json : null,
        'message'   => $success ? 'OK' : 'HTTP ' . $httpCode
    );
}

function etimsArrayValue($array, $paths, $default = '') {
    foreach ($paths as $path) {
        $segments = explode('.', $path);
        $current  = $array;
        $found    = true;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                $found = false;
                break;
            }
            $current = $current[$segment];
        }

        if ($found && $current !== null && $current !== '') {
            return $current;
        }
    }

    return $default;
}

function etimsNormalizeDateTime($value) {
    if ($value === null || $value === '') {
        return null;
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $ts);
}

function etimsBuildAuthPayload($settings) {
    $payload = array(
        'grant_type'    => $settings['grant_type'] !== '' ? $settings['grant_type'] : 'client_credentials',
        'client_id'     => $settings['client_id'],
        'client_secret' => $settings['client_secret']
    );

    if ($settings['scope'] !== '') {
        $payload['scope'] = $settings['scope'];
    }

    return $payload;
}

function etimsRequestAccessToken($forceRefresh = false) {
    $settings = etimsLoadSettings();

    if (!$forceRefresh && $settings['access_token'] !== '' && etimsIsTokenValid($settings['token_expires_at'])) {
        return array(
            'success' => true,
            'token'   => $settings['access_token'],
            'message' => 'Using stored token.'
        );
    }

    if ($settings['auth_url'] === '' || $settings['client_id'] === '' || $settings['client_secret'] === '') {
        return array(
            'success' => false,
            'token'   => '',
            'message' => 'Auth URL, Client ID, or Client Secret is missing.'
        );
    }

    $response = etimsCurlRequest(
        $settings['auth_url'],
        'POST',
        http_build_query(etimsBuildAuthPayload($settings)),
        array('Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'),
        $settings['allow_insecure']
    );

    $data  = is_array($response['json']) ? $response['json'] : array();
    $token = etimsArrayValue($data, array('access_token', 'data.access_token', 'token', 'data.token'));

    if ($token === '') {
        $message = etimsArrayValue($data, array('message', 'error_description', 'error', 'detail'), $response['body']);
        return array(
            'success' => false,
            'token'   => '',
            'message' => $message !== '' ? $message : 'Authorization response did not include an access token.'
        );
    }

    etimsSetConfigValue('ETIMS_AccessToken', $token);

    $expiresIn = etimsArrayValue($data, array('expires_in', 'data.expires_in'));

    // FIX 5: guard against 0 or negative expires_in storing a past timestamp
    if ($expiresIn !== '' && is_numeric($expiresIn) && (int) $expiresIn > 0) {
        etimsSetConfigValue('ETIMS_TokenExpiresAt', date('Y-m-d H:i:s', time() + (int) $expiresIn));
    } else {
        etimsSetConfigValue('ETIMS_TokenExpiresAt', '');
    }

    return array(
        'success' => true,
        'token'   => $token,
        'message' => 'Authorization successful.'
    );
}

function etimsFetchInvoiceHeader($documentType, $documentNo) {
    global $db;

    $sql = "SELECT SalesHeader.documenttype,
                   SalesHeader.documentno,
                   SalesHeader.docdate,
                   SalesHeader.postingdate,
                   SalesHeader.duedate,
                   SalesHeader.customercode,
                   SalesHeader.customername,
                   SalesHeader.currencycode,
                   SalesHeader.salespersoncode,
                   SalesHeader.yourreference,
                   SalesHeader.externaldocumentno,
                   SalesHeader.locationcode,
                   SalesHeader.vatinclusive,
                   SalesHeader.packagescharge,
                   debtors.email,
                   debtors.phone,
                   debtors.city,
                   debtors.country,
                   debtors.contact,
                   debtors.middlen AS PIN
            FROM SalesHeader
            LEFT JOIN debtors ON SalesHeader.customercode = debtors.itemcode
            WHERE SalesHeader.documenttype='" . (int) $documentType . "'
              AND SalesHeader.documentno='" . DB_escape_string($documentNo) . "'
            LIMIT 1";

    $result = DB_query($sql, $db);
    $row    = DB_fetch_array($result);

    return is_array($row) ? $row : null;
}

function etimsFetchInvoiceLines($documentType, $documentNo) {
    global $db;

    $sql = "SELECT entryno,
                   code,
                   description,
                   unitofmeasure,
                   Quantity,
                   UnitPrice,
                   vatamount,
                   invoiceamount,
                   vatrate,
                   shipping,
                   packagescharge,
                   locationcode,
                   partperunit,
                   totalchargedcontainers,
                   containercode
            FROM SalesLine
            WHERE documenttype='" . (int) $documentType . "'
              AND documentno='" . DB_escape_string($documentNo) . "'
            ORDER BY entryno";

    $result = DB_query($sql, $db);
    $lines  = array();

    while ($row = DB_fetch_array($result)) {
        $lines[] = $row;
    }

    return $lines;
}

function etimsBuildInvoicePayload($documentType, $documentNo) {
    $settings = etimsLoadSettings();
    $header   = etimsFetchInvoiceHeader($documentType, $documentNo);

    if (!$header) {
        return array(
            'success' => false,
            'message' => 'Invoice header not found.',
            'payload' => null
        );
    }

    $lines         = etimsFetchInvoiceLines($documentType, $documentNo);
    $lineItems     = array();
    $netTotal      = 0;
    $vatTotal      = 0;
    $grossTotal    = 0;
    $shippingTotal = 0;
    $packageTotal  = 0;

    foreach ($lines as $line) {
        $grossAmount = (float) $line['invoiceamount'];
        $vatAmount   = (float) $line['vatamount'];
        $netAmount   = $grossAmount - $vatAmount;

        $netTotal      += $netAmount;
        $vatTotal      += $vatAmount;
        $grossTotal    += $grossAmount;
        $shippingTotal += (float) $line['shipping'];
        $packageTotal  += (float) $line['packagescharge'];

        $lineItems[] = array(
            'lineNo'               => (int) $line['entryno'],
            'itemCode'             => $line['code'],
            'description'          => $line['description'],
            'uom'                  => $line['unitofmeasure'],
            'quantity'             => (float) $line['Quantity'],
            'unitPrice'            => (float) $line['UnitPrice'],
            // FIX 4: round individual line amounts to avoid float drift
            'netAmount'            => round($netAmount, 2),
            'vatAmount'            => round($vatAmount, 2),
            'grossAmount'          => round($grossAmount, 2),
            'vatRate'              => (float) $line['vatrate'],
            'shipping'             => (float) $line['shipping'],
            'packagesCharge'       => (float) $line['packagescharge'],
            'locationCode'         => $line['locationcode'],
            'partsPerUnit'         => (float) $line['partperunit'],
            'containerChargeTotal' => (float) $line['totalchargedcontainers'],
            'containerCode'        => $line['containercode']
        );
    }

    $payload = array(
        'sourceSystem'  => 'Laberp',
        'documentType'  => (int) $documentType,
        'documentNo'    => $documentNo,
        'kraPin'        => $settings['kra_pin'],
        'controlUnitId' => $settings['control_unit_id'],
        'deviceSerial'  => $settings['device_serial'],
        'branchId'      => $settings['branch_id'],
        'invoice'       => array(
            'invoiceNumber'    => $header['documentno'],
            'invoiceDate'      => $header['docdate'],
            'postingDate'      => $header['postingdate'],
            'dueDate'          => $header['duedate'],
            'currencyCode'     => $header['currencycode'],
            'salesPersonCode'  => $header['salespersoncode'],
            'yourReference'    => $header['yourreference'],
            'sourceDocumentNo' => $header['externaldocumentno'],
            'locationCode'     => $header['locationcode'],
            'vatInclusive'     => (int) $header['vatinclusive'],
            'customer'         => array(
                'customerCode' => $header['customercode'],
                'customerName' => $header['customername'],
                'contact'      => $header['contact'],
                'email'        => $header['email'],
                'phone'        => $header['phone'],
                'city'         => $header['city'],
                'country'      => $header['country'],
                'pin'          => $header['PIN']
            ),
            'totals'           => array(
                'netAmount'           => round($netTotal, 2),
                'vatAmount'           => round($vatTotal, 2),
                'grossAmount'         => round($grossTotal, 2),
                'shippingAmount'      => round($shippingTotal, 2),
                'packagesCharge'      => round($packageTotal, 2),
                'headerPackagesCharge'=> round((float) $header['packagescharge'], 2)
            ),
            'lines'            => $lineItems
        )
    );

    return array(
        'success' => true,
        'message' => 'Invoice payload prepared.',
        'payload' => $payload
    );
}

function etimsUpsertInvoiceLog($documentType, $documentNo, $status, $message, $rawResponse, $responseJson = null) {
    global $db;

    $settings     = etimsLoadSettings();
    $responseJson = is_array($responseJson) ? $responseJson : array();

    $kraInvoiceNo    = etimsArrayValue($responseJson, array('kra_invoice_no', 'kraInvoiceNo', 'data.kra_invoice_no', 'data.kraInvoiceNo'));
    $cuInvoiceNo     = etimsArrayValue($responseJson, array('cu_invoice_no', 'cuInvoiceNo', 'data.cu_invoice_no', 'data.cuInvoiceNo'));
    $controlUnitId   = etimsArrayValue($responseJson, array('control_unit_id', 'controlUnitId', 'data.control_unit_id', 'data.controlUnitId'), $settings['control_unit_id']);
    $kraPin          = etimsArrayValue($responseJson, array('kra_pin', 'kraPin', 'data.kra_pin', 'data.kraPin'), $settings['kra_pin']);
    $invoiceSignature= etimsArrayValue($responseJson, array('invoice_signature', 'invoiceSignature', 'data.invoice_signature', 'data.invoiceSignature'));
    $qrCodeText      = etimsArrayValue($responseJson, array('qr_code_text', 'qrCodeText', 'qrCode', 'data.qr_code_text', 'data.qrCodeText'));
    $verificationUrl = etimsArrayValue($responseJson, array('verification_url', 'verificationUrl', 'verifyUrl', 'data.verification_url', 'data.verificationUrl'));
    $issueDate       = etimsNormalizeDateTime(
        etimsArrayValue($responseJson, array('etims_issue_datetime', 'issueDateTime', 'issuedAt', 'data.etims_issue_datetime', 'data.issueDateTime'))
    );

    // FIX 6: truncate raw response before storing to avoid exceeding TEXT column limits
    $rawResponse = mb_substr($rawResponse, 0, 65000);

    $sql = "INSERT INTO etims_invoice
                (documenttype, documentno, kra_invoice_no, cu_invoice_no, control_unit_id, kra_pin,
                 invoice_signature, qr_code_text, verification_url, etims_issue_datetime, etims_status,
                 etims_message, etims_raw_response)
            VALUES
                ('" . (int) $documentType . "',
                 '" . DB_escape_string($documentNo) . "',
                 '" . DB_escape_string($kraInvoiceNo) . "',
                 '" . DB_escape_string($cuInvoiceNo) . "',
                 '" . DB_escape_string($controlUnitId) . "',
                 '" . DB_escape_string($kraPin) . "',
                 '" . DB_escape_string($invoiceSignature) . "',
                 '" . DB_escape_string($qrCodeText) . "',
                 '" . DB_escape_string($verificationUrl) . "',
                 " . ($issueDate !== null ? "'" . DB_escape_string($issueDate) . "'" : "NULL") . ",
                 '" . DB_escape_string($status) . "',
                 '" . DB_escape_string($message) . "',
                 '" . DB_escape_string($rawResponse) . "')
            ON DUPLICATE KEY UPDATE
                 kra_invoice_no        = VALUES(kra_invoice_no),
                 cu_invoice_no         = VALUES(cu_invoice_no),
                 control_unit_id       = VALUES(control_unit_id),
                 kra_pin               = VALUES(kra_pin),
                 invoice_signature     = VALUES(invoice_signature),
                 qr_code_text          = VALUES(qr_code_text),
                 verification_url      = VALUES(verification_url),
                 etims_issue_datetime  = VALUES(etims_issue_datetime),
                 etims_status          = VALUES(etims_status),
                 etims_message         = VALUES(etims_message),
                 etims_raw_response    = VALUES(etims_raw_response)";

    DB_query($sql, $db);
}

function etimsSubmitInvoice($documentType, $documentNo) {
    $settings = etimsLoadSettings();

    if ($settings['invoice_url'] === '') {
        etimsUpsertInvoiceLog($documentType, $documentNo, 'SKIPPED', 'eTIMS Invoice Submit URL is not configured.', '');
        return array(
            'success' => false,
            'skipped' => true,
            'message' => 'eTIMS Invoice Submit URL is not configured.'
        );
    }

    $payloadResult = etimsBuildInvoicePayload($documentType, $documentNo);
    if (!$payloadResult['success']) {
        etimsUpsertInvoiceLog($documentType, $documentNo, 'FAILED', $payloadResult['message'], '');
        return array(
            'success' => false,
            'skipped' => false,
            'message' => $payloadResult['message']
        );
    }

    $headers = array('Content-Type: application/json', 'Accept: application/json');
    $token   = $settings['access_token'];

    // FIX 1: only attempt token refresh when an auth URL is configured.
    // Using || (OR) previously meant a static pre-configured token would trigger
    // a refresh attempt — failing with "Auth URL missing" once the token expired.
    if ($settings['auth_url'] !== '') {
        $tokenResult = etimsRequestAccessToken(false);
        if (!$tokenResult['success']) {
            etimsUpsertInvoiceLog($documentType, $documentNo, 'FAILED', $tokenResult['message'], '');
            return array(
                'success' => false,
                'skipped' => false,
                'message' => $tokenResult['message']
            );
        }
        $token = $tokenResult['token'];
    }

    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $body     = json_encode($payloadResult['payload']);
    $response = etimsCurlRequest($settings['invoice_url'], 'POST', $body, $headers, $settings['allow_insecure']);

    $responseJson = is_array($response['json']) ? $response['json'] : array();

    if ($response['success']) {
        $message = etimsArrayValue($responseJson, array('message', 'msg', 'detail', 'data.message'), 'Invoice submitted to eTIMS.');
        etimsUpsertInvoiceLog($documentType, $documentNo, 'SUCCESS', $message, $response['body'], $responseJson);
        return array(
            'success' => true,
            'skipped' => false,
            'message' => $message
        );
    }

    $message = etimsArrayValue($responseJson, array('message', 'msg', 'detail', 'error_description', 'error'), $response['message']);
    if ($message === '') {
        $message = $response['body'] !== '' ? $response['body'] : 'eTIMS submission failed.';
    }

    etimsUpsertInvoiceLog($documentType, $documentNo, 'FAILED', $message, $response['body'], $responseJson);
    return array(
        'success' => false,
        'skipped' => false,
        'message' => $message
    );
}