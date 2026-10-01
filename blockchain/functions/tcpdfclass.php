<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php'); // Adjust the path as necessary

use TCPDF;

class PDFStarter {
    private $pdf;
    private $pageFormats = [
        'A4' => ['P', 595, 842, 30, 30, 40, 30],
        'A4_Landscape' => ['L', 842, 595, 30, 30, 40, 30],
        'A5' => ['P', 421, 595, 30, 30, 40, 30],
        'A5_Landscape' => ['L', 595, 421, 30, 30, 40, 30],
        'A3' => ['P', 842, 1190, 50, 50, 50, 40],
        'A3_Landscape' => ['L', 1190, 842, 50, 50, 50, 40],
        'Letter' => ['P', 612, 792, 36, 36, 36, 36],
        'Letter_Landscape' => ['L', 792, 612, 36, 36, 36, 36],
        'Legal' => ['P', 612, 1008, 36, 36, 36, 36],
        'Legal_Landscape' => ['L', 1008, 612, 36, 36, 36, 36],
    ];

    public function __construct($paperSize = 'A4') {
        // Set default paper size if not provided
        if (!isset($this->pageFormats[$paperSize])) {
            $paperSize = 'A4'; // Default to A4
        }

        list($orientation, $width, $height, $topMargin, $bottomMargin, $leftMargin, $rightMargin) = $this->pageFormats[$paperSize];

        // Create the TCPDF object
        $this->pdf = new TCPDF($orientation, PDF_UNIT, $paperSize, true, 'UTF-8', false);
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor('smartERP http://www.facebook.com/smarterp');
        $this->pdf->SetTitle('Sample PDF');
        $this->pdf->SetMargins($leftMargin, $topMargin, $rightMargin);
        $this->pdf->SetAutoPageBreak(TRUE, $bottomMargin);
        
        // Add a page
        $this->pdf->AddPage();
        $this->pdf->cMargin = 0;
    }

    public function getPDF() {
        return $this->pdf;
    }
}

// Example usage
try {
    $pdfStarter = new PDFStarter('A4'); // Set paper size to A4
    $pdf = $pdfStarter->getPDF(); // Get the TCPDF object

    // Set content using the $pdf variable
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'Hello, TCPDF!', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 12);
    // Start creating HTML content
    $html = '<div style="width: 100%; font-family: Arial, sans-serif; font-size: 12px;">';
    $html .= '<h2 style="text-align: center; color: #3498db;">LIMS Report</h2>';

    // Add header information
    $html .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="width: 25%; padding: 5px;"><strong>Date:</strong></td>
                    <td style="width: 25%; padding: 5px;">' . date('Y-m-d') . '</td>
                    <td style="width: 25%; padding: 5px;"><strong>Batch No:</strong></td>
                    <td style="width: 25%; padding: 5px;">Batch123</td>
                </tr>
                <tr>
                    <td style="width: 25%; padding: 5px;"><strong>Customer ID:</strong></td>
                    <td style="width: 25%; padding: 5px;">CUST001</td>
                    <td style="width: 25%; padding: 5px;"><strong>Customer Name:</strong></td>
                    <td style="width: 25%; padding: 5px;">ABC Corp</td>
                </tr>
              </table>';

    // Add sample information table
    $html .= '<h3 style="margin-bottom: 10px;">Sample Information</h3>';
    $html .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                <thead>
                    <tr style="background-color: #3498db; color: white;">
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Sample ID</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Test Name</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">MRL Result</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Result Status</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Range Result</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">User Name</th>
                    </tr>
                </thead>
                <tbody>';

    // Generate table rows dynamically
    foreach ($samples as $sample) {
        $html .= '<tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $sample['id'] . '</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $sample['test'] . '</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $sample['result'] . '</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $sample['status'] . '</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $sample['range'] . '</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . $sample['user'] . '</td>
                  </tr>';
    }

    $html .= '</tbody></table>';
    $html .= '</div>';

    // Write the dynamically generated HTML content to the PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    // Output the PDF document
    $pdf->Output('document.pdf', 'I'); // Change 'I' to 'D' for download
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>