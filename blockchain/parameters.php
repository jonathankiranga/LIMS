<link rel="stylesheet" href="css/quickbookslook.css">
<link rel="stylesheet" href="js/quilljs/1.3.7/quill.snow.css">
<link rel="stylesheet" href="css/reducedcss.css">
<link rel="stylesheet" href="css/modalcss.css">
<link rel="stylesheet" href="css/cooltables.css">
<link rel="stylesheet" href="css/typing.css"> 
<link rel="stylesheet" href="css/parametersetup.css"> 
<link rel="stylesheet" href="js/tom-select/tom-select.css" type="text/css"/>

<div class="container mt-5">
    <h3 class="text-center">Parameter Management</h3>
</div> 

    <i class="fas fa-magnifying-glass"></i>
    <select id="standardSearch" style="width: 20%;" placeholder="Search Standards...">
    </select>
      <div class="table-responsive">
        <div><h5><i class="fas fa-cogs"></i> Manage Parameters for <span id="standardName"></span></h5>
             </div>
                <button  data-id="" data-name="" id="addParameterBtn">      
                    <i class="fas fa-plus"></i> Add Parameters 
                </button>
                 <button  data-id=""  data-name="" id="importParametersBtn">
                    <i class="fas fa-file-excel"></i> Import Parameters
                </button>
                
                <table class="table table-sm table-hover" id="parametersTable">
                    <thead>
                        <tr>
                           <th>Parameter Name</th>
                            <th>Unit Of Measure</th>
                            <th>Standard Limits</th>
                            <th>Method</th>
                            <th>MRL </th>
                            <th>MRLUnit</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Parameters will be dynamically added here -->
                    </tbody>
                </table>
            
       </div>
    <nav>
        <ul class="pagination justify-content-center" id="pagination">
            <!-- Pagination buttons will be generated dynamically -->
        </ul>
    </nav>

<!-- Nested Modal for Adding a Parameter -->
<div id="ParameterModalRecord" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="padding: 10px;">
                <div class="modal-header">
                    <h5 class="modal-title">Standard:<span id="addstandardName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-target="#ParameterModalRecord" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex flex-column gap-3" style="padding: 10px;">
                    <form id="ParameterForm">
                          <input type="hidden"  id="ParameterIDForm" name="ParameterID">
                          <input type="hidden"  id="StandardIDForm" name="StandardID">
                          <input type="hidden"  id="GlobalParameterForm" name="GlobalParameterID">
                           <input type="hidden" id="ParameterNameID" name="ParameterName">
                       <!-- Trigger Button -->
                        <div class="mb-3" id="baseContainer">
                            <i class="fas fa-magnifying-glass"></i>
                            <select id="basesearch" placeholder="Search a parameter..." ></select>
                            <label for="ParameterNameForm" class="form-label">Parameter Name</label>
                            <div class="form-control-sm" id="ParameterNameForm" name="ParameterNameForm"></div>
                        </div>
                        <div class="row g-2">
                     <div class="col-md-6">
                        <!-- ParameterName -->
                        <div class="mb-2">
                            <label for="UnitOfMeasure" class="form-label">Unit Of Measure</label>
                            <input type="text" class="form-control" id="unitofmeasure" name="UnitOfMeasure" readonly>
                        </div>
                        <!-- MinLimit -->
                        <div class="mb-2">
                            <label for="MinLimit" class="form-label">Min Limit</label>
                            <input type="number" step="0.01" class="form-control"  id="MinLimit" name="MinLimit" readonly>
                        </div>
                        <!-- MaxLimit -->
                        <div class="mb-2">
                            <label for="MaxLimit" class="form-label">Max Limit</label>
                            <input type="number" step="0.01" class="form-control" id="MaxLimit" name="MaxLimit" readonly>
                        </div>
                        <!-- Limits -->
                        <div class="mb-2">
                            <label for="Limits" class="form-label">Limits</label>
                            <input type="text" class="form-control" id="Limits"  name="Limits" readonly>
                        </div>
                        </div>
                        <div class="col-md-6">
                        <!-- Method -->
                        <div class="mb-2">
                            <label for="Method" class="form-label">Method</label>
                            <input type="text" class="form-control" id="method" name="Method" readonly>
                        </div>
                        
                        <!-- Category -->
                        <div class="mb-2">
                            <label for="Category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="Category" name="Category" readonly>
                        </div>
                        <!-- MRL -->
                        <div class="mb-2">
                            <label for="MRL" class="form-label">MRL</label>
                            <input type="number" step="0.01" class="form-control" id="mrl" name="MRL">
                        </div>
                        <!-- MRLUnit -->
                        <div class="mb-2">
                            <label for="MRLUnit" class="form-label">MRL Unit</label>
                                <select id="mrlunit" class="form-control" name="MRLUnit">
                                </select>
                         </div>
                        </div>
                    </div>
                        <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script src="js/quilljs/1.3.7/quill.min.js"></script>
<script src="js/tom-select/tom-select.complete.min.js" type="text/javascript"></script>
<script>
$(document).ready(function () { 
      
        const TS = new TomSelect('#basesearch', {
        valueField: 'ParameterID',
        labelField: 'ParameterName',
        searchField: 'ParameterName',
        maxItems: 1, // This limits it to ONE selection
        load: function (query, callback) {
          if (!query.length) return callback();
          fetch('ajax/fetchbaseselect2.php?q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(json => {
              callback(json.data); // expects { ParameterID, ParameterName }
            }).catch(() => {
              callback();
            });
        },
        onItemAdd: function (value, item) {
          $('#GlobalParameterForm').val(value);
          $('#ParameterNameID').val(value);
          $('#ParameterNameForm').text(item.innerText);

          // Auto-fill limits from baseparameters
          fetch('ajax/fetchbaseselect2.php?id=' + encodeURIComponent(value))
            .then(function(res) { return res.json(); })
            .then(function(data) {
              if (data.success && data.data) {
                var bp = data.data;
                if (bp.Limits) $('#Limits').val(bp.Limits);
                if (bp.MinLimit) $('#MinLimit').val(bp.MinLimit);
                if (bp.MaxLimit) $('#MaxLimit').val(bp.MaxLimit);
                if (bp.UnitOfMeasure) $('#unitofmeasure').val(bp.UnitOfMeasure);
                if (bp.Method) $('#method').val(bp.Method);
                if (bp.Category) $('#Category').val(bp.Category);
              }
            }).catch(function() {});
        }
      }); 
      
  
   
    
   // Optional: handle the event when a user selects a result.
    $('#standardSearch').select2({
        placeholder: 'Search Standards...',
        allowClear: true,
        minimumInputLength: 1, // begin search after at least 1 character is typed
        ajax: {
            url: 'ajax/fetchteststandards.php', // same endpoint used in your fetchStandards function
            dataType: 'json',
            delay: 250, // debounce time in milliseconds
            data: function (params) {
                // Parameters sent to the server
                return {
                    q: params.term,           // search term
                    page: params.page || 1      // current page number
                };
            },
            processResults: function (data, params) {
                // Parse the results into the format expected by Select2.
                // Here, each result must have at least "id" and "text".
                params.page = params.page || 1;
                var results = data.data.map(function(standard) {
                    return {
                        id: standard.StandardID,
                        // Customize the display text as needed. Here we combine the code and name.
                        text: standard.StandardCode + ' - ' + standard.StandardName,
                        // Optionally include the whole object for later use
                        standardData: standard
                    };
                });
                
                return {
                    results: results,
                   // Indicate whether there are more results available for pagination.
                    pagination: {
                        more: params.page < data.total_pages
                    }
                };
            },
            cache: true
        }
    });
    
    // Optional: handle the event when a user selects a result.
    $('#standardSearch').on('select2:select', function (e) {
        // e.params.data is a single object, not an array.
        var selected = e.params.data;
        console.log("Selected Standard:", selected);
        // If you stored the full details in a property, e.g., standardData:
        var standard = selected.standardData || selected; // Fallback to selected if standardData not available
         fetchParameters(standard.StandardID) ;
         const pagination = $('#pagination');
         pagination.empty();
          
    });
    
    $('#standardSearch').on('select2:clear', function(e) {
        const savedPage = localStorage.getItem('currentPageforteststandards') || 1; // Default to page 1 if no saved page
   //     fetchParameters(savedPage); // Refresh standards table
   });

    // Add Parameter
    $('#addParameterBtn').on('click', function () {
        const standardID = $(this).data('id');
        $('#StandardIDForm').val(standardID);
        $('#ParameterIDForm').val('');
        $('#GlobalParameterForm').val('');
       
        const standardName = $(this).data('name');
        $('#addstandardName').text(standardName);
       // Code to open a form/modal to add a new parameter
        const editModal = new bootstrap.Modal(document.getElementById('ParameterModalRecord'), { backdrop: 'static' });
         editModal.show();
    
    });
    
  

      
    // Apply filter on input change
    $('#filterRow .filter-input').on('keyup', function () {
        const column = $(this).data('column'); // Get the column index
        const filterValue = $(this).val().toLowerCase(); // Get the input value

        $('#standardsTable tbody tr').filter(function () {
            // Show rows where the column text matches the filter value
            $(this).toggle(
                $(this).find('td').eq(column).text().toLowerCase().includes(filterValue)
            );
        });
    });
    
    // Handle Edit Button Click  edit parameters
    $(document).on('click','.editParameter', function () {
         var data = this.dataset;
        if (!TS.options.hasOwnProperty(data.parameterid)) {
            TS.addOption({ value: data.parameterid, label:data.parametername });
        }
        
        TS.setValue(data.parameterid);
        $('#ParameterNameID').val(data.parametername);
        $('#ParameterNameForm').text(data.parametername);
        $('#addstandardName').text($(this).data('standardname'));
        $('#GlobalParameterForm').val($(this).data('matrixid'));
        $('#StandardIDForm').val($(this).data('standardid'));
        $('#ParameterIDForm').val($(this).data('parameterid'));
        $('#MinLimit').val($(this).data('minlimit'));
        $('#MaxLimit').val($(this).data('maxlimit'));
        $('#method').val($(this).data('method'));
        $('#mrl').val($(this).data('mrl'));
        
        const mrlunit = $(this).data('mrlunit') || 'ppm'; 
        $('#mrlunit').val(mrlunit);  //select option
        
        const category = $(this).data('category') || 'chemical'; 
        $('#Category').val(category);
        
        const unitofmeasure = $(this).data('unitofmeasure') || 'ppm'; 
        $('#unitofmeasure').val(unitofmeasure);  //select option
        
        new bootstrap.Modal(document.getElementById('ParameterModalRecord')).show();
    
           
    });

        // Handle Save in Nested Modal
    $('#ParameterForm').submit(function (e) {
            e.preventDefault();

            const uploadButton = $('#ParameterForm button[type="submit"]');
            uploadButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

                const formData = $(this).serializeArray();
             
                $.ajax({
                url: 'ajax/saveparameter.php', // Replace with your actual server endpoint
                type: 'POST', // HTTP method to send data
                data: formData, // Serialized form data
                dataType: 'json', // Expect JSON response from the server
                success: function (response) {
                    // Handle the success response
                    if (response.success) {
                        generalPurposeTypeLine('Parameter saved successfully!'); // Show success message
                        bootstrap.Modal.getOrCreateInstance($('#ParameterModalRecord')[0]).hide(); // Hide the modal
                         fetchParameters($('#StandardIDForm').val())
                    } else {
                        toastr.error('Error: ' + response.message); // Show error message
                    }
                },
                error: function (xhr, status, error) {
                    // Handle errors
                    toastr.error('An error occurred: ' + error + '\nResponse: ' + xhr.responseText);
                },
                complete: function () {
                    console.log('AJAX call completed.'); // Optional: log completion for debugging
                    uploadButton.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload'); // Re-enable button after the request completes
                }
            });
        });
 
   // Ensure the modal is on top when shown
    $(document).on('shown.bs.modal', '.modal', function () {
        const zIndex = 1050 + ($('.modal:visible').length * 10);
        $(this).css('z-index', zIndex);
        $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
    });

    // Restore remaining modals and backdrops when a modal is closed
    $(document).on('hidden.bs.modal', '.modal', function () {
        $('.modal:visible').each(function (index) {
            const zIndex = 1050 + ((index + 1) * 10);
            $(this).css('z-index', zIndex);
        });

        $('.modal-backdrop').not('.modal-stack').each(function (index) {
            const zIndex = 1040 + ((index + 1) * 10);
            $(this).css('z-index', zIndex);
        });

        if (!$('.modal:visible').length) {
            $('.modal-backdrop').remove();
        }
    });

    $('#importParametersBtn').on('click', function () {
           $('#uploadStandardsForm').trigger('reset');
  
           const standardID = $(this).data('id');
           $('#uploadStandardID').val(standardID);
           const standardName = $(this).data('name');
           $('#uploadstandardName').text(standardName);
     
           const editModal = new bootstrap.Modal($('#uploadStandardsModal'));
           editModal.show();
    });

  
 // Handle pagination click
    $(document).on('click', '#pagination .page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
         localStorage.setItem('currentPageforteststandards', page); // Save current page to localStorage
        fetchStandards(page);
    });
    
    // Handle File Upload
    $('#uploadStandardsForm').on('submit', function (e) {
        e.preventDefault();

        const uploadButton = $('#uploadStandardsForm button[type="submit"]');
        uploadButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

        let formData = new FormData(this);
        const filterParameter = $('#uploadStandardID').val(); // Replace this with the actual value
        formData.append('StandardID', filterParameter); // Add the filter parameter to FormData

        // File validation (optional)
        var fileInput = $('#excelFile')[0];
        var file = fileInput.files[0];
        if (file && file.size > 5 * 1024 * 1024) { // 5MB size limit
            toastr.error('File size exceeds the maximum allowed limit of 5MB.');
            uploadButton.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
            return;
        }

        $.ajax({
            url: 'ajax/importparameters.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                console.log(response)
                const res = JSON.parse(response);
                if (res.success) {
                    toastr.succes(res.message);
                    bootstrap.Modal.getOrCreateInstance($('#uploadStandardsModal')[0]).hide();
                    fetchParameters(filterParameter)
                } else {
                    toastr.error('Error: ' + res.message);
                }
            },
            error: function (xhr, status, error) {
                toastr.error('Error: ' + error + '\nStatus: ' + status + '\nResponse: ' + xhr.responseText);
            },
            complete: function() {
                uploadButton.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload'); // Re-enable button after the request completes
            }
        });
    });


    // Delete Parameter
  //////  $(document).on('click', '.deleteParameter', function () {
    $(document).off('click', '.deleteParameter').on('click', '.deleteParameter', function () {
        const parameterID = $(this).data('parameterid');
        if (confirm('Are you sure you want to delete this parameter?')) {
            $.ajax({
                url: 'ajax/deleteparameter.php',
                type: 'POST',
                data: { parameterID },
                dataType: 'json',
                success: function (response) {
                    toastr.succes(response.message);
                    if (response.success) {
                        fetchParameters(response.standardID);
                    }
                },
                error: function () {
                    toastr.error('Failed to delete parameter.');
                }
            });
        }
    });
    
   

 // Function to update the Limits field
function updateLimits() {
    // Limits are now read-only from baseparameters
}
    
// Add event listeners to MinLimit and MaxLimit fields
document.getElementById('MinLimit').addEventListener('input', updateLimits);
document.getElementById('MaxLimit').addEventListener('input', updateLimits);


function populateMRLUnitSelect() {
    
     const mrlUnits = [
    {
        group: "Mass & Amount of Substance",
        options: [
            { value: "g", label: "g (Gram)" },
            { value: "kg", label: "kg (Kilogram)" },
            { value: "mg", label: "mg (Milligram)" },
            { value: "µg", label: "µg (Microgram)" },
            { value: "ng", label: "ng (Nanogram)" },
            { value: "mol", label: "mol (Mole)" },
            { value: "µmol", label: "µmol (Micromole)" },
            { value: "nmol", label: "nmol (Nanomole)" }
        ]
    },
    {
        group: "Volume & Concentration",
        options: [
            { value: "L", label: "L (Liter)" },
            { value: "mL", label: "mL (Milliliter)" },
            { value: "µL", label: "µL (Microliter)" },
            { value: "nL", label: "nL (Nanoliter)" },
            { value: "ppm", label: "ppm (Parts per Million)" },
            { value: "ppb", label: "ppb (Parts per Billion)" },
            { value: "M", label: "M (Molarity, mol/L)" },
            { value: "m", label: "m (Molality, mol/kg)" }
        ]
    },
    {
        group: "Temperature",
        options: [
            { value: "C", label: "°C (Celsius)" },
            { value: "K", label: "K (Kelvin)" },
            { value: "F", label: "°F (Fahrenheit)" }
        ]
    },
    {
        group: "Pressure",
        options: [
            { value: "Pa", label: "Pa (Pascal)" },
            { value: "atm", label: "atm (Atmosphere)" },
            { value: "bar", label: "bar (Bar)" },
            { value: "torr", label: "torr (Torr)" },
            { value: "mmHg", label: "mmHg (Millimeters of Mercury)" }
        ]
    }
];
    
    
    const selectdropdown = document.getElementById('mrlunit');
    // Clear existing options
    selectdropdown.innerHTML = '<option value="">Select a Symbol</option>';
    mrlUnits.forEach(group => {
        const optgroup = document.createElement('optgroup');
        optgroup.label = group.group;

        group.options.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.label;
            optgroup.appendChild(opt);
        });

        selectdropdown.appendChild(optgroup);
    });
}

populateMRLUnitSelect();

// Fetch Parameters with pagination
function fetchParameters(standardID, page = 1) {
    $.ajax({
        url: 'ajax/fetchparameters.php', // Server-side script
        type: 'GET',
        data: { standardID, page }, // Send page param as well
        dataType: 'json',
        success: function (response) {
            const tbody = $('#parametersTable tbody');
            tbody.empty();

            response.data.forEach(parameter => {
                tbody.append(`
                    <tr>
                        <td>${parameter.ParameterName}</td>
                        <td>${parameter.UnitOfMeasure}</td>
                        <td>${parameter.Limits}</td>
                        <td>${parameter.Method}</td>   
                        <td>${parameter.MRL}</td>
                        <td>${parameter.MRLUnit}</td>    
                        <td>${parameter.Category}</td>
                        <td>
                            <button class="btn btn-warning btn-sm editParameter"
                                data-ParameterID="${parameter.ParameterID}"  
                                data-matrixid="${parameter.BaseID}" 
                                data-StandardName="${parameter.StandardName}" 
                                data-StandardID="${parameter.StandardID}"  
                                data-ParameterName="${parameter.ParameterName}"  
                                data-unitofmeasure="${parameter.UnitOfMeasure}"  
                                data-limits="${parameter.Limits}" 
                                data-MinLimit="${parameter.MinLimit}" 
                                data-MaxLimit="${parameter.MaxLimit}"  
                                data-Method="${parameter.Method}"  
                                data-MRL="${parameter.MRL}"  
                                data-MRLUnit="${parameter.MRLUnit}" 
                                data-Category="${parameter.Category}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm deleteParameter" 
                                data-ParameterID="${parameter.ParameterID}"  
                                data-StandardID="${parameter.StandardID}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            // Render pagination for parameters
            renderPagination(response.current_page, response.total_pages, standardID);
        },
        error: function () {
            toastr.error('Failed to fetch parameters.');
        }
    });
}

// Reusable pagination (standards or parameters)
function renderPagination(currentPage, totalPages, standardID = null) {
    const pagination = $('#pagination');
    pagination.empty();

    // Previous
    pagination.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" data-standard="${standardID || ''}">Previous</a>
        </li>
    `);

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        pagination.append(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}" data-standard="${standardID || ''}">${i}</a>
            </li>
        `);
    }

    // Next
    pagination.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" data-standard="${standardID || ''}">Next</a>
        </li>
    `);
}

// Pagination click handler
$(document).on('click', '#pagination .page-link', function (e) {
    e.preventDefault();
    const page = $(this).data('page');
    const standardID = $(this).data('standard');

    if (standardID) {
        // Paginate Parameters
        fetchParameters(standardID, page);
    } else {
        // Paginate Standards
        fetchStandards(page);
    }
});




});

</script>