<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
$Title = _('Print Stock');

$stockClass = new StockBalance();
$ReporttankClass= new ReporttankClass();

if(mb_strlen($_POST['fromdate'])>0){
    
    if(mb_strlen($_POST['todate'])>0){
   
        if(isset($_POST['trailbalance'])){
            if(Date1GreaterThanDate2($_POST['fromdate'],$_POST['todate'])==TRUE){
             unset($_POST['trailbalance']);
             $errorExists=1;
            }
        }
        
    }else{
        unset($_POST['trailbalance']);
    }
    
}else{
        unset($_POST['trailbalance']);
    }
 
if(isset($_POST['trailbalance'])){
    
    $PaperSize='A4_Landscape';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Inventory Reports'));
    $pdf->addInfo('Subject',_('inventory'));
    $pdf->addInfo('Creator',_('SmartERP'));
    $store_name = $stockClass ->storename;
    
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFinventoryheader.inc');
     $FontSize = 8;
     $YPos = $firstrowpos;
     $Balance = 0;
     
     $ResultsP = $stockClass->EXECUTE();
     while($rows = DB_fetch_array($ResultsP)){
       
         $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize, ucfirst($rows['stockname']),'left');
         $LeftOvers = $pdf->addTextWrap(150, $YPos,50, $FontSize, trim($rows['fulqtyName']),'right');
         $LeftOvers = $pdf->addTextWrap(230, $YPos,30, $FontSize, number_format($rows['openstock_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(310, $YPos,30, $FontSize, number_format($rows['Purchases_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(390, $YPos,30, $FontSize, number_format($rows['Prod_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(470, $YPos,30, $FontSize, number_format($rows['Total_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(550, $YPos,30, $FontSize, number_format($rows['Work_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(630, $YPos,30, $FontSize, number_format($rows['Sales_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(710, $YPos,30, $FontSize, number_format($rows['Close_f'],0),'right');
         $YPos -= $line_height * 0.5 ;
           
         $YPos -= $line_height ;
         if($YPos < ($lastrow+($line_height * 3))){
             include('includes/PDFinventoryheader.inc');
             $YPos=$firstrowpos;
             $FontSize = 8;
         }
      }
          
      
      $DateEntry=FormatDateForSQL($_POST['todate']);
      
      $rowtanks = DB_query("SELECT 
            `ProductionUnit`.`tankname`,
            `ProductionUnit`.`balance`
        FROM `ProductionUnit` 
        where `ProductionUnit`.`status`=1", $db);
     
    if(DB_num_rows($rowtanks)>0){
           $YPos -= $line_height ;
           $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Work In Progress'),'left');
    }
    
     $rowtanks = DB_query("SELECT `ProductionUnit`.`itemcode`,`capacity`,`tankname`,`UOM`,`CapacityUOM` ,
        `ProductionUnit`.`units`,`balance`,`stockmaster`.`descrip`,`stockmaster`.`averagestock`
        FROM `ProductionUnit` join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode
        where `ProductionUnit`.`status`=1", $db);
   while($rows = DB_fetch_array($rowtanks)){
         $YPos -= $line_height ;
         if($YPos < ($lastrow+($line_height*2))){
             include('includes/PDFinventoryheader.inc');
             $YPos=$firstrowpos;
             $FontSize = 8;
         }
         $balance   = getTankbalanceBYDATE($rows['tankname'],$DateEntry);
         $LeftOvers = $pdf->addText(50, $YPos, $FontSize, _('Tank :').$rows['tankname'].'('.trim($rows['descrip']).')','left');
         $LeftOvers = $pdf->addText(400,$YPos, $FontSize,number_format($balance,0),'right');
     
   }   
    
$pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Inventory'). '_' . date('Y-m-d').'.pdf');
$pdf->__destruct();

    
} else {
    
  $Title = _('Print Inventory');
  include('includes/header.inc');
  
  if(isset($errorExists)){
     prnMsg('You have selected an invalid date range','warn');
  }
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Inventory Report') .'" alt="" />' . _('Inventory Report') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
  
  echo '<table class="table table-bordered"><tr><td valign="top"><table class="table table-bordered"><tr>'
    . '<td>From Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="fromdate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['fromdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
       <td>To Date</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="todate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['todate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
 echo '<tr><td>Select Store</td><td><select name="store"><option value="AllStores">All</option>';
 
   $sql="SELECT `code`,`Storename` FROM `Stores`";
   $ResultsP = DB_query($sql,$db);
    while($rows = DB_fetch_array($ResultsP)){
          echo '<option value="'.$rows['code'].'">'.$rows['Storename'].'</option>' ;
   }
   
  echo '</select></td></tr></table></td></tr>'
  . '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Inventory"/></td></tr></table>';
  echo '</div></form>';
  
  include('includes/footer.inc');
  
  
}


  
   class ReporttankClass{
         var $rows1;
         var $rowsunits;
                 
                function update_tank_balance(){
                    global $db;

                    $DATE=ConvertSQLDateTime($_POST['todate']).' 23:59:59';
                    $sql="SELECT `tankname`,`capacity` FROM `ProductionUnit`";
                       $ResultIndex = DB_query($sql,$db);
                       while($dbrows= DB_fetch_array($ResultIndex)){
                           $this->rows1[]=$dbrows;
                            
                       }
                        
                       foreach ($this->rows1 as $key => $tankcode) {
                           $myTankCode = $tankcode['tankname'];
                             
                           if(mb_strlen($myTankCode)>0){
                              $sql=sprintf("select sum(`units`) as units from `tanktrans` "
                                   . " where `tankname`='%s' and date <='%s'",$myTankCode,$DATE);
                           
                            $ResultIndex = DB_query($sql,$db);
                            while($dbrows= DB_fetch_array($ResultIndex)){
                                    $this->rowsunits[$myTankCode] = ($dbrows['units']);
                                }
                           }
                       }
                       
                         $_SESSION['tank_balance']=$this->rowsunits;

                }
                
                
     
     }
   
  
  
  class StockBalance {
      var $Fromdate;
      var $Todate;
      var $StoreCode;
      var $storename;
      
      function __construct() {
        
          $this->Fromdate = ConvertSQLDateTime($_POST['fromdate']);  
          $this->Todate = ConvertSQLDateTime($_POST['todate']).' 23:59:59';
          $this->StoreCode = $_POST['store'];
          
          if(isset($_POST['store'])){
                if($_POST['store']=='AllStores'){
                     $this->storename='AllStores';
               } else {
                      $sql="SELECT `Storename` FROM `Stores` where `code`='".$_POST['store']."'";
                      $ResultsP = DB_query($sql,$db);
                      $rows=DB_fetch_row($ResultsP);
                      $this->storename=$rows[0];
               }
          }
      }
            
      function EXECUTE(){
          Global $db;

          $fromDate = DB_escape_string($this->Fromdate);
          $toDate   = DB_escape_string($this->Todate);
          $storeSql = ($this->StoreCode=='AllStores') ? '' : " AND sl.store='" . DB_escape_string($this->StoreCode) . "'";

          $sql = "SELECT
                    i.itemcode,
                    i.stockname,
                    i.averagestock,
                    i.fulqtyName,
                    i.openstock_f,
                    i.Purchases_f,
                    i.Prod_f,
                    (i.openstock_f + i.Purchases_f + i.Prod_f) AS Total_f,
                    i.Sales_f,
                    i.Work_f,
                    i.Close_f
                  FROM (
                    SELECT
                      sm.itemcode AS itemcode,
                      sm.descrip AS stockname,
                      sm.averagestock AS averagestock,
                      IFNULL(uf.descrip,'') AS fulqtyName,
                      (
                        (
                          SELECT IFNULL(SUM(sl.fulqty * sl.partperunit),0) + IFNULL(SUM(sl.loosqty),0)
                          FROM stockledger sl
                          WHERE sl.itemcode = sm.itemcode
                            AND sl.`date` < '" . $fromDate . "'" . $storeSql . "
                        ) +
                        (
                          SELECT IFNULL(SUM(sl.fulqty * sl.partperunit),0) + IFNULL(SUM(sl.loosqty),0)
                          FROM stockledger sl
                          WHERE sl.itemcode = sm.itemcode
                            AND sl.`date` BETWEEN '" . $fromDate . "' AND '" . $toDate . "'
                            AND sl.doctyp=17" . $storeSql . "
                        )
                      ) AS openstock_f,
                      (
                        SELECT IFNULL(SUM(sl.fulqty * sl.partperunit),0) + IFNULL(SUM(sl.loosqty),0)
                        FROM stockledger sl
                        WHERE sl.itemcode = sm.itemcode
                          AND sl.`date` BETWEEN '" . $fromDate . "' AND '" . $toDate . "'
                          AND sl.doctyp=30" . $storeSql . "
                      ) AS Purchases_f,
                      (
                        SELECT IFNULL(SUM(sl.fulqty * sl.partperunit),0) + IFNULL(SUM(sl.loosqty),0)
                        FROM stockledger sl
                        WHERE sl.itemcode = sm.itemcode
                          AND sl.`date` BETWEEN '" . $fromDate . "' AND '" . $toDate . "'
                          AND sl.doctyp=40" . $storeSql . "
                      ) AS Prod_f,
                      (
                        SELECT IFNULL(SUM(sl.fulqty * sl.partperunit),0) + IFNULL(SUM(sl.loosqty),0)
                        FROM stockledger sl
                        WHERE sl.itemcode = sm.itemcode
                          AND sl.`date` BETWEEN '" . $fromDate . "' AND '" . $toDate . "'
                          AND sl.doctyp=19" . $storeSql . "
                      ) AS Sales_f,
                      (
                        SELECT IFNULL(SUM(sl.fulqty * sl.partperunit),0) + IFNULL(SUM(sl.loosqty),0)
                        FROM stockledger sl
                        WHERE sl.itemcode = sm.itemcode
                          AND sl.`date` BETWEEN '" . $fromDate . "' AND '" . $toDate . "'
                          AND sl.doctyp=26" . $storeSql . "
                      ) AS Work_f,
                      (
                        SELECT IFNULL(SUM(sl.fulqty * sl.partperunit),0) + IFNULL(SUM(sl.loosqty),0)
                        FROM stockledger sl
                        WHERE sl.itemcode = sm.itemcode
                          AND sl.`date` <= '" . $toDate . "'" . $storeSql . "
                      ) AS Close_f
                    FROM stockmaster sm
                    LEFT JOIN unit uf ON sm.units=uf.code
                    WHERE (sm.isstock_3=0 OR sm.isstock_3 IS NULL)
                      AND (sm.inactive=0 OR sm.inactive IS NULL)
                  ) i
                  ORDER BY i.stockname";

          return DB_query($sql,$db);
      }
      
      
  }
  
    
  
     
Function getTankbalanceBYDATE($tank, $DATE, $fromDate = '', $doctyp = '') {
        global $db;
        
        $conditions = array();
        $conditions[] = "tankname='" . $tank . "'";
        
        if (!empty($DATE)) {
            $conditions[] = "`date` <= '" . $DATE . "'";
        }
        
        if (!empty($fromDate)) {
            $conditions[] = "`date` >= '" . $fromDate . "'";
        }
        
        if (!empty($doctyp)) {
            $conditions[] = "doctyp='" . $doctyp . "'";
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $sql = "SELECT IFNULL(SUM(units), 0) as units 
                FROM tanktrans 
                WHERE " . $whereClause;
        
        $ResultIndex = DB_query($sql, $db);
        $dbrows = DB_fetch_row($ResultIndex);
        
        return $dbrows[0] ? $dbrows[0] : 0;
      }
  
?>
