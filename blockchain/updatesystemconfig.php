<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Configuration</title>
    <link href="js/quilljs/1.3.7/quill.snow.css" rel="stylesheet">
     <script src="js/quilljs/1.3.7/quill.min.js"></script>
    <style>
        #editor { height: 100px; }
    </style>
</head>
<body>
    <h1>Update Configuration</h1>
    <form id="update-config-form">
        <label for="config-select">Select Configuration:</label>
        <select id="config-select" name="configName" required>
            <!-- Options will be populated by PHP -->
        </select>
        <br><br>
        <label for="config-value">Value:</label>
        <div id="input-container">
            <input type="text" id="config-value" name="configValue" required>
        </div>
        <div id="current-file" style="margin-bottom: 10px;"></div>
         <div id="editor" class="quill-editor form-control-sm"></div>

        <br><br>
        <input type="submit" value="Update Configuration">
    </form>
    <div id="result-message"></div>

   
    <script>
        
        
        $(document).ready(function() {
            // Load configuration options
            $.ajax({
                url: 'ajax/get_configurations.php', // PHP script to fetch configurations
                type: 'GET',
                dataType: 'json',
                success: function(configs) {
                    configs.forEach(function(config) {
                        $('#config-select').append(new Option(config.confname, config.confname, false, false));
                    });
                    $('#config-select').change();
                },
                error: function() {
                    $('#result-message').html('<div class="error">Error loading configurations.</div>');
                }
            });

            // Change input type based on selected configuration
            $('#config-select').on('change', function() {
                const selectedConfig = $(this).val();
                $.ajax({
                    url: 'ajax/get_config_type.php', // New PHP script to get the type of the selected config
                    type: 'GET',
                    data: { configName: selectedConfig },
                    success: function(data) {
                        const configData = JSON.parse(data);
                        const inputContainer = $('#input-container');

                        // Clear previous input types
                        inputContainer.empty();
                        $('#editor').hide(); // Hide the editor by default
                        $('#current-file').hide(); // Hide the editor by default
                        // Create input based on the type
                        let input;
                        switch (configData.type) {
                            case 'number':
                                input = $('<input type="number"   class="form-control-sm"  value="' + configData.confvalue + '" id="config-value" name="configValue" required>');
                                break;
                            case 'date':
                                input = $('<input type="date"    class="form-control-sm" value="' + configData.confvalue + '" id="config-value" name="configValue" required>');
                                break;
                            case 'path':
                                 $('#current-file').show();
                                 $('#current-file').text('Current file: ' + configData.confvalue);
                                input = $('<input type="file"   class="form-control-sm" id="config-value" name="configValue" accept="*/*" required>');
                                // Optional: Add a change event to show selected file name
                                input.on('change', function() {
                                    const fileName = $(this).val().split('\\').pop(); // Get the file name
                                    $('#result-message').html('<div class="info">Selected file: ' + fileName + '</div>');
                                });
                                break;
                            case 'text':
                                if (!quill) {
                                    $('#editor').show(); // Show Quill editor for blob type
                                    quill = new Quill('#editor', {
                                        theme: 'snow',
                                        modules: {
                                            toolbar: [['bold', 'italic', 'underline'], ['link', 'image', 'code-block']]
                                        }
                                    });
                                }
                                // Show Quill editor for blob type
                                $('#editor').show();
                                
                                quill.root.innerHTML = configData.confvalue; // Set the initial content
                                return; // Skip adding input as Quill will handle it
                            case 'string':
                            default:
                                input = $('<input type="text"   class="form-control-sm" value="' + configData.confvalue + '" id="config-value" name="configValue" required>');
                                break;
                        }

                        inputContainer.append(input);
                    },
                    error: function() {
                        $('#result-message').html('<div class="error">Error loading configuration type.</div>');
                    }
                });
            });

            // Update configuration on form submission
            $('#update-config-form').on('submit', function(event) {
                event.preventDefault(); // Prevent default form submission

                let configValue;
                if ($('#editor').is(':visible')) {
                    // Get HTML from Quill editor
                    configValue = quill.root.innerHTML;
                } else {
                    configValue = $('#config-value').val();
                }

                $.ajax({
                    url: 'ajax/update_config.php', // PHP script to handle update
                    type: 'POST',
                    data: {
                        configName: $('#config-select').val(),
                        configValue: configValue
                    },
                    success: function(response) {
                        $('#result-message').html('<div class="success">' + response + '</div>');
                    },
                    error: function() {
                        $('#result-message').html('<div class="error">There was an error updating the configuration.</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>