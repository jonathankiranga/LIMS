<?php

$employment = array();
$employment[0] = 'Casual';
$employment[1] = 'Permanent';
$employment[2] = 'Contract';
$employment[3] = 'Attachment';

$approvalstatus = array();
$approvalstatus[0] = 'Cancelled' ;
$approvalstatus[1] = 'Waiting Authorization' ;
$approvalstatus[2] = 'Approved' ;
$approvalstatus[3] = 'On-Leave' ;

$leavetype = array();
$leavetype[1] = 'Annual Leave';
$leavetype[2] = 'Study Leave';
$leavetype[3] = 'Accademic Leave';
$leavetype[4] = 'Merternity';
$leavetype[5] = 'Perternity Leave';

$interestarray = array();
$interestarray[1] = 'Reducing Balance';
$interestarray[2] = 'Straight line';

function getemail($pfno){
       global $db;
        $identity=array();

        $sql="SELECT  "
                . "pm.[pf_no],"
                . "pm.[status],"
                . "pm.[fname],"
                . "pm.[mname],"
                . "pm.[lname],"
                . "pm.[email] "
                . "FROM [prlemployeemaster] pm  "
                . "where pm.pf_no='".$pfno."'";
        $ResultIndex = DB_query($sql,$db);
        while($row=DB_fetch_array($ResultIndex)){
            $identity[] = $row;
        }

      return  $identity ;
}

if(isset($_POST['getpf'])){
    if(isset($_POST['bpay'])){
        $sql = sprintf("update prlemployeemaster "
                . " set basicpay=%f ,"
                . "[insurancerelief]=%f , "
                . "[mortagerelief]=%f ,"
                . " salaryscale='%s' ,"
                . " salaryscale2='%s' "
                . " where pf_no='%s'"
                ,$_POST['bpay'], 
                $_POST['Insrelief'],
                $_POST['MortgageRelief'],
                $_POST['salaryscale'],
                $_POST['salaryscale2'],
                $_POST['pfno'] );
        
        
        DB_query($sql,$db);
        unset($_POST['bpay']);
        prnMsg('You have updated '.$_POST['pfno'].' Successfuly');
    }
    unset($_POST['getpf']);
}

function showname($pfno ){
        global $db;
        $identity='';

        $sql="SELECT  pm.[pf_no],pm.[status],pm.[fname],pm.[mname],pm.[lname] "
                . " FROM [prlemployeemaster] pm  where pm.pf_no='".$pfno."'";
        $ResultIndex = DB_query($sql,$db);
        while($row=DB_fetch_array($ResultIndex)){
            $identity .= $row['status'].' '.$row['fname'].' '.$row['mname'].' '.$row['lname'];
        }

return  ucwords($identity) ;
}

FUNCTION GETEMPLOYEE($pfno){
    global $db;

   $sql = "SELECT   
        CASE 
            WHEN (dateterminated > NOW() OR dateterminated IS NULL OR dateterminated = '0000-00-00 00:00:00') THEN 0 
            ELSE 1 
        END AS Active,
        pm.pf_no, 
        pm.status, 
        pm.fname, 
        pm.mname, 
        pm.lname, 
        pm.idno,
        pm.pin_no, 
        pm.nssf_no, 
        pm.nhif_no, 
        pm.freqcode, 
        pm.branch, 
        pm.basicpay,
        pm.noofhrsperday,
        lc.name AS branch, 
        pm.position, 
        pm.salaryscale, 
        pm.salaryscale2,
        pm.insurancerelief, 
        pm.mortagerelief,
        g1.name AS c1, 
        g2.name AS c2, 
        g3.name AS c3, 
        g4.name AS c4,
        g5.name AS c5,
        g6.name AS c6, 
        g7.name AS c7, 
        g8.name AS c8,
        g9.name AS c9, 
        g10.name AS c10
FROM prlemployeemaster pm
LEFT OUTER JOIN prlgeneralitems g1 ON g1.code = pm.property1
LEFT OUTER JOIN prlgeneralitems g2 ON g2.code = pm.property2
LEFT OUTER JOIN prlgeneralitems g3 ON g3.code = pm.property3
LEFT OUTER JOIN prlgeneralitems g4 ON g4.code = pm.property4
LEFT OUTER JOIN prlgeneralitems g5 ON g5.code = pm.property5
LEFT OUTER JOIN prlgeneralitems g6 ON g6.code = pm.property6
LEFT OUTER JOIN prlgeneralitems g7 ON g7.code = pm.property7
LEFT OUTER JOIN prlgeneralitems g8 ON g8.code = pm.property8
LEFT OUTER JOIN prlgeneralitems g9 ON g9.code = pm.property9
LEFT OUTER JOIN prlgeneralitems g10 ON g10.code = pm.property10
LEFT JOIN prldepartments lc ON pm.department = lc.code
WHERE pm.pf_no = '".$pfno."'";

        $ResultIndex = DB_query($sql,$db);
        return $ResultIndex ;
}

FUNCTION GETALLEMPLOYEES(){
    global $db;

    $sql = "SELECT   
        CASE 
            WHEN (dateterminated > NOW() OR dateterminated IS NULL OR dateterminated = '0000-00-00 00:00:00') THEN 0 
            ELSE 1 
        END AS Active,
        pm.pf_no, 
        pm.status, 
        pm.fname, 
        pm.mname, 
        pm.lname, 
        pm.idno,
        pm.pin_no, 
        pm.nssf_no, 
        pm.nhif_no, 
        pm.freqcode, 
        pm.branch, 
        pm.basicpay,
        pm.noofhrsperday,
        lc.name AS branch, 
        pm.position, 
        pm.salaryscale, 
        pm.salaryscale2,
        pm.insurancerelief, 
        pm.mortagerelief,
        g1.name AS c1, 
        g2.name AS c2, 
        g3.name AS c3, 
        g4.name AS c4,
        g5.name AS c5,
        g6.name AS c6, 
        g7.name AS c7, 
        g8.name AS c8,
        g9.name AS c9, 
        g10.name AS c10
FROM prlemployeemaster pm
LEFT OUTER JOIN prlgeneralitems g1 ON g1.code = pm.property1
LEFT OUTER JOIN prlgeneralitems g2 ON g2.code = pm.property2
LEFT OUTER JOIN prlgeneralitems g3 ON g3.code = pm.property3
LEFT OUTER JOIN prlgeneralitems g4 ON g4.code = pm.property4
LEFT OUTER JOIN prlgeneralitems g5 ON g5.code = pm.property5
LEFT OUTER JOIN prlgeneralitems g6 ON g6.code = pm.property6
LEFT OUTER JOIN prlgeneralitems g7 ON g7.code = pm.property7
LEFT OUTER JOIN prlgeneralitems g8 ON g8.code = pm.property8
LEFT OUTER JOIN prlgeneralitems g9 ON g9.code = pm.property9
LEFT OUTER JOIN prlgeneralitems g10 ON g10.code = pm.property10
LEFT JOIN prldepartments lc ON pm.department = lc.code";

        $ResultIndex = DB_query($sql,$db);
        return $ResultIndex ;
}

FUNCTION showbasicpayforall(){
    global $db;


   $sql = "SELECT CASE 
            WHEN (dateterminated > NOW() OR dateterminated IS NULL OR dateterminated = '0000-00-00 00:00:00') THEN 0 
            ELSE 1 
        END AS Active,
        pm.pf_no, 
        pm.status, 
        pm.fname, 
        pm.mname, 
        pm.lname, 
        pm.idno,
        pm.pin_no, 
        pm.nssf_no, 
        pm.nhif_no, 
        pm.telno, 
        pm.email, 
        pm.freqcode,
        pm.noofhrsperday, 
        lc.name AS branch, 
        pm.position, 
        pm.basicpay,
        COALESCE(ss.annual_inc, 0) AS increment, 
        COALESCE(ss.min, 0) AS min, 
        COALESCE(ss.max, 0) AS max,
        pm.insurancerelief, 
        pm.mortagerelief,
        g1.name AS c1, 
        g2.name AS c2, 
        g3.name AS c3, 
        g4.name AS c4,
        g5.name AS c5, 
        g6.name AS c6, 
        g7.name AS c7, 
        g8.name AS c8,
        g9.name AS c9, 
        g10.name AS c10
FROM prlemployeemaster pm
LEFT JOIN prldepartments lc ON pm.department = lc.code
LEFT JOIN prlgeneralitems g1 ON g1.code = pm.property1
LEFT JOIN prlgeneralitems g2 ON g2.code = pm.property2
LEFT JOIN prlgeneralitems g3 ON g3.code = pm.property3
LEFT JOIN prlgeneralitems g4 ON g4.code = pm.property4
LEFT JOIN prlgeneralitems g5 ON g5.code = pm.property5
LEFT JOIN prlgeneralitems g6 ON g6.code = pm.property6
LEFT JOIN prlgeneralitems g7 ON g7.code = pm.property7
LEFT JOIN prlgeneralitems g8 ON g8.code = pm.property8
LEFT JOIN prlgeneralitems g9 ON g9.code = pm.property9
LEFT JOIN prlgeneralitems g10 ON g10.code = pm.property10
LEFT JOIN prlsalaryscale ss ON ss.code = pm.salaryscale";

        $ResultIndex = DB_query($sql,$db);
        return $ResultIndex ;
}

function getcustom(){
    global $db ;
    $customheader = array();

    $sql = "select code,type from prltypes limit 6" ;
    $ResultIndex = DB_query($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $customheader[$row['code']] = $row['type'];
       }


    return $customheader;
}

function getcustomvalues($codes,$value){
    global $db ;

    $child="select name from prlgeneralitems where type='".$codes."' and code='".$value."'";
    $ResultIndex = DB_query($child,$db);
    $row = DB_fetch_row($ResultIndex);
    echo $row[0];
}

function getloanbalance($pfno){
    global $db;

    $amount=0;
    
  $sql = "SELECT 
            (COALESCE(prlstaffloans.principal, 0) - COALESCE(prlstaffloans.openbalance, 0)) - COALESCE(SUM(prlloantrans.amount), 0) AS balance
        FROM prlstaffloans
        JOIN prlloantrans ON prlstaffloans.loanindex = prlloantrans.loanindex
        WHERE prlstaffloans.pfno = '".$pfno."'
          AND (prlstaffloans.saving = 0 OR prlstaffloans.saving IS NULL)
        GROUP BY prlstaffloans.principal, prlstaffloans.openbalance";

$ResultIndex = DB_query($sql, $db);
while ($row = DB_fetch_array($ResultIndex)) {
    $amount += $row['balance'];
}
return $amount;
}

function getsavingsbalance($pfno){
    global $db;

       $amount=0;
 
    $sql = "SELECT 
            (COALESCE(prlstaffloans.principal, 0) - COALESCE(prlstaffloans.openbalance, 0)) - COALESCE(SUM(prlloantrans.amount), 0) AS balance
        FROM prlstaffloans
        JOIN prlloantrans ON prlstaffloans.loanindex = prlloantrans.loanindex
        WHERE prlstaffloans.pfno = '".$pfno."'
          AND (prlstaffloans.saving = 1)
        GROUP BY prlstaffloans.principal, prlstaffloans.openbalance";

$ResultIndex = DB_query($sql, $db);
while ($row = DB_fetch_array($ResultIndex)) {
    $amount += $row['balance'];
}
    
return $amount;
}

function getloanset($pfno){
    global $db;

     $sql = "SELECT pm.pf_no,
          pm.fname,
          pm.mname,
          pm.lname,
          pm.idno,
          ps.loanindex,
          ps.pfno,
          ps.deductcode,
          ps.principal,
          ps.instalment,
          ps.openbalance,
          ps.interest,
          ps.interesttype,
          ps.startdate,
          prlproducts.description,
          (COALESCE(ps.principal, 0) - COALESCE(ps.openbalance, 0)) - COALESCE(SUM(pl.amount), 0) AS balance
      FROM prlemployeemaster pm
      LEFT JOIN prlstaffloans ps ON pm.pf_no = ps.pfno
      JOIN prlproducts ON ps.deductcode = prlproducts.code
      LEFT JOIN prlloantrans pl ON ps.loanindex = pl.loanindex
      WHERE pm.pf_no = '".$pfno."' AND ps.saving = 0 
      GROUP BY pm.pf_no,
               pm.fname,
               pm.mname,
               pm.lname,
               pm.idno,
               ps.loanindex,
               ps.pfno,
               ps.deductcode,
               ps.principal,
               ps.instalment,
               ps.interest,
               ps.interesttype,
               ps.startdate,
               prlproducts.description,
               ps.openbalance";

      $ResultIndex = DB_query($sql,$db);
      return $ResultIndex;
}


function getsaccoset($pfno){
    global $db;

     $sql = "SELECT pm.pf_no,
          pm.fname,
          pm.mname,
          pm.lname,
          pm.idno,
          ps.loanindex,
          ps.pfno,
          ps.deductcode,
          ps.principal,
          ps.instalment,
          ps.openbalance,
          ps.interest,
          ps.interesttype,
          ps.startdate,
          prlproducts.description,
          (COALESCE(ps.principal, 0) - COALESCE(ps.openbalance, 0)) - COALESCE(SUM(pl.amount), 0) AS balance
      FROM prlemployeemaster pm
      LEFT JOIN prlstaffloans ps ON pm.pf_no = ps.pfno
      JOIN prlproducts ON ps.deductcode = prlproducts.code
      LEFT JOIN prlloantrans pl ON ps.loanindex = pl.loanindex
      WHERE pm.pf_no = '".$pfno."' AND ps.saving = 1 
      GROUP BY pm.pf_no,
               pm.fname,
               pm.mname,
               pm.lname,
               pm.idno,
               ps.loanindex,
               ps.pfno,
               ps.deductcode,
               ps.principal,
               ps.instalment,
               ps.interest,
               ps.interesttype,
               ps.startdate,
               prlproducts.description,
               ps.openbalance";
      
      $ResultIndex = DB_query($sql,$db);
      return $ResultIndex;
}

function getpayrollproduct(){
     global $db;

    $sql="select * from prlproducts where hastable='0' and [deduction]='false'  ";
    $ResultIndex = DB_query($sql,$db);
    echo '<select name="deductcode" style="width:100px" required="true">';

    while($rowq = DB_fetch_array($ResultIndex)){
        echo '<option value="'.$rowq['code'].'" '.($rowq['code']==$_POST['deductcode']?' selected="selected"':'').'>'.$rowq['description'].'</option>' ;
    }

    echo "</select>";
}


function getloanlist(){
     global $db;

    $sql="select * from prlproducts where hastable='0' and [deduction]='false' and ([pens_tax]=0 or [pens_tax]=2)";
    $ResultIndex = DB_query($sql,$db);
    echo '<select name="deductcode" style="width:100px" required="true">';

    while($rowq = DB_fetch_array($ResultIndex)){
        echo '<option value="'.$rowq['code'].'" '.($rowq['code']==$_POST['deductcode']?' selected="selected"':'').'>'.$rowq['description'].'</option>' ;
    }

    echo "</select>";
}


function filterproduct($deductcode){
     global $db;

    $sql="select * from prlproducts where hastable='0' and [deduction]='false' ";
    $ResultIndex = DB_query($sql,$db);
    echo '<select name="deductcode" style="width:100px" required="true">';
    while($rowq = DB_fetch_array($ResultIndex)){
        echo '<option value="'.$rowq['code'].'" '.(trim($rowq['code'])==trim($deductcode)?' selected="selected"':'').'>'.$rowq['description'].'</option>' ;
    }

    echo "</select>";
}


FUNCTION GETPERMAEMPLOYEES(){
    global $db;

   $sql = "SELECT
        pm.pf_no,
        pm.status,
        pm.fname,
        pm.mname,
        pm.lname,
        pm.idno,
        pm.pin_no,
        pm.nssf_no,
        pm.nhif_no,
        pm.telno,
        pm.email,
        lc.name AS branch,
        SUM(lp.days) AS days
      FROM prlemployeemaster pm
      LEFT JOIN prldepartments lc ON pm.department = lc.code
      LEFT JOIN prlstaffleaveplanner lp ON pm.pf_no = lp.pfno AND lp.status = 1
      WHERE pm.freqcode = 1 AND ((dateterminated > NOW()) OR (dateterminated IS NULL) OR dateterminated = '0000-00-00 00:00:00')
      GROUP BY
        pm.pf_no,
        pm.status,
        pm.fname,
        pm.mname,
        pm.lname,
        pm.idno,
        pm.pin_no,
        pm.nssf_no,
        pm.nhif_no,
        pm.telno,
        pm.email,
        lc.name";

        $ResultIndex = DB_query($sql,$db);
        return $ResultIndex ;
}


FUNCTION GETEMPLOYEEBybranch(){
global $db;
$pfnos=$_POST['pfno'];
$option='<option>No selection</option>';

  $sql = "SELECT pm.pf_no,
          pm.status,
          pm.fname,
          pm.mname,
          pm.lname,
          pm.idno,
          pm.pin_no,
          pm.nssf_no,
          pm.nhif_no,
          pm.telno,
          pm.email,
          pm.freqcode,
          pm.basicpay,
          pm.noofhrsperday,
          lc.name AS branch,
          pm.position,
          pm.salaryscale,
          pm.salaryscale2
      FROM prlemployeemaster pm
      LEFT JOIN prlestablishment lc ON pm.branch = lc.code
      WHERE (pm.freqcode > 0) 
        AND (pm.pf_no != '".$pfnos."') 
        AND ((dateterminated > NOW()) OR (dateterminated IS NULL) OR dateterminated = '0000-00-00 00:00:00')
        AND lc.code IN (SELECT branch FROM prlemployeemaster WHERE pf_no = '".$pfnos."')";
        $ResultIndex = DB_query($sql,$db);
        while($row=DB_fetch_array($ResultIndex)){
            $option .="<option value='".$row['pf_no']."'>".$row['status'].' '.$row['fname'].' '.$row['mname'].' '.$row['lname']."</option>";
        }
        return $option;
}

function getdepartments(){
    global $db;

    $SQL="select * from prldepartments ";
    $results = DB_query($SQL,$db);
    return $results;

}

function GetEstablishment(){
    global $db;

    $SQL="select * from prlestablishment ";
    $results = DB_query($SQL,$db);
    return $results;

}


function drillchildss($code){
    global $db;

    $SQL="SELECT 
       [code]
      ,[qualifications]
      ,[min]
      ,[annual_inc]
      ,[max]
      ,[lastupdate_year]
      ,[lastupdate]
    FROM [prlsalaryscale] where code='".$code."'";
    $results = DB_query($SQL,$db);
    return DB_fetch_array($results);
}


Function drilljg($jbgroup){
    global $db;

    $SQL="SELECT 
       [name]
      ,[qualification]
      ,[rowid]
    FROM [prljobgroup]
    where name='".$code."'";
    $results = DB_query($SQL,$db);
    return DB_fetch_array($results);
}



?>
