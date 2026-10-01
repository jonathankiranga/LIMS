<?php

include('includes/session.inc');
$Title = "Maintain Qualifications";
include('includes/header.inc');

if(isset($_POST['submit'])){

    DB_Txn_Begin($db);
    $SQL=sprintf("Insert into prlqualifications (name) values ('%s')",$_POST['qua']);
    $results =DB_query($SQL,$db);

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
echo '<div class="sp-header-icon"><i class="fas fa-graduation-cap"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Maintain qualifications</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">';
echo '<div>
    <table class="table-bordered">
    <tr><th>List of Available qualifications</th></tr>';
$b = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$Msg="There are no qualifications set up";
$SQL="select Q.* from prlqualifications Q";
$ResultIndex=DB_query($SQL, $db);
if(DB_num_rows($ResultIndex)==0){
    prnMsg($Msg);
}

while($rows=DB_fetch_array($ResultIndex)){
    echo sprintf('<tr><td><input type="text"  value="%s" readonly/></td></tr>',$rows['name']);
}
echo '</table>';

echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />'
   . '<table class="table-bordered">';
echo '<tr><td>Qualification</td><td><input type="text" name="qua" required/></td></tr>';
echo '</table><input type="submit" name="submit" value="Save"/></div></form>';

echo '</div>';
echo '</div>';
include('includes/footer.inc');
?>
