<?php
/**
 * Automation Execution Engine
 * Run via cron: */5 * * * * php /path/to/cron/run_automation.php
 * Or manually: php run_automation.php
 */
$PathPrefix = './';
$DatabaseName = 'mozillaerpv2';

include($PathPrefix . 'config.php');
include($PathPrefix . 'includes/ConnectDB.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');

$logFile = __DIR__ . '/automation.log';
function logMsg($msg) {
    global $logFile;
    $line = date('Y-m-d H:i:s') . " $msg\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

logMsg("=== Automation Engine Started ===");

function getActiveRules($db) {
    $rules = array();
    $SQL = "SELECT * FROM crm_automation_rules WHERE is_active=1 ORDER BY priority DESC";
    $Result = DB_query($SQL, $db);
    while ($row = DB_fetch_array($Result)) {
        $row['trigger_conditions'] = json_decode($row['trigger_conditions'], true);
        $row['actions'] = json_decode($row['actions'], true);
        $rules[] = $row;
    }
    return $rules;
}

function getLeadsWithPendingActions($db) {
    $leads = array();
    $SQL = "SELECT * FROM crm_leads WHERE last_activity_at IS NOT NULL 
            AND TIMESTAMPDIFF(HOUR, last_activity_at, NOW()) >= 24
            AND id NOT IN (SELECT entity_id FROM crm_automation_log WHERE entity_type='lead' AND executed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR))";
    $Result = DB_query($SQL, $db);
    while ($row = DB_fetch_array($Result)) {
        $leads[] = $row;
    }
    return $leads;
}

function getOverdueFollowups($db) {
    $items = array();
    $SQL = "SELECT * FROM crm_leads WHERE next_followup IS NOT NULL AND next_followup < CURDATE()";
    $Result = DB_query($SQL, $db);
    while ($row = DB_fetch_array($Result)) {
        $items[] = $row;
    }
    return $items;
}

function getNewLeads($db) {
    $leads = array();
    $SQL = "SELECT * FROM crm_leads WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND id NOT IN (SELECT entity_id FROM crm_automation_log WHERE trigger_type='lead_created' AND executed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR))";
    $Result = DB_query($SQL, $db);
    while ($row = DB_fetch_array($Result)) {
        $leads[] = $row;
    }
    return $leads;
}

function getChangedLeadStatuses($db) {
    $changes = array();
    $SQL = "SELECT l.*, ah.old_status, ah.new_status, ah.changed_at FROM crm_leads l
            LEFT JOIN (SELECT lead_id, old_status, new_status, MAX(created_at) as changed_at 
            FROM crm_automation_log WHERE trigger_type='lead_status_changed' GROUP BY lead_id) ah ON l.id = ah.lead_id
            WHERE ah.changed_at IS NOT NULL AND ah.changed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $Result = DB_query($SQL, $db);
    while ($row = DB_fetch_array($Result)) {
        $changes[] = $row;
    }
    return $changes;
}

function getWonOpportunities($db) {
    $opps = array();
    $SQL = "SELECT o.*, s.stage_name FROM crm_opportunities o
            LEFT JOIN crm_pipeline_stages s ON o.pipeline_stage_id = s.id
            WHERE s.stage_name LIKE '%Won%' AND o.updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND o.id NOT IN (SELECT entity_id FROM crm_automation_log WHERE trigger_type='opportunity_won' AND executed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR))";
    $Result = DB_query($SQL, $db);
    while ($row = DB_fetch_array($Result)) {
        $opps[] = $row;
    }
    return $opps;
}

function logExecution($db, $ruleId, $triggerType, $entityType, $entityId, $result, $error = null) {
    $safeResult = $db->real_escape_string($result);
    $safeError = $error ? $db->real_escape_string($error) : null;
    $SQL = "INSERT INTO crm_automation_log (rule_id, trigger_type, entity_type, entity_id, execution_result, error_message, executed_at)
            VALUES ($ruleId, '$triggerType', '$entityType', $entityId, '$safeResult', " . ($safeError ? "'$safeError'" : "NULL") . ", NOW())";
    DB_query($SQL, $db);
}

function executeAction($db, $action, $entity, $entityType) {
    $results = array();
    
    foreach ($action as $act) {
        $type = $act['type'] ?? '';
        
        switch ($type) {
            case 'update_field':
                $field = $act['field'] ?? '';
                $value = $act['value'] ?? '';
                if ($field && $entity['id']) {
                    $table = $entityType === 'lead' ? 'crm_leads' : 'crm_opportunities';
                    $SQL = "UPDATE $table SET $field='" . $db->real_escape_string($value) . "' WHERE id=" . $entity['id'];
                    DB_query($SQL, $db);
                    $results[] = "Updated $field to $value";
                }
                break;
                
            case 'create_task':
                $title = $act['title'] ?? 'Follow-up Required';
                $dueDays = $act['due_days'] ?? 1;
                $details = $act['details'] ?? '';
                $assignedTo = $entity['assigned_to'] ?? ($_SESSION['UserID'] ?? 'admin');
                $SQL = "INSERT INTO Tasks (Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated)
                        VALUES ('" . $db->real_escape_string($title) . "', '0', '1', '$assignedTo', DATE_ADD(CURDATE(), INTERVAL $dueDays DAY), 
                        '" . $db->real_escape_string($details) . "', NOW())";
                DB_query($SQL, $db);
                $results[] = "Created task: $title";
                break;
                
            case 'send_email':
                $to = $act['to'] ?? $entity['email'] ?? '';
                $subject = $act['subject'] ?? 'Notification';
                $body = $act['body'] ?? 'Hello, this is an automated notification.';
                $from = $act['from'] ?? 'noreply@smartcrm.com';
                
                if ($to) {
                    $subject = str_replace(['{{name}}', '{{company}}'], [$entity['contact_name'] ?? '', $entity['company_name'] ?? ''], $subject);
                    $body = str_replace(['{{name}}', '{{company}}'], [$entity['contact_name'] ?? '', $entity['company_name'] ?? ''], $body);
                    
                    $headers = "From: $from\r\nContent-Type: text/html; charset=UTF-8\r\n";
                    if (mail($to, $subject, $body, $headers)) {
                        $results[] = "Sent email to $to";
                    } else {
                        $results[] = "Failed to send email to $to";
                    }
                }
                break;
                
            case 'notify_user':
                $userId = $act['user_id'] ?? $entity['assigned_to'] ?? '';
                $message = $act['message'] ?? 'You have a new task.';
                $SQL = "INSERT INTO Tasks (Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated)
                        VALUES ('Automation Alert: $message', '0', '2', '$userId', CURDATE(), 'Automated notification', NOW())";
                DB_query($SQL, $db);
                $results[] = "Notified user $userId";
                break;
                
            default:
                $results[] = "Unknown action type: $type";
        }
    }
    
    return $results;
}

$rules = getActiveRules($db);
logMsg("Found " . count($rules) . " active rules");

$processed = 0;

foreach ($rules as $rule) {
    $trigger = $rule['trigger_type'];
    $actions = $rule['actions'];
    
    logMsg("Checking trigger: $trigger");
    
    $entities = array();
    
    switch ($trigger) {
        case 'lead_created':
            $entities = getNewLeads($db);
            break;
        case 'followup_overdue':
            $entities = getOverdueFollowups($db);
            break;
        case 'lead_status_changed':
            $entities = getChangedLeadStatuses($db);
            break;
        case 'opportunity_won':
            $entities = getWonOpportunities($db);
            break;
    }
    
    logMsg("Found " . count($entities) . " entities for $trigger");
    
    foreach ($entities as $entity) {
        $entityId = $entity['id'];
        $entityType = strpos($trigger, 'opportunity') !== false ? 'opportunity' : 'lead';
        
        logMsg("Executing rule #{$rule['id']} for $entityType ID $entityId");
        
        $results = executeAction($db, $actions, $entity, $entityType);
        
        foreach ($results as $result) {
            logMsg("  - $result");
        }
        
        logExecution($db, $rule['id'], $trigger, $entityType, $entityId, 'success');
        $processed++;
    }
}

logMsg("=== Automation Engine Completed. Processed: $processed ===");
