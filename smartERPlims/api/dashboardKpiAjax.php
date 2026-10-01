<?php
header('Content-Type: application/json');
include('../includes/ConnectAjax.php');

$modReq = isset($_GET['module']) ? $_GET['module'] : '';
$modulesEnabled = isset($_SESSION['ModulesEnabled']) ? $_SESSION['ModulesEnabled'] : array();

$moduleList = array(
    0 => 'Approval', 1 => 'Sales', 2 => 'AccountsReceivable',
    3 => 'Inventory', 4 => 'cRM', 5 => 'Purchases',
    6 => 'AccountsPayable', 7 => 'CashManagement', 8 => 'GeneralLedger',
    9 => 'FixedAssets', 10 => 'system'
);

function scalar($sql) {
    global $db;
    $r = DB_query($sql, $db);
    if (!$r) return 0;
    $row = DB_fetch_row($r);
    return isset($row[0]) ? $row[0] : 0;
}

function getModuleKpis($mod) {
    global $db;
    $kpis = array(); $recent = array(); $queue = array();
    $now = date('Y-m-d');
    $firstDay = date('Y-m-01');
    $lastDay = date('Y-m-t');

    switch ($mod) {

        case 'Approval':
            $kpis[] = array('label'=>'Pending Approvals','value'=>scalar("SELECT COUNT(*) FROM pricelist WHERE approved=0 OR approved IS NULL"),'icon'=>'fa-check-circle','color'=>'orange');
            $kpis[] = array('label'=>'Unreleased Sales Orders','value'=>scalar("SELECT COUNT(*) FROM salesheader WHERE documenttype=1 AND (released=0 OR released IS NULL)"),'icon'=>'fa-file-invoice','color'=>'blue');
            $kpis[] = array('label'=>'Unreleased Purchase Orders','value'=>scalar("SELECT COUNT(*) FROM assetsheader WHERE documenttype=18 AND (released=0 OR released IS NULL)"),'icon'=>'fa-cart-arrow-down','color'=>'purple');
            $kpis[] = array('label'=>'Open Tasks','value'=>scalar("SELECT COUNT(*) FROM tasks WHERE Status IS NULL OR Status < 4"),'icon'=>'fa-tasks','color'=>'green');
            break;

        case 'Sales':
            $kpis[] = array('label'=>'Open Sales Orders','value'=>scalar("SELECT COUNT(*) FROM salesheader WHERE documenttype=1 AND (released=0 OR released IS NULL)"),'icon'=>'fa-file-invoice','color'=>'blue');
            $kpis[] = array('label'=>'Monthly Revenue','value'=>number_format(scalar("SELECT COALESCE(SUM(sl.invoiceamount),0) FROM salesline sl JOIN salesheader sh ON sh.documentno=sl.documentno AND sh.documenttype=sl.documenttype WHERE sh.documenttype=10 AND MONTH(sh.docdate)=MONTH('$now') AND YEAR(sh.docdate)=YEAR('$now')"),2),'icon'=>'fa-chart-line','color'=>'green');
            $kpis[] = array('label'=>'Pending Deliveries','value'=>scalar("SELECT COUNT(DISTINCT documentno) FROM salesline WHERE documenttype=1 AND (Qunatity_delivered IS NULL OR Qunatity_delivered < Quantity) AND (completed=0 OR completed IS NULL)"),'icon'=>'fa-truck','color'=>'amber');
            $kpis[] = array('label'=>'Pending Quotations','value'=>scalar("SELECT COUNT(*) FROM salesheader WHERE documenttype=54 AND (released=0 OR released IS NULL)"),'icon'=>'fa-file-contract','color'=>'purple');
            $r = DB_query("SELECT documentno, customername, docdate FROM salesheader WHERE documenttype=1 AND (released=0 OR released IS NULL) ORDER BY docdate DESC LIMIT 5", $db);
            while ($row = DB_fetch_array($r)) $recent[] = $row;
            break;

        case 'AccountsReceivable':
            $kpis[] = array('label'=>'Outstanding Invoices','value'=>scalar("SELECT COUNT(*) FROM customerstatement WHERE Datewhenpaid IS NULL"),'icon'=>'fa-file-invoice-dollar','color'=>'blue');
            $kpis[] = array('label'=>'Total Outstanding','value'=>number_format(scalar("SELECT COALESCE(SUM(Grossamount),0) FROM customerstatement WHERE Datewhenpaid IS NULL"),2),'icon'=>'fa-dollar-sign','color'=>'red');
            $kpis[] = array('label'=>'Overdue Items','value'=>scalar("SELECT COUNT(*) FROM debtorsledger WHERE flag='I' AND (cleared=0 OR cleared IS NULL)"),'icon'=>'fa-exclamation-triangle','color'=>'orange');
            $kpis[] = array('label'=>'Total Receivables','value'=>number_format(scalar("SELECT COALESCE(SUM(balance),0) FROM debtors WHERE inactive=0"),2),'icon'=>'fa-piggy-bank','color'=>'green');
            break;

        case 'Inventory':
            $kpis[] = array('label'=>'Total SKUs','value'=>scalar("SELECT COUNT(*) FROM stockmaster WHERE inactive=0"),'icon'=>'fa-boxes','color'=>'blue');
            $kpis[] = array('label'=>'Lab Items','value'=>scalar("SELECT COUNT(*) FROM stockmaster WHERE category='LAB' AND inactive=0"),'icon'=>'fa-flask','color'=>'purple');
            $kpis[] = array('label'=>'Categories','value'=>scalar("SELECT COUNT(DISTINCT category) FROM stockmaster WHERE inactive=0 AND category IS NOT NULL AND category!=''"),'icon'=>'fa-tags','color'=>'green');
            $kpis[] = array('label'=>'Inactive Items','value'=>scalar("SELECT COUNT(*) FROM stockmaster WHERE inactive=1"),'icon'=>'fa-ban','color'=>'red');
            break;

        case 'cRM':
            $pipeline = DB_fetch_row(DB_query("SELECT COALESCE(SUM(expected_value),0), COALESCE(AVG(expected_value),0) FROM crm_opportunities WHERE pipeline_stage < 6", $db));
            $kpis[] = array('label'=>'Pending Price Approvals','value'=>scalar("SELECT COUNT(*) FROM pricelist WHERE approved=0 OR approved IS NULL"),'icon'=>'fa-tags','color'=>'orange');
            $kpis[] = array('label'=>'Pipeline Value','value'=>number_format($pipeline[0],2),'icon'=>'fa-chart-pie','color'=>'blue');
            $kpis[] = array('label'=>'Average Deal Size','value'=>number_format($pipeline[1],2),'icon'=>'fa-calculator','color'=>'green');
            $kpis[] = array('label'=>'Closed This Month','value'=>number_format(scalar("SELECT COALESCE(SUM(closed_value),0) FROM crm_opportunities WHERE pipeline_stage=6 AND MONTH(actual_close_date)=MONTH('$now') AND YEAR(actual_close_date)=YEAR('$now')"),2),'icon'=>'fa-check-circle','color'=>'purple');
            $r = DB_query("SELECT company_name, status, next_followup, lead_score FROM crm_leads ORDER BY created_at DESC LIMIT 5", $db);
            while ($row = DB_fetch_array($r)) $recent[] = $row;
            break;

        case 'Purchases':
            $kpis[] = array('label'=>'Open Purchase Orders','value'=>scalar("SELECT COUNT(*) FROM assetsheader WHERE documenttype=18 AND (released=0 OR released IS NULL)"),'icon'=>'fa-cart-plus','color'=>'blue');
            $kpis[] = array('label'=>'Active Suppliers','value'=>scalar("SELECT COUNT(*) FROM creditors WHERE inactive=0"),'icon'=>'fa-truck-moving','color'=>'green');
            $kpis[] = array('label'=>'Total Payables','value'=>number_format(scalar("SELECT COALESCE(SUM(balance),0) FROM creditors WHERE inactive=0"),2),'icon'=>'fa-money-bill','color'=>'red');
            $kpis[] = array('label'=>'Supplier Aging 90+','value'=>number_format(scalar("SELECT COALESCE(SUM(age4),0) FROM creditors WHERE inactive=0"),2),'icon'=>'fa-clock','color'=>'orange');
            break;

        case 'AccountsPayable':
            $kpis[] = array('label'=>'Unpaid Bills','value'=>scalar("SELECT COUNT(*) FROM supplierstatement"),'icon'=>'fa-file-invoice','color'=>'red');
            $kpis[] = array('label'=>'Total Due','value'=>number_format(scalar("SELECT COALESCE(SUM(Grossamount),0) FROM supplierstatement"),2),'icon'=>'fa-dollar-sign','color'=>'orange');
            $kpis[] = array('label'=>'Pending Vouchers','value'=>scalar("SELECT COUNT(*) FROM paymentvoucherheader WHERE status=0 OR status IS NULL"),'icon'=>'fa-file-invoice-dollar','color'=>'blue');
            $kpis[] = array('label'=>'Active Suppliers','value'=>scalar("SELECT COUNT(*) FROM creditors WHERE inactive=0"),'icon'=>'fa-handshake','color'=>'green');
            break;

        case 'CashManagement':
            $kpis[] = array('label'=>'Bank Accounts','value'=>scalar("SELECT COUNT(*) FROM bankaccounts WHERE Makeinactive=0 OR Makeinactive IS NULL"),'icon'=>'fa-university','color'=>'blue');
            $kpis[] = array('label'=>'Cash (KES)','value'=>number_format(scalar("SELECT COALESCE(SUM(lastreconbalance),0) FROM bankaccounts WHERE currency='KES' AND (Makeinactive=0 OR Makeinactive IS NULL)"),2),'icon'=>'fa-money-bill-wave','color'=>'green');
            $kpis[] = array('label'=>'Cash (USD)','value'=>number_format(scalar("SELECT COALESCE(SUM(lastreconbalance),0) FROM bankaccounts WHERE currency='USD' AND (Makeinactive=0 OR Makeinactive IS NULL)"),2),'icon'=>'fa-dollar-sign','color'=>'amber');
            $kpis[] = array('label'=>'Outstanding Cheques','value'=>scalar("SELECT COUNT(*) FROM banktransactions WHERE TransType='c' AND (cleared=0 OR cleared IS NULL)"),'icon'=>'fa-print','color'=>'purple');
            break;

        case 'GeneralLedger':
            $per = DB_fetch_array(DB_query("SELECT Name, closed FROM financialperiods WHERE '$now' BETWEEN start_date AND end_date LIMIT 1", $db));
            $periodStatus = $per ? ($per['closed'] ? 'Closed' : 'Open') : 'N/A';
            $periodName = $per ? $per['Name'] : 'N/A';
            $kpis[] = array('label'=>'Current Period','value'=>$periodName,'icon'=>'fa-calendar-alt','color'=>'blue');
            $kpis[] = array('label'=>'Period Status','value'=>$periodStatus,'icon'=>'fa-lock','color'=>($periodStatus=='Open'?'green':'red'));
            $kpis[] = array('label'=>'GL Accounts','value'=>scalar("SELECT COUNT(*) FROM acct WHERE inactive=0"),'icon'=>'fa-book','color'=>'purple');
            $kpis[] = array('label'=>'Posting Accounts','value'=>scalar("SELECT COUNT(*) FROM acct WHERE direct=1 AND inactive=0"),'icon'=>'fa-pen','color'=>'green');
            break;

        case 'FixedAssets':
            $assets = DB_fetch_array(DB_query("SELECT COUNT(*) AS cnt, COALESCE(SUM(cost),0) AS cost, COALESCE(SUM(accumdepn),0) AS depn, COALESCE(SUM(cost-accumdepn),0) AS nbv FROM fixedassets", $db));
            $kpis[] = array('label'=>'Total Assets','value'=>$assets['cnt'],'icon'=>'fa-building','color'=>'blue');
            $kpis[] = array('label'=>'Total Cost','value'=>number_format($assets['cost'],2),'icon'=>'fa-coins','color'=>'amber');
            $kpis[] = array('label'=>'Net Book Value','value'=>number_format($assets['nbv'],2),'icon'=>'fa-chart-simple','color'=>'green');
            $kpis[] = array('label'=>'Assets OK','value'=>scalar("SELECT COUNT(*) FROM fixedassets WHERE status='OK'"),'icon'=>'fa-check','color'=>'purple');
            break;

        case 'system':
            $kpis[] = array('label'=>'Active Users','value'=>scalar("SELECT COUNT(*) FROM www_users WHERE blocked=0"),'icon'=>'fa-users','color'=>'blue');
            $kpis[] = array('label'=>'Security Roles','value'=>scalar("SELECT COUNT(*) FROM securityroles"),'icon'=>'fa-shield-alt','color'=>'green');
            $kpis[] = array('label'=>'Scripts Registered','value'=>scalar("SELECT COUNT(*) FROM scripts"),'icon'=>'fa-file-code','color'=>'purple');
            $kpis[] = array('label'=>'Last Login', 'value'=>substr(scalar("SELECT MAX(lastvisitdate) FROM www_users"),0,10),'icon'=>'fa-clock','color'=>'orange');
            break;
    }

    return array('kpis'=>$kpis, 'recent'=>$recent, 'queue'=>$queue);
}

if ($modReq === 'all') {
    $result = array();
    foreach ($moduleList as $idx => $m) {
        $enabled = true;
        if (!empty($modulesEnabled)) {
            $enabled = false;
            if (isset($modulesEnabled[$m]) && $modulesEnabled[$m] == 1) $enabled = true;
            elseif (isset($modulesEnabled[$idx]) && $modulesEnabled[$idx] == 1) $enabled = true;
        }
        if (!$enabled) continue;
        $result[$m] = getModuleKpis($m);
    }
    echo json_encode($result);
} else {
    echo json_encode(getModuleKpis($modReq));
}
