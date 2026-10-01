<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/AccountBalance.inc');

$Title = _('Funds Flow');
$reportnames = array();
$reportnames['FundsFlow']="Funds Flow";

if(isset($_POST['trailbalance'])){
  if($_POST['output']=='1'){
            CustomizedBalanceSheet();
        }else{
            Showhtml();
        }
} else {
    
  include('includes/header.inc');
  $FR = new FinancialPeriods();
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' 
          . _('Cash Flow Report') .'" alt="" />' . _('Cash Flow Report') . '</p>';
  
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table  class="table table-bordered"><tr><td>Reporting Period</td></tr>';
        $FR->Get();
  echo '<tr><td>Select Report Output</td><td>'
            . '<select name="output">'
            . '<option value="1">PDF</option>'
            . '<option value="2">HTML/EXCEL</option>'
            . '</select>'
            . '</td></tr>';
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Funds Flow"/>'
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
            include('includes/PDFfundsflowheader.inc');
            $YPos=$firstrowpos;
         }
}    

Function CustomizedBalanceSheet(){
          
    global $db,$YearPeriod,$reportnames;   
         
     $headerName = $reportnames['FundsFlow'];
    $PaperSize='A4';
    include('includes/PDFStarter.php');
      
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)
    from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;  $PageNumber = 0; $line_height = 13;
        
    include('includes/PDFfundsflowheader.inc');
    
    $YPos = $firstrowpos;
    $FundsFlowForYear = new FundsFlowForYear();
    $FontSize = 11; $amount   = 0; $lastYear = 0;
  
    $YearDataArray = $FundsFlowForYear->CalYearlydata();
    foreach ($YearDataArray as $rowspl) {
        
       $AMOUNT = $rowspl['amount'];
       $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;
       $accdesc = ucfirst($rowspl['Description']);
       $FontSize=10;
       
        $LeftOvers = $pdf->addTextWrap(65,$YPos,250, $FontSize,$accdesc,'left');
        $LeftOvers = $pdf->addTextWrap(420,$YPos,100, $FontSize,$Diplay,'right');
        $YPos -= $line_height * 2  ;
         
         if($YPos < ($lastrow + $line_height)){
           include('includes/PDFfundsflowheader.inc');
          $YPos = $firstrowpos;
         }
     }
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Cashflow_Year'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
  
  
    }
   
Function Showhtml(){
     Global $db;
    
    include('includes/header.inc');
        
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)
    from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    echo '<Div class="centre">Funds Flow statemet </DIV>'
       . '<Div class="centre">'._('From :'). ConvertSQLDate($finacialdates[0])._(' To :'). ConvertSQLDate($finacialdates[1]) .'</DIV>'
       . '<div class="container"><table class="table table-striped table-bordered" id="GL"><tr>'
       . '<th>DESECRIPTION</th><th>AMOUNT</th></tr>';
 
    $FundsFlowForYear = new FundsFlowForYear();
    $YearDataArray = $FundsFlowForYear->CalYearlydata();
   foreach ($YearDataArray as $rowspl) {
     
       $AMOUNT = $rowspl['amount'];
       $Diplay = ($AMOUNT==0)?'': number_format($AMOUNT,0) ;
       $accdesc = ucfirst($rowspl['Description']);

       echo '<tr><td>'. $accdesc .'</td><td class="number">'.$Diplay.'</td></tr>';
   
     }
     echo '</table>';
       
     echo '<input type="button" onclick="tableToExcel(\'GL\',\'CashFlow\')" value="Export to Excel"></div>';

   include('includes/footer.inc');
}

 
class FundsFlowForYear {
    var $Calc = array();
    var $Calc_LastYear = array();
    Var $Amount = 0;
    Var $Amount2 = 0;
    Var $Amount2_lastyear = 0;
    Var $Amount_lastyear = 0;
    var $begintotal = 0;
    var $begintotal_lastyear = 0;
    var $starttotaling = true;
    Var $ResultDataSet = array();
    var $DebitSide = 0;
    var $CreditSide = 0;
    var $DebitSide_Last = 0;
    var $CreditSide_Last = 0;
    Var $Profit = 0;
    Var $Profit_last = 0;
    var $Resource;
    
    Var $NETAssets = 0;
    Var $NETinventory = 0;
    Var $NETdebtors = 0;
    Var $NETcreditors = 0;
    Var $NETloans = 0;
    Var $NETcapital = 0;
    Var $NONcashprofit = 0;
    var $Cashcfwd = 0;
    var $CashClosing = 0;
    
    Function __construct() {
        $_SESSION['BalanceSheed']['id']=100000;
    }
    
    Function GetID(){
       return $_SESSION['BalanceSheed']['id']++;
    }
    
    function Get($rows=array()){
        
       if($rows['ReportStyle']==3){
             $_SESSION['chart_table']['starttotaling'] = true;
             $_SESSION['chart_table']['begintotal'] = 0;
       }
             
       if(($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
           and ($_SESSION['chart_table']['starttotaling'] == true)){
                
               $_SESSION['chart_table']['begintotal']  += ($rows['amount']);
           
       }
              
       if($rows['ReportStyle']==4){
               $_SESSION['chart_table']['starttotaling'] = false;
               $this->Amount2 = $_SESSION['chart_table']['begintotal'];
               $_SESSION['chart_table']['begintotal'] = 0;
        } else {
              
             $this->Amount2 = ($rows['amount']);
           
        }
       
        $this->savetotal($rows['ReportCode'],$this->Amount2);
        
        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }
      
        return $this->Amount2;
        
    }
    
    function Get_last($rows=array()){
        
       if($rows['ReportStyle']==3){
             $_SESSION['chart_table_Last']['starttotaling'] = true;
             $_SESSION['chart_table_Last']['begintotal'] = 0;
       }
             
       if(($rows['ReportStyle'] == 0) and ($_SESSION['chart_table_Last']['starttotaling'] == true)){
               $_SESSION['chart_table_Last']['begintotal']  += ($rows['Amount_lastyear']);
       }
              
       if($rows['ReportStyle']==4){
               $_SESSION['chart_table_Last']['starttotaling'] = false;
               $this->Amount2_lastyear = $_SESSION['chart_table_Last']['begintotal'];
               $_SESSION['chart_table_Last']['begintotal'] = 0;
        } else {
             $this->Amount2_lastyear = ($rows['Amount_lastyear']);
        }
       
        $this->savetotal_last($rows['ReportCode'],$this->Amount2);
        
        if((mb_strlen($rows['Calculation'])>0) and ($rows['ReportStyle']==2)){
            $this->Amount2_lastyear = $this->GetTotal_last($rows['Calculation']);
        }
      
        return $this->Amount2_lastyear;
        
    }
    
    Function Reset(){
        Unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;
        $_SESSION['chart_table']['Amount'] = 0;
        $_SESSION['chart_table']['begintotal'] = 0;
        $_SESSION['chart_table']['Calc'] = array();
        
         Unset($_SESSION['chart_table_Last']);
        $_SESSION['chart_table_Last']['starttotaling'] = false;
        $_SESSION['chart_table_Last']['Amount'] = 0;
        $_SESSION['chart_table_Last']['begintotal'] = 0;
        $_SESSION['chart_table_Last']['Calc'] = array();
        
        $this->ResultDataSet=array();
    }
        
    Function GetTotal_last($formular){
        $this->Amount_lastyear=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount_lastyear = $this->AddTotals_last($code);
        }
        return $this->Amount_lastyear;
    }
    
    function AddTotals($account){
         $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
         return $_SESSION['chart_table']['Amount'];
    }
        
    Function savetotal_last($reportcode,$amount=0){
        $_SESSION['chart_table_Last']['Calc'][$reportcode] = $amount ;
    }
        
    Function GetTotal($formular){
        $this->Amount=0;
        $accounts=explode('+',$formular);
        foreach ($accounts as $code) {
           $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }
           
    function AddTotals_last($account){
         $_SESSION['chart_table_Last']['Amount'] += $_SESSION['chart_table_Last']['Calc'][$account];
         return $_SESSION['chart_table_Last']['Amount'];
    }
           
    Function savetotal($reportcode,$amount=0){
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount ;
    }
           
    Function CalYearlydata(){
        $this->Reset();
        $this->Profit = 0;
        $this->Profit_last = 0;
       
        $this->DebitSide = 0;
        $this->CreditSide = 0;
        
        $this->DebitSide_Last = 0;
        $this->CreditSide_Last = 0;
        $this->begintotal=0;
        
        $this->ResultDataSet['AccFund']['Description'] =  _('Profit(loss) For the year');
        $this->ResultDataSet['AccFund']['amount'] = 0;  
                   
         $this->DB_FixedAssets();
         $this->DB_Inventory();
         $this->DB_Debtors();
         $this->DB_Bank();
         $this->DB_CurrentLiability();
         $this->DB_LongLiability();
         $this->DB_Capital();
         $this->DB_BankAnalysis();
         
         
         return $this->ResultDataSet;
    }

    protected function GetPeriodDates($periodNo){
        global $db;
        $periodNo = (int)$periodNo;
        $sql = "SELECT MIN(`start_date`) AS date_from, MAX(`end_date`) AS date_to
                FROM `FinancialPeriods`
                WHERE `periodno`=" . $periodNo;
        $result = DB_query($sql,$db);
        $row = DB_fetch_array($result);
        return array('from'=>$row['date_from'],'to'=>$row['date_to']);
    }

    protected function GetRangeRows($fromField,$toField){
        global $db;
        $yearPeriod = (int)$_POST['Financial_Periods'];
        $lastPeriod = $yearPeriod - 12;

        $setupSql = "SELECT `".$fromField."` AS code_from, `".$toField."` AS code_to
                     FROM `Accountsetup`
                     LIMIT 1";
        $setupResult = DB_query($setupSql,$db);
        $setupRow = DB_fetch_array($setupResult);
        $codeFrom = DB_escape_string(trim($setupRow['code_from']));
        $codeTo   = DB_escape_string(trim($setupRow['code_to']));

        $dates     = $this->GetPeriodDates($yearPeriod);
        $lastDates = $this->GetPeriodDates($lastPeriod);
        $fromDate = DB_escape_string($dates['from']);
        $toDate   = DB_escape_string($dates['to']);
        $lastFrom = DB_escape_string($lastDates['from']);
        $lastTo   = DB_escape_string($lastDates['to']);

        $sql = "SELECT
                    acct.`ReportCode`,
                    acct.`accdesc`,
                    acct.`balance_income`,
                    acct.`ReportStyle`,
                    acct.`direct`,
                    acct.`Sale_Purchase_Neither`,
                    acct.`accno`,
                    CASE WHEN acct.`balance_income`=0 THEN
                        (SELECT SUM(gl.amount * gl.`ExchangeRate`) FROM `Generalledger` gl
                         WHERE gl.accountcode=acct.accno AND gl.Docdate <= '".$toDate."')
                    ELSE
                        (SELECT SUM(gl.amount * gl.`ExchangeRate`) FROM `Generalledger` gl
                         WHERE gl.accountcode=acct.accno AND gl.Docdate BETWEEN '".$fromDate."' AND '".$toDate."')
                    END AS debit,
                    CASE WHEN acct.`balance_income`=0 THEN
                        (SELECT SUM(gl.amount * gl.`ExchangeRate`) FROM `Generalledger` gl
                         WHERE gl.balaccountcode=acct.accno AND gl.Docdate <= '".$toDate."')
                    ELSE
                        (SELECT SUM(gl.amount * gl.`ExchangeRate`) FROM `Generalledger` gl
                         WHERE gl.balaccountcode=acct.accno AND gl.Docdate BETWEEN '".$fromDate."' AND '".$toDate."')
                    END AS credit,
                    CASE WHEN acct.`balance_income`=0 THEN
                        (SELECT SUM(gl.amount * gl.`ExchangeRate`) FROM `Generalledger` gl
                         WHERE gl.accountcode=acct.accno AND gl.Docdate <= '".$lastTo."')
                    ELSE
                        (SELECT SUM(gl.amount * gl.`ExchangeRate`) FROM `Generalledger` gl
                         WHERE gl.accountcode=acct.accno AND gl.Docdate BETWEEN '".$lastFrom."' AND '".$lastTo."')
                    END AS debit_last,
                    CASE WHEN acct.`balance_income`=0 THEN
                        (SELECT SUM(gl.amount * gl.`ExchangeRate`) FROM `Generalledger` gl
                         WHERE gl.balaccountcode=acct.accno AND gl.Docdate <= '".$lastTo."')
                    ELSE
                        (SELECT SUM(gl.amount * gl.`ExchangeRate`) FROM `Generalledger` gl
                         WHERE gl.balaccountcode=acct.accno AND gl.Docdate BETWEEN '".$lastFrom."' AND '".$lastTo."')
                    END AS credit_last,
                    acct.`Calculation`
                FROM `acct` acct
                WHERE acct.`ReportCode` BETWEEN '".$codeFrom."' AND '".$codeTo."'
                ORDER BY acct.`ReportCode`";

        return DB_query($sql,$db);
    }
    
        Function DB_FixedAssets(){
        $ResultsX = $this->GetRangeRows('fixedassetsFrom','fixedassetsTo');
        while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT_1 = ((float)$Row['debit'] - (float)$Row['credit']);
           $this->DebitSide += $AMOUNT_1;
           $AMOUNT = ((float)$Row['debit_last'] - (float)$Row['credit_last']);
           $this->NETAssets += $AMOUNT_1 - $AMOUNT;
           $this->DebitSide_Last += $AMOUNT;
        }
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Fixed Assets(increase)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETAssets * -1;
        $this->begintotal += $this->NETAssets * -1;
    }


        Function DB_Inventory(){
        $ResultsX = $this->GetRangeRows('inventoryFrom','inventoryTo');
        while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ((float)$Row['debit'] - (float)$Row['credit']);
           $this->DebitSide += $AMOUNT;
           $AMOUNT_0 = ((float)$Row['debit_last'] - (float)$Row['credit_last']);
           $this->NETinventory += $AMOUNT - $AMOUNT_0;
           $this->DebitSide_Last += $AMOUNT_0;
        }
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Inventory(Increase)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETinventory *-1 ;
        $this->begintotal += $this->NETinventory * -1;
    }

 
        Function DB_Debtors(){
       $ResultsX = $this->GetRangeRows('DebtorsFrom','DebtorsTo');
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ((float)$Row['debit'] - (float)$Row['credit']);
           $this->DebitSide += $AMOUNT;
           $AMOUNT_0 = ((float)$Row['debit_last'] - (float)$Row['credit_last']);
           $this->NETdebtors += $AMOUNT - $AMOUNT_0;
           $this->DebitSide_Last += $AMOUNT_0;
      }
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Debtors (Increase)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETdebtors * -1 ;
        $this->begintotal += $this->NETdebtors  * -1;
    }

  
        Function DB_Bank(){
       $ResultsX = $this->GetRangeRows('BankFrom','BankTo');
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ((float)$Row['debit'] - (float)$Row['credit']);
           $this->CashClosing += $AMOUNT;
           $this->DebitSide += $AMOUNT;
           $AMOUNT_0 = ((float)$Row['debit_last'] - (float)$Row['credit_last']);
           $this->Cashcfwd += $AMOUNT_0;
           $this->DebitSide_Last += $AMOUNT_0;
      }
    }


        Function DB_CurrentLiability(){
       $ResultsX = $this->GetRangeRows('CurLiabFrom','CurLiabTo');
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ((float)$Row['debit'] - (float)$Row['credit']);
           $this->CreditSide += $AMOUNT;
           $AMOUNT_0 = ((float)$Row['debit_last'] - (float)$Row['credit_last']);
           $this->NETcreditors += $AMOUNT - $AMOUNT_0;
           $this->CreditSide_Last += $AMOUNT_0;
       }
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Creditors(Decrease)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETcreditors * -1;
        $this->begintotal += $this->NETcreditors  * -1;
    }

  
        Function DB_LongLiability(){
       $ResultsX = $this->GetRangeRows('LongLiabFrom','LongLiabTo');
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ((float)$Row['debit'] - (float)$Row['credit']);
           $this->CreditSide += $AMOUNT;
           $AMOUNT_0 = ((float)$Row['debit_last'] - (float)$Row['credit_last']);
           $this->NETloans += $AMOUNT - $AMOUNT_0;
           $this->CreditSide_Last += $AMOUNT_0;
       }
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Loans (Decrease)');
        $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETloans * -1;
        $this->begintotal += $this->NETloans  * -1;
    }

  
        Function DB_Capital(){
       $ResultsX = $this->GetRangeRows('CapitalFrom','CapitalTo');
       while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ((float)$Row['debit'] - (float)$Row['credit']);
           $this->CreditSide += $AMOUNT;
           $AMOUNT_0 = ((float)$Row['debit_last'] - (float)$Row['credit_last']);
           $this->NETcapital += $AMOUNT - $AMOUNT_0;
           $this->CreditSide_Last += $AMOUNT_0;
       }
       $this->begintotal +=  $this->NETcapital  * -1;
       $AccountCode = $this->GetID();
       $this->ResultDataSet[$AccountCode]['Description'] = _('Net Change in Capital (Decrease)');
       $this->ResultDataSet[$AccountCode]['amount'] =  $this->NETcapital* -1 ;
       $this->Profit = $this->DebitSide + $this->CreditSide;
       $this->Profit_last = $this->DebitSide_Last + $this->CreditSide_Last;
       $this->ResultDataSet['AccFund']['Description'] = _('Profit (loss) For the year');
       $this->ResultDataSet['AccFund']['amount'] = ($this->Profit)-($this->Profit_last);
       $this->begintotal += ($this->Profit)-($this->Profit_last);
    }

         
        Function DB_BankAnalysis(){
        $this->DebitSide=0;
        $this->DebitSide_Last=0;
        $ResultsX = $this->GetRangeRows('BankFrom','BankTo');
        while($Row=DB_fetch_array($ResultsX)){
           $AMOUNT = ((float)$Row['debit'] - (float)$Row['credit']);
           $this->DebitSide += $AMOUNT;
           $AMOUNT_0 = ((float)$Row['debit_last'] - (float)$Row['credit_last']);
           $this->DebitSide_Last += $AMOUNT_0;
        }
         $AccountCode = $this->GetID();
         $this->ResultDataSet[$AccountCode]['Description'] = "-----------------------------------------";
         $this->ResultDataSet[$AccountCode]['amount']='';
         $AccountCode = $this->GetID();
         $this->ResultDataSet[$AccountCode]['Description'] = _('========================================');
         $this->ResultDataSet[$AccountCode]['amount']='';
         $AccountCode = $this->GetID();
         $this->ResultDataSet[$AccountCode]['Description'] = _('Net Cash Generated');
         $this->ResultDataSet[$AccountCode]['amount'] = $this->begintotal;
         $AccountCode = $this->GetID();
         $this->ResultDataSet[$AccountCode]['Description'] = _('========================================');
         $this->ResultDataSet[$AccountCode]['amount']='';
         $AccountCode = $this->GetID();
         $this->ResultDataSet[$AccountCode]['Description'] = _('Bank Start Balance');
         $this->ResultDataSet[$AccountCode]['amount'] = $this->Cashcfwd;
         $AccountCode = $this->GetID();
         $this->ResultDataSet[$AccountCode]['Description'] = _('Cash Flow');
         $this->ResultDataSet[$AccountCode]['amount'] = $this->begintotal;
         $AccountCode = $this->GetID();
         $this->ResultDataSet[$AccountCode]['Description'] = _('Bank End Balance');
         $this->ResultDataSet[$AccountCode]['amount'] =  $this->CashClosing;
    }

}

class FinancialPeriods {
    
    var $SelectObject='';
    
    Function __construct() {
        Global $db;
        $this->SelectObject='<tr><td>Select Year</td><td><select name="Financial_Periods">';
        $ResultIndex=DB_query("Select `periodno`,MAX(`end_date`) as year from `FinancialPeriods` Group by `periodno` order by `periodno` desc", $db);
        while($row=DB_fetch_array($ResultIndex)){
             $this->SelectObject .='<option value="'.$row['periodno'].'" '.(($_POST['Financial_Periods']==trim($row['periodno']))?'selected="selected"':'').'  >'.$row['year'].'</option>';
        }
        $this->SelectObject .='</select></td></tr>';
    }
    
    
    function Get(){
        echo $this->SelectObject;
    }
}

?>
