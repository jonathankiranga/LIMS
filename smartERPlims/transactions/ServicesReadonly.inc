<?php
  
$sqldebtors=DB_query("SELECT
    itemcode ,
    creditlimit,
    customer ,
    phone ,
    email ,
    city ,
    country,
      curr_cod,customerposting,salesman,VATinclusive
       FROM debtors join postinggroups on code=customerposting
       where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow=DB_fetch_row($sqldebtors);

$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];

$transstart=DB_Txn_Begin($db);
$PeriodNo = GetPeriod($_POST['date'],$db,true);

$sql=array();

$sql[]="INSERT INTO SalesHeader
           (documenttype
           ,documentno
           ,docdate
           ".(Is_date($_POST['Salesoderdate'])?',oderdate':'')."
           ".(Is_date($_POST['datedue'])?',duedate':'')."
           ,customercode
           ,customername
           ,yourreference
           ,locationcode
           ,postinggroup
           ,currencycode
           ,salespersoncode
           ,status
           ,userid
           ,period
           ,vatinclusive)
     VALUES
           ('32'
           ,'".$_POST['documentno']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
           ,'".$_POST['CustomerID']."'
           ,'".$_POST['CustomerName']."'
           ,'".$_POST['reference']."'
           ,'".$_POST['locationcode']."'
           ,'".$customerposting ."'
           ,'".$_POST['currencycode']."'
           ,'".$_POST['salespersoncode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."')" ;



foreach ($_POST['grossamount'] as $itemcode => $value ) {
if($value>0){
        
    $stockcode =$_POST['itemcode'][$itemcode];
   
    $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        stockmaster.partperunit,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        IFNULL(cv.vat,0) as CVAT,
                        unitloos.descrip as siloose
                  FROM stockmaster left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join unit unitloos on stockmaster.units=unitloos.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc
                  where stockmaster.inactive=0 and stockmaster.itemcode='".$stockcode."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   $stockname = $stkmaster[2];
   $si = $stkmaster[3];
   $siloos = $stkmaster[10];
   $parts = $stkmaster[5];
   $vatrate= $stkmaster[6];       
   $container=$stkmaster[7]; 
   $containername=$stkmaster[8]; 
   $containervat=$stkmaster[9]; 
         
    if($_POST['UOM'][$itemcode]=='fulqty'){
        $si = $stkmaster[3];
    }else{
        $si = $stkmaster[10];
    }
    
    $UOM=$_POST['UOM'][$itemcode];
      
   if(isset($_POST['quantity'][$itemcode])){
       $qty= $_POST['quantity'][$itemcode];
    }
       
    if(isset($_POST['salesprice'][$itemcode])){
       $salesprice= $_POST['salesprice'][$itemcode] ;
    }
       
    if(isset($_POST['grossamount'][$itemcode])){
        $grossamount= $_POST['grossamount'][$itemcode] ;
    }
    
    if(isset($_POST['vatamount'][$itemcode])){
       $vatamount= $_POST['vatamount'][$itemcode];
    }
    
    $sql[] =  sprintf("INSERT INTO SalesLine
           (documenttype,docdate,documentno
           ,code,description,unitofmeasure
           ,Quantity,UnitPrice,vatamount
           ,invoiceamount,vatrate ,inclusive
           ,UOM)
     VALUES
           ('%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
           ,'%s'
          ,'%s')"
            ,"32"
           ,FormatDateForSQL($_POST['date'])
           ,$_POST['documentno']
           ,$stockcode
           ,$stockname
           ,$si
           ,$qty
           ,$salesprice
           ,$vatamount
           ,$grossamount
           ,$vatrate
           ,$VATinclusive
           ,$UOM);
        }
}


if( $_SESSION['locked'] == true){
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }

    $_SESSION['savedsuccessfuly']=false;

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save sales order :'.$_POST['documentno'],'warn');

    }else{
        DB_Txn_Commit($db);
        prnMsg('Print sales order :'.$_POST['documentno'].'<a href="PDFPrintServiceOrder.php?No='.$_POST['documentno'].'">Print Order</a>');
        $_SESSION['savedsuccessfuly'] = true;
        $_SESSION['locked'] = false;
    }
    
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
      ,paymentterms
      ,postinggroup
      ,currencycode
      ,salespersoncode
      ,printed
      ,released
      ,status
      ,userid from SalesHeader where documenttype='32' and  documentno='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Service Order No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Service Order Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Service Order date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Service Order Due Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Customer ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Customer Name</td><td>%s</td></tr>",$row[6]);

    
    
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
           ,UOM from SalesLine 
           where documenttype=32 
           and documentno='".$_POST['documentno']."'";
    echo '<tr><td colspan="2">';
    
    echo '<table class="table table-bordered"><tr>'
            . '<th>Product<br /> Code</th>'
            . '<th>Product Name</th>'
            . '<th>Unit Of<br /> Measure</th>'
            . '<th class="number">Quantity</th>'
            . '<th class="number">Sales Price</th>'
            . '<th class="number">VAT Amount</th>'
            . '<th class="number">Gross Amount</th>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'],
                 $myrow['unitofmeasure'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
         $VATAMOUNT += $myrow['vatamount'];
         $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }
    
    
     echo sprintf('<tfoot><tr><td colspan="5">%s</td>'
             . '<td  class="number">%s</td><td class="number">%s</td></tr></tfoot>',
             'Service Order Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));

    echo '</table></td></tr>';
    echo '</table>';
    
}

    $_SESSION['Grossamounttotal']=0;
    $_SESSION['CompleteDocument']=$_POST['documentno'];
    
    
    echo '<div><input type="submit" name="confirm" value="'._('Confirm Sales Order').'"/></div>';
    echo '<div><input type="submit" name="Delete" value="'._('Cancel Order').'"/></div>';
?>
