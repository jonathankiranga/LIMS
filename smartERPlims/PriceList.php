<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('Price List');
include('includes/header.inc');

$POSclass = new PriceList();
if(isset($_GET['new'])){
    $POSclass->getready();
}

if(isset($_GET['delid'])) {
   $_POST['CustomerID']= $_GET['cu'];
   $POSclass->deletedata($_GET['delid']);
   unset($_POST['CustomerID']);
   unset($_POST['CustomerName']);
}
 
if(isset($_POST['remove'])){
   $POSclass->deleteRow($_POST['childid']);
} 
    
$_SESSION['containers']=array();
    $result=DB_query("SELECT itemcode,descrip FROM stockmaster where isstock_6=1 ",$db);
        while ($myrow = DB_fetch_array($result)) {
          $code = trim($myrow['itemcode']);
           $_SESSION['containers'][$code]=$myrow;
    } 
    
    $SQL = "SELECT itemcode,descrip"
         . " from stockmaster "
         . " where inactive=0 and isstock_1=1 "
         . " order by descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }


$_SESSION['CustomerTable']=array();
$SQL = "SELECT itemcode,customer from debtors join PriceList 
      on PriceList.customerCode=debtors.itemcode group by itemcode,customer";
$ResultIndex=DB_query($SQL, $db);
while($row = DB_fetch_array($ResultIndex)){
    $code = trim($row['itemcode']);
    $_SESSION['CustomerTable'][$code]=$row;
}

 
if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
        while($row = DB_fetch_array($ResultIndex)){
           $code = trim($row['code']);
           $_SESSION['units'][$code]=$row;
       }
}   

        

if(isset($_POST['submit'])){
     if($_POST['submit']=='Save Price List'){
         $POSclass->save();
     } 
 }
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

?>

<table class="table-condensed table-responsive-small">
    
    <tr><td>
    <B><u>TO COPY A NEW PRICE LIST FROM THE MAIN PRICE LIST</u></b>
    <div class="help-block"><fieldset><ui>
        <ul>First Select the customer you want to give the selected price using the button <input type="button" value="Search Customer" class="btn-sm"></ul>
        <ul>Next, Row click an item in DEFAULT PRICE LIST you want to offer a price</ul>
        <ul>Then, go the entry window on your right and review the price. click button <input type="button" value="Preview Price" class="btn-info btn-sm"> when competed</ul>
        <ul>Finaly Click button save <input type="button" value="Save Price List"  class="btn-sm"></ul>
       </ui></fieldset></div></td></tr>
    <tr><td valign="top" class="table-bordered"><?php DisplayPOS(); ?></td><td valign="top"><?php Entry(); ?></td></tr>
    <tr><td valign="top" colspan="2"><?php $POSclass->copyPriceList();?></td></tr>
    
</td></tr></table>
 
<?php
echo '</div></form>';
include('includes/footer.inc');



Function Entry(){
  echo '<div><table class="table-bordered"><caption>Entry Window</caption><tr><td>'
     . '<input type="hidden" id="rowid" name="rowid" value="'.$_POST['rowid'].'"  />'
     . '<input type="hidden" id="childid" name="childid" value="'.$_POST['childid'].'"  />'
     . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
     . '<tr><td>Item Code</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
     . '<tr><td>Measured by</td><td><select id="packid" name="units" class="DontChange">';
              
     foreach ($_SESSION['units'] as $key => $value) {
            $code = trim($value['code']);
            $selected = (($code==$_POST['units'])?'selected="selected"':'');
            echo '<option value="'.$code.'" '.$selected.'>'.$value['descrip'].'</option>';
     }
                
     echo '</select></td></tr>'
         . '<tr><td>Measurement Quantity</td><td><input class="col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" readonly="readonly" value="'.($_POST['qty'] ?? '1').'" onmouseleave="multiply(\'qty\',\'unitprice\',\'sp\');"/></td></tr>'
         . '<tr><td>Enter your UNIT PRICE </td><td><input class="col-sm-push-3" tabindex="3" type="text" maxlength="20" size="10" id="unitprice" name="unitprice" required="required"  value="'.$_POST['unitprice'].'"   onmouseleave="multiply(\'qty\',\'unitprice\',\'sp\');"/></td></tr>'
         . '<tr><td>TAT (days)</td><td><input class="col-sm-push-3" type="text" maxlength="6" size="10" id="tat" name="tat" value="'.($_POST['tat'] ?? '').'"/></td></tr>'
         . '<tr><td>Selling Price as per Measurement</td><td><input class="col-sm-push-3"  type="text" maxlength="6" size="10" id="sp" name="sp" readonly="readonly"  value="'.$_POST['sp'].'"/></td></tr>'
         . '<tr><td><input type="submit" name="refresh" value="Preview Price" class="btn-info"/></td>'
         . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
         . '</table>';       
}
              
    

function DisplayPOS(){
global $db,$POSclass;
 
if (!defined('GRIDFORM_CSS')) {
    define('GRIDFORM_CSS', 1);
    echo '<style>
    .gridform{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem 1rem;margin-bottom:0.5rem;}
    .gridform .form-group{display:flex;flex-direction:column;gap:0.25rem;}
    .gridform label{font-weight:500;font-size:0.8rem;margin-bottom:0;}
    .gridform input,.gridform select,.gridform button{max-width:100%;}
    @media(max-width:768px){.gridform{grid-template-columns:1fr 1fr;}}
    @media(max-width:520px){.gridform{grid-template-columns:1fr;}}
    </style>';
}

echo '<div class="gridform">'
    . '<div class="form-group"><label for="searchcustomer">'. _('Customer') .'</label>'
    . '<div style="display:flex;gap:4px;"><input type="hidden" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'" size="5" />'
    . '<input type="button" id="searchcustomer" value="Search Customer" style="min-width:0;" /></div>'
    . '<input type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'" size="20" readonly="readonly" /></div>'
    . '<div class="form-group"><label>'._('Price List For').'</label>'
    . '<input type="text" readonly="readonly" value="'.$_POST['CustomerName'].'" size="20" /></div>'
    . '</div>'
    . '<input type="hidden" id="currencycode"  name="currencycode"  value="'.$_POST['currencycode'].'" />'
    . '<input type="hidden" id="salespersoncode"  name="currencycode"  value="'.$_POST['salespersoncode'].'" />';


echo '<table class=" display table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>Code</label></th>'
        . '<th><label>Stock Description</label></th>'
        . '<th><label>Measured <br/>In What Package</label></th>'
        . '<th><label>Measurement<br/> Quantity</label></th>'
        . '<th class="number"><label>Sales Price<br/>as Per <br/>Measured Quantity</label></th>'
        . '</tr>'
        . '</thead><tbody>';

    $POSclass->GetCustomizeditems($_POST['stockitemcode'], $_POST['qty'],$_POST['CustomerID'],$_POST['units'],$_POST['sp'],$_POST['rowid']);
         
  echo '</tbody><tfoot><tr><th></th><th></th><th></th><th></th><th></th></tr>'
        . '</tfoot></table><table><tr><td>
	<input type="submit" name="submit" value="'. _('Refresh').'" class="btn-info" /></td><td>
 	<input type="submit" name="submit" value="'._('Save Price List').'"  onclick="return validateform(\'CustomerID\')" /></td>
        </table>';  
}

 
