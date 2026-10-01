<?php
include('includes/session.inc');

If(isset($_GET['PVNUMBER'])){
    
$SQL="SELECT 
       `DocDate`
      ,`doctype`
      ,`DocumentNo`
      ,`itemcode`
      ,`journal`
      ,(0-`amount`) as amount
      ,`narrative`
      ,BankAccounts.currency
      ,BankAccounts.bankName
      ,`currencies`.hundredsname
   FROM `BankTransactions` join BankAccounts on 
  BankTransactions.bankcode=BankAccounts.accountcode 
  join currencies on BankAccounts.currency=`currencies`.`currabrev`
     where journal='".$_GET['PVNUMBER']."'";
  

$ResultIndex = DB_query($SQL,$db);
$rows = DB_fetch_row($ResultIndex);

$docno = $rows[2] ;
$date = $rows[0] ;
$itemcode = $rows[3] ;
$externalref = '' ;
$narrative ='' ;
$amount = $rows[5] ;
$journal = $rows[4] ;
$customer = htmlspecialcharsLocal_decode($rows[6]) ;
$hundredsname = $rows[9] ;
$currency = $rows[7] ;
$amountinwords = Num2Wrd($amount,$currency,$hundredsname);    
DB_free_result($ResultIndex);

$PaperSize='A5_Landscape';
    include('includes/PDFStarter.php');
    $pdf->addInfo('Title',_('Receipts'));
    $pdf->addInfo('Subject',_('Receipts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $collumrows = array();
    $collumrows[1] = $Right_Margin;
    $collumrows[2] = $collumrows[1] + ($Page_Width/2);
        
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
    $Firstinvoicerow = 0;

    Pageheader();
    Preparecolums(1);
    
    $I=1;
    $YPos= $Firstinvoicerow;
    
    $SQL="SELECT `date`,`invoiceno`,abs(`amount`) as amount
        FROM `PaymentsAllocation` where 
        `receiptjournal`='".$_GET['PVNUMBER']."'";
    
    $ResultIndex = DB_query($SQL,$db);
    while($rowsf = DB_fetch_array($ResultIndex)){
        $pdf->addText($collumrows[$I],$YPos,$FontSize,$rowsf['date']);
        $pdf->addText($collumrows[$I]+ 100,$YPos ,$FontSize,$rowsf['invoiceno']);
        $pdf->addText($collumrows[$I]+ 200,$YPos ,$FontSize, number_format($rowsf['amount']));
          
        $YPos -= $line_height;
        
        if($YPos < $lastrow){
          $YPos= $Firstinvoicerow;  $I++;
            if($I>2){
                 Preparecolums(1);  $I=1; $YPos= $Firstinvoicerow;
             }
       }
    }
    
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Remittance_' .$docno. '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    $SQL="Update `BankTransactions` set  `ChequePrinted`=1 where journal='".$_GET['PVNUMBER']."'";
    DB_query($SQL,$db);
    
}else{

 $Title = _('Write Cheques');

 include('includes/header.inc');
  
  echo '<p class="page_title_text">'
 . '<img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' .$Title.'" alt="" />' .$Title. '</p>';
   
  $approvals = array();
  $approvals[0]='Waiting';
  $approvals[1]='FAM Approved';
  $approvals[2]='CEO,FAM Approved';
  $approvals[3]='Cheque waiting';
  $approvals[9]='CanCelled';
  
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  
  
  $SQL="SELECT 
       `DocDate`
      ,`doctype`
      ,`DocumentNo`
      ,`itemcode`
      ,`journal`
      ,(0-`amount`) as amount
      ,`narrative`
      ,BankAccounts.currency
      ,BankAccounts.bankName
  FROM `BankTransactions` join BankAccounts on BankTransactions.bankcode=BankAccounts.accountcode 
   where doctype=53 or ChequePrinted=0 
  order by `DocDate` desc limit 50" ;
  
  echo '<table class="table table-bordered"><tr>'
          . '<th>Cheque No</th>'
          . '<th>Date</th>'
          . '<th>Account Name</th>'
          . '<th>Amount</th>'
          . '<th></th>'
          . '</tr>';
  
  $ResultIndex=DB_query($SQL,$db);
  while($row=DB_fetch_array($ResultIndex)){
      
      echo sprintf('<tr><td><a href="%s">Print Cheque for :%s</a></td>',$_SERVER['PHP_SELF'].'?PVNUMBER='.$row['journal'],$row['DocumentNo']);
      echo sprintf('<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td></tr>',
              ConvertSQLDate($row['DocDate'])
              ,$row['narrative'],
              $row['currency'].' '. number_format($row['amount']),
              $row['bankName'] );
      
  }
  
  echo '</table>';
  echo '</div></form>';
  
  
include('includes/footer.inc');

}


function Pageheader(){
    
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$docno,$customer ;
 Global $firstrowpos,$lastrow,$amountinwords,$amount ;
    
 $FontSize = 12;
 $PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
  $pdf->newPage();
}
    
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 2, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$topRow = $Page_Height-$Top_Margin-$FontSize * 2;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Right_Margin+5,$Page_Height-$Top_Margin-50, 0,40);
$YPos=$topRow-25;
$FontSize = 9;
 
$pdf->addText($Right_Margin,$YPos,$FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Right_Margin, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Right_Margin, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Right_Margin, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($Right_Margin, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($Right_Margin, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($Right_Margin, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

// Print company info
$XPos = 60;
$YPos = $YPos-70;
$FontSize=12;
$FontSize =10;

$adjustby = mb_strlen('Remittance For Cheque No:') * (15/5)  ;
$pdf->addText((($Page_Width-$adjustby)/2)-$Right_Margin-$Left_Margin,$YPos,15,'Remittance For Cheque No:'.$docno);

$pdf->addText($Right_Margin,$YPos -= ($line_height * 3),$FontSize,'Payee :'.$customer);

$pdf->addText($Right_Margin,$YPos -= ($line_height * 2),$FontSize,'Amount in words ');

$adjustby = mb_strlen('Amount in words ') * 5   ;
$pdf->addText($Right_Margin+$adjustby,$YPos -= ($line_height * 2),8,$amountinwords);

$pdf->addText($Right_Margin,$YPos -= ($line_height * 2),$FontSize,'Amount in figures :'.number_format($amount));

$firstrowpos =($YPos - ($line_height * 3))  ;
$lastrow     = $Bottom_Margin + ($line_height * 2);
$YPos -= (2 * $line_height);
}

function Preparecolums($columno){
    Global $Right_Margin,$YPos,$line_height,$FontSize,$firstrowpos, $collumrows;
    global $pdf,$Firstinvoicerow;
            
    $YPos = $firstrowpos;
    
    $pdf->addText($collumrows[$columno],$YPos,$FontSize,'INVOICE DATE');
    $pdf->line($collumrows[$columno],$YPos-$line_height,$collumrows[$columno]+ 75,$YPos-$line_height);

    $pdf->addText($collumrows[$columno]+ 100,$YPos ,$FontSize,'INVOICE No');
    $pdf->line($collumrows[$columno]+ 100,$YPos-$line_height,$collumrows[$columno]+ 160,$YPos-$line_height);

    $pdf->addText($collumrows[$columno]+ 200,$YPos ,$FontSize,'AMOUNT PAID');
    $pdf->line($collumrows[$columno]+ 200,$YPos-$line_height,$collumrows[$columno]+ 250,$YPos-$line_height);

    $Firstinvoicerow=$YPos-$line_height;

}

?>
