// Close any open autocomplete when clicking outside its input/list (and on Tab/window switch)
if (!window.__autocompleteDismissBound) {
  window.__autocompleteDismissBound = true;
  document.addEventListener('pointerdown', function (e) {
    const open = document.querySelector('.dropdown');
    if (!open) return;
    if (open.contains(e.target)) return;
    if (e.target === open._owner) return;
    open.remove();
  });
  document.addEventListener('focusout', function (e) {
    const open = document.querySelector('.dropdown');
    if (!open) return;
    if (e.relatedTarget && open.contains(e.relatedTarget)) return;
    setTimeout(function () {
      const still = document.querySelector('.dropdown');
      if (still) still.remove();
    }, 180);
  });
}

$(document).on('input', 'input[name^="samples"]', function () {
    updateRegistrationSummary();
});

$(document).ready(function () {
    // Initialize Select2 for document number
    $('#documentno').select2({
        placeholder: 'Select Document',
        allowClear: true,
        ajax: {
            url: 'ajax/getDocumentNos.php',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                if (data.status === 'success' && Array.isArray(data.data)) {
                    return {
                        results: data.data.map(docNo => ({
                            id: docNo.HeaderID,
                            text: docNo.DocumentNo
                        }))
                    };
                } else {
                    return { results: [] };
                }
            },
            cache: true
        }
    });
    // Handle document selection and populate form
    //$('#documentno').on('select2:select'
    $('#documentno').on('select2:select', function (e) {
        const docNo = e.params.data.id;
        const batchNo = e.params.data.text;
        if (!docNo) return;

        const tbody = $('#sampleRows');
        tbody.empty();

        fetch(`ajax/getBatchDetails.php?documentno=${encodeURIComponent(docNo)}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Fill header info
                    data.data.header.forEach(header => {
                        $('#batchnoID').val(batchNo);
                        $('#HeaderID').val(docNo);
                        $('#date').val(new Date(header.Date.split(' ')[0]).toISOString().split('T')[0]);
                        $('#CustomerName').val(header.CustomerName || '');
                        $('#CustomerID').val(header.CustomerID || '');
                        $('#sampledby').val(header.SampledBy || '');
                        $('#SamplingMethod').val(header.SamplingMethod || '');
                        $('#samplingdate').val(new Date(header.SamplingDate.split(' ')[0]).toISOString().split('T')[0]);
                        $('#Orderno').val(header.OrderNo || '');
                    });

                    // Fill sample rows and params-rows
                    data.data.samples.forEach((row, idx) => {
                        const irow = idx + 1;
                        document.getElementById('tablecount').value = irow;

                        const tmpPath = row.SampleFileKey || 'images/no-image.png';
                        let imagePath = tmpPath.replace('../', '');
                        const date_of_manufacture = row.date_of_manufacture ? new Date(row.date_of_manufacture.split(' ')[0]).toISOString().split('T')[0] : '';
                        const date_of_expiry = row.date_of_expiry ? new Date(row.date_of_expiry.split(' ')[0]).toISOString().split('T')[0] : '';
                        const chilled_date_of_expiry = row.chilled_date_of_expiry ? new Date(row.chilled_date_of_expiry.split(' ')[0]).toISOString().split('T')[0] : '';
                        const frozen_date_of_expiry = row.frozen_date_of_expiry ? new Date(row.frozen_date_of_expiry.split(' ')[0]).toISOString().split('T')[0] : '';

                        const tr = `
                            <tr class="main-row">
                                <td>
                                    <input type="hidden" id="SampleID_${irow}" name="SampleID[]" value="${row.SampleID}" />
                                    <input type="hidden" id="rowindex_${irow}" name="rowindex[]" value="${row.TestID}" />
                                    <input type="text" id="StandardName_${irow}" value="${row.standardname || ''}" name="StandardName[]" autocomplete="off" onkeyup="handleStandardInput(event)" class="form-control form-control-sm" />
                                    <input type="hidden" id="StandardID_${irow}" value="${row.StandardID || ''}" name="standard_id[]" />
                                </td>
                                <td>
                                    <input type="text" id="MatrixName_${irow}" value="${row.matrixname || ''}" name="MatrixName[]" autocomplete="off" onkeyup="handleMatrixInput(event)" class="form-control form-control-sm"
                                        onmouseenter="showPreview(event, document.getElementById('StandardID_${irow}').value)" onmouseleave="hidePreview(event)" />
                                    <input type="hidden" id="MatrixID_${irow}" value="${row.BaseID || ''}" name="matrix_id[]" />
                                </td>
                                <td>
                                    <input type="number" id="samples_${irow}" value="${row.samples || ''}" name="samples[]" class="form-control form-control-sm" min="1" />
                                </td>
                                <td>
                                    <input type="text" placeholder="Enter SKU units" value="${row.standard_kit_units || ''}" id="standard_kit_units_${irow}" name="standard_kit_units[]" class="form-control form-control-sm"/>
                                </td>
                                <td>
                                    <input type="text" id="product_batch_no_${irow}" value="${row.product_batch_no || ''}" name="product_batch_no[]" class="form-control form-control-sm" />
                                </td>
                                <td>
                                    <input type="number" id="batch_size_${irow}" value="${row.batch_size || ''}" name="batch_size[]" class="form-control form-control-sm" />
                                </td>
                                <td>
                                    <input type="date" id="date_of_manufacture_${irow}" value="${date_of_manufacture}" name="date_of_manufacture[]" class="form-control form-control-sm" />
                                </td>
                                <td>
                                    <input type="date" id="date_of_expiry_${irow}" value="${date_of_expiry}" name="date_of_expiry[]" class="form-control form-control-sm" />
                                </td>
                                <td>
                                    <input type="date" id="chilled_date_of_expiry_${irow}" value="${chilled_date_of_expiry}" name="chilled_date_of_expiry[]" class="form-control form-control-sm" />
                                </td>
                                <td>
                                    <input type="date" id="frozen_date_of_expiry_${irow}" value="${frozen_date_of_expiry}" name="frozen_date_of_expiry[]" class="form-control form-control-sm" />
                                </td>
                                <td>
                                    <img src="${imagePath}" alt="Sample Image" class="thumb-preview" data-full="${imagePath}">
                                    <input type="file" id="sample_image_${irow}" accept="image/jpeg,image/gif" name="sample_images[]" class="form-control-file" />
                                </td>
                                <td>
                                    <input type="text" id="sample_source_${irow}" value="${row.sample_source || ''}" name="sample_source[]" class="form-control form-control-sm" onkeyup="handleSampleSourceInput(event)" />
                                </td>
                                <td>
                                    <input type="text" id="sample_name_${irow}" value="${row.sample_name || ''}" name="sample_name[]" class="form-control form-control-sm" />
                                </td>
                                <td>
                                    <input type="text" id="sample_method_${irow}" value="${row.sample_method || ''}" name="sample_method[]" class="form-control form-control-sm" />
                                </td>
                                <td>
                                    <select id="condition_of_sample_${irow}" name="condition_of_sample[]" class="form-control form-control-sm">
                                        <option value="">Select Condition</option>
                                        <option value="Fresh" ${row.condition_of_sample === 'Fresh' ? 'selected' : ''}>Fresh</option>
                                        <option value="Chilled" ${row.condition_of_sample === 'Chilled' ? 'selected' : ''}>Chilled</option>
                                        <option value="Frozen" ${row.condition_of_sample === 'Frozen' ? 'selected' : ''}>Frozen</option>
                                        <option value="Dried" ${row.condition_of_sample === 'Dried' ? 'selected' : ''}>Dried</option>
                                        <option value="Preserved" ${row.condition_of_sample === 'Preserved' ? 'selected' : ''}>Preserved</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger removeRow" title="Remove row"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>`;

                        tbody.append(tr);
// Extract parameters and selected from nested structure
                     // Extract parameters from nested structure
                        const testIdKey = row && row.TestID != null ? String(row.TestID) : null;
                        let nestedParams = [];
                        if (testIdKey && row[testIdKey]) {
                            nestedParams = Array.isArray(row[testIdKey].parameters) ? row[testIdKey].parameters : [];
                        }
                        const parameters = Array.isArray(row.parameters) ? row.parameters : nestedParams;

                        // Add params-row and populate checkboxes
                        addParamsRow(irow, parameters);
                        // Set initial visibility based on existing MatrixName value
                        const matrixInput = document.getElementById(`MatrixName_${irow}`);
                        const paramsRowEl = document.getElementById(`params_row_${irow}`);
                        if (matrixInput && matrixInput.value.trim() === '') {
                            if (paramsRowEl) paramsRowEl.style.display = '';
                        } else {
                            if (paramsRowEl) paramsRowEl.style.display = 'none';
                        }
                    });

                    updateRegistrationSummary();
                } else {
                    alert(data.message || 'Error retrieving batch details');
                }
            })
            .catch(err => {
                console.error(err);
                alert('AJAX error');
            });
    });

    // Image tooltip for previews
    const tooltip = document.createElement('div');
    tooltip.id = 'imageTooltip';
    document.body.appendChild(tooltip);

    document.addEventListener('mouseover', function (e) {
        if (e.target.classList.contains('thumb-preview')) {
            const fullSrc = e.target.getAttribute('data-full');
            tooltip.innerHTML = `<img src="${fullSrc}" alt="Full Image">`;
            tooltip.style.display = 'block';
        }
    });

    document.addEventListener('mousemove', function (e) {
        tooltip.style.top = (e.pageY - 50) + 'px';
        tooltip.style.left = (e.pageX + 15) + 'px';
    });

    document.addEventListener('mouseout', function (e) {
        if (e.target.classList.contains('thumb-preview')) {
            tooltip.style.display = 'none';
        }
    });

    // Table cell click handler
    document.addEventListener('click', function (e) {
        if (e.target.tagName === 'TD') {
            document.querySelectorAll('#sampleTable td').forEach(td => td.classList.remove('active'));
            e.target.classList.add('active');
        }
    });

    // Row removal handler
    document.getElementById('sampleTable').addEventListener('click', function (e) {
        if (e.target.classList.contains('removeRow')) {
            const mainRows = document.querySelectorAll('#sampleRows tr.main-row');
            if (mainRows.length > 1) {
                const tr = e.target.closest('tr');
                const rowId = tr.querySelector('input[name="rowindex[]"]').value;
                const paramsTr = document.getElementById(`params_row_${rowId}`);
                if (paramsTr) paramsTr.remove();
                tr.remove();
                reindexSampleRows();
                updateRegistrationSummary();
            }
        }
    });

    // Auth check
    const username = localStorage.getItem('username');
    if (!username) {
        localStorage.clear();
        localStorage.setItem('saygoodbye', 'proof');
        window.location.href = 'index.php?loutout=yes';
    }

    // New customer modal
    $('#new_customer').click(function () {
        const modal = new bootstrap.Modal(document.getElementById('modal'), { backdrop: 'static', keyboard: false });
        modal.show();
    });

    // Load countries
    $.ajax({
        url: 'jsonfiles/Countriesarray.php',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            var countrySelect = $('#countrySelect');
            $.each(data, function (index, country) {
                countrySelect.append($('<option></option>').attr('value', country).text(country));
            });
        },
        error: function (xhr, status, error) {
            toastr.error('Error fetching countries: ' + error.message);
        }
    });

    // Customer form submission
    $(document).on('submit', '#customerForm', function (e) {
        e.preventDefault();
        const customerData = $(this).serialize();
        $.ajax({
            url: 'ajax/saveCustomer.php',
            type: 'POST',
            data: customerData,
            success: function (response) {
                generalPurposeTypeLine('Customer added successfully!');
                generalPurposeTypeLine(response);
                bootstrap.Modal.getOrCreateInstance($('#modal')[0]).hide();
            },
            error: function () {
                toastr.error('Error adding customer. Please try again.');
            }
        });
    });

    // Lab form submission
    $(document).off('submit', '#labform').on('submit', '#labform', function (e) {
        e.preventDefault();

        if (!validateSampleRows()) {
            return;
        }

        const $submitBtns = $(this).find('button[type="submit"]');
        const originalHtmls = [];
        $submitBtns.each(function () { originalHtmls.push($(this).html()); });
        $submitBtns.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

        const form = this;
        // Build FormData from the form directly to ensure file inputs are captured
        let formData = new FormData(form);

        // Ensure HeaderID and batchno are present (some browsers/inputs may not include them)
        if (!formData.get('HeaderID')) {
            formData.append('HeaderID', document.getElementById('HeaderID') ? document.getElementById('HeaderID').value : '');
        }
        if (!formData.get('batchno')) {
            formData.append('batchno', document.getElementById('batchnoID') ? document.getElementById('batchnoID').value : '');
        }

        // If params hidden inputs are dynamically created they are already inside the form; avoid appending duplicate arrays

        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            const value = localStorage.getItem(key);
            formData.append(key, value);
        }

        // Add new fields to formData
        document.querySelectorAll('#sampleRows tr.main-row').forEach((row, idx) => {
            formData.append('rowindex[]', idx);
            formData.append('standard_id[]', row.querySelector('[name="standard_id[]"]').value);
            formData.append('matrix_id[]', row.querySelector('[name="matrix_id[]"]').value);
            formData.append('samples[]', row.querySelector('[name="samples[]"]').value);
            formData.append('kit_units[]', row.querySelector('[name="standard_kit_units[]"]').value);
            formData.append('batch_no[]', row.querySelector('[name="product_batch_no[]"]').value);
            formData.append('batch_size[]', row.querySelector('[name="batch_size[]"]').value);
            formData.append('date_mfg[]', row.querySelector('[name="date_of_manufacture[]"]').value);
            formData.append('date_exp[]', row.querySelector('[name="date_of_expiry[]"]').value);
            formData.append('chilled_date_exp[]', row.querySelector('[name="chilled_date_of_expiry[]"]').value);
            formData.append('frozen_date_exp[]', row.querySelector('[name="frozen_date_of_expiry[]"]').value);
            formData.append('sample_source[]', row.querySelector('[name="sample_source[]"]').value);
            formData.append('sample_name[]', row.querySelector('[name="sample_name[]"]').value);
            formData.append('sample_method[]', row.querySelector('[name="sample_method[]"]').value);
            formData.append('condition_of_sample[]', row.querySelector('[name="condition_of_sample[]"]').value);

            const fileInput = row.querySelector('[name="sample_images[]"]');
            if (fileInput && fileInput.files.length) {
                formData.append('sample_images[]', fileInput.files[0]);
            }
        });

        $.ajax({
            url: 'ajax/sampleregistrationAjaxUpdate.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                try {
                    const res = typeof response === 'object' ? response : JSON.parse(response);
                    if (res.success) {
                        toastr.success('Data successfully saved.');
                        setTimeout(function () { window.location.href = 'homepage.php'; }, 5000);
                    } else {
                        toastr.error('Error: ' + (res.message || 'Unknown server error.'));
                        $submitBtns.prop('disabled', false).each(function (i) {
                            $(this).html(originalHtmls[i] || '<i class="fas fa-save"></i> Save');
                        });
                    }
                } catch (err) {
                    toastr.error('Invalid server response. See console for details.');
                    console.error('Response parse error:', err, response);
                    $submitBtns.prop('disabled', false).each(function (i) {
                        $(this).html(originalHtmls[i] || '<i class="fas fa-save"></i> Save');
                    });
                }
            },
            error: function (xhr, status, error) {
                toastr.error('An error occurred: ' + error + '\nResponse: ' + xhr.responseText);
                $submitBtns.prop('disabled', false).each(function (i) {
                    $(this).html(originalHtmls[i] || '<i class="fas fa-save"></i> Save');
                });
            },
            complete: function () {
                $submitBtns.prop('disabled', false).each(function (i) {
                    $(this).html(originalHtmls[i] || '<i class="fas fa-save"></i> Save');
                });
                updateRegistrationSummary();
            }
        });
    });
});

// Add a new sample row
function addSampleRow() {
    const rowIndex = document.querySelectorAll('#sampleRows tr.main-row').length;
    const irow = rowIndex + 1;
    document.getElementById('tablecount').value = irow;

    const row = document.createElement('tr');
    row.classList.add('main-row');
    row.innerHTML = `
        <td>
            <input type="hidden" id="SampleID_${irow}" name="SampleID[]" value="" />
            <input type="hidden" id="rowindex_${irow}" name="rowindex[]" value="${irow}" />
            <input type="text" id="StandardName_${irow}" name="StandardName[]" autocomplete="off" onkeyup="handleStandardInput(event)" class="form-control form-control-sm" />
            <input type="hidden" id="StandardID_${irow}" name="standard_id[]" />
        </td>
        <td>
            <input type="text" id="MatrixName_${irow}" name="MatrixName[]" autocomplete="off" onkeyup="handleMatrixInput(event)" class="form-control form-control-sm"
                onmouseenter="showPreview(event, document.getElementById('StandardID_${irow}').value)" onmouseleave="hidePreview(event)" />
            <input type="hidden" id="MatrixID_${irow}" name="matrix_id[]" />
        </td>
        <td>
            <input type="number" id="samples_${irow}" name="samples[]" class="form-control form-control-sm" min="1" value="1" />
        </td>
        <td>
            <input type="text" placeholder="Enter SKU units" id="standard_kit_units_${irow}" name="standard_kit_units[]" class="form-control form-control-sm"/>
        </td>
        <td>
            <input type="text" id="product_batch_no_${irow}" name="product_batch_no[]" class="form-control form-control-sm" />
        </td>
        <td>
            <input type="number" id="batch_size_${irow}" name="batch_size[]" class="form-control form-control-sm" />
        </td>
        <td>
            <input type="date" id="date_of_manufacture_${irow}" name="date_of_manufacture[]" class="form-control form-control-sm" />
        </td>
        <td>
            <input type="date" id="date_of_expiry_${irow}" name="date_of_expiry[]" class="form-control form-control-sm" />
        </td>
        <td>
            <input type="date" id="chilled_date_of_expiry_${irow}" name="chilled_date_of_expiry[]" class="form-control form-control-sm" />
        </td>
        <td>
            <input type="date" id="frozen_date_of_expiry_${irow}" name="frozen_date_of_expiry[]" class="form-control form-control-sm" />
        </td>
        <td>
            <input type="file" id="sample_image_${irow}" accept="image/jpeg,image/gif" name="sample_images[]" class="form-control-file" />
        </td>
        <td>
            <input type="text" id="sample_source_${irow}" name="sample_source[]" class="form-control form-control-sm" onkeyup="handleSampleSourceInput(event)" />
        </td>
        <td>
            <input type="text" id="sample_name_${irow}" name="sample_name[]" class="form-control form-control-sm" />
        </td>
        <td>
            <input type="text" id="sample_method_${irow}" name="sample_method[]" class="form-control form-control-sm" />
        </td>
        <td>
            <select id="condition_of_sample_${irow}" name="condition_of_sample[]" class="form-control form-control-sm">
                <option value="">Select Condition</option>
                <option value="Fresh">Fresh</option>
                <option value="Chilled">Chilled</option>
                <option value="Frozen">Frozen</option>
                <option value="Dried">Dried</option>
                <option value="Preserved">Preserved</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger removeRow" title="Remove row"><i class="fas fa-trash-alt"></i></button>
        </td>
    `;

    document.getElementById('sampleRows').appendChild(row);
    addParamsRow(irow);

    setTimeout(function () {
        let container = document.getElementById('sampleTableContainer');
        if (container) container.scrollTop = container.scrollHeight;
    }, 50);

    reindexSampleRows();
    updateRegistrationSummary();
}

// Add params-row for checkboxes
function addParamsRow(irow, parameters = []) {
    const paramsRow = document.createElement('tr');
    paramsRow.classList.add('params-row');
    paramsRow.id = `params_row_${irow}`;
    paramsRow.style.display = 'none';
    paramsRow.innerHTML = `
        <td colspan="15">
            <div id="params_container_${irow}"></div>
        </td>
    `;
    document.getElementById('sampleRows').appendChild(paramsRow);

    const paramsContainer = document.getElementById(`params_container_${irow}`);
    if (paramsContainer && parameters.length > 0) {
        parameters.forEach(p => {
            const label = document.createElement('label');
            label.style.display = 'inline-block';
            label.style.marginRight = '10px';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = `selected_params_${irow}[]`;
            cb.value = p.ParameterID;
            cb.checked = true; // All parameters are selected by default
            label.appendChild(cb);
            label.appendChild(document.createTextNode(` ${p.ParameterName || 'Unknown Parameter'}`));
            paramsContainer.appendChild(label);
        });
    } else {
        paramsContainer.innerHTML = '<p>No parameters available.</p>';
    }
}

// Reindex rows after add/remove
function reindexSampleRows() {
    let i = 0;
    const rows = document.querySelectorAll('#sampleRows tr');
    let currentMainRow = null;
    rows.forEach((tr) => {
        if (tr.classList.contains('main-row')) {
            i++;
            currentMainRow = tr;
            const setIf = (selector, id) => {
                const el = tr.querySelector(selector);
                if (el) el.id = id;
            };

            setIf('input[name="SampleID[]"]', `SampleID_${i}`);
            setIf('input[name="rowindex[]"]', `rowindex_${i}`);
            setIf('input[name="StandardName[]"]', `StandardName_${i}`);
            setIf('input[name="standard_id[]"]', `StandardID_${i}`);
            setIf('input[name="MatrixName[]"]', `MatrixName_${i}`);
            setIf('input[name="matrix_id[]"]', `MatrixID_${i}`);
            setIf('input[name="samples[]"]', `samples_${i}`);
            setIf('input[name="standard_kit_units[]"]', `standard_kit_units_${i}`);
            setIf('input[name="product_batch_no[]"]', `product_batch_no_${i}`);
            setIf('input[name="batch_size[]"]', `batch_size_${i}`);
            setIf('input[name="date_of_manufacture[]"]', `date_of_manufacture_${i}`);
            setIf('input[name="date_of_expiry[]"]', `date_of_expiry_${i}`);
            setIf('input[name="chilled_date_of_expiry[]"]', `chilled_date_of_expiry_${i}`);
            setIf('input[name="frozen_date_of_expiry[]"]', `frozen_date_of_expiry_${i}`);
            setIf('input[name="sample_images[]"]', `sample_image_${i}`);
            setIf('input[name="sample_source[]"]', `sample_source_${i}`);
            setIf('input[name="sample_name[]"]', `sample_name_${i}`);
            setIf('input[name="sample_method[]"]', `sample_method_${i}`);
            setIf('select[name="condition_of_sample[]"]', `condition_of_sample_${i}`);

            const mat = tr.querySelector('input[name="MatrixName[]"]');
            if (mat) {
                mat.setAttribute('onmouseenter', `showPreview(event, document.getElementById('StandardID_${i}') ? document.getElementById('StandardID_${i}').value : '')`);
                mat.setAttribute('onmouseleave', 'hidePreview(event)');
            }

            const rowIndexInput = tr.querySelector('input[name="rowindex[]"]');
            if (rowIndexInput) rowIndexInput.value = i;
        } else if (tr.classList.contains('params-row') && currentMainRow) {
            tr.id = `params_row_${i}`;
            const container = tr.querySelector('div');
            if (container) {
                container.id = `params_container_${i}`;
                tr.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.name = `selected_params_${i}[]`;
                });
            }
        }
    });
    document.getElementById('tablecount').value = i;
    updateRegistrationSummary();
}

// Parameter preview tooltip
function showPreview(event, stdId) {
    const inputId = event.target.id;
    const hiddenFieldId = inputId.replace('Name', 'ID');
    const query = document.getElementById(hiddenFieldId).value;
    const triggerEl = event.target;
    triggerEl.setAttribute('data-bs-original-title', '<em>Loading parameters...</em>');
    let tooltip = bootstrap.Tooltip.getInstance(triggerEl);
    if (!tooltip) {
        tooltip = new bootstrap.Tooltip(triggerEl, { html: true, placement: 'auto' });
    }

    fetch('ajax/getParametersUnderMatrix.php?stdId=' + encodeURIComponent(stdId) + '&matrixid=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            let content;
            if (!Array.isArray(data) || data.length === 0) {
                content = '<span class="text-muted">No parameters found.</span>';
            } else {
                const list = data.map(p => `• ${p.ParameterName}`).join('<br>');
                content = `<strong>Full List of Parameters Available:</strong><br>${list}`;
            }
            triggerEl.setAttribute('data-bs-original-title', content);
            tooltip.show();
        })
        .catch(err => {
            console.error(err);
            triggerEl.setAttribute('data-bs-original-title', '<span class="text-danger">Error loading parameters.</span>');
            tooltip.show();
        });
}

function hidePreview(event) {
    const tooltip = bootstrap.Tooltip.getInstance(event.target);
    if (tooltip) tooltip.hide();
}

// Autocomplete for MatrixName (fixed to avoid undefined dropdown error and ensure responsive show/hide)
async function handleMatrixInput(event) {
    const query = event.target.value;
    const inputId = event.target.id;
    const dropdownId = 'dropdown_' + inputId;
    const hiddenFieldId = inputId.replace('Name', 'ID');
    const rowNum = inputId.split('_')[1];
    const paramsRow = document.getElementById(`params_row_${rowNum}`);

    // Remove previous dropdowns
    const existingDropdown = document.getElementById(dropdownId);
    if (existingDropdown) {
        existingDropdown.remove();
    }
    document.querySelectorAll('.dropdown').forEach(d => d.remove());

    const inputEl = document.getElementById(inputId);
    const rect = inputEl.getBoundingClientRect();
    const dropdown = document.createElement('div');
    dropdown.id = dropdownId;
    dropdown.classList.add('dropdown');
    dropdown._owner = inputEl;
    dropdown.style.width = 'max-content';
    dropdown.style.minWidth = inputEl.offsetWidth + 'px';
    dropdown.style.maxWidth = (window.innerWidth - Math.round(rect.left) - 10) + 'px';
    document.body.appendChild(dropdown);
    const scrollY = window.scrollY || document.documentElement.scrollTop;
    const scrollX = window.scrollX || document.documentElement.scrollLeft;
    dropdown.style.left = rect.left + scrollX + 'px';
    dropdown.style.top = rect.bottom + scrollY + 'px';
    dropdown.innerHTML = '';

    if (!query.trim()) {
        if (paramsRow) paramsRow.style.display = ''; // Show checkboxes if MatrixName empty
        document.getElementById(hiddenFieldId).value = '';
        dropdown.style.display = 'none';
        return;
    }

    if (paramsRow) paramsRow.style.display = 'none'; // Hide checkboxes if MatrixName not empty

    try {
        const resp = await fetch(`ajax/searchmatrixes.php?q=${encodeURIComponent(query)}`);
        const matrices = await resp.json();

        if (matrices.length) {
            dropdown.style.display = 'block';
            matrices.forEach(mtx => {
                const div = document.createElement('div');
                div.textContent = mtx.FullPath;
                div.dataset.code = mtx.ParameterID;
                div.classList.add('dropdown-item');
                div.addEventListener('click', () => {
                    inputEl.value = mtx.FullPath;
                    document.getElementById(hiddenFieldId).value = mtx.ParameterID;
                    dropdown.style.display = 'none';
                    if (paramsRow) paramsRow.style.display = 'none';
                });
                dropdown.appendChild(div);
            });
        } else {
            dropdown.style.display = 'none';
        }
    } catch (err) {
        console.error('Error fetching matrix parameters:', err);
    }
}

// Autocomplete for StandardName
async function handleStandardInput(event) {
    const query = event.target.value;
    const inputId = event.target.id;
    const dropdownId = 'dropdown_' + inputId;
    const hiddenFieldId = inputId.replace('Name', 'ID');
    const rowNum = inputId.split('_')[1];

    const existingDropdown = document.getElementById(dropdownId);
    if (existingDropdown) {
        existingDropdown.remove();
    }
    document.querySelectorAll('.dropdown').forEach(d => d.remove());

    const inputEl = document.getElementById(inputId);
    const rect = inputEl.getBoundingClientRect();
    const dropdown = document.createElement('div');
    dropdown.id = dropdownId;
    dropdown.classList.add('dropdown');
    dropdown._owner = inputEl;
    dropdown.style.width = 'max-content';
    dropdown.style.minWidth = inputEl.offsetWidth + 'px';
    dropdown.style.maxWidth = (window.innerWidth - Math.round(rect.left) - 10) + 'px';
    document.body.appendChild(dropdown);

    const scrollY = window.scrollY || document.documentElement.scrollTop;
    const scrollX = window.scrollX || document.documentElement.scrollLeft;
    dropdown.style.left = rect.left + scrollX + 'px';
    dropdown.style.top = rect.bottom + scrollY + 'px';
    dropdown.innerHTML = '';

    if (!query.trim()) {
        dropdown.style.display = 'none';
        return;
    }

    try {
        const resp = await fetch('ajax/searchstandards.php?query=' + encodeURIComponent(query));
        const standards = await resp.json();

        if (standards.length) {
            dropdown.style.display = 'block';
            standards.forEach(std => {
                const div = document.createElement('div');
                div.textContent = std.StandardName;
                div.dataset.code = std.StandardID;
                div.addEventListener('click', () => {
                    inputEl.value = std.StandardName;
                    document.getElementById(hiddenFieldId).value = std.StandardID;
                    dropdown.style.display = 'none';

                    const paramsContainer = document.getElementById(`params_container_${rowNum}`);
                    if (paramsContainer) {
                        fetch(`ajax/getParametersUnderMatrix.php?stdId=${std.StandardID}&matrixid=0`)
                            .then(res => res.json())
                            .then(data => {
                                paramsContainer.innerHTML = '';
                                if (!Array.isArray(data) || data.length === 0) {
                                    paramsContainer.innerHTML = '<p>No parameters available?.</p>';
                                } else {
                                    data.forEach(p => {
                                        const label = document.createElement('label');
                                        label.style.display = 'inline-block';
                                        label.style.marginRight = '10px';
                                        const cb = document.createElement('input');
                                        cb.type = 'checkbox';
                                        cb.name = `selected_params_${rowNum}[]`;
                                        cb.value = p.ParameterID;
                                        cb.checked = true;
                                        label.appendChild(cb);
                                        label.appendChild(document.createTextNode(` ${p.ParameterName}`));
                                        paramsContainer.appendChild(label);
                                    });
                                }
                                const matrixInput = document.getElementById(`MatrixName_${rowNum}`);
                                const paramsRow = document.getElementById(`params_row_${rowNum}`);
                                if (matrixInput && matrixInput.value.trim() === '') {
                                    if (paramsRow) paramsRow.style.display = '';
                                } else {
                                    if (paramsRow) paramsRow.style.display = 'none';
                                }
                            })
                            .catch(err => console.error('Error fetching parameters:', err));
                    }
                });
                dropdown.appendChild(div);
            });
        } else {
            dropdown.style.display = 'none';
        }
    } catch (err) {
        console.error('Error fetching standards:', err);
    }
}

// Autocomplete for CustomerName
async function handleCustomerNameInput(event) {
    const customerName = event.target.value;
    const inputId = event.target.id;
    const inputElement = document.getElementById(inputId);
    const dropdownId = 'dropdown_' + inputId;
    const existingDropdown = document.getElementById(dropdownId);
    if (existingDropdown) {
        existingDropdown.remove();
    }
    document.querySelectorAll('.dropdown').forEach(dropdown => dropdown.remove());

    const rect = inputElement.getBoundingClientRect();
    const dropdown = document.createElement('div');
    dropdown.id = dropdownId;
    dropdown.classList.add('dropdown');
    dropdown._owner = inputElement;
    dropdown.style.position = 'absolute';
    dropdown.style.backgroundColor = '#fff';
    dropdown.style.border = '1px solid #ccc';
    dropdown.style.width = 'max-content';
    dropdown.style.minWidth = inputElement.offsetWidth + 'px';
    dropdown.style.maxWidth = (window.innerWidth - Math.round(rect.left) - 10) + 'px';
    dropdown.style.zIndex = 1000;
    document.body.appendChild(dropdown);

    const scrollY = window.scrollY || document.documentElement.scrollTop;
    const scrollX = window.scrollX || document.documentElement.scrollLeft;
    dropdown.style.left = rect.left + scrollX + 'px';
    dropdown.style.top = rect.bottom + scrollY + 'px';
    dropdown.innerHTML = '';

    if (!customerName.trim()) {
        dropdown.style.display = 'none';
        return;
    }

    try {
        const response = await fetch('ajax/searchcustomers.php?query=' + encodeURIComponent(customerName));
        const customers = await response.json();

        if (customers.length > 0) {
            dropdown.style.display = 'block';
            customers.forEach(customer => {
                const div = document.createElement('div');
                div.textContent = customer.name;
                div.dataset.code = customer.code;
                div.dataset.name = customer.name;
                div.dataset.currency = customer.currency;
                div.dataset.salespersoncode = customer.salespersoncode;
                div.addEventListener('click', function () {
                    document.getElementById('CustomerID').value = this.dataset.code;
                    document.getElementById('CustomerName').value = this.dataset.name;
                    dropdown.style.display = 'none';
                });
                dropdown.appendChild(div);
            });
        } else {
            dropdown.style.display = 'none';
        }
    } catch (error) {
        console.error('Error fetching parameters:', error.message);
    }
}

// Validate sample rows
function validateSampleRows() {
    const rows = document.querySelectorAll('#sampleRows tr.main-row');
    if (rows.length === 0) {
        toastr.error('Please add at least one sample row.');
        return false;
    }

    let valid = true;
    let firstInvalidEl = null;

    rows.forEach((row, idx) => {
        const std = row.querySelector('input[name="StandardName[]"]');
        const mat = row.querySelector('input[name="MatrixName[]"]');
        const samples = row.querySelector('input[name="samples[]"]');
        const kit = row.querySelector('input[name="standard_kit_units[]"]');
        const productBatch = row.querySelector('input[name="product_batch_no[]"]');
        const batchSize = row.querySelector('input[name="batch_size[]"]');
        const dom = row.querySelector('input[name="date_of_manufacture[]"]');
        const doe = row.querySelector('input[name="date_of_expiry[]"]');

        const markInvalid = (el) => {
            if (!el) return;
            el.classList.add('is-invalid');
            if (!firstInvalidEl) firstInvalidEl = el;
            valid = false;
        };
        const markValid = (el) => {
            if (!el) return;
            el.classList.remove('is-invalid');
        };

        if (!std || !std.value.trim()) markInvalid(std); else markValid(std);
//        if (!mat || !mat.value.trim()) markInvalid(mat); else markValid(mat);
        if (!samples || !samples.value || parseInt(samples.value, 10) < 1) markInvalid(samples); else markValid(samples);
        if (!kit || !kit.value.trim()) markInvalid(kit); else markValid(kit);
        if (!productBatch || !productBatch.value.trim()) markInvalid(productBatch); else markValid(productBatch);
        // batch_size is optional now
        // if (!batchSize || !batchSize.value || parseInt(batchSize.value, 10) < 1) markInvalid(batchSize); else markValid(batchSize);

        if (dom && doe && dom.value && doe.value) {
            const domDate = new Date(dom.value);
            const doeDate = new Date(doe.value);
            if (doeDate <= domDate) {
                markInvalid(doe);
                markInvalid(dom);
            } else {
                markValid(dom);
                markValid(doe);
            }
        } else {
            if (!dom || !dom.value) markInvalid(dom); else markValid(dom);
            if (!doe || !doe.value) markInvalid(doe); else markValid(doe);
        }
    });

    if (!valid) {
        if (firstInvalidEl && typeof firstInvalidEl.scrollIntoView === 'function') {
            firstInvalidEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        toastr.error('Please fix the highlighted fields (all fields required, expiry must be after manufacture).');
    }
    return valid;
}

// Update registration summary
function updateRegistrationSummary() {
    const rows = document.querySelectorAll('#sampleRows tr.main-row');
    let totalRows = rows.length;
    let totalSamples = 0;
    rows.forEach(row => {
        const samplesEl = row.querySelector('input[name="samples[]"]');
        const n = samplesEl ? parseInt(samplesEl.value, 10) || 0 : 0;
        totalSamples += n;
    });

    document.getElementById('tablecount').value = totalRows;
    const out = document.getElementById('registrationsumary');
    if (out) {
        out.innerHTML = `<strong>Rows:</strong> ${totalRows} 
            &nbsp; | &nbsp; <strong>Total samples:</strong> ${totalSamples}`;
    }
}

// Customer name tooltip
function showtooltip(event) {
    const customerName = event.target.value;
    if (customerName != '') generalPurposeTypeLine(customerName);
}

async function handleSampleSourceInput(event) {
    const query = event.target.value;
    const inputId = event.target.id;
    const dropdownId = 'dropdown_' + inputId;

    const existingDropdown = document.getElementById(dropdownId);
    if (existingDropdown) {
        existingDropdown.remove();
    }
    document.querySelectorAll('.dropdown').forEach(d => d.remove());

    if (!query.trim()) return;

    const inputEl = document.getElementById(inputId);
    const dropdown = document.createElement('div');
    dropdown.id = dropdownId;
    dropdown.classList.add('dropdown');
    dropdown.style.position = 'absolute';
    dropdown.style.backgroundColor = '#fff';
    dropdown.style.border = '1px solid #ccc';
    dropdown.style.width = '200px';
    dropdown.style.zIndex = 1000;
    document.body.appendChild(dropdown);

    const rect = inputEl.getBoundingClientRect();
    const scrollY = window.scrollY || document.documentElement.scrollTop;
    const scrollX = window.scrollX || document.documentElement.scrollLeft;
    dropdown.style.left = rect.left + scrollX + 'px';
    dropdown.style.top = rect.bottom + scrollY + 'px';

    try {
        const resp = await fetch('ajax/searchsamplesources.php?q=' + encodeURIComponent(query));
        const sources = await resp.json();

        if (sources.length) {
            dropdown.style.display = 'block';
            sources.forEach(src => {
                const div = document.createElement('div');
                div.textContent = src.source_name || src.name || src;
                div.dataset.source = src.source_name || src.name || src;
                div.classList.add('dropdown-item');
                div.addEventListener('click', function() {
                    inputEl.value = this.dataset.source;
                    dropdown.style.display = 'none';
                });
                dropdown.appendChild(div);
            });
        } else {
            dropdown.style.display = 'none';
        }
    } catch (err) {
        console.error('Error fetching sample sources:', err);
    }
}