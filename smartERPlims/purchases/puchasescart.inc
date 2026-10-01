<?php

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
                vatcategory.vat
          FROM stockmaster 
          left join unit on stockmaster.units=unit.code
          left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
          left join vatcategory on inventorypostinggroup.vatcategory=vatcategory.vatc
          where stockmaster.itemcode='".$itemcode."'", $db);
         
         $stocklist = DB_fetch_row($ResultIndex);
         $this->stocklist['barcode'] = $stocklist[0];
         $this->stocklist['itemcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['si'] = $stocklist[3];
         $this->stocklist['averagestock'] = $stocklist[4];
         $this->stocklist['partperunit'] = $stocklist[5];
         $this->stocklist['vat'] = $stocklist[6];
         
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price){
         $StockList = $this->GetStockdetails($itemcode);
         $UOM = $_POST['UOM']['$itemcode'];
         $line_No = $this->LineNo();
         
         $_SESSION['Purchase_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$UOM,
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$price,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['Purchase_orders_index'];
     }
     
     function neworder(){
         $_SESSION['Purchase_orders']=array();
         $_SESSION['Purchase_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['Purchase_orders_index']++;
    }
    
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;
        $runningDiscounttotal = 0;

            $results=DB_query("SELECT 
                itemcode ,customer, phone ,
                email ,city ,country,curr_cod,
                supplierposting,VATinclusive,IsTaxed
            FROM creditors join arpostinggroups on code=supplierposting 
            where itemcode='".$_POST['VendorID']."'", $db);
              
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[7];
            $this->VATinclusive = $debtorsrow[8];
            $this->IsTaxed = $debtorsrow[9];

            
            foreach ($_SESSION['Purchase_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;$discount=0;$discountAmount=0;
                
                $line_no = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate = (($this->IsTaxed==false)?0:$stocklist['vat']);
    
                if(isset($_POST['quantity'][$line_no])){
                    $qty= $_POST['quantity'][$line_no];
                }else{
                    $qty=.0;
                }
        
                if(isset($_POST['salesprice'][$line_no])){
                    $salesprice= $_POST['salesprice'][$line_no] ;
                }else{
                    $salesprice=.0;
                }
                
                if(isset($_POST['discount'][$line_no])){
                    $discount = $_POST['discount'][$line_no];
                    $discountAmount = $salesprice * round($discount/100,2);
                }
                
                $baseamount = ($salesprice * $qty)-$discountAmount;
               
                
            if($this->VATinclusive == true){
                $netamount   = $baseamount /  round((($rate+100)/100),2);
                $vatamount   = $baseamount - $netamount;
                $grossamount = $baseamount ;
            }else{
                $vatamount  = $baseamount * round($rate/100,2);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
    
                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                $runningDiscounttotal += $discountAmount;
                
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td class="number"><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td class="number"><input type="text" class="number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="discount['.$line_no.']" value="'.$discount.'" size="4"/></td>'
              . '<td class="number"><input type="text" class="number" name="netamount['.$line_no.']" value="'.$netamount.'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" name="vatamount['.$line_no.']" value="'.$vatamount.'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="10"/></td>'
            . '</tr>',trim($stocklist['itemcode']),
                      trim($stocklist['stockname']),
                      CreateUnits($itemcode,$line_no),
                      number_format($stocklist['partperunit'],0),
                      number_format($stocklist['averagestock'],2));
            }
            
                       
            $this->htmltable .= sprintf('<tr>'
              . '<td colspan="8">TOTAL LPO VALUE</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr>', 
                      number_format($runningnettotal,2),
                      number_format($runningvattotal,2),
                      number_format($runninggrosstotal,2));
            
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
            if($_SESSION['Grossamounttotal']>0){$required='required="required"';}else{$required='';}
             
             $this->htmltable .= '<tr>'
              . '<td colspan="6">Include the Cost of freight (AS A TOTAL) to help apportion it to the cost of inventory</td>'
              . '<td colspan="5"><input type="text" class="number" name="FREIGHT" value="'.$_POST['FREIGHT'].'" size="10" '.$required.'/>'
              . '<input type="hidden" class="number" name="runningDiscounttotal" value="'.$runningDiscounttotal.'" /></td>'
              . '</tr>';
            
         
        echo $this->htmltable;

    }
    
    
    
     
 }
 
 class FixedAssets {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetStockdetails($itemcode){
         global $db;
         
        $sql= "SELECT
                fixedassets.assetid,
                fixedassets.barcode, 
                fixedassets.description, 
                fixedassets.cost, 
                vatcategory.vat 
               FROM fixedassets 
                left join fixedassetcategories on fixedassets.assetcategoryid=fixedassetcategories.categoryid 
                left join vatcategory on fixedassetcategories.vatcategorycode=vatcategory.vatc
                    where fixedassets.assetid='".$itemcode."'";
         $ResultIndex = DB_query($sql,$db);
         $stocklist = DB_fetch_row($ResultIndex);
         $this->stocklist['itemcode'] = $stocklist[0];
         $this->stocklist['barcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['averagestock'] = $stocklist[3];
         $this->stocklist['partperunit'] = 1;
         $this->stocklist['vat'] = $stocklist[4]; ;
         $this->stocklist['si'] = 'PCS' ;
         
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price){
         if(mb_strlen($itemcode)>0){
         $StockList = $this->GetStockdetails($itemcode);
         $ID = trim($itemcode);
         $_SESSION['Purchase_orders'][$ID]=
            array('LineNo'=>$ID,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>'Pcs',
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$price,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         }
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['Purchase_orders_index'];
     }
     
     function neworder(){
         $_SESSION['Purchase_orders']=array();
         $_SESSION['Purchase_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['Purchase_orders_index']++;
    }
    
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;

            $results=DB_query("SELECT itemcode ,customer,phone ,email 
            ,city ,country,curr_cod,supplierposting,VATinclusive,IsTaxed
            FROM creditors join postinggroups on code=supplierposting 
            where itemcode='".$_POST['VendorID']."'", $db);
              
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[7];
            $this->VATinclusive = $debtorsrow[8];
            $this->IsTaxed = $debtorsrow[9];

            
            foreach ($_SESSION['Purchase_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
                $itemcode = trim($rowvalue['itemcode']);
                $stocklist = $this->GetStockdetails($itemcode);
                $rate = ($this->IsTaxed==false)?0:$stocklist['vat'];
    
                if(isset($_POST['quantity'][$itemcode])){
                    $qty = $_POST['quantity'][$itemcode];
                }else{
                    $qty = .0;
                }
        
                if(isset($_POST['salesprice'][$itemcode])){
                    $salesprice = $_POST['salesprice'][$itemcode] ;
                }else{
                    $salesprice = .0;
                }
         
              $baseamount = ($salesprice * $qty);
    
            if($this->VATinclusive==true){
                $netamount   = $baseamount * (1- ($rate/(100+$rate)));
                $vatamount   = $netamount * ($rate/(100+$rate));
                $grossamount = $baseamount ;
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
              . '<td class="number"><input type="text" class="integer" name="quantity['.$itemcode.']" value="'.$qty.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
              . '</tr>',trim($stocklist['itemcode']),trim($stocklist['stockname']));
            }
            
                       
            $this->htmltable .= sprintf('<tr>'
              . '<td colspan="4">TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr>', number_format($runningnettotal,2),
                      number_format($runningvattotal,2),
                   number_format($runninggrosstotal,2));
              $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
    
    
    
     
 }
 
 class FixedAssetsHire {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetStockdetails($itemcode){
         global $db;
         
        $sql= "SELECT
                fixedassets.assetid,
                fixedassets.barcode, 
                fixedassets.description, 
                fixedassets.cost, 
                vatcategory.vat ,
                sum(fixedassettrans.units) as units
               FROM fixedassets 
               left join fixedassetcategories on fixedassets.assetcategoryid=fixedassetcategories.categoryid 
               left join vatcategory on fixedassetcategories.vatcategorycode=vatcategory.vatc
               left join fixedassettrans on fixedassets.assetid=fixedassettrans.assetid
                where fixedassets.assetid='".$itemcode."' 
                    Group by fixedassets.assetid,
                fixedassets.barcode, 
                fixedassets.description, 
                fixedassets.cost, 
                vatcategory.vat ";
         $ResultIndex = DB_query($sql,$db);
         $stocklist = DB_fetch_row($ResultIndex);
         $this->stocklist['itemcode'] = $stocklist[0];
         $this->stocklist['barcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['averagestock'] = $stocklist[3];
         $this->stocklist['partperunit'] = 1;
         $this->stocklist['vat'] = $stocklist[4]; 
         $this->stocklist['si'] = 'PCS' ;
         $this->stocklist['units'] =  $stocklist[5];
         
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price){
         $StockList = $this->GetStockdetails($itemcode);
         
         $_SESSION['Purchase_orders'][$this->LineNo()]=
            array('itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>'Pcs',
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'units'=>$StockList['units'],
                  'unitbalance'=>$StockList['units']-$qty,
                  'salesprice'=>$price,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['Purchase_orders_index'];
     }
     
     function neworder(){
         $_SESSION['Purchase_orders']=array();
         $_SESSION['Purchase_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['Purchase_orders_index']++;
    }
    
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;

        
   $results=DB_query("SELECT itemcode,creditlimit,customer,phone ,email ,city ,country,curr_cod,customerposting,salesman,VATinclusive,IsTaxed
                   FROM debtors join postinggroups on code=customerposting 
                   where itemcode='".$_POST['CustomerID']."'", $db);
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[8];
            $this->VATinclusive = $debtorsrow[10];
            $this->IsTaxed = $debtorsrow[11];
            
            foreach ($_SESSION['Purchase_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate = ($this->IsTaxed==false)?0:$stocklist['vat'];
                $unitsavailable = $stocklist['units'];
                
                if(isset($_POST['quantity'][$itemcode])){
                    $qty = $_POST['quantity'][$itemcode];
                }else{
                    $qty = .0;
                }
        
               $unitsremaining = $unitsavailable-$qty ;
                              
                if(isset($_POST['salesprice'][$itemcode])){
                    $salesprice = $_POST['salesprice'][$itemcode] ;
                }else{
                    $salesprice = .0;
                }
         
              $baseamount = ($salesprice * $qty);
    
            if($this->VATinclusive==true){
                $netamount   = $baseamount * (1- ($rate/(100+$rate)));
                $vatamount   = $netamount * ($rate/(100+$rate));
                $grossamount = $baseamount ;
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
              . '<td class="number"><input type="text" class="integer" name="quantity['.$itemcode.']" value="'.$qty.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="integer" name="units['.$itemcode.']" value="'.$unitsavailable.'" size="5" readonly="readonly"/></td>'
              . '<td class="number"><input type="text" class="integer" name="unitbalance['.$itemcode.']" value="'.$unitsremaining.'" size="5" readonly="readonly"/></td>'
              . '<td class="number"><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
              . '</tr>',trim($stocklist['itemcode']),trim($stocklist['stockname']));
            }
            
                       
            $this->htmltable .= sprintf('<tr>'
              . '<td colspan="6">TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr>', 
                      number_format($runningnettotal,2),
                      number_format($runningvattotal,2),
                      number_format($runninggrosstotal,2));
              $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
    
    
    
     
 }
 
 class PurchaseOrder {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     
     function GetStockdetails($itemcode,$vatcategory=''){
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
                  FROM stockmaster 
                  left join stockmaster c on stockmaster.container=c.itemcode
                  left join unit on stockmaster.units=unit.code
                  left join inventorypostinggroup on stockmaster.postinggroup=inventorypostinggroup.code
                  left join vatcategory on '".trim($vatcategory)."'=vatcategory.vatc
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
         
         return $this->stocklist;
         
     }
      
     function Getitems($itemcode,$qty,$uom,$packzize,$prize,$vatcategory,$discount=0,$recqty,$receiveduom,$packzizegrn){
         $StockList = $this->GetStockdetails($itemcode,$vatcategory);
          
         if(mb_strwidth($StockList['itemcode'])>0){
         $id = trim($StockList['itemcode']);
         $_SESSION['Purchase_orders'][$id]= 
                  array('line_no'=>$id,
                  'itemcode'=>$id,
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'uom'=>$uom,
                      'uomreceived'=>$receiveduom,
                  'partsperunit'=>$packzize,
                      'packzizegrn'=>$packzizegrn,
                  'price'=>$prize,
                  'quantity'=>$qty, 
                  'ReceivedQuantity'=>$recqty,
                  'discount'=>$discount,
                  'vatcategory'=>$vatcategory,
                  'vatrate'=>$StockList['vat']);
         }
          
         $this->showtable();
     }
      
    
     function neworder(){
         $_SESSION['Purchase_orders']=array();
     }
     
     function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['Purchase_orders'][$this->deleteID]);
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
        $runningDiscounttotal = 0;

            $results=DB_query("SELECT 
                itemcode ,customer, phone ,
                email ,city ,country,curr_cod,
                supplierposting,VATinclusive,IsTaxed
            FROM creditors join arpostinggroups on code=supplierposting 
            where itemcode='".$_POST['VendorID']."'", $db);
              
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[7];
            $this->VATinclusive = $debtorsrow[8];
            $this->IsTaxed = $debtorsrow[9];

            
            foreach ($_SESSION['Purchase_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0; 
                $cnetamount=0; $cgrossamount=0; $emptyunits =0;
                $discount=0; $discountAmount=0;
                
                $line_no = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $vatcategory = $rowvalue['vatcategory'];
                $ReceivedQuantity = $rowvalue['ReceivedQuantity'];
                
                $stocklist = $this->GetStockdetails($itemcode,$vatcategory);
                $rate = (($this->IsTaxed==false)?0:$stocklist['vat']);
    
                $qty       =(float) $rowvalue['quantity'] ;
                $UOM       = trim($rowvalue['uom']);
                $uomreceived = trim($rowvalue['uomreceived']) ;
                $package   =(float) $rowvalue['partsperunit'];
                $salesprice=(float) $rowvalue['price'];
                $discount  =(float) $rowvalue['discount'];
            
                $Inpacksdescription = $_SESSION['units'][$UOM]['descrip'];
                $InpacksReceived = $_SESSION['units'][$uomreceived]['descrip'];
             
                if($discount>0){
                  $discountAmount = ($salesprice * $qty * $package) * round($discount/100,2);
                }
                
                $baseamount = ($salesprice * $qty * $package)-$discountAmount;
                $packzizegrn  = $rowvalue['packzizegrn'];
                $RealCost = ($salesprice * $qty * $package)/($packzizegrn*$ReceivedQuantity);
                
            if($this->VATinclusive == true){
                $netamount   = $baseamount / round((($rate+100)/100),2);
                $vatamount   = $baseamount - $netamount;
                $grossamount = $baseamount ;
            }else{
                $vatamount  = $baseamount * round($rate/100,2);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
            
             
             $ID = trim($stocklist['itemcode']); 
             $BC = trim($stocklist['barcode']); 
             $NAME = trim($stocklist['stockname']);
             $arrayi = $_SESSION['Purchase_orders'][$lineno];
           
             $_SESSION['Purchase_orders'][$lineno] = array_replace_recursive($arrayi,
               array('discount'=>$discountAmount, 'netamount'=>$netamount, 'vatamount'=>$vatamount,'grossamount'=>$grossamount));
       
             $runningnettotal += $netamount;  $runningvattotal += $vatamount; $runninggrosstotal += $grossamount; $runningDiscounttotal += $discountAmount;
                
              $this->htmltable .= sprintf('<tr onclick="purchaseOrderInventorygrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');">'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>'.$qty.' X %s  <input type="hidden" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
              . '<td>'.$package.'<input type="hidden" class="number" name="package['.$line_no.']" value="'.$package.'"  size="7" maxlength="5" readonly="readonly"/></td>'
              . '<td>'.number_format($salesprice*$package,2).' X '.$Inpacksdescription.'<input type="hidden" class="number cell" readonly="readonly" value="'.($salesprice*$package).'" size="6"/></td>'
              . '<td>'.$packzizegrn.' X '.$ReceivedQuantity.$InpacksReceived.'<input type="hidden" name="ReceivedQuantity['.$line_no.']" value="'.$ReceivedQuantity.'" size="5" readonly="readonly" /></td>'
              . '<td>'.number_format($RealCost*$packzizegrn,2).' X '.$InpacksReceived.'<input type="hidden" class="number cell" readonly="readonly" value="'.($RealCost*$packzizegrn).'" size="6"/></td>'
              . '<td class="number cell">'.$discount.'<input type="hidden" class="number" name="discount['.$line_no.']" value="'.$discount.'" size="4"/></td>'
              . '<td class="number cell">'.number_format($netamount,2).'<input type="hidden" class="number" name="netamount['.$line_no.']" value="'.$netamount.'" /></td>'
              . '<td class="number cell">'.number_format($vatamount,2).'<input type="hidden" class="number" name="vatamount['.$line_no.']" value="'.$vatamount.'" /></td>'
              . '<td class="number cell">'.number_format($grossamount,2).'<input type="hidden" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" /></td>'
              . '</tr>',$ID,$BC,$NAME,$qty,$ReceivedQuantity,$salesprice,$package,$rowvalue['discount'],
                      trim($stocklist['barcode']),trim($stocklist['stockname']),$Inpacksdescription);
              //,getGriduom($line_no,$UOM)
            }
            
                       
            $this->htmltable .= sprintf('<tr>'
              . '<td colspan="8">TOTAL LPO VALUE</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr>',number_format($runningnettotal,2),
                      number_format($runningvattotal,2),
                      number_format($runninggrosstotal,2));
            
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
             
             $this->htmltable .= '<tr>'
              . '<td colspan="8">Include the Cost of freight (AS A TOTAL) to help apportion it to the cost of inventory</td>'
              . '<td colspan="4"><input type="text" class="number" name="FREIGHT" value="'.$_POST['FREIGHT'].'" size="10" />'
              . '<input type="hidden" class="number" name="runningDiscounttotal" value="'.$runningDiscounttotal.'" /></td>'
              . '</tr>';
            
         
        echo $this->htmltable;

    }
    
    
    
     
 }
 
?>