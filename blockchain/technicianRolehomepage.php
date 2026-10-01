<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Technician Workflow</title>
    <style>
        .workflow-group {
            margin-bottom: 10px; /* Reduced margin */
            text-align: center;
        }
        .workflow-arrow {
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            padding: 10px; /* Reduced padding */
        }
        .dashboard-card {
            top:0px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 80%;
        }
        .icon-container {
            font-size: 2.5rem; /* Slightly smaller font size */
            padding: 10px; /* Reduced padding */
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card-body {
            padding: 5px 15px; /* Reduced padding */
        }
        .card-title {
            font-size: 1rem; /* Slightly smaller font size */
            font-weight: bold;
        }
        .card-description {
            font-size: 0.8rem; /* Reduced font size */
            color: #6c757d;
        }
        .workflow-arrow i {
            color: #007bff;
        }
        .icon-container.bg-success { background-color: #28a745; }
        .icon-container.bg-info { background-color: #17a2b8; }
        .icon-container.bg-warning { background-color: #ffc107; }
        .icon-container.bg-secondary { background-color: #6c757d; }
    </style>
</head>
<body>
   
<div class="container">
    <div class="row">
        <!-- Static Workflow Data -->
        <div class="col-md-3 workflow-group">
            <div class="dashboard-card">
                <div class="icon-container bg-warning">
                    <i class="fas fa-user"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Register Sample</h5>
                    <p class="card-description">Assigned to: Reception</p>
                </div>
            </div>
        </div>

        <div class="col-md-1 workflow-arrow">
            <i class="fas fa-arrow-right"></i>
        </div>

        <div class="col-md-3 workflow-group">
            <div class="dashboard-card">
                <div class="icon-container bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Schedule Sample</h5>
                    <p class="card-description">Assigned to: Admin</p>
                </div>
            </div>
        </div>

        <div class="col-md-1 workflow-arrow">
            <i class="fas fa-arrow-right"></i>
        </div>

        <div class="col-md-3 workflow-group">
            <div class="dashboard-card">
                <div class="icon-container bg-info">
                    <i class="fas fa-flask"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Test Sample</h5>
                    <p class="card-description">Assigned to: Lab</p>
                </div>
            </div>
        </div>

        <div class="col-md-1 workflow-arrow">
            <i class="fas fa-arrow-right"></i>
        </div>

        <div class="col-md-3 workflow-group">
            <div class="dashboard-card">
                <div class="icon-container bg-warning">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Review Sample</h5>
                    <p class="card-description">Assigned to: Tech</p>
                </div>
            </div>
        </div>

        <div class="col-md-1 workflow-arrow">
            <i class="fas fa-arrow-right"></i>
        </div>

        <div class="col-md-3 workflow-group">
            <div class="dashboard-card">
                <div class="icon-container bg-success">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Authorize Sample</h5>
                    <p class="card-description">Assigned to: Supervisor</p>
                </div>
            </div>
        </div>

        <div class="col-md-1 workflow-arrow">
            <i class="fas fa-arrow-right"></i>
        </div>

        <div class="col-md-3 workflow-group">
            <div class="dashboard-card">
                <div class="icon-container bg-info">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Send Payment Request</h5>
                    <p class="card-description">Assigned to: Admin</p>
                </div>
            </div>
        </div>

        <div class="col-md-1 workflow-arrow">
            <i class="fas fa-arrow-right"></i>
        </div>

        <div class="col-md-3 workflow-group">
            <div class="dashboard-card">
                <div class="icon-container bg-success">
                    <i class="fas fa-print"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Print Sample</h5>
                    <p class="card-description">Assigned to: Tech</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
