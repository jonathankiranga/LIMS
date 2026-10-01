<script>
  (function() {
    // Define our array of common ions inside a new local scope.
    const ions = [
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

    // When the page loads, generate the table for user input.
    window.onload = function() {
      const inputTable = document.getElementById("inputTable");
      inputTable.innerHTML = `
        <tr>
          <th>Ion</th>
          <th>Type</th>
          <th>Weight (g/mol)</th>
          <th>Charge</th>
          <th>Concentration (meq/L)</th>
        </tr>`;
      
      ions.forEach((ion, index) => {
        inputTable.innerHTML += `
          <tr>
            <td>${ion.ion_name}</td>
            <td>${ion.type}</td>
            <td>${ion.weight}</td>
            <td>${ion.charge}</td>
            <td>
              <input type="number" step="any" id="conc${index}" placeholder="0" />
            </td>
          </tr>`;
      });
    };

    function calculateNeutrality() {
      let sum_cations = 0;
      let sum_anions = 0;
      const cations = [];
      const anions = [];
      
      // Loop through each ion, reading the user-provided concentration.
      ions.forEach((ion, index) => {
        // Get the user-provided concentration value. If blank, default to 0.
        let inputValue = document.getElementById("conc" + index).value;
        let concentration = parseFloat(inputValue) || 0;
        // Optionally store the concentration back to the ion object.
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
      
      // Calculate the imbalance and set the status.
      const imbalance = Math.abs(sum_cations - sum_anions);
      const status = (imbalance <= 0.5) ? "Balanced" : "Imbalance detected";
      
      // Update the results in the HTML.
      document.getElementById("cationSum").innerText = sum_cations.toFixed(2) + " meq/L";
      document.getElementById("anionSum").innerText = sum_anions.toFixed(2) + " meq/L";
      document.getElementById("imbalance").innerText = imbalance.toFixed(2) + " meq/L";
      document.getElementById("status").innerText = status;
      
      // Update the tables displaying cations and anions.
      const cationTable = document.getElementById("cationTable");
      const anionTable = document.getElementById("anionTable");
      
      cationTable.innerHTML = `
        <tr>
          <th>Ion</th>
          <th>Charge</th>
          <th>Concentration (meq/L)</th>
        </tr>`;
      anionTable.innerHTML = `
        <tr>
          <th>Ion</th>
          <th>Charge</th>
          <th>Concentration (meq/L)</th>
        </tr>`;
      
      cations.forEach(ion => {
        cationTable.innerHTML += `
          <tr>
            <td>${ion.ion_name}</td>
            <td>${ion.charge}</td>
            <td>${ion.concentration}</td>
          </tr>`;
      });
      
      anions.forEach(ion => {
        anionTable.innerHTML += `
          <tr>
            <td>${ion.ion_name}</td>
            <td>${ion.charge}</td>
            <td>${ion.concentration}</td>
          </tr>`;
      });
    }

    // Expose calculateNeutrality to the global scope if needed.
    window.calculateNeutrality = calculateNeutrality;
  })();
</script>
