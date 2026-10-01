<?php
include('includes/session.inc');
$Title = _('Delete Journal');
include('includes/header.inc');
if(isset($_POST['date'])){
   $TransDate = ConvertSQLDate($_POST['date']);
}

If(isset($_POST['DocumentNO'])){
    if (Is_Date(ConvertSQLDate($_SESSION['CompanyRecord']['PeriodRollover']))){
         $ProhibitPostingsBefore = $_SESSION['CompanyRecord']['PeriodRollover'];
         if(!is_null($TransDate) and !is_null($ProhibitPostingsBefore) ){
               if(IssqldateGreater($ProhibitPostingsBefore,$TransDate)==0) {
                  DIE(prnMsg('You are trying to modify a closed Perid','warn'));
               }
         }
        
    }
}      
        
if(isset($_POST['save'])){
    $SQLARRAY[]="delete from `Generalledger` where `DocumentType`='".$_POST['Documenttype']."' and  `journalno` in (select `journalno` from `Generalledger` G where  G.`Docdate`='".$TransDate."'  and  G.`DocumentNo`='".$_POST['DocumentNO']."')";
           
    $SQLARRAY[]="delete from `Generalledger` where `DocumentType`='".$_POST['Documenttype']."' and  `journalno` in (select `journal` from `BankTransactions` G where G.`Docdate`='".$TransDate."'  and  G.`DocumentNo` ='".$_POST['DocumentNO']."')";
    
    $SQLARRAY[]="delete from `BankTransactions` where `doctype`='".$_POST['Documenttype']."' and  `journal` in (select `journal` from `BankTransactions` G where G.`Docdate`='".$TransDate."'  and G.`DocumentNo` ='".$_POST['DocumentNO']."')";
    
    $SQLARRAY[]="delete from `BankTransactions` where `doctype`='".$_POST['Documenttype']."' and  `journal` in (select `journal` from `creditorsledger` C  where  C.`date`='".$TransDate."'  and  C.`systypes_1`='".$_POST['Documenttype']."' and  C.`invref`='".$_POST['DocumentNO']."')";
    
    $SQLARRAY[]="delete from `Generalledger` where `DocumentType`='".$_POST['Documenttype']."' and  `journalno` in (select `journal` from `creditorsledger` C where C.`date`='".$TransDate."'  and  C.`systypes_1`='".$_POST['Documenttype']."' and  C.`invref`='".$_POST['DocumentNO']."')";
     
    $SQLARRAY[]="delete from `creditorsledger` where `systypes_1`='".$_POST['Documenttype']."' and `journal` in (select `journal` from `creditorsledger` C where C.`date`='".$TransDate."' and  C.`systypes_1`='".$_POST['Documenttype']."' and C.`invref`='".$_POST['DocumentNO']."')";
    
    $SQLARRAY[]="delete from `SupplierStatement` where `Documenttype`='".$_POST['Documenttype']."' and  `Date`='".$TransDate."'  and   `Documentno`='".$_POST['DocumentNO']."'";
    
    $SQLARRAY[]="delete from `BankTransactions` where `doctype`='".$_POST['Documenttype']."' and  `journal` in (select `journal` from `debtorsledger` C where  C.`date`='".$TransDate."' and  C.`invref`='".$_POST['DocumentNO']."')";
  
    $SQLARRAY[]="delete from `Generalledger` where `DocumentType`='".$_POST['Documenttype']."' and  `journalno` in (select `journal` from `debtorsledger` C where C.`date`='".$TransDate."' and  C.`invref`='".$_POST['DocumentNO']."')";
     
    $SQLARRAY[]="delete from `debtorsledger` where `journal` in (select `journal` from `debtorsledger` C where C.`date`='".$TransDate."' and  C.`invref`='".$_POST['DocumentNO']."')";
    
    $SQLARRAY[]="delete from `CustomerStatement` where `Date`='".$TransDate."' and  `Documentno`='".$_POST['DocumentNO']."'  and `Documenttype`='".$_POST['Documenttype']."'";
      
    $SQLARRAY[]="delete from `stockledger` where `date`='".$TransDate."' and  `invref`='".$_POST['DocumentNO']."'";
 
    
    $SQLARRAY[]="delete from `tanktrans` where `date`='".$TransDate."' and  `batchno`='".$_POST['DocumentNO']."'";
 /*        $SQL[]= sprintf("INSERT INTO `tanktrans` (`tankname`,`units`,`uom`,`date`,`batchno`,`doctype`,`itemcode`)
             VALUES  ('%s',%f ,'%s' ,'".$date ."','%s',17,'%s')",$_POST['location'],$Adjustby,'loosqty',$_POST['DocNo'],$_POST['StockID']);
    */
       
    $SQLARRAY[]="Delete FROM `SupplierStatement` where `Documenttype`='".$_POST['Documenttype']."' and `JournalNo` in (select `journal` FROM `pettdoc` where `petteycashno`='".$_POST['DocumentNO']."')";
    $SQLARRAY[]="Delete FROM `creditorsledger` where `systypes_1`='".$_POST['Documenttype']."' and `journal` in (select `journal` FROM `pettdoc` where `petteycashno`='".$_POST['DocumentNO']."')";
    
    $SQLARRAY[]="Delete FROM `BankTransactions` where `doctype`='".$_POST['Documenttype']."' and  `journal` in (select `journal` FROM `pettdoc` where `petteycashno`='".$_POST['DocumentNO']."')";
    $SQLARRAY[]="Delete FROM `Generalledger` where `journalno` in (select `journal` FROM `pettdoc` where `petteycashno`='".$_POST['DocumentNO']."')";
    
    $SQLARRAY[]="Delete FROM `CustomerStatement` where `JournalNo` in (select `journal` FROM `pettdoc` where `petteycashno`='".$_POST['DocumentNO']."')";
    $SQLARRAY[]="Delete FROM `debtorsledger` where `journal` in (select `journal` FROM `pettdoc` where `petteycashno`='".$_POST['DocumentNO']."')";
    
    $SQLARRAY[]="Delete FROM `pettdoc` where `petteycashno`='".$_POST['DocumentNO']."'";
   
    $SQLARRAY[]="Delete FROM `PurchaseLine` where `documentno` ='".$_POST['DocumentNO']."'";
    $SQLARRAY[]="Delete FROM `PurchaseHeader` where `documentno` ='".$_POST['DocumentNO']."'";
   
    $SQLARRAY[]="Delete FROM `SalesLine` where `documentno` ='".$_POST['DocumentNO']."'";
    $SQLARRAY[]="Delete FROM `SalesHeader` where `documentno` ='".$_POST['DocumentNO']."'";
    
     $rowcount=0;
    DB_Txn_Begin($db);
    foreach ($SQLARRAY as $SQL) {
      $ResultIndex=DB_query($SQL, $db);  
      $rowcount += DB_num_rows($ResultIndex);
    }
    
    if(DB_error_no($db)==0){
       
        DB_Txn_Commit($db);
        prnMsg('You have deleted Entry '.$_POST['DocumentNO'].' in '.$rowcount.' places');
    }else{
        DB_Txn_Rollback($db);
    }
    
}

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Delete Entry Window') . '" alt="" />' . ' ' . _('Delete Entry Window') . '</p>';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-bordered" cellspacing="4"><tr><td valign="top">'
. '<table class="table table-bordered"><caption>Enter The Document No</caption>';
echo '<tr><td>Document NO</td>'
. '<td><input tabindex="1" type="text" required="required"  maxlength="10" size="10" name="DocumentNO" value="'.$_POST['DocumentNO'].'"/></td></tr>';
echo '<tr><td>Document TYPE</td>'
. '<td><select required="required" name="Documenttype">';

$Results = DB_query("Select `typeid`,`typename` from `systypes_1`",$db);
    while($roe = DB_fetch_array($Results)){
        $selected=(trim($roe['typeid'])==trim($_POST['Documenttype']))?'selected':'';
      echo sprintf('<option value="%s" %s>%s</option>',trim($roe['typeid']),$selected,$roe['typename']); 
    }
    
echo '</select></td></tr><tr><td>Document Dated</td>'
. '<td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
echo '</table></td></tr></table>';
echo '<div><input type="submit" name="save" value="'._('Delete Entry').'"/>';
echo '</div>';
echo '</div></form>' ;


include('includes/footer.inc');
?>