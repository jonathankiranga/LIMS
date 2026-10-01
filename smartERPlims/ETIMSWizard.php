<?php
$PageSecurity = 1;
include('includes/session.inc');
include_once('includes/EtimsService.inc');

$Title = _('eTIMS Wizard');
include('includes/header.inc');

function textValue($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$fields = array(
    'ETIMS_BaseUrl' => '',
    'ETIMS_AuthUrl' => '',
    'ETIMS_InvoiceUrl' => '',
    'ETIMS_ClientId' => '',
    'ETIMS_ClientSecret' => '',
    'ETIMS_Scope' => '',
    'ETIMS_GrantType' => 'client_credentials',
    'ETIMS_KraPin' => '',
    'ETIMS_ControlUnitId' => '',
    'ETIMS_DeviceSerial' => '',
    'ETIMS_BranchId' => '',
    'ETIMS_AllowInsecure' => '0'
);

if (isset($_POST['save_settings'])) {
    foreach ($fields as $key => $default) {
        $value = isset($_POST[$key]) ? trim($_POST[$key]) : $default;
        if ($key === 'ETIMS_AllowInsecure') {
            $value = isset($_POST['ETIMS_AllowInsecure']) ? '1' : '0';
        }
        etimsSetConfigValue($key, $value);
    }
    prnMsg(_('eTIMS settings saved.'), 'success');
}

if (isset($_POST['authorize'])) {
    foreach ($fields as $key => $default) {
        $value = isset($_POST[$key]) ? trim($_POST[$key]) : $default;
        if ($key === 'ETIMS_AllowInsecure') {
            $value = isset($_POST['ETIMS_AllowInsecure']) ? '1' : '0';
        }
        etimsSetConfigValue($key, $value);
    }

    $tokenResult = etimsRequestAccessToken(true);
    if ($tokenResult['success']) {
        prnMsg(_('Authorization successful. Token stored.'), 'success');
    } else {
        prnMsg(_('Authorization failed: ') . $tokenResult['message'], 'error');
    }
}

if (isset($_POST['send_test_invoice'])) {
    $invoiceNo = trim($_POST['ETIMS_TestInvoiceNo'] ?? '');

    if ($invoiceNo === '') {
        prnMsg(_('Enter an invoice number before testing eTIMS submission.'), 'warn');
    } else {
        foreach ($fields as $key => $default) {
            $value = isset($_POST[$key]) ? trim($_POST[$key]) : $default;
            if ($key === 'ETIMS_AllowInsecure') {
                $value = isset($_POST['ETIMS_AllowInsecure']) ? '1' : '0';
            }
            etimsSetConfigValue($key, $value);
        }

        $submitResult = etimsSubmitInvoice(10, $invoiceNo);
        if ($submitResult['success']) {
            prnMsg(_('Test invoice submitted to eTIMS: ') . $submitResult['message'], 'success');
        } elseif (!empty($submitResult['skipped'])) {
            prnMsg(_('Test invoice submission skipped: ') . $submitResult['message'], 'warn');
        } else {
            prnMsg(_('Test invoice submission failed: ') . $submitResult['message'], 'error');
        }
    }
}

$values = array();
foreach ($fields as $key => $default) {
    $values[$key] = etimsGetConfigValue($key, $default);
}

$token = etimsGetConfigValue('ETIMS_AccessToken', '');
$tokenExpires = etimsGetConfigValue('ETIMS_TokenExpiresAt', '');

echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . $Title . '" alt="" /> ' . $Title . '</p>';

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
echo '<div><input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" /></div>';

echo '<div class="container-fluid">';
echo '<table class="table table-bordered"><caption>Step 1: Company & Device Details</caption>';
echo '<tr><td>KRA PIN</td><td><input type="text" name="ETIMS_KraPin" value="' . textValue($values['ETIMS_KraPin']) . '" size="20" /></td>';
echo '<td>Control Unit ID</td><td><input type="text" name="ETIMS_ControlUnitId" value="' . textValue($values['ETIMS_ControlUnitId']) . '" size="20" /></td></tr>';
echo '<tr><td>Device Serial</td><td><input type="text" name="ETIMS_DeviceSerial" value="' . textValue($values['ETIMS_DeviceSerial']) . '" size="20" /></td>';
echo '<td>Branch ID</td><td><input type="text" name="ETIMS_BranchId" value="' . textValue($values['ETIMS_BranchId']) . '" size="20" /></td></tr>';
echo '</table>';

echo '<table class="table table-bordered"><caption>Step 2: API Endpoints</caption>';
echo '<tr><td>Base URL</td><td><input type="text" name="ETIMS_BaseUrl" value="' . textValue($values['ETIMS_BaseUrl']) . '" size="60" /></td></tr>';
echo '<tr><td>Auth URL</td><td><input type="text" name="ETIMS_AuthUrl" value="' . textValue($values['ETIMS_AuthUrl']) . '" size="60" /></td></tr>';
echo '<tr><td>Invoice Submit URL</td><td><input type="text" name="ETIMS_InvoiceUrl" value="' . textValue($values['ETIMS_InvoiceUrl']) . '" size="60" /></td></tr>';
echo '</table>';

echo '<table class="table table-bordered"><caption>Step 3: Authorization</caption>';
echo '<tr><td>Client ID</td><td><input type="text" name="ETIMS_ClientId" value="' . textValue($values['ETIMS_ClientId']) . '" size="40" /></td></tr>';
echo '<tr><td>Client Secret</td><td><input type="password" name="ETIMS_ClientSecret" value="' . textValue($values['ETIMS_ClientSecret']) . '" size="40" /></td></tr>';
echo '<tr><td>Grant Type</td><td><input type="text" name="ETIMS_GrantType" value="' . textValue($values['ETIMS_GrantType']) . '" size="30" /></td></tr>';
echo '<tr><td>Scope</td><td><input type="text" name="ETIMS_Scope" value="' . textValue($values['ETIMS_Scope']) . '" size="40" /></td></tr>';
echo '<tr><td>Allow Insecure SSL</td><td><input type="checkbox" name="ETIMS_AllowInsecure" value="1" ' . ($values['ETIMS_AllowInsecure'] === '1' ? 'checked="checked"' : '') . ' /></td></tr>';
echo '</table>';

echo '<table class="table table-bordered"><caption>Authorization Status</caption>';
echo '<tr><td>Access Token</td><td>' . ($token !== '' ? 'Stored' : 'Not available') . '</td></tr>';
echo '<tr><td>Token Expires At</td><td>' . textValue($tokenExpires) . '</td></tr>';
echo '</table>';

echo '<table class="table table-bordered"><caption>Step 4: Test Invoice Submission</caption>';
echo '<tr><td>Invoice Number</td><td><input type="text" name="ETIMS_TestInvoiceNo" value="' . textValue($_POST['ETIMS_TestInvoiceNo'] ?? '') . '" size="20" />';
echo '<div class="page_help_text">' . _('Use a posted sales invoice document number (document type 10) to test the full eTIMS submit flow.') . '</div></td></tr>';
echo '</table>';

echo '<div class="centre">';
echo '<input type="submit" name="save_settings" value="' . _('Save Settings') . '" /> ';
echo '<input type="submit" name="authorize" value="' . _('Authorize with eTIMS') . '" /> ';
echo '<input type="submit" name="send_test_invoice" value="' . _('Send Test Invoice') . '" />';
echo '</div>';
echo '</div>';
echo '</form>';

include('includes/footer.inc');
