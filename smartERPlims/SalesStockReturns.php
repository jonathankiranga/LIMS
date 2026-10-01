<?php
$Title = _('Sales Return List');

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/header.inc');   
include('transactions/stockbalance.inc');   

$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
       echo '<a class="page_title_text" href="SalesDeliveryList.php"> List of Sales Orders</a>';
echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Return List') .'" alt="" />' . ' ' . _('Sales Return List') . '</p>';

if(isset($_POST['confirm'])){
    include('transactions/SaveReturnlist.inc');  
} 

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_POST['manualdocumentno']= GetTempNextNo(11);
    $_SESSION['DocumentPicking']=false;
    $_SESSION['productionTankStore'] = array();
}
    
    
$filter="SELECT 
            `documenttype`
           ,`documentno`
           ,`docdate`
           ,`oderdate`
           ,`duedate`
           ,`postingdate`
           ,`customercode`
           ,`customername`
           ,`yourreference`
           ,`externaldocumentno`
           ,`locationcode`
           ,`paymentterms`
           ,`postinggroup`
           ,`currencycode`
           ,`salespersoncode`
           ,`vatinclusive`
       FROM `SalesHeader` 
       where `documentno`='".$_POST['documentno']."'";
$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
    
    $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    if(!isset($_POST['Salesoderdate'])){
        $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
    }
  
    $_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
    
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['CustomerID'] = $rowresults[6];
    $_POST['CustomerName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
  
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
        . '<table class="table table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';


echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>Credit Note No</td>'
        . '<td><input tabindex="4" type="text" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required"/></td>'
        . '</tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive` FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td><td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';
echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
$sqldebtors=DB_query("SELECT `itemcode` ,`creditlimit`,`customer`
      ,`phone` ,`email` ,`city` ,`country`,`curr_cod`,`customerposting`,`salesman`,`VATinclusive`
       FROM `debtors` join postinggroups on code=`customerposting` where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];

      


$ResultIndex = DB_query("SELECT
       `entryno`
      ,`documenttype`
      ,`docdate`
      ,`documentno`
      ,`locationcode`
      ,`code`
      ,`description`
      ,`unitofmeasure`
      ,`Quantity`
      ,`Quantity_toinvoice`
      ,`Qunatity_delivered`
      ,`UnitPrice`
      ,`vatamount`
      ,`invoiceamount`
      ,`completed`
      ,`printed`
      ,`containerprice`
      ,`containersunits`
      ,`totalchargedcontainers`
      ,`containercode`
      ,`vatrate`
      ,`inclusive`
      ,`partperunit`
      ,UOM
  FROM `SalesLine`  where `documentno`='".$_POST['documentno']."'", $db);


echo '<table class="table table-bordered"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">Packed In</th>'
        . '<th class="number">Qty<br />Ordered</th>'
        . '<th class="number">Store<br />Picked</th>'
        . '<th class="number">Store<br />Balance</th>'
        . '<th class="number">Qty<br />Returned</th>'
        . '<th class="number">Balance <br/> of Qty <br/>Sold</th>'
        . '<th class="number">Containers<br />Returned</th>
      </thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    
    $itemcode = trim($stocklist['entryno']);
    $stkcode  = trim($stocklist['code']);
    IsTankOrStore($stkcode);
    $containercode = trim($stocklist['containercode']);
    $packedas = $stocklist['unitofmeasure'];
    $QuantityReturned =(float) $stocklist['Qunatity_delivered'];
     
   
    $emptycost=0; $totalemptycost=0; $cvatamount =0; $cnetamount=0; $cgrossamount=0; $emptyunits=0;
      
    $qtyorderd    =(float) $stocklist['Quantity'];
    $qtyreceived  =(float) $stocklist['Qunatity_delivered'];
    $qtytoinvoice = $stocklist['Qunatity_toinvoice'];
        
    if(isset($_POST['subunits'][$itemcode])){
        $qty = $_POST['subunits'][$itemcode];
        $qtytoinvoice = ($qtyorderd - $QuantityReturned);
    }else{
        $qty = ($qtyorderd -$QuantityReturned) ;
    }
     
   
    if(isset($_POST['subunits'][$itemcode])){
        $qtytoinvoice = $qtyorderd - ($qty + $QuantityReturned ) ;
    }else{
        $qtytoinvoice = ($qtyorderd - $QuantityReturned);
    }
    
   
    
    
    if(isset($qty)){
       $emptyunits = $_POST['emptyunits'][$itemcode];
    }else{
       $emptyunits = .0;
    }
   
    $location = getDefaultStore($itemcode);
    if($_SESSION['productionTankStore'][$stkcode]=="T"){
        $StockBalanceInloose =(int) getTankbalance($location,$stkcode);
    }elseif($_SESSION['productionTankStore'][$stkcode]=="S"){
        $StockBalanceInloose=(int) getbalance($stkcode,$location,'loosqty');
    }
    
    $StockRemaining = (int) $StockBalanceInloose + $total;
    $stockBalance = intdiv($StockRemaining,$stocklist['partperunit']);
    $balanceDescription = sprintf('%f %s',$stockBalance,$packedas);
        
    echo sprintf('<tr>'
         .'<td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td><input type="text" class="integer" name="ordered['.$itemcode.']" value="'.$qtyorderd.'" readonly="readonly" size="5"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" autofocus="autofocus" required="required" size="10"/></td>'
         .'<td><input type="text" class="integer" name="Quantity_toinvoice['.$itemcode.']" value="'.$qtytoinvoice.'" readonly="readonly" size="5"/></td>'
       
         .'<td><input type="hidden" name="emptycode['.$itemcode.']" value="'.$containercode.'" size="0"/>'
         . '<input type="hidden" name="partperunit['.$itemcode.']" value="'.$stocklist['partperunit'].'" size="0"/>'
         .'<input type="text" class="number" name="emptyunits['.$itemcode.']" value="'.$emptyunits.'" size="5"/></td>'
         .'</tr>',trim($stocklist['description']),$packedas.'(1 X '. number_format($stocklist['partperunit'],0).')',
      StoreTankCMB($itemcode,$stkcode),$balanceDescription);
}
   
echo '<tfoot><tr>'
    . '<td colspan="5"></td>'
    . '<td ></td>'
    . '<td class="number"></td>'
    . '<td class="number"></td>'
    . '<td class="number"></td>'
    . '</tr></tfoot>';

echo '</table>';

echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="confirm" value="' . _('Confirm Returns Details') . '"'
        . ' onclick="return confirm(\''._('Are you sure you wish to Dispatch this Picking List ?').'\');" />
</div>';



echo '</td></tr></table></div></form>';
  
include('includes/footer.inc');

function getDefaultStore($rowid){
  return  (isset($_POST['location'][$rowid]))?($_POST['location'][$rowid]):'';
}

Function IsTankOrStore($ItemCode){
    global $db;
    
    $stcode = trim($ItemCode);
    $sql = sprintf("select count(*) from `ProductionUnit` where itemcode='%s'",$ItemCode);
    $ResultIndex=DB_query($sql,$db);
    $Stores = DB_fetch_row($ResultIndex);
   if($Stores[0]==0){
      $_SESSION['productionTankStore'][$stcode]="S";
   }else{
      $_SESSION['productionTankStore'][$stcode]="T";
   }
   
}

Function StoreTankCMB($id,$ItemCode){
        global $db;
        $stcode = trim($ItemCode);

        $line='';
        $line .='<select name="location['.$id.']" required="required">';
        $sql=sprintf("select count(*) from `ProductionUnit` where itemcode='%s'",$ItemCode);
        
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $_SESSION['productionTankStore'][$stcode]="S";
           $REsults=DB_query('SELECT `code`,`Storename` FROM `Stores`', $db);
           while($rows= DB_fetch_array($REsults)){
                $line .= sprintf('<option value="%s" %s>%s</option>',trim($rows['code']),$_POST['location']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
            }
       }else{
          $_SESSION['productionTankStore'][$stcode]="T";
           $ResultIndex=DB_query(sprintf("select tankname from `ProductionUnit` where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                   $line .= sprintf('<option value="%s" %s>%s</option>',trim($value['tankname']),$_POST['location']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
           }
       }
       $line .= '</select>';
        return $line; 
    }
  

   
?>