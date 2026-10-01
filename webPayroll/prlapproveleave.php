<?php
$Title = _('Leave Authorization');
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');
include('ExtFunc/Payrollfunctions.php');
include('ExtFunc/gensalary.inc');
include('ExtFunc/salary.inc');
include('ExtFunc/PDFleaveform.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

$mypage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if(isset($_GET['printid'])){
    if($_GET['id'] == 2){
        $id = $_GET['printid'];
        $myclass = new PrintLeave($id);
    } else {
        include('includes/header.inc');
        echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';
        echo '<div class="sp-page"><div class="sp-content">';
        prnMsg(_('You can only print approved applications'));
        echo '<br><a href="' . $mypage . '" class="sp-btn sp-btn-secondary"><i class="fas fa-arrow-left"></i> ' . _('Back') . '</a>';
        echo '</div></div>';
        include('includes/footer.inc');
        exit;
    }
} else {
    include('includes/header.inc');
    echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

    if(isset($_POST['status'])){
        if($_POST['status'] == 2){
            SelectApprover($_POST['refno']);
        } else {
            $sql = sprintf("UPDATE prlstaffleaveplanner SET status='%s' WHERE refno='%s' AND status!=3", 
                $_POST['status'], $_POST['refno']);
            DB_query($sql, $db);
            prnMsg(_('Leave status updated successfully'), 'success');
        }
    }

    if(isset($_POST['approve'])){
        $sql = sprintf("UPDATE prlstaffleaveplanner SET status='%s' WHERE refno='%s'", 
            $_POST['status'], $_POST['refno']);
        DB_query($sql, $db);
        prnMsg(_('Leave application processed successfully'), 'success');
    }
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Review and approve employee leave applications'); ?></p>
        </div>
        <div>
            <span class="sp-badge sp-badge-primary" style="font-size: 14px;">
                <?php
                $countSql = "SELECT COUNT(*) as cnt FROM prlstaffleaveplanner WHERE status IN (0, 1)";
                $countResult = DB_query($countSql, $db);
                $countRow = DB_fetch_array($countResult);
                echo $countRow['cnt'] . ' ' . _('Pending');
                ?>
            </span>
        </div>
    </div>

    <div class="sp-content">
        <?php if(isset($_GET['pfid']) and isset($_GET['docid'])): ?>
            <?php
            $sql = "EXEC dbo.approveselectedleave";
            $ResultIndex = DB_query($sql, $db);
            $row = DB_fetch_array($ResultIndex);
            
            if($row):
            ?>
            <div class="sp-card">
                <div class="sp-card-header">
                    <i class="fas fa-clipboard-list"></i>
                    <h3><?php echo _('Leave Application Details'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <form method="post" action="<?php echo $mypage; ?>">
                        <input type="hidden" name="pfid" value="<?php echo $_GET['pfid']; ?>" />
                        <input type="hidden" name="refno" value="<?php echo $_GET['docid']; ?>" />
                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                        
                        <div class="sp-row">
                            <div class="sp-col-6">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Application Reference'); ?></label>
                                    <input type="text" class="sp-input" value="<?php echo $row['refno']; ?>" readonly />
                                </div>
                            </div>
                            <div class="sp-col-6">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Requested By'); ?></label>
                                    <input type="text" class="sp-input" value="<?php echo $row['appliedby']; ?>" readonly />
                                </div>
                            </div>
                        </div>
                        
                        <div class="sp-row">
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('From Date'); ?></label>
                                    <input type="text" class="sp-input" value="<?php echo ConvertSQLDate($row['leavedue']); ?>" readonly />
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('To Date'); ?></label>
                                    <input type="text" class="sp-input" value="<?php echo ConvertSQLDate($row['leavend']); ?>" readonly />
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Number of Days'); ?></label>
                                    <input type="text" class="sp-input" value="<?php echo $row['days']; ?>" readonly />
                                </div>
                            </div>
                        </div>
                        
                        <div class="sp-row">
                            <div class="sp-col-6">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Substitute'); ?></label>
                                    <input type="text" class="sp-input" value="<?php echo $row['names']; ?>" readonly />
                                </div>
                            </div>
                            <div class="sp-col-6">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Approval Decision'); ?></label>
                                    <select name="status" class="sp-select" required>
                                        <?php foreach($approvalstatus as $key => $value): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                        $daysleft = GetleaveDaysBalance($_GET['pfid']);
                        ?>
                        
                        <div class="sp-d-flex sp-gap-2">
                            <button type="submit" name="approve" class="sp-btn sp-btn-success">
                                <i class="fas fa-check"></i> <?php echo _('Process Decision'); ?>
                            </button>
                            <a href="<?php echo $mypage; ?>" class="sp-btn sp-btn-secondary">
                                <i class="fas fa-arrow-left"></i> <?php echo _('Back to List'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        
        <?php else: ?>
        
            <div class="sp-card">
                <div class="sp-card-header">
                    <i class="fas fa-list"></i>
                    <h3><?php echo _('Leave Applications'); ?></h3>
                </div>
                <div class="sp-card-body sp-p-0">
                    <table class="sp-table">
                        <thead>
                            <tr>
                                <th><?php echo _('Ref No'); ?></th>
                                <th><?php echo _('Applicant'); ?></th>
                                <th><?php echo _('Start Date'); ?></th>
                                <th><?php echo _('End Date'); ?></th>
                                <th><?php echo _('Days'); ?></th>
                                <th><?php echo _('Substitute'); ?></th>
                                <th><?php echo _('Status'); ?></th>
                                <th><?php echo _('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            approveleaveappliction();
                            $sql = "EXEC dbo.approveleaveappliction";
                            $ResultIndex = DB_query($sql, $db);
                            
                            if(DB_num_rows($ResultIndex) == 0):
                                echo '<tr><td colspan="8" class="sp-text-center sp-text-muted">';
                                echo '<i class="fas fa-clipboard-list" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                                echo _('No leave applications pending'); 
                                echo '</td></tr>';
                            endif;
                            
                            while($row = DB_fetch_array($ResultIndex)):
                                $statusClass = '';
                                $statusIcon = '';
                                switch($row['status']) {
                                    case 0: $statusClass = 'sp-badge-warning'; $statusIcon = 'clock'; break;
                                    case 1: $statusClass = 'sp-badge-info'; $statusIcon = 'hourglass-half'; break;
                                    case 2: $statusClass = 'sp-badge-success'; $statusIcon = 'check'; break;
                                    case 3: $statusClass = 'sp-badge-danger'; $statusIcon = 'times'; break;
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo $row['refno']; ?></strong></td>
                                <td><?php echo $row['appliedby']; ?></td>
                                <td><?php echo ConvertSQLDate($row['leavedue']); ?></td>
                                <td><?php echo ConvertSQLDate($row['leavend']); ?></td>
                                <td class="sp-text-center"><?php echo $row['days']; ?></td>
                                <td><?php echo $row['names']; ?></td>
                                <td>
                                    <span class="sp-badge <?php echo $statusClass; ?>">
                                        <i class="fas fa-<?php echo $statusIcon; ?>"></i> <?php echo $approvalstatus[$row['status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['status'] < 2): ?>
                                        <a href="<?php echo $mypage; ?>?pfid=<?php echo trim($row['pfno']); ?>&docid=<?php echo trim($row['refno']); ?>" 
                                           class="sp-btn sp-btn-primary sp-btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if($row['status'] == 2): ?>
                                        <a href="<?php echo $mypage; ?>?printid=<?php echo trim($row['refno']); ?>&id=2" 
                                           class="sp-btn sp-btn-success sp-btn-sm" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        
        <?php endif; ?>
    </div>
</div>

<?php include('includes/footer.inc'); }
endif;

function GetleaveDaysBalance($pfno){
    global $db;
    
    $SQL = "SELECT refno, pfno, year, days FROM prlstaffleavemaster WHERE pfno='" . $pfno . "'";
    $ResultIndex = DB_query($SQL, $db);
    $rows = DB_fetch_row($ResultIndex);
    $Days[0] = $rows[3];
    
    $SQL = "SELECT ISNULL(SUM(days), 0) FROM prlstaffleaveplanner WHERE pfno='" . $pfno . "' AND status = 1";
    $ResultIndex = DB_query($SQL, $db);
    $rows = DB_fetch_row($ResultIndex);
    $Days[1] = $rows[0];
    
    $SQL = "SELECT ISNULL(SUM(days), 0) FROM prlstaffleaveplanner WHERE pfno='" . $pfno . "' AND (status = 2 OR status = 3)";
    $ResultIndex = DB_query($SQL, $db);
    $rows = DB_fetch_row($ResultIndex);
    $Days[2] = $rows[0];
    
    return ($Days[0] - ($Days[1] + $Days[2]));
}
?>
