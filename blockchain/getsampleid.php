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
    
    $sampleID = $_GET['id'] ?? '';
    $record = [];
    if ($sampleID !== '') {
        $stmt = $conn->prepare("SELECT sh.*,dr.* ,st.*
            FROM  sample_header sh 
            join debtors dr on sh.CustomerID = dr.itemcode 
            join sample_tests st on sh.HeaderID=st.HeaderID
        WHERE st.TestID = ?");
        if ($stmt) {
            $stmt->bind_param("s",$sampleID);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result ? ($result->fetch_assoc() ?: []) : [];
            $stmt->close();
        }
    }
    
    $this->SetXY(105, 12);
    $fitFontSize = $this->fitText($record['customer'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['customer'] ?? '', 50), 0, 'L', 0, 1, '', '', true); // Limit characters
   
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['company'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['company'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['postcode'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['postcode'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['city'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['city'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['phone'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['phone'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
    $this->SetXY(105, $this->GetY());
    $fitFontSize = $this->fitText($record['email'] ?? '', 95,$DefaultFont);
    $this->SetFont('helvetica', 'B', $fitFontSize);
    $this->MultiCell(95, 5, $this->truncateText($record['email'] ?? '', 50), 0, 'L', 0, 1, '', '', true);
    
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
    
    // Bottom Section
    $this->SetXY(10, 62);
    $this->MultiCell(95, 5,'Sample Batch No:'.($record['DocumentNo'] ?? ''), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Received:'.formatDate($record['Date'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampling Date:'.formatDate($record['SamplingDate'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Test Started:'.formatDate($testreceived,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Date Test Ended:'.formatDate($testended,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampled By:'. ($record['SampledBy'] ?? ''), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(10, $this->GetY());
    $this->MultiCell(95, 5,'Sampling Method:'. ($record['SamplingMethod'] ?? ''), 0, 'L', 0, 1, '', '', true); // Limit characters
    
     $this->SetXY(105, 62);
    $this->MultiCell(95, 5,'Sample ID:'. $realsampleid, 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'B/No:'.($record['BatchNo'] ?? ''), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'B/size:'.($record['BatchSize'] ?? ''), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Date of Man:'.formatDate($record['ManufactureDate'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'Date of Exp:'.formatDate($record['ExpDate'] ?? null,'dd-mm-yy'), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'SKU:'.($record['SKU'] ?? ''), 0, 'L', 0, 1, '', '', true); // Limit characters
    $this->SetXY(105, $this->GetY());
    $this->MultiCell(95, 5,'External Sample ID:'. ($record['ExternalSample'] ?? ''), 0, 'L', 0, 1, '', '', true); // Limit characters
 
   
}
   
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
 
    $stmt = $conn->prepare("SELECT tr.*,tp.*,ts.* FROM test_results tr 
    join testparameters tp on tp.ParameterID=tr.ParameterID and tp.StandardID=tr.StandardID
    join teststandards ts on ts.StandardID=tr.StandardID 
    WHERE tr.`TestID` = ? and tr.`StatusID` = 4");
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

      $html .= '<tr>
            <td style="border: 1px solid #ddd; padding: 17px; width: 55%;">' . $sample['ParameterName'] . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 15%;">' . $sample['Method'] . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 15%;">' . $validatedresults['results'] . '</td>
            <td style="border: 1px solid #ddd; padding: 8px; width: 15%;">' . $validatedresults['standard'] . '</td>
          </tr>';
   }

    $html .= '</tbody></table>'; // Close the table
    $html .= '<div style="text-align: center; margin-top: 10px;">*******End of Test Results*******</div>'; // Add footer
    $html .= '<div style="text-align: left; margin-top: 10px;"><u>NOTES</u></div>'; // Add footer
    
    $lines = explode("\n", $SampletypeNotes);
    foreach ($lines as $line) {
      $html .= '<div style="text-align: left; font-family: Arial, sans-serif; font-size: 8px; line-height: 0.5; margin: 0; padding: 0;color: blue;">'.$line.'</div>'; // Add footer
    }
    
   $html .= '<div style="text-align: left; margin-top: 10px;"><u>INTERPRETATION OF ANALYSIS RESULTS</u></div>'; // Add footer
   $html .= '<div style="text-align: left; font-family: Arial, sans-serif; font-size: 8px; line-height: 1.0; margin: 0; padding: 0;color: grey;">' ; 
 
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
$pdf->Image($wazito['technician']['signature_path'],15,$iconrow,10,10);
$pdf->SetXY(80,$wazitorow+12);
$pdf->MultiCell(95, 5,$wazito['technician']['full_name'], 0, 'L', 0, 1, '', '', true); // Limit characters
$pdf->SetXY(80,$wazitorow+17);
$pdf->MultiCell(95, 5,'Laboratory Technician', 0, 'L', 0, 1, '', '', true); // Limit characters
$pdf->SetXY(155,$wazitorow);
$pdf->Image($wazito['technician2']['signature_path'],15,$iconrow,10,10);
$pdf->SetXY(155,$wazitorow+12);
$pdf->MultiCell(95, 5,$wazito['technician2']['full_name'], 0, 'L', 0, 1, '', '', true); // Limit characters
$pdf->SetXY(155,$wazitorow+17);
$pdf->MultiCell(95, 5,'Laboratory Technician', 0, 'L', 0, 1, '', '', true); // Limit characters

$samerow = $pdf->GetY();
$pdf->SetXY(10,$samerow+5);
$html = '<div style="text-align: center; font-family: Arial, sans-serif; font-size: 5px; line-height: 1.0; margin: 0; padding: 0;color: blue;">Statement of Conformity</div>'; // Add footer

$statementofconformity = statementofconformity();
$lines = explode("\n",$statementofconformity);
foreach ($lines as $line) {
  $html .= '<div style="text-align: left; font-family: Arial, sans-serif; font-size: 5px; line-height: 1.0; margin: 0; padding: 0;color: blue;">'.$line.'</div>'; // Add footer
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
    $stmt = $conn->prepare("SELECT  SampleID  FROM test_results WHERE TestID = ?");
    $stmt->bind_param("s",$sampleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result ? ($result->fetch_assoc() ?: []) : [];
    return $record['SampleID'] ?? '';
}


function validateresults($row){
     $results=array();
     if($row['MRL_Result']){
         $results['results'] = $row['MRL_Result'].' '.$row['MRLUnit'];
         $results['standard']= $row['MRL'];
         $results['grade']   = ($row['MRL']>$row['MRL_Result'])?'green':'red';
     }
     
     if($row['ResultStatus']){
         $results['results'] = $row['ResultStatus'];
         $results['standard']='X²';
         $results['grade']   ='';
     }
     
     if($row['RangeResult']){
         $results['results']  = $row['RangeResult'].' '.$row['UnitOfMeasure'];
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
   $record = $result ? $result->fetch_assoc() : null;
    
   return  htmlspecialchars($record['Description'] ?? '');
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

    $defaultSignaturePath = realpath(__DIR__ . '/images/icons8-no-image-100.png') ?: (__DIR__ . '/images/icons8-no-image-100.png');

    $resolveSignaturePath = function ($rawPath) use ($defaultSignaturePath) {
        $rawPath = trim((string)$rawPath);
        $candidates = [];

        if ($rawPath !== '') {
            $candidates[] = $rawPath;
            $candidates[] = __DIR__ . '/' . ltrim($rawPath, '/\\');
            $candidates[] = __DIR__ . '/images/' . ltrim($rawPath, '/\\');
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
    if ($companyResult && $companyResult->num_rows > 0) {
        $companyRow = $companyResult->fetch_assoc() ?: [];
    }

    $fetchUser = function ($userId) use ($conn, $resolveSignaturePath) {
        $defaultName = 'Not Assigned';
        $userId = trim((string)$userId);

        if ($userId === '') {
            return ['full_name' => $defaultName, 'signature_path' => $resolveSignaturePath('')];
        }

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

    $authorisation = $fetchUser($companyRow['authorisation'] ?? '');
    $technician = $fetchUser($companyRow['technician'] ?? '');
    $technician2 = $fetchUser($companyRow['technician2'] ?? '');

    return array('authorisation'=>array('full_name'=>$authorisation['full_name'],'signature_path'=>$authorisation['signature_path']),
        'technician'=>array('full_name'=>$technician['full_name'],'signature_path'=>$technician['signature_path']),
        'technician2'=>array('full_name'=>$technician2['full_name'],'signature_path'=>$technician2['signature_path']));
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
