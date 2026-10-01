<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Purchase Order');

if(isset($_GET['No'])){
    
$SQL="Select
       `AssetsHeader`.`documentno`
      ,`AssetsHeader`.`docdate`
      ,`AssetsHeader`.`oderdate`
      ,`AssetsHeader`.`duedate`
      ,`AssetsHeader`.`vendorcode`
      ,`AssetsHeader`.`vendorname`
      ,`AssetsHeader`.`currencycode`
      ,`AssetsHeader`.`status`
      ,`AssetsHeader`.`userid`
      ,`creditors`.email
      ,`creditors`.city
      ,`creditors`.postcode
      ,`creditors`.country
      ,`creditors`.phone
      ,`creditors`.contact
      ,`AssetsHeader`.`yourreference`
      ,`AssetsHeader`.`printed`
      from `AssetsHeader` join FixedAssetsLine on 
      `AssetsHeader`.`documentno`=FixedAssetsLine.`documentno` 
      join creditors on `AssetsHeader`.`vendorcode`=creditors.itemcode
      where `AssetsHeader`.`documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
    
    $PaperSize='A4_Landscape';
    include('includes/PDFStarter.php');
    $headerName="Purchase Order";
    
    $pdf->addInfo('Title',_('Purchase Invoice'));
    $pdf->addInfo('Subject',_('Purchase Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFpurchaseheader.inc');
     
     
     $SQL=Sprintf("SELECT 
      AssetsHeader.docdate
      ,FixedAssetsLine.`description` 
      ,FixedAssetsLine.Quantity,
       `FixedAssetsLine`.UOM,
       FixedAssetsLine.UnitPrice as Sp ,
       'UNIT' as UOMDesc
      ,AssetsHeader.`documentno`
      ,AssetsHeader.`vendorcode`
      ,FixedAssetsLine.invoiceamount
      ,AssetsHeader.`currencycode`
      ,FixedAssetsLine.vatamount
      ,FixedAssetsLine.invoiceamount-FixedAssetsLine.vatamount as netamt
      ,AssetsHeader.`documenttype`
      ,FixedAssetsLine.vatrate
  FROM `AssetsHeader` 
        join FixedAssetsLine on AssetsHeader.documentno=FixedAssetsLine.documentno 
        and AssetsHeader.documenttype=FixedAssetsLine.documenttype 
        where AssetsHeader.documentno='%s' ",$_GET['No']);
     
     $FontSize = 12;
     $YPos=$firstrowpos-20;
     $R1=0;
     $R2=0;
     $R3=0;
     
     
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $R1 +=$rows['vatamount'];
         $R2 +=$rows['netamt'];
         $R3 +=$rows['invoiceamount'];
         
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, $rows['Quantity'],'right');
          $LeftOvers = $pdf->addTextWrap(488, $YPos, 85, $FontSize, number_format($rows['Sp'],2),'right');
          $LeftOvers = $pdf->addTextWrap(590, $YPos, 55, $FontSize, number_format($rows['vatrate'],0),'right');
          $LeftOvers = $pdf->addTextWrap(650, $YPos, 70, $FontSize, number_format($rows['vatamount'],2),'right');
          $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$YPos,90,$FontSize,number_format($rows['netamt'],2),'right');

         $YPos -= $line_height * 2;
         if($YPos < $lastrow){
             $PageNumber++;
             include('includes/PDFpurchaseheader.inc');
              $YPos=$firstrowpos;
         }
               
     }
     
    $zYpos = $Bottom_Margin+80;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R2,2),'right');
    
    $zYpos -= $line_height *2.5;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R1,2),'right');
    
    $zYpos -= $line_height *2.5 ;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R3,2),'right');
     
     $YPos= $Bottom_Margin+50;
     $YPos-= $line_height * 2;
     
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Date:'),'right');
     $pdf->line(400,$YPos,500,$YPos);
     
     $YPos-=  $line_height * 2;
    
     $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,_('Authorised By :'),'left');
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Sign:'),'right');
                   
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Purchase_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    DB_query("Update `AssetsHeader` set printed=1 where AssetsHeader.documenttype=41 "
            . " and AssetsHeader.documentno='".$_GET['No']."'", $db);
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
$SQL="select 
       `AssetsHeader`.`documentno`
      ,`AssetsHeader`.`docdate`
      ,`AssetsHeader`.`oderdate`
      ,`AssetsHeader`.`duedate`
      ,`AssetsHeader`.`vendorcode`
      ,`AssetsHeader`.`vendorname`
      ,`AssetsHeader`.`currencycode`
      ,`AssetsHeader`.`status`
      ,`AssetsHeader`.`userid` 
      ,sum(FixedAssetsLine.`invoiceamount`) as OrderValue
      ,`AssetsHeader`.printed
      from `AssetsHeader` join FixedAssetsLine on 
      `AssetsHeader`.`documentno`=FixedAssetsLine.`documentno` 
      where  group by 
       `AssetsHeader`.`documentno`
      ,`AssetsHeader`.`docdate`
      ,`AssetsHeader`.`oderdate`
      ,`AssetsHeader`.`duedate`
      ,`AssetsHeader`.`vendorcode`
      ,`AssetsHeader`.`vendorname`
      ,`AssetsHeader`.`currencycode`
      ,`AssetsHeader`.`status`
      ,`AssetsHeader`.`userid`  
      ,`AssetsHeader`.printed
      order by `AssetsHeader`.`docdate`
      ,`AssetsHeader`.`documentno` desc 
      limit ". $_SESSION['DefaultDisplayRecordsMax'] ."
      ";
    $Result=DB_query($SQL,$db);
       
          Echo '<Table class="table table-bordered"><tr>'
             . '<th>Print<br />Order</th>'  
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
             . '<th>Supplier <br />ID</th>'
             . '<th>Supplier<br /> Name</th>'
             . '<th>Purchase <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Print<br />Status</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
             . '</tr>';
    
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
         
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
              
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['printed']==1?'Has Been Printed':'Not Yet Printed');
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
          
         
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>