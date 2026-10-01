<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Security Manager</title>
    <link rel="stylesheet" href="css/rolemanager.css">
    <style>
        .permissions-tables {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            margin-top: 20px;
        }
        .permissions-table {
            flex: 1;
        }
        .permission-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .permission-grid th, .permission-grid td {
            border: 1px solid #ccc;
            padding: 6px;
        }
        .permission-grid th {
            background: #f4f4f4;
        }
        .permissions-table button {
            display: block;
            margin: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <div id="game-container">
        <header class="game-header">
            <h1>⚔ Role Security Manager ⚔</h1>
        </header>
        <section class="role-section">
            <label for="roleSelect">🎮 Select Role:</label>
            <select id="roleSelect"></select>
        </section>

        <section class="menu-items-section">
            <h3>🔒 Assign Security Permissions</h3>
            <form id="roleSecurityForm">
                <div class="permissions-tables">
                    <div class="permissions-table">
                        <h5>Available</h5>
                         <button type="button" id="assignBtn">→ Assign</button>
                        <table id="availableTable" class="permission-grid">
                            <thead>
                                <tr><th></th><th>Menu Item</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                       
                    </div>
                    
                    <div class="permissions-table">
                        <h5>Assigned</h5>
                        <button type="button" id="unassignBtn">← Unassign</button>
                        <table id="assignedTable" class="permission-grid">
                            <thead>
                                <tr><th></th><th>Menu Item</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        
                    </div>
                </div>
                <button type="submit" class="game-button">Save Permissions</button>
            </form>
            <button id="resetBtn" class="game-button reset-button">Reset</button>
        </section>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    let roles = [], menuItems = [], roleSecurity = {};

    // Load roles + menu items
    $.ajax({
        url: 'ajax/role_security.php',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            if (data && data.roles) roles = data.roles;
            if (data && data.menu_items) menuItems = data.menu_items;
            roleSecurity = {};
            if (data && data.role_security) {
                data.role_security.forEach(rs => {
                    if (!roleSecurity[rs.role_id]) roleSecurity[rs.role_id] = [];
                    roleSecurity[rs.role_id].push(rs.security_id.toString());
                });
            }
            populateRoles();
            $('#roleSelect').trigger('change');
        }
    });

    function populateRoles() {
        let roleSelect = $('#roleSelect').empty();
        roles.forEach(role => {
            roleSelect.append(`<option value="${role.id}">${role.role_name}</option>`);
        });
    }

    function groupMenuItems(items) {
        let grouped = {};
        items.forEach(item => {
            const parentId = item.parent_id !== null ? item.parent_id : 'root';
            if (!grouped[parentId]) grouped[parentId] = [];
            grouped[parentId].push(item);
        });
        return grouped;
    }

    function renderTables(assigned, groupedMenuItems) {
        let availableBody = $('#availableTable tbody').empty();
        let assignedBody = $('#assignedTable tbody').empty();

        if (groupedMenuItems['root']) {
            groupedMenuItems['root'].forEach(parent => {
                if (groupedMenuItems[parent.id]) {
                    groupedMenuItems[parent.id].forEach(child => {
                        let row = `
                            <tr>
                              <td><input type="checkbox" value="${child.id}"></td>
                              <td>${child.title}</td>
                            </tr>`;
                        if (assigned.includes(child.id.toString())) {
                            assignedBody.append(row);
                        } else {
                            availableBody.append(row);
                        }
                    });
                }
            });
        }
    }

    // On role change -> load tables
    $('#roleSelect').change(function () {
        let selectedRole = $(this).val();
        let assigned = roleSecurity[selectedRole] || [];
        let groupedMenuItems = groupMenuItems(menuItems);
        renderTables(assigned, groupedMenuItems);
    });

    // Assign button
    $('#assignBtn').on('click', () => {
        $('#availableTable tbody input:checked').each(function () {
            let row = $(this).closest('tr');
            $('#assignedTable tbody').append(row);
            $(this).prop('checked', false);
        });
    });

    // Unassign button
    $('#unassignBtn').on('click', () => {
        $('#assignedTable tbody input:checked').each(function () {
            let row = $(this).closest('tr');
            $('#availableTable tbody').append(row);
            $(this).prop('checked', false);
        });
    });

    // Reset button -> move everything back to available
    $('#resetBtn').on('click', function (e) {
        e.preventDefault();
        $('#assignedTable tbody tr').appendTo('#availableTable tbody');
        $('#availableTable input').prop('checked', false);
    });

    // Save permissions
    $('#roleSecurityForm').submit(function (e) {
        e.preventDefault();
        let selectedRole = $('#roleSelect').val();
        let security_ids = [];
        $('#assignedTable tbody input').each(function () {
            security_ids.push($(this).val());
        });

        $.ajax({
            url: 'ajax/role_security.php',
            method: 'POST',
            data: { role_id: selectedRole, security_ids: security_ids },
            dataType: 'json',
            success: response => toastr.success(response.message),
            error: () => toastr.error("Error saving role security.")
        });
    });
});
</script>
</body>
</html>
