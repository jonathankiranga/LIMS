<?php
include('includes/session.inc');
$Title = _('Maintain Positions');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

if(isset($_POST['submit'])){
    DB_Txn_Begin($db);
    $SQL = sprintf("INSERT INTO prlpositions (code, name) VALUES ((SELECT COALESCE(MAX(code), 0) + 1 FROM prlpositions), '%s')", $_POST['jobposition']);
    $results = DB_query($SQL, $db);

    if(DB_error_no($db) > 0){
        DB_Txn_Rollback($db);
        prnMsg(DB_error_msg($db), 'error');
    } else {
        DB_Txn_Commit($db);
        prnMsg(_('Position created successfully'), 'success');
    }
}

if(isset($_POST['EditPosition'])){
    DB_Txn_Begin($db);
    $SQL = sprintf("UPDATE prlpositions SET name='%s' WHERE code='%s'", $_POST['jobposition'], $_POST['EditPosition']);
    $results = DB_query($SQL, $db);

    if(DB_error_no($db) > 0){
        DB_Txn_Rollback($db);
        prnMsg(DB_error_msg($db), 'error');
    } else {
        DB_Txn_Commit($db);
        prnMsg(_('Position updated successfully'), 'success');
    }
}

if(isset($_GET['RemoveP'])){
    $SQL = sprintf("SELECT P.* FROM prlpositions P 
            JOIN prlemployeemaster ON position = P.code 
            WHERE P.code='%s'", $_GET['RemoveP']);
    
    $results = DB_query($SQL, $db);
    if(DB_num_rows($results) > 0){
        prnMsg(_('You cannot delete this position because it is assigned to employees'), 'warn');
    } else {
        DB_Txn_Begin($db);
        $SQL = sprintf("DELETE FROM prlpositions WHERE code='%s'", $_GET['RemoveP']);
        $results = DB_query($SQL, $db);

        if(DB_error_no($db) > 0){
            DB_Txn_Rollback($db);
            prnMsg(DB_error_msg($db), 'error');
        } else {
            DB_Txn_Commit($db);
            prnMsg(_('Position deleted successfully'), 'success');
        }
    }
}

$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$editMode = isset($_GET['EditP']);
$editName = '';

if($editMode){
    $row = GetPosition($_GET['EditP']);
    $editName = $row['name'];
}

$positions = GetPositions();
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Manage job positions within the organization'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-row">
            <div class="sp-col-4">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-<?php echo $editMode ? 'edit' : 'plus-circle'; ?>"></i>
                        <h3><?php echo $editMode ? _('Edit Position') : _('Add New Position'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" action="<?php echo $self; ?>">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            
                            <?php if($editMode): ?>
                                <input type="hidden" name="EditPosition" value="<?php echo $_GET['EditP']; ?>" />
                            <?php endif; ?>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Position Name'); ?></label>
                                <input type="text" name="jobposition" class="sp-input" required
                                    value="<?php echo $editName; ?>"
                                    placeholder="<?php echo _('e.g., Software Engineer'); ?>" />
                            </div>

                            <div class="sp-d-flex sp-gap-2">
                                <button type="submit" name="submit" class="sp-btn sp-btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $editMode ? _('Update') : _('Save'); ?>
                                </button>
                                <?php if($editMode): ?>
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
                        <h3><?php echo _('List of Positions'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <table class="sp-table">
                            <thead>
                                <tr>
                                    <th><?php echo _('Position'); ?></th>
                                    <th><?php echo _('HOD for Department'); ?></th>
                                    <th style="width: 120px;"><?php echo _('Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(count($positions) == 0):
                                    echo '<tr><td colspan="3" class="sp-text-center sp-text-muted">';
                                    echo '<i class="fas fa-user-tie" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                                    echo _('No positions found'); 
                                    echo '</td></tr>';
                                endif;
                                
                                foreach($positions as $rows):
                                    $isHod = !empty($rows['department_name']);
                                ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-briefcase" style="color: var(--sp-primary); margin-right: 8px;"></i>
                                        <strong><?php echo $rows['name']; ?></strong>
                                    </td>
                                    <td>
                                        <?php if($isHod): ?>
                                            <span class="sp-badge sp-badge-success">
                                                <i class="fas fa-user-shield"></i> <?php echo $rows['department_name']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="sp-text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo $self; ?>?EditP=<?php echo $rows['code']; ?>" 
                                           class="sp-btn sp-btn-primary sp-btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if(!$isHod): ?>
                                            <a href="<?php echo $self; ?>?RemoveP=<?php echo $rows['code']; ?>" 
                                               class="sp-btn sp-btn-danger sp-btn-sm"
                                               onclick="return confirm('<?php echo _('Delete this position?'); ?>');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="sp-btn sp-btn-secondary sp-btn-sm" title="<?php echo _('Cannot delete - position is HOD'); ?>">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        <?php endif; ?>
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
