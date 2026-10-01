<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Email Certificate of Analysis</title>
<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/all.min.js"></script>
<script src="js/2.10.377/pdf.min.js"></script>
<style>
.bank-statement-container {
    overflow-x: auto; /* Enable horizontal scrolling */
    background: white; /* White background for the table */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    margin-bottom: 20px; /* Space below the table */
}

.bank-statement {
    width: 100%;
    border-collapse: collapse;
}

.bank-statement thead {
    background-color: #3498db; /* Header background color */
    color: white;
}

.bank-statement th, .bank-statement td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
    white-space: nowrap; /* Prevent text wrapping */
}

.bank-statement th {
    font-weight: bold;
}

.bank-statement tr:nth-child(even) {
    background-color: #f2f2f2; /* Zebra striping */
}

.bank-statement tr:hover {
    background-color: #e0f7fa; /* Highlight on hover */
}

.pagination {
    display: flex;
    justify-content: center; /* Center pagination links */
    margin: 20px 0;
}

.pagination a {
    padding: 10px 15px;
    text-decoration: none;
    color: #3498db;
    border: 1px solid #ddd;
    border-radius: 5px; /* Rounded corners */
    margin: 0 5px;
    transition: background-color 0.3s; /* Smooth hover effect */
}

.pagination a:hover {
    background-color: #3498db; /* Change background on hover */
    color: white; /* Change text color on hover */
}

.pagination strong {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #e0e0e0; /* Highlight current page */
    color: #555; /* Darker color for current page */
}

#progress-container {
    display: none; /* Initially hidden */
    position: fixed; /* Fixed position on the screen */
    top: 50%; /* Center vertically */
    left: 50%; /* Center horizontally */
    transform: translate(-50%, -50%); /* Adjust position to truly center */
    background: rgba(255, 255, 255, 0.9); /* Optional: semi-transparent background */
    padding: 20px; /* Add some padding */
    border-radius: 10px; /* Rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Subtle shadow */
    text-align: center; /* Center-align text inside */
    z-index: 1000; /* Ensure it appears above other elements */
}

#progress-bar {
    width: 100%; /* Full width of the container */
    height: 20px; /* Adjust height as needed */
    border: 1px solid #ccc; /* Optional: border */
    border-radius: 5px; /* Rounded corners */
}

#mail-log-output {
    margin: 20px 0;
    padding: 16px;
    border: 1px solid #d9e2ec;
    border-radius: 8px;
    background: #f8fbff;
}

#mail-log-output.hidden {
    display: none;
}

.mail-log-entry {
    padding: 6px 0;
    border-bottom: 1px solid #e5edf5;
    white-space: normal;
}

.mail-log-entry:last-child {
    border-bottom: none;
}

.send-mail-btn {
    border: none;
    background: #198754;
    color: #fff;
    border-radius: 6px;
    padding: 6px 10px;
}

.send-mail-btn:hover {
    background: #157347;
}

.table-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.status-action-btn {
    border: none;
    background: #fd7e14;
    color: #fff;
    border-radius: 6px;
    padding: 6px 10px;
}

.status-action-btn:hover {
    background: #dc6b0d;
}

.coa-preview {
    border: none;
    background: #0d6efd;
    color: #fff;
    border-radius: 6px;
    padding: 6px 10px;
}

.coa-preview:hover {
    background: #0b5ed7;
}

.status-chip {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.status-chip.success {
    background: #d1e7dd;
    color: #0f5132;
}

#certPreviewModal .modal-dialog {
    max-width: 900px !important;
    width: 95% !important;
}

#certPreviewModal .cert-preview-body {
    max-height: 85vh;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0;
    background: #cfd4d9;
}

.preview-pdf-pages {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 16px;
}

.preview-pdf-page {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
    background: white;
}

.preview-pdf-page canvas {
    display: block;
    background: white;
}

.preview-pdf-caption {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    padding: 4px 8px;
    font-size: 12px;
    color: #495057;
    text-align: center;
}

.preview-pdf-error {
    color: #842029;
    font-size: 12px;
    padding: 8px;
}
}

.status-chip.failure {
    background: #f8d7da;
    color: #842029;
}

.status-chip.queued {
    background: #fff3cd;
    color: #664d03;
}
    
</style>
</head>
<body>

<div class="container">
    <input type="text" id="searchQuery" placeholder="Search Sample ID or Parameter Name">
    <button id="searchButton"><i class="fa-solid fa-magnifying-glass"></i></button><div class="btn btn-icon action-item" id="displayactiverow"></div>
    <h2>Approved Samples</h2>

    <div id="progress-container" style="display: none;">
       <label for="progress-bar">Email Send Progress:</label>
       <progress id="progress-bar" value="0" max="100"></progress>
   </div>

    <div id="mail-log-output" class="hidden">
        <h5>Mail Log</h5>
        <div id="mail-log-summary"></div>
        <div id="mail-log-lines"></div>
    </div>
         
        
        <table class="table table-hover" id="data-table">
            <thead>
                <tr>
                    <th>Date of Registration</th>
                    <th>Batch No</th>
                    <th>Sample ID</th>
                    <th>Customer Name</th>
                    <th>Sample Source</th>
                    <th>Emails Sent</th>
                    <th>Last Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be dynamically populated -->
            </tbody>
        </table>
        
        <!-- Pagination Placeholder -->
    <nav aria-label="Table pagination">
        <ul class="pagination" id="pagination">
            <!-- Pagination buttons will be dynamically added here -->
        </ul>
    </nav>
    </div>

<div class="modal fade" id="casModal" tabindex="-1" aria-labelledby="casModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="casModalLabel">Email Certificate of Analysis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                 <input type="hidden" name="optionprint" id="optionprint">
                <p id="casModalDesc">Select the Certificate of Analysis (COA) format to email to the customer:</p>
                <div class="list-group">
                    <button class="list-group-item list-group-item-action" id="casNoPic">
                        Email COA <i class="fas fa-file-alt"></i> without Picture
                    </button>
                    <button class="list-group-item list-group-item-action" id="casWithPic">
                        Email COA <i class="fas fa-image"></i> with Picture
                    </button>
                    
                    <button class="list-group-item list-group-item-action" id="casByBatch">
                        Email COA <i class="fas fa-layer-group"></i> without Picture and Color Intepretation
                    </button>
                    
                </div>
            </div>
            <div class="modal-footer">
                <button type="button"  class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="certPreviewModal" tabindex="-1" aria-labelledby="certPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl cert-preview-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="certPreviewModalLabel">Certificate of Analysis Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body cert-preview-body" id="certPreviewBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0 text-muted">Loading preview&hellip;</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Change Sample Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusResultsID">
                <input type="hidden" id="statusCurrentValue">
                <div class="mb-3">
                    <label for="statusSampleID" class="form-label">Sample ID</label>
                    <input type="text" id="statusSampleID" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label for="statusCurrentLabel" class="form-label">Current Status</label>
                    <input type="text" id="statusCurrentLabel" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label for="statusTargetLabel" class="form-label">New Status</label>
                    <input type="text" id="statusTargetLabel" class="form-control" value="Sample Received (1)" readonly>
                </div>
                <p class="mb-0 text-muted">This action will roll back the sample.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" id="confirmStatusChange">Roll Back Sample</button>
            </div>
        </div>
    </div>
</div>

<script>
	    window.certificateOfAssessmentTableState = window.certificateOfAssessmentTableState || {
	        page: 1,
	        query: ''
	    };
	    var tableState = window.certificateOfAssessmentTableState;

	    function getStatusLabel(statusID) {
	        const labels = {
	            0: 'Rejected (0)',
	            1: 'Sample Received (1)',
	            2: 'In Progress (2)',
	            3: 'Waiting Approval (3)',
	            4: 'Approved (4)'
	        };

	        return labels[Number(statusID)] || `Status ${escapeHtml(statusID || '')}`;
	    }
	    
	    $(document).ready(function() {
	        fetchData(1,'');
	    });

	   // Fetch data function
	    function fetchData(page, query) {
	        tableState.page = page;
	        tableState.query = query;
	        $.ajax({
	            url: 'ajax/getlabreportsApprovedAjax.php',
	            method: 'GET',
            data: {
                page: page,
                query: query
            },
            dataType: 'json',
            success: function (response) {
                if(response.sucess){
                    displayResults(response.samples);
                    setupPagination(response.total_pages, page);
                  }else{
                       setupPagination(1, page);
                       toastr.error(response.message || "Failed to get results.");
                  }
            },
            error: function (xhr, status, error) {
                console.error("Error fetching data: ", error);
            }
        });
    }

    // Display results function
	    function displayResults(samples) {
	        let tableBody = $("#data-table tbody");
	        tableBody.empty();
	        samples.forEach(sample => {
	            const status = sample.COALastEmailStatus || 'Not sent';
	            const statusClass = status === 'success' ? 'success' : (status === 'failure' ? 'failure' : '');
	            const sampleStatus = Number(sample.ResultStatusID || 4);
	            const sampleStatusLabel = getStatusLabel(sampleStatus);
	            tableBody.append(`
	                <tr data-documentno="${sample.DocumentNo}" data-resultsid="${sample.resultsID}" data-testid="${sample.TestID}" data-sampleid="${escapeHtml(sample.ResultSampleID)}" data-statusid="${sampleStatus}">
	                    <td>${formatDate(sample.Date)}</td>
	                    <td>${sample.DocumentNo}</td>
	                    <td>${sample.ResultSampleID}</td>
	                    <td>${sample.customer}</td>
	                    <td>${sample.ExternalSample || ''}</td>
	                    <td class="send-count">${sample.COAEmailSendCount || 0}</td>
	                    <td class="send-status"><span class="status-chip ${statusClass}">${status}</span></td>
	                    <td>
	                        <div class="table-actions">
	                            <button class="send-mail-btn printpdf" data-sampleid="${sample.TestID}" title="Email certificate">
	                                <i class="fa-solid fa-paper-plane"></i>
	                            </button>
	                            <button
	                                class="preview-btn coa-preview"
	                                data-sampleid="${sample.TestID}"
	                                title="Preview COA"
	                            >
	                                <i class="fa-solid fa-eye"></i>
	                            </button>
	                            <button
	                                class="status-action-btn reopen-sample-status"
	                                data-resultsid="${sample.resultsID}"
	                                data-sampleid="${escapeHtml(sample.ResultSampleID)}"
	                                data-statusid="${sampleStatus}"
	                                data-statuslabel="${escapeHtml(sampleStatusLabel)}"
	                                title="Roll Back Sample"
	                            >
	                                <i class="fa-solid fa-rotate-left"></i>
	                            </button>
	                        </div>
	                    </td>
	                </tr>
	            `);
	        });
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getReportLabel(reportOption) {
        const labels = {
            1: 'Without Picture',
            2: 'With Picture',
            3: 'Without Picture and Color Interpretation'
        };

        return labels[Number(reportOption)] || `Report ${escapeHtml(reportOption || '')}`;
    }

    function getFilteredRecentLogs(response, selectedReportOption) {
        const activeReportOption = Number(response.report_option || selectedReportOption || 0);
        const recentLogs = Array.isArray(response.recent_logs) ? response.recent_logs : [];

        if (!activeReportOption) {
            return recentLogs;
        }

        return recentLogs.filter(log => Number(log.report_option || 0) === activeReportOption);
    }

    function buildToastrMailMessage(response, selectedReportOption) {
        const activeReportOption = Number(response.report_option || selectedReportOption || 0);
        const recentLogs = getFilteredRecentLogs(response, activeReportOption);
        const logPreview = (response.mail_log || []).slice(-2);
        let html = `<strong>${escapeHtml(response.message || '')}</strong>`;

        if (activeReportOption) {
            html += `<br><small>${escapeHtml(getReportLabel(activeReportOption))}</small>`;
        }

        if (response.recipient_email) {
            html += `<br><small>Recipient: ${escapeHtml(response.recipient_email)}</small>`;
        }

        if (logPreview.length) {
            html += `<br><small>${logPreview.map(line => escapeHtml(line)).join('<br>')}</small>`;
        } else if (recentLogs.length) {
            const latestLog = recentLogs[0];
            html += `<br><small>${escapeHtml(latestLog.created_at || '')} - ${escapeHtml(latestLog.message || '')}</small>`;
        }

        return html;
    }

    function renderMailLog(response, selectedReportOption) {
        const logOutput = $('#mail-log-output');
        const logSummary = $('#mail-log-summary');
        const logLines = $('#mail-log-lines');
        const statusClass = response.status === 'queued'
            ? 'queued'
            : (response.success ? 'success' : 'failure');
        const recipient = response.recipient_email ? `Recipient: ${escapeHtml(response.recipient_email)}` : 'Recipient: not available';
        const activeReportOption = Number(response.report_option || selectedReportOption || 0);
        const activeReportLabel = activeReportOption ? getReportLabel(activeReportOption) : 'Selected Report';
        const filteredRecentLogs = getFilteredRecentLogs(response, activeReportOption);

        logSummary.html(`
            <div class="mail-log-entry">
                <span class="status-chip ${statusClass}">${escapeHtml(response.status || (response.success ? 'success' : 'failure'))}</span>
                <strong>${escapeHtml(activeReportLabel)}</strong><br>
                <strong>${escapeHtml(response.message || '')}</strong><br>
                ${recipient}<br>
                Send count: ${escapeHtml(response.send_count || 0)}
            </div>
        `);

        let linesHtml = '';
        (response.mail_log || []).forEach(line => {
            linesHtml += `<div class="mail-log-entry">${escapeHtml(line)}</div>`;
        });

        if (filteredRecentLogs.length) {
            linesHtml += `<div class="mail-log-entry"><strong>Recent Attempts For ${escapeHtml(activeReportLabel)}</strong></div>`;
            filteredRecentLogs.forEach(log => {
                linesHtml += `<div class="mail-log-entry">${escapeHtml(log.created_at || '')} - ${escapeHtml(log.status || '')} - ${escapeHtml(log.message || '')}</div>`;
            });
        }

        logLines.html(linesHtml);
        logOutput.removeClass('hidden');
    }

    function updateRowSendTracking(sampleID, response) {
        const row = $(`#data-table tbody tr[data-testid="${sampleID}"]`);
        if (!row.length) {
            return;
        }

        if (Object.prototype.hasOwnProperty.call(response, 'send_count')) {
            row.find('.send-count').text(response.send_count || 0);
        }
        const statusClass = response.status === 'queued'
            ? 'queued'
            : (response.success ? 'success' : 'failure');
        row.find('.send-status').html(
            `<span class="status-chip ${statusClass}">${escapeHtml(response.status || (response.success ? 'success' : 'failure'))}</span>`
        );
    }

    // Setup pagination
    function setupPagination(totalPages, currentPage) {
        $('#pagination').empty();
        for (let page = 1; page <= totalPages; page++) {
            $('#pagination').append(`<a href="#" class="page-link" data-page="${page}">${page}</a> `);
        }

        $('.page-link').on('click', function (e) {
            e.preventDefault();
            currentPage = $(this).data('page');
            fetchData(currentPage, $('#searchQuery').val());
        });
    }
    
    
    document.getElementById("searchButton").addEventListener("click", function () {
         const searchQuery = $("#searchQuery").val();
        fetchData(1,searchQuery);
    });
    
    let casPreviewMode = false;

    document.getElementById("casNoPic").addEventListener("click", function () {
         const sampleID = $("#optionprint").val();
         const modalElement = document.getElementById('casModal');
         const modal = bootstrap.Modal.getInstance(modalElement);
 // Hide the modal
          modal.hide();
         if (casPreviewMode) {
             previewCertificate(sampleID, 1);
         } else {
             getreportwithoption(sampleID,1);
         }
    });

    document.getElementById("casWithPic").addEventListener("click", function () {
        const sampleID = $("#optionprint").val();
        const modalElement = document.getElementById('casModal');
    const modal = bootstrap.Modal.getInstance(modalElement);

    // Hide the modal
    modal.hide();
        if (casPreviewMode) {
            previewCertificate(sampleID, 2);
        } else {
            getreportwithoption(sampleID,2);
        }
    });

    document.getElementById("casByBatch").addEventListener("click", function () {
           const sampleID = $("#optionprint").val();
           const modalElement = document.getElementById('casModal');
           const modal = bootstrap.Modal.getInstance(modalElement);
           modal.hide();
           if (casPreviewMode) {
               previewCertificate(sampleID, 3);
           } else {
               getreportwithoption(sampleID,3);
           }
    }); 

	    $("#data-table").on("click", ".printpdf", function () {
	        const activeRow = $(this); // Use 'this' to get the clicked button
	        const sampleID = activeRow.data("sampleid");
	        $("#optionprint").val(sampleID);
	        casPreviewMode = false;
	        $("#casModalLabel").text("Email Certificate of Analysis");
	        $("#casModalDesc").text("Select the Certificate of Analysis (COA) format to email to the customer:");
	        const modal = new bootstrap.Modal(document.getElementById('casModal'), { backdrop: 'static', keyboard: false });
	        modal.show(); // Show the modal
	    }); // <- Added closing parenthesis here

	    $("#data-table").on("click", ".coa-preview", function () {
	        const activeRow = $(this);
	        const sampleID = activeRow.data("sampleid");
	        $("#optionprint").val(sampleID);
	        casPreviewMode = true;
	        $("#casModalLabel").text("Select Certificate of Analysis (COA) format to preview");
	        $("#casModalDesc").text("Select the Certificate of Analysis (COA) format to preview:");
	        const modal = new bootstrap.Modal(document.getElementById('casModal'), { backdrop: 'static', keyboard: false });
	        modal.show();
	    });

    function previewCertificate(sampleID, reportoption) {
        reportoption = Number(reportoption) >= 1 && Number(reportoption) <= 3 ? Number(reportoption) : 1;
        const modalEl = document.getElementById('certPreviewModal');
        const bodyEl = document.getElementById('certPreviewBody');

        if ($('#certPreviewModal').length && typeof bootstrap !== 'undefined') {
            bodyEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div>' +
                '<p class="mt-2 mb-0 text-muted">Loading preview&hellip;</p></div>';
            bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false }).show();
        }

        if (typeof (window['pdfjs-dist/build/pdf']) === 'undefined') {
            if ($('#certPreviewModal').length) {
                bodyEl.innerHTML = '<div class="alert alert-danger mb-0">PDF viewer library failed to load.</div>';
            }
            return;
        }

        fetch('functions/certificateofanalysis' + reportoption + '.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: 'sampleID=' + encodeURIComponent(sampleID) + '&reportoption=' + reportoption + '&include_watermark=1'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.arrayBuffer();
        }).then(function (buffer) {
            renderCertificatePreview(bodyEl, buffer);
        }).catch(function (err) {
            console.error('Error previewing COA:', err);
            if ($('#certPreviewModal').length) {
                bodyEl.innerHTML = '<div class="alert alert-danger mb-0">Unable to load the certificate preview.<br><small>' + escapeHtml(err.message || err) + '</small></div>';
            }
        });
    }

    function renderCertificatePreview(host, buffer) {
        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'js/2.10.377/pdf.worker.min.js';

        const container = document.createElement('div');
        container.className = 'preview-pdf-pages';
        host.innerHTML = '';
        host.appendChild(container);

        pdfjsLib.getDocument({ data: new Uint8Array(buffer) }).promise.then(function (pdf) {
            const totalPages = pdf.numPages;
            const targetWidth = 794;
            let index = 1;

            function renderFailed(err) {
                console.error('Error rendering certificate PDF:', err);
                if (host.querySelector('.preview-pdf-page')) {
                    const note = document.createElement('div');
                    note.className = 'preview-pdf-error';
                    note.textContent = 'Failed to render page ' + index;
                    container.appendChild(note);
                } else if ($('#certPreviewModal').length) {
                    host.innerHTML = '<div class="alert alert-danger mb-0">Unable to render the certificate PDF.<br><small>' + escapeHtml(String((err && err.message) || err)) + '</small></div>';
                }
            }

            function renderNext() {
                if (index > totalPages) {
                    return;
                }
                const pageNum = index;
                pdf.getPage(pageNum).then(function (page) {
                    const base = page.getViewport({ scale: 1 });
                    const scale = (targetWidth * (window.devicePixelRatio || 1)) / base.width;
                    const viewport = page.getViewport({ scale: scale });

                    const holder = document.createElement('div');
                    holder.className = 'preview-pdf-page';

                    const canvas = document.createElement('canvas');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    canvas.style.width = targetWidth + 'px';
                    canvas.style.height = Math.round(targetWidth * viewport.height / viewport.width) + 'px';
                    holder.appendChild(canvas);

                    const caption = document.createElement('div');
                    caption.className = 'preview-pdf-caption';
                    caption.textContent = 'Page ' + pageNum + ' of ' + totalPages;
                    holder.appendChild(caption);

                    container.appendChild(holder);

                    page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise.then(function () {
                        index++;
                        renderNext();
                    }).catch(renderFailed);
                }).catch(renderFailed);
            }

            renderNext();
        }).catch(function (err) {
            console.error('Error loading certificate PDF:', err);
            if ($('#certPreviewModal').length) {
                host.innerHTML = '<div class="alert alert-danger mb-0">The certificate could not be generated.<br><small>' + escapeHtml(String((err && err.message) || err)) + '</small></div>';
            }
        });
    }

	    $("#data-table").on("click", ".reopen-sample-status", function () {
	        const actionButton = $(this);
	        $("#statusResultsID").val(actionButton.data("resultsid"));
	        $("#statusSampleID").val(actionButton.data("sampleid"));
	        $("#statusCurrentValue").val(actionButton.data("statusid"));
	        $("#statusCurrentLabel").val(actionButton.data("statuslabel"));

	        const modal = new bootstrap.Modal(document.getElementById('statusModal'), { backdrop: 'static', keyboard: false });
	        modal.show();
	    });

	    $("#confirmStatusChange").on("click", function () {
	        const resultsID = Number($("#statusResultsID").val());
	        const sampleID = $("#statusSampleID").val();
	        const currentStatus = Number($("#statusCurrentValue").val());

	        $.ajax({
	            url: 'ajax/reopenApprovedSampleAjax.php',
	            method: 'POST',
	            dataType: 'json',
	            data: {
	                resultsID: resultsID,
	                sampleID: sampleID,
	                currentStatus: currentStatus
	            },
	            success: function (response) {
	                if (response.success) {
	                    const modalElement = document.getElementById('statusModal');
	                    const modal = bootstrap.Modal.getInstance(modalElement);
	                    if (modal) {
	                        modal.hide();
	                    }

	                    if (typeof toastr !== 'undefined') {
	                        toastr.success(response.message || 'Sample status updated.');
	                    }

	                    fetchData(tableState.page, tableState.query);
	                } else if (typeof toastr !== 'undefined') {
	                    toastr.error(response.message || 'Unable to update the sample status.');
	                }
	            },
	            error: function (xhr) {
	                const response = xhr.responseJSON || {};
	                if (typeof toastr !== 'undefined') {
	                    toastr.error(response.message || 'Unable to update the sample status.');
	                }
	            }
	        });
	    });
	  
	    function getreportwithoption(sampleID,reportoption){
    // Show the progress bar
    $("#progress-container").show();
    $("#progress-bar").val(0);

    $.ajax({
        url: 'functions/email_certificateofanalysis.php',
        method: 'POST',
        data: {
            sampleID: sampleID,
            reportoption: reportoption
        },
        dataType: 'json',
        xhr: function () {
            const xhr = new XMLHttpRequest();

            // Attach progress event listener
            xhr.upload.addEventListener("progress", function (e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    $("#progress-bar").val(percentComplete);
                }
            });

            xhr.addEventListener("progress", function (e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    $("#progress-bar").val(percentComplete);
                }
            });

            return xhr;
        },
	        success: function (response) {
		            renderMailLog(response, reportoption);
		            updateRowSendTracking(sampleID, response);
		            if(response.status === 'queued'){
		                if (typeof toastr !== 'undefined') {
		                    toastr.info(
	                            buildToastrMailMessage(response, reportoption),
	                            `COA ${getReportLabel(reportoption)}`,
	                            { timeOut: 8000, extendedTimeOut: 3000, closeButton: true }
	                        );
		                }
		            } else if(response.success){
		                if (typeof toastr !== 'undefined') {
		                    toastr.success(
                            buildToastrMailMessage(response, reportoption),
                            `COA ${getReportLabel(reportoption)}`,
                            { timeOut: 8000, extendedTimeOut: 3000, closeButton: true }
                        );
	                }
	            } else if (typeof toastr !== 'undefined') {
	                toastr.error(
                        buildToastrMailMessage(response, reportoption),
                        `COA ${getReportLabel(reportoption)}`,
                        { timeOut: 10000, extendedTimeOut: 4000, closeButton: true }
                    );
	            }
	            $("#progress-container").hide();
	        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error sending COA email:', textStatus, errorThrown);
            let response = {
                success: false,
                status: 'failure',
                message: 'Unable to send the email request.',
                mail_log: []
            };
	            if (jqXHR.responseJSON) {
	                response = jqXHR.responseJSON;
	            }
	            renderMailLog(response, reportoption);
	            updateRowSendTracking(sampleID, response);
	            if (typeof toastr !== 'undefined') {
	                toastr.error(
                        buildToastrMailMessage(response, reportoption),
                        `COA ${getReportLabel(reportoption)}`,
                        { timeOut: 10000, extendedTimeOut: 4000, closeButton: true }
                    );
	            }
	            $("#progress-container").hide();
	        }
	    });
	}

        
</script>

</body>
</html>