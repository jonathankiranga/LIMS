<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIMS ERP Homepage</title>
    <link rel="icon" type="image/x-icon" href="logos/favicon.ico?v=2">
    <link rel="stylesheet" href="bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="css/homepage.css" type="text/css">
    <link rel="stylesheet" href="js/toastr/toastr.min.css">
    <link rel="stylesheet" href="css/select2.min.css">
  
 </head>
<body>
    <!-- Tooltip Preview -->
<div id="paramPreview" class="d-none param-preview"></div>
  
    <div>
    <div class="alert alert-info banner-container" role="alert">
      <div>
          <strong>User Logged In :</strong><span id="username"></span> | 
          <strong>Software Version: </strong><span id="version"></span>
          <strong>Next Batch No: </strong><span id="nextbatchno"></span>
      </div>
        <ul>
            <a href="usermanual.php" target="_blank" style="margin-right: 12px; color: #fff; text-decoration: none;"><i class="fa-book"></i> User Manual</a>
            <a onclick="logout()" style="color: #fff; text-decoration: none;"><i class="fa-sign-out-alt"></i>Log out</a>
        </ul>
  </div>
       
  <!-- Sidebar for Tree Menu -->
   <!-- Top  position should be the height of  "alert" , visible height should from its to to "footer" but scrollable -->
    <div class="sidebar">
        <div class="table-responsive" id="sidebarplaceholder">
        <ul class="nav flex-column tree-menu" id="treeMenu"><i class="fas fa-bars"></i></ul>
        </div>
    </div>
   <!-- Breadcrumbs -->
    <!-- Top  position should be the height of  "alert" ,left should be from the width of "sidebar" then its width should be the rest of the screen width. -->
  
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb" id="breadcrumbs">
                <li class="breadcrumb-item"><a href="#" data-page="homepage.php" data-title="Home"><i class="fas fa-home"></i></a></li>
            </ol>
        </nav>
    <!-- Content Area -->
      <!-- Top  position should be the height of  "breadcrumb" ,
      left should be from the width of "sidebar" then its width should be the rest of the screen width but scrollable.
      visible height should from its to to "footer" but scrollable -->
    
    <div id="page-content">
       <div id="page-content-closable">
       
       </div>
    </div>

    <!-- Login Form -->
    <div id="loginForm">
       <h2>Login</h2>
       <input type="text" id="username" class="login-input" placeholder="Username">
       <input type="password" id="password" class="login-input" placeholder="Password">
       <button class="login-button" onclick="handleLogin()">Login</button>
        <!-- Placeholder for error messages -->
    </div>
    
    <div class="modal fade" id="signatureModal" tabindex="-1" aria-labelledby="signatureModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="signatureModalLabel">Update Signature</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="signatureForm" enctype="multipart/form-data"  onsubmit="return validateForm();">
                <div class="mb-3">
                    <input type="text" id="editfull_name" class="form-control"  name="full_name" placeholder="Enter full name">
                </div>
                <div class="mb-3">
                    <input type="email" id="editemail" class="form-control" name="newemail" placeholder="Enter email">
                     </div>
                <div class="mb-3">
                    <input type="tel" id="edittelephone" class="form-control"  name="newtelephone" placeholder="Enter mobile Number">
                  </div>   
              <div class="mb-3">
                <label for="signature_file" class="form-label">Select Signature Image</label>
                <image src="" id="blobimage"/>
                <input type="file" class="form-control" id="signature_file" name="signature_image" accept="image/*" required />
              </div>
              <button type="button"  onclick="updateSignature();" class="btn btn-primary">Update Signature</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    </div>
 <!-- Footer Area -->
    <div class="footer">
        <span id="numslock-status">Num Lock: OFF</span> |
        <span id="insert-status">Insert: OFF</span> |
        <span id="current-date"></span> |
        <span id="errorDisplay"></span> |
        <img src="css/logo.png" alt="Vendor Logo" id="vendor-logo" style="max-width: 100%; height: 30px;">
    </div>
  
    <!-- Bootstrap and jQuery -->
    <script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
     <script src="assets/js/all.min.js"></script>
    <script type="text/javascript" src="js/toastr/confirmtoastr.js?v=<?php echo filemtime(__DIR__ . '/js/toastr/confirmtoastr.js'); ?>"></script>
    <script type="text/javascript" src="js/toastr/toastr.min.js"></script>
    <script src="js/default.js"></script>
    <script type="text/javascript" src="js/dynamictreemenu.js"></script>
    <script src="js/select2.min.js"></script>
    <script>
        let assignedTests = {}; // Object to hold assignments
    // Call loadMenu() after the user logs in, passing their roleId
        $(document).ready(function() {
            try {
                localStorage.setItem('key', 'value');
                const retrievedValue = localStorage.getItem('key');
                console.log('localStorage set/get works:', retrievedValue);
            } catch (e) {
                console.error('localStorage is not available:', e);
                alert('LocalStorage is blocked or not working in this browser.');
            }

            fetchbatchno();

           const versionNumber = getConfigValue('VersionNumber');
            document.getElementById("version").textContent = versionNumber; // Update version in DOM

            const username = localStorage.getItem('full_name');
            document.getElementById('username').textContent = username;

            const userRoleId = localStorage.getItem('role');
            const user_id = localStorage.getItem('user_id');
            // Fetch and generate the menu on page load
             fetchAndGenerateMenu(userRoleId);
             fillEditForm(user_id);

             setTimeout(function() { fetchbatchno(); },5030); 

             setTimeout(function() { fillEditForm(user_id); },5021); 
    
             const pagehome=`dashboard.php`;
            $('#page-content-closable').load(pagehome, function (response, status) {
                        if (status === "error") {
                            console.error("Error loading page:", response); // Log the error response for debugging
                            // Display error message in the content area
                            $('#page-content-closable').html(`
                                <button class="close-btn" onclick="closePopout()">Ã—</button>
                                <p>Error loading page. Please try again later.</p>
                            `);
                        } else {
                            // Ensure the close button is always present
                            if (!$('#page-content-closable .close-btn').length) {
                                $('#page-content-closable').prepend('<button class="close-btn" onclick="closePopout()">Ã—</button>');
                            }
                          // Show the loaded content and hide the popout
                            $('#page-content-closable').show();
                        }
                    });
                });


        function logout(){
            localStorage.clear();
            localStorage.setItem('saygoodbye','Good Bye');
            window.location.href = "index.php?loutout=yes";
        }

        function fetchbatchno(seed = null) {
          // Prepare the POST data
          //'GetTempLabrefNo', '10'
          let data = {
              action: 'GetTempLabrefNo',
              TransType: '10'
          };

          if (seed !== null) {
              data.seed = seed; // Add the seed parameter if provided
          }

          // Make the AJAX request
          // Use the Fetch API to send a POST request

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
                     $('#nextbatchno').text(data.data);
                  } else {
                      console.error('Error:', data.message);
                  }
              })
              .catch(error => {
                  // Handle any errors
                  console.error('Fetch Error:', error);
              });
        }

        const systemSettings = JSON.parse(localStorage.getItem('systemSettings'));

        // Function to get the configuration value by confname
        function getConfigValue(confname) {
            const setting = systemSettings.find(item => item.confname === confname);
            return setting ? setting.confvalue : null; // Return the value or null if not found
        }
    
        function fillEditForm(userId) {
            const now = Date.now(); // Get the current timestamp
            fetch(`ajax/get_user.php?id=${userId}&time=${now}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('editfull_name').value = user.full_name;
                    document.getElementById('edittelephone').value = user.telephone;
                    document.getElementById('editemail').value = user.email;
                    if(user.signature_path){
                    $('#blobimage').attr('src',`images/${user.signature_path}?time=${Date.now()}`).on('error', function(){ $(this).hide(); });
                    }
                } 
                }).catch(error => {
                    toastr.error('Error fetching user details.');
                    console.error('Error:', error);
                });
        }
        // Function to handle form submission
        function updateSignature() {
            // Get the form data
            const form = document.getElementById('signatureForm');
            const formData = new FormData(form);
            const userId = localStorage.getItem('user_id')
            const time = Date.now(); 
            // Append the user ID to the form data
            formData.append('user_id', userId);
            formData.append('time',time);
          // Send the form data using AJAX
            fetch('ajax/update_signature.php', {
                method: 'POST',
                body: formData // The form data (including the file) will be sent to the server
            }).then(response => response.json())  // Expecting a JSON response
            .then(data => {
                if (data.success) {
                    toastr.success('Signature updated successfully!');
                     fillEditForm(localStorage.getItem('user_id'));
                } else {
                    toastr.error(data.message);
                }
            }).catch(error => {
                toastr.error('An error occurred while updating the signature.'+error);
            });


        }

        function validateForm() {
            // Email validation (HTML5 built-in)
            var email = document.getElementById("editemail").value;
            var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/; // Optional custom email pattern
            if (!email.match(emailPattern)) {
                toastr.error("Please enter a valid email address.");
                return false;
            }



            var telephone = document.getElementById("edittelephone").value.trim(); // Get value and trim whitespace
            var phonePattern = /^\d{10}$/; // Simple 10-digit phone number validation

            // Check if the phone number matches the pattern
            if (!telephone.match(phonePattern)) {
                toastr.error("Please enter a valid 10-digit phone number.");
                return false; // Prevent form submission
            }

            return true;  // Allow form submission if validations pass
        }
    </script>
</body></html>
