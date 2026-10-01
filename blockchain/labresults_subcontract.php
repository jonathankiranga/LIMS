<style>
    .container {
        margin-top: 20px;
    }
</style>
<style>
        /* Style for draggable icon */
        .draggable-icon {
            display: inline-block;
            width: 120px;
            height: 120px;
            background: linear-gradient(145deg, #ff7f50, #ff6347); /* Gradient background */
            color: white;
            border-radius: 15px;
            margin: 8px;
            padding: 10px;
            font-size: 16px;
            line-height: 1.4;
            cursor: grab;
            text-align: center;
            font-weight: bold;
            box-shadow: 4px 4px 8px rgba(0, 0, 0, 0.2); /* Shadow to make it pop */
            transition: all 0.3s ease; /* Smooth transition for hover */
        }

        .draggable-icon:active {
            cursor: grabbing;
            transform: scale(0.98); /* Slight shrink when clicked */
        }

        .draggable-icon:hover {
            box-shadow: 6px 6px 12px rgba(0, 0, 0, 0.3); /* Hover shadow effect */
            transform: translateY(-4px); /* Lift effect on hover */
        }

        /* Style for the user test box */
        #userTestBox {
            min-height: 180px;
            width: 100%;
            border: 2px dashed #007bff; /* Bright blue dashed border */
            padding: 20px;
            background-color: #f0f8ff; /* Light blue background */
            text-align: center;
            border-radius: 15px;
            position: relative;
            box-shadow: 4px 4px 15px rgba(0, 0, 0, 0.15); /* Soft shadow */
            transition: background-color 0.3s ease; /* Smooth transition for background change */
        }

        #userTestBox:hover {
            background-color: #e6f7ff; /* Lighter blue on hover */
        }

        /* Adding some playful animations */
        @keyframes bounce {
            0% { transform: translateY(0); }
            25% { transform: translateY(-5px); }
            50% { transform: translateY(0); }
            75% { transform: translateY(-5px); }
            100% { transform: translateY(0); }
        }

        /* Apply bouncing animation to icons when dragged */
        .draggable-icon.dragging {
            animation: bounce 0.5s ease infinite; /* Infinite bouncing animation */
        }

        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 10; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            background-color: rgba(0, 0, 0, 0.7); /* Dark semi-transparent overlay */
        }

        .modal-content {
            background: linear-gradient(145deg, #282c34, #3c4048); /* Game-like gradient */
            border: 2px solid #4CAF50; /* Green border for a classic game UI feel */
            border-radius: 10px; /* Rounded edges */
            color: #e0e0e0; /* Light text color */
            width: 60%; /* Default width */
            max-width: 600px; /* Maximum width */
            height: auto; /* Adjust height automatically */
            max-height: 80vh; /* Restrict height to viewport */
            margin: auto; /* Center horizontally */
            position: relative;
            top: 50%;
            transform: translateY(-50%); /* Center vertically */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5); /* Subtle shadow */
            padding: 20px; /* Inner padding */
            text-align: center; /* Centered content */
        }

        .modal-content h2 {
            font-size: 24px; /* Larger heading for emphasis */
            color: #4CAF50; /* Accent green */
            margin-bottom: 20px;
            font-family: 'Press Start 2P', sans-serif; /* Retro game font */
            text-shadow: 2px 2px #000; /* Shadow for depth */
        }

        .modal-content form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px; /* Space between elements */
        }

        .modal-content label {
            font-family: 'Press Start 2P', sans-serif; /* Retro font */
            font-size: 12px;
            color: #fff;
        }

        .modal-content input,
        .modal-content select {
            padding: 10px;
            font-size: 14px;
            border: 2px solid #4CAF50;
            border-radius: 5px;
            background-color: #333;
            color: #fff;
            width: 80%;
        }

        .modal-content button {
            padding: 10px 20px;
            font-size: 16px;
            font-family: 'Press Start 2P', sans-serif; /* Retro button style */
            background-color: #4CAF50;
            color: #000;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 4px #2a7f33; /* 3D button effect */
            transition: transform 0.2s;
        }

        .modal-content button:hover {
            transform: scale(1.1); /* Button grows slightly when hovered */
        }

        .modal-content button:active {
            transform: scale(1); /* Button returns to normal size when clicked */
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #e0e0e0;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Press Start 2P', sans-serif; /* Retro font for the close button */
        }

        .close:hover {
            color: #f00; /* Highlight close button */
        }
    </style>
  
<div class="container mt-4">
    <!-- Accordion -->
    <div class="accordion" id="mainAccordion">
        <!-- Sample IDs Section -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    Sub-Contractors Samples
                </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#mainAccordion">
                <div class="accordion-body">
                    <div class="card">
                        <div class="card-body" id="sampleList">
                            <!-- Dynamically populated SampleIDs -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Parameters Section -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
                <button id="dynamicid" class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    Test Parameters for selected sample ID
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#mainAccordion">
                <div class="accordion-body">
                    <div class="card">
                        <div class="card-body" id="testParameters">
                            <!-- Draggable test parameter icons will appear here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="resultsModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Test Results Input</h2>
        <form id="testResultsForm">
            <input type="hidden" id="resultsid" name="resultsid" readonly>
            <div class="row">
                <div class="form-group">
                    <label for="sampleID">Sample ID:</label>
                    <input type="text" id="sampleID" name="sampleID" readonly>
                </div>
                <div class="form-group">
                    <label for="parameterName">Parameter Name:</label>
                    <input type="text" id="parameterName" name="parameterName" readonly>
                </div>
            </div>
            <div class="row">
                <div class="form-group">
                    <label for="resultType">Result Type:</label>
                    <select id="resultType" name="resultType" onchange="showInputField(this.value)">
                        <option value="" disabled selected>Select a result type</option>
                        <option value="quantitativeField">Quantitative Result</option>
                        <option value="qualitativeField">Qualitative Result</option>
                        <option value="rangeField">Range Result</option>
                    </select>

                    <div id="quantitativeField" class="input-field">
                        <label for="quantitative">Enter Concentration/Value:</label>
                        <input type="number" id="quantitative" name="quantitative" placeholder="Enter concentration/value">
                    </div>

                    <div id="qualitativeField" class="input-field">
                        <label for="qualitative">Select Status:</label>
                        <select id="qualitative" name="qualitative">
                            <option value="Not Detected">Not Detected</option>
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

                    <div id="rangeField" class="input-field">
                        <label for="range">Enter Range (e.g., <2 or Between 1-2):</label>
                        <input type="text" id="range" name="range" placeholder="Enter range">
                    </div>
                </div>
            </div>
            <button type="submit">Submit Results</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    fetchSampleIDs();

     //  Event listener for test parameter clicks
    $(document).on('click touchstart', '.draggable-icon', function () {
        const parameterName = $(this).text();
        const parameterID = $(this).data('parameter-id');
        const sampleID = $(this).data('sampleid'); // Replace with dynamic sampleID if available
        const resultsID = $(this).data('resultsid'); // Replace with dynamic sampleID if available
     
      // Populate modal fields
        $('#resultsid').val(resultsID);
        $('#parameterName').val(parameterName);
        $('#sampleID').val(sampleID);
        showInputField();
        // Show modal
        $('#resultsModal').css('display', 'block');
    });

    // Close modal functionality
    $('#closeModal').on('click', function () {
        $('#resultsModal').css('display', 'none');
    });

    // Form submission
       $('#testResultsForm').on('submit', function (event) {
        event.preventDefault();

        const resultType = $('#resultType').val();
        var resultValue;

        if (resultType === "quantitativeField") {
            resultValue = $('#quantitative').val();
        } else if (resultType === "qualitativeField") {
            resultValue = $('#qualitative').val();
        } else if (resultType === "rangeField") {
            resultValue = $('#range').val();
        }

        if (!resultType || !resultValue) {
            toastr.error("All fields are required. Please fill in all values.");
            return;
        }
        
        const formData = {
            resultsid: $('#resultsid').val(),
            sampleID: $('#sampleID').val(),
            parameterName: $('#parameterName').val(),
            resultType: resultType,
            resultValue: resultValue
        };

        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            const value = localStorage.getItem(key);
            formData[key] = value;
        }

        fetch('ajax/submit_test_results.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success("Results saved successfully.");
                    $('#resultsModal').css('display','none');
                   setTimeout(function() { clearform();  },5000);
                    $('#resultsModal').hide();
                    $('.sample-btn').eq(0).click(); 
               } else {
                    toastr.error(data.message || "Failed to save results.");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error("An error occurred while saving results." + error);
            });
    });

});

// Event listener for closing the modal
$('#closeModal').on('click', function () {
    $('#resultsModal').fadeOut();
    $('#quantitative').val("");
    $('#qualitative').val("");
    $('#range').val("");
    $('#resultType').val("");
});

// Optionally, close modal when clicking outside the modal content
$(window).on('click', function (e) {
    if($(e.target).is('#resultsModal')) {
    $('#resultsModal').fadeOut();
    $('#quantitative').val("");
    $('#qualitative').val("");
    $('#range').val("");
    $('#resultType').val("");
    }
});


function fetchSampleIDs() {
    $.ajax({
        url: 'ajax/getassinedsubcontractedlabtestAjax.php',
        method: 'GET',
        data: {
            category: "admin",
            department: "admin"
        },
       success: function (data) {
            console.log('assigned', data);
            const sampleList = $('#sampleList');
            sampleList.empty();
            const samples = data.samples;
          // Group by subcontractor
            const groupedSamples = samples.reduce((acc, sample) => {
                const subcontractorId = sample.subcontractor;
                if (!acc[subcontractorId]) {
                    acc[subcontractorId] = {
                        name: sample.name, // Using the name field for the header
                        samples: []
                    };
                }
                acc[subcontractorId].samples.push(sample);
                return acc;
            }, {});

            for (const subcontractorId in groupedSamples) {
                const group = groupedSamples[subcontractorId];

                if (!$(`#contractor-section-${subcontractorId}`).length) {
      // Create a section for each subcontractor
                const subcontractorHeader = document.createElement('h4');
                subcontractorHeader.textContent = group.name;
                subcontractorHeader.id = `contractor-section-${subcontractorId}`;
                sampleList.append(subcontractorHeader);
                const createdSampleIDs = new Set();
                // Create buttons for each sample under the subcontractor
                group.samples.forEach(sample => {

                    if (!createdSampleIDs.has(sample.SampleID)) {
                        createdSampleIDs.add(sample.SampleID);

                        const button = document.createElement('button');
                        button.className = 'btn btn-outline-primary btn-block mb-2 sample-btn';
                        button.setAttribute('data-sampleid', sample.SampleID);
                        button.setAttribute('data-contractor', sample.subcontractor);
                        button.textContent = sample.SampleID;
                        sampleList.append(button);
                    }

                });
            }

            // Add click event to dynamically generated buttons
            $('.sample-btn').on('click', function () {
                const sampleID = $(this).data('sampleid');
                const contractor = $(this).data('contractor');

                loadTestParameters(sampleID, contractor);
            });
           }
        },

        error: function (err) {
            console.error("Error loading SampleIDs:", err);
        }
    });
}

function loadTestParameters(sampleID,userid) {
  $('#dynamicid').text(`Test Parameters for selected sample ID ${sampleID}`);

   $.ajax({
        url: 'ajax/getassignedTestParameters2.php', // Endpoint for fetching test parameters
        method: 'POST',
        data: { 
            sampleID:sampleID ,
            userid:userid
        },
        success: function (data) {
             const samples=data.samples;
            const testParameters = $('#testParameters');
            testParameters.empty();
            samples.forEach(param => {
                testParameters.append(createDraggableIcon(param));
            });
        },
        error: function (err) {
            console.error("Error loading test parameters:", err);
        }
    });
}

function createDraggableIcon(rowsdata) {
    const parameterName = rowsdata.ParameterName;
    const parameterID = rowsdata.ParameterID;
    const SampleID = rowsdata.SampleID;
    const resultsID = rowsdata.resultsID;
    
    const iconDiv = document.createElement('div');
    iconDiv.classList.add('draggable-icon', 'text-center', 'mb-3');
    iconDiv.setAttribute('draggable', true);
    iconDiv.setAttribute('data-resultsid', resultsID);
    iconDiv.setAttribute('data-sampleid', SampleID);
    iconDiv.setAttribute('data-parameter-id', parameterID);
    iconDiv.textContent = parameterName;

    // Add drag event
    iconDiv.addEventListener('dragstart', function (event) {
        event.dataTransfer.setData("parameterID", parameterID);
    });

    return iconDiv;
}

function allowDrop(event) {
    event.preventDefault(); // Allow the drop operation
}

function drop(event) {
    event.preventDefault();

    const parameterID = event.dataTransfer.getData("parameterID");
    const draggedElement = document.querySelector(`[data-parameter-id="${parameterID}"]`);

    if (draggedElement) {
        const userTestBox = document.getElementById('userTestBox');
        userTestBox.appendChild(draggedElement.cloneNode(true)); // Add a copy to the User Test Box
    }
}

// Function to toggle input fields based on result type
function showInputField(value) {
    document.getElementById('quantitativeField').style.display = 'none';
    document.getElementById('qualitativeField').style.display = 'none';
    document.getElementById('rangeField').style.display = 'none';

    if (value) {
        document.getElementById(value).style.display = 'block';
    }
}

function clearform(){
    $('#quantitative').val("");
    $('#qualitative').val("");
    $('#range').val("");
    $('#resultType').val("");
}
</script>