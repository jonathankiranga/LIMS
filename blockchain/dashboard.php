<script src="js/npm/chart.js"></script>
<style>
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

/* Default: each chart takes one column */
.dashboard > div {
    grid-column: span 1;
}
 
/* Report row spans two columns */
.report-row > div {
   grid-column: span 2;
}

/* Ensure two columns on medium+ screens */
@media (min-width: 600px) {
    .dashboard {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Stack on smaller screens */
@media (max-width: 600px) {
    .dashboard {
        grid-template-columns: 1fr;
    }
    .report-row {
        grid-column: span 1; /* fallback so it doesn’t break layout */
    }
}

    </style>

<div class="dashboard">
    <div class="chart-container">
        <h4>Top 10 Most Recuring Testing</h4>
        <canvas id="topTestsChart"></canvas>
    </div>

    <div class="chart-container">
        <h4>Turn around Time (TAT) Analysis</h4>
        <canvas id="tatChart"></canvas>
    </div>

    <div class="chart-container">
        <h4>Lab Tests by Status Analysis</h4>
        <canvas id="statusChart"></canvas>
    </div>
    
   <div class="chart-container report-row"> 
    <h4>Sample Tracking</h4> 
    <!-- Search box --> 
    <input type="text" id="tableSearch" class="form-control mb-2" placeholder="Search...">
    <div class="table-responsive" style="max-height:450px; overflow-y:auto;">
        <table id="reportTable" class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Sample ID</th>
                    <th>Parameter Name</th>
                    <th>Days</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be injected here -->
            </tbody>
        </table>
    </div>
</div>


    
    <div class="chart-container">
        <h4>Predictive Calibration</h4>
        <label for="machineSelect">Select a Machine:</label>
        <select id="machineSelect">
            <option value="">-- Select a Machine --</option>
        </select>
        <canvas id="predictiveCalibrationChart"></canvas>
    </div>
    
    
</div>

 <script>
     
     // Search filter for table
document.getElementById("tableSearch").addEventListener("keyup", function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll("#reportTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});

     document.getElementById('machineSelect').addEventListener('change', function() {
            const selectedMachineId = this.value;
            if (selectedMachineId) {
                updateGraph(selectedMachineId);
            }
        });
        
        
        document.getElementById('machineSelect').addEventListener('change', function() {
            const selectedMachineId = this.value;
            if (selectedMachineId) {
                updateGraph(selectedMachineId);
            }
        });
        
   
fetch('examples/get_reports.php')
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
            data: { labels: tatLabels, datasets: [{ label: 'Avg TAT (Days)', data: tatValues, borderColor: 'green', fill: false }] },
            options: { responsive: true }
        });

        // Pending vs Completed Tests Chart
        new Chart(document.getElementById("statusChart"), {
            type: 'pie',
            data: { 
                labels: Object.keys(data.pending_completed), 
                datasets: [{ 
                        data: Object.values(data.pending_completed),
                        backgroundColor: ['red', 'green', 'blue']
                    }]
            },
            options: { responsive: true }
        });

        // ✅ Consolidated Report Table
        const tableBody = document.querySelector("#reportTable tbody");
        tableBody.innerHTML = "";

        data.consolidated.forEach(row => { 
            const tat = row.days;
            let colorofTAT; 
            if (tat <= 3) {
                colorofTAT = 'style="background-color: green !important; color:white;"'; 
            } else if (tat <= 5) { 
                colorofTAT = 'style="background-color: orange !important;"'; 
            } else { 
                colorofTAT = 'style="background-color: red; color:white;"'; 
            } 

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${row.SampleID}</td>
                <td>${row.ParameterName}</td>
                <td ${colorofTAT}>${row.days}</td>
            `;
            tableBody.appendChild(tr); 
        });
    })
    .catch(error => console.error('Error fetching data:', error));




     let allData = {};
  
        fetch('examples/predict_calibration.php')
            .then(response => response.json())
            .then(data => {
                allData = data; // Store the complete data for later use

                // Populate the select box
                const machineSelect = document.getElementById('machineSelect');
                Object.keys(data.historical).forEach(id => {
                    const machineName = data.historical[id].machine_name;
                    const option = document.createElement('option');
                    option.value = id; // Use equipment ID as the value
                    option.textContent = machineName; // Display machine name
                    machineSelect.appendChild(option);
                });

                // Draw graph for the first machine by default
                if (machineSelect.value) {
                    updateGraph(machineSelect.value);
                }
            })
            .catch(error => console.error('Error fetching predictions:', error));

      let chartInstance; // Variable to hold the current chart instance

        function updateGraph(machineId) {
            const historicalData = allData.historical[machineId].historical || [];
            const predictedData = allData.predictions[machineId].predicted_deviation || [];

            // Prepare historical labels and deviations
            const historicalLabels = historicalData.map(entry => entry.month);
            const historicalDeviations = historicalData.map(entry => entry.avg_deviation);

            // Prepare prediction labels
            const predictedLabels = ['Next Month', 'Month After Next'];

            // If the chart instance exists, destroy it before creating a new one
            if (chartInstance) {
                chartInstance.destroy();
            }

            // Create the chart
            chartInstance = new Chart(document.getElementById("predictiveCalibrationChart"), {
                type: 'line', // Use line chart for curves
                data: {
                    labels: historicalLabels.concat(predictedLabels), // Combine historical and predicted labels
                    datasets: [
                        {
                            label: 'Historical Deviations',
                            data: historicalDeviations,
                            borderColor: 'blue',
                            fill: false
                        },
                        {
                            label: 'Predicted Deviations',
                            data: predictedData,
                            borderColor: 'orange',
                            fill: false,
                            borderDash: [5, 5] // Dashed line for predictions
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            },
                            ticks: {
                                autoSkip: true,
                                maxTicksLimit: 20 // Adjust as necessary
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Deviation'
                            }
                        }
                    }
                }
            });
            // Ensure this closing parenthesis is included
        }

    
</script>
 