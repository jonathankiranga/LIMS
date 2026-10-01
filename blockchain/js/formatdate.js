        $(document).ready(function () {
            let dateFormat = 'YYYY-MM-DD'; // Default format (fallback)
           // Fetch date format from the config table
            $.ajax({
                url: 'ajax/get_date_format.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        dateFormat = response.format; // Set the format dynamically
                        console.log(`Date format loaded: ${dateFormat}`);
                    } else {
                        console.error(response.message);
                    }
                },
                error: function () {
                    console.error('Error fetching date format.');
                }
            });
         
            // Utility function to format date based on the dynamic format
            function formatDate(rawDate, format) {
                let dateParts = rawDate.split('-'); // Split YYYY-MM-DD
                let year = dateParts[0];
                let month = dateParts[1];
                let day = dateParts[2];

                switch (format) {
                    case 'dd-mm-yy':
                        return `${day}-${month}-${year.slice(-2)}`;
                    case 'mm-dd-yy':
                        return `${month}-${day}-${year.slice(-2)}`;
                    case 'dd/mm/yyyy':
                        return `${day}/${month}/${year}`;
                    case 'mm/dd/yyyy':
                        return `${month}/${day}/${year}`;
                    case 'YYYY-MM-DD':
                    default:
                        return rawDate; // Default format
                }
            }
      
           // Update displayed date format when user selects a date
            $('.date').on('change', function () {
                let rawDate = $(this).val(); // Get value in YYYY-MM-DD
                if (rawDate) {
                    let formattedDate = formatDate(rawDate, dateFormat);
                    $('#formattedDate').text(formattedDate); // Update display
                } else {
                    $('#formattedDate').text('None');
                }
            });
        
        });
    