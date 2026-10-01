<?php
// Dashboard with iframe navigation
$PageSecurity = 0;
$PathPrefix = './';
$AllowAnyone = false;
error_reporting(E_ALL);
include($PathPrefix . 'includes/session.inc');
include($PathPrefix . 'includes/SQL_CommonFunctions.inc');

$Title = _('Smarternow CRM');
include($PathPrefix . 'includes/header.inc');

$CurrencySymbol = isset($CurrencySymbol) ? $CurrencySymbol : 'KSh ';
?>
<style>
html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; width: 100vw; max-width: 100%; font-family: 'Segoe UI', sans-serif; }
* { box-sizing: border-box; }

#smartcrm-app { 
    display: flex; 
    flex-direction: column; 
    height: 100vh; 
    width: 100vw; 
    max-width: 100%; 
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
}

#smartcrm-header {
    background: linear-gradient(90deg, #e94560 0%, #ff6b6b 100%);
    color: white;
    padding: 0 25px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 20px rgba(233,69,96,0.4);
}

#smartcrm-header .brand { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
#smartcrm-header .brand i { font-size: 28px; }
#smartcrm-header .user-info { display: flex; align-items: center; gap: 20px; }
#smartcrm-header .user-info span { font-weight: 500; }
#smartcrm-header .user-info a { color: white; text-decoration: none; font-size: 14px; opacity: 0.9; transition: opacity 0.3s; }
#smartcrm-header .user-info a:hover { opacity: 1; }

#smartcrm-nav {
    background: linear-gradient(90deg, #16213e 0%, #1a1a2e 100%);
    padding: 0 25px;
    height: 50px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

#smartcrm-nav a {
    color: #a0a0a0;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

#smartcrm-nav a:hover { 
    background: rgba(233,69,96,0.2); 
    color: #ff6b6b; 
}

#smartcrm-nav a.active { 
    background: linear-gradient(90deg, #e94560, #ff6b6b); 
    color: white; 
    box-shadow: 0 4px 15px rgba(233,69,96,0.4);
}

#smartcrm-breadcrumb {
    background: rgba(255,255,255,0.05);
    padding: 12px 25px;
    font-size: 13px;
    color: #a0a0a0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

#smartcrm-breadcrumb a { color: #ff6b6b; text-decoration: none; }
#smartcrm-breadcrumb a:hover { text-decoration: underline; }
#smartcrm-breadcrumb span { color: white; font-weight: 500; }

#smartcrm-main {
    flex: 1;
    overflow: hidden;
    position: relative;
    width: 100vw;
    max-width: 100%;
    background: #0f0f1a;
}

#contentFrame {
    width: 100vw;
    height: 100%;
    border: none;
    background: transparent;
    display: block;
}
</style>

<div id="smartcrm-app">
    <div id="smartcrm-header">
        <div class="brand">
            <span>🚀 SmartCRM</span>
        </div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['UsersRealName'] ?? $_SESSION['UserID'] ?? 'Guest'); ?></span>
            <a href="Logout.php">Logout</a>
        </div>
    </div>
    
    <div id="smartcrm-nav">
        <a href="dashboard_content.php" data-page="dashboard" class="active" target="contentFrame">Dashboard</a>
        <a href="Leads.php" data-page="leads" target="contentFrame">Leads</a>
        <a href="Opportunities.php" data-page="opportunities" target="contentFrame">Pipeline</a>
        <a href="Communications.php" data-page="communications" target="contentFrame">Communications</a>
        <a href="Automation.php" data-page="automation" target="contentFrame">Automation</a>
    </div>
    
    <div id="smartcrm-breadcrumb">
        <a href="dashboard.php">Home</a> &raquo; <span id="crumb-current">Dashboard</span>
    </div>
    
    <div id="smartcrm-main">
        <iframe id="contentFrame" name="contentFrame" src="dashboard_content.php"></iframe>
    </div>
</div>

<script>
var CRM_CURRENCY_SYMBOL = '<?php echo $CurrencySymbol; ?>';

function updateBreadcrumb(page, title) {
    var crumbCurrent = document.getElementById('crumb-current');
    if (crumbCurrent) {
        crumbCurrent.textContent = title || page;
    }
}

function setActiveNav(link) {
    document.querySelectorAll('#smartcrm-nav a').forEach(function(a) {
        a.classList.remove('active');
    });
    link.classList.add('active');
}

// Handle nav clicks
document.querySelectorAll('#smartcrm-nav a').forEach(function(link) {
    link.addEventListener('click', function(e) {
        var page = this.getAttribute('data-page');
        var title = this.textContent;
        
        setActiveNav(this);
        updateBreadcrumb(page, title);
    });
});

// Handle iframe load to update nav state
document.getElementById('contentFrame').addEventListener('load', function() {
    try {
        var path = this.contentWindow.location.pathname;
        var pageName = path.split('/').pop().replace('.php', '');
        
        document.querySelectorAll('#smartcrm-nav a').forEach(function(link) {
            var href = link.getAttribute('href');
            if (href && href.indexOf(pageName) !== -1) {
                setActiveNav(link);
                updateBreadcrumb(link.getAttribute('data-page'), link.textContent);
            }
        });
    } catch(e) {
        // Cross-origin or other error - ignore
    }
});

// Adjust iframe on resize
window.addEventListener('resize', function() {
    // Iframe automatically fills remaining space via flex
});
</script>

<?php include($PathPrefix . 'includes/footer.inc'); ?>
