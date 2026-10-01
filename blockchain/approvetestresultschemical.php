<style>
    .container h5 {
        margin-bottom: 20px;
        font-weight: bold;
    }

    .mb-3 {
        margin-bottom: 15px;
    }
 
    .table th, .table td {
        text-align: center;
    }

    .table th {
        width: 120px; /* Set fixed width for column headers */
    }

    .table td {
    max-width: 150px; /* Set maximum width for cells */
    padding: 0; /* Remove padding to make space for content */
}

.scrollable-content {
    overflow: hidden; /* Initially hide overflow */
    white-space: nowrap; /* Prevent text wrapping */
    text-overflow: ellipsis; /* Show ellipsis for truncated text */
    display: block; /* Ensure block display for the content */
    max-width: 100%; /* Full width of the table cell */
    padding: 5px; /* Add padding inside the div */
    border: 1px solid #ccc; /* Subtle border for the content */
    background-color: #f9f9f9; /* Background color */
    color: #333; /* Text color */
    box-sizing: border-box; /* Include padding and border in width/height */
    font-family: Arial, sans-serif; /* Font style */
    font-size: 14px; /* Font size */
    max-height: 30px; /* Collapsed height */
    transition: max-height 0.3s ease; /* Smooth transition for expanding */
    cursor: pointer; /* Pointer cursor to indicate focusable element */
}

.scrollable-content:focus {
    overflow: visible; /* Show all content */
    white-space: normal; /* Allow text to wrap */
    max-height: none; /* Remove height restriction */
    outline: none; /* Remove default outline for better appearance */
    background-color: #fff; /* Optional: Change background color on focus */
    border-color: #007BFF; /* Optional: Highlight border on focus */
}


    /* Responsive table design */
    @media (max-width: 768px) {
        .table th, .table td {
            padding: 10px;
        }
    }

    .table {
        table-layout: fixed;
    }

    .table-bordered {
        border: 1px solid #ddd;
    }

    .table-striped tbody tr:nth-child(odd) {
        background-color: #f9f9f9;
    }
    
   #approveModal div {
            font-family: Arial, sans-serif; font-size: 14px; line-height: 1; padding: 5;
        }
    
    .wr-table-wrap { overflow-x: auto; }
    .wr-table { width: 100%; border-collapse: collapse; min-width: 1060px; }
    .wr-table th { background: #17324d; color: #fff; padding: 12px 10px; text-align: left; font-size: 12px; letter-spacing: 0.04em; text-transform: uppercase; }
    .wr-table td { padding: 12px 10px; border-bottom: 1px solid #e5edf5; font-size: 13px; color: #1f2937; vertical-align: top; }
    .wr-table tr:hover { background: #f8fbfd; }
    /* Pagination - matches the wr-table design */
    #pagination { margin-top: 14px; text-align: right; }
    #pagination button {
        background: #fff; border: 1px solid #d4deea; color: #17324d;
        padding: 6px 12px; margin-left: 4px; border-radius: 4px;
        font-size: 13px; cursor: pointer;
    }
    #pagination button:hover:not(:disabled) { background: #e9eef4; }
    #pagination button.active { background: #17324d; color: #fff; border-color: #17324d; }
    #pagination button:disabled { opacity: .5; cursor: default; }
    #pagination span { vertical-align: middle; }
</style>
<i class="fas fa-project-diagram"></i>Lab Result Workflow

<div class="container mt-4">
<div class="search-container">
    <input type="text" id="searchInput" class="form-control" placeholder="Search results..." onkeyup="searchTable()">
</div> 
<div class="wr-table-wrap">
<table id="testResultsTable" class="wr-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Sample Batch No</th>
            <th>Sample ID</th>
            <th>Date</th>
            <th>Parameter</th>
            <th>Results</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <!-- Rows will be dynamically generated -->
    </tbody>
</table>
</div>
<div id="pagination"></div>

</div>

<!-- Modal for Approving Test Results -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">Review and Approve Test Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="approveForm">
                    <input type="hidden" id="resultsID" name="resultsID">
                    <input type="hidden" id="flag" name="flag">
                    <input type="hidden" id="resultType" name="resultType">
                    <div class="mb-3">
                        <label for="sampleID" class="form-label">Sample ID</label>
                        <input type="text" id="sampleID" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="parameter" class="form-label">Parameter</label>
                        <input type="text" id="parameter" class="form-control" readonly>
                    </div>
                    <div class="container mt-4">
                        <h5 class="text-center">Test Result Details</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="mrlResult" class="form-label">Quantitative</label>
                                    <input type="text" id="mrlResult"  name="mrlResult" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="resultStatus" class="form-label">Qualitative</label>
                                    <select id="resultStatus" name="resultStatus" class="form-control">
                                        <option value="">N/A</option>
                                        <option value="ND">Not Detected</option>
                                        <option value="Absent">Absent</option>
                                        <option value="Detected">Detected</option>
                                        <option value="Below Limit">Below Limit</option>
                                        <option value="Detected Range">Detected Range</option>
                                        <option value="Trace">Trace</option>
                                        <option value="Above Limit">Above Limit</option>
                                        <option value="Inconclusive">Inconclusive</option>
                                        <option value="Error">Error</option>
                                        <option value="Invalid">Invalid</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="rangeResult" class="form-label">Range</label>
                                    <input type="text" id="rangeResult" name="rangeResult" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mw-100">
                                <label for="standtocompare" class="form-label">Standard Limits</label>
                                <input type="text" id="standtocompare" class="form-control" readonly>
                            </div>                       
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="approvalStatus" class="form-label">Approval Status</label>
                        <select id="approvalStatus" name="approvalStatus" class="form-select" required>
                            <option value="">Select</option>
                            <option value="1">Approved</option>
                            <option value="2">Reanalysis Required</option>
                            <option value="3">Error Corrected</option>
                            <option value="4">Rejected</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-success" onclick="submitApproval()">Submit Approval</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Lightweight client-side pagination for the results table (no DataTables dependency)
var wrPage = 1;
var wrPerPage = 10;
var wrRows = [];
var wrQuery = '';

function wrMatches(row, q) {
    if (!q) return true;
    const cells = row.getElementsByTagName('td');
    for (let j = 0; j < cells.length; j++) {
        if (cells[j] && cells[j].textContent.toLowerCase().includes(q)) return true;
    }
    return false;
}

function wrRenderPage() {
    const tbody = document.querySelector('#testResultsTable tbody');
    if (!tbody) return;
    const visible = wrRows.filter(function (r) { return wrMatches(r, wrQuery); });
    const totalPages = Math.max(1, Math.ceil(visible.length / wrPerPage));
    if (wrPage > totalPages) wrPage = totalPages;
    if (wrPage < 1) wrPage = 1;
    const start = (wrPage - 1) * wrPerPage;
    const slice = visible.slice(start, start + wrPerPage);
    tbody.innerHTML = '';
    slice.forEach(function (tr) { tbody.appendChild(tr); });
    wrRenderPager(totalPages, visible.length);
}

function wrRenderPager(totalPages, count) {
    const box = document.getElementById('pagination');
    if (!box) return;
    let html = '<span style="margin-right:8px;color:#17324d;font-size:13px;">' + count + ' record(s) - Page ' + wrPage + ' of ' + totalPages + '</span>';
    html += '<button type="button"' + (wrPage === 1 ? ' disabled' : '') + ' onclick="wrGo(' + (wrPage - 1) + ')">&#8592; Prev</button>';
    let s = Math.max(1, wrPage - 4);
    let e = Math.min(totalPages, s + 9);
    if (e < totalPages && e - s < 9) s = Math.max(1, e - 9);
    for (let i = s; i <= e; i++) {
        html += '<button type="button" class="' + (i === wrPage ? 'active' : '') + '" onclick="wrGo(' + i + ')">' + i + '</button>';
    }
    if (e < totalPages) html += '<span style="color:#17324d;margin-left:4px;">&hellip;</span>';
    html += '<button type="button"' + (wrPage === totalPages ? ' disabled' : '') + ' onclick="wrGo(' + (wrPage + 1) + ')">Next &#8594;</button>';
    box.innerHTML = html;
}

function wrGo(p) { wrPage = p; wrRenderPage(); }

function searchTable() {
    const input = document.getElementById('searchInput');
    wrQuery = input ? input.value.trim().toLowerCase() : '';
    wrPage = 1;
    wrRenderPage();
}

function disableOtherFields(selectedId) {
    const fields = ['mrlResult','resultStatus','rangeResult'];
    document.getElementById('resultType').value = selectedId;
    
    fields.forEach(field => {
        document.getElementById(field).disabled = (field !== selectedId);
    });
}

function reloadResults(){
    const fields = ['mrlResult', 'resultStatus', 'rangeResult'];
    
    fields.forEach(field => {
        document.getElementById(field).disabled = false;
    });
}

// Attach event listeners to fields
document.getElementById('mrlResult').addEventListener('input', () => disableOtherFields('mrlResult'));
document.getElementById('resultStatus').addEventListener('input', () => disableOtherFields('resultStatus'));
document.getElementById('rangeResult').addEventListener('input', () => disableOtherFields('rangeResult'));

function fetchTestResults(flag) {
     const tbody = document.querySelector('#testResultsTable tbody');
     // Use unified endpoint with department filter
    fetch(`ajax/get_test_results.php?statusID=${flag}&department=chemical`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tbody.innerHTML = '';
                wrRows = [];
                data.results.forEach((result, index) => {
                    const row = document.createElement('tr');
                    const displayResult = result.ResultType === 'quantitativeField' ? (result.MRL_Result || 'N/A') : (result.ResultType === 'qualitativeField' ? (result.ResultStatus || 'N/A') : (result.ResultType === 'rangeField' ? (result.RangeResult || 'N/A') : (result.MRL_Result || result.ResultStatus || result.RangeResult || 'N/A')));
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${result.DocumentNo}</td>
                        <td>${result.SampleID}</td>
                        <td>${new Date(result.Date.split(' ')[0]).toISOString().split('T')[0]}</td>
                        <td><div class="scrollable-content" tabindex="0">${result.ParameterName}</div></td>
                        <td>${displayResult}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="openApprovalModal(${result.resultsID}, ${flag})">
                                 ${getButtonLabel(flag)}
                            </button>
                        </td>
                    `;
                    wrRows.push(row);
                });
                wrPage = 1;
                wrRenderPage();
            } else {
                 tbody.innerHTML = '';
                toastr.error('No results to display.');
            }
        })
        .catch(error => console.error('Error fetching test results:', error));
}

function getButtonLabel(flag) {
    let label = 'Action'; // Default label
    switch (flag) {
        case 2:
            label = 'Action'; // If flag is 2, change the label to 'Review'
            break;
        default:
            label = 'Action'; // Default to 'Approve'
            break;
    }
    return label;
}

function openApprovalModal(resultsID,flag) {
    flag=3;
    reloadResults();
    fetch(`ajax/get_test_resultByID.php?resultsID=${resultsID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const result = data.result;
                document.getElementById('flag').value = flag;
                document.getElementById('resultsID').value = result.resultsID;
                document.getElementById('sampleID').value = result.SampleID;
                document.getElementById('parameter').value = result.ParameterName;
                document.getElementById('standtocompare').value = 'Limits:' +(result.Limits || 'N/A') + '  units of measure:' + (result.UnitOfMeasure || 'N/A') ;
                
                // Determine active result field robustly (maps various stored values)
                const rtRaw = (result.ResultType || '').toString();
                const rt = rtRaw.toLowerCase();
                let activeField = '';
                if (rt.includes('quant') || rt.includes('mrl')) {
                    activeField = 'mrlResult';
                } else if (rt.includes('qual') || rt.includes('resultstatus')) {
                    activeField = 'resultStatus';
                } else if (rt.includes('range')) {
                    activeField = 'rangeResult';
                } else {
                    // fallback: prefer the non-empty field from the record
                    if (result.MRL_Result && result.MRL_Result.toString().trim() !== '') activeField = 'mrlResult';
                    else if (result.ResultStatus && result.ResultStatus.toString().trim() !== '') activeField = 'resultStatus';
                    else if (result.RangeResult && result.RangeResult.toString().trim() !== '') activeField = 'rangeResult';
                    else activeField = 'mrlResult';
                }

                // Populate fields (always fill values) then enable only the active one
                document.getElementById('mrlResult').value = result.MRL_Result || '';
                setOption(result.ResultStatus || '');
                document.getElementById('rangeResult').value = result.RangeResult || '';
                disableOtherFields(activeField);

                bootstrap.Modal.getOrCreateInstance($('#approveModal')[0]).show();
            } else {
                toastr.error('Failed to load test result details.');
            }
        })
        .catch(error => console.error('Error loading test result details:', error));
}

function submitApproval() {
    const flag = document.getElementById('flag') ; 
    const formData = new FormData(document.getElementById('approveForm'));
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i); // Get the key
        const value = localStorage.getItem(key); // Get the corresponding value
        formData.append(key, value); // Append the key-value pair to formData
    }

    const approve_test_result=`approve_test_result_${flag.value}.php`;
 
    fetch(`ajax/${approve_test_result}`, {
        method:'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
             toastr.success(data.message);
            bootstrap.Modal.getOrCreateInstance($('#approveModal')[0]).hide();
            fetchTestResults(flag.value); // Reload the table
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => console.error('Error submitting approval:', error));
}
 
function setOption(resultValue) {
    const selectElement = document.getElementById('resultStatus');
    // Check if resultValue exists in the select options
    const optionExists = Array.from(selectElement.options).some(option => option.value === resultValue);
    // Set the option if it exists, otherwise set it to 'N/A'
    selectElement.value = optionExists ? resultValue : '';
}
fetchTestResults(2);
</script>