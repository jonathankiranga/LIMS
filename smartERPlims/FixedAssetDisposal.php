<?php
include('includes/session.inc');
$Title = _('Fixed Asset Disposal');

$ViewTopic = 'FixedAssets';
$BookMark = 'AssetDisposal';

include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

$assetId = isset($_GET['AssetID']) ? $_GET['AssetID'] : (isset($_POST['AssetID']) ? $_POST['AssetID'] : (isset($_POST['Select']) ? $_POST['Select'] : ''));

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/magnifier.png" title="' . _('Search') .
		'" alt="" />' . ' ' . $Title . '</p>';

// ---------- Commit disposal ----------
if (isset($_POST['ConfirmDisposal']) && $assetId != '') {

	$disposalDate   = $_POST['DisposalDate'];
	$disposalProceeds = (float)filter_number_format($_POST['DisposalProceeds']);
	$disposalRemarks = $_POST['DisposalRemarks'];
	$isPartial      = ($_POST['IsPartial'] ?? '0') === '1';
	$qtyToDispose   = $isPartial ? (int)$_POST['QtyToDispose'] : 0;

	// Fetch current asset
	$rs = DB_query("SELECT * FROM fixedassets WHERE assetid='" . $assetId . "'", $db);
	if (DB_num_rows($rs) == 0) {
		prnMsg(_('Asset not found'), 'error');
	} else {
		$asset = DB_fetch_array($rs);
		$currentQty = (int)($asset['quantity'] ?: 1);

		if ($isPartial && ($qtyToDispose < 1 || $qtyToDispose >= $currentQty)) {
			prnMsg(_('Partial quantity must be between 1 and') . ' ' . ($currentQty - 1), 'error');
			unset($_POST['ConfirmDisposal']);
		} else {
			$periodNo   = GetPeriod($disposalDate, $db, true);
			$transNo    = GetNextTransNo(45, $db);
			$sqlDate    = FormatDateForSQL($disposalDate);
			$datePurch  = $asset['datepurchased'] ? "'" . $asset['datepurchased'] . "'" : 'NOW()';

			$result = DB_Txn_Begin($db);
			$queries = [];

			if ($isPartial) {
				// --- Partial disposal ---
				$remainingQty = $currentQty - $qtyToDispose;
				$ratioDisposed = $qtyToDispose / $currentQty;
				$costDisposed   = (float)$asset['cost'] * $ratioDisposed;
				$depnDisposed   = (float)$asset['accumdepn'] * $ratioDisposed;
				$costRemaining  = (float)$asset['cost'] - $costDisposed;
				$depnRemaining  = (float)$asset['accumdepn'] - $depnDisposed;

				// 1. Insert new asset row for disposed portion
				$queries[] = "INSERT INTO fixedassets
					(equipment_code, serialno, barcode, assetlocation, cost, accumdepn,
					 datepurchased, disposaldate, disposalproceeds, assetcategoryid,
					 description, longdescription, depntype, depnrate, quantity,
					 manufacturer, modelno, status, remarks)
					VALUES (
						'" . $db->real_escape_string($asset['equipment_code'] ?? '') . "',
						'" . $db->real_escape_string($asset['serialno']) . "',
						'" . $db->real_escape_string($asset['barcode']) . "',
						'" . $db->real_escape_string($asset['assetlocation']) . "',
						" . $costDisposed . ",
						" . $depnDisposed . ",
						" . $datePurch . ",
						'" . $sqlDate . "',
						" . $disposalProceeds . ",
						'" . $db->real_escape_string($asset['assetcategoryid']) . "',
						'" . $db->real_escape_string($asset['description']) . "',
						'" . $db->real_escape_string($asset['longdescription'] ?? '') . "',
						" . (int)$asset['depntype'] . ",
						" . (float)$asset['depnrate'] . ",
						" . $qtyToDispose . ",
						'" . $db->real_escape_string($asset['manufacturer'] ?? '') . "',
						'" . $db->real_escape_string($asset['modelno'] ?? '') . "',
						'Disposed',
						'" . $db->real_escape_string($disposalRemarks) . "'
					)";

				// 2. Reduce remaining asset proportionally
				$queries[] = "UPDATE fixedassets SET
					quantity = " . $remainingQty . ",
					cost = " . $costRemaining . ",
					accumdepn = " . $depnRemaining . "
					WHERE assetid = '" . $assetId . "'";

				// 3. Insert trans for disposed portion (need new assetid from clone)
				// We'll get the new ID after INSERT
			} else {
				// --- Full disposal ---
				$queries[] = "UPDATE fixedassets SET
					status = 'Disposed',
					disposaldate = '" . $sqlDate . "',
					disposalproceeds = " . $disposalProceeds . ",
					remarks = CONCAT_WS('\\n', remarks, '" . $db->real_escape_string($disposalRemarks) . "')
					WHERE assetid = '" . $assetId . "'";

				$queries[] = "INSERT INTO fixedassettrans
					(assetid, transtype, transno, transdate, periodno, inputdate,
					 fixedassettranstype, amount, units)
					VALUES (
						'" . $assetId . "', 45, " . $transNo . ", '" . $sqlDate . "',
						" . $periodNo . ", NOW(), 'disposal',
						" . $disposalProceeds . ", " . $currentQty . "
					)";
			}

			// Execute queries
			foreach ($queries as $q) {
				$result = DB_query($q, $db, '', '', true);
			}

			// For partial, insert trans for the disposed clone
			if ($isPartial) {
				$newId = DB_Last_Insert_ID($db, 'fixedassets', 'assetid');
				$result = DB_query(
					"INSERT INTO fixedassettrans
						(assetid, transtype, transno, transdate, periodno, inputdate,
						 fixedassettranstype, amount, units)
						VALUES (
							'" . $newId . "', 45, " . $transNo . ", '" . $sqlDate . "',
							" . $periodNo . ", NOW(), 'disposal',
							" . $disposalProceeds . ", " . $qtyToDispose . "
						)",
					$db, '', '', true
				);
			}

			if (DB_error_no($db) > 0) {
				DB_Txn_Rollback($db);
				prnMsg(_('Disposal failed — transaction rolled back'), 'error');
			} else {
				DB_Txn_Commit($db);
				prnMsg(_('Disposal successfully recorded'), 'success');
				echo '<p><a class="btn btn-primary" href="FixedAssetDisposal.php">' . _('Dispose Another Asset') . '</a>
					<a class="btn btn-info" href="FixedAssetRegister.php">' . _('Back to Dashboard') . '</a></p>';
				include('includes/footer.inc');
				exit;
			}
		}
	}
}

// ---------- Disposal form for selected asset ----------
if ($assetId != '' && !isset($_POST['ConfirmDisposal'])) {
	$rs = DB_query("SELECT
			fa.*,
			fal.locationdescription,
			fac.categorydescription
		FROM fixedassets fa
		LEFT JOIN fixedassetlocations fal ON fal.locationid = fa.assetlocation
		LEFT JOIN fixedassetcategories fac ON fac.categoryid = fa.assetcategoryid
		WHERE fa.assetid='" . $assetId . "'", $db);

	if (DB_num_rows($rs) == 0) {
		prnMsg(_('Asset not found'), 'error');
	} else {
		$a = DB_fetch_array($rs);
		$qty = (int)($a['quantity'] ?: 1);
		$nbv = (float)$a['cost'] - (float)$a['accumdepn'];
?>
	<form autocomplete="off" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'); ?>">
	<div>
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
	<input type="hidden" name="AssetID" value="<?php echo $assetId; ?>" />

	<div class="container">
	<table class="table table-bordered">
		<tr>
			<th colspan="4"><?php echo _('Asset Details'); ?></th>
		</tr>
		<tr>
			<td><strong><?php echo _('Asset ID'); ?>:</strong></td>
			<td><?php echo $a['assetid']; ?></td>
			<td><strong><?php echo _('Equip Code'); ?>:</strong></td>
			<td><?php echo htmlspecialchars($a['equipment_code'] ?: ''); ?></td>
		</tr>
		<tr>
			<td><strong><?php echo _('Description'); ?>:</strong></td>
			<td><?php echo htmlspecialchars($a['description']); ?></td>
			<td><strong><?php echo _('Manufacturer'); ?>:</strong></td>
			<td><?php echo htmlspecialchars($a['manufacturer'] ?: ''); ?></td>
		</tr>
		<tr>
			<td><strong><?php echo _('Serial No.'); ?>:</strong></td>
			<td><?php echo htmlspecialchars($a['serialno']); ?></td>
			<td><strong><?php echo _('Model No.'); ?>:</strong></td>
			<td><?php echo htmlspecialchars($a['modelno'] ?: ''); ?></td>
		</tr>
		<tr>
			<td><strong><?php echo _('Location'); ?>:</strong></td>
			<td><?php echo htmlspecialchars($a['locationdescription'] ?: $a['assetlocation']); ?></td>
			<td><strong><?php echo _('Category'); ?>:</strong></td>
			<td><?php echo htmlspecialchars($a['categorydescription'] ?: ''); ?></td>
		</tr>
		<tr>
			<td><strong><?php echo _('Quantity'); ?>:</strong></td>
			<td class="number"><?php echo $qty; ?></td>
			<td><strong><?php echo _('Status'); ?>:</strong></td>
			<td><?php echo htmlspecialchars($a['status'] ?: _('Active')); ?></td>
		</tr>
		<tr>
			<td><strong><?php echo _('Cost'); ?>:</strong></td>
			<td class="number"><?php echo number_format((float)$a['cost'], 2); ?></td>
			<td><strong><?php echo _('Accum Depn'); ?>:</strong></td>
			<td class="number"><?php echo number_format((float)$a['accumdepn'], 2); ?></td>
		</tr>
		<tr>
			<td><strong><?php echo _('Net Book Value'); ?>:</strong></td>
			<td class="number"><strong><?php echo number_format($nbv, 2); ?></strong></td>
			<td colspan="2"></td>
		</tr>
	</table>

	<table class="table table-bordered">
		<tr>
			<th colspan="4"><?php echo _('Disposal Details'); ?></th>
		</tr>
		<tr>
			<td><strong><?php echo _('Disposal Date'); ?>:</strong></td>
			<td><input type="text" class="date" alt="<?php echo $_SESSION['DefaultDateFormat']; ?>" name="DisposalDate" maxlength="10" size="11" value="<?php echo date($_SESSION['DefaultDateFormat']); ?>" required="required" /></td>
			<td><strong><?php echo _('Disposal Proceeds'); ?>:</strong></td>
			<td><input type="text" class="number" name="DisposalProceeds" size="15" value="0" required="required" /></td>
		</tr>
		<tr>
			<td><strong><?php echo _('Disposal Type'); ?>:</strong></td>
			<td colspan="3">
				<input type="radio" name="IsPartial" value="0" checked="checked" onclick="toggleQty(false)" /> <strong><?php echo _('Full Disposal'); ?></strong>
				&nbsp;&nbsp;
				<input type="radio" name="IsPartial" value="1" onclick="toggleQty(true)" /> <strong><?php echo _('Partial Disposal'); ?></strong>
			</td>
		</tr>
		<tr id="qtyRow" style="display:none;">
			<td><strong><?php echo _('Quantity to Dispose'); ?>:</strong></td>
			<td>
				<input type="number" name="QtyToDispose" id="QtyToDispose" min="1" max="<?php echo $qty - 1; ?>" value="1" size="5" />
				<small><?php echo _('Max'); ?>: <?php echo $qty - 1; ?></small>
			</td>
			<td><strong><?php echo _('Remaining Qty'); ?>:</strong></td>
			<td id="remainingQtyDisplay"><?php echo $qty - 1; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo _('Remarks'); ?>:</strong></td>
			<td colspan="3"><textarea name="DisposalRemarks" cols="60" rows="3"></textarea></td>
		</tr>
	</table>

	<br />
	<div class="centre">
		<input type="submit" name="ConfirmDisposal" value="<?php echo _('Confirm Disposal'); ?>" class="btn btn-primary"
			onclick="return confirm('<?php echo _('Are you sure you want to record this disposal? This action cannot be undone.'); ?>');" />
	</div>
	</div>
	</div>
	</form>

	<script>
	function toggleQty(isPartial) {
		document.getElementById('qtyRow').style.display = isPartial ? '' : 'none';
	}
	document.getElementById('QtyToDispose')?.addEventListener('input', function() {
		var max = parseInt(this.getAttribute('max'));
		var val = parseInt(this.value) || 0;
		document.getElementById('remainingQtyDisplay').textContent = max + 1 - Math.min(val, max);
	});
	</script>
<?php
	}
	include('includes/footer.inc');
	exit;
}

// ---------- Search / Select ----------
if (!isset($_POST['Search']) && !isset($_POST['Select'])) {
	// Show search form
	$catResult = DB_query("SELECT categoryid, categorydescription FROM fixedassetcategories", $db);
?>
<form autocomplete="off" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'); ?>" method="post">
<div>
<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />

<table class="table table-bordered">
	<tr>
		<td><?php echo _('Asset Category'); ?>:</td>
		<td><select name="AssetCategory">
			<option value=""><?php echo _('All'); ?></option>
<?php while ($myrow = DB_fetch_array($catResult)): ?>
			<option value="<?php echo $myrow['categoryid']; ?>"><?php echo $myrow['categorydescription']; ?></option>
<?php endwhile; ?>
		</select></td>
		<td><?php echo _('Enter partial description'); ?>:</td>
		<td><input type="text" name="Keywords" size="20" maxlength="25" /></td>
	</tr>
	<tr>
		<td><?php echo _('Asset Location'); ?>:</td>
		<td><select name="AssetLocation">
			<option value=""><?php echo _('All'); ?></option>
<?php
$locResult = DB_query("SELECT locationid, locationdescription FROM fixedassetlocations", $db);
while ($myrow = DB_fetch_array($locResult)):
?>
			<option value="<?php echo $myrow['locationid']; ?>"><?php echo $myrow['locationdescription']; ?></option>
<?php endwhile; ?>
		</select></td>
		<td><strong><?php echo _('OR'); ?></strong> <?php echo _('Enter partial asset code'); ?>:</td>
		<td><input type="text" name="AssetCode" size="15" maxlength="13" /></td>
	</tr>
</table>
<br />
<div class="centre"><input type="submit" name="Search" value="<?php echo _('Search Now'); ?>" class="btn btn-primary" /></div>
</div>
</form>
<?php
}

// ---------- Search results ----------
if (isset($_POST['Search'])) {
	$where = [];
	if (!empty($_POST['AssetCategory'])) {
		$where[] = "fa.assetcategoryid='" . $db->real_escape_string($_POST['AssetCategory']) . "'";
	}
	if (!empty($_POST['AssetLocation'])) {
		$where[] = "fa.assetlocation='" . $db->real_escape_string($_POST['AssetLocation']) . "'";
	}
	if (!empty($_POST['Keywords'])) {
		$kw = '%' . str_replace(' ', '%', mb_strtoupper(trim($_POST['Keywords']))) . '%';
		$where[] = "UPPER(fa.description) LIKE '" . $db->real_escape_string($kw) . "'";
	}
	if (!empty($_POST['AssetCode'])) {
		$code = '%' . trim($_POST['AssetCode']) . '%';
		$where[] = "fa.assetid LIKE '" . $db->real_escape_string($code) . "'";
	}
	$whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

	$sql = "SELECT fa.assetid, fa.description, fa.serialno, fa.quantity, fa.cost,
				   fa.accumdepn, fa.status,
				   fal.locationdescription
			FROM fixedassets fa
			LEFT JOIN fixedassetlocations fal ON fal.locationid = fa.assetlocation
			$whereClause
			ORDER BY fa.assetid";
	$result = DB_query($sql, $db);

	if (DB_num_rows($result) == 0) {
		prnMsg(_('No assets found matching your criteria'), 'info');
	} else {
		echo '<br />';
		echo '<form autocomplete="off" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
		echo '<div>';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		// Preserve search filters
		foreach (['AssetCategory','AssetLocation','Keywords','AssetCode','Search'] as $f) {
			if (isset($_POST[$f])) {
				echo '<input type="hidden" name="' . $f . '" value="' . htmlspecialchars($_POST[$f]) . '" />';
			}
		}
		echo '<table class="table table-bordered table-striped">';
		echo '<tr>
				<th>' . _('Select') . '</th>
				<th>' . _('Asset ID') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('Serial No.') . '</th>
				<th>' . _('Qty') . '</th>
				<th>' . _('Cost') . '</th>
				<th>' . _('NBV') . '</th>
				<th>' . _('Location') . '</th>
				<th>' . _('Status') . '</th>
			</tr>';
		while ($row = DB_fetch_array($result)) {
			$nbv = (float)$row['cost'] - (float)$row['accumdepn'];
			echo '<tr>
					<td><input type="submit" name="Select" value="' . $row['assetid'] . '" class="btn btn-primary" /></td>
					<td>' . $row['assetid'] . '</td>
					<td>' . htmlspecialchars($row['description']) . '</td>
					<td>' . htmlspecialchars($row['serialno']) . '</td>
					<td class="number">' . (int)$row['quantity'] . '</td>
					<td class="number">' . number_format((float)$row['cost'], 2) . '</td>
					<td class="number">' . number_format($nbv, 2) . '</td>
					<td>' . htmlspecialchars($row['locationdescription']) . '</td>
					<td>' . htmlspecialchars($row['status'] ?: _('Active')) . '</td>
				</tr>';
		}
		echo '</table>';
		echo '</div>';
		echo '</form>';
	}
}

include('includes/footer.inc');
?>
