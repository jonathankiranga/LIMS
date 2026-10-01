<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php');
$CurrencyName = $CurrencyName ?? [];
$Title = _('Print Sales Invoice');
include('includes/CountriesArray.php');
include('chats/EmailConfig.php');
require_once('vendor/autoload.php');
require_once('reports/BarCodeClass.inc');

$makebarcode = new makebarcode();
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

$documentno = ($_GET['No'] ?? $_GET['sendNo']);
$companyLogo = findLogoFile($PathPrefix);
$kenhasLogo = findkenhaFile($PathPrefix);
$neemaLogo = findNeemaFile($PathPrefix);
$ilacLogo = findILACFile($PathPrefix);
$kraQRcode = $makebarcode->getQr($documentno);
 
if(isset($_GET['sendNo'])){
// Email functionality - if email parameter is passed, queue the invoice
   $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="INVOICE";
    
    $pdf->addInfo('Title',_('Sales Invoice'));
    $pdf->addInfo('Subject',_('Sales Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
    
    $pdf->SetMargins(15, 0, 15, true);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->SetFont('helvetica', '', 10);
    $html = gethtmlbody($documentno);
    $pdf->writeHTML($html, true, false, true, false, '');  
    $pdfContent = $pdf->Output('', 'S');
    $pdfFilename = 'INVOICE-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)$_GET['sendNo']) . '.pdf';
     
    $customercode= (string)$_GET['itemcode'];
      // Get customer email
    $emailSQL = "SELECT email,customer FROM debtors WHERE itemcode='".DB_escape_string($customercode, $db)."' LIMIT 1";
    $emailResult = DB_query($emailSQL, $db);
    if($emailRow = DB_fetch_array($emailResult)){
        $customerEmail = $emailRow['email'];
        $customerName = $emailRow['customer'];

        if(!empty($customerEmail)){
            $sent = sendInvoiceEmail($customerEmail,$customerName, $pdfContent, $pdfFilename,$documentno);
            if($sent){
                prnMsg('Invoice sent to: ' . htmlspecialchars($customerEmail), 'success');
            } else {
                prnMsg('Failed to send email', 'error');
            }
        
         } else {
            prnMsg('Customer email not found', 'warn');
        }
    }
    
    


}elseif(isset($_GET['No'])){
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="INVOICE";
    
    $pdf->addInfo('Title',_('Sales Invoice'));
    $pdf->addInfo('Subject',_('Sales Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
    
    $pdf->SetMargins(15, 0, 15, true);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->SetFont('helvetica', '', 10);
    $html= gethtmlbody($_GET['No']);
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($_SESSION['DatabaseName'] . '_INVOICE_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf','I');
    $pdf->__destruct();
    
    DB_query("Update `SalesHeader` set printed=1 where SalesHeader.documenttype=10 and SalesHeader.documentno='".$_GET['No']."'", $db);
      
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';



echo '<div class="container">'
    . '<table class="table table-bordered"><tr>'
    . '<td><input type="submit" value="Refresh" id="f1lt3r" class="btn-info" />'
    . '<input type="hidden" name="CustomerID" id="CustomerID"/></td>'
    . '<td><input type="button" id="filtercustomer" value="Search Customer" class="btn-info" />Customer Name</td>'
    . '<td><input type="text" name="CustomerName" id="CustomerName" readonly="readonly"/></td></tr>'
    . '<tr><td colspan="2"><input type="submit" id="filterdocumentno" name="filterdocumentno" value="Search Invoice No" class="btn-info"/>Filter By Document No</td>'
    . '<td><input type="text" name="documentno" id="documentno"/></td></tr>'
    . '</table></div>';


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
      where `SalesHeader`.`documenttype`='10' ".    
      ((mb_strlen($_POST['CustomerID'])>0)?" and `SalesHeader`.`customercode`='".$_POST['CustomerID']."'":'').
      ((mb_strlen($_POST['documentno'])>0)?" and `SalesHeader`.`documentno` like '%".$_POST['documentno']."%'":'')."
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
      limit ". $_SESSION['DefaultDisplayRecordsMax'] ."";
    $Result=DB_query($SQL,$db);
       
    
    Echo '<div class="container">'
             . '<Table class="table table-bordered"><tr>'
             . '<th>Invoice <br />No</th>' 
             . '<th>Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Sales<br /> Person</th>'
. '<th>Authorisation<br /> Status</th>'
              . '<th>Created<br /> By</th>'
              . '<th>Pictures</th>'
              . '<th>Email</th>'
              . '</tr>';
     
   while($row=DB_fetch_array($Result)){
       echo '<tr>';
            
         echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
         htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
         echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
         echo sprintf('<td>%s</td>',$row['customercode']);
         echo sprintf('<td>%s</td>',$row['customername']);
         echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
         echo sprintf('<td>%s</td>',$row['currencycode']);
         echo sprintf('<td>%s</td>',$row['salespersoncode']);
         echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
         echo sprintf('<td>%s</td>',$row['userid']);
         echo sprintf('<td>%s</td>',getiamge($row['documentno']));
         echo sprintf('<td><a href="%s?sendNo=%s&itemcode=%s">Email</a></td>',
         htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['customercode']);
          
         echo '</tr>';
  }
        
echo '</table></DIV>';
echo '</div></form>';

include('includes/footer.inc');

}   


Function GetDeliveries($INVOICEno){
    global $db;
    //`yourreference` //19
    //`externaldocumentno`//10
    
    $SQL="SELECT 
        `SalesHeader`.`externaldocumentno` as salesorder
      from `SalesHeader` where `SalesHeader`.`documenttype`='10'
      and `SalesHeader`.`documentno`='".$INVOICEno."' limit 1";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $ref=$myrow[0];
    
    $array=array();
    $SQL="select
        `SalesHeader`.`documentno` as deleiveries
      from `SalesHeader` where `SalesHeader`.`documenttype`='19' 
      and `SalesHeader`.`yourreference`='".$ref."'";
    $Result=DB_query($SQL,$db);
    while($myrow= DB_fetch_array($Result)){
        $array[]=$myrow['deleiveries'];
    }
    
    $return='';
    foreach ($array as $value) {
        $return .= $value."";
    }
    return $return;
}

function getiamge($ref){
    global $host,$database,$DBUser,$DBPassword;
    $dbimage = new mysqli($host, $DBUser, $DBPassword, $database);
 
    $SQL=sprintf("SELECT `picture` FROM `SalesHeader` where documentno='%s'",trim($ref));
     $Result=DB_query($SQL,$dbimage);
      $myrow=DB_fetch_row($Result);
    $urlString=$myrow[0];
     $images="";
          $urls = json_decode($urlString,TRUE);
        if (is_array($urls) && count($urls) > 0) {
            foreach ($urls as $url) {
                 $validUrl = htmlspecialchars($url,ENT_QUOTES,'utf-8'); // Decode the HTML entities
                 $validUrl = urldecode($url); // Decode the URL-encoded characters
                 $validUrl = str_replace('[', '' ,$validUrl);
                 $validUrl = str_replace( ']', '' ,$validUrl);
                 $validUrl = str_replace( '&quot;', '' ,$validUrl);
                 $images .= sprintf('<img src="%s" width="50" alt="Image">',$validUrl);
            }
           
        } 
        
        return $images;
}

function gethtmlbody($documentno) {
    global $db, $pdf, $companyLogo, $kenhasLogo, $ilacLogo, $neemaLogo, $kraQRcode;

    // --- MANDATORY DATA FETCHING ---
    $SQL = "SELECT 
            sh.documentno, sh.docdate, sh.oderdate, sh.duedate, sh.customercode,
            sh.customername, sh.currencycode,
            CONCAT(IFNULL(rtrim(rep.salesman),''), IF(rep.phone IS NOT NULL, CONCAT(', Tel:', rep.phone), '')) as rep_name,
            IFNULL(rtrim(rep.email),'') as rep_email,
            sh.userid, d.email, d.city, d.postcode, d.country, d.phone, d.contact,
            sh.yourreference, sh.externaldocumentno as BankCode2, sh.printed, d.middlen,
            sh.packagescharge, sh.picture, sh.locationcode as BankCode
            FROM SalesHeader sh
            JOIN debtors d ON sh.customercode = d.itemcode
            LEFT JOIN salesrepsinfo rep ON rep.code = sh.salespersoncode
            WHERE sh.documenttype = '10' AND sh.documentno = '" . $documentno . "' 
            GROUP BY sh.documentno";
    
    $Resultvs = DB_query($SQL, $db);
    $myrow = DB_fetch_row($Resultvs);
    $Bankcode  = $myrow[22] ?? '';
    $Bankcode2 = $myrow[17] ?? '';

    $etimsSQL = "SELECT kra_invoice_no, invoice_signature, verification_url, etims_issue_datetime, qr_code_text 
                 FROM etims_invoice WHERE documenttype='10' AND documentno='" . $documentno . "'";
    $etimsResult = DB_query($etimsSQL, $db);
    $etims = DB_fetch_array($etimsResult) ?: [];

    // --- CALCULATIONS ---
    
    $SQL_Lines=SPrintf("SELECT 
            SalesLine.SampleID as SampleID
            ,Container.`descrip` as containername
            ,SalesHeader.docdate
            ,GROUP_CONCAT(SalesLine.`description` SEPARATOR ', ') as description
            ,1 as Quantity
            ,SUM(SalesLine.UnitPrice) as Sp 
            ,SalesHeader.`documentno`
            ,SalesHeader.`customercode`
            ,SUM(SalesLine.invoiceamount) as invoiceamount
            ,SalesHeader.`currencycode`
            ,SUM(SalesLine.vatamount) as vatamount
            ,SUM(SalesLine.invoiceamount) - SUM(SalesLine.vatamount) as netamt
            ,SalesHeader.`documenttype`
            ,AVG(SalesLine.vatrate) as vatrate
            ,SUM(IFNULL(SalesLine.totalchargedcontainers,0)) as totalchargedcontainers
            ,SUM(IFNULL(SalesLine.shipping,0)) as shipping
            ,SalesHeader.QtyDiscount
            ,1 as partperunit
            ,AVG(IFNULL(SalesLine.LineDiscountPercent,0)) as lineDiscount
            ,IFNULL(SalesHeader.QtyDiscount,0) as headerDiscount
    FROM `SalesHeader` 
    join SalesLine  on SalesHeader.documentno=SalesLine.documentno 
    and SalesHeader.documenttype=SalesLine.documenttype and SalesHeader.documenttype=10
    left join stockmaster Container on Container.itemcode=SalesLine.containercode
    where SalesHeader.documentno='%s' GROUP BY
      SalesLine.SampleID
    , Container.descrip
    , SalesHeader.docdate
    , SalesHeader.documentno
    , SalesHeader.customercode
    , SalesHeader.currencycode
    , SalesHeader.documenttype
    , SalesHeader.QtyDiscount",$documentno);
    $Results = DB_query($SQL_Lines, $db);

    $R1=0; $R3=0; $netLineTotal = 0; $lineItems = []; $headerDiscountPercent = 0;
    while($rows = DB_fetch_array($Results)){
        if($headerDiscountPercent == 0) $headerDiscountPercent = (float)($rows['headerDiscount'] ?? 0);
        $lineDiscountPercent = (float)($rows['lineDiscount'] ?? 0);
        $originalLineTotal = $rows['Sp'] * $rows['Quantity'];
        $rows['lineDiscountAmount'] = $originalLineTotal * ($lineDiscountPercent / 100);
        $rows['lineNetamount'] = $rows['netamt'];
        $R1 += $rows['vatamount'];
        $R3 += $rows['invoiceamount'];
        $netLineTotal += $rows['lineNetamount'];
        $lineItems[] = $rows;
    }

    $totalDiscount = 0;
    foreach($lineItems as $line) { $totalDiscount += $line['lineDiscountAmount']; }
    if($headerDiscountPercent > 0){
        $totalDiscount += ($netLineTotal + $totalDiscount) * ($headerDiscountPercent / 100);
    }

    $primaryColor = '#2c3e50';
    $html = '<div style="font-family:helvetica; color:#333;">';

    // --- HEADER ---
    $html .= '
    <table width="100%" cellpadding="5">
        <tr>
            <td width="50%"><img src="'.htmlspecialchars($companyLogo).'" height="45"></td>
            <td width="50%" align="right">
                <span style="font-size:22pt; font-weight:bold; color:'.$primaryColor.'">SALES INVOICE</span><br>
                <span style="font-size:10pt;">Invoice No: <b>'.$documentno.'</b><br>Date: '.ConvertSQLDate($myrow[1]).'</span>
            </td>
        </tr>
    </table>';

    // --- ADDRESSES ---
    $html .= '
    <table width="100%" cellpadding="5" style="font-size:8.5pt; border-top:1px solid #ccc; border-bottom:1px solid #ccc;">
        <tr>
            <td width="50%">
                <b style="color:'.$primaryColor.'">Customer:</b><br>
                <strong>'.htmlspecialchars($myrow[5]).'</strong><br>
                '.htmlspecialchars($myrow[11]).'<br>
                Tel: '.htmlspecialchars($myrow[14]).' | Email: '.htmlspecialchars($myrow[10]).'
            </td>
            <td width="50%" align="right">
                <b style="color:'.$primaryColor.'">INVOICE FROM:</b><br>
                <b>'.htmlspecialchars($_SESSION['CompanyRecord']['coyname']).'</b><br>
                '.htmlspecialchars($_SESSION['CompanyRecord']['regoffice1']).'<br>
                PIN: '.htmlspecialchars($_SESSION['CompanyRecord']['PIN']).'<br>
                Tel: '.htmlspecialchars($_SESSION['CompanyRecord']['telephone']).'
            </td>
        </tr>
    </table><br>';

    // --- ITEMS TABLE ---
    $html .= '
    <table width="100%" cellpadding="6" cellspacing="0" style="font-size:8pt;">
        <thead>
            <tr style="background-color:'.$primaryColor.'; color:#fff; font-weight:bold;">
                <th width="12%">Sample ID</th>
                <th width="38%">Description</th>
                <th width="8%" align="center">Qty</th>
                <th width="12%" align="right">Price</th>
                <th width="10%" align="right">Disc</th>
                <th width="8%" align="right">VAT%</th>
                <th width="12%" align="right">Net Amt</th>
            </tr>
        </thead>
        <tbody>';

    foreach($lineItems as $rows){
        $html .= '
            <tr>
                <td width="12%" style="border-bottom:1px solid #eee;">'.$rows['SampleID'].'</td>
                <td width="38%" style="border-bottom:1px solid #eee;"><b>'.$rows['description'].'</b></td>
                <td width="8%"  style="border-bottom:1px solid #eee;" align="center">'.(int)$rows['Quantity'].'</td>
                <td width="12%" style="border-bottom:1px solid #eee;" align="right">'.number_format($rows['Sp'],2).'</td>
                <td width="10%" style="border-bottom:1px solid #eee;" align="right">'.number_format($rows['lineDiscountAmount'],2).'</td>
                <td width="8%"  style="border-bottom:1px solid #eee;" align="right">'.(int)$rows['vatrate'].'%</td>
                <td width="12%" style="border-bottom:1px solid #eee;" align="right">'.number_format($rows['lineNetamount'],2).'</td>
            </tr>';
    }
    
    $minRows = 7;
    $currentRows = count($lineItems);
    for ($i = $currentRows; $i < $minRows; $i++) {
        $html .= '
            <tr>
                <td style="border-bottom:1px solid #eee;"></td>
                <td style="border-bottom:1px solid #eee;"></td>
                <td style="border-bottom:1px solid #eee;" align="center"></td>
                <td style="border-bottom:1px solid #eee;" align="right"></td>
                <td style="border-bottom:1px solid #eee;" align="right"></td>
                <td style="border-bottom:1px solid #eee;" align="right"></td>
                <td style="border-bottom:1px solid #eee;" align="right"></td>
            </tr>';
    }
    $html .= '</tbody></table>';

    // --- TOTALS & TERMS ---
    $paymentterms = ($_SESSION['paymentterms'] ?? '');
    $html .= '
    <table nobrk="true" width="100%" cellpadding="5" style="margin-top:10px;">
        <tr>
            <td width="60%" style="font-size:7pt; color:#444;">
                <div style="background-color:#f9f9f9; padding:8px; border-left:3px solid '.$primaryColor.';">
                    <b style="font-size:8pt;">PAYMENT TERMS & CONDITIONS</b><br>
                    '.str_replace(array('<div>','</div>'), array('<br>',''), html_entity_decode($paymentterms)).'
                </div>
            </td>
            <td width="40%">
                <table width="100%" style="font-size:9pt; border-collapse:collapse;">
                    <tr><td align="right">Gross Total:</td><td align="right">'.number_format($netLineTotal + $totalDiscount, 2).'</td></tr>
                    <tr><td align="right" style="color:#a94442;">Less Discount:</td><td align="right" style="color:#a94442;">-'.number_format($totalDiscount, 2).'</td></tr>
                    <tr><td align="right">Add VAT:</td><td align="right">'.number_format($R1, 2).'</td></tr>
                    <tr style="background-color:'.$primaryColor.'; color:#fff; font-weight:bold;">
                        <td align="right">GRAND TOTAL:</td>
                        <td align="right">'.$myrow[6].' '.number_format($R3, 2).'</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';

    // --- BANK DETAILS (MANDATORY FIELDS) ---
    $html .= '<table nobrk="true" width="100%" style="margin-top:15px; font-size:7.5pt; border:1px solid #ccc;">';
    $html .= '<tr style="background-color:'.$primaryColor.'; color:white;"><td colspan="2" style="padding:4px;"><b>BANK PAYMENT DETAILS</b></td></tr><tr>';
    
    $bankQueries = [$Bankcode, $Bankcode2];
    foreach($bankQueries as $code) {
        if(!$code) continue;
        $bSql = "SELECT bankName, currency, AccountNo, BranchCode, BranchName, AcctName, swiftcode FROM BankAccounts WHERE accountcode='".$code."'";
        $bRes = DB_query($bSql, $db);
        if($bank = DB_fetch_array($bRes)) {
            $html .= '<td width="50%" style="padding:5px; border-right:1px solid #ccc;">
                <strong>Bank:</strong> '.$bank['bankName'].' ('.$bank['BranchName'].')<br>
                <strong>Account Name:</strong> '.$bank['AcctName'].'<br>
                <strong>Account No:</strong> '.$bank['AccountNo'].'<br>
                <strong>Branch Code:</strong> '.$bank['BranchCode'].' | <strong>Swift:</strong> '.$bank['swiftcode'].'<br>
                <strong>Currency:</strong> '.$bank['currency'].'
            </td>';
        }
    }
    $html .= '</tr></table>';

    $kenhaPath = $kenhasLogo ? str_replace('\\', '/', $kenhasLogo) : '';
$ilacPath = $ilacLogo ? str_replace('\\', '/', $ilacLogo) : '';
$neemaPath = $neemaLogo ? str_replace('\\', '/', $neemaLogo) : '';
$kraQRcodePath = $kraQRcode ? str_replace('\\', '/', $kraQRcode) : '';

if($kenhaPath && $ilacPath && $neemaPath && $kraQRcodePath){ 
    // --- E-TIMS & KRA COMPLIANCE ---

        $html .= '
        <table width="100%" style="margin-top:15px; border:1px solid #28a745; padding:8px;">
            <tr>
                <td width="15%"><img src="'.htmlspecialchars($kraQRcodePath, ENT_QUOTES, 'UTF-8').'" height="55"></td>
                <td width="55%" style="font-size:7.5pt; line-height:1.2;">
                    <b style="color:#1e7e34;">eTIMS VERIFIED DOCUMENT</b><br>
                    <b>Signature:</b> '.($etims['invoice_signature'] ?? '').'<br>
                    <b>KRA Invoice:</b> '.($etims['kra_invoice_no']  ?? '' ).'<br>
                    <b>Date/Time:</b> '.($etims['etims_issue_datetime']  ?? '') .'<br>
                    <b>Verification:</b> '.($etims['verification_url']  ?? '').'
                </td>
                <td width="30%" align="right" style="vertical-align:bottom;">
                    <img src="' . htmlspecialchars($kenhaPath, ENT_QUOTES, 'UTF-8') . '" height="20">
                    <img src="' . htmlspecialchars($ilacPath, ENT_QUOTES, 'UTF-8') . '" height="20">
                    <img src="' . htmlspecialchars($neemaPath, ENT_QUOTES, 'UTF-8') . '" height="20">
                </td>
            </tr>
        </table>';
     }

    $html .= '</div>';
    return $html;
}


function encrypt($data){
 // Store cipher method
$ciphering = "BF-CBC";
 
$options = 0;
// Use random_bytes() function which gives
// randomly 16 digit values
$encryption_iv = "12345678";
// Alternatively, we can use any 16 digit
// characters or numeric for iv
$encryption_key = "12345678";
// Encryption of string process starts
$encryption = openssl_encrypt($data, $ciphering,$encryption_key, $options, $encryption_iv);

return $encryption;
}


function decrypt($data){
// Store cipher method
$ciphering = "BF-CBC";

$options = 0;

$decryption_iv ="12345678" ;
// Store the decryption key
$decryption_key = "12345678";
// Descrypt the string
$decryption = openssl_decrypt ($data, $ciphering,$decryption_key, $options,$decryption_iv);

return $decryption;
}


function sendInvoiceEmail($toEmail, $toName, $pdfContent, $pdfFilename, $invoiceNo) {
    $mail = new PHPMailer(true);
    try {
        // Server Settings
        $mail->isSMTP();
        $mail->Host       = HOST;
        $mail->SMTPAuth   = true; // Ensure this is true
        $mail->Username   = username;
        $mail->Password   = trim(password);
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Identity - Fixes the 554 error
        $mail->setFrom(username,htmlspecialchars($_SESSION['CompanyRecord']['coyname']));
        $mail->addReplyTo(username, $_SESSION['UsersRealName']);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Invoice " . $invoiceNo;
        $mail->Body    = "Dear $toName, <br><br>Please find your invoice attached.";
        
        // Attachment
        $mail->addStringAttachment($pdfContent, $pdfFilename, 'base64', 'application/pdf');

return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
