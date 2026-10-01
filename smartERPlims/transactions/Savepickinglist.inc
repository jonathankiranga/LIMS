<?php
  
$_SESSION['DocumentNo']=$_POST['documentno']; $invoicerrors=0; $somedetails=0;
$_SESSION['cumBalance']=array();
foreach ($_POST['subunits'] as $key => $value) {
   if($value>0){
    $location = $_POST['location'][$key];
    $stcode  = $_POST['code'][$key];
    $qty     =(int) $_POST['subunits'][$key]  ;
    $ordered =(int) $_POST['Quantity_toinvoice'][$key] ;
    $partperunit =(int) $_POST['partperunit'][$key] ;
    $stockbal    =(int) $_POST['stockbal'][$key] ;
    $_SESSION['cumBalance'][$stcode] +=(int) ($stockbal - $qty)  ;
    
    if($qty>0){
        if($_SESSION['cumBalance'][$stcode]<0){
           $invoicerrors++;
        }
    }else{
        $somedetails++;
    }

    if($ordered < 0){
        $somedetails++;
    } 
    
   }
}

if($somedetails>0){
    prnMsg('You cannot dispatch more than was ordered','warn');
}

if($invoicerrors>0){
   prnMsg('You do not have enough stock units to invoice','warn');
}

if($_SESSION['DocumentPicking']==true){
   prnMsg('You cannot post this document more than once','warn');
}

   

if($somedetails==0 AND $_SESSION['DocumentPicking']==false and $invoicerrors==0){
                    
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
    
    $sqldebtors=DB_query("SELECT itemcode,creditlimit,customer,phone,
    email,city,country,curr_cod,customerposting,salesman,VATinclusive
    FROM debtors join postinggroups on code=customerposting 
    where itemcode='".$_POST['CustomerID']."'", $db);
    
    $debtorsrow = DB_fetch_row($sqldebtors);

    $customerposting = $debtorsrow[8];
    $VATinclusive    = $debtorsrow[10];
    $postingDate=FormatDateForSQL($_POST['posingdate']);
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,true);
    $TransBuffer     = DB_Txn_Begin($db);
     if(($_SESSION['ManualNumber']==0)){
        $INVOICENO   = GetNextTransNo(19,$db);
     }else{
        $INVOICENO = $_POST['manualdocumentno'];
    }
    $journal         = GetNextTransNo(0,$db);
    
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
           ,picture)
        (select '19'
           ,'".$INVOICENO."'
           ,'".$postingDate."'
           ,customercode
           ,customername
           ,'".$_SESSION['DocumentNo']."'
           ,locationcode
           ,postinggroup
           ,currencycode
           ,salespersoncode
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,vatinclusive
           ,picture from SalesHeader where documentno='".$_SESSION['DocumentNo']."')" ;
            
    
    foreach ($_POST['code'] as $key => $value) {
           if (isset($_POST['done'][$key])) {

               $stcode = trim($_POST['code'][$key]);
               $qty =(int) $_POST['ordered'][$key];       
               $salesoderno= $_SESSION['DocumentNo'];
               // If the line was marked Done in the picking list, increment delivered qty
           $entryno_s = DB_escape_string($key);
            $sql[] = "UPDATE SalesLine SET Qunatity_delivered = IFNULL(Qunatity_delivered,0) + " . $qty
                . ", Quantity_toinvoice = Quantity - (IFNULL(Qunatity_delivered,0) + " . $qty . ")"
                    . " WHERE entryno='" . $entryno_s . "'";
            
               
                $sql[]= sprintf("Update SalesHeader set
                    released=1 where documentno='%s' and documenttype='%s' ",$salesoderno,1);
                  
               
                $sql[]= sprintf("INSERT INTO SalesLine
                        (documenttype,docdate,documentno ,code ,description
                        ,unitofmeasure ,Quantity,UnitPrice ,vatamount ,invoiceamount
                        ,vatrate ,inclusive,containerprice,containersunits
                        ,totalchargedcontainers ,containercode,PartPerUnit ,PriceInPricelist
                        ,SampleID,TAT)
                        (select '%s','%s','%s',code,description,unitofmeasure,%f,
                        UnitPrice,vatamount,invoiceamount,vatrate,inclusive,containerprice,
                        '%s',totalchargedcontainers,containercode,PartPerUnit,PriceInPricelist
                        ,SampleID,TAT
                        from SalesLine where entryno=%s)" ,"19",$postingDate
                       ,$INVOICENO , $qty,'0',$key);
             
                
                
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
        
 
         $tankClass=new tankClass() ;
         $tankClass->update_tank_balance();
 
 
         echo sprintf('<p class="page_title_text"><a id="'.$INVOICENO.'" href="%s?No=%s" >'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Dispatch Note') . '" alt="" />%s</a></p>',
        'PDFPrintPickingList.php',$INVOICENO, _('Print Dispatch Note ').$INVOICENO);
    
 //        echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$INVOICENO);
       
        $_SESSION['DocumentPicking']=true; $_SESSION['DocumentNo']=true; unset($_POST);

    }
    
}
 

function getifsaved($Parent,$units){
    global $db;
    $return=array();
    
    $SQL ="select ContainerQTY,ContainerCode from Containers where itemcode='".trim($Parent)."'" ;
    $results=DB_query($SQL ,$db);
    while($rows= DB_fetch_array($results)){
       $return[]= array('ContainerQTY'=>($units * $rows['ContainerQTY']),
           'ContainerCode'=>$rows['ContainerCode']);
    }
    
  
   return $return;
}


function GetEmptiesStore($emptycode,$emptyunits){
    global $db;
    $storecode="";
    
    $value = sprintf("select store from stockledger where itemcode='%s' ",$emptycode);
    $ResultIndex=DB_query($value,$db);
   While($row=DB_fetch_array($ResultIndex)){
           $storecode=(($row['store']=='')?$storecode:$row['store']);
   }
     
     return $storecode;
     
}