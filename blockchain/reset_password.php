<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In...</title>
    <link rel="stylesheet" href="css/default.css" type="text/css"/>
</head>
<body>
<div>
        <div id="errorDisplay" class="error-message"></div> <!-- Placeholder for error messages -->
        <h3>Reset</h3>
        <input type="hidden" id="token"  placeholder="Enter password">
        <input type="password" id="newpassword" class="login-input" placeholder="Enter password">
        <input type="password" id="confirmpassword" class="login-input" placeholder="Confirm password">
        <button class="close-modal"  onclick="resetpassword()">Reset Password</button>
</div>

<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="js/toastr/toastr.min.js"></script>

<script>
$(document).ready(function () {
    // Parse query string parameters
    const queryParams = new URLSearchParams(window.location.search);
    if (queryParams.has('token')) {
        const tokenValue = queryParams.get('token');
        document.getElementById('token').value = tokenValue;
    } else {
        document.getElementById('errorDisplay').innerHTML = 'No reset token found. Please use the link from your email.';
        document.querySelector('.close-modal').disabled = true;
    }
});
</script>

<script>
    const typingSpeed = 50;
    const lineDelay = 1000;
    let currentLine = 0;

   
 // General-purpose typeLine function for custom messages
    function generalPurposeTypeLine(message, targetElementId, callback = null, index = 0) {
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


    function resetpassword(){
            const token = document.getElementById("token").value;
            const confirmpassword = document.getElementById("confirmpassword").value;
            const password = document.getElementById("newpassword").value;

            if (!token) {
                generalPurposeTypeLine("Invalid or missing reset token.", "errorDisplay");
                return;
            }
            if (!password || !confirmpassword) {
                generalPurposeTypeLine("Please enter and confirm your new password.", "errorDisplay");
                return;
            }
            if (password !== confirmpassword) {
                generalPurposeTypeLine("Passwords do not match.", "errorDisplay");
                return;
            }

            $.ajax({
                url: "ajax/register_user.php",
                type: "POST",
                dataType: "json",
                data: {
                    action: "validate_token",
                    reset_token: token,
                    confirm_password: confirmpassword,
                    new_password: password
                },
                success: function(data) {
                    if (data.success) {
                        toastr.success(data.message);
                        setTimeout(function(){ window.location.href = "index.php"; }, 2000);
                    } else {
                        generalPurposeTypeLine(data.message || "Reset failed.", "errorDisplay");
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error("Error: " + error);
                }
            });
    }
    
      // Display error message using typeLine
    function displayErrorMessage(message) {
        document.getElementById("errorDisplay").innerHTML = ""; // Clear any previous error
        generalPurposeTypeLine(message, "errorDisplay"); // Type out the error message
    }

   

</script>
</body>
</html>
