<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Create Journal');
include('includes/header.inc');

// Pre-load GL accounts
$glAccounts = [];
$res = DB_query("SELECT accno, accdesc, balance_income FROM acct WHERE ReportStyle=0 AND direct=1 AND inactive=0 ORDER BY accdesc", $db);
while ($r = DB_fetch_array($res)) {
    $bs = $r['balance_income'] == 0 ? 'BS' : ($r['balance_income'] == 1 ? 'P&L' : 'Suspense');
    $glAccounts[] = ['value' => trim($r['accno']), 'label' => trim($r['accdesc']) . ' (' . $bs . ')'];
}

// Pre-load currencies
$currencies = [];
$res = DB_query("SELECT currabrev FROM currencies ORDER BY currabrev", $db);
while ($r = DB_fetch_array($res)) {
    $currencies[] = ['value' => trim($r['currabrev']), 'label' => trim($r['currabrev'])];
}

// Pre-load personal accounts by type
$personalAccounts = [];
$types = ['debtors', 'creditors', 'employee', 'bank', 'GL', 'fixedassets'];

$res = DB_query("SELECT itemcode, customer FROM debtors ORDER BY customer", $db);
while ($r = DB_fetch_array($res)) {
    $personalAccounts['debtors'][] = ['value' => trim($r['itemcode']), 'label' => trim($r['customer'])];
}

$res = DB_query("SELECT itemcode, customer FROM creditors WHERE IsEmployee IS NULL ORDER BY customer", $db);
while ($r = DB_fetch_array($res)) {
    $personalAccounts['creditors'][] = ['value' => trim($r['itemcode']), 'label' => trim($r['customer'])];
}

$res = DB_query("SELECT itemcode, customer FROM creditors WHERE IsEmployee = 1 ORDER BY customer", $db);
while ($r = DB_fetch_array($res)) {
    $personalAccounts['employee'][] = ['value' => trim($r['itemcode']), 'label' => trim($r['customer'])];
}

$res = DB_query("SELECT accountcode, bankName FROM BankAccounts ORDER BY bankName", $db);
while ($r = DB_fetch_array($res)) {
    $personalAccounts['bank'][] = ['value' => trim($r['accountcode']), 'label' => trim($r['bankName'])];
}

$res = DB_query("SELECT accno, accdesc FROM acct WHERE ReportStyle=0 AND direct=1 AND inactive=0 ORDER BY accdesc", $db);
while ($r = DB_fetch_array($res)) {
    $personalAccounts['GL'][] = ['value' => trim($r['accno']), 'label' => trim($r['accdesc'])];
}

$res = DB_query("SELECT assetid, description FROM fixedassets WHERE disposaldate IS NULL ORDER BY description", $db);
while ($r = DB_fetch_array($res)) {
    $personalAccounts['fixedassets'][] = ['value' => trim($r['assetid']), 'label' => trim($r['assetid']) . ' - ' . trim($r['description'])];
}

// Pre-load dimensions
$dimId1 = (int)($_SESSION['CompanyRecord']['DefaultDimension_1'] ?? 0);
$dimId2 = (int)($_SESSION['CompanyRecord']['DefaultDimension_2'] ?? 0);
$dim1Options = [['value' => '', 'label' => '--']];
$dim2Options = [['value' => '', 'label' => '--']];
if ($dimId1 > 0) {
    $res = DB_query("SELECT `Code`, `Dimension` FROM `dimensions` WHERE `id` = $dimId1 AND (BLOCKED IS NULL OR BLOCKED = 0) ORDER BY `Dimension`", $db);
    while ($r = DB_fetch_array($res)) {
        $dim1Options[] = ['value' => trim($r['Code']), 'label' => trim($r['Dimension'])];
    }
}
if ($dimId2 > 0) {
    $res = DB_query("SELECT `Code`, `Dimension` FROM `dimensions` WHERE `id` = $dimId2 AND (BLOCKED IS NULL OR BLOCKED = 0) ORDER BY `Dimension`", $db);
    while ($r = DB_fetch_array($res)) {
        $dim2Options[] = ['value' => trim($r['Code']), 'label' => trim($r['Dimension'])];
    }
}

// Default currency from session
$defaultCurrency = $_SESSION['CompanyRecord']['currencydefault'] ?? 'USD';

$crTypeOptions = [
    ['value' => '', 'label' => '--'],
    ['value' => 'debtors', 'label' => 'Accounts Receivable'],
    ['value' => 'creditors', 'label' => 'Accounts Payable'],
    ['value' => 'employee', 'label' => 'Employee'],
    ['value' => 'bank', 'label' => 'Bank Account'],
    ['value' => 'fixedassets', 'label' => 'Fixed Asset'],
    ['value' => 'GL', 'label' => 'General Ledger'],
];

$periods = [];
$res = DB_query("SELECT start_date, end_date, CONCAT(Name, ' ', YEAR(start_date)) AS label
                 FROM financialperiods
                 ORDER BY start_date DESC", $db);
$currentPeriod = null;
$today = date('Y-m-d');
while ($r = DB_fetch_array($res)) {
    $periods[] = $r;
    if ($currentPeriod === null && $today >= $r['start_date'] && $today <= $r['end_date']) {
        $currentPeriod = $r;
    }
}
if ($currentPeriod === null && !empty($periods)) {
    $currentPeriod = $periods[0];
}
?>
<style>
    .save-indicator {
        position: fixed; top: 10px; right: 20px; z-index: 9999;
        padding: 10px 20px; border-radius: 4px; display: none;
        font-weight: bold; color: #fff;
    }
    .save-indicator.ok { background: #21ba45; display: block; }
    .save-indicator.fail { background: #db2828; display: block; }
    .toolbar { margin-bottom: 10px; }
    .toolbar button {
        padding: 6px 14px; font-size: 13px; margin-right: 6px;
        border: 1px solid #ccc; border-radius: 4px; cursor: pointer;
        background: #f8f9fa; color: #333;
    }
    .toolbar button:hover { background: #e9ecef; }
    .toolbar button:disabled { opacity: 0.5; cursor: not-allowed; }
    .tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: 4px 8px; }
    .tabulator-row .tabulator-cell { padding: 4px 8px; }
    #journalGrid { margin-top: 10px; }
    .filter-bar { margin-bottom: 12px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .filter-bar label { font-weight: 600; }
    .filter-bar select { padding: 4px 8px; font-size: 13px; }
</style>

<div class="save-indicator" id="saveMsg"></div>

<div class="centre">
    <div class="filter-bar">
        <label>Period:</label>
        <select id="periodSelect">
            <?php foreach ($periods as $p):
                $sel = ($currentPeriod && $p['start_date'] == $currentPeriod['start_date']) ? 'selected' : '';
            ?>
                <option value="<?php echo $p['start_date']; ?>|<?php echo $p['end_date']; ?>" <?php echo $sel; ?>>
                    <?php echo htmlspecialchars($p['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button id="loadBtn">Load</button>
        <span id="rowCount" style="margin-left:10px; color:#666;"></span>
    </div>

    <div class="toolbar">
        <button id="newJournalBtn">+ New Journal</button>
        <button id="addLineBtn" disabled>+ Add Line</button>
        <button id="saveBtn">Save Changes</button>
    </div>

    <div id="journalGrid"></div>
</div>

<script>
(function(){
    var $ajaxUrl = <?php echo json_encode($RootPath); ?> + '/Ajax/journalAjax.php';
    var periodEl = document.getElementById('periodSelect');
    var toast = document.getElementById('saveMsg');
    var journalTable = null;
    var newGroupCounter = 0;
    var currentNewGroupKey = null;
    var deletedRows = [];

    // Pre-loaded data from PHP
    var glAccounts = <?php echo json_encode($glAccounts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var currencies = <?php echo json_encode($currencies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var personalAccounts = <?php echo json_encode($personalAccounts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var dim1Options = <?php echo json_encode($dim1Options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var dim2Options = <?php echo json_encode($dim2Options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var defaultCurrency = <?php echo json_encode($defaultCurrency); ?>;
    var crTypeOptions = <?php echo json_encode($crTypeOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    var glAccountsMap = {};
    glAccounts.forEach(function(a) { glAccountsMap[a.value] = a.label; });

    function getPersonalLabel(type, val) {
        if (!type || !val) return val;
        var list = personalAccounts[type] || [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].value === val) return list[i].label;
        }
        return val;
    }

    function getLabel(options, val) {
        for (var i = 0; i < options.length; i++) {
            if (options[i].value === val) return options[i].label;
        }
        return val;
    }

    function showToast(msg, type) {
        toast.className = 'save-indicator ' + type;
        toast.textContent = msg;
        setTimeout(function(){ toast.className = 'save-indicator'; }, 3000);
    }

    function formatDate(d) {
        if (!d) return '';
        return d;
    }

    function markDirty(row) {
        var data = row.getData();
        if (data._state === 'existing' && !data._dirty) {
            row.update({_dirty: true});
        }
    }

    function deleteRow(row) {
        var data = row.getData();
        if (data._state === 'existing') {
            deletedRows.push({
                journalno: data.journalno,
                account: data.account,
                cracount: data.cracount,
                itemcode: data.itemcode,
                amount: data.amount
            });
        }
        row.delete();
    }

    function loadGrid() {
        var parts = periodEl.value.split('|');
        var startDate = parts[0];
        var endDate = parts[1];
        document.getElementById('rowCount').textContent = 'Loading...';
        fetch($ajaxUrl + '?action=get_entries&start_date=' + startDate + '&end_date=' + endDate)
            .then(function(r){ return r.json(); })
            .then(function(data){
            data.forEach(function(row){
                row._state = 'existing';
                row._dirty = false;
                row._orig = {
                    account: row.account,
                    cracount: row.cracount,
                    itemcode: row.itemcode,
                    amount: row.amount
                };
            });
                journalTable.setData(data);
                document.getElementById('rowCount').textContent = data.length + ' entries';
            })
            .catch(function(){
                showToast('Failed to load entries', 'fail');
                document.getElementById('rowCount').textContent = '';
            });
    }

    // Custom select editor for Tabulator (works with all builds)
    function selectEditor(cell, onRendered, success, cancel, editorParams) {
        var editor = document.createElement("select");
        editor.style.cssText = "width:100%;height:100%;padding:2px 4px;border:none;background:transparent;";
        var values = editorParams.values || [];
        var currentValue = cell.getValue();
        var emptyOpt = document.createElement("option");
        emptyOpt.value = "";
        emptyOpt.textContent = "--";
        editor.appendChild(emptyOpt);
        values.forEach(function(item) {
            var opt = document.createElement("option");
            if (typeof item === "object" && item.value !== undefined) {
                opt.value = item.value;
                opt.textContent = item.label || item.value;
            } else {
                opt.value = item;
                opt.textContent = item;
            }
            if (opt.value === currentValue) opt.selected = true;
            editor.appendChild(opt);
        });
        onRendered(function(){ editor.focus(); });
        editor.addEventListener("change", function(){ success(editor.value); });
        editor.addEventListener("blur", function(){ cancel(); });
        return editor;
    }

    // Custom date editor using jQuery UI DatePicker
    function dateEditor(cell, onRendered, success, cancel, editorParams) {
        var input = document.createElement("input");
        input.setAttribute("type", "text");
        input.style.cssText = "width:100%;height:100%;padding:2px 4px;border:none;background:#fff;";
        input.value = cell.getValue() || '';
        onRendered(function(){
            $(input).datepicker({
                dateFormat: 'yy-mm-dd',
                onSelect: function(dateText) {
                    $(this).datepicker("destroy");
                    success(dateText);
                }
            }).focus();
        });
        input.addEventListener("keydown", function(e){
            if (e.key === "Enter") {
                $(input).datepicker("destroy");
                success(input.value);
            }
            if (e.key === "Escape") {
                $(input).datepicker("destroy");
                cancel();
            }
        });
        return input;
    }

    // Initialize Tabulator
    journalTable = new Tabulator("#journalGrid", {
        data: [],
        layout: "fitDataFill",
        pagination: true,
        paginationSize: 100,
        paginationSizeSelector: [50, 100, 250, 500],
        movableColumns: true,
        resizable: true,
        columns: [
            {title: "Date", field: "docdate", width: 110, editor: dateEditor,
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "JNo", field: "journalno", width: 85, editor: false},
            {title: "Curr", field: "currency", width: 65, editor: selectEditor,
                editorParams: {values: currencies},
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "Narration", field: "narration", width: 160, editor: "input",
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "DR Account", field: "account", width: 160, editor: selectEditor,
                editorParams: function(cell) {
                    var cracct = cell.getRow().getData().cracount || '';
                    return {values: glAccounts.filter(function(a) { return a.value !== cracct; })};
                },
                formatter: function(cell) {
                    var val = cell.getValue();
                    return glAccountsMap[val] || val;
                },
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "CR Account", field: "cracount", width: 160, editor: selectEditor,
                editorParams: function(cell) {
                    var dracct = cell.getRow().getData().account || '';
                    return {values: glAccounts.filter(function(a) { return a.value !== dracct; })};
                },
                formatter: function(cell) {
                    var val = cell.getValue();
                    return glAccountsMap[val] || val;
                },
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "CR Type", field: "transtype", width: 100, editor: selectEditor,
                editorParams: {values: crTypeOptions},
                cellEdited: function(cell) {
                    var row = cell.getRow();
                    row.update({itemcode: ''});
                    markDirty(row);
                }
            },
            {title: "CR Name", field: "itemcode", width: 180, editor: selectEditor,
                editorParams: function(cell) {
                    var type = cell.getRow().getData().transtype;
                    return {values: personalAccounts[type] || []};
                },
                formatter: function(cell) {
                    var data = cell.getRow().getData();
                    return getPersonalLabel(data.transtype, cell.getValue());
                },
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "Amount", field: "amount", width: 120, hozAlign: "right",
                editor: "input",
                formatter: function(cell) {
                    var v = parseFloat(cell.getValue()) || 0;
                    return v.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                },
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "Dim1", field: "dimension1", width: 100, editor: selectEditor,
                editorParams: {values: dim1Options},
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "Dim2", field: "dimension2", width: 100, editor: selectEditor,
                editorParams: {values: dim2Options},
                cellEdited: function(cell) { markDirty(cell.getRow()); }
            },
            {title: "", width: 45, formatter: function(cell) {
                var btn = document.createElement("button");
                btn.className = "btn btn-xs btn-danger";
                btn.innerHTML = "&times;";
                btn.title = "Delete row";
                btn.style.cssText = "padding:2px 6px;font-size:12px;";
                btn.addEventListener("click", function(e) {
                    e.stopPropagation();
                    deleteRow(cell.getRow());
                });
                return btn;
            }}
        ],
        dataLoaded: function(data) {
            document.getElementById('rowCount').textContent = data.length + ' entries';
        }
    });

    // Load button
    document.getElementById('loadBtn').addEventListener('click', loadGrid);

    // Auto-load on page load
    loadGrid();

    // New Journal
    document.getElementById('newJournalBtn').addEventListener('click', function(){
        newGroupCounter++;
        var groupKey = 'new_' + Date.now() + '_' + newGroupCounter;
        currentNewGroupKey = groupKey;

        fetch($ajaxUrl + '?action=get_next_no')
            .then(function(r){ return r.json(); })
            .then(function(resp){
                var today = new Date();
                var dateStr = today.getFullYear() + '-' +
                    String(today.getMonth() + 1).padStart(2, '0') + '-' +
                    String(today.getDate()).padStart(2, '0');

                var newRow = {
                    docdate: dateStr,
                    journalno: resp.nextno,
                    currency: defaultCurrency,
                    narration: '',
                    account: '',
                    cracount: '',
                    account_description: '',
                    transtype: '',
                    itemcode: '',
                    name_display: '',
                    amount: 0,
                    dimension1: '',
                    dimension2: '',
                    _state: 'new',
                    _dirty: false,
                    _groupKey: groupKey
                };
                journalTable.addData([newRow], true);
                document.getElementById('addLineBtn').disabled = false;
                showToast('New journal ' + resp.nextno + ' added', 'ok');
            })
            .catch(function(){
                showToast('Failed to get journal number', 'fail');
            });
    });

    // Add Line
    document.getElementById('addLineBtn').addEventListener('click', function(){
        if (!currentNewGroupKey) return;
        var data = journalTable.getData();
        var refRow = null;
        for (var i = data.length - 1; i >= 0; i--) {
            if (data[i]._groupKey === currentNewGroupKey) {
                refRow = data[i];
                break;
            }
        }
        if (!refRow) {
            showToast('No new journal to add line to', 'fail');
            return;
        }

        fetch($ajaxUrl + '?action=get_next_no')
            .then(function(r){ return r.json(); })
            .then(function(resp){
                var newRow = {
                    docdate: refRow.docdate,
                    journalno: resp.nextno,
                    currency: refRow.currency || defaultCurrency,
                    narration: refRow.narration || '',
                    account: '',
                    cracount: '',
                    account_description: '',
                    transtype: '',
                    itemcode: '',
                    name_display: '',
                    amount: 0,
                    dimension1: refRow.dimension1 || '',
                    dimension2: refRow.dimension2 || '',
                    _state: 'new',
                    _dirty: false,
                    _groupKey: currentNewGroupKey
                };
                journalTable.addData([newRow], true);
            })
            .catch(function(){
                showToast('Failed to get journal number', 'fail');
            });
    });

    // Save Changes
    document.getElementById('saveBtn').addEventListener('click', function(){
        var allData = journalTable.getData();
        var newRows = [];
        var changedRows = [];

        allData.forEach(function(row){
            if (row._state === 'new') {
                newRows.push(row);
            } else if (row._state === 'existing' && row._dirty) {
                changedRows.push(row);
            }
        });

        var totalOps = (newRows.length > 0 ? 1 : 0) + changedRows.length + deletedRows.length;
        if (totalOps === 0) {
            showToast('No changes to save', 'ok');
            return;
        }

        showToast('Saving...', 'ok');
        var promises = [];

        // Save new journals
        if (newRows.length > 0) {
            var p = fetch($ajaxUrl + '?action=save_new_journal', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'rows=' + encodeURIComponent(JSON.stringify(newRows))
            })
            .then(function(r){ return r.json(); })
            .then(function(resp){
                if (resp.success) {
                    return {type: 'new', count: newRows.length, msg: resp.message};
                } else {
                    throw new Error(resp.message || 'Save failed');
                }
            });
            promises.push(p);
        }

        // Update changed rows — use _orig for WHERE clause, current values for SET
        changedRows.forEach(function(row){
            var orig = row._orig || row;
            var p = fetch($ajaxUrl + '?action=update_entry', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'journalno=' + encodeURIComponent(row.journalno)
                    + '&orig_account=' + encodeURIComponent(orig.account)
                    + '&orig_cracount=' + encodeURIComponent(orig.cracount)
                    + '&orig_itemcode=' + encodeURIComponent(orig.itemcode)
                    + '&orig_amount=' + encodeURIComponent(orig.amount)
                    + '&docdate=' + encodeURIComponent(row.docdate)
                    + '&currency=' + encodeURIComponent(row.currency)
                    + '&narration=' + encodeURIComponent(row.narration)
                    + '&account=' + encodeURIComponent(row.account)
                    + '&cracount=' + encodeURIComponent(row.cracount)
                    + '&transtype=' + encodeURIComponent(row.transtype)
                    + '&itemcode=' + encodeURIComponent(row.itemcode)
                    + '&amount=' + encodeURIComponent(row.amount)
                    + '&dimension1=' + encodeURIComponent(row.dimension1)
                    + '&dimension2=' + encodeURIComponent(row.dimension2)
            })
            .then(function(r){ return r.json(); })
            .then(function(resp){
                if (!resp.success) throw new Error('Update failed for ' + row.journalno);
                return {type: 'update', journalno: row.journalno};
            });
            promises.push(p);
        });

        // Delete rows
        deletedRows.forEach(function(del){
            var p = fetch($ajaxUrl + '?action=delete_entry', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'journalno=' + encodeURIComponent(del.journalno)
                    + '&account=' + encodeURIComponent(del.account)
                    + '&cracount=' + encodeURIComponent(del.cracount)
                    + '&itemcode=' + encodeURIComponent(del.itemcode)
                    + '&amount=' + encodeURIComponent(del.amount)
            })
            .then(function(r){ return r.json(); })
            .then(function(resp){
                if (!resp.success) throw new Error('Delete failed for ' + del.journalno);
                return {type: 'delete', journalno: del.journalno};
            });
            promises.push(p);
        });

        Promise.all(promises)
            .then(function(){
                var rebuildNos = {};
                changedRows.forEach(function(row){ rebuildNos[row.journalno] = true; });
                deletedRows.forEach(function(del){ rebuildNos[del.journalno] = true; });
                var rebuildList = Object.keys(rebuildNos);

                if (rebuildList.length > 0) {
                    return Promise.all(rebuildList.map(function(jn){
                        return fetch($ajaxUrl + '?action=rebuild_journal', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'journalno=' + encodeURIComponent(jn)
                        }).then(function(r){ return r.json(); });
                    }));
                }
            })
            .then(function(){
                deletedRows = [];
                currentNewGroupKey = null;
                document.getElementById('addLineBtn').disabled = true;
                showToast('All changes saved', 'ok');
                loadGrid();
            })
            .catch(function(err){
                showToast(err.message || 'Save failed', 'fail');
            });
    });
})();
</script>

<?php include('includes/footer.inc'); ?>
