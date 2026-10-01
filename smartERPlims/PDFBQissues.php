<?php
include('includes/session.inc');
$Title = _('Production Summary');

if(isset($_GET['DocNo'])){
    
$SQL="SELECT 
       `ProdcutionMasterLine`.`Batchno`,
       `ProdcutionMasterLine`.`itemcode`,
       `ProdcutionMasterLine`.`qty`,
       `ProdcutionMasterLine`.`cost`,
       `stockmaster`.`descrip`,
       `stockmaster`.`partperunit`,
            (select rtrim(`unit`.`descrip`) 
            from `unit` join stockmaster SM on SM.subunits=`unit`.code 
            and SM.itemcode=ProdcutionMasterLine.itemcode) 
            as UOMDesc ,
      `ProductionMaster`.`date`,
      `ProductionMaster`.`userid`
   FROM `ProdcutionMasterLine` 
   join stockmaster on `ProdcutionMasterLine`.itemcode=stockmaster.itemcode 
   join `ProductionMaster` on `ProductionMaster`.`Batchno`=`ProdcutionMasterLine`.`Batchno`
  where `ProdcutionMasterLine`.`Batchno`='".$_GET['DocNo']."'  and `ProdcutionMasterLine`.`qty` > 0";
    
    $ResultIndex=DB_query($SQL, $db);
    $headerrow=DB_fetch_row($ResultIndex);
    $documenttype=_('Issue Stock');
    $PaperSize='A4';
    include('includes/PDFStarter.php');
        
    $pdf->addInfo('Title',$documenttype);
    $pdf->addInfo('Subject',$documenttype);
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFissuesheader.inc');
     $FontSize = 12;
     $YPos=$firstrowpos-20;
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['descrip'],'left');
          $LeftOvers = $pdf->addTextWrap(300, $YPos,250, $FontSize, $rows['UOMDesc'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, number_format($rows['cost'],2),'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, number_format($rows['qty'],0),'right');
              
         $YPos -= $line_height * 2;
         if($YPos<$Bottom_Margin+100){
             $PageNumber++;
             include('includes/PDFissuesheader.inc');
             $YPos=$firstrowpos;
         }
                
     }
  
    $pdf->OutputD($_SESSION['DatabaseName'] . 'Stock Issues' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
} else {
    
    
include('includes/header.inc');

echo '<p class="page_title_text">'
   . '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Stock Issues') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';

$SQL="SELECT 
       `batchno`,`date`,`userid`
  FROM `ProductionMaster`  
  order by date desc";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Batch No</th>'
             . '<th>Date</th>' 
             . '<th>User</th>'
             . '</tr>';
  
    while($row=DB_fetch_array($Result)){
        echo '<tr>';
        echo sprintf('<td><a href="%s?DocNo=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['batchno'],$row['batchno']);
        echo sprintf('<td>%s</td>',is_null($row['date'])?'': ConvertSQLDate($row['date']));
        echo sprintf('<td>%s</td>',$row['userid']);
        echo '</tr>';
  }
        
echo '</table><br/></div></form>';

include('includes/footer.inc');
    
    
    
}

?>