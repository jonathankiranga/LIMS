<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
$Title = _('Print Price List');
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

$sqlstmt="SELECT 
    stockmaster.descrip,
    stockcode as itemcode,
    unit.descrip as Fulqty,
    quantity as partperunit,
    price as sellingprice,
    categorydescription as `Groups`
FROM PriceList 
left join stockmaster on stockmaster.itemcode = PriceList.stockcode 
left join unit on `stockmaster`.`units` = unit.code 
left join stockcategory on stockcategory.`categoryid` = stockmaster.`category` 
where (customerCode = '' or customerCode is null) Group by itemcode
order by `Groups` ASC";
      
 

if(isset($_POST['printpricelist'])){
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('ProductPriceList'));
    $pdf->addInfo('Subject',_('Pricelist'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 15;
    $ResultsP=DB_query($sqlstmt, $db);
    $TopRow = DB_fetch_row($ResultsP);
       
    Getheader();
    
     $FontSize = 10;
     $YPos = $firstrowpos;
     $Balance = 0;
     $NowID="xBegin";
      
     $ResultsP=DB_query($sqlstmt, $db);
     while($rows = DB_fetch_array($ResultsP)){
         
         if($rows['Groups']!=$NowID || $NowID=="xBegin"){
              if($NowID!="xBegin"){
                    $YPos -= $line_height *2;
              }
             
              $pdf->SetFont('Times','B',16);    
              $pdf->addTextWrap(50, $YPos+ $line_height,100,10,$rows['Groups'],'left');
               // set fill color
              $pdf->RoundRectangle($Left_Margin,($YPos + $line_height*2),$Page_Width-$Right_Margin-100,$line_height*2.5, 10, 10);
              $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Product'),'left');
              $pdf->addTextWrap(250, $YPos,100, $FontSize,_('Package'),'center');
              $pdf->addTextWrap(420, $YPos + $line_height,80, $FontSize,_('Package'),'right');
              $pdf->addTextWrap(420, $YPos,80, $FontSize,_('Price'),'right');
           
             $YPos -= $line_height*2 ;
             $NowID=$rows['Groups'];
             $pdf->SetFont('Times','',16);    
         }
          
         $UpperDescrip = ucwords($rows['descrip']);
         $LeftOvers = $pdf->addTextWrap(50, $YPos,200,10, $UpperDescrip,'left');
         
         if($rows['partperunit']==1){
            $pdf->addTextWrap(250, $YPos,150, 10, trim($rows['Fulqty']),'left');
         }else{
            $parone = number_format($rows['partperunit'],0);
            $pdf->addTextWrap(250, $YPos,150, 10,' 1 X '.$parone.' '. trim($rows['Fulqty']),'left');
         }
          $pdf->addTextWrap(410, $YPos,80, 10, number_format($rows['sellingprice'],2),'right');
          
          $YPos -= $line_height ;
         if($YPos < ($lastrow + ($line_height * 5))){
             Getheader();
             $YPos=$firstrowpos;
             $FontSize = 8;
              $NowID="xBegin";
         }
      }
      
    $pdf->SetY($YPos * -1);
    $Y = $pdf->GetY() ;

    $paymentterms = str_replace('<div>','<p>',html_entity_decode($_POST['paymentterms']));
    $paymentterms = str_replace('</div>','</p>',html_entity_decode($paymentterms));

    $footer='<p><B>Additional Information<b/></p><p>'.$paymentterms.'</p>';
    $LeftOvers = $pdf->writeHTMLCell(0,0,42,$Y,html_entity_decode($footer),0,0,false,true,'T', true);
 

  $pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('PriceList'). '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
  
} else {
    
  include('includes/header.inc');
   
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Price List') .'" alt="" />' . _('Price List') . '</p>';
  
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post" id="pricelist"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
  echo '<table style="height:200px"><tr><td valign="top"><table class="table table-bordered"><tr>';
    
  echo '<table class="table table-condensed"><tr><td><input type="submit" name="refresh" value="Refresh"/>'
     . '<input type="submit" name="printpricelist" value="Print PDF Price List"/></td>'
     . '</tr></table>';
 
   echo  '<table class="table-bordered table-condensed">
    <thead>'
        . '<TR>'
        . '<td class="ascending">Stock Code</td>'
        . '<td class="ascending">Inventory Name</td>'
        . '<td>Group</td>'
        . '<td>Package</td>'
        . '<td>Sellig Price</td>'
        . '<td>MarkUP</td>'
        . '</tr></thead>';
 
       $results=DB_query($sqlstmt, $db);
       $k=0;
       while($rows=DB_fetch_array($results)){
         echo '<tr>';
         echo sprintf('<td>%s</td>',trim($rows['itemcode']));
         echo '<td><b>'.$rows['descrip'].'</b></td>';
         echo '<td><b>'.$rows['Groups'].'</b></td>';
         echo '<td class="number"><i>1 X'.number_format($rows['partperunit'],0).' '.$rows['Fulqty'].'</i></td>';
         echo '<td class="number">'. number_format($rows['sellingprice'],2).'</td>';
          if($rows['sellingprice']>0){
             $markup=(($rows['sellingprice']-$rows['averagestock'])/$rows['sellingprice'])*100 ;
          }else{
            $markup=0;
          }
          echo '<td class="number">'. number_format($markup,2).'</td>';
          echo '</tr>';
            
       }        
   echo '</table>';
   
  echo '<table class="table">
    <tr style="outline: 1px solid"><td>Additional Information</td><td><textarea name="paymentterms" id="ParameterName"  style="width:100%; height:100%;">' . $_SESSION['paymentterms'] . '</textarea></td></tr></table>';
   
  echo '</div></form>';
  include('includes/footer.inc');
  
  
}
  
  
function Getheader(){
    Global $PageNumber,$pdf,$XPos,$YPos,$Page_Width,$Right_Margin,$FontSize,$firstrowpos,$lastrow,$Bottom_Margin;
    Global $Page_Height,$Top_Margin,$line_height,$TopRow;
// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
    $pdf->newPage();
}
// Print 'Quotation' title
// $XPos = 361; 
$XPos=46;

// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize * 2)-30;
$FontSize=12;

$pdf->SetTextColor(0,0,0);
$pdf->addTextWrap($Page_Width-$Right_Margin-200,$YPos-80,100, $FontSize, _('Page No:').$PageNumber, 'right');

$pdf->addTextWrap($Page_Width-$Right_Margin-300,$YPos-94,200, $FontSize, _('Printed:').Date('d/m/Y'), 'right');
$pdf->SetFillColor(0,0,0);
$pdf->SetTextColor(250,250,8);
$pdf->SetFont('times','B',16);  


$pdf->Cell($Page_Width-$Right_Margin-80,20, _('Customer Price List'),1,0,'C',1);
$pdf->SetTextColor(0,0,0);
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Page_Width-$Right_Margin-200,$YPos-60,0,80);
$pdf->SetFont('times','',16);    
$pdf->addText(50, $YPos,10,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText(50, $YPos-12, 10,_('Add1'). ': '. $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText(50, $YPos-21, 10,_('Add2'). ': '. $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText(50, $YPos-30, 10,_('Add3'). ': '. $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText(50, $YPos-39, 10,_('Tel'). ': '. _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ,' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText(50, $YPos-48, 10,_('Email'). ': '. $_SESSION['CompanyRecord']['email']);
$pdf->addText(50, $YPos-57, 10,_('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);
$pdf->addText(50, $YPos-70, 10, _('Currency') . ' - ' . $_SESSION['CompanyRecord']['currencydefault']);
$pdf->addText(50, $YPos-82, 10,_('VAT ').number_format($TopRow[6],0) .' % ');
$pdf->SetFont('times','',16);    
// Print 'Delivery To' info
$XPos = 46;
$YPos -= 120;
$FontSize=12;
 
$firstrowpos = ($YPos - $line_height) -20 ;
$lastrow = $Bottom_Margin + ($line_height * 2);
$FontSize=10;
$YPos -= (2 * $line_height);

}
    
?>