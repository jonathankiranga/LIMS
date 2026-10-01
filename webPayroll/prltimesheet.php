<?php
include('includes/session.inc');
$Title = _('PayRoll Time Management');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');
include('ExtFunc/timesheet.inc');
include('ExtFunc/attendance.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

$Timer = new timmer();
$mypage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if(isset($_POST['filter'])){ 
    if(mb_strlen($_POST['filter']) > 0){
        $Timer->date = FormatDateForSQL($_POST['filter']);
        $Timer->ERPdate = FormatDateForSQL($_POST['filter']);
    }
}

if(isset($_POST['import'])){
    $FileName = $_FILES['timeclock']['name'];
    $TempName = $_FILES['timeclock']['tmp_name'];
    $FileSize = $_FILES['timeclock']['size'];
    $FileHandle = fopen($TempName, 'r');
    $headRow = fgetcsv($FileHandle, 10000, ",");
    
    if(count($headRow) != 4){
        prnMsg(_('File contains ') . count($headRow) . ' columns, expected 4. Try downloading a new template.', 'error');
        fclose($FileHandle);
    } else {
        fclose($FileHandle);
        $FileHandle = fopen($TempName, 'r');
        DB_Txn_Begin($Timer->db);
        
        while(($myrow = fgetcsv($FileHandle, 10000, ",")) !== FALSE){
            $Timer->autoclock($myrow[0], $myrow[1], $myrow[2], $myrow[3]);
        }

        if(DB_error_no($Timer->db) > 0){
            prnMsg(DB_error_msg($Timer->db), 'error');
            DB_Txn_Rollback($Timer->db);
        } else {
            DB_Txn_Commit($Timer->db);
            prnMsg(_('Batch Import of ') . $FileName . ' ' . _('has been completed.'), 'success');
        }
    }
}

if(isset($_POST['attendance'])){
    $attclass = new attendance();
    $attclass->connect($_POST['address'], $_POST['port']);
    unset($_POST['attendance']);
}

if(isset($_POST['IN'])){
    $Timer->clockin();
    unset($_POST['IN']);
} elseif(isset($_POST['OUT'])){
    $Timer->clockout();
    unset($_POST['OUT']);
}

if(isset($_GET['pftno'])){
    if(isset($_GET['date'])){
        $Timer->date = $_GET['date'];
        $Timer->ERPdate = $_GET['date'];
    }

    $ResultIndex = $Timer->GEToneEMPLOYEE($_GET['pftno']);
    $row = DB_fetch_array($ResultIndex);
    
    $isClockIn = $_GET['type'] == 'IN';
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon" style="background: <?php echo $isClockIn ? 'var(--sp-success)' : 'var(--sp-danger)'; ?>;">
            <i class="fas fa-<?php echo $isClockIn ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $isClockIn ? _('Clock In') : _('Clock Out'); ?></h1>
            <p><?php echo $row['status'] . ' ' . $row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']; ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-card" style="max-width: 500px; margin: 0 auto;">
            <div class="sp-card-header" style="background: <?php echo $isClockIn ? 'var(--sp-success)' : 'var(--sp-danger)'; ?>; color: white;">
                <i class="fas fa-user-clock"></i>
                <h3><?php echo $isClockIn ? _('Clock In') : _('Clock Out'); ?></h3>
            </div>
            <div class="sp-card-body">
                <form method="post" action="<?php echo $mypage; ?>">
                    <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                    <input type="hidden" name="pfno" value="<?php echo $_GET['pftno']; ?>" />
                    <input type="hidden" name="recommended" value="<?php echo $row['noofhrsperday']; ?>" />
                    
                    <div class="sp-form-group">
                        <label class="sp-label"><?php echo _('Date'); ?></label>
                        <input type="text" class="sp-input" value="<?php echo ConvertSQLDate($Timer->date); ?>" readonly />
                    </div>
                    
                    <div class="sp-row">
                        <div class="sp-col-6">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Hours (24hr)'); ?></label>
                                <input type="number" name="hours" class="sp-input" min="0" max="23" value="<?php echo date('H'); ?>" />
                            </div>
                        </div>
                        <div class="sp-col-6">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Minutes'); ?></label>
                                <input type="number" name="minutes" class="sp-input" min="0" max="59" value="<?php echo date('i'); ?>" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="sp-d-flex sp-gap-2">
                        <button type="submit" name="<?php echo $_GET['type']; ?>" class="sp-btn sp-btn-lg sp-btn-block" style="background: <?php echo $isClockIn ? 'var(--sp-success)' : 'var(--sp-danger)'; ?>; color: white;">
                            <i class="fas fa-<?php echo $isClockIn ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i> 
                            <?php echo _('Confirm ') . ($isClockIn ? _('Clock In') : _('Clock Out')); ?>
                        </button>
                    </div>
                </form>
                
                <div style="margin-top: 20px; text-align: center;">
                    <a href="<?php echo $mypage; ?>" class="sp-btn sp-btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?php echo _('Back to Time Sheet'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php } else { ?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Time and Attendance Management for ') . ConvertSQLDate($Timer->getmydate($Timer->date)); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-row">
            <div class="sp-col-8">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-user-clock"></i>
                        <h3><?php echo _('Employee Clock Times'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <form method="post" action="<?php echo $mypage; ?>" class="sp-p-3" style="background: var(--sp-light);">
                            <div class="sp-d-flex sp-align-center sp-gap-2">
                                <label class="sp-label" style="margin: 0;"><?php echo _('Change Date:'); ?></label>
                                <input type="text" name="filter" class="sp-input date" alt="<?php echo $_SESSION['DefaultDateFormat']; ?>" 
                                       value="<?php echo $_POST['filter'] ?? date('Y-m-d'); ?>" style="width: 150px;" />
                                <button type="submit" class="sp-btn sp-btn-secondary">
                                    <i class="fas fa-sync-alt"></i> <?php echo _('Refresh'); ?>
                                </button>
                            </div>
                        </form>
                        
                        <table class="sp-table">
                            <thead>
                                <tr>
                                    <th><?php echo _('PF No'); ?></th>
                                    <th><?php echo _('Names'); ?></th>
                                    <th><?php echo _('Clock In'); ?></th>
                                    <th><?php echo _('Clock Out'); ?></th>
                                    <th><?php echo _('Hours'); ?></th>
                                    <th><?php echo _('Expected'); ?></th>
                                    <th><?php echo _('Status'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ResultIndex = $Timer->GETALLEMPLOYEES();
                                $k = 0;
                                
                                if(DB_num_rows($ResultIndex) == 0):
                                    echo '<tr><td colspan="7" class="sp-text-center sp-text-muted">';
                                    echo '<i class="fas fa-users" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                                    echo _('No records found'); 
                                    echo '</td></tr>';
                                endif;
                                
                                while($row = DB_fetch_array($ResultIndex)):
                                    if(is_null($row['date']) == false):
                                        $pfno = rtrim($row['pf_no']);
                                        $hasClockIn = $row['timein'] != null;
                                        $hasClockOut = $row['timeout'] != null;
                                        $statusClass = $hasClockOut ? 'sp-badge-success' : 'sp-badge-warning';
                                        $statusText = $hasClockOut ? _('Complete') : _('Incomplete');
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo $mypage; ?>?pftno=<?php echo $pfno; ?>&type=<?php echo $hasClockIn ? 'OUT' : 'IN'; ?>&date=<?php echo $row['date']; ?>" 
                                           class="sp-btn sp-btn-primary sp-btn-sm">
                                            <i class="fas fa-<?php echo $hasClockIn ? 'sign-out-alt' : 'sign-in-alt'; ?>"></i>
                                        </a>
                                        <strong><?php echo $row['pf_no']; ?></strong>
                                    </td>
                                    <td>
                                        <i class="fas fa-user" style="color: var(--sp-primary); margin-right: 8px;"></i>
                                        <?php echo $row['status'] . ' ' . $row['fname'] . ' ' . $row['lname']; ?>
                                    </td>
                                    <td><?php echo $Timer->getmytime($row['timein']); ?></td>
                                    <td><?php echo $Timer->getmytime($row['timeout']); ?></td>
                                    <td class="sp-text-center"><?php echo $row['noofhours']; ?></td>
                                    <td class="sp-text-center"><?php echo $row['noofhrsperday']; ?></td>
                                    <td>
                                        <span class="sp-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                </tr>
                                <?php 
                                    endif;
                                    $k = 1 - $k;
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="sp-col-4">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-upload"></i>
                        <h3><?php echo _('Import Data'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" action="<?php echo $mypage; ?>" enctype="multipart/form-data">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            
                            <div class="sp-alert sp-alert-info">
                                <i class="fas fa-file-csv"></i>
                                <div>
                                    <strong><?php echo _('CSV Format:'); ?></strong><br>
                                    PFNO, Date, TIME-IN, TIME-OUT
                                </div>
                            </div>
                            
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Select CSV File'); ?></label>
                                <input type="file" name="timeclock" class="sp-input" accept=".csv" required />
                            </div>
                            
                            <button type="submit" name="import" class="sp-btn sp-btn-success sp-btn-block">
                                <i class="fas fa-upload"></i> <?php echo _('Import Time Register'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
                    <div class="sp-card-header">
                        <i class="fas fa-plug"></i>
                        <h3><?php echo _('Attendance Device'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Device IP Address'); ?></label>
                                <input type="text" name="address" class="sp-input" placeholder="192.168.1.100" />
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Port'); ?></label>
                                <input type="number" name="port" class="sp-input" value="4370" />
                            </div>
                            <button type="submit" name="attendance" class="sp-btn sp-btn-primary sp-btn-block">
                                <i class="fas fa-sync"></i> <?php echo _('Connect & Sync'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php } ?>

<?php include('includes/footer.inc'); ?>
