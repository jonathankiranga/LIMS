<?php
session_write_close();
session_name('ErpWithCRM');
session_start();

include('../config.php');
$database = $_SESSION['DatabaseName'];
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
mysqli_set_charset($db, 'utf8mb4');

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    case 'get_items':
        $category = $_GET['category'] ?? '';
        if (empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Category required']);
            exit;
        }
        $catEsc = mysqli_real_escape_string($db, $category);
        $res = mysqli_query($db, "SELECT dt.itemcode, sm.descrip, dt.discount_percent, dt.is_active
                                   FROM discounttable dt
                                   LEFT JOIN stockmaster sm ON sm.itemcode = dt.itemcode
                                   WHERE dt.categoryid = '$catEsc'
                                   ORDER BY sm.descrip");
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'itemcode' => trim($r['itemcode']),
                'descrip' => trim($r['descrip'] ?? ''),
                'discount_percent' => (float)$r['discount_percent'],
                'is_active' => (bool)$r['is_active'],
            ];
        }
        echo json_encode($rows);
        break;

    case 'get_available_items':
        $category = $_GET['category'] ?? '';
        if (empty($category)) {
            echo json_encode([]);
            exit;
        }
        $catEsc = mysqli_real_escape_string($db, $category);
        $res = mysqli_query($db, "SELECT sm.itemcode, sm.descrip, sm.barcode
                                   FROM stockmaster sm
                                   WHERE sm.category = '$catEsc'
                                     AND (sm.inactive IS NULL OR sm.inactive = 0)
                                     AND sm.isstock_1 = 1
                                     AND sm.itemcode NOT IN (
                                         SELECT itemcode FROM discounttable WHERE categoryid = '$catEsc'
                                     )
                                   ORDER BY sm.descrip");
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'itemcode' => trim($r['itemcode']),
                'descrip' => trim($r['descrip']),
                'barcode' => trim($r['barcode'] ?? ''),
            ];
        }
        echo json_encode($rows);
        break;

    case 'add_discount':
        $category = $_POST['category'] ?? '';
        $itemcode = $_POST['itemcode'] ?? '';
        $pct = (float)($_POST['discount_percent'] ?? 0);
        $isActive = isset($_POST['is_active']) ? ($_POST['is_active'] === 'true' || $_POST['is_active'] === '1' ? 1 : 0) : 1;
        $userId = mysqli_real_escape_string($db, $_SESSION['UserID'] ?? '');

        if (empty($category) || empty($itemcode)) {
            echo json_encode(['success' => false, 'message' => 'Category and itemcode required']);
            exit;
        }
        $catEsc = mysqli_real_escape_string($db, $category);
        $itemEsc = mysqli_real_escape_string($db, $itemcode);

        $sql = "INSERT INTO discounttable (categoryid, itemcode, discount_percent, is_active, updated_by)
                VALUES ('$catEsc', '$itemEsc', $pct, $isActive, '$userId')
                ON DUPLICATE KEY UPDATE discount_percent = $pct, is_active = $isActive, updated_by = '$userId'";
        if (mysqli_query($db, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Discount saved']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($db)]);
        }
        break;

    case 'delete_discount':
        $category = $_POST['category'] ?? '';
        $itemcode = $_POST['itemcode'] ?? '';
        if (empty($category) || empty($itemcode)) {
            echo json_encode(['success' => false, 'message' => 'Category and itemcode required']);
            exit;
        }
        $catEsc = mysqli_real_escape_string($db, $category);
        $itemEsc = mysqli_real_escape_string($db, $itemcode);

        $sql = "DELETE FROM discounttable WHERE categoryid = '$catEsc' AND itemcode = '$itemEsc'";
        if (mysqli_query($db, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Discount removed']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($db)]);
        }
        break;

    case 'import_csv':
        $category = $_POST['category'] ?? '';
        if (empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Category required']);
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'CSV file upload failed']);
            exit;
        }
        $catEsc = mysqli_real_escape_string($db, $category);
        $userId = mysqli_real_escape_string($db, $_SESSION['UserID'] ?? '');
        $added = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            echo json_encode(['success' => false, 'message' => 'Cannot read uploaded file']);
            exit;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            echo json_encode(['success' => false, 'message' => 'CSV file is empty or invalid']);
            exit;
        }
        // Normalize header column names
        $header = array_map(function($h) {
            return trim(strtolower(str_replace("\xEF\xBB\xBF", '', $h)));
        }, $header);

        $colItemcode = array_search('itemcode', $header);
        $colDiscount = array_search('discount_percent', $header);
        if ($colItemcode === false) {
            fclose($handle);
            echo json_encode(['success' => false, 'message' => 'CSV must have an "itemcode" column']);
            exit;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $itemcode = trim($row[$colItemcode] ?? '');
            if (empty($itemcode)) continue;

            $itemEsc = mysqli_real_escape_string($db, $itemcode);
            $pct = $colDiscount !== false ? (float)($row[$colDiscount] ?? 0) : 0;

            // Validate item exists in stockmaster (optional but helpful)
            $chk = mysqli_query($db, "SELECT itemcode FROM stockmaster WHERE itemcode = '$itemEsc'");
            if (mysqli_num_rows($chk) === 0) {
                $skipped++;
                $errors[] = "Item '$itemcode' not found in stockmaster";
                continue;
            }

            $sql = "INSERT INTO discounttable (categoryid, itemcode, discount_percent, is_active, updated_by)
                    VALUES ('$catEsc', '$itemEsc', $pct, 1, '$userId')
                    ON DUPLICATE KEY UPDATE discount_percent = $pct, is_active = 1, updated_by = '$userId'";
            if (mysqli_query($db, $sql)) {
                $added++;
            } else {
                $skipped++;
                $errors[] = "Failed for '$itemcode': " . mysqli_error($db);
            }
        }
        fclose($handle);

        echo json_encode([
            'success' => true,
            'message' => "Imported: $added added, $skipped skipped",
            'added' => $added,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
