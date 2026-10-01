# SmartERP Installation Wizard

The SmartERP Installation Wizard helps you configure the system automatically without manually editing configuration files.

## Quick Start

### 1. **Access the Installer**
Visit: `http://your-server/smartERPv.2.2 - Copy/installer/`

Two options available:
- **`index.php`** - Interactive wizard to configure database and application settings
- **`status.php`** - System requirements checker and installation status

### 2. **Run the Wizard**

The wizard guides you through 3 steps:

#### Step 1: Database Configuration
- **Server**: MySQL/MariaDB host (e.g., localhost, 192.168.1.100)
- **Port**: Database port (default: 3306)
- **Database Name**: Name of the database to use
- **Username**: Database user with access to the database
- **Password**: Database password (optional if no password)

#### Step 2: Verify Connection & App Settings
- Connection is automatically tested
- Enter your **Application Name** (e.g., SmartERP)
- Enter your **Company Name** (for reports and headers)

#### Step 3: Configuration Saved
- `config.php` is automatically generated
- File is saved with proper permissions
- You can now use the application

### 3. **Clean Up (Important for Security)**

After successful installation:
```bash
# Option 1: Delete the installer directory
rm -rf installer/

# Option 2: Restrict access via .htaccess
<Directory /path/to/installer>
    Order Allow,Deny
    Deny from all
</Directory>

# Option 3: Rename the directory
mv installer/ installer-backup/
```

---

## System Requirements

The installer checks for:

### PHP Requirements
- PHP 7.2 or higher
- Required Extensions:
  - `mysqli` - MySQL connection
  - `json` - JSON support
  - `curl` - HTTP requests
  - `gd` - Image processing
  - `fileinfo` - File type detection

### Directory Permissions
These directories must be writable:
- `/logs` - Application logs
- `/uploads` - File uploads
- `/cache` - Cache files

### Database
- MySQL 5.7+ or MariaDB 10.3+
- A fresh database (recommended)
- User with full permissions to the database

---

## Installation Status Checker

Visit `installer/status.php` to check:

✓ System requirements compliance
✓ PHP version and extensions
✓ Directory write permissions
✓ Configuration validity
✓ System information

---

## Config.php File

The installer generates a complete `config.php` with:

### Database Settings
```php
$DBType = 'mysqli';
$host = 'localhost';
$dbname = 'smarterp';
$DBUser = 'root';
$DBPassword = 'password';
$DBPort = 3306;
```

### Application Settings
```php
$appName = 'SmartERP';
$companyName = 'Your Company';
$appVersion = '2.2';
```

### System Features
```php
$enableFixedAssets = true;
$enableInventory = true;
$enableCRM = true;
$enableHR = true;
```

### Security & Logging
```php
$debugMode = false;
$logErrors = true;
$forceHTTPS = false;
```

---

## Wizard API (Advanced)

### Check Requirements
```bash
GET /installer/checker.php?action=check_requirements
```

Returns JSON with system requirements status

### Check Configuration
```bash
GET /installer/checker.php?action=check_config
```

Returns JSON with config existence and validity

### Get System Info
```bash
GET /installer/checker.php?action=get_system_info
```

Returns JSON with system information

---

## Troubleshooting

### "Cannot create config.php" Error
**Solution**: Check directory permissions
```bash
# Ensure installer directory is writable
chmod 755 installer/
chmod 755 /path/to/root

# Check owner
ls -la config.php
```

### "Database connection failed" Error
**Solution**: Verify credentials
```bash
# Test MySQL connection
mysql -h localhost -u root -p database_name

# Check if user has permissions
GRANT ALL ON database_name.* TO 'user'@'localhost';
```

### "Missing PHP Extension" Error
**Solution**: Install required extension
```bash
# Ubuntu/Debian
apt-get install php-mysqli
apt-get install php-gd

# Restart web server
systemctl restart apache2
# or
systemctl restart php-fpm
```

### "Directory not writable" Error
**Solution**: Fix permissions
```bash
mkdir -p logs/
mkdir -p uploads/
mkdir -p cache/

chmod 755 logs/
chmod 755 uploads/
chmod 755 cache/
```

---

## Manual Configuration

If the wizard fails, you can manually create `config.php`:

```php
<?php
// Database Configuration
$DBType = 'mysqli';
$host = 'localhost';
$dbname = 'smarterp';
$DBUser = 'root';
$DBPassword = 'password';
$DBPort = 3306;

// Application Settings
$appName = 'SmartERP';
$companyName = 'Your Company';
$appVersion = '2.2';
$RootPath = dirname(__FILE__);

// System Settings
$sessionTimeout = 60;
$debugMode = false;
$maxFileUpload = 50;

// Security
$forceHTTPS = false;
$allowedDomains = '*';
$apiAuthRequired = true;

// Logging
$logDir = dirname(__FILE__) . '/logs';
$logErrors = true;
$logQueries = false;

// Email (Optional)
$smtpServer = 'localhost';
$smtpPort = 587;
$smtpUser = '';
$smtpPassword = '';

// Date & Time
$timezone = 'UTC';
$dateFormat = 'd/m/Y';
$timeFormat = 'H:i:s';

// Currency
$defaultCurrency = 'USD';
$currencySymbol = '$';
$currencyDecimals = 2;

// Features
$enableFixedAssets = true;
$enableInventory = true;
$enableCRM = true;
$enableHR = true;

if (!defined('APP_INITIALIZED')) {
    define('APP_INITIALIZED', true);
    date_default_timezone_set($timezone);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
}
?>
```

Save this as `/config.php` in the root directory.

---

## Security Best Practices

### After Installation
1. ✅ Delete or rename the `/installer` directory
2. ✅ Set `config.php` permissions to `0644`
3. ✅ Never commit `config.php` to version control
4. ✅ Use strong database passwords
5. ✅ Keep database credentials private
6. ✅ Restrict access to the application directory
7. ✅ Enable HTTPS in production (`$forceHTTPS = true`)

### Database Security
```bash
# Restrict database user permissions (principle of least privilege)
GRANT SELECT, INSERT, UPDATE, DELETE ON database_name.* TO 'user'@'localhost';

# Avoid using 'root' user
# Change password regularly
ALTER USER 'user'@'localhost' IDENTIFIED BY 'new_password';
```

### File Permissions
```bash
# Typical permissions
chmod 644 config.php      # Read-only
chmod 755 logs/           # Writable by web server
chmod 755 uploads/        # Writable by web server
chmod 755 cache/          # Writable by web server
```

---

## Upgrading Existing Installation

If you have an existing `config.php`:

1. **Backup existing config**
   ```bash
   cp config.php config.backup.php
   ```

2. **Access the wizard**
   ```
   http://your-server/installer/
   ```

3. **The wizard will**
   - Detect existing configuration
   - Show current settings
   - Allow you to update settings
   - Preserve or overwrite as needed

4. **Verify changes**
   ```bash
   # Check new config
   php -l config.php
   ```

---

## Support

For issues or questions:

1. Check `installer/status.php` for system diagnostics
2. Review error messages in application logs (`logs/` directory)
3. Verify database connectivity
4. Check file and directory permissions
5. Review PHP error logs (`php_errors.log`)

---

## Files Included

```
installer/
├── index.php           # Installation wizard
├── status.php          # System requirements checker
├── checker.php         # Configuration validation class
└── README.md           # This file
```

---

## Version Information

- **SmartERP Version**: 2.2
- **Database**: MySQL 5.7+, MariaDB 10.3+
- **PHP Version**: 7.2+
- **Last Updated**: 2024

---

**Important**: Always keep your `config.php` secure and never expose it publicly!
