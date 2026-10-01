<?php
  
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

$sqldebtors=DB_query("SELECT itemcode ,creditlimit,customer
      ,phone ,email ,city ,country,curr_cod,customerposting,salesman,VATinclusive
       FROM debtors join postinggroups on code=customerposting
       where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10] ?? 0;

$transstart = DB_Txn_Begin($db);
$PeriodNo = GetPeriod($_POST['date'],$db,true);
if(($_SESSION['ManualNumber']==0)){
  $_POST['documentno'] = GetNextTransNo(1,$db);
}

$_SESSION['CompleteDocument']=$_POST['documentno'];
$sql=array();

 $selectedImages = $_POST['selectedImages'];
 $decodedString = decodeHtmlEntities($selectedImages);
 $imagesArray = json_decode($decodedString);
 $imagesArray2 = json_encode($imagesArray);
 
 
// helper for escaping (mysqli assumed)
function esc($conn, $v) {
    return mysqli_real_escape_string($conn, (string)($v ?? ''));
}



// normalize inputs
$docNo        = esc($db, $_POST['documentno']);
$docDate      = FormatDateForSQL($_POST['date']);

$orderDateSQL = Is_date($_POST['date']) ? "'" . FormatDateForSQL($_POST['date']) . "'" : "NULL";
$dueDateSQL   = Is_date($_POST['date']) ? "'" . FormatDateForSQL($_POST['date']) . "'" : "NULL";

$customerID   = esc($db, $_POST['CustomerID']);
$customerName = esc($db, $_POST['CustomerName']);
$reference    = esc($db, $_POST['reference']);
$coaDocNo     = esc($db, $_POST['coa_documentno']);

$postingGroup = esc($db, $customerposting);
$currency     = esc($db, $_POST['currencycode']);
$salesperson  = esc($db, $_POST['salespersoncode']);

$userID       = esc($db, $_SESSION['UserID']);
$period       = esc($db, $PeriodNo);
$vatInclusive = esc($db, $VATinclusive);

$location     = esc($db, $_POST['Bank_Code']);
$shipping     = esc($db, $_POST['FREIGHT']);
$packages     = esc($db, $_POST['packagescharge']);
$picture      = esc($db, $imagesArray2);

$discount     = (float)($_POST['DiscountPercent'] ?? 0);

// build SQL
$sql[] = sprintf(
    "INSERT INTO SalesHeader (
        documenttype, documentno, docdate, oderdate, duedate,
        customercode, customername, yourreference, coa_documentno,
        postinggroup, currencycode, salespersoncode, status,
        userid, period, vatinclusive, locationcode, shipping,
        packagescharge, picture, QtyDiscount
    ) VALUES (
        1,
        '%s',
        '%s',
        %s,
        %s,
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        1,
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        %f
    )",
    $docNo,
    $docDate,
    $orderDateSQL,
    $dueDateSQL,
    $customerID,
    $customerName,
    $reference,
    $coaDocNo,
    $postingGroup,
    $currency,
    $salesperson,
    $userID,
    $period,
    $vatInclusive,
    $location,
    0,
    0,
    $picture,
    $discount
);
 function sqlVal($db, $v) {
    if ($v === null || $v === '') {
        return "NULL";
    }

    if (is_numeric($v)) {
        return $v;
    }

    return "'" . mysqli_real_escape_string($db, $v) . "'";
}

foreach ($_SESSION['sales_orders'] as $line_No => $rows) {

    // Persist every line with a quantity, even at zero amount: under 80/20
    // the free lines (bundle or tests) are part of the order.
    // (Invoices keep their own zero-amount skip.)
    if (trim($rows['itemcode'] ?? '') === '' || (float)($rows['quantity'] ?? 0) <= 0) {
        continue;
    }

        $UnitCode    = trim($rows['uom']);
        $stockcode   = trim($rows['itemcode']);
        $qty         = (float) $rows['quantity'];

        $stockSql = "SELECT 
                        sm.barcode,
                        sm.itemcode,
                        sm.descrip AS stockname,
                        sm.partperunit,
                        vc.vat,
                        sm.isstock_3
                     FROM stockmaster sm
                     LEFT JOIN inventorypostinggroup ipg ON sm.postinggroup = ipg.code
                     LEFT JOIN vatcategory vc ON ipg.vatcategory = vc.vatc
                     WHERE sm.itemcode = '" . mysqli_real_escape_string($db, $stockcode) . "'";

        $ResultIndex = DB_query($stockSql, $db);
        $stkmaster   = DB_fetch_row($ResultIndex);

        if (!$stkmaster) {
            continue;
        }

        $stockname   = $stkmaster[2];
        $vatrate     = (float)$stkmaster[4];
        $gotoinvoice = ($stkmaster[5] == 1) ? 1 : 0;
        $PartPerUnit = $stkmaster[3];

        $containerCode = trim($rows['containerCode'] ?? '');
        $emptycost     = 0.0;
        $totalemptycost= 0.0;
        $emptycode     = '';

        $salesprice          = (float)($rows['salesprice'] ?? 0);
        $grossamount         = (float)($rows['grossamount'] ?? 0);
        $vatamount           = (float)($rows['vatamount'] ?? 0);
        $tat                 = $rows['tat'] ?? null;
        $lineDiscountPercent = (float)($rows['discountpercent'] ?? 0);
        $PriceInPricelist    = (float)($rows['PriceInPricelist'] ?? 0);
        $sampleid            = $rows['sampleid'] ?? null;

        $columns = [
            'documenttype',
            'docdate',
            'documentno',
            'code',
            'description',
            'sampleID',
            'Quantity',
            'UnitPrice',
            'vatamount',
            'invoiceamount',
            'vatrate',
            'inclusive',
            'containerprice',
            'containersunits',
            'totalchargedcontainers',
            'containercode',
            'PartPerUnit',
            'TAT',
            'LineDiscountPercent',
            'PriceInPricelist'
        ];

        $values = [
            1,
            sqlVal($db, FormatDateForSQL($_POST['date'])),
            sqlVal($db, $_POST['documentno']),
            sqlVal($db, $stockcode),
            sqlVal($db, $stockname),     // ✅ FIXED (comma-safe)
            sqlVal($db, $rows['sampleid'] ?? ''),
            sqlVal($db, $qty),
            sqlVal($db, $salesprice),
            sqlVal($db, $vatamount),
            sqlVal($db, $grossamount),
            sqlVal($db, $vatrate),
            sqlVal($db, $VATinclusive ?? 0),
            sqlVal($db, $emptycost),
            0,
            sqlVal($db, $totalemptycost),
            sqlVal($db, $emptycode),
            sqlVal($db, $PartPerUnit),
            ($tat !== null ? (int)$tat : "NULL"),
            sqlVal($db, $lineDiscountPercent),
            sqlVal($db, $PriceInPricelist)
        ];

        if ($gotoinvoice === 1) {
            $columns[] = 'Qunatity_delivered';
            $values[]  = sqlVal($db, $qty);
        }

        $insertSql = "INSERT INTO SalesLine (" . implode(", ", $columns) . ")
                      VALUES (" . implode(", ", $values) . ")";

        $sql[] = $insertSql;
}
    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save sales order :'.$_POST['documentno'],'warn');

    } else {
        DB_Txn_Commit($db);
        prnMsg('Sales order :'.$_POST['documentno'].' has been created','info');
     }
 
    
    $SQL="select 
       documentno
      ,docdate
      ,oderdate
      ,duedate
      ,postingdate
      ,customercode
      ,customername
      ,yourreference
      ,externaldocumentno
      ,locationcode
      ,paymentterms
      ,postinggroup
      ,currencycode
      ,salespersoncode
      ,printed
      ,released
      ,status
      ,userid
      ,shipping
      ,packagescharge
      from SalesHeader where documenttype='1' and  documentno='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<div class="confirmso" id="conso" onmouseover="dragElement(this);">'
       . '<table class="table-bordered table-condensed table-responsive-small">';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Order No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Customer ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Customer Name</td><td>%s</td></tr>",$row[6]);
    echo sprintf("<tr><td>Cost of transport</td><td>%s</td></tr>",$row[18]);
    echo sprintf("<tr><td>Cost of packaging</td><td>%s</td></tr>",$row[19]);
    $shipping =(float) $row[18];
    $packaging =(float) $row[19];
    
            $SQL="select 
                documenttype
               ,docdate
               ,documentno
               ,code
               ,description
               ,unitofmeasure
               ,Quantity
               ,UnitPrice
               ,vatamount
               ,invoiceamount
               ,vatrate
               ,inclusive
               ,containerprice
               ,containersunits
               ,totalchargedcontainers
               ,containercode
               ,PartPerUnit
               ,PriceInPricelist
               ,shipping
           from SalesLine where documenttype=1 and documentno='".$_POST['documentno']."'";
    echo '<tr><td colspan="2">';
    
    echo '<table class="table-bordered table-condensed table-responsive-small"><tr>'
            . '<td>Code</td>'
            . '<td>Description</td>'
            . '<td>Unit Of Measure</td>'
            . '<td>Quantity</td>'
            . '<td>Sales Price</td>'
            . '<td>Charge on Empties</td>'
            . '<td>VAT Amount</td>'
            . '<td>Gross Amount</td>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '</tr>',
                 $myrow['code'],
                 $myrow['description'].'(1 X'.$myrow['PartPerUnit'].')',
                 $myrow['unitofmeasure'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['totalchargedcontainers'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
                $VATAMOUNT += $myrow['vatamount'];
                $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }
    
    
    echo sprintf('<tr><td colspan="6" class="number">%s</td><td class="number">%s</td><td class="number">%s</td></tr>','Sales Order Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT+$shipping+$packaging,2));
    echo '</table></td></tr>';
    echo '<tr><td><input type="submit" name="confirmOrder" value="'._('Confirm Sales Order And Print').'"/></td>'
            . '<td><input type="submit" name="submit" value="'._('Delete Order').'"/></td>'
            . '</tr></table></div>';
 
    $_SESSION['Grossamounttotal']=0;
 
 echo '</div></form>';   
?>
