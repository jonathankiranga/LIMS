<?php
include('includes/session.inc');
$Title = _('Cost Allocation');
include('includes/header.inc');

$Action = isset($_GET['action']) ? $_GET['action'] : 'rules';

echo '<p class="page_title_text">'
    . '<img src="'.$RootPath.'/css/'.$Theme.'/images/manufacture.png" title="' . _('Manufacturing') .'" alt="" />'
    . ' ' . _('Cost Allocation') . '</p>';

/* Tabs */
echo '<div style="margin:0 4px 12px;display:flex;gap:4px;">';
foreach (['rules'=>'Allocation Rules','run'=>'Run Allocation','periods'=>'Allocation Periods'] as $k=>$v) {
    $cls = ($Action==$k) ? 'btn btn-primary' : 'btn btn-default';
    echo '<a href="CostAllocation.php?action='.$k.'" class="'.$cls.'" style="text-decoration:none;padding:8px 18px;border-radius:6px;font-size:13px;">'.$v.'</a>';
}
echo '</div>';

/* ============================================================
   RUN ALLOCATION
   ============================================================ */
if ($Action == 'run') {
    if (isset($_POST['run_allocation'])) {
        $periodStart = $_POST['period_start'];
        $periodEnd   = $_POST['period_end'];

        if (empty($periodStart) || empty($periodEnd)) {
            prnMsg('Please select both start and end dates for the allocation period.', 'warn');
        } else {
            // Get active rules
            $rules = array();
            $rResult = DB_query("SELECT * FROM cost_allocation_rules WHERE is_active=1", $db);
            while ($rRow = DB_fetch_array($rResult)) {
                $rules[] = $rRow;
            }

            if (count($rules) == 0) {
                prnMsg('No active allocation rules found. Please create rules first.', 'warn');
            } else {
                // Get reagent mapping
                $reagentMap = array();
                $rrResult = DB_query("SELECT test_itemcode, SUM(quantity) as total_qty
                    FROM test_reagent_mapping GROUP BY test_itemcode", $db);
                while ($rrRow = DB_fetch_array($rrResult)) {
                    $reagentMap[$rrRow['test_itemcode']] = $rrRow['total_qty'];
                }

                // Count tests completed in the period from LIMS
                $limsConn = new mysqli($limsHost, $limsUser, $limsPass, $limsName);
                if ($limsConn->connect_error) {
                    prnMsg('Could not connect to LIMS database for test count.', 'warn');
                } else {
                    $limsConn->query("SET time_zone = '+03:00'");

                    // Get test completions from sample_tests
                    $testCounts = array();
                    $tcResult = $limsConn->query("
                        SELECT st.itemcode, COUNT(*) as cnt
                        FROM sample_tests st
                        WHERE st.status IN ('completed','approved','verified')
                        AND st.lastdatetime BETWEEN '$periodStart' AND DATE_ADD('$periodEnd', INTERVAL 1 DAY)
                        GROUP BY st.itemcode
                    ");
                    if ($tcResult) {
                        while ($tcRow = $tcResult->fetch_assoc()) {
                            $testCounts[$tcRow['itemcode']] = $tcRow['cnt'];
                        }
                    }

                    $totalTests = array_sum($testCounts);
                    $totalLaborDays = $totalTests;
                    $totalEquipmentHours = $totalTests;
                    $totalReagentCost = 0;

                    // Calculate totals for basis
                    foreach ($testCounts as $tc => $cnt) {
                        if (isset($reagentMap[$tc])) {
                            // Get avg cost of reagents used
                            $rcResult = DB_query("SELECT COALESCE(AVG(sm.averagestock),0) as avg_cost
                                FROM test_reagent_mapping trm
                                JOIN stockmaster sm ON sm.itemcode = trm.reagent_itemcode
                                WHERE trm.test_itemcode = '".$limsConn->real_escape_string($tc)."'", $db);
                            $rcRow = DB_fetch_array($rcResult);
                            $totalReagentCost += $rcRow['avg_cost'] * $reagentMap[$tc] * $cnt;
                        }
                    }

                    $limsConn->close();

                    // Create period record
                    $pSQL = "INSERT INTO cost_allocation_periods (period_start, period_end, status, total_labor_days, total_equipment_hours, total_reagent_cost, tests_completed)
                        VALUES ('$periodStart', '$periodEnd', 'open', $totalLaborDays, $totalEquipmentHours, $totalReagentCost, $totalTests)";
                    DB_query($pSQL, $db);
                    $periodId = mysqli_insert_id($db);

                    // For each rule, get GL balance and allocate
                    $insertedCount = 0;
                    foreach ($rules as $rule) {
                        $glAccount = $rule['gl_account'];
                        $driver    = $rule['allocation_driver'];
                        $component = $rule['cost_component'];

                        // Get GL balance for this account in the period
                        $glResult = DB_query("SELECT COALESCE(SUM(amount),0) as balance
                            FROM Generalledger
                            WHERE accountcode = '$glAccount'
                            AND Docdate BETWEEN '$periodStart' AND DATE_ADD('$periodEnd', INTERVAL 1 DAY)", $db);
                        $glRow = DB_fetch_array($glResult);
                        $glBalance = $glRow['balance'];

                        if ($glBalance <= 0) continue;

                        // Get account description
                        $adResult = DB_query("SELECT accdesc FROM acct WHERE accno='$glAccount'", $db);
                        $adRow = DB_fetch_array($adResult);
                        $glDesc = $adRow['accdesc'];

                        if ($driver == 'direct') {
                            // Direct cost - allocate proportionally based on reagent cost
                            foreach ($testCounts as $tc => $cnt) {
                                if ($cnt == 0) continue;
                                if (!isset($reagentMap[$tc])) continue;

                                $rcResult2 = DB_query("SELECT COALESCE(AVG(sm.averagestock),0) as avg_cost
                                    FROM test_reagent_mapping trm
                                    JOIN stockmaster sm ON sm.itemcode = trm.reagent_itemcode
                                    WHERE trm.test_itemcode = '".$limsConn->real_escape_string($tc)."'", $db);
                                $rcRow2 = DB_fetch_array($rcResult2);
                                $testReagentCost = $rcRow2['avg_cost'] * $reagentMap[$tc] * $cnt;

                                if ($totalReagentCost > 0) {
                                    $allocated = ($testReagentCost / $totalReagentCost) * $glBalance;
                                } else {
                                    $allocated = 0;
                                }

                                $tcDesc = '';
                                $tdResult = DB_query("SELECT descrip FROM stockmaster WHERE itemcode='".$limsConn->real_escape_string($tc)."'", $db);
                                $tdRow = DB_fetch_array($tdResult);
                                if ($tdRow) $tcDesc = $tdRow['descrip'];

                                $iSQL = "INSERT INTO cost_allocation_results
                                    (period_id, test_itemcode, test_descrip, cost_component, allocated_amount, basis_value, basis_total, gl_account, gl_account_desc, tests_count)
                                    VALUES ($periodId, '$tc', '".mysqli_real_escape_string($db,$tcDesc)."', '$component', $allocated, $testReagentCost, $totalReagentCost, '$glAccount', '".mysqli_real_escape_string($db,$glDesc)."', $cnt)";
                                DB_query($iSQL, $db);
                                $insertedCount++;
                            }
                        } elseif ($driver == 'reagent_cost') {
                            // Allocate proportionally based on reagent cost (same formula as direct)
                            foreach ($testCounts as $tc => $cnt) {
                                if ($cnt == 0) continue;
                                if (!isset($reagentMap[$tc])) continue;

                                $rcResult2 = DB_query("SELECT COALESCE(AVG(sm.averagestock),0) as avg_cost
                                    FROM test_reagent_mapping trm
                                    JOIN stockmaster sm ON sm.itemcode = trm.reagent_itemcode
                                    WHERE trm.test_itemcode = '".$limsConn->real_escape_string($tc)."'", $db);
                                $rcRow2 = DB_fetch_array($rcResult2);
                                $testReagentCost = $rcRow2['avg_cost'] * $reagentMap[$tc] * $cnt;

                                if ($totalReagentCost > 0) {
                                    $allocated = ($testReagentCost / $totalReagentCost) * $glBalance;
                                } else {
                                    $allocated = 0;
                                }

                                $tcDesc = '';
                                $tdResult = DB_query("SELECT descrip FROM stockmaster WHERE itemcode='".$limsConn->real_escape_string($tc)."'", $db);
                                $tdRow = DB_fetch_array($tdResult);
                                if ($tdRow) $tcDesc = $tdRow['descrip'];

                                $iSQL = "INSERT INTO cost_allocation_results
                                    (period_id, test_itemcode, test_descrip, cost_component, allocated_amount, basis_value, basis_total, gl_account, gl_account_desc, tests_count)
                                    VALUES ($periodId, '$tc', '".mysqli_real_escape_string($db,$tcDesc)."', '$component', $allocated, $testReagentCost, $totalReagentCost, '$glAccount', '".mysqli_real_escape_string($db,$glDesc)."', $cnt)";
                                DB_query($iSQL, $db);
                                $insertedCount++;
                            }
                        } elseif ($driver == 'labor_days') {
                            foreach ($testCounts as $tc => $cnt) {
                                if ($cnt == 0) continue;
                                $basis = $cnt;
                                if ($totalLaborDays > 0) {
                                    $allocated = ($basis / $totalLaborDays) * $glBalance;
                                } else {
                                    $allocated = 0;
                                }

                                $tdResult2 = DB_query("SELECT descrip FROM stockmaster WHERE itemcode='".mysqli_real_escape_string($db,$tc)."'", $db);
                                $tdRow2 = DB_fetch_array($tdResult2);
                                $tcDesc = $tdRow2 ? $tdRow2['descrip'] : '';

                                $iSQL = "INSERT INTO cost_allocation_results
                                    (period_id, test_itemcode, test_descrip, cost_component, allocated_amount, basis_value, basis_total, gl_account, gl_account_desc, tests_count)
                                    VALUES ($periodId, '$tc', '".mysqli_real_escape_string($db,$tcDesc)."', '$component', $allocated, $basis, $totalLaborDays, '$glAccount', '".mysqli_real_escape_string($db,$glDesc)."', $cnt)";
                                DB_query($iSQL, $db);
                                $insertedCount++;
                            }
                        } elseif ($driver == 'equipment_hours') {
                            foreach ($testCounts as $tc => $cnt) {
                                if ($cnt == 0) continue;
                                $basis = $cnt;
                                if ($totalEquipmentHours > 0) {
                                    $allocated = ($basis / $totalEquipmentHours) * $glBalance;
                                } else {
                                    $allocated = 0;
                                }

                                $tdResult3 = DB_query("SELECT descrip FROM stockmaster WHERE itemcode='".mysqli_real_escape_string($db,$tc)."'", $db);
                                $tdRow3 = DB_fetch_array($tdResult3);
                                $tcDesc = $tdRow3 ? $tdRow3['descrip'] : '';

                                $iSQL = "INSERT INTO cost_allocation_results
                                    (period_id, test_itemcode, test_descrip, cost_component, allocated_amount, basis_value, basis_total, gl_account, gl_account_desc, tests_count)
                                    VALUES ($periodId, '$tc', '".mysqli_real_escape_string($db,$tcDesc)."', '$component', $allocated, $basis, $totalEquipmentHours, '$glAccount', '".mysqli_real_escape_string($db,$glDesc)."', $cnt)";
                                DB_query($iSQL, $db);
                                $insertedCount++;
                            }
                        } elseif ($driver == 'fixed_per_test') {
                            if ($totalTests > 0) {
                                $perTest = $glBalance / $totalTests;
                            } else {
                                $perTest = 0;
                            }
                            foreach ($testCounts as $tc => $cnt) {
                                if ($cnt == 0) continue;
                                $allocated = $perTest * $cnt;

                                $tdResult4 = DB_query("SELECT descrip FROM stockmaster WHERE itemcode='".mysqli_real_escape_string($db,$tc)."'", $db);
                                $tdRow4 = DB_fetch_array($tdResult4);
                                $tcDesc = $tdRow4 ? $tdRow4['descrip'] : '';

                                $iSQL = "INSERT INTO cost_allocation_results
                                    (period_id, test_itemcode, test_descrip, cost_component, allocated_amount, basis_value, basis_total, gl_account, gl_account_desc, tests_count)
                                    VALUES ($periodId, '$tc', '".mysqli_real_escape_string($db,$tcDesc)."', '$component', $allocated, 1, $totalTests, '$glAccount', '".mysqli_real_escape_string($db,$glDesc)."', $cnt)";
                                DB_query($iSQL, $db);
                                $insertedCount++;
                            }
                        }
                    }

                    // Close the period
                    DB_query("UPDATE cost_allocation_periods SET status='closed', closed_at=NOW() WHERE id=$periodId", $db);

                    prnMsg("Allocation completed. $insertedCount cost entries allocated across $totalTests test instances for the period $periodStart to $periodEnd.");
                }
            }
        }
    }

    // Show form
    echo '<div style="margin:4px;padding:20px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;">';
    echo '<h3 style="margin:0 0 16px;color:#1e293b;">Run Cost Allocation</h3>';
    echo '<p style="color:#64748b;margin:0 0 16px;">Allocates GL expenses across lab tests based on configured rules. This reads expenses already recorded in the General Ledger.</p>';
    echo '<form method="POST" action="CostAllocation.php?action=run">';
    echo '<div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;">';
    echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">Period Start</label>';
    echo '<input type="date" name="period_start" required style="padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;"></div>';
    echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">Period End</label>';
    echo '<input type="date" name="period_end" required style="padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;"></div>';
    echo '<button type="submit" name="run_allocation" class="btn btn-primary" style="padding:8px 24px;border:none;border-radius:6px;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;">Run Allocation</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';

/* ============================================================
   ALLOCATION RULES
   ============================================================ */
} elseif ($Action == 'rules') {

    if (isset($_POST['save_rule'])) {
        $id       = isset($_POST['rule_id']) ? $_POST['rule_id'] : 0;
        $glAcc    = $_POST['gl_account'];
        $driver   = $_POST['allocation_driver'];
        $comp     = $_POST['cost_component'];
        $desc     = $_POST['description'];
        $active   = isset($_POST['is_active']) ? 1 : 0;

        if ($id > 0) {
            DB_query("UPDATE cost_allocation_rules
                SET gl_account='$glAcc', allocation_driver='$driver', cost_component='$comp',
                    description='".mysqli_real_escape_string($db,$desc)."', is_active=$active, updated_at=NOW()
                WHERE id=$id", $db);
            prnMsg('Rule updated successfully.');
        } else {
            DB_query("INSERT INTO cost_allocation_rules (gl_account, allocation_driver, cost_component, description, is_active)
                VALUES ('$glAcc','$','$comp','".mysqli_real_escape_string($db,$desc)."',$active)", $db);
            prnMsg('Rule created successfully.');
        }
    }

    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        DB_query("DELETE FROM cost_allocation_rules WHERE id=".intval($_GET['delete']), $db);
        prnMsg('Rule deleted.');
    }

    // Fetch rules
    $rulesResult = DB_query("SELECT car.*, a.accdesc
        FROM cost_allocation_rules car
        JOIN acct a ON a.accno = car.gl_account
        ORDER BY car.cost_component, car.gl_account", $db);

    echo '<div style="margin:4px;padding:20px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;">';
    echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">';
    echo '<h3 style="margin:0;color:#1e293b;">Allocation Rules</h3>';
    echo '<button onclick="document.getElementById(\'ruleForm\').style.display=\'block\';document.getElementById(\'rule_id\').value=\'\';document.getElementById(\'gl_account\').value=\'\';document.getElementById(\'description\').value=\'\';" style="padding:8px 16px;border:none;border-radius:6px;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;">+ Add Rule</button>';
    echo '</div>';

    // Rule form (hidden by default)
    echo '<div id="ruleForm" style="display:none;margin-bottom:20px;padding:16px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">';
    echo '<form method="POST" action="CostAllocation.php?action=rules">';
    echo '<input type="hidden" id="rule_id" name="rule_id" value="">';
    echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:12px;">';
    echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">GL Account</label>';
    echo '<select id="gl_account" name="gl_account" required style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;">';
    echo '<option value="">Select account...</option>';
    $acctResult = DB_query("SELECT accno, accdesc FROM acct WHERE direct=1 AND balance_income=1 ORDER BY ReportCode", $db);
    while ($acctRow = DB_fetch_array($acctResult)) {
        echo '<option value="'.$acctRow['accno'].'">'.$acctRow['accno'].' - '.$acctRow['accdesc'].'</option>';
    }
    echo '</select></div>';

    echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">Allocation Driver</label>';
    echo '<select name="allocation_driver" required style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;">';
    echo '<option value="labor_days">Day Rate</option>';
    echo '<option value="equipment_hours">Equipment Hours</option>';
    echo '<option value="reagent_cost">Reagent Cost</option>';
    echo '<option value="direct">Direct (100% traced)</option>';
    echo '<option value="fixed_per_test">Fixed Per Test</option>';
    echo '</select></div>';

    echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">Cost Component</label>';
    echo '<select name="cost_component" required style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;">';
    echo '<option value="labor">Labor</option>';
    echo '<option value="equipment">Equipment</option>';
    echo '<option value="overhead">Overhead</option>';
    echo '<option value="material">Material</option>';
    echo '</select></div>';

    echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">Description</label>';
    echo '<input type="text" id="description" name="description" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;"></div>';

    echo '<div><label style="display:block;font-weight:600;font-size:13px;color:#475569;margin-bottom:4px;">Active</label>';
    echo '<input type="checkbox" name="is_active" checked style="margin-top:8px;"></div>';
    echo '</div>';

    echo '<button type="submit" name="save_rule" style="padding:8px 20px;border:none;border-radius:6px;background:#16a34a;color:#fff;font-weight:600;cursor:pointer;">Save Rule</button>';
    echo '</form></div>';

    // Rules table
    echo '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
    echo '<thead><tr style="background:#f1f5f9;border-bottom:2px solid #e2e8f0;">';
    echo '<th style="padding:10px 12px;text-align:left;">GL Account</th>';
    echo '<th style="padding:10px 12px;text-align:left;">Description</th>';
    echo '<th style="padding:10px 12px;text-align:left;">Driver</th>';
    echo '<th style="padding:10px 12px;text-align:left;">Component</th>';
    echo '<th style="padding:10px 12px;text-align:center;">Active</th>';
    echo '<th style="padding:10px 12px;text-align:center;">Actions</th>';
    echo '</tr></thead><tbody>';

    $altRow = false;
    while ($row = DB_fetch_array($rulesResult)) {
        $bg = $altRow ? '#f8fafc' : '#fff';
        echo '<tr style="background:'.$bg.';border-bottom:1px solid #f1f5f9;">';
        echo '<td style="padding:10px 12px;font-family:monospace;">'.$row['gl_account'].'</td>';
        echo '<td style="padding:10px 12px;">'.$row['accdesc'].'</td>';
        echo '<td style="padding:10px 12px;">'.ucwords(str_replace('_',' ',$row['allocation_driver'])).'</td>';
        echo '<td style="padding:10px 12px;">'.ucfirst($row['cost_component']).'</td>';
        echo '<td style="padding:10px 12px;text-align:center;">'.($row['is_active'] ? '<span style="color:#16a34a;">Active</span>' : '<span style="color:#dc2626;">Inactive</span>').'</td>';
        echo '<td style="padding:10px 12px;text-align:center;">';
        echo '<a href="CostAllocation.php?action=rules&delete='.$row['id'].'" onclick="return confirm(\'Delete this rule?\')" style="color:#dc2626;margin-right:8px;">Delete</a>';
        echo '</td></tr>';
        $altRow = !$altRow;
    }
    echo '</tbody></table>';
    echo '</div>';

/* ============================================================
   ALLOCATION PERIODS
   ============================================================ */
} elseif ($Action == 'periods') {

    echo '<div style="margin:4px;padding:20px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;">';
    echo '<h3 style="margin:0 0 16px;color:#1e293b;">Allocation Periods</h3>';

    $periodResult = DB_query("SELECT * FROM cost_allocation_periods ORDER BY period_start DESC", $db);

    echo '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
    echo '<thead><tr style="background:#f1f5f9;border-bottom:2px solid #e2e8f0;">';
    echo '<th style="padding:10px 12px;text-align:left;">Period</th>';
    echo '<th style="padding:10px 12px;text-align:center;">Status</th>';
    echo '<th style="padding:10px 12px;text-align:right;">Tests</th>';
    echo '<th style="padding:10px 12px;text-align:right;">Day Rate Basis</th>';
    echo '<th style="padding:10px 12px;text-align:right;">Equipment Hours</th>';
    echo '<th style="padding:10px 12px;text-align:right;">Reagent Cost</th>';
    echo '<th style="padding:10px 12px;text-align:left;">Created</th>';
    echo '<th style="padding:10px 12px;text-align:center;">Action</th>';
    echo '</tr></thead><tbody>';

    $altRow = false;
    while ($row = DB_fetch_array($periodResult)) {
        $bg = $altRow ? '#f8fafc' : '#fff';
        $statusColor = $row['status']=='closed' ? '#16a34a' : '#2563eb';
        echo '<tr style="background:'.$bg.';border-bottom:1px solid #f1f5f9;">';
        echo '<td style="padding:10px 12px;">'.$row['period_start'].' to '.$row['period_end'].'</td>';
        echo '<td style="padding:10px 12px;text-align:center;"><span style="color:'.$statusColor.';font-weight:600;">'.ucfirst($row['status']).'</span></td>';
        echo '<td style="padding:10px 12px;text-align:right;">'.$row['tests_completed'].'</td>';
        echo '<td style="padding:10px 12px;text-align:right;">'.$row['total_labor_days'].'</td>';
        echo '<td style="padding:10px 12px;text-align:right;">'.$row['total_equipment_hours'].'</td>';
        echo '<td style="padding:10px 12px;text-align:right;">'.number_format($row['total_reagent_cost'],2).'</td>';
        echo '<td style="padding:10px 12px;">'.$row['created_at'].'</td>';
        echo '<td style="padding:10px 12px;text-align:center;">';
        echo '<a href="CostAccountingReport.php?period_id='.$row['id'].'" style="color:#2563eb;">View Report</a>';
        echo '</td></tr>';
        $altRow = !$altRow;
    }
    echo '</tbody></table>';
    echo '</div>';
}

include('includes/footer.inc');
?>
