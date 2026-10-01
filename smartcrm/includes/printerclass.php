<?php

//$handle  =  printer_open("Send To OneNote 2007"); ///This Works
/*
$handle = printer_open('\\\\192.168.0.8\\Canon MF4320-4350'); 
printer_set_option($handle, PRINTER_MODE, "RAW");
printer_write($handle, "TEXT To print");
printer_close($handle);
*/

function GetPrinters(){
$getprt = printer_list(PRINTER_ENUM_LOCAL| PRINTER_ENUM_SHARED );
$printers = serialize($getprt);
$printers = unserialize($printers);

echo '<select name="printers">';
    foreach ($printers as $PrintDest){
        
     $PrinterDesc=explode(",",$PrintDest["DESCRIPTION"]) ;
        
     echo "<option value='".$PrintDest["NAME"]."'>".$PrinterDesc[1]."</option>";
    
    }
    echo '</select>';
    
}


?>