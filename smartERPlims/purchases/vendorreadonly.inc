<?php
    
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
 
$sqldebtors=DB_query("SELECT itemcode,customer,phone,email,city,country,curr_cod,supplierposting,VATinclusive
FROM creditors join arpostinggroups on code=supplierposting where itemcode='".$_POST['VendorID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[7]; $VATinclusive = $debtorsrow[8];

if(($_SESSION['ManualNumber']==0)){
  $_POST['documentno'] = GetNextTransNo(18,$db);
 }
$PeriodNo = GetPeriod($_POST['date'],$db,true);

$sql=array();

$sql[]="INSERT INTO PurchaseHeader
           (documenttype
           ,documentno
           ,docdate
           ".(Is_date($_POST['Salesoderdate'])?',oderdate':'')."
           ".(Is_date($_POST['datedue'])?',duedate':'')."
           ,vendorcode
           ,vendorname
           ,yourreference
           ,locationcode
           ,postinggroup
           ,currencycode
           ,status
           ,userid
           ,period
           ,vatinclusive
           ,Dimension_1
           ,Dimension_2
           ,freight)
     VALUES
           ('18'
           ,'".$_POST['documentno']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
           ,'".$_POST['VendorID']."'
           ,'".$_POST['VendorName']."'
           ,'".$_POST['reference']."'
           ,'".$_POST['locationcode']."'
           ,'".$customerposting ."'
           ,'".$_POST['currencycode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."'
           ,'".$_POST['DimensionOne']."'
           ,'".$_POST['DimensionTwo']."'
           ,'".$_POST['FREIGHT']."')" ;



foreach ($_SESSION['Purchase_orders'] as $ids => $rows ) {
 if($rows['grossamount']>0){
        
        $stockcode =$rows['itemcode'];
        
        $SqL="SELECT 
        stockmaster.barcode,
        stockmaster.itemcode,
        stockmaster.descrip as stockname,
        stockmaster.container,
        stockmaster.averagestock,
        stockmaster.partperunit,
        vatcategory.vat
  FROM stockmaster 
  left join unit on stockmaster.units=unit.code
  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
 where stockmaster.inactive=0 and itemcode='".$stockcode ."'";
    
   $ResultIndex = DB_query($SqL, $db);
   $stkmaster = DB_fetch_row($ResultIndex);
   $stockname = $stkmaster[2];
   $drumCode = $stkmaster[3];
   $vatrate =(float) $rows['vatrate'];    
   $qty =(float) $rows['quantity'] ;
   $ReceivedQuantity =(float) $rows['ReceivedQuantity'];
   $UnitCode  = $rows['uom'];
   $uomreceived = $rows['uomreceived'];
   $package = (float) $rows['partsperunit'];
   $packzizegrn  = (float) $rows['packzizegrn'];
   $salesprice = (float) $rows['price'];
   $discountAmount =(float) $rows['discount'];
   $grossamount =(float) $rows['grossamount'] ;
   $vatamount  =(float) $rows['vatamount'];     
   $Inpacks = $_SESSION['units'][$UnitCode]['descrip'];
   $InpacksReceived = $_SESSION['units'][$uomreceived]['descrip'];
   
               
    //alter table PurchaseLine add discount float
    $sql[] =  sprintf("INSERT INTO PurchaseLine
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
           ,PartPerUnit
           ,discount
           ,containercode
           ,QuantityToReceive
           ,PriceToReceive
           ,unitofreceivedIn
           ,packzizegrn)
     VALUES
           ('%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,%f
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           , %f
           ,'%s'
           ,%f
           ,%f
           ,'%s'
           ,%f)"
           ,"18"
           ,FormatDateForSQL($_POST['date'])
           ,$_POST['documentno']
           ,$stockcode
           ,$stockname
           ,$Inpacks
           ,$qty
           ,$salesprice
           ,$vatamount
           ,$grossamount
           ,(isset($vatrate)?$vatrate:0)
           ,(isset($VATinclusive)?$VATinclusive:0)
           ,$package
           ,$discountAmount
           ,$drumCode
           ,$ReceivedQuantity
           ,(($salesprice*$qty)/$ReceivedQuantity)
           ,$InpacksReceived
           ,$packzizegrn );
        }
 }
 
$transstart = DB_Txn_Begin($db);
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save Purchase order :'.$_POST['documentno'],'warn');
    }else{
        DB_Txn_Commit($db);
        prnMsg('new Purchase order :'.$_POST['documentno'].' has been created','inf');
    }
    
    $SQL="select 
       documentno
      ,docdate
      ,oderdate
      ,duedate
      ,postingdate
      ,vendorcode
      ,vendorname
      ,yourreference
      ,externaldocumentno
      ,locationcode
      ,paymentterms
      ,postinggroup
      ,currencycode
      ,printed
      ,released
      ,status
      ,userid
      ,freight 
      from PurchaseHeader  where documenttype='18' and documentno='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table-bordered"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Purchase Order No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Purchase Order Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Purchase Order date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Purchase Order Due Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Supplier ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Supplier Name</td><td>%s</td></tr>",$row[6]);
    echo sprintf("<tr><td>Freight Cost</td><td>%s</td></tr>",number_format($row[17],2) );
    echo '<tr><td colspan="2">';
    
    echo '<table class="table-bordered table-condensed table-responsive-small"><tr>'
            . '<td>Stock Code</td>'
            . '<td>Name</td>'
            . '<td>Unit Of Measure</td>'
            . '<td>Parts</td>'
            . '<td>Quantity Purchased</td>'
            . '<td>Quantity Received</td>'
            . '<td>Price</td>'
            . '<td>Discount</td>'
            . '<td>vat</td>'
            . '<td>Gross</td>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $SQL="select 
           documenttype
           ,docdate
           ,documentno
           ,code
           ,description
           ,unitofmeasure
           ,Quantity
           ,UnitPrice
           ,discount
           ,vatamount
           ,invoiceamount
           ,PartPerUnit 
           ,QuantityToReceive
           from PurchaseLine 
           where documenttype=18 and documentno='".$_POST['documentno']."'";
   
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'], 
                 $myrow['unitofmeasure'],
                 $myrow['PartPerUnit'],
                 $myrow['Quantity'], 
                 $myrow['QuantityToReceive'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['discount'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
         $VATAMOUNT += $myrow['vatamount'];
         $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }     
    
    $_SESSION['Grossamounttotal']=0;
    $_SESSION['CompleteDocument']=$_POST['documentno'];
  
    echo sprintf('<tfoot><tr><td colspan="8">%s</td><td>%s</td><td>%s</td></tr></tfoot>','Purchase Order Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));
    echo '</table></td></tr>';
    echo '<tr><td><input type="submit" name="confirm" value="'._('Confirm Purchase Order').'"/></td>'
            . '<td><input type="submit" name="Delete" value="'._('Discard Order').'"/></td></tr>';
    echo '</table>';
    echo '</div></form>';  
?>
