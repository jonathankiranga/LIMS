<?php
$PageSecurity = 0;

include('includes/session.inc');
$Title = "Select Employee";
include('includes/header.inc');
include("ExtFunc/humanresource.inc");
include('ExtFunc/employeetypes.inc');
include('ExtFunc/salary.inc') ;

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '<br /></p>';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'] . '" />
    <input type="submit" name="nextpage" value="View All"></form>';

$customheader = getcustom();

echo '<div><table class="table-bordered"><tr>
    <th>Personal No</th>
    <th>ID No</th>
    <th>Names</th>
    <th>Branch</th>
    <th>Tel No</th>
    <th>Email</th>';

    foreach ($customheader as $key => $value) {
       echo '<th>'. $value .'</th>';
    }

    echo '</tr>';

$ResultIndex = GETALLEMPLOYEES() ;
while($row = DB_fetch_array($ResultIndex)){

echo '<tr><td>
    <input type="submit" name="pfno" value="'. rtrim($row['pf_no']).'"/></td>';
echo '<td>'.$row['idno'].'</td>';
echo '<td>'.$row['fname'].$row['mname'].$row['lname'].'</td>';
echo '<td>'.$row['branch'].'</td>';
echo '<td>'.$row['telno'].'</td>';
echo '<td>'.$row['email'].'</td>';

foreach ($customheader as $key => $value) {
   echo '<td>'. $row['c'.$key].'</td>';
}
  echo '</tr>';
}

echo '</table>';
echo '<div><input type="submit" name="prvpage" value="Previous Page"/></div></form>';

include('includes/footer.inc');
?>
