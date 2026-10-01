<?php
include ('includes/session.inc');
include ('includes/AccountBalance.inc');
$Title = _('Fixed Asset Register');

$ViewTopic = 'FixedAssets';
$BookMark = 'AssetRegister';
$csv_output = '';

$isFullPdf = isset($_POST['printfull']);
$isShowReport = isset($_POST['submit']) || isset($_POST['pdf']) || isset($_POST['csv']);
$isCsv = isset($_POST['csv']);

if ($isFullPdf) {
    header('Location: FixedAssetPrint.php');
    exit;
}

if ($isShowReport OR $isCsv) {
	if (empty($_POST['csv'])) {
		include('includes/header.inc');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';
	}
	$DateFrom = FormatDateForSQL($_POST['FromDate']);
	$DateTo = FormatDateForSQL($_POST['ToDate']);
    $sql = "SELECT 
                fixedassets.assetid,
                fixedassets.description,
                fixedassets.longdescription,
                fixedassets.assetcategoryid,
                fixedassets.serialno,
                fixedassetlocations.locationdescription,
                fixedassettrans.transdate as datepurchased,
                fixedassetlocations.parentlocationid,
                fixedassets.assetlocation,
                fixedassets.disposaldate,
                fixedassettrans.fixedassettranstype,
                SUM(CASE WHEN (fixedassettrans.transdate <'" . $DateFrom . "' AND fixedassettrans.fixedassettranstype='cost') THEN fixedassettrans.amount ELSE 0 END) AS costbfwd,
                SUM(CASE WHEN (fixedassettrans.transdate <'" . $DateFrom . "' AND fixedassettrans.fixedassettranstype='depn') THEN fixedassettrans.amount ELSE 0 END) AS depnbfwd,
                SUM(CASE WHEN (fixedassettrans.transdate >='" . $DateFrom ."'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='cost') THEN fixedassettrans.amount ELSE 0 END) AS periodadditions,
                SUM(CASE WHEN fixedassettrans.transdate >='" . $DateFrom . "'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='depn' THEN fixedassettrans.amount ELSE 0 END) AS perioddepn,
                SUM(CASE WHEN fixedassettrans.transdate >='" . $DateFrom . "'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='disposal' THEN fixedassettrans.amount ELSE 0 END) AS perioddisposal,
                SUM(CASE WHEN (fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='cost') THEN fixedassettrans.units ELSE 0 END) AS pcs,
                SUM(CASE WHEN (fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='Hired') THEN fixedassettrans.units ELSE 0 END) AS Hiredout
    FROM fixedassets
    INNER JOIN fixedassetcategories ON fixedassets.assetcategoryid=fixedassetcategories.categoryid
    INNER JOIN fixedassetlocations ON fixedassets.assetlocation=fixedassetlocations.locationid
    INNER JOIN fixedassettrans ON fixedassets.assetid=fixedassettrans.assetid 
    WHERE fixedassets.assetcategoryid " . LIKE . "'" . $_POST['AssetCategory'] . "'
    AND fixedassets.assetid " . LIKE . "'" . $_POST['AssetID'] . "'
    AND fixedassets.assetlocation " . LIKE . "'" . $_POST['AssetLocation'] . "'
    GROUP BY fixedassets.assetid,
                    fixedassets.description,
                    fixedassets.longdescription,
                    fixedassets.assetcategoryid,
                    fixedassets.serialno,
                    fixedassetlocations.locationdescription,
                    fixedassettrans.transdate,
                    fixedassetlocations.parentlocationid,
                    fixedassets.assetlocation,
                    fixedassets.disposaldate,
                    fixedassettrans.fixedassettranstype";
    
	    $result = DB_FixedAssetsRegister();
        
        
	if (isset($_POST['pdf'])) {
		$FontSize = 10;
		$pdf->addInfo('Title', _('Fixed Asset Register'));
		$pdf->addInfo('Subject', _('Fixed Asset Register'));
		$PageNumber = 1;
		$line_height = 12;
		if ($_POST['AssetCategory']=='%') {
			$AssetCategory=_('All');
		} else {
			$CategorySQL="SELECT categorydescription FROM fixedassetcategories WHERE categoryid='".$_POST['AssetCategory']."'";
			$CategoryResult=DB_query($CategorySQL, $db);
			$CategoryRow=DB_fetch_array($CategoryResult);
			$AssetCategory=$CategoryRow['categorydescription'];
		}

		if ($_POST['AssetID']=='%') {
			$AssetDescription =_('All');
		} else {
			$AssetSQL="SELECT description FROM fixedassets WHERE assetid='".$_POST['AssetID']."'";
			$AssetResult=DB_query($AssetSQL, $db);
			$AssetRow=DB_fetch_array($AssetResult);
			$AssetDescription =$AssetRow['description'];
		}
		PDFPageHeader();
	} elseif (isset($_POST['csv'])) {
		$csv_output = "'Asset ID','Description','Serial Number','Location','Date Acquired','Cost B/Fwd','Period Additions','Depn B/Fwd','Period Depreciation','Cost C/Fwd', 'Accum Depn C/Fwd','NBV','Disposal Value','No of Units','Units on Hire'\n";
	} else {
		echo '<p><a class="btn btn-primary" href="FixedAssetPrint.php">' . _('Print Full Asset Register') . '</a></p>';
		echo '<form autocomplete="off"id="RegisterForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?' . SID . '">
              <div>';
        echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<div class="centre">' ._('From') . ':' . $_POST['FromDate'] . ' ' . _('to') . ' ' . $_POST['ToDate'] . '</div>';
		echo '<br />
			<div class="container"><table  class="table table-bordered">
			<tr>
				<th>' . _('Asset ID') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('Serial Number') . '</th>
				<th>' . _('Location') . '</th>
				<th>' . _('Cost B/fwd') . '</th>
				<th>' . _('Depn B/fwd') . '</th>
				<th>' . _('Additions') . '</th>
				<th>' . _('Depn') . '</th>
				<th>' . _('Cost C/fwd') . '</th>
				<th>' . _('Depn C/fwd') . '</th>
				<th>' . _('NBV') . '</th>
				<th>' . _('Disposal Value') . '</th>
                                <th>' . _('No of Units') . '</th>
                                <th>' . _('Units On Hire') . '</th>
			</tr>';
	}
	$TotalCostBfwd =0;
	$TotalCostCfwd = 0;
	$TotalDepnBfwd = 0;
	$TotalDepnCfwd = 0;
	$TotalAdditions = 0;
	$TotalDepn = 0;
	$TotalDisposals = 0;
	$TotalNBV = 0;

	while ($myrow = DB_fetch_array($result)) {
		
            
		if (Date1GreaterThanDate2(ConvertSQLDate($myrow['disposaldate']),$_POST['FromDate']) OR $myrow['disposaldate']='0000-00-00') {

			if ($myrow['disposaldate']!='0000-00-00' AND Date1GreaterThanDate2($_POST['ToDate'], ConvertSQLDate($myrow['disposaldate']))){
				/*The asset was disposed during the period */
				$CostCfwd = 0;
				$AccumDepnCfwd = 0;
			} else {
				$CostCfwd = $myrow['periodadditions'] + $myrow['costbfwd'];
				$AccumDepnCfwd = $myrow['perioddepn'] + $myrow['depnbfwd'];
			}

			if (isset($_POST['pdf'])) {
                              $LeftOvers = $pdf->addTextWrap($XPos, $YPos, 30 , $FontSize, $myrow['assetid']);
				$LeftOvers = $pdf->addTextWrap($XPos + 30, $YPos, 150 , $FontSize, $myrow['description']);
				$LeftOvers = $pdf->addTextWrap($XPos + 180, $YPos, 40 , $FontSize, $myrow['serialno']);
				$LeftOvers = $pdf->addTextWrap($XPos + 270, $YPos, 70, $FontSize, locale_number_format($myrow['costbfwd'], 0), 'right');
				$LeftOvers = $pdf->addTextWrap($XPos + 340, $YPos, 70, $FontSize, locale_number_format($myrow['depnbfwd'], 0), 'right');
				$LeftOvers = $pdf->addTextWrap($XPos + 410, $YPos, 70, $FontSize, locale_number_format($myrow['periodadditions'], 0), 'right');
				$LeftOvers = $pdf->addTextWrap($XPos + 480, $YPos, 70, $FontSize, locale_number_format($myrow['perioddepn'], 0), 'right');
				$LeftOvers = $pdf->addTextWrap($XPos + 550, $YPos, 70, $FontSize, locale_number_format($CostCfwd, 0), 'right');
				$LeftOvers = $pdf->addTextWrap($XPos + 620, $YPos, 70, $FontSize, locale_number_format($AccumDepnCfwd, 0), 'right');
				$LeftOvers = $pdf->addTextWrap($XPos + 690, $YPos, 70, $FontSize, locale_number_format($CostCfwd - $AccumDepnCfwd, 0), 'right');

				$YPos = $YPos - (0.8 * $line_height);
				if ($YPos < $Bottom_Margin + $line_height) {
					PDFPageHeader();
				}
                                
			} elseif (isset($_POST['csv'])) {
                           
				$csv_output .= $myrow['assetid'] . ',' . $myrow['longdescription'] .',' . $myrow['serialno'] . ',' . $myrow['locationdescription']  . $myrow['costbfwd'] . ',' . $myrow['periodadditions'] . ',' . $myrow['depnbfwd'] . ',' . $myrow['perioddepn'] . ',' . $CostCfwd . ',' . $AccumDepnCfwd . ',' . ($CostCfwd - $AccumDepnCfwd) . ',' . $myrow['perioddisposal'] .',' . $myrow['pcs'].',' . $myrow['Hiredout']. "\n";
                            
			} else {
                         	echo '<tr>
						<td style="vertical-align:top">' . $myrow['assetid'] . '</td>
						<td style="vertical-align:top">' . $myrow['longdescription'] . '</td>
						<td style="vertical-align:top">' . $myrow['serialno'] . '</td>
						<td>' . $myrow['locationdescription'] . '<br />';
			
				echo '</td>
					<td style="vertical-align:top" class="number">' . locale_number_format($myrow['costbfwd'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="vertical-align:top" class="number">' . locale_number_format($myrow['depnbfwd'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="vertical-align:top" class="number">' . locale_number_format($myrow['periodadditions'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="vertical-align:top" class="number">' . locale_number_format($myrow['perioddepn'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="vertical-align:top" class="number">' . locale_number_format($CostCfwd , $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="vertical-align:top" class="number">' . locale_number_format($AccumDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="vertical-align:top" class="number">' . locale_number_format($CostCfwd - $AccumDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="vertical-align:top" class="number">' . locale_number_format($myrow['perioddisposal'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                                        <td style="vertical-align:top" class="number">' . $myrow['pcs'] . '</td>
                                        <td style="vertical-align:top" class="number">' . $myrow['Hiredout'] . '</td>
				</tr>';
                            
			}
		} // end of if the asset was either not disposed yet or disposed after the start date
		$TotalCostBfwd +=$myrow['costbfwd'];
		$TotalCostCfwd += ($myrow['costbfwd']+$myrow['periodadditions']);
		$TotalDepnBfwd += $myrow['depnbfwd'];
		$TotalDepnCfwd += ($myrow['depnbfwd']+$myrow['perioddepn']);
		$TotalAdditions += $myrow['periodadditions'];
		$TotalDepn += $myrow['perioddepn'];
		$TotalDisposals += $myrow['perioddisposal'];

		$TotalNBV += ($CostCfwd - $AccumDepnCfwd);
	
        }
	if (isset($_POST['pdf'])) {
		$LeftOvers = $pdf->addTextWrap($XPos, $YPos, 300 - $Left_Margin, $FontSize, _('TOTAL'));
		$LeftOvers = $pdf->addTextWrap($XPos + 270, $YPos, 70, $FontSize, locale_number_format($TotalCostBfwd, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $pdf->addTextWrap($XPos + 340, $YPos, 70, $FontSize, locale_number_format($TotalDepnBfwd, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $pdf->addTextWrap($XPos + 410, $YPos, 70, $FontSize, locale_number_format($TotalAdditions, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $pdf->addTextWrap($XPos + 480, $YPos, 70, $FontSize, locale_number_format($TotalDepn, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $pdf->addTextWrap($XPos + 550, $YPos, 70, $FontSize, locale_number_format($TotalCostCfwd, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $pdf->addTextWrap($XPos + 620, $YPos, 70, $FontSize, locale_number_format($TotalDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $pdf->addTextWrap($XPos + 690, $YPos, 70, $FontSize, locale_number_format($TotalNBV, $_SESSION['CompanyRecord']['decimalplaces']), 'right');

		$pdf->Output($_SESSION['DatabaseName'] . '_Asset Register_' . date('Y-m-d') . '.pdf', 'I');
		exit;
	} elseif (isset($_POST['csv'])) {
		$FileName =  $_SESSION['reports_dir'] . '/FixedAssetRegister_' . Date('Y-m-d') .'.csv';
		$csvFile = fopen($FileName, 'w');
		$i = fwrite($csvFile, $csv_output);
		header('Location: ' .$_SESSION['reports_dir'] . '/FixedAssetRegister_' . Date('Y-m-d') .'.csv');

	} else {
		//Total Values
		echo '<tr><th style="vertical-align:top" colspan="4">' . _('TOTAL') . '</th>';
		echo '<th style="text-align:right">' . locale_number_format($TotalCostBfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>';
		echo '<th style="text-align:right">' . locale_number_format($TotalDepnBfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>';
		echo '<th style="text-align:right">' . locale_number_format($TotalAdditions, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>';
		echo '<th style="text-align:right">' . locale_number_format($TotalDepn, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>';
		echo '<th style="text-align:right">' . locale_number_format($TotalCostCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>';
		echo '<th style="text-align:right">' . locale_number_format($TotalDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>';
		echo '<th style="text-align:right">' . locale_number_format($TotalNBV, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>';
		echo '<th style="text-align:right">' . locale_number_format($TotalDisposals, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>'
                        . '<th></th>'
                        . '<th></th></tr>';
		echo '</table></div>';

        echo '<input type="hidden" name="FromDate" value="' . $_POST['FromDate'] . '" />';
        echo '<input type="hidden" name="ToDate" value="' . $_POST['ToDate'] . '" />';
        echo '<input type="hidden" name="AssetCategory" value="' . $_POST['AssetCategory'] . '" />';
        echo '<input type="hidden" name="AssetID" value="' . $_POST['AssetID'] . '" />';
        echo '<input type="hidden" name="AssetLocation" value="' . $_POST['AssetLocation'] . '" />';

		echo '<br /><div class="centre">'
        . '<input type="submit" name="pdf" value="' . _('Print as a pdf') . '" />&nbsp;';
		echo '<input type="submit" name="csv" value="' . _('Print as CSV') . '" />
              </div>
              </div>
              </form>';
	}
} else {
	// Dashboard view — show full asset list + filters
	$ViewTopic = 'FixedAssets';
	$BookMark = 'AssetRegister';

	include('includes/header.inc');
	echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

	// Top action buttons
	echo '<div class="panel panel-default">
		<div class="panel-body">
			<a class="btn btn-primary" href="FixedAssetItems.php">' . _('Manage Assets') . '</a>
			<a class="btn btn-success" href="import_excel.php?type=assetregister">' . _('Import from Excel') . '</a>
			<a class="btn btn-warning" href="FixedAssetDisposal.php">' . _('Dispose Asset') . '</a>
		</div>
	</div>';

	// Filter form
	$resultCat = DB_query('SELECT categoryid,categorydescription FROM fixedassetcategories', $db);
	echo '<form autocomplete="off" id="RegisterForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" class="form-horizontal">';
	echo '<div class="panel panel-default">
		<div class="panel-heading">' . _('Filter Options') . '</div>
		<div class="panel-body">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<div class="row">
			<div class="col-md-3 col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-5">' . _('Asset Category') . '</label>
					<div class="col-sm-7">
						<select name="AssetCategory" class="form-control">';
	echo '<option value="%">' . _('ALL') . '</option>';
	while ($myrow = DB_fetch_array($resultCat)) {
		echo '<option value="' . $myrow['categoryid'] . '">' . $myrow['categorydescription'] . '</option>';
	}
	echo '</select>
					</div>
				</div>
			</div>';
	$resultLoc = DB_query("SELECT locationid, locationdescription FROM fixedassetlocations", $db);
	echo '<div class="col-md-3 col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-5">' . _('Asset Location') . '</label>
					<div class="col-sm-7">
						<select name="AssetLocation" class="form-control">
							<option value="%">' . _('ALL') . '</option>';
	while ($myrow = DB_fetch_array($resultLoc)) {
		echo '<option value="' . $myrow['locationid'] . '">' . $myrow['locationdescription'] . '</option>';
	}
	echo '</select>
					</div>
				</div>
			</div>';
	$resultAssets = DB_query("SELECT assetid, description FROM fixedassets", $db);
	echo '<div class="col-md-3 col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-5">' . _('Asset') . '</label>
					<div class="col-sm-7">
						<select name="AssetID" class="form-control">
							<option value="%">' . _('ALL') . '</option>';
	while ($myrow = DB_fetch_array($resultAssets)) {
		echo '<option value="' . $myrow['assetid'] . '">' . $myrow['assetid'] . ' - ' . $myrow['description'] . '</option>';
	}
	echo '</select>
					</div>
				</div>
			</div>';
	if (empty($_POST['FromDate'])) {
		$_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), date('d'), date('Y') - 1));
	}
	if (empty($_POST['ToDate'])) {
		$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
	}
	echo '<div class="col-md-3 col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-5">' . _('From Date') . '</label>
					<div class="col-sm-7">
						<input type="text" class="date form-control" alt="' . $_SESSION['DefaultDateFormat'] . '" name="FromDate" maxlength="10" size="11" value="' . $_POST['FromDate'] . '" />
					</div>
				</div>
			</div>
			<div class="col-md-3 col-sm-6">
				<div class="form-group">
					<label class="control-label col-sm-5">' . _('To Date') . '</label>
					<div class="col-sm-7">
						<input type="text" class="date form-control" alt="' . $_SESSION['DefaultDateFormat'] . '" name="ToDate" maxlength="10" size="11" value="' . $_POST['ToDate'] . '" />
					</div>
				</div>
			</div>
		</div>
		<br />
		<div class="row">
			<div class="col-sm-12 text-center">
				<input type="submit" name="submit" value="' . _('Show Financial Report') . '" class="btn btn-primary" />&nbsp;
				<input type="submit" name="printfull" value="' . _('Print Full Register (PDF)') . '" class="btn btn-default" />
			</div>
		</div>
		</div>
	</div>
	</form>';

	// Full asset list table (default view)
	$listSql = "SELECT
                    fa.assetid,
                    fa.equipment_code,
                    fa.description,
                    fa.manufacturer,
                    fa.serialno,
                    fa.modelno,
                    fa.quantity,
                    fa.cost,
                    fa.datepurchased,
                    fa.status,
                    fa.remarks,
                    fal.locationdescription,
                    fac.categorydescription
                FROM fixedassets fa
                LEFT JOIN fixedassetlocations fal ON fal.locationid = fa.assetlocation
                LEFT JOIN fixedassetcategories fac ON fac.categoryid = fa.assetcategoryid
                ORDER BY fal.locationdescription, fa.description";
	$listResult = DB_query($listSql, $db);

	echo '<div class="panel panel-default">
		<div class="panel-heading">' . _('Full Asset Register') . '</div>
		<div class="panel-body">
		<div class="table-responsive">
		<table class="table table-striped table-bordered">';
	echo '<thead><tr>
			<th>' . _('Equip Code') . '</th>
			<th>' . _('Equipment Name') . '</th>
			<th>' . _('Manufacturer') . '</th>
			<th>' . _('Serial No.') . '</th>
			<th>' . _('Model No.') . '</th>
			<th>' . _('Qty') . '</th>
			<th>' . _('Date Purchased') . '</th>
			<th>' . _('Status') . '</th>
			<th>' . _('Cost') . '</th>
			<th>' . _('Location') . '</th>
			<th>' . _('Category') . '</th>
			<th>' . _('Remarks') . '</th>
		</tr></thead>
		<tbody>';

	$totalCost = 0;
	while ($a = DB_fetch_array($listResult)) {
		$totalCost += (float)$a['cost'];
		$code = $a['equipment_code'] ?: $a['assetid'];
		echo '<tr>
				<td>' . htmlspecialchars($code) . '</td>
				<td>' . htmlspecialchars($a['description']) . '</td>
				<td>' . htmlspecialchars($a['manufacturer']) . '</td>
				<td>' . htmlspecialchars($a['serialno']) . '</td>
				<td>' . htmlspecialchars($a['modelno']) . '</td>
				<td class="number">' . (int)$a['quantity'] . '</td>
				<td>' . ($a['datepurchased'] ? date($_SESSION['DefaultDateFormat'], strtotime($a['datepurchased'])) : '') . '</td>
				<td>' . htmlspecialchars($a['status']) . '</td>
				<td class="number">' . number_format((float)$a['cost'], 2) . '</td>
				<td>' . htmlspecialchars($a['locationdescription']) . '</td>
				<td>' . htmlspecialchars($a['categorydescription']) . '</td>
				<td>' . htmlspecialchars($a['remarks']) . '</td>
			</tr>';
	}
	echo '</tbody>
		<tfoot>
		<tr style="font-weight:bold;">
			<td colspan="8">' . _('GRAND TOTAL') . '</td>
			<td class="number">' . number_format($totalCost, 2) . '</td>
			<td colspan="3"></td>
		</tr>
		</tfoot>';
	echo '</table>
		</div>
		</div>
	</div>';
}
include ('includes/footer.inc');


function PDFPageHeader (){
	global $PageNumber,
				$pdf,
				$XPos,
				$YPos,
				$Page_Height,
				$Page_Width,
				$Top_Margin,
				$Bottom_Margin,
				$FontSize,
				$Left_Margin,
				$Right_Margin,
				$line_height,
				$AssetDescription,
				$AssetCategory;

	if ($PageNumber>1){
		$pdf->newPage();
	}

	$FontSize=10;
	$YPos= $Page_Height-$Top_Margin;
	$XPos=0;
	$pdf->addJpegFromFile($_SESSION['LogoFile'] ,$XPos+20,$YPos-50,0,60);



	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-240,$YPos,240,$FontSize,htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-240,$YPos-($line_height*1),240,$FontSize, _('Asset Category ').' ' . $AssetCategory );
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-240,$YPos-($line_height*2),240,$FontSize, _('Asset Location ').' ' . $_POST['AssetLocation'] );
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-240,$YPos-($line_height*3),240,$FontSize, _('Asset ID').': ' . $AssetDescription);
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-240,$YPos-($line_height*4),240,$FontSize, _('From').': ' . $_POST['FromDate']);
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-240,$YPos-($line_height*5),240,$FontSize, _('To').': ' . $_POST['ToDate']);
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-240,$YPos-($line_height*7),240,$FontSize, _('Page'). ' ' . $PageNumber);

	$YPos -= 60;

	$YPos -=2*$line_height;
	//Note, this is ok for multilang as this is the value of a Select, text in option is different

	$YPos -=(2*$line_height);

	/*Draw a rectangle to put the headings in     */
	$YTopLeft=$YPos+$line_height;
	$pdf->line($Left_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);
	$pdf->line($Left_Margin, $YPos+$line_height,$Left_Margin, $YPos- $line_height);
	$pdf->line($Left_Margin, $YPos- $line_height,$Page_Width-$Right_Margin, $YPos- $line_height);
	$pdf->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos- $line_height);

	/*set up the headings */
	$FontSize=10;
	$XPos = $Left_Margin+1;
	$YPos -=(0.8*$line_height);
	$LeftOvers = $pdf->addTextWrap($XPos,$YPos,30,$FontSize,  _('Asset'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+30,$YPos,150,$FontSize,  _('Description'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+180,$YPos,40,$FontSize,  _('Serial No.'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+220,$YPos,50,$FontSize,  _('Purchased'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+270,$YPos,70,$FontSize,  _('Cost B/Fwd'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+340,$YPos,70,$FontSize,  _('Depn B/Fwd'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+410,$YPos,70,$FontSize,  _('Additions'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+480,$YPos,70,$FontSize,  _('Depreciation'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+550,$YPos,70,$FontSize,  _('Cost C/Fwd'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+620,$YPos,70,$FontSize,  _('Depn C/Fwd'), 'centre');
	$LeftOvers = $pdf->addTextWrap($XPos+690,$YPos,70,$FontSize,  _('Net Book Value'), 'centre');
	//$LeftOvers = $pdf->addTextWrap($XPos+760,$YPos,70,$FontSize,  _('Disposal Proceeds'), 'centre');

	$pdf->line($Left_Margin, $YTopLeft,$Page_Width-$Right_Margin, $YTopLeft);
	$pdf->line($Left_Margin, $YTopLeft,$Left_Margin, $Bottom_Margin);
	$pdf->line($Left_Margin, $Bottom_Margin,$Page_Width-$Right_Margin, $Bottom_Margin);
	$pdf->line($Page_Width-$Right_Margin, $Bottom_Margin,$Page_Width-$Right_Margin, $YTopLeft);

	$FontSize=8;
	$YPos -= (1.5 * $line_height);

	$PageNumber++;
}

?>
