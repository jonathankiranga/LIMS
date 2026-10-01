
    function fetchTransactionData(action, TransType, seed = null) {
    // Prepare the POST data
    let data = {
        action: action,
        TransType: TransType
    };
    
    if (seed !== null) {
        data.seed = seed; // Add the seed parameter if provided
    }

    // Make the AJAX request
    // Use the Fetch API to send a POST request
          
    fetch('ajax/getrefferences.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json', // Specify JSON data
        },
        body: JSON.stringify(data), // Send the POST data as JSON
    })
        .then(response => response.json())
        .then(data => {
            // Handle the JSON response data
            if (data.status === 'success') {
                console.log('Success: Doc No', data.data);
               $('#documentno').val(data.data);
            } else {
                console.error('Error:', data.message);
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            // Handle any errors
            console.error('Fetch Error:', error);
            alert('Fetch Error: ' + error.message);
        });
}
 

