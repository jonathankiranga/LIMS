<?php

if($_SESSION['DocumentPosted']==true){
    prnMsg('You cannot repost this invoice','warn');
}else{
       
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
    
    $sqldebtors=DB_query("SELECT itemcode,creditlimit,customer,phone,
    email,city,country,curr_cod,customerposting,salesman,VATinclusive
    FROM debtors join postinggroups on code=customerposting 
    where itemcode='".$_POST['CustomerID']."'", $db);
    
    $debtorsrow = DB_fetch_row($sqldebtors);

    $customerposting = $debtorsrow[8];
    $VATinclusive    = $debtorsrow[10];
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,true);
 
    $TransBuffer     = DB_Txn_Begin($db);
   
    $INVOICENO=GetNextTransNo(10,$db);
    
    $sql=array();
    
    $sql[] ="insert into `salesheader` 
      (documenttype
      ,documentno
      ,docdate
      ,postingdate
      ,customercode
      ,customername
      ,yourreference
      ,externaldocumentno
      ,postinggroup
      ,currencycode
      ,salespersoncode
      ,printed
      ,released
      ,status
      ,userid
      ,period
      ,vatinclusive) values ('10'
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
      ,'".FormatDateForSQL($_POST['posingdate'])."'
      ,'".$_POST['CustomerID']."'
      ,'".$_POST['CustomerName']."'
      ,'".$_POST['reference']."'
      ,''
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,'".$_POST['salespersoncode']."'
      ,0  ,2  ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."') ";
    
    foreach ($_POST['grossamount'] as $key => $value) {
       
    if($value>0){
        
        $stockcode=$_POST['itemcode'][$key];

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
         
    if($_POST['UOM'][$key]=='fulqty'){
        $si = $stkmaster[3];
    }else{
        $si = $stkmaster[10];
    }
    
    $UOM=$_POST['UOM'][$key];
      
   if(isset($_POST['quantity'][$key])){
       $qty= $_POST['quantity'][$key];
    }
       
    if(isset($_POST['salesprice'][$key])){
       $salesprice= $_POST['salesprice'][$key] ;
    }
       
    if(isset($_POST['grossamount'][$key])){
        $grossamount= $_POST['grossamount'][$key] ;
    }
    
    if(isset($_POST['vatamount'][$key])){
       $vatamount= $_POST['vatamount'][$key];
    }
    
    $sql[] =  sprintf("INSERT INTO `salesline``
           (documenttype,docdate,documentno ,code,description,unitofmeasure,Quantity,UnitPrice,vatamount ,invoiceamount,vatrate ,inclusive ,UOM)
     VALUES
           ('%s'  ,'%s'  ,'%s'  ,'%s' ,'%s'  ,'%s'  ,'%s' ,'%s'  ,'%s' ,'%s' ,'%s'  ,'%s' ,'%s')"
            ,"10" ,FormatDateForSQL($_POST['posingdate']) ,$INVOICENO ,$stockcode ,$stockname,$si ,$qty ,$salesprice ,$vatamount,$grossamount,$vatrate,$VATinclusive,$UOM);
        }
  
    }
    
   
    
    $journal=GetNextTransNo(0,$db);
    
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=PostDebtors($PeriodNo,$journal,$INVOICENO);
    $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=COGSPostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=CustomerStatemet($journal,$INVOICENO);
         
    $codes = array();
    foreach ($_POST['grossamount'] as $key => $value) {
        if ($value > 0 && isset($_POST['itemcode'][$key])) {
            $codes[] = "'" . $db->real_escape_string($_POST['itemcode'][$key]) . "'";
        }
    }
    if (!empty($codes)) {
        $checkResult = DB_query("SELECT COUNT(*) FROM stockmaster sm
            JOIN inventorypostinggroup ipg ON ipg.code = sm.postinggroup
            WHERE sm.itemcode IN (" . implode(',', $codes) . ")
            AND (ipg.CostOfSales IS NULL OR ipg.balancesheet IS NULL)");
        $badCount = DB_fetch_row($checkResult);
        if ($badCount[0] > 0) {
            prnMsg('Cannot post the invoice. ' . $badCount[0] . ' item(s) have inventory posting groups with missing Cost of Sales or Balance Sheet accounts. Update them in Inventory Posting Groups before posting.', 'error');
            DB_Txn_Rollback($db);
            return;
        }
    }
    
    foreach ($sql as $sqlvalue) {
        $ResultIndex=DB_query($sqlvalue,$db);
    }
    
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error Posting the invoice, Contact your administrator','warn');
    }else{
        prnMsg('Click here to print invoice No :'.'<a href="PDFPrintSalesInvoice.php?No='.$INVOICENO.'">'.$INVOICENO.'</a>');
 
        DB_query("Update `salesheader` set released=2 where documentno='".$INVOICENO."'",$db);
        DB_Txn_Commit($db);
        prnMsg('This Sale has successfully been posted in the General Ledger');
        
        $_SESSION['DocumentPosted']=true;
        unset($_SESSION['DocumentNo']);
        unset($_POST);
    }
    
}

function PostDebtors($period,$journal,$doc){
   $SQL= sprintf("Insert into debtorsledger
       (date
      ,details
      ,flag
      ,invref
      ,acctfolio
      ,amount
      ,pamount
      ,curr_cod
      ,i_n_t
      ,journal
      ,typ
      ,vatamt
      ,systypes_1
      ,ledger
      ,period)
  (SELECT 
      salesheader.postingdate
      ,CONCAT(RTRIM(salesline.description),' X ',CAST(salesline.Quantity AS CHAR),' ',
		  (case salesline.UOM when 'fulqty' 
	      then (select rtrim(unit.descrip) from unit join stockmaster on stockmaster.units=unit.code  and stockmaster.itemcode=salesline.code) 
		  else (select rtrim(unit.descrip) from unit join stockmaster on stockmaster.units=unit.code  and stockmaster.itemcode=salesline.code)
		  end))  as narration,'I'
      ,salesheader.documentno
      ,salesheader.customercode
      ,SUM(salesline.invoiceamount) as Value
      ,0.00
      ,salesheader.currencycode
      ,'I'
      ,'%s'
      ,'r'
      ,SUM(salesline.vatamount) as VAT
      ,salesheader.documenttype
      ,salesheader.postinggroup
      ,'%s'
  FROM `salesheader`` join `salesline` 
        on salesheader.documentno=salesline.documentno 
        and salesheader.documenttype=salesline.documenttype 
        where salesheader.documentno='%s' 
        group by
       salesheader.documenttype
      ,salesheader.documentno
      ,salesheader.postingdate
      ,salesheader.customercode
      ,salesheader.externaldocumentno
      ,salesheader.postinggroup
      ,salesheader.currencycode
      ,salesline.Quantity
      ,salesline.description
      ,salesline.code
      ,salesline.UOM)", $journal,$period,$doc);
   
   return $SQL;
}

function PostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into `generalledger`
      (journalno
      ,Docdate
      ,period
      ,DocumentNo
      ,DocumentType
      ,accountcode
      ,balaccountcode
      ,VATaccountcode
      ,amount
      ,VATamount
      ,currencycode
      ,ExchangeRate
      ,cutomercode
      ,narration
      ,dimension
      ,dimension2)
      (select
       '%s'
      ,salesheader.postingdate
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,postinggroups.debtorsaccount
      ,inventorypostinggroup.defaultgl_sales
      ,inventorypostinggroup.defaultgl_vat
      ,salesline.invoiceamount
      ,IFNULL(salesline.vatamount,0) as vatamount
      ,salesheader.currencycode
      ,currencies.rate
      ,salesheader.customercode
      ,CONCAT(RTRIM(salesline.description),' X ',CAST(salesline.Quantity AS CHAR),' ',
		  (case salesline.UOM when 'fulqty' 
	      then (select rtrim(unit.descrip) from unit join stockmaster on stockmaster.units=unit.code  and stockmaster.itemcode=salesline.code) 
		  else (select rtrim(unit.descrip) from unit join stockmaster on stockmaster.units=unit.code  and stockmaster.itemcode=salesline.code)
		  end))  as narration
        ,'".$_POST['DimensionOne']."' 
        ,'".$_POST['DimensionTwo']."'
      from `salesheader` join postinggroups on salesheader.postinggroup=postinggroups.code
      join `salesline` on salesline.documentno = salesheader.documentno 
      join stockmaster on salesline.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on salesheader.currencycode=currencies.currabrev
      where salesheader.documentno='%s'
      AND inventorypostinggroup.defaultgl_sales IS NOT NULL
      AND inventorypostinggroup.defaultgl_vat IS NOT NULL
      )",$journal,$period,$doc);
    
    return $sql;
    
}

function VATPostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into `generalledger`
      (journalno
      ,Docdate
      ,period
      ,DocumentNo
      ,DocumentType
      ,accountcode
      ,balaccountcode
      ,VATaccountcode
      ,amount
      ,VATamount
      ,currencycode
      ,ExchangeRate
      ,cutomercode
      ,narration)
      (select
       '%s'
      ,salesheader.postingdate
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,inventorypostinggroup.defaultgl_sales
      ,inventorypostinggroup.defaultgl_vat
      ,''
      ,salesline.vatamount
      ,0
      ,salesheader.currencycode
      ,currencies.rate
      ,salesheader.customercode
      ,CONCAT('VAT on ',RTRIM(salesline.description),' X ',CAST(salesline.Quantity AS CHAR),' ',
		  (case salesline.UOM when 'fulqty' 
	      then (select rtrim(unit.descrip) from unit join stockmaster on stockmaster.units=unit.code  and stockmaster.itemcode=salesline.code) 
		  else (select rtrim(unit.descrip) from unit join stockmaster on stockmaster.units=unit.code  and stockmaster.itemcode=salesline.code)
		  end))  as narration
      from `salesheader` join postinggroups on salesheader.postinggroup=postinggroups.code
      join `salesline` on salesline.documentno = salesheader.documentno 
      join stockmaster on salesline.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on salesheader.currencycode=currencies.currabrev
      where salesheader.documentno='%s'
      AND inventorypostinggroup.defaultgl_sales IS NOT NULL
      AND inventorypostinggroup.defaultgl_vat IS NOT NULL
      )",$journal,$period,$doc);
    
    return $sql;
    
}

Function CustomerStatemet($journal,$doc){
    $sql=sprintf("INSERT INTO `customerstatement`
           (Date
           ,Documentno
           ,Documenttype
           ,Accountno
           ,Grossamount
           ,JournalNo
           ,Dimension_One
           ,Dimension_Two
           ,Currency)
      (select
            salesheader.postingdate
           ,salesheader.documentno
           ,salesheader.documenttype
           ,salesheader.customercode
           ,sum(salesline.invoiceamount)
           ,'%s'
           ,'".$_POST['DimensionOne']."'
           ,'".$_POST['DimensionTwo']."' 
           ,salesheader.currencycode    
           from `salesheader`
           join `salesline` on salesline.documentno = salesheader.documentno 
         where salesheader.documentno='%s' 
         Group by  
            salesheader.postingdate
           ,salesheader.documentno
           ,salesheader.documenttype
           ,salesheader.customercode
           ,salesheader.currencycode) ",
       $journal,$doc);

    return $sql;
}

function COGSPostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into `generalledger`
      (journalno
      ,Docdate
      ,period
      ,DocumentNo
      ,DocumentType
      ,accountcode
      ,balaccountcode
      ,VATaccountcode
      ,amount
      ,VATamount
      ,currencycode
      ,ExchangeRate
      ,cutomercode
      ,narration
      ,dimension
      ,dimension2)
      (select
       '%s'
      ,salesheader.postingdate
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,inventorypostinggroup.balancesheet
      ,inventorypostinggroup.CostOfSales
      ,''
      ,IFNULL(stockmaster.averagestock,0) * salesline.Quantity
      ,0
      ,salesheader.currencycode
      ,currencies.rate
      ,salesheader.customercode
      ,CONCAT(RTRIM(salesline.description),' X ',CAST(salesline.Quantity AS CHAR),' ',
		  (case salesline.UOM when 'fulqty' 
	      then (select rtrim(unit.descrip) from unit join stockmaster on stockmaster.units=unit.code  and stockmaster.itemcode=salesline.code) 
		  else (select rtrim(unit.descrip) from unit join stockmaster on stockmaster.units=unit.code  and stockmaster.itemcode=salesline.code)
		  end))  as narration
        ,'".$_POST['DimensionOne']."' 
        ,'".$_POST['DimensionTwo']."'
      from `salesheader` join postinggroups on salesheader.postinggroup=postinggroups.code
      join `salesline` on salesline.documentno = salesheader.documentno 
      join stockmaster on salesline.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on salesheader.currencycode=currencies.currabrev
      where salesheader.documentno='%s'
      )",$journal,$period,$doc);

    return $sql;

}

?>
