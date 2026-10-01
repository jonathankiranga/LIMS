<!-- tds-calculator-content.php -->
<div class="container mt-5">
    <h1>TDS Calculator</h1>
    <p>
        <label for="sampleRef"><strong>Sample Reference (Water samples only):</strong></label>
        <select id="sampleRef">
            <option value="">&mdash; Select sample &mdash;</option>
        </select>
    </p>
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
   <script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>
   
<script>
    const elementSymbolMap = {
        'h': 0, 'o': 1, 'n': 2, 'c': 3, 'na': 4,
        'k': 5, 'ca': 6, 'mg': 7, 'fe': 8, 'cu': 9,
        'pb': 10, 'zn': 11, 'mn': 12, 'cl': 13, 'f': 14,
        'b': 15, 's': 16, 'p': 17
    };

    const elementNameMap = {
        'hydrogen': 0, 'oxygen': 1, 'nitrogen': 2, 'carbon': 3, 'sodium': 4,
        'potassium': 5, 'calcium': 6, 'magnesium': 7, 'iron': 8, 'copper': 9,
        'lead': 10, 'zinc': 11, 'manganese': 12, 'chlorine': 13, 'fluorine': 14,
        'boron': 15, 'sulfur': 16, 'phosphorus': 17
    };

    function prefillFromURL() {
        const params = new URLSearchParams(window.location.search);
        params.forEach((value, key) => {
            const lowerKey = key.toLowerCase();
            let index = null;
            if (elementSymbolMap.hasOwnProperty(lowerKey)) {
                index = elementSymbolMap[lowerKey];
            } else if (elementNameMap.hasOwnProperty(lowerKey)) {
                index = elementNameMap[lowerKey];
            }
            if (index !== null) {
                const inputs = document.querySelectorAll('input[type="number"][data-atomic-weight]');
                if (inputs[index]) {
                    inputs[index].value = parseFloat(value) || 0;
                }
            }
        });
    }

    function loadWaterSamples() {
        fetch('ajax/list_water_samples.php')
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('sampleRef');
                sel.innerHTML = '<option value="">&mdash; Select sample &mdash;</option>';
                (data.samples || []).forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.SampleID;
                    opt.textContent = s.SampleID + (s.CustomerName ? ' - ' + s.CustomerName : '') + (s.Date ? ' (' + String(s.Date).split(' ')[0] + ')' : '');
                    sel.appendChild(opt);
                });
            })
            .catch(function () {});
    }

    function fillTDSFromSample(sampleID) {
        fetch('ajax/get_sample_ions.php?sampleID=' + encodeURIComponent(sampleID))
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const inputs = document.querySelectorAll('input[type="number"][data-atomic-weight]');
                Object.entries(data.tdsElements || {}).forEach(function (entry) {
                    const key = entry[0].toLowerCase();
                    const idx = elementNameMap[key];
                    if (idx !== undefined && inputs[idx]) {
                        inputs[idx].value = entry[1];
                    }
                });
            })
            .catch(function () {});
    }

    function calculateTDS() {
        let totalTDS = 0;
        $('input[type="number"]').each(function() {
            const concentration = parseFloat($(this).val()) || 0;
            const atomicWeight = parseFloat($(this).data('atomic-weight')) || 0;
            totalTDS += concentration * atomicWeight;
        });
        $('#tdsResult').text(totalTDS.toFixed(2) + ' mg/L');
    }

    window.addEventListener('DOMContentLoaded', prefillFromURL);

    window.addEventListener('DOMContentLoaded', function () {
        loadWaterSamples();
        const sel = document.getElementById('sampleRef');
        if (sel) {
            sel.addEventListener('change', function () {
                if (this.value) fillTDSFromSample(this.value);
            });
        }
    });
</script>
