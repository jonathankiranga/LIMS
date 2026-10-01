<?php
include('includes/session.inc');
$Title = _('Test Labor Mapping');
include('includes/header.inc');

echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/manufacture.png" title="' . _('Manufacturing') .'" alt="" />'
    . ' ' . _('Test Labor Mapping') . '</p>';

/* Handle Save */
if (isset($_POST['save_mapping'])) {
    $testItemcode = mysqli_real_escape_string($db, $_POST['test_itemcode']);
    $amount       = floatval($_POST['amount']);

    if (empty($testItemcode) || $amount <= 0) {
        prnMsg('Please select a test and enter a valid amount.', 'warn');
    } else {
        $existing = DB_query("SELECT id FROM test_labor_mapping WHERE test_itemcode='$testItemcode'", $db);
        if (DB_num_rows($existing) > 0) {
            DB_query("UPDATE test_labor_mapping SET amount=$amount, updated_at=NOW()
                WHERE test_itemcode='$testItemcode'", $db);
            prnMsg('Mapping updated.');
        } else {
            DB_query("INSERT INTO test_labor_mapping (test_itemcode, amount) VALUES ('$testItemcode', $amount)", $db);
            prnMsg('Mapping created.');
        }
    }
}

/* Handle Delete */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    DB_query("DELETE FROM test_labor_mapping WHERE id=".intval($_GET['delete']), $db);
    prnMsg('Mapping deleted.');
}

/* Fetch all mappings */
$result = DB_query("SELECT tlm.*, sm.descrip as test_name
    FROM test_labor_mapping tlm
    JOIN stockmaster sm ON sm.itemcode = tlm.test_itemcode
    ORDER BY sm.descrip", $db);

/* Add Form */
echo '<div style="margin:4px;padding:20px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:12px;">';
echo '<h3 style="margin:0 0 16px;color:#1e293b;">'._('Add Labor Cost per Test').'</h3>';
echo '<form method="POST" action="TestLaborMapping.php">';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end;">';

echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">'._('Test (Lab Test)').'</label>';
echo '<select name="test_itemcode" required style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;">';
echo '<option value="">'._('Select test...').'</option>';
$tResult = DB_query("SELECT itemcode, descrip FROM stockmaster WHERE isstock_1=1 AND inactive=0 ORDER BY descrip", $db);
while ($tRow = DB_fetch_array($tResult)) {
    echo '<option value="'.$tRow['itemcode'].'">'.$tRow['itemcode'].' - '.$tRow['descrip'].'</option>';
}
echo '</select></div>';

echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">'._('Labor Cost per Test ($)').'</label>';
echo '<input type="number" name="amount" step="0.01" min="0.01" value="10.00" required style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;"></div>';

echo '<div><button type="submit" name="save_mapping" style="padding:8px 20px;border:none;border-radius:6px;background:#16a34a;color:#fff;font-weight:600;cursor:pointer;">'._('Save Mapping').'</button></div>';

echo '</div></form></div>';

/* Table */
echo '<div style="margin:4px;padding:20px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;">';
echo '<h3 style="margin:0 0 16px;color:#1e293b;">'._('Existing Mappings').'</h3>';

echo '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
echo '<thead><tr style="background:#f1f5f9;border-bottom:2px solid #e2e8f0;">';
echo '<th style="padding:10px 12px;text-align:left;">'._('Test Code').'</th>';
echo '<th style="padding:10px 12px;text-align:left;">'._('Test Name').'</th>';
echo '<th style="padding:10px 12px;text-align:right;">'._('Cost per Test ($)').'</th>';
echo '<th style="padding:10px 12px;text-align:center;">'._('Actions').'</th>';
echo '</tr></thead><tbody>';

$altRow = false;
while ($row = DB_fetch_array($result)) {
    $bg = $altRow ? '#f8fafc' : '#fff';
    echo '<tr style="background:'.$bg.';border-bottom:1px solid #f1f5f9;">';
    echo '<td style="padding:10px 12px;font-family:monospace;">'.$row['test_itemcode'].'</td>';
    echo '<td style="padding:10px 12px;">'.$row['test_name'].'</td>';
    echo '<td style="padding:10px 12px;text-align:right;font-weight:600;">$'.number_format($row['amount'],2).'</td>';
    echo '<td style="padding:10px 12px;text-align:center;">';
    echo '<a href="TestLaborMapping.php?delete='.$row['id'].'" onclick="return confirm(\''._('Delete this mapping?').'\')" style="color:#dc2626;">'._('Delete').'</a>';
    echo '</td></tr>';
    $altRow = !$altRow;
}

if ($altRow === false && DB_num_rows($result) == 0) {
    echo '<tr><td colspan="4" style="padding:20px;text-align:center;color:#94a3b8;">'._('No mappings configured yet. Add your first mapping above.').'</td></tr>';
}

echo '</tbody></table></div>';

include('includes/footer.inc');
?>
