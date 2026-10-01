<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
$Title = _('Print Petty Cash Report');

if(isset($_POST['submit'])){
 $PaperSize='A4_Landscape';
 
 include('includes/PDFStarter.php');
 $PageNumber=0;
 $line_height=15;
 $FontSize=10;
 
 $pdf->addInfo('Title',_('Petty Cash'));
 $pdf->addInfo('Subject',_('Petty Cash'));
 $pdf->addInfo('Creator',_('SmartERP'));
 
        //"ID[51.0]`admin`"
          $POST_NO=str_replace(']','',$_POST['No']);
          $POSTA_RRYAY=explode('[',$POST_NO);
      
          $_GET['No']=$POSTA_RRYAY[1];
          $_GET['Cas']=$POSTA_RRYAY[2];
 
        $sql="SELECT 
            `date` ,
            `transtype`,
            `userid` ,
            `moneyin`,
            `moneyout` ,
            `shiftno`,
            `expensedetails`,
            `petteycashno`
       ,case transtype when 0 then
       (select customer from creditors where itemcode=`pettdoc`.account) 
       else 
       (select accdesc from acct where accno=`pettdoc`.account) end as Account
       ,(select sum(`moneyin`-`moneyout`) from `pettdoc`  
       where (`shiftno`<'".$_GET['No']."') and `userid`='".$_GET['Cas']."') as bbfwd
       FROM `pettdoc` where `shiftno`='".$_GET['No']."' and `userid`='".$_GET['Cas']."' "
                . " order by `shiftno`,`date`,`journal`  asc ";

$ResultIndex = DB_query($sql, $db);
$rows = DB_fetch_row($ResultIndex);
$Balance = pcopenbal($_GET['Cas'],$_GET['No']);
DB_free_result($ResultIndex);

PetteyHeader();
 $FontSize=10;
 $ResultIndex=DB_query($sql,$db);
    while($row=DB_fetch_array($ResultIndex)){
        $Balance += $row['moneyin'] - $row['moneyout'];
        $Account = $row['Account'];
        
         $pdf->addTextWrap(45,$YPos,100, $FontSize, ConvertSQLDate($row['date']),'left');
         $pdf->addTextWrap(130,$YPos,100, $FontSize,$row['petteycashno'],'left');
         $pdf->addTextWrap(180,$YPos,100, $FontSize,htmlspecialcharsLocal_decode($row['userid']),'left');
         $LeftOvers = $pdf->addTextWrap(250,$YPos,300, $FontSize,htmlspecialcharsLocal_decode($Account).' '. htmlspecialcharsLocal_decode($row['expensedetails']),'left');
         $pdf->addTextWrap(550,$YPos,100, $FontSize, number_format($row['moneyin'] ,2),'right');
         $pdf->addTextWrap(600,$YPos,100, $FontSize, number_format(abs($row['moneyout']),2),'right');
         $pdf->addTextWrap(650,$YPos,100, $FontSize, number_format($Balance,2),'right');
         
        While(strlen($LeftOvers) > 0){
             $YPos -= $line_height;
             $LeftOvers = $pdf->addTextWrap(250,$YPos,300, $FontSize,htmlspecialcharsLocal_decode($LeftOvers),'left');
          }
         
        $YPos -= $line_height;
        if($Bottom_Margin > ($YPos -$line_height)){
            $pdf->newPage();  
            PetteyHeader();
            $FontSize=10;
        }
     }
                         
   $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('PetteyCash'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
    
} else {
    
$Title = _('Petty Cash');
include('includes/header.inc');

$shiftDates[]=array();
$ResultIndex=DB_query("SELECT Distinct `date`,`shiftno` FROM `pettdoc` order by `date` asc", $db);
while($rows =DB_fetch_array($ResultIndex)){
    $id=trim($rows['shiftno']);
    $shiftDates[$id]=$rows;
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' . _('Petty Cash') .'" alt="" />' . ' ' . _('Pettey Cash') . '</p>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
echo '<div class="centre">'
       . '<table class="table table-bordered">'
       . '<thead><tr><th>Disbursement No</th><th>Cashier</th></tr></thead>';
        
echo '<tr><td >Select the Cashier</td>'
. '<td><select name="No">';
        $ResultIndex=DB_query("SELECT Distinct `userid`,`shiftno` FROM `pettdoc` order by `shiftno` desc", $db);
        while($rows =DB_fetch_array($ResultIndex)){
             $id=trim($rows['shiftno']);
            $datesofsift = $shiftDates[$id]['date'];
            echo sprintf('<option value=ID['.$rows['shiftno'].']['.$rows['userid'].']>Cashier %s:Shift %s Date start %s</option>',
                    trim($rows['userid']),trim($rows['shiftno']),ConvertSQLDate($datesofsift));
           
        }

echo '</select></td></tr>'
        . '<tr><td colspan="2"><input type="submit" name="submit" value="Print" /></td></tr>'
        . '</table>';
echo '</div>';
echo '</div></form>';
      
include('includes/footer.inc');
    
}



function PetteyHeader(){
    Global $YPos,$Page_Height,$Top_Margin,$pdf,$Left_Margin,$Right_Margin;
    Global $line_height,$Bottom_Margin,$FontSize,$XPos,$Balance,$PageNumber;
    
    $PageNumber++;
         $FontSize=14;
         
     
       $XPos=46;
       // Print company logo
       $pdf->addJpegFromFile($_SESSION['LogoFile'],20,$Page_Height-$Top_Margin-$FontSize * 3,0,45);
       // Print company info
       $XPos = 60;
       $YPos = ($Page_Height-$Top_Margin-$FontSize * 3);
       $FontSize =10;
       $H=$Page_Height-$Top_Margin;
       $pdf->addText(200, $H-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
       $pdf->addText(200, $H-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
       $pdf->addText(200, $H-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
       $pdf->addText(200, $H-39, $FontSize, _('Phone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
       $pdf->addText(200, $H-48, $FontSize, $_SESSION['CompanyRecord']['email']);
       $pdf->addText(200, $H-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);
          
       $YPos = ($H-$line_height-60);
       $pdf->addText(45,$YPos,10, _('Page').': '.$PageNumber);
       $YPos -= $line_height;
       $pdf->addText(250,$YPos,10,_('Petty Cash Report') );        
       $YPos -= $line_height * 2;
            
        $LeftOvers = $pdf->addTextWrap(45,$YPos,100, $FontSize,_('Date '),'left');
        $LeftOvers = $pdf->addTextWrap(130,$YPos,100, $FontSize,_('No.'),'left');
        $LeftOvers = $pdf->addTextWrap(180,$YPos,100, $FontSize,_('Cashier Name'),'left');
        $LeftOvers = $pdf->addTextWrap(250,$YPos,100, $FontSize,_('Narration'),'left');
        $LeftOvers = $pdf->addTextWrap(550,$YPos,100, $FontSize,_('Debit.'),'right');
        $LeftOvers = $pdf->addTextWrap(600,$YPos,100, $FontSize,_('Credit'),'right');
        $LeftOvers = $pdf->addTextWrap(650,$YPos,100, $FontSize,_('Balance.'),'right');
        
        $YPos -= $line_height;
        
        $LeftOvers = $pdf->addTextWrap(45,$YPos,100, $FontSize,_('Balance B/fwd'),'left');
        $LeftOvers = $pdf->addTextWrap(650,$YPos,100, $FontSize, number_format($Balance,2),'right');
        
         $YPos -= $line_height;
       
}



 function pcopenbal($cas,$shiftno){
    global $db;
    $sqlbal = "Select sum((`moneyin`-`moneyout`)+balance) FROM `pettdoc`"
            . " where `userid`='".$cas."' and `shiftno`<'".$shiftno."'";
    $ResultIndex=DB_query($sqlbal,$db);
    $rowk = DB_fetch_row($ResultIndex);
    $balance = $rowk[0];
    return $balance;
 }
?>