<?php

include('includes/session.inc');
$Title = "Close Payroll"  ;
include('includes/header.inc') ;
include('ExtFunc/gensalary.inc') ;

$self = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title. '<br /></p>';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

if(isset($_POST['createpriod'])){
    createpayroll();
    closepayroll();
    unset($_POST['createpriod']);
}

echo '<div class="centre"><div>'
        . '<code><p>After The payroll has been printed and <br/>you want to move to the next month click "Roll Over Payroll" You cannot undo this action</p></code><input type="submit" name="createpriod" value="Roll Over Payroll" onclick="return confirm(\''._('Are you sure you wish to create a new period ?').'\');"/><br/><code>Every time you want to print the payslips click "Calculate Payroll" first.The system calculates and updates the postings</code>
          </div>';
echo '</table></div>';

echo '</form>';

include('includes/footer.inc');