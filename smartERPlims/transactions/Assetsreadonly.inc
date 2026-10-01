<?php

$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;


foreach ($_POST['quantity'] as $key => $value) {
    $location = $_POST['location'][$key];
    $stcode = $_POST['code'][$key] ;
    $qty = $value ;
    $dbbalance = $_POST['unitbalance'][$key];
    
    if($qty > 0){
        $_SESSION['invoicerrors']++;
    }
    
    if($dbbalance < 0){
        $_SESSION['somedetails']++;
    } 
    
}
    
if($_SESSION['somedetails'] > 0) {
   prnMsg('You cannot Issue more than is available','warn');
}
    
 if($_SESSION['invoicerrors'] == 0){
   prnMsg('You have not entred stock units to receive','warn');
}   
    
if($_SESSION['DocumentPicking']==true){
   prnMsg('Create a new GRN no','warn');
}

if($_SESSION['somedetails'] == 0 AND $_SESSION['DocumentPicking']==false){
    

$sqldebtors=DB_query("SELECT itemcode ,creditlimit,customer
      ,phone ,email ,city ,country,curr_cod,customerposting,salesman,VATinclusive
       FROM debtors join postinggroups on code=customerposting
       where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];


$transstart=DB_Txn_Begin($db);

$PeriodNo = GetPeriod($_POST['date'],$db,true);

$sql=array();

$sql[]="INSERT INTO AssetsHeader
           (documenttype
           ,documentno
           ,docdate
           ".(Is_date($_POST['Salesoderdate'])?',oderdate':'')."
           ".(Is_date($_POST['datedue'])?',duedate':'')."
           ,vendorcode
           ,vendorname
           ,yourreference
           ,locationcode
           ,postinggroup
           ,currencycode
           ,status
           ,userid
           ,period
           ,vatinclusive
           ,Dimension_1
           ,Dimension_2)
     VALUES
           ('55'
           ,'".$_POST['documentno']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
          ,'".$_POST['CustomerID']."'
           ,'".$_POST['CustomerName']."'
           ,'".$_POST['reference']."'
           ,'".$_POST['locationcode']."'
           ,'".$customerposting ."'
           ,'".$_POST['currencycode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."'
           ,'".$_POST['DimensionOne']."'
           ,'".$_POST['DimensionTwo']."')" ;



foreach ($_POST['grossamount'] as $itemcode => $value ) {
 if($value>0){
     
     $ResultIndex = DB_query("SELECT
            fixedassets.assetid,
            fixedassets.barcode, 
            fixedassets.description, 
            fixedassets.cost, 
            IFNULL(vatcategory.vat,0) as vat 
           FROM fixedassets 
           left join fixedassetcategories on fixedassets.assetcategoryid=fixedassetcategories.categoryid 
           left join acct on fixedassetcategories.costact=acct.accno 
           left join GLpostinggroup on acct.postinggroup=GLpostinggroup.code 
           left join vatcategory on GLpostinggroup.vatcategory=vatcategory.vatc 
           where fixedassets.assetid='".$itemcode."'", $db);
     
         $stocklist = DB_fetch_row($ResultIndex);
         $stockname = $stocklist[2];
         $parts = 1;
         $vatrate = $stocklist[4]; ;
         $si = 'Pcs' ;
         $UOM = 'fulqty';
            
   if(isset($_POST['quantity'][$itemcode])){
        $qty = $_POST['quantity'][$itemcode];
    }
           
    if(isset($_POST['salesprice'][$itemcode])){
        $salesprice = $_POST['salesprice'][$itemcode] ;
    }
    
    if(isset($_POST['grossamount'][$itemcode])){
        $grossamount = $_POST['grossamount'][$itemcode] ;
    }
    
    if(isset($_POST['vatamount'][$itemcode])){
       $vatamount = $_POST['vatamount'][$itemcode];
    }
    
    $sql[] =  sprintf("INSERT INTO FixedAssetsLine
           (documenttype,docdate,documentno,code ,description,unitofmeasure,Quantity
           ,UnitPrice,vatamount,invoiceamount,vatrate,inclusive,UOM)
     VALUES
           ('%s','%s','%s','%s' ,'%s' ,'%s','%s' ,'%s','%s','%s' ,'%s' ,'%s','%s')"
            ,"55"
           ,FormatDateForSQL($_POST['date'])
           ,$_POST['documentno']
           ,$itemcode
           ,$stockname
           ,$si
           ,$qty
           ,$salesprice
           ,$vatamount
           ,$grossamount
           ,$vatrate
           ,$VATinclusive
           ,$UOM);
        }
}


if( $_SESSION['locked'] == true){
     foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }

    $_SESSION['savedsuccessfuly']=false;

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save Purchase order :'.$_POST['documentno'],'warn');
    }else{
        DB_Txn_Commit($db);
        prnMsg('new Purchase order :'.$_POST['documentno']);
        $_SESSION['savedsuccessfuly'] = true;
        $_SESSION['locked'] = false;
    }
    
}

if($_SESSION['savedsuccessfuly']==true){
    
    $SQL="select 
       documentno
      ,docdate
      ,oderdate
      ,duedate
      ,postingdate
      ,vendorcode
      ,vendorname
      ,yourreference
      ,externaldocumentno
      ,locationcode
      ,paymentterms
      ,postinggroup
      ,currencycode
      ,printed
      ,released
      ,status
      ,userid from AssetsHeader 
      where documenttype='55' and documentno='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr><th>Parameter</th><th>Value</th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Request No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Collection date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Return Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Customer ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Customer Name</td><td>%s</td></tr>",$row[6]);
   echo '<tr><td colspan="2">Line Details</td></tr>';
       
    
    echo '<tr><td colspan="2">';
    
    echo '<table class="table table-bordered">'
            . '<tr><th>Asset ID</th>'
            . '<th>Name</th>'
            . '<th>Unit Of Measure</th>'
            . '<th>Quantity</th>'
            . '<th>Hire Charge</th>'
            . '<th>vat</th>'
            . '<th>Gross</th>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $SQL="select 
           documenttype
           ,docdate
           ,documentno
           ,code
           ,description
           ,unitofmeasure
           ,Quantity
           ,UnitPrice
           ,vatamount
           ,invoiceamount from FixedAssetsLine 
           where documenttype=55 and documentno='".$_POST['documentno']."'";
   
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'],
                 $myrow['unitofmeasure'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
         $VATAMOUNT += $myrow['vatamount'];
         $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }     
    echo sprintf('<tfoot><tr><td colspan="5">%s</td><td>%s</td><td>%s</td></tr></tfoot>','Order Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT,2));
    echo '</table></td></tr>';
    echo '</table>';
    
   echo '<div><input type="submit" name="confirm" value="'._('Confirm Request').'"/></div>';
}
    $_SESSION['Grossamounttotal']=0;
    $_SESSION['CompleteDocument']=$_POST['documentno'];
    
  
    echo '<div><input type="submit" name="Delete" value="'._('Cancel').'"/></div>';
}
?>
