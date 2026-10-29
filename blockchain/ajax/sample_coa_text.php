<?php
require '../db_connection.php';
header('Content-Type: application/json; charset=utf-8');

$sampleID = trim((string)($_REQUEST['SampleID'] ?? $_REQUEST['sampleID'] ?? ''));
$headerID = (int)($_REQUEST['HeaderID'] ?? 0);
if ($sampleID === '' || $headerID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sample ID and header ID are required.']);
    exit;
}

function coaTextDefaults($conn, $sampleID) {
    $defaults = ['ResultsInterpretation' => '', 'StatementOfConformity' => '', 'Notes' => ''];

    $stmt = $conn->prepare("SELECT ts.Description
        FROM test_results tr
        JOIN teststandards ts ON ts.StandardID = tr.StandardID
        WHERE tr.SampleID = ?
        ORDER BY tr.resultsID DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $sampleID);
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

    $stmt = $conn->prepare("SELECT tr.*, tp.ParameterName, tp.MinLimit, tp.MaxLimit,
                                  tp.UnitOfMeasure, tp.ResultType AS ParameterResultType
        FROM test_results tr
        LEFT JOIN testparameters tp
          ON tp.ParameterID = tr.ParameterID AND tp.StandardID = tr.StandardID
        WHERE tr.SampleID = ? AND tr.StatusID IN (2,3,4)
        ORDER BY tr.resultsID");
    if ($stmt) {
        $stmt->bind_param('s', $sampleID);
        if ($stmt->execute()) {
            $rs = $stmt->get_result();
            $parts = [];
            while ($r = $rs->fetch_assoc()) {
                $name = trim((string)($r['ParameterName'] ?? 'Test'));
                $grade = '';
                $value = '';
                if (trim((string)($r['ResultStatus'] ?? '')) !== '') {
                    $value = (string)$r['ResultStatus'];
                } elseif ($r['RangeResult'] !== null && $r['RangeResult'] !== '') {
                    $numeric = (float)$r['RangeResult'];
                    $value = (string)$r['RangeResult'];
                    if ($r['MinLimit'] !== null && $numeric < (float)$r['MinLimit']) $grade = 'blue';
                    elseif ($r['MaxLimit'] !== null && $numeric > (float)$r['MaxLimit']) $grade = 'red';
                    else $grade = 'green';
                } elseif ($r['MRL_Result'] !== null && $r['MRL_Result'] !== '') {
                    $value = (string)$r['MRL_Result'];
                    if (is_numeric($value) && $r['MinLimit'] !== null && $r['MaxLimit'] !== null) {
                        $numeric = (float)$value;
                        if ($numeric < (float)$r['MinLimit']) $grade = 'blue';
                        elseif ($numeric > (float)$r['MaxLimit']) $grade = 'red';
                        else $grade = 'green';
                    }
                }
                $interpretation = ($grade === 'blue') ? 'Below the minimum specified level'
                    : (($grade === 'green') ? 'Optimum'
                    : (($grade === 'red') ? 'above the maximum specified level' : $value));
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
    $defaults = coaTextDefaults($conn, $sampleID);
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
    echo json_encode(['success' => true, 'texts' => [
        'ResultsInterpretation' => $saved !== null ? (string)($saved['ResultsInterpretation'] ?? '') : $defaults['ResultsInterpretation'],
        'StatementOfConformity' => $saved !== null ? (string)($saved['StatementOfConformity'] ?? '') : $defaults['StatementOfConformity'],
        'Notes' => $saved !== null ? (string)($saved['Notes'] ?? '') : $defaults['Notes']
    ]]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported operation.']);
