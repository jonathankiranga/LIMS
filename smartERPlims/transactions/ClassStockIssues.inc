<?php

class Requsets{
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $TotalProduction;
     var  $tankStore;
     var  $productionTankStore;
     var  $proitemcode;
     var  $work_orders;
     
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
    
     Function ToStoreTankForItem($ItemCode){
        global $db;

        $line='';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            $this->productionTankStore="S";
            $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
            while($rows= DB_fetch_array($REsults)){
                 $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['ProdStore']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
             }
        } else {
            $this->productionTankStore="T";
            $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
              $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['ProdStore']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
            }
       }
        return $line; 
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
                         stockmaster.sellingprice ,
                         stockmaster.isstock_3,
                         stockmaster.requireserial
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
          $this->stocklist['charge'] =(int) $stocklist[11];
          $this->stocklist['requireserial'] = (int)$stocklist[12];
         
         return $this->stocklist;
         
     }
     
function Getitems($itemcode,$qty,$uom,$sp=0,$batchrowid=0){
          $StockList = $this->GetStockdetails($itemcode);
           
          if(mb_strwidth($StockList['itemcode'])>0){
                 $id = trim($StockList['itemcode']);
                 $serial = '';
                 if ($batchrowid > 0) {
                     $sr = DB_query("SELECT serial FROM StockRegister WHERE rowid=" . (int)$batchrowid, $GLOBALS['db']);
                     if ($srRow = DB_fetch_row($sr)) {
                         $serial = trim($srRow[0]);
                     }
                 }
                 $requireserial = (int)$StockList['requireserial'];
                 if ($requireserial) {
                     $qty = 1;
                 }
                 $_SESSION['PE_orders'][$id]= array('line_no'=>$id,
                          'itemcode'=>$id,
                          'barcode'=>$StockList['barcode'],
                          'stockname'=>$StockList['stockname'],
                          'uom' => $uom,
                          'averagestock'=>$sp,
                          'quantity'=>$qty,
                          'batchrowid'=>$batchrowid,
                          'serial'=>$serial,
                          'requireserial'=>$requireserial,
                          'charge'=>(int)$StockList['charge'],
                          'CVAT'=>$StockList['CVAT'],
                          'vat'=>(float)$StockList['vat']);
          }
           
          $this->showtable();
      }
      
     function neworder(){
         $_SESSION['PE_orders']=array();unset($_SESSION['htmltable']);
     }
     
       
    function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['PE_orders'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
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

    function tankresults($store,$item){
      global $db;
       $return ='';
       
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$item);
         $ResultIndex = DB_query($sql, $db);
        $Stores = DB_fetch_row($ResultIndex);
       if($Stores[0]>0){
            $sql=sprintf("SELECT balance FROM ProductionUnit where tankname='%s' and itemcode='%s'",$store,$item);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $StockBalanceInloose = $row[0];
            $return= sprintf('%f',(int)$StockBalanceInloose);
         }else{
            $return= '';
         }
         return $return;
   }
   
   
   
    function showtable(){
        global $db;
        
        $runningnettotal = 0; $totalProduction = 0; $percentTotal=0;
            
       $sqldebtors=DB_query("SELECT itemcode ,creditlimit,customer
      ,phone ,email ,city ,country,curr_cod,customerposting
      ,salesman,VATinclusive FROM debtors join postinggroups on code=customerposting
       where itemcode='".$_POST['CustomerID']."'", $db);
        $debtorsrow = DB_fetch_row($sqldebtors);
        $customerposting = $debtorsrow[8];
        $VATinclusive =(int) $debtorsrow[10];
        
            foreach ($_SESSION['PE_orders'] as $lineno => $rowvalue) {
                $line_no  = trim($rowvalue['line_no']);
                $itemcode = $rowvalue['itemcode'];
                $charge   = $rowvalue['charge'];
                $rate     = $rowvalue['vat'];
               
                $stocklist = $this->GetStockdetails($itemcode);
                $this->IsTankOrStore($itemcode);
                 
                 $UOM = $rowvalue['uom'];  
                 $location = $_POST['location'][$line_no];
                
                if(mb_strlen($location)==0){$location=$_SESSION['Stores'][0]['code']; }
                
                $averagestock = (float) $rowvalue['averagestock'];
                $qty = (float) $rowvalue['quantity'] ;
                $baseamount  = (float) ($averagestock * $qty );
                 
                
                 if($VATinclusive==1){
                    $netamount = $baseamount /  (($rate+100)/100);
                    $vatamount = $baseamount - $netamount;
                    $grossamount=$baseamount ;
                }else{
                    $vatamount  = $baseamount * ($rate/100);
                    $grossamount= $baseamount + $vatamount;
                    $netamount  = $baseamount;
                }
              
                $runningnettotal += $grossamount;
                 
                 $code    = trim($stocklist['itemcode']);
                 $ID      = trim($stocklist['itemcode']);
                 $BC      = trim($stocklist['barcode']);
                 $NAME    = trim($stocklist['stockname']);
                 
                 
              if($charge==0){
                  
                    if($this->tankStore=="T"){
                        $StockBalanceInloose =(int) getTankbalance($location,$code);
                        $_SESSION['PE_orders'][$code]['Balance']= $StockBalanceInloose;
                    }elseif($this->tankStore=="S"){
                        $StockBalanceInloose=(int) getbalance($code,$location,'loosqty');
                        $_SESSION['PE_orders'][$code]['Balance']=$StockBalanceInloose;
                    }
                    
              } else {
                    $this->tankStore="N";
                    $StockBalanceInloose =(int)$qty;
                    $_SESSION['PE_orders'][$code]['Balance']= $StockBalanceInloose;
              }   
              
                $arrayi  = $_SESSION['PE_orders'][$lineno];
                $trastype= trim($_POST['transactiontype']);
             
                $_SESSION['PE_orders'][$lineno] = array_replace_recursive($arrayi,
                array('tankstore'=>$this->tankStore,'netamount'=>$netamount,
                    'vatamount'=>$vatamount,'grossamount'=>$grossamount,
                    'stkbalance'=>( (($trastype=='1')?
                        ($_SESSION['PE_orders'][$code]['Balance'] - $qty):
                        ($_SESSION['PE_orders'][$code]['Balance'] + $qty))
                        )));
            
                $stockbalance= (($trastype=='1')?($_SESSION['PE_orders'][$code]['Balance'] - $qty): ($_SESSION['PE_orders'][$code]['Balance'] + $qty));
                        
                    
                    
                $serialDisplay = !empty($_SESSION['PE_orders'][$code]['serial'])
                    ? '<span class="serial-badge">' . htmlspecialchars($_SESSION['PE_orders'][$code]['serial']) . '</span>'
                    : '<span class="serial-na">&mdash;</span>';
                $this->htmltable .= sprintf('<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><input type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
                . '<td><select name="location['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.$stockbalance.'"  size="8"/></td>'
                . '<td>%s</td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.$baseamount.'"  size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($netamount,2).'" readonly="readonly" size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="vatamount['.$line_no.']" value="'.number_format($vatamount,2).'" readonly="readonly" size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="grossamount['.$line_no.']" value="'.number_format($grossamount,2).'" readonly="readonly" size="8"/></td>'
                . '</tr>',$ID,$BC,$NAME,$qty,$serialDisplay,trim($stocklist['barcode']),trim($stocklist['stockname']),$this->MakeStoreTankForItem($ID,$line_no),getGriduom($line_no,$UOM ));
           }
            
              $this->htmltable .= sprintf('<tfoot><tr><td colspan="10"><td class="number cell">%s</td></tr></tfoot>',number_format($runningnettotal,2));
              
              $_SESSION['hideOrShow']=($this->tankStore=='')?"button":"submit";
              $_SESSION['htmltable']=$this->htmltable;
    }
    
}

class Transfers{
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $TotalProduction;
     var  $tankStore;
     var  $productionTankStore;
     var  $proitemcode;
     var  $work_orders;
     
     Function MakeStoreTankForItem($ItemCode,$key,$Postlocation){
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
                     $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$Postlocation[$key]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  }
           }
       }else{
           $this->tankStore="T";
           $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$Postlocation[$key]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
           }
       }
        return $line; 
    }
    
     Function ToStoreTankForItem($ItemCode){
        global $db;

        $line='';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            $this->productionTankStore="S";
            $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
            while($rows= DB_fetch_array($REsults)){
                 $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['ProdStore']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
             }
        } else {
            $this->productionTankStore="T";
            $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
              $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['ProdStore']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
            }
       }
        return $line; 
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
     
     function Getitems($itemcode,$qty,$uom,$inPacksOf){
         $StockList = $this->GetStockdetails($itemcode);
          
         if(mb_strwidth($StockList['itemcode'])>0){
                $id = trim($StockList['itemcode']);
                
                $_SESSION['TransferFromStore'][$id]= array('line_no'=>$id,
                         'itemcode'=>$id,
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>$uom,'InPacks'=>$inPacksOf,
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$qty);
                
                $_SESSION['TransferToStore'][$id]= array('line_no'=>$id,
                         'itemcode'=>$id,
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>$uom,'InPacks'=>$inPacksOf,
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$qty);
         }
                
         $this->showtable();
                   
       
      }
      
     function neworder(){
         $_SESSION['TransferToStore']=array();
         $_SESSION['TransferFromStore']=array();
         unset($_SESSION['htmltable']);
     }
     
     function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['TransferToStore'][$this->deleteID]);
            unset($_SESSION['TransferFromStore'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
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

     function tankresults($store,$item){
      global $db;
       $return ='';
       
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$item);
         $ResultIndex = DB_query($sql, $db);
        $Stores = DB_fetch_row($ResultIndex);
       if($Stores[0]>0){
            $sql=sprintf("SELECT balance FROM ProductionUnit where tankname='%s' and itemcode='%s'",$store,$item);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $StockBalanceInloose = $row[0];
            $return= sprintf('%f',(int)$StockBalanceInloose);
         }else{
            $return= '';
         }
         return $return;
   }
  
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $totalProduction = 0;
        $percentTotal=0;
       
           //  $_SESSION['TransferFromStore']=array();      
         foreach ($_SESSION['TransferFromStore'] as $lineno => $rowvalue) {
                $line_no = trim($rowvalue['line_no']);
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $this->IsTankOrStore($itemcode);
                 
                $UOM = $rowvalue['uom'];  
                $location = $_POST['location_to'][$line_no];
                $averagestock = (float) $rowvalue['averagestock']*$rowvalue['InPacks'];
                $qty = (float) $rowvalue['quantity'] ;
                $baseamount  = (float) ($averagestock * $qty * -1);
                $runningnettotal += $baseamount;
                 
                 $code    = trim($stocklist['itemcode']);
                 $ID      = trim($stocklist['itemcode']);
                 $BC      = trim($stocklist['barcode']);
                 $NAME    = trim($stocklist['stockname']);
                 
              
                    if($this->tankStore=="T"){
                        $StockBalanceInloose =(int) getTankbalance($location,$code);
                        $_SESSION['TransferFromStore'][$code]['Balance']= $StockBalanceInloose;
                    }elseif($this->tankStore=="S"){
                        $StockBalanceInloose=(int) getbalance($code,$location,'loosqty');
                        $_SESSION['TransferFromStore'][$code]['Balance']=$StockBalanceInloose;
                    }
                      
                $arrayi  = $_SESSION['TransferFromStore'][$lineno];
                 
                $_SESSION['TransferFromStore'][$lineno] = array_replace_recursive($arrayi,
                array('tankstore'=>$this->tankStore,'netamount'=>$baseamount,
                    'stkbalance'=>(($_SESSION['TransferFromStore'][$code]['Balance'])-$qty)));
            
                $this->htmltable .= sprintf('<tr>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><input type="text" name="quantity_to['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
                . '<td>From:<select name="location_to['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.(($_SESSION['TransferFromStore'][$code]['Balance'])-$qty).'"  size="8"/></td>'
                . '<td>%s</td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.($averagestock).'"  size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="netamount_to['.$line_no.']" value="'.number_format($baseamount,2).'" readonly="readonly" size="8"/></td>'
                . '</tr>',trim($stocklist['barcode']),trim($stocklist['stockname']),
                $this->MakeStoreTankForItem($ID,$line_no,$_POST['location_to']),getGriduom($line_no,$UOM ));
           }
         //      $_SESSION['TransferToStore']=array();
       
         foreach ($_SESSION['TransferToStore'] as $lineno => $rowvalue) {
                $line_no = trim($rowvalue['line_no']);
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $this->IsTankOrStore($itemcode);
                 
                $UOM = $rowvalue['uom'];  
                $location = $_POST['location'][$line_no];
                $averagestock = (float) $rowvalue['averagestock']*$rowvalue['InPacks'];
                $qty = (float) $rowvalue['quantity'] ;
                $baseamount  = (float) ($averagestock * $qty );
                $runningnettotal += $baseamount;
                 
                 $code    = trim($stocklist['itemcode']);
                 $ID      = trim($stocklist['itemcode']);
                 $BC      = trim($stocklist['barcode']);
                 $NAME    = trim($stocklist['stockname']);
                 
              
                    if($this->tankStore=="T"){
                        $StockBalanceInloose =(int) getTankbalance($location,$code);
                        $_SESSION['TransferToStore'][$code]['Balance']= $StockBalanceInloose;
                    }elseif($this->tankStore=="S"){
                        $StockBalanceInloose=(int) getbalance($code,$location,'loosqty');
                        $_SESSION['TransferToStore'][$code]['Balance']=$StockBalanceInloose;
                    }
                      
                $arrayi  = $_SESSION['TransferToStore'][$lineno];
                 
                $_SESSION['TransferToStore'][$lineno] = array_replace_recursive($arrayi,
                array('tankstore'=>$this->tankStore,'netamount'=>$baseamount,
                    'stkbalance'=>(($_SESSION['TransferToStore'][$code]['Balance'])+$qty)));
            
                $this->htmltable .= sprintf('<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><input type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
                . '<td>To__:<select name="location['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.(($_SESSION['TransferToStore'][$code]['Balance'])+$qty).'"  size="8"/></td>'
                . '<td>%s</td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.($averagestock).'"  size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($baseamount,2).'" readonly="readonly" size="8"/></td>'
                . '</tr>',$ID,$BC,$NAME,$qty,trim($stocklist['barcode']),trim($stocklist['stockname']),
                        $this->MakeStoreTankForItem($ID,$line_no,$_POST['location']),getGriduom($line_no,$UOM));
           }
    
         $this->htmltable .= sprintf('<tfoot><tr><td colspan="7"><td class="number cell">%s</td></tr></tfoot>',number_format($runningnettotal,2));
         $_SESSION['hideOrShow']=($this->tankStore=='')?"button":"submit";
         $_SESSION['htmltable']=$this->htmltable;
    }
    
    
   
     
    
}

class StoreTankCls{
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $TotalProduction;
     var  $tankStore;
     var  $productionTankStore;
     var  $proitemcode;
     var  $work_orders;
     
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
    
     Function ToStoreTankForItem($ItemCode){
        global $db;

        $line='';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            $this->productionTankStore="S";
            $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
            while($rows= DB_fetch_array($REsults)){
                 $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['ProdStore']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
             }
        } else {
            $this->productionTankStore="T";
            $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
              $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['ProdStore']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
            }
       }
        return $line; 
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
     
     function Getitems($itemcode,$qty,$uom){
         $StockList = $this->GetStockdetails($itemcode);
          
         if(mb_strwidth($StockList['itemcode'])>0){
                $id = 0 ;
                $_SESSION['PE_orders'][$id]= array('line_no'=>$id,
                         'itemcode'=>$StockList['itemcode'],
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>$uom,
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$qty);
         }
          
         $this->showtable();
     }
      
     function neworder(){
         $_SESSION['PE_orders']=array();unset($_SESSION['htmltable']);
     }
     
       
    function RemoveOrder(){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['PE_orders'][0]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
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

    function tankresults($store,$item){
      global $db;
       $return ='';
       
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$item);
         $ResultIndex = DB_query($sql, $db);
        $Stores = DB_fetch_row($ResultIndex);
       if($Stores[0]>0){
            $sql=sprintf("SELECT balance FROM ProductionUnit where tankname='%s' and itemcode='%s'",$store,$item);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $StockBalanceInloose = $row[0];
            $return= sprintf('%f',(int)$StockBalanceInloose);
         }else{
            $return= '';
         }
         return $return;
   }
   
   
   
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $totalProduction = 0;
        $percentTotal=0;
            
            foreach ($_SESSION['PE_orders'] as $lineno => $rowvalue) {
                $line_no = trim($rowvalue['line_no']);
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $this->IsTankOrStore($itemcode);
                 
                $UOM = $rowvalue['uom'];  
                $location = $_POST['location'][$line_no];
                
                $averagestock = (float) $rowvalue['averagestock'];
                $qty = (float) $rowvalue['quantity'] ;
                $baseamount  = (float) ($averagestock * $qty );
                $runningnettotal += $baseamount;
                 
                 $code    = trim($stocklist['itemcode']);
                 $ID      = trim($stocklist['itemcode']);
                 $BC      = trim($stocklist['barcode']);
                 $NAME    = trim($stocklist['stockname']);
              
                if($this->tankStore=="T"){
                    $StockBalanceInloose =(int) getTankbalance($location,$code);
                    $_SESSION['PE_orders'][$code]['Balance']= $StockBalanceInloose;
                }elseif($this->tankStore=="S"){
                    $StockBalanceInloose=(int) getbalance($code,$location,'loosqty');
                    $_SESSION['PE_orders'][$code]['Balance']=$StockBalanceInloose;
                }
                      
                $arrayi  = $_SESSION['PE_orders'][$lineno];
                 
                $_SESSION['PE_orders'][$lineno] = array_replace_recursive($arrayi,
                array('tankstore'=>$this->tankStore,'netamount'=>$baseamount,
                    'stkbalance'=>(($_SESSION['PE_orders'][$code]['Balance'])+$qty)));
            
                $this->htmltable .= sprintf('<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td><input type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
                . '<td><select name="location['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.(($_SESSION['PE_orders'][$code]['Balance'])+$qty).'"  size="8"/></td>'
                . '<td>%s</td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.($averagestock*$qty).'"  size="8"/></td>'
                . '<td class="number cell"><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($baseamount,2).'" readonly="readonly" size="8"/></td>'
                . '</tr>',$ID,$BC,$NAME,$qty,trim($stocklist['barcode']),trim($stocklist['stockname']),$this->MakeStoreTankForItem($ID,$line_no),getGriduom($line_no,$UOM ));
           }
            
              $this->htmltable .= sprintf('<tfoot><tr><td colspan="7"><td class="number cell">%s</td></tr></tfoot>',number_format($runningnettotal,2));
              $_SESSION['hideOrShow']=($this->tankStore=='S')?"button":"submit";
              $_SESSION['htmltable']=$this->htmltable;
    }
    
}

class Decanter{
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $TotalProduction;
     var  $tankStore;
     var  $productionTankStore;
     var  $proitemcode;
     var  $work_orders;
     
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
    
     Function ToStoreTankForItem($ItemCode){
        global $db;

        $line='';
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            $this->productionTankStore="S";
            $REsults=DB_query('SELECT code,Storename FROM Stores', $db);
            while($rows= DB_fetch_array($REsults)){
                 $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['ProdStore']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
             }
        } else {
            $this->productionTankStore="T";
            $ResultIndex=DB_query(sprintf("select tankname from ProductionUnit where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
              $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['ProdStore']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
            }
       }
        return $line; 
    }
 
     function GetStockdetails($itemcode){
         global $db;
         
     
                 $ResultIndex = DB_query("SELECT 
                        stockmaster.barcode,
                        stockmaster.itemcode,
                        stockmaster.descrip as stockname,
                        unit.descrip as si,
                        stockmaster.averagestock,
                        vatcategory.vat,
                        c.itemcode as container,
                        c.descrip as packname,
                        cv.vat as CVAT
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
         $this->stocklist['vat'] = $stocklist[5];
         $this->stocklist['container'] = $stocklist[6];
         $this->stocklist['packname'] = $stocklist[7];
         $this->stocklist['CVAT'] = $stocklist[8];
         
         return $this->stocklist;
         
     }
         function neworder(){
         $_SESSION['PE_orders']=array();unset($_SESSION['htmltable']);
     }
     
       
    function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['PE_orders'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
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

    function tankresults($store,$item){
      global $db;
       $return ='';
       
        $sql=sprintf("select count(*) from ProductionUnit where itemcode='%s'",$item);
         $ResultIndex = DB_query($sql, $db);
        $Stores = DB_fetch_row($ResultIndex);
       if($Stores[0]>0){
            $sql=sprintf("SELECT balance FROM ProductionUnit where tankname='%s' and itemcode='%s'",$store,$item);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $StockBalanceInloose = $row[0];
            $return= sprintf('%f',(int)$StockBalanceInloose);
         }else{
            $return= '';
         }
         return $return;
   }
   
   
     function Getitems($itemcode,$decanteritemcode,$qty,$uom,$packSize){
         $StockList = $this->GetStockdetails($itemcode);
         $StockListTwo = $this->GetStockdetails($decanteritemcode);
         if(mb_strwidth($StockList['itemcode'])>0 
                 and mb_strwidth($StockList['container'])>1 
                 and mb_strwidth($StockListTwo['container'])<=1){
             
                $id = trim($StockList['itemcode']);
                $_SESSION['PE_orders'][$id] = array(
                         'line_no' => $id,
                         'itemcode' => trim($StockList['itemcode']),
                         'barcode' => $StockList['barcode'],
                         'stockname' => $StockList['stockname'],
                         'uom' => $uom,
                         'averagestock' => $StockList['averagestock'],
                         'quantity' => $qty,
                         'packSize' => $packSize,
                         'itemcodeTwo' => trim($StockListTwo['itemcode']),
                         'barcodeTwo' => $StockListTwo['barcode'],
                         'stocknameTwo' => trim($StockListTwo['stockname']),
                         'container' => trim($StockList['container']));
         }
         
         
          
         $this->showtable();
     }
      
 
   
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $totalProduction = 0;
        $percentTotal=0;
            
            foreach ($_SESSION['PE_orders'] as $lineno => $rowvalue) {
                $line_no = trim($rowvalue['line_no']);
                $itemcode = $rowvalue['itemcode'];
                $this->IsTankOrStore($itemcode);
                  
                $ContainerList = $this->GetStockdetails($rowvalue['container']);
                $stocklist = $this->GetStockdetails($itemcode);
                 
                $UOM = $rowvalue['uom'];  
                $location = $_POST['location'][$line_no];
                
                if(mb_strlen($location)==0){ $location=$_SESSION['Stores'][0]['code']; }
                
                $averagestock = (float) $rowvalue['averagestock'];
                $qty = (float) $rowvalue['quantity'] ;
                $packSize = (float) $rowvalue['packSize'];
                $baseamount  = (float) ($averagestock * $qty * $packSize);
                $runningnettotal += $baseamount;
                 
                 $code    = trim($stocklist['itemcode']);
                 $ID      = trim($stocklist['itemcode']);
                 $BC      = trim($stocklist['barcode']);
                 $NAME    = trim($stocklist['stockname']);
              
                if($this->tankStore=="T"){
                    $StockBalanceInloose =(int) getTankbalance($location,$code);
                    $_SESSION['PE_orders'][$code]['Balance']= $StockBalanceInloose;
                }elseif($this->tankStore=="S"){
                    $StockBalanceInloose=(int) getbalance($code,$location,'loosqty');
                    $_SESSION['PE_orders'][$code]['Balance']=$StockBalanceInloose;
                }
                
                $hownamyempties=((mb_strlen($ContainerList['stockname'])>0)?($qty.' X '.$ContainerList['stockname'].' will be received'):'');
                
                $arrayi  = $_SESSION['PE_orders'][$lineno];
                $_SESSION['PE_orders'][$lineno] = array_replace_recursive($arrayi, 
                array('location'=>$location,'netamount'=>$baseamount, 
                    'stkbalance'=>(($_SESSION['PE_orders'][$code]['Balance'])-($qty*$packSize)),
                    'ContainerList'=>$hownamyempties));
            
               
                
                $this->htmltable .= sprintf('<tr onclick="ProductionGridtwo(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');">'
                . '<td>%s</td>'
                . '<td><input type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" />X'.$packSize.'</td>'
                . '<td><select name="location['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td><input type="text" class="number cell" readonly="readonly" value="'.(($_SESSION['PE_orders'][$code]['Balance'])-($qty*$packSize)).'"  size="8"/></td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>'.$qty.' X '.$packSize.'</td>'
                . '<td><select name="TankStore['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
                . '<td><input type="text" class="number" readonly="readonly" value="'.($averagestock*$packSize).'" size="6"/></td>'
                . '<td class="number"><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($baseamount,2).'" readonly="readonly" size="12"/></td>'
                . '</tr>',$ID,$BC,$NAME,$qty,trim($rowvalue['stocknameTwo']),
                trim($stocklist['stockname']),$this->MakeStoreTankForItem($ID,$line_no),getGriduom($line_no,$UOM ), 
                trim($rowvalue['stocknameTwo']),$this->MakeStoreTankForItem(trim($rowvalue['itemcodeTwo']),$line_no));
           }
            
              $this->htmltable .= sprintf('<tfoot><tr><td colspan="9"> '.$hownamyempties.'<td class="number cell">%s</td></tr></tfoot>',number_format($runningnettotal,2));
              
              $_SESSION['hideOrShow']=($this->tankStore=='')?"button":"submit";
              $_SESSION['htmltable']=$this->htmltable;
    }
    
}

 ?>