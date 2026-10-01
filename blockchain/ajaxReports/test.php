<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php'); // Adjust the path as necessary

use TCPDF;

class PDFStarter extends TCPDF {
    public function __construct($paperSize = 'A4') {
        parent::__construct('P', PDF_UNIT, $paperSize, true, 'UTF-8', false);
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor('smartERP http://www.facebook.com/smarterp');
        $this->SetTitle('Sample PDF');
        $this->SetMargins(15, 27, 15);
        $this->SetAutoPageBreak(TRUE, 25);
        $this->AddPage();
    }

    public function Footer() {
        // Position at 15 mm from the bottom
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }

    public function outputPDF() {
        // HTML content
        $htmlContent = '
        <h1 style="text-align:center;">Hello, TCPDF!</h1>
        <p>This is a sample PDF document created using TCPDF. It can contain a lot of content that may require multiple pages.</p>
        <p style="color: blue;">You can use <strong>HTML</strong> formatting here!</p>
        <table border="1" cellpadding="5">
            <tr>
                <th>Header 1</th>
                <th>Header 2</th>
            </tr>
            <tr>
                <td>Data 1</td>
                <td>Data 2</td>
            </tr>
            <tr>
                <td>Data 3</td>
                <td>Data 4</td>
            </tr>
            <tr>
                <td>Data 5</td>
                <td>Data 6</td>
            </tr>
            <tr>
                <td>Data 7</td>
                <td>Data 8</td>
            </tr>
            <tr>
                <td>Data 9</td>
                <td>Data 10</td>
            </tr>
            <tr>
                <td>Data 11</td>
                <td>Data 12</td>
            </tr>
            <tr>
                <td>Data 13</td>
                <td>Data 14</td>
            </tr>
            <tr>
                <td>Data 15</td>
                <td>Data 16</td>
            </tr>
        </table>';

        // Write HTML content
        $this->writeHTML($htmlContent, true, false, true, false, '');

        // Output the PDF
        return $this->Output('document.pdf', 'S'); // S = return as string
    }
    
    public function outputPDF2() {
        // Set the font to Arial (Helvetica)
        $this->SetFont('freeserif', '', 11); // Adjust size as needed

        // HTML content
        $htmlContent = '
        <h1 style="text-align:center; color: blue;">Hello, TCPDF with freeserif Font!</h1>
        <p>This is a sample PDF document created using TCPDF with the freeserif font.</p>';

        // Write HTML content
        $this->writeHTML($htmlContent, true, false, true, false, '');

        // Output the PDF
        return $this->Output('document.pdf', 'S'); // S = return as string
    }
}

// Generate the PDF
$pdfStarter = new PDFStarter();
$pdfContent = $pdfStarter->outputPDF2();

// Return the PDF content to AJAX
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="document.pdf"');
echo $pdfContent;
?>