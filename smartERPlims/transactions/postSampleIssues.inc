<?php
if(($_SESSION['ManualNumber']==0)){
$_POST['documentno'] = GetNextTransNo(15,$db);
}
$_SESSION['DocumentNo'] = $_POST['documentno'];
$_SESSION['invoicerrors']=0;


foreach ($_SESSION['sales_orders'] as $key => $rowvalue) {
 
    $location     = $rowvalue['location'] ;
    $stcode       = $rowvalue['itemcode'] ;
    $qty          = $rowvalue['quantity']   ;
    $balancevalue = $rowvalue['stkbalance']   ;
    
    if($qty>0){
        if(($balancevalue)<0){
           $_SESSION['invoicerrors']++;
        }
    }
     
}

if($_SESSION['invoicerrors']>0){
   prnMsg('You do not have enough stock units to invoice','warn');
}
 
if($_SESSION['invoicerrors']==0){
$sqldate    = FormatDateForSQL($_POST['date']);
$PeriodNo    = GetPeriod($_POST['date'],$db,true);
$TransBuffer = DB_Txn_Begin($db);
 
$sql=array();
    

$sql[]="INSERT INTO SalesHeader
           (documenttype
           ,documentno
           ,docdate
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
           ('15'
           ,'".$_POST['documentno']."'
           ,'".$sqldate."'
           ,'".$_POST['CustomerID']."'
           ,'".$_POST['CustomerName']."'
           ,'".$_POST['reference']."'
           ,'".$_POST['locationcode']."'
           ,' '
           ,'".$_POST['currencycode']."'
           ,'".$_POST['salespersoncode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'0')" ;

    
    foreach ($_SESSION['sales_orders'] as $key => $rowvalue) {
             if($rowvalue['quantity']>0){
                 
           
                 
$ResultIndex = DB_query("SELECT 
        stockmaster.barcode,
        stockmaster.itemcode,
        stockmaster.descrip as stockname,
        stockmaster.averagestock,
        vatcategory.vat,
        c.itemcode as container,
        c.descrip as packname,
        IFNULL(cv.vat,0) as CVAT
  FROM stockmaster 
  left join stockmaster c on stockmaster.container=c.itemcode
  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
  left join inventorypostinggroup ci on c.postinggroup=ci.code
  left join vatcategory cv on ci.vatcategory=cv.vatc
  where  stockmaster.itemcode='".$key."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   $stockname = $stkmaster[2];
   $vatrate = $stkmaster[4];       
   $container = $stkmaster[5]; 
   $containername = $stkmaster[6]; 
   $containervat  = $stkmaster[7]; 
   
    $location    = $rowvalue['location'];
    $salesoderno = $_SESSION['DocumentNo'];
    $UnitPrice   = $rowvalue['salesprice'];
    $grossamount = $rowvalue['grossamount'];
    $stockcode   = trim($rowvalue['itemcode']);
    $UnitCode    = trim($rowvalue['uom']);
    $Inpacks     = $_SESSION['units'][$UnitCode]['descrip'];
               
    $sql[] =  sprintf("INSERT INTO SalesLine
           (documenttype,docdate ,documentno,code,description ,unitofmeasure
           ,Quantity ,UnitPrice ,vatamount,invoiceamount,vatrate,inclusive
           ,containerprice,containersunits,totalchargedcontainers,containercode,PartPerUnit)
     VALUES
           ('%s','%s','%s','%s','%s','%s','%s',%f ,%f ,%f,'%s','%s',%f ,%f,%f,'%s','%s')"
            ,"15"
           ,$sqldate
           ,$_POST['documentno']
           ,$stockcode
           ,$stockname
           ,$Inpacks
           ,$rowvalue['quantity']
           ,$UnitPrice
           ,$rowvalue['vatamount']
           ,$grossamount
           ,$vatrate
           ,$rowvalue['VATinclusive']
           ,0
           ,0
           ,0
           ,$container
           ,$rowvalue['partsperunit']);
             }
    }
   
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
       
    } else {
        DB_Txn_Commit($db);
    }
    
    

}

?>