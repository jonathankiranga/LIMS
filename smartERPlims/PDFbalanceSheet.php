<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/AccountBalance.inc');

$Title = _('Balance Sheet');
$reportnames = array();
$reportnames['1']="Balance Sheet";

if(isset($_POST['trailbalance'])){
    
    if($_POST['format'=='2']){
        RawBalancesheet();
    } else {
        if($_POST['output']=='1'){
            CustomizedBalanceSheet();
        }else{
            Showhtml();
        }
    }
    
} else {
    
 include('includes/header.inc');
  $FR = new FinancialPeriods();
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' . _('Balance Sheet') .'" alt="" />' . _('Balance Sheet') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table  class="table table-bordered"><tr><td>Reporting Period</td></tr>';
  $FR->Get();
  echo '<tr><td>Select Report Format</td><td><select name="format">'
       . '<option value="1">Customized</option>'
       . '<option value="2">Raw</option></select></td></tr>';
   echo '<tr><td>Select Report Output</td><td>'
            . '<select name="output">'
            . '<option value="1">PDF</option>'
            . '<option value="2">HTML/EXCEL</option>'
            . '</select>'
            . '</td></tr>';
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Balance Sheet"/>'
       . '</td></tr></table>';
  echo '</div></form>';
  
  
include('includes/footer.inc');
    
}

Function Addline(){
    global $Page_Width,
            $Right_Margin,
            $YPos,$pdf,
            $Left_Margin,
            $line_height,
            $lastrow,$firstrowpos;
    
    $YPos -= $line_height ;
         if($YPos < ($lastrow+$line_height)){
            $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
            include('includes/PDFpandlheader.inc');
            $YPos=$firstrowpos;
         }
}    

Function RawBalancesheet(){
    global $db,$reportnames;
    
    $headerName = $reportnames['1'];
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
     $FontSize = 11;
     $amount   = 0;
     $lastYear = 0;
     
    
    $YearPeriod = $_POST['Financial_Periods'];
    $ResultsX = DB_AccountBalance();
         
   while($rowspl = DB_fetch_array($ResultsX)){
       $ClassCalc = new Calculator();
       $amount = $ClassCalc->Get($rowspl);
       $lastYear = $ClassCalc->Get_lastyear();
        
        switch ($rowspl['ReportStyle']) {
           case 0:
                $accdesc= ucfirst($rowspl['accdesc']);
                $FontSize=8;
               break;
           default:
                 $accdesc=$rowspl['accdesc'];
                 $FontSize=9;
               break;
       }
         
       if($rowspl['ReportStyle']==0 || $rowspl['ReportStyle']==2 || $rowspl['ReportStyle']==4){
           
            if($rowspl['ReportStyle']==4 || $rowspl['ReportStyle']==2){
                 Addline(); 
                $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,$accdesc,'left');
            }else{
                 $LeftOvers = $pdf->addTextWrap(50,$YPos,250, $FontSize,$accdesc,'left');
            }
            
         $Diplay =($amount==0)?'': number_format($amount,0);         
         $LeftOvers = $pdf->addTextWrap(360,$YPos,100, $FontSize,$Diplay,'right');
        
         $DiplayOne =($lastYear==0)?'': number_format($lastYear,0);  
         $LeftOvers = $pdf->addTextWrap(420,$YPos,100, $FontSize,$DiplayOne,'right');
          
       } 
       else {
           $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,$accdesc,'left');
            Addline();
       }
                                       
      Addline(); 
     }
                                                
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('BalanceSheet'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
}
       
Function CustomizedBalanceSheet(){
    global $db,$YearPeriod,$reportnames;   
         
    $headerName = $reportnames['1'];
    $PaperSize='A4';
    include('includes/PDFStarter.php');
      
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)  from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;  $PageNumber = 0; $line_height = 13;
        
    include('includes/PDFpandlheader.inc');
    
    $YPos = $firstrowpos;
    $FontSize = 11; $amount   = 0; $lastYear = 0;
  
    $BalancesheetForYear = new BalancesheetForYear();
    $YearDataArray = $BalancesheetForYear->CalYearlydata();
    foreach ($YearDataArray as $rowspl) {
        
       $AMOUNT = $BalancesheetForYear->Get($rowspl);
       $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;
     
       $amount = $BalancesheetForYear->Get_last($rowspl);  
       $Diplay_Last =($amount==0)?'': number_format($amount,0);
   
       switch ($rowspl['ReportStyle']) {
           case 0:
                $accdesc = ucfirst($rowspl['accdesc']);
                $FontSize=9;
               break;
           default:
                 $accdesc = ucfirst($rowspl['accdesc']);
                 $FontSize=10;
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
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('BalanceSheet_Year'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
  
  
    }
       
Function Showhtml(){
    Global $db,$YearPeriod;
    
    include('includes/header.inc');
        
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)  from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    echo '<Div class="centre">Balance Sheet</DIV>'
       . '<Div class="centre">'._('From :'). ConvertSQLDate($finacialdates[0])._(' To :'). ConvertSQLDate($finacialdates[1]) .'</DIV>'
       . '<div class="container"><table class="table-striped table-bordered" id="GL">'
            . '<tr><th>ACCOUNT</th><th class="number">This Year</th><th class="number">Last Year</th></tr>';

    $YearPeriod=$_POST['Financial_Periods'];
    $BalancesheetForYear = new BalancesheetForYear();
    $YearDataArray = $BalancesheetForYear->CalYearlydata();

   foreach ($YearDataArray as $rowspl) {
       $AMOUNT = $BalancesheetForYear->Get($rowspl);
       $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;
     
       $amount = $BalancesheetForYear->Get_last($rowspl);  
       $Diplay_Last =($amount==0)?'': number_format($amount,0);

       $accdesc = ucfirst($rowspl['accdesc']);
 
       if($rowspl['ReportStyle']==0){
           echo '<tr><td>:'. $accdesc .'</td><td class="number">'.$Diplay.'</td><td class="number">'.$Diplay_Last.'</td></tr>';
        } elseif($rowspl['ReportStyle']==2 || $rowspl['ReportStyle']==4 ) {
           echo '<tr><td><B><i>'. $accdesc .'</i></B></td><td class="number"><b>'.$Diplay.'</b></td><td class="number"><b>'.$Diplay_Last.'</b></td></tr>';
        } elseif($rowspl['ReportStyle']==1 || $rowspl['ReportStyle']==3 ) {
           echo '<tr><td colspan="3"><B>'. $accdesc .'</B></td></tr>';
       }     

     }

   echo '</table></div>';
   echo '<input type="button" onclick="tableToExcel(\'GL\',\'Balance Sheet\')" value="Export to Excel">';

   include('includes/footer.inc');
}

    
?>
