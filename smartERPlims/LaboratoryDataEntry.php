<?php
include('includes/session.inc');
$Title = _('Lab Data Entry');
include('includes/header.inc'); 
include('includes/SQL_CommonFunctions.inc');
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Lab Data Entry') .'" alt="" />' . ' ' . _('Lab Data Entry') . '</p>';

if(isset($_POST['update'])){
   include('production/labtestdataentry.inc');  
}

If(Isset($_GET['SampleID'])){
  $documentno=$_GET['SampleID'];
}elseif(isset($_POST['documentno'])){
  $documentno=$_POST['documentno'];
}
   
If(isset($documentno)){
    
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="labform">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<div><table class="table-bordered">';
echo '<tr><td>Batch No</td><td><input tabindex="2" type="text" name="documentno" value="'.$documentno.'" size="10" readonly="readonly" /></td></tr>';
echo  '<tr><td>Date the Test ended</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" '
        . 'name="Endeddate" size="11" maxlength="10" readonly="readonly" value="' . ConvertSQLDate(Date('Y-m-d H:i:s')). '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';

$SQL="SELECT 
       `Batchno`
      ,`date`
      ,`averagestock`
      ,`itemcode`
  FROM `ProductionMaster`
      where `Batchno`='".$documentno."'";

    $ResultIndex = DB_query($SQL,$db);
    $row = DB_fetch_row($ResultIndex);  
    $itemcode = $row[3];
    $SamName = SampleName($itemcode);
      
    $ParaDetailsArray=array();
      
echo '<tr><td colspan="4">'
        . '<table class="table-bordered">'
        . '<tr>'
        . '<th>SAMPLE TYPE</TH>'
        . '<th>PARAMETER</TH>'
        . '<th>METHOD</TH>'
        . '<th>Test<BR/>Results</TH>'
        . '<th colspan="2">STANDARD Limits</TH>'
        . '</tr><tr>'
        . '<td></Td>'
        . '<td></Td>'
        . '<td></Td>'
        . '<td></Td>'
        . '<td>Min</Td>'
        . '<td>max</Td>'
        . '</tr>';

      $SQL="SELECT `ParameterID`,`Parameter`,`Limits_min`,`Limits_max`,`Method`,`NoStandardlimit` 
            FROM `LaboratoryStandards`  where `itemcode`='". $itemcode."'";
    
      $ResultIndex = DB_query($SQL,$db);
     while($return = DB_fetch_array($ResultIndex)){
         $ParaDetailsArray[]=$return;
     }
      
     $Ri=0;
     foreach ($ParaDetailsArray as  $key => $ParaDetails) {
           echo sprintf('<tr>'
                . '<td><input type="hidden" name="SampleID['.$Ri.']" value="'.$itemcode.'"/>%s :'
                . '<input type="hidden" name="ParameterID['.$Ri.']" value="'.$ParaDetails['ParameterID'].'"/>%s</td>'
                . '<td>%s</td>'
                . '<td>'.$ParaDetails['Method'].'<input type="hidden" name="Method['.$Ri.']" value="%s" /></td>'
                . '<td><input type="text" name="results['.$Ri.']" value="%s" size="10"/></td>'
                . '<td><input type="hidden" name="Limits_min['.$Ri.']" value="%s" />%s</td>'
                . '<td><input type="hidden" name="Limits_max['.$Ri.']" value="%s" />%s</td>'
                . '</tr>',
                $itemcode,
                $SamName,
                html_entity_decode($ParaDetails['Parameter']),
                html_entity_decode($ParaDetails['Method']),
                GetRecordValue($itemcode,$ParaDetails['ParameterID']),
                $ParaDetails['Limits_min'],
                $ParaDetails['Limits_min'],
                $ParaDetails['Limits_max'],
                $ParaDetails['Limits_max']);
           
         $Ri++;
      }

echo '</td></tr>';
echo '<tr><td colspan="2"></td><td>'
. '<input type="submit" name="update" value="Save Changes"/></td><td>'
. '<input type="submit" name="update" value="Finished All Tests"  onclick="return confirm(\''._('Are you sure you wish to Post this Test?').'\');"/></td></tr>';
echo '</table></div>';
echo '</div></form>';

} else{

    $SQL="SELECT 
       `ProductionMaster`.`Batchno`
      ,`ProductionMaster`.`date`
      ,`www_users`.`realname`
      ,`ProductionMaster`.`itemcode`
      ,stockmaster.descrip
  FROM `ProductionMaster`
      join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      left join `www_users` on `ProductionMaster`.`userid`=`www_users`.`userid`
      where `ProductionMaster`.`testreport` is null and `ProductionMaster`.`itemcode` is not null 
      order by `ProductionMaster`.`date` desc";

    echo '<div>'
       . '<table class="table table-bordered">'
       . '<tr>'
       . '<th>Batch No</th>'
       . '<th>Product</th>'
       . '<th>Date Received</th>'
       . '<th>Produced By</th>'
       . '</tr>';
      
      $ResultIndex = DB_query($SQL,$db);
      while($row = DB_fetch_array($ResultIndex)){
          
          echo sprintf('<tr><td><a href="%s?SampleID=%s">%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', 
                  $pge,$row['Batchno'],$row['Batchno'],$row['descrip'],ConvertSQLDate($row['date']), $row['realname']
                  );
      }
      
    echo '</table></div>';
}

include('includes/footer.inc');

Function SampleName($SampleTypeID){
    Global $db;
    
    $SQL="SELECT `descrip` FROM `stockmaster` where `itemcode`='".$SampleTypeID."'";
    $ResultIndex = DB_query($SQL,$db);
    $return = DB_fetch_row($ResultIndex);
    
    return $return[0];
}

Function GetParameter($Id,$PID){
      Global $db;
      
      $SQL="SELECT `ParameterID`,`Parameter`,`Limits_min`,`Limits_max`,`Method`,`NoStandardlimit` "
       . " FROM `LaboratoryStandards`  where `itemcode`='".$Id."'  and `ParameterID`='".$PID."'";
      $ResultIndex = DB_query($SQL,$db);
      $return = DB_fetch_row($ResultIndex);
      
    return $return;
}

Function GetRecordValue($itemcode,$ParaDetails){
    global $db;
    
   $SQL=sprintf("SELECT `Results` from `LabPostingDetail` WHERE `SampleID`='%s' and  `ParameterID`='%s'",$itemcode,$ParaDetails) ;
   $ResultIndex = DB_query($SQL,$db);
   $row = DB_fetch_row($ResultIndex);
   return   (isset($row[0])?$row[0]:'');
}


?>