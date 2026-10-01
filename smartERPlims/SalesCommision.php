<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Sales Commsions');
include('includes/header.inc');
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

 $FR = new MonthlyReports();
 if(isset($_GET['rownumber'])){
   $rownumber=$_GET['rownumber'];
 }elseif(isset($_POST['rownumber'])){
   $rownumber=$_POST['rownumber'];
 }
 
 if(isset($_GET['fp'])){
   $_POST['Financial_Periods']=$_GET['fp'];
 }elseif(isset($_POST['Financial_Periods'])){
   $_POST['Financial_Periods']=$_POST['Financial_Periods'];
 }
 
if(isset($_POST['Save_Rate'])){
     $sql=array();
     $sql[]="delete FROM Commision where ceiling='".$_POST['Financial_Periods']."' and rownumber='".$_POST['rownumber']."'";
     $sql[]="INSERT INTO Commision (commisionabove,commisionbelow,rownumber,ceiling)"
        . " values ('".$_POST['commisionabove']."','".$_POST['commisionbelow']."','".$_POST['rownumber']."','".$_POST['Financial_Periods']."')";
        foreach ($sql as $SQL) {
           
           DB_query($SQL, $db) ;
        }
 }
  
 if(isset($rownumber)){
     
 $ResultIndex=DB_query(sprintf("SELECT commisionabove,commisionbelow,rownumber"
 . " FROM Commision where ceiling='%s' and rownumber='%s'",$_POST['Financial_Periods'],$rownumber),$db);
    $row=DB_fetch_row($ResultIndex);
    $_POST['commisionabove']=$row[0];
    $_POST['commisionbelow']=$row[1];
    $_POST['rownumber']=$row[2];
    
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Sales Commision Rates') .'" alt="" />' . _('Sales Commision Rates') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>'
     . '<input type="hidden" name="rownumber" value="'.$_POST['rownumber'].'"/>';
  echo '<table  class="table-striped table-bordered"><tr><td colspan="2">**</td></tr>';
  $FR->Get();
  echo '<tr><td>TIER NO</td><td><input type="text" value="'.$_POST['rownumber'].'"/></td></tr>';
  echo '<tr><td>Rate when higher than Recommended Price</td>'
  . '<td><input type="text" name="commisionabove" value="'.$_POST['commisionabove'].'"/></td></tr>';
  echo '<tr><td>Rate when lower than Recommended Price</td><td><input type="text" name="commisionbelow" value="'.$_POST['commisionbelow'].'"/></td></tr>';
  echo '<tr><td><input type="submit" name="refresh" value="refresh"/></td>'
  . '<td><input type="submit" name="Save_Rate" value="Create New Row for the selected Month"/></td></tr></table>';
  echo '</div></form>';
  
 } else{
 
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
   echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/><table class="table table-striped table-bordered">';
   
   $ReportFinaceArray=array();
   
  $ResultIndex=DB_System("SELECT `periodno`,`lastdate_in_period` as year "
                        . "from `periods` where `lastdate_in_period` < DATE_ADD(NOW(), INTERVAL 1 MONTH)  "
                        . " Group by `periodno`,`lastdate_in_period`"
                        . " order by `periodno` desc limit 12", $db);
                while($row=DB_fetch_array($ResultIndex)){
                    $ReportFinaceArray[]=$row;
                }  
                
    foreach ($ReportFinaceArray as $key=>$value) {
        echo '<tr><th colspan="3">For The month Ending :'. ConvertSQLDate($value['year']).'</th></tr>';
        echo '<tr><th>Index</th><th>When Higher</th><th>When Lower</th></tr>';
        $ResultIndex=DB_query("SELECT commisionabove,commisionbelow,rownumber,ceiling"
                . " FROM Commision where ceiling='".$value['periodno']."'  order by rownumber asc limit 5",$db);
        while($rows = DB_fetch_array($ResultIndex)){

            echo sprintf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>",'<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?rownumber='.$rows['rownumber'].'&fp='.$rows['ceiling'].'">'.$rows['rownumber'].'</a>',
                   $rows['commisionabove'],$rows['commisionbelow'] );
        }
   }
   
   echo '</table>';
   
   
     echo '</div></form>';
 }
  
 
 
  include('includes/footer.inc');
