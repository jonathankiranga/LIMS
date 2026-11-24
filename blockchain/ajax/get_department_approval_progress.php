<?php
require '../db_connection.php';

header('Content-Type: application/json; charset=utf-8');

/*
 * Final approver dashboard.
 * StatusID 3 means a department has approved its own result rows.
 * A sample is ready only when every result row is at StatusID 3.
 * ParameterID is authoritative; BaseID is a fallback only where no exact
 * ParameterID + StandardID mapping exists.
 */
$sql = "
    SELECT
        x.HeaderID,
        x.SampleID,
        sh.DocumentNo,
        sh.`Date`,
        COUNT(*) AS TotalTests,
        SUM(CASE WHEN x.StatusID = 2 THEN 1 ELSE 0 END) AS PendingResults,
        SUM(CASE WHEN x.StatusID = 3 THEN 1 ELSE 0 END) AS DepartmentApprovedResults,
        SUM(CASE WHEN x.StatusID IS NULL OR x.StatusID NOT IN (2, 3) THEN 1 ELSE 0 END) AS BlockedResults,
        SUM(CASE WHEN x.Department = 'chemical' THEN 1 ELSE 0 END) AS ChemicalTotal,
        SUM(CASE WHEN x.Department = 'chemical' AND x.StatusID = 2 THEN 1 ELSE 0 END) AS ChemicalPending,
        SUM(CASE WHEN x.Department = 'chemical' AND x.StatusID = 3 THEN 1 ELSE 0 END) AS ChemicalApproved,
        SUM(CASE WHEN x.Department = 'chemical' AND (x.StatusID IS NULL OR x.StatusID NOT IN (2, 3)) THEN 1 ELSE 0 END) AS ChemicalBlocked,
        SUM(CASE WHEN x.Department = 'microbiological' THEN 1 ELSE 0 END) AS MicrobiologyTotal,
        SUM(CASE WHEN x.Department = 'microbiological' AND x.StatusID = 2 THEN 1 ELSE 0 END) AS MicrobiologyPending,
        SUM(CASE WHEN x.Department = 'microbiological' AND x.StatusID = 3 THEN 1 ELSE 0 END) AS MicrobiologyApproved,
        SUM(CASE WHEN x.Department = 'microbiological' AND (x.StatusID IS NULL OR x.StatusID NOT IN (2, 3)) THEN 1 ELSE 0 END) AS MicrobiologyBlocked,
        GROUP_CONCAT(DISTINCT CASE WHEN x.Department = 'chemical' AND x.StatusID = 3 THEN u.full_name END ORDER BY u.full_name SEPARATOR ', ') AS ChemicalApprovedBy,
        MAX(CASE WHEN x.Department = 'chemical' AND x.StatusID = 3 THEN x.updated_at END) AS ChemicalApprovedAt,
        GROUP_CONCAT(DISTINCT CASE WHEN x.Department = 'microbiological' AND x.StatusID = 3 THEN u.full_name END ORDER BY u.full_name SEPARATOR ', ') AS MicrobiologyApprovedBy,
        MAX(CASE WHEN x.Department = 'microbiological' AND x.StatusID = 3 THEN x.updated_at END) AS MicrobiologyApprovedAt
    FROM (
        SELECT
            tr.HeaderID,
            tr.SampleID,
            tr.StatusID,
            tr.approvedby,
            tr.updated_at,
            COALESCE(
                (
                    SELECT exact_tp.Category
                    FROM testparameters exact_tp
                    WHERE exact_tp.ParameterID = tr.ParameterID
                      AND exact_tp.StandardID = tr.StandardID
                    LIMIT 1
                ),
                (
                    SELECT CASE
                        WHEN COUNT(DISTINCT base_tp.Category) = 1 THEN MIN(base_tp.Category)
                        ELSE NULL
                    END
                    FROM testparameters base_tp
                    WHERE base_tp.BaseID = tr.ParameterID
                      AND base_tp.StandardID = tr.StandardID
                )
            ) AS Department
        FROM test_results tr
    ) x
    INNER JOIN sample_header sh ON sh.HeaderID = x.HeaderID
    LEFT JOIN users u ON u.user_id = x.approvedby
    GROUP BY x.HeaderID, x.SampleID, sh.DocumentNo, sh.Date
    HAVING SUM(CASE WHEN x.StatusID = 3 THEN 1 ELSE 0 END) > 0
    ORDER BY sh.Date DESC, x.HeaderID DESC
";

$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Unable to load departmental approval progress: ' . $conn->error
    ));
    $conn->close();
    exit;
}

$rows = array();
$summary = array(
    'awaitingDepartments' => 0,
    'readyForFinalApproval' => 0,
    'chemicalCompletedWaiting' => 0,
    'microbiologyCompletedWaiting' => 0
);

while ($row = $result->fetch_assoc()) {
    $row['TotalTests'] = (int)$row['TotalTests'];
    $row['PendingResults'] = (int)$row['PendingResults'];
    $row['DepartmentApprovedResults'] = (int)$row['DepartmentApprovedResults'];
    $row['BlockedResults'] = (int)$row['BlockedResults'];

    foreach (array('Chemical', 'Microbiology') as $prefix) {
        foreach (array('Total', 'Pending', 'Approved', 'Blocked') as $suffix) {
            $key = $prefix . $suffix;
            $row[$key] = isset($row[$key]) ? (int)$row[$key] : 0;
        }
    }

    $row['ChemicalStatus'] = 'Not required';
    if ($row['ChemicalTotal'] > 0) {
        if ($row['ChemicalBlocked'] > 0) {
            $row['ChemicalStatus'] = 'Blocked';
        } elseif ($row['ChemicalPending'] > 0) {
            $row['ChemicalStatus'] = $row['ChemicalApproved'] > 0 ? 'Partially approved' : 'Awaiting approval';
        } else {
            $row['ChemicalStatus'] = 'Approved';
        }
    }

    $row['MicrobiologyStatus'] = 'Not required';
    if ($row['MicrobiologyTotal'] > 0) {
        if ($row['MicrobiologyBlocked'] > 0) {
            $row['MicrobiologyStatus'] = 'Blocked';
        } elseif ($row['MicrobiologyPending'] > 0) {
            $row['MicrobiologyStatus'] = $row['MicrobiologyApproved'] > 0 ? 'Partially approved' : 'Awaiting approval';
        } else {
            $row['MicrobiologyStatus'] = 'Approved';
        }
    }

    $row['ReadyForFinalApproval'] = (
        $row['PendingResults'] === 0 &&
        $row['BlockedResults'] === 0 &&
        $row['DepartmentApprovedResults'] === $row['TotalTests']
    );

    if ($row['ReadyForFinalApproval']) {
        $summary['readyForFinalApproval']++;
    } else {
        $summary['awaitingDepartments']++;
        if ($row['ChemicalTotal'] > 0 && $row['ChemicalPending'] === 0 && $row['ChemicalBlocked'] === 0 && $row['ChemicalApproved'] === $row['ChemicalTotal'] && $row['MicrobiologyPending'] > 0) {
            $summary['chemicalCompletedWaiting']++;
        }
        if ($row['MicrobiologyTotal'] > 0 && $row['MicrobiologyPending'] === 0 && $row['MicrobiologyBlocked'] === 0 && $row['MicrobiologyApproved'] === $row['MicrobiologyTotal'] && $row['ChemicalPending'] > 0) {
            $summary['microbiologyCompletedWaiting']++;
        }
    }

    $rows[] = $row;
}

echo json_encode(array(
    'success' => true,
    'summary' => $summary,
    'results' => $rows
));

$result->free();
$conn->close();
?>