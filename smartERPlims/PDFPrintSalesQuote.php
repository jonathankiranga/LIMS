<?php 
/**
 * Sales Quotation PDF - 100% writeHTML() Version
 * - No mixing with addTextWrap()
 * - Proper column alignment using HTML table widths
 * - Max TAT calculated and displayed in header
 * - Uses TCPDF writeHTML() only
 */

include('includes/session.inc');
include('includes/CurrenciesArray.php');
$CurrencyName = $CurrencyName ?? [];

$Title = _('Print Sales Quotation');
include('includes/CountriesArray.php');

$PathPrefix = '';

function findLogoFile($PathPrefix) {
    $dir = $PathPrefix.'logos/' ;
    $DirHandle = @dir($dir);
    if(!$DirHandle){
        return null;
    }
    $InCompanyDir = array();
    while ($DirEntry = $DirHandle->read() ){
        if ($DirEntry != '.' AND $DirEntry !='..'){
            $InCompanyDir[] = $DirEntry;
        }
    }
    if ($InCompanyDir !== FALSE && count($InCompanyDir) > 0) {
        $logo = null;
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
            return $PathPrefix . 'logos/'. $logo;
        }
    }
    return null;
}

function findILACFile($PathPrefix) {
    $dir = $PathPrefix.'logos/' ;
    $DirHandle = @dir($dir);
    if(!$DirHandle){
        return null;
    }
    $InCompanyDir = array();
    while ($DirEntry = $DirHandle->read() ){
        if ($DirEntry != '.' AND $DirEntry !='..'){
            $InCompanyDir[] = $DirEntry;
        }
    }
    if ($InCompanyDir !== FALSE && count($InCompanyDir) > 0) {
        $logo = null;
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
            return $PathPrefix . 'logos/'. $logo;
        }
    }
    return null;
}

function findkenhaFile($PathPrefix) {
    $dir = $PathPrefix.'logos/';
    $DirHandle = @dir($dir);
    if(!$DirHandle){
        return null;
    }
    $InCompanyDir = array();
    while ($DirEntry = $DirHandle->read() ){
        if ($DirEntry != '.' AND $DirEntry !='..'){
            $InCompanyDir[] = $DirEntry;
        }
    }
    if ($InCompanyDir !== FALSE && count($InCompanyDir) > 0) {
        $logo = null;
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
            return $PathPrefix . 'logos/'. $logo;
        }
    }
    return null;
}

function findNeemaFile($PathPrefix) {
    $dir = $PathPrefix.'logos/' ;
    $DirHandle = @dir($dir);
    if(!$DirHandle){
        return null;
    }
    $InCompanyDir = array();
    while ($DirEntry = $DirHandle->read() ){
        if ($DirEntry != '.' AND $DirEntry !='..'){
            $InCompanyDir[] = $DirEntry;
        }
    }
    if ($InCompanyDir !== FALSE && count($InCompanyDir) > 0) {
        $logo = null;
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
            return $PathPrefix . 'logos/'. $logo;
        }
    }
    return null;
}

function findLABCODEFile($PathPrefix) {
    $dir = $PathPrefix.'logos/' ;
    $DirHandle = @dir($dir);
    if(!$DirHandle){
        return null;
    }
    $InCompanyDir = array();
    while ($DirEntry = $DirHandle->read() ){
        if ($DirEntry != '.' AND $DirEntry !='..'){
            $InCompanyDir[] = $DirEntry;
        }
    }
    if ($InCompanyDir !== FALSE && count($InCompanyDir) > 0) {
        $logo = null;
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
            return $PathPrefix . 'logos/'. $logo;
        }
    }
    return null;
}

// Find all available logos
$companyLogo = findLogoFile($PathPrefix);
$ilacLogo = findILACFile($PathPrefix);
$kenhasLogo = findkenhaFile($PathPrefix);
$neemaLogo = findNeemaFile($PathPrefix);
$labcodeLogo = findLABCODEFile($PathPrefix);

function getFooter($DOC){
    $filename= 'quotes/'.trim($DOC).'.terms';
    $paymentterms = '';
    if(file_exists($filename)){
        $paymentterms = file_get_contents($filename);
    }
    $filename= 'quotes/'.trim($DOC).'.bank';
    $bankaccountdetails = '';
    if(file_exists($filename)){
        $bankaccountdetails = file_get_contents($filename);
    }
    return array(
        'paymentterms' => ((mb_strlen($paymentterms)>1)?$paymentterms:$_SESSION['paymentterms'] ?? ''),
        'bankaccountdetails' => ((mb_strlen($bankaccountdetails)>1)?$bankaccountdetails:$_SESSION['bankaccountdetails'] ?? '')
    );
}

if(isset($_GET['No'])){
    
    $SQL="select 
            `SalesHeader`.`documentno`
           ,`SalesHeader`.`docdate`
           ,`SalesHeader`.`oderdate`
           ,`SalesHeader`.`duedate`
           ,`SalesHeader`.`customercode`
           ,`SalesHeader`.`customername`
           ,`SalesHeader`.`currencycode`
           ,CONCAT(IFNULL(rtrim(`salesrepsinfo`.`salesman`),''),
               IF(`salesrepsinfo`.`phone` IS NOT NULL,
                  CONCAT(', Tel:', `salesrepsinfo`.`phone`), '')) as salesrep ,IFNULL(rtrim(`salesrepsinfo`.`email`),'') as salesrepemail
           ,`SalesHeader`.`userid`
           ,`debtors`.`email` as customeremail
           ,`debtors`.`city`
           ,`debtors`.`postcode`
           ,`debtors`.`country`
           ,`debtors`.`phone`
           ,`debtors`.`contact`
           ,`SalesHeader`.`externaldocumentno` as BankCode2
           ,SalesHeader.`locationcode` as BankCode
           ,SalesHeader.paymentterms
           ,`picture`
        from `SalesHeader` 
        left join SalesLine on `SalesHeader`.`documentno`=SalesLine.`documentno` 
        left join debtors on `SalesHeader`.`customercode`=debtors.itemcode
        left join `salesrepsinfo` on `salesrepsinfo`.code=`SalesHeader`.`salespersoncode`
        where `SalesHeader`.`documenttype`='54' 
        and `SalesHeader`.`documentno`='".$_GET['No']."'";

    $Result = DB_query($SQL,$db);
    $myrow  = DB_fetch_row($Result);
    $Bankcode = $myrow[17] ?? '';
    $Bankcode2 = $myrow[16] ?? '';
    $urlString = $myrow[18] ?? '';
    $footerArray = getFooter($_GET['No']);
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Quotation";
    
    $pdf->addInfo('Title',_('Sales Quotation'));
    $pdf->addInfo('Subject',_('Sales Quotation'));
    $pdf->addInfo('Creator',_('SmartERP'));
    
    $pdf->SetMargins(15, 0, 15, true);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->SetFont('helvetica', '', 10);
    
    // Footer with page numbers - uses {nb} for current page, {total} for total pages
    $pdf->setPrintFooter(true);
    $pdf->setFooterFont(array('helvetica', '', 8));
    $pdf->SetFooterMargin(10);
    
    // Get line items and calculate maxTAT, including discount
    // Note: LineDiscountPercent column should be added to SalesLine table:
    // ALTER TABLE SalesLine ADD COLUMN LineDiscountPercent DECIMAL(5,2) DEFAULT 0 AFTER TAT;
    // ALTER TABLE SalesHeader ADD COLUMN QtyDiscount DECIMAL(5,2) DEFAULT 0;
    $SQL=Sprintf("SELECT 
        SalesLine.code as barcode
       ,SalesHeader.docdate
       ,SalesLine.description 
       ,SalesLine.Quantity
       ,SalesLine.unitofmeasure
       ,SalesLine.UnitPrice as Sp
       ,SalesHeader.documentno
       ,SalesHeader.customercode
       ,SalesLine.invoiceamount
       ,SalesHeader.currencycode
       ,SalesLine.vatamount
       ,(SalesLine.invoiceamount-SalesLine.vatamount) as netamt
       ,SalesHeader.documenttype
       ,SalesLine.vatrate
       ,SalesLine.totalchargedcontainers
       ,SalesLine.partperunit
       ,SalesLine.code as itemcode
       ,SalesLine.TAT
       ,IFNULL(SalesLine.LineDiscountPercent, 0) as discountpercent
       ,IFNULL(SalesHeader.QtyDiscount, 0) as headerdiscount
    FROM SalesHeader join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        and SalesHeader.documenttype=54  
        where SalesHeader.documentno='%s' ",$_GET['No']);
    
    $ResultIndex=DB_query($SQL,$db);
    
    // Calculate totals and maxTAT with discount handling
    $R1=0; $R2=0; $R3=0; $maxTAT = 0; $headerDiscountPercent = 0;
    $lineItems = [];
    $SalesAddCategory =new SalesAddCategory();
    
    while($rows = DB_fetch_array($ResultIndex)){
        // Store header discount from first row
        if($headerDiscountPercent == 0 && isset($rows['headerdiscount'])){
            $headerDiscountPercent = (float)($rows['headerdiscount'] ?? 0);
        }
        
        // Get line discount - calculate from ORIGINAL unit price (not already-discounted netamt)
        $lineDiscountPercent = (float)($rows['discountpercent'] ?? 0);
        $originalLineTotal = $rows['Sp'] * $rows['Quantity'] * $rows['partperunit'];
        if($lineDiscountPercent > 0){
            $lineDiscountAmount = $originalLineTotal * ($lineDiscountPercent / 100);
            $rows['line_discount_amount'] = $lineDiscountAmount;
        } else {
            $rows['line_discount_amount'] = 0;
        }
        
        // Use already-discounted values directly - no re-application of discount
        $R1 += $rows['vatamount'];
        $R2 += ($rows['invoiceamount'] - $rows['vatamount']);  // net amount (already discounted)
        $R3 += $rows['invoiceamount'];
        $tatValue = (int)($rows['TAT'] ?? 0);
        if($tatValue > $maxTAT){
            $maxTAT = $tatValue;
        }
        $lineItems[] = $rows;
    }
    
    // Get bank details
    $GetbankSql = "SELECT `bankName`,`currency`,`AccountNo`,`BranchCode`,`BranchName`,`AcctName`,`bankCode`,`swiftcode`
        FROM BankAccounts where `accountcode`='".$Bankcode."'";
    $BankResult = DB_query($GetbankSql,$db);
    $BankRow = DB_fetch_row($BankResult);
    
    $GetbankSql2 = "SELECT `bankName`,`currency`,`AccountNo`,`BranchCode`,`BranchName`,`AcctName`,`bankCode`,`swiftcode`
        FROM BankAccounts where `accountcode`='".$Bankcode2."'";
    $BankResult2 = DB_query($GetbankSql2,$db);
    $BankRow2 = DB_fetch_row($BankResult2);
    
    // Build header using two-column layout (Customer left, Company+Sales Rep right)
    $html = '<div style="font-family:helvetica;font-size:9pt;color:#333;margin-bottom:1px;">';
    
    // Quotation Title - compact
    $html .= '<div style="text-align:center;margin-bottom:1px;">';
    $html .= '<div style="font-size:11pt;font-weight:bold;color:#2c3e50;">SALES QUOTATION</div>';
    $html .= '<div style="font-size:8pt;">No: ' . htmlspecialchars($_GET['No']) . ' | Date: ' . ConvertSQLDate($myrow[1]) . ' | Currency: ' . htmlspecialchars($myrow[6]) . '</div>';
    if ($maxTAT > 0) {
        $html .= '<div style="font-size:8pt;color:#155724;font-weight:bold;">Max TAT: ' . $maxTAT . ' day' . ($maxTAT > 1 ? 's' : '') . '</div>';
    }
    $html .= '</div>';
    
    // Compact two-column header layout
    $html .= '<table style="width:100%;border-collapse:collapse;border:1px solid #999;margin-bottom:5px;font-size:7pt;">';
    $html .= '<tr>';
    
    // Left Column - Customer Details + Sales Rep
    $html .= '<td style="width:50%;vertical-align:top;padding:3px;border-right:1px solid #999;">';
    $html .= '<div style="font-weight:bold;margin-bottom:2px;">CUSTOMER</div>';
    $html .= '<strong>' . htmlspecialchars($myrow[5]) . '</strong> <br>';
    $html .= 'Contact: ' . htmlspecialchars($myrow[15]) . ' | Email: ' . htmlspecialchars($myrow[10]) . '<br>';
    $html .= 'Phone: ' . htmlspecialchars($myrow[14]) . ' | City: ' . htmlspecialchars($myrow[11]) . '<br>';
    $html .= '<div style="font-weight:bold;margin-bottom:2px;">Sales Rep</div>';
    $html .= 'Name: ' . htmlspecialchars($myrow[7] ?? '') . ' | Email: ' . htmlspecialchars($myrow[8] ?? '');
    $html .= '</td>';
    
    // Right Column - Company Details
    $html .= '<td style="width:50%;vertical-align:top;padding:3px;">';
    if($companyLogo && file_exists($companyLogo)){
        $html .= '<img src="' . htmlspecialchars(str_replace('\\', '/', realpath($companyLogo)), ENT_QUOTES, 'UTF-8') . '"  height="18" style="vertical-align:middle;"> <br>';
    }
    $html .= '<strong>' . htmlspecialchars($_SESSION['CompanyRecord']['coyname']) . '</strong><br>';
    $html .= htmlspecialchars($_SESSION['CompanyRecord']['regoffice1']) . '<br>';
    $html .= htmlspecialchars($_SESSION['CompanyRecord']['regoffice2']) . '<br>';
    $html .= 'Tel: ' . htmlspecialchars($_SESSION['CompanyRecord']['telephone']) . ' | PIN: ' . htmlspecialchars($_SESSION['CompanyRecord']['PIN']);
    $html .= '</td>';
    
    $html .= '</tr></table>';
    
    // Line Items Table - Fixed height with page-break-inside-avoid
    $html .= '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;margin:10px 0;font-size:8pt; page-break-inside:avoid;">';
    $html .= '<thead><tr style="background-color:#2c3e50;color:#ffffff;">';
    $html .= '<th style="padding:5px;text-align:right;">Qty</th>';
    $html .= '<th style="padding:5px;text-align:left;">Item Description</th>';
    $html .= '<th style="padding:5px;text-align:right;">Price</th>';
    $html .= '<th style="padding:5px;text-align:right;">Disc %</th>';
    $html .= '<th style="padding:5px;text-align:right;">VAT %</th>';
    $html .= '<th style="padding:5px;text-align:right;">VAT Amt</th>';
    $html .= '<th style="padding:5px;text-align:right;">Net Amt</th>';
    $html .= '<th style="padding:5px;text-align:right;">TAT</th>';
    $html .= '</tr></thead><tbody>';
    
    $rowCount = 0;
    foreach ($lineItems as $rows) {
        $rowCount++;
        $bgColor = ($rowCount % 2 == 0) ? '#f8f9fa' : '#ffffff';
        $ppu = (int)$rows['partperunit'];
        $units = ($ppu > 1) ? $rows['unitofmeasure'] . '(1x' . $ppu . ')' : $rows['unitofmeasure'];
        $lineDiscountPercent = (float)($rows['discountpercent'] ?? 0);
        
        $html .= '<tr style="background-color:' . $bgColor . ';">';
        $html .= '<td style="padding:5px;border-bottom:1px solid #eee;text-align:right;">' . (int)$rows['Quantity'] . '</td>';
        $html .= '<td style="padding:5px;border-bottom:1px solid #eee;">' . htmlspecialchars($rows['description']) . '</td>';
        $html .= '<td style="padding:5px;border-bottom:1px solid #eee;text-align:right;">' . number_format($rows['Sp'],2) . '</td>';
        $html .= '<td style="padding:5px;border-bottom:1px solid #eee;text-align:right;">' . number_format($lineDiscountPercent,2) . '%</td>';
        $html .= '<td style="padding:5px;border-bottom:1px solid #eee;text-align:right;">' . (int)$rows['vatrate'] . '%</td>';
        $html .= '<td style="padding:5px;border-bottom:1px solid #eee;text-align:right;">' . number_format($rows['vatamount'],2) . '</td>';
        $html .= '<td style="padding:5px;border-bottom:1px solid #eee;text-align:right;">' . number_format($rows['netamt'],2) . '</td>';
        $html .= '<td style="padding:5px;border-bottom:1px solid #eee;text-align:right;">' . ($rows['TAT'] ?? '-') . '</td>';
        $html .= '</tr>';
    }
    // Totals rows aligned with Net Amt column (col 7)
    // Calculate line discount total
    $lineTotalDiscount = 0;
    foreach ($lineItems as $rows) {
        $lineTotalDiscount += ($rows['line_discount_amount'] ?? 0);
    }
    
    // Calculate header discount on subtotal BEFORE line discounts
    $headerDiscountAmount = 0;
    if($headerDiscountPercent > 0){
        $subtotalBeforeLineDiscounts = $R2 + $lineTotalDiscount;
        $headerDiscountAmount = $subtotalBeforeLineDiscounts * ($headerDiscountPercent / 100);
    }
    
    // $R2 and $R1 are already the final totals after ALL discounts (line + header)
    // No further deduction needed - use directly
    $finalNetAmount = $R2;
    $finalGrossAmount = $R3;
    
    $totalDiscount = $lineTotalDiscount + $headerDiscountAmount;
    $html .= '<tr><td colspan="6" style="text-align:right;padding:8px 5px;"><strong>Gross Total</strong></td><td style="text-align:right;padding:8px 5px;"><strong>' . number_format($R2 + $totalDiscount,2) . '</strong></td><td></td></tr>';
    // Always show discount total
    $html .= '<tr style="background-color:#fff3cd;"><td colspan="6" style="text-align:right;padding:8px 5px;"><strong>Less: Discount</strong></td><td style="text-align:right;padding:8px 5px;color:#856404;"><strong>' . number_format($totalDiscount,2) . '</strong></td><td></td></tr>';
    $html .= '<tr><td colspan="6" style="text-align:right;padding:8px 5px;"><strong>Net Total</strong></td><td style="text-align:right;padding:8px 5px;"><strong>' . number_format($R2,2) . '</strong></td><td></td></tr>';
    $html .= '<tr><td colspan="6" style="text-align:right;padding:8px 5px;"><strong>Add: VAT</strong></td><td style="text-align:right;padding:8px 5px;"><strong>' . number_format($R1,2) . '</strong></td><td></td></tr>';
    $html .= '<tr style="background-color:#e3f2fd;"><td colspan="6" style="text-align:right;padding:8px 5px;"><strong>Grand Total</strong></td><td style="text-align:right;padding:8px 5px;font-weight:bold;"><strong>' . number_format($finalGrossAmount,2) . '</strong></td><td></td></tr>';
    $html .= '</tbody></table>';
    
    // Payment Terms
    $paymentterms = str_replace(['<div>','</div>'], ['<br>',''], html_entity_decode($footerArray['paymentterms']));
    $html .= '<table style="width:100%;border-collapse:collapse;margin:10px 0;page-break-inside:avoid;"><tr><td style="padding:0;border:1px solid #999;">';
    $html .= '<div style="background-color:#34495e;color:#ffffff;padding:4px;font-weight:bold;font-size:8pt;">Payment Terms & Conditions</div>';
    $html .= '<div style="padding:4px;font-size:7pt;">' . $paymentterms . '</div>';
    $html .= '</td></tr></table>';
    
    // Bank Details - Two columns
    $html .= '<table style="width:100%;border-collapse:collapse;margin-top:10px;page-break-inside:avoid;"><tr>';
    // Primary Bank
    $html .= '<td style="width:50%;vertical-align:top;padding:0;border:1px solid #999;">';
    $html .= '<div style="background-color:#34495e;color:#ffffff;padding:3px 5px;font-weight:bold;font-size:8pt;">Primary Bank Account</div>';
    $html .= '<div style="padding:2px 3px;font-size:7pt;">';
    $html .= '<div><strong>Bank:</strong> ' . htmlspecialchars($BankRow[0] ?? '') . '</div>';
    $html .= '<div><strong>Account:</strong> ' . htmlspecialchars($BankRow[5] ?? '') . '</div>';
    $html .= '<div><strong>Account No:</strong> ' . htmlspecialchars($BankRow[2] ?? '') . '</div>';
    $html .= '<div><strong>Branch:</strong> ' . htmlspecialchars($BankRow[4] ?? '') . '</div>';
    $html .= '<div><strong>SWIFT:</strong> ' . htmlspecialchars($BankRow[7] ?? '') . '</div>';
    $html .= '<div><strong>Currency:</strong> ' . htmlspecialchars($CurrencyName[trim($BankRow[1] ?? '')] ?? $BankRow[1] ?? '') . '</div>';
    $html .= '</div></td>';
    // Secondary Bank
    $html .= '<td style="width:50%;vertical-align:top;padding:0;border:1px solid #999;">';
    $html .= '<div style="background-color:#34495e;color:#ffffff;padding:3px 5px;font-weight:bold;font-size:8pt;">Secondary Bank Account</div>';
    $html .= '<div style="padding:2px 3px;font-size:7pt;">';
    $html .= '<div><strong>Bank:</strong> ' . htmlspecialchars($BankRow2[0] ?? '') . '</div>';
    $html .= '<div><strong>Account:</strong> ' . htmlspecialchars($BankRow2[5] ?? '') . '</div>';
    $html .= '<div><strong>Account No:</strong> ' . htmlspecialchars($BankRow2[2] ?? '') . '</div>';
    $html .= '<div><strong>Branch:</strong> ' . htmlspecialchars($BankRow2[4] ?? '') . '</div>';
    $html .= '<div><strong>SWIFT:</strong> ' . htmlspecialchars($BankRow2[7] ?? '') . '</div>';
    $html .= '<div><strong>Currency:</strong> ' . htmlspecialchars($CurrencyName[trim($BankRow2[1] ?? '')] ?? $BankRow2[1] ?? '') . '</div>';
    $html .= '</div></td>';
    $html .= '</tr></table>';
    
// Signature section
   $html .= '<div style="margin-top:20px;page-break-inside:avoid;border:none;padding:10px;">';

// Footer Logos Section - ILAC, Kenha, Neema (after signature)
$kenhaPath = $kenhasLogo ? str_replace('\\', '/', realpath($kenhasLogo)) : '';
$ilacPath = $ilacLogo ? str_replace('\\', '/', realpath($ilacLogo)) : '';
$neemaPath = $neemaLogo ? str_replace('\\', '/', realpath($neemaLogo)) : '';

if($kenhaPath || $ilacPath || $neemaPath){
    $html .= '<table border="0" style="width:100%;margin-top:30px;margin-bottom:15px;border-collapse:collapse;border:none;">';
    $html .= '<tr>';
    $html .= '<td style="width:33%;text-align:center;padding-bottom:5px;border:none;"><img src="' . htmlspecialchars($kenhaPath, ENT_QUOTES, 'UTF-8') . '" height="20"></td>';
    $html .= '<td style="width:33%;text-align:center;padding-bottom:5px;border:none;"><img src="' . htmlspecialchars($ilacPath, ENT_QUOTES, 'UTF-8') . '" height="20"></td>';
    $html .= '<td style="width:33%;text-align:center;padding-bottom:5px;border:none;"><img src="' . htmlspecialchars($neemaPath, ENT_QUOTES, 'UTF-8') . '" height="20"></td>';
    $html .= '</tr></table>';
}

$html .= '</div>';
    
   
    
    // Create header HTML to include in body (for multi-page reports)
    $headerRepeat = '<div style="font-family:helvetica;font-size:8pt;padding:3px;border:1px solid #999;margin-bottom:5px;">';
    $headerRepeat .= '<table style="width:100%;"><tr>';
    $headerRepeat .= '<td><strong>QUOTATION:</strong> ' . htmlspecialchars($_GET['No']) . ' | <strong>Customer:</strong> ' . htmlspecialchars($myrow[5]) . '</td>';
$docNo = isset($_GET['No']) ? $_GET['No'] : '';
    $docDate = isset($myrow[1]) ? ConvertSQLDate($myrow[1]) : date('d/M/Y');
    $customerName = isset($myrow[5]) ? $myrow[5] : '';
    $companyName = isset($_SESSION['CompanyRecord']['coyname']) ? $_SESSION['CompanyRecord']['coyname'] : '';
    
    
    
    // Write 100% HTML to PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Add images if available
    $urls = json_decode($urlString, true);
    if (is_array($urls) && count($urls) > 0) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Quotation Images', 0, 1, 'C');
        $y = 30;
        foreach ($urls as $url) {
            $validUrl = urldecode(str_replace(['[', ']', '&quot;'], '', $url));
            if (file_exists($validUrl)) {
                $pdf->Image($validUrl, 15, $y, 180, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
                $y += 60;
                if ($y > 250) {
                    $pdf->AddPage();
                    $y = 30;
                }
            }
        }
    }
    
    // Email the quotation with the PDF attached (PDFPrintSalesQuote.php?No=X&emailto=1)
    if (isset($_GET['emailto']) && (int)$_GET['emailto'] == 1) {
        $custEmail = trim((string)($myrow[10] ?? ''));
        $custName  = trim((string)($myrow[5] ?? $_GET['No']));
        $docNum    = htmlspecialchars($_GET['No'], ENT_QUOTES);
        if ($custEmail === '') {
            http_response_code(400);
            echo '<html><body style="font-family:Arial;padding:20px;">'
                . 'No email address is recorded for customer <strong>' . htmlspecialchars($custName) . '</strong>. '
                . 'Set it on the customer record (debtors.email) and try again.<br>'
                . '<a href="PDFPrintSalesQuote.php?No=' . $docNum . '">Print Sales Quote</a>'
                . '</body></html>';
            exit;
        }
        $pdfString = $pdf->Output('', 'S');
        $pdf->__destruct();
        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'QUOT_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $_GET['No']) . '_' . date('Ymd_His') . '.pdf';
        @file_put_contents($tmpFile, $pdfString);

        set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . DIRECTORY_SEPARATOR . 'Mailer');
        require_once $PathPrefix . 'Mailer/CustomMailerclass.php';
        $mailer = new MyMailer();
        // Always CC the current user so they keep a copy of the sent quotation.
        $ccEmail = '';
        if (!empty($_SESSION['UserID'])) {
            $ccRes = DB_query("SELECT email FROM www_users WHERE userid='" . DB_escape_string($_SESSION['UserID']) . "' LIMIT 1", $db);
            if ($ccRow = DB_fetch_array($ccRes)) {
                $ccEmail = trim((string)($ccRow['email'] ?? $ccRow[0] ?? ''));
            }
        }
        $subject = 'Sales Quotation ' . $_GET['No'];
        $body = 'Dear Customer,<br><br>Please find attached your Sales Quotation No: <strong>' . $docNum . '</strong>.<br>'
            . 'This quotation is valid for 30 days.<br><br>Best Regards,<br>' . htmlspecialchars($companyName);
        ob_start();
        $mailer->sendmail($custEmail, $subject, $body, $tmpFile, $ccEmail, $companyName, $ccEmail);
        $sendInfo = ob_get_clean();
        @unlink($tmpFile);

        $sent = stripos($sendInfo, 'sent') !== false;
        echo '<html><body style="font-family:Arial;padding:20px;">';
        if ($sent) {
            echo '<p style="color:#1d6f2f;font-weight:bold;">Sales Quotation ' . $docNum . ' mailed to ' . htmlspecialchars($custEmail) . '.</p>';
        } else {
            echo '<p style="color:#a93226;font-weight:bold;">Could not email the quotation.</p><p>' . htmlspecialchars(strip_tags($sendInfo)) . '</p>';
        }
        echo '<p><a href="PDFPrintSalesQuote.php?No=' . $docNum . '">Print Sales Quote</a></p>';
        echo '</body></html>';
        exit;
    }

    $pdf->Output($_SESSION['DatabaseName'] . '_QUOTATION_' . $_GET['No'] . '_' . date('Y-m-d') . '.pdf', 'I');
    $pdf->__destruct();
    
} else {

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

$SQL="select 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid` ,
      sum(SalesLine.`invoiceamount`) as OrderValue
    from `SalesHeader` join SalesLine on 
    `SalesHeader`.`documentno`=SalesLine.`documentno` 
    where `SalesHeader`.`documenttype`='54' 
    group by 
    `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid`  
    order by 
    `SalesHeader`.`docdate` desc
    limit  ". $_SESSION['DefaultDisplayRecordsMax'] ."";
    $Result=DB_query($SQL,$db);
    
    Echo '<Table class="table table-bordered"><tr>'
            . '<th>Order <br />No</th>' 
            . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
            . '<th>Sales <br /> Order <br />Due <br />Date</th>'
            . '<th>Customer <br />ID</th>'
            . '<th>Customer<br /> Name</th>'
            . '<th>Sales <br />Order<br /> Value</th>'
            . '<th>Currency</th>'
            . '<th>Sales<br /> Person</th>'
            . '<th>Authorisation<br /> Status</th>'
            . '<th>Created<br /> By</th>'
            . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
       
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
       
        echo '</tr>';
  }
    
    echo '</table><br />';
    
echo '</div></form>';

include('includes/footer.inc');

}   
?>
