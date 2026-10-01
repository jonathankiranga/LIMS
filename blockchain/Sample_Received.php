<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sample List</title>
  
  <style>
    .required { color: red; }
  </style>
</head>
<body>

  <!-- Sample List Table -->
  <div class="container mt-5">
    <h3>Sample List</h3>
    <table class="table table-bordered" id="sampleTable">
      <thead>
        <tr>
          <th>Sample ID</th>
          <th>Sample Name</th>
          <th>Sample Image</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <!-- Sample rows will be populated here dynamically -->
      </tbody>
    </table>
  </div>

  <!-- Modal for receiving the sample -->
 
  <div class="modal fade" id="receiveSampleModal" tabindex="-1" aria-labelledby="receiveSampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header py-2">
          <h6 class="modal-title" id="receiveSampleModalLabel"><i class="fa fa-box-open"></i> Receive Sample</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-2">
          <form id="receiveSampleForm">
            <div class="row g-2">
              <!-- First column -->
              <div class="col-md-4">
                <div class="mb-2">
                  <label for="sampleId" class="form-label">Sample ID <span class="required">*</span></label>
                  <input type="text" id="sampleId" name="sampleId" class="form-control form-control-sm" readonly>
                </div>
                
                <div class="mb-2">
                  <label for="storageLocation" class="form-label">Storage Location</label>
                  <input type="text" id="storageLocation" name="storageLocation" class="form-control form-control-sm">
                </div>
                  
                  <div class="mb-2">
                  <label for="remarks" class="form-label">Remarks</label>
                  <textarea id="remarks" name="remarks" class="form-control form-control-sm" rows="2"></textarea>
                </div>
              </div>
              <!-- Second column -->
              <div class="col-md-4">
                <div class="mb-2">
                  <label for="sampleName" class="form-label">Sample Name</label>
                  <input type="text" id="sampleName" name="sampleName" class="form-control form-control-sm" readonly>
                </div>
                <div class="mb-2">
                  <label for="assignedDepartment" class="form-label">Assigned Department/Technician</label>
                  <select id="assignedDepartment" name="assignedDepartment" class="form-select form-select-sm">
                    <option value="" disabled selected>Select Department</option>
                    <option value="chemical">Chemical</option>
                    <option value="microbiological">microbiological</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label for="condition" class="form-label">Condition Upon Arrival</label>
                  <select id="condition" name="condition" class="form-select form-select-sm">
                    <option value="intact">Intact</option>
                    <option value="damaged">Damaged</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>
              <!-- Third column -->
              <div class="col-md-4">
                <div class="mb-2">
                  <img id="modalsampleImage" name="modalsampleImage"  alt="Sample Image" class="img-fluid rounded border" style="max-height:100px;">
                </div>
              </div>
            </div>
            <!-- Submit button -->
            <div class="text-end">
                <button type="button" onclick="receivesampletolab();" class="btn btn-primary btn-sm"><i class="fa fa-save"></i>Receive</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>


<script>
    // Ensure modal state is reset when it is closed, even manually
  document.getElementById('receiveSampleModal').addEventListener('hidden.bs.modal', function () {
    console.log('Modal hidden, resetting form...');
    const form = document.getElementById('receiveSampleForm');
    form.reset(); // Reset form values when modal is hidden
    document.getElementById('sampleImage').src = ''; // Clear the image src
  });
    // Function to show the modal and populate the form
  function showReceiveForm(sample) {
    console.log('SampleFileKey:', sample.SampleFileKey);
    const department = localStorage.getItem('department');
     validateUser(department,sample.SampleID, function(success) {
        if (success) {
                 $('#assignedDepartment').val(department).change();
                // Fill the form fields with sample data using jQuery
                 $('#sampleId').val(sample.SampleID);
                 $('#sampleName').val(sample.StandardName);
                 $('#modalsampleImage').attr('src', sample.SampleFileKey);
                 // Show the modal using Bootstrap's Modal plugin with options
                 const myModal = new bootstrap.Modal($('#receiveSampleModal')[0], {
                     backdrop: 'static', // Prevent closing on outside click
                     keyboard: false     // Prevent closing with the Esc key
                 });
                 myModal.show();
        }else{
             toastr.error('No parameters for '+department+' department');
        } 
    });
  }

  function validateUser(department, SampleID, callback) {
    // Prepare the data to send in the request body
    const data = { department: department, SampleID: SampleID };
  // Use the fetch API
    fetch('ajax/validateUserDepartment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json', // Set the content type as JSON
        },
        body: JSON.stringify(data), // Convert data to a JSON string
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json(); // Parse the JSON response
    })
    .then(data => {
        if (data.success) {
            callback(true);
        } else {
            callback(false);
        }
    })
    .catch(error => {
       callback(false);
    });
}

 
  // Fetch sample data using AJAX and populate the table
  function fetchSamples() {
    let department = localStorage.getItem('department');
    $.ajax({
        url: 'ajax/SamplesRecievedList.php',
        type: 'POST',
        data: { department: department },
        dataType: 'json',
        success: function (response) {
            if (!response.success) {
                // Display backend error message using alert
                toastr.error(response.message || 'An unexpected error occurred.');
                return;
            }
         // Clear the table body
            const tbody = $('#sampleTable tbody');
            tbody.empty();

            if (response.samples && response.samples.length > 0) {
                response.samples.forEach(sample => {
                tbody.append(`
                        <tr>
                            <td>${sample.SampleID}</td>
                            <td>${sample.StandardName}</td>
                            <td><img id="sampleImage" src="${sample.SampleFileKey}" alt="Sample Image" class="img-fluid rounded border" style="max-height: 200px;"></td>
                            <td>
                                <button class="btn btn-primary btn-sm receiveSampleBtn" data-sample='${JSON.stringify(sample)}'>
                                    Receive Sample
                                </button>
                            </td>
                        </tr>
                    `);
                });

                // Attach event listeners to buttons
                $('.receiveSampleBtn').on('click', function () {
                    const sample = $(this).data('sample');
                    showReceiveForm(sample);
                });
            } else {
                // No samples found, use alert
                toastr.error('No samples found for the selected department.');
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('AJAX error:', textStatus, errorThrown);
            toastr.error('An error occurred while fetching the samples. Please try again.');
        }
    });
  }
   
  function receivesampletolab(){
        // Collect form data
          const formData = new FormData(document.getElementById('receiveSampleForm'));
  
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i); // Get the key
            const value = localStorage.getItem(key); // Get the corresponding value
            formData.append(key, value); // Append the key-value pair to formData
        }
  
        // Send AJAX request
        fetch('ajax/receiveSample.php', { // Update with the correct PHP script path
            method: 'POST',
            body: formData
        })
        .then(response => response.json()) // Expect JSON response from server
        .then(data => {
            if (data.success) {
                toastr.success('Sample received successfully!');
         fetchSamples(); // Call the fetch function if implemented
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('receiveSampleModal'));
                modal.hide();
                // Optionally, refresh the table or page content
               
            } else {
                toastr.error('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('An unexpected error occurred.');
        });
        
        

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
        
        
    }
 
   $(document).ready(function () {
        fetchSamples();
    });
</script>

</body>
</html>
