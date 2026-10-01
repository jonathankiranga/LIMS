<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('SALES SAMPLES');
include('includes/header.inc');
 
 $POSclass = new Samples();
 
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
    
    echo sprintf('<p class="page_title_text"><a id="'.$_SESSION['CompleteDocument'].'" href="%s?No=%s" >
      <img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Sales Samples') . '" alt="" />%s</a></p>',
        'PDFPrintSampleRequisition.php',$_SESSION['CompleteDocument'], _('Print Sales Samples ').$_SESSION['CompleteDocument']);
    
    echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$_SESSION['CompleteDocument']);

    unset($_POST);unset($_SESSION['CompleteDocument']);
 }
     
 if(!isset($_POST['date'])){ 
    unset($_SESSION['stockmaster']) ;
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
}

    $SQL = "SELECT itemcode,barcode,
            stockmaster.descrip
            from stockmaster 
            order by stockmaster.descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }

   
 if(!isset($_SESSION['Stores'])){
    $REsults = DB_query('SELECT `code`,`Storename` FROM `Stores`', $db);
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
     
     if($_POST['submit']=='Save Sample Requset'){
         
                If($_SESSION['SINGLEUSER']=='Singleuser'){
                    include('transactions/postSampleIssues.inc');  
                    DB_query("UPDATE `SalesHeader` SET `status` = 2 where documentno='".$_POST['documentno']."'", $db);
                    include('transactions/SaveSampleIssues.inc'); 
                }else{
                    include('transactions/requstSampleIssues.inc');  
               }
               unset($_POST);
         $POSclass->neworder();
     } 
     
     if($_POST['submit']=='Delete Order'){
         DeletePOS($_SESSION['CompleteDocument']);
         unset($_SESSION['CompleteDocument']);
     }
     
 }else{
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

?>

<table class="table-condensed table-responsive-small"><tr><td valign="top"><?php DisplayPOS($POSclass); ?></td><td valign="top" >
            <table class="table-condensed table-responsive-small"><tr><td valign="top"><?php Entry(); ?></td></tr><tr><td valign="top"><?php getstocklist(); ?><td></tr></table>
</td></tr></table>

<?php
echo '</div></form>';
 }
 
include('includes/footer.inc');

function getstocklist(){
   
      $return= '<div><div class="table"><label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
               <div class="posfinder"><table id="myStockTable" class="table-bordered stockfind"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
       
    foreach ($_SESSION['stockmaster'] as $key => $row){
    $return .= sprintf('<tr onclick="posInventory(\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
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
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Barcode</td><td><input class="col-sm-push-3" type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>No of units</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" required="required" value="'.$_POST['qty'].'"/></td></tr>'
    . '<tr><td>Units Measure In</td><td><select id="packid" name="units">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr>'
      . '<tr><td>Each Unit Contains</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="packzize" name="packzize" required="required" value="'.$_POST['packzize'].'"/>'
      . '<input type="hidden" id="sp" name="sp"/></td></tr>'
      . '<tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td>'
      . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
      . '</table>';
       
}

function DisplayPOS($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(15);
echo '<table class="table-bordered table-condensed">'
. '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"   required="required" /></td></tr>'
  . '<tr><td colspan="2">SalesMan (For CRM purposes)</td><td colspan="2"><select tabindex="7" name="salespersoncode" id="salespersoncode">'
   . '<option></option>';

$ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive`  FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
    if(isset($_POST['salespersoncode'])){
        if($_POST['salespersoncode']==$row['code']){
          $selected='selected="selected"';
        }else{
           $selected='';
       }
    }else{
        $selected='';
    }
   echo sprintf('<option value="%s" %s >%s</option>',
           $row['code'],($_POST['salespersoncode']==$row['code']?'selected="selected"':''),
           $row['salesman']);
}
    
echo '</select></td></tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchcustomer" value="Search Customer"/></td>'
        . '<td>Sample Sent to'
        . '<input type="text"  name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"   required="required" /></td>'
       ;

echo '<td>Currency Code'
    . '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>'
    . '</tr></table>';


    echo '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>BarCode</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>Unit Descrip</label></th>'
        . '<th><label>Each Contains</label></th>'
        . '<th><label>Sales Price</label></th>'
        . '<th><label>Net Amount</label></th>'
        . '<th><label>VAT Amount</label></th>'
        . '<th><label>Gross Amount</label></th>'
        . '<th><label>Fetch From Storage Location</label></th>'
        . '</tr>'
        . '</thead>';
     
          $POSclass->Getitems($_POST['stockitemcode'], $_POST['qty'],$_POST['units'],$_POST['packzize']);
        
  echo '</table><table><tr><td>
	<input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="submit" name="submit" value="'._('Save Sample Requset').'" /></td>
        </table>';  
}
 
Function DeletePOS($DOC){
    global $db;
    
    DB_query("Delete from `Salesline` where documentno='".$DOC."' and `documenttype`='15'", $db);
    DB_query("delete from `SalesHeader` where documentno='".$DOC."' and `documenttype`='15'", $db);
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

 