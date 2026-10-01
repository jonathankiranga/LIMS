<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Inventory Maintenace');
include('includes/header.inc');
$thispage = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<div><ul>';
echo '<li><a  target="mainContentIFrame"   href="SelectProduct.php?newsearch=yes">' ._('Inventory Items List') .'</a></li>';
echo '</ul></div>'; // QuickMenuDiv

if(isset($_POST['submit'])){
    
   if($_POST['submit']=='Add New Inventory'){
        $SearchString = '%'. str_replace(' ','%',trim($_POST['descrip'])) . '%';
        $sql="select * from stockmaster where descrip like '".$SearchString."'";
        $ResultIndex=DB_query($sql,$db);
        if(DB_num_rows($ResultIndex)==0){
            
           $itemcode = Triger_stockmaster($_POST['descrip']);
            
           $sql=sprintf("INSERT INTO `stockmaster`
           (`itemcode` ,`isstock`,isstock_1 ,isstock_2 ,isstock_3 ,isstock_4 ,isstock_5 ,isstock_6 ,
           `barcode`,`descrip` ,`postinggroup` ,`averagestock`,`reorderlevel`,`eoq` ,
           `category` ,`units` ,`inactive`,`nextserialno` ,`container`,`sellingprice`,production,requireserial)
     VALUES
           ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',0,%f ,%f,'%s','%s','%s',1,'%s',%f,'%s','%s')",
               $itemcode,$_POST['isstock'] ?? 0,
                $_POST['isstock_1'] ?? 0,
               $_POST['isstock_2'] ?? 0,
               $_POST['isstock_3'] ?? 0,
               $_POST['isstock_4'] ?? 0,
               $_POST['isstock_5'] ?? 0,
               $_POST['isstock_6']?? 0
            ,$_POST['barcode']
            ,$_POST['descrip']
            ,$_POST['postinggroup']
            ,$_POST['reorderlevel']
            ,$_POST['eoq']
            ,$_POST['category']
            ,$_POST['units']
            ,0
            ,$_POST['container'] ?? 0
            ,$_POST['sellingprice']
            ,$_POST['ProductionCategory']
            ,$_POST['requireserial'] ?? 0);
       
        }else{
             $sql="select nothing";
             prnMsg('You cannot duplicate an inventory description');
         }
      
       
   }
    
   if($_POST['submit']=='Update Inventory'){
       
      $sql=sprintf("UPDATE `stockmaster`
         SET `isstock` = '%s',
              isstock_1 = '%s',isstock_2 ='%s',isstock_3 ='%s',isstock_4 ='%s',isstock_5 ='%s',isstock_6 ='%s'
            ,`barcode` = '%s'
            ,`descrip` = '%s'
            ,`postinggroup` = '%s'
            ,`reorderlevel` = %f
            ,`eoq` = %f
            ,`category` = '%s'
            ,`units` = '%s'
            ,`inactive` = '%s'
            ,`container`='%s'
            ,`sellingprice`= %f
            ,production='%s'
            ,requireserial='%s'
        WHERE `itemcode` = '%s' ",
               $_POST['isstock'] ?? 0 ,$_POST['isstock_1'] ?? 0,
         $_POST['isstock_2'] ?? 0,
         $_POST['isstock_3'] ?? 0,
         $_POST['isstock_4'] ?? 0,
         $_POST['isstock_5'] ?? 0,
         $_POST['isstock_6'] ?? 0,
              $_POST['barcode']
              ,$_POST['descrip'] ,$_POST['postinggroup'] 
              ,$_POST['reorderlevel'],$_POST['eoq'] 
              ,$_POST['category'],$_POST['units'] 
              ,$_POST['inactive'] ?? 0
              ,$_POST['container'],$_POST['sellingprice']
              ,$_POST['ProductionCategory']
              ,$_POST['requireserial'] ?? 0
              ,$_POST['StockID']);
   }
   
   if(!isset($_POST['postinggroup'])){
       prnMsg('You cannot save this inventory because you have not selected an "Inventory Posting Group"');
   }else{
       $ErrMsg = _('The inventory alterations could not be processed because');
       $DbgMsg = _('The SQL that was used to update the inventory and failed was');
       
       $result = DB_query($sql,$db, $ErrMsg, $DbgMsg);
   }
   
   unset($_POST);
}

if(isset($_POST['delete'])){
    
    $sql=sprintf("select * from stockledger where itemcode='%s' ",$_POST['StockID']);
    $ResultIndex=DB_query($sql,$db);
    if(DB_num_rows($ResultIndex)==0){
        DB_query("Delete from stockmaster where itemcode='".$_POST['StockID']."'", $db);
    }else{
         prnMsg('You cannot delete this inventory because it has entries');
     }
}

if (isset($_GET['StockID'])) {
    $sql=sprintf("select 
        `isstock`,
        `isstock_1`,
        `isstock_2`,
        `isstock_3`,
        `isstock_4`,
        `isstock_5`,
        `isstock_6`
       ,`barcode`
       ,`itemcode`
       ,`descrip`
       ,`postinggroup`
       ,`sellingprice`
       ,`reorderlevel`
       ,`eoq`
       ,`category`
       ,`units`
       ,`inactive`
       ,`container`
       , production
       , requireserial
   FROM `stockmaster` where itemcode='%s' ",$_GET['StockID']);
     $ResultIndex=DB_query($sql,$db);
     while($rowsk=DB_fetch_array($ResultIndex)){
         $_POST['isstock']=$rowsk['isstock'];
         $_POST['isstock_1']=$rowsk['isstock_1'];
         $_POST['isstock_2']=$rowsk['isstock_2'];
         $_POST['isstock_3']=$rowsk['isstock_3'];
         $_POST['isstock_4']=$rowsk['isstock_4'];
         $_POST['isstock_5']=$rowsk['isstock_5'];
         $_POST['isstock_6']=$rowsk['isstock_6'];
         $_POST['barcode']=$rowsk['barcode'];
         $_POST['itemcode']=$rowsk['itemcode'];
         $_POST['descrip']=$rowsk['descrip'];
         $_POST['postinggroup']=$rowsk['postinggroup'];
         $_POST['sellingprice']=$rowsk['sellingprice'];
         $_POST['reorderlevel']=$rowsk['reorderlevel'];
         $_POST['eoq']=$rowsk['eoq'];
         $_POST['category']=$rowsk['category'];
         $_POST['units']=$rowsk['units'];
         $_POST['inactive']=$rowsk['inactive'];
         $_POST['container']=$rowsk['container'];
         $_POST['ProductionCategory']=$rowsk['production'];
         $_POST['requireserial']=$rowsk['requireserial'];
          
    }
    
}

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Inventory Maintenance') . '" alt="" />' . ' ' . _('Inventory Maintenance') . '</p>';
// Import button
echo '<p><a class="btn btn-info" href="import_excel.php?type=stock">' . _('Import from Excel (CSV)') . '</a></p>';
echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="inventory">';
echo '<div class="container"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($_GET['StockID'])) {
    echo '<input type="hidden" name="StockID" value="' . $_GET['StockID'] . '" />';
}

echo '<table class="table-condensed table-responsive-small"><tr><td valign="top">'
    . '<table class="table-condensed table-responsive-small"><caption>General Information</caption>';
echo '<tr><td>' . _('Barcode').'</td><td><input type="text" name="barcode" maxlength="20"  value="'.$_POST['barcode'].'"/></td></tr>';
echo '<tr><td>' . _('Name').'</td><td><input type="text" name="descrip" maxlength="100" size="50" required="required" value="'.$_POST['descrip'].'"/></td></tr>';
echo '<tr><td>' . _('Reorder Level in units').'</td><td><input type="text" name="reorderlevel" maxlength="10" class="integer"  value="'.$_POST['reorderlevel'].'"/></td></tr>';
echo '<tr><td>' . _('EOQ in units').'</td><td><input type="text" name="eoq" maxlength="10" class="integer"  value="'.$_POST['eoq'].'"/></td></tr>';
echo  '</table></td><td><table class="table-condensed table-responsive-small"><caption>Settings</caption>';
echo '<tr><td>' . _('Stock Classification').'</td><td>';
 
?>
<div class="form-check">
  <input class="form-check-input" name="isstock_1" type="checkbox" value="1"  id="flexCheckDefault1" <?php 
 if($_POST['isstock_1']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="flexCheckDefault1">For Sale</label>
</div>

<!-- Checked checkbox -->
<div class="form-check">
  <input  class="form-check-input" name="isstock_2" type="checkbox"  value="1"  id="flexCheckChecked2" <?php 
 if($_POST['isstock_2']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="flexCheckChecked2"> purchased </label>
</div>
<!-- Checked checkbox -->
<div class="form-check">
  <input  class="form-check-input" name="isstock_3" type="checkbox"  value="1"  id="flexCheckChecked3" <?php 
 if($_POST['isstock_3']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="flexCheckChecked3">service</label>
</div>

<!-- Checked checkbox -->
<div class="form-check">
  <input  class="form-check-input" name="isstock_4" type="checkbox"  value="1"  id="flexCheckChecked4" <?php 
 if($_POST['isstock_4']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="flexCheckChecked4"> Assemble (For Production) </label>
</div>

<!-- Checked checkbox -->
<div class="form-check">
  <input  class="form-check-input" name="isstock_5" type="checkbox" value="1"  id="flexCheckChecked5" <?php 
 if($_POST['isstock_5']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="flexCheckChecked5"> Kit (Dis-Assembled)</label>
</div>

<!-- Checked checkbox -->
<div class="form-check">
  <input class="form-check-input" name="isstock_6" type="checkbox" value="1"  id="flexCheckChecked6" <?php 
 if($_POST['isstock_6']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="flexCheckChecked6"> Container/Package</label>
</div>


<!-- Checked checkbox -->
<div class="form-check">
  <input class="form-check-input" name="isstock" type="checkbox" value="1"  id="flexCheckChecked7" <?php 
 if($_POST['isstock']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="flexCheckChecked7"> Requires KEBS certificate</label>
</div>

<!-- Checked checkbox -->
<div class="form-check">
  <input class="form-check-input" name="requireserial" type="checkbox" value="1"  id="flexCheckChecked8" <?php 
 if($_POST['requireserial']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="flexCheckChecked8"> Requires Serial Number </label>
</div>

<?php
echo  '</td></tr>';
 
    if(isset($_GET['StockID'])) {
       ?>
<!-- Checked checkbox -->
<tr><td></td><td><div class="form-check">
  <input class="form-check-input" name="inactive" type="checkbox" value="1"  id="inactive" <?php 
 if($_POST['inactive']=='1'){ echo 'checked' ; } 
?>/>
  <label class="form-check-label" for="inactive"> Do you want to remove this from Stock List ? </label>
</div></td></tr>
<?php
    } 
    
        echo '<tr><td>'._('Inventory Posting Group').'</td><td>';
          $result=DB_query("SELECT code FROM inventorypostinggroup",$db);
  	if (DB_num_rows($result)==0){
		echo '<tr><td colspan="2">' .
                        '<p>'. _('There are no posting groups currently defined <br /> go to the setup tab of the main menu and set at least one up first').'</p></td>
			</tr>';
	} else {
        echo '<select name="postinggroup">';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option value="'.$myrow['code'].'" '. ($myrow['code']==$_POST['postinggroup']?'selected="selected"':''). '>' . $myrow['code'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';

     
  echo '<tr><td>'._('Category').'</td><td>';
      
    $result=DB_query("SELECT `categoryid`,`categorydescription` FROM stockcategory",$db);
  	if (DB_num_rows($result)==0){
		echo '<tr><td colspan="2">' .
                        '<p>'. _('There are no Categories currently defined <br /> go to the setup tab of the main menu and set at least one up first').'</p></td>
			</tr>';
	} else {
        $result=DB_query("SELECT `categoryid`,`categorydescription` FROM stockcategory",$db);
        echo '<select name="category"><option value="0">Not Applicable</option>';
        while ($myrow = DB_fetch_array($result)) {
            	echo '<option value="'.$myrow['categoryid'].'" ' . (($myrow['categoryid']==$_POST['category'])?'selected="selected"':''). '>' . $myrow['categorydescription'] . '</option>';
        } //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';   
 echo '<tr><td>'._('Production Units of measure').'</td><td>';
      
    $result=DB_query("SELECT `code`,`descrip` FROM unit",$db);
  	if (DB_num_rows($result)==0){
        echo '<tr><td colspan="2">' .
                '<p>'. _('There are no Units of measure currently defined <br /> go to the setup tab of the main menu and set at least one up first').'</p></td>
                </tr>';
	} else {
        echo '<select name="units"><option value="0">Not Applicable</option>';
        while ($myrow = DB_fetch_array($result)) {
                    echo '<option value="'.$myrow['code'].'" ' . ($myrow['code']==$_POST['units']?'selected="selected"':''). '>' . $myrow['descrip'] . '</option>';
		} //end while loop
        echo '</select>' ;
        }      
     echo  '</td></tr>';   
     
     echo '<tr><td>'._('Select Production Category if applicable').'</td><td>';
      
        echo '<select name="ProductionCategory">';
        foreach ($ProductionCategory as $key => $value) {
           if($key!='06'){
                echo '<option value="'.$key.'" '. ($key==$_POST['ProductionCategory']?'selected="selected"':''). '>' . $value . '</option>';
           }
        } //end while loop
        echo '</select>' ;
     echo  '</td></tr>';
     
echo '</table></td></tr><tr><td colspan="2">' . _('Is this inventory packed').'</td></tr>';
echo '<tr><td>'._('Select the Package for Item if any');
    $result=DB_query("SELECT itemcode,descrip FROM stockmaster where isstock_6='1'",$db);
        echo '<select name="container"><option value="0">Not Applicable</option>';
        while ($myrow = DB_fetch_array($result)) {
		echo '<option value="'.$myrow['itemcode'].'" '. ($myrow['itemcode']==$_POST['container']?'selected="selected"':''). '>' . $myrow['descrip'] . '</option>';
		} //end while loop
        echo '</select>' ;
     echo  '</td></tr>';
     
     

     echo '<tr><td></td><td>';

        if (!isset($_GET['StockID'])) {
            echo '<input type="submit" name="submit" value="' . _('Add New Inventory') . '" />';
        } else {
           echo '<input type="submit" name="submit" value="' . _('Update Inventory') . '" />&nbsp;
                 <input type="submit" name="delete" value="' . _('Delete Inventory') . '" onclick="return confirm(\'' . _('Are You Sure You Want To Delete?') . '\');" />';
        }
        
echo '</td></tr></table></div></form>' ;
include('includes/footer.inc');

?>