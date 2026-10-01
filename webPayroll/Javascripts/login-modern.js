// Minimal helpers for the modern login

function generalPurposeTypeLine(message, targetId = "errorDisplay", idx = 0) {
    const el = document.getElementById(targetId);
    if (!el) return;
    if (idx === 0) el.innerHTML = '';
    if (idx < message.length) {
        el.innerHTML += message.charAt(idx);
        setTimeout(() => generalPurposeTypeLine(message, targetId, idx + 1), 30);
    }
}

function handleLogin(btn) {
    const form = document.getElementById('loginForm');
    if (!form) return false;

    const errorEl = document.getElementById('errorDisplay');
    const username = document.getElementById('username') ? document.getElementById('username').value.trim() : '';
    const password = document.getElementById('password') ? document.getElementById('password').value : '';
    const companyField = form.querySelector('[name="CompanyNameField"]') ? form.querySelector('[name="CompanyNameField"]').value : null;
    const formId = form.querySelector('input[name="FormID"]') ? form.querySelector('input[name="FormID"]').value : '';
    const base = (typeof rootPath !== 'undefined' && rootPath !== '') ? rootPath.replace(/\/$/, '') + '/' : '';

    if (!username || !password) {
        generalPurposeTypeLine('Enter username and password.', 'errorDisplay');
        return false;
    }

    const original = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
    }

    $.ajax({
        url:  'Ajax/validate_login.php',
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify({ username, password, company: companyField, formId }),
        success: function(resp) {
            if (resp && resp.success) {
                window.location.href =  'homepage.php';
            } else {
                generalPurposeTypeLine(resp && resp.message ? resp.message : 'Login failed.', 'errorDisplay');
            }
        },
        error: function() {
            generalPurposeTypeLine('Network error during login.', 'errorDisplay');
        },
        complete: function() {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }
    });

    return false;
}

function handleLoginSubmit(event) {
    if (event) event.preventDefault();
    const btn = document.querySelector('#loginForm .login-button');
    return handleLogin(btn);
}

function toggleReset() {
    const box = document.getElementById('resetBox');
    if (!box) return;
    box.style.display = box.style.display === 'none' ? 'block' : 'none';
}

function sendReset(btn) {
    const emailInput = document.getElementById('resetEmail');
    const errorEl = document.getElementById('errorDisplay');
    if (!emailInput || !emailInput.value) {
        generalPurposeTypeLine('Enter your registered email to reset.', 'errorDisplay');
        return;
    }
    const email = emailInput.value.trim();
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    $.ajax({
        url:  'Ajax/SendEmail.php',
        method: 'POST',
        data: { getemail: email },
        success: function(resp) {
            generalPurposeTypeLine(resp || 'If the email exists, a reset was sent.', 'errorDisplay');
        },
        error: function() {
            generalPurposeTypeLine('Reset failed. Try again or contact admin.', 'errorDisplay');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = 'Send reset link';
        }
    });
}

// Initialize boot sequence
window.addEventListener('load', () => {
    try { localStorage.setItem('key','value'); localStorage.getItem('key'); } catch (e) {}
    if (typeof typeLine === 'function') {
        typeLine(bootLines[currentLine]);
    } else {
        document.getElementById('bootSequence').style.display = 'none';
        document.getElementById('loginForm').style.display = 'block';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', handleLoginSubmit);
    }
});
