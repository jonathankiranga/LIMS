<?php
require '../db_connection.php';
require_once '../functions/functions.php';
header('Content-Type: application/json; charset=utf-8');

$sampleID = trim((string)($_REQUEST['SampleID'] ?? $_REQUEST['sampleID'] ?? ''));
$headerID = (int)($_REQUEST['HeaderID'] ?? 0);
if ($sampleID === '' || $headerID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sample ID and header ID are required.']);
    exit;
}

function coaTextDefaults($conn, $sampleID, $headerID) {
    $defaults = ['ResultsInterpretation' => '', 'StatementOfConformity' => '', 'Notes' => ''];

    $stmt = $conn->prepare("SELECT ts.Description
        FROM test_results tr
        JOIN teststandards ts ON ts.StandardID = tr.StandardID
        WHERE tr.SampleID = ? AND tr.HeaderID = ?
        ORDER BY tr.resultsID DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('si', $sampleID, $headerID);
        if ($stmt->execute()) {
            $row = $stmt->get_result()->fetch_assoc();
            $defaults['Notes'] = (string)($row['Description'] ?? '');
        }
        $stmt->close();
    }

    $result = $conn->query("SELECT confvalue FROM config WHERE confname = 'Conformity' LIMIT 1");
    if ($result && ($row = $result->fetch_assoc())) {
        $defaults['StatementOfConformity'] = (string)($row['confvalue'] ?? '');
    }

    $stmt = $conn->prepare("SELECT tr.*, tp.ParameterName, tp.MinLimit, tp.MaxLimit, tp.MRL, tp.MRLUnit,
                                  tp.UnitOfMeasure, tp.ResultType AS ParameterResultType
        FROM test_results tr
        LEFT JOIN testparameters tp
          ON tp.ParameterID = tr.ParameterID AND tp.StandardID = tr.StandardID
        WHERE tr.SampleID = ? AND tr.HeaderID = ? AND tr.StatusID IN (2,3,4)
        ORDER BY tr.resultsID");
    if ($stmt) {
        $stmt->bind_param('si', $sampleID, $headerID);
        if ($stmt->execute()) {
            $rs = $stmt->get_result();
            $parts = [];
            while ($r = $rs->fetch_assoc()) {
                $name = trim((string)($r['ParameterName'] ?? 'Test'));
                $grade = '';
                $value = '';
                if (!empty($r['MRL_Result'])) {
                    $value = function_exists('formatResultNumber')
                        ? formatResultNumber($r, $r['MRL_Result']) . ' ' . (string)($r['MRLUnit'] ?? '')
                        : (string)$r['MRL_Result'] . ' ' . (string)($r['MRLUnit'] ?? '');
                    $grade = ((float)($r['MRL'] ?? 0) > (float)$r['MRL_Result']) ? 'green' : 'red';
                }
                if (!empty($r['ResultStatus'])) {
                    $value = (string)$r['ResultStatus'];
                    $grade = '';
                }
                if (!empty($r['RangeResult'])) {
                    $value = function_exists('formatResultNumber')
                        ? formatResultNumber($r, $r['RangeResult']) . ' ' . (string)($r['UnitOfMeasure'] ?? '')
                        : (string)$r['RangeResult'] . ' ' . (string)($r['UnitOfMeasure'] ?? '');
                    $grade = 'green';
                    if ($r['MinLimit'] !== null && (float)$r['MinLimit'] > (float)$r['RangeResult']) $grade = 'blue';
                    if ($r['MaxLimit'] !== null && (float)$r['RangeResult'] > (float)$r['MaxLimit']) $grade = 'red';
                }
                $interpretation = ($grade === '') ? $value
                    : (($grade === 'blue') ? 'Below the minimum specified level'
                    : (($grade === 'green') ? 'Optimum'
                    : (($grade === 'red') ? ' above the maximum specified level' : $value)));
                if ($name !== '' && $interpretation !== '') {
                    $parts[] = 'Test Results for ' . $name . ' are ' . $interpretation . '.';
                }
            }
            $defaults['ResultsInterpretation'] = implode("\n", $parts);
        }
        $stmt->close();
    }
    return $defaults;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_POST['saveText'])) {
    $defaults = coaTextDefaults($conn, $sampleID, $headerID);
    $stmt = $conn->prepare("SELECT ResultsInterpretation, StatementOfConformity, Notes
        FROM sample_coa_text WHERE HeaderID = ? AND SampleID = ? LIMIT 1");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Unable to read saved COA text.']);
        exit;
    }
    $stmt->bind_param('is', $headerID, $sampleID);
    $stmt->execute();
    $saved = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    // Existing rows may have been created by an earlier approval that saved
    // empty editor values. Use the report defaults for any field not yet saved.
    $texts = [];
    foreach (['ResultsInterpretation', 'StatementOfConformity', 'Notes'] as $field) {
        $savedValue = $saved !== null ? trim((string)($saved[$field] ?? '')) : '';
        $texts[$field] = $savedValue !== '' ? (string)$saved[$field] : (string)$defaults[$field];
    }
    echo json_encode(['success' => true, 'texts' => $texts]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported operation.']);
