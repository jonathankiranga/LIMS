// ----------------------------
// SALES QUOTATION (sampleregistration-style JS)
// ----------------------------

window.quoteAjax = 'Ajax/quote_data_ajax.php';
window.quoteStdCache = {};       // cat -> {bundle, tests} (AJAX-fetched price lists)
window.customerFlags = { vatinclusive: false, istaxed: false };
window.lastCustomerID = '';
window.quoteRowSeq = 0;

// fetch helper (same spirit as fetchTransactionData.js)
function quoteFetch(params) {
  return fetch(window.quoteAjax, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(params),
  }).then(function (res) {
    var ct = (res.headers.get('content-type') || '');
    if (res.status !== 200 || ct.indexOf('application/json') === -1) {
      return res.text().then(function (txt) {
        var preview = (txt || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 140);
        console.warn('[quote] non-JSON response HTTP', res.status, '-', preview);
        return { status: 'error', code: 'HTTP_' + res.status, message: 'quote endpoint responded with HTTP ' + res.status + ' instead of JSON.', raw: preview, url: window.quoteAjax };
      });
    }
    return res.json();
  }).catch(function (err) {
    console.error('Fetch Error:', err);
    return { status: 'error', code: 'FETCH', message: String(err), url: window.quoteAjax };
  });
}

function quoteMsg(text, kind) {
  kind = kind || 'info';
  var el = document.getElementById('quotemessages');
  if (!el) return;
  text = (text == null ? '' : String(text)).trim();
  if (text === '') {
    el.innerHTML = '';
    return;
  }
  if (kind === 'info') {
    el.innerHTML = '<div class="statusbar">' + text + '</div>';
    return;
  }
  el.innerHTML = '<div class="alert alert-' + (kind === 'warn' ? 'warning' : 'info') + '">' + text + '</div>';
}

function quoteLoadMsg(text, kind) {
  kind = kind || 'info';
  var el = document.getElementById('loadQuoteMsg');
  if (!el) return;
  el.innerHTML = '<div class="small mt-1 mb-0 ' + (kind === 'warn' ? 'text-danger' : 'text-success') + '">' + text + '</div>';
}

// Load banks then auto-select the first account into both selects when they are
// empty. Never blocks: if the currency has no bank accounts the selects stay blank.
function quoteLoadBanks() {
  if (typeof loadBanks !== 'function') return;
  var p;
  try { p = loadBanks(); } catch (e) { return; }
  if (p && typeof p.then === 'function') {
    p.then(function () {
      var sel = document.getElementById('Bank_Code');
      if (sel && !sel.value && sel.options && sel.options.length > 1) {
        sel.value = sel.options[1].value;
      }
    });
  }
}

// Close any open autocomplete when clicking outside its input/list
if (!window.__quoteAutocompleteDismissBound) {
  window.__quoteAutocompleteDismissBound = true;
  document.addEventListener('pointerdown', function (e) {
    var open = document.querySelector('.dropdown');
    if (!open) return;
    if (open.contains(e.target)) return;
    if (e.target === open._owner) return;
    open.remove();
  });
  document.addEventListener('focusout', function (e) {
    var open = document.querySelector('.dropdown');
    if (!open) return;
    if (e.relatedTarget && open.contains(e.relatedTarget)) return;
    setTimeout(function () {
      var still = document.querySelector('.dropdown');
      if (still) still.remove();
    }, 180);
  });
}

// ----------------------------
// PRICING (mirrors applyStandardPricing 80/20 rule client-side)
// ----------------------------

function isTsBundle(code) {
  return /^TS\d{4}$/.test(code || '');
}

function quoteTableData() {
  if (!window.quoteTable) return [];
  try { return window.quoteTable.getData() || []; } catch (e) { return []; }
}

// ----------------------------
// SERVER PRICING (single source of truth: Ajax action=reprice)
// The grid never computes prices or amounts itself. It sends its state,
// the server reprices, and the grid paints the response in ONE setData.
// scope=full    -> group rules may reset price/disc (toggle, qty, customer).
// scope=amounts -> sent price/disc preserved, amounts recomputed (manual edits).
// ----------------------------

window.quoteRepriceSeq = 0;
window.quoteRepriceTimer = null;

function quoteModesFromRows(override) {
  var m = {};
  quoteTableData().forEach(function (d) {
    if (d.stdGroup && !(d.stdGroup in m)) m[d.stdGroup] = quoteModeClean(d.pricingMode);
  });
  if (override) {
    if (override.map && typeof override.map === 'object') {
      Object.keys(override.map).forEach(function (k) { m[k] = quoteModeClean(override.map[k]); });
    } else if (override.group !== undefined) {
      m[override.group] = quoteModeClean(override.mode);
    }
  }
  return m;
}

function quoteReprice(scope, override) {
  if (!window.quoteTable) return;
  var data = quoteTableData();
  if (!data.length) return;
  if (scope !== 'amounts') scope = 'full';
  window.quoteRepriceSeq += 1;
  var mySeq = window.quoteRepriceSeq;
  var cid = quoteField('CustomerID');
  var payload = {
    action: 'reprice',
    scope: scope,
    customerid: cid,
    DiscountPercent: quoteField('DiscountPercent'),
    pricingmode: JSON.stringify(quoteModesFromRows(override)),
    lines: data.map(function (d) {
      return {
        id: d.id, code: d.code, name: d.name, units: d.units, ppu: d.ppu,
        qty: d.quantity, price: d.price, disc: d.disc, vat: d.vat, tat: d.tat,
        group: d.stdGroup, gname: d.stdName, bundle: !!d.isBundle
      };
    })
  };
  quoteFetch(payload).then(function (resp) {
    if (mySeq !== window.quoteRepriceSeq) return; // stale response: a newer request already won
    if (!resp || resp.status !== 'ok' || !resp.rows) {
      quoteMsg((resp && resp.message) ? resp.message : 'Reprice failed - kept current lines.', 'warn');
      return;
    }
    quoteStampRowSeq(resp.rows);
    try { window.quoteTable.setData(resp.rows); } catch (e) { /* ignore */ }
    quotePaintTotals(resp.totals);
  });
}

function quoteRepriceDebounced(scope) {
  if (window.quoteRepriceTimer) clearTimeout(window.quoteRepriceTimer);
  window.quoteRepriceTimer = setTimeout(function () {
    window.quoteRepriceTimer = null;
    quoteReprice(scope);
  }, 300);
}

// ----------------------------
// TOTALS (paint-only: rows always arrive priced from the server)
// ----------------------------

// Totals painter. Never writes to the table, so it can never trigger a render.
function quotePaintTotals(totals) {
  var tfoot = document.getElementById('totalsRow');
  if (!tfoot) return;
  var n, v, g;
  if (totals && isFinite(totals.net)) {
    n = totals.net; v = totals.vat; g = totals.gross;
  } else {
    n = 0; v = 0; g = 0;
    quoteTableData().forEach(function (d) {
      n += parseFloat(d.net) || 0;
      v += parseFloat(d.vatAmt) || 0;
      g += parseFloat(d.gross) || 0;
    });
  }
  var tn = tfoot.querySelector('.q-tnet'); if (tn) tn.textContent = n.toFixed(2);
  var tv = tfoot.querySelector('.q-tvat'); if (tv) tv.textContent = v.toFixed(2);
  var tg = tfoot.querySelector('.q-tgross'); if (tg) tg.textContent = g.toFixed(2);
}

// Kept for existing callers (Re-Calculate button): repaints stored totals.
function quoteRecalc() {
  quotePaintTotals();
}

// Stamp rowseq values onto a plain rows array before it enters the table
// (no table writes, no renders).
function quoteStampRowSeq(rows) {
  (rows || []).forEach(function (r, idx) {
    r.rowseq = window.quoteRowSeq + '-' + idx;
  });
  return rows;
}

// ----------------------------
// ROW HANDLING (mirrors addSampleRow / params-row)
// ----------------------------

// ----------------------------
// TABULATOR GRID (grouped by standard, collapsible)
// ----------------------------

window.quoteTable = null;
window.quoteRowId = 0;

// Run fn with all table rendering blocked; exactly one render happens on restore.
// This is what stops the grid resizing/reflowing step-by-step while data loads.
function quoteBulkUpdate(fn) {
  var blocked = false;
  if (window.quoteTable) {
    try { window.quoteTable.blockRedraw(); blocked = true; } catch (e) { blocked = false; }
  }
  try {
    fn();
  } finally {
    if (blocked) {
      try { window.quoteTable.restoreRedraw(); } catch (e) { /* ignore */ }
    }
  }
}

function quoteModeClean(m) {
  return (m === 'standard' || m === 'pertest') ? m : 'auto';
}

function quoteEsc(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function initQuoteTable() {
  var el = document.getElementById('quoteTabulator');
  if (!el) return;
  if (typeof Tabulator === 'undefined') {
    el.innerHTML = '<div class="alert alert-warning">Tabulator library failed to load.</div>';
    return;
  }
  if (window.quoteTable) return;
  window.quoteTable = new Tabulator(el, {
    data: [],
    layout: 'fitColumns',
    resizableColumns: true,
    height: 420,
    placeholder: 'No lines yet — use Add Sample Standard.',
    groupBy: 'stdGroup',
    groupStartOpen: true,
    groupToggleElement: 'arrow',
    groupHeader: function (value, count, data) {
      var name = (data && data[0] && data[0].stdName) || value || 'Lines';
      var gross = 0;
      (data || []).forEach(function (d) {
        gross += parseFloat(d.gross) || 0;
      });
      var mode = quoteModeClean(data && data[0] && data[0].pricingMode);
      var gkey = quoteEsc(value);
      return '<span style="display:inline-flex; flex-wrap:wrap; align-items:center; gap:4px 12px; max-width:100%;">' +
        '<span>' + quoteEsc(name) + ' (' + count + ')</span>' +
        '<span style="font-weight:400;">Gross: ' + gross.toFixed(2) + '</span>' +
        '<select class="form-control form-control-sm qgroup-mode" data-group="' + gkey + '" title="Pricing: Auto 80/20, Standard price, or Test price" ' +
        'style="display:inline-block; width:auto; padding:2px 6px; font-size:12px;">' +
        '<option value="auto"' + (mode === 'auto' ? ' selected' : '') + '>Auto 80/20</option>' +
        '<option value="standard"' + (mode === 'standard' ? ' selected' : '') + '>Standard price</option>' +
        '<option value="pertest"' + (mode === 'pertest' ? ' selected' : '') + '>Test price</option>' +
        '</select>' +
        '<button type="button" class="btn btn-danger btn-sm qgroup-remove" data-group="' +
        gkey + '"><i class="fas fa-trash"></i> Remove</button></span>';
    },
    rowFormatter: function (row) {
      var d = row.getData();
      if (d && d.isBundle) {
        var elm = row.getElement();
        if (elm) elm.classList.add('q-bundle');
      }
    },
    columns: [
      { title: 'Item Code / Description', field: 'label', headerSort: false, widthGrow: 3 },
      { title: 'Code', field: 'code', visible: false },
      { title: 'No Units', field: 'quantity', editor: 'number', editorParams: { min: 0, step: 1 }, hozAlign: 'center', width: 95 },
      { title: 'Unit Descrip', field: 'units', width: 105 },
      { title: 'Sales Price', field: 'price', editor: 'number', editorParams: { min: 0, step: 0.01 }, hozAlign: 'right', width: 115 },
      { title: 'Disc (%)', field: 'disc', editor: 'number', editorParams: { min: 0, max: 100, step: 0.01 }, hozAlign: 'right', width: 95 },
      { title: 'Net Amount', field: 'net', hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2 }, width: 115 },
      { title: 'VAT Amount', field: 'vatAmt', hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2 }, width: 115 },
      { title: 'Gross Amount', field: 'gross', hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2 }, width: 125 },
      { title: 'TAT (days)', field: 'tat', editor: 'number', editorParams: { min: 0, step: 1 }, hozAlign: 'center', width: 95 },
      {
        title: 'Action', field: '__act', headerSort: false, width: 80, hozAlign: 'center',
        formatter: function () { return '<button type="button" class="btn btn-outline-danger btn-sm qrow-del" title="Delete line"><i class="fas fa-trash"></i></button>'; },
        cellClick: function (e, cell) {
          if (e) { e.stopPropagation(); }
          var row = cell.getRow();
          try { row.delete(); } catch (err) { /* ignore */ }
          quotePaintTotals(); // deletion only affects totals, no reprice needed
        }
      }
    ]
  });
  window.quoteTable.on('cellEdited', function (cell) {
    // All pricing is server-side. Qty changes group composition (full rule);
    // price/disc edits preserve values (amounts only); TAT needs no reprice.
    var f = cell.getField();
    if (f === 'quantity') quoteRepriceDebounced('full');
    else if (f === 'price' || f === 'disc') quoteRepriceDebounced('amounts');
  });
}

function quoteBuildRow(cat, catName, data, isBundle, mode) {
  window.quoteRowId += 1;
  var code = data.code;
  var name = data.name;
  return {
    id: window.quoteRowId,
    stdGroup: cat || '',
    stdName: catName || cat || 'Loaded lines',
    isBundle: !!isBundle,
    pricingMode: quoteModeClean(mode || data.pricingMode),
    code: code,
    label: code + ' - ' + name,
    name: name,
    units: data.units || 'PCS',
    ppu: data.ppu || 1,
    quantity: (data.quantity === 0 || data.units0) ? 0 : (data.quantity == null ? 1 : data.quantity),
    price: data.price || 0,
    disc: data.disc || 0,
    vat: data.vat || 0,
    tat: data.tat || 0,
    net: data.net || 0,
    vatAmt: data.vatAmt || 0,
    gross: data.gross || 0,
    rowseq: ''
  };
}

function quoteAddRow(cat, catName, data, isBundle, mode) {
  if (!window.quoteTable) return null;
  var row = quoteBuildRow(cat, catName, data, isBundle, mode);
  try { window.quoteTable.addData([row]); } catch (e) { /* ignore */ }
  return row.id;
}

function quoteRemoveGroup(cat) {
  if (!window.quoteTable) return;
  quoteBulkUpdate(function () {
    try {
      window.quoteTable.getRows()
        .filter(function (r) { return String(r.getData().stdGroup) === String(cat); })
        .forEach(function (r) { try { r.delete(); } catch (e) { /* ignore */ } });
    } catch (e) { /* ignore */ }
  });
  quotePaintTotals(); // deletion only affects totals, no reprice needed
  if (!quoteTableData().length && !document.querySelector('#pendingStandards .sq-pending')) {
    addQuoteStandardRow();
  }
}

// Standards-only picker: one search box per pending standard above the grid.
// Picking a standard loads its bundle+tests into a collapsible Tabulator group.
function addQuoteStandardRow(cat, catName, fee, skipLoad) {
  if (cat) {
    if (quoteStandardAlreadyUsed(cat, null)) {
      quoteMsg('Standard already in this quote.', 'warn');
      return null;
    }
    if (!skipLoad) loadStandardIntoRow(cat, fee);
    return cat;
  }
  window.quoteRowSeq += 1;
  var pid = 'P' + window.quoteRowSeq;
  var wrap = document.getElementById('pendingStandards');
  if (!wrap) {
    quoteMsg('Standards picker unavailable.', 'warn');
    return null;
  }
  var div = document.createElement('div');
  div.className = 'sq-pending';
  div.id = 'pending_' + pid;
  div.innerHTML = '<input type="text" id="StandardName_' + pid + '" class="form-control form-control-sm" ' +
    'placeholder="Search a sample standard" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" style="flex:1 1 auto;" />' +
    '<input type="hidden" id="StandardID_' + pid + '" value="" />' +
    '<button type="button" class="btn btn-danger btn-sm" data-remove-pending="pending_' + pid + '"><i class="fa fa-trash"></i> Remove</button>';
  wrap.appendChild(div);
  var input = div.querySelector('input[type="text"]');
  if (input) {
    input.onkeyup = handleQuoteStandardInput;
    setTimeout(function () { input.focus(); }, 50);
  }
  return pid;
}

function quoteStandardAlreadyUsed(cat, exceptPid) {
  if (!cat) return false;
  var used = false;
  quoteTableData().forEach(function (d) {
    if (d.stdGroup === cat) used = true;
  });
  document.querySelectorAll('#pendingStandards input[id^="StandardID_"]').forEach(function (h) {
    var pid = h.id.replace('StandardID_', '');
    if (h.value === cat && String(pid) !== String(exceptPid)) used = true;
  });
  return used;
}

function attachQuoteDropdown(inputEl) {
  document.querySelectorAll('.dropdown').forEach(function (d) { d.remove(); });
  var rect = inputEl.getBoundingClientRect();
  var dropdown = document.createElement('div');
  dropdown.id = 'dropdown_' + inputEl.id;
  dropdown.classList.add('dropdown');
  dropdown._owner = inputEl;
  dropdown.style.width = 'max-content';
  dropdown.style.minWidth = inputEl.offsetWidth + 'px';
  dropdown.style.maxWidth = (window.innerWidth - Math.round(rect.left) - 10) + 'px';
  document.body.appendChild(dropdown);
  var scrollY = window.scrollY || document.documentElement.scrollTop;
  var scrollX = window.scrollX || document.documentElement.scrollLeft;
  dropdown.style.left = rect.left + scrollX + 'px';
  dropdown.style.top = rect.bottom + scrollY + 'px';
  dropdown.innerHTML = '';
  return dropdown;
}

// Fetch bundle+tests for a category and add them as a collapsible Tabulator group.
// `fee` (optional) overrides the price-list bundle fee.
function loadStandardIntoRow(cat, fee) {
  var customerid = document.getElementById('CustomerID') ? document.getElementById('CustomerID').value : '';
  var headerDisc = document.getElementById('DiscountPercent') ? document.getElementById('DiscountPercent').value : '';

  quoteFetch({ action: 'standard', cat: cat, customerid: customerid, DiscountPercent: headerDisc }).then(function (d) {
    if (!d || d.status !== 'ok') {
      quoteMsg(d && d.message ? d.message : 'Could not load standard.', 'warn');
      return;
    }
    window.quoteStdCache[cat] = { bundle: d.bundle, tests: d.tests };

    var bundle = d.bundle;
    if (fee !== undefined && fee !== null && fee !== '' ) {
      bundle = Object.assign({}, d.bundle, { price: parseFloat(fee) || 0 });
    }
    var catName = bundle.name || cat;
    // Rows arrive priced from the server (action=standard), so this is a
    // single addData paint with no follow-up recompute.
    var rows = [quoteBuildRow(cat, catName, bundle, true)];
    d.tests.forEach(function (t) {
      rows.push(quoteBuildRow(cat, catName, t, false));
    });
    quoteStampRowSeq(rows);
    if (window.quoteTable) {
      try { window.quoteTable.addData(rows); } catch (e) { /* ignore */ }
    }
    quotePaintTotals();
    if (d.tests.length === 0) {
      quoteMsg('Standard ' + d.cat + ' has no tests in the discount table.', 'warn');
    } else {
      quoteMsg('Standard added: ' + catName);
    }
  });
}

function clearQuoteRows() {
  if (window.quoteTable) {
    try { window.quoteTable.clearData(); } catch (e) { /* ignore */ }
  }
  var wrap = document.getElementById('pendingStandards');
  if (wrap) wrap.innerHTML = '';
  window.quoteRowSeq = 0;
  window.quoteRowId = 0;
  window.quoteStdCache = {};
  quotePaintTotals({ net: 0, vat: 0, gross: 0 });
}

// ----------------------------
// AUTOCOMPLETE: STANDARD (standards-only, pending picker above the grid)
// ----------------------------

async function handleQuoteStandardInput(event) {
  var inputEl = event.target;
  var query = inputEl.value;
  if (event.key === 'Enter') {
    event.preventDefault();
    var first = document.querySelector('.dropdown div');
    if (first) first.click();
    return;
  }
  if (event.key && event.key.length > 1 && event.key !== 'Backspace' && event.key !== 'Delete') {
    return;
  }
  var pid = (inputEl.id || '').split('_')[1] || '';
  var dropdown = attachQuoteDropdown(inputEl);

  if (!query.trim()) {
    dropdown.style.display = 'none';
    return;
  }

  try {
    var resp = await fetch(window.quoteAjax + '?action=search&q=' + encodeURIComponent(query));
    var data = await resp.json();
    var results = data && data.results ? data.results : [];
    dropdown.innerHTML = '';

    if (results.length) {
      dropdown.style.display = 'block';
      results.forEach(function (std) {
        var div = document.createElement('div');
        div.textContent = std.label + ' (' + std.value + ')';
        div.dataset.code = std.value;
        div.dataset.name = std.label;
        div.addEventListener('click', function () {
          var cat = this.dataset.code;
          if (quoteStandardAlreadyUsed(cat, pid)) {
            quoteMsg('Standard already in this quote.', 'warn');
            dropdown.remove();
            return;
          }
          var picker = document.getElementById('pending_' + pid);
          if (picker) picker.remove();
          dropdown.remove();
          loadStandardIntoRow(cat);
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

// ----------------------------
// CUSTOMER HELPERS
// ----------------------------

function refreshQuoteCustomer() {
  var cid = document.getElementById('CustomerID') ? document.getElementById('CustomerID').value : '';
  if (!cid || cid === window.lastCustomerID) return;
  window.lastCustomerID = cid;
  quoteFetch({ action: 'customer', customerid: cid }).then(function (d) {
    if (d && d.status === 'ok') {
      window.customerFlags = { vatinclusive: !!d.vatinclusive, istaxed: !!d.istaxed };
      if (d.currency && document.getElementById('currencycode')) {
        document.getElementById('currencycode').value = d.currency;
      }
      quoteLoadBanks();
      quoteReprice('full'); // VAT flags may have changed: server reprices, one paint (skips when grid empty)
    } else if (d && d.code === 'NO_SESSION') {
      quoteMsg('Session expired - reload the quotation page and log in again.', 'warn');
    } else if (d && d.status === 'error') {
      quoteMsg(d.message || 'Customer lookup failed.', 'warn');
    }
  });
}

function applyPickedCustomer(c, dropdown) {
  var cid = document.getElementById('CustomerID');
  var cnm = document.getElementById('CustomerName');
  if (cid) cid.value = c.value;
  if (cnm) {
    cnm.value = c.label;
    cnm.dataset.lastPicked = c.label;
  }
  if (c.currency && document.getElementById('currencycode')) {
    document.getElementById('currencycode').value = c.currency;
  }
  if (c.salesman) {
    var sp = document.getElementById('salespersoncode');
    if (sp) {
      for (var i = 0; i < sp.options.length; i++) {
        if ((sp.options[i].value || sp.options[i].text) === c.salesman) { sp.selectedIndex = i; break; }
      }
    }
  }
  if (dropdown) dropdown.remove();
  window.lastCustomerID = '';
  refreshQuoteCustomer();
}

async function handleCustomerNameInput(event) {
  var query = event.target.value;
  var inputEl = event.target;
  var cid = document.getElementById('CustomerID');
  if (cid && query !== (inputEl.dataset.lastPicked || '')) {
    cid.value = '';
    window.lastCustomerID = '';
  }

  var dropdown = attachQuoteDropdown(inputEl);
  if (!query.trim()) {
    dropdown.style.display = 'none';
    return;
  }

  try {
    var resp = await fetch(window.quoteAjax + '?action=customers&q=' + encodeURIComponent(query));
    var data = await resp.json();
    var results = data && data.results ? data.results : [];
    dropdown.innerHTML = '';
    if (results.length) {
      dropdown.style.display = 'block';
      results.forEach(function (c) {
        var div = document.createElement('div');
        div.textContent = c.label + ' (' + c.value + ')';
        div.addEventListener('click', function () {
          applyPickedCustomer(c, dropdown);
        });
        dropdown.appendChild(div);
      });
    } else {
      dropdown.style.display = 'none';
    }
  } catch (err) {
    console.error('Error fetching customers:', err);
  }
}

// ----------------------------
// LOAD QUOTE
// ----------------------------

async function handleLoadQuoteInput(event) {
  var inputEl = event.target;
  var query = inputEl.value;
  if (event.key === 'Enter') {
    event.preventDefault();
    var open = document.querySelector('.dropdown');
    if (open) open.remove();
    loadQuote();
    return;
  }
  if (event.key && event.key !== 'Backspace' && event.key.length > 1 && event.key !== 'Delete') {
    return;
  }
  var dropdown = attachQuoteDropdown(inputEl);
  if (!query.trim()) {
    dropdown.style.display = 'none';
    return;
  }
  try {
    var resp = await fetch(window.quoteAjax + '?action=search_quotes&q=' + encodeURIComponent(query));
    var data = await resp.json();
    var results = data && data.results ? data.results : [];
    dropdown.innerHTML = '';
    if (results.length) {
      dropdown.style.display = 'block';
      results.forEach(function (r) {
        var div = document.createElement('div');
        div.textContent = r.label + ' (' + r.value + ')';
        div.dataset.code = r.value;
        div.addEventListener('click', function () {
          inputEl.value = this.dataset.code;
          dropdown.remove();
          loadQuote();
        });
        dropdown.appendChild(div);
      });
    } else {
      dropdown.style.display = 'none';
    }
  } catch (err) {
    console.error('Error fetching quotes:', err);
  }
}

function loadQuote() {
  document.querySelectorAll('.dropdown').forEach(function (d) { d.remove(); });
  var docno = document.getElementById('loadQuoteNo') ? document.getElementById('loadQuoteNo').value.trim() : '';
  if (docno === '') { quoteLoadMsg('Type a quote number to load.', 'warn'); return; }
  quoteFetch({ action: 'load_quote', documentno: docno }).then(function (d) {
    if (!d || d.status !== 'ok') { quoteLoadMsg(d && d.message ? d.message : 'Quote not found.', 'warn'); return; }
    var h = d.header;
    if (document.getElementById('date')) document.getElementById('date').value = h.date;
    if (document.getElementById('salesid')) document.getElementById('salesid').value = h.documentno;
    if (document.getElementById('CustomerID')) document.getElementById('CustomerID').value = h.customercode;
    if (document.getElementById('CustomerName')) document.getElementById('CustomerName').value = h.customername;
    if (document.getElementById('currencycode')) document.getElementById('currencycode').value = h.currencycode;
    if (document.getElementById('salespersoncode')) document.getElementById('salespersoncode').value = h.salespersoncode;
    if (document.getElementById('terms')) document.getElementById('terms').value = h.paymentterms;
    if (document.getElementById('DiscountPercent')) document.getElementById('DiscountPercent').value = h.DiscountPercent;
    if (document.getElementById('ParameterName')) document.getElementById('ParameterName').value = h.paymentterms;
    clearQuoteRows();

    // Pricing modes arrive with the AJAX header; stamp them onto each row so
    // every re-render restores the right toggle state from the data itself.
    var pmMap = {};
    if (h.pricingmode) {
      try {
        var pm = typeof h.pricingmode === 'string' ? JSON.parse(h.pricingmode) : h.pricingmode;
        if (pm && typeof pm === 'object') {
          Object.keys(pm).forEach(function (k) {
            pmMap[k] = quoteModeClean(pm[k]);
          });
        }
      } catch (e) { /* ignore malformed mode */ }
    }

    var rows = [];
    var loadingCat = null;
    var loadingName = null;
    d.lines.forEach(function (l) {
      if (isTsBundle(l.code)) {
        loadingCat = l.code;
        loadingName = l.description || l.code;
      } else if (!loadingCat) {
        loadingCat = '';
        loadingName = 'Loaded lines';
      }
      window.quoteRowId += 1;
      rows.push({
        id: window.quoteRowId,
        stdGroup: loadingCat || '',
        stdName: loadingName || 'Loaded lines',
        isBundle: isTsBundle(l.code),
        pricingMode: pmMap[loadingCat] || 'auto',
        code: l.code,
        label: l.code + ' - ' + (l.description || l.code),
        name: l.description || l.code,
        units: l.unitofmeasure || 'PCS',
        ppu: l.partsperunit || 1,
        quantity: l.quantity,
        price: l.price,
        disc: l.discountpercent,
        vat: l.vatrate,
        tat: l.tat,
        net: l.net || 0,
        vatAmt: l.vatAmt || 0,
        gross: l.gross || 0,
        rowseq: ''
      });
    });
    // Lines arrive priced from the server: one setData paint, no recompute.
    quoteStampRowSeq(rows);
    if (window.quoteTable) {
      try { window.quoteTable.setData(rows); } catch (e) { /* ignore */ }
    }
    quotePaintTotals();

    window.lastCustomerID = '';
    refreshQuoteCustomer();
    quoteLoadMsg('Quote loaded: ' + h.documentno);
  });
}

// ----------------------------
// SAVE / NEW QUOTE (AJAX only - no form submit)
// ----------------------------

function quoteField(id) {
  var el = document.getElementById(id);
  return el ? el.value : '';
}

function quoteGatherRows() {
  var arr = {};
  ['itemcode', 'stockname', 'units', 'partsperunit', 'quantity', 'pricevalue', 'LineDiscountPercent', 'tat', 'rowseq']
    .forEach(function (nm) { arr[nm] = []; });
  quoteTableData().forEach(function (d) {
    arr.itemcode.push(d.code || '');
    arr.stockname.push(d.name || '');
    arr.units.push(d.units || 'PCS');
    arr.partsperunit.push(d.ppu || 1);
    arr.quantity.push(d.quantity);
    arr.pricevalue.push(d.price);
    arr.LineDiscountPercent.push(d.disc);
    arr.tat.push(d.tat);
    arr.rowseq.push(d.rowseq || '');
  });
  return arr;
}

window.quoteSaving = false;

function saveQuote() {
  if (window.quoteSaving) return;
  var cid = quoteField('CustomerID');
  if (!cid) { quoteMsg('Select a customer before saving.', 'warn'); return; }
  if (!quoteTableData().length) {
    if (!window.confirm('No lines on this quote. Save anyway?')) return;
  }
  window.quoteSaving = true;
  var rows = quoteGatherRows();
  var payload = {
    action: 'save',
    documentno: quoteField('salesid'),
    date: quoteField('date'),
    CustomerID: cid,
    CustomerName: quoteField('CustomerName'),
    paymentterms: quoteField('ParameterName') || quoteField('terms'),
    terms: quoteField('terms'),
    currencycode: quoteField('currencycode'),
    salespersoncode: quoteField('salespersoncode'),
    Bank_Code: quoteField('Bank_Code'),
    Bank_Code2: quoteField('Bank_Code2'),
    DiscountPercent: quoteField('DiscountPercent'),
    pricingmode: (function () {
      var m = {};
      quoteTableData().forEach(function (d) {
        if (d.stdGroup && quoteModeClean(d.pricingMode) !== 'auto') m[d.stdGroup] = quoteModeClean(d.pricingMode);
      });
      return JSON.stringify(m);
    })(),
    selectedImages: quoteField('selectedImages')
  };
  Object.keys(rows).forEach(function (k) { payload[k + '[]'] = rows[k]; });

  quoteFetch(payload).then(function (d) {
    window.quoteSaving = false;
    if (!d || d.status !== 'saved') {
      quoteMsg((d && d.message) ? d.message : 'Save failed.', 'warn');
      if (d && d.code === 'NO_SESSION') quoteMsg('Session expired - reload the quotation page and log in again.', 'warn');
      return;
    }
    var el = document.getElementById('salesid');
    if (el) el.value = d.documentno;
    var url = d.print_url ? d.print_url : ('PDFPrintSalesQuote.php?No=' + encodeURIComponent(d.documentno));
    var box = document.getElementById('quotemessages');
    if (box) {
      var msg = d.message ? d.message : ('Sales Quote :' + d.documentno + ' has been created');
      var printLink = '<a class="btn btn-primary btn-sm" href="' + url + '" target="_blank"><i class="fa fa-print"></i> Print ' + d.documentno + '</a>';
      var emailLink = '<a class="btn btn-outline-primary btn-sm" href="' + url + '&emailto=1" target="_blank"><i class="fa fa-envelope"></i> Email ' + d.documentno + '</a>';
      box.innerHTML = '<div class="alert alert-success">' +
        '<h5 class="alert-heading">' + msg + '</h5>' +
        '<p>' + printLink + ' &nbsp; ' + emailLink + ' &nbsp; ' +
        '<button type="button" id="afterSaveNewBtn" class="btn btn-outline-secondary btn-sm">New Quote</button></p></div>';
      var nb = document.getElementById('afterSaveNewBtn');
      if (nb) nb.addEventListener('click', function () { quoteNew(); });
    }
  });
}

function quoteNew() {
  window.quoteSaving = false;
  clearQuoteRows();
  window.lastCustomerID = '';
  window.customerFlags = { vatinclusive: false, istaxed: false };
  ['salespersoncode', 'terms', 'loadQuoteNo', 'selectedImages']
    .forEach(function (id) { var e = document.getElementById(id); if (e) e.value = ''; });
  var disc = document.getElementById('DiscountPercent'); if (disc) disc.value = '';
  var pt = document.getElementById('ParameterName'); if (pt) pt.value = '';
  var lb = document.getElementById('loadQuoteMsg'); if (lb) lb.innerHTML = '';
  var msg = document.getElementById('quotemessages'); if (msg) msg.innerHTML = '';
  var dateEl = document.getElementById('date');
  if (dateEl) dateEl.value = quoteDateToday(dateEl.getAttribute('data-datefmt') || 'd/m/Y');
  addQuoteStandardRow();
  quoteFetch({ action: 'new' }).then(function (d) {
    var el = document.getElementById('salesid');
    if (el && d && d.status === 'ok') el.value = d.data;
    var cid = document.getElementById('CustomerID');
    if (cid && cid.value) refreshQuoteCustomer();
    else quoteLoadBanks();
  });
}

// ----------------------------
// INIT
// ----------------------------

function quoteDateToday(fmt) {
  var d = new Date();
  var mm = String(d.getMonth() + 1).padStart(2, '0');
  var dd = String(d.getDate()).padStart(2, '0');
  var yyyy = d.getFullYear();
  var ddmm = dd + '/' + mm + '/' + yyyy;
  var mmdd = mm + '/' + dd + '/' + yyyy;
  fmt = fmt || 'd/m/Y';
  var out = fmt.replace('d', dd).replace('m', mm).replace('Y', yyyy);
  if (fmt.indexOf('Y-m-d') === 0) return yyyy + '-' + mm + '-' + dd;
  if (fmt.indexOf('Y/m/d') === 0) return yyyy + '/' + mm + '/' + dd;
  if (fmt === 'm/d/Y') return mmdd;
  if (fmt === 'd/m/Y') return ddmm;
  return ddmm;
}

document.addEventListener('DOMContentLoaded', function () {
  var dateEl = document.getElementById('date');
  if (dateEl && !dateEl.value) {
    dateEl.value = quoteDateToday(dateEl.getAttribute('data-datefmt') || 'd/m/Y');
  }

  quoteFetch({ action: 'docno' }).then(function (d) {
    var el = document.getElementById('salesid');
    if (el && d && d.status === 'ok') el.value = d.data;
  });

  initQuoteTable();

  var addStdBtn = document.getElementById('addStandardBtn');
  if (addStdBtn) addStdBtn.addEventListener('click', function (e) {
    e.preventDefault();
    addQuoteStandardRow();
  });

  var pend = document.getElementById('pendingStandards');
  if (pend) pend.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-remove-pending]');
    if (!btn) return;
    var el = document.getElementById(btn.getAttribute('data-remove-pending'));
    if (el) el.remove();
  });

  var tab = document.getElementById('quoteTabulator');
  if (tab) tab.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.qgroup-remove');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    quoteRemoveGroup(btn.getAttribute('data-group'));
  });

  if (tab) tab.addEventListener('change', function (e) {
    var sel = e.target.closest && e.target.closest('.qgroup-mode');
    if (!sel) return;
    e.stopPropagation();
    // Server reprices with this group's new mode: one request, one paint,
    // no local rebuild for the interaction to race with.
    quoteReprice('full', { group: sel.getAttribute('data-group'), mode: quoteModeClean(sel.value) });
  });

  var loadBtn = document.getElementById('loadQuoteBtn');
  if (loadBtn) loadBtn.addEventListener('click', function (e) { e.preventDefault(); loadQuote(); });

  var saveBtn = document.getElementById('saveQuoteBtn');
  if (saveBtn) saveBtn.addEventListener('click', function (e) { e.preventDefault(); saveQuote(); });

  var newBtn = document.getElementById('newQuoteBtn');
  if (newBtn) newBtn.addEventListener('click', function (e) { e.preventDefault(); quoteNew(); });

  // global #searchcustomer (ConnectAjax) sets CustomerID/Name; detect the result
  document.addEventListener('click', function (e) {
    if (e.target.closest && e.target.closest('#findcustomer, .CustomerResults, #searchcustomer')) {
      setTimeout(refreshQuoteCustomer, 200);
    }
  });

  // Start with one standards-only picker when the grid is empty.
  if (!quoteTableData().length && !document.querySelector('#pendingStandards .sq-pending')) {
    addQuoteStandardRow();
  }

  // Hydrate a persisted customer from the session, or just load the default-currency banks.
  var cidEl = document.getElementById('CustomerID');
  if (cidEl && cidEl.value) refreshQuoteCustomer();
  else if (document.getElementById('currencycode')) quoteLoadBanks();
});