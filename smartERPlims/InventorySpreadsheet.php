<?php
include('includes/session.inc');
$Title = _('Inventory Spreadsheet');
include('includes/header.inc');

$periodsResult = DB_query("SELECT periodno, MIN(start_date) AS year_start, MAX(end_date) AS year_end, CONCAT(YEAR(MIN(start_date)), ' Financial Year') AS label FROM financialperiods GROUP BY periodno ORDER BY periodno DESC", $db);
$periodOptions = '';
$currentPeriod = 0;
$currentPeriodLabel = '';
while ($pRow = DB_fetch_array($periodsResult)) {
  $pno = $pRow['periodno'];
  $pStart = $pRow['year_start'];
  $pEnd = $pRow['year_end'];
  $selected = '';
  if ($currentPeriod === 0) {
    $currentPeriod = $pno;
    $currentPeriodLabel = "$pStart to $pEnd";
    $selected = 'selected';
  }
  $periodOptions .= "<option value=\"$pno\" $selected>$pStart to $pEnd</option>";
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/css/tabulator.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/c3/0.7.20/c3.min.css" />
<link rel="stylesheet" href="<?php echo $RootPath; ?>/css/InventorySpreadsheet.css" />

<div class="is-toolbar">
  <label for="is-period-select">Period:</label>
  <select id="is-period-select"><?php echo $periodOptions; ?></select>

  <button id="is-btn-save" class="btn-success">Save</button>

  <span style="flex:1;"></span>

  <span class="view-group">
    <input type="radio" name="is-view" id="is-view-raw" value="raw" checked>
    <label for="is-view-raw">Raw Data</label>
    <input type="radio" name="is-view" id="is-view-pivot" value="pivot">
    <label for="is-view-pivot">Pivot</label>
  </span>

  <button id="is-btn-print" class="btn-secondary">Print</button>
  <button id="is-btn-csv" class="btn-secondary">CSV</button>
  <button id="is-btn-excel" class="btn-secondary">Excel</button>
  <button id="is-btn-refresh" class="btn-primary">Refresh Data</button>
</div>

<div id="is-status" class="is-status-info">Loading...</div>

<div id="is-sheet"></div>
<div id="is-pivot-container" style="display:none;"></div>

<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/js/tabulator.min.js"></script>
<script src="https://d3js.org/d3.v5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/c3/0.7.20/c3.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/c3_renderers.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
var IS_CONFIG = <?php echo json_encode(['currentPeriod' => $currentPeriod, 'currentPeriodLabel' => $currentPeriodLabel, 'currentUserId' => $_SESSION['UserID'] ?? '']); ?>;
</script>
<script src="<?php echo $RootPath; ?>/javascripts/InventorySpreadsheet.js?v=1"></script>
<?php include('includes/footer.inc'); ?>
