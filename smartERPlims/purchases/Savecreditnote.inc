<?php
$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;

if($_SESSION['DocumentPicking']==true){
      prnMsg('Create a new Debit Note','warn');
}

if($_SESSION['DocumentPicking']==false){
    
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
       
    $sqldebtors=DB_query("SELECT itemcode,customer,phone,
    email,city,country,curr_cod,supplierposting,VATinclusive
    FROM creditors join arpostinggroups on code=supplierposting 
    where itemcode='".$_POST['VendorID']."'", $db);
    
    $debtorsrow = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[7];
    $VATinclusive    = $debtorsrow[8];
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,true);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = GetNextTransNo(21,$db);
    $journal         = GetNextTransNo(0,$db);
    
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
      21
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
       ,'".FormatDateForSQL($_POST['posingdate'])."'
       ,'".$_POST['VendorID']."'
       ,'".$_POST['VendorName']."'
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
    $salesoderno= $_SESSION['DocumentNo'];
    $partperunit = $_POST['partperunit'][$key];

                 
    if($partperunit>1){
         $fuqty=$qty;
         $losqty=0;
    }else{
         $losqty=$qty;
         $fuqty=0;
    }
            
 $sql[]= sprintf("Update PurchaseLine set
      Qunatity_delivered= IFNULL(Qunatity_delivered,0)- %f,
      Quantity_toinvoice= Quantity-Qunatity_delivered
      where documentno='%s' and documenttype='%s' 
      and entryno=%s",$qty,$salesoderno,'20',$key);
              

    $sql[] =sprintf("INSERT INTO PurchaseLine
    (documenttype,docdate,documentno,code,description,unitofmeasure,Quantity,UnitPrice,vatamount
    ,invoiceamount,vatrate,inclusive,locationcode,partperunit) 
    (select '%s','%s','%s',code,description,unitofmeasure,%f,%f,%f,%f,vatrate,inclusive,'%s',partperunit 
    from PurchaseLine where PurchaseLine.entryno='%s')",'21',FormatDateForSQL($_POST['date']),$INVOICENO
    ,$_POST['subunits'][$key] ,$_POST['salesprice'][$key] ,$_POST['vatamount'][$key] ,$_POST['grossamount'][$key],$_POST['location'][$key],$key);
              
        

    $sql[]="INSERT stockledger
        (date,stname ,doctyp ,itemcode,invref,netamt,vat 
        ,amount,fulqty,loosqty,price,store,journal,period
        ,jobcard,partperunit)
        (SELECT 
            PurchaseLine.docdate
           ,PurchaseLine.description
           ,'30'
           ,PurchaseLine.code
           ,PurchaseLine.documentno
           ,PurchaseLine.invoiceamount
           ,PurchaseLine.vatamount
           ,PurchaseLine.invoiceamount
           ,".(0-$fuqty)."  as fulqty
           ,".(0-$losqty)."  as loosqty
           ,PurchaseLine.UnitPrice
           ,'".$location."'
           ,'".$journal."'
           ,'".$PeriodNo."'
           ,'".$INVOICENO ."' 
           ,PurchaseLine.partperunit
            FROM PurchaseLine where entryno='".$key."' and code='".$stcode."')";
                
         }
    }
    
   
 
    foreach ($sql as $value) {
       $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
        
    }else{
        DB_Txn_Commit($db);
    
        DB_query("Update PurchaseHeader set status=2 where documentno='".$_SESSION['DocumentNo']."'",$db);
 
        prnMsg('This DEBIT NOTE document '.$INVOICENO.' has been created'.'<a href="PDFPrintdebitnote.php?No='.$INVOICENO.'">DEBIT note Reports</a>');
         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);
    }
    
    

}
?>