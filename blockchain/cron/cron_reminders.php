<?php
/**
 * cron_reminders.php
 *
 * Run every five minutes via cron:
 *   "0/5 * * * *" php /path/to/blockchain/cron/cron_reminders.php
 *
 * Two stages:
 *   1. Match workspace tasks against active ws_email_rules triggers
 *      (task_due_soon / task_overdue) and record a ws_reminders row for each.
 *   2. Fire every due ws_reminders row: render its template, deliver with
 *      sendEmail(), then mark it sent.
 *
 * Mail is sent directly by this script. ws_email_queue is intentionally not
 * used: nothing in this project drains that table, so queueing a row there
 * would never result in delivery.
 *
 * Task source mirrors workspace_board_schema_ready() in functions/api.php:
 * when the kanban tables exist, cards are the live task store; otherwise the
 * legacy workspace_tasks table is used.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

$DRY_RUN = in_array('--dry-run', $argv ?? array(), true);

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$logFile = $logDir . '/cron_reminders.log';

function log_cron($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

log_cron('Starting reminder processing');

$configFile = __DIR__ . '/../include/config.php';
if (!file_exists($configFile)) {
    log_cron('ERROR: config.php not found');
    exit(1);
}
$config = include($configFile);

$db = new mysqli($config['DB_HOST'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME']);
if ($db->connect_error) {
    log_cron('ERROR: DB connection failed - ' . $db->connect_error);
    exit(1);
}
$db->set_charset('utf8mb4');

require_once __DIR__ . '/../functions/sendemails.php';

/**
 * Run a query, log and swallow failures instead of emitting a fatal.
 */
function db_try($db, $sql, $context) {
    $result = $db->query($sql);
    if (!$result) {
        log_cron("ERROR [$context]: " . $db->error);
    }
    return $result;
}

/**
 * Run a write query. In --dry-run mode the statement is logged and discarded
 * so the whole pipeline can be exercised without touching data or sending mail.
 */
function db_write($db, $sql, $context) {
    global $DRY_RUN;
    if ($DRY_RUN) {
        log_cron("DRY-RUN [$context] would execute: " . preg_replace('/\s+/', ' ', $sql));
        return true;
    }
    return db_try($db, $sql, $context);
}

function db_table_exists($db, $table) {
    $result = db_try($db, "SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'", "table_exists:$table");
    return $result && $result->num_rows > 0;
}

/**
 * Whether the kanban (card) tables are in place. Matches
 * workspace_board_schema_ready() in functions/api.php.
 */
function using_cards($db) {
    foreach (['workspace_boards', 'workspace_lists', 'workspace_cards', 'workspace_card_members'] as $table) {
        if (!db_table_exists($db, $table)) {
            return false;
        }
    }
    return true;
}

/**
 * Load every open task that has a due date, normalised to one shape:
 *   id, title, description, due_date, workspace_name, recipient_name, emails[]
 */
function load_due_tasks($db, $useCards) {
    if ($useCards) {
        // Cards have no assignee column; membership lives in
        // workspace_card_members, so assignees arrive via GROUP_CONCAT.
        $sql = "SELECT c.id, c.title, c.description, c.due_date,
                       w.name AS workspace_name,
                       GROUP_CONCAT(DISTINCT u.email ORDER BY u.email SEPARATOR ',') AS emails,
                       GROUP_CONCAT(DISTINCT COALESCE(NULLIF(u.full_name, ''), cm.user_id)
                                    ORDER BY cm.id SEPARATOR ', ') AS recipient_names
                FROM workspace_cards c
                JOIN workspace_boards wb ON c.board_id = wb.id
                LEFT JOIN workspaces w ON wb.workspace_id = w.id
                LEFT JOIN workspace_lists l ON c.list_id = l.id
                LEFT JOIN workspace_card_members cm ON cm.card_id = c.id
                LEFT JOIN users u ON cm.user_id = u.user_id
                WHERE c.archived = 0
                  AND c.due_date IS NOT NULL
                  AND LOWER(COALESCE(l.name, '')) NOT IN ('done', 'complete', 'completed', 'closed')
                GROUP BY c.id";
    } else {
        $sql = "SELECT t.id, t.title, t.description, t.due_date,
                       w.name AS workspace_name,
                       u.email AS email,
                       COALESCE(NULLIF(u.full_name, ''), t.assigned_to) AS recipient_name
                FROM workspace_tasks t
                LEFT JOIN workspaces w ON t.workspace_id = w.id
                LEFT JOIN users u ON t.assigned_to = u.user_id
                WHERE t.status <> 'done'
                  AND t.due_date IS NOT NULL";
    }

    $result = db_try($db, $sql, $useCards ? 'load_cards' : 'load_tasks');
    if (!$result) {
        return array();
    }

    $tasks = array();
    while ($row = $result->fetch_assoc()) {
        $emails = array();
        if ($useCards) {
            if (!empty($row['emails'])) {
                $emails = array_filter(array_map('trim', explode(',', $row['emails'])));
            }
            $recipientName = !empty($row['recipient_names']) ? $row['recipient_names'] : 'User';
        } else {
            if (!empty($row['email'])) {
                $emails = array($row['email']);
            }
            $recipientName = !empty($row['recipient_name']) ? $row['recipient_name'] : 'User';
        }

        $tasks[] = array(
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => (string)($row['description'] ?? ''),
            'due_date' => $row['due_date'],
            'workspace_name' => !empty($row['workspace_name']) ? $row['workspace_name'] : 'Workspace',
            'recipient_name' => $recipientName,
            'emails' => array_values($emails),
        );
    }
    return $tasks;
}

/**
 * Create one ws_reminders row per (rule, task) match. Deduplicated on
 * (reminder_type, reference_id) for the current day, which is what
 * idx_reference on ws_reminders exists for.
 */
function process_email_rules($db, $tasks, $today) {
    $rulesResult = db_try($db,
        "SELECT r.*, t.subject, t.body
         FROM ws_email_rules r
         LEFT JOIN ws_email_templates t ON r.email_template_id = t.id
         WHERE r.is_active = 1
           AND r.trigger_type IN ('task_due_soon', 'task_overdue')
         ORDER BY r.priority DESC",
        'load_rules');
    if (!$rulesResult) {
        return array('rules' => 0, 'created' => 0);
    }

    $rulesProcessed = 0;
    $created = 0;
    $now = date('Y-m-d H:i:s');

    while ($rule = $rulesResult->fetch_assoc()) {
        $rulesProcessed++;
        $ruleId = (int)$rule['id'];

        $subject = $rule['subject_override'] ?: $rule['subject'];
        $body = $rule['body_override'] ?: $rule['body'];
        if (empty($subject) || empty($body)) {
            log_cron("SKIPPED rule #$ruleId ({$rule['rule_name']}): no active email template and no subject/body override");
            continue;
        }

        $configuredRecipients = json_decode($rule['recipients'] ?? '[]', true);
        if (!is_array($configuredRecipients)) {
            $configuredRecipients = array();
        }

        foreach ($tasks as $task) {
            $dueDay = substr((string)$task['due_date'], 0, 10);
            if ($dueDay === '') {
                continue;
            }
            if ($rule['trigger_type'] === 'task_due_soon') {
                $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
                if ($dueDay < $today || $dueDay > $tomorrow) {
                    continue;
                }
            } elseif ($dueDay >= $today) {
                continue;
            }

            $recipients = array();
            foreach ($configuredRecipients as $recipient) {
                if (!empty($recipient['email'])) {
                    $recipients[$recipient['email']] = $recipient['name'] ?? '';
                }
            }
            if (empty($recipients)) {
                foreach ($task['emails'] as $email) {
                    $recipients[$email] = $task['recipient_name'];
                }
            }
            if (empty($recipients)) {
                log_cron("SKIPPED task #{$task['id']} ({$task['title']}): rule #$ruleId has no recipients and the task has no assignee email");
                continue;
            }

            $context = array(
                'task_title' => $task['title'],
                'due_date' => $task['due_date'],
                'user_name' => $task['recipient_name'],
                'workspace_name' => $task['workspace_name'],
                'task_description' => $task['description'],
            );
            $renderedSubject = $subject;
            $renderedBody = $body;
            foreach ($context as $key => $value) {
                $renderedSubject = str_replace('{{' . $key . '}}', $value, $renderedSubject);
                $renderedBody = str_replace('{{' . $key . '}}', $value, $renderedBody);
            }

            $existing = db_try($db,
                "SELECT id FROM ws_reminders
                 WHERE reminder_type = 'task'
                   AND reference_id = " . $task['id'] . "
                   AND status IN ('pending', 'sent')
                   AND DATE(created_at) = '" . $db->real_escape_string($today) . "'
                 LIMIT 1",
                "dedup_task_{$task['id']}");
            if ($existing && $existing->num_rows > 0) {
                continue;
            }

            $primaryEmail = array_key_first($recipients);
            $primaryName = $recipients[$primaryEmail] !== '' ? $recipients[$primaryEmail] : $task['recipient_name'];

            $insert = "INSERT INTO ws_reminders
                       (reminder_type, reference_id, title, description, reminder_date,
                        remind_before_minutes, recipient_user, recipient_email,
                        send_notification, send_email, include_calendar, created_by)
                       VALUES ('task', " . $task['id'] . ", '"
                . $db->real_escape_string($renderedSubject) . "', '"
                . $db->real_escape_string(strip_tags($renderedBody)) . "', '"
                . $now . "', 0, '"
                . $db->real_escape_string($task['recipient_name']) . "', '"
                . $db->real_escape_string($primaryEmail) . "', 1, 1, 0, '"
                . $db->real_escape_string("system:rule=" . $ruleId) . "')";

            if (db_write($db, $insert, "insert_reminder_task_{$task['id']}")) {
                $created++;
                log_cron("Created {$rule['trigger_type']} reminder for task #{$task['id']} ({$task['title']}) -> $primaryEmail");
            }
        }
    }

    return array('rules' => $rulesProcessed, 'created' => $created);
}

/**
 * Resolve the subject/body a reminder should be delivered with. Reminders
 * created by this script carry 'system:rule=<id>' in created_by so the
 * originating rule's template (and any override) is used.
 */
function resolve_reminder_template($db, $reminder) {
    if (preg_match('/^system:rule=(\d+)$/', (string)$reminder['created_by'], $matches)) {
        $ruleId = (int)$matches[1];
        $result = db_try($db,
            "SELECT r.subject_override, r.body_override, t.subject, t.body
             FROM ws_email_rules r
             LEFT JOIN ws_email_templates t ON r.email_template_id = t.id
             WHERE r.id = $ruleId LIMIT 1",
            "resolve_rule_$ruleId");
        if ($result && ($row = $result->fetch_assoc())) {
            $subject = $row['subject_override'] ?: $row['subject'];
            $body = $row['body_override'] ?: $row['body'];
            if (!empty($subject) && !empty($body)) {
                return array('subject' => $subject, 'body' => $body);
            }
        }
    }

    $result = db_try($db,
        "SELECT subject, body FROM ws_email_templates
         WHERE category = 'reminder' AND is_active = 1
         ORDER BY id ASC LIMIT 1",
        'resolve_default_template');
    if ($result && ($row = $result->fetch_assoc())) {
        return array('subject' => $row['subject'], 'body' => $row['body']);
    }
    return null;
}

/**
 * Deliver every reminder that is due and still pending.
 */
function fire_due_reminders($db) {
    global $DRY_RUN;
    $result = db_try($db,
        "SELECT * FROM ws_reminders
         WHERE status = 'pending' AND reminder_date <= NOW()
         ORDER BY reminder_date ASC
         LIMIT 200",
        'load_due_reminders');
    if (!$result) {
        return array('processed' => 0, 'sent' => 0, 'failed' => 0);
    }

    $processed = 0;
    $sent = 0;
    $failed = 0;

    while ($reminder = $result->fetch_assoc()) {
        $id = (int)$reminder['id'];
        $processed++;

        if (empty($reminder['send_email']) || empty($reminder['recipient_email'])) {
            db_write($db, "UPDATE ws_reminders SET status = 'sent', sent_at = NOW() WHERE id = $id", "mark_sent_$id");
            continue;
        }

        $template = resolve_reminder_template($db, $reminder);
        if (!$template) {
            log_cron("ERROR reminder #$id: no usable email template found, leaving pending");
            $failed++;
            continue;
        }

        $context = array(
            'title' => $reminder['title'],
            'task_title' => $reminder['title'],
            'description' => $reminder['description'],
            'due_date' => $reminder['title'],
            'user_name' => $reminder['recipient_user'] ?: 'User',
        );
        $subject = $template['subject'];
        $body = $template['body'];
        foreach ($context as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        if ($DRY_RUN) {
            log_cron("DRY-RUN would send reminder #$id to {$reminder['recipient_email']}: $subject");
            $sent++;
            continue;
        }

        $result_sent = sendEmail($reminder['recipient_email'], $subject, $body);

        if (!empty($result_sent['success'])) {
            db_write($db,
                "UPDATE ws_reminders SET status = 'sent', sent_at = NOW() WHERE id = $id", "mark_sent_$id");
            db_write($db,
                "INSERT INTO ws_email_log (reminder_id, recipient_email, recipient_name, subject, status, sent_at)
                 VALUES ($id, '" . $db->real_escape_string($reminder['recipient_email']) . "',
                         '" . $db->real_escape_string($reminder['recipient_user'] ?? '') . "',
                         '" . $db->real_escape_string($subject) . "', 'sent', NOW())",
                "log_sent_$id");
            $sent++;
            log_cron("Sent reminder #$id to {$reminder['recipient_email']}: $subject");
        } else {
            db_write($db,
                "INSERT INTO ws_email_log (reminder_id, recipient_email, recipient_name, subject, status, error_message, sent_at)
                 VALUES ($id, '" . $db->real_escape_string($reminder['recipient_email']) . "',
                         '" . $db->real_escape_string($reminder['recipient_user'] ?? '') . "',
                         '" . $db->real_escape_string($subject) . "', 'failed',
                         '" . $db->real_escape_string(substr((string)($result_sent['error'] ?? 'unknown'), 0, 500)) . "', NOW())",
                "log_failed_$id");
            $failed++;
            log_cron("FAILED reminder #$id to {$reminder['recipient_email']}: " . ($result_sent['error'] ?? 'unknown error'));
        }
    }

    return array('processed' => $processed, 'sent' => $sent, 'failed' => $failed);
}

// ============================================
// Main
// ============================================

$today = date('Y-m-d');
$useCards = using_cards($db);
log_cron(($useCards ? 'Task source: workspace_cards' : 'Task source: workspace_tasks (legacy)')
    . ', date=' . $today);

$tasks = load_due_tasks($db, $useCards);
log_cron('Loaded ' . count($tasks) . ' open task(s) with a due date');

$ruleResult = process_email_rules($db, $tasks, $today);
log_cron("Processed {$ruleResult['rules']} email rules, created {$ruleResult['created']} reminder(s)");

$fireResult = fire_due_reminders($db);
log_cron("Fired {$fireResult['processed']} reminder(s): {$fireResult['sent']} sent, {$fireResult['failed']} failed");

log_cron('Completed reminder processing');
echo ($DRY_RUN ? "[DRY-RUN] no changes written, no mail sent. " : '')
    . "OK: processed {$ruleResult['created']} new reminder(s), sent {$fireResult['sent']}, failed {$fireResult['failed']}\n";