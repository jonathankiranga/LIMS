<?php
require '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

function batchDetailsError($message) {
    echo json_encode(array('status' => 'error', 'success' => false, 'message' => $message));
    exit;
}

function getstandardname($standardId) {
    global $conn;
    $stmt = $conn->prepare("SELECT StandardName FROM teststandards WHERE StandardID = ? LIMIT 1");
    if (!$stmt) throw new Exception('Preparing standard lookup failed: ' . $conn->error);
    $stmt->bind_param('i', $standardId);
    if (!$stmt->execute()) throw new Exception('Reading standard failed: ' . $stmt->error);
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ? $row['StandardName'] : '';
}

function getmatrixname($matrixId) {
    global $conn;
    if (!$matrixId) return '';
    $stmt = $conn->prepare("SELECT ParameterName FROM parametermatrix WHERE ParameterID = ? LIMIT 1");
    if (!$stmt) throw new Exception('Preparing matrix lookup failed: ' . $conn->error);
    $stmt->bind_param('i', $matrixId);
    if (!$stmt->execute()) throw new Exception('Reading matrix failed: ' . $stmt->error);
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ? $row['ParameterName'] : '';
}

function get_test_results($testId, $headerId) {
    global $conn;
    $sql = "SELECT tr.*, tp.ParameterName
            FROM test_results tr
            INNER JOIN testparameters tp
                ON tp.ParameterID = tr.ParameterID
               AND tp.StandardID = tr.StandardID
            WHERE tr.TestID = ? AND tr.HeaderID = ?
            ORDER BY tp.ParameterName";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Preparing test-parameter lookup failed: ' . $conn->error);
    $stmt->bind_param('ii', $testId, $headerId);
    if (!$stmt->execute()) throw new Exception('Reading test parameters failed: ' . $stmt->error);
    $result = $stmt->get_result();
    $rows = array();
    while ($result && ($row = $result->fetch_assoc())) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

$headerId = isset($_GET['documentno']) ? (int)$_GET['documentno'] : 0;
if ($headerId < 1) batchDetailsError('A valid registration reference is required.');

try {
    $headerSql = "SELECT HeaderID, Date, DocumentNo, CustomerName, CustomerID,
                         SampledBy, SamplingMethod, SamplingDate, OrderNo,
                         ScopeOfWork, User_name
                  FROM sample_header
                  WHERE HeaderID = ?
                  LIMIT 1";
    $headerStmt = $conn->prepare($headerSql);
    if (!$headerStmt) throw new Exception('Preparing registration lookup failed: ' . $conn->error);
    $headerStmt->bind_param('i', $headerId);
    if (!$headerStmt->execute()) throw new Exception('Reading registration failed: ' . $headerStmt->error);
    $headerResult = $headerStmt->get_result();
    $header = $headerResult ? $headerResult->fetch_assoc() : null;
    $headerStmt->close();

    if (!$header) batchDetailsError('Registration not found.');

    $sampleSql = "SELECT TestID, HeaderID, SampleID, StandardID, SampleFileKey,
                         SKU AS standard_kit_units,
                         BatchNo AS product_batch_no,
                         BatchSize AS batch_size,
                         ManufactureDate AS date_of_manufacture,
                         ExpDate AS date_of_expiry,
                         chilled_date_of_expiry,
                         frozen_date_of_expiry,
                         ExternalSample AS sample_source,
                         sample_name, sample_method, condition_of_sample,
                         User_name, BaseID
                  FROM sample_tests
                  WHERE HeaderID = ? AND (is_deleted = 0 OR is_deleted IS NULL)
                  ORDER BY TestID ASC";
    $sampleStmt = $conn->prepare($sampleSql);
    if (!$sampleStmt) throw new Exception('Preparing sample rows failed: ' . $conn->error);
    $sampleStmt->bind_param('i', $headerId);
    if (!$sampleStmt->execute()) throw new Exception('Reading sample rows failed: ' . $sampleStmt->error);
    $sampleResult = $sampleStmt->get_result();

    $samples = array();
    while ($sampleResult && ($row = $sampleResult->fetch_assoc())) {
        $row['standardname'] = getstandardname((int)$row['StandardID']);
        $row['matrixname'] = getmatrixname((int)$row['BaseID']);
        $row['parameters'] = get_test_results((int)$row['TestID'], $headerId);
        // A sample_tests record represents one physical sample.
        $row['samples'] = 1;
        $samples[] = $row;
    }
    $sampleStmt->close();

    echo json_encode(array(
        'status' => 'success',
        'success' => true,
        'data' => array(
            'header' => array($header),
            'samples' => $samples
        )
    ));
} catch (Throwable $e) {
    error_log('getBatchDetails.php: ' . $e->getMessage());
    batchDetailsError('Unable to load registration details. Check the application error log.');
} finally {
    $conn->close();
}
