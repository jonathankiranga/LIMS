<?php
$ForceConfigReload=true;
include('includes/session.inc');
$Title = "PayRoll Sacco and Savings"  ;
include('includes/header.inc') ;
include('ExtFunc/salary.inc') ;

$self = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<link rel="stylesheet" href="css/smartpayroll.css">';
echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-piggy-bank"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Manage staff savings and SACCO contributions</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';

$staffno =$_SESSION['payrollid'];

if(isset($staffno)){
    $title2 = ' For '. showname($staffno);
    echo '<p class="page_title_text">' . ' ' . $title2 . '<br /></p>';
}


if(isset($_POST['mergecredit'])){
    
    $ChildIndex=$_POST['childaccount'];
    
     $sql="select [deductcode] from prlstaffloans where loanindex='".$_POST['MergeId']."'";
     $ResultIndex = DB_query($sql,$db);
     $row = DB_fetch_row($ResultIndex);
     $p1  = $row[0];
     
     $sql="select [deductcode] from prlstaffloans where loanindex='".$ChildIndex."'";
     $ResultIndex = DB_query($sql,$db);
     $row = DB_fetch_row($ResultIndex);
     $p2  = $row[0];
     
    
    if($p1 != $p2){
        prnMsg('You cannot merge different account types ','warn');
    } elseif($_POST['MergeId']==$_POST['childaccount']){
        prnMsg('You cannot merge this account to it self','warn');
    } else {
        $sql=array();
        $sql[] = sprintf("Update prlloantrans set [loanindex]='%s' where [loanindex]='%s' ",$_POST['MergeId'],$ChildIndex);
        $sql[] = sprintf("Delete from prlstaffloans where [loanindex]='%s' ",$ChildIndex);
       
        DB_Txn_Begin($db);
        foreach ($sql as $value) {
            DB_query($value,$db);
        }

        if(DB_error_no($db)>0){
            DB_Txn_Rollback($db);
        }else{
            DB_Txn_Commit($db);
        }
    } 
}

echo '<form method="post" action="'. $self . '">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
 
 
if(isset($_GET['remindex'])){
   echo '<input type="hidden" name="MergeId" value="'. $_GET['remindex'] .'" />';
  
   $sql="select * from prlstaffloans where loanindex='".$_GET['remindex']."'";
    $ResultIndex = DB_query($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){

    echo '<table class="table-bordered">';
    echo '<tr><td>Parent Account <input type="text" value="'.$_GET['remindex'].'" size="10" maxlength="7"  readonly="readonly"/></td>';
    echo '<td><input type="text" value="'.$row['principal'].'"  size="10" maxlength="7"  readonly="readonly"/></td>';
    echo '<td><input type="text" value="'.ConvertSQLDate($row['startdate']).'"  readonly="readonly"  size="10" name="startdate" class="date" alt="'. $_SESSION['DefaultDateFormat'] .'"/></td>';
    echo '<td>Merge Transactions<input type="text"  size="10" name="childaccount" required class="number"  maxlength="4" size="6"/></td>';
    echo '</tr></table><input type="hidden" name="loanindex" value="'.$_GET['remindex'].'"/><input type="hidden" name="pf_no" value="'.$_GET['pf_no'].'"/>';
    echo '<input type="submit" name="mergecredit" value="Merge Sacco Items"/><br /><a href="'.$self.'">Go Back</a>';
  }
     
}


if(isset($_POST['createcredit'])){

    $sql = sprintf("INSERT INTO [prlstaffloans]
    ([pfno],[deductcode],[principal],openbalance,[instalment],[interest],[startdate],[saving])
     VALUES ('%s','%s','%f','%f','%f',%f,'%s','1')", $_POST['pf_no'], $_POST['deductcode'],
     $_POST['principal'],$_POST['openbalance'], $_POST['instalment'],$_POST['interest'], FormatDateForSQL( $_POST['startdate']) );
    
    
    DB_query($sql,$db);
    unset($_POST['pf_no']);
    unset($_POST['createcredit']);
    
} elseif(isset($_POST['editcredit'])){

     $sql = sprintf("update [prlstaffloans]
     set [deductcode]='%s',[principal]='%f',openbalance='%f',[instalment]='%f',[interest]=%f, [startdate]='%s'  
     where  loanindex='%s' ", $_POST['deductcode'], $_POST['principal'], $_POST['openbalance'], $_POST['instalment'],
     $_POST['interest'], FormatDateForSQL($_POST['startdate']) ,$_POST['loanindex']);

    DB_query($sql,$db);
    unset($_POST['pf_no']);
    unset($_POST['loanindex']);
    unset($_POST['editcredit']);
}

//// this is where the grid loads
if(!isset( $staffno ) and !isset($_GET['loanindex'])){

echo '<table class=" table-bordered "><thead><tr>'
    . '<th>PayRoll ID</th>'
        . '<th>ID<br />Number</th>'
        . '<th>Names</th>'
        . '<th>Telephone Mobile No</th>'
        . '<th>Email Address</th>'
    . '<th>Total Savings Balance</th></tr></thead>';

$ResultIndex = GETALLEMPLOYEES() ;  $k=0 ;
while($row = DB_fetch_array($ResultIndex)){
    if($row['Active']==0){
        echo '<tr><td><a href="'.$self.'?pf_no='.trim($row['pf_no']).'">'.$row['pf_no'].'</a></td>';
        echo '<td>'.$row['idno'].'</td>';
        echo '<td>'.$row['fname'].' '.$row['mname'].' '.$row['lname'].'</td>';
        echo '<td>'.$row['telno'].'</td>';
        echo '<td>'.$row['email'].'</td>';
        echo '<td class="number">'. number_format(getsavingsbalance($row['pf_no']),2).'</td></tr>';
        
    }
}
echo '</table>';


} elseif (isset($staffno) and !isset($_GET['loanindex'])) {

echo '<input type="hidden" name="pf_no" value="'. $staffno.'" />';
echo '<table class="table-condensed"><tr><td>
     <table class=" table-bordered "><tr>
     <th>Pkey Index</th>
     <th>Item Name</th>
     <th>Balance B/fwd</th>
     <th>Period Deductions</th>
     <th>Interest</th>
     <th>Start Deducting on</th>
     <th>C/fwd Balance</th></tr>';

$ResultIndex = getsaccoset($staffno);
while($row = DB_fetch_array($ResultIndex)){
    
    echo '<tr><td><a href="'.$self.'?loanindex='.$row['loanindex'].'&pf_no='. trim($staffno).'">'.$row['loanindex'].'</a></td>';
    echo '<td>'. $row['description'].'</td>';
    echo '<td>'. number_format($row['principal'],2).'</td>';
    echo '<td>'. number_format($row['instalment'],2).'</td>';
    echo '<td>'. $row['interest'].'</td>';
    echo '<td>'. ConvertSQLDate($row['startdate']).'</td>';
    echo '<td>'. number_format($row['balance'],2) .'</td>';
    echo '</tr>';

}
    echo '</table></td></tr><tr><td>';
  if(!isset($_GET['remindex'])){
  
    echo '<table class=" table-bordered "><tr><td></td>';
    echo '<td>Name : ';
     getloanlist();
    echo '</td>';
    echo '<td>Principle : <input type="text" size="10" maxlength="7"  name="principal"  required  class="number"/></td>';
    echo '<td>Current Balance : <input type="text" size="10" maxlength="7"  name="openbalance"  required  class="number"/></td>';
    echo '<td>Installments : <input type="text" size="10" maxlength="7"  name="instalment"  required  class="number"/></td>';
    echo '<td>Interest : <input type="text" size="10" name="interest" class="number" maxlength="4" size="6" /></td>';
    echo '<td>Start Date : <input type="text" size="10" name="startdate" class="date"  required  alt="'. $_SESSION['DefaultDateFormat'] .'"/></td>';
    echo '</tr>';
    echo '</table><input type="submit" name="createcredit" value="Add Contribution Item"/><br />';
 
    }
  
    echo '</td></tr></table>';



} elseif(isset($_GET['loanindex']) and isset( $staffno)){

    echo '<input type="hidden" name="pf_no" value="'.  $staffno .'" />';


    $sql="select * from prlstaffloans where loanindex='".$_GET['loanindex']."'";
    $ResultIndex = DB_query($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){

    echo '<table class=" table-bordered ">';
    echo '<tr><td></td><td>';
    filterproduct($row['deductcode']);
    echo '</td>';
    echo '<td><input type="text" value="'.$row['principal'].'" size="10" maxlength="7" required name="principal" class="number"/></td>';
    echo '<td>Current Balance : <input type="text" value="'.$row['openbalance'].'" size="10" maxlength="7"  name="openbalance"  required  class="number"/></td>';
  
    echo '<td><input type="text" value="'.$row['instalment'].'"  size="10" maxlength="7"  required  name="instalment" class="number"/></td>';
    echo '<td><input type="text" value="'.$row['interest'].'"  size="10" name="interest" class="number" maxlength="4" size="6"/></td>';
    echo '<td><input type="text" value="'.ConvertSQLDate($row['startdate']).'"  required  size="10" name="startdate" class="date" alt="'. $_SESSION['DefaultDateFormat'] .'"/></td>';
    echo '</tr></table><input type="hidden" name="loanindex" value="'.$_GET['loanindex'].'"/>
        <input type="hidden" name="pf_no" value="'. $staffno.'"/>';
    echo '<input type="submit" name="editcredit" value="edit Item"/>';
  }

}


echo '</form>';
echo '</div>';
echo '</div>';
include('includes/footer.inc');
?>
