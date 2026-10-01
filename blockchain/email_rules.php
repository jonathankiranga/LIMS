<?php
$PathPrefix = './';
include($PathPrefix . 'include/session.inc');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Rules & Reminders</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.er-app { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background: #f5f2f8; min-height: 100vh; box-sizing: border-box; }
.er-app *, .er-app *::before, .er-app *::after { box-sizing: border-box; }
.er-header { background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.er-header h1 { margin: 0; color: #6b4fd6; font-size: 24px; }
.er-tabs { display: flex; gap: 5px; margin-bottom: 0; background: #fff; padding: 15px 15px 0; border-radius: 12px 12px 0 0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.er-tab { padding: 10px 20px; border: none; background: transparent; color: #6f6c7a; font-size: 14px; font-weight: 500; cursor: pointer; border-radius: 8px 8px 0 0; transition: all 0.2s; }
.er-tab:hover { background: #f5f2f8; }
.er-tab.active { background: #6b4fd6; color: #fff; }
.er-content { background: #fff; padding: 20px; border-radius: 0 0 12px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.er-section { margin-bottom: 20px; }
.er-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.er-section h2 { margin: 0; color: #333; font-size: 18px; }
.er-btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; }
.er-btn-primary { background: #6b4fd6; color: #fff; }
.er-btn-primary:hover { background: #5a3fc0; }
.er-btn-success { background: #27ae60; color: #fff; }
.er-btn-secondary { background: #f5f2f8; color: #333; }
.er-table { width: 100%; border-collapse: collapse; }
.er-table th { background: #6b4fd6; color: #fff; padding: 12px 10px; text-align: left; font-size: 13px; }
.er-table td { padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 13px; color: #333; }
.er-table tr:hover { background: #f8f9fa; }
.er-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; display: inline-block; }
.er-badge-active { background: #d4edda; color: #155724; }
.er-badge-inactive { background: #f8d7da; color: #721c24; }
.er-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; }
.er-modal.show { display: flex; }
.er-modal-content { background: #fff; padding: 25px; border-radius: 10px; width: 600px; max-width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.er-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.er-modal-header h2 { margin: 0; color: #333; }
.er-form-group { margin-bottom: 15px; }
.er-form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; font-size: 13px; }
.er-form-group input, .er-form-group select, .er-form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
.er-form-group textarea { min-height: 100px; }
.er-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; padding: 0; line-height: 1; }
.er-modal-close:hover { color: #e74c3c; }
.er-modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; }
.er-empty { text-align: center; padding: 40px; color: #999; }
</style>
</head>
<body>
<div class="er-app">
<div class="er-header">
    <h1><i class="fas fa-envelope"></i> Email Rules & Reminders</h1>
</div>

<div class="er-tabs">
    <button class="er-tab active" onclick="showTab('templates', this)"><i class="fas fa-file-alt"></i> Templates</button>
    <button class="er-tab" onclick="showTab('rules', this)"><i class="fas fa-magic"></i> Automation Rules</button>
    <button class="er-tab" onclick="showTab('reminders', this)"><i class="fas fa-bell"></i> Reminders</button>
</div>

<div class="er-content">
    <!-- Templates -->
    <div id="tab-templates" class="er-tab-content">
        <div class="er-section-header">
            <h2>Email Templates</h2>
            <button class="er-btn er-btn-success" onclick="showTemplateModal()"><i class="fas fa-plus"></i> New Template</button>
        </div>
        <div id="templates-list"></div>
    </div>
    
    <!-- Rules -->
    <div id="tab-rules" class="er-tab-content" style="display:none;">
        <div class="er-section-header">
            <h2>Automation Rules</h2>
            <button class="er-btn er-btn-success" onclick="showRuleModal()"><i class="fas fa-plus"></i> New Rule</button>
        </div>
        <div id="rules-list"></div>
    </div>
    
    <!-- Reminders -->
    <div id="tab-reminders" class="er-tab-content" style="display:none;">
        <div class="er-section-header">
            <h2>Reminders</h2>
            <button class="er-btn er-btn-success" onclick="showReminderModal()"><i class="fas fa-plus"></i> New Reminder</button>
        </div>
        <div id="reminders-list"></div>
    </div>
</div>

<!-- Template Modal -->
<div id="templateModal" class="er-modal">
    <div class="er-modal-content">
        <div class="er-modal-header">
            <h2 id="templateModalTitle">New Template</h2>
            <button class="er-modal-close" onclick="closeModal('templateModal')">&times;</button>
        </div>
        <form id="templateForm">
            <input type="hidden" id="templateId" value="0">
            <div class="er-form-group">
                <label>Template Name *</label>
                <input type="text" id="templateName" required>
            </div>
            <div class="er-form-group">
                <label>Subject *</label>
                <input type="text" id="templateSubject" required>
            </div>
            <div class="er-form-group">
                <label>Category</label>
                <select id="templateCategory">
                    <option value="general">General</option>
                    <option value="reminder">Reminder</option>
                    <option value="meeting">Meeting</option>
                    <option value="task">Task</option>
                </select>
            </div>
            <div class="er-form-group">
                <label>Body *</label>
                <textarea id="templateBody" required></textarea>
            </div>
            <div class="er-form-group">
                <label><input type="checkbox" id="templateActive" checked> Active</label>
            </div>
            <div class="er-modal-footer">
                <button type="button" class="er-btn er-btn-secondary" onclick="closeModal('templateModal')">Cancel</button>
                <button type="submit" class="er-btn er-btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Rule Modal -->
<div id="ruleModal" class="er-modal">
    <div class="er-modal-content">
        <div class="er-modal-header">
            <h2 id="ruleModalTitle">New Rule</h2>
            <button class="er-modal-close" onclick="closeModal('ruleModal')">&times;</button>
        </div>
        <form id="ruleForm">
            <input type="hidden" id="ruleId" value="0">
            <div class="er-form-group">
                <label>Rule Name *</label>
                <input type="text" id="ruleName" required>
            </div>
            <div class="er-form-group">
                <label>Trigger Type *</label>
                <select id="ruleTrigger">
                    <option value="manual">Manual</option>
                    <option value="task_due_soon">Task Due Soon</option>
                    <option value="task_overdue">Task Overdue</option>
                    <option value="daily_digest">Daily Digest</option>
                </select>
            </div>
            <div class="er-form-group">
                <label>Email Template</label>
                <select id="ruleTemplate">
                    <option value="">-- Select Template --</option>
                </select>
            </div>
            <div class="er-form-group">
                <label>Recipients (JSON: [{"email":"user@test.com","name":"User"}])</label>
                <textarea id="ruleRecipients" rows="3" placeholder='[{"email":"user@test.com","name":"User"}]'></textarea>
            </div>
            <div class="er-form-group">
                <label>Priority</label>
                <input type="number" id="rulePriority" value="0">
            </div>
            <div class="er-form-group">
                <label><input type="checkbox" id="ruleActive" checked> Active</label>
            </div>
            <div class="er-modal-footer">
                <button type="button" class="er-btn er-btn-secondary" onclick="closeModal('ruleModal')">Cancel</button>
                <button type="submit" class="er-btn er-btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Reminder Modal -->
<div id="reminderModal" class="er-modal">
    <div class="er-modal-content">
        <div class="er-modal-header">
            <h2>New Reminder</h2>
            <button class="er-modal-close" onclick="closeModal('reminderModal')">&times;</button>
        </div>
        <form id="reminderForm">
            <div class="er-form-group">
                <label>Title *</label>
                <input type="text" id="reminderTitle" required>
            </div>
            <div class="er-form-group">
                <label>Date & Time *</label>
                <input type="datetime-local" id="reminderDate" required>
            </div>
            <div class="er-form-group">
                <label>Description</label>
                <textarea id="reminderDescription"></textarea>
            </div>
            <div class="er-modal-footer">
                <button type="button" class="er-btn er-btn-secondary" onclick="closeModal('reminderModal')">Cancel</button>
                <button type="submit" class="er-btn er-btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
if (window._emailRulesInit) return;
window._emailRulesInit = true;

const API_URL = 'functions/api.php';
const lsUserId = localStorage.getItem('user_id');

// Proxy fetch to append user_id automatically to workspace API calls
const originalFetch = window.fetch;
window.fetch = async function(url, options = {}) {
    if (typeof url === 'string' && url.includes(API_URL)) {
        if (!options.method || options.method.toUpperCase() === 'GET') {
            url += (url.includes('?') ? '&' : '?') + 'user_id=' + encodeURIComponent(lsUserId || '');
        } else if (options.method.toUpperCase() === 'POST' && (options.body instanceof URLSearchParams || options.body instanceof FormData)) {
            options.body.append('user_id', lsUserId || '');
        }
    }
    const res = await originalFetch(url, options);
    try {
        const cloned = res.clone();
        const data = await cloned.json();
        if (data && data.error) {
            if (typeof toastr !== 'undefined') toastr.error(data.error, 'Error');
            else alert(data.error);
        }
    } catch(e) {}
    return res;
};

window.showTab = function(tab, btn) {
    document.querySelectorAll('.er-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.er-tab-content').forEach(c => c.style.display = 'none');
    document.getElementById('tab-' + tab).style.display = 'block';
    if (btn) btn.classList.add('active');
    
    if (tab === 'templates') loadTemplates();
    else if (tab === 'rules') { loadRules(); loadTemplateOptions(); }
    else if (tab === 'reminders') loadReminders();
};

async function loadTemplateOptions() {
    const res = await fetch(API_URL + '?action=listEmailTemplates');
    const data = await res.json();
    const templates = data.data || [];
    document.getElementById('ruleTemplate').innerHTML = '<option value="">-- Select Template --</option>' + 
        templates.map(t => `<option value="${t.id}">${t.template_name}</option>`).join('');
}

window.showModal = function(id) { document.getElementById(id).classList.add('show'); };
window.closeModal = function(id) { document.getElementById(id).classList.remove('show'); };

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function loadTemplates() {
    const res = await fetch(API_URL + '?action=listEmailTemplates');
    const data = await res.json();
    const templates = data.data || [];
    
    if (!templates.length) {
        document.getElementById('templates-list').innerHTML = '<div class="er-empty">No templates yet</div>';
        return;
    }
    
    document.getElementById('templates-list').innerHTML = `
        <table class="er-table">
            <thead><tr><th>Name</th><th>Subject</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                ${templates.map(t => `
                    <tr>
                        <td>${escapeHtml(t.template_name)}</td>
                        <td>${escapeHtml(t.subject)}</td>
                        <td>${escapeHtml(t.category)}</td>
                        <td><span class="er-badge ${t.is_active ? 'er-badge-active' : 'er-badge-inactive'}">${t.is_active ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <button class="er-btn er-btn-secondary" onclick="editTemplate(${t.id})"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function loadRules() {
    const res = await fetch(API_URL + '?action=listEmailRules');
    const data = await res.json();
    const rules = data.data || [];
    
    if (!rules.length) {
        document.getElementById('rules-list').innerHTML = '<div class="er-empty">No rules yet</div>';
        return;
    }
    
    document.getElementById('rules-list').innerHTML = `
        <table class="er-table">
            <thead><tr><th>Name</th><th>Trigger</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                ${rules.map(r => `
                    <tr>
                        <td>${escapeHtml(r.rule_name)}</td>
                        <td>${escapeHtml(r.trigger_type)}</td>
                        <td>${r.priority}</td>
                        <td><span class="er-badge ${r.is_active ? 'er-badge-active' : 'er-badge-inactive'}">${r.is_active ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <button class="er-btn er-btn-secondary" onclick="editRule(${r.id})"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function loadReminders() {
    const res = await fetch(API_URL + '?action=listReminders');
    const data = await res.json();
    const reminders = data.data || [];
    
    if (!reminders.length) {
        document.getElementById('reminders-list').innerHTML = '<div class="er-empty">No reminders yet</div>';
        return;
    }
    
    document.getElementById('reminders-list').innerHTML = `
        <table class="er-table">
            <thead><tr><th>Title</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                ${reminders.map(r => `
                    <tr>
                        <td>${escapeHtml(r.title)}</td>
                        <td>${r.reminder_date}</td>
                        <td><span class="er-badge er-badge-${r.status === 'pending' ? 'active' : 'inactive'}">${r.status}</span></td>
                        <td>
                            <button class="er-btn er-btn-secondary" onclick="completeReminder(${r.id})"><i class="fas fa-check"></i></button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

function showTemplateModal(id) {
    document.getElementById('templateId').value = id || 0;
    document.getElementById('templateForm').reset();
    document.getElementById('templateModalTitle').textContent = id ? 'Edit Template' : 'New Template';
    if (id) loadTemplateData(id);
    showModal('templateModal');
}

function showRuleModal(id) {
    document.getElementById('ruleId').value = id || 0;
    document.getElementById('ruleForm').reset();
    document.getElementById('ruleModalTitle').textContent = id ? 'Edit Rule' : 'New Rule';
    if (id) loadRuleData(id);
    showModal('ruleModal');
}

function showReminderModal() {
    document.getElementById('reminderForm').reset();
    showModal('reminderModal');
}

async function loadTemplateData(id) {
    const res = await fetch(API_URL + '?action=getEmailTemplate&id=' + id);
    const data = await res.json();
    if (data.data) {
        document.getElementById('templateName').value = data.data.template_name || '';
        document.getElementById('templateSubject').value = data.data.subject || '';
        document.getElementById('templateCategory').value = data.data.category || 'general';
        document.getElementById('templateBody').value = data.data.body || '';
        document.getElementById('templateActive').checked = data.data.is_active == 1;
    }
}

async function loadRuleData(id) {
    const res = await fetch(API_URL + '?action=getEmailRule&id=' + id);
    const data = await res.json();
    if (data.data) {
        document.getElementById('ruleName').value = data.data.rule_name || '';
        document.getElementById('ruleTrigger').value = data.data.trigger_type || 'manual';
        document.getElementById('rulePriority').value = data.data.priority || 0;
        document.getElementById('ruleActive').checked = data.data.is_active == 1;
        document.getElementById('ruleTemplate').value = data.data.email_template_id || '';
        document.getElementById('ruleRecipients').value = data.data.recipients || '[]';
    }
}

window.editTemplate = function(id) { showTemplateModal(id); };
window.editRule = function(id) { showRuleModal(id); };
window.showTemplateModal = showTemplateModal;
window.showRuleModal = showRuleModal;
window.showReminderModal = showReminderModal;
window.completeReminder = async function(id) {
    await fetch(API_URL, { method: 'POST', body: new URLSearchParams({ action: 'completeReminder', id }) });
    loadReminders();
};

document.getElementById('templateForm').onsubmit = async function(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'saveEmailTemplate');
    fd.append('id', document.getElementById('templateId').value);
    fd.append('template_name', document.getElementById('templateName').value);
    fd.append('subject', document.getElementById('templateSubject').value);
    fd.append('category', document.getElementById('templateCategory').value);
    fd.append('body', document.getElementById('templateBody').value);
    fd.append('is_active', document.getElementById('templateActive').checked ? '1' : '0');
    
    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) { closeModal('templateModal'); loadTemplates(); }
};

document.getElementById('ruleForm').onsubmit = async function(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'saveEmailRule');
    fd.append('id', document.getElementById('ruleId').value);
    fd.append('rule_name', document.getElementById('ruleName').value);
    fd.append('trigger_type', document.getElementById('ruleTrigger').value);
    fd.append('priority', document.getElementById('rulePriority').value);
    fd.append('is_active', document.getElementById('ruleActive').checked ? '1' : '0');
    fd.append('email_template_id', document.getElementById('ruleTemplate').value || '0');
    fd.append('recipients', document.getElementById('ruleRecipients').value || '[]');
    
    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) { closeModal('ruleModal'); loadRules(); }
};

document.getElementById('reminderForm').onsubmit = async function(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'saveReminder');
    fd.append('title', document.getElementById('reminderTitle').value);
    fd.append('reminder_date', document.getElementById('reminderDate').value);
    fd.append('description', document.getElementById('reminderDescription').value);
    
    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) { closeModal('reminderModal'); loadReminders(); }
};

// Initialize
loadTemplates();
})();
</script>

</div>
</body>
</html>
