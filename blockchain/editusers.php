<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sample List</title>
  
  <link rel="stylesheet" href="css/users.css">
</head>
<body>
<div class="container">
    <div class="container">
        <h3><i class="fas fa-users"></i> Users</h3>
    </div>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th><i class="fas fa-user"></i></th>
                <th><i class="fas fa-user-edit"></i></th>
                <th><i class="fas fa-envelope"></i></th>
                <th><i class="fas fa-phone"></i></th>
                <th><i class="fas fa-toggle-on"></i></th>
                <th>Role</th>
                <th>Department</th>
                <th><i class="fas fa-cogs"></i></th>
            </tr>
        </thead>
        <tbody id="userTableBody">
            <!-- Rows will be populated by AJAX -->
        </tbody>
    </table>
</div>

 <!-- User Edit Modal -->
<div class="modal fade" id="userEditModal" tabindex="-1" aria-labelledby="userEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="userEditModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden"  id="user_id" name="user_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label"><i class="fas fa-user"></i>Username</label>
                            <input type="text" class="form-control" id="username" name="username" readonly="readonly">
                        </div>
                        <div class="col-md-6">
                            <label for="full_name" class="form-label"><i class="fas fa-user-edit"></i>Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telephone" class="form-label"><i class="fas fa-phone"></i>Telephone</label>
                            <input type="text" class="form-control" id="telephone" name="telephone" maxlength="12" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label"><i class="fas fa-envelope"></i>Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                               <option value="1">Administrator |Full access to all features and settings</option>
                               <option value="2">Manager |Access to high-level management features</option>
                               <option value="3">Supervisor |Can oversee operations and moderate content</option>
                               <option value="4">Editor |Can create, edit, and update content</option>
                               <option value="5">Viewer |Read-only access to view dashboards and reports</option>
                               <option value="6">Quality Analyst|Access to quality control and assurance tools</option>
                               <option value="7">Laboratory Technician |Handles sample processing and data entry</option>
                               <option value="8">Customer Support |Access to client communication and basic operations</option>
                               <option value="9">Auditor|Access to logs, reports, and audit trails</option>
                               <option value="10">Guest |Limited access for temporary users</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="banned">Banned</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="assignedDepartment" class="form-label">Assigned Department/Technician</label>
                            <select id="assignedDepartment" name="assignedDepartment" class="form-select form-select-sm">
                              <option value="" disabled selected>Select Department</option>
                              <option value="chemical">Chemical</option>
                              <option value="microbiological">Microbiological</option>
                              <option value="admin">Super User</option>
                              <option value="guest">No Department</option>
                            </select>
                          </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function fillEditForm(userId) {
        fetch(`ajax/get_user.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('user_id').value = user.user_id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('full_name').value = user.full_name;
                    document.getElementById('telephone').value = user.telephone;
                    document.getElementById('email').value = user.email;
                    document.getElementById('role').value = user.role;
                    document.getElementById('status').value = user.status;
                    document.getElementById('assignedDepartment').value = user.department;
                    
                    // Show the modal
                    new bootstrap.Modal(document.getElementById("userEditModal")).show();
                } else {
                    toastr.error('Failed to load user details.');
                }
            })
            .catch(error => {
                toastr.error('Error fetching user details.');
                console.error('Error:', error);
            });
    }

    function fetchUsers() {
        fetch('ajax/fetch_users.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const users = data.data;
                    const tableBody = document.getElementById('userTableBody');
                    tableBody.innerHTML = ''; // Clear existing rows

                    users.forEach(user => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${user.user_id}</td>
                            <td>${user.username}</td>
                            <td>${user.full_name}</td>
                            <td>${user.email}</td>
                            <td>${user.telephone}</td>
                            <td>${user.status}</td>
                            <td>${user.role}</td>
                            <td>${user.department}</td>
                            <td>
                                <button class="btn btn-primary" onclick="fillEditForm(${user.user_id})">
                                    Edit
                                </button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    toastr.error('No users found.');
                }
            })
            .catch(error => {
                toastr.error('Error fetching users.');
                console.error('Error:', error);
            });
    }
    
    document.getElementById('editUserForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const userData = {
                user_id: formData.get('user_id'),
                username: formData.get('username'),
                full_name: formData.get('full_name'),
                telephone: formData.get('telephone'),
                email: formData.get('email'),
                role: formData.get('role'),
                status: formData.get('status'),
                assignedDepartment: formData.get('assignedDepartment')
            };

            // Send the updated data using AJAX
            fetch('ajax/update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the modal after successful update
                    bootstrap.Modal.getOrCreateInstance($('#userEditModal')[0]).hide();
                    toastr.success('User data updated successfully.');
               // Refresh user list
               new bootstrap.Modal(document.getElementById("userEditModal")).hide();
                    fetchUsers();
                } else {
                    toastr.error('Failed to update user data.');
                }
            })
            .catch(error => {
                toastr.error('Error updating user data.');
                console.error('Error:', error);
            });
            
        });
    fetchUsers();
</script>
</body>
</html>

