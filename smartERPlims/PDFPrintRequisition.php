<?php 

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Picking List');

if(isset($_GET['No'])){
    
$SQL="select 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid`
      ,`productionEmployee`.email
      ,`productionEmployee`.city
      ,`productionEmployee`.postcode
      ,`productionEmployee`.country
      ,`productionEmployee`.phone
      ,`productionEmployee`.`pinno` 
      from `SalesHeader` 
      join `SalesLine` on SalesLine.`documentno` =`SalesLine`.`documentno`
      join `productionEmployee` on `SalesHeader`.`customercode`=`productionEmployee`.`code`
      where `SalesHeader`.`documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
        
     $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName ="STOCK ISSUE NOTE";
    $pdf->addInfo('Title',_('STOCK ISSUE NOTE'));
    $pdf->addInfo('Subject',_('STOCK ISSUE NOTE'));
    $pdf->addInfo('Creator',_('SmartERP'));
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFrequisationstheader.inc');
     
     $SQL=Sprintf("SELECT 
         stockmaster.barcode ,
         SalesHeader.docdate ,
         SalesLine.description ,
         SalesLine.Quantity ,
         SalesLine.unitofmeasure ,
        (case `SalesLine`.`PartPerUnit` when '1' then (UnitPrice * `SalesLine`.`PartPerUnit`) else (UnitPrice) end) as Sp , 
        SalesLine.documentno,
        SalesHeader.customercode ,
        SalesHeader.documenttype 
        FROM SalesHeader 
        join SalesLine on SalesHeader.documentno=SalesLine.documentno and SalesHeader.documenttype=SalesLine.documenttype  
        join stockmaster on stockmaster.itemcode=SalesLine.code where SalesLine.documentno='%s' ",$_GET['No']);
     
     $FontSize = 12;
     $YPos = $firstrowpos-20;
     
     $docSerials = isset($_SESSION['DocumentSerials'][$_GET['No']])
         ? $_SESSION['DocumentSerials'][$_GET['No']]
         : (isset($_GET['serials']) ? explode(',', $_GET['serials']) : array());
     $serialIdx = 0;
     
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
          $serial = isset($docSerials[$serialIdx]) ? $docSerials[$serialIdx] : '';
          $serialIdx++;
          $LeftOvers = $pdf->addTextWrap(42, $YPos,80, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(125, $YPos,160, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(288, $YPos,65, $FontSize, $serial,'left');
          $LeftOvers = $pdf->addTextWrap(356, $YPos, 60, $FontSize, $rows['unitofmeasure'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 65, $FontSize, number_format(abs($rows['Quantity']),0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos < $Bottom_Margin+100){
             $PageNumber++;
             include('includes/PDFrequisationstheader.inc');
              $YPos = $firstrowpos;
         }
                
     }
     
     $YPos = $Bottom_Margin+250;
     $LeftOvers = $pdf->addTextWrap(40,$YPos,150, $FontSize,_('ID No: '.$myrow[15]),'left');
     $LeftOvers = $pdf->addTextWrap(245,$YPos,150, $FontSize,_('Mobile: '.$myrow[14]),'left');
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos- $line_height,$Left_Margin,$YPos- $line_height);
     $YPos = $Bottom_Margin+180;
     $LeftOvers = $pdf->addTextWrap(40,$YPos - $line_height,100, $FontSize,_('Received By:'),'left');
     $LeftOvers = $pdf->addTextWrap(245,$YPos - $line_height,100, $FontSize,_('Sign:'),'left');
          
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Requisation_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


$SQL="SELECT 
      `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid` 
      from `SalesHeader`
      join `SalesLine` on `SalesHeader`.documentno=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='40' and `SalesHeader`.`status`=2
      order by `SalesHeader`.`documentno` desc limit 50";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Pick List <br />No</th>'
             . '<th>Employee <br />ID</th>'
             . '<th>Employee<br /> Name</th>'
             . '<th>Date</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
          
            . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
         
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>
