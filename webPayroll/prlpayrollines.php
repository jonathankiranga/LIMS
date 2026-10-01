<?php
include('includes/session.inc');
$Title = _('PayRoll Products Maintenance');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

$myPage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$myaccountlist = array();
$deductionarray = array(0 => _('Deduction'), 1 => _('Allowance or Non-Cash Benefit'));
$hasatablearray = array(0 => _('No'), 1 => _('Graduated Scale'), 2 => _('Percentage'));
$pensionarray = array(0 => _('Standard Item'), 1 => _('TAX Item'), 2 => _('Pension Scheme Item'), 3 => _('NON-cash Item'));
$reliefname = array(0 => _('Standard'), 1 => _('Insurance Relief'), 2 => _('Pension Relief'));

include('ExtFunc/payrolproducts.inc');

if(isset($_POST['settings'])){
    $_GET['settings'] = $_POST['settings'];
    unset($_POST['settings']);
}

if(isset($_POST['reliefs'])){
    $_GET['reliefs'] = $_POST['reliefs'];
    unset($_POST['reliefs']);
}

$mode = 'list';

if(isset($_GET['settings'])){
    $mode = 'settings';
}
if(isset($_GET['edittax'])){
    $mode = 'edittax';
}
if(isset($_GET['notable'])){
    $mode = 'notable';
}
if(isset($_GET['reliefs'])){
    $mode = 'reliefs';
}
if(isset($_GET['code'])){
    $mode = 'edit';
}
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-cogs"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Configure payroll items, allowances, and deductions'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <?php if($mode == 'list'): ?>
        <div class="sp-card">
            <div class="sp-card-header">
                <i class="fas fa-list"></i>
                <h3><?php echo _('Payroll Products'); ?></h3>
            </div>
            <div class="sp-card-body sp-p-0">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th><?php echo _('Code'); ?></th>
                            <th><?php echo _('Description'); ?></th>
                            <th><?php echo _('Type'); ?></th>
                            <th><?php echo _('Tax/Pension'); ?></th>
                            <th><?php echo _('Registration No.'); ?></th>
                            <th><?php echo _('Has Table'); ?></th>
                            <th><?php echo _('Employer Factor'); ?></th>
                            <th><?php echo _('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $products = GetProducts();
                        
                        foreach($products as $rows):
                            if($rows['code'] == '999999') continue;
                            $typeLabel = $deductionarray[$rows['deduction']] ?? $rows['deduction'];
                            $taxLabel = $pensionarray[$rows['pens_tax']] ?? $rows['pens_tax'];
                            $hasTableLabel = $hasatablearray[$rows['hastable']] ?? $rows['hastable'];
                        ?>
                        <tr>
                            <td><strong><?php echo $rows['code']; ?></strong></td>
                            <td><?php echo $rows['description']; ?></td>
                            <td>
                                <span class="sp-badge <?php echo $rows['deduction'] == 0 ? 'sp-badge-danger' : 'sp-badge-success'; ?>">
                                    <?php echo $typeLabel; ?>
                                </span>
                            </td>
                            <td><?php echo $taxLabel; ?></td>
                            <td><?php echo $rows['membership_no']; ?></td>
                            <td><?php echo $hasTableLabel; ?></td>
                            <td class="sp-text-right"><?php echo $rows['employerfactor']; ?></td>
                            <td>
                                <a href="<?php echo $myPage; ?>?code=<?php echo $rows['code']; ?>" class="sp-btn sp-btn-primary sp-btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if($rows['hastable'] == 1 || $rows['hastable'] == 2): ?>
                                    <a href="<?php echo $myPage; ?>?settings=<?php echo $rows['code']; ?>" class="sp-btn sp-btn-warning sp-btn-sm" title="<?php echo _('Tax Brackets'); ?>">
                                        <i class="fas fa-percentage"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if($rows['pens_tax'] == 1): ?>
                                    <a href="<?php echo $myPage; ?>?reliefs=<?php echo $rows['code']; ?>&name=<?php echo urlencode($rows['description']); ?>" class="sp-btn sp-btn-info sp-btn-sm" title="<?php echo _('Reliefs'); ?>">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
            <div class="sp-card-header">
                <i class="fas fa-plus-circle"></i>
                <h3><?php echo _('Add New Product'); ?></h3>
            </div>
            <div class="sp-card-body">
                <form method="post" action="<?php echo $myPage; ?>">
                    <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                    <div class="sp-row">
                        <div class="sp-col-4">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Description'); ?></label>
                                <input type="text" name="description" class="sp-input" required placeholder="<?php echo _('e.g., House Allowance'); ?>" />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Type'); ?></label>
                                <select name="deduction" class="sp-select" required>
                                    <option value="0"><?php echo _('Deduction'); ?></option>
                                    <option value="1"><?php echo _('Allowance'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Tax/Pension'); ?></label>
                                <select name="pens_tax" class="sp-select">
                                    <option value="0"><?php echo _('Standard'); ?></option>
                                    <option value="1"><?php echo _('TAX Item'); ?></option>
                                    <option value="2"><?php echo _('Pension'); ?></option>
                                    <option value="3"><?php echo _('NON-cash'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Registration No.'); ?></label>
                                <input type="text" name="membership_no" class="sp-input" />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Has Table'); ?></label>
                                <select name="hastable" class="sp-select" required>
                                    <option value="0"><?php echo _('No'); ?></option>
                                    <option value="1"><?php echo _('Graduated'); ?></option>
                                    <option value="2"><?php echo _('Percentage'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="sp-row">
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Employer Factor'); ?></label>
                                <input type="number" name="employerfactor" class="sp-input" value="1.0" step="0.01" />
                            </div>
                        </div>
                        <div class="sp-col-2" style="align-self: flex-end;">
                            <button type="submit" name="submitproduct" class="sp-btn sp-btn-success">
                                <i class="fas fa-plus"></i> <?php echo _('Add Product'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        
        <?php if(isset($_GET['code'])): ?>
        <?php
        $rows = GetProduct($_GET['code']);
        ?>
        <div class="sp-card">
            <div class="sp-card-header">
                <i class="fas fa-edit"></i>
                <h3><?php echo _('Edit Product'); ?>: <?php echo $rows['description']; ?></h3>
            </div>
            <div class="sp-card-body">
                <form method="post" action="<?php echo $myPage; ?>">
                    <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                    <div class="sp-row">
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Code'); ?></label>
                                <input type="text" name="code" class="sp-input" value="<?php echo $rows['code']; ?>" readonly />
                            </div>
                        </div>
                        <div class="sp-col-4">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Description'); ?></label>
                                <input type="text" name="description" class="sp-input" value="<?php echo $rows['description']; ?>" required />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Type'); ?></label>
                                <select name="deduction" class="sp-select">
                                    <option value="0" <?php echo ($rows['deduction'] == 0) ? 'selected' : ''; ?>><?php echo _('Deduction'); ?></option>
                                    <option value="1" <?php echo ($rows['deduction'] == 1) ? 'selected' : ''; ?>><?php echo _('Allowance'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Tax/Pension'); ?></label>
                                <select name="pens_tax" class="sp-select">
                                    <option value="0" <?php echo ($rows['pens_tax'] == 0) ? 'selected' : ''; ?>><?php echo _('Standard'); ?></option>
                                    <option value="1" <?php echo ($rows['pens_tax'] == 1) ? 'selected' : ''; ?>><?php echo _('TAX'); ?></option>
                                    <option value="2" <?php echo ($rows['pens_tax'] == 2) ? 'selected' : ''; ?>><?php echo _('Pension'); ?></option>
                                    <option value="3" <?php echo ($rows['pens_tax'] == 3) ? 'selected' : ''; ?>><?php echo _('NON-cash'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Registration No.'); ?></label>
                                <input type="text" name="membership_no" class="sp-input" value="<?php echo $rows['membership_no']; ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="sp-row">
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Has Table'); ?></label>
                                <select name="hastable" class="sp-select">
                                    <option value="0" <?php echo ($rows['hastable'] == 0) ? 'selected' : ''; ?>><?php echo _('No'); ?></option>
                                    <option value="1" <?php echo ($rows['hastable'] == 1) ? 'selected' : ''; ?>><?php echo _('Graduated'); ?></option>
                                    <option value="2" <?php echo ($rows['hastable'] == 2) ? 'selected' : ''; ?>><?php echo _('Percentage'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Employer Factor'); ?></label>
                                <input type="number" name="employerfactor" class="sp-input" value="<?php echo $rows['employerfactor']; ?>" step="0.01" />
                            </div>
                        </div>
                    </div>
                    <div class="sp-d-flex sp-gap-2">
                        <button type="submit" name="editproduct" class="sp-btn sp-btn-primary">
                            <i class="fas fa-save"></i> <?php echo _('Update'); ?>
                        </button>
                        <a href="<?php echo $myPage; ?>" class="sp-btn sp-btn-secondary">
                            <i class="fas fa-arrow-left"></i> <?php echo _('Back to List'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; // mode == 'list' ?>
        
        <?php if($mode == 'settings'): ?>
        <?php
        $productItems = FetchProductItems($_GET['settings']);
        $product = GetProduct($_GET['settings']);
        ?>
        <div class="sp-card">
            <div class="sp-card-header">
                <i class="fas fa-percentage"></i>
                <h3><?php echo _('Tax Brackets'); ?>: <?php echo $product['description']; ?></h3>
            </div>
            <div class="sp-card-body">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th><?php echo _('Code'); ?></th>
                            <th><?php echo _('Description'); ?></th>
                            <th><?php echo _('Tax Band'); ?></th>
                            <th><?php echo _('Range From'); ?></th>
                            <th><?php echo _('Range To'); ?></th>
                            <th><?php echo _('Percentage'); ?></th>
                            <th><?php echo _('Ceiling'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($productItems as $item): ?>
                        <tr>
                            <td><?php echo $item['code']; ?></td>
                            <td><?php echo $item['description']; ?></td>
                            <td><a href="<?php echo $myPage; ?>?edittax=<?php echo $item['taxband']; ?>&settings=<?php echo $_GET['settings']; ?>"><?php echo $item['taxband']; ?></a></td>
                            <td><?php echo $item['lowerlimit']; ?></td>
                            <td><?php echo $item['upperlimit']; ?></td>
                            <td><?php echo $item['percentageamount']; ?></td>
                            <td><?php echo $item['ceiling']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if(!isset($_GET['edittax'])): ?>
                <h4 style="margin-top: 20px;"><?php echo _('Add New Tax Band'); ?></h4>
                <form method="post" action="<?php echo $myPage; ?>?settings=<?php echo $_GET['settings']; ?>">
                    <input type="hidden" name="code" value="<?php echo $product['code']; ?>" />
                    <input type="hidden" name="description" value="<?php echo $product['description']; ?>" />
                    <div class="sp-row">
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Tax Band'); ?></label>
                                <input type="text" name="taxband" class="sp-input" required />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Range From'); ?></label>
                                <input type="number" name="rangefrom" class="sp-input" step="0.01" required />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Range To'); ?></label>
                                <input type="number" name="rangeto" class="sp-input" step="0.01" required />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Percentage'); ?></label>
                                <input type="number" name="percentage" class="sp-input" step="0.01" />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Ceiling'); ?></label>
                                <input type="number" name="ceiling" class="sp-input" step="0.01" />
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="createtax1" class="sp-btn sp-btn-primary">
                        <i class="fas fa-save"></i> <?php echo _('Save'); ?>
                    </button>
                </form>
                <?php endif; ?>
                
                <a href="<?php echo $myPage; ?>" class="sp-btn sp-btn-secondary" style="margin-top: 10px;">
                    <i class="fas fa-arrow-left"></i> <?php echo _('Back'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($mode == 'edittax'): ?>
        <?php
        $edtax = GetProductItem($_GET['settings'], $_GET['edittax']);
        ?>
        <div class="sp-card">
            <div class="sp-card-header">
                <i class="fas fa-edit"></i>
                <h3><?php echo _('Edit Tax Band'); ?></h3>
            </div>
            <div class="sp-card-body">
                <form method="post" action="<?php echo $myPage; ?>?settings=<?php echo $_GET['settings']; ?>">
                    <input type="hidden" name="code" value="<?php echo $edtax['code']; ?>" />
                    <input type="hidden" name="description" value="<?php echo $edtax['description']; ?>" />
                    <input type="hidden" name="taxband" value="<?php echo $edtax['taxband']; ?>" />
                    <div class="sp-row">
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Tax Band'); ?></label>
                                <input type="text" name="newtaxband" class="sp-input" value="<?php echo $edtax['taxband']; ?>" required />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Range From'); ?></label>
                                <input type="number" name="rangefrom" class="sp-input" value="<?php echo $edtax['lowerlimit']; ?>" step="0.01" required />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Range To'); ?></label>
                                <input type="number" name="rangeto" class="sp-input" value="<?php echo $edtax['upperlimit']; ?>" step="0.01" required />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Percentage'); ?></label>
                                <input type="number" name="percentage" class="sp-input" value="<?php echo $edtax['percentageamount']; ?>" step="0.01" />
                            </div>
                        </div>
                        <div class="sp-col-2">
                            <div class="sp-form-group">
                                <label class="sp-label"><?php echo _('Ceiling'); ?></label>
                                <input type="number" name="ceiling" class="sp-input" value="<?php echo $edtax['ceiling']; ?>" step="0.01" />
                            </div>
                        </div>
                    </div>
                    <div class="sp-d-flex sp-gap-2">
                        <button type="submit" name="edittax1" class="sp-btn sp-btn-primary">
                            <i class="fas fa-save"></i> <?php echo _('Update'); ?>
                        </button>
                        <button type="submit" name="deletetax1" class="sp-btn sp-btn-danger" onclick="return confirm('<?php echo _('Are you sure?'); ?>');">
                            <i class="fas fa-trash"></i> <?php echo _('Delete'); ?>
                        </button>
                    </div>
                </form>
                <a href="<?php echo $myPage; ?>?settings=<?php echo $_GET['settings']; ?>" class="sp-btn sp-btn-secondary" style="margin-top: 10px;">
                    <i class="fas fa-arrow-left"></i> <?php echo _('Back'); ?>
                </a>
            </div>
        </div>
        <?php endif; // mode == 'edittax' ?>

        <?php endif; // if($mode == 'list') / else ?>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
