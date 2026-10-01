<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
$Title = _('Supplier List');
include('includes/header.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if (isset($_GET['itemcode'])) {
    $_SESSION['Supplieritemcode'] = $_GET['itemcode'];
}
if (isset($_GET['newsearch'])) {
    unset($_SESSION['Supplieritemcode']);
}
if (isset($_SESSION['Supplieritemcode'])) {
    $Supplieritemcode = $_SESSION['Supplieritemcode'];
}

if (isset($_SESSION['Supplieritemcode'])) {

    $ErrMsg = _('The Vendor name requested cannot be retrieved because');
    $result = DB_query("SELECT itemcode, customer, inactive, phone, email, supplierposting, IsEmployee FROM creditors WHERE itemcode='" . $_SESSION['Supplieritemcode'] . "'", $db, $ErrMsg);
    if ($myrow = DB_fetch_array($result)) {
        $SupplierName = htmlspecialchars($myrow['customer'], ENT_QUOTES, 'UTF-8', false);
        $PhoneNo = $myrow['phone'];
    }

    echo '<div class="page_help_text">' . _('Select a menu option to operate using this Supplier ') . $SupplierName . '.</div><br />';
    echo '<table cellpadding="4" width="100%" class="table table-bordered">
                <tr>
                    <th style="width:33%">' . _('Supplier Inquiries') . '</th>
                    <th style="width:33%">' . _('Supplier Transactions') . '</th>
                    <th style="width:33%">' . _('Supplier Maintenance') . '</th>
                </tr>';
    echo '<tr><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/PrintvendorStatements.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '">' . _('Supplier Account Inquiry') . '</a><br />';
    echo '</td><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/EnterBills.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '&new=1">' . _('Enter Invoice') . '</a><br />';
    echo '<a href="' . $RootPath . '/PaymentVoucher.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '">' . _('Make Payment') . '</a><br />';
    echo '</td><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/Supplier.php">' . _('Add a New Supplier') . '</a><br />';
    echo '<a href="' . $RootPath . '/Supplier.php?Modify=' . $_SESSION['Supplieritemcode'] . '">' . _('Modify Or Delete Supplier Details') . '</a><br />';
    echo '</td>';
    echo '</tr></table><br />';

} else {

    $supplierData = [];
    $results = DB_query("SELECT itemcode, customer, inactive, phone, email, supplierposting, IsEmployee FROM creditors ORDER BY customer ASC", $db);

    while ($row = DB_fetch_array($results)) {
        $supplierData[] = [
            'itemcode' => trim($row['itemcode']),
            'customer' => trim($row['customer']),
            'type' => $row['IsEmployee'] == 1 ? 'Employee' : 'Supplier',
            'phone' => trim($row['phone'] ?? ''),
            'email' => trim($row['email'] ?? ''),
            'supplierposting' => trim($row['supplierposting'] ?? ''),
            'status' => $row['inactive'] == 1 ? 'Blocked' : 'Open',
        ];
    }

?>
<style>
    #supplierGrid { margin-top: 10px; }
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

<div class="action-panel-overlay" id="actionOverlay">
    <div class="action-panel-box">
        <span class="close-btn" id="actionClose">&times;</span>
        <h3 id="actionTitle">Actions</h3>
        <table class="table table-bordered">
            <tr>
                <th>Supplier Inquiries</th>
                <th>Supplier Transactions</th>
                <th>Supplier Maintenance</th>
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
    <span id="rowCount" style="color:#666;"></span>
</div>

<div id="supplierGrid"></div>

<script>
(function(){
    var allData = <?php echo json_encode($supplierData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var $root = <?php echo json_encode($RootPath); ?>;
    var $thispage = <?php echo json_encode($thispage); ?>;

    var table = new Tabulator("#supplierGrid", {
        data: allData,
        layout: "fitDataFill",
        pagination: true,
        paginationSize: 50,
        paginationSizeSelector: [25, 50, 100, 250, 500],
        movableColumns: true,
        resizable: true,
        columns: [
            {title: "Vendor Name", field: "customer", width: 250,
                formatter: function(cell) {
                    var data = cell.getRow().getData();
                    var a = document.createElement("a");
                    a.href = $thispage + "?itemcode=" + encodeURIComponent(data.itemcode);
                    a.textContent = data.customer;
                    return a;
                }
            },
            {title: "Type", field: "type", width: 100},
            {title: "Telephone No", field: "phone", width: 130},
            {title: "Email", field: "email", width: 200},
            {title: "Posting Group", field: "supplierposting", width: 130},
            {title: "Status", field: "status", width: 90, hozAlign: "center"},
            {title: "Actions", width: 90, hozAlign: "center", formatter: function(cell) {
                var btn = document.createElement("button");
                btn.className = "btn btn-xs btn-info action-btn";
                btn.textContent = "Actions";
                btn.addEventListener("click", function(e) {
                    e.stopPropagation();
                    var d = cell.getRow().getData();
                    openActionPanel(d.itemcode, d.customer);
                });
                return btn;
            }}
        ],
        dataLoaded: function(data) {
            document.getElementById('rowCount').textContent = data.length + ' suppliers';
        }
    });

    window.openActionPanel = function(itemcode, suppname) {
        document.getElementById('actionTitle').textContent = 'Actions — ' + suppname + ' (' + itemcode + ')';
        document.getElementById('actionInquiries').innerHTML =
            '<a href="' + $root + '/PrintvendorStatements.php?SupplierID=' + encodeURIComponent(itemcode) + '">Supplier Account Inquiry</a>';
        document.getElementById('actionTransactions').innerHTML =
            '<a href="' + $root + '/EnterBills.php?SupplierID=' + encodeURIComponent(itemcode) + '&new=1">Enter Invoice</a>' +
            '<a href="' + $root + '/PaymentVoucher.php?SupplierID=' + encodeURIComponent(itemcode) + '">Make Payment</a>';
        document.getElementById('actionMaintenance').innerHTML =
            '<a href="' + $root + '/Supplier.php">Add a New Supplier</a>' +
            '<a href="' + $root + '/Supplier.php?Modify=' + encodeURIComponent(itemcode) + '">Modify Or Delete Supplier Details</a>';
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
