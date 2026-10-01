<?php
require_once(dirname(__FILE__) . '/../vendor/autoload.php'); 
require_once 'functions.php';

$config = include('../include/config.php');
$conn = new mysqli($config['DB_HOST'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME']);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// 1. Get date range from AJAX
$sampleIDdata = $_POST['sampleIDdata'] ?? '';
$dates = explode('|', $sampleIDdata);
$fromDate = (!empty($dates[0])) ? $dates[0] : date('Y-m-01');
$toDate   = (!empty($dates[1])) ? $dates[1] : date('Y-m-d');

// 2. Fetch Report Data
$sql = "SELECT 
            sh.`Date`,
            sh.DocumentNo AS BatchNo,
            dr.Customer AS Customer,
            COUNT(st.TestID) AS NumberOfSamples
        FROM Sample_Header sh
        JOIN Sample_Tests st ON sh.HeaderID = st.HeaderID
        LEFT JOIN debtors dr ON sh.CustomerID = dr.itemcode
        WHERE sh.`Date` BETWEEN ? AND ?
        GROUP BY sh.DocumentNo, dr.Customer, sh.`Date`
        ORDER BY sh.`Date` ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $fromDate, $toDate);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// 3. Define Custom PDF Class to handle the COA-style header
class CustomPDF extends TCPDF {
    public function Header() {
        global $conn;
        // Fetch Company Details (from your study code)
        $sql = "SELECT * FROM company_master ORDER BY company_id DESC LIMIT 1";
        $result = $conn->query($sql);
        $record = $result->fetch_assoc();

        $this->SetY(10);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 5, "LAB TEST SUMMARY REPORT", 0, 1, 'C');
        
        // Draw the header border box (similar to your COA code)
        $this->Rect(10, 18, 190, 35, 'D'); 

        // Left Section: Company Info
        $this->SetXY(12, 22);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(90, 5, $record['company_name'], 0, 1, 'L');
        $this->SetFont('helvetica', '', 8);
        $this->SetX(12);
        $this->MultiCell(90, 4, $record['address'] . "\n" . $record['address1'] . "\n" . $record['email'] . "\n" . $record['telephne'], 0, 'L');

        // Right Section: Report Period Info
        $this->SetXY(105, 22);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(90, 5, "REPORT DETAILS", 0, 1, 'L');
        $this->SetFont('helvetica', '', 8);
        $this->SetX(105);
        $this->Cell(90, 5, "Period From: " . $GLOBALS['fromDate'], 0, 1, 'L');
        $this->SetX(105);
        $this->Cell(90, 5, "Period To: " . $GLOBALS['toDate'], 0, 1, 'L');
        $this->SetX(105);
        $this->Cell(90, 5, "Generated On: " . date('d-m-Y H:i'), 0, 1, 'L');

        // Vertical Divider line
        $this->Line(105, 18, 105, 53);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
    }
}

// 4. Initialize PDF
$pdf = new CustomPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 60, 10); // Top margin increased to 60 to clear the header box
$pdf->AddPage();

// 5. Create Table with Exact Column Width Alignment
$html = '
<style>
    table { border-collapse: collapse; width: 100%; }
    th { background-color: #3498db; color: white; font-weight: bold; text-align: center; border: 1px solid #ddd; }
    td { border: 1px solid #ddd; padding: 8px; font-size: 9px; }
    .total-row { background-color: #f2f2f2; font-weight: bold; }
</style>
<table>
    <thead>
        <tr>
            <th width="20%">DATE</th>
            <th width="25%">BATCH NO</th>
            <th width="35%">CUSTOMER</th>
            <th width="20%">SAMPLES</th>
        </tr>
    </thead>
    <tbody>';

$totalSamples = 0;
if (count($data) > 0) {
    foreach ($data as $row) {
        $html .= '<tr>
            <td width="20%">' . $row['Date'] . '</td>
            <td width="25%">' . $row['BatchNo'] . '</td>
            <td width="35%">' . $row['Customer'] . '</td>
            <td width="20%" align="center">' . $row['NumberOfSamples'] . '</td>
        </tr>';
        $totalSamples += $row['NumberOfSamples'];
    }
} else {
    $html .= '<tr><td colspan="4" align="center">No records found for selected dates.</td></tr>';
}

$html .= '
    <tr class="total-row">
        <td colspan="3" align="right">TOTAL SAMPLES:</td>
        <td align="center">' . $totalSamples . '</td>
    </tr>
</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

// 6. Output PDF
ob_end_clean();
header('Content-Type: application/pdf');
$pdf->Output("LabSummaryReport_$fromDate.pdf", 'I');