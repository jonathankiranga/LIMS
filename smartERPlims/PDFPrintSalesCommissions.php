<?php 

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Commsions');
 
if(isset($_POST['Financial_Periods'])){
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $ResultIndex=DB_query("Select DATE_ADD(DATE_ADD(`lastdate_in_period`, INTERVAL 1 DAY), INTERVAL -1 MONTH) ,
             DATE_ADD(`lastdate_in_period`, INTERVAL 1439 MINUTE)
             from `periods` where `periodno`='".$_POST['Financial_Periods']."' ", $db);
   $rowdate = DB_fetch_row($ResultIndex);
   
   $FromDate = $rowdate[0];
   $ToDate = $rowdate[1];
   DB_free_result($ResultIndex);
   
   $NAMES = getSaleman($_POST['salespersoncode']);
   If(isset($_POST['PrintCommision'])){
    $headerName = sprintf("SALES Commision Between Dates %s and %s For %s", ConvertSQLDate($FromDate), ConvertSQLDate($ToDate),$NAMES);
   }else{
     $headerName = sprintf("Total UNPAID Between Dates %s and %s For %s", ConvertSQLDate($FromDate), ConvertSQLDate($ToDate),$NAMES);
   }
    $pdf->addInfo('Title',_('Sales Commision'));
    $pdf->addInfo('Subject',_('Sales Commision'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        $REPORTTYPE='';
     include('includes/PDFCommheader.inc');
     
     If(isset($_POST['PrintCommision'])){
         $REPORTTYPE='1';
     $SQL=Sprintf("SELECT 
       SalesHeader.`customername`
      ,SalesHeader.`salespersoncode`
      ,SalesHeader.docdate
      ,SalesLine.`description` 
      ,case SalesLine.`partperunit` when '1' then (SalesLine.Quantity) else (SalesLine.Quantity * SalesLine.`partperunit`)  end as Quantity
      ,SalesLine.Quantity as QuantityAsSold,
       SalesLine.UnitPrice as Sp ,
       `SalesLine`.unitofmeasure as UOMDesc
      ,SalesHeader.`documentno`
      ,SalesHeader.`customercode`
      ,SalesLine.invoiceamount
      ,SalesHeader.`currencycode`
      ,SalesHeader.`documenttype`
      ,SalesLine.vatrate
      ,SalesLine.`PriceInPricelist`
      ,SalesLine.`Increase`
      ,SalesLine.`partperunit`
  FROM `SalesHeader` 
        join SalesLine on SalesHeader.documentno=SalesLine.documentno and SalesHeader.documenttype=SalesLine.documenttype 
        join `CustomerStatement` on `CustomerStatement`.`Documentno`=SalesHeader.`documentno` 
  where `CustomerStatement`.`Datewhenpaid` between '%s' and '%s' 
  and SalesHeader.`salespersoncode`='%s' 
  and SalesHeader.documenttype=10  order by SalesHeader.`documentno`  asc ",$FromDate,$ToDate,$_POST['salespersoncode']);
    
} else {
          
    $REPORTTYPE='2';
           
      $SQL=Sprintf("SELECT 
       SalesHeader.`customername`
      ,SalesHeader.`salespersoncode`
      ,SalesHeader.docdate
      ,SalesLine.`description` 
      ,case SalesLine.`partperunit` when '1' then (SalesLine.Quantity) else (SalesLine.Quantity * SalesLine.`partperunit`) end as Quantity
      ,SalesLine.Quantity as QuantityAsSold,
       SalesLine.UnitPrice as Sp ,
       SalesLine.unitofmeasure as UOMDesc
      ,SalesHeader.`documentno`
      ,SalesHeader.`customercode`
      ,SalesLine.invoiceamount
      ,SalesHeader.`currencycode`
      ,SalesHeader.`documenttype`
      ,SalesLine.vatrate
      ,SalesLine.`PriceInPricelist`
      ,SalesLine.`Increase`
      ,SalesLine.`partperunit`
      ,CustomerStatement.JournalNo
  FROM `SalesHeader` 
        join SalesLine on SalesHeader.documentno=SalesLine.documentno and SalesHeader.documenttype=SalesLine.documenttype 
        join `CustomerStatement` on `CustomerStatement`.`Documentno`=SalesHeader.`documentno` 
  where `CustomerStatement`.`Datewhenpaid` is null
  and SalesHeader.`salespersoncode`='%s' 
  and SalesHeader.documenttype=10  order by SalesHeader.docdate  DESC limit 500",$_POST['salespersoncode']);
         
     }
     
  
     $FontSize = 12;
     $YPos=$firstrowpos-20;
     $TotalCommission = 0;
     $TOTALSALES=0;
     $invoiceamount=0;
     
     $y=0;
     $SalesHeaderdocumentno=array();
     $Dataset=array();
     $Results=DB_query($SQL,$db);
      while($rows = DB_fetch_array($Results)){
          $Dataset[]=$rows;
          $SalesHeaderdocumentno[$y]=$rows['documentno'];
          $y++;
      }
     
      
      
     $ro=0;
  foreach ($Dataset as $rows) {
         
          $before=($ro-1);
          $after=($ro+1);
             
          $TOTALSALES += $rows['invoiceamount'];
          $amount = CalCulateCommision($rows['Sp']/$rows['partperunit'],$rows['PriceInPricelist']/$rows['partperunit'],$rows['Quantity']);
          $TotalCommission += $amount;
          
         if(($SalesHeaderdocumentno[$before]!=$rows['documentno']) ){
          $ID = trim($rows['documentno']).' '.trim($rows['customername']); 
          
          $pdf->addTextWrap($Left_Margin, $YPos,58, $FontSize, _('Invoice'),'left');
          $pdf->addTextWrap($Left_Margin+100, $YPos,200, $FontSize,$rows['documentno'],'left');
          $YPos -= $line_height;
          $pdf->addTextWrap($Left_Margin, $YPos,58, $FontSize, _('Customer'),'left');
          $pdf->addTextWrap($Left_Margin+100, $YPos,200, $FontSize,$rows['customername'],'left');
          $YPos -= $line_height;
          putcolumnheaders();
         }
         
          
          $pdf->addTextWrap($Left_Margin+20, $YPos,80, $FontSize, $rows['description'],'left');
          $pdf->addTextWrap($Left_Margin+100, $YPos,80, $FontSize, trim($rows['UOMDesc']).' X '.number_format($rows['QuantityAsSold'],0),'right');
          $pdf->addTextWrap($Left_Margin+180, $YPos, 50, $FontSize, number_format($rows['Quantity'],0),'right');
          $pdf->addTextWrap($Left_Margin+265, $YPos, 50, $FontSize, number_format($rows['Sp']/$rows['partperunit'],5),'right');
          $pdf->addTextWrap($Left_Margin+350, $YPos, 55, $FontSize, number_format($rows['PriceInPricelist']/$rows['partperunit'],0),'right');
          $pdf->addTextWrap($Left_Margin+405, $YPos, 50, $FontSize, number_format($rows['Increase']/$rows['partperunit'],2),'right');
          $pdf->addTextWrap($Left_Margin+475, $YPos, 50, $FontSize, number_format($amount,2),'right');
            
            
        $invoiceamount += $rows['invoiceamount'];
         if(($SalesHeaderdocumentno[$after]!=$rows['documentno']) ){
              $YPos -= $line_height*2;
              $pdf->addTextWrap($Left_Margin+100,$YPos,100,$FontSize,"Total Sales Invoice",'Left');
              $pdf->addTextWrap($Left_Margin+200,$YPos,90,$FontSize,number_format($invoiceamount,0),'right');
              
             if($REPORTTYPE=='2'){
                 
              $YPos -= $line_height;
              $Paid =PaymentsAllocations ($rows['JournalNo'],$rows['customercode']);
              $pdf->addTextWrap($Left_Margin+100,$YPos,100,$FontSize,"Paid",'Left');
              $pdf->addTextWrap($Left_Margin+200,$YPos,90,$FontSize,number_format($Paid,0),'right');
              
              $YPos -= $line_height;
              $pdf->addTextWrap($Left_Margin+100,$YPos,90,$FontSize,"Balance Unpaid",'Left');
              $pdf->addTextWrap($Left_Margin+200,$YPos,90,$FontSize,number_format($invoiceamount-$Paid,0),'right');
              
             }
             
             $pdf->line($Left_Margin,$YPos-$line_height,$Left_Margin+530,$YPos-$line_height);
               
             $YPos -= $line_height*2;
             $invoiceamount = 0;
         }
        
         $YPos -= $line_height ;
         if(($YPos - $line_height * 3) < $lastrow){
              include('includes/PDFCommheader.inc');
             $YPos=$firstrowpos-20;
         }
         $ro++;
     }
    
if($REPORTTYPE=='1'){
   $FontSize=15;
   $LeftOvers = $pdf->addTextWrap($Left_Margin+100,$lastrow+ $line_height,200,$FontSize,"Cummulative Sales ",'Left');
   $LeftOvers = $pdf->addTextWrap($Left_Margin+200,$lastrow+ $line_height,200,$FontSize,number_format($TOTALSALES,0),'right');

   $LeftOvers = $pdf->addTextWrap($Left_Margin+100,$lastrow,200,$FontSize,"Cummulative Commision",'Left');
   $LeftOvers = $pdf->addTextWrap($Left_Margin+200,$lastrow,200,$FontSize,number_format($TotalCommission,0),'right');
}
     
$pdf->OutputD($_SESSION['DatabaseName'] . '_Commision_(' . $ToDate .').pdf');
$pdf->__destruct();
   
 }else{
  include('includes/header.inc');
  include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

  $FR = new MonthlyReports();
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Sales Commision Report') .'" alt="" />' . _('Sales Commision Report') . '</p>';
  
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
  echo '<table  class="table-striped table-bordered"><tr><td colspan="2">**</td></tr>';
  $FR->Get();
  echo '<tr><td>Sales Person</td><td><select name="salespersoncode">';

 $ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive`  FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);

 while($row=DB_fetch_array($ResultIndex)){
    if(isset($_POST['salespersoncode'])){
        echo sprintf('<option value="%s" %s >%s</option>', $row['code'],($_POST['salespersoncode']==$row['code']?'selected="selected"':''), $row['salesman']);
    }else{  
        echo sprintf('<option value="%s" %s >%s</option>', $row['code'], "", $row['salesman']);
    }
}
    
 echo '</select></td></tr>';
 echo '<tr><td><input type="submit" name="PrintCommision" value="Print Commision for the month"/></td>'
 . '<td><input type="submit" name="SalesBySalesrep" value="Unpaid Sales by Sales REP"/></td></tr></table>';
 echo '</div></form>';
  
include('includes/footer.inc');
}   

function getSaleman($code){
    global $db;
    
$ResultIndex=DB_query(Sprintf("SELECT `salesman`,`commission`,`inactive`  FROM `salesrepsinfo`  where `code`='%s'",$code), $db);
$row=DB_fetch_row($ResultIndex);
       
return $row[0];
}

Function CalCulateCommision($sp,$mp,$qty){
   $perliter =(float) ($sp-$mp);
   $commision=0;
  
   if($perliter>0){
      $changingprice = $mp ;
   }else{
      $changingprice = $sp ;
   }
   
   $count=abs($perliter)+1;
    
    for ($index = 1; $index < $count; $index++) {
        $rowdata = GetCommTable($index);
        $comabove=(float) $rowdata[0];
        $commbelow=(float) $rowdata[1];

            if($perliter>0 and $changingprice <= $sp ){
                $commision +=(float) ($comabove * $qty);
                $changingprice++;
            }elseif($perliter<0 and $changingprice <= $mp){
               $commision  =(float) ($commbelow * $qty);
               $changingprice--;
           }

    }
    
    return $commision;
    
}

function GetCommTable($r){
    global $db;
    
    $ResultIndex=DB_query(sprintf("SELECT count(*) FROM Commision where ceiling='%s'",$_POST['Financial_Periods']),$db);
    $rows = DB_fetch_row($ResultIndex);
    if($rows[0]>1){
       $ResultIndex=DB_query(sprintf("SELECT commisionabove,commisionbelow 
       FROM Commision where rownumber='%s' and ceiling='%s'",(($r>5)?5:$r),$_POST['Financial_Periods']),$db);
    }else{
       $FinPrd= selectlast();
       $ResultIndex=DB_query(sprintf("SELECT commisionabove,commisionbelow 
       FROM Commision where rownumber='%s' and ceiling='%s'",(($r>5)?5:$r),$FinPrd),$db);
    }
    
   $rows = DB_fetch_row($ResultIndex);
   return $rows;
}

Function selectlast(){
      global $db;
    
   $ResultIndex=DB_query("SELECT ceiling FROM Commision order by ceiling desc limit 1",$db);
   $rows = DB_fetch_row($ResultIndex);
   return $rows[0];

}

FUNCTION PaymentsAllocations ($journal ,$Accountno ){
   global $db;
    
   $sql= sprintf("select sum(amount) FROM `ReceiptsAllocation` "
           . "  where `journalno`='%s' and `itemcode`='%s' ",$journal,$Accountno);
  
   $ResultIndex=DB_query($sql,$db);
   $rows = DB_fetch_row($ResultIndex);
   return $rows[0] *-1;
}

function putcolumnheaders(){
 global $pdf,$YPos,$FontSize,$Page_Width,$Right_Margin,$line_height,$Left_Margin;
    
 $pdf->line($Left_Margin,$YPos,$Left_Margin+530,$YPos);
 $YPos -= $line_height * 2;
 
 $pdf->addTextWrap($Left_Margin+20, $YPos,80, $FontSize, _('Item Description'),'right');
 $pdf->addTextWrap($Left_Margin+100, $YPos,80, $FontSize, _('UOM'),'right');
 $pdf->addTextWrap($Left_Margin+200, $YPos,50, $FontSize, _('Quantity'),'right');
 $pdf->addTextWrap($Left_Margin+200, $YPos-$line_height, 50, $FontSize, _('(lts/kgs)'),'right');
 $pdf->addTextWrap($Left_Margin+260, $YPos+$line_height, 50, $FontSize, _('REPS price'),'right');
 $pdf->addTextWrap($Left_Margin+260, $YPos,50, $FontSize, _('ex VAT)'),'right');
 $pdf->addTextWrap($Left_Margin+260, $YPos-$line_height, 50, $FontSize, _('per unit'),'right');
 $pdf->addTextWrap($Left_Margin+350, $YPos,55, $FontSize, _('Rec Price'),'right');
 $pdf->addTextWrap($Left_Margin+350, $YPos-$line_height, 55, $FontSize, _('Per Unit'),'right');
 $pdf->addTextWrap($Left_Margin+405, $YPos,50, $FontSize, _('Over Price'),'right');
 $pdf->addTextWrap($Left_Margin+405, $YPos-$line_height, 50, $FontSize, _('per unit'),'right');
 $pdf->addTextWrap($Left_Margin+475, $YPos,80, $FontSize, _('Items'),'left');
 $pdf->addTextWrap($Left_Margin+475, $YPos-$line_height,80, $FontSize, _('Commision'),'left');
 
 $YPos -= $line_height * 3;
 
       
 
}

?>
