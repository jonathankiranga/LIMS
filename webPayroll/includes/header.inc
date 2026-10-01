<?php
     if (!isset($RootPath)){
		$RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
		if ($RootPath == '/' OR $RootPath == "\\") {
			$RootPath = '';
		}
	}
       
     echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
     echo '<html xmlns="http://www.w3.org/1999/xhtml"> 
       <head><title>' . $Title . '</title> 
       <meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge">
       <meta name="viewport" content="width=device-width, initial-scale=1"> 
       <link rel="shortcut icon" href="'. $RootPath.'/comerp.ico" /> 
       <link rel="icon" href="' . $RootPath.'/comerp.ico" /> 
       <link href="'.$RootPath.'/css/homepage.css" rel="stylesheet" type="text/css"/>
       <link href="'.$RootPath.'/Javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" />
       <link href="'.$RootPath.'/css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css" />';
           ?>
<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f9f9f9;
        color: #333;
    }

    .table-bordered {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .table-bordered th, .table-bordered td {
        border: 1px solid #e0e0e0;
        padding: 12px;
        text-align: left;
    }

    .table-bordered th {
        background-color: #4CAF50;
        color: white;
        font-weight: bold;
    }

    .table-bordered td {
        background-color: #fff;
    }

    .table-bordered tr:hover td {
        background-color: #f1f1f1;
    }

    label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }

    input[type="text"], input[type="email"], select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom: 10px;
        transition: border 0.3s;
    }

    input[type="text"]:focus, input[type="email"]:focus, select:focus {
        border: 1px solid #4CAF50;
        outline: none;
    }

    .btn {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
    }

    .btn:hover {
        background-color: #45a049;
    }

    .form-header {
        background-color: #4CAF50;
        color: white;
        padding: 15px;
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 20px;
    }
</style>
<?php
        echo '<script type="text/javascript" src = "'.$RootPath.'/Javascripts/jquery.min.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/Javascripts/jquery-1.12.0.min.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/Javascripts/bootstrap.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/Javascripts/bootstrap.min.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/Javascripts/jquery.tablesorter.pager.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/Javascripts/jquery.dataTables.min.js"></script>
        <script type="text/javascript" src = "'.$RootPath.'/Javascripts/MiscFunctions.js"></script> 
        <script type="text/javascript" src = "'.$RootPath.'/Ajax/jquerryhr.js"></script>';
        
        echo '</head><body>';
	echo '<input type="hidden" name="Lang" id="Lang" value="'.$Lang.'" /><div class="container">';

?>
