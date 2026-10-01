<?php
if (!isset($RootPath)){
    $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
    if ($RootPath == '/' OR $RootPath == "\\") {
        $RootPath = '';
    }
}
        
if($_SESSION['last_activity'] < time() - $_SESSION['expire_time']) { //have we expired?
    //redirect to logout.php
    header("Location:".$RootPath."/Logout.php"); //change yoursite.com to the name of you site!!
} else{ //if we haven't expired:
    $_SESSION['last_activity'] = time(); //this was the moment of last activity.
}

?>