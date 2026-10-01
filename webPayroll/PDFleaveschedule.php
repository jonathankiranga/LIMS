<?php
include('includes/session.inc');
$self = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$Title = _('Employee leave schedule');


if(isset($_POST['printpdf'])){


    $PaperSize = 'A4';
    include('includes/PDFStarter.php');
    $pdf->addInfo('Title', _('Leave Schedule') );
    $pdf->addInfo('Subject',$Title);
    $FontSize=10;
    $line_height=15;
    include('includes/PDFleaveheader.inc');

     $sql="select p.* ,
     e.[status]+' '+ e.[fname]+' '+e.[mname]+'  '+e.[lname] as sub ,
     s.[status]+' '+ s.[fname]+' '+s.[mname]+'  '+s.[lname] as appliedby,
     s.email,  g1.name as sub1,   h1.name as appliedby2
     from [prlstaffleaveplanner] p
     join prlemployeemaster e on p.[handover]= e.[pf_no]
     join prlemployeemaster s on p.[pfno]= s.[pf_no]
     LEFT OUTER JOIN  prlgeneralitems g1  on  g1.code=e.property1
     LEFT OUTER JOIN  prlgeneralitems h1  on  h1.code=s.property1
     where leavedue >='".Date('Y-m-d')."'";

        $results=DB_query($sql,$db);
        while($rows=  DB_fetch_array($results)){
                $AllNames  = ucfirst($rows['appliedby']);
                $LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos,470,$FontSize,$AllNames,'left');
		$LeftOvers = $pdf->addTextWrap(380,$YPos,60,$FontSize,ConvertSQLDate($rows['leavedue']),'right');
		$LeftOvers = $pdf->addTextWrap(480,$YPos,60,$FontSize,ConvertSQLDate($rows['leavend']),'right');

        if ($YPos - $line_height <= $Bottom_Margin) {
                /* We reached the end of the page so finish off the page and start a newy */
                    $PageNumber++;
                    include('includes/PDFleaveheader.inc');
                    $FontSize = 10;
		} //end if need a new page headed up
            /*increment a line down for the next line item */
            $YPos-= $line_height;
	}

	$pdf->OutputD($_SESSION['DatabaseName'].' '.$Title.' '.date('Y-m-d').'.pdf');
	$pdf->__destruct();

} else {

include('includes/header.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';
echo '<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<div class="table-bordered"><input type="submit" name="printpdf" value="Print Leave Schedule"/></div></form>';

include('includes/footer.inc');
}
?>
