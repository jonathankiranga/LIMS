<?php
     if (!isset($RootPath)){
                $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
                if ($RootPath == '/' OR $RootPath == "\\") {
                        $RootPath = '';
                }
        }

     echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
     echo '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>' . $Title . '</title>
       <meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge">
       <meta name="viewport" content="width=device-width, initial-scale=1"> 
       <link rel="shortcut icon" href="'. $RootPath.'/comerp.ico" /><link rel="icon" href="' . $RootPath.'/comerp.ico" /> 
       <link href="'.$RootPath.'/javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" />
       <link href="'.$RootPath.'/Datatables/jquery.dataTables.css" rel="stylesheet" type="text/css" />
       <link href="'.$RootPath.'/css/homepage.css" rel="stylesheet" type="text/css"/>
       <link href="'.$RootPath.'/css/smart-dialog.css" rel="stylesheet" type="text/css"/>'
     . '<link href="'.$RootPath.'/Datatables/datatables.min.css" rel="stylesheet" type="text/css"/>
       <link href="'.$RootPath.'/Datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
       <link href="'.$RootPath.'/javascripts/dist/css/tabulator.min.css" rel="stylesheet" type="text/css"/>
       <script type="text/javascript" src = "'.$RootPath.'/javascripts/3.5.1/jquery-3.5.1.js"></script>'
     . '<script type="text/javascript" src = "'.$RootPath.'/javascripts/jQueryUI-1.12.1/jquery-ui.min.js"></script>'
     . '<script type="text/javascript" src = "'.$RootPath.'/javascripts/bootstrap.min.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/javascripts/jquery.tablesorter.pager.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/javascripts/SmartDialog.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/javascripts/nicEdit-latest.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/javascripts/MiscFunctions.js?v=' . filemtime(dirname(__DIR__) . '/javascripts/MiscFunctions.js') . '"></script> 
        <script type="text/javascript" src = "'.$RootPath.'/javascripts/JQueryclases.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/Datatables/datatables.min.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/javascripts/dist/js/tabulator.min.js"></script>';
     ?>
   <style>

  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
  }

  .modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
  }

  .close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .close:hover,
  .close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
  }

  .accept {
    color:#21ba45 ;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .accept:hover,
  .accept:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
  }

  .image-item {
    display: inline-block;
    margin: 10px;
    cursor: pointer;
  }

  .image-item.selected {
    border: 5px solid blue;
  }
</style>  
     <?php
        echo '</meta></meta></meta></head><body><div class="overlay"></div>';
        echo '<input type="hidden" name="Lang" id="Lang" value="'.$Lang.'" /><div style="padding:0;float:left;">';

?>
