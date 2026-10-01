<?php
echo '<table class="table table-bordered">'
        . '<thead><tr>'
        . '<th><label>Bar<br />Code</label></th>'
        . '<th><label>Stock <br />Description</label></th>'
        . '<th><label>Unit Of <br />Measure</label></th>'
        . '<th><label>Kit size</label></th>'
        . '<th><label>Average <br />Cost <br />per part</label></th>'
        . '<th class="number"><label>Quantity<br /> to Order</label></th>'
        . '<th class="number"><label>Price <br />per Kit part</label></th>'
       . '<th class="number"><label>Price <br />of empties</label></th>'
        . '<th class="number"><label>Total <br />Value of <br />empties</label></th>'
        . '<th class="number"><label>Net Amount</label></th>'
        . '<th class="number"><label>VAT Amount</label></th>'
        . '<th class="number"><label>Gross Amount</label></th></tr>'
        . '</thead>';

$itemcode = $_POST['stockitemcode'];

$POSclass->Getitems($_POST['stockitemcode'],
        $_POST['quantity'][$itemcode],
        $_POST['salesprice'][$itemcode],
        $_POST['emptycost'][$itemcode]);

echo '<td>Trade Discount</td><td colspan="11"><select  name="TradeDiscount"><option></option>';

$ResultIndex=DB_query(sprintf("SELECT Rate,QTY,rowid FROM Discouts where QTY<='%s'",$_SESSION['TotalInvoiceQty']), $db);

while($row=DB_fetch_array($ResultIndex)){
   echo sprintf('<option value="%s" %s >%f</option>',
           $row['rowid'],($_POST['TradeDiscount']==$row['rowid']?'selected="selected"':''), $row['Rate']);
}
    
echo '</select></td></tr>';
echo '</table><div class="rightside">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
        <input type="submit" name="submit" value="' . _('Add New Line') . '" />
 	<input type="submit" name="submit" value="' . _('Enter Delivery Details and Confirm Order') . '"  />
</div>';

?>