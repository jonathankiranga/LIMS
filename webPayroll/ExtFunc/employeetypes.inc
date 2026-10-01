<?php

$employment = array();
$employment[0] = 'Select Payroll Type';
$employment[1] = 'Permanent';
$employment[2] = 'Contract';
$employment[3] = 'Attachment';

function createcustom(){
global $db ;
$columcount=0;

    $CUTOMIZEEMPLOYEE = '<tr>';
  
    $sql = "select code,type from prltypes limit 6";
    $results = DB_query($sql,$db);
    $prltypes = array();
    while($row = DB_fetch_array($results)){
        $prltypes[]= $row;
    }
    
    if(empty($prltypes)){
        return '';
    }
    
    foreach ($prltypes as $row) {
        $CUTOMIZEEMPLOYEE .='<td><label>'.$row['type'].'</label></td>';
        $CUTOMIZEEMPLOYEE .='<td><select name="property'.$row['code'].'">';
        $child="select * from prlgeneralitems where type='".$row['code']."' ";
        $genitems = DB_query($child,$db);
        while($gen = DB_fetch_array($genitems)){
            $CUTOMIZEEMPLOYEE .= '<option value="'.$gen['code'].'"  '
                    .($gen['code']== $_POST['property'.$row['code']]?'selected':'').'>'
                    .$gen['name'].'</option>';
        }
        $CUTOMIZEEMPLOYEE .='</select></td>';
        
        $columcount++;
        if($columcount==3){
            $CUTOMIZEEMPLOYEE.'</tr><tr>';
        }
        
    }
       
return  $CUTOMIZEEMPLOYEE.'</tr>';
}

function setupcustom(){
global $db ;
global $my_page;
$f = $my_page ;

$CUTOMIZEEMPLOYEE = '<TABLE class="table-bordered">'
        . '<th>Custom Name</th><th>Is this a Payroll item ?</th>';
$sql = "select * from prltypes";
$results = DB_query($sql,$db);

    while($row = DB_fetch_array($results)){
        $CUTOMIZEEMPLOYEE .='<TR>';
        $CUTOMIZEEMPLOYEE .='<TD><a href="'.$f.'?P='.$row['code'].'">'.$row['type'].'</a></td>';
        $CUTOMIZEEMPLOYEE .='<td>'.($row['setup']==1?'YES':'NO').'</td>';
        $CUTOMIZEEMPLOYEE .='<td><a href="'.$f.'?Editcode='.$row['code'].'">EDIT</a></td></TR>';

    }

    return  $CUTOMIZEEMPLOYEE.'</TABLE><form method="POST" action="'.$f.'">
        <input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>
        <input type="Submit" name="newproperty" value="Create New Property"></form>';
}

function drillcustom($code){
    global $db ;
    global $my_page;
    $f = $my_page ;

    echo '<a href="'.$f.'">Back to Parent</a>';

   $CUTOMIZEEMPLOYEE = '<TABLE class="table-bordered">';
   $sql="select * from prlgeneralitems where type='". $code ."' ";

   $genitems = DB_query($sql,$db);
   while($gen = DB_fetch_array($genitems)){
       $CUTOMIZEEMPLOYEE .= '<tr><td><i>'.$gen['name'].'</i></td>
       <td><a href="'.$f.'?editlinecode='.$gen['code'].'">EDIT</a></td></TR>';
   }

   return  $CUTOMIZEEMPLOYEE.'</TABLE><form method="POST" action="'.$f.'">
        <input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>
        <input type="hidden" name="typeP" value="'. $code .'"/>
        <input type="Submit" name="newline" value="Create New line"></form>';
}

function editproperty(){

    global $my_page;
    global $db;
    $prtypes = array();
    $prtypes['0']='NO' ;
    $prtypes['1']='YES' ;

$CUTOMIZEEMPLOYEE = '<form method="POST" action="'.$my_page.'">
<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>
<input type="hidden" name="code" value="'. $_GET['Editcode'] .'"/>';

$sql = "select `code`,[type],[setup] from prltypes where code='".$_GET['Editcode']."'";
$results = DB_query($sql,$db);
$row = DB_fetch_row($results);

$CUTOMIZEEMPLOYEE .='<fieldset><input type="hidden" name="org" value="'. $row['2'] .'"/>
    <div>Custom Name:<input type="text" name="proprtyname" value="'. $row[1] .'"/></div></fieldset><fieldset>';
$CUTOMIZEEMPLOYEE .='<div>Is this a Payroll item ?:<select name="setup">';

foreach ($prtypes as $key => $value) {
    $CUTOMIZEEMPLOYEE .= '<option value="'.$key.'" '.($key==$row['2']?'selected':'').'>'.$value.'</option>';
 }
    $CUTOMIZEEMPLOYEE .='</select>';

    unset($_GET['Editcode']);

    return  $CUTOMIZEEMPLOYEE .='</div></fieldset><div><input type="Submit" name="editproperty" value="Edit Name"></div></form>';
}

function editlineproperty(){
    global $my_page,$db;

$CUTOMIZEEMPLOYEE = '<form method="POST" action="'.$my_page.'">'
                  . '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>'
                  . '<input type="hidden" name="code" value="'. $_GET['editlinecode'] .'"/>';

$sql = "select `code`,[name] from prlgeneralitems where code='".$_GET['editlinecode']."'";
$results = DB_query($sql,$db);
$row = DB_fetch_row($results);

$CUTOMIZEEMPLOYEE .='<fieldset><div>Custom option item:<input type="text" name="proprtyname" value="'. $row[1] .'"/></div></fieldset><fieldset>';
    return  $CUTOMIZEEMPLOYEE .='</div></fieldset><div><input type="Submit" name="editlineproperty" value="Edit Option"></div></form>';
}

function createproprty(){
    global $db;

    $sql=sprintf("INSERT INTO [prltypes] ([type],[setup],[system]) VALUES ('%s','%s','%s')",$_POST['typename'],$_POST['setup'],0);
    DB_query($sql,$db);
    if(DB_error_no($db)>0){
        echo DB_error_msg($db);
    } else {
         $Msg="You have created the record successfully.";
         prnMsg($Msg,'info');
    }

}

function createoptions(){
    global $db;

    $sql=sprintf("INSERT INTO [prlgeneralitems] ([name],[type]) "
            . " VALUES ('%s','%s')",$_POST['dname'],$_POST['type']);
    DB_query($sql,$db);
    if(DB_error_no($db)>0){
        echo DB_error_msg($db);
    } else {
         $Msg="You have created the record successfully.";
         prnMsg($Msg,'info');
    }

}

?>
