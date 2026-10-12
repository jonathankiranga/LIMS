<?php
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

$headerId = isset($_GET['header_id']) ? (int)$_GET['header_id'] : 0;
$department = strtolower(trim((string)($_GET['department'] ?? '')));
$departments = [
    'chemical' => ['label' => 'CHEMICAL DEPARTMENT', 'category' => 'chemical'],
    'microbiological' => ['label' => 'MICROBIOLOGICAL DEPARTMENT', 'category' => 'microbiological']
];

if ($headerId <= 0 || !isset($departments[$department])) {
    http_response_code(400);
    exit('Invalid receipt request.');
}

$metaStmt = $conn->prepare("
    SELECT sh.HeaderID, sh.DocumentNo, sh.Date
    FROM sample_header sh
    WHERE sh.HeaderID = ?
    LIMIT 1
");
if (!$metaStmt) {
    http_response_code(500);
    exit('Unable to prepare receipt lookup.');
}
$metaStmt->bind_param('i', $headerId);
$metaStmt->execute();
$metaResult = $metaStmt->get_result();
$header = $metaResult ? $metaResult->fetch_assoc() : null;
$metaStmt->close();

if (!$header) {
    http_response_code(404);
    exit('Registration not found.');
}

$companyName = '';
$companyResult = $conn->query("SELECT company_name FROM company_master ORDER BY company_id DESC LIMIT 1");
if ($companyResult && ($companyRecord = $companyResult->fetch_assoc())) {
    $companyName = trim((string)($companyRecord['company_name'] ?? ''));
}

$sql = "
    SELECT
        st.SampleID,
        COALESCE(st.sample_name, '') AS SampleName,
        tp.ParameterName
    FROM sample_tests st
    INNER JOIN test_results tr
        ON tr.HeaderID = st.HeaderID
       AND tr.TestID = st.TestID
       AND tr.SampleID = st.SampleID
    INNER JOIN testparameters tp
        ON tp.ParameterID = tr.ParameterID
       AND tp.StandardID = tr.StandardID
    WHERE st.HeaderID = ?
      AND LOWER(tp.Category) = ?
    ORDER BY st.SampleID, tp.ParameterName
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('Anonymous sample receipt SQL prepare failed: ' . $conn->error);
    http_response_code(500);
    exit('Unable to prepare department parameter lookup. Please check the server PHP error log.');
}
$category = $departments[$department]['category'];
$stmt->bind_param('is', $headerId, $category);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($result && ($row = $result->fetch_assoc())) {
    $rows[] = $row;
}
$stmt->close();
$conn->close();

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('LIMS');
$pdf->SetAuthor('Laboratory');
$pdf->SetTitle('Anonymous Sample Registration Receipt');
$pdf->SetSubject($departments[$department]['label']);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 9);

$esc = static function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$regDate = !empty($header['Date']) ? date('d M Y', strtotime($header['Date'])) : '';
$batchRef = $esc($header['DocumentNo'] ?? '');

$html = '
<table cellpadding="5" cellspacing="0" border="0" width="100%">
  <tr>
    <td width="100%" align="center">
      <span style="font-size:15px;font-weight:bold;">' . $esc($companyName) . '</span><br>
      <span style="font-size:16px;font-weight:bold;">LABORATORY SAMPLE REGISTRATION</span><br>
      <span style="font-size:10px;">ANONYMOUS INTERNAL RECEIPT</span><br>
      <span style="font-size:11px;font-weight:bold;">' . $esc($departments[$department]['label']) . '</span>
    </td>
  </tr>
</table>
<hr>
<table cellpadding="5" cellspacing="0" border="0" width="100%">
  <tr>
    <td width="50%"><b>BATCH REF:</b> ' . $batchRef . '</td>
    <td width="50%"><b>REGISTRATION DATE:</b> ' . $esc($regDate) . '</td>
  </tr>
  <tr>
    <td width="100%"><b>RESULTS EXPECTED ON DATE:</b> ______________________________</td>
  </tr>
  <tr>
    <td width="100%"><b>SPECIFY ANY INFORMATION FROM THE CUSTOMER THAT CAN AFFECT THE RESULTS:</b></td>
  </tr>
  <tr><td width="100%">________________________________________________________________________________</td></tr>
  <tr><td width="100%">________________________________________________________________________________</td></tr>
  <tr><td width="100%">________________________________________________________________________________</td></tr>
</table>
<br>
<table cellpadding="4" cellspacing="0" border="1" width="100%">
  <thead>
    <tr style="background-color:#e9ecef;font-weight:bold;">
      <th width="19%">Sample ID</th>
      <th width="23%">Sample Name</th>
      <th width="30%">TEST Parameter</th>
      <th width="28%">Comments</th>
    </tr>
  </thead>
  <tbody>';

if (!$rows) {
    $html .= '<tr><td colspan="4" align="center">No test parameters are assigned to this department for this registration.</td></tr>';
} else {
    foreach ($rows as $row) {
        // Each registered test parameter is printed as a normal body row.
        // Do not print StandardName/sample-standard values as a separate sample-type heading.
        $html .= '<tr>'
            . '<td width="19%">' . $esc($row['SampleID']) . '</td>'
            . '<td width="23%">' . $esc($row['SampleName']) . '</td>'
            . '<td width="30%">' . $esc($row['ParameterName']) . '</td>'
            . '<td width="28%">&nbsp;<br><br></td>'
            . '</tr>';
    }
}
$html .= '</tbody></table>
<br><br>
<table cellpadding="6" cellspacing="0" border="0" width="100%">
  <tr><td width="100%"><b>DEPARTMENT SIGN-OFF</b></td></tr>
  <tr><td>Staff Name: __________________________________________________________</td></tr>
  <tr><td>Signature: ____________________________________________________________</td></tr>
  <tr><td>Date: _______________________________________________________________</td></tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$filename = 'Anonymous_Receipt_' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$header['DocumentNo']) . '_' . $department . '.pdf';
$pdf->Output($filename, 'I');
exit;
