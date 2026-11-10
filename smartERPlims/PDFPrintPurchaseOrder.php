<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Purchase Order');

if(isset($_GET['No'])){
    
$DocumentNo = DB_escape_string($_GET['No']);
$SQL = "SELECT
       PurchaseHeader.documentno
      ,PurchaseHeader.docdate
      ,PurchaseHeader.oderdate
      ,PurchaseHeader.duedate
      ,PurchaseHeader.vendorcode
      ,PurchaseHeader.vendorname
      ,PurchaseHeader.currencycode
      ,PurchaseHeader.status
      ,PurchaseHeader.userid
      ,creditors.email AS supplier_email
      ,creditors.city AS supplier_city
      ,creditors.postcode AS supplier_postcode
      ,creditors.country AS supplier_country
      ,creditors.phone AS supplier_phone
      ,creditors.contact AS supplier_contact
      ,PurchaseHeader.yourreference
      ,PurchaseHeader.printed
      ,PurchaseHeader.documenttype
      FROM PurchaseHeader
      LEFT JOIN creditors ON PurchaseHeader.vendorcode=creditors.itemcode
      WHERE PurchaseHeader.documenttype IN ('18','41')
        AND PurchaseHeader.documentno='".$DocumentNo."'";

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
    $headerName="Purchase Order";
    
    $pdf->addInfo('Title',_('Purchase Order'));
    $pdf->addInfo('Subject',_('Purchase Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFpurchaseheader.inc');
     
     
     $SQL = "SELECT
       COALESCE(NULLIF(stockmaster.barcode,''), PurchaseLine.code) AS barcode
      ,PurchaseLine.description
      ,PurchaseLine.Quantity
      ,PurchaseLine.PartPerUnit
      ,PurchaseLine.UnitPrice AS Sp
      ,PurchaseLine.discount AS Discount
      ,PurchaseLine.unitofmeasure AS UOMDesc
      ,PurchaseLine.invoiceamount
      ,PurchaseLine.vatamount
      ,(PurchaseLine.invoiceamount - PurchaseLine.vatamount) AS netamt
      ,PurchaseLine.vatrate
      FROM PurchaseHeader
      INNER JOIN PurchaseLine
        ON PurchaseHeader.documentno=PurchaseLine.documentno
       AND PurchaseHeader.documenttype=PurchaseLine.documenttype
      LEFT JOIN stockmaster
        ON stockmaster.itemcode=PurchaseLine.code
      WHERE PurchaseHeader.documenttype='".DB_escape_string($myrow['documenttype'])."'
        AND PurchaseHeader.documentno='".$DocumentNo."'
      ORDER BY PurchaseLine.code";

     
     $FontSize = 12;
     $YPos=$firstrowpos-20;
     $R1=0; // VAT amount
     $R2=0; // Subtotal before discount
     $R3=0; // Total invoice amount
     $R4=0; // Discount amount
     
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
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
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,103, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(300, $YPos, 85, $FontSize, $units,'right');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $QTY,'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, number_format($PRICE,2),'right');
          $LeftOvers = $pdf->addTextWrap(492, $YPos, 85, $FontSize, number_format($discountRate,2).'%','right');
          $LeftOvers = $pdf->addTextWrap(590, $YPos, 55, $FontSize, number_format($rows['vatrate'],0),'right');
          $LeftOvers = $pdf->addTextWrap(650, $YPos, 70, $FontSize, number_format($rows['vatamount'],2),'right');
          $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$YPos,90,$FontSize,number_format($lineInvoiceAmount,2),'right');

         $YPos -= $line_height * 2;
         if($YPos < $lastrow){
             include('includes/PDFpurchaseheader.inc');
             $YPos=$firstrowpos-20;
         }
               
        }
     
           
    $zYpos = $Bottom_Margin+80;
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
    
    DB_query("UPDATE PurchaseHeader
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
       `PurchaseHeader`.`documentno`
      ,`PurchaseHeader`.`docdate`
      ,`PurchaseHeader`.`oderdate`
      ,`PurchaseHeader`.`duedate`
      ,`PurchaseHeader`.`vendorcode`
      ,`PurchaseHeader`.`vendorname`
      ,`PurchaseHeader`.`currencycode`
      ,`PurchaseHeader`.`status`
      ,`PurchaseHeader`.`userid` 
      ,sum(PurchaseLine.`invoiceamount`) as OrderValue
      ,`PurchaseHeader`.printed
      from `PurchaseHeader` join PurchaseLine on 
      `PurchaseHeader`.`documentno`=PurchaseLine.`documentno`
       and `PurchaseHeader`.`documenttype`=PurchaseLine.`documenttype`
       and PurchaseLine.documenttype=18
      where `PurchaseHeader`.`documenttype`='18' ".
        (isset($_POST['VendorID'])?" and `PurchaseHeader`.`vendorcode`='".$_POST['VendorID']."'":'')."
       group by 
       `PurchaseHeader`.`documentno`
      ,`PurchaseHeader`.`docdate`
      ,`PurchaseHeader`.`oderdate`
      ,`PurchaseHeader`.`duedate`
      ,`PurchaseHeader`.`vendorcode`
      ,`PurchaseHeader`.`vendorname`
      ,`PurchaseHeader`.`currencycode`
      ,`PurchaseHeader`.`status`
      ,`PurchaseHeader`.`userid`  
      ,`PurchaseHeader`.printed 
      order by `PurchaseHeader`.`docdate` desc
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