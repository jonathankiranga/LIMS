
<div  class="container" >
   <div  class="table-responsive" style="max-height:80vh;">
        <h2>Customers Table</h2>
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="search" id="customerSearch" class="form-control" placeholder="Search customers from database">
            </div>
        </div>
        <button id="addstandardmethodBtn" class="btn btn-secondary mb-3">
            <i class="fas fa-plus"></i>Add Customers
        </button>
        <table id="customersTable" class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <td>Name</td>
                    <td>Address</th>
                    <td>Address 2</th>
                    <td>city</th>
                    <td>Country</th>
                    <td>Telephone No</th>
                    <td>Alt Contact</th>
                    <td>email</th>
                    <td>Block Account</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data rows go here -->
            </tbody>
        </table>

    </div>
    <nav>
        <ul class="pagination justify-content-center" id="customerspagination">
            <!-- Pagination buttons will be generated dynamically -->
        </ul>
        </nav>
 </div>
    
 <div id="addstandardmethods" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                  <i class="fas fa-upload"></i>Add Customers
                </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-target="#addstandardmethods" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addcustomers">
                   <form id="customerForm">
                   <input type="hidden" name="itemcode" id="itemcode">
                    <table class="table table-sm"><tbody>
                     <tr>
                      <td valign="top">
                       <table>
                           <tbody>
                               <tr><td>Name</td><td><input type="text" name="customer" id="customer" maxlength="50" required="required"></td></tr>
                               <tr><td>Address</td><td><input type="text" name="company" id="company" maxlength="50"></td></tr>
                               <tr><td>Address 2</td><td><input type="text" name="postcode" id="postcode" maxlength="100"></td></tr>
                               <tr><td>city</td><td><input type="text" name="city" maxlength="50" id="city"></td></tr>
                               <tr><td>Country</td><td><select name="country" id="countrySelect"></select></td></tr>
                               <tr><td>Telephone No</td><td><input type="text" name="phone" maxlength="15" id="phone"></td></tr>
                               <tr><td>Alt Contact</td><td><input type="text" name="altcontact" maxlength="100" id="altcontact"></td></tr>
                               <tr><td>email</td><td><input type="text" name="email" id="email" maxlength="100" required="required" pattern="[a-z0-9!#$%&amp;'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*"></td></tr>
                               <tr><td>Block Account</td><td><select name="inactive" id="inactive"><option value="0">No</option><option value="1">Yes</option></select></td></tr>
                           </tbody>
                          </table>
                         </td>
                       </tr>
                      </tbody>
                    </table>
                   <button type="submit" class="btn btn-primary">Save Customer</button>
                  </form>
                </div>
            </div>
        </div>
    </div>
  </div>
     
   
<script>
       var fetchStandards = function(page, search) {
        $.ajax({
            url: 'ajax/fetchcustomers.php', // Create a separate PHP script to fetch all standards
            type: 'GET',
            data: { page, q: customerSearchTerm },
            dataType: 'json',
            success: function (response) {
                const tbody = $('#customersTable tbody');
                tbody.empty();
                 if (!response.data.length) {
                    tbody.append(`
                        <tr>
                            <td colspan="11" class="text-center">No customers found.</td>
                        </tr>
                    `);
                 } else {
                     response.data.forEach((standard, index) => {
                         let count=((response.current_page-1) * 50);
                        tbody.append(`
                            <tr>
                                <td>${index+1+count}</td>
                                <td>${standard.customer}</td>
                                <td>${standard.company}</td>
                                <td>${standard.postcode}</td>
                                <td>${standard.city}</td>
                                <td>${standard.country}</td>
                                 <td>${standard.phone}</td>
                                 <td>${standard.altcontact}</td>
                                 <td>${standard.email}</td>
                                 <td>${standard.inactive}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm editStandard" data-id="${standard.itemcode}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-danger btn-sm deleteStandard" data-id="${standard.itemcode}"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                        `);
                    });
                 }
       
                
                 // Handle pagination
                const pagination = $('#customerspagination');
                pagination.empty();

                // Add "Previous" link
                pagination.append(`
                    <li class="page-item ${response.current_page === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${response.current_page - 1}">Previous</a>
                    </li>
                `);

                pagination.append(`
                    <li class="page-item disabled">
                        <a class="page-link" href="#">Page ${response.current_page} of ${response.total_pages}</a>
                    </li>
                `);

                // Add "Next" link
                pagination.append(`
                    <li class="page-item ${response.current_page === response.total_pages ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${response.current_page + 1}">Next</a>
                    </li>
                `);

            },
            error: function () {
                toastr.error('Failed to fetch test standards.');
            }
            
            
        });
    };
  // Load standards on page load
        fetchStandards(1);
   // Handle pagination click
        $(document).on('click','#customerspagination .page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (!page) {
                return;
            }
            fetchStandards(page, customerSearchTerm);
        });

        $(document).on('input', '#customerSearch', function () {
            const searchValue = $(this).val().trim();
            clearTimeout(customerSearchTimer);
            customerSearchTimer = setTimeout(function () {
                fetchStandards(1, searchValue);
            }, 300);
        });
         
         $(document).off('submit','#customerForm').on('submit','#customerForm', function(e) {
                e.preventDefault(); // Prevent default form submission
                  $.ajax({
                    url: 'ajax/saveCustomer.php', // Your server-side script to handle the save
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                          fetchStandards(1, customerSearchTerm);
                          bootstrap.Modal.getOrCreateInstance($('#addstandardmethods')[0]).hide();
                    },
                    error: function() {
                        toastr.error('Error adding customer. Please try again.');
                    }
                });
            });
    
        $('#addstandardmethodBtn').on('click', function () {
            bootstrap.Modal.getOrCreateInstance($('#addstandardmethods')[0]).show();
        });

        $(document).on('click', '.deleteStandard', function () {
              const itemcode = $(this).data('id');
               $.ajax({
                   url: 'ajax/saveCustomer.php', // Replace with your actual data source
                   method: 'POST',
                   dataType: 'json',
                   data:{itemcode:itemcode,deleteaccount:true},
                   success: function(data) {
                        toastr.info(data);
                        fetchStandards(1, customerSearchTerm);
                   },
                   error: function(xhr, status, error) {
                       toastr.error('Error fetching debtor:'+ error.message);
                   }
               });



          });  

        $(document).on('click', '.editStandard', function () {
              const itemcode = $(this).data('id');
               $.ajax({
                   url: 'ajax/findcustomers.php', // Replace with your actual data source
                   method: 'POST',
                   dataType: 'json',
                   data:{itemcode:itemcode},
                   success: function(data) {
                        if(data.success){
                           const row = data.data[0];
                           console.log(row);
                           $('#itemcode').val(row.itemcode);
                           $('#customer').val(row.customer);
                           $('#company').val(row.company);
                           $('#postcode').val(row.postcode);
                           $('#city').val(row.city);
                           $('#countrySelect').val(row.country);
                           $('#phone').val(row.phone);
                           $('#altcontact').val(row.altcontact);
                           $('#email').val(row.email);
                           $('#inactive').val(row.inactive);

                           bootstrap.Modal.getOrCreateInstance($('#addstandardmethods')[0]).show();
                        }
                   },
                   error: function(xhr, status, error) {
                       toastr.error('Error fetching debtor:'+ error.message);
                   }
               });



          });

        $.ajax({
          url: 'jsonfiles/Countriesarray.php',
          method: 'GET',
          dataType: 'json',
          success: function(data) {
              var countrySelect = $('#countrySelect');
              $.each(data, function(index, country) {
                  countrySelect.append($('<option></option>').attr('value', country).text(country));
              });
          },
          error: function(xhr, status, error) {
              console.error('Error fetching countries:', error);
          }
      });
      
        function closeModal() {
           bootstrap.Modal.getOrCreateInstance($('#addstandardmethods')[0]).hide();
        }

        function saveEmployee() {
            // Logic to save employee data
            closeModal();
        }
        
</script>
