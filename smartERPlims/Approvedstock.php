<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');   
$Title = _('Issue of Stock');
include('includes/header.inc');   
include('transactions/stockbalance.inc');   
include('transactions/poscart.inc');
 
$POSclass = new ApprovedIssues();

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$myapprovalStatus=array('2'=>'Approved','1'=>'Pending Approval','0'=>'Rejected');

echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Issue of Stock') .'" alt="" />' . ' ' . _('Issue of Stock') . '</p>';

if(isset($_POST['saverecord']) ){
    include('transactions/SaveStockIssues.inc');  
}

if(isset($_GET['documentno'])){
    $_SESSION['DocumentPicking']=false;
     
    $SQL="select   `documentno`  ,`docdate`  ,`oderdate` ,`duedate` ,`postingdate` ,`customercode`
      ,`customername`,`yourreference`,`externaldocumentno` ,`locationcode`,`paymentterms`
      ,`postinggroup`  ,`currencycode` ,`printed` ,`released` ,`status`,`userid`  ,`Dimension_1` ,`Dimension_2`
      from `SalesHeader`  where `documenttype`='40' and `documentno`='".$_GET['documentno']."'";
    $Result=DB_query($SQL,$db);
    $rowselected = DB_fetch_row($Result);
    $_POST['documentno']=$rowselected[0];
    $_POST['date']= ConvertSQLDate($rowselected[1]);
    $_POST['CustomerID']=$rowselected[5];
    $_POST['CustomerName']=$rowselected[6];
    $_POST['DimensionOne']=$rowselected[16];
    $_POST['DimensionTwo']=$rowselected[17];
    
    $Result=DB_query("select IFNULL(`released`,0) from `SalesHeader` where `documenttype`='40' and documentno='".$_POST['documentno']."'",$db);
    $rowselected = DB_fetch_row($Result);
    if($rowselected[0]==1){
        $_SESSION['DocumentPicking']=true;
     }
     
}   


    
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div><input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<div class="container-fluid">';

// Header info
echo '<div class="row"><div class="col-md-12">';
echo '<table class="table table-bordered table-condensed">'
    . '<caption>' . _('Stock Issue Details') . '</caption>';
echo '<tr>'
    . '<td>' . _('Date') . '</td>'
    . '<td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>'
    . '<td>' . _('Document No') . '</td>'
    . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'" size="5" readonly="readonly"/></td>'
    . '</tr>';
echo '<tr>'
    . '<td>' . _('Employee ID') . '</td>'
    . '<td><input tabindex="4" type="text" name="CustomerID" id="EmployeeID" value="'.$_POST['CustomerID'].'" size="5" readonly="readonly" required="required" />'
    . '<input type="button" id="searchemployee" value="' . _('Search Employee') . '"/></td>'
    . '<td>' . _('Employee Name') . '</td>'
    . '<td><input tabindex="5" type="text" name="CustomerName" id="EmployeeName" value="'.$_POST['CustomerName'].'" size="20" required="required" /></td>'
    . '</tr>';
if (isset($_SESSION['SelectObject']['dimensionone']) || isset($_SESSION['SelectObject']['dimensiontwo'])) {
    echo '<tr><td colspan="4">'
        . ($_SESSION['SelectObject']['dimensionone'] ?? '')
        . ($_SESSION['SelectObject']['dimensiontwo'] ?? '')
        . '</td></tr>';
}
echo '</table>';
echo '</div></div>';

// Line items
echo '<div class="row"><div class="col-md-12">';
echo '<table class="table table-bordered table-condensed table-striped">'
    . '<thead><tr>'
    . '<th>' . _('Barcode') . '</th>'
    . '<th>' . _('Description') . '</th>'
    . '<th>' . _('Avg Cost') . '</th>'
    . '<th>' . _('Select Batch') . '</th>'
    . '<th class="number">' . _('Quantity') . '</th>'
    . '<th class="number">' . _('Cost/Unit') . '</th>'
    . '<th>' . _('Store') . '</th>'
    . '<th class="number">' . _('Balance After') . '</th>'
    . '</tr></thead>';

$SQL = "SELECT `documenttype`, `docdate`, `documentno`, `code`, `description`,
               `PartPerUnit`, `Quantity`, `UnitPrice`, `vatamount`,
               `invoiceamount`, `unitofmeasure`
        FROM `SalesLine`
        WHERE `documenttype`=40 AND `documentno`='" . $_POST['documentno'] . "'";

$ResultIndex = DB_query($SQL, $db);
while ($rowvalue = DB_fetch_array($ResultIndex)) {
    $POSclass->Getitems($rowvalue);
}

echo '</table>';
echo '</div></div>';

// Actions
echo '<div class="row"><div class="col-md-12 text-right">';
echo '<input type="submit" name="submit" value="' . _('Re-Calculate') . '" class="btn btn-info" />';
echo '<input type="submit" name="saverecord" value="' . _('Print and Issue items') . '" class="btn btn-primary" style="margin-left:10px;" />';
echo '</div></div>';

echo '</div></div></form>';
 


$SQL="Select  
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid` 
      from `SalesHeader` 
      where `SalesHeader`.`documenttype`='40' and 
      (`SalesHeader`.`released` is null or `SalesHeader`.`released`=0)
     order by `SalesHeader`.`docdate` desc";
    $Result=DB_query($SQL,$db);
           
    Echo '<BR/><DIV class="table-responsive">'
        . '<Table class="table-bordered"><tr>'
        . '<th>Documen No</th>'
        . '<th>Request <br /> Document<br /> date</th>'
        . '<th>Staff <br />ID</th>'
        . '<th>Staff <br /> Name</th>'
        . '<th>Authorisation<br /> Status</th>'
        . '<th>Created<br /> By</th>'
        . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        if($row['status']==2){
          echo sprintf('<td><a href="%s?documentno=%s">Select :%s</a></td>',htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        }else{
          echo sprintf('<td>%s</td>',$row['documentno']);
        }
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',$myapprovalStatus[trim($row['status'])]);
        echo sprintf('<td>%s</td>',$row['userid']);
        echo '</tr>';
  }
        
    echo '</table></DIV>';

include('includes/footer.inc');


?>