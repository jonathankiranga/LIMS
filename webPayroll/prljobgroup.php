<?php
include('includes/session.inc');
$Title = "Maintain Job Group";
include('includes/header.inc');

if(isset($_POST['submit'])){

    DB_Txn_Begin($db);
    $SQL=sprintf("Insert into prljobgroup (name,qualification) values ('%s','%s')",
            $_POST['jobgroup'],$_POST['qualification']);
    $results = DB_query($SQL,$db);

    if(DB_error_no($db)>0){
         DB_Txn_Rollback($db);
         prnMsg(DB_error_msg($db),'warn');
  } else {
      DB_Txn_Commit($db);
    }

}



echo '<link rel="stylesheet" href="css/smartpayroll.css">';
echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-layer-group"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Maintain job groups</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">';
echo '<div class="table-responsive mini">
    <table id="example" class="table-bordered"><thead>
    <tr><th>List of Available Job Groups</th><th>Qualification</th></tr></thead>';
$b = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$Msg="There are no job groups set up";

$SQL="select J.* from prljobgroup J";
$ResultIndex=DB_query($SQL, $db);
if(DB_num_rows($ResultIndex)==0){
    prnMsg($Msg);
}

while($rows=DB_fetch_array($ResultIndex)){
    echo sprintf('<tr><td><input type="text"  value="%s" readonly/></td><td>%s</td></tr>' ,$rows['name'],$rows['qualification']);
}

echo '</table>';


echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />'
        . '<div  class="table-responsive mini"><table class="table-bordered">';

echo '<tr><td>Select Qualification</td><td>
    <select name="qualification" required>';

$sql="select q.* from prlqualifications q";
$results=db_query($sql,$db);
while($rows= DB_fetch_array($results)){
    echo '<option value="'.$rows['name'].'">'.$rows['name'].'</option>';
}

 echo '</select></td></tr>';

echo '<tr><td>Job Group</td><td><input type="text" name="jobgroup" required/></td></tr>';
echo '<tr><td><input type="submit" name="submit" value="Save"/></td></tr>';
echo '</table></div></div></form>';


echo '</div>';
echo '</div>';
include('includes/footer.inc');
?>
