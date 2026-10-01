<?php
session_start();
include '../db_connection.php'; // Contains the DB connection logic.

$userRoleId = $_SESSION['role_id']; // Assume role ID is stored in the session after login.
$currentPage = basename($_SERVER['PHP_SELF']); // Get current page file name.

// Fetch the security ID for the current page.
$query = "SELECT security_id FROM page_security WHERE page_url = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $currentPage);
$stmt->execute();
$result = $stmt->get_result();
$page = $result->fetch_assoc();

if (!$page) {
    die('Page not found or not registered in security table.');
}

$pageSecurityId = $page['security_id'];

// Check if the user's role grants access to this security ID.
$query = "SELECT 1 FROM role_security WHERE role_id = ? AND security_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $userRoleId, $pageSecurityId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Redirect unauthorized users or display an error message.
    header("Location: functions/unauthorized.php");
    exit;
}

// Proceed with the page logic if authorized.
?>
