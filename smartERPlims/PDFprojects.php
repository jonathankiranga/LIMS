<?php
include('includes/session.inc');
include('includes/AccountBudgets.inc');
$Title = _('Project Budgets');
include('includes/header.inc');

$PeriodID = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/css/tabulator.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.css" />
<style>
.pb-toolbar {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  padding: 16px 20px;
  margin: 4px 4px 8px 4px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  border: 1px solid #e2e8f0;
}
.pb-toolbar label {
  font-weight: 600;
  font-size: 13px;
  color: #475569;
}
.pb-toolbar select {
  padding: 7px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 14px;
  background: #fff;
  color: #1e293b;
  outline: none;
}
.pb-toolbar select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}
.pb-btn {
  padding: 8px 18px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.pb-btn-primary { background: #2563eb; color: #fff; }
.pb-btn-primary:hover { background: #1d4ed8; }
.pb-btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.pb-btn-secondary:hover { background: #e2e8f0; }
.pb-btn-success { background: #16a34a; color: #fff; }
.pb-btn-success:hover { background: #15803d; }
.pb-sep { width: 1px; height: 28px; background: #e2e8f0; margin: 0 4px; }
#pb-sheet {
  margin: 4px 4px 8px 4px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
#pivot-container {
  margin: 4px 4px 8px 4px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  background: #fff;
  display: none;
}
.pb-summary {
  margin: 4px 4px 12px 4px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}
.pb-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 14px 16px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.pb-card-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.pb-card-value { font-size: 22px; color: #1e293b; font-weight: 700; margin-top: 4px; }
</style>

<div class="pb-toolbar">
  <label for="period-select">Period:</label>
  <select id="period-select">
    <option value="">-- Select Period --</option>
    <?php
    $firstPeriod = null;
    $pResult = DB_query("SELECT periodno, MIN(start_date) AS year_start, MAX(end_date) AS year_end, CONCAT(YEAR(MIN(start_date)), ' Financial Year') AS label FROM financialperiods GROUP BY periodno ORDER BY periodno DESC", $db);
    while ($pRow = DB_fetch_array($pResult)) {
      if ($firstPeriod === null) $firstPeriod = $pRow['periodno'];
      $sel = ($pRow['periodno'] == ($PeriodID ?: $firstPeriod)) ? 'selected' : '';
      echo '<option value="'.$pRow['periodno'].'" '.$sel.'>'.$pRow['year_start'].' to '.$pRow['year_end'].'</option>';
    }
    ?>
  </select>

  <span class="view-group">
    <input type="radio" name="pb-view" id="view-raw" value="raw" checked>
    <label for="view-raw">Raw Data</label>
    <input type="radio" name="pb-view" id="view-pivot" value="pivot">
    <label for="view-pivot">Pivot</label>
  </span>

  <div class="pb-sep"></div>

  <button id="btn-print" class="pb-btn pb-btn-secondary">Print</button>
  <button id="btn-export-csv" class="pb-btn pb-btn-secondary">CSV</button>
  <button id="btn-export-excel" class="pb-btn pb-btn-success">Excel</button>
  <button id="btn-refresh" class="pb-btn pb-btn-primary">Refresh</button>
</div>

<div class="pb-summary" id="pb-summary"></div>

<div id="pb-sheet"></div>
<div id="pivot-container"></div>

<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/js/tabulator.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.slim.min.js"></script>
<script src="https://d3js.org/d3.v5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/c3/0.7.20/c3.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/c3_renderers.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
var PB_CONFIG = <?php echo json_encode([
    'rootPath' => $RootPath,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="<?php echo $RootPath; ?>/javascripts/PDFprojects.js"></script>
<?php include('includes/footer.inc'); ?>
