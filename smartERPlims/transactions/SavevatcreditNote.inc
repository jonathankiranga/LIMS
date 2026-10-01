<?php


if($_SESSION['DocumentPosted']==true){
    prnMsg('You cannot repost this invoice','warn');
}else{
       
    
    $sqldebtors=DB_query("SELECT itemcode,creditlimit,customer,phone,
    email,city,country,curr_cod,customerposting,salesman,VATinclusive
    FROM debtors join postinggroups on code=customerposting 
    where itemcode='".$_POST['CustomerID']."'", $db);
    
    $debtorsrow      = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[8];
    $VATinclusive    = $debtorsrow[10];
    $PeriodNo        = GetPeriod($_POST['date'],$db,true);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       =  GetNextTransNo(13,$db);
    
    $sql=array();
    
    
 
    $sql[] ="insert into SalesHeader 
      (documenttype
      ,documentno
      ,docdate
       ".(Is_date($_POST['Salesoderdate'])?',oderdate':'')."
       ".(Is_date($_POST['datedue'])?',duedate':'')."
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
      ,vatinclusive) 
      values ('13'
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
      ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
      ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
      ,'".FormatDateForSQL($_POST['date'])."'
      ,'".$_POST['CustomerID']."'
      ,'".$_POST['CustomerName']."'
      ,'".$_POST['reference']."'
      ,'".$_POST['reference']."'
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,'".$_POST['salespersoncode']."'
      ,'0'
      ,'0'
      ,'1'
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."'
      ) ";
    
    foreach ($_POST['subunits'] as $key => $value) {
       
        if($value>0){
            $location   = $_POST['location'][$key];
            $stcode     = $_POST['code'][$key];
            $qty        = $_POST['subunits'][$key];        
            $salesprice = $_POST['salesprice'][$key];
            $netamount  = $_POST['netamount'][$key];
            $vatamount  = $_POST['vatamount'][$key];
            $grossamount = $_POST['grossamount'][$key];
          
            $sql[] = sprintf("INSERT INTO SalesLine 
                (documenttype,docdate,documentno,code,description,unitofmeasure,Quantity,UnitPrice,vatamount,invoiceamount,vatrate,inclusive,locationcode,UOM) 
                (select '%s','%s','%s',code,description,unitofmeasure,%f,%f,%f,%f,vatrate,inclusive,documentno,UOM from SalesLine where entryno='%s')"
                ,13,FormatDateForSQL($_POST['date']),$INVOICENO,$qty,$salesprice,$vatamount,$grossamount ,$key);
    
         }
  
    }
    

     $journal=GetNextTransNo(0,$db);
     $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
     $sql[]=PostDebtors($PeriodNo,$journal,$INVOICENO);
     $sql[]=CustomerStatemet($journal,$INVOICENO);
        
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
            AND (ipg.CostOfSales IS NULL OR ipg.balancesheet IS NULL)");
        $badCount = DB_fetch_row($checkResult);
        if ($badCount[0] > 0) {
            prnMsg('Cannot post the credit note. ' . $badCount[0] . ' item(s) have inventory posting groups with missing Cost of Sales or Balance Sheet accounts. Update them in Inventory Posting Groups before posting.', 'error');
            DB_Txn_Rollback($db);
            return;
        }
    }
    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error Posting the Credit Note, Contact your administrator','warn');
    } else {
        prnMsg('Click here to Print Credit Note :<a href="PDFPrintVATcreditnote.php?No='.$INVOICENO.'">'.$INVOICENO.'</a>');
  
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
      SalesHeader.postingdate
      ,rtrim(SalesLine.description)  as narration,'I'
      ,SalesHeader.documentno
      ,SalesHeader.customercode
      ,0-SUM(SalesLine.vatamount) as Value
      ,0.00
      ,SalesHeader.currencycode
      ,'I'
      ,'%s'
      ,'r'
      ,0-SUM(SalesLine.vatamount) as VAT
      ,SalesHeader.documenttype
      ,SalesHeader.postinggroup
      ,'%s'
  FROM SalesHeader join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        where SalesLine.documentno='%s' and SalesLine.documenttype =13
        group by
       SalesHeader.documenttype
      ,SalesHeader.documentno
      ,SalesHeader.postingdate
      ,SalesHeader.customercode
      ,SalesHeader.externaldocumentno
      ,SalesHeader.postinggroup
      ,SalesHeader.currencycode
      ,SalesLine.Quantity
      ,SalesLine.description
      ,SalesLine.code
      ,SalesLine.UOM)", $journal,$period,$doc);
   
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
      ,cutomercode
      ,narration
      ,dimension
      ,dimension2)
      (select
       '%s'
      ,SalesHeader.postingdate
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,inventorypostinggroup.defaultgl_vat
      ,postinggroups.debtorsaccount
      ,inventorypostinggroup.defaultgl_vat
      ,IFNULL(SalesLine.vatamount,0) as invoiceamount
      ,IFNULL(SalesLine.vatamount,0) as vatamount
      ,SalesHeader.currencycode
      ,currencies.rate
      ,SalesHeader.customercode
      ,rtrim(SalesLine.description)  as narration
       ,' ".$_POST['DimensionOne']."' 
       ,' ".$_POST['DimensionTwo']."'
      from SalesHeader join postinggroups on SalesHeader.postinggroup=postinggroups.code
      join SalesLine on SalesLine.documentno = SalesHeader.documentno 
      join stockmaster on SalesLine.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on SalesHeader.currencycode=currencies.currabrev
      where SalesLine.documentno='%s' and SalesLine.documenttype =13
      )",$journal,$period,$doc);
    
    return $sql;
    
}


Function CustomerStatemet($journal,$doc){
    $sql=sprintf("INSERT INTO CustomerStatement
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
              SalesHeader.postingdate
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,0-sum(SalesLine.vatamount)
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."' 
             ,SalesHeader.currencycode    
             from SalesHeader
             join SalesLine  on SalesLine.documentno = SalesHeader.documentno 
           where SalesLine.documentno='%s' and SalesLine.documenttype =13
           Group by 
             SalesHeader.postingdate
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,SalesHeader.currencycode
             ) ", $journal,$doc);
    
    return $sql;
}






?>