<?php
 
$_SESSION['invoicerrors']=0;

foreach ($_SESSION['PE_orders'] as $key => $rowvalue) {
    $balancevalue = $rowvalue['stkbalance']  ;
    
    if(($balancevalue)<0){
        $_SESSION['invoicerrors']++;
    }
     
}

if($_SESSION['invoicerrors']>0){
   prnMsg('You do not have enough stock units to invoice','warn');
}
 

if( $_SESSION['invoicerrors']==0){
$_POST['documentno'] = GetNextTransNo(40,$db);
$_SESSION['DocumentNo']=$_POST['documentno'] ;
$PeriodNo    = GetPeriod($_POST['date'],$db,true);
$TransBuffer = DB_Txn_Begin($db);
$journal     = GetNextTransNo(0,$db);
$sqldate     = FormatDateForSQL($_POST['date']);
 
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
           ,vatinclusive
           ,Dimension_1
           ,Dimension_2)
     VALUES
           ('40'
           ,'".$_POST['documentno']."'
           ,'".FormatDateForSQL($_POST['date'])."'
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
           ,'0'
           ,'".$_POST['DimensionOne']."'
           ,'".$_POST['DimensionTwo']."')" ;

    
  foreach ($_SESSION['PE_orders'] as $key => $rowvalue) {
      
      if($rowvalue['quantity']>0){
    
    $stkmaster     = MyStockData($rowvalue['itemcode']);
    $stockname     = trim($stkmaster[2]);
    $si            = $stkmaster[3];
    $containercode = $stkmaster[7];
    $averagestock  =(float) $stkmaster[8];
    
    $UnitPrice   = (float) $rowvalue['averagestock'];
    $grossamount = (float) $rowvalue['netamount'];
    $location    = $_POST['location'][$key];
    $location2   = $_POST['TankStore'][$key];
    $stcode      = $rowvalue['itemcode'];
    $salesoderno = $_SESSION['DocumentNo'];
    $QTY         = (float)($rowvalue['packSize']*$rowvalue['quantity']);
    
    $sql[] =  sprintf("INSERT INTO SalesLine
           (documenttype,docdate,documentno,code,description,unitofmeasure
           ,Quantity,UnitPrice,vatamount,invoiceamount,vatrate,inclusive
           ,containerprice,containersunits,totalchargedcontainers,containercode,PartPerUnit)
     VALUES
           ('%s','%s','%s','%s','%s','%s','%s',%f ,0,%f ,0,0,%f,%f,%f,'%s',%f)"
            ,"40" ,$sqldate ,$_POST['documentno'] ,$stcode ,(' Deduct :'.$stockname) ,$si ,($rowvalue['quantity']*-1),$UnitPrice
           ,$grossamount,0,$rowvalue['quantity'],0,$containercode, $rowvalue['packSize']);
  
    if(mb_strlen($location)>0){
     $sql[]="INSERT INTO stockledger
        (date,stname,doctyp,itemcode,invref,netamt,vat,amount,fulqty,loosqty,price,store,journal,period,jobcard,dimension,PartPerUnit)
        values ('".$sqldate."','".$stockname."','40','".$stcode."','".$salesoderno ."','".$grossamount."',0,'".$grossamount."',0 ,".($QTY*-1)."  
           ,'".$UnitPrice."','".$location."' ,'".$journal."','".$PeriodNo."','".$salesoderno ."','".$_POST['DimensionTwo']."',1)";
    }
     $mypost=array('PartPerUnit'=>1,'unitofmeasure'=>$si, 'documentno'=>$_POST['documentno'],'docdate'=>$sqldate,
        'code'=>$stcode,'description'=>$stockname ,'unitofmeasure'=>$si,'Quantity'=>$QTY ,'UnitPrice'=>$UnitPrice ,'invoiceamount'=>$grossamount);
    
        GetFIFOissues($journal,$PeriodNo,$salesoderno,$stcode,$value,$mypost); 
  
    
    $stkmaster = MyStockData($rowvalue['itemcodeTwo']);
    $stockname = trim($stkmaster[2]);
    $si        = $stkmaster[3];
    $location2   = $_POST['TankStore'][$key];
    $stcode      = $rowvalue['itemcodeTwo'];
    $salesoderno = $_SESSION['DocumentNo'];
    
     $sql[] =  sprintf("INSERT INTO SalesLine
           (documenttype,docdate,documentno,code,description,unitofmeasure
           ,Quantity,UnitPrice,vatamount,invoiceamount,vatrate,inclusive
           ,containerprice,containersunits,totalchargedcontainers,containercode,PartPerUnit)
     VALUES
           ('%s','%s','%s','%s','%s','%s','%s',%f ,0,%f ,0,0,%f,%f,%f,'%s',%f)"
            ,"40" ,$sqldate ,$_POST['documentno'] ,$stcode ,' ADD :'.$stockname ,$si ,
             ($QTY),$UnitPrice,$grossamount,0,$rowvalue['quantity'],0,$containercode, 1);
     
      
      $sql[]=Sprintf("UPDATE stockmaster SET averagestock=%f WHERE itemcode='%s'",$UnitPrice,$stcode);
    
     if(mb_strlen($location2)>0){
   
            $sql[] = sprintf("INSERT INTO tanktrans (tankname,units,uom,date,batchno,doctype,itemcode)
            VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',40,'%s')",$location2,($QTY),'loosqty',$sqldate,$salesoderno,$stcode);

     }                
        $mypost=array('PartPerUnit'=>1,'unitofmeasure'=>$si, 'documentno'=>$_POST['documentno'],'docdate'=>$sqldate,
        'code'=>$stcode,'description'=>$stockname ,'unitofmeasure'=>$si,'Quantity'=>$QTY ,'UnitPrice'=>$UnitPrice ,'invoiceamount'=>$grossamount);
    
        GetFIFOissues($journal,$PeriodNo,$salesoderno,$stcode,$value,$mypost); 
  /// keep Track of empties  
     
    $stkmaster = MyStockData($rowvalue['container']);
    $stcode    = $stkmaster[1];
    $stockname = trim($stkmaster[2]);
    $si        = $stkmaster[3];
    
     $sql[] =  sprintf("INSERT INTO SalesLine
           (documenttype,docdate,documentno,code,description,unitofmeasure
           ,Quantity,UnitPrice,vatamount,invoiceamount,vatrate,inclusive
           ,containerprice,containersunits,totalchargedcontainers,containercode,PartPerUnit)
     VALUES
           ('%s','%s','%s','%s','%s','%s','%s',%f ,0,%f ,0,0,%f,%f,%f,'%s',%f)"
            ,"40" ,$sqldate ,$_POST['documentno'] ,$stcode ,' ADD :'.$stockname ,$si ,
             $rowvalue['quantity'],$UnitPrice,$grossamount,0,$rowvalue['quantity'],0,'', 1);
    
    $sql[]="INSERT INTO stockledger
        (date,stname,doctyp,itemcode,invref,netamt,vat,amount,fulqty,loosqty,price,store,journal,period,jobcard,dimension,PartPerUnit)
        values ('".$sqldate."','".$stockname."','40','".$stcode."','".$salesoderno ."','".$grossamount."',0,'".$grossamount."',0 ,".$rowvalue['quantity']."  
           ,'".$UnitPrice."','".$location."' ,'".$journal."','".$PeriodNo."','".$salesoderno ."','".$_POST['DimensionTwo']."',1)";
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
      
        DB_query("UPDATE SalesHeader SET released=1 WHERE documenttype='40' and documentno='".$_POST['documentno']."'",$db);
        prnMsg(sprintf('<a href="PDFPrintRequisition.php?No=%s">Print Document:%s</a>',$_POST['documentno'],$_POST['documentno']),'info');
    }
    
}
 

function MyStockData($itemcode){
    global $db;
     $ResultIndex = DB_query("SELECT
        stockmaster.barcode,
        stockmaster.itemcode,
        stockmaster.descrip,
        unit.descrip,
        stockmaster.averagestock,
        vatcategory.vat,
        stockmaster.container,
        c.averagestock
  FROM stockmaster
  LEFT JOIN stockmaster c ON stockmaster.container=c.itemcode
  LEFT JOIN unit ON stockmaster.units=unit.code
  LEFT JOIN inventorypostinggroup ON stockmaster.postinggroup=inventorypostinggroup.code
  LEFT JOIN vatcategory ON inventorypostinggroup.vatcategory=vatcategory.vatc
  WHERE stockmaster.itemcode='".$itemcode."'", $db);
     
    $stkmaster = DB_fetch_row($ResultIndex);
    return $stkmaster;
}


function updatemaster($itemcode,$cost){
     global $db;
     
$SQL=Sprintf("UPDATE stockmaster SET averagestock=%f WHERE itemcode='%s'",$cost,$itemcode);
 $RESULTS  = DB_System($SQL,$db);
}
?>
