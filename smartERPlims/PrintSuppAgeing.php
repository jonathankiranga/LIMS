<?php
  include('includes/session.inc');
  include('includes/StatementHelpers.inc');
  
  If(isset($_POST['Submit'])){
          
    $PaperSize='A4_Landscape';
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
    $PeriodNo = GetPeriod($_POST['date'],$db);
    $age1=0;$age2=0;$age3=0;$age4=0;$age5=0;
    
     include('includes/SuppliersAgeingHeader.inc');
         
        $SqlOutput=DB_query("Select itemcode,customer from creditors where `curr_cod`='".$_POST['curr_cod']."' order by customer asc",$db);
        while($row=DB_fetch_array($SqlOutput)){
             $debtors[]=$row;
         }
     
         
         foreach ($debtors as $row) {
            $Name = htmlspecialcharsLocal_decode($row['customer']);
            $ageingarray = Ageing($row['itemcode'], $PeriodNo);
                  $age1 +=$ageingarray[0];
                  $age2 +=$ageingarray[1];
                  $age3 +=$ageingarray[2];
                  $age4 +=$ageingarray[3];
                  $age5 +=$ageingarray[4];
         
            $LeftOvers = $pdf->addTextWrap(45,  $YPos,200, $FontSize,$Name,'left');
            $LeftOvers = $pdf->addTextWrap(250, $YPos,80,$FontSize,number_format($ageingarray[0]),'right');
            $LeftOvers = $pdf->addTextWrap(320, $YPos,80,$FontSize,number_format($ageingarray[1]),'right');
            $LeftOvers = $pdf->addTextWrap(400, $YPos,80,$FontSize,number_format($ageingarray[2]),'right');
            $LeftOvers = $pdf->addTextWrap(500, $YPos,80,$FontSize,number_format($ageingarray[3]),'right');
            $LeftOvers = $pdf->addTextWrap(600, $YPos,80,$FontSize,number_format($ageingarray[4]),'right');

             $YPos -= $line_height;
        
           if ($YPos - (2 * $line_height) < $Bottom_Margin){
              include('includes/SuppliersAgeingHeader.inc');
           }
    }
    
    $LeftOvers = $pdf->addTextWrap(45,$YPos,100, $FontSize,'Total:','left');
    $LeftOvers = $pdf->addTextWrap(250, $YPos,80,$FontSize,number_format($age1),'right');
    $LeftOvers = $pdf->addTextWrap(320, $YPos,80,$FontSize,number_format($age2),'right');
    $LeftOvers = $pdf->addTextWrap(400, $YPos,80,$FontSize,number_format($age3),'right');
    $LeftOvers = $pdf->addTextWrap(500, $YPos,80,$FontSize,number_format($age4),'right');
    $LeftOvers = $pdf->addTextWrap(600, $YPos,80,$FontSize,number_format($age5),'right');

  $pdf->OutputD($_SESSION['DatabaseName'].'_' ._('SupplierAgeing').'_'.$_POST['date'].'.pdf');
  $pdf->__destruct();
        

}elseif(isset($_POST['SubmitExcel'])){
         $age1=0;$age2=0;$age3=0;$age4=0;$age5=0;
    
    $csv_output = "'Supplier Ageing Analysis As at ".$_POST['date']."'\n";
    $csv_output .= "'Supplier ID','Supplier Name','Current','60 days','90 days','120 days','over 120 days'\n";
    
    $PeriodNo=GetPeriod($_POST['date'],$db);
        $SqlOutput=DB_query("Select itemcode,customer from creditors where `curr_cod`='".$_POST['curr_cod']."' order by customer asc",$db);
        while($row=DB_fetch_array($SqlOutput)){
             $debtors[]=$row;
         }
         
    foreach ($debtors as $row) {
        $Name = htmlspecialcharsLocal_decode($row['customer']);
        $ageingarray = Ageing($row['itemcode'], $PeriodNo);
          $age1 +=$ageingarray[0];
            $age2 +=$ageingarray[1];
            $age3 +=$ageingarray[2];
            $age4 +=$ageingarray[3];
            $age5 +=$ageingarray[4];
         
        $csv_output .=$row['itemcode']. ',' .$Name.',' . $ageingarray[0]. ',' . $ageingarray[1]. ',' .$ageingarray[2]. ',' .$ageingarray[3]. ',' .$ageingarray[4]. "\n";
    }
    
      $csv_output .= "TOTALS," .($age1+$age2+$age3+$age4+$age5)."," .$age1. "," .$age2. "," .$age3. "," .$age4. "," .$age5. "\n";
  
    $FileName =  $_SESSION['reports_dir'] . '/SupplierAgeing_' .date('Y-m-d').'.csv';
    $csvFile = fopen($FileName, 'w');
    $i = fwrite($csvFile, $csv_output);
    header('Location: ' . $_SESSION['reports_dir'] . '/SupplierAgeing_' .date('Y-m-d').'.csv');

    
}else{
    
    include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
   
    $Title = _('Customer Statements');
    include('includes/header.inc');   

    echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Suppliers Ageing') .'" alt="" />' . ' ' . _('Suppliers Ageing') . '</p>';
    echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
    echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';

    echo '<div class="responsive"><table class="table-bordered"><tr>'
            . '<td>As At</td>'
            . '<td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></tr>';
    $result=DB_query("SELECT currency, currabrev FROM currencies",$db);
	if (DB_num_rows($result)==0){
		$DataError =1;
		echo '<tr><td colspan="2"><p>'._('There are no currencies currently defined - go to the setup tab of the main menu and set at least one up first').'</p></td></tr>';
	} else {
		if (!isset($_POST['curr_cod'])){
			$CurrResult = DB_query("SELECT currencydefault FROM companies WHERE coycode=1",$db);
			$myrow = DB_fetch_row($CurrResult);
			$_POST['curr_cod'] = $myrow[0];
		}
		echo '<tr><td>' . _('Supplier Currency') . ':</td>
				<td><select name="curr_cod" required="required">';
		while ($myrow = DB_fetch_array($result)) {
			if ($_POST['curr_cod']==$myrow['currabrev']){
				echo '<option selected="selected" value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
			} else {
				echo '<option value="'. $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
			}
		} //end while loop
		DB_data_seek($result,0);

		echo '</select></td></tr>';
        }
    echo '<tr><td colspan="3">'
    . '<input type="submit" name="Submit" value="Print"/>'
            . '<input type="submit" name="SubmitExcel" value="Excel"/></td></tr>'
    . '</table></div>';
  
    echo '</div></form>';

   include('includes/footer.inc');
   
}


function Ageing($custid,$period){
  return db_ageingSuppliersReport_row($custid,$period);
}


?>
