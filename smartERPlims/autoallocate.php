<?php
$PageSecurity=0;

include('includes/session.inc');

if (!isset($RootPath)){
             $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
             if ($RootPath == '/' OR $RootPath == "\\") {
                     $RootPath = '';
             }
     }

  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
  echo '<html xmlns="http://www.w3.org/1999/xhtml"><head>
    <meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1"> 
     <script type="text/javascript" src = "'.$RootPath.'/javascripts/3.5.1/jquery-3.5.1.js"></script>'
  . '<script type="text/javascript" src = "'.$RootPath.'/javascripts/jQueryUI-1.12.1/jquery-ui.min.js"></script>'
  . '<script type="text/javascript" src = "'.$RootPath.'/javascripts/SmartDialog.js"></script>'
  . '</meta></meta></meta></head>
      <script type="text/javascript">
        $(document).ready(
             function() {
              $.post("includes/autoallocatevendorsAjax.php",{
                      autoallocateall: "YES"
                    },function(data){
                      SmartDialog.info("Debtors and Creditors alloacation is running", "Information");
                    });
             }
           )
           </script>';  

?>
