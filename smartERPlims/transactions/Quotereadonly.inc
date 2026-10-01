<?php

echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

$sqldebtors=DB_query("SELECT itemcode ,creditlimit,customer
      ,phone ,email ,city ,country,curr_cod,
      customerposting,salesman,VATinclusive
       FROM debtors join postinggroups on code=customerposting
       where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10] ?? 0;

if(($_SESSION['ManualNumber']==0)){
$_POST['documentno'] = GetNextTransNo(54,$db);
}

$_SESSION['CompleteDocument']=$_POST['documentno'];

$transstart = DB_Txn_Begin($db);
$PeriodNo = GetPeriod($_POST['date'],$db,true);
$DATE = FormatDateForSQL($_POST['date']);

$sql=array();

 $selectedImages = $_POST['selectedImages'];
 $decodedString = decodeHtmlEntities($selectedImages);
 $imagesArray = json_decode($decodedString);
 $imagesArray2 = json_encode($imagesArray);
 
$sql[]="INSERT INTO SalesHeader
           (documenttype
           ,documentno
           ,docdate
           ,customercode
           ,customername
           ,externaldocumentno
           ,locationcode
           ,postinggroup
           ,currencycode
           ,salespersoncode
           ,status
           ,userid
           ,period
           ,vatinclusive
           ,paymentterms 
           ,picture
           ,QtyDiscount)
     VALUES
           ('54'
           ,'".$_POST['documentno']."'
           ,'".$DATE."'
           ,'".$_POST['CustomerID']."'
           ,'".$_POST['CustomerName']."'
           ,'".$_POST['Bank_Code2']."'
           ,'".$_POST['Bank_Code']."'
           ,'".$customerposting ."'
           ,'".$_POST['currencycode']."'
           ,'".$_POST['salespersoncode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."'
           ,'".$_POST['terms']."' 
           ,'".$imagesArray2."'
          ,'".(int)$_POST['DiscountPercent']."' "
        . ")" ;


foreach ($_SESSION['sales_orders'] as $lineno => $rows) {
     if($rows['grossamount']>0){

        $UnitCode    = trim($rows['uom']);
        $stockcode   = trim($rows['itemcode']);
        $Partperunit = (float) $rows['partsperunit'];
        $Inpacks     = $_SESSION['units'][$UnitCode]['descrip'];
        $qty         = (float) $rows['quantity'];
        $stockname   = $rows['stockname'];
        $vatrate     = $rows['vatrate'];
        $salesprice   = $rows['salesprice'] ;
        $grossamount  = $rows['grossamount'] ;
        $vatamount    = $rows['vatamount'];
        $tat          = $rows['tat'];
        $lineDiscountPercent = (float)($rows['discountpercent'] ?? 0);
    
    // Try to insert with discount column if it exists
    // If the column doesn't exist, an error will occur and the INSERT will fail
    $sql[] =  sprintf("INSERT INTO SalesLine
           (documenttype
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
           ,Partperunit
           ,TAT
           ,LineDiscountPercent)
     VALUES
           ('%s','%s','%s','%s','%s','%s','%s',%f,%f,%f ,'%f','%s' ,%f ,%f,%f,'%s','%s',%s,%f)"
            ,"54"
           ,$DATE
           ,$_POST['documentno']
           ,$stockcode
           ,$stockname
           ,$Inpacks
           ,$qty
           ,$salesprice
           ,$vatamount
           ,$grossamount
           ,$vatrate
           ,$VATinclusive
           ,0
           ,0
           ,0
           ,0
           ,$Partperunit
           ,$tat ? (int)$tat : 'NULL'
           ,$lineDiscountPercent);
        }
}

 
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }

    $_SESSION['savedsuccessfuly']=false;

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save sales Quote :'.$_POST['documentno'],'warn');

    } else {
        DB_Txn_Commit($db);
        prnMsg('Sales Quote :'.$_POST['documentno'].' has been created','info');
        $_SESSION['savedsuccessfuly'] = true;
     }
    


if($_SESSION['savedsuccessfuly']==true){
    
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
      ,BankAccounts.bankName
      ,paymentterms
      ,QtyDiscount
      ,currencycode
      ,salespersoncode
      ,printed
      ,released
      ,status
      ,userid 
      from SalesHeader 
      join BankAccounts on BankAccounts.accountcode=SalesHeader.locationcode 
      where documenttype='54' and  documentno='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
    $row=DB_fetch_row($Result);
        

    echo '<table class="table"><tr><th>Header</th><th>Description</th></tr>';
    echo sprintf("<tr><td>Quote No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Customer ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Customer Name</td><td>%s</td></tr>",$row[6]);
    echo sprintf("<tr><td>Bank Name</td><td>%s</td></tr>",$row[10]);
    echo sprintf("<tr><td>Credit Terms</td><td>%s</td></tr>",$row[11]);
    echo sprintf("<tr><td>Discount</td><td>%s</td></tr>",$row[12]);
    echo sprintf("<tr><td>FOOTER Payment</td><td>%s</td></tr>",html_entity_decode($_POST['paymentterms']));
    
    $headerDiscountPercent = $row[12];
    
    $SQL="select 
            documenttype
           ,docdate
           ,documentno
           ,code
           ,description
           ,unitofmeasure
           ,Quantity
           ,UnitPrice
           ,totalchargedcontainers
           ,vatamount
           ,invoiceamount
           ,Partperunit
           ,TAT
           ,LineDiscountPercent
            from SalesLine where documenttype=54 and documentno='".$_POST['documentno']."'";
    echo '<tr><td colspan="2">';
    
    echo '<table  class="table table-bordered"><thead><tr>'
             . '<th>Product<br /> Code</th>'
             . '<th>Product Name</th>'
             . '<th>Unit Of<br /> Measure</th>'
             . '<th class="number">Quantity</th>'
             . '<th class="number">Sales Price</th>'
            . '<th class="number">Discount</th>'
             . '<th class="number">VAT Amount</th>'
             . '<th class="number">Gross Amount</th>'
             . '<th class="number">TAT (days)</th>'
             . '</tr></thead>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
        
        $lineDiscountPercent = (float)($myrow['LineDiscountPercent'] ?? 0);
        $baseNetAmount = ($myrow['invoiceamount']-$myrow['vatamount']);
           
        if($lineDiscountPercent > 0){
            $lineDiscountAmount = $baseNetAmount * ($lineDiscountPercent / 100);
            $myrow['netamt'] = $baseNetAmount - $lineDiscountAmount;
            $myrow['line_discount_amount'] = $lineDiscountAmount;
        } else {
            $myrow['line_discount_amount'] = 0;
        }
           
         echo sprintf('<tr>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'],
                 $myrow['unitofmeasure'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['line_discount_amount'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2),
                 $myrow['TAT'] ?? '');
      
         $VATAMOUNT += $myrow['vatamount'];
         $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }
    
    
     echo sprintf('<tfoot><tr><td colspan="5">%s</td><td class="number">%s</td><td class="number">%s</td></tr></tfoot>',
             'Sales Quote Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));
    echo '</table></td></tr>';
    echo '<tr><td><label><input type="checkbox" name="sendEmail" value="1" /> '._('Send Email to Customer').'</label></td></tr>';
    echo '<tr><td><input type="submit" name="confirm" value="'._('Confirm Sales Quote').'"/></td>';
    echo '<td><input type="submit" name="Delete" value="'._('Cancel/Delete Quote').'"/></td></tr>';
    echo '</table>';
    
}

$_SESSION['Grossamounttotal']=0;
$_SESSION['CompleteDocument']=$_POST['documentno'];
    
echo '</div></form>';  

?>
