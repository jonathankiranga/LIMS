<?php
/**
 * Smart Suite launcher dashboard (ERP + LIMS + Payroll)
 * Place this file in the web root alongside smartERPlims/, blockchain/, webPayroll/.
 * Clicking a tile loads the selected app inside the embedded viewport without leaving the page.
 */

$apps = [
    [
        'key'     => 'erp',
        'name'    => 'Smart ERP',
        'tagline' => 'Finance | Inventory | CRM',
        'url'     => 'smartERPlims/index.php',
        'badge'   => 'Core',
        'meta'    => 'Latest activity, analytics, approvals',
    ],
    [
        'key'     => 'lims',
        'name'    => 'Smart LIMS',
        'tagline' => 'Labs | Compliance | QA',
        'url'     => 'blockchain/index.php',
        'badge'   => 'Quality',
        'meta'    => 'Sample flow, methods, certificates',
    ],
    [
        'key'     => 'payroll',
        'name'    => 'Smart Payroll',
        'tagline' => 'People | Pay | Leave',
        'url'     => 'webPayroll/index.php',
        'badge'   => 'People',
        'meta'    => 'Payroll runs, HR, statutory filings',
    ],
    [
        'key'     => 'crm',
        'name'    => 'SmartCRM',
        'tagline' => 'Kanban | Tasks | Activities',
        'url'     => 'smartcrm/index.php',
        'badge'   => 'Boards',
        'meta'    => 'Drag-and-drop pipeline for Tasks/Activities',
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Smart Suite Launcher</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        :root {
            --bg: #0b1221;
            --panel: #0f1b33;
            --glass: rgba(255,255,255,0.04);
            --border: rgba(255,255,255,0.08);
            --text: #e5e7eb;
            --muted: #9ca3af;
            --accent-blue: #38bdf8;
            --accent-amber: #fbbf24;
            --accent-green: #22c55e;
            --tile-radius: 16px;
            --shadow-soft: 0 10px 30px rgba(0,0,0,0.35);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, #102347 0%, transparent 30%),
                        radial-gradient(circle at 90% 10%, #12294d 0%, transparent 25%),
                        var(--bg);
            color: var(--text);
            font-family: "Poppins","Segoe UI","Helvetica Neue",sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .page {
            display: grid;
            grid-template-columns: 1.05fr 1.6fr;
            grid-template-rows: minmax(0, 1fr);
            gap: 22px;
            padding: 22px;
            height: 100vh;
            min-height: 100vh;
            align-items: stretch;
        }
        @media (max-width: 1180px) {
            .page { grid-template-columns: 1fr; height: auto; }
            .viewer { min-height: 70vh; }
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
            border-radius: 18px;
            box-shadow: var(--shadow-soft);
        }
        .title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .title h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.3px;
        }
        .title span {
            color: var(--muted);
            font-size: 13px;
        }
        .cta {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .pill {
            border: 1px solid var(--border);
            background: var(--glass);
            color: var(--text);
            padding: 8px 14px;
            border-radius: 40px;
            font-size: 13px;
            cursor: pointer;
            transition: 0.2s ease;
        }
        .pill:hover { border-color: rgba(255,255,255,0.18); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
        }
        .tile {
            position: relative;
            overflow: hidden;
            padding: 16px;
            border-radius: var(--tile-radius);
            border: 1px solid var(--border);
            background: var(--glass);
            box-shadow: var(--shadow-soft);
            cursor: pointer;
            transition: transform 0.18s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .tile:before {
            content: "";
            position: absolute;
            inset: -20% -10% auto auto;
            width: 140px;
            height: 140px;
            opacity: 0.75;
            filter: blur(12px);
            transform: rotate(8deg);
        }
        .tile-erp:before { background: linear-gradient(135deg,#1e3a8a,#2563eb,#38bdf8); }
        .tile-lims:before { background: linear-gradient(135deg,#0f766e,#10b981,#34d399); }
        .tile-payroll:before { background: linear-gradient(135deg,#be6b00,#f59e0b,#fbbf24); }
        .tile-crm:before { background: linear-gradient(135deg,#7c3aed,#a855f7,#ec4899); }
        .tile:hover { transform: translateY(-2px); border-color: rgba(255,255,255,0.2); }
        .tile.active { border-color: rgba(255,255,255,0.35); box-shadow: 0 15px 45px rgba(0,0,0,0.45); }
        .tile h3 { margin: 0 0 6px; font-size: 18px; }
        .tile .tagline { margin: 0 0 10px; color: var(--muted); font-size: 13px; }
        .meta { color: #d1d5db; font-size: 12px; display: inline-flex; gap: 6px; align-items: center; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            color: #0b1221;
            font-weight: 700;
            background: #f8fafc;
            mix-blend-mode: screen;
        }
        .glyph {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: #0b1221;
            font-weight: 700;
            background: rgba(255,255,255,0.86);
            margin-bottom: 10px;
        }
        .viewer {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: rgba(10,14,25,0.65);
            box-shadow: var(--shadow-soft);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
        }
        .viewer-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
        }
        .viewer-head .left {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            margin: 8px 0 0;
            font-size: 12px;
            color: var(--muted);
        }
        .crumb {
            padding: 5px 9px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.03);
        }
        .crumb.active {
            border-color: rgba(56,189,248,0.8);
            color: #e0f2fe;
        }
        .crumb-sep { color: var(--muted); }
        .viewer-head small { color: var(--muted); }
        .viewer-actions {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }
        .btn {
            background: var(--glass);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s ease;
        }
        .btn:hover { border-color: rgba(255,255,255,0.2); }
        iframe {
            border: none;
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            background: #0a0f1f;
        }
        .placeholder {
            display: grid;
            place-items: center;
            color: var(--muted);
            height: 220px;
            border-radius: 14px;
            border: 1px dashed var(--border);
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div>
            <header>
                <div class="title">
                    <h1>Smart Suite Dashboard</h1>
                    <span>Pick a workspace to launch it inside this window.</span>
                </div>
                <div class="cta">
                    <div class="pill" id="rememberToggle">Remember last opened</div>
                </div>
            </header>

            <div style="margin:18px 0 10px 2px; color:var(--muted); font-size:13px;">
                Modules
            </div>

            <div class="grid" id="appGrid">
                <?php foreach ($apps as $app): ?>
                    <article class="tile tile-<?php echo htmlspecialchars($app['key']); ?>" data-app="<?php echo htmlspecialchars($app['key']); ?>" data-url="<?php echo htmlspecialchars($app['url']); ?>" data-name="<?php echo htmlspecialchars($app['name']); ?>">
                        <div class="glyph"><?php echo strtoupper(substr($app['key'],0,2)); ?></div>
                        <h3><?php echo htmlspecialchars($app['name']); ?></h3>
                        <p class="tagline"><?php echo htmlspecialchars($app['tagline']); ?></p>
                        <span class="badge"><?php echo htmlspecialchars($app['badge']); ?></span>
                        <div class="meta" style="margin-top:10px;">
                            <span>*</span> <span><?php echo htmlspecialchars($app['meta']); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <section class="viewer">
            <div class="viewer-head">
                <div class="left">
                    <strong id="viewerTitle">No app loaded</strong>
                    <small id="viewerSub">Select a tile to open ERP, LIMS, or Payroll.</small>
                    <div class="breadcrumbs" id="breadcrumbs">
                        <span class="crumb">No path</span>
                    </div>
                </div>
                <div class="viewer-actions">
                    <button class="btn" id="openNewTab" disabled>Open in new tab</button>
                    <button class="btn" id="refreshFrame" disabled>Reload</button>
                </div>
            </div>
            <iframe id="appFrame" title="Application viewport"></iframe>
            <div class="placeholder" id="emptyState">Choose an application card to start.</div>
        </section>
    </div>

    <script>
        (function() {
            const grid = document.getElementById('appGrid');
            const frame = document.getElementById('appFrame');
            const viewerTitle = document.getElementById('viewerTitle');
            const viewerSub = document.getElementById('viewerSub');
            const emptyState = document.getElementById('emptyState');
            const openNewTab = document.getElementById('openNewTab');
            const refreshFrame = document.getElementById('refreshFrame');
            const rememberToggle = document.getElementById('rememberToggle');
            const breadcrumbs = document.getElementById('breadcrumbs');
            const rememberKey = 'smartsuite.lastApp';
            let currentAppName = null;

            function setActive(card) {
                document.querySelectorAll('.tile').forEach(el => el.classList.remove('active'));
                if (card) card.classList.add('active');
            }

            function renderBreadcrumbs(urlStr) {
                let items = [];
                try {
                    const u = new URL(urlStr, window.location.origin);
                    const segments = u.pathname.replace(/^\/+/,'').split('/').filter(Boolean);
                    const base = currentAppName ? currentAppName : 'App';
                    items.push({ label: base, href: u.origin + '/' + (segments[0] || '') });
                    let acc = '';
                    segments.forEach((seg, idx) => {
                        acc += (acc ? '/' : '') + seg;
                        items.push({ label: seg, href: u.origin + '/' + acc });
                    });
                } catch (e) {
                    items = [{ label: 'Navigation unavailable', href: null }];
                }

                breadcrumbs.innerHTML = '';
                if (!items.length) {
                    breadcrumbs.innerHTML = '<span class="crumb">No path</span>';
                    return;
                }
                items.forEach((item, idx) => {
                    const span = document.createElement('span');
                    span.className = 'crumb' + (idx === items.length - 1 ? ' active' : '');
                    span.textContent = item.label || '/';
                    breadcrumbs.appendChild(span);
                    if (idx !== items.length - 1) {
                        const sep = document.createElement('span');
                        sep.className = 'crumb-sep';
                        sep.textContent = '>';
                        breadcrumbs.appendChild(sep);
                    }
                });
            }

            function loadApp(card) {
                if (!card) return;
                const url = card.dataset.url;
                const name = card.dataset.name;
                frame.src = url;
                viewerTitle.textContent = name;
                viewerSub.textContent = url;
                currentAppName = name;
                emptyState.style.display = 'none';
                openNewTab.disabled = false;
                refreshFrame.disabled = false;
                openNewTab.onclick = () => window.open(url, '_blank');
                refreshFrame.onclick = () => frame.contentWindow ? frame.contentWindow.location.reload() : frame.src = url;
                setActive(card);
                if (rememberToggle.classList.contains('on')) {
                    localStorage.setItem(rememberKey, url);
                }
                renderBreadcrumbs(url);
            }

            frame.addEventListener('load', () => {
                try {
                    const href = frame.contentWindow.location.href;
                    renderBreadcrumbs(href);
                    viewerSub.textContent = href;
                } catch (e) {
                    breadcrumbs.innerHTML = '<span class=\"crumb\">Path hidden (cross-origin)</span>';
                }
            });

            grid.addEventListener('click', (e) => {
                const card = e.target.closest('.tile');
                if (card) {
                    e.preventDefault();
                    loadApp(card);
                }
            });

            rememberToggle.addEventListener('click', () => {
                rememberToggle.classList.toggle('on');
                const on = rememberToggle.classList.contains('on');
                rememberToggle.style.borderColor = on ? 'rgba(56,189,248,0.8)' : 'var(--border)';
                if (!on) localStorage.removeItem(rememberKey);
            });

            const saved = localStorage.getItem(rememberKey);
            if (saved) {
                rememberToggle.classList.add('on');
                const card = Array.from(document.querySelectorAll('.tile')).find(c => c.dataset.url === saved);
                if (card) loadApp(card);
            }
        })();
    </script>
</body>
</html>
