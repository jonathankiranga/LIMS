<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

$Title = _('Sales Representatives Target');

include('includes/header.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' .$Title.'" alt="" />' 
. ' ' . $Title . '</p>';

If(isset($_POST['Financial_Periods'])){
    
$periodno = GetPeriod($_POST['Financial_Periods'], $db);


echo '<form><table class="table table-bordered" id="testTable">'
        . '<thead><TR><th>Account Code</th>'
        . '<th>Name</th>'
        . '<th>Month</th>'
        . '<th>% Commission</th>'
        . '<th>Target For the month</th>'
        . '<th>Actual Sold</th>'
        . '<th>% of Target</th>'
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
                
                $salesamount = getsales($rows['code'],$periodno);
                $target = $rows['target'];
                
           echo sprintf('<td><a href="SalesMan.php?Modify=%s">%s</a></td>',trim($rows['code']),trim($rows['code']));
                    echo '<td>'.$rows['salesman'].'</td>';
                    echo '<td>'.$_POST['Financial_Periods'].'</td>';
                    echo '<td>'.number_format($rows['commission'],0).'</td>';
                    echo '<td>'.number_format($target,2).'</td>';
                    echo '<td>'.number_format($salesamount,2).'</td>';
                    echo '<td>'.number_format(($salesamount/$target) * 100,2).'</td>';
                    echo '</tr>';
            
       }        

echo '</tbody></table>';

echo '<input type="button" onclick="tableToExcel(\'testTable\', \'Supplier Stock exchange\')" value="Export to Excel"></form>';

}else{
    
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table class="table table-bordered"><tr><td>Reporting Period</td></tr>'
          . '<tr><td>Select Month :</td>'
          . '<td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Financial_Periods" size="11" maxlength="10" autofocus="autofocus" required="required"   onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>'
          . '</tr>';
  echo '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Display Summary"/></td></tr></table>';
  echo '</div></form>';
  
}


include('includes/footer.inc');



function getsales($salesid,$periodid){
    global $db;
    $total=0;
    
    $sql=sprintf("Select 
             sum(SalesLine.invoiceamount) as sales 
             from salesheader 
             join SalesLine on salesheader.documentno=SalesLine.documentno 
             where salesheader.documenttype='10' 
             and salesheader.period=%s 
             and salesheader.salespersoncode='%s'",$periodid, $salesid);
    
    $ResultIndex= DB_query($sql,$db);
    $row=DB_fetch_row($ResultIndex);
    
    $total=$row[0];
    
   return $total;
   
}

?>
