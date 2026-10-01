<?php
session_write_close();
session_name('ErpWithCRM');
session_start();

$PathPrefix = '../';
include('../config.php');
include('../includes/ConnectDB_mysqli.inc');
include('../includes/SQL_CommonFunctions.inc');
include('../includes/DateFunctions.inc');
include('../includes/CashJournalClass.inc');

header('Content-Type: application/json');

function ajaxDB_query($sql, $conn) {
    return DB_query($sql, $conn, '', '', false, false);
}

function deleteGLPostings($journalno) {
    global $db;
    $jn = mysqli_real_escape_string($db, $journalno);
    ajaxDB_query("DELETE FROM Generalledger WHERE DocumentNo='$jn' AND DocumentType='0'", $db);
    ajaxDB_query("DELETE FROM BankTransactions WHERE DocumentNo='$jn'", $db);
    ajaxDB_query("DELETE FROM CustomerStatement WHERE Documentno='$jn'", $db);
    ajaxDB_query("DELETE FROM debtorsledger WHERE invref='$jn'", $db);
    ajaxDB_query("DELETE FROM SupplierStatement WHERE Documentno='$jn'", $db);
    ajaxDB_query("DELETE FROM creditorsledger WHERE invref='$jn'", $db);
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    case 'get_banks':
        $rows = [];
        $res = ajaxDB_query("SELECT accountcode, bankName FROM BankAccounts ORDER BY bankName", $db);
        while ($r = DB_fetch_array($res)) {
            $rows[] = ['value' => trim($r['accountcode']), 'label' => trim($r['bankName'])];
        }
        echo json_encode($rows);
        break;

    case 'get_personal_accounts':
        $type = $_GET['type'] ?? '';
        $rows = [];
        if ($type == 'debtors') {
            $res = ajaxDB_query("SELECT itemcode, customer FROM debtors ORDER BY customer", $db);
            while ($r = DB_fetch_array($res)) {
                $rows[] = ['value' => trim($r['itemcode']), 'label' => trim($r['customer'])];
            }
        } elseif ($type == 'creditors') {
            $res = ajaxDB_query("SELECT itemcode, customer FROM creditors WHERE IsEmployee IS NULL ORDER BY customer", $db);
            while ($r = DB_fetch_array($res)) {
                $rows[] = ['value' => trim($r['itemcode']), 'label' => trim($r['customer'])];
            }
        } elseif ($type == 'employee') {
            $res = ajaxDB_query("SELECT itemcode, customer FROM creditors WHERE IsEmployee = 1 ORDER BY customer", $db);
            while ($r = DB_fetch_array($res)) {
                $rows[] = ['value' => trim($r['itemcode']), 'label' => trim($r['customer'])];
            }
        } elseif ($type == 'bank') {
            $res = ajaxDB_query("SELECT accountcode, bankName FROM BankAccounts ORDER BY bankName", $db);
            while ($r = DB_fetch_array($res)) {
                $rows[] = ['value' => trim($r['accountcode']), 'label' => trim($r['bankName'])];
            }
        }
        echo json_encode($rows);
        break;

    case 'get_currencies':
        $rows = [];
        $res = ajaxDB_query("SELECT currabrev FROM currencies ORDER BY currabrev", $db);
        while ($r = DB_fetch_array($res)) {
            $rows[] = ['value' => trim($r['currabrev']), 'label' => trim($r['currabrev'])];
        }
        echo json_encode($rows);
        break;

    case 'get_dimensions':
        $dimId = (int)($_GET['dimid'] ?? 0);
        $rows = [['value' => '', 'label' => '--']];
        if ($dimId > 0) {
            $res = ajaxDB_query("SELECT `Code` AS value, `Dimension` AS label FROM `dimensions` WHERE `id` = $dimId AND (BLOCKED IS NULL OR BLOCKED = 0) ORDER BY `Dimension`", $db);
            while ($r = DB_fetch_array($res)) {
                $rows[] = $r;
            }
        }
        echo json_encode($rows);
        break;

    case 'get_entries':
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $rows = [];
        $res = ajaxDB_query("SELECT je.*,
                    (SELECT ba.accountcode FROM BankAccounts ba WHERE ba.PostingGroup = je.Account LIMIT 1) AS bankcode
                    FROM JournalEntries je
                    WHERE je.Account = je.BalAccount
                      AND je.Docdate >= '$startDate' AND je.Docdate <= '$endDate'
                    ORDER BY je.JournalNo, je.Docdate", $db);
        while ($r = DB_fetch_array($res)) {
            $rows[] = [
                'docdate' => $r['Docdate'],
                'journalno' => trim($r['JournalNo']),
                'currency' => trim($r['Currency']),
                'narration' => trim($r['narration'] ?? ''),
                'bankcode' => trim($r['bankcode'] ?? ''),
                'transtype' => trim($r['transtype'] ?? ''),
                'itemcode' => trim($r['itemcode'] ?? ''),
                'amount' => (float)$r['amount'],
                'dimension1' => trim($r['Dimension_1'] ?? ''),
                'dimension2' => trim($r['Dimension_2'] ?? ''),
            ];
        }
        echo json_encode($rows);
        break;

    case 'get_next_no':
        echo json_encode(['nextno' => GetTempNextNo(3)]);
        break;

    case 'save_cash_journal':
        $rowsData = json_decode($_POST['rows'] ?? '[]', true);
        if (empty($rowsData)) {
            echo json_encode(['success' => false, 'message' => 'No rows to save']);
            exit;
        }

        $groups = [];
        foreach ($rowsData as $row) {
            $gKey = $row['_groupKey'] ?? 'default';
            if (!isset($groups[$gKey])) {
                $groups[$gKey] = [];
                $groups[$gKey]['_header'] = $row;
            }
            $groups[$gKey]['_lines'][] = $row;
        }

        $savedNos = [];
        DB_Txn_Begin($db);

        foreach ($groups as $gKey => $group) {
            $header = $group['_header'];
            $lines = $group['_lines'];

            $_POST['date'] = ConvertSQLDate($header['docdate'] ?? date('Y-m-d'));
            $_POST['currency'] = $header['currency'] ?? $_SESSION['CompanyRecord']['currencydefault'];
            $_POST['comments'] = $header['narration'] ?? '';
            $_POST['DimensionOne'] = $header['dimension1'] ?? '';
            $_POST['DimensionTwo'] = $header['dimension2'] ?? '';

            $_SESSION['ManualNumber'] = 0;
            $_SESSION['CashJournalLines'] = [];

            foreach ($lines as $line) {
                $_SESSION['CashJournalLines'][] = [
                    'bankcode' => $line['bankcode'] ?? '',
                    'acctype' => $line['transtype'] ?? '',
                    'itemcode' => $line['itemcode'] ?? '',
                    'amount' => (float)($line['amount'] ?? 0),
                ];
            }

            $cj = new cashjournalentries();
            $sqlArray = $cj->SaveCashJournal();

            foreach ($sqlArray as $sql) {
                ajaxDB_query($sql, $db);
                if (DB_error_no($db) > 0) {
                    $errMsg = $db->error . ' | SQL: ' . $sql;
                    DB_Txn_Rollback($db);
                    echo json_encode(['success' => false, 'message' => $errMsg]);
                    exit;
                }
            }
            $savedNos[] = $_POST['JournalNo'];
        }

        DB_Txn_Commit($db);
        echo json_encode(['success' => true, 'journalnos' => $savedNos, 'message' => count($savedNos) . ' journal(s) saved']);
        break;

    case 'update_entry':
        $journalno = $_POST['journalno'] ?? '';
        $origTranstype = $_POST['orig_transtype'] ?? '';
        $origItemcode = $_POST['orig_itemcode'] ?? '';
        $origAmount = (float)($_POST['orig_amount'] ?? 0);

        $docdate = $_POST['docdate'] ?? '';
        $currency = $_POST['currency'] ?? '';
        $narration = $_POST['narration'] ?? '';
        $bankcode = $_POST['bankcode'] ?? '';
        $transtype = $_POST['transtype'] ?? '';
        $itemcode = $_POST['itemcode'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $dimension1 = $_POST['dimension1'] ?? '';
        $dimension2 = $_POST['dimension2'] ?? '';

        if (empty($journalno)) {
            echo json_encode(['success' => false, 'message' => 'JournalNo required']);
            exit;
        }

        // Resolve new bank posting group if bankcode changed
        $bankPG = '';
        if (!empty($bankcode)) {
            $cj = new cashjournalentries();
            $bankPG = $cj->PostingGroup(3, $bankcode);
        }

        $sql = "UPDATE JournalEntries SET
                    Docdate = '$docdate',
                    Currency = '" . mysqli_real_escape_string($db, $currency) . "',
                    narration = '" . mysqli_real_escape_string($db, $narration) . "',
                    " . ($bankPG ? "Account = '" . mysqli_real_escape_string($db, $bankPG) . "', BalAccount = '" . mysqli_real_escape_string($db, $bankPG) . "', " : "") . "
                    transtype = '" . mysqli_real_escape_string($db, $transtype) . "',
                    itemcode = '" . mysqli_real_escape_string($db, $itemcode) . "',
                    amount = $amount,
                    Dimension_1 = '" . mysqli_real_escape_string($db, $dimension1) . "',
                    Dimension_2 = '" . mysqli_real_escape_string($db, $dimension2) . "'
                WHERE JournalNo = '" . mysqli_real_escape_string($db, $journalno) . "'
                  AND transtype = '" . mysqli_real_escape_string($db, $origTranstype) . "'
                  AND itemcode = '" . mysqli_real_escape_string($db, $origItemcode) . "'
                  AND amount = $origAmount";

        if (ajaxDB_query($sql, $db)) {
            echo json_encode(['success' => true, 'message' => 'Entry updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        break;

    case 'delete_entry':
        $journalno = $_POST['journalno'] ?? '';
        $transtype = $_POST['transtype'] ?? '';
        $itemcode = $_POST['itemcode'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);

        if (empty($journalno)) {
            echo json_encode(['success' => false, 'message' => 'JournalNo required']);
            exit;
        }

        $sql = "DELETE FROM JournalEntries
                WHERE JournalNo = '" . mysqli_real_escape_string($db, $journalno) . "'
                  AND transtype = '" . mysqli_real_escape_string($db, $transtype) . "'
                  AND itemcode = '" . mysqli_real_escape_string($db, $itemcode) . "'
                  AND amount = $amount";

        if (ajaxDB_query($sql, $db)) {
            echo json_encode(['success' => true, 'message' => 'Entry deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        break;

    case 'rebuild_cash_journal':
        $journalno = $_POST['journalno'] ?? '';
        if (empty($journalno)) {
            echo json_encode(['success' => false, 'message' => 'JournalNo required']);
            exit;
        }

        $jesc = mysqli_real_escape_string($db, $journalno);
        $res = ajaxDB_query("SELECT je.*,
                    (SELECT ba.accountcode FROM BankAccounts ba WHERE ba.PostingGroup = je.Account LIMIT 1) AS bankcode
                    FROM JournalEntries je
                    WHERE je.JournalNo='$jesc' AND je.Account = je.BalAccount
                    ORDER BY je.Docdate", $db);
        $rows = [];
        while ($r = DB_fetch_array($res)) {
            $rows[] = $r;
        }

        DB_Txn_Begin($db);
        deleteGLPostings($journalno);

        if (empty($rows)) {
            DB_Txn_Commit($db);
            echo json_encode(['success' => true, 'message' => 'Journal ' . $journalno . ' removed from GL']);
            exit;
        }

        $_POST['date'] = ConvertSQLDate($rows[0]['Docdate']);
        $_POST['currency'] = $rows[0]['Currency'];
        $_POST['comments'] = $rows[0]['narration'] ?? '';
        $_POST['DimensionOne'] = $rows[0]['Dimension_1'] ?? '';
        $_POST['DimensionTwo'] = $rows[0]['Dimension_2'] ?? '';
        $_POST['JournalNo'] = $journalno;

        $_SESSION['ManualNumber'] = 1;
        $_SESSION['CashJournalLines'] = [];

        foreach ($rows as $r) {
            $_SESSION['CashJournalLines'][] = [
                'bankcode' => trim($r['bankcode'] ?? ''),
                'acctype' => trim($r['transtype']),
                'itemcode' => trim($r['itemcode']),
                'amount' => (float)$r['amount'],
            ];
        }

        $cj = new cashjournalentries();
        $sqlArray = $cj->SaveCashJournal();

        foreach ($sqlArray as $sql) {
            ajaxDB_query($sql, $db);
            if (DB_error_no($db) > 0) {
                $errMsg = $db->error . ' | SQL: ' . $sql;
                DB_Txn_Rollback($db);
                echo json_encode(['success' => false, 'message' => 'Rebuild failed: ' . $errMsg]);
                exit;
            }
        }

        DB_Txn_Commit($db);
        echo json_encode(['success' => true, 'message' => 'Journal ' . $journalno . ' rebuilt']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
