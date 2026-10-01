<?php
$config = include('../include/config.php'); // Load the config file
// Database connection
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new PDO("mysql:host=$db_host;dbname=$db_name",$db_username,$db_password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if there are any folders
$stmt = $conn->query("SELECT COUNT(*) FROM folders");
$count = $stmt->fetchColumn();

if ($count == 0) {
    // Insert a default root folder
    $stmt = $conn->prepare("INSERT INTO folders (name) VALUES (:name)");
    $defaultFolderName = 'Root Folder';
    $stmt->bindParam(':name', $defaultFolderName);
    $stmt->execute();
}

function buildTree($parentId = null) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM folders WHERE parent_id " . ($parentId ? "= :parent_id" : "IS NULL"));
    if ($parentId) {
        $stmt->bindParam(':parent_id', $parentId);
    }
    $stmt->execute();
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tree = '';
    foreach ($folders as $folder) {
        $tree .= '<li>
            <input type="checkbox" class="folderCheckbox" data-folder-id="' . $folder['id'] . '">
            <i class="fas fa-folder" onclick="openModal(' . $folder['id'] . ')"></i> ' . htmlspecialchars($folder['name']) . '
            <ul>' . buildTree($folder['id']) . '</ul>
        </li>';

        // Fetch and display files in the current folder
        $fileStmt = $conn->prepare("SELECT * FROM files WHERE folder_id = :folder_id");
        $fileStmt->bindParam(':folder_id', $folder['id']);
        $fileStmt->execute();
        $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as $file) {
            $tree .= '<li><input type="checkbox" class="fileCheckbox" data-pdf-id="'.'sopuploads/'. htmlspecialchars($file['file_path']) . '">
                <i class="fas fa-file-pdf"  onclick="openPDF(\'' .'sopuploads/'. htmlspecialchars($file['file_path']) . '\')"></i> ' . htmlspecialchars($file['file_name']) . '
            </li>';
        }
    }
    return $tree;
}

echo buildTree();
?>

