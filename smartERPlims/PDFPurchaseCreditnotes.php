<?php 
$PageSecurity=0;

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Purchases Returns VAT');

if(isset($_POST['salesreport'])){
       
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Sales Invoice'));
    $pdf->addInfo('Subject',_('Sales Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
     Global $PageNumber;
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
    
    $castdate  = FormatDateForSQL($_POST['fromdate']);
    $Tcastdate = FormatDateForSQL($_POST['todate']);
     
    if(mb_strlen($Tcastdate)>=8){ } else {$Tcastdate=$castdate;}
     
    $R1=0; $R2=0; $R3=0; $Visa=0; $ledger=0; $mpesa=0; $cash=0;
     
     salesHeader();
    
     $SQL=Sprintf("SELECT 
       `PurchaseHeader`.`documentno`
      ,`PurchaseHeader`.`docdate`
      ,`PurchaseHeader`.`status`
      ,`PurchaseHeader`.`vendorcode`
      ,`PurchaseHeader`.`vendorname`
      ,sum(PurchaseLine.invoiceamount) as invoiceamount
      ,sum(PurchaseLine.vatamount) as vatamount
      ,sum(PurchaseLine.invoiceamount-PurchaseLine.vatamount) as netamt
  FROM `PurchaseHeader` join PurchaseLine 
        on PurchaseHeader.documentno=PurchaseLine.documentno 
        and PurchaseHeader.documenttype=PurchaseLine.documenttype
        and PurchaseHeader.documenttype=24
        where PurchaseHeader.`docdate` between '%s' and '%s' 
        group by 
       `PurchaseHeader`.`documentno`
      ,`PurchaseHeader`.`docdate`
      ,`PurchaseHeader`.`status`
      ,`PurchaseHeader`.`vendorcode`
      ,`PurchaseHeader`.`vendorname`",$castdate,$Tcastdate);
     
     $FontSize =10; $YPos = $firstrowpos; 
     
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $R1 +=$rows['vatamount'];
         $R2 +=$rows['netamt'];
         $R3 +=$rows['invoiceamount'];
         $custArray = GetCustomerdetails($rows['vendorcode']);
      
          
          $LeftOvers = $pdf->addTextWrap(42, $YPos,85, $FontSize, $custArray['PIN'] ,'left');
          $NameLeftOvers = $pdf->addTextWrap(140,$YPos,100, $FontSize, $custArray['Name'],'left');
          $LeftOvers = $pdf->addTextWrap(240,$YPos,50, $FontSize, ConvertSQLDate($rows['docdate']),'right');
          $LeftOvers = $pdf->addTextWrap(290,$YPos,50, $FontSize, $rows['documentno'],'right');
          $LeftOvers = $pdf->addTextWrap(340,$YPos,85, $FontSize, number_format($rows['netamt'],2),'right');
          $LeftOvers = $pdf->addTextWrap(430,$YPos,85, $FontSize, number_format($rows['vatamount'],0),'right');
       
          if (strlen($NameLeftOvers) > 0) { // If translated text is greater than 103, prints remainder
		$YPos-=$line_height ;
                $NameLeftOvers = $pdf->addTextWrap(140,$YPos,200, $FontSize,$NameLeftOvers,'left');
           }
         $YPos -= $line_height * 2;
         if($YPos < $Bottom_Margin){
             $PageNumber++;
             salesHeader();
             $YPos = $firstrowpos;
         }
       
     }
            
     $YPos -= $line_height ;
     if($YPos < ($Bottom_Margin + ($line_height * 4))){
        $PageNumber++;
        salesHeader();
        $YPos = $firstrowpos;
     }
     
      $LeftOvers = $pdf->addTextWrap(240, $YPos,150, $FontSize, _('Total VAT'),'right');
     $LeftOvers = $pdf->addTextWrap(485, $YPos, 85, $FontSize, number_format($R1 ,2),'right');
     $YPos -= $line_height ;
    
     $LeftOvers = $pdf->addTextWrap(240, $YPos,150, $FontSize, _('NET Purchaaes'),'right');
     $LeftOvers = $pdf->addTextWrap(485, $YPos, 85, $FontSize, number_format($R2,2),'right');
     
    $pdf->OutputD('PURCHASESVAT' . '_REport_' . $castdate . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
} else {

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered"><tr>'
  . '<td colspan="2">Reporting Period:'
          . '<table class="table table-bordered"><tr><td></td></tr>'
          . '<tr>'
          . '<td>FROM :</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="fromdate" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['fromdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>'
        . '</tr>'
        . '<tr>'
          . '<td>TO :</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="todate" size="11" maxlength="10"  required="required" value="' .$_POST['todate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>'
        . '</tr>'
          . '</table>';
  echo '</td></tr>';
  
  echo '<tr><td colspan="2"><input type="submit" name="salesreport" value="Purchases VAT Report"/></td></tr>'
  . '</table>';
  echo '</div></form>';
  
include('includes/footer.inc');

}   



function salesHeader(){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin,$Right_Margin,$firstrowpos,$PageNumber;
    Global $line_height,$Bottom_Margin,$FontSize,$XPos,$R1,$R2,$R3,$PageNumber,$castdate,$Tcastdate;
    
    $PageNumber++;
      if ($PageNumber>1) {
   $pdf->newPage();
}
    $XPos = 46;
    $YPos = ($Page_Height-$Top_Margin-$FontSize * 3);
    $FontSize =10;
    $H=$Page_Height-$Top_Margin;

    $pdf->addText(200,$H, $FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
    $pdf->addText(200, $H-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
    $pdf->addText(200, $H-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
    $pdf->addText(200, $H-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
    $pdf->addText(200, $H-39, $FontSize, _('Phone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
    $pdf->addText(200, $H-48, $FontSize, $_SESSION['CompanyRecord']['email']);

    $YPos = ($H-$line_height-60);
    $pdf->addText(45,$YPos,10, _('Page').': '.$PageNumber);
    $YPos -= $line_height;

     $reportdate  = FormatDateForSQL($_POST['fromdate']);
     $Treportdate = FormatDateForSQL($_POST['todate']);
    
    if(mb_strlen($Treportdate)>=8){ }else{ $Treportdate=$reportdate;}
     
    $pdf->addText(240,$YPos,10,_('Purchase Returns VAT Report ').':'.$reportdate.' to '. $Treportdate);        
    $YPos -= $line_height * 2;

    $LeftOvers = $pdf->addTextWrap(45,$YPos,80, $FontSize,_('PIN No.'),'left');
    $LeftOvers = $pdf->addTextWrap(140,$YPos,100, $FontSize,_('NAME'),'left');
    $LeftOvers = $pdf->addTextWrap(240,$YPos,100, $FontSize,_('DATE'),'left');
    $LeftOvers = $pdf->addTextWrap(290,$YPos,100, $FontSize,_('INVOICE'),'left');
    $LeftOvers = $pdf->addTextWrap(340,$YPos,100, $FontSize,_('Exc Amount.'),'right');
    $LeftOvers = $pdf->addTextWrap(430,$YPos,100, $FontSize,_('VAT Amount'),'right');
   
    $YPos -= $line_height * 2;
    $FontSize = 10;
    $firstrowpos=$YPos;
}

function GetCustomerdetails($CustomerID){
    global $db;
    
    $sqldebtors=DB_query("SELECT `vatregno`,`customer`,`phone`,`email`,`city`,`country`,`curr_cod`,`supplierposting`,`VATinclusive`,`IsTaxed`
    FROM `creditors` join arpostinggroups on code=`supplierposting` where itemcode='".$CustomerID."'", $db);
    $debtorsrow = DB_fetch_row($sqldebtors);
   
    $name = $debtorsrow[1];
    $pin = $debtorsrow[0];
    
    return array('PIN'=>$pin,'Name'=>$name);
}