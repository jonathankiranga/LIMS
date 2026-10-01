<?php
 Global  $ParameterGetarray ;
 
$ParameterGetarray = array();
     
Function SampleName($SampleTypeID){
    Global $db;
    
    $SQL="SELECT `sampletypeName` FROM `SampleTypes` WHERE `sampletypeid`='".$SampleTypeID."'";
    $ResultIndex = DB_query($SQL,$db);
    $return = DB_fetch_row($ResultIndex);
    
    return $return[0];
}

Function GetParameter($Id,$PID){
      Global $db,$ParameterGetarray;
     
      $SQL="SELECT `Parameter`,`Limits_min`,`Limits_max`,`Method`,`Units`,`NoStandardlimit`
      FROM `LaboratoryStandards` WHERE `itemcode`='".$Id."' AND `ParameterID`='".$PID."' LIMIT 1";
      $ResultIndex = DB_query($SQL,$db);
      $return = DB_fetch_row($ResultIndex);
      
     $ParameterGetarray[$Id][$PID]['Parameter']      =$return[0];
     $ParameterGetarray[$Id][$PID]['Limits_min']     =$return[1];
     $ParameterGetarray[$Id][$PID]['Limits_max']     =$return[2];
     $ParameterGetarray[$Id][$PID]['Method']         =$return[3];
     $ParameterGetarray[$Id][$PID]['Units']          =$return[4];
     $ParameterGetarray[$Id][$PID]['NoStandardlimit']=$return[5];
      
    return $return;
}

Function PutLines($lastrow){
  Global $pdf,$XPos,$YPos,$Page_Width,$Right_Margin;
  Global $Left_Margin,$line_height,$firstrowpos ;
    
$pdf->line($Left_Margin,$firstrowpos + $line_height-10,$Left_Margin,$lastrow+10);
$pdf->line(242,$firstrowpos + $line_height,242,$lastrow);
$pdf->line(340,$firstrowpos + $line_height,340,$lastrow);
$pdf->line(450,$firstrowpos + $line_height,450,$lastrow);
$pdf->line(500,$firstrowpos + $line_height,500,$lastrow);

$pdf->line($Page_Width-$Right_Margin,$lastrow+10,$Page_Width-$Right_Margin,$firstrowpos + $line_height-10);

 Return $lastrow-$line_height;
}

Function AdjustLastLine($rowsremaing){
 Global $pdf,$XPos,$YPos,$Page_Width,$Right_Margin,$Bottom_Margin;
 Global $Left_Margin,$line_height,$firstrowpos,$Page_Height,$lastrow ;
     //  $requiredspace =  $totalrows * $line_height+($firstrowpos );
 $requiredspace = ($YPos - ($rowsremaing * $line_height ) ) ;
 $AdjustLastLine = $Bottom_Margin + ($line_height * 13) ;
         
  return  $AdjustLastLine;
}

?>
