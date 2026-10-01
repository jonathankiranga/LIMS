<style>
  table { border-collapse: collapse; margin: 10px 0; }
  th, td { border: 1px solid #ccc; padding: 5px 10px; }
  input[type="number"] { width: 80px; }
</style>

<h2>Electro-Neutrality Calculator</h2>
<p>Enter the concentration for each ion (in meq/L):</p>
<table id="inputTable"></table>
<button onclick="calculateNeutrality()">Calculate Neutrality</button>

<h3>Cations</h3>
<table id="cationTable"></table>

<h3>Anions</h3>
<table id="anionTable"></table>

<h3>Results</h3>
<p><strong>Sum of Cations:</strong> <span id="cationSum"></span></p>
<p><strong>Sum of Anions:</strong> <span id="anionSum"></span></p>
<p><strong>Charge Imbalance:</strong> <span id="imbalance"></span></p>
<p><strong>Status:</strong> <span id="status"></span></p>


<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>

<script>
  
    // Define our array of common ions
    const ionsArray = [
      { ion_name: "Na⁺",   type: "Cation", weight: 22.99, charge: 1 },
      { ion_name: "K⁺",    type: "Cation", weight: 39.10, charge: 1 },
      { ion_name: "Ca²⁺",  type: "Cation", weight: 40.08, charge: 2 },
      { ion_name: "Mg²⁺",  type: "Cation", weight: 24.31, charge: 2 },
      { ion_name: "NH₄⁺",  type: "Cation", weight: 18.04, charge: 1 },
      { ion_name: "Cl⁻",   type: "Anion",  weight: 35.45, charge: -1 },
      { ion_name: "HCO₃⁻", type: "Anion",  weight: 61.02, charge: -1 },
      { ion_name: "NO₃⁻",  type: "Anion",  weight: 62.00, charge: -1 },
      { ion_name: "SO₄²⁻", type: "Anion",  weight: 96.06, charge: -2 },
      { ion_name: "CO₃²⁻", type: "Anion",  weight: 60.01, charge: -2 }
    ];

    // When the DOM is fully loaded, generate the table for user input
    document.addEventListener('DOMContentLoaded', function() {
      const inputTable = document.getElementById("inputTable");
      if (!inputTable) {
        console.error("Element with id 'inputTable' not found.");
        return;
      }
      
      // Set the table header
      inputTable.innerHTML = "<tr><th>Ion</th><th>Type</th><th>Weight (g/mol)</th><th>Charge</th><th>Concentration (meq/L)</th></tr>";
      
      // Loop over the ions array to create table rows
      ionsArray.forEach((ion, index) => {
        inputTable.innerHTML += `<tr>
          <td>${ion.ion_name}</td>
          <td>${ion.type}</td>
          <td>${ion.weight}</td>
          <td>${ion.charge}</td>
          <td>
            <input type="number" step="any" id="conc${index}" placeholder="0" />
          </td>
        </tr>`;
      });
    });

    function calculateNeutrality() {
      let sum_cations = 0;
      let sum_anions = 0;
      const cations = [];
      const anions = [];
      
      // Loop through each ion, reading the user provided concentration.
      ionsArray.forEach((ion, index) => {
        let inputValue = document.getElementById("conc" + index).value;
        let concentration = parseFloat(inputValue) || 0;
        ion.concentration = concentration;
        
        // Calculate meq = concentration / weight * |charge|
        const meq = (concentration / ion.weight) * Math.abs(ion.charge);
        if (ion.type.toLowerCase() === "cation") {
          sum_cations += meq;
          cations.push(ion);
        } else if (ion.type.toLowerCase() === "anion") {
          sum_anions += meq;
          anions.push(ion);
        }
      });
      
      const imbalance = Math.abs(sum_cations - sum_anions);
      const status = (imbalance <= 0.5) ? "Balanced" : "Imbalance detected";
      
      // Update the results in the HTML
      document.getElementById("cationSum").innerText = sum_cations.toFixed(2) + " meq/L";
      document.getElementById("anionSum").innerText = sum_anions.toFixed(2) + " meq/L";
      document.getElementById("imbalance").innerText = imbalance.toFixed(2) + " meq/L";
      document.getElementById("status").innerText = status;
      
      // Update the tables displaying which ions are cations and anions
      const cationTable = document.getElementById("cationTable");
      const anionTable = document.getElementById("anionTable");
      
      cationTable.innerHTML = "<tr><th>Ion</th><th>Charge</th><th>Concentration (meq/L)</th></tr>";
      anionTable.innerHTML = "<tr><th>Ion</th><th>Charge</th><th>Concentration (meq/L)</th></tr>";
      
      cations.forEach(ion => {
        cationTable.innerHTML += `<tr>
          <td>${ion.ion_name}</td>
          <td>${ion.charge}</td>
          <td>${ion.concentration}</td>
        </tr>`;
      });
      
      anions.forEach(ion => {
        anionTable.innerHTML += `<tr>
          <td>${ion.ion_name}</td>
          <td>${ion.charge}</td>
          <td>${ion.concentration}</td>
        </tr>`;
      });
    }
    
    // Expose the calculateNeutrality function globally so it can be called from the button
   
</script>