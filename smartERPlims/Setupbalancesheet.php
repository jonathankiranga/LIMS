<?php
include('includes/session.inc');
$Title = _('Balance Sheet Setup');
include('includes/header.inc');
?>
<style type="text/css">
    
  .accountcode {
        text-align: right;
    }
    
 .footer {
  padding-top: 40px;
  padding-bottom: 40px;
  margin-top: 40px;
  border-top: 1px solid #eee;
}

DIV.space{
    padding-left:  10px;
    padding-right:  10px;
}
/* Main marketing message and sign up button */
.jumbotron {
  text-align: center;
  background-color: transparent;
}
.jumbotron .btn {
  padding: 14px 24px;
  font-size: 21px;
}

/* Customize the nav-justified links to be fill the entire space of the .navbar */

.nav-justified {
  background-color: #eee;
  border: 1px solid #ccc;
  border-radius: 5px;
}
.nav-justified > li > a {
  padding-top: 15px;
  padding-bottom: 15px;
  margin-bottom: 0;
  font-weight: bold;
  color: #777;
  text-align: center;
  background-color: #e5e5e5; /* Old browsers */
  background-image: -webkit-gradient(linear, left top, left bottom, from(#f5f5f5), to(#e5e5e5));
  background-image: -webkit-linear-gradient(top, #f5f5f5 0%, #e5e5e5 100%);
  background-image:      -o-linear-gradient(top, #f5f5f5 0%, #e5e5e5 100%);
  background-image:         linear-gradient(to bottom, #f5f5f5 0%,#e5e5e5 100%);
  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f5f5f5', endColorstr='#e5e5e5',GradientType=0 ); /* IE6-9 */
  background-repeat: repeat-x; /* Repeat the gradient */
  border-bottom: 1px solid #d5d5d5;
}
.nav-justified > .active > a,
.nav-justified > .active > a:hover,
.nav-justified > .active > a:focus {
  background-color: #ddd;
  background-image: none;
  -webkit-box-shadow: inset 0 3px 7px rgba(0,0,0,.15);
          box-shadow: inset 0 3px 7px rgba(0,0,0,.15);
}
.nav-justified > li:first-child > a {
  border-radius: 5px 5px 0 0;
}
.nav-justified > li:last-child > a {
  border-bottom: 0;
  border-radius: 0 0 5px 5px;
}

@media (min-width: 768px) {
  .nav-justified {
    max-height: 52px;
  }
  .nav-justified > li > a {
    border-right: 1px solid #d5d5d5;
    border-left: 1px solid #fff;
  }
  .nav-justified > li:first-child > a {
    border-left: 0;
    border-radius: 5px 0 0 5px;
  }
  .nav-justified > li:last-child > a {
    border-right: 0;
    border-radius: 0 5px 5px 0;
  }
}

/* Responsive: Portrait tablets and up */
@media screen and (min-width: 768px) {
  /* Remove the padding we set earlier */
  .masthead,
  .marketing,
  .footer {
    padding-right: 0;
    padding-left: 0;
  }
}
</style>

<script type="text/javascript">
    
    function FixedAssets(){
       
       var Sf = document.getElementById('fixedassetsFrom');
       var St = document.getElementById('fixedassetsTo');
        
        if(Sf.value > St.value ){
            
            document.getElementById('fixedassetsTo').value=Sf.value.toString();
        }
      
        
    }
    
    function validateStock(){
       
       var Sf = document.getElementById('inventoryFrom');
       var St = document.getElementById('inventoryTo');
        
        if(Sf.value > St.value ){
            document.getElementById('inventoryTo').value=Sf.value.toString();
        }
      
        
    }
    
    function Debtors(){
       
       var Sf = document.getElementById('DebtorsFrom');
       var St = document.getElementById('DebtorsTo');
        
        if(Sf.value > St.value ){
            document.getElementById('DebtorsTo').value=Sf.value.toString();
        }
      
        
    }
    
    function Bank(){
       
       var Sf = document.getElementById('BankFrom');
       var St = document.getElementById('BankTo');
        
        if(Sf.value > St.value ){
            document.getElementById('BankTo').value=Sf.value.toString();
        }
      
        
    }
    
    function CurrentLiabilities(){
       
       var Sf = document.getElementById('CurLiabFrom');
       var St = document.getElementById('CurLiabTo');
        
        if(Sf.value > St.value ){
            document.getElementById('CurLiabTo').value=Sf.value.toString();
        }
      
        
    }
    
    function LongTermLiabilities(){
       
       var Sf = document.getElementById('LongLiabFrom');
       var St = document.getElementById('LongLiabTo');
        
        if(Sf.value > St.value ){
            document.getElementById('LongLiabTo').value=Sf.value.toString();
        }
      
        
    }
    
    function Capital(){
       
       var Sf = document.getElementById('CapitalFrom');
       var St = document.getElementById('CapitalTo');
        
        if(Sf.value > St.value ){
            document.getElementById('CapitalTo').value=Sf.value.toString();
        }
      
        
    }
    
</script>
<?php

if(isset($_POST['CreateIncomeReport'])){
        
       $sql= sprintf("UPDATE Accountsetup SET "
               . " fixedassetsFrom='%s' , fixedassetsTo='%s' ,
                   inventoryFrom='%s' , inventoryTo='%s' ,
                   DebtorsFrom='%s' , DebtorsTo='%s' ,
                   BankFrom='%s' , BankTo='%s' ,
                   CurLiabFrom='%s'  , CurLiabTo='%s'  ,
                   LongLiabFrom='%s'  , LongLiabTo='%s'  ,
                   CapitalFrom='%s'  , CapitalTo='%s'  ",
               $_POST['fixedassetsFrom'],$_POST['fixedassetsTo'],
               $_POST['inventoryFrom'],$_POST['inventoryTo'],
               $_POST['DebtorsFrom'],$_POST['DebtorsTo'],
               $_POST['BankFrom'],$_POST['BankTo'],
               $_POST['CurLiabFrom'],$_POST['CurLiabTo'],
               $_POST['LongLiabFrom'],$_POST['LongLiabTo'],
               $_POST['CapitalFrom'],$_POST['CapitalTo']);
      
       DB_query($sql,$db);
    
}

$SQL='Select 
   fixedassetsFrom , fixedassetsTo ,
   inventoryFrom , inventoryTo ,
   DebtorsFrom , DebtorsTo ,
   BankFrom , BankTo ,
   CurLiabFrom , CurLiabTo ,
   LongLiabFrom , LongLiabTo ,
   CapitalFrom , CapitalTo 
   from Accountsetup';
$ResultIndex=DB_query($SQL, $db);
while($row=DB_fetch_array($ResultIndex)){
        $_POST['fixedassetsFrom']=trim($row['fixedassetsFrom']);
        $_POST['fixedassetsTo']=trim($row['fixedassetsTo']);
        $_POST['inventoryFrom']=trim($row['inventoryFrom']);
        $_POST['inventoryTo']=trim($row['inventoryTo']);
        $_POST['DebtorsFrom']=trim($row['DebtorsFrom']);
        $_POST['DebtorsTo']=trim($row['DebtorsTo']);
        $_POST['BankFrom']=trim($row['BankFrom']);
        $_POST['BankTo']=trim($row['BankTo']);
        $_POST['CurLiabFrom']=trim($row['CurLiabFrom']);
        $_POST['CurLiabTo']=trim($row['CurLiabTo']);
        $_POST['LongLiabFrom']=trim($row['LongLiabFrom']);
        $_POST['LongLiabTo']=trim($row['LongLiabTo']);
        $_POST['CapitalFrom']=trim($row['CapitalFrom']);
        $_POST['CapitalTo']=trim($row['CapitalTo']);
}
 
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Balance Sheet Setup') .'" alt="" />'
    . ' ' . _('Balance Sheet Setup') . '</p>';

 echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
 echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />'
    . '<div class="container"><nav><legend>'. _('Balance Sheet Setup').'</legend></div></nav></div>
    <div class="space"><table class="table-bordered"><tr><td colspan="4" rowspan="16">'; 
     ShowChart();
 echo '</td></tr>
        <tr><td colspan="2"><label for="SalesFrom">'. _('Fixed Assets Ranges From').' </label></td></tr>';
        FixedAssets();
       echo '<tr><td colspan="2"><label for="stockFrom">'. _('Inventory Accounts Ranges From').'</label></td></tr>';
        Stock();
       echo '<tr><td colspan="2"><label for="PurchasesFrom">'. _('Debtors Accounts Ranges From').' </label></td></tr>';
        Debtors();
       echo '<tr><td colspan="2"><label for="ExpensesFrom">'. _('Bank Accounts Ranges From').'</label></td></tr>';
        Bank();
       echo '</td></tr>';
       echo '<tr><td colspan="2"><label for="ExpensesFrom">'. _('Current liabilities Accounts Ranges From').'</label></td></tr>';
        CurrentLiabilities();
        echo '</td></tr>';
       echo '<tr><td colspan="2"><label for="ExpensesFrom">'. _('Long term liabilities Accounts Ranges From').'</label></td></tr>';
        LongTermLiabilities();
       echo '</td></tr>';
       echo '<tr><td colspan="2"><label for="ExpensesFrom">'. _('Capital Accounts Ranges From').'</label></td></tr>';
        Capital();
      echo '</td></tr>
       <tr><td><fieldset><button type="submit" name="CreateIncomeReport">Save Report Format</button></fieldset></td></tr></table></div>
 </form>';
 
 echo '<div class="space"><table class="table table-bordered">'
   . '<thead><tr><th>Report Line</th><th>From Account</th><th>To Account</th></tr></thead>' ;
 echo '<tr><td>Fixed Assets</td><td>'.$_POST['fixedassetsFrom'].'</td><td>'. $_POST['fixedassetsTo'].'</td></tr>';
 echo '<tr><td>Inventory</td><td>'.$_POST['inventoryFrom'].'</td><td>'. $_POST['inventoryTo'].'</td></tr>';
 echo '<tr><td>Debtors</td><td>'.$_POST['DebtorsFrom'].'</td><td>'.$_POST['DebtorsTo'].'</td></tr>';
 echo '<tr><td>Bank</td><td>'.$_POST['BankFrom'].'</td><td>'. $_POST['BankTo'].'</td></tr>';
 echo '<tr><td>Current Liabilities</td><td>'.$_POST['CurLiabFrom'].'</td><td>'.$_POST['CurLiabTo'].'</td></tr>';
 echo '<tr><td>Long Term Liabilities</td><td>'.$_POST['LongLiabFrom'].'</td><td>'.$_POST['LongLiabTo'].'</td></tr>';
 echo '<tr><td>Capital</td><td>'.$_POST['CapitalFrom'].'</td><td>'.$_POST['CapitalTo'].'</td></tr>';
 echo '</table></div>';
 
 
 Function FixedAssets(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="fixedassetsFrom" id="fixedassetsFrom"'
     . ' value="'.$_POST['fixedassetsFrom'].'" pattern="^[0-9 ]*" '
             . 'class="accountcode" maxlength="10" size="20" onMouseOut="FixedAssets();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="fixedassetsTo" id="fixedassetsTo" '
     . 'value="'.$_POST['fixedassetsTo'].'" pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"  onMouseOut="FixedAssets();"/>';
     echo '</td></tr>';
}
 
 Function Stock(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="inventoryFrom" id="inventoryFrom"'
     . ' value="'.$_POST['inventoryFrom'].'"  pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"   onMouseOut="validateStock();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="inventoryTo" id="inventoryTo"'
     . '  value="'.$_POST['inventoryTo'].'"  pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"   onMouseOut="validateStock();"/>';
     echo '</td></tr>';
  }
 
 Function Debtors(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="DebtorsFrom" id="DebtorsFrom" '
     . 'value="'.$_POST['DebtorsFrom'].'"  pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"  onMouseOut="Debtors();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="DebtorsTo" id="DebtorsTo" '
     . 'value="'.$_POST['DebtorsTo'].'"  pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"  onMouseOut="Debtors();"/>';
     echo '</td></tr>';
  }
 
 Function  Bank(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="BankFrom" id="BankFrom" '
     . 'value="'.$_POST['BankFrom'].'"   pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"  onMouseOut="Bank();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="BankTo" id="BankTo" '
     . 'value="'.$_POST['BankTo'].'"   pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20" onMouseOut="Bank();"/>';
     echo '</td></tr>';
 }
 
 Function  CurrentLiabilities(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="CurLiabFrom" id="CurLiabFrom" '
     . 'value="'.$_POST['CurLiabFrom'].'"   pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"  onMouseOut="CurrentLiabilities();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="CurLiabTo" id="CurLiabTo" '
     . 'value="'.$_POST['CurLiabTo'].'"   pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20" onMouseOut="CurrentLiabilities();"/>';
     echo '</td></tr>';
 }
 
 Function  LongTermLiabilities(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="LongLiabFrom" id="LongLiabFrom" '
     . 'value="'.$_POST['LongLiabFrom'].'"   pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"  onMouseOut="LongTermLiabilities();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="LongLiabTo" id="LongLiabTo" '
     . 'value="'.$_POST['LongLiabTo'].'"   pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20" onMouseOut="LongTermLiabilities();"/>';
     echo '</td></tr>';
 }
 
 Function  Capital(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="CapitalFrom" id="CapitalFrom" '
     . 'value="'.$_POST['CapitalFrom'].'"   pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20"  onMouseOut="Capital();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="CapitalTo" id="CapitalTo" '
     . 'value="'.$_POST['CapitalTo'].'"   pattern="^[0-9 ]*"  '
             . 'class="accountcode" maxlength="10" size="20" onMouseOut="Capital();"/>';
     echo '</td></tr>';
 }
 
 Function ShowChart(){
     
     Global $db;
     
      echo '<DIV class="table-responsive" >'
            . '<table class="table-bordered">'
            . '<thead><tr>'
            . '<th>ACCOUNT NO</th>'
            . '<th>ACCOUNT NAME</th>'
            . '<th>BALANCE/INCOME</th>'
            . '<th>Formula</th>'
            . '<th>TYPE</th>'
            . '</thead></tr>';
      
    $BalanceSheet=array();
    $BalanceSheet[0]="Balance Sheet";
    $BalanceSheet[1]="Profit and Loss";
    
    $AccountType=array();
    $AccountType[0]="Posting";
    $AccountType[1]="Heading";
    $AccountType[2]="Total";
    $AccountType[3]="Begin-Total";
    $AccountType[4]="End-Total";

$REsults = DB_AccountBalance();
while($rows=DB_fetch_array($REsults)){
 
    echo '<tr><td><label>'.$rows['ReportCode'].'</label></td>';
    echo '<td>'.$rows['accdesc'].'</td>';
    echo '<td>'.$BalanceSheet[$rows['balance_income']].'</td>';
    echo '<td>'.trim($rows['Calculation']).'</td>';
    echo '<td>'.$AccountType[$rows['ReportStyle']].'</td>';
    echo '</tr>' ;

}

echo '</table></DIV>';
 }
 
?>
