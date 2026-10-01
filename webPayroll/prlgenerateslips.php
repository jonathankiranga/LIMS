<?php
include('includes/session.inc');
$Title = _('Calculate Payroll Transactions');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');
include('ExtFunc/gensalary.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if(isset($_GET['Gid'])){
    generatepayroll();
    unset($_GET['Gid']);
}

Reademployeefile();

$periods = GetPayrollPeriods();
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-calculator"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('Generate payroll for the selected period'); ?></p>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-alert sp-alert-info">
            <i class="fas fa-info-circle fa-lg"></i>
            <div>
                <strong><?php echo _('How to Process Payroll:'); ?></strong>
                <ul style="margin: 8px 0 0; padding-left: 20px;">
                    <li><?php echo _('Select a payroll period below'); ?></li>
                    <li><?php echo _('Click "Calculate Payroll" to process'); ?></li>
                    <li><?php echo _('Review and confirm before generating payslips'); ?></li>
                </ul>
            </div>
        </div>

        <div class="sp-card">
            <div class="sp-card-header">
                <i class="fas fa-calendar-alt"></i>
                <h3><?php echo _('Available Payroll Periods'); ?></h3>
            </div>
            <div class="sp-card-body sp-p-0">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th><?php echo _('Start Date'); ?></th>
                            <th><?php echo _('End Date'); ?></th>
                            <th><?php echo _('Status'); ?></th>
                            <th style="width: 180px;"><?php echo _('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(count($periods) == 0):
                            echo '<tr><td colspan="4" class="sp-text-center sp-text-muted">';
                            echo '<i class="fas fa-calendar-times" style="font-size:32px; display:block; margin-bottom:10px;"></i>';
                            echo _('No payroll periods available'); 
                            echo '</td></tr>';
                        endif;
                        
                        foreach($periods as $row):
                            $isOpen = $row['open'] == 1;
                        ?>
                        <tr>
                            <td><strong><?php echo ConvertSQLDate($row['fromdate']); ?></strong></td>
                            <td><strong><?php echo ConvertSQLDate($row['todate']); ?></strong></td>
                            <td>
                                <?php if($isOpen): ?>
                                    <span class="sp-badge sp-badge-success">
                                        <i class="fas fa-lock-open"></i> <?php echo _('Open'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="sp-badge sp-badge-secondary">
                                        <i class="fas fa-lock"></i> <?php echo _('Closed'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo $self; ?>?Gid=<?php echo $row['pkey']; ?>" 
                                   class="sp-btn sp-btn-primary sp-btn-sm">
                                    <i class="fas fa-calculator"></i> <?php echo _('Calculate Payroll'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sp-card" style="margin-top: var(--sp-spacing-md);">
            <div class="sp-card-header">
                <i class="fas fa-info-circle"></i>
                <h3><?php echo _('Quick Links'); ?></h3>
            </div>
            <div class="sp-card-body">
                <div class="sp-row">
                    <div class="sp-col-3">
                        <a href="prlmatrixscript.php" class="sp-btn sp-btn-outline sp-btn-block">
                            <i class="fas fa-cogs"></i> <?php echo _('Payroll Matrix'); ?>
                        </a>
                    </div>
                    <div class="sp-col-3">
                        <a href="prlmasterroll.php" class="sp-btn sp-btn-outline sp-btn-block">
                            <i class="fas fa-file-invoice-dollar"></i> <?php echo _('Generate Payslips'); ?>
                        </a>
                    </div>
                    <div class="sp-col-3">
                        <a href="Employeenetpay.php" class="sp-btn sp-btn-outline sp-btn-block">
                            <i class="fas fa-university"></i> <?php echo _('Bank Transfers'); ?>
                        </a>
                    </div>
                    <div class="sp-col-3">
                        <a href="prlpaye.php" class="sp-btn sp-btn-outline sp-btn-block">
                            <i class="fas fa-file-contract"></i> <?php echo _('PAYE Reports'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
