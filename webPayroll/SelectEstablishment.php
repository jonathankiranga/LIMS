<?php
include('includes/session.inc');
$Title = _('Company Establishment');
include('includes/header.inc');
include('includes/CountiesArray.php');
$self = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<link rel="stylesheet" href="css/smartpayroll.css">';
echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-building"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Company establishment management</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';

if(isset($_POST['selectedpositions']) && isset($_POST['positions'])){
    if(isset($_POST['establishment'])){
        foreach ($_POST['positions'] as $key => $value) {
            $SQL=sprintf("insert into [prlestablishment_details]
            ([establishcode],[positions]) values ('%s','%s')",$_POST['establishment'],$value);
            $ResultIndex=DB_query($SQL,$db);
        }
    }
}

if(isset($_POST['howmany']) and isset($_POST['posno'])){
    
    foreach ($_POST['posno'] as $key => $value) {
            $code = explode('_',$key);
        
            $SQL=sprintf("update [prlestablishment_details]
            set [no]='%s' where [establishcode]='%s' and  [positions]='%s' ", $value ,
            $_POST['establishcode'] , $code[1]);
            $ResultIndex=DB_query($SQL,$db);
    }

    $_GET['drill']=$_POST['establishcode'];

}

if(isset($_GET['delete'])){
    $SQL=sprintf("delete from [prlestablishment_details] where
    [establishcode]='%s' and [positions]='%s' ",$_GET['establishment'],$_GET['delete']);
    $ResultIndex=DB_query($SQL,$db);
}

if(isset($_POST['create'])){
    $sql = sprintf("INSERT INTO [prlestablishment] ([levels],[county],[name])
    VALUES   ('%s','%s','%s')", $_POST['stepone'] ,$_POST['countie'] ,$_POST['branchname']);

    DB_query($sql,$db);
    if(DB_error_no($db)==0){
        prnMsg(' The transaction has been saved successfuly');
    } else {
        prnMsg(' The transaction has not been committed','warn');
    }
}

if(isset($_POST['edititem'])){
    $ok=1;

    if($_POST['stepone']=='0' and $_POST['headoffice'] !='0'){
        $sql="Select * from prlestablishment where levels='0'";
        $results = DB_query($sql,$db);
        $no = DB_num_rows($results);
        if($no==0){ $ok=1; } else { $ok=0; }
    }

    if($ok==1){
        $sql = sprintf("update [prlestablishment]  set [levels]='%s',[county]='%s',[name]='%s'
        where code='%s'", $_POST['stepone'] , $_POST['countie'] , $_POST['branchname'], $_POST['editcode']);

        DB_query($sql,$db);
        if(DB_error_no($db)==0){
            prnMsg(' The transaction has been saved successfuly');
        } else {
            prnMsg(' The transaction has not been committed','warn');
        }
    } else {
      prnMsg('The transaction has not been committed because there is more than one headoffice','warn');
   }
}

if(isset($_GET['EDIT'])){

  echo '<a href="'.$self.'">Display List of all the created Establishments</a>';
  echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">';
  echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>
      <input type="hidden" name="editcode" value="' . $_GET['EDIT'] . '"/>';

  $sql="Select * from prlestablishment where code='".$_GET['EDIT']."'";
  $results = DB_query($sql,$db);
  while($rows=DB_fetch_array($results)){
// begin of get values
      echo '<input type="hidden" name="headoffice" value="' . $rows['levels'] . '"/>';
      $object ='<table class="table-bordered"><tr><td><label for="stepone">Select Region</label></td><td><select name="stepone" required>';
      foreach ($levelsarray as $key => $value) {
               $object .= '<option value="'.$key.'" '.($key==trim($rows['levels'])?' selected="selected"':'').'>'. $value .'</option>';
      }

    $object .= '</select></td></tr>';
    $object .= '<tr><td><label for="countie">Select County</label></td><td><select name="countie" required>';
    foreach ($KenyanCounties as $key => $value) {
             $object .='<option value="'.$key.'"'.($key==trim($rows['county'])?' selected="selected"':'').'>'.$value."</option>";
    }

    $object .= '</select></td></tr><tr>';
    echo $object .'<td><label for="branchname">Select Name of Establishment</label></td>
    <td><input type="text" name="branchname"  value="'.$rows['name'].'" /></td>
    </tr></table>';

  }

// end of get values
?>
<input tabindex="26" type="submit" name="edititem" value="<?php echo _('Update'); ?>"/>
<?php
echo '</div></form>';

} elseif(isset($_GET['view'])) {

echo '<a href="'.$self.'">Go back</a>';
   echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
   echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

  $sql="Select * from prlestablishment where levels='0'";
  $results = DB_query($sql,$db);
  $no = DB_num_rows($results);

  if($no==0){
        echo '<table><tr><td><label for="stepone">Select Region</label></td><td>
        <select name="stepone" required>
        <option value="0">Head Office</option>
        <option value="1">Regional Office</option>
        <option value="2">Sub-Regional Office</option>
        </select></td></tr>';
  } else {
         echo '<table class="table-bordered"><tr><td><label for="stepone">Select Region</label></td><td>
        <select name="stepone" required>
        <option value="1">Regional Office</option>
        <option value="2">Sub-Regional Office</option>
        </select></td></tr>';
  }

echo '<tr><td><label for="countie">Select County</label></td><td><select name="countie" required>';

foreach ($KenyanCounties as $key => $value) {
    echo '<option value="'.$key.'">'.$value."</option>";
}

echo '</select></td></tr><tr>
    <td><label for="branchname">Select Name of Establishment</label></td>
    <td><input type="text" name="branchname"  /></td>
    </tr></table>';
?>
<input tabindex="26" type="submit" name="create" value="<?php echo _('Update'); ?>"/>
<?php

echo '</div></form>';


} elseif(isset($_GET['CREATE'])) {

   echo '<a href="'.$self.'">Display List of all the created Establishments</a>';
   echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
   echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

  $sql="Select * from prlestablishment where levels='0'";
  $results = DB_query($sql,$db);
  $no = DB_num_rows($results);

  if($no==0){
    echo '<table class="table-bordered"><tr><td><label for="stepone">Select Region</label></td><td>
        <select name="stepone" required>
        <option value="0">Head Office</option>
        <option value="1">Regional Office</option>
        <option value="2">Sub-Regional Office</option>
        </select></td></tr>';
  } else {
      echo '<table class="table-bordered"><tr><td><label for="stepone">Select Region</label></td><td>
        <select name="stepone" required>
        <option value="1">Regional Office</option>
        <option value="2">Sub-Regional Office</option>
        </select></td></tr>';
  }

echo '<tr><td><label for="countie">Select County</label></td><td><select name="countie" required>';

foreach ($KenyanCounties as $key => $value) {
    echo '<option value="'.$key.'">'.$value."</option>";
}

echo '</select></td></tr><tr>
    <td><label for="branchname">Select Name of Establishment</label></td>
    <td><input type="text" name="branchname"  /></td>
    </tr></table>';
?>
<input tabindex="26" type="submit" name="create" value="<?php echo _('Update'); ?>"/>
<?php

echo '</div></form>';

} elseif(isset($_GET['drill'])) {
    $pa = $_GET['drill'];
    echo '<a href="'.$self.'">Go Back</a>';

   echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
   echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '"/>
         <input type="hidden" name="establishcode" value="' . $pa . '"/>';
   echo '<Table class="table-bordered"><centre><tr><th>Position</th><th>Job Group</th><th>How</br>many</th><th>Edit</th></tr>';
        $SQL = "select P.name,P.jobgroup,E.positions,P.code,E.no
        from  [prlestablishment_details] E join prlpositions P
        on P.code = E.positions where E.establishcode='". $pa ."'";
    $ResultIndex=DB_query($SQL,$db);

    if(DB_num_rows($ResultIndex)==0){
         prnMsg('There are no postions for this selection');
    } else {
        while($rows= DB_fetch_array($ResultIndex)){
            $body=sprintf('<tr><td>%s</td><td>%s</td><td>
                <input name="posno[code_'.$rows['code'].']" class="number" size="3"  value="'.$rows['no'].'"/></td>
                    <td><a href="'.$self.'?delete=%s&establishment='.$pa.'">remove</a></td></tr>',
                    $rows['name'],$rows['jobgroup'],$rows['code']);
            echo  $body;
        }
    }

    echo '</table><input type="submit" name="howmany" value="create how many positions"/></form><p>';
    echo '<a href="'.$self.'?positions=YES&pcode='.$_GET['drill'].'">Get Positions list</a>';

} elseif(isset($_GET['positions'])) {

    echo '<a href="'.$self.'">Go Back</a>';
    $_position_code = $_GET['pcode'];
    $option='';

    if(isset($_position_code)){

    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
         <input type="hidden" name="establishment" value="' . $_position_code . '" />
         <p><b>Press Ctrl and Click <br/> to select multiple items</b>';
    echo '<div class="container"><table class="table-bordered">'
    . '<tr><th>Positions and Job Group</th></tr><tr><td>';
    echo '<select name="positions[]" size="20" multiple="multiple">';

    $SQL = "select * from prlpositions";
    $ResultIndex = DB_query($SQL,$db);
    $i=0;
    while($rows = DB_fetch_array($ResultIndex)){
       
        $class = (($i/2)==0?'EvenTableRows':'OddTableRows');
        $option .= sprintf('<option value="%s" class="'.$class.'">%s : Job Group %s</option>',$rows['code'],$rows['name'],$rows['jobgroup']);
    
        $i++;
        
    }
    echo $option;

    echo '</select></td></tr></table>';
    echo '<input type="submit" name="selectedpositions" value="Save selected items"/>';
    echo '</div></form>';

    } else {
               prnMsg('invalid selection','error');
    }



} else {
   
    $sql="Select * from prlestablishment";
    $results = DB_query($sql,$db);
    echo '<div class="container"><a href="'.$self.'?CREATE=YES">Create New Branch Office</a><Table class="table-bordered">'
    . '<centre><tr><th>Establishment Name</th><th>Level</th><th>Location</th><th>View</th></tr>';
    while($rows =  DB_fetch_array($results)){
        
        $Est_level=trim($rows['levels']);
        $Est_County=trim($rows['county']);
        
        echo '<tr><td><a href="'.$self.'?EDIT='.$rows['code'].'">SET UP '.$rows['name'].'</a></td>
                <td>'.$levelsarray[$Est_level]
              .'</td><td>'.$KenyanCounties[$Est_County].'</td>
               <td><a href="'.$self.'?drill='.$rows['code'].'"> Drill Down '.$rows['name'].'</a>
              </td></tr>';
    }
    echo '</table></div>';



}

echo '</div>';
echo '</div>';
include('includes/footer.inc');


?>
