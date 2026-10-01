-- SQL Script to Create Payroll and Leave Management Tables

-- Table: employees
CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL,
    salary DECIMAL(10, 2) DEFAULT 0.00,
    overtime_rate DECIMAL(5, 2) DEFAULT 0.00,
    hire_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);

-- Table: attendance
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NULL,
    hours_worked DECIMAL(5, 2) GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, check_in, check_out) / 60) STORED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Table: leave_requests
CREATE TABLE leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type ENUM('annual', 'sick', 'maternity', 'unpaid') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    remarks TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (approved_by) REFERENCES employees(id)
);

-- Table: payroll_records
CREATE TABLE payroll_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    month VARCHAR(7) NOT NULL,
    basic_salary DECIMAL(10, 2) NOT NULL,
    overtime_hours DECIMAL(5, 2) DEFAULT 0.00,
    overtime_pay DECIMAL(10, 2) GENERATED ALWAYS AS (overtime_hours * (SELECT overtime_rate FROM employees WHERE employees.id = payroll_records.employee_id)) STORED,
    deductions DECIMAL(10, 2) DEFAULT 0.00,
    net_salary DECIMAL(10, 2) GENERATED ALWAYS AS (basic_salary + overtime_pay - deductions) STORED,
    paid_date DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Table: leave_balances
CREATE TABLE leave_balances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type ENUM('annual', 'sick', 'maternity') NOT NULL,
    entitlement DECIMAL(5, 2) NOT NULL,
    used_days DECIMAL(5, 2) DEFAULT 0.00,
    balance DECIMAL(5, 2) GENERATED ALWAYS AS (entitlement - used_days) STORED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

<div id="leave_requests" class="content">
            <h2>Leave Requests Table</h2>
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