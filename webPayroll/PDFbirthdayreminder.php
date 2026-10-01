<?php
define('any','- ANY -');
define('SELF',htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'));

include('includes/session.inc');
$Title = _('Birthday Reminder');

if(isset($_POST['submitlist'])){
    $PaperSize = 'A4';
    include('includes/PDFStarter.php');
    $pdf->addInfo('Title', _('Birthday Reminder list') );
    $pdf->addInfo('Subject',$Title);
    $FontSize=10;
    $line_height=15;
    include('includes/PDFbirthdayheader.inc');

    if($_POST['branch']==any){
        $sql=sprintf('select * from prlemployeemaster %',($_POST['pfno']==any?'':" where pf_no='".$_POST['pfno']."'"));
    } else {
        $sql="select * from prlemployeemaster  where branch='".$_POST['department']."'";
    }

        $results=DB_query($sql,$db);
        while($rows=  DB_fetch_array($results)){
                $AllNames  = ucfirst($rows['fname']).' '.ucfirst($rows['mname']).' '.ucfirst($rows['lname']);
                $LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos,470,$FontSize,$AllNames,'left');
		$LeftOvers = $pdf->addTextWrap(480,$YPos,60,$FontSize,ConvertSQLDate($rows['dateob']),'right');

        if ($YPos - $line_height <= $Bottom_Margin) {
                /* We reached the end of the page so finish off the page and start a newy */
                    $PageNumber++;
                    include('includes/PDFbirthdayheader.inc');
                    $FontSize = 10;
		} //end if need a new page headed up
            /*increment a line down for the next line item */
            $YPos-= $line_height;
	}

	$pdf->OutputD($_SESSION['DatabaseName'] .$Title. date('Y-m-d').'.pdf');
	$pdf->__destruct();

} else {

include('includes/header.inc');
include('ExtFunc/salary.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';
echo '<form method="post" action="'. SELF .'">';
echo '<div><input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>
      <fieldset>Select one creteria only. <br /> If you select both , the system will pick the latter</fieldset>';
echo '<table class="table-bordered"><tr><th>select birthday for</th></tr>';
echo '<tr><td>Select PF No</td><td><select name="pfno"><option>'. any .'</option>';

$results = GETALLEMPLOYEES();
while($row = DB_fetch_array($results)){
     echo sprintf('<option value="%s">%s</option>', $row['pf_no'],$row['fname'].' '.$row['mname'].' '.$row['lname']);
}

echo '</select></td></tr>';

echo '<tr><td> or Select Branch</td><td><select name="branch"><option>'.any.'</option>';

$results = GetEstablishment();
while($row = DB_fetch_array($results)){
    echo sprintf('<option value="%s">%s</option>', $row['code'],$row['name']);
}
echo '</select></td></tr>';

echo '</table><input type="submit" name="submitlist" value="print"/>';
echo '</div></form>';

include('includes/footer.inc');

}
?>
