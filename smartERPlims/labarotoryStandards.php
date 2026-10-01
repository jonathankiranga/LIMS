<?php
include('includes/session.inc');
$Title = _('TESTING STANDARDS');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if(isset($_POST['sampleID'])){
   $sampleID = $_POST['sampleID'];
}elseif(isset($_GET['editcode'])){
   $sampleID = $_GET['editcode'];
}elseif(isset($_GET['code'])){
   $sampleID = $_GET['code'];
}

if(isset($_POST['OrigianlParameterID'])){
   $ParameterID = $_POST['OrigianlParameterID'];
}elseif(isset($_GET['ParameterID'])){
   $ParameterID = $_GET['ParameterID'];
}
				
 
$PageHeaderDescription ='';
if(isset($_POST['save'])){
     $_POST['ParameterID']  = GetNextTransNo(31,$db);
     $_POST['Parameter']= htmlentities($_POST['Parameter'],ENT_QUOTES,'UTF-8');
    DB_query(sprintf("Insert into `LaboratoryStandards`"
            . "(`itemcode`,`ParameterID`,`Parameter`,`Limits_min`,Limits_max,`Method`,`NoStandardlimit`) "
            . " values ('%s','%s','%s','%s','%s','%s','%s')",$_POST['sampleID'],$_POST['ParameterID'],
            $_POST['Parameter'],$_POST['Limits_min'],$_POST['Limits_max'],$_POST['Method'],$_POST['NoStandardlimit']), $db);
}

if(isset($_POST['edit'])){
    if($_POST['edit']=='Edit Record'){
         $_POST['Parameter']= htmlspecialchars($_POST['Parameter'],ENT_QUOTES,'UTF-8');
    DB_query(sprintf("update `LaboratoryStandards` set "
            . "`itemcode`='%s', "
            . "`ParameterID`='%s' , "
            . "`Parameter`='%s' , "
            . "`Limits_min`='%s' , "
            . "`Limits_max`='%s' , "
            . "`Method`='%s' , "
            . "`NoStandardlimit`='%s' "
            . " where `itemcode`='%s' and `ParameterID`='%s' ",
            $_POST['sampleID'],$ParameterID, $_POST['Parameter'],
            $_POST['Limits_min'],$_POST['Limits_max'],$_POST['Method'],
            $_POST['NoStandardlimit'],$sampleID,$ParameterID), $db);
    }
    
    if($_POST['edit']=='Delete'){
        $sql="Select * from `LabPostingDetail` where `ParameterID`='".$ParameterID."'";
        $Result=DB_query($sql,$db);
        if(DB_num_rows($Result)==0){
            DB_query("Delete from `LaboratoryStandards`  "
                    . " where `itemcode`='".$sampleID."' "
                   . " and `ParameterID`='".$ParameterID."'", $db);
        }else{
            prnMsg('This Sample Type can not be deleted because its in use');
        }
    }
}

if(isset($_POST['save'])){
    if(isset($_POST['save'][1])){
         $_GET['code']= $sampleID ;
         prnMsg('You have successfuly created '.$_POST['ParameterID'].' :'.html_entity_decode($_POST['Parameter']));
         unset($_POST);
    }
}


if(isset($ParameterID)){
    $sql="SELECT `ParameterID`,`Parameter`,`Limits_min`,`Limits_max`,`Method`,`NoStandardlimit`  
    FROM `LaboratoryStandards`  where `ParameterID`='".$ParameterID."' and `itemcode`='".$sampleID."'";
    $results=DB_query($sql,$db);
    $rowse=DB_fetch_row($results);
    $_POST['ParameterID'] = $rowse[0];
    $_POST['Parameter']   = $rowse[1];
    $_POST['Limits_min']  = $rowse[2];
    $_POST['Limits_max']  = $rowse[3];
    $_POST['Method']      = $rowse[4];
    $_POST['NoStandardlimit']= $rowse[5];
}

 if(isset($sampleID)){      
    $sql="SELECT `descrip` FROM `stockmaster` where `itemcode`='".$sampleID."'";
    $results = DB_query($sql,$db);
    $rowse = DB_fetch_row($results);
    $PageHeaderDescription =_('Standards For '). $rowse[0];
}



echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('TESTING STANDARDS') . '" alt="" />' . ' ' . _('TESTING STANDARDS') . '</p>';
echo '<div class="container"><form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($ParameterID)){
  echo '<input type="hidden" name="OrigianlParameterID" value="' .$ParameterID. '" />';
}

if(isset($sampleID)){
  echo '<input type="hidden" name="sampleID" value="' . $sampleID . '" />';
}

?>

<table class="table-condensed"><tr><td valign="top"><?php ListSavedSamples(); ?></td>
        <td valign="top" rowspan="2">
      <table class="table-condensed"><tr><td valign="top"><?php DataEntry(); ?></td></tr>
          <tr><td valign="top"><?php Defaultlist(); ?></td></tr></table>
</td></tr></table>

<?php

echo '</form></div>' ;
include('includes/footer.inc');


Function DataEntry(){
    global $db,$PageHeaderDescription,$ParameterID,$sampleID;
  
    $return='';
    
       If(isset($sampleID)){
        
            if(isset($ParameterID)){
                $return= '<P>'.$PageHeaderDescription.'</P><table class="table-bordered">';
                $return .= '<tr><td>Parameter Name</td><td><textarea name="Parameter" rows="2" cols="70">'.$_POST['Parameter'].'</textarea></td></tr>';
                $return .= '<tr><td>Method</td><td><input type="text" maxlength="20" size="20" name="Method" value="'.$_POST['Method'].'"/></td></tr>';
                $return .= '<tr><td>Standard Min</td><td><input type="text" maxlength="20" size="10" name="Limits_min" value="'.$_POST['Limits_min'].'"/></td></tr>';
                $return .= '<tr><td>Standard Max</td><td><input type="text" maxlength="20" size="10" name="Limits_max" value="'.$_POST['Limits_max'].'"/></td></tr>';
                $return .= '<tr><td>Standard Has No limit defined (True ?)</td><td>'.GetNullStandard().'</td></tr>';
                $return .= '</table></td></tr><tr>';
                $return .= '<td><input type="submit" name="edit" value="'._('Edit Record').'"/>';
                $return .= '<input type="submit" name="edit" value="'._('Delete').'"/></td>';
            }else{
                $return= '<P>'.$PageHeaderDescription.'</P><table class="table-bordered">';
                $return .= '<tr><td>Parameter Name</td><td><textarea name="Parameter" rows="2" cols="70">'.$_POST['Parameter'].'</textarea></td></tr>';
                $return .= '<tr><td>Method</td><td><input type="text" maxlength="20" size="20" name="Method" value="'.$_POST['Method'].'"/></td></tr>';
                $return .= '<tr><td>Standard Min</td><td><input type="text" maxlength="20" size="10" name="Limits_min" value="'.$_POST['Limits_min'].'"/></td></tr>';
                $return .= '<tr><td>Standard Max</td><td><input type="text" maxlength="20" size="10" name="Limits_max" value="'.$_POST['Limits_max'].'"/></td></tr>';
                $return .= '<tr><td>Standard Has No limit defined (True ?)</td><td>'.GetNullStandard().'</td></tr>';
                $return .= '</table></td></tr><tr>';
                $return .= '<td><input type="submit" name="save" value="'._('Add New & Close').'"/>';
                $return .= '<input type="submit" name="save" value="'._('Add New & Continue').'"/></td>';
            }
         $return .= '</tr>';
        }

    echo $return ;
    
}

Function ListSavedSamples(){
    global $sampleID,$db;
    
    $return='';
    If(isset($sampleID)){
    $sql="SELECT "
            . "`ParameterID`,"
            . "`Parameter`,"
            . "`Limits_min`,"
            . "`Limits_max`,"
            . "`Method`,"
            . "`NoStandardlimit` "
       . " FROM `LaboratoryStandards`  where `itemcode`='".$sampleID."'";
    $results=DB_query($sql,$db);
    while($rows=DB_fetch_array($results)){
        $LaboratoryStandards[]=$rows;
    }
    
    $return= '<table class="table-bordered table-condensed">'
        . '<tr><th>Parameter ID</th><th>Parameter</th><th>Standard<br/>Limits</th><th>Method</th></tr>';

    foreach ($LaboratoryStandards as $key => $rows) {
        
            $return .= sprintf('<tr>'
            . '<td>'
            . '<a href="%s?ParameterID=%s&editcode=%s">Edit parameter %s</a></td>'
            . '<td>%s</td>'
            . '<td>%s %s</td>'
            . '<td>%s</td>'
             . '</tr>',
            htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') ,
            $rows['ParameterID'],
            $sampleID,
            $rows['ParameterID'],
            html_entity_decode($rows['Parameter']).$showCount,
            ' Min '.html_entity_decode($rows['Limits_min']).' Max '.html_entity_decode($rows['Limits_max']),
           ($rows['NoStandardlimit']==1?'(No Limits Defined)':''),
            $rows['Method']
            );
    }
    $return .= '</table>';
} 
    echo $return;
}

Function Defaultlist(){
    global $db;
    
    $sql="SELECT itemcode,descrip FROM stockmaster where isstock_1=1 or isstock_4=1 or isstock_5=1 ";
    $results=DB_query($sql,$db);
    while($rows=DB_fetch_array($results)){
        $stockmaster[]=$rows;
    }
    
    $return= '<table class="table-bordered table-condensed"><tr><th>Code</th><th>Butumen Grade </th></tr>';

    foreach ($stockmaster as $rows) {
             $pas = getChildren($rows['itemcode']);
             if($pas > 0){
                 $showCount =' Records('.$pas.')';
             }else{
                  $showCount ='';
             }
    
            $return.= sprintf('<tr><td><a href="%s?code=%s">Select %s</a></td><td>%s</td></tr>',
            htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') ,
            $rows['itemcode'],$rows['itemcode'].$showCount,$rows['descrip']);
    }
    $return.= '</table>';
    echo  $return;
}

function GetstockCategories(){
   $array=array();
   $array[1]="Chemical"; $array[2]="Microbiological";

   $option='<select name="Chemical">';
   foreach ($array as $value=>$key) {
        $option .= sprintf('<option value="%s" %s>%s</option>',$value, (($_POST['Chemical']==$value)?'selected="selected"':""), trim($key));
    }

    $option .='</select>';

   return $option;
}

function GetNullStandard(){
   $array=array();
   $array[0]="FALSE"; $array[1]="TRUE";

   $option='<select name="NoStandardlimit">';
    foreach ($array as $value => $key) {
        $selected=(($_POST['NoStandardlimit']==$value)?'selected="selected"':"");
        $option .= sprintf('<option value="%s" %s>%s</option>',$value,$selected,trim($key));
    }

    $option .='</select>';

   return $option;
}
    
    
function getChildren($ParameterID){
global $db;    

     $sql="Select count(*) from `LaboratoryStandards` where `itemcode`='".$ParameterID."'";
     $Result=DB_query($sql,$db);
     $paracount= DB_fetch_row($Result);
 return  (int)$paracount[0];
}
    
?>