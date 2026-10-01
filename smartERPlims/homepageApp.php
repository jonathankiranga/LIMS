<?php
$PageSecurity = 0;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$RootPath = isset($RootPath) ? $RootPath : '.';

$databaseType = $_SESSION['DatabaseType'] ?? 'LIMS';
$menuFile = 'includes/MainMenuLinksArray_' . strtolower($databaseType) . '.php';

if (file_exists($menuFile)) {
    include_once($menuFile);
} else {
    include_once('includes/MainMenuLinksArray_lims.php');
}

$userName = isset($_SESSION['UsersRealName']) ? $_SESSION['UsersRealName'] : 'User';
$companyName = isset($_SESSION['CompanyRecord']['coyname']) ? $_SESSION['CompanyRecord']['coyname'] : 'SmartERP';
$versionNumber = isset($_SESSION['VersionNumber']) ? $_SESSION['VersionNumber'] : '';

$module = $_GET['module'] ?? $_SESSION['currentModule'] ?? '';
if (empty($module) || !in_array($module, $ModuleLink)) {
    header('Location: homepage.php');
    exit;
}
$_SESSION['currentModule'] = $module;

$moduleIndex = array_search($module, $ModuleLink);
$moduleLabel = ($moduleIndex !== false && isset($ModuleList[$moduleIndex])) ? $ModuleList[$moduleIndex] : $module;

$legacyMenuAdditions = array(
    'Sales' => array(
        'Transactions' => array(
            array('Service Invoicing', 'EnterSalesBills.php'),
        ),
        'Reports' => array(
            array('Print Service Invoicing', 'PDFenterSalesbills.php'),
        ),
    ),
    'AccountsReceivable' => array(
        'Transactions' => array(
            array('Auto Allocation', 'autoallocate.php'),
        ),
    ),
    'CashManagement' => array(
        'Reports' => array(
            array('Cash Flow Report', 'CashFlowReport.php'),
        ),
    ),
    'Inventory' => array(
        'Reports' => array(
            array('Stock Expiry Report', 'ExpiryReport.php'),
        ),
    ),
    'GeneralLedger' => array(
        'Transactions' => array(
            array('Modify General Journal', 'EditJournal.php'),
        ),
        'Maintenance' => array(
            array('Manage Discrepancy', 'fellowgroups.php'),
            array('Merge Inventory', 'mergestock.php'),
        ),
    ),
);

if (isset($legacyMenuAdditions[$module])) {
    foreach ($legacyMenuAdditions[$module] as $sectionKey => $items) {
        foreach ($items as $item) {
            $MenuItems[$module][$sectionKey]['Caption'][] = $item[0];
            $MenuItems[$module][$sectionKey]['URL'][] = '/' . $item[1];
        }
    }
}

function homepageBuildMenuItems($moduleLinks, $moduleNames, $menuItems) {
    $builtItems = array();
    for ($i = 0; $i < count($moduleLinks); $i++) {
        $moduleKey = $moduleLinks[$i];
        $moduleEnabled = true;
        if (isset($_SESSION['ModulesEnabled']) && is_array($_SESSION['ModulesEnabled'])) {
            if (isset($_SESSION['ModulesEnabled'][$moduleKey])) {
                $moduleEnabled = ($_SESSION['ModulesEnabled'][$moduleKey] == 1);
            } elseif (isset($_SESSION['ModulesEnabled'][$i])) {
                $moduleEnabled = ($_SESSION['ModulesEnabled'][$i] == 1);
            }
        }
        if (!$moduleEnabled) continue;
        if (!isset($menuItems[$moduleKey]) || !is_array($menuItems[$moduleKey])) continue;
        $moduleEntries = array(
            array(
                'text' => trim(strip_tags($moduleNames[$i])),
                'url' => ''
            )
        );
        foreach (array('Transactions', 'Reports', 'Maintenance') as $sectionKey) {
            if (empty($menuItems[$moduleKey][$sectionKey]['Caption']) || empty($menuItems[$moduleKey][$sectionKey]['URL'])) continue;
            $sectionEntries = array();
            $captions = $menuItems[$moduleKey][$sectionKey]['Caption'];
            $urls = $menuItems[$moduleKey][$sectionKey]['URL'];
            foreach ($captions as $index => $caption) {
                if (!isset($urls[$index])) continue;
                $url = $urls[$index];
                $scriptNameArray = explode('?', ltrim($url, '/'));
                $scriptName = $scriptNameArray[0];
                $pageSecurity = isset($_SESSION['PageSecurityArray'][$scriptName]) ? $_SESSION['PageSecurityArray'][$scriptName] : null;
                if ($pageSecurity !== null && !in_array($pageSecurity, $_SESSION['AllowedPageSecurityTokens'])) continue;
                $sectionEntries[] = array(
                    'text' => '||' . trim($caption),
                    'url' => ltrim($url, '/')
                );
            }
            if (!empty($sectionEntries)) {
                $moduleEntries[] = array('text' => '|' . $sectionKey, 'url' => '');
                $moduleEntries = array_merge($moduleEntries, $sectionEntries);
            }
        }
        if (count($moduleEntries) > 1) {
            $builtItems = array_merge($builtItems, $moduleEntries);
        }
    }
    return $builtItems;
}

$homepageMenuItems = homepageBuildMenuItems(array($module), array($moduleLabel), $MenuItems);

if (empty($homepageMenuItems)) {
    $fallbackEntries = array(
        array('text' => trim(strip_tags($moduleLabel)), 'url' => '')
    );
    foreach (array('Transactions', 'Reports', 'Maintenance') as $sectionKey) {
        if (empty($MenuItems[$module][$sectionKey]['Caption']) || empty($MenuItems[$module][$sectionKey]['URL'])) continue;
        $fallbackEntries[] = array('text' => '|' . $sectionKey, 'url' => '');
        foreach ($MenuItems[$module][$sectionKey]['Caption'] as $index => $caption) {
            if (!isset($MenuItems[$module][$sectionKey]['URL'][$index])) continue;
            $fallbackEntries[] = array(
                'text' => '||' . trim($caption),
                'url' => ltrim($MenuItems[$module][$sectionKey]['URL'][$index], '/')
            );
        }
    }
    if (count($fallbackEntries) > 1) {
        $homepageMenuItems = $fallbackEntries;
    }
}

$homepageDmenuItems = array();
foreach ($homepageMenuItems as $item) {
    $dtext = $item['text'];
    $durl = $item['url'];
    $level = 0;
    if (preg_match('/^(\|*)/', $dtext, $m)) {
        $level = strlen($m[1]);
    }
    if ($level === 0) continue;
    $dtext = substr($dtext, 1);
    $homepageDmenuItems[] = array(
        $dtext, $durl,
        '', '', '', '', '', '', '', '', ''
    );
}

$dashBoardItems = array(
    array('Dash Board',   '', '', '', '', '', '', '', '', '', '', ''),
    array('|List of Assets',          'FixedAssetPrint.php', '', '', '', '', '', '', '', '', '', ''),
    array('|List of Suppliers',       'SelectSupplier.php?newsearch=yes', '', '', '', '', '', '', '', '', '', ''),
    array('|List of Customers',       'SelectCustomer.php?newsearch=yes', '', '', '', '', '', '', '', '', '', ''),
    array('|List of Products/Items',  'SelectProduct.php?newsearch=yes', '', '', '', '', '', '', '', '', '', ''),
);

$homepageDmenuItems = array_merge($homepageDmenuItems, $dashBoardItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart ERP - <?php echo htmlspecialchars($moduleLabel); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo $RootPath; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo $RootPath; ?>/css/bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo $RootPath; ?>/css/fontawesome6.4.0.all.min.css">
    <link rel="stylesheet" href="<?php echo $RootPath; ?>/css/homepageinline.css">
    <style>
        .app-shell {
            position: fixed;
            top: 56px;
            bottom: 38px;
            left: 0;
            right: 0;
            display: flex;
            flex-direction: column;
        }
        .app-menu-wrap {
            flex-shrink: 0;
            min-height: 42px;
            border-bottom: 1px solid #d1d5db;
            background: #f6f7fb;
            overflow: visible;
            position: relative;
            z-index: 950;
        }
        #homepageAppMenu {
            min-height: 42px;
            display: flex;
            align-items: center;
            overflow: visible;
        }
        #homepageAppMenu > div {
            margin: 0 !important;
        }
        .app-iframe-wrap {
            flex: 1;
            position: relative;
            padding: 14px;
            background: #f0f2f5;
        }
        .app-iframe-wrap iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .module-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        .module-heading a {
            color: #000;
            text-decoration: none;
        }
        .module-heading a:hover {
            text-decoration: underline;
        }
        .module-heading .sep {
            color: #94a3b8;
        }
        @media (max-width: 991.98px) {
            .banner { padding-left: 12px; padding-right: 12px; }
            .banner-meta { gap: 10px; font-size: 12px; overflow: hidden; }
        }
    </style>
</head>
<body>
    <header class="banner">
        <div class="banner-meta">
            <span class="module-heading">
                <a href="<?php echo $RootPath; ?>/homepage.php"><i class="fas fa-arrow-left"></i> Back to Modules</a>
                <span class="sep">|</span>
                <span><i class="fas fa-th-large"></i> <?php echo htmlspecialchars($moduleLabel); ?></span>
            </span>
        </div>
        <div class="banner-actions">
            <span id="bannerClock"><?php echo date('D, d M Y H:i'); ?></span>
            <a class="banner-link" href="<?php echo $RootPath; ?>/doc/Manual/SystemManual.html" target="_blank"><i class="fas fa-question-circle"></i> Help</a>
            <a class="banner-link" href="<?php echo $RootPath; ?>/Logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
    </header>
    <div class="app-shell">
        <div class="app-menu-wrap">
            <div id="homepageAppMenu">
                <script src="<?php echo $RootPath; ?>/javascripts/menu/dmenu.js"></script>
                <script src="<?php echo $RootPath; ?>/javascripts/menu/dmenu_key.js"></script>
                <script>
//--- Common
var itemStylesNames=[];
var menuStylesNames=[];
var isHorizontal=1;
var smColumns=1;
var smOrientation=0;
var dmRTL=0;
var pressedItem=-2;
var itemCursor="pointer";
var itemTarget="mainContentIFrame";
var statusString="link";
var blankImage="images/blank.gif";
var pathPrefix_img="javascripts/menu/";
var pathPrefix_link="";
//--- Dimensions
var menuWidth="250px";
var menuHeight="";
var smWidth="";
var smHeight="";
//--- Positioning
var absolutePos=0;
var posX="10px";
var posY="10px";
var topDX=0;
var topDY=0;
var DX=0;
var DY=0;
var subMenuAlign="left";
var subMenuVAlign="top";
//--- Font
var fontStyle=["normal 14px Tahoma","normal 14px Tahoma"];
var fontColor=["#000000","#FFFFFF"];
var fontDecoration=["none","none"];
var fontColorDisabled="#AAAAAA";
//--- Appearance
var menuBackColor="#FCFCFC";
var menuBackImage="";
var menuBackRepeat="repeat";
var menuBorderColor="#55A1FF";
var menuBorderWidth=0;
var menuBorderStyle="solid";
//--- Item Appearance
var itemBackColor=["transparent","#1665CB"];
var itemBackImage=["images/sm_back_xp.gif","images/sm_back_xp2.gif"];
var beforeItemImage=["",""];
var afterItemImage=["",""];
var beforeItemImageW="";
var afterItemImageW="";
var beforeItemImageH="";
var afterItemImageH="";
var itemBorderWidth=0;
var itemBorderColor=["#FCEEB0","#4C99AB"];
var itemBorderStyle=["solid","solid"];
var itemSpacing=0;
var itemPadding="3px 3px 3px 5px";
var itemAlignTop="left";
var itemAlign="left";
//--- Icons
var iconTopWidth=20;
var iconTopHeight=16;
var iconWidth=20;
var iconHeight=16;
var arrowWidth=7;
var arrowHeight=7;
var arrowImageMain=["images/arr_black_2.gif","images/arr_white_2.gif"];
var arrowWidthSub=0;
var arrowHeightSub=0;
var arrowImageSub=["images/arr_black_2.gif","images/arr_white_2.gif"];
//--- Separators
var separatorImage="images/sep_xp.gif";
var separatorWidth="90%";
var separatorHeight="3px";
var separatorAlignment="center";
var separatorVImage="";
var separatorVWidth="3px";
var separatorVHeight="100%";
var separatorPadding="5px";
//--- Floatable Menu
var floatable=0;
var floatIterations=6;
var floatableX=1;
var floatableY=1;
var floatableDX=15;
var floatableDY=15;
//--- Movable Menu
var movable=0;
var moveWidth=12;
var moveHeight=20;
var moveColor="#DECA9A";
var moveImage="";
var moveCursor="move";
var smMovable=0;
var closeBtnW=15;
var closeBtnH=15;
var closeBtn="";
//--- Transitional Effects & Filters
var transparency="100";
var transition=24;
var transOptions="";
var transDuration=350;
var transDuration2=200;
var shadowLen=4;
var shadowColor="#B1B1B1";
var shadowTop=1;
//--- CSS Support
var cssStyle=0;
var cssSubmenu="";
var cssItem=["",""];
var cssItemText=["",""];
//--- Advanced
var dmObjectsCheck=0;
var saveNavigationPath=1;
var showByClick=0;
var noWrap=1;
var smShowPause=200;
var smHidePause=1000;
var smSmartScroll=1;
var topSmartScroll=0;
var smHideOnClick=1;
var dm_writeAll=0;
var useIFRAME=0;
var dmSearch=0;
//--- AJAX-like
var dmAJAX=0;
var dmAJAXCount=0;
var ajaxReload=0;
//--- Dynamic Menu
var dynamic=0;
//--- Popup Menu
var popupMode=0;
//--- Keystrokes Support
var keystrokes=1;
var dm_focus=1;
var dm_actKey=113;
//--- Sound
var onOverSnd="";
var onClickSnd="";
var itemStyles = [];
var menuStyles = [];

var menuItems = <?php echo json_encode($homepageDmenuItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

dm_init();
                </script>
            </div>
        </div>
        <div class="app-iframe-wrap">
            <iframe id="mainContentIFrame" name="mainContentIFrame" src="<?php echo $RootPath; ?>/dashboard.php?module=<?php echo urlencode($module); ?>" title="Content"></iframe>
        </div>
    </div>
    <footer class="footer">
        <span id="currentDate"><?php echo date('l, j F Y'); ?></span>
        <span class="status" id="statusMessage">Ready</span>
        <span class="spacer"></span>
        <span><?php echo htmlspecialchars($companyName); ?></span>
    </footer>
    <script src="<?php echo $RootPath; ?>/javascripts/jquery-3.6.0.min.js"></script>
    <script src="<?php echo $RootPath; ?>/javascripts/bootstrap5.bundle.min.js"></script>
    <script>
        const rootPath = '<?php echo $RootPath; ?>';
        const defaultPage = 'dashboard.php';

        function mountModuleMenu() {
            var dock = document.getElementById('homepageAppMenu');
            if (!dock) return;
            var candidates = Array.from(document.querySelectorAll('div[id]'));
            var rootMenu = candidates.find(function(node) { return /^dm\d+m0$/.test(node.id); });
            if (!rootMenu) return;
            rootMenu.style.position = 'relative';
            rootMenu.style.top = '0px';
            rootMenu.style.left = '0px';
            rootMenu.style.margin = '0';
            rootMenu.style.zIndex = '960';
            if (!dock.contains(rootMenu)) dock.appendChild(rootMenu);
        }

        function normalizeMenuUrl(url) {
            if (!url) return '';
            var cleaned = String(url).trim();
            cleaned = cleaned.replace(/^https?:\/\/[^/]+/i, '');
            cleaned = cleaned.replace(/^\/+/, '');
            var rootPrefix = String(rootPath || '').replace(/^\.\/*/, '').replace(/^\/+|\/+$/g, '');
            if (rootPrefix && cleaned.toLowerCase().startsWith(rootPrefix.toLowerCase() + '/')) {
                cleaned = cleaned.substring(rootPrefix.length + 1);
            }
            return cleaned;
        }

        function tickClock() {
            var now = new Date();
            document.getElementById('bannerClock').textContent = now.toLocaleString();
            var dateEl = document.getElementById('currentDate');
            if (dateEl) dateEl.textContent = now.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        function updateBreadcrumb(pathParts) {
            var crumbs = Array.isArray(pathParts) && pathParts.length ? pathParts : ['<?php echo htmlspecialchars($moduleLabel); ?>'];
            var bc = document.getElementById('breadcrumbs');
            if (!bc) return;
            var html = '<li class="breadcrumb-item"><a href="<?php echo $RootPath; ?>/homepage.php"><i class="fas fa-home"></i> Home</a></li>';
            crumbs.forEach(function(part, index) {
                var safe = $('<div>').text(part).html();
                if (index === crumbs.length - 1) {
                    html += '<li class="breadcrumb-item active" aria-current="page">' + safe + '</li>';
                } else {
                    html += '<li class="breadcrumb-item">' + safe + '</li>';
                }
            });
            bc.innerHTML = html;
        }

        function plainMenuText(text) {
            return String(text || '').replace(/^\|+/, '').replace(/<[^>]*>/g, '').trim();
        }

        function getMenuLevel(text) {
            var match = String(text || '').match(/^\|+/);
            return match ? match[0].length : 0;
        }

        function buildBreadcrumbMap(items) {
            var map = {};
            var stack = [];
            items.forEach(function(item) {
                var text = item[0] || '';
                var url = item[1] || '';
                var level = getMenuLevel(text);
                var title = plainMenuText(text);
                stack.length = level;
                stack[level] = title;
                if (url) {
                    map[normalizeMenuUrl(url).toLowerCase()] = stack.slice(0, level + 1);
                }
            });
            return map;
        }

        var breadcrumbMap = buildBreadcrumbMap(<?php echo json_encode($homepageDmenuItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);

        function normalisePage(page) {
            var cleaned = normalizeMenuUrl(page);
            if (!cleaned) return '';
            var rootPrefix = rootPath && rootPath !== '.' ? rootPath.replace(/\/$/, '') + '/' : '';
            return rootPrefix + cleaned;
        }

        $(function() {
            tickClock();
            window.setInterval(tickClock, 1000);
            mountModuleMenu();

            $(document).on('click', 'a[target="mainContentIFrame"]', function() {
                var href = $(this).attr('href') || '';
                var key = normalizeMenuUrl(href).toLowerCase();
                var path = breadcrumbMap[key] || [plainMenuText($(this).text()) || '<?php echo htmlspecialchars($moduleLabel); ?>'];
                updateBreadcrumb(path);
                $('#statusMessage').text('Loading ' + path[path.length - 1] + '...');
            });

            $('#mainContentIFrame').on('load', function() {
                $('#statusMessage').text('Ready');
            });
        });
    </script>
</body>
</html>
