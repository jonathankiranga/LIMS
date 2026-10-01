<?php
include_once('credentials.inc');
include_once('NTLMStream.php');
include_once('NTLMSoapClient.php');
include_once('../config.php');

session_write_close(); //in case a previous session is not closed
session_name('Smartpayrollsystem');
session_start();
// we unregister the current HTTP wrapper
stream_wrapper_unregister('http');
 // we register the new HTTP wrapper
stream_wrapper_register('http','NTLMStream') or die("Failed to register protocol");



$db = odbc_connect("Driver={SQL Server};Server=$host;Database=$DefaultDatabase;",$DBUser,$DBPassword);
   
if(isset($_POST['DisplayCompany']) 
        || isset($_GET['DisplayCompany'])){
        DisplayCompany();
}

if(isset($_POST['Dimensions']) 
        || isset($_GET['Dimensions'])){
    DefaultDimension();
}

if(isset($_POST['VendorPage']) 
        || isset($_GET['VendorPage'])){
    if($_POST['NavPage']=='Vendor'){
        VendorPage($_POST['VendorPage']);
    }else{
        GlPage($_POST['VendorPage'],$_POST['pandls']);
    }
}

if(isset($_POST['PostPayrollId']) 
        || isset($_GET['PostPayrollId'])){
        PostPayroll($_POST['PostPayrollId']);
}

function LoadPage($webservice){
    try{
        $Pageservice = new NTLMSoapClient($webservice);
    } catch (NTLMSoapClient $e) {
         var_dump(libxml_get_last_error()) ;
    }  
    return $Pageservice;
}

function ReadPage($Page,$params=''){
    try{
        $result = $Page->ReadMultiple($params);  
    }  catch (NTLMSoapClient $e) {
              var_dump(libxml_get_last_error());
    }    
    return $result;
}

function init(){
    Global $baseURL,$USERPWD,$db;
    
    $result = odbc_exec($db,"Select confvalue from config  WHERE confname='WEBnavcompany'");
    $row = odbc_fetch_array($result,$rownumber=null);
    
    if(isset($row['confvalue'])){
      $_SESSION['WEBnavcompany'] = trim($row['confvalue']);
    }
    odbc_free_result($result);

    
    $ERP = new stdClass();
    try{
        $ERP = new NTLMSoapClient(baseURL.'SystemService');
    } catch (NTLMSoapClient $e) {
            echo "<script> alert('".$e->getMessage()."');</script>";
    }
    // Find the first Company in the Companies
    return  $ERP;
}

function GetCompany(){
  $Success = false;
  $ERPwebservice = init();
  
    if(property_exists($ERPwebservice->Companies(),'return_value')){
           $ERP = $ERPwebservice->Companies();
           $_SESSION['DynamicsNav71']['Companies'] = $ERP->return_value;
           $Success=true;
    }else{
         echo "<script> alert('Please check your ERP ,because the system is not on line');</script>";
    }
     
    return $Success;
}

Function DisplayCompany(){
    if (GetCompany()==true){
        
        if(is_array($_SESSION['DynamicsNav71']['Companies'])){
           foreach ($_SESSION['DynamicsNav71']['Companies'] as $key => $value) {
                if(isset($_SESSION['WEBnavcompany'])){
                    if($_SESSION['WEBnavcompany'] == $value){
                         echo '<option value="'.$value.'"  selected="selected">'.$value.'</option>' ;
                      }else{
                          echo '<option value="'.$value.'">'.$value.'</option>' ;    
                      }
                } else {
                     echo '<option value="'.$value.'">'.$value.'</option>' ;    
                }
            }
        } else {
             echo '<option value="'.$_SESSION['DynamicsNav71']['Companies'].'">'.$_SESSION['DynamicsNav71']['Companies'].'</option>' ;  
        }
       
    }
}

Function VendorPage($filter){
    
    if(GetCompany()==true){
        $companyname= trim($_SESSION['WEBnavcompany']);
        $webservice = baseURL.rawurlencode($companyname).'/Page/VendorList';
        $Page = LoadPage($webservice);
        
            $filter = strtoupper(trim($filter)).'*' ;
            $params = array('filter'=>
            array(array('Field'=>'Name','Criteria'=>$filter)),
            'setSize'=>50);
            
            $result = ReadPage($Page, $params);
                                       
            $AccountsArray=$result->ReadMultiple_Result->VendorList;
                                    
            if (is_array($AccountsArray)) {
                foreach ($AccountsArray as $value) {
                 if(isset($_SESSION['Webglaccountlink'])){ 
                     if($_SESSION['Webglaccountlink']==$value->No){
                          echo '<option value="'.$value->No.'"  selected="selected">'.$value->No.' - '.$value->Name.'</option>' ;
                     } else {
                          echo '<option value="'.$value->No.'">'.$value->No.' - '.$value->Name.'</option>' ;
                      }
                 } else {
                      echo '<option value="'.$value->No.'">'.$value->No.' - '.$value->Name.'</option>' ;
                  }
              }
            } else {
                echo '<option value="'.$AccountsArray->No.'">'.$AccountsArray->Name.'</option>' ;
            }      
        echo '<option></option>';
     }
}

Function GlPage($filter){
    
    if(GetCompany()==true){
        $companyname= trim($_SESSION['WEBnavcompany']);
        $webservice = baseURL.rawurlencode($companyname).'/Page/chartofaccounts';
        $Page = LoadPage($webservice) ;
         
        $filter = strtoupper(trim($filter)).'*' ;
        $params = array('filter' => array( 
             array('Field' => 'Name','Criteria' =>$filter),
             array('Field' => 'Account_Type','Criteria' => 'Posting'),
             array('Field' => 'Direct_Posting','Criteria' => 'Yes')),
            'setSize' => 0);
        $result = ReadPage($Page, $params);
                                            
        $AccountsArray=$result->ReadMultiple_Result->chartofaccounts;
                                
            if (is_array($AccountsArray)) {
                foreach ($AccountsArray as $value) {
                 if(isset($_SESSION['Webglaccountlink'])){ 
                     if($_SESSION['Webglaccountlink']==$value->No){
                          echo '<option value="'.$value->No.'"  selected="selected">'.$value->No.'-'.$value->Name.' - '.$value->Income_Balance.'</option>' ;
                     } else {
                          echo '<option value="'.$value->No.'">'.$value->No.'-'.$value->Name.' - '.$value->Income_Balance.'</option>' ;
                      }
                 } else {
                      echo '<option value="'.$value->No.'">'.$value->No.'-'.$value->Name.' - '.$value->Income_Balance.'</option>' ;
                  }
              }
            } else {
                echo '<option value="'.$AccountsArray->No.'">'.$AccountsArray->Name.'</option>' ;
            }      
        echo '<option></option>';
     }

}

function GetDoc(){
     Global $db;
     
    $SQL="SELECT [typeno] FROM [systypes_1] where [typeid]=3  ";
    $result = odbc_exec($db,$SQL);
    $systypes = odbc_fetch_array($result,1);
    $DocNo = $systypes['typeno'];
    odbc_free_result($result);

    $SQL="Update  [systypes_1] set [typeno]=[typeno]+1 where [typeid]=3  ";
    $result = odbc_exec($db,$SQL);

 
    return $DocNo;
}

Function PostPayroll($payid){
        Global $baseURL,$USERPWD,$db;
    
    if(GetCompany()==true){
        $companyname = trim($_SESSION['WEBnavcompany']);

        $journalpage = baseURL.rawurlencode($companyname).'/Page/payrolljournal';
        $Page = LoadPage($journalpage);
                                               
        $SQL="SELECT [payrollid],[code],[description],[amount] 
            ,[pagetype],[navcode],[pagetype_balancing],[navcode_balancing]
      FROM [prlnavwebserviceupdate] where [payrollid]='".$payid."' and [posted] is null";
        
        $result = odbc_exec($db,$SQL);
        while($row = odbc_fetch_array($result,$rownumber=null)){
             // Create object
            $create = new stdClass();
            $sq = new stdClass();
             // Add to Create
             $create->payrolljournal = $sq;
             $create->CurrentJnlBatchName="PAYROLL";
             $results = $Page->create($create);
             
             $docno= GetDoc();
             $date= $results->payrolljournal->Posting_Date;
             $key = $results->payrolljournal->Key;
             $update = new stdClass();
       
             $update->payrolljournal = $sq;
             $update->payrolljournal->Posting_Date = $date;
             $update->payrolljournal->Key = $key;
             $update->payrolljournal->Document_Type='Invoice';
             $update->payrolljournal->Document_No = $docno ;
             $update->payrolljournal->Account_Type = $row['pagetype'];
             $update->payrolljournal->Account_No = $row['navcode'];
             $update->payrolljournal->Amount = $row['amount'];
             $update->payrolljournal->Bal_Account_Type = $row['pagetype_balancing'];
             $update->payrolljournal->Bal_Account_No = $row['navcode_balancing'];
             $update->payrolljournal->Description = $row['description'];
             $update->payrolljournal->Shortcut_Dimension_1_Code = $row['DimensionValue'];
             $update->CurrentJnlBatchName="PAYROLL";
             $Page->Update($update);
        }
        
        if(libxml_get_last_error()==FALSE){
            $SQL[]="Update [prlnavwebserviceupdate] SET POSTED = getdate() where [payrollid]='".$payid."'";
            $SQL[]="Update config SET confvalue=DATEADD(month,1,confvalue) where confname='DB_Maintenance_LastRun'";
            foreach ($SQL as $value) {
                $result = odbc_exec($db,$value);
            }
            
            echo 'Posted, Go to Nav ERP';
            
        }
    }
}


function DefaultDimension(){
    ///Page/defaultdimensions    
    if(GetCompany()==true){
       $companyname = trim($_SESSION['WEBnavcompany']);
       $journalpage = baseURL.rawurlencode($companyname).'/Page/defaultdimensions';
       $Page = LoadPage($journalpage);
       
       $params = array('filter' => array( 
             array('Field' => 'Dimension_Value_Type','Criteria' =>'Standard'),
           array('Field'=>'Blocked','Criteria' =>'No')),
            'setSize' => 0);
       
        $result = ReadPage($Page, $params);
        $dimensionArray = $result->ReadMultiple_Result->defaultdimensions;
        if (is_array($dimensionArray)) {
            foreach ($dimensionArray as $value) {
               echo '<option value="'.$value->Code.'">'.$value->Code.'-'.$value->Name.'</option>' ;
             }
       } else{
             echo '<option value="'.$dimensionArray->Code.'">'.$dimensionArray->Code.'-'.$dimensionArray->Name.'</option>' ;
       }
       
    }
}


Function PostByCodeUnit(){
//Object Type	Object ID	Service Name	Published
//Codeunit	50002	PostPurchaseJournal	Yes
    if(GetCompany()==true){
        $companyname= trim($_SESSION['WEBnavcompany']);
        $webservice = baseURL.rawurlencode($companyname).'/Codeunit/PostPurchaseJournal';
        $Page = LoadPage($webservice) ;
        $Page->PostJournal();
    }
    
}

 



stream_wrapper_restore('http');
      
?>