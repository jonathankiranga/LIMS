<?php

include('includes/session.inc');
$Title = "PayRoll Salaries Maintenance" ;
include('includes/header.inc') ;
include('ExtFunc/salary.inc') ;

$customheader = getcustom();

echo '<link rel="stylesheet" href="css/smartpayroll.css">';
echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-money-check-alt"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Manage employee salaries</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($_POST['pfno'])){
    
 
 echo '<table class="table-bordered"><tr><td><label for="pfno">PayRoll ID</label></td>'
    . '<td><input type="text" name="pfno" value="'.$_POST['pfno'].'"  '.(isset($_POST['pfno'])?'readonly':'').' /></td>'
    . '</tr>';
   
$ResultIndex = GETEMPLOYEE($_POST['pfno']) ;
while($row = DB_fetch_array($ResultIndex)){
    
echo '<tr><td>ID Number</td><td><label>'.$row['idno'].'</label></td></tr>';
echo '<tr><td>Names</td><td><label>'.$row['fname'].$row['mname'].$row['lname'].'</label></td></tr>';
echo '<tr><td>Type of Employment</td><td><label>'.$employment[$row['freqcode']].'</label></td></tr>';

    foreach ($customheader as $key => $value) {
        $id='c'.trim($key);
        echo '<tr><td><label>'. $value .'</label></td><td><label>'. $row[$id].'</label></td></tr>';
    }
echo '<tr><td><label for="pfno">Insurance Relief</label></td>
    <td><input type="text" name="Insrelief" class="number" value="'.$row['insurancerelief'].'"/></td></tr>';

echo '<tr><td><label for="pfno">Mortgage Relief</label></td>
    <td><input type="text" name="MortgageRelief" class="number" value="'.$row['mortagerelief'].'"/></td></tr>';
    
echo '<tr><td><label for="pfno">Basic Pay</label></td>
    <td><input type="text" name="bpay" class="number" value="'.$row['basicpay'].'"/></td></tr>';

$SQL="SELECT [name],[qualification] FROM [prljobgroup] ";
$results = DB_query($SQL,$db);

echo '<tr><td><label for="pfno">Salary Scale</label></td><td><select name="salaryscale" id="jobgroup">';
    while($rowsjobgroup=DB_fetch_array($results)){
        echo '<option value="'.$rowsjobgroup['name'].'" '.($rowsjobgroup['name']==$row['salaryscale']?'selected="selected"':'').'>'.$rowsjobgroup['name']." ".$rowsjobgroup['qualification'].'</option>';
    }
echo '</select></td></tr>';


    }

echo '</table>';
echo '<div><input type="submit" class="btn rightside" name="getpf" value="submit"/></div>';

} else {

echo '<table class="table-bordered"> 
    <thead><tr>
    <th>PayRoll ID</th>
    <th>ID Number</th>
    <th>Names</th>
    <th>Type of Employment</th>
    <th>Salary scale</th>';

    foreach ($customheader as $key => $value) {
       echo '<th>'. $value .'</th>';
    }

    echo '<th>Basic Pay</th></tr></thead><tbody id="mydata">';

$k=0;
$ResultIndex = GETALLEMPLOYEES() ;
while($row = DB_fetch_array($ResultIndex)){
if($row['Active']==0){

echo '<tr><td><input type="submit" name="pfno" value="'.rtrim($row['pf_no']).'"/></td>';
echo '<td>'.$row['idno'].'</td>';
echo '<td>'.$row['fname'].$row['mname'].$row['lname'].'</td>';
echo '<td>'.$employment[$row['freqcode']].'</td>'
   . '<td>'.$row['salaryscale'].'</td>';

foreach ($customheader as $key => $value) {
   echo '<td>'. $row['c'.$key].'</td>';
}

echo '<td class="number">'.number_format($row['basicpay'],2).'</td></tr>';

}
}

echo '</tbody></table></div>
      <div class="col-md-12 text-center">
      <ul class="pagination pagination-lg pager" id="myPager"></ul>
      </div>';

}

echo '</div></form>';

unset($_POST['pfno']);

echo '</div>';
echo '</div>';
include('includes/footer.inc');
?>
