<?php

function calculateVCF($T1, $Tb, $alpha) {
    return (1 + $alpha * ($Tb - $T1)) / (1 + $alpha * ($T1 - $Tb));
}

$alpha = 0.0008; // Coefficient of thermal expansion for bitumen
$Tb = 15; // Base temperature

//echo "Temperature (°C) | VCF\n";
//echo "------------------------\n";
$vcfarray=array();
// Calculate and print VCF for each temperature from 20°C to 360°C
for ($T1 = 20; $T1 <= 360; $T1++) {
    $VCF = calculateVCF($T1, $Tb, $alpha);
 //   echo str_pad($T1, 17) . "| " . round($VCF, 4) . "\n";
 //   echo '<br/>';
    $vcfarray[$T1]=round($VCF, 4);
}


?>
