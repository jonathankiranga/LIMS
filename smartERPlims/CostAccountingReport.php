<?php
include('includes/session.inc');
$Title = _('Cost Accounting Report');
include('includes/header.inc');

$PeriodID = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;
$DateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$DateTo   = isset($_GET['date_to'])   ? $_GET['date_to']   : date('Y-m-t');

echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/manufacture.png" title="' . _('Manufacturing') .'" alt="" />'
    . ' ' . _('Cost Accounting Report') . '</p>';
?>
<style>
.car-toolbar {
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
.car-toolbar label {
  font-weight: 600;
  font-size: 13px;
  color: #475569;
  margin-right: -4px;
}
.car-toolbar input[type="date"] {
  padding: 7px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 14px;
  background: #fff;
  color: #1e293b;
  outline: none;
}
.car-toolbar input[type="date"]:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}
.car-btn {
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
.car-btn-primary { background: #2563eb; color: #fff; }
.car-btn-primary:hover { background: #1d4ed8; }
.car-btn-success { background: #16a34a; color: #fff; }
.car-btn-success:hover { background: #15803d; }
.car-sep { width: 1px; height: 28px; background: #e2e8f0; margin: 0 4px; }
#report-table {
  margin: 4px 4px 8px 4px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.car-summary {
  margin: 4px 4px 12px 4px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}
.car-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 14px 16px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.car-card-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.car-card-value { font-size: 22px; color: #1e293b; font-weight: 700; margin-top: 4px; }
.car-card-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
</style>

<div class="car-toolbar">
  <label>From</label>
  <input type="date" id="date_from" value="<?php echo $DateFrom; ?>">
  <label>To</label>
  <input type="date" id="date_to" value="<?php echo $DateTo; ?>">
  <div class="car-sep"></div>
  <button class="car-btn car-btn-primary" onclick="loadReport()">Load Report</button>
  <button class="car-btn car-btn-success" onclick="exportReport()">Export Excel</button>
  <div class="car-sep"></div>
  <select id="period_select" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:14px;">
    <option value="">-- Select Period --</option>
    <?php
    $pResult = DB_query("SELECT id, period_start, period_end, status, tests_completed FROM cost_allocation_periods ORDER BY period_start DESC LIMIT 20", $db);
    while ($pRow = DB_fetch_array($pResult)) {
        $sel = ($pRow['id'] == $PeriodID) ? 'selected' : '';
        echo '<option value="'.$pRow['id'].'" '.$sel.'>'.$pRow['period_start'].' to '.$pRow['period_end'].' ('.$pRow['status'].')</option>';
    }
    ?>
  </select>
</div>

<div class="car-summary" id="summary-cards"></div>

<div id="report-table"></div>

<script>
var CAR_CONFIG = <?php echo json_encode([
    'rootPath' => $RootPath,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="<?php echo $RootPath; ?>/javascripts/CostAccountingReport.js"></script>
<?php include('includes/footer.inc'); ?>
