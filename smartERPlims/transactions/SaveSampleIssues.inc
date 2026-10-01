<?php
$_SESSION['DocumentNo']=$_POST['documentno'] ;

 

if($_SESSION['invoicerrors']==0){
                    
    $PeriodNo    = GetPeriod($_POST['date'],$db,true);
    $sqldate     = FormatDateForSQL($_POST['date']);
    $TransBuffer = DB_Txn_Begin($db);
    $journal     = GetNextTransNo(0,$db);
    $sql=array();
    
    
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
   $vatrate= $stkmaster[4];       
   $container=$stkmaster[5]; 
   $containername=$stkmaster[6]; 
   $containervat=$stkmaster[7]; 
     
    $location    = $rowvalue['location'];
    $salesoderno = $_SESSION['DocumentNo'];
    $UnitPrice   = $rowvalue['salesprice'];
    $grossamount = $rowvalue['grossamount'];
    $stockcode   = trim($rows['itemcode']);
    $UnitCode    = trim($rows['uom']);
    $Inpacks     = $_SESSION['units'][$UnitCode]['descrip'];
    $quantity    = $rowvalue['quantity'];
               
    if($rowvalue['tankStore']=="S"){
        $sql[]="INSERT INTO stockledger (date,stname,doctyp,itemcode,invref,netamt,vat,amount,fulqty,loosqty,price,store,journal,period,jobcard,dimension,PartPerUnit) 
        values ('".$sqldate."','".$stockname."','15','".$stcode."','".$salesoderno ."','".$grossamount."',0,'".$grossamount."',".($rowvalue['partsperunit']>1?(0-$quantity):0)." ,".($rowvalue['partsperunit']==1?(0-$quantity):0)."  
           ,'".$UnitPrice."','".$location."' ,'".$journal."','".$PeriodNo."','".$salesoderno ."','".$_POST['DimensionTwo']."','".$rowvalue['partsperunit']."')";

    }elseif($rowvalue['tankStore']=="T"){
        $sql[] = sprintf("INSERT INTO tanktrans (tankname,units,uom,date,batchno,doctype,itemcode)
        VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',15,'%s')", $location,(0-$quantity),'loosqty',$sqldate,$salesoderno,$stcode);

    }
               
   
        $mypost=array('PartPerUnit'=>$rowvalue['partsperunit'],
            'unitofmeasure'=>$Inpacks,'documentno'=>$salesoderno,
            'docdate'=>$sqldate,'code'=>$stcode,'description'=>$stockname ,
            'Quantity'=>$quantity,'UnitPrice'=>$UnitPrice ,'invoiceamount'=>$grossamount);
    
        GetFIFOissues($journal,$PeriodNo,$salesoderno,$stcode,$value,$mypost);  
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
      
        DB_query("update SalesHeader set released=1  where documenttype='15' and documentno='".$_POST['documentno']."'",$db);
        prnMsg(sprintf('<a href="PDFPrintSampleRequisition.php?No=%s">Print Document:%s</a>',$_POST['documentno'],$_POST['documentno']),'info');
        
         unset($_SESSION['sales_orders']);
         $_SESSION['DocumentNo']=true;
        unset($_POST);
   }
    
}

 
?>