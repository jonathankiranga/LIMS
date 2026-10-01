(function () {
  var cfg = CAR_CONFIG || {};
  var rootPath = cfg.rootPath || '';

  var table = new Tabulator("#report-table", {
    layout: "fitColumns",
    groupBy: false,
    columns: [
      { title: "Test", field: "test_name", minWidth: 200 },
      { title: "No of Tests", field: "tests_count", hozAlign: "right", width: 80 },
      { title: "Sales Price", field: "sales_price", formatter: "money", formatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Profit", field: "profit", hozAlign: "right",
        formatter: function(cell){
          var v = parseFloat(cell.getValue()||0);
          var cls = v < 0 ? ' style="color:#dc2626;font-weight:600;"' : ' style="font-weight:600;"';
          return '<span' + cls + '>' + v.toFixed(2) + '</span>';
        }
      },
      { title: "Margin %", field: "profit_margin", hozAlign: "right",
        formatter: function(cell){
          var v = cell.getValue();
          return v < 0
            ? '<span style="color:#dc2626;font-weight:600;">' + (v||0).toFixed(1) + '%</span>'
            : (v||0).toFixed(1) + '%';
        }
      },
      { title: "Material", field: "material", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Labor", field: "labor", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Equipment", field: "equipment", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Overhead", field: "overhead", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Total Cost", field: "total", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right", cssClass: "font-bold" },
      { title: "Cost/Test", field: "cost_per_test", formatter: "money", formatterParams: { precision: 2 }, hozAlign: "right" },
    ],
    placeholder: "No report data. Click Load Report or select a period.",
  });

  function formatMoney(val) {
    return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updateCards(summary) {
    var s = summary || {};
    var html =
      '<div class="car-card"><div class="car-card-label">Total Tests</div><div class="car-card-value">' + (s.total_tests || 0) + '</div><div class="car-card-sub">completed in period</div></div>' +
      '<div class="car-card"><div class="car-card-label">Total Material</div><div class="car-card-value">' + formatMoney(s.total_material) + '</div><div class="car-card-sub">reagent costs</div></div>' +
      '<div class="car-card"><div class="car-card-label">Total Labor</div><div class="car-card-value">' + formatMoney(s.total_labor) + '</div><div class="car-card-sub">salaries + overtime</div></div>' +
      '<div class="car-card"><div class="car-card-label">Total Equipment</div><div class="car-card-value">' + formatMoney(s.total_equipment) + '</div><div class="car-card-sub">depreciation + maintenance</div></div>' +
      '<div class="car-card"><div class="car-card-label">Total Overhead</div><div class="car-card-value">' + formatMoney(s.total_overhead) + '</div><div class="car-card-sub">utilities + rent + etc</div></div>' +
      '<div class="car-card"><div class="car-card-label">Grand Total</div><div class="car-card-value" style="color:#2563eb;">' + formatMoney(s.grand_total) + '</div><div class="car-card-sub">all cost components</div></div>';
    document.getElementById('summary-cards').innerHTML = html;
  }

  window.loadReport = function () {
    var dateFrom = document.getElementById('date_from').value;
    var dateTo = document.getElementById('date_to').value;
    var periodId = document.getElementById('period_select').value;

    var url = 'api/get_cost_accounting_report.php?date_from=' + encodeURIComponent(dateFrom) + '&date_to=' + encodeURIComponent(dateTo);
    if (periodId) url += '&period_id=' + periodId;

    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          alert(data.message || 'Failed to load report');
          return;
        }
        updateCards(data.summary);
        table.setData(data.rows || []);
      })
      .catch(function (err) {
        console.error('Report load error:', err);
        alert('Failed to load report data.');
      });
  };

  window.exportReport = function () {
    table.download("xlsx", "CostAccountingReport.xlsx", { sheetName: "Cost Accounting" });
  };

  document.getElementById('period_select').addEventListener('change', function () {
    loadReport();
  });

  if (document.getElementById('period_select').value) {
    loadReport();
  }
})();
