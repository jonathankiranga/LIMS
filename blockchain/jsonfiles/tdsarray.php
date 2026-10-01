<?php

$elements = [
    ["id" => "1",'Element' => 'Hydrogen', 'Symbol' => 'H', 'AtomicWeight' => 1.008],
    ["id" => "2",'Element' => 'Oxygen', 'Symbol' => 'O', 'AtomicWeight' => 16.00],
    ["id" => "3",'Element' => 'Nitrogen', 'Symbol' => 'N', 'AtomicWeight' => 14.01],
    ["id" => "4",'Element' => 'Carbon', 'Symbol' => 'C', 'AtomicWeight' => 12.01],
    ["id" => "5",'Element' => 'Sodium', 'Symbol' => 'Na', 'AtomicWeight' => 22.99],
    ["id" => "6",'Element' => 'Potassium', 'Symbol' => 'K', 'AtomicWeight' => 39.10],
    ["id" => "7",'Element' => 'Calcium', 'Symbol' => 'Ca', 'AtomicWeight' => 40.08],
    ["id" => "8",'Element' => 'Magnesium', 'Symbol' => 'Mg', 'AtomicWeight' => 24.31],
    ["id" => "9",'Element' => 'Iron', 'Symbol' => 'Fe', 'AtomicWeight' => 55.85],
    ["id" => "10",'Element' => 'Copper', 'Symbol' => 'Cu', 'AtomicWeight' => 63.55],
    ["id" => "11",'Element' => 'Lead', 'Symbol' => 'Pb', 'AtomicWeight' => 207.2],
    ["id" => "12",'Element' => 'Zinc', 'Symbol' => 'Zn', 'AtomicWeight' => 65.38],
    ["id" => "13",'Element' => 'Manganese', 'Symbol' => 'Mn', 'AtomicWeight' => 54.94],
    ["id" => "14",'Element' => 'Chlorine', 'Symbol' => 'Cl', 'AtomicWeight' => 35.45],
    ["id" => "15",'Element' => 'Fluorine', 'Symbol' => 'F', 'AtomicWeight' => 19.00],
    ["id" => "16",'Element' => 'Boron', 'Symbol' => 'B', 'AtomicWeight' => 10.81],
    ["id" => "17",'Element' => 'Sulfur', 'Symbol' => 'S', 'AtomicWeight' => 32.07],
    ["id" => "18",'Element' => 'Phosphorus', 'Symbol' => 'P', 'AtomicWeight' => 30.97],
];
 

header('Content-Type: application/json');
echo json_encode($elements);
?>