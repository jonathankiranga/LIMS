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
                    <label for="emailto">To:</label>
                    <input type="email" id="emailto" placeholder="Recipient's email">
                </div>
                <div>
                    <label for="emailcc">CC:</label>
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
(function () {
    // Initialize Quill once when this page fragment is loaded.
    const editorElement = document.getElementById('editor');
    if (!editorElement) return;

    const quill = new Quill(editorElement, { theme: 'snow' });
    const sendButton = document.getElementById('send');
    const modalElement = document.getElementById('emailModal');

    sendButton.addEventListener('click', function () {
        const emailto = document.getElementById('emailto').value.trim();
        const emailcc = document.getElementById('emailcc').value.trim();
        const subject = document.getElementById('subject').value.trim();
        const content = quill.root.innerHTML;
        const plainContent = quill.getText().trim();

        if (!emailto || !subject || !plainContent) {
            toastr.error('Recipient, subject and email message are required.');
            return;
        }

        if (!/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(emailto)) {
            toastr.error('Enter a valid recipient email address.');
            return;
        }

        if (emailcc && !/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(emailcc)) {
            toastr.error('Enter a valid CC email address or leave it blank.');
            return;
        }

        sendButton.disabled = true;
        $.ajax({
            url: 'functions/sendemails.php',
            type: 'POST',
            dataType: 'json',
            data: {
                sendcc: emailcc,
                sendto: emailto,
                subject: subject,
                content: content
            },
            beforeSend: function () {
                toastr.info('Sending email...');
            },
            success: function (response) {
                if (response && response.success) {
                    toastr.success('Email sent successfully.');
                    bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                } else {
                    toastr.error((response && (response.error || response.message)) || 'Email could not be sent.');
                }
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                toastr.error((response && (response.error || response.message)) || 'Failed to send email. Please try again.');
            },
            complete: function () {
                sendButton.disabled = false;
            }
        });
    });

    $(document).ready(function () {
        fetchSampleIDs();
    });

    function fetchSampleIDs() {
        $.ajax({
            url: 'ajax/getassinedsubcontractedlabtestAjax.php',
            method: 'GET',
            dataType: 'json',
            data: { category: 'admin', department: 'admin' },
            success: function (response) {
                const sampleList = $('#sampleList');
                sampleList.empty();

                if (!response || response.success !== true || !Array.isArray(response.samples) || response.samples.length === 0) {
                    sampleList.text((response && response.message) || 'No outstanding contracted samples are available.');
                    return;
                }

                // Group samples by contractor, then show each unique SampleID once.
                const groups = new Map();
                response.samples.forEach(function (sample) {
                    const contractorId = String(sample.subcontractor || '');
                    if (!contractorId) return;

                    if (!groups.has(contractorId)) {
                        groups.set(contractorId, {
                            name: String(sample.name || ('Subcontractor ' + contractorId)),
                            samples: new Map()
                        });
                    }
                    groups.get(contractorId).samples.set(String(sample.SampleID), sample);
                });

                groups.forEach(function (group, contractorId) {
                    const section = $('<section class="mb-3"></section>');
                    $('<h4></h4>').text(group.name).appendTo(section);

                    group.samples.forEach(function (sample, sampleID) {
                        const button = $('<button type="button" class="btn btn-outline-primary mb-2 me-2 sample-btn"></button>');
                        button.attr('data-sampleid', sampleID);
                        button.attr('data-contractor', contractorId);
                        button.text(sampleID);
                        section.append(button);
                    });

                    sampleList.append(section);
                });

                if (groups.size === 0) {
                    sampleList.text('No outstanding contracted samples are available.');
                }
            },
            error: function (xhr) {
                console.error('Error loading subcontracted samples:', xhr.status, xhr.responseText);
                $('#sampleList').text('Failed to load subcontracted samples. Please refresh and try again.');
            }
        });
    }

    // Delegated event handler works for dynamically created sample buttons without
    // attaching duplicate handlers each time the sample list is refreshed.
    $('#sampleList').on('click', '.sample-btn', function () {
        loadComposedEmailDetails($(this).attr('data-sampleid'), $(this).attr('data-contractor'));
    });

    function loadComposedEmailDetails(sampleID, contractorID) {
        if (!sampleID || !contractorID) {
            toastr.error('A sample and subcontractor must be selected.');
            return;
        }

        $.ajax({
            url: 'ajax/getcomposedemail.php',
            method: 'POST',
            dataType: 'json',
            data: { sampleID: sampleID, contractor: contractorID },
            success: function (response) {
                if (!response || response.success !== true) {
                    toastr.error((response && response.message) || 'Could not prepare the subcontractor email.');
                    return;
                }

                $('#emailto').val(response.emailto || '');
                $('#emailcc').val(localStorage.getItem('email') || '');
                $('#subject').val(response.subject || '');
                quill.root.innerHTML = response.body || '';

                bootstrap.Modal.getOrCreateInstance(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                }).show();
            },
            error: function (xhr) {
                console.error('Error preparing subcontractor email:', xhr.status, xhr.responseText);
                const response = xhr.responseJSON;
                toastr.error((response && response.message) || 'Failed to prepare the subcontractor email.');
            }
        });
    }
})();
</script>
