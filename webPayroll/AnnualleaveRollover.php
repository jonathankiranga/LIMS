<?php
include('includes/session.inc');
$Title = _('Assign leave days for This Year '.$_SESSION['CalenderStartdate']);
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';
echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


if(isset($_POST['LeaveDaysYear'])){
$_SESSION['LeaveDaysYear']=$_POST['LeaveDaysYear'];

$Myarray=array();
$SQL="SELECT "
        . "[Inactive],"
        . "[pf_no],"
        . "[dateemployed],"
        . "[dateterminated] "
        . " FROM [prlemployeemaster] "
        . " where ([dateterminated]<[dateemployed]) or ([dateterminated] is null) and [freqcode]=1 ";

$Results=DB_query($SQL,$db);
while($row=DB_fetch_array($Results)){
    $Myarray[]=$row;
   
}

$refno = GetNextTransNo(500,$db);
$sql=array();
   
$sql[]="Delete from prlstaffleavemaster where year in "
        . "(select DATEPart(year,confvalue) from config  "
        . " where confname='CalenderStartdate')";

foreach ($Myarray as $value) {
    $refno ++;
      $sql[] = SPRINTF("INSERT INTO [prlstaffleavemaster]"
            . " ([refno],[pfno],[days],[year])"
            . " (select '%s','%s','%s', DATEPart(year,confvalue) from config  "
           . " where confname='CalenderStartdate')",
            $refno , $value['pf_no'] ,$_SESSION['LeaveDaysYear']);
}

foreach ($sql as $SQL) {
  $Results=DB_query($SQL,$db);
}

$SQL="Select [refno],[pfno],S.fname+S.mname+S.lname as Names,[year],[days] "
        . "from [prlstaffleavemaster] join prlemployeemaster S on pf_no = pfno where year in "
        . "(select DATEPart(year,confvalue) from config where confname='CalenderStartdate')";

 $Results=DB_query($SQL,$db);
 $echo='<div class="table-responsive">
    <table class="table-bordered"><thead>'
 . '<tr><th>Staff Names</th><th>This Year</th><th>Leave Days Allocated for the year</th></tr></thead>';
 while($Rows=DB_fetch_array($Results)){
       $echo .= sprintf('<tr><td>%s</td>'
             . '<td><input type="text" value="%s" readonly="readonly"/></td>'
             . '<td><input type="text" value="%s" readonly="readonly"/></td></tr>',
             $Rows['Names'],$Rows['year'],$Rows['days']);
 }
$echo .= '</table></div>';

echo $echo ;

} else {
    
echo '<div class="table-responsive">
      <table class="table-bordered"><tr>'
. '<td colpan="2">System Setting for leave days in a year</td></tr>'
. '<tr><td>Set the No of Leave days @ employee </td>'
. '<td><input type="text" class="number" value="'.$_SESSION['LeaveDaysYear'].'" name="LeaveDaysYear"/></td></tr>'
. '<tr><td colpan="2"><input type="submit" name="submit" value="Assign" /></td></tr>'
. '</table>';

}
echo '</form>';


include('includes/footer.inc');
?>