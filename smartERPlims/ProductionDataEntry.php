<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
$Title = _('Production');
include('includes/productionheader.inc');
include('transactions/stockbalance.inc');   
include('production/poscart.inc');

$mypage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$POSclass = new AssembleAdjust();

if(!isset($_POST['date'])){ 
    unset($_SESSION['stockmasterp']);
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
}

if(!isset($_SESSION['units'])){
            $ResultIndex=DB_query("select code, descrip from unit",$db);
             while($row = DB_fetch_array($ResultIndex)){
                $code = trim($row['code']);
                $_SESSION['units'][$code]=$row;
            }
       }

if(!isset($_SESSION['stockmasterp'])){
    $SQL = "SELECT itemcode,barcode,descrip,isstock from stockmaster 
            where inactive=0 and isstock_2=1 
            order by descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmasterp'][$code]=$row;
    }
    $_SESSION['stockmasterp']['H2O']=array('itemcode'=>'H2O','barcode'=>'H2O','descrip'=>'WATER');
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
            . "join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode  ", $db);
    while($value=DB_fetch_array($ResultIndex)){
            $_SESSION['ProductionUnit'][]=$row;
   }
}


if(isset($_POST['SaveProduction'])){
   
  
  $pi = new ProcessInformationAlter();
  $pi -> get($_POST,$_SESSION['work_orders'],$POSclass);
 
  if($pi -> anythingwrong()=='NO'){ 
      unset($_POST);$POSclass->neworder();unset($_SESSION['work_orders']);
  }

}

echo '<div class="centre">';
echo '<form autocomplete="off"action="'. $mypage .'" method="post" id="prodform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

?>

<table class="table-condensed table-responsive-small"><tr><td valign="top"><?php 
    if(isset($_GET['Batchno'])){$_POST['documentno']=$_GET['Batchno']; 
    $POSclass->RepopulateProduction($_GET['Batchno']);
    }
    DisplayPOS($POSclass); ?></td><td valign="top" >
<table class="table-condensed table-responsive-small"><tr><td valign="top"><?php Entry(); ?></td></tr>
    <tr><td valign="top"><?php getstocklist(); ?><td></tr></table>
</td></tr></table>

<?php
echo '</div></form>';
 

include('includes/footer.inc');


function getstocklist(){
   Global $db;
    $SQL="SELECT 
       `ProductionMaster`.`Batchno`
      ,`ProductionMaster`.`date`
      ,`www_users`.`realname`
      ,`ProductionMaster`.`itemcode`
      ,stockmaster.descrip
  FROM `ProductionMaster`
      join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      left join `www_users` on `ProductionMaster`.`userid`=`www_users`.`userid`
      where (modified is  null) or (modified=0) order by date desc";

   $return .= '<div>'
       . '<table class="table table-bordered">'
       . '<tr>'
       . '<th>Batch No</th>'
       . '<th>Product</th>'
       . '<th>Date Received</th>'
       . '<th>Produced By</th>'
       . '</tr>';
      
      $ResultIndex = DB_query($SQL,$db);
      while($row = DB_fetch_array($ResultIndex)){
      
      $return .= sprintf('<tr><td><a href="%s?Batchno=%s">%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', 
                  $pge, $row['Batchno'],htmlentities($row['Batchno']),$row['descrip'],ConvertSQLDate($row['date']), $row['realname']
                  );
      }
      
    $return .= '</table></div>';
           
   echo $return;
}

Function Entry(){
      Global $db;
  
      echo '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Barcode</td><td><input class="col-sm-push-3" type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>No of units</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" readonly/></td></tr>'
    . '<tr><td>Units Measure In</td><td><select id="packid" name="units">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr><tr><td colspan="2">';
        $code = $_POST['units']; $Inpacks  =  $_SESSION['units'][$code]['descrip'];
         
        echo substr($_SESSION['stockmasterp'][$_POST['stockitemcode']]['descrip'],0,30).'... Opening Bal :'.$_SESSION['stockmasterp'][$_POST['stockitemcode']]['Balance'].' '.$Inpacks;
        echo '</td></tr><tr><td><input type="submit" name="update"  value="Add/Update Record" class="btn-info" /></td>'
        . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
        . '</table>';
       
}

function DisplayPOS($POSclass){
global $db;
    
 $POSclass->Getitems($_POST['stockitemcode'], $_POST['qty'],$_POST['units']);
 
echo '<table class="table-bordered"><tr>'
    . '<td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
echo '<tr><td>Batch No</td><td><input tabindex="2" readonly="readonly" type="text" name="documentno" value="'.$_POST['documentno'].'"   required="required" /></td></tr>';
echo '<table class="table-bordered">'
        . '<thead><tr>'
        . '<th><label>Item code</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>STORE/TANK</label></th>'
        . '<th><label>BALANCE</label></th>'
        . '<th><label>Unit Descrip</label></th>'
        . '<th><label>Unit Cost</label></th>'
        . '<th><label>Percentage</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>Total Cost</label></th>'
        . '</tr>'
        . '</thead>';
  echo $_SESSION['htmltable'];
  
  echo sprintf('</table><table><tr id="SaveProduction"><td>
	<input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="submit" name="%s" value="'._('Produce/Manufacture').'"/></td>
        </table>',$_SESSION['altername_SaveProduction']);  
}
 

