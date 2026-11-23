<?php
/**
 * Smart Suite launcher dashboard (ERP + LIMS + Payroll)
 * Place this file in the web root alongside smartERPlims/, blockchain/, webPayroll/.
 * Clicking an application tile opens that application in a new browser tab.
 */

$apps = [
    [
        'key'     => 'erp',
        'name'    => 'Smart ERP',
        'tagline' => 'Finance | Inventory | CRM',
        'url'     => 'https://finance.erplabworks.co.ke/',
        'badge'   => 'Core',
        'meta'    => 'Latest activity, analytics, approvals',
    ],
    [
        'key'     => 'lims',
        'name'    => 'Smart LIMS',
        'tagline' => 'Labs | Compliance | QA',
        'url'     => 'https://limsv3.erplabworks.co.ke/',
        'badge'   => 'Quality',
        'meta'    => 'Sample flow, methods, certificates',
    ],
    [
        'key'     => 'payroll',
        'name'    => 'Smart Payroll',
        'tagline' => 'People | Pay | Leave',
        'url'     => 'https://payroll.erplabworks.co.ke/',
        'badge'   => 'People',
        'meta'    => 'Payroll runs, HR, statutory filings',
    ],
    [
        'key'     => 'crm',
        'name'    => 'SmartCRM',
        'tagline' => 'Kanban | Tasks | Activities',
        'url'     => 'https://crm.erplabworks.co.ke/',
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
            grid-template-columns: 1fr;
            gap: 22px;
            padding: 22px;
            min-height: 100vh;
            align-items: stretch;
        }
        @media (max-width: 1180px) {
            .page { grid-template-columns: 1fr; }
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
        .site-footer {
            grid-column: 1 / -1;
            text-align: center;
            padding: 18px 12px;
            margin-top: 24px;
            color: var(--muted);
            font-size: 12px;
            border-top: 1px solid var(--border);
        }
        .site-footer a {
            color: var(--accent-blue);
            text-decoration: none;
        }
        .site-footer a:hover { text-decoration: underline; }
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
                    <span>Select an application to open it in a new browser tab.</span>
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

        <footer class="site-footer">
            <p>© <?php echo date('Y'); ?> Smart Suite. Developed by
                <a href="https://smarternowhosting.co.ke/" target="_blank" rel="noopener noreferrer">SmarterNow Data Venture</a>.
            </p>
        </footer>
    </div>

    <script>
        (function() {
            const grid = document.getElementById('appGrid');

            grid.addEventListener('click', function(event) {
                const card = event.target.closest('.tile');
                if (!card) return;

                const url = card.dataset.url;
                if (url) {
                    window.open(url, '_blank', 'noopener,noreferrer');
                }
            });
        })();
    </script>
</body>
</html>
