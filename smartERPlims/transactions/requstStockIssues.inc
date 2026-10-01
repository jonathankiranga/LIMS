<?php

$_SESSION['invoicerrors']=0;

foreach ($_SESSION['PE_orders'] as $key => $rowvalue) {
    $location = $rowvalue['location'][$key];
    $stcode = $rowvalue['itemcode'][$key];
    $qty = $rowvalue['quantity'][$key]  ;
    $balancevalue= $rowvalue['stkbalance'][$key]  ;
    
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
                    
$PeriodNo    = GetPeriod($_POST['date'],$db,true);
$TransBuffer = DB_Txn_Begin($db);
$journal     = GetNextTransNo(0,$db);
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
                 
           
$ResultIndex = DB_query("SELECT 
        stockmaster.barcode,
        stockmaster.itemcode,
        stockmaster.descrip as stockname,
        unit.descrip as si
  FROM stockmaster 
  left join unit on stockmaster.units=unit.code
  where  stockmaster.itemcode='".$rowvalue['itemcode']."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   $stockname = $stkmaster[2];
   $si = $stkmaster[3];
     
    $stcode = $rowvalue['itemcode'];
    $salesoderno = $_SESSION['DocumentNo'];
    $sqldate = FormatDateForSQL($_POST['date']);
    
    $UnitPrice = $rowvalue['averagestock'];
    $grossamount = $rowvalue['netamount'];
               
               
    $sql[] =  sprintf("INSERT INTO SalesLine
           (documenttype,docdate ,documentno,code,description ,unitofmeasure
           ,Quantity ,UnitPrice ,vatamount,invoiceamount,vatrate,inclusive
           ,containerprice,containersunits,totalchargedcontainers ,containercode,PartPerUnit)
     VALUES
           ('%s','%s','%s','%s','%s','%s','%s',%f ,%f,%f ,0,0,0,0 ,0,'',1)"
            ,"40"
           ,$sqldate
           ,$_POST['documentno']
           ,$stcode
           ,$stockname
           ,$si
           ,$rowvalue['quantity']
           ,$UnitPrice
           ,0
           ,$grossamount);
        
                
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
        prnMsg('This Request document '.$salesoderno.' has been created . Wait  for approval');
        unset($_POST);

    }
    
    

}

?>