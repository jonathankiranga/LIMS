(function () {
  var cfg = DEPN_CONFIG || {};
  var data = cfg.data || [];
  var rootPath = cfg.rootPath || '';

  var table = new Tabulator("#depn-table", {
    data: data,
    layout: "fitColumns",
    groupBy: "categorydescription",
    groupHeader: function (value, count, data, group) {
      return value + ' <span style="margin-left:12px;color:#64748b;font-weight:400;">(' + count + ' asset(s))</span>';
    },
    groupClosedShowCalcs: true,
    columns: [
      { title: "Asset ID", field: "assetid", width: 80, frozen: true, hozAlign: "right" },
      { title: "Description", field: "description", minWidth: 180 },
      { title: "Date Purchased", field: "transdate", width: 120 },
      { title: "Cost", field: "costtotal", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Accum Depn", field: "depnbfwd", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Book Value", field: "bookvalue", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
      { title: "Depn Type", field: "depntype_label", width: 90, hozAlign: "center" },
      { title: "Depn Rate", field: "depnrate", width: 90, hozAlign: "right" },
      { title: "New Depn", field: "newdepn", formatter: "money", formatterParams: { precision: 2 }, bottomCalc: "sum", bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 2 }, hozAlign: "right" },
    ],
    rowFormatter: function (row) {
      var data = row.getData();
      if (data.newdepn === 0) {
        row.getElement().style.color = '#94a3b8';
      }
    },
  });

  document.getElementById('exportExcelBtn').addEventListener('click', function () {
    table.download("xlsx", "Depreciation.xlsx", { sheetName: "Depreciation" });
  });
})();
