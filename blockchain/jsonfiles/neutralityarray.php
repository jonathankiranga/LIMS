<?php

$elements = [
        ["id" => "1","ion_name" => "Na⁺", "type" => "Cation", "weight" => 22.99, "charge" => 1],
        ["id" => "2","ion_name" => "K⁺", "type" => "Cation", "weight" => 39.10, "charge" => 1],
        ["id" => "3","ion_name" => "Ca²⁺", "type" => "Cation", "weight" => 40.08, "charge" => 2],
        ["id" => "4","ion_name" => "Mg²⁺","type" => "Cation", "weight" => 24.31, "charge" => 2],
        ["id" => "5","ion_name" => "NH₄⁺", "type" => "Cation", "weight" => 18.04, "charge" => 1],
        ["id" => "6","ion_name" => "Cl⁻", "type" => "Anion",  "weight" => 35.45, "charge" => -1],
        ["id" => "7","ion_name" => "HCO₃⁻", "type" => "Anion",  "weight" => 61.02, "charge" => -1],
        ["id" => "8","ion_name" => "NO₃⁻","type" => "Anion",  "weight" => 62.00, "charge" => -1],
        ["id" => "9","ion_name" => "SO₄²⁻", "type" => "Anion",  "weight" => 96.06, "charge" => -2],
        ["id" => "10","ion_name" => "CO₃²⁻", "type" => "Anion",  "weight" => 60.01, "charge" => -2]
    ];
 

header('Content-Type: application/json');
echo json_encode($elements);
?>