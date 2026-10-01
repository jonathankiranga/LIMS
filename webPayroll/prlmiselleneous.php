<?php
include('includes/session.inc');
$Title = "Post Miscelleneous transactions";
include('includes/header.inc');
include('ExtFunc/payrollmatrix.inc');
include('ExtFunc/gensalary.inc');
include('ExtFunc/Payrollfunctions.php');

echo '<link rel="stylesheet" href="css/smartpayroll.css">';
echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-random"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Post miscellaneous transactions</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

$InputError = 0;

if(isset($_POST['insertitem'])){
    if(!isset($_POST['pfno'])){
        prnMsg('Please select an employee'.'warn');
          unset($_POST['insertitem']);
    }
  
}

if(isset($_POST['deleteitem'])){
    foreach ($_POST['pfno'] as $pfno) {
          DB_query("DELETE FROM [prlmatrix] WHERE [pfno]='".$pfno."'  and  [prodid]='".$_POST['code']."'",$db);
    }
}

if(isset($_POST['insertitem'])){
    $SQL=array();
     
     DB_Txn_Begin($db);
     
     $code = DB_escape_string($_POST['code']);
     $amount = floatval($_POST['amount']);
     $nonrecuring = (isset($_POST['nonrecuring']) && $_POST['nonrecuring'] == 'on') ? 1 : 0;
     $userName = DB_escape_string($_SESSION['UsersRealName']);
     
     $prod_sql = "SELECT description, deduction, employerfactor FROM prlproducts WHERE code = '" . $code . "'";
     $prod_result = DB_query($prod_sql, $db);
     $prod_row = DB_fetch_array($prod_result);
     $product_name = $prod_row['description'];
     $product_deduction = $prod_row['deduction'];
     $employer_factor = floatval($prod_row['employerfactor']);
     
     foreach ($_POST['pfno'] as $pfno_raw) {
        $pfno = DB_escape_string($pfno_raw);
        
        $SQL[] = "INSERT INTO auditprlmatrix (pfno, prodid, name, employeramount, deduction, nonrecuring, amount, posted, userid)
            VALUES ('" . $pfno . "', '" . $code . "', '" . DB_escape_string($product_name) . "', " . ($amount * $employer_factor) . ", " . $product_deduction . ", " . $nonrecuring . ", " . $amount . ", NOW(), '" . $userName . "')";
      
        $SQL[] = "DELETE FROM prlmatrix WHERE pfno='" . $pfno . "' AND prodid='" . $code . "'";
        
        $SQL[] = "INSERT INTO prlmatrix (pfno, prodid, name, employeramount, deduction, nonrecuring, amount)
            VALUES ('" . $pfno . "', '" . $code . "', '" . DB_escape_string($product_name) . "', " . ($amount * $employer_factor) . ", " . $product_deduction . ", " . $nonrecuring . ", " . $amount . ")";
     }
     
       foreach ($SQL as $value) {
           DB_query($value,$db);
       }
  

        if (DB_error_no($db)>0) { 
             DB_Txn_Rollback($db);
        } else { 
            DB_Txn_Commit($db);
        }
   

}

$mypage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$SQL = "SELECT code,description,deduction,hastable  "
     . " from prlproducts where code !='999999' and hastable=0 "
     . " and code not in (select [deductcode] from [prlstaffloans]) "
     . " order by description asc";
$ResultIndex = DB_query($SQL,$db);
   
  echo '<table class="table table-striped"><tr><td>Payroll Items</td><td>';
  echo '<select name="code">';
  while($rows = DB_fetch_array($ResultIndex)){
        echo '<option value="'.$rows['code'].'" '.($_POST['code']==trim($rows['code'])?'selected="selected"':'').' >'. ucwords($rows['description']).' ['.($rows['deduction']==0?'Deduction':'Allowance').']'.'</option>';
  }
  echo '</select></td></tr><tr><td>Select Employee</td><td>';
  echo '<select name="pfno[]" multiple  >';
        $SQL1="select * from prlemployeemaster where "
        . " (([dateterminated]<[dateemployed]) "
        . " or ([dateterminated] is null)) "
                . " order by dateemployed asc ";
$Result = DB_query($SQL1,$db);
while($prows = DB_fetch_array($Result)){
    echo '<option value="'.$prows['pf_no'].'">'.$prows['pf_no'].' : '.$prows['fname'].' '.$prows['mname'].' '.$prows['lname'].'</option>';
}

echo '</select></td></tr>';
echo '<tr><th>Enter Amount</th><td><input type="text" name="amount" class="number"/></td></tr>';
echo '<tr><th>Non-ReOccuring</th><td><input type="checkbox" name="nonrecuring" checked="unchecked"/>Should Deduct/Allow only Once</td></tr>';
echo '<tr><td></td><td><input type="submit" name="deleteitem" value="Remove from payroll"/>'
    . '<input type="submit" name="insertitem" value="Add To payroll"/></td></tr>';
echo '</table>';
echo '</form>';
    
Refreshpayroll();

$matrix = new emplyeematrix();
$matrix->ShowbyProducts();
echo '<div class="centre"><input type="button" onclick="tableToExcel(\'Export\', \'Master Roll\')" value="Export to Excel"></div>';

echo '</div>';
echo '</div>';
include('includes/footer.inc');
?>
