<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Sales Picking List');
include('includes/header.inc');   

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Picking List') .'" alt="" />' . ' ' . _('Sales Picking List') . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="container">'
    . '<table class="table table-bordered"><tr>'
    . '<td><input type="submit" value="Refresh" id="f1lt3r" class="btn-info" />'
    . '<input type="hidden" name="CustomerID" id="CustomerID"  value="' . $_POST['CustomerID'] . '"/></td>'
    . '<td><input type="button" id="filtercustomer" value="Search Customer" class="btn-info" />Customer Name</td>'
    . '<td><input type="text" name="CustomerName" id="CustomerName" value="' . $_POST['CustomerName'] . '"   readonly="readonly"/>'
    . '<br /><a href="?All">RESET</a></td></tr>'
    . '</table></div>';


$SQL="select 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid` ,
      sum(SalesLine.`invoiceamount`) as OrderValue
      from `SalesHeader` join SalesLine on `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='10'  ".    
        (isset($_POST['CustomerID'])?" and `SalesHeader`.`customercode`='".$_POST['CustomerID']."'":'')."
      group by 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid` order by `SalesHeader`.`docdate` desc";
    $Result=DB_query($SQL,$db);
    
    Echo '<Table class="table table-bordered"><tr>'
        . '<th colspan="2">Action</th>'
        . '<th>Date</th>'
        . '<th>Customer <br />ID</th>'
        . '<th>Customer <br /> Name</th>'
        . '<th>Sales <br />Order<br /> Value</th>'
        . '<th>Currency </th>'
        . '<th>Sales <br /> Person</th>'
        . '<th>Authorisation <br /> Status</th>'
        . '<th>Created <br /> By</th>'
        . '</tr>';
    
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        
        echo sprintf('<td><a href="%s?ref=%s">Return Only Inventory:%s </a></td>',
        htmlspecialchars('SalesStockReturns.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td><a href="%s?ref=%s">Post Credit Note:%s :</a></td>',
        htmlspecialchars('Salescreditnote.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',getSalemanDescrip($row['salespersoncode']));
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo '</tr>';
  }
        
    echo '</table><br />';
    
 echo '</div></form>';
     
     
include('includes/footer.inc');