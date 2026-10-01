<?php

// Path to the TCPDF tools directory
$tcpdfToolsPath = dirname(__FILE__) . '/../vendor/tecnickcom/tcpdf/tools';
// Update with the correct path to TCPDF tools

// Path to the Arial TTF font file
$arialFontPath = 'arial.ttf-master/arial.ttf'; // Update with the correct path to your Arial font file

// Command to execute tcpdf_addfont.php
$command = "php $tcpdfToolsPath/tcpdf_addfont.php -i $arialFontPath -o";

// Execute the command
exec($command, $output, $returnVar);

// Check if the command was successful
if ($returnVar === 0) {
    echo "Font successfully added to TCPDF.\n";
    echo implode("\n", $output); // Display output from the command
} else {
    echo "Failed to add font to TCPDF. Please check the paths and try again.\n";
    echo implode("\n", $output); // Display error messages from the command
}
?>

