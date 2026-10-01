<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('Price List');
include('includes/header.inc');

$POSclass = new PriceList();
if(isset($_GET['new'])){
    $POSclass->getready();
}

if(isset($_GET['delid'])) {
   $_POST['CustomerID']= $_GET['cu'];
   $POSclass->deletedata($_GET['delid']);
   unset($_POST['CustomerID']);
   unset($_POST['CustomerName']);
}
 
if(isset($_POST['remove'])){
   $POSclass->deleteRow($_POST['childid']);
} 

$_SESSION['containers']=array();
    $result=DB_query("SELECT itemcode,descrip FROM stockmaster where isstock_6=1  ",$db);
        while ($myrow = DB_fetch_array($result)) {
          $code = trim($myrow['itemcode']);
           $_SESSION['containers'][$code]=$myrow;
    } 
    
$_SESSION['stockmaster']=array();

$SQL = "SELECT itemcode,barcode,descrip"
     . " from stockmaster "
     . " where inactive=0 "
     . " order by descrip";
$ResultIndex=DB_query($SQL, $db);
while($row = DB_fetch_array($ResultIndex)){
    $code = trim($row['itemcode']);
    $_SESSION['stockmaster'][$code]=$row;
}


$_SESSION['CustomerTable']=array();
$SQL = "SELECT itemcode,customer from debtors join PriceList 
      on PriceList.customerCode=debtors.itemcode group by itemcode,customer";
$ResultIndex=DB_query($SQL, $db);
while($row = DB_fetch_array($ResultIndex)){
    $code = trim($row['itemcode']);
    $_SESSION['CustomerTable'][$code]=$row;
}

 
$_SESSION['units']=array();
    $ResultIndex=DB_query("select code, descrip from unit",$db);
        while($row = DB_fetch_array($ResultIndex)){
           $code = trim($row['code']);
           $_SESSION['units'][$code]=$row;
       }


        

 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
if (!defined('GRIDFORM_CSS')) {
    define('GRIDFORM_CSS', 1);
    echo '<style>
    .gridform{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem 1rem;margin-bottom:0.5rem;}
    .gridform .form-group{display:flex;flex-direction:column;gap:0.25rem;}
    .gridform label{font-weight:500;font-size:0.8rem;margin-bottom:0;}
    .gridform input,.gridform select,.gridform button{max-width:100%;}
    @media(max-width:768px){.gridform{grid-template-columns:1fr 1fr;}}
    @media(max-width:520px){.gridform{grid-template-columns:1fr;}}
    </style>';
}

echo '<div class="gridform">'
    . '<div class="form-group"><label for="searchcustomer">'._('Customer').'</label>'
    . '<div style="display:flex;gap:4px;"><input type="hidden" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'" size="5" />'
    . '<input type="button" id="searchcustomer" value="Search Customer" style="min-width:0;" /></div>'
    . '<input type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'" size="20" required="required" readonly="readonly" /></div>'
    . '<div class="form-group"><label>&nbsp;</label>'
    . '<input type="submit" name="Refresh" value="Refresh" class="btn-info" /></div>'
    . '</div>'
    . '<input type="hidden" id="currencycode"  name="currencycode"  value="'.$_POST['currencycode'].'" />'
    . '<input type="hidden" id="salespersoncode"  name="currencycode"  value="'.$_POST['salespersoncode'].'" />';

?>

<table class="table-condensed table-responsive-small">
    <tr><td valign="top" colspan="2">
            <?php  
            
        $CustID=trim($_POST['CustomerID']);
        if(mb_strlen($CustID)>0){
            echo '<div id="CustPricelist" onmouseover="dragElement(this);">
                <input type="button" onclick="hidewindow(\'CustPricelist\')" value="Close" class="rightside"/> 
        <div class="help-block">PRICE SUMMARY</div>';
              echo '<div class="upcomming" style="max-height:20%;">';
              $POSclass->copyPriceList($CustID);
              echo '</div>';
             echo '</div>' ;  
        }else{
            
            if($_POST['submit']!='Refresh'){
            echo '<div id="CustPricelist" onmouseover="dragElement(this);">
                <input type="button" onclick="hidewindow(\'CustPricelist\')" value="Close" class="rightside"/> 
             <div class="help-block">PRICE SUMMARY</div>';
            $POSclass->getready();
            $no=1; $anydata=false; 
           
            $table ='<div class="upcomming" style="max-height:20%;"><table class="table-bordered">';
             foreach ($_SESSION['CustomerTable'] as  $cust) {
                $anydata = true;
                $table .=  sprintf('<tr><th colspan="7"> %s : %s PRICE LIST</th></tr>',$no,$cust['customer']);
                $table .='<tr>'
                    . '<td>BARCODE</td>'
                    . '<td>ITEM DESCRIPTION</td>'
                    . '<td>PACKING</td>'
                    . '<td class="number">PRICE</td>'
                    . '<td class="number">UNIT PRICE</td>'
                    . '<td> </td>'
                    . '<td> </td>'
                    . '<td>TAT</td>'
                    . '</tr>';
                $table .=  $POSclass->SummaryPriceList($cust['itemcode']);
                $no++;
             }
             
            $table .='</table></div>';
             
             if($anydata){
                 echo $table;
             }
              echo '</div>' ;
            }  
                  
            
        } 
        
        ?></td></tr>
    
    
</td></tr></table>
 
<?php
echo '</div></form>';
include('includes/footer.inc');
