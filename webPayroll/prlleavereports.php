<?php
include('includes/session.inc');
$Title = _('Leave Reports & Analytics');

if(isset($_POST['print_pdf'])) {
    include('includes/PDFStarter.php');
    
    $report_type = $_POST['report_type'];
    $year = (int)$_POST['report_year'];
    
    $pdf->addInfo('Title', _('Leave Report'));
    $pdf->addInfo('Subject', 'Leave Management Report - ' . $year);
    
    switch($report_type) {
        case 'summary':
            printLeaveSummaryReport($pdf, $year, $db);
            break;
        case 'liability':
            printLeaveLiabilityReport($pdf, $year, $db);
            break;
        case 'calendar':
            printLeaveCalendarReport($pdf, $year, $db);
            break;
    }
    
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Leave_Report_' . $year . '_' . date('Ymd') . '.pdf');
    $pdf->__destruct();
    exit;
}

include('includes/header.inc');
include('includes/PDFStarter.php');

function printLeaveSummaryReport($pdf, $year, $db) {
    global $Left_Margin, $Right_Margin, $Top_Margin, $Bottom_Margin, $PageWidth, $YPos;
    
    $pdf->addInfo('Title', _('Leave Summary Report'));
    
    $sql = "SELECT 
        lt.leavetype_name,
        lt.color_code,
        COUNT(DISTINCT lb.pfno) as employees,
        SUM(lb.entitled_days) as total_entitled,
        SUM(lb.carried_over_days) as total_carried,
        SUM(lb.used_days) as total_used,
        SUM(lb.pending_days) as total_pending,
        SUM(lb.available_days) as total_available
        FROM prlleavebalances lb
        INNER JOIN prlleavetypes lt ON lb.leavetype_id = lt.leavetype_id
        WHERE lb.year = $year AND lt.active = 1
        GROUP BY lb.leavetype_id, lt.leavetype_name
        ORDER BY lt.sort_order";
    $result = DB_query($sql, $db);
    
    $YPos -= 20;
    $pdf->addTextWrap($Left_Margin, $YPos, $PageWidth, 12, 'LEAVE SUMMARY REPORT - ' . $year, 'center');
    $YPos -= 15;
    $pdf->addTextWrap($Left_Margin, $YPos, $PageWidth, 10, 'Generated: ' . date('d/m/Y H:i'), 'center');
    $YPos -= 25;
    
    $headers = ['Leave Type', 'Employees', 'Entitled', 'Carried', 'Used', 'Pending', 'Available'];
    $col_widths = [70, 30, 40, 40, 40, 40, 50];
    
    $pdf->setFillColor(0, 123, 255);
    $pdf->setTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8);
    
    foreach($headers as $i => $header) {
        $pdf->addText($Left_Margin + array_sum(array_slice($col_widths, 0, $i)), $YPos, 8, $header);
    }
    $YPos -= 12;
    
    $pdf->setTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', '', 8);
    
    while($row = DB_fetch_array($result)) {
        if($YPos < $Bottom_Margin + 20) {
            $pdf->newPage();
            $YPos = $Top_Margin;
        }
        
        $pdf->addText($Left_Margin, $YPos, 8, substr($row['leavetype_name'], 0, 15));
        $pdf->addText($Left_Margin + 70, $YPos, 8, $row['employees']);
        $pdf->addText($Left_Margin + 100, $YPos, 8, number_format($row['total_entitled'], 1));
        $pdf->addText($Left_Margin + 140, $YPos, 8, number_format($row['total_carried'], 1));
        $pdf->addText($Left_Margin + 180, $YPos, 8, number_format($row['total_used'], 1));
        $pdf->addText($Left_Margin + 220, $YPos, 8, number_format($row['total_pending'], 1));
        $pdf->addText($Left_Margin + 260, $YPos, 8, number_format($row['total_available'], 1));
        
        $YPos -= 10;
    }
}

function printLeaveLiabilityReport($pdf, $year, $db) {
    global $Left_Margin, $Right_Margin, $Top_Margin, $Bottom_Margin, $PageWidth, $YPos;
    
    $pdf->addInfo('Title', _('Leave Liability Report'));
    
    $sql = "SELECT e.pf_no, CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name,
            e.basicpay, d.name as dept_name,
            SUM(lb.available_days) as total_available,
            (SUM(lb.available_days) * (e.basicpay / 30)) as liability_value
            FROM prlemployeemaster e
            LEFT JOIN prldepartments d ON e.department = d.code
            LEFT JOIN prlleavebalances lb ON e.pf_no = lb.pfno AND lb.year = $year
            WHERE (e.dateterminated IS NULL OR e.dateterminated > CURDATE())
            AND e.Inactive = 0
            GROUP BY e.pf_no, e.basicpay, d.name
            HAVING total_available > 0
            ORDER BY d.name, emp_name";
    $result = DB_query($sql, $db);
    
    $YPos -= 20;
    $pdf->addTextWrap($Left_Margin, $YPos, $PageWidth, 12, 'LEAVE LIABILITY REPORT - ' . $year, 'center');
    $YPos -= 15;
    $pdf->addTextWrap($Left_Margin, $YPos, $PageWidth, 10, 'As of: ' . date('d/m/Y'), 'center');
    $YPos -= 25;
    
    $headers = ['PF No', 'Employee', 'Department', 'Days', 'Daily Rate', 'Liability (KES)'];
    $pdf->setFillColor(0, 123, 255);
    $pdf->setTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8);
    
    foreach($headers as $i => $header) {
        $pdf->addText($Left_Margin + ($i * 60), $YPos, 8, $header);
    }
    $YPos -= 12;
    
    $pdf->setTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', '', 8);
    $total_liability = 0;
    
    while($row = DB_fetch_array($result)) {
        if($YPos < $Bottom_Margin + 20) {
            $pdf->newPage();
            $YPos = $Top_Margin;
        }
        
        $pdf->addText($Left_Margin, $YPos, 8, $row['pf_no']);
        $pdf->addText($Left_Margin + 50, $YPos, 8, substr($row['emp_name'], 0, 20));
        $pdf->addText($Left_Margin + 150, $YPos, 8, substr($row['dept_name'] ?: '-', 0, 15));
        $pdf->addText($Left_Margin + 210, $YPos, 8, number_format($row['total_available'], 1));
        $pdf->addText($Left_Margin + 250, $YPos, 8, number_format($row['basicpay'] / 30, 2));
        $pdf->addText($Left_Margin + 300, $YPos, 8, number_format($row['liability_value'], 2));
        
        $total_liability += $row['liability_value'];
        $YPos -= 10;
    }
    
    $YPos -= 10;
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->addText($Left_Margin + 200, $YPos, 10, 'TOTAL LIABILITY:');
    $pdf->addText($Left_Margin + 300, $YPos, 10, number_format($total_liability, 2) . ' KES');
}

function printLeaveCalendarReport($pdf, $year, $db) {
    global $Left_Margin, $Right_Margin, $Top_Margin, $Bottom_Margin, $PageWidth, $YPos;
    
    $sql = "SELECT la.*, 
            CONCAT(e.fname, ' ', e.mname, ' ', e.lname) as emp_name,
            lt.leavetype_name
            FROM prlleaveapplications la
            INNER JOIN prlemployeemaster e ON la.pfno = e.pf_no
            INNER JOIN prlleavetypes lt ON la.leavetype_id = lt.leavetype_id
            WHERE YEAR(la.from_date) = $year AND la.status IN ('APPROVED', 'PENDING')
            ORDER BY la.from_date";
    $result = DB_query($sql, $db);
    
    $YPos -= 20;
    $pdf->addTextWrap($Left_Margin, $YPos, $PageWidth, 12, 'LEAVE CALENDAR - ' . $year, 'center');
    $YPos -= 25;
    
    $headers = ['From', 'To', 'Employee', 'Leave Type', 'Days'];
    $pdf->setFillColor(0, 123, 255);
    $pdf->setTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8);
    
    foreach($headers as $i => $header) {
        $pdf->addText($Left_Margin + ($i * 70), $YPos, 8, $header);
    }
    $YPos -= 12;
    
    $pdf->setTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', '', 8);
    
    while($row = DB_fetch_array($result)) {
        if($YPos < $Bottom_Margin + 20) {
            $pdf->newPage();
            $YPos = $Top_Margin;
        }
        
        $pdf->addText($Left_Margin, $YPos, 8, ConvertSQLDate($row['from_date']));
        $pdf->addText($Left_Margin + 70, $YPos, 8, ConvertSQLDate($row['to_date']));
        $pdf->addText($Left_Margin + 140, $YPos, 8, substr($row['emp_name'], 0, 25));
        $pdf->addText($Left_Margin + 280, $YPos, 8, substr($row['leavetype_name'], 0, 15));
        $pdf->addText($Left_Margin + 350, $YPos, 8, $row['applied_days']);
        
        $YPos -= 10;
    }
}

if(!isset($_POST['report_year'])) {
    $_POST['report_year'] = date('Y');
}

echo '<div class="container-fluid">';
echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' . _('Leave Reports') . '" alt="" /> ' . $Title . '</p>';

echo '<div class="row">';
echo '<div class="col-md-3">';

echo '<div class="card mb-3">';
echo '<div class="card-header bg-primary text-white">';
echo '<h5 class="mb-0">' . _('Generate Report') . '</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<form method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table table-sm">';
echo '<tr><td>' . _('Report Type') . '</td><td>';
echo '<select name="report_type" class="form-control" required>';
echo '<option value="summary">' . _('Leave Summary') . '</option>';
echo '<option value="liability">' . _('Leave Liability') . '</option>';
echo '<option value="calendar">' . _('Leave Calendar') . '</option>';
echo '</select>';
echo '</td></tr>';
echo '<tr><td>' . _('Year') . '</td><td>';
echo '<select name="report_year" class="form-control">';
for($y = date('Y') - 2; $y <= date('Y') + 1; $y++) {
    $selected = ($y == $_POST['report_year']) ? ' selected' : '';
    echo '<option value="' . $y . '"' . $selected . '>' . $y . '</option>';
}
echo '</select>';
echo '</td></tr>';
echo '<tr><td colspan="2"><input type="submit" name="print_pdf" value="' . _('Print PDF') . '" class="btn btn-primary btn-block" /></td></tr>';
echo '</table>';
echo '</form>';
echo '</div></div>';

echo '<div class="list-group">';
echo '<div class="list-group-item bg-light"><strong>' . _('Available Reports') . '</strong></div>';
echo '<a href="#" class="list-group-item list-group-item-action" onclick="document.getElementById(\'report_type\').value=\'summary\'; document.getElementById(\'report_form\').submit();">' . _('Leave Summary') . '<br><small>Overview of all leave types and balances</small></a>';
echo '<a href="#" class="list-group-item list-group-item-action" onclick="document.getElementById(\'report_type\').value=\'liability\'; document.getElementById(\'report_form\').submit();">' . _('Leave Liability') . '<br><small>Financial liability for accrued leave</small></a>';
echo '<a href="#" class="list-group-item list-group-item-action" onclick="document.getElementById(\'report_type\').value=\'calendar\'; document.getElementById(\'report_form\').submit();">' . _('Leave Calendar') . '<br><small>Monthly schedule of approved leave</small></a>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-9">';

$year = (int)$_POST['report_year'];

echo '<h4>' . _('Leave Summary - ') . $year . '</h4>';

$summary_sql = "SELECT 
    lt.leavetype_name,
    lt.color_code,
    lt.leavetype_code,
    COUNT(DISTINCT lb.pfno) as employees,
    SUM(lb.entitled_days) as total_entitled,
    SUM(lb.carried_over_days) as total_carried,
    SUM(lb.used_days) as total_used,
    SUM(lb.pending_days) as total_pending,
    SUM(lb.available_days) as total_available
    FROM prlleavebalances lb
    INNER JOIN prlleavetypes lt ON lb.leavetype_id = lt.leavetype_id
    WHERE lb.year = $year AND lt.active = 1
    GROUP BY lb.leavetype_id, lt.leavetype_name, lt.color_code, lt.leavetype_code
    ORDER BY lt.sort_order";
$summary_result = DB_query($summary_sql, $db);

echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('Leave Type') . '</th>';
echo '<th class="text-center">' . _('Employees') . '</th>';
echo '<th class="text-right">' . _('Entitled') . '</th>';
echo '<th class="text-right">' . _('Carried') . '</th>';
echo '<th class="text-right">' . _('Used') . '</th>';
echo '<th class="text-right">' . _('Pending') . '</th>';
echo '<th class="text-right">' . _('Available') . '</th>';
echo '<th class="text-center">' . _('Chart') . '</th>';
echo '</tr></thead><tbody>';

$chart_data = [];
while($row = DB_fetch_array($summary_result)) {
    $chart_data[] = [
        'name' => $row['leavetype_name'],
        'color' => $row['color_code'],
        'used' => $row['total_used'],
        'available' => $row['total_available']
    ];
    
    echo '<tr style="border-left: 4px solid ' . $row['color_code'] . ';">';
    echo '<td><strong>' . $row['leavetype_name'] . '</strong></td>';
    echo '<td class="text-center">' . $row['employees'] . '</td>';
    echo '<td class="text-right">' . number_format($row['total_entitled'], 1) . '</td>';
    echo '<td class="text-right">' . number_format($row['total_carried'], 1) . '</td>';
    echo '<td class="text-right">' . number_format($row['total_used'], 1) . '</td>';
    echo '<td class="text-right">' . number_format($row['total_pending'], 1) . '</td>';
    echo '<td class="text-right"><strong>' . number_format($row['total_available'], 1) . '</strong></td>';
    echo '<td class="text-center">';
    $total = $row['entitled_days'] + $row['carried_over_days'];
    if($total > 0) {
        $used_pct = ($row['used_days'] / $total) * 100;
        $avail_pct = ($row['available_days'] / $total) * 100;
        echo '<div class="progress" style="width:100px;height:15px;">';
        echo '<div class="progress-bar bg-success" style="width:' . $used_pct . '%"></div>';
        echo '<div class="progress-bar bg-info" style="width:' . $avail_pct . '%"></div>';
        echo '</div>';
    }
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo '</div>';

echo '<hr/><h4>' . _('Leave Liability Summary - ') . $year . '</h4>';

$liability_sql = "SELECT 
    SUM(lb.available_days * (e.basicpay / 30)) as total_liability,
    SUM(lb.available_days) as total_days,
    COUNT(DISTINCT lb.pfno) as employees
    FROM prlleavebalances lb
    INNER JOIN prlemployeemaster e ON lb.pfno = e.pf_no
    WHERE lb.year = $year AND lb.available_days > 0
    AND (e.dateterminated IS NULL OR e.dateterminated > CURDATE())
    AND e.Inactive = 0";
$liability_result = DB_query($liability_sql, $db);
$liability = DB_fetch_array($liability_result);

echo '<div class="row">';
echo '<div class="col-md-4">';
echo '<div class="card text-white bg-primary mb-3">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Total Liability</h5>';
echo '<h2>KES ' . number_format($liability['total_liability'] ?: 0, 2) . '</h2>';
echo '<p class="card-text">Based on basic pay / 30</p>';
echo '</div></div>';
echo '</div>';
echo '<div class="col-md-4">';
echo '<div class="card text-white bg-success mb-3">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Total Days</h5>';
echo '<h2>' . number_format($liability['total_days'] ?: 0, 1) . '</h2>';
echo '<p class="card-text">Days available across all employees</p>';
echo '</div></div>';
echo '</div>';
echo '<div class="col-md-4">';
echo '<div class="card text-white bg-info mb-3">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Employees</h5>';
echo '<h2>' . ($liability['employees'] ?: 0) . '</h2>';
echo '<p class="card-text">Employees with leave balance</p>';
echo '</div></div>';
echo '</div>';
echo '</div>';

echo '<hr/><h4>' . _('Leave Applications Status - ') . $year . '</h4>';

$status_sql = "SELECT status, COUNT(*) as count, SUM(applied_days) as days
               FROM prlleaveapplications 
               WHERE year = $year
               GROUP BY status";
$status_result = DB_query($status_sql, $db);

echo '<div class="table-responsive">';
echo '<table class="table table-bordered">';
echo '<thead class="thead-dark"><tr>';
echo '<th>' . _('Status') . '</th>';
echo '<th class="text-center">' . _('Applications') . '</th>';
echo '<th class="text-right">' . _('Total Days') . '</th>';
echo '</tr></thead><tbody>';
while($row = DB_fetch_array($status_result)) {
    $badge_class = $row['status'] == 'APPROVED' ? 'success' : ($row['status'] == 'PENDING' ? 'warning' : ($row['status'] == 'REJECTED' ? 'danger' : 'secondary'));
    echo '<tr>';
    echo '<td><span class="badge badge-' . $badge_class . '">' . $row['status'] . '</span></td>';
    echo '<td class="text-center">' . $row['count'] . '</td>';
    echo '<td class="text-right">' . number_format($row['days'], 1) . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo '</div>';

echo '<form id="report_form" method="post" style="display:none;">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="report_type" id="report_type" value="' . ($_POST['report_type'] ?? 'summary') . '" />';
echo '<input type="hidden" name="report_year" value="' . $_POST['report_year'] . '" />';
echo '</form>';

echo '</div></div></div>';

include('includes/footer.inc');
?>
