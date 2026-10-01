<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tree Menu with Upload</title>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<style>
#pdf-canvas {
    border: 1px solid black;
    margin-top: 20px;
}
.navigation {
    margin-top: 10px;
}
button {
    margin: 0 5px;
}
.search-bar {
    margin-bottom: 20px;
}
.sop-item {
    border: 1px solid #e2e2e2;
    padding: 20px;
    margin-bottom: 15px;
    background-color: #f9f9f9;
    border-radius: 8px;
}
.sop-item h5 {
    font-size: 18px;
}
.modal-body {
    padding: 2rem;
}
.sop-list {
    display: grid;
    gap: 20px;
}
.upload-success {
    color: green;
    margin-top: 10px;
}
.upload-error {
    color: red;
    margin-top: 10px;
}
.btn-upload {
    margin-top: 15px;
}
.tree {
    list-style-type: none;
}
.tree li {
    margin: 10px 0;
}
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgb(0,0,0);
    background-color: rgba(0,0,0,0.4);
    padding-top: 60px;
}
.modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 40%;
}
#closeModal, #closePDFModal {
    float: right;
    font-size: 20px;
    color: #888;
    cursor: pointer;
}
#closeModal:hover, #closePDFModal:hover {
    color: #000;
}
#pdfViewer {
    border: 1px solid #ddd;
    margin-top: 15px;
    display: block;
    margin: auto;
    width: 100%; /* Full width of the modal */
    height: calc(100% - 50px); /* Full height minus navigation */
}
.navigation {
    text-align: center;
    margin-top: 15px;
}
button {
    margin: 5px;
}
#currentPage {
    display: inline-block;
    margin: 0 10px;
    font-weight: bold;
}
.tree li.selected {
    font-weight: bold;
    color: #3498db;
}
.modal-body {
    padding: 10px;
}
.mb-3 {
    margin-bottom: 15px;
}
label {
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
}
.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 10px;
}
.upload-success {
    color: green;
    font-weight: bold;
}
.upload-error {
    color: red;
    font-weight: bold;
}
</style>
</head>
<body>

<h1>SOP Directory</h1>
<button id="viewPdfBtn">Click to View PDF</button>
<div id="checkuserrole" >
     <small>You can not delete what you create.<br> Tick the checkbox of the parent folder you want to create a subfolder under otherwise just create a Folder</small>
    <div>
    <input type="text" id="newFolderName" placeholder="New Folder Name">
    <button id="createFolderBtn">Create Folder</button>
    </div>
    <div>
    <input type="text" id="newSubFolderName" placeholder="New Subfolder Name">
    <button id="createSubFolderBtn">Create Subfolder</button>
    </div>
</div>


<ul class="tree" id="folderTree">...loading</ul>
 
<!-- Modal for Upload -->
<div id="uploadModal" class="modal">
      <div class="modal-content">
        <span id="closeModal" style="cursor: pointer;">&times;</span>
        <h5 class="modal-title" id="uploadSopModalLabel">Upload SOP Document</h5>
                <div class="modal-body">
                    <form id="uploadSopForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="sopTitle" class="form-label">SOP Title</label>
                            <input type="text" class="form-control" id="sopTitle" required>
                        </div>
                         <div class="mb-3">
                            <label for="sopFile" class="form-label">version</label>
                            <input type="text" class="form-control" id="version" required>
                        </div>
                        <div class="mb-3">
                            <label for="sopFile" class="form-label">Upload SOP File(s)</label>
                            <input type="file" class="form-control" id="pdfFile" accept="application/pdf"  required>
                        </div>
                         <button id="uploadBtn">Upload</button>
                    </form>
                    
                </div>
       
    </div>
</div>

<div id="pdfModal" class="modal">
    <div class="modal-content">
        <span id="closePDFModal" style="cursor: pointer;">&times;</span>
        <canvas id="pdfViewer"></canvas>
        <div>
            <button id="prevPage">Previous</button>
            <span id="currentPage">Page <span id="currentPageNum">1</span> of <span id="totalPages">0</span></span>
            <button id="nextPage">Next</button>
        </div>
    </div>
</div>
 
 <script src="js/2.10.377/pdf.min.js"></script>
 <script>
    window.currentFolderId = null;
    window.userRoleId = localStorage.getItem('role');
    
    if(window.userRoleId==="1"){
        $('#checkuserrole').show();
    }else{
        $('#checkuserrole').hide();
    }
   // Load folders from the database
     window.loadFolders = () => {
        $.ajax({
            url: 'sopuploads/load_folders.php',
            method: 'GET',
            success: function(data) {
                $('#folderTree').html(data);
            }
        });
    };
    
        window.openModal = (folderId) => {
           window.currentFolderId = folderId;
           $('#uploadModal').show();
       };
    
    // Close modal
    $('#closeModal').click(function() {
        $('#uploadModal').hide();
    });

    // Upload PDF
    $('#uploadBtn').click(function(event) {
         event.preventDefault();
        if(window.userRoleId!=="1"){
            toastr.error('Only Admin can upload');
            return;
        }
        const title = document.getElementById('sopTitle');
        const version = document.getElementById('version');
        
        const formData = new FormData();
        formData.append('folderId', currentFolderId);
        formData.append('Title',`${title.value}_${version.value}`);
      
        const fileInput = document.getElementById('pdfFile');
        const files = fileInput.files; // Get the FileList object
        formData.append('pdfFile',  files[0]); // Use an array-like name for multiple files
 
        $.ajax({
            url: 'sopuploads/save_folders.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function() {
                $('#uploadModal').hide();
                window.loadFolders();
            }
        });
    });
    
    // Create a new folder
$('#createFolderBtn').click(function() {
    if(window.userRoleId!=="1"){
        toastr.error('Only Admin can upload');
        return;
    }
    const folderName = $('#newFolderName').val();
    if (folderName) {
        $.ajax({
            url: 'sopuploads/create_folder.php',
            method: 'POST',
            data: { name: folderName, parent_id: null }, // null for root
            success: function() {
                window.loadFolders(); // Reload folders to show the new one
                $('#newFolderName').val(''); // Clear the input
            }
        });
    } else {
        toastr.error('Please enter a folder name.');
    }
});


// Create a new subfolder
$('#createSubFolderBtn').click(function() {
    if(window.userRoleId!=="1"){
        toastr.error('Only Admin can upload');
        return;
    }
    const folderName = $('#newSubFolderName').val();
    const selectedCheckbox = $('.folderCheckbox:checked');

    if (folderName && selectedCheckbox.length > 0) {
        const parentId = selectedCheckbox.data('folder-id'); // Get the selected folder's ID

        $.ajax({
            url: 'sopuploads/create_folder.php',
            method: 'POST',
            data: { name: folderName, parent_id: parentId }, // Use selected parent ID
            success: function() {
                window.loadFolders(); // Reload folders to show the new one
                $('#newSubFolderName').val(''); // Clear the input
            }
        });
    } else {
        toastr.error('Please enter a subfolder name and select a folder.');
    }
});



window.currentPDF = null;
window.pdfDoc = null;
window.currentPage = 1;

$('#viewPdfBtn').click(function() {
     const selectedCheckbox = $('.fileCheckbox:checked');
      const parentId = selectedCheckbox.data('pdf-id'); // Get the selected folder's ID
      if(parentId){
      openPDF(parentId);
    }else {
          toastr.error('Please select a PDF.');
      }
});

window.openPDF = (filePath) => {
    window.currentPDF = filePath;
    $('#pdfModal').show();
    window.renderPDF();
};

window.renderPDF = () => {
    const pdfViewer = document.getElementById('pdfViewer');
    const ctx = pdfViewer.getContext('2d');

    pdfjsLib.getDocument(currentPDF).promise.then(pdf => {
        pdfDoc = pdf;
        document.getElementById('totalPages').innerText = pdf.numPages;
        showPage(currentPage);
    });
};

 

window.showPage = (pageNum) => {
    pdfDoc.getPage(pageNum).then(page => {
        const scale = 1.0;
        const viewport = page.getViewport({ scale });
        pdfViewer.width = viewport.width;
        pdfViewer.height = viewport.height;

        const ctx = pdfViewer.getContext('2d');
        const renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        page.render(renderContext);
    });
};

// Navigation buttons
document.getElementById('prevPage').onclick = () => {
    if (currentPage > 1) {
        currentPage--;
        showPage(currentPage);
    }
};

document.getElementById('nextPage').onclick = () => {
    if (currentPDF && currentPage < pdfDoc.numPages) {
        currentPage++;
        showPage(currentPage);
    }
};

// Close PDF modal
document.getElementById('closePDFModal').onclick = () => {
    $('#pdfModal').hide();
};

    // Add event listener to load folders on page load
    $(document).ready(loadFolders);
       pdfjsLib.GlobalWorkerOptions.workerSrc = 'js/2.10.377/pdf.worker.min.js';
</script>

</body>
</html>