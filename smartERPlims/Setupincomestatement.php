<?php
include('includes/session.inc');
$Title = _('Income Statement Setup');
include('includes/header.inc');?>
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
    padding-bottom:  10px;
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
<?php

if(isset($_POST['CreateIncomeReport'])){
    $errors=false;
    if(mb_strlen($_POST['SalesFrom'])>0 and mb_strlen($_POST['SalesTo'])>0){
    }else{
        $errors=true;
        prnMsg('You have not selected the Sales/Income accounts','warn');
    }
    
    if(mb_strlen($_POST['stockFrom'])>0 and mb_strlen($_POST['stockTo'])>0){
    }else{
        $errors=true;
        prnMsg('You have not selected the opening/closing stock accounts','warn');
    }
    
    if(mb_strlen($_POST['PurchasesFrom'])>0 and mb_strlen($_POST['PurchasesTo'])>0){
    }else{
        $errors=true;
        prnMsg('You have not selected the Purchases accounts','warn');
    }
    
    if(mb_strlen($_POST['ExpensesFrom'])>0 and mb_strlen($_POST['ExpensesTo'])>0){
    }else{
        $errors=true;
        prnMsg('You have not selected the Expense accounts','warn');
    }
    
    
    if($errors==false){
        
       $sql= sprintf("Update Accountsetup SET "
               . " salesfrom='%s', salesto='%s',"
               . " stockfrom='%s', stockto='%s',"
               . " purchasesfrom='%s', purchasesto='%s',"
               . " expensefrom='%s', expenseto='%s' ",
               $_POST['SalesFrom'],$_POST['SalesTo'],
               $_POST['stockFrom'],$_POST['stockTo'],
               $_POST['PurchasesFrom'],$_POST['PurchasesTo'],
               $_POST['ExpensesFrom'],$_POST['ExpensesTo']);
      
       DB_query($sql,$db);
    }
}

$SQL='Select salesfrom,salesto,stockfrom,stockto,purchasesfrom,purchasesto,expensefrom,expenseto from Accountsetup';
$ResultIndex=DB_query($SQL, $db);
while($row=DB_fetch_array($ResultIndex)){
        $_POST['SalesFrom'] = trim($row['salesfrom']);
        $_POST['SalesTo'] = trim($row['salesto']);
        $_POST['stockFrom'] = trim($row['stockfrom']);
        $_POST['stockTo'] = trim($row['stockto']);
        $_POST['PurchasesFrom'] = trim($row['purchasesfrom']);
        $_POST['PurchasesTo'] = trim($row['purchasesto']);
        $_POST['ExpensesFrom'] = trim($row['expensefrom']);
        $_POST['ExpensesTo'] = trim($row['expenseto']);
}
 
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Income Statement Setup') .'" alt="" />'
    . ' ' . _('Income Statement Setup') . '</p>';

 echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
 echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />'
    . '<div class="container"><nav><legend>'. _('Income Statement Setup').'</legend></div></nav></div>
        <div class="space"><table class="table table-bordered"><tr><td colspan="4" rowspan="10">'; 
 ShowChart();
 echo '</td></tr>
        <tr><td colspan="2"><label for="SalesFrom">'. _('Sales Accounts Ranges From').' </label></td></tr>';
        Sales();
        echo '<tr><td colspan="2"><label for="stockFrom">'. _('Stock Accounts Ranges From').'</label></td></tr>';
        Stock();
        echo '<tr><td colspan="2"><label for="PurchasesFrom">'. _('Purchases').' </label></td></tr>';
        purchases();
        echo '<tr><td colspan="2"><label for="ExpensesFrom">'. _('Expenses').'</label></td></tr>';
        Expenses();
        echo '</td></tr><tr><td>
       <fieldset><button type="submit" name="CreateIncomeReport">Create Report Format</button>
         </fieldset></td></tr></table></div>
 </form>';
 
        
 echo '<div class="space"><table class="table table-bordered"><thead><tr><th>Report Line</th><th>From Account</th><th>To Account</th></tr></thead>' ;
 echo '<tr><td>SALES/INCOME ACCOUNTS</td><td>'.$_POST['SalesFrom'].'</td><td>'. $_POST['SalesTo'].'</td></tr>';
 echo '<tr><td>OPEN STOCK ACCOUNTS</td><td>'.$_POST['stockFrom'].'</td><td>'. $_POST['stockTo'].'</td></tr>';
 echo '<tr><td>PURCHASE ACCOUNTS</td><td>'.$_POST['PurchasesFrom'].'</td><td>'.$_POST['PurchasesTo'].'</td></tr>';
 echo '<tr><td>CLOSING STOCK ACCOUNTS</td><td>'.$_POST['stockFrom'].'</td><td>'. $_POST['stockTo'].'</td></tr>';
 echo '<tr><td>COST OF SALES</td><td>COS</td><td>COS</td></tr>';
 echo '<tr><td>EXPENSES ACCOUNTS</td><td>'.$_POST['ExpensesFrom'].'</td><td>'.$_POST['ExpensesTo'].'</td></tr>';
 echo '</table></div>';
 
 
 Function Sales(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="SalesFrom" id="SalesFrom" value="'.$_POST['SalesFrom'].'" pattern="^[0-9 ]*" class="accountcode" maxlength="10" size="20" onMouseOut="validateSales();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="SalesTo" id="SalesTo" value="'.$_POST['SalesTo'].'" pattern="^[0-9 ]*"  class="accountcode" maxlength="10" size="20"  onMouseOut="validateSales();"/>';
     echo '</td></tr>';
}
 
 Function Stock(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="stockFrom" id="stockFrom" value="'.$_POST['stockFrom'].'"  pattern="^[0-9 ]*"  class="accountcode" maxlength="10" size="20"   onMouseOut="validateStock();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="stockTo" id="stockTo"  value="'.$_POST['stockTo'].'"  pattern="^[0-9 ]*"  class="accountcode" maxlength="10" size="20"   onMouseOut="validateStock();"/>';
     echo '</td></tr>';
  }
 
 Function purchases(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="PurchasesFrom" id="PurchasesFrom" value="'.$_POST['PurchasesFrom'].'"  pattern="^[0-9 ]*"  class="accountcode" maxlength="10" size="20"  onMouseOut="validatepurchases();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="PurchasesTo" id="PurchasesTo" value="'.$_POST['PurchasesTo'].'"  pattern="^[0-9 ]*"  class="accountcode" maxlength="10" size="20"  onMouseOut="validatepurchases();"/>';
     echo '</td></tr>';
  }
 
 Function Expenses(){
     echo '<tr><td><label>From Account :</label>';
     echo '<input type="text" name="ExpensesFrom" id="ExpensesFrom" value="'.$_POST['ExpensesFrom'].'"   pattern="^[0-9 ]*"  class="accountcode" maxlength="10" size="20"  onMouseOut="validateExpenses();"/>';
     echo '</td><td><label>To Account :</label>';
     echo '<input type="text" name="ExpensesTo" id="ExpensesTo" value="'.$_POST['ExpensesTo'].'"   pattern="^[0-9 ]*"  class="accountcode" maxlength="10" size="20" onMouseOut="validateExpenses();"/>';
     echo '</td></tr>';
 }
 
 Function ShowChart(){
     
     Global $db;
     
      echo '<DIV class="table-responsive" style="height:10cm">'
            . '<table class="table">'
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
    echo '<td>'.$rows['Calculation'].'</td>';
    echo '<td>'.$AccountType[$rows['ReportStyle']].'</td>';
    echo '</tr>' ;

}

echo '</table></DIV>';
 }
 
?>
<script type="text/javascript">
    
    function validateSales(){
       
       var Sf = document.getElementById('SalesFrom');
       var St = document.getElementById('SalesTo');
        
        if(Sf.value > St.value ){
            
            document.getElementById('SalesTo').value=Sf.value.toString();
        }
      
        
    }
    
    function validateStock(){
       
       var Sf = document.getElementById('stockFrom');
       var St = document.getElementById('stockTo');
        
        if(Sf.value > St.value ){
            document.getElementById('stockTo').value=Sf.value.toString();
        }
      
        
    }
    
    function validatepurchases(){
       
       var Sf = document.getElementById('PurchasesFrom');
       var St = document.getElementById('PurchasesTo');
        
        if(Sf.value > St.value ){
            document.getElementById('PurchasesTo').value=Sf.value.toString();
        }
      
        
    }
    
    function validateExpenses(){
       
       var Sf = document.getElementById('ExpensesFrom');
       var St = document.getElementById('ExpensesTo');
        
        if(Sf.value > St.value ){
            document.getElementById('ExpensesTo').value=Sf.value.toString();
        }
      
        
    }
    
</script>
