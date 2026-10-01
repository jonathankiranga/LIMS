<?php
/**
 * build_epa_limits_cache.php
 *
 * Reads data/epa_limits_raw.json (produced by fetch_epa_limits.php) plus
 * epa_limits_overrides.json (hand-maintained aliases + non-federal
 * parameters), normalizes everything into the exact shape fill_null_limits.php
 * expects (the same shape as the old hardcoded $epaLimits array), and writes
 * data/epa_limits_cache.json.
 *
 * Run this right after fetch_epa_limits.php:
 *   php fetch_epa_limits.php && php build_epa_limits_cache.php
 */

require __DIR__ . '/epa_config.php';

if (!is_dir(EPA_LOG_DIR)) { mkdir(EPA_LOG_DIR, 0755, true); }
$logFile = EPA_LOG_DIR . '/build_epa_limits_cache.log';
$ts = date('Y-m-d H:i:s');
$log = "[$ts] === build_epa_limits_cache started ===\n";

if (!file_exists(EPA_RAW_CACHE)) {
    fwrite(STDERR, "Missing " . EPA_RAW_CACHE . " - run fetch_epa_limits.php first\n");
    exit(1);
}
$raw = json_decode(file_get_contents(EPA_RAW_CACHE), true);
if (!$raw || empty($raw['rows'])) {
    fwrite(STDERR, "Raw cache is empty or unreadable\n");
    exit(1);
}

$overrides = file_exists(EPA_OVERRIDES_FILE) ? json_decode(file_get_contents(EPA_OVERRIDES_FILE), true) : [];
$aliases = $overrides['aliases'] ?? [];
$supplemental = $overrides['supplemental'] ?? [];

// Microbiological / qualitative contaminants: even when EPA lists a numeric
// rule (e.g. "5.0%" positive samples per month), labs report these as
// presence/absence per sample, so we force qualitativeField with display '0'.
$qualitativeNames = [
    'total coliforms (including fecal coliform and e. coli)',
    'cryptosporidium',
    'giardia lamblia',
    'legionella',
];

/**
 * Parse a raw EPA cell value like "0.010 as of 01/23/06", "30 ug/L (microgram
 * per liter) as of 12/08/03", "TT (Treatment Technique)", "5.0%", "6.5 - 8.5",
 * "0.05 to 0.2 mg/L", "7 million fibers per liter (MFL)" into a normalized
 * ['display' => ..., 'unit' => ..., 'type' => ...] triple.
 */
function normalizeValue(string $raw): array {
    $text = trim($raw);
    $lower = strtolower($text);

    if ($text === '' || strpos($lower, 'non-corrosive') !== false) {
        return ['display' => '', 'unit' => null, 'type' => null];
    }

    // Pure treatment-technique cells with no number at all.
    if (preg_match('/^\s*TT\b/i', $text) && !preg_match('/\d/', $text)) {
        return ['display' => '', 'unit' => null, 'type' => 'qualitativeField'];
    }

    // Range: "6.5 - 8.5", "0.05 to 0.2", "0.05-0.2"
    if (preg_match('/(-?\d+\.?\d*)\s*(?:-|to)\s*(-?\d+\.?\d*)/i', $text, $m)) {
        $display = $m[1] . '-' . $m[2];
        $unit = detectUnit($text);
        return ['display' => $display, 'unit' => $unit, 'type' => 'quantitativeField'];
    }

    // Single number (handles very small decimals like 0.00000003).
    if (preg_match('/(-?\d+\.?\d*(?:[eE]-?\d+)?)/', $text, $m)) {
        $num = (float) $m[1];
        $unit = detectUnit($text);

        // ug/L (or "microgram per liter") -> convert to mg/L to match the
        // mg/L convention used everywhere else in the table.
        if ($unit === 'ug/L') {
            $num = $num / 1000;
            $unit = 'mg/L';
        }

        // Format without trailing zeros / scientific notation surprises.
        $display = rtrim(rtrim(sprintf('%.10f', $num), '0'), '.');
        if ($display === '' || $display === '-') $display = '0';

        return ['display' => $display, 'unit' => $unit ?: 'mg/L', 'type' => 'quantitativeField'];
    }

    // No number found at all (e.g. some descriptive cell) - can't use it.
    return ['display' => '', 'unit' => null, 'type' => null];
}

function detectUnit(string $text): ?string {
    $checks = [
        '/picocuries per Liter|pCi\s*\/\s*L/i' => 'pCi/L',
        '/millirems? per year/i' => 'millirems/year',
        '/microgram per liter|ug\s*\/\s*L/i' => 'ug/L',
        '/milligrams? per Liter|mg\s*\/\s*L/i' => 'mg/L',
        '/million fibers per liter|MFL/i' => 'MFL',
        '/color units?/i' => 'color units',
        '/threshold odor number|TON/i' => 'TON',
        '/%/' => '%',
        '/unitless/i' => 'unitless',
    ];
    foreach ($checks as $pattern => $unit) {
        if (preg_match($pattern, $text)) return $unit;
    }
    return null;
}

// 1) Build the scraped base, keyed by lowercased contaminant name.
$scraped = [];
foreach ($raw['rows'] as $row) {
    $name = trim($row['name']);
    if ($name === '') continue;
    $key = strtolower($name);
    // Strip a leading "*" or italics artifact some names may retain.
    $key = trim($key, "* \t\n\r\0\x0B");

    $norm = normalizeValue($row['mcl_raw']);

    // Force qualitative treatment for known microbiological contaminants
    // even if EPA's cell had a numeric rule (e.g. coliform's "5.0%").
    foreach ($qualitativeNames as $qn) {
        if (strpos($key, $qn) !== false || $key === $qn) {
            $norm = ['display' => '0', 'unit' => '/100mL', 'type' => 'qualitativeField'];
            break;
        }
    }

    if ($norm['type'] === null && $norm['display'] === '') continue; // nothing usable

    $scraped[$key] = [
        'mcl' => $norm['display'],
        'unit' => $norm['unit'] ?? '',
        'type' => $norm['type'],
        'source' => $row['source'],
    ];
}

epa_build_log($log, count($scraped) . " usable scraped entries");

// 2) Apply aliases: lab-side name -> canonical scraped name.
$final = $scraped;
foreach ($aliases as $aliasKey => $canonicalName) {
    $canonicalKey = strtolower($canonicalName);
    if (isset($scraped[$canonicalKey])) {
        $final[$aliasKey] = $scraped[$canonicalKey];
    } else {
        epa_build_log($log, "WARNING: alias '$aliasKey' -> '$canonicalName' not found in scraped data (page structure may have changed)");
    }
}

// 3) Apply hand-curated supplemental entries (never scraped, always as given).
foreach ($supplemental as $key => $entry) {
    if ($key === '_comment' || !is_array($entry)) continue;
    $final[$key] = [
        'mcl' => $entry['mcl'],
        'unit' => $entry['unit'],
        'type' => $entry['type'],
        'source' => 'manual-override',
    ];
}

if (empty($final)) {
    fwrite(STDERR, "Normalization produced zero usable entries - aborting, not overwriting cache\n");
    exit(1);
}

$out = [
    'built_at' => $ts,
    'source_fetched_at' => $raw['fetched_at'] ?? null,
    'entry_count' => count($final),
    'limits' => $final,
];

if (file_put_contents(EPA_FINAL_CACHE, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
    fwrite(STDERR, "Failed to write " . EPA_FINAL_CACHE . "\n");
    exit(1);
}

epa_build_log($log, "Wrote " . count($final) . " total entries (scraped + aliased + supplemental) to " . EPA_FINAL_CACHE);
epa_build_log($log, "=== build_epa_limits_cache finished ===");
file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);

echo json_encode(['success' => true, 'entries' => count($final)]) . "\n";

function epa_build_log(&$log, $msg) {
    global $ts;
    $log .= "[$ts] $msg\n";
}
