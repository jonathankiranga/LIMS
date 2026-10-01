<?php
/**
 * SmartERP Installation Configuration Checker
 * Validates system requirements and configuration status
 */

class InstallerChecker {
    private $rootPath;
    private $configPath;
    private $requirements = [
        'php_version' => ['min' => '7.2', 'current' => null],
        'extensions' => ['mysqli', 'json', 'curl', 'gd', 'fileinfo'],
        'writable_dirs' => ['/logs', '/uploads', '/cache'],
        'functions' => ['mysqli_connect', 'json_encode', 'file_get_contents']
    ];
    
    public function __construct($rootPath = null) {
        $this->rootPath = $rootPath ?: dirname(dirname(__FILE__));
        $this->configPath = $this->rootPath . '/config.php';
        $this->requirements['php_version']['current'] = PHP_VERSION;
    }
    
    /**
     * Check if config.php exists
     */
    public function configExists() {
        return file_exists($this->configPath);
    }
    
    /**
     * Check if config.php is valid
     */
    public function validateConfig() {
        if (!$this->configExists()) {
            return false;
        }
        
        try {
            include_once($this->configPath);
            return isset($host) && isset($dbname) && isset($DBUser);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check PHP version requirement
     */
    public function checkPHPVersion() {
        $current = $this->requirements['php_version']['current'];
        $minimum = $this->requirements['php_version']['min'];
        
        return version_compare($current, $minimum, '>=');
    }
    
    /**
     * Check required PHP extensions
     */
    public function checkExtensions() {
        $missing = [];
        
        foreach ($this->requirements['extensions'] as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        return ['status' => empty($missing), 'missing' => $missing];
    }
    
    /**
     * Check disabled functions
     */
    public function checkDisabledFunctions() {
        $disabled = explode(',', ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);
        
        $critical = [];
        foreach ($this->requirements['functions'] as $func) {
            if (in_array($func, $disabled)) {
                $critical[] = $func;
            }
        }
        
        return ['status' => empty($critical), 'disabled' => $critical];
    }
    
    /**
     * Check directory write permissions
     */
    public function checkWritableDirectories() {
        $writable = [];
        $notWritable = [];
        
        foreach ($this->requirements['writable_dirs'] as $dir) {
            $fullPath = $this->rootPath . $dir;
            
            // Create directory if it doesn't exist
            if (!is_dir($fullPath)) {
                @mkdir($fullPath, 0755, true);
            }
            
            if (is_writable($fullPath)) {
                $writable[] = $dir;
            } else {
                $notWritable[] = $dir;
            }
        }
        
        return [
            'status' => empty($notWritable),
            'writable' => $writable,
            'notWritable' => $notWritable
        ];
    }
    
    /**
     * Check if MySQL/MySQLi is available
     */
    public function checkMySQLAvailability() {
        return extension_loaded('mysqli') || function_exists('mysqli_connect');
    }
    
    /**
     * Get all system information
     */
    public function getSystemInfo() {
        return [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
            'web_server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_upload_size' => ini_get('upload_max_filesize'),
            'max_execution_time' => ini_get('max_execution_time'),
            'timezone' => date_default_timezone_get()
        ];
    }
    
    /**
     * Get a comprehensive status report
     */
    public function getStatusReport() {
        return [
            'config_exists' => $this->configExists(),
            'config_valid' => $this->validateConfig(),
            'php_version' => [
                'status' => $this->checkPHPVersion(),
                'current' => $this->requirements['php_version']['current'],
                'required' => $this->requirements['php_version']['min']
            ],
            'extensions' => $this->checkExtensions(),
            'disabled_functions' => $this->checkDisabledFunctions(),
            'writable_dirs' => $this->checkWritableDirectories(),
            'mysql_available' => $this->checkMySQLAvailability(),
            'system_info' => $this->getSystemInfo()
        ];
    }
    
    /**
     * Check if system is ready for installation
     */
    public function isReady() {
        $report = $this->getStatusReport();
        
        return $report['php_version']['status'] &&
               $report['extensions']['status'] &&
               $report['disabled_functions']['status'] &&
               $report['writable_dirs']['status'] &&
               $report['mysql_available'];
    }
}

// API Response for AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $checker = new InstallerChecker();
    
    switch ($_GET['action']) {
        case 'check_requirements':
            echo json_encode($checker->getStatusReport());
            break;
        
        case 'check_config':
            echo json_encode([
                'exists' => $checker->configExists(),
                'valid' => $checker->validateConfig()
            ]);
            break;
        
        case 'get_system_info':
            echo json_encode($checker->getSystemInfo());
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
    exit;
}
?>
