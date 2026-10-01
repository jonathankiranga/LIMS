<?php
$PageSecurity = 0;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');
$databaseType = $_SESSION['DatabaseType'] ?? 'LIMS';
$menuFile = 'includes/MainMenuLinksArray_' . strtolower($databaseType) . '.php';
if (file_exists($menuFile)) {
    include_once($menuFile);
} else {
    include_once('includes/MainMenuLinksArray_lims.php');
}

$RootPath = isset($RootPath) ? $RootPath : '.';
$userName = isset($_SESSION['UsersRealName']) ? $_SESSION['UsersRealName'] : 'User';
$companyName = isset($_SESSION['CompanyRecord']['coyname']) ? $_SESSION['CompanyRecord']['coyname'] : 'SmartERP';
$versionNumber = isset($_SESSION['VersionNumber']) ? $_SESSION['VersionNumber'] : '';

$moduleIcons = array(
    'Approval' => 'fa-check-circle',
    'Sales' => 'fa-receipt',
    'AccountsReceivable' => 'fa-handshake',
    'Inventory' => 'fa-warehouse',
    'cRM' => 'fa-address-book',
    'Purchases' => 'fa-shopping-cart',
    'AccountsPayable' => 'fa-money-check-dollar',
    'CashManagement' => 'fa-university',
    'GeneralLedger' => 'fa-book',
    'FixedAssets' => 'fa-building',
    'system' => 'fa-cogs'
);

$moduleColors = array(
    'Approval' => '#e74c3c',
    'Sales' => '#875F7A',
    'AccountsReceivable' => '#3498db',
    'Inventory' => '#f39c12',
    'cRM' => '#1abc9c',
    'Purchases' => '#9b59b6',
    'AccountsPayable' => '#e67e22',
    'CashManagement' => '#2ecc71',
    'GeneralLedger' => '#34495e',
    'FixedAssets' => '#16a085',
    'system' => '#7f8c8d'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart ERP - Home</title>
    <link rel="icon" type="image/x-icon" href="<?php echo $RootPath; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo $RootPath; ?>/css/bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo $RootPath; ?>/css/fontawesome6.4.0.all.min.css">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; font-family: "Segoe UI", Tahoma, Arial, sans-serif; background: #f0f2f5; color: #0f172a; }
        .banner {
            height: 56px; display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        }
        .banner-meta { display: flex; align-items: center; gap: 18px; font-size: 13px; white-space: nowrap; }
        .banner-actions { display: flex; align-items: center; gap: 14px; }
        .banner-link, .banner-link:visited { color: #000; font-weight: 600; }
        .main-wrap {
            padding-top: 56px; min-height: 100vh; display: flex; flex-direction: column;
        }
        .page-title {
            text-align: center; padding: 40px 20px 10px; font-size: 24px; font-weight: 700; color: #1e293b;
        }
        .module-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;
            max-width: 960px; margin: 20px auto 40px; padding: 0 20px; flex: 1;
        }
        .module-tile {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 36px 16px 28px; border-radius: 16px; color: #fff; text-decoration: none;
            transition: transform 0.25s, box-shadow 0.25s;
            background: var(--tile-color); cursor: pointer; min-height: 160px;
        }
        .module-tile i { font-size: 48px; margin-bottom: 14px; }
        .module-tile span { font-size: 15px; font-weight: 600; text-align: center; line-height: 1.3; }
        .module-tile:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(0,0,0,0.2); }
        .footer {
            height: 38px; display: flex; align-items: center; justify-content: center;
            padding: 0 14px; background: #fff; font-size: 12px; color: #64748b;
            border-top: 1px solid #d1d5db;
        }
        @media (max-width: 768px) {
            .module-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .module-tile { min-height: 130px; padding: 24px 12px 20px; }
            .module-tile i { font-size: 36px; }
        }
        @media (max-width: 480px) {
            .module-grid { grid-template-columns: 1fr; gap: 12px; }
        }
    </style>
</head>
<body>
    <header class="banner">
        <div class="banner-meta">
            <span><strong>User:</strong> <?php echo htmlspecialchars($userName); ?></span>
            <span><strong>Company:</strong> <?php echo htmlspecialchars($companyName); ?></span>
            <?php if ($versionNumber !== '') : ?>
                <span><strong>Version:</strong> <?php echo htmlspecialchars($versionNumber); ?></span>
            <?php endif; ?>
        </div>
        <div class="banner-actions">
            <span id="bannerClock"><?php echo date('D, d M Y H:i'); ?></span>
            <a class="banner-link" href="<?php echo $RootPath; ?>/doc/Manual/SystemManual.html" target="_blank"><i class="fas fa-question-circle"></i> Help</a>
            <a class="banner-link" href="<?php echo $RootPath; ?>/Logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
    </header>
    <div class="main-wrap">
        <div class="page-title"><i class="fas fa-th-large"></i> Select Module</div>
        <div class="module-grid">
            <?php
            for ($i = 0; $i < count($ModuleLink); $i++) {
                $moduleKey = $ModuleLink[$i];
                $enabled = true;
                if (isset($_SESSION['ModulesEnabled'])) {
                    if (isset($_SESSION['ModulesEnabled'][$moduleKey])) {
                        $enabled = ($_SESSION['ModulesEnabled'][$moduleKey] == 1);
                    } elseif (isset($_SESSION['ModulesEnabled'][$i])) {
                        $enabled = ($_SESSION['ModulesEnabled'][$i] == 1);
                    }
                }
                if (!$enabled) continue;
                $label = $ModuleList[$i];
                $icon = $moduleIcons[$moduleKey] ?? 'fa-box';
                $color = $moduleColors[$moduleKey] ?? '#6366f1';
            ?>
                <a href="homepageApp.php?module=<?php echo urlencode($moduleKey); ?>" class="module-tile" style="--tile-color: <?php echo $color; ?>">
                    <i class="fas <?php echo $icon; ?>"></i>
                    <span><?php echo htmlspecialchars($label); ?></span>
                </a>
            <?php } ?>
        </div>
        <footer class="footer">
            <span><?php echo htmlspecialchars($companyName); ?> &copy; <?php echo date('Y'); ?></span>
        </footer>
    </div>
    <script src="<?php echo $RootPath; ?>/javascripts/jquery-3.6.0.min.js"></script>
    <script>
        function tickClock() {
            document.getElementById('bannerClock').textContent = new Date().toLocaleString();
        }
        tickClock();
        setInterval(tickClock, 1000);
    </script>
</body>
</html>
