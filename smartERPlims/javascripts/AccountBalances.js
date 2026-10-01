(function() {
  var currentPeriod = AB_CONFIG.currentPeriod;
  var currentPeriodLabel = AB_CONFIG.currentPeriodLabel;
  var currentUserId = AB_CONFIG.currentUserId;
  var rawData = null;
  var pendingData = null;
  var pivotInitialized = false;
  var pivotContainer = document.getElementById('pivot-container');
  var calcContainer = document.getElementById('calc-sheet');
  var statusEl = document.getElementById('ab-status');
  var periodSelect = document.getElementById('period-select');
  var viewRadios = document.querySelectorAll('input[name="view"]');
  var spreadsheet = null;
  var glDataCache = {};
  var glData = null;
  var glCurrentMonth = '';
  var glMonthList = [];
  var glQuarterList = [];
  var glSortMap = {};
  var pVal = 'Value';
  var pPeriod = 'current_month';
  var pType = 'all';
  var pCurrency = false;

  function setStatus(msg, type) {
    statusEl.textContent = msg;
    statusEl.className = 'ab-status-' + (type || 'info');
  }

  function showError(msg) {
    setStatus(msg || 'Failed to load data. Click Refresh to try again.', 'error');
  }

  function formatNum(v) {
    if (v === null || v === undefined || isNaN(v)) return 0;
    return Math.round(v * 100) / 100;
  }

  function getSheetKey() {
    return '_BAL_' + currentPeriod;
  }

  function toggleView(view) {
    if (view === 'pivot') {
      calcContainer.style.display = 'none';
      pivotContainer.style.display = 'block';
      if (!pivotInitialized) {
        initPivot();
      }
    } else {
      calcContainer.style.display = 'block';
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
      var opening = formatNum(item.debit_last - item.credit_last);
      var debit = formatNum(item.debit - item.debit_last);
      var credit = formatNum(item.credit - item.credit_last);
      var closing = formatNum(item.debit - item.credit);
      return {
        num: ri + 1,
        reportcode: item.reportcode || '',
        accountname: item.accdesc || '',
        type: item.balance_income === 0 ? 'Balance Sheet' : 'Income Statement',
        group: item.accgrp || '',
        currency: item.currency || '',
        opening: opening,
        debit: debit,
        credit: credit,
        closing: closing
      };
    });
    spreadsheet.setData(rows);
    setStatus('Loaded ' + data.length + ' accounts for ' + currentPeriodLabel, 'success');
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
              reportcode: row[1] || '',
              accountname: row[2] || '',
              type: row[3] || '',
              group: row[4] || '',
              currency: row[5] || '',
              opening: row[6] || 0,
              debit: row[7] || 0,
              credit: row[8] || 0,
              closing: row[9] || 0
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
      fetch('api/get_account_balances.php?period_no=' + periodNo).then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); }),
      fetch('api/get_spreadsheet.php?test=' + getSheetKey() + '&user=' + encodeURIComponent(currentUserId)).then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    ]).then(function(results) {
      var apiResult = results[0];
      var saveResult = results[1];

      if (!apiResult.success) {
        showError('API error: ' + (apiResult.message || 'Unknown'));
        return;
      }

      rawData = apiResult.data || [];
      rawData.sort(function(a, b) { return (a.reportcode || '').localeCompare(b.reportcode || '', undefined, { numeric: true }); });

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

      pivotContainer.innerHTML = '<p style="padding:20px;color:var(--ab-text2);">Loading GL data...</p>';

      var cached = glDataCache[currentPeriod];
      if (cached) {
        glData = cached.data;
        glCurrentMonth = cached.currentMonth;
        glMonthList = cached.monthList || cached.months || [];
        glQuarterList = cached.quarterList || cached.quarters || [];
        glSortMap = {};
        glData.forEach(function(r) { glSortMap[r['ACCOUNT']] = r.reportcode || ''; });
        buildPivot();
        return;
      }

      fetch('api/get_gl_pivot_data.php?period_no=' + currentPeriod)
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(res) {
          if (!res.success) {
            pivotContainer.innerHTML = '<p style="color:var(--ab-danger);padding:20px;">GL data error: ' + (res.message || 'Unknown') + '</p>';
            return;
          }
          glMonthList = res.months || [];
          glQuarterList = res.quarters || [];
          glCurrentMonth = res.current_month || '';
          if (!res.data || res.data.length === 0) {
            if (rawData && rawData.length > 0) {
              glData = [];
              rawData.forEach(function(item) {
                var accName = item.accdesc || '';
                var rcode = item.reportcode || '';
                glData.push({
                  account_code: item.accno || '',
                  'ACCOUNT': accName,
                  reportcode: rcode,
                  balance_income: item.balance_income || 0,
                  account_group: item.accgrp || '',
                  currency: item.currency || 'KES',
                  value: 0,
                  period_month: 'Opening',
                  period_quarter: 'Opening'
                });
              });
              glSortMap = {};
              glData.forEach(function(r) { glSortMap[r['ACCOUNT']] = r.reportcode || ''; });
              glDataCache[currentPeriod] = { data: glData, currentMonth: glCurrentMonth, monthList: glMonthList, quarterList: glQuarterList };
              buildPivot();
            } else {
              pivotContainer.innerHTML = '<p style="color:var(--ab-text2);padding:20px;">No accounts found for this period.</p>';
            }
            return;
          }
          glData = res.data.map(function(item) {
            item['ACCOUNT'] = item.account_name;
            delete item.account_name;
            return item;
          });
          glSortMap = {};
          glData.forEach(function(r) { glSortMap[r['ACCOUNT']] = r.reportcode || ''; });
          glDataCache[currentPeriod] = { data: glData, currentMonth: glCurrentMonth, monthList: glMonthList, quarterList: glQuarterList };
          buildPivot();
        })
        .catch(function(err) {
          pivotContainer.innerHTML = '<p style="color:var(--ab-danger);padding:20px;">Failed to load GL data: ' + err.message + '</p>';
        });
    } catch(e) {
      pivotContainer.innerHTML = '<p style="color:var(--ab-danger);padding:20px;">Pivot failed: ' + e.message + '.<br>Switch back to Raw Data view.</p>';
      setStatus('Pivot render error', 'error');
    }
  }

  function buildPivot() {
    pivotContainer.innerHTML = '';

    var typeLabels = { all: 'All', balance_sheet: 'Balance Sheet', income_statement: 'Income Statement' };
    var periodLabels = { current_month: 'Current Month', '12_months': '12 Months', '4_quarters': '4 Quarters' };

    var bar = document.createElement('div');
    bar.id = 'pivot-bar';
    bar.innerHTML =
      '<div class="pf-row"><label class="pf-label">Group by</label>' +
      '<div class="pf-chips" id="pf-rows">' +
      '<label class="pf-chip' + (pCurrency ? ' active' : '') + '"><input type="checkbox" value="currency"' + (pCurrency ? ' checked' : '') + '> Currency</label>' +
      '</div></div>' +
      '<div class="pf-row"><label class="pf-label">Period</label>' +
      '<select id="pf-period">' +
      Object.keys(periodLabels).map(function(k) { return '<option value="' + k + '"' + (k === pPeriod ? ' selected' : '') + '>' + periodLabels[k] + '</option>'; }).join('') +
      '</select></div>' +
      '<div class="pf-row"><label class="pf-label">Measure</label>' +
      '<select id="pf-val">' +
      '<option value="Value"' + (pVal === 'Value' ? ' selected' : '') + '>Value</option>' +
      '<option value="Count"' + (pVal === 'Count' ? ' selected' : '') + '>Count</option>' +
      '</select></div>' +
      '<div class="pf-row"><label class="pf-label">Type</label>' +
      '<select id="pf-type">' +
      Object.keys(typeLabels).map(function(k) { return '<option value="' + k + '"' + (k === pType ? ' selected' : '') + '>' + typeLabels[k] + '</option>'; }).join('') +
      '</select></div>';

    var area = document.createElement('div');
    area.id = 'pivot-area';
    pivotContainer.appendChild(bar);
    pivotContainer.appendChild(area);

    renderPivot();

    bar.addEventListener('change', function() {
      pCurrency = document.querySelector('#pf-rows input:checked') !== null;
      pPeriod = document.getElementById('pf-period').value;
      pVal = document.getElementById('pf-val').value;
      pType = document.getElementById('pf-type').value;
      renderPivot();
    });

    pivotInitialized = true;
    setStatus('Pivot ready - ' + glData.length + ' rows', 'success');
  }

  function renderPivot() {
    var area = document.getElementById('pivot-area');
    if (!area || !glData) return;
    try {
      var filtered = glData.slice();

      if (pType === 'balance_sheet') {
        filtered = filtered.filter(function(r) { return r.balance_income === 0; });
      } else if (pType === 'income_statement') {
        filtered = filtered.filter(function(r) { return r.balance_income === 1; });
      }

      var cols, showList;
      if (pPeriod === 'current_month') {
        // Show only Opening + current month
        filtered = filtered.filter(function(r) { return r.period_month === 'Opening' || r.period_month === glCurrentMonth; });
        cols = ['period_month'];
        showList = glMonthList;
      } else if (pPeriod === '4_quarters') {
        cols = ['period_quarter'];
        showList = glQuarterList;
      } else {
        cols = ['period_month'];
        showList = glMonthList;
      }

      var rows = ['ACCOUNT'];
      if (pCurrency) rows.push('currency');

      var aggrFn, aggrName, valField;
      if (pVal === 'Count') {
        aggrFn = $.pivotUtilities.aggregators.Count();
        aggrName = 'Count';
        valField = 'account_code';
      } else {
        aggrFn = $.pivotUtilities.aggregators.Sum(['value']);
        aggrName = 'Sum';
        valField = 'value';
      }

      var sortAttr = cols[0];
      var sortList = showList;

      $(area).pivot(filtered, {
        rows: rows,
        cols: cols,
        vals: [valField],
        aggregator: aggrFn,
        aggregatorName: aggrName,
        renderer: $.pivotUtilities.renderers.Table,
        localeStrings: { rowTotal: 'Closing Balance' },
        sorters: function(attr) {
          if (attr === sortAttr && sortList && sortList.length) {
            return function(a, b) { return sortList.indexOf(a) - sortList.indexOf(b); };
          }
          if (attr === 'ACCOUNT') {
            return function(a, b) { return (glSortMap[a] || '').localeCompare(glSortMap[b] || '', undefined, { numeric: true }); };
          }
          return null;
        },
        rendererOptions: {
          table: { clickCallback: function(e, value, filters, pd) {
            try {
              if (typeof value !== 'number') return;
              if (!filters || !filters.period_month && !filters.period_quarter) return;
              var pm = filters.period_month || filters.period_quarter || '';
              if (pm === 'Opening') {
                showDrillDownStatic('Opening Balance \u2014 Cumulative balance brought forward from prior periods.');
                return;
              }
              var accountName = filters['ACCOUNT'] || '';
              if (!accountName) return;
              var records = [];
              if (pd && typeof pd.forEachMatchingRecord === 'function') {
                pd.forEachMatchingRecord(filters, function(r) { if (r) records.push(r); });
              }
              var accountCode = records.length > 0 ? (records[0].account_code || '') : '';
              var filtersCopy = {};
              for (var fk in filters) { if (filters.hasOwnProperty(fk)) filtersCopy[fk] = filters[fk]; }
              showDrillDownTransactions(accountName, accountCode, pm, value, filtersCopy);
            } catch(ex) { console.warn('Drill-down error:', ex); }
          }}
        }
      });
    } catch(ex) {
      area.innerHTML = '<p style="color:var(--ab-danger);padding:20px;">Render error: ' + ex.message + '</p>';
    }
  }

  function showDrillDownStatic(msg) {
    var existing = document.getElementById('ab-drilldown');
    if (existing) existing.remove();
    var overlay = document.createElement('div');
    overlay.id = 'ab-drilldown';
    overlay.innerHTML = '<div class="ab-dd-backdrop"><div class="ab-dd-box" style="min-width:400px;"><div class="ab-dd-hdr"><span>Notice</span><button class="ab-dd-close">&times;</button></div><div class="ab-dd-body" style="padding:20px;font-size:13px;color:var(--ab-text2);">' + msg + '</div></div></div>';
    document.body.appendChild(overlay);
    overlay.querySelector('.ab-dd-close').addEventListener('click', function() { overlay.remove(); });
    overlay.addEventListener('click', function(e) { if (e.target === overlay || e.target.classList.contains('ab-dd-backdrop')) overlay.remove(); });
  }

  function showDrillDownTransactions(accountName, accountCode, periodLabel, total, filters) {
    var existing = document.getElementById('ab-drilldown');
    if (existing) existing.remove();

    var dimParts = [];
    for (var k in filters) {
      if (filters.hasOwnProperty(k) && k !== 'ACCOUNT') dimParts.push(k.replace(/_/g, ' ') + ': ' + filters[k]);
    }
    var title = accountName + (dimParts.length ? ' \u2014 ' + dimParts.join(', ') : '');

    var overlay = document.createElement('div');
    overlay.id = 'ab-drilldown';
    overlay.innerHTML = '<div class="ab-dd-backdrop"><div class="ab-dd-box" style="min-width:700px;"><div class="ab-dd-hdr"><span>' + title + '</span><span style="font-weight:400;">Total: ' + total.toFixed(2) + '</span><button class="ab-dd-close">&times;</button></div><div class="ab-dd-body"><p style="padding:12px 18px;color:var(--ab-text2);font-size:13px;">Loading transactions...</p></div></div></div>';
    document.body.appendChild(overlay);
    overlay.querySelector('.ab-dd-close').addEventListener('click', function() { overlay.remove(); });
    overlay.addEventListener('click', function(e) { if (e.target === overlay || e.target.classList.contains('ab-dd-backdrop')) overlay.remove(); });

    if (!accountCode || !periodLabel) return;

    fetch('api/get_month_transactions.php?account_code=' + encodeURIComponent(accountCode) + '&period_no=' + currentPeriod + '&month=' + encodeURIComponent(periodLabel))
      .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function(res) {
        var body = overlay.querySelector('.ab-dd-body');
        if (!body) return;
        if (!res.success || !res.data || res.data.length === 0) {
          body.innerHTML = '<p style="padding:12px 18px;color:var(--ab-text2);font-size:13px;">No transactions found for this period.</p>';
          return;
        }
        var html = '<table class="ab-dd-table"><thead><tr><th>#</th><th>Date</th><th>Doc #</th><th>Type</th><th>Narration</th><th style="text-align:right;">Value</th><th>Side</th></tr></thead><tbody>';
        res.data.forEach(function(t, i) {
          var d = t.date ? t.date.substring(0,10) : '';
          var n = t.narration || '';
          if (n.length > 60) n = n.substring(0,60) + '...';
          var v = typeof t.value === 'number' ? t.value : 0;
          html += '<tr><td>' + (i+1) + '</td><td>' + d + '</td><td>' + (t.document_no || '') + '</td><td>' + (t.document_type || '') + '</td><td>' + n + '</td><td style="text-align:right;font-family:monospace;">' + v.toFixed(2) + '</td><td>' + (t.side || '') + '</td></tr>';
        });
        html += '</tbody></table>';
        body.innerHTML = html;
      })
      .catch(function(err) {
        var body = overlay.querySelector('.ab-dd-body');
        if (body) body.innerHTML = '<p style="padding:12px 18px;color:var(--ab-danger);font-size:13px;">Failed to load transactions: ' + err.message + '</p>';
      });
  }

  periodSelect.addEventListener('change', function() {
    var pno = parseInt(this.value);
    if (pno !== currentPeriod) {
      pivotInitialized = false;
      glData = null;
      loadPeriodData(pno);
      if (pivotContainer.style.display !== 'none') {
        initPivot();
      }
    }
  });

  document.getElementById('btn-refresh').addEventListener('click', function() {
    pivotInitialized = false;
    glData = null;
    delete glDataCache[currentPeriod];
    loadPeriodData(currentPeriod);
    if (pivotContainer.style.display !== 'none') {
      initPivot();
    }
  });

  document.getElementById('btn-save').addEventListener('click', saveSheet);

  document.getElementById('btn-print').addEventListener('click', function() {
    if (pivotContainer.style.display !== 'none') {
      var table = document.querySelector('#pivot-area table');
      if (!table) { setStatus('No pivot table to print', 'error'); return; }
      var html = '<html><head><meta charset="utf-8"><title>Pivot - SPREADSHEETS Period ' + currentPeriod + '</title>';
      html += '<style>body{font-family:Arial,sans-serif;font-size:10pt;}h2{font-size:14pt;}table{border-collapse:collapse;width:100%;font-size:10pt;}th,td{border:1px solid #999;padding:4px 8px;text-align:left;}th{background:#e2e8f0;font-weight:bold;}</style></head><body>';
      html += '<h2>SPREADSHEETS - Period ' + currentPeriod + ' (Pivot)</h2>';
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
    var table = document.querySelector('#pivot-area table.pvtTable');
    if (!table) { setStatus('No pivot table to export', 'error'); return; }
    try {
      if (typeof XLSX === 'undefined') { setStatus('XLSX library not loaded', 'error'); return; }
      // Clone and clean the table (remove onclick, fix header text)
      var clone = table.cloneNode(true);
      var pvtCells = clone.querySelectorAll('th.pvtColLabel, th.pvtRowLabel, th.pvtTotalLabel, th.pvtGrandTotal, td.pvtVal, td.pvtTotal, td.pvtGrandTotal');
      // Rebuild header row — replace pivot's multi-level header with flat header
      var sheet = XLSX.utils.table_to_sheet(clone, { raw: true });
      if (format === 'csv') {
        var csv = XLSX.utils.sheet_to_csv(sheet);
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'AccountBalances_Pivot_' + currentPeriod + '.csv';
        link.click();
        URL.revokeObjectURL(link.href);
      } else {
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, sheet, 'SPREADSHEETS');
        XLSX.writeFile(wb, 'AccountBalances_Pivot_' + currentPeriod + '.xlsx');
      }
      setStatus('Exported ' + format.toUpperCase(), 'success');
    } catch(e) {
      setStatus('Export failed: ' + e.message, 'error');
    }
  }

  document.getElementById('btn-export-csv').addEventListener('click', function() {
    if (pivotContainer.style.display !== 'none') {
      exportPivotTable('csv');
    } else if (spreadsheet) {
      spreadsheet.download('csv', 'AccountBalances_' + currentPeriod + '.csv');
      setStatus('Exported CSV', 'success');
    } else {
      setStatus('Nothing to export', 'error');
    }
  });

  document.getElementById('btn-export-excel').addEventListener('click', function() {
    if (pivotContainer.style.display !== 'none') {
      exportPivotTable('xlsx');
    } else if (spreadsheet) {
      try {
        spreadsheet.download('xlsx', 'AccountBalances_' + currentPeriod + '.xlsx', { sheetName: 'SPREADSHEETS' });
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

    calcContainer.innerHTML = '';
    spreadsheet = new Tabulator('#calc-sheet', {
      data: [],
      columns: [
        { title: '#', field: 'num', width: 45, hozAlign: 'right', headerSort: true },
        { title: 'Report Code', field: 'reportcode', width: 100, headerSort: true },
        { title: 'Account Name', field: 'accountname', width: 280, headerSort: true },
        { title: 'Type', field: 'type', width: 130, headerSort: true },
        { title: 'Group', field: 'group', width: 130, headerSort: true },
        { title: 'Currency', field: 'currency', width: 70, headerSort: true },
        { title: 'Opening', field: 'opening', width: 120, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'Debit', field: 'debit', width: 120, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'Credit', field: 'credit', width: 120, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2, thousand: ',', decimal: '.' }, headerSort: true },
        { title: 'Closing', field: 'closing', width: 120, hozAlign: 'right', formatter: 'money', formatterParams: { precision: 2, thousand: ',', decimal: '.' }, headerSort: true },
      ],
      layout: 'fitColumns',
      height: '520px',
      movableColumns: false,
      resizableColumns: true,
      selectable: false,
      printAsHtml: true,
      printHeader: '<h2 style="font-family:Arial;margin:0 0 8px;">SPREADSHEETS - Period ' + currentPeriod + '</h2>',
      printFooter: '<p style="font-family:Arial;color:#666;font-size:10px;">Generated ' + new Date().toLocaleString() + '</p>',
    });
  } catch(e) {
    showError('Failed to initialize spreadsheet: ' + e.message);
    return;
  }

  loadPeriodData(currentPeriod);
})();
