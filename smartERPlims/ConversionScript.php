<?php
/**
 * MSSQL to MySQLi Conversion Helper Script
 * This script helps convert MSSQL syntax to MySQL syntax
 */

// Define patterns for conversion
$conversions = [
    // Convert MSSQL brackets to backticks in SQL strings
    '~\[([a-zA-Z_][a-zA-Z0-9_]*)\]~' => '`$1`',
];

// Files to process (we'll scan PHP files)
$rootDir = dirname(__FILE__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir),
    RecursiveIteratorIterator::SELF_FIRST
);

$phpFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

echo "Found " . count($phpFiles) . " PHP files to process\n";

$convertedFiles = 0;
$totalReplacements = 0;

foreach ($phpFiles as $filepath) {
    $content = file_get_contents($filepath);
    $originalContent = $content;
    
    // Only process files that contain SQL brackets
    if (strpos($content, '[') === false) {
        continue;
    }
    
    $replacementCount = 0;
    foreach ($conversions as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        $replacementCount += substr_count($content, $pattern) - substr_count($newContent, $pattern);
        $content = $newContent;
    }
    
    if ($replacementCount > 0) {
        file_put_contents($filepath, $content);
        $convertedFiles++;
        $totalReplacements += $replacementCount;
        echo "✓ " . str_replace($rootDir, '', $filepath) . " - $replacementCount replacements\n";
    }
}

echo "\n=== Conversion Summary ===\n";
echo "Files converted: $convertedFiles\n";
echo "Total replacements: $totalReplacements\n";
?>
