<link href="css/labresultsstyling.css" rel="stylesheet" type="text/css"/>

<div class="grid-container">
    <div class="filter-bar">
        <input type="text" id="filterValue" placeholder="Enter search term...">
        <select id="filterColumn">
            <option value="">Select Filter...</option>
            <option value="SampleID">Sample ID</option>
            <option value="ParameterName">Parameter</option>
        </select>
        <button onclick="loadGrid()">Search</button>
        
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
                <span id="saveSpinner" style="display:none; margin-left:8px;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </th>
         </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <div class="pagination" id="pagination"></div>
</div>


<script>
var currentPage = 1;

function bindFilter() {
    $('#filterColumn').on('change', function() {
        if ($(this).val() === 'ParameterName') {
            $('#globalResultTypeWrapper').show();
        } else {
            $('#globalResultTypeWrapper').hide();
        }
    });

    $('#globalResultType').on('change', function() {
        const value = $(this).val();
        if (!value) return;
        $('#resultsGrid tbody select.result-type').val(value).trigger('change');
    });
}

function loadGrid(page = 1) {
    currentPage = page;
    const filterColumn = $('#filterColumn').val() || '';
    const filterValue  = $('#filterValue').val() || '';
    const category     = 'chemical';

    $.post('ajax/getGridData.php', {
        page: page,
        filterColumn: filterColumn,
        filterValue: filterValue,
        category: category
    }, function(payload) {
        const $tbody = $('#resultsGrid tbody').empty();

        if (!payload.data || payload.data.length === 0) {
            $tbody.html('<tr><td colspan="5">No pending results</td></tr>');
            renderPagination(0, 1);
            return;
        }

        payload.data.forEach(row => $tbody.append(renderRow(row)));
        renderPagination(payload.pagination?.total || 1, payload.pagination?.current || 1);
    }, 'json').fail(err => console.error('loadGrid error:', err));
}

function renderRow(row) {
    const $tr = $('<tr>').attr({
        'data-resultsid': row.resultsID,
        'data-sampleid': row.SampleID,
        'data-parametername': row.ParameterName,
        'data-resulttype': row.ResultType || ''
    });

    const $tdSample = $('<td>').text(row.SampleID);
    const $tdParam  = $('<td>').text(row.ParameterName);
    const $tdType   = $('<td>');
    const $tdInput  = $('<td>');
    const $tdAction = $('<td>');

    const $sel = $('<select>', { class: 'result-type' }).html(`
        <option value="">-- Select --</option>
        <option value="quantitativeField">Quantitative</option>
        <option value="qualitativeField">Qualitative</option>
        <option value="rangeField">Range</option>
    `).on('change', function() {
        showRowEditor($tr, $(this).val());
    });

    const $btnSave = $('<button>', {
        class: 'btn-save-row',
        title: 'Save entry'
    }).html('<i class="fas fa-save"></i> Save').on('click', function() {
        triggerRowSave($tr);
    });

    $tdType.append($sel);
    $tdAction.append($btnSave);
    $tr.append($tdSample, $tdParam, $tdType, $tdInput, $tdAction);

    if (row.ResultType) {
        $sel.val(row.ResultType);
        showRowEditor($tr, row.ResultType);
    }

    return $tr;
}

function showRowEditor($tr, type) {
    const $tdInput = $tr.children().eq(3).empty();
    let $input;

    if (type === 'quantitativeField') {
        $input = $('<input>', { type: 'number', class: 'result-value', placeholder: 'Enter value' });
    } else if (type === 'qualitativeField') {
        $input = $('<select>', { class: 'result-value' }).html(`
            <option value="">-- Select --</option>
            <option value="ND">Not Detected</option>
            <option value="Absent">Absent</option>
            <option value="Detected">Detected</option>
            <option value="Below Limit">Below Limit</option>
            <option value="Trace">Trace</option>
			<option value="Above Limit">Above Limit</option><option value="Inconclusive">Inconclusive</option>
			<option value="Error">Error</option><option value="Invalid">Invalid</option>
        `);
    } else if (type === 'rangeField') {
        $input = $('<input>', { type: 'text', class: 'result-value', placeholder: 'e.g. <2' });
    }

    $tdInput.append($input);
}

function triggerRowSave($tr) {
    const resultsid = $tr.attr('data-resultsid');
    const sampleID = $tr.attr('data-sampleid');
    const parameterName = $tr.attr('data-parametername');
    const resultType = $tr.find('.result-type').val();
    const resultValue = $tr.find('.result-value').val();

    // Validation
    if (!resultType || resultValue === "" || resultValue === null) {
        alert("Please select a Result Type and provide a Result Value before saving.");
        return;
    }

    saveResult({
        resultsid,
        sampleID,
        parameterName,
        resultType,
        resultValue
    });
}

function saveResult(row) {
    $('#saveSpinner').show();
    const payload = { ...row };

    // Capture context from localStorage
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        payload[key] = localStorage.getItem(key);
    }

    $.ajax({
        url: 'ajax/submit_test_results.php',
        type: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(payload),
        success: function(res) {
            $('#saveSpinner').hide();
            if (res.success) {
                const $row = $(`tr[data-resultsid="${row.resultsid}"]`);
                $row.addClass('saved').fadeOut(1000, function() {
                    $(this).remove(); // Remove from pending view after success
                });
            } else {
                alert("Save failed: " + res.message);
            }
        },
        error: function(xhr) {
            $('#saveSpinner').hide();
            console.error(xhr.responseText);
            alert("An error occurred during save.");
        }
    });
}

function renderPagination(totalPages, page) {
    const $container = $('#pagination').empty();
    if (totalPages <= 1) return;

    const makeBtn = (label, target, disabled = false, active = false) => {
        return $('<button>', { text: label, disabled }).css('font-weight', active ? 'bold' : '')
            .on('click', () => loadGrid(target));
    };

    $container.append(makeBtn('« Prev', Math.max(1, page - 1), page === 1));
    for (let i = 1; i <= totalPages; i++) {
        if(i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
            $container.append(makeBtn(i, i, false, i === page));
        }
    }
    $container.append(makeBtn('Next »', Math.min(totalPages, page + 1), page === totalPages));
}

$(function() {
    loadGrid(1);
    bindFilter();
});
</script>