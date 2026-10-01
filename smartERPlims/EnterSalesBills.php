<?php
$Title = _('Enter Sales Bills');

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('purchases/stockbalance.inc');
include('includes/header.inc'); 

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" title="' . _('Enter Sales Bills') .'" alt="" />' . ' ' . _('Enter Sales Bills') . '</p>';
echo '<div  class="centre"><a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">Click here to Create New Document No</a></div>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="Journal">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
 
    if(isset($_POST['Savebill'])){
        if($_SESSION['Grossamounttotal']==0){
            prnMsg('Please enter an amount','warn');
        }else{
            
            if($_SESSION['Grossamounttotal']<0){
            prnMsg('This is a reversal','succ');
            }
            
        $JouClass = new journalentries();

         $account = $_POST['account'.$JouClass->I];
         $amount = $_POST['amount'.$JouClass->I];
         $Grossamount = $_POST['Grossamount'.$JouClass->I];
         $VATamt= $_POST['vatamount'.$JouClass->I];
          
         $JouClass->GLentries($account,$_POST['VendorID'],$amount,$Grossamount,$VATamt);
         $SQL = $JouClass->SaveJournal();
         
         DB_Txn_Begin($db);
         foreach ($SQL as $value) {
             DB_query($value, $db);
         }
         
         if(DB_error_no($db)>0){
             DB_Txn_Rollback($db);
              prnMsg('Transaction has failed to save','warn');
         }else{
             DB_Txn_Commit($db);
             $JouClass->NewJournal();
             
             echo sprintf('<p class="page_title_text"><a id="'.$_POST['documentno'].'" href="%s?enterbillno=%s">'
            . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="'. _('Print Enter Bills').'" alt=""/>%s</a></p>',
            'PDFenterSalesbills.php',$_POST['documentno'], _('Print Enter Bills') .' '. $_POST['documentno']);
    
             echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$_POST['documentno']);

             unset($_POST);
         }
         
    }
}
   
    if(isset($_POST['cancel'])){
        $JouClass = new journalentries();
        $JouClass->NewJournal();
        Unset($_POST['account'.$JouClass->I]);
        Unset($_POST['amount'.$JouClass->I]);
    }

    if(isset($_POST['addline'])){
         $JouClass = new journalentries();
         if(mb_strlen($_POST['account'.$JouClass->I])==0){
              prnMsg('You must select a ledger account first','warn');
          } else {
              $JouClass->NextJournal();
          }
        
     }
   
    if(isset($_GET['new'])){
            $_SESSION['Grossamounttotal']=0;
            $_SESSION['amounttotal'] =0;
            $_SESSION['Vatamounttotal'] =0 ;
            
        $JouClass = new journalentries();
        $JouClass->NewJournal();
        $ResultIndex = DB_query('Select NOW() as date',$db);
        $rowdate = DB_fetch_row($ResultIndex);
         if(!isset($_POST['date'])){
           $_POST['date'] = ConvertSQLDate($rowdate[0]);
        }
         if(($_SESSION['ManualNumber']==0)){
        $_POST['documentno'] = GetNextTransNo(25,$db);
         }
        $_POST['currency'] = $_SESSION['CompanyRecord']['currencydefault'];
        $_SESSION['JournalID']=date('U');

    } else {
       $JouClass = new journalentries();
    }
 
    $account = $_POST['account'.$JouClass->I];
    $amount = $_POST['amount'.$JouClass->I];
    $Grossamount = $_POST['Grossamount'.$JouClass->I];
    $VATamt= $_POST['vatamount'.$JouClass->I];
    $JouClass->GLentries($account,$_POST['VendorID'],$amount,$Grossamount,$VATamt);
         
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);

    if(!isset($_POST['date'])){
        $_POST['date']= ConvertSQLDate($rowdate[0]);
    }

    $ResultIndex = DB_query('Select NOW()+1 as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);

    if(!isset($_POST['datedue'])){
        $_POST['datedue']= ConvertSQLDate($rowdate[0]);
    }
    

echo '<div class="container-fluid">'
    . '<table class="table-striped table-bordered"><caption>Invoice Header Details</caption>';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No<input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="10" maxlength="10" required="required" readonly="readonly"/></td>'
   . '<td>Customers Reference</td><td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="10"  maxlength="10"/></td></tr>';

if(isset($_GET['SupplierID'])){
      $ResultIndex = DB_query("SELECT itemcode,customer,`curr_cod` "
    . "from customer where itemcode='".$_GET['SupplierID']."'", $db);
    $row=DB_fetch_row($ResultIndex);
    $_POST['VendorName']=$row[1];
    $_POST['currencycode']=$row[2];
    $_POST['VendorID']=$row[0];
}

echo '<tr><td><input tabindex="4" type="hidden" name="VendorID" id="CustomerID" value="'.$_POST['VendorID'].'"  size="5"/>'
   . '<input type="button" id="searchcustomer" value="Search for Customer"/></td>';

echo '<td>Customers Name</td>'
     . '<td><input tabindex="5" type="text" name="VendorName" id="CustomerName" value="'.$_POST['VendorName'].'"  size="50"  required="required"/></td>';

echo '<td>Currency Code</td><td>'
    . '<input tabindex="6" type="text"  size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/> Sales REP<select tabindex="7" name="salespersoncode" id="salespersoncode">'
   . '<option></option>';

$ResultIndex=DB_query("SELECT `code`,`salesman`,`commission`,`inactive`  FROM `salesrepsinfo` where `inactive` is null or `inactive`=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
    $salesmancode=trim($row['code']);
   echo sprintf('<option value="%s" %s >%s</option>',  $salesmancode,($_POST['salespersoncode']==$salesmancode?'selected="selected"':''), $row['salesman']);
}
    
echo '</select></td></tr>';

echo $_SESSION['SelectObject']['dimensionone'];
echo $_SESSION['SelectObject']['dimensiontwo'];

echo '</tr><tr><td>Auto Calculate VAT</td><td><select name="calculatevat">'
    . '<option value="1" '; 
 echo $_POST['calculatevat']==1?'selected="selected"':""; 
 echo '>Yes</option>'
    . '<option value="0" '; 
 echo $_POST['calculatevat']==0?'selected="selected"':"";
    echo '>No</option>'
    . '</select></td>';
echo '<td>Narration:</td>'
    . '<td colspan="4"><input type="text" name="comments" size="50" value="'.$_POST['comments'].'" maxlenth="200" /></td>'
    . '</tr>';

echo '<tr><td colspan="10"><table class="table-striped table-bordered">'
   . '<tr><th>Index</th><th>Code</th><th>Account Description</th><th>Net Amount</th><th>VAT Amount</th><th>Total Amount</th></tr>';
$JouClass->ShowTable();
echo  '<tr><td colspan="3">Totals</td><td class="number">'.number_format($_SESSION['amounttotal'],2).'</td><td  class="number">'.number_format($_SESSION['Vatamounttotal'],2).'</td><td class="number">'.number_format($_SESSION['Grossamounttotal'],2).'</td></tr>'
        . '<tr><td></td><td></td><td colspan="5">';
echo '<input type="submit" name="update" value="Update"/>  '
. '  <input type="submit" name="addline" value="Add Line"  onclick="return confirm(\''._('Do you want to add a new line ?').'\');" />'
. '  <input type="submit" name="cancel" value="Cancel Entries"  onclick="return confirm(\''._('Do you want to cancel these entries ?').'\');" />'
. '  <input type="submit" name="Savebill" value="Save Bill"  onclick="return confirm(\''._('Do you want to save this Bill ?').'\');" />';
  
echo '</td></tr>';

echo '</table></td></tr>'
. '</table></div>';
   
echo '</td></tr></table>'
        . '</div></form>';



include('includes/footer.inc');

class journalentries {
    var $I; 
    var $JOURNAL;
    var $JournalArray=array();
    
    function __construct() {
          $this->I = $_SESSION['EnterbillEntryIndexNo'];
    }
    
    function getrates($fromcurrency,$tocurrency){
        
        $FromRate =$this->ExtractRate($fromcurrency);
        $Torate =$this->ExtractRate($tocurrency);
        
        return ($Torate/$FromRate);
    }
    
    function ExtractRate($currbrieve){
        global $db;
        
        $SQLOldRate = "SELECT rate FROM currencies
        WHERE currabrev = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];
                
        return $OldRate;
    }
          
    function NextJournal() {
        $_SESSION['EnterbillEntryIndexNo']++;
        $this->I = $_SESSION['EnterbillEntryIndexNo'];
 
        $_SESSION['JournalEntryDetails'][$_SESSION['EnterbillEntryIndexNo']]= 
                    array('account'=>"",
                          'accountdescription'=>"",
                          'netamount'=>0.00,
                          'vatamount'=>0.00,
                          'amount'=>0.00);
        
        $this->JOURNAL=$_SESSION['JournalEntryDetails'];
              
        return $this->JOURNAL ;
    }
    
    Function NewJournal(){
        unset($_SESSION['JournalEntryDetails']);
        $_SESSION['JournalEntryDetails'] = array();
        $_SESSION['EnterbillEntryIndexNo'] = 0;
        $this->I = $_SESSION['EnterbillEntryIndexNo'];
         
        $_SESSION['JournalEntryDetails'][$this->I] =
                    array('account'=>"",
                          'accountdescription'=>"",
                          'netamount'=>0.00,
                          'vatamount'=>0.00,
                          'amount'=>0.00);
        
        $this->JOURNAL=$_SESSION['JournalEntryDetails'];
        
        return $this->JOURNAL;
    }
    
    Function GetGL($account,$db){
        $array= array();
        
        $sql="SELECT `accno`,`accdesc`,`defaultgl_vat`,`VAT` FROM `acct` 
              left join GLpostinggroup on acct.postinggroup=GLpostinggroup.code 
              left join vatcategory on GLpostinggroup.vatcategory=vatcategory.vatc 
              where `accno`='".$account."'";
        
        $ResultIndex = DB_query($sql,$db);
        $row = DB_fetch_row($ResultIndex);
        
        $array['accno'] = $row[0];
        $array['accdesc'] = $row[1];
        $array['defaultgl_vat'] = $row[2];
        $array['Vat'] = $row[3];
        
        return $array;
    }
               
    function GetSupplierCurrency($itemcode){
        global $db;
      
        $ResultIndex = DB_query("SELECT 
                    `debtors`.`curr_cod`,
                    `postinggroups`.`debtorsaccount`,
                    `postinggroups`.`VATinclusive`,
                    `postinggroups`.`IsTaxed`
                    FROM `debtors`
                    left join `postinggroups` on `customerposting`=`postinggroups`.code 
                    where itemcode='".$itemcode."'", $db);
        $rows = DB_fetch_row($ResultIndex);
        $postingGroups['curr_cod'] = $rows[0];
        $postingGroups['creditorsaccount'] = $rows[1];
        $postingGroups['VATinclusive'] = $rows[2];
        $postingGroups['IsTaxed'] = $rows[3];
        
        if(is_null($postingGroups['creditorsaccount']) and mb_strlen($itemcode)>2){
            prnMsg('Please set up a posting group for this Customer','warn');
        }
        
        return $postingGroups;
    }
       
    Function GLentries($account,$vendorid,$amount=0,$grossamount=0,$mTax=0){
        Global $db;
        
        $Tax     = (($mTax=='')?0:$mTax);
        $glarray = $this->GetGL($account,$db);
        $vArray  = $this->GetSupplierCurrency($vendorid);
        $rate    = (($glarray['Vat']=='')? 0 : $glarray['Vat']);
       
        if($vArray['IsTaxed']==true and $_POST['calculatevat']==1){
            $R   = ($vArray['VATinclusive']==true)? (($rate+100)/100) : ($rate/100);
            
            if($vArray['VATinclusive'] == true){
              $Grossamt = $grossamount;
              $amount   = ($grossamount == 0)? 0 : ($grossamount / $R) ; 
              $Tax      = $Grossamt - $amount ;
            } else {
              $Tax      =  ($amount * $R) ;
              $Grossamt = $amount + $Tax;
            }
           
        }else{
            $amount   = $grossamount - $Tax;
            $Grossamt = $grossamount;
        }
               
         $_SESSION['JournalEntryDetails'][$this->I] = 
            array('account' => $glarray['accno'],
                  'accountdescription' => $glarray['accdesc'],
                  'amount' => $amount,
                  'vatamount' => $Tax,
                  'Grossamount' => $Grossamt,
                  'defaultgl_vat' => $glarray['defaultgl_vat']);
        
             $this->set();
             $this->JOURNAL = $_SESSION['JournalEntryDetails'];
    }
       
    Function cmbGL($objname,$value){
        Global $db;
        
        $BalanceSheet=array();
    $BalanceSheet[0]="Balance Sheet";
    $BalanceSheet[1]="Profit and Loss";
    
        $array='<select name="'.$objname.'" onchange="ReloadForm(Journal.update)"><option></option>';
        $ResultIndex = DB_query("Select accno,accdesc,ReportCode,ReportStyle,`balance_income` FROM `acct` "
                . " where `ReportStyle`=0 and `direct`=1 and `inactive`=0"
                . " order by accdesc asc", $db);
        while($row = DB_fetch_array($ResultIndex)){
            $array .='<option value="'.$row['accno'].'" '.(trim($row['accno'])==trim($value)?'selected="selected"':'').'>'.$row['accdesc'].' "'.$BalanceSheet[$row['balance_income']].'"</option>';
        }
        
        $array .='</select>';
    
        return $array;
    }
     
    function set(){
        $this->cmbGL('account'.$this->I,$_POST['account'.$this->I]);
      
    }
       
    
    
    
    
    function ShowTable(){
        if(isset($this->JOURNAL)){
            $_SESSION['Grossamounttotal'] = 0;
            $_SESSION['amounttotal'] = 0;
            $_SESSION['Vatamounttotal'] = 0 ;
            
                foreach ($this->JOURNAL as $key => $value) {
                    $NewKey=($key+1);
                    
                    echo sprintf('<tr>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td>%s</td>'
                        . '<td class="number"><input type="hidden" class="number" name="amount'.$key.'" value="%s"  size="10" maxlength="11" readonly="readonly"/>%s</td>'
                        . '<td class="number"><input type="text" class="number" name="vatamount'.$key.'"  value="%s" size="10"  maxlength="11"/></td>'
                        . '<td class="number"><input type="text" class="number" name="Grossamount'.$key.'"  value="%s" size="10" autofocus="autofocus" maxlength="11"/></td>'
                        . '</tr>',
                        $NewKey,
                        $this->cmbGL('account'.$key,$value['account']),
                        $value['accountdescription'], 
                        $value['amount'],
                        $value['amount'],
                        $value['vatamount'],
                        $value['Grossamount']);
                    
                        $_SESSION['amounttotal'] += $value['amount'];
                        $_SESSION['Vatamounttotal'] += $value['vatamount'];
                        $_SESSION['Grossamounttotal'] += $value['Grossamount'];
                     }
        }
    }
    
    Function SaveJournal(){
        Global $db;
        
         $periodno = GetPeriod($_POST['date'],$db,TRUE);
         $JOURNAL  = GetNextTransNo(0,$db);
            $Cust = $this->GetSupplierCurrency($_POST['VendorID']);
         $Conrate = $this->ExtractRate($_POST['currencycode']);
             
         $this->JournalArray[] = sprintf("INSERT INTO `EnterbillHeaders`
          (`date`,`documenttype`,`documentno`,`narration`,`journalno`,whtax,VendorID) VALUES ('%s','%s','%s','%s','%s','%s','%s')",
          FormatDateForSQL($_POST['date']),($_SESSION['Grossamounttotal']>0?'25':'11'),$_POST['documentno'],
          $_POST['reference'].$_POST['comments'],$JOURNAL,$_POST['withholdtax'],$_POST['VendorID']);
 
         
        $this->JournalArray[] = sprintf("INSERT INTO `CustomerStatement`
         (`Date`,`Documentno` ,`Documenttype` ,`Accountno`,`Grossamount` ,`JournalNo`,`Dimension_One`,`Dimension_Two`,`Currency`) VALUES
         ('%s','%s','%s' ,'%s' ,%f ,'%s' ,'%s' ,'%s' ,'%s')",
         FormatDateForSQL($_POST['date']),$_POST['documentno'],($_SESSION['Grossamounttotal']>0?'25':'11'),$_POST['VendorID'],($_SESSION['Grossamounttotal']),
         $JOURNAL,$_POST['DimensionOne'],$_POST['DimensionTwo'],$_POST['currencycode']);
                 
        $this->JournalArray[] = sprintf("INSERT INTO `debtorsledger`
        (`date`,`details`,`flag`,`invref` ,`acctfolio`,`amount`,`type`,`curr_cod`,`curr_rat`,`i_n_t`,`period`,`journal`,`typ`,`systypes_1`,`ledger`)
         VALUES ('%s','%s','%s','%s','%s',%f,'%s','%s',%f,'%s','%s','%s','%s','%s','%s')",FormatDateForSQL($_POST['date']),
         $_POST['reference'].$_POST['comments'],'CR',$_POST['documentno'],$_POST['VendorID'],($_SESSION['Grossamounttotal']),'P',$_POST['currencycode'],
         $Conrate,'J',$periodno,$JOURNAL,'P',($_SESSION['Grossamounttotal']>0?'25':'11'),$Cust['creditorsaccount']);
   
         
       $this->JOURNAL=$_SESSION['JournalEntryDetails'];
       foreach ($this->JOURNAL as $key => $row)  {
           
           if($row['Grossamount']>0){
           $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
           (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
           `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
           `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
           `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
            '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
            $_POST['documentno'],'25',$Cust['creditorsaccount'],$row['account'],$row['Grossamount'],
            $_POST['currencycode'], $Conrate,'',$_POST['VendorID'],'',0,$_POST['reference'].$_POST['comments'],
             '0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
           }else{
                $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
           (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
           `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
           `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
           `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
            '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
            $_POST['documentno'],'11',$row['account'],$Cust['creditorsaccount'],abs($row['Grossamount']),
            $_POST['currencycode'], $Conrate,'',$_POST['VendorID'],'',0,$_POST['reference'].$_POST['comments'],
             '0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
 
           }
           if(mb_strlen($row['defaultgl_vat'])>2){
               
               if($row['vatamount']>0){
                $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
                 (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
                  `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
                  `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
                  `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
                  '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
                  $_POST['documentno'],'25',$row['account'],$row['defaultgl_vat'],$row['vatamount'],
                  $_POST['currencycode'], $Conrate,'',$_POST['VendorID'],'',0,
                  $_POST['reference'].$_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
               }else{
                  $this->JournalArray[]= sprintf("INSERT INTO `Generalledger`
                 (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,
                  `balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`cutomercode`,`suppliercode` ,
                  `bankcode`,`reconcilled`,`narration`,`ExchangeRateDiff`,`VATaccountcode`,`VATamount`,
                  `dimension`,`dimension2`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s','%s','%s',
                  '%s','%s','%s','%s','%s','%s','%s')", $JOURNAL,FormatDateForSQL($_POST['date']),$periodno,
                  $_POST['documentno'],'11',$row['defaultgl_vat'],$row['account'],abs($row['vatamount']),
                  $_POST['currencycode'], $Conrate,'',$_POST['VendorID'],'',0,
                  $_POST['reference'].$_POST['comments'],'0','','0',$_POST['DimensionOne'],$_POST['DimensionTwo'] );
               }
                
           }
          
              if($row['vatamount']>0){
                    $this->JournalArray[] = sprintf("INSERT INTO `EnterbillsLines`
                   (`documenttype`,`documentno`,`journalno`,`account`,`vatamount`,`grossamount`)
                    VALUES ('%s','%s' ,'%s' ,'%s' ,%f ,%f)",($_SESSION['Grossamounttotal']>0?'25':'11')
                   , $_POST['documentno'],$JOURNAL,$row['account'],$row['vatamount'],$row['Grossamount']);
              } else {
                    $this->JournalArray[] = sprintf("INSERT INTO `EnterbillsLines`
                   (`documenttype`,`documentno`,`journalno`,`account`,`vatamount`,`grossamount`)
                    VALUES ('%s','%s' ,'%s' ,'%s' ,%f ,%f)",($_SESSION['Grossamounttotal']>0?'25':'11')
                   , $_POST['documentno'],$JOURNAL,$row['account'],$row['vatamount'],$row['Grossamount']);  
              }
              
       }
        
        return  $this->JournalArray;
    }
    
         
}


?>