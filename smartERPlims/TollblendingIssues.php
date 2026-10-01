<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/ClassStockIssues.inc');
include('transactions/stockbalance.inc');   



$Saleslineinex=0;
 if(isset($_POST['TollblendingStockPDF'])){
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $pdf->addInfo('Title',_('Receipts'));
    $pdf->addInfo('Subject',_('Receipts'));
    $pdf->addInfo('Creator',_('SmartERP'));
    
    $stockmasterp=array();

    $SQL = "SELECT itemcode,barcode,descrip,isstock from stockmaster";
    $ResultIndex=DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']); 
         $stockmasterp[$code]=array('itemcode'=>$row['itemcode'],
            'barcode'=>$row['barcode'],'descrip'=>$row['descrip'],
            'isstock'=>$row['isstock']);
    }
    
    $collumrows = array();
    $collumrows[1] = $Right_Margin;

    $FontSize = 10;
    $PageNumber = 0;
    $line_height = 12;
    $Firstinvoicerow = 0;

    Pageheader();
    Preparecolums(1);
    MakepdfForstock();
    
 }elseif(isset($_POST['TollblendingPDF'])){
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $pdf->addInfo('Title',_('Receipts'));
    $pdf->addInfo('Subject',_('Receipts'));
    $pdf->addInfo('Creator',_('SmartERP'));

    $collumrows = array();
    $collumrows[1] = $Right_Margin;

    $FontSize = 10;
    $PageNumber = 0;
    $line_height = 12;
    $Firstinvoicerow = 0;

    Pageheader();
    Preparecolums(1);
    Makepdf();
}else{
    $Title = _('Toll Blending');
    include('includes/header.inc');
}


    if(isset($_POST['PostBacthAllocation'])){
        
        $sql = array();
        foreach ($_POST['allocate'] as $salesref => $batchno) {
           $sql[] = "Update `ProductionMaster`  set `SalesHeader`=NULL  where `SalesHeader`= '".$salesref."'";
           foreach ($batchno as $key => $value) {
                           $sql[] = "Update `ProductionMaster`  set `SalesHeader`= '".$salesref."'  where `batchno`='".$key."'";
            }
        }

        foreach ($sql as $value) {
            $ResultIndex = DB_query($value, $db);
        }
    }

    $POSclass = new Requsets();
    if(isset($_GET['new'])){
      $POSclass->neworder();
    }
 
    if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
     while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['code']);
        $_SESSION['units'][$code]=$row;
      }
    }
 
    if(!isset($_POST['date'])){ 
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date'] = ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
     }
 
    if($_POST['transactiontype']=='1'){
    
    unset($_SESSION['stockmaster']) ;
    $SQL = "SELECT isstock,itemcode,barcode,descrip,isstock_3
    from stockmaster where isstock_1=1  order by descrip";
    $ResultIndex=DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
       $code = trim($row['itemcode']);
       $_SESSION['stockmaster'][$code]=$row;
    }

}else{
     unset($_SESSION['stockmaster']) ;
    $SQL = "SELECT isstock,itemcode,barcode,descrip
    from stockmaster where isstock_2=1  order by descrip";
    $ResultIndex=DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
       $code = trim($row['itemcode']);
       $_SESSION['stockmaster'][$code]=$row;
    }
    
}

    if(!isset($_SESSION['Stores'])){
    $REsults=DB_query('SELECT `code`,`Storename` FROM `Stores`', $db);
    $x=0;
    while($row= DB_fetch_array($REsults)){
        $_SESSION['Stores'][$x]=$row;
        $x++;
    }
}   

    if(isset($_POST['remove'])){
   $POSclass->RemoveOrder($_POST['stockitemcode']);
 }
 
    if(isset($_POST['submit'])){
     
     if($_POST['submit']=='Save Item Request'){
         include('transactions/savetollsales.inc'); 
         unset($_POST);
         $POSclass->neworder();
         
          echo sprintf('<p class="page_title_text"><a id="'.$_SESSION['DocumentNo'].'" href="%s?No=%s" >'
          . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Toll blending') . '" alt="" />%s</a></p>',
           'PDFPrintTollblending.php',$_SESSION['DocumentNo'], _('Print Toll blending ').$_SESSION['DocumentNo']);
   
     } 
     
     if($_POST['submit']=='Delete Order'){
         DeletePOS($_SESSION['CompleteDocument']);
         unset($_SESSION['CompleteDocument']);
     }
     
 }
  
    if(isset($_POST['TransactionState'])){
       $TransactionState=$_POST['TransactionState'];
    }else{
       $TransactionState='New';
    }
           
    if(isset($_POST['Begin'])){
       $TransactionState='Selected';
    }
    
    if(isset($_POST['Stop'])){
       $TransactionState='New';
    }
 
    $pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    if(isset($_POST['refresh']) or isset($_POST['Begin']) or isset($TransactionState) ){
     
    echo '<div class="centre">';
    echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
    echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
    echo '<input type="hidden" name="TransactionState" value="'. $TransactionState.'" />';

    if(!isset($_POST['TollblendingPDF']) || !isset($_POST['TollblendingStockPDF'])){
       if($TransactionState=='Selected'){
     ?>
       <table class="table-condensed table-responsive-small"><tr><td valign="top"><?php DisplayPOS($POSclass); ?></td><td valign="top" >
       <table class="table-condensed table-responsive-small"><tr><td valign="top"><?php Entry(); ?></td></tr><tr><td valign="top"><?php getstocklist(); ?><td></tr></table>
       </td></tr></table>

    <?php
                   
                }else{
                    
                $_POST['documentno'] = GetTempNextNo(40);
                echo '<table class="table table-bordered table-condensed">';
                echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
                echo '<td>Document No</td>'
                   . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
                   . '</tr>';

                echo '<tr><td>Customer ID</td>'
                        . '<td><input  type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
                        . '<input type="button" id="searchcustomer" value="Search Customer"/></td>'
                        . '<td>Invoice To</td>'
                        . '<td colspan="3"><input type="text"  name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td>'
                        . '</tr>';

                echo '<tr><td>Currency Code</td><td>'
                . '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

                echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
                . '<option value="not">Not selected</option>';

                $ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive` FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);
                 while($row=DB_fetch_array($ResultIndex)){
                  echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
                }

                echo '</select></td></tr>'; 
                echo '<tr><td>Transaction Type</td><td><select name="transactiontype"  >';

                $transtype=array('1'=>'Sale or Dispatch (Stock leaving)','2'=>'Delivery or Purchase (Stock Comming In)');   
                foreach ($transtype as $key => $value) {
                     $selunit =(($_POST['transactiontype']==$key)?'selected="selected"':'');
                     echo '<option value="'.$key.'" '.$selunit.'>'.$value.'</option>';
                }

                echo '</select></td><td><input type="submit" name="Begin" value="Continue" class="btn-info" /></td>'
                    . '<td><input type="submit" name="Stop" value="Cancel" class="btn-danger"/>'
                        . '<input type="submit" name="TollblendingPDF" value="Export Statement PDF">'
                        . '<input type="submit" name="TollblendingStockPDF" value="Stock Statement PDF"></td></tr>';
                echo '</table>';
          }
    }
    
    echo '</div></form>';
}
 
  if(!isset($_POST['TollblendingPDF']) || !isset($_POST['TollblendingStockPDF'])){
     include('includes/footer.inc');
  }
  
function getstocklist(){
   
      $return= '<div><div class="table"><label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
               <div class="posfinder"><table id="myStockTable" class="table-bordered stockfind"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
       
    foreach ($_SESSION['stockmaster'] as $key => $row){
    $return .= sprintf('<tr onclick="posInventory(\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),trim($row['barcode']),
            trim($row['descrip']),trim($row['barcode']),
            trim($row['descrip'])) ;
    }
   $return .= '</table></div></div></div>';
           
   echo $return;
}

Function Entry(){
      Global $db;
  
  echo '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Barcode</td><td><input class="col-sm-push-3" type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>No of Units</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty"  value="'.$_POST['qty'].'"/></td></tr>'
    . '<tr><td>Selling Price</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="selprice" name="selprice"  value="'.$_POST['selprice'].'"/></td></tr>'
  //   . '<tr><td>Production Batch No</td><td>'.getbachnoentry(trim($_POST['stockitemcode']),FormatDateForSQL($_POST['date'])).'</td></tr>'
     . '<tr><td>Units Measure In <code>Kg/Ltr</code></td><td><select id="packid" name="units" onchange="ReloadForm(prodform.refresh)">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']);
           $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr>';
    echo '<tr><td>Transaction Type</td><td><select name="transactiontype" id="transactiontype">';
    
        $transtype=array('1'=>'Sale or Dispatch','2'=>'Delivery or Purchase');   
        foreach ($transtype as $key => $value) {
             $selunit =(($_POST['transactiontype']==$key)?'selected="selected"':'');
             echo '<option value="'.$key.'" '.$selunit.'>'.$value.'</option>';
        }
              
    echo '</select></td></tr>'
         . '<tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td>'
        . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/><input type="submit" name="Stop" value="Cancel" class="btn-danger"/></td></tr>'
        . '</table>';
       
}

function DisplayPOS($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(40);
 
$POSclass->Getitems($_POST['stockitemcode'], $_POST['qty'],$_POST['units'],$_POST['selprice']);
echo '<table class="table-bordered table-condensed">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
   . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
   . '</tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input  type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchcustomer" value="Search Customer"/></td>'
        . '<td>Invoice To</td>'
        . '<td colspan="3"><input type="text"  name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td>'
        . '</tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive` FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);
while($row=DB_fetch_array($ResultIndex)){
  echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td></tr>'; 
echo '</table>';
echo '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>Item code</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>STORE/TANK</label></th>'
        . '<th><label>BALANCE</label></th>'
        . '<th><label>Unit Descrip</label></th>'
        . '<th><label>Unit Cost</label></th>'
        . '<th><label>NET</label></th>'
        . '<th><label>VAT</label></th>'
        . '<th><label>Total Cost</label></th>'
        . '</tr>'
        . '</thead>';
     
  echo $_SESSION['htmltable'];
        
  echo '</table><table><tr><td>
	<input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="'.$_SESSION['hideOrShow'].'" name="submit" value="'._('Save Item Request').'" /></td>
        </table>';  
  
        $return= '<div><div class="table">'
           . '<table class="table-bordered" id="GL"><caption>Delivary and collection history</caption>'
           . '<tr><th>Delivery Date</th><th>Stock</th><th>Quantity</th></tr>';
        
     $Totals=0;
     $SQL=sprintf("select `documenttype`,`documentno`,`docdate`,`customercode`
           ,`customername`,`userid`
           from SalesHeader where `postinggroup`='%s'",$_POST['CustomerID']);
     $ResultIndex= DB_query($SQL,$db);
    while ($rowss= DB_fetch_array($ResultIndex)){
        $mydat[]=$rowss;
    }
    
    foreach ($mydat as $key => $row) {
               $return .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
               ConvertSQLDate($row['docdate']),'Document:'.trim($row['documentno']),' Prepared by  :'.$row['userid'],'') ;
 
        $rowlines=Getlinedata(trim($row['documentno']));
        foreach ($rowlines as $key2 => $lrow) {
              $Totals += ($lrow['containercode']=='1')?($lrow['Quantity']*-1):($lrow['Quantity']);
              $qty = ($lrow['containercode']=='1')?($lrow['Quantity']*-1):($lrow['Quantity']);
              $return .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
              $lrow['code'],$lrow['description'],number_format($qty,0));
             // getbachno(trim($lrow['code']),trim($row['documentno']),$row['docdate'])
              //        ) ;
       }
      
    }
     
   $return .= sprintf('<tr><th>%s</th><th>%s</th><th>%s</th><th></th></tr>','Total Volume',' in KGS or LTRS',number_format($Totals,0)) ;
   $return .= '</table></div></div></div>';
   echo $return;
   echo '<input type="button" onclick="tableToExcel(\'GL\',\''.$_POST['CustomerName'].'\')" value="Export Statement to Excel">';
   echo '<input type="submit" name="TollblendingPDF" value="Export Statement PDF">'
      . '<input type="submit" name="PostBacthAllocation" value="Post Batch Allocations">';

}
 
Function Getlinedata($DOC){
   global $db;
   $mydat=array();
   
   $ResultIndex= DB_query("select * from `Salesline` where documentno='".$DOC."' and `documenttype`='40'  "
           . " and (`containercode`='1' or `containercode`='2')  ", $db);
        while ($rowss= DB_fetch_array($ResultIndex)){
        $mydat[]=$rowss;
    }
   
    return $mydat;
 }
 
 
function MakepdfForstock(){
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos,$Firstinvoicerow ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height,$collumrows ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$docno,$customer,$stockmasterp ;
 Global $firstrowpos,$lastrow,$narrative,$amount,$itemcode,$db,$Saleslineinex ;

   $YPos = $Firstinvoicerow;
    $count = 0;$currentno=0;
    $SQL=sprintf("select `documenttype`,`documentno`,`docdate`,`customercode`,`customername`,`userid` "
            . " from SalesHeader where `postinggroup`='%s'",$_POST['CustomerID']);
    $ResultIndex= DB_query($SQL,$db);
    while($rowss= DB_fetch_array($ResultIndex)){
       $mydat[]=$rowss;
    }
    
      $Totals = array(); 
      $lineCode ='';
      $rowlines = Sumlinedata(trim($_POST['CustomerID']));
     foreach ($rowlines as $key1 => $lrow) {
        $lineCode= trim($lrow['code']);  
        if(strlen(trim($lrow['batchno']))>2){
            $docno=trim($lrow['batchno']);
        }else{
           $docno=trim($lrow['documentno']);
        }
         $addion=trim($docno).' '.$lrow['description'];
        $pdf->addTextWrap($collumrows[1],$YPos,80,$FontSize,ConvertSQLDate($lrow['docdate']),'left');
        $pdf->addTextWrap($collumrows[1]+ 100,$YPos,200,$FontSize,$addion,'left');
        if(($lrow['containercode']=='2')){
          $pdf->addTextWrap($collumrows[1]+ 250,$YPos,80,$FontSize,number_format($lrow['Quantity'],0),'right');
        }else{
          $pdf->addTextWrap($collumrows[1]+ 350,$YPos,80,$FontSize,number_format($lrow['Quantity'],0),'right');
        }
         $Totals[$lrow['code']] += ($lrow['containercode']=='1')?($lrow['Quantity']*-1):($lrow['Quantity']);
         $pdf->addTextWrap($collumrows[1]+450,$YPos,80,$FontSize,number_format($Totals[$lineCode],0).' KGS','right');
         $YPos -= $line_height;
        if($YPos<($lastrow+($line_height*2))){
            Pageheader();
            Preparecolums(1);
            $YPos = $Firstinvoicerow;
         }
     }
     
     if($YPos<($lastrow+$line_height)){
            Pageheader();
            Preparecolums(1);
            $YPos = $Firstinvoicerow;
     }
     
     $YPos -= $line_height;
     $pdf->addTextWrap($collumrows[1]+100,$YPos,300,$FontSize,'Summary of items inquanity Kgs','left');
      
     foreach ($Totals as $key => $value) {
         $YPos -= $line_height;
         $addion= $stockmasterp[$key]['descrip'];
         $pdf->addTextWrap($collumrows[1]+100,$YPos,200,$FontSize,$addion,'left');
         $pdf->addTextWrap($collumrows[1]+450,$YPos,80,$FontSize,number_format($Totals[$key],0).' KGS','right');
      }

     $pdf->OutputD($_SESSION['DatabaseName'] . '_TollBlendingStock_' . date('Y-m-d').'.pdf');
     $pdf->__destruct();
}

Function Sumlinedata($CustomerCode){
   global $db,$Saleslineinex,$mydat;
   $mydat=array();
   $Saleslineinex=0;
    
   $sqltxt= "select "
           . "`Salesline`.documentno,"
           . "`Salesline`.description,"
           . "`Salesline`.Quantity,"
           . "`Salesline`.containercode,"
           . "`Salesline`.docdate ,"
           . "`Salesline`.code "
           . " from `Salesline` "
           . " join SalesHeader on SalesHeader.documentno=`Salesline`.documentno "
           . " and SalesHeader.documenttype=`Salesline`.documenttype "
           . " where SalesHeader.`postinggroup`='".$CustomerCode."' "
           . " and `Salesline`.`documenttype`='40' "
           . " and (`Salesline`.`containercode`='1' or `Salesline`.`containercode`='2')"
           . " order by `Salesline`.docdate asc";
   $ResultIndex= DB_query($sqltxt,$db);
     while ($rowss= DB_fetch_array($ResultIndex)){
        $mydat[] = $rowss;
     }
    
    $finalarray=array();$checkcode=array();
    foreach ($mydat as $key => $value) {
        $finalarray[] = $value;
        if (in_array($value['code'],$checkcode) == false) {
        $checkcode[]=$value['code'];
        
        $sql= "select 
        `ProductionMaster`.`date`,
        stockmaster.descrip as description,
        `ProdcutionMasterLine`.`itemcode` as code ,
        `ProdcutionMasterLine`.`qty` as Quantity,
        `ProductionMaster`.`SalesHeader` ,
        `ProdcutionMasterLine`.`Batchno` 
        from `ProdcutionMasterLine`
        join `ProductionMaster` on `ProdcutionMasterLine`.`Batchno`=`ProductionMaster`.`Batchno` 
        join stockmaster on stockmaster.itemcode=ProdcutionMasterLine.`itemcode`
        where `ProductionMaster`.`itemcode`='".$value['code']."'"; 
      
        
         $ResultIndex= DB_query($sql,$db);
         while ($rows2= DB_fetch_array($ResultIndex)){
                     $finalarray[] = array(
                    "documentno"=>$value['documentno'],
                    "description"=>$rows2['description'],
                    "containercode"=>$value['containercode'],
                    "docdate"=>$rows2['date'],
                    "batchno"=>$rows2['Batchno'],
                    "code"=>$rows2['code'],
                    "Quantity"=>$rows2['Quantity']
                    );
                    
            $Saleslineinex++;
            
         }
         
    }
     }
    
    return $finalarray;
 }
 
 
 
 
Function DeletePOS($DOC){
    global $db;
    
    DB_query("Delete from `Salesline` where documentno='".$DOC."' and `documenttype`='40'", $db);
    DB_query("delete from `SalesHeader` where documentno='".$DOC."' and `documenttype`='40'", $db);
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

function Makepdf(){
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos,$Firstinvoicerow ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height,$collumrows ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$docno,$customer ;
 Global $firstrowpos,$lastrow,$narrative,$amount,$itemcode,$db ;

   $YPos = $Firstinvoicerow;
   $Totals =0; $count = 0;$currentno=0;
    $SQL=sprintf("select `documenttype`,`documentno`,`docdate`,`customercode`,`customername`,`userid` from SalesHeader where `postinggroup`='%s'",$_POST['CustomerID']);
    $ResultIndex= DB_query($SQL,$db);
    while($rowss= DB_fetch_array($ResultIndex)){
       $mydat[]=$rowss;
          $count++;
    }
   
    foreach ($mydat as $key => $row) {
      $pdf->addTextWrap($collumrows[1],$YPos,80,$FontSize,ConvertSQLDate($row['docdate']),'left');
      $rowlines = Getlinedata(trim($row['documentno']));
      foreach ($rowlines as $key2 => $lrow) {
         
        $pdf->addTextWrap($collumrows[1]+ 100,$YPos,200,$FontSize,trim($row['documentno']).' '.$lrow['description'],'left');
        if(($lrow['containercode']=='2')){
          $pdf->addTextWrap($collumrows[1]+ 250,$YPos,80,$FontSize,number_format($lrow['Quantity'],0),'right');
        }else{
          $pdf->addTextWrap($collumrows[1]+ 350,$YPos,80,$FontSize,number_format($lrow['Quantity'],0),'right');
        }
        
         $Totals += ($lrow['containercode']=='1')?($lrow['Quantity']*-1):($lrow['Quantity']);
         $pdf->addTextWrap($collumrows[1]+450,$YPos,80,$FontSize,number_format($Totals,0).' KGS','right');
         $YPos -= $line_height;
         
        if($YPos<($lastrow+$line_height)){
            $pdf->addTextWrap($collumrows[1]+450,$lastrow,80,$FontSize,number_format($Totals,0).' KGS','right');
            Pageheader();
            Preparecolums(1);
            $YPos = $Firstinvoicerow;
         }
         
         $currentno++;
         if($currentno==$count){
            $pdf->addTextWrap($collumrows[1]+400,$lastrow-12,80,12,'Net Balance','left');
            $pdf->addTextWrap($collumrows[1]+450,$lastrow-12,80,12,number_format($Totals,0).' KGS','right');
         }
           
     }
   }
 
    $pdf->OutputD($_SESSION['DatabaseName'] . '_TollBlendingStatement_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
}

function Pageheader(){
    
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$docno,$customer ;
 Global $firstrowpos,$lastrow,$narrative,$amount,$itemcode,$db ;
    
 $FontSize = 11;
 $PageNumber++;
// Inserts a page break if it is not the first page
if($PageNumber>1) {
   $pdf->newPage();
}
    
$XPos=46;
$pdf->Rect(12,10,$Page_Width-$Left_Margin+12,$Page_Height-$Top_Margin+10, "D");
// le detail des totaux, demarre a 221 après le cadre des totaux
$pdf->SetLineWidth(0.1); 
// line($x1,$y1,$x2,$y2,$style=array())
$Middlepage = ($Page_Width/2);
$pdf->line($Middlepage,$Page_Height-$Top_Margin-155,$Middlepage,$Page_Height-$Top_Margin+20);
$topRow = $Page_Height-$Top_Margin-$FontSize * 2;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Right_Margin+5,$Page_Height-$Top_Margin-50, 0,40);
$YPos=$topRow-25;
 
$pdf->addText($Right_Margin,$YPos,$FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Right_Margin, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Right_Margin, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Right_Margin, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . '/' . $_SESSION['CompanyRecord']['regoffice4'] );
$pdf->addText($Right_Margin, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] );
$pdf->addText($Right_Margin, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($Right_Margin, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

// Print company info
$XPos = 60;
$FontSize=10;

$pdf->SetFillColor(255, 255, 0);
$pdf->addTextWrap($Page_Width-$Right_Margin-100,$Page_Height-$Top_Margin-$FontSize * 2,100,$FontSize, _('Page').': '.$PageNumber, 'right');

$YPos = $Page_Height-$Top_Margin-155;
$pdf->line(10,$YPos,$Page_Width-$Right_Margin+12,$YPos);
$pdf->addText($Right_Margin,$YPos -= $line_height ,$FontSize,'Customer Account Name:');

$sqlcreditors=DB_query("SELECT (`customer`+`curr_cod`),middlen as PIN,`phone`,`email`,`city` FROM `debtors` where itemcode='".$_POST['CustomerID']."'", $db);
$vendorrow=DB_fetch_row($sqlcreditors);
$pdf->addText($Middlepage-50,$YPos ,$FontSize,htmlspecialcharsLocal_decode($vendorrow[0]));
$pdf->addText($Middlepage-50,$YPos-=$line_height,$FontSize,'PIN:'.$vendorrow[1]);
$pdf->addText($Middlepage-50,$YPos-=$line_height,$FontSize,$vendorrow[2]);
$pdf->addText($Middlepage-50,$YPos-=$line_height,$FontSize,$vendorrow[3]);
$pdf->addText($Middlepage-50,$YPos-=$line_height,$FontSize,$vendorrow[4]);
$pdf->addText($Middlepage-50,$YPos-=$line_height,14,'Toll Blending Statement:');
$pdf->line(10,($YPos - ($line_height * 3)),$Page_Width-$Right_Margin+12,($YPos - ($line_height * 3)));
$pdf->line(10,($YPos - ($line_height * 5.5)),$Page_Width-$Right_Margin+12,($YPos - ($line_height * 5.5)));

$firstrowpos = ($YPos - ($line_height * 5))  ;
$lastrow     = $Bottom_Margin + ($line_height * 2);

$pdf->line(10,$lastrow+$line_height,$Page_Width-$Right_Margin+12,$lastrow+$line_height);
$pdf->addText($Right_Margin,$lastrow-$line_height,9,"When the balance is negative this means that the customer has been over supplied");

$YPos -= (2 * $line_height);

}

function Preparecolums($columno){
    Global $Right_Margin,$YPos,$line_height,$FontSize,$firstrowpos;
    global $pdf,$Firstinvoicerow,$collumrows;
            
    $YPos = $firstrowpos+$line_height;
    $pdf->addText($collumrows[$columno],$YPos,$FontSize,'Date');
    $pdf->addText($collumrows[$columno]+ 100,$YPos ,$FontSize,'Document No');
    $pdf->addText($collumrows[$columno]+ 300,$YPos ,$FontSize,' Stock In');
    $pdf->addText($collumrows[$columno]+ 380,$YPos ,$FontSize,' Stock Out');
    $pdf->addText($collumrows[$columno]+ 460,$YPos+$line_height ,$FontSize,' Cummulative');
    $pdf->addText($collumrows[$columno]+ 470,$YPos ,$FontSize,' Weight');
    $Firstinvoicerow=$YPos-($line_height*3);

}

function getbachno($itemcode,$docref,$transdate){
    global $db;
    
      $selectedobject=array();
      foreach ($_POST['units'][$docref] as $subject){
          $selectedobject[$subject]='checked="checked"';
      }
         
    $cmd='';
     
     $SQL=SPRINTF("SELECT 
           `ProductionMaster`.`Batchno`  
          ,`ProductionMaster`.`production`
          ,`ProductionMaster`.`itemcode`  
          ,stockmaster.descrip
          ,`ProductionMaster`.`date`
          ,`ProductionMaster`.`SalesHeader`
      FROM `ProductionMaster` 
      join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      where `ProductionMaster`.`itemcode`='%s' and (`ProductionMaster`.`date`
      between DATE_ADD('%s', INTERVAL -7 DAY) and DATE_ADD('%s', INTERVAL 1 DAY))
       order by `date` desc",$itemcode,$transdate,$transdate);
   
     $ResultIndex=DB_query($SQL,$db);
    while($HeaderRow = DB_fetch_array($ResultIndex)){
         $code = trim($HeaderRow['Batchno']);
         $SalesHeader = trim($HeaderRow['SalesHeader']);
         $selected = $selectedobject[$code];
      
         if($docref==$SalesHeader){
             $selected ='checked="checked"';
         }
         $Objid=$docref.$code;
      
         $tooltip = 'Production batch no: '.$HeaderRow['Batchno'].' Dated: '.ConvertSQLDate($HeaderRow['date']).' QTY :'.$HeaderRow['production'];
         $show =' QTY :'.$HeaderRow['production'].' '.$HeaderRow['Batchno'];
          $cmd .='<input class="checkbox" type="checkbox"  '.$selected.'  data-toggle="tooltip" data-placement="right" data-original-title="'.$tooltip.'" id="'.$Objid.'"  name="allocate['.$docref.']['.$code.']" value="1"><label for="'.$Objid.'">'.$show.'</label>'
                  . '<br>';
    }
      return $cmd;
}





function getbachnoentry($itemcode,$transdate){
    global $db;
    
    $cmd='<select name="batchno" onchange="ReloadForm(prodform.refresh)" >';
     $SQL=SPRINTF("SELECT 
           `ProductionMaster`.`Batchno`  
          ,`ProductionMaster`.production
          ,`ProductionMaster`.`itemcode`  
          ,stockmaster.descrip
          ,`ProductionMaster`.`date`
      FROM `ProductionMaster` 
      join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      where `ProductionMaster`.`itemcode`='%s' and (`ProductionMaster`.`date`
      between DATE_ADD('%s', INTERVAL -7 DAY) and DATE_ADD('%s', INTERVAL 1 DAY))
      order by `date` desc",$itemcode,$transdate,$transdate);
     
    $ResultIndex=DB_query($SQL, $db);
    while($HeaderRow = DB_fetch_array($ResultIndex)){
           $code = trim($HeaderRow['Batchno']);
           $whichCode = trim($_POST['batchno']);
           $selected = (($code==$whichCode)?'selected="selected"':'');
           $cmd .='<option value="'.$code.'" '.$selected.'>'.$HeaderRow['Batchno'].' : '.$HeaderRow['descrip'].'Dated '.ConvertSQLDate($HeaderRow['date']).' Production :'.$HeaderRow['production'].'</option>';
    }
      $cmd .='</select>';
      return $cmd;
}
