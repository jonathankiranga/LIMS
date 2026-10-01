<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Quotation');
include('includes/CountriesArray.php');

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
      ,`SalesHeader`.`externaldocumentno` as BankCode2
      ,SalesHeader.`locationcode` as BankCode
      ,SalesHeader.paymentterms
      from `SalesHeader` join SalesLine on 
      `SalesHeader`.`documentno`=SalesLine.`documentno` 
      join debtors on `SalesHeader`.`customercode`=debtors.itemcode
      left join `salesrepsinfo` on `salesrepsinfo`.code=`SalesHeader`.`salespersoncode`
      where `SalesHeader`.`documenttype`='54' 
      and `SalesHeader`.`documentno`='".$_GET['No']."'";

    $Result = DB_query($SQL,$db);
    $myrow = DB_fetch_row($Result);
    $Bankcode = $myrow[17];
    $Bankcode2 = $myrow[16];
    $footerArray = getFooter($_GET['No']);
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Commercial Invoice";
    
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
      ,SalesLine.code as itemcode
  FROM `SalesHeader` join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        and SalesHeader.documenttype=54  
        where SalesHeader.documentno='%s' GROUP BY SalesLine.documentno, SalesLine.SampleID",$_GET['No']);
     
    
     $R1=0; $R2=0; $R3=0;
     $Total_Rows=1;
     $FontSize=9;
     $Row_Count=0;
     
     $ResultIndex=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
        $Total_Rows ++;
      }
     DB_free_result($ResultIndex);
     
     $SalesAddCategory =new SalesAddCategory();
 
     include('includes/PDFQuoteheader.inc');
     $YPos = $firstrowpos-6;
     
     $Results = DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $Row_Count++;
         $R1 +=0;
         $R2 +=$rows['netamt'];
         $R3 +=$rows['netamt'];
         $ppu=(int)$rows['partperunit'];
         $vatrate=(int) 0;
        
         if($ppu>1){
           $units=$rows['unitofmeasure'].'(1x'.$ppu.')';
         }else{
            $units=$rows['unitofmeasure'];
         }
           $qty=(int)$rows['Quantity'];
           
           $roadgrade = $SalesAddCategory->AddPrefix($rows['itemcode']);
       
           $pdf->addTextWrap($Left_Margin, $YPos,40,9,$qty,'right');
           $LeftOvers = $pdf->addTextWrap(90, $YPos,100,9, $roadgrade.$rows['description'],'left');
           
           $pdf->addTextWrap(195, $YPos,85,9, $units,'right');
           $pdf->addTextWrap(250, $YPos,85,9, number_format($rows['Sp'],2),'right');
          
           $pdf->addTextWrap(310, $YPos,55,9,$vatrate,'right');
           $pdf->addTextWrap(400, $YPos,70,9,number_format(0,2),'right');
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
  
  if($SummaryYpos<450){

    // This page header increments variable $PageNumber before printing it.
    $PageNumber++;
    // Inserts a page break if it is not the first page
    if ($PageNumber>1) {
            $pdf->newPage();
    }
    $YPos = $Page_Height-$Top_Margin-$FontSize*5;
    $XPos = 361;
    $FontSize=9;
    // Print 'Quotation' info
    $pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*2, 200, 9, _('Date').': '. ConvertSQLDate($myrow[1]), 'left');
    $pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*3, 200, 9, _('Page').': '.$PageNumber, 'left');
    $pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*4, 200, 9, _('Terms'). ': '.$myrow[18], 'left');
    $pdf->addTextWrap($Left_Margin, $Page_Height-$Top_Margin-$FontSize*5, 200, 9, _($headerName.' No'). ': '.$_GET['No'], 'left');
    $pdf->RoundRectangle($Left_Margin,$Page_Height-$Top_Margin-$FontSize, 225+10+10, 65, 10, 10);// Function RoundRectangle from includes/class.pdf.php
    //
    // Print company info
    $XPos = 361;

    // Print company logo
    $pdf->addJpegFromFile($_SESSION['LogoFile'],$Page_Width-$Right_Margin-140,$Page_Height-$Top_Margin*3,0,40);

    $pdf->addText($Page_Width-$Right_Margin-250, $YPos,9,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
    $pdf->addText($Page_Width-$Right_Margin-250, $YPos-12,9,_('Add1'). ': '. $_SESSION['CompanyRecord']['regoffice1']);
    $pdf->addText($Page_Width-$Right_Margin-250, $YPos-21,9,_('Add2'). ': '. $_SESSION['CompanyRecord']['regoffice2']);
    $pdf->addText($Page_Width-$Right_Margin-250, $YPos-30,9,_('Add3'). ': '. $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] );
    $pdf->addText($Page_Width-$Right_Margin-250, $YPos-39,9,_('Tel'). ': ' . $_SESSION['CompanyRecord']['telephone']);
    $pdf->addText($Page_Width-$Right_Margin-250, $YPos-48,9,_('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);
    $pdf->addText($Page_Width-$Right_Margin-250, $YPos-57,9,_('Sales Rep') . ': ' .$myrow[7]);
    $pdf->addText($Page_Width-$Right_Margin-250, $YPos-69,9,_('Sales Rep Email'). ': '. $myrow[8]);
    $pdf->addText($Page_Width-$Right_Margin-250, $YPos-81,8, _('Currency') . ' - ' . $myrow[6] . ' ' . $CurrencyName[$myrow[6]]);

    $pdf->RoundRectangle($Page_Width-$Right_Margin-250,$Page_Height-$Top_Margin-$FontSize,250,140, 10, 10);// Function RoundRectangle from includes/class.pdf.php

    // Print 'Delivery To' info
    $XPos = 46;
    $YPos = $Page_Height-$Top_Margin-80;
    $FontSize=10;
    $pdf->addText($Left_Margin, $YPos, $FontSize, _('Customer').':' );
    $pdf->addText($Left_Margin, $YPos-15, $FontSize,htmlspecialcharsLocal_decode($myrow[5]));
    $pdf->addText($Left_Margin, $YPos-27, $FontSize,_('Email'). ': '. $myrow[10]);
    $pdf->addText($Left_Margin, $YPos-39, $FontSize,_('Tel'). ': '. $myrow[14]);
    $pdf->addText($Left_Margin, $YPos-51, $FontSize,_('Contact'). ': '.$myrow[15]);
    $pdf->RoundRectangle($Left_Margin,$YPos, 225+10+10, 65, 10, 10);// Function RoundRectangle from includes/class.pdf.php

    // Draw a rectangle with rounded corners around 'Delivery To' info
    $XPos= 50;
    $Y = $firstrowpos-6 ;
       
}

   $YBank1 = $Y- ($line_height*3.5);
    $pdf->addTextWrap(50,$YBank1+$line_height,400,$FontSize, _('BANK DETAILS'),'left');
    $pdf->RoundRectangle(45,$YBank1, 225+10+10,110, 10, 10);// Function RoundRectangle from includes/class.pdf.php
    $pdf->RoundRectangle(295,$YBank1, 225+10,110, 10, 10);// Function RoundRectangle from includes/class.pdf.php

    displaycolums1($Y,$Bankcode);
    $Y = displaycolums2($Y,$Bankcode2);
   
    $paymentterms = str_replace('<div>','<br>',html_entity_decode($footerArray['paymentterms']));
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
    
     $Y -= $line_height*10;
    
    $LeftOvers = $pdf->addTextWrap(42,$Y,250, $FontSize,_('Signature By (Name/Signature):'),'left');
    $LeftOvers = $pdf->addTextWrap(145,$Y,250, $FontSize,_('Confirmed By (Name/Signature):'),'right');

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
      where `SalesHeader`.`documenttype`='54' 
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

function getFooter($DOC){
        
 $filename= 'doc/'.trim($DOC).'.terms';
 $paymentterms =  file_get_contents($filename);
 
 $filename= 'doc/'.trim($DOC).'.bank';
 $bankaccountdetails = file_get_contents($filename);
  
 $return = array('paymentterms'=>((mb_strlen($paymentterms)>1)?$paymentterms:$_SESSION['paymentterms']),
  'bankaccountdetails'=>((mb_strlen($bankaccountdetails)>1)?$bankaccountdetails:$_SESSION['bankaccountdetails'])); 
 
 return $return;
}
 
 
     
function displaycolums1($Y,$Bankcode){
    global $db,$pdf,$line_height,$FontSize,$CurrencyName;
    $YBank1=$Y; 
      
  $GetbankSql="SELECT `bankName`,`currency`,`AccountNo`,`BranchCode`,`BranchName`,`AcctName`,`bankCode`,`swiftcode`
  FROM BankAccounts where `accountcode`='".$Bankcode."'";
  $Results = DB_query($GetbankSql,$db);
  $BankRow = DB_fetch_row($Results);
  
   $YBank1-= ($line_height*5.5);
   
   $pdf->addTextWrap(50,$YBank1,85,$FontSize,"Bank Account:",'left');
   $pdf->addTextWrap(150,$YBank1,250,$FontSize,trim($BankRow[5]),'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(50,$YBank1,85,$FontSize, "Bank Acount No:",'left');
   $pdf->addTextWrap(150,$YBank1,100,$FontSize, trim($BankRow[2]),'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(50,$YBank1,85,$FontSize, "Bank :",'left');
   $pdf->addTextWrap(150,$YBank1,100,$FontSize, trim($BankRow[0]),'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(50,$YBank1,85,$FontSize, "Bank Code:",'left');
   $pdf->addTextWrap(150,$YBank1,100,$FontSize, trim($BankRow[6]),'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(50,$YBank1,85,$FontSize, "Branch Code:",'left');
   $pdf->addTextWrap(150,$YBank1,100,$FontSize,trim($BankRow[3]),'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(50,$YBank1,85,$FontSize, "Branch Name:",'left');
   $pdf->addTextWrap(150,$YBank1,100,$FontSize,trim($BankRow[4]),'left');
    $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(50,$YBank1,85,$FontSize, "SWIFT code:",'left');
   $pdf->addTextWrap(150,$YBank1,100,$FontSize, trim($BankRow[7]),'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(50,$YBank1,85,$FontSize, "Currency:",'left');
   $C= trim($BankRow[1]);
   $pdf->addTextWrap(150,$YBank1,100,$FontSize,$CurrencyName[$C],'left');
   $YBank1 -= ($line_height*2);
}

function displaycolums2($Y,$Bankcode){
    global $db,$pdf,$line_height,$FontSize,$CurrencyName;
    $YBank1=$Y; 
      
  $GetbankSql="SELECT `bankName`,`currency`,`AccountNo`,`BranchCode`,`BranchName`,`AcctName`,`bankCode`,`swiftcode`
  FROM BankAccounts where `accountcode`='".$Bankcode."'";
  $Results = DB_query($GetbankSql,$db);
  $BankRow = DB_fetch_row($Results);
  
   $YBank1-= ($line_height*5.5);
   
   $pdf->addTextWrap(300,$YBank1,85,$FontSize,"Bank Account:",'left');
   $pdf->addTextWrap(400,$YBank1,250,$FontSize, $BankRow[5],'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(300,$YBank1,85,$FontSize, "Bank Acount No:",'left');
   $pdf->addTextWrap(400,$YBank1,150,$FontSize, $BankRow[2],'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(300,$YBank1,85,$FontSize, "Bank :",'left');
   $pdf->addTextWrap(400,$YBank1,150,$FontSize, $BankRow[0],'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(300,$YBank1,85,$FontSize, "Bank Code:",'left');
   $pdf->addTextWrap(400,$YBank1,100,$FontSize, $BankRow[6],'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(300,$YBank1,85,$FontSize, "Branch Code:",'left');
   $pdf->addTextWrap(400,$YBank1,100,$FontSize, $BankRow[3],'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(300,$YBank1,85,$FontSize, "Branch Name:",'left');
   $pdf->addTextWrap(400,$YBank1,150,$FontSize, $BankRow[4],'left');
    $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(300,$YBank1,85,$FontSize, "SWIFT code:",'left');
   $pdf->addTextWrap(400,$YBank1,100,$FontSize, $BankRow[7],'left');
   $YBank1 -= ($line_height*1.2);
   $pdf->addTextWrap(300,$YBank1,85,$FontSize, "Currency:",'left');
   $C = trim($BankRow[1]);
   $pdf->addTextWrap(400,$YBank1,100,$FontSize,$CurrencyName[$C],'left');
   $YBank1 -= ($line_height*2);
   
   return $YBank1;
}


?>