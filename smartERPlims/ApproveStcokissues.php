<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Unapproved Customer Documents');
include('includes/header.inc'); 

$mypage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

$Arraysystypes=array();

 $SQL = "SELECT `typeid`,`typename` 
          FROM `systypes_1` 
         where `typeid` in (1,11,13,15,54)";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['typeid']);
        $Arraysystypes[$code]=$row;
    }

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' .$Title .'" alt="" />' . ' ' . $Title . '</p>';

if(isset($_POST['confirm'])){
    DB_query("UPDATE `SalesHeader` SET `status` = 2 where documentno='".$_POST['documentno']."'", $db);
    unset($_POST);
}

if(isset($_POST['Reject'])){
    DB_query("UPDATE `SalesHeader` SET `status` = 0 where documentno='".$_POST['documentno']."'", $db);
    unset($_POST);
}

if(isset($_GET['No'])){
    $documentno=trim($_GET['No']);
}elseif(isset($_POST['documentno'])){
    $documentno=trim($_POST['documentno']);
}

if(isset($_GET['documenttype'])){
    $documenttype=trim($_GET['documenttype']);
}elseif(isset($_POST['documenttype'])){
    $documenttype=trim($_POST['documenttype']);
}

 

echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"  id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
if(isset($documentno)){
    echo sprintf('<p class="page_title_text"><a href="%s">Back HOME</a></p>',$mypage);

echo '<div class="container">';
echo '<input type="hidden" name="documentno" value="'. $documentno.'" />'
  . '<input type="hidden" name="documenttype" value="'. $documenttype.'" />';


    $SQL="select  `documentno` ,`docdate` ,`oderdate` ,`duedate` ,`postingdate` ,`customercode`
      ,`customername` ,`yourreference` ,`externaldocumentno`  ,`locationcode` ,`paymentterms`
      ,`postinggroup`  ,`currencycode`  ,`printed`  ,`released`  ,`status`  ,`userid`  ,`Dimension_1` ,`Dimension_2`
      from `SalesHeader` where `documenttype`='".$documenttype."' and `documentno`='".$documentno."'";
     
    $Result = DB_query($SQL,$db);
    $row = DB_fetch_row($Result);
    $Budget = FindBudgetline($row[17]);
    $Projct = FindProjectline($row[18]);
   
    Echo '<Table class="table-bordered"><tr><th>Document Details</th><th></th><th></th><th></th></tr>';
    echo sprintf("<tr><td>Document No</td><td>%s</td>",$documentno);
    echo sprintf("<td>Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td> Account ID</td><td>%s</td>", $row[5]);
    echo sprintf("<td> Account Name</td><td>%s</td></tr>",$row[6]);
    echo sprintf("<tr><td>Budget Line</td><td>%s</td>",$Budget);
    echo sprintf("<td>Project Line</td><td>%s</td></tr>",$Projct);
    
    echo '<tr><td colspan="4">';
    echo '<table class="table table-bordered">'
            . '<tr><th>Stock Code</th>'
            . '<th>Name</th>'
            . '<th>Unit Of Measure</th>'
            . '<th>Quantity</th>'
            . '<th>Price</th>'
            . '<th>Net Amount</th>'
            . '<th>vat</th>'
            . '<th>Gross Amount</th>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $SQL="select   `documenttype` ,`docdate`,`documentno` ,`code` ,`description`
           ,`unitofmeasure` ,`Quantity` ,`UnitPrice` ,`vatamount` ,`invoiceamount` ,PartPerUnit 
           from `SalesLine`  where `documenttype`='".$documenttype."' and `documentno`='".$documentno."'";
   
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
        $Partperunit=(int) $myrow['PartPerUnit'];
         echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'],
                 $myrow['unitofmeasure'].'(1 X'.$Partperunit.')',
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['invoiceamount']-$myrow['vatamount'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
         
                 $NETAMOUNT += $myrow['invoiceamount']-$myrow['vatamount'];
                 $VATAMOUNT += $myrow['vatamount'];
                 $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }     
    echo sprintf('<tfoot><tr><td colspan="5">%s</td><td>%s</td><td>%s</td><td>%s</td></tr></tfoot>',
            'Totals',number_format($NETAMOUNT,2),number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));

    echo '</table></td></tr>';
    echo '<tr><td colspan="4">';
    echo '<div><input type="submit" name="confirm" value="'._('Approve Request').'"/>';
    echo '<input type="submit" name="Reject" value="'._('Reject Request').'"/></div>';
    echo '</td></tr>';
    echo '</table>';
   

}else{

    echo '<table class="table table-bordered"><tr>'
            . '<td>Select The Document Types That You Want To Approve</td>'
            . '<td><select name="documenttype" onchange="ReloadForm(salesform.refresh);">';
        foreach ($Arraysystypes as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',
                    $key,(($_POST['documenttype']==$key)?'selected="selected"':''),$value['typename']);
        }
    echo '</select></td><td><input type="submit" name="refresh" value="'._('Refresh').'"/></td></tr></table>';
     
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
      where `SalesHeader`.`documenttype`='".$_POST['documenttype']."' 
        and `SalesHeader`.`status`=1 order by `SalesHeader`.`docdate` desc";
    $Result=DB_query($SQL,$db);
           
    Echo '<DIV class="container">'
        . '<Table class="table table-bordered"><tr>'
        . '<th>Document NO</th>'
        . '<th>Document  date</th>'
        . '<th>Account Code</th>'
        . '<th>Account Name</th>'
        . '<th>Status</th>'
        . '<th>Created<br /> By</th>'
        . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s&documenttype=%s">Select :%s</a></td>',htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$_POST['documenttype'],$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',$row['status']==1?'Pending Approval':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo '</tr>';
  }
        
    echo '</table></DIV>';
}   
echo '</div></form>' ;
include('includes/footer.inc');



function FindBudgetline($budget){
    Global $db;
    
    $Result = DB_query("SELECT "
        . "`Dim1`.`Dimension_type` as D1,"
        . "`Dim2`.`Dimension_type` as D2,"
        . "`DefaultDimension_1` ,"
        . "`DefaultDimension_2`  "
        . "FROM `companies`  "
        . "left join DimensionSetUp Dim1 on `companies`.`DefaultDimension_1`=Dim1.`id` "
        . "left join DimensionSetUp Dim2 on `companies`.`DefaultDimension_2`=Dim2.`id` "
        . "where `companies`.`coycode`=1",$db);
$DimensionRow = DB_fetch_row($Result);

$id1 = $DimensionRow[2];
$id2 = $DimensionRow[3];
$Dimesion_one="Not selected";

    $DRows = DB_query("SELECT  ltrim(rtrim(`Code`)) as Code,`Dimension` FROM `Dimensions` where `id`='".$id1."'  and code='".$budget."' order by Dimension", $db);
    while($row = DB_fetch_array($DRows)){
        $Dimesion_one=$row['Dimension'];
    }
    
    return $Dimesion_one;
}

function FindProjectline($budget){
    Global $db;
    
    $Result = DB_query("SELECT "
        . "`Dim1`.`Dimension_type` as D1,"
        . "`Dim2`.`Dimension_type` as D2,"
        . "`DefaultDimension_1` ,"
        . "`DefaultDimension_2`  "
        . "FROM `companies`  "
        . "left join DimensionSetUp Dim1 on `companies`.`DefaultDimension_1`=Dim1.`id` "
        . "left join DimensionSetUp Dim2 on `companies`.`DefaultDimension_2`=Dim2.`id` "
        . "where `companies`.`coycode`=1",$db);
$DimensionRow = DB_fetch_row($Result);

$id1 = $DimensionRow[2];
$id2 = $DimensionRow[3];

$Dimesion_one="Not selected";

    $DRows = DB_query("SELECT  ltrim(rtrim(`Code`)) as Code,`Dimension` FROM `Dimensions` where `id`='".$id2."'  and code='".$budget."' order by Dimension", $db);
    while($row = DB_fetch_array($DRows)){
        $Dimesion_one=$row['Dimension'];
    }
    
    return $Dimesion_one;
}

?>