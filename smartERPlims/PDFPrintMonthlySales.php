<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Stock Sales by Month');
 Global $toDate,$periodno;
 
 $_SESSION['StockArray']=array();
 
if(isset($_POST['year'])){
    $toDate = ConvertSQLDate($_POST['year'].'-12-31');
    $periodno = GetPeriod($toDate,$db,false);
}
 
if(isset($_POST['StockSalesReport'])){
    if($_POST['output']=='1'){
       Custom();
    }else{
       Showhtml();
    }
    
}else{

include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/header.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="center"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>';
echo '<table class="table table-bordered"><tr><td>Reporting Period:</td><td>';
echo '<select name="year">';
 
 $ResultIndex = DB_query("SELECT DISTINCT DATEPART(YEAR,lastdate_in_period) as years
         FROM `periods` where DATEPART(YEAR,lastdate_in_period)<=DATEPART(YEAR,NOW()) order by years desc", $db);
 
 while($row= DB_fetch_array($ResultIndex)){
     echo sprintf('<option value="%s">%s</option>',$row['years'],$row['years']);
 }
   
echo '</select></td></tr>';
  echo '<tr><td>Report In</td><td>'
            . '<select name="reportin">'
            . '<option value="1">Quantity</option>'
            . '<option value="2">Value</option>'
            . '<option value="3">Both Quantity and Value</option>'
            . '</select>'
            . '</td></tr>';
   echo '<tr><td>Select Report Output</td><td>'
            . '<select name="output">'
            . '<option value="1">PDF</option>'
            . '<option value="2">HTML/EXCEL</option>'
            . '</select>'
            . '</td></tr>';
  echo '<tr><td colspan="2"><input type="submit" name="StockSalesReport" value="Print Report"/></td></tr>'
  . '</table>';
  echo '</div></form>';
  
  
include('includes/footer.inc');
 

}   

Function Custom(){
    global $db,$periodno,$toDate;   
    
    $h=array('1'=>'Quantity(As Denoted)','2'=>'Values in (Kshs)','3'=>'Both Values in (Kshs) and Quantity(As Denoted)');
    
    $PaperSize = 'A4_Landscape';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Stock Control'));
    $pdf->addInfo('Subject',_('Stock Control'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
    $headerName  = "Product Sales For Year Ending :".$toDate;
    $headerdetails = "Reported In ".$h[$_POST['reportin']];
   
    include('includes/PDFStocksalesheader.inc');
     
     GetProducts();
     
     
     $YPos = $firstrowpos-20;
     
    $jan=0;$feb=0;$march=0; $april=0; $may=0; $june=0;
    $july=0; $august=0; $september=0; $october=0; $november=0; $december=0;
        
      foreach ($_SESSION['StockArray'] as $key => $value) {
           $FontSize = 10;
          if(array_key_exists('MyName',$value)){
             $jan +=$value['V1'];
             $feb +=$value['V2'];
             $march +=$value['V3'];
             $april +=$value['V4'];
             $may +=$value['V5'];
             $june +=$value['V6'];
             $july +=$value['V7'];
             $august +=$value['V8'];
             $september +=$value['V9'];
             $october +=$value['V10'];
             $november +=$value['V11'];
             $december +=$value['V12'];
             
            $LeftOvers = $pdf->addTextWrap(40, $YPos,80, $FontSize,$value['MyName'],'left');
            $LeftOvers = $pdf->addTextWrap(40, $YPos-$line_height,80, $FontSize,$LeftOvers,'left');
            $LeftOvers = $pdf->addTextWrap(40, $YPos-$line_height*2,80, $FontSize,$LeftOvers,'left');
            $LeftOvers = $pdf->addTextWrap(40, $YPos-$line_height*3,80, $FontSize,$LeftOvers,'left');
           
            if($_POST['reportin']==1){
            $LeftOvers = $pdf->addTextWrap(140, $YPos,56, $FontSize,$value['Q1'],'right');
            $LeftOvers = $pdf->addTextWrap(196, $YPos,56, $FontSize,$value['Q2'],'right');
            $LeftOvers = $pdf->addTextWrap(252, $YPos,56, $FontSize,$value['Q3'],'right');
            $LeftOvers = $pdf->addTextWrap(308, $YPos,56, $FontSize,$value['Q4'],'right');
            $LeftOvers = $pdf->addTextWrap(364, $YPos,56, $FontSize,$value['Q5'],'right');
            $LeftOvers = $pdf->addTextWrap(420, $YPos,56, $FontSize,$value['Q6'],'right');
            $LeftOvers = $pdf->addTextWrap(476, $YPos,56, $FontSize,$value['Q7'],'right');
            $LeftOvers = $pdf->addTextWrap(532, $YPos,56, $FontSize,$value['Q8'],'right');
            $LeftOvers = $pdf->addTextWrap(588, $YPos,56, $FontSize,$value['Q9'],'right');
            $LeftOvers = $pdf->addTextWrap(644, $YPos,56, $FontSize,$value['Q10'],'right');
            $LeftOvers = $pdf->addTextWrap(700, $YPos,56, $FontSize,$value['Q11'],'right');
            $LeftOvers = $pdf->addTextWrap(756, $YPos,56, $FontSize,$value['Q12'],'right');
            }
            if($_POST['reportin']==2){
                $FontSize = 8;
            $LeftOvers = $pdf->addTextWrap(140, $YPos,56, $FontSize,number_format($value['V1'],2),'right');
            $LeftOvers = $pdf->addTextWrap(196, $YPos,56, $FontSize,number_format($value['V2'],2),'right');
            $LeftOvers = $pdf->addTextWrap(252, $YPos,56, $FontSize,number_format($value['V3'],2),'right');
            $LeftOvers = $pdf->addTextWrap(308, $YPos,56, $FontSize,number_format($value['V4'],2),'right');
            $LeftOvers = $pdf->addTextWrap(364, $YPos,56, $FontSize,number_format($value['V5'],2),'right');
            $LeftOvers = $pdf->addTextWrap(420, $YPos,56, $FontSize,number_format($value['V6'],2),'right');
            $LeftOvers = $pdf->addTextWrap(476, $YPos,56, $FontSize,number_format($value['V7'],2),'right');
            $LeftOvers = $pdf->addTextWrap(532, $YPos,56, $FontSize,number_format($value['V8'],2),'right');
            $LeftOvers = $pdf->addTextWrap(588, $YPos,56, $FontSize,number_format($value['V9'],2),'right');
            $LeftOvers = $pdf->addTextWrap(644, $YPos,56, $FontSize,number_format($value['V10'],2),'right');
            $LeftOvers = $pdf->addTextWrap(700, $YPos,56, $FontSize,number_format($value['V11'],2),'right');
            $LeftOvers = $pdf->addTextWrap(756, $YPos,56, $FontSize,number_format($value['V12'],2),'right');
            }
            
            if($_POST['reportin']==3){
            $FontSize = 8;
            $LeftOvers = $pdf->addTextWrap(135, $YPos,20,8,'Kshs','left');
            $LeftOvers = $pdf->addTextWrap(135, $YPos-$line_height,20,8,'QTY','left');
           
            $LeftOvers = $pdf->addTextWrap(140, $YPos,56, $FontSize,number_format($value['V1'],2),'right');
            $LeftOvers = $pdf->addTextWrap(196, $YPos,56, $FontSize,number_format($value['V2'],2),'right');
            $LeftOvers = $pdf->addTextWrap(252, $YPos,56, $FontSize,number_format($value['V3'],2),'right');
            $LeftOvers = $pdf->addTextWrap(308, $YPos,56, $FontSize,number_format($value['V4'],2),'right');
            $LeftOvers = $pdf->addTextWrap(364, $YPos,56, $FontSize,number_format($value['V5'],2),'right');
            $LeftOvers = $pdf->addTextWrap(420, $YPos,56, $FontSize,number_format($value['V6'],2),'right');
            $LeftOvers = $pdf->addTextWrap(476, $YPos,56, $FontSize,number_format($value['V7'],2),'right');
            $LeftOvers = $pdf->addTextWrap(532, $YPos,56, $FontSize,number_format($value['V8'],2),'right');
            $LeftOvers = $pdf->addTextWrap(588, $YPos,56, $FontSize,number_format($value['V9'],2),'right');
            $LeftOvers = $pdf->addTextWrap(644, $YPos,56, $FontSize,number_format($value['V10'],2),'right');
            $LeftOvers = $pdf->addTextWrap(700, $YPos,56, $FontSize,number_format($value['V11'],2),'right');
            $LeftOvers = $pdf->addTextWrap(756, $YPos,56, $FontSize,number_format($value['V12'],2),'right');
        
            $YPos-=$line_height;
            $LeftOvers = $pdf->addTextWrap(140, $YPos,56, $FontSize,$value['Q1'],'right');
            $LeftOvers = $pdf->addTextWrap(196, $YPos,56, $FontSize,$value['Q2'],'right');
            $LeftOvers = $pdf->addTextWrap(252, $YPos,56, $FontSize,$value['Q3'],'right');
            $LeftOvers = $pdf->addTextWrap(308, $YPos,56, $FontSize,$value['Q4'],'right');
            $LeftOvers = $pdf->addTextWrap(364, $YPos,56, $FontSize,$value['Q5'],'right');
            $LeftOvers = $pdf->addTextWrap(420, $YPos,56, $FontSize,$value['Q6'],'right');
            $LeftOvers = $pdf->addTextWrap(476, $YPos,56, $FontSize,$value['Q7'],'right');
            $LeftOvers = $pdf->addTextWrap(532, $YPos,56, $FontSize,$value['Q8'],'right');
            $LeftOvers = $pdf->addTextWrap(588, $YPos,56, $FontSize,$value['Q9'],'right');
            $LeftOvers = $pdf->addTextWrap(644, $YPos,56, $FontSize,$value['Q10'],'right');
            $LeftOvers = $pdf->addTextWrap(700, $YPos,56, $FontSize,$value['Q11'],'right');
            $LeftOvers = $pdf->addTextWrap(756, $YPos,56, $FontSize,$value['Q12'],'right');
            }
           }
          
         $YPos -= $line_height * 4;
        if($YPos<$Bottom_Margin+$line_height){
            
            if($_POST['reportin']!=1){
                $FontSize=8.5;
                $pdf->line(140, $YPos+$line_height * 2,$Page_Width-$Right_Margin,$YPos+$line_height * 2);
                $LeftOvers = $pdf->addTextWrap(30,  $YPos+$line_height,56, $FontSize, 'Total','right');
                $LeftOvers = $pdf->addTextWrap(140, $YPos+$line_height,56, $FontSize,number_format($jan,2),'right');
                $LeftOvers = $pdf->addTextWrap(196, $YPos+$line_height,56, $FontSize,number_format($feb,2),'right');
                $LeftOvers = $pdf->addTextWrap(252, $YPos+$line_height,56, $FontSize,number_format($march,2),'right');
                $LeftOvers = $pdf->addTextWrap(308, $YPos+$line_height,56, $FontSize,number_format($april,2),'right');
                $LeftOvers = $pdf->addTextWrap(364, $YPos+$line_height,56, $FontSize,number_format($may,2),'right');
                $LeftOvers = $pdf->addTextWrap(420, $YPos+$line_height,56, $FontSize,number_format($june,2),'right');
                $LeftOvers = $pdf->addTextWrap(476, $YPos+$line_height,56, $FontSize,number_format($july,2),'right');
                $LeftOvers = $pdf->addTextWrap(532, $YPos+$line_height,56, $FontSize,number_format($august,2),'right');
                $LeftOvers = $pdf->addTextWrap(588, $YPos+$line_height,56, $FontSize,number_format($september,2),'right');
                $LeftOvers = $pdf->addTextWrap(644, $YPos+$line_height,56, $FontSize,number_format($october,2),'right');
                $LeftOvers = $pdf->addTextWrap(700, $YPos+$line_height,56, $FontSize,number_format($november,2),'right');
                $LeftOvers = $pdf->addTextWrap(756, $YPos+$line_height,56, $FontSize,number_format($december,2),'right');
            }
            
             include('includes/PDFStocksalesheader.inc');
             $YPos = $firstrowpos-20;
   
         }
           
         
          
     }
           
     if($_POST['reportin']!=1){
        $FontSize=8.5;
        $LeftOvers = $pdf->addTextWrap(30,  $Bottom_Margin,56, $FontSize, 'Total','right');
        $LeftOvers = $pdf->addTextWrap(140, $Bottom_Margin,56, $FontSize,number_format($jan,2),'right');
        $LeftOvers = $pdf->addTextWrap(196, $Bottom_Margin,56, $FontSize,number_format($feb,2),'right');
        $LeftOvers = $pdf->addTextWrap(252, $Bottom_Margin,56, $FontSize,number_format($march,2),'right');
        $LeftOvers = $pdf->addTextWrap(308, $Bottom_Margin,56, $FontSize,number_format($april,2),'right');
        $LeftOvers = $pdf->addTextWrap(364, $Bottom_Margin,56, $FontSize,number_format($may,2),'right');
        $LeftOvers = $pdf->addTextWrap(420, $Bottom_Margin,56, $FontSize,number_format($june,2),'right');
        $LeftOvers = $pdf->addTextWrap(476, $Bottom_Margin,56, $FontSize,number_format($july,2),'right');
        $LeftOvers = $pdf->addTextWrap(532, $Bottom_Margin,56, $FontSize,number_format($august,2),'right');
        $LeftOvers = $pdf->addTextWrap(588, $Bottom_Margin,56, $FontSize,number_format($september,2),'right');
        $LeftOvers = $pdf->addTextWrap(644, $Bottom_Margin,56, $FontSize,number_format($october,2),'right');
        $LeftOvers = $pdf->addTextWrap(700, $Bottom_Margin,56, $FontSize,number_format($november,2),'right');
        $LeftOvers = $pdf->addTextWrap(756, $Bottom_Margin,56, $FontSize,number_format($december,2),'right');
     }
   
    $pdf->OutputD($_SESSION['DatabaseName'] . '_StockReport_by_' . $_POST['reportin'].'.pdf');
    $pdf->__destruct();
    
 }
       
Function Showhtml(){
    global $db,$periodno,$toDate;   
    
    $h=array('1'=>'Quantity(As Denoted)','2'=>'Values in (Kshs)','3'=>'Both Values in (Kshs) and Quantity(As Denoted)');
    
    include('includes/header.inc');
    
     $headerName  = "Product Sales For Year Ending :".$toDate;
     $headerdetails = "Reported In ".$h[$_POST['reportin']];
   
    
    echo '<div class="container"><Div class="centre">'.$headerName.'</DIV>'
       . '<Div class="centre">'.$headerdetails .'</DIV>'
       . '<table class="table table-bordered" id="GL"><tr>'
       . '<th>Product Name</th><th>JAN</th><th>FEB</th>'
       . '<th>MARCH</th><th>APRIL</th><th>MAY</th><th>JUNE</th><th>JULY</th>'
       . '<th>AUGUST</th><th>SEPTEMBER</th><th>OCTOBER</th><th>NOVEMBER</th><th>DECEMBER</th></tr>';
    
       GetProducts();
   
     
    foreach ($_SESSION['StockArray'] as $key => $value) {
           echo '<tr>';
          if(array_key_exists('MyName',$value)){
          
            echo '<td>'.$value['MyName'].'</td>';
             if($_POST['reportin']==1){
                echo '<td>'.$value['Q1'].'</td>';
                echo '<td>'.$value['Q2'].'</td>';
                echo '<td>'.$value['Q3'].'</td>';
                echo '<td>'.$value['Q4'].'</td>';
                echo '<td>'.$value['Q5'].'</td>';
                echo '<td>'.$value['Q6'].'</td>';
                echo '<td>'.$value['Q7'].'</td>';
                echo '<td>'.$value['Q8'].'</td>';
                echo '<td>'.$value['Q9'].'</td>';
                echo '<td>'.$value['Q10'].'</td>';
                echo '<td>'.$value['Q11'].'</td>';
                echo '<td>'.$value['Q12'].'</td>';
             }
             if($_POST['reportin']==2){
                echo '<td>'.$value['V1'].'</td>';
                echo '<td>'.$value['V2'].'</td>';
                echo '<td>'.$value['V3'].'</td>';
                echo '<td>'.$value['V4'].'</td>';
                echo '<td>'.$value['V5'].'</td>';
                echo '<td>'.$value['V6'].'</td>';
                echo '<td>'.$value['V7'].'</td>';
                echo '<td>'.$value['V8'].'</td>';
                echo '<td>'.$value['V9'].'</td>';
                echo '<td>'.$value['V10'].'</td>';
                echo '<td>'.$value['V11'].'</td>';
                echo '<td>'.$value['V12'].'</td>';
             }
             
             
             if($_POST['reportin']==3){
                echo '<td>Value:'.number_format($value['V1'],2).' QTY:'.$value['Q1'].'</td>';
                echo '<td>Value:'.number_format($value['V2'],2).' QTY:'.$value['Q2'].'</td>';
                echo '<td>Value:'.number_format($value['V3'],2).' QTY:'.$value['Q3'].'</td>';
                echo '<td>Value:'.number_format($value['V4'],2).' QTY:'.$value['Q4'].'</td>';
                echo '<td>Value:'.number_format($value['V5'],2).' QTY:'.$value['Q5'].'</td>';
                echo '<td>Value:'.number_format($value['V6'],2).' QTY:'.$value['Q6'].'</td>';
                echo '<td>Value:'.number_format($value['V7'],2).' QTY:'.$value['Q7'].'</td>';
                echo '<td>Value:'.number_format($value['V8'],2).' QTY:'.$value['Q8'].'</td>';
                echo '<td>Value:'.number_format($value['V9'],2).' QTY:'.$value['Q9'].'</td>';
                echo '<td>Value:'.number_format($value['V10'],2).' QTY:'.$value['Q10'].'</td>';
                echo '<td>Value:'.number_format($value['V11'],2).' QTY:'.$value['Q11'].'</td>';
                echo '<td>Value:'.number_format($value['V12'],2).' QTY:'.$value['Q12'].'</td>';
             }
           }
        echo '</tr>';
                
     }
  
     echo '</table>';
    echo '<input type="button" onclick="tableToExcel(\'GL\',\'SalesByPRODUCT '. $toDate.'\')" value="Export to Excel"><div>';

   include('includes/footer.inc');
}

function GetQtyForPeriod($itemcode=""){
    global $db;
    
    for ($index = 1; $index < 13; $index++) {
        $month =(int) $index;
         
        $customDate = sprintf("%s-%s-%s", trim($_POST['year']),trim($month),1);
        $toDate = ConvertSQLDate($customDate);
        $periodno = GetPeriod($toDate,$db,false);
        
        $SQL = "select sum(SalesLine.`invoiceamount`) as OrderValue
      ,sum(SalesLine.`Quantity` * SalesLine.`PartPerUnit`) as `Quantity`
      from `SalesHeader` 
      join SalesLine on `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='10' and `SalesLine`.`code`='".$itemcode."' 
      and `SalesHeader`.`period` ='".$periodno."'";
         $ResultIndex = DB_query($SQL,$db);
        $row = DB_fetch_row($ResultIndex);
        $_SESSION['StockArray'][$itemcode]['Q'.$month] = $row[1] ;
    }
    
   
}


function GetValueForPeriod($itemcode=""){
    global $db;
    
    for ($index = 1; $index < 13; $index++) {
        $month =(int) $index;
         
        $customDate =sprintf("%s-%s-%s", trim($_POST['year']),trim($month),1);
    
        $toDate = ConvertSQLDate($customDate);
        $periodno = GetPeriod($toDate,$db,false);
        
         $SQL = "select 
             sum(SalesLine.`invoiceamount`) as OrderValue
             ,sum(SalesLine.`Quantity` * SalesLine.`PartPerUnit`) as `Quantity`
      from `SalesHeader` 
      join SalesLine on `SalesHeader`.`documentno`=SalesLine.`documentno` 
      where `SalesHeader`.`documenttype`='10' and `SalesLine`.`code`='".$itemcode."' 
      and `SalesHeader`.`period` ='".$periodno."'";
         $ResultIndex = DB_query($SQL,$db);
        $row = DB_fetch_row($ResultIndex);
        $_SESSION['StockArray'][$itemcode]['V'.$month] = $row[0] ;
    }
    
   
}



function GetProducts(){
      global $db;
      $array=array();
      $ResultIndex = DB_query("select itemcode,rtrim(stockmaster.descrip) as descrip  "
              . " from stockmaster where stockmaster.isstock_1=1 order by stockmaster.descrip asc",$db);
      while($row= DB_fetch_array($ResultIndex)){
          $array[]=$row;
      }
      
      foreach ($array as $row) {
           $_SESSION['StockArray'][$row['itemcode']]['Code'] = $row['itemcode'];
           $_SESSION['StockArray'][$row['itemcode']]['MyName'] = $row['descrip'];
           GetQtyForPeriod($row['itemcode']);
           GetValueForPeriod($row['itemcode']);
      }
     
}


?>