<?php
$PageSecurity = 0;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$RootPath = isset($RootPath) ? $RootPath : '.';
$userName = isset($_SESSION['UsersRealName']) ? $_SESSION['UsersRealName'] : 'User';
$accessLevel = isset($_SESSION['AccessLevel']) ? $_SESSION['AccessLevel'] : 0;
$roleName = 'User';
$rl = DB_query("SELECT secrolename FROM securityroles WHERE secroleid=".intval($accessLevel), $db);
if ($rl && DB_num_rows($rl) > 0) {
    $rr = DB_fetch_array($rl);
    $roleName = $rr['secrolename'];
}
$currentModule = isset($_GET['module']) ? $_GET['module'] : (isset($_SESSION['currentModule']) ? $_SESSION['currentModule'] : '');
$modulesEnabled = isset($_SESSION['ModulesEnabled']) ? $_SESSION['ModulesEnabled'] : array();
$moduleLabels = array('Approval'=>'Document Approvals','Sales'=>'Sales','AccountsReceivable'=>'Accounts Receivable','Inventory'=>'Inventory','cRM'=>'Customer Relations','Purchases'=>'Purchases','AccountsPayable'=>'Accounts Payable','CashManagement'=>'Cash Management','GeneralLedger'=>'General Ledger','FixedAssets'=>'Fixed Assets','system'=>'System Admin');
$moduleIcons = array('Approval'=>'fa-check-circle','Sales'=>'fa-receipt','AccountsReceivable'=>'fa-handshake','Inventory'=>'fa-warehouse','cRM'=>'fa-address-book','Purchases'=>'fa-shopping-cart','AccountsPayable'=>'fa-money-check-dollar','CashManagement'=>'fa-university','GeneralLedger'=>'fa-book','FixedAssets'=>'fa-building','system'=>'fa-cogs');
$moduleColors = array('Approval'=>'#e74c3c','Sales'=>'#875F7A','AccountsReceivable'=>'#3498db','Inventory'=>'#f39c12','cRM'=>'#1abc9c','Purchases'=>'#9b59b6','AccountsPayable'=>'#e67e22','CashManagement'=>'#2ecc71','GeneralLedger'=>'#34495e','FixedAssets'=>'#16a085','system'=>'#7f8c8d');
$globalAllowed = in_array($currentModule, array('CashManagement','GeneralLedger'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard</title>
<link rel="stylesheet" href="<?php echo $RootPath; ?>/css/fontawesome6.4.0.all.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; background: #f0f2f5; color: #0f172a; padding: 14px; min-height: 100vh; }
.dsh-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
.dsh-head h1 { font-size: 22px; font-weight: 700; margin: 0; }
.dsh-head h1 small { font-size: 13px; font-weight: 400; color: #64748b; margin-left: 8px; }
.dsh-head .dsh-meta { font-size: 13px; color: #475569; }
.dsh-tabs { display: flex; gap: 4px; margin-bottom: 14px; }
.dsh-tab { padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #475569; transition: all 0.15s; }
.dsh-tab:hover { background: #f1f5f9; }
.dsh-tab.active { background: #2563eb; color: #fff; border-color: #2563eb; }
.dsh-tab.active:hover { background: #1d4ed8; }
.dsh-loader { text-align: center; padding: 60px 20px; color: #64748b; font-size: 14px; }
.dsh-loader i { font-size: 32px; display: block; margin-bottom: 12px; color: #94a3b8; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 14px; }
.kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; box-shadow: 0 2px 6px rgba(15,23,42,0.04); }
.kpi-card .kpi-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.kpi-card .kpi-top i { font-size: 22px; }
.kpi-card .kpi-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1.3; }
.kpi-card .kpi-value { font-size: 26px; font-weight: 800; line-height: 1.1; margin-top: 4px; }
.kpi-card .kpi-note { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.panel-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 12px; }
.panel-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.panel-card .panel-head { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 10px 14px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.panel-card .panel-body { padding: 6px 14px; }
.panel-card .panel-body .empty { padding: 14px 0; color: #94a3b8; font-size: 13px; text-align: center; }
.item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.item-row:last-child { border-bottom: 0; }
.item-row .ir-main { display: flex; flex-direction: column; gap: 1px; }
.item-row .ir-main strong { font-weight: 600; color: #0f172a; }
.item-row .ir-main small { color: #64748b; font-size: 11px; }
.item-row .ir-side { text-align: right; white-space: nowrap; }
.pill { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.pill-green { background: #dcfce7; color: #166534; }
.pill-blue { background: #dbeafe; color: #1e40af; }
.pill-amber { background: #fef3c7; color: #92400e; }
.pill-red { background: #fee2e2; color: #991b1b; }
.pill-gray { background: #f1f5f9; color: #475569; }
.mgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
.mod-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 2px 8px rgba(15,23,42,0.04); }
.mod-card .mod-head { display: flex; align-items: center; gap: 10px; padding: 12px 14px; color: #fff; font-weight: 700; font-size: 14px; }
.mod-card .mod-body { padding: 10px 14px; }
.mod-card .mod-kpi { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; border-bottom: 1px solid #f1f5f9; }
.mod-card .mod-kpi:last-child { border-bottom: 0; }
.mod-card .mod-kpi span:last-child { font-weight: 700; }
</style>
</head>
<body>
<div class="dsh-head">
  <div>
    <h1>Welcome back, <?php echo htmlspecialchars($userName); ?> <small><?php echo htmlspecialchars($roleName); ?></small></h1>
  </div>
  <div class="dsh-meta"><i class="fas fa-clock"></i> <span id="dshClock"><?php echo date('D, d M Y H:i'); ?></span></div>
</div>

<div class="dsh-tabs">
  <div class="dsh-tab active" data-view="module" onclick="switchView('module')">
    <i class="fas fa-th-large"></i> Module View
  </div>
  <?php if ($globalAllowed) { ?><div class="dsh-tab" data-view="global" onclick="switchView('global')">
    <i class="fas fa-globe"></i> Global View
  </div><?php } ?>
</div>

<div id="kpiArea"><div class="dsh-loader"><i class="fas fa-spinner fa-spin"></i> Loading dashboard data...</div></div>

<script>
var rootPath = '<?php echo $RootPath; ?>';
var currentModule = '<?php echo htmlspecialchars($currentModule, ENT_QUOTES); ?>';
var currentView = 'module';
var cache = {};

function tickClock() {
  var el = document.getElementById('dshClock');
  if (el) el.textContent = new Date().toLocaleString();
}
setInterval(tickClock, 1000);

function switchView(view) {
  currentView = view;
  document.querySelectorAll('.dsh-tab').forEach(function(t) {
    t.classList.toggle('active', t.dataset.view === view);
  });
  renderKpis();
}

function statusPill(s) {
  if (!s) return '<span class="pill pill-gray">N/A</span>';
  var ls = String(s).toLowerCase();
  if (ls === 'open' || ls === 'pending' || ls === 'new') return '<span class="pill pill-amber">'+s+'</span>';
  if (ls === 'closed' || ls === 'completed' || ls === 'paid' || ls === 'ok') return '<span class="pill pill-green">'+s+'</span>';
  if (ls === 'overdue' || ls === 'disposed' || ls === 'inactive') return '<span class="pill pill-red">'+s+'</span>';
  return '<span class="pill pill-blue">'+s+'</span>';
}

function formatVal(v) {
  if (v === undefined || v === null || v === '') return '0';
  var n = parseFloat(String(v).replace(/,/g,''));
  if (isNaN(n)) return v;
  if (Number.isInteger(n) && n < 100000) return n.toLocaleString();
  if (n >= 1000 || Math.abs(n) < 0.01) return n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
  return n.toLocaleString();
}

function kpiIconColor(color) {
  var map = {blue:'#2563eb', green:'#16a34a', red:'#dc2626', orange:'#d97706', amber:'#d97706', purple:'#7c3aed'};
  return map[color] || '#64748b';
}

function renderCards(data) {
  if (!data || !data.kpis || !data.kpis.length) return '<div class="empty" style="text-align:center;padding:40px;color:#94a3b8;font-size:14px;">No data available for this module.</div>';
  var h = '<div class="kpi-grid">';
  data.kpis.forEach(function(k) {
    var color = kpiIconColor(k.color);
    h += '<div class="kpi-card">';
    h += '<div class="kpi-top"><span class="kpi-label">'+k.label+'</span><i class="fas '+k.icon+'" style="color:'+color+'"></i></div>';
    h += '<div class="kpi-value" style="color:'+color+'">'+formatVal(k.value)+'</div>';
    h += '</div>';
  });
  h += '</div>';

  var hasRecent = data.recent && data.recent.length;
  var hasQueue = data.queue && data.queue.length;
  if (hasRecent || hasQueue) {
    h += '<div class="panel-grid">';
    if (hasRecent) {
      h += '<div class="panel-card"><div class="panel-head"><i class="fas fa-history"></i> Recent Activity</div><div class="panel-body">';
      data.recent.forEach(function(r) {
        var vals = Object.values(r);
        var first = vals[0] || '';
        var second = vals[1] || '';
        h += '<div class="item-row"><div class="ir-main"><strong>'+first+'</strong><small>'+second+'</small></div></div>';
      });
      h += '</div></div>';
    }
    if (hasQueue) {
      h += '<div class="panel-card"><div class="panel-head"><i class="fas fa-list-check"></i> Queue</div><div class="panel-body">';
      data.queue.forEach(function(q) {
        h += '<div class="item-row"><span>'+q.label+'</span><span style="font-weight:700">'+q.count+'</span></div>';
      });
      h += '</div></div>';
    }
    h += '</div>';
  }

  return h;
}

function renderGlobal(data) {
  if (!data) return '<div class="empty" style="text-align:center;padding:40px;color:#94a3b8;font-size:14px;">No module data available.</div>';
  var keys = Object.keys(data);
  var h = '<div class="mgrid">';
  keys.forEach(function(m) {
    var d = data[m];
    if (!d || !d.kpis || !d.kpis.length) return;
    var color = <?php echo json_encode($moduleColors); ?>[m] || '#6366f1';
    var label = <?php echo json_encode($moduleLabels); ?>[m] || m;
    var icon = <?php echo json_encode($moduleIcons); ?>[m] || 'fa-box';
    h += '<div class="mod-card">';
    h += '<div class="mod-head" style="background:'+color+'"><i class="fas '+icon+'"></i> '+label+'</div>';
    h += '<div class="mod-body">';
    d.kpis.forEach(function(k) {
      h += '<div class="mod-kpi"><span>'+k.label+'</span><span style="color:'+kpiIconColor(k.color)+'">'+formatVal(k.value)+'</span></div>';
    });
    h += '</div></div>';
  });
  h += '</div>';
  return h;
}

function renderKpis() {
  var area = document.getElementById('kpiArea');
  area.innerHTML = '<div class="dsh-loader"><i class="fas fa-spinner fa-spin"></i> Loading dashboard data...</div>';

  if (currentView === 'module') {
    if (!currentModule) {
      area.innerHTML = '<div class="empty" style="text-align:center;padding:40px;color:#94a3b8;font-size:14px;">Select a module from the main menu to view its dashboard.</div>';
      return;
    }
    var cached = cache['mod_'+currentModule];
    if (cached) { area.innerHTML = renderCards(cached); return; }
    fetch(rootPath+'/api/dashboardKpiAjax.php?module='+encodeURIComponent(currentModule))
      .then(function(r) { return r.json(); })
      .then(function(d) { cache['mod_'+currentModule] = d; area.innerHTML = renderCards(d); })
      .catch(function() { area.innerHTML = '<div class="empty" style="text-align:center;padding:40px;color:#94a3b8;font-size:14px;">Failed to load dashboard data.</div>'; });
  } else {
    var cached = cache['global'];
    if (cached) { area.innerHTML = renderGlobal(cached); return; }
    fetch(rootPath+'/api/dashboardKpiAjax.php?module=all')
      .then(function(r) { return r.json(); })
      .then(function(d) { cache['global'] = d; area.innerHTML = renderGlobal(d); })
      .catch(function() { area.innerHTML = '<div class="empty" style="text-align:center;padding:40px;color:#94a3b8;font-size:14px;">Failed to load global data.</div>'; });
  }
}

renderKpis();
</script>
</body>
</html>
