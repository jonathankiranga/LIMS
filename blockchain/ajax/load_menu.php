<?php
include_once '../db_connection.php';
// Get the role ID from the AJAX request
$roleId = intval($_POST['role_id']);
 // Fetch accessible security IDs for the role
$securityIds = [0];
$menu=[];
$sql = "SELECT security_id FROM role_security WHERE role_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $roleId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $securityIds[] = $row['security_id'];
}
$stmt->close();

// Fetch menu items based on security IDs
$placeholders = implode(',', array_fill(0, count($securityIds), '?'));
$sql = "SELECT 
        m.id, 
        m.title, 
        m.icon, 
        m.url, 
        m.parent_id
    FROM 
        menu_items m
    WHERE 
        m.parent_id IS NULL 
        OR (m.parent_id IS NOT NULL AND m.id IN ($placeholders))
    ORDER BY 
        m.parent_id ASC, 
        m.id ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("SQL Error: " . $conn->error . $sql);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to prepare menu_items query']);
    exit;
}
$stmt->bind_param(str_repeat('i', count($securityIds)), ...$securityIds);
$stmt->execute();
$result = $stmt->get_result();

// Organize menu into hierarchical structure
$menu = [];
$menuMap = [];

while ($row = $result->fetch_assoc()) {
    $menuMap[$row['id']] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'icon' => $row['icon'],
        'url' => $row['url'],
        'submenus' => []
    ];
    
    if ($row['parent_id'] === null) {
        $menu[] = &$menuMap[$row['id']];
    } else {
        $menuMap[$row['parent_id']]['submenus'][] = &$menuMap[$row['id']];
    }
}

$stmt->close();
$conn->close();
header('Content-Type: application/json');

// Return menu as JSON
echo json_encode($menu);
?>



