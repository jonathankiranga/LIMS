<link  rel="stylesheet" href="css/quickbookslook.css">
<link  rel="stylesheet" href="js/quilljs/1.3.7/quill.snow.css">
<link  rel="stylesheet" href="css/reducedcss.css">
<link  rel="stylesheet" href="css/modalcss.css">
<link  rel="stylesheet" href="css/cooltables.css">
<link  rel="stylesheet" href="css/typing.css"> 
<link  rel="stylesheet" href="css/select2.min.css">
<link href="js/tom-select/tom-select.css" rel="stylesheet" type="text/css"/>
<style>
#standardsGrid { border-collapse: collapse; width: 100%; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; }
#standardsGrid thead th { background-color: #f3f6fb; color: #333; font-weight: 600; text-align: left; border: 1px solid #d0d7de; padding: 6px 8px; white-space: nowrap; position: sticky; top: 0; z-index: 2; }
#standardsGrid tbody td { border: 1px solid #d0d7de; padding: 2px 4px; background-color: #fff; vertical-align: middle; }
#standardsGrid tbody tr:nth-child(even) td { background-color: #f9fbfd; }
#standardsGrid tbody tr:hover td { background-color: #e6f2ff; }
.gi { width: 100%; border: 1px solid transparent; padding: 4px 6px; font-size: 13px; background: transparent; border-radius: 3px; outline: none; }
.gi:focus { border-color: #4a90e2; background: #fff; box-shadow: 0 0 0 2px rgba(74,144,226,0.15); }
.gs { width: 100%; border: 1px solid transparent; padding: 4px 6px; font-size: 13px; background: transparent; border-radius: 3px; cursor: pointer; }
.gs:focus { border-color: #4a90e2; }
tr.row-dirty td { background-color: #fff9e6 !important; }
tr.row-dirty .gi, tr.row-dirty .gs { background-color: #fff9e6; }
tr.row-new td { background-color: #f0fdf4 !important; }
tr.row-new .gi, tr.row-new .gs { background-color: #f0fdf4; }
.ts-toolbar { display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap; align-items: center; background: #fff; padding: 10px 14px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.ts-toolbar .btn { font-size: 12px; }
.ts-toolbar .badge { font-size: 13px; padding: 6px 10px; }
.ts-toolbar input { width: 200px; display: inline-block; }
.table-wrap { max-height: calc(100vh - 240px); overflow-y: auto; border: 1px solid #d0d7de; border-radius: 6px; }
#standardsGrid .col-cb { width: 36px; text-align: center; }
#standardsGrid .col-code { width: 110px; }
#standardsGrid .col-method { width: 200px; }
#standardsGrid .col-actions { width: 110px; white-space: nowrap; text-align: center; }
#standardsGrid .col-actions .btn { padding: 2px 6px; font-size: 11px; }
.ts-btn-xs { font-size: 11px; }
#paramTable.table.table-sm.table-hover {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif !important;
    font-size: 12px;
}
#paramTable.table.table-sm.table-hover thead th {
    font-family: inherit !important;
    font-size: 11px;
    text-transform: none;
    padding: 5px 8px;
}
#paramTable.table.table-sm.table-hover tbody td {
    padding: 4px 8px;
    vertical-align: middle;
    line-height: 1.15;
}
#paramTable tbody td:last-child {
    text-align: center;
    white-space: nowrap;
}
#paramTable tbody td:last-child .btn {
    padding: 2px 7px;
    font-size: 11px;
    line-height: 1.2;
}
#paramTable .pgi {
    width: 100%;
    border: 1px solid transparent;
    padding: 3px 5px;
    font-size: 12px;
    background: transparent;
    border-radius: 3px;
    outline: none;
    font-family: inherit;
}
#paramTable .pgi:focus {
    border-color: #4a90e2;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(74,144,226,0.15);
}
#paramTable select.pgi { cursor: pointer; }
#paramTable .pgi[readonly] { color: #6c757d; }
#paramTable tr.row-dirty .pgi { background-color: #fff9e6; }
#parametersModal .modal-dialog {
    max-width: 1400px !important;
    width: 95% !important;
}
</style>

<div class="container mt-5">
    <h3 class="text-center">Test Standards Management</h3>

    <div class="ts-toolbar">
        <button id="addRowsBtn" class="btn btn-success"><i class="fas fa-plus"></i> Add N Rows</button>
        <button id="saveAllBtn" class="btn btn-primary"><i class="fas fa-save"></i> Save All</button>
        <button id="cloneSelectedBtn" class="btn btn-info"><i class="fas fa-copy"></i> Clone Selected</button>
        <button id="deleteSelectedBtn" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Selected</button>
        <div class="vr"></div>
        <button id="uploadStandardsBtn" class="btn btn-secondary"><i class="fas fa-upload"></i> Upload</button>
        <button id="addStandardMethodBtn" class="btn btn-secondary"><i class="fas fa-plus"></i> New Method</button>
        <div class="vr"></div>
        <button id="syncStandardsToERP" class="btn btn-primary"><i class="fas fa-cloud-upload-alt"></i> Sync to ERP</button>
        <div class="vr"></div>
        <input type="text" id="gridSearch" class="form-control form-control-sm" placeholder="Search...">
        <span id="rowCount" class="badge bg-secondary">0 rows</span>
        <span id="dirtyCount" class="badge bg-warning text-dark" style="display:none;">0 unsaved</span>
    </div>

    <div class="table-wrap">
        <table class="table table-sm" id="standardsGrid">
            <thead>
                <tr>
                    <th class="col-cb"><input type="checkbox" id="selectAll"></th>
                    <th class="col-code">Standard Code</th>
                    <th>Standard Name</th>
                    <th class="col-method">Standard Method</th>
                    <th style="width:60px;">Params</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="gridBody"></tbody>
        </table>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadStandardsModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-upload"></i> Upload Test Standards</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-target="#uploadStandardsModal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="uploadStandardsForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="excelFile" class="form-label"><i class="fas fa-file-excel"></i> Select Excel File</label>
                        <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xls,.xlsx" required>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal (rich text for Description / ApplicableRegulation) -->
<div id="standardModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-alt"></i> Test Standard Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-target="#standardModal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="standardForm">
                    <input type="hidden" id="StandardID" name="StandardID">
                    <div class="mb-3">
                        <label for="standardmethod" class="form-label">Standard Method</label>
                        <input type="text" id="standardmethod" name="standardmethod" class="iso-standard" placeholder="Enter ISO Standard...">
                    </div>
                    <div class="mb-3">
                        <label for="Code" class="form-label"><i class="fas fa-barcode"></i> Standard Code</label>
                        <input type="text" class="form-control" id="Code" name="Code" placeholder="Enter standard code" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label"><i class="fas fa-tag"></i> Standard Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter standard name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label"><i class="fas fa-info-circle"></i> Report Note</label>
                        <div class="quill-editor" id="description" name="description"></div>
                    </div>
                    <div class="mb-3">
                        <label for="ApplicableRegulation" class="form-label"><i class="fas fa-balance-scale"></i> Applicable Regulation</label>
                        <div class="quill-editor" id="ApplicableRegulation" name="ApplicableRegulation"></div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Clone Modal -->
<div id="standardcloneModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-alt"></i> Clone Test Standard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-target="#standardcloneModal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="standardCloneForm">
                    <input type="hidden" id="StandardCloneID" name="StandardCloneID">
                    <p id="StandardClonename"></p>
                    <div class="mb-3">
                        <label for="Clonestandardmethod" class="form-label">Standard Method</label>
                        <input type="text" id="Clonestandardmethod" name="standardmethod" class="iso-standard" placeholder="Enter ISO Standard...">
                    </div>
                    <div class="mb-3">
                        <label for="CloneCode" class="form-label"><i class="fas fa-barcode"></i> Standard Code</label>
                        <input type="text" class="form-control" id="CloneCode" name="Code" placeholder="Enter standard code" required>
                    </div>
                    <div class="mb-3">
                        <label for="Clonename" class="form-label"><i class="fas fa-tag"></i> Standard Name</label>
                        <input type="text" class="form-control" id="Clonename" name="name" placeholder="Enter standard name" required>
                    </div>
                    <div class="mb-3">
                        <label for="Clonedescription" class="form-label"><i class="fas fa-info-circle"></i> Report Note</label>
                        <div class="quill-editor" id="Clonedescription" name="description"></div>
                    </div>
                    <div class="mb-3">
                        <label for="CloneApplicableRegulation" class="form-label"><i class="fas fa-balance-scale"></i> Applicable Regulation</label>
                        <div class="quill-editor" id="CloneApplicableRegulation" name="ApplicableRegulation"></div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Clone Parameters</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- New Method Modal -->
<div id="methodModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> New Standard Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="methodForm">
                    <div class="mb-3">
                        <label class="form-label">Enter ISO Standard (e.g., ISO 9001:2015)</label>
                        <input type="text" id="methodInput" class="form-control iso-standard" placeholder="ISO 9001:2015">
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Add Method</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Parameters Modal -->
<div id="parametersModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-flask"></i> Parameters for: <span id="paramStandardName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <button id="addParameterBtn" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Add Parameter</button>
                    <div class="d-flex align-items-center">
                        <span id="paramDirtyCount" class="badge bg-warning text-dark me-2" style="display:none;">0 unsaved</span>
                        <button id="paramSaveChangesBtn" class="btn btn-primary btn-sm" style="display:none;"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="paramTable">
                        <thead>
                            <tr>
                                <th>Parameter Name</th>
                                <th>Unit</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Limits</th>
                                <th>Method</th>
                                <th>MRL</th>
                                <th>MRL Unit</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <p class="text-muted" id="paramEmptyMsg">No parameters yet.</p>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Parameter Modal -->
<div id="parameterAddModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="paramAddTitle">Add</span> Parameter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paramAddForm">
                    <input type="hidden" id="p_ParameterID" name="ParameterID">
                    <input type="hidden" id="p_StandardID" name="StandardID">
                    <input type="hidden" id="p_GlobalParameterID" name="GlobalParameterID">
                    <div class="mb-3">
                        <label>Search Parameter</label>
                        <select id="p_basesearch" placeholder="Search a parameter..."></select>
                    </div>
                    <div class="mb-3">
                        <label>Parameter Name</label>
                        <div id="p_ParameterName" class="form-control-sm text-muted"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label>Unit Of Measure</label>
                                <select id="p_unitofmeasure" name="UnitOfMeasure" class="form-control"></select>
                            </div>
                            <div class="mb-2">
                                <label>Min Limit</label>
                                <input type="number" step="0.01" id="p_MinLimit" name="MinLimit" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label>Max Limit</label>
                                <input type="number" step="0.01" id="p_MaxLimit" name="MaxLimit" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label>Limits</label>
                                <input type="text" id="p_Limits" name="Limits" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label>Method</label>
                                <input type="text" id="p_Method" name="Method" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label>Category</label>
                                <select id="p_Category" name="Category" class="form-control">
                                    <option value="chemical">Chemical</option>
                                    <option value="microbiological">Microbiological</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label>MRL</label>
                                <input type="number" step="0.01" id="p_MRL" name="MRL" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label>MRL Unit</label>
                                <select id="p_MRLUnit" name="MRLUnit" class="form-control"></select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="js/quilljs/1.3.7/quill.min.js"></script>
<script src="js/select2.min.js" type="text/javascript"></script>
<script src="js/tom-select/tom-select.complete.min.js" type="text/javascript"></script>
<script>
window.quillInstances = window.quillInstances || {};

$(document).ready(function() {

// ──────────────────────────────────────────────
// STATE
// ──────────────────────────────────────────────
let items = [];            // {_uid, _dirty, StandardID, StandardCode, StandardName, Description, ApplicableRegulation, sm, standard_method}
let methods = [];          // [{id, standard_method}]
let nextUid = 1;
let nextTempSid = -1;       // negative IDs for new rows pending server insert

// ──────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────
function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function stripHtml(html) {
    var d = document.createElement('div');
    d.innerHTML = html;
    return d.textContent || d.innerText || '';
}

function getFieldIndex(colIdx) {
    var map = {1:'StandardCode',2:'StandardName',3:'standard_method'};
    return map[colIdx] || null;
}

// ──────────────────────────────────────────────
// LOAD DATA
// ──────────────────────────────────────────────
function loadMethods(cb) {
    $.getJSON('ajax/gettstandardmethods.php', function(d) {
        methods = d || [];
        if (cb) cb();
    }).fail(function() { generalPurposeTypeLine('Failed to load standard methods'); });
}

function loadAllStandards() {
    $.getJSON('ajax/fetchteststandards_all.php', function(res) {
        items = (res.data || []).map(function(s) {
            return { _uid: nextUid++, _dirty: false, StandardID: s.StandardID || 0,
                StandardCode: s.StandardCode || '', StandardName: s.StandardName || '',
                Description: s.Description || '', ApplicableRegulation: s.ApplicableRegulation || '',
                sm: s.sm || 0, standard_method: s.standard_method || '' };
        });
        renderGrid();
    }).fail(function() { generalPurposeTypeLine('Failed to load standards'); });
}

function init() {
    loadMethods(function() { loadAllStandards(); });
}

// ──────────────────────────────────────────────
// RENDER GRID
// ──────────────────────────────────────────────
function renderGrid() {
    var tbody = $('#gridBody');
    tbody.empty();
    var html = '';
    items.forEach(function(s) { html += createRowHtml(s); });
    tbody.html(html);
    applyGridFilter();
    updateCounts();
}

function createRowHtml(s) {
    var sid = s.StandardID || 0;
    var cls = '';
    if (s._dirty && sid > 0) cls = 'row-dirty';
    else if (sid <= 0) cls = 'row-new';
    return '<tr data-uid="' + s._uid + '" class="' + cls + '">' +
        '<td class="col-cb"><input type="checkbox" class="row-select"></td>' +
        '<td class="col-code"><input class="gi" value="' + esc(s.StandardCode) + '"></td>' +
        '<td><input class="gi" value="' + esc(s.StandardName) + '"></td>' +
        '<td class="col-method"><input class="gi" value="' + esc(s.standard_method || '') + '"></td>' +
        '<td style="text-align:center"><button class="btn btn-outline-info btn-sm params-btn" data-sid="' + sid + '" data-name="' + esc(s.StandardName) + '" title="Manage parameters"><i class="fas fa-flask"></i></button></td>' +
        '<td class="col-actions">' +
        '<button class="btn btn-warning btn-sm ts-btn-xs edit-std" title="Edit rich text"><i class="fas fa-edit"></i></button> ' +
        '<button class="btn btn-info btn-sm ts-btn-xs clone-std" title="Clone"><i class="fas fa-copy"></i></button> ' +
        '<button class="btn btn-danger btn-sm ts-btn-xs delete-std" title="Delete"><i class="fas fa-trash"></i></button>' +
        '</td></tr>';
}

function updateCounts() {
    var total = $('#gridBody tr:visible').length;
    var dirty = $('#gridBody tr.row-dirty, #gridBody tr.row-new').length;
    $('#rowCount').text(total + ' rows');
    if (dirty > 0) { $('#dirtyCount').text(dirty + ' unsaved').show(); }
    else { $('#dirtyCount').hide(); }
}

// ──────────────────────────────────────────────
// GRID INPUT HANDLING
// ──────────────────────────────────────────────
$('#gridBody').on('change', '.gi', function() {
    var td = $(this).closest('td');
    var tr = td.closest('tr');
    var uid = parseInt(tr.data('uid'));
    var item = items.find(function(s) { return s._uid === uid; });
    if (!item) return;

    var colIdx = td.index();
    var field = getFieldIndex(colIdx);
    if (!field) return;

    var val = $(this).val();
    item[field] = val;

    // resolve method text to method ID
    if (field === 'standard_method') {
        var match = methods.find(function(m) { return m.standard_method === val; });
        item['sm'] = match ? match.id : 0;
    }

    if (item.StandardID > 0) item._dirty = true;
    updateRowVisual(tr, item);
    updateCounts();
});

function updateRowVisual(tr, item) {
    tr.removeClass('row-dirty row-new');
    if (item._dirty && item.StandardID > 0) tr.addClass('row-dirty');
    else if (item.StandardID <= 0) tr.addClass('row-new');
}

// Tab navigation
$('#gridBody').on('keydown', '.gi', function(e) {
    if (e.key !== 'Tab' && e.key !== 'Enter') return;
    e.preventDefault();
    var inputs = $('#gridBody .gi');
    var idx = inputs.index(this);
    var next = (e.key === 'Enter')
        ? inputs.filter(function(i) {
            var curRow = $(this).closest('tr').index();
            var myRow = $(inputs[idx]).closest('tr').index();
            return $(this).closest('tr').index() === myRow + 1 && $(this).closest('td').index() === $(inputs[idx]).closest('td').index();
        }).first()
        : inputs.eq(e.shiftKey ? idx - 1 : idx + 1);
    if (next.length) next.focus();
});

// ──────────────────────────────────────────────
// SELECT ALL
// ──────────────────────────────────────────────
$('#selectAll').change(function() {
    $('#gridBody .row-select').prop('checked', this.checked);
});

// ──────────────────────────────────────────────
// SEARCH / FILTER
// ──────────────────────────────────────────────
function currentSearchTerm() {
    return $('#gridSearch').val().toLowerCase().trim();
}

function applyGridFilter() {
    var q = currentSearchTerm();
    $('#gridBody tr').each(function() {
        var text = $(this).find('input.gi').map(function() {
            return $(this).val();
        }).get().join(' ').toLowerCase();
        $(this).toggle(!q || text.indexOf(q) !== -1);
    });
}

$('#gridSearch').on('input', function() {
    applyGridFilter();
    updateCounts();
});

// ──────────────────────────────────────────────
// ADD N ROWS
// ──────────────────────────────────────────────
$('#addRowsBtn').click(function() {
    var count = prompt('How many rows to add?', '10');
    if (!count) return;
    count = parseInt(count);
    if (isNaN(count) || count < 1) return;

    for (var i = 1; i <= count; i++) {
        items.push({ _uid: nextUid++, _dirty: false, StandardID: 0,
            StandardCode: '', StandardName: '', Description: '',
            ApplicableRegulation: '', sm: 0, standard_method: '' });
    }
    renderGrid();
    setTimeout(function() { $('#gridBody .gi').first().focus(); }, 50);
});

// ──────────────────────────────────────────────
// SAVE ALL
// ──────────────────────────────────────────────
$('#saveAllBtn').click(function() {
    var dirty = items.filter(function(s) { return s._dirty || s.StandardID <= 0; });
    if (!dirty.length) { generalPurposeTypeLine('No changes to save.'); return; }

    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    var payload = dirty.map(function(s) { return {
        StandardID: s.StandardID,
        StandardCode: s.StandardCode,
        StandardName: s.StandardName,
        Description: s.Description,
        ApplicableRegulation: s.ApplicableRegulation,
        sm: s.sm || 0
    };});

    $.ajax({
        url: 'ajax/batch_save_standards.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ standards: payload, deleted: [] }),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save All');
            if (res.errors && res.errors.length) {
                generalPurposeTypeLine('Saved ' + res.saved + ' rows. Errors: ' + res.errors.join(' | '));
            } else {
                generalPurposeTypeLine('Saved ' + res.saved + ' rows successfully.');
            }
            loadAllStandards();
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save All');
            generalPurposeTypeLine('Save failed. Check console.');
        }
    });
});

// ──────────────────────────────────────────────
// CLONE SELECTED
// ──────────────────────────────────────────────
$('#cloneSelectedBtn').click(function() {
    var checked = $('#gridBody .row-select:checked');
    if (!checked.length) { generalPurposeTypeLine('Select rows to clone.'); return; }

    checked.each(function() {
        var tr = $(this).closest('tr');
        var uid = parseInt(tr.data('uid'));
        var src = items.find(function(s) { return s._uid === uid; });
        if (!src) return;
        items.push({ _uid: nextUid++, _dirty: false, StandardID: 0, StandardCode: src.StandardCode,
            StandardName: src.StandardName + ' (copy)', Description: src.Description,
            ApplicableRegulation: src.ApplicableRegulation, sm: src.sm, standard_method: src.standard_method });
    });
    renderGrid();
    generalPurposeTypeLine('Cloned ' + checked.length + ' row(s). Click Save All to persist.');
});

// ──────────────────────────────────────────────
// DELETE SELECTED
// ──────────────────────────────────────────────
$('#deleteSelectedBtn').click(function() {
    var checked = $('#gridBody .row-select:checked');
    if (!checked.length) { generalPurposeTypeLine('Select rows to delete.'); return; }
    if (!confirm('Delete ' + checked.length + ' selected standard(s)?')) return;

    var idsToDelete = [];
    var uidToRemove = [];
    var btn = $(this);
    btn.prop('disabled', true);

    checked.each(function() {
        var tr = $(this).closest('tr');
        var uid = parseInt(tr.data('uid'));
        var item = items.find(function(s) { return s._uid === uid; });
        if (!item) return;
        uidToRemove.push(uid);
        if (item.StandardID > 0) idsToDelete.push(item.StandardID);
    });

    if (idsToDelete.length) {
        $.ajax({
            url: 'ajax/batch_save_standards.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ standards: [], deleted: idsToDelete }),
            dataType: 'json',
            success: function() {
                btn.prop('disabled', false);
                items = items.filter(function(s) { return uidToRemove.indexOf(s._uid) === -1; });
                renderGrid();
                generalPurposeTypeLine('Deleted ' + idsToDelete.length + ' row(s).');
            },
            error: function() { btn.prop('disabled', false); generalPurposeTypeLine('Delete failed.'); }
        });
    } else {
        items = items.filter(function(s) { return uidToRemove.indexOf(s._uid) === -1; });
        renderGrid();
        btn.prop('disabled', false);
        generalPurposeTypeLine('Removed new row(s).');
    }
});

// ──────────────────────────────────────────────
// SINGLE DELETE
// ──────────────────────────────────────────────
$('#gridBody').on('click', '.delete-std', function() {
    var tr = $(this).closest('tr');
    var uid = parseInt(tr.data('uid'));
    var item = items.find(function(s) { return s._uid === uid; });
    if (!item) return;
    if (!confirm('Delete standard "' + (item.StandardName || item.StandardCode) + '"?')) return;

    if (item.StandardID > 0) {
        $.post('ajax/batch_save_standards.php', JSON.stringify({ standards: [], deleted: [item.StandardID] }),
            function() {
                items = items.filter(function(s) { return s._uid !== uid; });
                renderGrid();
                generalPurposeTypeLine('Deleted.');
            }, 'json');
    } else {
        items = items.filter(function(s) { return s._uid !== uid; });
        renderGrid();
    }
});

// ──────────────────────────────────────────────
// DETAIL MODAL (Edit rich text)
// ──────────────────────────────────────────────
$('#gridBody').on('click', '.edit-std', function() {
    var tr = $(this).closest('tr');
    var uid = parseInt(tr.data('uid'));
    var item = items.find(function(s) { return s._uid === uid; });
    if (!item) return;

    // Collect current input values from the grid row (user may have changed them without saving)
    var inputs = tr.find('.gi');
    var currentCode = inputs.eq(0).val();
    var currentName = inputs.eq(1).val();
    var currentMethod = inputs.eq(2).val();

    $('#StandardID').val(item.StandardID);
    $('#Code').val(currentCode);
    $('#name').val(currentName);
    $('#standardmethod').val(currentMethod);

    var dq = window.quillInstances['description'];
    var rq = window.quillInstances['ApplicableRegulation'];
    if (dq) dq.root.innerHTML = item.Description || '';
    if (rq) rq.root.innerHTML = item.ApplicableRegulation || '';
    generalPurposeTypeLine('Editing: ' + currentName);
    new bootstrap.Modal(document.getElementById('standardModal')).show();
});

// ──────────────────────────────────────────────
// DETAIL MODAL SAVE
// ──────────────────────────────────────────────
$('#standardForm').on('submit', function(e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    var dq = window.quillInstances['description'];
    var rq = window.quillInstances['ApplicableRegulation'];
    var fd = {
        StandardID: $('#StandardID').val(),
        Code: $('#Code').val(),
        name: $('#name').val(),
        standardmethod: $('#standardmethod').val(),
        description: dq ? dq.root.innerHTML : '',
        applicableregulation: rq ? rq.root.innerHTML : ''
    };

    $.ajax({
        url: 'ajax/teststandardshandler.php',
        type: 'POST',
        data: fd,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
            var modal = bootstrap.Modal.getInstance(document.getElementById('standardModal'));
            if (modal) modal.hide();
            generalPurposeTypeLine(res.message);
            if (res.success) loadAllStandards();
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
            generalPurposeTypeLine('Error saving.');
        }
    });
});

// ──────────────────────────────────────────────
// CLONE MODAL
// ──────────────────────────────────────────────
$('#gridBody').on('click', '.clone-std', function() {
    var tr = $(this).closest('tr');
    var uid = parseInt(tr.data('uid'));
    var item = items.find(function(s) { return s._uid === uid; });
    if (!item) return;
    $('#StandardCloneID').val(item.StandardID);
    var name = item.StandardName || '';
    $('#StandardClonename').text('Clone All Parameters from ' + name);
    new bootstrap.Modal(document.getElementById('standardcloneModal')).show();
});

$('#standardCloneForm').on('submit', function(e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cloning...');

    var dq = window.quillInstances['Clonedescription'];
    var rq = window.quillInstances['CloneApplicableRegulation'];
    var fd = {
        StandardCloneID: $('#StandardCloneID').val(),
        Code: $('#CloneCode').val(),
        name: $('#Clonename').val(),
        standardmethod: $('#Clonestandardmethod').val(),
        description: dq ? dq.root.innerHTML : '',
        applicableregulation: rq ? rq.root.innerHTML : ''
    };

    $.ajax({
        url: 'ajax/teststandardshandlerandclone.php',
        type: 'POST',
        data: fd,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Clone Parameters');
            var modal = bootstrap.Modal.getInstance(document.getElementById('standardcloneModal'));
            if (modal) modal.hide();
            generalPurposeTypeLine(res.message);
            if (res.success) loadAllStandards();
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Clone Parameters');
            generalPurposeTypeLine('Error cloning.');
        }
    });
});

// ──────────────────────────────────────────────
// UPLOAD
// ──────────────────────────────────────────────
$('#uploadStandardsBtn').click(function() {
    new bootstrap.Modal(document.getElementById('uploadStandardsModal')).show();
});

$('#uploadStandardsForm').on('submit', function(e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');
    var fd = new FormData(this);
    $.ajax({
        url: 'ajax/upload_standards_handler.php',
        type: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
            var r = typeof res === 'string' ? JSON.parse(res) : res;
            if (r.success) {
                generalPurposeTypeLine(r.message);
                bootstrap.Modal.getOrCreateInstance($('#uploadStandardsModal')[0]).hide();
                loadAllStandards();
            } else {
                generalPurposeTypeLine('Error: ' + r.message);
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
            generalPurposeTypeLine('Upload failed.');
        }
    });
});

// ──────────────────────────────────────────────
// SYNC TO ERP
// ──────────────────────────────────────────────
$('#syncStandardsToERP').click(function() {
    if (!confirm('Sync all test standards and parameter links to ERP?')) return;
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
    $.ajax({
        url: 'ajax/syncTeststandardsToERP.php',
        type: 'POST',
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                generalPurposeTypeLine(res.message);
            } else {
                generalPurposeTypeLine('Error: ' + res.message);
            }
        },
        error: function() {
            generalPurposeTypeLine('Sync failed. Check console.');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt"></i> Sync to ERP');
        }
    });
});

// ──────────────────────────────────────────────
// NEW METHOD MODAL (TomSelect)
// ──────────────────────────────────────────────
$('#addStandardMethodBtn').click(function() {
    $('#methodInput').val('');
    new bootstrap.Modal(document.getElementById('methodModal')).show();
    setTimeout(function() { $('#methodInput').focus(); }, 300);
});

$('#methodForm').on('submit', function(e) {
    e.preventDefault();
    var val = $('#methodInput').val().trim().toUpperCase().replace(/^ISO\s*/, 'ISO ');
    var match = val.match(/^ISO\s*([0-9]{1,5})\s*[:\-]?\s*([0-9]{4})$/);
    if (!match) { generalPurposeTypeLine('Invalid ISO format. Example: ISO 9001:2015'); return; }
    val = 'ISO ' + match[1] + ':' + match[2];

    $.ajax({
        url: 'ajax/save_iso_standard.php',
        type: 'POST',
        dataType: 'json',
        data: { standard_method: val },
        success: function(res) {
            if (res.success) {
                generalPurposeTypeLine('Method added: ' + val);
                bootstrap.Modal.getOrCreateInstance($('#methodModal')[0]).hide();
                loadMethods(function() { renderGrid(); });
            } else {
                generalPurposeTypeLine('Error: ' + res.message);
            }
        },
        error: function() { generalPurposeTypeLine('Save failed.'); }
    });
});

// ──────────────────────────────────────────────
// TOMSELECT INIT (for modals)
// ──────────────────────────────────────────────
function initTomSelect(id) {
    if (document.querySelector(id + ' .ts-wrapper')) return; // already init'd
    new TomSelect(id, {
        create: function(input, callback) {
            var isoRegex = /^(ISO(?:\/[A-Z]+)?)\s?\d{3,5}(?::\d{4})?$/i;
            if (!isoRegex.test(input)) {
                generalPurposeTypeLine("Invalid ISO format. Example: ISO 9001:2015, ISO/IEC 27001:2022");
                return false;
            }
            $.ajax({
                url: 'ajax/save_iso_standard.php',
                type: 'POST',
                dataType: 'json',
                data: { standard_method: input },
                success: function(response) {
                    if (response.success) {
                        callback({ value: response.id, text: response.standard_method });
                        loadMethods(function() { renderGrid(); });
                    } else {
                        generalPurposeTypeLine(response.message);
                        return false;
                    }
                },
                error: function() { generalPurposeTypeLine('Error saving standard'); return false; }
            });
        },
        valueField: 'id', labelField: 'standard_method', searchField: 'standard_method',
        maxItems: 1,
        load: function(query, callback) {
            if (!query.length) return callback();
            $.ajax({
                url: 'ajax/gettstandardmethods.php',
                type: 'GET', dataType: 'json',
                success: function(res) { callback(res); },
                error: function() { callback(); }
            });
        }
    });
}

// Initialize TomSelect on modal inputs after they're visible
$('#standardModal').on('shown.bs.modal', function() { initTomSelect('#standardmethod'); });
$('#standardcloneModal').on('shown.bs.modal', function() { initTomSelect('#Clonestandardmethod'); });

// ──────────────────────────────────────────────
// BLUR AUTO-SAVE ISO (existing behavior)
// ──────────────────────────────────────────────
$(document).on('blur', '#standardmethod, #Clonestandardmethod', function() {
    var val = $(this).val().trim();
    if (!val) return;
    val = val.toUpperCase().replace(/^ISO\s*/, 'ISO ');
    var match = val.match(/^ISO\s*([0-9]{1,5})\s*[:\-]?\s*([0-9]{4})$/);
    if (match) {
        val = 'ISO ' + match[1] + ':' + match[2];
        $(this).val(val);
        $.ajax({
            url: 'ajax/save_iso_standard.php',
            type: 'POST', data: { standard_method: val },
            success: function(res) { if (res.success) loadMethods(function() { renderGrid(); }); },
            error: function() {}
        });
    } else {
        generalPurposeTypeLine('Invalid ISO format. Example: ISO 9001:2015');
    }
});

// ──────────────────────────────────────────────
// QUILL
// ──────────────────────────────────────────────
function initializeQuillEditors() {
    document.querySelectorAll('.quill-editor').forEach(function(editor) {
        var quill = new Quill(editor, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'script': 'sub' }, { 'script': 'super' }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }]
                ]
            }
        });
        window.quillInstances[editor.id] = quill;
    });
}
initializeQuillEditors();

// ──────────────────────────────────────────────
// PARAMETER MODAL
// ──────────────────────────────────────────────
var currentParamStandardID = 0;

// Populate UnitOfMeasure and MRLUnit selects
var paramUnitList = ['mg/L','mg/kg','µg/L','µg/kg','g/L','g/kg','%','ppm','ppb','CFU/mL','CFU/g','MPN/100mL','NTU','pH','mS/cm','µS/cm','mg/m³','µg/m³','Bq/L','Bq/kg','°C','mm','cm','m','L','mL','µL','N','% v/v','% w/w'];

function unitOptionsHtml(selected) {
    var html = '<option value="">-- Select --</option>';
    paramUnitList.forEach(function(u) {
        html += '<option value="' + u + '"' + (String(u) === String(selected) ? ' selected' : '') + '>' + u + '</option>';
    });
    return html;
}

function populateUnitSelects() {
    $('#p_unitofmeasure').html(unitOptionsHtml(''));
    $('#p_MRLUnit').html(unitOptionsHtml(''));
}
populateUnitSelects();

// Auto-calculate Limits from Min/Max
$('#p_MinLimit, #p_MaxLimit').on('input', function() {
    var min = $('#p_MinLimit').val();
    var max = $('#p_MaxLimit').val();
    if (min !== '' && max !== '') {
        $('#p_Limits').val(min + ' - ' + max);
    } else if (min !== '') {
        $('#p_Limits').val('≥ ' + min);
    } else if (max !== '') {
        $('#p_Limits').val('≤ ' + max);
    } else {
        $('#p_Limits').val('');
    }
});

// TomSelect for BaseParameters search
var tsBaseSearch = null;
function initBaseSearch() {
    if (tsBaseSearch) { tsBaseSearch.destroy(); tsBaseSearch = null; }
    if ($('#p_basesearch').hasClass('ts-wrapper')) return;
    tsBaseSearch = new TomSelect('#p_basesearch', {
        valueField: 'ParameterID',
        labelField: 'ParameterName',
        searchField: 'ParameterName',
        maxItems: 1,
        load: function(query, callback) {
            if (!query.length) return callback();
            fetch('ajax/fetchbaseselect2.php?q=' + encodeURIComponent(query))
                .then(function(res) { return res.json(); })
                .then(function(json) { callback(json.data); })
                .catch(function() { callback(); });
        },
        onItemAdd: function(value, item) {
            $('#p_GlobalParameterID').val(value);
            $('#p_ParameterName').text(item.innerText);
        }
    });
}

// Open parameters modal
$('#gridBody').on('click', '.params-btn', function() {
    var sid = $(this).data('sid');
    if (sid <= 0) {
        generalPurposeTypeLine('Save the standard first before adding parameters.');
        return;
    }
    var name = $(this).data('name');
    currentParamStandardID = sid;
    $('#paramStandardName').text(name);
    $('#paramTable tbody').empty();
    $('#paramEmptyMsg').show();
    loadParameters(sid);
    new bootstrap.Modal(document.getElementById('parametersModal')).show();
});

function pgiNum(v) {
    return (v === null || v === undefined) ? '' : String(v);
}

function pgiCategorySelect(selected) {
    var html = '<select class="pgi" data-field="Category">';
    var cats = [['chemical', 'Chemical'], ['microbiological', 'Microbiological']];
    cats.forEach(function(c) {
        html += '<option value="' + c[0] + '"' + (String(selected) === c[0] ? ' selected' : '') + '>' + c[1] + '</option>';
    });
    html += '</select>';
    return html;
}

function paramFieldVal(tr, field) {
    var el = tr.find('.pgi[data-field="' + field + '"]');
    if (!el.length) return '';
    return el.val() == null ? '' : String(el.val());
}

function paramRowDataVal(tr, field) {
    var v = tr.data(field);
    return (v === null || v === undefined) ? '' : String(v);
}

function isParamRowDirty(tr) {
    var fields = ['ParameterName', 'UnitOfMeasure', 'MinLimit', 'MaxLimit', 'Limits', 'Method', 'MRL', 'MRLUnit', 'Category'];
    for (var i = 0; i < fields.length; i++) {
        var cur = paramFieldVal(tr, fields[i]);
        var orig = paramRowDataVal(tr, fields[i].toLowerCase());
        if (cur !== orig) return true;
    }
    return false;
}

function refreshParamDirty() {
    var n = $('#paramTable tbody tr.row-dirty').length;
    var btn = $('#paramSaveChangesBtn');
    var badge = $('#paramDirtyCount');
    if (n > 0) {
        badge.text(n + ' unsaved').show();
        btn.show();
    } else {
        badge.hide();
        btn.hide();
    }
    return n;
}

$('#paramTable').on('input change', '.pgi', function() {
    var tr = $(this).closest('tr');
    if (!tr.length) return;
    tr.toggleClass('row-dirty', isParamRowDirty(tr));
    refreshParamDirty();
});

$('#paramTable').on('input change', '.pgi[data-field="MinLimit"], .pgi[data-field="MaxLimit"]', function() {
    var tr = $(this).closest('tr');
    var min = paramFieldVal(tr, 'MinLimit');
    var max = paramFieldVal(tr, 'MaxLimit');
    var limits = '';
    if (min !== '' && max !== '') limits = min + ' - ' + max;
    else if (min !== '') limits = '\u2265 ' + min;
    else if (max !== '') limits = '\u2264 ' + max;
    tr.find('.pgi[data-field="Limits"]').val(limits);
});

$('#paramSaveChangesBtn').click(function() {
    var $btn = $(this);
    var dirtyRows = $('#paramTable tbody tr.row-dirty');
    if (!dirtyRows.length) { refreshParamDirty(); return; }

    var queue = [];
    dirtyRows.each(function() {
        queue.push({ tr: $(this) });
    });
    if (!queue.length) { refreshParamDirty(); return; }

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    var idx = 0;
    var errors = [];

    function saveNext() {
        if (idx >= queue.length) {
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Changes');
            if (errors.length) {
                generalPurposeTypeLine('Saved ' + (queue.length - errors.length) + '/' + queue.length + ' row(s). Errors: ' + errors.join(' | '));
            } else {
                generalPurposeTypeLine('Saved ' + queue.length + ' row(s).');
            }
            loadParameters(currentParamStandardID);
            return;
        }
        var item = queue[idx++];
        var tr = item.tr;
        var fd = {
            ParameterID: tr.data('pid'),
            StandardID: currentParamStandardID,
            ParameterName: paramFieldVal(tr, 'ParameterName'),
            UnitOfMeasure: paramFieldVal(tr, 'UnitOfMeasure'),
            MinLimit: paramFieldVal(tr, 'MinLimit'),
            MaxLimit: paramFieldVal(tr, 'MaxLimit'),
            Limits: paramFieldVal(tr, 'Limits'),
            Method: paramFieldVal(tr, 'Method'),
            GlobalParameterID: tr.data('baseid') || '',
            Category: paramFieldVal(tr, 'Category'),
            MRL: paramFieldVal(tr, 'MRL'),
            MRLUnit: paramFieldVal(tr, 'MRLUnit')
        };
        $.ajax({
            url: 'ajax/saveparameter.php',
            type: 'POST',
            data: fd,
            dataType: 'json',
            success: function(res) {
                if (!res.success) errors.push('param #' + item.pid + ': ' + (res.message || 'save failed'));
                saveNext();
            },
            error: function() {
                errors.push('param #' + item.pid + ' save failed');
                saveNext();
            }
        });
    }
    saveNext();
});

function loadParameters(sid) {
    $.getJSON('ajax/fetchparameters.php', { standardID: sid }, function(res) {
        var tbody = $('#paramTable tbody');
        tbody.empty();
        if (res.data && res.data.length) {
            $('#paramEmptyMsg').hide();
            res.data.forEach(function(p) {
                var pid = p.ParameterID;
                var rowData =
                    'data-pid="' + pid + '" ' +
                    'data-baseid="' + esc(p.BaseID || '') + '" ' +
                    'data-parametername="' + esc(p.ParameterName || '') + '" ' +
                    'data-unitofmeasure="' + esc(p.UnitOfMeasure || '') + '" ' +
                    'data-minlimit="' + esc(pgiNum(p.MinLimit)) + '" ' +
                    'data-maxlimit="' + esc(pgiNum(p.MaxLimit)) + '" ' +
                    'data-limits="' + esc(p.Limits || '') + '" ' +
                    'data-method="' + esc(p.Method || '') + '" ' +
                    'data-mrl="' + esc(pgiNum(p.MRL)) + '" ' +
                    'data-mrlunit="' + esc(p.MRLUnit || '') + '" ' +
                    'data-category="' + esc(p.Category || '') + '"';
                tbody.append(
                    '<tr ' + rowData + '>' +
                    '<td><input class="pgi" data-field="ParameterName" value="' + esc(p.ParameterName || '') + '"></td>' +
                    '<td><select class="pgi" data-field="UnitOfMeasure">' + unitOptionsHtml(p.UnitOfMeasure || '') + '</select></td>' +
                    '<td><input type="number" step="0.01" class="pgi" data-field="MinLimit" value="' + esc(pgiNum(p.MinLimit)) + '"></td>' +
                    '<td><input type="number" step="0.01" class="pgi" data-field="MaxLimit" value="' + esc(pgiNum(p.MaxLimit)) + '"></td>' +
                    '<td><input class="pgi" data-field="Limits" readonly value="' + esc(p.Limits || '') + '"></td>' +
                    '<td><input class="pgi" data-field="Method" value="' + esc(p.Method || '') + '"></td>' +
                    '<td><input type="number" step="0.01" class="pgi" data-field="MRL" value="' + esc(pgiNum(p.MRL)) + '"></td>' +
                    '<td><select class="pgi" data-field="MRLUnit">' + unitOptionsHtml(p.MRLUnit || '') + '</select></td>' +
                    '<td>' + pgiCategorySelect(p.Category || '') + '</td>' +
                    '<td>' +
                    '<button class="btn btn-warning btn-sm edit-param" title="Edit">' +
                    '<i class="fas fa-edit"></i></button> ' +
                    '<button class="btn btn-danger btn-sm delete-param" ' +
                    'data-parameterid="' + pid + '">' +
                    '<i class="fas fa-trash-alt"></i></button>' +
                    '</td></tr>'
                );
            });
        } else {
            $('#paramEmptyMsg').show();
        }
        refreshParamDirty();
    });
}

// Add Parameter button
$('#addParameterBtn').click(function() {
    $('#paramAddTitle').text('Add');
    $('#paramAddForm')[0].reset();
    $('#p_ParameterID').val('');
    $('#p_StandardID').val(currentParamStandardID);
    $('#p_GlobalParameterID').val('');
    $('#p_ParameterName').text('');
    $('#p_Limits').val('');
    if (tsBaseSearch) { tsBaseSearch.clear(); tsBaseSearch.clearOptions(); }
    initBaseSearch();
    new bootstrap.Modal(document.getElementById('parameterAddModal')).show();
});

// Edit Parameter
$(document).on('click', '.edit-param', function() {
    var tr = $(this).closest('tr');
    var pid = tr.data('pid');
    var paramName = paramFieldVal(tr, 'ParameterName');
    var baseId = tr.data('baseid') || '';
    $('#paramAddTitle').text('Edit');
    $('#p_ParameterID').val(pid);
    $('#p_StandardID').val(currentParamStandardID);
    $('#p_GlobalParameterID').val(baseId);
    $('#p_ParameterName').text(paramName);
    $('#p_unitofmeasure').val(paramFieldVal(tr, 'UnitOfMeasure'));
    $('#p_MinLimit').val(paramFieldVal(tr, 'MinLimit'));
    $('#p_MaxLimit').val(paramFieldVal(tr, 'MaxLimit'));
    $('#p_Limits').val(paramFieldVal(tr, 'Limits'));
    $('#p_Method').val(paramFieldVal(tr, 'Method'));
    $('#p_MRL').val(paramFieldVal(tr, 'MRL'));
    $('#p_MRLUnit').val(paramFieldVal(tr, 'MRLUnit'));
    $('#p_Category').val(paramFieldVal(tr, 'Category'));
    initBaseSearch();
    if (tsBaseSearch && baseId && paramName) {
        tsBaseSearch.addOption({ ParameterID: baseId, ParameterName: paramName });
        tsBaseSearch.addItem(baseId);
    }
    new bootstrap.Modal(document.getElementById('parameterAddModal')).show();
});

// Save Parameter
$('#paramAddForm').on('submit', function(e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    var fd = {
        ParameterID: $('#p_ParameterID').val(),
        StandardID: $('#p_StandardID').val(),
        ParameterName: $('#p_ParameterName').text(),
        UnitOfMeasure: $('#p_unitofmeasure').val(),
        MinLimit: $('#p_MinLimit').val(),
        MaxLimit: $('#p_MaxLimit').val(),
        Limits: $('#p_Limits').val(),
        Method: $('#p_Method').val(),
        GlobalParameterID: $('#p_GlobalParameterID').val(),
        Category: $('#p_Category').val(),
        MRL: $('#p_MRL').val(),
        MRLUnit: $('#p_MRLUnit').val()
    };
    $.ajax({
        url: 'ajax/saveparameter.php',
        type: 'POST',
        data: fd,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
            if (res.success) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('parameterAddModal'));
                if (modal) modal.hide();
                generalPurposeTypeLine(res.message);
                loadParameters(currentParamStandardID);
            } else {
                generalPurposeTypeLine('Error: ' + res.message);
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
            generalPurposeTypeLine('Save failed.');
        }
    });
});

// Delete Parameter
$(document).on('click', '.delete-param', function() {
    if (!confirm('Delete this parameter?')) return;
    var pid = $(this).data('parameterid');
    var btn = $(this);
    btn.prop('disabled', true);
    $.ajax({
        url: 'ajax/deleteparameter.php',
        type: 'POST',
        data: { parameterID: pid, standardID: currentParamStandardID },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                generalPurposeTypeLine('Parameter deleted.');
                loadParameters(currentParamStandardID);
            } else {
                generalPurposeTypeLine('Error: ' + res.message);
                btn.prop('disabled', false);
            }
        },
        error: function() {
            generalPurposeTypeLine('Delete failed.');
            btn.prop('disabled', false);
        }
    });
});

// ──────────────────────────────────────────────
// START
// ──────────────────────────────────────────────
init();

}); // end ready
</script>
