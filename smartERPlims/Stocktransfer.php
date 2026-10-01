<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/ClassStockIssues.inc');
include('transactions/stockbalance.inc');   
$Title = _('Stock Transfer');
include('includes/header.inc');
$POSclass = new Transfers();
 
 if(isset($_GET['new'])){
    $POSclass->neworder();
 }
 
 if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code,descrip from unit",$db);
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
    
 unset($_SESSION['stockmaster']) ;
     $SQL = "SELECT itemcode,barcode,descrip
     from stockmaster where isstock_2=1 order by descrip";
    $ResultIndex=DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
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
     
     if($_POST['submit']=='Save Transfer'){
            $SQL=array();

            foreach ($_SESSION['TransferFromStore'] as $key => $row) {

                  if(($row['stkbalance'] >= 0) and ($row['tankstore']=='S')){

                      $journalno = GetNextTransNo(16,$db);
                      $Periodno  = GetPeriod($_POST['date'], $db,true);
                      $Date = FormatDateForSQL($_POST['date']);
                      $fulTrue = (($row['InPacks']==1)?False:TRUE);

                      $SQL[]="INSERT INTO `stockledger`
                           (`date`,`stname`,`doctyp`,`itemcode`,`invref`,`fulqty`,`loosqty`,`price`,`store`,`journal`,`period`,`partperunit`)
                            VALUES ('".$Date."'
                                ,'Stock Transfer' 
                                ,'16'
                                ,'".$row['itemcode']."'"
                               . ",'".$journalno."'"
                               . ",'".(($fulTrue)?$row['quantity']* -1:'0')."'"
                               . ",'".(($fulTrue)?0:$row['quantity']* -1)."'"
                               . ",".$row['averagestock'].""
                               . ",'".$_POST['location_to'][$key]."'"
                               . ",'".$journalno."'"
                               . ",'".$Periodno."'"
                               . ",'".$row['InPacks']."'"
                               . ")";

                     $SQL[]="INSERT INTO `stockledger`
                           (`date`,`stname`,`doctyp`,`itemcode`,`invref`,`fulqty`,`loosqty`,`price`,`store`,`journal`,`period`,`partperunit`)
                             VALUES ('".$Date."'
                                ,'Stock Transfer' 
                                ,'16'
                                ,'".$row['itemcode']."'"
                               . ",'".$journalno."'"
                               . ",'".(($fulTrue)?$row['quantity']:'0')."'"
                               . ",'".(($fulTrue)?0:$row['quantity'])."'"
                               . ",".$row['averagestock'].""
                               . ",'".$_POST['location'][$key]."'"
                               . ",'".$journalno."'"
                               . ",'".$Periodno."'"
                               . ",'".$row['InPacks']."'"
                               . ")"; 


                       DB_Txn_Begin($db);
                       foreach($SQL as $value) {
                           DB_query($value,$db);
                       }

                       if(DB_error_no($db)>0){
                           DB_Txn_Rollback($db);
                       }else{
                           DB_Txn_Commit($db);
                           prnMsg('You have created transfer Document '.$journalno .' successfuly ','succes');
                      }

                  
                  }else{
                      
                      if(($row['stkbalance'] < 0)){
                          prnMsg('You do not have enough units to transfer '.$row['stockname'],'warn');
                      }
                      
                      if( ($row['tankstore']=='T')){
                          prnMsg('You should not transfer '.$row['stockname'].' from a tank ','warn');
                      }
                      
                  }
            }

         $POSclass->neworder();
     } 
     
     if($_POST['submit']=='Delete Order'){
         DeletePOS($_SESSION['CompleteDocument']);
         unset($_SESSION['CompleteDocument']);
     }
     
 }elseif(isset($_POST['refresh']) or !isset($_POST['submit'])){
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

?>

<table class="table-condensed table-responsive-small"><tr><td valign="top"><?php DisplayPOS($POSclass); ?></td><td valign="top" >
            <table class="table-condensed table-responsive-small"><tr><td valign="top"><?php Entry(); ?></td></tr><tr><td valign="top"><?php getstocklist(); ?><td></tr></table>
</td></tr></table>

<?php
echo '</div></form>';
 }
 
 
include('includes/footer.inc');

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
    . '<tr><td>No of units</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" required="required" value="'.$_POST['qty'].'"/></td></tr>'
    . '<tr><td>Units Measure In</td><td><select id="packid" name="units">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr>'
    . '<tr><td>Each Unit Contains</td><td><input class="number col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="packzize" name="packzize" required="required" value="'.$_POST['packzize'].'"/></td></tr>'
   
         . '<tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td>'
        . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
        . '</table>';
       
}

function DisplayPOS($POSclass){
global $db;
    
$_POST['documentno'] = GetTempNextNo(16);
 
$POSclass->Getitems($_POST['stockitemcode'], $_POST['qty'],$_POST['units'],$_POST['packzize']);
echo '<table class="table-bordered table-condensed">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
   . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
   . '</tr>';

echo '<tr><td>Employee ID</td>'
    . '<td><input tabindex="4" type="text" name="CustomerID" id="EmployeeID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
    . '<input type="button" id="searchemployee" value="Search Employee"/></td>'
    . '<td>Employee Name</td>'
    . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="EmployeeName" value="'.$_POST['CustomerName'].'"  size="20"  required="required" /></td></tr>'
   . '</table>';

echo '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>Item code</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>Store/Tank</label></th>'
        . '<th><label>New Balance</label></th>'
        . '<th><label>Unit Descrip</label></th>'
        . '<th><label>Unit Cost</label></th>'
        . '<th><label>Total Cost</label></th>'
        . '</tr>'
        . '</thead>';
     
     
  echo $_SESSION['htmltable'];
        
  echo '</table><table><tr><td>
	<input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="'.$_SESSION['hideOrShow'].'" name="submit" value="'._('Save Transfer').'" /></td>
        </table>';  
}
 
Function DeletePOS($DOC){
    global $db;
    
    DB_query("Delete from `Salesline` where documentno='".$DOC."' and `documenttype`='16'", $db);
    DB_query("delete from `SalesHeader` where documentno='".$DOC."' and `documenttype`='16'", $db);
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

 