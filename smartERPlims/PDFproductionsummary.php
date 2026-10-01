<?php
include('includes/session.inc');
$Title = _('Production Summary');

if(isset($_GET['Productionid'])){

    $stockmaster = array();
    $ResultIndex = DB_query("SELECT `stockmaster`.`barcode`,`stockmaster`.`itemcode`,
     `stockmaster`.`descrip`, `unit`.`descrip` as `UOM`,`stockmaster`.`averagestock`
     FROM `stockmaster` left join `unit` on `stockmaster`.`units`=`unit`.`code`",$db);
    while ($row1 = DB_fetch_array($ResultIndex)) {
        $Itecode=trim($row1['itemcode']);
        $stockmaster[$Itecode]=$row1;
    }

    $stockmaster['H2O']=array('barcode'=>'WATER','itemcode'=>'H2O','descrip'=>'Water','UOM'=>'LTS','averagestock'=>'1');
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
        
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
    
    $SQL=SPRINTF("SELECT 
           `ProductionMaster`.`Batchno`  
          ,`ProductionMaster`.`date`  
          ,`ProductionMaster`.`DateTestended`
          ,`ProductionMaster`.production
          ,`ProductionMaster`.`itemcode`  
          ,stockmaster.descrip
          ,`www_users`.`realname`
      FROM `ProductionMaster` 
      join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      left join `www_users` on `ProductionMaster`.`userid`=`www_users`.`userid`
      where `ProductionMaster`.`Batchno`='%s'",$_GET['Productionid']);
    $ResultIndex=DB_query($SQL, $db);
    $HeaderRow=DB_fetch_row($ResultIndex);
    
    $headerName = "PRODUCTION REPORT";
     include('includes/PDFproductionheader.inc');
     $FontSize = 12;
     $YPos=$firstrowpos-20;
     
     $TOTALUNITS=0; $TOTALCOST=0;
     $SQL=sprintf("SELECT `qty` ,(`qty`*`cost`) as cost FROM `ProdcutionMasterLine` where `Batchno`='%s'",$_GET['Productionid']);
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $TOTALUNITS +=$rows['qty'];
         $TOTALCOST +=$rows['cost'];
     }
    
     $SQL=sprintf("SELECT `itemcode` ,`qty` ,`cost` FROM `ProdcutionMasterLine` where `Batchno`='%s'",$_GET['Productionid']);
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
          $Itecode= trim($rows['itemcode']);
          $percent= number_format(($rows['qty']/$TOTALUNITS)*100,0);
          $cost =(($Itecode=='H2O')?$stockmaster[$Itecode]['averagestock'] :number_format($rows['cost'],2));
          $TotalLineCost=($cost*$rows['qty']);
          
          $LeftOvers = $pdf->addTextWrap(42, $YPos- $line_height,200, $FontSize,$stockmaster[$Itecode]['descrip'],'left');
          $LeftOvers = $pdf->addTextWrap(245,$YPos- $line_height,50, $FontSize,$rows['qty'],'right');
          $LeftOvers = $pdf->addTextWrap(300,$YPos- $line_height,50, $FontSize,$stockmaster[$Itecode]['UOM'],'right');
          $LeftOvers = $pdf->addTextWrap(350,$YPos- $line_height,50, $FontSize,$percent,'right');
          $LeftOvers = $pdf->addTextWrap(400,$YPos- $line_height,50, $FontSize,$cost,'right');
          $LeftOvers = $pdf->addTextWrap(480,$YPos- $line_height,50, $FontSize,$TotalLineCost,'right');
          
              
         $YPos -= $line_height * 2;
         if($YPos<$Bottom_Margin+100){
             $PageNumber++;
             include('includes/PDFproductionheader.inc');
              $YPos=$firstrowpos;
         }
                
     }
     
    $FontSize=10;
    $YPos -= $line_height * 2;
    $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
    $YPos -= $line_height * 2;
    $Itecode=trim($HeaderRow[4]);
    $cost=$TOTALCOST /$TOTALUNITS;

    $LeftOvers = $pdf->addTextWrap(42, $YPos- $line_height,200, $FontSize,$stockmaster[$Itecode]['descrip'],'left');
    $LeftOvers = $pdf->addTextWrap(245,$YPos- $line_height,50, $FontSize,$TOTALUNITS,'right');
    $LeftOvers = $pdf->addTextWrap(300,$YPos- $line_height,50, $FontSize,$stockmaster[$Itecode]['UOM'],'right');
    $LeftOvers = $pdf->addTextWrap(400,$YPos- $line_height,50, $FontSize,number_format($cost,2),'right');
    $LeftOvers = $pdf->addTextWrap(480,$YPos- $line_height,50, $FontSize,number_format($TOTALCOST,2),'right');
    $YPos -= $line_height * 2;
    $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);

    $pdf->OutputD($_SESSION['DatabaseName'] . 'Production Summary' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
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
      ,`ProductionMaster`.`testreport`
  FROM `ProductionMaster`
      join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      left join `www_users` on `ProductionMaster`.`userid`=`www_users`.`userid` 
      order by `ProductionMaster`.`date` desc limit 500";
 
   echo '<div class="container">'
       . '<table class="register display table-bordered" style="width:100%"><thead><tr>'
       . '<th>LabTest<br/>Batch No</th>'
       . '<th>Date Received</th>'
       . '<th>Date Test Completed</th>'
       . '<th>Product <br/>Report</th>'
       . '<th>Lab Test Done By</th>'
       . '</tr></thead><tbody>';
      
      $ResultIndex = DB_query($SQL,$db);
      while($row = DB_fetch_array($ResultIndex)){
          if(mb_strstr($row['testreport'],'Review')=='Review' 
                  or mb_strstr($row['testreport'],'Completed')=='Completed'){ 
              
               echo sprintf('<tr><td><a href="%s?SampleID=%s">%s</a></td>'
                  . '<td>%s</td><td>%s</td><td><a href="%s?Productionid=%s">%s</a></td><td>%s</td>'
                  . '</tr>','PDFLaboratorytest.php',$row['Batchno'],$row['Batchno'],
                  ConvertSQLDate($row['date']),
                  ConvertSQLDate($row['DateTestended']),
                  $pge,$row['Batchno'],$row['descrip'],
                  $row['realname']);
          }else{ 
                  echo sprintf('<tr>'
                  . '<td>%s</td>'
                  . '<td>%s</td>'
                  . '<td>%s</td>'
                  . '<td><a href="%s?Productionid=%s">%s</a></td>'
                  . '<td>%s</td>'
                  . '</tr>',$row['Batchno'],
                  ConvertSQLDate($row['date']),
                  ConvertSQLDate($row['DateTestended']),
                  $pge,$row['Batchno'],$row['descrip'],
                  $row['realname']);
        
           }
      }
      
echo '</tbody><tfoot><tr>'
       . '<th>LabTest<br/>Batch No</th>'
       . '<th>Date Received</th>'
       . '<th>Date Test Completed</th>'
       . '<th>Product <br/>Report</th>'
       . '<th>Lab Test Done By</th>'
       . '</tr></tfoot></table></div>';
echo '</form>';

include('includes/footer.inc');  
}

?>
