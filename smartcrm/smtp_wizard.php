<?php
/**
 * SMTP Configuration Wizard
 * Web-based wizard to configure SMTP settings
 * Access: Direct URL only (no menu link)
 */
$PageSecurity = 0;
$PathPrefix = './';
$inIframe = true;
$extraCrumb = 'SMTP Settings';
error_reporting(E_ALL);
include($PathPrefix . 'includes/session.inc');

$Title = _('SMTP Settings');
include($PathPrefix . 'includes/header.inc');

unset($extraCrumb);
?>
<link rel="stylesheet" href="kanban.css">
<style>
.crm-container { padding: 20px; max-width: 700px; margin: 0 auto; }
.crm-header { margin-bottom: 20px; }
.crm-header h2 { margin: 0; color: #2c3e50; }
.wizard-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 600; color: #2f2b3a; margin-bottom: 6px; }
.form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 2px solid #e1dfe8; border-radius: 8px; font-size: 14px; }
.form-group input:focus, .form-group select:focus { border-color: #6b4fd6; outline: none; }
.form-group .hint { font-size: 12px; color: #6f6c7a; margin-top: 4px; }
.btn-success { background: #27ae60; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; }
.btn-success:hover { background: #219a52; }
.btn-secondary { background: #e1dfe8; color: #6f6c7a; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; margin-right: 10px; }
.test-result { padding: 12px; border-radius: 8px; margin-top: 15px; display: none; }
.test-result.success { background: #d4edda; color: #155724; display: block; }
.test-result.error { background: #f8d7da; color: #721c24; display: block; }
.current-config { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-family: monospace; font-size: 13px; }
</style>

<div class="crm-container">
    <div class="crm-header">
        <h2>Email (SMTP) Configuration</h2>
        <p style="color: #6f6c7a; margin-top: 5px;">Configure SMTP settings for sending emails and calendar invites</p>
    </div>
    
    <div class="wizard-card">
        <div id="currentConfig" class="current-config"></div>
        
        <form id="smtpForm">
            <div class="form-group">
                <label>SMTP Host *</label>
                <input type="text" id="smtp_host" required placeholder="smtp.gmail.com">
                <div class="hint">SMTP server address (e.g., smtp.gmail.com, smtp.office365.com)</div>
            </div>
            
            <div class="form-group">
                <label>SMTP Port *</label>
                <select id="smtp_port">
                    <option value="587">587 (TLS)</option>
                    <option value="465">465 (SSL)</option>
                    <option value="25">25 (None)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Security</label>
                <select id="smtp_secure">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="">None</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Username *</label>
                <input type="text" id="smtp_username" required placeholder="your@email.com">
                <div class="hint">For Gmail, use your full email address</div>
            </div>
            
            <div class="form-group">
                <label>Password *</label>
                <input type="password" id="smtp_password" required placeholder="App password">
                <div class="hint">For Gmail, use an <a href="https://support.google.com/accounts/answer/185833" target="_blank">App Password</a></div>
            </div>
            
            <div class="form-group">
                <label>From Email *</label>
                <input type="email" id="smtp_from_email" required placeholder="noreply@yourdomain.com">
            </div>
            
            <div class="form-group">
                <label>From Name</label>
                <input type="text" id="smtp_from_name" value="SmartCRM" placeholder="SmartCRM">
            </div>
            
            <div style="display: flex; align-items: center;">
                <button type="button" class="btn-success" onclick="testSMTP()">Test Connection</button>
                <button type="button" class="btn-secondary" onclick="saveSMTP()">Save Configuration</button>
            </div>
            
            <div id="testResult" class="test-result"></div>
        </form>
    </div>
</div>

<script>
var configFile = 'smtp_config.php';

function loadCurrentConfig() {
    fetch(configFile + '?t=' + Date.now())
    .then(function(r) { return r.text(); })
    .then(function(text) {
        var container = document.getElementById('currentConfig');
        if (text.indexOf('$smtp_config') !== -1) {
            container.innerHTML = '<strong>Current Configuration:</strong><br>' + text.replace(/<\?php/, '').replace(/\?>/, '').replace(/\$/g, '').substring(0, 500);
        } else {
            container.innerHTML = '<em>No SMTP configuration found. Please configure below.</em>';
        }
    })
    .catch(function() {
        document.getElementById('currentConfig').innerHTML = '<em>No SMTP configuration found. Please configure below.</em>';
    });
}

function testSMTP() {
    var result = document.getElementById('testResult');
    result.className = 'test-result';
    result.style.display = 'block';
    result.innerHTML = 'Testing connection...';
    
    var fd = new FormData();
    fd.append('action', 'testSMTP');
    fd.append('host', document.getElementById('smtp_host').value);
    fd.append('port', document.getElementById('smtp_port').value);
    fd.append('secure', document.getElementById('smtp_secure').value);
    fd.append('username', document.getElementById('smtp_username').value);
    fd.append('password', document.getElementById('smtp_password').value);
    
    fetch('api.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            result.className = 'test-result success';
            result.innerHTML = '✓ ' + res.message;
        } else {
            result.className = 'test-result error';
            result.innerHTML = '✕ ' + (res.message || 'Connection failed');
        }
    })
    .catch(function(err) {
        result.className = 'test-result error';
        result.innerHTML = '✕ Network error: ' + err.message;
    });
}

function saveSMTP() {
    var fd = new FormData();
    fd.append('action', 'saveSMTPConfig');
    fd.append('host', document.getElementById('smtp_host').value);
    fd.append('port', document.getElementById('smtp_port').value);
    fd.append('secure', document.getElementById('smtp_secure').value);
    fd.append('username', document.getElementById('smtp_username').value);
    fd.append('password', document.getElementById('smtp_password').value);
    fd.append('from_email', document.getElementById('smtp_from_email').value);
    fd.append('from_name', document.getElementById('smtp_from_name').value);
    
    fetch('api.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.ok) {
            SmartDialog.success('SMTP configuration saved!');
            loadCurrentConfig();
        } else {
            SmartDialog.error(res.error || 'Failed to save');
        }
    })
    .catch(function(err) {
        SmartDialog.error('Network error: ' + err.message);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadCurrentConfig();
});
</script>

<?php include($PathPrefix . 'includes/footer.inc'); ?>
