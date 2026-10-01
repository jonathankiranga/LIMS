<?php
$PageSecurity = 0;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$SQL = "SELECT MONTH(fromdate) AS todate, YEAR(todate) AS todate FROM prlmrollperiods WHERE open = 1";
$ResultIndex = DB_query($SQL, $db);
$defaultdate = DB_fetch_row($ResultIndex);
$UseDate = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, $defaultdate[0], 1, $defaultdate[1]));
$DateEntry = FormatDateForSQL($UseDate);

$quickActions = array(
    array(
        'title' => 'Process Payroll',
        'desc' => 'Generate and process monthly payroll',
        'icon' => 'fa-calculator',
        'color' => '#0d6efd',
        'link' => 'prlgenerateslips.php',
        'fa_class' => 'bg-primary'
    ),
    array(
        'title' => 'Generate Payslips',
        'desc' => 'Create and print employee payslips',
        'icon' => 'fa-file-invoice-dollar',
        'color' => '#6c757d',
        'link' => 'prlmasterroll.php',
        'fa_class' => 'bg-secondary'
    ),
    array(
        'title' => 'Tax Reports',
        'desc' => 'Submit P9/P10 PAYE reports',
        'icon' => 'fa-file-contract',
        'color' => '#dc3545',
        'link' => 'prlpaye.php',
        'fa_class' => 'bg-danger'
    ),
    array(
        'title' => 'Leave Approvals',
        'desc' => 'Review and approve leave requests',
        'icon' => 'fa-calendar-check',
        'color' => '#198754',
        'link' => 'prlleaveapproval.php',
        'fa_class' => 'bg-success'
    ),
    array(
        'title' => 'Employee Records',
        'desc' => 'Manage employee information',
        'icon' => 'fa-users',
        'color' => '#0dcaf0',
        'link' => 'prlhrlistview.php',
        'fa_class' => 'bg-info'
    ),
    array(
        'title' => 'Bank Transfers',
        'desc' => 'Export payroll to banks',
        'icon' => 'fa-building-columns',
        'color' => '#fd7e14',
        'link' => 'Employeenetpay.php',
        'fa_class' => 'bg-warning'
    ),
    array(
        'title' => 'NHIF Reports',
        'desc' => 'NHIF compliance reports',
        'icon' => 'fa-hospital',
        'color' => '#20c997',
        'link' => 'prlnhif.php',
        'fa_class' => 'bg-teal'
    ),
    array(
        'title' => 'NSSF Reports',
        'desc' => 'NSSF compliance reports',
        'icon' => 'fa-shield-halved',
        'color' => '#6610f2',
        'link' => 'prlnssf.php',
        'fa_class' => 'bg-purple'
    )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Payroll</title>
    <link rel="stylesheet" href="<?php echo $RootPath; ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $RootPath; ?>/css/fontawesome6.4.0.all.min.css">
    <style>
        :root {
            --card-radius: 12px;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
            --shadow-hover: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            padding: 24px;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .dashboard-header h1 {
            color: #1a1a2e;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .dashboard-header p {
            color: #6c757d;
            font-size: 16px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .action-card {
            background: #fff;
            border-radius: var(--card-radius);
            padding: 24px;
            text-decoration: none;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
            transition: height 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }
        
        .action-card:hover::before {
            height: 6px;
        }
        
        .action-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 28px;
            color: #fff;
            transition: transform 0.3s ease;
        }
        
        .action-card:hover .action-icon {
            transform: scale(1.1);
        }
        
        .action-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .action-desc {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            max-width: 1200px;
            margin: 0 auto 32px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #fff;
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1;
        }
        
        .stat-info p {
            margin: 4px 0 0;
            font-size: 13px;
            color: #6c757d;
        }
        
        .bg-teal { background: #20c997; }
        .bg-purple { background: #6610f2; }
        
        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            body {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1><i class="fa fa-coins"></i> Smart Payroll Dashboard</h1>
        <p>Welcome back! Here are your quick actions.</p>
    </div>
    
    <div class="quick-actions">
        <?php foreach ($quickActions as $action): ?>
        <a href="<?php echo $RootPath; ?>/<?php echo $action['link']; ?>" class="action-card">
            <div class="action-icon <?php echo $action['fa_class']; ?>" style="background: <?php echo $action['color']; ?>;">
                <i class="fas <?php echo $action['icon']; ?>"></i>
            </div>
            <div class="action-title"><?php echo $action['title']; ?></div>
            <div class="action-desc"><?php echo $action['desc']; ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</body>
</html>
