<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/AccountBalance.inc');

$Title = _('Print Profit and loss By Project');
$reportnames = array();
$reportnames['Profit_loss']="INCOME STATEMENT BY PROJECT :".$_SESSION['Dimesion_two'][$_POST['DimensionTwo']];

if(isset($_POST['trailbalance'])){
    
   if($_POST['format']=='1'){
       if($_POST['output']==1){ Custom(); }else{ Customhtml();  }
    } else {
      if($_POST['output']==1){ original(); }else{ originalhtml();  }
    }

} else {
    
 include('includes/header.inc');
 $FR = new FinancialPeriods();
 
  echo '<p class="page_title_text">'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' . _('By Project') .'" alt="" />' . _('Annual Profit and Loss By Project') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div class="centre">';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  
  echo '<table class="table-bordered"><tr><td colspan="2">Reporting Period</td></tr>';
  $FR->Get();
  echo '<tr><td>Select Report Format</td><td><select name="format">'
        . '<option value="1" selected="selected">Trading Profit and Loss</option>'
        . '<option value="2">Income Statement(No Stock)</option></select></td></tr>';
 echo $_SESSION['report']['project'];
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
         
    $YearPeriod = $_POST['Financial_Periods'];
    $ProjectSelected = $_POST['DimensionTwo'];
   
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
     $TradingAccountbyMonth = new TradingAccountByProjectForYear();
   
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
               $LeftOvers = $pdf->addTextWrap(65,$YPos,250, $FontSize,$accdesc,'left');
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
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Project_P&L_Year'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
  
   
 }
   

function original(){
    global $db,$YearPeriod,$ProjectSelected; 
     $YearPeriod = $_POST['Financial_Periods'];
     $ProjectSelected = $_POST['DimensionTwo'];
   
    $headerName = $reportnames['Profit_loss'];
    $PaperSize='A4';
    include('includes/PDFStarter.php');
      
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)
    from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates = DB_fetch_row($Result);   
       
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
     $ResultsX = DB_Profit_loss_Project();
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
         
         $Diplay    = ($amount==0)?'': number_format( abs($amount),0);   
         $DiplayOne = ($lastYear==0)?'': number_format( abs($lastYear),0); 
          
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
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('ProjectIncomeStatement_Year'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
    }

    
Function Customhtml(){
    global $db,$YearPeriod;   
         
    $YearPeriod = $_POST['Financial_Periods'];
    $ProjectSelected = $_POST['DimensionTwo'];
   
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)  from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);  
     $headerName = 'Trading Profit and Loss for Project '.$_SESSION['Dimesion_two'][$ProjectSelected];
  
     include('includes/header.inc');
 
    echo '<Div class="centre">'.$headerName.'</DIV>'
       . '<Div class="centre">'._('FROM:').ConvertSQLDate($finacialdates[0]) ._(' TO: ').ConvertSQLDate($finacialdates[1]) .'</DIV>'
        . '<div class="container"><table class="table table-striped table-bordered" id="GL"><tr>'
       . '<th>Code</th><th>ACCOUNT</th><th>Current Year</th><th>Last Year</th></tr><tr>'
       . '<td></td><td></td><td class="number">AMOUNT</td><td class="number">AMOUNT</td></tr>';
       $TradingAccountbyMonth = new TradingAccountByProjectForYear();
   
       $amount   = 0; $lastYear = 0;
            
    $monthdataArray = $TradingAccountbyMonth->CalYearlydata();
    foreach ($monthdataArray as $rowspl) {
        
       $AMOUNT = $TradingAccountbyMonth->Get($rowspl);
       $Diplay = ($AMOUNT==0)?'': number_format(abs($AMOUNT),0) ;
     
       $amount = $TradingAccountbyMonth->Get_last($rowspl);  
       $Diplay_Last =($amount==0)?'': number_format(abs($amount),0);
   
       switch ($rowspl['ReportStyle']) {
           case 0:
                $accdesc = ucfirst($rowspl['accdesc']);
               break;
           default:
                 $accdesc = ucwords($rowspl['accdesc']);
               break;
       }
         $ReportNo= $rowspl['ReportCode'];
       
       if($rowspl['ReportStyle']==0 || 
          $rowspl['ReportStyle']==2 || 
          $rowspl['ReportStyle']==4 ){
           echo '<tr><td>'.$ReportNo.'</td><td>'. $accdesc .'</td><td class="number">'.$Diplay.'</td><td class="number">'.$Diplay_Last.'</td></tr>';
         } else {
           echo '<tr><td colspan="4">'. $accdesc .'</td></tr>';
         }     
       
     }
                         
  
       echo '</table>';
       echo '<input type="button" onclick="tableToExcel(\'GL\',\'P$L\')" value="Export to Excel"></div>';

   include('includes/footer.inc');
         
 }
   

function originalhtml(){
    global $db,$YearPeriod,$ProjectSelected; 
     $YearPeriod = $_POST['Financial_Periods'];
     $ProjectSelected = $_POST['DimensionTwo'];
   
    $headerName = 'Income Statement for Project '.$_SESSION['Dimesion_two'][$ProjectSelected];
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date) from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates = DB_fetch_row($Result);  
    
       include('includes/header.inc');
 
    echo '<Div class="centre">'.$headerName.'</DIV>'
       . '<Div class="centre">'._('FROM: ').ConvertSQLDate($finacialdates[0]) ._(' TO: ').ConvertSQLDate($finacialdates[1]) .'</DIV>'
       . '<div class="container"><table class="table table-striped table-bordered" id="GL"><tr>'
       . '<th>Code</th><th>ACCOUNT</th><th>Current Year</th><th>Last Year</th></tr><tr>'
       . '<td></td><td></td><td class="number">AMOUNT</td><td class="number">AMOUNT</td></tr>';
     
     $Calc = new Calculator();
     $Calc->Reset();
    
     $amount = 0; $lastYear = 0;
     $ResultsX = DB_Profit_loss_Project();
     while($rowspl = DB_fetch_array($ResultsX)){
       $amount = $Calc->Get($rowspl); 
       $lastYear = $Calc->Get_lastyear($rowspl);
         
       switch ($rowspl['ReportStyle']) {
           case 0:
                $accdesc = ucfirst($rowspl['accdesc']);
               break;
           default:
                 $accdesc = ucwords($rowspl['accdesc']);
               break;
       }
       
      $ReportNo= $rowspl['ReportCode'];
       
       if($rowspl['ReportStyle']==0 ||   $rowspl['ReportStyle']==2 ||  $rowspl['ReportStyle']==4 ){
           $Diplay    = ($amount==0)?'': number_format(abs($amount),0);   
           $DiplayOne = ($lastYear==0)?'': number_format(abs($lastYear),0); 
       
           echo '<tr><td>'.$ReportNo.'</td><td>'. $accdesc .'</td><td class="number">'.$Diplay.'</td><td class="number">'.$DiplayOne.'</td></tr>';
         } else {
            echo '<tr><td colspan="4">'. $accdesc .'</td></tr>';
         }     
     }
     
    echo '</table>';
    echo '<input type="button" onclick="tableToExcel(\'GL\',\'P$L\')" value="Export to Excel"></div>';

   include('includes/footer.inc');   
}

    
?>
