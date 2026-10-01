<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generate PDF</title>
<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/all.min.js"></script>
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
    
</style>
</head>
<body>

<h2>Sample Test Summary by Date</h2>
<div class="container">
   <div class="date-filter-container">
  <label for="fromDate">From:</label>
  <input type="date" id="fromDate" name="fromDate" value="<?= date('Y-m-01'); ?>">

  <label for="toDate">To:</label>
  <input type="date" id="toDate" name="toDate" value="<?= date('Y-m-d'); ?>">

  <button id="generateReport" class="btn btn-primary">
    <i class="fa-solid fa-file-pdf"></i> Generate Report
  </button>
</div>

    <div id="progress-container" style="display: none;">
       <label for="progress-bar">Data Transfer Progress:</label>
       <progress id="progress-bar" value="0" max="100"></progress>
   </div>
</div>



<script>
    
    
$('#generateReport').on('click', function() {
    const fromDate = $('#fromDate').val();
    const toDate = $('#toDate').val();
    if(!fromDate || !toDate) {
        alert('Please select both dates.');
        return;
    }

    const sampleID = `${fromDate}|${toDate}`; // Your PHP expects sampleID as date range
   
    getreportwithoption(sampleID);
});

   
    function getreportwithoption(sampleIDdata){
    // Show the progress bar
    $("#progress-container").show();
    $("#progress-bar").val(0);

    $.ajax({
        url: `functions/Testreportbydateandanydate.php`, // Adjust the path as necessary
        method: 'POST',
        data: {
            sampleIDdata: sampleIDdata
        },
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
        xhrFields: {
            responseType: 'blob' // Important for handling binary data
        },
        success: function (data) {
            // Create a blob URL for the PDF
            const blob = new Blob([data], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);

            // Open the PDF in a new tab
            window.open(url);

            // Hide the progress bar
            $("#progress-container").hide();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error generating PDF:', textStatus, errorThrown);

            // Hide the progress bar
            $("#progress-container").hide();
        }
    });
}

        
</script>

</body>
</html>
