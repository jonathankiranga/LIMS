<?php
require '../db_connection.php';
require_once '../functions/functions.php';
header('Content-Type: application/json; charset=utf-8');

// The editors receive HTML. Convert legacy/plain text to safe HTML while
// retaining every newline; already-formatted rich text is left unchanged.
function coaTextForEditor($value) {
    $value = (string)$value;
    if (strpos($value, '<') !== false && preg_match('~</?[a-z][^>]*>~i', $value)) {
        return $value;
    }
    return nl2br(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
}

$sampleID = trim((string)($_REQUEST['SampleID'] ?? $_REQUEST['sampleID'] ?? ''));
$headerID = (int)($_REQUEST['HeaderID'] ?? 0);
if ($sampleID === '' || $headerID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sample ID and header ID are required.']);
    exit;
}

// Keep approval-window result grading identical to the COA reports.
function validateresults($row) {
    $results = array('results' => '', 'standard' => '', 'grade' => '');
    if (!empty($row['MRL_Result'])) {
        $results['results'] = (function_exists('formatResultNumber')
            ? formatResultNumber($row, $row['MRL_Result'])
            : (string)$row['MRL_Result']) . ' ' . (string)($row['MRLUnit'] ?? '');
        $results['standard'] = $row['MRL'] ?? '';
        $results['grade'] = ((float)($row['MRL'] ?? 0) > (float)$row['MRL_Result']) ? 'green' : 'red';
    }
    if (!empty($row['ResultStatus'])) {
        $results['results'] = (string)$row['ResultStatus'];
        $results['standard'] = '-';
        $results['grade'] = '';
    }
    if (!empty($row['RangeResult'])) {
        $results['results'] = (function_exists('formatResultNumber')
            ? formatResultNumber($row, $row['RangeResult'])
            : (string)$row['RangeResult']) . ' ' . (string)($row['UnitOfMeasure'] ?? '');
        $results['standard'] = (string)($row['MinLimit'] ?? '') . ' - ' . (string)($row['MaxLimit'] ?? '');
        $results['grade'] = 'green';
        if ($row['MinLimit'] > $row['RangeResult']) {
            $results['grade'] = 'blue';
        }
        if ($row['RangeResult'] > $row['MaxLimit']) {
            $results['grade'] = 'red';
        }
    }
    return $results;
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

    // Use the same parameter mapping as ajax/get_test_results.php.
    // Some result rows reference testparameters.BaseID rather than ParameterID.
    $stmt = $conn->prepare("SELECT tr.*, tp.ParameterName, tp.MinLimit, tp.MaxLimit, tp.MRL, tp.MRLUnit,
                                  tp.UnitOfMeasure, tp.ResultType AS ParameterResultType,
                                  COALESCE(bp.ResultType, tp.ResultType, 'quantitativeField') AS ResultType
        FROM test_results tr
        LEFT JOIN testparameters tp
          ON tp.StandardID = tr.StandardID
         AND (tp.ParameterID = tr.ParameterID OR tp.BaseID = tr.ParameterID)
        LEFT JOIN baseparameters bp ON tp.BaseID = bp.ParameterID
        WHERE tr.SampleID = ? AND tr.HeaderID = ? AND tr.StatusID IN (2,3,4)
        ORDER BY tr.resultsID");
    if ($stmt) {
        $stmt->bind_param('si', $sampleID, $headerID);
        if ($stmt->execute()) {
            $rs = $stmt->get_result();
            $parts = [];
            while ($r = $rs->fetch_assoc()) {
                // Use the same result/standard/grade rules as the COA validateresults().
                $validated = validateresults($r);
                $grade = $validated['grade'];
                $value = $validated['results'];

                $interpretation = ($grade === '') ? $value
                    : (($grade === 'blue') ? 'Below the minimum specified level'
                    : (($grade === 'green') ? 'Optimum'
                    : (($grade === 'red') ? ' above the maximum specified level' : $value)));

                $name = trim((string)($r['ParameterName'] ?? ''));
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
        $texts[$field] = coaTextForEditor($savedValue !== '' ? (string)$saved[$field] : (string)$defaults[$field]);
    }
    echo json_encode(['success' => true, 'texts' => $texts]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported operation.']);
