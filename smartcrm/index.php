<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smarternow CRM Login</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="css/login-modern.css">
    <link rel="stylesheet" href="css/fontawesome6.4.0.all.min.css">
</head>
<body>
    <div id="bootSequence"></div>

    <form id="loginForm" autocomplete="off" onsubmit="return false;">
        <div class="brand">
            <i class="fas fa-flask"></i> Smarternow CRM
            <span class="pill">Login</span>
        </div>

        <div class="error-message" id="errorDisplay"></div>

        <input class="login-input" type="text" id="username" required maxlength="20" placeholder="User name" autofocus>
        <input class="login-input" type="password" id="password" required placeholder="Password">

        <button class="login-button" type="button" onclick="handleLogin(this);">
            <i class="fas fa-sign-in-alt"></i> Sign In
        </button>

        <div class="helper-links">
            <a onclick="toggleReset();">Forgot password?</a>
        </div>

        <div id="resetBox" style="display:none; margin-top:6px;">
            <input class="login-input" type="email" id="resetEmail" placeholder="Enter registered email">
            <button class="login-button" type="button" onclick="sendReset(this);">Send reset link</button>
        </div>
    </form>

    <script>
        const rootPath = '';
    </script>
    <script src="javascripts/jquery-3.6.0.min.js"></script>
    <script src="javascripts/bootmessge.js"></script>
    <script src="javascripts/login-modern.js"></script>
</body>
</html>
