<?php
// Chemical Sample Approval - grouped sample-level approval
?>
<style>
    .wr-table-wrap { overflow-x: auto; }
    .wr-table { width: 100%; border-collapse: collapse; min-width: 980px; }
    .wr-table th { background: #17324d; color: #fff; padding: 11px 10px; text-align: left; font-size: 12px; text-transform: uppercase; }
    .wr-table td { padding: 10px; border-bottom: 1px solid #e5edf5; font-size: 13px; color: #1f2937; vertical-align: middle; }
    .wr-table tr:hover { background: #f8fbfd; }
    .test-list { margin: 0; padding-left: 18px; text-align: left; }
    .test-list li { margin-bottom: 3px; }
    .result-badge { display:inline-block; margin:2px 3px 2px 0; padding:3px 7px; border:1px solid #d9e2ec; border-radius:3px; background:#f8fafc; }
    .sample-tests-table { width:100%; border-collapse:collapse; }
    .sample-tests-table th { background:#17324d; color:#fff; padding:8px; font-size:12px; text-align:left; }
    .sample-tests-table td { border-bottom:1px solid #e5edf5; padding:8px; font-size:13px; vertical-align:top; }
    .sample-tests-table tr:last-child td { border-bottom:0; }
    #pagination { margin-top:14px; text-align:right; }
    #pagination button { background:#fff; border:1px solid #d4deea; color:#17324d; padding:6px 12px; margin-left:4px; border-radius:4px; font-size:13px; cursor:pointer; }
    #pagination button.active { background:#17324d; color:#fff; border-color:#17324d; }
    #pagination button:disabled { opacity:.5; cursor:default; }
</style>

<i class="fas fa-project-diagram"></i> Lab Result Workflow

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Chemical Sample Approval</h5>
        <input type="text" id="searchInput" class="form-control" style="max-width:320px" placeholder="Search Sample ID / Batch No / Test..." onkeyup="searchTable()">
    </div>

    <div class="wr-table-wrap">
        <table id="testResultsTable" class="wr-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sample Batch No</th>
                    <th>Sample ID</th>
                    <th>Date</th>
                    <th>No. of Tests</th>
                    <th>Tests / Results</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div id="pagination"></div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">Review Sample Test Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Sample ID</label>
                        <input type="text" id="blockSampleID" class="form-control" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Sample Batch No</label>
                        <input type="text" id="blockDocumentNo" class="form-control" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Number of Tests</label>
                        <input type="text" id="blockTestCount" class="form-control" readonly>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="sample-tests-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Parameter</th>
                                <th>Result</th>
                                <th>Standard Limits</th>
                            </tr>
                        </thead>
                        <tbody id="sampleTestsBody"></tbody>
                    </table>
                </div>

                <hr>

                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label for="approvalStatus" class="form-label fw-bold">Block Decision</label>
                        <select id="approvalStatus" name="approvalStatus" class="form-select" required>
                            <option value="">Select decision</option>
                            <option value="1">Approve Entire Sample</option>
                            <option value="2">Reanalysis Required - Entire Sample</option>
                            <option value="4">Reject Entire Sample</option>
                        </select>
                    </div>
                    <div class="col-md-7 text-end">
                        <button type="button" class="btn btn-success" onclick="submitApproval()">
                            <i class="fas fa-check"></i> Apply Decision to All Tests
                        </button>
                    </div>
                </div>

                <input type="hidden" id="blockHeaderID">
                <input type="hidden" id="flag" value="3">
                <input type="hidden" id="blockDepartment" value="chemical">
            </div>
        </div>
    </div>
</div>

<script>
var wrPage = 1;
var wrPerPage = 10;
var wrRows = [];
var wrQuery = '';
var wrResults = [];

function esc(value) {
    return String(value == null ? '' : value)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
}

function resultValue(result) {
    if (result.ResultType === 'qualitativeField') return result.ResultStatus || 'N/A';
    if (result.ResultType === 'rangeField') return result.RangeResult || 'N/A';
    return result.MRL_Result || 'N/A';
}

function wrMatches(row, q) {
    if (!q) return true;
    return row.textContent.toLowerCase().indexOf(q) !== -1;
}

function wrRenderPage() {
    const tbody = document.querySelector('#testResultsTable tbody');
    if (!tbody) return;

    const visible = wrRows.filter(function(r){ return wrMatches(r, wrQuery); });
    const totalPages = Math.max(1, Math.ceil(visible.length / wrPerPage));
    if (wrPage > totalPages) wrPage = totalPages;
    if (wrPage < 1) wrPage = 1;

    const start = (wrPage - 1) * wrPerPage;
    tbody.innerHTML = '';
    visible.slice(start, start + wrPerPage).forEach(function(tr){ tbody.appendChild(tr); });
    wrRenderPager(totalPages, visible.length);
}

function wrRenderPager(totalPages, count) {
    const box = document.getElementById('pagination');
    if (!box) return;

    let html = '<span style="margin-right:8px;color:#17324d;font-size:13px;">' +
        count + ' sample(s) - Page ' + wrPage + ' of ' + totalPages + '</span>';

    html += '<button type="button"' + (wrPage === 1 ? ' disabled' : '') +
        ' onclick="wrGo(' + (wrPage - 1) + ')">&#8592; Prev</button>';

    let s = Math.max(1, wrPage - 4);
    let e = Math.min(totalPages, s + 9);
    if (e < totalPages && e - s < 9) s = Math.max(1, e - 9);

    for (let i = s; i <= e; i++) {
        html += '<button type="button" class="' + (i === wrPage ? 'active' : '') +
            '" onclick="wrGo(' + i + ')">' + i + '</button>';
    }

    if (e < totalPages) html += '<span style="color:#17324d;margin-left:4px;">&hellip;</span>';

    html += '<button type="button"' + (wrPage === totalPages ? ' disabled' : '') +
        ' onclick="wrGo(' + (wrPage + 1) + ')">Next &#8594;</button>';

    box.innerHTML = html;
}

function wrGo(p) {
    wrPage = p;
    wrRenderPage();
}

function searchTable() {
    const input = document.getElementById('searchInput');
    wrQuery = input ? input.value.trim().toLowerCase() : '';
    wrPage = 1;
    wrRenderPage();
}

function fetchTestResults(flag) {
    const tbody = document.querySelector('#testResultsTable tbody');
    const department = document.getElementById('blockDepartment').value;

    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';

    fetch('ajax/get_test_results.php?statusID=' + encodeURIComponent(flag) +
          '&department=' + encodeURIComponent(department) + '&groupBySample=1')
        .then(function(response){ return response.json(); })
        .then(function(data) {
            if (!data.success) {
                wrRows = [];
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No pending sample blocks.</td></tr>';
                wrRenderPager(1, 0);
                return;
            }

            wrResults = data.results || [];
            wrRows = [];

            wrResults.forEach(function(sample, index) {
                const tests = sample.tests || [];
                const testNames = tests.map(function(t){
                    return '<li>' + esc(t.ParameterName) + '</li>';
                }).join('');

                const resultSummary = tests.map(function(t){
                    return '<span class="result-badge"><b>' + esc(t.ParameterName) +
                        ':</b> ' + esc(resultValue(t)) + '</span>';
                }).join('');

                const row = document.createElement('tr');
                row.innerHTML =
                    '<td>' + (index + 1) + '</td>' +
                    '<td>' + esc(sample.DocumentNo || '') + '</td>' +
                    '<td><strong>' + esc(sample.SampleID || '') + '</strong></td>' +
                    '<td>' + esc((sample.Date || '').split(' ')[0]) + '</td>' +
                    '<td>' + tests.length + '</td>' +
                    '<td><ul class="test-list">' + testNames + '</ul><div>' + resultSummary + '</div></td>' +
                    '<td><button class="btn btn-primary btn-sm" onclick="openApprovalModal(' + index + ')">' +
                    '<i class="fas fa-tasks"></i> Review / Approve</button></td>';

                wrRows.push(row);
            });

            wrPage = 1;
            wrRenderPage();
        })
        .catch(function(error) {
            console.error('Error fetching grouped test results:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading results.</td></tr>';
        });
}

function openApprovalModal(index) {
    const sample = wrResults[index];
    if (!sample) return;

    document.getElementById('blockHeaderID').value = sample.HeaderID || '';
    document.getElementById('blockSampleID').value = sample.SampleID || '';
    document.getElementById('blockDocumentNo').value = sample.DocumentNo || '';
    document.getElementById('blockTestCount').value = (sample.tests || []).length;
    document.getElementById('approvalStatus').value = '';

    const body = document.getElementById('sampleTestsBody');
    body.innerHTML = '';

    (sample.tests || []).forEach(function(test, i) {
        const limits = 'Limits: ' + (test.Limits || 'N/A') +
            ' | Unit: ' + (test.UnitOfMeasure || 'N/A');

        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td>' + (i + 1) + '</td>' +
            '<td><strong>' + esc(test.ParameterName || '') + '</strong>' +
            (test.ParamCategory ? '<br><small>' + esc(test.ParamCategory) + '</small>' : '') + '</td>' +
            '<td>' + esc(resultValue(test)) + '</td>' +
            '<td>' + esc(limits) + '</td>';
        body.appendChild(tr);
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('approveModal')).show();
}

function submitApproval() {
    const headerID = document.getElementById('blockHeaderID').value;
    const sampleID = document.getElementById('blockSampleID').value;
    const department = document.getElementById('blockDepartment').value;
    const approvalStatus = document.getElementById('approvalStatus').value;
    const flag = document.getElementById('flag').value;

    if (!headerID || !sampleID) {
        toastr.error('Sample information is missing.');
        return;
    }

    if (!approvalStatus) {
        toastr.error('Please select a block decision.');
        return;
    }

    const formData = new FormData();
    formData.append('blockApproval', '1');
    formData.append('HeaderID', headerID);
    formData.append('SampleID', sampleID);
    formData.append('department', department);
    formData.append('flag', flag);
    formData.append('approvalStatus', approvalStatus);

    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        formData.append(key, localStorage.getItem(key));
    }

    if (!formData.get('user_id')) {
        toastr.error('User identity was not found. Please log in again.');
        return;
    }

    const button = document.querySelector('#approveModal .btn-success');
    if (button) button.disabled = true;

    fetch('ajax/approve_test_result_3.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response){ return response.json(); })
    .then(function(data) {
        if (data.success) {
            toastr.success(data.message);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('approveModal')).hide();
            fetchTestResults(2);
        } else {
            toastr.error(data.message || 'Block approval failed.');
        }
    })
    .catch(function(error) {
        console.error('Error submitting block approval:', error);
        toastr.error('An error occurred while approving the sample block.');
    })
    .finally(function() {
        if (button) button.disabled = false;
    });
}

fetchTestResults(2);
</script>
