<?php
if (!isset($RootPath)) {
    // Derive web root safely even when PATH_INFO is present (e.g. /index.php/anything)
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ($_SERVER['PHP_SELF'] ?? '');
    $RootPath = dirname($scriptName);
    if ($RootPath == '/' || $RootPath == "\\") {
        $RootPath = '';
    }
}

// Normalized base used for assets (keeps leading slash when present)
$AssetBase = rtrim(str_replace('\\', '/', $RootPath), '/');
$AssetBase = ($AssetBase === '') ? '' : '/' . ltrim($AssetBase, '/');

// Cache-buster versions to avoid stale CSS/JS when the login page is refreshed
$assetVersions = [
    'loginCss'  => @filemtime(__DIR__ . '/../css/login-modern.css') ?: time(),
    'faCss'     => @filemtime(__DIR__ . '/../css/fontawesome6.4.0.all.min.css') ?: time(),
    'jqueryJs'  => @filemtime(__DIR__ . '/../javascripts/jquery-3.6.0.min.js') ?: time(),
    'bootJs'    => @filemtime(__DIR__ . '/../javascripts/bootmessge.js') ?: time(),
    'loginJs'   => @filemtime(__DIR__ . '/../javascripts/login-modern.js') ?: time(),
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SmartERPlims Login</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($AssetBase); ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($AssetBase); ?>/css/login-modern.css?v=<?php echo $assetVersions['loginCss']; ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($AssetBase); ?>/css/fontawesome6.4.0.all.min.css?v=<?php echo $assetVersions['faCss']; ?>">
</head>
<body>
    <div id="bootSequence"></div>

    <form id="loginForm" autocomplete="off" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="brand">
            <i class="fas fa-flask"></i> SmartERP LIMS
            <span class="pill">Login</span>
        </div>

        <?php if (isset($demo_text)) : ?>
            <div class="error-message"><?php echo $demo_text; ?></div>
        <?php else: ?>
            <div class="error-message" id="errorDisplay"></div>
        <?php endif; ?>

        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />

        <?php
        // Company selector (preserve existing logic)
        if (isset($CompanyList) && is_array($CompanyList)) {
            foreach ($CompanyList as $key => $CompanyEntry){
                if ($DefaultDatabase == $CompanyEntry['database']) {
                    $CompanyNameField = "$key";
                    $DefaultCompany = $CompanyEntry['company'];
                }
            }

            if ($AllowCompanySelectionBox === 'Hide'){
                echo '<input type="hidden" name="CompanyNameField" value="' .  $CompanyNameField . '" />';
            } elseif ($AllowCompanySelectionBox === 'ShowInputBox'){
                echo '<label class="sr-only" for="DefaultCompany">'._('Company').'</label>';
                echo '<input class="login-input" type="text" name="DefaultCompany" required value="' .  htmlspecialchars($DefaultCompany ,ENT_QUOTES,'UTF-8') . '" disabled />';
                echo '<input type="hidden" name="CompanyNameField" value="' .  $CompanyNameField . '" />';
            } else {
                echo '<label class="sr-only" for="CompanyNameField">'._('Company').'</label>';
                echo '<select name="CompanyNameField" class="login-select">';
                foreach ($CompanyList as $key => $CompanyEntry){
                    if (is_dir('companies/' . $CompanyEntry['database']) ){
                        $selected = ($CompanyEntry['database'] == $DefaultDatabase) ? 'selected' : '';
                        echo '<option '.$selected.' value="'.$key.'">' . htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8') . '</option>';
                    }
                }
                echo '</select>';
            }
        } else {
            if ($AllowCompanySelectionBox === 'Hide'){
                echo '<input type="hidden" name="CompanyNameField" value="' . $DefaultCompany . '" />';
            } else if ($AllowCompanySelectionBox === 'ShowInputBox'){
                echo '<label class="sr-only" for="CompanyNameField">'._('Company').'</label>';
                echo '<input class="login-input" type="text" name="CompanyNameField" required value="' .  htmlspecialchars($DefaultCompany ,ENT_QUOTES,'UTF-8') . '" />';
            } else {
                echo '<label class="sr-only" for="CompanyNameField">'._('Company').'</label>';
                echo '<select name="CompanyNameField" class="login-select">';
                echo '<option selected value="'.$DefaultCompany.'">' . htmlspecialchars($DefaultCompany,ENT_QUOTES,'UTF-8') . '</option>';
                echo '</select>';
            }
        }
        ?>

        <input class="login-input" type="text" name="UserNameEntryField" id="username" required maxlength="20" placeholder="<?php echo _('User name'); ?>" autofocus>
        <input class="login-input" type="password" name="Password" id="password" required placeholder="<?php echo _('Password'); ?>">

        <button class="login-button" type="submit" name="SubmitUser" onclick="handleLogin(this); return false;">
            <i class="fas fa-sign-in-alt"></i> <?php echo _('Sign In'); ?>
        </button>

        <div class="helper-links">
            <span><?php echo htmlspecialchars($_SESSION['CompanyRecord']['coyname'] ?? ''); ?></span>
            <span>
                <a onclick="toggleReset();">Forgot password?</a>
            </span>
        </div>

        <div id="resetBox" style="display:none; margin-top:6px;">
            <input class="login-input" type="email" id="resetEmail" placeholder="Enter registered email">
            <button class="login-button" type="button" onclick="sendReset(this);">Send reset link</button>
        </div>
    </form>

    <script>
        // Expose the resolved root for Ajax helpers in login-modern.js
        const rootPath = "<?php echo htmlspecialchars($AssetBase, ENT_QUOTES, 'UTF-8'); ?>";
    </script>
    <script src="<?php echo htmlspecialchars($AssetBase); ?>/javascripts/jquery-3.6.0.min.js?v=<?php echo $assetVersions['jqueryJs']; ?>"></script>
    <script src="<?php echo htmlspecialchars($AssetBase); ?>/javascripts/bootmessge.js?v=<?php echo $assetVersions['bootJs']; ?>"></script>
    <script src="<?php echo htmlspecialchars($AssetBase); ?>/javascripts/login-modern.js?v=<?php echo $assetVersions['loginJs']; ?>"></script>
</body>
</html>
