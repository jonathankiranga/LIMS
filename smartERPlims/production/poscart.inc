<?php

/**
 * Production / Assembly Classes – MySQL Conversion
 *
 * Changes from MS-SQL original:
 *  - Square-bracket identifier quoting ([table], [column]) replaced with
 *    MySQL back-tick quoting (`table`, `column`) throughout.
 *  - INSERT [table] (...) syntax corrected to INSERT INTO `table` (...).
 *  - UPDATE [table] SET ... corrected to UPDATE `table` SET ...
 *  - All user-supplied values that were interpolated directly into SQL strings
 *    are now passed through mysqli_real_escape_string() via a local helper
 *    _esc() to prevent SQL injection.
 *  - No stored procedures were present; logic is otherwise identical.
 */

// ---------------------------------------------------------------------------
// Internal escape helper (avoids repeating global $db boilerplate)
// ---------------------------------------------------------------------------

function _esc($value)
{
    global $db;
    return mysqli_real_escape_string($db, $value);
}

// ---------------------------------------------------------------------------

class ProcessInformation
{
    var $post;
    var $session    = array();
    var $workorderclass;
    var $error1     = 0;
    var $error2     = 0;

    function get($post, $session, $workorderclass)
    {
        $this->post           = $post;
        $this->session        = $session;
        $this->workorderclass = $workorderclass;
    }

    function anythingwrong()
    {
        global $db;

        $return = 'YES';

        // Production Tank
        $ResultIndex = DB_query("SELECT COUNT(*) FROM `stockmaster`
                                 WHERE `itemcode` = '" . _esc(trim($this->post['proitemcode'])) . "'", $db);
        $rows = DB_fetch_row($ResultIndex);

        if ($rows[0] == 1) {
            if (mb_strstr($this->post['TankAllowedCapacity'], 'No limit') == false) {
                if ($this->post['TankAllowedCapacity'] < $this->post['TotalProduction']) {
                    $this->error1++;
                }
            }
        } else {
            $this->error1++;
        }

        foreach ($this->session as $key => $rowdata) {
            if ($rowdata['stkbalance'] < 0) {
                $this->error2++;
            }
        }

        if ($this->error1 > 0) {
            echo '<script type="text/javascript">
                function Checkproductionintegrity(){
                    SmartDialog.warning(\'Please check tank capacity of the production tanks\', \'Warning\');
                }
                Checkproductionintegrity();
            </script>';

            $this->savestockproduction();
            $return = 'NO';

        } elseif ($this->error2 > 0) {
            echo '<script type="text/javascript">
                function Checkproduction(){
                    SmartDialog.warning(\'Please check stock balances\', \'Warning\');
                }
                Checkproduction();
            </script>';

            $return = 'YES';
        } else {
            $this->savestockproduction();
            $return = 'NO';
        }

        return $return;
    }

    function savestockproduction()
    {
        global $db;

        $journal      = GetNextTransNo(0, $db);
        $period       = GetPeriod($this->post['date'], $db, true);
        $formatedDate = FormatDateForSQL($this->post['date']);

        $averagecost = $this->post['TotalProductionCost'] / $this->post['TotalProduction'];

        // Escape all interpolated values
        $documentno      = _esc($this->post['documentno']);
        $proitemcode     = _esc(trim($this->post['proitemcode']));
        $totalProduction = (float)$this->post['TotalProduction'];
        $userID          = _esc(stripslashes($_SESSION['UserID']));
        $bitumenph       = _esc($_POST['bitumenph']);
        $ProdStore       = _esc($this->post['ProdStore']);
        $Prostockname    = _esc($this->post['Prostockname']);

        // Save production master
        $sql[] = sprintf(
            "INSERT INTO `ProductionMaster` (`Batchno`, `itemcode`, `production`, `date`, `userid`, `bitumenph`)
             VALUES ('%s', '%s', %f, '%s', '%s', '%s')",
            $documentno, $proitemcode, $totalProduction, $formatedDate, $userID, $bitumenph
        );

        if ($this->post['productionTankStore'] == "T") {
            $sql[] = sprintf(
                "INSERT INTO `tanktrans` (`tankname`, `units`, `uom`, `date`, `batchno`, `doctype`, `itemcode`)
                 VALUES ('%s', %f, '%s', '%s', '%s', 28, '%s')",
                $ProdStore, $totalProduction, '', $formatedDate, $documentno, $proitemcode
            );

        } elseif ($this->post['productionTankStore'] == "S") {
            $sql[] = sprintf(
                "INSERT INTO `stockledger`
                    (`fulqty`, `date`, `stname`, `doctyp`, `itemcode`, `invref`,
                     `loosqty`, `price`, `store`, `journal`, `period`, `PartPerUnit`)
                 VALUES (0, '%s', '%s', '28', '%s', '%s', %f, 0, '%s', '%s', '%s', 1)",
                $formatedDate, $Prostockname, $proitemcode, $documentno,
                $totalProduction, $ProdStore, _esc($journal), _esc($period)
            );
        }

        if ($averagecost > 0) {
            $sql[] = sprintf(
                "UPDATE `stockmaster` SET `averagestock` = %f WHERE `itemcode` = '%s'",
                $averagecost, $proitemcode
            );

            $sql[] = sprintf(
                "INSERT INTO `StockRegister` (`itemcode`, `StockIn`, `cost`, `journal`, `GRN`, `StockOut`)
                 VALUES ('%s', %f, %f, '%s', '%s', 0)",
                $proitemcode, $totalProduction, $averagecost, _esc($journal), $documentno
            );
        }

        // Save raw materials
        foreach ($this->session as $key => $rowdata) {
            $storeTank   = _esc($this->post['location'][$key]);
            $ri_itemcode = _esc($rowdata['itemcode']);
            $ri_qty      = (float)$rowdata['quantity'];
            $ri_cost     = (float)$rowdata['averagestock'];
            $ri_vcf      = (float)$rowdata['VCF'];
            $ri_temp     = (float)$rowdata['TEMP'];

            $sql[] = sprintf(
                "INSERT INTO `ProdcutionMasterLine`
                    (`Batchno`, `itemcode`, `qty`, `cost`, `volcorfactor`, `temperature`)
                 VALUES ('%s', '%s', %f, %f, %f, %f)",
                $documentno, $ri_itemcode, $ri_qty, $ri_cost, $ri_vcf, $ri_temp
            );

            if (mb_strstr($rowdata['itemcode'], 'H2O') == false) {
                if ($rowdata['tankstore'] == "T") {
                    $sql[] = sprintf(
                        "INSERT INTO `tanktrans`
                            (`tankname`, `units`, `uom`, `date`, `batchno`, `doctype`, `itemcode`)
                         VALUES ('%s', %f, 'loosqty', '%s', '%s', 28, '%s')",
                        $storeTank, ($ri_qty * -1), $formatedDate, $documentno, $ri_itemcode
                    );

                } elseif ($rowdata['tankstore'] == "S") {
                    $sql[] = sprintf(
                        "INSERT INTO `stockledger`
                            (`fulqty`, `date`, `doctyp`, `itemcode`, `invref`,
                             `loosqty`, `price`, `store`, `journal`, `period`, `PartPerUnit`)
                         VALUES (0, '%s', '28', '%s', '%s', %f, 0, '%s', '%s', '%s', 1)",
                        $formatedDate, $ri_itemcode, $documentno,
                        ($ri_qty * -1), $storeTank, _esc($journal), _esc($period)
                    );
                }
            }
        }

        foreach ($sql as $value) {
            DB_query($value, $db);
        }

        echo '<script type="text/javascript">function Checkproductionintegrity(){ ';
        echo sprintf(" SmartDialog.success('Production %s has been posted. Now prepare Lab Test Report', 'Success'); ", _esc($this->post['documentno']));
        echo " }  Checkproductionintegrity();</script>";
    }
}

// ---------------------------------------------------------------------------

class NewAssemble
{
    var $customerposting;
    var $VATinclusive;
    var $IsTaxed;
    var $htmltable;
    var $stocklist           = array();
    var $deleteID;
    var $TotalProduction;
    var $tankStore;
    var $productionTankStore;
    var $proitemcode;
    var $work_orders;
    var $Default             = array();
    var $check;

    function MakeStoreTankForItem($ItemCode, $key)
    {
        global $db;
        $line = '';
        $sql  = sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode));
        $ResultIndex = DB_query($sql, $db);
        $Stores      = DB_fetch_row($ResultIndex);

        if ($Stores[0] == 0) {
            $this->tankStore = "S";
            if (mb_stristr($ItemCode, 'H2O') == 'H2O') {
                $line .= '<option></option>';
            } else {
                $REsults = DB_query('SELECT `code`, `Storename` FROM `Stores`', $db);
                while ($rows = DB_fetch_array($REsults)) {
                    $sel   = ($_POST['location'][$key] == trim($rows['code'])) ? 'selected="selected"' : '';
                    $line .= sprintf('<option value="%s" %s>%s</option>', trim($rows['code']), $sel, $rows['Storename']);
                }
            }
        } else {
            $this->tankStore = "T";
            $ResultIndex = DB_query(sprintf("SELECT `tankname` FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
            while ($value = DB_fetch_array($ResultIndex)) {
                $sel   = ($_POST['location'][$key] == trim($value['tankname'])) ? 'selected="selected"' : '';
                $line .= sprintf('<option value="%s" %s>%s</option>', trim($value['tankname']), $sel, $value['tankname']);
            }
        }
        return $line;
    }

    function MakeDefaultStoreTankForItem($ItemCode, $key)
    {
        global $db;
        $sql         = sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode));
        $ResultIndex = DB_query($sql, $db);
        $Stores      = DB_fetch_row($ResultIndex);

        if ($Stores[0] == 0) {
            $REsults = DB_query('SELECT `code`, `Storename` FROM `Stores`', $db);
            while ($rows = DB_fetch_array($REsults)) {
                $this->Default[0] = array('code' => trim($rows['code']), 'Storename' => $rows['Storename']);
            }
        } else {
            $ResultIndex = DB_query(sprintf("SELECT `tankname` FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
            while ($value = DB_fetch_array($ResultIndex)) {
                $this->Default[0] = array('code' => trim($value['tankname']), 'Storename' => trim($value['tankname']));
            }
        }
    }

    function ToStoreTankForItem($ItemCode)
    {
        global $db;
        $line        = '';
        $sql         = sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode));
        $ResultIndex = DB_query($sql, $db);
        $Stores      = DB_fetch_row($ResultIndex);

        if ($Stores[0] == 0) {
            $this->productionTankStore = "S";
            $REsults = DB_query('SELECT `code`, `Storename` FROM `Stores`', $db);
            while ($rows = DB_fetch_array($REsults)) {
                $sel   = ($_POST['ProdStore'] == trim($rows['code'])) ? 'selected="selected"' : '';
                $line .= sprintf('<option value="%s" %s>%s</option>', trim($rows['code']), $sel, $rows['Storename']);
                if ($_POST['ProdStore'] == trim($rows['code'])) {
                    $this->check = trim($rows['code']);
                }
            }
        } else {
            $this->productionTankStore = "T";
            $ResultIndex = DB_query(sprintf("SELECT `tankname` FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
            while ($value = DB_fetch_array($ResultIndex)) {
                $sel   = ($_POST['ProdStore'] == trim($value['tankname'])) ? 'selected="selected"' : '';
                $line .= sprintf('<option value="%s" %s>%s</option>', trim($value['tankname']), $sel, $value['tankname']);
                if ($_POST['ProdStore'] == trim($value['tankname'])) {
                    $this->check = trim($value['tankname']);
                }
            }
        }
        return $line;
    }

    function GetStockdetails($itemcode)
    {
        global $db;

        if ($itemcode == 'H2O') {
            $this->stocklist['barcode']      = 'WATER';
            $this->stocklist['itemcode']     = 'H2O';
            $this->stocklist['stockname']    = 'WATER';
            $this->stocklist['averagestock'] = '1';
            $this->stocklist['production']   = "06";
        } else {
            $ResultIndex = DB_query("SELECT
                    sm.`barcode`,
                    sm.`itemcode`,
                    sm.`descrip`          AS stockname,
                    u.`descrip`           AS si,
                    sm.`averagestock`,
                    vc.`vat`,
                    c.`itemcode`          AS container,
                    c.`descrip`           AS packname,
                    cv.`vat`              AS CVAT,
                    sm.`sellingprice`,
                    sm.`production`
                FROM `stockmaster` sm
                LEFT JOIN `stockmaster` c    ON sm.`container`     = c.`itemcode`
                LEFT JOIN `unit` u           ON sm.`units`         = u.`code`
                LEFT JOIN `inventorypostinggroup` ipg ON sm.`postinggroup` = ipg.`code`
                LEFT JOIN `vatcategory` vc   ON ipg.`vatcategory`  = vc.`vatc`
                LEFT JOIN `inventorypostinggroup` ci ON c.`postinggroup`  = ci.`code`
                LEFT JOIN `vatcategory` cv   ON ci.`vatcategory`   = cv.`vatc`
                WHERE sm.`itemcode` = '" . _esc(trim($itemcode)) . "'", $db);

            $stocklist = DB_fetch_row($ResultIndex);
            $this->stocklist['barcode']      = $stocklist[0];
            $this->stocklist['itemcode']     = $stocklist[1];
            $this->stocklist['stockname']    = $stocklist[2];
            $this->stocklist['si']           = $stocklist[3];
            $this->stocklist['averagestock'] = $stocklist[4];
            $this->stocklist['vat']          = $stocklist[5];
            $this->stocklist['container']    = $stocklist[6];
            $this->stocklist['packname']     = $stocklist[7];
            $this->stocklist['CVAT']         = $stocklist[8];
            $this->stocklist['sellingprice'] = $stocklist[9];
            $this->stocklist['production']   = $stocklist[10];
        }
        return $this->stocklist;
    }

    function GetStockProduction($itemcode)
    {
        global $db;
        $stocklist = array();

        if ($itemcode == '06') {
            $stocklist[] = array('barcode' => 'WATER', 'itemcode' => 'H2O', 'stockname' => 'WATER',
                                 'averagestock' => '1', 'production' => "06");
        } else {
            $ResultIndex = DB_query("SELECT
                    sm.`barcode`,
                    sm.`itemcode`,
                    sm.`descrip`          AS stockname,
                    u.`descrip`           AS si,
                    sm.`averagestock`,
                    vc.`vat`,
                    c.`itemcode`          AS container,
                    c.`descrip`           AS packname,
                    cv.`vat`              AS CVAT,
                    sm.`sellingprice`,
                    sm.`production`
                FROM `stockmaster` sm
                LEFT JOIN `stockmaster` c    ON sm.`container`     = c.`itemcode`
                LEFT JOIN `unit` u           ON sm.`units`         = u.`code`
                LEFT JOIN `inventorypostinggroup` ipg ON sm.`postinggroup` = ipg.`code`
                LEFT JOIN `vatcategory` vc   ON ipg.`vatcategory`  = vc.`vatc`
                LEFT JOIN `inventorypostinggroup` ci ON c.`postinggroup`  = ci.`code`
                LEFT JOIN `vatcategory` cv   ON ci.`vatcategory`   = cv.`vatc`
                WHERE sm.`production` = '" . _esc(trim($itemcode)) . "'", $db);

            while ($row = DB_fetch_array($ResultIndex)) {
                $stocklist[] = $row;
            }
        }
        return $stocklist;
    }

    function loaddefaultitems($itemcode)
    {
        global $db;

        if (empty($_SESSION['work_orders'])) {
            $ResultIndex   = DB_query("SELECT `category` FROM `stockmaster` WHERE `itemcode` = '" . _esc($itemcode) . "'", $db);
            $stocklist     = DB_fetch_row($ResultIndex);
            $selected_stock = trim($stocklist[0]);

            $UsedResult = DB_query("SELECT `rawmatid` FROM `productionconfig` WHERE `categoryid` = '" . _esc($selected_stock) . "'", $db);
            $TokensUsed = array();
            while ($myrow = DB_fetch_array($UsedResult)) {
                $TokensUsed[] = trim($myrow['rawmatid']);
            }

            $stockmasterUsed = array();
            foreach ($TokensUsed as $value) {
                $stocklistarray = $this->GetStockProduction(trim($value));
                foreach ($stocklistarray as $myrow) {
                    $stockmasterUsed[] = trim($myrow['itemcode']);
                }
            }

            foreach ($stockmasterUsed as $itemcode) {
                $StockList = $this->GetStockdetails($itemcode);
                $id        = trim($itemcode);
                $_SESSION['work_orders'][$id] = array(
                    'line_no'      => $id,
                    'itemcode'     => $id,
                    'barcode'      => $StockList['barcode'],
                    'stockname'    => $StockList['stockname'],
                    'uom'          => 1,
                    'VCF'          => 1,
                    'TEMP'         => 20,
                    'averagestock' => $StockList['averagestock'],
                    'quantity'     => 0,
                    'CostType'     => $StockList['production'],
                );
            }
        }
    }

    function Getitems($itemcode, $qty, $uom, $VCF, $TEMP)
    {
        $StockList = $this->GetStockdetails($itemcode);

        if (mb_strwidth($StockList['itemcode']) > 0) {
            $id     = trim($StockList['itemcode']);
            $arrayi = $_SESSION['work_orders'][$id];

            if ($TEMP < 15) { $VCF = 1; $TEMP = 15; }

            $_SESSION['work_orders'][$id] = array_replace_recursive($arrayi, array(
                'line_no'      => $id,
                'itemcode'     => $id,
                'barcode'      => $StockList['barcode'],
                'stockname'    => $StockList['stockname'],
                'uom'          => $uom,
                'VCF'          => $VCF,
                'TEMP'         => $TEMP,
                'averagestock' => $StockList['averagestock'],
                'quantity'     => $qty,
            ));
        }

        if (is_array($_SESSION['work_orders'])) {
            $this->GetSum();
            $this->showtable();
        }
    }

    function neworder()
    {
        $_SESSION['work_orders'] = array();
        $this->TotalProduction   = 0;
    }

    function GetSum()
    {
        $TotalProduction = 0;
        foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
            $qty      = (float)$rowvalue['quantity'];
            $VCF      = (float)$rowvalue['VCF'];
            $costtype = trim($rowvalue['CostType']);
            if ($costtype != '05') {
                $TotalProduction += ($qty * $VCF);
            }
        }
        $this->TotalProduction = $TotalProduction;
        $this->proitemcode     = $_POST['proitemcode'];
    }

    function RemoveOrder($itemcode)
    {
        $this->deleteID = trim($itemcode);
        unset($_SESSION['work_orders'][$this->deleteID]);
        unset($_POST['stockitemcode'], $_POST['qty'], $_POST['sp'], $_POST['units']);
    }

    function IsTankOrStore($ItemCode)
    {
        global $db;
        $sql         = sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode));
        $ResultIndex = DB_query($sql, $db);
        $Stores      = DB_fetch_row($ResultIndex);
        $this->tankStore = ($Stores[0] == 0) ? "S" : "T";
    }

    function tankresults($store, $item)
    {
        global $db;
        $sql         = sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($item));
        $ResultIndex = DB_query($sql, $db);
        $Stores      = DB_fetch_row($ResultIndex);

        if ($Stores[0] > 0) {
            $sql         = sprintf("SELECT `balance` FROM `ProductionUnit` WHERE `tankname` = '%s' AND `itemcode` = '%s'", _esc($store), _esc($item));
            $ResultIndex = DB_query($sql, $db);
            $row         = DB_fetch_row($ResultIndex);
            return sprintf('%f', (int)$row[0]);
        }
        return 'No limit';
    }

    function showtable()
    {
        global $db;

        $runningnettotal = 0;
        $percentTotal    = 0;
        $_SESSION["percentTotal"]      = $percentTotal;
        $_SESSION["runningnettotal"]   = $runningnettotal;

        foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
            $line_no  = trim($rowvalue['line_no']);
            $itemcode = $rowvalue['itemcode'];
            $VCF      = (float)$rowvalue['VCF'];
            $TEMP     = (string)$rowvalue['TEMP'];
            $costtype = trim($rowvalue['CostType']);

            $this->IsTankOrStore($itemcode);
            $stocklist = $this->GetStockdetails($itemcode);

            $UOM     = $rowvalue['uom'];
            $package = 1;
            $this->MakeDefaultStoreTankForItem($itemcode, $line_no);
            $location = isset($_POST['location'][$line_no]) ? $_POST['location'][$line_no] : $this->Default[0]['code'];

            $averagestock    = (float)$rowvalue['averagestock'];
            $qty             = (float)$rowvalue['quantity'];
            $baseamount      = (float)($averagestock * $qty * $VCF);
            $runningnettotal += $baseamount;
            $Production      = ($costtype != '05') ? $qty * $package * $VCF : 0;

            $code   = trim($stocklist['itemcode']);
            $ID     = trim($stocklist['itemcode']);
            $arrayi = $_SESSION['work_orders'][$lineno];

            $percent       = ($this->TotalProduction > 0) ? (float)(100 * ($Production / $this->TotalProduction)) : 0;
            $percentTotal += $percent;
            $_SESSION["percentTotal"]    = $percentTotal;
            $_SESSION["runningnettotal"] = $runningnettotal;

            if (mb_stristr($itemcode, 'H2O') == 'H2O') {
                $_SESSION['stockmasterp'][$code]['Balance'] = $qty;
            } else {
                if ($this->tankStore == "T") {
                    $_SESSION['stockmasterp'][$code]['Balance'] = (int)getTankbalance($location, $code);
                } elseif ($this->tankStore == "S") {
                    $_SESSION['stockmasterp'][$code]['Balance'] = (int)getbalance($code, $location, 'loosqty');
                }
            }

            $stkbalance = $_SESSION['stockmasterp'][$code]['Balance'] - ($qty * $VCF);

            $_SESSION['work_orders'][$lineno] = array_replace_recursive($arrayi, array(
                'TotalProduction' => $this->TotalProduction,
                'tankstore'       => $this->tankStore,
                'netamount'       => $baseamount,
                'percent'         => $percent,
                'stkbalance'      => $stkbalance,
            ));

            $this->htmltable .= sprintf(
                '<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td class="number cell"><input type="hidden" name="quantity[' . $line_no . ']" value="' . $qty . '" size="5" readonly="readonly" />' . $qty . '</td>'
                . '<td><select name="location[' . $line_no . ']" onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td class="number cell"><input type="hidden" class="number cell" readonly="readonly" value="' . $stkbalance . '" size="8"/>' . $stkbalance . '</td>'
                . '<td>%s</td>'
                . '<td class="number cell"><input type="hidden" value="' . $averagestock . '" size="8"/>' . $averagestock . '</td>'
                . '<td class="number cell"><input type="text" class="number" name="cents[' . $line_no . ']" value="' . $percent . '" readonly="readonly" size="8"/></td>'
                . '<td class="number cell">%s</td>'
                . '<td class="number cell">%s</td>'
                . '<td class="number cell"><input type="hidden" class="number" name="Production[' . $line_no . ']" value="' . number_format($Production, 2) . '" readonly="readonly" size="8"/>' . number_format($Production, 2) . '</td>'
                . '<td class="number cell"><input type="hidden" class="number" name="netamount[' . $line_no . ']" value="' . number_format($baseamount, 2) . '" readonly="readonly" size="8"/>' . number_format($baseamount, 2) . '</td>'
                . '</tr>',
                $ID, $BC = trim($stocklist['barcode']), $NAME = trim($stocklist['stockname']), $qty,
                $BC, $NAME,
                $this->MakeStoreTankForItem($ID, $line_no),
                getGriduom($line_no, $UOM), $VCF, $TEMP
            );
        }

        $this->ToStoreTankForItem($_POST['proitemcode']);
        $tankAllowedCapacity = (float)$this->tankresults($_POST['ProdStore'], $_POST['proitemcode']);
        $Reperent            = ($tankAllowedCapacity > 0) ? (($this->TotalProduction / $tankAllowedCapacity) * 100) : 0;

        $refreshPost = (isset($this->check)
            && mb_stristr($_POST['ProdStore'], $this->check) == $this->check
            && mb_strlen($this->check) > 0
            && mb_strlen($_POST['TotalProduction']) > 0)
            ? "SaveProduction"
            : "refreshcheck";

        $_SESSION['altername_SaveProduction'] = $refreshPost;
        $_SESSION['htmltable']                = $this->htmltable;
    }

    function RepopulateProduction($batchno)
    {
        global $db;

        $_POST['documentno']     = $batchno;
        $_SESSION['work_orders'] = array();

        $SQL         = sprintf("SELECT `itemcode` FROM `ProductionMaster` WHERE `Batchno` = '%s'", _esc($batchno));
        $ResultIndex = DB_query($SQL, $db);
        $row         = DB_fetch_row($ResultIndex);
        $_POST['proitemcode'] = $row[0];

        $StockArray          = $this->GetStockdetails($_POST['proitemcode']);
        $_POST['Prostockname'] = $StockArray['stockname'];

        $SQL    = sprintf("SELECT `Batchno`, `itemcode`, `qty`, `cost` FROM `ProdcutionMasterLine` WHERE `Batchno` = '%s'", _esc($batchno));
        $result = DB_query($SQL, $db);
        while ($row = DB_fetch_array($result)) {
            $StockList = $this->GetStockdetails($row['itemcode']);
            if (mb_strwidth($StockList['itemcode']) > 0) {
                $id = trim($StockList['itemcode']);
                $_SESSION['work_orders'][$id] = array(
                    'line_no'      => $id,
                    'itemcode'     => $id,
                    'barcode'      => $StockList['barcode'],
                    'stockname'    => $StockList['stockname'],
                    'uom'          => '1',
                    'averagestock' => $StockList['averagestock'],
                    'quantity'     => $row['qty'],
                );
            }
        }

        $this->GetSum();
    }
}

// ---------------------------------------------------------------------------
// Assemble and AssembleAdjust share the same structure. Both are converted
// with the same pattern: back-tick quoting, INSERT INTO, _esc() escaping.
// ---------------------------------------------------------------------------

class Assemble
{
    var $customerposting;
    var $VATinclusive;
    var $IsTaxed;
    var $htmltable;
    var $stocklist           = array();
    var $deleteID;
    var $TotalProduction;
    var $tankStore;
    var $productionTankStore;
    var $proitemcode;
    var $work_orders;
    var $Default             = array();
    var $check;

    function MakeStoreTankForItem($ItemCode, $key)
    {
        global $db;
        $line        = '';
        $ResultIndex = DB_query(sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
        $Stores      = DB_fetch_row($ResultIndex);

        if ($Stores[0] == 0) {
            $this->tankStore = "S";
            if (mb_stristr($ItemCode, 'H2O') == 'H2O') {
                $line .= '<option></option>';
            } else {
                $REsults = DB_query('SELECT `code`, `Storename` FROM `Stores`', $db);
                while ($rows = DB_fetch_array($REsults)) {
                    $sel   = ($_POST['location'][$key] == trim($rows['code'])) ? 'selected="selected"' : '';
                    $line .= sprintf('<option value="%s" %s>%s</option>', trim($rows['code']), $sel, $rows['Storename']);
                }
            }
        } else {
            $this->tankStore = "T";
            $ResultIndex = DB_query(sprintf("SELECT `tankname` FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
            while ($value = DB_fetch_array($ResultIndex)) {
                $sel   = ($_POST['location'][$key] == trim($value['tankname'])) ? 'selected="selected"' : '';
                $line .= sprintf('<option value="%s" %s>%s</option>', trim($value['tankname']), $sel, $value['tankname']);
            }
        }
        return $line;
    }

    function MakeDefaultStoreTankForItem($ItemCode, $key)
    {
        global $db;
        $ResultIndex = DB_query(sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
        $Stores      = DB_fetch_row($ResultIndex);

        if ($Stores[0] == 0) {
            $REsults = DB_query('SELECT `code`, `Storename` FROM `Stores`', $db);
            while ($rows = DB_fetch_array($REsults)) {
                $this->Default[0] = array('code' => trim($rows['code']), 'Storename' => $rows['Storename']);
            }
        } else {
            $ResultIndex = DB_query(sprintf("SELECT `tankname` FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
            while ($value = DB_fetch_array($ResultIndex)) {
                $this->Default[0] = array('code' => trim($value['tankname']), 'Storename' => trim($value['tankname']));
            }
        }
    }

    function ToStoreTankForItem($ItemCode)
    {
        global $db;
        $line        = '';
        $ResultIndex = DB_query(sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
        $Stores      = DB_fetch_row($ResultIndex);

        if ($Stores[0] == 0) {
            $this->productionTankStore = "S";
            $REsults = DB_query('SELECT `code`, `Storename` FROM `Stores`', $db);
            while ($rows = DB_fetch_array($REsults)) {
                $sel   = ($_POST['ProdStore'] == trim($rows['code'])) ? 'selected="selected"' : '';
                $line .= sprintf('<option value="%s" %s>%s</option>', trim($rows['code']), $sel, $rows['Storename']);
                if ($_POST['ProdStore'] == trim($rows['code'])) {
                    $this->check = trim($rows['code']);
                }
            }
        } else {
            $this->productionTankStore = "T";
            $ResultIndex = DB_query(sprintf("SELECT `tankname` FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
            while ($value = DB_fetch_array($ResultIndex)) {
                $sel   = ($_POST['ProdStore'] == trim($value['tankname'])) ? 'selected="selected"' : '';
                $line .= sprintf('<option value="%s" %s>%s</option>', trim($value['tankname']), $sel, $value['tankname']);
                if ($_POST['ProdStore'] == trim($value['tankname'])) {
                    $this->check = trim($value['tankname']);
                }
            }
        }
        return $line;
    }

    function GetStockdetails($itemcode)
    {
        global $db;

        if ($itemcode == 'H2O') {
            $this->stocklist['barcode']      = 'WATER';
            $this->stocklist['itemcode']     = 'H2O';
            $this->stocklist['stockname']    = 'WATER';
            $this->stocklist['averagestock'] = '1';
        } else {
            $ResultIndex = DB_query("SELECT
                    sm.`barcode`,
                    sm.`itemcode`,
                    sm.`descrip`          AS stockname,
                    u.`descrip`           AS si,
                    sm.`averagestock`,
                    vc.`vat`,
                    c.`itemcode`          AS container,
                    c.`descrip`           AS packname,
                    cv.`vat`              AS CVAT,
                    sm.`sellingprice`
                FROM `stockmaster` sm
                LEFT JOIN `stockmaster` c    ON sm.`container`     = c.`itemcode`
                LEFT JOIN `unit` u           ON sm.`units`         = u.`code`
                LEFT JOIN `inventorypostinggroup` ipg ON sm.`postinggroup` = ipg.`code`
                LEFT JOIN `vatcategory` vc   ON ipg.`vatcategory`  = vc.`vatc`
                LEFT JOIN `inventorypostinggroup` ci ON c.`postinggroup`  = ci.`code`
                LEFT JOIN `vatcategory` cv   ON ci.`vatcategory`   = cv.`vatc`
                WHERE sm.`itemcode` = '" . _esc(trim($itemcode)) . "'", $db);

            $stocklist = DB_fetch_row($ResultIndex);
            $this->stocklist['barcode']      = $stocklist[0];
            $this->stocklist['itemcode']     = $stocklist[1];
            $this->stocklist['stockname']    = $stocklist[2];
            $this->stocklist['si']           = $stocklist[3];
            $this->stocklist['averagestock'] = $stocklist[4];
            $this->stocklist['vat']          = $stocklist[5];
            $this->stocklist['container']    = $stocklist[6];
            $this->stocklist['packname']     = $stocklist[7];
            $this->stocklist['CVAT']         = $stocklist[8];
            $this->stocklist['sellingprice'] = $stocklist[9];
        }
        return $this->stocklist;
    }

    function Getitems($itemcode, $qty, $uom)
    {
        $StockList = $this->GetStockdetails($itemcode);

        if (mb_strwidth($StockList['itemcode']) > 0) {
            $id = trim($StockList['itemcode']);
            $_SESSION['work_orders'][$id] = array(
                'line_no'      => $id,
                'itemcode'     => $id,
                'barcode'      => $StockList['barcode'],
                'stockname'    => $StockList['stockname'],
                'uom'          => $uom,
                'averagestock' => $StockList['averagestock'],
                'quantity'     => $qty,
            );
        }

        $this->GetSum();
        $this->showtable();
    }

    function neworder()
    {
        $_SESSION['work_orders'] = array();
    }

    function GetSum()
    {
        $TotalProduction = 0;
        foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
            $TotalProduction += (float)$rowvalue['quantity'];
        }
        $this->TotalProduction = $TotalProduction;
        $this->proitemcode     = $_POST['proitemcode'];
    }

    function RemoveOrder($itemcode)
    {
        $this->deleteID = trim($itemcode);
        unset($_SESSION['work_orders'][$this->deleteID]);
        unset($_POST['stockitemcode'], $_POST['qty'], $_POST['sp'], $_POST['units']);
    }

    function IsTankOrStore($ItemCode)
    {
        global $db;
        $ResultIndex     = DB_query(sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($ItemCode)), $db);
        $Stores          = DB_fetch_row($ResultIndex);
        $this->tankStore = ($Stores[0] == 0) ? "S" : "T";
    }

    function tankresults($store, $item)
    {
        global $db;
        $ResultIndex = DB_query(sprintf("SELECT COUNT(*) FROM `ProductionUnit` WHERE `itemcode` = '%s'", _esc($item)), $db);
        $Stores      = DB_fetch_row($ResultIndex);

        if ($Stores[0] > 0) {
            $ResultIndex = DB_query(sprintf("SELECT `balance` FROM `ProductionUnit` WHERE `tankname` = '%s' AND `itemcode` = '%s'", _esc($store), _esc($item)), $db);
            $row         = DB_fetch_row($ResultIndex);
            return sprintf('%f', (int)$row[0]);
        }
        return 'No limit';
    }

    function showtable()
    {
        global $db;

        $runningnettotal = 0;
        $percentTotal    = 0;

        foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
            $line_no  = trim($rowvalue['line_no']);
            $itemcode = $rowvalue['itemcode'];
            $this->IsTankOrStore($itemcode);
            $stocklist = $this->GetStockdetails($itemcode);

            $UOM     = $rowvalue['uom'];
            $package = 1;
            $this->MakeDefaultStoreTankForItem($itemcode, $line_no);
            $location = isset($_POST['location'][$line_no]) ? $_POST['location'][$line_no] : $this->Default[0]['code'];

            $averagestock    = (float)$rowvalue['averagestock'];
            $qty             = (float)$rowvalue['quantity'];
            $baseamount      = (float)($averagestock * $qty);
            $runningnettotal += $baseamount;
            $Production      = $qty * $package;

            $code   = trim($stocklist['itemcode']);
            $ID     = trim($stocklist['itemcode']);
            $arrayi = $_SESSION['work_orders'][$lineno];
            $percent       = (100 * ($Production / $this->TotalProduction));
            $percentTotal += $percent;

            if (mb_stristr($itemcode, 'H2O') == 'H2O') {
                $_SESSION['stockmasterp'][$code]['Balance'] = $qty;
            } else {
                if ($this->tankStore == "T") {
                    $_SESSION['stockmasterp'][$code]['Balance'] = (int)getTankbalance($location, $code);
                } elseif ($this->tankStore == "S") {
                    $_SESSION['stockmasterp'][$code]['Balance'] = (int)getbalance($code, $location, 'loosqty');
                }
            }

            $stkbalance = $_SESSION['stockmasterp'][$code]['Balance'] - $qty;

            $_SESSION['work_orders'][$lineno] = array_replace_recursive($arrayi, array(
                'TotalProduction' => $this->TotalProduction,
                'tankstore'       => $this->tankStore,
                'netamount'       => $baseamount,
                'percent'         => $percent,
                'stkbalance'      => $stkbalance,
            ));

            $this->htmltable .= sprintf(
                '<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><input type="hidden" name="quantity[' . $line_no . ']" value="' . $qty . '" size="5" readonly="readonly" />'
                . '<select name="location[' . $line_no . ']" onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="' . $stkbalance . '" size="8"/></td>'
                . '<td>%s</td>'
                . '<td><input type="text" class="number cell" value="' . $averagestock . '" size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="cents[' . $line_no . ']" value="' . $percent . '" readonly="readonly" size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="Production[' . $line_no . ']" value="' . number_format($Production, 2) . '" readonly="readonly" size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="netamount[' . $line_no . ']" value="' . number_format($baseamount, 2) . '" readonly="readonly" size="8"/></td>'
                . '</tr>',
                $ID, trim($stocklist['barcode']), trim($stocklist['stockname']), $qty,
                trim($stocklist['barcode']), trim($stocklist['stockname']),
                $this->MakeStoreTankForItem($ID, $line_no),
                getGriduom($line_no, $UOM)
            );
        }

        $tankAllowedCapacity = $this->tankresults($_POST['ProdStore'], $_POST['proitemcode']);
        $Reperent            = ($tankAllowedCapacity != 'No limit' && $tankAllowedCapacity > 0)
                               ? (($this->TotalProduction / $tankAllowedCapacity) * 100) : 0;

        $this->htmltable .= '<tfoot><tr><td colspan="6">'
            . '<input type="hidden" name="TotalProductionCost" value="' . $runningnettotal . '"/>'
            . '<input type="hidden" id="proitemcode" size="5" name="proitemcode" value="' . htmlspecialchars($_POST['proitemcode']) . '" readonly="readonly"/>'
            . '<table class="table-bordered">'
            . '<tr><td>Product To Produce</td><td colspan="4"><input type="button" id="searchProstock" value="Search stock"/>'
            . '<input type="text" style="width:70%;" id="Prostockname" name="Prostockname" value="' . htmlspecialchars($_POST['Prostockname']) . '" readonly="readonly"/></td></tr>'
            . '<tr><td>Select Store/Tank</td><td><select name="ProdStore" onchange="ReloadForm(prodform.refresh)">' . $this->ToStoreTankForItem($_POST['proitemcode']) . '</select></td>'
            . '<td>Tank Capacity in ltrs :</td><td><input type="text" class="number" name="TankAllowedCapacity" value="' . $tankAllowedCapacity . '" readonly="readonly" /></td></tr>'
            . '<tr><td colspan="5">You are not allowed to produce more than this limit. Now at ' . number_format($Reperent, 1) . ' %'
            . '<input type="hidden" name="TotalProduction" value="' . $this->TotalProduction . '"/>'
            . '<input type="hidden" name="productionTankStore" value="' . $this->productionTankStore . '"/></td></tr>'
            . '</table></td>'
            . sprintf('<td class="number cell">%s</td><td class="number cell">%s</td><td class="number cell">%s</td></tr></tfoot>',
                $percentTotal, $this->TotalProduction, number_format($runningnettotal, 2));

        $refreshPost = (isset($this->check)
            && mb_stristr($_POST['ProdStore'], $this->check) == $this->check
            && mb_strlen($this->check) > 0
            && mb_strlen($_POST['TotalProduction']) > 0)
            ? "SaveProduction"
            : "refreshcheck";

        $_SESSION['altername_SaveProduction'] = $refreshPost;
        $_SESSION['htmltable']                = $this->htmltable;
    }

    function RepopulateProduction($batchno)
    {
        global $db;

        $_POST['documentno']     = $batchno;
        $_SESSION['work_orders'] = array();

        $ResultIndex = DB_query(sprintf("SELECT `itemcode` FROM `ProductionMaster` WHERE `Batchno` = '%s'", _esc($batchno)), $db);
        $row         = DB_fetch_row($ResultIndex);
        $_POST['proitemcode'] = $row[0];

        $StockArray            = $this->GetStockdetails($_POST['proitemcode']);
        $_POST['Prostockname'] = $StockArray['stockname'];

        $result = DB_query(sprintf("SELECT `Batchno`, `itemcode`, `qty`, `cost` FROM `ProdcutionMasterLine` WHERE `Batchno` = '%s'", _esc($batchno)), $db);
        while ($row = DB_fetch_array($result)) {
            $StockList = $this->GetStockdetails($row['itemcode']);
            if (mb_strwidth($StockList['itemcode']) > 0) {
                $id = trim($StockList['itemcode']);
                $_SESSION['work_orders'][$id] = array(
                    'line_no'      => $id,
                    'itemcode'     => $id,
                    'barcode'      => $StockList['barcode'],
                    'stockname'    => $StockList['stockname'],
                    'uom'          => '1',
                    'averagestock' => $StockList['averagestock'],
                    'quantity'     => $row['qty'],
                );
            }
        }

        $this->GetSum();
    }
}

// ---------------------------------------------------------------------------
// AssembleAdjust — identical structure to Assemble; showtable() footer
// includes an editable TotalProduction input and originalproduction hidden.
// ---------------------------------------------------------------------------

class AssembleAdjust extends Assemble
{
    // Inherits all methods from Assemble.
    // Only showtable() footer differs from Assemble — overridden below.

    function showtable()
    {
        global $db;

        $runningnettotal = 0;
        $percentTotal    = 0;

        foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
            $line_no  = trim($rowvalue['line_no']);
            $itemcode = $rowvalue['itemcode'];
            $this->IsTankOrStore($itemcode);
            $stocklist = $this->GetStockdetails($itemcode);

            $UOM     = $rowvalue['uom'];
            $package = 1;
            $this->MakeDefaultStoreTankForItem($itemcode, $line_no);
            $location = isset($_POST['location'][$line_no]) ? $_POST['location'][$line_no] : $this->Default[0]['code'];

            $averagestock    = (float)$rowvalue['averagestock'];
            $qty             = (float)$rowvalue['quantity'];
            $baseamount      = (float)($averagestock * $qty);
            $runningnettotal += $baseamount;
            $Production      = $qty * $package;

            $code   = trim($stocklist['itemcode']);
            $ID     = trim($stocklist['itemcode']);
            $arrayi = $_SESSION['work_orders'][$lineno];
            $percent       = (100 * ($Production / $this->TotalProduction));
            $percentTotal += $percent;

            if (mb_stristr($itemcode, 'H2O') == 'H2O') {
                $_SESSION['stockmasterp'][$code]['Balance'] = $qty;
            } else {
                if ($this->tankStore == "T") {
                    $_SESSION['stockmasterp'][$code]['Balance'] = (int)getTankbalance($location, $code);
                } elseif ($this->tankStore == "S") {
                    $_SESSION['stockmasterp'][$code]['Balance'] = (int)getbalance($code, $location, 'loosqty');
                }
            }

            $stkbalance = $_SESSION['stockmasterp'][$code]['Balance'] - $qty;

            $_SESSION['work_orders'][$lineno] = array_replace_recursive($arrayi, array(
                'TotalProduction' => $this->TotalProduction,
                'tankstore'       => $this->tankStore,
                'netamount'       => $baseamount,
                'percent'         => $percent,
                'stkbalance'      => $stkbalance,
            ));

            $this->htmltable .= sprintf(
                '<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><input type="hidden" name="quantity[' . $line_no . ']" value="' . $qty . '" size="5" readonly="readonly" />'
                . '<select name="location[' . $line_no . ']" onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="' . $stkbalance . '" size="8"/></td>'
                . '<td>%s</td>'
                . '<td><input type="text" class="number cell" value="' . $averagestock . '" size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="cents[' . $line_no . ']" value="' . $percent . '" readonly="readonly" size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="Production[' . $line_no . ']" value="' . number_format($Production, 2) . '" readonly="readonly" size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="netamount[' . $line_no . ']" value="' . number_format($baseamount, 2) . '" readonly="readonly" size="8"/></td>'
                . '</tr>',
                $ID, trim($stocklist['barcode']), trim($stocklist['stockname']), $qty,
                trim($stocklist['barcode']), trim($stocklist['stockname']),
                $this->MakeStoreTankForItem($ID, $line_no),
                getGriduom($line_no, $UOM)
            );
        }

        $tankAllowedCapacity = $this->tankresults($_POST['ProdStore'], $_POST['proitemcode']);
        $Reperent            = ($tankAllowedCapacity != 'No limit' && $tankAllowedCapacity > 0)
                               ? (($this->TotalProduction / $tankAllowedCapacity) * 100) : 0;

        // AssembleAdjust footer: editable TotalProduction + originalproduction hidden
        $this->htmltable .= '<tfoot><tr><td colspan="6">'
            . '<input type="hidden" name="TotalProductionCost" value="' . $runningnettotal . '"/>'
            . '<input type="hidden" id="proitemcode" size="5" name="proitemcode" value="' . htmlspecialchars($_POST['proitemcode']) . '" readonly="readonly"/>'
            . '<table class="table-bordered">'
            . '<tr><td>Product To Produce</td><td colspan="4">'
            . '<input type="text" style="width:70%;" id="Prostockname" name="Prostockname" value="' . htmlspecialchars($_POST['Prostockname']) . '" readonly="readonly"/></td></tr>'
            . '<tr><td>Select Store/Tank</td><td><select name="ProdStore" onchange="ReloadForm(prodform.refresh)">' . $this->ToStoreTankForItem($_POST['proitemcode']) . '</select></td>'
            . '<td>Tank Capacity in ltrs :</td><td><input type="text" class="number" name="TankAllowedCapacity" value="' . $tankAllowedCapacity . '" readonly="readonly" /></td></tr>'
            . '<tr><td colspan="5">You are not allowed to produce more than this limit. Now at ' . number_format($Reperent, 1) . ' %</td></tr>'
            . '<tr><td colspan="4">Enter Production :</td><td>'
            . '<input type="text" name="TotalProduction" value="' . htmlspecialchars($_POST['TotalProduction']) . '"/>'
            . '<input type="hidden" name="productionTankStore" value="' . $this->productionTankStore . '"/>'
            . '<input type="hidden" name="originalproduction" value="' . $this->TotalProduction . '"/></td></tr>'
            . '</table></td>'
            . sprintf('<td class="number cell">%s</td><td class="number cell">%s</td><td class="number cell">%s</td></tr></tfoot>',
                $percentTotal, htmlspecialchars($_POST['TotalProduction']), number_format($runningnettotal, 2));

        $refreshPost = (isset($this->check)
            && mb_stristr($_POST['ProdStore'], $this->check) == $this->check
            && mb_strlen($this->check) > 0
            && mb_strlen($_POST['TotalProduction']) > 0)
            ? "SaveProduction"
            : "refreshcheck";

        $_SESSION['altername_SaveProduction'] = $refreshPost;
        $_SESSION['htmltable']                = $this->htmltable;
    }
}

// ---------------------------------------------------------------------------

class Production
{
    var $customerposting;
    var $VATinclusive;
    var $IsTaxed;
    var $htmltable;
    var $stocklist = array();

    function GetStockdetails($itemcode)
    {
        global $db;

        $ResultIndex = DB_query("SELECT
                sm.`barcode`,
                sm.`itemcode`,
                sm.`descrip`    AS stockname,
                u.`descrip`     AS si,
                sm.`averagestock`,
                sm.`partperunit`,
                vc.`vat`
            FROM `stockmaster` sm
            LEFT JOIN `unit` u               ON sm.`units`        = u.`code`
            LEFT JOIN `inventorypostinggroup` ipg ON sm.`postinggroup` = ipg.`code`
            LEFT JOIN `vatcategory` vc       ON ipg.`vatcategory` = vc.`vatc`
            WHERE sm.`itemcode` = '" . _esc($itemcode) . "'", $db);

        $stocklist = DB_fetch_row($ResultIndex);
        $this->stocklist['barcode']      = $stocklist[0];
        $this->stocklist['itemcode']     = $stocklist[1];
        $this->stocklist['stockname']    = $stocklist[2];
        $this->stocklist['si']           = $stocklist[3];
        $this->stocklist['averagestock'] = $stocklist[4];
        $this->stocklist['partperunit']  = $stocklist[5];
        $this->stocklist['vat']          = $stocklist[6];
        return $this->stocklist;
    }

    function Getitems($itemcode, $qty)
    {
        $StockList = $this->GetStockdetails($itemcode);
        $line_No   = $this->LineNo();

        $_SESSION['labreagents'][$line_No] = array(
            'line_no'     => $line_No,
            'itemcode'    => $StockList['itemcode'],
            'barcode'     => $StockList['barcode'],
            'stockname'   => $StockList['stockname'],
            'partperunit' => $StockList['partperunit'],
            'quantity'    => $qty,
        );

        $this->showtable();
    }

    function LineNo()
    {
        return $_SESSION['labreagents_index'];
    }

    function neworder()
    {
        $_SESSION['labreagents']       = array();
        $_SESSION['labreagents_index'] = 0;
    }

    function Nextorder()
    {
        $_SESSION['labreagents_index']++;
    }

    function showtable()
    {
        global $db;

        foreach ($_SESSION['labreagents'] as $lineno => $rowvalue) {
            $line_no   = $rowvalue['line_no'];
            $itemcode  = $rowvalue['itemcode'];
            $stocklist = $this->GetStockdetails($itemcode);
            $qty       = isset($_POST['quantity'][$line_no]) ? $_POST['quantity'][$line_no] : 0;

            $this->htmltable .= sprintf(
                '<tr>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><input type="text" class="number" name="quantity[' . $line_no . ']" value="' . $qty . '" size="5"/>'
                . '<input type="hidden" name="itemcode[' . $line_no . ']" value="' . $stocklist['itemcode'] . '"/></td>'
                . '<td>%s</td>'
                . '</tr>',
                trim($stocklist['barcode']),
                trim($stocklist['stockname']),
                CreateUnits($itemcode, $line_no),
                createstores($line_no)
            );
        }
        echo $this->htmltable;
    }
}

// ---------------------------------------------------------------------------

class ProcessInformationAlter
{
    var $post;
    var $session;
    var $workorderclass;
    var $error = 0;

    function get($post, $session, $workorderclass)
    {
        $this->post           = $post;
        $this->session        = $session;
        $this->workorderclass = $workorderclass;
    }

    function anythingwrong()
    {
        global $db;

        $return      = 'YES';
        $ResultIndex = DB_query("SELECT COUNT(*) FROM `stockmaster` WHERE `itemcode` = '" . _esc(trim($this->post['proitemcode'])) . "'", $db);
        $rows        = DB_fetch_row($ResultIndex);

        if ($rows[0] == 1) {
            if (mb_strstr($this->post['TankAllowedCapacity'], 'No limit') == false) {
                if ($this->post['TankAllowedCapacity'] < $this->post['TotalProduction']) {
                    $this->error++;
                }
            }
        } else {
            $this->error++;
        }

        if ($this->error > 0) {
            echo '<script type="text/javascript">function Checkproductionintegrity(){ ';
            echo " SmartDialog.warning('Please check stock balances or tank capacity or production product code if its blank', 'Warning'); ";
            echo " }  Checkproductionintegrity();</script>";
            $return = 'YES';
        } else {
            $this->savestockproduction();
            $return = 'NO';
        }

        return $return;
    }

    function savestockproduction()
    {
        global $db;

        $journal      = GetNextTransNo(0, $db);
        $period       = GetPeriod($this->post['date'], $db, true);
        $formatedDate = FormatDateForSQL($this->post['date']);

        $DiffProduction  = ((float)$this->post['TotalProduction'] - (float)$this->post['originalproduction']);
        $averagecost     = (float)$this->post['TotalProductionCost'] / (float)$this->post['TotalProduction'];

        $documentno      = _esc($this->post['documentno']);
        $proitemcode     = _esc($this->post['proitemcode']);
        $totalProduction = (float)$this->post['TotalProduction'];
        $userID          = _esc(stripslashes($_SESSION['UserID']));
        $ProdStore       = _esc($this->post['ProdStore']);
        $Prostockname    = _esc($this->post['Prostockname']);

        $sql[] = sprintf(
            "UPDATE `ProductionMaster` SET `modified` = %f, `userid` = '%s'
             WHERE `Batchno` = '%s' AND `itemcode` = '%s'",
            $totalProduction, $userID, $documentno, $proitemcode
        );

        if ($this->post['productionTankStore'] == "T") {
            $sql[] = sprintf(
                "INSERT INTO `tanktrans` (`tankname`, `units`, `uom`, `date`, `batchno`, `doctype`, `itemcode`)
                 VALUES ('%s', %f, '%s', '%s', '%s', 28, '%s')",
                $ProdStore, $DiffProduction, '', $formatedDate, $documentno, $proitemcode
            );

        } elseif ($this->post['productionTankStore'] == "S") {
            $sql[] = sprintf(
                "INSERT INTO `stockledger`
                    (`fulqty`, `date`, `stname`, `doctyp`, `itemcode`, `invref`,
                     `loosqty`, `price`, `store`, `journal`, `period`, `PartPerUnit`)
                 VALUES (0, '%s', '%s', '28', '%s', '%s', %f, 0, '%s', '%s', '%s', 1)",
                $formatedDate, $Prostockname, $proitemcode, $documentno,
                $DiffProduction, $ProdStore, _esc($journal), _esc($period)
            );
        }

        if ($averagecost > 0) {
            $sql[] = sprintf(
                "UPDATE `stockmaster` SET `averagestock` = %f WHERE `itemcode` = '%s'",
                $averagecost, $proitemcode
            );

            $sql[] = sprintf(
                "INSERT INTO `StockRegister` (`itemcode`, `StockIn`, `cost`, `journal`, `GRN`, `StockOut`)
                 VALUES ('%s', %f, %f, '%s', '%s', 0)",
                $proitemcode, $DiffProduction, $averagecost, _esc($journal), $documentno
            );
        }

        foreach ($sql as $value) {
            DB_query($value, $db);
        }

        echo '<script type="text/javascript">function Checkproductionintegrity(){ ';
        echo sprintf(" SmartDialog.success('Production %s has been altered. Now prepare Lab Test Report'); ", _esc($this->post['documentno']));
        echo " }  Checkproductionintegrity();</script>";
    }
}

// ---------------------------------------------------------------------------
// CalculateVCF — pure PHP maths, no SQL; unchanged.
// ---------------------------------------------------------------------------

class CalculateVCF
{
    var $alpha; // Coefficient of thermal expansion for bitumen
    var $Tb;    // Base temperature

    function __construct()
    {
        $this->alpha = 0.0008;
        $this->Tb    = 15;
    }

    function VCF($T1)
    {
        if ($T1 > 15) {
            return (1 + $this->alpha * ($this->Tb - $T1)) / (1 + $this->alpha * ($T1 - $this->Tb));
        }
        return 0;
    }
}
