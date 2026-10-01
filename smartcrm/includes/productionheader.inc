<?php

if (!isset($RootPath)){
    $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
    if ($RootPath == '/' OR $RootPath == "\\") {
            $RootPath = '';
    }
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
echo '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>' . $Title . '</title> 
  <meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="shortcut icon" href="'. $RootPath.'/comerp.ico" /><link rel="icon" href="' . $RootPath.'/comerp.ico" /> 
  <link href="'.$RootPath.'/javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" />
  <link href="'.$RootPath.'/css/homepage.css" rel="stylesheet" type="text/css"/>
  <link href="'.$RootPath.'/Datatables/datatables.min.css" rel="stylesheet" type="text/css"/>
  <link href="'.$RootPath.'/Datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
  <script type="text/javascript" src = "'.$RootPath.'/javascripts/jquery-3.6.0.min.js"></script>
  <script type="text/javascript" src = "'.$RootPath.'/javascripts/bootstrap.min.js"></script>
  <script type="text/javascript" src = "'.$RootPath.'/javascripts/jquery.tablesorter.pager.js"></script>
  <script type="text/javascript" src = "'.$RootPath.'/Datatables/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src = "'.$RootPath.'/javascripts/MiscFunctions.js"></script> 
  <script type="text/javascript" src = "'.$RootPath.'/javascripts/JQueryProduction.js"></script>
  <script type="text/javascript" src = "'.$RootPath.'/Datatables/datatables.min.js"></script>
  <script type="text/javascript" src = "'.$RootPath.'/javascripts/nicEdit-latest.js"></script>
     ';
   echo '</head><body><div class="overlay"></div>';
   echo '<input type="hidden" name="Lang" id="Lang" value="'.$Lang.'" /><div style="padding:0;float:left;">';

?>
