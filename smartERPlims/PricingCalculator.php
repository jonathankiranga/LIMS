<?php
include('includes/session.inc');
$Title = _('Pricing Calculator');
include('includes/header.inc');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/plugins/css/pluginsCss.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/plugins/plugins.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/css/luckysheet.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/assets/iconfont/iconfont.css" />
<style>
:root {
  --pc-primary: #2563eb;
  --pc-primary-h: #1d4ed8;
  --pc-success: #16a34a;
  --pc-danger: #dc2626;
  --pc-bg: #f1f5f9;
  --pc-card: #ffffff;
  --pc-border: #e2e8f0;
  --pc-text: #1e293b;
  --pc-text2: #64748b;
  --pc-radius: 8px;
  --pc-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
}
.pc-toolbar {
  background: var(--pc-card);
  border-radius: var(--pc-radius);
  box-shadow: var(--pc-shadow);
  padding: 16px 20px;
  margin: 12px 4px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  border: 1px solid var(--pc-border);
}
.pc-toolbar label {
  font-weight: 600;
  font-size: 13px;
  color: var(--pc-text2);
  margin-right: -4px;
}
.pc-toolbar select, .pc-toolbar input[type="number"] {
  padding: 7px 12px;
  border: 1px solid var(--pc-border);
  border-radius: 6px;
  font-size: 14px;
  background: var(--pc-card);
  color: var(--pc-text);
  outline: none;
  min-width: 200px;
}
.pc-toolbar select:focus, .pc-toolbar input:focus {
  border-color: var(--pc-primary);
  box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}
.pc-toolbar input[type="number"] { min-width: 80px; width: 80px; }
.pc-price-info { order: 99; width: 100%; }
.pc-btn {
  padding: 7px 18px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.pc-btn-primary { background: var(--pc-primary); color: #fff; }
.pc-btn-primary:hover { background: var(--pc-primary-h); }
.pc-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.pc-btn-ghost {
  background: transparent;
  color: var(--pc-text2);
  border: 1px solid transparent;
  padding: 7px 12px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
}
.pc-btn-ghost:hover { background: #f1f5f9; color: var(--pc-text); }
.pc-btn-success { background: var(--pc-success); color: #fff; }
.pc-btn-success:hover { background: #15803d; }
.pc-btn-outline {
  background: transparent;
  color: var(--pc-text);
  border: 1px solid var(--pc-border);
}
.pc-btn-outline:hover { background: var(--pc-bg); }
.pc-btn-danger { background: var(--pc-danger); color: #fff; }
.pc-btn-danger:hover { background: #b91c1c; }
#calc-sheet {
  margin: 4px 4px 8px 4px;
  border-radius: var(--pc-radius);
  box-shadow: var(--pc-shadow);
  border: 1px solid var(--pc-border);
  overflow: hidden;
  height: 480px;
  flex: 1;
  min-height: 300px;
}
.pc-status {
  margin: 0 4px 12px 4px;
  padding: 10px 16px;
  border-radius: var(--pc-radius);
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.pc-status-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.pc-status-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.pc-status-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.pc-sep { width: 1px; height: 28px; background: var(--pc-border); margin: 0 4px; }

/* Tooltip positioned far right of active cell */
.luckysheet-tooltip {
    left: auto !important;
    right: 10px !important;
    transform: none !important;
    max-width: 300px;
    text-align: left;
}
.luckysheet-tooltip .luckysheet-tooltip-content {
    white-space: nowrap;
    overflow: visible;
}

/* Highlight the selected price cell */
.luckysheet-cell.price-cell {
  border: 2px solid #16a34a !important;
  box-shadow: inset 0 0 0 1px #16a34a;
}

/* Position formula popups far right so they don't block cell entry */
#luckysheet-formula-search-c,
.luckysheet-helpbox,
.luckysheet-arguments {
  left: auto !important;
  right: 10px !important;
  bottom: 40px !important;
  max-height: 60vh !important;
}

/* Responsive / iframe modal adjustments */
@media (max-width: 768px) {
  .pc-toolbar { padding: 12px; gap: 8px; }
  .pc-toolbar select { min-width: 140px; }
  .pc-toolbar input[type="number"] { width: 70px; }
  .pc-btn { padding: 6px 14px; font-size: 13px; }
  .pc-price-info { gap: 12px; font-size: 12px; }
}

/* Modal */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,0.55);
  z-index: 9999; display: flex; align-items: center; justify-content: center;
  opacity: 0; visibility: hidden; transition: all 0.2s;
}
.modal-overlay.active { opacity: 1; visibility: visible; }
.modal-card {
  background: var(--pc-card);
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
  width: 780px; max-width: 92vw;
  max-height: 88vh; display: flex; flex-direction: column;
  transform: scale(0.95) translateY(8px);
  transition: transform 0.2s;
}
.modal-overlay.active .modal-card { transform: scale(1) translateY(0); }
.modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--pc-border);
  display: flex; justify-content: space-between; align-items: center;
}
.modal-header h3 { margin: 0; font-size: 17px; color: var(--pc-text); }
.modal-header .test-badge {
  font-size: 12px; color: var(--pc-text2);
  background: var(--pc-bg); padding: 3px 10px; border-radius: 4px;
}
.modal-close {
  width: 32px; height: 32px; display: flex; align-items: center;
  justify-content: center; border-radius: 6px; cursor: pointer;
  font-size: 22px; color: var(--pc-text2); transition: 0.15s;
  border: none; background: none; line-height: 1;
}
.modal-close:hover { background: var(--pc-bg); color: var(--pc-text); }
.modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
.modal-footer {
  padding: 14px 24px;
  border-top: 1px solid var(--pc-border);
  display: flex; justify-content: flex-end; gap: 8px;
}
.mm-add-row {
  display: flex; gap: 10px; align-items: center;
  margin-bottom: 16px; padding: 12px 16px;
  background: var(--pc-bg); border-radius: 8px;
  flex-wrap: wrap;
}
.mm-add-row select {
  flex: 1; min-width: 200px; padding: 7px 12px;
  border: 1px solid var(--pc-border); border-radius: 6px;
  font-size: 14px; background: var(--pc-card); color: var(--pc-text);
}
.mm-add-row input[type="number"] {
  width: 100px; padding: 7px 12px;
  border: 1px solid var(--pc-border); border-radius: 6px;
  font-size: 14px;
}
.mm-table {
  width: 100%; border-collapse: collapse;
  font-size: 14px;
}
.mm-table th {
  text-align: left; padding: 10px 12px;
  background: var(--pc-bg); color: var(--pc-text2);
  font-weight: 600; font-size: 12px; text-transform: uppercase;
  letter-spacing: 0.04em; border-bottom: 2px solid var(--pc-border);
}
.mm-table td {
  padding: 10px 12px; border-bottom: 1px solid var(--pc-border);
  color: var(--pc-text);
}
.mm-table tr:hover td { background: #f8fafc; }
.mm-table .empty-row td {
  text-align: center; color: var(--pc-text2);
  padding: 30px 12px; font-style: italic;
}
.mm-qty-input {
  width: 72px; padding: 4px 8px;
  border: 1px solid var(--pc-border); border-radius: 4px;
  font-size: 13px; text-align: center;
}
.mm-qty-input:focus { border-color: var(--pc-primary); outline: none; }
.mm-del-btn {
  background: none; border: none; color: var(--pc-danger);
  cursor: pointer; font-size: 18px; padding: 4px 8px;
  border-radius: 4px; transition: 0.15s;
}
.mm-del-btn:hover { background: #fef2f2; }
.pc-price-info {
  display: flex; gap: 20px; flex-wrap: wrap;
  margin-left: auto;
}
.pc-price-info span {
  font-size: 13px; color: var(--pc-text2);
}
.pc-price-info strong {
  color: var(--pc-text); font-size: 15px;
}
.pc-spinner {
  width: 18px; height: 18px;
  border: 2px solid var(--pc-border);
  border-top-color: var(--pc-primary);
  border-radius: 50%;
  animation: pc-spin 0.6s linear infinite;
  display: inline-block;
}
@keyframes pc-spin { to { transform: rotate(360deg); } }
</style>

<div class="pc-toolbar">
  <label for="test-selector">Test</label>
  <select id="test-selector">
    <option value="">-- Select a Lab Test --</option>
  </select>

  <div class="pc-sep"></div>

  <label for="markup-input">Markup</label>
  <input type="number" id="markup-input" value="50" min="0" max="1000" step="0.5" />
  <span style="font-size:13px;color:var(--pc-text2);margin-left:-6px;">%</span>

  <div class="pc-sep"></div>

  <button class="pc-btn pc-btn-outline" id="pick-price-btn" disabled title="Click then select a cell to mark as final price">
    &#36; Pick Price Cell
  </button>

  <button class="pc-btn pc-btn-primary" id="publish-btn" disabled>
    &#128230; Publish to PriceList
  </button>

  <button class="pc-btn pc-btn-outline" id="manage-reagents-btn" disabled>
    &#9881; Manage Reagents
  </button>

  <button class="pc-btn pc-btn-outline" id="regenerate-btn" style="display:none;">
    &#8635; Regenerate from Mappings
  </button>

  <button class="pc-btn pc-btn-ghost" id="fn-help-btn" title="Show function reference">&#63; f(x)</button>

  <div class="pc-price-info" id="price-info">
    <span>Base: <strong id="disp-base">—</strong></span>
    <span>Current PriceList: <strong id="disp-current">—</strong></span>
    <span>Final: <strong id="disp-final">—</strong></span>
  </div>
</div>

<div id="calc-sheet"></div>

<div id="pc-status" class="pc-status pc-status-info">
  <span>&#9432;</span>
  <span>Select a lab test to begin. The cost breakdown will appear below.</span>
</div>

<!-- Mapping Modal -->
<div class="modal-overlay" id="mapping-modal">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h3>Manage Reagent Mappings</h3>
        <span class="test-badge" id="mm-test-badge">No test selected</span>
      </div>
      <button class="modal-close" id="mm-close-btn">&times;</button>
    </div>
    <div class="modal-body">
      <div class="mm-csv-row" style="display:flex;gap:8px;margin-bottom:10px;align-items:center;">
        <a class="pc-btn pc-btn-outline" id="mm-export-btn" href="#" style="text-decoration:none;font-size:13px;">&#8595; Download CSV</a>
        <label class="pc-btn pc-btn-outline" style="font-size:13px;cursor:pointer;">&#8613; Import CSV
          <input type="file" id="mm-import-input" accept=".csv" style="display:none" />
        </label>
        <span id="mm-import-status" style="font-size:13px;color:var(--pc-text2);"></span>
      </div>
      <div class="mm-add-row">
        <select id="mm-reagent-select">
          <option value="">-- Select Reagent --</option>
        </select>
        <input type="number" id="mm-qty" placeholder="Qty" min="0.001" step="0.001" value="1" />
        <button class="pc-btn pc-btn-primary" id="mm-add-btn">+ Add</button>
        <span id="mm-add-status" style="font-size:13px;color:var(--pc-text2);"></span>
      </div>
      <div style="overflow-x:auto;">
        <table class="mm-table" id="mm-table">
          <thead>
            <tr>
              <th style="width:40%;">Reagent</th>
              <th style="width:15%;">Qty</th>
              <th style="width:15%;">Unit Cost</th>
              <th style="width:15%;">Subtotal</th>
              <th style="width:10%;"></th>
            </tr>
          </thead>
          <tbody id="mm-tbody">
            <tr class="empty-row"><td colspan="5">No reagents mapped. Add one above.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <span id="mm-total-label" style="font-size:15px;font-weight:600;margin-right:auto;color:var(--pc-text);">Base Cost: <strong id="mm-total-cost">0.00</strong></span>
      <button class="pc-btn pc-btn-outline" id="mm-done-btn">Done</button>
    </div>
  </div>
</div>

<!-- Function Reference Modal -->
<div class="modal-overlay" id="fn-modal">
  <div class="modal-card" style="max-width:560px;">
    <div class="modal-header">
      <h3>Formula Reference</h3>
      <button class="modal-close" id="fn-close-btn">&times;</button>
    </div>
    <div class="modal-body" style="font-size:13px;line-height:1.8;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:2px solid var(--pc-border);">
            <th style="text-align:left;padding:4px 8px;color:var(--pc-text2);">Function</th>
            <th style="text-align:left;padding:4px 8px;color:var(--pc-text2);">Description</th>
          </tr>
        </thead>
        <tbody>
          <tr><td style="padding:4px 8px;font-family:monospace;">=SUM(C2:C20)</td><td style="padding:4px 8px;">Sum a range of values</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=AVERAGE(C2:C20)</td><td style="padding:4px 8px;">Average a range of values</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=MIN(C2:C20)</td><td style="padding:4px 8px;">Minimum value in a range</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=MAX(C2:C20)</td><td style="padding:4px 8px;">Maximum value in a range</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=IF(A2>0, B2*C2, 0)</td><td style="padding:4px 8px;">Conditional logic</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=ROUND(D2, 2)</td><td style="padding:4px 8px;">Round to N decimal places</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=B2*C2</td><td style="padding:4px 8px;">Multiply (subtotal = qty &times; cost)</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=C2*1.1</td><td style="padding:4px 8px;">Add 10% markup to a value</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=SUM(D2:D20)+SUM(E2:E20)</td><td style="padding:4px 8px;">Sum across non-adjacent ranges</td></tr>
          <tr><td style="padding:4px 8px;font-family:monospace;">=D2+$F$1</td><td style="padding:4px 8px;">Absolute reference to cell F1</td></tr>
        </tbody>
      </table>
      <p style="margin:12px 0 0;color:var(--pc-text2);font-size:12px;">Type <code>=</code> in a cell to start a formula. Luckysheet supports most common spreadsheet functions.</p>
    </div>
  </div>
</div>

<?php
echo '<script src="https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/plugins/js/plugin.js"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/luckysheet.umd.js"></script>';
?>
<script>
(function() {
  var currentTest = null;
  var currentData = null;
  var sheetReady = false;
  var sheetPopulated = false;
  var finalPriceRow = -1;
  var markupRow = -1;
  var hasSavedSheet = false;
  var saveTimer = null;
  var reagentCostMap = {};
  var currentUserId = '<?php echo $_SESSION['UserID']; ?>';

  var preselectTest = '<?php echo addslashes(trim($_GET['itemcode'] ?? '')); ?>';

  var testSelector = document.getElementById('test-selector');
  var markupInput = document.getElementById('markup-input');
  var publishBtn = document.getElementById('publish-btn');
  var pickPriceBtn = document.getElementById('pick-price-btn');
  var manageReagentsBtn = document.getElementById('manage-reagents-btn');
  var regenerateBtn = document.getElementById('regenerate-btn');
  var statusEl = document.getElementById('pc-status');
  var dispBase = document.getElementById('disp-base');
  var dispCurrent = document.getElementById('disp-current');
  var dispFinal = document.getElementById('disp-final');

  function setStatus(type, msg) {
    statusEl.className = 'pc-status pc-status-' + type;
    var icon = type === 'info' ? '&#9432;' : type === 'success' ? '&#10003;' : '&#9888;';
    statusEl.innerHTML = '<span>' + icon + '</span><span>' + msg + '</span>';
  }

  function clearAllCells() {
    for (var r = 0; r < 20; r++) {
      for (var c = 0; c < 4; c++) {
        try { luckysheet.setCellValue(r, c, ''); } catch(e) {}
      }
    }
    reagentCostMap = {};
  }

  function getCellRaw(r, c) {
    try {
      var sheet = luckysheet.getSheet();
      if (sheet && sheet.flowdata && sheet.flowdata[r] && sheet.flowdata[r][c]) {
        return sheet.flowdata[r][c];
      }
    } catch(e) {}
    return null;
  }

  function scanSheetLayout() {
    markupRow = -1;
    for (var r = 0; r < 20; r++) {
      try {
        var val = luckysheet.getCellValue(r, 0);
        if (val === 'Markup %') markupRow = r;
      } catch(e) {}
    }
  }

  function restoreSavedSheet(data) {
    clearAllCells();
    // Support both legacy flat array and new { calculator, reagent_costs } format
    var cells = data;
    var savedCosts = null;
    if (data && data.calculator) {
      cells = data.calculator;
      savedCosts = data.reagent_costs || null;
    }
    if (!cells || !cells.length) return;
    for (var i = 0; i < cells.length; i++) {
      var cell = cells[i];
      if (cell.r === undefined || cell.c === undefined) continue;
      var obj = {};
      if (cell.v !== undefined && cell.v !== null) obj.v = cell.v;
      if (cell.f) obj.f = cell.f;
      if (cell.bl) obj.bl = cell.bl;
      if (cell.bg) obj.bg = cell.bg;
      if (cell.fc) obj.fc = cell.fc;
      if (Object.keys(obj).length === 0) obj = '';
      try { luckysheet.setCellValue(cell.r, cell.c, obj); } catch(e) {}
    }
    // Restore saved reagent costs map
    if (savedCosts) {
      reagentCostMap = {};
      for (var j = 0; j < savedCosts.length; j++) {
        reagentCostMap[savedCosts[j][0]] = savedCosts[j][1];
      }
    }
    // Trigger formula engine to register all formulas for live calculation
    setTimeout(function() {
      for (var r = 0; r < 20; r++) {
        try {
          var raw = getCellRaw(r, 2);
          if (raw && raw.v !== undefined && raw.v !== null && raw.v !== '') {
            luckysheet.setCellValue(r, 2, parseFloat(raw.v));
          }
        } catch(e) {}
      }
    }, 200);
    scanSheetLayout();
    sheetPopulated = true;
    publishBtn.disabled = false;
  }

  function rebuildLookupTable(mappings) {
    reagentCostMap = {};
    if (!mappings || !mappings.length) return;
    mappings.forEach(function(m) { reagentCostMap[m.reagent_descrip] = m.unit_cost; });
  }

  function syncCostsFromLookup() {
    if (!Object.keys(reagentCostMap).length) return;
    for (var r = 0; r < 20; r++) {
      try {
        var name = luckysheet.getCellValue(r, 0);
        if (name && reagentCostMap[name] !== undefined) {
          luckysheet.setCellValue(r, 2, reagentCostMap[name]);
        }
      } catch(e) {}
    }
  }

  function populateFromMappings(data) {
    clearAllCells();
    currentData = data;
    var mappings = data.mappings || [];

    luckysheet.setCellValue(0, 0, 'Reagent');
    luckysheet.setCellValue(0, 1, 'Qty');
    luckysheet.setCellValue(0, 2, 'Cost/Unit');
    luckysheet.setCellValue(0, 3, 'Subtotal');

    for (var i = 0; i < mappings.length; i++) {
      var row = i + 1;
      var m = mappings[i];
      luckysheet.setCellValue(row, 0, m.reagent_descrip);
      luckysheet.setCellValue(row, 1, m.qty);
      luckysheet.setCellValue(row, 2, m.unit_cost);
      luckysheet.setCellValue(row, 3, { v: m.subtotal, f: '=B' + (row + 1) + '*C' + (row + 1) });
    }

    var lastDataRow = mappings.length;
    var baseRow = lastDataRow + 1;
    luckysheet.setCellValue(baseRow, 0, { v: 'Base Cost', bl: 1 });
    if (mappings.length > 0) {
      luckysheet.setCellValue(baseRow, 3, { v: data.base_cost || 0, f: '=SUM(D2:D' + (lastDataRow + 1) + ')' });
    } else {
      luckysheet.setCellValue(baseRow, 3, 0);
    }

    var sepRow = baseRow + 1;
    for (var c = 0; c < 4; c++) {
      luckysheet.setCellValue(sepRow, c, '');
    }

    markupRow = sepRow + 1;
    var markupVal = parseFloat(markupInput.value) || 50;
    luckysheet.setCellValue(markupRow, 0, 'Markup %');
    luckysheet.setCellValue(markupRow, 1, markupVal);

    var markupAmtRow = markupRow + 1;
    luckysheet.setCellValue(markupAmtRow, 0, 'Markup Amount');
    luckysheet.setCellValue(markupAmtRow, 3, { v: '', f: '=D' + (baseRow + 1) + '*B' + (markupRow + 1) + '/100' });

    var finalRow = markupAmtRow + 1;
    finalPriceRow = finalRow;
    luckysheet.setCellValue(finalRow, 0, { v: 'Final Price', bl: 1, bg: '#2563eb', fc: '#ffffff' });
    luckysheet.setCellValue(finalRow, 3, { v: '', f: '=D' + (baseRow + 1) + '+D' + (markupAmtRow + 1), bg: '#dbeafe', bl: 1 });

    rebuildLookupTable(mappings);

    updatePriceDisplay();
    sheetPopulated = true;
    publishBtn.disabled = false;
    pickPriceBtn.disabled = false;
    setTimeout(function() {
      for (var r = 0; r < 20; r++) {
        try {
          var raw = getCellRaw(r, 2);
          if (raw && raw.v !== undefined && raw.v !== null && raw.v !== '') {
            luckysheet.setCellValue(r, 2, parseFloat(raw.v));
          }
        } catch(e) {}
      }
    }, 200);
  }

  function updatePriceDisplay() {
    if (!sheetReady || !currentData) return;
    try {
      var baseVal = readCellValue(currentData.mappings.length + 1, 3);
      dispBase.textContent = baseVal != null ? baseVal.toFixed(2) : (currentData.base_cost ? currentData.base_cost.toFixed(2) : '—');
    } catch(e) {
      dispBase.textContent = currentData.base_cost ? currentData.base_cost.toFixed(2) : '—';
    }
    dispCurrent.textContent = currentData.current_pricelist_price != null ? currentData.current_pricelist_price.toFixed(2) : '—';
    if (finalPriceRow >= 0) {
      try {
        var finalVal = readCellValue(finalPriceRow, 3);
        dispFinal.textContent = finalVal != null ? finalVal.toFixed(2) : '—';
      } catch(e) {
        dispFinal.textContent = '—';
      }
    }
  }

  function autoSave() {
    if (!currentTest || !sheetReady) return;
    var cells = [];
    for (var r = 0; r < 20; r++) {
      for (var c = 0; c < 4; c++) {
        try {
          var raw = getCellRaw(r, c);
          if (raw) {
            var entry = { r: r, c: c, v: raw.v !== undefined ? raw.v : '' };
            if (raw.f) entry.f = raw.f;
            if (raw.bl) entry.bl = raw.bl;
            if (raw.bg) entry.bg = raw.bg;
            if (raw.fc) entry.fc = raw.fc;
            cells.push(entry);
          } else {
            var val = luckysheet.getCellValue(r, c);
            if (val !== null && val !== undefined && val !== '') {
              cells.push({ r: r, c: c, v: val });
            }
          }
        } catch(e) {}
      }
    }
    var costArr = [];
    for (var name in reagentCostMap) {
      costArr.push([name, reagentCostMap[name]]);
    }
    fetch('api/save_spreadsheet.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ test_itemcode: currentTest, sheet_data: { calculator: cells, reagent_costs: costArr }, user_id: currentUserId })
    }).catch(function() {});
  }

  function onSheetUpdated() {
    updatePriceDisplay();
    if (hasSavedSheet) {
      if (saveTimer) clearTimeout(saveTimer);
      saveTimer = setTimeout(autoSave, 3000);
    }
  }

  function loadTestList() {
    fetch('api/get_lab_tests.php')
      .then(function(r) { return r.json(); })
      .then(function(resp) {
        if (!resp.success) return;
        testSelector.innerHTML = '<option value="">-- Select a Lab Test --</option>';
        resp.data.forEach(function(t) {
          var opt = document.createElement('option');
          opt.value = t.itemcode;
          var label = t.descrip + ' (' + t.itemcode + ')';
          if (t.pricelist_price != null) label += ' - $' + t.pricelist_price.toFixed(2);
          opt.textContent = label;
          testSelector.appendChild(opt);
        });
      })
      .catch(function(err) {
        setStatus('error', 'Failed to load test list: ' + err.message);
      });
  }

  function loadTestData(testCode) {
    setStatus('info', 'Loading test data...');
    publishBtn.disabled = true;
    pickPriceBtn.disabled = true;
    manageReagentsBtn.disabled = true;
    regenerateBtn.style.display = 'none';
    dispBase.textContent = '—';
    dispCurrent.textContent = '—';
    dispFinal.textContent = '—';

    var urlMappings = 'api/get_test_mappings.php?test=' + encodeURIComponent(testCode);
    var urlSheet = 'api/get_spreadsheet.php?test=' + encodeURIComponent(testCode) + '&user=' + encodeURIComponent(currentUserId);

    var p1 = fetch(urlMappings).then(function(r) { return r.json(); });
    var p2 = fetch(urlSheet).then(function(r) { return r.json(); });

    Promise.all([p1, p2]).then(function(results) {
      var mappingsResp = results[0];
      var sheetResp = results[1];

      if (!mappingsResp.success) {
        setStatus('error', mappingsResp.message || 'Failed to load mappings');
        return;
      }

      currentData = mappingsResp.data;
      currentTest = testCode;
      manageReagentsBtn.disabled = false;

      if (!sheetReady) {
        return;
      }

      clearAllCells();

      var savedCells = null;
      if (sheetResp.success && sheetResp.data.sheet_data) {
        try { savedCells = JSON.parse(sheetResp.data.sheet_data); } catch(e) {}
      }

      // Support both legacy flat array and new { calculator, reagent_costs } format
      var hasSaved = savedCells && (Array.isArray(savedCells) ? savedCells.length > 0 : (savedCells.calculator && savedCells.calculator.length > 0));

      if (hasSaved) {
        restoreSavedSheet(savedCells);
        rebuildLookupTable(mappingsResp.data.mappings);
        syncCostsFromLookup();
        hasSavedSheet = true;
        regenerateBtn.style.display = '';
        setStatus('info', 'Restored saved sheet for <strong>' + mappingsResp.data.test.descrip + '</strong>');
      } else {
        populateFromMappings(mappingsResp.data);
        hasSavedSheet = false;
        regenerateBtn.style.display = '';
        var count = mappingsResp.data.mappings.length;
        setStatus('info', 'Populated from mappings for <strong>' + mappingsResp.data.test.descrip + '</strong> | ' + count + ' reagent' + (count !== 1 ? 's' : '') + ' mapped');
      }

      // Always editable - no permission checks
      try { luckysheet.setAllowEdit(true); } catch(e) {}

      updatePriceDisplay();
    }).catch(function(err) {
      setStatus('error', 'Failed to load test data: ' + err.message);
    });
  }

  function readCellValue(r, c) {
    var v = luckysheet.getCellValue(r, c);
    if (v != null && v !== '' && !isNaN(parseFloat(v))) return parseFloat(v);
    var cell = getCellRaw(r, c);
    if (cell) {
      if (cell.v != null && !isNaN(parseFloat(cell.v))) return parseFloat(cell.v);
      if (v != null) v = v.toString().replace(/[^0-9.\-]/g, '');
      if (v !== '' && !isNaN(parseFloat(v))) return parseFloat(v);
    }
    return null;
  }

  function publishPrice() {
    if (!currentTest || !sheetReady || finalPriceRow < 0) return;
    var priceNum = readCellValue(finalPriceRow, 3);
    if (priceNum == null || priceNum <= 0) {
      setStatus('error', 'Could not read a valid price from cell D' + (finalPriceRow + 1) + '. Got: ' + JSON.stringify(luckysheet.getCellValue(finalPriceRow, 3)));
      return;
    }
    var markupVal = parseFloat(markupInput.value) || 0;
    setStatus('info', 'Publishing price <strong>$' + priceNum.toFixed(2) + '</strong> to PriceList...');
    fetch('api/publish_price.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        test_itemcode: currentTest,
        price: priceNum,
        markup_pct: markupVal,
        units_code: 'PCS',
        qty: 1
      })
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
      if (resp.success) {
        setStatus('success', '&#10003; Price <strong>$' + priceNum.toFixed(2) + '</strong> published to PriceList for <strong>' + currentData.test.descrip + '</strong>');
        if (currentData) currentData.current_pricelist_price = priceNum;
        updatePriceDisplay();
        try { parent.postMessage({ action: 'pricePublished' }, '*'); } catch(e) {} // notify parent iframe
      } else {
        setStatus('error', resp.message || 'Publish failed');
      }
    })
    .catch(function(err) {
      setStatus('error', 'Publish failed: ' + err.message);
    });
  }

  // Modal
  var modal = document.getElementById('mapping-modal');
  var mmCloseBtn = document.getElementById('mm-close-btn');
  var mmDoneBtn = document.getElementById('mm-done-btn');
  var mmReagentSelect = document.getElementById('mm-reagent-select');
  var mmQty = document.getElementById('mm-qty');
  var mmAddBtn = document.getElementById('mm-add-btn');
  var mmTbody = document.getElementById('mm-tbody');
  var mmTotalCost = document.getElementById('mm-total-cost');
  var mmTestBadge = document.getElementById('mm-test-badge');
  var mmAddStatus = document.getElementById('mm-add-status');
  var mmExportBtn = document.getElementById('mm-export-btn');
  var mmImportInput = document.getElementById('mm-import-input');
  var mmImportStatus = document.getElementById('mm-import-status');

  function exportMappings() {
    if (!currentTest) return;
    mmExportBtn.href = 'api/export_mappings.php?test=' + encodeURIComponent(currentTest);
  }

  function importMappings(file) {
    if (!currentTest) { mmImportStatus.textContent = 'Select a test first'; mmImportStatus.style.color = 'var(--pc-danger)'; return; }
    var formData = new FormData();
    formData.append('csv_file', file);
    mmImportStatus.textContent = 'Importing...';
    mmImportStatus.style.color = 'var(--pc-text2)';
    fetch('api/import_mappings.php?test=' + encodeURIComponent(currentTest), {
      method: 'POST',
      body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
      if (resp.success) {
        mmImportStatus.textContent = resp.message; mmImportStatus.style.color = 'var(--pc-success)';
        refreshMappings();
      } else {
        mmImportStatus.textContent = resp.message || 'Import failed'; mmImportStatus.style.color = 'var(--pc-danger)';
      }
    })
    .catch(function() { mmImportStatus.textContent = 'Network error'; mmImportStatus.style.color = 'var(--pc-danger)'; });
  }

  function loadReagentList() {
    fetch('api/get_reagents.php')
      .then(function(r) { return r.json(); })
      .then(function(resp) {
        if (!resp.success) return;
        mmReagentSelect.innerHTML = '<option value="">-- Select Reagent --</option>';
        resp.data.forEach(function(r) {
          var opt = document.createElement('option');
          opt.value = r.itemcode;
          opt.textContent = r.descrip + ' (' + r.itemcode + ') - $' + r.unit_cost.toFixed(2) + '/' + r.units;
          mmReagentSelect.appendChild(opt);
        });
      });
  }

  function openManageModal() {
    if (!currentTest || !currentData) return;
    mmTestBadge.textContent = currentData.test.descrip + ' (' + currentTest + ')';
    updateModalTable();
    modal.classList.add('active');
  }

  function closeModal() { modal.classList.remove('active'); }

  function updateModalTable() {
    if (!currentData) return;
    var mappings = currentData.mappings || [];
    var html = '';
    var total = 0;
    if (mappings.length === 0) {
      html = '<tr class="empty-row"><td colspan="5">No reagents mapped. Add one above.</td></tr>';
    } else {
      mappings.forEach(function(m) {
        var subtotal = m.qty * m.unit_cost;
        total += subtotal;
        html += '<tr>' +
          '<td>' + m.reagent_descrip + '</td>' +
          '<td><input type="number" class="mm-qty-input" value="' + m.qty + '" step="0.001" min="0.001" data-id="' + m.id + '" data-code="' + m.reagent_itemcode + '" /></td>' +
          '<td>$' + m.unit_cost.toFixed(2) + '</td>' +
          '<td>$' + subtotal.toFixed(2) + '</td>' +
          '<td><button class="mm-del-btn" data-id="' + m.id + '" title="Remove">&times;</button></td>' +
        '</tr>';
      });
    }
    mmTbody.innerHTML = html;
    mmTotalCost.textContent = total.toFixed(2);

    mmTbody.querySelectorAll('.mm-qty-input').forEach(function(inp) {
      inp.addEventListener('change', function() {
        var mappingId = parseInt(this.dataset.id);
        var newQty = parseFloat(this.value);
        if (newQty > 0) updateMappingQuantity(mappingId, newQty);
      });
    });
    mmTbody.querySelectorAll('.mm-del-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        deleteMapping(parseInt(this.dataset.id));
      });
    });
  }

  function updateMappingQuantity(id, qty) {
    fetch('api/save_test_mapping.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'update_qty', id: id, qty: qty })
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
      if (resp.success) { refreshMappings(); }
      else { mmAddStatus.textContent = 'Update failed'; mmAddStatus.style.color = 'var(--pc-danger)'; }
    });
  }

  function deleteMapping(id) {
    fetch('api/save_test_mapping.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: id })
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
      if (resp.success) { refreshMappings(); }
      else { mmAddStatus.textContent = 'Delete failed'; mmAddStatus.style.color = 'var(--pc-danger)'; }
    });
  }

  function refreshMappings() {
    if (!currentTest) return;
    fetch('api/get_test_mappings.php?test=' + encodeURIComponent(currentTest))
      .then(function(r) { return r.json(); })
      .then(function(resp) {
        if (!resp.success) return;
        currentData = resp.data;
        updateModalTable();
        if (sheetReady) {
          rebuildLookupTable(resp.data.mappings);
          syncCostsFromLookup();
        }
      });
  }

  function addMapping() {
    var reagentCode = mmReagentSelect.value;
    var qty = parseFloat(mmQty.value);
    if (!reagentCode) { mmAddStatus.textContent = 'Select a reagent'; mmAddStatus.style.color = 'var(--pc-danger)'; return; }
    if (isNaN(qty) || qty <= 0) { mmAddStatus.textContent = 'Enter a valid quantity'; mmAddStatus.style.color = 'var(--pc-danger)'; return; }
    if (currentData && currentData.mappings) {
      var dup = currentData.mappings.some(function(m) { return m.reagent_itemcode === reagentCode; });
      if (dup) { mmAddStatus.textContent = 'Reagent already mapped for this test'; mmAddStatus.style.color = 'var(--pc-danger)'; return; }
    }
    mmAddStatus.textContent = 'Adding...';
    mmAddStatus.style.color = 'var(--pc-text2)';
    mmAddBtn.disabled = true;
    fetch('api/save_test_mapping.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add', test_itemcode: currentTest, reagent_itemcode: reagentCode, qty: qty })
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
      mmAddBtn.disabled = false;
      if (resp.success) {
        mmAddStatus.textContent = 'Added!'; mmAddStatus.style.color = 'var(--pc-success)';
        mmReagentSelect.value = ''; mmQty.value = '1';
        refreshMappings();
        setTimeout(function() { mmAddStatus.textContent = ''; }, 2000);
      } else {
        mmAddStatus.textContent = resp.message || 'Add failed'; mmAddStatus.style.color = 'var(--pc-danger)';
      }
    })
    .catch(function() { mmAddBtn.disabled = false; mmAddStatus.textContent = 'Network error'; mmAddStatus.style.color = 'var(--pc-danger)'; });
  }

  // Event handlers
  testSelector.addEventListener('change', function() {
    var code = this.value;
    if (!code) {
      currentTest = null;
      currentData = null;
      publishBtn.disabled = true;
      manageReagentsBtn.disabled = true;
      regenerateBtn.style.display = 'none';
      if (sheetReady) clearAllCells();
      dispBase.textContent = '—';
      dispCurrent.textContent = '—';
      dispFinal.textContent = '—';
      setStatus('info', 'Select a lab test to begin.');
      return;
    }
    loadTestData(code);
  });

  markupInput.addEventListener('input', function() {
    if (sheetReady && sheetPopulated && markupRow >= 0) {
      var val = parseFloat(this.value) || 0;
      try { luckysheet.setCellValue(markupRow, 1, val); } catch(e) {}
      setTimeout(updatePriceDisplay, 100);
    }
  });

  // Pick Price Cell mode
  var pickingPrice = false;
  var prevPriceRow = -1;
  function onCellClick(row, col) {
    if (!pickingPrice) return;
    pickingPrice = false;
    pickPriceBtn.textContent = '\u0024 Pick Price Cell';
    pickPriceBtn.className = 'pc-btn pc-btn-outline';
    document.body.style.cursor = '';
    finalPriceRow = row;
    prevPriceRow = row;
    var label = luckysheet.getCellValue(row, 0) || '';
    label = label.replace(/ \u2190 Price.*$/, '');
    try { luckysheet.setCellValue(row, 0, { v: label + ' \u2190 Price' }); } catch(e) {}
    var val = readCellValue(row, 3);
    var valStr = val != null ? '$' + val.toFixed(2) : '(empty)';
    setStatus('success', 'Price cell set to row ' + (row + 1) + '. Reading D' + (row + 1) + ' = ' + valStr);
    updatePriceDisplay();
  }
  pickPriceBtn.addEventListener('click', function() {
    if (!currentTest || !sheetReady) return;
    pickingPrice = !pickingPrice;
    if (pickingPrice) {
      pickPriceBtn.textContent = '\u2716 Cancel';
      pickPriceBtn.className = 'pc-btn pc-btn-danger';
      document.body.style.cursor = 'crosshair';
      setStatus('info', 'Click any cell in the sheet to mark it as the Price cell');
    } else {
      pickPriceBtn.textContent = '\u0024 Pick Price Cell';
      pickPriceBtn.className = 'pc-btn pc-btn-outline';
      document.body.style.cursor = '';
    }
  });
  // Detect cell clicks during pick mode via the updated hook
  function onPickCheck() {
    if (!pickingPrice) return;
    var sel = luckysheet.getluckysheet_select_save();
    if (sel && sel.length > 0) {
      var r = sel[0].row_focus;
      if (r >= 0) onCellClick(r, sel[0].column_focus);
    }
  }
  // Also listen on the sheet element for click
  document.addEventListener('mouseup', function(e) {
    if (!pickingPrice) return;
    var el = e.target;
    while (el) {
      if (el.id && el.id.indexOf('luckysheet-cell-main') >= 0) {
        setTimeout(onPickCheck, 50);
        break;
      }
      el = el.parentElement;
    }
  });

  publishBtn.addEventListener('click', publishPrice);
  manageReagentsBtn.addEventListener('click', openManageModal);
  regenerateBtn.addEventListener('click', function() {
    if (!currentTest || !currentData || !sheetReady) return;
    setStatus('info', 'Refreshing reagent costs...');
    fetch('api/get_test_mappings.php?test=' + encodeURIComponent(currentTest))
      .then(function(r) { return r.json(); })
      .then(function(resp) {
        if (resp.success) {
          currentData = resp.data;
          rebuildLookupTable(resp.data.mappings);
          syncCostsFromLookup();
          try { luckysheet.setAllowEdit(true); } catch(e) {}
          setStatus('info', 'Reagent costs refreshed. Calculator custom rows preserved.');
        } else {
          setStatus('error', resp.message || 'Failed to refresh costs');
        }
      })
      .catch(function(err) {
        setStatus('error', 'Failed to refresh costs: ' + err.message);
      });
  });
  mmCloseBtn.addEventListener('click', closeModal);
  mmDoneBtn.addEventListener('click', closeModal);

  // Function Reference modal
  var fnModal = document.getElementById('fn-modal');
  var fnHelpBtn = document.getElementById('fn-help-btn');
  var fnCloseBtn = document.getElementById('fn-close-btn');
  fnHelpBtn.addEventListener('click', function(e) {
    e.preventDefault();
    fnModal.classList.add('active');
  });
  function closeFnModal() { fnModal.classList.remove('active'); }
  fnCloseBtn.addEventListener('click', closeFnModal);
  fnModal.addEventListener('click', function(e) {
    if (e.target === fnModal) closeFnModal();
  });
  modal.addEventListener('click', function(e) {
    if (e.target === modal) closeModal();
  });
  mmAddBtn.addEventListener('click', addMapping);
  mmExportBtn.addEventListener('click', exportMappings);
  mmImportInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
      importMappings(this.files[0]);
      this.value = '';
    }
  });

  // Suppress browser shortcuts when spreadsheet is focused (Excel-like behavior)
  document.addEventListener('keydown', function(e) {
    if (!sheetReady) return;
    var el = e.target;
    var inSheet = false;
    while (el) { if (el.id === 'calc-sheet' || (el.id && el.id.indexOf('luckysheet') >= 0)) { inSheet = true; break; } el = el.parentElement; }
    if (!inSheet) return;
    var ctrl = e.ctrlKey || e.metaKey;
    if (ctrl && (e.key === 's' || e.key === 'f' || e.key === 'h' || e.key === 'p' || e.key === 'o')) e.preventDefault();
    if (e.key === 'F1' || e.key === 'F3' || e.key === 'F5' || e.key === 'F12') e.preventDefault();
  });

  // Init Luckysheet
  luckysheet.create({
    container: 'calc-sheet',
    column: 4,
    row: 20,
    lang: 'en',
    showtoolbar: false,
    showinfobar: false,
    allowEdit: true,
    columnWidth: [220, 70, 90, 110],
    enableAddRow: true,
    enableAddCol: true,
    showTooltip: true,
    hook: {
      updated: onSheetUpdated
    }
  });



  var wait = setInterval(function() {
    try {
      luckysheet.getCellValue(0, 0);
      clearInterval(wait);
      sheetReady = true;
      if (currentTest && currentData) {
        loadTestData(currentTest);
      } else if (preselectTest) {
        testSelector.value = preselectTest;
        loadTestData(preselectTest);
      }
    } catch(e) {}
  }, 200);

  loadTestList();
  loadReagentList();

  // Let Luckysheet manage its own focus; no tabindex on container (causes scroll-to-top on click)
})();
</script>
<?php include('includes/footer.inc'); ?>
