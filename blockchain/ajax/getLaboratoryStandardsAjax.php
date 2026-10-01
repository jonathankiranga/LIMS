<?php
// Simulating the database connection
$json_data = file_get_contents('php://input');
// Decode the JSON data into a PHP associative array
$data = json_decode($json_data, true); // 'true' converts it into an associative array

// Replace with your actual database logic
require '../db_connection.php'; 
 //{"sampleID":1,"Id":"LW005133","PricingType":3,"testID":20}


echo GetTests($data['sampleID'],$data['Id'],$data['PricingType'],$data['testID']);


function GetTests($sampletypeID, $Id, $PricingType,$testID=null) {
 global $conn;
        
       $dataarray = GetLaboratoryStandards($sampletypeID);
       if (is_array($dataarray)) {
      
        $results = '';
        if ($PricingType == 1) {
            $results .= '<div class="row row-cols-1 row-cols-md-3 gx-4">';
                 foreach ($dataarray as $rowdate) {
                    $RID  = $rowdate['ParameterID'];
                    $Name = html_entity_decode($rowdate['ParameterName']);
                    $vital =(bool) $rowdate['Vital'];
                    $results .= sprintf('<div class="col"><label class="checkbox-container">'
                    . '<input type="checkbox" name="TEST[%s][%s]" value="%s" %s>%s</label></div>',
                    $Id, $RID, $RID,($vital==true?'checked':''), $Name);
                }
            $results .= '</div>';
        }
        
        if ($PricingType == 2) {
            $results .= '<div class="row row-cols-1 row-cols-md-3 gx-4">';
             foreach ($dataarray as $rowdate) {
                $RID  = $rowdate['ParameterID'];
                $Name = html_entity_decode($rowdate['ParameterName']);
                $results .= sprintf('<div class="col"><label class="checkbox-container">'
                . '<input type="checkbox" name="TEST[%s][%s]" value="%s" >%s</label></div>',
                $Id, $RID, $RID, $Name);
            }
            $results .= '</div>';
        }
        
        if ($PricingType == 3) {
            $results .= '<div class="row row-cols-1 row-cols-md-3 gx-4">';
             foreach ($dataarray as $rowdate) {
                $paRID  = $rowdate['ParameterID'];
                $Name = html_entity_decode($rowdate['ParameterName']);
                $selected= getfromtesttable($paRID,$testID,$sampletypeID);
                $results .= sprintf('<div class="col"><label class="checkbox-container">'
                . '<input type="checkbox" name="TEST[%s][%s]" value="%s" %s>%s</label></div>',
                $Id,$paRID,$paRID,(($selected===true)?'checked':'') ,$Name);
            }
            $results .= '</div>';
        }


        if ($PricingType == 0) {
            $results = '<div class="row row-cols-1 row-cols-md-3 gx-4">'
                . '<div class="col themed-grid-col">Not yet Specified</div></div>';
        }
        
       } else {
           $results = '<div class="row row-cols-1 row-cols-md-3 gx-4">'
                . '<div class="col themed-grid-col">No data</div></div>';
       }
        return $results;
}
 
function getfromtesttable($RID,$testID,$sampleTypeID){
    global $conn;
    
    $checked=false;
    
    
    $stmtResults = $conn->prepare("SELECT * FROM test_results WHERE TestID = ? and `ParameterID`=? and `StandardID`=?");
    $stmtResults->bind_param("iii",$testID,$RID,$sampleTypeID);
    $stmtResults->execute();
    $resultsResult = $stmtResults->get_result();
   if($resultsResult->num_rows ==1 ) {
     $checked=true;
   }
   return $checked;         
}



function GetLaboratoryStandards($sampleTypeId) {
    global $conn;
  // Prepare the query
    $query = "SELECT 
        ts.StandardName, 
        tp.ParameterID,
        tp.StandardID,
        tp.ParameterName, 
        tp.MinLimit,
        tp.MaxLimit, 
        tp.Limits, 
        tp.Method, 
        tp.MRL,
        tp.MRLUnit, 
        tp.Category, 
        tp.Vital
 FROM testparameters tp
 INNER JOIN TestStandards ts ON tp.StandardID = ts.StandardID
WHERE tp.StandardID = ? order by Category asc";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        // Bind the parameter
        $stmt->bind_param("i", $sampleTypeId); // Assuming SampleTypeID is an integer
      // Execute the statement
        $stmt->execute();
      // Get the result
        $result = $stmt->get_result();
        $parameters = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $parameters[] = $row;
            }
        }

        // Return the data as an array
        return $parameters;
    } 
}

?>
