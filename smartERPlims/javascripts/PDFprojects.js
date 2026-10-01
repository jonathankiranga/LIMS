(function () {
  var cfg = PB_CONFIG || {};
  var rootPath = cfg.rootPath || '';
  var pivotInitialized = false;
  var pivotContainer = document.getElementById('pivot-container');

  var table = new Tabulator("#pb-sheet", {
    layout: "fitColumns",
    columns: [
      { title: "Code", field: "code", width: 80, frozen: true },
      { title: "Project", field: "budget_name", minWidth: 200 },
      { title: "Budget", field: "budget_amount", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Expenses", field: "expenses", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Committed", field: "committed", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Total", field: "total", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Balance", field: "balance", bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right",
        formatter: function(cell){
          var v = parseFloat(cell.getValue()||0);
          var cls = v < 0 ? ' style="color:#dc2626;font-weight:600;"' : v > 0 ? ' style="color:#16a34a;font-weight:600;"' : '';
          return '<span' + cls + '>' + v.toFixed(2) + '</span>';
        }
      },
      { title: "%", field: "percent", hozAlign: "right",
        formatter: function(cell){
          var v = cell.getValue();
          if (v === null || v === undefined) return '';
          var cls = v < 0 ? ' style="color:#dc2626;font-weight:600;"' : ' style="font-weight:600;"';
          return '<span' + cls + '>' + v.toFixed(1) + '%</span>';
        }
      },
    ],
    placeholder: "Select a period and click Refresh.",
  });

  function updateCards(summary) {
    var s = summary || {};
    var bal = parseFloat(s.total_balance || 0);
    var balStyle = bal < 0 ? 'color:#dc2626;' : bal > 0 ? 'color:#16a34a;' : '';
    document.getElementById('pb-summary').innerHTML =
      '<div class="pb-card"><div class="pb-card-label">Total Budget</div><div class="pb-card-value">' + formatMoney(s.total_budget) + '</div></div>' +
      '<div class="pb-card"><div class="pb-card-label">Total Expenses</div><div class="pb-card-value">' + formatMoney(s.total_expenses) + '</div></div>' +
      '<div class="pb-card"><div class="pb-card-label">Total Committed</div><div class="pb-card-value">' + formatMoney(s.total_committed) + '</div></div>' +
      '<div class="pb-card"><div class="pb-card-label">Total (E+C)</div><div class="pb-card-value">' + formatMoney(s.total_total) + '</div></div>' +
      '<div class="pb-card"><div class="pb-card-label">Total Balance</div><div class="pb-card-value" style="' + balStyle + '">' + formatMoney(bal) + '</div></div>';
  }

  function formatMoney(val) {
    return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function buildPivot(rows) {
    pivotInitialized = false;
    pivotContainer.innerHTML = '';

    if (!rows || rows.length === 0) {
      pivotContainer.innerHTML = '<p style="padding:20px;color:#64748b;">No data for pivot.</p>';
      return;
    }

    try {
      if (typeof $ === 'undefined' || !$.pivotUtilities) {
        throw new Error('PivotTable library not loaded');
      }

      var bar = document.createElement('div');
      bar.id = 'pb-pivot-bar';
      pivotContainer.appendChild(bar);

      var area = document.createElement('div');
      area.id = 'pb-pivot-area';
      pivotContainer.appendChild(area);

      pivotInitialized = true;

      // Melt data: one row per (Project, Measure) pair
      var melted = [];
      var measures = ['Budget','Expenses','Committed','Total','Balance'];
      rows.forEach(function(r){
        var vals = [r.budget_amount, r.expenses, r.committed, r.total, r.balance];
        measures.forEach(function(m, i){
          melted.push({ Project: r.budget_name, Measure: m, Amount: vals[i] });
        });
      });

      $(area).pivot(melted, {
        rows: ['Project'],
        cols: ['Measure'],
        aggregator: $.pivotUtilities.aggregators.Sum(['Amount']),
        renderer: $.pivotUtilities.renderers.Table,
        rendererOptions: {
          table: {
            clickCallback: function(e, value, filters, pivotData){},
          },
        },
      });

      // Move filter bar before the table
      var existingBar = document.getElementById('pb-pivot-bar');
      if (existingBar) {
        var pvtFilter = area.querySelector('.pvtFilterBox');
        if (pvtFilter) bar.appendChild(pvtFilter);
      }
    } catch (e) {
      pivotContainer.innerHTML = '<p style="color:#dc2626;padding:20px;">Pivot failed: ' + e.message + '.<br>Switch back to Raw Data view.</p>';
      pivotInitialized = false;
    }
  }

  window.loadBudget = function () {
    var periodId = document.getElementById('period-select').value;
    if (periodId === '' || periodId === null) return;

    var url = 'api/get_project_budgets.php?period_id=' + encodeURIComponent(periodId);

    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          alert(data.message || 'Failed to load data');
          return;
        }
        updateCards(data.summary);
        table.setData(data.rows || []);

        var view = document.querySelector('input[name="pb-view"]:checked');
        if (view && view.value === 'pivot') {
          setTimeout(function(){ buildPivot(data.rows); }, 100);
        }
      })
      .catch(function (err) {
        console.error('Load error:', err);
        alert('Failed to load budget data.');
      });
  };

  // View toggle
  var viewRadios = document.querySelectorAll('input[name="pb-view"]');
  for (var i = 0; i < viewRadios.length; i++) {
    viewRadios[i].addEventListener('change', function () {
      var raw = document.getElementById('pb-sheet');
      var pvt = document.getElementById('pivot-container');
      if (this.value === 'pivot') {
        raw.style.display = 'none';
        pvt.style.display = '';
        var data = table.getData();
        if (data.length > 0) buildPivot(data);
      } else {
        raw.style.display = '';
        pvt.style.display = 'none';
      }
    });
  }

  document.getElementById('period-select').addEventListener('change', function () {
    loadBudget();
  });

  document.getElementById('btn-refresh').addEventListener('click', function () {
    loadBudget();
  });

  document.getElementById('btn-print').addEventListener('click', function () {
    var view = document.querySelector('input[name="pb-view"]:checked');
    if (view && view.value === 'pivot') {
      window.print();
    } else {
      table.print(false, true);
    }
  });

  document.getElementById('btn-export-csv').addEventListener('click', function () {
    table.download("csv", "ProjectBudgets.csv");
  });

  document.getElementById('btn-export-excel').addEventListener('click', function () {
    table.download("xlsx", "ProjectBudgets.xlsx", { sheetName: "Projects" });
  });

  if (document.getElementById('period-select').value) {
    loadBudget();
  }
})();
