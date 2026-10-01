(function() {
  var currentPeriod = IS_CONFIG.currentPeriod;
  var currentPeriodLabel = IS_CONFIG.currentPeriodLabel;
  var currentUserId = IS_CONFIG.currentUserId;
  var rawData = null;
  var pendingData = null;
  var pivotInitialized = false;
  var pivotContainer = document.getElementById('is-pivot-container');
  var sheetContainer = document.getElementById('is-sheet');
  var statusEl = document.getElementById('is-status');
  var periodSelect = document.getElementById('is-period-select');
  var viewRadios = document.querySelectorAll('input[name="is-view"]');
  var spreadsheet = null;
  var invDataCache = {};
  var invData = null;
  var invCurrentMonth = '';
  var invMonthList = [];
  var invQuarterList = [];
  var pMeasure = 'qty';
  var pPeriod = 'current_month';
  var pCategory = 'all';
  var pStore = false;

  function setStatus(msg, type) {
    statusEl.textContent = msg;
    statusEl.className = 'is-status-' + (type || 'info');
  }

  function showError(msg) {
    setStatus(msg || 'Failed to load data. Click Refresh to try again.', 'error');
  }

  function formatNum(v) {
    if (v === null || v === undefined || isNaN(v)) return 0;
    return Math.round(v * 100) / 100;
  }

  function getSheetKey() {
    return '_INV_' + currentPeriod;
  }

  function toggleView(view) {
    if (view === 'pivot') {
      sheetContainer.style.display = 'none';
      pivotContainer.style.display = 'block';
      if (!pivotInitialized) initPivot();
    } else {
      sheetContainer.style.display = 'block';
      pivotContainer.style.display = 'none';
    }
  }

  viewRadios.forEach(function(r) {
    r.addEventListener('change', function() {
      if (this.checked) toggleView(this.value);
    });
  });

  function populateSheet(data) {
    if (!spreadsheet) return;
    var rows = data.map(function(item, ri) {
      return {
        num: ri + 1,
        itemcode: item.itemcode || '',
        description: item.description || '',
        category: item.category_name || '',
        store: item.store_name || item.store_code || '',
        unit: item.unit_name || item.unit_code || '',
        opening_qty: formatNum(item.opening_qty),
        opening_val: formatNum(item.opening_value),
        in_qty: formatNum(item.in_qty),
        in_val: formatNum(item.in_value),
        out_qty: formatNum(item.out_qty),
        out_val: formatNum(item.out_value),
        closing_qty: formatNum(item.closing_qty),
        closing_val: formatNum(item.closing_value)
      };
    });
    spreadsheet.setData(rows);
    setStatus('Loaded ' + data.length + ' items for ' + currentPeriodLabel, 'success');
  }

  function restoreSavedSheet(savedData) {
    if (!spreadsheet) return;
    try {
      var rows;
      if (Array.isArray(savedData)) {
        if (savedData.length > 0 && typeof savedData[0] === 'object' && !Array.isArray(savedData[0])) {
          rows = savedData;
        } else {
          rows = savedData.map(function(row) {
            return {
              num: row[0] || 0,
              itemcode: row[1] || '',
              description: row[2] || '',
              category: row[3] || '',
              store: row[4] || '',
              unit: row[5] || '',
              opening_qty: row[6] || 0,
              opening_val: row[7] || 0,
              in_qty: row[8] || 0,
              in_val: row[9] || 0,
              out_qty: row[10] || 0,
              out_val: row[11] || 0,
              closing_qty: row[12] || 0,
              closing_val: row[13] || 0
            };
          });
        }
      } else if (savedData && savedData.cells) {
        rows = savedData.cells;
      } else {
        throw new Error('invalid format');
      }
      spreadsheet.setData(rows);
      setStatus('Restored saved layout for ' + currentPeriodLabel, 'info');
    } catch(e) {
      if (rawData) populateSheet(rawData);
    }
  }

  function tryPopulate() {
    if (!rawData || !spreadsheet) return;
    if (pendingData) {
      restoreSavedSheet(pendingData);
    } else {
      populateSheet(rawData);
    }
  }

  function loadPeriodData(periodNo) {
    currentPeriod = periodNo;
    rawData = null;
    pendingData = null;
    pivotInitialized = false;
    pivotContainer.innerHTML = '';
    setStatus('Loading...', 'info');

    Promise.all([
      fetch('api/get_inventory_balances.php?period_no=' + periodNo).then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); }),
      fetch('api/get_spreadsheet.php?test=' + getSheetKey() + '&user=' + encodeURIComponent(currentUserId)).then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    ]).then(function(results) {
      var apiResult = results[0];
      var saveResult = results[1];

      if (!apiResult.success) {
        showError('API error: ' + (apiResult.message || 'Unknown'));
        return;
      }

      rawData = apiResult.data || [];

      if (saveResult.success && saveResult.data && saveResult.data.sheet_data) {
        pendingData = saveResult.data.sheet_data;
      }

      tryPopulate();
    }).catch(function(err) {
      showError('Error: ' + err.message);
    });
  }

  function saveSheet() {
    if (!spreadsheet) {
      setStatus('Spreadsheet not ready', 'error');
      return;
    }
    setStatus('Saving...', 'info');

    var data = spreadsheet.getData();

    fetch('api/save_spreadsheet.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        test_itemcode: getSheetKey(),
        sheet_data: { cells: data },
        user_id: currentUserId
      })
    }).then(function(r) { return r.json(); }).then(function(res) {
      if (res.success) {
        setStatus('Saved successfully', 'success');
      } else {
        setStatus('Save failed: ' + (res.message || ''), 'error');
      }
    }).catch(function(err) {
      setStatus('Save error: ' + err.message, 'error');
    });
  }

  function initPivot() {
    if (pivotInitialized) return;
    try {
      if (typeof $ === 'undefined' || !$.pivotUtilities) {
        throw new Error('PivotTable library not loaded');
      }

      pivotContainer.innerHTML = '<p style="padding:20px;color:var(--is-text2);">Loading inventory data...</p>';

      var cached = invDataCache[currentPeriod];
      if (cached) {
        invData = cached.data;
        invCurrentMonth = cached.currentMonth;
        invMonthList = cached.monthList || cached.months || [];
        invQuarterList = cached.quarterList || cached.quarters || [];
        buildPivot();
        return;
      }

      fetch('api/get_inventory_pivot_data.php?period_no=' + currentPeriod)
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(res) {
          if (!res.success) {
            pivotContainer.innerHTML = '<p style="color:var(--is-danger);padding:20px;">Inventory data error: ' + (res.message || 'Unknown') + '</p>';
            return;
          }
          invMonthList = res.months || [];
          invQuarterList = res.quarters || [];
          invCurrentMonth = res.current_month || '';
          if (!res.data || res.data.length === 0) {
            if (rawData && rawData.length > 0) {
              invData = [];
              rawData.forEach(function(item) {
                invData.push({
                  itemcode: item.itemcode || '',
                  description: item.description || '',
                  category: item.category || '',
                  category_name: item.category_name || '',
                  store_code: item.store_code || '',
                  store_name: item.store_name || '',
                  unit_code: item.unit_code || '',
                  unit_name: item.unit_name || '',
                  qty: 0,
                  value: 0,
                  period_month: 'Opening',
                  period_quarter: 'Opening'
                });
              });
              invDataCache[currentPeriod] = { data: invData, currentMonth: invCurrentMonth, monthList: invMonthList, quarterList: invQuarterList };
              buildPivot();
            } else {
              pivotContainer.innerHTML = '<p style="color:var(--is-text2);padding:20px;">No inventory items found for this period.</p>';
            }
            return;
          }
          invData = res.data;
          invDataCache[currentPeriod] = { data: invData, currentMonth: invCurrentMonth, monthList: invMonthList, quarterList: invQuarterList };
          buildPivot();
        })
        .catch(function(err) {
          pivotContainer.innerHTML = '<p style="color:var(--is-danger);padding:20px;">Failed to load inventory data: ' + err.message + '</p>';
        });
    } catch(e) {
      pivotContainer.innerHTML = '<p style="color:var(--is-danger);padding:20px;">Pivot failed: ' + e.message + '.<br>Switch back to Raw Data view.</p>';
      setStatus('Pivot render error', 'error');
    }
  }

  function buildPivot() {
    pivotContainer.innerHTML = '';

    var periodLabels = { current_month: 'Current Month', '12_months': '12 Months', '4_quarters': '4 Quarters' };

    var bar = document.createElement('div');
    bar.id = 'is-pivot-bar';
    bar.innerHTML =
      '<div class="pf-row"><label class="pf-label">Group by Store</label>' +
      '<div class="pf-chips" id="is-pf-cols">' +
      '<label class="pf-chip' + (pStore ? ' active' : '') + '"><input type="checkbox" value="store"' + (pStore ? ' checked' : '') + '> Store</label>' +
      '</div></div>' +
      '<div class="pf-row"><label class="pf-label">Category</label>' +
      '<select id="is-pf-category"></select></div>' +
      '<div class="pf-row"><label class="pf-label">Period</label>' +
      '<select id="is-pf-period">' +
      Object.keys(periodLabels).map(function(k) { return '<option value="' + k + '"' + (k === pPeriod ? ' selected' : '') + '>' + periodLabels[k] + '</option>'; }).join('') +
      '</select></div>' +
      '<div class="pf-row"><label class="pf-label">Measure</label>' +
      '<select id="is-pf-measure">' +
      '<option value="qty"' + (pMeasure === 'qty' ? ' selected' : '') + '>Quantity</option>' +
      '<option value="val"' + (pMeasure === 'val' ? ' selected' : '') + '>Value</option>' +
      '</select></div>';

    var area = document.createElement('div');
    area.id = 'is-pivot-area';
    pivotContainer.appendChild(bar);
    pivotContainer.appendChild(area);

    // Populate category dropdown (bar now in DOM)
    var catSelect = document.getElementById('is-pf-category');
    var cats = {};
    invData.forEach(function(r) { var cn = r.category_name || 'Uncategorized'; cats[cn] = true; });
    var catList = Object.keys(cats).sort();
    // Try rawData as fallback if invData has no categories
    if (catList.length === 0 && rawData) {
      rawData.forEach(function(r) { var cn = r.category_name || 'Uncategorized'; cats[cn] = true; });
      catList = Object.keys(cats).sort();
    }
    catSelect.innerHTML = '<option value="all"' + (pCategory === 'all' ? ' selected' : '') + '>All Categories</option>' +
      catList.map(function(c) { return '<option value="' + c.replace(/"/g, '&quot;') + '"' + (pCategory === c ? ' selected' : '') + '>' + c + '</option>'; }).join('');

    renderPivot();

    document.getElementById('is-pf-cols').addEventListener('change', function() {
      pStore = document.querySelector('#is-pf-cols input:checked') !== null;
      renderPivot();
    });
    document.getElementById('is-pf-category').addEventListener('change', function() {
      pCategory = this.value;
      renderPivot();
    });
    document.getElementById('is-pf-period').addEventListener('change', function() {
      pPeriod = this.value;
      renderPivot();
    });
    document.getElementById('is-pf-measure').addEventListener('change', function() {
      pMeasure = this.value;
      renderPivot();
    });

    pivotInitialized = true;
  }

  function renderPivot() {
    var area = document.getElementById('is-pivot-area');
    if (!area || !invData) return;
    try {
      var data = invData.slice();

      // Category filter
      if (pCategory && pCategory !== 'all') {
        data = data.filter(function(r) { return (r.category_name || 'Uncategorized') === pCategory; });
      }

      var periodCol, showList;
      if (pPeriod === 'current_month') {
        data = data.filter(function(r) { return r.period_month === 'Opening' || r.period_month === invCurrentMonth; });
        periodCol = 'period_month';
        showList = invMonthList;
      } else if (pPeriod === '4_quarters') {
        periodCol = 'period_quarter';
        showList = invQuarterList;
      } else {
        periodCol = 'period_month';
        showList = invMonthList;
      }

      var rows = ['description'];
      var cols = [];
      if (pStore) cols.push('store_name');
      cols.push(periodCol);

      var valField = pMeasure === 'val' ? 'value' : 'qty';
      var aggrFn = $.pivotUtilities.aggregators.Sum([valField]);
      var aggrName = 'Sum';

      $(area).pivot(data, {
        rows: rows,
        cols: cols,
        vals: [valField],
        aggregator: aggrFn,
        aggregatorName: aggrName,
        renderer: $.pivotUtilities.renderers.Table,
        localeStrings: { rowTotal: 'Closing Balance' },
        sorters: function(attr) {
          if (attr === 'store_name') {
            return function(a, b) { return (a || '').localeCompare(b || ''); };
          }
          if (attr === periodCol && showList && showList.length) {
            return function(a, b) { return showList.indexOf(a) - showList.indexOf(b); };
          }
          return null;
        },
        rendererOptions: {
          numberFormat: { digitsAfterDecimal: pMeasure === 'qty' ? 0 : 2 },
          table: { clickCallback: function(e, value, filters, pd) {
            try {
              if (typeof value !== 'number') return;
              if (!filters || (!filters.period_month && !filters.period_quarter)) return;
              var pm = filters.period_month || filters.period_quarter || '';
              if (pm === 'Opening') {
                showDrillDownStatic('Opening Balance \u2014 Cumulative stock balance brought forward from prior periods.');
                return;
              }
              var itemName = filters['description'] || '';
              if (!itemName) return;
              var records = [];
              if (pd && typeof pd.forEachMatchingRecord === 'function') {
                pd.forEachMatchingRecord(filters, function(r) { if (r) records.push(r); });
              }
              var itemCode = records.length > 0 ? (records[0].itemcode || '') : '';
              var storeCode = records.length > 0 ? (records[0].store_code || '') : '';
              var storeName = records.length > 0 ? (records[0].store_name || '') : '';
              var filtersCopy = {};
              for (var fk in filters) { if (filters.hasOwnProperty(fk)) filtersCopy[fk] = filters[fk]; }
              showDrillDownTransactions(itemName, itemCode, storeCode, storeName, pm, value, filtersCopy);
            } catch(ex) { console.warn('Drill-down error:', ex); }
          }}
        }
      });
    } catch(ex) {
      area.innerHTML = '<p style="color:var(--is-danger);padding:20px;">Render error: ' + ex.message + '</p>';
    }
  }

  function showDrillDownStatic(msg) {
    var existing = document.getElementById('is-drilldown');
    if (existing) existing.remove();
    var overlay = document.createElement('div');
    overlay.id = 'is-drilldown';
    overlay.innerHTML = '<div class="is-drilldown-backdrop"><div class="is-drilldown-box" style="min-width:400px;"><div class="is-drilldown-hdr"><span>Notice</span><button class="is-drilldown-close">&times;</button></div><div class="is-drilldown-body" style="padding:20px;font-size:13px;color:var(--is-text2);">' + msg + '</div></div></div>';
    document.body.appendChild(overlay);
    overlay.querySelector('.is-drilldown-close').addEventListener('click', function() { overlay.remove(); });
    overlay.addEventListener('click', function(e) { if (e.target === overlay || e.target.classList.contains('is-drilldown-backdrop')) overlay.remove(); });
  }

  function showDrillDownTransactions(itemName, itemCode, storeCode, storeName, periodLabel, total, filters) {
    var existing = document.getElementById('is-drilldown');
    if (existing) existing.remove();

    var dimParts = [];
    for (var k in filters) {
      if (filters.hasOwnProperty(k) && k !== 'description') dimParts.push(k.replace(/_/g, ' ') + ': ' + filters[k]);
    }
    var title = itemName + (dimParts.length ? ' \u2014 ' + dimParts.join(', ') : '');

    var overlay = document.createElement('div');
    overlay.id = 'is-drilldown';
    overlay.innerHTML = '<div class="is-drilldown-backdrop"><div class="is-drilldown-box" style="min-width:800px;"><div class="is-drilldown-hdr"><span>' + title + '</span><span style="font-weight:400;">Total: ' + (pMeasure === 'qty' ? total.toFixed(2) : total.toFixed(2)) + '</span><button class="is-drilldown-close">&times;</button></div><div class="is-drilldown-body"><p style="padding:12px 18px;color:var(--is-text2);font-size:13px;">Loading transactions...</p></div></div></div>';
    document.body.appendChild(overlay);
    overlay.querySelector('.is-drilldown-close').addEventListener('click', function() { overlay.remove(); });
    overlay.addEventListener('click', function(e) { if (e.target === overlay || e.target.classList.contains('is-drilldown-backdrop')) overlay.remove(); });

    if (!itemCode || !periodLabel) return;

    var qs = 'itemcode=' + encodeURIComponent(itemCode) + '&period_no=' + currentPeriod + '&month=' + encodeURIComponent(periodLabel);
    if (storeCode) qs += '&store=' + encodeURIComponent(storeCode);

    fetch('api/get_inventory_transactions.php?' + qs)
      .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function(res) {
        var body = overlay.querySelector('.is-drilldown-body');
        if (!body) return;
        if (!res.success || !res.data || res.data.length === 0) {
          body.innerHTML = '<p style="padding:12px 18px;color:var(--is-text2);font-size:13px;">No transactions found for this period.</p>';
          return;
        }
        var html = '<table class="is-drilldown-table"><thead><tr><th>#</th><th>Date</th><th>Doc #</th><th>Type</th><th>Narration</th><th style="text-align:right;">Qty</th><th style="text-align:right;">Unit Cost</th><th style="text-align:right;">Total Value</th></tr></thead><tbody>';
        res.data.forEach(function(t, i) {
          var d = t.date || '';
          var n = t.narration || '';
          if (n.length > 60) n = n.substring(0, 60) + '...';
          html += '<tr><td>' + (i+1) + '</td><td>' + d + '</td><td>' + (t.document_no || '') + '</td><td>' + (t.document_type || '') + '</td><td>' + n + '</td>';
          html += '<td style="text-align:right;font-family:monospace;">' + (t.qty || 0).toFixed(2) + '</td>';
          html += '<td style="text-align:right;font-family:monospace;">' + (t.unit_cost || 0).toFixed(2) + '</td>';
          html += '<td style="text-align:right;font-family:monospace;">' + (t.total_value || 0).toFixed(2) + '</td></tr>';
        });
        html += '</tbody></table>';
        body.innerHTML = html;
      })
      .catch(function(err) {
        var body = overlay.querySelector('.is-drilldown-body');
        if (body) body.innerHTML = '<p style="padding:12px 18px;color:var(--is-danger);font-size:13px;">Failed to load transactions: ' + err.message + '</p>';
      });
  }

  periodSelect.addEventListener('change', function() {
    var pno = parseInt(this.value);
    if (pno !== currentPeriod) {
      pivotInitialized = false;
      invData = null;
      loadPeriodData(pno);
      if (pivotContainer.style.display !== 'none') initPivot();
    }
  });

  document.getElementById('is-btn-refresh').addEventListener('click', function() {
    pivotInitialized = false;
    invData = null;
    delete invDataCache[currentPeriod];
    loadPeriodData(currentPeriod);
    if (pivotContainer.style.display !== 'none') initPivot();
  });

  document.getElementById('is-btn-save').addEventListener('click', saveSheet);

  document.getElementById('is-btn-print').addEventListener('click', function() {
    if (pivotContainer.style.display !== 'none') {
      var table = document.querySelector('#is-pivot-area table');
      if (!table) { setStatus('No pivot table to print', 'error'); return; }
      var html = '<html><head><meta charset="utf-8"><title>Inventory Spreadsheet Period ' + currentPeriod + '</title>';
      html += '<style>body{font-family:Arial,sans-serif;font-size:10pt;}h2{font-size:14pt;}table{border-collapse:collapse;width:100%;font-size:10pt;}th,td{border:1px solid #999;padding:4px 8px;text-align:left;}th{background:#e2e8f0;font-weight:bold;}</style></head><body>';
      html += '<h2>Inventory Spreadsheet - Period ' + currentPeriod + ' (Pivot)</h2>';
      html += table.outerHTML;
      html += '</body></html>';
      var w = window.open('', '_blank');
      w.document.write(html);
      w.document.close();
      w.focus();
      setTimeout(function() { w.print(); }, 500);
    } else if (spreadsheet) {
      spreadsheet.print(false, true);
    } else {
      setStatus('Nothing to print', 'error');
    }
  });

  function exportPivotTable(format) {
    var table = document.querySelector('#is-pivot-area table.pvtTable');
    if (!table) { setStatus('No pivot table to export', 'error'); return; }
    try {
      if (typeof XLSX === 'undefined') { setStatus('XLSX library not loaded', 'error'); return; }
      var clone = table.cloneNode(true);
      var sheet = XLSX.utils.table_to_sheet(clone, { raw: true });
      if (format === 'csv') {
        var csv = XLSX.utils.sheet_to_csv(sheet);
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Inventory_Pivot_' + currentPeriod + '.csv';
        link.click();
        URL.revokeObjectURL(link.href);
      } else {
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, sheet, 'Inventory');
        XLSX.writeFile(wb, 'Inventory_Pivot_' + currentPeriod + '.xlsx');
      }
      setStatus('Exported ' + format.toUpperCase(), 'success');
    } catch(e) {
      setStatus('Export failed: ' + e.message, 'error');
    }
  }

  document.getElementById('is-btn-csv').addEventListener('click', function() {
    if (pivotContainer.style.display !== 'none') {
      exportPivotTable('csv');
    } else if (spreadsheet) {
      spreadsheet.download('csv', 'Inventory_' + currentPeriod + '.csv');
      setStatus('Exported CSV', 'success');
    } else {
      setStatus('Nothing to export', 'error');
    }
  });

  document.getElementById('is-btn-excel').addEventListener('click', function() {
    if (pivotContainer.style.display !== 'none') {
      exportPivotTable('xlsx');
    } else if (spreadsheet) {
      try {
        spreadsheet.download('xlsx', 'Inventory_' + currentPeriod + '.xlsx', { sheetName: 'Inventory' });
        setStatus('Exported Excel', 'success');
      } catch(e) {
        setStatus('Excel export failed: ' + e.message, 'error');
      }
    } else {
      setStatus('Nothing to export', 'error');
    }
  });

  try {
    if (typeof Tabulator === 'undefined') {
      showError('Tabulator library not loaded');
      return;
    }

    sheetContainer.innerHTML = '';
    spreadsheet = new Tabulator('#is-sheet', {
      data: [],
      columns: [
        { title: '#', field: 'num', width: 45, hozAlign: 'right', headerSort: true },
        { title: 'Item Code', field: 'itemcode', width: 100, headerSort: true },
        { title: 'Description', field: 'description', width: 240, headerSort: true },
        { title: 'Category', field: 'category', width: 120, headerSort: true },
        { title: 'Store', field: 'store', width: 110, headerSort: true },
        { title: 'Unit', field: 'unit', width: 60, headerSort: true },
        { title: 'Opening Qty', field: 'opening_qty', width: 110, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 0, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'Opening Val', field: 'opening_val', width: 110, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'In Qty', field: 'in_qty', width: 100, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 0, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'In Val', field: 'in_val', width: 100, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'Out Qty', field: 'out_qty', width: 100, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 0, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'Out Val', field: 'out_val', width: 100, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'Closing Qty', field: 'closing_qty', width: 110, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 0, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'Closing Val', field: 'closing_val', width: 110, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2, thousand: ',', decimal: '.' }, headerSort: true },
      ],
      layout: 'fitColumns',
      height: '520px',
      movableColumns: false,
      resizableColumns: true,
      selectable: false,
      printAsHtml: true,
      printHeader: '<h2 style="font-family:Arial;margin:0 0 8px;">Inventory Spreadsheet - Period ' + currentPeriod + '</h2>',
      printFooter: '<p style="font-family:Arial;color:#666;font-size:10px;">Generated ' + new Date().toLocaleString() + '</p>',
    });
  } catch(e) {
    showError('Failed to initialize spreadsheet: ' + e.message);
    return;
  }

  loadPeriodData(currentPeriod);
})();
