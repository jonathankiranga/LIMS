<?php
// Communications Log Page
$PageSecurity = 0;
$PathPrefix = './';
$inIframe = true;
$extraCrumb = 'Communications';
error_reporting(E_ALL);
include($PathPrefix . 'includes/session.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');

$Title = _('Communications');
include($PathPrefix . 'includes/header.inc');

unset($extraCrumb);
?>
<link rel="stylesheet" href="kanban.css">
<style>
.crm-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
.crm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
.crm-header h2 { margin: 0; color: #2c3e50; }
.comm-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; align-items: center; }
.comm-filters select, .comm-filters input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
.comm-timeline { position: relative; padding-left: 30px; }
.comm-timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
.comm-item { position: relative; background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.comm-item::before { content: ''; position: absolute; left: -24px; top: 20px; width: 12px; height: 12px; border-radius: 50%; background: #3498db; }
.comm-item.type-email::before { background: #27ae60; }
.comm-item.type-call::before { background: #f39c12; }
.comm-item.type-meeting::before { background: #9b59b6; }
.comm-item.type-note::before { background: #95a5a6; }
.comm-item .type-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; font-weight: 600; }
.comm-item .type-badge.email { background: #d4edda; color: #155724; }
.comm-item .type-badge.call { background: #fff3cd; color: #856404; }
.comm-item .type-badge.meeting { background: #e2e3f5; color: #383d41; }
.comm-item .type-badge.note { background: #e9ecef; color: #383d41; }
.comm-item .subject { font-weight: 600; color: #2c3e50; margin: 5px 0; }
.comm-item .content { color: #6c757d; font-size: 14px; margin: 10px 0; white-space: pre-wrap; }
.comm-item .meta { font-size: 12px; color: #adb5bd; display: flex; gap: 15px; flex-wrap: wrap; }
.comm-item .direction-badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; }
.comm-item .direction-badge.inbound { background: #cce5ff; color: #004085; }
.comm-item .direction-badge.outbound { background: #d4edda; color: #155724; }
.btn-success { background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.btn-primary { background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.btn-secondary { background: #95a5a6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.btn-danger { background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
.modal.show { display: flex; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.close-modal { background: none; border: none; font-size: 24px; cursor: pointer; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #2c3e50; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.quick-actions { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.quick-actions button { display: flex; align-items: center; gap: 5px; }
.entity-selector { display: flex; gap: 10px; margin-bottom: 20px; }
.entity-selector select { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
</style>

<div class="crm-container">
    <div class="crm-header">
        <h2><?php echo _('Communications Log'); ?></h2>
    </div>
    
    <div class="quick-actions">
        <button class="btn-success" onclick="openCommModal('email')">+ Log Email</button>
        <button class="btn-primary" onclick="openCommModal('call')">+ Log Call</button>
        <button class="btn-secondary" onclick="openCommModal('meeting')">+ Log Meeting</button>
        <button class="btn-secondary" onclick="openCommModal('note')">+ Add Note</button>
    </div>
    
    <div class="entity-selector">
        <select id="entityType" onchange="switchEntityType()">
            <option value="lead">Leads</option>
            <option value="opportunity">Opportunities</option>
            <option value="contact">Contacts</option>
        </select>
        <select id="entityId" onchange="loadCommunications()">
            <option value="">Select...</option>
        </select>
    </div>
    
    <div class="comm-timeline" id="commTimeline">
        <div style="text-align:center; padding: 40px;">Select an entity to view communications</div>
    </div>
</div>

<!-- Communication Modal -->
<div class="modal" id="commModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="commModalTitle">Log Communication</h3>
            <button class="close-modal" onclick="closeCommModal()">&times;</button>
        </div>
        <form id="commForm">
            <input type="hidden" id="commId" name="id" value="0">
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select id="communication_type" name="communication_type">
                        <option value="email">Email</option>
                        <option value="call">Call</option>
                        <option value="meeting">Meeting</option>
                        <option value="note">Note</option>
                        <option value="sms">SMS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Direction</label>
                    <select id="direction" name="direction">
                        <option value="outbound">Outbound (I sent/made)</option>
                        <option value="inbound">Inbound (Received)</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Subject *</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea id="content" name="content" rows="4"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>From Email/Phone</label>
                    <input type="text" id="from_contact" name="from_contact">
                </div>
                <div class="form-group">
                    <label>To Email/Phone</label>
                    <input type="text" id="to_contact" name="to_contact">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Duration (seconds)</label>
                    <input type="number" id="duration_seconds" name="duration_seconds">
                </div>
                <div class="form-group">
                    <label>Outcome</label>
                    <select id="outcome" name="outcome">
                        <option value="">Select Outcome</option>
                        <option value="completed">Completed</option>
                        <option value="no_answer">No Answer</option>
                        <option value="left_voicemail">Left Voicemail</option>
                        <option value="callback_later">Callback Later</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Next Action</label>
                <input type="text" id="next_action" name="next_action">
            </div>
            <div class="form-group">
                <label>Next Follow-up Date</label>
                <input type="date" id="next_followup" name="next_followup">
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn-secondary" onclick="closeCommModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentEntityType = 'lead';

function switchEntityType() {
    currentEntityType = document.getElementById('entityType').value;
    loadEntityList();
}

function loadEntityList() {
    const type = currentEntityType;
    let action = type === 'lead' ? 'listLeads' : (type === 'opportunity' ? 'listOpportunities' : 'listContacts');
    
    fetch('api.php?action=' + action)
    .then(r => r.json())
    .then(res => {
        const select = document.getElementById('entityId');
        select.innerHTML = '<option value="">Select...</option>';
        if (res.data) {
            res.data.forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.id;
                const label = e.company_name || e.opportunity_name || e.name || 'Entity #' + e.id;
                opt.textContent = e.owner ? label + ' (' + e.owner + ')' : label;
                select.appendChild(opt);
            });
        }
    });
}

function loadCommunications() {
    const entityType = document.getElementById('entityType').value;
    const entityId = document.getElementById('entityId').value;
    const timeline = document.getElementById('commTimeline');
    
    if (!entityId) {
        timeline.innerHTML = '<div style="text-align:center; padding: 40px;">Select an entity to view communications</div>';
        return;
    }
    
    const params = entityType + '_id=' + entityId;
    fetch('api.php?action=listCommunications&' + params)
    .then(r => r.json())
    .then(res => {
        if (!res.data || res.data.length === 0) {
            timeline.innerHTML = '<div style="text-align:center; padding: 40px;">No communications found</div>';
            return;
        }
        timeline.innerHTML = res.data.map(c => `
            <div class="comm-item type-${c.communication_type}">
                <span class="type-badge ${c.communication_type}">${c.communication_type}</span>
                <span class="direction-badge ${c.direction}">${c.direction}</span>
                <div class="subject">${c.subject || 'No subject'}</div>
                <div class="content">${c.content || ''}</div>
                <div class="meta">
                    <span>By: ${c.created_by || 'System'}</span>
                    <span>${c.created_at || ''}</span>
                    ${c.duration_seconds ? '<span>Duration: ' + c.duration_seconds + 's</span>' : ''}
                    ${c.outcome ? '<span>Outcome: ' + c.outcome + '</span>' : ''}
                </div>
            </div>
        `).join('');
    });
}

function openCommModal(type) {
    const entityType = document.getElementById('entityType').value;
    const entityId = document.getElementById('entityId').value;
    
        if (!entityId) {
            toastWarning('Please select a ' + entityType + ' first');
            return;
        }
    
    document.getElementById('commModal').classList.add('show');
    document.getElementById('commModalTitle').textContent = 'Log ' + type.charAt(0).toUpperCase() + type.slice(1);
    document.getElementById('commForm').reset();
    document.getElementById('communication_type').value = type;
    
    const typeField = document.createElement('input');
    typeField.type = 'hidden';
    typeField.id = 'entity_' + entityType + '_id';
    typeField.name = entityType + '_id';
    typeField.value = entityId;
    document.getElementById('commForm').appendChild(typeField);
}

function closeCommModal() {
    document.getElementById('commModal').classList.remove('show');
    const hiddenInputs = document.getElementById('commForm').querySelectorAll('input[type="hidden"]');
    hiddenInputs.forEach(i => i.remove());
}

document.getElementById('commForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'saveCommunication');
    
    const type = document.getElementById('entityType').value;
    fd.append(type + '_id', document.getElementById('entityId').value);
    
    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
            if (res.ok) {
                closeCommModal();
                loadCommunications();
            } else {
                toastError(res.error || 'Error saving communication');
            }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadEntityList();
});
</script>

<?php include($PathPrefix . 'includes/footer.inc'); ?>
