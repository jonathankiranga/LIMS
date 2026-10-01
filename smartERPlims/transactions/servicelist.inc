<?php

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    

$sqldebtors=DB_query("SELECT itemcode 
      ,creditlimit,customer
      ,phone ,email 
      ,city ,country
      ,curr_cod,customerposting
      ,salesman,VATinclusive,IsTaxed
       FROM debtors join postinggroups on code=customerposting
       where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];
$IsTaxed = $debtorsrow[11];
    
$ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        stockmaster.partperunit,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        cv.vat as CVAT
                  FROM stockmaster left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc
                  where stockmaster.inactive=0 and stockmaster.isstock_3=1
                  order by stockmaster.descrip", $db);
      

echo '<div class="container">'
. '<table id="saleslines"  class="table table-bordered"><thead><tr>'
        . '<th>Item_Code</th>'
        . '<th>Service_Description</th>'
        . '<th>Unit_Of_Measure</th>'
        . '<th class="number">Quantity to_Order</th>'
        . '<th class="number">Fee</th>'
        . '<th class="number">Net_Amount</th>'
        . '<th class="number">VAT_Amount</th>'
        . '<th class="number">Gross_Amount</th></tr>'
        . '</thead><tbody id="sales_lines">';

while($stocklist=DB_fetch_array($ResultIndex)){
    $itemcode = trim($stocklist['itemcode']);
    $rate = ($IsTaxed==false)?0:$stocklist['vat'];
    
    $emptycost=0; $totalemptycost=0; $cvatamount =0;
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;
 
    if(isset($_POST['quantity'][$itemcode])){
        $qty= $_POST['quantity'][$itemcode];
    }else{
        $qty=.0;
    }
        
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice= $_POST['salesprice'][$itemcode] ;
    }else{
        $salesprice=.0;
    }
    
    
    if($_POST['UOM'][$itemcode]=='fulqty'){
          $baseamount = ($salesprice * ($qty * $stocklist['partperunit']));
    }else{
          $baseamount= ($salesprice * $qty);
     }
            
    if($VATinclusive==true){
        $netamount =$baseamount * (1- round(($rate/(100+$rate)),2));
        $vatamount = $netamount * round(($rate/(100+$rate)),2);
        $grossamount=$baseamount ;
    }else{
        $vatamount  = $baseamount * ($rate/100);
        $grossamount= $baseamount + $vatamount;
        $netamount  = $baseamount;
    }
        
    $runningnettotal += $netamount;
    $runningvattotal += $vatamount;
    $runninggrosstotal += $grossamount;
    
    
         echo sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="integer" size="4" class="integer" name="quantity['.$itemcode.']" value="'.$qty.'" /></td>'
              . '<td><input type="text"  size="4" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" /></td>'
              . '<td><input type="text"  size="5" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" /></td>'
              . '<td><input type="text"  size="5" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" /></td>'
              . '<td><input type="text"  size="5" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" /></td>'
            . '</tr>',trim($stocklist['barcode']),
                      trim($stocklist['stockname']),
                      CreateUnits($itemcode,$itemcode));
}
   
echo sprintf('</tbody><tfoot><tr>'
              . '<td></td>'
              . '<td></td>'
              . '<td></td>'
              . '<td>Total Order</td>'
              . '<td class="number"><B>%s</B></td>'
              . '<td class="number"><I>%s</I></td>'
              . '<td class="number"><B>%s</B></td>'
              . '</tr></tfoot>', 
            number_format($runningnettotal,2),
            number_format($runningvattotal,2),
            number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table></div>';

echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="submit" value="' . _('Confirm Order') . '"  />
</div>';

?>