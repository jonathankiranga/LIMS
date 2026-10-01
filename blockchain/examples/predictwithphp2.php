<?php

// Read JSON data from stdin
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die(json_encode(['error' => 'Invalid JSON input']));
}

// Convert to array and ensure correct data types
$df = [];
foreach ($data as $row) {
    $month = DateTime::createFromFormat('Y-m', $row['month']);
    if (!$month) {
        die(json_encode(['error' => 'Invalid date format in input']));
    }

    $df[] = [
        'equipment_id' => $row['equipment_id'],
        'avg_deviation' => (float)$row['avg_deviation'],
        'month' => $month,
        'month_num' => (int)$month->format('n')
    ];
}

// Predict calibration dates per equipment
$predictions = [];
foreach (array_group_by($df, 'equipment_id') as $equipment_id => $group) {
    $x = array_column($group, 'month_num');
    $y = array_column($group, 'avg_deviation');

    // Perform linear regression
    $regression = linear_regression($x, $y);

    // Predict next 3 months
    $max_month_num = max($x);
    $future_months = range($max_month_num + 1, $max_month_num + 3);
    $predicted_deviation = array_map(function($month) use ($regression) {
        return $regression['slope'] * $month + $regression['intercept'];
    }, $future_months);

    // Determine when calibration is needed (threshold deviation > 5%)
    $calibration_needed = null;
    foreach ($predicted_deviation as $i => $deviation) {
        if ($deviation > 5) {
          // Step 1: Clone the DateTime object
            $month = clone $group[0]['month'];
          // Step 2: Modify the cloned object by adding ($i + 1) months
            $month->modify('+' . ($i + 1) . ' months');
          // Step 3: Format the result as 'Y-m'
            $calibration_needed = $month->format('Y-m');
            break;
        }
    }

    $predictions[$equipment_id] = [
        "next_calibration" => $calibration_needed,
        "predicted_deviation" => $predicted_deviation
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($predictions);

// Helper function to group array by a key
function array_group_by(array $array, $key) {
    $result = [];
    foreach ($array as $item) {
        $result[$item[$key]][] = $item;
    }
    return $result;
}

// Helper function to perform linear regression
function linear_regression($x, $y) {
    $n = count($x);
    $x_sum = array_sum($x);
    $y_sum = array_sum($y);
    $xy_sum = 0;
    $xx_sum = 0;

    for ($i = 0; $i < $n; $i++) {
        $xy_sum += $x[$i] * $y[$i];
        $xx_sum += $x[$i] * $x[$i];
    }

    $slope = ($n * $xy_sum - $x_sum * $y_sum) / ($n * $xx_sum - $x_sum * $x_sum);
    $intercept = ($y_sum - $slope * $x_sum) / $n;

    return ['slope' => $slope, 'intercept' => $intercept];
}

?>