<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Order');

if(isset($_GET['printthis'])){
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Sales Loading'));
    $pdf->addInfo('Subject',_('Sales Loading'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
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
      from `SalesHeader` 
      join SalesLine on `SalesHeader`.`documentno`=SalesLine.`documentno` 
      join debtors on `SalesHeader`.`customercode`=debtors.itemcode
      where `SalesHeader`.`documentno`='".$_GET['printthis']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
     include('includes/PDFsalesloadingheader.inc');
     
     
     $FontSize = 14;
     $YPos=$firstrowpos;
     
     $SQL= Sprintf("SELECT 
       SalesHeader.docdate,SalesLine.`description`,SalesLine.Quantity,`SalesLine`.UOM,
       `SalesLine`.`unitofmeasure`,SalesHeader.`documentno`,SalesHeader.`customercode`,
       `SalesHeader`.`customername`,SalesLine.PartPerUnit 
    FROM `SalesHeader` 
    join SalesLine on SalesHeader.documentno=SalesLine.documentno 
    and SalesHeader.documenttype=SalesLine.documenttype 
    where SalesHeader.documentno ='%s' ", $_GET['printthis']);
    
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $ppu=(int)$rows['PartPerUnit'];
         $qty=(int)$rows['Quantity'];
         
            if($rows['PartPerUnit']>1){
              $units=$rows['unitofmeasure'].'(1x'.$ppu.' lts)';
            }else{
               $units=$rows['unitofmeasure'];
            }
           
            $LeftOversDesc = $pdf->addTextWrap(42,$YPos,200, $FontSize, $rows['description'],'left');
            $pdf->addTextWrap(300,$YPos,40,$FontSize,$units,'left');
            $pdf->addTextWrap(400,$YPos,85,$FontSize,$qty,'right');
            $GPos = $YPos;
            if(strlen($LeftOversDesc) > 0) { // If translated text is greater than 103, prints remainder
               $GPos -= $line_height;
               $LeftOversDesc= $pdf->addTextWrap(42,$GPos,200, $FontSize,$LeftOversDesc,'left');
	    }
            
            
         $YPos = max($GPos,$YPos);
         $YPos -= $line_height * 2;
         if($YPos < $lastrow){
             $PageNumber++;
             include('includes/PDFsalesloadingheader.inc');
             $YPos=$firstrowpos;
         }
        
        
     }
           
   $pdf->OutputD($_SESSION['DatabaseName'] . '_loading_' . $_GET['printthis'].'.pdf');
   $pdf->__destruct();
    
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

    if(isset($_GET['unprintedonly'])){
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
      ,sum(SalesLine.`invoiceamount`) as OrderValue
      from `SalesHeader` 
      join SalesLine on  `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='1' and (`SalesHeader`.`printed` is null or `SalesHeader`.`printed`=0)
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
      `SalesHeader`.`docdate` desc";
}else{
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
      ,sum(SalesLine.`invoiceamount`) as OrderValue
      from `SalesHeader` join SalesLine on 
      `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='1' or `SalesHeader`.`documenttype`='40' 
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
      `SalesHeader`.`docdate` desc  limit ". $_SESSION['DefaultDisplayRecordsMax'] ."";
}
    $Result=DB_query($SQL,$db);
       
    Echo '<input type="submit" name="print" value="Print selection">'
             . '<table class="table table-bordered"><tr>'
             . '<th>Order <br />No</th>' 
             . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
             . '<th>Sales <br /> Order <br />Due <br />Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Sales<br /> Person</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
            . '<th>Print For Loading</th>'
            . '</tr>';
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        echo sprintf('<td>%s</td>',$row['documentno']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td><a href="%s?ref=%s">%s</a></td>',
        'SalesLoading.php',$row['documentno'],"Select This Sales Order");
         echo '</tr>';
  }
        
    echo '</table>';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>