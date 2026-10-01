<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Order');

if(isset($_GET['No'])){
    
$SQL="select 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,CONCAT(IFNULL(rtrim(`salesrepsinfo`.`salesman`),''), IF(`salesrepsinfo`.`phone` IS NOT NULL, CONCAT(', Tel:', `salesrepsinfo`.`phone`), ''))
      ,IFNULL(rtrim(`salesrepsinfo`.`email`),'')
      ,`SalesHeader`.`userid`
      ,`debtors`.email
      ,`debtors`.city
      ,`debtors`.postcode
      ,`debtors`.country
      ,`debtors`.phone
      ,`debtors`.contact
      ,`SalesHeader`.`yourreference`   
      ,SalesHeader.`locationcode` as BankCode
      ,`SalesHeader`.`picture`
      from `SalesHeader` join SalesLine on 
      `SalesHeader`.`documentno`=SalesLine.`documentno` 
      join debtors on `SalesHeader`.`customercode`=debtors.itemcode
           left join `salesrepsinfo` on `salesrepsinfo`.code=`SalesHeader`.`salespersoncode`
      where `SalesHeader`.`documenttype`='1' 
      and `SalesHeader`.`documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $urlString=$myrow[18];
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="ORDER";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
    $logoPath = '';
    if (!empty($_SESSION['LogoFile']) && file_exists($_SESSION['LogoFile'])) {
        $logoPath = str_replace('\\', '/', $_SESSION['LogoFile']);
    }
    $companyName = htmlspecialchars($_SESSION['CompanyRecord']['coyname'] ?? '', ENT_QUOTES, 'UTF-8');
    $companyAddress1 = htmlspecialchars(trim($_SESSION['CompanyRecord']['regoffice1'] ?? ''), ENT_QUOTES, 'UTF-8');
    $companyTelephone = htmlspecialchars($_SESSION['CompanyRecord']['telephone'] ?? '', ENT_QUOTES, 'UTF-8');
    $companyEmail = htmlspecialchars(trim($_SESSION['CompanyRecord']['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $companyPIN = htmlspecialchars($_SESSION['CompanyRecord']['PIN'] ?? '', ENT_QUOTES, 'UTF-8');
    
     $SQL=Sprintf("SELECT      
         SalesLine.SampleID as sampleid, 
         stockmaster.barcode
       ,Container.`descrip` as containername
      ,SalesHeader.docdate
      ,SalesLine.`description` 
      ,SalesLine.Quantity,
       `SalesLine`.UOM,
       SalesLine.UnitPrice as Sp ,
       `SalesLine`.`unitofmeasure` as UOMDesc
      ,SalesHeader.`documentno`
      ,SalesHeader.`customercode`
      ,SalesLine.invoiceamount
      ,SalesHeader.`currencycode`
      ,SalesLine.vatamount
      ,SalesLine.invoiceamount-SalesLine.vatamount as netamt
      ,SalesHeader.`documenttype`
      ,SalesLine.vatrate
      ,SalesLine.`totalchargedcontainers`
      ,SalesLine.PartPerUnit as partperunit
      ,SalesLine.code as itemcode
      ,IFNULL(SalesLine.TAT,0) as TAT
      ,IFNULL(SalesLine.LineDiscountPercent,0) as lineDiscount
      ,IFNULL(SalesHeader.QtyDiscount,0) as headerDiscount
  FROM `SalesHeader` 
        join SalesLine on SalesHeader.documentno=SalesLine.documentno  and SalesHeader.documenttype=SalesLine.documenttype and SalesHeader.documenttype=1
        join stockmaster on stockmaster.itemcode=SalesLine.code
        left join stockmaster Container on Container.itemcode=SalesLine.containercode
        where SalesHeader.documentno='%s'  order by `description` asc ",$_GET['No']);
     
     $Results=DB_query($SQL,$db);
     $R1=0; $R2=0; $R3=0; $maxTAT=0; $headerDiscountPercent=0;
     $lineItems=array();
     $SalesAddCategory = new SalesAddCategory();
     $containerNote = '';

     while($rows = DB_fetch_array($Results)){
         if($headerDiscountPercent == 0){
             $headerDiscountPercent = (float)($rows['headerDiscount'] ?? 0);
         }
         $lineDiscountPercent = (float)($rows['lineDiscount'] ?? 0);
         $originalLineTotal = $rows['Sp'] * $rows['Quantity'] * $rows['partperunit'];
         if($lineDiscountPercent > 0){
             $rows['line_discount_amount'] = $originalLineTotal * ($lineDiscountPercent / 100);
         } else {
             $rows['line_discount_amount'] = 0;
         }
         $R1 += $rows['vatamount'];
         $R2 += $rows['netamt'];
         $R3 += $rows['invoiceamount'];
         $tatValue = (int)($rows['TAT'] ?? 0);
         if($tatValue > $maxTAT){
             $maxTAT = $tatValue;
         }
         if(mb_strlen($rows['containername'])>0){
             $containerNote = $rows['containername'];
         }
         $lineItems[] = $rows;
     }

     $companyAddress2 = htmlspecialchars(trim($_SESSION['CompanyRecord']['regoffice2'] ?? ''), ENT_QUOTES, 'UTF-8');
     $companyAddress3 = htmlspecialchars(trim($_SESSION['CompanyRecord']['regoffice3'] ?? ''), ENT_QUOTES, 'UTF-8');
     $companyAddress4 = htmlspecialchars(trim($_SESSION['CompanyRecord']['regoffice4'] ?? ''), ENT_QUOTES, 'UTF-8');
     $companyAddress5 = htmlspecialchars(trim($_SESSION['CompanyRecord']['regoffice5'] ?? ''), ENT_QUOTES, 'UTF-8');
     $companyAddress6 = htmlspecialchars(trim($_SESSION['CompanyRecord']['regoffice6'] ?? ''), ENT_QUOTES, 'UTF-8');
     $companyAddress = trim($companyAddress1 . '<br>' . $companyAddress2 . '<br>' . $companyAddress3 . '<br>' . $companyAddress4 . '<br>' . $companyAddress5 . '<br>' . $companyAddress6, '<br>');

     $totalDiscount = 0;
     foreach($lineItems as $line){
         $totalDiscount += $line['lineDiscountAmount'];
     }
     if($headerDiscountPercent > 0){
         $headerDiscountAmount = ($R2 + $totalDiscount) * ($headerDiscountPercent / 100);
         $totalDiscount += $headerDiscountAmount;
     } else {
         $headerDiscountAmount = 0;
     }

     $html = '<div style="font-family:helvetica;font-size:9pt;color:#333;margin-bottom:15px;">';

    // Quotation Title and Info
    $html .= '<div style="text-align:center;margin-bottom:15px;">';
    $html .= '<div style="font-size:16pt;font-weight:bold;color:#2c3e50;">SALES ORDER</div>';
    $html .= '<div style="font-size:10pt;">No: ' . htmlspecialchars($_GET['No']) . ' | Date: ' . ConvertSQLDate($myrow[1]) . ' | Currency: ' . htmlspecialchars($myrow[6]) . '</div>';
    if($maxTAT > 0){
        $html .= '<div style="font-size:10pt;color:#155724;font-weight:bold;">Maximum TAT: ' . $maxTAT . ' day' . ($maxTAT > 1 ? 's' : '') . '</div>';
    }
    $html .= '</div>';

    // Two-column header layout (Customer left, Company+Sales Rep right)
    $html .= '<table style="width:100%;border-collapse:collapse;border:1px solid #999;margin-bottom:15px;">';
    $html .= '<tr>';

    // Left Column - Customer Details
    $html .= '<td style="width:50%;vertical-align:top;padding:10px;border-right:1px solid #999;">';
    $html .= '<div style="font-weight:bold;padding:6px;margin-bottom:8px;text-align:center;">CUSTOMER DETAILS</div>';
    $html .= '<strong>' . htmlspecialchars($myrow[5]) . '</strong><br>';
    $html .= '<div style="font-size:8pt;margin-top:4px;">';
    $html .= 'Contact: ' . htmlspecialchars($myrow[15]) . '<br>';
    $html .= 'Email: ' . htmlspecialchars($myrow[10]) . '<br>';
    $html .= 'Phone: ' . htmlspecialchars($myrow[14]) . '<br>';
    $html .= '</div>';
    $html .= '</td>';

    // Right Column - Company Details and Sales Rep
    $html .= '<td style="width:50%;vertical-align:top;padding:5px 10px;border-right:1px solid #999;">';


    $html .= '<div style="font-weight:bold;padding:4px;margin-bottom:6px;text-align:center;">COMPANY DETAILS</div>';
    if($logoPath){
        $html .= '<div style="text-align:left;margin-bottom:10px;">';
        $html .= '<img src="' . htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') . '"  height="28">';
        $html .= '</div>';
    }
    $html .= '<strong>' . htmlspecialchars($companyName) . '</strong><br>';
    $html .= '<div style="font-size:8pt;margin-top:2px;margin-bottom:8px;">';
    $html .= htmlspecialchars($companyAddress1) . '<br>';
    $html .= htmlspecialchars($companyAddress2) . '<br>';
    $html .= 'Tel: ' . htmlspecialchars($companyTelephone) . '<br>';
    $html .= 'PIN: ' . htmlspecialchars($companyPIN) . '<br>';
    $html .= 'Email: ' . htmlspecialchars($companyEmail) . '<br>';
    $html .= '</div>';

    $html .= '<div style="font-size:8pt;">';
    $html .= 'SALES REPRESENTATIVE'  . '<br>';
    $html .= htmlspecialchars($myrow[7] ?? '') . '<br>';
    $html .= 'Email: ' . htmlspecialchars($myrow[8] ?? '') . '<br>';
    $html .= '</div>';
    $html .= '</td>';

    $html .= '</tr></table>';
    $html .= '</div>';

     $html .= '<table style="width:100%;border-collapse:collapse;font-size:9pt;">
         <thead>
           <tr style="background-color:#2c3e50;color:#ffffff;">
             <th style="padding:5px;text-align:left;">Sample ID</th>
             <th style="padding:0px;text-align:left;">Description</th>
             <th style="padding:6px;border:1px solid #ccc;text-align:right;">Qty</th>
             <th style="padding:6px;border:1px solid #ccc;text-align:right;">Price</th>
             <th style="padding:6px;border:1px solid #ccc;text-align:right;">Disc %</th>
             <th style="padding:6px;border:1px solid #ccc;text-align:right;">VAT %</th>
             <th style="padding:6px;border:1px solid #ccc;text-align:right;">VAT Amt</th>
             <th style="padding:6px;border:1px solid #ccc;text-align:right;">Net Amt</th>
           </tr>
         </thead>
         <tbody>';
     
     foreach($lineItems as $rows){
         $html .= '<tr style="border-bottom:1px solid #eee;">';
         $html .= '<td style="padding:6px;border:1px solid #ccc;">' . htmlspecialchars($rows['sampleid']) . '</td>';
         $html .= '<td style="padding:0px;border:1px solid #ccc;text-align:left;">' . htmlspecialchars($rows['description']) . '</td>';
         $html .= '<td style="padding:6px;border:1px solid #ccc;text-align:right;">' . (int)$rows['Quantity'] . '</td>';
         $html .= '<td style="padding:6px;border:1px solid #ccc;text-align:right;">' . number_format($rows['Sp'],2) . '</td>';
         $html .= '<td style="padding:6px;border:1px solid #ccc;text-align:right;">' . number_format($rows['lineDiscount'] ?? 0,2) . '%</td>';
         $html .= '<td style="padding:6px;border:1px solid #ccc;text-align:right;">' . number_format($rows['vatrate'],0) . '%</td>';
         $html .= '<td style="padding:6px;border:1px solid #ccc;text-align:right;">' . number_format($rows['vatamount'],2) . '</td>';
         $html .= '<td style="padding:6px;border:1px solid #ccc;text-align:right;">' . number_format($rows['netamt'],2) . '</td>';
         $html .= '</tr>';
     }
     $html .= '<tr><td colspan="7" style="padding:6px;border:1px solid #ccc;text-align:right;">Subtotal</td><td style="padding:6px;border:1px solid #ccc;text-align:right;">' . number_format($R2 + $totalDiscount,2) . '</td></tr>';
     $html .= '<tr><td colspan="7" style="padding:6px;border:1px solid #ccc;text-align:right;">Less Discount</td><td style="padding:6px;border:1px solid #ccc;text-align:right;">-' . number_format($totalDiscount,2) . '</td></tr>';
     $html .= '<tr><td colspan="7" style="padding:6px;border:1px solid #ccc;text-align:right;font-weight:bold;">Net Total</td><td style="padding:6px;border:1px solid #ccc;text-align:right;font-weight:bold;">' . number_format($R2,2) . '</td></tr>';
     $html .= '<tr><td colspan="7" style="padding:6px;border:1px solid #ccc;text-align:right;">VAT Total</td><td style="padding:6px;border:1px solid #ccc;text-align:right;">' . number_format($R1,2) . '</td></tr>';
     $html .= '<tr><td colspan="7" style="padding:6px;border:1px solid #ccc;text-align:right;font-weight:bold;">Grand Total</td><td style="padding:6px;border:1px solid #ccc;text-align:right;font-weight:bold;">' . number_format($R3,2) . '</td></tr>';
     $html .= '</table>';

     if(mb_strlen($containerNote) > 0){
         $html .= '<div style="margin-top:12px;font-size:9pt;color:#555;"><strong>NOTE:</strong> ' . htmlspecialchars($containerNote) . ' are charged separately.</div>';
     }

     $html .= '</div>';
     $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->OutputI($_SESSION['DatabaseName'] . '_ORDER_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    
    DB_query("Update `SalesHeader` set printed=1 where SalesHeader.documenttype=1 and SalesHeader.documentno='".$_GET['No']."'", $db);
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($_GET['unprintedonly'])){
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
      where `SalesHeader`.`documenttype`='1' and (`SalesHeader`.`printed` is null or `SalesHeader`.`printed`=0)
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
      `SalesHeader`.`docdate` desc";
}else{
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
      where `SalesHeader`.`documenttype`='1' 
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
}
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
              . '<th>Pictures</th>'
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
        echo sprintf('<td>%s</td>',getiamge($row['documentno']));
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   

function getiamge($ref){
    global $host,$database,$DBUser,$DBPassword;
//   $dbimage = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;",trim($DBUser),trim($DBPassword));
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
?>