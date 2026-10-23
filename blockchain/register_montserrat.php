<?php
/**
 * Register the Montserrat Regular TrueType font with TCPDF.
 *
 * Run from the blockchain directory:
 *   php register_montserrat.php
 *
 * This generates TCPDF's font definition files under the configured TCPDF
 * fonts directory. Keep the source TTF and its OFL license in the repository.
 */
require_once __DIR__ . '/vendor/autoload.php';

$fontFile = __DIR__ . '/fonts/Montserrat-Regular.ttf';

if (!is_file($fontFile)) {
    fwrite(STDERR, "ERROR: Font file not found: " . $fontFile . PHP_EOL);
    exit(1);
}

if (!class_exists('TCPDF_FONTS')) {
    fwrite(STDERR, "ERROR: TCPDF_FONTS class was not loaded. Check vendor/autoload.php." . PHP_EOL);
    exit(1);
}

$fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32);

if ($fontName === false) {
    fwrite(STDERR, "ERROR: TCPDF could not register Montserrat." . PHP_EOL);
    exit(1);
}

echo "Montserrat registered successfully." . PHP_EOL;
echo "TCPDF font family: " . $fontName . PHP_EOL;
