<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Sales Invoice');
include('includes/header.inc');   

    echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Sales Invoice') . '</p>';

$SQL="select 
       `salesheader`.`documentno`
      ,`salesheader`.`docdate`
      ,`salesheader`.`oderdate`
      ,`salesheader`.`duedate`
      ,`salesheader`.`customercode`
      ,`salesheader`.`customername`
      ,`salesheader`.`currencycode`
      ,`salesheader`.`salespersoncode`
      ,`salesheader`.`status`
      ,`salesheader`.`userid` 
      ,sum(salesline.`invoiceamount`) as OrderValue
      ,`salesheader`.`documenttype`
      ,salesheader.entryno
      from `salesheader` 
      join salesline on `salesheader`.`documentno`=salesline.`documentno` 
      where (`salesheader`.`documenttype`='1' and `salesheader`.`released`=1) 
      or `salesheader`.`documenttype`='40' 
      group by 
       `salesheader`.`documentno`
      ,`salesheader`.`docdate`
      ,`salesheader`.`oderdate`
      ,`salesheader`.`duedate`
      ,`salesheader`.`customercode`
      ,`salesheader`.`customername`
      ,`salesheader`.`currencycode`
      ,`salesheader`.`salespersoncode`
      ,`salesheader`.`status`
      ,`salesheader`.`documenttype`
      ,salesheader.entryno
      ,`salesheader`.`userid` 
      order by 
      `salesheader`.`entryno` DESC";
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