<?php
/**
 * AutomationDB.inc - CLI-Safe Database Helper
 * 
 * Provides:
 * - Direct mysqli connection (no ConnectDB.inc dependency)
 * - Query wrapper functions
 * - Condition matching functions
 * - Template variable replacement
 * 
 * Usage:
 *   include('includes/AutomationDB.inc');
 *   $db = getAutomationDB();  // Returns mysqli connection
 *   DB_query($sql, $db);       // Execute query
 *   DB_fetch_array($result);   // Fetch row
 */

// ============================================================
// DATABASE CONNECTION
// ============================================================

/**
 * Get database connection for automation
 * Reads credentials from config.php
 * 
 * @global string $host
 * @global string $DBUser
 * @global string $DBPassword
 * @global string $DefaultDatabase
 * @return mysqli|false Database connection or false on failure
 */
function getAutomationDB() {
    global $host, $DBUser, $DBPassword, $DefaultDatabase;
    
    // Ensure config variables are set
    if (!isset($host) || !isset($DBUser) || !isset($DBPassword) || !isset($DefaultDatabase)) {
        trigger_error('AutomationDB: Missing database configuration in config.php', E_USER_ERROR);
        return false;
    }
    
    // Create mysqli connection
    $db = new mysqli($host, $DBUser, $DBPassword, $DefaultDatabase);
    
    // Check for connection errors
    if ($db->connect_error) {
        trigger_error('AutomationDB: Connection failed - ' . $db->connect_error, E_USER_ERROR);
        return false;
    }
    
    // Set character encoding
    $db->set_charset("utf8mb4");
    
    return $db;
}

// ============================================================
// QUERY WRAPPERS
// ============================================================

/**
 * Execute a database query
 * Mimics the DB_query() interface from your system
 * 
 * @param string $sql SQL query to execute
 * @param mysqli $db Database connection
 * @param string $p2 Unused (for compatibility)
 * @param string $p3 Unused (for compatibility)
 * @param bool $p4 Unused (for compatibility)
 * @param bool $p5 Unused (for compatibility)
 * @return mysqli_result|false Query result or false on failure
 */
function DB_query($sql, $db, $p2 = '', $p3 = '', $p4 = false, $p5 = false) {
    if (!$db instanceof mysqli) {
        trigger_error('AutomationDB: Invalid database connection', E_USER_WARNING);
        return false;
    }
    
    $result = $db->query($sql);
    
    if (!$result && $db->error) {
        trigger_error('AutomationDB: Query failed - ' . $db->error . ' | SQL: ' . substr($sql, 0, 100), E_USER_WARNING);
    }
    
    return $result;
}

/**
 * Fetch a row from query result as associative array
 * 
 * @param mysqli_result $result Query result
 * @return array|null Associative array or null if no more rows
 */
function DB_fetch_array($result) {
    if ($result instanceof mysqli_result) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Escape a string for safe SQL use
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function DB_escape_string($string) {
    global $db;
    if (!$db instanceof mysqli) {
        // Fallback if no global $db
        return addslashes($string);
    }
    return $db->real_escape_string($string);
}

/**
 * Get the number of rows in a result
 * 
 * @param mysqli_result $result Query result
 * @return int Number of rows
 */
function DB_num_rows($result) {
    if ($result instanceof mysqli_result) {
        return $result->num_rows;
    }
    return 0;
}

/**
 * Get last insert ID
 * 
 * @param mysqli $db Database connection
 * @return int Last inserted ID
 */
function DB_last_insert_id($db) {
    if ($db instanceof mysqli) {
        return $db->insert_id;
    }
    return 0;
}

// ============================================================
// CONDITION MATCHING (from api.php)
// ============================================================

/**
 * Get allowed condition fields for an entity type
 * 
 * @param string $entityType 'lead' or 'opportunity'
 * @return array Allowed field names and their types
 */
function crm_condition_fields($entityType) {
    if ($entityType === 'opportunity') {
        return array(
            'pipeline_stage_id'   => 'number',
            'lead_id'             => 'number',
            'probability'         => 'number',
            'expected_value'      => 'number',
            'is_recurring'        => 'number',
            'assigned_to'         => 'text',
            'opportunity_name'    => 'text',
            'competitor'          => 'text',
            'recurring_cycle'     => 'text',
            'expected_close_date' => 'date'
        );
    }
    
    // Default: lead fields
    return array(
        'lead_score'    => 'number',
        'status'        => 'status',
        'source'        => 'text',
        'assigned_to'   => 'text',
        'industry'      => 'text',
        'country'       => 'text',
        'city'          => 'text',
        'company_name'  => 'text',
        'contact_name'  => 'text',
        'email'         => 'text',
        'phone'         => 'text',
        'next_followup' => 'date'
    );
}

/**
 * Compare a field value against an expected value using an operator
 * 
 * @param mixed $actual Actual value from database
 * @param string $op Operator (equals, contains, gt, gte, lt, lte, in, is_empty, etc.)
 * @param mixed $expected Expected value
 * @return bool True if condition matches
 */
function crm_condition_compare($actual, $op, $expected) {
    $op = strtolower(trim((string)$op));
    if ($op === '') {
        $op = 'equals';
    }
    
    $a = ($actual === null) ? '' : trim((string)$actual);
    $e = ($expected === null) ? '' : trim((string)$expected);

    switch ($op) {
        case 'is_empty':
            return $a === '';
        case 'not_empty':
            return $a !== '';
        case 'equals':
            return strcasecmp($a, $e) === 0;
        case 'not_equals':
            return strcasecmp($a, $e) !== 0;
        case 'contains':
            return $e !== '' && stripos($a, $e) !== false;
        case 'not_contains':
            return $e !== '' && stripos($a, $e) === false;
        case 'in':
        case 'not_in':
            $list = array_values(array_filter(array_map('trim', explode(',', $e)), 'strlen'));
            if (!$list) {
                return true;
            }
            $hit = false;
            foreach ($list as $c) {
                if (strcasecmp($a, $c) === 0) {
                    $hit = true;
                    break;
                }
            }
            return $op === 'in' ? $hit : !$hit;
        case 'gt':
        case 'gte':
        case 'lt':
        case 'lte':
            if ($a === '' || $e === '') {
                return false;
            }
            if (is_numeric($a) && is_numeric($e)) {
                $av = $a + 0;
                $ev = $e + 0;
            } else {
                $av = strtotime($a);
                $ev = strtotime($e);
                if ($av === false || $ev === false) {
                    return false;
                }
            }
            if ($op === 'gt')  { return $av > $ev; }
            if ($op === 'gte') { return $av >= $ev; }
            if ($op === 'lt')  { return $av < $ev; }
            return $av <= $ev;
        default:
            // Unknown operator: allow rule to pass (fail open)
            return true;
    }
}

/**
 * Check if an entity matches a rule's conditions
 * 
 * @param array $entity Entity data (lead or opportunity row)
 * @param array|object $conditions Condition definition (JSON decoded)
 * @param string $entityType 'lead' or 'opportunity'
 * @return bool True if conditions match (or if no conditions defined)
 */
function crm_rule_conditions_match($entity, $conditions, $entityType) {
    // Empty conditions = always run
    if (empty($conditions)) {
        return true;
    }
    
    // Convert object to array if needed
    if (is_object($conditions)) {
        $conditions = (array)$conditions;
    }

    // Extract conditions array
    $conds = array();
    if (isset($conditions['conditions']) && is_array($conditions['conditions'])) {
        $conds = $conditions['conditions'];
    } elseif (isset($conditions[0])) {
        $conds = $conditions;
    }
    
    // No conditions to check
    if (!$conds) {
        return true;
    }

    // Get allowed fields for this entity type
    $allowed  = crm_condition_fields($entityType);
    
    // Match mode: 'all' or 'any'
    $anyMode  = isset($conditions['match']) && strtolower((string)$conditions['match']) === 'any';

    // Check each condition
    foreach ($conds as $c) {
        if (!is_array($c)) {
            continue;
        }
        
        $field = trim((string)($c['field'] ?? ''));
        
        // Skip unknown fields or fields not in entity
        if ($field === '' || !isset($allowed[$field]) || !array_key_exists($field, $entity)) {
            continue;
        }
        
        // Perform comparison
        $hit = crm_condition_compare(
            $entity[$field],
            $c['op'] ?? 'equals',
            $c['value'] ?? ''
        );
        
        // Handle match modes
        if ($anyMode) {
            // ANY mode: return true on first match
            if ($hit) {
                return true;
            }
        } else {
            // ALL mode: return false on first non-match
            if (!$hit) {
                return false;
            }
        }
    }

    // ALL mode: all passed; ANY mode: none matched
    return !$anyMode;
}

// ============================================================
// TEMPLATE VARIABLE REPLACEMENT
// ============================================================

/**
 * Replace template variables in text
 * Supported: {{name}}, {{company}}
 * 
 * @param string $text Text containing template variables
 * @param array $entity Entity data (lead or opportunity)
 * @return string Text with variables replaced
 */
function replaceTemplateVariables($text, $entity) {
    if (!$text) {
        return $text;
    }
    
    $replacements = array(
        '{{name}}'    => $entity['contact_name'] ?? '',
        '{{company}}' => $entity['company_name'] ?? '',
    );
    
    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

// ============================================================
// LOGGING HELPERS (Optional - remove if you have your own)
// ============================================================

/**
 * Write a message to the automation log file
 * 
 * @param string $message Message to log
 * @param string $logFile Path to log file
 * @param string $level Log level (INFO, ERROR, WARNING)
 * @return bool True on success
 */
function automationLog($message, $logFile, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] [$level] $message\n";
    
    return file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Log an automation execution result
 * 
 * @param mysqli $db Database connection
 * @param int $ruleId Rule ID
 * @param string $triggerType Trigger type
 * @param string $entityType 'lead' or 'opportunity'
 * @param int $entityId Entity ID
 * @param string $result Result status (success, error, skipped)
 * @param string $message Optional error/detail message
 * @return bool True on success
 */
function logAutomationExecution($db, $ruleId, $triggerType, $entityType, $entityId, $result, $message = '') {
    try {
        $safeResult = DB_escape_string($result);
        $safeMessage = $message ? DB_escape_string($message) : 'NULL';
        
        $SQL = "INSERT INTO crm_automation_log 
                (rule_id, trigger_type, entity_type, entity_id, execution_result, error_message, executed_at)
                VALUES 
                (" . (int)$ruleId . ", 
                 '" . DB_escape_string($triggerType) . "', 
                 '" . DB_escape_string($entityType) . "', 
                 " . (int)$entityId . ", 
                 '$safeResult', 
                 " . ($message ? "'$safeMessage'" : "NULL") . ", 
                 NOW())";
        
        DB_query($SQL, $db, '', '', false, false);
        return true;
    } catch (Exception $e) {
        trigger_error('AutomationDB: Failed to log execution - ' . $e->getMessage(), E_USER_WARNING);
        return false;
    }
}

?>
