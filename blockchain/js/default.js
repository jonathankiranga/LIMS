     typingSpeed = 50;
     lineDelay = 1000;
     currentLine = 0;
    
    if (typeof toastr === 'object' && toastr !== null) {
        toastr.options = {
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "tapToDismiss": true,
        "closeButton": false
      }
    } else {
        console.log('toastr is not an object');
    }

    function formatDate(dateString) {
        let date = new Date(dateString);
        let day = date.getDate().toString().padStart(2, '0');
        let month = (date.getMonth() + 1).toString().padStart(2, '0');
        let year = date.getFullYear();
        return `${day}-${month}-${year} `;
    }
    // Asynchronous function to fetch system settings and store in localStorage

    async function loadSystemSettings() {
        try {
            // Fetch the system settings from the PHP script
            const response = await fetch('ajax/get_system_settings.php');

            // Check if the response is successful
            if (response.ok) {
                const data = await response.json(); // Parse the response as JSON

                // Check the 'status' in the response
                if (data.status === 'success') {
                    // Save the settings to localStorage
                    localStorage.setItem('systemSettings', JSON.stringify(data.data));  // data.data contains the actual settings
                    localStorage.setItem('settingsLoaded', 'true'); // Add a flag to indicate settings are loaded

                    console.log("System settings successfully loaded:", data.data);
                } else {
                    console.error('Error: ' + data.message); // Handle the case where no data is found
                }
            } else {
                console.error("Failed to load system settings. HTTP Status: " + response.status);
            }
        } catch (error) {
            console.error("Error fetching system settings:", error);
        }
    }

    function quiktype(message) {
        const typingSpeed = 50;
        const targetElementId = 'targetElement_' + new Date().getTime();
       // Create the target element dynamically
        const targetElement = document.createElement('div');
        targetElement.id = targetElementId; // Unique ID
        targetElement.style.position = 'fixed'; // Position it at the top of the screen
        targetElement.style.top = '10%'; // Adjust top distance as needed
        targetElement.style.left = '60%'; // Center it horizontally
        targetElement.style.transform = 'translateX(-50%)'; // Correct horizontal centering
        targetElement.style.fontFamily = 'monospace'; // Optional: Use monospace font for typing effect
        targetElement.style.fontSize = '14px'; // Adjust font size as needed
        targetElement.style.color = '#800000'; // Apply custom text color
        targetElement.style.zIndex = '9999'; // Ensure it's on top of other elements
        targetElement.style.whiteSpace = 'pre'; // Ensure whitespace is respected
        targetElement.style.width = '50vw'; // Position it at the top of the screen

        document.body.appendChild(targetElement); // Append it to the body (or any other container)

        // Typing logic
        let index = 0;
        function typeCharacter() {
            if (index < message.length) {
                targetElement.innerHTML += message.charAt(index);
                index++;
                setTimeout(typeCharacter, typingSpeed);
            } else {
                // Add the blinking cursor
                targetElement.innerHTML += "<span class='cursor'>|</span>";

                // Remove element after a delay
                const delay = (message.length * typingSpeed) + 1000; // Add 1 second after typing
                setTimeout(() => {
                    targetElement.remove();
                }, delay);
            }
        }
        typeCharacter();
    }

 // General-purpose typeLine function for custom messages
    function generalPurposeTypeLine(message, targetElementId="errorDisplay", callback = null, index = 0) {
        const targetElement = document.getElementById(targetElementId);
         // Clear the target element on the first call
        if (index === 0) {
            targetElement.innerHTML = ''; // Clear the content
        }
        
        if (index < message.length) {
            targetElement.innerHTML += message.charAt(index);
            setTimeout(() => generalPurposeTypeLine(message, targetElementId, callback, index + 1), typingSpeed);
        } else if (callback) {
            callback();
        }
    }

    function showForgotPassword() {
        document.getElementById("resetEmail").value='';
        document.getElementById("forgotPasswordModal").style.display = "block";
    }
    
    function showregisteruser() {
          document.getElementById("newusername").value='';
          document.getElementById("newemail").value='';
          document.getElementById("full_name").value='';
          document.getElementById("newtelephone").value='';
          document.getElementById("newpassword").value='';
          document.getElementById("registernewuser").style.display = "block";
    }

    function closeForgotPassword() {
        document.getElementById("forgotPasswordModal").style.display = "none";
    }

    function submitForgotPassword() {
        const email = document.getElementById("resetEmail").value.trim();

        if (!email) {
            generalPurposeTypeLine("Please enter your email address.", "errorDisplay");
            return;
        }

        document.getElementById("forgotPasswordModal").style.display = "none";

        const encodedEmail = encodeURIComponent(email);
        fetch("ajax/register_user.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `action=reset_request&email=${encodedEmail}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                generalPurposeTypeLine(data.message, "errorDisplay");
            } else {
                generalPurposeTypeLine(data.message || "Could not send reset link.", "errorDisplay");
            }
        })
        .catch(error => {
            toastr.error("Error: " + error.message);
        });
    }
    
    function closeregisteruser(){
            const username  = document.getElementById("newusername").value.trim();
            const email     = document.getElementById("newemail").value.trim();
            const full_name = document.getElementById("full_name").value.trim();
            const telephone = document.getElementById("newtelephone").value.trim();
            const password  = document.getElementById("newpassword").value;

            // Validate before closing modal
            if (!full_name) { generalPurposeTypeLine("Full name is required.", "errorDisplay"); return; }
            if (!username)  { generalPurposeTypeLine("Username is required.", "errorDisplay"); return; }
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                generalPurposeTypeLine("A valid email address is required.", "errorDisplay"); return;
            }
            if (!password || password.length < 6) {
                generalPurposeTypeLine("Password must be at least 6 characters.", "errorDisplay"); return;
            }

            // Close modal only after validation passes
            document.getElementById("registernewuser").style.display = "none";

            $.ajax({
                url: "ajax/register_user.php",
                type: "POST",
                dataType: "json",
                data: {
                    action: "register",
                    username: username,
                    email: email,
                    full_name: full_name,
                    role: '10',
                    password: password,
                    telephone: telephone
                },
                success: function(data) {
                    if (data.success) {
                        toastr.success(data.message);
                    } else {
                        // Re-open modal so user can correct
                        document.getElementById("registernewuser").style.display = "block";
                        generalPurposeTypeLine(data.message, "errorDisplay");
                    }
                },
                error: function(xhr, status, error) {
                    document.getElementById("registernewuser").style.display = "block";
                    toastr.error("Registration error: " + error);
                }
            });
    }
        // Display error message using typeLine
    function displayErrorMessage(message) {
        document.getElementById("errorDisplay").innerHTML = ""; // Clear any previous error
        generalPurposeTypeLine(message, "errorDisplay"); // Type out the error message
    }

    function handleLogin(loginbutton) {
        const log=loginbutton;
        const username = document.getElementById("username").value;
        const password = document.getElementById("password").value;
         console.log('username:',username);
         console.log('password:',password);
          
                validateCredentials(username, password, function(success, message) {
                if (success) {
                   log.disabled = true;
                   log.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
                 setTimeout(function() { window.location.href = "homepage.php"; },5000); 
                } else {
                    displayErrorMessage(message); // Type out error message
                }
            });
    }

    // Placeholder for login validation
    function validateCredentials(username, password, callback) {
         $.ajax({
            url: 'ajax/validate_login.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ username: username, password: password }),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    // Store credentials in localStorage
                    localStorage.setItem('full_name', data.full_name);
                    localStorage.setItem('username', data.username);
                    localStorage.setItem('role', data.role);
                    localStorage.setItem('user_id', data.user_id);
                    localStorage.setItem('department', data.department);
                    localStorage.setItem('telephone', data.telephone);
                    localStorage.setItem('email', data.email);
                    
                    callback(true, data.message || "Login successful.");
                } else {
                    localStorage.removeItem('full_name');
                    localStorage.removeItem('username');
                    localStorage.removeItem('role');
                    localStorage.removeItem('user_id');
                    localStorage.removeItem('department');
                    localStorage.removeItem('telephone');
                    localStorage.removeItem('email');
                   callback(false, data.message || "Invalid credentials.");
                }
            },
            error: function(xhr, status, error) {
               callback(false, "An error occurred during login. Please try again.");
            }
        });
    }

