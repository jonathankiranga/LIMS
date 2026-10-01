<?php
// Assume you have a database connection

$config = include('../include/config.php'); // Load the config file
// Database connection
$secretKey = $config['SECRET_KEY'];  // Access the SECRET_KEY from config
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new mysqli($db_host,$db_username,$db_password,$db_name);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}
// Assume you have a sample ID (can be dynamically fetched or passed as parameter)
$sample_id = 'S12345';  // Sample test ID (e.g., 'S12345')

// Retrieve customer details based on the sample ID
$query = "SELECT c.customer_name, c.email, s.test_id FROM customer c
          INNER JOIN samples s ON s.customer_id = c.customer_id
          WHERE s.sample_id = '$sample_id'";

$result = mysqli_query($conn, $query);
$customer = mysqli_fetch_assoc($result);

// Check if customer exists
if ($customer) {
    $customer_name = $customer['customer_name'];
    $customer_email = $customer['email'];
    $test_id = $customer['test_id'];
    $amount_due = '$200';  // Amount due can be calculated or fetched from another source

    // Retrieve the email template for the 'test_approved' event
    $template_query = "SELECT * FROM email_templates WHERE event_name = 'test_approved'";
    $template_result = mysqli_query($conn, $template_query);
    $template = mysqli_fetch_assoc($template_result);

    // Replace placeholders with actual data
    $subject = str_replace('{{customer_name}}', $customer_name, $template['subject']);
    $subject = str_replace('{{sample_id}}', $test_id, $subject);

    $body = str_replace('{{customer_name}}', $customer_name, $template['body']);
    $body = str_replace('{{sample_id}}', $test_id, $body);
    $body = str_replace('{{amount_due}}', $amount_due, $body);

    // Send the email
    $headers = "From: no-reply@yourcompany.com" . "\r\n" .
               "CC: admin@yourcompany.com" . "\r\n" .
               "Content-Type: text/html; charset=UTF-8";

    if (mail($customer_email, $subject, $body, $headers)) {
        echo "Email sent successfully to " . $customer_email;
    } else {
        echo "Error sending email.";
    }
} else {
    echo "Customer or sample not found.";
}

// Close the connection
mysqli_close($conn);
?>
