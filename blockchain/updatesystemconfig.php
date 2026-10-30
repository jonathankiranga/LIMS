<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Configuration</title>
    <link href="js/quilljs/1.3.7/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
     <script src="js/quilljs/1.3.7/quill.min.js"></script>
    <style>
        .config-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(145px,1fr)); gap:10px; margin:12px 0 20px; }
        .config-tile { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; min-height:94px; padding:12px 8px; border:1px solid #d5dbe3; border-radius:8px; background:#fff; color:#344054; cursor:pointer; transition:border-color .15s,background .15s,box-shadow .15s; }
        .config-tile .config-icon { font-size:22px; color:#667085; }
        .config-tile .config-label { font-size:12px; line-height:1.3; text-align:center; overflow-wrap:anywhere; }
        .config-tile:hover { border-color:#64748b; background:#f8fafc; }
        .config-tile.active { border-color:#2563eb; background:#eff6ff; box-shadow:0 0 0 1px #2563eb inset; }
        .config-tile.active .config-icon { color:#2563eb; }
        #editor { min-height:130px; height:auto; display:none; }
        #editor .ql-editor { min-height:110px; }
        #current-file { display:none; margin-bottom:10px; }
        #config-value { width:100%; max-width:640px; padding:7px; }
        #result-message { margin-top:12px; }
    </style>
</head>
<body>
    <h1>Update Configuration</h1>
    <form id="update-config-form">
        <label class="form-label">Choose a configuration</label>
        <div id="config-icons" class="config-grid" role="group" aria-label="Configuration options"></div>
        <input type="hidden" id="config-select" name="configName" required>
        <div id="selected-config-heading" style="font-weight:600;margin:8px 0 10px;">Select a configuration above to edit it.</div>
        <label for="config-value" id="config-value-label">Value:</label>
        <div id="input-container">
            <input type="text" id="config-value" name="configValue" required>
        </div>
        <div id="current-file"></div>
        <div id="editor" class="quill-editor"></div>

        <br><br>
        <input type="submit" value="Update Configuration">
    </form>
    <div id="result-message"></div>

   
    <script>
        var quill = null;
        var currentConfigType = '';

        function iconForConfig(name) {
            var key = String(name || '').toLowerCase();
            if (key.indexOf('conform') !== -1) return 'fa-file-signature';
            if (key.indexOf('logo') !== -1 || key.indexOf('image') !== -1 || key.indexOf('picture') !== -1) return 'fa-image';
            if (key.indexOf('path') !== -1 || key.indexOf('file') !== -1 || key.indexOf('signature') !== -1) return 'fa-folder-open';
            if (key.indexOf('email') !== -1 || key.indexOf('smtp') !== -1 || key.indexOf('mail') !== -1) return 'fa-envelope';
            if (key.indexOf('phone') !== -1 || key.indexOf('telephone') !== -1 || key.indexOf('mobile') !== -1) return 'fa-phone';
            if (key.indexOf('date') !== -1 || key.indexOf('time') !== -1) return 'fa-calendar-days';
            if (key.indexOf('font') !== -1 || key.indexOf('format') !== -1) return 'fa-font';
            if (key.indexOf('version') !== -1 || key.indexOf('revision') !== -1) return 'fa-code-branch';
            if (key.indexOf('report') !== -1 || key.indexOf('coa') !== -1 || key.indexOf('certificate') !== -1) return 'fa-file-pdf';
            if (key.indexOf('url') !== -1 || key.indexOf('website') !== -1 || key.indexOf('api') !== -1) return 'fa-link';
            if (key.indexOf('number') !== -1 || key.indexOf('limit') !== -1 || key.indexOf('count') !== -1) return 'fa-hashtag';
            if (key.indexOf('address') !== -1 || key.indexOf('company') !== -1 || key.indexOf('laboratory') !== -1) return 'fa-building';
            if (key.indexOf('tax') !== -1 || key.indexOf('currency') !== -1 || key.indexOf('price') !== -1) return 'fa-coins';
            return 'fa-sliders';
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function valueAsEditorHtml(value) {
            var text = String(value == null ? '' : value);
            if (/<\/?[a-z][^>]*>/i.test(text)) return text;
            return escapeHtml(text).replace(/\r\n|\r|\n/g, '<br>');
        }

        function initializeConfigQuill() {
            if (quill) return;
            quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'script': 'sub' }, { 'script': 'super' }],
                        ['link'],
                        ['clean']
                    ]
                }
            });
        }

        function selectConfiguration(configName, tile) {
            $('#config-select').val(configName);
            $('.config-tile').removeClass('active').attr('aria-pressed', 'false');
            $(tile).addClass('active').attr('aria-pressed', 'true');
            $('#selected-config-heading').text(configName);
            $('#result-message').empty();

            $.ajax({
                url: 'ajax/get_config_type.php',
                type: 'GET',
                data: { configName: configName },
                dataType: 'json',
                success: function(configData) {
                    var inputContainer = $('#input-container');
                    inputContainer.empty();
                    $('#editor').hide();
                    $('#current-file').hide().empty();
                    currentConfigType = String(configData.type || 'string').toLowerCase();

                    // Conformity is always edited as rich text, even if its legacy DB type is string.
                    if (String(configName).toLowerCase() === 'conformity' || currentConfigType === 'text') {
                        initializeConfigQuill();
                        $('#config-value-label').hide();
                        $('#editor').show();
                        quill.root.innerHTML = valueAsEditorHtml(configData.confvalue);
                        return;
                    }

                    $('#config-value-label').show();
                    var input;
                    if (currentConfigType === 'number') {
                        input = $('<input>', { type:'number', class:'form-control-sm', id:'config-value', name:'configValue', required:true }).val(configData.confvalue);
                    } else if (currentConfigType === 'date') {
                        input = $('<input>', { type:'date', class:'form-control-sm', id:'config-value', name:'configValue', required:true }).val(configData.confvalue);
                    } else if (currentConfigType === 'path') {
                        $('#current-file').show().text('Current file: ' + (configData.confvalue || 'Not set'));
                        input = $('<input>', { type:'file', class:'form-control-sm', id:'config-value', name:'configValue', accept:'*/*' });
                        input.on('change', function() {
                            var fileName = String($(this).val() || '').split('\\').pop();
                            $('#result-message').text(fileName ? 'Selected file: ' + fileName : '');
                        });
                    } else {
                        input = $('<input>', { type:'text', class:'form-control-sm', id:'config-value', name:'configValue', required:true }).val(configData.confvalue);
                    }
                    inputContainer.append(input);
                },
                error: function() {
                    $('#result-message').text('Error loading configuration type.');
                }
            });
        }

        $(document).ready(function() {
            // Load configuration options
            $.ajax({
                url: 'ajax/get_configurations.php',
                type: 'GET',
                dataType: 'json',
                success: function(configs) {
                    var grid = $('#config-icons');
                    configs.forEach(function(config) {
                        var name = String(config.confname || '');
                        var tile = $('<button type="button" class="config-tile" aria-pressed="false"></button>');
                        $('<i class="fa-solid ' + iconForConfig(name) + ' config-icon" aria-hidden="true"></i>').appendTo(tile);
                        $('<span class="config-label"></span>').text(name).appendTo(tile);
                        tile.on('click', function() { selectConfiguration(name, this); });
                        grid.append(tile);
                    });
                    if (!configs.length) $('#result-message').text('No configurations were found.');
                },
                error: function() {
                    $('#result-message').text('Error loading configurations.');
                }
            });

            // Update configuration on form submission
            $('#update-config-form').on('submit', function(event) {
                event.preventDefault(); // Prevent default form submission

                var configName = $('#config-select').val();
                if (!configName) {
                    $('#result-message').text('Select a configuration first.');
                    return;
                }

                var configValue;
                if ($('#editor').is(':visible')) {
                    configValue = quill ? quill.root.innerHTML : '';
                } else {
                    var valueInput = $('#config-value');
                    if (valueInput.attr('type') === 'file') {
                        configValue = valueInput[0].files[0] || '';
                    } else {
                        configValue = valueInput.val();
                    }
                }

                if (configValue instanceof File) {
                    var formData = new FormData();
                    formData.append('configName', configName);
                    formData.append('configValue', configValue);
                    $.ajax({
                        url: 'ajax/update_config.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) { $('#result-message').text(response); },
                        error: function() { $('#result-message').text('There was an error updating the configuration.'); }
                    });
                    return;
                }

                $.ajax({
                    url: 'ajax/update_config.php', // PHP script to handle update
                    type: 'POST',
                    data: {
                        configName: configName,
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