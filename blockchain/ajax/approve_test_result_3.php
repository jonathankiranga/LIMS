<?php
require '../db_connection.php';
require_once '../functions/functions.php'; 
 
$columnMap = [
    // DB enum values
    'quantitativeField' => 'MRL_Result',
    'qualitativeField'  => 'ResultStatus',
    'rangeField'        => 'RangeResult',
    // Form field IDs
    'mrlResult'         => 'MRL_Result',
    'resultStatus'      => 'ResultStatus',
    'rangeResult'       => 'RangeResult'
];

$selected     = trim($_POST['resultType']);
$resultColumn = $columnMap[$selected] ?? '';
$resultValue  = $_POST[$selected];
$FLAG         = (int)$_POST['flag'];
$approvalStatus=(int)$_POST['approvalStatus'] ?? NULL;

$statusMapping = [
    1 => 4,  // Approved -> StatusID 3
    2 => 1,  // Reanalysis Required -> StatusID 1
    3 => 4,  // Reanalysis Required -> StatusID 1
    4 => 0   // Rejected -> StatusID 0
];

$approvalMapping = [
    2 => 'reviewedby',  // Approved -> StatusID 3
    3 => 'approvedby'
];

header('Content-Type: application/json');
if (isset($approvalStatus) && isset($statusMapping[$approvalStatus])) {
    $conn->autocommit(0);
    try {
        $stmt = $conn->prepare("UPDATE sample_tests
                                JOIN test_results ON sample_tests.TestID = test_results.TestID
                                SET sample_tests.datetestended = NOW()
                                WHERE test_results.resultsID = ?");
              if (!$stmt) {
                   echo json_encode(['success' => false, 'message' => 'Failed to prepare date update for  approval.']);
                   exit;
               }

           $stmt->bind_param("i",$_POST['resultsID']);
           if (!$stmt->execute()) {
                   echo json_encode(['success' => false, 'message' => 'Failed to execute date update for  approval.']);
                   exit;
               }else{
                   $stmt->close();
               }
               
           $statusID = $statusMapping[$approvalStatus];
           $level =(($approvalStatus==3)?'alteredby': $approvalMapping[$FLAG]);
           // Prepare and execute the update statement
           $stmt = $conn->prepare("UPDATE test_results SET $level=? , StatusID = ? WHERE resultsID = ?");
            if(!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare statement approval.']);
                exit;
            }

            $stmt->bind_param("sii",$_POST['user_id'],$statusID, $_POST['resultsID']);
            if (!$stmt->execute()) {
              echo json_encode(['success' => false, 'message' => 'Failed to execute approval.']);
              exit;
             }

           if($_POST['approvalStatus']==3){
               $stmt = $conn->prepare("UPDATE test_results SET MRL_Result = null,  ResultStatus = null, 
                   RangeResult = null, $resultColumn = ?,StatusID = 4 WHERE resultsID = ?");
               if (!$stmt) {
                   echo json_encode(['success' => false, 'message' => 'Failed to prepare correction statement.']);
                   exit;
               }

               $stmt->bind_param('si', $resultValue,$_POST['resultsID']);
               if (!$stmt->execute()) {
                   echo json_encode(['success' => false, 'message' => 'Failed to execute correction.']);
                   exit;
               }else{
                   $stmt->close();
               }
           }

   } catch (Exception $e) {
           $conn->rollback();
    } finally {
           $conn->commit();
    }
   $conn->autocommit(1);
   $conn->close();
   
   echo json_encode(['success' => true, 'message' => 'Results updated successfully.']);
 }else{
      echo json_encode(['success' => FALSE, 'message' => 'Something is wrong']);
 }