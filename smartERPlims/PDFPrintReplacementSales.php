<?php 
$PageSecurity=0;
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Stcok Replacement List');

if(isset($_GET['No'])){
    
$SQL="select 
       `SalesHeader`.`documentno` 
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,`SalesHeader`.`salespersoncode`
      ,`SalesHeader`.`status`
      ,`SalesHeader`.`userid`
      ,`salesrepsinfo`.`salesman`
      ,`salesrepsinfo`.`phone`
      ,`debtors`.email
      ,`debtors`.city
      ,`debtors`.postcode
      ,`debtors`.country
      ,`debtors`.phone
      ,`debtors`.contact
      from `SalesHeader` 
      join `debtors` on `SalesHeader`.`customercode`=`debtors`.`itemcode`
      join `SalesLine` on `SalesLine`.`documentno`=`SalesLine`.`documentno`
      left join `salesrepsinfo` on `salesrepsinfo`.`code`=`SalesHeader`.`salespersoncode`
      where `SalesHeader`.`documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
        
     $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName ="Sample Gate Pass";
    $pdf->addInfo('Title',_('STOCK ISSUE NOTE'));
    $pdf->addInfo('Subject',_('STOCK ISSUE NOTE'));
    $pdf->addInfo('Creator',_('SmartERP'));
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFSampleheader.inc');
     
     $SQL=Sprintf("SELECT 
        stockmaster.barcode ,
        SalesHeader.docdate ,
        SalesLine.description ,
        SalesLine.Quantity ,
        SalesLine.UOM,
        SalesLine.UnitPrice as Sp ,
        SalesLine.unitofmeasure as UOMDesc,
        SalesLine.documentno,
        SalesHeader.customercode ,
        SalesHeader.documenttype 
        FROM `SalesHeader` 
        join SalesLine on SalesHeader.documentno=SalesLine.documentno and SalesHeader.documenttype=SalesLine.documenttype  
        join stockmaster on stockmaster.itemcode=SalesLine.code 
        where SalesLine.documentno='%s' ",$_GET['No']);
     
     $FontSize = 10;
     $YPos = $firstrowpos-20;
      
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, number_format(abs($rows['Quantity']),0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos < $Bottom_Margin+100){
             $PageNumber++;
             include('includes/PDFSampleheader.inc');
              $YPos = $firstrowpos;
         }
                
     }
     
     $YPos = $Bottom_Margin+250;
     $LeftOvers = $pdf->addTextWrap(40,$YPos,150, $FontSize,_('ID No:'),'left');
     $LeftOvers = $pdf->addTextWrap(245,$YPos,150, $FontSize,_('Mobile:'),'left');
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos- $line_height,$Left_Margin,$YPos- $line_height);
     $YPos = $Bottom_Margin+180;
     $LeftOvers = $pdf->addTextWrap(40,$YPos - $line_height,100, $FontSize,_('Received By:'),'left');
     $LeftOvers = $pdf->addTextWrap(245,$YPos - $line_height,100, $FontSize,_('Sign:'),'left');
          
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Spoilage_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
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
      where `SalesHeader`.`documenttype`='19' 
      order by `SalesHeader`.`docdate` desc limit 50";
    $Result=DB_query($SQL,$db);
       
    Echo '<table class="table table-bordered"><tr>'
             . '<th>ReplaceMent <br />No</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Date</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
            . '</tr>';
  while($row=DB_fetch_array($Result)){
    echo '<tr>';
    echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),
            $row['documentno'],$row['documentno']);
    echo sprintf('<td>%s</td>',$row['customercode']);
    echo sprintf('<td>%s</td>',$row['customername']);
    echo sprintf('<td>%s</td>', ConvertSQLDate($row['docdate']));
    echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
    echo sprintf('<td>%s</td>',$row['userid']);
    echo '</tr>';
  }
        
    echo '</table><br />';
echo '</div></form>';

include('includes/footer.inc');

}   


?>