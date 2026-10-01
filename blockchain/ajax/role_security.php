<?php
require '../db_connection.php'; // Ensure this connects to your database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save Role Security
    $role_id = $_POST['role_id'];
    $security_ids = isset($_POST['security_ids']) ? $_POST['security_ids'] : [];
   // Clear existing role_security records for the role
    $conn->query("DELETE FROM role_security WHERE role_id = $role_id");
   // Insert new security_ids
      
        foreach ($security_ids as $security_id) {
            $stmt = $conn->prepare("INSERT INTO role_security (role_id, security_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $role_id, $security_id);
            $stmt->execute();
        }
   
   $response=['success' => true, 'message' => 'Saved Roles.'];
  
}
 

// Handle the GET request to fetch the data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch roles from the database
    $roles_result = $conn->query("SELECT id, role_name FROM roles");
    $roles = [];
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }

    // Fetch menu items
    $menu_items_result = $conn->query("SELECT id, title, parent_id FROM menu_items");
    $menu_items = [];
    while ($row = $menu_items_result->fetch_assoc()) {
        $menu_items[] = $row;
    }

    // Fetch role security relationships
    $role_security_result = $conn->query("SELECT role_id, security_id FROM role_security");
    $role_security = [];
    while ($row = $role_security_result->fetch_assoc()) {
        $role_security[] = $row;
    }

    // Combine and return the data as JSON
    $response = [
        'roles' => $roles,
        'menu_items' => $menu_items,
        'role_security' => $role_security
    ];

    
}

header('Content-Type: application/json');
    echo json_encode($response);

?>

