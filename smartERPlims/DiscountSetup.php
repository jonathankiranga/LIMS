<?php
include('includes/session.inc');

// Export template action — must run before any HTML output
if (isset($_GET['action']) && $_GET['action'] === 'export_template') {
    $category = $_GET['category'] ?? '';
    if (empty($category)) {
        echo 'No category specified';
        exit;
    }
    $catEsc = mysqli_real_escape_string($db, $category);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="discount_template_' . $category . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Item Code', 'Test Name', 'Discount %']);
    $res = mysqli_query($db, "SELECT itemcode, descrip FROM stockmaster
                               WHERE category = '$catEsc'
                               AND (inactive IS NULL OR inactive = 0)
                               AND isstock_1 = 1
                               ORDER BY descrip");
    while ($row = mysqli_fetch_assoc($res)) {
        fputcsv($out, [trim($row['itemcode']), trim($row['descrip']), '']);
    }
    fclose($out);
    exit;
}

include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');
$Title = _('Category Discount Setup');
include('includes/header.inc');

$categories = [];
$catRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription", $db);
while ($catRow = DB_fetch_array($catRes)) {
    $categories[] = [
        'id' => trim($catRow['categoryid']),
        'name' => trim($catRow['categorydescription']),
    ];
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
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 10000;
    }
    .modal-overlay.show { display: flex; align-items: center; justify-content: center; }
    .modal-box {
        background: #fff; border-radius: 6px; padding: 20px; width: 80%; max-width: 500px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3); position: relative;
    }
    .modal-box h3 { margin-top: 0; }
    .modal-box .close-btn {
        position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #888;
    }
    .modal-box .close-btn:hover { color: #000; }
    .modal-body { margin: 15px 0; }
    .modal-body label { display: block; margin-bottom: 4px; font-weight: 600; }
    .modal-body select,
    .modal-body input[type="number"] {
        width: 100%; padding: 6px 8px; font-size: 13px; margin-bottom: 12px;
        border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
    }
    .modal-footer { text-align: right; }
    .modal-footer button { margin-left: 8px; }
    .toolbar { margin-bottom: 10px; }
    .toolbar button, .toolbar a.btn {
        padding: 6px 14px; font-size: 13px; margin-right: 6px;
        border: 1px solid #ccc; border-radius: 4px; cursor: pointer;
        background: #f8f9fa; color: #333; text-decoration: none; display: inline-block;
    }
    .toolbar button:hover, .toolbar a.btn:hover { background: #e9ecef; }
    .toolbar button:disabled { opacity: 0.5; cursor: not-allowed; }
    .tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: 4px 8px; }
    .tabulator-row .tabulator-cell { padding: 4px 8px; }
    #discountGrid { margin-top: 10px; }
</style>

<div class="save-indicator" id="saveMsg"></div>

<div class="centre">
    <div style="margin-bottom:10px;">
        <label for="catSelect"><b>Category:</b></label>
        <select id="catSelect" style="padding:4px 8px; font-size:13px;">
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo htmlspecialchars($c['id'], ENT_QUOTES); ?>">
                    <?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span id="rowCount" style="margin-left:15px; color:#666;"></span>
    </div>

    <div class="toolbar">
        <button id="addBtn" disabled>+ Add Discount</button>
        <button id="importBtn" disabled>Import CSV</button>
        <a id="exportBtn" class="btn" style="pointer-events:none;opacity:0.5;">Export Template</a>
        <input type="file" id="csvFileInput" accept=".csv" style="display:none">
    </div>

    <div id="discountGrid"></div>
</div>

<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <span class="close-btn" id="modalClose">&times;</span>
        <h3>Add Discount</h3>
        <div class="modal-body">
            <label for="modalItem">Test:</label>
            <select id="modalItem"></select>
            <label for="modalDiscount">Discount %:</label>
            <input type="number" id="modalDiscount" step="0.01" min="0" max="100" value="0">
            <label>
                <input type="checkbox" id="modalActive" checked> Active
            </label>
        </div>
        <div class="modal-footer">
            <button id="modalSave" class="btn btn-info">Save</button>
            <button id="modalCancel" class="btn">Cancel</button>
        </div>
    </div>
</div>

<script>
(function(){
    var $root = <?php echo json_encode($RootPath); ?>;
    var $ajaxUrl = $root + '/Ajax/discountSetupAjax.php';
    var currentCategory = '';
    var discountTable = null;
    var toast = document.getElementById('saveMsg');

    function showToast(msg, type) {
        toast.className = 'save-indicator ' + type;
        toast.textContent = msg;
        setTimeout(function(){ toast.className = 'save-indicator'; }, 3000);
    }

    document.getElementById('catSelect').addEventListener('change', function(){
        currentCategory = this.value.trim();
        var hasCat = currentCategory !== '';
        document.getElementById('addBtn').disabled = !hasCat;
        document.getElementById('importBtn').disabled = !hasCat;
        var exportBtn = document.getElementById('exportBtn');
        if (hasCat) {
            exportBtn.href = $root + '/DiscountSetup.php?action=export_template&category=' + encodeURIComponent(currentCategory);
            exportBtn.style.pointerEvents = '';
            exportBtn.style.opacity = '';
        } else {
            exportBtn.href = '#';
            exportBtn.style.pointerEvents = 'none';
            exportBtn.style.opacity = '0.5';
        }
        loadGrid();
    });

    function loadGrid() {
        if (!currentCategory) {
            discountTable.setData([]);
            document.getElementById('rowCount').textContent = '';
            return;
        }
        fetch($ajaxUrl + '?action=get_items&category=' + encodeURIComponent(currentCategory))
            .then(function(r){ return r.json(); })
            .then(function(data){
                discountTable.setData(data);
            })
            .catch(function(){
                showToast('Failed to load discounts', 'fail');
            });
    }

    discountTable = new Tabulator("#discountGrid", {
        data: [],
        layout: "fitDataFill",
        pagination: true,
        paginationSize: 50,
        paginationSizeSelector: [25, 50, 100, 250, 500],
        movableColumns: true,
        resizable: true,
        columns: [
            {title: "Item Code", field: "itemcode", width: 120},
            {title: "Test Name", field: "descrip", width: 300},
            {title: "Discount %", field: "discount_percent", width: 120, hozAlign: "right",
                editor: "input",
                formatter: function(cell) {
                    return parseFloat(cell.getValue()).toFixed(2);
                },
                cellEdited: function(cell) {
                    var row = cell.getRow().getData();
                    var val = parseFloat(cell.getValue()) || 0;
                    if (val < 0) val = 0;
                    if (val > 100) val = 100;
                    cell.getRow().update({discount_percent: val});
                    saveDiscount(row.itemcode, val, row.is_active);
                }
            },
            {title: "Active", field: "is_active", width: 90, hozAlign: "center",
                editor: "tickCross", formatter: "tickCross",
                cellEdited: function(cell) {
                    var row = cell.getRow().getData();
                    saveDiscount(row.itemcode, row.discount_percent, cell.getValue());
                }
            },
            {title: "Actions", width: 100, hozAlign: "center", formatter: function(cell) {
                var btn = document.createElement("button");
                btn.className = "btn btn-xs btn-danger";
                btn.textContent = "Remove";
                btn.addEventListener("click", function(e) {
                    e.stopPropagation();
                    if (!confirm('Remove discount for ' + cell.getRow().getData().itemcode + '?')) return;
                    var row = cell.getRow().getData();
                    fetch($ajaxUrl + '?action=delete_discount', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'category=' + encodeURIComponent(currentCategory) + '&itemcode=' + encodeURIComponent(row.itemcode)
                    })
                    .then(function(r){ return r.json(); })
                    .then(function(resp){
                        if (resp.success) {
                            showToast('Discount removed', 'ok');
                            loadGrid();
                        } else {
                            showToast(resp.message || 'Remove failed', 'fail');
                        }
                    })
                    .catch(function(){
                        showToast('Network error', 'fail');
                    });
                });
                return btn;
            }}
        ],
        dataLoaded: function(data) {
            document.getElementById('rowCount').textContent = data.length + ' items';
        }
    });

    function saveDiscount(itemcode, pct, active) {
        var actStr = active ? 'true' : 'false';
        fetch($ajaxUrl + '?action=add_discount', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'category=' + encodeURIComponent(currentCategory)
                + '&itemcode=' + encodeURIComponent(itemcode)
                + '&discount_percent=' + encodeURIComponent(pct)
                + '&is_active=' + actStr
        })
        .then(function(r){ return r.json(); })
        .then(function(resp){
            if (!resp.success) {
                showToast(resp.message || 'Save failed', 'fail');
            }
        })
        .catch(function(){
            showToast('Network error', 'fail');
        });
    }

    // Modal
    var addModal = document.getElementById('addModal');
    var modalItem = document.getElementById('modalItem');
    var modalDiscount = document.getElementById('modalDiscount');
    var modalActive = document.getElementById('modalActive');

    document.getElementById('addBtn').addEventListener('click', function(){
        if (!currentCategory) return;
        fetch($ajaxUrl + '?action=get_available_items&category=' + encodeURIComponent(currentCategory))
            .then(function(r){ return r.json(); })
            .then(function(data){
                modalItem.innerHTML = '';
                if (data.length === 0) {
                    var opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = '-- No available items --';
                    modalItem.appendChild(opt);
                    document.getElementById('modalSave').disabled = true;
                } else {
                    document.getElementById('modalSave').disabled = false;
                    data.forEach(function(item){
                        var opt = document.createElement('option');
                        opt.value = item.itemcode;
                        opt.textContent = item.itemcode + ' - ' + item.descrip;
                        modalItem.appendChild(opt);
                    });
                }
                modalDiscount.value = '0';
                modalActive.checked = true;
                addModal.className = 'modal-overlay show';
            })
            .catch(function(){
                showToast('Failed to load available items', 'fail');
            });
    });

    function closeModal() {
        addModal.className = 'modal-overlay';
    }
    document.getElementById('modalClose').onclick = closeModal;
    document.getElementById('modalCancel').onclick = closeModal;
    addModal.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    document.getElementById('modalSave').addEventListener('click', function(){
        var itemcode = modalItem.value;
        if (!itemcode) return;
        var pct = parseFloat(modalDiscount.value) || 0;
        if (pct < 0) pct = 0;
        if (pct > 100) pct = 100;
        var active = modalActive.checked;
        var actStr = active ? 'true' : 'false';

        fetch($ajaxUrl + '?action=add_discount', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'category=' + encodeURIComponent(currentCategory)
                + '&itemcode=' + encodeURIComponent(itemcode)
                + '&discount_percent=' + encodeURIComponent(pct)
                + '&is_active=' + actStr
        })
        .then(function(r){ return r.json(); })
        .then(function(resp){
            if (resp.success) {
                showToast('Discount added', 'ok');
                closeModal();
                loadGrid();
            } else {
                showToast(resp.message || 'Save failed', 'fail');
            }
        })
        .catch(function(){
            showToast('Network error', 'fail');
        });
    });

    // Import CSV
    var csvInput = document.getElementById('csvFileInput');
    document.getElementById('importBtn').addEventListener('click', function(){
        if (!currentCategory) return;
        csvInput.click();
    });
    csvInput.addEventListener('change', function(){
        if (!this.files || !this.files[0]) return;
        var file = this.files[0];
        var fd = new FormData();
        fd.append('csv_file', file);
        fd.append('category', currentCategory);
        fd.append('action', 'import_csv');

        showToast('Importing...', 'ok');
        fetch($ajaxUrl, {
            method: 'POST',
            body: fd
        })
        .then(function(r){ return r.json(); })
        .then(function(resp){
            if (resp.success) {
                showToast(resp.message, 'ok');
                loadGrid();
            } else {
                showToast(resp.message || 'Import failed', 'fail');
            }
        })
        .catch(function(){
            showToast('Network error', 'fail');
        });
        this.value = '';
    });
})();
</script>

<?php include('includes/footer.inc'); ?>
