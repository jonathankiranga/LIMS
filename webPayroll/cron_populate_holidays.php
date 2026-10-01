<?php
include('includes/session.inc');

function PopulateKenyaHolidays($year) {
    global $db;
    
    $fixed_holidays = [
        ['name' => "New Year's Day", 'month' => 1, 'day' => 1],
        ['name' => "Jamhuri Day", 'month' => 12, 'day' => 12],
        ['name' => "Christmas Day", 'month' => 12, 'day' => 25],
        ['name' => "Boxing Day", 'month' => 12, 'day' => 26]
    ];
    
    foreach($fixed_holidays as $h) {
        $date = sprintf('%d-%02d-%02d', $year, $h['month'], $h['day']);
        $sql = sprintf("INSERT IGNORE INTO prlkenyaholidays (holiday_name, holiday_date, holiday_type, year)
                       VALUES ('%s', '%s', 'FIXED', %d)",
                       $h['name'], $date, $year);
        DB_query($sql, $db);
    }
    
    $easter = date('Y-m-d', easter_date($year));
    $good_friday = date('Y-m-d', strtotime($easter . ' -2 days'));
    $easter_monday = date('Y-m-d', strtotime($easter . ' +1 day'));
    
    $easter_holidays = [
        ['name' => 'Good Friday', 'date' => $good_friday],
        ['name' => 'Easter Sunday', 'date' => $easter],
        ['name' => 'Easter Monday', 'date' => $easter_monday]
    ];
    
    foreach($easter_holidays as $h) {
        $sql = sprintf("INSERT IGNORE INTO prlkenyaholidays (holiday_name, holiday_date, holiday_type, year)
                       VALUES ('%s', '%s', 'FIXED', %d)",
                       $h['name'], $h['date'], $year);
        DB_query($sql, $db);
    }
    
    return true;
}

function GetNextYearHolidays($current_year = null) {
    if($current_year === null) {
        $current_year = date('Y');
    }
    
    $result = DB_query("SELECT MAX(year) as max_year FROM prlkenyaholidays", $db);
    $row = DB_fetch_array($result);
    $max_year = $row['max_year'] ?: $current_year;
    
    $years_to_add = [];
    for($y = $max_year + 1; $y <= $current_year + 2; $y++) {
        $years_to_add[] = $y;
    }
    
    foreach($years_to_add as $year) {
        PopulateKenyaHolidays($year);
    }
    
    return $years_to_add;
}

if(php_sapi_name() === 'cli' || isset($_GET['cron'])) {
    $added = GetNextYearHolidays();
    echo "Holidays populated for years: " . implode(', ', $added);
}
?>
