<?php
require '../db_connection.php';
header('Content-Type: application/json; charset=utf-8');
$page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;
$search = isset($_POST['search']) ? trim((string) $_POST['search']) : '';
$userFilter = isset($_POST['user']) ? trim((string) $_POST['user']) : '';
$where = array(); $params = array(); $types = '';
if ($userFilter !== '') {
    $where[] = '(UserName LIKE ? OR UserID LIKE ?)';
    $likeUser = '%' . $userFilter . '%';
    $params[] = $likeUser; $params[] = $likeUser; $types .= 'ss';
}
if ($search !== '') {
    $where[] = '(CAST(AuditID AS CHAR) LIKE ? OR CAST(resultsID AS CHAR) LIKE ? OR CAST(SampleID AS CHAR) LIKE ? OR ParameterName LIKE ? OR ResultType LIKE ? OR ResultValue LIKE ? OR ActionType LIKE ? OR UserName LIKE ? OR UserID LIKE ? OR CAST(CreatedAt AS CHAR) LIKE ?)';
    $like = '%' . $search . '%';
    for ($i = 0; $i < 10; $i++) { $params[] = $like; $types .= 's'; }
}
$whereSql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

function resultAuditBindParams($stmt, $types, &$values) {
    $bind = array();
    $bind[] = $types;
    foreach ($values as $key => &$value) {
        $bind[] = &$value;
    }
    return call_user_func_array(array($stmt, 'bind_param'), $bind);
}
$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM test_result_audit_trail' . $whereSql);
if (!$countStmt) { http_response_code(500); echo json_encode(array('success'=>false,'message'=>'Could not prepare audit count query.')); exit; }
if ($types !== '') resultAuditBindParams($countStmt, $types, $params);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$total = (int) ($countResult['total'] ?? 0);
$countStmt->close();
$sql = 'SELECT AuditID, CreatedAt, UserID, UserName, SampleID, ParameterName, ResultType, ResultValue, ActionType, resultsID FROM test_result_audit_trail' . $whereSql . ' ORDER BY CreatedAt DESC, AuditID DESC LIMIT ? OFFSET ?';
$stmt = $conn->prepare($sql);
if (!$stmt) { http_response_code(500); echo json_encode(array('success'=>false,'message'=>'Could not prepare result audit query.')); exit; }
if ($types !== '') {
    $allParams = $params; $allParams[] = $perPage; $allParams[] = $offset;
    $allTypes = $types . 'ii'; resultAuditBindParams($stmt, $allTypes, $allParams);
} else { $stmt->bind_param('ii', $perPage, $offset); }
if (!$stmt->execute()) { http_response_code(500); echo json_encode(array('success'=>false,'message'=>'Could not read result audit records.')); exit; }
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close(); $conn->close();
echo json_encode(array('success'=>true,'records'=>$rows,'page'=>$page,'perPage'=>$perPage,'total'=>$total,'totalPages'=>max(1,(int)ceil($total/$perPage))));
?>