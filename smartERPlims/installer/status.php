<?php
/**
 * SmartERP Installation Status Page
 * Shows system requirements and configuration status
 */

require_once 'checker.php';

$checker = new InstallerChecker();
$report = $checker->getStatusReport();
$configExists = $report['config_exists'];
$configValid = $report['config_valid'];
$isReady = $checker->isReady();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartERP Installation Status</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .status-card h3 {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-icon {
            font-size: 20px;
            display: flex;
            align-items: center;
            width: 24px;
        }
        
        .status-icon.success {
            color: #10b981;
        }
        
        .status-icon.error {
            color: #ef4444;
        }
        
        .status-icon.warning {
            color: #f59e0b;
        }
        
        .status-value {
            font-size: 14px;
            color: #6b7280;
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 3px solid #e5e7eb;
        }
        
        .status-value.success {
            background: #dcfce7;
            border-left-color: #10b981;
            color: #166534;
        }
        
        .status-value.error {
            background: #fee2e2;
            border-left-color: #ef4444;
            color: #991b1b;
        }
        
        .status-value.warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
            color: #92400e;
        }
        
        .details-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .details-section h2 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .detail-value {
            color: #1f2937;
            font-weight: 500;
        }
        
        .requirement-list {
            list-style: none;
            margin: 12px 0;
        }
        
        .requirement-list li {
            padding: 10px 0;
            padding-left: 28px;
            position: relative;
            color: #4b5563;
            font-size: 14px;
        }
        
        .requirement-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        .requirement-list li.missing::before {
            content: '✕';
            color: #ef4444;
        }
        
        .requirement-list li.missing {
            color: #991b1b;
        }
        
        .requirement-list li.warning::before {
            content: '⚠';
            color: #f59e0b;
        }
        
        .requirement-list li.warning {
            color: #92400e;
        }
        
        .action-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .action-bar a {
            text-decoration: none;
        }
        
        button, .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #1f2937;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .alert {
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-color: #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-color: #f59e0b;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #0c2d6b;
            border-color: #3b82f6;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 24px;
            }
            
            .status-cards {
                grid-template-columns: 1fr;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            button, .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SmartERP Installation Status</h1>
            <p>System requirements and configuration check</p>
        </div>
        
        <!-- Status Overview Cards -->
        <div class="status-cards">
            <div class="status-card">
                <h3>
                    <span class="status-icon <?php echo $configValid ? 'success' : 'error'; ?>">
                        <?php echo $configValid ? '✓' : '✕'; ?>
                    </span>
                    Configuration
                </h3>
                <div class="status-value <?php echo $configValid ? 'success' : 'error'; ?>">
                    <?php echo $configValid ? 'Configured' : 'Not Configured'; ?>
                </div>
            </div>
            
            <div class="status-card">
                <h3>
                    <span class="status-icon <?php echo $report['php_version']['status'] ? 'success' : 'error'; ?>">
                        <?php echo $report['php_version']['status'] ? '✓' : '✕'; ?>
                    </span>
                    PHP Version
                </h3>
                <div class="status-value <?php echo $report['php_version']['status'] ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($report['php_version']['current']); ?> 
                    (≥ <?php echo htmlspecialchars($report['php_version']['required']); ?>)
                </div>
            </div>
            
            <div class="status-card">
                <h3>
                    <span class="status-icon <?php echo $report['extensions']['status'] ? 'success' : 'error'; ?>">
                        <?php echo $report['extensions']['status'] ? '✓' : '✕'; ?>
                    </span>
                    Extensions
                </h3>
                <div class="status-value <?php echo $report['extensions']['status'] ? 'success' : 'error'; ?>">
                    <?php 
                    if ($report['extensions']['status']) {
                        echo 'All required';
                    } else {
                        echo count($report['extensions']['missing']) . ' missing';
                    }
                    ?>
                </div>
            </div>
            
            <div class="status-card">
                <h3>
                    <span class="status-icon <?php echo $report['writable_dirs']['status'] ? 'success' : 'error'; ?>">
                        <?php echo $report['writable_dirs']['status'] ? '✓' : '✕'; ?>
                    </span>
                    Directories
                </h3>
                <div class="status-value <?php echo $report['writable_dirs']['status'] ? 'success' : 'error'; ?>">
                    <?php echo $report['writable_dirs']['status'] ? 'Writable' : 'Permission issues'; ?>
                </div>
            </div>
        </div>
        
        <!-- Action Bar -->
        <div class="action-bar">
            <?php if (!$configValid): ?>
                <a href="index.php" class="btn btn-primary">Configure Now →</a>
            <?php endif; ?>
            <a href="../" class="btn btn-secondary">Go to Application</a>
        </div>
        
        <!-- Configuration Status -->
        <?php if ($configValid): ?>
            <div class="alert alert-success">
                ✓ <strong>Configuration Valid</strong> - Your system is properly configured and ready to use.
            </div>
        <?php elseif ($configExists): ?>
            <div class="alert alert-warning">
                ⚠ <strong>Configuration Found</strong> - However, it may not be valid. Please review the configuration.
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                ✕ <strong>Configuration Missing</strong> - Please run the installer to configure your database connection.
            </div>
        <?php endif; ?>
        
        <!-- Detailed Requirements -->
        <div class="details-section">
            <h2>System Requirements</h2>
            <ul class="requirement-list">
                <li <?php echo $report['php_version']['status'] ? '' : 'class="missing"'; ?>>
                    PHP <?php echo htmlspecialchars($report['php_version']['required']); ?>+ 
                    (Current: <?php echo htmlspecialchars($report['php_version']['current']); ?>)
                </li>
                <li>
                    Required PHP Extensions:
                </li>
            </ul>
            <ul class="requirement-list" style="margin-left: 28px;">
                <?php foreach ($report['extensions']['missing'] as $ext): ?>
                    <li class="missing">
                        <?php echo htmlspecialchars($ext); ?>
                    </li>
                <?php endforeach; ?>
                <?php 
                $loadedExt = array_diff(['mysqli', 'json', 'curl', 'gd', 'fileinfo'], $report['extensions']['missing']);
                foreach ($loadedExt as $ext): 
                ?>
                    <li><?php echo htmlspecialchars($ext); ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!$report['disabled_functions']['status']): ?>
                <ul class="requirement-list">
                    <li class="warning">
                        Disabled Functions: <?php echo implode(', ', $report['disabled_functions']['disabled']); ?>
                    </li>
                </ul>
            <?php endif; ?>
            
            <ul class="requirement-list">
                <li <?php echo $report['writable_dirs']['status'] ? '' : 'class="missing"'; ?>>
                    Writable Directories:
                </li>
            </ul>
            <ul class="requirement-list" style="margin-left: 28px;">
                <?php foreach ($report['writable_dirs']['notWritable'] as $dir): ?>
                    <li class="missing"><?php echo htmlspecialchars($dir); ?> - Not writable</li>
                <?php endforeach; ?>
                <?php foreach ($report['writable_dirs']['writable'] as $dir): ?>
                    <li><?php echo htmlspecialchars($dir); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- System Information -->
        <div class="details-section">
            <h2>System Information</h2>
            <div class="detail-item">
                <span class="detail-label">Operating System</span>
                <span class="detail-value"><?php echo htmlspecialchars($report['system_info']['os']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Web Server</span>
                <span class="detail-value"><?php echo htmlspecialchars($report['system_info']['web_server']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">PHP Version</span>
                <span class="detail-value"><?php echo htmlspecialchars($report['system_info']['php_version']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Memory Limit</span>
                <span class="detail-value"><?php echo htmlspecialchars($report['system_info']['memory_limit']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Max Upload Size</span>
                <span class="detail-value"><?php echo htmlspecialchars($report['system_info']['max_upload_size']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Max Execution Time</span>
                <span class="detail-value"><?php echo htmlspecialchars($report['system_info']['max_execution_time']); ?>s</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Timezone</span>
                <span class="detail-value"><?php echo htmlspecialchars($report['system_info']['timezone']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">MySQL Available</span>
                <span class="detail-value">
                    <?php echo $report['mysql_available'] ? 
                        '<span style="color: #10b981;">Yes</span>' : 
                        '<span style="color: #ef4444;">No</span>'; 
                    ?>
                </span>
            </div>
        </div>
        
        <!-- Overall Status -->
        <div class="details-section">
            <h2>Installation Status</h2>
            <?php if ($isReady && $configValid): ?>
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 48px; margin-bottom: 12px;">✓</div>
                    <h3 style="color: #10b981; font-size: 20px; margin-bottom: 8px;">Ready for Production</h3>
                    <p style="color: #6b7280;">Your system meets all requirements and is properly configured.</p>
                </div>
            <?php elseif ($isReady): ?>
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 48px; margin-bottom: 12px;">⚙️</div>
                    <h3 style="color: #f59e0b; font-size: 20px; margin-bottom: 8px;">Ready to Configure</h3>
                    <p style="color: #6b7280;">System requirements are met. Please run the installer to configure your database.</p>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 48px; margin-bottom: 12px;">⚠️</div>
                    <h3 style="color: #ef4444; font-size: 20px; margin-bottom: 8px;">System Issues Detected</h3>
                    <p style="color: #6b7280;">Please resolve the issues above before proceeding with installation.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
