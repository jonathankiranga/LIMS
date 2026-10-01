<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDS Calculator</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function calculateTDS() {
            let totalTDS = 0;
            $('input[type="number"]').each(function() {
                const concentration = parseFloat($(this).val()) || 0;
                const atomicWeight = parseFloat($(this).data('atomic-weight')) || 0;
                totalTDS += concentration * atomicWeight;
            });
            $('#tdsResult').text(totalTDS.toFixed(2) + ' mg/L');
        }
    </script>
</head>
<body>
<div class="container mt-5">
    <h1>TDS Calculator</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Element</th>
                <th>Symbol</th>
                <th>Atomic Weight (g/mol)</th>
                <th>Concentration (mg/L)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $elements = [
                ['Element' => 'Hydrogen', 'Symbol' => 'H', 'AtomicWeight' => 1.008],
                ['Element' => 'Oxygen', 'Symbol' => 'O', 'AtomicWeight' => 16.00],
                ['Element' => 'Nitrogen', 'Symbol' => 'N', 'AtomicWeight' => 14.01],
                ['Element' => 'Carbon', 'Symbol' => 'C', 'AtomicWeight' => 12.01],
                ['Element' => 'Sodium', 'Symbol' => 'Na', 'AtomicWeight' => 22.99],
                ['Element' => 'Potassium', 'Symbol' => 'K', 'AtomicWeight' => 39.10],
                ['Element' => 'Calcium', 'Symbol' => 'Ca', 'AtomicWeight' => 40.08],
                ['Element' => 'Magnesium', 'Symbol' => 'Mg', 'AtomicWeight' => 24.31],
                ['Element' => 'Iron', 'Symbol' => 'Fe', 'AtomicWeight' => 55.85],
                ['Element' => 'Copper', 'Symbol' => 'Cu', 'AtomicWeight' => 63.55],
                ['Element' => 'Lead', 'Symbol' => 'Pb', 'AtomicWeight' => 207.2],
                ['Element' => 'Zinc', 'Symbol' => 'Zn', 'AtomicWeight' => 65.38],
                ['Element' => 'Manganese', 'Symbol' => 'Mn', 'AtomicWeight' => 54.94],
                ['Element' => 'Chlorine', 'Symbol' => 'Cl', 'AtomicWeight' => 35.45],
                ['Element' => 'Fluorine', 'Symbol' => 'F', 'AtomicWeight' => 19.00],
                ['Element' => 'Boron', 'Symbol' => 'B', 'AtomicWeight' => 10.81],
                ['Element' => 'Sulfur', 'Symbol' => 'S', 'AtomicWeight' => 32.07],
                ['Element' => 'Phosphorus', 'Symbol' => 'P', 'AtomicWeight' => 30.97],
            ];
            foreach ($elements as $element) {
                echo "<tr>
                        <td>{$element['Element']}</td>
                        <td>{$element['Symbol']}</td>
                        <td>{$element['AtomicWeight']}</td>
                        <td><input type='number' data-atomic-weight='{$element['AtomicWeight']}' class='form-control' min='0' placeholder='Enter concentration'></td>
                      </tr>";
            }
            ?>
        </tbody>
    </table>
    <button type="button" class="btn btn-primary" onclick="calculateTDS()">Calculate TDS</button>
    <h3 class="mt-4">Total Dissolved Solids (TDS): <span id="tdsResult">0 mg/L</span></h3>
</div>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>