<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Issue Assets for Hire');
include('includes/header.inc');   

echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Issue Assets for Hire') .'" alt="" />' 
    . ' ' . _('Issue Assets for Hire') . '</p>';

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
      ,`AssetsHeader`.printed
      from `AssetsHeader` join FixedAssetsLine on  `AssetsHeader`.`documentno`=FixedAssetsLine.`documentno` 
      where `AssetsHeader`.`documenttype`='55' 
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
             . '<th>Issue Equipments</th>'
             . '<th>Print Invoice</th>'
             . '<th>Return Equipments</th>'
             . '<th>Document<br /> date</th>'
             . '<th>Return<br />Due <br />Date</th>'
             . '<th>Customer<br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '<th>Print<br />Status</th>'
             . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?ref=%s">Issue Equipment To :%s</a></td>',htmlspecialchars('FixedAssetsHire.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td><a href="%s?No=%s">Print Invoice No :%s</a></td>',htmlspecialchars('PDFPrintAssetshire.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td><a href="%s?CustomerID=%s">Drill Customer Details</a></td>',htmlspecialchars('FixedAssetsHireReturned.php',ENT_QUOTES,'UTF-8'),$row['vendorcode']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',$row['printed']==1?'Has Been Printed':'Not Yet Printed');
        echo '</tr>';
  }
        
    echo '</table><br />';
        
include('includes/footer.inc');

?>