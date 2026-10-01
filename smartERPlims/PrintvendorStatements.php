<?php
  include('includes/session.inc');
  include('includes/StatementHelpers.inc');
  
  if(isset($_GET['SupplierID'])){
    $_POST['output']='1';
    $_POST['Submit']='Quick';
    $_POST['VendorID']=$_GET['SupplierID'];
    
    $ResultIndex = DB_query("SELECT "
    . " `itemcode`,`customer`,`curr_cod`  from creditors "
    . " where itemcode='".$_GET['SupplierID']."'", $db);
    $row=DB_fetch_row($ResultIndex);
    $_POST['VendorName']=$row[1];
    $_POST['currencycode']=$row[2];
    
    $ResultIndex=DB_query("Select `periodno`"
    . "from `FinancialPeriods` ", $db);
    $row=DB_fetch_row($ResultIndex);
    $_POST['Financial_Periods']=$row[0];
       
  }
  
  If(isset($_POST['Submit'])){
          
  if($_POST['output']=='1'){
        ShowPDF();
    }else{
        Showhtml();
    }
}  else  {
    include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
   
    $FR = new FinancialPeriods();
 
    $Title = _('Vendor Statements');
    include('includes/header.inc');   

    echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Vendor Statements') .'" alt="" />' 
    . ' ' . _('Vendor Statements') . '</p>';
    
    echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
    echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
    echo '<table class="table table-bordered"><tr><td>Reporting Period</td></tr>';
    
    $FR->Get();
    echo '<tr><td>Supplier ID</td>'
        . '<td><input tabindex="4" type="text" name="VendorID" id="VendorID" value="'.$_POST['VendorID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchvendor" value="Search for Vendor"/></td></tr>'
        . '<tr><td>Supplier Name</td>'
        . '<td><input tabindex="5" type="text" name="VendorName" id="VendorName" value="'.$_POST['VendorName'].'"  size="50"  required="required" /></td></tr>';
echo '<tr><td>Select Report Output</td><td>'
            . '<select name="output">'
            . '<option value="1">PDF</option>'
            . '<option value="2" selected="selected">HTML/EXCEL</option>'
            . '</select>'
            . '</td></tr>';
    echo '<input type="hidden" size="5" name="currencycode" id="currencycode" value=""/>';
    echo '<tr><td></td><td><input type="submit" name="Submit" value="Print Statment"/></td></tr>'
    . '</table>';
  
    echo '</div></form>';

   include('includes/footer.inc');
   
}

  
Function LedgerHeader(){
    
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$finacialdates,$AccountName ;
 Global $firstrowpos,$lastrow,$db ;
    
 $FontSize = 12;
 $PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
  $pdf->newPage();
}
    
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 2, 200, $FontSize, _('Page').': '.$PageNumber, 'right');
$topRow = $Page_Height-$Top_Margin-$FontSize * 2;
// Print company logo
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Right_Margin+5,$Page_Height-$Top_Margin-50, 0,40);
$YPos=$topRow-25;
$FontSize = 9;
 
$pdf->addText($Right_Margin,$YPos,$FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$pdf->addText($Right_Margin, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Right_Margin, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Right_Margin, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($Right_Margin, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($Right_Margin, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($Right_Margin, $YPos-57, $FontSize,  _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

// Print company info
$XPos = 60;
$YPos = $YPos-70;
$FontSize=12;
$FontSize =10;

$adjustby = mb_strlen($AccountName) * (12/5)  ;
$AdJ = mb_strlen('Statement Date: Between '. ConvertSQLDate($finacialdates[0]).' And :'. ConvertSQLDate($finacialdates[1]) ) ;

$pdf->addText((($Page_Width-$adjustby)/2)-$Right_Margin-$Left_Margin,$YPos,12,$AccountName);
$pdf->addText((($Page_Width-$AdJ)/2)-$Right_Margin-$Left_Margin,$YPos-20,8,_('Statement Date: Between '). ConvertSQLDate($finacialdates[0])._(' And :'). ConvertSQLDate($finacialdates[1]));
$pdf->line($Page_Width-$Right_Margin,$YPos-30,$Left_Margin,$YPos-30);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 40;
$FontSize=12;
// Draw a rectangle with rounded corners around 'Delivery To' info
$FontSize=10;
$LeftOvers = $pdf->addTextWrap(42, $YPos,50, $FontSize, _('Date'),'left');
$LeftOvers = $pdf->addTextWrap(90, $YPos,50, $FontSize, _('Doc No'),'left');
$LeftOvers = $pdf->addTextWrap(145, $YPos,100, $FontSize, _('Doc Type'),'left');
$LeftOvers = $pdf->addTextWrap(245, $YPos,120, $FontSize, _('Narrative'),'left');
$LeftOvers = $pdf->addTextWrap(350, $YPos, 85, $FontSize, _('Debit'),'right');
$LeftOvers = $pdf->addTextWrap(415, $YPos, 85, $FontSize, _('Credit'),'right');
$LeftOvers = $pdf->addTextWrap(480, $YPos, 85, $FontSize, _('Balance'),'right');
$pdf->line($Page_Width-$Right_Margin,$YPos - $line_height,$Left_Margin,$YPos - $line_height);
     
$LeftOvers = $pdf->addTextWrap(480,($YPos-($line_height * 2)),85,$FontSize,number_format($Balance,2),'right');

$firstrowpos =($YPos - ($line_height * 3))  ;
$lastrow     = $Bottom_Margin + ($line_height * 2);
$YPos -= (2 * $line_height);
}  

function Ageing(){
      
 Global $pdf,$FontSize,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin ;
 Global $db,$lastrow ;
 
 $Yrow = ($lastrow - $line_height);
 
 $LeftOvers = $pdf->addTextWrap(45,$Yrow,100, $FontSize, _('Current'),'right');
 $LeftOvers = $pdf->addTextWrap(145,$Yrow,100, $FontSize, _('60 days'),'right');
 $LeftOvers = $pdf->addTextWrap(245,$Yrow,100, $FontSize, _('90 days'),'right');
 $LeftOvers = $pdf->addTextWrap(345,$Yrow,100, $FontSize, _('120 days'),'right');
 $LeftOvers = $pdf->addTextWrap(445,$Yrow,100, $FontSize, _('over 120 days'),'right');
 $pdf->line($Page_Width-$Right_Margin,$lastrow,$Left_Margin,$lastrow);

    
  db_autoallocatevendors($_POST['VendorID']);
     
 
 $agerow = db_ageingSuppliers_row($_POST['VendorID']);
 
 $LeftOvers = $pdf->addTextWrap(45,$Yrow- $line_height,100, $FontSize,number_format(abs($agerow[0])),'right');
 $LeftOvers = $pdf->addTextWrap(145,$Yrow- $line_height,100,$FontSize,number_format(abs($agerow[1])),'right');
 $LeftOvers = $pdf->addTextWrap(245,$Yrow- $line_height,100,$FontSize,number_format(abs($agerow[2])),'right');
 $LeftOvers = $pdf->addTextWrap(345,$Yrow- $line_height,100,$FontSize,number_format(abs($agerow[3])),'right');
 $LeftOvers = $pdf->addTextWrap(445,$Yrow- $line_height,100,$FontSize,number_format(abs($agerow[4])),'right');
 
    
}

Function ShowPDF(){
   
 Global $pdf,$FontSize,$PageNumber,$line_height,$YPos,$XPos ;
 Global $Page_Width,$Right_Margin,$Left_Margin,$Page_Height ;
 Global $Bottom_Margin,$Top_Margin,$Balance,$finacialdates,$AccountName ;
 Global $firstrowpos,$lastrow,$db ;
 
    $PaperSize='A4';
    include('includes/PDFStarter.php');
       
    $pdf->addInfo('Title',_('Statement of accounts'));
    $pdf->addInfo('Subject',_('Accounts'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
    $Balance = 0;
    $firstrowpos = 0;
    $lastrow = 0;
        
    $Result=DB_query("Select min(B.`start_date`),NOW() from `FinancialPeriods` B  where B.`periodno`='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    $AccountName = htmlspecialcharsLocal_decode($_POST['VendorName']);
    
     $SqlOutput=db_vendorstatements($_POST['VendorID'],$_POST['Financial_Periods']);
     $Balance=$SqlOutput['openbal'];
       
         LedgerHeader();
         $YPos=$firstrowpos;
         while($row=DB_fetch_array($SqlOutput['transactions'])){
         $debit = 0;$credit = 0; $Balance += $row['Grossamount'];
        
        if($row['Grossamount']>0){
           $debit=$row['Grossamount'];
        }else{
           $credit=$row['Grossamount'] * -1;
        }
                
        $LeftOvers = $pdf->addTextWrap(42,$YPos,50, $FontSize,ConvertSQLDate($row['Date']),'left');
        $LeftOvers = $pdf->addTextWrap(100,$YPos,50, $FontSize,$row['Documentno'],'left');
        $LeftOvers = $pdf->addTextWrap(150,$YPos,100, $FontSize,$row['doctypes'],'left');
        $LeftOvers = $pdf->addTextWrap(245,$YPos,100, $FontSize,$row['narration'],'left');
        $LeftOvers = $pdf->addTextWrap(350,$YPos, 85, 8,number_format($debit,2),'right');
        $LeftOvers = $pdf->addTextWrap(415,$YPos, 85, 8,number_format($credit,2),'right');
        $LeftOvers = $pdf->addTextWrap(480,$YPos, 85, 8,number_format($Balance,2),'right');
        
        $YPos -= $line_height;
        
        if($lastrow > $YPos){
            LedgerHeader();
            $YPos=$firstrowpos;
        }
        
    }
      
     if($lastrow > $YPos){
        $pdf->newPage();
    }

    Ageing();
    
   $pdf->OutputD($_SESSION['DatabaseName'].'_' ._('VendorStatements').'_'.$_POST['VendorID'].'_'. date('Y-m-d').'.pdf');
    $pdf->__destruct();
        

}

Function Showhtml(){
     Global $db ;
    
    $YearPeriod  = $_POST['Financial_Periods'];
    include('includes/header.inc');
        
    $Result=DB_query("Select min(B.`start_date`),max(B.End_date)
    from `FinancialPeriods` B where B.periodno='".$_POST['Financial_Periods']."'",$db);
    $finacialdates=DB_fetch_row($Result);   
    
    echo '<Div class="centre">Debtors Statemet '.$_POST['VendorName'].'</DIV>'
       . '<Div class="centre">'._('From :'). ConvertSQLDate($finacialdates[0])._(' To :'). ConvertSQLDate($finacialdates[1]) .'</DIV>'
       . '<table class="statement display" id="GL"><thead><tr>'
       . '<th>Date</th><th>Document No</th><th>Document Type</th><th>Narration</th>'
       . '<th>Debit</th><th>Credit</th><th>Balance</th></tr></thead><tbody>';
 
     $SqlOutput=db_vendorstatements($_POST['VendorID'],$_POST['Financial_Periods']);
     $Balance=$SqlOutput['openbal'];
       
         while($row=DB_fetch_array($SqlOutput['transactions'])){
            $debit = 0;
            $credit = 0; 
            $Balance += $row['Grossamount'];

                   if($row['Grossamount']>0){
                      $debit=$row['Grossamount'];
                   }else{
                      $credit=$row['Grossamount'] * -1;
                   }

                  echo '<tr><td>' . ConvertSQLDate($row['Date']) . '</td>';
                  echo '<td>' . $row['Documentno'] . '</td>';
                  echo '<td>' .$row['doctypes']. '</td>';
                  
                  echo '<td>' .$row['narration']. '</td>';
                  echo '<td class="number">' .number_format($debit,2). '</td>';
                  echo '<td class="number">' .number_format($credit,2). '</td>';
                  echo '<td class="number">' .number_format($Balance,2). '</td></tr>';
         }
         
   
 echo '</tbody><tfooter><tr>'
       . '<th>Date</th><th>Document No</th><th>Document Type</th><th>Narration</th>'
       . '<th>Debit</th><th>Credit</th><th>Balance</th></tr><tfooter></table>'
         . '<table class="table table-bordered"><tr><th colspan="3">Current Age</th>'
         . '<th>60 days</th>'
         . '<th>90 days</th>'
         . '<th>120 days</th>'
         . '<th>Over 120 days</th>'
         . '</tr>'; 
    
       db_autoallocatevendors($_POST['VendorID']);
     
        $agerow = db_ageingSuppliers_row($_POST['VendorID']);

        echo '<tr><td colspan="3">' .number_format($agerow[0]). '</td>';
        echo '<td class="number">' .number_format($agerow[1]). '</td>';
        echo '<td class="number">' .number_format($agerow[2]). '</td>';
        echo '<td class="number">' .number_format($agerow[3]). '</td>';
        echo '<td class="number">' .number_format($agerow[4]). '</td></tr>';
 
       echo '</table>';
       echo '<input type="button" onclick="tableToExcel(\'GL\',\''.$_POST['VendorName'].'\')" value="Export to Excel">';

   include('includes/footer.inc');
}


?>
