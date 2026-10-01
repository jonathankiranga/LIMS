<?php
/**
 * Import Parameters from Excel
 * FIXED: Proper baseparameters → testparameters relationship
 * ADDED: ResultType support from baseparameters
 */

require '../db_connection.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile']['tmp_name'];
    $standardID = intval($_POST['StandardID']);
    $errormessage = [];
    $insertCount = 0;
    $baseParamCache = [];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($sheetData as $index => $row) {
            if ($index === 1) continue; // Skip header row

            $paramName = trim($row['A']);
            
            if (empty($paramName)) continue;

            $resultType = trim($row['J'] ?? '');
            $validResultTypes = ['quantitativeField', 'qualitativeField', 'rangeField'];
            if ($resultType && !in_array($resultType, $validResultTypes)) {
                $resultType = '';
            }

            // ============================================
            // STEP 1: Get or Create baseparameter & get its ID
            // ============================================
            if (!isset($baseParamCache[$paramName])) {
                $stmtCheck = $conn->prepare("SELECT ParameterID, ResultType FROM baseparameters WHERE ParameterName = ?");
                $stmtCheck->bind_param("s", $paramName);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                
                if ($rowBase = $resultCheck->fetch_assoc()) {
                    $baseId = $rowBase['ParameterID'];
                    if (empty($rowBase['ResultType']) && $resultType) {
                        $stmtUpdate = $conn->prepare("UPDATE baseparameters SET ResultType = ? WHERE ParameterID = ?");
                        $stmtUpdate->bind_param("si", $resultType, $baseId);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();
                    }
                } else {
                    $stmtInsert = $conn->prepare("INSERT INTO baseparameters (ParameterName, ResultType) VALUES (?, ?)");
                    $stmtInsert->bind_param("ss", $paramName, $resultType);
                    $stmtInsert->execute();
                    $baseId = $conn->insert_id;
                    $stmtInsert->close();
                }
                $stmtCheck->close();
                
                $baseParamCache[$paramName] = $baseId;
            } else {
                $baseId = $baseParamCache[$paramName];
            }

            // ============================================
            // STEP 2: Get ResultType from baseparameters for testparameters
            // ============================================
            $stmtBase = $conn->prepare("SELECT ResultType FROM baseparameters WHERE ParameterID = ?");
            $stmtBase->bind_param("i", $baseId);
            $stmtBase->execute();
            $resultBase = $stmtBase->get_result();
            $baseRow = $resultBase->fetch_assoc();
            $stmtBase->close();
            
            $testResultType = $baseRow['ResultType'] ?? $resultType;

            // ============================================
            // STEP 3: Insert testparameters WITH BaseID and ResultType
            // ============================================
            $sql = "INSERT INTO testparameters 
                    (ParameterName, StandardID, MinLimit, MaxLimit, Method, Vital, Category, MRL, MRLUnit, UnitOfMeasure, BaseID, ResultType) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errormessage[] = ["success" => false, "message" => $conn->error];
                continue;
            }

            $stmt->bind_param(
                'siddsisdssiss',
                $paramName,
                $standardID,
                sprintf("%s", trim($row['B'])),
                sprintf("%s", trim($row['C'])),
                sprintf("%s", trim($row['D'])),
                sprintf("%s", trim($row['E'])),
                sprintf("%s", trim($row['F'])),
                sprintf("%s", trim($row['G'])),
                sprintf("%s", trim($row['H'])),
                sprintf("%s", trim($row['I'])),
                $baseId,
                $testResultType
            );

            if ($stmt->execute()) {
                $insertCount++;
            } else {
                $errormessage[] = ["success" => false, "message" => $stmt->error];
            }
            $stmt->close();
        }

        echo json_encode([
            "success" => $insertCount > 0,
            "message" => $insertCount > 0 ? "$insertCount parameters imported with proper links!" : "No data imported.",
            "errors" => $errormessage
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "No file uploaded."]);
}

?>