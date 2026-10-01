<?php
require_once '../db_connection.php'; 
$conn->set_charset("utf8mb4");

if(isset($_POST['testID'])){
$testID = $conn->real_escape_string($_POST['testID']); // Escape user input to prevent SQL injection

$stmt = $conn->prepare("SELECT tr.*,tp.*,ts.* 
    FROM test_results tr 
join testparameters tp on tp.ParameterID=tr.ParameterID and tp.StandardID=tr.StandardID
join teststandards ts on ts.StandardID=tr.StandardID 
LEFT JOIN test_assignments SS 
     ON tr.resultsID = SS.resultsID 
WHERE SS.resultsID IS NULL and tr.testID=?  ");
$stmt->bind_param("i",$testID);
$stmt->execute();
$result = $stmt->get_result();

$responseparameters = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $category = trim($row['Category']);
        $responseparameters[$category][] = $row;
    }
}
$stmt->close();


$query = "select * from subcontractors  WHERE inactive = ?";
$stmt = $conn->prepare($query);
$inactive='0';
$stmt->bind_param('d',$inactive);  // Assuming you ar
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


$colours = array('microbiological'=>'darkgreen','chemical'=>'#B58D2A');
$htmlobject = '<div id="wordBox" class="word-box">';
foreach ($responseparameters as $category =>  $para) {
   foreach ($para as $value) {
        $htmlobject .= '<div style="color:' .$colours[$category]. ';" '
           . 'class="draggable-word" id="word_'.$value['resultsID'].'" '
           . 'draggable="true" ondragstart="drag(event)">'.$value['ParameterName'].'</div>';
    }
}
$htmlobject .= '</div>';
$htmlobject .= '<div><select id="userid" class="form-control"><option value="">select Sub Contractor</option>';
foreach ($responseusers as $value) {
    $htmlobject .= '<option value="'.$value['id'].'">'.$value['name'].'</option>';
}
  $htmlobject .= '</select></div>';
  
 $htmlobject .= '<div id="userBox" class="user-box" ondrop="drop(event)"  data-foruser=""  ondragover="allowDrop(event)">
    <p>Drag a Tests here</p>
</div>';

}
 

echo $htmlobject ;

