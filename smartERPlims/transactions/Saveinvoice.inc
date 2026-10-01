<?php

$_SESSION['DocumentNo']=$_POST['documentno'] ;

$makebarcode=new makebarcode();

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
    
    $debtorsrow      = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[8];
    $salesrep        = $debtorsrow[9];
    $VATinclusive    = $debtorsrow[10]?? 0;
    $PeriodNo        = GetPeriod($_POST['date'],$db,false);
    $TransBuffer     = DB_Txn_Begin($db);
    $journal         = GetNextTransNo(0,$db);
    if(($_SESSION['ManualNumber']==0)){
         $INVOICENO= GetNextTransNo(10,$db);
    }else{
        $INVOICENO = $_POST['manualdocumentno'];
    }
    $sql = array();
    // determine external document number to store (prefer quotationno from form, else original document)
    if (isset($_POST['quotationno']) && trim($_POST['quotationno']) !== ''){
        $external_doc = $db->real_escape_string(trim($_POST['quotationno']));
    } else {
        $external_doc = $_SESSION['DocumentNo'];
    }
    
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
      ,vatinclusive
      ,journal
      ,packagescharge
      ,picture) 
      (select 10
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
       ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
       ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
       ,'".FormatDateForSQL($_POST['posingdate'])."'
       ,'".$_POST['CustomerID']."'
       ,'".$_POST['CustomerName']."'
    ,'".$_POST['reference']."'
    ,'".$external_doc."'
    ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,'".$_POST['salespersoncode']."'
      ,0
      ,0
      ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."'
      ,'".$journal ."'
      ,'0'
      ,picture
      from SalesHeader 
      where documentno='".$_SESSION['DocumentNo']."') ";
   
    $skippedZero = 0;
    foreach ($_SESSION['invoiceRows'] as $key => $row) {

        // Skip zero-amount lines: nothing to bill (price and gross both
        // come from the freshly re-rendered session rows).
        if (!isset($row['salesprice']) || (float)$row['salesprice'] <= 0
            || !isset($row['grossamount']) || (float)$row['grossamount'] <= 0) {
            $skippedZero++;
            continue;
        }
        {
            $linepackage = $row['linepackage'];
            $Shipping = $row['Shipping'];
            $netamount = $row['netamount'];
            $vatamount = $row['vatamount'];
            $grossamount = $row['grossamount'];
            $totalemptycost = $row['totalemptycost'];
            $salesprice = $row['salesprice'];

            // Determine discount to persist: prefer POST override, otherwise session row value
            if (isset($_POST['linediscountpercent']) && isset($_POST['linediscountpercent'][$key])) {
                $ld_sql = (float) $_POST['linediscountpercent'][$key];
            } else {
                $ld_sql = $row['linediscountpercent'];
            }

            // Determine sampleID to persist: prefer POST override, otherwise session row value
            if (isset($_POST['sampleid']) && isset($_POST['sampleid'][$key]) && $_POST['sampleid'][$key] !== '') {
                $sample_sql = "'" . $db->real_escape_string($_POST['sampleid'][$key]) . "'";
            } else {
                $sample_sql = "'" . $db->real_escape_string($row['sampleid']) . "'";
            }

            // Determine TAT to persist: prefer POST override, otherwise session row value
            if (isset($_POST['TAT']) && isset($_POST['TAT'][$key]) && $_POST['TAT'][$key] !== '') {
                $tat_sql = (int) $_POST['TAT'][$key];
            } else {
                $tat_sql = (int) $row['TAT'];
            }

           $sql[] =  sprintf("INSERT INTO SalesLine
           (documenttype,docdate,documentno,code,description,unitofmeasure,Quantity,UnitPrice,
           vatamount,invoiceamount,vatrate,inclusive,locationcode,partperunit,LineDiscountPercent,shipping,packagescharge,
           PriceInPricelist,totalchargedcontainers,containercode,sampleID,TAT) 
           (select '%s','%s','%s',code,description,unitofmeasure,Qunatity_delivered,
           %f ,%f ,%f ,vatrate,inclusive,locationcode,partperunit,%s,%f,%f,PriceInPricelist ,%f ,containercode,%s,%s from SalesLine where entryno='".$key."')" ,
            10 ,FormatDateForSQL($_POST['date']) ,$INVOICENO ,
            $salesprice,$vatamount ,$grossamount ,$ld_sql, $Shipping,
            $linepackage,$totalemptycost, $sample_sql, $tat_sql );
        }
    }

    if ($skippedZero > 0) {
        prnMsg($skippedZero . ' zero-amount line(s) skipped (not saved to the invoice).', 'info');
    }
        
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=PostDebtors($PeriodNo,$journal,$INVOICENO);
    $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=COGSPostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=CustomerStatemet($journal,$INVOICENO);
    
    $checkResult = DB_query("SELECT COUNT(*) FROM SalesLine sl
        JOIN stockmaster sm ON sl.code = sm.itemcode
        JOIN inventorypostinggroup ipg ON ipg.code = sm.postinggroup
        WHERE sl.documentno = '".$_SESSION['DocumentNo']."'
        AND (ipg.CostOfSales IS NULL OR ipg.balancesheet IS NULL)");
    $badCount = DB_fetch_row($checkResult);
    if ($badCount[0] > 0) {
        prnMsg('Cannot post the invoice. ' . $badCount[0] . ' item(s) have inventory posting groups with missing Cost of Sales or Balance Sheet accounts. Update them in Inventory Posting Groups before posting.', 'error');
        DB_Txn_Rollback($db);
        return;
    }
    
    $stmtIdx = 0;
    $stmtCount = count($sql);
    foreach ($sql as $value) {
        $stmtIdx++;
        $ResultIndex=DB_query($value,$db);
        if (DB_error_no($db) > 0) {
            break;
        }
    }


    if(DB_error_no($db)>0){
        $postErrNo = DB_error_no($db);
        $postErrMsg = function_exists('DB_error_msg') ? DB_error_msg($db) : $db->error;
        DB_Txn_Rollback($db);
        prnMsg('There was an Error Posting the invoice (statement ' . $stmtIdx . ' of ' . $stmtCount . ', DB ' . $postErrNo . '): ' . $postErrMsg . ' [doc ' . ($_POST['documentno'] ?? '?') . ']', 'error');
        @file_put_contents(dirname(__DIR__) . '/quotes/invoice_post_errors.log',
            '[' . date('Y-m-d H:i:s') . '] doc=' . ($_POST['documentno'] ?? '?')
            . ' stmt=' . $stmtIdx . '/' . $stmtCount
            . ' errno=' . $postErrNo . ' error=' . $postErrMsg . PHP_EOL, FILE_APPEND);
     }else{
        DB_query("Update SalesHeader set released=2 where documentno='".$_SESSION['DocumentNo']."'",$db);
        DB_Txn_Commit($db);

        $etimsResult = etimsSubmitInvoice(10, $INVOICENO);
         
         prnMsg('This Sale has successfully been posted in the General Ledger');
         if ($etimsResult['success']) {
             prnMsg('eTIMS submission completed: ' . $etimsResult['message'], 'success');
             
                 $etimsSQL = "SELECT kra_invoice_no, cu_invoice_no, control_unit_id, kra_pin, invoice_signature, qr_code_text, verification_url, etims_issue_datetime, etims_status, etims_message
                 FROM etims_invoice
                 WHERE documenttype='10' AND documentno='".$INVOICENO."'";
                $etimsResult = DB_query($etimsSQL, $db);
                $etimsRow = DB_fetch_array($etimsResult);
                $etims = is_array($etimsRow) ? $etimsRow : array();
                //$qr_code_text,$kra_invoice_no
                $makebarcode->MakeQrcode($etims["qr_code_text"],$INVOICENO);

         } elseif (!empty($etimsResult['skipped'])) {
             prnMsg('eTIMS submission skipped: ' . $etimsResult['message'], 'warn');
         } else {
             prnMsg('Invoice posted, but eTIMS submission failed: ' . $etimsResult['message'], 'warn');
         }
   
         echo sprintf('<p class="page_title_text"><a id="'.$INVOICENO.'" href="%s?No=%s" >
         <img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Invoice') . '" alt="" />%s</a></p>',
        'PDFPrintSalesInvoice.php',$INVOICENO, _('Print Invoice ').$INVOICENO);
    
        echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$INVOICENO);
        
         
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
      SalesHeader.docdate
      ,rtrim(SalesLine.description)  as narration,'I'
      ,SalesHeader.documentno
      ,SalesHeader.customercode
      ,SUM(SalesLine.invoiceamount) as Value
      ,0.00
      ,SalesHeader.currencycode
      ,'I'
      ,'%s'
      ,'r'
      ,SUM(SalesLine.vatamount) as VAT
      ,SalesHeader.documenttype
      ,SalesHeader.postinggroup
      ,'%s'
  FROM SalesHeader join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        where SalesHeader.documentno='%s' 
        group by
       SalesHeader.documenttype
      ,SalesHeader.documentno
      ,SalesHeader.docdate
      ,SalesHeader.customercode
      ,SalesHeader.externaldocumentno
      ,SalesHeader.postinggroup
      ,SalesHeader.currencycode
      ,SalesLine.Quantity
      ,SalesLine.description
      ,SalesLine.code)", $journal,$period,$doc);
   
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
      ,SalesHeader.docdate
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,postinggroups.debtorsaccount
      ,inventorypostinggroup.defaultgl_sales
      ,inventorypostinggroup.defaultgl_vat
      ,SalesLine.invoiceamount
      ,IFNULL(SalesLine.vatamount,0) as vatamount
      ,SalesHeader.currencycode
      ,currencies.rate
      ,SalesHeader.customercode
      ,(rtrim(SalesLine.description))  as narration
      ,'".$_POST['DimensionOne']."' 
      ,'".$_POST['DimensionTwo']."'
      from SalesHeader join postinggroups on SalesHeader.postinggroup=postinggroups.code
      join SalesLine on SalesLine.documentno = SalesHeader.documentno 
      join stockmaster on SalesLine.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on SalesHeader.currencycode=currencies.currabrev
      where SalesHeader.documentno='%s'
      AND inventorypostinggroup.defaultgl_sales IS NOT NULL
      AND inventorypostinggroup.defaultgl_vat IS NOT NULL
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
      ,cutomercode
      ,narration)
      (select
       '%s'
      ,SalesHeader.docdate
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,inventorypostinggroup.defaultgl_sales
      ,inventorypostinggroup.defaultgl_vat
      ,''
      ,SalesLine.vatamount
      ,0
      ,SalesHeader.currencycode
      ,currencies.rate
      ,SalesHeader.customercode
      ,(rtrim(SalesLine.description))  as narration
      from SalesHeader join postinggroups on SalesHeader.postinggroup=postinggroups.code
      join SalesLine on SalesLine.documentno = SalesHeader.documentno 
      join stockmaster on SalesLine.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on SalesHeader.currencycode=currencies.currabrev
      where SalesHeader.documentno='%s'
      AND inventorypostinggroup.defaultgl_sales IS NOT NULL
      AND inventorypostinggroup.defaultgl_vat IS NOT NULL
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
              SalesHeader.docdate
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,sum(SalesLine.invoiceamount)
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."' 
             ,SalesHeader.currencycode    
             from SalesHeader
             join SalesLine 
          on SalesLine.documentno = SalesHeader.documentno 
           where SalesHeader.documentno='%s' 
           Group by 
             SalesHeader.docdate
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,SalesHeader.currencycode
             ) ",
            $journal,$doc);
    
    return $sql;
}

function COGSPostGL($period,$journal,$doc){
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
      ,SalesHeader.docdate
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,inventorypostinggroup.CostOfSales
      ,inventorypostinggroup.balancesheet
      ,''
      ,IFNULL(stockmaster.averagestock,0) * SalesLine.Quantity
      ,0
      ,SalesHeader.currencycode
      ,currencies.rate
      ,SalesHeader.customercode
      ,rtrim(SalesLine.description) as narration
      ,'".$_POST['DimensionOne']."'
      ,'".$_POST['DimensionTwo']."'
      from SalesHeader 
      join postinggroups on SalesHeader.postinggroup=postinggroups.code
      join SalesLine on SalesLine.documentno = SalesHeader.documentno
      join stockmaster on SalesLine.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on SalesHeader.currencycode=currencies.currabrev
      where SalesHeader.documentno='%s'
      )",$journal,$period,$doc);

    return $sql;

}

?>
