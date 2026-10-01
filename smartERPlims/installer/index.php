<?php
/**
 * SmartERP Installation Wizard
 * Setup configuration and database parameters
 */

// Get the root path
$rootPath = dirname(dirname(__FILE__));
$configFile = $rootPath . '/config.php';

// Check if config.php already exists
$configExists = file_exists($configFile);
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
    
    if ($step == 2) {
        // Validate step 1 inputs
        $serverName = isset($_POST['serverName']) ? trim($_POST['serverName']) : '';
        $dbName = isset($_POST['dbName']) ? trim($_POST['dbName']) : '';
        $dbUser = isset($_POST['dbUser']) ? trim($_POST['dbUser']) : '';
        $dbPassword = isset($_POST['dbPassword']) ? trim($_POST['dbPassword']) : '';
        $dbPort = isset($_POST['dbPort']) ? (int)$_POST['dbPort'] : 3306;
        
        if (empty($serverName)) $errors[] = 'Database server is required';
        if (empty($dbName)) $errors[] = 'Database name is required';
        if (empty($dbUser)) $errors[] = 'Database user is required';
        
        if (empty($errors)) {
            // Try to connect to verify credentials
            try {
                $testConn = new mysqli($serverName, $dbUser, $dbPassword, $dbName, $dbPort);
                if ($testConn->connect_error) {
                    $errors[] = 'Connection failed: ' . $testConn->connect_error;
                } else {
                    $testConn->close();
                    $_POST['connectionValid'] = true;
                }
            } catch (Exception $e) {
                $errors[] = 'Could not test connection: ' . $e->getMessage();
            }
        }
    }
    
    if ($step == 3 && empty($errors)) {
        // Generate and save config.php
        $appName = isset($_POST['appName']) ? trim($_POST['appName']) : 'SmartERP';
        $companyName = isset($_POST['companyName']) ? trim($_POST['companyName']) : '';
        
        $config = generateConfigFile(
            $_POST['serverName'],
            $_POST['dbName'],
            $_POST['dbUser'],
            $_POST['dbPassword'],
            (int)$_POST['dbPort'],
            $appName,
            $companyName,
            $rootPath
        );
        
        if (file_put_contents($configFile, $config)) {
            chmod($configFile, 0644);
            $success = true;
            $configExists = true;
        } else {
            $errors[] = 'Failed to write config.php. Check write permissions.';
        }
    }
}

function generateConfigFile($host, $dbName, $user, $pass, $port, $appName, $companyName, $rootPath) {
    $timestamp = date('Y-m-d H:i:s');
    
    return <<<PHP
<?php
/**
 * SmartERP Configuration File
 * Auto-generated on {$timestamp}
 * 
 * WARNING: Keep this file secure!
 * - Never commit to version control
 * - Never expose database credentials
 * - Set proper file permissions (0644)
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================

// Database type: 'mysqli' or 'mssql'
\$DBType = 'mysqli';

// Database server/host
\$host = '{$host}';

// Database port (default: 3306 for MySQL)
\$DBPort = {$port};

// Database name
\$dbname = '{$dbName}';

// Database username
\$DBUser = '{$user}';

// Database password
\$DBPassword = '{$pass}';

// ============================================
// APPLICATION CONFIGURATION
// ============================================

// Application name
\$appName = '{$appName}';

// Company name (displayed in reports and headers)
\$companyName = '{$companyName}';

// Application version
\$appVersion = '2.2';

// Root path (auto-detected)
\$RootPath = dirname(__FILE__);

// ============================================
// SYSTEM SETTINGS
// ============================================

// Session timeout (in minutes)
\$sessionTimeout = 60;

// Enable debug mode (set to false in production)
\$debugMode = false;

// Max file upload size (in MB)
\$maxFileUpload = 50;

// ============================================
// SECURITY SETTINGS
// ============================================

// Enable HTTPS enforcement
\$forceHTTPS = false;

// CORS enabled domains (comma-separated)
\$allowedDomains = '*';

// Enable API authentication
\$apiAuthRequired = true;

// ============================================
// LOGGING
// ============================================

// Log file location
\$logDir = dirname(__FILE__) . '/logs';

// Log errors to file
\$logErrors = true;

// Log database queries (development only)
\$logQueries = false;

// ============================================
// EMAIL CONFIGURATION (Optional)
// ============================================

// SMTP server
\$smtpServer = 'localhost';
\$smtpPort = 587;
\$smtpUser = '';
\$smtpPassword = '';
\$smtpFromEmail = 'noreply@smarterp.com';

// ============================================
// DATE & TIME
// ============================================

// Timezone (PHP timezone identifier)
\$timezone = 'UTC';

// Date format (PHP date format)
\$dateFormat = 'd/m/Y';
\$timeFormat = 'H:i:s';

// ============================================
// CURRENCY
// ============================================

// Default currency code (ISO 4217)
\$defaultCurrency = 'USD';

// Currency symbol
\$currencySymbol = '\$';

// Decimal places for currency
\$currencyDecimals = 2;

// ============================================
// FEATURES
// ============================================

// Enable fixed asset management
\$enableFixedAssets = true;

// Enable inventory management
\$enableInventory = true;

// Enable CRM module
\$enableCRM = true;

// Enable HR module
\$enableHR = true;

// Enable laboratory module
\$enableLaboratory = false;

// ============================================
// DO NOT MODIFY BELOW THIS LINE
// ============================================

if (!defined('APP_INITIALIZED')) {
    define('APP_INITIALIZED', true);
    
    // Ensure timezone is set
    if (!empty(\$timezone)) {
        @date_default_timezone_set(\$timezone);
    }
    
    // Create logs directory if it doesn't exist
    if (\$logErrors && !is_dir(\$logDir)) {
        @mkdir(\$logDir, 0755, true);
    }
}
?>
PHP;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartERP Installation Wizard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .wizard-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .wizard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .wizard-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .wizard-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .wizard-content {
            padding: 40px 30px;
        }
        
        .progress-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 40px;
        }
        
        .progress-step {
            flex: 1;
            height: 4px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-step.active {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .progress-step.completed {
            background: #10b981;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group small {
            display: block;
            color: #6b7280;
            margin-top: 6px;
            font-size: 12px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #0c2d6b;
            border: 1px solid #bfdbfe;
        }
        
        .alert ul {
            margin-left: 20px;
            margin-top: 8px;
        }
        
        .alert li {
            margin-bottom: 4px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        
        button {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
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
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #1f2937;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .config-exists {
            text-align: center;
            padding: 40px 30px;
        }
        
        .config-exists h2 {
            color: #10b981;
            margin-bottom: 16px;
            font-size: 24px;
        }
        
        .config-exists p {
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .success-message {
            text-align: center;
            padding: 40px;
        }
        
        .success-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .success-message h2 {
            color: #10b981;
            margin-bottom: 12px;
            font-size: 24px;
        }
        
        .success-message p {
            color: #6b7280;
            margin-bottom: 24px;
        }
        
        .code-block {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            margin: 12px 0;
            color: #1f2937;
        }
        
        @media (max-width: 600px) {
            .wizard-header {
                padding: 30px 20px;
            }
            
            .wizard-header h1 {
                font-size: 22px;
            }
            
            .wizard-content {
                padding: 30px 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column-reverse;
            }
            
            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <?php if ($success): ?>
            <div class="wizard-header">
                <h1>Installation Complete!</h1>
                <p>Your system is ready to use</p>
            </div>
            <div class="wizard-content">
                <div class="success-message">
                    <div class="success-icon">✓</div>
                    <h2>Configuration Saved</h2>
                    <p>Your config.php file has been successfully created and configured.</p>
                    <p style="font-size: 13px; color: #9ca3af; margin-bottom: 32px;">
                        You can now access the application. It's recommended to:
                    </p>
                    <div class="code-block" style="text-align: left;">
                        1. Delete or rename the /installer directory for security<br>
                        2. Keep database credentials secure<br>
                        3. Set config.php permissions to 0644
                    </div>
                    <div class="button-group">
                        <a href="../" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">
                            <button class="btn-primary" style="margin: 0;">Go to Application →</button>
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif ($configExists && $step == 1): ?>
            <div class="wizard-header">
                <h1>SmartERP Installer</h1>
                <p>Configuration already exists</p>
            </div>
            <div class="wizard-content">
                <div class="config-exists">
                    <h2>✓ Configuration Found</h2>
                    <p>The config.php file already exists and appears to be configured.</p>
                    <p style="font-size: 13px; margin-bottom: 32px;">
                        If you need to reconfigure your database connection, you can delete or rename config.php and run the wizard again.
                    </p>
                    <div class="button-group">
                        <a href="../" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">
                            <button class="btn-primary" style="margin: 0;">Go to Application →</button>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="wizard-header">
                <h1>SmartERP Installer</h1>
                <p>Setup your database connection</p>
            </div>
            <div class="wizard-content">
                <!-- Progress Bar -->
                <div class="progress-bar">
                    <div class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?>"></div>
                    <div class="progress-step <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
                    <div class="progress-step <?php echo $step >= 3 ? 'active' : ''; ?>"></div>
                </div>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Step 1: Database Configuration -->
                <?php if ($step <= 1): ?>
                    <form method="POST">
                        <input type="hidden" name="step" value="2">
                        
                        <h2 style="font-size: 20px; margin-bottom: 24px; color: #1f2937;">Database Configuration</h2>
                        
                        <div class="form-group">
                            <label for="serverName">Database Server *</label>
                            <input type="text" id="serverName" name="serverName" 
                                   value="<?php echo isset($_POST['serverName']) ? htmlspecialchars($_POST['serverName']) : 'localhost'; ?>" 
                                   required>
                            <small>e.g., localhost, 192.168.1.100, or db.example.com</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dbPort">Port</label>
                                <input type="number" id="dbPort" name="dbPort" 
                                       value="<?php echo isset($_POST['dbPort']) ? htmlspecialchars($_POST['dbPort']) : '3306'; ?>" 
                                       min="1" max="65535">
                                <small>Default: 3306</small>
                            </div>
                            <div class="form-group">
                                <label for="dbName">Database Name *</label>
                                <input type="text" id="dbName" name="dbName" 
                                       value="<?php echo isset($_POST['dbName']) ? htmlspecialchars($_POST['dbName']) : ''; ?>" 
                                       required>
                                <small>Database to use</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dbUser">Username *</label>
                                <input type="text" id="dbUser" name="dbUser" 
                                       value="<?php echo isset($_POST['dbUser']) ? htmlspecialchars($_POST['dbUser']) : ''; ?>" 
                                       required>
                                <small>Database user</small>
                            </div>
                            <div class="form-group">
                                <label for="dbPassword">Password</label>
                                <input type="password" id="dbPassword" name="dbPassword" 
                                       value="<?php echo isset($_POST['dbPassword']) ? htmlspecialchars($_POST['dbPassword']) : ''; ?>">
                                <small>Leave empty if no password</small>
                            </div>
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" class="btn-primary">Continue →</button>
                        </div>
                    </form>
                
                <!-- Step 2: Verify Connection & App Settings -->
                <?php elseif ($step == 2): ?>
                    <form method="POST">
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="serverName" value="<?php echo htmlspecialchars($_POST['serverName']); ?>">
                        <input type="hidden" name="dbPort" value="<?php echo htmlspecialchars($_POST['dbPort']); ?>">
                        <input type="hidden" name="dbName" value="<?php echo htmlspecialchars($_POST['dbName']); ?>">
                        <input type="hidden" name="dbUser" value="<?php echo htmlspecialchars($_POST['dbUser']); ?>">
                        <input type="hidden" name="dbPassword" value="<?php echo htmlspecialchars($_POST['dbPassword']); ?>">
                        
                        <?php if (isset($_POST['connectionValid'])): ?>
                            <div class="alert alert-success">
                                ✓ Database connection successful!
                            </div>
                        <?php endif; ?>
                        
                        <h2 style="font-size: 20px; margin-bottom: 24px; color: #1f2937;">Application Settings</h2>
                        
                        <div class="form-group">
                            <label for="appName">Application Name</label>
                            <input type="text" id="appName" name="appName" 
                                   value="<?php echo isset($_POST['appName']) ? htmlspecialchars($_POST['appName']) : 'SmartERP'; ?>">
                            <small>Display name for the application</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="companyName">Company Name</label>
                            <input type="text" id="companyName" name="companyName" 
                                   value="<?php echo isset($_POST['companyName']) ? htmlspecialchars($_POST['companyName']) : ''; ?>">
                            <small>Your company or organization name</small>
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" formaction="?step=1" class="btn-secondary" formnovalidate>← Back</button>
                            <button type="submit" class="btn-primary">Finish →</button>
                        </div>
                    </form>
                
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
