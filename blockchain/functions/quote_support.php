<?php

function quote_table_exists(mysqli $conn, $tableName)
{
    $tableName = $conn->real_escape_string((string)$tableName);
    $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");

    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function quote_get_session_user()
{
    return trim((string)($_SESSION['UserID'] ?? $_SESSION['User_name'] ?? $_SESSION['username'] ?? 'system'));
}

function quote_get_config_value(mysqli $conn, $configName, $defaultValue = '')
{
    if (!quote_table_exists($conn, 'config')) {
        return $defaultValue;
    }

    $stmt = $conn->prepare("SELECT confvalue FROM config WHERE confname = ? LIMIT 1");
    if (!$stmt) {
        return $defaultValue;
    }

    $configName = trim((string)$configName);
    $stmt->bind_param('s', $configName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? ($result->fetch_assoc() ?: null) : null;
    $stmt->close();

    if (!$row || !array_key_exists('confvalue', $row)) {
        return $defaultValue;
    }

    return $row['confvalue'];
}

function get_quote_company_profile(mysqli $conn)
{
    $company = [
        'company_name' => '',
        'address' => '',
        'address1' => '',
        'address2' => '',
        'address3' => '',
        'telephone' => '',
        'email' => '',
        'accreditation_text' => '',
    ];

    if (!quote_table_exists($conn, 'company_master')) {
        return $company;
    }

    $result = $conn->query("SELECT * FROM company_master ORDER BY company_id DESC LIMIT 1");
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc() ?: [];
        $company = array_merge($company, $row);
    }

    $company['accreditation_text'] = trim((string)quote_get_config_value($conn, 'quote_company_accreditation_text', ''));
    if ($company['accreditation_text'] === '') {
        $company['accreditation_text'] = trim((string)quote_get_config_value($conn, 'company_accreditation_text', ''));
    }

    return $company;
}

function get_quote_customer_list(mysqli $conn)
{
    $customers = [];
    if (!quote_table_exists($conn, 'debtors')) {
        return $customers;
    }

    $sql = "SELECT itemcode, customer, contact, company, phone, email, postcode, city FROM debtors WHERE customer IS NOT NULL AND customer <> '' ORDER BY customer ASC";
    $result = $conn->query($sql);
    if (!$result instanceof mysqli_result) {
        return $customers;
    }

    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }

    return $customers;
}

function get_quote_customer_by_id(mysqli $conn, $customerId)
{
    $customerId = trim((string)$customerId);
    if ($customerId === '' || !quote_table_exists($conn, 'debtors')) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT itemcode, customer, contact, company, phone, email, postcode, city
        FROM debtors
        WHERE itemcode = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? ($result->fetch_assoc() ?: null) : null;
    $stmt->close();

    return $row;
}

function get_quote_parameter_catalog(mysqli $conn)
{
    return search_quote_parameters($conn, '', 5000);
}

function get_quote_parameters_by_standard(mysqli $conn, $standardId, $limit = 5000)
{
    $parameters = [];
    $standardId = (int)$standardId;
    $limit = max(1, min(5000, (int)$limit));

    if ($standardId <= 0 || !quote_table_exists($conn, 'testparameters')) {
        return $parameters;
    }

    $stmt = $conn->prepare("
        SELECT
            tp.ParameterID,
            tp.ParameterName,
            tp.StandardID,
            tp.Method,
            ts.StandardName,
            ts.StandardCode
        FROM testparameters tp
        LEFT JOIN teststandards ts ON ts.StandardID = tp.StandardID
        WHERE tp.StandardID = ?
        ORDER BY tp.ParameterName ASC
        LIMIT ?
    ");
    if (!$stmt) {
        return $parameters;
    }

    $stmt->bind_param('ii', $standardId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($result && ($row = $result->fetch_assoc())) {
        $standardCode = trim((string)($row['StandardCode'] ?? ''));
        $standardName = (string)($row['StandardName'] ?? '');

        $parameters[] = [
            'parameter_id' => (int)($row['ParameterID'] ?? 0),
            'parameter_name' => (string)($row['ParameterName'] ?? ''),
            'standard_id' => (int)($row['StandardID'] ?? 0),
            'method' => (string)($row['Method'] ?? ''),
            'standard_name' => $standardName,
            'standard_code' => $standardCode,
            'test_code' => $standardCode !== '' ? $standardCode : ('STD-' . $standardId),
            'label' => trim((string)($row['ParameterName'] ?? '') . ' - ' . $standardName),
        ];
    }

    $stmt->close();

    return $parameters;
}

function search_quote_parameters(mysqli $conn, $query = '', $limit = 50)
{
    $parameters = [];
    if (!quote_table_exists($conn, 'testparameters')) {
        return $parameters;
    }

    $query = trim((string)$query);
    $limit = max(1, min(5000, (int)$limit));
    $sql = "
        SELECT
            tp.ParameterID,
            tp.ParameterName,
            tp.StandardID,
            tp.Method,
            ts.StandardName,
            ts.StandardCode
        FROM testparameters tp
        LEFT JOIN teststandards ts ON ts.StandardID = tp.StandardID
    ";

    if ($query !== '') {
        $sql .= "
            WHERE tp.ParameterName LIKE ?
               OR ts.StandardName LIKE ?
               OR ts.StandardCode LIKE ?
        ";
    }

    $sql .= " ORDER BY ts.StandardName ASC, tp.ParameterName ASC LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $parameters;
    }

    if ($query !== '') {
        $searchPattern = '%' . $query . '%';
        $stmt->bind_param('sssi', $searchPattern, $searchPattern, $searchPattern, $limit);
    } else {
        $stmt->bind_param('i', $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($result && ($row = $result->fetch_assoc())) {
        $standardCode = trim((string)($row['StandardCode'] ?? ''));
        $standardId = (int)($row['StandardID'] ?? 0);
        $parameterName = (string)($row['ParameterName'] ?? '');
        $standardName = (string)($row['StandardName'] ?? '');

        $parameters[] = [
            'parameter_id' => (int)($row['ParameterID'] ?? 0),
            'parameter_name' => $parameterName,
            'standard_id' => $standardId,
            'method' => (string)($row['Method'] ?? ''),
            'standard_name' => $standardName,
            'standard_code' => $standardCode,
            'test_code' => $standardCode !== '' ? $standardCode : ('STD-' . $standardId),
            'label' => trim($parameterName . ' - ' . $standardName),
        ];
    }

    $stmt->close();

    return $parameters;
}

function get_quotes_for_user(mysqli $conn, $userName, $query = '', $limit = 100)
{
    $quotes = [];
    $userName = trim((string)$userName);
    $query = trim((string)$query);
    $limit = max(1, min(250, (int)$limit));

    if ($userName === '' || !quote_table_exists($conn, 'quote_headers')) {
        return $quotes;
    }

    $sql = "
        SELECT
            quote_id,
            quote_number,
            client_name,
            customer_id,
            quote_date,
            expiry_date,
            total_amount,
            created_at,
            updated_at
        FROM quote_headers
        WHERE (created_by = ? OR updated_by = ?)
    ";

    if ($query !== '') {
        $sql .= "
            AND (
                quote_number LIKE ?
                OR client_name LIKE ?
                OR customer_id LIKE ?
            )
        ";
    }

    $sql .= " ORDER BY updated_at DESC, quote_id DESC LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $quotes;
    }

    if ($query !== '') {
        $searchPattern = '%' . $query . '%';
        $stmt->bind_param('sssssi', $userName, $userName, $searchPattern, $searchPattern, $searchPattern, $limit);
    } else {
        $stmt->bind_param('ssi', $userName, $userName, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($result && ($row = $result->fetch_assoc())) {
        $row['quote_id'] = (int)($row['quote_id'] ?? 0);
        $row['total_amount'] = (float)($row['total_amount'] ?? 0);
        $quotes[] = $row;
    }
    $stmt->close();

    return $quotes;
}

function get_recent_quotes(mysqli $conn, $query = '', $limit = 100)
{
    $quotes = [];
    $query = trim((string)$query);
    $limit = max(1, min(250, (int)$limit));

    if (!quote_table_exists($conn, 'quote_headers')) {
        return $quotes;
    }

    $hasDebtors = quote_table_exists($conn, 'debtors');
    $sql = "
        SELECT
            q.quote_id,
            q.quote_number,
            q.client_name,
            q.customer_id,
            q.quote_date,
            q.expiry_date,
            q.total_amount,
            q.created_by,
            q.updated_by,
            q.updated_at" . ($hasDebtors ? ",
            d.email AS customer_email" : ",
            '' AS customer_email") . "
        FROM quote_headers q
        " . ($hasDebtors ? "LEFT JOIN debtors d ON d.itemcode = q.customer_id" : "") . "
    ";

    if ($query !== '') {
        $sql .= "
            WHERE (
                q.quote_number LIKE ?
                OR q.client_name LIKE ?
                OR q.customer_id LIKE ?
            )
        ";
    }

    $sql .= " ORDER BY q.updated_at DESC, q.quote_id DESC LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $quotes;
    }

    if ($query !== '') {
        $searchPattern = '%' . $query . '%';
        $stmt->bind_param('sssi', $searchPattern, $searchPattern, $searchPattern, $limit);
    } else {
        $stmt->bind_param('i', $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($result && ($row = $result->fetch_assoc())) {
        $row['quote_id'] = (int)($row['quote_id'] ?? 0);
        $row['total_amount'] = (float)($row['total_amount'] ?? 0);
        $quotes[] = $row;
    }
    $stmt->close();

    return $quotes;
}

function get_quote_builtin_default_terms()
{
    return "1. Sample retention is 30 days post-reporting unless otherwise negotiated.\n"
        . "2. Analysis will be performed in accordance with ISO 17025 standards where applicable.\n"
        . "3. Quote is valid for the matrices and quantities listed above only.";
}

function get_quote_default_terms($conn = null)
{
    $fallback = get_quote_builtin_default_terms();

    if (!$conn instanceof mysqli || !quote_table_exists($conn, 'config')) {
        return $fallback;
    }

    $stmt = $conn->prepare("SELECT confvalue FROM config WHERE confname = ? LIMIT 1");
    if (!$stmt) {
        return $fallback;
    }

    $configName = 'quote_default_terms';
    $stmt->bind_param('s', $configName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? ($result->fetch_assoc() ?: null) : null;
    $stmt->close();

    if (!$row || !array_key_exists('confvalue', $row)) {
        return $fallback;
    }

    return (string)$row['confvalue'];
}

function save_quote_default_terms(mysqli $conn, $termsText)
{
    if (!quote_table_exists($conn, 'config')) {
        throw new RuntimeException('Config table is not available for saving default quote terms.');
    }

    $configName = 'quote_default_terms';
    $configValue = trim((string)$termsText);

    $selectStmt = $conn->prepare("SELECT confname FROM config WHERE confname = ? LIMIT 1");
    if (!$selectStmt) {
        throw new RuntimeException('Failed to prepare default terms lookup: ' . $conn->error);
    }

    $selectStmt->bind_param('s', $configName);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $selectStmt->close();

    if ($exists) {
        $updateStmt = $conn->prepare("UPDATE config SET confvalue = ? WHERE confname = ?");
        if (!$updateStmt) {
            throw new RuntimeException('Failed to prepare default terms update: ' . $conn->error);
        }
        $updateStmt->bind_param('ss', $configValue, $configName);
        $updateStmt->execute();
        $updateStmt->close();
        return;
    }

    $configType = 'text';
    $insertStmt = $conn->prepare("INSERT INTO config (confname, confvalue, type) VALUES (?, ?, ?)");
    if (!$insertStmt) {
        throw new RuntimeException('Failed to prepare default terms insert: ' . $conn->error);
    }
    $insertStmt->bind_param('sss', $configName, $configValue, $configType);
    $insertStmt->execute();
    $insertStmt->close();
}

function generate_quote_number(mysqli $conn)
{
    $prefix = 'Q-' . date('Ymd') . '-';
    $stmt = $conn->prepare("SELECT quote_number FROM quote_headers WHERE quote_number LIKE CONCAT(?, '%') ORDER BY quote_id DESC LIMIT 1");
    if (!$stmt) {
        return $prefix . '001';
    }

    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? ($result->fetch_assoc() ?: []) : [];
    $stmt->close();

    if (empty($row['quote_number'])) {
        return $prefix . '001';
    }

    $lastSequence = (int)substr((string)$row['quote_number'], -3);

    return $prefix . str_pad((string)($lastSequence + 1), 3, '0', STR_PAD_LEFT);
}

function get_quote_form_defaults(mysqli $conn)
{
    return [
        'quote_id' => '',
        'quote_number' => generate_quote_number($conn),
        'customer_id' => '',
        'client_name' => '',
        'client_attention' => '',
        'client_address' => '',
        'client_city' => '',
        'project_name' => '',
        'matrix_name' => '',
        'turnaround_time' => 'Standard (7-10 Business Days)',
        'quote_date' => date('Y-m-d'),
        'expiry_date' => date('Y-m-d', strtotime('+30 days')),
        'discount_percent' => '0.00',
        'terms_text' => get_quote_default_terms($conn),
    ];
}

function get_quote_default_items()
{
    return [
        [
            'parameter_id' => '',
            'standard_id' => '',
            'test_code' => '',
            'description_text' => '',
            'method_text' => '',
            'quantity' => '1.00',
            'unit_price' => '0.00',
        ],
    ];
}

  function normalize_quote_form_state(mysqli $conn, array $quote = null)
{
    $formData = get_quote_form_defaults($conn);
    $formItems = get_quote_default_items();

    if ($quote) {
        $formData = array_merge($formData, $quote);
        if (!empty($quote['items']) && is_array($quote['items'])) {
            $formItems = $quote['items'];
        }
    }

    return [
        'formData' => $formData,
        'formItems' => $formItems,
    ];
}

function quote_clean_text($value)
{
    return trim((string)$value);
}

function quote_clean_decimal($value)
{
    $normalized = str_replace(',', '', trim((string)$value));
    return is_numeric($normalized) ? (float)$normalized : 0.0;
}

function collect_quote_items_from_request(array $request)
{
    $parameterIds = isset($request['parameter_id']) && is_array($request['parameter_id']) ? $request['parameter_id'] : [];
    $standardIds = isset($request['standard_id']) && is_array($request['standard_id']) ? $request['standard_id'] : [];
    $codes = isset($request['item_code']) && is_array($request['item_code']) ? $request['item_code'] : [];
    $descriptions = isset($request['item_description']) && is_array($request['item_description']) ? $request['item_description'] : [];
    $methods = isset($request['item_method']) && is_array($request['item_method']) ? $request['item_method'] : [];
    $quantities = isset($request['item_qty']) && is_array($request['item_qty']) ? $request['item_qty'] : [];
    $prices = isset($request['item_unit_price']) && is_array($request['item_unit_price']) ? $request['item_unit_price'] : [];

    $rowCount = max(count($parameterIds), count($standardIds), count($codes), count($descriptions), count($methods), count($quantities), count($prices));
    $items = [];

    for ($i = 0; $i < $rowCount; $i++) {
        $parameterId = (int)($parameterIds[$i] ?? 0);
        $standardId = (int)($standardIds[$i] ?? 0);
        $description = quote_clean_text($descriptions[$i] ?? '');
        $testCode = quote_clean_text($codes[$i] ?? '');
        $method = quote_clean_text($methods[$i] ?? '');
        $quantity = quote_clean_decimal($quantities[$i] ?? 0);
        $unitPrice = quote_clean_decimal($prices[$i] ?? 0);

        if ($parameterId <= 0 && $description === '' && $testCode === '' && $method === '' && $unitPrice <= 0) {
            continue;
        }

        $lineTotal = round($quantity * $unitPrice, 2);
        $items[] = [
            'line_order' => count($items) + 1,
            'parameter_id' => $parameterId,
            'standard_id' => $standardId,
            'test_code' => $testCode,
            'description_text' => $description,
            'method_text' => $method,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ];
    }

    return $items;
}

function save_quote(mysqli $conn, array $payload, array $items)
{
    if (empty($items)) {
        throw new InvalidArgumentException('At least one quote line item is required.');
    }

    $quoteId = isset($payload['quote_id']) ? (int)$payload['quote_id'] : 0;
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += (float)$item['line_total'];
    }
    $subtotal = round($subtotal, 2);

    $discountPercent = quote_clean_decimal($payload['discount_percent'] ?? 0);
    $discountAmount = round($subtotal * ($discountPercent / 100), 2);
    $totalAmount = round($subtotal - $discountAmount, 2);

    $quoteNumber = quote_clean_text($payload['quote_number'] ?? '');
    if ($quoteNumber === '') {
        $quoteNumber = generate_quote_number($conn);
    }

    $userName = quote_clean_text($payload['user_name'] ?? ($_SESSION['UserID'] ?? $_SESSION['User_name'] ?? $_SESSION['username'] ?? 'system'));
    $customerId = quote_clean_text($payload['customer_id'] ?? '');
    $clientName = quote_clean_text($payload['client_name'] ?? '');
    $quoteDate = quote_clean_text($payload['quote_date'] ?? '');
    $expiryDate = quote_clean_text($payload['expiry_date'] ?? '');

    if ($clientName === '') {
        throw new InvalidArgumentException('Client name is required.');
    }
    if ($quoteDate === '' || $expiryDate === '') {
        throw new InvalidArgumentException('Quote date and expiry date are required.');
    }

    $conn->begin_transaction();

    try {
        if ($quoteId > 0) {
            $updateSql = "
                UPDATE quote_headers
                SET
                    quote_number = ?,
                    customer_id = ?,
                    client_name = ?,
                    client_attention = ?,
                    client_address = ?,
                    client_city = ?,
                    project_name = ?,
                    matrix_name = ?,
                    turnaround_time = ?,
                    quote_date = ?,
                    expiry_date = ?,
                    subtotal = ?,
                    discount_percent = ?,
                    discount_amount = ?,
                    total_amount = ?,
                    terms_text = ?,
                    updated_by = ?
                WHERE quote_id = ?
            ";
            $stmt = $conn->prepare($updateSql);
            if (!$stmt) {
                throw new RuntimeException('Failed to prepare quote update: ' . $conn->error);
            }

            $clientAttention = quote_clean_text($payload['client_attention'] ?? '');
            $clientAddress = quote_clean_text($payload['client_address'] ?? '');
            $clientCity = quote_clean_text($payload['client_city'] ?? '');
            $projectName = quote_clean_text($payload['project_name'] ?? '');
            $matrixName = quote_clean_text($payload['matrix_name'] ?? '');
            $turnaround = quote_clean_text($payload['turnaround_time'] ?? '');
            $termsText = quote_clean_text($payload['terms_text'] ?? '');

            $stmt->bind_param(
                'sssssssssssddddssi',
                $quoteNumber,
                $customerId,
                $clientName,
                $clientAttention,
                $clientAddress,
                $clientCity,
                $projectName,
                $matrixName,
                $turnaround,
                $quoteDate,
                $expiryDate,
                $subtotal,
                $discountPercent,
                $discountAmount,
                $totalAmount,
                $termsText,
                $userName,
                $quoteId
            );
            $stmt->execute();
            $stmt->close();

            $deleteItems = $conn->prepare("DELETE FROM quote_items WHERE quote_id = ?");
            if (!$deleteItems) {
                throw new RuntimeException('Failed to prepare existing quote items cleanup: ' . $conn->error);
            }
            $deleteItems->bind_param('i', $quoteId);
            $deleteItems->execute();
            $deleteItems->close();
        } else {
            $insertSql = "
                INSERT INTO quote_headers (
                    quote_number,
                    customer_id,
                    client_name,
                    client_attention,
                    client_address,
                    client_city,
                    project_name,
                    matrix_name,
                    turnaround_time,
                    quote_date,
                    expiry_date,
                    subtotal,
                    discount_percent,
                    discount_amount,
                    total_amount,
                    terms_text,
                    created_by,
                    updated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($insertSql);
            if (!$stmt) {
                throw new RuntimeException('Failed to prepare quote insert: ' . $conn->error);
            }

            $clientAttention = quote_clean_text($payload['client_attention'] ?? '');
            $clientAddress = quote_clean_text($payload['client_address'] ?? '');
            $clientCity = quote_clean_text($payload['client_city'] ?? '');
            $projectName = quote_clean_text($payload['project_name'] ?? '');
            $matrixName = quote_clean_text($payload['matrix_name'] ?? '');
            $turnaround = quote_clean_text($payload['turnaround_time'] ?? '');
            $termsText = quote_clean_text($payload['terms_text'] ?? '');

            $stmt->bind_param(
                'sssssssssssddddsss',
                $quoteNumber,
                $customerId,
                $clientName,
                $clientAttention,
                $clientAddress,
                $clientCity,
                $projectName,
                $matrixName,
                $turnaround,
                $quoteDate,
                $expiryDate,
                $subtotal,
                $discountPercent,
                $discountAmount,
                $totalAmount,
                $termsText,
                $userName,
                $userName
            );
            $stmt->execute();
            $quoteId = (int)$stmt->insert_id;
            $stmt->close();
        }

        $itemStmt = $conn->prepare("
            INSERT INTO quote_items (
                quote_id, line_order, parameter_id, standard_id, test_code, description_text, method_text, quantity, unit_price, line_total
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$itemStmt) {
            throw new RuntimeException('Failed to prepare quote item insert: ' . $conn->error);
        }

        foreach ($items as $item) {
            $lineOrder = (int)$item['line_order'];
            $parameterId = !empty($item['parameter_id']) ? (int)$item['parameter_id'] : null;
            $standardId = !empty($item['standard_id']) ? (int)$item['standard_id'] : null;
            $testCode = (string)$item['test_code'];
            $descriptionText = (string)$item['description_text'];
            $methodText = (string)$item['method_text'];
            $quantity = (float)$item['quantity'];
            $unitPrice = (float)$item['unit_price'];
            $lineTotal = (float)$item['line_total'];

            $itemStmt->bind_param(
                'iiiisssddd',
                $quoteId,
                $lineOrder,
                $parameterId,
                $standardId,
                $testCode,
                $descriptionText,
                $methodText,
                $quantity,
                $unitPrice,
                $lineTotal
            );
            $itemStmt->execute();
        }
        $itemStmt->close();

        $conn->commit();

        return $quoteId;
    } catch (Throwable $throwable) {
        $conn->rollback();
        throw $throwable;
    }
}

function get_quote_by_id(mysqli $conn, $quoteId)
{
    $quoteId = (int)$quoteId;
    if ($quoteId <= 0) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM quote_headers WHERE quote_id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $quoteId);
    $stmt->execute();
    $result = $stmt->get_result();
    $quote = $result ? ($result->fetch_assoc() ?: null) : null;
    $stmt->close();

    if (!$quote) {
        return null;
    }

    $itemStmt = $conn->prepare("
        SELECT
            qi.*,
            tp.ParameterName,
            ts.StandardName
        FROM quote_items qi
        LEFT JOIN testparameters tp ON tp.ParameterID = qi.parameter_id
        LEFT JOIN teststandards ts ON ts.StandardID = qi.standard_id
        WHERE qi.quote_id = ?
        ORDER BY qi.line_order ASC, qi.item_id ASC
    ");
    if (!$itemStmt) {
        $quote['items'] = [];
        return $quote;
    }

    $itemStmt->bind_param('i', $quoteId);
    $itemStmt->execute();
    $itemResult = $itemStmt->get_result();
    $items = [];
    while ($itemResult && ($row = $itemResult->fetch_assoc())) {
        $parameterName = trim((string)($row['ParameterName'] ?? ''));
        $standardName = trim((string)($row['StandardName'] ?? ''));
        $row['parameter_label'] = trim($parameterName . ($standardName !== '' ? ' - ' . $standardName : ''));
        $items[] = $row;
    }
    $itemStmt->close();

    $quote['items'] = $items;

    return $quote;
}

function quote_currency($amount)
{
    return 'KES' . number_format((float)$amount, 2);
}

function quote_html_lines($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function quote_find_branding_asset($baseDir, array $preferredNames)
{
    $baseDir = rtrim((string)$baseDir, '/\\');
    if ($baseDir === '' || !is_dir($baseDir)) {
        return null;
    }

    foreach ($preferredNames as $preferredName) {
        $path = $baseDir . DIRECTORY_SEPARATOR . $preferredName;
        if (is_file($path) && is_readable($path)) {
            return str_replace('\\', '/', realpath($path) ?: $path);
        }
    }

    return null;
}

function quote_get_pdf_branding_assets()
{
    $logosDir = __DIR__ . '/../logos';

    return [
        'logo_path' => quote_find_branding_asset($logosDir, ['logo.png', 'logo.jpg', 'logo.jpeg', 'logo.gif']),
        'labcode_path' => quote_find_branding_asset($logosDir, ['labcode.png', 'labcode.jpg', 'labcode.jpeg', 'labcode.gif']),
    ];
}

function get_quote_email_template(mysqli $conn)
{
    $defaultSubject = 'Quotation {{quote_number}}';
    $defaultBody = '<p>Dear {{client_name}},</p>'
        . '<p>Please find attached your quotation {{quote_number}} dated {{quote_date}}.</p>'
        . '<p>Regards,<br>{{company_name}}</p>';

    return [
        'subject' => (string)quote_get_config_value($conn, 'quote_email_subject', $defaultSubject),
        'body' => (string)quote_get_config_value($conn, 'quote_email_body', $defaultBody),
    ];
}

function render_quote_email_text($text, array $context)
{
    $replacements = [
        '{{quote_number}}' => htmlspecialchars((string)($context['quote_number'] ?? ''), ENT_QUOTES, 'UTF-8'),
        '{{client_name}}' => htmlspecialchars((string)($context['client_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
        '{{quote_date}}' => htmlspecialchars((string)($context['quote_date'] ?? ''), ENT_QUOTES, 'UTF-8'),
        '{{company_name}}' => htmlspecialchars((string)($context['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
    ];

    return strtr((string)$text, $replacements);
}

function quote_pdf_date($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d-m-Y', $timestamp);
}

function quote_pdf_text($value)
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function quote_pdf_quantity($value)
{
    $numeric = (float)$value;
    if (abs($numeric - round($numeric)) < 0.00001) {
        return number_format($numeric, 0);
    }

    return rtrim(rtrim(number_format($numeric, 2, '.', ''), '0'), '.');
}

function quote_group_items_by_standard(array $items, array $quote = [])
{
    $groups = [];

    foreach ($items as $index => $item) {
        $standardId = (int)($item['standard_id'] ?? $item['StandardID'] ?? 0);
        $standardName = trim((string)($item['StandardName'] ?? $item['standard_name'] ?? ''));
        $projectName = trim((string)($item['project_name'] ?? ''));
        $groupLabel = $standardName !== '' ? $standardName : $projectName;

        if ($groupLabel === '') {
            $groupLabel = $standardId > 0 ? 'Standard #' . $standardId : 'Selected Tests';
        }

        $groupKey = $standardId > 0
            ? 'std_' . $standardId
            : 'lbl_' . md5(strtolower($groupLabel) . '_' . $index);

        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = [
                'standard_id' => $standardId,
                'label' => $groupLabel,
                'items' => [],
                'tests' => [],
                'quantities' => [],
            ];
        }

        $groups[$groupKey]['items'][] = $item;

        $testLabel = trim((string)($item['description_text'] ?? $item['ParameterName'] ?? $item['parameter_name'] ?? $item['parameter_label'] ?? $item['test_code'] ?? ''));
        if ($testLabel !== '' && !in_array($testLabel, $groups[$groupKey]['tests'], true)) {
            $groups[$groupKey]['tests'][] = $testLabel;
        }

        $quantityLabel = quote_pdf_quantity($item['quantity'] ?? 0);
        if ($quantityLabel !== '0' && !in_array($quantityLabel, $groups[$groupKey]['quantities'], true)) {
            $groups[$groupKey]['quantities'][] = $quantityLabel;
        }
    }

    if (!empty($groups)) {
        return array_values($groups);
    }

    $fallbackProject = trim((string)($quote['project_name'] ?? ''));
    $fallbackMatrix = trim((string)($quote['matrix_name'] ?? ''));
    if ($fallbackProject === '' && $fallbackMatrix === '') {
        return [];
    }

    return [[
        'standard_id' => 0,
        'label' => $fallbackProject !== '' ? $fallbackProject : 'Selected Tests',
        'items' => [],
        'tests' => [],
        'quantities' => [],
        'matrix_name' => $fallbackMatrix,
    ]];
}

function quote_group_meta_text(array $group)
{
    $meta = [];
    $testCount = count($group['tests'] ?? []);
    if ($testCount > 0) {
        $meta[] = $testCount . ' test(s)';
    }

    $quantities = isset($group['quantities']) && is_array($group['quantities']) ? $group['quantities'] : [];
    if (count($quantities) === 1) {
        $meta[] = $quantities[0] . ' sample(s)';
    } elseif (count($quantities) > 1) {
        $meta[] = 'Multiple sample counts';
    }

    return implode(' | ', $meta);
}

function quote_has_grouped_item_data(array $groups)
{
    foreach ($groups as $group) {
        if (!empty($group['items']) || !empty($group['tests'])) {
            return true;
        }
    }

    return false;
}

function quote_build_scope_summary_html(array $quote, array $groups)
{
    if (empty($groups) || !quote_has_grouped_item_data($groups)) {
        return '';
    }

    $rowsHtml = '';
    foreach ($groups as $group) {
        $metaText = quote_group_meta_text($group);
        $tests = isset($group['tests']) && is_array($group['tests']) ? $group['tests'] : [];
        $previewTests = array_slice($tests, 0, 4);
        $previewText = '';

        if (!empty($previewTests)) {
            $previewText = implode(', ', $previewTests);
            if (count($tests) > count($previewTests)) {
                $previewText .= ' +' . (count($tests) - count($previewTests)) . ' more';
            }
        }

        $rowsHtml .= '<tr><td style="border:1px solid #d7dfe8; padding:7px;">'
            . '<strong>' . quote_pdf_text($group['label'] ?? 'Selected Tests') . '</strong>'
            . ($metaText !== '' ? '<br><span style="font-size:8px; color:#5c6f82;">' . quote_pdf_text($metaText) . '</span>' : '')
            . ($previewText !== '' ? '<br><span style="font-size:8px; color:#2f3a45;">' . quote_pdf_text($previewText) . '</span>' : '')
            . '</td></tr>';
    }

    return '<table cellpadding="0" cellspacing="0" border="0" width="100%">' . $rowsHtml . '</table>';
}

function quote_build_grouped_item_rows_html(array $groups)
{
    $html = '';

    foreach ($groups as $group) {
        $groupLabel = trim((string)($group['label'] ?? 'Selected Tests'));
        $metaText = quote_group_meta_text($group);

        $html .= '<tr>'
            . '<td colspan="4" style="border:1px solid #d7dfe8; padding:7px; background-color:#eef4fb; color:#1f3c5a;">'
            . '<strong>' . quote_pdf_text($groupLabel) . '</strong>'
            . ($metaText !== '' ? '<br><span style="font-size:8px; color:#4f6478;">' . quote_pdf_text($metaText) . '</span>' : '')
            . '</td>'
            . '</tr>';

        foreach (($group['items'] ?? []) as $item) {
            $parameterName = trim((string)($item['description_text'] ?? $item['parameter_label'] ?? $item['ParameterName'] ?? $item['parameter_name'] ?? $item['test_code'] ?? ''));
            $quantity = (float)($item['quantity'] ?? 0);
            $unitPrice = (float)($item['unit_price'] ?? 0);
            $lineTotal = (float)($item['line_total'] ?? 0);

            $html .= '<tr>'
                . '<td style="border:1px solid #d7dfe8; padding:7px; width:52%;">' . quote_pdf_text($parameterName) . '</td>'
                . '<td style="border:1px solid #d7dfe8; padding:7px; width:16%; text-align:right;">' . quote_pdf_quantity($quantity) . '</td>'
                . '<td style="border:1px solid #d7dfe8; padding:7px; width:16%; text-align:right;">' . quote_currency($unitPrice) . '</td>'
                . '<td style="border:1px solid #d7dfe8; padding:7px; width:16%; text-align:right;">' . quote_currency($lineTotal) . '</td>'
                . '</tr>';
        }
    }

    return $html;
}

function quote_build_header_scope_lines(array $quote, array $groups, $maxLines = 3)
{
    $lines = [];
    $hasGroupedItemData = quote_has_grouped_item_data($groups);

    foreach ($hasGroupedItemData ? $groups : [] as $group) {
        $label = trim((string)($group['label'] ?? ''));
        if ($label === '') {
            continue;
        }

        $metaText = quote_group_meta_text($group);
        $lines[] = $metaText !== '' ? ($label . ' (' . $metaText . ')') : $label;
    }

    if (empty($lines)) {
        $fallbackProject = trim((string)($quote['project_name'] ?? ''));
        $fallbackMatrix = trim((string)($quote['matrix_name'] ?? ''));
        if ($fallbackProject !== '') {
            $lines[] = 'Project: ' . $fallbackProject;
        }
        if ($fallbackMatrix !== '') {
            $lines[] = 'Matrix: ' . $fallbackMatrix;
        }
    }

    $maxLines = max(1, (int)$maxLines);
    if (count($lines) > $maxLines) {
        $visibleLines = array_slice($lines, 0, $maxLines - 1);
        $visibleLines[] = '+' . (count($lines) - count($visibleLines)) . ' more standard(s)';
        return $visibleLines;
    }

    return $lines;
}

function quote_build_client_lines(array $quote)
{
    $lines = [];

    $clientName = trim((string)($quote['client_name'] ?? ''));
    if ($clientName !== '') {
        $lines[] = $clientName;
    }

    $clientAttention = trim((string)($quote['client_attention'] ?? ''));
    if ($clientAttention !== '') {
        $lines[] = 'Attn: ' . $clientAttention;
    }

    $clientAddress = trim((string)($quote['client_address'] ?? ''));
    if ($clientAddress !== '') {
        foreach (preg_split('/\r\n|\r|\n/', $clientAddress) as $addressLine) {
            $addressLine = trim((string)$addressLine);
            if ($addressLine !== '') {
                $lines[] = $addressLine;
            }
        }
    }

    $clientCity = trim((string)($quote['client_city'] ?? ''));
    if ($clientCity !== '') {
        $lines[] = $clientCity;
    }

    return $lines;
}

function quote_build_footer_group_html(array $items)
{
    $itemGroups = quote_group_items_by_standard($items);
    $groups = [];

    foreach ($itemGroups as $group) {
        $groupLabel = trim((string)($group['label'] ?? 'Selected Tests'));
        $tests = isset($group['tests']) && is_array($group['tests']) ? $group['tests'] : [];
        if (empty($tests)) {
            continue;
        }

        $groups[$groupLabel] = $tests;
    }

    if (empty($groups)) {
        return '';
    }

    $html = '<table cellpadding="4" cellspacing="0" border="0" width="100%"><tr>';
    $columnIndex = 0;

    foreach ($groups as $groupLabel => $tests) {
        if ($columnIndex > 0 && $columnIndex % 2 === 0) {
            $html .= '</tr><tr>';
        }

        $testHtml = '';
        foreach ($tests as $test) {
            $testHtml .= '<div style="font-size:7.5px; line-height:1.25;">' . quote_pdf_text($test) . '</div>';
        }

        $html .= '<td width="50%" style="vertical-align:top;">'
            . '<table cellpadding="3" cellspacing="0" border="1" width="100%" style="border-color:#cfd8e3;">'
            . '<tr><td style="background-color:#eef4fb; color:#1f3c5a; font-size:8px; font-weight:bold;">' . quote_pdf_text($groupLabel) . '</td></tr>'
            . '<tr><td style="font-size:7.5px; color:#2f3a45;">' . $testHtml . '</td></tr>'
            . '</table>'
            . '</td>';

        $columnIndex++;
    }

    if ($columnIndex % 2 === 1) {
        $html .= '<td width="50%"></td>';
    }

    $html .= '</tr></table>';

    return $html;
}

function render_quote_pdf(mysqli $conn, array $quote)
{
    if (!class_exists('TCPDF')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $company = get_quote_company_profile($conn);
    $branding = quote_get_pdf_branding_assets();
    $items = isset($quote['items']) && is_array($quote['items']) ? $quote['items'] : [];
    $itemGroups = quote_group_items_by_standard($items);
    $scopeGroups = quote_group_items_by_standard($items, $quote);
    $quote['scope_groups'] = $scopeGroups;
    $companyName = quote_pdf_text($company['company_name'] ?? '');
    $companyAddress = array_filter([
        quote_pdf_text($company['address'] ?? ''),
        quote_pdf_text($company['address1'] ?? ''),
        quote_pdf_text($company['address2'] ?? ''),
        quote_pdf_text($company['address3'] ?? ''),
    ]);
    $companyMeta = array_filter([
        quote_pdf_text($company['telephone'] ?? ''),
        quote_pdf_text($company['email'] ?? ''),
    ]);
    $accreditationText = quote_pdf_text($company['accreditation_text'] ?? '');
    $terms = trim((string)($quote['terms_text'] ?? ''));
    $termsHtml = '';
    if ($terms !== '') {
        $lines = preg_split('/\r\n|\r|\n/', $terms);
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $termsHtml .= '<p style="margin:0 0 6px 0;">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    $itemRowsHtml = quote_build_grouped_item_rows_html($itemGroups);

    if ($itemRowsHtml === '') {
        $itemRowsHtml = '<tr><td colspan="4" style="border:1px solid #d7dfe8; padding:9px; text-align:center; color:#6c757d;">No quote items available.</td></tr>';
    }

    $pdfHtml = '
    <style>
        body { font-family: helvetica; color: #333333; }
        .section-title { color: #5c6f82; font-size: 10px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #d6dee8; padding-bottom: 4px; margin-bottom: 8px; }
        .quote-table th { background-color: #2f6ea3; color: #ffffff; font-size: 9px; font-weight: bold; }
        .quote-table td { font-size: 9px; }
    </style>
    <div class="section-title" style="margin-top:4px;">Quoted Items</div>
    <table class="quote-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        <thead>
            <tr>
                <th style="padding:8px; width:52%; border:1px solid #2f6ea3; text-align:left;">Parameter Name</th>
                <th style="padding:8px; width:16%; border:1px solid #2f6ea3; text-align:right;">No. of Samples</th>
                <th style="padding:8px; width:16%; border:1px solid #2f6ea3; text-align:right;">Price</th>
                <th style="padding:8px; width:16%; border:1px solid #2f6ea3; text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>' . $itemRowsHtml . '</tbody>
    </table>
    <div style="margin-top:10px; font-size:10px;"><strong>TAT:</strong> ' . quote_pdf_text($quote['turnaround_time'] ?? '') . '</div>

    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:14px;">
        <tr>
            <td width="58%"></td>
            <td width="42%">
                <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 9px;">
                    <tr>
                        <td width="60%" style="padding:6px 0;"><strong>Subtotal:</strong></td>
                        <td width="40%" align="right">' . quote_currency($quote['subtotal'] ?? 0) . '</td>
                    </tr>
                    <tr>
                        <td width="60%" style="padding:6px 0;"><strong>Volume Discount (' . number_format((float)($quote['discount_percent'] ?? 0), 2) . '%):</strong></td>
                        <td width="40%" align="right">-' . quote_currency($quote['discount_amount'] ?? 0) . '</td>
                    </tr>
                    <tr>
                        <td width="60%" style="border-top:2px solid #2c3e50; padding-top:8px;"><strong>Total Amount:</strong></td>
                        <td width="40%" align="right" style="border-top:2px solid #2c3e50; padding-top:8px;"><strong>' . quote_currency($quote['total_amount'] ?? 0) . '</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-top:22px;">
        <div class="section-title">Terms And Conditions</div>
        <div style="font-size:10px;">' . $termsHtml . '</div>
    </div>';

    $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
        public $quoteCompany = [];
        public $quoteData = [];
        public $logoPath = '';
        public $labCodePath = '';

        private function fitText($text, $width, $maxFontSize, $minFontSize = 7)
        {
            $fontSize = $maxFontSize;
            $this->SetFont('helvetica', '', $fontSize);
            while ($this->GetStringWidth($text) > $width && $fontSize > $minFontSize) {
                $fontSize--;
                $this->SetFont('helvetica', '', $fontSize);
            }

            return $fontSize;
        }

        public function Header()
        {
            $company = is_array($this->quoteCompany) ? $this->quoteCompany : [];
            $quote = is_array($this->quoteData) ? $this->quoteData : [];
            $clientLines = quote_build_client_lines($quote);

            $this->SetDrawColor(185, 197, 210);
            $this->SetLineWidth(0.2);
            $this->Rect(10, 10, 190, 48, 'D');
            $this->Rect(10, 58, 190, 26, 'D');
            $this->Line(105, 10, 105, 58);
            $this->Line(105, 58, 105, 84);

            if ($this->logoPath && is_file($this->logoPath)) {
                $this->Image($this->logoPath, 12, 12, 38, 10);
            }

            if ($this->labCodePath && is_file($this->labCodePath)) {
                $this->Image($this->labCodePath, 87, 10, 18, 5);
            }

            $this->SetXY(12, 24);
            $companyName = trim((string)($company['company_name'] ?? ''));
            $fontSize = $this->fitText($companyName, 88, 10, 8);
            $this->SetFont('helvetica', 'B', $fontSize);
            $this->MultiCell(88, 5, $companyName, 0, 'L', 0, 1, '', '', true);

            $this->SetFont('helvetica', '', 8);
            $companyLines = array_filter([
                trim((string)($company['address'] ?? '')),
                trim((string)($company['address1'] ?? '')),
                trim((string)($company['address2'] ?? '')),
                trim((string)($company['address3'] ?? '')),
                trim((string)($company['telephone'] ?? '')),
                trim((string)($company['email'] ?? '')),
            ]);
            foreach ($companyLines as $line) {
                $this->SetX(12);
                $this->MultiCell(88, 4, $line, 0, 'L', 0, 1, '', '', true);
            }

            if (!empty($company['accreditation_text'])) {
                $this->SetX(12);
                $this->SetFont('helvetica', 'I', 7);
                $this->MultiCell(88, 4, trim((string)$company['accreditation_text']), 0, 'L', 0, 1, '', '', true);
            }

            $this->SetXY(108, 12);
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(80, 6, 'LABORATORY QUOTATION', 0, 1, 'L');
            $this->SetFont('helvetica', '', 8.5);
            $this->SetX(108);
            $this->Cell(80, 4, 'Quote #: ' . trim((string)($quote['quote_number'] ?? '')), 0, 1, 'L');
            $this->SetX(108);
            $this->Cell(80, 4, 'Quote Date: ' . quote_pdf_date($quote['quote_date'] ?? ''), 0, 1, 'L');
            $this->SetX(108);
            $this->Cell(80, 4, 'Expiry Date: ' . quote_pdf_date($quote['expiry_date'] ?? ''), 0, 1, 'L');

            $this->SetXY(12, 61);
            $this->SetFont('helvetica', 'B', 8);
            $this->Cell(90, 5, 'Client Information', 0, 1, 'L');
            $this->SetFont('helvetica', '', 8);
            $this->SetX(12);
            $clientText = trim(implode("\n", $clientLines));
            if ($clientText === '') {
                $clientText = 'No client information available.';
            }
            $this->MultiCell(90, 4, $clientText, 0, 'L', 0, 1, '', '', true);

            $this->SetXY(108, 61);
            $this->SetFont('helvetica', 'B', 8);
            $this->Cell(88, 5, 'Commercial Summary', 0, 1, 'L');
            $this->SetFont('helvetica', '', 8);
            $this->SetX(108);
            $this->Cell(88, 4, 'Subtotal: ' . quote_currency($quote['subtotal'] ?? 0), 0, 1, 'L');
            $this->SetX(108);
            $this->Cell(88, 4, 'Discount: ' . number_format((float)($quote['discount_percent'] ?? 0), 2) . '%', 0, 1, 'L');
            $this->SetX(108);
            $this->Cell(88, 4, 'Total Amount: ' . quote_currency($quote['total_amount'] ?? 0), 0, 1, 'L');
        }

        public function Footer()
        {
            $this->SetY(-8);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(120, 132, 145);
            $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    };

    $pdf->SetCreator('LIMS');
    $pdf->SetAuthor($company['company_name'] ?? 'Laboratory');
    $pdf->SetTitle('Quotation ' . ($quote['quote_number'] ?? ''));
    $pdf->quoteCompany = $company;
    $pdf->quoteData = $quote;
    $pdf->logoPath = (string)($branding['logo_path'] ?? '');
    $pdf->labCodePath = (string)($branding['labcode_path'] ?? '');
    $pdf->SetMargins(10, 90, 10);
    $pdf->SetAutoPageBreak(true, 52);
    $pdf->AddPage();
    $pdf->writeHTML($pdfHtml, true, false, true, false, '');

    return $pdf->Output('quote-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)$quote['quote_number']) . '.pdf', 'S');
}

function send_quote_email(mysqli $conn, $quoteId, $recipientEmail = '')
{
    require_once __DIR__ . '/certificate_email_support.php';

    $quoteId = (int)$quoteId;
    if ($quoteId <= 0) {
        throw new InvalidArgumentException('Quote ID is required.');
    }

    $quote = get_quote_by_id($conn, $quoteId);
    if (!$quote) {
        throw new RuntimeException('Quote not found.');
    }

    $customer = get_quote_customer_by_id($conn, (string)($quote['customer_id'] ?? ''));
    $resolvedRecipient = trim((string)$recipientEmail);
    if ($resolvedRecipient === '' && is_array($customer)) {
        $resolvedRecipient = trim((string)($customer['email'] ?? ''));
    }

    if ($resolvedRecipient === '' || !filter_var($resolvedRecipient, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('No valid customer email address is available for this quote.');
    }

    $company = get_quote_company_profile($conn);
    $template = get_quote_email_template($conn);
    $context = [
        'quote_number' => (string)($quote['quote_number'] ?? ''),
        'client_name' => (string)($quote['client_name'] ?? ''),
        'quote_date' => (string)($quote['quote_date'] ?? ''),
        'company_name' => (string)($company['company_name'] ?? ''),
    ];

    $subject = render_quote_email_text((string)$template['subject'], $context);
    $body = render_quote_email_text((string)$template['body'], $context);
    $pdfContent = render_quote_pdf($conn, $quote);
    $filename = 'quote-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)$quote['quote_number']) . '.pdf';

    $logLines = [];
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    configure_certificate_mailer($conn, $mail, $logLines);
    $mail->addAddress($resolvedRecipient, trim((string)($quote['client_name'] ?? 'Customer')));
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body))));
    $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');
    $mail->send();

    return [
        'success' => true,
        'message' => 'Quote emailed successfully.',
        'recipient_email' => $resolvedRecipient,
        'quote_number' => (string)($quote['quote_number'] ?? ''),
    ];
}
