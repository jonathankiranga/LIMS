<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Open Sales Orders List');
include('includes/header.inc');   

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Orders List') .'" alt="" />' 
        . ' ' . _('Sales Orders List') . '</p>';

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
      ,`customercode`
      ,`customername`
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
      from `SalesHeader`  where `documenttype`='10' and `documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    
  
    Echo '<Table class="table-bordered"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Sales Order No</td><td>%s</td></tr>",$_GET['No']);
    echo sprintf("<tr><td>Sales Order Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Sales Order date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Sales Order Due Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Customer ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Customer Name</td><td>%s</td></tr>",$row[6]);
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
           ,`vatamount`
           ,`invoiceamount`
           ,PartPerUnit 
           from `SalesLine` 
           where `documenttype`=10 and `documentno`='".$_GET['No']."'";
    
   
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'], 
                 $myrow['unitofmeasure'],
                 $myrow['PartPerUnit'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format(0,2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
         $VATAMOUNT += $myrow['vatamount'];
         $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }     
    
    
    echo sprintf('<tfoot><tr><td colspan="7">%s</td><td>%s</td><td>%s</td></tr></tfoot>','Sales Order Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));
    echo '</table></td></tr>';
      echo '</table>';
    echo '</div></form>';
}


$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    

$SQL="select 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid` 
      ,sum(SalesLine.`invoiceamount`) as OrderValue
      ,`SalesHeader`.printed
      from `SalesHeader` 
      join SalesLine on `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='10' and `SalesLine`.`code`='".$StockID."' 
      and `SalesHeader`.`docdate` between  dateadd(m,-24,NOW()) and (NOW() +24)
     group by 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
     ,`SalesHeader`.`oderdate`
     ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
     ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid`
      ,`SalesHeader`.printed  
      order by `SalesHeader`.`docdate` desc";
    $Result=DB_query($SQL,$db);
   
       
    Echo '<table class="register display"><thead><tr>'
             . '<th>Show</th>'
             . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '<th>Print<br />Status</th>'
             . '</tr></thead><tbody>';
    
 
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Show Order No :%s</a></td>',$pge,$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',$row['printed']==1?'Has Been Printed':'Not Yet Printed');
        echo '</tr>';
  }
        
    echo '</tbody><tfoot><tr>'
             . '<th>Show</th>'
             . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '<th>Print<br />Status</th>'
             . '</tr></tfoot></table>';
    
     
include('includes/footer.inc');


?>