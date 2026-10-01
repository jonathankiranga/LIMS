<?php

echo '<table class="table table-bordered"><thead><tr><th>StockID</th><th>Stock Description</th><th>UOM</th><th class="number">Ave Cost<br />Per Kit</th><th class="number">Kit size</th><th class="number">STD Quantity<br />To Use</th></thead>';

If($_POST['AddOneMore']=='Add New Line'){
   $bomclass->Autoshowtable() ;
   UnSET($_POST['AddOneMore']);
} else {
  $bomclass->Getitems($_POST['stockitemcode'],$_POST['quantity'][$_POST['stockitemcode']]);
}

echo '<tfoot><tr><td colspan="6"></td></tr></tfoot></table>';

echo '<div>
 <input type="submit" name="submit" value="Refresh"/>
 <input type="submit" name="submit" value="' . _('Add New Line') . '" />
 <input type="submit" name="submit" value="' . _('Delete all') . '" />
 <input type="submit" name="confirm" value="Confirm Details"  onclick="return confirm(\''._('Do you want to save these details ?').'\');" />'
. '</div>';

?>