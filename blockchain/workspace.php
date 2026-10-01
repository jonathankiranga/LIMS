<?php
$PathPrefix = './';
include($PathPrefix . 'include/session.inc');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Workspaces</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
html, body { height: 100%; margin: 0; }
body { overflow: hidden; }
.ws-app { font-family: 'Segoe UI', Arial, sans-serif; padding: 10px 12px; background: #f5f2f8; height: 100vh; box-sizing: border-box; display: flex; flex-direction: column; gap: 10px; overflow: hidden; }
.ws-app *, .ws-app *::before, .ws-app *::after { box-sizing: border-box; }
.ws-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0; flex-wrap: wrap; gap: 10px; background: #fff; padding: 12px 14px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); flex: 0 0 auto; }
.ws-header h1 { margin: 0; color: #2f2b3a; font-size: 20px; font-weight: 700; }
.ws-tabs { display: flex; gap: 8px; margin-bottom: 0; background: #fff; padding: 8px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); flex: 0 0 auto; }
.ws-tab { padding: 6px 12px; border: none; background: transparent; color: #6f6c7a; font-size: 13px; font-weight: 500; cursor: pointer; border-radius: 6px; transition: all 0.2s; }
.ws-tab:hover { background: #f5f2f8; }
.ws-tab.active { background: #2563eb; color: #fff; }
.ws-tab-content { flex: 1 1 auto; min-height: 0; overflow: hidden; }
.ws-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; height: 100%; overflow-y: auto; align-content: start; padding-right: 2px; }
.ws-card { background: #fff; border-radius: 10px; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform 0.2s, box-shadow 0.2s; }
.ws-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.ws-card h3 { margin: 0 0 8px; color: #2f2b3a; font-size: 16px; }
.ws-card p { margin: 0 0 12px; color: #6f6c7a; font-size: 12px; }
.ws-card .ws-meta { display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #6f6c7a; }
.ws-card .ws-actions { display: flex; gap: 6px; margin-top: 12px; }
.ws-btn { padding: 6px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; text-decoration: none; display: inline-block; }
.ws-btn-primary { background: #2563eb; color: #fff; }
.ws-btn-primary:hover { background: #1d4ed8; }
.ws-btn-secondary { background: #f5f2f8; color: #2f2b3a; }
.ws-btn-secondary:hover { background: #e1dfe8; }
.ws-btn-danger { background: #e74c3c; color: #fff; }
.ws-btn-danger:hover { background: #c0392b; }
.ws-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(47,43,58,0.65); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
.ws-modal.show { display: flex; }
.ws-modal-content { background: #fff; border-radius: 12px; padding: 20px; width: min(450px, 90vw); max-height: 90vh; overflow-y: auto; box-shadow: 0 18px 36px rgba(0,0,0,0.16); }
.ws-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.ws-modal-header h2, .ws-modal-header h3 { margin: 0; color: #2f2b3a; font-size: 16px; }
.ws-form-group { margin-bottom: 12px; }
.ws-form-group label { display: block; font-size: 12px; font-weight: 600; color: #2f2b3a; margin-bottom: 4px; }
.ws-form-group input, .ws-form-group select, .ws-form-group textarea { width: 100%; padding: 8px 10px; border: 2px solid #e1dfe8; border-radius: 6px; font-size: 13px; }
.ws-form-group input:focus, .ws-form-group select:focus, .ws-form-group textarea:focus { border-color: #2563eb; outline: none; }
.ws-modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #6f6c7a; padding: 0; line-height: 1; }
.ws-modal-close:hover { color: #e74c3c; }
.ws-modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 15px; padding-top: 12px; border-top: 1px solid #e1dfe8; }
.ws-user-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; }
.ws-user-chip { display: flex; flex-direction: column; align-items: center; padding: 8px; background: #f5f2f8; border-radius: 8px; cursor: pointer; transition: all 0.2s; border: 2px solid transparent; }
.ws-user-chip:hover { background: #e1dfe8; }
.ws-user-chip.selected { background: #2563eb; border-color: #2563eb; }
.ws-user-chip.selected .ws-avatar { background: #fff; color: #2563eb; }
.ws-user-chip.selected span { color: #fff; }
.ws-user-chip .ws-avatar { width: 32px; height: 32px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
.ws-user-chip span { font-size: 10px; color: #6f6c7a; text-align: center; }
.ws-user-chip input { display: none; }
.ws-empty { text-align: center; padding: 25px; color: #6f6c7a; }
.ws-empty i { font-size: 32px; margin-bottom: 10px; opacity: 0.5; display: block; }
.ws-empty h3 { margin: 0 0 8px; color: #2f2b3a; font-size: 15px; }
.ws-toastr-wrap { position: fixed; top: 14px; right: 14px; z-index: 2000; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
.ws-toastr { min-width: 240px; max-width: 360px; padding: 10px 12px; border-radius: 8px; color: #fff; font-size: 12px; box-shadow: 0 10px 24px rgba(0,0,0,0.24); pointer-events: auto; }
.ws-toastr-info { background: #2563eb; }
.ws-toastr-success { background: #16a34a; }
.ws-toastr-warning { background: #d97706; }
.ws-toastr-error { background: #dc2626; }
@media (max-width: 900px) {
    body { overflow: auto; }
    .ws-app { height: auto; min-height: 100vh; overflow: visible; }
    .ws-tab-content { overflow: visible; }
    .ws-grid { height: auto; overflow: visible; }
}
</style>
</head>
<body>
<div class="ws-app">
<div class="ws-header">
    <h1><i class="fas fa-users"></i> Workspaces</h1>
    <div style="display: flex; gap: 8px;">
         <button class="ws-btn ws-btn-primary" data-action="open-modal" data-modal="workspaceModal" style="display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-plus"></i> New Workspace
        </button>
    </div>
</div>

<div class="ws-tabs">
    <button class="ws-tab active" data-action="switch-tab" data-tab="my">
        <i class="fas fa-layer-group"></i> My Workspaces
    </button>
    <button class="ws-tab" data-action="switch-tab" data-tab="all">
        <i class="fas fa-globe"></i> All Workspaces
    </button>
</div>

<div id="tab-my" class="ws-tab-content">
    <div id="myWorkspaces" class="ws-grid"></div>
</div>

<div id="tab-all" class="ws-tab-content" style="display:none;">
    <div id="allWorkspaces" class="ws-grid"></div>
</div>

<!-- Workspace Modal -->
<div id="workspaceModal" class="ws-modal">
    <div class="ws-modal-content">
        <div class="ws-modal-header">
            <h3 id="workspaceModalTitle">New Workspace</h3>
            <button class="ws-modal-close" data-action="close-modal" data-modal="workspaceModal">&times;</button>
        </div>
        <form id="workspaceForm">
            <input type="hidden" id="workspaceId" value="0">
            <div class="ws-form-group">
                <label>Name *</label>
                <input type="text" id="workspaceName" required>
            </div>
            <div class="ws-form-group">
                <label>Description</label>
                <textarea id="workspaceDescription" rows="3"></textarea>
            </div>
            <div class="ws-form-group">
                <label>Color</label>
                <input type="color" id="workspaceColor" value="#6b4fd6">
            </div>
            <div class="ws-modal-footer">
                <button type="button" class="ws-btn ws-btn-secondary" data-action="close-modal" data-modal="workspaceModal">Cancel</button>
                <button type="submit" class="ws-btn ws-btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Members Modal -->
<div id="membersModal" class="ws-modal">
    <div class="ws-modal-content" style="width: 500px;">
        <div class="ws-modal-header">
            <h3>Workspace Members</h3>
            <button class="ws-modal-close" data-action="close-modal" data-modal="membersModal">&times;</button>
        </div>
        <input type="hidden" id="memberWorkspaceId" value="0">
        
        <div class="ws-form-group">
            <label style="font-weight: 600; margin-bottom: 10px; display: block;">Current Members</label>
            <div id="currentMembersList" style="max-height: 200px; overflow-y: auto; margin-bottom: 20px;"></div>
        </div>
        
        <div class="ws-form-group">
            <label style="font-weight: 600; margin-bottom: 10px; display: block;">Add More Users</label>
            <div id="userSelectList" class="ws-user-grid" style="max-height: 200px; overflow-y: auto;"></div>
        </div>
        
        <div class="ws-modal-footer">
            <button type="button" class="ws-btn ws-btn-secondary" data-action="close-modal" data-modal="membersModal">Close</button>
            <button type="button" class="ws-btn ws-btn-primary" data-action="save-members">Save Changes</button>
        </div>
    </div>
</div>

<!-- Board Modal (iframe) -->
<div id="boardModal" class="ws-modal" style="padding: 20px; background: rgba(0,0,0,0.5);">
    <div style="background: #fff; border-radius: 12px; width: 95vw; height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #f5f2f8; border-bottom: 1px solid #e1dfe8;">
            <h3 id="boardModalTitle" style="margin: 0; color: #2f2b3a;"><i class="fas fa-columns"></i> Board</h3>
            <button class="ws-modal-close" data-action="close-modal" data-modal="boardModal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6f6c7a;">&times;</button>
        </div>
        <iframe id="boardIframe" src="" style="flex: 1; border: none;"></iframe>
    </div>
</div>
<div id="wsToastrWrap" class="ws-toastr-wrap" aria-live="polite" aria-atomic="true"></div>

<style>
.ws-member-row { display: flex; align-items: center; padding: 8px 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 8px; }
.ws-member-row .ws-avatar-sm { width: 30px; height: 30px; border-radius: 50%; background: #4f46e5; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 10px; }
.ws-member-row .ws-member-name { flex: 1; font-size: 13px; color: #2f2b3a; font-weight: 500;}
.ws-member-row select { padding: 4px 8px; border: 1px solid #e1dfe8; border-radius: 4px; font-size: 12px; margin-right: 8px; color: #2f2b3a;}
.ws-member-row .ws-remove-btn { background: #fee2e2; color: #ef4444; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.2s; }
.ws-member-row .ws-remove-btn:hover { background: #ef4444; color: #fff; }
.ws-privilege-owner { background: #eef2ff; color: #4f46e5; }
.ws-privilege-admin { background: #f0f9ff; color: #0284c7; }
.ws-privilege-member { background: #f0fdf4; color: #16a34a; }
.ws-privilege-viewer { background: #fffbeb; color: #d97706; }
</style>

<script>
(function() {
window._workspacePageController?.abort();
const pageController = new AbortController();
const { signal } = pageController;
window._workspacePageController = pageController;

const API_URL = 'functions/api.php';
const lsUserId = localStorage.getItem('user_id');
let CURRENT_USER = null;
let deleteConfirmState = { id: null, expiresAt: 0 };

// Proxy fetch to append user_id automatically to workspace API calls
const originalFetch = window.fetch;
window.fetch = function(url, options = {}) {
    if (typeof url === 'string' && url.includes(API_URL)) {
        if (!options.method || options.method.toUpperCase() === 'GET') {
            url += (url.includes('?') ? '&' : '?') + 'user_id=' + encodeURIComponent(lsUserId || '');
        } else if (options.method.toUpperCase() === 'POST' && options.body instanceof URLSearchParams) {
            options.body.append('user_id', lsUserId || '');
        }
    }
    return originalFetch(url, options);
};
let allUsers = [];
let currentWorkspacePermissions = { can_manage: false };

function setActiveTab(tab, btn) {
    document.querySelectorAll('.ws-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ws-tab-content').forEach(c => c.style.display = 'none');
    const tabContent = document.getElementById('tab-' + tab);
    if (!tabContent) return;
    tabContent.style.display = 'block';
    if (btn) btn.classList.add('active');
}

function getActiveTab() {
    return document.querySelector('.ws-tab.active')?.dataset.tab || 'my';
}

async function reloadActiveTab() {
    const activeTab = getActiveTab();
    if (activeTab === 'all') return loadAllWorkspaces();
    if (activeTab === 'my') return loadWorkspaces();
    return Promise.resolve();
}

window.switchTab = async function(tab, btn) {
    setActiveTab(tab, btn);
    if (tab === 'my') loadWorkspaces();
    else if (tab === 'all') loadAllWorkspaces();
};

window.openModal = function(id) {
    document.getElementById(id)?.classList.add('show');
};
window.closeModal = function(id) {
    document.getElementById(id)?.classList.remove('show');
    if (id === 'boardModal') {
        const iframe = document.getElementById('boardIframe');
        if (iframe) iframe.src = 'about:blank';
    }
};

// Event delegation for all data-action buttons
document.addEventListener('click', function(e) {
    const target = e.target.closest('[data-action]');
    if (!target) return;
    
    const action = target.dataset.action;
    const id = target.dataset.id;
    const modalId = target.dataset.modal;
    
    if (action === 'switch-tab') {
        const tab = target.dataset.tab;
        setActiveTab(tab, target);
        if (tab === 'my') loadWorkspaces();
        else if (tab === 'all') loadAllWorkspaces();
    } else if (action === 'open-modal') {
        openModal(modalId);
    } else if (action === 'close-modal') {
        closeModal(modalId);
    } else if (action === 'open-board') {
        const name = target.dataset.name;
        document.getElementById('boardModalTitle').innerHTML = '<i class="fas fa-columns"></i> ' + escapeHtml(name);
        document.getElementById('boardIframe').src = 'workspace_kanban.php?id=' + id + '&name=' + encodeURIComponent(name);
        openModal('boardModal');
    } else if (action === 'open-members') {
        openMemberModal(id);
    } else if (action === 'edit-workspace') {
        editWorkspace(id, target.dataset.name, target.dataset.desc, target.dataset.color);
    } else if (action === 'delete-workspace') {
        deleteWorkspace(id);
    } else if (action === 'save-members') {
        saveMembers();
    } else if (action === 'toggle-chip') {
        const checkbox = target.querySelector('input');
        checkbox.checked = !checkbox.checked;
        target.classList.toggle('selected', checkbox.checked);
    } else if (action === 'remove-member') {
        removeMember(target.dataset.userId);
    } else if (action === 'update-privilege') {
        updatePrivilege(target.dataset.userId, target.value);
    }
}, { signal });

// Listen for messages from iframe
window.addEventListener('message', function(e) {
    if (e.data === 'close-board') {
        closeModal('boardModal');
        reloadActiveTab();
    }
}, { signal });

async function init() {
    const res = await fetch(API_URL + '?action=getCurrentUser');
    const data = await res.json();
    CURRENT_USER = data.data || { userid: 'guest', is_admin: false };
    loadWorkspaces();
}

async function loadWorkspaces() {
    const res = await fetch(API_URL + '?action=listWorkspaces');
    const data = await res.json();
    renderWorkspaces(data.data || [], 'myWorkspaces');
}

async function loadAllWorkspaces() {
    const res = await fetch(API_URL + '?action=listAllWorkspaces');
    const data = await res.json();
    renderWorkspaces(data.data || [], 'allWorkspaces');
}

function renderWorkspaces(workspaces, containerId) {
    const container = document.getElementById(containerId);
    if (!workspaces.length) {
        container.innerHTML = '<div class="ws-empty"><i class="fas fa-folder-open"></i><h3>No workspaces</h3></div>';
        return;
    }
    container.innerHTML = workspaces.map(w => `
        <div class="ws-card" style="border-left: 4px solid ${w.color || '#6b4fd6'}">
            <h3>${escapeHtml(w.name)}</h3>
            <p>${escapeHtml(w.description || '')}</p>
            <div class="ws-meta">
                <span><i class="fas fa-user"></i> ${Number(w.member_count ?? w.members ?? 0)} member${Number(w.member_count ?? w.members ?? 0) !== 1 ? 's' : ''}</span>
                <span><i class="fas fa-rectangle-list"></i> ${Number(w.task_count ?? w.tasks ?? 0)} cards</span>
            </div>
            <div class="ws-members-preview" id="members-preview-${w.id}" style="font-size: 9px; color: #888; margin: 5px 0;">
                <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>
            <div class="ws-actions">
                <button class="ws-btn ws-btn-primary" data-action="open-board" data-id="${w.id}" data-name="${escapeHtmlAttr(w.name)}">
                    <i class="fas fa-columns"></i> Board
                </button>
                <button class="ws-btn ws-btn-secondary" data-action="open-members" data-id="${w.id}"><i class="fas fa-users"></i> Members</button>
                <button class="ws-btn ws-btn-secondary" data-action="edit-workspace" data-id="${w.id}" data-name="${escapeHtmlAttr(w.name)}" data-desc="${escapeHtmlAttr(w.description || '')}" data-color="${w.color || '#6b4fd6'}"><i class="fas fa-edit"></i></button>
                ${w.can_manage ? `<button class="ws-btn ws-btn-danger" data-action="delete-workspace" data-id="${w.id}"><i class="fas fa-trash"></i></button>` : ''}
            </div>
        </div>
    `).join('');
    
    // Load member names for each workspace
    workspaces.forEach(w => loadMemberNames(w.id));
}

function escapeHtmlAttr(str) {
    return escapeHtml(str);
}

async function loadMemberNames(workspaceId) {
    const el = document.getElementById('members-preview-' + workspaceId);
    if (!el) return;
    try {
        const res = await fetch(API_URL + '?action=listWorkspaceMembers&workspace_id=' + workspaceId);
        const data = await res.json();
        const members = data.data || [];
        if (members.length === 0) {
            el.innerHTML = '<span style="color:#ccc;">No members yet</span>';
        } else {
            const names = members.slice(0, 4).map(m => m.full_name || m.user_id).join(', ');
            const more = members.length > 4 ? ` +${members.length - 4} more` : '';
            el.innerHTML = names + more;
        }
    } catch (e) {
        el.innerHTML = '<span style="color:#e74c3c;">Error loading</span>';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function showToastr(message, tone = 'info') {
    const wrap = document.getElementById('wsToastrWrap');
    if (!wrap || !message) return;
    const safeTone = ['info', 'success', 'warning', 'error'].includes(tone) ? tone : 'info';
    const toast = document.createElement('div');
    toast.className = 'ws-toastr ws-toastr-' + safeTone;
    toast.textContent = String(message);
    wrap.appendChild(toast);
    window.setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-4px)';
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        window.setTimeout(() => toast.remove(), 220);
    }, 3200);
}

window.editWorkspace = function(id, name, desc, color) {
    document.getElementById('workspaceId').value = id;
    document.getElementById('workspaceName').value = name;
    document.getElementById('workspaceDescription').value = desc;
    document.getElementById('workspaceColor').value = color;
    document.getElementById('workspaceModalTitle').textContent = 'Edit Workspace';
    openModal('workspaceModal');
};

window.deleteWorkspace = async function(id) {
    const now = Date.now();
    if (!(deleteConfirmState.id === String(id) && now < deleteConfirmState.expiresAt)) {
        deleteConfirmState = { id: String(id), expiresAt: now + 5000 };
        showToastr('Click delete again within 5 seconds to confirm.', 'warning');
        return;
    }
    deleteConfirmState = { id: null, expiresAt: 0 };
    const res = await fetch(API_URL, { method: 'POST', body: new URLSearchParams({ action: 'deleteWorkspace', id }) });
    const data = await res.json();
    if (data.ok) {
        showToastr('Workspace deleted.', 'success');
        reloadActiveTab();
    } else {
        showToastr(data.error || 'Delete failed.', 'error');
    }
};

window.openMemberModal = async function(workspaceId) {
    document.getElementById('memberWorkspaceId').value = workspaceId;

    const permissionsRes = await fetch(API_URL + '?action=getWorkspacePermissions&workspace_id=' + workspaceId);
    const permissionsData = await permissionsRes.json();
    currentWorkspacePermissions = permissionsData.data || { can_manage: false };
    
    const usersRes = await fetch(API_URL + '?action=listUsers');
    const usersData = await usersRes.json();
    allUsers = usersData.data || [];
    
    const membersRes = await fetch(API_URL + '?action=listWorkspaceMembers&workspace_id=' + workspaceId);
    const membersData = await membersRes.json();
    const currentMembers = membersData.data || [];
    const currentUserIds = currentMembers.map(m => m.user_id);
    
    document.getElementById('currentMembersList').innerHTML = currentMembers.length ? currentMembers.map(m => `
        <div class="ws-member-row" data-user-id="${m.user_id}" data-privilege="${m.privilege || 'member'}">
            <div class="ws-avatar-sm">${(m.full_name || m.user_id).charAt(0).toUpperCase()}</div>
            <div class="ws-member-name">${escapeHtml(m.full_name || m.user_id)}</div>
            <select class="ws-privilege-select" data-action="update-privilege" data-user-id="${m.user_id}">
                <option value="owner" ${m.privilege === 'owner' ? 'selected' : ''}>Owner</option>
                <option value="admin" ${m.privilege === 'admin' ? 'selected' : ''}>Admin</option>
                <option value="member" ${m.privilege === 'member' ? 'selected' : ''}>Member</option>
                <option value="viewer" ${m.privilege === 'viewer' ? 'selected' : ''}>Viewer</option>
            </select>
            ${currentWorkspacePermissions.can_manage ? `<button class="ws-remove-btn" data-action="remove-member" data-user-id="${m.user_id}"><i class="fas fa-trash"></i></button>` : ''}
        </div>
    `).join('') : '<div style="color: #999; padding: 10px;">No members yet</div>';
    
    document.getElementById('userSelectList').innerHTML = allUsers
        .filter(u => !currentUserIds.includes(String(u.id)))
        .map(u => `
            <label class="ws-user-chip" ${currentWorkspacePermissions.can_manage ? 'data-action="toggle-chip"' : ''}>
                <div class="ws-avatar">${(u.name || u.id).charAt(0).toUpperCase()}</div>
                <span>${escapeHtml(u.name || u.id)}</span>
                <input type="checkbox" class="ws-add-member" value="${u.id}" ${currentWorkspacePermissions.can_manage ? '' : 'disabled'}>
            </label>
        `).join('') || '<div style="color: #999; padding: 10px;">All users are already members</div>';

    document.querySelectorAll('#currentMembersList .ws-privilege-select').forEach(select => {
        select.disabled = !currentWorkspacePermissions.can_manage;
    });
    
    document.getElementById('membersModal').classList.add('show');
}

function removeMember(userId) {
    const row = document.querySelector(`.ws-member-row[data-user-id="${userId}"]`);
    if (row) {
        row.remove();
    }
    const user = allUsers.find(u => String(u.id) === String(userId));
    if (user) {
        const addList = document.getElementById('userSelectList');
        const chip = document.createElement('label');
        chip.className = 'ws-user-chip';
        chip.dataset.action = 'toggle-chip';
        chip.innerHTML = `
            <div class="ws-avatar">${(user.name || user.id).charAt(0).toUpperCase()}</div>
            <span>${escapeHtml(user.name || user.id)}</span>
            <input type="checkbox" class="ws-add-member" value="${user.id}">
        `;
        addList.insertBefore(chip, addList.firstChild);
    }
}

function updatePrivilege(userId, privilege) {
    const row = document.querySelector(`.ws-member-row[data-user-id="${userId}"]`);
    if (row) {
        row.dataset.privilege = privilege;
    }
}

async function saveMembers() {
    const workspaceId = document.getElementById('memberWorkspaceId').value;
    const memberMap = new Map();
    
    document.querySelectorAll('.ws-member-row').forEach(row => {
        memberMap.set(String(row.dataset.userId), {
            user_id: row.dataset.userId,
            privilege: row.dataset.privilege || 'member'
        });
    });
    
    document.querySelectorAll('#userSelectList input:checked').forEach(cb => {
        memberMap.set(String(cb.value), { user_id: cb.value, privilege: 'member' });
    });
    
    const res = await fetch(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'saveWorkspaceMembers', workspace_id: workspaceId, members: JSON.stringify(Array.from(memberMap.values())) })
    });
    const data = await res.json();
    if (data.ok) { closeModal('membersModal'); reloadActiveTab(); }
};

document.getElementById('workspaceForm').onsubmit = async function(e) {
    e.preventDefault();
    const fd = {
        action: 'saveWorkspace',
        id: document.getElementById('workspaceId').value,
        name: document.getElementById('workspaceName').value,
        description: document.getElementById('workspaceDescription').value,
        color: document.getElementById('workspaceColor').value
    };
    
    const res = await fetch(API_URL, { method: 'POST', body: new URLSearchParams(fd) });
    const data = await res.json();
    if (data.ok) { closeModal('workspaceModal'); reloadActiveTab(); }
};

init();
})();
</script>

</div>
</body>
</html>
