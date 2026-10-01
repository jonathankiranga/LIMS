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

$SQL="select 
       `AssetsHeader`.`documentno`
      ,`AssetsHeader`.`docdate`
      ,`AssetsHeader`.`oderdate`
      ,`AssetsHeader`.`duedate`
      ,`AssetsHeader`.`vendorcode`
      ,`AssetsHeader`.`vendorname`
      ,`AssetsHeader`.`currencycode`
      ,`AssetsHeader`.`status`
      ,`AssetsHeader`.`userid` 
      ,sum(FixedAssetsLine.`invoiceamount`) as OrderValue
      ,`AssetsHeader`.printed
      from `AssetsHeader` join FixedAssetsLine on 
      `AssetsHeader`.`documentno`=FixedAssetsLine.`documentno` 
      where `AssetsHeader`.`documenttype`='18' 
      and `released`=1  
      group by 
       `AssetsHeader`.`documentno`
      ,`AssetsHeader`.`docdate`
      ,`AssetsHeader`.`oderdate`
      ,`AssetsHeader`.`duedate`
      ,`AssetsHeader`.`vendorcode`
      ,`AssetsHeader`.`vendorname`
      ,`AssetsHeader`.`currencycode`
      ,`AssetsHeader`.`status`
      ,`AssetsHeader`.`userid`
      ,`AssetsHeader`.printed ";
    $Result=DB_query($SQL,$db);
       
    
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Recive</th>'
             . '<th>Print Order</th>'
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
             . '<th>Vendor <br />ID</th>'
             . '<th>Vendor<br /> Name</th>'
             . '<th>Purchase <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '<th>Print<br />Status</th>'
             . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
      
        echo sprintf('<td><a href="%s?ref=%s">Receive for No :%s</a></td>',htmlspecialchars('FixedAssetsReceived.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td><a href="%s?No=%s">Print Order No :%s</a></td>',htmlspecialchars('PDFPrintAssetsOrder.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',$row['printed']==1?'Has Been Printed':'Not Yet Printed');
        echo '</tr>';
  }
        
    echo '</table><br />';
    
     
include('includes/footer.inc');


?>