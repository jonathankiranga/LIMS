<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php');
$Title = _('Print Picking List');
$SELFPAGE = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_GET['No'])){
    
$SQL="select 
       `SalesHeader`.`yourreference`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid`
      ,`debtors`.email
      ,`debtors`.city
      ,`debtors`.postcode
      ,`debtors`.country
      ,`debtors`.phone
      ,`debtors`.contact
      ,`SalesHeader`.`documentno` as jobcard
      ,`SalesHeader`.`picture` 
      from `SalesHeader`
      join `SalesLine` on `SalesHeader`.`documentno`=`SalesLine`.`documentno`
      join `debtors` on `SalesHeader`.`customercode`=`debtors`.`itemcode`
      where `SalesHeader`.`documentno`='".$_GET['No']."' GROUP BY SalesLine.documentno, SalesLine.SampleID";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $urlString=$myrow[17];
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $pdf->addInfo('Title',_('Picking List'));
    $pdf->addInfo('Subject',_('Picking List'));
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
    $companyAddress2 = htmlspecialchars(trim($_SESSION['CompanyRecord']['regoffice2'] ?? ''), ENT_QUOTES, 'UTF-8');
    $companyAddress3 = htmlspecialchars(trim($_SESSION['CompanyRecord']['regoffice3'] ?? ''), ENT_QUOTES, 'UTF-8');
    $companyTelephone = htmlspecialchars($_SESSION['CompanyRecord']['telephone'] ?? '', ENT_QUOTES, 'UTF-8');
    $companyEmail = htmlspecialchars(trim($_SESSION['CompanyRecord']['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $companyVAT = htmlspecialchars($_SESSION['CompanyRecord']['vat'] ?? '', ENT_QUOTES, 'UTF-8');
    
    $SQL=sprintf("SELECT 
        stockmaster.barcode,
        SalesLine.`description`,
        `SalesLine`.Quantity,
        `SalesLine`.unitofmeasure as UOMDesc,
        `SalesLine`.UnitPrice as Sp
    FROM `SalesHeader` 
        join SalesLine on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        join stockmaster on stockmaster.itemcode=SalesLine.code
        where SalesHeader.documentno='%s' and SalesHeader.documenttype=19 " ,$_GET['No']);
     
    $Results=DB_query($SQL,$db);
    $lineItems = [];
    while($rows = DB_fetch_array($Results)){
        $lineItems[] = $rows;
    }
    
    $html = '<div style="font-family:helvetica;font-size:9pt;color:#333;margin-bottom:15px;">';
    
    // Title
    $html .= '<div style="text-align:center;margin-bottom:15px;">';
    $html .= '<div style="font-size:14pt;font-weight:bold;color:#2c3e50;">PICKING LIST</div>';
    $html .= '<div style="font-size:10pt;">No: ' . htmlspecialchars($_GET['No']) . ' | Date: ' . ConvertSQLDate($myrow[1]) . ' | For Sales Order: ' . htmlspecialchars($myrow[0]) . '</div>';
    $html .= '</div>';
    
    // Two-column header layout (Customer left, Company right)
    $html .= '<table style="width:100%;border-collapse:collapse;border:1px solid #999;margin-bottom:15px;">';
    $html .= '<tr>';
    
    // Left Column - Customer Details
    $html .= '<td style="width:50%;vertical-align:top;padding:10px;border-right:1px solid #999;">';
    $html .= '<div style="font-weight:bold;padding:6px;margin-bottom:8px;text-align:center;">CUSTOMER DETAILS</div>';
    $html .= '<strong>' . htmlspecialchars($myrow[5]) . '</strong><br>';
    $html .= '<div style="font-size:8pt;margin-top:4px;">';
    $html .= 'Contact: ' . htmlspecialchars($myrow[15]) . '<br>';
    $html .= 'Phone: ' . htmlspecialchars($myrow[14]) . '<br>';
    $html .= 'City: ' . htmlspecialchars($myrow[11]) . '<br>';
    $html .= 'Country: ' . htmlspecialchars($myrow[13]) . '<br>';
    $html .= '</div>';
    $html .= '</td>';
    
    // Right Column - Company Details
    $html .= '<td style="width:50%;vertical-align:top;padding:10px;">';
    $html .= '<div style="font-weight:bold;padding:4px;margin-bottom:6px;text-align:center;">COMPANY DETAILS</div>';
    if($logoPath){
        $html .= '<div style="text-align:left;margin-bottom:10px;">';
        $html .= '<img src="' . htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') . '" height="28">';
        $html .= '</div>';
    }
    $html .= '<strong>' . $companyName . '</strong><br>';
    $html .= '<div style="font-size:8pt;margin-top:2px;margin-bottom:8px;">';
    $html .= $companyAddress1 . '<br>';
    $html .= $companyAddress2 . '<br>';
    $html .= 'Tel: ' . $companyTelephone . '<br>';
    $html .= 'VAT: ' . $companyVAT . '<br>';
    $html .= 'Email: ' . $companyEmail . '<br>';
    $html .= '</div>';
    $html .= '</td>';
    
    $html .= '</tr></table>';
    
    // Line items table
    $html .= '<table style="width:100%;border-collapse:collapse;font-size:9pt;">
        <thead>
          <tr style="background-color:#2c3e50;color:#ffffff;">
            <th style="padding:6px;border:1px solid #555;text-align:left;">Barcode</th>
            <th style="padding:6px;border:1px solid #555;text-align:left;">Description</th>
            <th style="padding:6px;border:1px solid #555;text-align:right;">UOM</th>
            <th style="padding:6px;border:1px solid #555;text-align:right;">Quantity</th>
          </tr>
        </thead>
        <tbody>';
    
    foreach($lineItems as $rows){
        $html .= '<tr style="border-bottom:1px solid #eee;">';
        $html .= '<td style="padding:6px;border:1px solid #ccc;">' . htmlspecialchars($rows['barcode']) . '</td>';
        $html .= '<td style="padding:6px;border:1px solid #ccc;">' . htmlspecialchars($rows['description']) . '</td>';
        $html .= '<td style="padding:6px;border:1px solid #ccc;text-align:right;">' . htmlspecialchars($rows['UOMDesc']) . '</td>';
        $html .= '<td style="padding:6px;border:1px solid #ccc;text-align:right;">' . number_format($rows['Quantity'],0) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Driver and Delivery section
    $html .= '<table style="width:100%;border-collapse:collapse;font-size:9pt;margin-top:30px;">
        <tr>
            <td style="padding:12px;border:1px solid #ccc;width:33%;">
                <strong>Name of Driver:</strong><br><br>
                <span style="border-bottom:1px solid #000;display:block;height:1em;">&nbsp;</span>
            </td>
            <td style="padding:12px;border:1px solid #ccc;width:33%;">
                <strong>ID No:</strong><br><br>
                <span style="border-bottom:1px solid #000;display:block;height:1em;">&nbsp;</span>
            </td>
            <td style="padding:12px;border:1px solid #ccc;width:33%;">
                <strong>Mobile:</strong><br><br>
                <span style="border-bottom:1px solid #000;display:block;height:1em;">&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td style="padding:12px;border:1px solid #ccc;">
                <strong>Vehicle REG:</strong><br><br>
                <span style="border-bottom:1px solid #000;display:block;height:1em;">&nbsp;</span>
            </td>
            <td style="padding:12px;border:1px solid #ccc;">
                <strong>Sign:</strong><br><br>
                <span style="border-bottom:1px solid #000;display:block;height:1em;">&nbsp;</span>
            </td>
            <td style="padding:12px;border:1px solid #ccc;"></td>
        </tr>
        <tr>
            <td style="padding:12px;border:1px solid #ccc;">
                <strong>Received By:</strong><br><br>
                <span style="border-bottom:1px solid #000;display:block;height:1em;">&nbsp;</span>
            </td>
            <td style="padding:12px;border:1px solid #ccc;">
                <strong>Sign:</strong><br><br>
                <span style="border-bottom:1px solid #000;display:block;height:1em;">&nbsp;</span>
            </td>
            <td style="padding:12px;border:1px solid #ccc;"></td>
        </tr>
    </table>';
    
    // Romalpa Clause
    if(isset($_SESSION['RomalpaClause'])){
        $html .= '<div style="margin-top:20px;font-size:8pt;color:#666;border:1px solid #ccc;padding:10px;">';
        $html .= html_entity_decode($_SESSION['RomalpaClause']);
        $html .= '</div>';
    }
    
    // Images
    $urls = json_decode($urlString, true);
    if (is_array($urls) && count($urls) > 0) {
        $html .= '<div style="margin-top:20px;text-align:center;">';
        foreach ($urls as $url) {
            $validUrl = str_replace(['[', ']', '&quot;'], '', urldecode(htmlspecialchars_decode($url)));
            $html .= '<img src="' . htmlspecialchars($validUrl) . '" style="max-width:160px;margin:5px;">';
        }
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Pickinglist_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.$SELFPAGE.'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

$SQL="SELECT 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`yourreference`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid` 
      ,`SalesHeader`.`picture` 
      from `SalesHeader` 
      where `SalesHeader`.`documenttype`='19' 
      order by `SalesHeader`.`documentno` desc limit 50";
    $Result=DB_query($SQL,$db);
       
    Echo '<table class="table-condensed table-responsive-small table-bordered"><tr>'
             . '<td>Pick List No</td>'
             . '<td>Sales Order No</td>' 
             . '<td>Pick on date</td>'
             . '<td>Customer ID</td>'
             . '<td>Customer Name</td>'
             . '<td>Sales Person</td>'
             . '<td>Created By</td>'
          . '<td></td>'
            . '</tr>';
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',$SELFPAGE,$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',$row['yourreference']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',getSalemanDescrip($row['salespersoncode']));
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
   $dbimage = new mysqli($host, $DBUser, $DBPassword, $database);
  
    $SQL=sprintf("SELECT `picture` FROM `SalesHeader` where documentno='%s'",trim($ref));
     $Result=DB_query($SQL,$dbimage);
      $myrow=DB_fetch_row($Result);
    $urlString=$myrow[0];
     $images="";
          $urls = json_decode($urlString,TRUE);
        if (is_array($urls) && count($urls) > 0) {
            foreach ($urls as $url) {
                 $validUrl = htmlspecialchars($url,ENT_QUOTES,'utf-8');
                 $validUrl = urldecode($url);
                 $validUrl = str_replace('[', '' ,$validUrl);
                 $validUrl = str_replace( ']', '' ,$validUrl);
                 $validUrl = str_replace( '&quot;', '' ,$validUrl);
                 $images .= sprintf('<img src="%s" width="50" alt="Image">',$validUrl);
            }
           
        } 
        
        return $images;
}
?>