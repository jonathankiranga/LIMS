// Login greeting: short, skippable, and remembered for this browser.
var bootLines = [];
var currentLine = 0;
var typingSpeed = 18;
var greetingTimer = null;
var skipGreetingButton = null;
var greetingFinished = false;

(function () {
    var reason = null;
    try { reason = localStorage.getItem('saygoodbye'); } catch (e) {}

    if (reason === 'proof') {
        bootLines = [
            'You need to log in to continue.',
            'Please enter your credentials.'
        ];
    } else if (reason === 'Good Bye') {
        bootLines = [
            'Shutting down... Beginning backup',
            'Backup complete.',
            'Good bye.'
        ];
    } else {
        bootLines = [
            'Initializing system boot sequence...',
            'System checks complete.',
            'Welcome, Lab User.'
        ];
    }
})();

function finishLoginGreeting(rememberSkip) {
    if (greetingFinished) return;
    greetingFinished = true;
    if (greetingTimer !== null) {
        window.clearTimeout(greetingTimer);
        greetingTimer = null;
    }
    if (rememberSkip) {
        try { localStorage.setItem('skipLoginGreeting', '1'); } catch (e) {}
    }
    var bootEl = document.getElementById('bootSequence');
    var formEl = document.getElementById('loginForm');
    if (bootEl) bootEl.style.display = 'none';
    if (formEl) formEl.style.display = 'block';
    if (skipGreetingButton && skipGreetingButton.parentNode) {
        skipGreetingButton.parentNode.removeChild(skipGreetingButton);
    }
    try { localStorage.setItem('saygoodbye', 'null'); } catch (e) {}
}

function addGreetingSkipOption() {
    if (skipGreetingButton || !document.body) return;
    skipGreetingButton = document.createElement('button');
    skipGreetingButton.type = 'button';
    skipGreetingButton.id = 'skipLoginGreeting';
    skipGreetingButton.textContent = 'Skip greeting';
    skipGreetingButton.setAttribute('aria-label', 'Skip login greeting');
    skipGreetingButton.style.cssText = 'position:fixed;top:16px;right:16px;z-index:10000;padding:9px 14px;border:1px solid rgba(255,255,255,.35);border-radius:6px;background:#263445;color:#fff;font:600 13px Arial,sans-serif;cursor:pointer;';
    skipGreetingButton.addEventListener('click', function () {
        finishLoginGreeting(true);
    });
    document.body.appendChild(skipGreetingButton);
}

function typeLine(line, index) {
    var bootEl = document.getElementById('bootSequence');
    if (greetingFinished || !bootEl) {
        finishLoginGreeting(false);
        return;
    }

    if (index === undefined) index = 0;
    if (index === 0 && currentLine === 0) {
        try {
            if (localStorage.getItem('skipLoginGreeting') === '1') {
                finishLoginGreeting(false);
                return;
            }
        } catch (e) {}
        addGreetingSkipOption();
        bootEl.style.display = 'block';
        bootEl.textContent = '';
    }

    if (index < line.length) {
        bootEl.textContent += line.charAt(index);
        greetingTimer = window.setTimeout(function () {
            typeLine(line, index + 1);
        }, typingSpeed);
        return;
    }

    bootEl.appendChild(document.createElement('br'));
    currentLine++;
    if (currentLine < bootLines.length) {
        greetingTimer = window.setTimeout(function () {
            typeLine(bootLines[currentLine], 0);
        }, 120);
    } else {
        var cursor = document.createElement('span');
        cursor.className = 'cursor';
        bootEl.appendChild(cursor);
        greetingTimer = window.setTimeout(function () {
            finishLoginGreeting(false);
        }, 300);
    }
}
