<?php
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

    global $conn;
    $sql = "SELECT * FROM company_master ORDER BY company_id DESC LIMIT 1";
    $result = $conn->query($sql);
    $record = $result ? ($result->fetch_assoc() ?: []) : [];
    $this->SetDrawColor(221, 221, 221); 
  
    $this->SetXY(70,5);
    // Set fixed height for the header and Y position
    $this->MultiCell(80, 5,"CERTIFICATE OF ANALYSIS (COA)", 0, 'L', 0, 1, '', '', true); // Limit characters
    $DefaultFont=8;
    $this->SetY(10);
    $this->SetFont('helvetica','B',$DefaultFont);
    
    $this->Image($_SESSION['LogoFile'],12,12,40,10);
   
    // Draw the outer border
    $this->Rect(10, 10, 190, 50, 'D'); // Rectangle for header
    $this->Rect(10, 60, 190, 50, 'D'); // Second rectangle
    // Left Top Section with text containment
    $this->SetXY(10, 25); 
   
    $fitFontSize = $this->fitText($record['company_name'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['company_name'] ?? '', 50), 0, 'L', 0, 1, '', '', true); // Limit characters
   
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['address'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['address'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['address1'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['address1'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['address2'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['address2'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['address3'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['address3'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['email'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['email'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $companyPhone = $record['telephone'] ?? ($record['telephne'] ?? '');
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($companyPhone, 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($companyPhone, 50), 0, 'L', 0, 1, '', '', true);

    // Right Top Section-------------------------------------------------------------------------------------------------------------
    
    $sampleID = trim((string)($_GET['id'] ?? ''));
    $record = [];
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
        // Keep ERP customer values separate from the sample/report query array.
        $customerData = $this->fetchErpCustomerDetails($customerCode);
    }
    
    // Customer labels are required in the generated sample report.
    $customerLabelRows = [
        ['Customer: ', $customerData['customer'] ?? ''],
        ['Company: ', $customerData['company'] ?? ''],
        ['Postal Code: ', $customerData['postcode'] ?? ''],
        ['City: ', $customerData['city'] ?? ''],
        ['Telephone: ', $customerData['phone'] ?? ''],
        ['Email: ', $customerData['email'] ?? '']
    ];

    $this->SetXY(105, 12);
    foreach ($customerLabelRows as $customerRow) {
        $label = $customerRow[0];
        $value = $this->truncateText((string)$customerRow[1], 50);
        $this->SetFont('helvetica', 'B', $DefaultFont);
        $this->MultiCell(95, 5, $label . $value, 0, 'L', 0, 1, '', '', true);
        $this->SetXY(105, $this->GetY());
    }

    $this->SetXY(106, 53);
    $this->SetFont('helvetica', 'B', 8);
    $pageNumber = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
    $this->Cell(0, 10, $pageNumber, 0, 0, 'L');

    // Draw a line under the top sections--------------------------------------------------------------------------------
    $this->Line(10, 60, 200, 60);
    $this->Line(105, 10, 105, 60);
    $this->SetFont('helvetica','',$DefaultFont);
    
    $sampleID = $_GET['id'] ?? '';
    $testreceived = getdatesamplereceived($sampleID);
    $testended = getdatesamplecompleteed($sampleID);
    $realsampleid = getdatesampleid($sampleID);
    
    // Bottom Section — labels and order match the COA report headers.
    $this->SetXY(10, 62);
    $this->MultiCell(95, 5,'Sample Batch No:'.($record['DocumentNo'] ?? ''), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Received:'.formatDate($record['Date'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampling Date:'.formatDate($record['SamplingDate'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Test Started:'.formatDate($testreceived,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Test Ended:'.formatDate($testended,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampled By:'.($record['SampledBy'] ?? ''), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampling Method:'.($record['SamplingMethod'] ?? ''), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sample Source:'.($record['ExternalSample'] ?? ''), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sample Name:'.($record['sample_name'] ?? ''), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sample Method:'.($record['sample_method'] ?? ''), 0, 'L', 0, 1, '', '', true);

    $this->SetXY(105, 62);
    $this->MultiCell(95, 5,'Sample ID:'.$realsampleid, 0, 'L', 0, 1, '', '', true);
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'B/No:'.($record['BatchNo'] ?? ''), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'B/size:'.($record['BatchSize'] ?? ''), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Date of Man:'.formatDate($record['ManufactureDate'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Date of Exp:'.formatDate($record['ExpDate'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Chilled Exp:'.formatDate($record['chilled_date_of_expiry'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Frozen Exp:'.formatDate($record['frozen_date_of_expiry'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'SKU:'.($record['SKU'] ?? ''), 0, 'L', 0, 1, '', '', true);
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Condition:'.($record['condition_of_sample'] ?? ''), 0, 'L', 0, 1, '', '', true);
 
   
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
        $this->SetFont('helvetica', '', $currentFontSize);

        while ($this->GetStringWidth($text) > $width && $currentFontSize > $minFontSize) {
            $currentFontSize--;
            $this->SetFont('helvetica', '', $currentFontSize);
        }

        return $currentFontSize; // Final font size that fits
    }

   public function Footer() {
  // Define the initial footer row position
    $this->SetFooterMargin(-30);
	    $this->SetY(-30);
	    $iconrow = $this->GetY();
    $this->SetFont('helvetica', 'I',5);
    $this->Image($_SESSION['KenhasFile'],15,$iconrow,10,10);
    $this->Image($_SESSION['ILAC'],105,$iconrow,10,10);
    $this->Image($_SESSION['NeemaFile'],180,$iconrow,10,12);
    //$this->Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);

    $this->SetY(-20);
    $labfooter="Lab Works East Africa Ltd is an ISO/IEC 17025:2017 Accredited Testing Laboratory (KENAS/TL/57)"      ;
    $this->MultiCell(95, 5,$labfooter, 0, 'L', 0, 1, '', '', true); // Limit characters
   
    $this->SetXY(160,-17);
    $labfooter="Lab Works East Africa Ltd is NEMA gazetted no 47"      ;
    $this->MultiCell(95, 5,$labfooter, 0, 'L', 0, 1, '', '', true); // Limit characters

    $this->SetXY(70,-14);
    $labfooter="This Laboratory report shall not be reproduced except in full, without a written consent of Lab Works East Africa Ltd"      ;
    $this->MultiCell(95, 5,$labfooter, 0, 'L', 0, 1, '', '', true); // Limit characters

 
  // Footer page number
  $this->SetY(-8); // Adjust the Y-position for footer
  $this->SetFont('helvetica', 'I', 8);
  $pageNumber = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
  $this->Cell(0, 10, $pageNumber, 0, 0, 'C');
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
    $pdf->SetMargins(10,110,10); // Adjust top margin to avoid overlap with header
    $pdf->AddPage();
    
    
   
    // Start creating HTML content
    $html = '<div style="width: 100%; font-family: Helvetica, sans-serif; font-size: 10px;margin-left: 15mm; margin-right: 15mm;">';
    $html .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                <thead>
    <tr style="background-color: #3498db; color: white;">
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 46%;">PARAMETER</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 12%;"><strong>Method</strong></th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 12%;">RESULTS</th>
        <th style="border: 1px solid #ddd; padding: 5px; text-align: left; width: 5%;">Low</th>
        <th style="border: 1px solid #ddd; padding: 5px; text-align: left; width: 5%;">Opt</th>
        <th style="border: 1px solid #ddd; padding: 5px; text-align: left; width: 5%;">High</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 15%;">LIMITS</th>
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
            <td style="border: 1px solid #ddd; padding: 8px; width: 12%;">' . $resultsHtml . '</td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 5%; ' .
                ($validatedresults['grade'] === 'blue' ? 'background-color: blue; color: white;' : '') . '"></td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 5%; ' .
                ($validatedresults['grade'] === 'green' ? 'background-color: green; color: white;' : '') . '"></td>
            <td style="border: 1px solid #ddd; padding: 5px; width: 5%; ' .
                ($validatedresults['grade'] === 'red' ? 'background-color: red; color: white;' : '') . '"></td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 15%;">' . $standardHtml . '</td>
          </tr>';
    }

    $html .= '</tbody></table>'; // Close the table
    $html .= '<div style="text-align: center; margin-top: 10px;">*******End of Test Results*******</div>';
    $html .= '<div style="text-align: left; margin-top: 6px; font-size: 7px;">* Accredited &nbsp;&nbsp; † Not Accredited &nbsp;&nbsp; ‡ Contracted</div>';
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
$wazito = getsignature();
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(10,$wazitorow);
$iconrow = $wazitorow;
$pdf->Image($wazito['authorisation']['signature_path'],15,$iconrow,10,10);
$pdf->SetXY(10,$wazitorow+12);
$pdf->MultiCell(95, 5,$wazito['authorisation']['full_name'], 0, 'L', 0, 1, '', '', true); // Limit characters

$pdf->SetXY(10,$wazitorow+17);
$pdf->MultiCell(95, 5,'Authorization', 0, 'L', 0, 1, '', '', true); // Limit characters
$pdf->SetXY(80,$wazitorow);
$pdf->Image($wazito['technician']['signature_path'],85,$iconrow,10,10);
$pdf->SetXY(80,$wazitorow+12);
$pdf->MultiCell(95, 5,$wazito['technician']['full_name'], 0, 'L', 0, 1, '', '', true); // Limit characters
$pdf->SetXY(80,$wazitorow+17);
$pdf->MultiCell(95, 5,'Laboratory Technician', 0, 'L', 0, 1, '', '', true); // Limit characters
$pdf->SetXY(155,$wazitorow);
$pdf->Image($wazito['technician2']['signature_path'],160,$iconrow,10,10);
$pdf->SetXY(155,$wazitorow+12);
$pdf->MultiCell(95, 5,$wazito['technician2']['full_name'], 0, 'L', 0, 1, '', '', true); // Limit characters
$pdf->SetXY(155,$wazitorow+17);
$pdf->MultiCell(95, 5,'Laboratory Technician', 0, 'L', 0, 1, '', '', true); // Limit characters

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
    switch (strtolower(trim((string)$status))) {
        case 'accredited': return '*';
        case 'contracted': return '‡';
        case 'not_accredited':
        default: return '†';
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

function getsignature(){
    global $conn;

    // getsampleid.php is in blockchain/, and signature uploads are stored in blockchain/images/.
    $defaultSignaturePath = realpath(__DIR__ . '/images/icons8-no-image-100.png')
        ?: (__DIR__ . '/images/icons8-no-image-100.png');

    $resolveSignaturePath = function ($rawPath) use ($defaultSignaturePath) {
        $rawPath = trim((string)$rawPath);
        $normalizedPath = str_replace(chr(92), '/', $rawPath);
        $candidates = [];

        if ($rawPath !== '') {
            // Keep a valid absolute path as the first choice.
            if (preg_match('/^[A-Za-z]:\\//', $normalizedPath) || strpos($normalizedPath, '/') === 0) {
                $candidates[] = $rawPath;
            } else {
                // Stored relative paths may be "filename.png" or "images/filename.png".
                $candidates[] = __DIR__ . '/' . ltrim($normalizedPath, '/');
                $candidates[] = __DIR__ . '/images/' . ltrim($normalizedPath, '/');
            }

            // Uploads are saved under blockchain/images/. The database commonly stores
            // only the filename, or an old path from another server.
            $pathPart = parse_url($normalizedPath, PHP_URL_PATH);
            $fileName = basename((string)($pathPart !== false && $pathPart !== null ? $pathPart : $normalizedPath));
            if ($fileName !== '' && $fileName !== '.' && $fileName !== '..') {
                $candidates[] = __DIR__ . '/images/' . $fileName;
            }

            if (strpos(ltrim($normalizedPath, '/'), 'images/') === 0) {
                $candidates[] = __DIR__ . '/' . ltrim($normalizedPath, '/');
            }
        }

        $candidates[] = $defaultSignaturePath;

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate) && is_readable($candidate)) {
                $resolvedPath = realpath($candidate);
                return str_replace(chr(92), '/', $resolvedPath !== false ? $resolvedPath : $candidate);
            }
        }

        return str_replace(chr(92), '/', $defaultSignaturePath);
    };

    $companyRow = [];
    $companyResult = $conn->query("SELECT authorisation, technician, technician2 FROM company_master ORDER BY company_id DESC LIMIT 1");
    if ($companyResult) {
        $companyRow = $companyResult->fetch_assoc() ?: [];
    }

    $fetchUser = function ($userId) use ($conn, $resolveSignaturePath) {
        $defaultName = 'Not Assigned';
        $userId = trim((string)$userId);
        $stmt = $conn->prepare("SELECT full_name, signature_path FROM users WHERE user_id = ? LIMIT 1");
        if (!$stmt) {
            return ['full_name' => $defaultName, 'signature_path' => $resolveSignaturePath('')];
        }

        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $userRow = $res ? ($res->fetch_assoc() ?: []) : [];
        $stmt->close();

        return [
            'full_name' => !empty($userRow['full_name']) ? $userRow['full_name'] : $defaultName,
            'signature_path' => $resolveSignaturePath($userRow['signature_path'] ?? '')
        ];
    };

    return [
        'authorisation' => $fetchUser($companyRow['authorisation'] ?? ''),
        'technician' => $fetchUser($companyRow['technician'] ?? ''),
        'technician2' => $fetchUser($companyRow['technician2'] ?? '')
    ];
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
