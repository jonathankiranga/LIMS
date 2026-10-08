<?php
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
    global $conn,$QRcode,$includeCompanyWatermark;
    $sql = "SELECT * FROM company_master ORDER BY company_id DESC LIMIT 1";
    $result = $conn->query($sql);
    $record = $result->fetch_assoc();

    if ($includeCompanyWatermark && !empty($record['company_name'])) {
        $watermarkSeries = '';
        $wmStmt = $conn->prepare("SELECT sh.DocumentNo FROM sample_header sh JOIN sample_tests st ON sh.HeaderID = st.HeaderID WHERE st.TestID = ?");
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

    // Microtext strip (unconditional; mirrors the HTML preview)
    $this->SetFont('helvetica', 'I', 4.5);
    $this->SetTextColor(169, 178, 191);
    $microPart = 'LAB WORKS EAST AFRICA • ISO/IEC 17025:2017 • CERTIFIED TESTING LABORATORY • ';
    $micro = '';
    while ($micro !== $microPart && $this->GetStringWidth($micro . $microPart) <= 190) {
        $micro .= $microPart;
    }
    $this->SetXY(10, 2);
    $this->Cell(0, 3, $micro, 0, 0, 'L');
    $this->SetTextColor(0, 0, 0);
    $this->SetDrawColor(221, 221, 221); 
  
	    $this->SetXY(70,5);
	    // Set fixed height for the header and Y position
	    $this->SetFont('helvetica', 'B', 10);
	    $this->MultiCell(80, 5,"CERTIFICATE OF ANALYSIS (COA)", 0, 'L', 0, 1, '', '', true); // Limit characters
	    $DefaultFont=8;
    $this->SetY(10);
    $this->SetFont('helvetica','B',$DefaultFont);
    
    $this->Image($_SESSION['LogoFile'],12,12,40,10);
    $this->Image($_SESSION['LABCODE'],88,10,17,5);

    // Draw the outer border
    $this->Rect(10, 10, 190, 50, 'D'); // Rectangle for header
    $this->Rect(10, 60, 190, 50, 'D'); // Second rectangle
    // Left Top Section with text containment
    $this->SetXY(10, 25); 
   
    $fitFontSize = $this->fitText($record['company_name'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['company_name'], 50), 0, 'L', 0, 1, '', '', true); // Limit characters
   
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['address'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['address'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['address1'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['address1'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['address2'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['address2'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['address3'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['address3'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['email'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['email'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(10, $this->GetY());
    $fitFontSize = $this->fitText($record['telephone'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['telephone'], 50), 0, 'L', 0, 1, '', '', true);

    // Right Top Section-------------------------------------------------------------------------------------------------------------
    $sampleID = $_POST['sampleID'];
    $qrcodelink = $QRcode->getQr($sampleID);

    $stmt = $conn->prepare("
    SELECT 
        sh.*, 
        dr.*, 
        st.*
    FROM sample_header sh 
    JOIN debtors dr ON sh.CustomerID = dr.itemcode 
    JOIN sample_tests st ON sh.HeaderID = st.HeaderID
     WHERE st.TestID = ? ");
   
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    
    $this->Image($qrcodelink,180,10,20,20);
   
    $this->SetXY(105, 12);
    $fitFontSize = $this->fitText($record['customer'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['customer'], 50), 0, 'L', 0, 1, '', '', true); // Limit characters
   
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['company'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['company'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['postcode'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['postcode'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['city'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['city'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['phone'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['phone'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['email'], 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['email'], 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(106, 53);
    $this->SetFont('helvetica', 'B', 8);
    $pageNumber = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
    $this->Cell(0, 10, $pageNumber, 0, 0, 'L');

    // Draw a line under the top sections--------------------------------------------------------------------------------
    $this->Line(10, 60, 200, 60);
    $this->Line(105, 10, 105, 60);
    $this->SetFont('helvetica','',$DefaultFont);

    $testreceived = getdatesamplereceived($sampleID);
    $testended = getdatesamplecompleteed($sampleID);
    $realsampleid = getdatesampleid($sampleID);
 

    // Bottom Section
    $this->SetXY(10, 62);
    $this->MultiCell(95, 5,'Sample Batch No:'.$record['DocumentNo'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Received:'.formatDate($record['Date'],'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampling Date:'.formatDate($record['SamplingDate'],'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Test Started:'.formatDate($testreceived,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Test Ended:'.formatDate($testended,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampled By:'. $record['SampledBy'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampling Method:'. $record['SamplingMethod'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sample Source:'. $record['ExternalSample'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sample Name:'. $record['sample_name'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sample Method:'. $record['sample_method'], 0, 'L', 0, 1, '', '', true); // Limit characters
    
     $this->SetXY(105, 62);
    $this->MultiCell(95, 5,'Sample ID:'.$realsampleid, 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'B/No:'.$record['BatchNo'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'B/size:'.$record['BatchSize'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Date of Man:'.formatDate($record['ManufactureDate'],'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Date of Exp:'.formatDate($record['ExpDate'],'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Chilled Exp:'.formatDate($record['chilled_date_of_expiry'],'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Frozen Exp:'.formatDate($record['frozen_date_of_expiry'],'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'SKU:'.$record['SKU'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'SKU:'.$record['SKU'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'External Sample ID:'. $record['ExternalSample'], 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Condition:'. $record['condition_of_sample'], 0, 'L', 0, 1, '', '', true); // Limit characters
 
   
	}

    private function drawCompanyWatermark($watermarkText) {
        $watermarkText = trim((string)$watermarkText);
        if ($watermarkText === '') {
            return;
        }

        $centerX = $this->getPageWidth() / 2;
        $centerY = $this->getPageHeight() / 2;
        $fontSize = 30;

        $this->SetFont('helvetica', 'B', $fontSize);
        while ($this->GetStringWidth($watermarkText) > 150 && $fontSize > 18) {
            $fontSize--;
            $this->SetFont('helvetica', 'B', $fontSize);
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

    /**
     * Helper function to truncate text to a specific length.
     */
    private function truncateText($text, $maxLength) {
        return mb_strimwidth($text, 0, $maxLength, '...');
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

    // Custom header
    // Custom footer
    
public function Footer() {
  global $reportFooterHtml;
  $this->SetY(-78);
  if (!empty($reportFooterHtml)) {
      $this->writeHTMLCell(190, 68, 10, '', $reportFooterHtml, 0, 1, 0, true, 'L', true);
  }
  $this->SetY(-8);
  $this->SetFont('helvetica', 'I', 8);
  $pageNumber = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
  $this->Cell(0, 10, $pageNumber, 0, 0, 'C');
}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_REQUEST['preview'])) {
    $sampleID = $_REQUEST['sampleID'];

    $SampletypeNotes = TSDescription($sampleID);
 
    $stmt = $conn->prepare("SELECT tr.*,tp.*,ts.* FROM test_results tr 
    join testparameters tp on tp.ParameterID=tr.ParameterID and tp.StandardID=tr.StandardID
    join teststandards ts on ts.StandardID=tr.StandardID 
    WHERE tr.`TestID` = ?   and tr.`StatusID`=4 ");
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
     while ($row = $result->fetch_assoc()) {
            $samples[] = $row;
        }
  
    // Initialize custom PDF
    $pdf = new CustomPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetAuthor('Jonathan');
    $pdf->SetTitle('LIMS Report');
    $pdf->SetMargins(10,110,10); // Adjust top margin to avoid overlap with header
    $pdf->AddPage();

    // Start creating HTML content
    $html = '<div style="width: 100%; font-family: Arial, sans-serif; font-size: 10px;margin-left: 15mm; margin-right: 15mm;">';
    $html .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                <thead>
    <tr style="background-color: #3498db; color: white;">
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 55%;">PARAMETER</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 15%;">METHOD</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 15%;">RESULTS</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 15%;">STD LIMITS</th>
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
            <td style="border: 1px solid #ddd; padding: 17px; width: 55%;">' . accreditationParameterName($sample) . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 15%;">' . $sample['Method'] . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 15%;">' . $resutlts . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 15%;">' . $standard . '</td>
          </tr>';
   }

    $html .= '</tbody></table>'; // Close the table
    $html .= '<div style="text-align: center; margin-top: 10px;">*******End of Test Results*******</div>';
    $html .= '<div style="text-align: left; margin-top: 6px; font-size: 7px;">* Accredited &nbsp;&nbsp; † Not Accredited &nbsp;&nbsp; ‡ Contracted</div>'; // Add footer
    $html .= '<div style="text-align: left; margin-top: 10px;"><u>NOTES</u></div>'; // Add footer
    
    $lines = explode("\n", $SampletypeNotes);
    foreach ($lines as $line) {
      $html .= '<div style="text-align: left; font-family: Arial, sans-serif; font-size: 8px; line-height: 0.5; margin: 0; padding: 0;color: blue;">'.$line.'</div>'; // Add footer
    }
    
   $html .= '<div style="text-align: left; margin-top: 10px;"><u>INTERPRETATION OF ANALYSIS RESULTS</u></div>'; // Add footer
   $html .= '<div style="text-align: left; font-family: Arial, sans-serif; font-size: 8px; line-height: 1.0; margin: 0; padding: 0;color: grey;">' ; 
 
    foreach ($samples as $sample) {
        $validatedresults = validateresults($sample);
        $html .=' Test Results for '. accreditationParameterName($sample) .' are <u>'.
       ($validatedresults['grade'] == '' ? $validatedresults['results'] : '')  . 
       ($validatedresults['grade'] == 'blue' ? 'Below the minimum specified level' : '')  . 
       ($validatedresults['grade'] == 'green' ? 'Optimum' : '')  . 
       ($validatedresults['grade'] == 'red' ? ' above the maximum specified level' : '')  .' </u> ' ;
        
   }
     $html .='</div>';
 $reportFooterHtml = buildFooterHtml(getsignature());

    // Write the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

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


function getdatesamplereceived($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT  MIN(created_at) as last_created, 
            MAX(updated_at) as last_updated  FROM test_results WHERE TestID = ?");
    
    
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    return $record['last_created'];
}


function getdatesamplecompleteed($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT  MIN(created_at) as last_created, 
            MAX(updated_at) as last_updated  FROM test_results WHERE TestID = ?");
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    return $record['last_updated'];
}


function getdatesampleid($sampleID){
    global $conn;
    $stmt = $conn->prepare("SELECT  SampleID,`rollback`  FROM test_results WHERE TestID = ?");
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
     $results=array();
     if($row['MRL_Result']){
         $results['results'] = formatResultNumber($row, $row['MRL_Result']).' '.$row['MRLUnit'];
         $results['standard']= $row['MRL'];
         $results['grade']   = ($row['MRL']>$row['MRL_Result'])?'green':'red';
     }
     
     if($row['ResultStatus']){
         $results['results'] = $row['ResultStatus'];
         $results['standard']='X²';
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
  
  $sql =sprintf("SELECT `Description` FROM teststandards TS "
          . "join `Sample_Tests` ST on TS.StandardID=ST.StandardID where `SampleID`='%s'",$sampleid);
  $result = $conn->query($sql);
   $record = $result->fetch_assoc();
    
   return  $record['Description'];
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

    $defaultSignaturePath = realpath(__DIR__ . '/../images/icons8-no-image-100.png') ?: (__DIR__ . '/../images/icons8-no-image-100.png');

    $resolveSignaturePath = function ($rawPath) use ($defaultSignaturePath) {
        $rawPath = trim((string)$rawPath);
        $candidates = [];

        if ($rawPath !== '') {
            if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $rawPath) || strpos($rawPath, '/') === 0 || strpos($rawPath, '\\\\') === 0) {
                $candidates[] = $rawPath;
            } else {
$candidates[] = __DIR__ . '/../' . ltrim($rawPath, '/\\');
            $candidates[] = __DIR__ . '/' . ltrim($rawPath, '/\\');
            $candidates[] = __DIR__ . '/../images/' . ltrim($rawPath, '/\\');
            $candidates[] = dirname(__DIR__) . '/' . ltrim($rawPath, '/\\');
            }
        }

        $candidates[] = $defaultSignaturePath;

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate) && is_readable($candidate)) {
                return str_replace('\\', '/', $candidate);
            }
        }

        return str_replace('\\', '/', $defaultSignaturePath);
    };

    $companyRow = [];
    $companyResult = $conn->query("SELECT authorisation, technician, technician2 FROM company_master ORDER BY company_id DESC LIMIT 1");
    if ($companyResult) {
        $companyRow = $companyResult->fetch_assoc() ?: [];
    }

    $fetchUser = function ($userId) use ($conn, $resolveSignaturePath) {
        $defaultName = 'Not Assigned';
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

    return array(
        'authorisation' => $fetchUser($companyRow['authorisation'] ?? ''),
        'technician' => $fetchUser($companyRow['technician'] ?? ''),
        'technician2' => $fetchUser($companyRow['technician2'] ?? '')
    );
}

function buildFooterHtml($wazito){
    $kenhasLogo = str_replace('\\', '/', realpath($_SESSION['KenhasFile']) ?: $_SESSION['KenhasFile']);
    $ilacLogo = str_replace('\\', '/', realpath($_SESSION['ILAC']) ?: $_SESSION['ILAC']);
    $neemaLogo = str_replace('\\', '/', realpath($_SESSION['NeemaFile']) ?: $_SESSION['NeemaFile']);

    $statement = nl2br(htmlspecialchars((string)statementofconformity(), ENT_QUOTES, 'UTF-8'));
    $html = '<div style="font-family:helvetica,sans-serif; color:#111;">
      <table style="width:100%; border-collapse:collapse;" cellpadding="1">
        <tr>
          <td style="width:33%; text-align:center;">
            <img src="' . htmlspecialchars(str_replace('\\', '/', $wazito['authorisation']['signature_path']), ENT_QUOTES, 'UTF-8') . '" height="20"><br>
            <span style="font-size:9.5px; font-weight:bold; line-height:1.2;">' . htmlspecialchars($wazito['authorisation']['full_name'], ENT_QUOTES, 'UTF-8') . '</span><br>
            <span style="font-size:8px; color:#4a4a4a; letter-spacing:0.2px;">Authorization</span>
          </td>
          <td style="width:33%; text-align:center;">
            <img src="' . htmlspecialchars(str_replace('\\', '/', $wazito['technician']['signature_path']), ENT_QUOTES, 'UTF-8') . '" height="20"><br>
            <span style="font-size:9.5px; font-weight:bold; line-height:1.2;">' . htmlspecialchars($wazito['technician']['full_name'], ENT_QUOTES, 'UTF-8') . '</span><br>
            <span style="font-size:8px; color:#4a4a4a; letter-spacing:0.2px;">Laboratory Technician</span>
          </td>
          <td style="width:33%; text-align:center;">
            <img src="' . htmlspecialchars(str_replace('\\', '/', $wazito['technician2']['signature_path']), ENT_QUOTES, 'UTF-8') . '" height="20"><br>
            <span style="font-size:9.5px; font-weight:bold; line-height:1.2;">' . htmlspecialchars($wazito['technician2']['full_name'], ENT_QUOTES, 'UTF-8') . '</span><br>
            <span style="font-size:8px; color:#4a4a4a; letter-spacing:0.2px;">Laboratory Technician</span>
          </td>
        </tr>
        <tr>
          <td colspan="3" style="font-size:5px; text-align:center; border-top:1px solid #dcdcdc; padding-top:3px;">
            <span style="font-size:5px; font-weight:bold;">Statement of Conformity</span><br>' . $statement . '
          </td>
        </tr>
        <tr>
          <td style="width:33%; text-align:center;"><img src="' . htmlspecialchars($kenhasLogo, ENT_QUOTES, 'UTF-8') . '" height="10"></td>
          <td style="width:33%; text-align:center;"><img src="' . htmlspecialchars($ilacLogo, ENT_QUOTES, 'UTF-8') . '" height="10"></td>
          <td style="width:33%; text-align:center;"><img src="' . htmlspecialchars($neemaLogo, ENT_QUOTES, 'UTF-8') . '" height="10"></td>
        </tr>
        <tr>
          <td colspan="2" style="font-size:5px; text-align:left; color:#2f2f2f;">
            Lab Works East Africa Ltd is an ISO/IEC 17025:2017 Accredited Testing Laboratory (KENAS/TL/57)
          </td>
          <td style="font-size:5px; text-align:right; color:#2f2f2f;">
            Lab Works East Africa Ltd is NEMA gazetted no 47
          </td>
        </tr>
        <tr>
          <td colspan="3" style="font-size:5px; text-align:center; color:#2f2f2f;">
            This Laboratory report shall not be reproduced except in full, without a written consent of Lab Works East Africa Ltd
          </td>
        </tr>
      </table></div>';

    return $html;
}

?>
