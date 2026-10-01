<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Create Supplier Invoice');
include('includes/header.inc');   
include('purchases/stockbalance.inc');   

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Create Supplier Invoice') .'" alt="" />' . ' ' . _('Create Supplier Invoice') . '</p>';

if(isset($_GET['No'])){
    $_POST['documentno'] = $_GET['No'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
    
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
}
    
if(isset($_POST['submit']) and $_POST['submit']=='Enter Delivery Details and Confirm Invoice'){

                        $_SESSION['DocumentNo']=$_POST['LpoNo'] ;

                        if($_SESSION['DocumentPosted']==true){
                            prnMsg('You cannot repost this invoice','warn');
                       
                            } else  {
                                
                            $sql="SELECT `itemcode`,`customer`,`phone`,`email`,`city`,`country`,`curr_cod`,`supplierposting`,`VATinclusive`,`IsTaxed`
                            FROM `creditors` join arpostinggroups on code=`supplierposting`  where itemcode='".$_POST['CustomerID']."'";

                            $res = DB_query($sql, $db);
                            $debtorsrow = DB_fetch_row($res);
                            $customerposting = $debtorsrow[7];
                            $VATinclusive    = $debtorsrow[8];
                            $PeriodNo        = GetPeriod($_POST['posingdate'],$db,true);
                            $TransBuffer     = DB_Txn_Begin($db);
                            $INVOICENO       = GetNextTransNo(20,$db);
                            $sql=array();

                            $sql[] ="insert into `PurchaseHeader` 
                              (`documenttype`
                              ,`documentno`
                              ,`docdate`
                              ,`postingdate`
                              ,`vendorcode`
                              ,`vendorname`
                              ,`yourreference`
                              ,`externaldocumentno`
                              ,`postinggroup`
                              ,`currencycode`
                              ,`printed`
                              ,`released`
                              ,`status`
                              ,`userid`
                              ,`period`
                              ,`vatinclusive`) 
                              (select 
                              20
                              ,'".$INVOICENO."'
                              ,'".FormatDateForSQL($_POST['date'])."'
                               ,'".FormatDateForSQL($_POST['posingdate'])."'
                               ,'".$_POST['CustomerID']."'
                               ,'".$_POST['CustomerName']."'
                              ,'".$_POST['reference']."'
                              ,'".$_SESSION['DocumentNo']."'
                              ,'".$customerposting."'
                              ,'".$_POST['currencycode']."'
                              ,0
                              ,0
                              ,1
                              ,'".$_SESSION['UserID']."'
                              ,'".$PeriodNo."'
                              ,'".$VATinclusive ."'
                              from `AssetsHeader` 
                              where documentno='".$_SESSION['DocumentNo']."') ";

                            foreach ($_POST['subunits'] as $key => $value) {

                                     if($value>0){

                                        $location = $_POST['location'][$key];
                                        $stcode = $_POST['code'][$key];
                                        $qty = $_POST['subunits'][$key];        
                                        $salesprice = $_POST['salesprice'][$key];
                                        $netamount = $_POST['netamount'][$key];
                                        $vatamount = $_POST['vatamount'][$key];
                                        $grossamount = $_POST['grossamount'][$key];

                                      $sql[] = "INSERT INTO fixedassets
                                            (equipment_code, serialno, barcode, assetlocation, cost, datepurchased,
                                             assetcategoryid, description, longdescription, depntype, depnrate,
                                             quantity, manufacturer, modelno, status, remarks)
                                            SELECT
                                                equipment_code,
                                                serialno,
                                                barcode,
                                                assetlocation,
                                                " . $grossamount . " AS cost,
                                                '" . FormatDateForSQL($_POST['date']) . "' AS datepurchased,
                                                assetcategoryid,
                                                description,
                                                longdescription,
                                                depntype,
                                                depnrate,
                                                " . (int)$qty . " AS quantity,
                                                manufacturer,
                                                modelno,
                                                status,
                                                remarks
                                            FROM fixedassets
                                            WHERE assetid = '" . $stcode . "'";

                                        $sql[] =sprintf("INSERT INTO 
                                               `PurchaseLine`
                                               (`documenttype`,
                                               `docdate`,
                                               `documentno`,
                                               `code`,
                                               `description`,
                                               `unitofmeasure`,
                                               `Quantity`,
                                               `UnitPrice`,
                                               `vatamount`,
                                               `invoiceamount`,
                                               `vatrate`,
                                               `inclusive`,
                                               `locationcode`,
                                               UOM) 
                                               (select 
                                               '%s',
                                               '%s' ,
                                               '%s' ,
                                               `code`,
                                               `description`,
                                               `unitofmeasure`,
                                               `Quantity`,
                                               %f,%f,%f,
                                               `vatrate`,
                                               `inclusive`,
                                               '%s',
                                               `UOM`
                                               from FixedAssetsLine where `entryno`='".$key."')"
                                               ,20
                                               ,FormatDateForSQL($_POST['date'])
                                               ,$INVOICENO
                                               ,$salesprice
                                               ,$vatamount
                                               ,$grossamount
                                               ,$location);

                                    }

                            }


                            $journal = GetNextTransNo(0,$db);
                            
                            $sql[] = PostCreditors($PeriodNo,$journal,$INVOICENO);
                            $sql[] = SupplierStatemet($journal,$INVOICENO);
                            $sql[] = PostGL($PeriodNo,$journal,$INVOICENO);
                            $sql[] = VATPostGL($PeriodNo,$journal,$INVOICENO) ;  
                           
                            if($_POST['foropeningbalance']==1){
                                foreach ($sql as $value) {
                                   $ResultIndex=DB_query($value,$db);
                                }
                            }

                            if(DB_error_no($db)>0){
                                DB_Txn_Rollback($db);
                                prnMsg('There was an Error Posting the invoice, Contact your administrator','warn');
                            } else {
                                DB_Txn_Commit($db);
                                prnMsg('This Purchase has successfully been posted');
                                DB_query("Update AssetsHeader set `released`=2 where documentno='".$_SESSION['DocumentNo']."'",$db);

                                $_SESSION['DocumentPosted']=true;
                                unset($_SESSION['DocumentNo']);
                                unset($_POST);
                            }

                        }


}

echo '<a href="'.htmlspecialchars('FassetsReceivedNote.php',ENT_QUOTES,'UTF-8').'">Go To Assets Received Note</a>';
     
$filter="SELECT 
            `AssetsHeader`.`documenttype`
           ,`AssetsHeader`.`documentno`
           ,`AssetsHeader`.`docdate`
           ,`AssetsHeader`.`oderdate`
           ,`AssetsHeader`.`duedate`
           ,`AssetsHeader`.`postingdate`
           ,`AssetsHeader`.`vendorcode`
           ,`AssetsHeader`.`vendorname`
           ,`AssetsHeader`.`yourreference`
           ,`AssetsHeader`.`externaldocumentno`
           ,`AssetsHeader`.`locationcode`
           ,`AssetsHeader`.`paymentterms`
           ,`AssetsHeader`.`postinggroup`
           ,`AssetsHeader`.`currencycode`
           ,`AssetsHeader`.`vatinclusive`
       FROM `AssetsHeader` 
       join FixedAssetsLine on `AssetsHeader`.`documentno`=`FixedAssetsLine`.`documentno`
       where `AssetsHeader`.`documentno`='".$_POST['documentno']."' and  `AssetsHeader`.`documenttype`='41' ";

    $ResultIndex= DB_query($filter, $db);
    $rowresults = DB_fetch_row($ResultIndex);
    
    $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
       
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['CustomerID'] = $rowresults[6];
    $_POST['CustomerName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
  
  
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="purchasesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<input type="hidden" name="LpoNo" value="'. $rowresults[1] .'" />';

echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
   . '<table class="table table-bordered"><caption>GRN Invoice Header Details</caption>';
echo '<tr><td>GRN Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Posting Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="posingdate" size="11" maxlength="10" required="required" value="' .$_POST['posingdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';

echo '<tr><td>GRN No</td>'
   . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly" /></td>'
   . '<td>Delivery No/Supplier Invoice No</td>'
   . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" required="required" /></td>'
   . '<td>Update Only Fixed Assets Register and ignore Accounts Ledgers</td>'
   . '<td><select tabindex="6" name="foropeningbalance">'
        . '<option value="1" '.($_POST['foropeningbalance']=='1'?'selection="selection"':'').'">No</option>'
        . '<option value="2" '.($_POST['foropeningbalance']=='2'?'selection="selection"':'').'">Yes</option>'
        . '</select><br /><code>When posted as opening balance,'
        . '<br/>the system will not record the transaction in the ledgers<br />'
        . ' Only the Fixed assets Register will be posted.</code></td>'
   . '</tr>';

echo '<tr><td>Supplier ID</td>'
   . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
   . '<td>Supplier Name</td>'
   . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" readonly="readonly"/></td></tr>';
echo '<tr><td>Currency Code</td><td><input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];
echo '</tr></table></td></tr><tr><td>';
  
$sql="SELECT `itemcode`,`customer`,
      `phone`,`email`,`city`,`country`,`curr_cod`,
      `supplierposting`,`VATinclusive`,`IsTaxed`
       FROM `creditors` join arpostinggroups on code=`supplierposting` 
       where itemcode='".$_POST['CustomerID']."'";

$sqldebtors = DB_query($sql, $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[7];
$VATinclusive = $debtorsrow[8];
$IsTaxed = $debtorsrow[9];

$Slaqry ="SELECT 
     `FixedAssetsLine`.`entryno`
    ,`FixedAssetsLine`.`documenttype`
    ,`FixedAssetsLine`.`docdate`
    ,`FixedAssetsLine`.`documentno`
    ,`FixedAssetsLine`.`locationcode`
    ,`FixedAssetsLine`.`stocktype`
    ,`FixedAssetsLine`.`code` 
    ,`FixedAssetsLine`.`description`
    ,`FixedAssetsLine`.`unitofmeasure`
    ,`FixedAssetsLine`.`Quantity`
    ,`FixedAssetsLine`.`Quantity_toinvoice`
    ,`FixedAssetsLine`.`Qunatity_delivered`
    ,`FixedAssetsLine`.`UnitPrice`
    ,`FixedAssetsLine`.`vatamount`
    ,`FixedAssetsLine`.`invoiceamount`
    ,`FixedAssetsLine`.`vatrate` 
    ,`FixedAssetsLine`.`inclusive`
    ,`FixedAssetsLine`.`UOM`
  FROM  FixedAssetsLine
  where `FixedAssetsLine`.`documentno`='".$_POST['documentno']."' "
 . " and `FixedAssetsLine`.`documenttype`='41' ";

$ResultIndex = DB_query($Slaqry,$db);
echo '<table  class="table table-bordered"><thead><tr>'
        . '<th class="number">Stock ID</th>'
        . '<th class="number">Stock Description</th>'
        . '<th class="number">Qty to<br />Invoice</th>'
        . '<th class="number">Purchase Cost<br /> per part</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']);
    $rate =(($IsTaxed==0)?0: $stocklist['vatrate']);
    $location = $_POST['location'][$itemcode];
    $emptycost=0; $totalemptycost=0; $cvatamount =0;
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    $qty = $stocklist['Quantity'];
     
    
    
    if($_POST['UOM'][$itemcode]){
        $UOM=$_POST['UOM'][$itemcode];
    }else{
        $UOM = $stocklist['UOM'];
    }
       
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode];
    }else{
        $salesprice = $stocklist['UnitPrice'];
    }
       
    $baseamount = ($salesprice * $qty);
                
    if($VATinclusive==true){
        $netamount  = $baseamount / ( ($rate+100)/100);
        $vatamount  = $baseamount - $netamount ;
        $grossamount= $baseamount ;
    }else{
        $vatamount  = $baseamount * ($rate/100);
        $grossamount= $baseamount + $vatamount;
        $netamount  = $baseamount;
    }
         
    $runningnettotal += $netamount;
    $runningvattotal += $vatamount;
    $runninggrosstotal += $grossamount;
    
    echo '<tr><td><input type="text" name="code['.$itemcode.']" value="'.$stkcode.'" size="4" readonly="readonly"/></td>';
         echo sprintf('<td>%s</td>',trim($stocklist['description']));
     echo'<td><input type="text" class="integer" name="subunits['.$itemcode.']" value="'.$qty.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
         .'<td><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
         .'<td><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
         .'</tr>';
}
   
echo sprintf('<tfoot><tr>'
        . '<td colspan="3"></td>'
              . '<td >TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningnettotal,2),
                number_format($runningvattotal,2),
                number_format($runninggrosstotal,2));

$_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table>';
echo '<div class="centre">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="submit" value="' . _('Enter Delivery Details and Confirm Invoice') . '"
            onclick="return confirm(\''._('Have you selected the correct GRN. Once saved it cannot be changed ?').'\');" />
</div>';

echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');


    function PostCreditors($period,$journal,$doc){
       $SQL= sprintf("Insert into `creditorsledger`
           (`date`
          ,`details`
          ,`flag`
          ,`invref`
          ,`acctfolio`
          ,`amount`
          ,`pamount`
          ,`curr_cod`
          ,`i_n_t`
          ,`journal`
          ,`typ`
          ,`vatamt`
          ,`systypes_1`
          ,`ledger`
          ,period)
      (SELECT 
          PurchaseHeader.`postingdate`
          ,(rtrim(PurchaseLine.`description`) +' X '+CAST(PurchaseLine.`Quantity` as nvarchar(10)) )  as narration
          ,'I'
          ,PurchaseHeader.`documentno`
          ,PurchaseHeader.`vendorcode`
          ,SUM(PurchaseLine.invoiceamount *-1) as Value
          ,0.00
          ,PurchaseHeader.`currencycode`
          ,'I'
          ,'%s'
          ,'I'
          ,SUM(PurchaseLine.vatamount *-1) as VAT
          ,PurchaseHeader.`documenttype`
          ,PurchaseHeader.`postinggroup`
          ,'%s'
      FROM `PurchaseHeader` join PurchaseLine 
            on PurchaseHeader.documentno=PurchaseLine.documentno 
            and PurchaseHeader.documenttype=PurchaseLine.documenttype 
            where PurchaseHeader.documentno='%s' 
            group by
           PurchaseHeader.`documenttype`
          ,PurchaseHeader.`documentno`
          ,PurchaseHeader.`postingdate`
          ,PurchaseHeader.`vendorcode`
          ,PurchaseHeader.`externaldocumentno`
          ,PurchaseHeader.`postinggroup`
          ,PurchaseHeader.`currencycode`
          ,PurchaseLine.`Quantity`
          ,PurchaseLine.`description`
          ,PurchaseLine.`code`
          ,PurchaseLine.`UOM`)", $journal,$period,$doc);

       return $SQL;
    }

    function PostGL($period,$journal,$doc){
        $sql=SPRINTF("Insert into `Generalledger`
          (`journalno`
          ,`Docdate`
          ,`period`
          ,`DocumentNo`
          ,`DocumentType`
          ,`accountcode`
          ,`balaccountcode`
          ,`VATaccountcode`
          ,`amount`
          ,`VATamount`
          ,`currencycode`
          ,`ExchangeRate`
          ,`suppliercode`
          ,`narration`
          ,`dimension`
          ,`dimension2`)
          (select
           '%s'
          ,PurchaseHeader.`postingdate`
          ,'%s'
          ,PurchaseHeader.documentno
          ,PurchaseHeader.documenttype
          ,`fixedassetcategories`.`costact`
          ,`arpostinggroups`.`creditorsaccount`
          ,`fixedassetcategories`.defaultgl_vat_act
          ,PurchaseLine.invoiceamount
          ,IFNULL(PurchaseLine.vatamount,0) as vatamount
          ,PurchaseHeader.`currencycode`
          ,`currencies`.`rate`
          ,PurchaseHeader.`vendorcode`
          ,(rtrim(PurchaseLine.`description`) +'X'+CAST(PurchaseLine.`Quantity` as nvarchar(10)) ) as narration
          ,'".trim($_POST['DimensionOne'])."'
          ,'".trim($_POST['DimensionTwo'])."'    
          from PurchaseHeader join `arpostinggroups` on `PurchaseHeader`.`postinggroup`=`arpostinggroups`.`code`
          join PurchaseLine on `PurchaseLine`.`documentno` = `PurchaseHeader`.`documentno` 
          join fixedassets on `PurchaseLine`.`code`=fixedassets.assetid
          join `fixedassetcategories` on fixedassets.`assetcategoryid`=`fixedassetcategories`.`categoryid` 
          join `currencies` on `PurchaseHeader`.`currencycode`=`currencies`.`currabrev`
          where PurchaseHeader.documentno='%s'
          )",$journal,$period,$doc);

        return $sql;

    }

    function VATPostGL($period,$journal,$doc){
        $sql=SPRINTF("Insert into `Generalledger`
          (`journalno`
          ,`Docdate`
          ,`period`
          ,`DocumentNo`
          ,`DocumentType`
          ,`accountcode`
          ,`balaccountcode`
          ,`VATaccountcode`
          ,`amount`
          ,`VATamount`
          ,`currencycode`
          ,`ExchangeRate`
          ,`suppliercode`
          ,`narration`)
          (select
           '%s'
          ,PurchaseHeader.`postingdate`
          ,'%s'
          ,PurchaseHeader.documentno
          ,PurchaseHeader.documenttype
          ,`fixedassetcategories`.defaultgl_vat_act
          ,`fixedassetcategories`.`costact`
          ,''
          ,PurchaseLine.vatamount
          ,0
          ,PurchaseHeader.`currencycode`
          ,`currencies`.`rate`
          ,`fixedassetcategories`.defaultgl_vat_act
          ,(rtrim(PurchaseLine.`description`) +' X '+CAST(PurchaseLine.`Quantity` as nvarchar(10)) )  as narration
          from PurchaseHeader join `arpostinggroups` on `PurchaseHeader`.`postinggroup`=`arpostinggroups`.`code`
          join PurchaseLine on `PurchaseLine`.`documentno` = `PurchaseHeader`.`documentno` 
          join fixedassets on `PurchaseLine`.`code`=fixedassets.assetid
          join `fixedassetcategories` on fixedassets.`assetcategoryid`=`fixedassetcategories`.`categoryid` 
          join `currencies` on `PurchaseHeader`.`currencycode`=`currencies`.`currabrev`
          where PurchaseHeader.documentno='%s'
          )",$journal,$period,$doc);

        return $sql;

    }

    Function SupplierStatemet($journal,$doc){
        $sql=sprintf("INSERT INTO `SupplierStatement`
               (`Date`
               ,`Documentno`
               ,`Documenttype`
               ,`Accountno`
               ,`Grossamount`
               ,`JournalNo`
               ,`Dimension_One`
               ,`Dimension_Two`
               ,`Currency`)
          (select
                  PurchaseHeader.`docdate`
                 ,PurchaseHeader.documentno
                 ,PurchaseHeader.documenttype
                 ,PurchaseHeader.`vendorcode`
                 ,sum(PurchaseLine.invoiceamount * -1)
                 ,'%s'
                 ,'".$_POST['DimensionOne']."'
                 ,'".$_POST['DimensionTwo']."'  
                 ,PurchaseHeader.`currencycode`   
                 From PurchaseHeader
                 join PurchaseLine on `PurchaseLine`.`documentno` = `PurchaseHeader`.`documentno` 
                 where PurchaseHeader.documentno='%s' 
                 Group By PurchaseHeader.`docdate`
                 ,PurchaseHeader.documentno
                 ,PurchaseHeader.documenttype
                 ,PurchaseHeader.`vendorcode`
                 ,PurchaseHeader.`currencycode` 
                 ) ",
                $journal,$doc);

        return $sql;
    }
    
?>