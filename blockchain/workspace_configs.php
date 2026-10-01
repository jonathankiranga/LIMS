<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Workspace Configurations</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.ws-app { font-family: 'Segoe UI', Arial, sans-serif; padding: 12px; background: #f5f2f8; min-height: 100vh; box-sizing: border-box; }
.ws-app *, .ws-app *::before, .ws-app *::after { box-sizing: border-box; }
.ws-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.ws-header h1 { margin: 0; color: #2f2b3a; font-size: 20px; font-weight: 700; }
.ws-tabs { display: flex; gap: 8px; margin-bottom: 15px; background: #fff; padding: 10px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.ws-tab { padding: 6px 12px; border: none; background: transparent; color: #6f6c7a; font-size: 13px; font-weight: 500; cursor: pointer; border-radius: 6px; transition: all 0.2s; }
.ws-tab:hover { background: #f5f2f8; }
.ws-tab.active { background: #2563eb; color: #fff; }
.ws-btn { padding: 6px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.ws-btn-primary { background: #2563eb; color: #fff; }
.ws-btn-primary:hover { background: #1d4ed8; }
.ws-btn-secondary { background: #f5f2f8; color: #2f2b3a; }
.ws-btn-secondary:hover { background: #e1dfe8; }
.ws-header-actions { display: flex; gap: 8px; }
</style>
</head>
<body>
<div class="ws-app">
    <div class="ws-header">
        <h1><i class="fas fa-cogs"></i> Workspace Configurations</h1>
        <div class="ws-header-actions">
            <a href="workspace.php" class="ws-btn ws-btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Workspaces
            </a>
        </div>
    </div>

    <div class="ws-tabs">
        <button class="ws-tab active" data-action="switch-tab" data-tab="email">
            <i class="fas fa-envelope"></i> Email & Reminders
        </button>
    </div>

    <div id="tab-email" class="ws-tab-content">
        <iframe src="email_rules.php" style="width:100%; height:800px; border:none; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);"></iframe>
    </div>

<script>
(function() {
    function setActiveTab(tab, btn) {
        document.querySelectorAll('.ws-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.ws-tab-content').forEach(c => c.style.display = 'none');
        const tabContent = document.getElementById('tab-' + tab);
        if (tabContent) tabContent.style.display = 'block';
        if (btn) btn.classList.add('active');
    }

    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-action]');
        if (!target) return;
        
        if (target.dataset.action === 'switch-tab') {
            setActiveTab(target.dataset.tab, target);
        }
    });
})();
</script>
</div>
</body>
</html>
