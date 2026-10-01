<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
$Title = _('Setting Up Company Accounts');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/chartbalancing.inc');
include('includes/AccountBalance.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('General ledger') .'" alt="" />'
. ' ' . _('General ledger') . '</p>';

// ── Backward-compatible account creation ──
function CreateNewAccount(){
    global $db;
    $Errors=0;
    if(isset($_POST['Calculation']) && mb_strlen($_POST['Calculation'])>0){
        foreach (explode('+',$_POST['Calculation']) as $value) {
            $value = trim($value);
            if(mb_strlen($value)>0){
                $r=DB_query("Select accno from Acct where ReportCode='".$value."'",$db);
                if(DB_num_rows($r)==0){ prnMsg("Invalid account in formula: '".$value."'",'warn'); $Errors++; }
            }
        }
        foreach (explode('-',$_POST['Calculation']) as $value) {
            $value = trim($value);
            if(mb_strlen($value)>0){
                $r=DB_query("Select accno from Acct where ReportCode='".$value."'",$db);
                if(DB_num_rows($r)==0){ prnMsg("Invalid account in formula: '".$value."'",'warn'); $Errors++; }
            }
        }
    }
    $r=DB_query("Select accno from Acct where ReportCode='".$_POST['ReportCode']."'",$db);
    if(DB_num_rows($r)==0 && $Errors==0){
        if($_POST['BalanceSheet']==0) $_POST['DirectPosting']=0;
        $accno = Triger_getaccountno($_POST['accdesc']);
        $sql=sprintf("INSERT INTO `acct` (`ReportCode`,`accdesc`,`balance_income`,`ReportStyle`,`direct`,`inactive`,`Sale_Purchase_Neither`,`Calculation`,postinggroup,accno) values ('%s','%s',%s,%s,%s,%s,%s,'%s','%s','%s')",
            $_POST['ReportCode'],$_POST['accdesc'],$_POST['BalanceSheet'],$_POST['AccountType'],$_POST['DirectPosting'],$_POST['Blocked'],$_POST['Sale_Purchase_Neither'],$_POST['Calculation'],$_POST['postinggroup'],$accno);
        $r=DB_query($sql,$db);
        if(DB_error_no($db)>0) prnMsg(DB_error_msg($db));
        else unset($_POST);
    } else {
        prnMsg('Account code already exists: <b>'.$_POST['ReportCode'].'</b>','warn');
    }
}

// ── Backward-compatible account update ──
function UpdateAccount(){
    global $db;
    $Errors=0;
    if(isset($_POST['Calculation']) && mb_strlen($_POST['Calculation'])>0){
        if(strpos($_POST['Calculation'],'+')>0){
            foreach (explode('+',$_POST['Calculation']) as $value) {
                $value = trim($value);
                if(mb_strlen($value)>0){
                    $r=DB_query("Select accno from Acct where ReportCode='".$value."'",$db);
                    if(DB_num_rows($r)==0){ prnMsg("Invalid account in formula: '".$value."'",'warn'); $Errors++; }
                }
            }
        }
        if(strpos($_POST['Calculation'],'-')>0){
            foreach (explode('-',$_POST['Calculation']) as $value) {
                $value = trim($value);
                if(mb_strlen($value)>0){
                    $r=DB_query("Select accno from Acct where ReportCode='".$value."'",$db);
                    if(DB_num_rows($r)==0){ prnMsg("Invalid account in formula: '".$value."'",'warn'); $Errors++; }
                }
            }
        }
    }
    $chk=DB_query("Select accno from Acct where ReportCode='".$_POST['ReportCode']."' and accno!='".$_POST['ACCNO']."'",$db);
    if(DB_num_rows($chk)==0 && $Errors==0){
        $sql=sprintf("Update `acct` set ReportCode='%s', accdesc='%s', balance_income=%s, ReportStyle=%s, direct=%s, inactive=%s, Sale_Purchase_Neither=%s, Calculation='%s', postinggroup='%s' where accno='%s'",
            $_POST['ReportCode'],$_POST['accdesc'],$_POST['BalanceSheet'],$_POST['AccountType'],$_POST['DirectPosting'],$_POST['Blocked'],$_POST['Sale_Purchase_Neither'],$_POST['Calculation'],$_POST['postinggroup'],$_POST['ACCNO']);
        $r=DB_query($sql,$db);
        if(DB_error_no($db)>0) prnMsg(DB_error_msg($db));
        else unset($_POST);
    } else {
        prnMsg('Account code already exists: <b>'.$_POST['ReportCode'].'</b>','warn');
    }
}

if(isset($_POST['Newaccounts'])){ CreateNewAccount(); }
if(isset($_POST['submitaccounts'])){ UpdateAccount(); }

// Load account data for grid
$coaData = [];
$result = DB_query("SELECT accno, ReportCode, accdesc, balance_income, ReportStyle, direct, inactive, Sale_Purchase_Neither, Calculation, postinggroup FROM acct ORDER BY ReportCode", $db);
while ($raw = DB_fetch_array($result)) {
    $coaData[] = [
        'accno'                => trim($raw['accno']),
        'ReportCode'           => trim($raw['ReportCode']),
        'accdesc'              => trim($raw['accdesc']),
        'balance_income'       => $raw['balance_income'],
        'ReportStyle'          => $raw['ReportStyle'],
        'direct'               => $raw['direct'],
        'inactive'             => $raw['inactive'],
        'Sale_Purchase_Neither'=> trim($raw['Sale_Purchase_Neither'] ?? ''),
        'Calculation'          => trim($raw['Calculation'] ?? ''),
        'postinggroup'         => trim($raw['postinggroup'] ?? ''),
    ];
}

// Load posting groups for modal dropdown
$postingGroupOpts = [];
$pgResult = DB_query("SELECT code FROM GLpostinggroup", $db);
while ($pgRow = DB_fetch_array($pgResult)) {
    $postingGroupOpts[] = trim($pgRow['code']);
}

$balanceSheetLabels = [0 => 'Balance Sheet', 1 => 'Profit and Loss', 2 => 'Control'];
$accountTypeLabels  = [0 => 'Posting', 1 => 'Heading', 2 => 'Total', 3 => 'Begin-Total', 4 => 'End-Total'];
$directPostingLabels = [0 => 'No', 1 => 'Yes'];
$blockedLabels      = [0 => 'No', 1 => 'Yes'];
$reconLabels        = [0 => '', 1 => 'Bank'];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/css/tabulator.min.css" />
<style>
#coaGrid { margin-top: 10px; }
.coa-save-indicator {
  position: fixed; top: 10px; right: 20px; z-index: 9999;
  padding: 10px 20px; border-radius: 4px; display: none;
  font-weight: bold; color: #fff;
}
.coa-save-indicator.ok { background: #21ba45; display: block; }
.coa-save-indicator.fail { background: #db2828; display: block; }
.coa-modal-overlay {
  display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.5); z-index: 10000;
}
.coa-modal-overlay.show { display: flex; align-items: center; justify-content: center; }
.coa-modal-box {
  background: #fff; border-radius: 6px; padding: 20px; width: 90%; max-width: 700px;
  max-height: 85vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3); position: relative;
}
.coa-modal-box h3 { margin-top: 0; }
.coa-modal-close {
  position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #888;
}
.coa-modal-close:hover { color: #000; }
.coa-modal-box table { width: 100%; border-collapse: collapse; }
.coa-modal-box td { padding: 6px 8px; border: 1px solid #ddd; vertical-align: middle; }
.coa-modal-box td label { white-space: nowrap; }
.coa-modal-box input[type="text"], .coa-modal-box select { width: 100%; padding: 4px 6px; box-sizing: border-box; }
.coa-modal-box input[type="text"] { width: 100%; }
.coa-modal-actions { margin-top: 15px; text-align: right; }
.coa-modal-actions button { margin-left: 8px; }
.coa-toolbar { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
.coa-toolbar button { padding: 6px 14px; }
.tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: 4px 8px; }
.tabulator-row .tabulator-cell { padding: 4px 8px; }
</style>

<div class="coa-save-indicator" id="coaSaveMsg"></div>

<div class="coa-toolbar">
  <button id="coaAddBtn" class="btn-primary">+ New Account</button>
  <span id="coaRowCount" style="color:#666;font-size:13px;"></span>
</div>

<div id="coaGrid"></div>

<!-- Modal -->
<div class="coa-modal-overlay" id="coaModal">
  <div class="coa-modal-box">
    <span class="coa-modal-close" id="coaModalClose">&times;</span>
    <h3 id="coaModalTitle">Edit Account</h3>
    <form id="coaModalForm">
      <input type="hidden" name="accno" id="coaField_accno" value="" />
      <table class="table-bordered">
        <tr>
          <td><label>Account Code</label></td>
          <td><input type="text" name="ReportCode" id="coaField_ReportCode" required /></td>
          <td><label>Account Name</label></td>
          <td><input type="text" name="accdesc" id="coaField_accdesc" required /></td>
        </tr>
        <tr>
          <td><label>Income/Balance</label></td>
          <td><select name="balance_income" id="coaField_balance_income">
<?php foreach ($balanceSheetLabels as $k => $v): ?>
            <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($v); ?></option>
<?php endforeach; ?>
          </select></td>
          <td><label>Account Type</label></td>
          <td><select name="ReportStyle" id="coaField_ReportStyle">
<?php foreach ($accountTypeLabels as $k => $v): ?>
            <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($v); ?></option>
<?php endforeach; ?>
          </select></td>
        </tr>
        <tr>
          <td><label>Direct Posting</label></td>
          <td><select name="direct" id="coaField_direct">
<?php foreach ($directPostingLabels as $k => $v): ?>
            <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($v); ?></option>
<?php endforeach; ?>
          </select></td>
          <td><label>Blocked</label></td>
          <td><select name="inactive" id="coaField_inactive">
<?php foreach ($blockedLabels as $k => $v): ?>
            <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($v); ?></option>
<?php endforeach; ?>
          </select></td>
        </tr>
        <tr>
          <td><label>Reconciliation</label></td>
          <td><select name="Sale_Purchase_Neither" id="coaField_Sale_Purchase_Neither">
<?php foreach ($reconLabels as $k => $v): ?>
            <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($v); ?></option>
<?php endforeach; ?>
          </select></td>
          <td><label>Posting Group</label></td>
          <td><select name="postinggroup" id="coaField_postinggroup">
            <option value=""></option>
<?php foreach ($postingGroupOpts as $v): ?>
            <option value="<?php echo htmlspecialchars($v); ?>"><?php echo htmlspecialchars($v); ?></option>
<?php endforeach; ?>
          </select></td>
        </tr>
        <tr>
          <td><label>Formula (+ only)</label></td>
          <td colspan="3"><input type="text" name="Calculation" id="coaField_Calculation" placeholder="e.g. +`accountno`+[account~1]" /></td>
        </tr>
      </table>
      <div class="coa-modal-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-secondary" id="coaModalCancel">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var COA_CONFIG = <?php echo json_encode([
    'data' => $coaData,
    'postingGroups' => $postingGroupOpts,
    'balanceSheetLabels' => $balanceSheetLabels,
    'accountTypeLabels' => $accountTypeLabels,
    'rootPath' => $RootPath,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/js/tabulator.min.js"></script>
<script src="<?php echo $RootPath; ?>/javascripts/ChartofAccounts.js"></script>
<?php
include('includes/footer.inc');
?>
