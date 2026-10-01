<?php
include('includes/session.inc');
$Title = "Register Working Days";
include('includes/header.inc');
include('Extfunc/gensalary.inc');

if(isset($_POST['submit'])){
    $errors = [];
    
    foreach($_POST['DayWeek'] as $dayName => $value) {
        $isWorking = ($value == '1') ? '1' : '0';
        $sql = "UPDATE prlweekends SET is_working_day = '$isWorking' WHERE day_name = '" . addslashes($dayName) . "'";
        $result = DB_query($sql, $db);
        if(DB_error_no($db) > 0) {
            $errors[] = DB_error_msg($db);
        }
    }
    
    if(count($errors) > 0) {
        prnMsg(implode('<br>', $errors), 'warn');
    } else {
        prnMsg('Working days saved successfully', 'success');
    }
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '<br /></p>';
echo '<div class="centre"><code>When the system calculates days to deduct from salary when absent, it looks at the off days and working days.</code></div>';

$sql = "SELECT Dayoftheweek, isworkingday FROM Dayoftheweeks ORDER BY CASE Dayoftheweek 
        WHEN 'Sunday' THEN 0 WHEN 'Monday' THEN 1 WHEN 'Tuesday' THEN 2 
        WHEN 'Wednesday' THEN 3 WHEN 'Thursday' THEN 4 WHEN 'Friday' THEN 5 WHEN 'Saturday' THEN 6 END";
$result = DB_query($sql, $db);

if(DB_num_rows($result) == 0) {
    $defaultDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $workingDays = [0, 1, 1, 1, 1, 1, 0];
    foreach($defaultDays as $i => $day) {
        $insertSql = "INSERT INTO Dayoftheweeks (Dayoftheweek, isworkingday) VALUES ('$day', " . $workingDays[$i] . ")";
        DB_query($insertSql, $db);
    }
    $result = DB_query($sql, $db);
}

echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<table class="table table-bordered" style="max-width:400px;">';
echo '<thead><tr><th>Day of Week</th><th class="text-center">Working Day</th></tr></thead>';
echo '<tbody>';

$weekendDays = ['Sunday', 'Saturday'];
while($row = DB_fetch_array($result)) {
    $checked = ($row['isworkingday'] == 1) ? 'checked' : '';
    $dayName = $row['Dayoftheweek'];
    
    $rowClass = in_array($dayName, $weekendDays) ? 'active' : '';
    
    echo '<tr class="' . $rowClass . '">';
    echo '<td><i class="fas fa-calendar-day"></i> ' . $dayName . '</td>';
    echo '<td class="text-center">';
    echo '<input type="hidden" name="DayWeek[' . htmlspecialchars($dayName) . ']" value="0" />';
    echo '<input type="checkbox" name="DayWeek[' . htmlspecialchars($dayName) . ']" value="1" ' . $checked . ' class="form-check-input" />';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '<tfoot><tr><td colspan="2" class="text-center"><input type="submit" name="submit" value="Save" class="btn btn-success" /></td></tr></tfoot>';
echo '</table>';
echo '</form>';

echo '<div class="well" style="max-width:400px;margin:20px auto;">
<h5><i class="fas fa-info-circle"></i> Note</h5>
<ul class="mb-0">
<li>Weekends (Saturday & Sunday) are highlighted</li>
<li>Working days are used to calculate leave entitlements</li>
<li>Non-working days are excluded from salary calculations when absent</li>
</ul>
</div>';

include('includes/footer.inc');
?>
