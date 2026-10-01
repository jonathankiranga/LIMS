<?php 
include('includes/session.inc');

include('includes/CurrenciesArray.php');
 // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); 
include('includes/AccountBalance.inc');

$Title = _('Print Profit and loss');
$reportnames = array();
$reportnames['AccountBalance'] = "Trail Balance";
$reportnames['Profit_loss'] = "Trading Profit and Loss";
$reportnames['Balancesheet'] = "Balance Sheet";

if(isset($_POST['trailbalance'])){
    if($_POST['format']=='1'){
        if($_POST['output']==1){
            Custom();
        }else{
            Showhtml();
        }
    } else {
        orgininal();
    }
} else {
    
 include('includes/header.inc');
    
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' . _('P & L') .'" alt="" />' . _('Profit and Loss') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div class="centre">';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
    
  echo '<table class="table table-bordered"><tr>'
  . '<td colspan="2">Reporting Period:'
          . '<table class="table table-bordered"><tr><td>Month</td><td>Year</td></tr>'
      . '<tr><td><select name="month">'
          . '<option value="1">Jan</option>'
          . '<option value="2">Feb</option>'
          . '<option value="3">March</option>'
          . '<option value="4">April</option>'
          . '<option value="5">May</option>'
          . '<option value="6">June</option>'
          . '<option value="7">July</option>'
          . '<option value="8">Aug</option>'
          . '<option value="9">Sep</option>'
          . '<option value="10">Oct</option>'
          . '<option value="11">Nov</option>'
          . '<option value="12">Dec</option>'
          . '</select></td>'
          . '<td><input type="text" maxlength="4" size="10" class="integer" name="year" required="required"/></td></tr>'
          . '</table>';
  echo '</td></tr>';
  echo '<tr><td>Select Report Format</td><td>'
            . '<select name="format">'
            . '<option value="1">Trading Profit and Loss</option>'
            . '<option value="2">Income Statement(No Stock)</option>'
            . '</select>'
            . '</td></tr>';
  echo '<tr><td>Select Report Output</td><td>'
            . '<select name="output">'
            . '<option value="1">PDF</option>'
            . '<option value="2">HTML/EXCEL</option>'
            . '</select>'
            . '</td></tr>';
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Report For The Month"/></td></tr>'
  . '</table>';
  echo '</div></form>';
  
  
include('includes/footer.inc');
    
}


 Function orgininal(){
     global $db,$periodno;   
        
    $headerName  = "Trading Profit and Loss";
    $PaperSize   = 'A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 10;
           
    $_POST['FromDate'] = ConvertSQLDate($_POST['year'].'-'.$_POST['month'].'-'.'01');
    $periodno = GetPeriod($_POST['FromDate'],$db,false);
    $ResultIndex = DB_query("SELECT `lastdate_in_period` FROM `periods` where periodno='".$periodno."'", $db);
    $row = DB_fetch_row($ResultIndex);
    $lastDay = ConvertSQLDate($row[0]);
    
    include('includes/PDFpandlmonthheader.inc');
    
     $YPos = $firstrowpos;
     $Calc = new Calculator();
     $Calc->Reset();
  
     $FontSize = 11;
     $amount   = 0;
     $lastYear = 0;
          
    $ResultsX = DB_Profit_loss_by_month();
    
   while($rowspl = DB_fetch_array($ResultsX)){
       
       $amount = $Calc->Get($rowspl);  
       $Diplay =($amount==0)?'': number_format($amount * -1,0);
        
       switch ($rowspl['ReportStyle']) {
           case 0:
                $accdesc = ucfirst($rowspl['accdesc']);
                $FontSize=9;
               break;
           default:
                 $accdesc = ucfirst($rowspl['accdesc']);
                 $FontSize=9;
               break;
       }
       
       if($rowspl['ReportStyle']==0 || $rowspl['ReportStyle']==2 || 
          $rowspl['ReportStyle']==4 || $rowspl['ReportStyle']==5){
           
             if($rowspl['ReportStyle']==4 || $rowspl['ReportStyle']==2){
               $YPos -= $line_height ;
            }
           
            $LeftOvers = $pdf->addTextWrap(80,$YPos,250, $FontSize,$accdesc,'left');
            $LeftOvers = $pdf->addTextWrap(360,$YPos,100, $FontSize,$Diplay,'right');
           } else {
           $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,$accdesc,'left');
           $YPos -= $line_height ;
       }     
       
       
       
         $YPos -= $line_height  ;
         if($YPos < ($lastrow+$line_height)){
            $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
            include('includes/PDFpandlmonthheader.inc');
            $YPos=$firstrowpos;
         }
     }
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('P&L'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
  
   
   }
    
   
 Function Custom(){
     global $db,$periodno;   
        
    $headerName  = "Trading Profit and Loss";
    $PaperSize   = 'A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 10;
           
    $_POST['FromDate'] = ConvertSQLDate($_POST['year'].'-'.$_POST['month'].'-'.'01');
    $periodno = GetPeriod($_POST['FromDate'],$db,false);
    
    $_POST['periodno'] = $periodno;
    
    $ResultIndex = DB_query("SELECT `lastdate_in_period` FROM `periods` where periodno='".$periodno."'", $db);
    $row = DB_fetch_row($ResultIndex);
    $lastDay = ConvertSQLDate($row[0]);
    
    include('includes/PDFpandlmonthheader.inc');
    
     $YPos = $firstrowpos;
     $TradingAccountbyMonth = new TradingAccountbyMonth();
   
     $FontSize = 11;
     $amount   = 0;
     $lastYear = 0;
          
    $monthdataArray = $TradingAccountbyMonth->Calmonthlydata();
    
    foreach ($monthdataArray as $rowspl) {
        
       $AMOUNT = $TradingAccountbyMonth->Get($rowspl);
           
       if($rowspl['ReportCode']=='netprofit'){
         $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT * -1,0) ;
       }else{
         $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;    
       }
       
       switch ($rowspl['ReportStyle']) {
           case 0:
                $accdesc = ucfirst($rowspl['accdesc']);
                $FontSize=9;
               break;
           default:
                 $accdesc = ucfirst($rowspl['accdesc']);
                 $FontSize=12;
               break;
       }
       
       if($rowspl['ReportStyle']==0 || 
          $rowspl['ReportStyle']==2 || 
               $rowspl['ReportStyle']==4 ){
             
            if($rowspl['ReportStyle']==4 || $rowspl['ReportStyle']==2){
               $YPos -= $line_height ;
               $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,$accdesc,'left');
               $LeftOvers = $pdf->addTextWrap(360,$YPos,100, $FontSize,$Diplay,'right');
            }else{
               $LeftOvers = $pdf->addTextWrap(65,$YPos,250, $FontSize,$accdesc,'left');
               $LeftOvers = $pdf->addTextWrap(275,$YPos,100, $FontSize,$Diplay,'right');
            }
           
           
            
         } else {
             
           $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,$accdesc,'left');
           $YPos -= $line_height * 2 ;
           
       }     
       
       
         $YPos -= $line_height  ;
         if($YPos < ($lastrow + $line_height)){
            $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
            
            include('includes/PDFpandlmonthheader.inc');
            $YPos = $firstrowpos;
         }
     }
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('P&L'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
  
   
 }
   
 
    
Function Showhtml(){
     Global $db,$periodno;
    
    include('includes/header.inc');
    
    $_POST['FromDate'] = ConvertSQLDate($_POST['year'].'-'.$_POST['month'].'-'.'01');
    $periodno = GetPeriod($_POST['FromDate'],$db,false);
    
    $_POST['periodno'] = $periodno;
    
    $ResultIndex = DB_query("SELECT `lastdate_in_period` FROM `periods` where periodno='".$periodno."'", $db);
    $row = DB_fetch_row($ResultIndex);
    $lastDay = ConvertSQLDate($row[0]); 
    
    echo '<Div class="centre">Profit and Loss Report</DIV>'
       . '<Div class="centre">'._('For Month Ending :'). $lastDay .'</DIV>'
       . '<div class="container"><table class="table-striped table-bordered" id="GL"><tr>'
       . '<th>DESECRIPTION</th><th>Amount</th><th>Total</th></tr>';
 
 
    $TradingAccountbyMonth = new  TradingAccountbyMonth();
    $YearDataArray = $TradingAccountbyMonth->Calmonthlydata();
    
    foreach ($YearDataArray as $rowspl) {
         
       $AMOUNT = $TradingAccountbyMonth->Get($rowspl);
      
       if($rowspl['ReportCode']=='netprofit'){
         $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT * -1,0) ;
       }else{
         $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;    
       }

       switch ($rowspl['ReportStyle']) {
           case 0:
                $accdesc = ucfirst($rowspl['accdesc']);
               break;
           default:
                 $accdesc = ucfirst($rowspl['accdesc']);
               break;
       }
       
       if($rowspl['ReportStyle']==0 || 
          $rowspl['ReportStyle']==2 || 
          $rowspl['ReportStyle']==4 ){
             
           if($rowspl['ReportStyle']==0){
             echo '<tr><td>'. $accdesc .'</td><td>'.$Diplay.'</td><td></td></tr>';
           }else{
             echo '<tr><td>'. $accdesc .'</td><td></td><td>'.$Diplay.'</td></tr>';
           }
            
         } else {
           echo '<tr><td>'. $accdesc .'</td></tr>';
       }     
       
       
   
     }
                     
       echo '</table>';
       echo '<input type="button" onclick="tableToExcel(\'GL\',\'P$L\')" value="Export to Excel">';

   include('includes/footer.inc');
}

?>
