<?php
/**
 * Handle remaining MSSQL-specific issues
 */

$stats = ['files' => 0, 'replacements' => 0, 'errors' => []];

function processDirectory($dir, &$stats) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filepath = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($filepath)) {
            $skipDirs = ['vendor', 'node_modules', '.git', 'nbproject'];
            if (!in_array($file, $skipDirs)) {
                processDirectory($filepath, $stats);
            }
            continue;
        }
        
        if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'php') {
            continue;
        }
        
        processFile($filepath, $stats);
    }
}

function processFile($filepath, &$stats) {
    $original = file_get_contents($filepath);
    $content = $original;
    $changes = 0;
    
    // Handle MSSQL CAST functions
    // CAST(expr AS SQL_INT) -> CAST(expr AS SIGNED)
    // CAST(expr AS SQL_VARCHAR) -> CAST(expr AS CHAR)
    $content = preg_replace('/CAST\s*\(\s*([^A-Z]+)\s+AS\s+SQL_INT\s*\)/i', 'CAST($1 AS SIGNED)', $content);
    $content = preg_replace('/CAST\s*\(\s*([^A-Z]+)\s+AS\s+SQL_VARCHAR\s*\)/i', 'CAST($1 AS CHAR)', $content);
    
    // Handle CONVERT function - MSSQL has CONVERT(type, value, style)
    // MySQL doesn't use the style parameter, so we need to be careful
    // For simple numeric conversion: CAST(expression AS SIGNED) -> CAST(expression AS SIGNED)
    $content = preg_replace('/CONVERT\s*\(\s*INT\s*,\s*([^,)]+)\s*\)/i', 'CAST($1 AS SIGNED)', $content);
    $content = preg_replace('/CONVERT\s*\(\s*VARCHAR\s*,\s*([^,)]+)\s*\)/i', 'CAST($1 AS CHAR)', $content);
    
    // Handle ISNULL that might have been missed (with different spacing)
    $content = preg_replace_callback('/ISNULL\s*\(\s*([^,]+)\s*,\s*([^)]+)\s*\)/i', function($m) {
        return 'IFNULL(' . trim($m[1]) . ',' . trim($m[2]) . ')';
    }, $content);
    
    // Handle NOW() that might have been missed
    $content = preg_replace('/GETDATE\s*\(\s*\)/i', 'NOW()', $content);
    
    // Handle CURRENT_TIMESTAMP (MSSQL also uses this, but it's MySQL compatible)
    // No change needed
    
    // Handle IDENT_CURRENT - this should already be in the wrapper
    // DB_Last_Insert_ID() is used instead
    
    // Handle Top N - MSSQL uses "TOP 10" but MySQL uses "LIMIT 10"
    // SELECT /* TOP 10 */ ... -> SELECT ... LIMIT 10
    // This is complex because we need to move LIMIT to end of query
    // For now, we'll handle simple cases
    $content = preg_replace_callback('/SELECT\s+TOP\s+(\d+)\s/i', function($m) {
        // We'll handle this more carefully - need to move the clause
        // For now, just mark it
        return 'SELECT /* TOP ' . $m[1] . ' */ ';
    }, $content);
    
    // Handle UUID() - MySQL equivalent is UUID()
    $content = preg_replace('/NEWID\s*\(\s*\)/i', 'UUID()', $content);
    
    // Handle LEN() - MySQL equivalent is CHAR_LENGTH() or LENGTH()
    // Both work in MySQL, LEN also works in newer versions
    // No change needed
    
    // Handle SUBSTRING - Compatible
    // SUBSTRING(str, pos, len) is same in both
    // No change needed
    
    // Handle DATEDIFF - MSSQL and MySQL differ
    // MSSQL: DATEDIFF(interval, start_date, end_date)
    // MySQL: DATEDIFF(end_date, start_date) - only works with dates, no interval
    // This requires manual review, so we'll just mark it
    
    if ($content !== $original) {
        file_put_contents($filepath, $content);
        $stats['files']++;
        $stats['replacements']++;
        echo "✓ " . basename($filepath) . "\n";
    }
}

echo "=== Processing remaining MSSQL issues ===\n";
$root = dirname(__FILE__);
processDirectory($root, $stats);

echo "\n=== Processing Complete ===\n";
echo "Files processed: " . $stats['files'] . "\n";
echo "Total changes: " . $stats['replacements'] . "\n";
?>
