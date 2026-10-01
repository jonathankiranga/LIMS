bootLines = []; // Declare once here
currentLine = 0;
typingSpeed = 90;

switch (localStorage.getItem('saygoodbye')) {
    case "proof":
        bootLines = [
            "You need to log in to continue.",
            "Please enter your credentials."
        ];
        break;
    case "Good Bye":
        bootLines = [
            "Shutting down... Beginning backup",
            "Backup complete.",
            "Good bye."
        ];
        break;
    default:
        bootLines = [
            "Initializing system boot sequence...",
            "System checks complete.",
            "Welcome, SmartERP LIMS user."
        ];
        break;
}

function typeLine(line, index = 0) {
    const bootEl = document.getElementById("bootSequence");
    const formEl = document.getElementById("loginForm");
    if (!bootEl) return;

    if (index === 0) {
        bootEl.innerHTML = '';
    }

    if (index < line.length) {
        bootEl.innerHTML += line.charAt(index);
        index++;
        setTimeout(() => typeLine(line, index), typingSpeed);
    } else {
        bootEl.innerHTML += "<br>";
        currentLine++;
        if (currentLine < bootLines.length) {
            const randomDelay = () => Math.floor(Math.random() * (2200 - 900 + 1)) + 900;
            setTimeout(() => typeLine(bootLines[currentLine]), randomDelay());
        } else {
            bootEl.innerHTML += "<br><span class='cursor'></span>";
            setTimeout(() => {
                if (bootEl) {
                    bootEl.style.display = "none";
                }
                if (formEl) {
                    formEl.style.display = "block";
                }
                localStorage.setItem('saygoodbye', null);
            }, 1400);
        }
    }
}
