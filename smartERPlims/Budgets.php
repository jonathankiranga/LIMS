<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Setting Up Budgets');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.


$thispage=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

$FR = new FinancialPeriods();
 
echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Dimension Budgets') .'" alt="" />'
. ' ' . _('Dimension Budgets') . '</p>';

if(isset($_GET['periodno'])){
    $period=$_GET['periodno'];
} 
    
if(isset($_POST['Financial_Periods'])){
    $period=$_POST['Financial_Periods'];
}

if(isset($_POST['submit'])){
    if(isset($_POST['editbudget'])){
        updatebudget();
    }else{
        createbudget();
    }
}


echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post" id="budgetform">';
echo '<div class="table-responsive">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($period)){
    $_POST['Financial_Periods'] = $period;
    echo '<input type="hidden" name="editbudget" value="TRUE" />';
    echo '<input type="hidden" name="fperiod" value="'.$period.'" />';
}

if(isset($_POST['projectcode'])){
    if(mb_strlen($_POST['projectcode'])>0){
      echo '<input type="hidden" name="fprojectcode" value="'.$_POST['projectcode'].'" />';
    }else{
      echo '<input type="hidden" name="fprojectcode" />';
    }
}

echo '<table class="table table-bordered"><tr><th colspan="4">SetUp Budgets against Projects for the Financial Period</th></tr>';
echo '<tr>'. Getyearselect().'<td>Select Project :<select name="projectcode" onchange="ReloadForm(budgetform.refresh);"><option selected="selected"></option>';

$Result = DB_query("select Dimensions.Code,Dimensions.Dimension from Dimensions "
        . " join companies on (Dimensions.id=DefaultDimension_2) and `companies`.`coycode`=1",$db);
while($rows = DB_fetch_array($Result)){
    echo sprintf('<option value="%s" %s>%s</option>',
         $rows['Code'],($rows['Code']==$_POST['projectcode']?'selected="selected"':''),
         $rows['Dimension']);
}

echo '</select></td></tr>' ;

 if(mb_strlen($_POST['projectcode'])>0){
     $Result = DB_query("select distinct
	Budgets.periodno,
	max(FinancialPeriods.`end_date`) as ENDDATE,
	min(FinancialPeriods.`start_date`) as STARTDATE,
	(select sum(B.amount) from Budgets B where B.periodno=Budgets.periodno) as BUDGET
	from Budgets 
        join FinancialPeriods on Budgets.periodno=FinancialPeriods.`periodno`
        where Budgets.`dimecode2`='".$_POST['projectcode']."'
	group by Budgets.periodno,
	Budgets.amount 
        order by Budgets.periodno",$db);
 }else{
     $Result = DB_query("select distinct
	Budgets.periodno,
	max(FinancialPeriods.`end_date`) as ENDDATE,
	min(FinancialPeriods.`start_date`) as STARTDATE,
	(select sum(B.amount) from Budgets B where B.periodno=Budgets.periodno) as BUDGET
	from Budgets 
        join FinancialPeriods on Budgets.periodno=FinancialPeriods.`periodno`
	group by Budgets.periodno,
	Budgets.amount 
        order by Budgets.periodno",$db);
 }
 
echo '<tr><td colspan="2" valign="top"><table class="table table-bordered"><tr><th>Existing <br/> Budgets by  <br/>Financial Period</th><th class="number">Total Budget</th></tr>';
$i=-1;

while($rows = DB_fetch_array($Result)){
    $i+=2;
    echo sprintf('<tr><td><a href="%s">Click to reveal budget %s</a></td>'
            . '<td><input type="text" class="number" value="%s" readonly="readonly" /></td></tr>',
            ($thispage.'?periodno='.$rows['periodno']),'<br/>Between '. $rows['STARTDATE'].' AND '. $rows['ENDDATE'], number_format($rows['BUDGET'],2) );
}

if($i<0){
     echo '<tr><td>No Data</td><td><a href="DimensionTypes.php">Create New Dimensions</td></tr>';
}

echo '<tr><td colspan="2">'
. '<input type="submit" name="refresh" value="Refresh"/>'
. '<input type="submit" name="submit" value="Save Budget"/></td></tr>';

echo '</table></td><td><div class="table-responsive mini">';

echo '<table class="table-bordered"><tr><th>Dimension Name</th><th class="number">BUDGET</th></tr>';

$Result = DB_query("select Dimensions.Code,Dimensions.Dimension from Dimensions join companies on 
                    (Dimensions.id=companies.DefaultDimension_1) 
                    and `companies`.`coycode`=1 order by Dimension",$db);
$Total=0;

while($rows = DB_fetch_array($Result)){
    
    if(isset($_GET['periodno']) or isset($_POST['Financial_Periods'])){
       $Bamount= Getamount($rows['Code']);
    }else{
       $Bamount= $_POST['amount'][$rows['Code']];
    }
    
    echo sprintf('<tr><td>%s</td><td><input type="text" class="number" name="amount[%s]" value="'.$Bamount.'"/>'
            . '</td></tr>',  $rows['Dimension'],$rows['Code'],$rows['Code'] );
    $Total += $Bamount;
}

echo '<tr><td>TOTAL</td><td class="number">'.number_format($Total).'</td></tr>';
echo '</table></div></td>';
echo '</tr></table>';
echo '</div></form>' ;

include('includes/footer.inc');


function Getamount($dim){
    global $db;
    
    if(isset($_GET['periodno'])){
        $period=$_GET['periodno'];
    } 
    
    if(isset($_POST['Financial_Periods'])){
         $period=$_POST['Financial_Periods'];
    }
    
     if(mb_strlen($_POST['projectcode'])>0){
         $SQL="SELECT  
               `periodno`
              ,`dimecode`
              ,`amount`
           FROM `Budgets` where 
           `periodno`='". $period."'  "
                 . " and `dimecode`='".$dim."' "
                 . " and `dimecode2`='".$_POST['projectcode']."'";
     } else {
        $SQL="SELECT  
              `periodno`
             ,`dimecode`
             ,sum(`amount`)
          FROM `Budgets` where 
          `periodno`='". $period."'"
                . " and `dimecode`='".$dim."'"
                . " group by `periodno`,`dimecode`";
     }
     
    $ResultIndex=DB_query($SQL, $db);
    $rows = DB_fetch_row($ResultIndex);
    
    return $rows[2];
}

function createbudget(){
    Global $db;
    $sql=array();
    
    foreach ($_POST['amount'] as $dimcode => $budget) {
        if($budget>0){
         $sql[]="insert into `Budgets` (`periodno`,`dimecode`,`amount`,`dimecode2`) "
              . " values ('".$_POST['Financial_Periods']."','".$dimcode."','".$budget."','".$_POST['projectcode']."')";
        }
    }
   
    DB_Txn_Begin($db);
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
    }else{
       DB_Txn_Commit($db);
       unset($_POST);
    }
}

function updatebudget(){
   Global $db;
    $sql=array();
    
    
 if(mb_strlen($_POST['projectcode'])>0){
    $sql[]="Delete from `Budgets` "
            . " where `periodno`='".$_POST['fperiod']."' "
            . " and `dimecode2`='".$_POST['projectcode']."'";
    
    foreach ($_POST['amount'] as $dimcode => $budget) {
        if($budget>0){
            $sql[]="insert into `Budgets` (`periodno`,`dimecode`,`amount`,`dimecode2`) "
          . " values ('".$_POST['fperiod']."','".$dimcode."','".$budget."','".$_POST['projectcode']."')";
        }
    }
    
 } else {
   $sql[]="Delete from `Budgets`  where `periodno`='".$_POST['fperiod']."' ";
    
    foreach ($_POST['amount'] as $dimcode => $budget) {
        if($budget>0){
            $sql[]="insert into `Budgets` (`periodno`,`dimecode`,`amount`) "
          . " values ('".$_POST['fperiod']."','".$dimcode."','".$budget."')";
        }
    }
    
 }   
   
    DB_Txn_Begin($db);
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
    }else{
       DB_Txn_Commit($db);
       unset($_POST);
    }
}
    
function  Getyearselect(){
         Global $db;
         
        $SelectObject='<td>Select Year as the ID</td><td><select name="Financial_Periods">';
        $ResultIndex=DB_query("Select `periodno`,MAX(`end_date`) as year from `FinancialPeriods` Group by `periodno` order by `periodno` desc", $db);
        while($row=DB_fetch_array($ResultIndex)){
             $SelectObject .='<option value="'.$row['periodno'].'" '.(($_POST['Financial_Periods']==trim($row['periodno']))?'selected="selected"':'').'  >'.$row['year'].'</option>';
        }
        $SelectObject .='</select></td>';
        
     return $SelectObject;   
    }
?>