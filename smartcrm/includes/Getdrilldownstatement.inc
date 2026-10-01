<?php

$Perdno  = GetPeriod($_POST['fromdate'], $db);

 $REsults = db_GetBalance_withfilter($Perdno,$_POST['Accountcode']);
$row     = DB_fetch_row($REsults);
$openbal = ($row[0] - $row[1]);

$SQLDrilldown ="SELECT 
    `rowid`,
    `journalno`,
    `Docdate`,
    `period`,
    `DocumentNo`,
    `DocumentType`,
    `accountcode`,
    `balaccountcode`,
    (CASE
      WHEN `accountcode`='".$_POST['Accountcode']."'
      THEN (`amount` * `ExchangeRate`)
      ELSE (0-`amount`) * `ExchangeRate`
    END) AS AMOUNT,
      `currencycode`,
      `ExchangeRate`,
      `cutomercode`,
      `suppliercode`,
      `bankcode`,
      `reconcilled`,
      `narration`,
      `ExchangeRateDiff`,
      `VATaccountcode`,
      `VATamount`,
      `dimension`,
      `dimension2`,
      ".$balance_income." AS `balance_income`,
      '".$openbal."' as balcfwd    
    from  `Generalledger` 
    where (`Generalledger`.`accountcode`='".$_POST['Accountcode']."' "
        . " or  `Generalledger`.`balaccountcode`='".$_POST['Accountcode']."')"
        . " and `Generalledger`.`Docdate` between '".FormatDateForSQL($_POST['fromdate'])."' "
        . " and '".FormatDateForSQL($_POST['Todate'])."'"
        . " order by `Docdate`,`rowid` asc "
     

?>
