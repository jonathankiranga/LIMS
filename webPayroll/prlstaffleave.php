<?php
include('includes/session.inc');
$Title = "Request for Leave";

include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include("ExtFunc/humanresource.inc");
include('ExtFunc/employeetypes.inc');
include('ExtFunc/salary.inc') ;
include('Extfunc/gensalary.inc');


$myself = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
  
if(isset($_GET['leaveid'])) {
    $_POST['pfno'] = $_GET['leaveid'];
}

if(isset($_POST['selectedpfno'])){
   $_POST['pfno']=$_POST['selectedpfno'];
}

if(isset($_POST['applyforleave'])){
    
    $datefrom   = ConvertSQLDateTime($_POST['fromdate']) ;
    $todayornow = Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,Date('m'),0,Date('Y')));
    $year       = date('Y');

    if($_POST['authoriseddays'] < $_POST['nodays']){
        echo '<p>' ;
        prnMsg('You have applied for more days then authorised to','warn');
        echo '</p>';
        
     } elseif( $_POST['pfhandover']=='No selection') {
        echo '<p>' ;
        prnMsg('You have not selected a replacement staff member','warn');
        echo '</p>';
       
    } elseif(mydaysdiff($datefrom,$todayornow)<0){
       echo '<p>' ;
       prnMsg('You can not back date a leave application','warn');
       echo '</p>';
    
    } else {

        $year     = date('Y');
        $FromDate = str_replace('/','-',FormatDateForSQL($_POST['fromdate']).' 00:00:00');
        
        $sql = sprintf("INSERT INTO [prlstaffleaveplanner]
        ([refno],[pfno],[year],[leavedue],[leavend],[status],[handover],[typeofleave],[days])
         VALUES ('%s','%s',%s,'%s','%s',%s,'%s',%s,%s)",$_POST['refno'], $_POST['selectedpfno'],
        $year, $FromDate,$_POST['todate'],'1', $_POST['pfhandover'],$_POST['typeofleave'] ,$_POST['nodays']);

        DB_query($sql,$db);

        addApproveLeave($_POST['refno']);
  
        unset($_POST);
    }
   
}

echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-calendar-alt"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>Submit and track leave applications</p>';
echo '</div>';
echo '</div>';

echo '<div class="sp-content">';
echo '<link rel="stylesheet" href="css/smartpayroll.css">';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" id="PRLleave">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($_POST['pfno']) or isset($_POST['selectedpfno'])) {
    
$names = showname($_POST['pfno']);
echo '<p class="page_title_text">'. ' ' .ucwords($names). '</p>';
$daysleft = GetleaveDaysBalance($_POST['pfno']);

if(isset($_POST['fromdate'])){
    if(mb_strlen($_POST['fromdate']>1)) {
        SetExpecteddateofreturn($_POST['fromdate'],$_POST['nodays']);
        $ResultIndex = DB_query('EXEC [dbo].[SetExpecteddateofreturn]', $db); 
        $row  = DB_fetch_row($ResultIndex);
        $ToDate = ConvertSQLDate($row[0]);
        echo '<input type="hidden" name="todate" value="'. $row[0].'"/>';
    }
}else{
     $ToDate ='';
}
        
echo '<div class="sp-card">';
echo '<div class="sp-card-header"><h3>APPLICATION FORM</h3></div>';
echo '<div class="sp-card-body">';
echo '<table class="sp-table"><tr>';
echo '<input type="hidden" name="authoriseddays" value="'. $daysleft .'"/>';
echo '<input type="hidden" name="selectedpfno" value="'. $_POST['pfno'] .'"/>'
   . '<input type="hidden" name="staffdepartment" value="'. getemployeedepartment($_POST['pfno']) .'"/>';

echo  '</th><th></th></tr><tr><td><input type="button" value="Leave Application No" id="getleaveid"/></td>'
   . '<td><input type="text" name="refno" id="prleaveid" required="required" readonly="readonly" value="'.$_POST['refno'].'"/></td></tr>
      <tr><td>From Date</td><td><input required="required" type="text" alt="'.$_SESSION['DefaultDateFormat'].'" class="date"  name="fromdate" value="'.$_POST['fromdate'].'" size="10"/></td></tr>
      <tr><td>How Many Days</td><td><input required="required" type="text"  class="number"  name="nodays"  value="'.$_POST['nodays'].'"  size="10"/></td></tr>
     <tr><td>Expected Date of return</td><td><label>'.$ToDate.'</label></td></tr><tr><td>Type of Leave</td>
      <td><select name="typeofleave" required="required" onchange="ReloadForm(PRLleave.refresh);"><option></option>';
    
      foreach ($leavetype as $key => $value) {
         echo  '<option value="'.$key.'"  '.((trim($key)==trim($_POST['typeofleave']))?'selected="selected"':'').'>'.$value.'</option>';
       }
       
echo '</select></td></tr><tr><td>Handing Over To</td>'
   . '<td><select name="pfhandover">'. GetSbranch().'</select></td></tr>';
   echo '</table>';
   echo '<div class="sp-d-flex sp-gap-3">';
   echo '<button type="submit" name="refresh" class="sp-btn sp-btn-secondary"><i class="fas fa-sync"></i> Refresh</button>';
   echo '<button type="submit" name="applyforleave" class="sp-btn sp-btn-success"><i class="fas fa-paper-plane"></i> Apply For Leave</button>';
   echo '</div>';
   echo '</div>';
   echo '</div>';
   
}

if(isset($_GET['getapp'])){

    $names = showname($_GET['getapp']);
    echo '<div class="sp-alert sp-alert-info"><i class="fas fa-user"></i> ' . $names . '</div>';
    echo '<table class="sp-table"><thead><tr>
        <th>Application Ref No</th>
        <th>From Date</th>
        <th>To Date</th>
        <th>No of Days</th>
        <th>Substitute</th>
        <th>Status</th></tr></thead><tbody>';

 $sql="select p.* , e.[status]+' '+ e.[fname]+' '+e.[mname]+'  '+e.[lname] as names
      from [prlstaffleaveplanner] p join prlemployeemaster e
      on p.[handover]=e.[pf_no] where p.pfno='".$_GET['getapp']."'";

        $ResultIndex = DB_query($sql,$db);
        while($row=DB_fetch_array($ResultIndex)){
        echo '<tr><td>'.$row['refno'].'</td>
                  <td>'.ConvertSQLDate($row['leavedue']).'</td>
                  <td>'.ConvertSQLDate($row['leavend']).'</td>
                  <td>'.$row['days'].'</td>
                  <td>'.$row['names'].'</td>
                  <td>'.$approvalstatus[$row['status']].'</td>
              </tr>';
       }

    echo'</table>';


} 

    if(!isset($_POST['pfno']) and !isset($_GET['getapp'])) {
        
            echo '<table class="sp-table"><thead>
                   <tr><th>Payroll ID</th>
                   <th>ID Number</th>
                   <th>Names</th>
                   <th>Department</th>
                   <th>PIN No</th>
                   <th>Tel No</th>
                   <th>Actions</th></thead><tbody>';

            $sql="SELECT
               pm.[pf_no] , pm.[status] ,pm.[fname] , pm.[mname] , pm.[lname] , pm.[idno],
               pm.[pin_no] , pm.[nssf_no] , pm.[nhif_no], pm.[telno] , pm.[email] ,
               lc.[name] as branch, sum(lp.days) as days
               FROM [prlemployeemaster] pm
               left join [prldepartments] lc on  pm.[department]=lc.code
               left join [prlstaffleaveplanner] lp on pm.[pf_no] = lp.pfno and (lp.status=1)
               where  (([dateterminated]<[dateemployed]) or ([dateterminated] is null))
               group by pm.[pf_no] ,pm.[status] ,
               pm.[fname] , pm.[mname] , pm.[lname] , pm.[idno], pm.[pin_no] ,
               pm.[nssf_no] , pm.[nhif_no], pm.[telno] , pm.[email] , lc.[name]";


            $ResultIndex = DB_query($sql,$db);
            while($row = DB_fetch_array($ResultIndex)){

           echo '<tr><td><button type="submit" name="pfno" class="sp-btn sp-btn-sm sp-btn-primary" value="'. rtrim($row['pf_no']).'">'. rtrim($row['pf_no']).'</button></td>';
           echo '<td><a href="'.$myself.'?getapp='.rtrim($row['pf_no']).'" class="sp-btn sp-btn-sm sp-btn-secondary"><i class="fas fa-eye"></i> View</a></td>';
           echo '<td>'.$row['fname'].' '.$row['mname'].' '.$row['lname'].'</td>';
           echo '<td>'.$row['branch'].'</td>';
           echo '<td>'.$row['paye_no'].'</td>';
           echo '<td>'.$row['telno'].'</td>';
           echo '<td>'.$row['email'].'</td>';
           echo '</tr>';

           }
echo'</tbody></table>';
echo '<div class="sp-mt-3"><a href="'.$myself.'" class="sp-btn sp-btn-outline"><i class="fas fa-arrow-left"></i> Back</a></div>';
    }
echo '</form>';
echo '</div>';
echo '</div>';
include('includes/footer.inc');


function addApproveLeave($docno){
 Global $db;
 
    $sqlarray=array();

    $sqlsmt=sprintf("SELECT  [userdepartment] ,[positionapprover], [positionaplevel]
     FROM [prlauthorizers] where [userdepartment]='%s' order by [positionaplevel] desc ",$_POST['staffdepartment']);

    $ResultIndex = DB_query($sqlsmt,$db);
       if(DB_num_rows($ResultIndex)==0){
           prnMsg('This Department has not been setup with approvals','warn');
       } else {

               $ResultIndex = DB_query($sqlsmt,$db);
               while($rowes=DB_fetch_array($ResultIndex)){
               $sqlarray[] = sprintf("INSERT INTO [prlleaveapprovaltrans]  ([docno],[userdepartment],[position],[authoritylevel])
                VALUES ('%s','%s','%s','%s')",$docno,$rowes['userdepartment'],$rowes['positionapprover'],$rowes['positionaplevel'] );
               }

               foreach ($sqlarray as $command) {
                  DB_query($command,$db);
               }
      }
}
             
function getemployeedepartment($pfno){
        global $db;
        $results = DB_query("SELECT department FROM prlemployeemaster where pf_no='".$pfno."'",$db);
        $row = DB_fetch_row($results);
        return (is_null($row[0])?'':$row[0]);
}

function emailrequests(){
   Global $db;
   
    $emailsql=sprintf("Select email from  prlemployeemaster where position in 
   (select positionapprover from prlauthorizers where userdepartment='%s')",$_POST['staffdepartment']);
    
       $ResultIndex=DB_query($emailsql,$db);
       while($rowes=DB_fetch_array($ResultIndex)){
           email($rowes['email']);
       }
}
        
function email($Recipients=''){
 global $pathtourlapprove;

 $EmailText='<a href="'.$pathtourlapprove.'">Please Approve Leave Document no :'.$_POST['refno'].'</a>';

 $mymailer = new MyMailer();
 $mymailer->sendmail($Recipients,'Leave Application approval',$EmailText);
}
  
FUNCTION GetSbranch(){
        global $db;
        
        $pfnos  = trim($_POST['pfno']);
        $option ='<option>No selection</option>';
        

      if(isset($_POST['fromdate'])){
          $dateto = FormatDateForSQL($_POST['fromdate']) ;
      
          $sql="SELECT  pm.[pf_no] , pm.[status] , pm.[fname] , pm.[mname] , pm.[lname] 
           FROM [prlemployeemaster] pm  
           left join [prlestablishment] lc on pm.[branch]=lc.code 
           where (pm.[freqcode]>0) and (pm.[pf_no] !='".$_POST['pfno']."') 
           and (lc.code in (select branch from [prlemployeemaster] where pf_no='".$_POST['pfno']."'))
           and (pm.[pf_no] not in ( SELECT [pfno] from [prlstaffleaveplanner] where '".$dateto."' between leavedue and leavend)) 
           and (pm.[pf_no] not in ( SELECT [pf_no] from [prlemployeemaster] where [dateterminated] < '".$dateto."') )";

            $ResultIndex = DB_query($sql,$db);
            while($row=DB_fetch_array($ResultIndex)){
                if(isset($_POST['pfhandover'])){
                    $option .="<option value='".trim($row['pf_no'])."' ".((trim($row['pf_no'])==trim($_POST['pfhandover']))?'selected="selected"':'').">".$row['status'].' '.$row['fname'].' '.$row['mname'].' '.$row['lname']."</option>";
                }else{
                    $option .="<option value='".trim($row['pf_no'])."'>".$row['status'].' '.$row['fname'].' '.$row['mname'].' '.$row['lname']."</option>";
                }
           }
    }
    
     return $option;
}


?>
