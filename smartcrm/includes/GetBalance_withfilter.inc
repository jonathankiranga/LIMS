<?php

function db_GetBalance_withfilter($prd,$accno){
Global $db;

$sql = "SELECT
        IFNULL((
            SELECT SUM(gl.amount * gl.ExchangeRate)
            FROM Generalledger gl
            WHERE gl.accountcode = acct.accno
              AND gl.period < " . (int)$prd . "
        ),0) AS debit,
        IFNULL((
            SELECT SUM(gl.amount * gl.ExchangeRate)
            FROM Generalledger gl
            WHERE gl.balaccountcode = acct.accno
              AND gl.period < " . (int)$prd . "
        ),0) AS credit,
        IFNULL(acct.ReportCode,'') AS ReportCode,
        acct.accdesc,
        IFNULL(acct.balance_income,0) AS balance_income,
        IFNULL(acct.ReportStyle,0) AS ReportStyle,
        acct.direct,
        IFNULL(acct.Sale_Purchase_Neither,0) AS Sale_Purchase_Neither,
        acct.accno,
        acct.Calculation
    FROM acct
    WHERE acct.accno='" . $accno . "'
    LIMIT 1";

return DB_query($sql,$db);
};

?>
