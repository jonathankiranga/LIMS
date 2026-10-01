<?php
             
$_SESSION['invoicerrors']=0;

if($_POST['transactiontype']=='1'){
    foreach ($_SESSION['PE_orders'] as $key => $rowvalue) {
        $location = $rowvalue['location'];
        $stcode   = $rowvalue['itemcode'] ;
        $qty      = $rowvalue['quantity'] ;
        $balancevalue=(($rowvalue['charge']==1)?10000: $rowvalue['stkbalance']);
        if(($balancevalue)<0){
           $_SESSION['invoicerrors']++;
        }
    }
}

if($_SESSION['invoicerrors']>0){
   prnMsg('You do not have enough stock units to invoice','warn');
}
 
if($_SESSION['invoicerrors']==0){
    if($_POST['transactiontype']=='1'){
    $_POST['documentno']=GetNextTransNo(1,$db);
    }else{
    $_POST['documentno']=GetNextTransNo(40,$db);
    }
 $_SESSION['DocumentNo']=$_POST['documentno'] ;
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
           ,externaldocumentno)
     VALUES
           ('40'
           ,'".$_SESSION['DocumentNo']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ,'".$_POST['CustomerID']."'
           ,'".$_POST['CustomerName']."'
           ,'".$_POST['transactiontype']."'
           ,'".$_POST['locationcode']."'
           ,'".$_POST['CustomerID']."'
           ,'".$_POST['currencycode']."'
           ,'".$_POST['salespersoncode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'0' 
           ,'".$_POST['batchno']."')" ;

    /*
  array('tankstore'=>$this->tankStore,'netamount'=>$netamount,
                    'vatamount'=>$vatamount,'grossamount'=>$grossamount,
     *      */
    foreach ($_SESSION['PE_orders'] as $key => $rowvalue) {
        $checkamount=(float) $rowvalue['quantity'];
      if($checkamount>0){
      
            $ResultIndex = DB_query("SELECT 
                    stockmaster.barcode,
                    stockmaster.itemcode,
                    stockmaster.descrip as stockname
              FROM stockmaster 
              where stockmaster.itemcode='".$rowvalue['itemcode']."'", $db);

               $stkmaster = DB_fetch_row($ResultIndex);
               $stockname = $stkmaster[2];
              
                $si           = trim($rowvalue['uom']);
                 $Inpacks     = $_SESSION['units'][$si]['descrip'];
                $UnitPrice    = (float) $rowvalue['averagestock'];
                $location     = $_POST['location'][$key];
                $stcode       = trim($rowvalue['itemcode']);
                $salesoderno  = $_SESSION['DocumentNo'];
                $sqldate      = FormatDateForSQL($_POST['date']);
                $netamount    =(float) $rowvalue['netamount'];
                $grossamount  =(float) $rowvalue['grossamount'];
                $vatamount    =(float) $rowvalue['vatamount'];
                $rate         =(float) $rowvalue['vat'];
                
                $sql[] =  sprintf("INSERT INTO SalesLine
                       (documenttype,docdate ,documentno,code,description ,unitofmeasure
                       ,Quantity ,Qunatity_delivered,UnitPrice ,vatamount,invoiceamount,vatrate,inclusive
                       ,containerprice,containersunits,totalchargedcontainers,containercode,PartPerUnit)
                 VALUES
                       ('%s','%s','%s','%s','%s','%s','%s','%s',%f ,%f,%f ,'%s',0,0,0 ,0,'%s',1)"
                        ,"40" ,$sqldate ,$_SESSION['DocumentNo'] ,$stcode ,$stockname ,$Inpacks
                       ,$rowvalue['quantity'],$rowvalue['quantity'],$UnitPrice,$vatamount,$grossamount,$rate ,$_POST['transactiontype']);

                        $mypost=array('PartPerUnit'=>1,'unitofmeasure'=>$si,'documentno'=>$_SESSION['DocumentNo'],'docdate'=>$sqldate,
                         'code'=>$stcode,'description'=>$stockname ,'unitofmeasure'=>$si,'Quantity'=>$value ,'UnitPrice'=>$UnitPrice ,'invoiceamount'=>$grossamount);


                        if($_POST['transactiontype']=='1'){

                                if($rowvalue['tankstore']=="S"){
                                    $sql[]="INSERT INTO stockledger (date,stname,doctyp,itemcode,invref,netamt,vat,amount,fulqty
                                        ,loosqty,price,store,journal,period,jobcard,PartPerUnit)
                                        (SELECT SalesLine.docdate ,substring(SalesLine.description,0,30)
                                           ,'40',SalesLine.code ,SalesLine.documentno,SalesLine.invoiceamount ,SalesLine.vatamount,SalesLine.invoiceamount
                                           , 0 as fulqty,".(0-$rowvalue['quantity'])." as loosqty,(SalesLine.UnitPrice/SalesLine.partperunit)
                                           ,'".$location."' ,'".$journal."' ,'".$PeriodNo."' ,'".$_SESSION['DocumentNo'] ."' ,1 
                                         FROM SalesLine 
                                         where documentno='".$salesoderno."' and code='".$stcode."' )";
                                    
                                     GetFIFO($journal,$PeriodNo,$_SESSION['DocumentNo'],$stcode,$value,$mypost);
                                }
                                    
                                if($rowvalue['tankstore']=="T"){
                                         $sql[] = sprintf("INSERT INTO tanktrans (tankname,units,uom,date,batchno,doctype,itemcode)
                                         VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',40,'%s')", $location,(0-$rowvalue['quantity']),'loosqty',$sqldate,$_SESSION['DocumentNo'],$stcode);
                                  GetFIFO($journal,$PeriodNo,$_SESSION['DocumentNo'],$stcode,$value,$mypost);
                                }

                           
                        }

                        if($_POST['transactiontype']=='2'){

                    if($rowvalue['tankstore']=="S"){
                      $sql[]="INSERT INTO stockledger (date,stname,doctyp,itemcode,invref,netamt,vat,amount,fulqty
                          ,loosqty,price,store,journal,period,jobcard,PartPerUnit)
                          (SELECT SalesLine.docdate ,substring(SalesLine.description,0,30)
                             ,'40',SalesLine.code ,SalesLine.documentno,SalesLine.invoiceamount ,SalesLine.vatamount,SalesLine.invoiceamount
                             , 0 as fulqty,".($rowvalue['quantity'])." as loosqty,(SalesLine.UnitPrice/SalesLine.partperunit)
                             ,'".$location."' ,'".$journal."' ,'".$PeriodNo."' ,'".$_SESSION['DocumentNo']."' ,1 
                           FROM SalesLine 
                           where documentno='".$salesoderno."' and code='".$stcode."' )";
                  }elseif($rowvalue['tankstore']=="T"){
                           $sql[] = sprintf("INSERT INTO tanktrans (tankname,units,uom,date,batchno,doctype,itemcode)
                           VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',40,'%s')", $location,($rowvalue['quantity']),'loosqty',$sqldate,$_SESSION['DocumentNo'],$stcode);

                  }

                GetFIFOissues($journal,$PeriodNo,$_SESSION['DocumentNo'],$stcode,$value,$mypost); 
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
        
        DB_query("UPDATE SalesHeader SET status = 2 where documentno='".$_SESSION['DocumentNo']."'", $db);
       
        prnMsg('This Request document '.$_SESSION['DocumentNo'].' has been created .');
    }
    
    

}




?>  