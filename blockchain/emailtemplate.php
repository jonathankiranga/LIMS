<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Email Template</title>
    <link href="js/quilljs/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        .help-list {
            list-style-type: none;
            padding: 0;
        }
        .help-list li {
            margin: 5px 0;
        }
        #placeholder-const-wrapper {
            display: none;
            margin-top: 16px;
        }
        #placeholder-const {
            background: #f7f7f7;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 12px;
            white-space: pre-wrap;
            font-family: Consolas, Monaco, monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Create Email Template</h2>
        <form action="ajax/save_template.php" method="POST">
            <div class="form-group">
                <label for="event_name">Event Name:</label>
                <select class="form-control" id="event_name" name="event_name" required>
                    <option value="">Select Event</option>
                    <option value="test_approved">Test Approved</option>
                    <option value="sample_received">Sample Received</option>
                      <option value="sample_subcontrating">Sample SubContracting</option>
                </select>
            </div>

            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" class="form-control" id="subject" name="subject" required>
            </div>

            <div class="form-group">
                <label for="body">Body:</label>
                <div id="editor" style="height: 200px;"></div>
                <input type="hidden" name="body" id="body">
            </div>

            <button type="button" id="saveTemplateBtn" class="btn btn-success mt-3">Save Template</button>

        </form>

        <h3 class="mt-4">Dynamic Variables (Placeholders) Help List:</h3>
        <ul class="help-list" id="help-list">
            <!-- Placeholders will be shown here dynamically -->
        </ul>
        <div id="placeholder-const-wrapper">
            <h4 class="mt-3">Placeholder Object</h4>
            <pre id="placeholder-const"></pre>
        </div>
    </div>
  <script src="js/quilljs/1.3.7/quill.min.js"></script>
   <script>
        // Define placeholder lists for each event
        const placeholders = {
            "test_approved": [
                { placeholder: "{{customer_name}}", description: "The name of the customer." },
                { placeholder: "{{sample_id}}", description: "The sample ID associated with the test." },
                { placeholder: "{{test_type}}", description: "The type of test performed." },
                { placeholder: "{{amount_due}}", description: "Amount Due." },
                { placeholder: "{{due_date}}", description: "Due Date" }
            ],
            "sample_received": [
                { placeholder: "{{customer_name}}", description: "The name of the customer." },
                { placeholder: "{{batchno}}", description: "The Batch No associated with the test." },
                { placeholder: "{{test_date}}", description: "The date the test was received." },
                { placeholder: "{{sample_type}}", description: "The type of sample received." }
            ],"sample_subcontrating":[
                { placeholder: "{{contractor_name}}", description: "contractor name." },
                { placeholder: "{{sample_list}}", description: "The list of samples assigned" }
            ],
            // Add more events with placeholders here as needed
        };

        // Default templates for each event
        const defaultTemplates = {
            "test_approved": {
                subject: "Test Results for Sample {{batchno}}",
                body: `
                    Dear {{customer_name}},

                    The test for sample {{batchno}} ({{sample_type}}) was completed. 

                    Amount due: {{amount_due}}.

                    Please ensure payment is made by the due date: {{due_date}}.

                    Best regards,
                    Your Lab Team
                `
            },
            "sample_received": {
                subject: "Sample {{batchno}} Received",
                body: `
                    Dear {{customer_name}},

                    We have received your sample with ID {{batchno}}.

                    Test Date: {{test_date}}.
                    Sample Type: {{sample_type}}.

                    Our team will begin processing it shortly.

                    Best regards,
                    Your Lab Team
                `
            },"sample_subcontrating":{
               subject: "Request for Subcontracting Test Samples",
                body: `
                    Dear {{contractor_name}},

                    We are reaching out to request your assistance in subcontracting the following test samples:

                    {{sample_list}}

                    We kindly ask you to confirm your availability to perform these tests at your earliest convenience.

                    Best regards,
                    Your Lab Team`
            }
            // Add default templates for other events here as needed
        };

        // Initialize Quill editor
        var quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Compose your email body here...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image']
                ]
            }
        });

        // Function to update the help list based on selected event
        function updateHelpList(event) {
            const helpList = document.getElementById('help-list');
            helpList.innerHTML = ''; // Clear existing list

            if (event && placeholders[event]) {
                placeholders[event].forEach(function(item) {
                    const li = document.createElement('li');
                    li.innerHTML = `<strong>${item.placeholder}</strong> - ${item.description}`;
                    helpList.appendChild(li);
                });
            }
        }

        function showPlaceholderConst(eventName, shouldShow) {
            const wrapper = document.getElementById('placeholder-const-wrapper');
            const pre = document.getElementById('placeholder-const');
            if (!wrapper || !pre) return;

            const list = placeholders[eventName] || [];
            if (!shouldShow || !eventName || list.length === 0) {
                wrapper.style.display = 'none';
                pre.textContent = '';
                return;
            }

            const obj = {};
            obj[eventName] = list;
            pre.textContent = 'const placeholders = ' + JSON.stringify(obj, null, 2) + ';';
            wrapper.style.display = '';
        }

        // Function to set the default template if no body is retrieved
        function setDefaultTemplate(event) {
            const subjectInput = document.getElementById('subject');
            const bodyEditor = quill.root;

            if (defaultTemplates[event]) {
                // Set the default subject and body
                subjectInput.value = defaultTemplates[event].subject;
                bodyEditor.innerHTML = defaultTemplates[event].body;
            }
        }

        // Event listener for event name selection
        document.getElementById('event_name').addEventListener('change', function() {
            updateHelpList(this.value); // Update the help list based on selected event
            setDefaultTemplate(this.value); // Set the default template if no saved template
            showPlaceholderConst(this.value, false);
        });

        // Initial call to update the help list and set default template when the page loads
        const selectedEvent = document.getElementById('event_name').value;
        if (selectedEvent) {
            updateHelpList(selectedEvent);
            setDefaultTemplate(selectedEvent);
            showPlaceholderConst(selectedEvent, false);
        }

       
    </script>
    <script>
    $(document).ready(function() {
        // Function to save the template
        $('#saveTemplateBtn').click(function() {
            // Get the data from the form fields
            var eventName = $('#event_name').val();
            var subject = $('#subject').val();
            var body = quill.root.innerHTML;

            // Check if the form fields are filled
            if (eventName && subject && body) {
                // AJAX call to save the template
                $.ajax({
                    url: 'ajax/save_template.php',
                    type: 'POST',
                    data: {
                        event_name: eventName,
                        subject: subject,
                        body: body
                    },
                    success: function(response) {
                        // Show success or error message
                        toastr.info(response);
                    },
                    error: function() {
                        toastr.info('An error occurred while saving the template.');
                    }
                });
            } else {
                toastr.info('Please fill out all the fields.');
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        // When the user selects an event, fetch the template data
        $('#event_name').change(function() {
            var eventName = $(this).val();

            // Make sure the event name is selected
            if (eventName) {
                $.ajax({
                    url: 'ajax/get_template.php',
                    type: 'POST',
                    data: { event_name: eventName },
                    success: function(response) {
                        var template = JSON.parse(response);

                        if (template.error) {
                            if (String(template.error).toLowerCase().indexOf('not found') === -1) {
                                toastr.info(template.error);
                            }
                            setDefaultTemplate(eventName);
                            showPlaceholderConst(eventName, true);
                        } else {
                            // Pre-fill the subject and body fields with the retrieved template
                            $('#subject').val(template.subject);
                            quill.root.innerHTML = template.body;  // Pre-fill Quill editor
                            showPlaceholderConst(eventName, !!template.is_default);

                            // Optional: Display a message or perform additional actions
                        }
                    },
                    error: function() {
                        toastr.info('An error occurred while fetching the template.');
                    }
                });
            }
        });
    });
</script>

</body>
</html>
