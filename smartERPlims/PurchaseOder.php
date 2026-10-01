<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('purchases/puchasescart.inc');
include('transactions/stockbalance.inc');   

$Title = _('PURCHASES');
include('includes/header.inc');
 
$POSclass = new PurchaseOrder();
 
 if(isset($_GET['new'])){
     $POSclass->neworder();
 }
 
If($_SESSION['SINGLEUSER']=='Singleuser'){
    if(isset($_POST['Delete'])){
        DB_query("Delete from `PurchaseLine` where documentno='".$_SESSION['CompleteDocument']."' and `documenttype`='18'", $db);
        DB_query("delete from `PurchaseHeader` where documentno='".$_SESSION['CompleteDocument']."' and `documenttype`='18'", $db);
        prnMsg('Purchase order :'.$_SESSION['CompleteDocument'].' has been Deleted.');
        unset($_POST);
    }

    if(isset($_POST['confirm'])){
        DB_query("UPDATE `PurchaseHeader` SET `released` = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
        DB_query("UPDATE `PurchaseLine` SET `completed` = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
     
      echo sprintf('<p class="page_title_text"><a id="'.$_SESSION['CompleteDocument'].'" href="%s?No=%s" >'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Sales Order') . '" alt="" />%s</a></p>',
        'PDFPrintPurchaseOrder.php',$_SESSION['CompleteDocument'], _('Print Sales Order ').$_SESSION['CompleteDocument']);
    
      echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$_SESSION['CompleteDocument']);  
        
        unset($_POST);
    }
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
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
   
}
    
  $_SESSION['stockmaster']=array();
    $SQL = "SELECT 
           `stockmaster`.`descrip`,
           `stockmaster`.`itemcode`,
           `vatcategory`.`vat` as CVAT ,
           `stockmaster`.`units`
     FROM `stockmaster` 
     left join `inventorypostinggroup` on `stockmaster`.`postinggroup`=`inventorypostinggroup`.`code`
     left join `vatcategory` on `inventorypostinggroup`.`vatcategory`=`vatcategory`.`vatc`
       where isstock_2=1 and (`inactive`=0 or `inactive` is null)
       group by descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }

   
 if(!isset($_SESSION['Stores'])){
    $REsults=DB_query('SELECT '
            . '`code`,'
            . '`Storename` '
            . 'FROM `Stores`', $db);
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
     
     if($_POST['submit']=='Save Purchase Order'){
         include('purchases/vendorreadonly.inc');
         $POSclass->neworder();
     } 
     
     if($_POST['submit']=='Delete Order'){
         DeletePOS($_SESSION['CompleteDocument']);
         unset($_SESSION['CompleteDocument']);
     }
     
 }elseif($_POST['submit']=='Re-Calculate' or !isset($_POST['submit'])){
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<I>Some inventory items are purchaed in different Units/Quanities than we invoice<br/> e.g. liters but the system requires in kgs. <br/>'
    . 'Enter the purchased quantity for Invoicing and Received quantity for inventory </I>' ;
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div class="centre"><input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
  
?>
<div class="pos-layout">
  <!-- Left column: POS display -->
  <div class="pos-left">
    <?php DisplayPOS($POSclass); ?>
  </div>

  <!-- Right column: Entry + Stock list -->
  <div class="pos-right">
    <div class="pos-entry">
      <?php Entry(); ?>
    </div>
    <div class="pos-entry">
      <?php getstocklist(); ?>
    </div>
  </div>

</div>

<?php 

echo '</div></form>';

 }
 
 
include('includes/footer.inc');


function getstocklist(){
    
    $return= '<label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
                <table id="myStockTable" class="table-bordered"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
        
    foreach ($_SESSION['stockmaster'] as $key => $row){
       $container = $_SESSION['units'][trim($row['units'])]['descrip'];
         
    $return .= sprintf('<tr onclick="posInventoryvat(\'%s\',\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),trim($row['CVAT']),trim($row['descrip']),trim($container),
            trim($row['itemcode']),trim($row['descrip'])) ;
   }
   $return .= '</table>';
            
   echo $return;
}

Function Entry(){
      Global $db;
    echo  '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
     . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
     . '<tr><td>Barcode</td><td><input type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
     . '<tr><td>Item Description</td><td><input type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
     . '<tr><td>Purchasing Units of Measure </td><td><select id="packid" name="units">' ;
                
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
               
    echo '</select></td></tr>'
     . '<tr><td>Purchasable Quantity </td><td><input type="text" maxlength="6" size="10" id="qty" name="qty" required="required" value="'.$_POST['qty'].'"/></td></tr>'
     . '<tr><td>Each Purchased Unit Contains</td><td><input type="text" maxlength="6" size="10" id="packzize" name="packzize" required="required" value="'.$_POST['packzize'].'"/></td></tr>'
     . '<tr><td>Cost Per Purchased Quantity</td><td><input type="text" maxlength="20" size="10" id="cost" name="cost" required="required" value="'.$_POST['cost'].'"/></td></tr>'
     . '<tr><td>Receiving Units of Measure </td><td><select id="receivedid" name="unitsreceived">';
              
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['unitsreceived']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
               
    echo '</select></td></tr>'
     . '<tr><td>Stores Quantity To Be Received</td><td><input type="text" maxlength="6" size="10" id="recqty" name="recqty" required="required" value="'.$_POST['recqty'].'"/></td></tr>'
     . '<tr><td>Each Received Unit Contains</td><td><input type="text" maxlength="6" size="10" id="packzizegrn" name="packzizegrn" required="required" value="'.$_POST['packzizegrn'].'"/></td></tr>'
     . '<tr><td>Discount %</td><td><input type="text" maxlength="2" size="10" id="discount" name="discount" value="'.$_POST['discount'].'"/></td></tr>'
     . '<tr><td>VAT Category</td><td><select id="vatcategory" name="vatcategory">';
             
     $result=DB_query("Select vatc,vat from vatcategory",$db);
    while ($myrow = DB_fetch_array($result)) {
        if($_POST['vatcategory']== trim($myrow['vatc'])){
                echo '<option selected="selected" value="'.  trim($myrow['vatc']) .'">' . $myrow['vatc'] .'-'. $myrow['vat'] . '</option>';
        } else {
                echo '<option value="'.  trim($myrow['vatc']).'">' . $myrow['vatc'] .'-'. $myrow['vat'] . '</option>';
        }
    } //end while loop
              
    echo '</select></td></tr>'
    . '<tr><td colspan="2"><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td></tr>'
    . '<tr><td colspan="2"><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
    . '</table>';
       
}

function DisplayPOS($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(18);
echo '<table class="table-bordered table-condensed">';
echo '<tr><td>Date</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date of Order</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Purchaseoderdate" size="11" maxlength="10"   value="' .$_POST['Purchaseoderdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Date Due</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="datedue" size="11" maxlength="10"   value="' .$_POST['datedue']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';

echo '<tr><td>Document No</td>'
   . '<td><input type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" required="required"/></td>'
   . '<td>Reference</td>'
   . '<td><input type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';

echo '<tr><td>Supplier ID</td>'
        . '<td><input type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchvendor" value="Search for Vendor"/></td>'
        . '<td>Supplier Name</td>'
        . '<td colspan="3"><input type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Currency Code</td><td>'
   . '<input type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];
echo '</tr>';
echo '</table>';


    echo  '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>BarCode</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>Quantity Purchased <br/>(Cloumn 1)<br/>Unit Descrip</label></th>'
        . '<th><label>For Cloumn 1<br/>Each Unit Contains </label></th>'
        . '<th><label>Purchased<br/>Cost Price<br/>For Cloumn 1</label></th>'
        . '<th><label>Equivalent<br/> Quantity To be <br/>Received in</label></th>'
        . '<th><label>Real Cost Price</label></th>'
        . '<th><label>Discount Rate</label></th>'
        . '<th><label>Net Amount</label></th>'
        . '<th><label>VAT Amount</label></th>'
        . '<th><label>Gross Amount</label></th>'
        . '</tr>'
        . '</thead>';
   
     
   $POSclass->Getitems($_POST['stockitemcode'], $_POST['qty'],$_POST['units']
        ,$_POST['packzize'],$_POST['cost'],$_POST['vatcategory'],$_POST['discount'],$_POST['recqty'],$_POST['unitsreceived'],$_POST['packzizegrn']);
        
  echo '</table><table><tr><td>
	<input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="submit" name="submit" value="'._('Save Purchase Order').'" /></td>
        </table>';  
}
 
Function DeletePOS($DOC){
    global $db;
  
    DB_query("Delete from `PurchaseLine` where documentno='".$DOC."' and `documenttype`='18'", $db);
    DB_query("delete from `PurchaseHeader` where documentno='".$DOC."' and `documenttype`='18'", $db);
    
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

 