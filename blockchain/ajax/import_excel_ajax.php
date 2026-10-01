<?php
$config = include('../include/config.php');
$secretKey = $config['SECRET_KEY'];
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['excel_file'])) {
    die(json_encode(['success' => false, 'message' => 'No file uploaded']));
}

$fileTmpPath = $_FILES['excel_file']['tmp_name'];
$fileType = $_FILES['excel_file']['type'];
$allowedTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel'
];

if (!in_array($fileType, $allowedTypes)) {
    die(json_encode(['success' => false, 'message' => 'Invalid file type. Please upload an Excel file.']));
}

try {
    $spreadsheet = IOFactory::load($fileTmpPath);
    $importType = $_POST['import_type'] ?? '';

    if ($importType === 'separate_sheets') {
        $testStandardsSheet = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
        $testParametersSheet = $spreadsheet->getSheet(1)->toArray(null, true, true, true);
        $conn->begin_transaction();
        importTestStandards($testStandardsSheet);
        importTestParameters($testParametersSheet);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Data imported successfully!']);

    } elseif ($importType === 'combined_sheet') {
        $combinedSheet = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
        $conn->begin_transaction();
        importCombinedSheet($combinedSheet);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Data imported successfully!']);

    } elseif ($importType === 'dictionary_lookup') {
        $parametersSheet = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
        $conn->begin_transaction();
        $result = importTestParametersWithDictionary($parametersSheet);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => $result]);

    } elseif ($importType === 'update_limits') {
        $limitsSheet = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
        $conn->begin_transaction();
        $result = updateLimitsFromExcel($limitsSheet);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => $result]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown import type']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
}

// --- Import Functions ---

function importTestStandards($data) {
    global $conn;
    array_shift($data);

    foreach ($data as $row) {
        $standardCode = $conn->real_escape_string($row['A']);
        $standardName = $conn->real_escape_string($row['B']);
        $description = $conn->real_escape_string($row['C']);
        $applicableRegulation = $conn->real_escape_string($row['D']);
        $sm = (int)$row['E'];

        $query = "
            INSERT INTO TestStandards (StandardCode, StandardName, Description, ApplicableRegulation, sm)
            VALUES ('$standardCode', '$standardName', '$description', '$applicableRegulation', $sm)
            ON DUPLICATE KEY UPDATE
                StandardName = VALUES(StandardName),
                Description = VALUES(Description),
                ApplicableRegulation = VALUES(ApplicableRegulation),
                sm = VALUES(sm)";
        if (!$conn->query($query)) {
            throw new Exception("Error inserting/updating TestStandards: " . $conn->error);
        }
    }
}

function importTestParameters($data) {
    global $conn;
    array_shift($data);

    foreach ($data as $row) {
        $parameterName = $conn->real_escape_string($row['A']);
        $standardCode = $conn->real_escape_string($row['B']);
        $limits = $conn->real_escape_string($row['C']);
        $minLimit = is_numeric($row['D']) ? $row['D'] : 'NULL';
        $maxLimit = is_numeric($row['E']) ? $row['E'] : 'NULL';
        $method = $conn->real_escape_string($row['F']);
        $vital = (int)$row['G'];
        $category = strtolower($conn->real_escape_string($row['H']));
        if (!in_array($category, ['microbiological', 'chemical'])) {
            $category = 'chemical';
        }
        $mrl = is_numeric($row['I']) ? $row['I'] : 'NULL';
        $mrlUnit = $conn->real_escape_string($row['J']);
        $unitOfMeasure = $conn->real_escape_string($row['K']);
        $resultType = $conn->real_escape_string($row['L'] ?? '');
        $validResultTypes = ['quantitativeField', 'qualitativeField', 'rangeField'];
        if (!in_array($resultType, $validResultTypes)) {
            $resultType = 'rangeField';
        }

        $standardIDQuery = "SELECT StandardID FROM TestStandards WHERE StandardCode = '$standardCode'";
        $result = $conn->query($standardIDQuery);
        if ($result && $result->num_rows > 0) {
            $standardID = $result->fetch_assoc()['StandardID'];

            $checkBase = $conn->query("SELECT ParameterID FROM baseparameters WHERE ParameterName = '$parameterName'");
            if ($checkBase && $checkBase->num_rows > 0) {
                $baseRow = $checkBase->fetch_assoc();
                $baseID = $baseRow['ParameterID'];
            } else {
                $conn->query("INSERT INTO baseparameters (ParameterName) VALUES ('$parameterName')");
                $baseID = $conn->insert_id;
            }

            $query = "
                INSERT INTO testparameters (BaseID, StandardID, ParameterName, Limits, MinLimit, MaxLimit, Method, Vital, Category, MRL, MRLUnit, UnitOfMeasure, ResultType)
                VALUES ($baseID, $standardID, '$parameterName', '$limits', $minLimit, $maxLimit, '$method', $vital, '$category', $mrl, '$mrlUnit', '$unitOfMeasure', '$resultType')
                ON DUPLICATE KEY UPDATE
                    Limits = VALUES(Limits),
                    MinLimit = VALUES(MinLimit),
                    MaxLimit = VALUES(MaxLimit),
                    Method = VALUES(Method),
                    Vital = VALUES(Vital),
                    Category = VALUES(Category),
                    MRL = VALUES(MRL),
                    MRLUnit = VALUES(MRLUnit),
                    UnitOfMeasure = VALUES(UnitOfMeasure),
                    ResultType = VALUES(ResultType)";
            if (!$conn->query($query)) {
                throw new Exception("Error inserting/updating TestParameters: " . $conn->error);
            }
        }
    }
}

function importCombinedSheet($data) {
    global $conn;
    $standardsMap = [];
    $currentStandardCode = null;
    array_shift($data);

    foreach ($data as $row) {
        $standardName = trim($row['A']);
        $description = trim($row['B']);
        $applicableRegulation = trim($row['C']);
        $parameterName = trim($row['D']);

        if (!empty($standardName)) {
            $currentStandardCode = 'STD' . str_pad((count($standardsMap) + 1), 3, '0', STR_PAD_LEFT);
            $standardsMap[$standardName] = $currentStandardCode;

            $query = "
                INSERT INTO TestStandards (StandardCode, StandardName, Description, ApplicableRegulation)
                VALUES ('$currentStandardCode', '$standardName', '$description', '$applicableRegulation')";
            if (!$conn->query($query)) {
                throw new Exception("Error inserting TestStandards: " . $conn->error);
            }
        }

        if (!empty($parameterName)) {
            $limits = trim($row['E']);
            $minLimit = is_numeric($row['F']) ? $row['F'] : 'NULL';
            $maxLimit = is_numeric($row['G']) ? $row['G'] : 'NULL';
            $method = trim($row['H']);
            $vital = (int)$row['I'];
            $category = strtolower(trim($row['J']));
            if (!in_array($category, ['microbiological', 'chemical'])) {
                $category = 'chemical';
            }
            $mrl = is_numeric($row['K']) ? $row['K'] : 'NULL';
            $mrlUnit = trim($row['L']);
            $unitOfMeasure = trim($row['M']);
            $resultType = trim($row['N'] ?? '');
            $validResultTypes = ['quantitativeField', 'qualitativeField', 'rangeField'];
            if (!in_array($resultType, $validResultTypes)) {
                $resultType = 'rangeField';
            }

            $checkBase = $conn->query("SELECT ParameterID FROM baseparameters WHERE ParameterName = '$parameterName'");
            if ($checkBase && $checkBase->num_rows > 0) {
                $baseRow = $checkBase->fetch_assoc();
                $baseID = $baseRow['ParameterID'];
            } else {
                $conn->query("INSERT INTO baseparameters (ParameterName) VALUES ('$parameterName')");
                $baseID = $conn->insert_id;
            }

            $query = "
                INSERT INTO testparameters (BaseID, StandardID, ParameterName, Limits, MinLimit, MaxLimit, Method, Vital, Category, MRL, MRLUnit, UnitOfMeasure, ResultType)
                VALUES ($baseID, (SELECT StandardID FROM TestStandards WHERE StandardCode = '$currentStandardCode'), '$parameterName', '$limits', $minLimit, $maxLimit, '$method', $vital, '$category', $mrl, '$mrlUnit', '$unitOfMeasure', '$resultType')";
            if (!$conn->query($query)) {
                throw new Exception("Error inserting TestParameters: " . $conn->error);
            }
        }
    }
}

function importTestParametersWithDictionary($data) {
    global $conn;
    array_shift($data);
    
    $insertedCount = 0;
    $updatedCount = 0;
    $createdStandardsCount = 0;
    $createdBaseCount = 0;

    foreach ($data as $row) {
        $aliasName = trim($row['A']);
        if (empty($aliasName)) continue;
        
        $standardName = trim($row['B']);
        $limits = trim($row['C']);
        $minLimit = is_numeric($row['D']) ? $row['D'] : null;
        $maxLimit = is_numeric($row['E']) ? $row['E'] : null;
        $method = trim($row['F']);
        $vital = intval($row['G']);
        $category = strtolower(trim($row['H']));
        if (!in_array($category, ['microbiological', 'chemical'])) {
            $category = 'chemical';
        }
        $mrl = is_numeric($row['I']) ? $row['I'] : null;
        $mrlUnit = trim($row['J']);
        $unitOfMeasure = trim($row['K']);
        $resultType = trim($row['L'] ?? '');
        $validResultTypes = ['quantitativeField', 'qualitativeField', 'rangeField'];
        if (!in_array($resultType, $validResultTypes)) {
            $resultType = 'rangeField';
        }

        $canonicalName = $aliasName;
        $masterParamId = null;

        $stmt = $conn->prepare("SELECT master_parameter_id FROM parameter_dictionary WHERE alias_name = ? LIMIT 1");
        $stmt->bind_param('s', $aliasName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $masterParamId = $result->fetch_assoc()['master_parameter_id'];
            $stmt->close();
            
            $stmtBase = $conn->prepare("SELECT ParameterName FROM baseparameters WHERE ParameterID = ?");
            $stmtBase->bind_param('i', $masterParamId);
            $stmtBase->execute();
            $resultBase = $stmtBase->get_result();
            $baseRow = $resultBase->fetch_assoc();
            $stmtBase->close();
            
            if ($baseRow) {
                $canonicalName = $baseRow['ParameterName'];
            }
        } else {
            $stmt->close();
            
            $stmtInsert = $conn->prepare("INSERT INTO baseparameters (ParameterName) VALUES (?)");
            $stmtInsert->bind_param('s', $aliasName);
            $stmtInsert->execute();
            $masterParamId = $conn->insert_id;
            $stmtInsert->close();
            
            $stmtDict = $conn->prepare("INSERT INTO parameter_dictionary (master_parameter_id, alias_name) VALUES (?, ?)");
            $stmtDict->bind_param('is', $masterParamId, $aliasName);
            $stmtDict->execute();
            $stmtDict->close();
            
            $createdBaseCount++;
        }
        
        if (empty($standardName)) continue;
        
        $stmtStd = $conn->prepare("SELECT StandardID FROM TestStandards WHERE StandardName = ?");
        $stmtStd->bind_param('s', $standardName);
        $stmtStd->execute();
        $resultStd = $stmtStd->get_result();
        $stdRow = $resultStd->fetch_assoc();
        $stmtStd->close();
        
        if (!$stdRow) {
            $stmtCreate = $conn->prepare("INSERT INTO TestStandards (StandardName) VALUES (?)");
            $stmtCreate->bind_param('s', $standardName);
            $stmtCreate->execute();
            $standardID = $conn->insert_id;
            $stmtCreate->close();
            $createdStandardsCount++;
        } else {
            $standardID = $stdRow['StandardID'];
        }
        
        $stmtCheck = $conn->prepare("SELECT ParameterID FROM testparameters WHERE BaseID = ? AND StandardID = ?");
        $stmtCheck->bind_param('ii', $masterParamId, $standardID);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $stmtCheck->close();
        
        if ($resultCheck->num_rows > 0) {
            $stmtUpdate = $conn->prepare("UPDATE testparameters SET Limits = ?, MinLimit = ?, MaxLimit = ?, Method = ?, Vital = ?, Category = ?, MRL = ?, MRLUnit = ?, UnitOfMeasure = ?, ResultType = ?, UpdatedAt = NOW() WHERE BaseID = ? AND StandardID = ? and Customized = 0");
            $stmtUpdate->bind_param('sddsisdsssii', $limits, $minLimit, $maxLimit, $method, $vital, $category, $mrl, $mrlUnit, $unitOfMeasure, $resultType, $masterParamId, $standardID);
            $stmtUpdate->execute();
            $stmtUpdate->close();
            $updatedCount++;
        } else {
            $query = "INSERT INTO testparameters 
                        (ParameterName, StandardID, BaseID, Limits, MinLimit, MaxLimit, Method, Vital, Category, MRL, MRLUnit, UnitOfMeasure, ResultType)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmtIns = $conn->prepare($query);
            $stmtIns->bind_param(
                'siisddsisdsss',
                $canonicalName,
                $standardID,
                $masterParamId,
                $limits,
                $minLimit,
                $maxLimit,
                $method,
                $vital,
                $category,
                $mrl,
                $mrlUnit,
                $unitOfMeasure,
                $resultType
            );
            
            if ($stmtIns->execute()) {
                $insertedCount++;
            } else {
                throw new Exception("Error inserting testparameter: " . $stmtIns->error);
            }
            $stmtIns->close();
        }
    }

    return "$insertedCount parameters inserted. $updatedCount updated (all fields). $createdStandardsCount new teststandards created. $createdBaseCount new baseparameters created.";
}

function updateLimitsFromExcel($data) {
    global $conn;
    array_shift($data);
    
    $updatedCount = 0;
    $notFoundCount = 0;
    $skippedCount = 0;

    foreach ($data as $row) {
        $aliasName = trim($row['A']);
        if (empty($aliasName)) {
            $skippedCount++;
            continue;
        }
        
        $limits = trim($row['B']);
        $minLimit = is_numeric($row['C']) ? $row['C'] : null;
        $maxLimit = is_numeric($row['D']) ? $row['D'] : null;

        $stmt = $conn->prepare("SELECT master_parameter_id FROM parameter_dictionary WHERE alias_name = ? LIMIT 1");
        $stmt->bind_param('s', $aliasName);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows === 0) {
            $notFoundCount++;
            continue;
        }
        
        $masterParamId = $result->fetch_assoc()['master_parameter_id'];
        
        $stmtUpdate = $conn->prepare("UPDATE testparameters SET Limits = ?, MinLimit = ?, MaxLimit = ?, UpdatedAt = NOW() WHERE BaseID = ?");
        $stmtUpdate->bind_param('ssdi', $limits, $minLimit, $maxLimit, $masterParamId);
        $stmtUpdate->execute();
        
        if ($stmtUpdate->affected_rows > 0) {
            $updatedCount += $stmtUpdate->affected_rows;
        } else {
            $notFoundCount++;
        }
        $stmtUpdate->close();
    }

    $msg = "$updatedCount limits updated across testparameters.";
    if ($notFoundCount > 0) {
        $msg .= " $notFoundCount aliases not found in parameter_dictionary.";
    }
    if ($skippedCount > 0) {
        $msg .= " $skippedCount skipped (empty).";
    }
    
    return $msg;
}
