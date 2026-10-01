<?php
$Title = _('Sales Credit Note');
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('transactions/stockbalance.inc');
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

include('includes/header.inc');   
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Credit Note') .'" alt="" />' . ' ' . _('Sales Credit Note') . '</p>';

if(isset($_POST['confirm'])){
    if($_SESSION['Grossamounttotal']>0){
          include('transactions/SavecreditNote.inc');
    }else{
        prnMsg('This credit note is invalid');
    }
}

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_POST['manualdocumentno'] = GetTempNextNo(13);
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
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

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
        . '<table class="table table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';

echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>Credit Note No</td>'
        . '<td><input tabindex="4" type="text" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required" /></td>'
 
        . '<td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td>'
        . '</tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"/>'
   . '</td><td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td colspan="3">'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode"><option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive` FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s" %s>%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td><td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td>
 </tr><tr>';
echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];
echo '</tr></table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
$sqldebtors=DB_query("SELECT `itemcode` ,`creditlimit`,`customer`
      ,`phone` ,`email` ,`city` ,`country`,`curr_cod`,`customerposting`,`salesman`,`VATinclusive`,`IsTaxed`
       FROM `debtors` join postinggroups on code=`customerposting` where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];
$IsTaxed = $debtorsrow[11];


      
$Slaqry ="SELECT `entryno`
      ,`documenttype`
      ,`docdate` 
      ,`documentno` 
      ,`locationcode`
      ,`code` 
      ,`description`
      ,`unitofmeasure`
      ,Qunatity_delivered as Returned  
      ,`UnitPrice`
      ,`vatamount`
      ,`invoiceamount`
      ,`completed`
      ,`printed` 
      ,`containerprice`
      ,`totalchargedcontainers`
      ,`containercode`
      ,`vatrate` 
      ,`inclusive`
      ,`partperunit`
  FROM `SalesLine` 
 where `documentno`='".$_POST['documentno']."' group by  
       `entryno`,`documenttype`,`docdate`,`documentno`
      ,`locationcode`,`code` ,`description` ,`unitofmeasure`,`Qunatity_delivered`
      ,`UnitPrice` ,`vatamount` ,`invoiceamount`,`completed` ,`printed`
      ,`containerprice` ,`totalchargedcontainers` ,`containercode`
      ,`vatrate` ,`inclusive` ,`partperunit` ";


 $ResultIndex = DB_query($Slaqry,$db);

echo '<table  class="table table-bordered"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">Packed In</th>'
        . '<th class="number">Qty to<br />Return</th>'
        . '<th class="number">Invoice Price<br /> per part</th>'
        . '<th class="number">Container<br />Charge</th>'
        . '<th class="number">Total Container<br /> Charge</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist = DB_fetch_array($ResultIndex)){
    
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']);
    $containercode = trim($stocklist['container']);
    $InfRowContainers = ContainerInfo($stkcode);
    $rate = ($IsTaxed==0)? 0 : $stocklist['vatrate'];
    $QuantityReturned = $stocklist['Returned'];
    $emptycost=0; $totalemptycost=0; $cvatamount =0;
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;
  
    $location = $_POST['location'][$itemcode];
    $qty = abs($stocklist['Returned']);
    $emptyunits = abs($stocklist['Returned']);
     
   
       
    if(isset($_POST['subunits'][$itemcode])){
       $qty  =$_POST['subunits'][$itemcode];
    }else{
        $qty = $stocklist['Returned'];
    }
    
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
         $salesprice = $stocklist['UnitPrice'];
    }
       
    $baseamount = ($salesprice * $qty );
    if(isset($_POST['emptycost'][$itemcode])){
         $emptycost = $_POST['emptycost'][$itemcode];
    }

    $totalemptycost = ($emptycost * $emptyunits);             
    $crate = $InfRowContainers[3];
    $cnetamount = $totalemptycost;

    if($VATinclusive=='Yes'){
         $netx = $cnetamount /  (($crate+100)/100);
         $cvatamount = $cnetamount - $netx ;
         $cgrossamount = $cnetamount ;
    }else{
        $cvatamount = $cnetamount * ($crate/100);
        $cgrossamount= $cnetamount + $cvatamount;
    }
               
    
        
    
    if($stocklist['inclusive']==true){
        $netamount = $baseamount / (($rate+100)/100);
        $vatamount = $baseamount - $netamount ;
        $grossamount = $baseamount ;
    }else{
        $vatamount  = $baseamount * ($rate/100);
        $grossamount= $baseamount + $vatamount;
        $netamount  = $baseamount;
    }
       
    
    $runningnettotal += $netamount;
    $runningvattotal += $vatamount;
    $runninggrosstotal += $grossamount;
    $Controlbox = GetUnits($stkcode);
    
    echo sprintf('<tr>'
         .'<td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td class="number"><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'"  size="5"/></td>'
         .'<td class="number"><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
         .'<td class="number"><input type="hidden" name="emptyunits['.$itemcode.']" value="'.$emptyunits.'" size="1" readonly="readonly"/>'
         .'<input type="text" class="number" name="emptycost['.$itemcode.']" value="'.$emptycost.'" size="5"/>'
         .'<input type="hidden" name="emptycode['.$itemcode.']" value="'.$containercode.'" size="1"/></td>'
         .'<td class="number"><input type="text" class="number" name="totalemptycost['.$itemcode.']" value="'.$cgrossamount.'" readonly="readonly" size="5"/></td>'
         .'<td class="number"><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
         .'<td class="number"><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
         .'<td class="number"><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
         .'</tr>',trim($stocklist['description']),$stocklist['unitofmeasure'].'(1 x'. number_format($stocklist['partperunit'],0).')');
}
   
echo sprintf('<tfoot><tr>'
        . '<td colspan="6"></td>'
              . '<td >TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningnettotal,2),
                number_format($runningvattotal,2),
                number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table>';
echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="confirm" value="' . _('Enter Credit Note Details and Confirm') . '"
            onclick="return confirm(\''._('Are you sure you wish to create this Credit Note ?').'\');" />
</div>';

echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');


function ContainerInfo($itemcode){
    global $db;
    
    $ResultIndex = DB_query("SELECT 
        `stockmaster`.`container`,
        `c`.`itemcode` ,
        `c`.`descrip` as `packname`,
        IFNULL(`cv`.`vat`,0) as CVAT
  FROM `stockmaster` 
  left join `stockmaster` c on `stockmaster`.`container`=`c`.`itemcode`
  left join `inventorypostinggroup` ci on `c`.`postinggroup`=`ci`.`code`
  left join `vatcategory` cv on `ci`.`vatcategory`=`cv`.`vatc`
  where `stockmaster`.`itemcode`='".$itemcode."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   return $stkmaster;
}


?>