<link href="js/quilljs/1.3.7/quill.snow.css" rel="stylesheet">

<style>
    h2 {
        text-align: center;
        font-size: 24px;
        color: #ffcc00;
        text-shadow: 2px 2px #000;
    }
    label {
        font-size: 18px;
        margin: 10px 0;
    }
    input[type="email"],
    input[type="text"] {
        width: calc(100% - 20px);
        padding: 10px;
        border: none;
        border-radius: 5px;
        margin-bottom: 20px;
        background-color: #444850;
        color: #ffffff;
        font-size: 16px;
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
    

 
 

h2 {
    text-align: center;
    font-size: 24px;
    color: #ffcc00;
    text-shadow: 2px 2px #000;
}

label {
    font-size: 18px;
    margin: 10px 0;
}

input[type="email"],
input[type="text"] {
    width: calc(100% - 20px);
    padding: 10px;
    border: none;
    border-radius: 5px;
    margin-bottom: 20px;
    background-color: #444850;
    color: #ffffff;
    font-size: 16px;
}

#editor {
    height: 80%;
    margin-top: 20px;
    border: 2px solid #ffcc00;
    border-radius: 5px;
}

#send {
    width: 100%;
    padding: 10px;
    background-color: #ffcc00;
    border: none;
    border-radius: 5px;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s;
}

#send:hover {
    background-color: #e6b800;
}

</style>

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
</div>




<div class="modal fade" id="emailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Compose Email Service Order To Subcontractor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div>
                    <label for="to">To:</label>
                    <input type="email" id="emailto" placeholder="Recipient's email">
                </div>
                <div>
                    <label for="to">cc:</label>
                    <input type="email" id="emailcc" placeholder="cc email">
                </div>
                <div>
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" placeholder="Email subject">
                </div>
                <div id="editor"></div>
                
            </div>
            <div class="modal-footer">
                <button id="send">Send Email</button>
            </div>
        </div>
    </div>
</div>

    
 
<script src="js/quilljs/1.3.7/quill.min.js"></script>
<script>
   
     // Initialize Quill editor
    quill = new Quill('#editor', {
        theme: 'snow'
    });

    // Send button functionality
    document.getElementById('send').addEventListener('click', function() {
        const emailto = document.getElementById('emailto').value;
        const emailcc = document.getElementById('emailcc').value;
        const subject = document.getElementById('subject').value;
        const content = quill.root.innerHTML;

        // Here you can implement the logic to send the email
        console.log('Email Details:', { to, subject, content });
        
        $.ajax({
           url: 'functions/sendemails.php',
           type: 'POST',
           data: {
               sendcc:emailcc,
               sendto:emailto,
               subject:subject,
               content:content  
           },
           beforeSend: function () {
               toastr.info("sending...");
           },
           success: function (response) {
               if(response.success){
                 toastr.info('email sent') ;
               }else{
                 toastr.error(response) ;
               }
           },
           error: function () {
               toastr.error("Failed to send email.");
           }
       });
        
        $('#emailModal').fadeOut();
        // For example, you could use AJAX to send the email
    });
    
    
    $(document).ready(function () {
        fetchSampleIDs();
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


function loadTestParameters(sampleID, userid) {
    // Validate input before making the request
    if (!sampleID || !userid) {
        toastr.error("Invalid Sample ID or User ID provided.");
        return;
    }

    // AJAX call to fetch test parameters
    $.ajax({
        url: 'ajax/getcomposedemail.php', // Backend script for fetching email details
        method: 'POST',
        data: { 
            sampleID: sampleID,
            contractor: userid
        },
        success: function (response) {
                 console.log('assigned',response);
       
               // Ensure response is parsed correctly
                if (response.success) {
                    // Populate modal form fields
                    $('#emailto').val(response.emailto || ""); // Fallback to empty string
                    $('#emailcc').val(localStorage.getItem('email') || ""); // Fallback to localStorage email
                    $('#subject').val(response.subject || "");
                    
                    // Set content in Quill editor
                    quill.root.innerHTML = response.body || "";

                    // Initialize and display Bootstrap modal
                    const modal = new bootstrap.Modal(document.getElementById('emailModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    modal.show();
                } else {
                    // Handle unsuccessful response
                    toastr.error(response.message || "Failed to load data.");
                }
             
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            toastr.error("Failed to fetch test parameters. Please try again.");
        }
    });
}

</script>
