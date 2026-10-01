<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Dimension Types');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Dimension Types') .'" alt="" />'
. ' ' . _('Dimension Types') . '</p>';

$Blocked=array();
$Blocked[0]="No";
$Blocked[1]="Yes";
    
$BlockedStatus=array();
$BlockedStatus[0]="Active";
$BlockedStatus[1]="Closed";

if(isset($_POST['id'])){
    $idtype=$_POST['id'];
}elseif($_GET['id']){
     $idtype=$_GET['id'];
}


if(isset($_GET['code'])){
    $editcode=$_GET['code'];
}elseif(isset($_POST['codeedit'])){
    $editcode=$_POST['codeedit'];
}


if(isset($_GET['editid'])){
    $editid=$_GET['editid'];
}elseif(isset($_POST['idedit'])){
    $editid=$_POST['idedit'];
}


if($_POST['fid']=='dty'){
    if(isset($_POST['idedit'])){
        DB_query("Update `DimensionSetUp` set Dimension_type='".$_POST['Dimension_type']."'  where id='".$_POST['idedit']."'", $db);
    }else{
         DB_query("insert into `DimensionSetUp` (Dimension_type) values ('".$_POST['Dimension_type']."')", $db);
    }
    unset($_POST);
    unset($editcode);
}


if($_POST['fid']=='dval'){
    if(isset($_POST['codeedit'])){
        DB_query("Update `Dimensions` set `Dimension`='".$_POST['Dimension']."',`Blocked`='".$_POST['Blocked']."' where code='".$_POST['codeedit']."' and id='".$_POST['id']."'", $db);
    }else{
        DB_query("insert into `Dimensions` (`Dimension`,`Blocked`,`code`,`id`)"
                . " values ('".$_POST['Dimension']."',".$_POST['Blocked'].",'".$_POST['code']."','".$_POST['id']."')", $db);
    }
     unset($_POST);
     unset($editcode);
}


if(isset($idtype)){
  
$ResultIndex=DB_query("SELECT `Dimension_type` FROM `DimensionSetUp` where id='".$idtype."'", $db);
$rows=DB_fetch_row($ResultIndex);
                    
echo '<p class="page_title_text">'. ' ' .$rows[0] . '</p>';
               
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="id" value="' .$idtype. '" />';
echo '<input type="hidden" name="fid" value="dval" />';

    if(isset($editcode)){
        echo '<input type="hidden" name="codeedit" value="' .$editcode. '" />';
        $ResultIndex2=DB_query("SELECT `code`,`Dimension`,`Blocked` FROM `Dimensions` where id='".$idtype."' and code='".$editcode."'", $db);
        $rows=DB_fetch_row($ResultIndex2);
            $_POST['code']=$rows[0];
            $_POST['Dimension']=$rows[1];
            $_POST['Blocked']=$rows[2];
          
          
       echo '<table class="table table-bordered"><tr>'
        . '<td>Code :</td><td>'.$_POST['code'].'</td></tr>'
        . '<tr><td>Dimension :</td><td><input type="text" name="Dimension" value="'.$_POST['Dimension'].'" required="required" maxlength="50"/></td></tr>'
        . '<tr><td>Block</td><td><select name="Blocked">';
        foreach ($Blocked as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['Blocked']?'selected="selected"':''),$value);
        }
        echo '</select></td></tr></table>';

    }else{

    echo '<table class="table-bordered"><tr>'
        . '<td>Code :</td><td><input type="text" name="code" value="'.$_POST['code'].'" required="required" maxlength="10"/></td></tr>'
        . '<tr><td>Name :</td><td><input type="text" name="Dimension" value="'.$_POST['Dimension'].'" required="required" maxlength="50"/></td>'
        . '</tr>'
            . '<tr><td>Block</td><td><select name="Blocked">';
        foreach ($Blocked as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['Blocked']?'selected="selected"':''),$value);
        }
        echo '</select></td></tr></table>';
    }
    echo '<div><input type="submit" name="submit" value="Update"/></div></div></form>';

    $ResultIndex3=DB_query("SELECT `code`,`Dimension`,`Blocked` FROM `Dimensions` where id='".$idtype."'", $db);
    
    echo '<P><table class="table table-bordered"><tr><th colspan="3">Dimension Values</th></tr>'
    . '<tr><td>Code</td><td>Name</td><td>Status</td></tr>';
    while($rows=DB_fetch_array($ResultIndex3)){
          echo '<tr><td>'.$rows['code'].'</td>'
          . '<td><a href="'.$_SERVER['PHP_SELF'].'?code='.trim($rows['code']).'&id='.$idtype.'">'.$rows['Dimension'].'</a></td>'
          . '<td>'.$BlockedStatus[$rows['Blocked']].'</td></tr>';
    }
      
    echo '</table></p>';
    
}elseif(isset($_GET['editid'])) {
       
    echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    echo '<input type="hidden" name="fid" value="dty" />';
    if(isset($editid)){
        
        echo '<input type="hidden" name="idedit" value="' .$editid. '" />';

        $ResultIndex4=DB_query("SELECT `id`,`Dimension_type`,`Blocked` FROM `DimensionSetUp` where id='".$editid."'", $db);
        $rows=DB_fetch_row($ResultIndex4);
            $_POST['id']=$rows[0];
            $_POST['Dimension_type']=$rows[1];
            $_POST['Blocked']=$rows[2];
          
    }

    echo '<table class="table-bordered"><tr>'
        . '<td>Dimension Type:</td><td><input type="text" name="Dimension_type" value="'.$_POST['Dimension_type'].'" required="required"/></td>'
        . '</tr>'
        . '</table><div><input type="submit" name="submit" value="Update"/></div>';
    echo '</div></form>';
    
}else{
    
    $ResultIndex5=DB_query('SELECT `id`,`Dimension_type` FROM `DimensionSetUp`', $db);
    
    echo '<table class="table table-bordered"><tr><th>Dimension Type</th></tr>';
    while($rows=DB_fetch_array($ResultIndex5)){
       echo '<tr><td><a href="'.$_SERVER['PHP_SELF'].'?id='.$rows['id'].'"> Click to Add :'.$rows['Dimension_type'].'</a></td>'
          . '<td><a href="'.$_SERVER['PHP_SELF'].'?editid='.$rows['id'].'"> Edit TYPE</a></td></tr>';
    }
    
    echo '</table>';
    echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
     echo '<input type="hidden" name="fid" value="dty" />';
    echo '<table class="table-bordered"><tr>'
        . '<td>Dimension Type:</td><td><input type="text" name="Dimension_type"  required="required"/></td>'
        . '</tr><tr><td colspan="2"><div><input type="submit" name="submit" value="Create Dimension Type"/></div></td></tr>'
        . '</table>';
    echo '</div></form>';
}

include('includes/footer.inc');

?>