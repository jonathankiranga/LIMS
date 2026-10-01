<?php
include('includes/session.inc');
$Title = _('Maintain Employee Records');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');
include('ExtFunc/humanresource.inc');
include('ExtFunc/employeetypes.inc');
include('ExtFunc/salary.inc');
include('includes/encrypt.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

$departments = GetDepartments();
$positions = GetPositions();
$banks = GetBanks();
$establishments = GetEstablishments();

if(isset($_GET['pfno'])){ 
    cleardata();
    $_POST['pfno'] = $_GET['pfno']; 
    $PFNO = $_GET['pfno']; 
}

if(isset($_GET['editid'])){  
    cleardata();
    $_POST['pfno'] = $_GET['editid'];
    $PFNO = $_GET['editid'];
}

if(isset($_POST['editpfno'])){  
    $_POST['pfno'] = $_POST['editpfno'];
    $PFNO = $_POST['editpfno'];
}

if(isset($_POST['saverecord']) || isset($_POST['submitrecord'])){
    $errors = 0;
    
    if(mb_strlen($_POST['pfno']) == 0){ prnMsg(_('Please click to get Payroll No'), 'warn'); $errors = 1;}
    if(mb_strlen($_POST['fname']) == 0){ prnMsg(_('Please enter the First Name'), 'warn'); $errors = 1;}
    if(mb_strlen($_POST['idno']) == 0){ prnMsg(_('Please enter the ID'), 'warn'); $errors = 1;}
    if(mb_strlen($_POST['telno']) == 0){ prnMsg(_('Please enter the Telephone No'), 'warn'); $errors = 1;}
    if(mb_strlen($_POST['dob']) == 0){ prnMsg(_('Please enter the Date of Birth'), 'warn'); $errors = 1;}
    if(mb_strlen($_POST['doe']) == 0){ prnMsg(_('Please enter the Date Employed'), 'warn'); $errors = 1;}
    if($_POST['freqcode'] == '2' || $_POST['freqcode'] == '3'){ 
        if(!mb_strlen($_POST['dot']) > 0){ prnMsg(_('Please enter the date the contract ends'), 'warn'); $errors = 1;}
    }
    
    if($errors == 1){
        unset($_POST['saverecord']);
        unset($_POST['submitrecord']);
    }
    
    if(isset($_POST['saverecord'])){ 
        savemployee(); 
        cleardata(); 
    }
    
    if(isset($_POST['submitrecord'])){ editemployee(); cleardata(); }
}

if(isset($_POST['pfno']) and !isset($_POST['editpfno'])){
    showemployee(); 
}

$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Add or edit employee master records'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <form method="post" action="<?php echo $self; ?>" id="prlhrform">
            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />

            <?php if(isset($PFNO)): ?>
                <input type="hidden" name="editpfno" value="<?php echo $PFNO; ?>"/>
            <?php endif; ?>

            <div class="sp-card">
                <div class="sp-card-header">
                    <i class="fas fa-user"></i>
                    <h3><?php echo _('Employment Details'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <div class="sp-row">
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Employment Type'); ?></label>
                                <select name="freqcode" class="sp-select" id="PrType" onchange="this.form.submit();">
                                    <?php foreach($employment as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($key == trim($_POST['freqcode'])) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Branch'); ?></label>
                                <select name="branch" class="sp-select">
                                    <option value="">-- <?php echo _('Select'); ?> --</option>
                                    <?php
                                    $SQL = "SELECT * FROM prlestablishment";
                                    $ResultIndex = DB_query($SQL, $db);
                                    while($row = DB_fetch_array($ResultIndex)):
                                    ?>
                                        <option value="<?php echo $row['code']; ?>" <?php echo ($row['code'] == $_POST['branch']) ? 'selected' : ''; ?>>
                                            <?php echo trim($row['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Department'); ?></label>
                                <select name="department" class="sp-select">
                                    <option value="">-- <?php echo _('Select'); ?> --</option>
                                    <?php foreach($departments as $row): ?>
                                        <option value="<?php echo trim($row['code']); ?>" <?php echo ($row['code'] == trim($_POST['department'])) ? 'selected' : ''; ?>>
                                            <?php echo trim($row['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Position'); ?></label>
                                <select name="position" class="sp-select">
                                    <option value="">-- <?php echo _('Select'); ?> --</option>
                                    <?php foreach($positions as $row): ?>
                                        <option value="<?php echo trim($row['code']); ?>" <?php echo ($row['code'] == trim($_POST['position'])) ? 'selected' : ''; ?>>
                                            <?php echo trim($row['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
                <div class="sp-card-header">
                    <i class="fas fa-id-card"></i>
                    <h3><?php echo _('Personal Information'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <div class="sp-row">
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo isset($PFNO) ? _('PayRoll ID') : ''; ?></label>
                                <?php if(!isset($PFNO)): ?>
                                    <input type="button" name="GetPrNo" id="GetPrNo" class="sp-btn sp-btn-secondary" value="<?php echo _('Get PayRoll ID'); ?>"/>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('PayRoll No'); ?></label>
                                <input type="text" name="pfno" id="pfidno" class="sp-input" value="<?php echo $_POST['pfno']; ?>" readonly />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('ID No.'); ?></label>
                                <input type="text" name="idno" class="sp-input" value="<?php echo $_POST['idno']; ?>" maxlength="20" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Salutation'); ?></label>
                                <select name="status" class="sp-select">
                                    <option value="Mr" <?php echo ($_POST['status'] == 'Mr') ? 'selected' : ''; ?>><?php echo _('Mr'); ?></option>
                                    <option value="Mrs" <?php echo ($_POST['status'] == 'Mrs') ? 'selected' : ''; ?>><?php echo _('Mrs'); ?></option>
                                    <option value="Ms" <?php echo ($_POST['status'] == 'Ms') ? 'selected' : ''; ?>><?php echo _('Ms'); ?></option>
                                    <option value="Dr" <?php echo ($_POST['status'] == 'Dr') ? 'selected' : ''; ?>><?php echo _('Dr'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="sp-row">
                        <div class="sp-col-4">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('First Name'); ?> *</label>
                                <input type="text" name="fname" class="sp-input" value="<?php echo $_POST['fname']; ?>" required />
                            </div>
                        </div>
                        <div class="sp-col-4">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Middle Name'); ?></label>
                                <input type="text" name="mname" class="sp-input" value="<?php echo $_POST['mname']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-4">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Last Name'); ?></label>
                                <input type="text" name="lname" class="sp-input" value="<?php echo $_POST['lname']; ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="sp-row">
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Date of Birth'); ?></label>
                                <input type="date" name="dob" class="sp-input date" value="<?php echo $_POST['dob']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Date Employed'); ?></label>
                                <input type="date" name="doe" class="sp-input date" value="<?php echo $_POST['doe']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Contract End Date'); ?></label>
                                <input type="date" name="dot" class="sp-input date" value="<?php echo $_POST['dot']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Work Hours/Day'); ?></label>
                                <input type="number" name="noofhrsperday" class="sp-input" value="<?php echo $_POST['noofhrsperday'] ?: 8; ?>" min="1" max="24" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
                <div class="sp-card-header">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <h3><?php echo _('Tax & Banking Information'); ?></h3>
                </div>
                <div class="sp-card-body">
                    <div class="sp-row">
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('P.I.N.'); ?></label>
                                <input type="text" name="pinno" class="sp-input" value="<?php echo $_POST['pinno']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('N.S.S.F. No.'); ?></label>
                                <input type="text" name="nssfno" class="sp-input" value="<?php echo $_POST['nssfno']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('N.H.I.F. No.'); ?></label>
                                <input type="text" name="nhifno" class="sp-input" value="<?php echo $_POST['nhifno']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Telephone No.'); ?></label>
                                <input type="text" name="telno" class="sp-input" value="<?php echo $_POST['telno']; ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="sp-row">
                        <div class="sp-col-6">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Email Address'); ?></label>
                                <input type="email" name="email" class="sp-input" value="<?php echo $_POST['email']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Bank Account No.'); ?></label>
                                <input type="text" name="bankaccount" class="sp-input" value="<?php echo $_POST['bankaccount']; ?>" />
                            </div>
                        </div>
                        <div class="sp-col-3">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Bank'); ?></label>
                                <select name="bank" class="sp-select">
                                    <option value="">-- <?php echo _('Select'); ?> --</option>
                                    <?php foreach($banks as $row): ?>
                                        <option value="<?php echo $row['code']; ?>" <?php echo ($row['code'] == $_POST['bank']) ? 'selected' : ''; ?>>
                                            <?php echo $row['bankname']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php echo createcustom(); ?>

            <div class="sp-d-flex sp-justify-between sp-mt-3">
                <div>
                    <button type="submit" class="sp-btn sp-btn-secondary">
                        <i class="fas fa-sync-alt"></i> <?php echo _('Refresh'); ?>
                    </button>
                </div>
                <div class="sp-d-flex sp-gap-2">
                    <?php if(!isset($PFNO)): ?>
                        <button type="submit" name="saverecord" class="sp-btn sp-btn-success">
                            <i class="fas fa-user-plus"></i> <?php echo _('Create Employee'); ?>
                        </button>
                    <?php else: ?>
                        <button type="submit" name="submitrecord" class="sp-btn sp-btn-primary">
                            <i class="fas fa-save"></i> <?php echo _('Update Employee'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
