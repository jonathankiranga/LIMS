<?php
/**
 * Menu Data Generator
 * Builds menu data from MainMenuLinksArray and outputs as JavaScript
 * Called by index.php to populate sidebar menu
 */

// Set content type for JavaScript
header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

// Include menu structure
include('../includes/MainMenuLinksArray.php');

// Start JavaScript output
echo "// Generated menu data\n";
echo "var menuItems = [];\n\n";

// Check if session data exists
if (!isset($_SESSION['ModulesEnabled']) || !isset($_SESSION['AllowedPageSecurityTokens'])) {
    echo "console.warn('User session not properly initialized for menu');\n";
    die;
}

// Build menu from MainMenuLinksArray
$items = array();

// Iterate through modules
for ($i = 0; $i < count($ModuleLink); $i++) {
    // Skip if module not enabled
    if (!isset($_SESSION['ModulesEnabled'][$i]) || $_SESSION['ModulesEnabled'][$i] != 1) {
        continue;
    }
    
    $Module = $ModuleLink[$i];
    $ModuleName = $ModuleList[$i];
    $moduleIcon = 'fa-box'; // Default icon

    // Define module icons
    $moduleIcons = array(
        'PO' => 'fa-shopping-cart',
        'SO' => 'fa-receipt',
        'MA' => 'fa-cogs',
        'BA' => 'fa-university',
        'GL' => 'fa-book',
        'SA' => 'fa-handshake',
        'ST' => 'fa-warehouse',
        'FA' => 'fa-building',
        'HR' => 'fa-users',
        'CRM' => 'fa-address-book',
        'LA' => 'fa-flask'
    );

    if (isset($moduleIcons[$Module])) {
        $moduleIcon = $moduleIcons[$Module];
    }

    // Add module as main menu item
    echo "menuItems.push(['{$ModuleName}', '', '{$moduleIcon}']);\n";

    // Add Transactions
    if (isset($MenuItems[$Module]['Transactions']['Caption'])) {
        echo "menuItems.push(['|Transactions', '', 'fa-arrow-right']);\n";
        
        foreach ($MenuItems[$Module]['Transactions']['Caption'] as $t => $Caption) {
            $Url = $MenuItems[$Module]['Transactions']['URL'][$t];
            $ScriptNameArray = explode('?', substr($Url, 1));
            $PageSecurity = isset($_SESSION['PageSecurityArray'][$ScriptNameArray[0]]) 
                ? $_SESSION['PageSecurityArray'][$ScriptNameArray[0]] 
                : null;
            
            // Check security access
            if (in_array($PageSecurity, $_SESSION['AllowedPageSecurityTokens']) || !isset($PageSecurity)) {
                echo "menuItems.push(['||{$Caption}', '{$Url}', 'fa-file-alt']);\n";
            }
        }
    }

    // Add Reports
    if (isset($MenuItems[$Module]['Reports']['Caption'])) {
        echo "menuItems.push(['|Reports & Inquiries', '', 'fa-arrow-right']);\n";
        
        foreach ($MenuItems[$Module]['Reports']['Caption'] as $r => $Caption) {
            $Url = $MenuItems[$Module]['Reports']['URL'][$r];
            $ScriptNameArray = explode('?', substr($Url, 1));
            $PageSecurity = isset($_SESSION['PageSecurityArray'][$ScriptNameArray[0]]) 
                ? $_SESSION['PageSecurityArray'][$ScriptNameArray[0]] 
                : null;
            
            // Check security access
            if (in_array($PageSecurity, $_SESSION['AllowedPageSecurityTokens']) || !isset($PageSecurity)) {
                echo "menuItems.push(['||{$Caption}', '{$Url}', 'fa-chart-bar']);\n";
            }
        }
    }

    // Add Maintenance/Setup
    if (isset($MenuItems[$Module]['Maintenance']['Caption'])) {
        echo "menuItems.push(['|Setup', '', 'fa-arrow-right']);\n";
        
        foreach ($MenuItems[$Module]['Maintenance']['Caption'] as $m => $Caption) {
            $Url = $MenuItems[$Module]['Maintenance']['URL'][$m];
            $ScriptNameArray = explode('?', substr($Url, 1));
            $PageSecurity = isset($_SESSION['PageSecurityArray'][$ScriptNameArray[0]]) 
                ? $_SESSION['PageSecurityArray'][$ScriptNameArray[0]] 
                : null;
            
            // Check security access
            if (in_array($PageSecurity, $_SESSION['AllowedPageSecurityTokens']) || !isset($PageSecurity)) {
                echo "menuItems.push(['||{$Caption}', '{$Url}', 'fa-tools']);\n";
            }
        }
    }
}

echo "\nconsole.log('Menu items loaded: ' + menuItems.length);\n";
?>
