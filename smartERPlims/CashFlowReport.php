<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Cash Flow Report');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/chartbalancing.inc');
include('includes/AccountBalance.inc');
include('includes/GetBalance_withfilter.inc');
 $FR = new FinancialPeriods();
 
 $_SESSION['cflowrunning']=array();
 $_SESSION['cflowrunning']['monthAr']=array();
 $_SESSION['cflowrunning']['monthcs']=array();
 $_SESSION['GoingOut']=array();
 $_SESSION['ComingIn']=array();
 
 $_SESSION['cflow2021']=0;
 $_SESSION['cflowExpensee2021']=0;
 $_SESSION['Ledger']=array();
  
 $DocyTypes=array(
   '12'=>'receipt',
   '1'=>'Salesorder',
   '0'=>'CashJournal',
   '22'=>'CreditorPayment',
   '53'=>'Paymentvoucher',
   '2'=>'pettycash'
 );
 
 $SalesArray=array( 'receipt'=>'Sales', 'Salesorder'=>'Sales' );
 
 $ExpensesArray=array(
   'CashJournal'=>'Generalledger',
   'CreditorPayment'=>'Generalledger',
   'Paymentvoucher'=>'Generalledger',
   'pettycash'=>'Generalledger'
 );
 
 $_SESSION['acct'] = array();
 $_SESSION['DrillDownData'] = array();
 
 $ResultIndex=DB_query("SELECT `defaultgl_vat` FROM `inventorypostinggroup`", $db);
 while ($row = DB_fetch_array($ResultIndex)) {
    $_SESSION['defaultgl_vat'][] = trim($row['defaultgl_vat']);
 }
 
 $ResultIndex=DB_query("select `debtorsaccount`,`salesaccount` from `postinggroups`", $db);
 while ($row = DB_fetch_array($ResultIndex)) {
    $_SESSION['debtorsaccount'][trim($row['debtorsaccount'])] = trim($row['salesaccount']);
 }           
 
 $ResultIndex=DB_query("select `creditorsaccount`,`purchaseaccount` FROM `arpostinggroups`", $db);
while ($row = DB_fetch_array($ResultIndex)) {
    $_SESSION['creditorsaccount'][trim($row['creditorsaccount'])] = trim($row['purchaseaccount']);
 }              
 
 $ResultIndex=DB_query("SELECT `balance_income`,`accgrp`,`currency`,`accno`,`accdesc` FROM `acct` order by `balance_income` desc", $db);
 while ($row = DB_fetch_array($ResultIndex)) {
    $accno = trim($row['accno']);
    $_SESSION['acct'][$accno] = $row;
 }
 
 $_SESSION['bankaccounts']=array();
 $ResultIndex=DB_query("SELECT `accountcode` FROM `BankAccounts`", $db);
 while ($row = DB_fetch_array($ResultIndex)) {
    $_SESSION['bankaccounts'][] = trim($row['accountcode']);
 }
 
 $_SESSION['Generalledger']=array();
 $myCashflowhtml='';$MonthName='';
 
 if(isset($_POST['cashFlow'])){
 $_SESSION['Financial_Periods']=$_POST['Financial_Periods'];
  
            $ResultIndex=DB_query(sprintf("SELECT `start_date`,`end_date`,`Name`,`periodno`  
            FROM `FinancialPeriods` where `periodno`='%s' order by start_date asc",$_SESSION['Financial_Periods']), $db);

            while ($row = DB_fetch_array($ResultIndex)) {
                 $Querry[]=$row;
            }
 
    echo  Getdata($Querry);
    
 } else {
     
  include('includes/header.inc');
  $FR = new FinancialPeriods();
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' .$Title .'" alt="" />' .$Title . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table  class="table table-bordered"><tr><td>Reporting Period</td></tr>';
        $FR->Get();
  echo '<tr><td colspan="2"><input type="submit" name="cashFlow" value="Generate Cash Flow Report"/></td></tr></table></div></form>';
  include('includes/footer.inc');
   
 }
 
 
Function Getdata($Querry){
    /*Querry`start_date`,Querry`end_date`,Querry`Name`,`periodno`*/
    Global $db,$DocyTypes,$OrderOfDisplay,$acct,$myCashflowhtml,$Generalledger;
    $sql="Select MIn(`start_date`),MAX(`end_date`)
    from `FinancialPeriods` where `periodno`='".$_SESSION['Financial_Periods']."'";
   
    $ResultIndex=DB_System($sql,$db);
    $daterange=DB_fetch_row($ResultIndex);
    
    $SQL =sprintf("select `journalno`,`accountcode`,`balaccountcode`,`amount`,`cutomercode`,`suppliercode`,`DocumentType`
    from Generalledger where `amount`>0 and Docdate between '%s' and '%s' order by `amount` ASC",$daterange[0],$daterange[1]);
        
    $REsults = DB_query($SQL,$db);
    while($rows = DB_fetch_array($REsults)){
         $journal=trim($rows['journalno']);
         $_SESSION['Generalledger'][$journal][]=$rows;
    }
     
    $myCashflowhtml='<div class="confirmso" id="myAccountTable"><p>Monthly cashflow projection</p>'
    . '<p>'.$_SESSION['CompanyRecord']['coyname'].'</p>'
    . '<p>Report from:'.ConvertSQLDate($daterange[0]).' To :'.ConvertSQLDate($daterange[1]).'</p>'
    . '<table class="table table-striped table-bordered">'
    . '<tr><th></th><th>Pre-startup</th>';
    foreach ($Querry as $value) {
        $myCashflowhtml .= sprintf('<th>%s</th>',$value['Name']);
    }
    $myCashflowhtml .='<th>Total</th></tr>';
    /* Get Data from other tables */
    getcbopenbal();
    $myCashflowhtml .= '<tr><td colspan="15">2. INCOME<br/>[(a) Cash sales,(b) Collections from credit accounts ,(c) Loan or other cash injection]</td></tr>';
    InComingFunds();
    TotalReceipts();
    TotalCashAvailable();
    $myCashflowhtml .= '<tr><td colspan="15"> 5. OUTGOINGS</td></tr>';
    OutGoingFunds();
    TotalPaidout();
    CashPosition();
     
   $myCashflowhtml .= '<tr><td colspan="15"><input type="button" onclick="tableToExcel(\'myAccountTable\',\'Cash Flow Report\')" value="Export to Excel"></td></tr>';
   $myCashflowhtml .='</table></div>';
    return $myCashflowhtml;
} 
 
function getcbopenbal(){
    global $db,$bankaccounts,$Querry,$myCashflowhtml;
    $openbal=0;
    
    foreach ($_SESSION['bankaccounts'] as $accno) {
            $REsults = db_GetBalance_withfilter($_SESSION['Financial_Periods'],$accno);
            $rowdata = DB_fetch_row($REsults);
            $debit = (float) (isset($rowdata[0])?$rowdata[0]:0);
            $credit= (float) (isset($rowdata[1])?$rowdata[1]:0);
            $openbal += ($debit-$credit);
    }
    
   $myCashflowhtml .= '<tr><td style="min-width:200px;">1. CASH ON THE PREMISES<br/>(Beginning of month)</td><td>'.number_format($openbal,2).'</td>';
    foreach ($Querry as $value) {
        $myCashflowhtml .= '<td></td>';
    }
    $myCashflowhtml .='<td></td></tr>';
   
    $_SESSION['cflow2021'] =(float) $openbal;
   
    return $myCashflowhtml;
 }
 
Function InComingFunds(){
      global $db,$Querry;
    
       foreach ($Querry as $key => $Querryvalue) {
            $MonthName = trim($Querryvalue['Name']);
             
        $SQL=sprintf("select `bankcode`,`DocDate`,`doctype`,`DocumentNo`,`TransType`,`itemcode`,`journal`,`amount` 
        from `BankTransactions` where `DocDate` between '%s' and '%s' and (`amount` > 0) order by `DocDate` asc ",
                $Querryvalue["start_date"],$Querryvalue["end_date"]);
            $REsults = DB_query($SQL,$db);
            while($bankTransrow = DB_fetch_array($REsults)){
                $doctype   = trim($bankTransrow['doctype']);
                $journal   = trim($bankTransrow['journal']);
                $paidIn    = (float)$bankTransrow["amount"];
                DrillAllocations($journal,$doctype,$MonthName,$paidIn,$Querryvalue);
            }
                        
      }
      
    DispayDeposits();
   $_SESSION['DrillDownData']=array();
}


function DrillAllocations($journal,$typ,$MonthName,$paidOut,$Querryvalue){
    global $db;
    
$start_date=$Querryvalue["start_date"];
$end_date=$Querryvalue["end_date"];
$Lamount=$paidOut;
DrillReceivables($journal,$MonthName,$paidOut,$Querryvalue);
  /*
 $sql=sprintf("SELECT `journalno` FROM `ReceiptsAllocation` where receiptjournal='%s'",$journal);
 $ResultIndex=DB_query($sql,$db);
 $ReceiptsAllocation=DB_fetch_row($ResultIndex);
  if(isset($ReceiptsAllocation[0])){
       $journal=trim($ReceiptsAllocation[0]);
            if(isset($_SESSION['Generalledger'][$journal])){
                  $ledger = $_SESSION['Generalledger'][$journal];
                  foreach ($ledger as $row) {
                      
                      $accountcode = trim($row['accountcode']);
                      $balaccountcode=trim($row['balaccountcode']);
                      $useamount =(float)GetReceiptNetamount($Lamount,$row['amount']);
                      $Lamount -= $row['amount'];
                    if(SearchVATAccount($balaccountcode)==TRUE){$balaccountcode=$accountcode;}
                      $_SESSION['ComingIn'][$balaccountcode][$MonthName] += $useamount;
                      $_SESSION['DrillDownData'][$balaccountcode][$MonthName]="selectReports.php?Accountcode=".$accountcode."&fromdate=".$start_date."&Todate=".$end_date;
                      
                      
                  }
            }else{
              $SQL = sprintf("select `journalno`,`accountcode`,`balaccountcode`,`amount`,`DocumentType`
               from Generalledger where `amount`>0 and journalno='%s' order by `amount` asc",$journal);
               $ResultIndex = DB_query($SQL,$db);
               while($ledgerRow= DB_fetch_array($ResultIndex)){
                    $accountcode = trim($ledgerRow['accountcode']);
                    $balaccountcode=trim($ledgerRow['balaccountcode']);
                    $useamount =(float) GetReceiptNetamount($Lamount,$ledgerRow['amount']);
                    $Lamount -= $ledgerRow['amount'];
                if(SearchVATAccount($balaccountcode)==TRUE){$balaccountcode=$accountcode;}
    
                    $_SESSION['ComingIn'][$balaccountcode][$MonthName] += $useamount;
                    $_SESSION['DrillDownData'][$balaccountcode][$MonthName]="selectReports.php?Accountcode=".$accountcode."&fromdate=".$start_date."&Todate=".$end_date;
                  }
            }
   }else{
      DrillReceivables($journal,$MonthName,$paidOut,$Querryvalue);
   }
     */        
}

function DrillReceivables($journal,$MonthName,$Lamount=0,$Querryvalue){
   global $db;
   $start_date=$Querryvalue["start_date"];
   $end_date=$Querryvalue["end_date"];
                
    
   if(isset($_SESSION['Generalledger'][$journal])){
        $ledger = $_SESSION['Generalledger'][$journal];
        foreach ($ledger as $row) {
            $accountcode = trim($row['accountcode']);
            $balaccountcode=trim($row['balaccountcode']);
            $useamount =(float)GetReceiptNetamount($Lamount,$row['amount']);
            $Lamount -= $row['amount'];
            
            if((SearchmyBankArray($balaccountcode)==FALSE) and (SearchmyBankArray($accountcode)==TRUE)){
            $_SESSION['ComingIn'][$balaccountcode][$MonthName] += $useamount;
            $_SESSION['DrillDownData'][$balaccountcode][$MonthName]="selectReports.php?Accountcode=".$balaccountcode."&fromdate=".$start_date."&Todate=".$end_date;
            }
            
          }
        
   }else{
       $SQL = sprintf("select `journalno`,`accountcode`,`balaccountcode`,`amount`,`DocumentType`
       from Generalledger where `amount`>0 and journalno='%s' order by `amount` asc",$journal);
       $ResultIndex = DB_query($SQL,$db);
       while($ledgerRow= DB_fetch_array($ResultIndex)){
            $accountcode = trim($ledgerRow['accountcode']);
            $balaccountcode=trim($ledgerRow['balaccountcode']);
            $useamount =(float) GetReceiptNetamount($Lamount,$ledgerRow['amount']);
            $Lamount -= $ledgerRow['amount'];
            
           if((SearchmyBankArray($balaccountcode)==FALSE) and (SearchmyBankArray($accountcode)==TRUE)){
             $_SESSION['ComingIn'][$balaccountcode][$MonthName] += $useamount;
            $_SESSION['DrillDownData'][$balaccountcode][$MonthName]="selectReports.php?Accountcode=".$balaccountcode."&fromdate=".$start_date."&Todate=".$end_date;
            }
              
          }
   }
    
    
}

Function DispayDeposits(){
    global $db,$myCashflowhtml,$Querry;
    $runningTotal=0;
    $temp=array();
    $_SESSION['cflowrunning']['monthAr']=array();
    $copyAcct = $_SESSION['acct'];
    
    foreach ($_SESSION['acct'] as $accountcode => $row) {
       $Anything = 0;  $accountcode = trim($accountcode);
       if(SearchmyBankArray($accountcode)==false){
            if(isset($_SESSION['ComingIn'][$accountcode])){
                 $mycode = AccReceive($accountcode);
                 if($mycode==$accountcode){$Desc='';}else{$Desc=$copyAcct[$mycode]['accdesc']; $Desc='('.$Desc.')'; }
                  
                 $temp[$accountcode] = '<tr><td>'.$row['accdesc']. $Desc.'</td><td></td>';

                  foreach ($Querry as $key => $value) {
                      $month = trim($value['Name']);
                      $anchor = $_SESSION['DrillDownData'][$accountcode][$month];
                      $amount=(float) $_SESSION['ComingIn'][$accountcode][$month];
                      $temp[$accountcode] .='<td><a href="'.$anchor.'" target="_new">'.number_format($amount,2).'</a></td>';
                    
                      $_SESSION['cflowrunning']['monthAr'][$month] += $_SESSION['ComingIn'][$accountcode][$month];
                      
                      $runningTotal += $amount;
                      $Anything  += $amount;
                  }

               $temp[$accountcode] .='<td>'.number_format($runningTotal,2).'</td></tr>';
               
              if($Anything==0){ unset($temp[$accountcode]);}
               $runningTotal = 0;
               $Anything = 0;
            }
       
      
       }
    }
    
    foreach ($temp as $value) {
        $myCashflowhtml .= $value;
    }
    
     return $myCashflowhtml;
}

Function TotalReceipts(){
      global $myCashflowhtml,$Querry;
       $runningTotal=0;
       $myCashflowhtml .= '<tr><td>3. TOTAL CASH RECEIPTS<br/>[2a + 2b + 2c=3]</td><td></td>';
       foreach ($Querry as $key => $value) {
            $monthName = trim($value['Name']);
         //get the sum of receipts
       $ar =(float) $_SESSION['cflowrunning']['monthAr'][$monthName];
       //key has one value less than the month number
        $myCashflowhtml .= '<td>'.number_format($ar,2).'</td>';
          //save the total to month and running total at the close of the month
        $runningTotal += $ar;
     }
    
     $myCashflowhtml .='<td>'.number_format($runningTotal,2).'</td></tr>';
}
 
Function TotalCashAvailable(){
      global $myCashflowhtml,$Querry;
       $runningTotal=$_SESSION['cflow2021'];
       $myCashflowhtml .= '<tr><td>4. TOTAL CASH AVAILABLE  (Before cash out) [1 + 3]</td><td>'.number_format($runningTotal,2).'</td>';
       foreach ($Querry as $key => $value) {
            $monthName = trim($value['Name']);
         //get the sum of receipts
       $ar =(float) $_SESSION['cflowrunning']['monthAr'][$monthName];
       $runningTotal += $ar;
       //key has one value less than the month number
        $myCashflowhtml .= '<td>'.number_format($runningTotal,2).'</td>';
          //save the total to month and running total at the close of the month
        
     }
    
     $myCashflowhtml .='<td>'.number_format($runningTotal,2).'</td></tr>';
}

Function OutGoingFunds(){
      global $db,$Querry;
    
       foreach ($Querry as $key => $Querryvalue) {
           
        $SQL=sprintf("select `bankcode`,`DocDate`,`doctype`,`DocumentNo`,`TransType`,`itemcode`,`journal`,`amount` 
             from `BankTransactions` where `DocDate` between '%s' and '%s' and (`amount` < 0) order by `DocDate` asc ",  $Querryvalue["start_date"],$Querryvalue["end_date"]);
            $REsults = DB_query($SQL,$db);
            while($rows = DB_fetch_array($REsults)){
                GetFinnerDetails($rows,$Querryvalue);
            }
             
           
      }
      
    DispayExpenses();
        
}

function GetFinnerDetails($bankTransrow,$Querryvalue){
   global $bankaccounts,$MonthName;
    
   $doctype   = trim($bankTransrow['doctype']);
   $journal   = trim($bankTransrow['journal']);
   $MonthName = trim($Querryvalue['Name']);
   $paidOut   =(float)$bankTransrow["amount"];
   
   Drillgl($journal,$doctype,$MonthName,$paidOut,$Querryvalue);
   
 }

function Drillgl($journal,$typ,$MonthName,$paidOut,$Querryvalue){
    global $db;
      
   $start_date=$Querryvalue["start_date"];
   $end_date=$Querryvalue["end_date"];
 
   $Lamount=$paidOut;
 /*
  $ResultIndex=DB_query(sprintf("SELECT `journalno` FROM `PaymentsAllocation` where receiptjournal='%s'",$journal),$db);
  $PaymentsAllocation=DB_fetch_row($ResultIndex);
  if(isset($PaymentsAllocation[0])){
       $journal=trim($PaymentsAllocation[0]);
   
      if(isset($_SESSION['Generalledger'][$journal])){
        $ledger = $_SESSION['Generalledger'][$journal];
        foreach ($ledger as $row) {
            $accountcode = trim($row['accountcode']);
            $balaccountcode=trim($row['balaccountcode']);
            $useamount =(float)GetNetamount($Lamount,$row['amount']);
            $Lamount += $row['amount'];
           
            if(SearchVATAccount($accountcode)==TRUE){$accountcode=$balaccountcode;}
            if((SearchmyBankArray($balaccountcode)==FALSE) and (SearchmyBankArray($accountcode)==FALSE)){
            $_SESSION['GoingOut'][$accountcode][$MonthName] += $useamount;
            $_SESSION['DrillDownData'][$accountcode][$MonthName]="selectReports.php?Accountcode=".$balaccountcode."&fromdate=".$start_date."&Todate=".$end_date;
            }
          }
        
   }else{
       $SQL = sprintf("select `journalno`,`accountcode`,`balaccountcode`,`amount`,`DocumentType`
       from Generalledger where `amount`>0 and journalno='%s' order by `amount` asc",$journal);
       $ResultIndex = DB_query($SQL,$db);
       while($ledgerRow= DB_fetch_array($ResultIndex)){
            $accountcode = trim($ledgerRow['accountcode']);
            $balaccountcode=trim($ledgerRow['balaccountcode']);
            $useamount =(float) GetNetamount($Lamount,$ledgerRow['amount']);
            $Lamount += $ledgerRow['amount'];
            
            if(SearchVATAccount($accountcode)==TRUE){$accountcode=$balaccountcode;}
            if((SearchmyBankArray($balaccountcode)==FALSE) and (SearchmyBankArray($accountcode)==FALSE)){
             $_SESSION['GoingOut'][$accountcode][$MonthName] += $useamount;
             $_SESSION['DrillDownData'][$accountcode][$MonthName]="selectReports.php?Accountcode=".$balaccountcode."&fromdate=".$start_date."&Todate=".$end_date;
            }
          }
   }
   
  }else{
      DrillPG($journal,$MonthName,$paidOut,$Querryvalue);
   }
             */
   
     DrillPG($journal,$MonthName,$paidOut,$Querryvalue);
}


function DrillPG($journal,$MonthName,$Lamount=0,$Querryvalue){
   global $db,$MonthName;
   $start_date=$Querryvalue["start_date"];
   $end_date=$Querryvalue["end_date"];
           
    
   if(isset($_SESSION['Generalledger'][$journal])){
        $ledger = $_SESSION['Generalledger'][$journal];
        foreach ($ledger as $row) {
            $accountcode = trim($row['accountcode']);
            $balaccountcode=trim($row['balaccountcode']);
            $useamount =(float)GetNetamount($Lamount,$row['amount']);
            $Lamount += $row['amount'];
            
            if((SearchmyBankArray($balaccountcode)==TRUE) and (SearchmyBankArray($accountcode)==FALSE)){
            $_SESSION['GoingOut'][$accountcode][$MonthName] += $useamount;
            $_SESSION['DrillDownData'][$accountcode][$MonthName]="selectReports.php?Accountcode=".$accountcode."&fromdate=".$start_date."&Todate=".$end_date;
            }
            
          }
        
   }else{
       $SQL = sprintf("select `journalno`,`accountcode`,`balaccountcode`,`amount`,`DocumentType`
       from Generalledger where `amount`>0 and journalno='%s' order by `amount` asc",$journal);
       $ResultIndex = DB_query($SQL,$db);
       while($ledgerRow= DB_fetch_array($ResultIndex)){
            $accountcode = trim($ledgerRow['accountcode']);
            $balaccountcode=trim($ledgerRow['balaccountcode']);
            $useamount =(float) GetNetamount($Lamount,$ledgerRow['amount']);
            $Lamount += $ledgerRow['amount'];
            if((SearchmyBankArray($balaccountcode)==TRUE) and  (SearchmyBankArray($accountcode)==FALSE)){
            $_SESSION['GoingOut'][$accountcode][$MonthName] += $useamount;
            $_SESSION['DrillDownData'][$accountcode][$MonthName]="selectReports.php?Accountcode=".$accountcode."&fromdate=".$start_date."&Todate=".$end_date;
           }
          }
   }
    
   
}

Function DispayExpenses(){
    global $db,$myCashflowhtml,$Querry;
    $runningTotal = 0;
    $temp = array();
    $copyAcct = $_SESSION['acct'];
    
    foreach ($_SESSION['acct'] as $accountcode => $row) {
       $Anything = 0;
       $accountcode = trim($accountcode);
       
            if(isset($_SESSION['GoingOut'][$accountcode])){
                   $mycode = AccPayable($accountcode);
                 if($mycode==$accountcode){$Desc=''; }else{  $Desc=$copyAcct[$mycode]['accdesc']; $Desc='('.$Desc.')'; }
       
                 $temp[$accountcode] = '<tr><td>'.$row['accdesc'].$Desc.'</td><td></td>';

                  foreach ($Querry as $key => $value) {
                      $month = trim($value['Name']);
                      $anchor = $_SESSION['DrillDownData'][$accountcode][$month];
                      $amount=(float) $_SESSION['GoingOut'][$accountcode][$month];
                   
                      $temp[$accountcode] .='<td><a href="'.$anchor.'" target="_new">'.number_format($amount * -1,2).'</a></td>';
    
                     $_SESSION['cflowrunning']['monthAP'][$month] += $_SESSION['GoingOut'][$accountcode][$month];
          
                      $runningTotal += $amount;
                      $Anything  += $amount;
                  }

               $temp[$accountcode] .='<td>'.number_format($runningTotal * -1,2).'</td></tr>';
              if($Anything==0){ unset($temp[$accountcode]);}
               $runningTotal = 0;
               $Anything = 0;
            }
      
    }
    
    foreach ($temp as $value) {
        $myCashflowhtml .= $value;
    }
    
    $_SESSION['cflowExpensee2021']=$runningTotal;
    
   return $myCashflowhtml;
}

Function TotalPaidout(){
      global $myCashflowhtml,$Querry;
       $runningTotal=0;
       $myCashflowhtml .= '<tr><td>6. TOTAL Cash Paid Out<br/>[5 ]</td><td></td>';
       foreach ($Querry as $key => $value) {
            $monthName = trim($value['Name']);
         //get the sum of receipts
       $ar =(float) $_SESSION['cflowrunning']['monthAP'][$monthName];
       //key has one value less than the month number
        $myCashflowhtml .= '<td>'.number_format($ar * -1,2).'</td>';
          //save the total to month and running total at the close of the month
        $runningTotal += $ar;
     }
    
     $myCashflowhtml .='<td>'.number_format($runningTotal * -1,2).'</td></tr>';
}

Function CashPosition(){
      global $myCashflowhtml,$Querry;
       $runningTotal=$_SESSION['cflow2021'];
       $myCashflowhtml .= '<tr><td>7. Cash Position<br/>[4-6 ]</td><td>'.number_format($runningTotal,2).'</td>';
       foreach ($Querry as $key => $value) {
         $monthName = trim($value['Name']);
            //get the sum of receipts
          $monthAr =(float) $_SESSION['cflowrunning']['monthAr'][$monthName];
          $monthAP =(float) $_SESSION['cflowrunning']['monthAP'][$monthName];
           $runningTotal += ($monthAr+ $monthAP);
           //key has one value less than the month number
         $myCashflowhtml .= '<td>'.number_format($runningTotal,2).'</td>';
          //save the total to month and running total at the close of the month
        
     }
    
     $myCashflowhtml .='<td>'.number_format($runningTotal,2).'</td></tr>';
}
 
function SearchmyBankArray($accountcode){
    
    $return=false;
    foreach ($_SESSION['bankaccounts'] as $item) {
    if (trim($item) == $accountcode) {
        $return=true;
        break;
    }
  }
  
 return  $return;
}

Function SearchVATAccount($accountcode){
    $return=false;
    
   foreach ($_SESSION['defaultgl_vat'] as $item) {
    if (trim($item) == $accountcode) {
        $return=true;
        break;
    }
  }
   
  return  $return;
}

Function GetNetamount($bankPaid,$ledgerAmount){
    $ToshowAmount=0;
    
    if(($bankPaid+$ledgerAmount)<=0){
        
        if(abs($bankPaid)<$ledgerAmount){
            $ToshowAmount=$ledgerAmount*-1;
        }else{
            $ToshowAmount=$bankPaid;
        }
    }
    
  return $ToshowAmount;
}

Function GetReceiptNetamount($bankPaid,$ledgerAmount){
    $ToshowAmount=0;
    
     
    if(($bankPaid-$ledgerAmount)>=0){
        
        if($bankPaid>$ledgerAmount){
            $ToshowAmount=$ledgerAmount;
        }else{
            $ToshowAmount=$bankPaid;
        }
    }
    
  return $ToshowAmount;
}


 Function AccReceive($account){
     
     if(isset($_SESSION['debtorsaccount'][trim($account)])){
         $account=$_SESSION['debtorsaccount'][trim($account)];
     }
     return $account;
 }
 
 Function AccPayable($account){
     
     if(isset($_SESSION['creditorsaccount'][trim($account)])){
         $account=$_SESSION['creditorsaccount'][trim($account)];
     }
     return $account;
 }
 
