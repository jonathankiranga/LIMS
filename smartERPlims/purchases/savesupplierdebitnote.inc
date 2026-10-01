<?php

$_SESSION['DocumentNo']=$_POST['documentno'] ;

if($_SESSION['DocumentPosted']==true){
    prnMsg('You cannot repost this invoice','warn');
} else {
    
        $sql="SELECT itemcode,customer,phone,email,city,country,curr_cod,supplierposting,VATinclusive,IsTaxed
         FROM creditors join arpostinggroups on code=supplierposting  where itemcode='".$_POST['CustomerID']."'";
        $res = DB_query($sql, $db);
        $debtorsrow = DB_fetch_row($res);
        $customerposting = $debtorsrow[7];
        $VATinclusive    = $debtorsrow[8];
        $PeriodNo        = GetPeriod($_POST['posingdate'],$db,true);
        $INVOICENO       = GetNextTransNo(24,$db);
        
    $sql=array();
    
    $sql[] ="insert into PurchaseHeader 
      (documenttype
      ,documentno
      ,docdate
      ,postingdate
      ,vendorcode
      ,vendorname
      ,yourreference
      ,externaldocumentno
      ,postinggroup
      ,currencycode
      ,printed
      ,released
      ,status
      ,userid
      ,period
      ,vatinclusive) 
      (select 
      24
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
       ,'".FormatDateForSQL($_POST['posingdate'])."'
       ,'".$_POST['CustomerID']."'
       ,'".$_POST['CustomerName']."'
      ,'".$_POST['reference']."'
      ,'".$_SESSION['DocumentNo']."'
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,0
      ,0
      ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."'
      from PurchaseHeader 
      where documentno='".$_SESSION['DocumentNo']."') ";
    
    foreach ($_POST['subunits'] as $key => $value) {
       
    if($value>0){
        
    $location = $_POST['location'][$key];
    $stcode = $_POST['code'][$key];
    $qty = $_POST['subunits'][$key];        
    $salesprice = $_POST['salesprice'][$key];
    $netamount = $_POST['netamount'][$key];
    $vatamount = $_POST['vatamount'][$key];
    $grossamount = $_POST['grossamount'][$key];

  
    $sql[] =  sprintf("INSERT INTO PurchaseLine
           (documenttype,docdate,documentno
           ,code,description,unitofmeasure
           ,Quantity,UnitPrice,vatamount
           ,invoiceamount,vatrate
           ,inclusive,locationcode,PartPerUnit) 
           (select '%s','%s' ,'%s' ,code,description,unitofmeasure
           ,%f,%f,%f,%f,vatrate,inclusive,'%s',PartPerUnit
           from PurchaseLine where entryno='".$key."')"
           ,'24'
           ,FormatDateForSQL($_POST['date'])
           ,$INVOICENO
           ,$qty
           ,$salesprice
           ,$vatamount
           ,$grossamount
           ,$location);
        
    }
  
    }
    
  
    $journal=GetNextTransNo(0,$db);
    DB_Txn_Begin($db);
   
    $sql[]=PostCreditors($PeriodNo,$journal,$INVOICENO);
    $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO) ;  
    $sql[]=SupplierStatemet($journal,$INVOICENO);
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
    
    $codes = array();
    foreach ($_POST['subunits'] as $key => $value) {
        if ($value > 0) {
            $codes[] = "'" . $db->real_escape_string($_POST['code'][$key]) . "'";
        }
    }
    if (!empty($codes)) {
        $checkResult = DB_query("SELECT COUNT(*) FROM stockmaster sm
            JOIN inventorypostinggroup ipg ON ipg.code = sm.postinggroup
            WHERE sm.itemcode IN (" . implode(',', $codes) . ")
            AND (ipg.defaultgl_purch IS NULL OR ipg.defaultgl_vat IS NULL)");
        $badCount = DB_fetch_row($checkResult);
        if ($badCount[0] > 0) {
            prnMsg('Cannot post the supplier debit note. ' . $badCount[0] . ' item(s) have inventory posting groups with missing Purchase or VAT accounts. Update them in Inventory Posting Groups before posting.', 'error');
            DB_Txn_Rollback($db);
            return;
        }
    }
    
    foreach ($sql as $value) {
      $ResultIndex=DB_query($value,$db);
    }
    
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error Posting the invoice, Contact your administrator','warn');
    }else{
        DB_Txn_Commit($db);
        prnMsg('This Document has successfully been posted in the General Ledger');
  
        $sql =  sprintf("update PurchaseHeader set PurchaseHeader.status=2
                where documenttype=21 and PurchaseHeader.documentno='%s' ",$_POST['documentno']);
        $ResultIndex=DB_query($sql,$db);
         
        $_SESSION['DocumentPosted']=true;
        unset($_SESSION['DocumentNo']);
        unset($_POST);
    }
    
}

function PostCreditors($period,$journal,$doc){
   $SQL= sprintf("Insert into creditorsledger
       (date,details ,flag,invref ,acctfolio ,amount,pamount
      ,curr_cod ,i_n_t ,journal,typ ,vatamt,systypes_1 ,ledger ,period)
  (SELECT 
      PurchaseHeader.postingdate
      ,CONCAT(RTRIM(PurchaseLine.description),' X ',CAST(PurchaseLine.Quantity AS CHAR)) as narration,'I'
      ,PurchaseHeader.documentno
      ,PurchaseHeader.vendorcode
      ,SUM(PurchaseLine.invoiceamount) as Value
      ,0.00
      ,PurchaseHeader.currencycode
      ,'I'
      ,'%s'
      ,'I'
      ,SUM(PurchaseLine.vatamount) as VAT
      ,PurchaseHeader.documenttype
      ,PurchaseHeader.postinggroup
      ,'%s'
  FROM PurchaseHeader join PurchaseLine 
        on PurchaseHeader.documentno=PurchaseLine.documentno 
        and PurchaseHeader.documenttype=PurchaseLine.documenttype 
        where PurchaseHeader.documentno='%s' 
        group by
       PurchaseHeader.documenttype
      ,PurchaseHeader.documentno
      ,PurchaseHeader.postingdate
      ,PurchaseHeader.vendorcode
      ,PurchaseHeader.externaldocumentno
      ,PurchaseHeader.postinggroup
      ,PurchaseHeader.currencycode
      ,PurchaseLine.Quantity
      ,PurchaseLine.description
      ,PurchaseLine.code)", $journal,$period,$doc);
   
   return $SQL;
}

function PostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into Generalledger
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
      ,suppliercode
      ,narration
      ,dimension
      ,dimension2)
      (select
       '%s'
      ,PurchaseHeader.postingdate
      ,'%s'
      ,PurchaseHeader.documentno
      ,PurchaseHeader.documenttype
      ,arpostinggroups.creditorsaccount
      ,inventorypostinggroup.defaultgl_purch
      ,inventorypostinggroup.defaultgl_vat
      ,PurchaseLine.invoiceamount
      ,IFNULL(PurchaseLine.vatamount,0) as vatamount
      ,PurchaseHeader.currencycode
      ,currencies.rate
      ,PurchaseHeader.vendorcode
      ,CONCAT(RTRIM(PurchaseLine.description),'X',CAST(PurchaseLine.Quantity AS CHAR)) as narration
      ,'".trim($_POST['DimensionOne'])."'
      ,'".trim($_POST['DimensionTwo'])."'    
      from PurchaseHeader join arpostinggroups on PurchaseHeader.postinggroup=arpostinggroups.code
      join PurchaseLine on PurchaseLine.documentno = PurchaseHeader.documentno 
      join stockmaster on PurchaseLine.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on PurchaseHeader.currencycode=currencies.currabrev
      where PurchaseHeader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

function VATPostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into Generalledger
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
      ,suppliercode
      ,narration)
      (select
       '%s'
      ,PurchaseHeader.postingdate
      ,'%s'
      ,PurchaseHeader.documentno
      ,PurchaseHeader.documenttype
      ,inventorypostinggroup.defaultgl_purch
      ,inventorypostinggroup.defaultgl_vat
      ,''
      ,PurchaseLine.vatamount
      ,0
      ,PurchaseHeader.currencycode
      ,currencies.rate
      ,inventorypostinggroup.defaultgl_vat
      ,CONCAT(RTRIM(PurchaseLine.description),' X ',CAST(PurchaseLine.Quantity AS CHAR)) as narration
      from PurchaseHeader join arpostinggroups on PurchaseHeader.postinggroup=arpostinggroups.code
      join PurchaseLine on PurchaseLine.documentno = PurchaseHeader.documentno 
      join stockmaster on PurchaseLine.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on PurchaseHeader.currencycode=currencies.currabrev
      where PurchaseHeader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

Function SupplierStatemet($journal,$doc){
    $sql=sprintf("INSERT INTO SupplierStatement
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
              PurchaseHeader.docdate
             ,PurchaseHeader.documentno
             ,PurchaseHeader.documenttype
             ,PurchaseHeader.vendorcode
             ,sum(PurchaseLine.invoiceamount)
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."'  
             ,PurchaseHeader.currencycode   
             From PurchaseHeader
             join PurchaseLine on PurchaseLine.documentno = PurchaseHeader.documentno 
             where PurchaseHeader.documentno='%s' 
             Group By PurchaseHeader.docdate
             ,PurchaseHeader.documentno
             ,PurchaseHeader.documenttype
             ,PurchaseHeader.vendorcode
             ,PurchaseHeader.currencycode 
             ) ",
            $journal,$doc);
    
    return $sql;
}

?>
