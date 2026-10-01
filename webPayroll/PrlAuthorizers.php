<?php
include('includes/session.inc');
$Title = "Create system Authourisation Members";
include('includes/header.inc');

$b = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_POST['submit'])){
    $SQL=sprintf("Select * from [prlauthorizers] where [userdepartment]='%s' and 
      [positionapprover]='%s' ",$_POST['departcode'],$_POST['positioncode']);
    $resu=DB_query($SQL,$db);
    if(DB_num_rows($resu)==0){
        DB_free_result($resu); 
        $sql=sprintf("insert into [prlauthorizers] ([userdepartment],[positionapprover],[positionaplevel]) "
                . " values ('%s','%s','%s')",$_POST['departcode'],$_POST['positioncode'],$_POST['levelindex'] );
        DB_query($sql,$db);
    }
    DB_free_result($resu);
    
    
   unset($_POST['departcode']);
   unset($_POST['positioncode']);
   unset($_POST['levelindex']);
}

if(isset($_GET['del'])){
    $SQL=sprintf("delete from [prlauthorizers] 
      where [uniq]='%s' ",$_GET['del']);
    $resu=DB_query($SQL,$db);

   unset($_POST['departcode']);
   unset($_POST['positioncode']);
   unset($_POST['levelindex']);
}

if(isset($_GET['id'])){
    $SQL=sprintf("Select [userdepartment],[positionapprover]
      ,[positionaplevel] from [prlauthorizers] 
      where [uniq]='%s' ",$_GET['id']);
    $resu=DB_query($SQL,$db);
    $rowse=DB_fetch_row($resu);
    
    $_POST['departcode']=$rowse[0];
    $_POST['positioncode']=$rowse[1];
    $_POST['levelindex']=$rowse[2];
    
}


echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="'. _('Search') . '" alt="" />' . ' ' . $Title . '<br /></p>';
echo '<form method="post"  id="approver" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">';
echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table-bordered"><tr>';

echo '<td>Select Department</td><td><select name="departcode" onchange="ReloadForm(approver.refresh);"><option></option>';
$ResultIndex=DB_query('Select code,name from prldepartments', $db);
while($row=DB_fetch_array($ResultIndex)){ echo '<option value="'.trim($row['code']).'"  '.($_POST['departcode']==trim($row['code'])?'selected="selected"':'').' >'.$row['name'].'</option>';}
echo '</select></td></tr>';

echo '<tr><td>Select the Approver <br/> for that department</td><td><select name="positioncode">';
$ResultIndex=DB_query("Select code,name from prlpositions where code='".GetHOD($_POST['departcode'])."'", $db);
while($row=DB_fetch_array($ResultIndex)){ echo '<option value="'.$row['code'].'" '  .($_POST['positioncode']==$row['code']?'selected="selected"':'').'>'.$row['name'].'</option>';}
echo '</select></td></tr>';

echo '<tr><td>Select their Authority<br/><code>([0=Mandatory], [1=Optional])</code></td><td><select name="levelindex">';
for ($index = 0; $index < 2; $index++) {
    echo '<option value="'.$index.'" '  .($_POST['levelindex']==$row['$index']?'selected="selected"':'').'>'.$index.'</option>';
}
echo '</select></td></tr>';

if(isset($_GET['uniq'])){
    echo '<tr><td><input type="submit" name="refresh" value="Refresh"/>'
    . '<input type="submit" name="edituser" value="Edit Position"/></td></tr>';
}else{
    echo '<tr><td><input type="submit" name="refresh" value="Refresh"/>'
    . '<input type="submit" name="submit" value="Create Position"/></td></tr>';    
}
echo  '</table>';


$SQL="SELECT [uniq],
        [userdepartment],
        [positionapprover],
        [positionaplevel],
        isnull(d.name,'') as Name,
        isnull(p.name,'') as Position
       FROM [prlauthorizers] A 
        join prldepartments d on A.[userdepartment]=d.code 
        join prlpositions p on A.[positionapprover]=p.code  
        order by [positionaplevel],[userdepartment]";
$ResultIndex=DB_query($SQL,$db);


echo '<table class="table-bordered"><tr><th>Departments</th><th>Approver</th><th>Level Index</th></tr>';

while($rows=DB_fetch_array($ResultIndex)){
    echo sprintf('<tr>'
            . '<td><input type="text" value="%s" readonly="readonly"/></td>'
            . '<td><input type="text" value="%s" readonly="readonly"/></td>'
            . '<td>%s</td>'
            . '<td><a href="%s?id=%s">Edit</a></td>'
            . '<td><a href="%s?del=%s">Delete</a></td>'
            . '</tr>', $rows['Name'],$rows['Position'],$rows['positionaplevel'],
              $b,$rows['uniq'],$b,$rows['uniq']);
}
echo '</table>';

echo '</div></form>';
include('includes/footer.inc');


Function GetHOD($code){
    Global $db;
    $ResultIndex=DB_query("Select hod from prldepartments where code='".$code."'", $db);
    $row = DB_fetch_row($ResultIndex);
    return $row[0];
}

?>