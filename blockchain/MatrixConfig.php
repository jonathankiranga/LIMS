<link rel="stylesheet" href="css/quickbookslook.css">
<link rel="stylesheet" href="js/quilljs/1.3.7/quill.snow.css">
<link rel="stylesheet" href="css/reducedcss.css">
<link rel="stylesheet" href="css/modalcss.css">
<link rel="stylesheet" href="css/cooltables.css">
<link rel="stylesheet" href="css/typing.css"> 
<link rel="stylesheet" href="css/savematrixconfig.css">

<div class="container">
  <h2>Standard Parameter Matrix Configuration</h2>
 <form id="configForm">
    <div class="form-grid">

      <div class="form-group">
        <label for="matrixSelect">Matrix:</label>
      </div>
        
      <div class="form-group">
        <input type="text" id="MatrixName" name="MatrixName" autocomplete="off" onkeyup="handleMatrixInput(event)" class="form-control-sm" />
        <input type="hidden" id="MatrixID" name="matrix_id"/>
      </div>  

      <div class="form-group full-width">

      </div>
      <div class="form-group full-width">
        <div class="drag-drop-container">
          <table id="parameterTable" class="parameter-grid">
            <thead>
              <tr>
                <th><input type="checkbox" id="selectAllCheckbox" />Select All</th>
                <th>Parameter Name</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
       </div>
      </div>
         
      <div class="form-group full-width">
        <label for="notes">Notes:</label>
      </div>
      <div class="form-group full-width">
        <textarea id="notes" rows="3"></textarea>
      </div>   

      <div class="form-group button-group">
        <button type="button" onclick="saveConfig()">💾 Save Configuration</button>
      </div>
    </div>
  </form>

  <hr />

  <h3>📋 Current Configuration Entries</h3>
  <input type="text" id="searchBox" placeholder="Search..." style="margin-bottom:10px; width: 200px;" />

  <table id="configTable">
    <thead>
      <tr>
        <th>Matrix</th>
        <th>Parameter</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

  <div class="pagination">
    <button id="prevPage">⬅ Prev</button>
    <span id="pageInfo">Page 1</span>
    <button id="nextPage">Next ➡</button>
  </div>

  <div class="summary" id="summary">
    <strong>Total Configurations:</strong> <span id="totalConfigs">0</span>
  </div>
</div>

<script>
var parameterData = [];
var rowsPerPage = 5;
var currentPage = 1;
var currentSearch = '';

var tableBody = document.querySelector('#configTable tbody');
var totalConfigsEl = document.getElementById('totalConfigs');
var pageInfo = document.getElementById('pageInfo');
var searchBox = document.getElementById('searchBox');

// Master select-all checkbox handler
document.addEventListener("change", function (e) {
  if (e.target.id === "selectAllCheckbox") {
    const isChecked = e.target.checked;
    document
      .querySelectorAll("#parameterTable tbody input[type='checkbox']")
      .forEach(cb => (cb.checked = isChecked));
  }

  // If a row checkbox was clicked, update header checkbox state
  if (e.target.closest("#parameterTable tbody")) {
    syncSelectAllState();
  }
});

// Matrix search
async function handleMatrixInput(event) {
  const query = event.target.value;
  const inputId = event.target.id;
  const dropdownId = 'dropdown_' + inputId;
  const hiddenFieldId = inputId.replace('MatrixName','MatrixID');

  const existing = document.getElementById(dropdownId);
  if (existing) existing.remove();
  document.querySelectorAll('.dropdown').forEach(d => d.remove());

  const inputEl = document.getElementById(inputId);
  const dropdown = document.createElement('div');
  dropdown.id = dropdownId;
  dropdown.classList.add('dropdown');
  dropdown.style.width = '300px';
  document.body.appendChild(dropdown);

  const rect = inputEl.getBoundingClientRect();
  const scrollY = window.scrollY || document.documentElement.scrollTop;
  const scrollX = window.scrollX || document.documentElement.scrollLeft;
  dropdown.style.left = rect.left + scrollX + 'px';
  dropdown.style.top = rect.bottom + scrollY + 'px';
  dropdown.innerHTML = '';

  if (!query.trim()) {
    dropdown.style.display = 'none';
    return;
  }

  try {
    const resp = await fetch('ajax/searchmatrixes.php?q=' + encodeURIComponent(query));
    const matrices = await resp.json();

    if (matrices.length) {
      dropdown.style.display = 'block';
      matrices.forEach(mtx => {
        const div = document.createElement('div');
        div.textContent = mtx.ParameterName;
        div.dataset.code = mtx.ParameterID;
        div.addEventListener('click', () => {
          inputEl.value = mtx.FullPath;
          document.getElementById(hiddenFieldId).value = mtx.ParameterID;
          loadParameters(mtx.ParameterID)
          dropdown.style.display = 'none';
        });
        dropdown.appendChild(div);
      });
    } else {
      dropdown.style.display = 'none';
    }
  } catch (err) {
    console.error('Error fetching matrix parameters:', err);
  }
}

function renderSelectedParameterList(savedIDs = []) {
  const tbody = document.querySelector("#parameterTable tbody");
  tbody.innerHTML = '';

  parameterData.forEach(param => {
    const tr = document.createElement("tr");

    const tdCheck = document.createElement("td");
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.value = param.ParameterID;
    if (savedIDs.includes(param.ParameterID)) checkbox.checked = true;
    tdCheck.appendChild(checkbox);

    const tdName = document.createElement("td");
    tdName.textContent = param.ParameterName;

    tr.appendChild(tdCheck);
    tr.appendChild(tdName);
    tbody.appendChild(tr);
  });
}

function loadselectedParameters(matrixID = '') {
  fetch(`ajax/fetch_saved_config.php?matrix_id=${matrixID}`)
    .then(res => res.json())
    .then(data => {
      if (data.data.length) {
        const selectedIDs = data.data.map(item => item.ParameterID);
        renderSelectedParameterList(selectedIDs);
      } else {
        renderSelectedParameterList([]);
      }
    })
    .catch(err => {
      console.error("loadselectedParameters error:", err);
    });
}

function loadParameters(matrixID = '') {
  fetch(`ajax/fetchbaseparameters.php`)
    .then(res => res.json())
    .then(data => {
      parameterData = data.data;
      renderParameterList();
      loadselectedParameters(matrixID); 
    });
}

// Render parameter list with saved IDs checked
function renderParameterList(savedIDs = []) {
  const tbody = document.querySelector("#parameterTable tbody");
  tbody.innerHTML = '';

  parameterData.forEach(param => {
    const tr = document.createElement("tr");
    const tdCheck = document.createElement("td");
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.value = param.ParameterID;
    if (savedIDs.includes(param.ParameterID)) checkbox.checked = true;
    tdCheck.appendChild(checkbox);

    const tdName = document.createElement("td");
    tdName.textContent = param.ParameterName;

    tr.appendChild(tdCheck);
    tr.appendChild(tdName);
    tbody.appendChild(tr);
  });

  // Refresh select-all state after rendering
  syncSelectAllState();
}


// Keep header checkbox synced with row selections
function syncSelectAllState() {
  const checkboxes = document.querySelectorAll("#parameterTable tbody input[type='checkbox']");
  const allChecked = [...checkboxes].length > 0 && [...checkboxes].every(cb => cb.checked);
  document.getElementById("selectAllCheckbox").checked = allChecked;
}



function resetParameters() {
  document.querySelectorAll("#parameterTable tbody input[type='checkbox']")
    .forEach(cb => cb.checked = false);
}
 

function saveConfig() {
  const notes = document.getElementById("notes").value;
  const matrixId = document.getElementById("MatrixID").value;
  const selectedParameterIds = Array.from(document.querySelectorAll("#parameterTable tbody input[type='checkbox']:checked")).map(cb => cb.value);

  if (!matrixId) {
    alert("Please complete all fields.");
    return;
  }

  fetch("ajax/save_config.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      matrix_id: matrixId,
      parameter_ids: selectedParameterIds,
      notes: notes
    })
  })
  .then(res => res.json())
  .then(result => {
    alert(result.message);
    if (result.success) loadConfigTable();
  });
}
 
function loadConfigTable(page = 1, search = '') {
  const params = new URLSearchParams({
    page: page,
    limit: rowsPerPage,
    search: search
  });

  fetch(`ajax/fetchallsaved_configs.php?${params}`)
    .then(res => res.json())
    .then(data => {
      tableBody.innerHTML = '';

      data.rows.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${row.MatrixName}</td>
          <td>${row.ParameterName}</td>
          <td>${row.Notes}</td>
        `;
        tableBody.appendChild(tr);
      });

      currentPage = data.page;
      totalConfigsEl.textContent = data.total;
      const totalPages = Math.ceil(data.total / rowsPerPage);
      pageInfo.textContent = `Page ${currentPage} of ${totalPages || 1}`;
    });
}

document.getElementById("prevPage").addEventListener("click", () => {
  if (currentPage > 1) {
    currentPage--;
    loadConfigTable(currentPage, currentSearch);
  }
});

document.getElementById("nextPage").addEventListener("click", () => {
  const total = parseInt(totalConfigsEl.textContent);
  const totalPages = Math.ceil(total / rowsPerPage);
  if (currentPage < totalPages) {
    currentPage++;
    loadConfigTable(currentPage, currentSearch);
  }
});

searchBox.addEventListener("input", () => {
  currentSearch = searchBox.value.trim();
  currentPage = 1;
  loadConfigTable(currentPage, currentSearch);
});



loadParameters();

loadConfigTable(currentPage);
</script>
