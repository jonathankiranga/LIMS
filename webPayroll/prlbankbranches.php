<?php
$ForceConfigReload=true;
include('includes/session.inc');
$Title = "Maintain Banks branches";
include('includes/header.inc');


if(isset($_POST['submit'])){
    $SQL=sprintf("SELECT count(*) as items FROM [employeebnkbranch] "
            . " where [parentcode]='%s' and code ='%s' ",$_POST['addPosition'],$_POST['Code']);
    $ResultIndex = DB_query($SQL, $db);
    $row=DB_fetch_row($ResultIndex);
   
     if($row[0]>0){ 
         unset($_POST['submit']);
         
     }
       

}

if(isset($_POST['submit'])){

    DB_Txn_Begin($db);
    $SQL=sprintf("Insert into employeebnkbranch (parentcode,address,bankbranch,code) values ('%s','%s','%s','%s')",
    $_POST['addPosition'],$_POST['jobposition'],$_POST['parentname'],$_POST['Code']);
    $results = DB_query($SQL,$db);

    if(DB_error_no($db)>0){
         DB_Txn_Rollback($db);
         prnMsg(DB_error_msg($db),'warn');
  } else {
      DB_Txn_Commit($db);
  }

}



if(isset($_POST['EditPosition'])){
    
    DB_Txn_Begin($db);
    $SQL=  sprintf("update employeebnkbranch  set address='%s' where code='%s' ",
            $_POST['jobposition'],$_POST['Code']);
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
echo '<div class="sp-header-icon"><i class="fas fa-university"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Maintain bank branches</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';

$b = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_GET['EditP'])){
    echo '<input type="hidden" name="EditPosition" value="'. $_GET['EditP'] .'"/>';
    echo '<table  id="example" class="table-bordered">'
    . '<thead><tr><th>Code</th><th>Address</th><th>Parent Bank</th></tr></thead>';

    $SQL=sprintf("select P.*, D.[address] as depar ,D.code as Bcode from [employeebanks] P "
            . "left join [employeebnkbranch] D on P.code=D.parentcode where D.code='%s'",$_GET['EditP']);
    $ResultIndex=DB_query($SQL, $db);
    while($rows=DB_fetch_array($ResultIndex)){
            echo sprintf('<tr>'
            . '<td><input type="text" value="%s" readonly="readonly" size="30" name="Code"/></td>'
            . '<td><input type="text" value="%s" required="required" size="30" name="jobposition"/></td>'
            . '<td>%s</td></tr>',$_GET['EditP'],$rows['depar'],$rows['bankname']);
    }
    echo '</table><div><input type="submit" name="submit" value="Save"/></div>';

}elseif(isset($_GET['addbranch'])){
    
    $SQL=sprintf("select P.bankname from [employeebanks] P  where P.code='%s'",$_GET['addbranch']);
    $ResultIndex=DB_query($SQL, $db);
    $bankname=DB_fetch_row($ResultIndex);
    
    echo '<input type="hidden" name="addPosition" id="bankfilter" value="'. $_GET['addbranch'] .'"/>';
    echo '<table  id="example" class="table-bordered">'
    . '<thead><tr><th>Code</th><th>Address</th><th>Parent<br />Bank</th><th></th></tr></thead>';
    echo '<tr><td colspan="3">The parent Code is :'. $_GET['addbranch'] .', Create a code after that series</td></tr>';
    echo '<tr><td><input type="text" required="required" id="validatenewbankbranch" size="10" name="Code"/></td>'
            . '<td><input type="text" required="required" size="30" name="jobposition"/></td>'
            . '<td><input type="text" name="parentname" value="'. $bankname[0].'" readonly="readonly" /></td>'
            . '</tr></table>'
            . '<div><input type="submit" name="submit" value="Save"/></div>';
    
} else{

  echo '<table id="example" class="table-bordered">'
    . '<thead><tr><th>Code</th>'
          . '<th>List of Available Banks</th>'
          . '<th>Address</th>'
          . '<th>Branches</th><th></th></tr></thead>';
      
    $SQL="select P.*,"
            . " D.[address] as depar,"
            . " D.code as Bcode from [employeebanks] P "
            . "left join [employeebnkbranch] D on P.code=D.parentcode "
            . "order by bankname";
    
    $ResultIndex=DB_query($SQL, $db);
        while($rows=DB_fetch_array($ResultIndex)){
            $branchnew = sprintf('<a href="%s?addbranch=%s">Add branch</a>',$b,$rows['code']);
            $anc = sprintf('<a href="%s?EditP=%s">Edit</a>',$b,$rows['Bcode']);
            echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $branchnew,$rows['Bcode'],$rows['bankname'],$rows['depar'],$anc);
        }
        echo '<tr></tr>';

echo '</table>';
}

echo '</div>';
echo '</form>';
echo '</div>';
echo '</div>';
include('includes/footer.inc');
?>
