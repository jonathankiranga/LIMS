<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.


if(isset($_GET['id'])){
     
$PaperSize='A4';
include('includes/PDFStarter.php');

$pdf->addInfo('Title',_('Financial Reports'));
$pdf->addInfo('Subject',_('Payment Voucher'));
$pdf->addInfo('Creator',_('SmartERP'));

$FontSize = 15;
$PageNumber = 0;
$line_height = 15;
$PageNumber = 0;
PaymentHeader();

    $SQL="SELECT `StatementNo`,`bankcode`,`narration`,`amount`"
        . " FROM `BankReconciliation`"
        . "  where `StatementNo`='".$_GET['id']."' "
        . "and `bankcode`='".$_GET['bankcode']."'"; ;
    $ResultIndex=DB_query($SQL,$db);

    while($row=DB_fetch_array($ResultIndex)){
    
         $LeftOvers = $pdf->addTextWrap( 45, $YPos,350, $FontSize, $row['narration'],'left');
         if(is_numeric($row['amount'])){
           $LeftOvers = $pdf->addTextWrap(360, $YPos, 85, $FontSize, number_format($row['amount'],2),'right');
         }else{
             $YPos -= $line_height * 2 ;
         }
         
         $YPos -= $line_height ;
        if($YPos < ($Bottom_Margin-$line_height * 2)){
            PaymentHeader();
        }
    }

$pdf->OutputD($_SESSION['DatabaseName']. '_'._('BankReconciliation').'_'. date('Y-m-d').'.pdf');
$pdf->__destruct();
     
} else {
    
  $Title = _('Print Reconciliation');   
  include('includes/header.inc');
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' . _('Print Reconciliation') .'" alt="" />' . _('Print Reconciliation') . '</p>';
  
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table class="table-bordered"><tr>'
          . '<th>Bank Name</th>'
          . '<th>Recon No</th>'
          . '</tr>';
  
  $SQL="SELECT DISTINCT 
       `BankReconciliation`.`StatementNo`
      ,`BankReconciliation`.bankcode
      ,BankAccounts.bankName
  FROM `BankReconciliation` 
  join BankAccounts on `BankReconciliation`.bankcode=BankAccounts.accountcode
  order by `BankReconciliation`.`StatementNo` desc" ;
  
  $ResultIndex=DB_query($SQL,$db);
  while($row=DB_fetch_array($ResultIndex)){
      
      echo sprintf('<tr><td><a href="%s">%s</a></td>',$_SERVER['PHP_SELF'].'?id='. trim($row['StatementNo']).'&bankcode='.$row['bankcode'],$row['bankName']);
      echo sprintf('<td>%s</td></tr>',$row['StatementNo']);
      
  }
  
  echo '</table>';
  echo '</div></form>';
  
  
include('includes/footer.inc');
    
    
    
}



Function PaymentHeader(){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin,$PageNumber;
    Global $line_height,$Bottom_Margin,$db,$FontSize,$Page_Width;
    
  $SQL="SELECT `BankReconciliation`.`StatementNo`,
	    `BankReconciliation`.`bankcode`,
	    `BankAccounts`.`bankName`
       FROM `BankReconciliation` 
       join `BankAccounts` on `BankReconciliation`.`bankcode`=`BankAccounts`.`accountcode`
       where `BankReconciliation`.`StatementNo`='".$_GET['id']."' "
          . " and `BankReconciliation`.`bankcode`='".$_GET['bankcode']."' limit 1";
       
    $ResultIndex=DB_query($SQL,$db);
    $rows = DB_fetch_row($ResultIndex);

    $PageNumber++;
// Inserts a page break if it is not the first page
    if ($PageNumber>1) {
        $pdf->newPage();
    }
        
       $XPos=46;
       // Print company logo
       $pdf->addJpegFromFile($_SESSION['LogoFile'],50,$Page_Height-$Top_Margin-$FontSize * 4,0,45);
       // Print company info
       $XPos = 60;
       $YPos = ($Page_Height-$Top_Margin-$FontSize*3)-30;
       $FontSize=14;
       $FontSize =10;
       $H=$Page_Height-$Top_Margin;
       $pdf->addText(300, $H-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
       $pdf->addText(300, $H-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
       $pdf->addText(300, $H-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
       $pdf->addText(300, $H-39, $FontSize, _('Phone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
       $pdf->addText(300, $H-48, $FontSize, $_SESSION['CompanyRecord']['email']);
       $pdf->addText(300, $H-57, $FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
       $pdf->addText(200, $H-100,10,_('Bank Reconciliation'));
       $pdf->addText(200, $H-120,10,_('Bank Name :').$rows[2]);
    
       $YPos = $H-140 ;
             
}


?>
