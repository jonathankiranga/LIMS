<?php
include('includes/session.inc');
$Title = _('SPREADSHEETS');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/chartbalancing.inc');
include('includes/AccountBalance.inc');
include('includes/GetBalance_withfilter.inc');

// Fetch period list for dropdown
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
<link rel="stylesheet" href="<?php echo $RootPath; ?>/css/AccountBalances.css" />

<div class="ab-toolbar">
  <label for="period-select">Period:</label>
  <select id="period-select"><?php echo $periodOptions; ?></select>

  <button id="btn-save" class="btn-success">Save</button>

  <span style="flex:1;"></span>

  <span class="view-group">
    <input type="radio" name="view" id="view-raw" value="raw" checked>
    <label for="view-raw">Raw Data</label>
    <input type="radio" name="view" id="view-pivot" value="pivot">
    <label for="view-pivot">Pivot</label>
  </span>

  <button id="btn-print" class="btn-secondary">Print</button>
  <button id="btn-export-csv" class="btn-secondary">CSV</button>
  <button id="btn-export-excel" class="btn-secondary">Excel</button>
  <button id="btn-refresh" class="btn-primary">Refresh Data</button>
</div>

<div id="ab-status" class="ab-status-info">Loading...</div>

<div id="calc-sheet"></div>
<div id="pivot-container" style="display:none;"></div>

<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/js/tabulator.min.js"></script>
<script src="https://d3js.org/d3.v5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/c3/0.7.20/c3.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/c3_renderers.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
var AB_CONFIG = <?php echo json_encode(['currentPeriod' => $currentPeriod, 'currentPeriodLabel' => $currentPeriodLabel, 'currentUserId' => $_SESSION['UserID'] ?? '']); ?>;
</script>
<script src="<?php echo $RootPath; ?>/javascripts/AccountBalances.js?v=tabulator"></script>
<?php include('includes/footer.inc'); ?>
