<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Fixed Asset Returns');
include('includes/header.inc');   

$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Fixed Asset Returns') .'" alt="" />' . ' ' . _('Fixed Asset Returns') . '</p>';
 
  $SQL=Sprintf("select `documentno`,`docdate`,`duedate`,`vendorcode`,`vendorname` ,"
          . " DATEDIFF(NOW(),`duedate`) as overdue"
          . " from `AssetsHeader` where `AssetsHeader`.`documenttype`=55 "
          . " and `AssetsHeader`.`released`=1 and `vendorcode`='%s' ",$_GET['CustomerID']);
          
           
 Echo '<Table class="table table-bordered"><tr>'
             . '<th>Document No</th>'
             . '<th>Document<br /> date</th>'
             . '<th>Customer<br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Return<br />Due <br />Date</th>'
             . '<th>Over Due Days</th>'
             . '</tr>';

  $Result= DB_query($SQL, $db);
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?ref=%s">Receive Equipment for No :%s</a></td>',htmlspecialchars('FixedAssetsBackTostore.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['overdue']>0?$row['overdue']:'On Time');
        echo '</tr>';
  }
        
    echo '</table><br />';

include('includes/footer.inc');

?>
