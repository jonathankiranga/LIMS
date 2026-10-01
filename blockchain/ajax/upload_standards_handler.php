<?php

require '../db_connection.php'; // Ensure this connects to your database
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $fileTmpPath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];

    try {
        // Load the Excel file
        $spreadsheet = IOFactory::load($fileTmpPath);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
      // Iterate over rows and save to the database
        $insertCount = 0;
        foreach ($sheetData as $index => $row) {
            if ($index === 1) continue; // Skip header row

            $code = $conn->real_escape_string($row['A']); // Assuming Column A is Code
            $name = $conn->real_escape_string($row['B']); // Column B is Name
            $description = $conn->real_escape_string($row['C']); // Column C is Description
            $regulation = $conn->real_escape_string($row['D']); // Column D is Regulation
            $createdAt = date('Y-m-d H:i:s');
            
                        // Assuming $conn is your MySQLi connection object
            $sql = "INSERT INTO TestStandards (StandardCode, StandardName, Description, ApplicableRegulation, CreatedAt) 
                    VALUES (?, ?, ?, ?, ?)";

            // Prepare the statement
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Bind parameters (s = string, i = integer, d = double, b = blob)
                $stmt->bind_param("sssss", $code, $name, $description, $regulation, $createdAt);
                 // Execute the statement
                if ($stmt->execute()) {
                    $insertCount++;
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => "Error executing query: " . $stmt->error
                    ]);
                }
               // Close the statement
                $stmt->close();
            } else {
               echo json_encode([
                        'success' => false,
                        'message' => "Error preparing query: " . $conn->error
                    ]);
            }

        }

        echo json_encode([
            'success' => true,
            'message' => "$insertCount standards uploaded successfully."
        ]);
        
    } catch (Exception $e) {
        
        echo json_encode([
            'success' => false,
            'message' => 'Error processing the file: ' . $e->getMessage()
        ]);
        
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
}
?>
