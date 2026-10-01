<?php
// Automation Rules Management
$PageSecurity = 0;
$PathPrefix = './';
$inIframe = true;
$extraCrumb = 'Automation';
error_reporting(E_ALL);
include($PathPrefix . 'includes/session.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');

$Title = _('Automation');
include($PathPrefix . 'includes/header.inc');

unset($extraCrumb);
?>
<link rel="stylesheet" href="kanban.css">
<style>
.crm-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.crm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
.crm-header h2 { margin: 0; color: #2c3e50; }
.rules-list { display: flex; flex-direction: column; gap: 15px; }
.rule-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.rule-card .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.rule-card .name { font-weight: 600; color: #2c3e50; font-size: 16px; }
.rule-card .trigger { color: #6c757d; font-size: 13px; margin-bottom: 10px; }
.rule-card .actions-list { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 13px; }
.rule-card .toggle-btn { padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; }
.rule-card .toggle-btn.active { background: #27ae60; color: white; }
.rule-card .toggle-btn.inactive { background: #95a5a6; color: white; }
.rule-card .edit-btn { background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
.rule-card .delete-btn { background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
.rule-card .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; margin-right: 5px; }
.rule-card .badge-active { background: #d4edda; color: #155724; }
.rule-card .badge-inactive { background: #f8d7da; color: #721c24; }
.btn-success { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
.btn-secondary { background: #95a5a6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
.btn-outline-primary { background: white; border: 1px solid #3498db; color: #3498db; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
.btn-outline-primary:hover { background: #3498db; color: white; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
.modal.show { display: flex; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.close-modal { background: none; border: none; font-size: 24px; cursor: pointer; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #2c3e50; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.form-group textarea { min-height: 80px; }
.predefined-rules { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
.predefined-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s; }
.predefined-card:hover { transform: translateY(-2px); }
.predefined-card h4 { margin: 0 0 10px 0; color: #2c3e50; font-size: 14px; }
.predefined-card p { margin: 0; color: #6c757d; font-size: 12px; }
.action-item { background: white; padding: 12px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #dee2e6; }
.action-item select, .action-item input, .action-item textarea { width: 100%; padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 4px; }
.action-item label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 500; color: #2c3e50; }
.action-fields .field-group { margin-bottom: 10px; }
</style>

<div class="crm-container">
    <div class="crm-header">
        <h2><?php echo _('Automation Rules'); ?></h2>
        <button class="btn-success" onclick="openRuleModal()">+ New Rule</button>
    </div>
    
    <h3>Quick Setup</h3>
    <div class="predefined-rules">
        <div class="predefined-card" onclick="createQuickRule('lead_created', 'assign_lead', 'Auto-assign new leads to me')">
            <h4>Auto-assign New Leads</h4>
            <p>Automatically assign new leads to your user account</p>
        </div>
        <div class="predefined-card" onclick="createQuickRule('lead_status_changed', 'send_email', 'Notify when lead status changes')">
            <h4>Status Change Notification</h4>
            <p>Send email notification when lead status changes</p>
        </div>
        <div class="predefined-card" onclick="createQuickRule('followup_overdue', 'create_task', 'Create task for overdue follow-up')">
            <h4>Overdue Follow-up Task</h4>
            <p>Create task when follow-up date is missed</p>
        </div>
        <div class="predefined-card" onclick="createQuickRule('opportunity_won', 'send_email', 'Celebrate when deal is won')">
            <h4>Deal Won Celebration</h4>
            <p>Send congratulatory email when opportunity is closed won</p>
        </div>
    </div>
    
    <h3>Custom Rules</h3>
    <div class="rules-list" id="rulesList">
        <div style="text-align:center; padding: 40px;">Loading rules...</div>
    </div>
</div>

<!-- Rule Modal -->
<div class="modal" id="ruleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="ruleModalTitle">New Automation Rule</h3>
            <button class="close-modal" onclick="closeRuleModal()">&times;</button>
        </div>
        <form id="ruleForm">
            <input type="hidden" id="ruleId" name="id" value="0">
            <div class="form-group">
                <label>Rule Name *</label>
                <input type="text" id="rule_name" name="rule_name" required placeholder="e.g., Auto-assign new leads">
            </div>
            <div class="form-group">
                <label>Trigger *</label>
                <select id="trigger_type" name="trigger_type" onchange="onTriggerChange()">
                    <option value="">Select Trigger</option>
                    <option value="lead_created">Lead Created</option>
                    <option value="lead_status_changed">Lead Status Changed</option>
                    <option value="opportunity_won">Opportunity Won</option>
                    <option value="opportunity_lost">Opportunity Lost</option>
                    <option value="opportunity_stage_changed">Opportunity Stage Changed</option>
                    <option value="followup_overdue">Follow-up Overdue</option>
                </select>
            </div>
            <div class="form-group">
                <label>Conditions (optional)</label>
                <div style="background:#f8f9fa;padding:15px;border-radius:8px;">
                    <div style="margin-bottom:10px;">
                        <label style="display:inline;margin-right:8px;">Match</label>
                        <select id="condition_match" onchange="syncConditionsField()">
                            <option value="all">All conditions</option>
                            <option value="any">Any condition</option>
                        </select>
                    </div>
                    <div id="conditionList"></div>
                    <button type="button" class="btn-outline-primary" onclick="addCondition()">+ Add Condition</button>
                </div>
                <input type="hidden" id="trigger_conditions" name="trigger_conditions">
            </div>
            <div class="form-group">
                <label>Priority</label>
                <input type="number" id="priority" name="priority" value="0" min="0" max="100">
            </div>
            <div class="form-group">
                <label>Actions</label>
                <div id="actionsBuilder" style="background:#f8f9fa;padding:15px;border-radius:8px;">
                    <div id="actionList"></div>
                    <button type="button" class="btn-outline-primary" onclick="addAction()">+ Add Action</button>
                </div>
                <input type="hidden" id="actions" name="actions">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="is_active" name="is_active" checked> Active
                </label>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn-secondary" onclick="closeRuleModal()">Cancel</button>
                <button type="submit" class="btn-success">Save Rule</button>
            </div>
        </form>
    </div>
</div>

<!-- Action Template -->
<div id="actionTemplate" style="display:none;">
    <div class="action-item">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <select class="action-type" onchange="actionTypeChanged(this)">
                <option value="">Select Action...</option>
                <option value="update_field">Update Field</option>
                <option value="create_task">Create Task</option>
                <option value="send_email">Send Email</option>
                <option value="schedule_meeting">Schedule Meeting (ICS)</option>
            </select>
            <button type="button" onclick="window.removeAction(this)" style="background:none;border:none;color:#e74c3c;cursor:pointer;font-size:18px;">&times;</button>
        </div>
        <div class="action-fields"></div>
    </div>
</div>

<!-- Condition Template -->
<div id="conditionTemplate" style="display:none;">
    <div class="action-item condition-item">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:8px;">
            <select class="condition-field" onchange="conditionFieldChanged(this)" style="flex:1;"></select>
            <select class="condition-op" onchange="syncConditionsField()" style="flex:1;"></select>
            <input type="text" class="condition-value" oninput="syncConditionsField()" style="flex:1;" placeholder="Value">
            <button type="button" onclick="window.removeCondition(this)" style="background:none;border:none;color:#e74c3c;cursor:pointer;font-size:18px;">&times;</button>
        </div>
    </div>
</div>

<datalist id="leadStatusList">
    <option value="new"></option>
    <option value="contacted"></option>
    <option value="qualified"></option>
    <option value="proposal"></option>
    <option value="negotiation"></option>
    <option value="converted"></option>
    <option value="lost"></option>
</datalist>

<script>
var triggers = [];
window.openRuleModal = function(id) {
    id = id || 0;
    document.getElementById('ruleModal').classList.add('show');
    document.getElementById('ruleModalTitle').textContent = id ? 'Edit Rule' : 'New Automation Rule';
    document.getElementById('ruleForm').reset();
    document.getElementById('ruleId').value = id;
    document.getElementById('actionList').innerHTML = '';
    addAction();
    document.getElementById('conditionList').innerHTML = '';
    syncConditionsField();
};

function closeRuleModal() {
    document.getElementById('ruleModal').classList.remove('show');
}

window.editRule = function(id) {
    fetch('api.php?action=getAutomationRule&id=' + encodeURIComponent(id))
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (!res.data) {
            SmartDialog.error(res.error || 'Could not load rule');
            return;
        }
        var rule = res.data;
        window.openRuleModal(rule.id);
        document.getElementById('rule_name').value = rule.rule_name;
        document.getElementById('trigger_type').value = rule.trigger_type;
        document.getElementById('priority').value = rule.priority;
        document.getElementById('is_active').checked = Number(rule.is_active) === 1;
        loadConditionsIntoBuilder(rule.trigger_conditions || {});
        loadActionsIntoBuilder(rule.actions || []);
    })
    .catch(function() { SmartDialog.error('Could not load rule'); });
};

window.createQuickRule = function(trigger, actionType, name) {
    var actions = {
        'assign_lead': [{type:'update_field', field:'assigned_to', value:'<?php echo $_SESSION["UserID"] ?? ""; ?>'}],
        'send_email': [{type:'send_email', subject:'Notification', body:'Automated notification'}],
        'create_task': [{type:'create_task', title:'Follow-up required', due_days:1}]
    };
    
    var fd = new FormData();
    fd.append('action', 'saveAutomationRule');
    fd.append('rule_name', name);
    fd.append('trigger_type', trigger);
    fd.append('actions', JSON.stringify(actions[actionType] || []));
    fd.append('is_active', 1);
    fd.append('priority', 10);
    
    fetch('api.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
            if (res.ok) {
                loadRules();
            } else {
                SmartDialog.error(res.error || 'Error creating rule');
            }
    });
}

window.toggleRule = function(id, active) {
    var fd = new FormData();
    fd.append('action', 'toggleAutomationRule');
    fd.append('id', id);
    fd.append('active', active ? 1 : 0);
    fetch('api.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            loadRules();
            SmartDialog.success(active ? 'Rule enabled' : 'Rule disabled');
        } else {
            SmartDialog.error(res.error || 'Error updating rule');
        }
    });
};

window.deleteRule = function(id) {
    if (!confirm('Delete this rule?')) return;
    var fd = new FormData();
    fd.append('action', 'deleteAutomationRule');
    fd.append('id', id);
    fetch('api.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            loadRules();
            SmartDialog.success('Rule deleted');
        } else {
            SmartDialog.error(res.error || 'Error deleting rule');
        }
    });
};

var TRIGGER_LABELS = {
    lead_created: 'Lead Created',
    lead_status_changed: 'Lead Status Changed',
    opportunity_won: 'Opportunity Won',
    opportunity_lost: 'Opportunity Lost',
    opportunity_stage_changed: 'Opportunity Stage Changed',
    followup_overdue: 'Follow-up Overdue'
};

// Mirrors crm_condition_fields() in api.php. A rule can only use these fields.
var CONDITION_FIELDS = {
    lead: [
        {value: 'status', label: 'Status', type: 'status'},
        {value: 'source', label: 'Source', type: 'text'},
        {value: 'assigned_to', label: 'Assigned To', type: 'text'},
        {value: 'lead_score', label: 'Lead Score', type: 'number'},
        {value: 'industry', label: 'Industry', type: 'text'},
        {value: 'country', label: 'Country', type: 'text'},
        {value: 'city', label: 'City', type: 'text'},
        {value: 'company_name', label: 'Company', type: 'text'},
        {value: 'contact_name', label: 'Contact', type: 'text'},
        {value: 'email', label: 'Email', type: 'text'},
        {value: 'phone', label: 'Phone', type: 'text'},
        {value: 'next_followup', label: 'Next Follow-up', type: 'date'}
    ],
    opportunity: [
        {value: 'pipeline_stage_id', label: 'Pipeline Stage ID', type: 'number'},
        {value: 'probability', label: 'Probability', type: 'number'},
        {value: 'expected_value', label: 'Expected Value', type: 'number'},
        {value: 'lead_id', label: 'Lead ID', type: 'number'},
        {value: 'is_recurring', label: 'Is Recurring (0/1)', type: 'number'},
        {value: 'assigned_to', label: 'Assigned To', type: 'text'},
        {value: 'opportunity_name', label: 'Opportunity', type: 'text'},
        {value: 'competitor', label: 'Competitor', type: 'text'},
        {value: 'recurring_cycle', label: 'Recurring Cycle', type: 'text'},
        {value: 'expected_close_date', label: 'Expected Close', type: 'date'}
    ]
};

var CONDITION_OPS = {
    text: [
        ['equals', 'is'], ['not_equals', 'is not'],
        ['contains', 'contains'], ['not_contains', 'does not contain'],
        ['in', 'is one of (comma separated)'], ['not_in', 'is none of (comma separated)'],
        ['is_empty', 'is empty'], ['not_empty', 'is not empty']
    ],
    status: [
        ['equals', 'is'], ['not_equals', 'is not'],
        ['in', 'is one of'], ['not_in', 'is none of'],
        ['is_empty', 'is empty'], ['not_empty', 'is not empty']
    ],
    number: [
        ['equals', '= '], ['not_equals', '<> '],
        ['gt', '>'], ['gte', '>= '], ['lt', '<'], ['lte', '<= '],
        ['is_empty', 'is empty'], ['not_empty', 'is not empty']
    ],
    date: [
        ['equals', 'is'], ['lt', 'is before'], ['gte', 'is on or after'],
        ['gt', 'is after'], ['is_empty', 'is empty'], ['not_empty', 'is not empty']
    ]
};

var VALUE_LESS_OPS = ['is_empty', 'not_empty'];

var ACTION_LABELS = {
    update_field: 'Update Field',
    create_task: 'Create Task',
    send_email: 'Send Email',
    schedule_meeting: 'Schedule Meeting'
};

var UPDATE_FIELDS = {
    lead: [
        {value: 'assigned_to', label: 'Assigned To'},
        {value: 'status', label: 'Status'},
        {value: 'source', label: 'Source'},
        {value: 'lead_score', label: 'Lead Score'},
        {value: 'notes', label: 'Notes'}
    ],
    opportunity: [
        {value: 'assigned_to', label: 'Assigned To'},
        {value: 'pipeline_stage_id', label: 'Pipeline Stage ID'},
        {value: 'probability', label: 'Probability'},
        {value: 'expected_value', label: 'Expected Value'},
        {value: 'next_step', label: 'Next Step'},
        {value: 'description', label: 'Description'}
    ]
};

var ACTION_FIELDS = {
    update_field: [
        {key: 'field', label: 'Field', type: 'select'},
        {key: 'value', label: 'Value', type: 'text'}
    ],
    create_task: [
        {key: 'title', label: 'Task Title', type: 'text', value: 'Follow-up required'},
        {key: 'details', label: 'Details', type: 'text'},
        {key: 'due_days', label: 'Due In (days)', type: 'number', value: 1}
    ],
    send_email: [
        {key: 'to', label: 'To (blank = record email)', type: 'text'},
        {key: 'subject', label: 'Subject', type: 'text', value: 'Notification'},
        {key: 'body', label: 'Body', type: 'textarea', value: 'Automated notification'}
    ],
    schedule_meeting: [
        {key: 'title', label: 'Title', type: 'text', value: 'Meeting'},
        {key: 'description', label: 'Description', type: 'text'},
        {key: 'location', label: 'Location', type: 'text'},
        {key: 'start_date', label: 'Date (YYYY-MM-DD)', type: 'date'},
        {key: 'start_time', label: 'Time (HH:MM)', type: 'text', value: '09:00'},
        {key: 'duration', label: 'Duration (min)', type: 'number', value: 60}
    ]
};

function escapeHtml(s) {
    return String(s === null || s === undefined ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function entityKindForTrigger(trigger) {
    return String(trigger || '').indexOf('opportunity') === 0 ? 'opportunity' : 'lead';
}

function buildActionFields(item, type, values) {
    var box = item.querySelector('.action-fields');
    var defs = ACTION_FIELDS[type] || [];
    var kind = entityKindForTrigger(document.getElementById('trigger_type').value);
    box.innerHTML = '';
    defs.forEach(function(def) {
        var wrap = document.createElement('div');
        wrap.className = 'field-group';
        var label = document.createElement('label');
        label.textContent = def.label;
        var input;
        if (def.type === 'select') {
            input = document.createElement('select');
            UPDATE_FIELDS[kind].forEach(function(o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                input.appendChild(opt);
            });
        } else if (def.type === 'textarea') {
            input = document.createElement('textarea');
        } else {
            input = document.createElement('input');
            input.type = def.type === 'number' ? 'number' : (def.type === 'date' ? 'date' : 'text');
        }
        input.setAttribute('data-key', def.key);
        var v = values ? values[def.key] : undefined;
        input.value = (v === undefined || v === null) ? (def.value === undefined ? '' : def.value) : v;
        wrap.appendChild(label);
        wrap.appendChild(input);
        box.appendChild(wrap);
    });
}

function conditionTypeFor(fieldValue) {
    var kind = entityKindForTrigger(document.getElementById('trigger_type').value);
    var list = CONDITION_FIELDS[kind] || [];
    for (var i = 0; i < list.length; i++) {
        if (list[i].value === fieldValue) { return list[i].type; }
    }
    return 'text';
}

function buildConditionOps(item, fieldValue, preselect) {
    var opSel = item.querySelector('.condition-op');
    if (!opSel) { return; }
    var ops = CONDITION_OPS[conditionTypeFor(fieldValue)] || CONDITION_OPS.text;
    opSel.innerHTML = '';
    for (var i = 0; i < ops.length; i++) {
        var o = document.createElement('option');
        o.value = ops[i][0];
        o.textContent = ops[i][1];
        opSel.appendChild(o);
    }
    var found = false;
    for (var j = 0; j < ops.length; j++) {
        if (ops[j][0] === preselect) { found = true; }
    }
    opSel.value = found ? preselect : ops[0][0];
}

function buildConditionFields(item, fieldValue) {
    var fieldSel = item.querySelector('.condition-field');
    var valIn = item.querySelector('.condition-value');
    var kind = entityKindForTrigger(document.getElementById('trigger_type').value);
    var list = CONDITION_FIELDS[kind] || [];
    var type;

    fieldSel.innerHTML = '';
    for (var i = 0; i < list.length; i++) {
        var o = document.createElement('option');
        o.value = list[i].value;
        o.textContent = list[i].label;
        fieldSel.appendChild(o);
    }
    // A field that does not exist for this entity kind must fall back to the
    // first valid one, otherwise the row would serialize as empty and vanish.
    var valid = false;
    for (var j = 0; j < list.length; j++) {
        if (list[j].value === fieldValue) { valid = true; }
    }
    fieldSel.value = valid ? fieldValue : (list[0] ? list[0].value : '');
    type = conditionTypeFor(fieldSel.value);
    buildConditionOps(item, fieldSel.value, '');

    if (valIn) {
        if (type === 'status') {
            valIn.setAttribute('list', 'leadStatusList');
            valIn.setAttribute('placeholder', 'e.g. qualified');
        } else {
            valIn.removeAttribute('list');
            valIn.setAttribute('placeholder', type === 'date' ? 'YYYY-MM-DD' : 'Value');
        }
        valIn.type = 'text';
    }
}

function conditionFieldChanged(sel) {
    var item = sel.closest('.condition-item');
    var valIn = item.querySelector('.condition-value');
    if (valIn) { valIn.value = ''; }
    buildConditionFields(item, sel.value);
    syncConditionsField();
}

function addCondition(preset) {
    var list = document.getElementById('conditionList');
    var wrap = document.createElement('div');
    wrap.innerHTML = document.getElementById('conditionTemplate').innerHTML;
    var item = wrap.firstElementChild;
    list.appendChild(item);
    if (preset && preset.field) {
        buildConditionFields(item, preset.field);
        buildConditionOps(item, item.querySelector('.condition-field').value, preset.op || 'equals');
        var valIn = item.querySelector('.condition-value');
        if (valIn) { valIn.value = (preset.value === undefined || preset.value === null) ? '' : preset.value; }
    } else {
        buildConditionFields(item, '');
    }
    syncConditionsField();
    return item;
}

window.removeCondition = function(btn) {
    var item = btn.closest('.condition-item');
    if (item && item.parentNode) { item.parentNode.removeChild(item); }
    syncConditionsField();
};

function collectConditions() {
    var out = [];
    var items = document.querySelectorAll('#conditionList .condition-item');
    for (var i = 0; i < items.length; i++) {
        var f = items[i].querySelector('.condition-field');
        var o = items[i].querySelector('.condition-op');
        var v = items[i].querySelector('.condition-value');
        if (!f || !f.value) { continue; }
        out.push({
            field: f.value,
            op: (o && o.value) ? o.value : 'equals',
            value: v ? v.value : ''
        });
    }
    return out;
}

function syncConditionsField() {
    var list = collectConditions();
    var matchEl = document.getElementById('condition_match');
    var payload = list.length
        ? {match: (matchEl && matchEl.value) ? matchEl.value : 'all', conditions: list}
        : {};
    document.getElementById('trigger_conditions').value = JSON.stringify(payload);
}

function loadConditionsIntoBuilder(conds) {
    document.getElementById('conditionList').innerHTML = '';
    var list = [];
    var match = 'all';
    if (conds && typeof conds === 'object' && !Array.isArray(conds)) {
        if (conds.match) { match = conds.match; }
        if (conds.conditions && conds.conditions.length) { list = conds.conditions; }
    } else if (conds && conds.length) {
        list = conds;
    }
    var sel = document.getElementById('condition_match');
    if (sel) { sel.value = (match === 'any') ? 'any' : 'all'; }
    for (var i = 0; i < list.length; i++) {
        addCondition(list[i]);
    }
    syncConditionsField();
}

function conditionOpLabel(op) {
    var label = 'is';
    var keys = Object.keys(CONDITION_OPS);
    for (var i = 0; i < keys.length; i++) {
        var ops = CONDITION_OPS[keys[i]];
        for (var j = 0; j < ops.length; j++) {
            if (ops[j][0] === op) { label = ops[j][1]; }
        }
    }
    return label;
}

function conditionFieldLabel(trigger, field) {
    var list = CONDITION_FIELDS[entityKindForTrigger(trigger)] || [];
    for (var i = 0; i < list.length; i++) {
        if (list[i].value === field) { return list[i].label; }
    }
    return field;
}

function summarizeConditions(rule) {
    var cc = rule.trigger_conditions;
    if (!cc) { return ''; }
    var list = Array.isArray(cc) ? cc : (cc.conditions || []);
    if (!list.length) { return ''; }
    var joiner = (cc.match === 'any') ? ' OR ' : ' AND ';
    var bits = [];
    for (var i = 0; i < list.length; i++) {
        var c = list[i];
        var txt = escapeHtml(conditionFieldLabel(rule.trigger_type, c.field)) + ' ' + escapeHtml(conditionOpLabel(c.op));
        if (VALUE_LESS_OPS.indexOf(c.op) < 0) { txt += ' "' + escapeHtml(c.value) + '"'; }
        bits.push(txt);
    }
    return '<div class="trigger">When ' + bits.join(joiner) + '</div>';
}

function onTriggerChange() {
    var items = document.querySelectorAll('#actionList .action-item');
    for (var i = 0; i < items.length; i++) {
        var sel = items[i].querySelector('.action-type');
        if (sel && sel.value === 'update_field') {
            buildActionFields(items[i], 'update_field', {});
        }
    }
    // lead_* and opportunity_* expose different condition fields, so rebuild.
    var conds = collectConditions();
    document.getElementById('conditionList').innerHTML = '';
    for (var j = 0; j < conds.length; j++) {
        addCondition(conds[j]);
    }
    syncConditionsField();
}

function addAction(preset) {
    var list = document.getElementById('actionList');
    var wrap = document.createElement('div');
    wrap.innerHTML = document.getElementById('actionTemplate').innerHTML;
    var item = wrap.firstElementChild;
    list.appendChild(item);
    if (preset && preset.type && ACTION_FIELDS[preset.type]) {
        item.querySelector('.action-type').value = preset.type;
        buildActionFields(item, preset.type, preset);
    }
    syncActionsField();
    return item;
}

window.removeAction = function(btn) {
    var item = btn.closest('.action-item');
    if (item) item.parentNode.removeChild(item);
    syncActionsField();
};

function actionTypeChanged(sel) {
    buildActionFields(sel.closest('.action-item'), sel.value, {});
    syncActionsField();
}

function collectActions() {
    var out = [];
    var items = document.querySelectorAll('#actionList .action-item');
    for (var i = 0; i < items.length; i++) {
        var sel = items[i].querySelector('.action-type');
        if (!sel || !sel.value) continue;
        var act = {type: sel.value};
        var inputs = items[i].querySelectorAll('.action-fields [data-key]');
        for (var j = 0; j < inputs.length; j++) {
            act[inputs[j].getAttribute('data-key')] = inputs[j].value;
        }
        out.push(act);
    }
    return out;
}

function syncActionsField() {
    document.getElementById('actions').value = JSON.stringify(collectActions());
}

function loadActionsIntoBuilder(actions) {
    document.getElementById('actionList').innerHTML = '';
    var list = actions || [];
    for (var i = 0; i < list.length; i++) {
        addAction(list[i]);
    }
    if (!list.length) addAction();
    syncActionsField();
}

function loadRules() {
    var list = document.getElementById('rulesList');
    fetch('api.php?action=listAutomationRules&include_inactive=1')
    .then(function(r) { return r.json(); })
    .then(function(res) {
        var rules = res.data || [];
        if (!rules.length) {
            list.innerHTML = '<div style="text-align:center; padding:40px; color:#6c757d;">No rules yet. Use Quick Setup above, or click "+ New Rule".</div>';
            return;
        }
        var html = '';
        rules.forEach(function(rule) {
            var acts = rule.actions || [];
            var badges = '';
            for (var i = 0; i < acts.length; i++) {
                badges += '<span class="badge badge-active">' + escapeHtml(ACTION_LABELS[acts[i].type] || acts[i].type) + '</span>';
            }
            html += '<div class="rule-card">'
                + '<div class="header"><div>'
                + '<div class="name">' + escapeHtml(rule.rule_name) + '</div>'
                + '<div class="trigger">' + escapeHtml(TRIGGER_LABELS[rule.trigger_type] || rule.trigger_type) + ' &middot; priority ' + (rule.priority || 0) + '</div>'
                + summarizeConditions(rule)
                + '</div><div>'
                + '<span class="badge ' + (rule.is_active ? 'badge-active' : 'badge-inactive') + '">' + (rule.is_active ? 'Active' : 'Inactive') + '</span>'
                + '<button class="toggle-btn ' + (rule.is_active ? 'active' : 'inactive') + '" onclick="toggleRule(' + (rule.id) + ',' + (!rule.is_active) + ')">' + (rule.is_active ? 'Disable' : 'Enable') + '</button> '
                + '<button class="edit-btn" onclick="editRule(' + (rule.id) + ')">Edit</button> '
                + '<button class="delete-btn" onclick="deleteRule(' + (rule.id) + ')">Delete</button>'
                + '</div></div>'
                + '<div class="actions-list">' + (badges || '<em>No actions</em>') + '</div>'
                + '</div>';
        });
        list.innerHTML = html;
    })
    .catch(function() {
        list.innerHTML = '<div style="text-align:center; padding:40px; color:#e74c3c;">Could not load rules.</div>';
    });
}

function loadTriggers() {
    document.getElementById('trigger_type').disabled = false;
}

document.getElementById('ruleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var acts = collectActions();
    if (!acts.length) {
        SmartDialog.error('Add at least one action before saving');
        return;
    }
    var fd = new FormData();
    fd.append('action', 'saveAutomationRule');
    fd.append('id', document.getElementById('ruleId').value || 0);
    fd.append('rule_name', document.getElementById('rule_name').value);
    fd.append('trigger_type', document.getElementById('trigger_type').value);
    fd.append('actions', JSON.stringify(acts));
    fd.append('trigger_conditions', document.getElementById('trigger_conditions').value || '{}');
    fd.append('priority', document.getElementById('priority').value || 0);
    fd.append('is_active', document.getElementById('is_active').checked ? 1 : 0);
    fetch('api.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            closeRuleModal();
            loadRules();
            SmartDialog.success('Rule saved');
        } else {
            SmartDialog.error(res.error || 'Error saving rule');
        }
    })
    .catch(function() { SmartDialog.error('Network error'); });
});

document.addEventListener('DOMContentLoaded', function() {
    loadRules();
    loadTriggers();
});
</script>

<?php include($PathPrefix . 'includes/footer.inc'); ?>
