<?php

function db_AccountBudgets($yearIndex){
    global $db;

    $yearIndex = (int)$yearIndex;

    $dateSQL = "SELECT MIN(start_date) AS datefrom, MAX(end_date) AS dateto
                FROM FinancialPeriods
                WHERE periodno='" . $yearIndex . "'";
    $dateResult = DB_query($dateSQL, $db);
    $dateRow = DB_fetch_row($dateResult);
    $dateFrom = isset($dateRow[0]) ? $dateRow[0] : '';
    $dateTo = isset($dateRow[1]) ? $dateRow[1] : '';

    $sql = "SELECT
                b.dimecode AS Code,
                d.Dimension AS BudgetName,
                SUM(b.amount) AS BudgetAmount,
                IFNULL((
                    SELECT SUM(gl.amount)
                    FROM Generalledger gl
                    WHERE gl.dimension = b.dimecode
                      AND gl.Docdate BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                ),0) AS Expeses,
                IFNULL((
                    SELECT SUM(pl.invoiceamount)
                    FROM PurchaseHeader ph
                    JOIN PurchaseLine pl
                      ON ph.documenttype = pl.documenttype
                     AND ph.documentno = pl.documentno
                    WHERE ph.released = 1
                      AND ph.documenttype = 18
                      AND ph.Dimension_1 = b.dimecode
                ),0) AS Committed,
                (
                    IFNULL((
                        SELECT SUM(gl2.amount)
                        FROM Generalledger gl2
                        WHERE gl2.dimension = b.dimecode
                          AND gl2.Docdate BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    ),0)
                    +
                    IFNULL((
                        SELECT SUM(pl2.invoiceamount)
                        FROM PurchaseHeader ph2
                        JOIN PurchaseLine pl2
                          ON ph2.documenttype = pl2.documenttype
                         AND ph2.documentno = pl2.documentno
                        WHERE ph2.released = 1
                          AND ph2.documenttype = 18
                          AND ph2.Dimension_1 = b.dimecode
                    ),0)
                ) AS Total,
                (
                    SUM(b.amount) - (
                        IFNULL((
                            SELECT SUM(gl3.amount)
                            FROM Generalledger gl3
                            WHERE gl3.dimension = b.dimecode
                              AND gl3.Docdate BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        ),0)
                        +
                        IFNULL((
                            SELECT SUM(pl3.invoiceamount)
                            FROM PurchaseHeader ph3
                            JOIN PurchaseLine pl3
                              ON ph3.documenttype = pl3.documenttype
                             AND ph3.documentno = pl3.documentno
                            WHERE ph3.released = 1
                              AND ph3.documenttype = 18
                              AND ph3.Dimension_1 = b.dimecode
                        ),0)
                    )
                ) AS Balance,
                (
                    CASE
                        WHEN SUM(b.amount)=0 THEN 0
                        ELSE (
                            100 * (
                                (
                                    SUM(b.amount) - (
                                        IFNULL((
                                            SELECT SUM(gl4.amount)
                                            FROM Generalledger gl4
                                            WHERE gl4.dimension = b.dimecode
                                              AND gl4.Docdate BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                                        ),0)
                                        +
                                        IFNULL((
                                            SELECT SUM(pl4.invoiceamount)
                                            FROM PurchaseHeader ph4
                                            JOIN PurchaseLine pl4
                                              ON ph4.documenttype = pl4.documenttype
                                             AND ph4.documentno = pl4.documentno
                                            WHERE ph4.released = 1
                                              AND ph4.documenttype = 18
                                              AND ph4.Dimension_1 = b.dimecode
                                        ),0)
                                    )
                                ) / SUM(b.amount)
                            )
                        )
                    END
                ) AS percent
            FROM Dimensions d
            JOIN budgets b
              ON d.code = b.dimecode
             AND b.periodno = '" . $yearIndex . "'
            JOIN companies c
              ON d.id = c.DefaultDimension_1
            GROUP BY b.dimecode, d.Dimension";

    return DB_query($sql, $db);
}

function db_AccountProject($yearIndex){
    global $db;

    $yearIndex = (int)$yearIndex;

    $dateSQL = "SELECT MIN(start_date) AS datefrom, MAX(end_date) AS dateto
                FROM FinancialPeriods
                WHERE periodno='" . $yearIndex . "'";
    $dateResult = DB_query($dateSQL, $db);
    $dateRow = DB_fetch_row($dateResult);
    $dateFrom = isset($dateRow[0]) ? $dateRow[0] : '';
    $dateTo = isset($dateRow[1]) ? $dateRow[1] : '';

    $sql = "SELECT
                b.dimecode AS Code,
                d.Dimension AS BudgetName,
                SUM(b.amount) AS BudgetAmount,
                IFNULL((
                    SELECT SUM(gl.amount)
                    FROM Generalledger gl
                    WHERE gl.dimension2 = b.dimecode
                      AND gl.Docdate BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                ),0) AS Expeses,
                IFNULL((
                    SELECT SUM(pl.invoiceamount)
                    FROM PurchaseHeader ph
                    JOIN PurchaseLine pl
                      ON ph.documenttype = pl.documenttype
                     AND ph.documentno = pl.documentno
                    WHERE ph.released = 1
                      AND ph.documenttype = 18
                      AND ph.Dimension_2 = b.dimecode
                ),0) AS Committed,
                (
                    IFNULL((
                        SELECT SUM(gl2.amount)
                        FROM Generalledger gl2
                        WHERE gl2.dimension2 = b.dimecode
                          AND gl2.Docdate BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                    ),0)
                    +
                    IFNULL((
                        SELECT SUM(pl2.invoiceamount)
                        FROM PurchaseHeader ph2
                        JOIN PurchaseLine pl2
                          ON ph2.documenttype = pl2.documenttype
                         AND ph2.documentno = pl2.documentno
                        WHERE ph2.released = 1
                          AND ph2.documenttype = 18
                          AND ph2.Dimension_2 = b.dimecode
                    ),0)
                ) AS Total,
                (
                    SUM(b.amount) - (
                        IFNULL((
                            SELECT SUM(gl3.amount)
                            FROM Generalledger gl3
                            WHERE gl3.dimension2 = b.dimecode
                              AND gl3.Docdate BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                        ),0)
                        +
                        IFNULL((
                            SELECT SUM(pl3.invoiceamount)
                            FROM PurchaseHeader ph3
                            JOIN PurchaseLine pl3
                              ON ph3.documenttype = pl3.documenttype
                             AND ph3.documentno = pl3.documentno
                            WHERE ph3.released = 1
                              AND ph3.documenttype = 18
                              AND ph3.Dimension_2 = b.dimecode
                        ),0)
                    )
                ) AS Balance,
                (
                    CASE
                        WHEN SUM(b.amount)=0 THEN 0
                        ELSE (
                            100 * (
                                (
                                    SUM(b.amount) - (
                                        IFNULL((
                                            SELECT SUM(gl4.amount)
                                            FROM Generalledger gl4
                                            WHERE gl4.dimension2 = b.dimecode
                                              AND gl4.Docdate BETWEEN '" . $dateFrom . "' AND '" . $dateTo . "'
                                        ),0)
                                        +
                                        IFNULL((
                                            SELECT SUM(pl4.invoiceamount)
                                            FROM PurchaseHeader ph4
                                            JOIN PurchaseLine pl4
                                              ON ph4.documenttype = pl4.documenttype
                                             AND ph4.documentno = pl4.documentno
                                            WHERE ph4.released = 1
                                              AND ph4.documenttype = 18
                                              AND ph4.Dimension_2 = b.dimecode
                                        ),0)
                                    )
                                ) / SUM(b.amount)
                            )
                        )
                    END
                ) AS percent
            FROM Dimensions d
            JOIN budgets b
              ON d.code = b.dimecode
             AND b.periodno = '" . $yearIndex . "'
            JOIN companies c
              ON d.id = c.DefaultDimension_2
            GROUP BY b.dimecode, d.Dimension";

    return DB_query($sql, $db);
}

?>
