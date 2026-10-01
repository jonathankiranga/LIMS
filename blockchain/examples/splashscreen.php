<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIMS ERP Homepage</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom CSS */
        body {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 20%;
            background-color: #343a40;
            color: white;
            padding: 10px;
            overflow-y: auto;
        }
        .sidebar .tree-menu a {
            color: #c7c7c7;
            text-decoration: none;
        }
        .sidebar .tree-menu a:hover {
            color: #ffffff;
            background-color: #495057;
        }
        .content-area {
            width: 80%;
            padding: 20px;
            overflow-y: auto;
        }
        .tree-menu .nav-item {
            padding: 5px;
        }
        /* Collapsible submenu styling */
        .submenu {
            display: none;
            padding-left: 15px;
        }
    </style>
</head>
<body>

    <!-- Sidebar for Tree Menu -->
    <div class="sidebar">
        <h4 class="text-white">LIMS ERP Menu</h4>
        <ul class="nav flex-column tree-menu">
            <li class="nav-item">
                <a href="#" data-page="sample-management.html" class="nav-link"><i class="fas fa-vial"></i> Sample Management</a>
            </li>
            <li class="nav-item">
                <a href="#" data-page="environmental-monitoring.html" class="nav-link"><i class="fas fa-seedling"></i> Environmental Monitoring</a>
            </li>
            <li class="nav-item">
                <a href="#" data-toggle="collapse" data-target="#chain-custody" class="nav-link"><i class="fas fa-link"></i> Chain of Custody</a>
                <ul class="submenu" id="chain-custody">
                    <li><a href="#" data-page="custody-log.html" class="nav-link">Custody Log</a></li>
                    <li><a href="#" data-page="custody-tracking.html" class="nav-link">Custody Tracking</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="#" data-page="quality-control.html" class="nav-link"><i class="fas fa-balance-scale"></i> Quality Control</a>
            </li>
            <li class="nav-item">
                <a href="#" data-page="blockchain-ledger.html" class="nav-link"><i class="fas fa-lock"></i> Blockchain Ledger</a>
            </li>
            <li class="nav-item">
                <a href="#" data-page="predictive-analysis.html" class="nav-link"><i class="fas fa-chart-line"></i> Predictive MRL Analysis</a>
            </li>
            <li class="nav-item">
                <a href="#" data-page="sms-alerts.html" class="nav-link"><i class="fas fa-sms"></i> Real-Time SMS Alerts</a>
            </li>
            <li class="nav-item">
                <a href="#" data-page="role-dashboard.html" class="nav-link"><i class="fas fa-user-circle"></i> Role-Based Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="#" data-page="interactive-reports.html" class="nav-link"><i class="fas fa-chart-pie"></i> Interactive Reports</a>
            </li>
        </ul>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <h2>Welcome to LIMS ERP</h2>
        <div id="page-content">
            <p>Select an item from the menu to view details here.</p>
        </div>
    </div>

    <!-- Bootstrap and jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle submenu visibility
        $('.tree-menu .nav-item > a[data-toggle="collapse"]').on('click', function(e) {
            e.preventDefault();
            $(this).next('.submenu').slideToggle();
        });

        // AJAX content loading
        $('.tree-menu .nav-link').on('click', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                $('#page-content').html('<p>Loading...</p>'); // Loading message
                // Load page content into content area
                $('#page-content').load(page, function(response, status) {
                    if (status === "error") {
                        $('#page-content').html('<p>Error loading page. Please try again later.</p>');
                    }
                });
            }
        });
    </script>
</body>
</html>
