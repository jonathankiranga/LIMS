<?php


$mysignitories=array(
    'Lab Confirmed By________________________________.  Signature_______________________________',
    'Issued by_______________________________________   Signature_______________________________',
    'Finance Approved by_____________________________   Signature_______________________________.',
    'Loading by______________________________________   Signature_______________________________',
    'Checked by______________________________________   Signature_______________________________',
    'LPO No_________________________________   Invoiced by______________Invoiced No...........',
    'Verified by_____________________________   Signature_____________________________________');


$myGRNSIGNATURES=array(
    'RECEIVED BY STORE____________________________   Signature___________________',
    'SECURITY NAME________________________________.  Signature___________________',
    'ACCOUNTS NAME________________________________   Signature___________________',
    'PURCHASING NAME _____________________________   Signature___________________.',
    'OTHER NAME   ________________________________  Signature____________________');

$Dimesion_one=array();
$Dimesion_two=array();
$Dimesion_three=array();

$_SESSION['SelectObject']=array('dimensionone','dimensiontwo');

$Result = DB_query("SELECT "
        . "Dim1.`Dimension_type` AS D1,"
        . "Dim2.`Dimension_type` AS D2,"
        . "IFNULL(companies.`DefaultDimension_1`,'0') ,"
        . "IFNULL(companies.`DefaultDimension_2`,'0') "
        . "FROM `companies`"
        . " LEFT JOIN `DimensionSetUp` Dim1 ON companies.`DefaultDimension_1`=Dim1.`id` "
        . " LEFT JOIN `DimensionSetUp` Dim2 ON companies.`DefaultDimension_2`=Dim2.`id` "
        . "WHERE companies.`coycode`=1",$db);
$DimensionRow = DB_fetch_row($Result);

$id1 = $DimensionRow[2];
$id2 = $DimensionRow[3];

$_SESSION['Dimesion_one_name']= $DimensionRow[0];
$_SESSION['Dimesion_two_name']= $DimensionRow[1];

DB_free_result($Result);

///////////////////////////////////////////////////////////////////////
$_SESSION['SelectObject']['dimensionone']='<td>'.$_SESSION['Dimesion_one_name'].':</td><td><Select name="DimensionOne"><option></option>'; 
$DRows=DB_query("SELECT `Code`,`Dimension`,0 AS levels
                 FROM `Dimensions`
                 WHERE `id`='".$id1."' AND IFNULL(`Blocked`,0)=0
                 ORDER BY `parent`,`Dimension`",$db);
while($row = DB_fetch_array($DRows)){
     $styles=(($row['levels']==0)?'':'&nbsp;&nbsp;');
     if(isset($_POST['DimensionOne'])){
     $_SESSION['SelectObject']['dimensionone'] .= '<option  value="'.$row['Code'].'" '.($row['Code']==$_POST['DimensionOne']?'selected="selected"':'').'>'.$styles.$row['Dimension'].'</option>';
    }else{
     $_SESSION['SelectObject']['dimensionone'] .= '<option   value="'.$row['Code'].'" >'.$styles.$row['Dimension'].'</option>';
    }
}

$_SESSION['SelectObject']['dimensionone'] .='</select></td>';
  
////////////////////////////////////////////////////////////


$_SESSION['SelectObject']['dimensiontwo']='<td>'.$_SESSION['Dimesion_two_name'].':</td><td><Select name="DimensionTwo"><option></option>'; 
$DRows=DB_query("SELECT `Code`,`Dimension`,0 AS levels
                 FROM `Dimensions`
                 WHERE `id`='".$id2."' AND IFNULL(`Blocked`,0)=0
                 ORDER BY `parent`,`Dimension`",$db);
while($row =DB_fetch_array($DRows)){
     $styles=(($row['levels']==0)?'':'&nbsp;&nbsp;');
     if(isset($_POST['DimensionTwo'])){
     $_SESSION['SelectObject']['dimensiontwo'] .= '<option value="'.$row['Code'].'" '.($row['Code']==$_POST['DimensionTwo']?'selected="selected"':'').'>'.$styles.$row['Dimension'].'</option>';
    }else{
     $_SESSION['SelectObject']['dimensiontwo'] .= '<option value="'.$row['Code'].'" >'.$styles.$row['Dimension'].'</option>';
    }
}

$_SESSION['SelectObject']['dimensiontwo'] .='</select></td>';




/////////////////////////////////////////


$_SESSION['report']['project']='<td>'.$_SESSION['Dimesion_two_name'].':</td><td><Select name="DimensionTwo"><option value="">Admin</option>'; 
$DRows=DB_query("SELECT `Code`,`Dimension`,0 AS levels
                 FROM `Dimensions`
                 WHERE `id`='".$id2."' AND IFNULL(`Blocked`,0)=0
                 ORDER BY `parent`,`Dimension`",$db);
while($row =DB_fetch_array($DRows)){
     $styles=(($row['levels']==0)?'':'&nbsp;&nbsp;');
     if(isset($_POST['DimensionTwo'])){
     $_SESSION['report']['project'] .= '<option  value="'.$row['Code'].'" '.($row['Code']==$_POST['DimensionTwo']?'selected="selected"':'').'>'.$styles.$row['Dimension'].'</option>';
    }else{
      $_SESSION['report']['project'] .= '<option value="'.$row['Code'].'" >'.$styles.$row['Dimension'].'</option>';
    }
}
$_SESSION['report']['project'] .='</select></td>';

 ///////////////////////////////////////////////////////////////////////


$_SESSION['report']['budget']='<td>'.$_SESSION['Dimesion_one_name'].':</td><td><Select name="DimensionOne"><option value="">Admin</option>'; 
$DRows=DB_query("SELECT `Code`,`Dimension`,0 AS levels
                 FROM `Dimensions`
                 WHERE `id`='".$id1."' AND IFNULL(`Blocked`,0)=0
                 ORDER BY `parent`,`Dimension`",$db);
while($row =DB_fetch_array($DRows)){
    $styles=(($row['levels']==0)?'':'&nbsp;&nbsp;');
     if(isset($_POST['DimensionOne'])){
     $_SESSION['report']['budget'] .= '<option  value="'.$row['Code'].'" '.($row['Code']==$_POST['DimensionOne']?'selected="selected"':'').'>'.$styles.$row['Dimension'].'</option>';
    }else{
      $_SESSION['report']['budget'] .= '<option  value="'.$row['Code'].'" >'.$styles.$row['Dimension'].'</option>';
    }
}
$_SESSION['report']['budget'] .='</select></td>';





FUNCTION IssqldateGreater($PriodClosedDate,$CurrentDate){
    return (strtotime($CurrentDate) < strtotime($PriodClosedDate)) ? 0 : 1;
}

function OrderTransactionClasses(){
 return true;
 }

function OrderReportClasses(){
 return true;
 }

function DropConn($name){
   return true;
}



 function SelectPriceListToUse($stockid,$quantity,$unitscode,$customerid=''){
         global $db;
            $price=''; $return=0; $Dprice='';
          
            if(mb_strlen($customerid)>1){
                $SQL1 = sprintf("select price from PriceList where stockcode='%s' and approved=1 and units_code='%s' and quantity=%f and customerCode='%s'",
                        $stockid,$unitscode,$quantity,$customerid);
                $ResultIndex = DB_query($SQL1,$db);
                $row = DB_fetch_row($ResultIndex);
                $price = $row[0];
            }

            $SQL2 = sprintf("select price from PriceList where stockcode='%s' and units_code='%s' and quantity=%f and (customerCode is null or customerCode='')",
                    $stockid,$unitscode,$quantity);
            $ResultIndex = DB_query($SQL2,$db);
            $row = DB_fetch_row($ResultIndex);
            $Dprice = $row[0];
                
            if($price>0){
                $return=(float) $price;
            }else{
                $return=(float) $Dprice;
            }
            
        return $return;     
     }
    
function getSalemanDescrip($code){
    global $db;
    
$ResultIndex=DB_query(Sprintf("SELECT `salesman`,`commission`,`inactive` FROM `salesrepsinfo` WHERE `code`='%s'",$code), $db);
$row=DB_fetch_row($ResultIndex);
       
return (isset($row[0])?$row[0]:'');
}



 function SelectContainerToUse($stockid,$quantity,$unitscode){
         global $db;
            $price=''; $return=0; $Dprice='';
  
            $SQL2 = sprintf("SELECT IFNULL(container,'') FROM PriceList WHERE stockcode='%s' AND units_code='%s' AND quantity=%f AND (customerCode IS NULL OR customerCode='')",
                    $stockid,$unitscode,$quantity);
            $ResultIndex = DB_query($SQL2,$db);
            $row = DB_fetch_row($ResultIndex);
            $containerCode = $row[0];
            
        return $containerCode;     
     }
     
     
     function SelectSupplierInvoiceNo($invoice_journal){
         global $db;
        
        $SQL2 = sprintf("SELECT `invref` FROM `creditorsledger` WHERE `journal`='%s'",$invoice_journal);
        $ResultIndex = DB_query($SQL2,$db);
        $row = DB_fetch_row($ResultIndex);
        $containerCode = $row[0];
            
        return $containerCode;     
     } 
?>
