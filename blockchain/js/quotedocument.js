(function ($) {
    'use strict';

    const $form = $('#quoteForm');
    let defaultTerms = '';
    let submitMode = 'save_quote';
    let currentQuoteId = 0;
    let rowSeed = 0;
    let customerSearchTimer = null;
    let rowSearchTimers = {};

    if (!$form.length) {
        return;
    }

    function getInitialQuoteId() {
        const params = new URLSearchParams(window.location.search);
        return Number(params.get('quote_id') || 0);
    }

    function buildPdfUrl(quoteId) {
        const numericId = Number(quoteId || 0);
        return numericId > 0 ? 'ajax/quoteDocumentPdf.php?quote_id=' + encodeURIComponent(numericId) : '';
    }

    function formatMoney(value) {
        const numeric = Number(value || 0);
        return 'Ksh.' + numeric.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showInlineMessage(message, type) {
        const $alert = $('#quote-alert');
        if (!$alert.length) {
            return;
        }

        if (!message) {
            $alert.addClass('d-none').removeClass('alert-success alert-danger alert-info').text('');
            return;
        }

        const normalizedType = type === 'danger' ? 'danger' : (type === 'info' ? 'info' : 'success');
        $alert
            .removeClass('d-none alert-success alert-danger alert-info')
            .addClass('alert-' + normalizedType)
            .text(message);
    }

    function notify(message, type) {
        showInlineMessage(message, type);
        if (window.toastr && message) {
            const method = type === 'danger' ? 'error' : (type === 'info' ? 'info' : 'success');
            toastr[method](message);
        }
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.quote-ajax-dropdown').forEach(function (dropdown) {
            dropdown.remove();
        });
    }

    function positionDropdown(inputElement, dropdown) {
        const rect = inputElement.getBoundingClientRect();
        const scrollY = window.scrollY || document.documentElement.scrollTop;
        const scrollX = window.scrollX || document.documentElement.scrollLeft;

        dropdown.style.position = 'absolute';
        dropdown.style.left = rect.left + scrollX + 'px';
        dropdown.style.top = rect.bottom + scrollY + 'px';
        dropdown.style.width = Math.max(rect.width, 260) + 'px';
        dropdown.style.zIndex = '2000';
    }

    function createDropdown(inputElement) {
        closeAllDropdowns();
        const dropdown = document.createElement('div');
        dropdown.className = 'dropdown quote-ajax-dropdown';
        positionDropdown(inputElement, dropdown);
        document.body.appendChild(dropdown);
        return dropdown;
    }

    function normalizePrice(value) {
        const numeric = Number(value || 0);
        return Number.isFinite(numeric) && numeric >= 0 ? numeric : 0;
    }

    function normalizeSampleCount(value) {
        const numeric = Number(value || 0);
        if (!Number.isFinite(numeric) || numeric <= 0) {
            return 1;
        }
        return Math.max(1, Math.round(numeric));
    }

    function formatPriceInput(value) {
        return normalizePrice(value).toFixed(2);
    }

    function formatSampleInput(value) {
        return String(normalizeSampleCount(value));
    }

    function getNextRowIndex() {
        rowSeed += 1;
        return rowSeed;
    }

    function getQuoteRows() {
        return $('#quoteItemRows').find('tr.quote-main-row');
    }

    function getRowByIndex(rowIndex) {
        return $('#quoteItemRows').find('tr.quote-main-row[data-row-index="' + rowIndex + '"]');
    }

    function getParamsRowByIndex(rowIndex) {
        return $('#quote_params_row_' + rowIndex);
    }

    function getParamsContainerByIndex(rowIndex) {
        return $('#quote_params_container_' + rowIndex);
    }

    function getParamsMetaByIndex(rowIndex) {
        return $('#quote_params_meta_' + rowIndex);
    }

    function makeTestCode(standardCode, standardId) {
        const cleanCode = String(standardCode || '').trim();
        if (cleanCode !== '') {
            return cleanCode;
        }

        const numericStandardId = Number(standardId || 0);
        return numericStandardId > 0 ? 'STD-' + numericStandardId : '';
    }

    function renderCompanyProfile(company) {
        const profile = company || {};
        const lines = [];

        if (profile.address) {
            lines.push(profile.address);
        }
        if (profile.address1) {
            lines.push(profile.address1);
        }
        if (profile.address2) {
            lines.push(profile.address2);
        }
        if (profile.address3) {
            lines.push(profile.address3);
        }
        if (profile.telephone) {
            lines.push(profile.telephone);
        }
        if (profile.email) {
            lines.push(profile.email);
        }
        if (profile.accreditation_text) {
            lines.push(profile.accreditation_text);
        }

        $('#quote_company_name').text(profile.company_name || '');
        $('#quote_company_copy').html(lines.map(escapeHtml).join('<br>'));
    }

    function buildMainRowHtml(rowIndex, rowValues) {
        const values = $.extend({
            standard_name: '',
            standard_id: '',
            standard_code: '',
            matrix_name: '',
            matrix_id: '',
            quantity: '1',
            unit_price: '0.00'
        }, rowValues || {});

        return [
            '<tr class="quote-main-row" data-row-index="', escapeHtml(rowIndex), '">',
            '  <td>',
            '    <input type="hidden" class="quote-row-index" value="', escapeHtml(rowIndex), '">',
            '    <input type="hidden" class="quote-standard-id" name="quote_standard_id[]" value="', escapeHtml(values.standard_id || ''), '">',
            '    <input type="hidden" class="quote-standard-code" value="', escapeHtml(values.standard_code || ''), '">',
            '    <input type="text" id="StandardName_', escapeHtml(rowIndex), '" class="form-control form-control-sm quote-standard-name" value="', escapeHtml(values.standard_name || ''), '" autocomplete="off" placeholder="Search standard">',
            '  </td>',
            '  <td>',
            '    <input type="hidden" class="quote-matrix-id" name="quote_matrix_id[]" value="', escapeHtml(values.matrix_id || ''), '">',
            '    <input type="text" id="MatrixName_', escapeHtml(rowIndex), '" class="form-control form-control-sm quote-matrix-name" value="', escapeHtml(values.matrix_name || ''), '" autocomplete="off" placeholder="Optional matrix package">',
            '  </td>',
            '  <td><input type="number" min="1" step="1" class="form-control form-control-sm quote-samples text-end" value="', escapeHtml(formatSampleInput(values.quantity)), '"></td>',
            '  <td><input type="number" min="0" step="0.01" class="form-control form-control-sm quote-price text-end" value="', escapeHtml(formatPriceInput(values.unit_price)), '"></td>',
            '  <td>',
            '    <input type="text" class="form-control form-control-sm quote-row-total" value="0.00" readonly>',
            '    <div class="quote-page__row-meta">No tests selected</div>',
            '  </td>',
            '  <td><button type="button" class="btn btn-outline-danger btn-sm remove-row">Remove</button></td>',
            '</tr>'
        ].join('');
    }

    function buildParamsRowHtml(rowIndex) {
        return [
            '<tr class="quote-page__params-row" id="quote_params_row_', escapeHtml(rowIndex), '" data-row-index="', escapeHtml(rowIndex), '" style="display:none;">',
            '  <td colspan="6">',
            '    <div class="quote-page__params-meta" id="quote_params_meta_', escapeHtml(rowIndex), '">Choose a standard to load tests.</div>',
            '    <div class="quote-page__params-list" id="quote_params_container_', escapeHtml(rowIndex), '"></div>',
            '  </td>',
            '</tr>'
        ].join('');
    }

    function appendQuoteRow(rowData) {
        const rowIndex = getNextRowIndex();
        $('#quoteItemRows').append(buildMainRowHtml(rowIndex, rowData || {}));
        $('#quoteItemRows').append(buildParamsRowHtml(rowIndex));

        const parameters = Array.isArray(rowData && rowData.parameters) ? rowData.parameters : [];
        if (parameters.length) {
            renderRowParameters(rowIndex, parameters, true);
        } else {
            renderRowParameters(rowIndex, [], false);
        }

        const container = document.getElementById('items-table');
        if (container && container.parentElement) {
            container.parentElement.scrollTop = container.parentElement.scrollHeight;
        }

        recalculateTotals();
    }

    function buildParameterOptionHtml(parameter, rowIndex, checked, optionIndex) {
        const parameterId = String(parameter.parameter_id || parameter.ParameterID || optionIndex || '');
        const optionId = 'quote_param_' + rowIndex + '_' + parameterId + '_' + optionIndex;
        const parameterName = String(parameter.parameter_name || parameter.ParameterName || parameter.label || '').trim();
        const method = String(parameter.method || parameter.Method || '').trim();
        const standardId = String(parameter.standard_id || parameter.StandardID || '').trim();
        const standardName = String(parameter.standard_name || parameter.StandardName || '').trim();
        const standardCode = String(parameter.standard_code || parameter.StandardCode || '').trim();
        const testCode = makeTestCode(parameter.test_code || parameter.TestCode || standardCode, standardId);

        return [
            '<label class="quote-page__picker-option" for="', escapeHtml(optionId), '">',
            '  <input type="checkbox"',
            '         id="', escapeHtml(optionId), '"',
            checked ? ' checked' : '',
            '         data-parameter-id="', escapeHtml(parameterId), '"',
            '         data-parameter-name="', escapeHtml(parameterName), '"',
            '         data-method="', escapeHtml(method), '"',
            '         data-standard-id="', escapeHtml(standardId), '"',
            '         data-standard-name="', escapeHtml(standardName), '"',
            '         data-standard-code="', escapeHtml(standardCode), '"',
            '         data-test-code="', escapeHtml(testCode), '">',
            '  <span class="quote-page__picker-copy">',
            '    <strong>', escapeHtml(parameterName), '</strong>',
            method ? '    <span>' + escapeHtml(method) + '</span>' : '',
            '  </span>',
            '</label>'
        ].join('');
    }

    function renderRowParameters(rowIndex, rows, showRow) {
        const $paramsRow = getParamsRowByIndex(rowIndex);
        const $paramsContainer = getParamsContainerByIndex(rowIndex);
        const $paramsMeta = getParamsMetaByIndex(rowIndex);

        if (!$paramsContainer.length || !$paramsMeta.length) {
            return;
        }

        const sourceRows = Array.isArray(rows) ? rows : [];
        const safeRows = [];
        const seen = {};

        sourceRows.forEach(function (row) {
            const key = String(row.parameter_id || row.ParameterID || row.parameter_name || row.ParameterName || '').trim();
            if (!key || seen[key]) {
                return;
            }
            seen[key] = true;
            safeRows.push(row);
        });

        if (!safeRows.length) {
            $paramsContainer.html('<div class="quote-page__picker-empty">No tests available for this selection.</div>');
            $paramsMeta.text('Choose a standard and optionally a matrix to load tests.');
            if (showRow) {
                $paramsRow.show();
            } else {
                $paramsRow.hide();
            }
            recalculateTotals();
            return;
        }

        const html = safeRows.map(function (parameter, index) {
            const shouldCheck = parameter.checked !== false;
            return buildParameterOptionHtml(parameter, rowIndex, shouldCheck, index);
        }).join('');

        $paramsContainer.html(html);
        $paramsMeta.text('Tick the tests you want to include for this row.');
        if (showRow) {
            $paramsRow.show();
        } else {
            $paramsRow.hide();
        }

        recalculateTotals();
    }

    function collectSelectedParameters($row) {
        const rowIndex = Number($row.data('rowIndex') || 0);
        const parameters = [];

        getParamsContainerByIndex(rowIndex).find('input[type="checkbox"]:checked').each(function () {
            const $checkbox = $(this);
            parameters.push({
                parameter_id: String($checkbox.data('parameterId') || '').trim(),
                parameter_name: String($checkbox.data('parameterName') || '').trim(),
                method: String($checkbox.data('method') || '').trim(),
                standard_id: String($checkbox.data('standardId') || '').trim(),
                standard_name: String($checkbox.data('standardName') || '').trim(),
                standard_code: String($checkbox.data('standardCode') || '').trim(),
                test_code: String($checkbox.data('testCode') || '').trim()
            });
        });

        return parameters;
    }

    function extractRowContext($row) {
        return {
            rowIndex: Number($row.data('rowIndex') || 0),
            standardId: String($row.find('.quote-standard-id').val() || '').trim(),
            standardName: String($row.find('.quote-standard-name').val() || '').trim(),
            standardCode: String($row.find('.quote-standard-code').val() || '').trim(),
            matrixId: String($row.find('.quote-matrix-id').val() || '').trim(),
            matrixName: String($row.find('.quote-matrix-name').val() || '').trim(),
            quantity: normalizeSampleCount($row.find('.quote-samples').val()),
            unitPrice: normalizePrice($row.find('.quote-price').val())
        };
    }

    function updateRowTotal($row) {
        const context = extractRowContext($row);
        const selectedCount = collectSelectedParameters($row).length;
        const total = context.quantity * context.unitPrice * selectedCount;
        const metaText = selectedCount > 0
            ? selectedCount + ' test(s) x ' + context.quantity + ' sample(s)'
            : 'No tests selected';

        $row.find('.quote-row-total').val(total.toFixed(2));
        $row.find('.quote-page__row-meta').text(metaText);

        return total;
    }

    function updateQuoteItemSummary() {
        const $summary = $('#quoteItemSummary');
        if (!$summary.length) {
            return;
        }

        let rowCount = 0;
        let totalSamples = 0;
        let totalTests = 0;

        getQuoteRows().each(function () {
            const $row = $(this);
            rowCount += 1;
            totalSamples += normalizeSampleCount($row.find('.quote-samples').val());
            totalTests += collectSelectedParameters($row).length;
        });

        $summary.html(
            '<strong>Rows:</strong> ' + rowCount
            + ' &nbsp; | &nbsp; <strong>Selected tests:</strong> ' + totalTests
            + ' &nbsp; | &nbsp; <strong>Total samples:</strong> ' + totalSamples
        );
    }

    function syncDerivedHeaderFields() {
        const standardNames = [];
        const matrixNames = [];

        getQuoteRows().each(function () {
            const $row = $(this);
            const standardName = String($row.find('.quote-standard-name').val() || '').trim();
            const matrixName = String($row.find('.quote-matrix-name').val() || '').trim();

            if (standardName !== '' && standardNames.indexOf(standardName) === -1) {
                standardNames.push(standardName);
            }
            if (matrixName !== '' && matrixNames.indexOf(matrixName) === -1) {
                matrixNames.push(matrixName);
            }
        });

        $('#project_name').val(standardNames.join(', '));
        $('#matrix_name').val(matrixNames.join(', '));
    }

    function recalculateTotals() {
        let subtotal = 0;

        getQuoteRows().each(function () {
            subtotal += updateRowTotal($(this));
        });

        const discountPercent = Number($('#discount_percent').val() || 0);
        const discountAmount = subtotal * (discountPercent / 100);
        const totalAmount = subtotal - discountAmount;

        $('#subtotal_display').text(formatMoney(subtotal));
        $('#discount_display').text('-' + formatMoney(discountAmount));
        $('#total_display').text(formatMoney(totalAmount));

        updateQuoteItemSummary();
        syncDerivedHeaderFields();
    }

    function updatePrintLink(quoteId, pdfUrl) {
        const $link = $('#print-saved-quote');
        const hasQuote = Number(quoteId || 0) > 0;
        const resolvedUrl = pdfUrl || buildPdfUrl(quoteId);

        $link.attr('href', resolvedUrl || '#');
        $link.attr('data-pdf-url', resolvedUrl || '');

        if (hasQuote && resolvedUrl) {
            $link.removeAttr('hidden');
        } else {
            $link.attr('hidden', 'hidden');
        }
    }

    function syncHeaderStatus(formData) {
        currentQuoteId = Number((formData && formData.quote_id) || 0);
        $('#quote_number_preview').text((formData && formData.quote_number) || '');
        $('#quote_status_text').text(currentQuoteId > 0 ? 'Saved quote' : 'Draft quote');
    }

    function buildRowModelsFromItems(items) {
        const safeItems = Array.isArray(items) ? items : [];
        if (!safeItems.length) {
            return [{}];
        }

        const groups = new Map();

        safeItems.forEach(function (item, index) {
            const standardId = String(item.standard_id || item.StandardID || '').trim();
            const standardName = String(item.StandardName || item.standard_name || '').trim();
            const standardCode = String(item.standard_code || item.StandardCode || item.test_code || '').trim();
            const quantity = normalizeSampleCount(item.quantity || item.item_qty || 1);
            const unitPrice = normalizePrice(item.unit_price || item.item_unit_price || 0);
            const key = [standardId, quantity, unitPrice.toFixed(2)].join('|');

            if (!groups.has(key)) {
                groups.set(key, {
                    standard_id: standardId,
                    standard_name: standardName,
                    standard_code: standardCode,
                    matrix_id: '',
                    matrix_name: '',
                    quantity: quantity,
                    unit_price: unitPrice,
                    parameters: []
                });
            }

            const group = groups.get(key);
            const description = String(item.ParameterName || item.parameter_name || item.description_text || item.parameter_label || '').trim();
            group.parameters.push({
                parameter_id: String(item.parameter_id || '').trim() || String(index + 1),
                parameter_name: description,
                method: String(item.method_text || item.Method || '').trim(),
                standard_id: standardId,
                standard_name: standardName,
                standard_code: standardCode,
                test_code: String(item.test_code || makeTestCode(standardCode, standardId)).trim(),
                checked: true
            });
        });

        return Array.from(groups.values());
    }

    function populateForm(formData, items, customerLookup) {
        const payload = formData || {};

        Object.keys(payload).forEach(function (key) {
            const $field = $form.find('[name="' + key + '"]');
            if ($field.length) {
                $field.val(payload[key] == null ? '' : payload[key]);
            }
        });

        $('#customer_lookup').val(customerLookup || payload.client_name || '');

        rowSeed = 0;
        $('#quoteItemRows').empty();
        buildRowModelsFromItems(items).forEach(function (rowModel) {
            appendQuoteRow(rowModel);
        });

        if (!getQuoteRows().length) {
            appendQuoteRow({});
        }

        updatePrintLink(payload.quote_id, buildPdfUrl(payload.quote_id));
        syncHeaderStatus(payload);
        recalculateTotals();
    }

    function syncHistory(quoteId) {
        const numericId = Number(quoteId || 0);
        if (!window.history || typeof window.history.replaceState !== 'function') {
            return;
        }

        const url = new URL(window.location.href);
        if (numericId > 0) {
            url.searchParams.set('quote_id', String(numericId));
        } else {
            url.searchParams.delete('quote_id');
        }
        window.history.replaceState({}, '', url.toString());
    }

    function toggleSubmitting(isSubmitting) {
        $form.find('button[type="submit"]').each(function () {
            const $button = $(this);
            if (!$button.data('original-html')) {
                $button.data('original-html', $button.html());
            }
            $button.prop('disabled', isSubmitting);
            $button.html(isSubmitting ? '<i class="fas fa-spinner fa-spin"></i> Saving...' : $button.data('original-html'));
        });
    }

    function appendLocalStorage(formData) {
        try {
            for (let i = 0; i < localStorage.length; i += 1) {
                const key = localStorage.key(i);
                formData.append(key, localStorage.getItem(key) || '');
            }
        } catch (error) {
            console.warn('Local storage could not be read.', error);
        }
    }

    function buildQuoteItemsPayload() {
        const items = [];

        getQuoteRows().each(function () {
            const $row = $(this);
            const context = extractRowContext($row);
            const selectedParameters = collectSelectedParameters($row);

            selectedParameters.forEach(function (parameter) {
                const parameterName = String(parameter.parameter_name || '').trim();
                if (parameterName === '') {
                    return;
                }

                items.push({
                    parameter_id: parameter.parameter_id || '',
                    standard_id: parameter.standard_id || context.standardId || '',
                    item_code: parameter.test_code || makeTestCode(parameter.standard_code || context.standardCode, parameter.standard_id || context.standardId),
                    item_description: parameterName,
                    item_method: parameter.method || '',
                    item_qty: context.quantity.toFixed(2),
                    item_unit_price: context.unitPrice.toFixed(2)
                });
            });
        });

        return items;
    }

    function validateBeforeSubmit(form) {
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            form.reportValidity();
            return null;
        }

        const payloadItems = buildQuoteItemsPayload();
        if (!payloadItems.length) {
            notify('Select at least one test in the quote rows before saving.', 'danger');
            return null;
        }

        return payloadItems;
    }

    function toggleActionButton($button, isBusy, busyText) {
        if (!$button.length) {
            return;
        }

        if ($button.is(':checkbox')) {
            $button.prop('disabled', isBusy);
            return;
        }

        if (!$button.data('original-html')) {
            $button.data('original-html', $button.html());
        }

        $button.prop('disabled', isBusy);
        $button.html(isBusy ? busyText : $button.data('original-html'));
    }

    function openPdfBlob(pdfUrl) {
        if (!pdfUrl) {
            notify('Save the quote before opening the PDF.', 'danger');
            return;
        }

        fetch(pdfUrl, {
            method: 'GET',
            credentials: 'same-origin'
        }).then(function (response) {
            const contentType = response.headers.get('content-type') || '';
            if (!response.ok || contentType.indexOf('application/json') !== -1) {
                return response.text().then(function (text) {
                    let message = 'Unable to open quotation PDF.';

                    if (text) {
                        try {
                            const payload = JSON.parse(text);
                            message = payload.message || message;
                        } catch (error) {
                            message = text;
                        }
                    }

                    throw new Error(message);
                });
            }

            return response.blob();
        }).then(function (blob) {
            const blobUrl = URL.createObjectURL(blob);
            const popup = window.open(blobUrl, '_blank', 'noopener');

            if (!popup) {
                URL.revokeObjectURL(blobUrl);
                throw new Error('Pop-up blocked while opening quotation PDF.');
            }

            popup.addEventListener('load', function () {
                setTimeout(function () {
                    URL.revokeObjectURL(blobUrl);
                }, 60000);
            });
        }).catch(function (error) {
            notify(error.message || 'Unable to open quotation PDF.', 'danger');
        });
    }

    function loadQuote(quoteId) {
        const numericId = Number(quoteId || 0);
        showInlineMessage(numericId > 0 ? 'Loading quotation...' : 'Loading a new quotation...', 'info');

        $.ajax({
            url: 'ajax/quoteDocumentAjax.php',
            method: 'GET',
            dataType: 'json',
            data: {
                action: 'bootstrap',
                quote_id: numericId
            }
        }).done(function (response) {
            if (!response || !response.success) {
                notify((response && response.message) || 'Unable to load quotation data.', 'danger');
                populateForm({}, [{}], '');
                return;
            }

            defaultTerms = String(response.defaultTerms || '');
            renderCompanyProfile(response.company || {});
            populateForm(response.formData || {}, response.items || [], response.customer_lookup || '');
            updatePrintLink(response.quote_id, response.pdf_url || buildPdfUrl(response.quote_id));
            syncHistory(response.quote_id || 0);
            showInlineMessage('', 'success');
        }).fail(function (xhr) {
            notify((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to load quotation data.', 'danger');
            populateForm({}, [{}], '');
        });
    }

    function saveQuote(form) {
        const payloadItems = validateBeforeSubmit(form);
        if (!payloadItems) {
            return;
        }

        toggleSubmitting(true);

        const formData = new FormData(form);
        formData.set('action', submitMode);
        formData.set('submit_action', submitMode);

        payloadItems.forEach(function (item) {
            formData.append('parameter_id[]', item.parameter_id);
            formData.append('standard_id[]', item.standard_id);
            formData.append('item_code[]', item.item_code);
            formData.append('item_description[]', item.item_description);
            formData.append('item_method[]', item.item_method);
            formData.append('item_qty[]', item.item_qty);
            formData.append('item_unit_price[]', item.item_unit_price);
        });

        appendLocalStorage(formData);

        $.ajax({
            url: 'ajax/quoteDocumentAjax.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).done(function (response) {
            let res = response;
            if (typeof response === 'string') {
                try {
                    res = JSON.parse(response);
                } catch (error) {
                    res = null;
                }
            }

            if (!res || !res.success) {
                notify((res && res.message) || 'Quote save failed.', 'danger');
                return;
            }

            populateForm(res.formData || {}, res.items || [], $('#customer_lookup').val());
            updatePrintLink(res.quote_id, res.pdf_url || buildPdfUrl(res.quote_id));
            syncHistory(res.quote_id);
            notify(res.message || 'Quote saved successfully.', 'success');

            if (submitMode === 'save_print') {
                openPdfBlob(res.pdf_url || buildPdfUrl(res.quote_id));
            }
        }).fail(function (xhr) {
            notify((xhr.responseJSON && xhr.responseJSON.message) || 'Quote save failed.', 'danger');
        }).always(function () {
            toggleSubmitting(false);
            recalculateTotals();
        });
    }

    function saveDefaultTerms() {
        const $checkbox = $('#save-default-terms');
        toggleActionButton($checkbox, true, '');

        $.ajax({
            url: 'ajax/quoteDocumentAjax.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'save_default_terms',
                terms_text: $('#terms_text').val() || ''
            }
        }).done(function (response) {
            if (!response || !response.success) {
                notify((response && response.message) || 'Unable to save default quote terms.', 'danger');
                return;
            }

            defaultTerms = String(response.default_terms || '');
            notify(response.message || 'Default quote terms saved successfully.', 'success');
        }).fail(function (xhr) {
            notify((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to save default quote terms.', 'danger');
        }).always(function () {
            toggleActionButton($checkbox, false, '');
            $checkbox.prop('checked', false);
        });
    }

    function searchCustomers(inputElement) {
        const query = inputElement.value.trim();
        closeAllDropdowns();
        $('#customer_id').val('');

        if (!query) {
            return;
        }

        $.ajax({
            url: 'ajax/searchQuoteCustomers.php',
            method: 'GET',
            dataType: 'json',
            data: { query: query }
        }).done(function (rows) {
            if (!Array.isArray(rows) || !rows.length) {
                return;
            }

            const dropdown = createDropdown(inputElement);
            rows.forEach(function (customer) {
                const item = document.createElement('div');
                item.className = 'dropdown-item';
                item.textContent = (customer.customer || '') + (customer.itemcode ? ' (' + customer.itemcode + ')' : '');
                item.addEventListener('click', function () {
                    $('#customer_lookup').val(customer.customer || '');
                    $('#customer_id').val(customer.itemcode || '');
                    $('#client_name').val(customer.customer || '');
                    $('#client_attention').val(customer.contact || '');
                    $('#client_city').val(customer.city || '');

                    const addressLines = [];
                    if (customer.company) {
                        addressLines.push(customer.company);
                    }
                    if (customer.postcode) {
                        addressLines.push(customer.postcode);
                    }
                    $('#client_address').val(addressLines.join('\n'));
                    closeAllDropdowns();
                });
                dropdown.appendChild(item);
            });
        });
    }

    function getSelectedParameterIds($row) {
        return collectSelectedParameters($row).map(function (parameter) {
            return String(parameter.parameter_id || '').trim();
        });
    }

    function fetchRowParameters($row) {
        const context = extractRowContext($row);
        const rowIndex = context.rowIndex;
        const selectedIds = getSelectedParameterIds($row);

        if (!context.standardId) {
            renderRowParameters(rowIndex, [], false);
            return;
        }

        getParamsMetaByIndex(rowIndex).text('Loading tests...');
        getParamsContainerByIndex(rowIndex).html('<div class="quote-page__picker-empty">Loading tests...</div>');
        getParamsRowByIndex(rowIndex).show();

        $.ajax({
            url: 'ajax/getParametersUnderMatrix.php',
            method: 'GET',
            dataType: 'json',
            data: {
                stdId: context.standardId,
                matrixid: context.matrixId || 0
            }
        }).done(function (rows) {
            const parameters = Array.isArray(rows) ? rows.map(function (row) {
                const parameterId = String(row.ParameterID || row.parameter_id || '').trim();
                return {
                    parameter_id: parameterId,
                    parameter_name: row.ParameterName || row.parameter_name || '',
                    method: row.Method || row.method || '',
                    standard_id: row.StandardID || context.standardId,
                    standard_name: row.StandardName || context.standardName,
                    standard_code: row.StandardCode || context.standardCode,
                    test_code: makeTestCode(row.StandardCode || context.standardCode, row.StandardID || context.standardId),
                    checked: selectedIds.length ? selectedIds.indexOf(parameterId) !== -1 : true
                };
            }) : [];

            renderRowParameters(rowIndex, parameters, true);
        }).fail(function () {
            getParamsMetaByIndex(rowIndex).text('Unable to load tests right now.');
            getParamsContainerByIndex(rowIndex).html('<div class="quote-page__picker-empty">Unable to load tests right now.</div>');
            getParamsRowByIndex(rowIndex).show();
            recalculateTotals();
        });
    }

    function searchStandards($row, inputElement) {
        const query = (inputElement.value || '').trim();
        closeAllDropdowns();

        if (!query) {
            $row.find('.quote-standard-id').val('');
            $row.find('.quote-standard-code').val('');
            renderRowParameters(Number($row.data('rowIndex') || 0), [], false);
            recalculateTotals();
            return;
        }

        $.ajax({
            url: 'ajax/searchstandards.php',
            method: 'GET',
            dataType: 'json',
            data: { query: query }
        }).done(function (rows) {
            if (!Array.isArray(rows) || !rows.length) {
                return;
            }

            const dropdown = createDropdown(inputElement);
            rows.forEach(function (standard) {
                const item = document.createElement('div');
                item.className = 'dropdown-item';
                item.textContent = standard.StandardName || '';
                item.addEventListener('click', function () {
                    const currentStandardId = String($row.find('.quote-standard-id').val() || '').trim();
                    const nextStandardId = String(standard.StandardID || '').trim();

                    $row.find('.quote-standard-name').val(standard.StandardName || '');
                    $row.find('.quote-standard-id').val(nextStandardId);
                    $row.find('.quote-standard-code').val(standard.StandardCode || '');

                    if (currentStandardId !== nextStandardId) {
                        $row.find('.quote-matrix-name').val('');
                        $row.find('.quote-matrix-id').val('');
                    }

                    closeAllDropdowns();
                    fetchRowParameters($row);
                });
                dropdown.appendChild(item);
            });
        }).fail(function () {
            notify('Unable to search standards right now.', 'danger');
        });
    }

    function searchMatrices($row, inputElement) {
        const query = (inputElement.value || '').trim();
        closeAllDropdowns();

        if (!query) {
            $row.find('.quote-matrix-id').val('');
            if ($row.find('.quote-standard-id').val()) {
                fetchRowParameters($row);
            } else {
                recalculateTotals();
            }
            return;
        }

        $.ajax({
            url: 'ajax/searchmatrixes.php',
            method: 'GET',
            dataType: 'json',
            data: { q: query }
        }).done(function (rows) {
            if (!Array.isArray(rows) || !rows.length) {
                return;
            }

            const dropdown = createDropdown(inputElement);
            rows.forEach(function (matrix) {
                const item = document.createElement('div');
                item.className = 'dropdown-item';
                item.textContent = matrix.FullPath || matrix.ParameterName || '';
                item.addEventListener('click', function () {
                    $row.find('.quote-matrix-name').val(matrix.FullPath || matrix.ParameterName || '');
                    $row.find('.quote-matrix-id').val(matrix.ParameterID || '');
                    closeAllDropdowns();

                    if ($row.find('.quote-standard-id').val()) {
                        fetchRowParameters($row);
                    } else {
                        notify('Pick a standard first, then the matrix will filter that row\'s tests.', 'info');
                    }
                });
                dropdown.appendChild(item);
            });
        }).fail(function () {
            notify('Unable to search matrix packages right now.', 'danger');
        });
    }

    $('#add-item-row').on('click', function () {
        appendQuoteRow({});
    });

    $('#new-quote').on('click', function () {
        loadQuote(0);
    });

    $('#items-table').on('click', '.remove-row', function () {
        const $rows = getQuoteRows();

        if ($rows.length === 1) {
            const $row = $(this).closest('tr.quote-main-row');
            const rowIndex = Number($row.data('rowIndex') || 0);

            $row.find('.quote-standard-name, .quote-matrix-name').val('');
            $row.find('.quote-standard-id, .quote-matrix-id, .quote-standard-code').val('');
            $row.find('.quote-samples').val('1');
            $row.find('.quote-price').val('0.00');
            $row.find('.quote-row-total').val('0.00');
            renderRowParameters(rowIndex, [], false);
            recalculateTotals();
            return;
        }

        const $row = $(this).closest('tr.quote-main-row');
        const rowIndex = Number($row.data('rowIndex') || 0);
        getParamsRowByIndex(rowIndex).remove();
        $row.remove();
        recalculateTotals();
    });

    $('#items-table').on('input', '.quote-samples, .quote-price', function () {
        const $row = $(this).closest('tr.quote-main-row');
        if ($(this).hasClass('quote-samples')) {
            $(this).val(formatSampleInput($(this).val()));
        } else {
            $(this).val(formatPriceInput($(this).val()));
        }
        updateRowTotal($row);
        recalculateTotals();
    });

    $('#items-table').on('change', '.quote-page__params-list input[type="checkbox"]', function () {
        recalculateTotals();
    });

    $('#items-table').on('input', '.quote-standard-name', function () {
        const $row = $(this).closest('tr.quote-main-row');
        const rowIndex = Number($row.data('rowIndex') || 0);

        $row.find('.quote-standard-id').val('');
        $row.find('.quote-standard-code').val('');

        window.clearTimeout(rowSearchTimers['standard_' + rowIndex]);
        rowSearchTimers['standard_' + rowIndex] = window.setTimeout(function () {
            searchStandards($row, $row.find('.quote-standard-name').get(0));
        }, 250);
    });

    $('#items-table').on('input', '.quote-matrix-name', function () {
        const $row = $(this).closest('tr.quote-main-row');
        const rowIndex = Number($row.data('rowIndex') || 0);

        $row.find('.quote-matrix-id').val('');

        window.clearTimeout(rowSearchTimers['matrix_' + rowIndex]);
        rowSearchTimers['matrix_' + rowIndex] = window.setTimeout(function () {
            searchMatrices($row, $row.find('.quote-matrix-name').get(0));
        }, 250);
    });

    $('#discount_percent').on('input', recalculateTotals);

    $('#customer_lookup').on('input', function () {
        const input = this;
        window.clearTimeout(customerSearchTimer);
        customerSearchTimer = window.setTimeout(function () {
            searchCustomers(input);
        }, 250);
    });

    $('#apply-default-terms').on('change', function () {
        if (!this.checked) {
            return;
        }

        $('#terms_text').val(defaultTerms);
        notify('Saved default terms applied to this quote.', 'success');
        this.checked = false;
    });

    $('#save-default-terms').on('change', function () {
        if (!this.checked) {
            return;
        }

        saveDefaultTerms();
    });

    $('#print-saved-quote').on('click', function (event) {
        event.preventDefault();
        openPdfBlob($(this).attr('data-pdf-url') || $(this).attr('href'));
    });

    $form.on('click', 'button[type="submit"]', function () {
        submitMode = $(this).data('submit-mode') || 'save_quote';
        $('#submit_action').val(submitMode);
    });

    $form.on('submit', function (event) {
        event.preventDefault();
        saveQuote(this);
    });

    $form.on('keydown', '#customer_lookup, .quote-standard-name, .quote-matrix-name', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
        }
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.quote-ajax-dropdown, #customer_lookup, .quote-standard-name, .quote-matrix-name').length) {
            closeAllDropdowns();
        }
    });

    loadQuote(getInitialQuoteId());
})(jQuery);
