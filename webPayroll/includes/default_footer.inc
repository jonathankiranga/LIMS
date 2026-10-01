<?php

echo '<div id="FooterDiv">';
echo '<div id="FooterWrapDiv">';

echo '<div id="FooterLogoDiv">';
	echo '<img src="'. $RootPath . '/' . $_SESSION['LogoFile'] . '" width="50" alt="payroll" title="Human Resource ' . _('Copyright') . ' &copy; ' . date('Y') . '" />';
echo '</div>';

echo '<div id="FooterVersionDiv" class="nav-header">';
	echo 'Human Resource management and Payroll ( with DynamicsNav Intergration ' ._('version') .' 2017 ) ' . _('Copyright') . ' © 2004 - ' . Date('Y');
echo '</div>';

echo '<div id="FooterTimeDiv" class="nav-header">';
	echo DisplayDateTime();
echo '</div>';

echo '</div>'; // FooterWrapDiv
echo '</div>'; // FooterDiv

echo '</body>';
echo '</html>';
?>