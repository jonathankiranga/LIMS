// Object to track the current page for each SOP
var currentSopPages = {};

// Object to track the PDF document for each SOP
var pdfDocs = {};

// Function to render the PDF page for a specific SOP
function renderPDF(sopFile, sopID, pageNum) {
    // Check if the PDF document for this SOP is already loaded
    if (!pdfDocs[sopID]) {
        pdfjsLib.getDocument(sopFile).promise.then(function(pdf) {
            pdfDocs[sopID] = pdf; // Store the loaded PDF for this SOP
            currentSopPages[sopID] = 1; // Initialize page number for this SOP
            document.getElementById('page-count-' + sopID).textContent = pdf.numPages;

            renderPage(pdf, sopID, pageNum); // Render the initial page
        }).catch(function(error) {
            console.error('Error loading PDF:', error);
        });
    } else {
        renderPage(pdfDocs[sopID], sopID, pageNum); // Render the page if the PDF is already loaded
    }
}

// Helper function to render the page
function renderPage(pdf, sopID, pageNum) {
    pdf.getPage(pageNum).then(function (page) {
        var scale = 1; // Zoom level
        var viewport = page.getViewport({ scale: scale });

        var canvas = document.getElementById('pdf-canvas-' + sopID);
        var context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        var renderContext = {
            canvasContext: context,
            viewport: viewport
        };
        page.render(renderContext);
        document.getElementById('page-num-' + sopID).textContent = pageNum; // Update page number
    }).catch(function (error) {
        console.error('Error rendering page:', error);
    });
}

// Event listener for the "Next" button
$('.nextsop').on('click', function () {
    const sopFilePath = $(this).data('pdfurl');
    const sopID = $(this).data('sopid');

    // Initialize current page if not already set
    if (!currentSopPages[sopID]) {
        currentSopPages[sopID] = 1; // Default to page 1 if undefined
    }

    console.log('pdfDoc', pdfDocs[sopID]); // Log the loaded PDF document for this SOP
    console.log('currentSopPage', currentSopPages[sopID]); // Log the current page number for this SOP

    // Ensure we're not exceeding the total number of pages
    if (pdfDocs[sopID] && currentSopPages[sopID] < pdfDocs[sopID].numPages) {
        currentSopPages[sopID]++; // Move to the next page for this SOP
        renderPDF(sopFilePath, sopID, currentSopPages[sopID]); // Render the next page
    }
});

// Event listener for the "Previous" button
$('.prevsop').on('click', function () {
    const sopFilePath = $(this).data('pdfurl');
    const sopID = $(this).data('sopid');

    // Initialize current page if not already set
    if (!currentSopPages[sopID]) {
        currentSopPages[sopID] = 1; // Default to page 1 if undefined
    }

    console.log('pdfDoc', pdfDocs[sopID]); // Log the loaded PDF document for this SOP
    console.log('currentSopPage', currentSopPages[sopID]); // Log the current page number for this SOP

    // Ensure we're not going below page 1
    if (currentSopPages[sopID] > 1) {
        currentSopPages[sopID]--; // Move to the previous page for this SOP
        renderPDF(sopFilePath, sopID, currentSopPages[sopID]); // Render the previous page
    }
});
