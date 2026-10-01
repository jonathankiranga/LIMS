<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Expiry Report</title>
    <?php 
    session_write_close();
    session_name('ErpWithCRM');
    session_start();
    
    include('config.php');
    $database = $_SESSION['DatabaseName'];
    $db = mysqli_connect($host, $DBUser, $DBPassword, $database);
    mysqli_set_charset($db, 'utf8mb4');
    
    function DB_query($sql, $conn) { return mysqli_query($conn, $sql); }
    function DB_fetch_assoc($res) { return mysqli_fetch_assoc($res); }
    function DB_num_rows($res) { return mysqli_num_rows($res); }
    ?>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .report-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .report-header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .report-header .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .report-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f8f8;
            border-radius: 4px;
        }
        
        .report-meta div { color: #555; }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .summary-card {
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        .summary-card.expired { background: #ffebee; border-left: 4px solid #d32f2f; }
        .summary-card.warning { background: #fff3e0; border-left: 4px solid #f57c00; }
        .summary-card.ok { background: #e8f5e9; border-left: 4px solid #388e3c; }
        .summary-card.total { background: #e3f2fd; border-left: 4px solid #1976d2; }
        
        .summary-card h3 { font-size: 11px; text-transform: uppercase; margin-bottom: 5px; opacity: 0.7; }
        .summary-card .count { font-size: 28px; font-weight: bold; }
        
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .filter-section label { font-weight: 600; margin-right: 10px; }
        .filter-section select, .filter-section input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #333;
            color: white;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        tr:hover { background: #f5f5f5; }
        
        tr.expired { background: #ffebee; }
        tr.expired td:first-child { border-left: 4px solid #d32f2f; }
        
        tr.warning { background: #fff8e1; }
        tr.warning td:first-child { border-left: 4px solid #f57c00; }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.expired { background: #d32f2f; color: white; }
        .status-badge.warning { background: #f57c00; color: white; }
        .status-badge.ok { background: #388e3c; color: white; }
        
        .qty-cell { text-align: right; font-family: monospace; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .report-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #777;
            font-size: 11px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }
        
        .btn-primary { background: #1976d2; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        @media screen {
            body { background: #333; }
            .report-container { box-shadow: 0 5px 30px rgba(0,0,0,0.3); }
        }
    </style>
</head>
<body>
<?php 
$filterStatus = $_GET['status'] ?? 'all';
$filterItem = $_GET['itemcode'] ?? '';
$filterSerial = $_GET['serial'] ?? '';
$asOfDate = $_GET['asofdate'] ?? date('Y-m-d');
?>

<div class="report-container">
    <div class="report-header no-print">
        <h1>Inventory Expiry Report</h1>
        <div class="subtitle">Stock batches with expiry date tracking</div>
    </div>
    
    <div class="report-meta no-print">
        <div><strong>Generated:</strong> <?php echo date('d-M-Y H:i'); ?></div>
        <div><strong>As of:</strong> <?php echo date('d-M-Y', strtotime($asOfDate)); ?></div>
    </div>
    
    <div class="summary-cards">
        <?php
        $today = date('Y-m-d');
        $thirtyDays = date('Y-m-d', strtotime('+30 days'));
        
        $statsSql = "SELECT 
            SUM(CASE WHEN expiry_date < '$today' THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN expiry_date BETWEEN '$today' AND '$thirtyDays' THEN 1 ELSE 0 END) as warning,
            SUM(CASE WHEN expiry_date > '$thirtyDays' THEN 1 ELSE 0 END) as ok,
            COUNT(*) as total
            FROM StockRegister 
            WHERE expiry_date IS NOT NULL 
              AND (StockIn - IFNULL(StockOut,0)) > 0";
        
        $statsResult = DB_query($statsSql, $db);
        $stats = DB_fetch_assoc($statsResult);
        ?>
        <div class="summary-card expired">
            <h3>Expired</h3>
            <div class="count"><?php echo (int)$stats['expired']; ?></div>
        </div>
        <div class="summary-card warning">
            <h3>Expiring Soon</h3>
            <div class="count"><?php echo (int)$stats['warning']; ?></div>
        </div>
        <div class="summary-card ok">
            <h3>OK</h3>
            <div class="count"><?php echo (int)$stats['ok']; ?></div>
        </div>
        <div class="summary-card total">
            <h3>Total Batches</h3>
            <div class="count"><?php echo (int)$stats['total']; ?></div>
        </div>
    </div>
    
    <div class="filter-section no-print">
        <form method="GET" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
            <label>Status:</label>
            <select name="status">
                <option value="all" <?php echo $filterStatus=='all'?'selected':''; ?>>All</option>
                <option value="expired" <?php echo $filterStatus=='expired'?'selected':''; ?>>Expired</option>
                <option value="warning" <?php echo $filterStatus=='warning'?'selected':''; ?>>Expiring Soon</option>
                <option value="ok" <?php echo $filterStatus=='ok'?'selected':''; ?>>OK</option>
            </select>
            
            <label>Item:</label>
            <input type="text" name="itemcode" value="<?php echo htmlspecialchars($filterItem); ?>" placeholder="Enter item code">
            
            <label>Serial:</label>
            <input type="text" name="serial" value="<?php echo htmlspecialchars($filterSerial); ?>" placeholder="Enter serial number">
            
            <label>As of:</label>
            <input type="date" name="asofdate" value="<?php echo $asOfDate; ?>">
            
            <button type="submit" class="btn btn-primary">Filter</button>
            <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
        </form>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Serial No</th>
                <th>Item Code</th>
                <th>Description</th>
                <th>GRN</th>
                <th>Batch Ref</th>
                <th>Expiry Date</th>
                <th class="text-right">Qty</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $whereClause = "WHERE sr.expiry_date IS NOT NULL AND (sr.StockIn - IFNULL(sr.StockOut,0)) > 0";
            
            if ($filterStatus == 'expired') {
                $whereClause .= " AND sr.expiry_date < '$today'";
            } elseif ($filterStatus == 'warning') {
                $whereClause .= " AND sr.expiry_date BETWEEN '$today' AND '$thirtyDays'";
            } elseif ($filterStatus == 'ok') {
                $whereClause .= " AND sr.expiry_date > '$thirtyDays'";
            }
            
            if (!empty($filterItem)) {
                $filterItem = $db->real_escape_string($filterItem);
                $whereClause .= " AND sr.itemcode LIKE '%$filterItem%'";
            }
            
            if (!empty($filterSerial)) {
                $filterSerial = $db->real_escape_string($filterSerial);
                $whereClause .= " AND sr.serial LIKE '%$filterSerial%'";
            }
            
            $sql = "SELECT 
                sr.serial,
                sr.itemcode,
                sm.description,
                sr.GRN,
                sr.batch_reference,
                sr.expiry_date,
                (sr.StockIn - IFNULL(sr.StockOut,0)) AS StockBalance
            FROM StockRegister sr
            LEFT JOIN stockmaster sm ON sr.itemcode = sm.itemcode
            $whereClause
            ORDER BY sr.serial ASC, sr.expiry_date ASC";
            
            $result = DB_query($sql, $db);
            
            while ($row = DB_fetch_assoc($result)) {
                $daysLeft = (strtotime($row['expiry_date']) - strtotime($today)) / 86400;
                
                if ($daysLeft < 0) {
                    $status = 'expired';
                    $statusLabel = 'EXPIRED';
                } elseif ($daysLeft <= 30) {
                    $status = 'warning';
                    $statusLabel = $daysLeft . ' days';
                } else {
                    $status = 'ok';
                    $statusLabel = 'OK';
                }
            ?>
            <tr class="<?php echo $status; ?>">
                <td><?php echo htmlspecialchars($row['serial'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['itemcode']); ?></td>
                <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['GRN'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['batch_reference'] ?? '-'); ?></td>
                <td><?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></td>
                <td class="qty-cell"><?php echo number_format($row['StockBalance'], 2); ?></td>
                <td class="text-center">
                    <span class="status-badge <?php echo $status; ?>"><?php echo $statusLabel; ?></span>
                </td>
            </tr>
            <?php } ?>
            
            <?php if (DB_num_rows($result) == 0) { ?>
            <tr>
                <td colspan="8" class="text-center" style="padding: 30px;">
                    No records found
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    
    <div class="report-footer">
        <p>Printed by: <?php echo $_SESSION['UserID']; ?> | <?php echo date('d-M-Y H:i:s'); ?></p>
    </div>
</div>


</body>
</html>