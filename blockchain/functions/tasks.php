<?php

class EventManager
{
    private $handlers = [];

    public function __construct()
    {
        // Register events and their handlers
        $this->handlers['test_approved'] = [$this, 'handleTestApproved'];
        $this->handlers['sample_received'] = [$this, 'handleSampleReceived'];
        $this->handlers['sample_subcontrating'] = [$this, 'handleSampleSubContract'];
    }

    // Trigger event method
    public function trigger_event($eventName, $data)
    {
        if (!isset($this->handlers[$eventName])) {
            throw new Exception("No handler for event: {$eventName}");
        }
        call_user_func($this->handlers[$eventName], $data);
    }

    public function handleSampleSubContract($data)
    {
        $contractorId = $data['id'] ?? null;
        $eventId = (string)($data['test_id'] ?? '');
        $debtors = $this->get_contractor_details($contractorId);
        $this->log_event($eventId, 'sample_subcontrating', 'pending');

        try {
            if (empty($debtors['email'])) {
                throw new Exception('Contractor email not found.');
            }

            $template = $this->get_email_template('sample_subcontrating');
            $subject = $this->replace_subcontract_placeholders($template['subject'], $debtors, $data);
            $body = $this->replace_subcontract_placeholders($template['body'], $debtors, $data);
            $this->send_email($debtors['email'], $subject, $body);
            $this->update_log_status($eventId, 'success');
        } catch (Exception $e) {
            $this->log_event_error($eventId, 'email_send', $e->getMessage());
            throw $e;
        }
    }

    public function handleSampleReceived($data)
    {
        $customerId = $data['customer_id'] ?? null;
        $eventId = (string)($data['test_id'] ?? '');
        $this->log_event($eventId, 'sample_received', 'pending');

        try {
            $debtors = $this->get_customer_details($customerId);
            if (empty($debtors['email'])) {
                throw new Exception('Customer email not found.');
            }

            $template = $this->get_email_template('sample_received');
            $context = $this->build_context_data($data, $debtors);
            $subject = $this->replace_placeholders($template['subject'], $context);
            $body = $this->replace_placeholders($template['body'], $context);
            $this->send_email($debtors['email'], $subject, $body);
            $this->update_log_status($eventId, 'success');
        } catch (Exception $e) {
            $this->log_event_error($eventId, 'email_send', $e->getMessage());
            throw $e;
        }
    }

    public function handleTestApproved($data)
    {
        $customerId = $data['customer_id'] ?? null;
        $eventId = (string)($data['test_id'] ?? '');
        $this->log_event($eventId, 'test_approved', 'pending');

        try {
            $debtors = $this->get_customer_details($customerId);
            if (empty($debtors['email'])) {
                throw new Exception('Customer email not found.');
            }

            $template = $this->get_email_template('test_approved');
            $context = $this->build_context_data($data, $debtors);
            $subject = $this->replace_placeholders($template['subject'], $context);
            $body = $this->replace_placeholders($template['body'], $context);
            $this->send_email($debtors['email'], $subject, $body);
            $this->update_log_status($eventId, 'success');
        } catch (Exception $e) {
            $this->log_event_error($eventId, 'email_send', $e->getMessage());
            throw $e;
        }
    }

    private function get_contractor_details($id)
    {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM subcontractors WHERE id = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('Failed to prepare contractor query.');
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? $row : [];
    }

    private function get_customer_details($id)
    {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM debtors WHERE itemcode = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('Failed to prepare customer query.');
        }
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? $row : [];
    }

    private function get_email_template($eventName)
    {
        foreach ($this->get_template_lookup_keys($eventName) as $lookupKey) {
            $template = $this->fetch_template_from_db($lookupKey);
            if ($template !== null) {
                return $template;
            }
        }

        return $this->get_default_template($eventName);
    }

    private function get_template_lookup_keys($eventName)
    {
        $eventName = strtolower(trim((string)$eventName));
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

        $keys = array_values(array_unique(array_filter($keys, static function ($value) {
            return trim((string)$value) !== '';
        })));

        return $keys;
    }

    private function fetch_template_from_db($eventName)
    {
        global $conn;
        $stmt = $conn->prepare("SELECT subject, body FROM email_templates WHERE event_name = ? LIMIT 1");
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
        if (!$this->has_template_content($subject) || !$this->has_template_content($body)) {
            return null;
        }

        return ['subject' => trim($subject), 'body' => trim($body)];
    }

    private function has_template_content($value)
    {
        $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\xC2\xA0", ' ', $value); // nbsp
        $plain = trim(strip_tags($value));
        return $plain !== '';
    }

    private function get_default_template($eventName)
    {
        $eventName = strtolower(trim((string)$eventName));
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

        if (isset($defaults[$eventName])) {
            return $defaults[$eventName];
        }

        return [
            'subject' => 'Lab Notification',
            'body' => 'Dear Customer,<br><br>This is an automated notification from the laboratory system.<br><br>Best regards,<br>Your Lab Team',
        ];
    }

    private function get_first_sample_context_by_document($documentNo)
    {
        global $conn;
        $query = "
            SELECT
                sh.Date,
                sh.DocumentNo,
                sh.CustomerName,
                st.SampleID,
                ts.StandardName,
                tp.ParameterName
            FROM sample_header sh
            LEFT JOIN sample_tests st ON st.HeaderID = sh.HeaderID
            LEFT JOIN test_results tr ON tr.TestID = st.TestID
            LEFT JOIN TestStandards ts ON ts.StandardID = tr.StandardID
            LEFT JOIN TestParameters tp
                ON tp.ParameterID = tr.ParameterID
               AND tp.StandardID = tr.StandardID
            WHERE sh.DocumentNo = ?
            ORDER BY st.TestID ASC, tr.resultsID ASC
            LIMIT 1
        ";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("s", $documentNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? $row : [];
    }

    private function build_context_data($inputData, $customerData)
    {
        $documentNo = (string)($inputData['document_no'] ?? $inputData['DocumentNo'] ?? $inputData['test_id'] ?? '');
        $sampleData = $documentNo !== '' ? $this->get_first_sample_context_by_document($documentNo) : [];

        $customerName =
            (string)($inputData['customer_name']
                ?? $inputData['CustomerName']
                ?? $customerData['customer']
                ?? $sampleData['CustomerName']
                ?? '');

        return [
            'test_date' => (string)($inputData['test_date'] ?? $inputData['Date'] ?? $sampleData['Date'] ?? ''),
            'customer_name' => $customerName,
            'batchno' => (string)($inputData['batchno'] ?? $inputData['DocumentNo'] ?? $sampleData['DocumentNo'] ?? $documentNo),
            'sample_type' => (string)($inputData['sample_type'] ?? $inputData['StandardName'] ?? $sampleData['StandardName'] ?? ''),
            'sample_id' => (string)($inputData['sample_id'] ?? $inputData['SampleID'] ?? $sampleData['SampleID'] ?? ''),
            'test_type' => (string)($inputData['test_type'] ?? $inputData['ParameterName'] ?? $sampleData['ParameterName'] ?? ''),
            'amount_due' => (string)($inputData['amount_due'] ?? $inputData['amount'] ?? $customerData['creditlimit'] ?? ''),
            'due_date' => (string)($inputData['due_date'] ?? $inputData['datedue'] ?? ''),
        ];
    }

    private function replace_subcontract_placeholders($text, $debtors, $data)
    {
        $sampleList = '';
        if (!empty($data['sampleTypes']) && is_array($data['sampleTypes'])) {
            foreach ($data['sampleTypes'] as $sample) {
                $sampleList .= '<li>' . htmlspecialchars((string)$sample, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        $replacements = [
            '{{contractor_name}}' => (string)($debtors['name'] ?? ''),
            '{{sample_list}}' => $sampleList,
        ];

        return strtr($text, $replacements);
    }

    private function replace_placeholders($text, $data)
    {
        $replacements = [
            '{{test_date}}' => (string)($data['test_date'] ?? ''),
            '{{customer_name}}' => (string)($data['customer_name'] ?? ''),
            '{{batchno}}' => (string)($data['batchno'] ?? ''),
            '{{sample_type}}' => (string)($data['sample_type'] ?? ''),
            '{{sample_id}}' => (string)($data['sample_id'] ?? ''),
            '{{test_type}}' => (string)($data['test_type'] ?? ''),
            '{{amount_due}}' => (string)($data['amount_due'] ?? ''),
            '{{due_date}}' => (string)($data['due_date'] ?? ''),
        ];

        return strtr($text, $replacements);
    }

    private function log_event($entityId, $eventTriggered, $status)
    {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO event_logs (event_id, event_triggered, status) VALUES (?, ?, ?)");
        if (!$stmt) {
            return;
        }
        $entityId = (string)$entityId;
        $stmt->bind_param("sss", $entityId, $eventTriggered, $status);
        $stmt->execute();
        $stmt->close();
    }

    private function update_log_status($entityId, $status)
    {
        global $conn;
        $stmt = $conn->prepare("UPDATE event_logs SET status = ? WHERE event_id = ? AND status = 'pending'");
        if (!$stmt) {
            return;
        }
        $entityId = (string)$entityId;
        $stmt->bind_param("ss", $status, $entityId);
        $stmt->execute();
        $stmt->close();
    }

    private function log_event_error($entityId, $action, $errorMessage)
    {
        global $conn;
        $stmt = $conn->prepare("UPDATE event_logs SET status = 'failure', error_message = ? WHERE event_id = ? AND status = 'pending'");
        if (!$stmt) {
            return;
        }
        $entityId = (string)$entityId;
        $error = "Failed at {$action}: {$errorMessage}";
        $stmt->bind_param("ss", $error, $entityId);
        $stmt->execute();
        $stmt->close();
    }

    private function set_log_message($entityId, $message)
    {
        global $conn;
        $stmt = $conn->prepare("UPDATE event_logs SET error_message = ? WHERE event_id = ? ORDER BY id DESC LIMIT 1");
        if (!$stmt) {
            return;
        }
        $entityId = (string)$entityId;
        $msg = (string)$message;
        $stmt->bind_param("ss", $msg, $entityId);
        $stmt->execute();
        $stmt->close();
    }

    public function log_event_decision($eventName, $eventId, $status = 'success', $message = null)
    {
        $status = strtolower((string)$status);
        if (!in_array($status, ['pending', 'success', 'failure'], true)) {
            $status = 'success';
        }

        $eventId = (string)$eventId;
        $this->log_event($eventId, (string)$eventName, $status);

        if ($message !== null && trim((string)$message) !== '') {
            $this->set_log_message($eventId, (string)$message);
        }
    }

    private function send_email($email, $subject, $body)
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: no-reply@labworks.local',
        ];

        if (!mail($email, $subject, $body, implode("\r\n", $headers))) {
            throw new Exception("Failed to send email to {$email}");
        }
    }
}

?>
