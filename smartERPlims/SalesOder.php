<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('SALES');
include('includes/header.inc');
function decodeHtmlEntities($string) {
    return html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
 
 
 $POSclass = new Salespos();
 
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
 
if(isset($_POST['confirmOrder'])){
    DB_query("UPDATE `SalesHeader` SET `status`=1, `released`=1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    
    echo sprintf('<p class="page_title_text"><a id="'.$_SESSION['CompleteDocument'].'" href="%s?No=%s" >'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Sales Order') . '" alt="" />%s</a></p>',
        'PDFPrintSalesOrder.php',$_SESSION['CompleteDocument'], _('Print Sales Order ').$_SESSION['CompleteDocument']);
    
    echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$_SESSION['CompleteDocument']);

    unset($_POST);
    unset($_SESSION['CompleteDocument']);
 }
     
 
 if(!isset($_POST['date'])){ 
    unset($_SESSION['stockmaster']) ;
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
}
if(!isset($_POST['DiscountPercent'])){
    $_POST['DiscountPercent']='';
}
if(!isset($_POST['LineDiscountPercent'])){
    $_POST['LineDiscountPercent']='0';
}
    
 
   $SQL = "SELECT itemcode,barcode, stockmaster.descrip  from stockmaster   where (inactive=0 or inactive is null) and  isstock_1=1  
order by stockmaster.descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
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
   $POSclass->RemoveOrder($_POST['line_no']);
 }
 
 
 if(isset($_POST['submit'])){
     
if($_POST['submit']=='Save and Print Sales Order'){
    include('transactions/customerreadonly.inc');
    $POSclass->neworder();
} 


if($_POST['submit']=='Delete Order'){
    DeletePOS($_SESSION['CompleteDocument']);
    unset($_SESSION['CompleteDocument']);
}
     
 }elseif($_POST['submit']=='Re-Calculate' or !isset($_POST['submit'])){
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off"  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

?>
<!-- Hidden input parameters to store the selected values -->
<style>.pos-layout {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  width: 100%;
}

.pos-left {
  flex-shrink: 0;
}

.pos-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.pos-stock {
  max-height: 350px;
  overflow-y: auto;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 4px;
}

.pos-stock small {
  display: block;
  margin-bottom: 4px;
  color: #666;
  font-size: 12px;
}</style>
<!-- Modal stays unchanged -->
<div id="salesPhotosModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <span class="accept">&checkmark;</span>
    <div id="salesPhotosContainer">
      <div id="imageContainer"></div>
    </div>
  </div>
</div>

<!-- Replace the nested tables with this -->
<div class="pos-layout">

  <div class="pos-left">
    <?php DisplayPOS($POSclass); ?>
  </div>

  <div class="pos-right">
    <div class="pos-entry">
      <?php Entry(); ?>
    </div>
    <div class="pos-stock">
      <small>This list comes from the price list module</small>
      <?php getstocklist(); ?>
    </div>
  </div>

</div>


<?php
echo '</div><input type="hidden" id="selectedRef"  name="selectedRef" value="'.$_POST['selectedRef'].'">
<input type="hidden" id="selectedImages" name="selectedImages" value="'.$_POST['selectedImages'].'"/></form>';
 }
 
 


include('includes/footer.inc');

function getstocklist(){
   
      $return= '<div><div class="table"><label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
               <div class="posfinder"><table id="myStockTable" class="table-bordered stockfind"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
       
    foreach ($_SESSION['stockmaster'] as $key => $row){
    $return .= sprintf('<tr onclick="SalesInventory(\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),trim($row['barcode']),
            trim($row['descrip']),trim($row['barcode']),
            trim($row['descrip'])) ;
    }
   $return .= '</table></div></div></div>';
           
   echo $return;
}

Function Entry(){
      Global $db;
      
    echo '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
    . '<input type="hidden" id="line_no" name="line_no" value="'.$_POST['line_no'].'"/>'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3" type="hidden" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>No of units</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" required="required" value="'.($_POST['qty'] ?? '1').'"/></td></tr>'
    . '<tr><td>Units Measure In</td><td><select id="packid" name="units">';
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
    echo '</select></td></tr>'
    . '<tr><td>Each Unit Contains</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="packzize" name="packzize" required="required" value="'.($_POST['packzize'] ?? '1').'"/></td></tr>'
    . '<tr><td>Unit Price</td><td><input class="number col-sm-push-3" type="text" maxlength="6" size="10" id="sp" name="unitprice"  value="'.$_POST['unitprice'].'"/></td></tr>'
    . '<tr><td>Discount (%)</td><td><input class="number col-sm-push-3" tabindex="4" type="text" maxlength="6" size="10" id="discountpercent" name="LineDiscountPercent" value="'.htmlspecialchars($_POST['LineDiscountPercent'],ENT_QUOTES).'"/></td></tr>'
    . '<tr><td>Empties Charges (If any)</td><td><input class="number col-sm-push-3" tabindex="5" type="text" maxlength="6" size="10" id="Esp" name="Esp" value="'.$_POST['Esp'].'"/></td></tr>'
    . '<tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td>'
    . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
    . '</table>';
 
}

function DisplayPOS($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(1);
echo '<table class="table-bordered table-condensed">'
. '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  id="salesid" size="5" required="required" /></td></tr>'
        . '<tr><td>LPO Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td>'
        . '<td>SalesMan (For Sales commision)</td><td colspan="3"><select tabindex="7" name="salespersoncode" id="salespersoncode">'
   . '<option></option>';

$ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive`  FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
    $salesmancode=trim($row['code']);
   echo sprintf('<option value="%s" %s >%s</option>',  $salesmancode,($_POST['salespersoncode']==$salesmancode?'selected="selected"':''), $row['salesman']);
}
    
echo '</select></td></tr>';

echo '<tr><td>Certificate of Analysis</td>'
        . '<td colspan="4"><input type="text" name="coa_documentno" id="coa_documentno" value="'.$_POST['coa_documentno'].'" size="10" readonly="readonly" /></td></tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input  type="hidden" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchcustomer" value="Search Customer"/></td>'
        . '<td>Invoice To</td>'
        . '<td colspan="3"><input type="text"  name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="20"  required="required" /></td>'
        . '</tr>';

echo '<tr><td>Discount (%):</td><td><input type="text" class="number" name="DiscountPercent" value="'.htmlspecialchars($_POST['DiscountPercent'],ENT_QUOTES).'" size="5" /></td>'
        . '<td>Currency Code</td><td colspan="3">'
    . '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>'
    . '<tr><td>Gallery</td><td><button id="getsalesphotos">Get Sales Photos</button></td></tr>'
        . '</tr></table>';

echo '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>BarCode</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>Unit Descrip</label></th>'
        . '<th><label>Each Contains</label></th>'
        . '<th><label>Sales Price</label></th>'
        . '<th><label>Disc (%)</label></th>'
        . '<th><label>Net Amount</label></th>'
        . '<th><label>VAT Amount</label></th>'
        . '<th><label>Gross Amount</label></th>'
        . '<th><label>TAT</label></th>'
        . '</tr>'
        . '</thead>';
     
$POSclass->Getitems($_POST['stockitemcode'], $_POST['qty'],$_POST['units'],$_POST['packzize'],$_POST['unitprice'], '', ($_POST['LineDiscountPercent'] ?? 0));
        
echo '</table><table>';
$SQLstment="SELECT accountcode,bankName,BranchName,currency FROM `BankAccounts` where currency='".$_POST['currencycode']."'";
        echo '<tr><td>Select Bank to Deposit (Proforma Invoince) :</td><td><Select name="Bank_Code"><option></option>';
                 
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                echo  '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                echo '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
        }
        
        echo '</select></td></tr><tr><td colsapn="2">
	<input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="submit" name="submit" value="'._('Save and Print Sales Order').'" /></td>
        </table>';  
        
        $selectedImages = $_POST['selectedImages'];
        $decodedString = decodeHtmlEntities($selectedImages);
        
        echo getiamgeobject($decodedString);
}
 
Function DeletePOS($DOC){
    global $db;
    
    DB_query("Delete from `Salesline` where documentno='".$DOC."' and `documenttype`='1'", $db);
    DB_query("delete from `SalesHeader` where documentno='".$DOC."' and `documenttype`='1'", $db);
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

function getiamgeobject($urlString){
          $images = '';
          $urls = json_decode($urlString,TRUE);
        if (is_array($urls) && count($urls) > 0) {
            foreach ($urls as $url) {
                 $validUrl = htmlspecialchars($url,ENT_QUOTES,'utf-8'); // Decode the HTML entities
                 $validUrl = urldecode($url); // Decode the URL-encoded characters
                 $validUrl = str_replace('[', '' ,$validUrl);
                 $validUrl = str_replace( ']', '' ,$validUrl);
                 $validUrl = str_replace( '&quot;', '' ,$validUrl);
                 $images .= sprintf('<img src="%s" width="50" alt="Image">',$validUrl);
            }
           
        } 
        
        return $images;
}
?>
