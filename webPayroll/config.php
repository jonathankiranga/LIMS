<?php
// User configurable variables
//---------------------------------------------------
//DefaultLanguage to use for the login screen and the setup of new users.
$DefaultLanguage = 'en_GB.utf8';
// Whether to display the demo login and password or not on the login screen
$AllowDemoMode = FALSE;
// Connection information for the database
// $host is the computer ip address or name where the database is located
// assuming that the webserver is also the sql server
$host = 'localhost';
//`mozillaerp v2`
//assuming that the web server is also the sql server
$DBUser = 'root';
$DBPassword = 'mysqlpassword';
// The timezone of the business - this allows the possibility of having;
date_default_timezone_set('Africa/Nairobi');
putenv('TZ=Africa/Nairobi');
$AllowCompanySelectionBox = 'ShowSelectionBox';

//The system administrator name use the user input mail;
$SysAdminEmail   = 'jonathankiranga@gmail.com';
$DefaultDatabase = 'webpayroll';
$SessionLifeTime = 3600;
$MaximumExecutionTime = 0;
$CryptFunction   = 'sha1';
$DefaultClock    = 12;

$RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'));
if (isset($DirectoryLevelsDeep)){
   for ($i=0;$i<$DirectoryLevelsDeep;$i++){
            $RootPath = mb_substr($RootPath,0, strrpos($RootPath,'/'));
	}
}
if ($RootPath == '/' OR $RootPath == '\\') {
    $RootPath = '';
}

//Installed companies
$CompanyList[] = array('database'=>'webpayroll' ,'company'=>'Demo Company');


//End Installed companies-do not change this line
/* Make sure there is nothing - not even spaces after this last ?> */
?>