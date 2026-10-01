<?php
/* $Id: FixedAssetDepreciation.php 4213 2010-12-22 14:33:20Z tim_schofield $*/
include('includes/session.inc');
$Title = _('Depreciation Journal Entry');

$ViewTopic = 'FixedAssets';
$BookMark = 'AssetDepreciation';
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/AccountBalance.inc');

/*Get the last period depreciation (depn is transtype =44) was posted for */
$result = DB_query("SELECT `last_depreciation` FROM companies where `coycode`=1",$db);
$LastDepnRun = DB_fetch_row($result);
$AllowUserEnteredProcessDate = true;

if (!is_date(ConvertSQLDate($LastDepnRun[0]))) { //then depn has never been run yet?
        $_POST['ProcessDate'] = Date($_SESSION['DefaultDateFormat'],mktime(0,0,0,date('m'),0,date('Y')));
} else {
        $_POST['ProcessDate'] = ConvertSQLDate($LastDepnRun[0]);
//depn calc has been run previously
	$AllowUserEnteredProcessDate = false;
}

/* Get list of assets for journal */

$AssetsResult = DB_FixedAssets();
$InputError = false; //always hope for the best
if (Date1GreaterThanDate2($_POST['ProcessDate'],Date($_SESSION['DefaultDateFormat']))){
	prnMsg(_('No depreciation will be committed as the processing date is beyond the current date. The depreciation run can only be run for periods prior to today'),'warn');
	$InputError =true;
}
if (isset($_POST['CommitDepreciation']) AND $InputError==false){
	$result = DB_Txn_Begin($db);
	$TransNo = GetNextTransNo(44, $db);
	$PeriodNo = GetPeriod($_POST['ProcessDate'],$db,TRUE);
        $SQLArray=array();
}

// --- Build asset rows array for Tabulator ---
$assetRows = array();
$TotalCost = 0;
$TotalAccumDepn = 0;
$TotalDepn = 0;
$AssetCategoryDescription = '0';
$TotalCategoryCost = 0;
$TotalCategoryAccumDepn = 0;
$TotalCategoryDepn = 0;

while ($AssetRow = DB_fetch_array($AssetsResult)) {

	if ($AssetCategoryDescription != $AssetRow['categorydescription'] AND $AssetCategoryDescription != '0' ){
		$TotalCategoryCost = 0;
		$TotalCategoryAccumDepn = 0;
		$TotalCategoryDepn = 0;
	}
	if ($AssetCategoryDescription == '0'){
		$AssetCategoryDescription = $AssetRow['categorydescription'];
	}

	$BookValueBfwd = $AssetRow['costtotal'] - $AssetRow['depnbfwd'];
	if ($AssetRow['depntype']==0){ //straight line depreciation
		$DepreciationType = _('SL');
		$NewDepreciation = $AssetRow['costtotal'] * $AssetRow['depnrate']/100/12;
		if ($NewDepreciation > $BookValueBfwd){
			$NewDepreciation = $BookValueBfwd;
		}
	} else { //Diminishing value depreciation
		$DepreciationType = _('DV');
		$NewDepreciation = $BookValueBfwd * $AssetRow['depnrate']/100/12;
	}

	if (Date1GreaterThanDate2(ConvertSQLDate($AssetRow['transdate']),$_POST['ProcessDate'])){
		/*Over-ride calculations as the asset was not purchased at the date of the calculation!! */
		$NewDepreciation = 0;
	}

	$newdepn = round($NewDepreciation, 2);

	$assetRows[] = array(
		'assetid' => $AssetRow['assetid'],
		'description' => $AssetRow['description'],
		'transdate' => ConvertSQLDate($AssetRow['transdate']),
		'costtotal' => floatval($AssetRow['costtotal']),
		'depnbfwd' => floatval($AssetRow['depnbfwd']),
		'bookvalue' => round($BookValueBfwd, 2),
		'depntype_label' => $DepreciationType,
		'depnrate' => $AssetRow['depnrate'],
		'newdepn' => $newdepn,
		'categorydescription' => $AssetRow['categorydescription'],
	);

	$TotalCategoryCost += $AssetRow['costtotal'];
	$TotalCategoryAccumDepn += $AssetRow['depnbfwd'];
	$TotalCategoryDepn += $newdepn;
	$TotalCost += $AssetRow['costtotal'];
	$TotalAccumDepn += $AssetRow['depnbfwd'];
	$TotalDepn += $newdepn;

	if (isset($_POST['CommitDepreciation']) AND $NewDepreciation != 0 AND $InputError==false){

		$SQL = sprintf("select `depnact`,`accumdepnact` from `fixedassetcategories` where `categoryid`='%s'",$AssetRow['assetcategoryid']);
		$ResultIndex = DB_query($SQL,$db);
		$Row=DB_fetch_row($ResultIndex);

		$SQLArray[]= Sprintf("INSERT INTO `Generalledger` (`journalno`,`Docdate`,`period`,`DocumentNo`,`DocumentType`,`accountcode`,`balaccountcode`,`amount`,`currencycode`,`ExchangeRate`,`narration`,`asset_id`) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s',%s)",
			$TransNo , FormatDateForSQL($_POST['ProcessDate']) ,$PeriodNo ,$TransNo ,44 , $Row[0], $Row[1] , $NewDepreciation , $_SESSION['CompanyRecord']['currencydefault'] ,1 , _('Monthly depreciation for ') . ' ' . $AssetRow['categorydescription'] , $AssetRow['assetid'] );
		  //insert the fixedassettrans record
		$SQLArray[] = "INSERT INTO fixedassettrans (assetid, transtype, transno, transdate,periodno, inputdate, fixedassettranstype, amount)
				  VALUES ('" . $AssetRow['assetid'] . "', '44', '" . $TransNo . "',  '" . FormatDateForSQL($_POST['ProcessDate']) . "', '" . $PeriodNo . "', '" . FormatDateForSQL($_POST['ProcessDate']) . "','depn', '" . $NewDepreciation . "')";
		 /*now update the accum depn in fixedassets */
		$SQLArray[] = "UPDATE fixedassets SET  accumdepn = accumdepn + " . $NewDepreciation  . " WHERE assetid = '" . $AssetRow['assetid'] . "'";

	} //end if Committing the depreciation to DB


} //end loop around the assets to calculate depreciation for

// --- Commit processing ---
if (isset($_POST['CommitDepreciation']) AND $InputError==false){

	$SQLArray[] = sprintf("Update companies set `last_depreciation`=DATE_ADD(CAST('%s' AS DATETIME), INTERVAL 1 MONTH)  where `coycode`=1",FormatDateForSQL($_POST['ProcessDate']));

	foreach ($SQLArray as $SQL) {
		$ErrMsg = _('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset accumulated depreciation could not be updated:');
		$DbgMsg = _('The following SQL was used to attempt the update the accumulated depreciation of the asset was:');
		$Result = DB_query($SQL,$db,$ErrMsg, $DbgMsg, true);
	}

	if (DB_error_no($db)==0){
		$result = DB_Txn_Commit($db);
		prnMsg(_('Depreciation') . ' ' . $TransNo . ' ' . _('has been successfully entered'),'success');
	}else{
		DB_Txn_Rollback($db);
	}

	unset($_POST['ProcessDate']);
}

// --- Render Tabulator grid ---
echo '<div class="container" id="depn-table"></div>';
echo '<div style="margin:12px 0;"><button id="exportExcelBtn" class="btn btn-default" style="padding:8px 16px;">' . _('Export to Excel') . '</button></div>';
?>

<script>
var DEPN_CONFIG = <?php echo json_encode([
    'data' => $assetRows,
    'rootPath' => $RootPath,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="<?php echo $RootPath; ?>/javascripts/FixedAssetDepreciation.js"></script>

<?php
if (!isset($_POST['CommitDepreciation']) OR $InputError){
	echo '<form autocomplete="off" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="form">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<br />

		<table class="table-striped table-bordered">
		<tr>';
	if ($AllowUserEnteredProcessDate){
		echo '<td>' . _('Date to Process Depreciation'). ':</td><td><input type="text" class="date" alt="' .$_SESSION['DefaultDateFormat']. '" required="required" name="ProcessDate" maxlength="10" size="11" value="' . $_POST['ProcessDate'] . '" /></td>';
	} else {
		echo '<td>' . _('Date to Process Depreciation'). ':</td><td>' . $_POST['ProcessDate']  . '</td>';
	}
	echo '<td><div class="centre"><input type="submit" name="CommitDepreciation" value="'._('Commit Depreciation').'" /></div></td></tr></table></div></form>';
}
include('includes/footer.inc');
?>
