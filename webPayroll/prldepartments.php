<?php
include('includes/session.inc');
$Title = _('Maintain Departments');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

if(isset($_POST['submit'])){
    DB_Txn_Begin($db);
    $SQL = sprintf("INSERT INTO prldepartments (code, name, hod) VALUES ((SELECT COALESCE(MAX(code), 0) + 1 FROM prldepartments), '%s', '%s')",
        $_POST['departname'], $_POST['hod']);
    $results = DB_query($SQL, $db);

    if(DB_error_no($db) > 0){
        DB_Txn_Rollback($db);
        prnMsg(DB_error_msg($db), 'error');
    } else {
        DB_Txn_Commit($db);
        prnMsg(_('Department created successfully'), 'success');
    }
}

if(isset($_POST['Editdep'])){
    $SQL = sprintf("UPDATE prldepartments SET name='%s', hod='%s' WHERE code='%s'",
        $_POST['departname'], $_POST['hod'], $_POST['Departid']);
    $results = DB_query($SQL, $db);
    prnMsg(_('Department updated successfully'), 'success');
}

if(isset($_GET['DepartidDel'])){
    $SQL = sprintf("SELECT name as depar FROM prldepartments
        JOIN prlemployeemaster ON department = prldepartments.code 
        WHERE prldepartments.code='%s'", $_GET['DepartidDel']);
    
    $results = DB_query($SQL, $db);
    if(DB_num_rows($results) > 0){
        prnMsg(_('You cannot delete this department because it is in use'), 'warn');
    } else {
        DB_Txn_Begin($db);
        $SQL = sprintf("DELETE FROM prldepartments WHERE code='%s'", $_GET['DepartidDel']);
        $results = DB_query($SQL, $db);

        if(DB_error_no($db) > 0){
            DB_Txn_Rollback($db);
            prnMsg(DB_error_msg($db), 'error');
        } else {
            DB_Txn_Commit($db);
            prnMsg(_('Department deleted successfully'), 'success');
        }
    }
}

$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$editMode = isset($_GET['Departid']);
$selectedcode = '';
$selectedname = '';

if($editMode){
    $rowsp = GetDepartment($_GET['Departid']);
    $selectedcode = $rowsp['hod'];
    $selectedname = $rowsp['name'];
}

$departments = GetDepartments();
$positions = GetPositions();
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-building"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Manage organizational departments and their heads'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-row">
            <div class="sp-col-4">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-<?php echo $editMode ? 'edit' : 'plus-circle'; ?>"></i>
                        <h3><?php echo $editMode ? _('Edit Department') : _('Add New Department'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" action="<?php echo $self; ?>">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            
                            <?php if($editMode): ?>
                                <input type="hidden" name="Departid" value="<?php echo $_GET['Departid']; ?>" />
                            <?php endif; ?>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Department Name'); ?></label>
                                <input type="text" name="departname" class="sp-input" required
                                    value="<?php echo $selectedname; ?>"
                                    placeholder="<?php echo _('e.g., Human Resources'); ?>" />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Head of Department (HOD)'); ?></label>
                                <select name="hod" class="sp-select">
                                    <option value="">-- <?php echo _('Select HOD'); ?> --</option>
                                    <?php
                                    foreach($positions as $pos):
                                        $sel = ($pos['code'] == $selectedcode) ? 'selected' : '';
                                        echo '<option value="' . $pos['code'] . '" ' . $sel . '>' . $pos['name'] . '</option>';
                                    endforeach;
                                    ?>
                                </select>
                            </div>

                            <div class="sp-d-flex sp-gap-2">
                                <button type="submit" name="<?php echo $editMode ? 'Editdep' : 'submit'; ?>" class="sp-btn sp-btn-primary">
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
                        <h3><?php echo _('List of Departments'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <table class="sp-table">
                            <thead>
                                <tr>
                                    <th><?php echo _('Department'); ?></th>
                                    <th><?php echo _('Head of Department'); ?></th>
                                    <th style="width: 120px;"><?php echo _('Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(count($departments) == 0):
                                    echo '<tr><td colspan="3" class="sp-text-center sp-text-muted">';
                                    echo '<i class="fas fa-building" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                                    echo _('No departments found'); 
                                    echo '</td></tr>';
                                endif;
                                
                                foreach($departments as $rows):
                                ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-building" style="color: var(--sp-primary); margin-right: 8px;"></i>
                                        <strong><?php echo $rows['name']; ?></strong>
                                    </td>
                                    <td><?php echo $rows['hod'] ?: 'N/A'; ?></td>
                                    <td>
                                        <a href="<?php echo $self; ?>?Departid=<?php echo $rows['code']; ?>" 
                                           class="sp-btn sp-btn-primary sp-btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $self; ?>?DepartidDel=<?php echo $rows['code']; ?>" 
                                           class="sp-btn sp-btn-danger sp-btn-sm"
                                           onclick="return confirm('<?php echo _('Delete this department?'); ?>');">
                                            <i class="fas fa-trash"></i>
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
