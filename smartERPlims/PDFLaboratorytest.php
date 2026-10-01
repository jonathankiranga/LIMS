<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/gradelabresults.inc');


If(Isset($_GET['SampleID'])){
    $documentno = $_GET['SampleID'];
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',$documenttype);
    $pdf->addInfo('Subject',$documenttype);
    $pdf->addInfo('Creator',_('SmartERP'));
       
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 15;
    $headerName = "LABORATORY TEST REPORT";
  
    $SQL=SPRINTF("SELECT 
           `ProductionMaster`.`Batchno`  
          ,`ProductionMaster`.`date`  
          ,`ProductionMaster`.`DateTestended`
          ,`ProductionMaster`.`Interpretation`
          ,`ProductionMaster`.`itemcode` as `SampleID`
          ,stockmaster.descrip
          ,`www_users`.`realname`
      FROM `ProductionMaster` 
      join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      left join `www_users` on `ProductionMaster`.`userid`=`www_users`.`userid`
      join `LabPostingDetail` on `LabPostingDetail`.`SampleID`=`ProductionMaster`.`itemcode`
     where `ProductionMaster`.`Batchno`='%s'",$documentno);
    $Results = DB_query($SQL,$db);
    $HeaderRow = DB_fetch_row($Results);
    DB_free_result($Results);
    
  include('includes/PDFTestResultsheader.inc');
 
    $SQL=SPRINTF("SELECT 
           `LabPostingDetail`.`SampleTypeID`
          ,`LabPostingDetail`.`ParameterID`
          ,`LabPostingDetail`.`Method`
          ,`LabPostingDetail`.`Limits_min`
          ,`LabPostingDetail`.`Limits_max`
          ,`LabPostingDetail`.`Results`
      FROM `LabPostingDetail` 
      where `LabPostingDetail`.`Documentno` ='%s'",$documentno);
     
    $ResultIndex=DB_query($SQL,$db);
     while($rows = DB_fetch_array($ResultIndex)){
        GetParameter($rows['SampleTypeID'],$rows['ParameterID']);
    }
     
     $ResultIndex= DB_query($SQL,$db);
     $No_of_rows = DB_num_rows($ResultIndex);
     DB_data_seek($ResultIndex,0);
     
     $YPos = ($firstrowpos - $line_height * 2.5);
     $FontSize = 8; $LineFooter = $YPos; $currentline = $YPos;  $countrow=0; $NumberOfRows=10;
     $No_of_rows = (($NumberOfRows>$No_of_rows)?$NumberOfRows:$No_of_rows);
  
     while($rows = DB_fetch_array($ResultIndex)){
          $countrow++;
          
          $pdf->SetFont('', '', 8);  $pdf->SetY($YPos * -1);  $Y = $pdf->GetY()-10 ;
          $NoStandardlimit = $ParameterGetarray[$rows['SampleTypeID']][$rows['ParameterID']]['NoStandardlimit'];
          $SAMPLENAME = $ParameterGetarray[$rows['SampleTypeID']][$rows['ParameterID']]['Parameter'];
          $ParameterName = html_entity_decode($SAMPLENAME);
     
          $LeftOvers = $pdf->writeHTMLCell(0 ,0 ,42 ,$Y ,$ParameterName);
          $LeftOvers = $pdf->addTextWrap(245, $YPos+5, 85, $FontSize, $rows['Method'],'left');
          $LeftOvers = $pdf->addTextWrap(350, $YPos+5, 85, $FontSize, $rows['Results'],'left');
          $LeftOvers = $pdf->addTextWrap(455, $YPos+5, 85, $FontSize, $rows['Limits_min'],'left');
          $LeftOvers = $pdf->addTextWrap(505, $YPos+5, 85, $FontSize, $rows['Limits_max'],'left');
          
          PutLines($YPos); $ULinePos = $YPos; $YPos -= ($line_height*1.5); $currentline = $YPos; $lastrow = AdjustLastLine($No_of_rows-$countrow);
          
          if($YPos < ($lastrow + $line_height)){
                  $pdf->line($Page_Width-$Right_Margin-10,$ULinePos,$Left_Margin+10,$ULinePos);
                  ////*Now do the bottom left corner 180 - 270 coming back west*/
                  $pdf->partEllipse($Left_Margin+10,$ULinePos+10,180,270,10,10);
                  /*Now do the bottom right corner */
                  $pdf->partEllipse($Page_Width-$Right_Margin-10,$ULinePos+10,270,350,10,10);
         
            if($No_of_rows>$countrow){ // Check if this is the last entry
               $FontSize = 10;
               include('includes/PDFTestResultsheader.inc');
 
               $YPos = ($firstrowpos - $line_height * 2);
               $FontSize = 8;  $LineFooter = $YPos; $currentline = $YPos;
            }
            
         } else {
             if(($No_of_rows-$countrow)==0){
                  $pdf->line($Page_Width-$Right_Margin-10,$ULinePos,$Left_Margin+10,$ULinePos);
                  ////*Now do the bottom left corner 180 - 270 coming back west*/
                  $pdf->partEllipse($Left_Margin+10,$ULinePos+10,180,270,10,10);
                  /*Now do the bottom right corner */
                  $pdf->partEllipse($Page_Width-$Right_Margin-10,$ULinePos+10,270,350,10,10);
             }else{
                 $pdf->line($Page_Width-$Right_Margin,$ULinePos,$Left_Margin,$ULinePos);
             }
        }
   }
  
  
  for ($index = $countrow; $index < 11; $index++) {
    PutLines($YPos); $ULinePos = $YPos; 
    $YPos -= ($line_height*1.5); $currentline = $YPos;
    $lastrow = AdjustLastLine($No_of_rows-$countrow);
    if($YPos < ($lastrow + $line_height)){
             $pdf->line($Page_Width-$Right_Margin-10,$ULinePos,$Left_Margin+10,$ULinePos);
             ////*Now do the bottom left corner 180 - 270 coming back west*/
             $pdf->partEllipse($Left_Margin+10,$ULinePos+10,180,270,10,10);
             /*Now do the bottom right corner */
             $pdf->partEllipse($Page_Width-$Right_Margin-10,$ULinePos+10,270,350,10,10);
     } else {
        if(($No_of_rows-$countrow)==0){
             $pdf->line($Page_Width-$Right_Margin-10,$ULinePos,$Left_Margin+10,$ULinePos);
             ////*Now do the bottom left corner 180 - 270 coming back west*/
             $pdf->partEllipse($Left_Margin+10,$ULinePos+10,180,270,10,10);
             /*Now do the bottom right corner */
             $pdf->partEllipse($Page_Width-$Right_Margin-10,$ULinePos+10,270,350,10,10);
        }else{
            $pdf->line($Page_Width-$Right_Margin,$ULinePos,$Left_Margin,$ULinePos);
        }
   }

    $countrow++;
  }
   
   include('includes/PDFTestResultsfooter.inc');
  
   $pdf->OutputD($_SESSION['DatabaseName'] . '_BITUMEN_TEST_REPORT_' .$documentno. '_' . date('Y-m-d').'.pdf');
   $pdf->__destruct();
     
} else {
    
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');   
$Title = _('Lab Test Results');
include('includes/header.inc'); 
  
echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Lab Test Results') .'" alt="" />' . ' ' . _('Lab Test Results') . '</p>';
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"  id="Journal"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';

  $SQL="SELECT 
       `ProductionMaster`.`Batchno`
      ,`ProductionMaster`.`date`
      ,`ProductionMaster`.`DateTestended`
      ,stockmaster.descrip
      ,`www_users`.`realname`
  FROM `ProductionMaster`
      join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      left join `www_users` on `ProductionMaster`.`userid`=`www_users`.`userid`
      where (`ProductionMaster`.`testreport`='Review' or `ProductionMaster`.`testreport`='Completed')
      order by `ProductionMaster`.`date` desc";
  
  
  
   echo '<div class="container">'
       . '<table class="register display table-bordered" style="width:100%"><thead><tr>'
       . '<th>Batch No.</th>'
       . '<th>Date Received</th>'
       . '<th>Date Test Completed</th>'
       . '<th>Product</th>'
       . '<th>Lab Test Done By</th>'
       . '</tr></thead><tbody>';
      
      $ResultIndex = DB_query($SQL,$db);
      while($row = DB_fetch_array($ResultIndex)){
          echo sprintf('<tr><td><a href="%s?SampleID=%s">%s</a></td>'
                  . '<td>%s</td><td>%s</td><td><a href="%s?Productionid=%s">%s</a></td><td>%s</td>'
                  . '</tr>',$pge,$row['Batchno'], $row['Batchno'],
                  ConvertSQLDate($row['date']),
                  ConvertSQLDate($row['DateTestended']),
                  'PDFproductionsummary.php',$row['Batchno'],$row['descrip'],
                  $row['realname']
                  );
      }
      
echo '</tbody><tfoot><tr>'
       . '<th>Batch No.</th>'
       . '<th>Date Received</th>'
       . '<th>Date Test Completed</th>'
       . '<th>Product</th>'
       . '<th>Lab Test Done By</th>'
       . '</tr></tfoot></table></div>';
echo '</form>';

include('includes/footer.inc');
}

?>