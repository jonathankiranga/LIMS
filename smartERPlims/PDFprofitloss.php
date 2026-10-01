<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/AccountBalance.inc');

$Title = _('Print Profit and loss');

$reportnames = array();
$reportnames['1']="Trail Balance";
$reportnames['2']="Trading Profit and Loss";
$reportnames['3']="Balance Sheet";

if(isset($_POST['trailbalance'])){
   if($_POST['format']=='1'){
        if($_POST['output']=='1'){
            Custom();
         }else{
            Showhtml();
        }
    } else {
       orgininal();
    }

    
}else{
    
 include('includes/header.inc');
 $FR = new FinancialPeriods();
 
  echo '<p class="page_title_text">'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' . _('P & L') .'" alt="" />' . _('Annual Profit and Loss') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div class="centre">';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  
  echo '<table class="table table-bordered"><tr><td colspan="2">Reporting Period</td></tr>';
  $FR->Get();
  echo '<tr><td>Select Report Format</td><td><select name="format">'
         . '<option value="1">Trading Profit and Loss</option>'
        . '<option value="2">Income Statement(No Stock)</option></select></td></tr>';
   echo '<tr><td>Select Report Output</td><td>'
            . '<select name="output">'
            . '<option value="1">PDF</option>'
            . '<option value="2">HTML/EXCEL</option>'
            . '</select>'
            . '</td></tr>';
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Report"/>'
  . '</td></tr></table>';
  echo '</div></form>';
     
include('includes/footer.inc');
    
}



Function Custom(){
    global $db,$YearPeriod;   
         
    $YearPeriod  = $_POST['Financial_Periods'];
    $headerName  = "Trading Profit and Loss";
    $PaperSize   = 'A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 10;
           
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)
    from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);  
     
    include('includes/PDFpandlheader.inc');
    
     $YPos = $firstrowpos;
     $TradingAccountbyMonth = new TradingAccountForYear();
   
     $FontSize = 11;
     $amount   = 0;
     $lastYear = 0;
            
    $monthdataArray = $TradingAccountbyMonth->CalYearlydata();
    foreach ($monthdataArray as $rowspl) {
        
       $AMOUNT = $TradingAccountbyMonth->Get($rowspl);
       $Diplay = ($AMOUNT==0)?'': number_format(abs($AMOUNT),0) ;
     
       $amount = $TradingAccountbyMonth->Get_last($rowspl);  
       $Diplay_Last =($amount==0)?'': number_format(abs($amount),0);
   
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
            }else{
               $LeftOvers = $pdf->addTextWrap(50,$YPos,250, $FontSize,$accdesc,'left');
            }
           
            $LeftOvers = $pdf->addTextWrap(320,$YPos,100, $FontSize,$Diplay,'right');
            $LeftOvers = $pdf->addTextWrap(420,$YPos,100, $FontSize,$Diplay_Last,'right');
            
         } else {
             
           $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,$accdesc,'left');
           $YPos -= $line_height ;
           
       }     
       
       
         $YPos -= $line_height  ;
         if($YPos < ($lastrow + $line_height)){
            $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
            
           include('includes/PDFpandlheader.inc');
   
            $YPos = $firstrowpos;
         }
     }
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('P&L_Year'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
  
   
 }
   

function original(){
    global $db,$YearPeriod; 
    
    $headerName = $reportnames['2'];
    $PaperSize='A4';
    include('includes/PDFStarter.php');
      
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)
    from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
       
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
    include('includes/PDFpandlheader.inc');
    
     $YPos = $firstrowpos;
     $Calc = new Calculator();
     $Calc->Reset();
  
     $FontSize = 11;
     $amount   = 0;
     $lastYear = 0;
     
     $YearPeriod = $_POST['Financial_Periods'];
     
     $ResultsX = DB_Profit_loss();
  
   while($rowspl = DB_fetch_array($ResultsX)){
       $amount = $Calc->Get($rowspl); 
       $lastYear = $Calc->Get_lastyear($rowspl);
         
       switch ($rowspl['ReportStyle']) {
           case 0:
                $accdesc= ucfirst($rowspl['accdesc']);
                $FontSize=8;
               break;
           default:
                 $accdesc=$rowspl['accdesc'];
                 $FontSize=10;
               break;
       }
       
       if($rowspl['ReportStyle']==0 || 
               $rowspl['ReportStyle']==2 || 
               $rowspl['ReportStyle']==4 || 
               $rowspl['ReportStyle']==5){
         
         $Diplay    = ($amount==0)?'': number_format($amount,0);   
         $DiplayOne = ($lastYear==0)?'': number_format($lastYear,0); 
          
         if($rowspl['ReportStyle']==4 || $rowspl['ReportStyle']==2){
            $YPos -= $line_height ;
         }
         
         $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,$accdesc,'left');
         $LeftOvers = $pdf->addTextWrap(320,$YPos,100, $FontSize,$Diplay,'right');
         $LeftOvers = $pdf->addTextWrap(420,$YPos,100, $FontSize,$DiplayOne,'right');
         
        } else {
           $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,$accdesc,'left');
           $YPos -= $line_height ;
        }     
       
         $YPos -= $line_height ;
         if($YPos < ($lastrow+$line_height)){
            $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
            include('includes/PDFpandlheader.inc');
            $YPos=$firstrowpos;
         }
     }
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('P&L_Year'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
    }

      
Function Showhtml(){
     Global $db,$YearPeriod ;
    
    $YearPeriod  = $_POST['Financial_Periods'];
    include('includes/header.inc');
        
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)
    from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    echo '<Div class="centre">Consolidated Profit and Loss </DIV>'
       . '<Div class="centre">'._('From :'). ConvertSQLDate($finacialdates[0])._(' To :'). ConvertSQLDate($finacialdates[1]) .'</DIV>'
       . '<div class="container">'
            . '<table class="table-striped table-bordered" id="GL"><tr>'
            . '<th>Account</th>'
            . '<th class="number">Amount</th>'
            . '<th class="number">Total</th></tr>';
 
    $YearPeriod=$_POST['Financial_Periods'];
    $TradingAccountbyMonth = new TradingAccountForYear();
    $YearDataArray = $TradingAccountbyMonth->CalYearlydata();
    foreach ($YearDataArray as $rowspl) {
       $AMOUNT = $TradingAccountbyMonth->Get($rowspl);
       $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;

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
             echo '<tr><td>'. $accdesc .'</td><td class="number">'.$Diplay.'</td><td></td></tr>';
           }elseif($rowspl['ReportStyle']==2 || $rowspl['ReportStyle']==4){
             echo '<tr><td><b>'. $accdesc .'</b></td><td></td><td class="number"><b>'.$Diplay.'</b></td></tr>';
           }
            
         } else {
           echo '<tr><td colspan="3"><b>'. $accdesc .'</b></td></tr>';
       }     
   
     }
                     
       echo '</table>';
       echo '<input type="button" onclick="tableToExcel(\'GL\',\'Yearly Trading P$L\')" value="Export to Excel"></div>';

   include('includes/footer.inc');
}

?>
