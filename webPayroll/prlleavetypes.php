<?php
include('includes/session.inc');
$Title = _('Leave Types Configuration');
include('includes/header.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

if(isset($_POST['submit'])) {
    $errors = 0;
    
    if(empty($_POST['leavetype_name'])) {
        prnMsg(_('Leave type name is required'), 'error');
        $errors++;
    }
    if(empty($_POST['leavetype_code'])) {
        prnMsg(_('Leave type code is required'), 'error');
        $errors++;
    }
    
    if($errors == 0) {
        if(isset($_POST['leavetype_id'])) {
            $sql = sprintf("UPDATE prlleavetypes SET 
                leavetype_code = '%s',
                leavetype_name = '%s',
                leavetype_name_short = '%s',
                default_days = %d,
                paid_leave = %d,
                requires_medical_certificate = %d,
                requires_approval = %d,
                allow_carryover = %d,
                max_carryover_days = %d,
                pro_rata_applicable = %d,
                encashable = %d,
                max_encashment_days = %s,
                requires_handover = %d,
                min_service_months = %d,
                statutory_leave = %d,
                can_be_advanced = %d,
                gender_applicable = '%s',
                color_code = '%s',
                description = '%s',
                sort_order = %d,
                active = %d
                WHERE leavetype_id = %d",
                $_POST['leavetype_code'],
                $_POST['leavetype_name'],
                $_POST['leavetype_name_short'],
                (int)$_POST['default_days'],
                isset($_POST['paid_leave']) ? 1 : 0,
                isset($_POST['requires_medical_certificate']) ? 1 : 0,
                isset($_POST['requires_approval']) ? 1 : 0,
                isset($_POST['allow_carryover']) ? 1 : 0,
                (int)$_POST['max_carryover_days'],
                isset($_POST['pro_rata_applicable']) ? 1 : 0,
                isset($_POST['encashable']) ? 1 : 0,
                !empty($_POST['max_encashment_days']) ? (int)$_POST['max_encashment_days'] : 'NULL',
                isset($_POST['requires_handover']) ? 1 : 0,
                (int)$_POST['min_service_months'],
                isset($_POST['statutory_leave']) ? 1 : 0,
                isset($_POST['can_be_advanced']) ? 1 : 0,
                $_POST['gender_applicable'],
                $_POST['color_code'],
                $_POST['description'],
                (int)$_POST['sort_order'],
                isset($_POST['active']) ? 1 : 0,
                (int)$_POST['leavetype_id']
            );
            $msg = _('Leave type updated successfully');
        } else {
            $sql = sprintf("INSERT INTO prlleavetypes 
                (leavetype_code, leavetype_name, leavetype_name_short, default_days, paid_leave,
                requires_medical_certificate, requires_approval, allow_carryover, max_carryover_days,
                pro_rata_applicable, encashable, max_encashment_days, requires_handover, min_service_months,
                statutory_leave, can_be_advanced, gender_applicable, color_code, description, sort_order, active)
                VALUES ('%s', '%s', '%s', %d, %d, %d, %d, %d, %d, %d, %d, %s, %d, %d, %d, %d, '%s', '%s', '%s', %d, %d)",
                $_POST['leavetype_code'],
                $_POST['leavetype_name'],
                $_POST['leavetype_name_short'],
                (int)$_POST['default_days'],
                isset($_POST['paid_leave']) ? 1 : 0,
                isset($_POST['requires_medical_certificate']) ? 1 : 0,
                isset($_POST['requires_approval']) ? 1 : 0,
                isset($_POST['allow_carryover']) ? 1 : 0,
                (int)$_POST['max_carryover_days'],
                isset($_POST['pro_rata_applicable']) ? 1 : 0,
                isset($_POST['encashable']) ? 1 : 0,
                !empty($_POST['max_encashment_days']) ? (int)$_POST['max_encashment_days'] : 'NULL',
                isset($_POST['requires_handover']) ? 1 : 0,
                (int)$_POST['min_service_months'],
                isset($_POST['statutory_leave']) ? 1 : 0,
                isset($_POST['can_be_advanced']) ? 1 : 0,
                $_POST['gender_applicable'],
                $_POST['color_code'],
                $_POST['description'],
                (int)$_POST['sort_order'],
                isset($_POST['active']) ? 1 : 0
            );
            $msg = _('Leave type created successfully');
        }
        
        $result = DB_query($sql, $db);
        if($result) {
            prnMsg($msg, 'success');
            unset($_POST);
        }
    }
}

if(isset($_GET['delete'])) {
    $sql = "SELECT COUNT(*) as cnt FROM prlleaveapplications WHERE leavetype_id = " . (int)$_GET['delete'];
    $result = DB_query($sql, $db);
    $row = DB_fetch_array($result);
    
    if($row['cnt'] > 0) {
        prnMsg(_('Cannot delete leave type that has existing applications'), 'warn');
    } else {
        DB_query("DELETE FROM prlleavetypes WHERE leavetype_id = " . (int)$_GET['delete'], $db);
        prnMsg(_('Leave type deleted successfully'), 'success');
    }
}

if(isset($_GET['edit'])) {
    $sql = "SELECT * FROM prlleavetypes WHERE leavetype_id = " . (int)$_GET['edit'];
    $result = DB_query($sql, $db);
    $edit_row = DB_fetch_array($result);
}
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Configure leave types for Kenyan statutory compliance'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-row">
            <div class="sp-col-4">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-plus-circle"></i>
                        <h3><?php echo isset($edit_row) ? _('Edit Leave Type') : _('Add New Leave Type'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            
                            <?php if(isset($edit_row)): ?>
                                <input type="hidden" name="leavetype_id" value="<?php echo $edit_row['leavetype_id']; ?>" />
                            <?php endif; ?>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Leave Code'); ?> *</label>
                                <input type="text" name="leavetype_code" class="sp-input" value="<?php echo isset($edit_row['leavetype_code']) ? htmlspecialchars($edit_row['leavetype_code']) : ''; ?>" required />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Leave Name'); ?> *</label>
                                <input type="text" name="leavetype_name" class="sp-input" value="<?php echo isset($edit_row['leavetype_name']) ? htmlspecialchars($edit_row['leavetype_name']) : ''; ?>" required />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Short Name'); ?></label>
                                <input type="text" name="leavetype_name_short" class="sp-input" value="<?php echo isset($edit_row['leavetype_name_short']) ? htmlspecialchars($edit_row['leavetype_name_short']) : ''; ?>" />
                            </div>

                            <div class="sp-row">
                                <div class="sp-col-6">
                                    <div class="sp-form-group">
                                        <label class="sp-label"><?php echo _('Days/Year'); ?></label>
                                        <input type="number" name="default_days" class="sp-input" value="<?php echo isset($edit_row['default_days']) ? $edit_row['default_days'] : 0; ?>" min="0" />
                                    </div>
                                </div>
                                <div class="sp-col-6">
                                    <div class="sp-form-group">
                                        <label class="sp-label"><?php echo _('Color'); ?></label>
                                        <input type="color" name="color_code" class="sp-input" value="<?php echo isset($edit_row['color_code']) ? $edit_row['color_code'] : '#0d6efd'; ?>" style="height:42px;" />
                                    </div>
                                </div>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Gender Applicable'); ?></label>
                                <select name="gender_applicable" class="sp-select">
                                    <option value="ALL" <?php echo (isset($edit_row['gender_applicable']) && $edit_row['gender_applicable'] == 'ALL') ? 'selected' : ''; ?>><?php echo _('All Employees'); ?></option>
                                    <option value="MALE" <?php echo (isset($edit_row['gender_applicable']) && $edit_row['gender_applicable'] == 'MALE') ? 'selected' : ''; ?>><?php echo _('Male Only'); ?></option>
                                    <option value="FEMALE" <?php echo (isset($edit_row['gender_applicable']) && $edit_row['gender_applicable'] == 'FEMALE') ? 'selected' : ''; ?>><?php echo _('Female Only'); ?></option>
                                </select>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Options'); ?></label>
                                <div class="sp-row">
                                    <?php
                                    $checkboxes = [
                                        'paid_leave' => _('Paid'),
                                        'requires_medical_certificate' => _('Medical Cert'),
                                        'requires_approval' => _('Approval Req.'),
                                        'allow_carryover' => _('Carry Over'),
                                        'pro_rata_applicable' => _('Pro-Rata'),
                                        'encashable' => _('Encashable'),
                                        'statutory_leave' => _('Statutory'),
                                        'active' => _('Active')
                                    ];
                                    foreach($checkboxes as $name => $label):
                                        $checked = isset($edit_row[$name]) && $edit_row[$name] == 1 ? 'checked' : '';
                                    ?>
                                    <div class="sp-col-6">
                                        <label class="sp-checkbox">
                                            <input type="checkbox" name="<?php echo $name; ?>" value="1" <?php echo $checked; ?> />
                                            <span><?php echo $label; ?></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Description'); ?></label>
                                <textarea name="description" class="sp-textarea" rows="3"><?php echo isset($edit_row['description']) ? htmlspecialchars($edit_row['description']) : ''; ?></textarea>
                            </div>

                            <div class="sp-d-flex sp-gap-2">
                                <button type="submit" name="submit" class="sp-btn sp-btn-primary sp-btn-block">
                                    <i class="fas fa-save"></i> <?php echo isset($edit_row) ? _('Update') : _('Save'); ?>
                                </button>
                                <?php if(isset($edit_row)): ?>
                                    <a href="<?php echo $RootPath; ?>/prlleavetypes.php" class="sp-btn sp-btn-secondary">
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
                        <h3><?php echo _('Configured Leave Types'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <table class="sp-table sp-table-color-border">
                            <thead>
                                <tr>
                                    <th><?php echo _('Code'); ?></th>
                                    <th><?php echo _('Leave Type'); ?></th>
                                    <th><?php echo _('Days'); ?></th>
                                    <th><?php echo _('Gender'); ?></th>
                                    <th><?php echo _('Status'); ?></th>
                                    <th><?php echo _('Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM prlleavetypes ORDER BY sort_order, leavetype_name";
                                $result = DB_query($sql, $db);

                                while($row = DB_fetch_array($result)):
                                    $color = $row['color_code'] ?: '#0d6efd';
                                ?>
                                <tr style="border-left-color: <?php echo $color; ?>;">
                                    <td><strong><?php echo $row['leavetype_code']; ?></strong></td>
                                    <td><?php echo $row['leavetype_name']; ?></td>
                                    <td class="sp-text-center"><?php echo $row['default_days']; ?></td>
                                    <td>
                                        <?php 
                                        $gender_icon = $row['gender_applicable'] == 'FEMALE' ? 'fa-venus' : ($row['gender_applicable'] == 'MALE' ? 'fa-mars' : 'fa-users');
                                        ?>
                                        <i class="fas <?php echo $gender_icon; ?>"></i> <?php echo _($row['gender_applicable']); ?>
                                    </td>
                                    <td>
                                        <?php if($row['active']): ?>
                                            <span class="sp-badge sp-badge-success"><i class="fas fa-check"></i> <?php echo _('Active'); ?></span>
                                        <?php else: ?>
                                            <span class="sp-badge sp-badge-secondary"><i class="fas fa-times"></i> <?php echo _('Inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo $RootPath; ?>/prlleavetypes.php?edit=<?php echo $row['leavetype_id']; ?>" class="sp-btn sp-btn-primary sp-btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if(!$row['statutory_leave']): ?>
                                            <a href="<?php echo $RootPath; ?>/prlleavetypes.php?delete=<?php echo $row['leavetype_id']; ?>" class="sp-btn sp-btn-danger sp-btn-sm" onclick="return confirm('<?php echo _('Are you sure you want to delete this leave type?'); ?>');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="sp-btn sp-btn-secondary sp-btn-sm" title="<?php echo _('Cannot delete statutory leave'); ?>">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
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
