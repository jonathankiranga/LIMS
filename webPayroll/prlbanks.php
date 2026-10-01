<?php
$ForceConfigReload=true;
include('includes/session.inc');
$Title = _('Maintain Banks');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

if(isset($_POST['submit'])){
    $SQL = sprintf("SELECT count(*) as items FROM employeebanks WHERE code='%s'", $_POST['Code']);
    $ResultIndex = DB_query($SQL, $db);
    $row = DB_fetch_row($ResultIndex);        
    if($row[0] > 0){
        prnMsg(_('Bank with this code already exists'), 'warn');
        unset($_POST['submit']);
    }
}

if(isset($_POST['submit'])){
    DB_Txn_Begin($db);
    $SQL = sprintf("INSERT INTO employeebanks (bankname, code) VALUES ('%s', '%s')", 
        $_POST['bankname'], $_POST['Code']);
    $results = DB_query($SQL, $db);

    if(DB_error_no($db) > 0){
        DB_Txn_Rollback($db);
        prnMsg(DB_error_msg($db), 'error');
    } else {
        DB_Txn_Commit($db);
        prnMsg(_('Bank saved successfully'), 'success');
    }
}

if(isset($_POST['EditPosition'])){
    DB_Txn_Begin($db);
    $SQL = sprintf("UPDATE employeebanks SET bankname='%s' WHERE code='%s'", 
        $_POST['bankname'], $_POST['EditPosition']);
    $results = DB_query($SQL, $db);

    if(DB_error_no($db) > 0){
        DB_Txn_Rollback($db);
        prnMsg(DB_error_msg($db), 'error');
    } else {
        DB_Txn_Commit($db);
        prnMsg(_('Bank updated successfully'), 'success');
    }
}

$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$banks = GetBanks();
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-university"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Manage banking institutions for payroll processing'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-row">
            <div class="sp-col-4">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-plus-circle"></i>
                        <h3><?php echo isset($_GET['EditP']) ? _('Edit Bank') : _('Add New Bank'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" action="<?php echo $self; ?>">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            
                            <?php if(isset($_GET['EditP'])): ?>
                                <input type="hidden" name="EditPosition" value="<?php echo $_GET['EditP']; ?>" />
                            <?php endif; ?>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Bank Code'); ?></label>
                                <input type="text" name="Code" class="sp-input" 
                                    value="<?php echo isset($_GET['EditP']) ? $_GET['EditP'] : ''; ?>"
                                    <?php echo isset($_GET['EditP']) ? 'readonly' : 'required'; ?>
                                    placeholder="<?php echo _('e.g., KCB'); ?>" />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Bank Name'); ?></label>
                                <input type="text" name="bankname" class="sp-input" required 
                                    placeholder="<?php echo _('e.g., Kenya Commercial Bank'); ?>" />
                            </div>

                            <div class="sp-d-flex sp-gap-2">
                                <button type="submit" name="submit" class="sp-btn sp-btn-primary">
                                    <i class="fas fa-save"></i> <?php echo _('Save'); ?>
                                </button>
                                <?php if(isset($_GET['EditP'])): ?>
                                    <a href="<?php echo $self; ?>" class="sp-btn sp-btn-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="sp-col-8">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-list"></i>
                        <h3><?php echo _('List of Banks'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <table class="sp-table">
                            <thead>
                                <tr>
                                    <th><?php echo _('Code'); ?></th>
                                    <th><?php echo _('Bank Name'); ?></th>
                                    <th><?php echo _('Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(count($banks) == 0):
                                    echo '<tr><td colspan="3" class="sp-text-center sp-text-muted">';
                                    echo '<i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                                    echo _('No banks found'); 
                                    echo '</td></tr>';
                                endif;
                                
                                foreach($banks as $rows):
                                ?>
                                <tr>
                                    <td><strong><?php echo $rows['code']; ?></strong></td>
                                    <td><?php echo $rows['bankname']; ?></td>
                                    <td>
                                        <a href="<?php echo $self; ?>?EditP=<?php echo $rows['code']; ?>" 
                                           class="sp-btn sp-btn-primary sp-btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="prlbankbranches.php?bank=<?php echo $rows['code']; ?>" 
                                           class="sp-btn sp-btn-success sp-btn-sm">
                                            <i class="fas fa-code-branch"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
