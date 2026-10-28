<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BlockchainLIMS');
    session_start();
}
require_once(dirname(__FILE__) . '/../vendor/autoload.php'); // Adjust the path as necessary
require_once 'BarCodeClass.inc';
require_once 'functions.php';

$config = include('../include/config.php'); // Load the config file
$secretKey   = $config['SECRET_KEY'];  // Access the SECRET_KEY from config
$db_host     = $config['DB_HOST'];
$db_name     = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new mysqli($db_host,$db_username,$db_password,$db_name);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}
 
if (!isset($PathPrefix)) {
    $PathPrefix=dirname(__FILE__) . '/../';
}

if (empty($_SESSION['LogoFile'])) {
    $_SESSION['LogoFile'] = findLogoFile($PathPrefix);
}

if (empty($_SESSION['KenhasFile'])) {
    $_SESSION['KenhasFile'] = findkenhaFile($PathPrefix);
}

if (empty($_SESSION['NeemaFile'])) {
   $_SESSION['NeemaFile'] = findNeemaFile($PathPrefix);
}

if (empty($_SESSION['LABCODE'])) {
    $_SESSION['LABCODE'] = findLABCODEFile($PathPrefix);
 }

if (empty($_SESSION['ILAC'])) {
   $_SESSION['ILAC'] = findILACFile($PathPrefix);
}

$GLOBALS['QRcode'] = new makebarcode();
$QRcode = $GLOBALS['QRcode'];
$GLOBALS['reportFooterHtml'] = '';
$reportFooterHtml =& $GLOBALS['reportFooterHtml'];
$GLOBALS['includeCompanyWatermark'] = isset($_POST['include_watermark']) && $_POST['include_watermark'] === '1';
$includeCompanyWatermark =& $GLOBALS['includeCompanyWatermark'];

class CustomPDF extends TCPDF {
    
public function Header() {
    // Decorative multi-line wave border matching the report reference.
    $borderLeft = 2;
    $borderTop = 2;
    $borderRight = 208;
    $borderBottom = 295;
    $this->SetLineStyle(['width' => 0.18, 'color' => [174, 198, 219]]);

    // Layer fine sine waves along the top and bottom edges.
    $waveCount = 8;
    $waveStep = 0.65;
    $waveAmplitude = 2.15;
    $waveLength = 22;
    $segment = 1.0;

    for ($i = 0; $i < $waveCount; $i++) {
        $offset = $i * $waveStep;
        $phase = $i * 0.42;

        for ($x = $borderLeft; $x < $borderRight; $x += $segment) {
            $nextX = min($x + $segment, $borderRight);
            $angle1 = (($x - $borderLeft) / $waveLength) * 2 * M_PI + $phase;
            $angle2 = (($nextX - $borderLeft) / $waveLength) * 2 * M_PI + $phase;

            $topY1 = $borderTop + $offset + $waveAmplitude * sin($angle1);
            $topY2 = $borderTop + $offset + $waveAmplitude * sin($angle2);
            $bottomY1 = $borderBottom - $offset + $waveAmplitude * sin($angle1);
            $bottomY2 = $borderBottom - $offset + $waveAmplitude * sin($angle2);

            $this->Line($x, $topY1, $nextX, $topY2);
            $this->Line($x, $bottomY1, $nextX, $bottomY2);
        }
    }

    // Continue the same layered wave motif down both vertical edges.
    for ($i = 0; $i < $waveCount; $i++) {
        $offset = $i * $waveStep;
        $phase = $i * 0.42;

        for ($y = $borderTop; $y < $borderBottom; $y += $segment) {
            $nextY = min($y + $segment, $borderBottom);
            $angle1 = (($y - $borderTop) / $waveLength) * 2 * M_PI + $phase;
            $angle2 = (($nextY - $borderTop) / $waveLength) * 2 * M_PI + $phase;

            $leftX1 = $borderLeft + $offset + $waveAmplitude * sin($angle1);
            $leftX2 = $borderLeft + $offset + $waveAmplitude * sin($angle2);
            $rightX1 = $borderRight - $offset + $waveAmplitude * sin($angle1);
            $rightX2 = $borderRight - $offset + $waveAmplitude * sin($angle2);

            $this->Line($leftX1, $y, $leftX2, $nextY);
            $this->Line($rightX1, $y, $rightX2, $nextY);
        }
    }

    // Corner medallions from the reference report.
    $this->SetLineStyle(['width' => 0.35, 'color' => [190, 211, 232]]);
    $cornerPoints = [
        [18, 18], [192, 18], [18, 279], [192, 279]
    ];
    foreach ($cornerPoints as $point) {
        $this->Circle($point[0], $point[1], 7, 0, 360, 'D');
        $this->Circle($point[0], $point[1], 10.5, 0, 360, 'D');
        $this->SetFillColor(218, 231, 247);
        $this->Circle($point[0], $point[1], 1.6, 0, 360, 'F');
    }

    global $conn,$QRcode,$includeCompanyWatermark;
    $sql = "SELECT * FROM company_master ORDER BY company_id DESC LIMIT 1";
    $result = $conn->query($sql);
    $record = $result->fetch_assoc();

    if ($includeCompanyWatermark && !empty($record['company_name'])) {
        $watermarkSeries = '';
        $wmStmt = $conn->prepare("SELECT sh.DocumentNo FROM sample_header sh JOIN test_results tr ON sh.HeaderID = tr.HeaderID WHERE tr.SampleID = ?");
        if ($wmStmt) {
            $wmSampleId = isset($_REQUEST['sampleID']) ? $_REQUEST['sampleID'] : '';
            $wmStmt->bind_param("s", $wmSampleId);
            $wmStmt->execute();
            $wmRow = $wmStmt->get_result()->fetch_assoc();
            if ($wmRow && !empty($wmRow['DocumentNo'])) {
                $watermarkSeries = trim((string)$wmRow['DocumentNo']);
            }
        }
        $watermarkText = ($watermarkSeries !== '' ? $watermarkSeries . ' - ' : '') . $record['company_name'];
        $this->drawCompanyWatermark($watermarkText);
    }

    
    // Keep the existing company query, ERP customer lookup and sample fields.
    $companyRecord = $record;
    $DefaultFont = 7;
    $sampleID = trim((string)($_POST['sampleID'] ?? ''));
    $record = [];
    $customerData = [];
    if ($sampleID !== '') {
        $stmt = $conn->prepare("
            SELECT
                sh.*,
                st.*,
                tr.SampleID AS ResultSampleID,
                tr.TestID AS ResultTestID,
                tr.StandardID AS ResultStandardID
            FROM sample_header sh
            JOIN test_results tr ON sh.HeaderID = tr.HeaderID
            LEFT JOIN sample_tests st ON st.TestID = tr.TestID
            WHERE tr.SampleID = ?
        ");
        if ($stmt) {
            $stmt->bind_param("s", $sampleID);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result ? ($result->fetch_assoc() ?: []) : [];
            $stmt->close();
        }
        $customerCode = trim((string)($record['CustomerID'] ?? ''));
        $customerData = $this->fetchErpCustomerDetails($customerCode);
    }

    global $QRcode;
    $qrcodelink = ($sampleID !== '' && isset($QRcode)) ? $QRcode->getQr($sampleID) : '';

    // Letterhead: logo at left, title centred, QR at far right; no lab contact text here.
    if (!empty($_SESSION['LogoFile'])) {
        $this->Image($_SESSION['LogoFile'], 10, 9, 32, 12);
    }
    $this->SetFont('montserrat', 'B', 10);
    $this->SetXY(55, 12);
    $this->Cell(100, 5, 'CERTIFICATE OF ANALYSIS (COA)', 0, 0, 'C');
    if ($qrcodelink !== '') { $this->Image($qrcodelink, 185, 9, 15, 15); }

    $this->SetLineStyle(['width' => 0.25, 'color' => [190, 211, 232]]);
    $this->SetDrawColor(190, 211, 232);
    $this->SetTextColor(35, 45, 55);

    // Frame 1: Company Details.
    $this->Rect(10, 25, 95, 24, 'D');
    $this->SetFont('montserrat', 'B', 7);
    $this->SetXY(12, 25.5);
    // Company details shown without a heading.
    $companyPhone = $companyRecord['telephone'] ?? ($companyRecord['telephne'] ?? '');
    $companyRows = [
        $companyRecord['company_name'] ?? '',
        $companyRecord['address'] ?? '',
        $companyRecord['address1'] ?? '',
        $companyRecord['address2'] ?? '',
        $companyRecord['address3'] ?? '',
        $companyRecord['email'] ?? '',
        $companyPhone
    ];
    $this->SetFont('montserrat', '', 6.4);
    $cy = 26.5;
    foreach ($companyRows as $companyLine) {
        if (trim((string)$companyLine) !== '') {
            $this->SetXY(12, $cy);
            $this->Cell(91, 3.0, $this->truncateText((string)$companyLine, 80), 0, 0, 'L');
        }
        $cy += 3.0;
    }

    // Frame 2: Customer Details, using the existing ERP master lookup.
    $this->Rect(105, 25, 95, 24, 'D');
    $this->SetFont('montserrat', 'B', 7);
    $this->SetXY(107, 25.5);
    // Customer details shown without a heading.
    $customerRows = [
        ['Customer: ', $customerData['customer'] ?? ''],
        ['Company: ', $customerData['company'] ?? ''],
        ['Postal Code: ', $customerData['postcode'] ?? ''],
        ['City: ', $customerData['city'] ?? ''],
        ['Telephone: ', $customerData['phone'] ?? ''],
        ['Email: ', $customerData['email'] ?? '']
    ];
    $this->SetFont('montserrat', '', 6.4);
    $cy = 26.5;
    foreach ($customerRows as $customerRow) {
        $customerValue = trim((string)$customerRow[1]);
        if ($customerValue !== '') {
            $this->SetXY(107, $cy);
            $this->Cell(91, 3.0, $this->truncateText($customerValue, 80), 0, 0, 'L');
            $cy += 3.0;
        }
    }

    // Build both sample metadata groups first, then size both frames to the visible rows.
    $testreceived = getdatesamplereceived($sampleID);
    $testended = getdatesamplecompleteed($sampleID);
    $realsampleid = getdatesampleid($sampleID);

    $sampleRowsLeft = [
        ['Sample Batch No: ', $record['DocumentNo'] ?? ''],
        ['Sample ID: ', $realsampleid ?? ''],
        ['Sampling Date: ', formatDate($record['SamplingDate'] ?? null, 'dd-mm-yy')],
        ['Sampled By: ', $record['SampledBy'] ?? ''],
        ['Sampling Method: ', $record['SamplingMethod'] ?? ''],
        ['Sample Source: ', $record['ExternalSample'] ?? ''],
        ['Sample Name: ', $record['sample_name'] ?? '']
    ];
    $sampleRowsRight = [
        ['Date Received: ', formatDate($record['Date'] ?? null, 'dd-mm-yy')],
        ['Date Test Started: ', formatDate($testreceived, 'dd-mm-yy')],
        ['Date Test Completed: ', formatDate($testended, 'dd-mm-yy')],
        ['Report Issued Date: ', formatDate(getReportIssuedDate($sampleID), 'dd-mm-yy')],
        ['Sample Method: ', $record['sample_method'] ?? ''],
        ['B/No: ', $record['BatchNo'] ?? ''],
        ['B/size: ', $record['BatchSize'] ?? ''],
        ['Date of Man: ', formatDate($record['ManufactureDate'] ?? null, 'dd-mm-yy')],
        ['Date of Exp: ', formatDate($record['ExpDate'] ?? null, 'dd-mm-yy')],
        ['Chilled Exp: ', formatDate($record['chilled_date_of_expiry'] ?? null, 'dd-mm-yy')],
        ['Frozen Exp: ', formatDate($record['frozen_date_of_expiry'] ?? null, 'dd-mm-yy')],
        ['SKU: ', $record['SKU'] ?? ''],
        ['Condition: ', $record['condition_of_sample'] ?? '']
    ];
    $sampleRowsLeft = array_values(array_filter($sampleRowsLeft, function ($sampleRow) { return trim((string)$sampleRow[1]) !== ''; }));
    $sampleRowsRight = array_values(array_filter($sampleRowsRight, function ($sampleRow) { return trim((string)$sampleRow[1]) !== ''; }));
    $sampleFrameHeight = max(count($sampleRowsLeft), count($sampleRowsRight)) * 3.0 + 5.0;

    // Frames 3 and 4 share a height based on the visible metadata rows.
    $this->Rect(10, 51, 95, $sampleFrameHeight, 'D');
    $this->Rect(105, 51, 95, $sampleFrameHeight, 'D');

    $this->SetFont('montserrat', '', 7);
    $cy = 53.5;
    foreach ($sampleRowsLeft as $sampleRow) {
        $this->SetXY(12, $cy);
        $this->Cell(91, 3.0, $this->truncateText($sampleRow[0] . (string)$sampleRow[1], 85), 0, 0, 'L');
        $cy += 3.0;
    }

    $this->SetFont('montserrat', '', 6.4);
    $cy = 53.5;
    foreach ($sampleRowsRight as $sampleRow) {
        $this->SetXY(107, $cy);
        $this->Cell(91, 3.0, $this->truncateText($sampleRow[0] . (string)$sampleRow[1], 85), 0, 0, 'L');
        $cy += 3.0;
    }
    $this->SetTextColor(0, 0, 0);
}



private function fetchErpCustomerDetails($customerCode) {
    global $config;

    static $customerCache = [];
    $customerCode = trim((string)$customerCode);
    $emptyDetails = [
        'customer' => '',
        'company' => '',
        'postcode' => '',
        'city' => '',
        'phone' => '',
        'email' => ''
    ];

    if ($customerCode === '') {
        return $emptyDetails;
    }
    if (array_key_exists($customerCode, $customerCache)) {
        return $customerCache[$customerCode];
    }

    $erpApiUrl = is_array($config) ? trim((string)($config['ERP_API_URL'] ?? '')) : '';
    if ($erpApiUrl === '' || !function_exists('curl_init')) {
        $customerCache[$customerCode] = $emptyDetails;
        return $customerCache[$customerCode];
    }

    $postData = json_encode([
        'action' => 'search_customers',
        'query' => $customerCode,
        'limit' => 10,
        'offset' => 0
    ]);

    $ch = curl_init($erpApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $erpResponse = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && is_string($erpResponse) && $erpResponse !== '') {
        $erpData = json_decode($erpResponse, true);
        foreach (($erpData['customers'] ?? []) as $debtor) {
            $debtorCode = trim((string)($debtor['itemcode'] ?? $debtor['code'] ?? ''));
            if ($debtorCode !== '' && strcasecmp($debtorCode, $customerCode) === 0) {
                $customerCache[$customerCode] = [
                    'customer' => trim((string)($debtor['customer'] ?? $debtor['name'] ?? '')),
                    'company' => trim((string)($debtor['company'] ?? '')),
                    'postcode' => trim((string)($debtor['postcode'] ?? '')),
                    'city' => trim((string)($debtor['city'] ?? '')),
                    'phone' => trim((string)($debtor['phone'] ?? '')),
                    'email' => trim((string)($debtor['email'] ?? ''))
                ];
                return $customerCache[$customerCode];
            }
        }
    }

    $customerCache[$customerCode] = $emptyDetails;
    return $customerCache[$customerCode];
}

private function drawCompanyWatermark($watermarkText) {
    $watermarkText = trim((string)$watermarkText);
    if ($watermarkText === '') {
        return;
    }

    $centerX = $this->getPageWidth() / 2;
    $centerY = $this->getPageHeight() / 2;
    $fontSize = 30;

    $this->SetFont('montserrat', 'B', $fontSize);
    while ($this->GetStringWidth($watermarkText) > 150 && $fontSize > 18) {
        $fontSize--;
        $this->SetFont('montserrat', 'B', $fontSize);
    }

    $textWidth = $this->GetStringWidth($watermarkText);
    if (method_exists($this, 'SetAlpha')) {
        $this->SetAlpha(0.2);
    }
    $this->SetTextColor(140, 140, 140);
    $this->StartTransform();
    $this->Rotate(45, $centerX, $centerY);
    $this->Text($centerX - ($textWidth / 2), $centerY, $watermarkText);
    $this->StopTransform();
    if (method_exists($this, 'SetAlpha')) {
        $this->SetAlpha(1);
    }
    $this->SetTextColor(0, 0, 0);
}

private function truncateText($text, $maxLength) {
    return mb_strimwidth($text, 0, $maxLength, '...');
}

    function fitText($text, $width, $maxFontSize, $minFontSize = 8) {
        $currentFontSize = $maxFontSize;
        $this->SetFont('montserrat', '', $currentFontSize);

        while ($this->GetStringWidth($text) > $width && $currentFontSize > $minFontSize) {
            $currentFontSize--;
            $this->SetFont('montserrat', '', $currentFontSize);
        }

        return $currentFontSize; // Final font size that fits
    }

   
public function Footer() {
  global $reportFooterHtml, $conn;
  static $printedBy = null;
  if ($printedBy === null) {
      $printedBy = trim((string)($_SESSION['full_name'] ?? ''));
      if ($printedBy === '') {
      }
      $printedByID = $_SESSION['UserID'] ?? '';
      if ($printedByID !== '' && isset($conn) && $conn instanceof mysqli) {
          $printedByStmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ? LIMIT 1");
          if ($printedByStmt) {
              $printedByStmt->bind_param("s", $printedByID);
              if ($printedByStmt->execute()) {
                  $printedByResult = $printedByStmt->get_result();
                  if ($printedByResult && ($printedByRow = $printedByResult->fetch_assoc()) && trim((string)$printedByRow['full_name']) !== '') {
                      $printedBy = trim((string)$printedByRow['full_name']);
                  }
              }
              $printedByStmt->close();
          }
      }
  }
  $this->SetAutoPageBreak(false, 0);
  $this->SetY(-78);
  if (!empty($reportFooterHtml)) {
      $this->writeHTMLCell(190, 66, 10, $this->getPageHeight() - 78, $reportFooterHtml, 0, 0, 0, true, 'L', true);
  }
  $this->SetAutoPageBreak(true, 80);
  $this->SetXY(10, -8);
  $this->SetFont('montserrat', '', 7);
  if ($printedBy !== '') {
      $this->Cell(80, 4, 'Printed By: ' . $printedBy, 0, 0, 'L');
  }
  $this->SetXY(80, -8);
  $this->SetFont('montserrat', 'I', 8);
  $pageNumber = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
  $this->Cell(120, 4, $pageNumber, 0, 0, 'C');
}
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_REQUEST['preview'])) {
    $sampleID = $_REQUEST['sampleID'];

    $SampletypeNotes = TSDescription($sampleID);
    $samples = [];
 
    $stmt = $conn->prepare("SELECT tr.*,tp.*,ts.* FROM test_results tr 
    LEFT JOIN testparameters tp ON tp.ParameterID=tr.ParameterID AND tp.StandardID=tr.StandardID
    LEFT JOIN teststandards ts ON ts.StandardID=tr.StandardID
    WHERE tr.`SampleID` = ? AND tr.`StatusID` = 4 ");
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
     while ($row = $result->fetch_assoc()) {
            $samples[] = $row;
        }
  
    // Initialize custom PDF
    $pdf = new CustomPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Jonathan');
    $pdf->SetTitle('LIMS Report');
    $pdf->SetMargins(10,98,10); // Adjust top margin to avoid overlap with header
    $pdf->SetAutoPageBreak(true, 80); // Reserve 80 mm for the existing report footer
    $pdf->AddPage();

    // Start creating HTML content
    $html = '<div style="width: 100%; font-family: Helvetica, sans-serif; font-size: 10px;margin-left: 15mm; margin-right: 15mm;">';
    $html .= '<div style="font-family: montserrat, Helvetica, sans-serif; font-size: 9px; font-weight: bold; margin-bottom: 3px;">' . htmlspecialchars((string)($samples[0]['StandardName'] ?? ''), ENT_QUOTES, 'UTF-8') . '</div>';
    $html .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                <thead>
    <tr style="background-color: #3498db; color: white;">
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 38%;">Parameter</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 12%;"><strong>Method</strong></th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 10%;">Units</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 12%;">Results</th>
        <th style="border: 1px solid #ddd; padding: 5px; text-align: left; width: 4%;">Low</th>
        <th style="border: 1px solid #ddd; padding: 5px; text-align: left; width: 4%;">Opt</th>
        <th style="border: 1px solid #ddd; padding: 5px; text-align: left; width: 4%;">High</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 16%;">Limits</th>
    </tr>
</thead><tbody>';
     // Generate table rows dynamically
    foreach ($samples as $sample) {
    $validatedresults = validateresults($sample);
    $resutlts = (strpos($validatedresults['results'], '<sup>') !== false)
        ? $validatedresults['results']
        : htmlentities($validatedresults['results'], ENT_QUOTES, 'UTF-8');
    $standard = htmlentities($validatedresults['standard'], ENT_QUOTES, 'UTF-8');
 
      $html .= '<tr>
            <td style="border: 1px solid #ddd; padding: 17px; width: 38%;">' . accreditationParameterName($sample) . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 12%;">' . $sample['Method'] . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 10%;">' . htmlspecialchars((string)($sample['UnitOfMeasure'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 12%;">' . $resutlts. '</td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 4%; ' . 
                ($validatedresults['grade'] == 'blue' ? 'background-color: blue; color: white;' : '') . '">' . 
            '</td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 4%; ' . 
                ($validatedresults['grade'] == 'green' ? 'background-color: green; color: white;' : '') . '">' . 
            '</td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 4%; ' . 
                ($validatedresults['grade'] == 'red' ? 'background-color: red; color: white;' : '') . '">' . 
            '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 16%;">' . $standard. '</td>
          </tr>';
   }

    $html .= '</tbody></table>'; // Close the table
    $html .= '<div style="text-align: center; margin-top: 10px;">*******End of Test Results*******</div>';
    $html .= '<div style="text-align: left; margin-top: 6px; font-size: 7px;">* Not Accredited &nbsp;&nbsp; ~ Subcontracted</div>'; // Add footer
    $html .= '<div style="text-align: left; margin-top: 10px;"><u>NOTES</u></div>'; // Add footer
    
    $lines = explode("\n", $SampletypeNotes);
    foreach ($lines as $line) {
      $html .= '<div style="text-align: left; font-family: Helvetica, sans-serif; font-size: 8px; line-height: 0.5; margin: 0; padding: 0;color: blue;">'.$line.'</div>'; // Add footer
    }
    
   $html .= '<div style="text-align: left; margin-top: 10px;"><u>INTERPRETATION OF ANALYSIS RESULTS</u></div>'; // Add footer
   $html .= '<div style="text-align: left; font-family: Helvetica, sans-serif; font-size: 8px; line-height: 1.0; margin: 0; padding: 0;color:grey;">' ; 
 
    foreach ($samples as $sample) {
        $validatedresults = validateresults($sample);
        $html .=' Test Results for '. accreditationParameterName($sample) .' are <u>'.
       ($validatedresults['grade'] == '' ? $validatedresults['results'] : '')  . 
       ($validatedresults['grade'] == 'blue' ? 'Below the minimum specified level' : '')  . 
       ($validatedresults['grade'] == 'green' ? 'Optimum' : '')  . 
       ($validatedresults['grade'] == 'red' ? ' above the maximum specified level' : '')  .' </u> ' ;
        
   }
$html .=   '</div>';

$SamplePicture = TSamplePicture($sampleID);

$reportFooterHtml = buildFooterHtml(getsignature($sampleID));

    // Write the HTML content to the PDF
    $pdf->writeHTML($html, true, false, true, false, '');
$pdf->AddPage();
   
//$html = '<div style="text-align: center; margin-top: 20px;"><h1>Sample Picture</h1></div>'; // Add footer
//$html = str_replace('X2', '-', $html);
    $pdf->writeHTML($html, true, false, true, false, '');
$pdf->SetXY(70,120);
$pdf->SetFont('montserrat', '',20);
$pdf->MultiCell(95,14,'Sample Picture', 0, 'C', 0, 1, '', '', true); // Limit characters

// Get the page width and height
$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();
// Get the current Y position
$currentY = $pdf->GetY();
// Define the margin and calculate the remaining space
$marginLeft = 20;  $headerHeight = 140;
// Adjust the available height by subtracting the header area
$availableHeight = $pageHeight - $headerHeight - 80; // 20 is the botto// Calculate the available width
// Get the image dimensions (to maintain aspect ratio)
list($imgWidth, $imgHeight) = getimagesize($SamplePicture);

$adw = $imgWidth*($availableHeight/$imgHeight);
// Position and size the image
$pdf->Image($SamplePicture, $marginLeft, $currentY,$adw,$availableHeight);
// Output the PDF as a string for AJAX
$pdfContent = $pdf->Output('lims_report.pdf', 'S');
header('Content-Type: application/pdf');
echo $pdfContent;
}

function findLogoFile($PathPrefix) {
            $dir = $PathPrefix.'logos/' ;
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'logo.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'logo.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return '../logos/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

function findILACFile($PathPrefix) {
            $dir = $PathPrefix.'logos/' ;
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'ILAC.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'ILAC.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return '../logos/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

function findkenhaFile($PathPrefix) {
            $dir = $PathPrefix.'logos/';
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'KenhasFile.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'KenhasFile.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return '../logos/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

function findNeemaFile($PathPrefix) {
            $dir = $PathPrefix.'logos/' ;
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'neemaLogo.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'neemaLogo.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return '../logos/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }
    
    

function checkPageSpace($pdf, $requiredHeight) {
    $currentY = $pdf->GetY(); // Current Y position
    $pageHeight = $pdf->getPageHeight(); // Total page height
    $bottomMargin = $pdf->getBreakMargin(); // Bottom margin
    $availableSpace = $pageHeight - $currentY - $bottomMargin;
    
    return $availableSpace >= $requiredHeight;
}
    
function findLABCODEFile($PathPrefix) {
            $dir = $PathPrefix.'logos/' ;
            $DirHandle = dir($dir);
            while ($DirEntry = $DirHandle->read() ){
                    if ($DirEntry != '.' AND $DirEntry !='..'){
                            $InCompanyDir[] = $DirEntry; //make an array of all files under company directory
                    }
            } //loop through list of files in the company directory
            if ($InCompanyDir !== FALSE) {
                    foreach($InCompanyDir as $logofilename) {
                            if (strncasecmp($logofilename,'labcode.png',8) === 0 AND
                                    is_readable($dir . $logofilename) AND
                                    is_file($dir . $logofilename)) {
                                    $logo = $logofilename;
                                    break;
                            }
                    }
                    if (!isset($logo)) {
                            foreach($InCompanyDir as $logofilename) {
                                    if (strncasecmp($logofilename,'labcode.jpg',8) === 0 AND
                                            is_readable($dir . $logofilename) AND
                                            is_file($dir . $logofilename)) {
                                            $logo = $logofilename;
                                            break;
                                    }
                            }
                    }
                    if (empty($logo)) {
                            return null;
                    } else {
                            return '../logos/'. $logo;
                    }
            } //end listing of files under company directory is not empty
    }

function getReportIssuedDate($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT MAX(updated_at) AS approval_date FROM test_results WHERE SampleID = ? AND StatusID = 4 AND approvedby IS NOT NULL AND approvedby <> 0");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $sampleID);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $result = $stmt->get_result();
    $record = $result ? ($result->fetch_assoc() ?: []) : [];
    $stmt->close();
    return $record['approval_date'] ?? null;
}

function getdatesamplereceived($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT  MIN(created_at) as last_created, 
            MAX(updated_at) as last_updated  FROM test_results WHERE SampleID = ?");
    
    
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    return $record['last_created'];
}


function getdatesamplecompleteed($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT  MIN(created_at) as last_created, 
            MAX(updated_at) as last_updated  FROM test_results WHERE SampleID = ?");
    
    
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    return $record['last_updated'];
}


function getdatesampleid($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT  SampleID,`rollback`  FROM test_results WHERE SampleID = ?");
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $sampleId = $record['SampleID'];
    $rollback = $record['rollback'] ?? null;
    if (!empty($rollback) && $rollback > 0) {
        return $sampleId . ' /' . $rollback . '';
    }
    return $sampleId;
}


function getlabenvironmenr($envID){
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM environmental_parameters sh WHERE sh.param_id = ?");
    $stmt->bind_param("i",$envID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
     
    return array('temp'=>$record['temperature'],'hum'=>$record['humidity']);
}

function needsPowerNotation($value){
    $value = trim((string)$value);
    if ($value === '' || !is_numeric($value)) {
        return false;
    }
    $digits = preg_replace('/[^0-9]/', '', $value);
    if (abs((float)$value) < 1) {
        $digits = (string)substr($digits, 1);
    }
    return strlen($digits) > 2;
}

function formatCertNumber($value){
    $value = str_replace(',', '', trim((string)$value));
    if (!is_numeric($value) || (float)$value == 0) {
        return $value;
    }
    $f = (float)$value;
    $sign = $f < 0 ? '-' : '';
    $abs = abs($f);
    $exp10 = floor(log10($abs));
    $mant = $abs / pow(10, $exp10 - 1);
    $mantInt = (int)round($mant);
    if ($mantInt >= 100) {
        $mantInt = 10;
        $exp = $exp10;
    } else {
        $exp = $exp10 - 1;
    }
    return $sign . $mantInt . ' × 10<sup>' . $exp . '</sup>';
}

function formatResultNumber($row, $value){
    if (isset($row['Category'], $row['ResultType'])
        && $row['Category'] === 'microbiological'
        && $row['ResultType'] === 'quantitativeField'
        && needsPowerNotation($value)) {
        return formatCertNumber($value);
    }
    return $value;
}

function accreditationSymbol($status){
    $normalizedStatus = strtolower(trim((string)$status));
    $normalizedStatus = str_replace(array(' ', '-'), '_', $normalizedStatus);

    switch ($normalizedStatus) {
        case 'accredited': return '';
        case 'contracted':
        case 'sub_contracted':
        case 'subcontracted': return '~';
        case 'not_accredited':
        default: return '*';
    }
}

function accreditationParameterName($sample){
    $name = htmlspecialchars((string)($sample['ParameterName'] ?? ''), ENT_QUOTES, 'UTF-8');
    $symbol = accreditationSymbol($sample['AccreditationStatus'] ?? 'not_accredited');
    return $name . '<sup style="font-size:7px;">' . $symbol . '</sup>';
}

function validateresults($row){
     $results = array('results' => '', 'standard' => '', 'grade' => '');
     if($row['MRL_Result']){
         $results['results'] = formatResultNumber($row, $row['MRL_Result']).' '.$row['MRLUnit'];
         $results['standard']= $row['MRL'];
         $results['grade']   = ($row['MRL']>$row['MRL_Result'])?'green':'red';
     }
     
     if($row['ResultStatus']){
         $results['results'] = $row['ResultStatus'];
         $results['standard'] = '-';
         $results['grade']   ='';
     }
     
     if($row['RangeResult']){
         $results['results']  = formatResultNumber($row, $row['RangeResult']).' '.$row['UnitOfMeasure'];
         $results['standard'] = $row['MinLimit'].' - '.$row['MaxLimit'];
         $results['grade']    = 'green';
         
         if($row['MinLimit']>$row['RangeResult']){
              $results['grade']    = 'blue';
         }
         
         if($row['RangeResult']>$row['MaxLimit']){
              $results['grade']    = 'red';
         }
        
     }
     return $results;
}
 
function TSDescription($sampleid){
  global $conn;
  $stmt = $conn->prepare("SELECT ts.Description
          FROM test_results tr
          JOIN teststandards ts ON ts.StandardID = tr.StandardID
          WHERE tr.SampleID = ?
          ORDER BY tr.resultsID DESC
          LIMIT 1");
  if (!$stmt) {
      return '';
  }
  $stmt->bind_param("s", $sampleid);
  if (!$stmt->execute()) {
      $stmt->close();
      return '';
  }
  $result = $stmt->get_result();
  $record = $result ? $result->fetch_assoc() : null;
  $stmt->close();
  return $record['Description'] ?? '';
}

function statementofconformity(){
  global $conn;
  
  $sql ="SELECT `confvalue` FROM config  where `confname`='Conformity'";
  $result = $conn->query($sql);
   $record = $result->fetch_assoc();
    
   return  $record['confvalue'];
}

function getsignature($sampleID){
    global $conn;
    $sampleID = trim((string)$sampleID);
    $defaultSignaturePath = realpath(__DIR__ . '/../images/icons8-no-image-100.png') ?: (__DIR__ . '/../images/icons8-no-image-100.png');
    $resolveSignaturePath = function ($rawPath) use ($defaultSignaturePath) {
        $rawPath = trim((string)$rawPath); $normalizedPath = str_replace(chr(92), '/', $rawPath); $candidates = [];
        if ($rawPath !== '') {
            if (preg_match('/^[A-Za-z]:\\//', $normalizedPath) || strpos($normalizedPath, '/') === 0) $candidates[] = $rawPath;
            else { $candidates[] = __DIR__ . '/../' . ltrim($normalizedPath, '/'); $candidates[] = __DIR__ . '/' . ltrim($normalizedPath, '/'); }
            $pathPart = parse_url($normalizedPath, PHP_URL_PATH); $fileName = basename((string)($pathPart !== false && $pathPart !== null ? $pathPart : $normalizedPath));
            if ($fileName !== '' && $fileName !== '.' && $fileName !== '..') $candidates[] = dirname(__DIR__) . '/images/' . $fileName;
            if (strpos(ltrim($normalizedPath, '/'), 'images/') === 0) $candidates[] = dirname(__DIR__) . '/' . ltrim($normalizedPath, '/');
        }
        $candidates[] = $defaultSignaturePath;
        foreach ($candidates as $candidate) if ($candidate && is_file($candidate) && is_readable($candidate)) { $resolvedPath = realpath($candidate); return str_replace(chr(92), '/', $resolvedPath !== false ? $resolvedPath : $candidate); }
        return str_replace(chr(92), '/', $defaultSignaturePath);
    };
    $approverID = ''; $reviewers = [];
    if ($sampleID !== '') {
        $stmt = $conn->prepare("SELECT resultsID, reviewedby, approvedby FROM test_results WHERE SampleID = ? ORDER BY resultsID DESC");
        if ($stmt) {
            $stmt->bind_param("s", $sampleID);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res) while ($row = $res->fetch_assoc()) {
                    $reviewID = trim((string)($row['reviewedby'] ?? '')); $approveID = trim((string)($row['approvedby'] ?? ''));
                    if ($approverID === '' && $approveID !== '' && $approveID !== '0') $approverID = $approveID;
                    if ($reviewID !== '' && $reviewID !== '0' && !isset($reviewers[$reviewID])) $reviewers[$reviewID] = true;
                }
            }
            $stmt->close();
        }
    }
    $signatoryIDs = [];
    foreach (array_keys($reviewers) as $reviewID) { if (count($signatoryIDs) >= 2) break; if ($reviewID !== $approverID) $signatoryIDs[] = ['user_id'=>$reviewID,'approval_role'=>'Reviewed by']; }
    if ($approverID !== '') $signatoryIDs[] = ['user_id'=>$approverID,'approval_role'=>'Approved by'];
    $signatories = []; $seen = [];
    foreach ($signatoryIDs as $entry) {
        $userID = trim((string)$entry['user_id']); if ($userID === '' || isset($seen[$userID])) continue; $seen[$userID] = true;
        $userStmt = $conn->prepare("SELECT user_id, full_name, department, signature_path FROM users WHERE user_id = ? LIMIT 1");
        if (!$userStmt) continue;
        $userStmt->bind_param("s", $userID); $userStmt->execute(); $userRes = $userStmt->get_result(); $user = $userRes ? ($userRes->fetch_assoc() ?: []) : []; $userStmt->close();
        if (!$user) continue;
        $department = trim((string)($user['department'] ?? ''));
        $signatories[] = ['user_id'=>$userID,'full_name'=>trim((string)($user['full_name'] ?? '')),'department'=>$department !== '' ? ucwords(str_replace(['_','-'],' ',$department)) : $entry['approval_role'],'approval_role'=>$entry['approval_role'],'signature_path'=>$resolveSignaturePath($user['signature_path'] ?? '')];
    }
    return ['signatories'=>$signatories];
}

function buildFooterHtml($wazito){
    $kenhasLogo = str_replace('\\', '/', realpath($_SESSION['KenhasFile']) ?: $_SESSION['KenhasFile']);
    $ilacLogo = str_replace('\\', '/', realpath($_SESSION['ILAC']) ?: $_SESSION['ILAC']);
    $neemaLogo = str_replace('\\', '/', realpath($_SESSION['NeemaFile']) ?: $_SESSION['NeemaFile']);

    $footerImageTag = function ($path, $maxWidth, $maxHeight) {
        $path = (string)$path;
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return '';
        }
        $dimensions = @getimagesize($path);
        if (!$dimensions || empty($dimensions[0]) || empty($dimensions[1])) {
            return '';
        }
        $scale = min($maxWidth / $dimensions[0], $maxHeight / $dimensions[1]);
        $width = round($dimensions[0] * $scale, 2);
        $height = round($dimensions[1] * $scale, 2);
        $safePath = htmlspecialchars(str_replace('\\', '/', $path), ENT_QUOTES, 'UTF-8');
        return '<img src="' . $safePath . '" width="' . $width . '" height="' . $height . '">';
    };
    $signatoryCells = '';
    $signatoryCount = count($wazito['signatories'] ?? []);
    $cellWidth = $signatoryCount > 0 ? (100 / $signatoryCount) : 100;
    foreach (($wazito['signatories'] ?? []) as $signatory) {
        $signatureImage = $footerImageTag($signatory['signature_path'] ?? '', 28, 18);
        $signatoryCells .= '<td style="width:' . $cellWidth . '%; text-align:center; vertical-align:middle;">'
            . $signatureImage . '<br><span style="font-size:9px; font-weight:bold;">'
            . htmlspecialchars($signatory['full_name'] ?? '', ENT_QUOTES, 'UTF-8')
            . '</span><br><span style="font-size:7px; color:#4a4a4a;">'
            . htmlspecialchars($signatory['department'] ?? '', ENT_QUOTES, 'UTF-8')
            . '</span></td>';
    }

    $kenhasImage = $footerImageTag(str_replace('\\', '/', realpath($_SESSION['KenhasFile']) ?: $_SESSION['KenhasFile']), 38, 10);
    $ilacImage = $footerImageTag(str_replace('\\', '/', realpath($_SESSION['ILAC']) ?: $_SESSION['ILAC']), 38, 10);
    $neemaImage = $footerImageTag(str_replace('\\', '/', realpath($_SESSION['NeemaFile']) ?: $_SESSION['NeemaFile']), 38, 10);
    $statement = nl2br(htmlspecialchars((string)statementofconformity(), ENT_QUOTES, 'UTF-8'));

    $html = '<div style="font-family:helvetica,sans-serif; color:#111; font-size:7px;">
      <table style="width:100%; border-collapse:collapse;" cellpadding="2" cellspacing="0">
        <tr>' . $signatoryCells . '</tr>
        <tr>
          <td colspan="3" style="border:1px solid #9eacb5; font-size:6px; text-align:center; vertical-align:middle;">
            <span style="font-size:7px; font-weight:bold;">Statement of Conformity</span><br>' . $statement . '
          </td>
        </tr>
        <tr>
          <td style="width:33.33%; border-left:1px solid #9eacb5; border-right:1px solid #9eacb5; text-align:center;">' . $kenhasImage . '</td>
          <td style="width:33.33%; border-right:1px solid #9eacb5; text-align:center;">' . $ilacImage . '</td>
          <td style="width:33.33%; border-right:1px solid #9eacb5; text-align:center;">' . $neemaImage . '</td>
        </tr>
        <tr>
          <td colspan="2" style="border-left:1px solid #9eacb5; border-bottom:1px solid #9eacb5; font-size:6px; text-align:left; color:#2f2f2f;">
            Lab Works East Africa Ltd is an ISO/IEC 17025:2017 Accredited Testing Laboratory (KENAS/TL/57)
          </td>
          <td style="border-right:1px solid #9eacb5; border-bottom:1px solid #9eacb5; font-size:6px; text-align:right; color:#2f2f2f;">
            Lab Works East Africa Ltd is NEMA gazetted no 47
          </td>
        </tr>

      </table></div>';

    return $html;
}

function TSamplePicture( $sampleID) {
    global $conn;
    // Default image path
    $defaultImage = '../uploads/icons8-no-image-100.png';
    
    // Query to get the sample file key
    $query = "SELECT SampleFileKey FROM sample_tests WHERE SampleID = ?";
    
    // Prepare the statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return json_encode(['success' => false, 'message' => 'Statement preparation failed: ' . $conn->error]);
    }
    
    // Bind parameters
    $stmt->bind_param("s", $sampleID);
    
    // Execute the query
    $stmt->execute();
    if ($stmt->error) {
        return json_encode(['success' => false, 'message' => 'Statement execution failed: ' . $stmt->error]);
    }
    
    // Fetch the results
    $result = $stmt->get_result();
    $sampleFileKey = $defaultImage; // Initialize with default image
    
    if ($row = $result->fetch_assoc()) {
        $fileKey = trim($row['SampleFileKey']);
        if (file_exists($fileKey)) {
            $sampleFileKey = $fileKey;
        }
    }
    
    // Close the statement
    $stmt->close();
    
    // Return the file path
    return $sampleFileKey;
}




