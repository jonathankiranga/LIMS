<?php

 class laboratryPOS {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $id;
     
     function gethashcode($itemcode,$unitscode){
         $return='';
         if(mb_strlen($itemcode)>0){
             $hash=trim($itemcode).trim($unitscode);
             $return=CryptPass($hash);
         }
          return $return;
     }
     
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        stockmaster.partperunit,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        cv.vat as CVAT,
                        stockmaster.sellingprice
                  FROM stockmaster 
                  left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc 
                  where stockmaster.itemcode='".trim($itemcode)."' ", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode'] = $stocklist[0];
         $this->stocklist['itemcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['si'] = $stocklist[3];
         $this->stocklist['averagestock'] = $stocklist[4];
         $this->stocklist['partperunit'] = $stocklist[5];
         $this->stocklist['vat'] = $stocklist[6];
         $this->stocklist['container'] = $stocklist[7];
         $this->stocklist['packname'] = $stocklist[8];
         $this->stocklist['CVAT'] = $stocklist[9];
         $this->stocklist['sellingprice'] = $stocklist[10];
         
         return $this->stocklist;
         
     }

     function GetTatFromPriceList($stockCode){
         global $db;
         $tat = 0;
         if(empty($stockCode)){
             return $tat;
         }
         $SQL = sprintf("SELECT tat FROM PriceList WHERE stockcode='%s' LIMIT 1", $stockCode);
         $ResultIndex = DB_query($SQL, $db);
         if($Row = DB_fetch_row($ResultIndex)){
             $tat = (int)($Row[0] ?? 0);
         }
         return $tat;
     }
     
      function neworder(){
          $_SESSION['sales_orders']=array();
          $_SESSION['sales_orders_category'] = '';
      }
      
      function RemoveOrder($rowID){
             $this->deleteID=trim($rowID);
             unset($_SESSION['sales_orders'][$this->deleteID]);
             unset($_POST['stockitemcode']); 
             unset($_POST['qty']); 
             unset($_POST['sp']);
             unset($_POST['units']);
             $_SESSION['sales_orders_category'] = '';
    }
  
    
  
      function Getitems($itemcode,$qty,$coa_documentno,$sampleid,$pricevalue,$id='',$discountpercent=0){
          global $db;
          $StockList = $this->GetStockdetails($itemcode);
          if($id==''){
              $this->id = $this->gethashcode($itemcode,0);
          }else{
              $this->id=$id;
          }

          if(mb_strwidth($StockList['itemcode'])>0){

          $_SESSION['sales_orders'][$this->id] = array(
                   'line_no'=>$this->id,
                   'itemcode'=>$StockList['itemcode'],
                   'barcode'=>$StockList['barcode'],
                   'stockname'=>$StockList['stockname'],
                   'sampleid'=>trim($sampleid),
                   'partsperunit'=>1,
                   'averagestock'=>$StockList['averagestock'],
                   'quantity'=>$qty,
                   'pricevalue' => $pricevalue,
                   'tat'=>$this->GetTatFromPriceList($itemcode),
                   'discountpercent'=>$discountpercent);

          $catResult = DB_query("SELECT category FROM stockmaster WHERE itemcode='" . mysqli_real_escape_string($db, trim($itemcode)) . "' LIMIT 1", $db);
          if ($catRow = DB_fetch_array($catResult)) {
              $itemCat = $catRow['category'] ?? '';
              if (empty($_SESSION['sales_orders_category'])) {
                  $_SESSION['sales_orders_category'] = $itemCat;
              } elseif ($_SESSION['sales_orders_category'] !== $itemCat) {
                  $_SESSION['sales_orders_category'] = '';
              }
          }
          }
        $this->showtable();
      }
      
   
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;
        
        $results=DB_query("SELECT customerposting,VATinclusive,IsTaxed FROM debtors 
        join postinggroups on code=customerposting where itemcode='".$_POST['CustomerID']."'", $db);
        $debtorsrow = DB_fetch_row($results);
        $this->customerposting = $debtorsrow[0]; $this->VATinclusive = $debtorsrow[1]; $this->IsTaxed = $debtorsrow[2];
            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptyprice=0; $totalemptycost=0;  
                $cvatamount=0; $vatamount=0;
                $cnetamount=0; $cgrossamount=0; 
              
                $line_no   = trim($rowvalue['line_no']);
                $itemcode  = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate =(int) ($this->IsTaxed==false)?0:$stocklist['vat'];
             
                $sampleid  = $rowvalue['sampleid'];
                $package   =(float) $rowvalue['partsperunit'];
                $location  = $_POST['location'];
                $emptyprice =(float) $_POST['emptyPrice'][$line_no];
                $salesprice       =(float) SelectTestPriceListToUse($itemcode,$package,$UOM,$_POST['CustomerID']) ;
                $PriceInPricelist =(float) SelectTestPriceListToUse($itemcode,$package,$UOM) ;
                $UserPrice   = (float) $rowvalue['pricevalue'];
                if($UserPrice==0){
                      $salesprice  = ($salesprice==0)?$PriceInPricelist:$salesprice;
                }else{
                      $salesprice  = $UserPrice;
                }
              
                $qty         = (float) $rowvalue['quantity'] ;
                $baseamount  = ($salesprice * $qty);
                $lineDiscountPercent = (float) ($rowvalue['discountpercent'] ?? 0);
                $lineDiscountAmount = $baseamount * ($lineDiscountPercent / 100);
                $taxableAmount = $baseamount - $lineDiscountAmount;
                
                 if($this->VATinclusive==true){
                    $netamount = $taxableAmount /  (($rate+100)/100);
                    $vatamount = $taxableAmount - $netamount;
                    $grossamount=round($taxableAmount,2) ;
                }else{
                    $vatamount  = $taxableAmount * ($rate/100);
                    $grossamount= round($taxableAmount + $vatamount,2);
                    $netamount  = $taxableAmount;
                }

                $headerDiscountPercent = (float)($_POST['DiscountPercent'] ?? 0);
                if($headerDiscountPercent > 0){
                    $factor = (100 - $headerDiscountPercent) / 100;
                    $netamount *= $factor;
                    $vatamount *= $factor;
                    $grossamount *= $factor;
                }

                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                if(isset($location)){
                     $StockBalanceInloose=(int) getbalance($stocklist['itemcode'],$location,'loosqty');
                     $code = trim($stocklist['itemcode']);
                     $_SESSION['stockmaster'][$code]['Balance']= intdiv($StockBalanceInloose,$package);
                }else{
                     $StockBalanceInloose= 0;
                }
                
               //  id,bc,name 
                 $ID  = trim($stocklist['itemcode']);
                 $BC  = trim($stocklist['barcode']);
                 $NAME= trim($stocklist['stockname']);
                 $arrayi=$_SESSION['sales_orders'][$lineno];
                  
                 $_SESSION['sales_orders'][$lineno] = array_replace_recursive($arrayi,array('emptyPrice'=>$emptyprice,
                 'salesprice'=>$salesprice,'PriceInPricelist'=>$PriceInPricelist,
                 'netamount'=>$netamount,'vatamount'=>$vatamount,'grossamount'=>$grossamount));
          
                $itemByLine[$lineno] = trim($itemcode);
                $rowsBuff[$lineno] = sprintf(''
              . '<tr onclick="LabOrderInventorygrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');">'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
              . '<td class="number"><input type="text" class="number" value="'.number_format($salesprice,2).'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" readonly="readonly" value="'.number_format($lineDiscountPercent,2).'" size="7"/></td>'
              . '<td class="number"><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($netamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" name="vatamount['.$line_no.']" value="'.number_format($vatamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" name="grossamount['.$line_no.']" value="'.number_format($grossamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td><input type="text" class="number" name="tat['.$line_no.']" value="'.htmlspecialchars($rowvalue['tat'] ?? '', ENT_QUOTES).'" placeholder="TAT days" size="5" readonly="readonly"/></td>'
              . '</tr>',$line_no,$ID,$BC,$NAME,$qty,$salesprice,$rowvalue['partsperunit'],$rowvalue['sampleid'],$rowvalue['discountpercent'] ,
                        $rowvalue['sampleid'],
                        trim($stocklist['stockname']));
            }
           
              if (!isset($rowsBuff)) {
                $rowsBuff = array();
                $itemByLine = array();
            }
            $keyByLine = array_flip($itemByLine);
            $linenoOrder = array_keys($_SESSION['sales_orders']);
            $bundleLines = array();
            foreach ($itemByLine as $il => $ic) {
                if (preg_match('/^TS\d{4}$/', $ic)) $bundleLines[$il] = $ic;
            }
            $memberOf = array();
            if (!empty($bundleLines)) {
                $catIn = array();
                foreach ($bundleLines as $bc) $catIn[] = "'" . mysqli_real_escape_string($db, $bc) . "'";
                $memRes = DB_query("SELECT dt.categoryid, dt.itemcode FROM discounttable dt JOIN stockmaster sm ON sm.itemcode = dt.itemcode WHERE dt.categoryid IN (" . implode(',', $catIn) . ") AND dt.is_active = 1 AND (sm.inactive = 0 OR sm.inactive IS NULL) ORDER BY sm.descrip", $db);
                while ($mm = DB_fetch_array($memRes)) $memberOf[trim($mm['itemcode'])] = trim($mm['categoryid']);
            }
            $orderedRows = array();
            $used = array();
            foreach ($linenoOrder as $il) {
                if (isset($used[$il])) continue;
                $used[$il] = 1;
                if (isset($bundleLines[$il])) {
                    $orderedRows[] = $il;
                    foreach ($memberOf as $mic => $gcat) {
                        if ($gcat === $bundleLines[$il] && isset($keyByLine[$mic]) && !isset($used[$keyByLine[$mic]])) {
                            $orderedRows[] = $keyByLine[$mic];
                            $used[$keyByLine[$mic]] = 1;
                        }
                    }
                } else {
                    $orderedRows[] = $il;
                }
            }
            if (!defined('STDGROUP_CSS')) {
                define('STDGROUP_CSS', 1);
                echo '<style>.std-group td{background:#edf2fb !important;font-weight:bold;} .std-group-item td:first-child{padding-left:30px !important;}</style>';
            }
            foreach ($orderedRows as $il) {
                $rc = isset($bundleLines[$il]) ? 'std-group' : (isset($memberOf[trim($itemByLine[$il])]) ? 'std-group-item' : '');
                $this->htmltable .= ($rc === '') ? $rowsBuff[$il] : str_replace('<tr onclick="LabOrderInventorygrid', '<tr class="' . $rc . '" onclick="LabOrderInventorygrid', $rowsBuff[$il]);
            }

              $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number cell" colspan="5">Total Order</td>'
              . '<td class="number cell">%s</td>'
              . '<td class="number cell">%s</td>'
              . '<td class="number cell">%s</td>'
              . '<td></td>'
              . '</tr></tfoot>',
                      number_format($runningnettotal,2),
                      number_format($runningvattotal,2),  
                      number_format($runninggrosstotal,2));
              
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
       
            
        echo $this->htmltable;
      

    }
    
    
 }

 class QUOTES  {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $tankStore;
     var  $Default=array();
     
     function SaveFooter(){
         
              $filename= 'quotes/'.trim($_POST['documentno']).'.terms';
              file_put_contents($filename,$_POST['paymentterms']."\r\n\r\n",FILE_APPEND);
             
              $filename= 'quotes/'.trim($_POST['documentno']).'.bank';
              file_put_contents($filename,$_POST['bankaccountdetails']."\r\n\r\n",FILE_APPEND);

           
     }
     
          // Helper: fetch TAT (days) from PriceList for given customer/item/units/quantity
     function GetTatFromPriceList($stockCode){
          global $db;
           $tat = 0;
           if( empty($stockCode) ){
               return $tat;
           }
           $SQL = sprintf("SELECT tat FROM PriceList WHERE stockcode='%s'  LIMIT 1",$stockCode);
           $ResultIndex = DB_query($SQL, $db);
           if($Row = DB_fetch_row($ResultIndex)){
               $tat = (int)($Row[0] ?? 0);
           }
           return $tat;
       }
     
     Function IsTankOrStore($ItemCode){
        global $db;
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $this->tankStore="S";
       }else{
          $this->tankStore="T";
       }
    }
    
     Function MakeStoreTankForItem($ItemCode,$key){
        global $db;
        
          
        $line='';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
           $this->tankStore="S";
            if(mb_stristr($ItemCode,'H2O')=='H2O'){
               $line .= '<option></option>';
             }else{
                $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
                while($rows= DB_fetch_array($REsults)){
                     $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['location'][$key]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  }
           }
       }else{
           $this->tankStore="T";
           $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['location'][$key]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
           }
       }
        return $line; 
    }
    
      Function MakeDefaultStoreTankForItem($ItemCode,$key){
        global $db;
        
         
        $line='';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            
                $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
                while($rows =DB_fetch_array($REsults)){
                     $this->Default[0]=array('code'=>trim($rows['code']),'Storename'=>$rows['Storename']);
                  }
       }else{
           
           $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $this->Default[0]=array('code'=>trim($value['tankname']),'Storename'=>trim($value['tankname']));
           }
       }
       
    }
    
     function GetStockdetails($itemcode){
         global $db;
               
         $this->IsTankOrStore($itemcode);
   
          $ResultIndex = DB_query("SELECT 
                         stockmaster.barcode,
                         stockmaster.itemcode,
                         stockmaster.descrip as stockname,
                         unit.descrip as si,
                         stockmaster.averagestock,
                         stockmaster.partperunit,
                         vatcategory.vat,
                         c.itemcode as container,
                         c.descrip as packname,
                         cv.vat as CVAT,
                         stockmaster.sellingprice
                   FROM stockmaster 
                  left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc 
                  where stockmaster.itemcode='".trim($itemcode)."' ", $db);
         
          $stocklist=DB_fetch_row($ResultIndex);
          $this->stocklist['itemcode']  = $stocklist[1];
          $this->stocklist['stockname'] = $stocklist[2];
          $this->stocklist['averagestock'] = $stocklist[4];
          $this->stocklist['partperunit'] = $stocklist[5];
          $this->stocklist['vat'] = $stocklist[6];
          $this->stocklist['container'] = $stocklist[7];
          $this->stocklist['packname'] = $stocklist[8];
          $this->stocklist['CVAT'] = $stocklist[9];
          $this->stocklist['sellingprice'] = $stocklist[10];
         
         return $this->stocklist;
         
     }
     
       function Getitems($itemcode,$qty=1,$uom,$packzize=1,$discountpercent=0){
           global $db;
           $StockList = $this->GetStockdetails($itemcode);
            
           if(mb_strwidth($StockList['itemcode'])>0){
           $id = trim($StockList['itemcode']);
           $container = SelectContainerToUse($itemcode,$packzize,$uom) ;
           $tat  = $this->GetTatFromPriceList($itemcode);
       
           $_SESSION['sales_orders'][$id]= array('line_no'=>$id,
                    'itemcode' => $id,
                    'barcode' => $StockList['barcode'],
                    'stockname' => $StockList['stockname'],
                    'uom' => $uom, 
                    'pricevalue' => $pricevalue,
                    'partsperunit' => $packzize,
                    'averagestock' => $StockList['averagestock'],
                    'quantity' => $qty,
                    'container' => $container,
                    'tat' => $tat,
                    'discountpercent' => $discountpercent);

           $catResult = DB_query("SELECT category FROM stockmaster WHERE itemcode='" . mysqli_real_escape_string($db, trim($id)) . "' LIMIT 1", $db);
           if ($catRow = DB_fetch_array($catResult)) {
               $itemCat = $catRow['category'] ?? '';
               if (empty($_SESSION['sales_orders_category'])) {
                   $_SESSION['sales_orders_category'] = $itemCat;
               } elseif ($_SESSION['sales_orders_category'] !== $itemCat) {
                   $_SESSION['sales_orders_category'] = '';
               }
           }
           }
            
           $this->showtable();
       }
       
      function neworder(){
          $_SESSION['sales_orders']=array();
          $_SESSION['sales_orders_category'] = '';
      }
      
     function RemoveOrder($itemcode){
             $this->deleteID=trim($itemcode);
             unset($_SESSION['sales_orders'][$this->deleteID]);
             unset($_POST['stockitemcode']); 
             unset($_POST['qty']); 
             unset($_POST['sp']);
             unset($_POST['units']);
             $_SESSION['sales_orders_category'] = '';
     }
     
function showtable(){
          global $db;
         
         if (function_exists('applyStandardPricing')) {
             applyStandardPricing();
         }
         
         $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;
        
        $results=DB_query("SELECT customerposting,VATinclusive,IsTaxed FROM debtors 
        join postinggroups on code=customerposting where itemcode='".$_POST['CustomerID']."'", $db);
        $debtorsrow = DB_fetch_row($results);
        if($debtorsrow){
            $this->customerposting = $debtorsrow[0]; $this->VATinclusive = $debtorsrow[1]; $this->IsTaxed = $debtorsrow[2];
        }else{
            $this->customerposting = ''; $this->VATinclusive = false; $this->IsTaxed = false;
        }
            
            $renderOrder = array();
            $groupClass = array();
            $codeByKey = array();
            $keyByCode = array();
            foreach ($_SESSION['sales_orders'] as $kk => $vv) {
                $cc = trim($vv['itemcode']);
                $codeByKey[$kk] = $cc;
                $keyByCode[$cc] = $kk;
            }
            $bundleOrder = array();
            foreach ($codeByKey as $kk => $cc) {
                if (preg_match('/^TS\d{4}$/', $cc)) $bundleOrder[] = $kk;
            }
            if (count($bundleOrder) > 0) {
                $membersOf = array();
                $catIn = array();
                foreach ($bundleOrder as $kk) {
                    $catIn[] = "'" . mysqli_real_escape_string($db, $codeByKey[$kk]) . "'";
                }
                $memRes = DB_query("SELECT dt.categoryid, dt.itemcode FROM discounttable dt JOIN stockmaster sm ON sm.itemcode = dt.itemcode WHERE dt.categoryid IN (" . implode(',', $catIn) . ") AND dt.is_active = 1 AND (sm.inactive = 0 OR sm.inactive IS NULL) ORDER BY sm.descrip", $db);
                while ($mm = DB_fetch_array($memRes)) {
                    $membersOf[trim($mm['categoryid'])][] = trim($mm['itemcode']);
                }
                $used = array();
                foreach ($bundleOrder as $kk) {
                    if (isset($used[$kk])) continue;
                    $renderOrder[] = $kk;
                    $groupClass[$kk] = 'std-group';
                    $used[$kk] = 1;
                    foreach (($membersOf[$codeByKey[$kk]] ?? array()) as $mc) {
                        if (isset($keyByCode[$mc]) && !isset($used[$keyByCode[$mc]])) {
                            $renderOrder[] = $keyByCode[$mc];
                            $groupClass[$keyByCode[$mc]] = 'std-group-item';
                            $used[$keyByCode[$mc]] = 1;
                        }
                    }
                }
                foreach (array_keys($_SESSION['sales_orders']) as $kk) {
                    if (!isset($used[$kk])) {
                        $renderOrder[] = $kk;
                        $groupClass[$kk] = '';
                    }
                }
            } else {
                $renderOrder = array_keys($_SESSION['sales_orders']);
            }

            foreach ($renderOrder as $lineno) {
                $rowvalue = $_SESSION['sales_orders'][$lineno];
                $rowClass = $groupClass[$lineno] ?? '';
                $emptyprice=0; $totalemptycost=0; 
                $cvatamount=0; $vatamount=0;
                $cnetamount=0; $cgrossamount=0; 
                
                $line_no   = trim($rowvalue['line_no']);
                $itemcode  = $rowvalue['itemcode'];
                
                $stocklist = $this->GetStockdetails($itemcode);
                $rate      = ($this->IsTaxed==false)?0:$stocklist['vat'];
             
                $UOM       = $rowvalue['uom'];
                $package   =(float) $rowvalue['partsperunit'];
                $this->MakeDefaultStoreTankForItem($itemcode,$lineno);
  
                $location    = isset($_POST['location'][$line_no])? $_POST['location'][$line_no]:($this->Default[0]['code']);
                $salesprice  = (float) $rowvalue['pricevalue'] ;
                $PriceInPricelist = SelectTestPriceListToUse($itemcode,$package) ;
                $salesprice = ($salesprice=='')?$PriceInPricelist:$salesprice;
                $qty        = (float) $rowvalue['quantity'] ;
                $baseamount = ($salesprice * $qty * $package);
                $lineDiscountPercent = (float) ($rowvalue['discountpercent'] ?? 0);
                $lineDiscountAmount = $baseamount * ($lineDiscountPercent / 100);
                $taxableAmount = $baseamount - $lineDiscountAmount;
                $headerDiscountPercent = (float) ($_POST['DiscountPercent'] ?? 0);
                
                 if($this->VATinclusive==true){
                    $netamount = $taxableAmount /  (($rate+100)/100);
                    $vatamount = $taxableAmount - $netamount;
                    $grossamount=$taxableAmount ;
                }else{
                    $vatamount  = $taxableAmount * ($rate/100);
                    $grossamount= $taxableAmount + $vatamount;
                    $netamount  = $taxableAmount;
                }
                if($headerDiscountPercent > 0){
                    $factor = (100 - $headerDiscountPercent) / 100;
                    $netamount *= $factor;
                    $vatamount *= $factor;
                    $grossamount *= $factor;
                }
            
                $runningnettotal   += $netamount;
                $runningvattotal   += $vatamount;
                $runninggrosstotal += $grossamount;
                
                if($this->tankStore=="T"){
                    $StockBalanceInloose =(int) getTankbalance($location,$itemcode);
                    $_SESSION['stockmasterp'][$itemcode]['Balance']= $StockBalanceInloose;
                }elseif($this->tankStore=="S"){
                    $StockBalanceInloose=(int) getbalance($itemcode,$location,'loosqty');
                    $_SESSION['stockmasterp'][$itemcode]['Balance']=$StockBalanceInloose;
                }
                 
               //  id,bc,name 
                 $ID  = trim($stocklist['itemcode']);
                 $BC  = trim($stocklist['barcode']);
                 $NAME= trim($stocklist['stockname']);
                 $Balance = $StockBalanceInloose;
                 $arrayi = $_SESSION['sales_orders'][$lineno];
            
                 $_SESSION['sales_orders'][$lineno] = array_replace_recursive($arrayi,
                 array(
                     'stockname'=>$NAME,
                     'VATinclusive'=>$this->VATinclusive,
                     'IsTaxed'=>$this->IsTaxed,
                     'vatrate'=>$rate,
                     'salesprice'=>$salesprice,
                     'PriceInPricelist'=>$PriceInPricelist,
                     'stkbalance'=>($Balance-$qty),
                     'netamount'=>$netamount,
                     'vatamount'=>$vatamount,
                     'grossamount'=>$grossamount)
                );
           
                  $myobject=getGriduom($line_no,$UOM);
                 $this->htmltable .= sprintf('<tr class="%s" onclick="SalesOrderInventorygrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');">'
               . '<td>%s</td>'
               . '<td>%s</td>'
               . '<td class="number"><input class="number" type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
               . '<td>%s</td>'
               . '<td><input type="text" class="number" readonly="readonly" value="'.number_format($salesprice,2).'"  size="10"/></td>'
               . '<td><input type="text" class="number" readonly="readonly" value="'.number_format($lineDiscountPercent,2).'"  size="7"/></td>'
               . '<td><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($netamount,2).'" readonly="readonly" size="10"/></td>'
               . '<td><input type="text" class="number" name="vatamount['.$line_no.']" value="'.number_format($vatamount,2).'" readonly="readonly" size="10"/></td>'
               . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.number_format($grossamount,2).'" readonly="readonly" size="10"/></td>'
               . '<td><input type="text" class="number" name="tat['.$line_no.']" value="'.($rowvalue['tat'] ?? '').'" placeholder="TAT days" size="5"/></td>'
               . '</tr>',$rowClass,$line_no,$ID,$BC,"$NAME",$qty,$salesprice,$package,$UOM,$lineDiscountPercent,
                         $ID,$NAME,$myobject);
        
                }
            
              $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number cell" colspan="6">Total Order</td><td class="number cell">%s</td><td class="number cell">%s</td><td class="number cell">%s</td><td></td>'
              . '</tr></tfoot>',number_format($runningnettotal,2),  number_format($runningvattotal,2),  number_format($runninggrosstotal,2));
              
            if (!defined('STDGROUP_CSS')) {
                define('STDGROUP_CSS', 1);
                echo '<style>.std-group td{background:#edf2fb !important;font-weight:bold;} .std-group-item td:first-child{padding-left:30px !important;}</style>';
            }
            
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
         
            
        echo $this->htmltable;
      

    }
    
    
 }

 class Samples {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $tankStore;
     var  $Default=array();
     
     Function IsTankOrStore($ItemCode){
        global $db;
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $this->tankStore="S";
       }else{
          $this->tankStore="T";
       }
    }
    
     Function MakeStoreTankForItem($ItemCode,$key){
        global $db;
        
          
        $line='';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
           $this->tankStore="S";
            if(mb_stristr($ItemCode,'H2O')=='H2O'){
               $line .= '<option></option>';
             }else{
                $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
                while($rows= DB_fetch_array($REsults)){
                     $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['location'][$key]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  }
           }
       }else{
           $this->tankStore="T";
           $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['location'][$key]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
           }
       }
        return $line; 
    }
    
      Function MakeDefaultStoreTankForItem($ItemCode,$key){
        global $db;
        
         
        $line='';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            
                $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
                while($rows =DB_fetch_array($REsults)){
                     $this->Default[0]=array('code'=>trim($rows['code']),'Storename'=>$rows['Storename']);
                  }
       }else{
           
           $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $this->Default[0]=array('code'=>trim($value['tankname']),'Storename'=>trim($value['tankname']));
           }
       }
       
    }
    
      function GetStockdetails($itemcode){
          global $db;
                
          $this->IsTankOrStore($itemcode);
    
          $ResultIndex = DB_query("SELECT 
                         stockmaster.itemcode,
                         stockmaster.descrip as stockname,
                         unit.descrip as si,
                         stockmaster.averagestock,
                         stockmaster.partperunit,
                         vatcategory.vat,
                         c.itemcode as container,
                         c.descrip as packname,
                         cv.vat as CVAT,
                         stockmaster.sellingprice
                   FROM stockmaster 
                   left join stockmaster c on stockmaster.container=c.itemcode
                   left join unit on stockmaster.units=unit.code
                   left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                   left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                   left join inventorypostinggroup ci on c.postinggroup=ci.code
                   left join vatcategory cv on ci.vatcategory=cv.vatc 
                   where stockmaster.itemcode='".trim($itemcode)."' ", $db);
          
          $stocklist=DB_fetch_row($ResultIndex);
          $this->stocklist['itemcode']  = $stocklist[0];
          $this->stocklist['stockname'] = $stocklist[1];
          $this->stocklist['averagestock'] = $stocklist[3];
          $this->stocklist['partperunit'] = $stocklist[4];
          $this->stocklist['vat'] = $stocklist[5];
          $this->stocklist['container'] = $stocklist[6];
          $this->stocklist['packname'] = $stocklist[7];
          $this->stocklist['CVAT'] = $stocklist[8];
          $this->stocklist['sellingprice'] = $stocklist[9];
          
          return $this->stocklist;
          
      }
     
      function Getitems($itemcode,$qty,$uom,$packzize){
          $StockList = $this->GetStockdetails($itemcode);
           
          if(mb_strwidth($StockList['itemcode'])>0){
          $id = trim($StockList['itemcode']);
          $_SESSION['sales_orders'][$id]= array('line_no'=>$id,
                   'itemcode'=>$id,
                   'stockname'=>$StockList['stockname'],
                   'uom'=>$uom,
                   'partsperunit'=>$packzize,
                   'averagestock'=>$StockList['averagestock'],
                   'quantity'=>$qty);
          }
           
          $this->showtable();
      }
      
     function neworder(){
         $_SESSION['sales_orders']=array();
     }
     
    function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['sales_orders'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
    }
    
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;
        
        $results=DB_query("SELECT customerposting,VATinclusive,IsTaxed FROM debtors 
        join postinggroups on code=customerposting where itemcode='".$_POST['CustomerID']."'", $db);
        $debtorsrow = DB_fetch_row($results);
        $this->customerposting = $debtorsrow[0]; $this->VATinclusive = $debtorsrow[1]; $this->IsTaxed = $debtorsrow[2];
            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptyprice=0; $totalemptycost=0; 
                $cvatamount=0; $vatamount=0;
                $cnetamount=0; $cgrossamount=0; 
    
                 
                $line_no   = trim($rowvalue['line_no']);
                $itemcode  = $rowvalue['itemcode'];
                
                $stocklist = $this->GetStockdetails($itemcode);
                $rate      = ($this->IsTaxed==false)?0:$stocklist['vat'];
             
                $UOM       = $rowvalue['uom'];
                $package   =(float) $rowvalue['partsperunit'] ?? '1';
                $this->MakeDefaultStoreTankForItem($itemcode,$lineno);
  
                $location  =isset($_POST['location'][$line_no])? $_POST['location'][$line_no]:($this->Default[0]['code']);
                 
                $salesprice  = SelectPriceListToUse($itemcode,$package,$UOM,$_POST['CustomerID']) ;
                $PriceInPricelist = SelectPriceListToUse($itemcode,$package,$UOM) ;
                $salesprice = ($salesprice=='')?$PriceInPricelist:$salesprice;
                $qty         =(float) $rowvalue['quantity'] ?? '1' ;
                $baseamount  = ($salesprice * $qty);
                
                 if($this->VATinclusive==true){
                    $netamount = $baseamount /  (($rate+100)/100);
                    $vatamount = $baseamount - $netamount;
                    $grossamount=$baseamount ;
                }else{
                    $vatamount  = $baseamount * ($rate/100);
                    $grossamount= $baseamount + $vatamount;
                    $netamount  = $baseamount;
                }
            
                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                if($this->tankStore=="T"){
                    $StockBalanceInloose =(int) getTankbalance($location,$itemcode);
                    $_SESSION['stockmasterp'][$itemcode]['Balance']= $StockBalanceInloose;
                }elseif($this->tankStore=="S"){
                    $StockBalanceInloose=(int) getbalance($itemcode,$location,'loosqty');
                    $_SESSION['stockmasterp'][$itemcode]['Balance']=$StockBalanceInloose;
                }
                 
               //  id,bc,name 
                 $ID  = trim($stocklist['itemcode']);
                 $BC  = trim($stocklist['barcode']);
                 $NAME= trim($stocklist['stockname']);
                 $Balance = $StockBalanceInloose;
                 $arrayi = $_SESSION['sales_orders'][$lineno];
            
                 $_SESSION['sales_orders'][$lineno] = array_replace_recursive($arrayi,
                 array(
                     'location'=>$location,
                     'tankStore'=>$this->tankStore,
                     'VATinclusive'=>$this->VATinclusive,
                     'IsTaxed'=>$this->IsTaxed,
                     'vatrate'=>$rate,
                     'salesprice'=>$salesprice,
                     'PriceInPricelist'=>$PriceInPricelist,
                     'stkbalance'=>($Balance-$qty),
                     'netamount'=>$netamount,
                     'vatamount'=>$vatamount,
                     'grossamount'=>$grossamount)
                );
           
                  
                $this->htmltable .= sprintf('<tr onclick="SalesOrderInventorygrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');">'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td class="number"><input class="number" type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
              . '<td>%s</td>'
              . '<td><input class="number" type="text" name="package['.$line_no.']" value="'.$package.'"  maxlength="5" readonly="readonly" size="7"/></td>'
              . '<td><input type="text" class="number" readonly="readonly" value="'.number_format($salesprice,2).'"  size="10"/></td>'
              . '<td><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($netamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td><input type="text" class="number" name="vatamount['.$line_no.']" value="'.number_format($vatamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.number_format($grossamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td><select name="location['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select> Balance:<br/>'.($Balance-$qty).'</td>'
              . '</tr>',$ID,$BC,$NAME,$qty,$salesprice,$package,
               trim($stocklist['barcode']),trim($stocklist['stockname']),getGriduom($line_no,$UOM),$this->MakeStoreTankForItem($ID,$line_no));
        
                }
            
              $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number cell" colspan="6">Total Order</td><td class="number cell">%s</td><td class="number cell">%s</td><td class="number cell">%s</td>'
              . '</tr></tfoot>',number_format($runningnettotal,2),  number_format($runningvattotal,2),  number_format($runninggrosstotal,2));
              
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
         
            
        echo $this->htmltable;
      

    }
    
    
 }

 class Salespos {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $id;
     
     function gethashcode($itemcode,$unitscode){
         $return='';
         if(mb_strlen($itemcode)>0){
             $hash=trim($itemcode).trim($unitscode);
             $return=CryptPass($hash);
         }
          return $return;
     }
     
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        stockmaster.partperunit,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        cv.vat as CVAT,
                        stockmaster.sellingprice
                  FROM stockmaster 
                  left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc 
                  where stockmaster.itemcode='".trim($itemcode)."' ", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode'] = $stocklist[0];
         $this->stocklist['itemcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['si'] = $stocklist[3];
         $this->stocklist['averagestock'] = $stocklist[4];
         $this->stocklist['partperunit'] = $stocklist[5];
         $this->stocklist['vat'] = $stocklist[6];
         $this->stocklist['container'] = $stocklist[7];
         $this->stocklist['packname'] = $stocklist[8];
         $this->stocklist['CVAT'] = $stocklist[9];
         $this->stocklist['sellingprice'] = $stocklist[10];
         
         return $this->stocklist;
         
     }

     function GetTatFromPriceList($stockCode){
         global $db;
         $tat = 0;
         if(empty($stockCode)){
             return $tat;
         }
         $SQL = sprintf("SELECT tat FROM PriceList WHERE stockcode='%s' LIMIT 1", $stockCode);
         $ResultIndex = DB_query($SQL, $db);
         if($Row = DB_fetch_row($ResultIndex)){
             $tat = (int)($Row[0] ?? 0);
         }
         return $tat;
     }
     
     function neworder(){
         $_SESSION['sales_orders']=array();
     }
     
     function RemoveOrder($rowID){
            $this->deleteID=trim($rowID);
            unset($_SESSION['sales_orders'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
    }
 
   
  
     function Getitems($itemcode,$qty,$uom,$packzize,$pricevalue,$id='',$discountpercent=0){
         $StockList = $this->GetStockdetails($itemcode);
         if($id==''){ 
             $this->id = $this->gethashcode($itemcode,$uom); 
          }else{  
             $this->id=$id;
             }
         
         if(mb_strwidth($StockList['itemcode'])>0){
         $container = SelectContainerToUse($itemcode,$packzize,$uom) ;
             
         $_SESSION['sales_orders'][$this->id] = array(
                  'line_no'=>$this->id,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'uom'=>trim($uom),
                  'partsperunit'=>$packzize,
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'pricevalue' => $pricevalue,
                  'containerCode'=>$container,
                  'tat'=>$this->GetTatFromPriceList($itemcode),
                  'discountpercent'=>$discountpercent);
         }
       $this->showtable();
     }
      
   
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;
        
        $results=DB_query("SELECT customerposting,VATinclusive,IsTaxed FROM debtors 
        join postinggroups on code=customerposting where itemcode='".$_POST['CustomerID']."'", $db);
        $debtorsrow = DB_fetch_row($results);
        $this->customerposting = $debtorsrow[0]; $this->VATinclusive = $debtorsrow[1]; $this->IsTaxed = $debtorsrow[2];
            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptyprice=0; $totalemptycost=0;  
                $cvatamount=0; $vatamount=0;
                $cnetamount=0; $cgrossamount=0; 
              
                $line_no   = trim($rowvalue['line_no']);
                $itemcode  = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate =(int) ($this->IsTaxed==false)?0:$stocklist['vat'];
             
                $UOM       = $rowvalue['uom'];
                $package   =(float) $rowvalue['partsperunit'];
                $location  = $_POST['location'];
                $emptyprice =(float) $_POST['emptyPrice'][$line_no];
                $salesprice  =(float) SelectPriceListToUse($itemcode,$package,$UOM,$_POST['CustomerID']) ;
                $PriceInPricelist =(float) SelectPriceListToUse($itemcode,$package,$UOM) ;
                $UserPrice   = (float) $rowvalue['pricevalue'];
                if($UserPrice==0){
                      $salesprice  = ($salesprice==0)?$PriceInPricelist:$salesprice;
                }else{
                      $salesprice  = $UserPrice;
                }
              
                $qty         = (float) $rowvalue['quantity'] ;
                $baseamount  = ($salesprice * $qty);
                $lineDiscountPercent = (float) ($rowvalue['discountpercent'] ?? 0);
                $lineDiscountAmount = $baseamount * ($lineDiscountPercent / 100);
                $taxableAmount = $baseamount - $lineDiscountAmount;
                
                 if($this->VATinclusive==true){
                    $netamount = $taxableAmount /  (($rate+100)/100);
                    $vatamount = $taxableAmount - $netamount;
                    $grossamount=round($taxableAmount,2) ;
                }else{
                    $vatamount  = $taxableAmount * ($rate/100);
                    $grossamount= round($taxableAmount + $vatamount,2);
                    $netamount  = $taxableAmount;
                }

                $headerDiscountPercent = (float)($_POST['DiscountPercent'] ?? 0);
                if($headerDiscountPercent > 0){
                    $factor = (100 - $headerDiscountPercent) / 100;
                    $netamount *= $factor;
                    $vatamount *= $factor;
                    $grossamount *= $factor;
                }

                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                if(isset($location)){
                     $StockBalanceInloose=(int) getbalance($stocklist['itemcode'],$location,'loosqty');
                     $code = trim($stocklist['itemcode']);
                     $_SESSION['stockmaster'][$code]['Balance']= intdiv($StockBalanceInloose,$package);
                }else{
                     $StockBalanceInloose= 0;
                }
                
               //  id,bc,name 
                 $ID  = trim($stocklist['itemcode']);
                 $BC  = trim($stocklist['barcode']);
                 $NAME= trim($stocklist['stockname']);
                 $arrayi=$_SESSION['sales_orders'][$lineno];
                 
                  $unitname  = $_SESSION['units'][$UOM]['descrip'];
                  
                 $_SESSION['sales_orders'][$lineno] = array_replace_recursive($arrayi,array('emptyPrice'=>$emptyprice,
                 'salesprice'=>$salesprice,'PriceInPricelist'=>$PriceInPricelist,
                 'netamount'=>$netamount,'vatamount'=>$vatamount,'grossamount'=>$grossamount));
            
                $this->htmltable .= sprintf('<tr onclick="SalesOrderInventorygrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');">'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
              . '<td>%s</td><td><input type="text" class="number" name="package['.$line_no.']" value="'.$package.'"  size="7" maxlength="5" readonly="readonly"/></td>'
              . '<td class="number"><input type="text" class="number" value="'.number_format($salesprice,2).'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" readonly="readonly" value="'.number_format($lineDiscountPercent,2).'" size="7"/></td>'
              . '<td class="number"><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($netamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" name="vatamount['.$line_no.']" value="'.number_format($vatamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" name="grossamount['.$line_no.']" value="'.number_format($grossamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td><input type="text" class="number" name="tat['.$line_no.']" value="'.htmlspecialchars($rowvalue['tat'] ?? '', ENT_QUOTES).'" placeholder="TAT days" size="5" readonly="readonly"/></td>'
              . '</tr>',$line_no,$ID,$BC,$NAME,$qty,$salesprice,$package,$unitname,
                        trim($stocklist['barcode']),
                        trim($stocklist['stockname']),
                        getGriduom($line_no,$UOM ));
            }
           
              $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number cell" colspan="7">Total Order</td>'
              . '<td class="number cell">%s</td>'
              . '<td class="number cell">%s</td>'
              . '<td class="number cell">%s</td>'
              . '<td></td>'
              . '</tr><tr>'
              . '<td colspan="9">Transport Charge to add to Invoice Total </td>'
              . '<td><input type="text" class="number" name="FREIGHT" value="'.$_POST['FREIGHT'].'" size="10" />'
              . '</td><td></td></tr><tr>'
              . '<td colspan="9">Package Charge to add to Invoice Total </td>'
              . '<td><input type="text" class="number" name="packagescharge" value="'.$_POST['packagescharge'].'" size="10" />'
              . '</td><td></td></tr></tfoot>',
                      number_format($runningnettotal,2),
                      number_format($runningvattotal,2),  
                      number_format($runninggrosstotal,2));
              
            $_SESSION['Grossamounttotal']=($runninggrosstotal+(float) $_POST['FREIGHT']+(float)$_POST['packagescharge']);
            
       
            
        echo $this->htmltable;
      

    }
    
    
 }

 class PriceList {
     var  $rowid;
     var  $htmltable;
     var  $stocklist=array();
                 
     function getready(){
         $_SESSION['price_list'] = array();
     }
    
      function save(){
          global $db;
          DB_Txn_Begin($db);
          
          foreach ($_SESSION['price_list'] as $key => $row) {
            $approved =(mb_strlen($row['customercode'])>1)?False:True;
              
            $SQL = sprintf("Delete from PriceList where customerCode='%s' and stockcode='%s' and units_code='%s' and quantity='%s'",
                    $row['customercode'],$row['itemcode'],$row['unitcode'],$row['packing']);
                 $ResultIndex = DB_query($SQL,$db);  
              
            $tatValue = isset($row['tat']) ? (int)$row['tat'] : 0;
            $SQL = sprintf("insert into PriceList (customerCode,stockcode,units_code,quantity,price,container,approved,tat) "
                    . " values ('%s','%s','%s',%f,%f,'%s','%s',%d)",$row['customercode'],$row['itemcode'],$row['unitcode'],$row['packing'],$row['sellingprice'],$_POST['container'],$approved,$tatValue);
             DB_query($SQL,$db);
          }
         
        echo '<a  target="mainContentIFrame"   href="ApprovePriceList.php">' ._('Approve Price List ') .'</a>';
	
         if(DB_error_no($db)==0){
             DB_Txn_Commit($db);
             $this->getready();
                   unset($_POST['stockitemcode']);
                   unset($_POST['qty']);
                   unset($_POST['CustomerID']);
                   unset($_POST['units']);
                   unset($_POST['sp']);
                   unset($_POST['container']);
         }else{
             DB_Txn_Rollback($db);
         }
         
         
     }
     
     function deleteMasterdata($id){
         global $db;
         
         $SQL = sprintf("select stockcode,units_code,quantity from PriceList where id='%s' ",$id);
         $ResultIndex = DB_query($SQL,$db);
         $stockcode = DB_fetch_row($ResultIndex);
         
         $SQL = sprintf("select id from PriceList "
                 . " where stockcode='%s' and units_code='%s' and quantity='%s' "
                 . " order by customerCode desc",
                 $stockcode[0],$stockcode[1],$stockcode[2]);
         $ResultIndex = DB_query($SQL,$db);
         while($row= DB_fetch_array($ResultIndex)){
             $DeleteAll[]=$row['id'];
         }
         
         $SQL = sprintf("select count(*) from PriceList "
                 . " where stockcode='%s' and units_code='%s' and quantity='%s'"
                 . " and customerCode<>'' ",$stockcode[0],$stockcode[1],$stockcode[2]);
         $ResultIndex = DB_query($SQL,$db);
         $count = DB_fetch_row($ResultIndex);
         
         if($count[0]==0){
             if(mb_strlen($id)>0){
                $SQL = sprintf("Delete from PriceList where id='%s'",$id);
                $ResultIndex = DB_query($SQL,$db);
             }
             $this->getready();
         }else{
             foreach ($DeleteAll as $value) {
                    $SQL = sprintf("Delete from PriceList where id='%s'",$value);
                     $ResultIndex = DB_query($SQL,$db);
             }
             prnMsg("You have also deleted in ". $count[0]." other places", 'warn');
              $this->getready();
         }
     }
     
     function deletedata($id){
         global $db;
             if(mb_strlen($id)>0){
                $SQL = sprintf("Delete from PriceList where id='%s'",$id);
                $ResultIndex = DB_query($SQL,$db);
                
                $results = DB_query(sprintf("SELECT customer FROM debtors where itemcode='%s'",trim($_POST['CustomerID'])), $db);
                $row = DB_fetch_row($results);
                $_POST['CustomerName'] = $row[0];
            }
         $this->getready();
     }
     
     function showPriceList(){
         global $db,$RootPath,$Theme,$pge;
            $SQL="select stockcode,units_code,quantity,price,id,tat from PriceList where customerCode=''";
             
        $table = '<center><h3>Price List</h3></center>'
                    . '<table class="register display">'
                    . '<thead><tr><th>Code</th>'
                    . '<th>Product Name</th>'
                    . '<th class="number">Unit PRICE</th>'
                    . '<th class="number">Calculated Price</th>'
                    . '<th class="number">TAT Per Unit</th>'
                    . '<th class="number">REMOVE ITEM</th></tr></thead><tbody>';
            
            $ResultIndex=DB_query($SQL,$db);
            while($row= DB_fetch_array($ResultIndex)){
               $itemcode  = $row['stockcode']; 
               $unitscode = trim($row['units_code']); 
               $contCode  = trim($row['container']); 
               $ContainerName = $_SESSION['containers'][$contCode]['descrip'];
                $StockList = $this->GetStockdetails($itemcode);
                 $unitname  = $_SESSION['units'][$unitscode]['descrip'];
                 $qty = $row['quantity']; 
                 $price= $row['price'];
                 $Rid = $row['id'];
                 $stockname = trim($StockList['stockname']);
               $tatVal = isset($row['tat']) ? (int)$row['tat'] : 0;
            $table .= sprintf('<tr>'
                    . '<td>%s</td>'
                    . '<td>%s</td>'
                    . '<td class="number">%s</td>'
                    . '<td class="number">%s</td>'
                    . '<td class="number">%s</td>'
                    . '<td>%s</td></tr>',
            $StockList['itemcode'],$StockList['stockname'],
                    $price,($price/$qty),$tatVal,
            '<a href="'.$pge.'?delid='.$Rid.'" onclick="return confirm(\'Do you want to delete this price item ?\');"><img src="'.$RootPath.'/css/xenos/images/cross.png"></a>');   
          }
            
           $table .='</tbody><tfoot><tr>'
                   . '<th></th>'
                     . '<th></th>'
                     . '<th></th>'
                     . '<th></th>'
                     . '<th></th>'
                     . '<th></th>'
                   . '</tr></tfoot></table>';
        echo $table;
     }
     
     function PriceListStatus($row){
       if($row['approved']==1){
             $Date = ConvertSQLDateTime($row['DateTime']);
             $user = $row['approvedby'];
             $return="Approved on ".$Date." by ".$user;
       }elseif($row['approved']==0 and $row['approvedby']!=''){
           $Date = ConvertSQLDateTime($row['DateTime']);
           $user = $row['approvedby'];
           $return="Was rejected on ".$Date." by ".$user;
       }else{
           $return="Waiting approval ";
       }
        return $return;

     }
 
        // Helper: fetch TAT (days) from PriceList for given customer/item/units/quantity
     function GetTatFromPriceList($stockCode){
         global $db;
           $tat = 0;
           
           if(empty($stockCode)){
               return $tat;
           }
           
           $SQL = sprintf("SELECT tat FROM PriceList WHERE stockcode='%s'  LIMIT 1",
               $customerCode, $stockCode, $unitsCode, (int)$quantity);
           $ResultIndex = DB_query($SQL, $db);
           if($Row = DB_fetch_row($ResultIndex)){
               $tat = (int)($Row[0] ?? 0);
           }
           return $tat;
       }

     function SummaryPriceList($id=''){
         global $db,$RootPath,$Theme,$pge;
              $table =''; 
              
            $SQL=sprintf("select pl.stockcode,pl.units_code,pl.container,pl.quantity,pl.price,pl.id,"
                . "pl.approved,pl.approvedby,pl.DateTime,pl.tat,"
                . " CASE WHEN pl.stockcode LIKE 'TS____' THEN pl.stockcode WHEN dt.categoryid LIKE 'TS____' THEN dt.categoryid ELSE CONCAT('~',pl.stockcode) END AS grp"
                . " from PriceList pl LEFT JOIN discounttable dt ON dt.itemcode = pl.stockcode AND dt.categoryid LIKE 'TS____' LEFT JOIN stockmaster sm ON sm.itemcode = pl.stockcode"
                . " where pl.customerCode='%s' order by grp asc, (pl.stockcode LIKE 'TS____') desc, sm.descrip asc",$id);
            $ResultIndex=DB_query($SQL,$db);
            while($row= DB_fetch_array($ResultIndex)){
               $itemcode  = $row['stockcode']; 
               $contCode  = trim($row['container']); 
               if(isset($_SESSION['containers'][$contCode]['descrip'])){
                $ContainerName = $_SESSION['containers'][$contCode]['descrip'];
               }else{
                 $ContainerName = '';
               }
               $unitscode = trim($row['units_code']); 
               $StockList = $this->GetStockdetails($itemcode);
               $unitname  = $_SESSION['units'][$unitscode]['descrip'];
               $qty       = $row['quantity']; 
               $price     = $row['price']; 
               $Rid       = $row['id'];
               $TAT       = $row['tat'];
               $BC        = trim($StockList['barcode']);
               $stockname = trim($StockList['stockname']);
              $isBundle = (bool)preg_match('/^TS\d{4}$/', trim($itemcode));
                $rowClass = $isBundle ? 'std-group' : (preg_match('/^TS\d{4}$/', trim($row['grp'] ?? '')) ? 'std-group-item' : '');
              $table .= sprintf('<tr class="%s" onclick="pricelistgrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');'
                      . 'divideCheckList(\'qty\',\'unitprice\',\'sp\');">'
                      . '<td>%s</td>'
                      . '<td>%s</td>'
                      . '<td>%s 1 x %s </td>'
                      . '<td class="number">%s</td>'
                      . '<td class="number">%s</td>'
                      . '<td class="number">%s</td>'
                      . '<td>%s</td>'
                      . '<td>%s</td></tr>',
                             $rowClass,
                             $itemcode,$BC,$stockname,$qty,$price,$unitname,$Rid,$ContainerName,
                             $StockList['barcode'],$StockList['stockname'],$unitname,$qty,$price,
                            ($price/$qty),$TAT,$this->PriceListStatus($row),
                      '<a href="'.$pge.'?delid='.$Rid.'&cu='.$id.'" onclick="return confirm(\'Do you want to delete this price item ?\');"><img src="'.$RootPath.'/css/xenos/images/cross.png"></a>');   
                 }
            
            
            return $table;
     }
 
     function copyPriceList($id=''){
         global $db,$RootPath,$Theme,$pge;
         
         
         if (!defined('STDGROUP_CSS')) {
        define('STDGROUP_CSS', 1);
        echo '<style>.std-group td{background:#edf2fb !important;font-weight:bold;} .std-group-item td:first-child{padding-left:30px !important;}</style>';
    }

    $table ='<table style="width: 50%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
        <thead>
            <tr>
                <th>Target</th>
                 <th>Search text</th>
                <th>Treat as regex</th>
                <th>Use smart search</th>
            </tr>
        </thead>
        <tbody>
            <tr id="filter_global">
                <td>Global search</td>
                <td align="center"><input type="text" class="global_filter" id="global_filter"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_regex"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_smart" checked="checked"></td>
    
            </tr>
            <tr id="filter_col1" data-column="1">
                <td>Column - Stock Name</td>
                <td align="center"><input type="text" class="column_filter" id="col1_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_smart" checked="checked"></td>
            </tr>
          </tbody>
    </table>';
         
            if(mb_strlen($id)>0){
                 $SQL=sprintf("select pl.container,pl.stockcode,pl.units_code,pl.quantity,pl.price,pl.id,pl.approved,pl.approvedby,pl.DateTime,pl.TAT,"
                . " CASE WHEN pl.stockcode LIKE 'TS____' THEN pl.stockcode WHEN dt.categoryid LIKE 'TS____' THEN dt.categoryid ELSE CONCAT('~',pl.stockcode) END AS grp"
                . " from PriceList pl LEFT JOIN discounttable dt ON dt.itemcode = pl.stockcode AND dt.categoryid LIKE 'TS____' LEFT JOIN stockmaster sm ON sm.itemcode = pl.stockcode"
                . " where pl.customerCode='%s' order by grp asc, (pl.stockcode LIKE 'TS____') desc, sm.descrip asc",$id);
               
                  $table .='<center><h2>'.$_POST['CustomerName'].' PRICE LIST</h2></center>'
                     . '<table class=" register display table-bordered table-striped">'
                     . '<thead><tr>'
                     . '<td>Code</td>'
                     . '<td>SALES ITEM DESCRIPTION</td>'
                     . '<td>PACKING</td>'
                     . '<td class="number">PRICE</td>'
                     . '<td class="number">UNIT PRICE</td>'
                     . '<td class="number">TAT</td>'
                     . '<td>STATUS</td>'
                     . '<td class="number">REMOVE ITEM</td>'
                     . '</tr></thead><tbody>';
          }else{
                 $SQL="select pl.container,pl.stockcode,pl.units_code,pl.quantity,pl.price,pl.id,pl.tat,"
                . " CASE WHEN pl.stockcode LIKE 'TS____' THEN pl.stockcode WHEN dt.categoryid LIKE 'TS____' THEN dt.categoryid ELSE CONCAT('~',pl.stockcode) END AS grp"
                . " from PriceList pl LEFT JOIN discounttable dt ON dt.itemcode = pl.stockcode AND dt.categoryid LIKE 'TS____' LEFT JOIN stockmaster sm ON sm.itemcode = pl.stockcode"
                . " where pl.customerCode='' order by grp asc, (pl.stockcode LIKE 'TS____') desc, sm.descrip asc";
                 $table .='<center><h3>DEFAULT PRICE LIST</h3></center>'
                     . '<table class="register display table-bordered table-striped">'
                     . '<thead><tr>'
                     . '<td>Code</td>'
                     . '<td span="3">SALES ITEM DESCRIPTION</td>'
                     . '<td>PACKING</td>'
                     . '<td class="number">PRICE</td>'
                     . '<td class="number">UNIT PRICE</td>'
                     . '<td class="number">TAT</td>'
                     . '</tr></thead><tbody>';
          
            
            }
            
            
            
             $ResultIndex=DB_query($SQL,$db);
             while($row= DB_fetch_array($ResultIndex)){
                $itemcode  = $row['stockcode']; 
                $unitscode = trim($row['units_code']); 
                $StockList = $this->GetStockdetails($itemcode);
                $unitname  = $_SESSION['units'][$unitscode]['descrip'];
                $qty       = $row['quantity']; 
                $price     = $row['price']; 
                $Rid       = $row['id'];
                $stockname = trim($StockList['stockname']);
                $isBundle = (bool)preg_match('/^TS\d{4}$/', trim($itemcode));
                $rowClass = $isBundle ? 'std-group' : (preg_match('/^TS\d{4}$/', trim($row['grp'] ?? '')) ? 'std-group-item' : '');
                $tatVal    = isset($row['tat']) ? (int)$row['tat'] : 0;
                $contCode  = trim($row['container']); 
                $ContainerName = $_SESSION['containers'][$contCode]['descrip'];
  
                if(mb_strlen($id)>0){
                     $table .= sprintf('<tr class="%s" onclick="pricelistgrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');'
                            . 'divideCheck(\'qty\',\'unitprice\',\'sp\');">'
                             . '<td>%s</td>'
                             . '<td>%s</td>'
                             . '<td>%s 1 x %s </td>'
                             . '<td class="number">%s</td>'
                             . '<td class="number">%s</td>'
                             . '<td class="number">%s</td>'
                             . '<td>%s</td>'
                             . '<td class="number">%s</td>'
. '</tr>',
                             $rowClass,
             $itemcode,$itemcode,$stockname,$qty,$price,$unitname,$Rid,$ContainerName,
             $itemcode,$stockname,$unitname,$qty,$price,($price/$qty),$tatVal,
             $this->PriceListStatus($row),
                             '<a href="'.$pge.'?delid='.$Rid.'&cu='.$id.'" onclick="return confirm(\'Do you want to delete this price item ?\');"><img src="'.$RootPath.'/css/xenos/images/cross.png"></a>');   
                  }else{
                     $table .= sprintf('<tr class="%s" onclick="pricelistgrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');'
                            . 'divideCheck(\'qty\',\'unitprice\',\'sp\');">'
                            . '<td>%s</td>'
                             . '<td>%s</td>'
                             . '<td>%s 1 x %s </td>'
                             . '<td class="number">%s</td>'
                             . '<td class="number">%s</td>'
                             . '<td class="number">%s</td>'
                             . '<td>%s</td>'
                             . '<td>%s</td>'
. '</tr>',
                             $rowClass,
                             $itemcode,$itemcode,$stockname,$qty,$price,$unitname,$Rid,$ContainerName,
                              $itemcode,$stockname,$unitname,$qty,$price,($price/$qty),$tatVal,"","");
                }
                
                }
               
               
            $table .='</tbody><tfoot><tr>'
                    . '<td></td>'
                    . '<td></td>'
                    . '<td></td>'
                    . '<td></td>'
                    . '<td></td>'
                    . '<td></td>'
                    . '<td></td>'
                    . '<td></td>'
                    . '</tr></tfoot></table>';
            
            echo $table;
     }
     
     function deleteRow($rowid){
        if(mb_strwidth($rowid)>0){
         unset($_SESSION['price_list'][$rowid]);
            unset($_POST['stockitemcode']);
            unset($_POST['qty']);
            unset($_POST['CustomerID']);
            unset($_POST['units']);
            unset($_POST['sp']);
         }
     }
     
      function GetStockdetails($itemcode){
          global $db;
          
          $ResultIndex = DB_query("SELECT 
                         stockmaster.barcode,
                         stockmaster.itemcode,
                         stockmaster.descrip ,
                         stockmaster.averagestock,
                         stockmaster.partperunit
                   FROM stockmaster 
                  where stockmaster.itemcode='".$itemcode."'", $db);
          
          $stocklist=DB_fetch_row($ResultIndex);
          $this->stocklist['barcode'] = $stocklist[0];
          $this->stocklist['itemcode'] = $stocklist[1];
          $this->stocklist['stockname'] = $stocklist[2];
          $this->stocklist['averagestock'] = $stocklist[3];
          $this->stocklist['partperunit'] = $stocklist[4];
          
          return $this->stocklist;
          
      }

      function getPriceById($id) {
          global $db;
          $id = (int)$id;
          $res = DB_query("SELECT id, stockcode, price, tat, units_code, quantity FROM PriceList WHERE id='$id'", $db);
          return DB_fetch_assoc($res);
      }
     
     function gethashcode($itemcode,$qty,$customercode){
         $return='';
         if(mb_strlen($itemcode)>0){
             $hash=trim($itemcode).trim($customercode).$qty;
             $return=CryptPass($hash);
         }
          return $return;
     }
     
      function Getitems($itemcode='',$qty=1,$unitscode='',$price=0,$tatValue){
          global $db;
         
          if(mb_strlen($itemcode)>0){
             $this->rowid = $this->gethashcode($itemcode,$qty,"");
             $StockList = $this->GetStockdetails($itemcode);
             $unitscode = $unitscode ?: ($StockList['si'] ?? '');
             $packCode = $StockList['container'] ?? '';
             $_SESSION['price_list'][$this->rowid] = array('line_no'=>$this->rowid,
                      'itemcode'=>$StockList['itemcode'],
                       'barcode'=>$StockList['barcode'],
                       'stockname'=>$StockList['stockname'],
                       'unitcode'=>$unitscode,
                       'packing'=>$qty,
                       'sellingprice'=>$price,
                       'packCode'=>$packCode,
                       'tat'=>$tatValue);
         }
          $this->showtable();
      }
         
      function GetCustomizeditems($itemcode='',$qty=1,$customercode='',$unitscode='',$price=0,$id){
          global $db;
          
          $SQL=sprintf("select units_code from PriceList where id='%s'",$id);
          $ResultIndex=DB_query($SQL,$db);
          $row = DB_fetch_row($ResultIndex);
          $unitscode = trim($row[0]);
          
          if(mb_strlen($itemcode)>0 and mb_strlen($customercode)>0){
             $this->rowid = $this->gethashcode($itemcode,$qty,$customercode);
             $StockList = $this->GetStockdetails($itemcode);
             // Read TAT from POST (submitted via form), fallback to 0
             $tatFromPost = isset($_POST['tat']) ? (int)$_POST['tat'] : 0;
             $_SESSION['price_list'][$this->rowid]= array(
                       'line_no'=>$this->rowid,
                       'itemcode'=>$StockList['itemcode'],
                       'barcode'=>$StockList['itemcode'],
                       'stockname'=>$StockList['stockname'],
                       'unitcode'=>$unitscode,
                       'customercode'=>$customercode,
                       'packing'=>$qty,
                       'parentID'=>$id,
                       'sellingprice'=>$price,
                       'tat'=>$tatFromPost);
         }elseif(mb_strlen($customercode)>0){
            echo '<script type="text/javascript">SmartDialog.warning("Check if you have selected a Stock Item", "Warning");</script>';
         }
         
          $this->showtable();
      }
         
        
        
 
     function showtable(){
        global $db;
          
        
            foreach ($_SESSION['price_list'] as $lineno => $rowvalue) {
                $U = $rowvalue['unitcode'];
                $unitID = trim($rowvalue['unitcode']);
                $BC = trim($rowvalue['barcode']);
                $itemcode  = $rowvalue['itemcode'];
                $stockname = $rowvalue['stockname'];
                $sellprice = $rowvalue['sellingprice'];
                $tat = $rowvalue['tat'];
 
                $rowsBuff[$lineno] = sprintf('<tr onclick="limspricelistgrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');'
                        . 'divideCheckList(\'qty\',\'unitprice\',\'sp\');">'
                        . '<td>%s</td><td>%s</td><td class="number">%s </td>'
                        . '<td class="number">%s Days</td></tr>',
                        $itemcode,$BC,$stockname,1,$sellprice,$lineno,
                        $itemcode,$stockname,$sellprice,$tat);
                $itemByLine[$lineno] = trim($itemcode);
                
            }
            
            if (!isset($rowsBuff)) { $rowsBuff = array(); $itemByLine = array(); }
            $keyByLine = array_flip($itemByLine);
            $linenoOrder = array_keys($_SESSION['price_list']);
            $bundleLines = array();
            foreach ($itemByLine as $il => $ic) {
                if (preg_match('/^TS\d{4}$/', $ic)) $bundleLines[$il] = $ic;
            }
            $memberOf = array();
            if (!empty($bundleLines)) {
                $catIn = array();
                foreach ($bundleLines as $bc) $catIn[] = "'" . mysqli_real_escape_string($db, $bc) . "'";
                $memRes = DB_query("SELECT dt.categoryid, dt.itemcode FROM discounttable dt JOIN stockmaster sm ON sm.itemcode = dt.itemcode WHERE dt.categoryid IN (" . implode(',', $catIn) . ") AND dt.is_active = 1 AND (sm.inactive = 0 OR sm.inactive IS NULL) ORDER BY sm.descrip", $db);
                while ($mm = DB_fetch_array($memRes)) $memberOf[trim($mm['itemcode'])] = trim($mm['categoryid']);
            }
            if (!defined('STDGROUP_CSS')) {
                define('STDGROUP_CSS', 1);
                echo '<style>.std-group td{background:#edf2fb !important;font-weight:bold;} .std-group-item td:first-child{padding-left:30px !important;}</style>';
            }
            $orderedRows = array();
            $used = array();
            foreach ($linenoOrder as $il) {
                if (isset($used[$il])) continue;
                $used[$il] = 1;
                if (isset($bundleLines[$il])) {
                    $this->htmltable .= str_replace('<tr onclick="limspricelistgrid', '<tr class="std-group" onclick="limspricelistgrid', $rowsBuff[$il]);
                    foreach ($memberOf as $mic => $gcat) {
                        if ($gcat === $bundleLines[$il] && isset($keyByLine[$mic]) && !isset($used[$keyByLine[$mic]])) {
                            $this->htmltable .= str_replace('<tr onclick="limspricelistgrid', '<tr class="std-group-item" onclick="limspricelistgrid', $rowsBuff[$keyByLine[$mic]]);
                            $used[$keyByLine[$mic]] = 1;
                        }
                    }
                } else {
                    $this->htmltable .= $rowsBuff[$il];
                }
            }
            
        echo $this->htmltable;

    }
    
    
    
 }
 
 class Pos {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        stockmaster.partperunit,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        cv.vat as CVAT,
                        stockmaster.sellingprice
                  FROM stockmaster left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc
                  where stockmaster.itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['partperunit']= $stocklist[5];
         $this->stocklist['vat']= $stocklist[6];
         $this->stocklist['container']= $stocklist[7];
         $this->stocklist['packname']= $stocklist[8];
         $this->stocklist['CVAT']= $stocklist[9];
         $this->stocklist['price']= $stocklist[10];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price,$price_empties){
         $StockList = $this->GetStockdetails($itemcode);
         $UOM = $_POST['UOM']['$itemcode'];
         $line_No = $this->LineNo();
         
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$UOM,
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$StockList['price'],
                  'emptycost'=>$price_empties,
                  'totalemptycost'=>$total_empties,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['sales_orders_index'];
     }
     
     function neworder(){
         $_SESSION['sales_orders']=array();
         $_SESSION['sales_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['sales_orders_index']++;
    }
    
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;
        $TotalInvoiceQty = 0;

            $results=DB_query("SELECT 
                itemcode,
                creditlimit,
                customer,
                phone ,
                email ,
                city ,
                country,
                curr_cod,
                customerposting,
                salesman,
                VATinclusive,
                IsTaxed
                FROM debtors join postinggroups on code=customerposting 
                   where itemcode='".$_POST['CustomerID']."'", $db);
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[8];
            $this->VATinclusive = $debtorsrow[10];
            $this->IsTaxed = $debtorsrow[11];

            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
                $salesprice=0; $PriceInPricelist=0;
    
                $PriceInPricelist = $rowvalue['salesprice'];
                $line_no = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                
                $containercode = trim($stocklist['container']);
                $rate = ($this->IsTaxed==false)?0:$stocklist['vat'];
    
                if(isset($_POST['quantity'][$line_no])){
                    $qty= $_POST['quantity'][$line_no];
                }else{
                    $qty=.0;
                }
                
                
                if(isset($_POST['TradeDiscount']) and mb_strlen($_POST['TradeDiscount'])>0 ){
                    $ResultIndex=DB_query(sprintf("SELECT Rate FROM Discouts where rowid='%s'",$_POST['TradeDiscount']), $db);
                    $dicrow=DB_fetch_row($ResultIndex);

                    $salesprice= $rowvalue['salesprice']*(1-$dicrow[0]) ;
                    DB_free_result($ResultIndex);
                }else{
                    if(isset($_POST['salesprice'][$line_no]) and $_POST['salesprice'][$line_no]>0){
                        $salesprice=(float)$_POST['salesprice'][$line_no];
                    }else{
                         $salesprice=$rowvalue['salesprice'];
                    }
                }
                
                
                if($_POST['UOM'][$line_no]=='fulqty'){
                     $baseamount = ($salesprice * ($qty * $stocklist['partperunit']));
                     $TotalInvoiceQty += ($qty * $stocklist['partperunit']);
                     
                       if(isset($_POST['emptycost'][$line_no])){
                         $emptycost=$_POST['emptycost'][$line_no];
                       }
                       
                       $emptyunits     = $qty;
                       $totalemptycost = ($emptycost * $emptyunits);             
                       $crate          = $stocklist['CVAT'];

                        if($VATinclusive==true){
                             $cvatamount =  $totalemptycost * ($crate/100+$crate);
                             $cgrossamount=  $totalemptycost ;
                        }else{
                            $cvatamount =  $totalemptycost * ($crate/100);
                            $cgrossamount=  $totalemptycost + $cvatamount;
                        }

                 }else{
                   $baseamount= ($salesprice * $qty);
                   $TotalInvoiceQty +=$qty;
                }
          
      
        
            if($this->VATinclusive==true){
                $netamount = $baseamount /  (($rate+100)/100);
                $vatamount = $baseamount - $netamount;
                $grossamount=$baseamount ;
            }else{
                $vatamount  = $baseamount * ($rate/100);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
    
                $netamount += $totalemptycost;
                $vatamount += $cvatamount;
                $grossamount +=$cgrossamount;

                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                                
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/>
                 <input type="hidden"  name="PriceInPricelist['.$line_no.']" value="'.$PriceInPricelist.'"/></td>'
              . '<td><input type="text" class="number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="10"/></td>'
              . '<td><input type="text" class="number" name="emptycost['.$line_no.']" value="'.$emptycost.'" size="5"/>'
              . '<input type="hidden" name="emptycode['.$line_no.']" value="'.$containercode.'" size="1"/>'
              . '<input type="hidden" name="emptyunits['.$line_no.']" value="'.$emptyunits.'" size="1"/></td>'
              . '<td><input type="text" class="number" name="totalemptycost['.$line_no.']" value="'.$totalemptycost.'" readonly="readonly" size="5"/></td>'
              . '<td><input type="text" class="number" name="netamount['.$line_no.']" value="'.$netamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="vatamount['.$line_no.']" value="'.$vatamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="8"/></td>'
             . '</tr>',trim($stocklist['barcode']), 
                        trim($stocklist['stockname']),
                        CreateUnits($itemcode,$line_no), 
                        number_format($stocklist['partperunit'],0),
                        number_format($stocklist['averagestock'],2));
            }
            
            $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number" colspan="9">Total Order + Cost of applicable Empties</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>',number_format($runningnettotal,2), number_format($runningvattotal,2),  number_format($runninggrosstotal,2));
            
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            $_SESSION['TotalInvoiceQty']=$TotalInvoiceQty;
            
        echo $this->htmltable;

    }
       
 }
 
 class serviceinvoice {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        stockmaster.partperunit,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        cv.vat as CVAT
                  FROM stockmaster left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc
                  where stockmaster.itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['vat']= $stocklist[6];
         $this->stocklist['CVAT']= $stocklist[9];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price){
         $StockList = $this->GetStockdetails($itemcode);
         $UOM       = $_POST['UOM']['$itemcode'];
         $line_No   = $this->LineNo();
         
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$price,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['sales_orders_index'];
     }
     
     function neworder(){
         $_SESSION['sales_orders']=array();
         $_SESSION['sales_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['sales_orders_index']++;
    }
    
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;

            $results=DB_query("SELECT 
                itemcode,
                creditlimit,
                customer,
                phone ,
                email ,
                city ,
                country,
                curr_cod,
                customerposting,
                salesman,
                VATinclusive,
                IsTaxed
                FROM debtors join postinggroups on code=customerposting 
                where itemcode='".$_POST['CustomerID']."'", $db);
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[8];
            $this->VATinclusive = $debtorsrow[10];
            $this->IsTaxed = $debtorsrow[11];

            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
                $line_no = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate = ($this->IsTaxed==false)?0:$stocklist['vat'];
    
                if(isset($_POST['quantity'][$line_no])){
                    $qty= $_POST['quantity'][$line_no];
                }else{
                    $qty=1;
                }
        
                if(isset($_POST['salesprice'][$line_no])){
                    $salesprice= $_POST['salesprice'][$line_no] ;
                }else{
                    $salesprice=.0;
                }
    
          
               $baseamount= ($salesprice * $qty);
               
          
    
            if($this->VATinclusive==true){
                $netamount = $baseamount /  (($rate+100)/100);
                $vatamount = $baseamount - $netamount;
                $grossamount=$baseamount ;
            }else{
                $vatamount  = $baseamount * ($rate/100);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
    
 
                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="cell integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden" name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td><input type="text" class="cell number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td><input type="text" class="cell number" name="netamount['.$line_no.']" value="'.$netamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="cell number" name="vatamount['.$line_no.']" value="'.$vatamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="cell number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="8"/></td>'
             . '</tr>',trim($stocklist['barcode']),trim($stocklist['stockname']),CreateUnits($itemcode,$line_no));
            }
            
            $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number" colspan="5">Total Order</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>',
                    number_format($runningnettotal,2),
                    number_format($runningvattotal,2), 
                    number_format($runninggrosstotal,2));
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
       
 }
 
 class Labtests {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
    
    
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        stockmaster.partperunit,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        cv.vat as CVAT
                  FROM stockmaster left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc
                  where stockmaster.itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['partperunit']= $stocklist[5];
         $this->stocklist['vat']= $stocklist[6];
         $this->stocklist['container']= $stocklist[7];
         $this->stocklist['packname']= $stocklist[8];
         $this->stocklist['CVAT']= $stocklist[9];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty){
         $StockList = $this->GetStockdetails($itemcode);
         $line_No   = $this->LineNo();
         $store     = $_POST['location'][$line_no];
         $UOM       = $_POST['UOM'][$line_no];
          
         $_SESSION['labreagents'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$UOM,
                  'partperunit'=>$StockList['partperunit'],
                  'quantity'=>$qty
                  );
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['labreagents_index'];
     }
     
     function neworder(){
         $_SESSION['labreagents'] = array();
         $_SESSION['labreagents_index'] = 0;
     }
     
     function Nextorder(){
         $_SESSION['labreagents_index']++;
    }
    
    function showtable(){
        global $db;
               
        foreach ($_SESSION['labreagents'] as $lineno => $rowvalue) {
                $line_no   = $rowvalue['line_no'];
                $itemcode  = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $store     = $_POST['location'][$line_no];
                $UOM       = $_POST['UOM'][$line_no];
                
                if(isset($_POST['quantity'][$line_no])){
                    $qty = $_POST['quantity'][$line_no];
                }else{
                    $qty = 0;
                }
                 
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '</tr>',trim($stocklist['barcode']), 
                        trim($stocklist['stockname']),
                        CreateUnits($itemcode,$line_no), 
                        number_format($stocklist['partperunit'],0),
                        createstores($line_no),
                        getbalance($itemcode,$store,$UOM));
            }
        echo $this->htmltable;

    }
       
 }
 
 class Issues {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        stockmaster.partperunit,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        cv.vat as CVAT
                  FROM stockmaster left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                  left join inventorypostinggroup ci on c.postinggroup=ci.code
                  left join vatcategory cv on ci.vatcategory=cv.vatc
                  where stockmaster.itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['partperunit']= $stocklist[5];
         $this->stocklist['vat']= $stocklist[6];
         $this->stocklist['container']= $stocklist[7];
         $this->stocklist['packname']= $stocklist[8];
         $this->stocklist['CVAT']= $stocklist[9];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price,$price_empties){
         $StockList = $this->GetStockdetails($itemcode);
         $UOM = $_POST['UOM']['$itemcode'];
         $line_No = $this->LineNo();
         
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$UOM,
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$price,
                  'emptycost'=>$price_empties,
                  'totalemptycost'=>$total_empties,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['sales_orders_index'];
     }
     
     function neworder(){
         $_SESSION['sales_orders']=array();
         $_SESSION['sales_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['sales_orders_index']++;
    }
    
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;
            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;$vatamount=0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
                $line_no = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $location = $_POST['location'][$line_no];
                $UOM = $_POST['UOM'][$line_no];
             
                if(isset($_POST['quantity'][$line_no])){
                    $qty= $_POST['quantity'][$line_no];
                }else{
                    $qty=.0;
                }
        
                if(($_POST['salesprice'][$line_no]) >0){
                    $salesprice= $_POST['salesprice'][$line_no] ;
                }else{
                    $salesprice=$stocklist['averagestock'];
                }
    
                if($_POST['UOM'][$line_no]=='fulqty'){
                     $baseamount = ($salesprice * ($qty * $stocklist['partperunit']));
                }else{
                     $baseamount= ($salesprice * $qty);
                }
          
                $grossamount= $baseamount ;
                $netamount  = $baseamount;
                
                $netamount += $totalemptycost;
                $vatamount += $cvatamount;
                $grossamount +=$cgrossamount;

                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                if(isset($location)){
                     $StockBalance = getbalance($stocklist['itemcode'],$location,$UOM)-$qty;
                 }else{
                     $StockBalance= -0;
                 }
                 
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td><input type="text" class="number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="8"/></td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="number" name="balance['.$line_no.']" value="%s" readonly="readonly" size="8"/></td>'
              . '</tr>',trim($stocklist['barcode']), 
                        trim($stocklist['stockname']),
                        CreateUnits($itemcode,$line_no), 
                        number_format($stocklist['partperunit'],0),
                        number_format($stocklist['averagestock'],2),
                        createstores($line_no) ,
                        $StockBalance);
            }
            
            $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number" colspan="7">Total Issues Cost</td>'
              . '<td class="number">%s</td><td colspan="2"></td>'
              . '</tr></tfoot>', number_format($runninggrosstotal,2));
            
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
       
 }
 
 class ApprovedIssues {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        vatcategory.vat
                  FROM stockmaster  
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
                   where stockmaster.itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['vat']= $stocklist[5];
        
         return $this->stocklist;
         
     }
     
      function Getitems($row){
          $StockList = $this->GetStockdetails($row['code']);
          $line_No = trim($row['code']);
          
          $_SESSION['sales_orders'][$line_No]=
             array('line_no'=>$line_No,
                   'itemcode'=>$StockList['itemcode'],
                   'barcode'=>$StockList['barcode'],
                   'stockname'=>$StockList['stockname'],
                   'si'=>$row['unitofmeasure'],
                   'partperunit'=>$row['PartPerUnit'],
                   'averagestock'=>$StockList['averagestock'],
                   'quantity'=>$row['Quantity'],
                   'salesprice'=>$row['UnitPrice'],
                   'grossamount'=>$row['invoiceamount']);
          
          // Also populate PE_orders for SaveStockIssues.inc compatibility
          $_SESSION['PE_orders'][$line_No]=
             array('line_no'=>$line_No,
                   'itemcode'=>$StockList['itemcode'],
                   'barcode'=>$StockList['barcode'],
                   'stockname'=>$StockList['stockname'],
                   'quantity'=>$row['Quantity'],
                   'averagestock'=>$StockList['averagestock'],
                   'grossamount'=>$row['invoiceamount'],
                   'location'=>'',
                   'stkbalance'=>0,
                   'batchrowid'=>0);
          
          $this->showtable();
      }
     
      
     
     function neworder(){
         $_SESSION['sales_orders']=array();
         $_SESSION['sales_orders_index']=0;
         
     }
     
      
    
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runninggrosstotal = 0;
            
        foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
            $line_no   = $rowvalue['line_no'];
            $itemcode  = $rowvalue['itemcode'];
            $stocklist = $this->GetStockdetails($itemcode);
            $location  = $_POST['location'][$line_no];
            $package   = $rowvalue['partperunit'];
            $qty       = (float)$rowvalue['quantity'];
            $salesprice= (float)$rowvalue['salesprice'];

            if ($package > 1) {
                $baseamount = $salesprice * ($qty * $package);
            } else {
                $baseamount = $salesprice * $qty;
            }

            $grossamount = $baseamount;
            $netamount   = $baseamount;
            $runningnettotal += $netamount;
            $runninggrosstotal += $grossamount;

            // Available balance across all batches
            $balResult = DB_query(
                "SELECT COALESCE(SUM(StockIn - IFNULL(StockOut,0)), 0)
                 FROM StockRegister
                 WHERE itemcode = '$itemcode'",
                $db
            );
            $balRow = DB_fetch_row($balResult);
            $totalAvailable = (float)$balRow[0];

            // Batch dropdown (FIFO-ordered available stock)
            $batchOptions = '<option value="0">Auto (FIFO)</option>';
            $batchResult = DB_query(
                "SELECT rowid, GRN,
                        (StockIn - IFNULL(StockOut,0)) AS StockBalance,
                        serial
                 FROM StockRegister
                 WHERE itemcode = '$itemcode'
                   AND (StockIn - IFNULL(StockOut,0)) > 0
                 ORDER BY rowid ASC",
                $db
            );
            while ($batchRow = DB_fetch_array($batchResult)) {
                $serialTag = !empty($batchRow['serial']) ? ' [' . $batchRow['serial'] . ']' : '';
                $batchOptions .= '<option value="' . $batchRow['rowid'] . '">'
                    . $batchRow['GRN'] . $serialTag . ' | Bal: ' . number_format($batchRow['StockBalance'], 0)
                    . '</option>';
            }

            $afterBalance = $totalAvailable - $qty;

            $this->htmltable .= sprintf('<tr>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><select name="batchrowid['.$line_no.']">%s</select></td>'
                . '<td>%s</td>'
                . '<td class="number">%s</td>'
                . '<td>%s</td>'
                . '<td class="number">%s</td>'
                . '</tr>',
                $stocklist['barcode'],
                $stocklist['stockname'],
                number_format($stocklist['averagestock'], 2),
                $batchOptions,
                number_format($qty, 0),
                number_format($salesprice, 2),
                createstores($line_no),
                ($afterBalance < 0
                    ? '<span style="color:red">' . number_format($afterBalance, 0) . '</span>'
                    : number_format($afterBalance, 0))
            );

            // Update PE_orders with current data for SaveStockIssues
            $_SESSION['PE_orders'][$lineno]['location'] = $location;
            $_SESSION['PE_orders'][$lineno]['stkbalance'] = $afterBalance;
            
            $selectedBatch = isset($_POST['batchrowid'][$line_no]) ? (int)$_POST['batchrowid'][$line_no] : 0;
            $_SESSION['PE_orders'][$lineno]['batchrowid'] = $selectedBatch;
        }
            
        $this->htmltable .= sprintf('<tfoot><tr>'
            . '<td class="number" colspan="6">' . _('Total Issues Cost') . '</td>'
            . '<td class="number">%s</td><td></td>'
            . '</tr></tfoot>',
            number_format($runninggrosstotal, 2));
            
        $_SESSION['Grossamounttotal'] = $runninggrosstotal;
            
        echo $this->htmltable;
    }
       
 }


function applyCategoryDiscounts(){
    global $db;

    $catId = $_SESSION['sales_orders_category'] ?? '';

    if (empty($catId) || !isset($_SESSION['sales_orders']) || empty($_SESSION['sales_orders'])) {
        foreach ($_SESSION['sales_orders'] as $key => $item) {
            $_SESSION['sales_orders'][$key]['discountpercent'] = 0;
        }
        return;
    }

    $itemcodes = array();
    foreach ($_SESSION['sales_orders'] as $item) {
        $itemcodes[] = trim($item['itemcode']);
    }
    $itemcodes = array_unique(array_filter($itemcodes));
    if (empty($itemcodes)) return;

    $escaped = array_map(function($c) use ($db) {
        return "'" . mysqli_real_escape_string($db, $c) . "'";
    }, $itemcodes);

    $catCheck = DB_query("SELECT DISTINCT sm.category FROM stockmaster sm WHERE sm.itemcode IN (" . implode(',', $escaped) . ")", $db);
    $actualCats = array();
    while ($r = DB_fetch_array($catCheck)) {
        if (!empty($r['category'])) $actualCats[] = $r['category'];
    }
    $actualCats = array_unique($actualCats);

    if (count($actualCats) !== 1 || reset($actualCats) !== $catId) {
        $_SESSION['sales_orders_category'] = '';
        foreach ($_SESSION['sales_orders'] as $key => $item) {
            $_SESSION['sales_orders'][$key]['discountpercent'] = 0;
        }
        return;
    }

    $discResult = DB_query("SELECT itemcode, discount_percent FROM discounttable
                            WHERE categoryid='" . mysqli_real_escape_string($db, $catId) . "'
                            AND itemcode IN (" . implode(',', $escaped) . ") AND is_active = 1", $db);
    $discounts = array();
    while ($r = DB_fetch_array($discResult)) {
        $discounts[trim($r['itemcode'])] = (float)$r['discount_percent'];
    }

    foreach ($_SESSION['sales_orders'] as $key => $item) {
        $ic = trim($item['itemcode']);
        $_SESSION['sales_orders'][$key]['discountpercent'] = $discounts[$ic] ?? 0;
    }
}


 ?>
