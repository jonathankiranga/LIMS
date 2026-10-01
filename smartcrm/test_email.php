<?php
/**
 * Test script for email and calendar invite functionality
 * Run from command line: php test_email.php
 */

// Load SMTP config
require_once __DIR__ . '/smtp_config.php';

echo "=== SmartCRM Email & Calendar Test ===\n\n";

// Test 1: SMTP Connection
echo "1. Testing SMTP Connection...\n";
$result = testSMTPConnection();
echo "   Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "   Message: " . $result['message'] . "\n\n";

// Test 2: Send Test Email
echo "2. Sending Test Email...\n";
$testEmail = 'recovery@erplabworks.co.ke'; // Change to your test email
$result = sendSMTPEmail(
    $testEmail,
    'SmartCRM Test Email',
    '<h1>Test Email</h1><p>This is a test email from SmartCRM.</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>',
    '',
    'SmartCRM Test'
);
echo "   Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if (!$result['success']) {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// Test 3: Send Calendar Invite
echo "3. Sending Calendar Invite...\n";
$start = date('Y-m-d H:i:s', strtotime('+1 hour'));
$end = date('Y-m-d H:i:s', strtotime('+2 hours'));
$result = sendICSInvite(
    $testEmail,
    'Test Meeting - SmartCRM',
    'This is a test calendar invite from SmartCRM.',
    $start,
    $end,
    'Conference Room A',
    '',
    'SmartCRM Test'
);
echo "   Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if (!$result['success']) {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// Test 4: Generate ICS content (verify format)
echo "4. Testing ICS Generation...\n";
$ics = generateICS(
    'Test Meeting',
    'Test description with special chars: , ; \n new line',
    $start,
    $end,
    'Test Location',
    'test-uid@smartcrm',
    'organizer@example.com',
    'Organizer Name'
);
echo "   ICS Length: " . strlen($ics) . " chars\n";
echo "   Has BEGIN:VCALENDAR: " . (strpos($ics, 'BEGIN:VCALENDAR') !== false ? 'YES' : 'NO') . "\n";
echo "   Has METHOD:REQUEST: " . (strpos($ics, 'METHOD:REQUEST') !== false ? 'YES' : 'NO') . "\n";
echo "   Has ORGANIZER: " . (strpos($ics, 'ORGANIZER') !== false ? 'YES' : 'NO') . "\n";
echo "   Has DTSTAMP (UTC): " . (preg_match('/DTSTAMP:\d{8}T\d{6}Z/', $ics) ? 'YES' : 'NO') . "\n";
echo "\n";

// Save ICS for manual inspection
file_put_contents(__DIR__ . '/logs/test_invite.ics', $ics);
echo "   ICS saved to logs/test_invite.ics\n\n";

echo "=== All Tests Complete ===\n";