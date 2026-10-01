<?php
/**
 * CostAllocationEngine
 *
 * Core logic for allocating GL expenses across lab tests.
 * Reads from: Generalledger, acct, fixedassets,
 *             stockmaster, test_labor_mapping, test_equipment_mapping
 * Reads test counts from: salesline + salesheader (documenttype=1)
 * Writes to: cost_allocation_periods, cost_allocation_results
 */
class CostAllocationEngine {

    private $db;
    private $errors = array();

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get active allocation rules
     */
    public function getRules() {
        $rules = array();
        $result = DB_query("SELECT car.*, a.accdesc
            FROM cost_allocation_rules car
            JOIN acct a ON a.accno = car.gl_account
            WHERE car.is_active = 1
            ORDER BY car.cost_component, car.gl_account", $this->db);
        while ($row = DB_fetch_array($result)) {
            $rules[] = $row;
        }
        return $rules;
    }

    /**
     * Get test counts from Sales Orders (documenttype=1, released=1)
     */
    public function getTestCounts($dateFrom, $dateTo) {
        $counts = array();
        $df = mysqli_real_escape_string($this->db, $dateFrom);
        $dt = mysqli_real_escape_string($this->db, $dateTo);

        $result = DB_query("SELECT sl.code AS itemcode, SUM(sl.Quantity) AS cnt
            FROM salesline sl
            JOIN salesheader sh ON sh.documentno = sl.documentno AND sh.documenttype = sl.documenttype
            WHERE sl.documenttype = 1
            AND sh.released = 1
            AND sh.docdate BETWEEN '$df' AND DATE_ADD('$dt', INTERVAL 1 DAY)
            AND sl.code IS NOT NULL AND sl.code != ''
            GROUP BY sl.code", $this->db);
        while ($row = DB_fetch_array($result)) {
            $counts[$row['itemcode']] = intval($row['cnt']);
        }
        return $counts;
    }

    /**
     * Get GL balance for an account in a date range
     */
    public function getGLBalance($glAccount, $dateFrom, $dateTo) {
        $ga = mysqli_real_escape_string($this->db, $glAccount);
        $df = mysqli_real_escape_string($this->db, $dateFrom);
        $dt = mysqli_real_escape_string($this->db, $dateTo);

        $result = DB_query("SELECT COALESCE(SUM(amount),0) as balance
            FROM Generalledger
            WHERE accountcode = '$ga'
            AND Docdate BETWEEN '$df' AND DATE_ADD('$dt', INTERVAL 1 DAY)", $this->db);
        $row = DB_fetch_array($result);
        return floatval($row['balance']);
    }

    /**
     * Get account description
     */
    public function getAccountDesc($glAccount) {
        $ga = mysqli_real_escape_string($this->db, $glAccount);
        $result = DB_query("SELECT accdesc FROM acct WHERE accno='$ga'", $this->db);
        $row = DB_fetch_array($result);
        return $row ? $row['accdesc'] : '';
    }

    /**
     * Get test description
     */
    public function getTestDesc($itemcode) {
        $ic = mysqli_real_escape_string($this->db, $itemcode);
        $result = DB_query("SELECT descrip FROM stockmaster WHERE itemcode='$ic'", $this->db);
        $row = DB_fetch_array($result);
        return $row ? $row['descrip'] : '';
    }

    /**
     * Run the allocation for a given period
     *
     * @param string $dateFrom  Start date (Y-m-d)
     * @param string $dateTo    End date (Y-m-d)
     * @return array  Result with success, period_id, summary
     */
    public function runAllocation($dateFrom, $dateTo) {
        $rules = $this->getRules();
        if (empty($rules)) {
            return array('success' => false, 'message' => 'No active allocation rules');
        }

        $testCounts = $this->getTestCounts($dateFrom, $dateTo);
        $totalTests = array_sum($testCounts);
        if ($totalTests == 0) {
            return array('success' => false, 'message' => 'No completed tests found in this period');
        }

        // Get reagent mapping — total quantity per test
        $reagentMap = array();
        $rrResult = DB_query("SELECT test_itemcode, SUM(quantity) as total_qty FROM test_reagent_mapping GROUP BY test_itemcode", $this->db);
        while ($rrRow = DB_fetch_array($rrResult)) {
            $reagentMap[$rrRow['test_itemcode']] = $rrRow['total_qty'];
        }

        // Total reagent cost basis — AVG(averagestock) × qty per test × test count
        $totalReagentCost = 0;
        foreach ($testCounts as $tc => $cnt) {
            if (!isset($reagentMap[$tc])) continue;
            $rc = DB_query("SELECT COALESCE(AVG(sm.averagestock),0) as avg_cost
                FROM test_reagent_mapping trm
                JOIN stockmaster sm ON sm.itemcode = trm.reagent_itemcode
                WHERE trm.test_itemcode = '" . mysqli_real_escape_string($this->db, $tc) . "'", $this->db);
            $rcRow = DB_fetch_array($rc);
            $totalReagentCost += $rcRow['avg_cost'] * $reagentMap[$tc] * $cnt;
        }

        $totalLaborDays = $totalTests;
        $totalEquipmentHours = $totalTests;

        // Create period record
        $df = mysqli_real_escape_string($this->db, $dateFrom);
        $dt = mysqli_real_escape_string($this->db, $dateTo);
        DB_query("INSERT INTO cost_allocation_periods
            (period_start, period_end, status, total_labor_days, total_equipment_hours, total_reagent_cost, tests_completed)
            VALUES ('$df', '$dt', 'open', $totalLaborDays, $totalEquipmentHours, $totalReagentCost, $totalTests)", $this->db);
        $periodId = mysqli_insert_id($this->db);

        $insertedCount = 0;
        $totalMaterial = 0;
        $totalLabor = 0;
        $totalEquipment = 0;
        $totalOverhead = 0;

        foreach ($rules as $rule) {
            $glAccount = $rule['gl_account'];
            $driver    = $rule['allocation_driver'];
            $component = $rule['cost_component'];

            $glBalance = $this->getGLBalance($glAccount, $dateFrom, $dateTo);
            if ($glBalance <= 0) continue;

            $glDesc = $this->getAccountDesc($glAccount);

            foreach ($testCounts as $tc => $cnt) {
                if ($cnt == 0) continue;

                $basis = 0;
                $basisTotal = 0;

                switch ($driver) {
                    case 'labor_days':
                        $basis = $cnt;
                        $basisTotal = $totalLaborDays;
                        break;

                    case 'equipment_hours':
                        $basis = $cnt;
                        $basisTotal = $totalEquipmentHours;
                        break;

                    case 'reagent_cost':
                    case 'direct':
                        if (!isset($reagentMap[$tc])) { continue 2; }
                        $rc = DB_query("SELECT COALESCE(AVG(sm.averagestock),0) as avg_cost
                            FROM test_reagent_mapping trm
                            JOIN stockmaster sm ON sm.itemcode = trm.reagent_itemcode
                            WHERE trm.test_itemcode = '" . mysqli_real_escape_string($this->db, $tc) . "'", $this->db);
                        $rcRow = DB_fetch_array($rc);
                        $basis = $rcRow['avg_cost'] * $reagentMap[$tc] * $cnt;
                        $basisTotal = $totalReagentCost;
                        break;

                    case 'fixed_per_test':
                        $basis = $cnt;
                        $basisTotal = $totalTests;
                        break;

                    default:
                        continue 2;
                }

                $allocated = ($basisTotal > 0) ? ($basis / $basisTotal) * $glBalance : 0;
                $tcDesc = $this->getTestDesc($tc);

                $itc = mysqli_real_escape_string($this->db, $tc);
                $itd = mysqli_real_escape_string($this->db, $tcDesc);
                $igd = mysqli_real_escape_string($this->db, $glDesc);

                DB_query("INSERT INTO cost_allocation_results
                    (period_id, test_itemcode, test_descrip, cost_component, allocated_amount,
                     basis_value, basis_total, gl_account, gl_account_desc, tests_count)
                    VALUES ($periodId, '$itc', '$itd', '$component', $allocated,
                     $basis, $basisTotal, '$glAccount', '$igd', $cnt)", $this->db);
                $insertedCount++;

                switch ($component) {
                    case 'material':    $totalMaterial += $allocated; break;
                    case 'labor':       $totalLabor += $allocated; break;
                    case 'equipment':   $totalEquipment += $allocated; break;
                    case 'overhead':    $totalOverhead += $allocated; break;
                }
            }
        }

        // Close the period
        DB_query("UPDATE cost_allocation_periods SET status='closed', closed_at=NOW() WHERE id=$periodId", $this->db);

        return array(
            'success' => true,
            'period_id' => $periodId,
            'tests_count' => $totalTests,
            'entries_created' => $insertedCount,
            'summary' => array(
                'total_material'  => round($totalMaterial, 2),
                'total_labor'     => round($totalLabor, 2),
                'total_equipment' => round($totalEquipment, 2),
                'total_overhead'  => round($totalOverhead, 2),
                'grand_total'     => round($totalMaterial + $totalLabor + $totalEquipment + $totalOverhead, 2)
            )
        );
    }

    /**
     * Get allocation results for a period or date range
     */
    public function getResults($periodId = 0, $dateFrom = '', $dateTo = '') {
        $sql = "SELECT
            car.test_itemcode,
            sm.descrip as test_name,
            car.cost_component,
            SUM(car.allocated_amount) as amount,
            SUM(car.tests_count) as tests_count,
            GROUP_CONCAT(DISTINCT car.gl_account ORDER BY car.gl_account SEPARATOR ', ') as gl_accounts
        FROM cost_allocation_results car
        JOIN stockmaster sm ON sm.itemcode = car.test_itemcode";

        if ($periodId > 0) {
            $sql .= " WHERE car.period_id = " . intval($periodId);
        } elseif (!empty($dateFrom) && !empty($dateTo)) {
            $sql .= " JOIN cost_allocation_periods cap ON cap.id = car.period_id
                      WHERE cap.period_start >= '" . mysqli_real_escape_string($this->db, $dateFrom) . "'
                      AND cap.period_end <= '" . mysqli_real_escape_string($this->db, $dateTo) . "'";
        }

        $sql .= " GROUP BY car.test_itemcode, sm.descrip, car.cost_component
                  ORDER BY sm.descrip, car.cost_component";

        $result = DB_query($sql, $this->db);

        $tests = array();
        while ($row = DB_fetch_array($result)) {
            $code = $row['test_itemcode'];
            if (!isset($tests[$code])) {
                $tests[$code] = array(
                    'test_name' => $row['test_name'],
                    'material' => 0, 'labor' => 0, 'equipment' => 0, 'overhead' => 0,
                    'tests_count' => 0, 'gl_accounts' => array()
                );
            }
            $amt = floatval($row['amount']);
            $tests[$code][$row['cost_component']] += $amt;
            $tests[$code]['tests_count'] = max($tests[$code]['tests_count'], intval($row['tests_count']));
            if (!empty($row['gl_accounts'])) {
                $glArr = array_map('trim', explode(',', $row['gl_accounts']));
                $tests[$code]['gl_accounts'] = array_unique(array_merge($tests[$code]['gl_accounts'], $glArr));
            }
        }

        $rows = array();
        foreach ($tests as $code => $t) {
            $total = $t['material'] + $t['labor'] + $t['equipment'] + $t['overhead'];
            $cpt = $t['tests_count'] > 0 ? $total / $t['tests_count'] : 0;
            $rows[] = array(
                'test_name'     => $t['test_name'],
                'tests_count'   => $t['tests_count'],
                'material'      => round($t['material'], 2),
                'labor'         => round($t['labor'], 2),
                'equipment'     => round($t['equipment'], 2),
                'overhead'      => round($t['overhead'], 2),
                'total'         => round($total, 2),
                'cost_per_test' => round($cpt, 2),
                'gl_accounts'   => implode(', ', $t['gl_accounts'])
            );
        }

        usort($rows, function($a, $b) { return $b['total'] <=> $a['total']; });

        $summary = array(
            'total_tests'     => 0,
            'total_material'  => 0,
            'total_labor'     => 0,
            'total_equipment' => 0,
            'total_overhead'  => 0,
            'grand_total'     => 0
        );
        foreach ($rows as $r) {
            $summary['total_tests'] += $r['tests_count'];
            $summary['total_material'] += $r['material'];
            $summary['total_labor'] += $r['labor'];
            $summary['total_equipment'] += $r['equipment'];
            $summary['total_overhead'] += $r['overhead'];
        }
        $summary['grand_total'] = $summary['total_material'] + $summary['total_labor'] + $summary['total_equipment'] + $summary['total_overhead'];

        return array('rows' => $rows, 'summary' => $summary);
    }

    /**
     * Get any errors from the last operation
     */
    public function getErrors() {
        return $this->errors;
    }
}
