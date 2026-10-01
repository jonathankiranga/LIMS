<?php
include('includes/session.inc');
$Title = _('Payments Allocation');
include('includes/header.inc');  
   
if(isset($_POST['cancel'])){
    unset($_POST);
}

 if(isset($_POST['resetaccount'])){
       DB_query("Delete from `PaymentsAllocation` where  `itemcode`='".$_POST['VendorID']."'", $db);
    }
    
    
if(isset($_POST['Auto'])){
    db_autoallocatevendors($_POST['VendorID']);
}
 
    echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Payments Allocation') .'" alt="" />' . ' ' . _('Payments Allocation') . '</p>';
    echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
    echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
    if(isset($_POST['VendorID'])){
      echo  '<input  type="hidden" name="VendorID" value="'.$_POST['VendorID'].'"/>';
      echo  '<input  type="hidden" name="VendorName" value="'.$_POST['VendorName'].'"/>';
      echo '<p class="page_title_text">For Account :'.$_POST['VendorName'].'</p>';
      echo '<table class="table table-bordered"><tr><th>DATE</th><th>DOC No</th><th>Doc Type</th><th class="number">Amount</th>'
          .'<th class="number">Unallocated</th><th class="number">To Allocate</th></tr>';
        
      $sql="SELECT `Date` as date
      ,`Documentno` as invref
      ,(select systypes_1.typename from systypes_1 where systypes_1.typeid=SupplierStatement.Documenttype) as doctypes
      ,`Accountno` as acctfolio
      ,`Grossamount` as amount
      ,`JournalNo` as journal
      ,(`Grossamount`+ IFNULL((SELECT sum(`amount`) FROM `PaymentsAllocation` 
         where `itemcode`=`SupplierStatement`.`Accountno` 
          and `date`=`SupplierStatement`.`date` 
          and `invoiceno`=`SupplierStatement`.`Documentno` 
          and `journalno`=`SupplierStatement`.`JournalNo`),0)) as Pamount
      FROM `SupplierStatement` where `SupplierStatement`.`Accountno`='".$_POST['VendorID']."'";

      
      $ResultIndex=DB_query($sql,$db);
      while($row=DB_fetch_array($ResultIndex)){
          $maxamount = $row['Pamount'];
          if($maxamount<0){
                echo sprintf('<tr>'
                           . '<td>%s</td>'
                           . '<td>%s</td>'
                           . '<td>%s</td>'
                           . '<td><input type="text" class="number" value="%f" readonly="readonly"/></td>'
                           . '<td><input type="text" class="number" value="%f" name="Minus['.$row['journal'].']" readonly="readonly"/></td>'
                           . '<td></td>'
                           . '</tr>',ConvertSQLDate($row['date']),
                           $row['invref'],
                           $row['doctypes'],
                           $row['amount'],
                           $maxamount);
            }
      }

      echo '<tr><td colspan="4"><input type="submit" name="Auto" value="Auto Allocate"/>'
      . '<input type="submit" name="resetaccount" value="Reset Allocation"/></td>'
      . '<td><input type="submit" name="cancel" value="Select Another account"/></td></tr>';
      echo '</table>';
      
    } else {

    echo '<table class="table table-bordered">';
     echo '<tr><td>Supplier ID</td>'
        . '<td><input tabindex="4" type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchvendor" value="Search for Vendor"/></td></tr>'
        . '<tr><td>Supplier Name</td>'
        . '<td><input tabindex="5" type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  required="required" /></td></tr>';
  echo '<input type="hidden"  size="5" name="currencycode" id="currencycode" value=""/>';

    echo '<tr><td></td><td><input type="submit" name="Submit" value="Select Account"/></td></tr>'
    . '</table>';
  
    }
    
   echo '</div></form>';

   include('includes/footer.inc');
   

?>
<?php
function db_autoallocatevendors($accountno){
    global $db;

    $accountno = DB_escape_string(trim($accountno));

    $receiptsSQL = "SELECT
            (ss.Grossamount - IFNULL((
                SELECT SUM(pa.amount)
                FROM PaymentsAllocation pa
                WHERE pa.receiptjournal = ss.JournalNo
                  AND pa.itemcode = '" . $accountno . "'
            ),0)) AS AMOUNT2,
            ss.JournalNo,
            ss.Documentno
        FROM SupplierStatement ss
        WHERE ss.Accountno = '" . $accountno . "'
          AND (ss.Grossamount - IFNULL((
                SELECT SUM(pa.amount)
                FROM PaymentsAllocation pa
                WHERE pa.receiptjournal = ss.JournalNo
                  AND pa.itemcode = '" . $accountno . "'
          ),0)) > 0
        ORDER BY ss.Date ASC";

    $receiptsResult = DB_query($receiptsSQL, $db);
    while($receiptRow = DB_fetch_array($receiptsResult)){
        $amountReceipt  = (float)$receiptRow['AMOUNT2'];
        $journalReceipt = DB_escape_string($receiptRow['JournalNo']);
        $receiptNo      = DB_escape_string($receiptRow['Documentno']);

        $invoicesSQL = "SELECT
                (ss.Grossamount + IFNULL((
                    SELECT SUM(pa.amount)
                    FROM PaymentsAllocation pa
                    WHERE pa.journalno = ss.JournalNo
                      AND pa.itemcode = '" . $accountno . "'
                ),0)) AS AMOUNT2,
                ss.JournalNo
            FROM SupplierStatement ss
            WHERE ss.Accountno = '" . $accountno . "'
              AND (ss.Grossamount + IFNULL((
                    SELECT SUM(pa.amount)
                    FROM PaymentsAllocation pa
                    WHERE pa.journalno = ss.JournalNo
                      AND pa.itemcode = '" . $accountno . "'
              ),0)) < 0
            ORDER BY ss.Date ASC";

        $invoicesResult = DB_query($invoicesSQL, $db);
        while($amountReceipt > 0 && ($invoiceRow = DB_fetch_array($invoicesResult))){
            $amountInvoice = (float)$invoiceRow['AMOUNT2'];
            $journalNoInv  = DB_escape_string($invoiceRow['JournalNo']);

            if(($amountReceipt + $amountInvoice) > 0){
                DB_query("INSERT INTO PaymentsAllocation
                    (itemcode, date, invoiceno, journalno, doctype, receiptno, amount, receiptjournal)
                    SELECT Accountno, Date, Documentno, JournalNo, Documenttype,
                           '" . $receiptNo . "', (Grossamount * -1), '" . $journalReceipt . "'
                    FROM SupplierStatement
                    WHERE JournalNo = '" . $journalNoInv . "'", $db);

                $amountReceipt = $amountReceipt + $amountInvoice;
            } else {
                DB_query("INSERT INTO PaymentsAllocation
                    (itemcode, date, invoiceno, journalno, doctype, receiptno, amount, receiptjournal)
                    SELECT Accountno, Date, Documentno, JournalNo, Documenttype,
                           '" . $receiptNo . "', " . $amountReceipt . ", '" . $journalReceipt . "'
                    FROM SupplierStatement
                    WHERE JournalNo = '" . $journalNoInv . "'", $db);

                $amountReceipt = $amountReceipt + $amountInvoice;
            }
        }
    }
}
?>
