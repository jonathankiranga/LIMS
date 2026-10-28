<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BlockchainLIMS');
    session_start();
}
require_once('vendor/autoload.php'); // Adjust the path as necessary
require_once 'functions/functions.php';
$config      = include('include/config.php'); // Load the config file
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
    $PathPrefix='';
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
 

class CustomPDF extends TCPDF {
    protected $angle = 0;
    
    function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;
        if ($this->angle != 0) $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.3F %.3F %.3F %.3F %.3F %.3F cm 1 0 0 1 %.3F %.3F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }
    
    public function StopTransform() {
        parent::StopTransform();
        if ($this->angle != 0) {
            // Rotate() opens a second graphics-state level in addition to StartTransform().
            // Close that outer level too so footer images inherit the normal page orientation.
            $this->_out('Q');
            $this->angle = 0;
        }
    }

    function _endpage() {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
    
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
            // Keep the bottom wave inside the printable page area.
            $bottomWaveAmplitude = 1.0;
            $bottomY1 = $borderBottom - $offset + $bottomWaveAmplitude * sin($angle1);
            $bottomY2 = $borderBottom - $offset + $bottomWaveAmplitude * sin($angle2);

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

    global $conn;
    $sql = "SELECT * FROM company_master ORDER BY company_id DESC LIMIT 1";
    $result = $conn->query($sql);
    $record = $result ? ($result->fetch_assoc() ?: []) : [];

    // Keep the existing company query, ERP customer lookup and sample fields.
    $companyRecord = $record;
    $DefaultFont = 7;
    $sampleID = trim((string)($_GET['id'] ?? ''));
    $record = [];
    $customerData = [];
    if ($sampleID !== '') {
        $stmt = $conn->prepare("
            SELECT sh.*, st.*, tr.SampleID AS ResultSampleID,
                   tr.TestID AS ResultTestID, tr.StandardID AS ResultStandardID
            FROM sample_header sh
            JOIN test_results tr ON sh.HeaderID = tr.HeaderID
            LEFT JOIN sample_tests st ON st.TestID = tr.TestID
            WHERE tr.SampleID = ?
            ORDER BY tr.resultsID DESC
            LIMIT 1
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

    require_once 'functions/BarCodeClass.inc';
    $QRcode = new makebarcode();
    $qrcodelink = ($sampleID !== '') ? $QRcode->getQr($sampleID) : '';

    // Letterhead: logo at left, title centred, QR at far right; no lab contact text here.
    if (!empty($_SESSION['LogoFile'])) $this->Image($_SESSION['LogoFile'], 10, 9, 32, 12);
    $this->SetFont('montserrat', 'B', 10);
    $this->SetXY(55, 12);
    $this->Cell(100, 5, 'CERTIFICATE OF ANALYSIS (COA)', 0, 0, 'C');
    if ($qrcodelink !== '') $this->Image($qrcodelink, 185, 9, 15, 15);

    $this->SetLineStyle(['width' => 0.25, 'color' => [190, 211, 232]]);
    $this->SetDrawColor(190, 211, 232);
    $this->SetTextColor(35, 45, 55);

    // Frame 1: Company Details.
    $this->Rect(10, 25, 95, 24, 'D');
    $this->SetFont('montserrat', 'B', 7);
    $this->SetXY(12, 25.5);
    // Company details shown without a heading.
    $companyPhone = $companyRecord['telephone'] ?? ($companyRecord['telephne'] ?? '');
    $companyRows = [$companyRecord['company_name'] ?? '', $companyRecord['address'] ?? '', $companyRecord['address1'] ?? '', $companyRecord['address2'] ?? '', $companyRecord['address3'] ?? '', $companyRecord['email'] ?? '', $companyPhone];
    $this->SetFont('montserrat', '', 6.4);
    $cy = 26.5;
    foreach ($companyRows as $companyLine) {
        if (trim((string)$companyLine) !== '') {
            $this->SetXY(12, $cy);
            $this->Cell(91, 3.0, $this->truncateText((string)$companyLine, 80), 0, 0, 'L');
        }
        $cy += 3.0;
    }

    // Frame 2: Customer Details from the existing ERP master lookup.
    $this->Rect(105, 25, 95, 24, 'D');
    $this->SetFont('montserrat', 'B', 7);
    $this->SetXY(107, 25.5);
    // Customer details shown without a heading.
    $customerRows = [['Customer: ', $customerData['customer'] ?? ''], ['Company: ', $customerData['company'] ?? ''], ['Postal Code: ', $customerData['postcode'] ?? ''], ['City: ', $customerData['city'] ?? ''], ['Telephone: ', $customerData['phone'] ?? ''], ['Email: ', $customerData['email'] ?? '']];
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
            foreach (($erpData['customers'] ?? []) as $customer) {
                $erpCustomerCode = trim((string)($customer['itemcode'] ?? $customer['code'] ?? ''));
                if ($erpCustomerCode !== '' && strcasecmp($erpCustomerCode, $customerCode) === 0) {
                    $customerCache[$customerCode] = [
                        'customer' => trim((string)($customer['customer'] ?? $customer['name'] ?? '')),
                        'company' => trim((string)($customer['company'] ?? '')),
                        'postcode' => trim((string)($customer['postcode'] ?? '')),
                        'city' => trim((string)($customer['city'] ?? '')),
                        'phone' => trim((string)($customer['phone'] ?? '')),
                        'email' => trim((string)($customer['email'] ?? ''))
                    ];
                    return $customerCache[$customerCode];
                }
            }
        }

        $customerCache[$customerCode] = $emptyDetails;
        return $customerCache[$customerCode];
    }

    private function truncateText($text, $maxLength) {
        return mb_strimwidth((string)$text, 0, $maxLength, '...');
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

    private function drawFooterLogo($path, $x, $y, $maxWidth = 15, $maxHeight = 10) {
        if (empty($path) || !is_file($path) || !is_readable($path)) {
            return;
        }
        $dimensions = @getimagesize($path);
        if (!$dimensions || empty($dimensions[0]) || empty($dimensions[1])) {
            return;
        }
        $scale = min($maxWidth / $dimensions[0], $maxHeight / $dimensions[1]);
        $width = $dimensions[0] * $scale;
        $height = $dimensions[1] * $scale;
        $this->Image($path, $x, $y + (($maxHeight - $height) / 2), $width, $height);
    }

    public function Footer() {
        global $conn;
        static $printedBy = null;
        if ($printedBy === null) {
            $printedBy = 'Not available';
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
        $iconrow = $this->getPageHeight() - 35;
        $this->SetFont('montserrat', 'I', 5);
        $this->drawFooterLogo($_SESSION['KenhasFile'] ?? '', 15, $iconrow, 15, 10);
        $this->drawFooterLogo($_SESSION['ILAC'] ?? '', 100, $iconrow, 15, 10);
        $this->drawFooterLogo($_SESSION['NeemaFile'] ?? '', 180, $iconrow, 15, 10);

        $this->SetXY(10, -22);
        $this->MultiCell(105, 4, 'Lab Works East Africa Ltd is an ISO/IEC 17025:2017 Accredited Testing Laboratory (KENAS/TL/57)', 0, 'L', 0, 1, '', '', true);
        $this->SetXY(125, -22);
        $this->MultiCell(75, 4, 'Lab Works East Africa Ltd is NEMA gazetted no 47', 0, 'R', 0, 1, '', '', true);
        $this->SetXY(25, -14);
        $this->MultiCell(160, 4, 'This Laboratory report shall not be reproduced except in full, without a written consent of Lab Works East Africa Ltd', 0, 'C', 0, 1, '', '', true);

        $this->SetXY(10, -8);
        $this->SetFont('montserrat', '', 7);
        $this->Cell(80, 4, 'Printed By: ' . $printedBy, 0, 0, 'L');
        $this->SetXY(80, -8);
        $this->SetFont('montserrat', 'I', 8);
        $pageNumber = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
        $this->Cell(120, 4, $pageNumber, 0, 0, 'C');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && $_GET['id'] !== '') {
 
    $sampleID = $_GET['id'];
    $SampletypeNotes = TSDescription($sampleID);
    $samples = [];
 
    $stmt = $conn->prepare("
        SELECT tr.*, tp.*, ts.*
        FROM test_results tr
        LEFT JOIN testparameters tp
            ON tp.ParameterID = tr.ParameterID
            AND tp.StandardID = tr.StandardID
        LEFT JOIN teststandards ts
            ON ts.StandardID = tr.StandardID
        WHERE tr.SampleID = ?
          AND tr.StatusID = 4
        ORDER BY tr.resultsID ASC
    ");
    if ($stmt) {
        $stmt->bind_param("s", $sampleID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($result && ($row = $result->fetch_assoc())) {
            $samples[] = $row;
        }
        $stmt->close();
    }
  
    // Initialize custom PDF
    $pdf = new CustomPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Jonathan');
    $pdf->SetTitle('LIMS Report');
    $pdf->SetMargins(10,98,10); // Adjust top margin to avoid overlap with header
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

    // Use the same result formatter, accreditation markers and grade cells as the COA reports.
    foreach ($samples as $sample) {
        $validatedresults = validateresults($sample);
        $resultsHtml = (strpos($validatedresults['results'], '<sup>') !== false)
            ? $validatedresults['results']
            : htmlentities($validatedresults['results'], ENT_QUOTES, 'UTF-8');
        $standardHtml = htmlentities($validatedresults['standard'], ENT_QUOTES, 'UTF-8');
        $methodHtml = htmlspecialchars((string)($sample['Method'] ?? ''), ENT_QUOTES, 'UTF-8');

        $html .= '<tr>
            <td style="border: 1px solid #ddd; padding: 8px; width: 46%;">' . accreditationParameterName($sample) . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 12%;">' . $methodHtml . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 10%;">' . htmlspecialchars((string)($sample['UnitOfMeasure'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 12%;">' . $resultsHtml . '</td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 4%; ' .
                ($validatedresults['grade'] === 'blue' ? 'background-color: blue; color: white;' : '') . '"></td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 4%; ' .
                ($validatedresults['grade'] === 'green' ? 'background-color: green; color: white;' : '') . '"></td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 4%; ' .
                ($validatedresults['grade'] === 'red' ? 'background-color: red; color: white;' : '') . '"></td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 16%;">' . $standardHtml . '</td>
          </tr>';
    }

    $html .= '</tbody></table>'; // Close the table
    $html .= '<div style="text-align: center; margin-top: 10px;">*******End of Test Results*******</div>';
    $html .= '<div style="text-align: left; margin-top: 6px; font-size: 7px;">* Not Accredited &nbsp;&nbsp; ~ Subcontracted</div>';
    $html .= '<div style="text-align: left; margin-top: 10px;"><u>NOTES</u></div>'; // Add footer
    
    $lines = explode("\n", $SampletypeNotes);
    foreach ($lines as $line) {
      $html .= '<div style="text-align: left; font-family: Helvetica, sans-serif; font-size: 8px; line-height: 0.5; margin: 0; padding: 0;color: blue;">'.$line.'</div>'; // Add footer
    }
    
   $html .= '<div style="text-align: left; margin-top: 10px;"><u>INTERPRETATION OF ANALYSIS RESULTS</u></div>'; // Add footer
   $html .= '<div style="text-align: left; font-family: Helvetica, sans-serif; font-size: 8px; line-height: 1.0; margin: 0; padding: 0;color: grey;">' ; 
 
    foreach ($samples as $sample) {
        $validatedresults = validateresults($sample);
        $html .=' Test Results for '. $sample['ParameterName'].' are <u>'.
       ($validatedresults['grade'] == '' ? $validatedresults['results'] : '')  . 
       ($validatedresults['grade'] == 'blue' ? 'Below the minimum specified level' : '')  . 
       ($validatedresults['grade'] == 'green' ? 'Optimum' : '')  . 
       ($validatedresults['grade'] == 'red' ? ' above the maximum specified level' : '')  .' </u> ' ;
        
   }
     $html .='</div>';
    // Write the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');
$wazitorow = $pdf->GetY();
//signature
$wazito = getsignature($sampleID);
$pdf->SetFont('montserrat','',8);
$pdf->SetTextColor(0, 0, 0);
$signatories = $wazito['signatories'] ?? [];
$signatoryCount = count($signatories);
$pageMargins = $pdf->getMargins();
$pageWidth = $pdf->getPageWidth() - $pageMargins['left'] - $pageMargins['right'];
$cellWidth = $signatoryCount > 0 ? $pageWidth / $signatoryCount : $pageWidth;
foreach ($signatories as $index => $signatory) {
    $x = $pageMargins['left'] + ($index * $cellWidth);
    $pdf->SetXY($x, $wazitorow);
    $signaturePath = $signatory['signature_path'] ?? '';
    if ($signaturePath !== '' && is_file($signaturePath) && is_readable($signaturePath)) {
        $pdf->Image($signaturePath, $x + 2, $wazitorow, 12, 9);
    }
    $pdf->SetXY($x, $wazitorow + 10);
    $pdf->MultiCell($cellWidth - 3, 4, $signatory['full_name'] ?? '', 0, 'L', 0, 1, '', '', true);
    $pdf->SetXY($x, $wazitorow + 14);
    $pdf->MultiCell($cellWidth - 3, 4, $signatory['department'] ?? '', 0, 'L', 0, 1, '', '', true);
}
$pdf->SetY($wazitorow + ($signatoryCount > 0 ? 19 : 2));
$samerow = $pdf->GetY();
$pdf->SetXY(10,$samerow+5);
$html = '<div style="text-align: center; font-family: Helvetica, sans-serif; font-size: 5px; line-height: 1.0; margin: 0; padding: 0;color: blue;">Statement of Conformity</div>'; // Add footer

$statementofconformity = statementofconformity();
$lines = explode("\n",$statementofconformity);
foreach ($lines as $line) {
  $html .= '<div style="text-align: left; font-family: Helvetica, sans-serif; font-size: 5px; line-height: 1.0; margin: 0; padding: 0;color: blue;">'.$line.'</div>'; // Add footer
}
$pdf->writeHTML($html, true, false, true, false, '');
   // Save the PDF to the server
$filePath = __DIR__ . '/QRCODELOG/dynamic_' . $_GET['id'] . '.pdf'; // Save as dynamic_123.pdf for each linkid

$directory = 'QRCODELOG';
if (!file_exists($directory)) {
    mkdir($directory, 0777, true); // Create directory with permissions
}
$pdf->Output($filePath,'F'); // Output the PDF to the specified file path

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
                            return 'logos/'. $logo;
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
                            return 'logos/'. $logo;
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
                            return 'logos/'. $logo;
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
                            return 'logos/'. $logo;
                    }
            } //end listing of files under company directory is not empty
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
                            return 'logos/'. $logo;
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
    $stmt = $conn->prepare("SELECT MIN(created_at) AS last_created FROM test_results WHERE SampleID = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result ? ($result->fetch_assoc() ?: []) : [];
    $stmt->close();
    return $record['last_created'] ?? null;
}


function getdatesamplecompleteed($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT MAX(updated_at) AS last_updated FROM test_results WHERE SampleID = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result ? ($result->fetch_assoc() ?: []) : [];
    $stmt->close();
    return $record['last_updated'] ?? null;
}


function getdatesampleid($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT SampleID, `rollback` FROM test_results WHERE SampleID = ? LIMIT 1");
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param("s", $sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result ? ($result->fetch_assoc() ?: []) : [];
    $stmt->close();
    $realSampleId = (string)($record['SampleID'] ?? '');
    $rollback = $record['rollback'] ?? null;
    if ($realSampleId !== '' && !empty($rollback) && (int)$rollback > 0) {
        return $realSampleId . ' /' . (int)$rollback;
    }
    return $realSampleId;
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
    $number = (float)$value;
    $sign = $number < 0 ? '-' : '';
    $absolute = abs($number);
    $exponent10 = (int)floor(log10($absolute));
    $mantissa = $absolute / pow(10, $exponent10 - 1);
    $mantissaInt = (int)round($mantissa);
    if ($mantissaInt >= 100) {
        $mantissaInt = 10;
        $exponent = $exponent10;
    } else {
        $exponent = $exponent10 - 1;
    }
    return $sign . $mantissaInt . ' × 10<sup>' . $exponent . '</sup>';
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
    $results = ['results' => '', 'standard' => '', 'grade' => ''];

    if (!empty($row['MRL_Result'])) {
        $results['results'] = formatResultNumber($row, $row['MRL_Result']) . ' ' . ($row['MRLUnit'] ?? '');
        $results['standard'] = (string)($row['MRL'] ?? '');
        $results['grade'] = ((float)($row['MRL'] ?? 0) > (float)$row['MRL_Result']) ? 'green' : 'red';
    }

    if (!empty($row['ResultStatus'])) {
        $results['results'] = (string)$row['ResultStatus'];
        $results['standard'] = '-';
        $results['grade'] = '';
    }

    if (!empty($row['RangeResult'])) {
        $results['results'] = formatResultNumber($row, $row['RangeResult']) . ' ' . ($row['UnitOfMeasure'] ?? '');
        $results['standard'] = (string)($row['MinLimit'] ?? '') . ' - ' . (string)($row['MaxLimit'] ?? '');
        $results['grade'] = 'green';
        if ((float)($row['MinLimit'] ?? 0) > (float)$row['RangeResult']) {
            $results['grade'] = 'blue';
        }
        if ((float)$row['RangeResult'] > (float)($row['MaxLimit'] ?? 0)) {
            $results['grade'] = 'red';
        }
    }

    return $results;
}
 
function TSDescription($sampleid){
    global $conn;
    $stmt = $conn->prepare("
        SELECT ts.Description
        FROM test_results tr
        JOIN teststandards ts ON ts.StandardID = tr.StandardID
        WHERE tr.SampleID = ?
        ORDER BY tr.resultsID DESC
        LIMIT 1
    ");
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
    return htmlspecialchars((string)($record['Description'] ?? ''), ENT_QUOTES, 'UTF-8');
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
    $defaultSignaturePath = realpath(__DIR__ . '/images/icons8-no-image-100.png') ?: (__DIR__ . '/images/icons8-no-image-100.png');
    $resolveSignaturePath = function ($rawPath) use ($defaultSignaturePath) {
        $rawPath = trim((string)$rawPath); $normalizedPath = str_replace(chr(92), '/', $rawPath); $candidates = [];
        if ($rawPath !== '') {
            if (preg_match('/^[A-Za-z]:\\//', $normalizedPath) || strpos($normalizedPath, '/') === 0) $candidates[] = $rawPath;
            else { $candidates[] = __DIR__ . '/' . ltrim($normalizedPath, '/'); $candidates[] = __DIR__ . '/images/' . ltrim($normalizedPath, '/'); }
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
?>
<canvas id="pdf-canvas"></canvas>
<div>
    <button id="prevPage">Previous</button>
    <span id="currentPage">Page <span id="currentPageNum">1</span> of <span id="totalPages">0</span></span>
    <button id="nextPage">Next</button>
</div>
<script src="js/2.10.377/pdf.min.js"></script>
<script>
const dynamicURL = '<?php echo "QRCODELOG/dynamic_" . $_GET['id'] . ".pdf"; ?>';

const pdfjsLib = window['pdfjs-dist/build/pdf'];
pdfjsLib.GlobalWorkerOptions.workerSrc = 'js/2.10.377/pdf.worker.min.js';

let pdfDoc = null; // Holds the PDF document
let currentPage = 1; // Current page number
let totalPages = 0; // Total number of pages
const scale = 1.5; // Scale for the viewport

// References to HTML elements
const canvas = document.getElementById('pdf-canvas');
const context = canvas.getContext('2d');
const prevButton = document.getElementById('prevPage');
const nextButton = document.getElementById('nextPage');
const currentPageNum = document.getElementById('currentPageNum');
const totalPagesSpan = document.getElementById('totalPages');

// Function to render a page
function renderPage(pageNumber) {
    pdfDoc.getPage(pageNumber).then(function (page) {
        const viewport = page.getViewport({ scale });
        canvas.width = viewport.width;
        canvas.height = viewport.height;

        const renderContext = {
            canvasContext: context,
            viewport: viewport,
        };

        page.render(renderContext).promise.then(() => {
            currentPageNum.textContent = pageNumber; // Update current page number display
        });
    });
}

// Function to load the PDF
function loadPDF(url) {
    pdfjsLib.getDocument(url).promise.then(function (pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages; // Get total number of pages
        totalPagesSpan.textContent = totalPages; // Update total pages display
        renderPage(currentPage); // Render the first page
    }).catch(function (error) {
        console.error('Error loading PDF:', error);
    });
}

// Event listeners for navigation buttons
prevButton.addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
    }
});

nextButton.addEventListener('click', () => {
    if (currentPage < totalPages) {
        currentPage++;
        renderPage(currentPage);
    }
});
 
// Load the dynamic PDF
loadPDF(dynamicURL);
</script>
