<?php
include('includes/session.inc');
$Title = _('Employee Shortcut Link');
include('includes/header.inc');
include('ExtFunc/salary.inc') ;

$self = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_SESSION['payrollid'])){
     $selectedpfno = $_SESSION['payrollid'];
     $sql = sprintf("Select * from [prlemployeemaster] where pf_no='%s'",$selectedpfno);
     $results = db_query($sql,$db) ;
     $rows = DB_fetch_array($results) ;
     $staffname = $rows['fname'] .' '. $rows['mname'] .' '. $rows['lname'];
 
} else {
    $selectedpfno="";
}



echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/supplier.png"   title="' . _('Employee') . '" alt="" />' .
        ' ' . _('Staff Name: ')  . $staffname . ' - PF No: ' .  $selectedpfno . _(' has been selected') . '</p>';

echo '<div class="page_help_text">' . _('Select a menu option to use on this branch') . '.</div><br />';
echo '<table class="table-bordered">
        <tr>
            <th style="width:33%">' . _('Employee Inquiries') . '</th>
            <th style="width:33%">' . _('Employee Transactions') . '</th>
            <th style="width:33%">' . _('Employee Maintenace') . '</th>
        </tr>';
echo '<tr><td valign="top" class="select">
    <a href="prlselfcreditloans.php?pf_no='. $selectedpfno.'">My Loans</a>
    </td>
    <td valign="top" class="select">
    <a href="prlstaffleave.php?leaveid='. $selectedpfno.'">Leave Application</a>
    </td>
    <td valign="top" class="select">
    <a href="prlhr.php?editid='. $selectedpfno.'">Edit My Details</a>
    </td></tr>';

echo '<tr><td valign="top" class="select">
    <a href="prlpaye.php?costid='. $selectedpfno.'">PAYE P9 Report</a>
    </td>
    <td valign="top" class="select"></td>
    <td valign="top" class="select"></td></tr>';

echo '<tr><td valign="top" class="select">
    <a href="prlselfsaccosavings.php?pf_no='. $selectedpfno.'">My SACCO </a>
    </td>
    <td valign="top" class="select"></td>
    <td valign="top" class="select"></td></tr>';

echo '<tr><td valign="top" class="select">
    <a href="prlmasterroll.php?costid='. $selectedpfno.'">My payslip</a>
    </td>
    <td valign="top" class="select"></td>
    <td valign="top" class="select"></td></tr>';


echo '</table>';



   

include('includes/footer.inc');
?>
