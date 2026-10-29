<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BlockchainLIMS');
    session_start();
}
$isAdministrator = (isset($_SESSION['UserID']) && (int) $_SESSION['UserID'] === 1) || (isset($_SESSION['role']) && strtolower((string) $_SESSION['role']) === 'admin');
if (!$isAdministrator) {
    http_response_code(403);
    exit('Administrator access is required to manage AI API settings.');
}
require_once 'db_connection.php';

function aiSettingsKey($secret)
{
    return hash('sha256', (string) $secret, true);
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providedKey = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
    if ($providedKey === '' || strpos($providedKey, 'sk-') !== 0) {
        $error = 'Enter a valid API key. The saved key is not displayed on this page.';
    } elseif (!function_exists('openssl_encrypt') || !function_exists('random_bytes')) {
        $error = 'OpenSSL is required to encrypt the API key before storage.';
    } else {
        $key = aiSettingsKey($secretKey);
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($providedKey, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ciphertext === false || $tag === '') {
            $error = 'The API key could not be encrypted.';
        } else {
            $stmt = $conn->prepare("INSERT INTO ai_api_settings (setting_name, encrypted_value, nonce, auth_tag) VALUES ('openai_api_key', ?, ?, ?) ON DUPLICATE KEY UPDATE encrypted_value = VALUES(encrypted_value), nonce = VALUES(nonce), auth_tag = VALUES(auth_tag)");
            if (!$stmt) {
                $error = 'Unable to prepare secure settings storage. Run the AI COA Notes SQL migration first.';
            } else {
                $ciphertext = base64_encode($ciphertext);
                $nonce = base64_encode($nonce);
                $tag = base64_encode($tag);
                $stmt->bind_param('sss', $ciphertext, $nonce, $tag);
                if ($stmt->execute()) {
                    $message = 'API key encrypted and saved. The key is never shown again.';
                } else {
                    $error = 'Unable to save the encrypted API key. Confirm the SQL migration has been run.';
                }
                $stmt->close();
            }
        }
    }
}

$configured = false;
$result = $conn->query("SELECT setting_name FROM ai_api_settings WHERE setting_name = 'openai_api_key' LIMIT 1");
if ($result && $result->num_rows > 0) {
    $configured = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI API Settings</title>
<link rel="stylesheet" href="css/quickbookslook.css">
<style>
.ai-settings{max-width:760px;margin:18px auto;padding:18px;background:#fff;border:1px solid #d9e0e8;border-radius:6px;font:14px Arial,sans-serif;color:#243041}
.ai-settings h2{font-size:20px;margin:0 0 12px}.ai-settings p{line-height:1.5}
.ai-settings label{display:block;font-weight:600;margin:16px 0 6px}
.ai-settings input{width:100%;box-sizing:border-box;padding:10px;border:1px solid #b9c4d0;border-radius:4px}
.ai-settings button{margin-top:12px;padding:9px 14px;border:0;border-radius:4px;background:#185abd;color:#fff;cursor:pointer}
.ai-settings .status{padding:10px;background:#edf8ee;border:1px solid #b7dfbd;margin:10px 0}
.ai-settings .error{padding:10px;background:#fff1f0;border:1px solid #f0b8b5;margin:10px 0}
</style>
</head>
<body>
<div class="ai-settings">
<h2>AI API Settings</h2>
<p>Configure the OpenAI API key used by AI-assisted Certificate of Analysis Notes. The key is encrypted with the server's existing SECRET_KEY before it is stored in the database. It is never sent back to the browser after saving.</p>
<?php if ($message !== ''): ?><div class="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<p><strong>Configuration status:</strong> <?php echo $configured ? 'An API key is stored.' : 'No API key is configured.'; ?></p>
<form method="post" autocomplete="off">
<label for="api_key">OpenAI API key</label>
<input type="password" id="api_key" name="api_key" autocomplete="new-password" required placeholder="Paste API key here">
<button type="submit">Encrypt and Save API Key</button>
</form>
<p>Security: use this page only over HTTPS. The API key must not be added to Git, SQL migration files, or client-side JavaScript.</p>
</div>
</body>
</html>
