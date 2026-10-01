
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #e9ecef;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            margin: auto;
            border-top: 5px solid #4CAF50; /* Accent color */
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
            color: #34495e;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, textarea:focus, select:focus {
            border-color: #4CAF50; /* Focus color */
            outline: none;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .col-md-6 {
            flex: 1;
            min-width: 300px; /* Ensures responsiveness */
        }
        .mb-2 {
            margin-bottom: 15px;
        }
        textarea {
            resize: vertical; /* Allow vertical resizing */
        }
    </style>


<h2>Company Registration Form</h2>

<form id="companyForm" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="company_id" id="company_id">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-2"> 
                <label for="company_name">Company Name:</label>
                <input type="text" name="company_name" id="company_name" required>
            </div>
            <div class="mb-2">
                <label for="address1id">Address Line 1:</label>
                <textarea name="address1id" id="address1id" required></textarea>
            </div>
            <div class="mb-2">
                <label for="address2id">Address Line 2:</label>
                <textarea name="address2id" id="address2id" required></textarea>
            </div>
            <div class="mb-2">
                <label for="address3id">Address Line 3:</label>
                <textarea name="address3id" id="address3id" required></textarea>
            </div>
            <div class="mb-2">
                <label for="address4id">Address Line 4:</label>
                <textarea name="address4id" id="address4id" required></textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-2">
                <label for="telephone">Telephone:</label>
                <input type="text" name="telephone" id="telephoneid" required>
            </div>
            <div class="mb-2">
                <label for="email">Email:</label>
                <input type="text" name="email" id="emailid" required>
            </div>
            <div class="mb-2">
                <label for="Batchprefix">Lab Batch Prefix:</label>
                <input type="hidden" id="oldBatchprefix" size="5" maxlength="3">
                <input type="text" id="Batchprefix" size="5" maxlength="3">
            </div>
            <div class="mb-2">
                <label for="authorisation">Authorised By:</label>
                <select name="authorisation" id="authorisationid" required></select>
            </div>
            <div class="mb-2">
                <label for="technician">User Checked By:</label>
                <select name="technician" id="technicianid" required></select>
            </div>
            <div class="mb-2">
                <label for="technician2">User Authorised By:</label>
                <select name="technician2" id="technician2id" required></select>
            </div>
            <div class="mb-2">
                <input type="submit" value="Upload">
            </div>
        </div>
    </div>
</form>

  
<script>
    
    $("#Batchprefix").change(function(){
        var oldref=$("#oldBatchprefix").val();
        var Ref   =$("#Batchprefix").val();
        if(oldref != Ref){
            if(confirm('Do you want to Change the Batch Refix ? This will reset the Lab Batch No To 0')){
                $.post("ajax/updatebatchprefix.php",{
                    LabPrefix:Ref
                 },function(data){
                    toastr.inf(data)
                 });
            }
        }
       
    }); 
    
    function getusers(){
         var usersReq1 = $.ajax({
         url: 'ajax/getusers.php', // Replace with your actual data source
         method: 'GET',
         dataType: 'json',
         success: function(data) {
             // Assuming 'data' is an array of country names
             var countrySelect = $('#technicianid');
             $.each(data, function(user_id, full_name) {
                 countrySelect.append($('<option></option>').attr('value', user_id).text(full_name));
             });
         },
         error: function(xhr, status, error) {
             toastr.error('Error fetching users: ' + status);
         }
     });
     
     
     var usersReq2 = $.ajax({
         url: 'ajax/getusers.php', // Replace with your actual data source
         method: 'GET',
         dataType: 'json',
         success: function(data) {
             // Assuming 'data' is an array of country names
             var countrySelect = $('#authorisationid');
             $.each(data, function(user_id, full_name) {
                 countrySelect.append($('<option></option>').attr('value', user_id).text(full_name));
             });
         },
         error: function(xhr, status, error) {
             toastr.error('Error fetching users: ' + status);
         }
     });
     
         var usersReq3 = $.ajax({
         url: 'ajax/getusers.php', // Replace with your actual data source
         method: 'GET',
         dataType: 'json',
         success: function(data) {
             // Assuming 'data' is an array of country names
             var countrySelect = $('#technician2id');
             $.each(data, function(user_id, full_name) {
                 countrySelect.append($('<option></option>').attr('value', user_id).text(full_name));
             });
         },
         error: function(xhr, status, error) {
             toastr.error('Error fetching users: ' + status);
         }
     });
     
     return $.when(usersReq1, usersReq2, usersReq3);
    }
 
    function loaddata(){
         fetch('ajax/companyupdate.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let row = data.data[0];
                $('#company_id').val(row.company_id);
                $('#company_name').val(row.company_name);
                $('#address1id').val(row.address);
                $('#address2id').val(row.address1);
                $('#address3id').val(row.address2);
                $('#address4id').val(row.address3);
                $('#telephoneid').val(row.telephone);
                $('#emailid').val(row.email);
                $('#technicianid').val(row.technician);
                $('#technician2id').val(row.technician2);
                $('#authorisationid').val(row.authorisation);
          } else {
                toastr.error(data.message ? data.message : 'Failed to fetch company data.');
            }
        })
        .catch(error => console.error('Error:', error));
    }
    // Assuming you have a form element with the ID 'companyForm'
    $('#companyForm').submit(function(event) {
        // Prevent the default form submission
        event.preventDefault();
     // Create a new FormData object
        let formData = new FormData();
       // Append form fields to the FormData object
        formData.append('company_name', $('#company_name').val());
        formData.append('address1', $('#address1id').val());
        formData.append('address2', $('#address2id').val());
        formData.append('address3', $('#address3id').val());
        formData.append('address4', $('#address4id').val());
        formData.append('telephone', $('#telephoneid').val());
        formData.append('email', $('#emailid').val());
        formData.append('userid1', $('#technicianid').val());
        formData.append('userid2', $('#technician2id').val());
        formData.append('authorisation', $('#authorisationid').val());
        formData.append('company_id', $('#company_id').val());
       // Send the form data using fetch
        fetch('ajax/companyupdate.php', {
            method: 'POST',
            body: formData // Pass FormData as the body of the request
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                  loaddata();
                } else {
                    toastr.error(data.message);
                }
            }).catch(error => console.error('Error:', error));
     
    });
   
      function fetchbatchno(seed = null) {
          // Prepare the POST data
          //'GetTempLabrefNo', '10'
          let data = {
              action: 'GetTempLabrefprefix',
              TransType: '10'
          };

          if (seed !== null) {
              data.seed = seed; // Add the seed parameter if provided
          }

           fetch('ajax/getrefferences.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json', // Specify JSON data
              },
              body: JSON.stringify(data), // Send the POST data as JSON
          })
              .then(response => {
                  return response.json(); // Parse the JSON from the response
              })
              .then(data => {
                  // Handle the JSON response data
                  if (data.status === 'success') {
                      console.log('Success:', data.data);
                      $('#oldBatchprefix').val(data.data);
                      $('#Batchprefix').val(data.data);
                 } else {
                      console.error('Error:', data.message);
                  }
              })
              .catch(error => {
                  // Handle any errors
                  console.error('Fetch Error:', error);
              });
        }
 
  getusers().always(function () {
     loaddata();
  });
  fetchbatchno(); 

</script>
