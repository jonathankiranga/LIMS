<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Open Purchase Orders List');
include('includes/header.inc');   

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Purchase Orders List') .'" alt="" />' 
        . ' ' . _('Purchase Orders List') . '</p>';

if(isset($_GET['StockID'])){
     $StockID=$_GET['StockID'];
}elseif(isset($_SESSION['StockID'])){
    $StockID=$_SESSION['StockID'];
}

if(isset($_GET['No'])){
       $SQL="select 
       `documentno`
      ,`docdate`
      ,`oderdate`
      ,`duedate`
      ,`postingdate`
      ,`vendorcode`
      ,`vendorname`
      ,`yourreference`
      ,`externaldocumentno`
      ,`locationcode`
      ,`paymentterms`
      ,`postinggroup`
      ,`currencycode`
      ,`printed`
      ,`released`
      ,`status`
      ,`userid`
      ,`freight` 
      from `PurchaseHeader`  where `documenttype`='20' and `documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table-bordered"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Purchase Order No</td><td>%s</td></tr>",$_GET['No']);
    echo sprintf("<tr><td>Purchase Order Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Purchase Order date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Purchase Order Due Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Supplier ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Supplier Name</td><td>%s</td></tr>",$row[6]);
    echo sprintf("<tr><td>Freight Cost</td><td>%s</td></tr>",number_format($row[17],2) );
    echo '<tr><td colspan="2">';
    
    echo '<table class="table-bordered table-condensed table-responsive-small"><tr>'
            . '<td>Stock Code</td>'
            . '<td>Name</td>'
            . '<td>Unit Of Measure</td>'
            . '<td>Parts</td>'
            . '<td>Unit Of Measure<br>Quantity</td>'
            . '<td>KG Price</td>'
            . '<td>Discount</td>'
            . '<td>vat</td>'
            . '<td>Gross</td>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $SQL="select 
           `documenttype`
           ,`docdate`
           ,`documentno`
           ,`code`
           ,`description`
           ,`unitofmeasure`
           ,`Quantity`
           ,`UnitPrice`
           ,`discount`
           ,`vatamount`
           ,`invoiceamount`
           ,PartPerUnit 
           from `PurchaseLine` 
           where `documenttype`=20 and `documentno`='".$_GET['No']."'";
   
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'], 
                 $myrow['unitofmeasure'],
                 $myrow['PartPerUnit'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['discount'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
         $VATAMOUNT += $myrow['vatamount'];
         $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }     
    
    
    echo sprintf('<tfoot><tr><td colspan="7">%s</td><td>%s</td><td>%s</td></tr></tfoot>','Purchase Order Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));
    echo '</table></td></tr>';
      echo '</table>';
    echo '</div></form>';
}


$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    

$SQL="SELECT 
       `PurchaseHeader`.`documentno`
      ,`PurchaseHeader`.`docdate`
      ,`PurchaseHeader`.`oderdate`
      ,`PurchaseHeader`.`duedate`
      ,`PurchaseHeader`.`vendorcode`
      ,`PurchaseHeader`.`vendorname`
      ,`PurchaseHeader`.`currencycode`
      ,`PurchaseHeader`.`status`
      ,`PurchaseHeader`.`userid` 
      ,sum(PurchaseLine.`invoiceamount`) as OrderValue
      ,`PurchaseHeader`.printed
      from `PurchaseHeader` 
      join PurchaseLine on `PurchaseHeader`.`documentno`=PurchaseLine.`documentno` 
      where `PurchaseHeader`.`documenttype`='20' and `PurchaseLine`.`code`='".$StockID."'
      group by 
       `PurchaseHeader`.`documentno`
      ,`PurchaseHeader`.`docdate`
      ,`PurchaseHeader`.`oderdate`
      ,`PurchaseHeader`.`duedate`
      ,`PurchaseHeader`.`vendorcode`
      ,`PurchaseHeader`.`vendorname`
      ,`PurchaseHeader`.`currencycode`
      ,`PurchaseHeader`.`status`
      ,`PurchaseHeader`.`userid`
      ,`PurchaseHeader`.printed  order by `PurchaseHeader`.`docdate` desc limit 20";
    $Result=DB_query($SQL,$db);
   
    
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Recive</th>'
             . '<th>Print Order</th>'
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
             . '<th>Vendor <br />ID</th>'
             . '<th>Vendor<br /> Name</th>'
             . '<th>Purchase <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '<th>Print<br />Status</th>'
             . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
      
        echo sprintf('<td><a href="%s?No=%s">Show Order No :%s</a></td>',$pge,$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',$row['printed']==1?'Has Been Printed':'Not Yet Printed');
        echo '</tr>';
  }
        
    echo '</table><br />';
    
     
include('includes/footer.inc');


?>
