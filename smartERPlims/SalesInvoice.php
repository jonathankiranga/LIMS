<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Sales Invoice');
include('includes/header.inc');   

    echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Sales Invoice') . '</p>';

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
      ,`SalesHeader`.`userid` 
      ,sum(SalesLine.`invoiceamount`) as OrderValue
      ,`SalesHeader`.`documenttype`
      ,SalesHeader.entryno
      from `SalesHeader` 
      join SalesLine on `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where (`SalesHeader`.`documenttype`='1' and `SalesHeader`.`released`=1) 
      or `SalesHeader`.`documenttype`='40' 
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
      ,`SalesHeader`.`documenttype`
      ,SalesHeader.entryno
      ,`SalesHeader`.`userid` 
      order by 
      `SalesHeader`.`entryno` DESC";
    $Result=DB_query($SQL,$db);
       
    Echo '<table class="table-condensed table-responsive-small table-bordered"><tr>'
             . '<td>Sales Order</td>'
             . '<td>Date</td>'
             . '<td>Customer ID</td>'
             . '<td>Customer Name</td>'
             . '<td>Total Value</td>'
             . '<td>Currency</td>'
             . '<td>Sales Person</td>'
             . '<td>Status</td>'
             . '<td>User</td>'
             . '</tr>';
    
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?ref=%s">Order:%s</a></td>',
        htmlspecialchars('SalesInvoicefromorders.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
              
     echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',getSalemanDescrip($row['salespersoncode']));
        echo sprintf('<td>%s</td>',$row['documenttype']==1?'Sales Order':'Toll Blending');
        echo sprintf('<td>%s</td>',$row['userid']);
            
        echo '</tr>';
  }
        
    echo '</table><br />';
    
     
include('includes/footer.inc');


?>