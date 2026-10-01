<?php
include('includes/session.inc');
$Title = _('Print Fixed Asset Register');
include('includes/header.inc');

$sql = "SELECT
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

$result = DB_query($sql, $db);

$groups = [];
while ($row = DB_fetch_array($result)) {
    $loc = $row['locationdescription'] ?: _('Unassigned');
    $groups[$loc][] = $row;
}
?>

<style>
    @page { size: A4 landscape; margin: 10mm; }
    body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 8pt; color: #000; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 7.5pt; }
    th, td { border: 1px solid #000; padding: 2px 4px; text-align: left; }
    th { background: #e0e0e0; font-weight: bold; text-align: center; }
    .loc-header { background: #d0d0d0; font-weight: bold; font-size: 9pt; padding: 4px; margin-top: 6px; }
    .number { text-align: right; }
    .center { text-align: center; }
    h1 { text-align: center; font-size: 13pt; margin-bottom: 3px; }
    .subtotal td { font-weight: bold; background: #f5f5f5; }
    .grandtotal td { font-weight: bold; background: #e8e8e8; font-size: 9pt; }

@media print {
    .no-print { display: none; }
}
</style>

<div class="no-print">
    <p><a class="btn btn-primary" href="javascript:window.print()"><?php echo _('Print / Save PDF'); ?></a>
    <a class="btn btn-info" href="FixedAssetRegister.php"><?php echo _('Back to Dashboard'); ?></a></p>
</div>

<h1><?php echo _('FIXED ASSET REGISTER'); ?></h1>

<?php
$grandTotal = 0;
$locIdx = 0;
foreach ($groups as $location => $assets):
    $locTotal = 0;
    $locIdx++;
?>
    <div class="loc-header"><?php echo $locIdx . '. ' . htmlspecialchars($location); ?></div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th><?php echo _('Equip Code'); ?></th>
                <th><?php echo _('Equipment Name'); ?></th>
                <th><?php echo _('Manufacturer'); ?></th>
                <th><?php echo _('Serial No.'); ?></th>
                <th><?php echo _('Model No.'); ?></th>
                <th><?php echo _('Qty'); ?></th>
                <th><?php echo _('Date Purchased'); ?></th>
                <th><?php echo _('Status'); ?></th>
                <th><?php echo _('Cost (KES)'); ?></th>
                <th><?php echo _('Category'); ?></th>
                <th><?php echo _('Remarks'); ?></th>
            </tr>
        </thead>
        <tbody>
<?php
    $idx = 1;
    foreach ($assets as $a):
        $locTotal += (float)$a['cost'];
        $code = $a['equipment_code'] ?: $a['assetid'];
?>
            <tr>
                <td class="center"><?php echo $idx++; ?></td>
                <td><?php echo htmlspecialchars($code); ?></td>
                <td><?php echo htmlspecialchars($a['description']); ?></td>
                <td><?php echo htmlspecialchars($a['manufacturer']); ?></td>
                <td><?php echo htmlspecialchars($a['serialno']); ?></td>
                <td><?php echo htmlspecialchars($a['modelno']); ?></td>
                <td class="center"><?php echo (int)$a['quantity']; ?></td>
                <td class="center"><?php echo $a['datepurchased'] ? date($_SESSION['DefaultDateFormat'], strtotime($a['datepurchased'])) : ''; ?></td>
                <td><?php echo htmlspecialchars($a['status']); ?></td>
                <td class="number"><?php echo number_format((float)$a['cost'], 2); ?></td>
                <td><?php echo htmlspecialchars($a['categorydescription']); ?></td>
                <td><?php echo htmlspecialchars($a['remarks']); ?></td>
            </tr>
<?php
        $grandTotal += (float)$a['cost'];
    endforeach;
?>
            <tr class="subtotal">
                <td colspan="9"><?php echo _('Location Total') . ': ' . htmlspecialchars($location); ?></td>
                <td class="number"><?php echo number_format($locTotal, 2); ?></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
<?php endforeach; ?>

<table class="table table-bordered">
    <tr class="grandtotal">
        <td colspan="9"><?php echo _('GRAND TOTAL'); ?></td>
        <td class="number"><?php echo number_format($grandTotal, 2); ?></td>
        <td colspan="2"></td>
    </tr>
</table>

<?php include('includes/footer.inc'); ?>
