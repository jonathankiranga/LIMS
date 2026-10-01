<?php

 $XPos = 46;
 $YPos -= ($line_height);
 
$disclaimer="Precision parameters apply in the determination of these test results. Users of the data shown in this report should refer to the latest published Revision of ASTM D 3244.IP 367 AND ISO 4259 when utilizing the test data to determine conformance with any specification or process requirements";
$LeftOvers = $pdf->addTextWrap(50,$YPos,500,$FontSize,$disclaimer,'left');
While(strlen($LeftOvers) > 0){
     $YPos -= ($line_height);
     $LeftOvers = $pdf->addTextWrap(50,$YPos,500,$FontSize,$LeftOvers,'left');
 }
         
$pdf->addText($Left_Margin,($Bottom_Margin + 50),10,_('Authorized signatory:'));

$pdf->addText($Left_Margin,($Bottom_Margin ),10,$_SESSION['CompanyRecord']['Signaturename_1']);




?>