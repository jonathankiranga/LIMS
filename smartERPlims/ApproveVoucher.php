<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Payment voucher Approval');
include('includes/header.inc');
include('includes/budgetbalance.php');
include('includes/chartbalancing.inc');
$TRBanks = new ShowBankAccounts();

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' . _('Payment Voucher Approval') .'" alt="" />' . ' ' . _('Payment Voucher Approval') . '</p>';

if(isset($_GET['edit'])){
    $selectedrec = $_GET['edit'];
}elseif(isset($_POST['pvjounal'])){
    $selectedrec = $_POST['pvjounal'];
}

if(isset($_POST['approve'])){
    $SQL=sprintf("Update paymentvoucherheader set `status`='1',`Comments`='%s' where `journal`='%s'",$_POST['comments'],$_POST['pvjounal']);
    DB_query($SQL,$db);

    header("Location:PDFpaymentvoucher.php"); 
//change yoursite.com to the name of you site!!
}elseif($_POST['decline']){
    $SQL=sprintf("Update paymentvoucherheader set `status`='9',`Comments`='%s' where `journal`='%s'",$_POST['comments'],$_POST['pvjounal']);
    DB_query($SQL, $db);
    
    header("Location:PDFpaymentvoucher.php"); 
//change yoursite.com to the name of you site!!
}

if(isset($selectedrec)){
       $sql="SELECT
       `docno`
      ,`paymentvoucherheader`.`date`
      ,`creditors`.`customer`
      ,`externalref`
      ,`narrative`
      ,`amount`
      ,`paymentvoucherheader`.`itemcode`
      ,`journal`
      ,`currency`
  FROM `paymentvoucherheader` join `creditors`
  on `paymentvoucherheader`.`itemcode`=`creditors`.`itemcode`
  where `paymentvoucherheader`.`journal`='".$selectedrec."'";
    $ResultIndex = DB_query($sql,$db);
    $Row = DB_fetch_row($ResultIndex);
    $_POST['documentno'] = $Row[0];
    $_POST['date'] = ConvertSQLDate($Row[1]);
    $_POST['reference'] = $Row[3];
    $_POST['VendorID'] = $Row[6];
    $_POST['VendorName'] = $Row[2];
    $_POST['currencycode'] = $Row[8];


echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?new=1">To Create A new Voucher click here</a>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="custreseipts"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';

echo '<input type="hidden" name="pvjounal" value="'.$selectedrec.'"/>';

Echo '<table class="slection">';
echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<tr><td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/></td>'
        . '<td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';
echo '<tr><td>Supplier ID</td>'
        . '<td><input tabindex="4" type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"/>'
        . '</td>'
        . '<td>Supplier Name</td>'
        . '<td><input tabindex="5" type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td>'
   . '<input tabindex="6" type="text" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';
       
echo '</tr>';
   
    $sql="SELECT 
        `paymentvoucherheader`.`date` as Date,
        `paymentvoucherheader`.`docno` as Documentno,
        `paymentvoucherheader`.`itemcode` as Accountno,
        `paymentvoucherline`.`amount` as Tamount,
        `paymentvoucherheader`.`journal` as JournalNo
      FROM `paymentvoucherheader` 
      join `paymentvoucherline` on `paymentvoucherheader`.`journal`=`paymentvoucherline`.`journal`
      where `paymentvoucherheader`.`journal`='".$selectedrec."'";
      
    
    if(isset($_GET['edit'])){
            $ResultIndex=DB_query($sql,$db);
            while($row=DB_fetch_array($ResultIndex)){
                $_POST['topay'][$row['JournalNo']]= $row['Tamount'];
            }
        }

    Echo '<tr><td colspan="6">'
        . '<table class="table1"><tr>'
        . '<th>Date</th>'
        . '<th>Doc No</th>'
        . '<th class="number">Pay This</th>'
        . '</tr>';

      
$k=0; 
$TotalAmount=0;

$ResultIndex=DB_query($sql,$db);
while($row=DB_fetch_array($ResultIndex)){
  
        if(isset($_GET['edit'])){
            $amount = $_POST['topay'][$row['JournalNo']];
        }else{
            $amount = $row['Tamount'];
        }
        
        $TotalAmount += $row['Tamount'];
        if($k==1){ $k=0; } else {  $k++;  }
               
        $linerow = sprintf('<tr class="'.(($k==0)?'OddTableRows':'EvenTableRows').'">'
        . '<td>%s</td>'
        . '<td>%s</td>'
        . '<td><input type="text" class="number" size="10"  readonly="readonly" '
        . ' value="'.number_format($row['Tamount'],2).'"  name="topay['.$row['JournalNo'].']"/>'
        . '</td></tr>',
        ConvertSQLDate($row['Date']), 
                $row['Documentno']);
        
        echo $linerow;
     
}



echo '<tr><td></td><td>Total Amount Posted :</td><td>'
. '<input type="text" class="number" size="10" value="'.number_format($TotalAmount,2).'" name="totalamount" readonly="readonly"/></td></tr>'
. '<tr><td colspan="2">Comments: <textarea  rows="2" cols="50" name="comments"></textarea></td></tr></table></td></tr>'
. '<tr>';

echo  '<td>'
    . '<input type="submit" name="approve" value="Approve Payment Voucher" onclick="return confirm(\''._('Do you want to Approve this Payment Voucher ?').'\');" />';
echo  '<input type="submit" name="decline" value="Decline Payment Voucher" onclick="return confirm(\''._('Do you want to "Decline" this Payment Voucher ?').'\');" /></td>';
     
echo '</tr></table>';
echo '</div></form>';

}else{
      header("Location:PDFpaymentvoucher.php"); 
}
include('includes/footer.inc');



?>
