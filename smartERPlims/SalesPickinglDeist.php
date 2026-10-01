<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Sales Picking List');
include('includes/header.inc');   

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Picking List') .'" alt="" />' . ' ' . _('Sales Picking List') . '</p>';

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
      from `SalesHeader` 
      join SalesLine on `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='1' 
      and `SalesHeader`.`released`=1 and `SalesHeader`.`status`=2
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
      ,`SalesHeader`.`userid` ";
    $Result=DB_query($SQL,$db);
       
    Echo '<div class="container"><Table class="table table-bordered"><tr>'
             . '<th>Dispatch <br />Sales Order</th>'
             . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
             . '<th>Sales <br /> Order <br />Due <br />Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Sales<br /> Person</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
      
        echo sprintf('<td><a href="%s?ref=%s">Order:%s</a></td>',
        htmlspecialchars('SalespickingList.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
                
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
      
        echo '</tr>';
  }
        
    echo '</table><DIV />';
    
     
include('includes/footer.inc');


?>