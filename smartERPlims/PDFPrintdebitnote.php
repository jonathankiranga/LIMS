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
      ,`creditors`.`email`
      ,`creditors`.`city`
      ,`creditors`.`postcode`
      ,`creditors`.`country`
      ,`creditors`.`phone`
      ,`creditors`.`contact`
      ,PurchaseHeader.`externaldocumentno`
     from `PurchaseHeader` 
     join PurchaseLine on `PurchaseHeader`.`documentno`=PurchaseLine.`documentno` 
     join `creditors` on `PurchaseHeader`.`vendorcode`=`creditors`.`itemcode`
     where `PurchaseHeader`.`documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="DEBIT NOTE";
    
    $pdf->addInfo('Title',_('Purchase Order'));
    $pdf->addInfo('Subject',_('Purchase Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFdebittheader.inc');
     
     
     $SQL=Sprintf("SELECT 
         stockmaster.barcode ,
         PurchaseHeader.docdate ,
         PurchaseLine.`description` ,
        `PurchaseLine`.Quantity,
        `PurchaseLine`.partperunit,
        (PurchaseLine.UnitPrice * `PurchaseLine`.partperunit)  as Sp , 
        `PurchaseLine`.unitofmeasure as UOMDesc ,
        PurchaseHeader.`documentno` ,
        PurchaseHeader.`vendorcode` ,
        PurchaseHeader.`externaldocumentno` 
        FROM `PurchaseHeader` 
        join PurchaseLine on PurchaseHeader.documentno=PurchaseLine.documentno 
        join stockmaster on stockmaster.itemcode=PurchaseLine.code
        where PurchaseHeader.`documentno`='%s' and PurchaseHeader.`documenttype`='21' ",$_GET['No']);
     
     $FontSize = 12;
     $YPos=$firstrowpos-20;
      
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize,  number_format( abs($rows['Quantity']),0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos<$Bottom_Margin+100){
             $PageNumber++;
             include('includes/PDFdebittheader.inc');
              $YPos=$firstrowpos;
         }
                
     }
     
     $YPos=$Bottom_Margin+70;
     
     $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,_('Received by (Name) :'),'left');
     $YPos-= $line_height;
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
     $LeftOvers = $pdf->addTextWrap(42,$YPos -= $line_height * 3,250, $FontSize,_('Designation:'),'left');
     
     $YPos=$Bottom_Margin+70;
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Date:'),'right');
     $LeftOvers = $pdf->addTextWrap(145,$YPos -= $line_height * 4,250, $FontSize,_('Sign:'),'right');
          
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_DebitNote_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


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
      where `PurchaseHeader`.`documenttype`='20' 
      order by `PurchaseHeader`.`docdate` desc limit  ". $_SESSION['DefaultDisplayRecordsMax'] ."";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Debit <br />Note</th>'
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