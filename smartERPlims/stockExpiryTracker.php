<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Expiry Tracker</title>
    <link rel="stylesheet" type="text/css"/> 
      <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        .expiry-dashboard {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .expiry-dashboard h2 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #333;
        }

        .expiry-stats-row {
            display: flex;
            flex-direction: row;
            gap: 16px;
            margin-bottom: 20px;
        }

        .expiry-stats-row .expiry-card {
            flex: 1;
            margin-bottom: 0;
        }

        .expiry-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .expiry-card h5 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .expiry-card h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .expiry-card small {
            font-size: 12px;
            opacity: 0.75;
        }

        .expiry-danger {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .expiry-danger h5,
        .expiry-danger h2,
        .expiry-danger small {
            color: #721c24;
        }

        .expiry-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .expiry-warning h5,
        .expiry-warning h2,
        .expiry-warning small {
            color: #856404;
        }

        .expiry-good {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }

        .expiry-good h5,
        .expiry-good h2,
        .expiry-good small {
            color: #155724;
        }

        .expiry-card .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .expiry-card .toolbar .filter-wrap {
            flex: 1;
            max-width: 320px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-success  { background: #28a745; color: white; }
        .btn-primary  { background: #007bff; color: white; }
        .btn-secondary{ background: #6c757d; color: white; }
        .btn-warning  { background: #ffc107; color: #212529; }
        .btn-danger   { background: #dc3545; color: white; }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table.table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table.table thead tr {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        table.table th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            white-space: nowrap;
        }

        table.table td {
            padding: 9px 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        table.table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge-expiry {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-expired { background: #dc3545; color: white; }
        .badge-soon    { background: #ffc107; color: #212529; }
        .badge-ok      { background: #28a745; color: white; }

        .form-select,
        .form-control {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #495057;
        }

        .mb-3 { margin-bottom: 16px; }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1050;
            overflow-y: auto;
            padding: 20px 0;
        }

        .modal.show {
            display: block;
        }

        .modal-dialog {
            background: white;
            border-radius: 10px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 600;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }

        .modal-dialog {
            background: white;
            border-radius: 10px;
            width: 100%;
            max-width: 480px;
            max-height: 85vh;
            margin: 20px auto;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            overflow-y: auto;
        }

        .modal-body {
            padding: 20px;
            max-height: calc(85vh - 120px);
            overflow-y: auto;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 14px 20px;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .expiry-stats-row {
                flex-wrap: wrap;
            }
            .expiry-stats-row .expiry-card {
                flex: 1 1 calc(50% - 8px);
            }
        }

        @media (max-width: 480px) {
            .expiry-stats-row .expiry-card {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
<?php
session_write_close();
session_name('ErpWithCRM');
session_start();
include('config.php');
$database = $_SESSION['DatabaseName'];
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);
mysqli_set_charset($db, 'utf8mb4');
include('includes/ClosePeriods.inc');
?>
<div class="expiry-dashboard">
    <h2>Inventory Expiry Tracker</h2>

    <div class="expiry-stats-row">
        <div class="expiry-card expiry-danger">
            <h5>Expired</h5>
            <h2 id="expiredCount">0</h2>
            <small>Items past expiry date</small>
        </div>
        <div class="expiry-card expiry-warning">
            <h5>Expiring Soon</h5>
            <h2 id="expiringSoonCount">0</h2>
            <small>Within 30 days</small>
        </div>
        <div class="expiry-card expiry-good">
            <h5>OK</h5>
            <h2 id="okCount">0</h2>
            <small>More than 30 days</small>
        </div>
        <div class="expiry-card">
            <h5>Total Batches</h5>
            <h2 id="totalCount">0</h2>
            <small>Tracked batches</small>
        </div>
    </div>

    <div class="expiry-card">
        <div class="toolbar">
            <button class="btn btn-success" onclick="openModal()">+ Add Expiry Entry</button>
            <div class="filter-wrap">
                <select id="itemFilter" class="form-select" style="width: 100%;">
                    <option value="">All Items</option>
                </select>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table" id="expiryTable">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>GRN/Batch</th>
                        <th>Batch Ref</th>
                        <th>Expiry Date</th>
                        <th>Quantity</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="expiryTableBody">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Expiry Modal -->
<div class="modal" id="addExpiryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Expiry Entry</h5>
                <button type="button" class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="addExpiryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <input type="text" id="itemSearch" class="form-control" placeholder="Search item..." oninput="searchItem(this.value)" style="width: 50%; margin-bottom: 5px;">
                        <select name="itemcode" id="itemSelect" class="form-select" required style="width: 50%; max-height: 150px; overflow-y: auto;" onchange="onItemChange()" size="5"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GRN (Available Stock)</label>
                        <select name="GRN" id="grnSelect" class="form-select" required onchange="onGrnChange()"></select>
                        <small id="balanceInfo" style="color: #155724; display: block; margin-top: 4px;"></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Reference</label>
                        <input type="text" name="batch_reference" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity (max: <span id="maxQty">0</span>)</label>
                        <input type="number" name="quantity" id="qtyInput" class="form-control" step="0.01" required min="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="javascripts/jquery-3.6.0.min.js"></script>
<script>
function openModal()  { document.getElementById('addExpiryModal').classList.add('show'); }
function closeModal() { document.getElementById('addExpiryModal').classList.remove('show'); }

document.addEventListener('DOMContentLoaded', function() {
    loadExpiryData();
    loadItemDropdown();

    var itemFilter = document.getElementById('itemFilter');
    if (itemFilter) {
        itemFilter.addEventListener('change', function() {
            loadExpiryData(this.value);
        });
    }

    var addExpiryForm = document.getElementById('addExpiryForm');
    if (addExpiryForm) {
        addExpiryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var grnSelect = document.getElementById('grnSelect');
            var qtyInput = document.getElementById('qtyInput');
            var grnValue = grnSelect.value;
            var grnText = grnSelect.options[grnSelect.selectedIndex].text.split(' | ')[0];
            var qtyValue = parseFloat(qtyInput.value);
            var maxQty = parseFloat(qtyInput.max) || 0;
            
            if (!grnValue) {
                alert('Please select a GRN');
                return;
            }
            
            if (qtyValue > maxQty) {
                alert('Quantity cannot exceed available balance of ' + maxQty);
                return;
            }
            
            var formData = new FormData(this);
            formData.set('rowid', grnValue);
            formData.set('GRN', grnText);
            formData.set('quantity', qtyValue);
            
            fetch('ajax/saveExpiryTracker.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(res) {
                if (res.success) {
                    alert('Expiry entry saved!');
                    closeModal();
                    document.getElementById('addExpiryForm').reset();
                    document.getElementById('grnSelect').innerHTML = '';
                    document.getElementById('balanceInfo').textContent = '';
                    document.getElementById('maxQty').textContent = '0';
                    loadExpiryData();
                } else {
                    alert(res.message);
                }
            });
        });
    }
});

function onItemChange() {
    var itemSelect = document.getElementById('itemSelect');
    var grnSelect = document.getElementById('grnSelect');
    var balanceInfo = document.getElementById('balanceInfo');
    var maxQty = document.getElementById('maxQty');
    var qtyInput = document.getElementById('qtyInput');
    
    var itemcode = itemSelect.value;
    grnSelect.innerHTML = '<option value="">Loading...</option>';
    balanceInfo.textContent = '';
    maxQty.textContent = '0';
    qtyInput.value = '';
    qtyInput.max = '';
    
    if (!itemcode) {
        grnSelect.innerHTML = '<option value="">Select Item first</option>';
        return;
    }
    
    fetch('ajax/getStockBalance.php?itemcode=' + encodeURIComponent(itemcode))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            grnSelect.innerHTML = '<option value="">Select GRN</option>';
            
            if (data.length === 0) {
                grnSelect.innerHTML = '<option value="">No stock available</option>';
                return;
            }
            
            data.forEach(function(stock) {
                var expiryDisplay = stock.expiry_date ? ' | Exp: ' + stock.expiry_date : '';
                var text = stock.GRN + ' | Bal: ' + stock.StockBalance + expiryDisplay;
                var opt = new Option(text, stock.rowid, false, false);
                opt.dataset.balance = stock.StockBalance;
                grnSelect.add(opt);
            });
        });
}

function onGrnChange() {
    var grnSelect = document.getElementById('grnSelect');
    var balanceInfo = document.getElementById('balanceInfo');
    var maxQty = document.getElementById('maxQty');
    var qtyInput = document.getElementById('qtyInput');
    
    var selectedOpt = grnSelect.options[grnSelect.selectedIndex];
    var balance = selectedOpt ? selectedOpt.dataset.balance : 0;
    var grnText = selectedOpt ? selectedOpt.text : '';
    
    balanceInfo.textContent = 'Available: ' + balance;
    maxQty.textContent = balance;
    qtyInput.max = balance;
}

function loadExpiryData(itemcode) {
    var url = 'ajax/getExpiryTracker.php';
    if (itemcode) {
        url += '?itemcode=' + encodeURIComponent(itemcode);
    }
    fetch(url)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            renderExpiryTable(data.items);
            updateCounts(data.stats);
        });
}

function renderExpiryTable(items) {
    var tbody = document.getElementById('expiryTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No records found</td></tr>';
        return;
    }

    var today = new Date();

    items.forEach(function(item) {
        var expiry = new Date(item.expiry_date);
        var daysUntil = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

        var badge;
        if (daysUntil < 0) {
            badge = '<span class="badge-expiry badge-expired">EXPIRED</span>';
        } else if (daysUntil <= 30) {
            badge = '<span class="badge-expiry badge-soon">' + daysUntil + ' days</span>';
        } else {
            badge = '<span class="badge-expiry badge-ok">' + daysUntil + ' days</span>';
        }

        var row = document.createElement('tr');
        row.innerHTML =
            '<td>' + item.itemcode + '</td>' +
            '<td>' + (item.description || '') + '</td>' +
            '<td>' + (item.GRN || '-') + '</td>' +
            '<td>' + (item.batch_reference || '-') + '</td>' +
            '<td>' + item.expiry_date + '</td>' +
            '<td>' + item.quantity + '</td>' +
            '<td>' + item.remaining_qty + '</td>' +
            '<td>' + badge + '</td>' +
            '<td>' +
            '<button class="btn btn-sm btn-warning" onclick="editExpiry(' + item.id + ')">Edit</button> ' +
            '<button class="btn btn-sm btn-danger" onclick="deleteExpiry(' + item.id + ')">Del</button>' +
            '</td>';
        tbody.appendChild(row);
    });
}

function updateCounts(stats) {
    var expiredEl = document.getElementById('expiredCount');
    var expiringEl = document.getElementById('expiringSoonCount');
    var okEl = document.getElementById('okCount');
    var totalEl = document.getElementById('totalCount');

    if (expiredEl) expiredEl.textContent = stats.expired || 0;
    if (expiringEl) expiringEl.textContent = stats.expiring_soon || 0;
    if (okEl) okEl.textContent = stats.ok || 0;
    if (totalEl) totalEl.textContent = stats.total || 0;
}

function loadItemDropdown() {
    searchItem('');
}

function searchItem(term) {
    var url = 'ajax/searchInventory.php?q=' + encodeURIComponent(term || '');
    fetch(url)
        .then(function(response) { return response.json(); })
        .then(function(items) {
            var select = document.getElementById('itemSelect');
            var filter = document.getElementById('itemFilter');
            select.innerHTML = '<option value="">Select Item</option>';
            filter.innerHTML = '<option value="">All Items</option>';
            items.forEach(function(item) {
                var opt1 = new Option(item.text, item.id, false, false);
                var opt2 = new Option(item.text, item.id, false, false);
                select.add(opt1);
                filter.add(opt2);
            });
        });
}



function editExpiry(id) {
    alert('Edit ID: ' + id);
}

function deleteExpiry(id) {
    if (confirm('Delete this expiry entry?')) {
        $.ajax({
            url: 'ajax/deleteExpiryTracker.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                const res = JSON.parse(response);
                if (res.success) {
                    alert('Entry deleted');
                    loadExpiryData();
                }
            }
        });
    }
}
</script>
</body>
</html>