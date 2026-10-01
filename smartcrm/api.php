<?php
// Simple JSON API for SmartCRM Kanban (standalone).
$PageSecurity = 0;    // align with SmartERPlims default
$AllowAnyone  = false; // require login/session tokens
$PathPrefix = './';

include($PathPrefix . 'includes/session.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');
include($PathPrefix . 'includes/crm_scope.php');

// DB_query() writes an audit-trail row on the same connection after every
// INSERT/UPDATE/DELETE. audittrail has no auto-increment column, so that
// second INSERT resets $db->insert_id to 0 and the real id is lost.
// LAST_INSERT_ID() still reports the last auto-generated value, so read it
// that way immediately after an INSERT.
// (Named crm_* to avoid clashing with the app's dead DB_Last_Insert_ID().)
function crm_last_insert_id($db, $default = null) {
    $res = @$db->query("SELECT LAST_INSERT_ID() AS id");
    if ($res === false) {
        return $default;
    }
    $row = $res->fetch_assoc();
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    return $id > 0 ? $id : $default;
}

$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/api_errors.log';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);

function log_api_error($message, array $context = array())
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $user = $_SESSION['UserID'] ?? 'guest';
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $contextText = $context ? json_encode($context) : '';
    error_log(sprintf("[%s] user=%s action=%s %s %s\n", $timestamp, $user, $action ?: '-', $message, $contextText), 3, $logFile);
}

set_exception_handler(function ($ex) {
    log_api_error('Uncaught exception: ' . $ex->getMessage(), array('trace' => $ex->getTraceAsString()));
    http_response_code(200);
    echo json_encode(array('error' => 'Internal server error'));
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
        log_api_error('Fatal error: ' . $error['message'], array('file' => $error['file'], 'line' => $error['line']));
    }
});

header('Content-Type: application/json');

$TaskstatusArray = array(
    "0" => 'Not yet begun',
    "1" => 'In progress',
    "2" => 'Almost Done',
    "3" => 'Taking Longer than expected',
    "4" => 'Complete'
);

/**
 * Find contact by company (case-insensitive). If missing, insert minimal row.
 * Returns array(contactId|null, existed:boolean).
 */
function find_or_create_contact($contactName)
{
    global $db;
    if ($contactName === '') {
        return array(null, false);
    }
    $safe = DB_escape_string($contactName);
    $SQL = "SELECT pkey FROM NewContacts WHERE LOWER(Company)=LOWER('$safe') LIMIT 1";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result !== false && DB_num_rows($Result) > 0) {
        $row = DB_fetch_array($Result);
        return array((int)$row['pkey'], true);
    }

    $owner = crm_user_id();
    if ($owner === '') {
        $insertSQL = "INSERT INTO NewContacts (Company) VALUES ('$safe')";
    } else {
        $safeOwner = DB_escape_string($owner);
        $insertSQL = "INSERT INTO NewContacts (Company, owner) VALUES ('$safe', '$safeOwner')";
    }
    $InsertResult = DB_query($insertSQL, $db, '', '', false, false);
    if ($InsertResult === false) {
        log_api_error('quick add contact failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $insertSQL));
        return array(null, false);
    }

        $newId = crm_last_insert_id($db);
        return array($newId ? (int)$newId : null, false);
}

/**
 * Fields an automation rule may use as a condition, per entity type.
 * Keep in sync with CONDITION_FIELDS in Automation.php.
 * Anything not listed here is ignored, so a rule can never probe arbitrary columns.
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

function crm_condition_compare($actual, $op, $expected) {
    $op = strtolower(trim((string)$op));
    if ($op === '') {
        $op = 'equals';
    }
    $a = ($actual === null) ? '' : trim((string)$actual);
    $e = ($expected === null) ? '' : trim((string)$expected);

    switch ($op) {
        case 'is_empty':     return $a === '';
        case 'not_empty':    return $a !== '';
        case 'equals':       return strcasecmp($a, $e) === 0;
        case 'not_equals':   return strcasecmp($a, $e) !== 0;
        case 'contains':     return $e !== '' && stripos($a, $e) !== false;
        case 'not_contains': return $e !== '' && stripos($a, $e) === false;
        case 'in':
        case 'not_in': {
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
        }
        case 'gt': case 'gte': case 'lt': case 'lte': {
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
        }
    }

    // Unknown operator: do not silently block the rule.
    return true;
}

/**
 * Empty/absent conditions mean "always run", which keeps every rule saved before
 * this feature working exactly as it did.
 */
function crm_rule_conditions_match($entity, $conditions, $entityType) {
    if (empty($conditions)) {
        return true;
    }
    if (is_object($conditions)) {
        $conditions = (array)$conditions;
    }

    $conds = array();
    if (isset($conditions['conditions']) && is_array($conditions['conditions'])) {
        $conds = $conditions['conditions'];
    } elseif (isset($conditions[0])) {
        $conds = $conditions;
    }
    if (!$conds) {
        return true;
    }

    $allowed  = crm_condition_fields($entityType);
    $anyMode  = isset($conditions['match']) && strtolower((string)$conditions['match']) === 'any';

    foreach ($conds as $c) {
        if (!is_array($c)) {
            continue;
        }
        $field = trim((string)($c['field'] ?? ''));
        if ($field === '' || !isset($allowed[$field]) || !array_key_exists($field, $entity)) {
            continue;
        }
        $hit = crm_condition_compare($entity[$field], $c['op'] ?? 'equals', $c['value'] ?? '');
        if ($anyMode) {
            if ($hit) {
                return true;
            }
        } elseif (!$hit) {
            return false;
        }
    }

    return !$anyMode;
}

function triggerAutomation($db, $triggerType, $entityType, $entityId) {
    $rules = array();
    $SQL = "SELECT * FROM crm_automation_rules WHERE is_active=1 AND trigger_type='" . DB_escape_string($triggerType) . "'";
    $Result = DB_query($SQL, $db, '', '', false, false);
    while ($row = DB_fetch_array($Result)) {
        $row['trigger_conditions'] = json_decode($row['trigger_conditions'], true);
        $row['actions'] = json_decode($row['actions'], true);
        $rules[] = $row;
    }
    
    if (empty($rules)) return;
    
    $entity = null;
    $table = $entityType === 'lead' ? 'crm_leads' : 'crm_opportunities';
    $SQL = "SELECT * FROM $table WHERE id=" . (int)$entityId . " LIMIT 1";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($row = DB_fetch_array($Result)) {
        $entity = $row;
    }
    
    if (!$entity) return;
    
    foreach ($rules as $rule) {
        if (!crm_rule_conditions_match($entity, $rule['trigger_conditions'], $entityType)) {
            DB_query("INSERT INTO crm_automation_log (rule_id, trigger_type, entity_type, entity_id, execution_result, executed_at)
                    VALUES (" . (int)$rule['id'] . ", '" . DB_escape_string($triggerType) . "', '$entityType', " . (int)$entityId . ", 'skipped:conditions', NOW())", $db);
            continue;
        }
        executeActions($db, $rule, $entity, $entityType);
        
        DB_query("INSERT INTO crm_automation_log (rule_id, trigger_type, entity_type, entity_id, execution_result, executed_at)
                VALUES (" . $rule['id'] . ", '" . DB_escape_string($triggerType) . "', '$entityType', " . (int)$entityId . ", 'success', NOW())", $db);
    }
}

function executeActions($db, $rule, $entity, $entityType) {
    $actions = $rule['actions'] ?? array();
    
    foreach ($actions as $act) {
        $type = $act['type'] ?? '';
        
        switch ($type) {
            case 'update_field':
                $table = $entityType === 'lead' ? 'crm_leads' : 'crm_opportunities';
                $allowed = $entityType === 'lead'
                    ? array('assigned_to', 'status', 'source', 'lead_score', 'notes')
                    : array('assigned_to', 'pipeline_stage_id', 'probability', 'expected_value', 'next_step', 'description');
                $field = $act['field'] ?? '';
                $value = $act['value'] ?? '';
                if ($field && in_array($field, $allowed, true) && $entity['id']) {
                    $SQL = "UPDATE $table SET $field='" . $db->real_escape_string($value) . "' WHERE id=" . (int)$entity['id'];
                    DB_query($SQL, $db, '', '', false, false);
                }
                break;
                
            case 'create_task':
                $title = $act['title'] ?? 'Follow-up Required';
                $dueDays = $act['due_days'] ?? 1;
                $details = $act['details'] ?? '';
                $assignedTo = $entity['assigned_to'] ?? 'admin';
                $SQL = "INSERT INTO Tasks (Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated)
                        VALUES ('" . $db->real_escape_string($title) . "', '0', '1', '$assignedTo', DATE_ADD(CURDATE(), INTERVAL $dueDays DAY), 
                        '" . $db->real_escape_string($details) . "', NOW())";
                DB_query($SQL, $db, '', '', false, false);
                break;
                
            case 'send_email':
                $to = $act['to'] ?? $entity['email'] ?? '';
                $subject = $act['subject'] ?? 'Notification';
                $body = $act['body'] ?? 'Notification from SmartCRM';
                $from = $act['from'] ?? 'noreply@smartcrm.com';
                
                if ($to) {
                    $subject = str_replace(['{{name}}', '{{company}}'], [$entity['contact_name'] ?? '', $entity['company_name'] ?? ''], $subject);
                    $body = str_replace(['{{name}}', '{{company}}'], [$entity['contact_name'] ?? '', $entity['company_name'] ?? ''], $body);
                    
                    $smtpConfigFile = __DIR__ . '/smtp_config.php';
                    if (file_exists($smtpConfigFile)) {
                        include($smtpConfigFile);
                        sendSMTPEmail($to, $subject, $body, $from, 'SmartCRM');
                    } else {
                        $headers = "From: $from\r\nContent-Type: text/html; charset=UTF-8\r\n";
                        @mail($to, $subject, $body, $headers);
                    }
                }
                break;
                
            case 'schedule_meeting':
                $title = $act['title'] ?? 'Meeting with {{company}}';
                $description = $act['description'] ?? 'Meeting scheduled from SmartCRM automation';
                $location = $act['location'] ?? '';
                $startDate = $act['start_date'] ?? '';
                $startTime = $act['start_time'] ?? '09:00';
                $duration = $act['duration'] ?? 60;
                
                if ($startDate) {
                    $title = str_replace(['{{name}}', '{{company}}'], [$entity['contact_name'] ?? '', $entity['company_name'] ?? ''], $title);
                    $description = str_replace(['{{name}}', '{{company}}'], [$entity['contact_name'] ?? '', $entity['company_name'] ?? ''], $description);
                    $location = str_replace(['{{name}}', '{{company}}'], [$entity['contact_name'] ?? '', $entity['company_name'] ?? ''], $location);
                    
                    $start = $startDate . ' ' . $startTime;
                    $end = date('Y-m-d H:i:s', strtotime("+{$duration} minutes", strtotime($start)));
                    $to = $entity['email'] ?? $act['to'] ?? '';
                    
                    $smtpConfigFile = __DIR__ . '/smtp_config.php';
                    if ($to && file_exists($smtpConfigFile)) {
                        include($smtpConfigFile);
                        sendICSInvite($to, $title, $description, $start, $end, $location);
                    }
                }
                break;
        }
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Send ICS calendar invite manually via API
 * POST { action: 'send_ics_invite', to, subject, description, start, end, location?, from?, fromName? }
 */
if ($action === 'send_ics_invite') {
    $to = $_POST['to'] ?? $_GET['to'] ?? '';
    $subject = $_POST['subject'] ?? $_GET['subject'] ?? '';
    $description = $_POST['description'] ?? $_GET['description'] ?? '';
    $start = $_POST['start'] ?? $_GET['start'] ?? '';
    $end = $_POST['end'] ?? $_GET['end'] ?? '';
    $location = $_POST['location'] ?? $_GET['location'] ?? '';
    $from = $_POST['from'] ?? $_GET['from'] ?? '';
    $fromName = $_POST['fromName'] ?? $_GET['fromName'] ?? '';

    if (empty($to) || empty($subject) || empty($start) || empty($end)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters: to, subject, start, end']);
        exit;
    }

    $smtpConfigFile = __DIR__ . '/smtp_config.php';
    if (file_exists($smtpConfigFile)) {
        include($smtpConfigFile);
        $result = sendICSInvite($to, $subject, $description, $start, $end, $location, $from, $fromName);
    } else {
        $result = ['success' => false, 'error' => 'SMTP config not found'];
    }

    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result);
    exit;
}

/**
 * Send email with optional ICS attachment
 * POST { action: 'send_email_with_ics', to, subject, body, start?, end?, location?, from?, fromName? }
 */
if ($action === 'send_email_with_ics') {
    $to = $_POST['to'] ?? $_GET['to'] ?? '';
    $subject = $_POST['subject'] ?? $_GET['subject'] ?? '';
    $body = $_POST['body'] ?? $_GET['body'] ?? '';
    $start = $_POST['start'] ?? $_GET['start'] ?? '';
    $end = $_POST['end'] ?? $_GET['end'] ?? '';
    $location = $_POST['location'] ?? $_GET['location'] ?? '';
    $from = $_POST['from'] ?? $_GET['from'] ?? '';
    $fromName = $_POST['fromName'] ?? $_GET['fromName'] ?? '';

    if (empty($to) || empty($subject) || empty($body)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters: to, subject, body']);
        exit;
    }

    $smtpConfigFile = __DIR__ . '/smtp_config.php';
    if (file_exists($smtpConfigFile)) {
        include($smtpConfigFile);
        
        if (!empty($start) && !empty($end)) {
            // Send with ICS attachment
            $icsContent = generateICS($subject, $body, $start, $end, $location, '', $from, $fromName);
            
            $mailerPath = __DIR__ . '/Mailer/class.phpmailer.php';
            if (file_exists($mailerPath)) {
                require_once $mailerPath;
                require_once __DIR__ . '/Mailer/class.smtp.php';
                
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = $smtp_config['host'];
                    $mail->Port = $smtp_config['port'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_config['username'];
                    $mail->Password = $smtp_config['password'];
                    $secure = strtolower($smtp_config['secure']);
                    if ($secure === 'ssl') $mail->SMTPSecure = 'ssl';
                    elseif ($secure === 'tls') $mail->SMTPSecure = 'tls';
                    
                    $mail->setFrom($from ?: $smtp_config['from_email'], $fromName ?: $smtp_config['from_name']);
                    $mail->addAddress($to);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->AltBody = strip_tags($body);
                    $mail->addStringAttachment($icsContent, 'invite.ics', 'base64', 'text/calendar; method=REQUEST; charset=UTF-8');
                    $mail->send();
                    $result = ['success' => true, 'error' => null];
                } catch (Exception $e) {
                    $result = ['success' => false, 'error' => $e->getMessage() . ' | PHPMailer: ' . $mail->ErrorInfo];
                }
            } else {
                $result = ['success' => false, 'error' => 'PHPMailer not found'];
            }
        } else {
            // Regular email
            $result = sendSMTPEmail($to, $subject, $body, $from, $fromName);
        }
    } else {
        $result = ['success' => false, 'error' => 'SMTP config not found'];
    }

    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result);
    exit;
}

/**
 * Test SMTP connection
 */
if ($action === 'test_smtp') {
    $smtpConfigFile = __DIR__ . '/smtp_config.php';
    if (file_exists($smtpConfigFile)) {
        include($smtpConfigFile);
        $result = testSMTPConnection();
    } else {
        $result = ['success' => false, 'message' => 'SMTP config not found'];
    }
    echo json_encode($result);
    exit;
}

function get_contact_by_id($id)
{
    global $db;
    $id = (int)$id;
    if ($id <= 0) return null;
    $SQL = "SELECT pkey, Company FROM NewContacts WHERE pkey=" . $id . " LIMIT 1";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result !== false && DB_num_rows($Result) > 0) {
        return DB_fetch_array($Result);
    }
    return null;
}

$scopeRaw  = crm_scope_raw();
$reportStalled = (strpos($scopeRaw, 'stalled') !== false);
$reportDue = (strpos($scopeRaw, 'due') !== false && !$reportStalled);
$stageDays = isset($_POST['stageDays']) ? (int)$_POST['stageDays'] : (isset($_GET['stageDays']) ? (int)$_GET['stageDays'] : 3);
$stageDays = $stageDays < 1 ? 1 : $stageDays;
$dueDays = isset($_POST['dueDays']) ? (int)$_POST['dueDays'] : (isset($_GET['dueDays']) ? (int)$_GET['dueDays'] : 7);
$dueDays = $dueDays < 1 ? 1 : $dueDays;

if ($action === 'listContacts') {
    $contacts = array();
    $SQL = "SELECT pkey, Company, owner FROM NewContacts"
        . crm_scope_where(array(crm_scope_owner_clause('owner')))
        . " ORDER BY Company ASC LIMIT 500";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('listContacts query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(200);
        echo json_encode(array('error' => 'Database error while listing contacts'));
        exit;
    }
    while ($row = DB_fetch_array($Result)) {
        $contacts[] = array(
            'id' => (int)$row['pkey'],
            'name' => $row['Company'],
            'owner' => $row['owner'],
        );
    }
    echo json_encode(array('data' => $contacts));
    exit;
}

if ($action === 'listUsers') {
    $users = array();
    $SQL = "SELECT userid, realname FROM www_users WHERE blocked = 0 ORDER BY realname ASC";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('listUsers query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(200);
        echo json_encode(array('error' => 'Database error while listing users'));
        exit;
    }
    while ($row = DB_fetch_array($Result)) {
        $users[] = array('id' => $row['userid'], 'name' => $row['realname']);
    }
    echo json_encode(array('data' => $users));
    exit;
}

if ($action === 'getContact') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(array('error' => 'Missing contact id'));
        exit;
    }

    $SQL = "SELECT * FROM newcontacts"
        . crm_scope_where(array('pkey=' . $id, crm_scope_owner_clause('owner')))
        . " LIMIT 1";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false || DB_num_rows($Result) === 0) {
        echo json_encode(array('error' => 'Contact not found'));
        exit;
    }

    $row = DB_fetch_array($Result);
    echo json_encode(array('data' => $row));
    exit;
}

if ($action === 'saveContact') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $company = trim($_POST['company'] ?? $_POST['company_name'] ?? '');

    if ($id > 0) {
        $existsSQL = "SELECT pkey FROM newcontacts WHERE pkey=" . $id
            . crm_scope_where(array(crm_scope_owner_clause('owner')))
            . ' LIMIT 1';
        $ExistsResult = DB_query($existsSQL, $db, '', '', false, false);
        if ($ExistsResult === false || DB_num_rows($ExistsResult) === 0) {
            echo json_encode(array('error' => 'Contact not found'));
            exit;
        }
    } elseif ($company === '') {
        echo json_encode(array('error' => 'Contact name is required'));
        exit;
    }

    $updates = array();
    if (isset($_POST['company'])) {
        $updates[] = "Company='" . DB_escape_string($company) . "'";
    }
    foreach (array('industry', 'lead_source', 'contact_status') as $optional) {
        if (isset($_POST[$optional])) {
            $updates[] = $optional . "='" . DB_escape_string(trim($_POST[$optional])) . "'";
        }
    }

    if ($id > 0) {
        if (empty($updates)) {
            echo json_encode(array('error' => 'Nothing to update'));
            exit;
        }
        $SQL = "UPDATE newcontacts SET " . implode(', ', $updates) . " WHERE pkey=" . $id;
        $Result = DB_query($SQL, $db, '', '', false, false);
        if ($Result === false) {
            log_api_error('saveContact update failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
            echo json_encode(array('error' => 'Database error while saving contact'));
            exit;
        }
        echo json_encode(array('ok' => true, 'id' => $id));
        exit;
    }

    $owner = crm_user_id();
    $columns = array('Company');
    $values = array("'" . DB_escape_string($company) . "'");
    if ($owner !== '') {
        $columns[] = 'owner';
        $values[] = "'" . DB_escape_string($owner) . "'";
    }
    foreach (array('industry', 'lead_source', 'contact_status') as $optional) {
        if (isset($_POST[$optional])) {
            $columns[] = $optional;
            $values[] = "'" . DB_escape_string(trim($_POST[$optional])) . "'";
        }
    }

    $SQL = "INSERT INTO newcontacts (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('saveContact insert failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        echo json_encode(array('error' => 'Database error while saving contact'));
        exit;
    }

    $newId = crm_last_insert_id($db);
    echo json_encode(array('ok' => true, 'id' => $newId ? (int)$newId : null));
    exit;
}

if ($action === 'createActivity') {
    $title    = trim($_POST['title'] ?? '');
    $owner    = trim($_POST['owner'] ?? '');
    $status   = trim($_POST['status'] ?? '0');
    $priority = trim($_POST['priority'] ?? '1');
    $due      = trim($_POST['due'] ?? '');
    $details  = trim($_POST['details'] ?? '');
    $contact  = trim($_POST['contact'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $value    = trim($_POST['value'] ?? '');
    $fromdate = trim($_POST['fromdue'] ?? '');
    $todate   = trim($_POST['todue'] ?? '');
    $actionType = trim($_POST['action_type'] ?? '');
    $relatedId  = isset($_POST['related_id']) ? (int)$_POST['related_id'] : 0;
    $relatedTitle = trim($_POST['related_title'] ?? '');
    $contactIdInput = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;
    $recurEvery = isset($_POST['recur_every']) ? (int)$_POST['recur_every'] : 0;
    $recurUnit  = trim($_POST['recur_unit'] ?? '');
    $recurCount = isset($_POST['recur_count']) && $_POST['recur_count'] !== '' ? (int)$_POST['recur_count'] : null;
    $recurUntil = trim($_POST['recur_until'] ?? '');

    if ($title === '' || !array_key_exists($status, $TaskstatusArray)) {
        http_response_code(200);
        echo json_encode(array('error' => 'Missing or invalid title/status'));
        exit;
    }

    $safeTitle    = DB_escape_string($title);
    $safeOwner    = DB_escape_string($owner ?: ($_SESSION['UserID'] ?? ''));
    $safeStatus   = (int)$status;
    $safePriority = (int)$priority;
    $safeDue      = $due !== '' ? DB_escape_string($due) : null;

    // Quick add contact into NewContacts if provided.
    $contactId = null;
    $contactExisted = false;
    if ($contactIdInput > 0) {
        $existing = get_contact_by_id($contactIdInput);
        if ($existing) {
            $contactId = (int)$existing['pkey'];
            $contactExisted = true;
            if ($contact === '') {
                $contact = $existing['Company'];
            }
        }
    } elseif ($contact !== '') {
        list($contactId, $contactExisted) = find_or_create_contact($contact);
    }

    $composedDetails = $details;
    if ($actionType !== '')  $composedDetails = '[Action] ' . $actionType . "\n" . $composedDetails;
    if ($location !== '')  $composedDetails .= "\nLocation: " . $location;
    if ($contact !== '')   $composedDetails .= "\nContact: " . $contact;
    if ($value !== '')     $composedDetails .= "\nValue: " . $value;
    if ($fromdate !== '' || $todate !== '') {
        $composedDetails .= "\nWindow: " . trim($fromdate . ' - ' . $todate);
    }
    if ($contactId !== null) {
        $composedDetails .= "\nContact ID: #" . $contactId . ($contactExisted ? ' (existing)' : ' (new)');
    }
    if ($relatedId > 0) {
        $composedDetails .= "\nRelated to task #" . $relatedId;
        if ($relatedTitle !== '') {
            $composedDetails .= " (" . $relatedTitle . ")";
        }
    }
    $safeDetails = DB_escape_string($composedDetails);

    $SQL = sprintf(
        "INSERT INTO Tasks (Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated) VALUES ('%s','%s','%s','%s',%s,'%s',NOW())",
        $safeTitle,
        $safeStatus,
        $safePriority,
        $safeOwner,
        $safeDue === null ? 'NULL' : "'$safeDue'",
        $safeDetails
    );

    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('createActivity insert failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(200);
        echo json_encode(array('error' => 'Database error while creating activity'));
        exit;
    }

    $newId = crm_last_insert_id($db);

    // Optional recurrence registration
    $allowedUnits = array('hour','day','week','month');
    if ($recurEvery > 0 && in_array($recurUnit, $allowedUnits, true) && $newId) {
        try {
            $baseDate = $due !== '' ? new DateTime($due) : new DateTime('now');
            switch ($recurUnit) {
                case 'hour':
                    $intervalSpec = 'PT' . $recurEvery . 'H';
                    break;
                case 'day':
                    $intervalSpec = 'P' . $recurEvery . 'D';
                    break;
                case 'week':
                    $intervalSpec = 'P' . $recurEvery . 'W';
                    break;
                case 'month':
                default:
                    $intervalSpec = 'P' . $recurEvery . 'M';
                    break;
            }
            $dtNext = clone $baseDate;
            $dtNext->add(new DateInterval($intervalSpec));
            $nextRun = $dtNext->format('Y-m-d H:i:s');
            $untilSql = $recurUntil !== '' ? "'" . DB_escape_string($recurUntil) . "'" : 'NULL';
            $countSql = $recurCount !== null ? (int)$recurCount : 'NULL';
            $recSQL = sprintf(
                "INSERT INTO RecurringActivities (task_id, every_n, unit, max_count, until_date, next_run) VALUES ('%d','%d','%s',%s,%s,'%s')",
                (int)$newId,
                (int)$recurEvery,
                DB_escape_string($recurUnit),
                $countSql,
                $untilSql,
                DB_escape_string($nextRun)
            );
            DB_query($recSQL, $db, '', '', false, false);
        } catch (Exception $e) {
            log_api_error('createActivity recurrence failed: ' . $e->getMessage(), array('task_id' => $newId));
        }
    }

    echo json_encode(array('ok' => true, 'id' => (int)$newId));
    exit;
}

if ($action === 'listTasks') {
    $tasks = array();
    $whereParts = array();
    $ownerClause = crm_scope_owner_clause('TaskOwner');
    if ($ownerClause !== '') {
        $whereParts[] = $ownerClause;
    }
    if ($reportStalled) {
        $whereParts[] = "Status <> 4";
        $whereParts[] = "TIMESTAMPDIFF(DAY, COALESCE(lastactivity, datecreated), NOW()) >= " . (int)$stageDays;
    } elseif ($reportDue) {
        $whereParts[] = "Status <> 4";
        $whereParts[] = "datedue IS NOT NULL";
        $whereParts[] = "datedue <= DATE_ADD(CURDATE(), INTERVAL " . (int)$dueDays . " DAY)";
    }
    $where = '';
    if (!empty($whereParts)) {
        $where = 'WHERE ' . implode(' AND ', $whereParts);
    }

    $selectExtra = '';
    if ($reportStalled) {
        $selectExtra = ", TIMESTAMPDIFF(DAY, COALESCE(lastactivity, datecreated), NOW()) AS days_in_stage";
    } elseif ($reportDue) {
        $selectExtra = ", DATEDIFF(datedue, CURDATE()) AS days_to_due";
    }

    $SQL = "SELECT pkey, Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated, lastactivity
            $selectExtra
            FROM Tasks
            $where
            ORDER BY datecreated DESC
            LIMIT 200";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('listTasks query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(200);
        echo json_encode(array('error' => 'Database error while listing tasks'));
        exit;
    }
    while ($row = DB_fetch_array($Result)) {
        $tasks[] = array(
            'id'        => (int)$row['pkey'],
            'title'     => $row['Taskname'],
            'status'    => (string)$row['Status'],
            'priority'  => (string)$row['Priority'],
            'owner'     => $row['TaskOwner'],
            'due'       => $row['datedue'],
            'details'   => $row['taskdetails'],
            'created'   => $row['datecreated'],
            'daysInStage' => isset($row['days_in_stage']) ? (int)$row['days_in_stage'] : null,
            'daysToDue' => isset($row['days_to_due']) ? (int)$row['days_to_due'] : null
        );
    }
    echo json_encode(array('data' => $tasks));
    exit;
}

if ($action === 'updateTaskStatus') {
    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : null;

    if ($id <= 0 || $status === null || !array_key_exists($status, $TaskstatusArray)) {
        http_response_code(200);
        echo json_encode(array('error' => 'Invalid id or status'));
        exit;
    }

    $SQL = sprintf(
        "UPDATE Tasks SET Status='%s', lastactivity=NOW() WHERE pkey='%s'",
        (int)$status,
        $id
    );
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('updateTaskStatus query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL, 'id' => $id, 'status' => $status));
        http_response_code(200);
        echo json_encode(array('error' => 'Database error while updating task status'));
        exit;
    }

    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'saveTask') {
    $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title     = isset($_POST['title']) ? trim($_POST['title']) : '';
    $status    = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    $priority  = isset($_POST['priority']) ? (int)$_POST['priority'] : 1;
    $duedate   = isset($_POST['duedate']) ? $_POST['duedate'] : null;
    $details   = isset($_POST['details']) ? $_POST['details'] : '';

    if ($title === '') {
        http_response_code(200);
        echo json_encode(array('error' => 'Task name is required'));
        exit;
    }

    $userID = $_SESSION['UserID'] ?? 'admin';

    if ($id > 0) {
        $SQL = sprintf(
            "UPDATE Tasks SET Taskname='%s', Status='%s', Priority='%s', datedue='%s', taskdetails='%s', lastactivity=NOW() WHERE pkey='%s'",
            DB_escape_string($title),
            (int)$status,
            (int)$priority,
            $duedate ? DB_escape_string($duedate) : 'NULL',
            DB_escape_string($details),
            $id
        );
    } else {
        $SQL = sprintf(
            "INSERT INTO Tasks (Taskname, Status, Priority, datedue, taskdetails, TaskOwner, datecreated, lastactivity) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', NOW(), NOW())",
            DB_escape_string($title),
            (int)$status,
            (int)$priority,
            $duedate ? DB_escape_string($duedate) : 'NULL',
            DB_escape_string($details),
            DB_escape_string($userID)
        );
    }

    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('saveTask query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(200);
        echo json_encode(array('error' => 'Database error while saving task'));
        exit;
    }

    $newId = $id > 0 ? $id : crm_last_insert_id($db, 0);
    echo json_encode(array('ok' => true, 'id' => $newId));
    exit;
}

// ============================================
// LEAD MANAGEMENT API
// ============================================

$LeadStatusArray = array('new', 'contacted', 'qualified', 'proposal', 'negotiation', 'converted', 'lost');

if ($action === 'listLeads') {
    $search = $_POST['search'] ?? $_GET['search'] ?? '';
    $status = $_POST['status'] ?? $_GET['status'] ?? '';
    $assignedTo = $_POST['assigned_to'] ?? $_GET['assigned_to'] ?? '';
    $source = $_POST['source'] ?? $_GET['source'] ?? '';
    $mineOnly = isset($_POST['mine']) || isset($_GET['mine']);
    
    $whereParts = array();
    if ($search !== '') {
        $safeSearch = DB_escape_string($search);
        $whereParts[] = "(company_name LIKE '%$safeSearch%' OR contact_name LIKE '%$safeSearch%' OR email LIKE '%$safeSearch%')";
    }
    if ($status !== '') {
        $whereParts[] = "status='" . DB_escape_string($status) . "'";
    }
    if ($source !== '') {
        $whereParts[] = "source='" . DB_escape_string($source) . "'";
    }
    if ($assignedTo !== '') {
        $whereParts[] = "assigned_to='" . DB_escape_string($assignedTo) . "'";
    }
    $ownerClause = $mineOnly
        ? crm_owner_clause(crm_user_id(), 'assigned_to', false)
        : crm_scope_owner_clause('assigned_to');
    if ($ownerClause !== '') {
        $whereParts[] = $ownerClause;
    }
    
    $where = empty($whereParts) ? '' : 'WHERE ' . implode(' AND ', $whereParts);
    $SQL = "SELECT * FROM crm_leads $where ORDER BY created_at DESC LIMIT 200";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('listLeads query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db)));
        http_response_code(200);
        echo json_encode(array('error' => 'Database error'));
        exit;
    }
    $leads = array();
    while ($row = DB_fetch_array($Result)) {
        $leads[] = array(
            'id' => (int)$row['id'],
            'company_name' => $row['company_name'],
            'contact_name' => $row['contact_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'mobile' => $row['mobile'],
            'website' => $row['website'],
            'industry' => $row['industry'],
            'source' => $row['source'],
            'source_details' => $row['source_details'],
            'status' => $row['status'],
            'assigned_to' => $row['assigned_to'],
            'lead_score' => (int)$row['lead_score'],
            'notes' => $row['notes'],
            'address' => $row['address'],
            'city' => $row['city'],
            'country' => $row['country'],
            'pin_vat' => $row['pin_vat'],
            'next_followup' => $row['next_followup'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        );
    }
    echo json_encode(array('data' => $leads));
    exit;
}

if ($action === 'getLead') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Missing lead id'));
        exit;
    }
    $SQL = "SELECT * FROM crm_leads"
        . crm_scope_where(array('id=' . $id, crm_scope_owner_clause('assigned_to')))
        . ' LIMIT 1';
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false || DB_num_rows($Result) === 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Lead not found'));
        exit;
    }
    $row = DB_fetch_array($Result);
    echo json_encode(array('data' => $row));
    exit;
}

if ($action === 'saveLead') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $fields = array(
        'company_name', 'contact_name', 'email', 'phone', 'mobile', 'website',
        'industry', 'source', 'source_details', 'status', 'assigned_to',
        'lead_score', 'notes', 'address', 'city', 'country', 'pin_vat',
        'next_followup'
    );
    $data = array();
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        if ($val === '') {
            $data[$f] = 'NULL';
        } else {
            $data[$f] = DB_escape_string($val);
        }
    }
    $user = $_SESSION['UserID'] ?? '';
    $now = date('Y-m-d H:i:s');
    
    if ($id > 0) {
        $setParts = array();
        foreach ($data as $col => $val) {
            if ($val !== 'NULL') {
                $setParts[] = "`$col`='" . $val . "'";
            } else {
                $setParts[] = "`$col`=NULL";
            }
        }
        $setParts[] = "`updated_at`='" . DB_escape_string($now) . "'";
        $SQL = "UPDATE crm_leads SET " . implode(',', $setParts) . " WHERE id=" . $id;
        $Result = DB_query($SQL, $db, '', '', false, false);
        if ($Result === false) {
            http_response_code(200);
            echo json_encode(array('error' => 'Failed to update lead'));
            exit;
        }
        echo json_encode(array('ok' => true, 'id' => $id));
        exit;
    }
    
    $cols = implode(',', array_keys($data)) . ", created_by, created_at, updated_at";
    $vals = '';
    foreach ($data as $v) {
        $vals .= ($vals ? ',' : '') . ($v === 'NULL' ? 'NULL' : "'" . $v . "'");
    }
    $vals .= ", '" . DB_escape_string($user) . "', '" . DB_escape_string($now) . "', '" . DB_escape_string($now) . "'";
    $SQL = "INSERT INTO crm_leads ($cols) VALUES ($vals)";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('saveLead insert failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(200);
        echo json_encode(array('error' => 'Failed to create lead: ' . DB_error_msg($db)));
        exit;
    }
    $newId = crm_last_insert_id($db);
    
    // Fire automation trigger: lead_created
    if ($newId) {
        triggerAutomation($db, 'lead_created', 'lead', $newId);
    }
    
    echo json_encode(array('ok' => true, 'id' => (int)$newId));
    exit;
}

if ($action === 'importLeads') {
    // Check if file was uploaded
    if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(array('ok' => false, 'error' => 'No file uploaded or upload error'));
        exit;
    }
    
    $file = $_FILES['importFile'];
    $allowedMimes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel' // .xls
    ];
    
    if (!in_array($file['type'], $allowedMimes) && !preg_match('/\.(xlsx|xls)$/i', $file['name'])) {
        http_response_code(400);
        echo json_encode(array('ok' => false, 'error' => 'Invalid file type. Please upload .xlsx or .xls file'));
        exit;
    }
    
    // Get import options
    $defaultSource = trim($_POST['default_source'] ?? '');
    $defaultStatus = trim($_POST['default_status'] ?? '');
    $assignToMe = isset($_POST['assign_to_me']) && $_POST['assign_to_me'] === '1';
    $skipHeader = isset($_POST['skip_header']) && $_POST['skip_header'] === '1';
    
    // Current user for assignment
    $currentUser = $_SESSION['UserID'] ?? '';
    $assignedTo = $assignToMe && $currentUser ? $currentUser : '';
    
    // Move uploaded file to temp location
    $tmpFile = sys_get_temp_dir() . '/leads_import_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
        http_response_code(500);
        echo json_encode(array('ok' => false, 'error' => 'Failed to save uploaded file'));
        exit;
    }
    
    // Read Excel file - try PhpSpreadsheet first, then fallback
    $spreadsheet = null;
    $reader = null;
    
    // Check if PhpSpreadsheet is available
    $vendorAutoload = __DIR__ . '/vendor/autoload.php';
    
    // Try to load PhpSpreadsheet
    $spreadsheetError = '';
    if (!file_exists($vendorAutoload)) {
        $spreadsheetError = 'vendor/autoload.php not found at: ' . $vendorAutoload;
    } else {
        require_once $vendorAutoload;
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            $spreadsheetError = 'PhpOffice\PhpSpreadsheet\IOFactory class not found after loading autoloader';
        } else {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpFile);
                $spreadsheet = $reader->load($tmpFile);
            } catch (\Exception $e) {
                $spreadsheetError = 'Error reading file: ' . $e->getMessage();
            }
        }
    }

    if (!$spreadsheet) {
        @unlink($tmpFile);
        http_response_code(500);
        echo json_encode(array('ok' => false, 'error' => $spreadsheetError ?: 'Failed to load spreadsheet'));
        exit;
    }
    
    $sheet = $spreadsheet->getActiveSheet();
    $rows = [];
    
    foreach ($sheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = trim($cell->getValue());
        }
        $rows[] = $rowData;
    }
    
    if (empty($rows)) {
        @unlink($tmpFile);
        http_response_code(400);
        echo json_encode(array('ok' => false, 'error' => 'Empty spreadsheet'));
        exit;
    }
    
    // Get headers
    $headers = array_map('strtolower', array_map('trim', $rows[0]));
    $startRow = $skipHeader ? 1 : 0;
    
    // Map column names to database fields
    $fieldMap = [
        'company_name' => 'company_name',
        'contact_name' => 'contact_name',
        'email' => 'email',
        'phone' => 'phone',
        'mobile' => 'mobile',
        'website' => 'website',
        'industry' => 'industry',
        'source' => 'source',
        'source_details' => 'source_details',
        'status' => 'status',
        'lead_score' => 'lead_score',
        'notes' => 'notes',
        'address' => 'address',
        'city' => 'city',
        'country' => 'country',
        'pin_vat' => 'pin_vat',
        'next_followup' => 'next_followup',
        // Alternative header names
        'company' => 'company_name',
        'contact' => 'contact_name',
        'company name' => 'company_name',
        'contact name' => 'contact_name',
        'lead source' => 'source',
        'lead status' => 'status',
        'lead score' => 'lead_score',
        'next followup' => 'next_followup',
        'next follow up' => 'next_followup',
        'pin' => 'pin_vat',
        'vat' => 'pin_vat',
    ];
    
    // Build column index map
    $colMap = [];
    foreach ($headers as $idx => $header) {
        if (isset($fieldMap[$header])) {
            $colMap[$idx] = $fieldMap[$header];
        }
    }
    
    if (!isset($colMap[array_search('company_name', $colMap)])) {
        // Check if company_name column exists
        $hasCompany = false;
        foreach ($colMap as $dbField) {
            if ($dbField === 'company_name') { $hasCompany = true; break; }
        }
        if (!$hasCompany) {
            @unlink($tmpFile);
            http_response_code(400);
            echo json_encode(array('ok' => false, 'error' => 'Required column "company_name" not found in spreadsheet'));
            exit;
        }
    }
    
    $imported = 0;
    $errors = 0;
    $errorDetails = [];
    $now = date('Y-m-d H:i:s');
    
    // Process each data row
    for ($i = $startRow; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (empty(array_filter($row))) continue; // Skip empty rows
        
        $leadData = [];
        foreach ($colMap as $colIdx => $dbField) {
            $val = isset($row[$colIdx]) ? trim($row[$colIdx]) : '';
            if ($val !== '') {
                $leadData[$dbField] = DB_escape_string($val);
            }
        }
        
        // Skip if no company name
        if (empty($leadData['company_name'])) {
            $errors++;
            $errorDetails[] = "Row " . ($i + 1) . ": Missing company_name";
            continue;
        }
        
        // Apply defaults
        if (empty($leadData['source']) && $defaultSource) {
            $leadData['source'] = DB_escape_string($defaultSource);
        }
        if (empty($leadData['status']) && $defaultStatus) {
            $leadData['status'] = DB_escape_string($defaultStatus);
        } elseif (empty($leadData['status'])) {
            $leadData['status'] = 'new';
        }
        
        // Validate status
        if (!in_array($leadData['status'], $LeadStatusArray)) {
            $leadData['status'] = 'new';
        }
        
        // Validate and convert next_followup date
        if (isset($leadData['next_followup']) && $leadData['next_followup'] !== '') {
            $dateVal = $leadData['next_followup'];
            // Excel stores dates as numeric serials - convert them
            if (is_numeric($dateVal)) {
                $dateVal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dateVal)->format('Y-m-d');
            } else {
                // Try to parse string date
                $ts = strtotime($dateVal);
                $dateVal = $ts ? date('Y-m-d', $ts) : '';
            }
            if ($dateVal) {
                $leadData['next_followup'] = DB_escape_string($dateVal);
            } else {
                unset($leadData['next_followup']);
            }
        }
        
        // Validate lead_score
        if (isset($leadData['lead_score'])) {
            $leadData['lead_score'] = (int)$leadData['lead_score'];
        }
        
        // Assign to current user if option selected
        if ($assignedTo) {
            $leadData['assigned_to'] = DB_escape_string($assignedTo);
        }
        
        // Build insert
        $leadData['created_by'] = DB_escape_string($currentUser);
        $leadData['created_at'] = $now;
        $leadData['updated_at'] = $now;
        
        $cols = implode(',', array_keys($leadData));
        $vals = '';
        foreach ($leadData as $v) {
            $vals .= ($vals ? ',' : '') . ($v === 'NULL' ? 'NULL' : "'" . $v . "'");
        }
        
        $SQL = "INSERT INTO crm_leads ($cols) VALUES ($vals)";
        $Result = DB_query($SQL, $db, '', '', false, false);
        
        if ($Result !== false) {
            $newId = crm_last_insert_id($db, 0);
            if ($newId) {
                triggerAutomation($db, 'lead_created', 'lead', $newId);
            }
            $imported++;
        } else {
            $errors++;
            $errorDetails[] = "Row " . ($i + 1) . ": " . DB_error_msg($db);
        }
    }
    
    // Cleanup
    @unlink($tmpFile);
    
    // Return results
    $response = array(
        'ok' => true,
        'imported' => $imported,
        'errors' => $errors
    );
    
    if (!empty($errorDetails)) {
        $response['error_details'] = $errorDetails;
    }
    
    echo json_encode($response);
    exit;
}

if ($action === 'updateLeadStatus') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    if ($id <= 0 || $status === '' || !in_array($status, $LeadStatusArray)) {
        http_response_code(200);
        echo json_encode(array('error' => 'Invalid id or status'));
        exit;
    }
    
    // Get old status before update
    $oldSQL = "SELECT status FROM crm_leads WHERE id=" . $id;
    $oldResult = DB_query($oldSQL, $db, '', '', false, false);
    $oldRow = DB_fetch_array($oldResult);
    $oldStatus = $oldRow['status'] ?? '';
    
    $SQL = "UPDATE crm_leads SET status='" . DB_escape_string($status) . "', updated_at=NOW() WHERE id=" . $id;
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        http_response_code(200);
        echo json_encode(array('error' => 'Failed to update lead status'));
        exit;
    }
    
    // Fire automation trigger: lead_status_changed
    if ($oldStatus && $oldStatus !== $status) {
        triggerAutomation($db, 'lead_status_changed', 'lead', $id);
    }
    
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'deleteLead') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Invalid id'));
        exit;
    }
    $SQL = "DELETE FROM crm_leads WHERE id=" . $id;
    $Result = DB_query($SQL, $db, '', '', false, false);
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'listLeadStatuses') {
    $SQL = "SELECT * FROM crm_lead_statuses ORDER BY status_order";
    $Result = DB_query($SQL, $db, '', '', false, false);
    $statuses = array();
    while ($row = DB_fetch_array($Result)) {
        $statuses[] = $row;
    }
    echo json_encode(array('data' => $statuses));
    exit;
}

if ($action === 'listLeadSources') {
    $SQL = "SELECT * FROM crm_lead_sources WHERE is_active=1";
    $Result = DB_query($SQL, $db, '', '', false, false);
    $sources = array();
    while ($row = DB_fetch_array($Result)) {
        $sources[] = $row;
    }
    echo json_encode(array('data' => $sources));
    exit;
}

// ============================================
// OPPORTUNITY API
// ============================================

if ($action === 'listOpportunities') {
    $search = $_POST['search'] ?? $_GET['search'] ?? '';
    $stageId = $_POST['stage_id'] ?? $_GET['stage_id'] ?? '';
    $assignedTo = $_POST['assigned_to'] ?? $_GET['assigned_to'] ?? '';
    $mineOnly = isset($_POST['mine']) || isset($_GET['mine']);
    
    $whereParts = array();
    if ($search !== '') {
        $safeSearch = DB_escape_string($search);
        $whereParts[] = "(opportunity_name LIKE '%$safeSearch%')";
    }
    if ($stageId !== '') {
        $whereParts[] = "pipeline_stage_id=" . (int)$stageId;
    }
    if ($assignedTo !== '') {
        $whereParts[] = "o.assigned_to='" . DB_escape_string($assignedTo) . "'";
    }
    $ownerClause = $mineOnly
        ? crm_owner_clause(crm_user_id(), 'o.assigned_to', false)
        : crm_scope_owner_clause('o.assigned_to');
    if ($ownerClause !== '') {
        $whereParts[] = $ownerClause;
    }
    
    $where = empty($whereParts) ? '' : 'WHERE ' . implode(' AND ', $whereParts);
    $SQL = "SELECT o.*, s.stage_name, s.probability as stage_probability 
            FROM crm_opportunities o 
            LEFT JOIN crm_pipeline_stages s ON o.pipeline_stage_id = s.id 
            $where ORDER BY o.created_at DESC LIMIT 200";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        http_response_code(200);
        echo json_encode(array('error' => 'Database error'));
        exit;
    }
    $opps = array();
    while ($row = DB_fetch_array($Result)) {
        $opps[] = array(
            'id' => (int)$row['id'],
            'opportunity_name' => $row['opportunity_name'],
            'lead_id' => $row['lead_id'],
            'contact_id' => $row['contact_id'],
            'pipeline_stage_id' => $row['pipeline_stage_id'],
            'stage_name' => $row['stage_name'],
            'stage_probability' => $row['stage_probability'],
            'assigned_to' => $row['assigned_to'],
            'expected_value' => $row['expected_value'],
            'probability' => (int)$row['probability'],
            'expected_close_date' => $row['expected_close_date'],
            'actual_close_date' => $row['actual_close_date'],
            'closed_value' => $row['closed_value'],
            'won_reason' => $row['won_reason'],
            'lost_reason' => $row['lost_reason'],
            'competitor' => $row['competitor'],
            'description' => $row['description'],
            'next_step' => $row['next_step'],
            'created_at' => $row['created_at']
        );
    }
    echo json_encode(array('data' => $opps));
    exit;
}

if ($action === 'getOpportunity') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Missing opportunity id'));
        exit;
    }
    $SQL = "SELECT o.*, s.stage_name, s.probability as stage_probability 
            FROM crm_opportunities o 
            LEFT JOIN crm_pipeline_stages s ON o.pipeline_stage_id = s.id "
        . crm_scope_where(array('o.id=' . $id, crm_scope_owner_clause('o.assigned_to')))
        . ' LIMIT 1';
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false || DB_num_rows($Result) === 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Opportunity not found'));
        exit;
    }
    $row = DB_fetch_array($Result);
    echo json_encode(array('data' => $row));
    exit;
}

if ($action === 'saveOpportunity') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $fields = array(
        'opportunity_name', 'lead_id', 'contact_id', 'pipeline_stage_id', 'assigned_to',
        'expected_value', 'probability', 'expected_close_date', 'actual_close_date',
        'closed_value', 'won_reason', 'lost_reason', 'competitor', 'description', 'next_step'
    );
    $data = array();
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        if ($val === '') {
            $data[$f] = 'NULL';
        } else {
            if ($f === 'expected_value' || $f === 'closed_value' || $f === 'probability') {
                $val = is_numeric($val) ? $val : 0;
            }
            $data[$f] = DB_escape_string($val);
        }
    }
    $user = $_SESSION['UserID'] ?? '';
    $now = date('Y-m-d H:i:s');
    
    if ($id > 0) {
        $setParts = array();
        foreach ($data as $col => $val) {
            if ($val !== 'NULL') {
                $setParts[] = "`$col`='" . $val . "'";
            } else {
                $setParts[] = "`$col`=NULL";
            }
        }
        $setParts[] = "`updated_at`='" . DB_escape_string($now) . "'";
        $SQL = "UPDATE crm_opportunities SET " . implode(',', $setParts) . " WHERE id=" . $id;
        $Result = DB_query($SQL, $db, '', '', false, false);
        if ($Result === false) {
            http_response_code(200);
            echo json_encode(array('error' => 'Failed to update opportunity'));
            exit;
        }
        echo json_encode(array('ok' => true, 'id' => $id));
        exit;
    }
    
    $cols = implode(',', array_keys($data)) . ", created_by, created_at, updated_at";
    $vals = '';
    foreach ($data as $v) {
        $vals .= ($vals ? ',' : '') . ($v === 'NULL' ? 'NULL' : "'" . $v . "'");
    }
    $vals .= ", '" . DB_escape_string($user) . "', '" . DB_escape_string($now) . "', '" . DB_escape_string($now) . "'";
    $SQL = "INSERT INTO crm_opportunities ($cols) VALUES ($vals)";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('saveOpportunity insert failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(200);
        echo json_encode(array('error' => 'Failed to create opportunity: ' . DB_error_msg($db)));
        exit;
    }
    $newId = crm_last_insert_id($db);
    echo json_encode(array('ok' => true, 'id' => (int)$newId));
    exit;
}

if ($action === 'updateOpportunityStage') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $stageId = isset($_POST['stage_id']) ? (int)$_POST['stage_id'] : 0;
    
    if ($id <= 0 || $stageId <= 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Invalid id or stage'));
        exit;
    }
    
    $checkSQL = "SELECT stage_name, probability FROM crm_pipeline_stages WHERE id=" . $stageId;
    $checkResult = DB_query($checkSQL, $db, '', '', false, false);
    $stage = DB_fetch_array($checkResult);
    
    $setParts = array("pipeline_stage_id=" . $stageId, "updated_at=NOW()");
    if ($stage) {
        $setParts[] = "probability=" . (int)$stage['probability'];
        if (stripos($stage['stage_name'], 'Won') !== false) {
            $setParts[] = "actual_close_date=CURDATE()";
            $setParts[] = "closed_value=expected_value";
        } elseif (stripos($stage['stage_name'], 'Lost') !== false) {
            $setParts[] = "actual_close_date=CURDATE()";
        }
    }
    
    $SQL = "UPDATE crm_opportunities SET " . implode(',', $setParts) . " WHERE id=" . $id;
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        http_response_code(200);
        echo json_encode(array('error' => 'Failed to update stage'));
        exit;
    }
    
    // Fire automation trigger: opportunity_won or opportunity_lost or opportunity_stage_changed
    if ($stage) {
        if (stripos($stage['stage_name'], 'Won') !== false) {
            triggerAutomation($db, 'opportunity_won', 'opportunity', $id);
        } elseif (stripos($stage['stage_name'], 'Lost') !== false) {
            triggerAutomation($db, 'opportunity_lost', 'opportunity', $id);
        } else {
            triggerAutomation($db, 'opportunity_stage_changed', 'opportunity', $id);
        }
    }
    
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'deleteOpportunity') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Invalid id'));
        exit;
    }
    $SQL = "DELETE FROM crm_opportunities WHERE id=" . $id;
    $Result = DB_query($SQL, $db, '', '', false, false);
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'listPipelineStages') {
    $SQL = "SELECT * FROM crm_pipeline_stages ORDER BY stage_order";
    $Result = DB_query($SQL, $db, '', '', false, false);
    $stages = array();
    while ($row = DB_fetch_array($Result)) {
        $stages[] = $row;
    }
    echo json_encode(array('data' => $stages));
    exit;
}

if ($action === 'getPipelineStats') {
    $ownerClause = crm_scope_owner_clause('o.assigned_to');
    $joinCondition = 's.id = o.pipeline_stage_id';
    if ($ownerClause !== '') {
        $joinCondition .= ' AND ' . $ownerClause;
    }
    $SQL = "SELECT s.id, s.stage_name, s.probability, s.stage_order,
            COUNT(o.id) as opportunity_count,
            COALESCE(SUM(o.expected_value), 0) as total_value,
            COALESCE(SUM(CASE WHEN o.pipeline_stage_id = s.id THEN o.expected_value ELSE 0 END), 0) as stage_value
            FROM crm_pipeline_stages s
            LEFT JOIN crm_opportunities o ON $joinCondition
            GROUP BY s.id, s.stage_name, s.probability, s.stage_order
            ORDER BY s.stage_order";
    $Result = DB_query($SQL, $db, '', '', false, false);
    $stats = array();
    while ($row = DB_fetch_array($Result)) {
        $stats[] = $row;
    }
    echo json_encode(array('data' => $stats));
    exit;
}

// ============================================
// COMMUNICATIONS API
// ============================================

if ($action === 'listCommunications') {
    $leadId = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0;
    $oppId = isset($_GET['opportunity_id']) ? (int)$_GET['opportunity_id'] : 0;
    $contactId = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    
    if ($leadId <= 0 && $oppId <= 0 && $contactId <= 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Must provide lead_id, opportunity_id, or contact_id'));
        exit;
    }
    
    $whereParts = array();
    if ($leadId > 0) $whereParts[] = "lead_id=" . $leadId;
    if ($oppId > 0) $whereParts[] = "opportunity_id=" . $oppId;
    if ($contactId > 0) $whereParts[] = "contact_id=" . $contactId;
    if ($type !== '') $whereParts[] = "communication_type='" . DB_escape_string($type) . "'";
    
    $SQL = "SELECT * FROM crm_communications WHERE " . implode(' OR ', $whereParts) . " ORDER BY created_at DESC LIMIT 100";
    $Result = DB_query($SQL, $db, '', '', false, false);
    $comms = array();
    while ($row = DB_fetch_array($Result)) {
        $comms[] = $row;
    }
    echo json_encode(array('data' => $comms));
    exit;
}

if ($action === 'saveCommunication') {
    $fields = array(
        'lead_id', 'opportunity_id', 'contact_id', 'task_id', 'communication_type',
        'subject', 'content', 'direction', 'from_email', 'to_email',
        'from_phone', 'to_phone', 'duration_seconds', 'outcome', 'result',
        'next_action', 'next_followup'
    );
    // Numeric FK / date columns must be NULL, not '' - MySQL strict mode
    // rejects an empty string for int/date columns (and the FK columns
    // lead_id/opportunity_id are constrained).
    $nullIfBlank = array('lead_id', 'opportunity_id', 'contact_id', 'task_id',
                         'duration_seconds', 'next_followup');
    // ENUM columns reject '' - supply a valid member instead.
    $enumDefaults = array('direction' => 'outbound', 'communication_type' => 'note');
    $data = array();
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        if ($val === '' && in_array($f, $nullIfBlank, true)) {
            $data[$f] = 'NULL';
        } elseif ($val === '' && isset($enumDefaults[$f])) {
            $data[$f] = DB_escape_string($enumDefaults[$f]);
        } else {
            $data[$f] = DB_escape_string($val);
        }
    }
    $user = $_SESSION['UserID'] ?? '';
    $now = date('Y-m-d H:i:s');
    
    $quoted = array();
    foreach ($data as $f => $v) {
        $quoted[] = ($v === 'NULL') ? 'NULL' : "'" . $v . "'";
    }
    $cols = implode(',', array_keys($data)) . ", created_by, created_at";
    $vals = implode(',', $quoted) . ", '" . DB_escape_string($user) . "', '" . DB_escape_string($now) . "'";
    $SQL = "INSERT INTO crm_communications ($cols) VALUES ($vals)";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('saveCommunication insert failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(200);
        echo json_encode(array('error' => 'Failed to save communication: ' . DB_error_msg($db)));
        exit;
    }
    $newId = crm_last_insert_id($db);
    
    if (!empty($data['lead_id'])) {
        DB_query("UPDATE crm_leads SET last_activity_at=NOW() WHERE id=" . (int)$data['lead_id'], $db, '', '', false, false);
    }
    
    echo json_encode(array('ok' => true, 'id' => (int)$newId));
    exit;
}

// ============================================
// AUTOMATION API
// ============================================

if ($action === 'listAutomationRules') {
    $activeOnly = !isset($_GET['include_inactive']);
    $where = $activeOnly ? "WHERE is_active=1" : "";
    $SQL = "SELECT * FROM crm_automation_rules $where ORDER BY priority DESC, created_at DESC";
    $Result = DB_query($SQL, $db, '', '', false, false);
      $rules = array();
      while ($row = DB_fetch_array($Result)) {
          // mysqli returns every column as a string unless
          // MYSQLI_OPT_INT_AND_FLOAT_NATIVE is set, which it is not. Cast the
          // numeric fields so client-side strict comparisons (===) work.
          $row['id'] = (int)$row['id'];
          $row['priority'] = (int)$row['priority'];
          $row['is_active'] = (int)$row['is_active'];
          $row['trigger_conditions'] = json_decode($row['trigger_conditions'], true);
          $row['actions'] = json_decode($row['actions'], true);
          $rules[] = $row;
      }
      echo json_encode(array('data' => $rules));
      exit;
  }

  if ($action === 'getAutomationRule') {
      $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
      if ($id <= 0) {
          echo json_encode(array('error' => 'Missing rule id'));
          exit;
      }
      $SQL = "SELECT * FROM crm_automation_rules WHERE id=$id LIMIT 1";
      $Result = DB_query($SQL, $db, '', '', false, false);
      if ($Result === false) {
          log_api_error('getAutomationRule query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
          http_response_code(500);
          echo json_encode(array('error' => 'Database error while loading rule'));
          exit;
      }
      if ($row = DB_fetch_array($Result)) {
          $row['id'] = (int)$row['id'];
          $row['priority'] = (int)$row['priority'];
          $row['is_active'] = (int)$row['is_active'];
          $row['trigger_conditions'] = json_decode($row['trigger_conditions'], true);
          $row['actions'] = json_decode($row['actions'], true);
          echo json_encode(array('data' => $row));
          exit;
      }
      echo json_encode(array('error' => 'Rule not found'));
      exit;
  }

if ($action === 'saveAutomationRule') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $rule_name = DB_escape_string($_POST['rule_name'] ?? '');
    $trigger_type = DB_escape_string($_POST['trigger_type'] ?? '');
    $trigger_conditions = $_POST['trigger_conditions'] ?? null;
    $actions = $_POST['actions'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 0;
    
      $actionsArr = is_array($actions) ? $actions : json_decode($actions, true);

      if ($rule_name === '' || $trigger_type === '' || empty($actionsArr)) {
        echo json_encode(array('error' => 'Missing required: rule_name="' . $rule_name . '", trigger_type="' . $trigger_type . '", actions=' . (empty($actionsArr) ? 'empty' : 'present')));
        exit;
    }
    
    $conditionsJson = is_array($trigger_conditions) ? json_encode($trigger_conditions) : $trigger_conditions;
    if ($conditionsJson === null || trim((string)$conditionsJson) === '') {
        $conditionsJson = '{}';
    }
    $actionsJson = is_array($actions) ? json_encode($actions) : $actions;
    if ($actionsJson === null || trim((string)$actionsJson) === '') {
        $actionsJson = '[]';
    }
    
    if ($id > 0) {
        $SQL = "UPDATE crm_automation_rules SET 
                rule_name='$rule_name', trigger_type='$trigger_type',
                trigger_conditions='" . DB_escape_string($conditionsJson) . "',
                actions='" . DB_escape_string($actionsJson) . "',
                is_active=$is_active, priority=$priority
                WHERE id=" . $id;
        $Result = DB_query($SQL, $db, '', '', false, false);
        echo json_encode(array('ok' => true, 'id' => $id));
        exit;
    }
    
    $user = $_SESSION['UserID'] ?? '';
    $SQL = "INSERT INTO crm_automation_rules (rule_name, trigger_type, trigger_conditions, actions, is_active, priority, created_by)
            VALUES ('$rule_name', '$trigger_type', '" . DB_escape_string($conditionsJson) . "', '" . DB_escape_string($actionsJson) . "', $is_active, $priority, '" . DB_escape_string($user) . "')";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('saveAutomationRule INSERT failed', array('sql_error' => $db->error));
        echo json_encode(array('error' => 'Could not save rule: ' . $db->error));
        exit;
    }
    $newId = crm_last_insert_id($db);
    if ($newId <= 0) {
        log_api_error('saveAutomationRule INSERT returned no id', array('insert_id' => $newId));
        echo json_encode(array('error' => 'Rule saved but no id was returned'));
        exit;
    }
    echo json_encode(array('ok' => true, 'id' => $newId));
    exit;
}

  if ($action === 'toggleAutomationRule') {
      $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
      // Accept 1/0, "true"/"false", "yes"/"no", "on"/"off". A raw (int) cast
      // would turn the string "true" into 0, silently disabling the rule.
      $activeRaw = isset($_POST['active']) ? strtolower(trim((string)$_POST['active'])) : '1';
      $active = in_array($activeRaw, array('1', 'true', 'yes', 'on'), true) ? 1 : 0;
      if ($id <= 0) {
          http_response_code(200);
          echo json_encode(array('error' => 'Invalid id'));
          exit;
      }
      $SQL = "UPDATE crm_automation_rules SET is_active=" . ($active ? 1 : 0) . " WHERE id=" . $id;
      if (DB_query($SQL, $db, '', '', false, false) === false) {
          log_api_error('toggleAutomationRule update failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
          http_response_code(500);
          echo json_encode(array('error' => 'Database error while updating rule'));
          exit;
      }
      echo json_encode(array('ok' => true));
      exit;
  }

if ($action === 'deleteAutomationRule') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Invalid id'));
        exit;
    }
    DB_query("DELETE FROM crm_automation_rules WHERE id=" . $id, $db, '', '', false, false);
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'listAutomationTriggers') {
    $SQL = "SELECT * FROM crm_automation_triggers ORDER BY trigger_name";
    $Result = DB_query($SQL, $db, '', '', false, false);
    $triggers = array();
    while ($row = DB_fetch_array($Result)) {
        $triggers[] = $row;
    }
    echo json_encode(array('data' => $triggers));
    exit;
}

// ============================================
// EMAIL TEMPLATES API
// ============================================

if ($action === 'listEmailTemplates') {
    $SQL = "SELECT * FROM crm_email_templates WHERE is_active=1 ORDER BY template_name";
    $Result = DB_query($SQL, $db, '', '', false, false);
    $templates = array();
    while ($row = DB_fetch_array($Result)) {
        $row['variables'] = json_decode($row['variables'], true);
        $templates[] = $row;
    }
    echo json_encode(array('data' => $templates));
    exit;
}

if ($action === 'saveEmailTemplate') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $template_name = DB_escape_string($_POST['template_name'] ?? '');
    $subject = DB_escape_string($_POST['subject'] ?? '');
    $body = DB_escape_string($_POST['body'] ?? '');
    $category = DB_escape_string($_POST['category'] ?? '');
    $variables = $_POST['variables'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($template_name === '' || $subject === '' || $body === '') {
        http_response_code(200);
        echo json_encode(array('error' => 'Missing required fields'));
        exit;
    }
    
    $varsJson = is_array($variables) ? json_encode($variables) : $variables;
    if ($varsJson === null || trim((string)$varsJson) === '') {
        $varsJson = '{}';
    }
    $user = $_SESSION['UserID'] ?? '';
    
    if ($template_name === '' || $subject === '' || $body === '') {
        http_response_code(200);
        echo json_encode(array('error' => 'Missing required fields: template_name, subject, or body'));
        exit;
    }
    
    $SQL = "INSERT INTO crm_email_templates (template_name, subject, body, category, variables, is_active, created_by)
            VALUES ('$template_name', '$subject', '$body', '$category', '" . DB_escape_string($varsJson) . "', $is_active, '$user')";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('saveEmailTemplate insert failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        echo json_encode(array('error' => 'Database error while saving template: ' . DB_error_msg($db)));
        exit;
    }
    $newId = crm_last_insert_id($db);
    echo json_encode(array('ok' => true, 'id' => (int)$newId));
    exit;
}

if ($action === 'deleteEmailTemplate') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        http_response_code(200);
        echo json_encode(array('error' => 'Invalid id'));
        exit;
    }
    DB_query("DELETE FROM crm_email_templates WHERE id=" . $id, $db, '', '', false, false);
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'saveSMTPConfig') {
    $host = DB_escape_string($_POST['host'] ?? '');
    $port = (int)($_POST['port'] ?? 587);
    $secure = DB_escape_string($_POST['secure'] ?? 'tls');
    $username = DB_escape_string($_POST['username'] ?? '');
    $password = DB_escape_string($_POST['password'] ?? '');
    $fromEmail = DB_escape_string($_POST['from_email'] ?? '');
    $fromName = DB_escape_string($_POST['from_name'] ?? 'SmartCRM');
    
    if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
        echo json_encode(array('error' => 'Missing required fields'));
        exit;
    }
    
    $config = "<?php\n";
    $config .= "// SMTP Configuration - Generated by SMTP Wizard\n";
    $config .= "\$smtp_config = array(\n";
    $config .= "    'host' => '$host',\n";
    $config .= "    'port' => $port,\n";
    $config .= "    'username' => '$username',\n";
    $config .= "    'password' => '$password',\n";
    $config .= "    'from_email' => '$fromEmail',\n";
    $config .= "    'from_name' => '$fromName',\n";
    $config .= "    'secure' => '$secure',\n";
    $config .= "    'debug' => false\n";
    $config .= ");\n";
    
    $result = file_put_contents(__DIR__ . '/smtp_config.php', $config);
    if ($result === false) {
        echo json_encode(array('error' => 'Failed to write config file'));
        exit;
    }
    
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'testSMTP') {
    $host = $_POST['host'] ?? '';
    $port = (int)($_POST['port'] ?? 587);
    $secure = $_POST['secure'] ?? 'tls';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($host === '' || $username === '' || $password === '') {
        echo json_encode(array('success' => false, 'message' => 'Missing required fields'));
        exit;
    }
    
    if (!function_exists('fsockopen')) {
        echo json_encode(array('success' => false, 'message' => 'fsockopen not available'));
        exit;
    }
    
    $errno = 0;
    $errstr = '';
    $timeout = 10;
    
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if (!$connection) {
        echo json_encode(array('success' => false, 'message' => "Cannot connect: $errstr ($errno)"));
        exit;
    }
    
    fclose($connection);
    echo json_encode(array('success' => true, 'message' => 'Connection successful!'));
    exit;
}

// ============================================
// DASHBOARD STATS API
// ============================================

if ($action === 'getDashboardStats') {
    $stats = array('totalLeads' => 0, 'new' => 0, 'converted' => 0, 'oppCount' => 0, 'oppValue' => 0.0);

    $SQL = "SELECT status, COUNT(*) as cnt FROM crm_leads"
        . crm_scope_where(array(crm_scope_owner_clause('assigned_to')))
        . " GROUP BY status";
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('getDashboardStats lead query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(500);
        echo json_encode(array('error' => 'Database error while loading dashboard stats'));
        exit;
    }
    while ($row = DB_fetch_array($Result)) {
        $cnt = (int)$row['cnt'];
        $stats['totalLeads'] += $cnt;
        $statusKey = strtolower(trim((string)$row['status']));
        if ($statusKey === 'new') {
            $stats['new'] = $cnt;
        } elseif ($statusKey === 'converted') {
            $stats['converted'] = $cnt;
        }
    }

    $SQL = "SELECT COUNT(*) as cnt, COALESCE(SUM(expected_value), 0) as val FROM crm_opportunities"
        . crm_scope_where(array(crm_scope_owner_clause('assigned_to')));
    $Result = DB_query($SQL, $db, '', '', false, false);
    if ($Result === false) {
        log_api_error('getDashboardStats opportunity query failed', array('errno' => DB_error_no($db), 'error' => DB_error_msg($db), 'sql' => $SQL));
        http_response_code(500);
        echo json_encode(array('error' => 'Database error while loading dashboard stats'));
        exit;
    }
    if ($row = DB_fetch_array($Result)) {
        $stats['oppCount'] = (int)$row['cnt'];
        $stats['oppValue'] = (float)$row['val'];
    }

    echo json_encode(array('data' => $stats));
    exit;
}

// ============================================
// WEEKLY REPORT API
// ============================================

if ($action === 'getWeeklyReport') {
    $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    
    $report = array(
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'tasks_completed' => 0,
        'tasks_created' => 0,
        'leads_created' => 0,
        'opportunities_won' => 0,
        'total_pipeline_value' => 0,
        'recent_tasks' => array(),
        'recent_leads' => array(),
        'recent_opportunities' => array()
    );
    
    $safeStart = DB_escape_string($weekStart);
    $safeEnd = DB_escape_string($weekEnd);
    $taskOwnerClause = crm_scope_owner_clause('TaskOwner');
    $leadOwnerClause = crm_scope_owner_clause('assigned_to');
    $oppOwnerClause = crm_scope_owner_clause('o.assigned_to');
    
    $tasksResult = DB_query("SELECT pkey, Taskname, Status, TaskOwner, datedue, taskdetails 
                            FROM Tasks WHERE datecreated BETWEEN '$safeStart' AND '$safeEnd 23:59:59'"
                            . ($taskOwnerClause !== '' ? " AND $taskOwnerClause" : '') . "
                            ORDER BY datecreated DESC LIMIT 20", $db, '', '', false, false);
    while ($row = DB_fetch_array($tasksResult)) {
        $report['recent_tasks'][] = $row;
        if ($row['Status'] == 4) $report['tasks_completed']++;
    }
    $report['tasks_created'] = count($report['recent_tasks']);
    
    $leadsResult = DB_query("SELECT * FROM crm_leads WHERE created_at BETWEEN '$safeStart' AND '$safeEnd 23:59:59'"
        . ($leadOwnerClause !== '' ? " AND $leadOwnerClause" : '')
        . " ORDER BY created_at DESC LIMIT 10", $db, '', '', false, false);
    while ($row = DB_fetch_array($leadsResult)) {
        $report['recent_leads'][] = $row;
        $report['leads_created']++;
    }
    
    $oppResult = DB_query("SELECT o.*, s.stage_name FROM crm_opportunities o
                          LEFT JOIN crm_pipeline_stages s ON o.pipeline_stage_id = s.id
                          WHERE o.created_at BETWEEN '$safeStart' AND '$safeEnd 23:59:59' "
        . ($oppOwnerClause !== '' ? "AND $oppOwnerClause " : "")
        . "ORDER BY o.created_at DESC LIMIT 10", $db, '', '', false, false);
    while ($row = DB_fetch_array($oppResult)) {
        $report['recent_opportunities'][] = $row;
        if (stripos($row['stage_name'], 'Won') !== false) $report['opportunities_won']++;
    }
    
    $pipeResult = DB_query("SELECT COALESCE(SUM(expected_value), 0) as total FROM crm_opportunities WHERE pipeline_stage_id NOT IN (SELECT id FROM crm_pipeline_stages WHERE stage_name LIKE '%Won%' OR stage_name LIKE '%Lost%')"
        . ($leadOwnerClause !== '' ? " AND $leadOwnerClause" : ''), $db, '', '', false, false);
    if ($row = DB_fetch_array($pipeResult)) {
        $report['total_pipeline_value'] = (float)$row['total'];
    }
    
    echo json_encode(array('data' => $report));
    exit;
}

if ($action === 'exportWeeklyReport') {
    $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
    
    $safeStart = DB_escape_string($weekStart);
    $weekEnd = date('Y-m-d', strtotime($safeStart . ' +6 days'));
    $taskOwnerClause = crm_scope_owner_clause('TaskOwner');
    $leadOwnerClause = crm_scope_owner_clause('assigned_to');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="weekly_report_' . $weekStart . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Weekly Report: ' . $safeStart . ' to ' . $weekEnd));
    fputcsv($output, array(''));
    
    fputcsv($output, array('Tasks Created'));
    $tasksResult = DB_query("SELECT Taskname, Status, TaskOwner, datedue FROM Tasks WHERE datecreated BETWEEN '$safeStart' AND '$weekEnd 23:59:59'"
        . ($taskOwnerClause !== '' ? " AND $taskOwnerClause" : ''), $db, '', '', false, false);
    fputcsv($output, array('Task Name', 'Status', 'Owner', 'Due Date'));
    while ($row = DB_fetch_array($tasksResult)) {
        fputcsv($output, array($row['Taskname'], $row['Status'], $row['TaskOwner'], $row['datedue']));
    }
    
    fputcsv($output, array(''));
    fputcsv($output, array('Leads Created'));
    $leadsResult = DB_query("SELECT company_name, contact_name, status, assigned_to FROM crm_leads WHERE created_at BETWEEN '$safeStart' AND '$weekEnd 23:59:59'"
        . ($leadOwnerClause !== '' ? " AND $leadOwnerClause" : ''), $db, '', '', false, false);
    fputcsv($output, array('Company', 'Contact', 'Status', 'Assigned To'));
    while ($row = DB_fetch_array($leadsResult)) {
        fputcsv($output, array($row['company_name'], $row['contact_name'], $row['status'], $row['assigned_to']));
    }
    
    fclose($output);
    exit;
}

http_response_code(200);
log_api_error('Unknown action requested', array('action' => $action));
echo json_encode(array('error' => 'Unknown action'));
