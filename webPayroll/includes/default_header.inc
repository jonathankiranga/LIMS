<?php
	if (!isset($RootPath)){
		$RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
		if ($RootPath == '/' OR $RootPath == "\\") {
			$RootPath = '';
		}
	}
        
	$ViewTopic = isset($ViewTopic)?'?ViewTopic=' . $ViewTopic : '';
	$BookMark  = isset($BookMark)? '#' . $BookMark : '';
        $Title = "Human Resource Management and Payroll";
       
	echo '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>' . $Title . '</title>';
	echo '<meta charset="utf-8"/>
              <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
              <meta name="viewport" content="width=device-width,initial-scale=1"/>';
       echo  '<link rel="shortcut icon" href="'.$RootPath.'/comerp.ico" />';
       echo  '<link rel="icon" href="'.$RootPath.'/comerp.ico" />';
       echo  '<link href="'.$RootPath.'/css/homepage.css" rel="stylesheet" type="text/css"/>
       <link href="'.$RootPath.'/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
       <link href="'.$RootPath.'/Javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" />';
       
      echo '<script class="cssdeck" type="text/javascript" src="'.$RootPath.'/Javascripts/1.9.1/jquery.min.js"></script>
            <script class="cssdeck" type="text/javascript" src="'.$RootPath.'/Javascripts/2.3.1/bootstrap.min.js"></script>'; 
        echo '</head>';
	echo '<body>';
	echo '<input type="hidden" name="Lang" id="Lang" value="'.$Lang.'" />'
                . '<script type="text/javascript" src="'.$RootPath.'/Javascripts/menu/dmenu.js"></script>
             <script type="text/javascript" src="'.$RootPath.'/Javascripts/menu/dmenu_key.js"></script>
          <script type="text/javascript" src="'.$RootPath.'/Javascripts/menu/data-clear-2.js"></script>';


        echo '<div id="CanvasDiv">';
	echo '<div id="HeaderDiv">';
	echo '<div id="HeaderWrapDiv">';

	if (isset($Title)) {
		echo '<div id="AppInfoDiv">'; //===HJ===
			echo '<div id="AppInfoCompanyDiv">';
				echo '<img src="'.$RootPath.'/css/xenos/images/company.png" title="'._('Company').'" alt="'._('Company').'"/>' . stripslashes( $_SESSION['CompanyRecord']['coyname']);
			echo '</div>';
			echo '<div id="AppInfoUserDiv">';
				echo '<a target="mainContentIFrame"  href="'. $RootPath.'/UserSettings.php"><img src="'.$RootPath.'/css/xenos/images/user.png" title="User" alt="'._('User').'"/>' . stripslashes($_SESSION['UsersRealName']) . '</a>';
			echo '</div>';
		echo '</div>'; // AppInfoDiv
		echo '<div id="QuickMenuDiv" class="nav-header"><ul>';
		if (count($_SESSION['AllowedPageSecurityTokens'])>1){
			echo '<li><a href="'.$RootPath.'/index.php">' . _('Home Page') . '</a></li>';
			echo '<li><a target="mainContentIFrame"  href="'.$RootPath.'/SelectEstablishment.php">' ._('Company Structure')  . '</a></li>';
			$DefaultManualLink = '<li><a rel="external" accesskey="8" href="' .  $RootPath . '/doc/Manual/ManualContents.php'. $ViewTopic . $BookMark. '">' . _('Manual') . '</a></li>';
			if (mb_substr($_SESSION['Language'],0,2) != 'en'){
				if (file_exists('locale/'.$_SESSION['Language'].'/Manual/ManualContents.php')){
					echo '<li><a target="_blank" href="'.$RootPath.'/locale/'.$_SESSION['Language'].'/Manual/ManualContents.php'. $ViewTopic . $BookMark. '">' . _('Manual') . '</a></li>';
				} else {
					echo $DefaultManualLink;
				}
			} else {
                            echo $DefaultManualLink;
			}
		}

		echo '<li><a href="'.$RootPath.'/Logout.php" onclick="return confirm(\''._('Are you sure you wish to logout?').'\');">' . _('Log-Out $ Backup') . '</a></li>';

		echo '</ul></div>'; // QuickMenuDiv
	}
	echo '</div>'; // HeaderWrapDiv
	echo '</div>'; // Headerdiv
        

?>
