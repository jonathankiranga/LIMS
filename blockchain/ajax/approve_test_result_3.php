<?php
require '../db_connection.php';
require_once '../functions/functions.php';

header('Content-Type: application/json');

/* Whole-sample review block approval mode */
if (isset($_POST['reviewBlockApproval']) && (int)$_POST['reviewBlockApproval'] === 1) {
    $headerID=(int)($_POST['HeaderID']??0); $sampleID=trim($_POST['SampleID']??''); $flag=(int)($_POST['flag']??3); $approvalStatus=(int)($_POST['approvalStatus']??0); $userID=trim($_POST['user_id']??'');
    if($headerID<=0||$sampleID===''||$userID===''){echo json_encode(['success'=>false,'message'=>'Sample, header or user information is missing.']);exit;}
    if($flag!==3){echo json_encode(['success'=>false,'message'=>'Invalid review approval level.']);exit;}
    $statusMap=[1=>4,4=>0]; if(!isset($statusMap[$approvalStatus])){echo json_encode(['success'=>false,'message'=>'Invalid sample review decision.']);exit;}
    $statusID=$statusMap[$approvalStatus]; $conn->begin_transaction();
    try {
        $stmt=$conn->prepare("SELECT tr.TestID FROM test_results tr JOIN Sample_Header sp ON sp.HeaderID=tr.HeaderID WHERE tr.HeaderID=? AND sp.SampleID=? AND tr.StatusID=3");
        if(!$stmt)throw new Exception('Failed to prepare sample review lookup: '.$conn->error);
        $stmt->bind_param('is',$headerID,$sampleID); if(!$stmt->execute())throw new Exception('Failed to load sample review tests: '.$stmt->error);
        $rs=$stmt->get_result();$testIDs=[];while($r=$rs->fetch_assoc())$testIDs[]=(int)$r['TestID'];$stmt->close();
        if(!$testIDs)throw new Exception('No pending review tests were found for this sample block.');
        $stmt=$conn->prepare("UPDATE test_results SET approvedby=?, StatusID=? WHERE HeaderID=? AND StatusID=3");
        if(!$stmt)throw new Exception('Failed to prepare sample review update: '.$conn->error);
        $stmt->bind_param('sii',$userID,$statusID,$headerID);if(!$stmt->execute())throw new Exception('Failed to update sample review block: '.$stmt->error);$updated=$stmt->affected_rows;$stmt->close();
        if($updated!==count($testIDs))throw new Exception('Sample review changed during approval. No partial review block was committed.');
        $ph=implode(',',array_fill(0,count($testIDs),'?'));$stmt=$conn->prepare("UPDATE sample_tests SET datetestended=NOW() WHERE TestID IN ($ph)");
        if(!$stmt)throw new Exception('Failed to prepare review completion update: '.$conn->error);$types=str_repeat('i',count($testIDs));$bind=[$types];foreach($testIDs as $id)$bind[]=$id;$refs=[];foreach($bind as $k=>$v)$refs[$k]=&$bind[$k];call_user_func_array([$stmt,'bind_param'],$refs);if(!$stmt->execute())throw new Exception('Failed to update review completion dates: '.$stmt->error);$stmt->close();
        $privateKeyPath=__DIR__."/userkeys/".$userID."/private_key.pem";if(!file_exists($privateKeyPath))throw new Exception('Blockchain private key not found for user '.$userID.'. Approval was not committed.');$privateKey=file_get_contents($privateKeyPath);if($privateKey===false||trim($privateKey)==='')throw new Exception('Blockchain private key could not be read. Approval was not committed.');
        $decisionText=[1=>'APPROVED',4=>'REJECTED'][$approvalStatus];$ledgerPayload=['event'=>'SAMPLE_REVIEW_BLOCK_DECISION','sampleID'=>$sampleID,'HeaderID'=>$headerID,'decision'=>$approvalStatus,'decisionText'=>$decisionText,'testCount'=>$updated,'user_id'=>$userID,'timestamp'=>date('Y-m-d H:i:s')];
        $q=$conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");if(!$q)throw new Exception('Unable to read the previous blockchain block: '.$conn->error);$last=$q->fetch_assoc();$previousHash=$last?$last['current_hash']:str_repeat('0',64);
        $payloadJson=json_encode($ledgerPayload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($payloadJson===false)throw new Exception('Unable to create blockchain payload.');$dataString=$payloadJson.'|'.$previousHash;$currentHash=hash('sha256',$dataString);$digitalSignature=signData($currentHash,$privateKey);$encryptedData=encryptPrivateKey($dataString,$privateKey);
        $stmtLedger=$conn->prepare("INSERT INTO blockchain_ledger (timestamp,previous_hash,current_hash,digital_signature,encrypted_data,status,userid) VALUES (CURRENT_TIMESTAMP,?,?,?,?,'active',?)");if(!$stmtLedger)throw new Exception('Failed to prepare blockchain ledger entry: '.$conn->error);$stmtLedger->bind_param('sssss',$previousHash,$currentHash,$digitalSignature,$encryptedData,$userID);if(!$stmtLedger->execute())throw new Exception('Failed to write blockchain ledger entry: '.$stmtLedger->error);$blockId=$conn->insert_id;$stmtLedger->close();
        foreach($testIDs as $recordID)log_transaction_metadata($conn,$blockId,$recordID,'test_results');logAction($conn,'SAMPLE_REVIEW_BLOCK_'.$decisionText,$sampleID,$currentHash,$userID,'SUCCESS');$conn->commit();$word=[1=>'approved',4=>'rejected'][$approvalStatus];echo json_encode(['success'=>true,'message'=>'Sample '.$sampleID.' has been '.$word.' as a review block. '.$updated.' test result(s) updated.']);$conn->close();exit;
    }catch(Exception $e){$conn->rollback();$conn->close();echo json_encode(['success'=>false,'message'=>$e->getMessage()]);exit;}
}

/* Sample block approval mode */
if (isset($_POST['blockApproval']) && (int)$_POST['blockApproval'] === 1) {
    $headerID = (int)($_POST['HeaderID'] ?? 0);
    $sampleID = trim($_POST['SampleID'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $flag = (int)($_POST['flag'] ?? 3);
    $approvalStatus = (int)($_POST['approvalStatus'] ?? 0);
    $userID = trim($_POST['user_id'] ?? '');

    /*
     * IMPORTANT: department is supplied by the calling approval program.
     * It is NOT taken from the logged-in user's department because a
     * super-user/admin may legitimately have no department assignment.
     */
    $allowedDepartments = ['microbiological', 'chemical'];
    $department = strtolower($department);

    $statusMap = [1 => 4, 2 => 1, 4 => 0];

    if ($headerID <= 0 || $sampleID === '' || $userID === '') {
        echo json_encode(['success'=>false,'message'=>'Sample, header or user information is missing.']);
        exit;
    }
    if (!in_array($department, $allowedDepartments, true)) {
        echo json_encode(['success'=>false,'message'=>'Invalid department supplied by the calling program.']);
        exit;
    }
    if (!isset($statusMap[$approvalStatus])) {
        echo json_encode(['success'=>false,'message'=>'Invalid block decision.']);
        exit;
    }

    $statusID = $statusMap[$approvalStatus];
    $level = ($flag === 2) ? 'reviewedby' : 'approvedby';

    $conn->begin_transaction();

    try {
        $sql = "SELECT tr.TestID
                FROM test_results tr
                JOIN Sample_Header sp ON sp.HeaderID = tr.HeaderID
                JOIN testparameters tp
                  ON tp.ParameterID = tr.ParameterID
                 AND tp.StandardID = tr.StandardID
                WHERE tr.HeaderID = ?
                  AND sp.SampleID = ?
                  AND tr.StatusID = 2";
        $sql .= " AND tp.Category = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('Failed to prepare sample lookup: '.$conn->error);

        $stmt->bind_param('iss', $headerID, $sampleID, $department);

        if (!$stmt->execute()) throw new Exception('Failed to load sample tests: '.$stmt->error);

        $rs = $stmt->get_result();
        $testIDs = [];
        while ($r = $rs->fetch_assoc()) $testIDs[] = (int)$r['TestID'];
        $stmt->close();

        if (!$testIDs) throw new Exception('No pending tests were found for this sample block.');

        $sql = "UPDATE test_results tr
                JOIN testparameters tp
                  ON tp.ParameterID = tr.ParameterID
                 AND tp.StandardID = tr.StandardID
                SET tr.`$level` = ?, tr.StatusID = ?
                WHERE tr.HeaderID = ?
                  AND tr.StatusID = 2";
        $sql .= " AND tp.Category = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('Failed to prepare block update: '.$conn->error);

        $stmt->bind_param('siis', $userID, $statusID, $headerID, $department);

        if (!$stmt->execute()) throw new Exception('Failed to update sample block: '.$stmt->error);
        $updated = $stmt->affected_rows;
        $stmt->close();

        if ($updated <= 0) throw new Exception('No test results were updated.');

        $placeholders = implode(',', array_fill(0, count($testIDs), '?'));
        $sql = "UPDATE sample_tests SET datetestended = NOW() WHERE TestID IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('Failed to prepare completion update: '.$conn->error);

        $types = str_repeat('i', count($testIDs));
        $bind = [$types];
        foreach ($testIDs as $id) $bind[] = $id;
        $refs = [];
        foreach ($bind as $k => $v) $refs[$k] = &$bind[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);

        if (!$stmt->execute()) throw new Exception('Failed to update completion dates: '.$stmt->error);
        $stmt->close();

        /*
         * 4. Write ONE blockchain block for the complete sample decision.
         * The database approval and ledger entry are committed together.
         */
        $privateKeyPath = __DIR__ . "/userkeys/" . $userID . "/private_key.pem";
        if (!file_exists($privateKeyPath)) {
            throw new Exception('Blockchain private key not found for user ' . $userID . '. Approval was not committed.');
        }

        $privateKey = file_get_contents($privateKeyPath);
        if ($privateKey === false || trim($privateKey) === '') {
            throw new Exception('Blockchain private key could not be read. Approval was not committed.');
        }

        $ledgerPayload = [
            'event' => 'SAMPLE_TEST_BLOCK_DECISION',
            'sampleID' => $sampleID,
            'HeaderID' => $headerID,
            'department' => $department,
            'decision' => $approvalStatus,
            'decisionText' => [1=>'APPROVED',2=>'REANALYSIS_REQUIRED',4=>'REJECTED'][$approvalStatus],
            'testCount' => $updated,
            'user_id' => $userID,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $lastBlockQuery = $conn->query("SELECT current_hash FROM blockchain_ledger ORDER BY block_id DESC LIMIT 1");
        if (!$lastBlockQuery) {
            throw new Exception('Unable to read the previous blockchain block: ' . $conn->error);
        }
        $lastBlock = $lastBlockQuery->fetch_assoc();
        $previousHash = $lastBlock ? $lastBlock['current_hash'] : str_repeat('0', 64);

        $payloadJson = json_encode($ledgerPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false) {
            throw new Exception('Unable to create blockchain payload.');
        }

        $dataString = $payloadJson . '|' . $previousHash;
        $currentHash = hash('sha256', $dataString);
        $digitalSignature = signData($currentHash, $privateKey);
        $encryptedData = encryptPrivateKey($dataString, $privateKey);

        $stmtLedger = $conn->prepare(
            "INSERT INTO blockchain_ledger
             (timestamp, previous_hash, current_hash, digital_signature, encrypted_data, status, userid)
             VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, 'active', ?)"
        );
        if (!$stmtLedger) {
            throw new Exception('Failed to prepare blockchain ledger entry: ' . $conn->error);
        }

        $stmtLedger->bind_param(
            'sssss',
            $previousHash,
            $currentHash,
            $digitalSignature,
            $encryptedData,
            $userID
        );

        if (!$stmtLedger->execute()) {
            throw new Exception('Failed to write blockchain ledger entry: ' . $stmtLedger->error);
        }

        $blockId = $conn->insert_id;
        $stmtLedger->close();

        /*
         * Link every test result in this approval block to the same ledger block.
         */
        foreach ($testIDs as $recordID) {
            log_transaction_metadata($conn, $blockId, $recordID, 'test_results');
        }

        logAction(
            $conn,
            'SAMPLE_BLOCK_' . $ledgerPayload['decisionText'],
            $sampleID,
            $currentHash,
            $userID,
            'SUCCESS'
        );

        $conn->commit();
        $words = [1=>'approved',2=>'sent for reanalysis',4=>'rejected'];

        echo json_encode([
            'success'=>true,
            'message'=>'Sample '.$sampleID.' has been '.$words[$approvalStatus].' as a block. '.$updated.' test result(s) updated.'
        ]);
        $conn->close();
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        exit;
    }
}

/* Existing single-result mode retained for compatibility. */
$columnMap = [
    'quantitativeField'=>'MRL_Result',
    'qualitativeField'=>'ResultStatus',
    'rangeField'=>'RangeResult',
    'mrlResult'=>'MRL_Result',
    'resultStatus'=>'ResultStatus',
    'rangeResult'=>'RangeResult'
];

$selected = trim($_POST['resultType'] ?? '');
$resultColumn = $columnMap[$selected] ?? '';
$resultValue = $_POST[$selected] ?? '';
$flag = (int)($_POST['flag'] ?? 3);
$approvalStatus = (int)($_POST['approvalStatus'] ?? 0);

$statusMapping = [1=>4,2=>1,3=>4,4=>0];
$approvalMapping = [2=>'reviewedby',3=>'approvedby'];

if (!isset($statusMapping[$approvalStatus])) {
    echo json_encode(['success'=>false,'message'=>'Something is wrong']);
    exit;
}

$resultsID = (int)($_POST['resultsID'] ?? 0);
$userID = $_POST['user_id'] ?? '';
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE sample_tests
                            JOIN test_results ON sample_tests.TestID = test_results.TestID
                            SET sample_tests.datetestended = NOW()
                            WHERE test_results.resultsID = ?");
    if (!$stmt) throw new Exception('Failed to prepare date update.');
    $stmt->bind_param('i',$resultsID);
    if (!$stmt->execute()) throw new Exception('Failed to update test completion date.');
    $stmt->close();

    $statusID = $statusMapping[$approvalStatus];
    $level = ($approvalStatus === 3) ? 'alteredby' : ($approvalMapping[$flag] ?? 'approvedby');

    $stmt = $conn->prepare("UPDATE test_results SET `$level`=?, StatusID=? WHERE resultsID=?");
    if (!$stmt) throw new Exception('Failed to prepare approval update.');
    $stmt->bind_param('sii',$userID,$statusID,$resultsID);
    if (!$stmt->execute()) throw new Exception('Failed to execute approval.');
    $stmt->close();

    if ($approvalStatus === 3 && $resultColumn !== '') {
        $stmt = $conn->prepare("UPDATE test_results
                                SET MRL_Result=NULL, ResultStatus=NULL, RangeResult=NULL,
                                    `$resultColumn`=?, StatusID=4
                                WHERE resultsID=?");
        if (!$stmt) throw new Exception('Failed to prepare correction update.');
        $stmt->bind_param('si',$resultValue,$resultsID);
        if (!$stmt->execute()) throw new Exception('Failed to execute correction.');
        $stmt->close();
    }

    $conn->commit();
    $conn->close();
    echo json_encode(['success'=>true,'message'=>'Results updated successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}