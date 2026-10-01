<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Sales Representatives');
include('includes/header.inc');
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' . _('Sales Representatives') .'" alt="" />' 
. ' ' . _('Sales Representatives') . '</p>';


echo '<form><table class="table">'
        . '<thead><TR><th>Account Code</th>'
        . '<th>Name</th>'
        . '<th>%</th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Target P/M</th>'
        . '</tr></thead><tbody>';
      
       $results=DB_query('Select code,salesman,commission,phone,email,target from salesrepsinfo', $db);
       $k=0;
       while($rows=DB_fetch_array($results)){
           if ($k==1) {
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo  '<tr class="OddTableRows">';;
			$k++;
		}
           echo sprintf('<td><a href="SalesMan.php?Modify=%s">%s</a></td>',
                   trim($rows['code']),trim($rows['code']));
                    echo '<td>'.$rows['salesman'].'</td>';
                    echo '<td>'.number_format($rows['commission'],0).'</td>';
                    echo '<td>'.trim($rows['phone']).'</td>';
                    echo '<td>'.trim($rows['email']).'</td>'
                    . '<td>'.number_format($rows['target'],2).'</td>'
                . '</tr>';
            
       }        

echo '</tbody></table></form>';


include('includes/footer.inc');


?>
