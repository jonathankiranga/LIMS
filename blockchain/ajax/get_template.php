<?php

header('Content-Type: application/json');

$config = include('../include/config.php');
$db_host = $config['DB_HOST'];
$db_name = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$eventName = strtolower(trim((string)($_POST['event_name'] ?? '')));
if ($eventName === '') {
    echo json_encode(['error' => 'Event name is required.']);
    $conn->close();
    exit;
}

$template = null;
foreach (get_template_lookup_keys($eventName) as $lookupKey) {
    $template = fetch_template($conn, $lookupKey);
    if ($template !== null) {
        break;
    }
}

if ($template === null) {
    $template = get_default_template($eventName);
    $template['is_default'] = true;
}

echo json_encode($template);
$conn->close();
exit;

function get_template_lookup_keys($eventName)
{
    $aliases = [
        'sample_subcontracting' => 'sample_subcontrating',
        'sample_subcontrating' => 'sample_subcontracting',
    ];

    $keys = [$eventName];
    if (isset($aliases[$eventName])) {
        $keys[] = $aliases[$eventName];
    }
    $keys[] = 'default_' . $eventName;
    $keys[] = 'default';

    return array_values(array_unique(array_filter($keys, static function ($value) {
        return trim((string)$value) !== '';
    })));
}

function fetch_template($conn, $eventName)
{
    $query = "SELECT subject, body FROM email_templates WHERE event_name = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $eventName);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $template = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!is_array($template)) {
        return null;
    }

    $subject = (string)($template['subject'] ?? '');
    $body = (string)($template['body'] ?? '');
    if (!has_template_content($subject) || !has_template_content($body)) {
        return null;
    }

    return ['subject' => trim($subject), 'body' => trim($body)];
}

function has_template_content($value)
{
    $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = str_replace("\xC2\xA0", ' ', $value); // nbsp
    $plain = trim(strip_tags($value));
    return $plain !== '';
}

function get_default_template($eventName)
{
    $defaults = [
        'test_approved' => [
            'subject' => 'Test Results for Sample {{batchno}}',
            'body' => 'Dear {{customer_name}},<br><br>'
                . 'The test for sample {{batchno}} ({{sample_type}}) was completed.<br><br>'
                . 'Amount due: {{amount_due}}.<br>'
                . 'Please ensure payment is made by the due date: {{due_date}}.<br><br>'
                . 'Best regards,<br>Your Lab Team',
        ],
        'sample_received' => [
            'subject' => 'Sample {{batchno}} Received',
            'body' => 'Dear {{customer_name}},<br><br>'
                . 'We have received your sample with ID {{batchno}}.<br><br>'
                . 'Test Date: {{test_date}}.<br>'
                . 'Sample Type: {{sample_type}}.<br><br>'
                . 'Our team will begin processing it shortly.<br><br>'
                . 'Best regards,<br>Your Lab Team',
        ],
        'sample_subcontrating' => [
            'subject' => 'Request for Subcontracting Test Samples',
            'body' => 'Dear {{contractor_name}},<br><br>'
                . 'We are reaching out to request your assistance in subcontracting the following test samples:<br><br>'
                . '{{sample_list}}<br><br>'
                . 'We kindly ask you to confirm your availability to perform these tests at your earliest convenience.<br><br>'
                . 'Best regards,<br>Your Lab Team',
        ],
        'sample_subcontracting' => [
            'subject' => 'Request for Subcontracting Test Samples',
            'body' => 'Dear {{contractor_name}},<br><br>'
                . 'We are reaching out to request your assistance in subcontracting the following test samples:<br><br>'
                . '{{sample_list}}<br><br>'
                . 'We kindly ask you to confirm your availability to perform these tests at your earliest convenience.<br><br>'
                . 'Best regards,<br>Your Lab Team',
        ],
    ];

    return $defaults[$eventName] ?? [
        'subject' => 'Lab Notification',
        'body' => 'Dear Customer,<br><br>This is an automated notification from the laboratory system.<br><br>Best regards,<br>Your Lab Team',
    ];
}
