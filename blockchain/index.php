<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smarternow LIMS</title>
    <link rel="stylesheet" href="css/default.css" type="text/css"/>
    <link rel="icon" type="image/x-icon" href="logos/favicon.ico?v=2">
</head>
<body>
<div id="bootSequence"></div>
    <form onsubmit="return false;" autocomplete="off">
    <div id="loginForm">
        <h2>Login</h2>
        <input type="text" id="username" class="login-input" placeholder="Username">
        <input type="password" id="password" class="login-input" placeholder="Password">
        <button class="login-button" onclick="handleLogin(this)">Login</button>
        <div id="errorDisplay" class="error-message"></div> <!-- Placeholder for error messages -->
        <a class="forgot-password" onclick="showForgotPassword()">Forgot Password?</a>
        <a class="forgot-password" onclick="showregisteruser()">Register</a>
    </div>
    </form>
   <!-- Modal for "Forgot Password" -->
    <form onsubmit="return false;" autocomplete="off">
    <div id="forgotPasswordModal">
        <h3>Password Recovery</h3>
        <p>Enter your email to reset your password:</p>
        <input type="email" class="modal-input" id="resetEmail" placeholder="Email Address">
        <button class="close-modal" onclick="submitForgotPassword()">Send Reset Link</button>
        <button class="close-modal" style="margin-top:4px;background:#555;" onclick="closeForgotPassword()">Cancel</button>
    </div>
    </form>
    
    <form onsubmit="return false;" autocomplete="off">
    <div id="registernewuser">
         <h3>Register New User</h3>
            <input type="text" id="full_name" class="login-input" placeholder="Enter full name">
            <input type="text" id="newusername" class="login-input" placeholder="Enter username">
            <input type="email" id="newemail" class="login-input" placeholder="Enter email">
            <input type="tel" id="newtelephone" class="login-input" placeholder="Enter mobile Number">
            <input type="password" id="newpassword" class="login-input" placeholder="Enter password">
            <button class="close-modal"  onclick="closeregisteruser()">Create Account</button>
    </div>
    </form>
<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src ="js/bootmessge.js"></script>
<script type="text/javascript" src ="js/default.js"></script>
<script type="text/javascript" src="js/toastr/toastr.min.js"></script>
<script>
    try {
        localStorage.setItem('key', 'value');
        const retrievedValue = localStorage.getItem('key');
        console.log('localStorage set/get works:', retrievedValue);
    } catch (e) {
        console.error('localStorage is not available:', e);
        alert('LocalStorage is blocked or not working in this browser.');
    }

    window.onload = async () => {
    try {
        // Wait for system settings to be loaded before proceeding
        await loadSystemSettings();
        // Now type the boot sequence line after settings are loaded
        typeLine(bootLines[currentLine]);
    } catch (error) {
        console.error('Error loading system settings:', error);
    }
  };
</script>
</body>
</html>

