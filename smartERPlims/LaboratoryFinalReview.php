<?php
include('includes/session.inc');
$Title = _('Lab Data Entry');
include('includes/header.inc'); 
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Lab Data Entry') .'" alt="" />' . ' ' . _('Lab Data Entry') . '</p>';

if(isset($_POST['update'])){
  $SQLArray = sprintf("UPDATE `ProductionMaster` SET "
          . "`Interpretation`= '%s',"
          . "`Passed`= '%s',"
          . "`Status`= 4,"
          . "`testreport`=%s  WHERE `Batchno`= '%s' ",
  $_POST['interpretation'],$_POST['pass'],(($_POST['pass']==3)?'NULL':"'Completed'"),$_POST['documentno']);
       DB_Txn_Begin($db);
            DB_query($SQLArray, $db);
        If(DB_error_no($db)==0){
            DB_Txn_Commit($db);
            prnMsg('Lab results have been saves sucessfully','info');
            Unset($_POST);
        }else{
            DB_Txn_Rollback($db);
        }
 
}

If(Isset($_GET['DocumentNo'])){
  $documentno=$_GET['DocumentNo'];
}else{
  $documentno=$_POST['documentno'];
}

If(isset($documentno)){
    
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="labform">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '<div class="container"><table class="table table-bordered">';
echo '<tr><td>Batch No:</td><td><input tabindex="2" type="text" name="documentno" value="'.$documentno.'" size="10" readonly="readonly" /></td></tr>';

echo '<tr><td colspan="4"><table class="table table-bordered">'
        . '<th>SAMPLE TYPE</TH>'
        . '<th>PARAMETER</TH>'
        . '<th>METHOD</TH>'
        . '<th>Test<BR/> Results</TH>'
        . '<th>STANDARD<BR/>(Min Limits)</TH>'
        . '<th>STANDARD<BR/>(Max Limits)</TH>'
        . '<th>Posted<BR/>By</TH>'
        . '<th>Date & Time</TH>'
        . '</tr>';
$Ri=0;

$SQL="SELECT 
       `LabPostingDetail`.`SampleID`
      ,`LabPostingDetail`.`SampleTypeID`
      ,`LabPostingDetail`.`ParameterID`
      ,`LabPostingDetail`.`Method`
      ,`LabPostingDetail`.`Limits_min`
      ,`LabPostingDetail`.`Limits_max`
      ,`LabPostingDetail`.`Results`
      ,`www_users`.`realname`
      ,`LabPostingDetail`.`lastdatetime`
  FROM `LabPostingDetail` 
  left join `www_users` on `LabPostingDetail`.`lastuserid`=`www_users`.`userid`
  where `DocumentNo`='".$documentno."'";
 
      $ResultIndex = DB_query($SQL,$db);
      while($row = DB_fetch_array($ResultIndex)){
        $SamName = SampleName($row['SampleTypeID']);
        $ParaDetailsArray = GetParameter($row['SampleTypeID'],$row['ParameterID']);
        
          echo sprintf('<tr>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>'.$row['Results'].'</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '<td>%s</td>'
                . '</tr>',
                $SamName,
                html_entity_decode($ParaDetailsArray[0]),
                html_entity_decode($ParaDetailsArray[3]),
                  
                html_entity_decode($ParaDetailsArray[1]),
                html_entity_decode($ParaDetailsArray[2]),
                $row['realname'],
                ConvertSQLDateTime($row['lastdatetime']));
                $Ri++;
      }

echo '</td></tr>';
echo '<tr><td>Interpretation of the displayed results</td>'
. '<td  colspan="6"><textarea  rows="2" cols="70" name="interpretation">'.$_POST['interpretation'].'</textarea></td></tr>';
echo '<tr><td >Pass / Fail </td><td colspan="6"><select name="pass" required="required">'
        . '<option value="1" '.($_POST['pass']==1?'selected="selected"':'').'>Passed</option>'
        . '<option value="2" '.($_POST['pass']==2?'selected="selected"':'').'>Failed</option>'
        . '<option value="3" '.($_POST['pass']==3?'selected="selected"':'').'>Cancelled</option>'
        . '</select></td></tr>';
echo '<tr><td colspan="7"><input type="submit"  name="update"  value="Post Review" onclick="return confirm(\''._('Are you sure you wish to Post this Test?').'\');"/></td></tr>';
echo '</table></div>';
echo '</div></form>';

} else {


    $SQL="SELECT 
       `ProductionMaster`.`Batchno`
      ,`ProductionMaster`.`date`
      ,`www_users`.`realname`
      ,`ProductionMaster`.`itemcode`
      ,stockmaster.descrip
  FROM `ProductionMaster`
     join stockmaster on stockmaster.itemcode=`ProductionMaster`.`itemcode`
      left join `www_users` on `ProductionMaster`.`userid`=`www_users`.`userid`
      where `ProductionMaster`.`testreport`='Review' 
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
          
          echo sprintf('<tr><td><a href="%s?DocumentNo=%s">%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', 
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
      $SQL="SELECT  `Parameter` ,`Limits_min`,`Limits_max`,`Method`
      FROM `LaboratoryStandards`  where `itemcode`='".$Id."'  and `ParameterID`='".$PID."'";
      $ResultIndex = DB_query($SQL,$db);
      $return = DB_fetch_row($ResultIndex);
      
    return $return;
}

?>