<?php

class cashjournalentries {
    var $JournalArray = array();

    function getrates($fromcurrency, $tocurrency) {
        $FromRate = $this->ExtractRate($fromcurrency);
        $Torate = $this->ExtractRate($tocurrency);
        return ($Torate / $FromRate);
    }

    function ExtractRate($currbrieve) {
        global $db;
        $SQLOldRate = "SELECT rate FROM currencies WHERE currabrev = '" . $currbrieve . "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        return $myrow[0];
    }

    function GetBankDetails($accountCode) {
        global $db;
        $resultindex = DB_query("SELECT `BankAccounts`.`currency`,`currencies`.`rate`
            FROM `BankAccounts` join `currencies` on `BankAccounts`.`currency`=`currencies`.`currabrev`
            WHERE `accountcode`='" . $accountCode . "'", $db);
        $row = DB_fetch_row($resultindex);
        $BankArray['currency'] = $row[0];
        $BankArray['rate'] = $row[1];
        return $BankArray;
    }

    function GetCustomerCurrency($itemcode) {
        global $db;
        $ResultIndex = DB_query("SELECT `curr_cod` FROM `debtors` WHERE itemcode='" . $itemcode . "'", $db);
        $rows = DB_fetch_row($ResultIndex);
        return ['curr_cod' => $rows[0]];
    }

    function GetSupplierCurrency($itemcode) {
        global $db;
        $ResultIndex = DB_query("SELECT `curr_cod` FROM `creditors` WHERE itemcode='" . $itemcode . "'", $db);
        $rows = DB_fetch_row($ResultIndex);
        return ['curr_cod' => $rows[0]];
    }

    function PostingGroup($flag, $acount) {
        global $db;
        switch ($flag) {
            case 1:
                $SQL = "SELECT `postinggroups`.`debtorsaccount`
                    FROM `debtors`
                    JOIN `postinggroups` ON `debtors`.`customerposting` = `postinggroups`.`code`
                    WHERE `itemcode`='" . $acount . "'";
                break;
            case 2:
                $SQL = "SELECT `arpostinggroups`.`creditorsaccount`
                    FROM `creditors`
                    JOIN `arpostinggroups` ON `creditors`.`supplierposting` = `arpostinggroups`.`code`
                    WHERE `itemcode`='" . $acount . "'";
                break;
            case 3:
                $SQL = "SELECT `PostingGroup` FROM `BankAccounts` WHERE accountcode='" . $acount . "'";
                break;
            default:
                return '';
        }
        $ResultIndex = DB_query($SQL, $db);
        $pgrow = DB_fetch_row($ResultIndex);
        return $pgrow[0];
    }

    function SaveCashJournal() {
        global $db;

        if (($_SESSION['ManualNumber'] ?? 0) == 0) {
            $_POST['JournalNo'] = GetNextTransNo(3, $db);
        }
        $periodno = GetPeriod($_POST['date'], $db, true);
        $JOURNAL = GetNextTransNo(0, $db);

        $lines = $_SESSION['CashJournalLines'] ?? [];

        foreach ($lines as $row) {
            $bankPG = $this->PostingGroup(3, $row['bankcode']);
            $bankcode = $row['bankcode'];
            $acctype = $row['acctype'];
            $itemcode = $row['itemcode'];
            $amount = (float)$row['amount'];

            // JournalEntries INSERT — Account = BalAccount = bankPG (identifies as cash journal)
            $this->JournalArray[] = sprintf("INSERT INTO `JournalEntries`
                (`Docdate`,`JournalNo`,`Currency`,`Dimension_1`,`Dimension_2`,`Account`,`BalAccount`,`transtype`,`itemcode`,`narration`,`amount`)
                VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',%f)",
                FormatDateForSQL($_POST['date']), $_POST['JournalNo'], $_POST['currency'],
                $_POST['DimensionOne'] ?? '', $_POST['DimensionTwo'] ?? '',
                $bankPG, $bankPG, $acctype, $itemcode,
                $_POST['comments'], $amount);

            // BankTransactions (bank side — DR)
            $BankD = $this->GetBankDetails($bankcode);
            $Conrate = $this->getrates($BankD['currency'], $_POST['currency']);

            $this->JournalArray[] = sprintf("INSERT INTO `BankTransactions`
                (`bankcode`,`DocDate`,`doctype`,`DocumentNo`,`TransType`,`journal`,`amount`,`narrative`,`exchangerate`,`itemcode`)
                VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                $bankcode, FormatDateForSQL($_POST['date']), '0',
                $_POST['JournalNo'], $acctype, $JOURNAL,
                $amount * $Conrate, $_POST['comments'], $Conrate, $bankPG);

            // CR-side processing based on personal account type
            if ($acctype == 'bank') {
                $BankD2 = $this->GetBankDetails($itemcode);
                $Conrate = $this->getrates($BankD2['currency'], $_POST['currency']);
                $personalPG = $this->PostingGroup(3, $itemcode);

                if ($amount > 0) {
                    $debit = $bankPG;
                    $credit = $personalPG;
                } else {
                    $debit = $personalPG;
                    $credit = $bankPG;
                }

                $this->JournalArray[] = sprintf("INSERT INTO `Generalledger`
                    (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
                    `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode`,
                    `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
                    `dimension`,`dimension2`)
                    VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                    $JOURNAL, FormatDateForSQL($_POST['date']), $periodno,
                    $_POST['JournalNo'], '0', $debit, $credit, abs($amount),
                    $_POST['currency'], $Conrate, '', '', $itemcode, 0,
                    $_POST['comments'], '0', '', '0',
                    $_POST['DimensionOne'] ?? '', $_POST['DimensionTwo'] ?? '');

                $this->JournalArray[] = sprintf("INSERT INTO `BankTransactions`
                    (`bankcode`,`DocDate`,`doctype`,`DocumentNo`,`TransType`,`journal`,`amount`,`narrative`,`exchangerate`,`itemcode`)
                    VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                    $itemcode, FormatDateForSQL($_POST['date']), '0',
                    $_POST['JournalNo'], $acctype, $JOURNAL,
                    ($amount * $Conrate) * -1, $_POST['comments'], $Conrate, $bankPG);
            }

            if ($acctype == 'debtors') {
                $Cust = $this->GetCustomerCurrency($itemcode);
                $Conrate = $this->getrates($Cust['curr_cod'], $_POST['currency']);
                $personalPG = $this->PostingGroup(1, $itemcode);

                if ($amount > 0) {
                    $debit = $bankPG;
                    $credit = $personalPG;
                } else {
                    $debit = $personalPG;
                    $credit = $bankPG;
                }

                $this->JournalArray[] = sprintf("INSERT INTO `Generalledger`
                    (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
                    `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode`,
                    `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
                    `dimension`,`dimension2`)
                    VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                    $JOURNAL, FormatDateForSQL($_POST['date']), $periodno,
                    $_POST['JournalNo'], '0', $debit, $credit, abs($amount),
                    $_POST['currency'], $Conrate,
                    $itemcode, '', '', 0,
                    $_POST['comments'], '0', '', '0',
                    $_POST['DimensionOne'] ?? '', $_POST['DimensionTwo'] ?? '');

                $this->JournalArray[] = sprintf("INSERT INTO `CustomerStatement`
                    (`Date`,`Documentno`,`Documenttype`,`Accountno`,`Grossamount`,`JournalNo`,`Dimension_One`,`Dimension_Two`,`Currency`)
                    VALUES ('%s','%s','%s','%s',%f,'%s','%s','%s','%s')",
                    FormatDateForSQL($_POST['date']), $_POST['JournalNo'], '0', $itemcode,
                    ($amount * $Conrate) * -1, $JOURNAL,
                    $_POST['DimensionOne'] ?? '', $_POST['DimensionTwo'] ?? '', $_POST['currency']);

                $this->JournalArray[] = sprintf("INSERT INTO `debtorsledger`
                    (`date`,`details`,`flag`,`invref`,`acctfolio`,`amount`,`type`,`curr_cod`,`curr_rat`,`i_n_t`,`period`,`journal`,`typ`,`systypes_1`,`ledger`)
                    VALUES ('%s','%s','%s','%s','%s',%f,'%s','%s',%f,'%s','%s','%s','%s','%s','%s')",
                    FormatDateForSQL($_POST['date']), $_POST['comments'], 'DR', $_POST['JournalNo'],
                    $itemcode, ($amount * $Conrate) * -1, 'J', $_POST['currency'],
                    $Conrate, 'J', $periodno, $JOURNAL, 'J', '0', $bankPG);
            }

            if ($acctype == 'creditors' || $acctype == 'employee') {
                $Cust = $this->GetSupplierCurrency($itemcode);
                $Conrate = $this->getrates($Cust['curr_cod'], $_POST['currency']);
                $personalPG = $this->PostingGroup(2, $itemcode);

                if ($amount > 0) {
                    $debit = $bankPG;
                    $credit = $personalPG;
                } else {
                    $debit = $personalPG;
                    $credit = $bankPG;
                }

                $this->JournalArray[] = sprintf("INSERT INTO `Generalledger`
                    (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
                    `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode`,
                    `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
                    `dimension`,`dimension2`)
                    VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                    $JOURNAL, FormatDateForSQL($_POST['date']), $periodno,
                    $_POST['JournalNo'], '0', $debit, $credit, abs($amount),
                    $_POST['currency'], $Conrate,
                    '', $itemcode, '', 0,
                    $_POST['comments'], '0', '', '0',
                    $_POST['DimensionOne'] ?? '', $_POST['DimensionTwo'] ?? '');

                $this->JournalArray[] = sprintf("INSERT INTO `SupplierStatement`
                    (`Date`,`Documentno`,`Documenttype`,`Accountno`,`Grossamount`,`JournalNo`,`Dimension_One`,`Dimension_Two`,`Currency`)
                    VALUES ('%s','%s','%s','%s',%f,'%s','%s','%s','%s')",
                    FormatDateForSQL($_POST['date']), $_POST['JournalNo'], '0', $itemcode,
                    ($amount * $Conrate) * -1, $JOURNAL,
                    $_POST['DimensionOne'] ?? '', $_POST['DimensionTwo'] ?? '', $_POST['currency']);

                $this->JournalArray[] = sprintf("INSERT INTO `creditorsledger`
                    (`date`,`details`,`flag`,`invref`,`acctfolio`,`amount`,`type`,`curr_cod`,`curr_rat`,`i_n_t`,`period`,`journal`,`typ`,`systypes_1`,`ledger`)
                    VALUES ('%s','%s','%s','%s','%s',%f,'%s','%s',%f,'%s','%s','%s','%s','%s','%s')",
                    FormatDateForSQL($_POST['date']), $_POST['comments'], 'DR', $_POST['JournalNo'],
                    $itemcode, ($amount * $Conrate) * -1, 'J', $_POST['currency'],
                    $Conrate, 'J', $periodno, $JOURNAL, 'J', '0', $bankPG);
            }
        }

        return $this->JournalArray;
    }
}
