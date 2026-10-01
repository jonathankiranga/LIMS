// ----------------------------
// LABORATORY ORDER GRID (Tabulator, direct inline editing - no entry window)
// Cart lives in $_SESSION['sales_orders']; every edit syncs via AJAX so the
// classic Save/Delete/confirm POST flow (customerreadonly.inc) is untouched.
// All failures are loud: labMsg + console, never swallowed.
// ----------------------------

window.labAjax = 'Ajax/laborder_data_ajax.php';
window.labGrid = null;

function labFetch(params) {
  return fetch(window.labAjax, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(params)
  }).then(function (res) {
    var ct = (res.headers.get('content-type') || '');
    if (res.status !== 200 || ct.indexOf('application/json') === -1) {
      return res.text().then(function (txt) {
        var preview = (txt || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160);
        var err = { status: 'error', code: 'HTTP_' + res.status, message: 'Order endpoint responded with HTTP ' + res.status + ' instead of JSON: ' + preview, url: window.labAjax };
        console.error('[laborder]', err);
        return err;
      });
    }
    return res.json();
  }).catch(function (err) {
    var e = { status: 'error', code: 'FETCH', message: String(err && err.message ? err.message : err), url: window.labAjax };
    console.error('[laborder] Fetch Error:', err);
    return e;
  });
}

function labMsg(text, kind) {
  kind = kind || 'info';
  var el = document.getElementById('labmessages');
  if (!el) {
    console.error('[laborder]', text);
    return;
  }
  text = (text == null ? '' : String(text)).trim();
  if (text === '') {
    el.innerHTML = '';
    return;
  }
  if (kind === 'info') {
    el.innerHTML = '<div class="statusbar">' + text + '</div>';
    return;
  }
  el.innerHTML = '<div class="alert alert-' + (kind === 'warn' ? 'warning' : kind === 'error' ? 'danger' : 'info') + '">' + text + '</div>';
}

function labModeClean(m) {
  m = String(m == null ? '' : m).trim().toLowerCase();
  return (m === 'standard' || m === 'pertest') ? m : 'auto';
}

function labEscHtml(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function labField(id) {
  var el = document.getElementById(id);
  return el ? el.value : '';
}

function labPaintTotals(totals) {
  var bar = document.getElementById('labTotalsRow');
  if (!bar) return;
  var n = totals && isFinite(totals.net) ? totals.net : 0;
  var v = totals && isFinite(totals.vat) ? totals.vat : 0;
  var g = totals && isFinite(totals.gross) ? totals.gross : 0;
  var set = function (cls, val) {
    var s = bar.querySelector('.' + cls);
    if (s) s.textContent = val.toFixed(2);
  };
  set('l-tnet', n);
  set('l-tvat', v);
  set('l-tgross', g);
}

// Paint server rows in ONE setData (single render, no resize feedback).
// A paint failure is shown on screen, never swallowed: an invisible grid
// with a success message is exactly what this guard exists to prevent.
function labPaintRows(rows, totals) {
  if (window.labGrid) {
    try {
      window.labGrid.setData(rows || []);
    } catch (e) {
      console.error('[laborder] setData failed:', e);
      labMsg('Grid failed to paint ' + (rows || []).length + ' row(s): ' + (e && e.message ? e.message : e), 'error');
      return;
    }
  }
  labPaintTotals(totals);
}

function labRefreshCart() {
  return labFetch({ action: 'cart_list', CustomerID: labField('CustomerID'), DiscountPercent: labField('DiscountPercent') })
    .then(function (d) {
      if (!d || d.status !== 'ok') {
        labMsg((d && d.message) ? d.message : 'Could not load order lines.', 'warn');
        return;
      }
      labPaintRows(d.rows, d.totals);
    });
}

// Close any open lab autocomplete when clicking outside its input/list.
if (!window.__labAutocompleteDismissBound) {
  window.__labAutocompleteDismissBound = true;
  document.addEventListener('pointerdown', function (e) {
    var open = document.querySelector('.lab-dropdown');
    if (!open) return;
    if (open.contains(e.target)) return;
    if (e.target === open._owner) return;
    open.remove();
  });
}

function attachLabDropdown(inputEl) {
  document.querySelectorAll('.lab-dropdown').forEach(function (d) { d.remove(); });
  var rect = inputEl.getBoundingClientRect();
  var dropdown = document.createElement('div');
  dropdown.classList.add('lab-dropdown');
  dropdown._owner = inputEl;
  dropdown.style.minWidth = inputEl.offsetWidth + 'px';
  document.body.appendChild(dropdown);
  var scrollY = window.scrollY || document.documentElement.scrollTop;
  var scrollX = window.scrollX || document.documentElement.scrollLeft;
  dropdown.style.left = rect.left + scrollX + 'px';
  dropdown.style.top = rect.bottom + scrollY + 'px';
  dropdown.innerHTML = '';
  return dropdown;
}

function labSearchInto(inputEl, url, onPick) {
  var query = inputEl.value;
  var dropdown = attachLabDropdown(inputEl);
  if (!query.trim()) {
    dropdown.style.display = 'none';
    return;
  }
  fetch(url + encodeURIComponent(query))
    .then(function (resp) { return resp.json(); })
    .then(function (data) {
      var results = data && data.results ? data.results : [];
      dropdown.innerHTML = '';
      if (!results.length) {
        dropdown.style.display = 'none';
        return;
      }
      dropdown.style.display = 'block';
      results.forEach(function (r) {
        var div = document.createElement('div');
        div.textContent = r.label + ' (' + r.value + ')';
        div.addEventListener('click', function () {
          dropdown.remove();
          onPick(r);
        });
        dropdown.appendChild(div);
      });
    })
    .catch(function (err) {
      console.error('[laborder] search failed:', err);
      labMsg('Search failed. See the browser console for details.', 'warn');
    });
}

// Enter inside a load box loads immediately (keydown: keyup is too late to
// stop the native implicit submit, which would double-fire with AJAX).
function labLoadOnEnter(event, kind) {
  if (event.key === 'Enter') {
    event.preventDefault();
    var v = (event.target.value || '').trim();
    if (kind === 'quote') labLoadQuote(v);
    else labLoadOrder(v);
    return false;
  }
  return true;
}

function handleLabOrderSearch(event) {
  if (event.key && event.key.length > 1 && event.key !== 'Backspace' && event.key !== 'Delete') return;
  var inputEl = event.target;
  labSearchInto(inputEl, window.labAjax + '?action=search_orders&q=', function (r) {
    inputEl.value = r.value;
    labLoadOrder(r.value);
  });
}

function handleLabQuoteSearch(event) {
  if (event.key && event.key.length > 1 && event.key !== 'Backspace' && event.key !== 'Delete') return;
  var inputEl = event.target;
  labSearchInto(inputEl, window.labAjax + '?action=search_quotes&q=', function (r) {
    inputEl.value = r.value;
    labLoadQuote(r.value);
  });
}

function labSetField(id, val) {
  var el = document.getElementById(id);
  if (el && val !== undefined && val !== null) el.value = val;
}

function labRebuildBanks(banks, selected) {
  var sel = document.getElementById('Bank_Code');
  if (!sel) return;
  sel.innerHTML = '<option></option>';
  (banks || []).forEach(function (b) {
    var opt = document.createElement('option');
    opt.value = b.accountcode;
    opt.textContent = ((b.bankName || '').trim() + ' ' + (b.BranchName || '') + ' ' + (b.currency || '')).trim();
    if (b.accountcode === selected) opt.selected = true;
    sel.appendChild(opt);
  });
}

function labLoadOrder(docno) {
  if (!docno) { labMsg('Type an order number to load.', 'warn'); return; }
  labFetch({ action: 'load_order', documentno: docno }).then(function (d) {
    if (!d || d.status !== 'ok') {
      labMsg((d && d.message) ? d.message : 'Order not found.', 'warn');
      return;
    }
    var h = d.header || {};
    labSetField('salesid', h.documentno);
    labSetField('date', h.date);
    labSetField('CustomerID', h.customercode);
    labSetField('CustomerName', h.customername);
    var cnm = document.getElementById('CustomerName');
    if (cnm) cnm.dataset.lastPicked = h.customername || '';
    labSetField('reference', h.reference);
    labSetField('currencycode', h.currencycode);
    labSetField('salespersoncode', h.salespersoncode);
    labSetField('DiscountPercent', h.DiscountPercent);
    labSetField('coa_documentno', '');
    labRebuildBanks(d.banks, h.Bank_Code);
    labPaintRows(d.rows, d.totals);
    labMsg(d.message || ('Order loaded: ' + h.documentno));
  });
}

function labLoadQuote(docno) {
  if (!docno) { labMsg('Type a quote number to load.', 'warn'); return; }
  labFetch({ action: 'load_quote', documentno: docno }).then(function (d) {
    if (!d || d.status !== 'ok') {
      labMsg((d && d.message) ? d.message : 'Quote not found.', 'warn');
      return;
    }
    var h = d.header || {};
    labSetField('CustomerID', h.customercode);
    labSetField('CustomerName', h.customername);
    var cnm = document.getElementById('CustomerName');
    if (cnm) cnm.dataset.lastPicked = h.customername || '';
    labSetField('reference', h.reference);
    labSetField('currencycode', h.currencycode);
    labSetField('salespersoncode', h.salespersoncode);
    labSetField('DiscountPercent', h.DiscountPercent);
    labSetField('coa_documentno', '');
    labRebuildBanks(d.banks, h.Bank_Code);
    labPaintRows(d.rows, d.totals);
    labMsg(d.message || 'Quote loaded.');
  });
}

// Enter inside an AJAX search box must never trigger the native form submit
// (keyup is too late to stop it). Item box picks the first match.
function labItemEnter(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
    var first = document.querySelector('.lab-dropdown div');
    if (first) first.click();
    return false;
  }
  return true;
}

// Enter on the customer box picks the first match (or stays put) instead of
// submitting the form with an unpicked name.
function labCustomerEnter(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
    var first = document.querySelector('.lab-dropdown div');
    if (first) first.click();
    return false;
  }
  return true;
}

function handleLabCustomerSearch(event) {
  if (event.key && event.key.length > 1 && event.key !== 'Backspace' && event.key !== 'Delete' && event.key !== 'Enter') return;
  var inputEl = event.target;
  var cid = document.getElementById('CustomerID');
  if (cid && inputEl.value !== (inputEl.dataset.lastPicked || '')) {
    cid.value = '';
  }
  labSearchInto(inputEl, window.labAjax + '?action=customers&q=', function (c) {
    if (cid) cid.value = c.value;
    inputEl.value = c.label;
    inputEl.dataset.lastPicked = c.label;
    labFetch({ action: 'customer', customerid: c.value }).then(function (d) {
      if (d && d.status === 'ok') {
        if (d.currency) labSetField('currencycode', d.currency);
        labRebuildBanks(d.banks, labField('Bank_Code'));
      } else if (d) {
        labMsg(d.message || 'Customer lookup failed.', 'warn');
      }
      if (c.salesman) {
        var sp = document.getElementById('salespersoncode');
        if (sp) {
          for (var i = 0; i < sp.options.length; i++) {
            if ((sp.options[i].value || sp.options[i].text) === c.salesman) { sp.selectedIndex = i; break; }
          }
        }
      }
      labRefreshCart();
    });
  });
}

function handleLabItemSearch(event) {
  if (event.key && event.key.length > 1 && event.key !== 'Backspace' && event.key !== 'Delete') return;
  var inputEl = event.target;
  labSearchInto(inputEl, window.labAjax + '?action=search_items&q=', function (r) {
    // One box for both: a standard expands into its test group,
    // a plain item adds as a single loose line.
    var payload;
    if (r.type === 'standard') {
      payload = {
        action: 'add_standard',
        cat: r.value,
        CustomerID: labField('CustomerID'),
        DiscountPercent: labField('DiscountPercent')
      };
    } else {
      payload = {
        action: 'cart_add',
        itemcode: r.value,
        quantity: 1,
        CustomerID: labField('CustomerID'),
        DiscountPercent: labField('DiscountPercent')
      };
    }
    labFetch(payload).then(function (d) {
      if (!d || d.status !== 'ok') {
        labMsg((d && d.message) ? d.message : 'Could not add ' + r.value + '.', 'warn');
        return;
      }
      inputEl.value = '';
      labPaintRows(d.rows, d.totals);
      var msg = 'Added: ' + r.label;
      if (d.warnings && d.warnings.length) {
        msg += '<br>' + d.warnings.join('<br>');
      }
      labMsg(msg, (d.warnings && d.warnings.length) ? 'warn' : 'info');
    });
  });
}

function labGatherHeader() {
  return {
    documentno: labField('salesid'),
    date: labField('date'),
    CustomerID: labField('CustomerID'),
    CustomerName: labField('CustomerName'),
    reference: labField('reference'),
    currencycode: labField('currencycode'),
    salespersoncode: labField('salespersoncode'),
    Bank_Code: labField('Bank_Code'),
    DiscountPercent: labField('DiscountPercent'),
    coa_documentno: labField('coa_documentno'),
    selectedImages: labField('selectedImages')
  };
}

window.labSaving = false;

function labSaveOrder() {
  if (window.labSaving) return;
  var h = labGatherHeader();
  if (!h.CustomerID) { labMsg('Select a customer before saving.', 'warn'); return; }
  var rows = [];
  if (window.labGrid) {
    try { rows = window.labGrid.getData() || []; } catch (e) { console.error('[laborder] getData failed:', e); }
  }
  if (!rows.length && !window.confirm('No lines on this order. Save anyway?')) return;
  window.labSaving = true;
  var payload = { action: 'save' };
  Object.keys(h).forEach(function (k) { payload[k] = h[k]; });
  labFetch(payload).then(function (d) {
    window.labSaving = false;
    if (!d || (d.status !== 'saved' && d.status !== 'ok')) {
      labMsg((d && d.message) ? d.message : 'Save failed.', 'warn');
      return;
    }
    var docno = d.documentno;
    var salesid = document.getElementById('salesid');
    if (salesid) salesid.value = docno;
    var url = d.print_url ? d.print_url : ('PDFPrintSalesOrder.php?No=' + encodeURIComponent(docno));
    var box = document.getElementById('labmessages');
    var warns = '';
    if (d.warnings && d.warnings.length) {
      warns = '<ul>' + d.warnings.map(function (w) { return '<li>' + w + '</li>'; }).join('') + '</ul>';
    }
    if (box) {
      box.innerHTML = '<div class="alert alert-success">' +
        '<h5 class="alert-heading">' + (d.message ? d.message : ('Sales order :' + docno + ' has been created')) + '</h5>' +
        warns +
        '<p><a class="btn btn-primary btn-sm" href="' + url + '" target="_blank">Print ' + docno + '</a>' +
        ' &nbsp; <button type="button" id="labAfterConfirmBtn" class="btn btn-success btn-sm">Confirm Sales Order And Print</button>' +
        ' &nbsp; <button type="button" id="labAfterDeleteBtn" class="btn btn-danger btn-sm">Delete Order</button>' +
        ' &nbsp; <button type="button" id="labAfterNewBtn" class="btn btn-outline-secondary btn-sm">New Order</button></p></div>';
      var cb = document.getElementById('labAfterConfirmBtn');
      if (cb) cb.addEventListener('click', function () { labConfirmOrder(docno); });
      var db = document.getElementById('labAfterDeleteBtn');
      if (db) db.addEventListener('click', function () {
        if (window.confirm('Delete order ' + docno + '?')) labDeleteOrder(docno);
      });
      var nb = document.getElementById('labAfterNewBtn');
      if (nb) nb.addEventListener('click', function () { labNewOrder(); });
    }
    labPaintRows([], { net: 0, vat: 0, gross: 0 });
  });
}

function labConfirmOrder(docno) {
  docno = docno || labField('salesid');
  if (!docno) { labMsg('No order number to confirm.', 'warn'); return; }
  labFetch({ action: 'confirm', documentno: docno }).then(function (d) {
    if (!d || d.status !== 'ok') {
      labMsg((d && d.message) ? d.message : 'Confirm failed.', 'warn');
      return;
    }
    var url = d.print_url ? d.print_url : ('PDFPrintSalesOrder.php?No=' + encodeURIComponent(docno));
    labMsg((d.message || 'Confirmed.') + ' <a href="' + url + '" target="_blank">Print ' + docno + '</a>');
    var w = window.open(url, '_blank');
    if (!w) labMsg('Popup blocked - use the print link above.', 'warn');
  });
}

function labDeleteOrder(docno) {
  docno = docno || labField('salesid');
  if (!docno) { labMsg('No order number to delete.', 'warn'); return; }
  labFetch({ action: 'delete_order', documentno: docno }).then(function (d) {
    if (!d || d.status !== 'ok') {
      labMsg((d && d.message) ? d.message : 'Delete failed.', 'warn');
      return;
    }
    labMsg(d.message);
    labNewOrder();
  });
}

function labNewOrder() {
  labFetch({ action: 'new' }).then(function () {
    labFetch({ action: 'docno' }).then(function (d) {
      if (d && d.status === 'ok' && document.getElementById('salesid')) {
        document.getElementById('salesid').value = d.data;
      }
    });
    ['salespersoncode', 'CustomerName', 'loadOrderNo', 'loadQuoteNo', 'reference', 'coa_documentno']
      .forEach(function (id) { var e = document.getElementById(id); if (e) e.value = ''; });
    var cid = document.getElementById('CustomerID');
    if (cid) cid.value = '';
    var disc = document.getElementById('DiscountPercent');
    if (disc) disc.value = '';
    var msg = document.getElementById('labmessages');
    if (msg) msg.innerHTML = '';
    labPaintRows([], { net: 0, vat: 0, gross: 0 });
    labAddItemFocus();
  });
}

function labAddItemFocus() {
  var inp = document.getElementById('labItemSearch');
  if (inp) inp.focus();
}

function initLabGrid() {
  var el = document.getElementById('labOrderGrid');
  if (!el) return;
  if (typeof Tabulator === 'undefined') {
    el.innerHTML = '<div class="alert alert-warning">Tabulator library failed to load. Order lines unavailable.</div>';
    labMsg('Tabulator library failed to load.', 'error');
    return;
  }
  if (window.labGrid) return;
  window.labGrid = new Tabulator(el, {
    data: [],
    layout: 'fitColumns',
    resizableColumns: true,
    layoutColumnsOnNewData: false,
    height: 420,
    placeholder: 'No lines yet — search an item or standard above to add it.',
    groupBy: 'stdGroup',
    groupStartOpen: true,
    groupToggleElement: 'arrow',
    groupHeader: function (value, count, data) {
      // Ungrouped rows get NO header at all (marker hides the row via CSS):
      // their identity is already in each row, no invented label.
      if (!value) {
        return '<span class="lab-nogroup-marker"></span>';
      }
      var name = (data && data[0] && data[0].stdName) || value;
      var gross = 0;
      (data || []).forEach(function (d) { gross += parseFloat(d.gross) || 0; });
      var mode = labModeClean(data && data[0] && data[0].pricingMode);
      return '<span>' + labEscHtml(name) + ' (' + count + ')</span>' +
        ' <span style="font-weight:400;">Gross: ' + gross.toFixed(2) + '</span>' +
        ' <select class="form-control form-control-sm labgroup-mode" data-group="' + labEscHtml(value) + '"' +
        ' title="Pricing: Auto 80/20, Standard price, or Test price"' +
        ' style="display:inline-block; width:auto; padding:2px 6px; font-size:12px;">' +
        '<option value="auto"' + (mode === 'auto' ? ' selected' : '') + '>Auto 80/20</option>' +
        '<option value="standard"' + (mode === 'standard' ? ' selected' : '') + '>Standard price</option>' +
        '<option value="pertest"' + (mode === 'pertest' ? ' selected' : '') + '>Test price</option>' +
        '</select>' +
        ' <button type="button" class="btn btn-danger btn-sm labgroup-remove" data-group="' +
        labEscHtml(value) + '"><i class="fas fa-trash"></i> Remove</button>';
    },
    rowFormatter: function (row) {
      var d = row.getData();
      if (d && d.isBundle) {
        var elm = row.getElement();
        if (elm) elm.classList.add('lab-bundle');
      }
    },
    columns: [
      { title: 'Description', field: 'label', headerSort: false, width: 220, maxWidth: 300, tooltip: true },
      { title: 'Sample ID', field: 'sampleid', editor: 'input', width: 110 },
      { title: 'Units', field: 'quantity', editor: 'number', editorParams: { min: 0, step: 1 }, hozAlign: 'center', width: 85 },
      { title: 'Sales Price', field: 'price', editor: 'number', editorParams: { min: 0, step: 0.01 }, hozAlign: 'right', width: 110 },
      { title: 'Disc (%)', field: 'disc', editor: 'number', editorParams: { min: 0, max: 100, step: 0.01 }, hozAlign: 'right', width: 85 },
      { title: 'Net Amount', field: 'net', hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2 }, width: 110 },
      { title: 'VAT Amount', field: 'vatAmt', hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2 }, width: 110 },
      { title: 'Gross Amount', field: 'gross', hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2 }, width: 120 },
      { title: 'TAT', field: 'tat', editor: 'number', editorParams: { min: 0, step: 1 }, hozAlign: 'center', width: 80 },
      {
        title: 'Action', field: '__act', headerSort: false, width: 75, hozAlign: 'center',
        formatter: function () { return '<button type="button" class="btn btn-outline-danger btn-sm labrow-del" title="Delete line"><i class="fas fa-trash"></i></button>'; },
        cellClick: function (e, cell) {
          if (e) { e.stopPropagation(); }
          var id = cell.getRow().getData().id;
          labFetch({ action: 'cart_delete', id: id, CustomerID: labField('CustomerID'), DiscountPercent: labField('DiscountPercent') })
            .then(function (d) {
              if (!d || d.status !== 'ok') {
                labMsg((d && d.message) ? d.message : 'Could not delete the line.', 'warn');
                return;
              }
              labPaintRows(d.rows, d.totals);
            });
        }
      }
    ]
  });
  var fieldMap = { sampleid: 'sampleid', quantity: 'quantity', price: 'price', disc: 'disc', tat: 'tat' };
  window.labGrid.on('cellEdited', function (cell) {
    var f = fieldMap[cell.getField()];
    if (!f) return;
    var row = cell.getRow().getData();
    labFetch({
      action: 'cart_update', id: row.id, field: f, value: cell.getValue(),
      CustomerID: labField('CustomerID'), DiscountPercent: labField('DiscountPercent')
    }).then(function (d) {
      if (!d || d.status !== 'ok') {
        labMsg((d && d.message) ? d.message : 'Could not update the line.', 'warn');
        labRefreshCart();
        return;
      }
      labPaintRows(d.rows, d.totals);
    });
  });
  labRefreshCart();
}

document.addEventListener('DOMContentLoaded', function () {
  initLabGrid();

  var tab = document.getElementById('labOrderGrid');
  if (tab) tab.addEventListener('change', function (e) {
    var sel = e.target.closest && e.target.closest('.labgroup-mode');
    if (!sel) return;
    e.preventDefault();
    e.stopPropagation();
    // Server owns pricing: it recomputes this group and repaints in one pass.
    labFetch({
      action: 'set_pricing_mode',
      cat: sel.getAttribute('data-group'),
      mode: labModeClean(sel.value),
      CustomerID: labField('CustomerID'),
      DiscountPercent: labField('DiscountPercent')
    }).then(function (d) {
      if (!d || d.status !== 'ok') {
        labMsg((d && d.message) ? d.message : 'Could not change the pricing mode.', 'warn');
        return;
      }
      labPaintRows(d.rows, d.totals);
    });
  });

  if (tab) tab.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.labgroup-remove');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    var cat = btn.getAttribute('data-group');
    labFetch({ action: 'remove_group', cat: cat, CustomerID: labField('CustomerID'), DiscountPercent: labField('DiscountPercent') })
      .then(function (d) {
        if (!d || d.status !== 'ok') {
          labMsg((d && d.message) ? d.message : 'Could not remove the group.', 'warn');
          return;
        }
        labPaintRows(d.rows, d.totals);
      });
  });

  var addBtn = document.getElementById('labAddItemBtn');
  if (addBtn) addBtn.addEventListener('click', function (e) {
    e.preventDefault();
    var inp = document.getElementById('labItemSearch');
    if (inp) { inp.focus(); labMsg('Type to search stock, Enter picks the first match.'); }
  });

  var form = document.getElementById('salesform');
  if (form) form.addEventListener('submit', function (e) {
    var btn = '';
    if (e.submitter && e.submitter.value) {
      btn = e.submitter.value;
    } else if (document.activeElement && document.activeElement.value) {
      btn = document.activeElement.value;
    }
    if (btn === 'Save and Print Sales Order') {
      e.preventDefault();
      if (window.labGrid) {
        try {
          if (window.labGrid.getData().length === 0 && !window.confirm('No lines on this order. Save anyway?')) {
            return false;
          }
        } catch (err) { console.error('[laborder] save guard failed:', err); }
      }
      labSaveOrder();
      return false;
    }
    if (btn === 'Delete Order') {
      e.preventDefault();
      if (window.confirm('Delete this order?')) labDeleteOrder(labField('salesid'));
      return false;
    }
    // Re-Calculate and any other submit keep the classic POST flow
    // (category discounts + server re-render).
  });
});
