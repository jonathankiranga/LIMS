<?php
include('includes/session.inc');
$Title = _('Maintain Salary Scale');
include('includes/header.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

if(isset($_POST['submit'])){
    DB_Txn_Begin($db);
    $sql = sprintf("INSERT INTO prlsalaryscale (qualifications, jbgroup, min, annual_inc, max)
        SELECT qualification, name, '%s', '%s', '%s' 
        FROM prljobgroup WHERE rowid = '%s'",
        $_POST['minamount'], $_POST['increment'], $_POST['maxamount'], $_POST['jobgroupid']);

    DB_query($sql, $db);
    if(DB_error_no($db) > 0){
        DB_Txn_Rollback($db);
        prnMsg(DB_error_msg($db), 'error');
    } else {
        DB_Txn_Commit($db);
        prnMsg(_('Salary scale created successfully'), 'success');
    }
}

$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Manage salary scales and pay grades'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-row">
            <div class="sp-col-4">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-plus-circle"></i>
                        <h3><?php echo _('Add Salary Scale'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" action="<?php echo $self; ?>">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Job Group'); ?></label>
                                <select name="jobgroupid" class="sp-select" required>
                                    <option value="">-- <?php echo _('Select Job Group'); ?> --</option>
                                    <?php
                                    $SQL = "SELECT rowid, name FROM prljobgroup ORDER BY name";
                                    $ResultIndex = DB_query($SQL, $db);
                                    if(DB_num_rows($ResultIndex) == 0):
                                        echo '<option value="" disabled>' . _('No job groups available') . '</option>';
                                    endif;
                                    while($jbrow = DB_fetch_array($ResultIndex)):
                                        echo '<option value="' . $jbrow['rowid'] . '">' . $jbrow['name'] . '</option>';
                                    endwhile;
                                    ?>
                                </select>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Minimum Salary'); ?></label>
                                <input type="number" name="minamount" class="sp-input" value="0.00" step="0.01" required />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Annual Increment'); ?></label>
                                <input type="number" name="increment" class="sp-input" value="0.00" step="0.01" required />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Maximum Salary'); ?></label>
                                <input type="number" name="maxamount" class="sp-input" value="0.00" step="0.01" required />
                            </div>

                            <button type="submit" name="submit" class="sp-btn sp-btn-primary sp-btn-block">
                                <i class="fas fa-save"></i> <?php echo _('Save'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="sp-col-8">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-list"></i>
                        <h3><?php echo _('Salary Scales'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <table class="sp-table">
                            <thead>
                                <tr>
                                    <th><?php echo _('Qualification/Job Group'); ?></th>
                                    <th><?php echo _('Job Group'); ?></th>
                                    <th class="sp-text-right"><?php echo _('Min'); ?></th>
                                    <th class="sp-text-right"><?php echo _('Increment'); ?></th>
                                    <th class="sp-text-right"><?php echo _('Max'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM prlsalaryscale ORDER BY qualifications, jbgroup";
                                $results = DB_query($sql, $db);
                                
                                if(DB_num_rows($results) == 0):
                                    echo '<tr><td colspan="5" class="sp-text-center sp-text-muted">';
                                    echo '<i class="fas fa-money-bill-wave" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                                    echo _('No salary scales configured'); 
                                    echo '</td></tr>';
                                endif;
                                
                                while($rows = DB_fetch_array($results)):
                                ?>
                                <tr>
                                    <td><strong><?php echo $rows['qualifications']; ?></strong></td>
                                    <td><?php echo $rows['jbgroup']; ?></td>
                                    <td class="sp-text-right"><?php echo number_format($rows['min'], 2); ?></td>
                                    <td class="sp-text-right"><?php echo number_format($rows['annual_inc'], 2); ?></td>
                                    <td class="sp-text-right"><?php echo number_format($rows['max'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
