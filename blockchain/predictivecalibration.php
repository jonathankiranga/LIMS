<div class="chart-container">
    <h4>Predictive Calibration</h4>
    <label for="machineSelect">Select a Machine:</label>
    <select id="machineSelect">
        <option value="">-- Select a Machine --</option>
    </select>
    <canvas id="predictiveCalibrationChart"></canvas>
</div>

<script>
document.getElementById('machineSelect').addEventListener('change', function() {
       const selectedMachineId = this.value;
       if (selectedMachineId) {
           updateGraph(selectedMachineId);
       }
   });


window.allData = {};

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

window.chartInstance; // Variable to hold the current chart instance

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
    }); // Ensure this closing parenthesis is included
}
    
</script>
