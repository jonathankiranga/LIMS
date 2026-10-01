<?php
$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;

foreach ($_POST['Toreceive'] as $key => $value) {
    $location = $_POST['location'][$key];
    $stcode   = $_POST['code'][$key] ;
    $qty      =(float) $_POST['Toreceive'][$key] ;
    $dbbalance =(float) $_POST['balance'][$key];
   
    if($qty > 0){
        $_SESSION['invoicerrors']++;
    }
       
    if($dbbalance < 0){
        $_SESSION['somedetails']++;
    } 
       
}
   
if($_SESSION['somedetails'] > 0) {
        prnMsg('You cannot Receive more than was ordered','warn');
}
    
 if($_SESSION['invoicerrors'] == 0){
       prnMsg('You have not entred stock units to receive','warn');
}   
    
if($_SESSION['DocumentPicking']==true){
      prnMsg('Create a new GRN no','warn');
}

if($_SESSION['somedetails'] == 0 AND $_SESSION['DocumentPicking']==false){
    
    $ResultIndex = DB_query('SELECT NOW() AS date',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
    $_serialdate = $rowdate[0];
       
    $sqldebtors=DB_query("SELECT itemcode,customer,phone,
    email,city,country,curr_cod,supplierposting,VATinclusive
    FROM creditors join arpostinggroups on code=supplierposting
    where itemcode='".$_POST['VendorID']."'", $db);
    
    $debtorsrow = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[7];
    $VATinclusive    = $debtorsrow[8];
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,true);
    $TransBuffer     = DB_Txn_Begin($db);
    
     if(($_SESSION['ManualNumber']==0)){
     $INVOICENO= GetNextTransNo(30,$db);
     }else{
      $INVOICENO=$_POST['manualdocumentno'];
     }
    
    $journal = GetNextTransNo(0,$db);
    
    $sql=array();
    
    RecordGRN($INVOICENO);
    
    foreach ($_POST['Toreceive'] as $key => $value) {
         if($value>0){
             
               $containercode = $_POST['containercode'][$key];
               $partperunit   = $_POST['partperunit'][$key];
               $location      = $_POST['location'][$key];
               $stcode        = $_POST['code'][$key];
               $qty           = $_POST['Toreceive'][$key];       
               $salesoderno   = $_SESSION['DocumentNo'];
               
            
               $sql[]= sprintf("UPDATE PurchaseLine SET
                    Qunatity_delivered= IFNULL(Qunatity_delivered,0)+ %f,
                    Quantity_toinvoice= QuantityToReceive-Qunatity_delivered
                    WHERE documentno='%s' AND documenttype='%s'
                    AND entryno=%s",$qty,$salesoderno,18,$key);
               
               
                $sql[]="INSERT INTO stockledger
                    (date
                    ,stname
                    ,batch
                    ,doctyp
                    ,itemcode
                    ,invref
                    ,netamt
                    ,vat
                    ,amount
                    ,fulqty
                    ,loosqty
                    ,price
                    ,store
                    ,journal
                    ,period
                    ,jobcard
                    ,PartPerUnit)
                    (SELECT
                        PurchaseLine.docdate
                       ,LEFT(PurchaseLine.description,30)
                       ,'".$key."'
                       ,'30'
                       ,PurchaseLine.code
                       ,PurchaseLine.documentno
                       ,PurchaseLine.invoiceamount
                       ,PurchaseLine.vatamount
                       ,PurchaseLine.invoiceamount
                       ,IF(PurchaseLine.partperunit>1, ".$qty."  , 0 ) as fulqty
                       ,IF(PurchaseLine.partperunit=1, ".$qty."  , 0 ) as loosqty
                       ,PurchaseLine.UnitPrice
                       ,'".$location."'
                       ,'".$journal."'
                       ,'".$PeriodNo."'
                       ,'".$INVOICENO ."' 
                       ,PurchaseLine.partperunit
                       FROM PurchaseLine
                       where documentno='".$salesoderno."' 
                         and code='".$stcode."' 
                         and entryno='".$key."' )";
        
                $results=PostStockRegister($qty,$key,$journal,$PeriodNo);
              
         }
    }
    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
      
    } 
    else {
        DB_Txn_Commit($db);
        $serialLink = '<a href="PDFPrintSerialNumbers.php?No='.$INVOICENO.'">' . _('Print Serial Numbers') . '</a>';
        prnMsg('This GRN document '.$INVOICENO.' has been created'.'<a href="PDFPrintgrnNONStock.php?No='.$INVOICENO.'">GRN Reports</a> | ' . $serialLink);
         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);

    }
    
}




Function RecordGRN($DOCNO){
 Global $db,$sql;
 
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
      (select '30'
           ,'".$DOCNO."'
           ,'".FormatDateForSQL($_POST['date'])."'
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
           ,freight from PurchaseHeader where documentno='".$_POST['documentno']."')" ;



            foreach ($_POST['Toreceive'] as $key => $value) {

            if($value>0){

                $itemcode = $_POST['code'][$key];
      
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
                       ,partperunit)
                       (select 
                       30,
                        docdate
                       ,'".$DOCNO."'
                       ,code
                       ,description
                       ,unitofreceivedIn
                       ,%f
                       ,((UnitPrice * Quantity) / QuantityToReceive)
                       ,vatamount
                       ,invoiceamount
                       ,vatrate
                       ,inclusive
                       ,partperunit
                       from PurchaseLine where documentno='".$_POST['documentno']."' and code='".$itemcode."')"
                       ,$value);
                    }


            }

            
Return $sql;

}

?>
