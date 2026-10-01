<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Order');

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
      ,`debtors`.email
      ,`debtors`.city
      ,`debtors`.postcode
      ,`debtors`.country
      ,`debtors`.phone
      ,`debtors`.contact
      ,`SalesHeader`.`yourreference`
      from `SalesHeader` join SalesLine on 
      `SalesHeader`.`documentno`=SalesLine.`documentno` 
      join debtors on `SalesHeader`.`customercode`=debtors.itemcode
      where `SalesHeader`.`documenttype`='32' 
      and `SalesHeader`.`documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
    
    $PaperSize='A4_Landscape';
    include('includes/PDFStarter.php');
    $headerName="JOB ORDER";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFserviceorderheader.inc');
     
     
     $SQL=Sprintf("SELECT 
       stockmaster.barcode
       ,Container.`descrip` as containername
      ,SalesHeader.docdate
      ,SalesLine.`description` 
      ,SalesLine.Quantity,
       `SalesLine`.UOM,
       SalesLine.UnitPrice as Sp ,
	  (case `SalesLine`.UOM when 'fulqty' 
          then (select rtrim(`unit`.`descrip`) from `unit` join stockmaster on stockmaster.units=`unit`.code  and stockmaster.itemcode=SalesLine.code) 
	  else (select rtrim(`unit`.`descrip`) from `unit` join stockmaster on `stockmaster`.`units`=`unit`.code  and stockmaster.itemcode=SalesLine.code)
	  end) as UOMDesc
      ,SalesHeader.`documentno`
      ,SalesHeader.`customercode`
      ,SalesLine.invoiceamount
      ,SalesHeader.`currencycode`
      ,SalesLine.vatamount
      ,SalesLine.invoiceamount-SalesLine.vatamount as netamt
      ,SalesHeader.`documenttype`
      ,SalesLine.vatrate
      ,SalesLine.`totalchargedcontainers`
  FROM `SalesHeader` join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype and SalesHeader.documenttype=32
        join stockmaster on stockmaster.itemcode=SalesLine.code
        left join stockmaster Container on Container.itemcode=SalesLine.containercode
        where SalesHeader.documentno='%s' ",$_GET['No']);
     
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
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, $rows['Quantity'],'right');
          $LeftOvers = $pdf->addTextWrap(488, $YPos, 85, $FontSize, number_format($rows['Sp'],2),'right');
          $LeftOvers = $pdf->addTextWrap(590, $YPos, 55, $FontSize, number_format($rows['vatrate'],0),'right');
          $LeftOvers = $pdf->addTextWrap(650, $YPos, 70, $FontSize, number_format($rows['vatamount'],2),'right');
          $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$YPos,90,$FontSize,number_format($rows['netamt'],2),'right');
        
         $YPos -= $line_height * 2;
         if($YPos< $lastrow){
             $PageNumber++;
             include('includes/PDFserviceorderheader.inc');
              $YPos=$firstrowpos;
         }
        
        
     }
            
     $zYpos = $Bottom_Margin+80;
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R2,2),'right');
        $zYpos -=$line_height *2.5;
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R1,2),'right');
        $zYpos -=$line_height *2.5 ;
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R3,2),'right');
               
    $pdf->OutputD($_SESSION['DatabaseName'] . '_SORDER_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
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
      ,`SalesHeader`.`userid` ,
      sum(SalesLine.`invoiceamount`) as OrderValue
      from `SalesHeader` join SalesLine on 
      `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='32' 
       group by 
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
      order by 
       `SalesHeader`.`docdate` desc
       limit ". $_SESSION['DefaultDisplayRecordsMax'] ."
      ";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Order <br />No</th>' 
             . '<th>Job <br /> Card <br /> Document<br /> date</th>'
             . '<th>Job <br /> card <br />Due <br />Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Job<br />Card<br />Value</th>'
             . '<th>Currency</th>'
             . '<th>Sales<br /> Person</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
          
            . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
              
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
          
         
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>