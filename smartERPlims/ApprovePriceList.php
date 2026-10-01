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

$SQL = "SELECT itemcode,barcode,descrip"
     . " from stockmaster "
     . " where inactive=0 "
     . " order by descrip";
$ResultIndex=DB_query($SQL, $db);
while($row = DB_fetch_array($ResultIndex)){
    $code = trim($row['itemcode']);
    $_SESSION['stockmaster'][$code]=$row;
}

if(!isset($_SESSION['CustomerTable'])){
    $SQL = "SELECT itemcode,customer from debtors where inactive=0 order by customer";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['CustomerTable'][$code]=$row;
    }
}
   
if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
        while($row = DB_fetch_array($ResultIndex)){
           $code = trim($row['code']);
           $_SESSION['units'][$code]=$row;
       }
}   

if(isset($_POST['action'])){
    foreach ($_POST['action'] as $key => $value) {
        switch ($value) {
            case 'Approve':
                $SQL = sprintf("UPDATE PriceList set approved=1,`approvedby`='%s'
                             ,`DateTime`='%s' where id='%s' ", $_SESSION['UserID'],gmdate('Y-m-d H:i:s'),$key);
                        $ResultIndex = DB_query($SQL,$db); 

                break;

             case 'Reject':
                  $SQL = sprintf("UPDATE PriceList set approved=0,`approvedby`='%s'
                             ,`DateTime`='%s' where id='%s' ", $_SESSION['UserID'],gmdate('Y-m-d H:i:s'),$key);
                        $ResultIndex = DB_query($SQL,$db); 

                break;
        }
        
        
        
    }
     
 }
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

?>

<table class="table-condensed table-responsive-small">
    <tr><td valign="top"><?php DisplayPOS(); ?></td></tr>
</td></tr></table>
 
<?php
echo '</div></form>';
include('includes/footer.inc');


function DisplayPOS(){
    global $db;
    
    if (!defined('STDGROUP_CSS')) {
        define('STDGROUP_CSS', 1);
        echo '<style>.std-group td{background:#edf2fb !important;font-weight:bold;} .std-group-item td:first-child{padding-left:30px !important;}</style>';
    }
    
    echo '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>Customer Account</label></th>'
        . '<th><label>Stock Description</label></th>'
        . '<th><label>Measured <br/>In What Package</label></th>'
        . '<th class="number"><label>Measurement<br/> Quantity</label></th>'
        . '<th class="number"><label>Requsted Sales Price<br/>as Per <br/>Measured Quantity</label></th>'
        . '<th class="number"><label>Requsted Sales Price<br/> Per <br/>Unit</label></th>'
        . '<th class="number"><label>Company Sales Price<br/>Per <br/>Unit</label></th>'
        . '<th><label>Approve Or Reject</label></th>'
        . '<th>Approval Status</th>'
        . '</tr>'
        . '</thead>';
    
            $SQL="select PriceList.customercode,PriceList.stockcode,PriceList.units_code,
                PriceList.quantity,PriceList.price,PriceList.id,PriceList.approved,PriceList.`approvedby`
      ,PriceList.`DateTime`,debtors.customer,"
      . " CASE WHEN PriceList.stockcode LIKE 'TS____' THEN PriceList.stockcode WHEN dt.categoryid LIKE 'TS____' THEN dt.categoryid ELSE CONCAT('~',PriceList.stockcode) END AS grp"
      . " from PriceList 
      join debtors on PriceList.customerCode=debtors.itemcode 
      LEFT JOIN discounttable dt ON dt.itemcode = PriceList.stockcode AND dt.categoryid LIKE 'TS____'
      LEFT JOIN stockmaster sm ON sm.itemcode = PriceList.stockcode
      where (`DateTime` is null  or  approved is null) 
      order by grp asc, (PriceList.stockcode LIKE 'TS____') desc, sm.descrip asc, customercode asc";
            $ResultIndex=DB_query($SQL,$db);
            while($row= DB_fetch_array($ResultIndex)){
               $itemcode  = trim($row['stockcode']); 
               $unitscode = trim($row['units_code']); 
               $StockList = $_SESSION['stockmaster'][$itemcode];
               $unitname  = $_SESSION['units'][$unitscode]['descrip'];
               $qty       = $row['quantity'];
               $price     = $row['price']; 
               $Rid       = $row['id'];
               $stockname = trim($StockList['descrip']);
               $acctfolio = trim($row['customercode']);
               $customer  = $row['customer'];
               $isBundle = (bool)preg_match('/^TS\d{4}$/', trim($itemcode));
               $rowClass = $isBundle ? 'std-group' : (preg_match('/^TS\d{4}$/', trim($row['grp'] ?? '')) ? 'std-group-item' : '');
               $PriceInPricelist = SelectPriceListToUse($itemcode,$qty,$unitscode) ;
               $Approved = ($row['approved']=='')?'Pending':Rejected($row);
           echo  sprintf('<tr class="'.$rowClass.'">'
                    . '<td>%s</td>'
                    . '<td>%s</td>'
                    . '<td>%s</td>'
                    . '<td>1 x %s </td>'
                    . '<td class="number">%s</td>'
                    . '<td class="number">%s</td>'
                    . '<td class="number">%s</td>'
                   . '<td><select name="action[%s]" onchange="ReloadForm(salesform.refresh);">%s</select></td>'
                   . '<td>%s</td>'
                   . '</tr>',$customer,$stockname,$unitname,$qty,
                              $price,($price/$qty),($PriceInPricelist/$qty),
                              $Rid,getoption(),$Approved);   
              }
    
  echo '</table><input type="submit" name="refresh" value="Refresh" onclick="return confirm(\''._('Do you want to approve/reject this price ?').'\');"/>';  
}

 function getoption(){
      $r ='';
    $array=array('Select Action','Approve','Reject');
     foreach ($array as $value) {
        $r .= sprintf('<option>%s</option>',$value);
     }
     return $r ;
 }
 
 function Rejected($row){
     if(Is_date($row['DateTime'])){
       $Date = ConvertSQLDateTime($row['DateTime']);
       $user = $row['approvedby'];
       $return= "Was rejected on ".$Date." by ".$user;
     }else{
       $return= "Not acted on ";
     }
    return $return;
     
 }