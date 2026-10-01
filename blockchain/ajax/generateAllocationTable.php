<?php
require_once '../db_connection.php'; 
$conn->set_charset("utf8mb4");


if(isset($_POST['department']) && isset($_POST['testID'])){
$department = $conn->real_escape_string($_POST['department']); // Escape user input to prevent SQL injection
$testID     = $conn->real_escape_string($_POST['testID']); // Escape user input to prevent SQL injection

$stmt = $conn->prepare("SELECT tr.*,tp.*,ts.* 
    FROM test_results tr 
join testparameters tp on tp.ParameterID=tr.ParameterID and tp.StandardID=tr.StandardID
join teststandards  ts on ts.StandardID=tr.StandardID 
LEFT JOIN test_assignments SS 
     ON tr.resultsID = SS.resultsID and SS.category=?
WHERE SS.resultsID IS NULL
and  tp.Category = ? and tr.testID=?  ");
$stmt->bind_param("ssi",$department,$department,$testID);
$stmt->execute();
$result = $stmt->get_result();

$responseparameters = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $responseparameters[] = $row;
    }
}
$stmt->close();


$query = "select * from users  WHERE department = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s',$department);  // Assuming you ar
$stmt->execute();
$result = $stmt->get_result();

$responseusers = [];
if ($result->num_rows > 0) {
     while ($row = $result->fetch_assoc()) {
        $responseusers[] = $row;
    }
}

$stmt->close();    
$conn->close();
 
$htmlobject = '<div id="wordBox" class="word-box">';
foreach ($responseparameters as $para) {
   $htmlobject .= '<div class="draggable-word" id="word_'.$para['resultsID'].'" draggable="true" ondragstart="drag(event)">'.$para['ParameterName'].'</div>';
}
$htmlobject .= '</div>';
  
$htmlobject .= '<div><select id="userid" class="form-control"><option value="">select User</option>';
foreach ($responseusers as $value) {
    $htmlobject .= '<option value="'.$value['user_id'].'">'.$value['full_name'].'</option>';
}
  $htmlobject .= '</select></div>';
  
 $htmlobject .= '<div id="userBox" class="user-box" ondrop="drop(event)"  data-foruser=""  ondragover="allowDrop(event)">
    <p>Drag a Tests here</p>
</div>';

}
 

echo $htmlobject ;

