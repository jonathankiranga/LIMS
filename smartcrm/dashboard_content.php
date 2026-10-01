<?php
$PageSecurity = 0;
$PathPrefix = './';
$AllowAnyone = false;
$inIframe = true;
error_reporting(E_ALL);
include($PathPrefix . 'includes/session.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');
include($PathPrefix . 'includes/crm_scope.php');

$Title = _('Dashboard');
include($PathPrefix . 'includes/header.inc');

$CurrencySymbol = isset($CurrencySymbol) ? $CurrencySymbol : 'KSh ';
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="kanban.css">
<style>
:root {
    --kanban-bg: #f5f2f8;
    --kanban-accent: #6b4fd6;
}
* { font-family: 'Poppins', sans-serif; }
body, html { background: var(--kanban-bg); }
#smartcrm-shell { padding: 12px 16px 24px; }

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    gap: 10px;
    background: white;
    padding: 14px 18px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.page-header h2 { margin: 0; font-weight: 700; color: #2f2b3a; font-size: 20px; }
.page-header p { margin: 2px 0 0; color: #6f6c7a; font-size: 12px; }

.dashboard-stats { display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.stat-card {
    padding: 16px 20px;
    border-radius: 10px;
    color: white;
    min-width: 180px;
    flex: 1;
    max-width: 260px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 120ms ease;
}
.stat-card:hover { transform: scale(1.02); }
.stat-card h4 { margin: 0 0 4px; font-size: 11px; opacity: 0.9; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-card .value { font-size: 28px; font-weight: 700; margin-bottom: 2px; }
.stat-card .sub { font-size: 11px; opacity: 0.85; }

.stat-card:nth-child(1) { background: linear-gradient(135deg, #ff6b6b, #feca57); }
.stat-card:nth-child(2) { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-card:nth-child(3) { background: linear-gradient(135deg, #a8edea, #fed6e3); color: #333; }

#kanban { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; }

.actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.actions select, .actions input { padding: 8px 12px; border: 1px solid #e1dfe8; border-radius: 8px; font-size: 13px; }
.actions select:focus, .actions input:focus { border-color: #6b4fd6; outline: none; }
.actions button { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; }
.btn-success { background: #27ae60; color: white; }
.btn-primary { background: #6b4fd6; color: white; }

.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #6f6c7a; margin-bottom: 4px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #e1dfe8; border-radius: 8px; font-size: 13px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #6b4fd6; outline: none; }
.form-group textarea { min-height: 70px; resize: vertical; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
.modal-actions button { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; }
.modal-actions button[type="button"] { background: #e1dfe8; color: #6f6c7a; }
.modal-actions button[type="submit"] { background: #6b4fd6; color: white; }

#taskModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(25,20,38,0.65); z-index:1000; backdrop-filter: blur(2px); }
.modal-backdrop { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; }
.modal-backdrop.show { display: flex; }
.modal-sheet { background: #fff; width: min(480px, 90vw); border-radius: 12px; box-shadow: 0 18px 36px rgba(0,0,0,0.16); padding: 20px; }

#kanban > .kanban-col.collapsed { opacity: 0.6; }
#kanban > .kanban-col.collapsed header { background: #f0e8ff; }
.toggle-icon { font-size: 10px; cursor: pointer; }
</style>

<?php
$TaskstatusArray = array(
    "0" => 'Not yet begun',
    "1" => 'In progress',
    "2" => 'Almost Done',
    "3" => 'Taking Longer than expected',
    "4" => 'Complete'
);

$PriorityArray = array(
    "0" => 'High',
    "1" => 'Moderate',
    "2" => 'Low'
);

$CurrentUser = crm_user_id();
$CurrentScope = crm_scope_raw();

$tasks = array();
$SQL = "SELECT pkey, Taskname, Status, Priority, TaskOwner, datedue, taskdetails, datecreated
        FROM Tasks" . crm_scope_where(array(crm_scope_owner_clause('TaskOwner'))) . "
        ORDER BY datecreated DESC
        LIMIT 200";
$Result = DB_query($SQL, $db);
while ($row = DB_fetch_array($Result)) {
    $tasks[] = array(
        'id'        => (int)$row['pkey'],
        'title'     => $row['Taskname'],
        'status'    => (string)$row['Status'],
        'priority'  => (string)$row['Priority'],
        'owner'     => $row['TaskOwner'],
        'due'       => $row['datedue'],
        'details'   => $row['taskdetails'],
        'created'   => $row['datecreated']
    );
}

$myOpenTasks = count(array_filter($tasks, function ($t) use ($CurrentUser) {
    if ($t['status'] === '4') {
        return false;
    }
    return $t['owner'] === $CurrentUser || $t['owner'] === null || $t['owner'] === '';
}));

$leadStats = array('new' => 0, 'contacted' => 0, 'qualified' => 0, 'converted' => 0);
$totalLeads = 0;
$SQL = "SELECT status, COUNT(*) as cnt FROM crm_leads"
    . crm_scope_where(array(crm_scope_owner_clause('assigned_to')))
    . " GROUP BY status";
$Result = DB_query($SQL, $db);
while ($row = DB_fetch_array($Result)) {
    $cnt = (int)$row['cnt'];
    // total must include every status, including proposal/negotiation/lost,
    // otherwise the card undercounts.
    $totalLeads += $cnt;
    $statusKey = strtolower(trim((string)$row['status']));
    if (isset($leadStats[$statusKey])) {
        $leadStats[$statusKey] = $cnt;
    }
}

$oppStats = array('count' => 0, 'value' => 0);
$SQL = "SELECT COUNT(*) as cnt, COALESCE(SUM(expected_value), 0) as val FROM crm_opportunities"
    . crm_scope_where(array(crm_scope_owner_clause('assigned_to')));
$Result = DB_query($SQL, $db);
if ($row = DB_fetch_array($Result)) {
    $oppStats = array('count' => (int)$row['cnt'], 'value' => (float)$row['val']);
}

$CurrencySymbol = isset($CurrencySymbol) ? $CurrencySymbol : 'KSh ';
?>

<div id="smartcrm-shell">
    <div class="page-header">
        <div>
            <h2>Tasks</h2>
            <p>Drag cards between stages to update task status.</p>
        </div>
        <div class="actions">
            <label>Scope:
                <select id="scopeFilter" onchange="applyScope()">
                    <option value="all"<?php echo $CurrentScope === 'all' ? ' selected' : ''; ?>>All tasks</option>
                    <option value="my"<?php echo $CurrentScope === 'my' ? ' selected' : ''; ?>>My tasks</option>
                    <option value="stalled"<?php echo $CurrentScope === 'stalled' ? ' selected' : ''; ?>>Stalled (>= days)</option>
                    <option value="my-stalled"<?php echo $CurrentScope === 'my-stalled' ? ' selected' : ''; ?>>My stalled</option>
                    <option value="due"<?php echo $CurrentScope === 'due' ? ' selected' : ''; ?>>Due soon (<= days)</option>
                    <option value="my-due"<?php echo $CurrentScope === 'my-due' ? ' selected' : ''; ?>>My due soon</option>
                </select>
            </label>
            <label>Stage days: <input type="number" id="stageDays" value="<?php echo isset($_GET['stageDays']) ? (int)$_GET['stageDays'] : 3; ?>" min="1" style="width:60px;"></label>
            <label>Due days: <input type="number" id="dueDays" value="<?php echo isset($_GET['dueDays']) ? (int)$_GET['dueDays'] : 7; ?>" min="1" style="width:60px;"></label>
            <button class="btn-success" onclick="openTaskModal()">+ Add Task</button>
            <button class="btn-primary" id="refreshTasksBtn" onclick="refreshDashboard()">Refresh</button>
        </div>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <h4>Total Leads</h4>
            <div class="value" id="statTotalLeads"><?php echo $totalLeads; ?></div>
            <div class="sub">New: <span id="statLeadsNew"><?php echo $leadStats['new']; ?></span> | Converted: <span id="statLeadsConverted"><?php echo $leadStats['converted']; ?></span></div>
        </div>
        <div class="stat-card">
            <h4>Opportunities</h4>
            <div class="value" id="statOppCount"><?php echo $oppStats['count']; ?></div>
            <div class="sub">Value: <span id="statOppValue"><?php echo $CurrencySymbol . number_format($oppStats['value']); ?></span></div>
        </div>
        <div class="stat-card">
            <h4>My Tasks</h4>
            <div class="value" id="myTaskCount"><?php echo $myOpenTasks; ?></div>
            <div class="sub">Active (excludes completed)</div>
        </div>
    </div>
    
    <div id="kanban" data-update-url="api.php"></div>
</div>

<div id="taskModal" class="modal-backdrop">
    <div class="modal-sheet">
        <h3 id="taskModalTitle">New Task</h3>
        <form id="taskForm">
            <input type="hidden" id="taskId" value="0">
            <div class="form-group">
                <label>Task Name:</label>
                <input type="text" id="taskName" required>
            </div>
            <div class="form-group">
                <label>Status:</label>
                <select id="taskStatus">
                    <option value="0">Not yet begun</option>
                    <option value="1">In progress</option>
                    <option value="2">Almost Done</option>
                    <option value="3">Taking Longer than expected</option>
                    <option value="4">Complete</option>
                </select>
            </div>
            <div class="form-group">
                <label>Priority:</label>
                <select id="taskPriority">
                    <option value="0">High</option>
                    <option value="1">Moderate</option>
                    <option value="2">Low</option>
                </select>
            </div>
            <div class="form-group">
                <label>Due Date:</label>
                <input type="date" id="taskDueDate">
            </div>
            <div class="form-group">
                <label>Details:</label>
                <textarea id="taskDetails" rows="3"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeTaskModal()">Cancel</button>
                <button type="submit">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
var SMARTCRM_STATUSES = <?php echo json_encode($TaskstatusArray); ?>;
var SMARTCRM_PRIORITIES = <?php echo json_encode($PriorityArray); ?>;
var SMARTCRM_TASKS = <?php echo json_encode($tasks); ?>;
var SMARTCRM_FETCH_URL = 'api.php';
var LEAD_STATS = <?php echo json_encode($leadStats); ?>;
var TOTAL_LEADS = <?php echo $totalLeads; ?>;
var OPP_STATS = <?php echo json_encode($oppStats); ?>;
var CRM_CURRENCY_SYMBOL = '<?php echo $CurrencySymbol; ?>';
<?php echo crm_scope_js(); ?>

var allTasks = SMARTCRM_TASKS.slice();
var collapsedStages = [];

function loadTasks() {
    var scopeFilter = document.getElementById('scopeFilter').value;
    var stageDays = parseInt(document.getElementById('stageDays').value) || 3;
    var dueDays = parseInt(document.getElementById('dueDays').value) || 7;
    var userId = '<?php echo $CurrentUser; ?>';
    
    var now = new Date();
    
    allTasks = SMARTCRM_TASKS.filter(function(t) {
        var isMy = t.owner === userId;
        
        switch(scopeFilter) {
            case 'all': return true;
            case 'my': return isMy;
            case 'stalled':
            case 'my-stalled':
                if (t.status != '4') {
                    var created = new Date(t.created);
                    var daysInStage = Math.floor((now - created) / (1000 * 60 * 60 * 24));
                    return scopeFilter === 'stalled' || (scopeFilter === 'my-stalled' && isMy) ? daysInStage >= stageDays : false;
                }
                return false;
            case 'due':
            case 'my-due':
                if (t.due) {
                    var due = new Date(t.due);
                    var daysUntilDue = Math.floor((due - now) / (1000 * 60 * 60 * 24));
                    return daysUntilDue <= dueDays && (scopeFilter === 'due' || (scopeFilter === 'my-due' && isMy));
                }
                return false;
            default: return isMy;
        }
    });
    
    var myOpen = SMARTCRM_TASKS.filter(function(t) {
        return t.status !== '4' && (t.owner === userId || !t.owner);
    }).length;
    var myTaskCount = document.getElementById('myTaskCount');
    if (myTaskCount) {
        myTaskCount.textContent = myOpen;
    }
    
    renderKanban();
}

function renderKanban() {
    var container = document.getElementById('kanban');
    var columns = ['0','1','2','3','4'];
    var columnNames = SMARTCRM_STATUSES;
    
    container.innerHTML = columns.map(function(status) {
        var tasksInColumn = allTasks.filter(function(t) { return t.status === status; });
        var isCollapsed = collapsedStages.indexOf(status) !== -1;
        var toggleIcon = status === '4' ? '<span class="toggle-icon" onclick="toggleStage(' + status + ')" style="cursor:pointer;margin-left:8px;">' + (isCollapsed ? '&#9654;' : '&#9660;') + '</span>' : '';
        return '<div class="kanban-col' + (isCollapsed ? ' collapsed' : '') + '" data-status="' + status + '"><header>' + columnNames[status] + ' <span class="pill">' + tasksInColumn.length + '</span>' + toggleIcon + '</header>' +
            '<div class="kanban-list"' + (isCollapsed ? ' style="display:none;"' : '') + '>' +
            tasksInColumn.map(function(task) {
                var priorityClass = 'priority-' + task.priority;
                return '<div class="kanban-card" draggable="true" data-id="' + task.id + '" data-status="' + task.status + '" style="cursor:grab;">' +
                    '<h4 onclick="event.stopPropagation(); openTaskModal(' + task.id + ')" style="cursor:pointer;">' + task.title + '</h4>' +
                    '<div class="meta-row" style="cursor:pointer;">' +
                    '<span class="tag ' + priorityClass + '" onclick="event.stopPropagation(); openTaskModal(' + task.id + ')">' + (task.priority === '0' ? 'High' : task.priority === '1' ? 'Moderate' : 'Low') + '</span>' +
                    '<span onclick="event.stopPropagation(); openTaskModal(' + task.id + ')">' + (task.due ? 'Due: ' + task.due : 'No due date') + '</span>' +
                    '</div></div>';
            }).join('') +
        '</div></div>';
    });
    
    initDragDrop();
}

function toggleStage(status) {
    var idx = collapsedStages.indexOf(status);
    if (idx === -1) {
        collapsedStages.push(status);
    } else {
        collapsedStages.splice(idx, 1);
    }
    renderKanban();
}

function initDragDrop() {
    var cards = document.querySelectorAll('.kanban-card');
    cards.forEach(function(card) {
        card.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', card.dataset.id);
        });
    });
    
    var columns = document.querySelectorAll('#kanban > .kanban-col');
    columns.forEach(function(col) {
        col.addEventListener('dragover', function(e) { e.preventDefault(); });
        col.addEventListener('drop', function(e) {
            e.preventDefault();
            var taskId = e.dataTransfer.getData('text/plain');
            var newStatus = col.dataset.status;
            if (taskId && newStatus !== undefined) {
                updateTaskStatus(taskId, newStatus);
            }
        });
    });
}

function updateTaskStatus(taskId, status) {
    var fd = new FormData();
    fd.append('action', 'updateTaskStatus');
    fd.append('id', taskId);
    fd.append('status', status);
    
    fetch('api.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            refreshTasksFromAPI();
        } else {
            toastError(res.error || 'Error updating task');
        }
    });
}

function refreshTasksFromAPI() {
    var btn = document.getElementById('refreshTasksBtn');
    var scope = document.getElementById('scopeFilter').value;
    var stageDays = parseInt(document.getElementById('stageDays').value) || 3;
    var dueDays = parseInt(document.getElementById('dueDays').value) || 7;
    if (btn) { btn.disabled = true; btn.textContent = 'Refreshing...'; }
    return fetch('api.php?action=listTasks&scope=' + encodeURIComponent(scope) +
          '&stageDays=' + stageDays + '&dueDays=' + dueDays)
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.data) {
            SMARTCRM_TASKS = res.data.map(function(t) {
                return {
                    id: t.id,
                    title: t.title,
                    status: String(t.status),
                    priority: String(t.priority),
                    owner: t.owner || '',
                    due: t.due || '',
                    details: t.details || '',
                    created: t.created || ''
                };
            });
            loadTasks();
        } else {
            toastError(res.error || 'Could not refresh tasks');
        }
    })
    .catch(function() { toastError('Could not refresh tasks'); })
    .then(function() {
        if (btn) { btn.disabled = false; btn.textContent = 'Refresh'; }
    });
}

function refreshDashboardStats() {
    var scope = document.getElementById('scopeFilter').value;
    return fetch('api.php?action=getDashboardStats&scope=' + encodeURIComponent(scope))
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (!res.data) { return; }
        var d = res.data;
        var set = function(id, val) {
            var el = document.getElementById(id);
            if (el) { el.textContent = val; }
        };
        set('statTotalLeads', d.totalLeads);
        set('statLeadsNew', d.new);
        set('statLeadsConverted', d.converted);
        set('statOppCount', d.oppCount);
        set('statOppValue', <?php echo json_encode(isset($CurrencySymbol) ? $CurrencySymbol : 'KSh '); ?> + Number(d.oppValue).toLocaleString());
    })
    .catch(function() {
        // Stats are supplementary: never let a failure here break the kanban.
    });
}

function refreshDashboard() {
    refreshDashboardStats();
    return refreshTasksFromAPI();
}

function openTaskModal(id) {
    id = id || 0;
    document.getElementById('taskModal').classList.add('show');
    document.getElementById('taskModalTitle').textContent = id ? 'Edit Task' : 'New Task';
    document.getElementById('taskId').value = id;
    document.getElementById('taskForm').reset();
    
    if (id > 0) {
        var task = SMARTCRM_TASKS.find(function(t) { return Number(t.id) === Number(id); });
        if (task) {
            document.getElementById('taskName').value = task.title;
            document.getElementById('taskStatus').value = task.status;
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskDueDate').value = task.due || '';
            document.getElementById('taskDetails').value = task.details || '';
        }
    }
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.remove('show');
}

document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData();
    fd.append('action', 'saveTask');
    fd.append('id', document.getElementById('taskId').value);
    fd.append('title', document.getElementById('taskName').value);
    fd.append('status', document.getElementById('taskStatus').value);
    fd.append('priority', document.getElementById('taskPriority').value);
    fd.append('duedate', document.getElementById('taskDueDate').value);
    fd.append('details', document.getElementById('taskDetails').value);
    
    fetch('api.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            closeTaskModal();
            toastSuccess('Task saved successfully');
            window.location.reload();
        } else {
            toastError(res.error || 'Error saving task');
        }
    });
});

document.getElementById('stageDays').addEventListener('change', loadTasks);
document.getElementById('dueDays').addEventListener('change', loadTasks);

function applyScope() {
    setCrmScope(document.getElementById('scopeFilter').value, false);
    refreshDashboard();
}

loadTasks();
</script>

<?php include($PathPrefix . 'includes/footer.inc'); ?>
