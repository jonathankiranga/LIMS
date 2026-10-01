<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');
if(isset($_GET['enterbillno'])){
    if(mb_strlen($_GET['enterbillno'])>0){
        $enterbillno=  $_GET['enterbillno'];
    }else{
        unset($_GET['enterbillno']);
    }
}


if(isset($enterbillno)){

$PaperSize='A5';
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

$SQL="SELECT 
 EnterbillHeaders.`date`,
 EnterbillHeaders.`documentno`,
 EnterbillHeaders.`narration`,
 EnterbillHeaders.`journalno`,
 EnterbillHeaders.VendorID,
 creditors.customer
 FROM `EnterbillHeaders` 
 join creditors on creditors.itemcode=EnterbillHeaders.VendorID 
 where `documentno`='".$enterbillno."'";
 $ResultIndex = DB_query($SQL,$db);
 $Rowheader = DB_fetch_row($ResultIndex);
 
$externalref = '' ; 
$docno = $Rowheader[1] ;
$date = ConvertSQLDate($Rowheader[0]) ;
$itemcode = $Rowheader[4] ;
$narrative = $Rowheader[2] ;
$journal = $Rowheader[3] ;
$customer = htmlspecialcharsLocal_decode($Rowheader[5]) ;
DB_free_result($ResultIndex);

Pageheader();
Preparecolums(1);

  $I=1;
  $YPos= $Firstinvoicerow;
  $Running =0;$VATRunning=0;
  
  $SQL="SELECT `documenttype`,`documentno`,`journalno`,`account`,`vatamount`,`grossamount`
  FROM `EnterbillsLines` where `documentno`='" .$enterbillno. "'";
  $ResultIndex = DB_query($SQL,$db);
 
  while($Row = DB_fetch_array($ResultIndex)){
        $accdesc = GetGL($Row['account'],$db);
        $Running +=$Row['grossamount'];
        $VATRunning +=$Row['vatamount'];
        
        if($Row['grossamount']>0){
        $pdf->addTextwrap($collumrows[$I],$YPos,200,$FontSize,$accdesc['accdesc']);
        $pdf->addText($collumrows[$I]+ 200,$YPos ,$FontSize, number_format($Row['vatamount'],2));
        $pdf->addText($collumrows[$I]+ 300,$YPos ,$FontSize, number_format($Row['grossamount'],2));
        
        }else{
         $pdf->addTextwrap($collumrows[$I],$YPos,200,$FontSize,$accdesc['accdesc']);
         $pdf->addText($collumrows[$I]+ 200,$YPos ,$FontSize, number_format($Row['vatamount'],2));
         $pdf->addText($collumrows[$I]+ 300,$YPos ,$FontSize, number_format($Row['grossamount']* -1,2));
         $pdf->addTextwrap($Page_Width-$Right_Margin-100,$Page_Height-$Top_Margin-$FontSize * 4,100,$FontSize,_('Debit Note'),'right',0,1);
        }
         
        $YPos -= $line_height;
        if($YPos < $lastrow){
          $YPos= $Firstinvoicerow;  
           
                 Pageheader();
                 Preparecolums(1); 
                 $I=1; 
                 $YPos= $Firstinvoicerow;
            
       }
    }
    
 $pdf->line(10,($lastrow + $line_height),$Page_Width-$Right_Margin+12,($lastrow + $line_height));
 $pdf->addText($collumrows[$I]+ 200,$lastrow ,$FontSize, number_format(abs($VATRunning),2));
 $pdf->addText($collumrows[$I]+ 300,$lastrow ,$FontSize, number_format(abs($Running),2));

 $pdf->OutputD($_SESSION['DatabaseName'] . '_EnterBILL_' .$docno. '_' . date('Y-m-d').'.pdf');
 $pdf->__destruct();
    
}else{
    
 $Title = _('Enter Bills Reprint');
 include('includes/header.inc');
  
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/money_delete.png" title="' .$Title.'" alt="" />' .$Title. '</p>';
echo '<form>
    <div class="container">
     <table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
        <thead>
            <tr>
                <th>Target</th>
                 <th>Search text</th>
                <th>Treat as regex</th>
                <th>Use smart search</th>
            </tr>
        </thead>
        <tbody>
            <tr id="filter_global">
                <td>Global search</td>
                <td align="center"><input type="text" class="global_filter" id="global_filter"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_regex"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_smart" checked="checked"></td>
    
            </tr>
            <tr id="filter_col1" data-column="0">
                <td>Column - Enter Bill No</td>
                <td align="center"><input type="text" class="column_filter" id="col0_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_smart" checked="checked"></td>
      
            </tr>
            <tr id="filter_col2" data-column="2">
                <td>Column - Vendor Name</td>
               <td align="center"><input type="text" class="column_filter" id="col2_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col2_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col2_smart" checked="checked"></td>
            </tr>
            
         </tbody>
    </table>
    <table class="register display" style="width:100%">
    <thead>'
        . '<TR>'
        . '<th>Enter Bill No</th>'
        . '<th>Date</th>'
        . '<th>Vendor Name</th>'
        . '<th>Narration</th>'
        . '</tr></thead><tbody>';

      $SQL='SELECT 
         EnterbillHeaders.`date`,`documenttype`,`documentno`,
         `narration`,`journalno`,VendorID,creditors.customer
         FROM `EnterbillHeaders` join creditors on creditors.itemcode=EnterbillHeaders.VendorID 
         order by EnterbillHeaders.`date` desc limit 1000';
      $ResultIndex=DB_query($SQL, $db);
      while($row=DB_fetch_array($ResultIndex)){
           echo sprintf('<tr>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '</tr>',   
            sprintf('<a href="%s?enterbillno=%s">Print :%s</a>',htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']) ,
            ConvertSQLDate($row['date']),
            $row['customer'],
            $row['narration']);
      }
     
  
  echo '</tbody><tfoot>
         <tr><th>Enter Bill No</th>'
        . '<th>Date</th>'
        . '<th>Vendor Name</th>'
        . '<th>Narration</th>
       </tr>
        </tfoot></table></div></form>';
   
include('includes/footer.inc');

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
    
function Pageheader(){
    
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$docno,$customer ;
 Global $firstrowpos,$lastrow,$narrative,$amount,$itemcode,$db ;
    
 $FontSize = 12;
 $PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
  $pdf->newPage();
}
    
$XPos=46;
$pdf->Rect(12,10,$Page_Width-$Left_Margin+12,$Page_Height-$Top_Margin+10, "D");
// le detail des totaux, demarre a 221 après le cadre des totaux
$pdf->SetLineWidth(0.1); 
// line($x1,$y1,$x2,$y2,$style=array())
$Middlepage = ($Page_Width/2);
$pdf->line($Middlepage,$Page_Height-$Top_Margin-155,$Middlepage,$Page_Height-$Top_Margin+20);
$topRow = $Page_Height-$Top_Margin-$FontSize * 2;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Right_Margin+5,$Page_Height-$Top_Margin-50, 0,40);
$YPos=$topRow-25;
$FontSize = 9;
 
$pdf->addText($Right_Margin,$YPos,$FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Right_Margin, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Right_Margin, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Right_Margin, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . '/' . $_SESSION['CompanyRecord']['regoffice4'] );
$pdf->addText($Right_Margin, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] );
$pdf->addText($Right_Margin, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($Right_Margin, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

// Print company info
$XPos = 60;
$FontSize=12;

$pdf->SetFillColor(255, 255, 0);
$pdf->addTextWrap($Page_Width-$Right_Margin-100,$Page_Height-$Top_Margin-$FontSize * 2,100,$FontSize, _('Page').': '.$PageNumber, 'right');
$pdf->addTextwrap($Page_Width-$Right_Margin-100,$Page_Height-$Top_Margin-$FontSize * 3,100,$FontSize,_('Bill No').':'.$docno,'right',0,1);

$YPos = $Page_Height-$Top_Margin-155;
$pdf->line(10,$YPos,$Page_Width-$Right_Margin+12,$YPos);
$pdf->addText($Right_Margin,$YPos -= $line_height ,$FontSize,'Vender Account Name:');

$sqlcreditors=DB_query("SELECT (`customer`+`curr_cod`),vatregno,`phone`,`email`,`city` FROM `creditors` where itemcode='".$itemcode."'", $db);
$vendorrow=DB_fetch_row($sqlcreditors);
$pdf->addText($Middlepage-50,$YPos ,$FontSize,htmlspecialcharsLocal_decode($vendorrow[0]));
$pdf->addText($Middlepage-50,$YPos-=$line_height,$FontSize,'VAT REG:'.$vendorrow[1]);
$pdf->addText($Middlepage-50,$YPos-=$line_height,$FontSize,$vendorrow[2]);
$pdf->addText($Middlepage-50,$YPos-=$line_height,$FontSize,$vendorrow[3]);
$pdf->addText($Middlepage-50,$YPos-=$line_height,$FontSize,$vendorrow[4]);

$pdf->addText($Right_Margin,$YPos -=($line_height * 3) ,$FontSize,'Transaction Details:');
$LeftOvers = $pdf->addTextwrap($Middlepage-50,$YPos,150,$FontSize,htmlspecialcharsLocal_decode($narrative));
$pdf->addTextwrap($Middlepage-50,$YPos-$line_height,150,$FontSize,htmlspecialcharsLocal_decode($LeftOvers));

$pdf->line(10,($YPos - ($line_height * 3)),$Page_Width-$Right_Margin+12,($YPos - ($line_height * 3)));
$pdf->line(10,($YPos - ($line_height * 5)),$Page_Width-$Right_Margin+12,($YPos - ($line_height * 5)));

$firstrowpos =($YPos - ($line_height * 5))  ;
$lastrow = $Bottom_Margin + ($line_height * 2);
$YPos -= (2 * $line_height);
}

function Preparecolums($columno){
    Global $Right_Margin,$YPos,$line_height,$FontSize,$firstrowpos;
    global $pdf,$Firstinvoicerow,$collumrows;
            
    $YPos = $firstrowpos+$line_height;
    $pdf->addText($collumrows[$columno],$YPos,$FontSize,'Description');
    $pdf->addText($collumrows[$columno]+ 200,$YPos ,$FontSize,'VAT Amount');
    $pdf->addText($collumrows[$columno]+ 300,$YPos ,$FontSize,' Amount');
    $Firstinvoicerow=$YPos-($line_height*3);

}
