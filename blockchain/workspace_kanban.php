<?php
$PathPrefix = './';
include($PathPrefix . 'include/session.inc');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Workspace Board</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.kanban-app { font-family: 'Segoe UI', Arial, sans-serif; min-height: 100vh; padding: 12px; box-sizing: border-box; color: #172b4d; background: linear-gradient(135deg, #24516a 0%, #2d6a6f 44%, #4c6f4d 100%); }
.kanban-app *, .kanban-app *::before, .kanban-app *::after { box-sizing: border-box; }
.kanban-shell { max-width: 1600px; margin: 0 auto; }
.kanban-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; padding: 14px 18px; border-radius: 12px; color: #fff; background: rgba(9, 30, 66, 0.22); border: 1px solid rgba(255,255,255,0.16); box-shadow: 0 10px 20px rgba(9, 30, 66, 0.16); backdrop-filter: blur(14px); }
.kanban-header-main { min-width: 0; }
.kanban-eyebrow { margin-bottom: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.74); }
.kanban-header h1 { margin: 0; font-size: 24px; line-height: 1.05; font-weight: 800; color: #fff; }
.kanban-subtitle { margin-top: 4px; max-width: 760px; color: rgba(255,255,255,0.84); font-size: 13px; line-height: 1.4; }
.kanban-board-members { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.kanban-member-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 8px 4px 4px; border-radius: 999px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.12); color: #fff; font-size: 12px; }
.kanban-member-avatar, .kanban-task-avatar { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: #fff; background: linear-gradient(135deg, #0b3b5b, #2a6f97); flex-shrink: 0; }
.kanban-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.kanban-btn { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease; }
.kanban-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(9, 30, 66, 0.12); }
.kanban-btn-primary { background: linear-gradient(135deg, #2563eb, #0f766e); color: #fff; }
.kanban-btn-secondary { background: rgba(255,255,255,0.92); color: #17324d; }
.kanban-btn-secondary:hover { background: #fff; }
.kanban-notice { display: none; margin-bottom: 12px; padding: 10px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.kanban-notice.show { display: block; }
.kanban-notice.info { background: rgba(255,255,255,0.9); color: #2455a6; }
.kanban-notice.warn { background: #fff4dd; color: #8a6700; }
.kanban-filters { display: flex; gap: 10px; margin-bottom: 16px; padding: 10px 14px; border-radius: 12px; flex-wrap: wrap; align-items: center; background: rgba(248,250,252,0.92); box-shadow: 0 6px 14px rgba(9, 30, 66, 0.10); }
.kanban-filter-search { flex: 1 1 200px; min-width: 180px; }
.kanban-filter-group { display: flex; gap: 6px; align-items: center; }
.kanban-filter-label { font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #5e6c84; }
.kanban-filters input, .kanban-filters select, .kanban-form-group input, .kanban-form-group select, .kanban-form-group textarea { width: 100%; padding: 6px 10px; border: 1px solid #d0d7de; border-radius: 6px; font-size: 13px; background: #fff; }
.kanban-filters input:focus, .kanban-filters select:focus, .kanban-form-group input:focus, .kanban-form-group select:focus, .kanban-form-group textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.14); }
.kanban-board { display: flex; gap: 12px; overflow-x: auto; padding: 2px 2px 10px; align-items: flex-start; }
.kanban-col { min-width: 280px; width: 280px; max-width: 280px; max-height: calc(100vh - 160px); display: flex; flex-direction: column; border-radius: 12px; background: rgba(235, 240, 245, 0.96); box-shadow: 0 8px 16px rgba(9, 30, 66, 0.12); overflow: hidden; }
.kanban-col-header { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 12px; background: rgba(255,255,255,0.84); border-bottom: 1px solid #d9e2ec; }
.kanban-col-title { display: flex; align-items: center; gap: 8px; min-width: 0; }
.kanban-col-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.kanban-col-header h3 { margin: 0; font-size: 15px; font-weight: 800; color: #17324d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.kanban-col-header .kanban-count { background: #dbe5f0; color: #17324d; padding: 2px 6px; border-radius: 999px; font-size: 12px; font-weight: 800; }
.kanban-card-list { min-height: 40px; flex: 1; overflow-y: auto; padding: 10px; }
.kanban-empty { color: #7a869a; font-size: 13px; padding: 12px 6px; text-align: center; }
.kanban-task { width: 100%; border: 0; border-radius: 10px; padding: 10px; margin-bottom: 8px; box-shadow: 0 4px 10px rgba(9,30,66,0.08); cursor: pointer; text-align: left; background: #fff; transition: transform 0.18s ease, box-shadow 0.18s ease; }
.kanban-task:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(9,30,66,0.14); }
.kanban-task.dragging { opacity: 0.5; }
.kanban-task-title { font-size: 14px; font-weight: 700; color: #172b4d; margin-bottom: 6px; line-height: 1.3; }
.kanban-task-desc { font-size: 12px; color: #5e6c84; margin-bottom: 8px; line-height: 1.35; }
.kanban-task-meta { display: flex; gap: 6px; flex-wrap: wrap; font-size: 11px; color: #5e6c84; }
.kanban-task-meta span { display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 3px 6px; border-radius: 999px; font-weight: 700; }
.kanban-task-meta .p-low { background: #dcfce7; color: #166534; }
.kanban-task-meta .p-medium { background: #fef3c7; color: #92400e; }
.kanban-task-meta .p-high { background: #fee2e2; color: #b91c1c; }
.kanban-task-meta .p-urgent { background: linear-gradient(135deg, #7c3aed, #be123c); color: #fff; }
.kanban-task-members { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 8px; font-size: 12px; color: #5e6c84; }
.kanban-task-member-stack { display: flex; align-items: center; }
.kanban-task-avatar { width: 22px; height: 22px; margin-left: -6px; border: 2px solid #fff; font-size: 10px; }
.kanban-task-avatar:first-child { margin-left: 0; }
.kanban-task-members em { font-style: normal; font-weight: 700; color: #7a869a; }
.kanban-task-actions { display: flex; gap: 4px; margin-top: 6px; opacity: 0; transition: opacity 0.2s; }
.kanban-task:hover .kanban-task-actions { opacity: 1; }
.kanban-task-actions button { background: #eff4f9; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #5e6c84; }
.kanban-task-actions button:hover { background: #dbe7f3; }
.kanban-task-actions .btn-danger:hover { background: #fef2f2; color: #dc2626; }
.kanban-task-labels { display: flex; gap: 4px; margin-bottom: 6px; flex-wrap: wrap; }
.kanban-task-label { width: 40px; height: 8px; border-radius: 4px; }
.kanban-task-badges { display: flex; gap: 8px; margin-bottom: 6px; flex-wrap: wrap; }
.kanban-task-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #5e6c84; font-weight: 600; }
.kanban-task-badge i { font-size: 10px; }
.kanban-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; background: #fff; padding: 12px; border-radius: 10px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.kanban-view-toggle { display: flex; gap: 6px; }
.kanban-view-btn { padding: 6px 12px; border: none; background: #f1f5f9; color: #5e6c84; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: 6px; transition: all 0.2s; }
.kanban-view-btn:hover { background: #e2e8f0; }
.kanban-view-btn.active { background: #2563eb; color: #fff; }
.kanban-add-card { width: calc(100% - 20px); justify-content: center; margin: 0 10px 10px; }
.kanban-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15,23,42,0.42); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(6px); padding: 16px; }
.kanban-modal.show { display: flex; }
.kanban-modal-content { background: #fff; border-radius: 12px; padding: 18px; width: min(500px, 92vw); max-height: 90vh; overflow-y: auto; box-shadow: 0 16px 32px rgba(9,30,66,0.18); }
.kanban-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.kanban-modal-header h3 { margin: 0; color: #17324d; font-size: 18px; }
.kanban-modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280; padding: 0; line-height: 1; }
.kanban-modal-close:hover { color: #dc2626; }
.kanban-form-group { margin-bottom: 12px; }
.kanban-form-group label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #5e6c84; margin-bottom: 4px; }
.kanban-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.kanban-modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
.detail-modal { position: fixed; inset: 0; z-index: 1100; display: none; align-items: center; justify-content: center; padding: 12px; background: rgba(15,23,42,0.48); backdrop-filter: blur(8px); }
.detail-modal.show { display: flex; }
.detail-shell { width: min(1000px, 96vw); max-height: 94vh; overflow: hidden; border-radius: 16px; background: #f3f7fb; box-shadow: 0 24px 48px rgba(9,30,66,0.28); }
.detail-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 18px; color: #fff; background: linear-gradient(135deg, #12324b, #1b5e6f); }
.detail-header h3 { margin: 0; color: #fff; font-size: 18px; }
.detail-close { border: none; background: transparent; color: #fff; cursor: pointer; font-size: 24px; line-height: 1; }
.detail-loading, .detail-empty { padding: 20px; text-align: center; color: #5e6c84; font-size: 13px; }
.detail-body { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.9fr); max-height: calc(94vh - 64px); overflow-y: auto; }
.detail-main, .detail-side { overflow-y: auto; padding: 16px; }
.detail-main { border-right: 1px solid #dbe3ec; }
.detail-side { background: #fff; }
.detail-panel { margin-bottom: 12px; padding: 14px; border-radius: 12px; background: #fff; box-shadow: 0 6px 12px rgba(9,30,66,0.06); }
.detail-panel:last-child { margin-bottom: 0; }
.detail-panel-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 10px; }
.detail-panel-head h4 { margin: 0; font-size: 14px; color: #17324d; }
.detail-panel input, .detail-panel select, .detail-panel textarea { width: 100%; padding: 8px 10px; border: 1px solid #d0d7de; border-radius: 8px; font-size: 12px; background: #fff; }
.detail-panel input:focus, .detail-panel select:focus, .detail-panel textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.14); }
.detail-label { display: block; margin-bottom: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #5e6c84; }
.detail-title { width: 100%; margin-bottom: 10px; padding: 0; border: 0; outline: none; font-size: 20px; line-height: 1.1; font-weight: 800; color: #172b4d; background: transparent; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.detail-stack, .detail-comments, .detail-activity, .detail-label-list { display: grid; gap: 8px; }
.detail-pill-list, .detail-member-list { display: flex; flex-wrap: wrap; gap: 6px; }
.detail-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 8px; border-radius: 999px; background: #eef3f8; color: #17324d; font-size: 12px; font-weight: 700; }
.detail-swatch { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.detail-member { position: relative; }
.detail-member input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.detail-member-chip { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 4px 10px 4px 4px; border-radius: 999px; border: 1px solid #d0d7de; background: #fff; color: #17324d; font-size: 12px; font-weight: 700; }
.detail-member input:checked + .detail-member-chip { border-color: #2563eb; color: #fff; background: linear-gradient(135deg, #2563eb, #0f766e); }
.detail-member input:checked + .detail-member-chip .kanban-member-avatar { background: #fff; color: #2563eb; }
.detail-toggle { display: flex; justify-content: space-between; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: 1px solid #d0d7de; border-radius: 8px; background: #fff; cursor: pointer; text-align: left; }
.detail-toggle.active { border-color: #2563eb; background: #eef4ff; }
.detail-comment { display: flex; gap: 10px; align-items: flex-start; }
.detail-comment-body, .detail-activity-item { flex: 1; padding: 10px 12px; border-radius: 10px; background: #f8fbfd; border: 1px solid #e2e8f0; }
.detail-comment-meta, .detail-activity-meta { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 4px; font-size: 12px; color: #5e6c84; }
.detail-text { font-size: 14px; line-height: 1.4; color: #17324d; }
.detail-checklist { padding: 12px; border-radius: 12px; background: #f8fbfd; border: 1px solid #dbe3ec; }
.detail-progress { height: 6px; margin: 8px 0 10px; border-radius: 999px; overflow: hidden; background: #dbe7f0; }
.detail-progress span { display: block; height: 100%; background: linear-gradient(90deg, #2563eb, #0f766e); }
.detail-check-item { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-top: 1px solid #e5ebf1; }
.detail-check-item:first-child { border-top: 0; }
.detail-check-item label { flex: 1; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #17324d; }
.detail-check-item.done span { color: #7a869a; text-decoration: line-through; }
.detail-inline { display: flex; gap: 6px; margin-top: 8px; }
.detail-inline input { flex: 1; }
.detail-mini { border: none; border-radius: 6px; padding: 6px 8px; cursor: pointer; font-size: 12px; font-weight: 700; color: #17324d; background: #e8f0f8; }
.detail-mini:hover { background: #dbe7f3; }
.detail-mini.danger:hover { background: #fef2f2; color: #dc2626; }
@media (max-width: 1080px) { .detail-body { grid-template-columns: 1fr; } .detail-main { border-right: 0; border-bottom: 1px solid #dbe3ec; } }
@media (max-width: 760px) {
    .kanban-app { padding: 12px; }
    .kanban-header { padding: 14px; }
    .kanban-col { min-width: 90vw; width: 90vw; max-width: 90vw; }
    .kanban-form-row { grid-template-columns: 1fr; }
    .detail-grid { grid-template-columns: 1fr; }
    .detail-inline { flex-direction: column; }
}
.ws-user-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; }
.ws-user-chip { display: flex; flex-direction: column; align-items: center; padding: 8px; background: #f8fafc; border-radius: 8px; cursor: pointer; transition: all 0.2s; border: 2px solid transparent; }
.ws-user-chip:hover { background: #e2e8f0; }
.ws-user-chip.selected { background: #3b82f6; border-color: #3b82f6; color: #fff; }
.ws-user-chip.selected .ws-avatar { background: #fff; color: #3b82f6; }
.ws-user-chip.selected span { color: #fff; }
.ws-user-chip .ws-avatar { width: 32px; height: 32px; border-radius: 50%; background: #0f766e; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
.ws-user-chip span { font-size: 11px; color: #5e6c84; text-align: center; }
.ws-user-chip input { display: none; }
.ws-member-row { display: flex; align-items: center; padding: 6px 8px; background: #f8fafc; border-radius: 6px; margin-bottom: 6px; }
.ws-member-row .ws-avatar-sm { width: 26px; height: 26px; border-radius: 50%; background: #0f766e; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 8px; }
.ws-member-row .ws-member-name { flex: 1; font-size: 13px; }
.ws-member-row select { padding: 4px 6px; border: 1px solid #d0d7de; border-radius: 4px; font-size: 12px; margin-right: 6px; }
.ws-member-row .ws-remove-btn { background: #fef2f2; color: #dc2626; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; font-size: 12px; }
.ws-member-row .ws-remove-btn:hover { background: #dc2626; color: #fff; }
</style>
</head>
<body>
<div class="kanban-app">
<div class="kanban-shell">
<div class="kanban-header">
    <div class="kanban-header-main">
        <div class="kanban-eyebrow">Workspace Board</div>
        <h1><i class="fas fa-columns"></i> <span id="workspaceName">Workspace</span></h1>
        <div class="kanban-subtitle" id="boardMeta">Loading board...</div>
        <div id="boardMembers" class="kanban-board-members"></div>
    </div>
<div class="kanban-actions">
        <button class="kanban-btn kanban-btn-secondary" id="backBtn">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <button class="kanban-btn kanban-btn-secondary" id="manageMembersBtn" data-action="open-members-modal" style="display:none;">
            <i class="fas fa-users"></i> Members
        </button>
        <button class="kanban-btn kanban-btn-primary" id="newCardBtn" data-action="new-card" style="display:none;">
            <i class="fas fa-plus"></i> New Card
        </button>
    </div>
</div>

<div id="boardNotice" class="kanban-notice info"></div>

<div class="kanban-header">
    <div class="kanban-filters">
        <div class="kanban-filter-search">
            <div class="kanban-filter-label">Search</div>
            <input type="search" id="searchFilter" placeholder="Search cards, descriptions, or people">
        </div>
        <div class="kanban-filter-group">
            <span class="kanban-filter-label">Priority</span>
            <select id="priorityFilter">
                <option value="">All Priorities</option>
                <option value="1">Low</option>
                <option value="2">Medium</option>
                <option value="3">High</option>
                <option value="4">Urgent</option>
            </select>
        </div>
        <div class="kanban-filter-group">
            <span class="kanban-filter-label">Member</span>
            <select id="assigneeFilter">
                <option value="">All Members</option>
            </select>
        </div>
    </div>
    <div class="kanban-view-toggle">
        <button class="kanban-view-btn active" data-view="tasks"><i class="fas fa-tasks"></i> Tasks</button>
        <button class="kanban-view-btn" data-view="field-activities"><i class="fas fa-clipboard-list"></i> Field Activities</button>
    </div>
</div>

<div id="kanbanBoard" class="kanban-board"></div>

<div id="cardModal" class="kanban-modal">
    <div class="kanban-modal-content">
        <div class="kanban-modal-header">
            <h3 id="cardModalTitle">New Card</h3>
            <button class="kanban-modal-close" data-action="close-card-modal">&times;</button>
        </div>
        <form id="cardForm">
            <input type="hidden" id="cardId" value="0">
            <div class="kanban-form-group">
                <label>Title *</label>
                <input type="text" id="cardTitle" required>
            </div>
            <div class="kanban-form-group">
                <label>Description</label>
                <textarea id="cardDescription" rows="3"></textarea>
            </div>
            <div class="kanban-form-group">
                <label>List</label>
                <select id="cardListId"></select>
            </div>
            <div class="kanban-form-row">
                <div class="kanban-form-group">
                    <label>Priority</label>
                    <select id="cardPriority">
                        <option value="1">Low</option>
                        <option value="2" selected>Medium</option>
                        <option value="3">High</option>
                        <option value="4">Urgent</option>
                    </select>
                </div>
                <div class="kanban-form-group">
                    <label>Due Date</label>
                    <input type="datetime-local" id="cardDueDate">
                </div>
            </div>
            <div class="kanban-form-group">
                <label>Assigned To</label>
                <select id="cardAssignedTo">
                    <option value="">-- Select --</option>
                </select>
            </div>
            <div class="kanban-modal-footer">
                <button type="button" class="kanban-btn kanban-btn-secondary" data-action="close-card-modal">Cancel</button>
                <button type="submit" class="kanban-btn kanban-btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="detailModal" class="detail-modal">
    <div class="detail-shell">
        <div class="detail-header">
            <div>
                <div class="kanban-eyebrow" style="margin-bottom:4px;color:rgba(255,255,255,0.7);">Card Detail</div>
                <h3 id="detailHeading">Card</h3>
            </div>
            <button class="detail-close" type="button" data-action="close-detail">&times;</button>
        </div>
        <div id="detailLoading" class="detail-loading">Loading card...</div>
        <div id="detailBody" class="detail-body" style="display:none;">
            <div class="detail-main">
                <div class="detail-panel">
                    <label class="detail-label" for="detailTitle">Title</label>
                    <input id="detailTitle" class="detail-title" type="text" placeholder="Card title">
                    <div class="detail-grid">
                        <div>
                            <label class="detail-label" for="detailList">List</label>
                            <select id="detailList"></select>
                        </div>
                        <div>
                            <label class="detail-label" for="detailPriority">Priority</label>
                            <select id="detailPriority">
                                <option value="1">Low</option>
                                <option value="2">Medium</option>
                                <option value="3">High</option>
                                <option value="4">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="detail-grid" style="margin-top:12px;">
                        <div>
                            <label class="detail-label" for="detailDueDate">Due Date</label>
                            <input id="detailDueDate" type="datetime-local">
                        </div>
                        <div>
                            <label class="detail-label">Updated</label>
                            <div id="detailUpdated" class="detail-pill">-</div>
                        </div>
                    </div>
                </div>

                <div class="detail-panel">
                    <div class="detail-panel-head">
                        <h4>Description</h4>
                        <button id="detailSaveBtn" class="kanban-btn kanban-btn-primary" type="button"><i class="fas fa-save"></i> Save</button>
                    </div>
                    <textarea id="detailDescription" rows="8" placeholder="Add a fuller description for this card"></textarea>
                </div>

                <div class="detail-panel">
                    <div class="detail-panel-head">
                        <h4>Checklists</h4>
                        <span id="detailChecklistSummary" class="kanban-filter-label">No checklist yet</span>
                    </div>
                    <div id="detailChecklists" class="detail-stack"></div>
                    <div class="detail-inline">
                        <input id="detailNewChecklist" type="text" placeholder="New checklist title">
                        <button id="detailAddChecklistBtn" class="kanban-btn kanban-btn-secondary" type="button"><i class="fas fa-list-check"></i> Add Checklist</button>
                    </div>
                </div>

                <div class="detail-panel">
                    <div class="detail-panel-head">
                        <h4>Comments</h4>
                        <span class="kanban-filter-label">Conversation stays with the card</span>
                    </div>
                    <div class="detail-stack" style="margin-bottom:12px;">
                        <textarea id="detailNewComment" rows="4" placeholder="Write a comment"></textarea>
                        <div>
                            <button id="detailAddCommentBtn" class="kanban-btn kanban-btn-primary" type="button"><i class="fas fa-paper-plane"></i> Post Comment</button>
                        </div>
                    </div>
                    <div id="detailComments" class="detail-comments"></div>
                </div>

                <div class="detail-panel">
                    <div class="detail-panel-head">
                        <h4>Activity</h4>
                        <span class="kanban-filter-label">Latest changes on the card</span>
                    </div>
                    <div id="detailActivity" class="detail-activity"></div>
                </div>
            </div>

            <div class="detail-side">
                <div class="detail-panel">
                    <div class="detail-panel-head">
                        <h4>Members</h4>
                        <span class="kanban-filter-label">Assignees</span>
                    </div>
                    <div id="detailMembers" class="detail-member-list"></div>
                </div>

                <div class="detail-panel">
                    <div class="detail-panel-head">
                        <h4>Labels</h4>
                        <span class="kanban-filter-label">Board labels</span>
                    </div>
                    <div id="detailCurrentLabels" class="detail-pill-list" style="margin-bottom:12px;"></div>
                    <div id="detailLabelList" class="detail-label-list"></div>
                    <div class="detail-inline" style="margin-top:12px;">
                        <input id="detailNewLabelName" type="text" placeholder="New label name">
                        <input id="detailNewLabelColor" type="color" value="#2563eb" style="width:56px;min-width:56px;padding:6px;">
                    </div>
                    <div style="margin-top:10px;">
                        <button id="detailAddLabelBtn" class="kanban-btn kanban-btn-secondary" type="button"><i class="fas fa-tag"></i> Create Label</button>
                    </div>
                </div>

                <div class="detail-panel">
                    <div class="detail-panel-head">
                        <h4>Card Actions</h4>
                        <span class="kanban-filter-label">Build 3</span>
                    </div>
                    <div class="detail-stack">
                        <button id="detailArchiveBtn" class="kanban-btn kanban-btn-secondary" type="button"><i class="fas fa-box-archive"></i> Archive Card</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Members Modal -->
<div id="membersModal" class="kanban-modal">
    <div class="kanban-modal-content" style="width: 500px;">
        <div class="kanban-modal-header">
            <h3>Workspace Members</h3>
            <button class="kanban-modal-close" data-action="close-members-modal">&times;</button>
        </div>
        
        <div class="kanban-form-group">
            <label style="font-weight: 600; margin-bottom: 10px; display: block;">Current Members</label>
            <div id="currentMembersList" style="max-height: 200px; overflow-y: auto; margin-bottom: 20px;"></div>
        </div>
        
        <div class="kanban-form-group">
            <label style="font-weight: 600; margin-bottom: 10px; display: block;">Add More Users</label>
            <div id="userSelectList" class="ws-user-grid" style="max-height: 200px; overflow-y: auto;"></div>
        </div>
        
        <div class="kanban-modal-footer">
            <button type="button" class="kanban-btn kanban-btn-secondary" data-action="close-members-modal">Close</button>
            <button type="button" class="kanban-btn kanban-btn-primary" data-action="save-members">Save Changes</button>
        </div>
    </div>
</div>

<script>
(function() {
window._kanbanPageController?.abort();
const pageController = new AbortController();
const { signal } = pageController;
window._kanbanPageController = pageController;

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

const PRIORITIES = { 1: 'Low', 2: 'Medium', 3: 'High', 4: 'Urgent' };
const P_CLASSES = { 1: 'p-low', 2: 'p-medium', 3: 'p-high', 4: 'p-urgent' };
const ACTIVITY_LABELS = {
    comment: 'Commented',
    checklist_added: 'Added checklist',
    checklist_item_done: 'Completed checklist item',
    checklist_item_undone: 'Unchecked checklist item',
    label_added: 'Added label',
    description_changed: 'Updated description'
};

const urlParams = new URLSearchParams(window.location.search);
const WORKSPACE_ID = parseInt(urlParams.get('id')) || 0;
const WS_NAME = urlParams.get('name') || 'Workspace';
document.getElementById('workspaceName').textContent = decodeURIComponent(WS_NAME);

let boardData = null;
let lists = [];
let cards = [];
let members = [];
let allUsers = [];
let permissions = { can_manage: false, can_edit: false };
let legacyMode = true;
let currentDetailCardId = 0;
let currentDetailCard = null;

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function truncateText(str, limit = 110) {
    const value = String(str || '').trim();
    return value.length > limit ? value.slice(0, limit - 3) + '...' : value;
}

function getInitials(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    return parts.slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('');
}

function setNotice(message, tone = 'info') {
    const el = document.getElementById('boardNotice');
    if (!message) {
        el.className = 'kanban-notice info';
        el.textContent = '';
        return;
    }
    el.className = 'kanban-notice show ' + tone;
    el.textContent = message;
}

function formatDateTime(value, includeTime = true) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return includeTime
        ? date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })
        : date.toLocaleDateString([], { dateStyle: 'medium' });
}

function formatDateInput(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value).slice(0, 16);
    const shifted = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
    return shifted.toISOString().slice(0, 16);
}

function getCardAssigneeId(card) {
    return Array.isArray(card.member_ids) && card.member_ids.length ? card.member_ids[0] : '';
}

function populateMemberOptions() {
    const memberOptions = members.map(m => `<option value="${escapeHtml(m.id)}">${escapeHtml(m.name)}</option>`).join('');
    document.getElementById('cardAssignedTo').innerHTML = '<option value="">-- Select --</option>' + memberOptions;
    document.getElementById('assigneeFilter').innerHTML = '<option value="">All Members</option>' + memberOptions;
}

function populateListOptions(selectedId) {
    document.getElementById('cardListId').innerHTML = lists.map(list => `<option value="${escapeHtml(list.id)}" ${String(list.id) === String(selectedId) ? 'selected' : ''}>${escapeHtml(list.name)}</option>`).join('');
}

function renderBoardMembers() {
    const container = document.getElementById('boardMembers');
    if (!members.length) {
        container.innerHTML = '';
        return;
    }
    container.innerHTML = members.slice(0, 8).map(member => `
        <span class="kanban-member-pill" title="${escapeHtml(member.name)}">
            <span class="kanban-member-avatar">${escapeHtml(getInitials(member.name))}</span>
            <span>${escapeHtml(member.name)}</span>
        </span>
    `).join('');
}

function renderControls() {
    document.getElementById('boardMeta').textContent = legacyMode
        ? 'Legacy mode: fixed task statuses are still being shown for this workspace.'
        : 'Board -> List -> Card model active on ' + (boardData?.board?.name || 'Main Board') + '. Drag cards across lists and use the board filters to focus the work.';
    document.getElementById('newCardBtn').style.display = permissions.can_edit ? 'inline-flex' : 'none';
    document.getElementById('manageMembersBtn').style.display = permissions.can_manage ? 'inline-flex' : 'none';
    renderBoardMembers();
}

function renderCard(card) {
    const pClass = P_CLASSES[card.priority] || 'p-medium';
    const memberNames = Array.isArray(card.member_names) ? card.member_names : [];
    const avatars = memberNames.slice(0, 3).map(name => `
        <span class="kanban-task-avatar" title="${escapeHtml(name)}">${escapeHtml(getInitials(name))}</span>
    `).join('');

    const labels = Array.isArray(card.labels) ? card.labels : [];
    const labelBadges = labels.slice(0, 4).map(label => `
        <span class="kanban-task-label" style="background:${escapeHtml(label.color || '#2563eb')}"></span>
    `).join('');
    
    const checklists = Array.isArray(card.checklists) ? card.checklists : [];
    let checklistBadge = '';
    if (checklists.length > 0) {
        let total = 0, done = 0;
        checklists.forEach(cl => {
            if (Array.isArray(cl.items)) {
                total += cl.items.length;
                done += cl.items.filter(i => i.done).length;
            }
        });
        if (total > 0) {
            const pct = Math.round((done / total) * 100);
            checklistBadge = `<span class="kanban-task-badge"><i class="fas fa-check-square"></i> ${done}/${total}</span>`;
        }
    }
    
    const attachments = Array.isArray(card.attachments) ? card.attachments : [];
    const attachmentBadge = attachments.length > 0 ? `<span class="kanban-task-badge"><i class="fas fa-paperclip"></i> ${attachments.length}</span>` : '';
    
    const comments = Array.isArray(card.comments) ? card.comments : [];
    const commentBadge = comments.length > 0 ? `<span class="kanban-task-badge"><i class="fas fa-comment"></i> ${comments.length}</span>` : '';

    const isEditable = permissions.can_edit;
    return `
        <div class="kanban-task ${isEditable ? '' : 'no-drag'}" draggable="${isEditable ? 'true' : 'false'}" data-id="${card.id}" data-action="open-card" style="cursor:${isEditable ? 'grab' : 'not-allowed'}">
            ${labelBadges ? `<div class="kanban-task-labels">${labelBadges}</div>` : ''}
            <div class="kanban-task-title">${escapeHtml(card.title)}</div>
            ${card.description ? `<div class="kanban-task-desc">${escapeHtml(truncateText(card.description))}</div>` : ''}
            ${checklistBadge || attachmentBadge || commentBadge ? `<div class="kanban-task-badges">${checklistBadge}${attachmentBadge}${commentBadge}</div>` : ''}
            <div class="kanban-task-meta">
                <span class="${pClass}"><i class="fas fa-flag"></i> ${PRIORITIES[card.priority] || 'Medium'}</span>
                ${card.due_date ? `<span><i class="fas fa-calendar-day"></i> ${new Date(card.due_date).toLocaleDateString()}</span>` : ''}
            </div>
            <div class="kanban-task-members">
                <div class="kanban-task-member-stack">${avatars || '<em>Unassigned</em>'}</div>
                <span>${memberNames.length ? escapeHtml(memberNames.join(', ')) : 'Open card'}</span>
            </div>
            ${(isEditable || card.can_delete) ? `
            <div class="kanban-task-actions">
                ${isEditable ? `<button data-action="edit-card" data-id="${card.id}"><i class="fas fa-edit"></i></button>` : ''}
                ${card.can_delete ? `<button data-action="delete-card" data-id="${card.id}" class="btn-danger"><i class="fas fa-trash"></i></button>` : ''}
            </div>` : ''}
        </div>
    `;
}

function renderBoard() {
    const board = document.getElementById('kanbanBoard');
    const priority = document.getElementById('priorityFilter').value;
    const assignee = document.getElementById('assigneeFilter').value;
    const searchTerm = document.getElementById('searchFilter').value.trim().toLowerCase();
    let filtered = cards.slice();
    if (priority) filtered = filtered.filter(card => String(card.priority) === String(priority));
    if (assignee) filtered = filtered.filter(card => Array.isArray(card.member_ids) && card.member_ids.includes(assignee));
    if (searchTerm) {
        filtered = filtered.filter(card => {
            const haystack = [card.title, card.description, ...(card.member_names || [])].join(' ').toLowerCase();
            return haystack.includes(searchTerm);
        });
    }
    board.innerHTML = lists.map(list => {
        const listCards = filtered.filter(card => String(card.list_id) === String(list.id));
        return `
            <div class="kanban-col" data-list-id="${list.id}">
                <div class="kanban-col-header">
                    <div class="kanban-col-title">
                        <span class="kanban-col-dot" style="background:${escapeHtml(list.color || '#6b4fd6')}"></span>
                        <h3>${escapeHtml(list.name)}</h3>
                    </div>
                    <span class="kanban-count">${listCards.length}</span>
                </div>
                <div class="kanban-card-list">
                    ${listCards.length ? listCards.map(renderCard).join('') : '<div class="kanban-empty">No cards yet</div>'}
                </div>
                ${permissions.can_edit ? `<button class="kanban-btn kanban-btn-secondary kanban-add-card" data-action="new-card" data-list-id="${list.id}"><i class="fas fa-plus"></i> Add Card</button>` : ''}
            </div>
        `;
    }).join('');
}

function openCardModal(card = null, listId = '') {
    document.getElementById('cardForm').reset();
    document.getElementById('cardId').value = card?.id || 0;
    document.getElementById('cardTitle').value = card?.title || '';
    document.getElementById('cardDescription').value = card?.description || '';
    document.getElementById('cardPriority').value = card?.priority || 2;
    document.getElementById('cardDueDate').value = card?.due_date ? String(card.due_date).slice(0, 16) : '';
    document.getElementById('cardAssignedTo').value = card ? getCardAssigneeId(card) : '';
    populateListOptions(card?.list_id || listId || lists[0]?.id || '');
    document.getElementById('cardModalTitle').textContent = card ? 'Edit Card' : 'New Card';
    document.getElementById('cardModal').classList.add('show');
}

function closeCardModal() {
    document.getElementById('cardModal').classList.remove('show');
}

function closeDetailModal() {
    currentDetailCardId = 0;
    currentDetailCard = null;
    document.getElementById('detailModal').classList.remove('show');
}

function showDetailLoading(message = 'Loading card...') {
    document.getElementById('detailModal').classList.add('show');
    document.getElementById('detailLoading').style.display = 'block';
    document.getElementById('detailLoading').textContent = message;
    document.getElementById('detailBody').style.display = 'none';
}

function renderDetailLabels(card) {
    const currentLabels = Array.isArray(card.labels) ? card.labels : [];
    const activeIds = new Set(currentLabels.map(label => String(label.id)));
    document.getElementById('detailCurrentLabels').innerHTML = currentLabels.length
        ? currentLabels.map(label => `<span class="detail-pill"><span class="detail-swatch" style="background:${escapeHtml(label.color || '#2563eb')}"></span>${escapeHtml(label.name)}</span>`).join('')
        : '<div class="detail-empty" style="padding:10px 0;">No labels on this card yet.</div>';
    const boardLabels = Array.isArray(card.board_labels) ? card.board_labels : [];
    document.getElementById('detailLabelList').innerHTML = boardLabels.length
        ? boardLabels.map(label => `
            <button type="button" class="detail-toggle ${activeIds.has(String(label.id)) ? 'active' : ''}" data-action="toggle-label" data-id="${label.id}">
                <span class="detail-pill" style="padding:0;background:none;">
                    <span class="detail-swatch" style="background:${escapeHtml(label.color || '#2563eb')}"></span>
                    ${escapeHtml(label.name)}
                </span>
                <span>${activeIds.has(String(label.id)) ? '<i class="fas fa-check"></i>' : '<i class="fas fa-plus"></i>'}</span>
            </button>
        `).join('')
        : '<div class="detail-empty" style="padding:10px 0;">No board labels yet.</div>';
}

function renderDetailMembers(card) {
    const activeIds = new Set((card.members || []).map(member => String(member.user_id)));
    document.getElementById('detailMembers').innerHTML = members.length
        ? members.map(member => `
            <label class="detail-member">
                <input type="checkbox" value="${escapeHtml(member.id)}" ${activeIds.has(String(member.id)) ? 'checked' : ''} ${card.can_edit ? '' : 'disabled'}>
                <span class="detail-member-chip">
                    <span class="kanban-member-avatar">${escapeHtml(getInitials(member.name))}</span>
                    <span>${escapeHtml(member.name)}</span>
                </span>
            </label>
        `).join('')
        : '<div class="detail-empty" style="padding:10px 0;">No workspace members available.</div>';
}

function renderDetailChecklists(card) {
    const checklists = Array.isArray(card.checklists) ? card.checklists : [];
    let totalItems = 0;
    let doneItems = 0;

    document.getElementById('detailChecklists').innerHTML = checklists.length
        ? checklists.map(checklist => {
            const items = Array.isArray(checklist.items) ? checklist.items : [];
            const doneCount = items.filter(item => Number(item.is_done) === 1).length;
            const progress = items.length ? Math.round((doneCount / items.length) * 100) : 0;
            totalItems += items.length;
            doneItems += doneCount;
            return `
                <div class="detail-checklist">
                    <div class="detail-panel-head">
                        <div>
                            <h4 style="margin:0 0 4px;">${escapeHtml(checklist.title)}</h4>
                            <div class="kanban-filter-label">${doneCount}/${items.length} complete</div>
                        </div>
                        ${card.can_edit ? `<button class="detail-mini danger" type="button" data-action="delete-checklist" data-id="${checklist.id}"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                    <div class="detail-progress"><span style="width:${progress}%"></span></div>
                    <div>
                        ${items.length ? items.map(item => `
                            <div class="detail-check-item ${Number(item.is_done) === 1 ? 'done' : ''}">
                                <label>
                                    <input type="checkbox" data-action="toggle-check-item" data-id="${item.id}" ${Number(item.is_done) === 1 ? 'checked' : ''} ${card.can_edit ? '' : 'disabled'}>
                                    <span>${escapeHtml(item.title)}</span>
                                </label>
                                ${card.can_edit ? `<button class="detail-mini danger" type="button" data-action="delete-check-item" data-id="${item.id}"><i class="fas fa-times"></i></button>` : '' }
                            </div>
                        `).join('') : '<div class="detail-empty" style="padding:8px 0;">No checklist items yet.</div>'}
                    </div>
                    ${card.can_edit ? `
                        <div class="detail-inline">
                            <input type="text" data-checklist-input="${checklist.id}" placeholder="Add checklist item">
                            <button class="detail-mini" type="button" data-action="add-check-item" data-id="${checklist.id}"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('')
        : '<div class="detail-empty">No checklists yet.</div>';

    document.getElementById('detailChecklistSummary').textContent = checklists.length ? `${doneItems}/${totalItems} items complete` : 'No checklist yet';
}

function renderDetailComments(card) {
    const comments = Array.isArray(card.comments) ? card.comments : [];
    document.getElementById('detailComments').innerHTML = comments.length
        ? comments.map(comment => `
            <div class="detail-comment">
                <span class="kanban-member-avatar">${escapeHtml(getInitials(comment.full_name || comment.user_id))}</span>
                <div class="detail-comment-body">
                    <div class="detail-comment-meta">
                        <strong>${escapeHtml(comment.full_name || comment.user_id)}</strong>
                        <span>${escapeHtml(formatDateTime(comment.created_at))}</span>
                    </div>
                    <div class="detail-text">${escapeHtml(comment.comment)}</div>
                </div>
            </div>
        `).join('')
        : '<div class="detail-empty">No comments yet.</div>';
}

function renderDetailActivity(card) {
    const activity = Array.isArray(card.activity) ? card.activity : [];
    document.getElementById('detailActivity').innerHTML = activity.length
        ? activity.map(item => `
            <div class="detail-activity-item">
                <div class="detail-activity-meta">
                    <strong>${escapeHtml(item.full_name || item.user_id)}</strong>
                    <span>${escapeHtml(formatDateTime(item.created_at))}</span>
                </div>
                <div class="detail-text"><strong>${escapeHtml(ACTIVITY_LABELS[item.action_type] || item.action_type)}</strong>${item.details ? ': ' + escapeHtml(item.details) : ''}</div>
            </div>
        `).join('')
        : '<div class="detail-empty">No activity yet.</div>';
}

function renderDetailCard(card) {
    currentDetailCard = card;
    currentDetailCardId = Number(card.id);

    document.getElementById('detailHeading').textContent = card.title || 'Card';
    document.getElementById('detailTitle').value = card.title || '';
    document.getElementById('detailDescription').value = card.description || '';
    document.getElementById('detailPriority').value = card.priority || 2;
    document.getElementById('detailDueDate').value = formatDateInput(card.due_date || '');
    document.getElementById('detailUpdated').textContent = card.updated_at ? formatDateTime(card.updated_at) : 'Just now';
    document.getElementById('detailList').innerHTML = (card.lists || []).map(list => `<option value="${list.id}" ${String(list.id) === String(card.list_id) ? 'selected' : ''}>${escapeHtml(list.name)}</option>`).join('');
    document.getElementById('detailArchiveBtn').innerHTML = card.archived
        ? '<i class="fas fa-box-open"></i> Restore Card'
        : '<i class="fas fa-box-archive"></i> Archive Card';

    renderDetailMembers(card);
    renderDetailLabels(card);
    renderDetailChecklists(card);
    renderDetailComments(card);
    renderDetailActivity(card);

    const readOnly = !card.can_edit;
    ['detailTitle', 'detailDescription', 'detailPriority', 'detailDueDate', 'detailList', 'detailNewChecklist', 'detailNewComment', 'detailNewLabelName', 'detailNewLabelColor']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = readOnly;
        });
    ['detailSaveBtn', 'detailAddChecklistBtn', 'detailAddCommentBtn', 'detailAddLabelBtn', 'detailArchiveBtn']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = readOnly;
        });

    document.getElementById('detailLoading').style.display = 'none';
    document.getElementById('detailBody').style.display = 'grid';
}

async function fetchJson(url, options) {
    const res = await fetch(url, options);
    return res.json();
}

async function openDetailModal(cardId) {
    if (legacyMode) {
        openCardModal(cards.find(card => String(card.id) === String(cardId)) || null);
        return;
    }
    showDetailLoading();
    const data = await fetchJson(API_URL + '?action=getCardDetail&card_id=' + cardId);
    if (!data.data) {
        document.getElementById('detailLoading').textContent = data.error || 'Unable to load card detail.';
        return;
    }
    renderDetailCard(data.data);
}

async function saveDetailCard() {
    if (!currentDetailCard?.can_edit || !currentDetailCardId) return;
    const memberIds = Array.from(document.querySelectorAll('#detailMembers input:checked')).map(input => input.value);
    const payload = new URLSearchParams({
        action: 'saveWorkspaceCard',
        id: String(currentDetailCardId),
        workspace_id: String(WORKSPACE_ID),
        list_id: document.getElementById('detailList').value,
        title: document.getElementById('detailTitle').value.trim(),
        description: document.getElementById('detailDescription').value,
        priority: document.getElementById('detailPriority').value,
        due_date: document.getElementById('detailDueDate').value,
        assigned_to: memberIds[0] || '',
        member_ids: JSON.stringify(memberIds)
    });
    const data = await fetchJson(API_URL, { method: 'POST', body: payload });
    if (!data.ok) {
        setNotice(data.error || 'Unable to save card', 'warn');
        return;
    }
    await loadBoard();
    await openDetailModal(currentDetailCardId);
}

async function addDetailComment() {
    const comment = document.getElementById('detailNewComment').value.trim();
    if (!comment || !currentDetailCardId) return;
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'saveCardComment', card_id: String(currentDetailCardId), comment })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to add comment', 'warn');
        return;
    }
    document.getElementById('detailNewComment').value = '';
    await openDetailModal(currentDetailCardId);
}

async function addChecklist() {
    const title = document.getElementById('detailNewChecklist').value.trim();
    if (!title || !currentDetailCardId) return;
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'saveCardChecklist', card_id: String(currentDetailCardId), title })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to add checklist', 'warn');
        return;
    }
    document.getElementById('detailNewChecklist').value = '';
    await openDetailModal(currentDetailCardId);
}

async function addChecklistItem(checklistId) {
    const input = document.querySelector(`[data-checklist-input="${checklistId}"]`);
    const title = input?.value.trim() || '';
    if (!title) return;
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'saveChecklistItem', checklist_id: String(checklistId), title })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to add checklist item', 'warn');
        return;
    }
    if (input) input.value = '';
    await openDetailModal(currentDetailCardId);
}

async function toggleChecklistItem(itemId) {
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'toggleChecklistItem', item_id: String(itemId) })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to update checklist item', 'warn');
        return;
    }
    await openDetailModal(currentDetailCardId);
}

async function deleteChecklistItem(itemId) {
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'deleteChecklistItem', item_id: String(itemId) })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to delete checklist item', 'warn');
        return;
    }
    await openDetailModal(currentDetailCardId);
}

async function deleteChecklist(checklistId) {
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'deleteCardChecklist', checklist_id: String(checklistId) })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to delete checklist', 'warn');
        return;
    }
    await openDetailModal(currentDetailCardId);
}

async function toggleLabel(labelId) {
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'toggleCardLabel', card_id: String(currentDetailCardId), label_id: String(labelId) })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to update label', 'warn');
        return;
    }
    await openDetailModal(currentDetailCardId);
}

async function openMemberModal() {
    const usersRes = await fetchJson(API_URL + '?action=listUsers');
    allUsers = usersRes.data || [];
    
    const membersRes = await fetchJson(API_URL + '?action=listWorkspaceMembers&workspace_id=' + WORKSPACE_ID);
    const currentMembers = membersRes.data || [];
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
            ${permissions.can_manage ? `<button class="ws-remove-btn" type="button" data-action="remove-member" data-user-id="${m.user_id}"><i class="fas fa-trash"></i></button>` : ''}
        </div>
    `).join('') : '<div class="kanban-empty">No members yet</div>';
    
    document.getElementById('userSelectList').innerHTML = allUsers
        .filter(u => !currentUserIds.includes(String(u.id)))
        .map(u => `
            <label class="ws-user-chip" ${permissions.can_manage ? 'data-action="toggle-chip"' : ''}>
                <div class="ws-avatar">${(u.name || u.id).charAt(0).toUpperCase()}</div>
                <span>${escapeHtml(u.name || u.id)}</span>
                <input type="checkbox" class="ws-add-member" value="${u.id}" ${permissions.can_manage ? '' : 'disabled'}>
            </label>
        `).join('') || '<div class="kanban-empty">All users are already members</div>';

    document.querySelectorAll('#currentMembersList .ws-privilege-select').forEach(select => {
        select.disabled = !permissions.can_manage;
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
        body: new URLSearchParams({ action: 'saveWorkspaceMembers', workspace_id: WORKSPACE_ID, members: JSON.stringify(Array.from(memberMap.values())) })
    });
    const data = await res.json();
    if (data.ok) { 
        document.getElementById('membersModal').classList.remove('show');
        loadBoard();
    } else {
        setNotice(data.error || 'Unable to save members', 'warn');
    }
}

async function createBoardLabel() {
    const name = document.getElementById('detailNewLabelName').value.trim();
    if (!name || !currentDetailCard?.board_id) return;
    const color = document.getElementById('detailNewLabelColor').value;
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'saveBoardLabel', board_id: String(currentDetailCard.board_id), name, color })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to create label', 'warn');
        return;
    }
    document.getElementById('detailNewLabelName').value = '';
    document.getElementById('detailNewLabelColor').value = '#2563eb';
    await openDetailModal(currentDetailCardId);
}

async function toggleArchiveCard() {
    if (!currentDetailCardId) return;
    const archive = currentDetailCard?.archived ? 0 : 1;
    const data = await fetchJson(API_URL, {
        method: 'POST',
        body: new URLSearchParams({ action: 'archiveCard', card_id: String(currentDetailCardId), archive: String(archive) })
    });
    if (!data.ok) {
        setNotice(data.error || 'Unable to update archive state', 'warn');
        return;
    }
    closeDetailModal();
    await loadBoard();
}

async function loadBoard() {
    const data = await fetchJson(API_URL + '?action=getWorkspaceBoard&workspace_id=' + WORKSPACE_ID);
    if (!data.data) {
        setNotice(data.error || 'Unable to load board', 'warn');
        return;
    }
    boardData = data.data;
    lists = data.data.lists || [];
    cards = data.data.cards || [];
    members = data.data.members || [];
    permissions = data.data.permissions || { can_manage: false, can_edit: false };
    legacyMode = !!data.meta?.legacy_mode;
    populateMemberOptions();
    renderControls();
    renderBoard();
    setNotice(legacyMode ? 'You are seeing the legacy task board. New workspaces should use the board -> list -> card flow.' : '', legacyMode ? 'info' : 'info');
}

async function saveCard() {
    const payload = {
        id: document.getElementById('cardId').value,
        workspace_id: WORKSPACE_ID,
        title: document.getElementById('cardTitle').value,
        description: document.getElementById('cardDescription').value,
        priority: document.getElementById('cardPriority').value,
        due_date: document.getElementById('cardDueDate').value,
        assigned_to: document.getElementById('cardAssignedTo').value
    };
    let data;
    if (legacyMode) {
        payload.action = 'saveWorkspaceTask';
        payload.status = document.getElementById('cardListId').value;
        data = await fetchJson(API_URL, { method: 'POST', body: new URLSearchParams(payload) });
    } else {
        payload.action = 'saveWorkspaceCard';
        payload.list_id = document.getElementById('cardListId').value;
        data = await fetchJson(API_URL, { method: 'POST', body: new URLSearchParams(payload) });
    }
    if (!data.ok) {
        setNotice(data.error || 'Unable to save card', 'warn');
        return;
    }
    closeCardModal();
    loadBoard();
}

async function moveCard(cardId, listId) {
    let data;
    if (legacyMode) {
        data = await fetchJson(API_URL, { method: 'POST', body: new URLSearchParams({ action: 'updateWorkspaceTaskStatus', id: cardId, status: listId }) });
    } else {
        data = await fetchJson(API_URL, { method: 'POST', body: new URLSearchParams({ action: 'moveWorkspaceCard', id: cardId, list_id: listId, workspace_id: WORKSPACE_ID }) });
    }
    if (!data.ok) {
        setNotice(data.error || 'Unable to move card', 'warn');
        return;
    }
    loadBoard();
}

async function deleteCard(cardId) {
    if (!confirm('Delete this card?')) return;
    let data;
    if (legacyMode) {
        data = await fetchJson(API_URL, { method: 'POST', body: new URLSearchParams({ action: 'deleteWorkspaceTask', id: cardId }) });
    } else {
        data = await fetchJson(API_URL, { method: 'POST', body: new URLSearchParams({ action: 'deleteWorkspaceCard', id: cardId, workspace_id: WORKSPACE_ID }) });
    }
    if (!data.ok) {
        setNotice(data.error || 'Unable to delete card', 'warn');
        return;
    }
    loadBoard();
}

document.addEventListener('click', function(e) {
    const target = e.target.closest('[data-action]');
    if (!target) return;
    const action = target.dataset.action;
    const id = target.dataset.id;
    const listId = target.dataset.listId || '';
    if (action === 'new-card' && permissions.can_edit) openCardModal(null, listId);
    else if (action === 'open-card') openDetailModal(id);
    else if (action === 'edit-card' && permissions.can_edit) openCardModal(cards.find(card => String(card.id) === String(id)) || null);
    else if (action === 'delete-card') deleteCard(id);
    else if (action === 'close-card-modal') closeCardModal();
    else if (action === 'close-detail') closeDetailModal();
    else if (action === 'toggle-label' && currentDetailCard?.can_edit) toggleLabel(id);
    else if (action === 'add-check-item' && currentDetailCard?.can_edit) addChecklistItem(id);
    else if (action === 'toggle-check-item' && currentDetailCard?.can_edit) toggleChecklistItem(id);
    else if (action === 'delete-check-item' && currentDetailCard?.can_edit) deleteChecklistItem(id);
    else if (action === 'delete-checklist' && currentDetailCard?.can_edit) deleteChecklist(id);
    else if (action === 'open-members-modal' && permissions.can_manage) openMemberModal();
    else if (action === 'close-members-modal') document.getElementById('membersModal').classList.remove('show');
    else if (action === 'save-members' && permissions.can_manage) saveMembers();
    else if (action === 'toggle-chip' && permissions.can_manage) {
        const checkbox = target.querySelector('input');
        checkbox.checked = !checkbox.checked;
        target.classList.toggle('selected', checkbox.checked);
    } else if (action === 'remove-member' && permissions.can_manage) removeMember(target.dataset.userId);
    else if (action === 'update-privilege' && permissions.can_manage) updatePrivilege(target.dataset.userId, target.value);
}, { signal });

document.addEventListener('change', function(e) {
    if (e.target.id === 'priorityFilter' || e.target.id === 'assigneeFilter') {
        renderBoard();
    }
}, { signal });

document.getElementById('searchFilter').addEventListener('input', function() {
    renderBoard();
}, { signal });

document.getElementById('detailSaveBtn').addEventListener('click', function() {
    saveDetailCard();
}, { signal });

document.getElementById('detailAddCommentBtn').addEventListener('click', function() {
    addDetailComment();
}, { signal });

document.getElementById('detailAddChecklistBtn').addEventListener('click', function() {
    addChecklist();
}, { signal });

document.getElementById('detailAddLabelBtn').addEventListener('click', function() {
    createBoardLabel();
}, { signal });

document.getElementById('detailArchiveBtn').addEventListener('click', function() {
    toggleArchiveCard();
}, { signal });

document.addEventListener('dragover', function(e) {
    const col = e.target.closest('.kanban-col');
    if (col && permissions.can_edit) e.preventDefault();
}, { signal });

document.addEventListener('dragstart', function(e) {
    const task = e.target.closest('.kanban-task');
    if (task && permissions.can_edit) {
        e.dataTransfer.setData('cardId', task.dataset.id);
        task.classList.add('dragging');
    }
}, { signal });

document.addEventListener('dragend', function(e) {
    const task = e.target.closest('.kanban-task');
    if (task) task.classList.remove('dragging');
}, { signal });

document.addEventListener('drop', function(e) {
    const col = e.target.closest('.kanban-col');
    if (!col || !permissions.can_edit) return;
    e.preventDefault();
    const cardId = e.dataTransfer.getData('cardId');
    const listId = col.dataset.listId;
    if (cardId && listId) moveCard(cardId, listId);
}, { signal });

document.getElementById('backBtn').addEventListener('click', function() {
    if (window.parent !== window) {
        window.parent.postMessage('close-board', '*');
    } else {
        window.location.href = 'workspace.php';
    }
}, { signal });

document.getElementById('cardForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveCard();
}, { signal });

loadBoard();
})();
</script>

</div>
</div>
</body>
</html>
