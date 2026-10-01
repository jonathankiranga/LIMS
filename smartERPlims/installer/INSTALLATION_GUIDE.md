# SmartERP - Quick Installation Guide

## 🚀 Installation Steps

### Step 1: Database Setup
Create a MySQL database for SmartERP:

```bash
# Connect to MySQL
mysql -u root -p

# Create database
CREATE DATABASE smarterp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (recommended instead of using root)
CREATE USER 'smarterp'@'localhost' IDENTIFIED BY 'strong_password_here';

# Grant permissions
GRANT ALL PRIVILEGES ON smarterp.* TO 'smarterp'@'localhost';
FLUSH PRIVILEGES;

# Exit
EXIT;
```

### Step 2: Run Installation Wizard

1. **Start the wizard**
   ```
   http://your-server/smartERPv.2.2 - Copy/installer/
   ```

2. **Enter database credentials**
   - Server: `localhost`
   - Port: `3306`
   - Database: `smarterp`
   - Username: `smarterp`
   - Password: Your password

3. **Verify connection** 
   - System will test the connection automatically

4. **Enter application details**
   - Application Name: e.g., `SmartERP`
   - Company Name: Your company name

5. **Complete setup**
   - File `config.php` is created
   - Redirect to application

### Step 3: Secure the Installer (Important!)

After successful setup:

**Option A: Delete the installer (Recommended)**
```bash
# Windows
rmdir /s installer

# Linux/Mac
rm -rf installer/
```

**Option B: Restrict access with .htaccess**
Edit `installer/.htaccess` and uncomment the security lines:
```apache
<Files "*.php">
    Order Allow,Deny
    Deny from all
</Files>
```

**Option C: Rename the directory**
```bash
mv installer/ installer-backup/
```

### Step 4: Access the Application

Visit: `http://your-server/smartERPv.2.2 - Copy/`

---

## 🔍 Check Installation Status

Before installation, verify system requirements:

```
http://your-server/smartERPv.2.2 - Copy/installer/status.php
```

This page shows:
- ✓ PHP version
- ✓ Required extensions
- ✓ Directory permissions
- ✓ Database availability
- ✓ Configuration status

---

## ⚙️ Manual Configuration (Alternative)

If the wizard fails, manually create `config.php`:

1. **Copy template**
   ```bash
   cp config.template.php config.php
   ```

2. **Edit configuration**
   ```php
   $host = 'localhost';
   $dbname = 'smarterp';
   $DBUser = 'smarterp';
   $DBPassword = 'your_password';
   ```

3. **Set permissions**
   ```bash
   chmod 644 config.php
   ```

---

## 🐛 Troubleshooting

### Problem: "Cannot create config.php"
**Solution**: Check directory permissions
```bash
# Make directory writable
chmod 755 /path/to/smarterp

# Run installer again
```

### Problem: "Database connection failed"
**Solution**: Verify credentials
```bash
# Test connection
mysql -h localhost -u smarterp -p smarterp

# Check permissions
SHOW GRANTS FOR 'smarterp'@'localhost';
```

### Problem: "Upload directory not writable"
**Solution**: Fix permissions
```bash
mkdir -p uploads/ logs/ cache/
chmod 755 uploads/ logs/ cache/
chown www-data:www-data uploads/ logs/ cache/  # on Linux
```

### Problem: "Missing PHP extension"
**Solution**: Install extension
```bash
# Ubuntu/Debian
sudo apt-get install php-mysqli php-gd

# CentOS/RHEL
sudo yum install php-mysqlnd php-gd

# Restart web server
sudo systemctl restart apache2  # or nginx, etc.
```

---

## 🔐 Security Checklist

After installation, verify:

- [ ] Installer directory deleted or protected
- [ ] `config.php` has correct permissions (644)
- [ ] Database password is strong
- [ ] Database user has minimal required permissions
- [ ] `logs/` directory is writable but not public
- [ ] `uploads/` directory is restricted
- [ ] HTTPS is enabled in production
- [ ] `config.php` is not in version control

---

## 📋 System Requirements

- **PHP**: 7.2 or higher
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.10+
- **Memory**: Minimum 256MB
- **Disk Space**: Minimum 100MB

### Required PHP Extensions
- `mysqli` - Database connection
- `json` - JSON support
- `curl` - HTTP requests
- `gd` - Image processing
- `fileinfo` - File type detection

---

## 📁 Directory Structure

```
smartERPv.2.2 - Copy/
├── installer/              # Installation wizard
│   ├── index.php          # Wizard interface
│   ├── status.php         # System checker
│   ├── checker.php        # Configuration class
│   └── README.md          # Installer docs
├── config.php             # Generated after setup
├── includes/              # Core includes
├── javascripts/           # JavaScript files
├── css/                   # Stylesheets
├── logs/                  # Application logs (created)
├── uploads/               # User uploads (created)
└── ... other files
```

---

## 🎯 Next Steps After Installation

1. **Log in to the application**
   - Access main application
   - Use default credentials (if provided)
   - Change password immediately

2. **Configure system settings**
   - Set company information
   - Configure fiscal periods
   - Set up users and permissions

3. **Import data (if applicable)**
   - Chart of accounts
   - Customers and vendors
   - Products and inventory

4. **Set up security**
   - Configure user roles
   - Set permissions
   - Enable audit trail

5. **Customize features**
   - Enable/disable modules
   - Set up workflows
   - Configure reports

---

## 📞 Support Resources

- **Status Check**: `installer/status.php`
- **Requirements**: Check installer/README.md
- **Logs**: Check `logs/` directory
- **Database**: Verify with your hosting provider

---

## 🔄 Updating Existing Installation

If you have an existing system:

1. **Backup current config**
   ```bash
   cp config.php config.backup.php
   ```

2. **Run installer**
   - New wizard will detect existing config
   - You can update settings
   - Existing data is preserved

3. **Restore backup if needed**
   ```bash
   cp config.backup.php config.php
   ```

---

**Installation Date**: [Current Date]
**System Version**: SmartERP 2.2
**Last Updated**: 2024

For more details, see: **installer/README.md**
