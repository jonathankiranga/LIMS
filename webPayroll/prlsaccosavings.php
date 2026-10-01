<?php
$ForceConfigReload = true;
include('includes/session.inc');
$Title = _('PayRoll Sacco and Savings');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');
include('ExtFunc/salary.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if(isset($_POST['pf_no'])){
    $staffno = $_POST['pf_no'];
} elseif(isset($_GET['pf_no'])){
    $staffno = $_GET['pf_no'];
}

if(isset($staffno)){
    $title2 = ' ' . _('For') . ' ' . showname($staffno);
}

if(isset($_POST['mergecredit'])){
    $ChildIndex = $_POST['childaccount'];
    $sql = "SELECT deductcode FROM prlstaffloans WHERE loanindex='" . $_POST['MergeId'] . "'";
    $ResultIndex = DB_query($sql, $db);
    $row = DB_fetch_row($ResultIndex);
    $p1 = $row[0];
    
    $sql = "SELECT deductcode FROM prlstaffloans WHERE loanindex='" . $ChildIndex . "'";
    $ResultIndex = DB_query($sql, $db);
    $row = DB_fetch_row($ResultIndex);
    $p2 = $row[0];
    
    if($p1 != $p2){
        prnMsg(_('You cannot merge different account types'), 'warn');
    } elseif($_POST['MergeId'] == $_POST['childaccount']){
        prnMsg(_('You cannot merge this account to itself'), 'warn');
    } else {
        $sql = array();
        $sql[] = sprintf("UPDATE prlloantrans SET loanindex='%s' WHERE loanindex='%s'", $_POST['MergeId'], $ChildIndex);
        $sql[] = sprintf("DELETE FROM prlstaffloans WHERE loanindex='%s'", $ChildIndex);
        
        DB_Txn_Begin($db);
        foreach($sql as $value){
            DB_query($value, $db);
        }
        
        if(DB_error_no($db) > 0){
            DB_Txn_Rollback($db);
        } else {
            DB_Txn_Commit($db);
            prnMsg(_('Items merged successfully'), 'success');
        }
    }
}

if(isset($_POST['createcredit'])){
    $sql = sprintf("INSERT INTO prlstaffloans (pfno, deductcode, principal, instalment, interest, startdate, saving)
        VALUES ('%s', '%s', '%f', '%f', %f, '%s', '1')",
        $_POST['pf_no'], $_POST['deductcode'], $_POST['principal'], $_POST['instalment'],
        $_POST['interest'], FormatDateForSQL($_POST['startdate']));
    
    DB_query($sql, $db);
    prnMsg(_('Contribution added successfully'), 'success');
    unset($_POST['pf_no']);
    unset($_POST['createcredit']);
} elseif(isset($_POST['editcredit'])){
    $sql = sprintf("UPDATE prlstaffloans SET deductcode='%s', principal='%f', instalment='%f', interest=%f, startdate='%s' WHERE loanindex='%s'",
        $_POST['deductcode'], $_POST['principal'], $_POST['instalment'],
        $_POST['interest'], FormatDateForSQL($_POST['startdate']), $_POST['loanindex']);
    
    DB_query($sql, $db);
    prnMsg(_('Contribution updated successfully'), 'success');
    unset($_POST['pf_no']);
    unset($_POST['loanindex']);
    unset($_POST['editcredit']);
}

$viewMode = isset($_GET['loanindex']) ? 'edit' : (isset($staffno) ? 'employee' : 'list');
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-piggy-bank"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?><?php echo isset($title2) ? $title2 : ''; ?></h1>
            <p><?php echo _('Manage employee savings and SACCO contributions'); ?></p>
        </div>
        <div>
            <?php if($viewMode != 'list'): ?>
                <a href="<?php echo $self; ?>" class="sp-btn sp-btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo _('Back to List'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="sp-content">
        <?php if($viewMode == 'list'): ?>
            <div class="sp-card">
                <div class="sp-card-header">
                    <i class="fas fa-users"></i>
                    <h3><?php echo _('Select Employee'); ?></h3>
                </div>
                <div class="sp-card-body sp-p-0">
                    <table class="sp-table">
                        <thead>
                            <tr>
                                <th><?php echo _('PayRoll ID'); ?></th>
                                <th><?php echo _('Names'); ?></th>
                                <th><?php echo _('Contact'); ?></th>
                                <th><?php echo _('Employment'); ?></th>
                                <th class="sp-text-right"><?php echo _('Total Savings Balance'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $ResultIndex = GETALLEMPLOYEES();
                            while($row = DB_fetch_array($ResultIndex)):
                                if($row['Active'] == 0):
                                    $balance = getsavingsbalance($row['pf_no']);
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo $self; ?>?pf_no=<?php echo trim($row['pf_no']); ?>" class="sp-btn sp-btn-primary sp-btn-sm">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                    <strong><?php echo $row['pf_no']; ?></strong>
                                </td>
                                <td>
                                    <i class="fas fa-user" style="color: var(--sp-primary); margin-right: 8px;"></i>
                                    <?php echo $row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']; ?>
                                </td>
                                <td>
                                    <?php if(!empty($row['telno'])): ?>
                                        <i class="fas fa-phone" style="color: var(--sp-success);"></i> <?php echo $row['telno']; ?><br>
                                    <?php endif; ?>
                                    <?php if(!empty($row['email'])): ?>
                                        <i class="fas fa-envelope" style="color: var(--sp-warning);"></i> <?php echo substr($row['email'], 0, 25); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $employment[$row['freqcode']]; ?></td>
                                <td class="sp-text-right">
                                    <strong style="color: var(--sp-success);">
                                        KES <?php echo number_format($balance, 2); ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php 
                                endif;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        
        <?php elseif($viewMode == 'employee'): ?>
            <div class="sp-card">
                <div class="sp-card-header">
                    <i class="fas fa-list"></i>
                    <h3><?php echo _('Employee Savings'); ?></h3>
                </div>
                <div class="sp-card-body sp-p-0">
                    <table class="sp-table">
                        <thead>
                            <tr>
                                <th><?php echo _('Item Name'); ?></th>
                                <th class="sp-text-right"><?php echo _('Balance Fwd'); ?></th>
                                <th class="sp-text-right"><?php echo _('Instalment'); ?></th>
                                <th><?php echo _('Interest'); ?></th>
                                <th><?php echo _('Start Date'); ?></th>
                                <th class="sp-text-right"><?php echo _('C/Fwd Balance'); ?></th>
                                <th><?php echo _('Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $ResultIndex = getsaccoset($staffno);
                            if(DB_num_rows($ResultIndex) == 0):
                                echo '<tr><td colspan="7" class="sp-text-center sp-text-muted">';
                                echo '<i class="fas fa-piggy-bank" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                                echo _('No savings found for this employee'); 
                                echo '</td></tr>';
                            endif;
                            
                            while($row = DB_fetch_array($ResultIndex)):
                            ?>
                            <tr>
                                <td><strong><?php echo $row['description']; ?></strong></td>
                                <td class="sp-text-right"><?php echo number_format($row['principal'], 2); ?></td>
                                <td class="sp-text-right"><?php echo number_format($row['instalment'], 2); ?></td>
                                <td><?php echo $row['interest']; ?>%</td>
                                <td><?php echo ConvertSQLDate($row['startdate']); ?></td>
                                <td class="sp-text-right">
                                    <span class="sp-badge sp-badge-success">
                                        <?php echo number_format($row['balance'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo $self; ?>?loanindex=<?php echo $row['loanindex']; ?>&pf_no=<?php echo trim($staffno); ?>" 
                                       class="sp-btn sp-btn-primary sp-btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
                <div class="sp-card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3><?php echo _('Add New Contribution'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <form method="post" action="<?php echo $self; ?>">
                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                        <input type="hidden" name="pf_no" value="<?php echo $staffno; ?>" />
                        
                        <div class="sp-row">
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Contribution Type'); ?></label>
                                    <select name="deductcode" class="sp-select" required>
                                        <?php getloanlist(); ?>
                                    </select>
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Balance Forward'); ?></label>
                                    <input type="number" name="principal" class="sp-input" step="0.01" required />
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Monthly Contribution'); ?></label>
                                    <input type="number" name="instalment" class="sp-input" step="0.01" required />
                                </div>
                            </div>
                        </div>
                        
                        <div class="sp-row">
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Interest Rate (%)'); ?></label>
                                    <input type="number" name="interest" class="sp-input" step="0.01" />
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Start Date'); ?></label>
                                    <input type="text" name="startdate" class="sp-input date" alt="<?php echo $_SESSION['DefaultDateFormat']; ?>" required />
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="createcredit" class="sp-btn sp-btn-success">
                            <i class="fas fa-plus"></i> <?php echo _('Add Contribution'); ?>
                        </button>
                    </form>
                </div>
            </div>
        
        <?php elseif($viewMode == 'edit'): ?>
            <?php
            $sql = "SELECT * FROM prlstaffloans WHERE loanindex='" . $_GET['loanindex'] . "'";
            $ResultIndex = DB_query($sql, $db);
            $row = DB_fetch_array($ResultIndex);
            ?>
            <div class="sp-card">
                <div class="sp-card-header">
                    <i class="fas fa-edit"></i>
                    <h3><?php echo _('Edit Contribution'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <form method="post" action="<?php echo $self; ?>">
                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                        <input type="hidden" name="pf_no" value="<?php echo $staffno; ?>" />
                        <input type="hidden" name="loanindex" value="<?php echo $_GET['loanindex']; ?>" />
                        
                        <div class="sp-row">
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Contribution Type'); ?></label>
                                    <select name="deductcode" class="sp-select" required>
                                        <?php filterproduct($row['deductcode']); ?>
                                    </select>
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Balance Forward'); ?></label>
                                    <input type="number" name="principal" class="sp-input" step="0.01" value="<?php echo $row['principal']; ?>" required />
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Monthly Contribution'); ?></label>
                                    <input type="number" name="instalment" class="sp-input" step="0.01" value="<?php echo $row['instalment']; ?>" required />
                                </div>
                            </div>
                        </div>
                        
                        <div class="sp-row">
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Interest Rate (%)'); ?></label>
                                    <input type="number" name="interest" class="sp-input" step="0.01" value="<?php echo $row['interest']; ?>" />
                                </div>
                            </div>
                            <div class="sp-col-4">
                                <div class="sp-form-group">
                                    <label class="sp-label"><?php echo _('Start Date'); ?></label>
                                    <input type="text" name="startdate" class="sp-input date" alt="<?php echo $_SESSION['DefaultDateFormat']; ?>" value="<?php echo ConvertSQLDate($row['startdate']); ?>" required />
                                </div>
                            </div>
                        </div>
                        
                        <div class="sp-d-flex sp-gap-2">
                            <button type="submit" name="editcredit" class="sp-btn sp-btn-primary">
                                <i class="fas fa-save"></i> <?php echo _('Update Contribution'); ?>
                            </button>
                            <a href="<?php echo $self; ?>?pf_no=<?php echo $staffno; ?>" class="sp-btn sp-btn-secondary">
                                <i class="fas fa-arrow-left"></i> <?php echo _('Back'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
