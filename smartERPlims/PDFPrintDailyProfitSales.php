<?php 
$PageSecurity=0;

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Profitability Report');

if(isset($_POST['salesreport'])){
       
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Sales Invoice'));
    $pdf->addInfo('Subject',_('Sales Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
    
    $castdate  = trim($_POST['year']).'-'.trim($_POST['month']).'-'.trim($_POST['day']);
    $Tcastdate = trim($_POST['Tyear']).'-'.trim($_POST['Tmonth']).'-'.trim($_POST['Tday']);
     
    if(mb_strlen($Tcastdate)>=8){ } else {$Tcastdate=$castdate;}
     
    $R1=0; $R2=0; $R3=0; $Visa=0; $ledger=0; $mpesa=0; $cash=0;
     
     salesHeader();
    
     $SQL=Sprintf("SELECT 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`customercode`
      ,sum(`SalesLine`.`Quantity`) as Quantity
      ,`stockmaster`.`descrip`
      ,sum(`SalesLine`.`invoiceamount`) as Sales
      ,sum(`stockledger`.`stockvalue`) as cost
  FROM `SalesHeader` join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype
        and (SalesHeader.documenttype=12 or SalesHeader.documenttype=1)
        join `stockmaster` on `stockmaster`.itemcode=SalesLine.code
        join `stockledger` 
        on `stockledger`.itemcode=SalesLine.code 
        and `stockledger`.`invref`=SalesLine.documentno
        where SalesHeader.`docdate` 
        between cast('%s 00:00:00' as smalldatetime) 
        and cast('%s 23:59:59' as smalldatetime) 
        group by `SalesHeader`.`documentno`
      ,`stockmaster`.`descrip`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`customercode`",$castdate,$Tcastdate);
     
     $FontSize =10; $YPos = $firstrowpos; 
     
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $R1 +=$rows['Sales'];
         $R2 +=$rows['cost'];
         $R3 +=($rows['Sales']-$rows['cost']) ;
          
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,85, $FontSize, ConvertSQLDate($rows['docdate']),'left');
          $LeftOvers = $pdf->addTextWrap(90, $YPos,85, $FontSize, $rows['documentno'],'left');
          $LeftOvers = $pdf->addTextWrap(140,$YPos,100, $FontSize, $rows['Quantity'],'right');
          $LeftOvers = $pdf->addTextWrap(240,$YPos,85, $FontSize, $rows['descrip'],'right');
          $LeftOvers = $pdf->addTextWrap(315,$YPos,85, $FontSize, number_format($rows['cost'],2),'right');
          $LeftOvers = $pdf->addTextWrap(400,$YPos,85, $FontSize, number_format($rows['Sales'],2),'right');
          $LeftOvers = $pdf->addTextWrap(485,$YPos,85, $FontSize, number_format($R1-$R2,2),'right');
       
         $YPos -= $line_height ;
         if($YPos < $Bottom_Margin){
             $PageNumber++;
             salesHeader();
             $YPos = $firstrowpos;
         }
       
     }
            
      $YPos -= $line_height * 2 ;
        if($YPos < $Bottom_Margin){
             $PageNumber++;
             salesHeader();
             $YPos = $firstrowpos;
         }
         
          $LeftOvers = $pdf->addTextWrap(240,$YPos,85, $FontSize, _('Totals'),'right');
          $LeftOvers = $pdf->addTextWrap(315,$YPos,85, $FontSize, number_format($R2,2),'right');
          $LeftOvers = $pdf->addTextWrap(400,$YPos,85, $FontSize, number_format($R1,2),'right');
          $LeftOvers = $pdf->addTextWrap(485,$YPos,85, $FontSize, number_format($R1-$R2,2),'right');
     
    $pdf->OutputD($_SESSION['DatabaseName'] . '_ProfitREport_' . $castdate . '_' . date('Y-m-d').'.pdf');
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
          . '<table class="table table-bordered"><tr><td></td><td>Date</td><td>Month</td><td>Year</td></tr>'
          . '<tr>'
          . '<td>FROM :</td><td><input type="text" maxlength="2" size="4" class="integer" name="day" required="required"/></td>'
          . '<td><input type="text" maxlength="2" size="4" class="integer" name="month" required="required"/></td>'
          . '<td><input type="text" maxlength="4" size="6" class="integer" name="year" required="required"/></td>'
        . '</tr>'
        . '<tr>'
          . '<td>TO :</td><td><input type="text" maxlength="2" size="4" class="integer" name="Tday" /></td>'
          . '<td><input type="text" maxlength="2" size="4" class="integer" name="Tmonth" /></td>'
          . '<td><input type="text" maxlength="4" size="6" class="integer" name="Tyear" /></td>'
        . '</tr>'
          . '</table>';
  echo '</td></tr>';
  
  echo '<tr><td colspan="2"><input type="submit" name="salesreport" value="Print Profitability Report"/></td></tr>'
  . '</table>';
  echo '</div></form>';
  
include('includes/footer.inc');

}   



function salesHeader(){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin,$Right_Margin,$firstrowpos;
    Global $line_height,$Bottom_Margin,$FontSize,$XPos,$R1,$R2,$R3,$PageNumber,$castdate,$Tcastdate;
    
    $PageNumber++;
      
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
       
        $rprtdate  = trim($_POST['day']).'-'.trim($_POST['month']).'-'.trim($_POST['year']);
        $Trprtdate = trim($_POST['Tday']).'-'.trim($_POST['Tmonth']).'-'.trim($_POST['Tyear']);
        
       if(mb_strlen($Trprtdate)>=8){ }else{ $Trprtdate=$rprtdate;}
     
       $pdf->addText(240,$YPos,10,_('Daily Profitability Report ').':'. $rprtdate .' to '.$Trprtdate);        
       $YPos -= $line_height * 2;
          
        $LeftOvers = $pdf->addTextWrap(45,$YPos,100, $FontSize,_('Date '),'left');
        $LeftOvers = $pdf->addTextWrap(90,$YPos,100, $FontSize,_('Ref No.'),'left');
        $LeftOvers = $pdf->addTextWrap(180,$YPos,100, $FontSize,_('Item'),'left');
        $LeftOvers = $pdf->addTextWrap(250,$YPos,100, $FontSize,_('Units'),'left');
        $LeftOvers = $pdf->addTextWrap(300,$YPos,100, $FontSize,_('Cost of Sales.'),'right');
        $LeftOvers = $pdf->addTextWrap(380,$YPos,100, $FontSize,_('Sales'),'right');
        $LeftOvers = $pdf->addTextWrap(470,$YPos,100, $FontSize,_('Profit'),'right');
        
        $YPos -= $line_height;
        
        $LeftOvers = $pdf->addTextWrap(90,$YPos,100, $FontSize,_('Bal b/fwd'),'left');
        $LeftOvers = $pdf->addTextWrap(485,$YPos,85, $FontSize, number_format($R3,2),'right');
        
         $YPos -= $line_height * 2;
         $FontSize = 10;
         $firstrowpos=$YPos;
}

?>