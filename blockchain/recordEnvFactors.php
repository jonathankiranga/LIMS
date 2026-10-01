<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sub-Contractor</title>

<style>
/* Word Box Styling */
/* Increase the modal's width */
.modal-dialog {
    max-width: 80%; /* Increases horizontal space, you can use 900px or other values */
    margin: 30px auto; /* Adjusts vertical space */
}
/* Ensure line height is 1 across all elements */
.modal-body, .modal-header, .modal-footer, .form-control, select {
    line-height: 1; /* Reduced line height for compactness */
    padding: 0; /* Remove padding */
}
/* If needed, adjust form padding and margins */
.mb-3 {
    margin-bottom: 0; /* Remove bottom margin */
}
.modal-content {
    padding: 0; /* Remove padding from modal content */
}
.modal-body {
    padding: 10px 0; /* Optional, adjust modal-body padding */
}
/* Optional: Reduce the padding inside form inputs and selects */
.form-control {
    padding: 5px; /* Reduce padding in form inputs */
}
/* Optional: Styling for select dropdown to make it compact */
select.form-control {
    padding: 5px; /* Reduced padding */
}
/* Word Box Styling */
.word-box {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); /* Automatically fill columns with minimum width */
    gap: 5px; /* Reduced gap for a more compact layout */
    width: 100%; /* Fill the width of the parent container */
    border: 1px solid #ccc;
    padding: 0; /* No padding to make it tight */
    margin-bottom: 20px;
    background-color: #f9f9f9;
    line-height: 1; /* Set line height to 1 for compact text */
}
.draggable-word {
    padding: 2px 5px; /* Reduced padding for a more compact look */
    border: 1px solid #ccc;
    cursor: pointer;
    background-color: #e0f7fa;
    border-radius: 5px;
    text-align: center;
    font-size: 14px; /* Reduced font size for compactness */
    line-height: 1; /* Set line height to 1 for compact text */
}
.draggable-word:active {
    background-color: #80deea;
}
/* User Box Styling */
.user-box {
    width: 300px;
    height: 150px;
    border: 1px solid #ccc;
    padding: 5px;
    background-color: #f0f0f0;
    min-height: 100px;
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: flex-start;
    line-height: 1;
}
/* Styling for Dropped Word */
.dropped-word {
    background-color: #c8e6c9;
    padding: 5px;
    margin-top: 5px;
    border-radius: 3px;
    color: #388e3c;
    line-height: 1;
    font-size: 14px;
}
/* Disabled Word Styling */
.dropped {
    pointer-events: none;
    opacity: 0.5;
}
/* .user-box expands dynamically as items are dropped */
.user-box {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px; /* Increased gap for spacing between items */
    width: 100%;
    min-height: 100px; /* Minimum height to ensure it has some space */
    max-height: 400px; /* Prevents it from growing indefinitely */
    overflow-y: auto; /* Allows scrolling if the content exceeds max height */
    border: 2px solid #007bff; /* Subtle border with a blue color */
    background-color: #f7f9fc; /* Light, clean background color */
    border-radius: 10px; /* Rounded corners */
    padding: 10px; /* Padding inside the box */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Light shadow for depth */
}
/* Modal header with padding and modern background */
.modal-header {
    background-color: #007bff; /* Blue background */
    color: white; /* White text for contrast */
    padding: 20px; /* Increased padding for more space */
    border-radius: 10px 10px 0 0; /* Rounded corners for the top */
}
/* Modal content with improved padding */
.modal-content {
    padding: 20px; /* Increased padding for more spacing */
}
/* A better appearance for the form controls */
.form-control {
    border-radius: 5px; /* Rounded edges for form inputs */
    padding: 10px; /* Add padding for better feel */
    border: 1px solid #ccc; /* Light border */
}
/* Button styles */
.btn {
    padding: 10px 20px; /* Increased padding for better touch targets */
    border-radius: 5px; /* Rounded corners */
    background-color: #28a745; /* Green button color */
    color: white;
    border: none;
}
.btn:hover {
    background-color: #218838; /* Darker shade on hover */
}

.cancel-btn {
    margin-left: 5px;
    background-color: #ff4d4d;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
}

.cancel-btn:hover {
    background-color: #cc0000;
}

</style>

<style>/* Style the entire modal content */
.modal-content {
    background-color: #fdfdfd; /* Light background for the modal */
    border: 2px solid #007bff; /* Blue border for distinction */
    border-radius: 10px; /* Smooth corners */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Slight shadow for depth */
}

/* Style the modal header specifically */
.modal-header {
    background-color: #007bff; /* Blue background for the header */
    color: white; /* White text for contrast */
    padding: 20px; /* Comfortable spacing */
    border-radius: 10px 10px 0 0; /* Rounded corners at the top */
}

/* Style modal buttons */
.btn {
    background-color: #28a745; /* Green buttons for a vibrant look */
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
}

.btn:hover {
    background-color: #218838; /* Darker green on hover */
}

/* Cancel button with distinctive color */
.cancel-btn {
    margin-left: 5px;
    background-color: #ff4d4d; /* Bright red for cancel */
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
}

.cancel-btn:hover {
    background-color: #cc0000; /* Darker red on hover */
}
</style>
</head>
<body>
    
 
<div class="container my-5">
    <div>
         <button class="btn btn-primary" type="button" id="new_customer">
                <i class="fa fa-plus"></i>Create New Lab Sub Contractor
            </button>
    </div>   
    <h1>Sample Tests</h1>
    <table class="table table-bordered" id="sampleTable">
        <thead>
            <tr>
                <th>Test ID</th>
                <th>Sample ID</th>
                <th>Sample Standard</th>
                <th>Batch No</th>
                <th>Batch Size</th>
                <th>Manufacture Date</th>
                <th>Expiration Date</th>
                <th>External Sample</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- Dynamic rows will be inserted here -->
        </tbody>
    </table>
</div>
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="scheduleForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Sample Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sampleID" class="form-label">Sample ID</label>
                        <input type="text" class="form-control" id="sampleID" name="sampleID" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="testID" class="form-label">Sample Standard</label>
                        <input type="hidden" class="form-control" id="testID" name="testID" readonly>
                        <input type="text" class="form-control" id="standardname" name="standardname" readonly>
                    </div>
                    
                    <div class="mb-3" id="ParameterAllocationLIST">
                       
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="saveAssignedTests();" class="btn btn-success">Save Allocation</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal HTML -->
<div class="modal fade" id="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">New Lab Contractor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <form id="customerForm">
                    <table class="table table-sm"><tbody>
                   <tr><td valign="top">
                   <table>
                       <tbody><tr><td>Name</td><td><input type="text" name="customer" maxlength="50" required="required"></td></tr>
                           <tr><td>Address</td><td><input type="text" name="company" maxlength="50"></td></tr>
                           <tr><td>Address 2</td><td><input type="text" name="postcode" maxlength="100"></td></tr>
                           <tr><td>city</td><td><input type="text" name="city" maxlength="50"></td></tr>
                           <tr><td>Country</td><td><select name="country" id="countrySelect"></select></td></tr>
                           <tr><td>Telephone No</td><td><input type="text" name="phone" maxlength="15"></td></tr>
                           <tr><td>Alt Contact</td><td><input type="text" name="altcontact" maxlength="100"></td></tr>
                           <tr><td>email</td><td><input type="text" name="email" maxlength="100" pattern="[a-z0-9!#$%&amp;'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"></td></tr>
                           <tr><td>Block Account</td><td><select name="inactive"><option value="0">No</option><option value="1">Yes</option></select></td></tr>
                       </tbody></table>
                       </td>
                   </tr>
                  </tbody>
                </table>
                     <button type="submit" class="btn btn-primary">SAVE</button>
              </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
    <div id="responseMessage"></div>
</div>

<script>
      // Event listener for the "Add New Customer" button
$('#new_customer').click(function() {
    const modal = new bootstrap.Modal(document.getElementById('modal'), { backdrop: 'static', keyboard: false});
    modal.show(); // Show the modal
});

 $.ajax({
    url: 'jsonfiles/Countriesarray.php', // Replace with your actual data source
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        // Assuming 'data' is an array of country names
        var countrySelect = $('#countrySelect');
        $.each(data, function(index, country) {
            countrySelect.append($('<option></option>').attr('value', country).text(country));
        });
    },
    error: function(xhr, status, error) {
        toastr.error('Error fetching countries:'+ error.message);
    }
});

$(document).ready(function () {
    fetchSamples();
    
    $('#customerForm').on('submit', function (e) {
        e.preventDefault(); // Prevent form submission
        $.ajax({
            url: 'ajax/Save_subcontractor.php', // Server-side script
            method: 'POST',
            data: $(this).serialize(), // Serialize form data
            success: function (response) {
                $('#responseMessage').html(response); // Display response
                
                const modal = new bootstrap.Modal(document.getElementById('modal'));
                 modal.hide(); // Show the modal
            },
            error: function () {
                $('#responseMessage').html('<span style="color: red;">An error occurred.</span>');
            }
        });
    });
    
});
     

function returnItemToWordBox(parameterID) {
    // Get the word-box and user-box elements
    const wordBox = document.getElementById('word-box');
    const userBox = document.getElementById('userBox');

    // Find the item in the user-box using the parameterID
    const itemToReturn = userBox.querySelector(`[data-parameter-id="${parameterID}"]`);

    if (itemToReturn) {
        // Remove the "dropped" class and re-enable the item
        itemToReturn.classList.remove('dropped');
        itemToReturn.removeAttribute('disabled');
        itemToReturn.style.backgroundColor = ''; // Reset the background color

        // Move the item back to the word-box
        wordBox.appendChild(itemToReturn);

        // Optionally, remove the item from the assignedTests object if it exists
        if (assignedTests) {
            for (const user in assignedTests) {
                if (assignedTests[user].includes(parameterID)) {
                    assignedTests[user] = assignedTests[user].filter(id => id !== parameterID); // Remove the parameterID
                    break;
                }
            }
        }

        console.log(`Item with ID ${parameterID} returned to word-box.`);
    } else {
        console.warn(`Item with ID ${parameterID} not found in user-box.`);
    }
}

function fetchSamples() {
        $.ajax({
            url: 'ajax/SamplescheduleList2.php',
            method: 'POST',
            data: {
                department: 'admin'
            },
            dataType: 'json',
            success: function (samples) {
                let tableBody = $('#sampleTable tbody');
                tableBody.empty();

                samples.forEach(sample => {
                    tableBody.append(`
                        <tr style="line-height: 1; margin: 0; padding: 0;color: blue;">
                            <td>${sample.TestID}</td>
                            <td>${sample.SampleID}</td>
                            <td>${sample.StandardName}</td>
                            <td>${sample.BatchNo}</td>
                            <td>${sample.BatchSize}</td>
                            <td>${formatDate(sample.ManufactureDate)}</td>
                            <td>${formatDate(sample.ExpDate)}</td>
                            <td>${sample.ExternalSample}</td>
                            <td>
                                <button class="btn btn-primary btn-sm schedule-btn" 
                                        data-sampleid="${sample.SampleID}" 
                                        data-testid="${sample.TestID}" 
                                        data-standardname="${sample.StandardName}">Select Sub-Contractor</button>
                            </td>
                        </tr>
                    `);
                     const draggableItem = createDraggableItem(sample.StandardName, sample.TestID);
                      $('#word-box').append(draggableItem);           
                });
                

                // Attach click event to dynamically added buttons
                $('.schedule-btn').on('click', function () {
                    const sampleID = $(this).data('sampleid');
                    const testID = $(this).data('testid');
                    const standardname = $(this).data('standardname');
                   // Populate modal fields
                    $('#scheduleModal #sampleID').val(sampleID);
                    $('#scheduleModal #testID').val(testID);
                    $('#scheduleModal #standardname').val(standardname);
                     // Show the modal

                    getregisteredparameters();

                    var myModal = new bootstrap.Modal(document.getElementById('scheduleModal'), 
                    { backdrop: false, keyboard: true  });
                          myModal.show();
                });


              },
            error: function (xhr, status, error) {
            console.error('Error fetching samples:', error);
            }
        });
}



function closeModal() {
    const wordBox = document.getElementById('userBox');
    wordBox.innerHTML = "";
    
    var modalElement = document.getElementById('scheduleModal');
    var modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
}   

function getregisteredparameters(){
    const formElement = document.getElementById('ParameterAllocationLIST'); // Get the form element
          formElement.innerHTML = ""; // Use innerHTML to update the content

     const testID = document.getElementById('testID');
   
       $.ajax({
           url: 'ajax/subcontractorAllocationTable.php',
           type: 'POST',
           data: {testID:testID.value},
           beforeSend: function () {
               toastr.info("Generating allocation table...");
           },
           success: function (response) {
                formElement.innerHTML = response;
           },
           error: function () {
               toastr.error("Failed to generate allocation table.");
           }
       });
}    

function createDraggableItem(parameterName, parameterID) {
    const item = document.createElement('div');
    item.classList.add('draggable'); // Add styling class for draggable items
    item.textContent = parameterName;  // Set the name as the item text
    item.draggable = true;  // Make the item draggable
    item.setAttribute('data-parameter-id', parameterID);  // Store the parameter ID for reference

    // Create a cancel button
    const cancelBtn = document.createElement('button');
    cancelBtn.classList.add('cancel-btn');
    cancelBtn.textContent = 'X';
    cancelBtn.onclick = function (e) {
        e.stopPropagation();  // Prevent triggering other drag events
        returnItemToWordBox(parameterID);  // Call function to return the item back to word box
    };

    item.appendChild(cancelBtn);  // Append the cancel button to the item

    // Add dragstart event listener to handle the drag event
    item.addEventListener('dragstart', function (e) {
        e.dataTransfer.setData('parameterID', parameterID);  // Store the ID for the drag operation
    });

    return item;  // Return the created draggable item
}

function returnItemToWordBox(parameterID) {
    const wordBox = document.getElementById('word-box');
    const itemToReturn = document.querySelector(`[data-parameter-id="${parameterID}"]`);

    // Move the item back to the word box (if it exists)
    if (itemToReturn) {
        wordBox.appendChild(itemToReturn);  // Move the element back to the word box
    }
}

// Function to allow an element to be dragged
function drag(event) {
  event.dataTransfer.setData("text", event.target.id); // Store the ID of the dragged word
}

// Allow the drop by preventing the default action
function allowDrop(event) {
  event.preventDefault();
}

// Function to handle the drop operation
function drop(event) {
  event.preventDefault();

  // Get the ID of the dragged element
  var data = event.dataTransfer.getData("text");
  var draggedElement = document.getElementById(data);

  // Check if the element has already been dropped
  if (draggedElement.classList.contains('dropped')) {
      return; // If already dropped, do nothing
  }

  // Disable the word in the Word Box (mark it as dropped)
  draggedElement.classList.add('dropped');
  draggedElement.setAttribute('disabled', 'true');
  draggedElement.style.backgroundColor = '#d3d3d3'; // Change color to show it's disabled

  // Add the word to the User Box
  var userBox = event.target;
  var span = document.createElement("span");
  span.textContent = draggedElement.textContent;
  span.classList.add('dropped-word');
  userBox.appendChild(span); // Append the dropped word to the User Box
}


function allowDrop(event) {
    event.preventDefault();
}

function drag(event) {
    event.dataTransfer.setData("text", event.target.id); // Pass the dragged item's ID
}

function drop(event) {
    event.preventDefault();

    const draggedID = event.dataTransfer.getData("text");
    const draggedElement = document.getElementById(draggedID);
    const userBox = document.getElementById('userBox');
    const selectedUser = document.getElementById('userid').value;

    if (!selectedUser) {
         toastr.info("Please select a Sub Contractor before assigning tests.");
        return;
    }

    // Append the dragged item to the user box
    userBox.appendChild(draggedElement);

    // Add the assignment to the tracking object
    if (!assignedTests[selectedUser]) {
        assignedTests[selectedUser] = [];
    }
    assignedTests[selectedUser].push(draggedID.replace('word_', '')); // Store the resultsID
}

// Save the assigned tests to the database
function saveAssignedTests() {
    if (Object.keys(assignedTests).length === 0) {
         toastr.info("No assignments to save.");
          fetchSamples();
        return;
    }

    fetch('ajax/savelabsubassignments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(assignedTests), // Send assignments as JSON
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                 toastr.info("Assignments saved successfully.");
                assignedTests = {}; // Clear the assignments
                getregisteredparameters();
             } else {
                 toastr.info("Error saving assignments: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
             toastr.info("An unexpected error occurred."+error);
        });
        
           closeModal();
          fetchSamples();
        
}

</script>
</body>
</html>
