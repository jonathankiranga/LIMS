<?php
require __DIR__ . '/../db_connection.php';
require __DIR__ . '/epa_config.php';

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) { mkdir($logDir, 0755, true); }
$logFile = $logDir . '/fill_null_limits.log';
$ts = date('Y-m-d H:i:s');
$log = "[$ts] === fill_null_limits started ===\n";

/**
 * Load the EPA limits table. Preference order:
 *  1. The auto-refreshed cache written by fetch_epa_limits.php + build_epa_limits_cache.php.
 *  2. A tiny built-in safety net (below) if the cache is missing/corrupt, so
 *     this script never hard-fails just because cron hasn't run yet.
 */
function loadEpaLimits(array &$log_ref, string &$ts_ref): array {
    if (file_exists(EPA_FINAL_CACHE)) {
        $decoded = json_decode(file_get_contents(EPA_FINAL_CACHE), true);
        if (is_array($decoded) && !empty($decoded['limits'])) {
            $age = isset($decoded['built_at']) ? $decoded['built_at'] : 'unknown';
            $log_ref[] = "[$ts_ref] Loaded " . count($decoded['limits']) . " limits from cache (built_at=$age)";
            if (isset($decoded['built_at']) && strtotime($decoded['built_at']) < strtotime('-90 days')) {
                $log_ref[] = "[$ts_ref] WARNING: EPA limits cache is over 90 days old - check the fetch_epa_limits cron job";
            }
            return $decoded['limits'];
        }
        $log_ref[] = "[$ts_ref] WARNING: cache file exists but is empty/corrupt, falling back to built-in minimal set";
    } else {
        $log_ref[] = "[$ts_ref] WARNING: no EPA limits cache found at " . EPA_FINAL_CACHE . " - run fetch_epa_limits.php + build_epa_limits_cache.php. Falling back to built-in minimal set";
    }

    // Minimal built-in fallback so a broken/late cron job doesn't leave this
    // script doing nothing at all. Not meant to be comprehensive.
    return [
        'lead' => ['mcl' => '0.015', 'unit' => 'mg/L', 'type' => 'quantitativeField'],
        'copper' => ['mcl' => '1.3', 'unit' => 'mg/L', 'type' => 'quantitativeField'],
        'nitrate' => ['mcl' => '10', 'unit' => 'mg/L', 'type' => 'quantitativeField'],
        'total coliform' => ['mcl' => '0', 'unit' => '/100mL', 'type' => 'qualitativeField'],
        'ph' => ['mcl' => '6.5-8.5', 'unit' => 'pH units', 'type' => 'quantitativeField'],
    ];
}

$logLines = [];
$epaLimits = loadEpaLimits($logLines, $ts);
foreach ($logLines as $line) { $log .= $line . "\n"; }

$sql = "SELECT ParameterID, ParameterName, UnitOfMeasure, ResultType 
        FROM testparameters 
        WHERE (Limits IS NULL OR Limits = '' OR MinLimit IS NULL OR MaxLimit IS NULL OR ResultType IS NULL)
        AND ParameterName IS NOT NULL AND ParameterName != ''
        ORDER BY ParameterID ASC";
$result = $conn->query($sql);
if (!$result) {
    $log .= "[$ts] ERROR: " . $conn->error . "\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    exit(1);
}
$total = $result->num_rows;
$updated = 0;
$notFound = 0;
$log .= "[$ts] Found $total parameters needing limits or ResultType\n";

while ($row = $result->fetch_assoc()) {
    $pid = $row['ParameterID'];
    $pname = trim($row['ParameterName']);
    $existingUnit = trim($row['UnitOfMeasure'] ?? '');
    $existingRT = trim($row['ResultType'] ?? '');
    $lower = strtolower($pname);

    $match = null;

    if (isset($epaLimits[$lower])) {
        $match = $epaLimits[$lower];
    } else {
        $cleaned = preg_replace('/\s*,?\s*(max|min|total|free|dissolved)\s*$/i', '', $lower);
        $cleaned = preg_replace('/\s*\(.*?\)\s*$/', '', $cleaned);
        $cleaned = trim($cleaned);
        if (isset($epaLimits[$cleaned])) {
            $match = $epaLimits[$cleaned];
        } else {
            foreach ($epaLimits as $key => $val) {
                if (strpos($lower, $key) !== false || strpos($key, $lower) !== false) {
                    $match = $val;
                    break;
                }
            }
            if (!$match) {
                $words = preg_split('/[\s,]+/', $cleaned);
                $sigWords = array_filter($words, function($w) {
                    return strlen($w) >= 3 && !in_array($w, ['and','the','for','with','total','dissolved','free']);
                });
                foreach ($sigWords as $word) {
                    foreach ($epaLimits as $key => $val) {
                        if (strpos($key, $word) !== false) {
                            $match = $val;
                            break 2;
                        }
                    }
                }
            }
        }
    }

    if ($match) {
        $limitsDisplay = $match['mcl'];
        $minLimit = 0;
        $maxLimit = ($limitsDisplay !== '' && is_numeric($limitsDisplay)) ? floatval($limitsDisplay) : null;
        $unit = $match['unit'] ?: $existingUnit;
        $resultType = $match['type'];

        $setClauses = [];
        $bindTypes = '';
        $bindValues = [];

        if ($limitsDisplay !== '') {
            $setClauses[] = 'Limits = ?';
            $bindTypes .= 's';
            $bindValues[] = $limitsDisplay;
        }
        if ($maxLimit !== null) {
            $setClauses[] = 'MinLimit = ?';
            $setClauses[] = 'MaxLimit = ?';
            $bindTypes .= 'dd';
            $bindValues[] = $minLimit;
            $bindValues[] = $maxLimit;
        }
        if ($unit !== '') {
            $setClauses[] = 'UnitOfMeasure = COALESCE(NULLIF(?, ""), UnitOfMeasure)';
            $bindTypes .= 's';
            $bindValues[] = $unit;
        }
        if ($resultType !== null && $existingRT === '') {
            $setClauses[] = 'ResultType = ?';
            $bindTypes .= 's';
            $bindValues[] = $resultType;
        }

        if (empty($setClauses)) {
            $notFound++;
            $log .= "[$ts] NO ACTION: [$pid] $pname (no updatable fields)\n";
            continue;
        }

        $setSQL = implode(', ', $setClauses);
        $bindValues[] = $pid;
        $bindTypes .= 'i';

        $stmt = $conn->prepare("UPDATE testparameters SET $setSQL WHERE ParameterID = ?  and  Customized = 0");
        $stmt->bind_param($bindTypes, ...$bindValues);
        if ($stmt->execute()) {
            $updated++;
            $changes = [];
            if ($limitsDisplay !== '') $changes[] = "Limits='$limitsDisplay'";
            if ($maxLimit !== null) $changes[] = "Max=$maxLimit";
            if ($unit !== '') $changes[] = "Unit='$unit'";
            if ($resultType !== null && $existingRT === '') $changes[] = "Type='$resultType'";
            $log .= "[$ts] UPDATED: [$pid] $pname => " . implode(', ', $changes) . "\n";
        } else {
            $log .= "[$ts] ERROR: [$pid] $pname => " . $stmt->error . "\n";
        }
        $stmt->close();
    } else {
        $notFound++;
        $log .= "[$ts] NOT FOUND: [$pid] $pname\n";
    }
}

$log .= "[$ts] === Done: $updated updated, $notFound not found ===\n";
file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
echo json_encode(['success' => true, 'total' => $total, 'updated' => $updated, 'not_found' => $notFound]) . "\n";
