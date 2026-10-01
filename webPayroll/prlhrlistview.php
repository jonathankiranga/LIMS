<?php
include('includes/session.inc');
$Title = _('Employee List');
include('includes/header.inc');
include('ExtFunc/Payrollfunctions.php');
include('ExtFunc/humanresource.inc');
include('ExtFunc/employeetypes.inc');
include('ExtFunc/salary.inc');

echo '<link rel="stylesheet" href="' . $RootPath . '/css/smartpayroll.css">';

$customheader = getcustom();
$self = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$editPage = htmlspecialchars('prlhr.php', ENT_QUOTES, 'UTF-8');

$employeeCount = GetEmployeeCount();
$employees = GetEmployees();
?>

<div class="sp-page">
    <div class="sp-header">
        <div class="sp-header-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="sp-header-title">
            <h1><?php echo $Title; ?></h1>
            <p><?php echo _('View and manage employee records'); ?></p>
        </div>
        <div>
            <a href="prlhr.php" class="sp-btn sp-btn-success">
                <i class="fas fa-user-plus"></i> <?php echo _('Add New Employee'); ?>
            </a>
        </div>
    </div>

    <div class="sp-content">
        <div class="sp-card">
            <div class="sp-card-header">
                <i class="fas fa-list"></i>
                <h3><?php echo _('Employee Records'); ?></h3>
                <div style="margin-left: auto;">
                    <span class="sp-badge sp-badge-primary" style="font-size: 14px;">
                        <?php echo $employeeCount . ' ' . _('Active Employees'); ?>
                    </span>
                </div>
            </div>
            <div class="sp-card-body sp-p-0">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><?php echo _('#'); ?></th>
                            <th><?php echo _('Status'); ?></th>
                            <th><?php echo _('PayRoll ID'); ?></th>
                            <th><?php echo _('Names'); ?></th>
                            <th><?php echo _('Department'); ?></th>
                            <th><?php echo _('Position'); ?></th>
                            <th><?php echo _('Employment'); ?></th>
                            <th><?php echo _('Contact'); ?></th>
                            <th><?php echo _('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $k = 0;
                        $rowNum = 1;
                        
                        if(count($employees) == 0):
                            echo '<tr><td colspan="9" class="sp-text-center sp-text-muted">';
                            echo '<i class="fas fa-users" style="font-size: 48px; display: block; margin: 20px 0;"></i>';
                            echo _('No employees found');
                            echo '</td></tr>';
                        endif;
                        
                        foreach($employees as $row):
                            $isActive = ($row['Inactive'] == 0 || $row['Inactive'] == null);
                            $fullName = trim($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']);
                            $empType = $employment[$row['freqcode']] ?? $row['freqcode'];
                            
                            $deptName = $row['department_name'] ?? '-';
                            $posName = $row['position_name'] ?? '-';
                            
                            $rowClass = ($k % 2 == 0) ? '' : '';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><?php echo $rowNum++; ?></td>
                            <td>
                                <?php if($isActive): ?>
                                    <span class="sp-badge sp-badge-success">
                                        <i class="fas fa-check"></i> <?php echo _('Active'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="sp-badge sp-badge-secondary">
                                        <i class="fas fa-pause"></i> <?php echo _('Inactive'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $row['pf_no']; ?></strong></td>
                            <td>
                                <i class="fas fa-user" style="color: var(--sp-primary); margin-right: 8px;"></i>
                                <?php echo htmlspecialchars($fullName); ?>
                            </td>
                            <td>
                                <i class="fas fa-building" style="color: var(--sp-info); margin-right: 8px;"></i>
                                <?php echo htmlspecialchars($deptName); ?>
                            </td>
                            <td><?php echo htmlspecialchars($posName); ?></td>
                            <td>
                                <span class="sp-badge <?php echo ($row['freqcode'] == 1) ? 'sp-badge-primary' : 'sp-badge-info'; ?>">
                                    <?php echo $empType; ?>
                                </span>
                            </td>
                            <td>
                                <?php if(!empty($row['telno'])): ?>
                                    <i class="fas fa-phone" style="color: var(--sp-success);"></i> <?php echo $row['telno']; ?><br>
                                <?php endif; ?>
                                <?php if(!empty($row['email'])): ?>
                                    <i class="fas fa-envelope" style="color: var(--sp-warning);"></i> 
                                    <small><?php echo htmlspecialchars(substr($row['email'], 0, 20)) . (strlen($row['email']) > 20 ? '...' : ''); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo $editPage; ?>?pfno=<?php echo $row['pf_no']; ?>" 
                                   class="sp-btn sp-btn-primary sp-btn-sm" title="<?php echo _('View/Edit'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo $editPage; ?>?editid=<?php echo $row['pf_no']; ?>" 
                                   class="sp-btn sp-btn-warning sp-btn-sm" title="<?php echo _('Edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                        $k = 1 - $k;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.inc'); ?>
