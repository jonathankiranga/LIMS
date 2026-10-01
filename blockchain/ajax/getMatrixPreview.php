<?php
require_once '../db_connection.php'; 

$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT 
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
        tp.matrixID,
        PM.ParameterName as matrixdesc
 FROM testparameters tp
 INNER JOIN TestStandards ts ON tp.StandardID = ts.StandardID
 left join ParameterMatrix PM on PM.ParameterID = tp.matrixID
 WHERE tp.matrixID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No related parameters.";
} else {
    echo "<ul style='margin:0; padding-left:1em'>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['ParameterName']) . "</li>";
    }
    echo "</ul>";
}
?>
