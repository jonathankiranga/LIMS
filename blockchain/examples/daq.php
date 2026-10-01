<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAQ Data Plot</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>DAQ Data Plot</h1>
    <canvas id="daqChart" width="400" height="200"></canvas>
    <script>
        // Fetch the DAQ data from the PHP backend
        fetch('dataexample.php')
            .then(response => response.json())
            .then(data => {
                // Prepare the data for Chart.js
                const labels = data.map(item => item.Timestamp);
                const measurements = data.map(item => item.Measurement);

                const ctx = document.getElementById('daqChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'line', // Line chart
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'DAQ Measurement',
                            data: measurements,
                            borderColor: 'rgb(75, 192, 192)',
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                type: 'category',
                                title: {
                                    display: true,
                                    text: 'Timestamp'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Measurement'
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching DAQ data:', error));
    </script>
</body>
</html>
