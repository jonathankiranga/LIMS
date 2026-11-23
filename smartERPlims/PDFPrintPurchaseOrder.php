<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Purchase Order');

if(isset($_GET['No'])){
    
$DocumentNo = DB_escape_string($_GET['No']);
$SQL = "SELECT
       purchaseheader.documentno
      ,purchaseheader.docdate
      ,purchaseheader.oderdate
      ,purchaseheader.duedate
      ,purchaseheader.vendorcode
      ,purchaseheader.vendorname
      ,purchaseheader.currencycode
      ,purchaseheader.status
      ,purchaseheader.userid
      ,creditors.email AS supplier_email
      ,creditors.city AS supplier_city
      ,creditors.postcode AS supplier_postcode
      ,creditors.country AS supplier_country
      ,creditors.phone AS supplier_phone
      ,creditors.contact AS supplier_contact
      ,purchaseheader.yourreference
      ,purchaseheader.printed
      ,purchaseheader.documenttype
      FROM purchaseheader
      LEFT JOIN creditors ON purchaseheader.vendorcode=creditors.itemcode
      WHERE purchaseheader.documenttype IN ('18','41')
        AND purchaseheader.documentno='".$DocumentNo."'";

    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_array($Result);
    if (!$myrow) {
        prnMsg(_('The requested purchase order could not be found.'), 'warn');
        include('includes/header.inc');
        include('includes/footer.inc');
        exit;
    }
    
    
    $PaperSize='A4_Landscape';
    include('includes/PDFStarter.php');
    // This report uses explicit top-origin PDF coordinates and manual row pagination.
    // Prevent TCPDF Cell() calls in addTextWrap() from inserting automatic page breaks.
    $pdf->SetAutoPageBreak(false, 0);
    $headerName="Purchase Order";
    
    $pdf->addInfo('Title',_('Purchase Order'));
    $pdf->addInfo('Subject',_('Purchase Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFpurchaseheader.inc');
     
     
     $SQL = "SELECT
       COALESCE(NULLIF(stockmaster.barcode,''), purchaseline.code) AS barcode
      ,purchaseline.description
      ,purchaseline.Quantity
      ,purchaseline.PartPerUnit
      ,purchaseline.UnitPrice AS Sp
      ,purchaseline.discount AS Discount
      ,purchaseline.unitofmeasure AS UOMDesc
      ,purchaseline.invoiceamount
      ,purchaseline.vatamount
      ,(purchaseline.invoiceamount - purchaseline.vatamount) AS netamt
      ,purchaseline.vatrate
      FROM purchaseheader
      INNER JOIN purchaseline
        ON purchaseheader.documentno=purchaseline.documentno
       AND purchaseheader.documenttype=purchaseline.documenttype
      LEFT JOIN stockmaster
        ON stockmaster.itemcode=purchaseline.code
      WHERE purchaseheader.documenttype='".DB_escape_string($myrow['documenttype'])."'
        AND purchaseheader.documentno='".$DocumentNo."'
      ORDER BY purchaseline.code";

     
     $FontSize = ((string)$myrow['documenttype'] === '18') ? 9 : 12;
     $YPos=$firstrowpos-20;
     $R1=0; // VAT amount
     $R2=0; // Subtotal before discount
     $R3=0; // Total invoice amount
     $R4=0; // Discount amount
     
     $Results=DB_query($SQL,$db);
     $purchaseRowIndex = 0;
     while($rows = DB_fetch_array($Results)){
         if($YPos < $lastrow){
             include('includes/PDFpurchaseheader.inc');
             $YPos = $firstrowpos - 20;
         }

         $lineQuantity = (float)$rows['Quantity'];
         $linePackage = (float)$rows['PartPerUnit'];
         $lineUnitPrice = (float)$rows['Sp'];
         $discountRate = (float)$rows['Discount'];
         $lineBaseAmount = $lineUnitPrice * $lineQuantity * $linePackage;
         $discountAmount = $lineBaseAmount * ($discountRate / 100);
         $lineNetAmount = (float)$rows['netamt'];
         $lineVatAmount = (float)$rows['vatamount'];
         $lineInvoiceAmount = (float)$rows['invoiceamount'];

         $R1 += $lineVatAmount;
         $R2 += ($lineNetAmount + $discountAmount);
         $R3 += $lineInvoiceAmount;
         $R4 += $discountAmount;
         
         if($linePackage>1){
           $packageLabel = rtrim(rtrim(number_format($linePackage,4,'.',''), '0'), '.');
           $units = '(1X'.$packageLabel.') '.$rows['UOMDesc'];
           $PRICE = $lineUnitPrice * $linePackage;
         }else{
            $units=$rows['UOMDesc'];
            $PRICE = $lineUnitPrice;
         }

         $QTY = rtrim(rtrim(number_format($lineQuantity,4,'.',','), '0'), '.');

         // Subtle alternating row shading and separators for the modern LPO.
         if ((string)$myrow['documenttype'] === '18') {
             if (($purchaseRowIndex % 2) === 1) {
                 $pdf->SetFillColor(247, 249, 252);
                 $pdf->Rect(42, $pdf->getPageHeight() - $YPos - 17, 758, 21, 'F');
             }
             $pdf->SetTextColor(55, 65, 81);
             $pdf->SetFont('', '', 9);
         }

          $LeftOvers = $pdf->addTextWrap(42, $YPos,95, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,145, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(300, $YPos, 75, $FontSize, $units,'right');
          $LeftOvers = $pdf->addTextWrap(380, $YPos, 55, $FontSize, $QTY,'right');
          $LeftOvers = $pdf->addTextWrap(440, $YPos, 70, $FontSize, number_format($PRICE,2),'right');
          $LeftOvers = $pdf->addTextWrap(515, $YPos, 50, $FontSize, number_format($discountRate,2).'%','right');
          $LeftOvers = $pdf->addTextWrap(570, $YPos, 55, $FontSize, number_format($rows['vatrate'],0),'right');
          $LeftOvers = $pdf->addTextWrap(630, $YPos, 75, $FontSize, number_format($rows['vatamount'],2),'right');
          $LeftOvers = $pdf->addTextWrap(720,$YPos,90,$FontSize,number_format($lineInvoiceAmount,2),'right');

         if ((string)$myrow['documenttype'] === '18') {
             $pdf->SetDrawColor(225, 230, 237);
             $pdf->SetLineWidth(0.35);
             $pdf->line(42, $YPos - 6, 800, $YPos - 6);
         }
         $purchaseRowIndex++;
         $YPos -= $line_height * 2;
               
        }
     
           
    $zYpos = $Bottom_Margin+80;
    if ((string)$myrow['documenttype'] === '18') {
        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('', '', 9);
        $FontSize = 9;
    }
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R2,2),'right');
    
    $zYpos -= $line_height *2;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R4,2),'right');
    
    $zYpos -= $line_height *2;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R1,2),'right');
    
    $zYpos -= $line_height *2 ;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R3,2),'right');

     $YPos= $Bottom_Margin+50;
     $YPos-= $line_height * 2;
     
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Date:'),'right');
     $pdf->line(400,$YPos,500,$YPos);
     
     $YPos-=  $line_height * 2;
    
     $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,_('Authorised By :').$_SESSION['UsersRealName'],'left');
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Sign:'),'right');
                   
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Purchase_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    DB_query("UPDATE purchaseheader
              SET printed=1
              WHERE documenttype='".DB_escape_string($myrow['documenttype'])."'
                AND documentno='".$DocumentNo."'", $db);
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
   . '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"  id="salesform">';
echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] .'"/>';

echo '<div class="container"><table class="table table-bordered"><tr>'
    . '<td><input type="button" id="searchvendor" value="Search for Vendor"/>'
    . '<input type="hidden" name="VendorID" id="VendorID"/>'
    . '<input type="hidden" name="currencycode" id="currencycode"/></td>'
    . '<td>Supplier Name</td><td><input tabindex="5" type="text" name="VendorName" id="VendorName"/>'
    . '<input type="submit" value="GO"/></td></tr></table></div>';

$SQL="select 
       `purchaseheader`.`documentno`
      ,`purchaseheader`.`docdate`
      ,`purchaseheader`.`oderdate`
      ,`purchaseheader`.`duedate`
      ,`purchaseheader`.`vendorcode`
      ,`purchaseheader`.`vendorname`
      ,`purchaseheader`.`currencycode`
      ,`purchaseheader`.`status`
      ,`purchaseheader`.`userid` 
      ,sum(purchaseline.`invoiceamount`) as OrderValue
      ,`purchaseheader`.printed
      from `purchaseheader` join purchaseline on 
      `purchaseheader`.`documentno`=purchaseline.`documentno`
       and `purchaseheader`.`documenttype`=purchaseline.`documenttype`
       and purchaseline.documenttype=18
      where `purchaseheader`.`documenttype`='18' ".
        (isset($_POST['VendorID'])?" and `purchaseheader`.`vendorcode`='".$_POST['VendorID']."'":'')."
       group by 
       `purchaseheader`.`documentno`
      ,`purchaseheader`.`docdate`
      ,`purchaseheader`.`oderdate`
      ,`purchaseheader`.`duedate`
      ,`purchaseheader`.`vendorcode`
      ,`purchaseheader`.`vendorname`
      ,`purchaseheader`.`currencycode`
      ,`purchaseheader`.`status`
      ,`purchaseheader`.`userid`  
      ,`purchaseheader`.printed 
      order by `purchaseheader`.`docdate` desc
     limit ". $_SESSION['DefaultDisplayRecordsMax'] ." ";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Receive<br />Order</th>' 
             . '<th>Print<br />Order</th>'  
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
             . '<th>Supplier <br />ID</th>'
             . '<th>Supplier<br /> Name</th>'
             . '<th>Purchase <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Print<br />Status</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
          
            . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?ref=%s">Receive :%s</a></td>',
        htmlspecialchars('Goodsrecievedfromorders.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
              
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['printed']==1?'Has Been Printed':'Not Yet Printed');
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
          
         
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>