<?php
include('includes/session.inc');
$Title = _('Create PayRoll Salary Matrix');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');
include('ExtFunc/payrollmatrix.inc');
include('ExtFunc/gensalary.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

$mypage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if(isset($_POST['pfno'][0]) && $_POST['pfno'][0] == 'ALL'){
    $employees = GetEmployees();
    foreach($employees as $emp){
        $_POST['pfno'][] = $emp['pf_no'];
    }
}

$period = GetCurrentPayrollPeriod();
$gid = $period ? $period['pkey'] : 0;

$employees = GetEmployees();
$products = GetProducts();

if(isset($_POST['insertitem']) || isset($_POST['deleteitem'])){
    if(!isset($_POST['pfno'])){
        prnMsg(_('Please select an employee'), 'warn');
        unset($_POST['insertitem']);
        unset($_POST['deleteitem']);
    }
}

if(isset($_POST['deleteitem'])){
    $employeeList = $_POST['pfno'];
    if(in_array('ALL', $employeeList)){
        $allEmployees = GetEmployees();
        $employeeList = array();
        foreach($allEmployees as $emp){
            $employeeList[] = $emp['pf_no'];
        }
    }
    
    foreach($employeeList as $pfno){
        DB_query("DELETE FROM prlmatrix WHERE pfno='" . DB_escape_string($pfno) . "' AND prodid='" . DB_escape_string($_POST['code']) . "'", $db);
    }
    prnMsg(_('Items removed from payroll'), 'success');
}

if(isset($_POST['insertitem'])){
    $SQL_date = "SELECT pkey FROM prlmrollperiods WHERE open = 1 ORDER BY pkey DESC";
    $ResultIndex = DB_query($SQL_date, $db);
    $rows = DB_fetch_row($ResultIndex);
    $gid = $rows[0];
    
    $prod_sql = "SELECT description, deduction, employerfactor FROM prlproducts WHERE code = '" . DB_escape_string($_POST['code']) . "'";
    $prod_result = DB_query($prod_sql, $db);
    $prod_row = DB_fetch_array($prod_result);
    $product_name = $prod_row['description'];
    $product_deduction = $prod_row['deduction'];
    $employer_factor = floatval($prod_row['employerfactor']);
    
    $nonrecuring = (isset($_POST['nonrecuring']) && $_POST['nonrecuring'] == "on") ? 1 : 0;
    
    $employeeList = $_POST['pfno'];
    if(in_array('ALL', $employeeList)){
        $allEmployees = GetEmployees();
        $employeeList = array();
        foreach($allEmployees as $emp){
            $employeeList[] = $emp['pf_no'];
        }
    }
    
    $SQL = array();
    DB_Txn_Begin($db);
    
    foreach($employeeList as $pfno_raw){
        $pfno = DB_escape_string($pfno_raw);
        $code = DB_escape_string($_POST['code']);
        
        $calcValue = GetPayrollValue($pfno, $_POST['code'], $gid);
        $employerAmount = $calcValue * $employer_factor;
        $employeeAmount = $calcValue;
        
        $SQL[] = "INSERT INTO auditprlmatrix (pfno, prodid, name, employeramount, deduction, nonrecuring, amount, posted, userid)
            VALUES ('" . $pfno . "', '" . $code . "', '" . DB_escape_string($product_name) . "', " . $employerAmount . ", " . $product_deduction . ", " . $nonrecuring . ", " . $employeeAmount . ", NOW(), '" . DB_escape_string($_SESSION['UsersRealName']) . "')";
        
        $SQL[] = "DELETE FROM prlmatrix WHERE pfno='" . $pfno . "' AND prodid='" . $code . "'";
        
        $SQL[] = "INSERT INTO prlmatrix (pfno, prodid, name, employeramount, deduction, nonrecuring, amount)
            VALUES ('" . $pfno . "', '" . $code . "', '" . DB_escape_string($product_name) . "', " . $employerAmount . ", " . $product_deduction . ", " . $nonrecuring . ", " . $employeeAmount . ")";
    }
    
    foreach($SQL as $value){
        $result = DB_query($value, $db);
        if(DB_error_no($db) > 0){
            prnMsg("Error: " . DB_error_msg($db) . "<br>Query: " . $value, 'error');
        }
    }
    
    if(DB_error_no($db) > 0){
        prnMsg(DB_error_msg($db), 'error');
        DB_Txn_Rollback($db);
    } else {
        prnMsg(_('Payroll matrix updated successfully'), 'success');
        DB_Txn_Commit($db);
    }
}

$SQL = "SELECT code, description, deduction, hastable FROM prlproducts WHERE code != '999999' AND code NOT IN (SELECT deductcode FROM prlstaffloans) ORDER BY description ASC";
$ResultIndex = DB_query($SQL, $db);
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-table"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Run PAYE anytime you change an allowance or deduction. This is the only module that recalculates PAYE.'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-alert sp-alert-warning">
            <i class="fas fa-exclamation-triangle fa-lg"></i>
            <div>
                <strong><?php echo _('Important:'); ?></strong>
                <?php echo _('This module recalculates PAYE. Run it after making any changes to allowances or deductions.'); ?>
            </div>
        </div>

        <div class="sp-row">
            <div class="sp-col-5">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-plus-circle"></i>
                        <h3><?php echo _('Add Item to Payroll'); ?></h3>
                    </div>
                    <div class="sp-card-body">
                        <form method="post" action="<?php echo $mypage; ?>">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Payroll Item'); ?></label>
                                <select name="code" class="sp-select" required>
                                    <?php foreach($products as $rows): ?>
                                        <?php if($rows['code'] == '999999') continue; ?>
                                        <option value="<?php echo $rows['code']; ?>" <?php echo (isset($_POST['code']) && $_POST['code'] == trim($rows['code'])) ? 'selected' : ''; ?>>
                                            <?php echo ucwords($rows['description']); ?> [<?php echo $rows['deduction'] == 0 ? _('Deduction') : _('Allowance'); ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Select Employee(s)'); ?></label>
                                <select name="pfno[]" class="sp-select" multiple size="8" style="min-height: 150px;">
                                    <option value="ALL"><?php echo _('Apply For All Employees'); ?></option>
                                    <?php foreach($employees as $emp): ?>
                                        <option value="<?php echo $emp['pf_no']; ?>">
                                            <?php echo $emp['pf_no'] . ' : ' . $emp['fname'] . ' ' . $emp['mname'] . ' ' . $emp['lname']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="sp-text-muted"><?php echo _('Hold Ctrl/Cmd to select multiple'); ?></small>
                            </div>

                            <div class="sp-form-group">
                                <label class="sp-checkbox">
                                    <input type="checkbox" name="nonrecuring" />
                                    <span><?php echo _('Non-Recurring (Deduct/Allow only once)'); ?></span>
                                </label>
                            </div>

                            <div class="sp-d-flex sp-gap-2">
                                <button type="submit" name="insertitem" class="sp-btn sp-btn-success">
                                    <i class="fas fa-plus"></i> <?php echo _('Add to Payroll'); ?>
                                </button>
                                <button type="submit" name="deleteitem" class="sp-btn sp-btn-danger">
                                    <i class="fas fa-trash"></i> <?php echo _('Remove'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="sp-col-7">
                <div class="sp-card">
                    <div class="sp-card-header">
                        <i class="fas fa-list"></i>
                        <h3><?php echo _('Current Payroll Matrix'); ?></h3>
                        <div style="margin-left: auto;">
                            <button onclick="tableToExcel('Export', 'Master Roll')" class="sp-btn sp-btn-secondary sp-btn-sm">
                                <i class="fas fa-file-excel"></i> <?php echo _('Export to Excel'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="sp-card-body sp-p-0">
                        <?php
                        Refreshpayroll();
                        $matrix = new emplyeematrix();
                        $matrix->ShowbyProducts();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
