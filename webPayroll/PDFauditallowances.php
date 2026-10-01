<?php
/* $Id: AuditTrail.php 6310 2013-08-29 10:42:50Z daintree $ */
include('includes/session.inc');
$Title = _('Audit Changes on Allowances');
// Get list of users
$UserResult = DB_query("SELECT userid FROM www_users ORDER BY userid",$db);

if(!isset($_POST['ContainingText'])){
    $_POST['ContainingText']='';
}

if (isset($_POST['ContainingText'])){
	$ContainingText = trim(mb_strtoupper($_POST['ContainingText']));
} elseif (isset($_GET['ContainingText'])){
	$ContainingText = trim(mb_strtoupper($_GET['ContainingText']));
}

if (!isset($_POST['FromDate'])){
	$_POST['FromDate'] = Date($_SESSION['DefaultDateFormat'],mktime(0,0,0, Date('m')-$_SESSION['MonthsAuditTrail']));
}
if (!isset($_POST['ToDate'])){
	$_POST['ToDate']= Date($_SESSION['DefaultDateFormat']);
}

if ((!(Is_Date($_POST['FromDate'])) OR (!Is_Date($_POST['ToDate']))) AND (isset($_POST['View']))) {
	prnMsg( _('Incorrect date format used, please re-enter'), error);
	unset($_POST['View']);
}

if (isset($_POST['View'])) {
	$FromDate = str_replace('/','-',FormatDateForSQL($_POST['FromDate']).' 00:00:00');
	$ToDate = str_replace('/','-',FormatDateForSQL($_POST['ToDate']).' 23:59:59');

	
	if (mb_strlen($ContainingText) > 0) {
	    $ContainingText = " AND name LIKE '%" . $ContainingText . "%' ";
        } else {
	    $ContainingText = "";
	}

        
        
		$sql="SELECT [posted]
                            ,[userid]
                            ,[pfno]
                            ,[name]
                            ,[amount]
                            ,[employeramount]
                            ,[deduction]
                            ,[nonrecuring]
                        FROM [auditprlmatrix]
			WHERE [posted] BETWEEN '". $FromDate."' AND '".$ToDate."'" . $ContainingText;
	
        $result = DB_query($sql,$db);
                
        $PaperSize = 'A4';
        include('includes/PDFStarter.php');
        $pdf->addInfo('Title', _('Audit Basic Pay') );
        $pdf->addInfo('Subject',$Title);
        $FontSize=10;
        $line_height=15;
        include('includes/PDFauditproductsheader.inc');

        
        while ($myrow = DB_fetch_row($result)) {
                                                                                    
                $Xpos = $Left_Margin + 1;
                $LeftOvers = $pdf->addText($Xpos,$YPos,$FontSize,ConvertSQLDateTime($myrow[0]));
                $LeftOvers = $pdf->addText(100,$YPos,$FontSize,$myrow[1]);
                $LeftOvers = $pdf->addText(150,$YPos,$FontSize,$myrow[2]);
                $LeftOvers = $pdf->addText(200,$YPos,$FontSize,$myrow[3]);
                $LeftOvers = $pdf->addText(250,$YPos,$FontSize,$myrow[4]);
                $LeftOvers = $pdf->addText(350,$YPos,$FontSize,$myrow[5]);
                $LeftOvers = $pdf->addText(400,$YPos,$FontSize,$myrow[6]==0?'Deduction':'Allowance');
                $LeftOvers = $pdf->addText(450,$YPos,$FontSize,$myrow[7]==0?'Monthly':'Once');

                  /*increment a line down for the next line item */
           
                $YPos-= $line_height;
                if ($YPos - $line_height <= $Bottom_Margin) {
                /* We reached the end of the page so finish off the page and start a newy */
                    $PageNumber++;
                    include('includes/PDFauditproductsheader.inc');
                    $FontSize = 10;
		} //end if need a new page headed up
           
	          
             
	}
	
	$pdf->OutputD($_SESSION['DatabaseName'] .$Title. date('Y-m-d').'.pdf');
	$pdf->__destruct();

} else {

include('includes/header.inc');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table-bordered">';

echo '<tr><td>' .  _('From Date') . ' ' . $_SESSION['DefaultDateFormat']  . '</td>
		<td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="FromDate" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['FromDate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
	</tr>
	<tr><td>' .  _('To Date') . ' ' . $_SESSION['DefaultDateFormat']  . '</td>
		<td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="ToDate" size="11" maxlength="10" required="required" value="' . $_POST['ToDate'] . '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
	</tr>';



// Show the text
echo '<tr><td>' . _('Containing text') . ':</td>
		<td><input type="text" name="ContainingText" size="20" maxlength="20" value="'. $_POST['ContainingText'] . '" /></td>
	</tr>
	</table>
	<br />
	<div class="centre">
		<input tabindex="5" type="submit" name="View" value="' . _('View') . '" />
	</div>
	</div>
	</form>';

// View the audit trail
include('includes/footer.inc');
}
?>