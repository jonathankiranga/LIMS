// ----------------------------
// DYNAMIC ROW HANDLING
// ----------------------------

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

// Caches and global state used to render parameter groups client-side
window.sampleParamsCache = {};       // keyed by row index -> array of parameter objects
window.masterSelectedParams = {};    // keyed by row index -> array of currently-selected parameter objects
window.labRefSequence = [];          // flat array of lab-ref strings across all rows/samples

$(document).on('input', 'input[name^="samples"]', function () {
  updateRegistrationSummary();
});

  // Add a new sample row (merged & extended)
function addSampleRow() {
    const rowIndex = document.querySelectorAll('#sampleRows tr.main-row').length;
    const irow = rowIndex + 1;
    document.getElementById('tablecount').value= irow;
                      
    //  gets the last rowindex
    const row = document.createElement('tr');
    row.classList.add('main-row');
    row.innerHTML = `
      <td>
        <input type="hidden" id="rowindex_${irow}" name="rowindex[]" value="${irow}" />
        <input type="hidden" id="MatrixID_${irow}" name="matrix_id[]" value="0" />
        <input type="hidden" id="StandardID_${irow}" name="standard_id[]" />
        <label class="small text-muted" for="StandardName_${irow}">Standard</label>
        <input type="text" id="StandardName_${irow}" name="StandardName[]" autocomplete="off" onkeyup="handleStandardInput(event)" class="form-control form-control-sm mb-2" />
        <label class="small text-muted" for="samples_${irow}">Number of Samples</label>
        <input type="number" id="samples_${irow}" name="samples[]" class="form-control form-control-sm" min="1" value="1" />
      </td>
      <td>
        <label class="small text-muted" for="standard_kit_units_${irow}">Standard Kit Units</label>
        <input type="text" placeholder="Enter SKU units" id="standard_kit_units_${irow}" name="standard_kit_units[]" class="form-control form-control-sm mb-2"/>
        <label class="small text-muted" for="product_batch_no_${irow}">Product Batch No</label>
        <input type="text" id="product_batch_no_${irow}" name="product_batch_no[]" class="form-control form-control-sm mb-2" />
        <label class="small text-muted" for="batch_size_${irow}">Batch Size</label>
        <input type="number" id="batch_size_${irow}" name="batch_size[]" class="form-control form-control-sm" />
      </td>
      <td>
        <label class="small text-muted" for="date_of_manufacture_${irow}">Date of Manufacture</label>
        <input type="date" id="date_of_manufacture_${irow}" name="date_of_manufacture[]" class="form-control form-control-sm mb-2" />
        <label class="small text-muted" for="date_of_expiry_${irow}">Date of Expiry</label>
        <input type="date" id="date_of_expiry_${irow}" name="date_of_expiry[]" class="form-control form-control-sm mb-2" />
        <label class="small text-muted" for="chilled_date_of_expiry_${irow}">Chilled Date of Expiry</label>
        <input type="date" id="chilled_date_of_expiry_${irow}" name="chilled_date_of_expiry[]" class="form-control form-control-sm mb-2" />
        <label class="small text-muted" for="frozen_date_of_expiry_${irow}">Frozen Date of Expiry</label>
        <input type="date" id="frozen_date_of_expiry_${irow}" name="frozen_date_of_expiry[]" class="form-control form-control-sm" />
      </td>
      <td>
        <label class="small text-muted" for="sample_source_${irow}">Sample Source</label>
        <input type="text" id="sample_source_${irow}" name="sample_source[]" class="form-control form-control-sm mb-2" onkeyup="handleSampleSourceInput(event)" />
        <label class="small text-muted" for="sample_name_${irow}">Sample Name</label>
        <input type="text" id="sample_name_${irow}" name="sample_name[]" class="form-control form-control-sm mb-2" />
        <label class="small text-muted" for="sample_method_${irow}">Sample Method</label>
        <input type="text" id="sample_method_${irow}" name="sample_method[]" class="form-control form-control-sm mb-2" />
        <label class="small text-muted" for="condition_of_sample_${irow}">Condition of Sample</label>
        <input type="text" id="condition_of_sample_${irow}" name="condition_of_sample[]" class="form-control form-control-sm" />
      </td>
      <td>
        <input type="file" id="sample_image_${irow}" accept="image/jpeg,image/gif" name="sample_images[]" class="form-control form-control-sm" />
      </td>
    `;

    document.getElementById('sampleRows').appendChild(row);

    const paramsRow = document.createElement('tr');
    paramsRow.classList.add('params-row');
    paramsRow.id = `params_row_${irow}`;
    paramsRow.style.display = '';
    paramsRow.innerHTML = `
      <td colspan="5">
        <div id="params_container_${irow}"></div>
      </td>
    `;
    document.getElementById('sampleRows').appendChild(paramsRow);

    // Smooth scroll container to bottom so user sees new row
    setTimeout(function () {
      let container = document.getElementById("sampleTableContainer");
      if (container) container.scrollTop = container.scrollHeight;
    }, 50);

    reindexSampleRows();        // ensure IDs are consistent
    updateRegistrationSummary();
  };

// Row removal handler
document.getElementById('sampleTable').addEventListener('click', function (e) {
  const btn = e.target.closest('.removeRow');
  if (!btn) return;

  const mainRows = document.querySelectorAll('#sampleRows tr.main-row');
  if (mainRows.length <= 1) {
    toastr.info('At least one sample row is required.');
    return;
  }

  

  const tr = btn.closest('tr.main-row');
  if (!tr) return;

  const rowIndexEl = tr.querySelector('input[name="rowindex[]"]');
  const rowId = rowIndexEl ? rowIndexEl.value : '';
  const paramsTr = rowId ? document.getElementById(`params_row_${rowId}`) : null;
  if (paramsTr) paramsTr.remove();
  tr.remove();

  reindexSampleRows();
  updateRegistrationSummary();
});

// ----------------------------
// PREVIEW BOX HANDLER
// ----------------------------

document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { html: true, placement: 'auto' });
    });
});

function showPreview(event, stdId) {
    const inputId = event.target.id;
    const hiddenFieldId = inputId.replace('Name', 'ID');
    const query = document.getElementById(hiddenFieldId).value;
    const triggerEl = event.target;
    // Set temporary tooltip content
    triggerEl.setAttribute('data-bs-original-title', '<em>Loading parameters...</em>');
    // Show tooltip immediately
    let tooltip = bootstrap.Tooltip.getInstance(triggerEl);
    if (!tooltip) {
        tooltip = new bootstrap.Tooltip(triggerEl, { html: true, placement: 'auto' });
    }
  
    // Fetch parameters
    fetch('ajax/getParametersUnderMatrix.php?stdId=' + encodeURIComponent(stdId) + '&matrixid=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            let content;
            if (!Array.isArray(data) || data.length === 0) {
                content = '<span class="text-muted">No parameters found.</span>';
            } else {
                const list = data.map(p => `• ${p.ParameterName}`).join('<br>');
                content = `<strong>Parameters:</strong><br>${list}`;
            }
        // Update tooltip content
            triggerEl.setAttribute('data-bs-original-title', content);
            tooltip.show(); // refresh
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

function fetchlabno(seed = null, callback) {
    let data = {
        action: 'GetTempBarcoderfNo',
        TransType: '19'
    };
    if (seed !== null) {
        data.seed = seed;
    }

    fetch('ajax/getrefferences.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            callback(data.data);   // run after we have it
        } else {
            console.error('Error:', data.message);
        }
    })
    .catch(err => console.error('Fetch Error:', err));
}
// Re-index row IDs and important attributes after add/remove
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
    
        // keep the preview handlers in sync
        const mat = tr.querySelector('input[name="MatrixName[]"]');
        if (mat) {
          mat.setAttribute('onmouseenter', `showPreview(event, document.getElementById('StandardID_${i}') ? document.getElementById('StandardID_${i}').value : '')`);
          mat.setAttribute('onmouseleave', 'hidePreview(event)');
        }

        // Update rowindex value
        const rowIndexInput = tr.querySelector('input[name="rowindex[]"]');
        if (rowIndexInput) rowIndexInput.value = i;
      } else if (tr.classList.contains('params-row') && currentMainRow) {
        tr.id = `params_row_${i}`;
        const container = tr.querySelector('div');
        if (container) {
              container.id = `params_container_${i}`;
            }
      }
    });
    document.getElementById('tablecount').value = i;
  }

  // Update registration summary: row count & total samples
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

      // Compute a client-side lab-ref sequence (preview) and render groups
      computeLabRefSequence(totalSamples, function(seq) {
        window.labRefSequence = seq || [];
        const out = document.getElementById('registrationsumary');
        if (out) {
          const from = seq && seq.length ? seq[0] : '';
          const to = seq && seq.length ? seq[seq.length - 1] : '';
          out.innerHTML = `<strong>Rows:</strong> ${totalRows} &nbsp; | &nbsp; <strong>Total samples:</strong> ${totalSamples} | &nbsp; LAB REF NO from :<strong>${from}</strong> to :<strong>${to}</strong>`;
        }
        renderAllParamGroups();
      });
        
      
      
  }

  // Keep the summary up-to-date when users change numeric fields
  document.getElementById('sampleRows').addEventListener('input', function (e) {
    if (e.target.matches('input[name="samples[]"], input[name="standard_kit_units[]"], input[name="batch_size[]"]')) {
      updateRegistrationSummary();
    }
  });

  // -- Helper functions placed at top-level (moved out of event handlers) --
  function computeLabRefSequence(totalSamples, callback) {
    if (!totalSamples || totalSamples <= 0) {
      callback([]);
      return;
    }
    fetchlabno(null, function(first) {
      try {
        const s = String(first || '');
        const m = s.match(/^(.*?)(\d+)$/);
        let prefix = '';
        let startNum = 1;
        let width = 0;
        if (m) {
          prefix = m[1] || '';
          const numStr = m[2] || '';
          width = numStr.length;
          startNum = parseInt(numStr, 10);
        } else {
          startNum = parseInt(s, 10) || 1;
          width = String(startNum).length;
        }
        const seq = [];
        for (let i = 0; i < totalSamples; i++) {
          seq.push(prefix + String(startNum + i).padStart(width, '0'));
        }
        callback(seq);
      } catch (err) {
        console.error('Error computing lab ref sequence', err);
        callback([]);
      }
    });
  }

    // Helper: return the numeric order index of a top-level row given its rowNum/id
    function getRowOrderIndexByRowNum(rn) {
      const rows = Array.from(document.querySelectorAll('#sampleRows tr.main-row'));
      for (let idx = 0; idx < rows.length; idx++) {
        const ridEl = rows[idx].querySelector('input[name="rowindex[]"]');
        const rid = ridEl ? String(ridEl.value) : String(idx + 1);
        if (String(rid) === String(rn) || String(idx + 1) === String(rn)) return idx;
      }
      return 0;
    }

  function renderAllParamGroups() {
    const rows = Array.from(document.querySelectorAll('#sampleRows tr.main-row'));
    let offset = 0;
    rows.forEach((tr, idx) => {
      const rowIndexInput = tr.querySelector('input[name="rowindex[]"]');
      const rowNumId = rowIndexInput ? rowIndexInput.value : String(idx + 1);
      const samplesEl = tr.querySelector('input[name="samples[]"]');
      const samplesCount = samplesEl ? parseInt(samplesEl.value, 10) || 1 : 1;
      const labRefsForRow = window.labRefSequence.slice(offset, offset + samplesCount);
      renderSampleGroupsForRow(rowNumId, labRefsForRow);
      offset += samplesCount;
    });
  }

  function renderSampleGroupsForRow(rowNum, labRefsForRow) {
      const paramsContainer = document.getElementById(`params_container_${rowNum}`);
      if (!paramsContainer) return;

      // hidden container for header inputs (one header per parameter per sample)
      let hiddenHeaders = document.getElementById(`sample_headers_inputs_${rowNum}`);
      if (!hiddenHeaders) {
        hiddenHeaders = document.createElement('div');
        hiddenHeaders.id = `sample_headers_inputs_${rowNum}`;
        hiddenHeaders.style.display = 'none';
        paramsContainer.appendChild(hiddenHeaders);
      } else {
        hiddenHeaders.innerHTML = '';
      }

      const existingGroups = document.getElementById(`sample_groups_container_${rowNum}`);
      if (existingGroups) existingGroups.remove();
      const groupsContainer = document.createElement('div');
      groupsContainer.id = `sample_groups_container_${rowNum}`;
      groupsContainer.className = 'sample-groups';

      

      // Prefer parameters stored on the container (survives reindexing), fallback to window cache
      let paramsForRow = [];
      try {
        const raw = paramsContainer.dataset.params;
        paramsForRow = raw ? JSON.parse(raw) : (window.sampleParamsCache[rowNum] || []);
      } catch (e) {
        paramsForRow = window.sampleParamsCache[rowNum] || [];
      }

      // master selection for this row (used for checked defaults)
      let master = [];
      try {
        const rawMaster = paramsContainer.dataset.masterSelected;
        master = rawMaster ? JSON.parse(rawMaster) : (window.masterSelectedParams[rowNum] || []);
      } catch (e) {
        master = window.masterSelectedParams[rowNum] || [];
      }

      labRefsForRow = labRefsForRow && labRefsForRow.length ? labRefsForRow : [''];

      const orderIndex = getRowOrderIndexByRowNum(rowNum);

      // Render each individual sample with fewer columns by grouping related fields.
      // Keep every per-sample field and the existing sample_headers submission names.
      labRefsForRow.forEach((labRef, grpIdx) => {
        const headerIndex = grpIdx;
        const tbl = document.createElement('table');
        tbl.className = 'table table-sm sample-group-table';
        tbl.style.width = '100%';
        tbl.style.tableLayout = 'fixed';

        const tblHead = document.createElement('thead');
        tblHead.innerHTML = '<tr><th style="width:115px;">Sample ID</th><th style="width:28%;">Sample Details</th><th style="width:24%;">Product / Batch</th><th style="width:30%;">Dates</th><th style="width:130px;">Picture</th></tr>';
        tbl.appendChild(tblHead);
        const tblBody = document.createElement('tbody');

        let mainTr = null;
        document.querySelectorAll('#sampleRows tr.main-row').forEach(function(tr) {
          const ridEl = tr.querySelector('input[name="rowindex[]"]');
          if (ridEl && String(ridEl.value) === String(rowNum)) mainTr = tr;
        });

        const standardCaption = document.createElement('caption');
        standardCaption.style.captionSide = 'top';
        standardCaption.style.textAlign = 'left';
        standardCaption.style.fontWeight = '600';
        standardCaption.style.padding = '4px 0';
        standardCaption.textContent = 'Sample Standard: ' + (mainTr ? ((mainTr.querySelector('[name="StandardName[]"]') || {}).value || '') : '');
        tbl.insertBefore(standardCaption, tblHead);

        const mainRow = document.createElement('tr');
        mainRow.className = 'group-main-row';
        mainRow.dataset.groupIndex = headerIndex;
        mainRow.dataset.rowNum = rowNum;

        const getMainValue = function(selector) {
          return mainTr ? ((mainTr.querySelector(selector) || {}).value || '') : '';
        };
        const makeField = function(labelText, input) {
          const wrapper = document.createElement('label');
          wrapper.style.display = 'flex';
          wrapper.style.flexDirection = 'column';
          wrapper.style.minWidth = '0';
          wrapper.style.gap = '2px';
          wrapper.style.margin = '0';
          const label = document.createElement('span');
          label.textContent = labelText;
          label.style.fontSize = '0.72rem';
          label.style.color = '#6c757d';
          wrapper.appendChild(label);
          wrapper.appendChild(input);
          return wrapper;
        };
        const makeInput = function(type, className, placeholder, value) {
          const input = document.createElement('input');
          input.type = type;
          input.className = 'form-control form-control-sm ' + className;
          if (placeholder) input.placeholder = placeholder;
          if (value !== undefined && value !== null) input.value = value;
          input.style.minWidth = '0';
          input.style.width = '100%';
          return input;
        };
        const makeBlock = function() {
          const block = document.createElement('div');
          block.style.display = 'grid';
          block.style.gridTemplateColumns = 'repeat(2, minmax(0, 1fr))';
          block.style.gap = '4px 6px';
          block.style.alignItems = 'end';
          return block;
        };

        const tdSampleId = document.createElement('td');
        tdSampleId.className = 'sample-id-cell';
        tdSampleId.style.fontWeight = '600';
        tdSampleId.textContent = labRef || '';
        mainRow.appendChild(tdSampleId);

        const tdDetails = document.createElement('td');
        const detailsBlock = makeBlock();
        const sampleNameInput = makeInput('text', 'header-sample-name', 'Sample name', getMainValue('[name="sample_name[]"]'));
        const sourceInput = makeInput('text', 'header-source', 'Sample source', getMainValue('[name="sample_source[]"]'));
        const sampleMethodInput = makeInput('text', 'header-sample-method', 'Sample method', getMainValue('[name="sample_method[]"]'));
        const conditionSelect = document.createElement('select');
        conditionSelect.className = 'form-control form-control-sm header-condition';
        conditionSelect.innerHTML = '<option value="">Condition</option><option value="Fresh">Fresh</option><option value="Chilled">Chilled</option><option value="Frozen">Frozen</option><option value="Dried">Dried</option><option value="Preserved">Preserved</option>';
        conditionSelect.value = getMainValue('[name="condition_of_sample[]"]');
        detailsBlock.appendChild(makeField('Sample Name', sampleNameInput));
        detailsBlock.appendChild(makeField('Sample Source', sourceInput));
        detailsBlock.appendChild(makeField('Sample Method', sampleMethodInput));
        detailsBlock.appendChild(makeField('Condition', conditionSelect));
        tdDetails.appendChild(detailsBlock);
        mainRow.appendChild(tdDetails);

        const tdBatchDetails = document.createElement('td');
        const batchBlock = makeBlock();
        const skuInput = makeInput('text', 'header-sku', 'SKU units', getMainValue('[name="standard_kit_units[]"]'));
        const batchNoInput = makeInput('text', 'header-batch', 'Batch number', getMainValue('[name="product_batch_no[]"]'));
        const batchSizeInput = makeInput('number', 'header-size', 'Batch size', getMainValue('[name="batch_size[]"]'));
        batchSizeInput.min = '1';
        batchBlock.appendChild(makeField('Standard Kit Units', skuInput));
        batchBlock.appendChild(makeField('Product Batch No', batchNoInput));
        batchBlock.appendChild(makeField('Batch Size', batchSizeInput));
        tdBatchDetails.appendChild(batchBlock);
        mainRow.appendChild(tdBatchDetails);

        const tdDates = document.createElement('td');
        const datesBlock = makeBlock();
        const mfgInput = makeInput('date', 'header-mfg', '', getMainValue('[name="date_of_manufacture[]"]'));
        const expInput = makeInput('date', 'header-exp', '', getMainValue('[name="date_of_expiry[]"]'));
        const chilledExpInput = makeInput('date', 'header-chilled-exp', '', getMainValue('[name="chilled_date_of_expiry[]"]'));
        const frozenExpInput = makeInput('date', 'header-frozen-exp', '', getMainValue('[name="frozen_date_of_expiry[]"]'));
        datesBlock.appendChild(makeField('Date of Manufacture', mfgInput));
        datesBlock.appendChild(makeField('Date of Expiry', expInput));
        datesBlock.appendChild(makeField('Chilled Date Expiry', chilledExpInput));
        datesBlock.appendChild(makeField('Frozen Date Expiry', frozenExpInput));
        tdDates.appendChild(datesBlock);
        mainRow.appendChild(tdDates);

        const tdPic = document.createElement('td');
        const picInput = document.createElement('input');
        picInput.type = 'file';
        picInput.accept = 'image/jpeg,image/gif';
        picInput.className = 'form-control-file header-image';
        picInput.style.maxWidth = '100%';
        tdPic.appendChild(picInput);
        mainRow.appendChild(tdPic);
        tblBody.appendChild(mainRow);

        // Selected quotation parameters display in compact side-by-side blocks.
        const paramRow = document.createElement('tr');
        paramRow.id = 'group_param_row_' + rowNum + '_' + headerIndex;
        paramRow.className = 'group-param-row';
        const paramTd = document.createElement('td');
        paramTd.colSpan = 5;
        const paramHeading = document.createElement('div');
        paramHeading.textContent = 'Selected Parameters';
        paramHeading.style.fontSize = '0.75rem';
        paramHeading.style.fontWeight = '600';
        paramHeading.style.marginBottom = '4px';
        const paramBlocks = document.createElement('div');
        paramBlocks.className = 'test-list';
        paramBlocks.style.display = 'flex';
        paramBlocks.style.flexWrap = 'wrap';
        paramBlocks.style.gap = '4px';
        paramBlocks.style.margin = '0';

        const selectedParameterIds = new Set(master.map(function(p) {
          return String(p.ParameterID || p.parameterId || '');
        }));
        paramsForRow.forEach(function(p) {
          const pid = p.ParameterID || p.parameterId || '';
          if (!selectedParameterIds.has(String(pid))) return;
          const pname = p.ParameterName || p.Parameter || '';
          const block = document.createElement('span');
          block.textContent = pname;
          block.style.display = 'inline-block';
          block.style.padding = '3px 7px';
          block.style.border = '1px solid #dee2e6';
          block.style.borderRadius = '3px';
          block.style.background = '#f8f9fa';
          block.style.fontSize = '0.78rem';
          paramBlocks.appendChild(block);
        });
        if (!paramBlocks.children.length) {
          const empty = document.createElement('span');
          empty.className = 'text-muted';
          empty.textContent = 'No parameters selected.';
          paramBlocks.appendChild(empty);
        }
        paramTd.appendChild(paramHeading);
        paramTd.appendChild(paramBlocks);

        // Preserve the hidden payload structure expected by sampleregistrationAjax.php.
        const hiddenGroup = document.createElement('div');
        hiddenGroup.id = 'sample_header_group_' + rowNum + '_' + headerIndex;
        hiddenGroup.style.display = 'none';
        hiddenHeaders.appendChild(hiddenGroup);

        const base = 'sample_headers[' + orderIndex + '][' + headerIndex + ']';
        const addHidden = function(name, val, suffix) {
          const i = document.createElement('input');
          i.type = 'hidden';
          i.name = base + '[' + name + ']';
          i.value = val || '';
          i.id = 'sample_header_input_' + rowNum + '_' + headerIndex + '_' + (suffix || name);
          hiddenGroup.appendChild(i);
          return i;
        };

        addHidden('sku', skuInput.value, 'sku');
        addHidden('batchNo', batchNoInput.value, 'batch');
        addHidden('batchSize', batchSizeInput.value, 'size');
        addHidden('date_mfg', mfgInput.value, 'mfg');
        addHidden('date_exp', expInput.value, 'exp');
        addHidden('chilled_date_exp', chilledExpInput.value, 'chilled_exp');
        addHidden('frozen_date_exp', frozenExpInput.value, 'frozen_exp');
        addHidden('externalSample', sourceInput.value, 'src');
        addHidden('sample_name', sampleNameInput.value, 'sample_name');
        addHidden('sample_method', sampleMethodInput.value, 'sample_method');
        addHidden('condition_of_sample', conditionSelect.value, 'condition');
        addHidden('StandardID', (master[0] && master[0].StandardID) || '');
        addHidden('labref', labRef || '');

        master.forEach(function(p) {
          const parameterId = p.ParameterID || p.parameterId || '';
          if (!parameterId) return;
          const ip = document.createElement('input');
          ip.type = 'hidden';
          ip.name = base + '[parameters][]';
          ip.value = parameterId;
          hiddenGroup.appendChild(ip);
        });

        const syncHidden = function() {
          const sync = function(suffix, value) {
            const el = document.getElementById('sample_header_input_' + rowNum + '_' + headerIndex + '_' + suffix);
            if (el) el.value = value || '';
          };
          sync('sku', skuInput.value);
          sync('batch', batchNoInput.value);
          sync('size', batchSizeInput.value);
          sync('mfg', mfgInput.value);
          sync('exp', expInput.value);
          sync('chilled_exp', chilledExpInput.value);
          sync('frozen_exp', frozenExpInput.value);
          sync('src', sourceInput.value);
          sync('sample_name', sampleNameInput.value);
          sync('sample_method', sampleMethodInput.value);
          sync('condition', conditionSelect.value);
        };
        [skuInput, batchNoInput, batchSizeInput, mfgInput, expInput, chilledExpInput, frozenExpInput, sourceInput, sampleNameInput, sampleMethodInput, conditionSelect].forEach(function(input) {
          input.addEventListener('input', syncHidden);
          input.addEventListener('change', syncHidden);
        });

        paramRow.appendChild(paramTd);
        tblBody.appendChild(paramRow);
        tbl.appendChild(tblBody);
        groupsContainer.appendChild(tbl);
      });

      paramsContainer.appendChild(groupsContainer);

      // Parameter list is read-only; selected parameters are submitted via hidden inputs.
  }

  function refreshHiddenSelectedParamsInputs(rowNum) {
    // Rebuild the header-based hidden inputs `sample_headers[row][headerIndex][...]`
    const paramsContainer = document.getElementById(`params_container_${rowNum}`);
    if (!paramsContainer) return;
    let hiddenContainer = document.getElementById(`sample_headers_inputs_${rowNum}`);
    if (!hiddenContainer) {
      hiddenContainer = document.createElement('div');
      hiddenContainer.id = `sample_headers_inputs_${rowNum}`;
      hiddenContainer.style.display = 'none';
      paramsContainer.appendChild(hiddenContainer);
    }
    hiddenContainer.innerHTML = '';

    // compute labRefs for this row (to account for samples[] > 1)
    const rows = Array.from(document.querySelectorAll('#sampleRows tr.main-row'));
    let offset = 0;
    let targetIdx = -1;
    for (let idx = 0; idx < rows.length; idx++) {
      const ridEl = rows[idx].querySelector('input[name="rowindex[]"]');
      const rid = ridEl ? String(ridEl.value) : String(idx + 1);
      if (String(rid) === String(rowNum) || String(idx + 1) === String(rowNum)) {
        targetIdx = idx;
        break;
      }
      const samplesEl = rows[idx].querySelector('input[name="samples[]"]');
      const n = samplesEl ? parseInt(samplesEl.value, 10) || 0 : 0;
      offset += n;
    }
    if (targetIdx === -1) targetIdx = 0;
    const samplesElForTarget = rows[targetIdx] ? rows[targetIdx].querySelector('input[name="samples[]"]') : null;
    const samplesCountForTarget = samplesElForTarget ? parseInt(samplesElForTarget.value, 10) || 1 : 1;
    const labRefsForRow = (window.labRefSequence && window.labRefSequence.length) ? window.labRefSequence.slice(offset, offset + samplesCountForTarget) : [''];

    // master selection list
    let master = [];
    try {
      const rawMaster = paramsContainer.dataset.masterSelected;
      master = rawMaster ? JSON.parse(rawMaster) : (window.masterSelectedParams[rowNum] || []);
    } catch (e) {
      master = window.masterSelectedParams[rowNum] || [];
    }

    const orderIndex = getRowOrderIndexByRowNum(rowNum);

    // For each labRef create one group-level header hidden inputs; include all master parameters as selected by default
    labRefsForRow.forEach(function(_labRef, grpIdx) {
      const base = `sample_headers[${orderIndex}][${grpIdx}]`;
      const add = function(name, val, idSuffix) {
        const i = document.createElement('input'); i.type = 'hidden'; i.name = `${base}[${name}]`; i.value = val || '';
        i.id = `sample_header_input_${rowNum}_${grpIdx}_${idSuffix || name}`;
        hiddenContainer.appendChild(i);
      };
      add('sku', '');
      add('batchNo', '');
      add('batchSize', '');
      add('date_mfg', '');
      add('date_exp', '');
      add('externalSample', '');
      add('StandardID', (master[0] && master[0].StandardID) || '');

      // include all master parameters as parameters[] by default
      master.forEach(function(p) {
        const pm = p.ParameterID || p.parameterId || '';
        const ip = document.createElement('input'); ip.type = 'hidden'; ip.name = `${base}[parameters][]`; ip.value = pm || ''; hiddenContainer.appendChild(ip);
      });
    });
  }

// ----------------------------
// PRE-FILL: load and render a row's parameters under a standard (+ optional matrix)
// ----------------------------
async function prefillParams(rowNum, std, matrixId) {
  const paramsContainer = document.getElementById(`params_container_${rowNum}`);
  if (!paramsContainer) return;
  const mat = (matrixId !== null && matrixId !== undefined && matrixId !== '') ? matrixId : 0;
  try {
    const resp = await fetch(`ajax/getParametersUnderMatrix.php?stdId=${encodeURIComponent(std.StandardID)}&matrixid=${encodeURIComponent(mat)}`);
    const data = await resp.json();
    paramsContainer.innerHTML = '';
    if (!Array.isArray(data) || data.length === 0) {
      paramsContainer.innerHTML = '<p class="text-muted">No parameters available' + (mat ? ' under the selected matrix.' : '.') + '</p>';
      const paramsRow = document.getElementById(`params_row_${rowNum}`);
      if (paramsRow) paramsRow.style.display = '';
      return;
    }
    // Cache parameters for this row and create a master selection list
    window.sampleParamsCache[rowNum] = data;
    const masterList = data.map(p => ({
      ParameterID: p.ParameterID || p.parameterId || null,
      ParameterName: p.ParameterName || p.parameterName || '',
      StandardID: std.StandardID || null,
      BaseID: p.BaseID || p.BasePID || null
    }));
    window.masterSelectedParams[rowNum] = masterList;

    // Store cache on the params container DOM element so it survives reindexing
    try { paramsContainer.dataset.params = JSON.stringify(data); } catch (e) { /* ignore */ }
    try { paramsContainer.dataset.masterSelected = JSON.stringify(masterList); } catch (e) { /* ignore */ }

    // Hidden container for inputs that will be submitted as selected_params[row][k][...]
    let hiddenContainer = document.getElementById(`selected_params_inputs_${rowNum}`);
    if (!hiddenContainer) {
      hiddenContainer = document.createElement('div');
      hiddenContainer.id = `selected_params_inputs_${rowNum}`;
      hiddenContainer.style.display = 'none';
      paramsContainer.appendChild(hiddenContainer);
    }
    refreshHiddenSelectedParamsInputs(rowNum);

    // Render visible per-sample parameter groups for this row
    renderAllParamGroups();

    // Make the parameter grid visible (no silently hidden prefill)
    const paramsRow = document.getElementById(`params_row_${rowNum}`);
    if (paramsRow) paramsRow.style.display = '';
  } catch (err) {
    console.error('Error fetching parameters:', err);
    paramsContainer.innerHTML = '<p class="text-danger">Error loading parameters.</p>';
    const paramsRow = document.getElementById(`params_row_${rowNum}`);
    if (paramsRow) paramsRow.style.display = '';
  }
}

// Auto-select a standard by exact name (Enter / Tab / blur) so parameters
// prefill even when the suggestion dropdown was not clicked.
async function tryAutoSelectStandard(rowNum, inputEl, idEl, queryVal) {
  const q = (queryVal || '').trim();
  if (!q) return;
  try {
    const resp = await fetch('ajax/searchstandards.php?query=' + encodeURIComponent(q));
    const standards = await resp.json();
    const matches = (Array.isArray(standards) ? standards : []).filter(
      s => String(s.StandardName || '').trim().toLowerCase() === q.toLowerCase()
    );
    if (matches.length === 1) {
      const std = matches[0];
      if (idEl) idEl.value = std.StandardID;
      inputEl.value = std.StandardName;
      const dropdown = document.getElementById('dropdown_' + inputEl.id);
      if (dropdown) dropdown.remove();
      const matrixIdEl = document.getElementById(`MatrixID_${rowNum}`);
      const matrixInputEl = document.getElementById(`MatrixName_${rowNum}`);
      const mat = (matrixInputEl && matrixInputEl.value.trim() !== '' && matrixIdEl && matrixIdEl.value) ? matrixIdEl.value : 0;
      prefillParams(rowNum, std, mat);
    }
  } catch (err) {
    console.error('Error auto-selecting standard:', err);
  }
}

// ----------------------------
// AUTOCOMPLETE: MATRIX
// ----------------------------

async function handleMatrixInput(event) {
  const query = event.target.value;
  const inputId = event.target.id;
  const rowNum = inputId.split('_')[1];
  const dropdownId = 'dropdown_' + inputId;
  const hiddenFieldId = inputId.replace('Name', 'ID');
  const paramsRow = document.getElementById(`params_row_${rowNum}`);
  const existingDropdown = document.getElementById(dropdownId);

  if (!query.trim()) {
    if (paramsRow) paramsRow.style.display = '';
    document.getElementById(hiddenFieldId).value = '';
    if (existingDropdown) existingDropdown.remove();
    const stdIdEl = document.getElementById(`StandardID_${rowNum}`);
    const stdIdVal = stdIdEl ? stdIdEl.value : '';
    if (stdIdVal) prefillParams(rowNum, { StandardID: stdIdVal }, 0);
    return;
  } else {
    if (paramsRow) paramsRow.style.display = 'none';
  }

  // Remove previous dropdowns
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
          const stdIdEl = document.getElementById(`StandardID_${rowNum}`);
          const stdIdVal = stdIdEl ? stdIdEl.value : '';
          if (stdIdVal) {
            prefillParams(rowNum, { StandardID: stdIdVal }, mtx.ParameterID);
          } else {
            if (paramsRow) paramsRow.style.display = '';
          }
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
// ----------------------------
// AUTOCOMPLETE: STANDARD
// ----------------------------
async function handleStandardInput(event) {
  const query = event.target.value;
  const inputId = event.target.id;
  const dropdownId = 'dropdown_' + inputId;
  const hiddenFieldId = inputId.replace('Name', 'ID'); // StandardName → StandardID
  const rowNum = inputId.split('_')[1];

  // Clean up previous dropdowns
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
          const matrixIdEl = document.getElementById(`MatrixID_${rowNum}`);
          const matrixInputEl = document.getElementById(`MatrixName_${rowNum}`);
          const mat = (matrixInputEl && matrixInputEl.value.trim() !== '' && matrixIdEl && matrixIdEl.value) ? matrixIdEl.value : 0;
          prefillParams(rowNum, std, mat);
        });
        dropdown.appendChild(div);
      });
    } else {
      dropdown.style.display = 'none';
    }
  } catch (err) {
    console.error('Error fetching standards:', err);
  }

  // Keyboard Enter/Tab or blur with an exact name match: auto-select the
  // standard so parameters prefill even if the dropdown wasn't clicked.
  if (inputEl.dataset.stdSelectBound !== '1') {
    inputEl.dataset.stdSelectBound = '1';
    const selectExact = function () {
      const idEl = document.getElementById(hiddenFieldId);
      if (idEl && idEl.value) return;
      tryAutoSelectStandard(rowNum, inputEl, idEl, inputEl.value);
    };
    inputEl.addEventListener('keydown', function (ke) {
      if (ke.key === 'Enter' || ke.key === 'Tab') selectExact();
    });
    inputEl.addEventListener('blur', selectExact);
  }
}

function promptSendSampleReceivedEmail(onDecision) {
  if (typeof onDecision !== 'function') {
    return;
  }

  if (!document.getElementById('toastr-middle-center-style')) {
    const style = document.createElement('style');
    style.id = 'toastr-middle-center-style';
    style.textContent = `
      #toast-container.toast-middle-center {
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
      }
      #toast-container.toast-middle-center > div {
        margin: 0 0 8px 0;
      }
    `;
    document.head.appendChild(style);
  }

  const promptId = `send_email_prompt_${Date.now()}_${Math.floor(Math.random() * 10000)}`;
  const messageHtml = `
    <div id="${promptId}">
      <div>Send a "Sample Received" email to this customer after successful registration?</div>
      <div style="margin-top:8px; display:flex; gap:8px;">
        <button type="button" class="btn btn-sm btn-primary email-prompt-yes">Send Email</button>
        <button type="button" class="btn btn-sm btn-secondary email-prompt-no">Skip Email</button>
      </div>
    </div>
  `;

  let resolved = false;
  const toast = toastr.warning(messageHtml, '', {
    closeButton: true,
    tapToDismiss: false,
    timeOut: 0,
    extendedTimeOut: 0,
    preventDuplicates: true,
    positionClass: 'toast-middle-center',
    onHidden: function () {
      if (!resolved) {
        resolved = true;
        onDecision(false);
      }
    }
  });

  if (!toast || !toast.length) {
    onDecision(false);
    return;
  }

  const resolveOnce = function (choice) {
    if (resolved) return;
    resolved = true;
    toastr.clear(toast, { force: true });
    onDecision(choice);
  };

  toast.off('click.emailPrompt');
  toast.on('click.emailPrompt', '.email-prompt-yes', function (e) {
    e.preventDefault();
    e.stopPropagation();
    resolveOnce(true);
  });
  toast.on('click.emailPrompt', '.email-prompt-no', function (e) {
    e.preventDefault();
    e.stopPropagation();
    resolveOnce(false);
  });
}


$(document).ready(function() {
  // Search existing registrations for anonymous department receipt reprinting.
  $(document).off('click', '#searchReceiptsBtn').on('click', '#searchReceiptsBtn', function () {
    const query = ($('#receiptSearchQuery').val() || '').trim();
    const output = document.getElementById('receiptSearchResults');
    if (!query) {
      if (output) output.innerHTML = '<div class="alert alert-warning py-2">Enter a Batch Ref or Sample ID.</div>';
      return;
    }
    if (output) output.innerHTML = '<span class="text-muted">Searching registrations...</span>';
    fetch('ajax/searchSampleReceipts.php?q=' + encodeURIComponent(query), { credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!output) return;
        if (!data.success) {
          output.innerHTML = '<div class="alert alert-danger py-2">' + escapeReceiptText(data.message || 'Search failed.') + '</div>';
          return;
        }
        if (!data.registrations || !data.registrations.length) {
          output.innerHTML = '<div class="alert alert-info py-2">No matching registrations found.</div>';
          return;
        }
        output.innerHTML = data.registrations.map(function (item) {
          const chemicalUrl = 'ajax/printAnonymousSampleReceipt.php?header_id=' + encodeURIComponent(item.header_id) + '&department=chemical';
          const microUrl = 'ajax/printAnonymousSampleReceipt.php?header_id=' + encodeURIComponent(item.header_id) + '&department=microbiological';
          return '<div class="border rounded bg-white p-2 mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">' +
            '<div><strong>Batch Ref:</strong> ' + escapeReceiptText(item.document_no) +
            ' <span class="text-muted">| ' + escapeReceiptText(item.date || '') +
            ' | Samples: ' + Number(item.sample_count || 0) + '</span></div>' +
            '<div class="d-flex flex-wrap gap-2">' +
            '<a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="' + chemicalUrl + '">Chemical PDF</a>' +
            '<a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="' + microUrl + '">Microbiological PDF</a>' +
            '</div></div>';
        }).join('');
      })
      .catch(function (error) {
        console.error('Receipt search failed:', error);
        if (output) output.innerHTML = '<div class="alert alert-danger py-2">Could not search registrations. Please try again.</div>';
      });
  });
  $(document).off('keydown', '#receiptSearchQuery').on('keydown', '#receiptSearchQuery', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      $('#searchReceiptsBtn').trigger('click');
    }
  });

  function escapeReceiptText(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }

  const username = localStorage.getItem('username');
  if (!username) {
        localStorage.clear();
        localStorage.setItem('saygoodbye', 'proof');
        window.location.href = 'index.php?loutout=yes';
        return;
    }

	     $(document).off('submit','#labform').on('submit', '#labform', function (e) {
                    e.preventDefault();
                    const form = this;

                    // Run built-in form validation first (required, date, etc.)
                    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                      form.reportValidity();
                      return;
                    }

                    // A quotation must be linked so the sales order can be raised from it
                    const quoteNoOnSubmit = (document.getElementById('quoteno') || {}).value || '';
                    if (!quoteNoOnSubmit.trim()) {
                      toastr.error('Please create and link a quotation first before saving the sample.');
                      return;
                    }

                    // Validate sample rows first
                    if (!validateSampleRows()) {
                      return; // stop submission
                    }
                    
                    const continueSubmission = function(shouldSendSampleReceivedEmail) {
                    // Disable submit buttons and show spinner (restore original HTML after)
                    const $submitBtns = $(form).find('button[type="submit"]');
                    const originalHtmls = [];
                    $submitBtns.each(function () { originalHtmls.push($(this).html()); });
                    $submitBtns.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

                    // Build FormData from the form (will include the file inputs)
                    let formData = new FormData(form);

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

                        // if files are present
                        const fileInput = row.querySelector('[name="sample_images[]"]');
                        if (fileInput && fileInput.files.length) {
                            formData.append('sample_images[]', fileInput.files[0]);
                        }
                    });

                    for (let i = 0; i < localStorage.length; i++) {
                        const key = localStorage.key(i);
                        const value = localStorage.getItem(key);
                        formData.append(key, value);
                    }

                    // Backend checks this flag before triggering email event
                    formData.append('send_sample_received_email', shouldSendSampleReceivedEmail ? '1' : '0');

                    // AJAX submit
                    $.ajax({
                        url: 'ajax/sampleregistrationAjax.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            try {
                                const res = typeof response === 'object' ? response : JSON.parse(response);
                                if (res.success) {
                                    const baseMessage = res.message || 'Data successfully saved.';
                                    const statusMessageMap = {
                                      SUCCESS: ' Email sent to customer.',
                                      SKIPPED: ' Email skipped by your selection.',
                                      FAILED: ' Email was not sent.'
                                    };
                                    const emailStatus = (res.email_status || '').toUpperCase();
                                    const statusMessage = statusMessageMap[emailStatus] || '';
                                    toastr.success(baseMessage + statusMessage);
                                    if (res.email_note && emailStatus !== 'SUCCESS') {
                                      toastr.info(res.email_note);
                                    }
                                    if (res.receipts && res.receipts.chemical && res.receipts.microbiological) {
                                      const links = document.getElementById('newReceiptLinks');
                                      if (links) {
                                        links.innerHTML =
                                          '<div class="alert alert-success mb-2"><strong>Registration saved.</strong> Print or save the two anonymous department receipts:</div>' +
                                          '<div class="d-flex flex-wrap gap-2">' +
                                          '<a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="' + res.receipts.chemical + '">Chemical Department PDF</a>' +
                                          '<a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="' + res.receipts.microbiological + '">Microbiological Department PDF</a>' +
                                          '</div>';
                                        links.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                      }
                                    }
                                } else {
                                    toastr.error('Error: ' + (res.message || 'Unknown server error.'));
                                    $submitBtns.prop('disabled', false).each(function (i) { $(this).html(originalHtmls[i] || '<i class="fas fa-save"></i> Save'); });
                                }
                            } catch (err) {
                                toastr.error('Invalid server response. See console for details.');
                                console.error('Response parse error:', err, response);
                                $submitBtns.prop('disabled', false).each(function (i) { $(this).html(originalHtmls[i] || '<i class="fas fa-save"></i> Save'); });
                            }
                        },
                        error: function (xhr, status, error) {
                            toastr.error('An error occurred: ' + error + '\nResponse: ' + xhr.responseText);
                            $submitBtns.prop('disabled', false).each(function (i) { $(this).html(originalHtmls[i] || '<i class="fas fa-save"></i> Save'); });
                        },
                        complete: function () {
                            $submitBtns.prop('disabled', false).each(function (i) { $(this).html(originalHtmls[i] || '<i class="fas fa-save"></i> Save'); });
                            updateRegistrationSummary();
                        }
                    });
                };

                promptSendSampleReceivedEmail(continueSubmission);
       });  

     $(document).on('submit','#customerForm', function(e) {
                e.preventDefault(); // Prevent default form submission
         // Collect data from the form
                const customerData = $(this).serialize(); // Serialize form data
          // Example AJAX request to save the customer
                $.ajax({
                    url: 'ajax/saveCustomer.php', // Your server-side script to handle the save
                    type: 'POST',
                    data: customerData,
                    success: function(response) {
                        generalPurposeTypeLine('Customer added successfully!');
                        bootstrap.Modal.getOrCreateInstance($('#modal')[0]).hide(); // Hide the modal
                        // Optionally, refresh the customer list or perform other actions
                    },
                    error: function() {
                        toastr.error('Error adding customer. Please try again.');
                    }
                });
            });
            
            
             $.ajax({
             url: 'jsonfiles/Countriesarray.php', // Replace with your actual data source
             method: 'GET',
             dataType: 'json',
             success: function(data) {
                 // Assuming 'data' is an array of country names
                 var countrySelect = $('#countrySelect');
                 $.each(data, function(index, country) {
                     countrySelect.append($('<option></option>').attr('value', country).text(country));
                 });
             },
             error: function(xhr, status, error) {
                 toastr.error('Error fetching countries:'+ error.message);
             }
         });


 });    


async function handleCustomerNameInput(event) {
// Get the value of the input field
  const customerName = event.target.value;
  const inputId      = event.target.id;
  const inputElement = document.getElementById(inputId);
  const dropdownId   = 'dropdown_' + inputId;
  const existingDropdown = document.getElementById(dropdownId);
    if (existingDropdown) {
        existingDropdown.remove();
    }
        const existingDropdowns = document.querySelectorAll('.dropdown');
    existingDropdowns.forEach(dropdown => dropdown.remove());

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

     // Add it to the DOM

        const scrollY = window.scrollY || document.documentElement.scrollTop;
        const scrollX = window.scrollX || document.documentElement.scrollLeft;
        dropdown.style.left = rect.left + scrollX + 'px';
        dropdown.style.top = rect.bottom + scrollY + 'px';
 // You can now use customerName to perform further actions
console.log(customerName);
// Example: If you need to make an asynchronous call
try {
    // Example: Fetch data from an API
    let response = await fetch('ajax/searchcustomers.php?query=' + encodeURIComponent(customerName));
        const customers = await response.json();

     dropdown.innerHTML = '';

     if (customers.length > 0) {
        dropdown.style.display = 'block';

        customers.forEach(customer => {
            const div = document.createElement('div');
            div.textContent = customer.name;
            div.dataset.code = customer.code;
            div.dataset.name = customer.name;
            div.dataset.currency = customer.currency;
            div.dataset.salespersoncode= customer.salespersoncode;

            div.addEventListener('click', function() {
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

// ----------------------------
// AUTOCOMPLETE: QUOTATION (PRE-FILL FROM ERP)
// ----------------------------
function quoteDropdown() {
  const inputEl = document.getElementById('quoteno');
  const dropdownId = 'dropdown_quoteno';
  const existingDropdown = document.getElementById(dropdownId);
  if (existingDropdown) existingDropdown.remove();
  document.querySelectorAll('.dropdown').forEach(d => d.remove());

  const rect = inputEl.getBoundingClientRect();
  const dropdown = document.createElement('div');
  dropdown.id = dropdownId;
  dropdown.classList.add('dropdown');
  dropdown._owner = inputEl;
  dropdown.style.position = 'absolute';
  dropdown.style.backgroundColor = '#fff';
  dropdown.style.border = '1px solid #ccc';
  dropdown.style.width = 'max-content';
  dropdown.style.minWidth = inputEl.offsetWidth + 'px';
  dropdown.style.maxWidth = (window.innerWidth - Math.round(rect.left) - 10) + 'px';
  dropdown.style.zIndex = 1000;
  document.body.appendChild(dropdown);

  const scrollY = window.scrollY || document.documentElement.scrollTop;
  const scrollX = window.scrollX || document.documentElement.scrollLeft;
  dropdown.style.left = rect.left + scrollX + 'px';
  dropdown.style.top = rect.bottom + scrollY + 'px';
  return dropdown;
}

async function handleQuoteInput(event) {
  if (event.key && event.key === 'Escape') {
    const d = document.getElementById('dropdown_quoteno');
    if (d) d.remove();
    return;
  }
  searchQuotes(event.target.value);
}

async function searchQuotes(query) {
  const dropdown = quoteDropdown();
  dropdown.innerHTML = '';

  try {
    const resp = await fetch('ajax/searchquotes.php?query=' + encodeURIComponent(query || ''));
    const quotes = await resp.json();

    if (!Array.isArray(quotes) || quotes.length === 0) {
      dropdown.innerHTML = '<div style="padding:0.35rem 0.6rem;color:#888;">No quotations found.</div>';
      return;
    }

    dropdown.style.display = 'block';
    quotes.forEach(q => {
      const div = document.createElement('div');
      div.textContent = q.documentno + ' - ' + (q.customername || '') + ' (' + (q.docdate || '').slice(0, 10) + ')';
      div.dataset.documentno = q.documentno;
      div.addEventListener('click', () => {
        dropdown.style.display = 'none';
        applySelectedQuote(q.documentno);
      });
      dropdown.appendChild(div);
    });
  } catch (err) {
    console.error('Error fetching quotations:', err);
  }
}

function loadQuoteParams(rowNum, stdId, quotedBaseIds) {
  const paramsContainer = document.getElementById(`params_container_${rowNum}`);
  if (!paramsContainer) return Promise.resolve();

  const quotedIds = new Set(
    (Array.isArray(quotedBaseIds) ? quotedBaseIds : [])
      .map(id => String(id).trim())
      .filter(id => id !== '')
  );

  const baseIdsQuery = Array.from(quotedIds).join(',');
  return fetch(`ajax/getParametersUnderMatrix.php?stdId=${encodeURIComponent(stdId)}&matrixid=0&baseIds=${encodeURIComponent(baseIdsQuery)}`)
    .then(res => res.json())
    .then(data => {
      paramsContainer.innerHTML = '';

      if (!Array.isArray(data) || data.length === 0) {
        window.sampleParamsCache[rowNum] = [];
        window.masterSelectedParams[rowNum] = [];
        paramsContainer.dataset.params = '[]';
        paramsContainer.dataset.masterSelected = '[]';
        paramsContainer.innerHTML = '<p>No parameters available.</p>';
      } else {
        window.sampleParamsCache[rowNum] = data;

        const masterList = data.map(p => ({
          ParameterID: p.ParameterID || p.parameterId || null,
          ParameterName: p.ParameterName || '',
          StandardID: stdId || null,
          BaseID: p.BaseID || p.BasePID || null
        }));

        // A quotation identifies parameters by stockmaster.labid,
        // which is the same ID as baseparameters.ParameterID and therefore
        // matches testparameters.BaseID. Only those quoted BaseIDs are selected.
        const selectedList = masterList.filter(p =>
          p.BaseID !== null &&
          p.BaseID !== '' &&
          quotedIds.has(String(p.BaseID).trim())
        );

        window.masterSelectedParams[rowNum] = selectedList;

        try { paramsContainer.dataset.params = JSON.stringify(data); } catch (e) { /* ignore */ }
        try { paramsContainer.dataset.masterSelected = JSON.stringify(selectedList); } catch (e) { /* ignore */ }

        let hiddenContainer = document.getElementById(`selected_params_inputs_${rowNum}`);
        if (!hiddenContainer) {
          hiddenContainer = document.createElement('div');
          hiddenContainer.id = `selected_params_inputs_${rowNum}`;
          hiddenContainer.style.display = 'none';
          paramsContainer.appendChild(hiddenContainer);
        }

        refreshHiddenSelectedParamsInputs(rowNum);
        renderAllParamGroups();

        if (quotedIds.size === 0) {
          console.warn(
            'Quotation contains no parameter lines for StandardID ' + stdId +
            '. All parameters remain unchecked.'
          );
        } else if (selectedList.length === 0) {
          console.warn(
            'No quoted parameters matched testparameters.BaseID for StandardID ' + stdId +
            '. All parameters remain unchecked.'
          );
        }
      }

      // Keep the parameter section visible regardless of matrix selection.
      const paramsRow = document.getElementById(`params_row_${rowNum}`);
      if (paramsRow) paramsRow.style.display = '';
    })
    .catch(err => {
      console.error('Error fetching parameters for quote row:', err);
      throw err;
    });
}

async function addQuoteSampleRow(stdId, stdName, qty, units, quotedBaseIds) {
  addSampleRow();
  const rows = document.querySelectorAll('#sampleRows tr.main-row');
  const row = rows[rows.length - 1];
  const rowNum = row.querySelector('input[name="rowindex[]"]').value;

  document.getElementById(`StandardID_${rowNum}`).value = stdId;
  document.getElementById(`StandardName_${rowNum}`).value = stdName;

  const samplesEl = document.getElementById(`samples_${rowNum}`);
  if (samplesEl) samplesEl.value = qty > 0 ? qty : 1;

  const unitsEl = document.getElementById(`standard_kit_units_${rowNum}`);
  if (unitsEl) unitsEl.value = units;

  await loadQuoteParams(rowNum, stdId, quotedBaseIds);
}

async function applySelectedQuote(documentNo) {
  try {
    const resp = await fetch('ajax/getquote.php?documentno=' + encodeURIComponent(documentNo));
    const res = await resp.json();

    if (!res || res.success !== true) {
      toastr.error((res && res.message) || 'Quotation could not be loaded.');
      return;
    }

    const quote = res.quote || {};
    const lines = Array.isArray(res.lines) ? res.lines : [];

    const hasFilledRows = Array.prototype.some.call(
      document.querySelectorAll('#sampleRows tr.main-row'),
      tr => {
        const stdName = tr.querySelector('input[name="StandardName[]"]');
        const stdId = tr.querySelector('input[name="standard_id[]"]');
        return (stdName && stdName.value) || (stdId && stdId.value);
      }
    );

    if (hasFilledRows && !confirm(
      'Loading quotation ' + documentNo +
      ' will replace the current sample rows. Continue?'
    )) {
      return;
    }

    document.getElementById('CustomerID').value = quote.customercode || '';
    document.getElementById('CustomerName').value = quote.customername || '';
    document.getElementById('quoteno').value = documentNo;

    /*
     * Build the quotation parameter map first.
     *
     * ERP mapping:
     *   SalesLine.category = TS0076
     *       -> standard_id = 76
     *
     *   SalesLine.labid = 112
     *       -> baseparameters.ParameterID = 112
     *       -> testparameters.BaseID = 112
     *
     * Therefore we must NOT compare labid with testparameters.ParameterID.
     */
    const quotedByStandard = {};
    const bundleLines = [];
    const seenStandards = {};

    lines.forEach(l => {
      const code = String(l.code || '').trim();
      const category = String(l.category || '').trim();

      let stdId = parseInt(l.standard_id, 10) || 0;

      if (!stdId) {
        const categoryMatch = /^TS(\d{1,6})$/i.exec(category);
        if (categoryMatch) {
          stdId = parseInt(categoryMatch[1], 10);
        }
      }

      // Standard bundle line, e.g. TS0076.
      const bundleMatch = /^TS(\d{1,6})$/i.exec(code);
      if (bundleMatch) {
        const bundleStdId = parseInt(bundleMatch[1], 10);

        if (!seenStandards[bundleStdId]) {
          seenStandards[bundleStdId] = true;
          bundleLines.push({
            stdId: bundleStdId,
            stdName: l.description || '',
            qty: parseInt(l.Quantity || 1, 10),
            units: l.unitofmeasure || '',
            quotedBaseIds: []
          });
        }

        return;
      }

      // Parameter line: labid is the BaseParameterID.
      const baseId = parseInt(l.labid, 10) || 0;
      if (!stdId || !baseId) {
        return;
      }

      if (!quotedByStandard[stdId]) {
        quotedByStandard[stdId] = [];
      }

      if (!quotedByStandard[stdId].some(id => String(id) === String(baseId))) {
        quotedByStandard[stdId].push(baseId);
      }
    });

    // Attach the exact quoted BaseIDs to each standard row.
    bundleLines.forEach(bl => {
      bl.quotedBaseIds = quotedByStandard[bl.stdId] || [];
    });

    document.getElementById('sampleRows')
      .querySelectorAll('tr.main-row, tr.params-row')
      .forEach(tr => tr.remove());

    window.sampleParamsCache = {};
    window.masterSelectedParams = {};
    document.getElementById('tablecount').value = '';

    if (bundleLines.length === 0) {
      toastr.info(
        'No sample-standard bundle lines in this quotation. ' +
        'Customer and quotation were linked only.'
      );
      reindexSampleRows();
      updateRegistrationSummary();
      return;
    }

    // Wait for every standard's parameters to load before rendering the final state.
    await Promise.all(
      bundleLines.map(bl =>
        addQuoteSampleRow(
          bl.stdId,
          bl.stdName,
          bl.qty,
          bl.units,
          bl.quotedBaseIds
        )
      )
    );

    reindexSampleRows();
    updateRegistrationSummary();

    const totalQuotedParameters = bundleLines.reduce(
      (total, bl) => total + bl.quotedBaseIds.length,
      0
    );

    toastr.success(
      'Quotation ' + documentNo +
      ' linked. ' + totalQuotedParameters +
      ' quoted parameter(s) selected.'
    );
  } catch (err) {
    console.error('Error applying quotation:', err);
    toastr.error('Error loading quotation.');
  }
}

async function handlePricingInput(query,inputId) {
              console.log('inputId:', inputId);
           // Extract `labid` from `inputId`
            const rowid       = inputId.split('_'); // Expecting id format: PRC_<labid>
            const labid       = rowid[1]; // This will give the portion after the underscore
            const sampid      = document.getElementById('SAM_' + labid); // Fetch element with id SAM_<labid>
            const flexElement = document.getElementById("FLEX_"+labid);
            const sampidValue = sampid ? sampid.value : null; // Get its value, or null if not found
            const numberOfSamples = $('#numberofsamples').val(); // Assuming jQuery for this part
            //<input type="hidden" name="sampletype[LW005126]" id="SAM_LW005126" value="0111">
            try {
                 const data = {
                    sampleID: sampidValue, // Replace with actual value
                    Id: labid,      // Replace with actual value
                    PricingType: query , // Replace with actual value
                    numberOfSamples:numberOfSamples
                };

                console.log('pricings:', data);

                fetch('ajax/getLaboratoryStandardsAjax.php', {
                    method: 'POST', 
                    headers: {
                        'Content-Type': 'application/json', // Set content type to JSON
                    },
                    body: JSON.stringify(data) // Send data as JSON string
                })
                .then(response => response.text())  // Assuming the PHP script returns HTML content
                .then(result => {
                    // Handle the response, which will be the result from GetTests
                    console.log('flexElement:',result); // You can process the result here
                     if (flexElement) {
                        flexElement.innerHTML = ''; // Clear the content
                        flexElement.innerHTML = result; // Append the new content
                    } else {
                        console.error('Element with ID "FLEX_' + labid + '" not found.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });

            } catch (error) {
                console.error('Error fetching parameters:', error.message);
            }

}

function showtooltip(event){
    const customerName = event.target.value;
    if(customerName!='') generalPurposeTypeLine(customerName);
}

// Validate dynamic sample rows before submit
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
    const samples = row.querySelector('input[name="samples[]"]');
    const kit = row.querySelector('input[name="standard_kit_units[]"]');
    const productBatch = row.querySelector('input[name="product_batch_no[]"]');
    const batchSize = row.querySelector('input[name="batch_size[]"]');
    const dom = row.querySelector('input[name="date_of_manufacture[]"]');
    const doe = row.querySelector('input[name="date_of_expiry[]"]');

    // helper to mark invalid
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

    // required checks
    if (!std || !std.value.trim()) markInvalid(std); else markValid(std);
    if (!samples || !samples.value || parseInt(samples.value, 10) < 1) markInvalid(samples); else markValid(samples);
 //   if (!kit || !kit.value.trim()) markInvalid(kit); else markValid(kit);
 //   if (!productBatch || !productBatch.value.trim()) markInvalid(productBatch); else markValid(productBatch);
 //   if (!batchSize || !batchSize.value || parseInt(batchSize.value, 10) < 1) markInvalid(batchSize); else markValid(batchSize);

    // date logic: if both present, expiry must be > manufacture
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
      // require both DOM and DOE (if you prefer optional, remove these)
 //     if (!dom || !dom.value) markInvalid(dom); else markValid(dom);
 //     if (!doe || !doe.value) markInvalid(doe); else markValid(doe);
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

async function handleSampleSourceInput(event) {
  const query = event.target.value;
  const inputId = event.target.id;
  const dropdownId = 'dropdown_' + inputId;
  const rowNum = inputId.split('_')[1];

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

document.addEventListener("click", function(e) {
      if (e.target.tagName === "TD") {
          document.querySelectorAll("#sampleTable td").forEach(td => td.classList.remove("active"));
          e.target.classList.add("active");
      }
  });
