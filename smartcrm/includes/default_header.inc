<?php
	if (!isset($RootPath)){
		$RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
		if ($RootPath == '/' OR $RootPath == "\\") {
			$RootPath = '';
		}
	}
   	$ViewTopic = isset($ViewTopic)?'?ViewTopic=' . $ViewTopic : '';
	$BookMark  = isset($BookMark)? '#' . $BookMark : '';
        $Title = "SmartERP";
 	echo '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>' . $Title . '</title>';
	echo '<meta charset="utf-8"/>
              <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
              <meta name="viewport" content="width=device-width,initial-scale=1"/>';
    
       echo '<link rel="shortcut icon" href="'.$RootPath.'/favicon.ico" />';
       echo '<link rel="icon" href="'.$RootPath.'/favicon.ico" />';
    
      echo '<link rel="stylesheet" href="'.$RootPath.'/css/bootstrap.min.css"  type="text/css"/>
            <link rel="stylesheet" href="'.$RootPath.'/css/bootstrap-responsive.min.css"  type="text/css"/>
            <link rel="stylesheet" href="'.$RootPath.'/css/homepage.css?v='.time().'" type="text/css"/>';
      
      echo '<script type="text/javascript" src="'.$RootPath.'/javascripts/jquery.min.js"></script>
            <script type="text/javascript" src="'.$RootPath.'/javascripts/bootstrap.min.js"></script>'; 
     
        echo '</head>';
	echo '<body>';
	echo '<input type="hidden" name="Lang" id="Lang" value="'.$Lang.'" />
        <script type="text/javascript" src="'.$RootPath.'/javascripts/menu/dmenu.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/menu/dmenu_key.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/menu/data-clear-2.js"></script>';

        echo '<div id="CanvasDiv">';
	echo '<div id="HeaderDiv">';
	echo '<div id="HeaderWrapDiv">';

        if (isset($Title)) {
		echo '<div id="AppInfoDiv">'; 
//===HJ===
			echo '<div id="AppInfoCompanyDiv">';
				echo '<img src="'.$RootPath.'/css/'.$Theme.'/images/company.png" title="'._('Company').'" alt="'._('Company').'"/>' . stripslashes($_SESSION['CompanyRecord']['coyname']);
			echo '</div>';
		echo '<div id="AppInfoUserDiv">';
			echo '<a target="mainContentIFrame"   href="'. $RootPath.'/UserSettings.php"><img src="'.$RootPath.'/css/'.$Theme.'/images/user.png" title="User" alt="'._('User').'"/>' . stripslashes($_SESSION['UsersRealName']) . '</a>';
		  echo '</div>';
			
                      
		echo '</div>'; 
// AppInfoDiv
		echo '<div id="QuickMenuDiv"><ul>';
		
//take off inline formatting, use CSS instead ===HJ===

		if (count($_SESSION['AllowedPageSecurityTokens'])>1){
                     echo '<li><a href="index.php">' . _('Home Page') .'</a></li>';
			echo '<li><a  target="mainContentIFrame"   href="SelectCustomer.php?newsearch=yes">' . _('Customers List') .'</a></li>';
			echo '<li><a  target="mainContentIFrame"   href="SelectSupplier.php?newsearch=yes">' . _('Suppliers List') .'</a></li>';
                        echo '<li><a  target="mainContentIFrame"   href="SelectProduct.php?newsearch=yes">' ._('Inventory Items List') .'</a></li>';
                        echo '<li><a  target="mainContentIFrame"   href="PriceListdisplay.php">' ._('Display Price List ') .'</a></li>';
			
			$DefaultManualLink = '<li><a rel="external" accesskey="8" href="'. $RootPath .'/doc/Manual/SystemManual.html'. $ViewTopic . $BookMark. '">' . _('Manual') . '</a></li>';

			if (substr($_SESSION['Language'],0,2) != 'en'){
				if (file_exists('locale/'.$_SESSION['Language'].'/Manual/ManualContents.php')){
				  echo '<li><a target="_blank" href="'.$RootPath.'/locale/'.$_SESSION['Language'].'/Manual/SystemManual.html'. $ViewTopic . $BookMark. '">' . _('Manual') . '</a></li>';
				} else {
				  echo $DefaultManualLink;
				}
			} else {
                            echo $DefaultManualLink;
			}
		}

		echo '<li><a href="'.$RootPath.'/Logout.php" onclick="return confirm(\''._('Are you sure you wish to logout?').'\');">' . _('Logout') . '</a></li>';

		echo '</ul></div>'; // QuickMenuDiv
	}
        
	echo '</div>'; // HeaderWrapDiv
	echo '</div>'; // Headerdiv
        

?>
