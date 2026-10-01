<?php
include('includes/session.inc');
$Title = _('Tasks Management Report');
include('includes/productionheader.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

$FR = new MonthlyPeriods();
$URL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' .$Title. '" alt="" />' . ' ' .$Title. '</p>';
echo '<form autocomplete="off"action="'.$URL.'" method="post" id="Taskform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>'
        . '<input type="hidden" id="TaskName" value="TASK"/>';
echo '<div class="container">';
echo '<table class="table-bordered"><tr><td>Color Code</td><td><input placeholder="Deadline Not yet" readonly="readonly" style="background-color:pink"/>'
        . '<input placeholder="Current or Closed" readonly="readonly" style="background-color:white;"/>'
        . '<input placeholder="Dead line Over due" readonly="readonly" style="background-color:lightcyan;"/></td></tr>';
$FR->Get();
echo '</table>';
echo '</div></form>' ;
echo '<div class="container">';
echo ' <table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
        <thead>
            <tr>
                <th>Target</th>
                 <th>Search text</th>
                <th>Treat as regex</th>
                <th>Use smart search</th>
            </tr>
        </thead>
        <tbody>
            <tr id="filter_global">
                <td>Global search</td>
                <td align="center"><input type="text" class="global_filter" id="global_filter"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_regex"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_smart" checked="checked"></td>
    
            </tr>
            <tr id="filter_col1" data-column="0">
                <td>Column - USER</td>
                <td align="center"><input type="text" class="column_filter" id="col0_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_smart" checked="checked"></td>
      
            </tr>
          </tbody>
    </table><table class="register display table-bordered" style="width:100%" id="TaskReport">';
echo '<thead><tr><th>Task Owner/User</th><th>Task Name</th><th>Due On</th><th>Priority</th><th>Task Details</th><th>Task Status</th></tr></thead><tbody>';
echo '</tbody><tfoot><tr><th>Task Owner/User</th><th>Task Name</th><th>Due On</th><th>Priority</th><th>Task Details</th><th>Task Status</th></tr></tfoot>';
echo '</table>';
echo '</div>';  
  
include('includes/footer.inc');


?>