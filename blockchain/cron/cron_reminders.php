<?php
// Cron Job: Process Reminders and Send Emails
// Run this every 5 minutes via cron: */5 * * * * php /path/to/cron_reminders.php

error_reporting(E_ALL);
ini_set('display_errors', 0);

$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$logFile = $logDir . '/cron_reminders.log';

function log_cron($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

log_cron('Starting reminder processing');

$configFile = __DIR__ . '/../include/config.php';
if (!file_exists($configFile)) {
    log_cron('ERROR: config.php not found');
    exit(1);
}
include($configFile);

$db_host = $config['DB_HOST'];
$db_name = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$db = new mysqli($db_host, $db_username, $db_password, $db_name);
if ($db->connect_error) {
    log_cron('ERROR: DB connection failed - ' . $db->connect_error);
    exit(1);
}
$db->set_charset('utf8mb4');

// ============================================
// Process Email Rules (task_due_soon, task_overdue)
// ============================================
function processEmailRules($db, $log_cron) {
    $rulesResult = $db->query("SELECT r.*, t.subject, t.body 
                               FROM ws_email_rules r 
                               LEFT JOIN ws_email_templates t ON r.email_template_id = t.id 
                               WHERE r.is_active=1 AND r.trigger_type IN ('task_due_soon', 'task_overdue') 
                               ORDER BY r.priority DESC");
    
    $rulesProcessed = 0;
    $today = date('Y-m-d');
    
    while ($rule = $rulesResult->fetch_assoc()) {
        $triggerType = $rule['trigger_type'];
        $recipients = json_decode($rule['recipients'] ?? '[]', true);
        
        if ($triggerType === 'task_due_soon') {
            $startDate = $today;
            $endDate = date('Y-m-d', strtotime('+1 day'));
            $dateField = 'due_date';
        } elseif ($triggerType === 'task_overdue') {
            $startDate = '1970-01-01';
            $endDate = $today;
            $dateField = 'due_date';
        } else {
            continue;
        }
        
        $taskSql = "SELECT t.*, w.name as workspace_name, u.email, u.realname, u.userid as assignee_id
                    FROM workspace_tasks t
                    LEFT JOIN workspace_boards wb ON t.board_id = wb.id
                    LEFT JOIN workspaces w ON wb.workspace_id = w.id
                    LEFT JOIN www_users u ON t.assigned_to = u.userid
                    WHERE t.$dateField IS NOT NULL 
                    AND t.$dateField BETWEEN '$startDate' AND '$endDate'";
        
        if ($triggerType === 'task_due_soon') {
            $taskSql .= " AND t.status != 'done'";
        } elseif ($triggerType === 'task_overdue') {
            $taskSql .= " AND t.status != 'done'";
        }
        
        $taskResult = $db->query($taskSql);
        
        while ($task = $taskResult->fetch_assoc()) {
            $checkSql = "SELECT id FROM ws_email_log 
                        WHERE email_rule_id=" . $rule['id'] . " 
                        AND reference_id=" . $task['id'] . "
                        AND DATE(sent_at) = '$today'";
            $checkResult = $db->query($checkSql);
            if ($checkResult->num_rows > 0) {
                continue;
            }
            
            $context = array(
                'task_title' => $task['title'],
                'due_date' => $task['due_date'],
                'user_name' => $task['realname'] ?? $task['assigned_to'] ?? 'User',
                'workspace_name' => $task['workspace_name'] ?? 'Workspace',
                'task_description' => $task['description'] ?? ''
            );
            
            $subject = $rule['subject_override'] ?: $rule['subject'];
            $body = $rule['body_override'] ?: $rule['body'];
            
            foreach ($context as $key => $value) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
                $body = str_replace('{{' . $key . '}}', $value, $body);
            }
            
            foreach ($recipients as $recipient) {
                $email = $recipient['email'] ?? '';
                $name = $recipient['name'] ?? '';
                
                if ($email) {
                    $sql = "INSERT INTO ws_email_queue (email_rule_id, recipient_email, recipient_name, subject, body, status, send_at)
                            VALUES (" . $rule['id'] . ", '" . $db->real_escape_string($email) . "', 
                                    '" . $db->real_escape_string($name) . "',
                                    '" . $db->real_escape_string($subject) . "', '" . $db->real_escape_string($body) . "', 
                                    'pending', NOW())";
                    $db->query($sql);
                    
                    $db->query("INSERT INTO ws_email_log (email_rule_id, reference_id, recipient_email, recipient_name, subject, status)
                                VALUES (" . $rule['id'] . ", " . $task['id'] . ", 
                                        '" . $db->real_escape_string($email) . "', '" . $db->real_escape_string($name) . "',
                                        '" . $db->real_escape_string($subject) . "', 'sent')");
                    
                    $log_cron("Queued {$triggerType} email for task #{$task['id']}: {$task['title']} -> $email");
                }
            }
        }
        $rulesProcessed++;
    }
    
    return array('rules' => $rulesProcessed, 'emails' => $emailsQueued);
}

$result = processEmailRules($db, 'log_cron');
log_cron("Processed {$result['rules']} email rules, queued {$result['emails']} task emails");

$now = date('Y-m-d H:i:s');
$windowStart = date('Y-m-d H:i:s', strtotime('-5 minutes'));
$windowEnd = date('Y-m-d H:i:s', strtotime('+5 minutes'));

$result = $db->query("SELECT * FROM ws_reminders 
                      WHERE status='pending' 
                      AND reminder_date BETWEEN '$windowStart' AND '$windowEnd'");

$processed = 0;
$emailsQueued = 0;

while ($row = $result->fetch_assoc()) {
    $context = array(
        'title' => $row['title'],
        'description' => $row['description'],
        'reminder_date' => $row['reminder_date'],
        'user_name' => $row['recipient_user'] ?? 'User'
    );
    
    if ($row['send_email'] && $row['recipient_email']) {
        $templateResult = $db->query("SELECT * FROM ws_email_templates WHERE category='reminder' AND is_active=1 LIMIT 1");
        if ($template = $templateResult->fetch_assoc()) {
            $subject = $template['subject'];
            $body = $template['body'];
            foreach ($context as $key => $value) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
                $body = str_replace('{{' . $key . '}}', $value, $body);
            }
            
            $sql = "INSERT INTO ws_email_queue (reminder_id, recipient_email, recipient_name, subject, body, status, send_at)
                    VALUES (" . $row['id'] . ", '" . $db->real_escape_string($row['recipient_email']) . "', 
                            '" . $db->real_escape_string($row['recipient_user'] ?? '') . "',
                            '" . $db->real_escape_string($subject) . "', '" . $db->real_escape_string($body) . "', 
                            'pending', NOW())";
            $db->query($sql);
            $emailsQueued++;
            
            log_cron("Queued email for reminder #{$row['id']}: {$row['title']}");
        }
    }
    
    $db->query("UPDATE ws_reminders SET status='sent', sent_at=NOW() WHERE id=" . $row['id']);
    $processed++;
}

log_cron("Processed $processed reminders, queued $emailsQueued emails");

// Process due task reminders
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$taskResult = $db->query("SELECT t.pkey, t.Taskname, t.datedue, t.TaskOwner, u.email, u.realname 
                          FROM Tasks t 
                          LEFT JOIN www_users u ON t.TaskOwner = u.userid
                          WHERE t.datedue IS NOT NULL AND t.Status <> 4 
                          AND t.datedue BETWEEN '$today' AND '$tomorrow'");

while ($task = $taskResult->fetch_assoc()) {
    $existingResult = $db->query("SELECT id FROM ws_reminders WHERE reference_id=" . $task['pkey'] . " AND reminder_type='task' AND DATE(reminder_date)='" . $today . "'");
    if ($existingResult->num_rows > 0) {
        continue;
    }
    
    if ($task['email']) {
        $reminderDate = $today . ' 08:00:00';
        $sql = "INSERT INTO ws_reminders (reminder_type, reference_id, title, description, reminder_date, remind_before_minutes,
                recipient_user, recipient_email, send_notification, send_email, include_calendar, created_by)
                VALUES ('task', " . $task['pkey'] . ", 'Task Due Tomorrow: " . $db->real_escape_string($task['Taskname']) . "',
                        'Task due on " . $task['datedue'] . "', '$reminderDate', 0,
                        '" . $db->real_escape_string($task['TaskOwner']) . "', '" . $db->real_escape_string($task['email']) . "',
                        1, 1, 0, 'system')";
        $db->query($sql);
        log_cron("Created task reminder for #{$task['pkey']}: {$task['Taskname']}");
    }
}

log_cron('Completed reminder processing');
echo "OK: Processed $processed reminders\n";
