<?php
  
$_SESSION['DocumentNo']=$_POST['documentno']; 
$invoicerrors=0; 
$somedetails=0;
foreach ($_POST['subunits'] as $key => $value) {
   if($value>0){
    $location    = trim($_POST['location'][$key]);
    $stcode      = trim($_POST['code'][$key]);
    $qty         =(int) $_POST['subunits'][$key]  ;
    $partperunit =(int) $_POST['partperunit'][$key] ;
    $stockbal    =(int) $_POST['stockbal'][$key] ;
    $_SESSION['cumBalance'][$stcode] +=(int) ($stockbal - $qty)  ;
    
    if($qty>0){
        if($_SESSION['cumBalance'][$stcode]<0){
           $invoicerrors++;
        }
    }
    
   }
}


if($invoicerrors>0){
   prnMsg('You do not have enough stock units to Issue','warn');
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
    $postingDate     = FormatDateForSQL($_POST['posingdate']);
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,true);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = GetNextTransNo(4,$db);
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
           ,vatinclusive)
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
           ,vatinclusive from SalesHeader where documentno='".$_SESSION['DocumentNo']."')" ;
            
    
    foreach ($_POST['subunits'] as $key => $value) {
             if($value>0){

               $CostOfItem =(float) $_POST['CostOfItem'][$key];
               $location = trim($_POST['location'][$key]);
               $stcode = trim($_POST['code'][$key]);
               $qty =(int) $_POST['subunits'][$key];       
               $partperunit =(int) $_POST['partperunit'][$key];
               $emptycode = $_POST['emptycode'][$key];
               $salesoderno= $_SESSION['DocumentNo'];
               $emptyunits = $qty;
               $looseUnits = $qty * $partperunit;
               
                  $sql[]= sprintf("Update SalesLine set
                    Qunatity_replaced=(IFNULL(Qunatity_replaced,0)+ %f),
                    containersunits=(IFNULL(containersunits,0)+ %f)
                    where documentno='%s' and documenttype='%s' 
                    and entryno=%s",$qty,$emptyunits,$salesoderno,1,$key);
           
               
                $sql[]= sprintf("Update SalesHeader set
                    released=1 where documentno='%s' and documenttype='1' ",$salesoderno);
                  
               
               $sql[]= sprintf("INSERT INTO SalesLine
                       (documenttype,docdate,documentno ,code ,description
                       ,unitofmeasure ,Quantity,UnitPrice ,vatamount ,invoiceamount
                       ,vatrate ,inclusive,containerprice,containersunits
                       ,totalchargedcontainers ,containercode,PartPerUnit ,PriceInPricelist)
                       (select '%s','%s','%s',code,description,unitofmeasure,%f,%f,
                       0,(%f * PartPerUnit),vatrate,inclusive,containerprice,
                       '%s',totalchargedcontainers,containercode,PartPerUnit,PriceInPricelist 
                       from SalesLine where entryno=%s)" ,"19",$postingDate
                      ,$INVOICENO,$qty,$emptyunits,$CostOfItem,($qty*$CostOfItem),$key);
             
                        if($_SESSION['productionTankStore'][$stcode]=="S"){
                            $sql[]="INSERT INTO stockledger (date,stname,doctyp,itemcode,invref,netamt,vat,amount,fulqty
                                ,loosqty,price,store,journal,period,jobcard,PartPerUnit)
                                (SELECT SalesLine.docdate ,substring(SalesLine.description,0,30)
                                   ,'4',SalesLine.code ,SalesLine.documentno,SalesLine.invoiceamount ,SalesLine.vatamount,SalesLine.invoiceamount
                                   , 0 as fulqty,".(0-$looseUnits)." as loosqty,(SalesLine.UnitPrice/SalesLine.partperunit)
                                   ,'".$location."' ,'".$journal."' ,'".$PeriodNo."' ,'".$INVOICENO ."' ,1 
                                 FROM SalesLine 
                                 where documentno='".$salesoderno."' and code='".$stcode."' and entryno='".$key."')";
                        }elseif($_SESSION['productionTankStore'][$stcode]=="T"){
                                 $sql[] = sprintf("INSERT INTO tanktrans (tankname,units,uom,date,batchno,doctype,itemcode)
                                 VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',4,'%s')", $location,(0-$looseUnits),'loosqty',$postingDate,$INVOICENO,$stcode);
      
                        }
 
                 GetFIFO($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key);  
                 
                 if(mb_strlen($emptycode)>0){        
               $seales= getifsaved($stcode,$qty);
               foreach ($seales as $array) {
                    $EmptiesStore = GetEmptiesStore($array['ContainerCode'],$qty);
                     $sql[]="INSERT INTO stockledger (date,stname,doctyp,itemcode,invref,netamt,vat,amount,fulqty,loosqty
                        ,price ,store ,journal,period ,jobcard ,PartPerUnit)
                        (SELECT  SalesLine.docdate,substring(SalesLine.description,0,30),'4'
                        ,'".$array['ContainerCode']."',SalesLine.documentno,SalesLine.invoiceamount
                        ,SalesLine.vatamount ,SalesLine.invoiceamount,".(0-$array['ContainerQTY']).",0,SalesLine.UnitPrice
                        ,'".$EmptiesStore."' ,'".$journal."' ,'".$PeriodNo."' ,'".$INVOICENO ."' ,1  
                        FROM SalesLine  where documentno='".$salesoderno."' and code='".$stcode."')";
               }
          }     
                 
                 if(mb_strlen($emptycode)>0){ 
                    $EmptiesStore = GetEmptiesStore($emptycode,$emptyunits);

                    $sql[]="INSERT INTO stockledger (date,stname,doctyp,itemcode,invref,netamt,vat,amount,fulqty,loosqty
                        ,price ,store ,journal,period ,jobcard ,PartPerUnit)
                        (SELECT  SalesLine.docdate,substring(SalesLine.description,0,30),'4'
                        ,SalesLine.containercode,SalesLine.documentno,SalesLine.invoiceamount
                        ,SalesLine.vatamount ,SalesLine.invoiceamount,".(0-$emptyunits).",0,SalesLine.UnitPrice
                        ,'".$EmptiesStore."' ,'".$journal."' ,'".$PeriodNo."' ,'".$INVOICENO ."' ,1  
                        FROM SalesLine  where documentno='".$salesoderno."' and containercode='".$emptycode."')";
                
                    GetEmptiesFIFO($journal,$PeriodNo,$salesoderno,$emptycode,$emptyunits,$key);
                }
                 
            }
    }
    
   
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
    
    $codes = array();
    foreach ($_POST['subunits'] as $key => $value) {
        if ($value > 0) {
            $codes[] = "'" . $db->real_escape_string(trim($_POST['code'][$key])) . "'";
        }
    }
    if (!empty($codes)) {
        $checkResult = DB_query("SELECT COUNT(*) FROM stockmaster sm
            JOIN inventorypostinggroup ipg ON ipg.code = sm.postinggroup
            WHERE sm.itemcode IN (" . implode(',', $codes) . ")
            AND (ipg.CostOfSales IS NULL OR ipg.balancesheet IS NULL)");
        $badCount = DB_fetch_row($checkResult);
        if ($badCount[0] > 0) {
            prnMsg('Cannot post the spoiled stock document. ' . $badCount[0] . ' item(s) have inventory posting groups with missing Cost of Sales or Balance Sheet accounts. Update them in Inventory Posting Groups before posting.', 'error');
            DB_Txn_Rollback($db);
            return;
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
    
         echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$INVOICENO);
       
        $_SESSION['DocumentPicking']=true; $_SESSION['DocumentNo']=true; unset($_POST);

    }
    
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
      ,'4'
      ,inventorypostinggroup.spoilage
      ,inventorypostinggroup.balancesheet
      ,inventorypostinggroup.defaultgl_vat
      ,SalesLine.invoiceamount
      ,IFNULL(SalesLine.vatamount,0) as vatamount
      ,SalesHeader.currencycode
      ,currencies.rate
      ,SalesHeader.customercode
      ,(rtrim(SalesLine.description)) as narration
      ,'".$_POST['DimensionOne']."' 
      ,'".$_POST['DimensionTwo']."'
      from SalesHeader 
      join SalesLine on SalesLine.documentno = SalesHeader.documentno 
      join stockmaster on SalesLine.code=stockmaster.itemcode
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join currencies on SalesHeader.currencycode=currencies.currabrev
      where SalesHeader.documentno='%s'
      AND inventorypostinggroup.spoilage IS NOT NULL
      AND inventorypostinggroup.balancesheet IS NOT NULL
      AND inventorypostinggroup.defaultgl_vat IS NOT NULL
      )",$journal,$period,$doc);
    
    return $sql;
    
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