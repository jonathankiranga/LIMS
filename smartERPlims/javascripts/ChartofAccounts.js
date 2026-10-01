(function () {
  var cfg = COA_CONFIG || {};
  var coaData = cfg.data || [];
  var postingGroups = cfg.postingGroups || [];
  var bsLabels = cfg.balanceSheetLabels || {};
  var atLabels = cfg.accountTypeLabels || {};
  var rootPath = cfg.rootPath || '';
  var isNew = false;

  var saveMsg = document.getElementById('coaSaveMsg');
  function showSave(msg, type) {
    saveMsg.className = 'coa-save-indicator ' + (type || 'ok');
    saveMsg.textContent = msg;
    setTimeout(function () { saveMsg.className = 'coa-save-indicator'; }, 2500);
  }

  function resolveLabel(val, labels) {
    for (var k in labels) {
      if (labels.hasOwnProperty(k) && labels[k] === val) return k;
    }
    return val;
  }

  // ── Inline edit handler ──
  function onCellEdited(cell) {
    var oldVal = cell.getOldValue();
    var newVal = cell.getValue();
    var f = cell.getField();
    if (f === 'balance_income') newVal = resolveLabel(newVal, bsLabels);
    if (f === 'ReportStyle') newVal = resolveLabel(newVal, atLabels);
    if (oldVal == newVal) return;
    var d = cell.getRow().getData();

    fetch(rootPath + '/api/update_coa_account.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=update_field&accno=' + encodeURIComponent(d.accno) +
        '&field=' + encodeURIComponent(f) +
        '&value=' + encodeURIComponent(newVal)
    })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        showSave('Saved', 'ok');
      } else {
        showSave(resp.message || 'Update failed', 'fail');
        cell.setValue(oldVal, true);
      }
    })
    .catch(function () {
      showSave('Network error', 'fail');
      cell.setValue(oldVal, true);
    });
  }

  // ── Custom formatters ──
  function bsFormatter(cell) { return bsLabels[cell.getValue()] || cell.getValue(); }
  function atFormatter(cell) { return atLabels[cell.getValue()] || cell.getValue(); }

  // ── Edit button ──
  function editBtnFormatter(cell) {
    var btn = document.createElement('button');
    btn.className = 'btn btn-xs btn-info';
    btn.textContent = 'Edit';
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      openModal(cell.getRow().getData(), false);
    });
    return btn;
  }

  // ── Tabulator ──
  var table = new Tabulator('#coaGrid', {
    data: coaData,
    index: 'accno',
    layout: 'fitDataFill',
    pagination: true,
    paginationSize: 50,
    paginationSizeSelector: [25, 50, 100, 250, 500],
    movableColumns: true,
    resizable: true,
    columns: [
      { title: 'Account Code', field: 'ReportCode', width: 120, editor: 'input', cellEdited: onCellEdited, headerSort: true },
      { title: 'Account Name', field: 'accdesc', width: 250, editor: 'input', cellEdited: onCellEdited, headerSort: true },
      { title: 'Balance/Income', field: 'balance_income', width: 140,
        editor: 'select', editorParams: { values: bsLabels },
        formatter: bsFormatter, cellEdited: onCellEdited },
      { title: 'Account Type', field: 'ReportStyle', width: 120,
        editor: 'select', editorParams: { values: atLabels },
        formatter: atFormatter, cellEdited: onCellEdited },
      { title: 'Formula', field: 'Calculation', width: 200, editor: 'input', cellEdited: onCellEdited },
      { title: 'Posting Group', field: 'postinggroup', width: 120, editor: 'input', cellEdited: onCellEdited },
      { title: '', width: 70, formatter: editBtnFormatter, hozAlign: 'center' }
    ],
    dataLoaded: function (data) {
      document.getElementById('coaRowCount').textContent = data.length + ' accounts';
    }
  });

  // ── Modal ──
  var modal = document.getElementById('coaModal');
  var modalTitle = document.getElementById('coaModalTitle');
  var modalForm = document.getElementById('coaModalForm');

  function openModal(rowData, creating) {
    isNew = creating;
    modalTitle.textContent = creating ? 'New Account' : 'Edit Account';
    document.getElementById('coaField_accno').value = rowData.accno || '';
    document.getElementById('coaField_ReportCode').value = rowData.ReportCode || '';
    document.getElementById('coaField_accdesc').value = rowData.accdesc || '';
    document.getElementById('coaField_balance_income').value = rowData.balance_income != null ? rowData.balance_income : '0';
    document.getElementById('coaField_ReportStyle').value = rowData.ReportStyle != null ? rowData.ReportStyle : '0';
    document.getElementById('coaField_direct').value = rowData.direct != null ? rowData.direct : '0';
    document.getElementById('coaField_inactive').value = rowData.inactive != null ? rowData.inactive : '0';
    document.getElementById('coaField_Sale_Purchase_Neither').value = rowData.Sale_Purchase_Neither != null ? rowData.Sale_Purchase_Neither : '';
    document.getElementById('coaField_postinggroup').value = rowData.postinggroup || '';
    document.getElementById('coaField_Calculation').value = rowData.Calculation || '';
    modal.className = 'coa-modal-overlay show';
  }

  function closeModal() { modal.className = 'coa-modal-overlay'; }

  // Add button
  document.getElementById('coaAddBtn').addEventListener('click', function () { openModal({}, true); });

  // Close handlers
  document.getElementById('coaModalClose').addEventListener('click', closeModal);
  document.getElementById('coaModalCancel').addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

  // Modal form submit
  modalForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(modalForm);
    fd.set('action', isNew ? 'create' : 'update_all');

    fetch(rootPath + '/api/update_coa_account.php', {
      method: 'POST',
      body: new URLSearchParams(fd)
    })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        showSave(isNew ? 'Account created' : 'Account updated', 'ok');
        closeModal();

        var updated = {
          accno: isNew ? resp.accno : fd.get('accno'),
          ReportCode: fd.get('ReportCode'),
          accdesc: fd.get('accdesc'),
          balance_income: fd.get('balance_income'),
          ReportStyle: fd.get('ReportStyle'),
          direct: fd.get('direct'),
          inactive: fd.get('inactive'),
          Sale_Purchase_Neither: fd.get('Sale_Purchase_Neither'),
          Calculation: fd.get('Calculation'),
          postinggroup: fd.get('postinggroup')
        };

        if (isNew) {
          table.addRow(updated, true);
          document.getElementById('coaRowCount').textContent = (table.getData().length) + ' accounts';
        } else {
          table.updateData([updated]);
        }
      } else {
        showSave(resp.message || (isNew ? 'Create failed' : 'Update failed'), 'fail');
      }
    })
    .catch(function (err) {
      showSave('Network error: ' + err.message, 'fail');
    });
  });

  // Escape key closes modal
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
  });
})();
