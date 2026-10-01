<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Unapproved Supplier Documents');
include('includes/header.inc');   
$mypage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

$Arraysystypes=array();
/*
 typeid	typename
18	Purchase Order                                    
20	Purchase Invoice                                  
21	Debit Note                                        
24	Purchase Returns                                  
30	Goods Received Note 
*/

 $SQL = "SELECT `typeid`,`typename` 
          FROM `systypes_1` 
         where `typeid` in (18,21,24)";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['typeid']);
        $Arraysystypes[$code]=$row;
    }

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' .$Title.'" alt="" />' . ' ' .$Title. '</p>';
 
if(isset($_POST['confirm'])){
    DB_query("UPDATE `PurchaseHeader` SET `released` = 1,`status`=2 where documentno='".$_POST['documentno']."'", $db);
    DB_query("UPDATE `PurchaseLine` SET `completed` = 1 where documentno='".$_POST['documentno']."'", $db);
    unset($_POST);
}

if(isset($_POST['Reject'])){
    DB_query("UPDATE `PurchaseHeader` SET `released`=NULL,`status`=0 where documentno='".$_POST['documentno']."'", $db);
    DB_query("UPDATE `PurchaseLine` SET `completed`=NULL where documentno='".$_POST['documentno']."'", $db);
    prnMsg('Purchase Document :'.$_POST['documentno'].' has been Rejected.');
    unset($_POST);
}


if(isset($_GET['No'])){
    $documentno=$_GET['No'];
}elseif(isset($_POST['documentno'])){
    $documentno=$_POST['documentno'];
}


if(isset($_GET['documenttype'])){
    $documenttype=trim($_GET['documenttype']);
}elseif(isset($_POST['documenttype'])){
    $documenttype=trim($_POST['documenttype']);
}

echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"  id="salesform">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

if(isset($documentno)){
    echo sprintf('<p class="page_title_text"><a href="%s">Back HOME</a></p>',$mypage);

      echo '<input type="hidden" name="documentno" value="'. $documentno.'" />'
        . '<input type="hidden" name="documenttype" value="'. $documenttype.'" />';

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
      ,`Dimension_1`
      ,`Dimension_2` 
      from `PurchaseHeader`  where `documenttype`='".$documenttype."' and `documentno`='".$documentno."'";
 
    $Result=DB_query($SQL,$db);
    $row=DB_fetch_row($Result);
    $Budget = FindBudgetline($row[18]);
    $Projct = FindProjectline($row[19]);
  
    Echo '<Table class="table-bordered"><tr><th>Document Details</th><th></th><th></th><th></th></tr>';
    echo sprintf("<tr><td>Document No</td><td>%s</td>",$documentno);
    echo sprintf("<td>Document date</td><td>%s",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("(Due Date %s)</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
  
    echo sprintf("<tr><td>Supplier ID</td><td>%s</td>", $row[5]);
    echo sprintf("<td>Supplier Name</td><td>%s ",$row[6]);
    echo sprintf("(Freight Cost %s)</td></tr>",number_format($row[17],2) );
    echo sprintf("<tr><td>Budget Line</td><td>%s</td>",$Budget);
    echo sprintf("<td>Project Line</td><td>%s</td></tr>",$Projct);
   
    echo '<tr><td colspan="4">';
    echo '<table class="table table-bordered"><tr>'
            . '<th>Stock Code</th>'
            . '<th>Name</th>'
            . '<th>Unit Of Measure</th>'
            . '<th>Quantity</th>'
            . '<th>Purcahse Price</th>'
            . '<th>Net Amount</th>'
            . '<th>VAT Amount</th>'
            . '<th>Gross Amount</th>'
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
           ,partperunit 
           from `PurchaseLine` 
           where `documenttype`='".$documenttype."' and `documentno`='".$documentno."'";
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         $Partperunit=(int) $myrow['partperunit'];
        
         echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'],
                 $myrow['unitofmeasure'].'(1 x '.$Partperunit.')',
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
    echo '<tr><td colspan="2">';
    echo '<div><input type="submit" name="confirm" value="'._('Approve Document').'"/>';
    echo '<input type="submit" name="Reject" value="'._('Reject Document').'"/></div>';
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
     echo '</select></td><td><input type="submit" name="refresh" value="'._('Refresh').'"/></td></tr>'
             . '</table>';
   
$SQL="Select  
       `PurchaseHeader`.`documentno`
      ,`PurchaseHeader`.`docdate`
      ,`PurchaseHeader`.`oderdate`
      ,`PurchaseHeader`.`duedate`
      ,`PurchaseHeader`.`vendorcode`
      ,`PurchaseHeader`.`vendorname`
      ,`PurchaseHeader`.`currencycode`
      ,`PurchaseHeader`.`status`
      ,`PurchaseHeader`.`userid` 
      from `PurchaseHeader` 
      where `PurchaseHeader`.`documenttype`='".$_POST['documenttype']."' 
        and `PurchaseHeader`.`status`=1 order by `PurchaseHeader`.`docdate` desc";
    $Result=DB_query($SQL,$db);
           
    Echo '<DIV class="container">'
        . '<Table class="table table-bordered"><tr>'
        . '<th>Supplier Invoice<br /> for GRN</th>'
        . '<th>Document<br /> date</th>'
        . '<th>Due <br />Date</th>'
        . '<th>Vendor <br />ID</th>'
        . '<th>Vendor<br /> Name</th>'
        . '<th>Authorisation<br /> Status</th>'
        . '<th>Created<br /> By</th>'
        . '</tr>';
    
 
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        if($row['status']==2){
              echo sprintf('<td>%s</td>',$row['documentno']);
        }else{
              echo sprintf('<td><a href="%s?No=%s&documenttype=%s">Select :%s</a></td>',$mypage,$row['documentno'],$_POST['documenttype'],$row['documentno']);
       }
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
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