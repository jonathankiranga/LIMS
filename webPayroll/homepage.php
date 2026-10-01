<?php
$PageSecurity = 0;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');
include_once('includes/MainMenuLinksArray.php');

$RootPath = isset($RootPath) ? $RootPath : '.';
$userName = isset($_SESSION['UsersRealName']) ? $_SESSION['UsersRealName'] : 'User';
$companyName = isset($_SESSION['CompanyRecord']['coyname']) ? $_SESSION['CompanyRecord']['coyname'] : 'SmartPayroll';
$versionNumber = isset($_SESSION['VersionNumber']) ? $_SESSION['VersionNumber'] : '';
$defaultPage = 'dashboard.php';

function homepageModuleIcon($moduleKey) {
    $icons = array(
        'HM' => 'fa-users',
        'HR' => 'fa-money-check-dollar',
        'system' => 'fa-gear'
    );
    return isset($icons[$moduleKey]) ? $icons[$moduleKey] : 'fa-folder-open';
}

function homepageSectionIcon($sectionKey) {
    $icons = array(
        'Transactions'    => 'fa-right-left',
        'Reports'         => 'fa-chart-column',
        'Maintenance'     => 'fa-screwdriver-wrench',
        'Leave Management'=> 'fa-calendar-check'
    );
    return isset($icons[$sectionKey]) ? $icons[$sectionKey] : 'fa-folder';
}

function homepageBuildMenuItems($moduleLinks, $moduleNames, $menuItems) {
    $builtItems = array();
    $sectionLabels = array(
        'Transactions'    => _('Transactions'),
        'Reports'         => _('Reports & Inquiries'),
        'Maintenance'     => _('Setup'),
        'Leave Management'=> _('Leave Management')
    );

    for ($i = 0; $i < count($moduleLinks); $i++) {
        if (!isset($_SESSION['ModulesEnabled'][$i]) || $_SESSION['ModulesEnabled'][$i] != 1) {
            continue;
        }

        $moduleKey = $moduleLinks[$i];

        if (!isset($menuItems[$moduleKey]) || !is_array($menuItems[$moduleKey])) {
            continue;
        }

        $moduleEntries = array();

        foreach (array('Transactions', 'Reports', 'Maintenance', 'Leave Management') as $sectionKey) {
            if (empty($menuItems[$moduleKey][$sectionKey]['Caption']) || empty($menuItems[$moduleKey][$sectionKey]['URL'])) {
                continue;
            }

            $sectionEntries = array();
            $captions = $menuItems[$moduleKey][$sectionKey]['Caption'];
            $urls     = $menuItems[$moduleKey][$sectionKey]['URL'];

            foreach ($captions as $index => $caption) {
                if (!isset($urls[$index])) {
                    continue;
                }

                $url             = $urls[$index];
                $scriptNameArray = explode('?', ltrim($url, '/'));
                $scriptName      = $scriptNameArray[0];
                $pageSecurity    = isset($_SESSION['PageSecurityArray'][$scriptName]) ? $_SESSION['PageSecurityArray'][$scriptName] : null;

                if ($pageSecurity !== null && !in_array($pageSecurity, $_SESSION['AllowedPageSecurityTokens'])) {
                    continue;
                }

                $sectionEntries[] = array(
                    'text' => '||' . trim(strip_tags($caption)),
                    'url'  => ltrim($url, '/'),
                    'icon' => 'fa-file-lines'
                );
            }

            if (!empty($sectionEntries)) {
                $moduleEntries[] = array(
                    'text' => '|' . ($sectionLabels[$sectionKey] ?? $sectionKey),
                    'url'  => '',
                    'icon' => homepageSectionIcon($sectionKey)
                );
                $moduleEntries = array_merge($moduleEntries, $sectionEntries);
            }
        }

        if (!empty($moduleEntries)) {
            array_unshift($moduleEntries, array(
                'text' => $moduleNames[$i],
                'url'  => '',
                'icon' => homepageModuleIcon($moduleKey)
            ));
            $builtItems = array_merge($builtItems, $moduleEntries);
        }
    }

    return $builtItems;
}

$homepageMenuItems = homepageBuildMenuItems($ModuleLink, $ModuleList, $MenuItems);

// Convert to indexed arrays matching the dmenu row format [text, url, icon, ...]
$homepageDmenuItems = array();
foreach ($homepageMenuItems as $item) {
    $homepageDmenuItems[] = array(
        $item['text'],
        isset($item['url'])  ? $item['url']  : '',
        isset($item['icon']) ? $item['icon'] : '',
        '', '', '', '', '', '', '', ''
    );
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Payroll - Homepage</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/css/bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/css/fontawesome6.4.0.all.min.css">
    <style>
        :root {
            --banner-height: 56px;
            --footer-height: 38px;
            --menu-height: 42px;
            --sidebar-width: 288px;
            --sidebar-bg: #ffffff;
            --sidebar-border: #d1d5db;
            --sidebar-text: #000000;
            --sidebar-muted: #374151;
            --sidebar-hover: rgba(0, 0, 0, 0.05);
            --sidebar-active: #e5e7eb;
            --shell-bg: #ffffff;
            --panel-bg: #ffffff;
            --panel-border: #d1d5db;
            --banner-bg: #ffffff;
            --footer-bg: #ffffff;
            --accent: #0f766e;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: var(--shell-bg);
            color: #0f172a;
        }

        a { text-decoration: none; }

        /* ── Banner ── */
        .banner {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--banner-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 16px;
            background: var(--banner-bg);
            color: #0f172a;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.18);
            border-bottom: 1px solid var(--panel-border);
        }

        .banner-meta {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
            font-size: 14px;
            white-space: nowrap;
        }

        .banner-meta strong { color: #0f172a; }

        .banner-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .banner-link,
        .banner-link:visited {
            color: #000000;
            font-weight: 600;
        }

        /* ── Shell: fills between banner and footer ── */
        .shell {
            position: fixed;
            inset: var(--banner-height) 0 var(--footer-height) 0;
            display: flex;
            flex-direction: column;
        }

        /* ── Top dock (legacy dmenu) ── */
        .top-dock-menu {
            height: var(--menu-height);
            min-height: var(--menu-height);
            border-bottom: 1px solid var(--panel-border);
            background: #f6f7fb;
            overflow: visible;
            position: relative;
            z-index: 950;
            flex-shrink: 0;
        }

        #topDockMenu {
            height: var(--menu-height);
            display: flex;
            align-items: center;
            overflow: visible;
        }

        #topDockMenu > div {
            margin: 0 !important;
        }

        /* ── Workspace: below the top dock ── */
        .workspace {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            color: var(--sidebar-text);
            overflow: hidden;
            z-index: 900;
        }

        .sidebar-inner {
            height: 100%;
            overflow-y: auto;
            padding: 12px 10px 16px;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px 14px;
            color: #000000;
            font-weight: 700;
            letter-spacing: 0.2px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            margin-bottom: 12px;
        }

        .sidebar-header small {
            display: block;
            color: var(--sidebar-muted);
            font-weight: 500;
            letter-spacing: 0;
        }

        /* ── Tree menu ── */
        .tree-menu,
        .tree-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .tree-menu > li { margin-bottom: 4px; }

        .tree-link,
        .tree-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: var(--sidebar-text);
            text-align: left;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.18s ease, color 0.18s ease;
        }

        .tree-link:hover,
        .tree-toggle:hover {
            background: var(--sidebar-hover);
            color: #000000;
        }

        .tree-link.active {
            background: var(--sidebar-active);
            color: #0f172a;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.06);
        }

        .tree-toggle .tree-caret {
            margin-left: auto;
            transition: transform 0.18s ease;
            color: var(--sidebar-muted);
        }

        .tree-toggle.expanded .tree-caret {
            transform: rotate(90deg);
            color: #000000;
        }

        .tree-menu .submenu {
            display: none;
            margin-left: 14px;
            padding-left: 10px;
            border-left: 1px solid rgba(148, 163, 184, 0.25);
        }

        .tree-menu .submenu.open { display: block; }

        .tree-menu .submenu .tree-link,
        .tree-menu .submenu .tree-toggle {
            font-size: 13px;
            color: #334155;
        }

        .tree-icon {
            width: 16px;
            text-align: center;
            color: #000000;
            flex-shrink: 0;
        }

        /* ── Main content ── */
        .main {
            position: absolute;
            top: 0; right: 0; bottom: 0;
            left: var(--sidebar-width);
            overflow: hidden;
        }

        .breadcrumb-bar {
            height: 42px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 16px;
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid var(--panel-border);
            backdrop-filter: blur(4px);
        }

        .breadcrumb {
            margin: 0;
            padding: 0;
            background: transparent;
            font-size: 13px;
        }

        .breadcrumb-item + .breadcrumb-item::before { color: #64748b; }
        .breadcrumb-item a { color: #000000; }

        .content-shell {
            position: absolute;
            inset: 42px 14px 14px 14px;
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            background: var(--panel-bg);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .content-frame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #fff;
        }

        /* ── Footer ── */
        .footer {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            height: var(--footer-height);
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 14px;
            background: var(--footer-bg);
            color: #1e293b;
            font-size: 12px;
            z-index: 1000;
            border-top: 1px solid var(--panel-border);
        }

        .footer .spacer { margin-left: auto; }

        .footer .status {
            color: #334155;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── Mobile ── */
        .mobile-toggle {
            display: none;
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.08);
            color: #0f172a;
        }

        .sidebar-backdrop { display: none; }

        @media (max-width: 991.98px) {
            .mobile-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .banner { padding-left: 12px; padding-right: 12px; }

            .banner-meta {
                gap: 10px;
                font-size: 12px;
                overflow: hidden;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.2s ease;
                box-shadow: 8px 0 24px rgba(15, 23, 42, 0.24);
            }

            .sidebar.open { transform: translateX(0); }

            .sidebar-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.35);
                z-index: 850;
            }

            .sidebar-backdrop.show { display: block; }

            .main { left: 0; }
        }
    </style>
</head>
<body>
    <header class="banner">
        <div class="d-flex align-items-center gap-2">
            <button class="mobile-toggle" id="sidebarToggle" type="button" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="banner-meta">
                <span><strong>User:</strong> <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                <span><strong>Company:</strong> <?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ($versionNumber !== '') : ?>
                    <span><strong>Version:</strong> <?php echo htmlspecialchars($versionNumber, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="banner-actions">
            <span id="bannerClock"><?php echo date('D, d M Y H:i'); ?></span>
            <a class="banner-link" href="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/Logout.php">
                <i class="fas fa-sign-out-alt"></i> Log out
            </a>
        </div>
    </header>

    <div class="shell">
        <!-- Legacy dmenu renders here natively from data-clear-2.js -->
        <div class="top-dock-menu">
            <div id="topDockMenu">
                <script src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/javascripts/menu/dmenu.js"></script>
                <script src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/javascripts/menu/dmenu_key.js"></script>
                <script src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/javascripts/menu/data-clear-2.js"></script>
            </div>
        </div>

        <div class="workspace">
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

            <aside class="sidebar" id="sidebar">
                <div class="sidebar-inner">
                    <div class="sidebar-header">
                        <i class="fas fa-coins"></i>
                        <div>
                            Smart Payroll
                            <small>Docked navigation</small>
                        </div>
                    </div>
                    <ul class="tree-menu" id="treeMenu"></ul>
                </div>
            </aside>

            <main class="main">
                <div class="breadcrumb-bar">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb" id="breadcrumbs">
                            <li class="breadcrumb-item">
                                <a href="#" id="breadcrumbHome"><i class="fas fa-home"></i> Home</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>
                </div>

                <div class="content-shell">
                    <iframe
                        id="mainContentIFrame"
                        name="mainContentIFrame"
                        class="content-frame"
                        src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars($defaultPage, ENT_QUOTES, 'UTF-8'); ?>"
                        title="Main content"></iframe>
                </div>
            </main>
        </div>
    </div>

    <footer class="footer">
        <span id="currentDate"><?php echo date('l, j F Y'); ?></span>
        <span class="status" id="statusMessage">Ready</span>
        <span class="spacer"></span>
        <span><?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?></span>
    </footer>

    <script src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/javascripts/jquery-3.6.0.min.js"></script>
    <script src="<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>/javascripts/bootstrap5.bundle.min.js"></script>
    <script>
        const rootPath    = '<?php echo htmlspecialchars($RootPath, ENT_QUOTES, 'UTF-8'); ?>';
        const defaultPage = '<?php echo htmlspecialchars($defaultPage, ENT_QUOTES, 'UTF-8'); ?>';

        // PHP-built, permission-filtered sidebar items [text, url, icon, ...]
        const rawDmenuItems = <?php echo json_encode($homepageDmenuItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

        /* ── URL helpers (Doc 2 logic) ── */
        function normalizeMenuUrl(url) {
            if (!url) return '';
            let cleaned = String(url).trim();
            cleaned = cleaned.replace(/^https?:\/\/[^/]+/i, '');
            cleaned = cleaned.replace(/^\/+/, '');
            const rootPrefix = String(rootPath || '').replace(/^\.\/*/, '').replace(/^\/+|\/+$/g, '');
            if (rootPrefix && cleaned.toLowerCase().startsWith(rootPrefix.toLowerCase() + '/')) {
                cleaned = cleaned.substring(rootPrefix.length + 1);
            }
            return cleaned;
        }

        function normalisePage(page) {
            const cleaned = normalizeMenuUrl(page);
            if (!cleaned) return '';
            const rootPrefix = rootPath && rootPath !== '.' ? rootPath.replace(/\/$/, '') + '/' : '';
            return rootPrefix + cleaned;
        }

        /* ── Text / level helpers ── */
        function plainMenuText(text) {
            return String(text || '').replace(/^\|+/, '').replace(/<[^>]*>/g, '').trim();
        }

        function getMenuLevel(text) {
            const match = String(text || '').match(/^\|+/);
            return match ? match[0].length : 0;
        }

        function hasChild(items, index, level) {
            if (index + 1 >= items.length) return false;
            return getMenuLevel(items[index + 1][0] || '') > level;
        }

        /* ── Icon helper ── */
        function resolveIconClass(iconValue, childBranch) {
            const cleaned = String(iconValue || '').replace(/^images\//, '').replace(/\.(gif|png|jpg|jpeg)$/i, '');
            if (cleaned.startsWith('fa-')) return cleaned;
            return childBranch ? 'fa-folder-open' : 'fa-file-lines';
        }

        /* ── Breadcrumb map (Doc 2 logic) ── */
        function buildBreadcrumbMap(items) {
            const map   = {};
            const stack = [];
            items.forEach((item) => {
                const text  = item[0] || '';
                const url   = item[1] || '';
                const level = getMenuLevel(text);
                const title = plainMenuText(text);
                stack.length  = level;
                stack[level]  = title;
                if (url) {
                    map[normalizeMenuUrl(url).toLowerCase()] = stack.slice(0, level + 1);
                }
            });
            return map;
        }

        /* ── Build sidebar tree from rawDmenuItems ── */
        function buildTreeMenu(items) {
            const root = document.getElementById('treeMenu');
            if (!root) return;
            root.innerHTML = '';

            if (!Array.isArray(items) || !items.length) {
                root.innerHTML = '<li><span class="tree-link">No menu items available.</span></li>';
                return;
            }

            const listStack = [root];
            const pathStack = [];

            items.forEach((item, index) => {
                const rawText   = item[0] || '';
                const page      = item[1] || '';
                const iconValue = item[2] || '';
                const level     = getMenuLevel(rawText);
                const title     = plainMenuText(rawText);
                const childBranch = hasChild(items, index, level);
                const iconClass = resolveIconClass(iconValue, childBranch);

                while (listStack.length > level + 1) listStack.pop();

                pathStack.length = level;
                pathStack[level] = title;

                const li        = document.createElement('li');
                const container = listStack[listStack.length - 1];
                const iconNode  = '<span class="tree-icon"><i class="fas ' + iconClass + '"></i></span>';

                if (childBranch) {
                    const button  = document.createElement('button');
                    button.type   = 'button';
                    button.className = 'tree-toggle';
                    button.innerHTML = iconNode + '<span>' + $('<div>').text(title).html() + '</span>'
                                     + '<i class="fas fa-chevron-right tree-caret"></i>';
                    li.appendChild(button);

                    const subMenu = document.createElement('ul');
                    subMenu.className = 'submenu';
                    li.appendChild(subMenu);
                    listStack.push(subMenu);
                } else {
                    const link    = document.createElement('a');
                    const pageUrl = normalisePage(page);
                    link.href     = pageUrl || '#';
                    link.target   = 'mainContentIFrame';
                    link.className  = 'tree-link';
                    link.dataset.page = pageUrl;
                    link.dataset.path = JSON.stringify(pathStack.slice(0, level + 1));
                    link.innerHTML  = iconNode + '<span>' + $('<div>').text(title).html() + '</span>';
                    li.appendChild(link);
                }

                container.appendChild(li);
            });
        }

        /* ── Mount legacy dmenu into top dock ── */
        function mountLegacyDmenu() {
            const dock = document.getElementById('topDockMenu');
            if (!dock) return;
            const rootMenu = Array.from(document.querySelectorAll('div[id]'))
                                  .find((node) => /^dm\d+m0$/.test(node.id));
            if (!rootMenu) {
                $('#statusMessage').text('dmenu not mounted');
                return;
            }
            rootMenu.style.position = 'relative';
            rootMenu.style.top      = '0px';
            rootMenu.style.left     = '0px';
            rootMenu.style.margin   = '0';
            rootMenu.style.zIndex   = '960';
            if (!dock.contains(rootMenu)) dock.appendChild(rootMenu);
        }

        /* ── Breadcrumb ── */
        function updateBreadcrumb(pathParts) {
            const crumbs     = Array.isArray(pathParts) && pathParts.length ? pathParts : ['Dashboard'];
            const breadcrumb = document.getElementById('breadcrumbs');
            const items      = [
                '<li class="breadcrumb-item"><a href="#" id="breadcrumbHomeLink"><i class="fas fa-home"></i> Home</a></li>'
            ];
            crumbs.forEach((part, index) => {
                const safeText = $('<div>').text(part).html();
                if (index === crumbs.length - 1) {
                    items.push('<li class="breadcrumb-item active" aria-current="page">' + safeText + '</li>');
                } else {
                    items.push('<li class="breadcrumb-item">' + safeText + '</li>');
                }
            });
            breadcrumb.innerHTML = items.join('');
        }

        /* ── Clock ── */
        function tickClock() {
            const now = new Date();
            $('#bannerClock').text(now.toLocaleString());
            $('#currentDate').text(now.toLocaleDateString(undefined, {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            }));
        }

        /* ── Sidebar open / close ── */
        function closeSidebar() {
            $('#sidebar').removeClass('open');
            $('#sidebarBackdrop').removeClass('show');
        }

        function openSidebar() {
            $('#sidebar').addClass('open');
            $('#sidebarBackdrop').addClass('show');
        }

        /* ── Init ── */
        const breadcrumbMap = buildBreadcrumbMap(rawDmenuItems);

        $(function () {
            tickClock();
            window.setInterval(tickClock, 1000);

            buildTreeMenu(rawDmenuItems);
            mountLegacyDmenu();

            /* Sidebar tree toggles */
            $(document).on('click', '.tree-toggle', function () {
                $(this).toggleClass('expanded').next('.submenu').toggleClass('open');
            });

            /* Sidebar tree links */
            $(document).on('click', '.tree-link', function (event) {
                const pageUrl = $(this).data('page');
                if (!pageUrl) { event.preventDefault(); return false; }
                event.preventDefault();
                $('.tree-link').removeClass('active');
                $(this).addClass('active');
                document.getElementById('mainContentIFrame').src = pageUrl;
                const pathParts = JSON.parse($(this).attr('data-path') || '["Dashboard"]');
                updateBreadcrumb(pathParts);
                $('#statusMessage').text('Loading ' + pathParts[pathParts.length - 1] + '...');
                closeSidebar();
                return false;
            });

            /* dmenu top-bar links (target mainContentIFrame) */
            $(document).on('click', 'a[target="mainContentIFrame"]', function () {
                const href = $(this).attr('href') || '';
                const key  = normalizeMenuUrl(href).toLowerCase();
                const path = breadcrumbMap[key] || [plainMenuText($(this).text()) || 'Dashboard'];
                updateBreadcrumb(path);
                $('#statusMessage').text('Loading ' + path[path.length - 1] + '...');
            });

            /* Home breadcrumb */
            $(document).on('click', '#breadcrumbHome, #breadcrumbHomeLink', function (event) {
                event.preventDefault();
                document.getElementById('mainContentIFrame').src = normalisePage(defaultPage);
                updateBreadcrumb(['Dashboard']);
            });

            /* Mobile toggle */
            $('#sidebarToggle').on('click', function () {
                if ($('#sidebar').hasClass('open')) closeSidebar(); else openSidebar();
            });

            $('#sidebarBackdrop').on('click', closeSidebar);

            $('#mainContentIFrame').on('load', function () {
                $('#statusMessage').text('Ready');
            });

            $(window).on('resize', function () {
                if (window.innerWidth > 991) closeSidebar();
            });
        });
    </script>
</body>
</html>