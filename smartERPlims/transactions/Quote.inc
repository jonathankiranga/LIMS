<?php

 class Quote {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
    
     function __construct() {
         Global $db;
         
         
          if (!isset($_SESSION['paymentterms']) ) {
            $sql= "INSERT INTO config (confname,confvalue) values('paymentterms','The above offer is based on current rates')";
            $ResultIndex = DB_query($sql, $db);
            
                    $sql = "SELECT confname, confvalue FROM config where confname='paymentterms'";
                    $ConfigResult = DB_query($sql,$db);
                    while($myrow = DB_fetch_array($ConfigResult) ) {
                         $_SESSION[$myrow['confname']] =  $myrow['confvalue'];
                    } 
                    
          } 
          
          
          if (!isset($_SESSION['bankaccountdetails']) ) {
            $sql= "INSERT INTO config (confname,confvalue) values('bankaccountdetails','Bank Account')";
            $ResultIndex = DB_query($sql, $db);
            
                    $sql = "SELECT confname, confvalue FROM config where confname='bankaccountdetails'";
                    $ConfigResult = DB_query($sql,$db);
                    while($myrow = DB_fetch_array($ConfigResult) ) {
                         $_SESSION[$myrow['confname']] =  $myrow['confvalue'];
                    } 
                    
          } 
    
    
     }
     
     
             
     function GetStockdetails($itemcode){
         global $db;
         
         $sql = "SELECT 
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
                  where stockmaster.itemcode='".$itemcode."'";
         
         $ResultIndex = DB_query($sql, $db);
           
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
     
     function GetAssetdetails($itemcode){
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
         $this->stocklist['si'] = 'UNIT' ;
         $this->stocklist['CVAT'] = $stocklist[4];
         
         
         return $this->stocklist;
         
     }
    
     function Getitems($itemcode,$qty,$price,$price_empties){
         $StockList = $this->GetStockdetails($itemcode);
         
         $line_No = $this->LineNo();
         
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$_POST['UOM'][$line_No],
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'emptycost'=>$price_empties,
                  'totalemptycost'=>$total_empties,
                  'quantity'=>$_POST['quantity'][$line_No],
                  'salesprice'=>$_POST['salesprice'][$line_No],
                  'netamount'=>$_POST['netamount'][$line_No],
                  'vatamount'=>$_POST['vatamount'][$line_No],
                  'grossamount'=>$_POST['grossamount'][$line_No],
                  'quotetype'=>'stock',
                  'CVAT'=>$StockList['vat'],
                  'packname'=>$StockList['packname'],
                  'container'=>$StockList['container']);
        
         $this->showtable();
     }
 
     function Getassets($itemcode,$qty,$price){
         $StockList = $this->GetAssetdetails($itemcode);
         $line_No = $this->LineNo();
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$_POST['UOM'][$line_No],
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$_POST['quantity'][$line_No],
                  'salesprice'=>$_POST['salesprice'][$line_No],
                  'netamount'=>$_POST['netamount'][$line_No],
                  'vatamount'=>$_POST['vatamount'][$line_No],
                  'grossamount'=>$_POST['grossamount'][$line_No],
                  'quotetype'=>'assets',
                  'CVAT'=>$StockList['CVAT'],
                  'packname'=>$StockList[''],
                  'container'=>$StockList['']);
        
         $this->showtable();
     }
         
     function PUTitems($quotetype,$itemcode,$qty,$price,$price_empties=''){
       
            if($quotetype=='stock'){
             $this->Getitems($itemcode,$qty,$price,$price_empties);
            }elseif($quotetype=='assets'){
             $this->Getassets($itemcode,$qty,$price);
            }
        
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

            $results=DB_query("SELECT itemcode,creditlimit,customer,phone
                ,email ,city ,country,curr_cod,customerposting,salesman,VATinclusive,IsTaxed
                   FROM debtors join postinggroups on code=customerposting 
                   where itemcode='".$_POST['CustomerID']."'", $db);
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[8];
            $this->VATinclusive = $debtorsrow[10];
            $this->IsTaxed = $debtorsrow[11];
           
           
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
 
                $line_no  = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
              
                if($this->IsTaxed==true){
                    $rate=$rowvalue['CVAT'];
                }else{
                    $rate='0';
                }
    
               
                 
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
    
                $ArrayUOM = explode('_',$_POST['UOM'][$line_no]);
                
                if($ArrayUOM[0]=='F'){
                     $baseamount = ($salesprice * ($qty * $rowvalue['partperunit']));
                }else{
                     $baseamount= ($salesprice * $qty);
                }
          
    
            if($this->VATinclusive==true){
                $netamount = $baseamount /(($rate+100)/100);
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
               . '<td><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
               . '<td>%s</td>'
               . '<td>%s</td>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td><input type="text" class="number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td><input type="text" class="number" name="netamount['.$line_no.']" value="'.$netamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="vatamount['.$line_no.']" value="'.$vatamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="8"/></td>'
              . '</tr>',trim($rowvalue['barcode']), 
                        trim($rowvalue['stockname']),
                        CreateQuoteUnits($itemcode,$line_no), 
                        number_format($rowvalue['partperunit'],0));
            }
            
            $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number" colspan="7">Total Order </td>'
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

 
