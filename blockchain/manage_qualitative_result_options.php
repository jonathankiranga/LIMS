<div class="container-fluid py-3" id="qualitativeOptionsManager">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Manage Qualitative Result Options</h4>
            <div class="text-muted small">Maintain the values available for qualitative test results.</div>
        </div>
        <span class="badge bg-secondary" id="qroCount">0 options</span>
    </div>

    <div id="qroMessage" class="alert d-none" role="alert"></div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">Add an option</div>
        <div class="card-body">
            <form id="qroAddForm" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="qroNewValue">Stored value</label>
                    <input class="form-control" id="qroNewValue" name="OptionValue" maxlength="100" required>
                    <div class="form-text">Saved with the test result. Must be unique.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="qroNewLabel">Display label</label>
                    <input class="form-control" id="qroNewLabel" name="OptionLabel" maxlength="150" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="qroNewOrder">Display order</label>
                    <input class="form-control" id="qroNewOrder" name="DisplayOrder" type="number" value="0" step="1">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="fas fa-plus"></i> Add option</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Existing options</div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="min-width:130px;">Stored value</th>
                        <th style="min-width:190px;">Display label</th>
                        <th style="width:130px;">Display order</th>
                        <th style="width:100px;">Status</th>
                        <th style="width:190px;">Action</th>
                    </tr>
                </thead>
                <tbody id="qroOptionsBody">
                    <tr><td colspan="5" class="text-center text-muted py-4">Loading options...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted">
            Stored values cannot be edited after creation because saved test results may already use them. Edit the label or deactivate an option instead of deleting it.
        </div>
    </div>
</div>

<script>
(function ($) {
    var $root = $('#qualitativeOptionsManager');
    if (!$root.length || $root.data('qro-initialized')) return;
    $root.data('qro-initialized', true);

    var endpoint = 'ajax/manage_qualitative_result_options.php';

    function showMessage(message, success) {
        $root.find('#qroMessage')
            .removeClass('d-none alert-success alert-danger')
            .addClass(success ? 'alert-success' : 'alert-danger')
            .text(message);
    }

    function renderOptions(options) {
        var $body = $root.find('#qroOptionsBody').empty();
        $root.find('#qroCount').text(options.length + (options.length === 1 ? ' option' : ' options'));

        if (!options.length) {
            $body.append($('<tr>').append($('<td>', {
                colspan: 5,
                class: 'text-center text-muted py-4',
                text: 'No options found.'
            })));
            return;
        }

        options.forEach(function (option) {
            var $row = $('<tr>').attr('data-option-id', option.OptionID);
            var $value = $('<code>').text(option.OptionValue);
            var $label = $('<input>', {
                type: 'text',
                maxlength: 150,
                class: 'form-control form-control-sm qro-label',
                value: option.OptionLabel
            });
            var $order = $('<input>', {
                type: 'number',
                step: 1,
                class: 'form-control form-control-sm qro-order',
                value: option.DisplayOrder
            });
            var active = Number(option.IsActive) === 1;
            var $status = $('<span>', {
                class: active ? 'badge bg-success' : 'badge bg-secondary',
                text: active ? 'Active' : 'Inactive'
            });
            var $save = $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-primary qro-save',
                text: 'Save'
            });
            var $toggle = $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-outline-secondary qro-toggle',
                text: active ? 'Deactivate' : 'Activate'
            });

            $row.append($('<td>').append($value));
            $row.append($('<td>').append($label));
            $row.append($('<td>').append($order));
            $row.append($('<td>').append($status));
            $row.append($('<td class="text-nowrap">').append($save, ' ', $toggle));
            $body.append($row);
        });
    }

    function loadOptions() {
        $.ajax({
            url: endpoint,
            method: 'GET',
            dataType: 'json',
            cache: false
        }).done(function (response) {
            if (response && response.success && Array.isArray(response.options)) {
                renderOptions(response.options);
            } else {
                $root.find('#qroOptionsBody').html(
                    $('<tr>').append($('<td>', {
                        colspan: 5,
                        class: 'text-center text-danger py-4',
                        text: response && response.message ? response.message : 'Could not load options.'
                    }))
                );
                showMessage(response && response.message ? response.message : 'Could not load options.', false);
            }
        }).fail(function (xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Could not load options. Check the database connection and qualitative_result_options table.';
            $root.find('#qroOptionsBody').html(
                $('<tr>').append($('<td>', {
                    colspan: 5,
                    class: 'text-center text-danger py-4',
                    text: message
                }))
            );
            showMessage(message, false);
        });
    }

    function sendAction(data, done) {
        $.ajax({
            url: endpoint,
            method: 'POST',
            dataType: 'json',
            data: data
        }).done(function (response) {
            if (response && response.success) {
                showMessage(response.message || 'Saved.', true);
                loadOptions();
                if (typeof done === 'function') done();
            } else {
                showMessage(response && response.message ? response.message : 'The request failed.', false);
            }
        }).fail(function (xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'The request failed. Check the server response.';
            showMessage(message, false);
        });
    }

    $root.on('submit', '#qroAddForm', function (event) {
        event.preventDefault();
        var data = $(this).serializeArray();
        data.push({ name: 'action', value: 'add' });
        sendAction(data, function () {
            $root.find('#qroAddForm')[0].reset();
            $root.find('#qroNewOrder').val('0');
        });
    });

    $root.on('click', '.qro-save', function () {
        var $row = $(this).closest('tr');
        sendAction({
            action: 'update',
            OptionID: $row.attr('data-option-id'),
            OptionLabel: $row.find('.qro-label').val(),
            DisplayOrder: $row.find('.qro-order').val()
        });
    });

    $root.on('click', '.qro-toggle', function () {
        var $row = $(this).closest('tr');
        sendAction({
            action: 'toggle',
            OptionID: $row.attr('data-option-id')
        });
    });

    loadOptions();
})(jQuery);
</script>
