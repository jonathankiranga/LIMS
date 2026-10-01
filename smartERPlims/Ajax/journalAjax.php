<?php
session_write_close();
session_name('ErpWithCRM');
session_start();

$PathPrefix = '../';
include('../config.php');
include('../includes/ConnectDB_mysqli.inc');
include('../includes/SQL_CommonFunctions.inc');
include('../includes/DateFunctions.inc');
include('../includes/JournalClass.inc');

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

    case 'get_entries':
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $rows = [];
        $res = ajaxDB_query("SELECT je.*,
                                acct.accdesc AS account_description,
                                CASE je.transtype
                                    WHEN 'debtors'    THEN (SELECT customer FROM debtors WHERE itemcode = je.itemcode)
                                    WHEN 'creditors'  THEN (SELECT customer FROM creditors WHERE itemcode = je.itemcode AND IsEmployee IS NULL)
                                    WHEN 'employee'   THEN (SELECT customer FROM creditors WHERE itemcode = je.itemcode AND IsEmployee = 1)
                                    WHEN 'bank'       THEN (SELECT bankName FROM BankAccounts WHERE accountcode = je.itemcode)
                                    WHEN 'GL'         THEN (SELECT accdesc FROM acct WHERE accno = je.itemcode)
                                    WHEN 'fixedassets' THEN (SELECT description FROM fixedassets WHERE assetid = je.itemcode)
                                END AS name_display
                         FROM JournalEntries je
                         LEFT JOIN acct ON je.account = acct.accno
                         WHERE je.Docdate >= '$startDate' AND je.Docdate <= '$endDate'
                         ORDER BY je.JournalNo, je.Docdate", $db);
        while ($r = DB_fetch_array($res)) {
            $rows[] = [
                'docdate' => $r['Docdate'],
                'journalno' => trim($r['JournalNo']),
                'currency' => trim($r['Currency']),
                'narration' => trim($r['narration'] ?? ''),
                'account' => trim($r['Account']),
                'cracount' => trim($r['BalAccount'] ?? ''),
                'account_description' => trim($r['account_description'] ?? ''),
                'transtype' => trim($r['transtype'] ?? ''),
                'itemcode' => trim($r['itemcode'] ?? ''),
                'name_display' => trim($r['name_display'] ?? ''),
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

    case 'get_gl_accounts':
        $rows = [];
        $res = ajaxDB_query("SELECT accno, accdesc, balance_income FROM acct
                         WHERE ReportStyle=0 AND direct=1 AND inactive=0
                         ORDER BY accdesc", $db);
        while ($r = DB_fetch_array($res)) {
            $label = $r['accdesc'] . ' (' . ($r['balance_income'] == 0 ? 'BS' : ($r['balance_income'] == 1 ? 'P&L' : 'Suspense')) . ')';
            $rows[] = ['value' => trim($r['accno']), 'label' => $label];
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
        } elseif ($type == 'GL') {
            $res = ajaxDB_query("SELECT accno, accdesc FROM acct WHERE ReportStyle=0 AND direct=1 AND inactive=0 ORDER BY accdesc", $db);
            while ($r = DB_fetch_array($res)) {
                $rows[] = ['value' => trim($r['accno']), 'label' => trim($r['accdesc'])];
            }
        } elseif ($type == 'fixedassets') {
            $res = ajaxDB_query("SELECT assetid, description FROM fixedassets WHERE disposaldate IS NULL ORDER BY description", $db);
            while ($r = DB_fetch_array($res)) {
                $rows[] = ['value' => trim($r['assetid']), 'label' => trim($r['assetid']) . ' - ' . trim($r['description'])];
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

    case 'save_new_journal':
        $rowsData = json_decode($_POST['rows'] ?? '[]', true);
        if (empty($rowsData)) {
            echo json_encode(['success' => false, 'message' => 'No rows to save']);
            exit;
        }

        // Group rows by temporary journal no (each group = one journal voucher)
        $groups = [];
        foreach ($rowsData as $row) {
            $gKey = $row['_groupKey'] ?? 'default';
            if (!isset($groups[$gKey])) {
                $groups[$gKey] = [];
                // First row in group defines header fields
                $groups[$gKey]['_header'] = $row;
            }
            $groups[$gKey]['_lines'][] = $row;
        }

        $savedNos = [];
        $errors = [];
        DB_Txn_Begin($db);

        foreach ($groups as $gKey => $group) {
            $header = $group['_header'];
            $lines = $group['_lines'];

            // Set up POST for SaveJournal — grid date is Y-m-d, convert to locale format
            $_POST['date'] = ConvertSQLDate($header['docdate'] ?? date('Y-m-d'));
            $_POST['currency'] = $header['currency'] ?? $_SESSION['CompanyRecord']['currencydefault'];
            $_POST['comments'] = $header['narration'] ?? '';
            $_POST['DimensionOne'] = $header['dimension1'] ?? '';
            $_POST['DimensionTwo'] = $header['dimension2'] ?? '';

            $_SESSION['ManualNumber'] = 0; // Auto-assign the real number
            $_SESSION['JournalEntryDetails'] = [];

            foreach ($lines as $line) {
                $itemcode = $line['itemcode'] ?? '';
                $_SESSION['JournalEntryDetails'][] = [
                    'account' => $line['account'] ?? '',
                    'cracount' => $line['cracount'] ?? '',
                    'accountdescription' => '',
                    'acctype' => $line['transtype'] ?? '',
                    'itemcode' => $itemcode,
                    'amount' => (float)($line['amount'] ?? 0),
                    'assetid' => ($line['transtype'] == 'fixedassets' ? (int)$itemcode : 0),
                ];
            }

            $_POST['JournalNo'] = $header['journalno'] ?? '';

            $journal = new journalentries();
            $sqlArray = $journal->SaveJournal();

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
        $origAccount = $_POST['orig_account'] ?? '';
        $origCracount = $_POST['orig_cracount'] ?? '';
        $origItemcode = $_POST['orig_itemcode'] ?? '';
        $origAmount = (float)($_POST['orig_amount'] ?? 0);

        $docdate = $_POST['docdate'] ?? '';
        $currency = $_POST['currency'] ?? '';
        $narration = $_POST['narration'] ?? '';
        $account = $_POST['account'] ?? '';
        $cracount = $_POST['cracount'] ?? '';
        $transtype = $_POST['transtype'] ?? '';
        $itemcode = $_POST['itemcode'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $dimension1 = $_POST['dimension1'] ?? '';
        $dimension2 = $_POST['dimension2'] ?? '';

        if (empty($journalno)) {
            echo json_encode(['success' => false, 'message' => 'JournalNo required']);
            exit;
        }

        $sql = "UPDATE JournalEntries SET
                    Docdate = '$docdate',
                    Currency = '" . mysqli_real_escape_string($db, $currency) . "',
                    narration = '" . mysqli_real_escape_string($db, $narration) . "',
                    Account = '" . mysqli_real_escape_string($db, $account) . "',
                    BalAccount = '" . mysqli_real_escape_string($db, $cracount) . "',
                    transtype = '" . mysqli_real_escape_string($db, $transtype) . "',
                    itemcode = '" . mysqli_real_escape_string($db, $itemcode) . "',
                    amount = $amount,
                    Dimension_1 = '" . mysqli_real_escape_string($db, $dimension1) . "',
                    Dimension_2 = '" . mysqli_real_escape_string($db, $dimension2) . "'
                WHERE JournalNo = '" . mysqli_real_escape_string($db, $journalno) . "'
                  AND Account = '" . mysqli_real_escape_string($db, $origAccount) . "'
                  AND BalAccount = '" . mysqli_real_escape_string($db, $origCracount) . "'
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
        $account = $_POST['account'] ?? '';
        $cracount = $_POST['cracount'] ?? '';
        $itemcode = $_POST['itemcode'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);

        if (empty($journalno)) {
            echo json_encode(['success' => false, 'message' => 'JournalNo required']);
            exit;
        }

        $sql = "DELETE FROM JournalEntries
                WHERE JournalNo = '" . mysqli_real_escape_string($db, $journalno) . "'
                  AND Account = '" . mysqli_real_escape_string($db, $account) . "'
                  AND BalAccount = '" . mysqli_real_escape_string($db, $cracount) . "'
                  AND itemcode = '" . mysqli_real_escape_string($db, $itemcode) . "'
                  AND amount = $amount";

        if (ajaxDB_query($sql, $db)) {
            echo json_encode(['success' => true, 'message' => 'Entry deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        break;

    case 'rebuild_journal':
        $journalno = $_POST['journalno'] ?? '';
        if (empty($journalno)) {
            echo json_encode(['success' => false, 'message' => 'JournalNo required']);
            exit;
        }

        $jesc = mysqli_real_escape_string($db, $journalno);
        $res = ajaxDB_query("SELECT * FROM JournalEntries WHERE JournalNo='$jesc' ORDER BY Docdate", $db);
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
        $_SESSION['JournalEntryDetails'] = [];

        foreach ($rows as $r) {
            $balAccount = trim($r['BalAccount']);
            $account = trim($r['Account']);
            $transtype = trim($r['transtype']);
            $itemcode = trim($r['itemcode']);

            if ($balAccount === $account && ($transtype === 'GL' || $transtype === 'fixedassets')) {
                $cracount = $itemcode;
            } else {
                $cracount = $balAccount;
            }

            $_SESSION['JournalEntryDetails'][] = [
                'account' => $account,
                'cracount' => $cracount,
                'accountdescription' => '',
                'acctype' => $transtype,
                'itemcode' => $itemcode,
                'amount' => (float)$r['amount'],
                'assetid' => ($transtype === 'fixedassets' ? (int)$itemcode : 0),
            ];
        }

        $journal = new journalentries();
        $sqlArray = $journal->SaveJournal();

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
