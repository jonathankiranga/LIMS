<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/AccountBudgets.inc');
$Title = _('Print Budgets');

if(isset($_POST['trailbalance'])){
    
    $Result=DB_query("Select 
        min(B.`start_date`),
        max(B.End_date)
    from `FinancialPeriods` B 
    where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFbudgetheader.inc');
     
     $YPos = $firstrowpos;
     $Calc = new Calculator();
     $Calc->Reset();
      
     $BudgetAmount=0;
     $Expeses=0;
     $Committed=0;
     $Total=0;
     $Balance=0;
       
     $FontSize = 8;

     $ResultsP = db_AccountBudgets($_POST['Financial_Periods']);
     while($rows = DB_fetch_array($ResultsP)){
         
         $LeftOvers = $pdf->addTextWrap(40,$YPos,10, $FontSize, ucfirst($rows['Code']),'left');
         $LeftOvers = $pdf->addTextWrap(50,$YPos,100, $FontSize, ucfirst($rows['BudgetName']),'left');
         $LeftOvers = $pdf->addTextWrap(150, $YPos,70, $FontSize, number_format($rows['BudgetAmount'],2),'right');
         $LeftOvers = $pdf->addTextWrap(220, $YPos,70, $FontSize, number_format($rows['Expeses'],2),'right');
         $LeftOvers = $pdf->addTextWrap(290, $YPos,70, $FontSize, number_format($rows['Committed'],2),'right');
         $LeftOvers = $pdf->addTextWrap(360, $YPos,70, $FontSize, number_format($rows['Total'],2),'right');
         $LeftOvers = $pdf->addTextWrap(430, $YPos,70, $FontSize, number_format($rows['Balance'],2),'right');
         $LeftOvers = $pdf->addTextWrap(500, $YPos,50, $FontSize, number_format($rows['percent'],2).' %','right');
                   
         $YPos -= $line_height ;
         if($YPos < ($lastrow+($line_height*2))){
             include('includes/PDFbudgetheader.inc');
             $YPos=$firstrowpos;
             $FontSize = 8;
         }
   
        $BudgetAmount +=$rows['BudgetAmount'];
        $Expeses +=$rows['Expeses'];
        $Committed +=$rows['Committed'];
        $Total +=$rows['Total'];
        $Balance +=$rows['Balance'];
     }
          
     $YPos = $lastrow - $line_height ;   
     $pdf->line($Page_Width-$Right_Margin,$YPos + $line_height,$Left_Margin,$YPos+ $line_height);
    
     $LeftOvers = $pdf->addTextWrap(150, $YPos,70, $FontSize, number_format($BudgetAmount,2),'right');
     $LeftOvers = $pdf->addTextWrap(220, $YPos,70, $FontSize, number_format($Expeses,2),'right');
     $LeftOvers = $pdf->addTextWrap(290, $YPos,70, $FontSize, number_format($Committed,2),'right');
     $LeftOvers = $pdf->addTextWrap(360, $YPos,70, $FontSize, number_format($Total,2),'right');
     $LeftOvers = $pdf->addTextWrap(430, $YPos,70, $FontSize, number_format($Balance,2),'right');

     $YPos -= $line_height;
     $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
    
    $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Budgets'). '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
} else {
    
  $Title = _('Print Budgets');

  include('includes/header.inc');
  $FR = new FinancialPeriods();
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Budget Report') .'" alt="" />' . _('Budget Report') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table   class="table table-bordered"><tr><td>Budget Reporting Period</td></tr>';
  $FR->Get();
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Budget"/></td></tr></table>';
  echo '</div></form>';
  
  include('includes/footer.inc');
    
}


?>
