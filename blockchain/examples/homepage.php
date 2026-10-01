
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .chart-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        canvas {
            width: 100%;
            height: auto;
        }
    </style>

    <div class="dashboard">
        <div class="chart-container">
            <h4>Top 10 Testing</h4>
            <canvas id="topTestsChart"></canvas>
        </div>
        <div class="chart-container">
            <h4>Turnaround Time (TAT) Analysis</h4>
            <canvas id="tatChart"></canvas>
        </div>
        <div class="chart-container">
            <h4>Pending vs Completed Tests</h4>
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <script>
        fetch('get_reports.php')
            .then(response => response.json())
            .then(data => {
                // Top 10 Testing Chart
                const testLabels = data.top_tests.map(test => test.ParameterName);
                const testValues = data.top_tests.map(test => test.test_count);
                new Chart(document.getElementById("topTestsChart"), {
                    type: 'bar',
                    data: { labels: testLabels, datasets: [{ label: 'Top Tests', data: testValues, backgroundColor: 'blue' }] },
                    options: { responsive: true }
                });

                // Turnaround Time (TAT) Analysis Chart
                const tatLabels = data.tat_analysis.map(test => test.ParameterName);
                const tatValues = data.tat_analysis.map(test => test.avg_tat);
                new Chart(document.getElementById("tatChart"), {
                    type: 'line',
                    data: { labels: tatLabels, datasets: [{ label: 'Avg TAT (hrs)', data: tatValues, borderColor: 'green', fill: false }] },
                    options: { responsive: true }
                });

                // Pending vs Completed Tests Chart
                new Chart(document.getElementById("statusChart"), {
                    type: 'pie',
                    data: { 
                        labels: Object.keys(data.pending_completed), 
                        datasets: [{ data: Object.values(data.pending_completed), backgroundColor: ['red', 'green'] }]
                    },
                    options: { responsive: true }
                });
            })
            .catch(error => console.error('Error fetching data:', error));
    </script>
 