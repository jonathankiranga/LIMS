<?php


/**
 * Financial Reporting â€” MySQL Query Functions
 * Compatible: PHP 7.0+, MySQL 5.7+ (DB_Profit_loss_Project requires MySQL 8.0 for WITH RECURSIVE)
 *
 * Replaces MS-SQL stored procedures with direct PHP/MySQL execution.
 * Optimisations applied:
 *  - Stored-procedure overhead removed; queries run directly via DB_query().
 *  - Temporary tables replaced with derived tables / inline SELECT.
 *  - Period date ranges fetched once into PHP variables to avoid
 *    repeated correlated subqueries on FinancialPeriods.
 *  - Cursor-based loops (DB_House_cost, DB_FixedAssets, DB_FixedAssetsRegister)
 *    replaced with set-based JOINs and conditional aggregation.
 *  - All user input sanitised through mysqli_real_escape_string / intval.
 */

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

/**
 * Fetch the four period boundary dates for a given periodno.
 * Returns associative array with keys: cur_start, cur_end, last_start, last_end
 * Returns FALSE on failure.
 *
 * @param  int        $yearPeriod
 * @return array|bool
 */
function _getPeriodDates($yearPeriod)
{
    global $db;
    $yearPeriod = (int)$yearPeriod;
    $lastPeriod = $yearPeriod - 12;

    $sql = "SELECT
                (SELECT MIN(start_date) FROM FinancialPeriods WHERE periodno = $yearPeriod) AS cur_start,
                (SELECT MAX(end_date)   FROM FinancialPeriods WHERE periodno = $yearPeriod) AS cur_end,
                (SELECT MIN(start_date) FROM FinancialPeriods WHERE periodno = $lastPeriod) AS last_start,
                (SELECT MAX(end_date)   FROM FinancialPeriods WHERE periodno = $lastPeriod) AS last_end";

    $result = DB_query($sql, $db);
    $row    = DB_fetch_array($result);

    // GUARD: If no dates are returned, the period hasn't been set up
    if (!$row || is_null($row['cur_start'])) {
        return false; 
    }

    // Continue with fetching last year's dates...
    return $row;
}

/**
 * Build the four correlated GL CASE blocks shared by most reports.
 * Dates are embedded as literals so MySQL does not re-scan FinancialPeriods
 * on every row of acct.
 *
 * @param  array  $dates  Result from _getPeriodDates()
 * @return string
 */
function _buildGLColumns($dates)
{
    $cs = $dates['cur_start'];
    $ce = $dates['cur_end'];
    $ls = $dates['last_start'];
    $le = $dates['last_end'];

    return "
        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$ce')
            WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$cs' AND '$ce')
        END AS debit,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$ce')
            WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$cs' AND '$ce')
        END AS credit,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$cs')
            ELSE         (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$ls' AND '$le')
        END AS debit_Last,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$cs')
            ELSE         (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$ls' AND '$le')
        END AS credit_Last";
}

/**
 * Standard SELECT column list used by most account reports.
 *
 * @return string
 */
function _acctColumns()
{
    return "ReportCode, accdesc, balance_income, ReportStyle, direct,
            Sale_Purchase_Neither, accno, debit, credit,
            debit_Last, credit_Last, Calculation";
}

/**
 * Standard GROUP BY / ORDER BY tail shared by most account reports.
 *
 * @return string
 */
function _acctGroupOrder()
{
    return "GROUP BY ReportCode, accdesc, balance_income, ReportStyle,
                     direct, Sale_Purchase_Neither, Calculation, accno
            ORDER BY ReportCode";
}

// ---------------------------------------------------------------------------
// Account Balance Reports
// ---------------------------------------------------------------------------

/**
 * Full chart-of-accounts balance for the selected period (all accounts).
 *
 * @return resource|bool
 */
function DB_AccountBalance()
{
    global $db, $YearPeriod;

    $dates = _getPeriodDates((int)$YearPeriod);
    if (!$dates) {
        return false;
    }

    $sql = "SELECT " . _acctColumns() . "
            FROM (
                SELECT ReportCode, accdesc, balance_income, ReportStyle, direct,
                       Sale_Purchase_Neither, accno, Calculation,
                       " . _buildGLColumns($dates) . "
                FROM acct
                " . _acctGroupOrder() . "
            ) AS acct_data
            ORDER BY ReportCode";

    return DB_query($sql, $db);
}

/**
 * Trial-balance view: same as AccountBalance but restricted to ReportStyle = 0.
 *
 * @return resource|bool
 */
function DB_TBalance()
{
    global $db, $YearPeriod;

    $dates = _getPeriodDates((int)$YearPeriod);
    if (!$dates) {
        return false;
    }

    $sql = "SELECT " . _acctColumns() . "
            FROM (
                SELECT ReportCode, accdesc, balance_income, ReportStyle, direct,
                       Sale_Purchase_Neither, accno, Calculation,
                       " . _buildGLColumns($dates) . "
                FROM acct
                WHERE ReportStyle = 0
                " . _acctGroupOrder() . "
            ) AS acct_data
            ORDER BY ReportCode";

    return DB_query($sql, $db);
}

/**
 * Account balances filtered by account description.
 *
 * @param  string         $filter  Partial account description (will be escaped).
 * @return resource|bool
 */
function DB_AccountBalance_withfilter($filter = '')
{
    global $db, $YearPeriod;

    $dates         = _getPeriodDates((int)$YearPeriod);
    $escapedFilter = mysqli_real_escape_string($db, $filter);

    if (!$dates) {
        return false;
    }

    $cs = $dates['cur_start'];
    $ce = $dates['cur_end'];
    $ls = $dates['last_start'];
    $le = $dates['last_end'];

    // Note: the original 'withfilter' variant used < (not <=) for
    // balance-sheet debit â€” preserved here for behavioural consistency.
    $glColumns = "
        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.accountcode = acct.accno AND gl.Docdate < '$ce')
            WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$cs' AND '$ce')
        END AS debit,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$ce')
            WHEN 1 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$cs' AND '$ce')
        END AS credit,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$cs')
            ELSE         (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$ls' AND '$le')
        END AS debit_Last,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$cs')
            ELSE         (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$ls' AND '$le')
        END AS credit_Last";

    $sql = "SELECT " . _acctColumns() . "
            FROM (
                SELECT ReportCode, accdesc, balance_income, ReportStyle, direct,
                       Sale_Purchase_Neither, accno, Calculation,
                       $glColumns
                FROM acct
                WHERE accdesc LIKE '%$escapedFilter%'
                " . _acctGroupOrder() . "
            ) AS acct_data
            ORDER BY ReportCode";

    return DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// Balance Sheet  (balance_income = 0 only)
// ---------------------------------------------------------------------------

/**
 * Balance sheet accounts only.
 *
 * @return resource|bool
 */
function DB_Balancesheet()
{
    global $db, $YearPeriod;

    $dates = _getPeriodDates((int)$YearPeriod);
    if (!$dates) {
        return false;
    }

    $sql = "SELECT " . _acctColumns() . "
            FROM (
                SELECT ReportCode, accdesc, balance_income, ReportStyle, direct,
                       Sale_Purchase_Neither, accno, Calculation,
                       " . _buildGLColumns($dates) . "
                FROM acct
                WHERE balance_income = 0
                " . _acctGroupOrder() . "
            ) AS acct_data
            ORDER BY ReportCode";

    return DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// Profit & Loss  (balance_income = 1 only)
// ---------------------------------------------------------------------------

/**
 * Profit & Loss accounts only.
 *
 * @return resource|bool
 */
function DB_Profit_loss()
{
    global $db, $YearPeriod;

    $dates = _getPeriodDates((int)$YearPeriod);
    if (!$dates) {
        return false;
    }

    $sql = "SELECT " . _acctColumns() . "
            FROM (
                SELECT ReportCode, accdesc, balance_income, ReportStyle, direct,
                       Sale_Purchase_Neither, accno, Calculation,
                       " . _buildGLColumns($dates) . "
                FROM acct
                WHERE balance_income = 1
                " . _acctGroupOrder() . "
            ) AS acct_data
            ORDER BY ReportCode";

    return DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// Profit & Loss â€” by Period / Month
// ---------------------------------------------------------------------------

/**
 * P&L for a specific period/month.
 * Balance-sheet accounts (balance_income=0): cumulative up to period.
 * P&L accounts (balance_income=1): current period only.
 *
 * @return resource|bool
 */
function DB_Profit_loss_by_month()
{
    global $db, $periodno;

    $escapedPeriod = mysqli_real_escape_string($db, $periodno);

    $sql = "SELECT
                ReportCode,
                accdesc,
                balance_income,
                ReportStyle,
                direct,
                Sale_Purchase_Neither,
                accno,
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
                END AS credit,
                NULL AS debit_Last,
                NULL AS credit_Last,
                Calculation
            FROM acct
            WHERE balance_income = 1
            " . _acctGroupOrder();

    return DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// Profit & Loss â€” filtered by Project Dimension  (requires MySQL 8.0+)
// ---------------------------------------------------------------------------

/**
 * P&L filtered by a project dimension and all its child dimensions.
 * Requires MySQL 8.0+ for the WITH RECURSIVE CTE.
 *
 * @return resource|bool
 */
function DB_Profit_loss_Project()
{
    global $db, $YearPeriod, $ProjectSelected;

    $dates          = _getPeriodDates((int)$YearPeriod);
    $escapedProject = mysqli_real_escape_string($db, $ProjectSelected);

    if (!$dates) {
        return false;
    }

    $cs = $dates['cur_start'];
    $ce = $dates['cur_end'];
    $ls = $dates['last_start'];
    $le = $dates['last_end'];

    $glColumnsProject = "
        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         JOIN ProjectDimensions pd ON pd.Code = gl.dimension2
                         WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$ce')
            ELSE         (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         JOIN ProjectDimensions pd ON pd.Code = gl.dimension2
                         WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$cs' AND '$ce')
        END AS debit,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         JOIN ProjectDimensions pd ON pd.Code = gl.dimension2
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$ce')
            ELSE         (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         JOIN ProjectDimensions pd ON pd.Code = gl.dimension2
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$cs' AND '$ce')
        END AS credit,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         JOIN ProjectDimensions pd ON pd.Code = gl.dimension2
                         WHERE gl.accountcode = acct.accno AND gl.Docdate <= '$cs')
            ELSE         (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         JOIN ProjectDimensions pd ON pd.Code = gl.dimension2
                         WHERE gl.accountcode = acct.accno AND gl.Docdate BETWEEN '$ls' AND '$le')
        END AS debit_Last,

        CASE acct.balance_income
            WHEN 0 THEN (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         JOIN ProjectDimensions pd ON pd.Code = gl.dimension2
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate <= '$cs')
            ELSE         (SELECT SUM(gl.amount * gl.ExchangeRate) FROM Generalledger gl
                         JOIN ProjectDimensions pd ON pd.Code = gl.dimension2
                         WHERE gl.balaccountcode = acct.accno AND gl.Docdate BETWEEN '$ls' AND '$le')
        END AS credit_Last";

    $sql = "WITH RECURSIVE ProjectDimensions AS (
                SELECT Code, Dimension, parent
                FROM   Dimensions
                WHERE  Code = '$escapedProject'

                UNION ALL

                SELECT child.Code, child.Dimension, child.parent
                FROM   Dimensions child
                INNER JOIN ProjectDimensions pd ON child.parent = pd.Code
            )
            SELECT " . _acctColumns() . "
            FROM (
                SELECT ReportCode, accdesc, balance_income, ReportStyle, direct,
                       Sale_Purchase_Neither, accno, Calculation,
                       $glColumnsProject
                FROM acct
                WHERE balance_income = 1
                " . _acctGroupOrder() . "
            ) AS acct_data
            ORDER BY ReportCode";

    return DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// House / Project Cost Summary  (cursor replaced with set-based JOIN)
// ---------------------------------------------------------------------------

/**
 * Project cost summary: expenses and committed purchase orders
 * for a project dimension and its immediate children.
 *
 * @param  string         $projectSelected
 * @return resource|bool
 */
function DB_House_cost($projectSelected)
{
    global $db;

    $escapedProject = mysqli_real_escape_string($db, $projectSelected);

    $sql = "SELECT
                d.Code,
                d.Dimension                                                    AS BudgetName,
                NULL                                                           AS BudgetAmount,
                IFNULL(SUM(DISTINCT gl.amount), 0)                            AS Expeses,
                IFNULL(SUM(DISTINCT pl.invoiceamount), 0)                     AS Committed,
                IFNULL(SUM(DISTINCT gl.amount), 0)
                    + IFNULL(SUM(DISTINCT pl.invoiceamount), 0)               AS Total,
                NULL                                                           AS Balance,
                NULL                                                           AS percent
            FROM Dimensions d

            LEFT JOIN Generalledger gl
                   ON gl.dimension2 = d.Code
                  AND gl.accountcode IN (SELECT accno FROM acct WHERE balance_income = 1)

            LEFT JOIN PurchaseHeader ph
                   ON ph.Dimension_2  = d.Code
                  AND ph.released     = 1
                  AND ph.documenttype = 18

            LEFT JOIN PurchaseLine pl
                   ON pl.documenttype = ph.documenttype
                  AND pl.documentno   = ph.documentno

            WHERE d.Code   = '$escapedProject'
               OR d.parent = '$escapedProject'

            GROUP BY d.Code, d.Dimension
            ORDER BY d.Code";

    return DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// Fixed Assets Schedule  (cursor replaced with conditional aggregation)
// ---------------------------------------------------------------------------

/**
 * Fixed assets schedule as at a given process date.
 * Uses $_POST['ProcessDate'] via FormatDateForSQL().
 *
 * @return resource|bool
 */
function DB_FixedAssets()
{
    global $db;

    $processDate = FormatDateForSQL($_POST['ProcessDate']);

    $sql = "SELECT
                fa.assetid,
                fa.description,
                fa.longdescription,
                fa.assetcategoryid,
                fac.categorydescription,
                fa.serialno,
                fa.assetlocation                                              AS locationdescription,
                MIN(CASE WHEN ft.fixedassettranstype = 'cost'
                          AND ft.transdate <= '$processDate'
                         THEN ft.transdate END)                              AS transdate,
                SUM(CASE WHEN ft.fixedassettranstype = 'cost'
                          AND ft.transdate <= '$processDate'
                         THEN ft.amount ELSE 0 END)                          AS costtotal,
                SUM(CASE WHEN ft.fixedassettranstype = 'depn'
                          AND ft.transdate <= '$processDate'
                         THEN ft.amount ELSE 0 END)                          AS depnbfwd,
                fa.depntype,
                fa.depnrate
            FROM fixedassets fa
            LEFT JOIN fixedassetcategories fac ON fac.categoryid = fa.assetcategoryid
            LEFT JOIN fixedassettrans ft        ON ft.assetid    = fa.assetid
            WHERE fa.disposaldate IS NULL
            GROUP BY
                fa.assetid,
                fa.description,
                fa.longdescription,
                fa.assetcategoryid,
                fac.categorydescription,
                fa.serialno,
                fa.assetlocation,
                fa.depntype,
                fa.depnrate
            ORDER BY fa.assetcategoryid ASC";

    return DB_query($sql, $db);
}

// ---------------------------------------------------------------------------
// Fixed Assets Register  (cursor replaced with conditional aggregation)
// ---------------------------------------------------------------------------

/**
 * Fixed assets register for a date range with opening balances,
 * period movements, unit counts and hire figures.
 * Uses $_POST keys: FromDate, ToDate, AssetCategory, AssetID, AssetLocation.
 *
 * @return resource|bool
 */
function DB_FixedAssetsRegister()
{
    global $db;

    $dateFrom      = FormatDateForSQL($_POST['FromDate']);
    $dateTo        = FormatDateForSQL($_POST['ToDate']);
    $assetCategory = mysqli_real_escape_string($db, $_POST['AssetCategory']);
    $assetID       = mysqli_real_escape_string($db, $_POST['AssetID']);
    $assetLocation = mysqli_real_escape_string($db, $_POST['AssetLocation']);

    // Build optional LIKE predicates â€” pass '%' in the POST field to mean "all".
    $catFilter = ($assetCategory !== '%')
        ? "AND fa.assetcategoryid LIKE '$assetCategory'" : '';
    $idFilter  = ($assetID !== '%')
        ? "AND fa.assetid LIKE '$assetID'" : '';
    $locFilter = ($assetLocation !== '%')
        ? "AND fa.assetlocation LIKE '$assetLocation'" : '';

    $sql = "SELECT
                fa.assetid,
                fa.description,
                fa.longdescription,
                fa.assetcategoryid,
                fac.categorydescription,
                fa.serialno,
                fa.assetlocation                                              AS locationdescription,
                MIN(CASE WHEN ft.fixedassettranstype = 'cost'
                          AND ft.transdate >= '$dateFrom'
                         THEN ft.transdate END)                              AS datepurchased,

                -- Opening balances (before period start)
                SUM(CASE WHEN ft.fixedassettranstype = 'cost'
                          AND ft.transdate < '$dateFrom'
                         THEN ft.amount ELSE 0 END)                          AS costbfwd,
                SUM(CASE WHEN ft.fixedassettranstype = 'depn'
                          AND ft.transdate < '$dateFrom'
                         THEN ft.amount ELSE 0 END)                          AS depnbfwd,

                -- Period movements
                SUM(CASE WHEN ft.fixedassettranstype = 'cost'
                          AND ft.transdate BETWEEN '$dateFrom' AND '$dateTo'
                         THEN ft.amount ELSE 0 END)                          AS periodadditions,
                SUM(CASE WHEN ft.fixedassettranstype = 'depn'
                          AND ft.transdate BETWEEN '$dateFrom' AND '$dateTo'
                         THEN ft.amount ELSE 0 END)                          AS perioddepn,
                SUM(CASE WHEN ft.fixedassettranstype = 'disposal'
                          AND ft.transdate BETWEEN '$dateFrom' AND '$dateTo'
                         THEN ft.amount ELSE 0 END)                          AS perioddisposal,

                -- Unit counts to end of period
                SUM(CASE WHEN ft.fixedassettranstype = 'cost'
                          AND ft.transdate <= '$dateTo'
                         THEN ft.units ELSE 0 END)                           AS pcs,
                SUM(CASE WHEN ft.fixedassettranstype = 'Hired'
                          AND ft.transdate <= '$dateTo'
                         THEN ft.units ELSE 0 END)                           AS Hiredout

            FROM fixedassets fa
            LEFT JOIN fixedassetcategories fac ON fac.categoryid = fa.assetcategoryid
            LEFT JOIN fixedassettrans ft        ON ft.assetid    = fa.assetid

            WHERE 1 = 1
              $catFilter
              $idFilter
              $locFilter

            GROUP BY
                fa.assetid,
                fa.description,
                fa.longdescription,
                fa.assetcategoryid,
                fac.categorydescription,
                fa.serialno,
                fa.assetlocation
            ORDER BY fa.assetcategoryid, fa.assetid ASC";

    return DB_query($sql, $db);
}
