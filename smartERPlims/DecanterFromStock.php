<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/ClassStockIssues.inc');
include('transactions/stockbalance.inc');   
$Title = _('Bitumen Decanter');
include('includes/header.inc');
 $POSclass = new Decanter();
 
 if(isset($_GET['new'])){
    $POSclass->neworder();
 }
 
 if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
     while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['code']);
        $_SESSION['units'][$code]=$row;
    }
}
 

 if(!isset($_POST['date'])){ 
   
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date'] = ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
}
    
  unset($_SESSION['stockmaster']) ;
     $SQL = "SELECT itemcode,barcode,descrip,container
     from stockmaster where itemcode in (select `itemcode` from `ProductionUnit`) order by descrip";
    $ResultIndex=DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }

    
    unset($_SESSION['stockmasterP']) ;
    $SQL = "SELECT itemcode,barcode,descrip,container
     from stockmaster where itemcode NOT In(select `itemcode` from `ProductionUnit`) order by descrip";
    $ResultIndex=DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmasterP'][$code]=$row;
    }


 if(!isset($_SESSION['Stores'])){
    $REsults=DB_query('SELECT `code`,`Storename` FROM `Stores`', $db);
    $x=0;
    while($row= DB_fetch_array($REsults)){
        $_SESSION['Stores'][$x]=$row;
        $x++;
    }
}   

 if(isset($_POST['remove'])){
   $POSclass->RemoveOrder($_POST['stockitemcode']);
 }
 
 if(isset($_POST['submit'])){
     
     if($_POST['submit']=='Save Item Request'){
            include('purchases/Decantbitumen.inc'); 
            unset($_POST);
           $POSclass->neworder();
     } 
     
     if($_POST['submit']=='Delete Order'){
         DeletePOS($_SESSION['CompleteDocument']);
         unset($_SESSION['CompleteDocument']);
     }
     
 }elseif(isset($_POST['refresh']) or !isset($_POST['submit'])){
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

?>

<table class="table-condensed table-responsive-small"><tr><td valign="top"><?php DisplayParent($POSclass); ?></td><td valign="top" >
            <table class="table-condensed table-responsive-small"><tr><td valign="top"><?php Entry(); ?></td></tr><tr><td valign="top"><?php getstocklist(); ?><td></tr></table>
</td></tr></table>

<?php
echo '</div></form>';
 }
 
include('includes/footer.inc');

function DisplayParent($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(40);
 
$POSclass->Getitems($_POST['stockitemcode'],$_POST['bitumenstockitemcode'], $_POST['qty'],$_POST['units'],$_POST['packzize']);
echo '<table class="table-bordered table-condensed">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td><td><input tabindex="2" type="text" name="documentno" value="'.$_POST['documentno'].'"  readonly="readonly"/></td>'
   . '</tr>';

echo '<tr><td>Employee ID</td>'
    . '<td><input tabindex="3" type="text" name="CustomerID" id="EmployeeID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
    . '<input type="button" id="searchemployee" value="Search Employee"/></td>'
    . '<td>Employee Name</td>'
    . '<td colspan="3"><input tabindex="4" type="text" name="CustomerName" id="EmployeeName" value="'.$_POST['CustomerName'].'"  size="20"  required="required" /></td></tr>'
   . '</table>';


    echo '<table class="table-bordered ">'
. '<caption>One Stock item must have a container/Drum/bucket and the other stock item should not have a container</caption>'
        . '<thead><tr>'
        . '<th><label>Description</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>STORE/TANK</label></th>'
        . '<th><label>BALANCE</label></th>'
        . '<th><label>Unit Descrip</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>Decanting STORE/TANK</label></th>'
        . '<th><label>Unit Cost</label></th>'
        . '<th><label>Total Cost</label></th>'
        . '</tr>'
        . '</thead>';
     
     
  echo $_SESSION['htmltable'];
        
  echo '</table><table><tr><td>
	<input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="'.$_SESSION['hideOrShow'].'" name="submit" value="'._('Save Item Request').'" /></td>
        </table>';  
}
 
 
function getstocklist(){
     $return= '<div><div class="table"><label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for stock with containers." ></label>
               <div class="posfinder"><table id="myStockTable" class="table-bordered stockfind"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
       
    foreach ($_SESSION['stockmasterP'] as $row){
        $Container=trim($row['container']);
        if(mb_strlen($Container)>1){
            $return .= sprintf('<tr onclick="posInventory(\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),trim($row['barcode']),
            trim($row['descrip']),trim($row['barcode']),
            trim($row['descrip'])) ;
        }
    }
   $return .= '</table></div></div></div>';
           
   echo $return;
}

Function Entry(){
      Global $db;
  
      echo '<table class="table-bordered table-condensed"><caption>Entry Window</caption><tr><td>'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Barcode</td><td><input class="col-sm-push-3" type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Bitumen Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>No of units</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" required="required" value="'.$_POST['qty'].'"/></td></tr>'
    . '<tr><td>Units Measure In</td><td><select id="packid" name="units">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
     //itemcode,barcode,descrip         
    echo '</select></td></tr>'
        . '<tr><td>Each Unit Contains</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="packzize" name="packzize" required="required" value="'.$_POST['packzize'].'"/></td></tr>'
  
    . '<tr><td>Select Bitumen Bulk Stock Item</td><td><select id="bitcode" name="bitumenstockitemcode">';
    foreach ($_SESSION['stockmaster'] as $value) {
         $Container=trim($value['container']);
        if(mb_strlen($Container)==1 or $Container=='0'){
           $code = trim($value['itemcode']); $selunit =(($_POST['bitumenstockitemcode']==$code)?'selected="selected"':'');
            echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
        }
    }
              
    echo '</select></td></tr>'
      . '<tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td>'
        . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
        . '</table>';
       
}

Function DeletePOS($DOC){
    global $db;
    
    DB_query("Delete from `Salesline` where documentno='".$DOC."' and `documenttype`='40'", $db);
    DB_query("delete from `SalesHeader` where documentno='".$DOC."' and `documenttype`='40'", $db);
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

 