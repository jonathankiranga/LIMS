<?php
require '../db_connection.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'all';
$imported = 0;
$skipped = 0;
$errors = [];

// =============================================
// MODE 1: Import from laboratorystandards.php
// =============================================
if ($mode === 'local' || $mode === 'all') {
    require '../ajaxReports/laboratorystandards.php';

    $stmt = $conn->prepare("INSERT IGNORE INTO standard_limits
        (parameter_name, method, limits_display, min_limit, max_limit, unit, source, standard_name, sample_type_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($laboratorystandards as $entry) {
        $paramName = trim($entry['Parameter']);
        $method = trim($entry['Method'] ?? '');
        $limits = trim($entry['Limits'] ?? '');
        $minLimit = floatval($entry['MinimumLimit'] ?? 0);
        $maxLimit = floatval($entry['MaximumLimit'] ?? 0);
        $unit = trim($entry['Units'] ?? '');
        $sampleTypeId = $entry['sampletypeid'] ?? '';

        // Derive standard name from sample type ID
        $standardName = 'KS/EAS Standard (Sample Type: ' . $sampleTypeId . ')';

        // Skip entries with no useful limits
        if ($limits === '' || $limits === 'X2') {
            $minLimit = null;
            $maxLimit = null;
        }

        $stmt->bind_param('sssddssss',
            $paramName, $method, $limits, $minLimit, $maxLimit, $unit, $standardName, $standardName, $sampleTypeId
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $imported++;
            } else {
                $skipped++;
            }
        } else {
            $errors[] = $stmt->error;
        }
    }
    $stmt->close();
}

// =============================================
// MODE 2: Fetch from EPA Envirofacts API
// =============================================
if ($mode === 'epa' || $mode === 'all') {
    $epaUrl = 'https://data.epa.gov/efservice/SDWIS_SYSTEM/CONTAMINANT_NAME/MCL_VALUE/JSON';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $epaUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $epaData = json_decode($response, true);
        if (is_array($epaData)) {
            // EPA returns rows like: {CONTAMINANT_NAME, MCL_VALUE, UNIT, HEALTH_BASED}
            // Deduplicate by contaminant name
            $seen = [];
            $stmt = $conn->prepare("INSERT IGNORE INTO standard_limits
                (parameter_name, method, limits_display, min_limit, max_limit, unit, source, standard_name, sample_type_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($epaData as $row) {
                $contaminant = trim($row['CONTAMINANT_NAME'] ?? '');
                $mcl = trim($row['MCL_VALUE'] ?? '');
                $unit = trim($row['UNIT'] ?? '');

                if ($contaminant === '' || $mcl === '' || $mcl === '0' || strtolower($mcl) === 'no mcl') {
                    continue;
                }

                $key = strtolower($contaminant);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $mclVal = floatval($mcl);
                $minLimit = null;
                $maxLimit = $mclVal > 0 ? $mclVal : null;

                $method = 'EPA SDWIS';
                $source = 'US EPA Safe Drinking Water Information System';
                $standardName = 'US EPA MCL';
                $sampleType = '';

                $stmt->bind_param('sssddssss',
                    $contaminant, $method, $mcl, $minLimit, $maxLimit, $unit, $source, $standardName, $sampleType
                );

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $imported++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $errors[] = $stmt->error;
                }
            }
            $stmt->close();
        }
    } else {
        $errors[] = 'EPA API request failed (HTTP ' . $httpCode . ')';
    }
}

// =============================================
// MODE 3: Import from Excel file upload
// =============================================
if ($mode === 'excel' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $stmt = $conn->prepare("INSERT IGNORE INTO standard_limits
            (parameter_name, method, limits_display, min_limit, max_limit, unit, source, standard_name, sample_type_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $header = [];
        foreach ($sheetData as $index => $row) {
            if ($index === 1) {
                $header = array_map('strtolower', array_map('trim', $row));
                continue;
            }

            // Map columns by header name
            $paramName = '';
            $method = '';
            $limits = '';
            $minLimit = null;
            $maxLimit = null;
            $unit = '';

            foreach ($row as $col => $val) {
                $colLower = strtolower(trim($col));
                $valTrim = trim($val);
                if (strpos($colLower, 'parameter') !== false || strpos($colLower, 'name') !== false) {
                    $paramName = $valTrim;
                } elseif (strpos($colLower, 'method') !== false) {
                    $method = $valTrim;
                } elseif (strpos($colLower, 'limit') !== false && strpos($colLower, 'min') === false && strpos($colLower, 'max') === false) {
                    $limits = $valTrim;
                } elseif (strpos($colLower, 'min') !== false) {
                    $minLimit = floatval($valTrim);
                } elseif (strpos($colLower, 'max') !== false) {
                    $maxLimit = floatval($valTrim);
                } elseif (strpos($colLower, 'unit') !== false) {
                    $unit = $valTrim;
                }
            }

            if ($paramName === '') {
                continue;
            }

            $source = 'Excel Import';
            $standardName = 'Excel Import';
            $sampleType = '';

            $stmt->bind_param('sssddssss',
                $paramName, $method, $limits, $minLimit, $maxLimit, $unit, $source, $standardName, $sampleType
            );

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } else {
                $errors[] = $stmt->error;
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        $errors[] = 'Excel parse error: ' . $e->getMessage();
    }
}

echo json_encode([
    'success' => true,
    'imported' => $imported,
    'skipped' => $skipped,
    'errors' => $errors,
    'message' => "$imported entries imported. $skipped duplicates skipped."
]);
