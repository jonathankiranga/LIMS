<?php
  
$sqldebtors=DB_query("SELECT `itemcode`,`customer`,`phone`,`email`,
                             `city`,`country`,`curr_cod`,
                             `supplierposting`,`VATinclusive`
       FROM `creditors` JOIN `arpostinggroups` ON `code`=`supplierposting`
       WHERE `itemcode`='".$_POST['VendorID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[7];
$VATinclusive = $debtorsrow[8];


$transstart=DB_Txn_Begin($db);
$PeriodNo = GetPeriod($_POST['date'],$db,True);

$sql=array();

$sql[]="INSERT INTO `PurchaseHeader`
           (`documenttype`
           ,`documentno`
           ,`docdate`
           ".(Is_date($_POST['Salesoderdate'])?',`oderdate`':'')."
           ".(Is_date($_POST['datedue'])?',`duedate`':'')."
           ,`vendorcode`
           ,`vendorname`
           ,`yourreference`
           ,`locationcode`
           ,`postinggroup`
           ,`currencycode`
           ,`status`
           ,`userid`
           ,`period`
           ,`vatinclusive`
           ,`Dimension_1`
           ,`Dimension_2`)
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
           ,'0'
           ,'".$_POST['DimensionOne']."'
           ,'".$_POST['DimensionTwo']."')" ;



foreach ($_POST['quantity'] as $itemcode => $value ) {
 
    if($value>0){
        
        $stockcode =$_POST['itemcode'][$itemcode];
        
        $SqL="SELECT
        `stockmaster`.`barcode`,
        `stockmaster`.`itemcode`,
        `stockmaster`.`descrip` AS `stockname`,
        `unit`.`descrip` AS `si`,
        `stockmaster`.`averagestock`,
        `stockmaster`.`partperunit`,
        `vatcategory`.`vat`
  FROM `stockmaster`
  LEFT JOIN `unit` ON `stockmaster`.`units`=`unit`.`code`
  LEFT JOIN `inventorypostinggroup` ON `stockmaster`.`postinggroup`=`inventorypostinggroup`.`code`
  LEFT JOIN `vatcategory` ON `inventorypostinggroup`.`vatcategory`=`vatcategory`.`vatc`
 WHERE `stockmaster`.`inactive`=0 AND `itemcode`='".$stockcode ."'";
    
   $ResultIndex = DB_query($SqL, $db);
   $stkmaster = DB_fetch_row($ResultIndex);
   $stockname = $stkmaster[2];
   $si        = $stkmaster[3];
   $parts     = $stkmaster[5];
   $vatrate   = $stkmaster[6];       
   
    $UOM = $_POST['UOM'][$itemcode];
            
   if(isset($_POST['quantity'][$itemcode])){
        $qty = $_POST['quantity'][$itemcode];
    }
           
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode] ;
    }
    
    
    
    $sql[] =  sprintf("INSERT INTO `PurchaseLine`
           (`documenttype`
           ,`docdate`
           ,`documentno`
           ,`code`
           ,`description`
           ,`unitofmeasure`
           ,`Quantity`
           ,`UnitPrice`
           ,`vatamount`
           ,`invoiceamount`
           ,`vatrate`
           ,`inclusive`
           ,`UOM`)
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
            ,"18"
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
        prnMsg('Failed to save Production Job :'.$_POST['documentno'],'warn');
    }else{
        DB_Txn_Commit($db);
        prnMsg('new Purchase order :'.$_POST['documentno']);
        $_SESSION['savedsuccessfuly'] = true;
        $_SESSION['locked'] = false;
    }
    
}

if($_SESSION['savedsuccessfuly']==true){
    
    $SQL="SELECT
       `documentno`
      ,`docdate`
      ,`oderdate`
      ,`duedate`
      ,`postingdate`
      ,`vendorcode`
      ,`vendorname`
      ,`yourreference`
      ,`externaldocumentno`
      ,`locationcode`
      ,`paymentterms`
      ,`postinggroup`
      ,`currencycode`
      ,`printed`
      ,`released`
      ,`status`
      ,`userid` FROM `PurchaseHeader`
      WHERE `documenttype`='18' AND `documentno`='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Production No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Production Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Production Order date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Production Order Due Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Employee ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Employee Name</td><td>%s</td></tr>",$row[6]);
    echo '<tr><td colspan="2">Details of production</td></tr>';
    echo '<tr><td colspan="2">';
    
    echo '<table class="table table-bordered"><tr><th>Stock Code</th>'
            . '<th>Name</th>'
            . '<th>Unit Of Measure</th>'
            . '<th>Quantity</th>'
            . '<th>Production Rate</th>'
            . '<th>Gross</th>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $SQL="SELECT
           `documenttype`
           ,`docdate`
           ,`documentno`
           ,`code`
           ,`description`
           ,`unitofmeasure`
           ,`Quantity`
           ,`UnitPrice`
           ,`invoiceamount` FROM `PurchaseLine`
           WHERE `documenttype`=18 AND `documentno`='".$_POST['documentno']."'";
   
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'],
                 $myrow['unitofmeasure'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['invoiceamount'],2));
     
         $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }     
    echo sprintf('<tfoot><tr><td colspan="5">%s</td><td>%s</td><td>%s</td></tr></tfoot>','Production Job Totals',number_format($INVOICEAMOUNT,2));
    echo '</table></td></tr>';
    echo '</table>';
    
}
    $_SESSION['Grossamounttotal']=0;
    $_SESSION['CompleteDocument']=$_POST['documentno'];
    
    echo '<div><input type="submit" name="confirm" value="'._('Confirm Purchase Order').'"/></div>';
    echo '<div><input type="submit" name="Delete" value="'._('Cancel').'"/></div>';
?>
