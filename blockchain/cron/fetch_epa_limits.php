<?php
/**
 * fetch_epa_limits.php
 *
 * Downloads EPA's National Primary Drinking Water Regulations (NPDWR) table
 * and the National Secondary Drinking Water Standards (SMCL) table, parses
 * every <table> on each page generically, and writes the raw scraped rows
 * to data/epa_limits_raw.json.
 *
 * This does NOT decide final Limits/ResultType for testparameters - that
 * normalization (unit conversion, aliases, treatment-technique handling)
 * happens in build_epa_limits_cache.php. This script's only job is: fetch
 * the live page, extract "Contaminant" + "MCL" (or "Secondary MCL") columns
 * as literal text, and save them.
 *
 * Intended to run on a schedule (e.g. monthly via cron) since EPA revises
 * these tables only occasionally (new NPDWRs, six-year reviews, etc).
 *
 * Run: php fetch_epa_limits.php
 * Exit code 0 on success, 1 on failure (existing cache is left untouched on failure).
 */

require __DIR__ . '/epa_config.php';
require __DIR__ . '/epa_scraper_lib.php';

if (!is_dir(EPA_DATA_DIR)) { mkdir(EPA_DATA_DIR, 0755, true); }
if (!is_dir(EPA_LOG_DIR)) { mkdir(EPA_LOG_DIR, 0755, true); }

$logFile = EPA_LOG_DIR . '/fetch_epa_limits.log';
$ts = date('Y-m-d H:i:s');
$log = "[$ts] === fetch_epa_limits started ===\n";

function epa_log(&$log, $msg) {
    global $ts;
    $log .= "[$ts] $msg\n";
}

$errors = [];
$allRows = [];

foreach ([EPA_NPDWR_URL, EPA_SMCL_URL] as $url) {
    epa_log($log, "Fetching $url");
    $html = fetchUrl($url, $errors);
    if ($html === null) {
        continue;
    }
    $rows = parseTables($html, $url);
    epa_log($log, "Parsed " . count($rows) . " rows from $url");
    $allRows = array_merge($allRows, $rows);
}

if (empty($allRows)) {
    epa_log($log, "ERROR: no rows scraped from either source. Errors: " . implode(' | ', $errors));
    epa_log($log, "Existing cache (if any) left untouched.");
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    fwrite(STDERR, "fetch_epa_limits failed: " . implode(' | ', $errors) . "\n");
    exit(1);
}

$payload = [
    'fetched_at' => $ts,
    'row_count' => count($allRows),
    'rows' => $allRows,
    'fetch_errors' => $errors,
];

if (file_put_contents(EPA_RAW_CACHE, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
    epa_log($log, "ERROR: failed to write " . EPA_RAW_CACHE);
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    exit(1);
}

epa_log($log, "Wrote " . count($allRows) . " rows to " . EPA_RAW_CACHE);
epa_log($log, "=== fetch_epa_limits finished ===");
file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);

echo json_encode(['success' => true, 'rows' => count($allRows)]) . "\n";
