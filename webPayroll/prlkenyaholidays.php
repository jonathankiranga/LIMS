<?php
include('includes/session.inc');
$Title = _('Kenya Gazetted Holidays');
include('includes/header.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

if(!isset($_POST['year'])) {
    $_POST['year'] = date('Y');
}

if(isset($_POST['add_holiday'])) {
    $errors = 0;
    
    if(empty($_POST['holiday_name']) || empty($_POST['holiday_date'])) {
        prnMsg(_('Holiday name and date are required'), 'error');
        $errors++;
    }
    
    if($errors == 0) {
        $sql = sprintf("INSERT INTO prlkenyaholidays (holiday_name, holiday_date, holiday_type, year, substitute_date, region)
            VALUES ('%s', '%s', '%s', %d, %s, '%s')
            ON DUPLICATE KEY UPDATE 
                holiday_name = VALUES(holiday_name),
                holiday_type = VALUES(holiday_type),
                substitute_date = VALUES(substitute_date),
                region = VALUES(region)",
            $_POST['holiday_name'],
            $_POST['holiday_date'],
            $_POST['holiday_type'],
            (int)$_POST['year'],
            !empty($_POST['substitute_date']) ? "'" . $_POST['substitute_date'] . "'" : 'NULL',
            $_POST['region']
        );
        DB_query($sql, $db);
        prnMsg(_('Holiday added successfully'), 'success');
        unset($_POST);
    }
}

if(isset($_POST['bulk_add'])) {
    $year = (int)$_POST['bulk_year'];
    
    $fixed_holidays = [
        ['name' => "New Year's Day", 'date' => $year . '-01-01'],
        ['name' => "Labour Day", 'date' => $year . '-05-01'],
        ['name' => "Madaraka Day", 'date' => $year . '-06-01'],
        ['name' => "Mashujaa Day", 'date' => $year . '-10-20'],
        ['name' => "Jamhuri Day", 'date' => $year . '-12-12'],
        ['name' => "Christmas Day", 'date' => $year . '-12-25'],
        ['name' => "Boxing Day", 'date' => $year . '-12-26']
    ];
    
    foreach($fixed_holidays as $h) {
        $sql = sprintf("INSERT INTO prlkenyaholidays (holiday_name, holiday_date, holiday_type, year)
            VALUES ('%s', '%s', 'FIXED', %d)
            ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name)",
            $h['name'], $h['date'], $year
        );
        DB_query($sql, $db);
    }
    prnMsg(_('Fixed holidays added for ') . $year, 'success');
}

if(isset($_GET['delete'])) {
    DB_query("DELETE FROM prlkenyaholidays WHERE holiday_id = " . (int)$_GET['delete'], $db);
    prnMsg(_('Holiday deleted successfully'), 'success');
}

if(isset($_POST['generate_easter'])) {
    $year = (int)$_POST['easter_year'];
    $easter = date('Y-m-d', easter_date($year));
    $good_friday = date('Y-m-d', strtotime($easter . ' -2 days'));
    $easter_monday = date('Y-m-d', strtotime($easter . ' +1 day'));
    
    $holidays = [
        ['name' => 'Good Friday', 'date' => $good_friday],
        ['name' => 'Easter Monday', 'date' => $easter_monday]
    ];
    
    foreach($holidays as $h) {
        $sql = sprintf("INSERT INTO prlkenyaholidays (holiday_name, holiday_date, holiday_type, year)
            VALUES ('%s', '%s', 'FIXED', %d)
            ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name)",
            $h['name'], $h['date'], $year
        );
        DB_query($sql, $db);
    }
    prnMsg(_('Easter holidays generated for ') . $year, 'success');
}

$year = (int)$_POST['year'];
$sql = "SELECT * FROM prlkenyaholidays WHERE year = $year ORDER BY holiday_date";
$result = DB_query($sql, $db);

function calculateWorkingDays($start, $end, $db) {
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    $working_days = 0;
    
    $holidays_result = DB_query("SELECT holiday_date FROM prlkenyaholidays WHERE year = " . date('Y', $start_ts), $db);
    $holidays = [];
    while($row = DB_fetch_array($holidays_result)) {
        $holidays[] = $row['holiday_date'];
    }
    
    while($start_ts <= $end_ts) {
        $day_of_week = date('w', $start_ts);
        $current_date = date('Y-m-d', $start_ts);
        
        if($day_of_week != 0 && $day_of_week != 6 && !in_array($current_date, $holidays)) {
            $working_days++;
        }
        $start_ts = strtotime('+1 day', $start_ts);
    }
    return $working_days;
}

$working_days = calculateWorkingDays($year . '-01-01', $year . '-12-31', $db);
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-flag"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Configure Kenya gazetted public holidays for payroll calculations'); ?></p>
        </div>
        <div>
            <span class="sp-badge sp-badge-info" style="font-size:16px; padding: 10px 20px;">
                <i class="fas fa-calendar-check"></i> <?php echo sprintf(_('%d Working Days in %d'), $working_days, $year); ?>
            </span>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-alert sp-alert-info">
            <i class="fas fa-info-circle fa-lg"></i>
            <div>
                <strong><?php echo _('Kenyan Statutory Holidays'); ?>:</strong>
                <?php echo _('New Year, Labour Day (May 1), Madaraka Day (Jun 1), Mashujaa Day (Oct 20), Jamhuri Day (Dec 12), Christmas Day, Boxing Day + Easter holidays'); ?>
            </div>
        </div>

        <div class="sp-row">
            <div class="sp-col-3">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-plus-circle"></i>
                        <h3><?php echo _('Add Holiday'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            <input type="hidden" name="year" value="<?php echo $year; ?>" />
                            
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Holiday Name'); ?></label>
                                <input type="text" name="holiday_name" class="sp-input" placeholder="<?php echo _('e.g. Mashujaa Day'); ?>" required />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Date'); ?></label>
                                <input type="date" name="holiday_date" class="sp-input" required />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Type'); ?></label>
                                <select name="holiday_type" class="sp-select">
                                    <option value="FIXED"><?php echo _('Fixed'); ?></option>
                                    <option value="SUBSTITUTE"><?php echo _('Substitute'); ?></option>
                                    <option value="FLOATING"><?php echo _('Floating'); ?></option>
                                </select>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Region'); ?></label>
                                <input type="text" name="region" class="sp-input" value="NATIONAL" />
                            </div>

                            <button type="submit" name="add_holiday" class="sp-btn sp-btn-primary sp-btn-block">
                                <i class="fas fa-plus"></i> <?php echo _('Add Holiday'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
                    <div class="sp-card-header">
                        <i class="fas fa-magic"></i>
                        <h3><?php echo _('Quick Actions'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Year'); ?></label>
                                <select name="bulk_year" class="sp-select">
                                    <?php for($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" name="bulk_add" class="sp-btn sp-btn-success sp-btn-block">
                                <i class="fas fa-calendar-plus"></i> <?php echo _('Add Fixed Holidays'); ?>
                            </button>
                        </form>

                        <hr style="margin: 15px 0; border-color: var(--sp-border);" />

                        <form method="post">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Easter Year'); ?></label>
                                <select name="easter_year" class="sp-select">
                                    <?php for($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" name="generate_easter" class="sp-btn sp-btn-warning sp-btn-block">
                                <i class="fas fa-egg"></i> <?php echo _('Generate Easter'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="sp-col-9">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-calendar"></i>
                        <h3><?php echo sprintf(_('Public Holidays for %d'), $year); ?></h3>
                        <div style="margin-left: auto;">
                            <form method="post" style="display:inline-flex; gap:8px;">
                                <select name="year" class="sp-select sp-select-sm" onchange="this.form.submit();">
                                    <?php for($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo ($y == $year) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <table class="sp-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th><?php echo _('Holiday Name'); ?></th>
                                    <th><?php echo _('Date'); ?></th>
                                    <th><?php echo _('Day'); ?></th>
                                    <th><?php echo _('Type'); ?></th>
                                    <th style="width:100px;"><?php echo _('Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                $type_colors = ['FIXED' => 'sp-badge-success', 'SUBSTITUTE' => 'sp-badge-warning', 'FLOATING' => 'sp-badge-info'];
                                $count = 1;
                                
                                if(DB_num_rows($result) == 0):
                                ?>
                                <tr>
                                    <td colspan="6" class="sp-text-center sp-text-muted">
                                        <i class="fas fa-calendar-times" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                                        <?php echo _('No holidays found for this year'); ?>
                                    </td>
                                </tr>
                                <?php
                                else:
                                    while($row = DB_fetch_array($result)):
                                        $day_of_week = date('w', strtotime($row['holiday_date']));
                                        $is_past = strtotime($row['holiday_date']) < strtotime('today');
                                ?>
                                <tr <?php echo $is_past ? 'style="opacity: 0.6;"' : ''; ?>>
                                    <td><?php echo $count++; ?></td>
                                    <td>
                                        <strong><?php echo $row['holiday_name']; ?></strong>
                                        <?php if($row['region'] != 'NATIONAL'): ?>
                                            <span class="sp-badge sp-badge-secondary" style="font-size: 10px;"><?php echo $row['region']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo ConvertSQLDate($row['holiday_date']); ?></td>
                                    <td><?php echo _($day_names[$day_of_week]); ?></td>
                                    <td><span class="sp-badge <?php echo $type_colors[$row['holiday_type']]; ?>"><?php echo $row['holiday_type']; ?></span></td>
                                    <td>
                                        <a href="<?php echo $RootPath; ?>/prlkenyaholidays.php?delete=<?php echo $row['holiday_id']; ?>&year=<?php echo $year; ?>" 
                                           class="sp-btn sp-btn-danger sp-btn-sm" 
                                           onclick="return confirm('<?php echo _('Delete this holiday?'); ?>');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
                    <div class="sp-card-header">
                        <i class="fas fa-calculator"></i>
                        <h3><?php echo _('Working Days Summary'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <div class="sp-row">
                            <div class="sp-col-4">
                                <div class="sp-text-center">
                                    <h2 style="color: var(--sp-primary); margin: 0;"><?php echo $working_days; ?></h2>
                                    <p class="sp-text-muted"><?php echo _('Working Days'); ?></p>
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-text-center">
                                    <h2 style="color: var(--sp-success); margin: 0;"><?php echo 52 * 5; ?></h2>
                                    <p class="sp-text-muted"><?php echo _('Weekend Days'); ?></p>
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-text-center">
                                    <h2 style="color: var(--sp-danger); margin: 0;">
                                        <?php 
                                        $holiday_count = DB_num_rows($result);
                                        echo $holiday_count;
                                        ?>
                                    </h2>
                                    <p class="sp-text-muted"><?php echo _('Public Holidays'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
