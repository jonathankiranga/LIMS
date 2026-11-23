<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Open Purchase Orders List');
include('includes/header.inc');   

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Purchase Orders List') .'" alt="" />' 
        . ' ' . _('Purchase Orders List') . '</p>';

$SQL="Select Distinct 
       `purchaseheader`.`documentno`
      ,`purchaseheader`.`docdate`
      ,`purchaseheader`.`oderdate`
      ,`purchaseheader`.`duedate`
      ,`purchaseheader`.`vendorcode`
      ,`purchaseheader`.`vendorname`
      ,`purchaseheader`.`currencycode`
      ,`purchaseheader`.`status`
      ,`purchaseheader`.`userid` 
      from `purchaseheader` 
      join purchaseline on `purchaseheader`.`documentno`=purchaseline.`documentno` 
      where `purchaseheader`.`documenttype`='18' 
      and `purchaseheader`.`released`=1 and purchaseheader.status=1
      order by `purchaseheader`.`docdate` desc";
    $Result=DB_query($SQL,$db);
           // --and purchaseheader.status=2
    Echo '<DIV class="container">'
        . '<Table class="table table-bordered"><tr>'
        . '<th>Supplier Invoice<br /> for GRN</th>'
        . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
        . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
        . '<th>Vendor <br />ID</th>'
        . '<th>Vendor<br /> Name</th>'
        . '<th>Purchase <br />Order<br /> Value</th>'
        . '<th>Currency</th>'
        . '<th>Authorisation<br /> Status</th>'
        . '<th>Created<br /> By</th>'
        . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Invoice:%s</a></td>',htmlspecialchars('SupplierInvoice.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo '</tr>';
  }
        
    echo '</table></DIV>';
    
     
include('includes/footer.inc');


?>