<?php
require '../db_connection.php';

header('Content-Type: application/json');

$sampleID = isset($_POST['sampleID']) ? trim((string)$_POST['sampleID']) : '';
$contractor = isset($_POST['contractor']) ? (int)$_POST['contractor'] : 0;

if ($sampleID === '' || $contractor <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sample ID and contractor are required.']);
    exit;
}

$customerStmt = $conn->prepare("SELECT name, email FROM subcontractors WHERE id = ? LIMIT 1");
if (!$customerStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare contractor query.']);
    exit;
}
$customerStmt->bind_param("i", $contractor);
$customerStmt->execute();
$customerRes = $customerStmt->get_result();
$customer = $customerRes ? $customerRes->fetch_assoc() : null;
$customerStmt->close();

if (!$customer) {
    echo json_encode(['success' => false, 'message' => 'Contractor not found.']);
    exit;
}

$template = get_email_template($conn, 'sample_subcontrating');
$subject = (string)($template['subject'] ?? '');
$body = (string)($template['body'] ?? '');
$body = str_replace('{{contractor_name}}', (string)$customer['name'], $body);

$query = "SELECT tp.ParameterName
          FROM sample_tests ST
          JOIN test_results TR ON ST.TestID = TR.TestID
          JOIN TestParameters tp ON TR.ParameterID = tp.ParameterID AND TR.StandardID = tp.StandardID
          JOIN test_assignments ta ON TR.resultsID = ta.resultsID
          WHERE ST.SampleID = ? AND ta.subcontractor = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param("si", $sampleID, $contractor);
$stmt->execute();
$result = $stmt->get_result();
$samples = [];
while ($row = $result->fetch_assoc()) {
    $samples[] = $row;
}
$stmt->close();

$emailTemplate = "<ol style='list-style-position: inside; text-align: center; margin: 0 auto; padding: 0;'>";
foreach ($samples as $sample) {
    $param = htmlspecialchars((string)($sample['ParameterName'] ?? ''), ENT_QUOTES, 'UTF-8');
    $sid = htmlspecialchars($sampleID, ENT_QUOTES, 'UTF-8');
    $emailTemplate .= "<li style='margin-bottom: 10px; font-size: 12px; display: inline-block; text-align: left; min-width:600px;'>Sample ID:<b>{$sid}</b> Test For:<i>{$param}</i></li>";
}
$emailTemplate .= '</ol>';

$body = str_replace('{{sample_list}}', $emailTemplate, $body);

echo json_encode([
    'success' => true,
    'emailto' => (string)($customer['email'] ?? ''),
    'subject' => $subject,
    'body' => $body
]);
exit;

function get_email_template($conn, $eventName)
{
    $stmt = $conn->prepare("SELECT subject, body FROM email_templates WHERE event_name = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $eventName);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (is_array($row)) {
            return $row;
        }
    }

    return [
        'subject' => 'Request for Samples Test',
        'body' => "
            Dear {{contractor_name}},
            <br><br>
            We are reaching out to request your assistance in subcontracting the following test samples:
            <br><br>
            {{sample_list}}
            <br><br>
            We kindly ask you to confirm your availability to perform these tests at your earliest convenience.
            <br><br>
            Best regards,<br>
            Your Lab Team
        "
    ];
}

