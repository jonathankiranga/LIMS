<?php
/* $Id: AuditTrail.php 6310 2013-08-29 10:42:50Z daintree $ */
include('includes/session.inc');
$Title = _('Audit new employees');
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

	// Find the query type (insert/update/delete)
	function Query_Type($SQLString) {
 		$SQLArray = explode(" ",$SQLString);
       		return $SQLArray[0];
        }

	function InsertQueryInfo($SQLString) {
		$SQLArray = explode('(', $SQLString);
		$_SESSION['SQLString']['table'] = $SQLArray[0];
		$SQLString = str_replace(')','',$SQLString);
		$SQLString = str_replace('(','',$SQLString);
		$SQLString = str_replace($_SESSION['SQLString']['table'],'',$SQLString);
		$SQLArray = explode('VALUES', $SQLString);
		$fieldnamearray = explode(',', $SQLArray[0]);
		$_SESSION['SQLString']['fields'] = $fieldnamearray;
		if (isset($SQLArray[1])) {
			$FieldValueArray = preg_split("/[[:space:]]*('[^']*'|[[:digit:].]+),/", $SQLArray[1], 0, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
			$_SESSION['SQLString']['values'] = $FieldValueArray;
		}
	}

        function Series($seed=null){
                    if($seed==null){
                       return  $_SESSION['SQLString']['noseries'] =$_SESSION['SQLString']['noseries']+2;
                     }else{
                       return  $_SESSION['SQLString']['noseries'] = $seed;
                    }
                }
        
	function UpdateQueryInfo($SQLString) {
		$SQLArray = explode('SET', $SQLString);
                $_SESSION['SQLString']['table'] = $SQLArray[0];
		$SQLString = str_replace($_SESSION['SQLString']['table'],'',$SQLString);
      		$SQLString = str_replace('SET',' ',$SQLString);
     		$SQLString = str_replace('WHERE',',',$SQLString);
		$SQLString = str_replace('AND',',',$SQLString);
                $SQLString = str_replace(',','=',$SQLString);
		$FieldArray = preg_split("/[[:space:]]*([[:alnum:].]+[[:space:]]*=[[:space:]]*(?:'[^']*'|[[:digit:].]+))[[:space:]]*,/", $SQLString, 0, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		
                                
                for ($i=0; $i<sizeof($FieldArray); $i++) {
			$Assigment = explode('=',$FieldArray[$i]);
                        $s=Series(0); 
                        
                        for($r=0; $r<sizeof($Assigment); $r++){
               	           $_SESSION['SQLString']['fields'][$r] = TRIM($Assigment[$s]);
                           $_SESSION['SQLString']['values'][$r] = TRIM($Assigment[$s+1]);
                           $s=Series();
                        }
               
                }
                                                 
	}

	function DeleteQueryInfo($SQLString) {
		$SQLArray = explode("WHERE", $SQLString);
		$_SESSION['SQLString']['table'] = $SQLArray[0];
		$SQLString = trim(str_replace($SQLArray[0], '', $SQLString));
		$SQLString = trim(str_replace("DELETE", '', $SQLString));
		$SQLString = trim(str_replace("FROM", '', $SQLString));
		$SQLString = trim(str_replace("WHERE", '', $SQLString));
		$Assigment = explode('=', $SQLString);
		$_SESSION['SQLString']['fields'][0] = $Assigment[0];
		$_SESSION['SQLString']['values'][0] = $Assigment[1];
	}
               
        
        function clean($value){  
           return  htmlspecialchars_decode($value) ;
        }

	if (mb_strlen($ContainingText) > 0) {
	    $ContainingText = " AND querystring LIKE '%" . $ContainingText . "%' ";
        } else {
	    $ContainingText = "";
	}

            $ContainingText .= " AND querystring LIKE '%prlemployeemaster%' ";
            $ContainingText .= " AND querystring LIKE '%insert%' ";
	
        
	if ($_POST['SelectedUser'] == 'ALL') {
		$sql="SELECT transactiondate,
                        userid,
                        querystring
			FROM audittrail
			WHERE transactiondate BETWEEN '". $FromDate."' AND '".$ToDate."'" . $ContainingText;
	} else {
		$sql="SELECT transactiondate,
                            userid,
                            querystring
			FROM audittrail
			WHERE userid='".$_POST['SelectedUser']."'
			AND transactiondate BETWEEN '".$FromDate."' AND '".$ToDate."'" . $ContainingText;
	}
        $result = DB_query($sql,$db);
                
        $PaperSize = 'A4';
        include('includes/PDFStarter.php');
        $pdf->addInfo('Title', _('Audit Basic Pay') );
        $pdf->addInfo('Subject',$Title);
        $FontSize=10;
        $line_height=15;
        include('includes/PDFaudittrail.inc');

        while ($myrow = DB_fetch_row($result)) {
            
                        if (Query_Type($myrow[2]) == "INSERT") {
                                InsertQueryInfo(str_replace("INSERT INTO",'',$myrow[2]));
                                $RowColour = '#a8ff90';
                        }
                        if (Query_Type($myrow[2]) == "UPDATE") {
                                UpdateQueryInfo(str_replace("UPDATE",'',$myrow[2]));
                                $RowColour = '#feff90';
                        }
                        if (Query_Type($myrow[2]) == "DELETE") {
                                DeleteQueryInfo(str_replace("DELETE FROM",'',$myrow[2]));
                                $RowColour = '#fe90bf';
                        }
                 
			if (!isset($_SESSION['SQLString']['values'])) {
			    $_SESSION['SQLString']['values'][0]='';
			}
                        
                        
                        $Xpos = $Left_Margin + 1;
                        $LeftOvers = $pdf->addText($Xpos,$YPos,$FontSize,ConvertSQLDateTime($myrow[0]));
                        $LeftOvers = $pdf->addText(150,$YPos,$FontSize,$myrow[1]);
                        $LeftOvers = $pdf->addText(200,$YPos,$FontSize,Query_Type($myrow[2]));
                        
                         for ($i=0; $i<sizeof($_SESSION['SQLString']['fields']); $i++) {
                             
                            if (isset($_SESSION['SQLString']['values'][$i]) 
                                    and (trim(str_replace("'","",$_SESSION['SQLString']['values'][$i])) != "") &
                            (trim($_SESSION['SQLString']['fields'][$i]) != 'password') &
                            (trim($_SESSION['SQLString']['fields'][$i]) != 'www_users.password')) {

                                $LeftOvers = $pdf->addText(250,$YPos,$FontSize,($_SESSION['SQLString']['fields'][$i]));
                                $LeftOvers = $pdf->addText(400,$YPos,$FontSize,clean(trim(str_replace("'","",$_SESSION['SQLString']['values'][$i]))));
                                $YPos-= $line_height;
	 
                            }
                             
			}
                       //  var_dump($_SESSION['SQLString']);
                  /*increment a line down for the next line item */
           
                $YPos-= $line_height;
                if ($YPos - $line_height <= $Bottom_Margin) {
                /* We reached the end of the page so finish off the page and start a newy */
                    $PageNumber++;
                    include('includes/PDFaudittrail.inc');
                    $FontSize = 10;
		} //end if need a new page headed up
           
	          
			 
		unset($_SESSION['SQLString']);
             
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
// Show user selections
echo '<tr><td>' .  _('User ID'). '</td>
	<td><select tabindex="3" name="SelectedUser">
	<option value="ALL">' . _('All') . '</option>';

while ($Users = DB_fetch_row($UserResult)) {
    if (isset($_POST['SelectedUser']) and $users[0]==$_POST['SelectedUser']) {
            echo '<option selected="selected" value="' . $Users[0] . '">' . $Users[0] . '</option>';
    } else {
            echo '<option value="' . $Users[0] . '">' . $Users[0] . '</option>';
    }
}

echo '</select></td></tr>';


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