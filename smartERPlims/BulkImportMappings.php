<?php
include('includes/session.inc');
$Title = _('Bulk Import Mappings');
include('includes/header.inc');

$db = mysqli_connect($host, $DBUser, $DBPassword, $DefaultDatabase);

// Export template
if (isset($_GET['export'])) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="mappings_template.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['testcode', 'testname', 'reagentcode', 'reagentname', 'reagentcost', 'reagentqty', 'directexpenses', 'subtotal', 'markup', 'salesprice']);

  $sql = "SELECT m.test_itemcode,
                 t.descrip AS test_descrip,
                 m.reagent_itemcode,
                 r.descrip AS reagent_descrip,
                 COALESCE(r.averagestock, 0) AS reagent_cost,
                 m.quantity
          FROM test_reagent_mapping m
          JOIN stockmaster t ON t.itemcode = m.test_itemcode
          JOIN stockmaster r ON r.itemcode = m.reagent_itemcode
          ORDER BY t.descrip, r.descrip";
  $result = mysqli_query($db, $sql);

  $currentTest = '';
  $buf = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $tc = trim($row['test_itemcode']);
    if ($tc !== $currentTest && $currentTest !== '') {
      // Flush buffered rows for previous test
      $first = $buf[0];
      foreach ($buf as $b) {
        fputcsv($out, [$currentTest, $first['testname'], $b['rc'], $b['rn'], $b['cost'], $b['qty'], '', '', '', '']);
      }
      $buf = [];
    }
    $currentTest = $tc;
    $buf[] = [
      'testname' => trim($row['test_descrip']),
      'rc' => trim($row['reagent_itemcode']),
      'rn' => trim($row['reagent_descrip']),
      'cost' => (float)$row['reagent_cost'],
      'qty' => (float)$row['quantity']
    ];
  }
  // Flush last test
  if (!empty($buf)) {
    $first = $buf[0];
    foreach ($buf as $b) {
      fputcsv($out, [$currentTest, $first['testname'], $b['rc'], $b['rn'], $b['cost'], $b['qty'], '', '', '', '']);
    }
  }
  fclose($out);
  mysqli_close($db);
  exit;
}

$resultMsg = '';
$resultClass = '';

if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
  $tmpPath = $_FILES['csv_file']['tmp_name'];
  $handle = fopen($tmpPath, 'r');
  if ($handle) {
    $header = fgetcsv($handle);
    if ($header) {
      $header = array_map('trim', $header);

      $colMap = [];
      foreach ($header as $i => $name) {
        $lower = strtolower($name);
        if (in_array($lower, ['testcode', 'test_itemcode', 'test_code'])) $colMap['testcode'] = $i;
        elseif (in_array($lower, ['reagentcode', 'reagent_itemcode', 'reagent_code'])) $colMap['reagentcode'] = $i;
        elseif (in_array($lower, ['reagentcost', 'unit_cost', 'cost'])) $colMap['reagentcost'] = $i;
        elseif (in_array($lower, ['reagentqty', 'qty', 'quantity'])) $colMap['reagentqty'] = $i;
        elseif (in_array($lower, ['directexpenses', 'direct_expenses'])) $colMap['directexpenses'] = $i;
        elseif (in_array($lower, ['markup'])) $colMap['markup'] = $i;
        elseif (in_array($lower, ['subtotal'])) $colMap['subtotal'] = $i;
        elseif (in_array($lower, ['salesprice', 'sales_price', 'sales price'])) $colMap['salesprice'] = $i;
      }

      if (!isset($colMap['testcode']) || !isset($colMap['reagentcode']) || !isset($colMap['reagentqty'])) {
        $resultClass = 'error';
        $resultMsg = _('CSV must have testcode, reagentcode, and reagentqty columns');
        fclose($handle);
      } else {
        // Read all rows into memory, grouped by testcode
        $groups = [];
        while (($row = fgetcsv($handle)) !== false) {
          if (count($row) < count($header)) continue;
          $row = array_map('trim', $row);
          $tc = $row[$colMap['testcode']];
          if (empty($tc)) continue;
          if (!isset($groups[$tc])) $groups[$tc] = ['reagents' => [], 'meta' => []];
          $groups[$tc]['reagents'][] = [
            'testname' => isset($colMap['testcode']) ? ($row[$colMap['testcode']] ?? '') : '',
            'rc' => $row[$colMap['reagentcode']],
            'cost' => isset($colMap['reagentcost']) && isset($row[$colMap['reagentcost']]) && $row[$colMap['reagentcost']] !== '' ? (float)$row[$colMap['reagentcost']] : null,
            'qty' => (float)($row[$colMap['reagentqty']] ?? 0)
          ];
          // Read meta from first row of each group
          if (count($groups[$tc]['reagents']) === 1) {
            $groups[$tc]['meta'] = [
              'directexpenses' => isset($colMap['directexpenses']) && isset($row[$colMap['directexpenses']]) && $row[$colMap['directexpenses']] !== '' ? (float)$row[$colMap['directexpenses']] : 0,
              'markup' => isset($colMap['markup']) && isset($row[$colMap['markup']]) && $row[$colMap['markup']] !== '' ? (float)$row[$colMap['markup']] : 0,
            ];
          }
        }
        fclose($handle);

        if (empty($groups)) {
          $resultClass = 'error';
          $resultMsg = _('No valid data rows found in CSV');
        } else {
          $mappingsInserted = 0;
          $mappingsUpdated = 0;
          $costsUpdated = 0;
          $sheetsSaved = 0;
          $errors = 0;

          mysqli_begin_transaction($db);

          try {
            foreach ($groups as $testCode => $group) {
              $reagents = $group['reagents'];
              $meta = $group['meta'];
              $n = count($reagents);

              // -------------------------------------------------------
              // Phase 1: Upsert mappings + update reagent costs
              // -------------------------------------------------------
              foreach ($reagents as $r) {
                $rc = $r['rc'];
                $qty = $r['qty'];
                $cost = $r['cost'];

                if (empty($rc) || $qty <= 0) {
                  $errors++;
                  continue;
                }

                $check = mysqli_prepare($db, "SELECT id FROM test_reagent_mapping WHERE test_itemcode = ? AND reagent_itemcode = ?");
                mysqli_stmt_bind_param($check, 'ss', $testCode, $rc);
                mysqli_stmt_execute($check);
                mysqli_stmt_store_result($check);
                $exists = mysqli_stmt_num_rows($check) > 0;
                mysqli_stmt_close($check);

                if ($exists) {
                  $upd = mysqli_prepare($db, "UPDATE test_reagent_mapping SET quantity = ? WHERE test_itemcode = ? AND reagent_itemcode = ?");
                  mysqli_stmt_bind_param($upd, 'dss', $qty, $testCode, $rc);
                  mysqli_stmt_execute($upd);
                  mysqli_stmt_close($upd);
                  $mappingsUpdated++;
                } else {
                  $ins = mysqli_prepare($db, "INSERT INTO test_reagent_mapping (test_itemcode, reagent_itemcode, quantity) VALUES (?, ?, ?)");
                  mysqli_stmt_bind_param($ins, 'ssd', $testCode, $rc, $qty);
                  mysqli_stmt_execute($ins);
                  mysqli_stmt_close($ins);
                  $mappingsInserted++;
                }

                if ($cost !== null) {
                  $costUpd = mysqli_prepare($db, "UPDATE stockmaster SET averagestock = ? WHERE itemcode = ? AND isstock_2 = 1");
                  mysqli_stmt_bind_param($costUpd, 'ds', $cost, $rc);
                  mysqli_stmt_execute($costUpd);
                  if (mysqli_stmt_affected_rows($costUpd) > 0) {
                    $costsUpdated++;
                  }
                  mysqli_stmt_close($costUpd);
                }
              }

              // -------------------------------------------------------
              // Phase 2: Build spreadsheet JSON
              // -------------------------------------------------------
              $cells = [];
              $reagentCostArray = [];

              // Row 0: Headers
              $cells[] = ['r' => 0, 'c' => 0, 'v' => 'Reagent', 'bl' => 1];
              $cells[] = ['r' => 0, 'c' => 1, 'v' => 'Qty', 'bl' => 1];
              $cells[] = ['r' => 0, 'c' => 2, 'v' => 'Cost/Unit', 'bl' => 1];
              $cells[] = ['r' => 0, 'c' => 3, 'v' => 'Subtotal', 'bl' => 1];

              // Rows 1..N: Reagent data
              $baseSum = 0;
              for ($i = 0; $i < $n; $i++) {
                $r = $i + 1;
                $cells[] = ['r' => $r, 'c' => 0, 'v' => $reagents[$i]['rc']];
                $cells[] = ['r' => $r, 'c' => 1, 'v' => $reagents[$i]['qty']];
                $costVal = $reagents[$i]['cost'] ?? 0;
                $cells[] = ['r' => $r, 'c' => 2, 'v' => $costVal];
                $subVal = $costVal * $reagents[$i]['qty'];
                $baseSum += $subVal;
                $cells[] = ['r' => $r, 'c' => 3, 'v' => $subVal, 'f' => '=B' . ($r + 1) . '*C' . ($r + 1)];
                $reagentCostArray[] = [$reagents[$i]['rc'], $costVal];
              }

              // Row N+1: Base Cost
              $baseRow = $n + 1;
              $cells[] = ['r' => $baseRow, 'c' => 0, 'v' => 'Base Cost', 'bl' => 1];
              $cells[] = ['r' => $baseRow, 'c' => 3, 'v' => $baseSum, 'f' => '=SUM(D2:D' . ($n + 1) . ')'];

              // Row N+2: empty separator
              $sepRow = $baseRow + 1;
              // leave empty (no cells)

              // Row N+3: Direct Expenses
              $directRow = $sepRow + 1;
              $directVal = $meta['directexpenses'];
              $cells[] = ['r' => $directRow, 'c' => 0, 'v' => 'Direct Expenses'];
              $cells[] = ['r' => $directRow, 'c' => 3, 'v' => $directVal];

              // Row N+4: Subtotal (bold, blue bg, white text) = Base + Direct
              $subtotalRow = $directRow + 1;
              $subtotalVal = $baseSum + $directVal;
              $cells[] = ['r' => $subtotalRow, 'c' => 0, 'v' => 'Subtotal', 'bl' => 1, 'bg' => '#2563eb', 'fc' => '#ffffff'];
              $cells[] = ['r' => $subtotalRow, 'c' => 3, 'v' => $subtotalVal, 'f' => '=D' . ($baseRow + 1) . '+D' . ($directRow + 1), 'bg' => '#dbeafe', 'bl' => 1];

              // Row N+5: Markup %
              $markupRow = $subtotalRow + 1;
              $markupPct = $meta['markup'];
              $cells[] = ['r' => $markupRow, 'c' => 0, 'v' => 'Markup %'];
              $cells[] = ['r' => $markupRow, 'c' => 1, 'v' => $markupPct];

              // Row N+6: Markup Amount = Subtotal * Markup% / 100
              $markupAmtRow = $markupRow + 1;
              $markupAmtVal = $subtotalVal * $markupPct / 100;
              $cells[] = ['r' => $markupAmtRow, 'c' => 0, 'v' => 'Markup Amount'];
              $cells[] = ['r' => $markupAmtRow, 'c' => 3, 'v' => $markupAmtVal, 'f' => '=D' . ($subtotalRow + 1) . '*B' . ($markupRow + 1) . '/100'];

              // Row N+7: Sales Price (bold, blue bg, white text) = Subtotal + Markup Amount
              $salesRow = $markupAmtRow + 1;
              $salesVal = $subtotalVal + $markupAmtVal;
              $cells[] = ['r' => $salesRow, 'c' => 0, 'v' => 'Sales Price', 'bl' => 1, 'bg' => '#2563eb', 'fc' => '#ffffff'];
              $cells[] = ['r' => $salesRow, 'c' => 3, 'v' => $salesVal, 'f' => '=D' . ($subtotalRow + 1) . '+D' . ($markupAmtRow + 1), 'bg' => '#dbeafe', 'bl' => 1];

              // Build full sheet data
              $sheetData = json_encode([
                'calculator' => $cells,
                'reagent_costs' => $reagentCostArray
              ]);

              // Upsert into test_spreadsheet
              $userId = $_SESSION['UserID'] ?? '';
              $stmt = mysqli_prepare($db, "INSERT INTO test_spreadsheet (test_itemcode, sheet_data, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE sheet_data = VALUES(sheet_data), updated_by = VALUES(updated_by), updated_at = NOW()");
              mysqli_stmt_bind_param($stmt, 'sss', $testCode, $sheetData, $userId);
              mysqli_stmt_execute($stmt);
              mysqli_stmt_close($stmt);
              $sheetsSaved++;
            }

            mysqli_commit($db);
            $resultClass = 'info';
            $resultMsg = sprintf(
              _('Mappings: %d inserted, %d updated | Reagent costs updated: %d | Spreadsheets saved: %d | Errors: %d'),
              $mappingsInserted, $mappingsUpdated, $costsUpdated, $sheetsSaved, $errors
            );
          } catch (Exception $e) {
            mysqli_rollback($db);
            $resultClass = 'error';
            $resultMsg = _('Import failed: ') . $e->getMessage();
          }
        }
      }
    } else {
      $resultClass = 'error';
      $resultMsg = _('Empty CSV file');
      fclose($handle);
    }
  } else {
    $resultClass = 'error';
    $resultMsg = _('Cannot read uploaded file');
  }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $resultClass = 'error';
  $resultMsg = _('Please select a CSV file to upload');
}

mysqli_close($db);
?>
<style>
.import-container {
  max-width: 800px;
  margin: 20px auto;
  padding: 24px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  border: 1px solid #e2e8f0;
}
.import-container h2 {
  margin-top: 0;
  color: #1e293b;
  font-size: 20px;
}
.import-container h3 {
  margin: 20px 0 8px;
  color: #1e293b;
  font-size: 15px;
}
.import-container p {
  color: #64748b;
  font-size: 13px;
  line-height: 1.5;
}
.import-container .btn {
  display: inline-block;
  padding: 8px 16px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: none;
}
.import-container .btn-download {
  background: #2563eb;
  color: #fff;
}
.import-container .btn-import {
  background: #16a34a;
  color: #fff;
  margin-top: 8px;
}
.import-container label {
  font-weight: 600;
  font-size: 13px;
  color: #475569;
  display: block;
  margin-bottom: 4px;
}
.import-container input[type="file"] {
  display: block;
  margin-bottom: 8px;
}
.import-container .info-box {
  padding: 12px 16px;
  border-radius: 6px;
  margin-bottom: 16px;
  font-size: 13px;
}
.import-container .info-box.info {
  background: #dbeafe;
  color: #1e40af;
  border: 1px solid #bfdbfe;
}
.import-container .info-box.error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}
.steps {
  margin: 8px 0 16px;
  padding-left: 20px;
}
.steps li {
  margin-bottom: 6px;
  font-size: 13px;
  color: #334155;
}
</style>

<div class="import-container">
  <h2><?php echo _('Bulk Import Test-Reagent Mappings'); ?></h2>

  <p><?php echo _('Upload a CSV with all tests, their reagents, costs, quantities, direct expenses, and markup. Mappings, reagent costs, and full calculator spreadsheets will be generated automatically.'); ?></p>

  <?php if ($resultMsg): ?>
  <div class="info-box <?php echo $resultClass; ?>"><?php echo htmlspecialchars($resultMsg); ?></div>
  <?php endif; ?>

  <h3><?php echo _('Step 1: Download Template'); ?></h3>
  <p><?php echo _('Download the current mappings as a CSV template. Open it in Excel, fill in the missing columns, then re-upload.'); ?></p>
  <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?export=1" class="btn btn-download">&#8595; <?php echo _('Download Template CSV'); ?></a>

  <h3><?php echo _('Step 2: Edit in Excel'); ?></h3>
  <ol class="steps">
    <li><?php echo _('Open the downloaded CSV in Excel'); ?></li>
    <li><?php echo _('Fill in <strong>directexpenses</strong>, <strong>markup</strong> for each test (same value for all rows of that test)'); ?></li>
    <li><?php echo _('Adjust <strong>reagentcost</strong> and <strong>reagentqty</strong> as needed'); ?></li>
    <li><?php echo _('To add new mappings, insert rows with testcode, reagentcode, qty, and costs'); ?></li>
    <li><?php echo _('Save as CSV (File &rarr; Save As &rarr; CSV UTF-8)'); ?></li>
  </ol>

  <h3><?php echo _('Step 3: Upload'); ?></h3>
  <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <label for="csv_file"><?php echo _('Select edited CSV file'); ?></label>
    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
    <input type="submit" value="&#9654; <?php echo _('Import Mappings & Generate Sheets'); ?>" class="btn btn-import">
  </form>
</div>

<?php include('includes/footer.inc'); ?>
