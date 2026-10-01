<?php
$PathPrefix = './';
include($PathPrefix . 'include/session.inc');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weekly Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.wr-app { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background: #eef2f7; min-height: 100vh; box-sizing: border-box; }
.wr-app *, .wr-app *::before, .wr-app *::after { box-sizing: border-box; }
.wr-header, .wr-section { background: #fff; border-radius: 14px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); }
.wr-header { padding: 22px; margin-bottom: 20px; }
.wr-header h1 { margin: 0 0 8px; color: #17324d; font-size: 28px; }
.wr-header-note { color: #5e6c84; font-size: 14px; }
.wr-controls { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: 16px; }
.wr-btn { padding: 9px 16px; border: none; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
.wr-btn-primary { background: linear-gradient(135deg, #2563eb, #0f766e); color: #fff; }
.wr-btn-primary:hover { filter: brightness(0.97); }
.wr-btn-secondary { background: #eef3f8; color: #17324d; }
.wr-btn-secondary:hover { background: #dfe8f1; }
.wr-section { padding: 20px; margin-bottom: 20px; }
.wr-section-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.wr-section-header h2 { margin: 0; color: #17324d; font-size: 19px; }
.wr-section-note { color: #5e6c84; font-size: 13px; margin-top: 4px; }
.wr-dashboard-notice { display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 10px; font-size: 13px; }
.wr-dashboard-notice.show { display: block; }
.wr-dashboard-notice.info { background: #eef4ff; color: #2455a6; }
.wr-dashboard-notice.warn { background: #fff4dd; color: #8a6700; }
.wr-dashboard-notice.error { background: #fde7e9; color: #b42318; }
.wr-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; margin-bottom: 18px; }
.wr-stat { padding: 20px; border-radius: 14px; color: #fff; }
.wr-stat-purple { background: linear-gradient(135deg, #1d4ed8, #4338ca); }
.wr-stat-green { background: linear-gradient(135deg, #0f766e, #16a34a); }
.wr-stat-pink { background: linear-gradient(135deg, #db2777, #ef4444); }
.wr-stat-blue { background: linear-gradient(135deg, #0284c7, #0ea5e9); }
.wr-stat-value { font-size: 32px; font-weight: 800; }
.wr-stat-label { opacity: 0.94; font-size: 13px; margin-top: 4px; }
	.wr-table-wrap { overflow-x: auto; }
	.wr-table { width: 100%; border-collapse: collapse; min-width: 1060px; }
.wr-table th { background: #17324d; color: #fff; padding: 12px 10px; text-align: left; font-size: 12px; letter-spacing: 0.04em; text-transform: uppercase; }
.wr-table td { padding: 12px 10px; border-bottom: 1px solid #e5edf5; font-size: 13px; color: #1f2937; vertical-align: top; }
.wr-table tr:hover { background: #f8fbfd; }
.wr-card-title { font-size: 14px; font-weight: 700; color: #17324d; margin-bottom: 4px; }
.wr-card-meta, .wr-comment-meta { color: #64748b; font-size: 12px; line-height: 1.45; }
.wr-card-desc { margin-top: 6px; color: #475569; font-size: 12px; line-height: 1.45; }
.wr-status-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: capitalize; }
.wr-status-planned { background: #fff3cd; color: #8a6700; }
.wr-status-in_progress { background: #dbeafe; color: #1d4ed8; }
.wr-status-completed { background: #d1fae5; color: #047857; }
.wr-status-cancelled { background: #f3f4f6; color: #4b5563; }
.wr-label-list { display: flex; gap: 6px; flex-wrap: wrap; }
.wr-label-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 9px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #f8fafc; border: 1px solid #e2e8f0; color: #17324d; }
.wr-label-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.wr-checklist { min-width: 150px; }
.wr-checklist-head { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; color: #475569; font-weight: 700; }
.wr-checklist-track { height: 8px; border-radius: 999px; background: #dbe4ee; overflow: hidden; margin: 7px 0 8px; }
.wr-checklist-track span { display: block; height: 100%; background: linear-gradient(90deg, #2563eb, #0f766e); }
.wr-checklist-note { color: #64748b; font-size: 12px; line-height: 1.4; }
.wr-comment-box { padding: 10px 12px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; min-width: 220px; }
.wr-comment-text { color: #17324d; font-size: 12px; line-height: 1.45; margin-top: 4px; }
.wr-empty { text-align: center; padding: 26px; color: #64748b; }
.wr-empty i { font-size: 28px; display: block; margin-bottom: 10px; opacity: 0.55; }
.wr-link-inline { color: #1d4ed8; font-weight: 700; text-decoration: none; }
.wr-link-inline:hover { text-decoration: underline; }
.wr-gantt-shell { display: grid; gap: 12px; }
.wr-gantt-head, .wr-gantt-row { display: grid; grid-template-columns: minmax(280px, 1.1fr) minmax(420px, 1.9fr); gap: 14px; align-items: stretch; }
.wr-gantt-head-info { padding: 10px 14px; border-radius: 12px; background: #17324d; color: #fff; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 800; }
.wr-gantt-head-days { display: grid; grid-template-columns: repeat(7, minmax(46px, 1fr)); gap: 6px; }
.wr-gantt-day-head { padding: 10px 4px; border-radius: 12px; background: #17324d; color: #fff; text-align: center; }
.wr-gantt-day-head strong { display: block; font-size: 12px; }
.wr-gantt-day-head span { font-size: 11px; opacity: 0.84; }
.wr-gantt-info { padding: 14px; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
.wr-gantt-kicker { color: #64748b; font-size: 12px; margin-bottom: 6px; }
.wr-gantt-title { font-size: 15px; font-weight: 800; color: #17324d; margin-bottom: 6px; }
.wr-gantt-meta { color: #475569; font-size: 12px; line-height: 1.45; margin-bottom: 8px; }
.wr-gantt-track { display: grid; grid-template-columns: repeat(7, minmax(46px, 1fr)); gap: 6px; align-items: stretch; position: relative; min-height: 108px; }
.wr-gantt-cell { border-radius: 12px; background: linear-gradient(180deg, #f8fafc, #eef3f8); border: 1px solid #dbe4ee; }
.wr-gantt-bar-wrap { grid-row: 1; z-index: 2; align-self: center; }
.wr-gantt-bar { position: relative; min-height: 58px; border-radius: 14px; background: var(--bar-color, #2563eb); color: #fff; box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18); overflow: hidden; }
.wr-gantt-progress { position: absolute; inset: 0 auto 0 0; background: rgba(255,255,255,0.22); }
.wr-gantt-bar-content { position: relative; z-index: 1; padding: 10px 12px; }
.wr-gantt-bar-top { display: flex; justify-content: space-between; gap: 10px; align-items: center; font-size: 12px; font-weight: 700; }
.wr-gantt-bar-top span:last-child { opacity: 0.88; }
.wr-gantt-bar-bottom { margin-top: 8px; font-size: 12px; line-height: 1.35; opacity: 0.96; }
.wr-modal { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.58); z-index: 1100; display: none; align-items: center; justify-content: center; padding: 16px; backdrop-filter: blur(4px); }
.wr-modal.show { display: flex; }
.wr-modal-shell { width: min(96vw, 1500px); height: min(92vh, 980px); background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28); display: flex; flex-direction: column; }
.wr-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; background: #17324d; color: #fff; }
.wr-modal-title { font-size: 16px; font-weight: 800; }
.wr-modal-close { border: none; background: transparent; color: #fff; font-size: 28px; line-height: 1; cursor: pointer; }
.wr-modal-close:hover { opacity: 0.82; }
.wr-modal-frame { width: 100%; flex: 1; border: 0; background: #f8fafc; }
.toastr-wrap { position: fixed; top: 16px; right: 16px; z-index: 2000; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
.toastr { min-width: 260px; max-width: 420px; padding: 12px 14px; border-radius: 10px; color: #fff; font-size: 13px; line-height: 1.4; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.28); pointer-events: auto; animation: toastr-in 0.2s ease-out; }
.toastr-info { background: #2455a6; }
.toastr-warning { background: #8a6700; }
.toastr-error { background: #b42318; }
.toastr-success { background: #047857; }
@keyframes toastr-in {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 1100px) {
    .wr-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .wr-gantt-head, .wr-gantt-row { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
    .wr-app { padding: 12px; }
    .wr-header, .wr-section { padding: 16px; }
    .wr-stats { grid-template-columns: 1fr; }
    .wr-controls { align-items: stretch; }
    .wr-controls input, .wr-controls .wr-btn { width: 100%; justify-content: center; }
    .wr-gantt-head-days, .wr-gantt-track { min-width: 560px; }
}
</style>
</head>
<body>
<div class="wr-app">
    <div class="wr-header">
        <h1 id="reportTitle"><i class="fas fa-chart-bar"></i> Weekly Report</h1>
        <div class="wr-header-note">Board cards now drive this weekly report so the board stays the single source of truth.</div>
        <div class="wr-controls">
            <strong>Period:</strong>
            <span id="weekPeriod">Loading...</span>
            <input type="date" id="weekSelector">
            <button class="wr-btn wr-btn-primary" id="exportBtn"><i class="fas fa-file-excel"></i> Export Excel</button>
        </div>
    </div>

    <div class="wr-section">
        <div class="wr-section-header">
            <div>
                <h2><i class="fas fa-table-list"></i> Board Activity Ledger</h2>
                <div class="wr-section-note">A read-only weekly ledger sourced from board cards, list status, checklist progress, labels, and comments.</div>
            </div>
        </div>
        <div class="wr-table-wrap">
            <table class="wr-table">
                <thead>
                    <tr>
                        <th>Updated</th>
                        <th>Workspace</th>
                        <th>Card</th>
                        <th>Checklist</th>
                        <th>Labels</th>
                        <th>Latest Comment</th>
                        <th>Board</th>
                    </tr>
                </thead>
                <tbody id="activitiesBody">
                    <tr><td colspan="7" style="text-align:center;color:#64748b;">Loading board activity...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="wr-section">
        <div class="wr-section-header">
            <div>
                <h2><i class="fas fa-diagram-project"></i> Gantt Report</h2>
                <div class="wr-section-note">Visual timeline for the selected week using card dates, checklist completion, label colors, and the latest comment context.</div>
            </div>
        </div>
        <div id="ganttBody" class="wr-empty">
            <i class="fas fa-timeline"></i>
            <div>Loading timeline...</div>
        </div>
    </div>
</div>

<div id="boardModal" class="wr-modal">
    <div class="wr-modal-shell">
        <div class="wr-modal-header">
            <div class="wr-modal-title" id="boardModalTitle"><i class="fas fa-columns"></i> Board</div>
            <button type="button" class="wr-modal-close" id="boardModalClose">&times;</button>
        </div>
        <iframe id="boardModalFrame" class="wr-modal-frame" src="about:blank" title="Workspace Board"></iframe>
    </div>
</div>
<div id="toastrWrap" class="toastr-wrap" aria-live="polite" aria-atomic="true"></div>

<script>
(function() {
const API_URL = 'functions/api.php';
const lsUserId = localStorage.getItem('user_id');
const originalFetch = window.fetch;
window.fetch = async function(url, options = {}) {
    if (typeof url === 'string' && url.includes(API_URL)) {
        if (!options.method || options.method.toUpperCase() === 'GET') {
            url += (url.includes('?') ? '&' : '?') + 'user_id=' + encodeURIComponent(lsUserId || '');
        } else if (options.method.toUpperCase() === 'POST' && (options.body instanceof URLSearchParams || options.body instanceof FormData)) {
            options.body.append('user_id', lsUserId || '');
        }
    }
    return originalFetch(url, options);
};

const urlParams = new URLSearchParams(window.location.search);
const REPORT_TITLE = (urlParams.get('title') || 'Weekly Report').trim() || 'Weekly Report';
const STATUS_LABELS = {
    planned: 'Planned',
    in_progress: 'In Progress',
    completed: 'Completed',
    cancelled: 'Cancelled'
};

let WEEK_START = formatDate(getMonday(new Date()));
let weeklyReportData = null;

function getMonday(date) {
    const value = new Date(date);
    const day = value.getDay();
    const diff = value.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(value.setDate(diff));
}

function formatDate(value) {
    const date = new Date(value);
    return date.toISOString().split('T')[0];
}

function formatDisplay(value) {
    return value.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatDateTime(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
}

function truncateText(value, limit = 120) {
    const text = String(value || '').trim();
    return text.length > limit ? text.slice(0, limit - 3) + '...' : text;
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

function updateWeekDisplay() {
    const monday = new Date(WEEK_START);
    const sunday = new Date(monday);
    sunday.setDate(sunday.getDate() + 6);
    document.getElementById('weekPeriod').textContent = formatDisplay(monday) + ' - ' + formatDisplay(sunday);
    document.getElementById('weekSelector').value = WEEK_START;
}

function setDashboardNotice(message, tone = 'info') {
    if (!message) return;
    const toastTone = tone === 'warn' ? 'warning' : tone;
    showToastr(message, toastTone);
}

function showToastr(message, tone = 'info') {
    const wrap = document.getElementById('toastrWrap');
    if (!wrap || !message) return;
    const safeTone = ['info', 'warning', 'error', 'success'].includes(tone) ? tone : 'info';
    const node = document.createElement('div');
    node.className = 'toastr toastr-' + safeTone;
    node.textContent = String(message);
    wrap.appendChild(node);
    window.setTimeout(() => {
        node.style.opacity = '0';
        node.style.transform = 'translateY(-4px)';
        node.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        window.setTimeout(() => node.remove(), 220);
    }, 3800);
}

async function fetchJson(url, options) {
    const res = await fetch(url, options);
    return res.json();
}

function getStatusBadge(status) {
    const safeStatus = STATUS_LABELS[status] ? status : 'planned';
    return '<span class="wr-status-badge wr-status-' + safeStatus + '">' + STATUS_LABELS[safeStatus] + '</span>';
}

function getChecklistCounts(card) {
    let total = Number(card.checklist_total || 0);
    let done = Number(card.checklist_done || 0);
    if ((total === 0 || done > total) && Array.isArray(card.checklists)) {
        total = 0;
        done = 0;
        card.checklists.forEach(checklist => {
            const items = Array.isArray(checklist.items) ? checklist.items : [];
            total += items.length;
            done += items.filter(item => Number(item.is_done) === 1).length;
        });
    }
    return { total, done, percent: total ? Math.round((done / total) * 100) : 0 };
}

function getLatestComment(card) {
    if (card && card.latest_comment && card.latest_comment.comment) {
        return card.latest_comment;
    }
    if (Array.isArray(card.comments) && card.comments.length) {
        return card.comments[0];
    }
    return null;
}

function getPrimaryColor(card) {
    if (Array.isArray(card.labels) && card.labels.length && card.labels[0].color) {
        return card.labels[0].color;
    }
    return card.list_color || '#2563eb';
}

function getBoardUrl(card) {
    return 'workspace_kanban.php?id=' + encodeURIComponent(card.workspace_id || '') + '&name=' + encodeURIComponent(card.workspace_name || 'Workspace');
}

function openBoardModal(workspaceId, workspaceName) {
    const modal = document.getElementById('boardModal');
    const frame = document.getElementById('boardModalFrame');
    document.getElementById('boardModalTitle').innerHTML = '<i class="fas fa-columns"></i> ' + escapeHtml(workspaceName || 'Board');
    frame.src = 'workspace_kanban.php?id=' + encodeURIComponent(workspaceId || '') + '&name=' + encodeURIComponent(workspaceName || 'Workspace');
    modal.classList.add('show');
}

function closeBoardModal() {
    const modal = document.getElementById('boardModal');
    const frame = document.getElementById('boardModalFrame');
    modal.classList.remove('show');
    frame.src = 'about:blank';
}

function getWeekDays() {
    const days = [];
    const base = new Date(WEEK_START + 'T00:00:00');
    for (let i = 0; i < 7; i++) {
        const date = new Date(base);
        date.setDate(base.getDate() + i);
        days.push({
            date: formatDate(date),
            shortLabel: date.toLocaleDateString([], { weekday: 'short' }),
            number: date.toLocaleDateString([], { day: '2-digit', month: '2-digit' })
        });
    }
    return days;
}

function getTimelinePlacement(card, days) {
    const dates = days.map(day => day.date);
    let start = dates.indexOf(String(card.timeline_start || '').slice(0, 10));
    let end = dates.indexOf(String(card.timeline_end || '').slice(0, 10));
    if (start === -1) start = 0;
    if (end === -1) end = start;
    if (end < start) end = start;
    return { start, end };
}

function renderLabels(card) {
    const labels = Array.isArray(card.labels) ? card.labels : [];
    if (!labels.length) {
        return '<span class="wr-card-meta">No labels</span>';
    }
    return '<div class="wr-label-list">' + labels.map(label => (
        '<span class="wr-label-chip"><span class="wr-label-dot" style="background:' + escapeHtml(label.color || '#2563eb') + '"></span>' + escapeHtml(label.name || 'Label') + '</span>'
    )).join('') + '</div>';
}

function renderChecklist(card) {
    const counts = getChecklistCounts(card);
    const summaries = Array.isArray(card.checklists)
        ? card.checklists
            .filter(checklist => Array.isArray(checklist.items) && checklist.items.length)
            .slice(0, 2)
            .map(checklist => escapeHtml(checklist.title))
        : [];
    return `
        <div class="wr-checklist">
            <div class="wr-checklist-head">
                <span>${counts.done}/${counts.total}</span>
                <span>${counts.percent}%</span>
            </div>
            <div class="wr-checklist-track"><span style="width:${counts.percent}%;"></span></div>
            <div class="wr-checklist-note">${summaries.length ? summaries.join(', ') : 'Checklist not started'}</div>
        </div>
    `;
}

function renderComment(card) {
    const comment = getLatestComment(card);
    if (!comment) {
        return '<div class="wr-comment-box"><div class="wr-comment-meta">No comments yet</div></div>';
    }
    return `
        <div class="wr-comment-box">
            <div class="wr-comment-meta">${escapeHtml(comment.full_name || comment.user_id || 'User')} • ${escapeHtml(formatDateTime(comment.created_at))}</div>
            <div class="wr-comment-text">${escapeHtml(truncateText(comment.comment || '', 160))}</div>
        </div>
    `;
}

function renderActivities(cards) {
    const tbody = document.getElementById('activitiesBody');
    if (!cards.length) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="wr-empty"><i class="fas fa-layer-group"></i><div>No board cards matched this week.</div></div></td></tr>';
        return;
    }

    tbody.innerHTML = cards.map(card => `
        <tr>
            <td>${escapeHtml(formatDateTime(card.updated_at))}</td>
            <td>
                <div class="wr-card-title">${escapeHtml(card.workspace_name || 'Workspace')}</div>
                <div class="wr-card-meta">${escapeHtml(card.board_name || 'Main Board')} • ${escapeHtml(card.list_name || 'List')}</div>
            </td>
            <td>
                <div class="wr-card-title">${escapeHtml(card.title || 'Untitled card')}</div>
                <div class="wr-card-meta">Assignees: ${escapeHtml((card.member_names || []).join(', ') || 'Unassigned')}</div>
                <div class="wr-card-meta">Due: ${escapeHtml(card.due_date ? formatDateTime(card.due_date) : 'No due date')}</div>
                ${card.description ? `<div class="wr-card-desc">${escapeHtml(truncateText(card.description, 150))}</div>` : ''}
            </td>
            <td>${renderChecklist(card)}</td>
            <td>${renderLabels(card)}</td>
            <td>${renderComment(card)}</td>
            <td><button type="button" class="wr-btn wr-btn-secondary" data-action="open-board-modal" data-workspace-id="${escapeHtml(card.workspace_id || '')}" data-workspace-name="${escapeHtml(card.workspace_name || 'Workspace')}"><i class="fas fa-columns"></i> Open Board</button></td>
        </tr>
    `).join('');
}

function renderGantt(cards) {
    const container = document.getElementById('ganttBody');
    if (!cards.length) {
        container.className = 'wr-empty';
        container.innerHTML = '<i class="fas fa-timeline"></i><div>No cards to visualise for this week.</div>';
        return;
    }

    const days = getWeekDays();
    const headerDays = days.map(day => `
        <div class="wr-gantt-day-head">
            <strong>${escapeHtml(day.shortLabel)}</strong>
            <span>${escapeHtml(day.number)}</span>
        </div>
    `).join('');

    const rows = cards.map(card => {
        const placement = getTimelinePlacement(card, days);
        const counts = getChecklistCounts(card);
        const latestComment = getLatestComment(card);
        const color = getPrimaryColor(card);
        const infoLabels = Array.isArray(card.labels) && card.labels.length ? renderLabels(card) : '<span class="wr-card-meta">No labels</span>';
        const infoComment = latestComment
            ? escapeHtml(truncateText((latestComment.full_name || latestComment.user_id || 'User') + ': ' + (latestComment.comment || ''), 140))
            : 'No comments yet';

        return `
            <div class="wr-gantt-row">
                <div class="wr-gantt-info">
                    <div class="wr-gantt-kicker">${escapeHtml(card.workspace_name || 'Workspace')} • ${escapeHtml(card.list_name || 'List')}</div>
                    <div class="wr-gantt-title">${escapeHtml(card.title || 'Untitled card')}</div>
                    <div class="wr-gantt-meta">Assignees: ${escapeHtml((card.member_names || []).join(', ') || 'Unassigned')}<br>Due: ${escapeHtml(card.due_date ? formatDateTime(card.due_date) : 'No due date')}</div>
                    ${infoLabels}
                    <div class="wr-gantt-meta" style="margin-top:8px;">Latest comment: ${infoComment}</div>
                </div>
                <div class="wr-gantt-track">
                    ${days.map(() => '<div class="wr-gantt-cell"></div>').join('')}
                    <div class="wr-gantt-bar-wrap" style="grid-column:${placement.start + 1} / ${placement.end + 2};">
                        <div class="wr-gantt-bar" style="--bar-color:${escapeHtml(color)};">
                            <div class="wr-gantt-progress" style="width:${counts.percent}%;"></div>
                            <div class="wr-gantt-bar-content">
                                <div class="wr-gantt-bar-top">
                                    <span>${escapeHtml(STATUS_LABELS[card.activity_status] || 'Planned')}</span>
                                    <span>${counts.done}/${counts.total} checklist</span>
                                </div>
                                <div class="wr-gantt-bar-bottom">${escapeHtml((card.labels || []).map(label => label.name).join(', ') || 'No labels')}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    container.className = 'wr-gantt-shell';
    container.innerHTML = `
        <div class="wr-gantt-head">
            <div class="wr-gantt-head-info">Card Context</div>
            <div class="wr-gantt-head-days">${headerDays}</div>
        </div>
        ${rows}
    `;
}

async function loadReport() {
    const data = await fetchJson(API_URL + '?action=getWeeklyReport&week_start=' + WEEK_START + '&title=' + encodeURIComponent(REPORT_TITLE.toUpperCase()));
    if (!data.data) {
        weeklyReportData = null;
        renderActivities([]);
        renderGantt([]);
        setDashboardNotice(data.error || 'The weekly report could not be loaded.', 'warn');
        return;
    }

    weeklyReportData = data.data;
    const cards = data.data.board_activities || data.data.field_activities || [];
    
    renderActivities(cards);
    renderGantt(cards);
}

function exportReport() {
    const userIdParam = lsUserId ? '&user_id=' + encodeURIComponent(lsUserId) : '';
    window.location.href = API_URL + '?action=exportWeeklyReport&week_start=' + WEEK_START + '&title=' + encodeURIComponent(REPORT_TITLE.toUpperCase()) + userIdParam;
}

function changeWeek(value) {
    WEEK_START = formatDate(getMonday(new Date(value)));
    updateWeekDisplay();
    loadReport();
}

document.getElementById('exportBtn').addEventListener('click', exportReport);
document.getElementById('boardModalClose').addEventListener('click', closeBoardModal);
document.getElementById('boardModal').addEventListener('click', function(e) {
    if (e.target.id === 'boardModal') {
        closeBoardModal();
    }
});
document.getElementById('weekSelector').addEventListener('change', function(e) {
    changeWeek(e.target.value);
});

document.addEventListener('click', function(e) {
    const target = e.target.closest('[data-action="open-board-modal"]');
    if (!target) return;
    openBoardModal(target.dataset.workspaceId, target.dataset.workspaceName);
});

window.addEventListener('message', function(e) {
    if (e.data === 'close-board') {
        closeBoardModal();
        loadReport();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('reportTitle').innerHTML = '<i class="fas fa-chart-bar"></i> ' + escapeHtml(REPORT_TITLE);
    const weekParam = urlParams.get('week');
    if (weekParam) {
        WEEK_START = formatDate(getMonday(new Date(weekParam)));
    }
    updateWeekDisplay();
    loadReport();
});
})();
</script>
</body>
</html>
