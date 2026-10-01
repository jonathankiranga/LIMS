<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/stockbalance.inc');   
include('production/poscart.inc');

$ClassVcf = new CalculateVCF();

$mypage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$POSclass = new NewAssemble();

if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
     while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['code']);
        $_SESSION['units'][$code]=$row;
    }
}

if(!isset($_POST['date'])){ 
    unset($_SESSION['stockmasterp']);
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
    
     $SQL = "SELECT itemcode,barcode,descrip,units,isstock,production from stockmaster 
            where inactive=0 and (isstock_2=1 or isstock_4=1 or isstock_5=1)
            order by descrip";
    $ResultIndex=DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']); 
        $units= trim($row['units']);
        $Inpacks = $_SESSION['units'][$units]['descrip'];
        
        $_SESSION['stockmasterp'][$code]=array('itemcode'=>$row['itemcode'],
            'barcode'=>$row['barcode'],'descrip'=>$row['descrip'],
            'isstock'=>$row['isstock'],'production'=>$row['production'],
            'units'=>$Inpacks);
    }
    
    $_SESSION['stockmasterp']['H2O']=array('itemcode'=>'H2O',
        'barcode'=>'H2O','descrip'=>'WATER','units'=>'litre','isstock'=>'0',
        'production'=>'06');
    
}

if(!isset($_SESSION['Stores'])){
    $REsults=DB_query('SELECT '
            . '`code`,'
            . '`Storename` '
            . 'FROM `Stores`', $db);
    $x=0;
    while($row= DB_fetch_array($REsults)){
        $_SESSION['Stores'][$x]=$row;
        $x++;
    }
}   

if(!isset($_SESSION['ProductionUnit'])){
$ResultIndex=DB_query("select tankname, stockmaster.descrip from `ProductionUnit` "
    . " join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode  ", $db);
    while($value=DB_fetch_array($ResultIndex)){
            $_SESSION['ProductionUnit'][]=$row;
   }
}

if(isset($_POST['SaveProduction'])){
    
     $prodcode = trim($_POST['proitemcode']);
     $SQL = sprintf("SELECT isstock from stockmaster where itemcode='%s' ",$prodcode);
     $ResultIndex=DB_query($SQL,$db);
     $row = DB_fetch_row($ResultIndex);
     $testpH = ((($row['0'])==1)?'1':'0');
  
    if(($_POST['bitumenph']=='7') && $testpH=='1'){
         prnMsg('Please check your pH Value','warn');
    }else{
        $_POST['documentno'] = GetNextTransNo(28,$db);
        $pi = new ProcessInformation();
        $pi -> get($_POST,$_SESSION['work_orders'],$POSclass);

        if($pi -> anythingwrong()=='NO'){ 
            unset($_POST);$POSclass->neworder();unset($_SESSION['work_orders']);
        }
    }
}

$Title = _('Production');
include('includes/productionheader.inc');
echo '<form autocomplete="off" action="'.$mypage.'" method="post" id="prodform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<div class="centre">';

 $temp =(int) trim($_POST['Temperature']);
 $_POST['VCF'] =(float) $ClassVcf->VCF($temp);
?>
   <table class="table-condensed table-responsive-small">
     <tr><td valign="top"><?php Entry(); ?></td><td rowspan="2" valign="top"><?php DisplayPOS($POSclass); ?></td></tr>
    </table>

<?php
echo '</div></form>'
. '</div>';

include('includes/footer.inc');

Function Entry(){
      Global $db,$POSclass,$ClassVcf;
      
    echo '<p class="page_title_text">Select The Item for production</p>';
      
    echo '<table class="table-bordered"><caption>Select Product</caption><tr><td colspan="6">'
    . '<input type="hidden" id="proitemcode" size="5" name="proitemcode" value="'.$_POST['proitemcode'].'" readonly="readonly"/>'
    . '<table class="table-bordered">'
    . '<tr><td>Product To Produce</td><td colspan="4"><input type="button" id="searchProstock" value="Search stock"/>'
    . '<input type="text"  size="50" id="Prostockname" name="Prostockname" value="'.$_POST['Prostockname'].'" readonly="readonly"/></td></tr>'
     . '</table></td></tr></table>';
 
     echo '<p class="page_title_text">After selecting the item for production ,<br/>'
     . 'the next thing is that you select the raw materials and quantity</p>';
     
      echo '<table class="table table-bordered"><caption>Quantity Window</caption><tr><td>'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td colspan="2"><input class="col-sm-push-3" type="hidden" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>No of units</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" value="'.$_POST['qty'].'"/></td></tr>'
    . '<tr><td>Units Measure In</td><td><select id="packid" name="packid">';
             
     
      
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); 
           $selunit =(($_POST['packid']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr>'
    . '<tr><td>Volume Correction Factor <br/>for bitumen</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10"  name="VCF"  id="VCF"  value="'.$_POST['VCF'].'"/></td></tr>'
    . '<tr><td>Observed Temperature<br/> for bitumen</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10"  name="Temperature" id="TEMPID" value="'.$_POST['Temperature'].'"/></td></tr>'
    . '<tr><td colspan="2">';
        $code = $_POST['packid']; $Inpacks  =  $_SESSION['units'][$code]['descrip'];
        echo substr($_SESSION['stockmasterp'][$_POST['stockitemcode']]['descrip'],0,30).'... Opening Bal :'.$_SESSION['stockmasterp'][$_POST['stockitemcode']]['Balance'].' '.$Inpacks;
        echo '</td></tr><tr><td><input type="submit" name="update"   value="Add/Update Record" class="btn-info" /></td>'
        . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
        . '</table>';
        
         $POSclass->loaddefaultitems($_POST['proitemcode']);
      
}

function DisplayPOS($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(28);

if($_POST['remove']){
    $POSclass->RemoveOrder($_POST['stockitemcode']);
}

 $POSclass->Getitems($_POST['stockitemcode'], $_POST['qty'],$_POST['packid'],$_POST['VCF'],$_POST['Temperature']);
 $tankAllowedCapacity = $POSclass->tankresults($_POST['ProdStore'],$_POST['proitemcode']);
 $capacity = (float) $POSclass->TotalProduction;
 if($tankAllowedCapacity>0){ $Reperent = (($capacity/$tankAllowedCapacity)*100); }else{ $Reperent = 0;}
echo '<table class="table-bordered"><tr>'
    . '<td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
echo '<tr><td>Batch No</td><td><input tabindex="2" readonly="readonly" type="text" name="documentno" value="'.$_POST['documentno'].'"  required="required"/></td></tr>';
echo '<tr><td>For The Production of</td><td>'.$_POST['Prostockname'].'</td></tr>';
echo '<tr><td>Enter The pH of Bitumen</td><td><div class="slidecontainer"><input type="range" id="phrange" min="0" max="14" step="0.1" name="bitumenph" value="'.$_POST['bitumenph'].'" class="slider" oninput="document.getElementById(\'pHid\').innerText = parseFloat(this.value);pHid=parseFloat(this.value)"/></div></td></tr>';
echo '<tr><td>This pH</td><td><output id="pHid">'.$_POST['bitumenph'].'</output></td></tr>';
echo '<tr><td><input type="hidden" name="TotalProductionCost" value="'.$_SESSION["runningnettotal"].'"/>Select Store/Tank</td><td><select name="ProdStore" onchange="ReloadForm(prodform.refresh)">'.$POSclass->ToStoreTankForItem($_POST['proitemcode']).'</select></td></tr>'
    . '<tr><td>Tank Capacity in ltrs :</td><td><input type="text" class="number" name="TankAllowedCapacity" value="'.$tankAllowedCapacity.'" readonly="readonly" /></td></tr>'
    . '<tr><td>Your not allowed to produce more than this limit .</td><td> Now at '. number_format($Reperent,1).' %  <input type="hidden" name="TotalProduction" value="'.$POSclass->TotalProduction.'"/>'
        . '<input type="hidden" name="productionTankStore" value="'.$POSclass->productionTankStore.'"/></td></tr>';
 
echo '<div class="container"><table class="table-bordered">'
        . '<thead><tr>'
        . '<th><label>Item code</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>Quanity <br/> Before VCF</label></th>'
        . '<th><label>STORE<br/>TANK</label></th>'
        . '<th><label>BALANCE</label></th>'
        . '<th><label>Quanity<br/>Descrip</label></th>'
        . '<th><label>Stock<br/>Cost</label></th>'
        . '<th><label>Percentage</label></th>'
         . '<th><label>Vol Corec.<br/> Factor</label></th>'
        . '<th><label>Temperature</label></th>'
        . '<th><label>Quanity<br/>After VCF</label></th>'
        . '<th><label>Total Cost</label></th>'
        . '</tr>'
        . '</thead><tbody>';
 echo $_SESSION['htmltable'];
  echo '<tr>'
        . '<td colspan="9">TOTOALS</td>'
        . '<td class="number"><label>Unit Cost:'.(($POSclass->TotalProduction>0)? number_format($_SESSION["runningnettotal"]/$POSclass->TotalProduction,2):'0').'</label></td>'
        . '<td class="number"><label>'.$POSclass->TotalProduction.'</label></td>'
        . '<td class="number"><label>'.$_SESSION["runningnettotal"].'</label></td>'
        . '</tr>';
    echo '</tbody></table></div>';
  echo sprintf('<table><tr id="SaveProduction"><td>
	<input type="submit" id="refresh" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="submit" name="%s" value="'._('Save Data and proceed to Lab Test').'"/></td>
        </table>',$_SESSION['altername_SaveProduction']);  
  
 
}



?>
<script type="text/javascript">
   document.getElementById('pHid').innerText = parseFloat(document.getElementById('phrange').value);
</script>