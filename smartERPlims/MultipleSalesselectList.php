<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Sales List');
include('includes/header.inc');   

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales List') .'" alt="" />' . ' ' . _('Sales List') . '</p>';

echo '<form autocomplete="off"action="MultipleSalescreditnote.php" method="post">'
. '<input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="container">'
    . '<table class="table table-bordered"><tr>'
    . '<td><input type="hidden" name="CustomerID" id="CustomerID"  value="' . $_POST['CustomerID'] . '"/>'
    . '<input type="button" id="filtercustomer" value="Search Customer" class="btn-info" />Customer Name</td>'
    . '<td><input type="text" name="CustomerName" id="CustomerName" value="' . $_POST['CustomerName'] .'"   readonly="readonly"/>'
    . '<input type="button" value="Refresh" id="vatrefresh" class="btn-info" /><br /><a href="?All">Clear</a></td></tr>'
    . '</table></div><span id="SalesResults"></sapn>';

 echo '</div></form>';
     
     
include('includes/footer.inc');


?>