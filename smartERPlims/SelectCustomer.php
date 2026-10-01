<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
$Title = _('Customer List');
include('includes/header.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if (isset($_GET['itemcode'])) {
    $_SESSION['Customeritemcode'] = $_GET['itemcode'];
}
if (isset($_GET['newsearch'])) {
    unset($_SESSION['Customeritemcode']);
}
if (isset($_SESSION['Customeritemcode'])) {
    $Customeritemcode = $_SESSION['Customeritemcode'];
}

$SalesmanArray = array();
$result = DB_query("SELECT code, salesman FROM salesrepsinfo", $db);
while ($myrow = DB_fetch_array($result)) {
    $SalesmanArray[trim($myrow['code'])] = $myrow['salesman'];
}

if (isset($_SESSION['Customeritemcode'])) {

    $ErrMsg = _('The customer name requested cannot be retrieved because');
    $result = DB_query("SELECT itemcode, customer, creditlimit, phone, email, salesman, inactive, customerposting FROM debtors WHERE itemcode='" . $_SESSION['Customeritemcode'] . "'", $db, $ErrMsg);
    if ($myrow = DB_fetch_array($result)) {
        $CustomerName = htmlspecialchars($myrow['customer'], ENT_QUOTES, 'UTF-8', false);
        $PhoneNo = $myrow['phone'];
    }

    echo '<div class="page_help_text">' . _('Select a menu option to operate using this Customer ') . $CustomerName . '.</div><br />';
    echo '<table cellpadding="4" width="100%" class="table table-bordered">
                <tr>
                    <th style="width:33%">' . _('Customer Inquiries') . '</th>
                    <th style="width:33%">' . _('Customer Transactions') . '</th>
                    <th style="width:33%">' . _('Customer Maintenance') . '</th>
                </tr>';
    echo '<tr><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/PrintCustStatements.php?FromCust=' . $_SESSION['Customeritemcode'] . '&amp;ToCust=' . $_SESSION['Customeritemcode'] . '&amp;PrintPDF=Yes">' . _('Print Customer Statement') . '</a><br />';
    echo '</td><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/receipts.php?SelectedCustomer=' . $_SESSION['Customeritemcode'] . '">' . _('Record Receipts') . '</a><br />';
    echo '<a href="' . $RootPath . '/ReceitsAllocation.php?CustomerID=' . $_SESSION['Customeritemcode'] . '">' . _('Allocate Receipts OR Credit Notes') . '</a><br />';
    echo '</td><td valign="top" class="select">';
    echo '<a href="' . $RootPath . '/Customer.php">' . _('Add a New Customer') . '</a><br />';
    echo '<a href="' . $RootPath . '/Customer.php?Modify=' . $_SESSION['Customeritemcode'] . '">' . _('Modify Customer Details') . '</a><br />';
    echo '</td>';
    echo '</tr></table><br />';

} else {

    $customerData = [];
    $results = DB_query("SELECT
                            itemcode, customer, creditlimit,
                            SUM(`CustomerStatement`.`Grossamount`) as Totsales,
                            phone, email, salesman, customerposting
                         FROM debtors
                         LEFT JOIN `CustomerStatement` ON itemcode=`Accountno` AND (Documenttype=10 OR Documenttype=13) AND (`CustomerStatement`.`Date` BETWEEN DATE_SUB(NOW(), INTERVAL 12 MONTH) AND NOW())
                         GROUP BY itemcode, customer, creditlimit, phone, email, salesman, customerposting
                         ORDER BY Totsales DESC", $db);

    while ($row = DB_fetch_array($results)) {
        $code = trim($row['itemcode']);
        $customerData[] = [
            'itemcode' => $code,
            'customer' => trim($row['customer']),
            'creditlimit' => (float)$row['creditlimit'],
            'totsales' => (float)$row['Totsales'],
            'phone' => trim($row['phone'] ?? ''),
            'email' => trim($row['email'] ?? ''),
            'salesman' => $SalesmanArray[trim($row['salesman'])] ?? trim($row['salesman']),
            'unpaid' => (float)GetUnpaid($code),
        ];
    }

?>
<style>
    #customerGrid { margin-top: 10px; }
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
                <th>Customer Inquiries</th>
                <th>Customer Transactions</th>
                <th>Customer Maintenance</th>
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

<div id="customerGrid"></div>

<script>
(function(){
    var allData = <?php echo json_encode($customerData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var $root = <?php echo json_encode($RootPath); ?>;
    var $thispage = <?php echo json_encode($thispage); ?>;

    var table = new Tabulator("#customerGrid", {
        data: allData,
        layout: "fitDataFill",
        pagination: true,
        paginationSize: 50,
        paginationSizeSelector: [25, 50, 100, 250, 500],
        movableColumns: true,
        resizable: true,
        columns: [
            {title: "Customer Name", field: "customer", width: 250,
                formatter: function(cell) {
                    var data = cell.getRow().getData();
                    var a = document.createElement("a");
                    a.href = $thispage + "?itemcode=" + encodeURIComponent(data.itemcode);
                    a.textContent = data.customer;
                    return a;
                }
            },
            {title: "Credit Limit", field: "creditlimit", width: 120, hozAlign: "right",
                formatter: function(cell) {
                    return parseFloat(cell.getValue()).toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
                }
            },
            {title: "Total Sales (12 months)", field: "totsales", width: 160, hozAlign: "right",
                formatter: function(cell) {
                    return parseFloat(cell.getValue()).toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
                }
            },
            {title: "Telephone No", field: "phone", width: 130},
            {title: "Email", field: "email", width: 200},
            {title: "Sales Rep", field: "salesman", width: 150},
            {title: "Unpaid Balance", field: "unpaid", width: 130, hozAlign: "right",
                formatter: function(cell) {
                    return parseFloat(cell.getValue()).toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
                }
            },
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
            document.getElementById('rowCount').textContent = data.length + ' customers';
        }
    });

    window.openActionPanel = function(itemcode, customername) {
        document.getElementById('actionTitle').textContent = 'Actions — ' + customername + ' (' + itemcode + ')';
        document.getElementById('actionInquiries').innerHTML =
            '<a href="' + $root + '/PrintCustStatements.php?FromCust=' + encodeURIComponent(itemcode) + '&ToCust=' + encodeURIComponent(itemcode) + '&PrintPDF=Yes">Print Customer Statement</a>';
        document.getElementById('actionTransactions').innerHTML =
            '<a href="' + $root + '/receipts.php?SelectedCustomer=' + encodeURIComponent(itemcode) + '">Record Receipts</a>' +
            '<a href="' + $root + '/ReceitsAllocation.php?CustomerID=' + encodeURIComponent(itemcode) + '">Allocate Receipts OR Credit Notes</a>';
        document.getElementById('actionMaintenance').innerHTML =
            '<a href="' + $root + '/Customer.php">Add a New Customer</a>' +
            '<a href="' + $root + '/Customer.php?Modify=' + encodeURIComponent(itemcode) + '">Modify Customer Details</a>';
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

Function GetUnpaid($itemcode){
    global $db;
    $results = DB_query("SELECT SUM(`Grossamount`) FROM CustomerStatement WHERE `Accountno`='" . $itemcode . "'", $db);
    $rows = DB_fetch_row($results);
    return (int)$rows[0];
}
?>
