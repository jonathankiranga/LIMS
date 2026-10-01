<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Cash Book Register');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/budgetbalance.php');
include('includes/chartbalancing.inc');

Global $statementstartdate, $DRamount, $CRamount,$tableObject, $PostingGroup,$Uncleared;
      
$DRamount=0; $CRamount=0; $Openbalance=0; $Difference=0;$Uncleared=0;
$SQL = array() ;
$banklist = array();

$resultindex=DB_query("SELECT `accountcode`,`bankName`,`currency`,`lastreconcileddate`,`AccountNo`,`BranchCode`,`BranchName`,`lastreconbalance`,`lastChequeno`,`PostingGroup`   FROM `BankAccounts`", $db);
while($row=DB_fetch_array($resultindex)){
    $banklist[trim($row['accountcode'])]=$row['bankName'].' '.$row['BranchName'].' '.$row['currency'];
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Cash Book Register') .'" alt="" />'. _('Cash Book Register') . '</p>';
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
echo '<table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">';
echo  constructBanks(); 

if(isset($_POST['Bank_Code'])){
    $TRBanks = new ShowBankAccounts();
    $ArrayBanks = $TRBanks->GetBankDetails($_POST['Bank_Code']);
    $PostingGroup       = $ArrayBanks['PostingGroup'];
    $statementstartdate = $ArrayBanks['lastreconcileddate'];
    $Openbalance        = $ArrayBanks['lastreconbalance'];
    
      
    
   $tableObject='<table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
        <thead>
            <tr>
                <th>Target</th>
                 <th>Search text</th>
                <th>Treat as regex</th>
                <th>Use smart search</th>
            </tr>
        </thead>
        <tbody>
            <tr id="filter_global">
                <td>Global search</td>
                <td align="center"><input type="text" class="global_filter" id="global_filter"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_regex"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_smart" checked="checked"></td>
    
            </tr>
            <tr id="filter_col1" data-column="0">
                <td>Column - DATE</td>
                     <td align="center"><input type="text" class="column_filter" id="col0_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_smart" checked="checked"></td>
      
            </tr>
            <tr id="filter_col2" data-column="1">
                <td>Column - Cheque No</td>
               <td align="center"><input type="text" class="column_filter" id="col1_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_smart" checked="checked"></td>
            </tr>
            <tr id="filter_col3" data-column="2">
                <td>Column - Narration</td>
          <td align="center"><input type="text" class="column_filter" id="col2_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col2_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col2_smart" checked="checked"></td>
              </tr>
            <tr id="filter_col4" data-column="3">
                <td>Column - Narration 2</td>
           <td align="center"><input type="text" class="column_filter" id="col3_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col3_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col3_smart" checked="checked"></td>
              </tr>
          <tr id="filter_col5" data-column="4">
                <td>Column - AMOUNT</td>
                 <td align="center"><input type="text" class="column_filter" id="col4_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col4_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col4_smart" checked="checked"></td>
        </tr>
            
        </tbody>
    </table>';

$tableObject.= '<table class="register display" style="width:100%">'
        . '<thead><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>AMOUNT</th>'
        . '</tr></thead><tbody>';

    $SQL="SELECT 
       `bankcode`
      ,`DocDate`
      ,`systypes_1`.`typename`
      ,`DocumentNo`
      ,(case TransType 
	   when 'DR' then (select customer from debtors where debtors.itemcode=banktransactions.itemcode)  
	   when 'CR' then (select customer from creditors where creditors.itemcode=banktransactions.itemcode)  
	   else (select customer from debtors where debtors.itemcode=banktransactions.itemcode) end) as Payee
      ,`journal`
      ,`amount`
      ,`narrative`
      ,`exchangerate`
      ,IFNULL(`cleared`,0) AS `cleared`
      ,`BankTransactions`.`reconciled`
  FROM `BankTransactions` 
  left join `systypes_1` on `BankTransactions`.`doctype`=`systypes_1`.typeid 
  where (`bankcode`='".$_POST['Bank_Code']."')  order by `DocDate` Asc";
 $ResultIndex=DB_query($SQL,$db);
while($row=DB_fetch_array($ResultIndex)){
         
     $tableObject .= SprintF('<tr>'
    . '<td>%s</td>'
    . '<td>%s</td>' 
    . '<td>%s</td>'
    . '<td>%s</td>'
    . '<td class="number">%s</td>'
    . '</tr>',ConvertSQLDate($row['DocDate']),
            $row['DocumentNo'],
            trim($row['narrative']),
            trim($row['typename']).'  '.trim($row['Payee']),
            $row['amount']);
    }

  $tableObject .= '</tbody>
        <tfoot>
            <tr><th>Date</th><th>Cheque No</th>'
        . '<th>Narration</th><th>Narration 2</th><th>AMOUNT</th>'
        . '</tr>
            </tr>
        </tfoot></table>';
         

}
 

echo $tableObject;

echo '</div></form>' ;

include('includes/footer.inc');

function constructBanks() {
   Global $db,$banklist;
    
   $banknames = $banklist[trim($_POST['Bank_Code'])];
      
    if(isset($_POST['Bank_Code'])){
        $BankObject='<tr><td>Select Bank:</td>'
                . '<td><input type="text" id="bankselected" name="Bank_Code" readonly="readonly" value="'.$_POST['Bank_Code'].'" /></td>'
                . '<td><input type="submit" name="cancel" value="Refresh"/>'. $banknames.'</td></tr>';
    
        echo  $BankObject;
        
    } else{    
        
        $BankObject='<tr><td>Select Bank:</td>'
                . '<td colspan="3"><Select id="bankselected" name="Bank_Code" required="required">';
        $resultindex=DB_query("SELECT `accountcode`,`bankName`,`currency`,"
                . "`lastreconcileddate`,`AccountNo`,`BranchCode`,`BranchName`,"
                . "`lastreconbalance`,`lastChequeno`,`PostingGroup` "
                . " FROM `BankAccounts`", $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                $BankObject .= '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                $BankObject .= '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
       }
        
        $BankObject .='</select>';
        $BankObject .= '<input type="submit" name="GetBankData" value="Refresh"/>';
        $BankObject .= '<br/><em>Click and Please wait for the transactions to load</em></td></tr>';
        
        echo $BankObject;
    
     }
}


?>
