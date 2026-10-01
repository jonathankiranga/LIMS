 bootLines = []; // Declare once here
 currentLine = 0;
 typingSpeed = 100; // Define typing speed

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
                    "Backing up complete.",
                    "Good bye."
                ];
                break;
             default:
                bootLines = [
                    "Initializing system boot sequence...",
                    "System boot complete.",
                    "Welcome, Lab User."
                ];
                break;
        }

    function typeLine(line, index = 0) {
        if (index === 0) {
            document.getElementById("bootSequence").innerHTML = ''; // Clear the content
        }
        
        if (index < line.length) {
            document.getElementById("bootSequence").innerHTML += line.charAt(index);
            index++;
            setTimeout(() => typeLine(line, index), typingSpeed);
        } else {
            document.getElementById("bootSequence").innerHTML += "<br>";
            currentLine++;
            if (currentLine < bootLines.length) {
                const randomDelay = () => Math.floor(Math.random() * (3000 - 1000 + 1)) + 1000;
                setTimeout(() => typeLine(bootLines[currentLine]), randomDelay());
            } else {
                document.getElementById("bootSequence").innerHTML += "<br><span class='cursor'></span>";
                setTimeout(() => {
                    document.getElementById("bootSequence").style.display = "none";
                    document.getElementById("loginForm").style.display = "block";
                    localStorage.setItem('saygoodbye',null);
                }, 2000);
            }
        }
    }