<?php
require_once __DIR__ . '/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    $response = array('success' => false, 'message' => 'Invalid request.');

    if ($action === 'add') {
        $value = isset($_POST['OptionValue']) ? trim($_POST['OptionValue']) : '';
        $label = isset($_POST['OptionLabel']) ? trim($_POST['OptionLabel']) : '';
        $order = isset($_POST['DisplayOrder']) && $_POST['DisplayOrder'] !== '' ? (int) $_POST['DisplayOrder'] : 0;

        if ($value === '' || $label === '') {
            $response['message'] = 'Option value and display label are required.';
        } elseif (strlen($value) > 100 || strlen($label) > 150) {
            $response['message'] = 'Option value must be 100 characters or fewer and label 150 characters or fewer.';
        } else {
            $stmt = $conn->prepare('INSERT INTO qualitative_result_options (OptionValue, OptionLabel, DisplayOrder, IsActive) VALUES (?, ?, ?, 1)');
            if ($stmt) {
                $stmt->bind_param('ssi', $value, $label, $order);
                if ($stmt->execute()) {
                    $response = array('success' => true, 'message' => 'Qualitative result option added.');
                } elseif ($stmt->errno === 1062) {
                    $response['message'] = 'That option value already exists. Use a unique stored value.';
                } else {
                    $response['message'] = 'Could not add the option. Please check the database table and try again.';
                }
                $stmt->close();
            } else {
                $response['message'] = 'Could not prepare the add operation.';
            }
        }
    } elseif ($action === 'update') {
        $id = isset($_POST['OptionID']) ? (int) $_POST['OptionID'] : 0;
        $label = isset($_POST['OptionLabel']) ? trim($_POST['OptionLabel']) : '';
        $order = isset($_POST['DisplayOrder']) && $_POST['DisplayOrder'] !== '' ? (int) $_POST['DisplayOrder'] : 0;

        if ($id <= 0 || $label === '') {
            $response['message'] = 'A valid option and display label are required.';
        } elseif (strlen($label) > 150) {
            $response['message'] = 'Display label must be 150 characters or fewer.';
        } else {
            $stmt = $conn->prepare('UPDATE qualitative_result_options SET OptionLabel = ?, DisplayOrder = ?, UpdatedAt = NOW() WHERE OptionID = ?');
            if ($stmt) {
                $stmt->bind_param('sii', $label, $order, $id);
                if ($stmt->execute()) {
                    $response = array('success' => true, 'message' => 'Option updated.');
                } else {
                    $response['message'] = 'Could not update the option.';
                }
                $stmt->close();
            } else {
                $response['message'] = 'Could not prepare the update operation.';
            }
        }
    } elseif ($action === 'toggle') {
        $id = isset($_POST['OptionID']) ? (int) $_POST['OptionID'] : 0;
        if ($id <= 0) {
            $response['message'] = 'Invalid option.';
        } else {
            $stmt = $conn->prepare('UPDATE qualitative_result_options SET IsActive = IF(IsActive = 1, 0, 1), UpdatedAt = NOW() WHERE OptionID = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $response = array('success' => true, 'message' => 'Option status changed.');
                } elseif ($stmt->affected_rows === 0) {
                    $response['message'] = 'Option not found or status was unchanged.';
                } else {
                    $response['message'] = 'Could not change option status.';
                }
                $stmt->close();
            } else {
                $response['message'] = 'Could not prepare the status update.';
            }
        }
    }

    echo json_encode($response);
    $conn->close();
    exit;
}

$options = array();
$tableError = '';
$result = $conn->query('SELECT OptionID, OptionValue, OptionLabel, DisplayOrder, IsActive FROM qualitative_result_options ORDER BY DisplayOrder ASC, OptionLabel ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }
    $result->free();
} else {
    $tableError = 'The qualitative_result_options table is not available. Apply blockchain/sql/create_qualitative_result_options.sql first.';
}

function qro_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<div class="container-fluid py-3" id="qualitativeOptionsManager">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Manage Qualitative Result Options</h4>
            <div class="text-muted small">Maintain the values available for qualitative test results.</div>
        </div>
        <span class="badge bg-secondary"><?php echo count($options); ?> options</span>
    </div>

    <div id="qroMessage" class="alert d-none" role="alert"></div>

    <?php if ($tableError !== ''): ?>
        <div class="alert alert-warning"><?php echo qro_escape($tableError); ?></div>
    <?php else: ?>
    <div class="card mb-3">
        <div class="card-header fw-semibold">Add an option</div>
        <div class="card-body">
            <form id="qroAddForm" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="qroNewValue">Stored value</label>
                    <input class="form-control" id="qroNewValue" name="OptionValue" maxlength="100" required>
                    <div class="form-text">Saved in test results. Keep values unique.</div>
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
                <?php if (count($options) === 0): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No options found.</td></tr>
                <?php else: foreach ($options as $option): ?>
                    <tr data-option-id="<?php echo (int) $option['OptionID']; ?>">
                        <td><code><?php echo qro_escape($option['OptionValue']); ?></code></td>
                        <td><input class="form-control form-control-sm qro-label" maxlength="150" value="<?php echo qro_escape($option['OptionLabel']); ?>"></td>
                        <td><input class="form-control form-control-sm qro-order" type="number" step="1" value="<?php echo (int) $option['DisplayOrder']; ?>"></td>
                        <td>
                            <?php if ((int) $option['IsActive'] === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-primary qro-save">Save</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary qro-toggle"><?php echo (int) $option['IsActive'] === 1 ? 'Deactivate' : 'Activate'; ?></button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted">
            Stored values are intentionally fixed after creation because existing test results may already use them. Update the display label or deactivate an option instead of deleting it.
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function ($) {
    var $root = $('#qualitativeOptionsManager');
    if (!$root.length || $root.data('qro-initialized')) return;
    $root.data('qro-initialized', true);

    function showMessage(message, success) {
        var $message = $root.find('#qroMessage');
        $message.removeClass('d-none alert-success alert-danger')
            .addClass(success ? 'alert-success' : 'alert-danger')
            .text(message);
    }

    function sendAction(data, done) {
        $.ajax({
            url: 'manage_qualitative_result_options.php',
            method: 'POST',
            dataType: 'json',
            data: data
        }).done(function (response) {
            if (response && response.success) {
                showMessage(response.message || 'Saved.', true);
                if (typeof done === 'function') done();
            } else {
                showMessage(response && response.message ? response.message : 'The request failed.', false);
            }
        }).fail(function (xhr) {
            var message = 'The request failed. Check the database table and server response.';
            if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
            showMessage(message, false);
        });
    }

    $root.on('submit', '#qroAddForm', function (event) {
        event.preventDefault();
        var data = $(this).serializeArray();
        data.push({ name: 'action', value: 'add' });
        sendAction(data, function () {
            window.setTimeout(function () { window.location.reload(); }, 500);
        });
    });

    $root.on('click', '.qro-save', function () {
        var $row = $(this).closest('tr');
        sendAction({
            action: 'update',
            OptionID: $row.attr('data-option-id'),
            OptionLabel: $row.find('.qro-label').val(),
            DisplayOrder: $row.find('.qro-order').val()
        }, function () {
            window.setTimeout(function () { window.location.reload(); }, 500);
        });
    });

    $root.on('click', '.qro-toggle', function () {
        var $button = $(this);
        var $row = $button.closest('tr');
        sendAction({
            action: 'toggle',
            OptionID: $row.attr('data-option-id')
        }, function () {
            window.setTimeout(function () { window.location.reload(); }, 500);
        });
    });
})(jQuery);
</script>
