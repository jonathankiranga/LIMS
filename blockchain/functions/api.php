<?php
// Blockchain Workspace API - Module for Blockchain LIMS
error_reporting(E_ALL);
ini_set('display_errors', 0);

$PathPrefix = '../';
include($PathPrefix . 'include/session.inc');

$apiUserId = $_POST['user_id'] ?? $_GET['user_id'] ?? ($_SESSION['UserID'] ?? '');

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$logFile = $logDir . '/workspace_api.log';

function log_api($message, $context = array()) {
    global $logFile, $apiUserId;
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $user = $apiUserId ?? 'guest';
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $contextText = $context ? json_encode($context) : '';
    $msg = "[$timestamp] user=$user action=$action $message $contextText\n";
    @file_put_contents($logFile, $msg, FILE_APPEND);
}

set_exception_handler(function ($ex) {
    log_api('Exception: ' . $ex->getMessage());
    echo json_encode(array('error' => 'Internal server error'));
    exit;
});

header('Content-Type: application/json');

// Load database configuration from blockchain
$config = include($PathPrefix . 'include/config.php');
$db_host = $config['DB_HOST'];
$db_name = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$db = new mysqli($db_host, $db_username, $db_password, $db_name);
if ($db->connect_error) {
    log_api('DB connection failed', array('error' => $db->connect_error));
    echo json_encode(array('error' => 'Database connection failed'));
    exit;
}
$db->set_charset('utf8mb4');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function getUsers($db) {
    $users = array();
    $result = $db->query("SELECT user_id, full_name FROM users WHERE status = 'active' ORDER BY full_name");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = array(
                'id' => $row['user_id'], 
                'name' => $row['full_name']
            );
        }
    }
    return $users;
}

function getLeadStatusArray() {
    return array('new', 'contacted', 'qualified', 'proposal', 'negotiation', 'converted', 'lost');
}

function getTaskStatusArray() {
    return array("0" => 'Not yet begun', "1" => 'In progress', "2" => 'Almost Done', "3" => 'Taking Longer than expected', "4" => 'Complete');
}

function db_query_safe($db, $sql, $context = '') {
    $result = $db->query($sql);
    if ($result === false) {
        log_api('SQL failed', array(
            'context' => $context,
            'error' => $db->error,
            'sql' => $sql
        ));
    }
    return $result;
}

function table_exists($db, $table) {
    static $cache = array();
    $key = strtolower($table);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $safeTable = $db->real_escape_string($table);
    $result = db_query_safe($db, "SHOW TABLES LIKE '$safeTable'", 'table_exists:' . $table);
    $cache[$key] = $result ? ($result->num_rows > 0) : false;
    return $cache[$key];
}

function column_exists($db, $table, $column) {
    static $cache = array();
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    if (!table_exists($db, $table)) {
        $cache[$key] = false;
        return false;
    }
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = $db->real_escape_string($column);
    $result = db_query_safe($db, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'", 'column_exists:' . $table . '.' . $column);
    $cache[$key] = $result ? ($result->num_rows > 0) : false;
    return $cache[$key];
}

function current_user_is_admin($db, $userId = null) {
    static $cache = array();
    $userId = $userId ?? ($apiUserId ?? '');
    if ($userId === '') {
        return false;
    }
    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }
    if (!table_exists($db, 'users') || !column_exists($db, 'users', 'role')) {
        $cache[$userId] = false;
        return false;
    }
    $safeUserId = $db->real_escape_string($userId);
    $result = db_query_safe($db, "SELECT role FROM users WHERE user_id='$safeUserId' LIMIT 1", 'current_user_is_admin');
    $cache[$userId] = $result && ($row = $result->fetch_assoc()) ? ((string)($row['role'] ?? '') === '1') : false;
    return $cache[$userId];
}

function get_workspace_privilege($db, $workspaceId, $userId = null) {
    $workspaceId = (int)$workspaceId;
    $userId = $userId ?? ($apiUserId ?? '');
    if ($workspaceId <= 0 || $userId === '') {
        return '';
    }
    $safeUserId = $db->real_escape_string($userId);
    $result = db_query_safe($db, "SELECT privilege FROM workspace_members WHERE workspace_id=$workspaceId AND user_id='$safeUserId' LIMIT 1", 'get_workspace_privilege');
    if ($result && ($row = $result->fetch_assoc())) {
        return (string)($row['privilege'] ?? '');
    }
    $result = db_query_safe($db, "SELECT created_by FROM workspaces WHERE id=$workspaceId LIMIT 1", 'get_workspace_privilege:workspace_owner');
    if ($result && ($row = $result->fetch_assoc()) && (string)($row['created_by'] ?? '') === $userId) {
        return 'owner';
    }
    return '';
}

function can_manage_workspace($db, $workspaceId, $userId = null) {
    global $apiUserId;
    $userId = $userId ?? ($apiUserId ?? '');
    if ($userId === '') {
        return false;
    }
    if (current_user_is_admin($db, $userId)) {
        return true;
    }
    return in_array(get_workspace_privilege($db, $workspaceId, $userId), array('owner', 'admin'), true);
}

function can_edit_workspace($db, $workspaceId, $userId = null) {
    global $apiUserId;
    $userId = $userId ?? ($apiUserId ?? '');
    if ($userId === '') {
        return false;
    }
    if (current_user_is_admin($db, $userId)) {
        return true;
    }
    return in_array(get_workspace_privilege($db, $workspaceId, $userId), array('owner', 'admin', 'member'), true);
}

function can_delete_field_activity($db, $activityId, $userId = null) {
    return false;
}

function iso_date_only($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    return substr($value, 0, 10);
}

function clamp_iso_date($value, $minDate, $maxDate, $fallback = '') {
    $date = iso_date_only($value);
    if ($date === '') {
        $date = $fallback !== '' ? iso_date_only($fallback) : '';
    }
    if ($date === '') {
        return $minDate;
    }
    if ($date < $minDate) {
        return $minDate;
    }
    if ($date > $maxDate) {
        return $maxDate;
    }
    return $date;
}

function map_board_list_to_report_status($listName) {
    $name = strtolower(trim((string)$listName));
    if ($name === '') {
        return 'planned';
    }
    if (strpos($name, 'done') !== false || strpos($name, 'complete') !== false || strpos($name, 'closed') !== false) {
        return 'completed';
    }
    if (strpos($name, 'progress') !== false || strpos($name, 'doing') !== false || strpos($name, 'review') !== false || strpos($name, 'wait') !== false || strpos($name, 'qa') !== false) {
        return 'in_progress';
    }
    if (strpos($name, 'cancel') !== false || strpos($name, 'hold') !== false || strpos($name, 'archive') !== false) {
        return 'cancelled';
    }
    return 'planned';
}

function get_weekly_board_cards($db, $userId, $weekStart, $weekEnd) {
    $cards = array();
    if ($userId === '' || !workspace_board_schema_ready($db)) {
        return $cards;
    }

    $safeUserId = $db->real_escape_string($userId);
    $safeStart = $db->real_escape_string($weekStart);
    $safeEnd = $db->real_escape_string($weekEnd);
    $endOfWeek = $safeEnd . ' 23:59:59';

    $sql = "SELECT
                c.*,
                w.id AS workspace_id,
                w.name AS workspace_name,
                wb.name AS board_name,
                wl.name AS list_name,
                wl.color AS list_color,
                GROUP_CONCAT(cm.user_id ORDER BY cm.user_id SEPARATOR ',') AS member_ids,
                GROUP_CONCAT(COALESCE(u.full_name, cm.user_id) ORDER BY cm.user_id SEPARATOR '||') AS member_names
            FROM workspace_cards c
            INNER JOIN workspace_boards wb ON c.board_id = wb.id
            INNER JOIN workspaces w ON wb.workspace_id = w.id
            LEFT JOIN workspace_lists wl ON c.list_id = wl.id
            LEFT JOIN workspace_card_members cm ON c.id = cm.card_id
            LEFT JOIN users u ON cm.user_id = u.user_id
            WHERE c.archived = 0
              AND (
                    c.created_by = '$safeUserId'
                    OR EXISTS (
                        SELECT 1 FROM workspace_card_members cmx
                        WHERE cmx.card_id = c.id AND cmx.user_id = '$safeUserId'
                    )
              )
              AND (
                    c.created_at BETWEEN '$safeStart' AND '$endOfWeek'
                    OR c.updated_at BETWEEN '$safeStart' AND '$endOfWeek'
                    OR (c.due_date IS NOT NULL AND c.due_date BETWEEN '$safeStart' AND '$endOfWeek')
                    OR EXISTS (
                        SELECT 1 FROM workspace_card_activity wca
                        WHERE wca.card_id = c.id AND wca.created_at BETWEEN '$safeStart' AND '$endOfWeek'
                    )
                    OR EXISTS (
                        SELECT 1 FROM workspace_card_comments wcc
                        WHERE wcc.card_id = c.id AND wcc.created_at BETWEEN '$safeStart' AND '$endOfWeek'
                    )
              )
            GROUP BY c.id
            ORDER BY COALESCE(c.due_date, c.updated_at, c.created_at) ASC, c.updated_at DESC, c.id DESC";

    $result = db_query_safe($db, $sql, 'get_weekly_board_cards:cards');
    if (!$result) {
        return $cards;
    }

    while ($row = $result->fetch_assoc()) {
        $cardId = (int)$row['id'];
        $labels = array();
        $labelResult = db_query_safe($db, "SELECT wl.id, wl.name, wl.color FROM workspace_card_labels wcl INNER JOIN workspace_labels wl ON wcl.label_id = wl.id WHERE wcl.card_id=$cardId ORDER BY wl.name ASC", 'get_weekly_board_cards:labels');
        if ($labelResult) {
            while ($labelRow = $labelResult->fetch_assoc()) {
                $labels[] = array(
                    'id' => (int)$labelRow['id'],
                    'name' => $labelRow['name'],
                    'color' => $labelRow['color']
                );
            }
        }

        $checklists = array();
        $checklistTotal = 0;
        $checklistDone = 0;
        $checklistResult = db_query_safe($db, "SELECT id, title, position FROM workspace_checklists WHERE card_id=$cardId ORDER BY position ASC, id ASC", 'get_weekly_board_cards:checklists');
        if ($checklistResult) {
            while ($checklistRow = $checklistResult->fetch_assoc()) {
                $checklistId = (int)$checklistRow['id'];
                $items = array();
                $itemResult = db_query_safe($db, "SELECT id, title, is_done, completed_by, completed_at FROM workspace_checklist_items WHERE checklist_id=$checklistId ORDER BY position ASC, id ASC", 'get_weekly_board_cards:checklist_items');
                if ($itemResult) {
                    while ($itemRow = $itemResult->fetch_assoc()) {
                        $isDone = (int)$itemRow['is_done'] === 1;
                        $items[] = array(
                            'id' => (int)$itemRow['id'],
                            'title' => $itemRow['title'],
                            'is_done' => $isDone ? 1 : 0,
                            'completed_by' => $itemRow['completed_by'],
                            'completed_at' => $itemRow['completed_at']
                        );
                        $checklistTotal++;
                        if ($isDone) {
                            $checklistDone++;
                        }
                    }
                }
                $checklists[] = array(
                    'id' => $checklistId,
                    'title' => $checklistRow['title'],
                    'items' => $items
                );
            }
        }

        $comments = array();
        $commentResult = db_query_safe($db, "SELECT wcc.id, wcc.user_id, COALESCE(u.full_name, wcc.user_id) AS full_name, wcc.comment, wcc.created_at FROM workspace_card_comments wcc LEFT JOIN users u ON wcc.user_id = u.user_id WHERE wcc.card_id=$cardId ORDER BY wcc.created_at DESC, wcc.id DESC", 'get_weekly_board_cards:comments');
        if ($commentResult) {
            while ($commentRow = $commentResult->fetch_assoc()) {
                $comments[] = array(
                    'id' => (int)$commentRow['id'],
                    'user_id' => $commentRow['user_id'],
                    'full_name' => $commentRow['full_name'],
                    'comment' => $commentRow['comment'],
                    'created_at' => $commentRow['created_at']
                );
            }
        }

        $memberIds = trim((string)($row['member_ids'] ?? '')) === '' ? array() : explode(',', $row['member_ids']);
        $memberNames = trim((string)($row['member_names'] ?? '')) === '' ? array() : explode('||', $row['member_names']);
        $status = map_board_list_to_report_status($row['list_name'] ?? '');
        $timelineStart = clamp_iso_date($row['created_at'] ?? '', $weekStart, $weekEnd, $row['updated_at'] ?? '');
        $timelineEnd = clamp_iso_date($row['due_date'] ?? '', $weekStart, $weekEnd, $row['updated_at'] ?? ($row['created_at'] ?? ''));
        if ($timelineEnd < $timelineStart) {
            $timelineEnd = $timelineStart;
        }

        $cards[] = array(
            'id' => $cardId,
            'workspace_id' => (int)$row['workspace_id'],
            'workspace_name' => $row['workspace_name'] ?: 'Workspace',
            'board_id' => (int)$row['board_id'],
            'board_name' => $row['board_name'] ?: 'Main Board',
            'list_id' => (int)$row['list_id'],
            'list_name' => $row['list_name'] ?: 'Unsorted',
            'list_color' => $row['list_color'] ?: '#64748b',
            'activity_status' => $status,
            'title' => $row['title'],
            'description' => $row['description'],
            'priority' => (int)$row['priority'],
            'due_date' => $row['due_date'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'timeline_start' => $timelineStart,
            'timeline_end' => $timelineEnd,
            'member_ids' => $memberIds,
            'member_names' => $memberNames,
            'labels' => $labels,
            'checklists' => $checklists,
            'checklist_total' => $checklistTotal,
            'checklist_done' => $checklistDone,
            'comments' => $comments,
            'comments_count' => count($comments),
            'latest_comment' => count($comments) ? $comments[0] : null
        );
    }

    return $cards;
}

function workspace_board_schema_ready($db) {
    return table_exists($db, 'workspace_boards')
        && table_exists($db, 'workspace_lists')
        && table_exists($db, 'workspace_cards')
        && table_exists($db, 'workspace_card_members');
}

function get_default_workspace_list_blueprint() {
    return array(
        array('slug' => 'backlog', 'name' => 'Backlog', 'color' => '#475569'),
        array('slug' => 'todo', 'name' => 'To Do', 'color' => '#2563eb'),
        array('slug' => 'doing', 'name' => 'Doing', 'color' => '#f59e0b'),
        array('slug' => 'waiting', 'name' => 'Waiting', 'color' => '#8b5cf6'),
        array('slug' => 'done', 'name' => 'Done', 'color' => '#16a34a')
    );
}

function get_workspace_board_lists($db, $boardId) {
    $lists = array();
    $result = db_query_safe($db, "SELECT * FROM workspace_lists WHERE board_id=" . (int)$boardId . " ORDER BY position ASC, id ASC", 'get_workspace_board_lists');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lists[] = array(
                'id' => (int)$row['id'],
                'board_id' => (int)$row['board_id'],
                'name' => $row['name'],
                'color' => $row['color'] ?: '#6b4fd6',
                'position' => (int)$row['position']
            );
        }
    }
    return $lists;
}

function ensure_workspace_board($db, $workspaceId, $userId) {
    if (!workspace_board_schema_ready($db)) {
        return null;
    }
    $workspaceId = (int)$workspaceId;
    $safeUserId = $db->real_escape_string($userId);
    $result = db_query_safe($db, "SELECT * FROM workspace_boards WHERE workspace_id=$workspaceId ORDER BY is_default DESC, id ASC LIMIT 1", 'ensure_workspace_board:select');
    if ($result && ($row = $result->fetch_assoc())) {
        $board = $row;
    } else {
        db_query_safe($db, "INSERT INTO workspace_boards (workspace_id, name, description, is_default, created_by) VALUES ($workspaceId, 'Main Board', 'Default board', 1, '$safeUserId')", 'ensure_workspace_board:insert');
        $boardId = (int)$db->insert_id;
        $board = array(
            'id' => $boardId,
            'workspace_id' => $workspaceId,
            'name' => 'Main Board',
            'description' => 'Default board',
            'is_default' => 1
        );
    }
    $boardId = (int)$board['id'];
    $listResult = db_query_safe($db, "SELECT COUNT(*) AS cnt FROM workspace_lists WHERE board_id=$boardId", 'ensure_workspace_board:list_count');
    $listCount = $listResult && ($listRow = $listResult->fetch_assoc()) ? (int)$listRow['cnt'] : 0;
    if ($listCount === 0) {
        $position = 10;
        foreach (get_default_workspace_list_blueprint() as $list) {
            $name = $db->real_escape_string($list['name']);
            $color = $db->real_escape_string($list['color']);
            db_query_safe($db, "INSERT INTO workspace_lists (board_id, name, color, position, created_by) VALUES ($boardId, '$name', '$color', $position, '$safeUserId')", 'ensure_workspace_board:create_list');
            $position += 10;
        }
    }
    migrate_legacy_workspace_tasks_to_cards($db, $workspaceId, $boardId, $userId);
    return array(
        'id' => $boardId,
        'workspace_id' => $workspaceId,
        'name' => $board['name'] ?? 'Main Board',
        'description' => $board['description'] ?? ''
    );
}

function resolve_board_list_map($lists) {
    $map = array();
    foreach ($lists as $list) {
        $key = strtolower(trim($list['name']));
        $map[$key] = (int)$list['id'];
    }
    return array(
        'todo' => $map['to do'] ?? $map['todo'] ?? ($map['backlog'] ?? ($lists[0]['id'] ?? 0)),
        'in_progress' => $map['doing'] ?? $map['in progress'] ?? ($map['to do'] ?? ($lists[0]['id'] ?? 0)),
        'review' => $map['waiting'] ?? $map['review'] ?? ($map['doing'] ?? ($lists[0]['id'] ?? 0)),
        'done' => $map['done'] ?? ($lists[count($lists) - 1]['id'] ?? ($lists[0]['id'] ?? 0))
    );
}

function migrate_legacy_workspace_tasks_to_cards($db, $workspaceId, $boardId, $userId) {
    if (!workspace_board_schema_ready($db) || !table_exists($db, 'workspace_tasks')) {
        return;
    }
    $cardCountResult = db_query_safe($db, "SELECT COUNT(*) AS cnt FROM workspace_cards WHERE board_id=" . (int)$boardId, 'migrate_legacy_workspace_tasks_to_cards:card_count');
    $cardCount = $cardCountResult && ($row = $cardCountResult->fetch_assoc()) ? (int)$row['cnt'] : 0;
    if ($cardCount > 0) {
        return;
    }
    $legacyCountResult = db_query_safe($db, "SELECT COUNT(*) AS cnt FROM workspace_tasks WHERE workspace_id=" . (int)$workspaceId, 'migrate_legacy_workspace_tasks_to_cards:legacy_count');
    $legacyCount = $legacyCountResult && ($row = $legacyCountResult->fetch_assoc()) ? (int)$row['cnt'] : 0;
    if ($legacyCount === 0) {
        return;
    }
    $lists = get_workspace_board_lists($db, $boardId);
    $listMap = resolve_board_list_map($lists);
    $positions = array();
    foreach ($listMap as $status => $listId) {
        $positions[$listId] = 10;
    }
    $result = db_query_safe($db, "SELECT * FROM workspace_tasks WHERE workspace_id=" . (int)$workspaceId . " ORDER BY created_at ASC, id ASC", 'migrate_legacy_workspace_tasks_to_cards:tasks');
    if (!$result) {
        return;
    }
    while ($row = $result->fetch_assoc()) {
        $status = (string)($row['status'] ?? 'todo');
        $listId = (int)($listMap[$status] ?? ($listMap['todo'] ?? 0));
        if ($listId <= 0) {
            continue;
        }
        $title = $db->real_escape_string($row['title'] ?? '');
        $description = $db->real_escape_string($row['description'] ?? '');
        $dueDate = trim($row['due_date'] ?? '');
        $dueDateSql = $dueDate === '' ? 'NULL' : "'" . $db->real_escape_string($dueDate) . "'";
        $createdBy = $db->real_escape_string($row['created_by'] ?? $userId);
        $priority = (int)($row['priority'] ?? 2);
        $position = (int)($positions[$listId] ?? 10);
        db_query_safe($db, "INSERT INTO workspace_cards (board_id, list_id, title, description, priority, due_date, position, created_by, created_at, updated_at) VALUES ($boardId, $listId, '$title', '$description', $priority, $dueDateSql, $position, '$createdBy', '" . $db->real_escape_string($row['created_at'] ?? date('Y-m-d H:i:s')) . "', '" . $db->real_escape_string($row['updated_at'] ?? date('Y-m-d H:i:s')) . "')", 'migrate_legacy_workspace_tasks_to_cards:insert_card');
        $cardId = (int)$db->insert_id;
        $positions[$listId] = $position + 10;
        $assignedTo = trim($row['assigned_to'] ?? '');
        if ($cardId > 0 && $assignedTo !== '') {
            $safeAssigned = $db->real_escape_string($assignedTo);
            db_query_safe($db, "INSERT IGNORE INTO workspace_card_members (card_id, user_id, added_by) VALUES ($cardId, '$safeAssigned', '$createdBy')", 'migrate_legacy_workspace_tasks_to_cards:insert_member');
        }
    }
}

log_api('Request received');

// ============================================
// AUTHENTICATION
// ============================================

if ($action === 'login') {
    $username = $db->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $password === '') {
        echo json_encode(array('error' => 'Username and password required'));
        exit;
    }
    
    $result = $db->query("SELECT user_id, full_name, password_hash FROM www_users WHERE username='$username' AND status='active'");
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash']) || md5($password) === $row['password_hash']) {
            $apiUserId = $row['user_id'];
            $_SESSION['user_name'] = $row['full_name'];
            echo json_encode(array('ok' => true, 'user_id' => $row['user_id'], 'user_name' => $row['full_name']));
        } else {
            echo json_encode(array('error' => 'Invalid credentials'));
        }
    } else {
        echo json_encode(array('error' => 'User not found'));
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'getCurrentUser') {
    $userId = $apiUserId ?? '';
    $userName = $_SESSION['user_name'] ?? '';
    echo json_encode(array('data' => array(
        'userid' => $userId,
        'full_name' => $userName,
        'is_admin' => current_user_is_admin($db, $userId)
    )));
    exit;
}

// ============================================
// USERS
// ============================================

if ($action === 'listUsers') {
    echo json_encode(array('data' => getUsers($db)));
    exit;
}

// ============================================
// TASKS (Tasks table)
// ============================================

if ($action === 'listTasks') {
    $mineOnly = isset($_GET['mine']);
    $scope = strtolower($_GET['scope'] ?? 'all');
    
    $whereParts = array();
    if ($mineOnly && isset($apiUserId)) {
        $whereParts[] = "TaskOwner='" . $db->real_escape_string($apiUserId) . "'";
    }
    if ($scope === 'stalled') {
        $whereParts[] = "Status <> 4";
        $whereParts[] = "TIMESTAMPDIFF(DAY, COALESCE(lastactivity, datecreated), NOW()) >= 3";
    } elseif ($scope === 'due') {
        $whereParts[] = "Status <> 4";
        $whereParts[] = "datedue IS NOT NULL AND datedue <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    }
    
    $where = empty($whereParts) ? '' : 'WHERE ' . implode(' AND ', $whereParts);
    $sql = "SELECT pkey, Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated, lastactivity FROM Tasks $where ORDER BY datecreated DESC LIMIT 200";
    $result = $db->query($sql);
    $tasks = array();
    while ($row = $result->fetch_assoc()) {
        $tasks[] = array(
            'id' => (int)$row['pkey'],
            'title' => $row['Taskname'],
            'status' => (string)$row['Status'],
            'priority' => (string)$row['Priority'],
            'owner' => $row['TaskOwner'],
            'due' => $row['datedue'],
            'details' => $row['taskdetails'],
            'created' => $row['datecreated']
        );
    }
    echo json_encode(array('data' => $tasks));
    exit;
}

if ($action === 'saveTask') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $status = (int)($_POST['status'] ?? 0);
    $priority = (int)($_POST['priority'] ?? 1);
    $duedate = $_POST['duedate'] ?? null;
    $details = $_POST['details'] ?? '';
    $userID = $apiUserId;
    
    if ($title === '') {
        echo json_encode(array('error' => 'Task name is required'));
        exit;
    }
    
    if ($id > 0) {
        $sql = "UPDATE Tasks SET Taskname='" . $db->real_escape_string($title) . "', Status=$status, Priority=$priority, 
                datedue=" . ($duedate ? "'" . $db->real_escape_string($duedate) . "'" : "NULL") . ", 
                taskdetails='" . $db->real_escape_string($details) . "', lastactivity=NOW() WHERE pkey=$id";
    } else {
        $sql = "INSERT INTO Tasks (Taskname, Status, Priority, datedue, taskdetails, TaskOwner, datecreated, lastactivity) 
                VALUES ('" . $db->real_escape_string($title) . "', $status, $priority, " . ($duedate ? "'" . $db->real_escape_string($duedate) . "'" : "NULL") . ", 
                '" . $db->real_escape_string($details) . "', '" . $db->real_escape_string($userID) . "', NOW(), NOW())";
    }
    
    $db->query($sql);
    $newId = $id > 0 ? $id : $db->insert_id;
    echo json_encode(array('ok' => true, 'id' => $newId));
    exit;
}

if ($action === 'updateTaskStatus') {
    $id = (int)($_POST['id'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);
    if ($id <= 0) {
        echo json_encode(array('error' => 'Invalid id'));
        exit;
    }
    $db->query("UPDATE Tasks SET Status=$status, lastactivity=NOW() WHERE pkey=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'deleteTask') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(array('error' => 'Invalid id'));
        exit;
    }
    $db->query("DELETE FROM Tasks WHERE pkey=$id");
    echo json_encode(array('ok' => true));
    exit;
}

// ============================================
// LEADS (crm_leads)
// ============================================

if ($action === 'listLeads') {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $mineOnly = isset($_GET['mine']);
    
    $whereParts = array();
    if ($search !== '') {
        $safeSearch = $db->real_escape_string($search);
        $whereParts[] = "(company_name LIKE '%$safeSearch%' OR contact_name LIKE '%$safeSearch%' OR email LIKE '%$safeSearch%')";
    }
    if ($status !== '') {
        $whereParts[] = "status='" . $db->real_escape_string($status) . "'";
    }
    if ($mineOnly && isset($apiUserId)) {
        $whereParts[] = "assigned_to='" . $db->real_escape_string($apiUserId) . "'";
    }
    
    $where = empty($whereParts) ? '' : 'WHERE ' . implode(' AND ', $whereParts);
    $sql = "SELECT * FROM crm_leads $where ORDER BY created_at DESC LIMIT 200";
    $result = $db->query($sql);
    $leads = array();
    while ($row = $result->fetch_assoc()) {
        $leads[] = array(
            'id' => (int)$row['id'],
            'company_name' => $row['company_name'],
            'contact_name' => $row['contact_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'status' => $row['status'],
            'assigned_to' => $row['assigned_to'],
            'lead_score' => (int)$row['lead_score'],
            'created_at' => $row['created_at']
        );
    }
    echo json_encode(array('data' => $leads));
    exit;
}

if ($action === 'getLead') {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "SELECT * FROM crm_leads WHERE id=$id LIMIT 1";
    $result = $db->query($sql);
    if ($row = $result->fetch_assoc()) {
        echo json_encode(array('data' => $row));
    } else {
        echo json_encode(array('error' => 'Lead not found'));
    }
    exit;
}

if ($action === 'saveLead') {
    $id = (int)($_POST['id'] ?? 0);
    $fields = array('company_name', 'contact_name', 'email', 'phone', 'mobile', 'website', 'industry', 'source', 'status', 'assigned_to', 'lead_score', 'notes', 'address', 'city', 'country', 'pin_vat', 'next_followup');
    $data = array();
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        $data[$f] = $val === '' ? 'NULL' : "'" . $db->real_escape_string($val) . "'";
    }
    $user = $apiUserId;
    $now = date('Y-m-d H:i:s');
    
    if ($id > 0) {
        $setParts = array();
        foreach ($data as $col => $val) {
            $setParts[] = "`$col`=$val";
        }
        $sql = "UPDATE crm_leads SET " . implode(',', $setParts) . ", updated_at='$now' WHERE id=$id";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => $id));
    } else {
        $cols = implode(',', array_keys($data)) . ", created_by, created_at, updated_at";
        $vals = implode(',', array_values($data)) . ", '$user', '$now', '$now'";
        $sql = "INSERT INTO crm_leads ($cols) VALUES ($vals)";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    }
    exit;
}

if ($action === 'deleteLead') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("DELETE FROM crm_leads WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

// ============================================
// OPPORTUNITIES (crm_opportunities)
// ============================================

if ($action === 'listOpportunities') {
    $sql = "SELECT o.*, s.stage_name FROM crm_opportunities o 
            LEFT JOIN crm_pipeline_stages s ON o.pipeline_stage_id = s.id 
            ORDER BY o.created_at DESC LIMIT 200";
    $result = $db->query($sql);
    $opps = array();
    while ($row = $result->fetch_assoc()) {
        $opps[] = array(
            'id' => (int)$row['id'],
            'opportunity_name' => $row['opportunity_name'],
            'stage_name' => $row['stage_name'],
            'expected_value' => $row['expected_value'],
            'probability' => (int)$row['probability'],
            'assigned_to' => $row['assigned_to'],
            'created_at' => $row['created_at']
        );
    }
    echo json_encode(array('data' => $opps));
    exit;
}

if ($action === 'saveOpportunity') {
    $id = (int)($_POST['id'] ?? 0);
    $fields = array('opportunity_name', 'lead_id', 'pipeline_stage_id', 'assigned_to', 'expected_value', 'probability', 'expected_close_date', 'description', 'next_step');
    $data = array();
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        $data[$f] = $val === '' ? 'NULL' : "'" . $db->real_escape_string($val) . "'";
    }
    $user = $apiUserId;
    $now = date('Y-m-d H:i:s');
    
    if ($id > 0) {
        $setParts = array();
        foreach ($data as $col => $val) {
            $setParts[] = "`$col`=$val";
        }
        $sql = "UPDATE crm_opportunities SET " . implode(',', $setParts) . ", updated_at='$now' WHERE id=$id";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => $id));
    } else {
        $cols = implode(',', array_keys($data)) . ", created_by, created_at, updated_at";
        $vals = implode(',', array_values($data)) . ", '$user', '$now', '$now'";
        $sql = "INSERT INTO crm_opportunities ($cols) VALUES ($vals)";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    }
    exit;
}

// ============================================
// WORKSPACES (workspace_ tables)
// ============================================

if ($action === 'listWorkspaces') {
    $userId = $apiUserId;
    $workspaceItemCountSql = workspace_board_schema_ready($db)
        ? "(SELECT COUNT(*) FROM workspace_cards c INNER JOIN workspace_boards b ON c.board_id = b.id WHERE b.workspace_id = w.id AND c.archived = 0)"
        : "(SELECT COUNT(*) FROM workspace_tasks WHERE workspace_id = w.id)";
    
    $sql = "SELECT DISTINCT w.*, 
            (SELECT COUNT(*) FROM workspace_members WHERE workspace_id = w.id) as members,
            $workspaceItemCountSql as tasks,
            wm.privilege as user_privilege
            FROM workspaces w
            LEFT JOIN workspace_members wm ON w.id = wm.workspace_id AND wm.user_id = '$userId'
            WHERE wm.user_id IS NOT NULL OR w.created_by = '$userId' OR w.visibility = 'public'
            ORDER BY w.updated_at DESC";
    $result = $db->query($sql);
    $workspaces = array();
    while ($row = $result->fetch_assoc()) {
        $workspaces[] = array(
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'color' => $row['color'] ?: '#6b4fd6',
            'icon' => $row['icon'] ?: 'folder',
            'visibility' => $row['visibility'] ?: 'private',
            'members' => (int)($row['members'] ?? 0),
            'tasks' => (int)($row['tasks'] ?? 0),
            'user_privilege' => $row['user_privilege'],
            'can_manage' => current_user_is_admin($db, $userId) || in_array((string)($row['user_privilege'] ?? ''), array('owner', 'admin'), true) || ($row['created_by'] ?? '') === $userId,
            'created_by' => $row['created_by'],
            'created_at' => $row['created_at']
        );
    }
    echo json_encode(array('data' => $workspaces));
    exit;
}

if ($action === 'listAllWorkspaces') {
    $userId = $apiUserId ?? '';
    $safeUserId = $db->real_escape_string($userId);
    $workspaceItemCountSql = workspace_board_schema_ready($db)
        ? "(SELECT COUNT(*) FROM workspace_cards c INNER JOIN workspace_boards b ON c.board_id = b.id WHERE b.workspace_id = w.id AND c.archived = 0)"
        : "(SELECT COUNT(*) FROM workspace_tasks WHERE workspace_id = w.id)";
    $sql = "SELECT w.*, 
            (SELECT COUNT(*) FROM workspace_members WHERE workspace_id = w.id) as member_count,
            $workspaceItemCountSql as task_count,
            wm.privilege as user_privilege
            FROM workspaces w
            LEFT JOIN workspace_members wm ON w.id = wm.workspace_id AND wm.user_id = '$safeUserId'
            ORDER BY w.updated_at DESC";
    $result = $db->query($sql);
    $workspaces = array();
    while ($row = $result->fetch_assoc()) {
        $workspaces[] = array(
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'color' => $row['color'],
            'icon' => $row['icon'],
            'visibility' => $row['visibility'],
            'member_count' => (int)$row['member_count'],
            'task_count' => (int)$row['task_count'],
            'user_privilege' => $row['user_privilege'],
            'can_manage' => current_user_is_admin($db, $userId) || in_array((string)($row['user_privilege'] ?? ''), array('owner', 'admin'), true) || ($row['created_by'] ?? '') === $userId,
            'created_by' => $row['created_by'],
            'created_at' => $row['created_at']
        );
    }
    echo json_encode(array('data' => $workspaces));
    exit;
}

if ($action === 'getWorkspace') {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "SELECT * FROM workspaces WHERE id=$id LIMIT 1";
    $result = $db->query($sql);
    if ($row = $result->fetch_assoc()) {
        echo json_encode(array('data' => $row));
    } else {
        echo json_encode(array('error' => 'Workspace not found'));
    }
    exit;
}

if ($action === 'saveWorkspace') {
    $id = (int)($_POST['id'] ?? 0);
    $name = $db->real_escape_string(trim($_POST['name'] ?? ''));
    $description = $db->real_escape_string(trim($_POST['description'] ?? ''));
    $color = $db->real_escape_string(trim($_POST['color'] ?? '#6b4fd6'));
    $icon = $db->real_escape_string(trim($_POST['icon'] ?? 'folder'));
    $visibility = $db->real_escape_string(trim($_POST['visibility'] ?? 'private'));
    $userId = $apiUserId;
    $now = date('Y-m-d H:i:s');
    
    if ($name === '') {
        echo json_encode(array('error' => 'Workspace name is required'));
        exit;
    }
    
    if ($id > 0) {
        $sql = "UPDATE workspaces SET name='$name', description='$description', color='$color', 
                icon='$icon', visibility='$visibility', updated_at='$now' WHERE id=$id";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => $id));
    } else {
        $sql = "INSERT INTO workspaces (name, description, color, icon, visibility, created_by, created_at, updated_at)
                VALUES ('$name', '$description', '$color', '$icon', '$visibility', '$userId', '$now', '$now')";
        $db->query($sql);
        $newId = $db->insert_id;
        if ($newId && $userId) {
            $db->query("INSERT INTO workspace_members (workspace_id, user_id, privilege, added_by, added_at) VALUES ($newId, '$userId', 'owner', '$userId', '$now')");
        }
        echo json_encode(array('ok' => true, 'id' => (int)$newId));
    }
    exit;
}

if ($action === 'deleteWorkspace') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(array('error' => 'Invalid workspace id'));
        exit;
    }
    if (!can_manage_workspace($db, $id)) {
        echo json_encode(array('error' => 'Only workspace owners or admins can delete this workspace'));
        exit;
    }
    $db->query("DELETE FROM workspace_tasks WHERE workspace_id=$id");
    $db->query("DELETE FROM workspace_members WHERE workspace_id=$id");
    $db->query("DELETE FROM workspaces WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'getWorkspacePermissions') {
    $workspaceId = (int)($_GET['workspace_id'] ?? 0);
    $userId = $apiUserId ?? '';
    $privilege = get_workspace_privilege($db, $workspaceId, $userId);
    echo json_encode(array('data' => array(
        'workspace_id' => $workspaceId,
        'user_privilege' => $privilege,
        'is_admin' => current_user_is_admin($db, $userId),
        'can_manage' => can_manage_workspace($db, $workspaceId, $userId),
        'can_delete' => can_manage_workspace($db, $workspaceId, $userId)
    )));
    exit;
}

// Workspace Members
if ($action === 'listWorkspaceMembers') {
    $workspaceId = (int)($_GET['workspace_id'] ?? 0);
    
    $tableCheck = $db->query("SHOW TABLES LIKE 'workspace_members'");
    if ($tableCheck->num_rows === 0) {
        echo json_encode(array('data' => [], 'error' => 'Table not found'));
        exit;
    }
    
    $sql = "SELECT wm.*, u.full_name
            FROM workspace_members wm
            LEFT JOIN users u ON wm.user_id = u.user_id
            WHERE wm.workspace_id=$workspaceId
            ORDER BY wm.added_at DESC";
    $result = $db->query($sql);
    $members = array();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $members[] = array(
                'id' => (int)$row['id'],
                'user_id' => $row['user_id'],
                'full_name' => $row['full_name'] ?? '',
                'privilege' => $row['privilege']
            );
        }
    }
    echo json_encode(array('data' => $members));
    exit;
}

if ($action === 'saveWorkspaceMembers') {
    $workspaceId = (int)($_POST['workspace_id'] ?? 0);
    $members = json_decode($_POST['members'] ?? '[]', true);
    $addedBy = $apiUserId;
    $now = date('Y-m-d H:i:s');
    if (!can_manage_workspace($db, $workspaceId, $addedBy)) {
        echo json_encode(array('error' => 'Only workspace owners or admins can change members'));
        exit;
    }
    
    // Check if table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'workspace_members'");
    if ($tableCheck->num_rows === 0) {
        echo json_encode(array('error' => 'Table not found'));
        exit;
    }
    
    $db->query("DELETE FROM workspace_members WHERE workspace_id=$workspaceId");
    
    if (is_array($members)) {
        foreach ($members as $m) {
            $userId = $db->real_escape_string($m['user_id'] ?? '');
            $privilege = $db->real_escape_string($m['privilege'] ?? 'member');
            if ($userId) {
                $db->query("INSERT INTO workspace_members (workspace_id, user_id, privilege, added_by, added_at) 
                         VALUES ($workspaceId, '$userId', '$privilege', '$addedBy', '$now')");
            }
        }
    }
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'getWorkspaceBoard') {
    $workspaceId = (int)($_GET['workspace_id'] ?? 0);
    $userId = $apiUserId ?? '';
    if ($workspaceId <= 0) {
        echo json_encode(array('error' => 'Invalid workspace id'));
        exit;
    }

    $members = array();
    $memberResult = db_query_safe($db, "SELECT wm.user_id, wm.privilege, u.full_name FROM workspace_members wm LEFT JOIN users u ON wm.user_id = u.user_id WHERE wm.workspace_id=$workspaceId ORDER BY wm.added_at ASC", 'getWorkspaceBoard:members');
    if ($memberResult) {
        while ($row = $memberResult->fetch_assoc()) {
            $members[] = array(
                'id' => $row['user_id'],
                'name' => $row['full_name'] ?: $row['user_id'],
                'privilege' => $row['privilege']
            );
        }
    }

    if (!workspace_board_schema_ready($db)) {
        $lists = array();
        $position = 10;
        foreach (get_default_workspace_list_blueprint() as $list) {
            $lists[] = array(
                'id' => $list['slug'],
                'name' => $list['name'],
                'color' => $list['color'],
                'position' => $position
            );
            $position += 10;
        }
        $legacyCards = array();
        $legacyResult = db_query_safe($db, "SELECT t.*, u.full_name AS assignee_name FROM workspace_tasks t LEFT JOIN users u ON t.assigned_to = u.user_id WHERE t.workspace_id=$workspaceId ORDER BY t.created_at DESC", 'getWorkspaceBoard:legacy_cards');
        if ($legacyResult) {
            while ($row = $legacyResult->fetch_assoc()) {
                $legacyCards[] = array(
                    'id' => (int)$row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'priority' => (int)$row['priority'],
                    'due_date' => $row['due_date'],
                    'list_id' => $row['status'],
                    'position' => 0,
                    'member_ids' => $row['assigned_to'] ? array($row['assigned_to']) : array(),
                    'member_names' => $row['assignee_name'] ? array($row['assignee_name']) : array(),
                    'can_delete' => can_manage_workspace($db, $workspaceId, $userId)
                );
            }
        }
        echo json_encode(array('data' => array(
            'board' => array('id' => 'legacy', 'workspace_id' => $workspaceId, 'name' => 'Main Board', 'description' => ''),
            'lists' => $lists,
            'cards' => $legacyCards,
            'members' => $members,
            'permissions' => array(
                'can_manage' => can_manage_workspace($db, $workspaceId, $userId),
                'can_edit' => can_edit_workspace($db, $workspaceId, $userId)
            )
        ), 'meta' => array('legacy_mode' => true)));
        exit;
    }

    $board = ensure_workspace_board($db, $workspaceId, $userId);
    $boardId = (int)($board['id'] ?? 0);
    $lists = get_workspace_board_lists($db, $boardId);
    $cards = array();
    $cardResult = db_query_safe($db, "SELECT c.*, GROUP_CONCAT(cm.user_id ORDER BY cm.id SEPARATOR ',') AS member_ids, GROUP_CONCAT(COALESCE(u.full_name, cm.user_id) ORDER BY cm.id SEPARATOR '||') AS member_names FROM workspace_cards c LEFT JOIN workspace_card_members cm ON c.id = cm.card_id LEFT JOIN users u ON cm.user_id = u.user_id WHERE c.board_id=$boardId AND c.archived=0 GROUP BY c.id ORDER BY c.position ASC, c.updated_at DESC", 'getWorkspaceBoard:cards');
    if ($cardResult) {
        while ($row = $cardResult->fetch_assoc()) {
            $memberIds = trim((string)($row['member_ids'] ?? '')) === '' ? array() : explode(',', $row['member_ids']);
            $memberNames = trim((string)($row['member_names'] ?? '')) === '' ? array() : explode('||', $row['member_names']);
            $cards[] = array(
                'id' => (int)$row['id'],
                'board_id' => (int)$row['board_id'],
                'list_id' => (int)$row['list_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'priority' => (int)$row['priority'],
                'due_date' => $row['due_date'],
                'position' => (int)$row['position'],
                'member_ids' => $memberIds,
                'member_names' => $memberNames,
                'can_delete' => can_manage_workspace($db, $workspaceId, $userId)
            );
        }
    }
    echo json_encode(array('data' => array(
        'board' => $board,
        'lists' => $lists,
        'cards' => $cards,
        'members' => $members,
        'permissions' => array(
            'can_manage' => can_manage_workspace($db, $workspaceId, $userId),
            'can_edit' => can_edit_workspace($db, $workspaceId, $userId)
        )
    ), 'meta' => array('legacy_mode' => false)));
    exit;
}

if ($action === 'saveWorkspaceList') {
    $workspaceId = (int)($_POST['workspace_id'] ?? 0);
    $name = $db->real_escape_string(trim($_POST['name'] ?? ''));
    $color = $db->real_escape_string(trim($_POST['color'] ?? '#6b4fd6'));
    $userId = $apiUserId ?? '';
    if ($workspaceId <= 0 || $name === '') {
        echo json_encode(array('error' => 'Workspace and list name are required'));
        exit;
    }
    if (!workspace_board_schema_ready($db)) {
        echo json_encode(array('error' => 'Run the updated workspace schema to enable custom lists'));
        exit;
    }
    if (!can_manage_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Only workspace owners or admins can add lists'));
        exit;
    }
    $board = ensure_workspace_board($db, $workspaceId, $userId);
    $boardId = (int)($board['id'] ?? 0);
    $posResult = db_query_safe($db, "SELECT COALESCE(MAX(position), 0) AS max_position FROM workspace_lists WHERE board_id=$boardId", 'saveWorkspaceList:position');
    $nextPosition = $posResult && ($row = $posResult->fetch_assoc()) ? ((int)$row['max_position'] + 10) : 10;
    db_query_safe($db, "INSERT INTO workspace_lists (board_id, name, color, position, created_by) VALUES ($boardId, '$name', '$color', $nextPosition, '" . $db->real_escape_string($userId) . "')", 'saveWorkspaceList:insert');
    echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    exit;
}

if ($action === 'saveWorkspaceCard') {
    $id = (int)($_POST['id'] ?? 0);
    $workspaceId = (int)($_POST['workspace_id'] ?? 0);
    $listId = (int)($_POST['list_id'] ?? 0);
    $title = $db->real_escape_string(trim($_POST['title'] ?? ''));
    $description = $db->real_escape_string(trim($_POST['description'] ?? ''));
    $priority = (int)($_POST['priority'] ?? 2);
    $dueDate = $db->real_escape_string(trim($_POST['due_date'] ?? ''));
    $assignedTo = $db->real_escape_string(trim($_POST['assigned_to'] ?? ''));
    $memberIdsRaw = trim($_POST['member_ids'] ?? '');
    $userId = $apiUserId ?? '';
    $now = date('Y-m-d H:i:s');
    if ($workspaceId <= 0 || $listId <= 0 || $title === '') {
        echo json_encode(array('error' => 'Workspace, list and title are required'));
        exit;
    }
    if (!workspace_board_schema_ready($db)) {
        echo json_encode(array('error' => 'Run the updated workspace schema to enable cards'));
        exit;
    }
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'You do not have permission to edit this board'));
        exit;
    }
    $board = ensure_workspace_board($db, $workspaceId, $userId);
    $boardId = (int)($board['id'] ?? 0);
    $dueDateSql = $dueDate === '' ? 'NULL' : "'" . $dueDate . "'";
    if ($id > 0) {
        db_query_safe($db, "UPDATE workspace_cards SET list_id=$listId, title='$title', description='$description', priority=$priority, due_date=$dueDateSql, updated_at='$now' WHERE id=$id AND board_id=$boardId", 'saveWorkspaceCard:update');
        $cardId = $id;
    } else {
        $posResult = db_query_safe($db, "SELECT COALESCE(MAX(position), 0) AS max_position FROM workspace_cards WHERE list_id=$listId", 'saveWorkspaceCard:position');
        $nextPosition = $posResult && ($row = $posResult->fetch_assoc()) ? ((int)$row['max_position'] + 10) : 10;
        db_query_safe($db, "INSERT INTO workspace_cards (board_id, list_id, title, description, priority, due_date, position, created_by, created_at, updated_at) VALUES ($boardId, $listId, '$title', '$description', $priority, $dueDateSql, $nextPosition, '" . $db->real_escape_string($userId) . "', '$now', '$now')", 'saveWorkspaceCard:insert');
        $cardId = (int)$db->insert_id;
    }
    if ($cardId > 0) {
        $memberIds = array();
        if ($memberIdsRaw !== '') {
            $decoded = json_decode($memberIdsRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $memberId) {
                    $memberId = trim((string)$memberId);
                    if ($memberId !== '') {
                        $memberIds[] = $memberId;
                    }
                }
            }
        }
        if (!$memberIds && $assignedTo !== '') {
            $memberIds[] = $assignedTo;
        }
        $memberIds = array_values(array_unique($memberIds));
        db_query_safe($db, "DELETE FROM workspace_card_members WHERE card_id=$cardId", 'saveWorkspaceCard:clear_members');
        foreach ($memberIds as $memberId) {
            $safeMemberId = $db->real_escape_string($memberId);
            db_query_safe($db, "INSERT INTO workspace_card_members (card_id, user_id, added_by) VALUES ($cardId, '$safeMemberId', '" . $db->real_escape_string($userId) . "')", 'saveWorkspaceCard:add_member');
        }
    }
    echo json_encode(array('ok' => true, 'id' => $cardId));
    exit;
}

if ($action === 'moveWorkspaceCard') {
    $id = (int)($_POST['id'] ?? 0);
    $listId = (int)($_POST['list_id'] ?? 0);
    $workspaceId = (int)($_POST['workspace_id'] ?? 0);
    $userId = $apiUserId ?? '';
    if ($id <= 0 || $listId <= 0 || $workspaceId <= 0) {
        echo json_encode(array('error' => 'Invalid card or list'));
        exit;
    }
    if (!workspace_board_schema_ready($db)) {
        echo json_encode(array('error' => 'Run the updated workspace schema to enable card movement'));
        exit;
    }
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'You do not have permission to move cards'));
        exit;
    }
    $posResult = db_query_safe($db, "SELECT COALESCE(MAX(position), 0) AS max_position FROM workspace_cards WHERE list_id=$listId", 'moveWorkspaceCard:position');
    $nextPosition = $posResult && ($row = $posResult->fetch_assoc()) ? ((int)$row['max_position'] + 10) : 10;
    db_query_safe($db, "UPDATE workspace_cards SET list_id=$listId, position=$nextPosition, updated_at=NOW() WHERE id=$id", 'moveWorkspaceCard:update');
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'deleteWorkspaceCard') {
    $id = (int)($_POST['id'] ?? 0);
    $workspaceId = (int)($_POST['workspace_id'] ?? 0);
    $userId = $apiUserId ?? '';
    if ($id <= 0 || $workspaceId <= 0) {
        echo json_encode(array('error' => 'Invalid card'));
        exit;
    }
    if (!workspace_board_schema_ready($db)) {
        echo json_encode(array('error' => 'Run the updated workspace schema to enable cards'));
        exit;
    }
    if (!can_manage_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Only workspace owners or admins can delete cards'));
        exit;
    }
    db_query_safe($db, "DELETE wci FROM workspace_checklist_items wci INNER JOIN workspace_checklists wc ON wci.checklist_id = wc.id WHERE wc.card_id=$id", 'deleteWorkspaceCard:checklist_items');
    db_query_safe($db, "DELETE FROM workspace_card_labels WHERE card_id=$id", 'deleteWorkspaceCard:labels');
    db_query_safe($db, "DELETE FROM workspace_card_members WHERE card_id=$id", 'deleteWorkspaceCard:members');
    db_query_safe($db, "DELETE FROM workspace_card_comments WHERE card_id=$id", 'deleteWorkspaceCard:comments');
    db_query_safe($db, "DELETE FROM workspace_checklists WHERE card_id=$id", 'deleteWorkspaceCard:checklists');
    db_query_safe($db, "DELETE FROM workspace_card_activity WHERE card_id=$id", 'deleteWorkspaceCard:activity');
    db_query_safe($db, "DELETE FROM workspace_cards WHERE id=$id", 'deleteWorkspaceCard:card');
    echo json_encode(array('ok' => true));
    exit;
}

// Workspace Tasks
if ($action === 'listWorkspaceTasks') {
    $workspaceId = (int)($_GET['workspace_id'] ?? 0);
    $status = $_GET['status'] ?? '';
    $userId = $apiUserId ?? '';
    $canDelete = can_manage_workspace($db, $workspaceId, $userId);
    
    $where = "WHERE t.workspace_id=$workspaceId";
    if ($status !== '') {
        $where .= " AND t.status='" . $db->real_escape_string($status) . "'";
    }
    
    $sql = "SELECT t.*, u.full_name as assignee_name
            FROM workspace_tasks t
            LEFT JOIN users u ON t.assigned_to = u.user_id
            $where ORDER BY t.priority DESC, t.created_at DESC";
    $result = $db->query($sql);
    $tasks = array();
    while ($row = $result->fetch_assoc()) {
        $tasks[] = array(
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'status' => $row['status'],
            'priority' => $row['priority'],
            'due_date' => $row['due_date'],
            'assigned_to' => $row['assigned_to'],
            'assignee_name' => $row['assignee_name'] ?? '',
            'can_delete' => $canDelete,
            'created_at' => $row['created_at']
        );
    }
    echo json_encode(array('data' => $tasks));
    exit;
}

if ($action === 'saveWorkspaceTask') {
    $id = (int)($_POST['id'] ?? 0);
    $workspaceId = (int)($_POST['workspace_id'] ?? 0);
    $title = $db->real_escape_string(trim($_POST['title'] ?? ''));
    $description = $db->real_escape_string(trim($_POST['description'] ?? ''));
    $status = $db->real_escape_string(trim($_POST['status'] ?? 'todo'));
    $priority = (int)($_POST['priority'] ?? 2);
    $dueDate = $db->real_escape_string(trim($_POST['due_date'] ?? ''));
    $assignedTo = $db->real_escape_string(trim($_POST['assigned_to'] ?? ''));
    $userId = $apiUserId;
    $now = date('Y-m-d H:i:s');
    
    if ($title === '' || $workspaceId <= 0) {
        echo json_encode(array('error' => 'Title and workspace_id are required'));
        exit;
    }
    
    if ($id > 0) {
        $sql = "UPDATE workspace_tasks SET title='$title', description='$description', status='$status',
                priority=$priority, due_date=" . ($dueDate ? "'$dueDate'" : "NULL") . ",
                assigned_to='$assignedTo', updated_at='$now' WHERE id=$id";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => $id));
    } else {
        $sql = "INSERT INTO workspace_tasks (workspace_id, title, description, status, priority, due_date, assigned_to, created_by, created_at, updated_at)
                VALUES ($workspaceId, '$title', '$description', '$status', $priority, " . ($dueDate ? "'$dueDate'" : "NULL") . ", '$assignedTo', '$userId', '$now', '$now')";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    }
    exit;
}

if ($action === 'updateWorkspaceTaskStatus') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $db->real_escape_string(trim($_POST['status'] ?? ''));
    if ($id <= 0 || $status === '') {
        echo json_encode(array('error' => 'Invalid id or status'));
        exit;
    }
    $db->query("UPDATE workspace_tasks SET status='$status', updated_at=NOW() WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'deleteWorkspaceTask') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(array('error' => 'Invalid task id'));
        exit;
    }
    $workspaceId = 0;
    $result = db_query_safe($db, "SELECT workspace_id FROM workspace_tasks WHERE id=$id LIMIT 1", 'deleteWorkspaceTask:workspace');
    if ($result && ($row = $result->fetch_assoc())) {
        $workspaceId = (int)($row['workspace_id'] ?? 0);
    }
    if ($workspaceId <= 0 || !can_manage_workspace($db, $workspaceId)) {
        echo json_encode(array('error' => 'Only workspace owners or admins can delete tasks'));
        exit;
    }
    $db->query("DELETE FROM workspace_tasks WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'getWorkspaceTaskStats') {
    $workspaceId = (int)($_GET['workspace_id'] ?? 0);
    $sql = "SELECT status, COUNT(*) as count FROM workspace_tasks WHERE workspace_id=$workspaceId GROUP BY status";
    $result = $db->query($sql);
    $stats = array('todo' => 0, 'in_progress' => 0, 'review' => 0, 'done' => 0);
    while ($row = $result->fetch_assoc()) {
        if (isset($stats[$row['status']])) {
            $stats[$row['status']] = (int)$row['count'];
        }
    }
    echo json_encode(array('data' => $stats));
    exit;
}

if ($action === 'getWorkspaceReport') {
    $workspaceId = (int)($_GET['workspace_id'] ?? 0);
    $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    $safeStart = $db->real_escape_string($weekStart);
    $safeEnd = $db->real_escape_string($weekEnd);
    
    $report = array(
        'workspace_id' => $workspaceId,
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'total_tasks' => 0,
        'completed' => 0,
        'in_progress' => 0,
        'pending' => 0,
        'tasks' => array()
    );
    
    $result = $db->query("SELECT * FROM workspace_tasks WHERE workspace_id=$workspaceId AND created_at BETWEEN '$safeStart' AND '$safeEnd 23:59:59' ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $report['tasks'][] = $row;
        $report['total_tasks']++;
        if ($row['status'] === 'done') $report['completed']++;
        elseif ($row['status'] === 'in_progress') $report['in_progress']++;
        else $report['pending']++;
    }
    
    echo json_encode(array('data' => $report));
    exit;
}

if ($action === 'exportWorkspaceReport') {
    $workspaceId = (int)($_GET['workspace_id'] ?? 0);
    $weekStart = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="workspace_report_' . $weekStart . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Workspace Weekly Report - Week of ' . $weekStart));
    fputcsv($output, array(''));
    fputcsv($output, array('Task', 'Status', 'Assigned To', 'Due Date', 'Created'));
    
    $result = $db->query("SELECT t.*, u.full_name FROM workspace_tasks t LEFT JOIN www_users u ON t.assigned_to = u.user_id WHERE t.workspace_id=$workspaceId ORDER BY t.created_at DESC");
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array($row['title'], $row['status'], $row['full_name'] ?? '', $row['due_date'] ?? '', $row['created_at']));
    }
    
    fclose($output);
    exit;
}

// ============================================
// DASHBOARD STATS
// ============================================

if ($action === 'getDashboardStats') {
    $stats = array(
        'tasks_pending' => 0,
        'tasks_overdue' => 0,
        'workspaces' => 0
    );
    
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $monthStart = date('Y-m-01');
    $userId = $apiUserId;
    

    
    $r = $db->query("SELECT COUNT(*) as cnt FROM Tasks WHERE Status < 4");
    if ($row = $r->fetch_assoc()) $stats['tasks_pending'] = (int)$row['cnt'];
    
    $r = $db->query("SELECT COUNT(*) as cnt FROM Tasks WHERE Status < 4 AND datedue < '$today'");
    if ($row = $r->fetch_assoc()) $stats['tasks_overdue'] = (int)$row['cnt'];
    
    $r = $db->query("SELECT COUNT(*) as cnt FROM workspaces");
    if ($row = $r->fetch_assoc()) $stats['workspaces'] = (int)$row['cnt'];
    
    echo json_encode(array('data' => $stats));
    exit;
}

// ============================================
// WEEKLY REPORT - FIELD ACTIVITIES
// ============================================

if ($action === 'listFieldActivities') {
    $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    $userId = $apiUserId ?? '';
    $cards = get_weekly_board_cards($db, $userId, $weekStart, $weekEnd);
    $activities = array();
    foreach ($cards as $card) {
        $latestComment = $card['latest_comment']['comment'] ?? '';
        $activities[] = array(
            'id' => (int)$card['id'],
            'report_week' => $weekStart,
            'activity_date' => iso_date_only($card['updated_at'] ?? $card['created_at'] ?? $weekStart),
            'company' => $card['workspace_name'] ?? 'Workspace',
            'activity_type' => $card['title'] ?? 'Board Card',
            'activity_status' => $card['activity_status'] ?? 'planned',
            'contact_person' => implode(', ', $card['member_names'] ?? array()),
            'contact_phone' => '',
            'contact_email' => '',
            'designation' => $card['list_name'] ?? '',
            'current_lab' => $card['board_name'] ?? '',
            'contact_result' => $latestComment,
            'created_by' => '',
            'created_at' => $card['created_at'] ?? '',
            'updated_at' => $card['updated_at'] ?? '',
            'workspace_id' => $card['workspace_id'] ?? 0,
            'source' => 'workspace_board',
            'can_delete' => false
        );
    }
    echo json_encode(array(
        'data' => $activities,
        'meta' => array(
            'status_enabled' => true,
            'source' => 'workspace_board'
        )
    ));
    exit;
}

if ($action === 'saveFieldActivity') {
    echo json_encode(array('error' => 'Field activity editing has moved to the collaboration board. Create or update cards in workspace_kanban.php.'));
    exit;
}

if ($action === 'deleteFieldActivity') {
    echo json_encode(array('error' => 'Field activity deletion has moved to the collaboration board. Delete the card from workspace_kanban.php.'));
    exit;
}

if ($action === 'updateFieldActivityStatus') {
    echo json_encode(array('error' => 'Field activity status now comes from the collaboration board list. Move the card on workspace_kanban.php.'));
    exit;
}

// ============================================
// WEEKLY REPORT - METRICS
// ============================================

if ($action === 'getWeeklyMetrics') {
    $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
    $safeWeek = $db->real_escape_string($weekStart);
    $userId = $apiUserId;
    if (!table_exists($db, 'weekly_report_metrics')) {
        echo json_encode(array('data' => null, 'meta' => array('table_available' => false)));
        exit;
    }
    
    $sql = "SELECT * FROM weekly_report_metrics WHERE report_week='$safeWeek' AND user_id='$userId' LIMIT 1";
    $result = db_query_safe($db, $sql, 'getWeeklyMetrics');
    if ($result && ($row = $result->fetch_assoc())) {
        echo json_encode(array('data' => $row, 'meta' => array('table_available' => true)));
    } else {
        echo json_encode(array('data' => null, 'meta' => array('table_available' => true)));
    }
    exit;
}

if ($action === 'saveWeeklyMetrics') {
    $weekStart = $db->real_escape_string($_POST['week_start'] ?? date('Y-m-d', strtotime('monday this week')));
    $userId = $apiUserId;
    $notes = $db->real_escape_string($_POST['notes'] ?? '');
    $now = date('Y-m-d H:i:s');
    
    $leadsCreated = (int)($_POST['leads_created'] ?? 0);
    $leadsConverted = (int)($_POST['leads_converted'] ?? 0);
    $opportunitiesCreated = (int)($_POST['opportunities_created'] ?? 0);
    $opportunitiesWon = (int)($_POST['opportunities_won'] ?? 0);
    $pipelineValue = (float)($_POST['pipeline_value'] ?? 0);
    $tasksCompleted = (int)($_POST['tasks_completed'] ?? 0);
    $tasksCreated = (int)($_POST['tasks_created'] ?? 0);
    $visitsPlanned = (int)($_POST['visits_planned'] ?? 0);
    $visitsCompleted = (int)($_POST['visits_completed'] ?? 0);
    if (!table_exists($db, 'weekly_report_metrics')) {
        echo json_encode(array('error' => 'weekly_report_metrics table not found'));
        exit;
    }
    
    $sql = "INSERT INTO weekly_report_metrics 
            (report_week, user_id, leads_created, leads_converted, opportunities_created, opportunities_won, pipeline_value, tasks_completed, tasks_created, visits_planned, visits_completed, notes)
            VALUES ('$weekStart', '$userId', $leadsCreated, $leadsConverted, $opportunitiesCreated, $opportunitiesWon, $pipelineValue, $tasksCompleted, $tasksCreated, $visitsPlanned, $visitsCompleted, '$notes')
            ON DUPLICATE KEY UPDATE 
            leads_created=$leadsCreated, leads_converted=$leadsConverted, 
            opportunities_created=$opportunitiesCreated, opportunities_won=$opportunitiesWon,
            pipeline_value=$pipelineValue, tasks_completed=$tasksCompleted, tasks_created=$tasksCreated,
            visits_planned=$visitsPlanned, visits_completed=$visitsCompleted, notes='$notes'";
    $result = db_query_safe($db, $sql, 'saveWeeklyMetrics');
    if ($result === false) {
        echo json_encode(array('error' => 'Failed to save weekly metrics'));
        exit;
    }
    echo json_encode(array('ok' => true));
    exit;
}

// ============================================
// COMBINED WEEKLY REPORT
// ============================================

if ($action === 'getWeeklyReport') {
    $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    $safeStart = $db->real_escape_string($weekStart);
    $safeEnd = $db->real_escape_string($weekEnd);
    $userId = $apiUserId;
    $reportTitle = trim($_GET['title'] ?? 'WEEKLY REPORT');
    if ($reportTitle === '') {
        $reportTitle = 'WEEKLY REPORT';
    }
    
    $report = array(
        'report_info' => array(
            'title' => $reportTitle,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $userId
        ),
        'crm_metrics' => array(
            'tasks_completed' => 0,
            'tasks_created' => 0
        ),
        'activity_metrics' => array(
            'logged' => 0,
            'planned' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0
        ),
        'board_activities' => array(),
        'field_activities' => array(),
        'recent_tasks' => array(),
        'user_metrics' => array(
            'leads_converted' => 0,
            'opportunities_won' => 0,
            'visits_planned' => 0,
            'visits_completed' => 0,
            'notes' => ''
        ),
        'notes' => ''
    );
    
    // CRM Metrics from database
    $r = db_query_safe($db, "SELECT COUNT(*) as cnt FROM Tasks WHERE TaskOwner='$userId' AND datecreated BETWEEN '$safeStart' AND '$safeEnd 23:59:59'", 'getWeeklyReport:tasks_created');
    if ($r && ($row = $r->fetch_assoc())) $report['crm_metrics']['tasks_created'] = (int)$row['cnt'];
    
    $r = db_query_safe($db, "SELECT COUNT(*) as cnt FROM Tasks WHERE TaskOwner='$userId' AND Status=4 AND lastactivity BETWEEN '$safeStart' AND '$safeEnd 23:59:59'", 'getWeeklyReport:tasks_completed');
    if ($r && ($row = $r->fetch_assoc())) $report['crm_metrics']['tasks_completed'] = (int)$row['cnt'];

    // Also include Workspace Cards
    if (workspace_board_schema_ready($db)) {
        $r_card = db_query_safe($db, "SELECT COUNT(*) as cnt FROM workspace_cards WHERE created_by='$userId' AND created_at BETWEEN '$safeStart' AND '$safeEnd 23:59:59'", 'getWeeklyReport:card_created');
        if ($r_card && ($rowCard = $r_card->fetch_assoc())) $report['crm_metrics']['tasks_created'] += (int)$rowCard['cnt'];
    }
    

    $boardActivities = get_weekly_board_cards($db, $userId, $weekStart, $weekEnd);
    foreach ($boardActivities as $card) {
        $status = $card['activity_status'] ?? 'planned';
        if (!isset($report['activity_metrics'][$status])) {
            $status = 'planned';
        }
        $report['activity_metrics']['logged']++;
        $report['activity_metrics'][$status]++;
        if ($status === 'completed') {
            $report['crm_metrics']['tasks_completed']++;
        }
    }
    $report['board_activities'] = $boardActivities;
    $report['field_activities'] = $boardActivities;
    $report['report_info']['source'] = 'workspace_board';
    $report['report_info']['source_note'] = 'Board cards are the source of truth for this weekly report.';
    
    // Weekly Metrics (user entered)
    $r = db_query_safe($db, "SELECT * FROM weekly_report_metrics WHERE report_week='$safeStart' AND user_id='$userId' LIMIT 1", 'getWeeklyReport:user_metrics');
    if ($r && ($row = $r->fetch_assoc())) {
        $report['user_metrics'] = array(
            'leads_converted' => (int)$row['leads_converted'],
            'opportunities_won' => (int)$row['opportunities_won'],
            'visits_planned' => (int)$row['visits_planned'],
            'visits_completed' => (int)$row['visits_completed'],
            'notes' => $row['notes']
        );
        $report['notes'] = $row['notes'];
    }
    
    // Recent Tasks (this week)
    $result = db_query_safe($db, "SELECT pkey, Taskname, Status, TaskOwner, datedue, taskdetails FROM Tasks WHERE TaskOwner='$userId' AND datecreated BETWEEN '$safeStart' AND '$safeEnd 23:59:59' ORDER BY datecreated DESC LIMIT 10", 'getWeeklyReport:recent_tasks');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['recent_tasks'][] = $row;
        }
    }
    

    echo json_encode(array('data' => $report));
    exit;
}

if ($action === 'exportWeeklyReport') {
    $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    $safeStart = $db->real_escape_string($weekStart);
    $userId = $apiUserId;
    $reportTitle = trim($_GET['title'] ?? 'WEEKLY REPORT');
    if ($reportTitle === '') {
        $reportTitle = 'WEEKLY REPORT';
    }
    
    require_once($PathPrefix . 'vendor/autoload.php');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Weekly_Report_' . str_replace('-', '', $weekStart) . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Weekly Report');
    $rowNum = 1;
    
    $writeRow = function($data) use (&$sheet, &$rowNum) {
        $sheet->fromArray([$data], NULL, 'A' . $rowNum);
        $rowNum++;
    };
    
    $boardActivities = get_weekly_board_cards($db, $userId, $weekStart, $weekEnd);
    
    // Report Header
    $writeRow(array($reportTitle));
    $writeRow(array('Week:', $weekStart . ' to ' . $weekEnd));
    $writeRow(array('Generated:', date('Y-m-d H:i:s')));
    $writeRow(array('Source:', 'Workspace Board'));
    $writeRow(array(''));
    
    // CRM Summary
    $writeRow(array('CRM SUMMARY'));
    $writeRow(array('Metric', 'Value'));
    
    $r = db_query_safe($db, "SELECT COUNT(*) as cnt FROM Tasks WHERE TaskOwner='$userId' AND datecreated BETWEEN '$safeStart' AND '$weekEnd 23:59:59'", 'exportWeeklyReport:tasks_created');
    $row = $r ? $r->fetch_assoc() : array('cnt' => 0);
    $cntTasksCreated = (int)($row['cnt'] ?? 0);
    
    if (workspace_board_schema_ready($db)) {
        $r_card = db_query_safe($db, "SELECT COUNT(*) as cnt FROM workspace_cards WHERE created_by='$userId' AND created_at BETWEEN '$safeStart' AND '$weekEnd 23:59:59'", 'exportWeeklyReport:card_created');
        if ($r_card && ($rowCard = $r_card->fetch_assoc())) $cntTasksCreated += (int)$rowCard['cnt'];
    }
    $writeRow(array('Tasks Created', $cntTasksCreated));
    
    $r = db_query_safe($db, "SELECT COUNT(*) as cnt FROM Tasks WHERE TaskOwner='$userId' AND Status=4 AND lastactivity BETWEEN '$safeStart' AND '$weekEnd 23:59:59'", 'exportWeeklyReport:tasks_completed');
    $row = $r ? $r->fetch_assoc() : array('cnt' => 0);
    $cntTasksCompleted = (int)($row['cnt'] ?? 0);
    foreach ($boardActivities as $activity) {
        if (($activity['activity_status'] ?? '') === 'completed') {
            $cntTasksCompleted++;
        }
    }
    $writeRow(array('Tasks Completed', $cntTasksCompleted));
    

    
    $writeRow(array(''));
    
    $writeRow(array('BOARD ACTIVITIES'));
    $writeRow(array('Updated', 'Workspace', 'Board', 'List', 'Status', 'Card', 'Assignees', 'Checklist', 'Labels', 'Latest Comment', 'Due Date'));
    foreach ($boardActivities as $activity) {
        $labelText = array();
        foreach (($activity['labels'] ?? array()) as $label) {
            $labelText[] = trim(($label['name'] ?? '') . ' ' . ($label['color'] ?? ''));
        }
        $latestComment = $activity['latest_comment']['comment'] ?? '';
        $latestCommentMeta = $activity['latest_comment']['full_name'] ?? '';
        $latestCommentText = trim($latestCommentMeta . ($latestComment !== '' ? ': ' . $latestComment : ''));
        $assignees = implode(', ', $activity['member_names'] ?? array());
        $checklistSummary = ($activity['checklist_done'] ?? 0) . '/' . ($activity['checklist_total'] ?? 0);
        $writeRow(array(
            $activity['updated_at'] ?? '',
            $activity['workspace_name'] ?? '',
            $activity['board_name'] ?? '',
            $activity['list_name'] ?? '',
            $activity['activity_status'] ?? '',
            $activity['title'] ?? '',
            $assignees,
            $checklistSummary,
            implode(' | ', $labelText),
            $latestCommentText,
            $activity['due_date'] ?? ''
        ));
    }
    
    $writeRow(array(''));
    $writeRow(array('GANTT SNAPSHOT'));
    $writeRow(array('Card', 'Timeline Start', 'Timeline End', 'Checklist Done', 'Checklist Total', 'Status'));
    foreach ($boardActivities as $activity) {
        $writeRow(array(
            $activity['title'] ?? '',
            $activity['timeline_start'] ?? '',
            $activity['timeline_end'] ?? '',
            $activity['checklist_done'] ?? 0,
            $activity['checklist_total'] ?? 0,
            $activity['activity_status'] ?? ''
        ));
    }
    
    $writeRow(array(''));
    
    // User Metrics
    $writeRow(array('PERFORMANCE METRICS'));
    $writeRow(array('Metric', 'Planned', 'Actual'));
    
    $r = db_query_safe($db, "SELECT * FROM weekly_report_metrics WHERE report_week='$safeStart' AND user_id='$userId' LIMIT 1", 'exportWeeklyReport:user_metrics');
    if ($r && ($row = $r->fetch_assoc())) {
        $writeRow(array('Site Visits', $row['visits_planned'], $row['visits_completed']));
        $writeRow(array('Leads Converted', '-', $row['leads_converted']));
        $writeRow(array('Opportunities Won', '-', $row['opportunities_won']));
        $writeRow(array(''));
        $writeRow(array('Notes:'));
        $writeRow(array($row['notes']));
    }
    
    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ============================================
// EMAIL TEMPLATES
// ============================================

if ($action === 'listEmailTemplates') {
    $category = $_GET['category'] ?? '';
    $where = $category ? "WHERE category='" . $db->real_escape_string($category) . "' AND is_active=1" : "WHERE is_active=1";
    $sql = "SELECT * FROM ws_email_templates $where ORDER BY template_name";
    $result = $db->query($sql);
    $templates = array();
    while ($row = $result->fetch_assoc()) {
        $row['variables'] = json_decode($row['variables'] ?? '[]', true);
        $templates[] = $row;
    }
    echo json_encode(array('data' => $templates));
    exit;
}

if ($action === 'getEmailTemplate') {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "SELECT * FROM ws_email_templates WHERE id=$id LIMIT 1";
    $result = $db->query($sql);
    if ($row = $result->fetch_assoc()) {
        $row['variables'] = json_decode($row['variables'] ?? '[]', true);
        echo json_encode(array('data' => $row));
    } else {
        echo json_encode(array('error' => 'Template not found'));
    }
    exit;
}

if ($action === 'saveEmailTemplate') {
    $id = (int)($_POST['id'] ?? 0);
    $name = $db->real_escape_string($_POST['template_name'] ?? '');
    $subject = $db->real_escape_string($_POST['subject'] ?? '');
    $body = $db->real_escape_string($_POST['body'] ?? '');
    $category = $db->real_escape_string($_POST['category'] ?? 'general');
    $variables = $_POST['variables'] ?? array();
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $userId = $apiUserId;
    $now = date('Y-m-d H:i:s');
    $varsJson = json_encode(is_array($variables) ? $variables : array());
    
    if ($name === '' || $subject === '' || $body === '') {
        echo json_encode(array('error' => 'Template name, subject, and body are required'));
        exit;
    }
    
    if ($id > 0) {
        $sql = "UPDATE ws_email_templates SET template_name='$name', subject='$subject', body='$body', 
                category='$category', variables='$varsJson', is_active=$isActive WHERE id=$id";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => $id));
    } else {
        $sql = "INSERT INTO ws_email_templates (template_name, subject, body, category, variables, is_active, created_by) 
                VALUES ('$name', '$subject', '$body', '$category', '$varsJson', $isActive, '$userId')";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    }
    exit;
}

if ($action === 'deleteEmailTemplate') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("DELETE FROM ws_email_templates WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'renderEmailTemplate') {
    $templateId = (int)($_POST['template_id'] ?? 0);
    $variables = json_decode($_POST['variables'] ?? '{}', true);
    
    $result = $db->query("SELECT * FROM ws_email_templates WHERE id=$templateId LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $subject = $row['subject'];
        $body = $row['body'];
        foreach ($variables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }
        echo json_encode(array('data' => array('subject' => $subject, 'body' => $body)));
    } else {
        echo json_encode(array('error' => 'Template not found'));
    }
    exit;
}

// ============================================
// EMAIL RULES (Automation)
// ============================================

if ($action === 'listEmailRules') {
    $activeOnly = !isset($_GET['include_inactive']);
    $where = $activeOnly ? "WHERE r.is_active=1" : "";
    $sql = "SELECT r.*, t.template_name 
            FROM ws_email_rules r 
            LEFT JOIN ws_email_templates t ON r.email_template_id = t.id 
            $where ORDER BY r.priority DESC, r.created_at DESC";
    $result = $db->query($sql);
    $rules = array();
    while ($row = $result->fetch_assoc()) {
        $row['trigger_conditions'] = json_decode($row['trigger_conditions'] ?? '{}', true);
        $row['recipients'] = json_decode($row['recipients'] ?? '[]', true);
        $rules[] = $row;
    }
    echo json_encode(array('data' => $rules));
    exit;
}

if ($action === 'getEmailRule') {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "SELECT r.*, t.template_name, t.subject as template_subject, t.body as template_body 
            FROM ws_email_rules r 
            LEFT JOIN ws_email_templates t ON r.email_template_id = t.id 
            WHERE r.id=$id LIMIT 1";
    $result = $db->query($sql);
    if ($row = $result->fetch_assoc()) {
        $row['trigger_conditions'] = json_decode($row['trigger_conditions'] ?? '{}', true);
        $row['recipients'] = json_decode($row['recipients'] ?? '[]', true);
        echo json_encode(array('data' => $row));
    } else {
        echo json_encode(array('error' => 'Rule not found'));
    }
    exit;
}

if ($action === 'saveEmailRule') {
    $id = (int)($_POST['id'] ?? 0);
    $name = $db->real_escape_string($_POST['rule_name'] ?? '');
    $description = $db->real_escape_string($_POST['description'] ?? '');
    $triggerType = $db->real_escape_string($_POST['trigger_type'] ?? '');
    $triggerConditions = $_POST['trigger_conditions'] ?? '{}';
    $templateId = (int)($_POST['email_template_id'] ?? 0) ?: 'NULL';
    $recipients = $_POST['recipients'] ?? '[]';
    $cc = $db->real_escape_string($_POST['cc_addresses'] ?? '');
    $bcc = $db->real_escape_string($_POST['bcc_addresses'] ?? '');
    $subjectOverride = $db->real_escape_string($_POST['subject_override'] ?? '');
    $bodyOverride = $db->real_escape_string($_POST['body_override'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $priority = (int)($_POST['priority'] ?? 0);
    $userId = $apiUserId;
    $now = date('Y-m-d H:i:s');
    
    if ($name === '' || $triggerType === '') {
        echo json_encode(array('error' => 'Rule name and trigger type are required'));
        exit;
    }
    
    $conditionsJson = is_array($triggerConditions) ? json_encode($triggerConditions) : $triggerConditions;
    $recipientsJson = is_array($recipients) ? json_encode($recipients) : $recipients;
    
    if ($id > 0) {
        $sql = "UPDATE ws_email_rules SET rule_name='$name', description='$description', trigger_type='$triggerType',
                trigger_conditions='$conditionsJson', email_template_id=$templateId, recipients='$recipientsJson',
                cc_addresses='$cc', bcc_addresses='$bcc', subject_override='$subjectOverride', 
                body_override='$bodyOverride', is_active=$isActive, priority=$priority WHERE id=$id";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => $id));
    } else {
        $sql = "INSERT INTO ws_email_rules (rule_name, description, trigger_type, trigger_conditions, email_template_id, 
                recipients, cc_addresses, bcc_addresses, subject_override, body_override, is_active, priority, created_by)
                VALUES ('$name', '$description', '$triggerType', '$conditionsJson', $templateId, '$recipientsJson',
                '$cc', '$bcc', '$subjectOverride', '$bodyOverride', $isActive, $priority, '$userId')";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    }
    exit;
}

if ($action === 'toggleEmailRule') {
    $id = (int)($_POST['id'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $db->query("UPDATE ws_email_rules SET is_active=$active WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'deleteEmailRule') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("DELETE FROM ws_email_rules WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'triggerEmailRule') {
    $ruleId = (int)($_POST['rule_id'] ?? 0);
    $context = json_decode($_POST['context'] ?? '{}', true);
    
    $result = $db->query("SELECT r.*, t.subject, t.body, t.variables 
                          FROM ws_email_rules r 
                          LEFT JOIN ws_email_templates t ON r.email_template_id = t.id 
                          WHERE r.id=$ruleId AND r.is_active=1 LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $subject = $row['subject_override'] ?: $row['subject'];
        $body = $row['body_override'] ?: $row['body'];
        
        foreach ($context as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }
        
        $recipients = json_decode($row['recipients'] ?? '[]', true);
        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? '';
            $name = $recipient['name'] ?? '';
            if ($email) {
                queueEmail($db, null, null, $email, $name, $subject, $body, $row['cc_addresses'], $row['bcc_addresses'], $row['id']);
            }
        }
        echo json_encode(array('ok' => true, 'message' => 'Email queued for ' . count($recipients) . ' recipient(s)'));
    } else {
        echo json_encode(array('error' => 'Rule not found or inactive'));
    }
    exit;
}

function queueEmail($db, $reminderId, $ruleId, $email, $name, $subject, $body, $cc = '', $bcc = '', $relatedRuleId = null) {
    $now = date('Y-m-d H:i:s');
    $ruleIdSql = $ruleId ? (int)$ruleId : 'NULL';
    $reminderIdSql = $reminderId ? (int)$reminderId : 'NULL';
    
    $sql = "INSERT INTO ws_email_queue (reminder_id, email_rule_id, recipient_email, recipient_name, cc_addresses, bcc_addresses, subject, body, status, send_at)
            VALUES ($reminderIdSql, $ruleIdSql, '" . $db->real_escape_string($email) . "', '" . $db->real_escape_string($name) . "',
                    '" . $db->real_escape_string($cc) . "', '" . $db->real_escape_string($bcc) . "',
                    '" . $db->real_escape_string($subject) . "', '" . $db->real_escape_string($body) . "', 'pending', '$now')";
    $db->query($sql);
    
    if ($ruleId) {
        $db->query("INSERT INTO ws_email_log (email_rule_id, reminder_id, recipient_email, recipient_name, subject, status) 
                    VALUES ($ruleIdSql, $reminderIdSql, '" . $db->real_escape_string($email) . "', '" . $db->real_escape_string($name) . "', 
                            '" . $db->real_escape_string($subject) . "', 'pending')");
    }
}

// ============================================
// REMINDERS
// ============================================

if ($action === 'listReminders') {
    $userId = $apiUserId;
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';
    
    $whereParts = array("(recipient_user='$userId' OR recipient_email IS NOT NULL)");
    if ($status) $whereParts[] = "status='" . $db->real_escape_string($status) . "'";
    if ($type) $whereParts[] = "reminder_type='" . $db->real_escape_string($type) . "'";
    
    $where = 'WHERE ' . implode(' AND ', $whereParts);
    $sql = "SELECT * FROM ws_reminders $where ORDER BY reminder_date ASC";
    $result = $db->query($sql);
    $ws_reminders = array();
    while ($row = $result->fetch_assoc()) {
        $ws_reminders[] = $row;
    }
    echo json_encode(array('data' => $ws_reminders));
    exit;
}

if ($action === 'getUpcomingReminders') {
    $userId = $apiUserId;
    $hours = (int)($_GET['hours'] ?? 24);
    
    $sql = "SELECT * FROM ws_reminders 
            WHERE status='pending' 
            AND reminder_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL $hours HOUR)
            AND (recipient_user='$userId' OR recipient_email IS NOT NULL)
            ORDER BY reminder_date ASC LIMIT 50";
    $result = $db->query($sql);
    $ws_reminders = array();
    while ($row = $result->fetch_assoc()) {
        $ws_reminders[] = $row;
    }
    echo json_encode(array('data' => $ws_reminders));
    exit;
}

if ($action === 'saveReminder') {
    $id = (int)($_POST['id'] ?? 0);
    $type = $db->real_escape_string($_POST['reminder_type'] ?? 'custom');
    $referenceId = (int)($_POST['reference_id'] ?? 0) ?: 'NULL';
    $title = $db->real_escape_string($_POST['title'] ?? '');
    $description = $db->real_escape_string($_POST['description'] ?? '');
    $reminderDate = $db->real_escape_string($_POST['reminder_date'] ?? '');
    $beforeMinutes = (int)($_POST['remind_before_minutes'] ?? 30);
    $recipientUser = $db->real_escape_string($_POST['recipient_user'] ?? '');
    $recipientEmail = $db->real_escape_string($_POST['recipient_email'] ?? '');
    $sendNotification = isset($_POST['send_notification']) ? 1 : 0;
    $sendEmail = isset($_POST['send_email']) ? 1 : 0;
    $includeCalendar = isset($_POST['include_calendar']) ? 1 : 0;
    $userId = $apiUserId;
    $now = date('Y-m-d H:i:s');
    
    if ($title === '' || $reminderDate === '') {
        echo json_encode(array('error' => 'Title and reminder date are required'));
        exit;
    }
    
    if ($id > 0) {
        $sql = "UPDATE ws_reminders SET reminder_type='$type', reference_id=$referenceId, title='$title', description='$description',
                reminder_date='$reminderDate', remind_before_minutes=$beforeMinutes, recipient_user='$recipientUser',
                recipient_email='$recipientEmail', send_notification=$sendNotification, send_email=$sendEmail,
                include_calendar=$includeCalendar WHERE id=$id";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => $id));
    } else {
        $sql = "INSERT INTO ws_reminders (reminder_type, reference_id, title, description, reminder_date, remind_before_minutes,
                recipient_user, recipient_email, send_notification, send_email, include_calendar, created_by)
                VALUES ('$type', $referenceId, '$title', '$description', '$reminderDate', $beforeMinutes,
                '$recipientUser', '$recipientEmail', $sendNotification, $sendEmail, $includeCalendar, '$userId')";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    }
    exit;
}

if ($action === 'createTaskReminder') {
    $taskId = (int)($_POST['task_id'] ?? 0);
    $remindBefore = (int)($_POST['remind_before_minutes'] ?? 60);
    $sendEmail = isset($_POST['send_email']) ? 1 : 1;
    
    $result = $db->query("SELECT pkey, Taskname, datedue, TaskOwner FROM Tasks WHERE pkey=$taskId LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $dueDate = $row['datedue'];
        if (!$dueDate) {
            echo json_encode(array('error' => 'Task has no due date'));
            exit;
        }
        
        $reminderDate = date('Y-m-d H:i:s', strtotime("-$remindBefore minutes", strtotime($dueDate . ' 09:00:00')));
        $userResult = $db->query("SELECT email, full_name FROM www_users WHERE user_id='" . $db->real_escape_string($row['TaskOwner']) . "' LIMIT 1");
        $userRow = $userResult->fetch_assoc();
        
        $sql = "INSERT INTO ws_reminders (reminder_type, reference_id, title, description, reminder_date, remind_before_minutes,
                recipient_user, recipient_email, send_notification, send_email, include_calendar, created_by)
                VALUES ('task', $taskId, 'Task Reminder: " . $db->real_escape_string($row['Taskname']) . "',
                        'Due date: $dueDate', '$reminderDate', $remindBefore, '" . $db->real_escape_string($row['TaskOwner']) . "',
                        '" . $db->real_escape_string($userRow['email'] ?? '') . "', 1, $sendEmail, 1, '" . $db->real_escape_string($row['TaskOwner']) . "')";
        $db->query($sql);
        echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    } else {
        echo json_encode(array('error' => 'Task not found'));
    }
    exit;
}

if ($action === 'completeReminder') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("UPDATE ws_reminders SET status='completed', updated_at=NOW() WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'cancelReminder') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("UPDATE ws_reminders SET status='cancelled', updated_at=NOW() WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'deleteReminder') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("DELETE FROM ws_reminders WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

// ============================================
// EMAIL QUEUE
// ============================================

if ($action === 'listEmailQueue') {
    $status = $_GET['status'] ?? '';
    $where = $status ? "WHERE status='" . $db->real_escape_string($status) . "'" : "";
    $sql = "SELECT * FROM ws_email_queue $where ORDER BY created_at DESC LIMIT 100";
    $result = $db->query($sql);
    $queue = array();
    while ($row = $result->fetch_assoc()) {
        $queue[] = $row;
    }
    echo json_encode(array('data' => $queue));
    exit;
}

if ($action === 'retryEmail') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("UPDATE ws_email_queue SET status='pending', error_message=NULL WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'cancelEmail') {
    $id = (int)($_POST['id'] ?? 0);
    $db->query("UPDATE ws_email_queue SET status='cancelled' WHERE id=$id");
    echo json_encode(array('ok' => true));
    exit;
}

// ============================================
// EMAIL LOG
// ============================================

if ($action === 'listEmailLog') {
    $limit = (int)($_GET['limit'] ?? 100);
    $sql = "SELECT * FROM ws_email_log ORDER BY sent_at DESC LIMIT $limit";
    $result = $db->query($sql);
    $log = array();
    while ($row = $result->fetch_assoc()) {
        $log[] = $row;
    }
    echo json_encode(array('data' => $log));
    exit;
}

// ============================================
// CRON: Process Reminders (call this via cron job)
// ============================================

if ($action === 'processReminders') {
    $now = date('Y-m-d H:i:s');
    $windowStart = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    $windowEnd = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    $result = $db->query("SELECT * FROM ws_reminders 
                          WHERE status='pending' 
                          AND reminder_date BETWEEN '$windowStart' AND '$windowEnd'");
    
    $processed = 0;
    while ($row = $result->fetch_assoc()) {
        $context = array(
            'title' => $row['title'],
            'description' => $row['description'],
            'reminder_date' => $row['reminder_date'],
            'user_name' => $row['recipient_user']
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
                queueEmail($db, $row['id'], null, $row['recipient_email'], $row['recipient_user'], $subject, $body);
            }
        }
        
        $db->query("UPDATE ws_reminders SET status='sent', sent_at=NOW() WHERE id=" . $row['id']);
        $processed++;
    }
    
    echo json_encode(array('ok' => true, 'processed' => $processed));
    exit;
}

// ============================================
// CARD DETAIL
// ============================================

if ($action === 'getCardDetail') {
    $cardId = (int)($_GET['card_id'] ?? 0);
    if ($cardId <= 0) {
        echo json_encode(array('error' => 'Card ID required'));
        exit;
    }
    $card = null;
    $cardResult = db_query_safe($db, "SELECT c.*, b.workspace_id FROM workspace_cards c JOIN workspace_boards b ON c.board_id = b.id WHERE c.id=$cardId", 'getCardDetail:card');
    if ($cardResult && ($row = $cardResult->fetch_assoc())) {
        $workspaceId = (int)$row['workspace_id'];
        $userId = $apiUserId ?? '';
        $card = array(
            'id' => (int)$row['id'],
            'board_id' => (int)$row['board_id'],
            'list_id' => (int)$row['list_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'priority' => (int)$row['priority'],
            'due_date' => $row['due_date'],
            'cover_color' => $row['cover_color'],
            'position' => (int)$row['position'],
            'archived' => (int)$row['archived'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'workspace_id' => $workspaceId,
            'can_edit' => can_edit_workspace($db, $workspaceId, $userId),
            'can_manage' => can_manage_workspace($db, $workspaceId, $userId)
        );
    }
    if (!$card) {
        echo json_encode(array('error' => 'Card not found'));
        exit;
    }
    
    $members = array();
    $memResult = db_query_safe($db, "SELECT cm.*, u.full_name FROM workspace_card_members cm LEFT JOIN users u ON cm.user_id = u.user_id WHERE cm.card_id=$cardId", 'getCardDetail:members');
    if ($memResult) {
        while ($r = $memResult->fetch_assoc()) {
            $members[] = array('user_id' => $r['user_id'], 'full_name' => $r['full_name'] ?: $r['user_id']);
        }
    }
    $card['members'] = $members;
    
    $labels = array();
    $labelResult = db_query_safe($db, "SELECT wl.* FROM workspace_card_labels wcl JOIN workspace_labels wl ON wcl.label_id = wl.id WHERE wcl.card_id=$cardId", 'getCardDetail:labels');
    if ($labelResult) {
        while ($r = $labelResult->fetch_assoc()) {
            $labels[] = array('id' => (int)$r['id'], 'name' => $r['name'], 'color' => $r['color']);
        }
    }
    $card['labels'] = $labels;
    
    $boardLabels = array();
    $boardLabelResult = db_query_safe($db, "SELECT * FROM workspace_labels WHERE board_id=" . (int)$card['board_id'] . " ORDER BY name", 'getCardDetail:board_labels');
    if ($boardLabelResult) {
        while ($r = $boardLabelResult->fetch_assoc()) {
            $boardLabels[] = array('id' => (int)$r['id'], 'name' => $r['name'], 'color' => $r['color']);
        }
    }
    $card['board_labels'] = $boardLabels;
    
    $checklists = array();
    $clResult = db_query_safe($db, "SELECT * FROM workspace_checklists WHERE card_id=$cardId ORDER BY position, id", 'getCardDetail:checklists');
    if ($clResult) {
        while ($cl = $clResult->fetch_assoc()) {
            $clId = (int)$cl['id'];
            $items = array();
            $itemResult = db_query_safe($db, "SELECT * FROM workspace_checklist_items WHERE checklist_id=$clId ORDER BY position, id", 'getCardDetail:checklist_items');
            if ($itemResult) {
                while ($it = $itemResult->fetch_assoc()) {
                    $items[] = array(
                        'id' => (int)$it['id'],
                        'title' => $it['title'],
                        'is_done' => (int)$it['is_done'],
                        'completed_by' => $it['completed_by'],
                        'completed_at' => $it['completed_at']
                    );
                }
            }
            $checklists[] = array(
                'id' => $clId,
                'title' => $cl['title'],
                'position' => (int)$cl['position'],
                'items' => $items
            );
        }
    }
    $card['checklists'] = $checklists;
    
    $comments = array();
    $comResult = db_query_safe($db, "SELECT wcc.*, u.full_name FROM workspace_card_comments wcc LEFT JOIN users u ON wcc.user_id = u.user_id WHERE wcc.card_id=$cardId ORDER BY wcc.created_at DESC", 'getCardDetail:comments');
    if ($comResult) {
        while ($c = $comResult->fetch_assoc()) {
            $comments[] = array(
                'id' => (int)$c['id'],
                'user_id' => $c['user_id'],
                'full_name' => $c['full_name'] ?: $c['user_id'],
                'comment' => $c['comment'],
                'created_at' => $c['created_at']
            );
        }
    }
    $card['comments'] = $comments;
    
    $activity = array();
    $actResult = db_query_safe($db, "SELECT wca.*, u.full_name FROM workspace_card_activity wca LEFT JOIN users u ON wca.user_id = u.user_id WHERE wca.card_id=$cardId ORDER BY wca.created_at DESC LIMIT 50", 'getCardDetail:activity');
    if ($actResult) {
        while ($a = $actResult->fetch_assoc()) {
            $activity[] = array(
                'id' => (int)$a['id'],
                'user_id' => $a['user_id'],
                'full_name' => $a['full_name'] ?: $a['user_id'],
                'action_type' => $a['action_type'],
                'details' => $a['details'],
                'created_at' => $a['created_at']
            );
        }
    }
    $card['activity'] = $activity;
    
    $lists = array();
    $listResult = db_query_safe($db, "SELECT * FROM workspace_lists WHERE board_id=" . (int)$card['board_id'] . " ORDER BY position", 'getCardDetail:lists');
    if ($listResult) {
        while ($l = $listResult->fetch_assoc()) {
            $lists[] = array('id' => (int)$l['id'], 'name' => $l['name'], 'color' => $l['color']);
        }
    }
    $card['lists'] = $lists;
    
    echo json_encode(array('data' => $card));
    exit;
}

if ($action === 'saveCardComment') {
    $cardId = (int)($_POST['card_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $userId = $apiUserId ?? '';
    $now = date('Y-m-d H:i:s');
    
    if ($cardId <= 0 || $comment === '') {
        echo json_encode(array('error' => 'Card ID and comment are required'));
        exit;
    }
    
    $wsResult = db_query_safe($db, "SELECT b.workspace_id FROM workspace_cards c JOIN workspace_boards b ON c.board_id = b.id WHERE c.id=$cardId", 'saveCardComment:workspace');
    $workspaceId = $wsResult && ($wsRow = $wsResult->fetch_assoc()) ? (int)$wsRow['workspace_id'] : 0;
    
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    $safeComment = $db->real_escape_string($comment);
    db_query_safe($db, "INSERT INTO workspace_card_comments (card_id, user_id, comment, created_at) VALUES ($cardId, '" . $db->real_escape_string($userId) . "', '$safeComment', '$now')", 'saveCardComment:insert');
    $commentId = (int)$db->insert_id;
    
    db_query_safe($db, "INSERT INTO workspace_card_activity (card_id, user_id, action_type, details, created_at) VALUES ($cardId, '" . $db->real_escape_string($userId) . "', 'comment', '" . $db->real_escape_string($comment) . "', '$now')", 'saveCardComment:activity');
    
    echo json_encode(array('ok' => true, 'id' => $commentId));
    exit;
}

if ($action === 'deleteCardComment') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $userId = $apiUserId ?? '';
    if ($commentId <= 0) {
        echo json_encode(array('error' => 'Comment ID required'));
        exit;
    }
    $result = db_query_safe($db, "SELECT wcca.card_id, wcca.user_id, b.workspace_id FROM workspace_card_comments wcca JOIN workspace_cards c ON wcca.card_id = c.id JOIN workspace_boards b ON c.board_id = b.id WHERE wcca.id=$commentId", 'deleteCardComment:check');
    if (!$result || !($row = $result->fetch_assoc())) {
        echo json_encode(array('error' => 'Comment not found'));
        exit;
    }
    $workspaceId = (int)$row['workspace_id'];
    if ($row['user_id'] !== $userId && !can_manage_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    db_query_safe($db, "DELETE FROM workspace_card_comments WHERE id=$commentId", 'deleteCardComment:delete');
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'saveCardChecklist') {
    $cardId = (int)($_POST['card_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $userId = $apiUserId ?? '';
    $now = date('Y-m-d H:i:s');
    
    if ($cardId <= 0 || $title === '') {
        echo json_encode(array('error' => 'Card ID and title are required'));
        exit;
    }
    
    $wsResult = db_query_safe($db, "SELECT b.workspace_id FROM workspace_cards c JOIN workspace_boards b ON c.board_id = b.id WHERE c.id=$cardId", 'saveCardChecklist:workspace');
    $workspaceId = $wsResult && ($wsRow = $wsResult->fetch_assoc()) ? (int)$wsRow['workspace_id'] : 0;
    
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    $posResult = db_query_safe($db, "SELECT COALESCE(MAX(position), 0) + 10 AS pos FROM workspace_checklists WHERE card_id=$cardId", 'saveCardChecklist:position');
    $position = $posResult && ($pRow = $posResult->fetch_assoc()) ? (int)$pRow['pos'] : 10;
    
    $safeTitle = $db->real_escape_string($title);
    db_query_safe($db, "INSERT INTO workspace_checklists (card_id, title, position, created_by, created_at) VALUES ($cardId, '$safeTitle', $position, '" . $db->real_escape_string($userId) . "', '$now')", 'saveCardChecklist:insert');
    $checklistId = (int)$db->insert_id;
    
    db_query_safe($db, "INSERT INTO workspace_card_activity (card_id, user_id, action_type, details, created_at) VALUES ($cardId, '" . $db->real_escape_string($userId) . "', 'checklist_added', '" . $db->real_escape_string($title) . "', '$now')", 'saveCardChecklist:activity');
    
    echo json_encode(array('ok' => true, 'id' => $checklistId));
    exit;
}

if ($action === 'deleteCardChecklist') {
    $checklistId = (int)($_POST['checklist_id'] ?? 0);
    $userId = $apiUserId ?? '';
    if ($checklistId <= 0) {
        echo json_encode(array('error' => 'Checklist ID required'));
        exit;
    }
    $result = db_query_safe($db, "SELECT cc.card_id, b.workspace_id FROM workspace_checklists cc JOIN workspace_cards c ON cc.card_id = c.id JOIN workspace_boards b ON c.board_id = b.id WHERE cc.id=$checklistId", 'deleteCardChecklist:check');
    if (!$result || !($row = $result->fetch_assoc())) {
        echo json_encode(array('error' => 'Checklist not found'));
        exit;
    }
    if (!can_edit_workspace($db, (int)$row['workspace_id'], $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    db_query_safe($db, "DELETE FROM workspace_checklist_items WHERE checklist_id=$checklistId", 'deleteCardChecklist:items');
    db_query_safe($db, "DELETE FROM workspace_checklists WHERE id=$checklistId", 'deleteCardChecklist:checklist');
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'saveChecklistItem') {
    $checklistId = (int)($_POST['checklist_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $userId = $apiUserId ?? '';
    $now = date('Y-m-d H:i:s');
    
    if ($checklistId <= 0 || $title === '') {
        echo json_encode(array('error' => 'Checklist ID and title are required'));
        exit;
    }
    
    $clResult = db_query_safe($db, "SELECT cc.card_id, b.workspace_id FROM workspace_checklists cc JOIN workspace_cards c ON cc.card_id = c.id JOIN workspace_boards b ON c.board_id = b.id WHERE cc.id=$checklistId", 'saveChecklistItem:check');
    $workspaceId = $clResult && ($clRow = $clResult->fetch_assoc()) ? (int)$clRow['workspace_id'] : 0;
    
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    $posResult = db_query_safe($db, "SELECT COALESCE(MAX(position), 0) + 10 AS pos FROM workspace_checklist_items WHERE checklist_id=$checklistId", 'saveChecklistItem:position');
    $position = $posResult && ($pRow = $posResult->fetch_assoc()) ? (int)$pRow['pos'] : 10;
    
    $safeTitle = $db->real_escape_string($title);
    db_query_safe($db, "INSERT INTO workspace_checklist_items (checklist_id, title, position, created_at) VALUES ($checklistId, '$safeTitle', $position, '$now')", 'saveChecklistItem:insert');
    
    echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    exit;
}

if ($action === 'toggleChecklistItem') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $userId = $apiUserId ?? '';
    $now = date('Y-m-d H:i:s');
    
    if ($itemId <= 0) {
        echo json_encode(array('error' => 'Item ID required'));
        exit;
    }
    
    $itemResult = db_query_safe($db, "SELECT wci.*, cc.card_id, b.workspace_id FROM workspace_checklist_items wci JOIN workspace_checklists cc ON wci.checklist_id = cc.id JOIN workspace_cards c ON cc.card_id = c.id JOIN workspace_boards b ON c.board_id = b.id WHERE wci.id=$itemId", 'toggleChecklistItem:check');
    if (!$itemResult || !($itemRow = $itemResult->fetch_assoc())) {
        echo json_encode(array('error' => 'Item not found'));
        exit;
    }
    
    if (!can_edit_workspace($db, (int)$itemRow['workspace_id'], $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    $currentState = (int)$itemRow['is_done'];
    $newState = $currentState ? 0 : 1;
    $completedBy = $newState ? "'" . $db->real_escape_string($userId) . "'" : 'NULL';
    $completedAt = $newState ? "'$now'" : 'NULL';
    
    db_query_safe($db, "UPDATE workspace_checklist_items SET is_done=$newState, completed_by=$completedBy, completed_at=$completedAt, updated_at='$now' WHERE id=$itemId", 'toggleChecklistItem:update');
    
    $actionType = $newState ? 'checklist_item_done' : 'checklist_item_undone';
    db_query_safe($db, "INSERT INTO workspace_card_activity (card_id, user_id, action_type, details, created_at) VALUES (" . (int)$itemRow['card_id'] . ", '" . $db->real_escape_string($userId) . "', '$actionType', '" . $db->real_escape_string($itemRow['title']) . "', '$now')", 'toggleChecklistItem:activity');
    
    echo json_encode(array('ok' => true, 'is_done' => $newState));
    exit;
}

if ($action === 'deleteChecklistItem') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $userId = $apiUserId ?? '';
    if ($itemId <= 0) {
        echo json_encode(array('error' => 'Item ID required'));
        exit;
    }
    $result = db_query_safe($db, "SELECT wci.checklist_id, cc.card_id, b.workspace_id FROM workspace_checklist_items wci JOIN workspace_checklists cc ON wci.checklist_id = cc.id JOIN workspace_cards c ON cc.card_id = c.id JOIN workspace_boards b ON c.board_id = b.id WHERE wci.id=$itemId", 'deleteChecklistItem:check');
    if (!$result || !($row = $result->fetch_assoc())) {
        echo json_encode(array('error' => 'Item not found'));
        exit;
    }
    if (!can_edit_workspace($db, (int)$row['workspace_id'], $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    db_query_safe($db, "DELETE FROM workspace_checklist_items WHERE id=$itemId", 'deleteChecklistItem:delete');
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'toggleCardLabel') {
    $cardId = (int)($_POST['card_id'] ?? 0);
    $labelId = (int)($_POST['label_id'] ?? 0);
    $userId = $apiUserId ?? '';
    
    if ($cardId <= 0 || $labelId <= 0) {
        echo json_encode(array('error' => 'Card ID and label ID required'));
        exit;
    }
    
    $wsResult = db_query_safe($db, "SELECT b.workspace_id FROM workspace_cards c JOIN workspace_boards b ON c.board_id = b.id WHERE c.id=$cardId", 'toggleCardLabel:workspace');
    $workspaceId = $wsResult && ($wsRow = $wsResult->fetch_assoc()) ? (int)$wsRow['workspace_id'] : 0;
    
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    $existing = db_query_safe($db, "SELECT id FROM workspace_card_labels WHERE card_id=$cardId AND label_id=$labelId", 'toggleCardLabel:check');
    if ($existing && $existing->num_rows > 0) {
        db_query_safe($db, "DELETE FROM workspace_card_labels WHERE card_id=$cardId AND label_id=$labelId", 'toggleCardLabel:remove');
    } else {
        db_query_safe($db, "INSERT INTO workspace_card_labels (card_id, label_id) VALUES ($cardId, $labelId)", 'toggleCardLabel:add');
        $labelResult = db_query_safe($db, "SELECT name FROM workspace_labels WHERE id=$labelId", 'toggleCardLabel:label');
        $labelName = $labelResult && ($lRow = $labelResult->fetch_assoc()) ? $lRow['name'] : '';
        if ($labelName) {
            db_query_safe($db, "INSERT INTO workspace_card_activity (card_id, user_id, action_type, details, created_at) VALUES ($cardId, '" . $db->real_escape_string($userId) . "', 'label_added', '" . $db->real_escape_string($labelName) . "', NOW())", 'toggleCardLabel:activity');
        }
    }
    
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'saveBoardLabel') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    $labelId = (int)($_POST['label_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '#6b4fd6');
    $userId = $apiUserId ?? '';
    
    if ($boardId <= 0) {
        echo json_encode(array('error' => 'Board ID required'));
        exit;
    }
    
    $wsResult = db_query_safe($db, "SELECT workspace_id FROM workspace_boards WHERE id=$boardId", 'saveBoardLabel:board');
    $workspaceId = $wsResult && ($wsRow = $wsResult->fetch_assoc()) ? (int)$wsRow['workspace_id'] : 0;
    
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    $safeName = $db->real_escape_string($name);
    $safeColor = $db->real_escape_string($color);
    
    if ($labelId > 0) {
        db_query_safe($db, "UPDATE workspace_labels SET name='$safeName', color='$safeColor' WHERE id=$labelId AND board_id=$boardId", 'saveBoardLabel:update');
        echo json_encode(array('ok' => true, 'id' => $labelId));
    } else {
        db_query_safe($db, "INSERT INTO workspace_labels (board_id, name, color, created_by) VALUES ($boardId, '$safeName', '$safeColor', '" . $db->real_escape_string($userId) . "')", 'saveBoardLabel:insert');
        echo json_encode(array('ok' => true, 'id' => (int)$db->insert_id));
    }
    exit;
}

if ($action === 'deleteBoardLabel') {
    $labelId = (int)($_POST['label_id'] ?? 0);
    $userId = $apiUserId ?? '';
    if ($labelId <= 0) {
        echo json_encode(array('error' => 'Label ID required'));
        exit;
    }
    $result = db_query_safe($db, "SELECT wl.board_id, b.workspace_id FROM workspace_labels wl JOIN workspace_boards b ON wl.board_id = b.id WHERE wl.id=$labelId", 'deleteBoardLabel:check');
    if (!$result || !($row = $result->fetch_assoc())) {
        echo json_encode(array('error' => 'Label not found'));
        exit;
    }
    if (!can_edit_workspace($db, (int)$row['workspace_id'], $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    db_query_safe($db, "DELETE FROM workspace_card_labels WHERE label_id=$labelId", 'deleteBoardLabel:card_labels');
    db_query_safe($db, "DELETE FROM workspace_labels WHERE id=$labelId", 'deleteBoardLabel:label');
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'saveCardDescription') {
    $cardId = (int)($_POST['card_id'] ?? 0);
    $description = $_POST['description'] ?? '';
    $userId = $apiUserId ?? '';
    $now = date('Y-m-d H:i:s');
    
    if ($cardId <= 0) {
        echo json_encode(array('error' => 'Card ID required'));
        exit;
    }
    
    $wsResult = db_query_safe($db, "SELECT b.workspace_id, c.description AS old_desc FROM workspace_cards c JOIN workspace_boards b ON c.board_id = b.id WHERE c.id=$cardId", 'saveCardDescription:workspace');
    $workspaceId = $wsResult && ($wsRow = $wsResult->fetch_assoc()) ? (int)$wsRow['workspace_id'] : 0;
    
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    $safeDesc = $db->real_escape_string($description);
    db_query_safe($db, "UPDATE workspace_cards SET description='$safeDesc', updated_at='$now' WHERE id=$cardId", 'saveCardDescription:update');
    
    if ($wsResult && ($wsRow = $wsResult->fetch_assoc()) && $wsRow['old_desc'] !== $description) {
        db_query_safe($db, "INSERT INTO workspace_card_activity (card_id, user_id, action_type, details, created_at) VALUES ($cardId, '" . $db->real_escape_string($userId) . "', 'description_changed', 'Description updated', '$now')", 'saveCardDescription:activity');
    }
    
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'logCardActivity') {
    $cardId = (int)($_POST['card_id'] ?? 0);
    $actionType = trim($_POST['action_type'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $userId = $apiUserId ?? '';
    $now = date('Y-m-d H:i:s');
    
    if ($cardId <= 0 || $actionType === '') {
        echo json_encode(array('error' => 'Card ID and action type required'));
        exit;
    }
    
    $safeAction = $db->real_escape_string($actionType);
    $safeDetails = $db->real_escape_string($details);
    db_query_safe($db, "INSERT INTO workspace_card_activity (card_id, user_id, action_type, details, created_at) VALUES ($cardId, '" . $db->real_escape_string($userId) . "', '$safeAction', '$safeDetails', '$now')", 'logCardActivity:insert');
    
    echo json_encode(array('ok' => true));
    exit;
}

if ($action === 'archiveCard') {
    $cardId = (int)($_POST['card_id'] ?? 0);
    $archive = isset($_POST['archive']) ? (int)$_POST['archive'] : 1;
    $userId = $apiUserId ?? '';
    
    if ($cardId <= 0) {
        echo json_encode(array('error' => 'Card ID required'));
        exit;
    }
    
    $wsResult = db_query_safe($db, "SELECT b.workspace_id FROM workspace_cards c JOIN workspace_boards b ON c.board_id = b.id WHERE c.id=$cardId", 'archiveCard:workspace');
    $workspaceId = $wsResult && ($wsRow = $wsResult->fetch_assoc()) ? (int)$wsRow['workspace_id'] : 0;
    
    if (!can_edit_workspace($db, $workspaceId, $userId)) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    db_query_safe($db, "UPDATE workspace_cards SET archived=$archive, updated_at=NOW() WHERE id=$cardId", 'archiveCard:update');
    
    echo json_encode(array('ok' => true));
    exit;
}

// ============================================
// CARD DETAIL ENDPOINTS
// ============================================
if ($action === 'getCardDetail') {
    $cardId = (int)($_GET['card_id'] ?? 0);
    if ($cardId <= 0) { echo json_encode(['error' => 'Card ID required']); exit; }
    $cardRes = db_query_safe($db, "SELECT c.*, b.workspace_id, b.id AS board_id FROM workspace_cards c JOIN workspace_boards b ON c.board_id = b.id WHERE c.id=$cardId", 'getCardDetail:card');
    if (!$cardRes || !($row = $cardRes->fetch_assoc())) { echo json_encode(['error' => 'Card not found']); exit; }
    $workspaceId = (int)($row['workspace_id'] ?? 0);
    $userId = $apiUserId ?? '';
    $card = [
        'id' => (int)$row['id'],
        'board_id' => (int)$row['board_id'],
        'list_id' => (int)$row['list_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'priority' => (int)$row['priority'],
        'due_date' => $row['due_date'],
        'cover_color' => $row['cover_color'],
        'position' => (int)$row['position'],
        'archived' => (int)($row['archived'] ?? 0),
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'workspace_id' => $workspaceId,
        'can_edit' => can_edit_workspace($db, $workspaceId, $userId),
        'can_manage' => can_manage_workspace($db, $workspaceId, $userId)
    ];
    // Members
    $card['members'] = [];
    $memRes = db_query_safe($db, "SELECT cm.user_id, u.full_name FROM workspace_card_members cm LEFT JOIN users u ON cm.user_id = u.user_id WHERE cm.card_id=$cardId", 'getCardDetail:members');
    if ($memRes) while ($m = $memRes->fetch_assoc()) $card['members'][] = ['user_id' => $m['user_id'], 'full_name' => $m['full_name'] ?? $m['user_id']];
    // Labels
    $card['labels'] = [];
    $lblRes = db_query_safe($db, "SELECT wl.id, wl.name, wl.color FROM workspace_card_labels wcl JOIN workspace_labels wl ON wcl.label_id = wl.id WHERE wcl.card_id=$cardId", 'getCardDetail:labels');
    if ($lblRes) while ($l = $lblRes->fetch_assoc()) $card['labels'][] = ['id' => (int)$l['id'], 'name' => $l['name'], 'color' => $l['color']];
    // Board labels
    $card['board_labels'] = [];
    $blRes = db_query_safe($db, "SELECT id, name, color FROM workspace_labels WHERE board_id=" . (int)$card['board_id'], 'getCardDetail:board_labels');
    if ($blRes) while ($bb = $blRes->fetch_assoc()) $card['board_labels'][] = ['id' => (int)$bb['id'], 'name' => $bb['name'], 'color' => $bb['color']];
    // Checklists + items
    $card['checklists'] = [];
    $clRes = db_query_safe($db, "SELECT id, title, position FROM workspace_checklists WHERE card_id=$cardId ORDER BY position, id", 'getCardDetail:checklists');
    if ($clRes) while ($cl = $clRes->fetch_assoc()) {
        $clid = (int)$cl['id'];
        $items = [];
        $itRes = db_query_safe($db, "SELECT id, title, is_done, position, completed_by, completed_at FROM workspace_checklist_items WHERE checklist_id=$clid ORDER BY position, id", 'getCardDetail:checklist_items');
        if ($itRes) while ($it = $itRes->fetch_assoc()) {
            $items[] = ['id' => (int)$it['id'], 'title' => $it['title'], 'is_done' => (int)$it['is_done'], 'position' => (int)$it['position'], 'completed_by' => $it['completed_by'], 'completed_at' => $it['completed_at']];
        }
        $card['checklists'][] = ['id' => $clid, 'title' => $cl['title'], 'position' => (int)$cl['position'], 'items' => $items];
    }
    // Comments
    $card['comments'] = [];
    $ccom = db_query_safe($db, "SELECT wcc.id, wcc.user_id, u.full_name, wcc.comment, wcc.created_at FROM workspace_card_comments wcc LEFT JOIN users u ON wcc.user_id = u.user_id WHERE wcc.card_id=$cardId ORDER BY wcc.created_at DESC", 'getCardDetail:comments');
    if ($ccom) while ($cc = $ccom->fetch_assoc()) $card['comments'][] = ['id' => (int)$cc['id'], 'user_id' => $cc['user_id'], 'full_name' => $cc['full_name'] ?? $cc['user_id'], 'comment' => $cc['comment'], 'created_at' => $cc['created_at']];
    // Activity
    $card['activity'] = [];
    $act = db_query_safe($db, "SELECT a.id, a.user_id, a.action_type, a.details, a.created_at, u.full_name FROM workspace_card_activity a LEFT JOIN users u ON a.user_id = u.user_id WHERE a.card_id=$cardId ORDER BY a.created_at DESC LIMIT 50", 'getCardDetail:activity');
    if ($act) while ($ar = $act->fetch_assoc()) $card['activity'][] = ['id' => (int)$ar['id'], 'user_id' => $ar['user_id'], 'full_name' => $ar['full_name'] ?? $ar['user_id'], 'action_type' => $ar['action_type'], 'details' => $ar['details'], 'created_at' => $ar['created_at']];
    // Lists
    $card['lists'] = [];
    $ll = db_query_safe($db, "SELECT id, name, color, position FROM workspace_lists WHERE board_id=" . (int)$card['board_id'] . " ORDER BY position", 'getCardDetail:lists');
    if ($ll) while ($lr = $ll->fetch_assoc()) $card['lists'][] = ['id' => (int)$lr['id'], 'name' => $lr['name'], 'color' => $lr['color'], 'position' => (int)$lr['position']];
    echo json_encode(['data' => $card]); exit;
}

