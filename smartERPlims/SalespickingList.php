<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');   
include('transactions/stockbalance.inc');   
$Title = _('Sales Picking List');
include('includes/header.inc');  

$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
 
echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Picking List') .'" alt="" />' . ' ' . _('Sales Picking List') . '</p>';
 
if (isset($_POST['submit'])) {
    if ($_POST['submit'] == 'Confirm Action') {
       
        include('transactions/Savepickinglist.inc');
    }
}

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_POST['manualdocumentno']= GetTempNextNo(19);
    $_SESSION['DocumentPicking'] = false;
    $_SESSION['productionTankStore'] = array();
    $_SESSION['cumQty']=array();
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
    
    if(!isset($_POST['date'])){
       $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    }
    
    if(!isset($_POST['Salesoderdate'])){
        $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
    }
    
     if(!isset($_POST['datedue'])){
       $_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
     }
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

echo '</div><div class="container-fluid">'
    . '<table class="table-condensed table-responsive-small table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
<td>Document No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>Delivery No</td>'
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
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td>
   </tr>';
echo '</table>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
$sqldebtors=DB_query("SELECT `itemcode` ,`creditlimit`,`customer`
      ,`phone` ,`email` ,`city` ,`country`,`curr_cod`,`customerposting`,`salesman`,`VATinclusive`
       FROM `debtors` join postinggroups on code=`customerposting` where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];



$query="SELECT
       `entryno`
      ,`documenttype`
      ,`docdate`
      ,`documentno`
      ,`locationcode`
      ,`stocktype`
      ,`code`
    ,`SalesLine`.`SampleID`
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
      ,`SalesLine`.`partperunit`
      ,`stockmaster`.`averagestock`
      ,UOM
  FROM `SalesLine` 
  join `stockmaster` on `SalesLine`.code=`stockmaster`.itemcode
  where `documentno`='".$_POST['documentno']."'
    and (`Qunatity_delivered` < `Quantity` or `Qunatity_delivered` is null)";
 $ResultIndex = DB_query($query, $db);

echo '<table class="table-condensed table-responsive-small table-bordered" style="width:100%"><tr class="picking-header">'
    . '<td style="width:1%">Stock ID</td>'
    . '<td style="width:1%">Sample ID</td>'
    . '<td style="width:100%">Stock Description</td>'
    . '<td class="number">Done<br><button type="button" class="btn btn-sm btn-success" onclick="selectAll(\'done\', true)">All</button> <button type="button" class="btn btn-sm btn-warning" onclick="selectAll(\'done\', false)">None</button></td>'
    . '<td class="number">Not Done<br><button type="button" class="btn btn-sm btn-success" onclick="selectAll(\'notdone\', true)">All</button> <button type="button" class="btn btn-sm btn-warning" onclick="selectAll(\'notdone\', false)">None</button></td></tr>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;
 

$Rowdata = array();
while($stocklist=DB_fetch_array($ResultIndex)){
    $_SESSION['cumQty'][$stocklist['code']]=0;
    $Rowdata[]=$stocklist;
}

if(is_array($Rowdata) && count($Rowdata)>0){
foreach ($Rowdata as $key => $stocklist) {
    $emptycost = 0; $totalemptycost = 0; $cvatamount = 0;
    $cnetamount = 0; $cgrossamount = 0; $emptyunits = 0;
    
    $itemcode  = trim($stocklist['entryno']);
    $stkcode   = trim($stocklist['code']);
    $sampleid  = isset($stocklist['SampleID'])?trim($stocklist['SampleID']):'';
    $displayID = ($sampleid!=='')?$sampleid:$stkcode;
    $packedas  = trim($stocklist['unitofmeasure']);
    $containercode = trim($stocklist['container']);
    $qtyorderd     =(int) $stocklist['Quantity'];
    $qtyreceived   =(int) $stocklist['Qunatity_delivered'];  //0 
    $Quantity_toinvoice = (int) $stocklist['Quantity_toinvoice']; // 0
    $qtytoremaining = $qtyorderd-($qtyreceived);
    if($qtytoremaining>0){
        
    // Reduced columns for lab picking list: Stock ID, SampleID, Description, Done, Not Done
    echo '<tr id="row-'.$itemcode.'">'
        . '<td>' . htmlspecialchars($stkcode,ENT_QUOTES,'UTF-8') . '<input type="hidden" name="code['.$itemcode.']" value="'.$stkcode.'"/></td>'
        . '<td>' . htmlspecialchars($sampleid,ENT_QUOTES,'UTF-8') . '<input type="hidden" name="sampleid['.$itemcode.']" value="'.$sampleid.'"/></td>'
        . '<td style="width:100%">' . htmlspecialchars(trim($stocklist['description']),ENT_QUOTES,'UTF-8')
            . '<input type="hidden" name="ordered['.$itemcode.']" value="'.$qtyorderd.'"/>'
            . '<input type="hidden" name="Quantity_toinvoice['.$itemcode.']" value="'.$qtytoremaining.'"/>'
            . '<input type="hidden" name="emptycode['.$itemcode.']" value="'.$containercode.'"/>'
            . '<input type="hidden" name="partperunit['.$itemcode.']" value="'.$stocklist['partperunit'].'"/>'
        . '</td>'
       . '<td class="number"><input id="done-' . $itemcode . '" type="checkbox" name="done[' . $itemcode . ']" value="1" '
        . (isset($_POST['done'][$itemcode]) ? 'checked' : '')
        . ' onclick="if(this.checked){ var row=this.closest(\'tr\'); row.querySelector(\'[id^=notdone-]\').checked=false; }" /></td>'

        . '<td class="number"><input id="notdone-' . $itemcode . '" type="checkbox" name="notdone[' . $itemcode . ']" value="0" '
        . (isset($_POST['notdone'][$itemcode]) ? 'checked' : '')
        . ' onclick="if(this.checked){ var row=this.closest(\'tr\'); row.querySelector(\'[id^=done-]\').checked=false; }" /></td>'    . '</tr>';
    }

  
}

}

echo '<tfoot><tr>'
    . '<td colspan="2"></td>'
    . '<td class="number"><input type="submit" name="ReCalculate" value="' . _('Re-Calculate') . '" /></td>'
    . '<td class="number"><input type="submit" name="submit" value="' . _('Confirm Action') . '"'
    . ' onclick="return confirm(\'' . _('Are you sure you wish to Confirm Action ?') . '\');" /></td>'
    . '</tr></tfoot>';
echo '</table>';
echo '</div></div></form>';

echo '<script>
function selectAll(column, check) {
    document.querySelectorAll("input[id^=\'" + column + "-\']").forEach(function(cb) {
        cb.checked = check;
        if (check) {
            var row = cb.closest("tr");
            var other = row.querySelector("input[id^=\'" + (column === "done" ? "notdone" : "done") + "-\']");
            if (other) other.checked = false;
        }
    });
}
</script>';
 
include('includes/footer.inc');
  