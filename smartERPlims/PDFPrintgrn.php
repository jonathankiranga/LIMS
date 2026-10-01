<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Picking List');

if(isset($_GET['No'])){
    
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
      ,`creditors`.email
      ,`creditors`.city
      ,`creditors`.postcode
      ,`creditors`.country
      ,`creditors`.phone
      ,`creditors`.contact
      ,stockledger.jobcard
      from `stockledger` 
      join `PurchaseLine` on `stockledger`.`invref`=`PurchaseLine`.`documentno`
      join `PurchaseHeader` on `PurchaseHeader`.`documentno`=PurchaseLine.`documentno` 
      join `creditors` on `PurchaseHeader`.`vendorcode`=`creditors`.`itemcode`
      where `stockledger`.`jobcard`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Goods Received Note";
    
    $pdf->addInfo('Title',_('Purchase Order'));
    $pdf->addInfo('Subject',_('Purchase Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFgrntheader.inc');
     
     
     $SQL=Sprintf("SELECT 
         stockmaster.barcode ,
         PurchaseHeader.docdate ,
         PurchaseLine.`description` ,
        (case `PurchaseLine`.UOM when 'fulqty' then (select stockledger.fulqty)  else (select stockledger.loosqty)  end) as Quantity,
        `PurchaseLine`.UOM,
        (case `PurchaseLine`.UOM when 'fulqty'  then (select PurchaseLine.UnitPrice*stockmaster.partperunit from stockmaster where stockmaster.itemcode=PurchaseLine.code)  else (PurchaseLine.UnitPrice) end) as Sp , 
        (case `PurchaseLine`.UOM when 'fulqty' then (select rtrim(`unit`.`descrip`) from `unit` join stockmaster on stockmaster.units=`unit`.code   
        and stockmaster.itemcode=PurchaseLine.code) else (select rtrim(`unit`.`descrip`)  from `unit` join stockmaster on `stockmaster`.`units`=`unit`.code and stockmaster.itemcode=PurchaseLine.code) end) as UOMDesc ,
        stockledger.jobcard as `documentno` ,
        PurchaseHeader.`vendorcode` ,
        PurchaseHeader.`documenttype` 
        FROM `PurchaseHeader` join PurchaseLine 
        on PurchaseHeader.documentno=PurchaseLine.documentno and PurchaseHeader.documenttype=PurchaseLine.documenttype and PurchaseHeader.documenttype=18 
        join stockmaster on stockmaster.itemcode=PurchaseLine.code join stockledger on stockledger.invref=PurchaseLine.documentno 
        and stockledger.itemcode=PurchaseLine.code
        where stockledger.jobcard='%s' ",$_GET['No']);
     
     $FontSize = 12;
     $YPos=$firstrowpos-20;
      
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize,  number_format( $rows['Quantity'],0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos<$Bottom_Margin+200){
             $PageNumber++;
             include('includes/PDFgrntheader.inc');
              $YPos=$firstrowpos;
         }
                
     }
     
     $YPos=$Bottom_Margin+200;
     
     
    foreach ($myGRNSIGNATURES as $value) {
        $pdf->addText($XPos,$lastrow -= ($line_height*2.2), $FontSize ,$value);
    }
      
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_GRN_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


echo '<div class="container"><table class="table table-bordered"><tr>'
    . '<td><input type="button" id="searchvendor" value="Search for Vendor"/>'
    . '<input type="hidden" name="VendorID" id="VendorID"/>'
    . '<input type="hidden" name="currencycode" id="currencycode"/></td>'
    . '<td>Supplier Name</td><td><input tabindex="5" type="text" name="VendorName" id="VendorName"/>'
    . '<input type="submit" value="GO"/></td></tr></table></div>';

$SQL="Select
       stockledger.jobcard
      ,`PurchaseHeader`.`documentno`
      ,`PurchaseHeader`.`docdate`
      ,`PurchaseHeader`.`oderdate`
      ,`PurchaseHeader`.`duedate`
      ,`PurchaseHeader`.`vendorcode`
      ,`PurchaseHeader`.`vendorname`
      ,`PurchaseHeader`.`currencycode`
      ,`PurchaseHeader`.`status`
      ,`PurchaseHeader`.`userid` 
      from stockledger join PurchaseLine on 
      stockledger.invref=PurchaseLine.`documentno` 
      join `PurchaseHeader` on `PurchaseHeader`.documentno=PurchaseLine.`documentno` 
      where `PurchaseHeader`.`documenttype`='18' ".
      (isset($_POST['VendorID'])?" and `PurchaseHeader`.`vendorcode`='".$_POST['VendorID']."'":'')."
      order by  `PurchaseHeader`.`docdate` desc 
      limit ". $_SESSION['DefaultDisplayRecordsMax'] ."";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>GR <br />Note</th>'
             . '<th>Purchase Order<br />No</th>' 
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
             . '<th>Vendor <br />ID</th>'
             . '<th>Vendor<br /> Name</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
          
            . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['jobcard'],$row['jobcard']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
                  
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>