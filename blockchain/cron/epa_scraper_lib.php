<?php
/**
 * Reusable HTML-table scraping functions shared by fetch_epa_limits.php
 * and its test harness.
 */

function fetchUrl(string $url, array &$errors): ?string {
    if (!function_exists('curl_init')) {
        $errors[] = "cURL extension not available";
        return null;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => defined('EPA_USER_AGENT') ? EPA_USER_AGENT : 'LabLimitsSync/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($html === false || $err) {
        $errors[] = "cURL error fetching $url: $err";
        return null;
    }
    if ($httpCode >= 400) {
        $errors[] = "HTTP $httpCode fetching $url";
        return null;
    }
    return $html;
}

function cleanCellText(string $text): string {
    $text = preg_replace('/\[\d+\]\([^)]*\)/', '', $text);
    $text = preg_replace('/\[\d+\]/', '', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function parseTables(string $html, string $sourceUrl): array {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    $tables = $xpath->query('//table');
    $results = [];

    foreach ($tables as $table) {
        $rows = $xpath->query('.//tr', $table);
        if ($rows->length < 2) continue;

        $headerCells = null;
        $headerRowIndex = 0;
        foreach ($rows as $i => $row) {
            $ths = $xpath->query('.//th', $row);
            if ($ths->length > 0) {
                $headerCells = $ths;
                $headerRowIndex = $i;
                break;
            }
        }
        if ($headerCells === null) {
            $headerCells = $xpath->query('.//td', $rows->item(0));
            $headerRowIndex = 0;
        }

        $headers = [];
        foreach ($headerCells as $cell) {
            $headers[] = cleanCellText($cell->textContent);
        }
        if (empty($headers)) continue;

        $nameCol = 0;
        $mclCol = null;
        foreach ($headers as $idx => $h) {
            $hu = strtoupper($h);
            if (strpos($hu, 'CONTAMINANT') !== false) {
                $nameCol = $idx;
            }
            if (strpos($hu, 'MCLG') === false && (strpos($hu, 'MCL') !== false || strpos($hu, 'TT') !== false)) {
                if ($mclCol === null) $mclCol = $idx;
            }
        }
        if ($mclCol === null) continue;

        $rowIndex = -1;
        foreach ($rows as $row) {
            $rowIndex++;
            if ($rowIndex <= $headerRowIndex) continue;

            $cells = $xpath->query('.//td', $row);
            if ($cells->length === 0) continue;

            $name = $cells->item($nameCol) ? cleanCellText($cells->item($nameCol)->textContent) : '';
            $mcl = $cells->item($mclCol) ? cleanCellText($cells->item($mclCol)->textContent) : '';

            if ($name === '') continue;

            $results[] = [
                'name' => $name,
                'mcl_raw' => $mcl,
                'source' => $sourceUrl,
            ];
        }
    }

    return $results;
}
