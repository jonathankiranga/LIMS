<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
$Title = _('Inventory List');
include('includes/header.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if (isset($_GET['StockID'])) {
    $_SESSION['StockID'] = $_GET['StockID'];
}
if (isset($_GET['newsearch'])) {
    unset($_SESSION['StockID']);
}
if (isset($_SESSION['StockID'])) {
    $StockID = $_SESSION['StockID'];
}

// Load category list for filter and grid
$categories = [];
$catResult = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription", $db);
while ($catRow = DB_fetch_array($catResult)) {
    $categories[] = [
        'id' => trim($catRow['categoryid']),
        'name' => trim($catRow['categorydescription']),
    ];
}

if (isset($_SESSION['StockID'])) {

    $ErrMsg = _('The Item name requested cannot be retrieved because');
    $result = DB_query("SELECT isstock, barcode, itemcode, descrip, postinggroup, averagestock,
                        partperunit, reorderlevel, eoq, category, units, inactive, nextserialno, sellingprice
                        FROM stockmaster WHERE itemcode='" . $_SESSION['StockID'] . "'", $db, $ErrMsg);

    if ($myrow = DB_fetch_array($result)) {
        $StockName = htmlspecialchars($myrow['descrip'], ENT_QUOTES, 'UTF-8', false);
    }

    echo '<div class="page_help_text">' . _('Select a menu option to operate using this Inventory ') . $StockName . '.</div><br />';
    echo '<table cellpadding="4" width="100%" class="table table-bordered">
                <tr>
                        <th style="width:33%">' . _('Inventory Inquiries') . '</th>
                        <th style="width:33%">' . _('Inventory Transactions') . '</th>
                        <th style="width:33%">' . _('Inventory Maintenance') . '</th>
                </tr>';
    echo '<tr><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/StockMovements.php?StockID=' . $StockID . '">' . _('Show Stock Movements') . '</a><br />';
    echo '<a href="' . $RootPath . '/PurchaseOrderbystock.php?StockID=' . $StockID . '">' . _('Search Purchase Orders') . '</a><br />';
    echo '<a href="' . $RootPath . '/SalesOrderbystock.php?StockID=' . $StockID . '">' . _('Search Sales Orders') . '</a><br />';
    echo '</td><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/StockAdjustments.php?StockID=' . $StockID . '">' . _('Quantity Adjustments') . '</a><br />';
    echo '<a href="' . $RootPath . '/StockContainer.php?StockID=' . $StockID . '">' . _('Link Container') . '</a><br />';
    echo '</td><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/Stocks.php">' . _('Insert New Item') . '</a><br />';
    echo '<a href="' . $RootPath . '/Stocks.php?StockID=' . $StockID . '">' . _('Modify Item Details') . '</a><br />';
    echo '</td>';
    echo '</tr></table><br />';

} else {

    // Load stock data
    $stockData = [];
    $results = DB_query("SELECT sm.itemcode, sm.descrip, sm.category, sm.production,
                            sm.inactive, sm.averagestock, sm.postinggroup, sm.barcode,
                            sc.categorydescription,
                            unitfull.descrip AS unitname
                         FROM stockmaster sm
                         LEFT JOIN unit unitfull ON sm.units = unitfull.code
                         LEFT JOIN stockcategory sc ON sc.categoryid = sm.category
                         ORDER BY sm.inactive, sm.descrip ASC", $db);

    while ($row = DB_fetch_array($results)) {
        $catName = $row['categorydescription'] ?? '';
        $prodName = isset($ProductionCategory[$row['production']]) ? $ProductionCategory[$row['production']] : ($row['production'] ?? '');
        $stockData[] = [
            'itemcode' => trim($row['itemcode']),
            'descrip' => trim($row['descrip']),
            'category' => $catName,
            'production' => $prodName,
            'inactive' => $row['inactive'] ? 'YES' : 'NO',
            'averagestock' => (float)$row['averagestock'],
            'postinggroup' => trim($row['postinggroup']),
            'barcode' => trim($row['barcode'] ?? ''),
        ];
    }

    // Build category name -> id lookup for JS
    $catMap = [];
    foreach ($categories as $c) {
        $catMap[$c['name']] = $c['id'];
    }
    $catNames = array_column($categories, 'name');

?>
<style>
    #inventoryGrid { margin-top: 10px; }
    .save-indicator {
        position: fixed; top: 10px; right: 20px; z-index: 9999;
        padding: 10px 20px; border-radius: 4px; display: none;
        font-weight: bold; color: #fff;
    }
    .save-indicator.ok { background: #21ba45; display: block; }
    .save-indicator.fail { background: #db2828; display: block; }
    .action-panel-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 10000;
    }
    .action-panel-overlay.show { display: flex; align-items: center; justify-content: center; }
    .action-panel-box {
        background: #fff; border-radius: 6px; padding: 20px; width: 80%; max-width: 700px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3); position: relative;
    }
    .action-panel-box h3 { margin-top: 0; }
    .action-panel-box .close-btn {
        position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #888;
    }
    .action-panel-box .close-btn:hover { color: #000; }
    .action-panel-box table { width: 100%; border-collapse: collapse; }
    .action-panel-box th { background: #f5f5f5; padding: 8px; border: 1px solid #ddd; width: 33%; text-align: center; }
    .action-panel-box td { padding: 8px; border: 1px solid #ddd; vertical-align: top; }
    .action-panel-box td a { display: block; padding: 4px 0; color: #333; text-decoration: none; }
    .action-panel-box td a:hover { color: #2185d0; text-decoration: underline; }
    .tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: 4px 8px; }
    .tabulator-row .tabulator-cell { padding: 4px 8px; }
    .action-btn { cursor: pointer; }
</style>

<div class="save-indicator" id="saveMsg"></div>

<div class="action-panel-overlay" id="actionOverlay">
    <div class="action-panel-box">
        <span class="close-btn" id="actionClose">&times;</span>
        <h3 id="actionTitle">Actions</h3>
        <table class="table table-bordered">
            <tr>
                <th>Inventory Inquiries</th>
                <th>Inventory Transactions</th>
                <th>Inventory Maintenance</th>
            </tr>
            <tr>
                <td id="actionInquiries"></td>
                <td id="actionTransactions"></td>
                <td id="actionMaintenance"></td>
            </tr>
        </table>
    </div>
</div>

<div style="margin-bottom:10px;">
    <label for="catFilter"><b>Filter by Category:</b></label>
    <select id="catFilter" style="padding:4px 8px; font-size:13px;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
    </select>
    <span id="rowCount" style="margin-left:15px; color:#666;"></span>
</div>

<div id="inventoryGrid"></div>

<script>
(function(){
    var allData = <?php echo json_encode($stockData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var catMap = <?php echo json_encode($catMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var catNames = <?php echo json_encode($catNames, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var $root = <?php echo json_encode($RootPath); ?>;

    var catEditorParams = {values: catNames};

    var table = new Tabulator("#inventoryGrid", {
        data: allData,
        layout: "fitDataFill",
        pagination: true,
        paginationSize: 50,
        paginationSizeSelector: [25, 50, 100, 250, 500],
        movableColumns: true,
        resizable: true,
        columns: [
            {title: "Stock Code", field: "itemcode", width: 120},
            {title: "Inventory Name", field: "descrip", width: 250},
            {title: "Category", field: "category", width: 160, editor: "select", editorParams: catEditorParams,
                cellEdited: function(cell) {
                    var rowData = cell.getRow().getData();
                    var oldVal = cell.getOldValue();
                    var newVal = cell.getValue();
                    if (oldVal === newVal) return;
                    var catId = catMap[newVal] || newVal;
                    var el = document.getElementById('saveMsg');
                    fetch($root + '/Ajax/updateStockCategory.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'itemcode=' + encodeURIComponent(rowData.itemcode) + '&category=' + encodeURIComponent(catId)
                    })
                    .then(function(r){ return r.json(); })
                    .then(function(resp){
                        if (resp.success) {
                            el.className = 'save-indicator ok';
                            el.textContent = 'Category updated for ' + rowData.itemcode;
                        } else {
                            el.className = 'save-indicator fail';
                            el.textContent = resp.message || 'Update failed';
                            cell.setValue(oldVal, true);
                        }
                        setTimeout(function(){ el.className = 'save-indicator'; }, 2500);
                    })
                    .catch(function(){
                        el.className = 'save-indicator fail';
                        el.textContent = 'Network error';
                        cell.setValue(oldVal, true);
                        setTimeout(function(){ el.className = 'save-indicator'; }, 2500);
                    });
                }
            },
            {title: "Production", field: "production", width: 150},
            {title: "Is Obsolete", field: "inactive", width: 90, hozAlign: "center"},
            {title: "Ave Cost", field: "averagestock", width: 100, hozAlign: "right",
                formatter: function(cell) {
                    return parseFloat(cell.getValue()).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                }
            },
            {title: "Posting Group", field: "postinggroup", width: 120},
            {title: "Barcode", field: "barcode", width: 100},
            {title: "Actions", width: 90, hozAlign: "center", formatter: function(cell) {
                var btn = document.createElement("button");
                btn.className = "btn btn-xs btn-info action-btn";
                btn.textContent = "Actions";
                btn.addEventListener("click", function(e) {
                    e.stopPropagation();
                    openActionPanel(cell.getRow().getData().itemcode);
                });
                return btn;
            }}
        ],
        dataLoaded: function(data) {
            document.getElementById('rowCount').textContent = data.length + ' items';
        }
    });

    // Category dropdown filter
    document.getElementById('catFilter').addEventListener('change', function(){
        var val = this.value;
        if (!val) {
            table.clearHeaderFilter();
            table.setData(allData);
        } else {
            table.setData(allData.filter(function(r){ return r.category === val; }));
        }
    });

    // Action panel
    window.openActionPanel = function(itemcode) {
        document.getElementById('actionTitle').textContent = 'Actions — ' + itemcode;
        document.getElementById('actionInquiries').innerHTML =
            '<a href="' + $root + '/StockMovements.php?StockID=' + encodeURIComponent(itemcode) + '">Show Stock Movements</a>' +
            '<a href="' + $root + '/PurchaseOrderbystock.php?StockID=' + encodeURIComponent(itemcode) + '">Search Purchase Orders</a>' +
            '<a href="' + $root + '/SalesOrderbystock.php?StockID=' + encodeURIComponent(itemcode) + '">Search Sales Orders</a>';
        document.getElementById('actionTransactions').innerHTML =
            '<a href="' + $root + '/StockAdjustments.php?StockID=' + encodeURIComponent(itemcode) + '">Quantity Adjustments</a>' +
            '<a href="' + $root + '/StockContainer.php?StockID=' + encodeURIComponent(itemcode) + '">Link Container</a>';
        document.getElementById('actionMaintenance').innerHTML =
            '<a href="' + $root + '/Stocks.php">Insert New Item</a>' +
            '<a href="' + $root + '/Stocks.php?StockID=' + encodeURIComponent(itemcode) + '">Modify Item Details</a>';
        document.getElementById('actionOverlay').className = 'action-panel-overlay show';
    };
    document.getElementById('actionClose').onclick = function() {
        document.getElementById('actionOverlay').className = 'action-panel-overlay';
    };
    document.getElementById('actionOverlay').onclick = function(e) {
        if (e.target === this) this.className = 'action-panel-overlay';
    };
})();
</script>
<?php
}

include('includes/footer.inc');
?>
