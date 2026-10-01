<?php

/**
 * Trading / Balance Sheet Classes – MySQL Conversion
 *
 * Replaces MS-SQL stored-procedure pattern (dropprocedure / CREATE PROCEDURE / EXECUTE)
 * with direct MySQL queries, using the shared helpers:
 *   _getPeriodDates($yearPeriod)  – returns cur_start/cur_end/last_start/last_end
 *   _buildGLColumns($dates)       – builds the four CASE…END GL sub-select columns
 *   _acctGroupOrder()             – GROUP BY / ORDER BY tail
 *
 * Account-range boundaries (e.g. salesfrom/salesto) are fetched once from
 * Accountsetup per method and embedded as literals, eliminating the
 * @codefrom/@codeto variable pattern from the MSSQL procedures.
 *
 * Compatible: PHP 7.0+, MySQL 5.7+
 */

// ---------------------------------------------------------------------------
// Internal helper – fetch an account-range pair from Accountsetup
// ---------------------------------------------------------------------------

/**
 * Returns ['from' => '...', 'to' => '...'] for a given column-pair prefix.
 * e.g. _getAccountRange('sales') reads salesfrom / salesto.
 *
 * @param  string $prefix  Column name prefix (sales, stock, purchases, expense,
 *                         fixedassets, inventory, Debtors, Bank, CurLiab,
 *                         LongLiab, Capital)
 * @return array|false
 */
function _getAccountRange($prefix)
{
    global $db;
    $prefix = mysqli_real_escape_string($db, $prefix);
    $sql    = "SELECT `{$prefix}from`, `{$prefix}to` FROM Accountsetup LIMIT 1";
    $result = DB_query($sql, $db);
    $row    = DB_fetch_array($result);
    if (!$row) {
        return false;
    }
    return ['from' => $row["{$prefix}from"], 'to' => $row["{$prefix}to"]];
}

/**
 * Build the standard acct SELECT with GL sub-selects for a given date set
 * and account-range.  Returns a DB result resource or false.
 *
 * @param  array  $dates   From _getPeriodDates()
 * @param  string $from    ReportCode lower bound (escaped by caller)
 * @param  string $to      ReportCode upper bound (escaped by caller)
 * @param  string $extra   Optional extra WHERE clause fragment (e.g. "AND balance_income=1")
 * @return resource|false
 */
function _queryAccountRange($dates, $from, $to, $extra = '')
{
    global $db;
    $from = mysqli_real_escape_string($db, $from);
    $to   = mysqli_real_escape_string($db, $to);

    $sql = "SELECT ReportCode, accdesc, balance_income, ReportStyle,
                   direct, Sale_Purchase_Neither, accno, Calculation,
                   " . _buildGLColumns($dates) . "
            FROM acct
            WHERE ReportCode BETWEEN '$from' AND '$to'
            $extra
            " . _acctGroupOrder();

    return DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// TradingAccountbyMonth  (single-period / monthly P&L)
// ---------------------------------------------------------------------------

class TradingAccountbyMonth
{
    var $Calc              = array();
    var $Calc_LastYear     = array();
    var $Resource;
    var $Amount            = 0;
    var $Amount2           = 0;
    var $Amount2_lastyear  = 0;
    var $Amount_lastyear   = 0;
    var $begintotal        = 0;
    var $begintotal_lastyear = 0;
    var $starttotaling     = true;
    var $ResultDataSet     = array();
    var $COST_OF_SALES     = 0;
    var $Profit            = 0;

    // ------------------------------------------------------------------
    // Display / calculation helpers (unchanged from original)
    // ------------------------------------------------------------------

    function Get($rows = array())
    {
        if ($rows['ReportStyle'] == 3) {
            $_SESSION['chart_table']['starttotaling'] = true;
            $_SESSION['chart_table']['begintotal']    = 0;
        }

        if (($rows['ReportStyle'] == 0) && ($_SESSION['chart_table']['starttotaling'] == true)) {
            $_SESSION['chart_table']['begintotal'] += ($rows['amount']);
        }

        if ($rows['ReportStyle'] == 4) {
            $_SESSION['chart_table']['starttotaling'] = false;
            $this->Amount2 = $_SESSION['chart_table']['begintotal'];
            $_SESSION['chart_table']['begintotal'] = 0;
        } else {
            $this->Amount2 = ($rows['amount']);
        }

        $this->savetotal($rows['ReportCode'], $this->Amount2);

        if ((mb_strlen($rows['Calculation']) > 0) && ($rows['ReportStyle'] == 2)) {
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }

        return $this->Amount2;
    }

    function Reset()
    {
        unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling']         = false;
        $_SESSION['chart_table']['Amount_lastyear']       = 0;
        $_SESSION['chart_table']['Amount']                = 0;
        $_SESSION['chart_table']['begintotal']            = 0;
        $_SESSION['chart_table']['begintotal_lastyear']   = 0;
        $_SESSION['chart_table']['Calc']                  = array();
        $_SESSION['chart_table']['Calc_LastYear']         = array();
    }

    function GetTotal($formular)
    {
        $this->Amount = 0;
        $accounts = explode('+', $formular);
        foreach ($accounts as $code) {
            $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function AddTotals($account)
    {
        $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
        return $_SESSION['chart_table']['Amount'];
    }

    function savetotal($reportcode, $amount = 0)
    {
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount;
    }

    // ------------------------------------------------------------------
    // Main entry point
    // ------------------------------------------------------------------

    function Calmonthlydata()
    {
        $this->Reset();
        $this->Profit = 0;

        $this->DB_Profit_loss_by_month_sales();
        $this->COST_OF_SALES = 0;
        $this->DB_Profit_loss_by_month_OStock();
        $this->DB_Profit_loss_by_month_Purchase();
        $this->DB_Profit_loss_by_month_Clstock();
        $this->DB_Profit_loss_by_month_expenses();

        return $this->ResultDataSet;
    }

    // ------------------------------------------------------------------
    // Monthly GL columns helper
    // Uses $periodno (period number, not date) to build inline sub-selects.
    // Balance-sheet accounts: cumulative up to period; P&L: current period only.
    // ------------------------------------------------------------------

    /**
     * Build debit/credit GL columns keyed on period number (not date ranges).
     * Mirrors DB_Profit_loss_by_month() from the query functions file.
     *
     * @param  string $escapedPeriod  Escaped $periodno value
     * @return string
     */
    private function _buildMonthlyGLColumns($escapedPeriod)
    {
        return "
            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.period <= '$escapedPeriod')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.period = '$escapedPeriod')
            END AS debit,

            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.period <= '$escapedPeriod')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.period = '$escapedPeriod')
            END AS credit";
    }

    /**
     * Run a monthly query for a given Accountsetup range prefix.
     * Returns a DB result resource or false.
     */
    private function _monthlyQuery($rangePrefix, $extraWhere = '')
    {
        global $db, $periodno;

        $range = _getAccountRange($rangePrefix);
        if (!$range) {
            return false;
        }

        $escapedPeriod = mysqli_real_escape_string($db, $periodno);
        $from          = mysqli_real_escape_string($db, $range['from']);
        $to            = mysqli_real_escape_string($db, $range['to']);

        $sql = "SELECT ReportCode, accdesc, balance_income, ReportStyle,
                       direct, Sale_Purchase_Neither, accno, Calculation,
                       " . $this->_buildMonthlyGLColumns($escapedPeriod) . "
                FROM acct
                WHERE ReportCode BETWEEN '$from' AND '$to'
                $extraWhere
                " . _acctGroupOrder();

        return DB_query($sql, $db);
    }

    // ------------------------------------------------------------------
    // Section queries
    // ------------------------------------------------------------------

    function DB_Profit_loss_by_month_sales()
    {
        $ResultsX = $this->_monthlyQuery('sales');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']) * -1;
            $this->ResultDataSet[$Row['ReportCode']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['ReportCode']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['ReportCode']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['ReportCode']]['amount']      = $AMOUNT;
            $this->Profit += $AMOUNT;
        }
    }

    function DB_Profit_loss_by_month_OStock()
    {
        // Opening stock uses period < $periodno (prior periods only)
        global $db, $periodno;

        $range = _getAccountRange('stock');
        if (!$range) {
            return;
        }

        $escapedPeriod = mysqli_real_escape_string($db, $periodno);
        $from          = mysqli_real_escape_string($db, $range['from']);
        $to            = mysqli_real_escape_string($db, $range['to']);

        // Opening stock: always uses period < current (regardless of balance_income)
        $glCols = "
            (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
             WHERE gl.accountcode = acct.accno AND gl.period < '$escapedPeriod') AS debit,
            (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
             WHERE gl.balaccountcode = acct.accno AND gl.period < '$escapedPeriod') AS credit";

        $sql = "SELECT ReportCode, accdesc, balance_income, ReportStyle,
                       direct, Sale_Purchase_Neither, accno, Calculation,
                       $glCols
                FROM acct
                WHERE ReportCode BETWEEN '$from' AND '$to'
                " . _acctGroupOrder();

        $this->ResultDataSet['OS']['ReportCode']  = _('OS');
        $this->ResultDataSet['OS']['accdesc']     = _('Opening Stock');
        $this->ResultDataSet['OS']['ReportStyle'] = 2;

        $openstock = 0;
        $ResultsX  = DB_query($sql, $db);
        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$Row['ReportCode']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['ReportCode']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['ReportCode']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['ReportCode']]['amount']      = $AMOUNT;
            $this->COST_OF_SALES += $AMOUNT;
            $openstock           += $AMOUNT;
        }

        $this->ResultDataSet['OS_t']['ReportCode']  = _('OS');
        $this->ResultDataSet['OS_t']['accdesc']     = _('Opening Stock Total');
        $this->ResultDataSet['OS_t']['ReportStyle'] = 2;
        $this->ResultDataSet['OS_t']['amount']      = $openstock;
    }

    function DB_Profit_loss_by_month_Purchase()
    {
        $ResultsX = $this->_monthlyQuery('purchases');
        if (!$ResultsX) {
            return;
        }

        $this->ResultDataSet['Ps']['ReportCode']  = _('PS');
        $this->ResultDataSet['Ps']['accdesc']     = _('Add:Purchases');
        $this->ResultDataSet['Ps']['ReportStyle'] = 2;

        $openstock = 0;
        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$Row['ReportCode']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['ReportCode']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['ReportCode']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['ReportCode']]['amount']      = $AMOUNT;
            $this->COST_OF_SALES += $AMOUNT;
            $openstock           += $AMOUNT;
        }

        $this->ResultDataSet['Ps_t']['ReportCode']  = _('PS');
        $this->ResultDataSet['Ps_t']['accdesc']     = _('Total Purchases');
        $this->ResultDataSet['Ps_t']['ReportStyle'] = 2;
        $this->ResultDataSet['Ps_t']['amount']      = $openstock;
    }

    function DB_Profit_loss_by_month_Clstock()
    {
        // Closing stock uses period <= $periodno (same query as purchases/sales)
        $ResultsX = $this->_monthlyQuery('stock');
        if (!$ResultsX) {
            return;
        }

        $this->ResultDataSet['CS']['ReportCode']  = _('CS');
        $this->ResultDataSet['CS']['accdesc']     = _('Closing Stock');
        $this->ResultDataSet['CS']['ReportStyle'] = 2;

        $ClosingStock = 0;
        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$Row['accno']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['accno']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['accno']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['accno']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['accno']]['amount']      = $AMOUNT;
            $this->COST_OF_SALES -= $AMOUNT;   // subtract closing stock
            $ClosingStock        -= $AMOUNT;
        }

        $this->ResultDataSet['CS_t']['ReportCode']  = _('PS');
        $this->ResultDataSet['CS_t']['accdesc']     = _('Less Closing Stock');
        $this->ResultDataSet['CS_t']['ReportStyle'] = 2;
        $this->ResultDataSet['CS_t']['amount']      = $ClosingStock;

        $this->ResultDataSet['cos']['ReportCode']  = _('COS');
        $this->ResultDataSet['cos']['accdesc']     = _('Cost Of Sales');
        $this->ResultDataSet['cos']['ReportStyle'] = 2;
        $this->ResultDataSet['cos']['amount']      = $this->COST_OF_SALES;

        $this->Profit -= $this->COST_OF_SALES;
    }

    function DB_Profit_loss_by_month_expenses()
    {
        $ResultsX = $this->_monthlyQuery('expense');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$Row['ReportCode']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['ReportCode']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['ReportCode']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['ReportCode']]['amount']      = $AMOUNT;
            $this->Profit -= $AMOUNT;
        }

        $this->ResultDataSet['netprofit']['ReportCode']  = _('netprofit');
        $this->ResultDataSet['netprofit']['accdesc']     = _('Net Profit');
        $this->ResultDataSet['netprofit']['ReportStyle'] = 2;
        $this->ResultDataSet['netprofit']['amount']      = $this->Profit * -1;
    }
}

// ---------------------------------------------------------------------------
// TradingAccountForYear  (full-year P&L with prior-year comparison)
// ---------------------------------------------------------------------------

class TradingAccountForYear
{
    var $Calc                  = array();
    var $Calc_LastYear         = array();
    var $Resource;
    var $Amount                = 0;
    var $Amount2               = 0;
    var $Amount2_lastyear      = 0;
    var $Amount_lastyear       = 0;
    var $begintotal            = 0;
    var $begintotal_lastyear   = 0;
    var $starttotaling         = true;
    var $ResultDataSet         = array();
    var $COST_OF_SALES         = 0;
    var $Profit                = 0;
    var $COST_OF_SALES_last    = 0;
    var $Profit_last           = 0;

    // ------------------------------------------------------------------
    // Display / calculation helpers (unchanged from original)
    // ------------------------------------------------------------------

    function Get($rows = array())
    {
        if ($rows['ReportStyle'] == 3) {
            $_SESSION['chart_table']['starttotaling'] = true;
            $_SESSION['chart_table']['begintotal']    = 0;
        }

        if (($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
            && ($_SESSION['chart_table']['starttotaling'] == true)) {
            $_SESSION['chart_table']['begintotal'] += ($rows['amount']);
        }

        if ($rows['ReportStyle'] == 4) {
            $_SESSION['chart_table']['starttotaling'] = false;
            $this->Amount2 = $_SESSION['chart_table']['begintotal'];
            $_SESSION['chart_table']['begintotal'] = 0;
        } else {
            $this->Amount2 = ($rows['amount']);
        }

        $this->savetotal($rows['ReportCode'], $this->Amount2);

        if ((mb_strlen($rows['Calculation']) > 0) && ($rows['ReportStyle'] == 2)) {
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }

        return $this->Amount2;
    }

    function Get_last($rows = array())
    {
        if ($rows['ReportStyle'] == 3) {
            $_SESSION['chart_table_Last']['starttotaling'] = true;
            $_SESSION['chart_table_Last']['begintotal']    = 0;
        }

        if (($rows['ReportStyle'] == 0) && ($_SESSION['chart_table_Last']['starttotaling'] == true)) {
            $_SESSION['chart_table_Last']['begintotal'] += ($rows['Amount_lastyear']);
        }

        if ($rows['ReportStyle'] == 4) {
            $_SESSION['chart_table_Last']['starttotaling'] = false;
            $this->Amount2_lastyear = $_SESSION['chart_table_Last']['begintotal'];
            $_SESSION['chart_table_Last']['begintotal'] = 0;
        } else {
            $this->Amount2_lastyear = ($rows['Amount_lastyear']);
        }

        $this->savetotal_last($rows['ReportCode'], $this->Amount2);

        if ((mb_strlen($rows['Calculation']) > 0) && ($rows['ReportStyle'] == 2)) {
            $this->Amount2_lastyear = $this->GetTotal_last($rows['Calculation']);
        }

        return $this->Amount2_lastyear;
    }

    function Reset()
    {
        unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;
        $_SESSION['chart_table']['Amount']        = 0;
        $_SESSION['chart_table']['begintotal']    = 0;
        $_SESSION['chart_table']['Calc']          = array();

        unset($_SESSION['chart_table_Last']);
        $_SESSION['chart_table_Last']['starttotaling'] = false;
        $_SESSION['chart_table_Last']['Amount']        = 0;
        $_SESSION['chart_table_Last']['begintotal']    = 0;
        $_SESSION['chart_table_Last']['Calc']          = array();
    }

    function GetTotal($formular)
    {
        $this->Amount = 0;
        $accounts = explode('+', $formular);
        foreach ($accounts as $code) {
            $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function GetTotal_last($formular)
    {
        $this->Amount_lastyear = 0;
        $accounts = explode('+', $formular);
        foreach ($accounts as $code) {
            $this->Amount_lastyear = $this->AddTotals_last($code);
        }
        return $this->Amount_lastyear;
    }

    function AddTotals($account)
    {
        $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
        return $_SESSION['chart_table']['Amount'];
    }

    function AddTotals_last($account)
    {
        $_SESSION['chart_table_Last']['Amount'] += $_SESSION['chart_table_Last']['Calc'][$account];
        return $_SESSION['chart_table_Last']['Amount'];
    }

    function savetotal($reportcode, $amount = 0)
    {
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount;
    }

    function savetotal_last($reportcode, $amount = 0)
    {
        $_SESSION['chart_table_Last']['Calc'][$reportcode] = $amount;
    }

    // ------------------------------------------------------------------
    // Main entry point
    // ------------------------------------------------------------------

    function CalYearlydata()
    {
        $this->Reset();
        $this->Profit      = 0;
        $this->Profit_last = 0;

        $this->DB_Profit_loss_sales();
        $this->COST_OF_SALES      = 0;
        $this->COST_OF_SALES_last = 0;
        $this->DB_Profit_loss_OStock();
        $this->DB_Profit_loss_Purchase();
        $this->DB_Profit_loss_Clstock();
        $this->DB_Profit_loss_expenses();

        return $this->ResultDataSet;
    }

    // ------------------------------------------------------------------
    // Shared query runner for yearly sections
    //
    // Opening Stock is special: its "current year" data comes from
    // YearPeriod-12 (prior year end) and its "last year" from YearPeriod-24.
    // All other sections use YearPeriod for current and YearPeriod-12 for last.
    // ------------------------------------------------------------------

    /**
     * Run a yearly query for the given Accountsetup range prefix.
     *
     * @param  string $rangePrefix   e.g. 'sales', 'stock', 'purchases', 'expense'
     * @param  int    $currentPeriod periodno to use as "current year"
     * @param  int    $lastPeriod    periodno to use as "last year"
     * @return resource|false
     */
    private function _yearlyQuery($rangePrefix, $currentPeriod, $lastPeriod)
    {
        global $db;

        $range = _getAccountRange($rangePrefix);
        if (!$range) {
            return false;
        }

        $from = mysqli_real_escape_string($db, $range['from']);
        $to   = mysqli_real_escape_string($db, $range['to']);

        // Fetch date boundaries for both periods in one query
        $currentDates = _getPeriodDates($currentPeriod);
        $lastDates    = _getPeriodDates($lastPeriod);

        if (!$currentDates || !$lastDates) {
            return false;
        }

        // Build current-year GL columns using _buildGLColumns helper
        // then swap last_start/last_end to point at $lastPeriod dates
        $ccs = $currentDates['cur_start'];
        $cce = $currentDates['cur_end'];
        $lls = $lastDates['cur_start'];   // last year's own start
        $lle = $lastDates['cur_end'];     // last year's own end

        $glCurrent = "
            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$cce')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$ccs' AND '$cce')
            END AS debit,

            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$cce')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$ccs' AND '$cce')
            END AS credit,

            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$lle')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$lls' AND '$lle')
            END AS debit_Last,

            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$lle')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$lls' AND '$lle')
            END AS credit_Last";

        $sql = "SELECT ReportCode, accdesc, balance_income, ReportStyle,
                       direct, Sale_Purchase_Neither, accno, Calculation,
                       $glCurrent
                FROM acct
                WHERE ReportCode BETWEEN '$from' AND '$to'
                " . _acctGroupOrder();

        return DB_query($sql, $db);
    }

    // ------------------------------------------------------------------
    // Section queries
    // ------------------------------------------------------------------

    function DB_Profit_loss_sales()
    {
        global $YearPeriod;
        $YearPeriod = $_POST['Financial_Periods'];

        $ResultsX = $this->_yearlyQuery('sales', (int)$YearPeriod, (int)$YearPeriod - 12);
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']) * -1;
            $this->ResultDataSet[$Row['ReportCode']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['ReportCode']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['ReportCode']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['ReportCode']]['amount']      = $AMOUNT;
            $this->Profit += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']) * -1;
            $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT_last;
            $this->Profit_last += $AMOUNT_last;
        }
    }

    function DB_Profit_loss_OStock()
    {
        global $YearPeriod;

        // Opening stock for current year = balance at end of prior year (YearPeriod-12)
        // Opening stock for last year    = balance at end of two years ago  (YearPeriod-24)
        $ResultsX = $this->_yearlyQuery('stock', (int)$YearPeriod - 12, (int)$YearPeriod - 24);
        if (!$ResultsX) {
            return;
        }

        $this->ResultDataSet['OS']['ReportCode']  = _('OS');
        $this->ResultDataSet['OS']['accdesc']     = _('Opening Stock');
        $this->ResultDataSet['OS']['ReportStyle'] = 2;

        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$Row['ReportCode']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['ReportCode']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['ReportCode']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['ReportCode']]['amount']      = $AMOUNT;
            $this->COST_OF_SALES += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT_last;
            $this->COST_OF_SALES_last += $AMOUNT_last;
        }

        $this->ResultDataSet['OS_T']['ReportCode']       = _('OS');
        $this->ResultDataSet['OS_T']['accdesc']          = _('Total Opening Stock');
        $this->ResultDataSet['OS_T']['ReportStyle']      = 2;
        $this->ResultDataSet['OS_T']['amount']           = $this->COST_OF_SALES;
        $this->ResultDataSet['OS_T']['Amount_lastyear']  = $this->COST_OF_SALES_last;
    }

    function DB_Profit_loss_Purchase()
    {
        global $YearPeriod;

        $purchases     = 0;
        $lastpurchases = 0;

        $this->ResultDataSet['PS']['ReportCode']  = _('PS');
        $this->ResultDataSet['PS']['accdesc']     = _('PURCHASES');
        $this->ResultDataSet['PS']['ReportStyle'] = 2;

        $ResultsX = $this->_yearlyQuery('purchases', (int)$YearPeriod, (int)$YearPeriod - 12);
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$Row['ReportCode']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['ReportCode']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['ReportCode']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['ReportCode']]['amount']      = $AMOUNT;
            $this->COST_OF_SALES += $AMOUNT;
            $purchases           += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT_last;
            $this->COST_OF_SALES_last += $AMOUNT_last;
            $lastpurchases            += $AMOUNT_last;
        }

        $this->ResultDataSet['PS_T']['ReportCode']      = _('PS');
        $this->ResultDataSet['PS_T']['accdesc']         = _('TOTAL PURCHASES');
        $this->ResultDataSet['PS_T']['ReportStyle']     = 2;
        $this->ResultDataSet['PS_T']['amount']          = $purchases;
        $this->ResultDataSet['PS_T']['Amount_lastyear'] = $lastpurchases;
    }

    function DB_Profit_loss_Clstock()
    {
        global $YearPeriod;

        $Closingstock      = 0;
        $Closingstock_last = 0;

        $this->ResultDataSet['CS']['ReportCode']  = _('CS');
        $this->ResultDataSet['CS']['accdesc']     = _('Closing Stock');
        $this->ResultDataSet['CS']['ReportStyle'] = 2;

        $ResultsX = $this->_yearlyQuery('stock', (int)$YearPeriod, (int)$YearPeriod - 12);
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$Row['accno']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['accno']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['accno']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['accno']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['accno']]['amount']      = $AMOUNT;
            $Closingstock           += $AMOUNT;
            $this->COST_OF_SALES    -= $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$Row['accno']]['Amount_lastyear'] = $AMOUNT_last;
            $Closingstock_last            += $AMOUNT_last;
            $this->COST_OF_SALES_last     -= $AMOUNT_last;
        }

        $this->ResultDataSet['CS_t']['ReportCode']      = _('CS');
        $this->ResultDataSet['CS_t']['accdesc']         = _('LESS Closing Stock');
        $this->ResultDataSet['CS_t']['ReportStyle']     = 2;
        $this->ResultDataSet['CS_t']['amount']          = $Closingstock;
        $this->ResultDataSet['CS_t']['Amount_lastyear'] = $Closingstock_last;

        $this->ResultDataSet['cos']['ReportCode']      = _('COS');
        $this->ResultDataSet['cos']['accdesc']         = _('Cost Of Sales');
        $this->ResultDataSet['cos']['ReportStyle']     = 2;
        $this->ResultDataSet['cos']['amount']          = $this->COST_OF_SALES;
        $this->ResultDataSet['cos']['Amount_lastyear'] = $this->COST_OF_SALES_last;

        $this->Profit      -= $this->COST_OF_SALES;
        $this->Profit_last -= $this->COST_OF_SALES_last;
    }

    function DB_Profit_loss_expenses()
    {
        global $YearPeriod;

        $ResultsX = $this->_yearlyQuery('expense', (int)$YearPeriod, (int)$YearPeriod - 12);
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$Row['ReportCode']]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$Row['ReportCode']]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$Row['ReportCode']]['accno']       = $Row['accno'];
            $this->ResultDataSet[$Row['ReportCode']]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$Row['ReportCode']]['amount']      = $AMOUNT;
            $this->Profit -= $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$Row['ReportCode']]['Amount_lastyear'] = $AMOUNT_last;
            $this->Profit_last -= $AMOUNT_last;
        }

        $this->ResultDataSet['netprofit']['ReportCode']      = _('netprofit');
        $this->ResultDataSet['netprofit']['accdesc']         = _('Net Profit');
        $this->ResultDataSet['netprofit']['ReportStyle']     = 2;
        $this->ResultDataSet['netprofit']['amount']          = $this->Profit;
        $this->ResultDataSet['netprofit']['Amount_lastyear'] = $this->Profit_last;
    }
}

// ---------------------------------------------------------------------------
// ShowBankAccounts, Calculator, FinancialPeriods, MonthlyReports,
// MonthlyPeriods, SaveReceipt, SavePrepaymentReceipt, CalculateExchange
// — these classes contain no stored-procedure calls and are UNCHANGED.
// ---------------------------------------------------------------------------

class ShowBankAccounts
{
    var $BankObject = '';
    var $BankArray  = array();

    function __construct()
    {
        global $db;

        $this->BankObject = '<tr><td>Select Bank:</td><td><Select name="Bank_Code" required="required">';
        $resultindex = DB_query("SELECT accountcode, bankName, currency, lastreconcileddate,
                                        AccountNo, BranchCode, BranchName, lastreconbalance,
                                        lastChequeno, PostingGroup
                                 FROM BankAccounts", $db);

        while ($row = DB_fetch_array($resultindex)) {
            $selected = (isset($_POST['Bank_Code']) && trim($_POST['Bank_Code']) == trim($row['accountcode']))
                        ? 'selected="selected"' : '';
            $this->BankObject .= '<option value="' . $row['accountcode'] . '" ' . $selected . '>'
                               . $row['bankName'] . ' ' . $row['BranchName'] . ' ' . $row['currency']
                               . '</option>';
        }

        $this->BankObject .= '</select></td></tr>';
    }

    function Get()
    {
        echo $this->BankObject;
    }

    function GetBankDetails($accountCode)
    {
        global $db;
        $accountCode = mysqli_real_escape_string($db, $accountCode);
        $resultindex = DB_query("SELECT b.accountcode, b.bankName, b.currency, b.lastreconcileddate,
                                        b.AccountNo, b.BranchCode, b.BranchName, b.lastreconbalance,
                                        b.lastChequeno, b.PostingGroup, c.rate, b.StatementNo
                                 FROM BankAccounts b
                                 JOIN currencies c ON b.currency = c.currabrev
                                 WHERE b.accountcode = '$accountCode'", $db);

        $row = DB_fetch_row($resultindex);

        $this->BankArray['accountcode']       = $row[0];
        $this->BankArray['bankName']          = $row[1];
        $this->BankArray['currency']          = $row[2];
        $this->BankArray['lastreconcileddate'] = $row[3];
        $this->BankArray['AccountNo']         = $row[4];
        $this->BankArray['BranchCode']        = $row[5];
        $this->BankArray['BranchName']        = $row[6];
        $this->BankArray['lastreconbalance']  = $row[7];
        $this->BankArray['lastChequeno']      = $row[8];
        $this->BankArray['PostingGroup']      = $row[9];
        $this->BankArray['rate']              = $row[10];
        $this->BankArray['StatementNo']       = $row[11];

        return $this->BankArray;
    }
}

class Calculator
{
    var $Calc                = array();
    var $Calc_LastYear       = array();
    var $Resource;
    var $Amount              = 0;
    var $Amount2             = 0;
    var $Amount2_lastyear    = 0;
    var $Amount_lastyear     = 0;
    var $begintotal          = 0;
    var $begintotal_lastyear = 0;
    var $starttotaling       = true;

    function Get($rows = array())
    {
        if ($rows['ReportStyle'] == 3) {
            $_SESSION['chart_table']['starttotaling']       = true;
            $_SESSION['chart_table']['begintotal']          = 0;
            $_SESSION['chart_table']['begintotal_lastyear'] = 0;
        }

        if (($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
            && ($_SESSION['chart_table']['starttotaling'] == true)) {
            $_SESSION['chart_table']['begintotal']          += ($rows['debit'] - $rows['credit']);
            $_SESSION['chart_table']['begintotal_lastyear'] += ($rows['debit_Last'] - $rows['credit_Last']);
        }

        if ($rows['ReportStyle'] == 4) {
            $_SESSION['chart_table']['starttotaling'] = false;
            $this->Amount2          = $_SESSION['chart_table']['begintotal'];
            $this->Amount2_lastyear = $_SESSION['chart_table']['begintotal_lastyear'];
        } else {
            $this->Amount2          = ($rows['debit'] - $rows['credit']);
            $this->Amount2_lastyear = ($rows['debit_Last'] - $rows['credit_Last']);
        }

        $this->savetotal($rows['ReportCode'], $this->Amount2);
        $this->savetotal_lastyear($rows['ReportCode'], $this->Amount2_lastyear);

        if ((mb_strlen($rows['Calculation']) > 0) && ($rows['ReportStyle'] == 2)) {
            $this->Amount2          = $this->GetTotal($rows['Calculation']);
            $this->Amount2_lastyear = $this->GetTotal_lastyear($rows['Calculation']);
        }

        return $this->Amount2;
    }

    function Reset()
    {
        unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling']       = false;
        $_SESSION['chart_table']['Amount_lastyear']     = 0;
        $_SESSION['chart_table']['Amount']              = 0;
        $_SESSION['chart_table']['begintotal']          = 0;
        $_SESSION['chart_table']['begintotal_lastyear'] = 0;
        $_SESSION['chart_table']['Calc']                = array();
        $_SESSION['chart_table']['Calc_LastYear']       = array();
    }

    function GetTotal($formular)
    {
        $this->Amount = 0;
        $accounts = explode('+', $formular);
        foreach ($accounts as $code) {
            $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function GetTotal_lastyear($formular)
    {
        $this->Amount_lastyear = 0;
        $accounts = explode('+', $formular);
        foreach ($accounts as $code) {
            $this->Amount_lastyear = $this->AddTotals_lastyear($code);
        }
        return $this->Amount_lastyear;
    }

    function AddTotals($account)
    {
        $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
        return $_SESSION['chart_table']['Amount'];
    }

    function AddTotals_lastyear($account)
    {
        $_SESSION['chart_table']['Amount_lastyear'] += $_SESSION['chart_table']['Calc_LastYear'][$account];
        return $_SESSION['chart_table']['Amount_lastyear'];
    }

    function savetotal($reportcode, $amount = 0)
    {
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount;
    }

    function savetotal_lastyear($reportcode, $amount = 0)
    {
        $_SESSION['chart_table']['Calc_LastYear'][$reportcode] = $amount;
    }

    function Get_lastyear()
    {
        return $this->Amount2_lastyear;
    }
}

class FinancialPeriods
{
    var $SelectObject = '';

    function __construct()
    {
        global $db;
        $this->SelectObject = '<tr><td>Select Year</td><td><select name="Financial_Periods">';
        $ResultIndex = DB_query("SELECT periodno, MAX(end_date) AS year
                                 FROM FinancialPeriods
                                 GROUP BY periodno
                                 ORDER BY periodno DESC", $db);
        while ($row = DB_fetch_array($ResultIndex)) {
            $selected = ($_POST['Financial_Periods'] == trim($row['periodno'])) ? 'selected="selected"' : '';
            $this->SelectObject .= '<option value="' . trim($row['periodno']) . '" ' . $selected . '>'
                                 . $row['year'] . '</option>';
        }
        $this->SelectObject .= '</select></td></tr>';
    }

    function Get()
    {
        echo $this->SelectObject;
    }
}

class MonthlyReports
{
    var $SelectObject = '';

    function __construct()
    {
        global $db;
        $this->SelectObject = '<tr><td>Select Ending Month</td><td><select name="Financial_Periods">';
        // Note: DB_System() preserved as-is; uses the system DB connection for the periods table.
        $ResultIndex = DB_System("SELECT periodno, lastdate_in_period AS year
                                  FROM periods
                                  WHERE lastdate_in_period < DATE_ADD(NOW(), INTERVAL 1 MONTH)
                                  GROUP BY periodno, lastdate_in_period
                                  ORDER BY periodno DESC", $db);
        while ($row = DB_fetch_array($ResultIndex)) {
            $selected = (isset($_POST['Financial_Periods']) && $_POST['Financial_Periods'] == trim($row['periodno']))
                        ? 'selected="selected"' : '';
            $this->SelectObject .= '<option value="' . trim($row['periodno']) . '" ' . $selected . '>'
                                 . ConvertSQLDate($row['year']) . '</option>';
        }
        $this->SelectObject .= '</select></td></tr>';
    }

    function Get()
    {
        echo $this->SelectObject;
    }
}

class MonthlyPeriods
{
    var $SelectObject = '';

    function __construct()
    {
        global $db;
        $this->SelectObject = '<tr><td>Select Ending Month</td><td>'
                            . '<select id="TaskActivityDate" name="Financial_Periods"><option></option>';
        $ResultIndex = DB_System("SELECT periodno, lastdate_in_period AS year
                                  FROM periods
                                  WHERE lastdate_in_period < DATE_ADD(NOW(), INTERVAL 1 MONTH)
                                  GROUP BY periodno, lastdate_in_period
                                  ORDER BY periodno DESC", $db);
        while ($row = DB_fetch_array($ResultIndex)) {
            $selected = (isset($_POST['Financial_Periods']) && $_POST['Financial_Periods'] == trim($row['periodno']))
                        ? 'selected="selected"' : '';
            $this->SelectObject .= '<option value="' . trim($row['periodno']) . '" ' . $selected . '>'
                                 . ConvertSQLDate($row['year']) . '</option>';
        }
        $this->SelectObject .= '</select></td></tr>';
    }

    function Get()
    {
        echo $this->SelectObject;
    }
}

// SaveReceipt, SavePrepaymentReceipt, CalculateExchange — no stored procedures; unchanged.
// (Paste originals here unchanged.)

class SaveReceipt extends ShowBankAccounts{
    Var $bankcode;
    Var $FormVariables=array();
    var $periodNo;
    var $sqlarray=array();
    var $journal;
    var $invref;
    var $acctfolio; 
    Var $Receipt_Journal;
    Var $JournalArray=array();
    Var $date;
    var $documentno;
    var $reference;
    var $CustomerID;
    var $CustomerName;
    Var $currencycode;
    var $totalamount;
    Var $postingGroups;
    Var $checkbalance=0;
    
    function GetForm(){
        Global $db;
        
            $this->FormVariables= $_POST;
            foreach ($this->FormVariables as $key=>$value) {
                
                if($key=='date'){
                    $this->date = ConvertSQLDate($value);
                    $this->periodNo= GetPeriod($value,$db);
                }
                if($key=='Bank_Code'){
                    $this->bankcode=$value;
                    $this->GetBankDetails($this->bankcode);
                }
                    
                if($key=='documentno'){
                $this->documentno = $value;
                
                }
                if($key=='reference'){
                $this->reference = $value;
                
                }
                if($key=='CustomerID'){
                $this->CustomerID = $value;
                $this->GetCustomer($this->CustomerID);
                }
                if($key=='CustomerName'){
                $this->CustomerName = $value;
                
                }
                if($key=='currencycode'){
                $this->currencycode = $value;
                
                }
                if($key=='totalamount'){
                $this->totalamount = $value;
                
                }
                                              
                if($key=='topay'){
                    $this->checkbalance=0;
                    foreach ($value as $journal => $amount) {
                        $this->GetPaidinvoices($journal,$amount);
                       
                    }
                 
                }
                
            }
            
            $this->GetCustomer($this->CustomerID);
            $this->PostToAccounts();
    }
  
    function GetPaidinvoices($journal,$amount=0){
        if($amount>0){
                $this->checkbalance += $amount;
                $this->JournalArray[]=SPRINTF("insert into `ReceiptsAllocation`
                        (`itemcode`,`date`,`invoiceno`,`journalno`,`doctype`,`receiptno`,`amount`,`receiptjournal`) 
                         (Select `Accountno`,`date`,`Documentno`,`JournalNo`,`Documenttype`,'%s',%f,'%s' 
                         from `CustomerStatement` where `JournalNo`='%s') ",
                        $this->documentno,
                        (0-$amount),
                        $this->Receipt_Journal,
                        $journal);
                
        }
    }
    
    function GetCustomer($itemcode){
        global $db;
        
        $ResultIndex = DB_query("select `postinggroups`.`debtorsaccount`,`customerposting` FROM "
                . "`debtors` join `postinggroups` on `customerposting`=`postinggroups`.`code` "
                . "where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $this->postingGroups = $rows[0];
    }
    
    function PostToAccounts(){
        $this->totalamount= $this->checkbalance;
       
          $this->JournalArray[] =sprintf("INSERT INTO `CustomerStatement`
                                (`Date`
                                ,`Documentno`
                                ,`Documenttype`
                                ,`Accountno`
                                ,`Grossamount`
                                ,`JournalNo`
                                ,`Currency`)
                              VALUES  
                                   ('%s'
                                    ,'%s'
                                    ,'%s'
                                    ,'%s'
                                    ,%f
                                    ,'%s'
                                    ,'%s'   
                                    ) "
                                    ,$this->date
                                    ,$this->documentno 
                                    ,'12'
                                    ,$this->CustomerID
                                    ,0-$this->totalamount 
                                    ,$this->Receipt_Journal
                                    ,$this->BankArray['currency']);
     
        
        $this->JournalArray[]=sprintf("INSERT INTO `receiptheader`
                                        (`docno`
                                        ,`date`
                                        ,`itemcode`
                                        ,`externalref`
                                        ,`narrative`
                                        ,`amount`
                                        ,`journal`
                                        ,`currency`)
                                  VALUES
                                        ('%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,%f
                                        ,'%s'
                                        ,'%s')",
                            $this->documentno,
                            $this->date, 
                            $this->CustomerID,
                            $this->reference,
                            'Paid in '.$this->BankArray['currency'] ,
                            $this->totalamount,
                            $this->Receipt_Journal,
                            $this->BankArray['currency']);
        
        
        $this->JournalArray[]=SPRINTF("INSERT INTO `BankTransactions`
                                    (`bankcode`
                                    ,`DocDate`
                                    ,`doctype`
                                    ,`DocumentNo`
                                    ,`TransType`
                                    ,`itemcode`
                                    ,`journal`
                                    ,`amount`
                                    ,`narrative`
                                    ,`exchangerate`)
                              VALUES
                                    ('%s'
                                    ,'%s'
                                    ,12
                                    ,'%s'
                                    ,'DR'
                                    ,'%s'
                                    ,'%s'
                                    ,%f
                                    ,'%s'
                                    ,%f
                                    ) ",$this->bankcode,
                                        $this->date,
                                        $this->documentno,
                                        $this->CustomerID,
                                        $this->Receipt_Journal,
                                        $this->totalamount,
                                        (trim($this->CustomerName).'  '.trim($this->reference)),
                                        $this->BankArray['rate']);
        
        
        
        $this->JournalArray[]= sprintf("Insert into `debtorsledger`
                (`date`
               ,`details`
               ,`flag`
               ,`invref`
               ,`acctfolio`
               ,`amount`
               ,`pamount`
               ,`curr_cod`
               ,`i_n_t`
               ,`journal`
               ,`typ`
               ,`vatamt`
               ,`systypes_1`
               ,`ledger`
               ,`period`)
               values
               ('%s','%s','%s','%s','%s',%f,%f,'%s','%s','%s','%s',%f,'%s','%s','%s')",
                $this->date,
                ('Receipt  '.trim($this->reference)),
                "DR",
                $this->documentno,
                $this->CustomerID,
                (0-$this->totalamount),
                0,
                $this->currencycode,
                "R",
                $this->Receipt_Journal,
                "_W",
                0,
                12,
                $this->postingGroups,
                $this->periodNo);
        
        
        
             $this->JournalArray[]=SPRINTF("Insert into `Generalledger`
                                    (`journalno`
                                    ,`Docdate`
                                    ,`period`
                                    ,`DocumentNo`
                                    ,`DocumentType`
                                    ,`accountcode`
                                    ,`balaccountcode`
                                    ,`VATaccountcode`
                                    ,`amount`
                                    ,`VATamount`
                                    ,`currencycode`
                                    ,`ExchangeRate`
                                    ,`cutomercode`
                                    ,`narration`) 
                                    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ",
              $this->Receipt_Journal,$this->date,$this->periodNo,$this->documentno,12,
              $this->BankArray['PostingGroup'],$this->postingGroups,"",
              $this->totalamount,0,$this->currencycode,$this->BankArray['rate'],
              $this->CustomerID,(trim($this->CustomerName).'  '.trim($this->reference)));
    
    }
    
}
/////////////////////////////////////////
class SavePrepaymentReceipt extends ShowBankAccounts {
    Var $bankcode;
    Var $FormVariables=array();
    var $periodNo;
    var $sqlarray=array();
    var $journal;
    var $invref;
    var $acctfolio; 
    Var $Receipt_Journal;
    Var $JournalArray=array();
    Var $date;
    var $documentno;
    var $reference;
    var $CustomerID;
    var $CustomerName;
    Var $currencycode;
    var $totalamount;
    Var $postingGroups;
    Var $checkbalance=0;
    var $FunctionalCurrency;
    Var $FromRate;
    var $Torate;
    
    
    function __construct() {
        $this->FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];
     }
    
    function getrates($fromcurrency,$tocurrency){
        
        $this->FromRate =$this->ExtractRate($fromcurrency);
        $this->Torate =$this->ExtractRate($tocurrency);
        
        return ($this->Torate/$this->FromRate);
    }
    
    function ExtractRate($currbrieve){
        global $db;
        
        $SQLOldRate = "SELECT `rate` FROM `currencies`
        WHERE `currabrev` = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];
                
        return $OldRate;
    }
    
    function GetForm(){
        Global $db;
        
            $this->Receipt_Journal= GetNextTransNo(0,$db);
        
            $this->FormVariables= $_POST;
            foreach ($this->FormVariables as $key=>$value) {
                
                if($key=='date'){
                    $this->date = ConvertSQLDate($value);
                    $this->periodNo= GetPeriod($value,$db);
                }
                if($key=='Bank_Code'){
                    $this->bankcode=$value;
                    $this->GetBankDetails($this->bankcode);
                }
                    
                if($key=='documentno'){
                $this->documentno = $value;
                
                }
                if($key=='reference'){
                $this->reference = $value;
                
                }
                if($key=='CustomerID'){
                $this->CustomerID = $value;
                $this->GetCustomer($this->CustomerID);
                }
                if($key=='CustomerName'){
                $this->CustomerName = $value;
                
                }
                if($key=='currencycode'){
                $this->currencycode = $value;
                
                }
                if($key=='totalamount'){
                $this->totalamount = $value;
                
                }
                                                              
            }
            
            $this->GetCustomer($this->CustomerID);
            $this->PostToAccounts();
    }
   
    function GetCustomer($itemcode){
        global $db;
        
        $ResultIndex = DB_query("select `postinggroups`.`debtorsaccount`,`customerposting` FROM "
                . "`debtors` join `postinggroups` on `customerposting`=`postinggroups`.`code` "
                . " where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $this->postingGroups = $rows[0];
    }
    
    function PostToAccounts(){
        
        
          $this->JournalArray[] =sprintf("INSERT INTO `CustomerStatement`
                                (`Date`
                                ,`Documentno`
                                ,`Documenttype`
                                ,`Accountno`
                                ,`Grossamount`
                                ,`JournalNo`
                                ,`Currency`)
                              VALUES  
                                   ('%s'
                                    ,'%s'
                                    ,'%s'
                                    ,'%s'
                                    ,%f
                                    ,'%s'
                                    ,'%s'   
                                    ) "
                                    ,$this->date
                                    ,$this->documentno 
                                    ,'12'
                                    ,$this->CustomerID
                                    ,0-$this->totalamount 
                                    ,$this->Receipt_Journal
                                    ,$this->BankArray['currency']);
        
        $this->JournalArray[]=sprintf("INSERT INTO `receiptheader`
                                        (`docno`
                                        ,`date`
                                        ,`itemcode`
                                        ,`externalref`
                                        ,`narrative`
                                        ,`amount`
                                        ,`journal`
                                        ,`currency`)
                                  VALUES
                                        ('%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,%f
                                        ,'%s'
                                        ,'%s')",
                                        $this->documentno,
                                        $this->date, 
                                        $this->CustomerID,
                                        $this->reference,
                                        'Pain in :'.$this->BankArray['currency'] ,
                                        $this->totalamount,
                                        $this->Receipt_Journal,
                                        $this->BankArray['currency']);
        
        
        
        $this->JournalArray[]=SPRINTF("INSERT INTO `BankTransactions`
                                    (`bankcode`
                                    ,`DocDate`
                                    ,`doctype`
                                    ,`DocumentNo`
                                    ,`TransType`
                                    ,`itemcode`
                                    ,`journal`
                                    ,`amount`
                                    ,`narrative`
                                    ,`exchangerate`)
                              VALUES
                                    ('%s'
                                    ,'%s'
                                    ,12
                                    ,'%s'
                                    ,'DR'
                                    ,'%s'
                                    ,'%s'
                                    ,%f
                                    ,'%s'
                                    ,%f
                                    ) ",$this->bankcode,
                                        $this->date,
                                        $this->documentno,
                                        $this->CustomerID,
                                        $this->Receipt_Journal,
                                        $this->totalamount,
                                        (trim($this->CustomerName).'  '.trim($this->reference)),
                                        $this->BankArray['rate']);
        
        
        
        $this->JournalArray[]= sprintf("Insert into `debtorsledger`
                (`date`
               ,`details`
               ,`flag`
               ,`invref`
               ,`acctfolio`
               ,`amount`
               ,`pamount`
               ,`curr_cod`
               ,`i_n_t`
               ,`journal`
               ,`typ`
               ,`vatamt`
               ,`systypes_1`
               ,`ledger`
               ,`period`)
               values
               ('%s','%s','%s','%s','%s',%f,%f,'%s','%s','%s','%s',%f,'%s','%s','%s')",
                $this->date,
                ('Receipt  '.trim($this->reference)),
                "DR",
                $this->documentno,
                $this->CustomerID,
                ((0-$this->totalamount) * $this->getrates($this->currencycode,$this->BankArray['currency'])),
                0,
                $this->BankArray['currency'],
                "R",
                $this->Receipt_Journal,
                "_W",
                0,
                12,
                $this->postingGroups,
                $this->periodNo);
        
        
        
             $this->JournalArray[]=SPRINTF("Insert into `Generalledger`
                                    (`journalno`
                                    ,`Docdate`
                                    ,`period`
                                    ,`DocumentNo`
                                    ,`DocumentType`
                                    ,`accountcode`
                                    ,`balaccountcode`
                                    ,`VATaccountcode`
                                    ,`amount`
                                    ,`VATamount`
                                    ,`currencycode`
                                    ,`ExchangeRate`
                                    ,`cutomercode`
                                    ,`narration`) 
                                    values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ",
              $this->Receipt_Journal,$this->date,$this->periodNo,$this->documentno,12,
              $this->BankArray['PostingGroup'],$this->postingGroups,"",
              $this->totalamount,0,$this->currencycode,$this->BankArray['rate'],
              $this->CustomerID,(trim($this->CustomerName).'  '.trim($this->reference)));
    
    }
    
  
}

class CalculateExchange{
    var $FunctionalCurrency;
    Var $FromRate;
    var $Torate;
        
    function __construct() {
       $this->FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];
     }
    
    function get($fromcurrency,$tocurrency){
        $this->FromRate =$this->ExtractRate($fromcurrency);
        $this->Torate =$this->ExtractRate($tocurrency);
        
        return ($this->FromRate/$this->Torate);
        
    }
    
    
    function ExtractRate($currbrieve){
        global $db;
        
        $SQLOldRate = "SELECT `rate` FROM `currencies`
        WHERE `currabrev` = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];
                
        return $OldRate;
    }
}

class Savepaymentvoucher {
    Var $bankcode;
    Var $FormVariables=array();
    var $periodNo;
    var $sqlarray=array();
    var $journal;
    var $invref;
    var $acctfolio; 
    Var $Receipt_Journal;
    Var $JournalArray= array();
    Var $date;
    var $documentno;
    var $reference;
    var $CustomerID;
    var $CustomerName;
    Var $currencycode;
    var $totalamount;
    Var $postingGroups;
    Var $checkbalance=0;
    Var $DimensionsBudget;
    VAR $DocNo=0;
    var $BankArray = array();
     
    function __construct() {
        $this->DimensionsBudget = new DimensionsBudget();
    }
    
    function GetFormUpdate(){
        $this->JournalArray[]="Delete from `paymentvoucherline` where `journal`='".$_POST['edit']."'";
        $this->JournalArray[]="Delete from `paymentvoucherheader` where `journal`='".$_POST['edit']."'";
        $this->GetForm();
    }
    
    
    function GetBankDetails($accountCode){
         Global $db;
        $resultindex=DB_query("SELECT 
            `accountcode`,`bankName`,`BankAccounts`.`currency`,`lastreconcileddate`
           ,`AccountNo`,`BranchCode`,`BranchName`,`lastreconbalance`
           ,`lastChequeno`,`PostingGroup`,`currencies`.`rate`,`StatementNo`
            FROM `BankAccounts` 
            join `currencies` on `BankAccounts`.`currency`=`currencies`.`currabrev`
            where `accountcode`='".$accountCode."'", $db);
        
        $row=DB_fetch_row($resultindex);
        $this->BankArray['accountcode'] = $row[0];
        $this->BankArray['bankName']    = $row[1];
        $this->BankArray['currency']    = $row[2];
        $this->BankArray['lastreconcileddate'] = $row[3];
        $this->BankArray['AccountNo'] = $row[4];
        $this->BankArray['BranchCode']= $row[5];
        $this->BankArray['BranchName']= $row[6];
        $this->BankArray['lastreconbalance'] = $row[7];
        $this->BankArray['lastChequeno'] = $row[8];
        $this->BankArray['PostingGroup'] = $row[9];
        $this->BankArray['rate'] = $row[10];
        $this->BankArray['StatementNo'] = $row[11];
        return $this->BankArray;
    }
    
    
    
    function GetForm(){
        Global $db;
        
            $this->FormVariables = $_POST;
            foreach ($this->FormVariables as $key=>$value) {
                
                if($key=='date'){
                    $this->date = ConvertSQLDate($value);
                    $this->periodNo= GetPeriod($value,$db);
                }
                
                if($key=='Bank_Code'){
                    $this->bankcode=$value;
                    $this->GetBankDetails($this->bankcode);
                }
                    
                if($key=='documentno'){
                $this->documentno = $value;
                
                }
                if($key=='reference'){
                $this->reference = $value;
                
                }
                
                if($key=='VendorID'){
                    $this->CustomerID = $value;
                }
                
                if($key=='VendorName'){
                     $this->CustomerName = $value;
                
                }
                if($key=='currencycode'){
                     $this->currencycode = $value;
                
                }

                if($key=='totalamount'){
                   $this->totalamount = $value;
                }
                      
                
                if($key=='topay'){
                         foreach ($value as $journalno => $amountval) {
                             
                                if($amountval>0){
                                     $this->GetBudgetsForAll($journalno,$amountval);
                                  }
                                
                          }
                   
                   }
                   
                   
             
            }
            
            $this->PVheader();
                 
    }
   
    
    
    function PVheader(){
        
            $this->JournalArray[] = sprintf("INSERT INTO `paymentvoucherheader`
                                        (`docno`
                                        ,`date`
                                        ,`itemcode`
                                        ,`externalref`
                                        ,`narrative`
                                        ,`amount`
                                        ,`journal`
                                        ,`currency`
                                        ,`status`)
                                  VALUES
                                        ('%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,'%s'
                                        ,%f
                                        ,'%s'
                                        ,'%s'
                                        ,0)",
                                        $this->documentno ,
                                        $this->date, 
                                        $this->CustomerID,
                                        $this->reference,
                                        'Paid in '.$this->BankArray['currency'] ,
                                        $this->totalamount,
                                        $this->Receipt_Journal,
                                        $this->BankArray['currency']);
        
    }
    
    
  
    function GetBudgetsForAll($journal,$amount1){
        global $db;
        
        $SQL = SPRINTF("SELECT
            `Grossamount`,
            `Dimension_One` ,
            `Dimension_Two` ,
            `currencies`.`rate`
            FROM `SupplierStatement` left join `currencies`
            on `currencies`.`currabrev`=`SupplierStatement`.`Currency`
            where `Accountno`='%s' and `JournalNo`='%s' ",  $this->CustomerID,$journal);
            
        $ResultIndex = DB_query($SQL,$db);
        $INVOICES = DB_fetch_row($ResultIndex);
        
        $amount = ($amount1 * ($INVOICES[3]/$this->BankArray['rate']));
        
        $VOTEARRAY = $this->DimensionsBudget->Calculate($INVOICES[1]);         
        $budgetbalance =($VOTEARRAY['Budget'] - ($VOTEARRAY['Committement']+ $VOTEARRAY['Expensed'] + $amount));
              
        
        $this->JournalArray[] = sprintf("INSERT INTO `paymentvoucherline`
                                        (`docno`
                                        ,`itemcode`
                                        ,`narrative`
                                        ,`amount`
                                        ,`journal`
                                        ,`invoice_journal`
                                        ,`Budget`
                                        ,`Committed`
                                        ,`Expensed`
                                        ,`Balance`
                                        ,`Dimension_1`
                                        ,`Dimension_2`)
                                  VALUES
                ('%s','%s','%s',%f,'%s','%s',%f,%f,%f,%f,'%s','%s')",
                                        $this->documentno ,
                                        $this->CustomerID,
                                        'Paid in '.$this->BankArray['currency'] ,
                                        $amount,
                                        $this->Receipt_Journal,
                                        $journal,
                                        $VOTEARRAY['Budget'],
                                        $VOTEARRAY['Committement'],
                                        $VOTEARRAY['Expensed'],
                                        $budgetbalance,
                                        $INVOICES[1],
                                        $INVOICES[2]);
        
    }
    
    
}
// ---------------------------------------------------------------------------
// BalancesheetForYear  (Balance Sheet with prior-year comparison)
// ---------------------------------------------------------------------------

class BalancesheetForYear
{
    var $Calc                  = array();
    var $Calc_LastYear         = array();
    var $Amount                = 0;
    var $Amount2               = 0;
    var $Amount2_lastyear      = 0;
    var $Amount_lastyear       = 0;
    var $begintotal            = 0;
    var $begintotal_lastyear   = 0;
    var $starttotaling         = true;
    var $ResultDataSet         = array();
    var $DebitSide             = 0;
    var $CreditSide            = 0;
    var $DebitSide_Last        = 0;
    var $CreditSide_Last       = 0;
    var $Profit                = 0;
    var $Profit_last           = 0;
    var $Resource;
    var $Closingstock          = 0;
    var $Openningstock         = 0;
    var $CurrentAssets         = 0;
    var $CurrentAssets_Last    = 0;

    function __construct()
    {
        $_SESSION['BalanceSheed']['id'] = 100000;
    }

    function GetID()
    {
        return $_SESSION['BalanceSheed']['id']++;
    }

    // ------------------------------------------------------------------
    // Display / calculation helpers (unchanged from original)
    // ------------------------------------------------------------------

    function Get($rows = array())
    {
        if ($rows['ReportStyle'] == 3) {
            $_SESSION['chart_table']['starttotaling'] = true;
            $_SESSION['chart_table']['begintotal']    = 0;
        }

        if (($rows['ReportStyle'] == 0 || $rows['ReportStyle'] == 5)
            && ($_SESSION['chart_table']['starttotaling'] == true)) {
            $_SESSION['chart_table']['begintotal'] += ($rows['amount']);
        }

        if ($rows['ReportStyle'] == 4) {
            $_SESSION['chart_table']['starttotaling'] = false;
            $this->Amount2 = $_SESSION['chart_table']['begintotal'];
            $_SESSION['chart_table']['begintotal'] = 0;
        } else {
            $this->Amount2 = ($rows['amount']);
        }

        $this->savetotal($rows['ReportCode'], $this->Amount2);

        if ((mb_strlen($rows['Calculation']) > 0) && ($rows['ReportStyle'] == 2)) {
            $this->Amount2 = $this->GetTotal($rows['Calculation']);
        }

        return $this->Amount2;
    }

    function Get_last($rows = array())
    {
        if ($rows['ReportStyle'] == 3) {
            $_SESSION['chart_table_Last']['starttotaling'] = true;
            $_SESSION['chart_table_Last']['begintotal']    = 0;
        }

        if (($rows['ReportStyle'] == 0) && ($_SESSION['chart_table_Last']['starttotaling'] == true)) {
            $_SESSION['chart_table_Last']['begintotal'] += ($rows['Amount_lastyear']);
        }

        if ($rows['ReportStyle'] == 4) {
            $_SESSION['chart_table_Last']['starttotaling'] = false;
            $this->Amount2_lastyear = $_SESSION['chart_table_Last']['begintotal'];
            $_SESSION['chart_table_Last']['begintotal'] = 0;
        } else {
            $this->Amount2_lastyear = ($rows['Amount_lastyear']);
        }

        $this->savetotal_last($rows['ReportCode'], $this->Amount2);

        if ((mb_strlen($rows['Calculation']) > 0) && ($rows['ReportStyle'] == 2)) {
            $this->Amount2_lastyear = $this->GetTotal_last($rows['Calculation']);
        }

        return $this->Amount2_lastyear;
    }

    function Reset()
    {
        unset($_SESSION['chart_table']);
        $_SESSION['chart_table']['starttotaling'] = false;
        $_SESSION['chart_table']['Amount']        = 0;
        $_SESSION['chart_table']['begintotal']    = 0;
        $_SESSION['chart_table']['Calc']          = array();

        unset($_SESSION['chart_table_Last']);
        $_SESSION['chart_table_Last']['starttotaling'] = false;
        $_SESSION['chart_table_Last']['Amount']        = 0;
        $_SESSION['chart_table_Last']['begintotal']    = 0;
        $_SESSION['chart_table_Last']['Calc']          = array();

        $this->ResultDataSet = array();
    }

    function GetTotal($formular)
    {
        $this->Amount = 0;
        $accounts = explode('+', $formular);
        foreach ($accounts as $code) {
            $this->Amount = $this->AddTotals($code);
        }
        return $this->Amount;
    }

    function GetTotal_last($formular)
    {
        $this->Amount_lastyear = 0;
        $accounts = explode('+', $formular);
        foreach ($accounts as $code) {
            $this->Amount_lastyear = $this->AddTotals_last($code);
        }
        return $this->Amount_lastyear;
    }

    function AddTotals($account)
    {
        $_SESSION['chart_table']['Amount'] += $_SESSION['chart_table']['Calc'][$account];
        return $_SESSION['chart_table']['Amount'];
    }

    function AddTotals_last($account)
    {
        $_SESSION['chart_table_Last']['Amount'] += $_SESSION['chart_table_Last']['Calc'][$account];
        return $_SESSION['chart_table_Last']['Amount'];
    }

    function savetotal($reportcode, $amount = 0)
    {
        $_SESSION['chart_table']['Calc'][$reportcode] = $amount;
    }

    function savetotal_last($reportcode, $amount = 0)
    {
        $_SESSION['chart_table_Last']['Calc'][$reportcode] = $amount;
    }

    // ------------------------------------------------------------------
    // Main entry point
    // ------------------------------------------------------------------

    function CalYearlydata()
    {
        $this->Reset();

        $this->Profit      = 0;
        $this->Profit_last = 0;
        $this->DebitSide   = $this->DebitSide_Last  = 0;
        $this->CreditSide  = $this->CreditSide_Last = 0;
        $this->CurrentAssets = $this->CurrentAssets_Last = 0;

        $this->DB_FixedAssets();
        $this->DB_Inventory();
        $this->DB_Debtors();
        $this->DB_Bank();
        $this->DB_CurrentLiability();
        $this->DB_LongLiability();
        $this->DB_Capital();

        return $this->ResultDataSet;
    }

    // ------------------------------------------------------------------
    // Shared yearly query runner for balance-sheet sections.
    // All balance-sheet sections use the same period/date logic as
    // TradingAccountForYear::_yearlyQuery().
    // ------------------------------------------------------------------

    private function _bsQuery($rangePrefix)
    {
        global $db, $YearPeriod;
        $YearPeriod = $_POST['Financial_Periods'];

        $range = _getAccountRange($rangePrefix);
        if (!$range) {
            return false;
        }

        $from         = mysqli_real_escape_string($db, $range['from']);
        $to           = mysqli_real_escape_string($db, $range['to']);
        $currentPeriod = (int)$YearPeriod;
        $lastPeriod    = $currentPeriod - 12;

        $currentDates = _getPeriodDates($currentPeriod);
        $lastDates    = _getPeriodDates($lastPeriod);

        if (!$currentDates || !$lastDates) {
            return false;
        }

        $ccs = $currentDates['cur_start'];
        $cce = $currentDates['cur_end'];
        $lls = $lastDates['cur_start'];
        $lle = $lastDates['cur_end'];

        // Use _buildGLColumns pattern but with explicit period boundaries
        $glCols = "
            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$cce')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$ccs' AND '$cce')
            END AS debit,

            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$cce')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$ccs' AND '$cce')
            END AS credit,

            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$lle')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$lls' AND '$lle')
            END AS debit_Last,

            CASE acct.balance_income
                WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$lle')
                WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                             WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$lls' AND '$lle')
            END AS credit_Last";

        $sql = "SELECT ReportCode, accdesc, balance_income, ReportStyle,
                       direct, Sale_Purchase_Neither, accno, Calculation,
                       $glCols
                FROM acct
                WHERE ReportCode BETWEEN '$from' AND '$to'
                " . _acctGroupOrder();

        return DB_query($sql, $db);
    }

    // ------------------------------------------------------------------
    // Section methods
    // ------------------------------------------------------------------

    function DB_FixedAssets()
    {
        $ResultsX = $this->_bsQuery('fixedassets');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AccountCode = $this->GetID();
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$AccountCode]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$AccountCode]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$AccountCode]['accno']       = $Row['accno'];
            $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$AccountCode]['amount']      = $AMOUNT;
            $this->DebitSide += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT_last;
            $this->DebitSide_Last += $AMOUNT_last;
        }
    }

    function DB_Inventory()
    {
        $ResultsX = $this->_bsQuery('inventory');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AccountCode = $this->GetID();
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$AccountCode]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$AccountCode]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$AccountCode]['accno']       = $Row['accno'];
            $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$AccountCode]['amount']      = $AMOUNT;
            $this->CurrentAssets += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT_last;
            $this->CurrentAssets_Last += $AMOUNT_last;
        }
    }

    function DB_Debtors()
    {
        $ResultsX = $this->_bsQuery('Debtors');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AccountCode = $this->GetID();
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$AccountCode]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$AccountCode]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$AccountCode]['accno']       = $Row['accno'];
            $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$AccountCode]['amount']      = $AMOUNT;
            $this->CurrentAssets += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT_last;
            $this->CurrentAssets_Last += $AMOUNT_last;
        }
    }

    function DB_Bank()
    {
        $ResultsX = $this->_bsQuery('Bank');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AccountCode = $this->GetID();
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$AccountCode]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$AccountCode]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$AccountCode]['accno']       = $Row['accno'];
            $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$AccountCode]['amount']      = $AMOUNT;
            $this->CurrentAssets += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT_last;
            $this->CurrentAssets_Last += $AMOUNT_last;
        }
    }

    function DB_CurrentLiability()
    {
        $ResultsX = $this->_bsQuery('CurLiab');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AccountCode = $this->GetID();
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$AccountCode]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$AccountCode]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$AccountCode]['accno']       = $Row['accno'];
            $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$AccountCode]['amount']      = $AMOUNT * -1;  // liability shown as positive deduction
            $this->CurrentAssets += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT_last * -1;
            $this->CurrentAssets_Last += $AMOUNT_last;
        }

        // Totals injected after current liabilities — same as original
        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['ReportCode']      = _('NetAssets');
        $this->ResultDataSet[$AccountCode]['accdesc']         = _('Net Current Assets');
        $this->ResultDataSet[$AccountCode]['ReportStyle']     = 2;
        $this->ResultDataSet[$AccountCode]['amount']          = $this->CurrentAssets;
        $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $this->CurrentAssets_Last;
        $this->DebitSide      += $this->CurrentAssets;
        $this->DebitSide_Last += $this->CurrentAssets_Last;

        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['ReportCode']      = _('Assets');
        $this->ResultDataSet[$AccountCode]['accdesc']         = _('Total Assets');
        $this->ResultDataSet[$AccountCode]['ReportStyle']     = 2;
        $this->ResultDataSet[$AccountCode]['amount']          = $this->DebitSide;
        $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $this->DebitSide_Last;

        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['ReportCode']  = _('Capital');
        $this->ResultDataSet[$AccountCode]['accdesc']     = _('Capital Employed');
        $this->ResultDataSet[$AccountCode]['ReportStyle'] = 4;
    }

    function DB_LongLiability()
    {
        $ResultsX = $this->_bsQuery('LongLiab');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AccountCode = $this->GetID();
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$AccountCode]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$AccountCode]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$AccountCode]['accno']       = $Row['accno'];
            $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$AccountCode]['amount']      = $AMOUNT * -1;
            $this->CreditSide += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT_last * -1;
            $this->CreditSide_Last += $AMOUNT_last;
        }
    }

    function DB_Capital()
    {
        $ResultsX = $this->_bsQuery('Capital');
        if (!$ResultsX) {
            return;
        }

        while ($Row = DB_fetch_array($ResultsX)) {
            $AccountCode = $this->GetID();
            $AMOUNT = ($Row['debit'] - $Row['credit']);
            $this->ResultDataSet[$AccountCode]['ReportCode']  = $Row['ReportCode'];
            $this->ResultDataSet[$AccountCode]['accdesc']     = $Row['accdesc'];
            $this->ResultDataSet[$AccountCode]['accno']       = $Row['accno'];
            $this->ResultDataSet[$AccountCode]['ReportStyle'] = $Row['ReportStyle'];
            $this->ResultDataSet[$AccountCode]['amount']      = $AMOUNT * -1;
            $this->CreditSide += $AMOUNT;

            $AMOUNT_last = ($Row['debit_Last'] - $Row['credit_Last']);
            $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $AMOUNT_last * -1;
            $this->CreditSide_Last += $AMOUNT_last;
        }

        // Retained earnings = imbalance between assets and capital+liabilities
        $this->Profit      = $this->DebitSide + $this->CreditSide;
        $this->Profit_last = $this->DebitSide_Last + $this->CreditSide_Last;

        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['ReportCode']      = _('AccFund');
        $this->ResultDataSet[$AccountCode]['accdesc']         = _('Retained Earnings B/F');
        $this->ResultDataSet[$AccountCode]['ReportStyle']     = 2;
        $this->ResultDataSet[$AccountCode]['amount']          = $this->Profit;
        $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = $this->Profit_last;

        $AccountCode = $this->GetID();
        $this->ResultDataSet[$AccountCode]['ReportCode']      = _('Capital');
        $this->ResultDataSet[$AccountCode]['accdesc']         = _('Capital Employed');
        $this->ResultDataSet[$AccountCode]['ReportStyle']     = 2;
        $this->ResultDataSet[$AccountCode]['amount']          = ($this->CreditSide - $this->Profit) * -1;
        $this->ResultDataSet[$AccountCode]['Amount_lastyear'] = ($this->CreditSide_Last - $this->Profit_last) * -1;
    }
}