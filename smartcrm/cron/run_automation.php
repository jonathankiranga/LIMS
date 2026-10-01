<?php
/**
 * Automation Execution Engine
 * 
 * 
 * Or manually: php cron/run_automation.php
 * 
 * This script:
 * - Reads database credentials from config.php
 * - Connects directly via AutomationDB helper (no ConnectDB.inc needed)
 * - Works in CLI mode without user login
 * - Processes automation rules from crm_automation_rules table
 * - Fires on: lead_created, lead_status_changed, followup_overdue, opportunity_won
 */

// ============================================================
// SETUP
// ============================================================

// For CLI mode: set safe $_SERVER defaults
if (PHP_SAPI === 'cli') {
    $_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? 'cron/run_automation.php';
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? 'cron/run_automation.php';
}

// Path setup
$PathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;
$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'automation.log';

// Include config and database helper
include($PathPrefix . 'config.php');
include($PathPrefix . 'includes/AutomationDB.inc');

// Get database connection (reads from config.php)
$db = getAutomationDB();
if (!$db) {
    echo "[ERROR] Failed to connect to database\n";
    exit(1);
}

// ============================================================
// LOGGING
// ============================================================

function logMsg($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $msg\n";
    
    // Write to log file
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    
    // Echo to stdout (for testing)
    if (PHP_SAPI === 'cli') {
        echo $line;
    }
}

function logError($msg) {
    logMsg("ERROR: $msg");
}

// ============================================================
// QUERY FUNCTIONS - Get entities for processing
// ============================================================

/**
 * Get all active automation rules
 */
function getActiveRules($db) {
    $rules = array();
    $SQL = "SELECT * FROM crm_automation_rules WHERE is_active=1 ORDER BY priority DESC";
    $Result = DB_query($SQL, $db);
    
    if (!$Result) {
        return $rules;
    }
    
    while ($row = DB_fetch_array($Result)) {
        $row['trigger_conditions'] = json_decode($row['trigger_conditions'], true);
        $row['actions'] = json_decode($row['actions'], true);
        $rules[] = $row;
    }
    
    return $rules;
}

/**
 * Get newly created leads (created in last hour, not yet processed)
 */
function getNewLeads($db) {
    $leads = array();
    $SQL = "SELECT * FROM crm_leads 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND id NOT IN (
                SELECT entity_id FROM crm_automation_log 
                WHERE trigger_type='lead_created' 
                AND executed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            )
            ORDER BY created_at DESC";
    
    $Result = DB_query($SQL, $db);
    
    if (!$Result) {
        return $leads;
    }
    
    while ($row = DB_fetch_array($Result)) {
        $leads[] = $row;
    }
    
    return $leads;
}

/**
 * Get leads with status changes (in last hour)
 */
function getChangedLeadStatuses($db) {
    $changes = array();
    $SQL = "SELECT * FROM crm_leads 
            WHERE updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND id NOT IN (
                SELECT entity_id FROM crm_automation_log 
                WHERE trigger_type='lead_status_changed' 
                AND executed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            )
            ORDER BY updated_at DESC";
    
    $Result = DB_query($SQL, $db);
    
    if (!$Result) {
        return $changes;
    }
    
    while ($row = DB_fetch_array($Result)) {
        $changes[] = $row;
    }
    
    return $changes;
}

/**
 * Get leads with overdue followup dates
 */
function getOverdueFollowups($db) {
    $items = array();
    $SQL = "SELECT * FROM crm_leads 
            WHERE next_followup IS NOT NULL 
            AND next_followup < CURDATE()
            AND id NOT IN (
                SELECT entity_id FROM crm_automation_log 
                WHERE trigger_type='followup_overdue' 
                AND executed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            )
            ORDER BY next_followup ASC";
    
    $Result = DB_query($SQL, $db);
    
    if (!$Result) {
        return $items;
    }
    
    while ($row = DB_fetch_array($Result)) {
        $items[] = $row;
    }
    
    return $items;
}

/**
 * Get opportunities that moved to Won stage
 */
function getWonOpportunities($db) {
    $opps = array();
    $SQL = "SELECT o.*, s.stage_name FROM crm_opportunities o
            LEFT JOIN crm_pipeline_stages s ON o.pipeline_stage_id = s.id
            WHERE s.stage_name LIKE '%Won%' 
            AND o.updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND o.id NOT IN (
                SELECT entity_id FROM crm_automation_log 
                WHERE trigger_type='opportunity_won' 
                AND executed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            )
            ORDER BY o.updated_at DESC";
    
    $Result = DB_query($SQL, $db);
    
    if (!$Result) {
        return $opps;
    }
    
    while ($row = DB_fetch_array($Result)) {
        $opps[] = $row;
    }
    
    return $opps;
}

// ============================================================
// ACTION EXECUTION
// ============================================================

/**
 * Execute actions for a rule
 * 
 * @param mysqli $db Database connection
 * @param array $actions Array of action definitions
 * @param array $entity Lead or opportunity data
 * @param string $entityType 'lead' or 'opportunity'
 * @return array Results of action execution
 */
function executeActions($db, $actions, $entity, $entityType) {
    $results = array();
    
    if (!is_array($actions)) {
        $results[] = "ERROR: Actions is not an array";
        return $results;
    }
    
    foreach ($actions as $act) {
        if (!is_array($act)) {
            continue;
        }
        
        $type = $act['type'] ?? '';
        
        switch ($type) {
            case 'update_field':
                try {
                    $field = $act['field'] ?? '';
                    $value = $act['value'] ?? '';
                    
                    if ($field && $entity['id']) {
                        $table = $entityType === 'lead' ? 'crm_leads' : 'crm_opportunities';
                        $SQL = "UPDATE $table 
                                SET $field='" . DB_escape_string($value) . "', updated_at=NOW() 
                                WHERE id=" . (int)$entity['id'];
                        
                        DB_query($SQL, $db);
                        $results[] = "Updated $field to '$value'";
                    }
                } catch (Exception $e) {
                    $results[] = "ERROR updating field: " . $e->getMessage();
                }
                break;
                
            case 'create_task':
                try {
                    $title = replaceTemplateVariables($act['title'] ?? 'Follow-up Required', $entity);
                    $dueDays = (int)($act['due_days'] ?? 1);
                    $details = replaceTemplateVariables($act['details'] ?? '', $entity);
                    $assignedTo = $entity['assigned_to'] ?? 'admin';
                    
                    $SQL = "INSERT INTO Tasks (Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated)
                            VALUES (
                                '" . DB_escape_string($title) . "', 
                                '0', 
                                '1', 
                                '" . DB_escape_string($assignedTo) . "', 
                                DATE_ADD(CURDATE(), INTERVAL $dueDays DAY), 
                                '" . DB_escape_string($details) . "', 
                                NOW()
                            )";
                    
                    DB_query($SQL, $db);
                    $results[] = "Created task: '$title'";
                } catch (Exception $e) {
                    $results[] = "ERROR creating task: " . $e->getMessage();
                }
                break;
                
            case 'send_email':
                try {
                    $to = $act['to'] ?? $entity['email'] ?? '';
                    $subject = replaceTemplateVariables($act['subject'] ?? 'Notification', $entity);
                    $body = replaceTemplateVariables($act['body'] ?? 'Hello, this is an automated notification.', $entity);
                    $from = $act['from'] ?? 'noreply@smartcrm.com';
                    
                    if ($to) {
                        $headers = "From: $from\r\nContent-Type: text/html; charset=UTF-8\r\n";
                        if (mail($to, $subject, $body, $headers)) {
                            $results[] = "Sent email to '$to'";
                        } else {
                            $results[] = "WARNING: mail() failed for '$to'";
                        }
                    } else {
                        $results[] = "SKIPPED: No email address for recipient";
                    }
                } catch (Exception $e) {
                    $results[] = "ERROR sending email: " . $e->getMessage();
                }
                break;
                
            case 'notify_user':
                try {
                    $userId = $act['user_id'] ?? $entity['assigned_to'] ?? '';
                    $message = replaceTemplateVariables($act['message'] ?? 'You have a new task.', $entity);
                    
                    $SQL = "INSERT INTO Tasks (Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated)
                            VALUES (
                                'Automation Alert: " . DB_escape_string($message) . "', 
                                '0', 
                                '2', 
                                '" . DB_escape_string($userId) . "', 
                                CURDATE(), 
                                'Automated notification', 
                                NOW()
                            )";
                    
                    DB_query($SQL, $db);
                    $results[] = "Notified user '$userId'";
                } catch (Exception $e) {
                    $results[] = "ERROR notifying user: " . $e->getMessage();
                }
                break;
                
            default:
                $results[] = "SKIPPED: Unknown action type '$type'";
        }
    }
    
    return $results;
}

// ============================================================
// MAIN EXECUTION
// ============================================================

logMsg("=== Automation Engine Started ===");

try {
    // Get all active rules
    $rules = getActiveRules($db);
    logMsg("Found " . count($rules) . " active automation rules");
    
    $totalProcessed = 0;
    
    // Process each rule
    foreach ($rules as $rule) {
        $ruleId = $rule['id'];
        $ruleName = $rule['rule_name'];
        $triggerType = $rule['trigger_type'];
        $actions = $rule['actions'];
        $conditions = $rule['trigger_conditions'];
        
        logMsg("Processing rule: '$ruleName' (ID: $ruleId, Trigger: $triggerType)");
        
        // Get entities to process based on trigger type
        $entities = array();
        $entityType = 'lead';
        
        switch ($triggerType) {
            case 'lead_created':
                $entities = getNewLeads($db);
                $entityType = 'lead';
                break;
            case 'lead_status_changed':
                $entities = getChangedLeadStatuses($db);
                $entityType = 'lead';
                break;
            case 'followup_overdue':
                $entities = getOverdueFollowups($db);
                $entityType = 'lead';
                break;
            case 'opportunity_won':
                $entities = getWonOpportunities($db);
                $entityType = 'opportunity';
                break;
            default:
                logMsg("  WARNING: Unknown trigger type '$triggerType'");
                continue;
        }
        
        logMsg("  Found " . count($entities) . " $entityType(s) matching trigger");
        
        // Process each entity
        foreach ($entities as $entity) {
            $entityId = $entity['id'];
            
            try {
                // Check if conditions match (if defined)
                if (!empty($conditions)) {
                    if (!crm_rule_conditions_match($entity, $conditions, $entityType)) {
                        logMsg("    Entity ID $entityId: Conditions NOT matched, skipping");
                        logAutomationExecution($db, $ruleId, $triggerType, $entityType, $entityId, 'skipped', 'Conditions not matched');
                        continue;
                    }
                }
                
                // Execute actions
                $results = executeActions($db, $actions, $entity, $entityType);
                
                // Log results
                foreach ($results as $result) {
                    logMsg("    Entity ID $entityId: $result");
                }
                
                // Log execution to database
                logAutomationExecution($db, $ruleId, $triggerType, $entityType, $entityId, 'success', implode('; ', $results));
                $totalProcessed++;
                
            } catch (Exception $e) {
                $errorMsg = "Error processing entity ID $entityId: " . $e->getMessage();
                logError($errorMsg);
                logAutomationExecution($db, $ruleId, $triggerType, $entityType, $entityId, 'error', $e->getMessage());
            }
        }
    }
    
    logMsg("=== Automation Engine Completed. Processed: $totalProcessed ===");
    
    // Close database connection
    $db->close();
    exit(0);
    
} catch (Exception $e) {
    logError("Fatal error: " . $e->getMessage());
    if (isset($db)) {
        $db->close();
    }
    exit(1);
}

?>
