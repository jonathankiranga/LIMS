
    <link href="css/chainofcustody.css" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="css/tracksamples.css">
    <link href="css/chainofcustody.css" rel="stylesheet" type="text/css"/>
   
    <div class="action-menu">
        <i class="fas fa-exchange-alt">Chain of Custody</i>
        <div class="btn btn-icon action-item" id="displayactiverow"></div>
    </div>
   <!-- Sample Data Table -->
    <div class="container">
        <input type="text" id="searchQuery" placeholder="Search...">
        <button id="searchButton">Search</button>

        <h2>Registered Samples</h2>
        <table class="table table-striped-columns table-bordered" id="data-table">
            <thead>
                <tr>
                    <th>Date of Registration</th>
                    <th>Sampling Date</th>
                    <th>Sample Standard</th>
                    <th>Image File</th>
                    <th>Batch No</th>
                    <th>Date Of Man</th>
                    <th>Exp Date</th>
                    <th>External Sample</th>
                    <th></th>
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
   
   

<div class="modal" id="chainOfCustodyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Chain of Custody</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="chainOfCustodyForm">
          <div class="mb-3">
            <label for="custodySampleID" class="form-label">Sample ID</label>
            <input type="text" class="form-control" id="custodySampleID" name="custodySampleID" readonly>
          </div>
          <div class="mb-3">
            <label for="handlerName" class="form-label">Handler Name</label>
            <input type="text" class="form-control" id="handlerName" name="handlerName" required>
          </div>
          <div class="mb-3">
            <label for="action" class="form-label">Action</label>
            <input type="text" class="form-control" id="action" name="action" required>
          </div>
          <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" name="location" required>
          </div>
            <div class="mb-3">
            <label for="location" class="form-label">NOTES</label>
            <input type="text" class="form-control" id="narration" name="narration" required>
          </div>
          <button type="submit" class="btn btn-primary">Update Custody</button>
        </form>
      </div>
    </div>
  </div>
</div>



<script>
    
    $(document).ready(function() {
// Tooltip for 🔍 icon
$("#data-table tbody").on("mouseenter", ".img-preview-icon", function(e) {
    let $cell = $(this).closest("td");
    let fullPath = $cell.find("img.thumb-preview").data("full");

    if (fullPath) {
        let tooltip = $("<div class='image-tooltip'></div>")
            .append(`<img src="${fullPath}" alt="Preview">`);
        $("body").append(tooltip);

        $(this).on("mousemove", function(ev) {
            tooltip.css({ top: ev.pageY + 15, left: ev.pageX + 15 }).show();
        });

        $(this).on("mouseleave", function() {
            tooltip.remove();
        });
    }
});



        
                $(document).on("click", ".action-button-chain", function () {
                        const activeRow = $(".active-row");
                        if (activeRow.length === 0) {
                            toastr.error("Please select a row first!");
                            return;
                        }

                        const sampleID = activeRow.data("sampleid");
                        const headerid = activeRow.data("headerid");

                           document.getElementById('custodySampleID').value = sampleID;
                          new bootstrap.Modal(document.getElementById('chainOfCustodyModal')).show();
                    });

                $(document).on('click', '.view-custody', function() {
                    let sampleId = $(this).data('sampleid');
                    let row = $(this).closest('tr');

     // Check if custody row already exists
                    if (row.next().hasClass('custody-row')) {
                        row.next().toggle(); // toggle visibility
                        return;
                    }
                    

                    // Otherwise, fetch custody records via AJAX
                    $.ajax({
                        url: 'ajax/get_custody.php',
                        type: 'GET',
                        data: { sample_id: sampleId },
                        success: function(response) {
                            let custodyHtml = '<tr class="custody-row"><td colspan="9">';
                            custodyHtml += '<table class="table table-sm table-bordered">';
                            custodyHtml += '<thead><tr><th>Date</th><th>Handled By</th><th>Location</th><th>Comments</th><th>Action/Task</th></tr></thead><tbody>';

                            if (response.length > 0) {
                                response.forEach(record => {
                                    custodyHtml += `<tr>
                                        <td>${record.DateTime}</td>
                                        <td>${record.HandlerName}</td>
                                        <td>${record.Location}</td>
                                        <td>${record.Notes}</td>
                                        <td>${record.Action}</td>
                                    </tr>`;
                                });
                            } else {
                                custodyHtml += '<tr><td colspan="5">No custody records found.</td></tr>';
                            }

                            custodyHtml += '</tbody></table></td></tr>';

                            row.after(custodyHtml);
                        }
                    });
                });
    
            // Handle row selection
            let currentPage = 1;
            const recordsPerPage = 50;

            function fetchData(page, query) {
                $.ajax({
                    url: 'ajax/SampleRegistrationList.php', // Update with your PHP script path
                    method: 'GET',
                    data: {
                        page: page,
                        query: query
                    },
                    dataType: 'json',
                    success: function(response) {
                        displayResults(response.samples);
                        setupPagination(response.total_pages, page);
                        initializeRowClickListener();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching data: ", error);
                    }
                });
            }

            function displayResults(samples) {
                 
                let tableBody = $("#data-table tbody");
                
                tableBody.empty();
                samples.forEach(sample => {
                    if(sample.disposal_timestamp === null || sample.disposal_timestamp === undefined){
                        let tmpPath = sample.SampleFileKey || 'images/no-image.png';
                        let imagePath = tmpPath.replace('../', '');
                        console.log("data: ",imagePath);
                        let mainRow = `
                            <tr      
                            data-sampleid="${sample.SampleID}" 
                            data-headerid="${sample.HeaderID}"
                            data-bs-toggle="collapse" 
                            data-bs-target="#details-${sample.SampleID}" class="accordion-toggle">
                            <span class="toggle-arrow">▶</span>
                                <td>${formatDate(sample.Date)}</td>
                                <td>${formatDate(sample.SamplingDate)}</td>
                                <td>${sample.SampleID},${sample.StandardName}</td>
                                <td>
                                    <span class="img-preview-icon" title="Preview Image">🔍</span>
                                    <img src="${imagePath}"  alt="Sample Image"  class="thumb-preview"  data-full="${imagePath}">    
                                </td>
                                <td>${sample.BatchNo}</td>
                                <td>${formatDate(sample.ManufactureDate)}</td>
                                <td>${formatDate(sample.ExpDate)}</td>
                                <td>${sample.ExternalSample}</td>
                                <td>
                                    <button class="btn btn-primary btn-sm action-button-chain" 
                                            data-sampleid="${sample.SampleID}"  
                                            data-headerid="${sample.HeaderID}">Book Custody</button>
                                </td>
                            </tr>
                            <tr class="collapse-row">
                                <td colspan="10">
                                    <div class="collapse-content">
                                        <button class="btn btn-sm btn-info view-custody" 
                                                data-sampleid="${sample.SampleID}">
                                            View Custody Trail
                                        </button>
                                        <div class="custody-details"></div>
                                    </div>
                                </td>
                            </tr>
                        `;

                   tableBody.append(mainRow);
                   } 
                });
  
                    // Tooltip logic
                    $("#data-table tbody tr").each(function() {
                        let $row = $(this);
                        let $img = $row.find("td[data-img] img");
                        if($img.length) {
                            let tooltip = $("<div class='image-tooltip'></div>").append($img.clone());
                            $("body").append(tooltip);

                            $row.on("mousemove", function(e) {
                                tooltip.css({ top: e.pageY + 15, left: e.pageX + 15 });
                            }).on("mouseenter", function() {
                                tooltip.show();
                            }).on("mouseleave", function() {
                                tooltip.hide();
                            });
                        }
                    });

                    // Rotate arrow
                    $("#data-table tbody tr[data-bs-toggle='collapse']").on('click', function() {
                        let $arrow = $(this).find('.toggle-arrow');
                        let target = $($(this).attr('data-bs-target'));
                        if (target.hasClass('show')) {
                            $arrow.css("transform", "rotate(0deg)");
                        } else {
                            $arrow.css("transform", "rotate(90deg)");
                        }
                    });
               
            }


            function setupPagination(totalPages, currentPage) {
                $('#pagination').empty();
                for (let page = 1; page <= totalPages; page++) {
                    $('#pagination').append(`<a href="#" class="page-link" data-page="${page}">${page}</a> `);
                }
                $('.page-link').on('click', function(e) {
                    e.preventDefault();
                    currentPage = $(this).data('page');
                    fetchData(currentPage, $('#searchQuery').val());
                });
            }

            $('#searchButton').on('click', function() {
                currentPage = 1; // Reset to the first page on new search
                fetchData(currentPage, $('#searchQuery').val());
            });

            // Initial fetch
            fetchData(currentPage, '');

        
    });
    
    document.addEventListener("DOMContentLoaded", () => {
        const tableBody = document.querySelector("#data-table tbody");
        // Use event delegation to detect clicks on rows within the tbody
        tableBody.addEventListener("click", (event) => {
            const clickedRow = event.target.closest("tr"); // Identify the clicked row
             if (clickedRow) {
           // Remove 'selected' class from all rows
                const rows = tableBody.querySelectorAll("tr");
                rows.forEach(row => row.classList.remove("active-row"));
                 // Add 'selected' class to the clicked row
                clickedRow.classList.add("active-row");
                const sampleID = clickedRow.getAttribute("data-sampleid");
            // Display the sampleID in the designated element
                document.getElementById("displayactiverow").innerHTML = sampleID || "No ID Found";
           }
        });
    });
    
    
    function initializeRowClickListener() {
        const tableBody = document.querySelector("#data-table tbody");
        // Use event delegation to detect clicks on rows within the tbody
        tableBody.addEventListener("click", (event) => {
            const clickedRow = event.target.closest("tr"); // Identify the clicked row
             if (clickedRow) {
           // Remove 'selected' class from all rows
                const rows = tableBody.querySelectorAll("tr");
                rows.forEach(row => row.classList.remove("active-row"));
                 // Add 'selected' class to the clicked row
                clickedRow.classList.add("active-row");
                const sampleID = clickedRow.getAttribute("data-sampleid");
            // Display the sampleID in the designated element
                document.getElementById("displayactiverow").innerHTML = sampleID || "No ID Found";
           }
        });
        
    }
  
    document.getElementById('chainOfCustodyForm').addEventListener('submit', function(e) {
       e.preventDefault(); // Prevent the default form submission behavior

       const formData = new FormData(this); // Gather form data
       for (let i = 0; i < localStorage.length; i++) {
           const key = localStorage.key(i); // Get the key
           const value = localStorage.getItem(key); // Get the corresponding value
           formData.append(key, value); // Append the key-value pair to formData
       }

       fetch('ajax/save_coc_handler.php', {
           method: 'POST',
           body: formData
       })
       .then(response => response.json()) // Expecting JSON response from the server
       .then(data => {
           if (data.success) {
              toastr.success("Chain of custody saved successfully!");
               // Optionally, clear the form or perform other actions
               document.getElementById('chainOfCustodyForm').reset();
           } else {
               toastr.error('Error: ' + data.message);
           }
       })
       .catch(error => {
           console.error('Error:', error);
           toastr.error('An error occurred while saving the data.');
       });
   });
  
</script>
