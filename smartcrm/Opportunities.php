<?php
// Opportunity Pipeline Management
$PageSecurity = 0;
$PathPrefix = './';
$inIframe = true;
$extraCrumb = 'Opportunity Pipeline';
error_reporting(E_ALL);
include($PathPrefix . 'includes/session.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');
include($PathPrefix . 'includes/crm_scope.php');

$Title = _('Opportunity Pipeline');
include($PathPrefix . 'includes/header.inc');

unset($extraCrumb);

$CurrencySymbol = isset($CurrencySymbol) ? $CurrencySymbol : 'KSh ';
?>
<link rel="stylesheet" href="kanban.css">
<script>
var CRM_CURRENCY_SYMBOL = '<?php echo $CurrencySymbol; ?>';
</script>
<style>
.crm-container { padding: 20px; max-width: 1600px; margin: 0 auto; }
.crm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
.crm-header h2 { margin: 0; color: #2c3e50; }
.pipeline-stats { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
.stat-card { background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); min-width: 150px; }
.stat-card h4 { margin: 0 0 5px 0; color: #6c757d; font-size: 12px; text-transform: uppercase; }
.stat-card .value { font-size: 24px; font-weight: bold; color: #2c3e50; }
.pipeline-board { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 20px; }
.pipeline-column { min-width: 280px; background: #f8f9fa; border-radius: 8px; padding: 15px; flex-shrink: 0; }
.pipeline-column h3 { margin: 0 0 10px 0; font-size: 14px; color: #2c3e50; display: flex; justify-content: space-between; align-items: center; }
.pipeline-column .count { background: #e9ecef; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
.pipeline-column .value { font-size: 12px; color: #27ae60; font-weight: 600; }
.pipeline-cards { min-height: 200px; }
.opp-card { background: white; padding: 12px; border-radius: 6px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s; }
.opp-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
.opp-card .name { font-weight: 600; color: #2c3e50; margin-bottom: 5px; }
.opp-card .company { font-size: 12px; color: #6c757d; }
.opp-card .value { font-size: 16px; font-weight: bold; color: #27ae60; margin-top: 8px; }
.opp-card .date { font-size: 11px; color: #adb5bd; }
.opp-card .probability { font-size: 11px; padding: 2px 6px; border-radius: 4px; background: #e9ecef; }
.funnel-summary { display: flex; justify-content: space-between; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; margin-bottom: 20px; }
.funnel-summary .item { text-align: center; }
.funnel-summary .item .label { font-size: 12px; opacity: 0.9; }
.funnel-summary .item .num { font-size: 20px; font-weight: bold; }
.btn-success { background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.btn-primary { background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.btn-secondary { background: #95a5a6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
.modal.show { display: flex; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.close-modal { background: none; border: none; font-size: 24px; cursor: pointer; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #2c3e50; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
</style>

<div class="crm-container">
    <div class="crm-header">
        <h2><?php echo _('Opportunity Pipeline'); ?></h2>
        <div class="crm-filters">
            <select id="scopeFilter" onchange="onScopeChange()">
                <?php echo crm_scope_options('My Opportunities', 'All Opportunities'); ?>
            </select>
        </div>
        <button class="btn-success" onclick="openOppModal()">+ New Opportunity</button>
    </div>
    
    <div class="funnel-summary" id="funnelSummary">
        <div class="item"><div class="label">Total Deals</div><div class="num" id="totalDeals">0</div></div>
        <div class="item"><div class="label">Pipeline Value</div><div class="num" id="pipelineValue">0</div></div>
        <div class="item"><div class="label">Expected Value</div><div class="num" id="expectedValue">0</div></div>
        <div class="item"><div class="label">Avg Probability</div><div class="num" id="avgProb">0%</div></div>
    </div>
    
    <div class="pipeline-stats" id="pipelineStats"></div>
    
    <div class="pipeline-board" id="pipelineBoard">
        <div style="text-align:center; padding: 40px;">Loading pipeline...</div>
    </div>
</div>

<!-- Opportunity Modal -->
<div class="modal" id="oppModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="oppModalTitle">New Opportunity</h3>
            <button class="close-modal" onclick="closeOppModal()">&times;</button>
        </div>
        <form id="oppForm">
            <input type="hidden" id="oppId" name="id" value="0">
            <div class="form-group">
                <label>Opportunity Name *</label>
                <input type="text" id="opportunity_name" name="opportunity_name" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Lead</label>
                    <select id="lead_id" name="lead_id">
                        <option value="">Select Lead</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stage</label>
                    <select id="pipeline_stage_id" name="pipeline_stage_id">
                        <option value="">Select Stage</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Expected Value</label>
                    <input type="number" id="expected_value" name="expected_value" step="0.01" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>Probability (%)</label>
                    <input type="number" id="probability" name="probability" min="0" max="100" value="50">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Expected Close Date</label>
                    <input type="date" id="expected_close_date" name="expected_close_date">
                </div>
                <div class="form-group">
                    <label>Assigned To</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <div class="form-group">
                <label>Next Step</label>
                <input type="text" id="next_step" name="next_step">
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn-secondary" onclick="closeOppModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Opportunity</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php echo crm_scope_js(); ?>

let stages = [];
let opportunities = [];

function onScopeChange() {
    setCrmScope(document.getElementById('scopeFilter').value, false);
    loadPipeline();
}

function loadPipeline() {
    const scope = document.getElementById('scopeFilter').value;
    Promise.all([
        fetch('api.php?action=listPipelineStages').then(r => r.json()),
        fetch('api.php?action=listOpportunities&scope=' + encodeURIComponent(scope)).then(r => r.json()),
        fetch('api.php?action=getPipelineStats&scope=' + encodeURIComponent(scope)).then(r => r.json())
    ]).then(([stagesRes, oppsRes, statsRes]) => {
        stages = stagesRes.data || [];
        opportunities = oppsRes.data || [];
        
        renderFunnelSummary(statsRes.data || []);
        renderPipelineBoard();
        renderStats(statsRes.data || []);
    });
}

function renderFunnelSummary(stats) {
    const totalDeals = opportunities.length;
    const pipelineValue = opportunities.reduce((sum, o) => sum + parseFloat(o.expected_value || 0), 0);
    const expectedValue = opportunities.reduce((sum, o) => sum + (parseFloat(o.expected_value || 0) * (o.probability || 50) / 100), 0);
    const avgProb = totalDeals > 0 ? Math.round(opportunities.reduce((sum, o) => sum + parseInt(o.probability || 0), 0) / totalDeals) : 0;
    
    document.getElementById('totalDeals').textContent = totalDeals;
    document.getElementById('pipelineValue').textContent = CRM_CURRENCY_SYMBOL + pipelineValue.toLocaleString();
    document.getElementById('expectedValue').textContent = CRM_CURRENCY_SYMBOL + expectedValue.toLocaleString();
    document.getElementById('avgProb').textContent = avgProb + '%';
}

function renderStats(stats) {
    const container = document.getElementById('pipelineStats');
    container.innerHTML = stats.map(s => `
        <div class="stat-card">
            <h4>${s.stage_name}</h4>
            <div class="value">${s.opportunity_count || 0} deals</div>
            <div class="value">${CRM_CURRENCY_SYMBOL}${(s.total_value || 0).toLocaleString()}</div>
        </div>
    `).join('');
}

function renderPipelineBoard() {
    const board = document.getElementById('pipelineBoard');
    board.innerHTML = stages.map(stage => {
        const stageOpps = opportunities.filter(o => o.pipeline_stage_id == stage.id);
        return `
        <div class="pipeline-column" data-stage-id="${stage.id}">
            <h3>${stage.stage_name} <span class="count">${stageOpps.length}</span></h3>
            <div class="value">${CRM_CURRENCY_SYMBOL}${stageOpps.reduce((sum, o) => sum + parseFloat(o.expected_value || 0), 0).toLocaleString()}</div>
            <div class="pipeline-cards">
                ${stageOpps.map(opp => `
                    <div class="opp-card" draggable="true" data-id="${opp.id}" data-stage-id="${opp.pipeline_stage_id}" onclick="editOpp(${opp.id})">
                        <div class="name">${opp.opportunity_name}</div>
                        <div class="company">Owner: ${opp.assigned_to || 'Unassigned'}</div>
                        <div class="value">${CRM_CURRENCY_SYMBOL}${(opp.expected_value || 0).toLocaleString()}</div>
                        <div style="display:flex; justify-content:space-between; margin-top:5px;">
                            <span class="probability">${opp.probability}%</span>
                            <span class="date">${opp.expected_close_date || 'No date'}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `}).join('');
    
    initOppDragDrop();
}

function initOppDragDrop() {
    const cards = document.querySelectorAll('.opp-card');
    cards.forEach(card => {
        card.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', card.dataset.id);
        });
    });
    
    const columns = document.querySelectorAll('.pipeline-column');
    columns.forEach(col => {
        col.addEventListener('dragover', function(e) { e.preventDefault(); });
        col.addEventListener('drop', function(e) {
            e.preventDefault();
            const oppId = e.dataTransfer.getData('text/plain');
            const newStageId = col.dataset.stageId;
            if (oppId && newStageId) {
                updateOppStage(oppId, newStageId);
            }
        });
    });
}

function updateOppStage(oppId, stageId) {
    const fd = new FormData();
    fd.append('action', 'updateOpportunityStage');
    fd.append('id', oppId);
    fd.append('stage_id', stageId);
    
    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            loadPipeline();
        } else {
            toastError(res.error || 'Error updating opportunity');
        }
    });
}

function loadFormData() {
    fetch('api.php?action=listPipelineStages').then(r => r.json()).then(res => {
        const select = document.getElementById('pipeline_stage_id');
        res.data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.stage_name + ' (' + s.probability + '%)';
            select.appendChild(opt);
        });
    });
    
    fetch('api.php?action=listLeads').then(r => r.json()).then(res => {
        const select = document.getElementById('lead_id');
        if (res.data) {
            res.data.forEach(l => {
                const opt = document.createElement('option');
                opt.value = l.id;
                opt.textContent = l.company_name;
                select.appendChild(opt);
            });
        }
    });
    
    fetch('api.php?action=listUsers').then(r => r.json()).then(res => {
        const select = document.getElementById('assigned_to');
        if (res.data) {
            res.data.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = u.name;
                select.appendChild(opt);
            });
        }
    });
}

function openOppModal(id = 0) {
    document.getElementById('oppModal').classList.add('show');
    document.getElementById('oppModalTitle').textContent = id ? 'Edit Opportunity' : 'New Opportunity';
    document.getElementById('oppForm').reset();
    document.getElementById('oppId').value = id;
}

function closeOppModal() {
    document.getElementById('oppModal').classList.remove('show');
}

function editOpp(id) {
    fetch('api.php?action=getOpportunity&id=' + id)
    .then(r => r.json())
    .then(res => {
        if (res.data) {
            const opp = res.data;
            openOppModal(opp.id);
            Object.keys(opp).forEach(k => {
                const el = document.getElementById(k);
                if (el) el.value = opp[k] || '';
            });
        }
    });
}

document.getElementById('oppForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'saveOpportunity');
    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            closeOppModal();
            loadPipeline();
        } else {
            toastError(res.error || 'Error saving opportunity');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadPipeline();
    loadFormData();
});
</script>

<?php include($PathPrefix . 'includes/footer.inc'); ?>
