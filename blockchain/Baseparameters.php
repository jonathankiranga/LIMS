<link rel="stylesheet" href="css/quickbookslook.css">
<link rel="stylesheet" href="js/quilljs/1.3.7/quill.snow.css">
<link rel="stylesheet" href="css/reducedcss.css">
<link rel="stylesheet" href="css/modalcss.css">
<link rel="stylesheet" href="css/cooltables.css">
<link rel="stylesheet" href="css/typing.css">
<link rel="stylesheet" href="css/parametersetup.css">

<style>
    .badge-resulttype { padding: 3px 8px; border-radius: 3px; font-size: 11px; }
    .badge-qty { background: #007bff; color: white; }
    .badge-qual { background: #28a745; color: white; }
    .badge-range { background: #ffc107; color: black; }
    .badge-none { background: #6c757d; color: white; }

    .bp-table { font-size: 13px; border-collapse: separate; border-spacing: 0; }
    .bp-table thead th { background: #2c3e50; color: white; font-weight: 600; padding: 10px 8px; border-bottom: 2px solid #1a252f; white-space: nowrap; }
    .bp-table tbody tr { transition: background-color 0.15s ease; }
    .bp-table tbody tr:nth-child(even) { background-color: #f8f9fc; }
    .bp-table tbody tr:nth-child(odd) { background-color: #ffffff; }
    .bp-table tbody tr:hover { background-color: #eaf0f9 !important; }
    .bp-table tbody td { padding: 8px; vertical-align: middle; border-bottom: 1px solid #e3e6f0; }
    .bp-table .col-id { width: 55px; text-align: center; }
    .bp-table .col-name { max-width: 300px; }
    .bp-table .col-limits { max-width: 140px; }
    .bp-table .col-minmax { width: 70px; text-align: center; }
    .bp-table .col-unit { width: 80px; }
    .bp-table .col-category { width: 120px; text-align: center; }
    .bp-table .col-resulttype { width: 105px; text-align: center; }
    .bp-table .col-actions { width: 140px; text-align: center; white-space: nowrap; }

    .filter-bar { background: #f8f9fc; border: 1px solid #e3e6f0; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
    .pagination-controls { background: #f8f9fc; border: 1px solid #e3e6f0; border-radius: 6px; padding: 10px 15px; }
    .pagination-controls .page-indicator { font-weight: 600; color: #2c3e50; }

    .bp-table thead th { padding: 6px 8px; font-size: 11px; }
    .bp-table tbody td { padding: 4px 6px; font-size: 12px; line-height: 1.15; }
    .bp-table tbody td:last-child .btn { padding: 2px 7px; font-size: 11px; line-height: 1.2; }
    .bp-table .badge-resulttype { padding: 2px 6px; font-size: 10px; }

    #ParameterModalRecord .modal-content,
    #ParameterModalRecord .modal-body { padding: 8px 10px; }
    #ParameterModalRecord .modal-body { gap: 0.4rem !important; }
    #ParameterModalRecord .modal-header { padding: 8px 12px; }
    #ParameterForm .form-label { margin-bottom: 0.1rem; font-size: 0.78rem; }
    #ParameterForm .form-control {
        height: 28px;
        min-height: 28px;
        padding: 0.15rem 0.4rem;
        font-size: 0.78rem;
        line-height: 1.15;
    }
    #ParameterForm .quill-editor .ql-toolbar { padding: 2px 4px; }
    #ParameterForm .quill-editor .ql-container { min-height: 34px; font-size: 0.8rem; }
    #ParameterForm .row { margin-bottom: 0; }
    #ParameterForm .row > .col-6 { padding-left: 0.25rem; padding-right: 0.25rem; }
    #ParameterForm .mb-2 { margin-bottom: 0.3rem !important; }
    #ParameterForm .mb-3 { margin-bottom: 0.5rem !important; }
    #ParameterForm hr { margin: 0.4rem 0; }
    #ParameterForm h6 { margin: 0.2rem 0 0.3rem; font-size: 0.85rem; }
</style>

<div class="container mt-5">
    <h3 class="text-center">Global Parameters</h3>
</div>

<!-- File Upload Modal -->
<div id="uploadStandardsModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload"></i>Upload parameters from Excel<span id="uploadstandardName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ">
                <form id="uploadStandardsForm" enctype="multipart/form-data">
                   <input type="hidden" id="uploadStandardID" />
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">
                            <i class="fas fa-file-excel"></i> Select Excel File
                        </label>
                        <ul><li>Parameter Name</li>
                            <li>Neutrality ID (if applicable)</li>
                            <li>TDS ID (If applicable)</li>
                            <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xls,.xlsx" required>
                    </div>
                   <div id="showstatus"></div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Parameter Modal -->
<div id="ParameterModalRecord" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="padding: 10px;">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="addstandardName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-target="#ParameterModalRecord" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex flex-column gap-3" style="padding: 10px;">
                       <form id="ParameterForm">
                            <input type="hidden"  id="ParameterIDForm" name="ParameterIDForm">
                            <div class="mb-3">
                            <label for="ParameterName" class="form-label">Name ( Must be unique)</label>
                            <div class="quill-editor form-control-sm" id="ParameterNameForm" name="ParameterName"></div>
                        </div>

                        <div class="d-flex flex-column mb-2">
                            <label for="neutralityID" class="form-label">Neutrality ID</label>
                            <select id="neutralityID" name="neutralityID" class="form-control"><option></option></select>
                        </div>

                        <div class="d-flex flex-column mb-2">
                            <label for="tdsID" class="form-label">TDS Calculator ID</label>
                            <select id="tdsID" name="tdsID" class="form-control"><option></option></select>
                        </div>

                        <div class="d-flex flex-column mb-2">
                            <label for="resultType" class="form-label">Result Type</label>
                            <select id="resultType" name="resultType" class="form-control">
                                <option value="">Not Set</option>
                                <option value="quantitativeField">Quantitative</option>
                                <option value="qualitativeField">Qualitative</option>
                                <option value="rangeField">Range</option>
                            </select>
                        </div>

                        <hr>
                        <h6>Standard Limits</h6>

                        <div class="d-flex flex-column mb-2">
                            <label for="limits" class="form-label">Limits (text)</label>
                            <input type="text" id="limits" name="limits" class="form-control" placeholder="e.g. 0.5 - 1.0">
                        </div>

                        <div class="row">
                            <div class="col-6 d-flex flex-column mb-2">
                                <label for="minLimit" class="form-label">Min Limit</label>
                                <input type="number" step="0.01" id="minLimit" name="minLimit" class="form-control">
                            </div>
                            <div class="col-6 d-flex flex-column mb-2">
                                <label for="maxLimit" class="form-label">Max Limit</label>
                                <input type="number" step="0.01" id="maxLimit" name="maxLimit" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 d-flex flex-column mb-2">
                                <label for="unitOfMeasure" class="form-label">Unit of Measure</label>
                                <input type="text" id="unitOfMeasure" name="unitOfMeasure" class="form-control" placeholder="e.g. mg/l">
                            </div>
                            <div class="col-6 d-flex flex-column mb-2">
                                <label for="category" class="form-label">Category</label>
                                <select id="category" name="category" class="form-control">
                                    <option value="chemical">Chemical</option>
                                    <option value="microbiological">Microbiological</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex flex-column mb-2">
                            <label for="method" class="form-label">Method</label>
                            <input type="text" id="method" name="method" class="form-control" placeholder="e.g. ISO 5983-1">
                        </div>

                         <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<button id="addParameterBtn" class="btn btn-secondary mb-3">
    <i class="fas fa-plus"></i>Add Global Parameter
</button>

<button id="syncToERP" class="btn btn-primary mb-3">
    <i class="fas fa-cloud-upload-alt"></i> Sync to ERP
</button>

<button class="btn btn-secondary btn-sm mb-3" data-id=""  data-name="" id="importParametersBtn">
    <i class="fas fa-file-excel"></i> Import Parameters
</button>

<div class="d-flex gap-2 align-items-center filter-bar">
    <select id="filterColumn" class="form-control form-control-sm" style="width:180px">
        <option value="-1">All Columns</option>
        <option value="0">ID</option>
        <option value="1">Parameter Name</option>
        <option value="2">Limits</option>
        <option value="3">Min</option>
        <option value="4">Max</option>
        <option value="5">Unit</option>
        <option value="6">Category</option>
        <option value="7">ResultType</option>
    </select>
    <select id="filterOperator" class="form-control form-control-sm" style="width:130px">
        <option value="contains">contains</option>
        <option value="starts">starts with</option>
        <option value="ends">ends with</option>
        <option value="=">=</option>
        <option value="!=">!=</option>
        <option value=">" class="numeric-only">&gt;</option>
        <option value="<" class="numeric-only">&lt;</option>
        <option value=">=" class="numeric-only">&gt;=</option>
        <option value="<=" class="numeric-only">&lt;=</option>
    </select>
    <input type="text" id="filterValue" class="form-control form-control-sm" placeholder="Value..." style="width:250px">
    <button id="filterSearchBtn" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
</div>

<div id="baseparametersTable">
    <table class="table bp-table" id="baseparametersTableInner">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-name">Parameter Name</th>
                <th class="col-limits">Limits</th>
                <th class="col-minmax">Min</th>
                <th class="col-minmax">Max</th>
                <th class="col-unit">Unit</th>
                <th class="col-category">Category</th>
                <th class="col-resulttype">ResultType</th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody id="baseparametersBody"></tbody>
    </table>
    <div id="paginationControls" class="pagination-controls d-flex justify-content-between align-items-center">
        <button id="prevPageBtn" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-left"></i> Back</button>
        <span id="pageIndicator" class="page-indicator">1 / 1</span>
        <div class="d-flex gap-2 align-items-center">
            <input type="number" id="goToPage" class="form-control form-control-sm d-inline-block text-center" style="width:65px" min="1" value="1">
            <button id="goToPageBtn" class="btn btn-sm btn-outline-secondary">Go</button>
        </div>
        <button id="nextPageBtn" class="btn btn-sm btn-outline-secondary">Forward <i class="fas fa-chevron-right"></i></button>
    </div>
</div>

<script src="js/quilljs/1.3.7/quill.min.js"></script>
<script>

window.quillInstances = window.quillInstances || {};

function initializeQuillEditors() {
    var editors = document.querySelectorAll('.quill-editor');
    editors.forEach(function(editor) {
        var quill = new Quill(editor, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, false] }],
                    ['bold', 'italic', 'underline'],
                    [{'script': 'sub'}, {'script': 'super'}],
                    [{'list': 'ordered'}, {'list': 'bullet'}],
                ],
            },
        });
        window.quillInstances[editor.id] = quill;
        quill.on('text-change', function () {
            clearTimeout(window.__speciesMatchTimer);
            window.__speciesMatchTimer = setTimeout(autoMatchSpecies, 400);
        });
    });
}

// ---- Hardcoded auto-match: TDS Element + Neutrality ion from Parameter Name ----
var SPECIES_MATCH = [
    { re: /sodium/i, tds: 5, n: 1 },
    { re: /potassium/i, tds: 6, n: 2 },
    { re: /calcium/i, tds: 7, n: 3 },
    { re: /magnesium/i, tds: 8, n: 4 },
    { re: /bicarbonate|\bhco3\b|\bhco_?3\b/i, n: 7 },
    { re: /carbonate|\bco3\b|\bco_?3\b/i, n: 10 },
    { re: /chloride|chlorine|\bcl\b/i, tds: 14, n: 6 },
    { re: /nitrate|\bno3\b|\bno_?3\b/i, n: 8 },
    { re: /sulfate|sulphate|\bso4\b|\bso_?4\b/i, n: 9 },
    { re: /ammonium|\bnh4\b|\bnh_?4\b/i, n: 5 },
    { re: /iron|\bfe\b/i, tds: 9 },
    { re: /copper|\bcu\b/i, tds: 10 },
    { re: /lead|\bpb\b/i, tds: 11 },
    { re: /zinc|\bzn\b/i, tds: 12 },
    { re: /manganese|\bmn\b/i, tds: 13 },
    { re: /fluorine|fluoride|\bf\b/i, tds: 15 },
    { re: /boron|\bb\b/i, tds: 16 },
    { re: /sulfur|sulphur|\bs\b/i, tds: 17 },
    { re: /phosphor(us|ate)?|\bp\b/i, tds: 18 },
    { re: /carbon\b/i, tds: 4 },
    { re: /hydrogen|\bh\b/i, tds: 1 },
    { re: /oxygen|\bo\b/i, tds: 2 },
    { re: /nitrogen|\bn\b/i, tds: 3 }
];

function autoMatchSpecies() {
    var quill = window.quillInstances['ParameterNameForm'];
    if (!quill) return;
    var nameText = (quill.root.innerText || '').trim();
    if (!nameText) return;
    for (var i = 0; i < SPECIES_MATCH.length; i++) {
        var rule = SPECIES_MATCH[i];
        if (rule.re.test(nameText)) {
            var fills = [];
            if (rule.tds && $('#tdsID').val() === '') {
                $('#tdsID').val(rule.tds);
                fills.push('TDS element #' + rule.tds);
            }
            if (rule.n && $('#neutralityID').val() === '') {
                $('#neutralityID').val(rule.n);
                fills.push('Neutrality ion #' + rule.n);
            }
            if (fills.length) {
                toastr.info('Auto-matched "' + nameText + '": ' + fills.join(', '));
            }
            break;
        }
    }
}

var currentPage = 1;

function loadBaseParameters(page) {
    currentPage = page;
    $.ajax({
        url: 'ajax/filter_baseparameters.php',
        data: {
            page: page,
            size: 10,
            filter_col: $('#filterColumn').val(),
            filter_op: $('#filterOperator').val(),
            filter_val: $('#filterValue').val()
        },
        dataType: 'json',
        success: function(response) {
            if (!response.data || !response.data.length) {
                $('#baseparametersBody').html('<tr><td colspan="9" class="text-center">No parameters found</td></tr>');
                $('#pageIndicator').text('0 / 0');
                return;
            }
            var rows = '';
            $.each(response.data, function(i, row) {
                var resultTypeBadge = '';
                if (row.ResultType === 'quantitativeField') resultTypeBadge = '<span class="badge badge-resulttype badge-qty">Quantitative</span>';
                else if (row.ResultType === 'qualitativeField') resultTypeBadge = '<span class="badge badge-resulttype badge-qual">Qualitative</span>';
                else if (row.ResultType === 'rangeField') resultTypeBadge = '<span class="badge badge-resulttype badge-range">Range</span>';
                else resultTypeBadge = '<span class="badge badge-resulttype badge-none">Not Set</span>';

                rows += '<tr>' +
                    '<td class="col-id">' + (row.ParameterID || '') + '</td>' +
                    '<td class="col-name">' + $('<div></div>').text(row.ParameterName || '').html() + '</td>' +
                    '<td class="col-limits">' + $('<div></div>').text(row.Limits || '').html() + '</td>' +
                    '<td class="col-minmax">' + (row.MinLimit || '') + '</td>' +
                    '<td class="col-minmax">' + (row.MaxLimit || '') + '</td>' +
                    '<td class="col-unit">' + $('<div></div>').text(row.UnitOfMeasure || '').html() + '</td>' +
                    '<td class="col-category">' + $('<div></div>').text(row.Category || '').html() + '</td>' +
                    '<td class="col-resulttype">' + resultTypeBadge + '</td>' +
                    '<td class="col-actions">' +
                        '<button class="btn btn-info btn-sm editParameter" ' +
                        'data-parameterid="' + row.ParameterID + '" ' +
                        'data-parametername="' + $('<div></div>').text(row.ParameterName || '').html() + '" ' +
                        'data-neutralityid="' + (row.NeutralityID || '') + '" ' +
                        'data-tdsid="' + (row.TdsID || '') + '" ' +
                        'data-resulttype="' + (row.ResultType || '') + '" ' +
                        'data-limits="' + $('<div></div>').text(row.Limits || '').html() + '" ' +
                        'data-minlimit="' + (row.MinLimit || '') + '" ' +
                        'data-maxlimit="' + (row.MaxLimit || '') + '" ' +
                        'data-unitofmeasure="' + $('<div></div>').text(row.UnitOfMeasure || '').html() + '" ' +
                        'data-category="' + (row.Category || '') + '" ' +
                        'data-method="' + $('<div></div>').text(row.Method || '').html() + '">' +
                        '<i class="fas fa-sliders-h"></i> Manage</button> ' +
                        '<button class="btn btn-danger btn-sm deleteParameter" data-id="' + row.ParameterID + '">' +
                        '<i class="fas fa-trash-alt"></i></button>' +
                    '</td>' +
                    '</tr>';
            });
            $('#baseparametersBody').html(rows);
            $('#pageIndicator').text(currentPage + ' / ' + (response.last_page || 1));
            $('#prevPageBtn').prop('disabled', currentPage <= 1);
            $('#nextPageBtn').prop('disabled', currentPage >= (response.last_page || 1));
        },
        error: function() {
            toastr.error('Failed to load parameters');
        }
    });
}

$(document).ready(function () {

    if (typeof Quill !== 'undefined') {
        initializeQuillEditors();
    }

    loadBaseParameters(1);

    // Pagination controls
    $('#prevPageBtn').on('click', function () {
        if (currentPage > 1) loadBaseParameters(currentPage - 1);
    });
    $('#nextPageBtn').on('click', function () {
        loadBaseParameters(currentPage + 1);
    });
    $('#goToPageBtn').on('click', function () {
        var page = parseInt($('#goToPage').val());
        if (page >= 1) loadBaseParameters(page);
    });
    $('#goToPage').on('keypress', function (e) {
        if (e.which === 13) { $('#goToPageBtn').click(); }
    });

    // Select2 search (only if element exists and Select2 loaded)
    if ($.fn.select2 && $('#standardSearch').length) {
        $('#standardSearch').select2({
            placeholder: 'Search parameters...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: 'ajax/fetchbaseelements.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term, page: params.page || 1, size: 10 };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    var results = data.data.map(function(standard) {
                        return { id: standard.ParameterID, text: standard.ParameterName, standardData: standard };
                    });
                    return { results: results, pagination: { more: params.page < data.last_page } };
                },
                cache: true
            }
        });

        $('#standardSearch').on('select2:select', function (e) {
            var selected = e.params.data;
            var standard = selected.standardData || selected;
            $('#filterColumn').val(1);
            $('#filterOperator').val('contains');
            $('#filterValue').val(standard.ParameterName);
            loadBaseParameters(1);
        });

        $('#standardSearch').on('select2:clear', function(e) {
            $('#filterValue').val('');
            loadBaseParameters(1);
        });
    }

    // Column change: restrict numeric operators, then reload
    $('#filterColumn').on('change', function () {
        var col = parseInt($(this).val());
        var isNumeric = (col === 0 || col === 3 || col === 4);
        $('#filterOperator option').show();
        if (!isNumeric) {
            $('#filterOperator option.numeric-only').hide();
            if ($('#filterOperator').val() === '>' || $('#filterOperator').val() === '<' ||
                $('#filterOperator').val() === '>=' || $('#filterOperator').val() === '<=') {
                $('#filterOperator').val('contains');
            }
        }
        loadBaseParameters(1);
    }).trigger('change');

    // Filter button click
    $('#filterSearchBtn').on('click', function () {
        loadBaseParameters(1);
    });
    $('#filterValue').on('keypress', function (e) {
        if (e.which === 13) { loadBaseParameters(1); }
    });


// Add Parameter
    $('#addParameterBtn').on('click', function () {
        $('#ParameterForm').trigger('reset');
        $('#ParameterIDForm').val("");
        $('#addstandardName').text("");
        window.quillInstances['ParameterNameForm'].root.innerHTML = "";
        var editModal = new bootstrap.Modal($('#ParameterModalRecord'), { backdrop: 'static' });
        editModal.show();
    });

    // Sync to ERP
    $('#syncToERP').on('click', function () {
        if (!confirm('Sync all base parameters to ERP system?')) return;
        var syncBtn = $('#syncToERP');
        syncBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
        $.ajax({
            url: 'ajax/syncBaseparameterToERP.php',
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success('Synced ' + response.count + ' parameters to ERP');
                } else {
                    toastr.error('Sync failed: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                toastr.error('Error: ' + error);
            },
            complete: function () {
                syncBtn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt"></i> Sync to ERP');
            }
        });
    });

    // Edit Parameter
    $(document).on('click', '.editParameter', function (e) {
        e.stopPropagation();
        $('#ParameterForm').trigger('reset');
        $('#ParameterIDForm').val($(this).data('parameterid'));
        $('#addstandardName').text($(this).data('parametername'));
        window.quillInstances['ParameterNameForm'].root.innerHTML = $(this).data('parametername');
        $('#tdsID').val($(this).data('tdsid'));
        $('#neutralityID').val($(this).data('neutralityid'));
        $('#resultType').val($(this).data('resulttype') || '');
        $('#limits').val($(this).data('limits') || '');
        $('#minLimit').val($(this).data('minlimit') || '');
        $('#maxLimit').val($(this).data('maxlimit') || '');
        $('#unitOfMeasure').val($(this).data('unitofmeasure') || '');
        $('#category').val($(this).data('category') || 'chemical');
        $('#method').val($(this).data('method') || '');
        var editModal = new bootstrap.Modal($('#ParameterModalRecord'), { backdrop: 'static' });
        editModal.show();
    });

    // Save Parameter
    $('#ParameterForm').submit(function (e) {
        e.preventDefault();
        var uploadButton = $('#ParameterForm button[type="submit"]');
        uploadButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        var formData = $(this).serializeArray();
        $('.quill-editor').each(function () {
            var quillInstanceId = $(this).attr('id');
            var quill = Quill.find(this);
            if (quill) {
                formData.push({ name: quillInstanceId, value: quill.root.innerText.trim() });
            }
        });

        $.ajax({
            url: 'ajax/saveBaseparameter.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success('Parameter saved successfully!');
                    bootstrap.Modal.getOrCreateInstance($('#ParameterModalRecord')[0]).hide();
                    loadBaseParameters(currentPage);
                } else {
                    toastr.error('Error: ' + response.message);
                }
            },
            error: function (xhr, status, error) {
                toastr.error('An error occurred: ' + error);
            },
            complete: function () {
                uploadButton.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
            }
        });
    });

    // Delete Parameter
    $(document).on('click', '.deleteParameter', function (e) {
        e.stopPropagation();
        var parameterID = $(this).data('id');
        if (confirm('Are you sure you want to delete this parameter?')) {
            $.ajax({
                url: 'ajax/deleteglobalparameter.php',
                type: 'POST',
                data: { parameterID: parameterID },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        loadBaseParameters(currentPage);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function () {
                    toastr.error('Failed to delete parameter.');
                }
            });
        }
    });

    // Import Parameters
    $('#importParametersBtn').on('click', function () {
        var standardID = $(this).data('id');
        $('#uploadStandardID').val(standardID);
        var standardName = $(this).data('name');
        $('#uploadstandardName').text(standardName);
        $('#uploadStandardsForm').trigger('reset');
        var editModal = new bootstrap.Modal($('#uploadStandardsModal'));
        editModal.show();
    });

    // File Upload
    $('#uploadStandardsForm').on('submit', function (e) {
        e.preventDefault();
        var uploadButton = $('#uploadStandardsForm button[type="submit"]');
        uploadButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

        var fileInput = $('#excelFile')[0];
        var file = fileInput.files[0];
        if (file && file.size > 5 * 1024 * 1024) {
            toastr.error('File size exceeds the maximum allowed limit of 5MB.');
            uploadButton.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
            return;
        }

        var formData = new FormData(this);
        $.ajax({
            url: 'ajax/importGlobalparameters.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                var res = JSON.parse(response);
                if (res.success) {
                    toastr.success(res.message);
                    bootstrap.Modal.getOrCreateInstance($('#uploadStandardsModal')[0]).hide();
                    loadBaseParameters(currentPage);
                } else {
                    toastr.error('Error: ' + res.message);
                }
            },
            error: function (xhr, status, error) {
                toastr.error('Error: ' + error);
            },
            complete: function() {
                uploadButton.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
            }
        });
    });

    // Modal stacking
    $(document).on('shown.bs.modal', '.modal', function () {
        var zIndex = 1050 + ($('.modal:visible').length * 10);
        $(this).css('z-index', zIndex);
        $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        $('.modal:visible').each(function (index) {
            var zIndex = 1050 + ((index + 1) * 10);
            $(this).css('z-index', zIndex);
        });
        $('.modal-backdrop').not('.modal-stack').each(function (index) {
            var zIndex = 1040 + ((index + 1) * 10);
            $(this).css('z-index', zIndex);
        });
        if (!$('.modal:visible').length) {
            $('.modal-backdrop').remove();
        }
    });

});

// Load Neutrality dropdown
$.ajax({
    url: 'jsonfiles/neutralityarray.php',
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        var countrySelect = $('#neutralityID');
        $.each(data, function(index, country) {
            countrySelect.append($('<option></option>').attr('value', country.id).text(country.ion_name));
        });
    },
    error: function(xhr, status, error) {
        toastr.error('Error fetching neutrality parameters: ' + error);
    }
});

// Load TDS dropdown
$.ajax({
    url: 'jsonfiles/tdsarray.php',
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        var countrySelect = $('#tdsID');
        $.each(data, function(index, country) {
            countrySelect.append($('<option></option>').attr('value', country.id).text(country.Element));
        });
    },
    error: function(xhr, status, error) {
        toastr.error('Error fetching TDS parameters: ' + error);
    }
});
</script>
