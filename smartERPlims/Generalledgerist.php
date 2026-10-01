<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Setting Up Company Accounts');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/chartbalancing.inc');
include('includes/AccountBalance.inc');

echo '<p class="page_title_text">'. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('General ledger') .'" alt="" />'. ' ' . _('General ledger') . '</p>';
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post" id="ledger"><div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
   
    $ClassCalc = new Calculator();
    $ClassCalc->Reset();

    $BalanceSheet=array();
    $BalanceSheet[0]="Balance Sheet";
    $BalanceSheet[1]="Profit and Loss";
    
    $AccountType=array();
    $AccountType[0]="Posting";
    $AccountType[1]="Heading";
    $AccountType[2]="Total";
    $AccountType[3]="Begin-Total";
    $AccountType[4]="End-Total";
      
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
            . '<td><input type="text" name="filter"  class="myInput" id="myAccountInput" onkeyup="mysetAccountFunction()"/></td></tr></table>';
            
   echo '<DIV id="container" >'
            . '<table class="table-striped table-bordered" id="myAccountTable">'
            . '<thead><tr>'
            . '<th>ACCOUNT NO</th>'
            . '<th>ACCOUNT NAME</th>'
            . '<th>BALANCE/INCOME</th>'
            . '<th>Formula</th>'
            . '<th>TYPE</th>'
            . '<th>BALANCE</th>'
            . '</thead></tr>';
  
    $k =1; 
$URL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
   $REsults = DB_AccountBalance();

while($rows=DB_fetch_array($REsults)){
    
    $amount = $ClassCalc->Get($rows);

    echo '<tr>'
    . '<td><a href="ChartofAccounts.php?AKD='.trim($rows['accno']).'"> Code:'.$rows['ReportCode'].' </a></td>';
    if($rows['ReportStyle']==0){
      echo '<td><a href="LedgerReports.php?Drill='.trim($rows['accno']).'">'.$rows['accdesc'].'</a></td>';
    }else{
      echo '<td>'.$rows['accdesc'].'</a></td>';
    }
    echo '<td>'.$BalanceSheet[$rows['balance_income']].'</td>';
    echo '<td>'.$rows['Calculation'].'</td>';
    echo '<td>'.$AccountType[$rows['ReportStyle']].'</td>';
    if($rows['ReportStyle']==1 or $rows['ReportStyle']==3){
       echo '<td class="number"></td>';
    } else {
      echo '<td class="number">'.number_format($amount,2).'</td>';
    }
    echo '</tr>' ;

}


echo '</table></DIV>';
echo '</div></form>';
include('includes/footer.inc');
?>
