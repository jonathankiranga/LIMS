<?php
echo '<div id="FooterDiv">';
echo '<div id="FooterWrapDiv">';

echo '<div id="FooterLogoDiv">';
	echo '<img src="'. $RootPath . '/' . $_SESSION['LogoFile'] . '" width="50" alt="ERP" title="SMARTERP ' . _('Copyright') . ' &copy; ' . date('Y') . '" />';
echo '</div>';
echo '<div id="FooterVersionDiv" class="nav-header">';
	echo 'SMARTERNOW ERP ' . _('Copyright') . ' © 2012 - ' . Date('Y');
  if($_SESSION['ManualNumber']==1){
            echo '<code>Your settings for auto number have been disabled by the Admin.You are responsible for the number generation</code>';
         }      
echo '</div>';
echo '<div id="FooterTimeDiv" class="nav-header">';
	echo DisplayDateTime();
echo '</div>';
echo '</div>'; // FooterWrapDiv
echo '</div>'; // FooterDiv
echo '</body>';
echo '</html>';
?>