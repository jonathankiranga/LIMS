<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Dimension');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Dimension') .'" alt="" />'
. ' ' . _('Dimension') . '</p>';

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





if($_POST['fid']=='dval'){
          //alter table dimensions add parent varchar(10),`level` int
 
    if($_POST['Parent']==''){
        if(isset($_POST['codeedit'])){
            DB_query("Update `Dimensions` set parent=NULL,`Dimension`='".$_POST['Dimension']."',`Blocked`='".$_POST['Blocked']."' where code='".$_POST['codeedit']."' and id='".$_POST['id']."'", $db);
        }else{
            DB_query("insert into `Dimensions` (parent,`Dimension`,`Blocked`,`code`,`id`)"
                    . " values (NULL,'".$_POST['Dimension']."',".$_POST['Blocked'].",'".$_POST['code']."','".$_POST['id']."')", $db);
        }
    }else{
        if(isset($_POST['codeedit'])){
        DB_query("Update `Dimensions` set parent='".$_POST['Parent']."',`Dimension`='".$_POST['Dimension']."',`Blocked`='".$_POST['Blocked']."' where code='".$_POST['codeedit']."' and id='".$_POST['id']."'", $db);
        }else{
        DB_query("insert into `Dimensions` (parent,`Dimension`,`Blocked`,`code`,`id`)"
                . " values ('".$_POST['Parent']."','".$_POST['Dimension']."',".$_POST['Blocked'].",'".$_POST['code']."','".$_POST['id']."')", $db);
        }
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
        $ResultIndex=DB_query("SELECT `code`,`Dimension`,`Blocked` FROM `Dimensions` where id='".$idtype."' and code='".$editcode."'", $db);
        $rows=DB_fetch_row($ResultIndex);
            $_POST['code']=$rows[0];
            $_POST['Dimension']=$rows[1];
            $_POST['Blocked']=$rows[2];
        
       echo '<table class="table table-bordered"><tr><td>Parent :</td><td>';
         echo getparents($idtype);
         echo '</td></tr><tr>'
        . '<td>Code :</td><td>'.$_POST['code'].'</td></tr>'
        . '<tr><td>Dimension :</td><td><input type="text" name="Dimension" value="'.$_POST['Dimension'].'" required="required" maxlength="50"/></td></tr>'
        . '<tr><td>Block</td><td><select name="Blocked">';
        foreach ($Blocked as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['Blocked']?'selected="selected"':''),$value);
        }
        echo '</select></td></tr></table>';

    }else{

         echo '<table class="table-bordered"><tr><td>Parent :</td><td>';
         echo getparents($idtype);
         echo '</td></tr><tr><td>Code :</td><td><input type="text" name="code" value="'.$_POST['code'].'" required="required" maxlength="10"/></td></tr>'
            . '<tr><td>Name :</td><td><input type="text" name="Dimension" value="'.$_POST['Dimension'].'" required="required" maxlength="50"/></td>'
            . '</tr><tr><td>Block</td><td><select name="Blocked">';
        foreach ($Blocked as $key => $value) {
            echo sprintf('<option value="%s" %s>%s</option>',$key,($key==$_POST['Blocked']?'selected="selected"':''),$value);
        }
        echo '</select></td></tr></table>';
    }
    echo '<div><input type="submit" name="submit" value="Update"/></div></div></form>';

    $ResultIndex=DB_query("SELECT `code`,`Dimension`,`Blocked` FROM `Dimensions` where id='".$idtype."'", $db);
    
    echo '<P><table class="table table-bordered"><tr><th colspan="3">Dimension Values</th></tr>'
    . '<tr><td>Code</td><td>Name</td><td>Status</td></tr>';
    while($rows=DB_fetch_array($ResultIndex)){
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

        $ResultIndex=DB_query("SELECT `id`,`Dimension_type`,`Blocked` FROM `DimensionSetUp` where id='".$editid."'", $db);
        $rows=DB_fetch_row($ResultIndex);
            $_POST['id']=$rows[0];
            $_POST['Dimension_type']=$rows[1];
            $_POST['Blocked']=$rows[2];

    }

    echo '<table class="table-bordered"><tr>'
        . '<td>Dimension Type:</td><td><input type="text" name="Dimension_type" value="'.$_POST['Dimension_type'].'" required="required"/></td>'
        . '</tr>'
        . '</table><div><input type="submit" name="submit" value="Update"/></div>';
    echo '</div></form>';
    
}

include('includes/footer.inc');

function getparents($dimensionID){
    global $db;
    
    $combo = '<select name="Parent"><option></option>';
    $sql="SELECT `code`,`Dimension`,`Blocked` FROM `Dimensions` where id='$dimensionID' and `level` is null"; 
    $ResultIndex = DB_query($sql,$db);
    while($row=DB_fetch_array($ResultIndex)){
        if(isset($_POST['Parent'])){
            if(trim($_POST['Parent'])==trim($row['code'])){
                 $combo .= '<option value="'.$row['code'].'" selected="selected">'.$row['Dimension'].'</option>';
            }
        }else{
             $combo .= '<option value="'.$row['code'].'">'.$row['Dimension'].'</option>';
       }
    }
    $combo .= '</select>';
    
    return $combo;
}


?>