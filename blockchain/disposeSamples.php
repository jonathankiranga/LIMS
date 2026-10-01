<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispose of Samples</title>
    <link rel="stylesheet" href="css/tracksamples.css">
</head>
<body>
    <div class="action-menu">
         <i class="fas fa-trash-alt">Dispose of Samples</i>
    <div class="btn btn-icon action-item" id="displayactiverow"></div>
</div>
  <!-- Sample Data Table -->
    <div class="container">
        <input type="text" id="searchQuery" placeholder="Search...">
    <button id="searchButton">Search</button>
        <h2>Registered Samples</h2>
        <table class="table table-striped table-hover table-bordered" id="data-table">
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


<div class="modal" id="disposeSamplesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Dispose of Sample</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="disposeSamplesForm">
          <div class="mb-3">
            <label for="disposeSampleID" class="form-label">Sample ID</label>
            <input type="text" class="form-control" id="disposeSampleID" name="disposeSampleID" readonly>
            <input type="hidden" class="form-control" id="headerID" name="headerID" readonly>
         </div>
          <div class="mb-3">
            <label for="reason" class="form-label">Reason for Disposal</label>
            <textarea class="form-control" id="reason" name="reason" required></textarea>
          </div>
            <button type="button" onclick="disposesample();" class="btn btn-danger">Dispose</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
    
    $(document).on("click", ".action-button", function () {
            const activeRow = $(".active-row");
            if (activeRow.length === 0) {
                toastr.error("Please select a row first!");
                return;
            }

            const sampleID = activeRow.data("sampleid");
            const headerid = activeRow.data("headerid");

               document.getElementById('disposeSampleID').value = sampleID;
                document.getElementById('headerID').value = headerid;
                new bootstrap.Modal(document.getElementById('disposeSamplesModal')).show();
        });
    
    $(document).ready(function () {
            // Handle row selection/*
           
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
                        tableBody.append(`<tr  data-sampleid="${sample.SampleID}"  
                                            data-headerid="${sample.HeaderID}">
                            <td>${sample.Date}</td>
                            <td>${sample.SamplingDate}</td>
                            <td>${sample.SampleID}</td>
                            <td><img src="${sample.SampleFileKey}" alt="Sample File" width="50"/></td>
                            <td>${sample.BatchNo}</td>
                            <td>${sample.ManufactureDate}</td>
                            <td>${sample.ExpDate}</td>
                            <td>${sample.ExternalSample}</td>
                                 <td>
                                    <button class="btn btn-primary btn-sm action-button" 
                                            data-sampleid="${sample.SampleID}"  
                                            data-headerid="${sample.HeaderID}">Dispose Samples</button>
                                </td>
                        </tr>`);
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
    
  
  function disposesample(){
        // Collect form data
     const formData = new FormData(document.getElementById('disposeSamplesForm'));
     for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i); // Get the key
        const value = localStorage.getItem(key); // Get the corresponding value
        formData.append(key, value); // Append the key-value pair to formData
    }
                    
    fetch('ajax/save_sample_disposal.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json()) // Expecting JSON response from the server
    .then(data => {
        if (data.success) {
           toastr.success("disposal recorded saved successfully!");
            // Optionally, clear the form or perform other actions
             const modal = bootstrap.Modal.getInstance(document.getElementById('disposeSamplesModal'));
                modal.hide()
        } else {
            toastr.error('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('An error occurred while saving the data.');
    });
}


</script>


</body>
</html>
