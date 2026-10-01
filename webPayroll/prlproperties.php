<?php
include('includes/session.inc');
$Title = "Human Resource Description Maintenace";
include('includes/header.inc');
include('ExtFunc/employeetypes.inc');
$my_page = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<link rel="stylesheet" href="css/smartpayroll.css">';
echo '<div class="sp-page">';
echo '<div class="sp-header">';
echo '<div class="sp-header-icon"><i class="fas fa-cogs"></i></div>';
echo '<div class="sp-header-title">';
echo '<h1>' . $Title . '</h1>';
echo '<p>HR description maintenance</p>';
echo '</div>';
echo '</div>';
echo '<div class="sp-content">';

if(isset($_POST['submittypename'])){
   createproprty();
}

if(isset($_POST['submitdname'])){
    createoptions();
}

if(isset($_GET['P'])){

    echo drillcustom($_GET['P']);

} elseif(isset($_POST['newline'])){

    echo '<form action="'.$my_page.'" method="POST">
    <input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>
    <input type="hidden" name="type" value="'. $_POST['typeP'] .'"/><div>';

    echo '<table class="table-bordered">
        <tr><td>Property name</td><td>
         <input type="text" name="dname" required="true" maxwidth="50" size="50"></td></tr></table>
         <input type="submit" name="submitdname" value="Create" />';
    echo '</div></form>';

    

} elseif(isset($_POST['newproperty'])){

    echo '<form action="'.$my_page.'" method="POST">
        <input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>
        <div>';

    echo '<table class="table-bordered">
        <tr><td>Property Name</td>
        <td><input type="text" name="typename" required="true" maxwidth="50" size="50"></td></tr>
        <tr><td>Is this a Payroll item ?</td>
        <td><select name="setup">
        <option value="1">Yes</option>
        <option value="0" selected="selected">NO</option>
        </select></td></tr>
        <tr><td colspan="2"><center><i>Only 6 items can be created as  payroll properties.<br/>Once an item has been used as a
         a payroll property, it <b><u>can not</u></b> be changed. </i></center></td></tr></table>
        <input type="submit" name="submittypename" value="Create" />';
    echo '</div></form>';

    unset($_POST['newproperty']);

} elseif(isset($_GET['Editcode'])) {

    echo editproperty();


} elseif(isset($_GET['editlinecode'])) {

    echo editlineproperty();


} elseif(isset($_POST['editproperty'])) {

    if($_POST['setup'] == $_POST['org']){

        $sql="Update prltypes set type='".$_POST['proprtyname']."' where code='".$_POST['code']."'";
        DB_query($sql,$db);
            $Msg="You have edited the record successfully.";
            prnMsg($Msg,'info');

    } else {
        $sqlchild="select code from prlgeneralitems where type=".$_POST['code'];
        $sql="select * from prlemployeemaster
            where [property1] in (".$sqlchild.") or
            [property2] in (".$sqlchild.") or
            [property3] in (".$sqlchild.") or
            [property4] in (".$sqlchild.") or
            [property5] in (".$sqlchild.") or
            [property6] in (".$sqlchild.") or
            [property7] in (".$sqlchild.") or
            [property8] in (".$sqlchild.") or
            [property9] in (".$sqlchild.") or
            [property10] in (".$sqlchild.")";
        $ResultIndex = DB_query($sql,$db);
        if(DB_num_rows($ResultIndex)==1){
            $Msg="You can not <b>Change</b> this property to payroll setup item because it is in use.";
            prnMsg($Msg,'warn');
        } else {
            $sql="Update prltypes set
                  type='". $_POST['proprtyname']."',
                  setup='". $_POST['setup']."'
                  where code='".$_POST['code']."'";
             DB_query($sql,$db);
             $Msg="You have edited the record successfully.";
            prnMsg($Msg,'info');
        }
    }


    unset($_POST['editproperty']);
    echo setupcustom();

} elseif(isset($_POST['editlineproperty'])){

        $sql="Update prlgeneralitems set name='".$_POST['proprtyname']."' where code='".$_POST['code']."'";
        DB_query($sql,$db);
        $Msg="You have edited the record successfully.";
        prnMsg($Msg,'info');

        $_GET['P']=$_POST['code'];

        echo drillcustom($_GET['P']);
        unset($_POST['editlineproperty']);
} else {

    echo setupcustom();

}


echo '</div>';
echo '</div>';
include('includes/footer.inc');

?>
