<?php
include('includes/session.inc');
$Title = "PayRoll Salaries Maintenance" ;
include('includes/header.inc') ;
include('ExtFunc/salary.inc') ;

if(isset($_POST['Update'])){

    DB_Txn_Begin($db);
    $errors=0;

    foreach ($_POST['newbasicpay'] as $key => $value) {
        $SQL= sprintf("update prlemployeemaster set basicpay=%s where pf_no='%s'",$value,$key);
        DB_query($SQL,$db);
        if(DB_error_no($db)>0){
            $errors++;
        }
    }

    if($errors==0){
        DB_Txn_Commit($db);
        prnMsg('You have updated successfuly');
    }else{
        DB_Txn_Rollback($db);
         prnMsg('Something failed to update. Please try again','warn');
    }

}

$customheader = getcustom();

echo '<link rel="stylesheet" href="css/smartpayroll.css">';
echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-chart-line"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Salary increment management</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<div><div><div>
 <table class="table table-bordered"><thead><tr>
    <th>Personal<br /> Number</th>
    <th>ID <br />Number</th>
    <th>Names</th>
    <th>N.S.S.F <br />NO </th>
    <th>P.A.Y.E <br />NO </th>
    <th>N.H.I.F. <br />NO </th>
    <th>Type of <br />Employment</th>';

    foreach ($customheader as $key => $value) {
       echo '<th>'. $value .'</th>';
    }

echo '<th>Current <br />Basic Pay</th>
      <th>To be <br />increased<br /> by</th>
      <th>New <br />Basic pay</th></tr></thead>';

$k=0;
$ResultIndex = showbasicpayforall() ;
while($row = DB_fetch_array($ResultIndex)){
    
if($row['Active']==0){
        
echo '<tr><td>'.$row['pf_no'].'</td>';
echo '<td>'.$row['idno'].'</td>';
echo '<td>'.$row['fname'].$row['mname'].$row['lname'].'</td>';
echo '<td>'.$row['nssf_no'].'</td>';
echo '<td>'.$row['paye_no'].'</td>';
echo '<td>'.$row['nhif_no'].'</td>';
echo '<td>'.$employment[$row['freqcode']].'</td>';

foreach ($customheader as $key => $value) {
   echo '<td>'. $row['c'.$key].'</td>';
}

$amt = ($row['basicpay'] + $row['increment']);
$newbasicpay = ($amt > $row['max'] ? $row['basicpay'] : $amt );

    echo '<td class="number">'.number_format($row['basicpay'],2).'</td>
      <td class="number">'.number_format($row['increment'],2).'</td>
      <td class="number"><input type="text" value="'. $newbasicpay .'"  size="10" class="number" name="newbasicpay['. trim($row['pf_no']).']"/></td></tr>';

    }
    
}
echo '</table></div>
      </div></div>
    <div>
    <input type="submit" name="Update"  value="Perform Salary Increase"/>
    </div>';


echo '</div>';
echo '</div>';
include('includes/footer.inc');

?>
