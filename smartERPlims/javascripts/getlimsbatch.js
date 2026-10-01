// === CERTIFICATE FINDER SCRIPT ===

document.addEventListener("DOMContentLoaded", function() {

    const certButton = document.getElementById("getCertificateFinder");
    if (!certButton) {
        console.warn("Certificate button not found on this page");
        return;
    }

    const certModal = document.getElementById("certificateFinderModal");
    const certClose = document.querySelector(".close-cert");
    const certTableBody = document.querySelector("#cert-table tbody");
    const certPagination = document.getElementById("cert-pagination");
    const certSearchButton = document.getElementById("certSearchButton");
    const certSearchQuery = document.getElementById("certSearchQuery");
    const certApiUrl = "Ajax/getCertificateAssesmentAjax.php";

    function formatDate(value) {
        if (!value) return "";
        const d = new Date(value);
        return isNaN(d.getTime()) ? value : d.toISOString().slice(0, 10);
    }

    function displayCertificates(samples) {
        certTableBody.innerHTML = "";

        if (!samples || samples.length === 0) {
            certTableBody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:30px;">No certificates found</td></tr>`;
            return;
        }

        samples.forEach(sample => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${formatDate(sample.Date)}</td>
                <td>${sample.DocumentNo || ''}</td>
                <td>${sample.customer || ''}</td>
                <td><button class="use-certificate" data-docno="${sample.DocumentNo || ''}">Use</button></td>`;
            certTableBody.appendChild(row);
        });
    }

    function setupPagination(totalPages, currentPage, query) {
        certPagination.innerHTML = "";
        for (let page = 1; page <= totalPages; page++) {
            const link = document.createElement("a");
            link.href = "#";
            link.textContent = page;
            link.dataset.page = page;
            if (page === currentPage) link.classList.add("active");

            link.addEventListener("click", (e) => {
                e.preventDefault();
                fetchCertificates(parseInt(link.dataset.page), query);
            });
            certPagination.appendChild(link);
        }
    }

    function fetchCertificates(page = 1, query = "") {
    
    console.log(`[DEBUG] fetchCertificates called - Page: ${page}, Query: "${query}"`);
    console.log(`[DEBUG] API URL: ${certApiUrl}`);

    $.ajax({
        url: certApiUrl,
        method: "GET",
        data: { 
            page: page, 
            query: query 
        },
        dataType: "json",
        
        success: function(response) {
            console.log("[DEBUG] AJAX Success - Response received:", response);
            
            if (response.success) {
                console.log(`[DEBUG] Success = true | Samples count: ${response.samples ? response.samples.length : 0}`);
                displayCertificates(response.samples || []);
                setupPagination(response.total_pages || 1, page, query);
            } else {
                console.warn("[DEBUG] Success = false or missing", response);
                displayCertificates([]);
            }
        },
        
        error: function(xhr, status, error) {
            console.error("[DEBUG] AJAX Error occurred!");
            console.error("Status:", status);
            console.error("Error:", error);
            console.error("Response Text:", xhr.responseText);
            console.error("Status Code:", xhr.status);
            
            displayCertificates([]);
        },
        
        complete: function() {
            console.log("[DEBUG] AJAX request completed (success or error)");
        }
    });
}

    // Button Click
    certButton.addEventListener("click", function(e) {
        e.preventDefault();
        certModal.style.display = "block";
        fetchCertificates(1, "");
    });

    // Search
    certSearchButton.addEventListener("click", () => {
        fetchCertificates(1, certSearchQuery.value.trim());
    });

    certSearchQuery.addEventListener("keypress", (e) => {
        if (e.key === "Enter") fetchCertificates(1, certSearchQuery.value.trim());
    });

    // Use Certificate
    certTableBody.addEventListener("click", function(e) {
        if (e.target.classList.contains("use-certificate")) {
            const docno = e.target.dataset.docno || "";
            const input = document.getElementById("coa_documentno");
            if (input) input.value = docno;
            certModal.style.display = "none";
             document.body.classList.remove("loading");
        }
    });

    // Close Modal
    if (certClose) {
        certClose.addEventListener("click", () => {
            certModal.style.display = "none";
             document.body.classList.remove("loading");
        });
    }

    window.addEventListener("click", (e) => {
        if (e.target === certModal) {
            certModal.style.display = "none";
             document.body.classList.remove("loading");
        }
    });


    // Open certModal
    certButton.onclick = function(){
        certModal.style.display = 'block';
        loadCertificates('');
    };
    
    // Close certModal
    certClose.onclick = function(){
        certModal.style.display = 'none';
         document.body.classList.remove("loading");
    };
    
    window.onclick = function(event){
        if (event.target == certModal) {
            certModal.style.display = 'none';
        }
    };
    
    // Search certificates
    $('#certSearchButton').on('click', function(){
        var query = $('#certSearchQuery').val();
        loadCertificates(query);
    });
    
    $('#certSearchQuery').on('keyup', function(e){
        if(e.which == 13){
            loadCertificates($(this).val());
        }
    });
    
    // Load certificates
    function loadCertificates(searchText) {
    var url = 'Ajax/getsampleidforbatchAjax.php?query=' + encodeURIComponent(searchText);

    // SHOW loader safely (prevent duplicates)
    if (!$("body").hasClass("loading")) {
        $("body").addClass("loading");
    }

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',

        success: function (response) {
            if (response.success && response.samples) {

                var tableBody = $('#cert-table tbody');
                tableBody.empty();

                $.each(response.samples, function (index, sample) {

                    // ✅ FIX: create row
                    var row = document.createElement("tr");

                    row.innerHTML = `
                        <td>${formatDate(sample.Date)}</td>
                        <td>${sample.DocumentNo || ''}</td>
                        <td>${sample.customer || ''}</td>
                        <td>
                            <button class="use-certificate" 
                                data-docno="${sample.DocumentNo || ''}">
                                Use
                            </button>
                        </td>
                    `;

                    tableBody.append(row);
                });
            } else {
                console.warn("Invalid response format", response);
            }
        },

        error: function (xhr, status, err) {
            console.error("AJAX error:", status, err);
            alert('Error loading certificates');
        },

        // ✅ CRITICAL: always runs → fixes stuck overlay
        complete: function () {
            $("body").removeClass("loading");

            // optional: kill any duplicate overlays
            $('.overlay, .modal-backdrop, .o_blockUI').remove();
        }
    });
}
    
    // ================== USE CERTIFICATE BUTTON ==================
    certTableBody.addEventListener("click", function(e) {
        if (e.target.classList.contains("use-certificate")) {
            const docno = e.target.dataset.docno || "";
            if (docno) {
                selectCertificate(docno);   // ← Now calls your function
            }
        }
    });
    // Handle certificate selection and load sample IDs
    function selectCertificate(documentNo){
        $.ajax({
            url: 'Ajax/getsampleidforbatchAjax.php?documentno=' + encodeURIComponent(documentNo),
            type: 'GET',
            dataType: 'json',
            success: function(response){
                if(response.success){
                    // Update the coa_documentno field
                    $('#coa_documentno').val(documentNo);
                    
                    // Update sample dropdown if data returned
                    if(response.sampleIds && response.sampleIds.length > 0){
                        var sampleSelect = $('#sampleid');
                        sampleSelect.empty();
                        $.each(response.sampleIds, function(index, sample){
                            sampleSelect.append('<option value="' + sample.sampleid + '">' + sample.sampleid + '</option>');
                        });
                    }
                    
                    // Close certModal
                    certModal.style.display = 'none';
                    document.body.classList.remove("loading");
                }
            },
            error: function(){
                alert('Error loading samples for document: ' + documentNo);
            }
        });
    }
    
    
});
 