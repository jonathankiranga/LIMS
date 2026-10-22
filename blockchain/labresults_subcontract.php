<link href="css/labresultsstyling.css" rel="stylesheet" type="text/css"/>

<div class="grid-container">
    <div class="card mb-3">
        <div class="card-header"><strong>Subcontractor Samples</strong></div>
        <div class="card-body" id="sampleList">
            <span>Loading assigned samples...</span>
        </div>
    </div>

    <div class="filter-bar">
        <strong id="selectedSampleLabel">Select an assigned sample to view its contracted parameters.</strong>
        <div class="mt-2">
            <input type="text" id="filterValue" placeholder="Enter search term...">
            <select id="filterColumn">
                <option value="">Select Filter...</option>
                <option value="SampleID">Sample ID</option>
                <option value="ParameterName">Parameter</option>
            </select>
            <button type="button" id="searchResults">Search</button>
        </div>

        <div id="globalResultTypeWrapper" style="display:none; margin:10px 0;">
            <label for="globalResultType">Global Result Type: </label>
            <select id="globalResultType">
                <option value="" disabled selected>Select a result type</option>
                <option value="quantitativeField">Quantitative Result</option>
                <option value="qualitativeField">Qualitative Result</option>
                <option value="rangeField">Range Result</option>
            </select>
        </div>
    </div>

    <div class="table-wrapper">
        <table id="resultsGrid" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Sample ID</th>
                    <th>Parameter</th>
                    <th>Result Type</th>
                    <th>Result Input</th>
                    <th>
                        Action
                        <span id="saveSpinner" style="display:none; margin-left:10px; color:#007bff;">
                            <i class="fas fa-spinner fa-spin"></i> Saving...
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5">Select a sample above to load its contracted parameters.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>

<script>
var currentPage = 1;
var currentSampleID = '';
var currentContractorID = '';
var qualitativeResultOptions = [];
var qualitativeOptionsLoaded = false;

function loadQualitativeOptions(callback) {
    $.ajax({
        url: 'ajax/get_qualitative_result_options.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response && response.success && Array.isArray(response.options)) {
                qualitativeResultOptions = response.options;
                qualitativeOptionsLoaded = true;
            } else {
                qualitativeResultOptions = [];
                qualitativeOptionsLoaded = false;
                console.error('Could not load qualitative result options:', response && response.message);
            }
        },
        error: function(xhr) {
            qualitativeResultOptions = [];
            qualitativeOptionsLoaded = false;
            console.error('Could not load qualitative result options:', xhr.responseText);
        },
        complete: function() {
            if (typeof callback === 'function') callback();
        }
    });
}

function fetchSampleIDs() {
    $.ajax({
        url: 'ajax/getassinedsubcontractedlabtestAjax.php',
        method: 'GET',
        dataType: 'json',
        data: {
            category: 'admin',
            department: 'admin'
        },
        success: function(response) {
            var $sampleList = $('#sampleList').empty();
            var samples = response && Array.isArray(response.samples) ? response.samples : [];

            if (!samples.length) {
                $sampleList.text('No outstanding contracted samples are currently assigned.');
                return;
            }

            var groupedSamples = {};
            samples.forEach(function(sample) {
                var contractorID = String(sample.subcontractor);
                if (!groupedSamples[contractorID]) {
                    groupedSamples[contractorID] = {
                        name: sample.name || ('Subcontractor ' + contractorID),
                        samples: []
                    };
                }
                groupedSamples[contractorID].samples.push(sample);
            });

            Object.keys(groupedSamples).forEach(function(contractorID) {
                var group = groupedSamples[contractorID];
                var $section = $('<div class="mb-3">');
                $('<h5>').text(group.name).appendTo($section);

                var seenSampleIDs = {};
                group.samples.forEach(function(sample) {
                    var sampleID = String(sample.SampleID);
                    if (seenSampleIDs[sampleID]) return;
                    seenSampleIDs[sampleID] = true;

                    $('<button>', {
                        type: 'button',
                        class: 'btn btn-outline-primary mb-2 me-2 sample-btn',
                        text: sampleID
                    }).attr({
                        'data-sampleid': sampleID,
                        'data-contractor': contractorID
                    }).appendTo($section);
                });

                $sampleList.append($section);
            });
        },
        error: function(xhr) {
            $('#sampleList').text('Unable to load assigned samples.');
            console.error('Error loading subcontracted samples:', xhr.responseText);
        }
    });
}

function selectSample(sampleID, contractorID) {
    currentSampleID = String(sampleID);
    currentContractorID = String(contractorID);
    currentPage = 1;

    $('#selectedSampleLabel').text(
        'Contracted parameters for Sample ID ' + currentSampleID
    );
    $('.sample-btn').removeClass('active');
    $('.sample-btn').filter(function() {
        return String($(this).data('sampleid')) === currentSampleID &&
               String($(this).data('contractor')) === currentContractorID;
    }).addClass('active');

    loadGrid(1);
}

function bindFilter() {
    $('#searchResults').on('click', function() {
        loadGrid(1);
    });

    $('#filterValue').on('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadGrid(1);
        }
    });

    $('#filterColumn').on('change', function() {
        if ($(this).val() === 'ParameterName') {
            $('#globalResultTypeWrapper').show();
        } else {
            $('#globalResultTypeWrapper').hide();
        }
    });

    $('#globalResultType').on('change', function() {
        var value = $(this).val();
        if (!value) return;
        $('#resultsGrid tbody select.result-type').val(value).trigger('change');
    });
}

function loadGrid(page) {
    page = page || 1;
    currentPage = page;

    if (!currentSampleID || !currentContractorID) {
        $('#resultsGrid tbody').html(
            '<tr><td colspan="5">Select a sample above to load its contracted parameters.</td></tr>'
        );
        $('#pagination').empty();
        return;
    }

    $.ajax({
        url: 'ajax/getGridData.php',
        type: 'POST',
        dataType: 'json',
        data: {
            page: page,
            filterColumn: $('#filterColumn').val() || '',
            filterValue: $('#filterValue').val() || '',
            category: 'subcontracted',
            sampleID: currentSampleID,
            userid: currentContractorID
        },
        success: function(payload) {
            var $tbody = $('#resultsGrid tbody').empty();

            if (!payload || !Array.isArray(payload.data) || payload.data.length === 0) {
                $tbody.html('<tr><td colspan="5">No pending contracted parameters for this sample.</td></tr>');
                renderPagination(0, 1);
                return;
            }

            payload.data.forEach(function(row) {
                $tbody.append(renderRow(row));
            });
            renderPagination(
                payload.pagination ? payload.pagination.total : 1,
                payload.pagination ? payload.pagination.current : 1
            );
        },
        error: function(xhr) {
            $('#resultsGrid tbody').html(
                '<tr><td colspan="5">Unable to load contracted parameters.</td></tr>'
            );
            $('#pagination').empty();
            console.error('Subcontract results grid error:', xhr.responseText);
        }
    });
}

function renderRow(row) {
    var $tr = $('<tr>').attr({
        'data-resultsid': row.resultsID,
        'data-sampleid': row.SampleID,
        'data-parametername': row.ParameterName,
        'data-resulttype': row.ResultType || ''
    });

    var $tdSample = $('<td>').text(row.SampleID);
    var $tdParam = $('<td>').text(row.ParameterName);
    var $tdType = $('<td>');
    var $tdInput = $('<td>');
    var $tdAction = $('<td>');

    var $select = $('<select>', {
        class: 'result-type form-control'
    }).html(
        '<option value="">-- Select --</option>' +
        '<option value="quantitativeField">Quantitative</option>' +
        '<option value="qualitativeField">Qualitative</option>' +
        '<option value="rangeField">Range</option>'
    ).on('change', function() {
        showRowEditor($tr, $(this).val());
    });

    var $saveButton = $('<button>', {
        type: 'button',
        class: 'btn-save-row',
        title: 'Save this result'
    }).html('<i class="fas fa-save"></i> Save').on('click', function() {
        prepareSave($tr);
    });

    $tdType.append($select);
    $tdAction.append($saveButton);
    $tr.append($tdSample, $tdParam, $tdType, $tdInput, $tdAction);

    if (row.ResultType) {
        $select.val(row.ResultType);
        showRowEditor($tr, row.ResultType);
    }

    return $tr;
}

function showRowEditor($tr, type) {
    var $inputCell = $tr.children().eq(3).empty();
    var $input;

    if (type === 'quantitativeField') {
        $input = $('<input>', {
            type: 'number',
            step: 'any',
            class: 'result-value form-control',
            placeholder: 'Enter value'
        });
    } else if (type === 'qualitativeField') {
        $input = $('<select>', { class: 'result-value form-control' });
        $input.append($('<option>', { value: '' }).text(
            qualitativeOptionsLoaded ? '-- Select --' : 'Qualitative options unavailable'
        ));

        if (qualitativeOptionsLoaded) {
            qualitativeResultOptions.forEach(function(option) {
                $input.append($('<option>').val(option.value).text(option.label));
            });
        }
    } else if (type === 'rangeField') {
        $input = $('<input>', {
            type: 'text',
            class: 'result-value form-control',
            placeholder: 'e.g. <2 or 1-5'
        });
    }

    if ($input) $inputCell.append($input);
}

function prepareSave($tr) {
    var resultsid = $tr.attr('data-resultsid');
    var sampleID = $tr.attr('data-sampleid');
    var parameterName = $tr.attr('data-parametername');
    var resultType = $tr.find('.result-type').val();
    var resultValue = $tr.find('.result-value').val();

    if (!resultType || resultValue === '' || resultValue === null || typeof resultValue === 'undefined') {
        alert('Please select a result type and enter a result value.');
        return;
    }

    saveResult({
        resultsid: resultsid,
        sampleID: sampleID,
        parameterName: parameterName,
        resultType: resultType,
        resultValue: resultValue
    });
}

function saveResult(row) {
    $('#saveSpinner').show();
    var payload = Object.assign({}, row);

    // Preserve the user/session context used by the existing result handler.
    for (var i = 0; i < localStorage.length; i++) {
        var key = localStorage.key(i);
        payload[key] = localStorage.getItem(key);
    }

    $.ajax({
        url: 'ajax/submit_test_results.php',
        type: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(payload),
        success: function(response) {
            $('#saveSpinner').hide();
            if (response && response.success) {
                loadGrid(1);
            } else {
                alert('Save failed: ' + (response && response.message ? response.message : 'Unknown error'));
            }
        },
        error: function(xhr) {
            $('#saveSpinner').hide();
            console.error('Result save failed:', xhr.responseText);
            alert('A server error occurred while saving the result.');
        }
    });
}

function renderPagination(totalPages, page) {
    var $container = $('#pagination').empty();
    if (!totalPages || totalPages <= 1) return;

    function makeButton(label, target, disabled, active) {
        return $('<button>', {
            type: 'button',
            text: label,
            disabled: !!disabled,
            class: active ? 'active' : ''
        }).on('click', function() {
            loadGrid(target);
        });
    }

    $container.append(makeButton('« Prev', Math.max(1, page - 1), page === 1, false));
    for (var i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
            $container.append(makeButton(String(i), i, false, i === page));
        }
    }
    $container.append(makeButton('Next »', Math.min(totalPages, page + 1), page === totalPages, false));
}

$(function() {
    bindFilter();
    $(document).on('click', '.sample-btn', function() {
        selectSample($(this).data('sampleid'), $(this).data('contractor'));
    });
    loadQualitativeOptions(function() {
        fetchSampleIDs();
    });
});
</script>
