<?php
include('includes/session.inc');
$Title = _('Company Special Days');
include('includes/header.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

if(isset($_POST['submit'])){
    DB_Txn_Begin($db);
    if(isset($_POST['EditName'])){
        $SQL = sprintf("UPDATE prlspecialdays SET name='%s', day='%s', month='%s', week='%s' WHERE name='%s'",
            $_POST['Nameoffday'], $_POST['day'], $_POST['month'], $_POST['week'], $_POST['EditName']);
    } else {
        $SQL = sprintf("INSERT INTO prlspecialdays (name, day, month, week) VALUES ('%s', '%s', '%s', '%s')",
            $_POST['Nameoffday'], $_POST['day'], $_POST['month'], $_POST['week']);
    }
    
    $results = DB_query($SQL, $db);
    if(DB_error_no($db) > 0){
        DB_Txn_Rollback($db);
        prnMsg(DB_error_msg($db), 'error');
    } else {
        DB_Txn_Commit($db);
        prnMsg(isset($_POST['EditName']) ? _('Special day updated successfully') : _('Special day added successfully'), 'success');
    }
}

$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$editMode = isset($_GET['Name']);
$editName = '';
$editDay = '';
$editMonth = '';
$editWeek = '';

if($editMode){
    $SQL = "SELECT name, day, month, week FROM prlspecialdays WHERE name='" . $_GET['Name'] . "'";
    $ResultIndex = DB_query($SQL, $db);
    $Row = DB_fetch_array($ResultIndex);
    $editName = $Row['name'];
    $editDay = $Row['day'];
    $editMonth = $Row['month'];
    $editWeek = $Row['week'];
}

$months = [1 => _('January'), 2 => _('February'), 3 => _('March'), 4 => _('April'), 
           5 => _('May'), 6 => _('June'), 7 => _('July'), 8 => _('August'),
           9 => _('September'), 10 => _('October'), 11 => _('November'), 12 => _('December')];
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-calendar-star"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Configure company-specific special days and holidays'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-alert sp-alert-info">
            <i class="fas fa-info-circle fa-lg"></i>
            <div>
                <strong><?php echo _('Note:'); ?></strong>
                <?php echo _('Special days are company-specific holidays (e.g., company founding day). For Kenyan gazetted holidays, use the Kenya Holidays module.'); ?>
            </div>
        </div>

        <div class="sp-row">
            <div class="sp-col-4">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-<?php echo $editMode ? 'edit' : 'plus-circle'; ?>"></i>
                        <h3><?php echo $editMode ? _('Edit Special Day') : _('Add Special Day'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" action="<?php echo $self; ?>">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            
                            <?php if($editMode): ?>
                                <input type="hidden" name="EditName" value="<?php echo $_GET['Name']; ?>" />
                            <?php endif; ?>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Holiday Name'); ?></label>
                                <input type="text" name="Nameoffday" class="sp-input" required
                                    value="<?php echo $editName; ?>"
                                    placeholder="<?php echo _('e.g., Company Founding Day'); ?>" />
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Day'); ?></label>
                                <select name="day" class="sp-select">
                                    <option value="">-- <?php echo _('Select'); ?> --</option>
                                    <?php for($i = 1; $i <= 31; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($editDay == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Month'); ?></label>
                                <select name="month" class="sp-select">
                                    <option value="">-- <?php echo _('Select'); ?> --</option>
                                    <?php foreach($months as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php echo ($editMonth == $num) ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                        <h3><?php echo _('List of Special Days'); ?></h3>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <table class="sp-table">
                            <thead>
                                <tr>
                                    <th><?php echo _('Holiday Name'); ?></th>
                                    <th><?php echo _('Date'); ?></th>
                                    <th style="width: 100px;"><?php echo _('Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $SQL = "SELECT name, day, month, week FROM prlspecialdays ORDER BY month, day";
                                $ResultIndex = DB_query($SQL, $db);
                                
                                if(DB_num_rows($ResultIndex) == 0):
                                    echo '<tr><td colspan="3" class="sp-text-center sp-text-muted">';
                                    echo '<i class="fas fa-calendar-times" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                                    echo _('No special days configured'); 
                                    echo '</td></tr>';
                                endif;
                                
                                while($rows = DB_fetch_array($ResultIndex)):
                                    $monthName = $months[$rows['month']] ?? $rows['month'];
                                ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-star" style="color: var(--sp-warning); margin-right: 8px;"></i>
                                        <strong><?php echo $rows['name']; ?></strong>
                                    </td>
                                    <td><?php echo $rows['day'] . ' ' . $monthName; ?></td>
                                    <td>
                                        <a href="<?php echo $self; ?>?Name=<?php echo urlencode($rows['name']); ?>" 
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
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
