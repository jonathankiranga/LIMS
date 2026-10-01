<?php
include('includes/session.inc');
$Title = _('Print Cutomer Receipts');


If(isset($_GET['ReceiptNo'])){
    
   
$SQL="Select 
       `docno`
      ,`receiptheader`.`date`
      ,`receiptheader`.`itemcode`
      ,`externalref`
      ,`narrative`
      ,`amount`
      ,`printed`
      ,`journal`
      ,`debtors`.customer
      ,`currencies`.hundredsname
      ,`receiptheader`.`currency`
       FROM `receiptheader` 
  join debtors on `receiptheader`.`itemcode`=`debtors`.`itemcode` 
  join currencies on `receiptheader`.`currency`=`currencies`.`currabrev`
  where journal='".$_GET['ReceiptNo']."'";
  

$ResultIndex = DB_query($SQL,$db);
$rows = DB_fetch_row($ResultIndex);

$docno = $rows[0] ;
$date = $rows[1] ;
$itemcode = $rows[2] ;
$externalref = $rows[3] ;
$narrative =htmlspecialcharsLocal_decode($rows[4]) ;
$amount = $rows[5] ;
$printed = $rows[6] ;
$journal = $rows[7] ;
$customer = htmlspecialcharsLocal_decode($rows[8]) ;
$hundredsname = $rows[9] ;
$currency = $rows[10] ;
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
        FROM `ReceiptsAllocation` where 
        `receiptjournal`='".$_GET['ReceiptNo']."'";
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
    
    

    $pdf->OutputD($_SESSION['DatabaseName'] . '_Receipt_' .$docno. '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    $SQL="Update `receiptheader` set`printed`=1 where journal='".$_GET['ReceiptNo']."'";
    DB_query($SQL,$db);
    
}else{
 
include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


if(isset($_GET['unprintedonly'])){
    $SQL="Select 
       `docno`
      ,`receiptheader`.`date`
      ,`receiptheader`.`itemcode`
      ,`externalref`
      ,`narrative`
      ,`amount`
      ,`printed`
      ,`journal`
      ,`debtors`.customer
      ,`currencies`.hundredsname
      ,`receiptheader`.`currency`
  FROM `receiptheader` 
  join debtors on `receiptheader`.`itemcode`=`debtors`.`itemcode` 
  join currencies on `receiptheader`.`currency`=`currencies`.`currabrev`
  where `receiptheader`.printed is null or printed=0
  order by `receiptheader`.`date` desc";
}else{

$SQL="Select 
       `docno`
      ,`receiptheader`.`date`
      ,`receiptheader`.`itemcode`
      ,`externalref`
      ,`narrative`
      ,`amount`
      ,`printed`
      ,`journal`
      ,`debtors`.customer
      ,`currencies`.hundredsname
      ,`receiptheader`.`currency`
  FROM `receiptheader` 
  join debtors on `receiptheader`.`itemcode`=`debtors`.`itemcode` 
  join currencies on `receiptheader`.`currency`=`currencies`.`currabrev`
  order by `receiptheader`.`date` desc  limit ". $_SESSION['DefaultDisplayRecordsMax'] ;
}
$Result=DB_query($SQL,$db);
       
    Echo '<Table class="table-bordered"><tr>'
             . '<th>Receipt <br />No</th>'
             . '<th>Date<br /> date</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Currency</th>'
             . '<th>Amount</th><th></th>'
            . '</tr>';
    
    
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?ReceiptNo=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['journal'],$row['docno']);
        echo sprintf('<td>%s</td>',is_null($row['date'])?'': ConvertSQLDate($row['date']));
        echo sprintf('<td>%s</td>',$row['customer']);
        echo sprintf('<td>%s</td>',$row['currency']);
        echo sprintf('<td>%s</td>',number_format($row['amount'],2));
        echo sprintf('<td>%s</td>',$row['printed']==1?'Printed':'Not yet printed');
        echo '</tr>';
  }
        
    echo '</table><br />';
	
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

$adjustby = mb_strlen('RECEIPT No') * (15/5)  ;
$pdf->addText((($Page_Width-$adjustby)/2)-$Right_Margin-$Left_Margin,$YPos,15,'RECEIPT No:'.$docno);

$pdf->addText($Right_Margin,$YPos -= ($line_height * 3),$FontSize,'We acknowledge receipt from :'.$customer);

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

    $pdf->addText($collumrows[$columno]+ 200,$YPos ,$FontSize,'PAID');
    $pdf->line($collumrows[$columno]+ 200,$YPos-$line_height,$collumrows[$columno]+ 250,$YPos-$line_height);

    $Firstinvoicerow=$YPos-$line_height;

}

?>