<?php
$config = include('../include/config.php'); // Load the config file
// Database connection
$db_host   = $config['DB_HOST'];
$db_name   = $config['DB_NAME'];
$db_username = $config['DB_USERNAME'];
$db_password = $config['DB_PASSWORD'];
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get search term
$searchTerm = isset($_POST['searchTerm']) ? $mysqli->real_escape_string($_POST['searchTerm']) : '';

    // SQL query to fetch data based on search term
$sql = "SELECT 
            tr.*,
            st.*,
            sp.*,
            tp.*
        FROM 
            Test_Results tr
        JOIN 
            Sample_Tests st ON tr.TestID = st.TestID
        JOIN 
            Sample_Header sp ON tr.HeaderID = sp.HeaderID
        JOIN 
            TestParameters tp ON tr.`StandardID`=tp.StandardID AND tr.ParameterID=tp.ParameterID
        WHERE 
            tr.HeaderID LIKE '%$searchTerm%' and (tr.MRL_Result IS NOT NULL 
    or tr.ResultStatus IS NOT NULL  or tr.RangeResult IS NOT NULL)
        ORDER BY 
            tr.resultsID DESC";

$result = $mysqli->query($sql);
$response = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $SampleID=trim($row['SampleID']);
        $SampleFileKey=trim($row['SampleFileKey']);
        
            if(file_exists($SampleFileKey)){
                $fileInfo = pathinfo($SampleFileKey);
                $fileName = $fileInfo['filename'];     // Output: photo
                $extension = $fileInfo['extension'];   // Output: jpg
           
                $row['SampleFileKey']="uploads/$fileName.$extension";
            }else{
                $row['SampleFileKey']='uploads/icons8-no-image-100.png';
            }
       $response[] = $row;
    }
}
$mysqli->close();

header('Content-Type: application/json');
// Return JSON response
echo json_encode($response);


?>