<?php

require_once '../db_connection.php'; 
require_once 'getrefferencesfunction.inc'; 

function generateSampleTable($numberOfSamples) {
    $sampleTable = '<table class="table table-sm">';
    for ($index = 1; $index <= $numberOfSamples; $index++) {
        $_POST['internalsamples'][$index] = GetTempBarcoderfNo(19,$index);
        $Y = $_POST['internalsamples'][$index];

        $sampleTable .='<div class="file-upload">
              <label for="sampleFile['.$index.']">Sample '.$index.' Photo:</label><i class="fas fa-upload"></i>
              <input type="file" id="sampleFile_'.$index.'" name="sampleFile['.$index.']" accept="image/*" />
              </div>
             ';
                
        $sampleTable .= sprintf('<tr>'
            . '<td><div class="containernew">Sample %d:<input type="text" class="form-control-sm" name="internalsamples[%d]" value="%s" readonly="readonly"/></div></td>'
            . '<td><div class="containernew">SKU:<input type="text" class="form-control-sm" name="SKU[%s]" value="%s" maxlength="5"/></div></td>'
            . '<td><div class="containernew">Batch No:<input type="text"  class="form-control-sm" name="Batchno[%s]" value="%s" maxlength="20"/></div></td>'
            . '</tr><tr><td><div class="containernew">Batch Size:<input type="number" class="form-control-sm" name="Batchsize[%s]" value="%s" maxlength="5" /></div></td>'
            , $index, $index, $_POST['internalsamples'][$index], $index, $_POST['SKU'][$index], $index, $_POST['Batchno'][$index], $index, $_POST['Batchsize'][$index]);

        
        $sampleTable .= sprintf('<td><div class="containernew">ManuF Date:<input type="date" class="form-control-sm"  name="MANDATE[%s]" value="%s" size="11" maxlength="11" /></div></td>'
            . '<td><div class="containernew">Expire Date:<input type="date" class="form-control-sm"  name="EXPDATE[%s]" value="%s" size="11" maxlength="11"  /></div></td></tr><tr>'
            . '<td><div class="containernew">Sample Source:<input type="text" class="form-control-sm" name="externalsamples[%s]" value="%s" maxlength="30" /></div></td>',
                $index, $_POST['MANDATE'][$index],  $index, $_POST['EXPDATE'][$index],  $index,$_POST['externalsamples'][$index]);
        
        $sampleTable .= ShowTestType($Y);

        $sampleTable .= sprintf('<tr><td colspan="3"><div id="FLEX_%s"></div></td></tr>', $Y);
    }
    $sampleTable .= '</table>';
    
     
    return $sampleTable;
}
    
function ShowTestType($ID) {
    $arrayPricing = [0 => '', 1 => 'Auto Select', 2 => 'Manual Selection'];
    //handleParametersInput
    $object = '<td><div class="containernew">Sample Standards:<input type="hidden" name="sampletype[' . $ID . ']" id="SAM_' . $ID . '" value="' . $_POST['sampletype'][$ID] . '">'
        . '<input type="text" class="form-control-sm"  name="samplename[' . $ID . ']" value="' . $_POST['samplename'][$ID] . '" id="PAR_' . $ID . '" placeholder="Search sample standards" '
            . ' onkeyup="handleParametersInput(event)"></div></td>';
    $object .= '<td><div class="containernew">Select Parameters:<select name="Pricing[' . $ID . ']" class="Pricing form-control-sm" id="PRC_' . $ID . '"   onchange="handlePricingInput(this.value, this.id)">';
    foreach ($arrayPricing as $rowData => $rowValue) {
        $object .= sprintf('<option value="%s" >%s</option>', $rowData, $rowValue);
    }
    $object .= '</select></div></td></tr>';
    return $object;
}


if (isset($_POST['numberofsamples'])) {
    $numberOfSamples = $_POST['numberofsamples'];  // Get the value of numberofsamples
    echo  generateSampleTable($numberOfSamples);
}
        