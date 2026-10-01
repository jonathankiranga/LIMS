<?php

function CalculateAveCost($docno){
    Global $db;

    $SQL = "SELECT
                stockmaster.itemcode,
                PurchaseLine.Quantity,
                (PurchaseLine.UnitPrice * currencies.rate) AS UnitPrice,
                IFNULL(stockmaster.averagestock,0) AS AveCost,
                IFNULL(SUM(stockledger.fulqty * stockledger.partperunit),0) + IFNULL(SUM(stockledger.loosqty),0) AS StockBalance
            FROM PurchaseLine
            JOIN stockmaster ON stockmaster.itemcode=PurchaseLine.code
            LEFT JOIN stockledger ON stockmaster.itemcode=stockledger.itemcode
            JOIN PurchaseHeader ON PurchaseLine.documentno=PurchaseHeader.documentno
            JOIN currencies ON PurchaseHeader.currencycode=currencies.currabrev
            WHERE PurchaseLine.documentno='" . $docno . "'
            GROUP BY
                stockmaster.itemcode,
                stockmaster.averagestock,
                PurchaseLine.UOM,
                PurchaseLine.UnitPrice,
                PurchaseLine.Quantity,
                currencies.rate,
                stockmaster.partperunit";

    $ResultIndex = DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $itemcode = $row['itemcode'];
        $Quantity = (float)$row['Quantity'];
        $UnitPrice = (float)$row['UnitPrice'];
        $AveCost = (float)$row['AveCost'];
        $StockBalance = (float)$row['StockBalance'];
        $denominator = $StockBalance + $Quantity;

        if($denominator > 0){
            $newAveCost = (($Quantity * $UnitPrice) + ($AveCost * $StockBalance)) / $denominator;
        } else {
            $newAveCost = $UnitPrice;
        }

        $updateSQL = sprintf(
            "UPDATE stockmaster SET averagestock=%f WHERE itemcode='%s'",
            $newAveCost,
            $itemcode
        );
        DB_query($updateSQL, $db);
    }
}

?>