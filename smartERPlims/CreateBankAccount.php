<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');

$Title = _('Create Bank Accounts');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if(isset($_POST['update'])){
    
   $sql= sprintf("UPDATE `BankAccounts`
                    SET  `bankName`='%s'
                        ,`AccountNo`='%s'
                        ,`BranchCode`='%s'
                        ,`BranchName`='%s'
                        ,`lastChequeno`='%s'
                        ,`PostingGroup`='%s'
                        ,`Makeinactive`='%s'
                        ,`Fluctuation`='%s'
                        ,`lastreconbalance`=%f 
                        ,`AcctName`='%s' 
                        ,`bankCode`='%s' 
                        ,`swiftcode`='%s'
                    WHERE `accountcode`='%s' ",
                         $_POST['bankName'],$_POST['AccountNo'],$_POST['BranchCode'],
                         $_POST['BranchName'], $_POST['lastChequeno'], $_POST['PostingGroup'],
                         $_POST['Makeinactive'],$_POST['Fluctuation'],$_POST['lastreconbalance'],$_POST['AcctName']
                          ,$_POST['bankCode']
                          ,$_POST['swiftcode'],
                         $_POST['BankCode']);
                
 DB_query($sql,$db);
 
 $sql=sprintf("update `acct` set `direct`=0 where accno='%s'",$_POST['AccountNo']);
 DB_query($sql,$db);  
 
 Unset($_POST);
}
    
if(isset($_POST['submit'])){
    $sql=sprintf("update `acct` set `direct`=0 where accno='%s'",$_POST['AccountNo']);
   DB_query($sql,$db);
 
   $accountcode = Triger_bankaccounts();
 
    $sql=sprintf("INSERT INTO `BankAccounts`
           (`accountcode`
           ,`bankName`
           ,`currency`
           ,`AccountNo`
           ,`BranchCode`
           ,`BranchName`
           ,`lastChequeno`
           ,`PostingGroup`
           ,`Fluctuation`
           ,`lastreconbalance`
           ,`AcctName`
           ,`bankCode`
           ,`swiftcode`)
     VALUES
           ('%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%f'
           ,'%s'
           ,'%s'
           ,%f
           ,'%s'
           ,'%s'
           ,'%s')",
            $accountcode
           ,$_POST['bankName']
           ,$_POST['currency']
           ,$_POST['AccountNo']
           ,$_POST['BranchCode']
           ,$_POST['BranchName']
           ,$_POST['lastChequeno'] ?? '1'
           ,$_POST['PostingGroup']
           ,$_POST['Fluctuation']
           ,$_POST['lastreconbalance']
           ,$_POST['AcctName']
           ,$_POST['bankCode']
           ,$_POST['swiftcode']);
    
   DB_query($sql,$db);
   
   Unset($_POST);
}

if(isset($_GET['C0d3'])){
    $bankcodeselected=$_GET['C0d3'];
}elseif($_POST['BankCode']){
    $bankcodeselected=$_POST['BankCode'];
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Create Bank accounts') .'" alt="" />' . ' ' . _('Create Bank accounts') . '</p>';

echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if($bankcodeselected){
    echo '<input type="hidden" name="BankCode" value="' .$bankcodeselected. '" />';

    $sql="SELECT 
           `accountcode`
          ,`bankName`
          ,`currency`
          ,`lastreconcileddate`
          ,`AccountNo`
          ,`BranchCode`
          ,`BranchName`
          ,`lastreconbalance`
          ,`lastChequeno`
          ,`PostingGroup`
          ,`Fluctuation`
          ,`Makeinactive`
          ,lastreconbalance
          ,`AcctName`
          ,`bankCode`
          ,`swiftcode`
      FROM `BankAccounts`
      where `accountcode`='".$bankcodeselected."'";
    
    $ResultIndex=DB_query($sql,$db);
    $rowbanks = DB_fetch_row($ResultIndex);
    
    $_POST['bankName']=$rowbanks[1];
    $_POST['currency']=$rowbanks[2];
    $_POST['lastreconcileddate']=$rowbanks[3];
    $_POST['AccountNo']=$rowbanks[4];
    $_POST['BranchCode']=$rowbanks[5];
    $_POST['BranchName']=$rowbanks[6];
    $_POST['lastreconbalance']=$rowbanks[7];
    $_POST['lastChequeno']=$rowbanks[8];
    $_POST['PostingGroup']=$rowbanks[9];
    $_POST['Fluctuation']=$rowbanks[10];
    $_POST['Makeinactive']=$rowbanks[11];
    $_POST['lastreconbalance']=$rowbanks[12];
    $_POST['AcctName']=$rowbanks[13];
    $_POST['bankCode']=$rowbanks[14];
    $_POST['swiftcode']=$rowbanks[15];

}

echo '<table class="table-bordered table-compact">'
. '<tr><td>Bank</td><td><input type="text" name="bankName" value="'.$_POST['bankName'].'" maxlength="50"  required="required"  onmouseleave="CopyFieldValue(this.value,\'AcctName\')"/></td><td>AC NAME</td><td><input id="AcctName" type="text" name="AcctName" value="'.$_POST['AcctName'].'" maxlength="50" required="required"/></td></tr>'
. '<tr><td>Bank Account No</td><td><input type="text" name="AccountNo" value="'.$_POST['AccountNo'].'" maxlength="20" /></td><td>Bank Code</td><td><input type="text" name="bankCode" value="'.$_POST['bankCode'].'" maxlength="50"/></td></tr>'
. '<tr><td>Bank Branch Code</td><td><input type="text" name="BranchCode" value="'.$_POST['BranchCode'].'" maxlength="10" /></td><td>Swift Code</td><td><input type="text" name="swiftcode" value="'.$_POST['swiftcode'].'" maxlength="50"/></td></tr>'
. '<tr><td>Bank Branch Name</td><td><input type="text" name="BranchName" value="'.$_POST['BranchName'].'" maxlength="50" /></td><td>Make Inactive</td><td><select name="Makeinactive" >'
. '<option '.(($_POST['Makeinactive']==0)?'selected="selected"':'').' value="0">No</option>'
. '<option '.(($_POST['Makeinactive']==1)?'selected="selected"':'').' value="1">Yes</option>'
. '</select></td></tr>';
echo '<tr><td>Last Cheque No</td><td><input type="text" name="lastChequeno" value="'.$_POST['lastChequeno'].'" maxlength="10" pattern="[0-9]*" /></td><td></td><td></td></tr>';
echo '<tr><td>Bank Reconciliation Start Balance</td><td><input type="text" name="lastreconbalance" value="'.$_POST['lastreconbalance'].'" maxlength="10" class="number" /></td><td></td><td></td></tr>';
echo '<tr><td></td><td></td><td>Select Currency Name</td><td><select name="currency" required="required">';
foreach ($CurrencyName as $key => $value) {
    echo '<option value="'.$key.'" '.(($key==$_POST['currency'])?'selected="selected"':'').'  >'.$value."</option>";
}
echo '</select></td></tr>';
echo '<tr><td></td><td></td><td>Select GL Bank Account</td><td><select name="PostingGroup" required="required">';

$ResultIndex=DB_query('select `accno`,`accdesc` from `acct` where balance_income=0 order by reportcode asc',$db);
while($ROW=DB_fetch_array($ResultIndex)){
    if(trim($_POST['PostingGroup'])==trim($ROW['accno'])){
        echo '<option value="'.$ROW['accno'].'" selected="selected">'.$ROW['accdesc'].'</option>';
    }else{
         echo '<option value="'.$ROW['accno'].'">'.$ROW['accdesc'].'</option>';
    }
 }
echo '</select></td></tr>';



echo '<tr><td colspan="2"><input type="submit" name="refesh" value="Refresh"/></td><td colspan="2">';

if(isset($bankcodeselected)){
    echo '<input type="submit" name="update" value="update Details"/>';
}else{
    echo '<input type="submit" name="submit" value="Insert New Account"/>';
}

echo '</td></tr></table>';
echo '</div></form>' ;

$sql="SELECT `accountcode`
      ,`bankName`
      ,`BankAccounts`.`currency`
      ,`lastreconcileddate`
      ,`AccountNo`
      ,`BranchCode`
      ,`BranchName`
      ,`lastreconbalance`
      ,`lastChequeno`
      ,`Makeinactive`
      ,`PostingGroup`
      ,`Fluctuation`
  FROM `BankAccounts`";
$BankAccounts=array();
echo '<div class="container">';
echo '<table class="table-bordered"><tr>'
        . '<th>Bank</th>'
        . '<th>Branch Name</th>'
        . '<th>Currency</th>'
        . '<th>Account No</th>'
        . '<th>Last Reconciled on</th>'
        . '<th class="number">Last Reconciliation Balance</th>'
        . '<th>Last Cheque No</th>'
        . '<th>Bank GL</th>'
        . '<th>Exchange G/L</th>'
        . '<th>Active</th>'
        . '</tr>';

        $ResultIndex=DB_query($sql,$db);
        while($rows=DB_fetch_array($ResultIndex)){
            $BankAccounts[]=$rows;
        }
        
        foreach ($BankAccounts as $rows) {
           $PostingGroup = GetGL($rows['PostingGroup'],$db);
            $Fluctuation = GetGL($rows['Fluctuation'],$db);
            $lastreconcileddate=(Is_date(ConvertSQLDate($rows['lastreconcileddate']))?ConvertSQLDate($rows['lastreconcileddate']):'');
         
            echo sprintf('<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td class="number">%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $_SERVER['PHP_SELF'].'?C0d3='.$rows['accountcode'],$rows['bankName'],
         //   $rows['bankName'],
            $rows['BranchName'],
            $rows['currency'],
            $rows['AccountNo'],
            $lastreconcileddate,  
            number_format($rows['lastreconbalance'],2),
            $rows['lastChequeno'],
           $PostingGroup,
           $Fluctuation,
           ($rows['Makeinactive']==1?'Inactive':'Active'));
        }
echo '</table></div>';
        
include('includes/footer.inc');



Function GetGL($account,$db){
 
    $sql="SELECT `accdesc` FROM `acct`  where `accno`='".trim($account)."'";
    $ResultIndex = DB_query($sql,$db);
    $row = DB_fetch_row($ResultIndex);
   
    return $row[0];
}
    
?>

