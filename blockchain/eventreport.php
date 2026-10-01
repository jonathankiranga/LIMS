<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Log Report</title>
  
</head>
<body>

<!-- Filter Form -->
<form id="eventLogForm" method="GET" action="event_log.php">
    <div class="form-group">
        <label for="status">Filter by Status:</label>
        <select class="form-control" name="status" id="status">
            <option value="">-- Select Status --</option>
            <option value="success">Success</option>
            <option value="failure">Failure</option>
        </select>
    </div>

    <div class="form-group">
        <label for="event_triggered">Filter by Event Name:</label>
   <select class="form-control" name="event_triggered" id="event_triggered" required>
                    <option value="">Select Event</option>
                    <option value="test_approved">Test Approved</option>
                    <option value="sample_received">Sample Received</option>
                    <!-- Add other events as needed -->
                </select>
    </div>

    <button type="submit" class="btn btn-primary">Filter Logs</button>
</form>

<!-- Event Logs Table -->
<div id="logContainer"></div>

<script>
   $(document).ready(function() {
    // Function to fetch and display logs dynamically
    function fetchLogs() {
        var status = $('#status').val();
        var eventTriggered = $('#event_triggered').val();

        $.ajax({
            url: 'ajax/fetcheventlogs.php', // The PHP script that handles fetching logs
            type: 'GET',
            data: { status: status, event_triggered: eventTriggered },
            success: function(response) {
                var logs = JSON.parse(response);
                
                if (logs.success === false) {
                    $('#logContainer').html('<p>' + logs.message + '</p>');
                } else {
                    var logTable = '<table class="table table-bordered">';
                    logTable += '<thead><tr><th>Task ID</th><th>Event Triggered</th><th>Status</th><th>Error Message</th><th>Triggered At</th></tr></thead>';
                    logTable += '<tbody>';

                    // Loop through the logs and create table rows
                    logs.forEach(function(log) {
                        logTable += '<tr>';
                        logTable += '<td>' + log.task_name + ' (ID: ' + log.task_id + ')</td>';
                        logTable += '<td>' + log.event_triggered + '</td>';
                        logTable += '<td>' + log.status + '</td>';
                        logTable += '<td>' + log.error_message + '</td>';
                        logTable += '<td>' + log.triggered_at + '</td>';
                        logTable += '</tr>';
                    });

                    logTable += '</tbody>';
                    logTable += '</table>';
                    
                    $('#logContainer').html(logTable); // Insert table into the page
                }
            },
            error: function() {
                alert('Error fetching event logs.');
            }
        });
    }

    // Fetch logs when the form is submitted (AJAX)
    $('#eventLogForm').submit(function(e) {
        e.preventDefault();  // Prevent the default form submission
        fetchLogs();  // Call the function to fetch and display logs
    });

    // Optionally, you can fetch logs when the page loads to display all logs
    fetchLogs();
});

</script>

</body>
</html>
