<?php
include('../config.php');

global $db;
// Make sure it IS global, regardless of our context
$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
if(!$db){
    die('Database connection failed');
}


class LimsSalesApi{
    private $db;
    private $config;

    public function __construct($db = null)
    {
        global $host, $DBUser, $DBPassword, $DefaultDatabase;
        
        $this->config = [
            'host' => $host ?? 'localhost',
            'database' => $DefaultDatabase ?? 'mozillaerpv2',
            'username' => $DBUser ?? 'root',
            'password' => $DBPassword ?? '',
            'currency' => 'KES',
            'postinggroup' => 'GENERAL',
            'locationcode' => 'MAIN',
            'uom' => 'PCS',
            'qty' => '1',
            'documenttype' => 1
        ];
        
        if ($db !== null) {
            $this->db = $db;
        } else {
            global $db;
            if (isset($db)) {
                $this->db = $db;
            } else {
                $this->db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);
            }
        }
    }

    public function handleRequest()
    {
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Only POST method allowed'], 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()], 400);
            return;
        }

        $action = $input['action'] ?? '';
        //createStockFromBaseparameter
        switch ($action) {
            case 'register_sample':
                $this->registerSample($input);
                break;
            case 'sync_sample':
                $this->syncSample($input);
                break;
            case 'map_parameter':
                $this->mapParameter($input);
                break;
            case 'create_stock_from_baseparameter':
                $this->createStockFromBaseparameter($input);
                break;
            case 'sync_teststandard':
                $this->syncTeststandard($input);
                break;
            case 'create_inventory':
                $this->createInventory($input);
                break;
            case 'update_inventory':
                $this->updateInventory($input);
                break;
            case 'get_stock_items':
                $this->getStockItems($input);
                break;
            case 'create_customer':
                $this->createCustomer($input);
                break;
            case 'search_customers':
                $this->searchCustomers($input);
                break;
            case 'update_customer':
                $this->updateCustomer($input);
                break;
            case 'delete_customer':
                $this->deleteCustomer($input);
                break;
            case 'create_supplier':
                $this->createSupplier($input);
                break;
            case 'update_supplier':
                $this->updateSupplier($input);
                break;
            case 'delete_supplier':
                $this->deleteSupplier($input);
                break;
            case 'search_suppliers':
                $this->searchSuppliers($input);
                break;
            case 'get_customer':
                $this->getCustomer($input);
                break;
            case 'get_supplier':
                $this->getSupplier($input);
                break;
            case 'search_quotes':
                $this->searchQuotes($input);
                break;
            case 'get_quote':
                $this->getQuote($input);
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    private function registerSample($data)
    {
        $required = ['documentno', 'customercode', 'customername', 'lines'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->jsonResponse(['success' => false, 'message' => "Missing required field: $field"], 400);
                return;
            }
        }

        if (!is_array($data['lines']) || count($data['lines']) === 0) {
            $this->jsonResponse(['success' => false, 'message' => 'At least one line item is required'], 400);
            return;
        }

        $headerResult = $this->insertSalesHeader($data);
        if (!$headerResult) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to insert sales header'], 500);
            return;
        }

        $lineResults = [];
        foreach ($data['lines'] as $line) {
            // Determine itemcode: if `code` looks numeric treat as labid and map to stockmaster, else assume it's an itemcode
            $itemcode = '';
            if (!empty($line['code'])) {
                if (is_numeric($line['code'])) {
                    $stockItem = $this->findStockItemByLabId($line['code']);
                    if ($stockItem) {
                        $itemcode = $stockItem['itemcode'] ?? '';
                    }
                } else {
                    $itemcode = $line['code'];
                }
            }

            if (empty($itemcode)) {
                $default = $this->getDefaultStockItem();
                $itemcode = $default['itemcode'] ?? '';
            }

            // Calculate UnitPrice using existing helper
            $unitPrice = $this->calculatePrice(
                $itemcode,
                $this->config['qty'],
                $this->config['uom'],
                $data['customercode'] ?? ''
            );

            // Ensure UnitPrice is present for insert
            $line['UnitPrice'] = $unitPrice;
            $line['documentno'] = $headerResult['documentno'];

            $lineResult = $this->insertSalesLine($line, $headerResult['entryno']);
            $lineResults[] = $lineResult;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Sample registered successfully',
            'data' => [
                'header' => $headerResult,
                'lines' => $lineResults
            ]
        ]);
    }

    private function calculatePrice($itemcode, $quantity, $uom, $customercode = '')
    {
        // Stage 1: Check customer price -> pass to $userPrice
        // Stage 2: If null, check default price -> pass to $userPrice
        
        $customerPrice = 0;
        $defaultPrice = 0;

        // Stage 1: Check customer-specific price
        if (!empty($customercode)) {
            $stmt = $this->db->prepare("
                SELECT price FROM PriceList 
                WHERE stockcode = ? AND approved = 1 AND units_code = ? 
                AND quantity = ? AND customerCode = ?
            ");
            $stmt->bind_param('ssds', $itemcode, $uom, $quantity, $customercode);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $customerPrice = (float)$row['price'];
            }
        }

        // Stage 2: If customer price is null/0, check default pricelist
        if ($customerPrice > 0) {
            $userPrice = $customerPrice;
        } else {
            $stmt = $this->db->prepare("
                SELECT price FROM PriceList 
                WHERE stockcode = ? AND units_code = ? AND quantity = ? 
                AND (customerCode IS NULL OR customerCode = '')
            ");
            $stmt->bind_param('ssd', $itemcode, $uom, $quantity);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $defaultPrice = (float)$row['price'];
            }
            $userPrice = $defaultPrice;
        }

        return $userPrice;
    }

    
    private function getPrice($itemcode,$customercode = '')
    {
        $customerPrice = 0;
        $defaultPrice = 0;

        // Stage 1: Check customer-specific price
        if (!empty($customercode)) {
            $stmt = $this->db->prepare("
                SELECT price FROM PriceList 
                WHERE stockcode = ? AND approved = 1  AND customerCode = ?
            ");
            $stmt->bind_param('ss', $itemcode, $customercode);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $customerPrice = (float)$row['price'];
            }
        }

        // Stage 2: If customer price is null/0, check default pricelist
        if ($customerPrice > 0) {
            $userPrice = $customerPrice;
        } else {
            $stmt = $this->db->prepare("
                SELECT price FROM PriceList 
                WHERE stockcode = ? AND (customerCode IS NULL OR customerCode = '')
            ");
            $stmt->bind_param('s', $itemcode);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $defaultPrice = (float)$row['price'];
            }
            $userPrice = $defaultPrice;
        }

        return $userPrice;
    }

    private function syncSample($data)
    {
        // Blockchain sends payload directly, not wrapped in 'lims_data'
        $limsData = $data; // Use data directly instead of $data['lims_data']
        
        if (empty($limsData['documentno'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing DocumentNo'], 400);
            return;
        }

        $headerData = [
            'documentno' => $limsData['documentno'],
            'docdate' => $limsData['docdate'] ?? date('Y-m-d H:i:s'),
            'oderdate' => $limsData['oderdate'] ?? $limsData['docdate'] ?? date('Y-m-d H:i:s'),
            'duedate' => $limsData['duedate'] ?? $limsData['docdate'] ?? date('Y-m-d H:i:s'),
            'customercode' => $limsData['customercode'] ?? $limsData['CustomerID'] ?? '',
            'customername' => $limsData['customername'] ?? $limsData['CustomerName'] ?? '',
            'yourreference' => $limsData['yourreference'] ?? $limsData['Orderno'] ?? '',
            'externaldocumentno' => $limsData['externaldocumentno'] ?? $limsData['HeaderID'] ?? '',
            'userid' => $limsData['userid'] ?? 'system',
            'printed' => '1','released' => '1','status' => '1',
            'documenttype' => $this->config['documenttype']
        ];

        $quoteNo = trim((string)($limsData['quoteno'] ?? ''));
        if ($quoteNo === '') {
            $this->jsonResponse(['success' => false, 'message' => 'No quotation linked. Create and link a quotation before the sales order can be raised.'], 400);
            return;
        }

        // Validate the source quotation BEFORE writing the order: a failed
        // lookup used to leave an empty sales header behind.
        $stmt = $this->db->prepare("SELECT code, category, description, unitofmeasure, Quantity, UnitPrice, PartPerUnit, IFNULL(sampleID, '') AS sampleID
            FROM salesline WHERE documenttype = 54 AND documentno = ? ORDER BY entryno ASC");
        $stmt->bind_param("s", $quoteNo);
        $stmt->execute();
        $quoteLines = $stmt->get_result();
        if ($quoteLines->num_rows === 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Quotation ' . $quoteNo . ' not found or has no lines.'], 404);
            return;
        }

        // Upsert on the LIMS document number instead of appending a new order
        // every time the same sample is sent.
        $headerResult = $this->insertSalesHeader($headerData);
        if (!$headerResult) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to sync header'], 500);
            return;
        }

        $this->db->begin_transaction();
        try {
            // Re-syncing rewrites the lines rather than appending a second set.
            $del = $this->db->prepare("DELETE FROM salesline WHERE documenttype = ? AND documentno = ?");
            $del->bind_param('is', $this->config['documenttype'], $headerResult['documentno']);
            $del->execute();

            $lineResults = [];
            while ($ql = $quoteLines->fetch_assoc()) {
                $lineData = [
                    'documentno' => $headerResult['documentno'],
                    'docdate' => $headerResult['docdate'],
                    'code' => trim((string)$ql['code']),
                    'category' => trim((string)($ql['category'] ?? '')),
                    'description' => trim((string)$ql['description']),
                    'Quantity' => (float)($ql['Quantity'] ?? 0),
                    'UnitPrice' => (float)($ql['UnitPrice'] ?? 0),
                    'locationcode' => $this->config['locationcode'],
                    'unitofmeasure' => trim((string)($ql['unitofmeasure'] ?? '')) !== '' ? trim((string)$ql['unitofmeasure']) : $this->config['uom'],
                    'sampleID' => trim((string)($ql['sampleID'] ?? '')),
                    'PartPerUnit' => (float)($ql['PartPerUnit'] ?? 1)
                ];
                $lineResult = $this->insertSalesLine($lineData, $headerResult['entryno']);
                if ($lineResult === false) {
                    throw new RuntimeException('Failed to insert line for ' . $lineData['code']);
                }
                $lineResults[] = $lineResult;
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            $this->jsonResponse(['success' => false, 'message' => 'Failed to sync order lines: ' . $e->getMessage()], 500);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => ($headerResult['created'] ? 'Sales order created' : 'Sales order updated') . ' from quotation ' . $quoteNo,
            'data' => [
                'header' => $headerResult,
                'source_quotation' => $quoteNo,
                'lines' => $lineResults
            ]
        ]);
    }

    private function mapParameter($data)
    {
        // baseparameters.ParameterID maps to stockmaster.labid
        // baseparameters.ParameterName maps to stockmaster.descrip
        // stockmaster.partperunit = 1
        $labId = $data['labid'] ?? null; // ParameterID from blockchain
        $itemCode = $data['itemcode'] ?? null;
        $parameterName = $data['parameterName'] ?? $data['description'] ?? null;
        
        if (!$labId) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing labid (ParameterID)'], 400);
            return;
        }

        $existing = $this->getStockItemByLabId($labId);
        if ($existing) {
            $updates = ['labid = ?'];
            $params = [$labId];
            $types = 's';
            
            if ($itemCode) {
                $updates[] = 'itemcode = ?';
                $params[] = $itemCode;
                $types .= 's';
            }
            if ($parameterName) {
                $updates[] = 'descrip = ?';
                $params[] = $parameterName;
                $types .= 's';
            }
            
            $params[] = $existing['pkey'];
            $types .= 'i';
            
            $sql = "UPDATE stockmaster SET " . implode(', ', $updates) . " WHERE pkey = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
        } else {
            $descrip = $parameterName ?: ($itemCode ?: 'Lab Parameter');
            $stmt = $this->db->prepare(
                "INSERT INTO stockmaster (labid, itemcode, descrip, isstock, postinggroup, partperunit) VALUES (?, ?, ?, 1, 'GENERAL', 1)"
            );
            $stmt->bind_param('sss', $labId, $itemCode, $descrip);
        }

        if ($stmt->execute()) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Parameter mapped successfully',
                'labid' => $labId,
                'itemcode' => $itemCode,
                'parameterName' => $parameterName
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to map parameter'], 500);
        }
    }

    private function createStockFromBaseparameter($data)
    {
        // Create stock item from blockchain baseparameters (simulates Stocks.php)
        // baseparameters.ParameterID -> stockmaster.labid
        // baseparameters.ParameterName -> stockmaster.descrip
        
        $parameterId = $data['ParameterID'] ?? null;
        $parameterName = $data['ParameterName'] ?? null;
        
        if (empty($parameterId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing ParameterID'], 400);
            return;
        }

        $existing = $this->getStockItemByLabId($parameterId);
        // Check for duplicate description
        $searchString = '%' . str_replace(' ', '%', trim($parameterName)) . '%';
        $checkSql = "SELECT * FROM stockmaster WHERE descrip LIKE '" . $this->db->real_escape_string($searchString) . "'";
        $checkResult = $this->db->query($checkSql);
        
        if ($existing) {
            // Update existing
            $sql = sprintf("UPDATE `stockmaster`
                SET `isstock` = '%s',
                    isstock_1 = '%s',
                    isstock_2 = '%s', 
                    isstock_3 = '%s', 
                    isstock_4 = '%s', 
                    isstock_5 = '%s', 
                    isstock_6 = '%s',
                    `barcode` = '%s',
                    `descrip` = '%s',
                    `postinggroup` = '%s',
                    `reorderlevel` = %f,
                    `eoq` = %f,
                    `category` = '%s',
                    `units` = '%s',
                    `inactive` = '%s',
                    `container` = '%s',
                    `sellingprice` = %f,
                    `production` = '%s'
                WHERE `pkey` = %d",
                $data['isstock'] ?? '1',
                $data['isstock_1'] ?? '0',
                $data['isstock_2'] ?? '0',
                $data['isstock_3'] ?? '0',
                $data['isstock_4'] ?? '0',
                $data['isstock_5'] ?? '0',
                $data['isstock_6'] ?? '0',
                $this->db->real_escape_string($data['barcode'] ?? $existing['pkey']),
                $this->db->real_escape_string($parameterName),
                $this->db->real_escape_string($data['postinggroup'] ?? 'GENERAL'),
                floatval($data['reorderlevel'] ?? 0),
                floatval($data['eoq'] ?? 0),
                $this->db->real_escape_string($data['category'] ?? 'LAB'),
                $this->db->real_escape_string($data['units'] ?? 'PCS'),
                $data['inactive'] ?? '0',
                $this->db->real_escape_string($data['container'] ?? ''),
                floatval($data['sellingprice'] ?? 0),
                $this->db->real_escape_string($data['production'] ?? ''),
                $existing['pkey']
            );
            $result = $this->db->query($sql);
            $itemCode = $existing['itemcode'];
        }  else {
            // Insert new - exactly like Stocks.php
            $itemCode = $this->generateItemCode($parameterName);
            
            $sql = sprintf("INSERT INTO `stockmaster`
                (`itemcode`, `isstock`, isstock_1, isstock_2, isstock_3, isstock_4, isstock_5, isstock_6,
                `barcode`, `descrip`, `postinggroup`, `averagestock`, `reorderlevel`, `eoq`,
                `category`, `units`, `inactive`, `nextserialno`, `container`, `sellingprice`, `production`, `labid`)
                VALUES
                ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', 0, %f, %f,
                '%s', '%s', '%s', 1, '%s', %f, '%s', '%s')",
                $this->db->real_escape_string($itemCode),
                $data['isstock'] ?? '1',
                $data['isstock_1'] ?? '1',
                $data['isstock_2'] ?? '0',
                $data['isstock_3'] ?? '0',
                $data['isstock_4'] ?? '0',
                $data['isstock_5'] ?? '0',
                $data['isstock_6'] ?? '0',
                $this->db->real_escape_string($data['barcode'] ?? ''),
                $this->db->real_escape_string($parameterName),
                $this->db->real_escape_string($data['postinggroup'] ?? 'GENERAL'),
                floatval($data['reorderlevel'] ?? 0),
                floatval($data['eoq'] ?? 0),
                $this->db->real_escape_string($data['category'] ?? 'LAB'),
                $this->db->real_escape_string($data['units'] ?? 'PCS'),
                $data['inactive'] ?? '0',
                $this->db->real_escape_string($data['container'] ?? ''),
                floatval($data['sellingprice'] ?? 0),
                $this->db->real_escape_string($data['production'] ?? ''),
                $parameterId
            );
            $result = $this->db->query($sql);
        }

        if ($result) {
            $stockPkey = $existing['pkey'] ?? $this->db->insert_id;
            $this->jsonResponse([
                'success' => true,
                'message' => 'Stock item created successfully',
                'data' => [
                    'labid' => $parameterId,
                    'itemcode' => $itemCode,
                    'descrip' => $parameterName,
                    'pkey' => $stockPkey
                ]
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create stock item: ' . $this->db->error], 500);
        }
    }

    private function generateItemCode($descript)
    {
        // Same logic as Triger_stockmaster() in Stocks.php, but retries with the
        // next available number when the count-derived candidate already exists,
        // so batch inserts can never collide on an existing itemcode.
        $base = strtoupper(substr($descript, 0, 2));
        $result = $this->db->query("SELECT COUNT(*) FROM stockmaster");
        $row = $result->fetch_row();
        $start = (int)$row[0];
        $attempt = 0;
        do {
            $candidate = $start + $attempt++;
            $padLength = 6 - strlen($candidate);
            $replicate = str_repeat('0', max(0, $padLength));
            $code = $base . $replicate . $candidate;
            $chk = $this->db->query("SELECT itemcode FROM stockmaster WHERE itemcode = '" . $this->db->real_escape_string($code) . "' LIMIT 1");
        } while ($chk && $chk->num_rows > 0);
        return $code;
    }

    private function createInventory($data)
    {
        // Simulates "Add New Inventory" from Stocks.php
        $descrip = $data['descrip'] ?? null;
        
        if (!$descrip) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing descrip'], 400);
            return;
        }

        // Check for duplicate
        $searchString = '%' . str_replace(' ', '%', trim($descrip)) . '%';
        $checkSql = "SELECT * FROM stockmaster WHERE descrip LIKE '" . $this->db->real_escape_string($searchString) . "'";
        $checkResult = $this->db->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $this->jsonResponse(['success' => false, 'message' => 'You cannot duplicate an inventory description'], 400);
            return;
        }

        if (!$data['postinggroup']) {
            $this->jsonResponse(['success' => false, 'message' => 'You must select an Inventory Posting Group'], 400);
            return;
        }

        $itemcode = $this->generateItemCode($descrip);

        $sql = sprintf("INSERT INTO `stockmaster`
            (`itemcode`, `isstock`, isstock_1, isstock_2, isstock_3, isstock_4, isstock_5, isstock_6,
            `barcode`, `descrip`, `postinggroup`, `averagestock`, `reorderlevel`, `eoq`,
            `category`, `units`, `inactive`, `nextserialno`, `container`, `sellingprice`, `production`)
            VALUES
            ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s', '%s', '%s', 0, %f, %f,'%s', '%s', '%s', 1, '%s', %f, '%s')",
            $this->db->real_escape_string($itemcode),
            $data['isstock'] ?? '1',
            $data['isstock_1'] ?? '0',
            $data['isstock_2'] ?? '0',
            $data['isstock_3'] ?? '0',
            $data['isstock_4'] ?? '0',
            $data['isstock_5'] ?? '0',
            $data['isstock_6'] ?? '0',
            $this->db->real_escape_string($data['barcode'] ?? ''),
            $this->db->real_escape_string($descrip),
            $this->db->real_escape_string($data['postinggroup']),
            floatval($data['reorderlevel'] ?? 0),
            floatval($data['eoq'] ?? 0),
            $this->db->real_escape_string($data['category'] ?? 'LAB'),
            $this->db->real_escape_string($data['units'] ?? 'PCS'),
            $data['inactive'] ?? '0',
            $this->db->real_escape_string($data['container'] ?? ''),
            floatval($data['sellingprice'] ?? 0),
            $this->db->real_escape_string($data['production'] ?? '')
        );

        $result = $this->db->query($sql);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Inventory added successfully',
                'data' => [
                    'itemcode' => $itemcode,
                    'descrip' => $descrip,
                    'pkey' => $this->db->insert_id
                ]
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create inventory: ' . $this->db->error], 500);
        }
    }

    private function updateInventory($data)
    {
        // Simulates "Update Inventory" from Stocks.php
        $itemcode = $data['itemcode'] ?? $data['StockID'] ?? null;
        
        if (!$itemcode) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing itemcode/StockID'], 400);
            return;
        }

        if (!$data['postinggroup']) {
            $this->jsonResponse(['success' => false, 'message' => 'You must select an Inventory Posting Group'], 400);
            return;
        }

        $sql = sprintf("UPDATE `stockmaster`
            SET `isstock` = '%s',
                isstock_1 = '%s', 
                isstock_2 = '%s', 
                isstock_3 = '%s', 
                isstock_4 = '%s', 
                isstock_5 = '%s', 
                isstock_6 = '%s',
                `barcode` = '%s',
                `descrip` = '%s',
                `postinggroup` = '%s',
                `reorderlevel` = %f,
                `eoq` = %f,
                `category` = '%s',
                `units` = '%s',
                `inactive` = '%s',
                `container` = '%s',
                `sellingprice` = %f,
                `production` = '%s'
            WHERE `itemcode` = '%s'",
            $data['isstock'] ?? '1',
            $data['isstock_1'] ?? '0',
            $data['isstock_2'] ?? '0',
            $data['isstock_3'] ?? '0',
            $data['isstock_4'] ?? '0',
            $data['isstock_5'] ?? '0',
            $data['isstock_6'] ?? '0',
            $this->db->real_escape_string($data['barcode'] ?? ''),
            $this->db->real_escape_string($data['descrip'] ?? ''),
            $this->db->real_escape_string($data['postinggroup']),
            floatval($data['reorderlevel'] ?? 0),
            floatval($data['eoq'] ?? 0),
            $this->db->real_escape_string($data['category'] ?? ''),
            $this->db->real_escape_string($data['units'] ?? ''),
            $data['inactive'] ?? '0',
            $this->db->real_escape_string($data['container'] ?? ''),
            floatval($data['sellingprice'] ?? 0),
            $this->db->real_escape_string($data['production'] ?? ''),
            $this->db->real_escape_string($itemcode)
        );

        $result = $this->db->query($sql);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Inventory updated successfully',
                'data' => ['itemcode' => $itemcode]
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update inventory: ' . $this->db->error], 500);
        }
    }

    private function getStockItems($data)
    {
        $labId = $data['labid'] ?? null;
        
        if ($labId) {
            $stmt = $this->db->prepare("SELECT * FROM stockmaster WHERE labid = ?");
            $stmt->bind_param('s', $labId);
        } else {
            $stmt = $this->db->query("SELECT pkey, itemcode, descrip, labid FROM stockmaster WHERE isstock = 1 ORDER BY descrip LIMIT 50");
        }

        $items = [];
        if ($stmt) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }

        $this->jsonResponse([
            'success' => true,
            'items' => $items
        ]);
    }

    private function insertSalesHeader($data)
    {
        $fields = [];
        $values = [];
        $placeholders = [];
        
        $map = [
            'documentno' => 's',
            'documenttype' => 'i',
            'docdate' => 's',
            'oderdate' => 's',
            'duedate' => 's',
            'customercode' => 's',
            'customername' => 's',
            'yourreference' => 's',
            'externaldocumentno' => 's',
            'salespersoncode' => 's',
            'userid' => 's',
            'postinggroup' => 's',
            'currencycode' => 's',
            'locationcode' => 's',
            'paymentterms' => 's',
            'printed' => 'i',
            'released' => 'i',
            'status' => 'i',
            'vatinclusive' => 'i',
            'period' => 'i',
            'coa_documentno'=>'s',
            'picture' => 's',
            'shipping' => 'd',
            'packagescharge' => 'd',
            'QtyDiscount' => 'd'
        ];

        foreach ($map as $field => $type) {
            if (isset($data[$field])) {
                $fields[] = $field;
                $values[] = $data[$field];
                $placeholders[] = '?';
            } elseif ($field === 'documenttype') {
                $fields[] = $field;
                $values[] = $this->config['documenttype'];
                $placeholders[] = '?';
            } elseif ($field === 'period') {
                $fields[] = $field;
                $values[] = (int)date('Y');
                $placeholders[] = '?';
            } elseif ($field === 'currencycode') {
                $fields[] = $field;
                $values[] = $this->config['currency'];
                $placeholders[] = '?';
            } elseif ($field === 'postinggroup') {
                $fields[] = $field;
                $values[] = $this->config['postinggroup'];
                $placeholders[] = '?';
            } elseif ($field === 'locationcode') {
                $fields[] = $field;
                $values[] = $this->config['locationcode'];
                $placeholders[] = '?';
            } elseif (in_array($field, ['printed', 'released', 'status', 'vatinclusive'])) {
                $fields[] = $field;
                $values[] = 0;
                $placeholders[] = '?';
            }
        }

        if (empty($fields)) {
            return false;
        }

        $docNo = (string)($data['documentno'] ?? '');
        $docType = (int)($data['documenttype'] ?? $this->config['documenttype']);
        if ($docNo === '') {
            return false;
        }

        // The LIMS document number is the key of the document, enforced by
        // uq_salesheader_doctype_docno. Look it up first so a re-sync updates
        // the existing order instead of appending a second copy of it.
        $find = $this->db->prepare("SELECT entryno FROM salesheader WHERE documenttype = ? AND documentno = ? LIMIT 1");
        $find->bind_param('is', $docType, $docNo);
        $find->execute();
        $existing = $find->get_result()->fetch_assoc();

        if ($existing) {
            $sets = array();
            foreach ($fields as $i => $field) {
                $sets[] = $field . ' = ?';
            }
            $sql = "UPDATE salesheader SET " . implode(', ', $sets) . " WHERE entryno = ?";
            $stmt = $this->db->prepare($sql);

            $types = '';
            foreach ($values as $v) {
                $types .= is_int($v) ? 'i' : 's';
            }
            $values[] = (int)$existing['entryno'];
            $types .= 'i';

            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) {
                return false;
            }
            return [
                'entryno' => (int)$existing['entryno'],
                'documentno' => $docNo,
                'docdate' => $data['docdate'] ?? date('Y-m-d H:i:s'),
                'created' => false
            ];
        }

        $sql = "INSERT INTO salesheader (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        
        $types = '';
        foreach ($values as $v) {
            $types .= is_int($v) ? 'i' : 's';
        }
        
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            return [
                'entryno' => $this->db->insert_id,
                'documentno' => $docNo,
                'docdate' => $data['docdate'] ?? date('Y-m-d H:i:s'),
                'created' => true
            ];
        }
        
        return false;
    }

    private function insertSalesLine($data, $headerEntryNo)
    {
        $fields = ['documenttype', 'docdate', 'documentno'];
        $values = [$this->config['documenttype'], date('Y-m-d H:i:s'), $data['documentno']];
        $placeholders = ['?', '?', '?'];

        $optionalFields = [
            'code' => 's',
            'description' => 's',
            'sampleID'=> 's',
            'Quantity' => 'd',
            'Qunatity_delivered'=> 'd',
            'UnitPrice' => 'd',
            'locationcode' => 's',
            'unitofmeasure' => 's',
            'stocktype' => 'i',
            'vatamount' => 'd',
            'invoiceamount' => 'd',
            'containerprice' => 'd',
            'containersunits' => 'd',
            'containercode' => 's',
            'vatrate' => 'd',
            'inclusive' => 'i',
            'TAT' => 'i',
            // fields used by transactions/customerreadonly.inc
            'totalchargedcontainers' => 'd',
            'PartPerUnit' => 'd',
            'LineDiscountPercent' => 'd',
            'PriceInPricelist' => 'd',
            'category' => 's'
        ];

        foreach ($optionalFields as $field => $type) {
            if (isset($data[$field])) {
                $fields[] = $field;
                $values[] = $data[$field];
                $placeholders[] = '?';
            } elseif ($field === 'locationcode') {
                $fields[] = $field;
                $values[] = $this->config['locationcode'];
                $placeholders[] = '?';
            } elseif ($field === 'unitofmeasure') {
                $fields[] = $field;
                $values[] = $this->config['uom'];
                $placeholders[] = '?';
            } elseif ($field === 'stocktype') {
                $fields[] = $field;
                $values[] = 1;
                $placeholders[] = '?';
            }
        }

        $sql = "INSERT INTO salesline (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);

        $types = '';
        foreach ($values as $v) {
            $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
        }

        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            return [
                'entryno' => $this->db->insert_id,
                'documentno' => $data['documentno'],
                'itemcode' => $data['code'] ?? ''
            ];
        }

        return false;
    }

    private function findStockItemByLabId($labId)
    {
        if (!$labId) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM stockmaster WHERE labid = ?");
        $stmt->bind_param('s', $labId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getStockItemByLabId($labId)
    {
        return $this->findStockItemByLabId($labId);
    }

    private function getDefaultStockItem()
    {
        $stmt = $this->db->query("SELECT * FROM stockmaster WHERE isstock = 1 ORDER BY pkey LIMIT 1");
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function createCustomer($data)
    {
        // Create customer in debtors table using Triger_debtors logic
        $customerName = $data['customer'] ?? $data['name'] ?? null;
        
        if (!$customerName) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing customer name'], 400);
            return;
        }

        // Check for duplicate
        $checkSql = "SELECT * FROM debtors WHERE customer = '" . $this->db->real_escape_string($customerName) . "'";
        $checkResult = $this->db->query($checkSql);
        if ($checkResult && $checkResult->num_rows > 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Customer already exists'], 400);
            return;
        }

        // Generate itemcode using Triger_debtors logic: Dr + first 2 chars + zero-padded count
        $itemcode = $this->generateCustomerCode($customerName);

        $creditLimit = is_numeric($data['creditlimit'] ?? '') ? floatval($data['creditlimit']) : 0;
        
        $sql = sprintf("INSERT INTO `debtors`
            (itemcode, `contact`, `creditlimit`, `customer`, `middlen`,
            `phone`, `fax`, `company`, `altcontact`, `email`, `city`, `country`,
            `inactive`, `postcode`, `curr_cod`, `customerposting`, `salesman`)
            VALUES
            ('%s', '%s', %f, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s')",
            $this->db->real_escape_string($itemcode),
            $this->db->real_escape_string($data['contact'] ?? ''),
            $creditLimit,
            $this->db->real_escape_string($customerName),
            $this->db->real_escape_string($data['middlen'] ?? ''),
            $this->db->real_escape_string($data['phone'] ?? ''),
            $this->db->real_escape_string($data['fax'] ?? ''),
            $this->db->real_escape_string($data['company'] ?? ''),
            $this->db->real_escape_string($data['altcontact'] ?? ''),
            $this->db->real_escape_string($data['email'] ?? ''),
            $this->db->real_escape_string($data['city'] ?? ''),
            $this->db->real_escape_string($data['country'] ?? ''),
            $data['inactive'] ?? '0',
            $this->db->real_escape_string($data['postcode'] ?? ''),
            $this->db->real_escape_string($data['curr_cod'] ?? 'KES'),
            $this->db->real_escape_string($data['customerposting'] ?? 'GEN'),
            $this->db->real_escape_string($data['salesman'] ?? '')
        );

        $result = $this->db->query($sql);
        
        if (!$result) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'SQL Error: ' . $this->db->error . ' | SQL: ' . $sql
            ], 500);
            return;
        }

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => [
                    'itemcode' => $itemcode,
                    'customer' => $customerName
                ]
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create customer: ' . $this->db->error], 500);
        }
    }

    private function generateCustomerCode($descript)
    {
        // Same logic as Triger_debtors: Dr + first 2 chars + zero-padded count
        $result = $this->db->query("SELECT COUNT(*) FROM debtors");
        $row = $result->fetch_row();
        $int = (int)$row[0];
        $padLength = 4 - strlen($int);
        $replicate = str_repeat('0', max(0, $padLength));
        $code = 'Dr' . strtoupper(substr($descript, 0, 2)) . $replicate . $int;
        return $code;
    }

    private function searchCustomers($data)
    {
        $query = $data['query'] ?? '';
        $limit = isset($data['limit']) ? (int)$data['limit'] : 50;
        $offset = isset($data['offset']) ? (int)$data['offset'] : 0;
        
        // Get all fields that blockchain expects
        if (!empty($query)) {
            $stmt = $this->db->prepare("
                SELECT itemcode, customer, company, postcode, city, country, phone, altcontact, email,
                       curr_cod, IFNULL(salesman, '') AS salespersoncode
                FROM debtors 
                WHERE customer LIKE CONCAT('%', ?, '%') 
                ORDER BY customer ASC
                LIMIT ?, ?
            ");
            $stmt->bind_param("sii", $query, $offset, $limit);
        } else {
            $stmt = $this->db->prepare("
                SELECT itemcode, customer, company, postcode, city, country, phone, altcontact, email,
                       curr_cod, IFNULL(salesman, '') AS salespersoncode
                FROM debtors 
                ORDER BY customer ASC
                LIMIT ?, ?
            ");
            $stmt->bind_param("ii", $offset, $limit);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            // Map to names blockchain expects
            $customers[] = [
                'code' => $row['itemcode'],
                'itemcode' => $row['itemcode'],
                'name' => $row['customer'],
                'customer' => $row['customer'],
                'company' => $row['company'] ?? '',
                'postcode' => $row['postcode'] ?? '',
                'city' => $row['city'] ?? '',
                'country' => $row['country'] ?? '',
                'phone' => $row['phone'] ?? '',
                'altcontact' => $row['altcontact'] ?? '',
                'email' => $row['email'] ?? '',
                'inactive' => $row['inactive'] ?? '0',
                'currency' => $row['curr_cod'],
                'salespersoncode' => $row['salespersoncode']
            ];
        }

        $this->jsonResponse([
            'success' => true,
            'customers' => $customers
        ]);
    }

    private function updateCustomer($data)
    {
        // Update customer in debtors table
        $itemcode = $data['itemcode'] ?? null;
        
        if (!$itemcode) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing itemcode'], 400);
            return;
        }

        $sql = sprintf("UPDATE `debtors` SET 
            contact = '%s', creditlimit = '%s', customer = '%s', middlen = '%s',
            phone = '%s', fax = '%s', company = '%s', altcontact = '%s', email = '%s',
            city = '%s', country = '%s', inactive = '%s', postcode = '%s',
            curr_cod = '%s', customerposting = '%s', salesman = '%s'
            WHERE itemcode = '%s'",
            $this->db->real_escape_string($data['contact'] ?? ''),
            $this->db->real_escape_string($data['creditlimit'] ?? '0'),
            $this->db->real_escape_string($data['customer'] ?? ''),
            $this->db->real_escape_string($data['middlen'] ?? ''),
            $this->db->real_escape_string($data['phone'] ?? ''),
            $this->db->real_escape_string($data['fax'] ?? ''),
            $this->db->real_escape_string($data['company'] ?? ''),
            $this->db->real_escape_string($data['altcontact'] ?? ''),
            $this->db->real_escape_string($data['email'] ?? ''),
            $this->db->real_escape_string($data['city'] ?? ''),
            $this->db->real_escape_string($data['country'] ?? ''),
            $data['inactive'] ?? '0',
            $this->db->real_escape_string($data['postcode'] ?? ''),
            $this->db->real_escape_string($data['curr_cod'] ?? 'KES'),
            $this->db->real_escape_string($data['customerposting'] ?? 'GEN'),
            $this->db->real_escape_string($data['salesman'] ?? ''),
            $this->db->real_escape_string($itemcode)
        );

        $result = $this->db->query($sql);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Customer updated successfully',
                'itemcode' => $itemcode
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update customer: ' . $this->db->error], 500);
        }
    }

    private function deleteCustomer($data)
    {
        // Delete customer from debtors table (only if not used in sample_header)
        $itemcode = $data['itemcode'] ?? null;
        
        if (!$itemcode) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing itemcode'], 400);
            return;
        }

        // Check if customer is used in sample_header
        $checkSql = "SELECT COUNT(*) as cnt FROM sample_header WHERE CustomerID = '" . $this->db->real_escape_string($itemcode) . "'";
        $checkResult = $this->db->query($checkSql);
        $row = $checkResult->fetch_assoc();
        
        if ($row['cnt'] > 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Cannot delete - customer has existing records'], 400);
            return;
        }

        $sql = "DELETE FROM `debtors` WHERE itemcode = '" . $this->db->real_escape_string($itemcode) . "'";
        $result = $this->db->query($sql);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete customer: ' . $this->db->error], 500);
        }
    }

    private function searchQuotes($data)
    {
        $query = trim((string)($data['query'] ?? ''));
        $limit = isset($data['limit']) ? (int)$data['limit'] : 25;
        $limit = min(max($limit, 1), 50);

        if ($query !== '') {
            $stmt = $this->db->prepare("SELECT documentno, docdate, customercode, customername, currencycode, IFNULL(yourreference, '') AS yourreference
                FROM salesheader
                WHERE documenttype = 54 AND (documentno LIKE CONCAT('%', ?, '%') OR customername LIKE CONCAT('%', ?, '%'))
                ORDER BY entryno DESC
                LIMIT ?");
            $stmt->bind_param("ssi", $query, $query, $limit);
        } else {
            $stmt = $this->db->prepare("SELECT documentno, docdate, customercode, customername, currencycode, IFNULL(yourreference, '') AS yourreference
                FROM salesheader
                WHERE documenttype = 54
                ORDER BY entryno DESC
                LIMIT ?");
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $quotes = [];
        while ($row = $result->fetch_assoc()) {
            $quotes[] = [
                'documentno' => trim((string)$row['documentno']),
                'docdate' => $row['docdate'],
                'customercode' => trim((string)$row['customercode']),
                'customername' => trim((string)$row['customername']),
                'currencycode' => trim((string)$row['currencycode']),
                'yourreference' => trim((string)$row['yourreference'])
            ];
        }
        $this->jsonResponse(['success' => true, 'quotes' => $quotes]);
    }

    private function getQuote($data)
    {
        $documentNo = trim((string)($data['documentno'] ?? ''));
        if ($documentNo === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Missing documentno'], 400);
            return;
        }

        $stmt = $this->db->prepare("SELECT documentno, docdate, customercode, customername, currencycode, IFNULL(yourreference, '') AS yourreference, IFNULL(paymentterms, '') AS paymentterms
            FROM salesheader
            WHERE documenttype = 54 AND documentno = ?
            LIMIT 1");
        $stmt->bind_param("s", $documentNo);
        $stmt->execute();
        $hdr = $stmt->get_result()->fetch_assoc();
        if (!$hdr) {
            $this->jsonResponse(['success' => false, 'message' => 'Quote not found: ' . $documentNo], 404);
            return;
        }

        $stmt = $this->db->prepare("SELECT sl.code, sm.labid, sl.category, sl.description, sl.Quantity, sl.UnitPrice, IFNULL(sl.unitofmeasure, 'PCS') AS unitofmeasure, IFNULL(sl.PartPerUnit, 1) AS PartPerUnit
            FROM salesline sl
            LEFT JOIN stockmaster sm ON sm.itemcode = sl.code
            WHERE sl.documenttype = 54 AND sl.documentno = ?
            ORDER BY sl.entryno ASC");
        $stmt->bind_param("s", $documentNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $lines = [];
        while ($row = $result->fetch_assoc()) {
            $category = trim((string)($row['category'] ?? ''));
            $standardId = 0;
            if (preg_match('/^TS(\\d{1,6})$/', $category, $m)) {
                $standardId = (int)$m[1];
            } elseif (preg_match('/^TS(\\d{1,6})$/', trim((string)$row['code']), $m)) {
                // The bundle line itself identifies its StandardID.
                $standardId = (int)$m[1];
            }

            $lines[] = [
                'code' => trim((string)$row['code']),
                'labid' => (int)($row['labid'] ?? 0),
                'standard_id' => $standardId,
                'category' => $category,
                'description' => trim((string)$row['description']),
                'Quantity' => (float)($row['Quantity'] ?? 0),
                'UnitPrice' => (float)($row['UnitPrice'] ?? 0),
                'unitofmeasure' => trim((string)$row['unitofmeasure']),
                'PartPerUnit' => (float)($row['PartPerUnit'] ?? 1)
            ];
        }

        $this->jsonResponse([
            'success' => true,
            'quote' => [
                'documentno' => trim((string)$hdr['documentno']),
                'docdate' => $hdr['docdate'],
                'customercode' => trim((string)$hdr['customercode']),
                'customername' => trim((string)$hdr['customername']),
                'currencycode' => trim((string)$hdr['currencycode']),
                'yourreference' => trim((string)$hdr['yourreference']),
                'paymentterms' => trim((string)$hdr['paymentterms'])
            ],
            'lines' => $lines
        ]);
    }

    private function jsonResponse($data, $code = 200)
    {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    private function createSupplier($data)
    {
        // Create supplier in creditors table
        $customer = $data['customer'] ?? $data['name'] ?? null;
        
        if (!$customer) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing supplier name'], 400);
            return;
        }

        // Check for duplicate
        $checkSql = "SELECT * FROM creditors WHERE customer = '" . $this->db->real_escape_string($customer) . "'";
        $checkResult = $this->db->query($checkSql);
        if ($checkResult && $checkResult->num_rows > 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Supplier already exists'], 400);
            return;
        }

        // Generate itemcode using Triger_creditors logic
        $itemcode = $this->generateSupplierCode($customer);

        $sql = sprintf("INSERT INTO `creditors`
            (itemcode, contact, vatregno, customer, middlen, phone, fax, company,
            altcontact, email, city, country, inactive, postcode, curr_cod,
            supplierposting, firstn)
            VALUES
            ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s')",
            $this->db->real_escape_string($itemcode),
            $this->db->real_escape_string($data['contact'] ?? ''),
            $this->db->real_escape_string($data['vatregno'] ?? ''),
            $this->db->real_escape_string($customer),
            $this->db->real_escape_string($data['middlen'] ?? ''),
            $this->db->real_escape_string($data['phone'] ?? ''),
            $this->db->real_escape_string($data['fax'] ?? ''),
            $this->db->real_escape_string($data['company'] ?? ''),
            $this->db->real_escape_string($data['altcontact'] ?? ''),
            $this->db->real_escape_string($data['email'] ?? ''),
            $this->db->real_escape_string($data['city'] ?? ''),
            $this->db->real_escape_string($data['country'] ?? ''),
            $data['inactive'] ?? '0',
            $this->db->real_escape_string($data['postcode'] ?? ''),
            $this->db->real_escape_string($data['curr_cod'] ?? 'KES'),
            $this->db->real_escape_string($data['supplierposting'] ?? 'GEN'),
            $this->db->real_escape_string($data['firstn'] ?? '')
        );

        $result = $this->db->query($sql);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Supplier created successfully',
                'data' => [
                    'itemcode' => $itemcode,
                    'customer' => $customer
                ]
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create supplier: ' . $this->db->error], 500);
        }
    }

    private function generateSupplierCode($descript)
    {
        // Same logic as Triger_creditors: cr + first 2 chars + zero-padded count
        $result = $this->db->query("SELECT COUNT(*) FROM creditors");
        $row = $result->fetch_row();
        $int = (int)$row[0];
        $padLength = 4 - strlen($int);
        $replicate = str_repeat('0', max(0, $padLength));
        $code = 'cr' . strtoupper(substr($descript, 0, 2)) . $replicate . $int;
        return $code;
    }

    private function updateSupplier($data)
    {
        $itemcode = $data['itemcode'] ?? $data['editcode'] ?? null;
        
        if (!$itemcode) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing itemcode'], 400);
            return;
        }

        $sql = sprintf("UPDATE `creditors` SET 
            contact = '%s', vatregno = '%s', customer = '%s', firstn = '%s',
            middlen = '%s', lastn = '%s', status = '%s', phone = '%s', fax = '%s',
            company = '%s', altcontact = '%s', email = '%s', city = '%s', country = '%s',
            inactive = '%s', postcode = '%s', curr_cod = '%s', supplierposting = '%s'
            WHERE itemcode = '%s'",
            $this->db->real_escape_string($data['contact'] ?? ''),
            $this->db->real_escape_string($data['vatregno'] ?? ''),
            $this->db->real_escape_string($data['customer'] ?? ''),
            $this->db->real_escape_string($data['firstn'] ?? ''),
            $this->db->real_escape_string($data['middlen'] ?? ''),
            $this->db->real_escape_string($data['lastn'] ?? ''),
            $this->db->real_escape_string($data['status'] ?? ''),
            $this->db->real_escape_string($data['phone'] ?? ''),
            $this->db->real_escape_string($data['fax'] ?? ''),
            $this->db->real_escape_string($data['company'] ?? ''),
            $this->db->real_escape_string($data['altcontact'] ?? ''),
            $this->db->real_escape_string($data['email'] ?? ''),
            $this->db->real_escape_string($data['city'] ?? ''),
            $this->db->real_escape_string($data['country'] ?? ''),
            $data['inactive'] ?? '0',
            $this->db->real_escape_string($data['postcode'] ?? ''),
            $this->db->real_escape_string($data['curr_cod'] ?? 'KES'),
            $this->db->real_escape_string($data['supplierposting'] ?? 'GEN'),
            $this->db->real_escape_string($itemcode)
        );

        $result = $this->db->query($sql);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Supplier updated successfully',
                'itemcode' => $itemcode
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update supplier: ' . $this->db->error], 500);
        }
    }

    private function deleteSupplier($data)
    {
        $itemcode = $data['itemcode'] ?? $data['editcode'] ?? null;
        
        if (!$itemcode) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing itemcode'], 400);
            return;
        }

        // Check for transactions
        $checkSql = "SELECT COUNT(*) as cnt FROM creditor_trans WHERE acctfolio = '" . $this->db->real_escape_string($itemcode) . "'";
        $checkResult = $this->db->query($checkSql);
        $row = $checkResult->fetch_assoc();
        
        if ($row['cnt'] > 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Supplier has transactions and cannot be deleted'], 400);
            return;
        }

        $sql = "DELETE FROM creditors WHERE itemcode = '" . $this->db->real_escape_string($itemcode) . "'";
        $result = $this->db->query($sql);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete supplier: ' . $this->db->error], 500);
        }
    }

    private function searchSuppliers($data)
    {
        $query = $data['query'] ?? '';
        
        if (empty($query)) {
            $this->jsonResponse(['success' => false, 'message' => 'Query parameter required'], 400);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT itemcode AS code, customer AS name, curr_cod AS currency, 
                   IFNULL(phone, '') AS phone
            FROM creditors 
            WHERE customer LIKE CONCAT('%', ?, '%') 
            LIMIT 10
        ");
        $stmt->bind_param("s", $query);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $suppliers = [];
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }

        $this->jsonResponse([
            'success' => true,
            'suppliers' => $suppliers
        ]);
    }

    private function getCustomer($data)
    {
        $itemcode = $data['itemcode'] ?? null;
        
        if (!$itemcode) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing itemcode'], 400);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM debtors WHERE itemcode = ?");
        $stmt->bind_param("s", $itemcode);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $customers
        ]);
    }

    private function getSupplier($data)
    {
        $itemcode = $data['itemcode'] ?? null;
        
        if (!$itemcode) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing itemcode'], 400);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM creditors WHERE itemcode = ?");
        $stmt->bind_param("s", $itemcode);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $suppliers = [];
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    private function syncTeststandard($data)
    {
        $standards = $data['standards'] ?? [];
        $links = $data['links'] ?? [];

        $standardsSynced = 0;
        $parametersSynced = 0;
        $linksSynced = 0;
        $errors = [];
        $skipped = ['no_stock' => 0, 'no_category' => 0];

        // ── Sync standards → stockcategory (+ bundle stock item for quoting) ──
        foreach ($standards as $std) {
            $stdId = intval($std['StandardID'] ?? 0);
            $stdNameRaw = trim((string)($std['StandardName'] ?? ''));
            if ($stdNameRaw === '') continue;

            // Name columns are varchar(255) (same width as LIMS), so the full
            // name is kept — never trim to 50.
            $stdName = $this->db->real_escape_string(mb_substr($stdNameRaw, 0, 255));

            // Prefer the deterministic 'TS####' category id derived from StandardID
            // so every standard maps to its own category and links never drop.
            $catId = 'TS' . str_pad(min($stdId, 9999), 4, '0', STR_PAD_LEFT);
            $existing = $this->db->query("SELECT categoryid FROM stockcategory WHERE categoryid = '$catId' LIMIT 1");
            if (!$existing) {
                $errors[] = 'standard select stdid=' . $stdId . ': ' . $this->db->error;
                continue;
            }

            if ($existing->num_rows > 0) {
                $q = $this->db->query("UPDATE stockcategory SET categorydescription = '$stdName' WHERE categoryid = '$catId'");
                if (!$q) $errors[] = 'standard update stdid=' . $stdId . ' (' . $catId . '): ' . $this->db->error;
            } else {
                // StandardID is the authoritative identity. Never substitute a
                // legacy category found by name because two standards can share
                // the same name.
                $q = $this->db->query("INSERT INTO stockcategory (categoryid, categorydescription) VALUES ('$catId', '$stdName')");
                if (!$q) $errors[] = 'standard insert stdid=' . $stdId . ' (' . $catId . '): ' . $this->db->error;
            }

            // Upsert bundle stock item so the standard itself is priceable/quotable in Sales Quotation
            if (preg_match('/^TS\d{4}$/', $catId)) {
                $bundleChk = $this->db->query("SELECT itemcode FROM stockmaster WHERE itemcode = '" . $catId . "' LIMIT 1");
                if ($bundleChk && $bundleChk->num_rows > 0) {
                    $q = $this->db->query("UPDATE stockmaster SET descrip = '$stdName', category = '$catId', isstock = '1', isstock_1 = '1', inactive = '0' WHERE itemcode = '" . $catId . "'");
                    if (!$q) $errors[] = 'bundle update ' . $catId . ': ' . $this->db->error;
                } else {
                    $q = $this->db->query("INSERT INTO stockmaster
                        (`itemcode`, `isstock`, isstock_1, isstock_2, isstock_3, isstock_4, isstock_5, isstock_6,
                        `barcode`, `descrip`, `postinggroup`, `averagestock`, `reorderlevel`, `eoq`,
                        `category`, `units`, `inactive`, `nextserialno`, `container`, `sellingprice`, `production`)
                        VALUES
                        ('" . $catId . "', '1', '1', '0', '0', '0', '0', '0',
                        '" . $catId . "', '" . $stdName . "', 'GENERAL', 0, 0, 0,
                        '" . $catId . "', 'PCS', '0', 1, '', 0, '')");
                    if (!$q) $errors[] = 'bundle insert ' . $catId . ': ' . $this->db->error;
                }
            }
            $standardsSynced++;
        }

        // ── Ensure a stock item exists for every parameter referenced by the links.
        //    Links resolve via stockmaster.labid = baseparameters.ParameterID, so the
        //    parameters must be imported too or the majority of links will be dropped. ──
        $parameters = $data['parameters'] ?? [];
        foreach ($parameters as $param) {
            $pid = intval($param['ParameterID'] ?? 0);
            $pname = trim((string)($param['ParameterName'] ?? ''));
            if ($pid <= 0 || $pname === '') continue;
            $pnameEsc = $this->db->real_escape_string(mb_substr($pname, 0, 255));
            $units = $this->db->real_escape_string(mb_substr((string)($param['UnitOfMeasure'] ?? 'PCS'), 0, 20));
            if ($units === '') $units = 'PCS';
            $category = $this->db->real_escape_string((string)($param['Category'] ?? 'LAB'));
            if ($category === '') $category = 'LAB';

            $existing = $this->getStockItemByLabId($pid);
            if ($existing) {
                $upd = $this->db->query(sprintf(
                    "UPDATE `stockmaster` SET `descrip` = '%s', `units` = '%s', `category` = '%s',
                        `inactive` = '0', `isstock` = '1', isstock_1 = '1' WHERE `pkey` = %d",
                    $pnameEsc, $units, $category, (int)$existing['pkey']
                ));
                if (!$upd) {
                    $errors[] = 'param update labid=' . $pid . ': ' . $this->db->error;
                    continue;
                }
            } else {
                // Deterministic, collision-free code (generateItemCode can clash once
                // stockmaster grows; a count-derived suffix may repeat an existing code).
                $itemCode = 'LB' . str_pad($pid, 6, '0', STR_PAD_LEFT);
                $uniq = $this->db->query("SELECT itemcode FROM stockmaster WHERE itemcode = '" . $this->db->real_escape_string($itemCode) . "' LIMIT 1");
                if ($uniq && $uniq->num_rows > 0) $itemCode .= '_' . $pid;
                $itemCode = $this->db->real_escape_string($itemCode);
                $ins = $this->db->query(sprintf(
                    "INSERT INTO `stockmaster`
                     (`itemcode`, `isstock`, isstock_1, isstock_2, isstock_3, isstock_4, isstock_5, isstock_6,
                     `barcode`, `descrip`, `postinggroup`, `averagestock`, `reorderlevel`, `eoq`,
                     `category`, `units`, `inactive`, `nextserialno`, `container`, `sellingprice`, `production`, `labid`)
                     VALUES ('%s', '1', '1', '0', '0', '0', '0', '0',
                     '%s', '%s', 'GENERAL', 0, 0, 0,
                     '%s', '%s', '0', 1, '', 0, '', %d)",
                    $itemCode, $itemCode, $pnameEsc, $category, $units, $pid
                ));
                if (!$ins) {
                    $errors[] = 'param insert labid=' . $pid . ' (' . $itemCode . '): ' . $this->db->error;
                    continue;
                }
            }
            $parametersSynced++;
        }

        // ── Sync links → discounttable ──
        foreach ($links as $link) {
            $baseId = intval($link['BaseID'] ?? 0);
            $stdName = trim((string)($link['StandardName'] ?? ''));
            if ($baseId <= 0 || $stdName === '') continue;
            $stdNameEsc = $this->db->real_escape_string(mb_substr($stdName, 0, 255));

            // Find stockmaster by labid
            $smResult = $this->db->query("SELECT itemcode FROM stockmaster WHERE labid = $baseId LIMIT 1");
            if (!$smResult || $smResult->num_rows === 0) {
                $skipped['no_stock']++;
                continue;
            }
            $smRow = $smResult->fetch_assoc();
            $itemcode = $this->db->real_escape_string($smRow['itemcode']);

            // Resolve the sample-standard category from the payload's StandardID
            // (same 'TS####' derivation used in the standards pass above), then
            // fall back to matching the category description by full name.
            $catId = '';
            $stdId = intval($link['StandardID'] ?? 0);
            if ($stdId > 0) {
                $cand = 'TS' . str_pad(min($stdId, 9999), 4, '0', STR_PAD_LEFT);
                $ctResult = $this->db->query("SELECT categoryid FROM stockcategory WHERE categoryid = '" . $this->db->real_escape_string($cand) . "' LIMIT 1");
                if ($ctResult && $ctResult->num_rows > 0) $catId = $cand;
            }
            if ($catId === '') {
                // StandardID is required for an exact parameter-to-standard link.
                // Do not fall back to a category name and risk assigning the
                // parameter to the wrong standard.
                $skipped['no_category']++;
                continue;
            }
            $catId = $this->db->real_escape_string($catId);

            // Check if discounttable entry exists, insert if missing
            $dtResult = $this->db->query("SELECT id FROM discounttable WHERE categoryid = '$catId' AND itemcode = '$itemcode' LIMIT 1");
            if (!$dtResult) {
                $errors[] = 'link check cat=' . $catId . ' item=' . $itemcode . ': ' . $this->db->error;
                continue;
            }
            if ($dtResult->num_rows === 0) {
                $ins = $this->db->query("INSERT INTO discounttable (categoryid, itemcode, discount_percent, is_active) VALUES ('$catId', '$itemcode', 0.00, 1)");
                if (!$ins) {
                    $errors[] = 'link insert cat=' . $catId . ' item=' . $itemcode . ': ' . $this->db->error;
                    continue;
                }
            }
            $linksSynced++;
        }

        $this->jsonResponse([
            'success' => true,
            'standards_synced' => $standardsSynced,
            'parameters_synced' => $parametersSynced,
            'links_synced' => $linksSynced,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }
}


$api = new LimsSalesApi($db);
$api->handleRequest();