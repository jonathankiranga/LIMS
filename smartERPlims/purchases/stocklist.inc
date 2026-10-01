<?php
$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
echo '<table class="table-bordered"><thead><tr>'
        . '<th>Stock Code</th>'
        . '<th>Stock Description</th>'
        . '<th>Unit Of Measure (UOM)</th>'
        . '<th>Kit size (@ X 1)</th>'
        . '<th>Ave Cost per part</th>'
        . '<th class="number">Quantity to Order</th>'
        . '<th class="number">Price<br/> for<br/> the <br/>Selected<br/> UOM</th>'
        . '<th class="number">% Discount</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$itemcode = $_POST['stockitemcode'];
$POSclass->Getitems($_POST['stockitemcode'],$_POST['quantity'][$itemcode],$_POST['salesprice'][$itemcode]);

echo '<tr><td colspan="2">Find Stock</td>'
    . '<td colspan="9"><input type="hidden" id="stockitemcode" size="5" name="stockitemcode" value="'.$_POST['stockitemcode'].'" />'
    . '<input type="text" id="stockname" size="30" name="stockname" value="'.$_POST['stockname'].'" />'
    . '<input type="button" id="searchstock" value="Search stock"/></td></tr>';
echo '<tr><td colspan="11">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
        <input type="submit" name="submit" value="' . _('Add New Line') . '" />
	<input type="submit" name="submit" value="' . _('Enter Delivery Details and Confirm Order') . '"  />
      </td></tr>';
echo '</table>';


?>