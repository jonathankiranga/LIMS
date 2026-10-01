<?php
$config = include('include/config.php');
$secretKey = $config['SECRET_KEY'];
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    
    $templateType = $_GET['type'] ?? 'separate_sheets';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    if ($templateType === 'separate_sheets') {
        $sheet->setTitle('Test Standards');
        $sheet->setCellValue('A1', 'StandardCode');
        $sheet->setCellValue('B1', 'StandardName');
        $sheet->setCellValue('C1', 'Description');
        $sheet->setCellValue('D1', 'ApplicableRegulation');
        $sheet->setCellValue('E1', 'SM');
        
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Test Parameters');
        $sheet2->setCellValue('A1', 'ParameterName');
        $sheet2->setCellValue('B1', 'StandardCode');
        $sheet2->setCellValue('C1', 'Limits');
        $sheet2->setCellValue('D1', 'MinLimit');
        $sheet2->setCellValue('E1', 'MaxLimit');
        $sheet2->setCellValue('F1', 'Method');
        $sheet2->setCellValue('G1', 'Vital');
        $sheet2->setCellValue('H1', 'Category');
        $sheet2->setCellValue('I1', 'MRL');
        $sheet2->setCellValue('J1', 'MRLUnit');
        $sheet2->setCellValue('K1', 'UnitOfMeasure');
        $sheet2->setCellValue('L1', 'ResultType');
        
    } elseif ($templateType === 'dictionary_lookup') {
        $sheet->setTitle('Dictionary Lookup');
        $sheet->setCellValue('A1', 'ParameterName');
        $sheet->setCellValue('B1', 'StandardName');
        $sheet->setCellValue('C1', 'Limits');
        $sheet->setCellValue('D1', 'MinLimit');
        $sheet->setCellValue('E1', 'MaxLimit');
        $sheet->setCellValue('F1', 'Method');
        $sheet->setCellValue('G1', 'Vital');
        $sheet->setCellValue('H1', 'Category');
        $sheet->setCellValue('I1', 'MRL');
        $sheet->setCellValue('J1', 'MRLUnit');
        $sheet->setCellValue('K1', 'UnitOfMeasure');
        $sheet->setCellValue('L1', 'ResultType');

    } else {
        $sheet->setCellValue('A1', 'StandardName');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'ApplicableRegulation');
        $sheet->setCellValue('D1', 'ParameterName');
        $sheet->setCellValue('E1', 'Limits');
        $sheet->setCellValue('F1', 'MinLimit');
        $sheet->setCellValue('G1', 'MaxLimit');
        $sheet->setCellValue('H1', 'Method');
        $sheet->setCellValue('I1', 'Vital');
        $sheet->setCellValue('J1', 'Category');
        $sheet->setCellValue('K1', 'MRL');
        $sheet->setCellValue('L1', 'MRLUnit');
        $sheet->setCellValue('M1', 'UnitOfMeasure');
        $sheet->setCellValue('N1', 'ResultType');
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="template_' . $templateType . '.xlsx"');
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Excel Data</title>
    <style>
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Import Test Standards and Parameters from Excel</h2>
        
        <div id="importResult" class="mt-3" style="display:none;"></div>
        
        <div class="mb-4">
            <strong>Download Templates:</strong>
            <a href="import_excel.php?action=download_template&type=separate_sheets" class="btn btn-success btn-sm ms-2">Separate Sheets Template</a>
            <a href="import_excel.php?action=download_template&type=combined_sheet" class="btn btn-success btn-sm ms-2">Combined Sheet Template</a>
            <a href="import_excel.php?action=download_template&type=dictionary_lookup" class="btn btn-success btn-sm ms-2">Dictionary Lookup Template</a>
        </div>
        
        <form id="importForm" class="mt-4">
            <div class="mb-3">
                <label for="excelFile" class="form-label">Select Excel File:</label>
                <input type="file" name="excel_file" id="excelFile" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="importType" class="form-label">Select Import Type:</label>
                <select name="import_type" id="importType" class="form-select" required>
                    <option value="separate_sheets">Separate Sheets (Standards & Parameters)</option>
                    <option value="combined_sheet">Combined Sheet</option>
                    <option value="dictionary_lookup">Dictionary Lookup (Alias to Canonical)</option>
                    <option value="update_limits">Update Limits (Dictionary Lookup)</option>
                </select>
            </div>
            <button type="submit" id="submitBtn" class="btn btn-primary">
                <span id="btnText">Upload and Import</span>
                <span id="btnSpinner" class="spinner" style="display:none; margin-left:8px;"></span>
            </button>
        </form>
        <h3>Excel Layout Formats</h3>
    <h4>Separate Sheets:</h4>
    <p><strong>Sheet 1: Test Standards</strong></p>
    <ul>
        <li><strong>Column A:</strong> Standard Code</li>
        <li><strong>Column B:</strong> Standard Name</li>
        <li><strong>Column C:</strong> Description</li>
        <li><strong>Column D:</strong> Applicable Regulation</li>
        <li><strong>Column E:</strong> SM (blank)</li>
    </ul>
    <p><strong>Sheet 2: Test Parameters</strong></p>
    <ul>
        <li><strong>Column A:</strong> Parameter Name</li>
        <li><strong>Column B:</strong> Standard Code</li>
        <li><strong>Column C:</strong> Limits</li>
        <li><strong>Column D:</strong> Min Limit</li>
        <li><strong>Column E:</strong> Max Limit</li>
        <li><strong>Column F:</strong> Method</li>
        <li><strong>Column G:</strong> Vital (1 or 0)</li>
        <li><strong>Column H:</strong> Category('chemical','microbiological')</li>
        <li><strong>Column I:</strong> MRL</li>
        <li><strong>Column J:</strong> MRL Unit</li>
        <li><strong>Column K:</strong> Unit of Measure</li>
        <li><strong>Column L:</strong> ResultType (quantitativeField/qualitativeField/rangeField)</li>
    </ul>

    <h4>Combined Sheet:</h4>
    <ul>
        <li><strong>Column A:</strong> Standard Name</li>
        <li><strong>Column B:</strong> Description</li>
        <li><strong>Column C:</strong> Applicable Regulation</li>
        <li><strong>Column D:</strong> Parameter Name</li>
        <li><strong>Column E:</strong> Limits(leave blank)</li>
        <li><strong>Column F:</strong> Min Limit</li>
        <li><strong>Column G:</strong> Max Limit</li>
        <li><strong>Column H:</strong> Method</li>
        <li><strong>Column I:</strong> Vital (1 or 0)</li>
        <li><strong>Column J:</strong> Category('chemical','microbiological')</li>
        <li><strong>Column K:</strong> MRL</li>
        <li><strong>Column L:</strong> MRL Unit(leave blank,select option)</li>
        <li><strong>Column M:</strong> Unit of Measure(leave blank,select option)</li>
        <li><strong>Column N:</strong> ResultType (quantitativeField/qualitativeField/rangeField)</li>
    </ul>

    <h4>Dictionary Lookup:</h4>
    <p>Uses <code>parameter_dictionary</code> to resolve alias names (e.g., "flouride", "TDS", "BOD") to canonical <code>baseparameters.ParameterID</code>.</p>
    <ul>
        <li><strong>Column A:</strong> Parameter Name (alias from parameter_dictionary)</li>
        <li><strong>Column B:</strong> Standard Name (exact match; created if not found)</li>
        <li><strong>Column C:</strong> Limits</li>
        <li><strong>Column D:</strong> Min Limit</li>
        <li><strong>Column E:</strong> Max Limit</li>
        <li><strong>Column F:</strong> Method</li>
        <li><strong>Column G:</strong> Vital (1 or 0)</li>
        <li><strong>Column H:</strong> Category('chemical','microbiological')</li>
        <li><strong>Column I:</strong> MRL</li>
        <li><strong>Column J:</strong> MRL Unit</li>
        <li><strong>Column K:</strong> Unit of Measure</li>
        <li><strong>Column L:</strong> ResultType (quantitativeField/qualitativeField/rangeField)</li>
    </ul>
    <p><em>Note: Aliases not found in parameter_dictionary will create a new baseparameter entry automatically. If testparameter exists (same BaseID + StandardName), limits are updated.</em></p>
    
    <h4>Update Limits:</h4>
    <p>Updates <code>testparameters.Limits</code>, <code>MinLimit</code>, and <code>MaxLimit</code> for all testparameters sharing the same <code>BaseID</code>.</p>
    <p><strong>Expected columns:</strong></p>
    <ul>
        <li><strong>Column A:</strong> Parameter Name (alias from parameter_dictionary)</li>
        <li><strong>Column B:</strong> Limits (display string, e.g., "0 - 1.5")</li>
        <li><strong>Column C:</strong> Min Limit (numeric)</li>
        <li><strong>Column D:</strong> Max Limit (numeric)</li>
    </ul>
    <p><em>Note: Uses parameter_dictionary alias lookup. One alias updates all testparameters with the same BaseID.</em></p>
    </div>

    <script>
    document.getElementById('importForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var submitBtn = document.getElementById('submitBtn');
        var btnText = document.getElementById('btnText');
        var btnSpinner = document.getElementById('btnSpinner');
        var resultDiv = document.getElementById('importResult');
        
        submitBtn.disabled = true;
        btnText.textContent = 'Importing...';
        btnSpinner.style.display = 'inline-block';
        resultDiv.style.display = 'none';
        
        fetch('ajax/import_excel_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            resultDiv.style.display = 'block';
            if (data.success) {
                resultDiv.className = 'alert alert-success mt-3';
            } else {
                resultDiv.className = 'alert alert-danger mt-3';
            }
            resultDiv.textContent = data.message;
        })
        .catch(function(err) {
            resultDiv.style.display = 'block';
            resultDiv.className = 'alert alert-danger mt-3';
            resultDiv.textContent = 'Request failed: ' + err.message;
        })
        .finally(function() {
            submitBtn.disabled = false;
            btnText.textContent = 'Upload and Import';
            btnSpinner.style.display = 'none';
        });
    });
    </script>
</body>
</html>
