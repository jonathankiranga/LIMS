<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
$Title = _('Print Payment Voucher');
include('includes/PaymentVoucherReverse.inc');

$DimArrayVote=array();
$DimArrayBudget=array();
$DimArraycommitted=array();
$DimArrayexpensed=array();
$DimArraybalance=array();
$DimArrayThisEntry=array();
$kesrow=array();

If(isset($_GET['jonal'])){
    $paymentvoucherno = $_GET['jonal'];
}

Function DoPrint($print){
    return substr($print,0);
    
}

Function ReversePage(){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin,$Page_Width;
    Global $line_height,$Bottom_Margin,$PageReverse,$FontSize;
    
   $pdf->newPage();
   
   
   $YPos = $Page_Height - $Top_Margin - 50;
    foreach($PageReverse[0] as $line){
       $print =htmlspecialcharsLocal_decode($line);
       $LeftOvers = $pdf->addTextWrap(10,$YPos,800,8,DoPrint($print),'left');
             
       $YPos -= ($line_height);
       if ($YPos - (2 * $line_height) < $Bottom_Margin){
            $pdf->newPage();
            $YPos = $Page_Height - $Top_Margin - 50;
         }
    }
    
    $YPos = $Page_Height - $Top_Margin - 50;
    foreach($PageReverse[1] as $line){
       $print =htmlspecialcharsLocal_decode($line);
       $LeftOvers = $pdf->addTextWrap(300,$YPos,500,8,DoPrint($print),'left');
              
       $YPos -= ($line_height);
       if ($YPos - (2 * $line_height) < $Bottom_Margin){
            $pdf->newPage();
            $YPos = $Page_Height - $Top_Margin - 50;
         }
    }
    
    
    
}


Function GetCurrency($journalno){
  global $db,$kesrow;
  
  $SQL="SELECT  
      `currencies`.`currabrev`,
      `currencies`.`hundredsname`,
      `paymentvoucherheader`.`currency`
  FROM `paymentvoucherheader` 
  join `currencies` on `paymentvoucherheader`.`currency`=`currencies`.`currabrev`
  where `paymentvoucherheader`.`journal`='".$journalno."'" ;
  
 
  $ResultIndex=DB_query($SQL,$db);
  $row =DB_fetch_row($ResultIndex);
  $kesrow[0]=$row[0];
  $kesrow[1]=$row[1];
  
 Return $kesrow;
}

Function PaymentHeader($journalno){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin;
    Global $line_height,$Bottom_Margin,$db,$FontSize,$Page_Width;
    
         
    $SQL="SELECT
       `docno`
      ,`paymentvoucherheader`.`date`
      ,`creditors`.`customer`
      ,`amount`
      ,`printed`
      ,`journal`
      ,`currency`
      ,`paymentvoucherheader`.`status`
  FROM `paymentvoucherheader` join `creditors`
       on `paymentvoucherheader`.`itemcode`=`creditors`.`itemcode` 
       where `paymentvoucherheader`.`journal`='".$journalno."'";
       
    $ResultIndex=DB_query($SQL,$db);
    $rows = DB_fetch_row($ResultIndex);
       // $XPos = 361; 
       $XPos=46;
       // Print company logo
       $pdf->addJpegFromFile($_SESSION['LogoFile'],50,$Page_Height-$Top_Margin-$FontSize * 4,0,45);
       // Print company info
       $XPos = 60;
       $YPos = ($Page_Height-$Top_Margin-$FontSize*3)-30;
       $FontSize=14;
       $pdf->addText($XPos, $YPos,8, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
       $FontSize =10;
       $H=$Page_Height-$Top_Margin;
       $pdf->addText(300, $H-12,8, $_SESSION['CompanyRecord']['regoffice1']);
       $pdf->addText(300, $H-21,8, $_SESSION['CompanyRecord']['regoffice2']);
       $pdf->addText(300, $H-30,8, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
       $pdf->addText(300, $H-39,8, _('Phone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
       $pdf->addText(300, $H-48,8, $_SESSION['CompanyRecord']['email']);
       $pdf->addText(300, $H-57,8,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);
       
       $adjustwidth =  mb_strlen(_('PAYMENT VOUCHER '). $rows[0]) * 21;
       $adjustwidth =  mb_strlen(_('PAYEE ').$rows[2]) * 21;
       $pdf->addText(($Page_Width- $adjustwidth/2)- $Right_Margin ,$YPos-12,8,_('PAYMENT VOUCHER ').$rows[0] );
       $pdf->addText(($Page_Width- $adjustwidth/2)- $Right_Margin ,$YPos-21,8,_('PAYEE ').$rows[2]);
    
       $YPos -= 35;
}

Function PageBody(){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin;
    Global $line_height,$Bottom_Margin,$db,$DimArray,$FontSize,$kesrow;
    Global $DimArrayVote,$DimArrayBudget,$DimArraycommitted;
    Global $DimArrayexpensed,$DimArraybalance,$DimArrayThisEntry;
    Global $VBCERTIFICATE,$accountantvbc,$authorization,$arrayvotehead;
    
   $VOTEBOOKtop=$YPos;
   foreach($VBCERTIFICATE as $value){
      $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,300,8,$value,'left');
    }
    
   $FontSize=8;
   $YPos -=$line_height;
  
   
    $x=0;
    $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,50, $FontSize,_('Budget Name:'),'left');
    foreach ($DimArrayVote as $key => $value) {
          $LeftOvers = $pdf->addTextWrap(80+$x,$YPos ,100 , $FontSize,$value,'right');
          $x +=80;
      }
     
       $x=0;
    $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,50, $FontSize,_('Budget Amount:'),'left');
    foreach ($DimArrayBudget as $key => $value) {
        $LeftOvers = $pdf->addTextWrap(80+$x,$YPos,100, $FontSize, number_format($value,2),'right');
          $x +=80;
     } 
     
      $x=0;
    $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,50, $FontSize,_('Committed Amount:'),'left');
    foreach ($DimArraycommitted as $key => $value) {
        $LeftOvers = $pdf->addTextWrap(80+$x,$YPos,100, $FontSize, number_format($value,2),'right');
          $x +=80;
     }
    
      $x=0;
    $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,50, $FontSize,_('Expensed Amount:'),'left');
    foreach ($DimArrayexpensed as $key => $value) {
        $LeftOvers = $pdf->addTextWrap(80+$x,$YPos,100, $FontSize, number_format($value,2),'right');
          $x +=80;
     }
    
      $x=0;
    $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,50, $FontSize,_('Balance:'),'left');
    foreach ($DimArraybalance as $key => $value) {
        $LeftOvers = $pdf->addTextWrap(80+$x,$YPos,100, $FontSize, number_format($value,2),'right');
          $x +=80;
     }
    
      $x=0;$TOTAL=0;
    $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,50, $FontSize,_('This Entry:'),'left');
    foreach ($DimArrayThisEntry as $key => $value) {
        $TOTAL += $value;
        $LeftOvers = $pdf->addTextWrap(80+$x,$YPos,100, $FontSize, number_format($value,2),'right');
          $x +=80;
     }
   
     $YPos -=$line_height;
     foreach ($accountantvbc as $value) {
              $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,500, $FontSize,$value,'left');
        }
             
    $YPos -=$line_height;
    $LeftOvers = $pdf->line(45,$YPos,300,$YPos);
    
    $YPos -=$line_height;
     foreach ($authorization as $value) {
              $LeftOvers = $pdf->addTextWrap(350,$VOTEBOOKtop -=$line_height,500, $FontSize,$value,'left');
        }  
     
    Votebooksummary($VOTEBOOKtop);
               
}

function Votebooksummary($VOTEBOOKtop){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin;
    Global $line_height,$Bottom_Margin,$DimArray,$FontSize;
    
    $VOTEBOOKtop -=$line_height * 2;
    $topline=$VOTEBOOKtop;
    $lastline= $topline - $line_height * 6;
    $LeftOvers = $pdf->line(45,$VOTEBOOKtop,550,$VOTEBOOKtop);
    $VOTEBOOKtop -=$line_height ;
    
    $LeftOvers = $pdf->addTextWrap(45,$VOTEBOOKtop,100, $FontSize,_('VOTE'),'left');
    $LeftOvers = $pdf->addTextWrap(245,$VOTEBOOKtop,100, $FontSize,_('HEAD/SUBHEAD'),'left');
    $LeftOvers = $pdf->addTextWrap(500,$VOTEBOOKtop,100, $FontSize,_('ITEM'),'left');
    $LeftOvers = $pdf->line(45,$VOTEBOOKtop-2,550,$VOTEBOOKtop-2);
    
    $VOTEBOOKtop -=$line_height ;
    $secondline= $VOTEBOOKtop ;
    $LeftOvers = $pdf->addTextWrap(200,$VOTEBOOKtop,100, $FontSize,_('Dept. Vch.'),'left');
    $LeftOvers = $pdf->addTextWrap(410,$VOTEBOOKtop,100, $FontSize,_('CASH BOOK'),'left');
    $LeftOvers = $pdf->addTextWrap(500,$VOTEBOOKtop,100, $FontSize,_('AMOUNT.'),'left');
     $LeftOvers = $pdf->line(200,$secondline,550,$secondline);
   
    $VOTEBOOKtop -=$line_height ;
    $LeftOvers = $pdf->addTextWrap(45, $VOTEBOOKtop,100, $FontSize,_('AIE No'),'left');
    $LeftOvers = $pdf->addTextWrap(100,$VOTEBOOKtop,100, $FontSize,_('Account No.'),'left');
    $LeftOvers = $pdf->addTextWrap(200,$VOTEBOOKtop,100, $FontSize,_('No.'),'left');
    $LeftOvers = $pdf->addTextWrap(300,$VOTEBOOKtop,100, $FontSize,_('Station'),'left');
    $LeftOvers = $pdf->addTextWrap(400,$VOTEBOOKtop,100, $FontSize,_('Vch. No.'),'left');
    $LeftOvers = $pdf->addTextWrap(450,$VOTEBOOKtop,100, $FontSize,_('Date.'),'left');
    $LeftOvers = $pdf->addTextWrap(500,$VOTEBOOKtop,100, $FontSize,_('KES.'),'left');
    $LeftOvers = $pdf->line(45,$VOTEBOOKtop,550,$VOTEBOOKtop);
    
    //fiistrow
    $LeftOvers = $pdf->line(45,$topline,45,$lastline);
    $LeftOvers = $pdf->line(200,$topline,200,$lastline);
    $LeftOvers = $pdf->line(500,$topline,500,$lastline);
    $LeftOvers = $pdf->line(550,$topline,550,$lastline);
    $LeftOvers = $pdf->line(45,$lastline,550,$lastline);
    $LeftOvers = $pdf->line(100,$topline,100,$lastline);
    
    $LeftOvers = $pdf->line(300,$secondline,300,$lastline);
    $LeftOvers = $pdf->line(400,$secondline,400,$lastline);
    $LeftOvers = $pdf->line(450,$secondline,450,$lastline);
    
}

Function Paymentlist($journalno){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin;
    Global $line_height,$Bottom_Margin,$db,$DimArray,$FontSize;
    Global $DimArrayVote,$DimArrayBudget,$DimArraycommitted;
    Global $DimArrayexpensed,$DimArraybalance,$DimArrayThisEntry;
    global $Examination,$Internalaudit,$kesrow;
       
     
    $kesrow = GetCurrency($journalno);
   
     $YPos -=$line_height;
     $LeftOvers = $pdf->line(45,$YPos,545,$YPos);
     $YPos -=$line_height;
     $LeftOvers = $pdf->addTextWrap(45,  $YPos,100, $FontSize,_('Inv No'),'left');
     $LeftOvers = $pdf->addTextWrap(145, $YPos,100, $FontSize,_('Date'),'left');
     $LeftOvers = $pdf->addTextWrap(245, $YPos,100, $FontSize,_('Account Name'),'left');
     $LeftOvers = $pdf->addTextWrap(345, $YPos,100, $FontSize,_('Paid Amount'),'right');
     $YPos -=$line_height;
     $LeftOvers = $pdf->line(45,$YPos,545,$YPos);
    
    
    $SQL="SELECT
       `paymentvoucherheader`.`date`
      ,`creditors`.`customer`
      ,`externalref`
      ,`paymentvoucherheader`.`narrative`
      ,`paymentvoucherheader`.`itemcode`
      ,`paymentvoucherheader`.`journal`
      ,`currency`
      ,`paymentvoucherline`.`docno` 
      ,`paymentvoucherline`.`amount`
      ,`Dimensions`.`Dimension`
      ,`paymentvoucherline`.`Budget`
      ,`paymentvoucherline`.`Committed`
      ,`paymentvoucherline`.`Expensed`
      ,`paymentvoucherline`.`Balance`
  FROM `paymentvoucherheader` 
  join `creditors` on `paymentvoucherheader`.`itemcode`=`creditors`.`itemcode`
  join paymentvoucherline on  paymentvoucherline.`journal`=`paymentvoucherheader`.`journal`
  left join `Dimensions` on `paymentvoucherline`.`Dimension_1`=`Dimensions`.`code`
      where `paymentvoucherheader`.`journal`='".$journalno."'";
       
  $total=0;  $i=0;
  $ResultIndex=DB_query($SQL,$db);
  while($rows=DB_fetch_array($ResultIndex)){
   
    $DimArrayVote[$rows['Dimension_1']] = $rows['Dimension'];
    $DimArrayBudget[$rows['Dimension_1']] += $rows['Budget'];
    $DimArraycommitted[$rows['Dimension_1']] += $rows['Committed'];
    $DimArrayexpensed[$rows['Dimension_1']] += $rows['Expensed'];
    $DimArraybalance[$rows['Dimension_1']] += $rows['Balance'];
    $DimArrayThisEntry[$rows['Dimension_1']] += $rows['amount'];;
     
    $YPos -= $line_height;
    
     $LeftOvers = $pdf->addTextWrap(45,  $YPos,100, $FontSize,$rows['docno'],'left');
     $LeftOvers = $pdf->addTextWrap(145, $YPos,100, $FontSize,$rows['date'],'left');
     $LeftOvers = $pdf->addTextWrap(245, $YPos,100, $FontSize,$rows['customer'],'left');
     $LeftOvers = $pdf->addTextWrap(345, $YPos,100, $FontSize, number_format($rows['amount'],0),'right');
     $total += $rows['amount']; $i++;
  }
  
  $YPos -= $line_height;
  if($i<5){
    $YPos -= $line_height * (5-$i);
  }
  
  $LeftOvers = $pdf->line(45,$YPos,545,$YPos);
  $YPos -= $line_height;
  $LeftOvers = $pdf->addTextWrap(45,  $YPos,100, $FontSize,_('Total'),'left');
  $LeftOvers = $pdf->addTextWrap(345, $YPos,100, $FontSize, number_format($total,0),'right');
  $YPos -=$line_height;
  $LeftOvers = $pdf->addTextWrap(45,$YPos,60, 8,_('Amount In Words'),'left');
  $LeftOvers = $pdf->addTextWrap(120,$YPos,200,8,Num2Wrd($total,$kesrow[0],$kesrow[1]),'left');

  $YPos -=$line_height;
  $LeftOvers = $pdf->line(45,$YPos,545,$YPos);
  $Bookmark = $YPos;
  
  foreach($Examination as $value){
    $LeftOvers = $pdf->addTextWrap(45,$YPos -=$line_height,300,8,$value,'left');
  }
    
  foreach($Internalaudit as $value){
    $LeftOvers = $pdf->addTextWrap(345,$Bookmark -=$line_height,300,8,$value,'left');
  }
    
  $YPos -= $line_height;
  $LeftOvers = $pdf->line(45,$YPos,545,$YPos);
  
}

If(isset($paymentvoucherno)){
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Financial Reports'));
    $pdf->addInfo('Subject',_('Payment Voucher'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
    
    PaymentHeader($paymentvoucherno);
    Paymentlist($paymentvoucherno); 
    PageBody();
    ReversePage();
       
   $pdf->OutputD($_SESSION['DatabaseName']. '_'._('PayMentVoucher').'_'. date('Y-m-d').'.pdf');
   $pdf->__destruct();
   
   DB_query(sprintf("Update paymentvoucherheader set `printed`=1 where `journal`='%s'",$paymentvoucherno) , $db);
    
} else { 
    
 include('includes/header.inc');
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' . _('Payment Voucher Status') .'" alt="" />' . _('Payment Voucher Status ') . '</p>';
   
  $approvals = array();
  $approvals[0]='Waiting';
  $approvals[1]='FAM Approved';
  $approvals[2]='CEO,FAM Approved';
  $approvals[3]='Cheque waiting';
  $approvals[9]='CanCelled';
  
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
  echo '<table class="table table-bordered"><tr>'
          . '<th>Payment Voucher No</th>'
          . '<th>Date</th>'
          . '<th>Account Name</th>'
          . '<th>Amount</th>'
          . '<th>Approval Status</th>'
          . '<th>Print Status</th>'
          . '<th>Comments</th>'
          . '<th></th>'
          . '</tr>';
  
  $SQL="SELECT
       `docno`
      ,`paymentvoucherheader`.`date`
      ,`creditors`.`customer`
      ,`externalref`
      ,`narrative`
      ,`amount`
      ,`printed`
      ,`journal`
      ,`currency`
      ,`paymentvoucherheader`.`status`
      ,`paymentvoucherheader`.Comments
  FROM `paymentvoucherheader` join `creditors`
  on `paymentvoucherheader`.`itemcode`=`creditors`.`itemcode`
  where (`paymentvoucherheader`.`status`=0)
  or (`paymentvoucherheader`.`status`=1)
  or (`paymentvoucherheader`.`status`=2)
  order by date desc" ;
  
  $ResultIndex=DB_query($SQL,$db);
  while($row=DB_fetch_array($ResultIndex)){
      
      echo sprintf('<tr><td><a href="%s">%s</a></td>',$_SERVER['PHP_SELF'].'?jonal='.$row['journal'],$row['docno']);
      echo sprintf('<td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>', ConvertSQLDate($row['date'])
              ,$row['customer'],
              $row['currency'].' '. number_format($row['amount']),
        $approvals[$row['status']],
              $row['printed']==1?'Printed':'',
              $row['Comments'] );
      if($row['status']==0){
         echo sprintf('<td><a href="%s">EDIT</a></td>','PaymentVoucher.php?edit='.$row['journal'],$row['docno']);
      }else{
         echo '<td>EDIT</td>';
      }
      echo  '</tr>';
   
  }
  
  echo '</table>';
  echo '</div></form>';
  
  
include('includes/footer.inc');
}
?>