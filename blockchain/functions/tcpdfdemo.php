<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php'); // Adjust the path as necessary

$sampleID = isset($_GET['id']) ? intval($_GET['id']) : 'unknown';
// Check if the request is for the PDF content
if (isset($_GET['id'])) {

    // Create the PDF document
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Hello, this is your LIMS Report!');

    // Output the PDF as a string
    $pdfContent = $pdf->Output('lims_report.pdf', 'S'); // 'S' returns the raw content

    // Serve the PDF content
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="lims_report.pdf"');
    echo $pdfContent;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Viewer</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
              width:100hw;
            background-color: #f4f4f9;
        }
        #pdf-viewer {
             height: 100%;
            width:100%;
            border: 1px solid #ccc;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div id="pdf-viewer">
        <canvas id="pdf-canvas"></canvas>
    </div>

    <script>
        const url = '<?php echo $_SERVER['PHP_SELF']; ?>?pdf=1'; // PHP script serving the PDF

        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

        // Load the PDF document
        pdfjsLib.getDocument(url).promise.then(function (pdf) {
            // Fetch the first page
            pdf.getPage(1).then(function (page) {
                const viewport = page.getViewport({ scale: 1.5 }); // Adjust scale as needed
                const canvas = document.getElementById('pdf-canvas');
                const context = canvas.getContext('2d');

                canvas.width = viewport.width;
                canvas.height = viewport.height;

                const renderContext = {
                    canvasContext: context,
                    viewport: viewport,
                };
                page.render(renderContext);
            });
        });
    </script>
</body>
</html>
