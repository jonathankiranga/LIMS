<?php
include('includes/session.inc');
$self  = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$Title = _('Custom Human Resource Reports');
if(isset($_POST['printreport'])){
//property1

    $Xsql=sprintf("SELECT  pm.[pf_no] , pm.[status] , pm.[fname] , pm.[mname] , pm.[lname] , pm.[idno],
       pm.[pin_no] , pm.[nssf_no] , pm.[nhif_no], pm.[telno] , pm.[email] , pm.[freqcode] ,
       lc.[name] as branch , pm.position , pm.salaryscale ,gi.name as customs
       FROM [prlemployeemaster] pm
       join [prlestablishment] lc on  pm.[branch]=lc.code
       LEFT OUTER JOIN  prlgeneralitems gi  on  gi.code = pm.[property%s]
       ",$_POST['category']);

    $sql= "select * from prltypes where code='".$_POST['category']."'" ;
    $ResultIndex = DB_query($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $properties[] = $row;
    }

    $PaperSize = 'A4';
    include('includes/PDFStarter.php');
    $pdf->addInfo('Title', _('Custom Human Resource Reports'));
    $pdf->addInfo('Subject',$Title);
    $FontSize=10;
    $line_height=15;
    include('includes/PDFCustomReportsHeader.inc');


    $results=DB_query($Xsql,$db);
    while($rows=DB_fetch_array($results)){
            $AllNames  = ucfirst($rows['fname']).' '.ucfirst($rows['mname']).' '.ucfirst($rows['lname']);
            $LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos,40,$FontSize,$rows['pf_no'],'left');
            $LeftOvers = $pdf->addTextWrap($Left_Margin+50,$YPos,100,$FontSize,$AllNames,'left');
            $LeftOvers = $pdf->addTextWrap(280,$YPos,60,$FontSize,$rows['branch'],'left');
            $LeftOvers = $pdf->addTextWrap(480,$YPos,60,$FontSize,$rows['customs'],'left');

    if ($YPos - $line_height <= $Bottom_Margin) {
            /* We reached the end of the page so finish off the page and start a newy */
                $PageNumber++;
                include('includes/PDFCustomReportsHeader.inc');
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
echo '<form method="post" action="'. $self . '"><div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/><table class="table-bordered"><tr>
    <td>Select a Reporting Category</td><td><select name="category" style="width:100px">';

$customheader = getcustom();
foreach ($customheader as $key => $value) {
    echo '<option value="'.$key.'">'.$value.'</option>';
}

echo '</select></td></tr></table><div><input type="submit" name="printreport" value="Print Report"/></div></div></form>';

include('includes/footer.inc');
}

?>
