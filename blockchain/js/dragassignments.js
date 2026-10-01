let assignedTests = {}; // Object to hold assignments

function allowDrop(event) {
    event.preventDefault();
}

function drag(event) {
    event.dataTransfer.setData("text", event.target.id); // Pass the dragged item's ID
}

function drop(event) {
    event.preventDefault();

    const draggedID = event.dataTransfer.getData("text");
    const draggedElement = document.getElementById(draggedID);
    const userBox = document.getElementById('userBox');
    const selectedUser = document.getElementById('userid').value;

    if (!selectedUser) {
        alert("Please select a user before assigning tests.");
        return;
    }

    // Append the dragged item to the user box
    userBox.appendChild(draggedElement);

    // Add the assignment to the tracking object
    if (!assignedTests[selectedUser]) {
        assignedTests[selectedUser] = [];
    }
    assignedTests[selectedUser].push(draggedID.replace('word_', '')); // Store the resultsID
}

// Save the assigned tests to the database
function saveAssignedTests() {
    if (Object.keys(assignedTests).length === 0) {
        alert("No assignments to save.");
        return;
    }

    fetch('ajax/saveAssignments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(assignedTests), // Send assignments as JSON
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Assignments saved successfully.");
                assignedTests = {}; // Clear the assignments
            } else {
                alert("Error saving assignments: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An unexpected error occurred.");
        });
}
