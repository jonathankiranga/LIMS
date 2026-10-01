<?php
require '../db_connection.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $errormessage = []; // Initialize error messages
    $insertCount = 0;
    
$sql = "INSERT INTO baseparameters (ParameterName) VALUES (?) "
             . " ON DUPLICATE KEY UPDATE ParameterName = VALUES(ParameterName)";
              
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $errormessage[] = ["success" => false, "message" => $conn->error];
        return;
    }
             
     $importdata = [];       
    $skipped = [];
    try {
        $spreadsheet = IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($sheetData as $index => $row) {
            if ($index === 1) continue; // Skip header row
                $mykey=trim($row[0]);
                $importdata[$mykey] = sprintf('%s',$mykey);
        }
        
        foreach ($importdata as $value) {
            // Check if exists first (to track duplicates)
            $checkStmt = $conn->prepare("SELECT ParameterID FROM baseparameters WHERE ParameterName = ?");
            $checkStmt->bind_param('s', $value);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $skipped[] = $value; // Track duplicates
            } else {
                $stmt->bind_param('s', $value);
                if ($stmt->execute()) {
                    $insertCount++;
                } else {
                    $errormessage[] = ["success" => false, "message" => $stmt->error];
                }
            }
            $checkStmt->close();
        }

        $message = "$insertCount parameters imported.";
        if (count($skipped) > 0) {
            $message .= " " . count($skipped) . " duplicates skipped: " . implode(', ', array_slice($skipped, 0, 5));
            if (count($skipped) > 5) $message .= "...";
        }
        
        echo json_encode([
            "success" => $insertCount > 0,
            "message" => $message,
            "imported" => $insertCount,
            "skipped" => count($skipped),
            "duplicates" => $skipped,
            "errors" => $errormessage
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error processing the file: " . $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode(["success" => false, "message" => "No file uploaded or invalid request."]);
    exit;
}
?>
