<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Quotation');

if(isset($_GET['No'])){
    
$SQL="select 
       `SalesHeader`.`documentno`
      ,`SalesHeader`.`docdate`
      ,`SalesHeader`.`oderdate`
      ,`SalesHeader`.`duedate`
      ,`SalesHeader`.`customercode`
      ,`SalesHeader`.`customername`
      ,`SalesHeader`.`currencycode`
      ,rtrim(`salesrepsinfo`.`salesman`)+' ,Tel:'+IFNULL(`salesrepsinfo`.`phone`,'')
      ,rtrim(`salesrepsinfo`.`email`)
      ,`SalesHeader`.`userid`
      ,`debtors`.email
      ,`debtors`.city
      ,`debtors`.postcode
      ,`debtors`.country
      ,`debtors`.phone
      ,`debtors`.contact
      ,`SalesHeader`.`yourreference`
      ,SalesHeader.`locationcode` as BankCode
       ,`SalesHeader`.`picture`
      from `SalesHeader` join SalesLine on 
      `SalesHeader`.`documentno`=SalesLine.`documentno` 
      join debtors on `SalesHeader`.`customercode`=debtors.itemcode
      left join `salesrepsinfo` on `salesrepsinfo`.code=`SalesHeader`.`salespersoncode`
      where `SalesHeader`.`documenttype`='1' 
      and `SalesHeader`.`documentno`='".$_GET['No']."'";

    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $Bankcode = $myrow[17];
    $urlString= $myrow[18];
 
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Proforma";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 10;
        
     
    
     $SQL=Sprintf("SELECT 
       SalesLine.code as barcode
      ,SalesHeader.docdate
      ,SalesLine.`description` 
      ,SalesLine.Quantity
      ,`SalesLine`.`unitofmeasure`
      ,SalesLine.UnitPrice as Sp 
      ,SalesHeader.`documentno`
      ,SalesHeader.`customercode`
      ,SalesLine.invoiceamount
      ,SalesHeader.`currencycode`
      ,SalesLine.vatamount
      ,(SalesLine.invoiceamount-SalesLine.vatamount) as netamt
      ,SalesHeader.`documenttype`
      ,SalesLine.vatrate
      ,SalesLine.`totalchargedcontainers`
      ,`SalesLine`.`partperunit`
  FROM `SalesHeader` join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        and SalesHeader.documenttype=1  
        where SalesHeader.documentno='%s' ",$_GET['No']);
     
    
     $R1=0; $R2=0; $R3=0;
     $Total_Rows=1;
     $FontSize=9;
     $Row_Count=0;
     
     $ResultIndex=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
        $Total_Rows ++;
      }
     DB_free_result($ResultIndex);
     
    
     include('includes/PDFQuoteProfheader.inc');
     $YPos = $firstrowpos-6;
     
     $Results = DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $Row_Count++;
         $R1 +=$rows['vatamount'];
         $R2 +=$rows['netamt'];
         $R3 +=$rows['invoiceamount'];
         $ppu=(int)$rows['partperunit'];
         $vatrate=(int) $rows['vatrate'];
        
         if($ppu>1){
           $units=$rows['unitofmeasure'].'(1x'.$ppu.')';
         }else{
            $units=$rows['unitofmeasure'];
         }
           $qty=(int)$rows['Quantity'];
           
           $pdf->addTextWrap($Left_Margin, $YPos,40,9,$qty,'right');
           $LeftOvers = $pdf->addTextWrap(90, $YPos,100,9, $rows['description'],'left');
           
           $pdf->addTextWrap(195, $YPos,85,9, $units,'right');
           $pdf->addTextWrap(250, $YPos,85,9, number_format($rows['Sp'],2),'right');
          
           $pdf->addTextWrap(310, $YPos,55,9,$vatrate,'right');
           $pdf->addTextWrap(400, $YPos,70,9,number_format($rows['vatamount'],2),'right');
           $pdf->addTextWrap(490, $YPos,90,9,number_format($rows['netamt'],2),'right');
           
            if (strlen($LeftOvers) > 0) { // If translated text is greater than 103, prints remainder
		$YPos-=$line_height;
                $LeftOvers = $pdf->addTextWrap(90,$YPos,100,9, $LeftOvers,'left');
	    }
            if (strlen($LeftOvers) > 0) { // If translated text is greater than 103, prints remainder
                $YPos-=$line_height;
		$LeftOvers = $pdf->addTextWrap(90,$YPos,100,9, $LeftOvers,'left');
            }
            if (strlen($LeftOvers) > 0) { // If translated text is greater than 103, prints remainder
		$YPos-=$line_height;
                $LeftOvers = $pdf->addTextWrap(90,$YPos,100,9, $LeftOvers,'left');
            }
         
          $YPos -= $line_height ;
          if(($YPos < $lastrow) || ($Row_Count==$Total_Rows)){
            $zYpos = $lastrow-$line_height*1.2;
            $LeftOvers = $pdf->addTextWrap(490,$zYpos,90,$FontSize, number_format($R2,2),'right');//620
            $zYpos -=($line_height * 1.2);
            $LeftOvers = $pdf->addTextWrap(490,$zYpos,90,$FontSize, number_format($R1,2),'right');//630
            $zYpos -=($line_height * 1.2) ;
            $LeftOvers = $pdf->addTextWrap(490,$zYpos,90,$FontSize, number_format($R3,2),'right');//30
         }
         
       if(($YPos < $lastrow ) && ($Row_Count<$Total_Rows)){  
            $PageNumber++;
            include('includes/PDFQuoteheader.inc');
            $YPos = $firstrowpos-6;
         }
   }
   
  $Y=$SummaryYpos;
  
  $GetbankSql="SELECT `bankName`,`currency`,`AccountNo`,`BranchCode`,
      `BranchName`,`AcctName`,`bankCode`,`swiftcode`
  FROM BankAccounts where `accountcode`='".$Bankcode."'";
  $Results = DB_query($GetbankSql,$db);
  $BankRow = DB_fetch_row($Results);
  
   $Y-= ($line_height*5);
   $pdf->addTextWrap(50, $Y,85,$FontSize, "Bank Acount Name",'left');
   $pdf->addTextWrap(130,$Y,200,$FontSize, $BankRow[5],'right');
   $Y -= ($line_height*1.2);
   $pdf->addTextWrap(50,$Y,85,$FontSize, "Bank Acount No",'left');
   $pdf->addTextWrap(130,$Y,200,$FontSize, $BankRow[2],'right');
   $Y -= ($line_height*1.2);
   $pdf->addTextWrap(50,$Y,85,$FontSize, "Bank ",'left');
   $pdf->addTextWrap(130,$Y,200,$FontSize, $BankRow[0],'right');
   $Y -= ($line_height*1.2);
   $pdf->addTextWrap(50,$Y,85,$FontSize, "Bank Code",'left');
   $pdf->addTextWrap(130,$Y,200,$FontSize, $BankRow[6],'right');
   $Y -= ($line_height*1.2);
   $pdf->addTextWrap(50,$Y,85,$FontSize, "Branch Code",'left');
   $pdf->addTextWrap(130,$Y,200,$FontSize, $BankRow[3],'right');
   $Y -= ($line_height*1.2);
   $pdf->addTextWrap(50,$Y,85,$FontSize, "Branch Name",'left');
   $pdf->addTextWrap(130,$Y,200,$FontSize, $BankRow[4],'right');
    $Y -= ($line_height*1.2);
   $pdf->addTextWrap(50,$Y,85,$FontSize, "SWIFT code",'left');
   $pdf->addTextWrap(130,$Y,200,$FontSize, $BankRow[7],'right');
   $Y -= ($line_height*1.2);
   $pdf->addTextWrap(50,$Y,85,$FontSize, "Currency",'left');
   $pdf->addTextWrap(130,$Y,200,$FontSize,$CurrencyName[$BankRow[1]],'right');
   $Y -= ($line_height*2);
            
    $paymentterms = str_replace('<div>','',html_entity_decode($_SESSION['paymentterms']));
    $paymentterms = str_replace('</div>','<br>',html_entity_decode($paymentterms));

    $paymentterms = explode('<br>',$paymentterms) ; 
    foreach ($paymentterms as $value) {
       $LeftOvers=$pdf->addTextWrap(50,$Y,400,$FontSize,$value,'left');
        While(strlen($LeftOvers) > 0){
             $Y -= ($line_height);
             $LeftOvers=$pdf->addTextWrap(50,$Y,400,$FontSize,$LeftOvers,'left');
         }
      $Y -= $line_height*.5;
    }
    
    $pdf->addTextWrap(42,$Bottom_Margin+($line_height*3),250, $FontSize,_('Signature By (Name/Signature):'),'left');
    $pdf->addTextWrap(145,$Bottom_Margin+($line_height*3),250, $FontSize,_('Confirmed By (Name/Signature):'),'right');
  
    if (is_array($urls) && count($urls) > 0) {
         $PageNumber++;
        include('includes/PDFQuoteheaderGallery.inc');
      } 
      
    $pdf->Output($_SESSION['DatabaseName'] . '_QUOTATION_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf','I');
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
      where `SalesHeader`.`documenttype`='1' 
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
      `SalesHeader`.`docdate` desc limit  ". $_SESSION['DefaultDisplayRecordsMax'] ."
      ";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
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
             . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?No=%s">Print Proforma:%s</a></td>',
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