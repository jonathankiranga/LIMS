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
    SELECT sh.HeaderID, sh.DocumentNo, sh.Date, sh.SamplingDate, sh.SampledBy
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

$sql = "
    SELECT
        st.SampleID,
        COALESCE(st.sample_name, '') AS SampleName,
        COALESCE(ts.StandardName, '') AS SampleType,
        tp.ParameterName
    FROM sample_tests st
    INNER JOIN test_results tr
        ON tr.HeaderID = st.HeaderID
       AND tr.TestID = st.TestID
       AND tr.SampleID = st.SampleID
    INNER JOIN testparameters tp
        ON tp.ParameterID = tr.ParameterID
       AND tp.StandardID = tr.StandardID
    LEFT JOIN teststandards ts ON ts.StandardID = st.StandardID
    WHERE st.HeaderID = ?
      AND LOWER(tp.Category) = ?
    ORDER BY st.SampleID, tp.ParameterName
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit('Unable to prepare department parameter lookup.');
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
$samplingDate = !empty($header['SamplingDate']) ? date('d M Y', strtotime($header['SamplingDate'])) : '';
$batchRef = $esc($header['DocumentNo'] ?? '');
$sampledBy = $esc($header['SampledBy'] ?? '');

$html = '
<table cellpadding="5" cellspacing="0" border="0" width="100%">
  <tr>
    <td width="100%" align="center">
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
    <td width="50%"><b>SAMPLING DATE:</b> ' . $esc($samplingDate) . '</td>
    <td width="50%"><b>SAMPLED BY:</b> ' . $sampledBy . '</td>
  </tr>
</table>
<br>
<table cellpadding="5" cellspacing="0" border="1" width="100%">
  <thead>
    <tr style="background-color:#e9ecef;font-weight:bold;">
      <th width="19%">Sample ID</th>
      <th width="23%">Sample Name</th>
      <th width="30%">TEST Parameter</th>
      <th width="14%">RESULTS</th>
      <th width="14%">USER</th>
    </tr>
  </thead>
  <tbody>';

$lastGroup = '';
if (!$rows) {
    $html .= '<tr><td colspan="5" align="center">No test parameters are assigned to this department for this registration.</td></tr>';
} else {
    foreach ($rows as $row) {
        $groupKey = (string)$row['SampleID'] . '|' . (string)$row['SampleName'] . '|' . (string)$row['SampleType'];
        if ($groupKey !== $lastGroup) {
            $html .= '<tr style="background-color:#f5f5f5;">'
                . '<td colspan="2"><b>Sample Type</b></td>'
                . '<td colspan="3">' . $esc($row['SampleType']) . '</td>'
                . '</tr>';
            $lastGroup = $groupKey;
        }
        $html .= '<tr>'
            . '<td>' . $esc($row['SampleID']) . '</td>'
            . '<td>' . $esc($row['SampleName']) . '</td>'
            . '<td>' . $esc($row['ParameterName']) . '</td>'
            . '<td>&nbsp;<br><br></td>'
            . '<td>&nbsp;<br><br></td>'
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
