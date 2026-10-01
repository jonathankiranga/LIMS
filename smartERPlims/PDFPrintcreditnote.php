<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Invoice');

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
      ,`SalesHeader`.`printed`
      from `SalesHeader` join SalesLine on 
      `SalesHeader`.`documentno`=SalesLine.`documentno` 
      join debtors on `SalesHeader`.`customercode`=debtors.itemcode
      where `SalesHeader`.`documenttype`='13' 
      and `SalesHeader`.`documentno`='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Credit Note";
    
    $pdf->addInfo('Title',_('Sales Invoice'));
    $pdf->addInfo('Subject',_('Sales Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFreturnstheader.inc');
     
     
     $SQL=Sprintf("SELECT 
       stockmaster.barcode
       ,Container.`descrip` as containername
       ,SalesHeader.docdate
       ,SalesLine.`description` 
       ,SalesLine.Quantity,
       `SalesLine`.UOM,
       SalesLine.UnitPrice as Sp ,
       `SalesLine`.`unitofmeasure`  as UOMDesc
      ,SalesHeader.`documentno`
      ,SalesHeader.`customercode`
      ,SalesLine.invoiceamount
      ,SalesHeader.`currencycode`
      ,SalesLine.vatamount
      ,(SalesLine.invoiceamount - SalesLine.vatamount) as netamt
      ,SalesHeader.`documenttype`
      ,SalesLine.vatrate
      ,SalesLine.`totalchargedcontainers`
  FROM `SalesHeader` join SalesLine  
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        and `SalesHeader`.`documenttype`='13'
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
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,50, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(100, $YPos,145, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(245, $YPos,85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(345, $YPos,40, $FontSize, $rows['Quantity'],'right');
          $LeftOvers = $pdf->addTextWrap(435, $YPos,90, $FontSize, number_format($rows['netamt'],2),'right');
      
         $YPos -= $line_height ;
         if($YPos < ($lastrow )){
             include('includes/PDFreturnstheader.inc');
              $YPos = $firstrowpos-20;
         }
       
     }
     
    $zYpos = $lastrow + 80;
    $LeftOvers = $pdf->addTextWrap(435,$zYpos,90,$FontSize, number_format($R2,2),'right');
    $zYpos -= $line_height * 2.5;
    $LeftOvers = $pdf->addTextWrap(435,$zYpos,90,$FontSize, number_format($R1,2),'right');
    $zYpos -= $line_height * 2.5 ;
    $LeftOvers = $pdf->addTextWrap(435,$zYpos,90,$FontSize, number_format($R3,2),'right');

    $YPos = $lastrow + 50;
    $YPos -= $line_height * 2;
    $LeftOvers = $pdf->addTextWrap(145,$YPos,100, $FontSize,_('Date:'),'right');
    $YPos -= $line_height * 2;
    $LeftOvers = $pdf->addTextWrap(42,$YPos,100, $FontSize,_('Authorised By :'),'left');
    $LeftOvers = $pdf->addTextWrap(145,$YPos,100, $FontSize,_('Sign:'),'right');
       
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_CreditNote_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
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
      ,`SalesHeader`.`entryno`
      from `SalesHeader` join SalesLine on 
      `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='13' 
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
      ,`SalesHeader`.`userid` ,`SalesHeader`.`entryno`
      order by `SalesHeader`.`entryno` desc
      ";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Invoice <br />No</th>' 
             . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
             . '<th>Sales <br /> Order <br />Due <br />Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
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