<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #1e1e2e;
            color: #ffffff;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        h1 {
            color: #f39c12;
        }

        .menu {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .menu-item {
            width: 200px;
            height: 150px;
            background: #2c3e50;
            border: 2px solid #16a085;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.3s, background 0.3s;
        }

        .menu-item:hover {
            transform: scale(1.1);
            background: #16a085;
        }

        .menu-item i {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .menu-item span {
            font-size: 1.2em;
        }

        .content {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #34495e;
            border-radius: 10px;
            width: 100%;
            max-width: 800px;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
        }

        .content table th,
        .content table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ffffff;
        }

        .content table th {
            background: #16a085;
        }

        .add-button {
            margin-bottom: 20px;
            background: #16a085;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }

        .add-button:hover {
            background: #1abc9c;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #2c3e50;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .modal-content h2 {
            margin-top: 0;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 5px;
        }

        .modal-content button {
            background: #16a085;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }

        .modal-content button:hover {
            background: #1abc9c;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script>
        function showContent(tableId) {
            const sections = document.querySelectorAll('.content');
            sections.forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(tableId).style.display = 'block';
        }

        function openAttendanceModal() {
            document.getElementById('attendanceModal').style.display = 'flex';
        }

        function closeAttendanceModal() {
            document.getElementById('attendanceModal').style.display = 'none';
        }

        function saveAttendance() {
            // Logic to save attendance data
            closeAttendanceModal();
        }

        $(document).ready(function() {
            // Initialize select2 for employee search
            $('#employeeSearch').select2({
                ajax: {
                    url: 'fetch_employees.php', // Replace with server-side script to fetch employee data
                    dataType: 'json',
                    processResults: function(data) {
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name
                                };
                            })
                        };
                    }
                }
            });

            $('#employeeSearch').on('select2:select', function(e) {
                const data = e.params.data;
                // Populate other fields based on selected employee
                // Example: Fetch data from server
                $.ajax({
                    url: 'fetch_employee_details.php', // Replace with server-side script
                    data: { id: data.id },
                    success: function(response) {
                        const details = JSON.parse(response);
                        $('#checkIn').val(details.check_in || '');
                        $('#checkOut').val(details.check_out || '');
                        $('#hoursWorked').val(details.hours_worked || '');
                    }
                });
            });
        });
    
        function openModal() {
            document.getElementById('addEmployeeModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('addEmployeeModal').style.display = 'none';
        }

        function saveEmployee() {
            // Logic to save employee data
            closeModal();
        }
     
        function openLeaveRequestModal() {
            document.getElementById('leaveRequestModal').style.display = 'flex';
        }

        function closeLeaveRequestModal() {
            document.getElementById('leaveRequestModal').style.display = 'none';
        }

        function saveLeaveRequest() {
            closeLeaveRequestModal();
        }

        $(document).ready(function() {
            // Initialize select2 for employee search
            $('#employeeSearchLeave').select2({
                ajax: {
                    url: 'fetch_employees.php', // Replace with server-side script to fetch employee data
                    dataType: 'json',
                    processResults: function(data) {
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name
                                };
                            })
                        };
                    }
                }
            });

            $('#employeeSearchLeave').on('select2:select', function(e) {
                const data = e.params.data;
                // Populate other fields based on selected employee
                // Example: Fetch data from server
                $.ajax({
                    url: 'fetch_employee_details.php', // Replace with server-side script
                    data: { id: data.id },
                    success: function(response) {
                        const details = JSON.parse(response);
                        $('#leaveType').val(details.leave_type || '');
                        $('#startDate').val(details.start_date || '');
                        $('#endDate').val(details.end_date || '');
                    }
                });
            });
        });    
   </script>           
</head>
<body>
    <div class="container">
        <div class="menu">
            <div class="menu-item" onclick="showContent('employees')">
                <i class="fas fa-users"></i>
                <span>Employees</span>
            </div>
            <div class="menu-item" onclick="showContent('attendance')">
                <i class="fas fa-clock"></i>
                <span>Attendance</span>
            </div>
            <div class="menu-item" onclick="showContent('leave_requests')">
                <i class="fas fa-plane"></i>
                <span>Leave Requests</span>
            </div>
            
            <div class="menu-item" onclick="showContent('leave_balances')">
                <i class="fas fa-chart-bar"></i>
                <span>Leave Balances</span>
            </div>
        </div>

        <div id="employees" class="content">
            <h2>Employees Table</h2>
            <button class="add-button" onclick="openModal()">Add Employee</button>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Salary</th>
                        <th>Overtime Rate</th>
                        <th>Hire Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data rows go here -->
                </tbody>
            </table>
        </div>

        <div id="addEmployeeModal" class="modal">
            <div class="modal-content">
                <h2>Add Employee</h2>
                <label for="employeeName">Name</label>
                <input type="text" id="employeeName" placeholder="Enter name">

                <label for="employeeRole">Role</label>
                <input type="text" id="employeeRole" placeholder="Enter role">

                <label for="employeeSalary">Salary</label>
                <input type="number" id="employeeSalary" placeholder="Enter salary">

                <label for="overtimeRate">Overtime Rate</label>
                <input type="number" step="0.01" id="overtimeRate" placeholder="Enter overtime rate">

                <label for="hireDate">Hire Date</label>
                <input type="date" id="hireDate">

                <button onclick="saveEmployee()">Save</button>
                <button onclick="closeModal()">Cancel</button>
            </div>
        </div>


        <div id="attendance" class="content">
            <h2>Attendance Table</h2>
            <button class="add-button" onclick="openAttendanceModal()">Add Attendance</button>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Hours Worked</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data rows go here -->
                </tbody>
            </table>
        </div>

        <div id="attendanceModal" class="modal">
            <div class="modal-content">
                <h2>Add Attendance</h2>
                <label for="employeeSearch">Employee</label>
                <select id="employeeSearch" style="width: 100%;"></select>

                <label for="checkIn">Check-In</label>
                <input type="datetime-local" id="checkIn">

                <label for="checkOut">Check-Out</label>
                <input type="datetime-local" id="checkOut">

                <label for="hoursWorked">Hours Worked</label>
                <input type="number" step="0.01" id="hoursWorked">

                <button onclick="saveAttendance()">Save</button>
                <button onclick="closeAttendanceModal()">Cancel</button>
            </div>
        </div>
    
<div id="leave_requests" class="content">
            <h2>Leave Requests Table</h2>
                      <button class="add-button" onclick="openLeaveRequestModal()">Add Leave Request</button>
  
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data rows go here -->
                </tbody>
            </table>
        </div>
        

        <div id="leaveRequestModal" class="modal">
            <div class="modal-content">
                <h2>Add Leave Request</h2>
                <label for="employeeSearchLeave">Employee</label>
                <select id="employeeSearchLeave" style="width: 100%;"></select>

                <label for="leaveType">Leave Type</label>
                <input type="text" id="leaveType">

                <label for="startDate">Start Date</label>
                <input type="date" id="startDate">

                <label for="endDate">End Date</label>
                <input type="date" id="endDate">

                <button onclick="saveLeaveRequest()">Save</button>
                <button onclick="closeLeaveRequestModal()">Cancel</button>
            </div>
        </div>
 
        <div id="leave_balances" class="content">
            <h2>Leave Balances Table</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Leave Type</th>
                        <th>Entitlement</th>
                        <th>Used Days</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data rows go here -->
                </tbody>
            </table>
        </div>
   

    </div>
</body>
</html>
