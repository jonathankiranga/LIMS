<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/AccountBalance.inc');

$Title = _('Print Trial Balance');

$reportnames = array();
$reportnames['1']="Trial Balance";
 
if(isset($_POST['trailbalance'])){
    
    if($_POST['reportoutput']=='2'){
    $headerName = $reportnames['1'];
    
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date) from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFfinancialsheader.inc');
     
     $YPos = $firstrowpos;
     $Calc = new Calculator();
     $Calc->Reset();
     
     $debit= 0;
     $credit = 0;
     $FontSize = 11;
     
     $YearPeriod=$_POST['Financial_Periods'];
     $ResultsP = DB_TBalance();
     while($rows = DB_fetch_array($ResultsP)){
         $Calc = new Calculator();
         $amount = $Calc->Get($rows);
         
          if($amount>0){ $debit += $amount; }else{ $credit += $amount * -1; }
           $LeftOvers = $pdf->addTextWrap(42,$YPos,250,8,ucfirst($rows['accdesc']),'left');
           
            if($amount>0){
               $LeftOvers = $pdf->addTextWrap(360, $YPos,100,8, number_format($amount,0),'right');
            } elseif($amount<0){
               $LeftOvers = $pdf->addTextWrap(420, $YPos,100,8, number_format($amount *-1,0),'right');
            }

          $YPos -= $line_height ;
          if($YPos < ($lastrow+($line_height * 3))){
              $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
              include('includes/PDFfinancialsheader.inc');
              $YPos=$firstrowpos;
          }
         
     }
     
     
     
     $amount=($debit-$credit)* -1;
      $LeftOvers = $pdf->addTextWrap(42, $YPos,250,10,'P&L balance','left');
            if($amount>0){
               $LeftOvers = $pdf->addTextWrap(360, $YPos,100,8, number_format($amount,0),'right');
            } elseif($amount<0){
               $LeftOvers = $pdf->addTextWrap(420, $YPos,100,8, number_format($amount *-1,0),'right');
            }
            
     $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
     $YPos -= $line_height ;  
     
     if($amount>0){ $debit += $amount; }else{ $credit += $amount * -1; }
        
     $LeftOvers = $pdf->addTextWrap(145, $YPos,250,10,'Totals','left');
     $LeftOvers = $pdf->addTextWrap(360, $YPos, 85,8, number_format($debit,0),'right');
     $LeftOvers = $pdf->addTextWrap(420, $YPos, 85,8, number_format($credit,0),'right');
    
             
    $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('TrailBalance'). '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    }else{
        
        include('includes/header.inc');
       $Result=DB_query("Select min(B.`start_date`),max(B.End_date) from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
       $financialPeriods=DB_fetch_row($Result);   
       $StartDate = $financialPeriods[0];
       $Enddate = $financialPeriods[1];
       $YearPeriod = $_POST['Financial_Periods'];
       $REsults = DB_TBalance();
         
             
    echo '<DIV class="container" id="GL"><table class="table-striped table-bordered">'
       . '<tr><td>PERIOD REPORTING <div> From :<b>'. ConvertSQLDate($StartDate).'</b> To <b>'.ConvertSQLDate($Enddate). '</b></div></td>'
            . '</tr></table>'
            . '<table class="table-striped table-bordered">'
            . '<thead><tr>'
            . '<th>ACCOUNT NO</th>'
            . '<th>ACCOUNT NAME</th>'
            . '<th>BALANCE/INCOME</th>'
            . '<th class="number">DEBIT</th>'
            . '<th class="number">CREDIT</th>'
            . '</thead></tr>';
    
    $ClassCalc = new Calculator();
    $ClassCalc->Reset();
    
$debit=0; $credit=0;
while($rows=DB_fetch_array($REsults)){
    $ClassCalc = new Calculator();
    $amount = $ClassCalc->Get($rows);
     if($amount>0){$debit += $amount;} elseif ($amount<0){$credit += $amount * -1;}
         
        echo '<tr><td><a href="ChartofAccounts.php?AKD='.trim($rows['accno']).'"> Code:'.$rows['ReportCode'].' </a></td>';
        echo '<td><a href="LedgerReports.php?Drill='.trim($rows['accno']).'">'.$rows['accdesc'].'</a></td>';
        echo '<td>'.$BalanceSheet[$rows['balance_income']].'</td>';
               
        if($amount>0){
           echo '<td class="number">'.number_format($amount,2).'</td>';
           echo '<td class="number"></td>';
        }elseif($amount<0){
           echo '<td class="number"></td>';
           echo '<td class="number">'.number_format($amount * -1,2).'</td>';
        }else{
           echo '<td class="number"></td>';
           echo '<td class="number"></td>';
        }
    
        echo '</tr>' ;
    
    
}
$amount=($debit-$credit)* -1;
echo '<tr><td colspan="3">P&L balance</td>';
  if($amount>0){
      echo '<td class="number">'. number_format($amount,0).'</td><td class="number"></td>';
  } elseif($amount<0){
     echo '<td class="number"></td><td class="number">'. number_format($amount *-1,0).'</td>';
  }
  echo '</tr>' ;   
  
   if($amount>0){ $debit += $amount; }elseif($amount<0){ $credit += $amount * -1; }
   
echo '<tr><td colspan="3">Totals</td><td class="number">'.number_format($debit,2).'</td>'
        . '<td class="number">'.number_format($credit,2).'</td></tr></table></DIV>';
    echo '<input type="button" onclick="tableToExcel(\'GL\',\'Trail Balance\')" value="Export to Excel">';

include('includes/footer.inc');
    }
    
    
}else{
    
  include('includes/header.inc');
  $FR = new FinancialPeriods();
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('General ledger') .'" alt="" />' . _('General ledger') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
  echo '<table  class="table-striped table-bordered"><tr><td>Reporting Period</td></tr>';
  $FR->Get();
   echo '<tr><td>Select Report Output</td><td><select name="reportoutput">'
                  . '<option value="1">HTML</option>'
                 . '<option value="2">PDF</option></select></td></tr>';
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Trial Balance"/></td></tr></table>';
  echo '</div></form>';
 
  
    $REsults = DB_query('Select 
        min(B.`start_date`),
        max(B.End_date),
        periodno 
        from `FinancialPeriods` B  
        where B.closed=0 
        Group by periodno',$db);
    
       $financialPeriods = DB_fetch_row($REsults);
       $StartDate = $financialPeriods[0];
       $Enddate = $financialPeriods[1];
       $YearPeriod = $financialPeriods[2];
       
       
    echo '<table class="table-striped table-bordered"><tr><td>PERIOD REPORTING <div> From :<b>'.
            ConvertSQLDate($StartDate).'</b> To <b>'.ConvertSQLDate($Enddate). '</b></div></td>'
            . '</tr></table>';
            
    echo '<DIV>'
            . '<table class="table-striped table-bordered">'
            . '<thead><tr>'
            . '<th>ACCOUNT NO</th>'
            . '<th>ACCOUNT NAME</th>'
            . '<th>BALANCE/INCOME</th>'
            . '<th>Formula</th>'
            . '<th>TYPE</th>'
            . '<th>BALANCE</th>'
            . '</thead></tr>';
 
    $ClassCalc = new Calculator();
    $ClassCalc->Reset();
    
    $k =1; 
   $URL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
   $REsults = DB_TBalance();


while($rows=DB_fetch_array($REsults)){
    $ClassCalc = new Calculator();
    $amount = $ClassCalc->Get($rows);

    echo '<tr><td><a href="ChartofAccounts.php?AKD='.trim($rows['accno']).'"> Code:'.$rows['ReportCode'].' </a></td>';
    echo '<td><a href="LedgerReports.php?Drill='.trim($rows['accno']).'">'.$rows['accdesc'].'</a></td>';
    echo '<td>'.$BalanceSheet[$rows['balance_income']].'</td>';
    echo '<td>'.$rows['Calculation'].'</td>';
    echo '<td>'.$AccountType[$rows['ReportStyle']].'</td>';
    echo '<td class="number">'.number_format($amount,2).'</td>';
    echo '</tr>' ;

}

echo '</table></DIV>';
include('includes/footer.inc');
    
}


?>
