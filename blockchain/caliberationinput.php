
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4>Control Sample Test Results</h4>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMachineModal">
                    Add New Machine
                </button>
                <form id="testResultForm">
                    <div class="form-group">
                    <label for="equipmentId">Equipment ID</label>
                    <select class="form-control" id="equipmentId" required>
                        <option value="" disabled selected>Select Equipment</option>
                    </select>
                </div>
                    <div class="form-group">
                        <label for="sampleName">Sample Name</label>
                        <input type="text" class="form-control" id="sampleName" required>
                    </div>
                    <div class="form-group">
                        <label for="knownValue">Known Value</label>
                        <input type="number" class="form-control" id="knownValue" step="any" required>
                    </div>
                    <div class="form-group">
                        <label for="measuredValue">Measured Value</label>
                        <input type="number" class="form-control" id="measuredValue" step="any" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Submit Test Result</button>
                    </div>
                    <div id="statusMessage"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS Scripts -->
 <!-- Modal to Add Machine -->
<div class="modal fade" id="addMachineModal" tabindex="-1" aria-labelledby="addMachineModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addMachineModalLabel">Add Machine</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addMachineForm">
          <div class="mb-3">
            <label for="machineName" class="form-label">Machine Name</label>
            <input type="text" id="machineName" name="machineName" class="form-control" required>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Add Machine</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script> 
    function loadmachines(){
            // Fetch available machines from the backend when the page loads
            $.ajax({
                url: 'ajax/getMachines.php',  // Backend endpoint to fetch machine data
                method: 'GET',
                success: function(response) {
                    // Clear the existing options in the dropdown
                    $('#equipmentId').empty();

                    // Add a default "Select Equipment" option
                    $('#equipmentId').append('<option value="" disabled selected>Select Equipment</option>');

                    // Loop through the response and add each machine as an option
                    response.forEach(function(machine) {
                        $('#equipmentId').append(`<option value="${machine.machine_id}">${machine.machine_name}</option>`);
                    });
                },
                error: function(error) {
                    console.error('Error fetching machine list:', error);
                    $('#equipmentId').append('<option value="" disabled>Error loading machines</option>');
                }
            });
        }
        
        $(document).ready(function() {
            $('#testResultForm').on('submit', function(e) {
                e.preventDefault();

                const equipmentId = $('#equipmentId').val();
                const sampleName = $('#sampleName').val();
                const knownValue = parseFloat($('#knownValue').val());
                const measuredValue = parseFloat($('#measuredValue').val());

                if (equipmentId && sampleName && !isNaN(knownValue) && !isNaN(measuredValue)) {
                    const testData = {
                        equipment_id: equipmentId,
                        sample_name: sampleName,
                        known_value: knownValue,
                        measured_value: measuredValue
                    };

                    // Send the AJAX request to the backend API
                    $.ajax({
                        url: 'ajax/control-sample-results.php',  // Backend API endpoint
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify(testData),  // Sending data as JSON
                        success: function(response) {
                            console.log('Test result saved successfully:', response);
                            $('#statusMessage').html('<p class="upload-success">Test result saved successfully!</p>');
                        },
                        error: function(error) {
                            console.error('Error saving test result:', error);
                            $('#statusMessage').html('<p class="upload-error">Error saving test result. Please try again.</p>');
                        }
                    });
                    
                    
                } else {
                    $('#statusMessage').html('<p class="upload-error">Please enter valid values.</p>');
                }
            });
            
            loadmachines();
            // Handle form submission to add a machine
            $('#addMachineForm').on('submit', function (e) {
                e.preventDefault(); // Prevent the default form submission
                // Get the value of the machine name input field
                const machineName = $('#machineName').val();
             // Check if machine name is provided
                if (machineName.trim() === '') {
                    toastr.error("Please enter a machine name.");
                    return;
                }
            // Send the data to the server via AJAX
                $.ajax({
                    url: 'ajax/add-machine.php',  // Backend endpoint to handle machine insertion
                    method: 'POST',
                    data: {
                        machine_name: machineName
                    },
                    success: function (response) {
                        console.log(response);
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success) {
                            toastr.success("Machine added successfully!");
                            bootstrap.Modal.getOrCreateInstance($('#addMachineModal')[0]).hide(); // Close the modal
                            $('#addMachineForm')[0].reset(); // Reset the form
                            loadmachines();
                        } else {
                            toastr.error("Error adding machine. Please try again.");
                        }
                    },
                    error: function (error) {
                        toastr.error("An error occurred while adding the machine.");
                        console.log(error);
                    }
                });
            });
        });



</script>
 
