<?php
$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['IssuedSerials'] = array();


foreach ($_SESSION['PE_orders'] as $key => $rowvalue) {
     
    $location = $rowvalue['location'];
    $stcode = $rowvalue['itemcode'];
    $qty = $rowvalue['quantity']  ;
    $balancevalue= $rowvalue['stkbalance']  ;
    
    if(($balancevalue)<0){
           $_SESSION['invoicerrors']++;
    }
     
}



if($_SESSION['invoicerrors']>0){
   prnMsg('You do not have enough stock units to invoice','warn');
}
 

if( $_SESSION['invoicerrors']==0){
                    
    $PeriodNo    = GetPeriod($_POST['date'],$db,true);
    $TransBuffer = DB_Txn_Begin($db);
    $journal     = GetNextTransNo(0,$db);
    $sql=array();
    
  foreach ($_SESSION['PE_orders'] as $key => $rowvalue) {
             if($rowvalue['quantity']>0){
                 
$ResultIndex = DB_query("SELECT 
        stockmaster.barcode,
        stockmaster.itemcode,
        stockmaster.descrip as stockname,
        unit.descrip as si,
        stockmaster.averagestock,
        stockmaster.partperunit,
        vatcategory.vat 
  FROM stockmaster 
  left join stockmaster c on stockmaster.container=c.itemcode
  left join unit on stockmaster.units=unit.code
  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
  where  stockmaster.itemcode='".$rowvalue['itemcode']."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   $stockname = $stkmaster[2];
   $si        = $stkmaster[3];
    
   
    $UnitPrice = $rowvalue['averagestock'];
    $grossamount = $rowvalue['netamount'];
    $location = $_POST['location'][$key];
    $stcode = $rowvalue['itemcode'];
    $salesoderno = $_SESSION['DocumentNo'];
    $sqldate = FormatDateForSQL($_POST['date']);
     
               
    $sql[]="INSERT INTO stockledger (date,stname,doctyp,itemcode,invref,netamt,vat,amount
        ,fulqty,loosqty,price,store,journal,period,jobcard,dimension,PartPerUnit) 
        values ('".FormatDateForSQL($_POST['date'])."','".$stockname."','40','".$stcode."','".$salesoderno ."','".$grossamount."',0,'".$grossamount."',0 ,".($rowvalue['quantity']*-1)."  
           ,'".$UnitPrice."','".$location."' ,'".$journal."','".$PeriodNo."','".$salesoderno ."','".$_POST['DimensionTwo']."',1)";
        
$mypost=array('PartPerUnit'=>1,'unitofmeasure'=>$si,
         'documentno'=>$_POST['documentno'],'docdate'=>FormatDateForSQL($_POST['date']),
         'code'=>$stcode,'description'=>$stockname ,'unitofmeasure'=>$si,
         'Quantity'=>$rowvalue['quantity'] ,'UnitPrice'=>$UnitPrice ,'invoiceamount'=>$grossamount);
     
          $batchrowid = isset($_POST['batchrowid'][$key]) ? (int)$_POST['batchrowid'][$key] : 0;
          GetFIFOissues($journal,$PeriodNo,$salesoderno,$stcode,$rowvalue['quantity'],$mypost,$batchrowid);
        
          if (!empty($rowvalue['serial'])) {
              $_SESSION['IssuedSerials'][] = $rowvalue['serial'];
          }
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
      
        DB_query("update SalesHeader set released=1  where documenttype='40' and documentno='".$_POST['documentno']."'",$db);
        if (!empty($_SESSION['IssuedSerials'])) {
            $_SESSION['DocumentSerials'][$_POST['documentno']] = $_SESSION['IssuedSerials'];
        }
        $msg = sprintf('<a href="PDFPrintRequisition.php?No=%s">Print Document:%s</a>',$_POST['documentno'],$_POST['documentno']);
        if (!empty($_SESSION['IssuedSerials'])) {
            $serialList = implode(', ', $_SESSION['IssuedSerials']);
            $msg .= '<br/><strong>' . _('Issued Serials') . ':</strong> ' . $serialList;
            $_SESSION['IssuedSerials'] = array();
        }
        prnMsg($msg, 'info');
    }
    
}
 
?>