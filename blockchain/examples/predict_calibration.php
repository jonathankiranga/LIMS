<?php

require '../db_connection.php'; // Ensure this connects to your MySQLi instance

// Fetch deviation data for the last 6 months
$sql = "SELECT 
    ma.machine_name,
    Cs.equipment_id, 
    Cs.sample_name, 
    DATE_FORMAT(Cs.result_timestamp, '%Y-%m') AS month, 
    AVG(Cs.deviation) AS avg_deviation 
FROM 
    ControlSampleResults Cs
JOIN 
    machines ma ON ma.machine_id = Cs.equipment_id
WHERE 
    Cs.result_timestamp >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
GROUP BY 
    Cs.equipment_id, 
    Cs.sample_name, 
    month 
ORDER BY 
    month";

$result = $conn->query($sql);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    die(json_encode(['error' => 'Database query failed']));
}

$conn->close();

$df = [];
foreach ($data as $row) {
    $month = DateTime::createFromFormat('Y-m', $row['month']);
    if (!$month) {
        die(json_encode(['error' => 'Invalid date format in input']));
    }

    $df[] = [
        'machine_name' => $row['machine_name'],
        'equipment_id' => $row['equipment_id'],
        'avg_deviation' => (float)$row['avg_deviation'],
        'month' => $month,
        'month_num' => (int)$month->format('n')
    ];
}

// Predict calibration dates per equipment
// Prepare predictions and historical data
$predictions = [];
$historical_data = [];
foreach (array_group_by($df, 'equipment_id') as $equipment_id => $group) {
    $x = array_column($group, 'month_num');
    $y = array_column($group, 'avg_deviation');
    $machine_name = $group[0]['machine_name'];
// Store historical data
    $historical_data[$equipment_id] = [
        "machine_name" => $machine_name,
        "historical" => array_map(function($entry) {
            return [
                'month' => $entry['month']->format('Y-m'), // Format month
                'avg_deviation' => $entry['avg_deviation']
            ];
        }, $group)
    ];
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
            // Clone the DateTime object
            $month = clone $group[0]['month'];
            // Modify the cloned object by adding ($i + 1) months
            $month->modify('+' . ($i + 1) . ' months');
            // Format the result as 'Y-m'
            $calibration_needed = $month->format('Y-m');
            break;
        }
    }

    $predictions[$equipment_id] = [
        "machine_name" => $machine_name,
        "next_calibration" => $calibration_needed,
        "predicted_deviation" => $predicted_deviation
    ];
}

// Return JSON response
$response = [
    'historical' => $historical_data,
    'predictions' => $predictions
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
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
    if ($n < 2) {
        // Not enough data to perform regression
        return ['slope' => 0, 'intercept' => 0];
    }
    $x_sum = array_sum($x);
    $y_sum = array_sum($y);
    $xy_sum = 0;
    $xx_sum = 0;

    for ($i = 0; $i < $n; $i++) {
        $xy_sum += $x[$i] * $y[$i];
        $xx_sum += $x[$i] * $x[$i];
    }
    $denominator = ($n * $xx_sum - $x_sum * $x_sum);
    
    if ($denominator == 0) {
        // Handle the case where all x values are the same
        // Calculate average of y-values for intercept
        $average_y = $y_sum / $n; // Average y
        return ['slope' => 0, 'intercept' => $average_y];
    }
    $slope = ($n * $xy_sum - $x_sum * $y_sum) / $denominator;
    $intercept = ($y_sum - $slope * $x_sum) / $n;

    return ['slope' => $slope, 'intercept' => $intercept];
}
?>