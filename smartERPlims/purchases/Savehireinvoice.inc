<?php
$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;

foreach ($_POST['subunits'] as $key => $value) {
    $location = $_POST['location'][$key];
    $stcode = $_POST['code'][$key] ;
    $qty = $value;
    $dbbalance = $_POST['balance'][$key];
    
    if($qty > 0){
        $_SESSION['invoicerrors']++;
    }
    
    if($dbbalance < 0){
        $_SESSION['somedetails']++;
    } 
    
}
    
if($_SESSION['somedetails'] > 0) {
   prnMsg('You cannot Issue more than was ordered','warn');
}
    
 if($_SESSION['invoicerrors'] == 0){
   prnMsg('You have not entred stock units to receive','warn');
}   
    
if($_SESSION['DocumentPicking']==true){
   prnMsg('Create a new GRN no','warn');
}

if($_SESSION['somedetails'] == 0 AND $_SESSION['DocumentPicking']==false){
    $ResultIndex = DB_query('Select NOW() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
   
    $sqldebtors=DB_query("SELECT itemcode ,creditlimit,customer,
    phone ,email ,city ,country,curr_cod,customerposting,salesman,VATinclusive
    FROM debtors join postinggroups on code=customerposting
    where itemcode='".$_POST['VendorID']."'", $db);
     $debtorsrow = DB_fetch_row($sqldebtors);
     $customerposting = $debtorsrow[8];
     $VATinclusive = $debtorsrow[10];

    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,true);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = GetNextTransNo(41,$db);
    $journal         = GetNextTransNo(0,$db);
    
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
           ('19'
           ,'".$INVOICENO ."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
           ,'".$_POST['VendorID']."'
           ,'".$_POST['VendorName']."'
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

    foreach ($_POST['subunits'] as $key => $value) {
     if($value>0){

               $location = $_POST['location'][$key];
               $stcode = $_POST['code'][$key];
               $qty = $value;       
               $salesoderno= $_SESSION['DocumentNo'];
            
               $sql[] = sprintf("Update FixedAssetsLine set
                Qunatity_delivered = IFNULL(Qunatity_delivered,0) + %f,
                Quantity_toinvoice = Quantity-Qunatity_delivered
                where FixedAssetsLine.documentno='%s' 
                and FixedAssetsLine.documenttype='%s' 
                and FixedAssetsLine.entryno = %s",$qty,$salesoderno,55,$key);
                    
                    
                $sql[] =  sprintf("INSERT INTO FixedAssetsLine
                (documenttype,docdate,documentno,code,description,unitofmeasure,Quantity,UnitPrice,vatamount,invoiceamount,vatrate,inclusive,UOM) 
                (select '%s','%s','%s',code,description,unitofmeasure,'%s','%s','%s','%s',vatrate,inclusive,UOM from FixedAssetsLine 
                 where entryno=%s and documentno='%s' and documenttype='%s')","19",
                FormatDateForSQL($_POST['date']),$INVOICENO,$qty,$_POST['salesprice'][$key],
                $_POST['vatamount'][$key],$_POST['grossamount'][$key],$key,$salesoderno,55 );
              
              
              
                    $sql[]="INSERT INTO fixedassettrans 
                               (assetid,
                                transtype,
                                transno,
                                transdate,
                                periodno,
                                inputdate,
                                fixedassettranstype,
                                amount,
                                units)
                            (SELECT 
                               FixedAssetsLine.code,
                               55,
                               '".$INVOICENO."',
                               FixedAssetsLine.docdate,
                               '".$PeriodNo."',
                               FixedAssetsLine.docdate,
                               '" . _('Hired') . "',
                               (FixedAssetsLine.invoiceamount*currencies.rate),
                               '".($qty *-1)."'
                               FROM FixedAssetsLine 
                               join AssetsHeader on AssetsHeader.documentno=FixedAssetsLine.documentno
                               join currencies on currencies.currabrev=AssetsHeader.currencycode
                               where FixedAssetsLine.documentno='".$salesoderno."' "
                            . " and FixedAssetsLine.code='".$stcode."' "
                            . " and FixedAssetsLine.entryno='".$key."')";
                
                
                
                
         }
    }
    
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=PostDebtors($PeriodNo,$journal,$INVOICENO);
    $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=CustomerStatemet($journal,$INVOICENO);
    
    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
        
        
    } else {
        DB_Txn_Commit($db);
        
         DB_query("Update AssetsHeader set released=1 where documentno='".$_SESSION['DocumentNo']."'",$db);

         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);

    }
    
    

}



function PostDebtors($period,$journal,$doc){
   $SQL= sprintf("Insert into debtorsledger
       (date
      ,details
      ,flag
      ,invref
      ,acctfolio
      ,amount
      ,pamount
      ,curr_cod
      ,i_n_t
      ,journal
      ,typ
      ,vatamt
      ,systypes_1
      ,ledger
      ,period)
  (SELECT 
      AssetsHeader.docdate
      ,rtrim(FixedAssetsLine.description)  as narration,'I'
      ,AssetsHeader.documentno
      ,AssetsHeader.vendorcode
      ,SUM(FixedAssetsLine.invoiceamount) as Value
      ,0.00
      ,AssetsHeader.currencycode
      ,'I'
      ,'%s'
      ,'r'
      ,SUM(FixedAssetsLine.vatamount) as VAT
      ,AssetsHeader.documenttype
      ,AssetsHeader.postinggroup
      ,'%s'
  FROM AssetsHeader join FixedAssetsLine
        on AssetsHeader.documentno=FixedAssetsLine.documentno 
        and AssetsHeader.documenttype=FixedAssetsLine.documenttype 
        where AssetsHeader.documentno='%s' 
        group by
       AssetsHeader.documenttype
      ,AssetsHeader.documentno
      ,AssetsHeader.docdate
      ,AssetsHeader.vendorcode
      ,AssetsHeader.externaldocumentno
      ,AssetsHeader.postinggroup
      ,AssetsHeader.currencycode
      ,FixedAssetsLine.Quantity
      ,FixedAssetsLine.description
      ,FixedAssetsLine.code
      ,FixedAssetsLine.UOM)", $journal,$period,$doc);
   
   return $SQL;
}

function PostGL($period,$journal,$doc){
    
    $sql=SPRINTF("Insert into Generalledger
      (journalno
      ,Docdate
      ,period
      ,DocumentNo
      ,DocumentType
      ,accountcode
      ,balaccountcode
      ,VATaccountcode
      ,amount
      ,VATamount
      ,currencycode
      ,ExchangeRate
      ,cutomercode
      ,narration
      ,dimension
      ,dimension2)
      (select
       '%s'
      ,AssetsHeader.docdate
      ,'%s'
      ,AssetsHeader.documentno
      ,AssetsHeader.documenttype
      ,postinggroups.debtorsaccount
      ,fixedassetcategories.Equipment_hired_act
      ,fixedassetcategories.defaultgl_vat_act
      ,FixedAssetsLine.invoiceamount
      ,IFNULL(FixedAssetsLine.vatamount,0) as vatamount
      ,AssetsHeader.currencycode
      ,currencies.rate
      ,AssetsHeader.vendorcode
      ,(rtrim(FixedAssetsLine.description))  as narration
       ,'".$_POST['DimensionOne']."' 
       ,'".$_POST['DimensionTwo']."'
      from AssetsHeader join postinggroups on AssetsHeader.postinggroup=postinggroups.code
      join FixedAssetsLine on FixedAssetsLine.documentno = AssetsHeader.documentno 
      join fixedassets on FixedAssetsLine.code=fixedassets.assetid
      join fixedassetcategories on fixedassetcategories.categoryid=fixedassets.assetcategoryid
      join currencies on AssetsHeader.currencycode=currencies.currabrev
      where AssetsHeader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

function VATPostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into Generalledger
      (journalno
      ,Docdate
      ,period
      ,DocumentNo
      ,DocumentType
      ,accountcode
      ,balaccountcode
      ,VATaccountcode
      ,amount
      ,VATamount
      ,currencycode
      ,ExchangeRate
      ,cutomercode
      ,narration)
      (select
       '%s'
      ,AssetsHeader.docdate
      ,'%s'
      ,AssetsHeader.documentno
      ,AssetsHeader.documenttype
      ,fixedassetcategories.Equipment_hired_act
      ,fixedassetcategories.defaultgl_vat_act
      ,''
      ,FixedAssetsLine.vatamount
      ,0
      ,AssetsHeader.currencycode
      ,currencies.rate
      ,AssetsHeader.vendorcode
      ,(rtrim(FixedAssetsLine.description))  as narration
      from AssetsHeader join postinggroups on AssetsHeader.postinggroup=postinggroups.code
      join FixedAssetsLine on FixedAssetsLine.documentno = AssetsHeader.documentno 
      join fixedassets on FixedAssetsLine.code=fixedassets.assetid
      join fixedassetcategories on fixedassetcategories.categoryid=fixedassets.assetcategoryid
      join currencies on AssetsHeader.currencycode=currencies.currabrev
      where AssetsHeader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

Function CustomerStatemet($journal,$doc){
    $sql=sprintf("INSERT INTO CustomerStatement
           (Date
           ,Documentno
           ,Documenttype
           ,Accountno
           ,Grossamount
           ,JournalNo
           ,Dimension_One
           ,Dimension_Two
           ,Currency)
      (select
              AssetsHeader.docdate
             ,AssetsHeader.documentno
             ,AssetsHeader.documenttype
             ,AssetsHeader.vendorcode
             ,sum(FixedAssetsLine.invoiceamount)
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."' 
             ,AssetsHeader.currencycode    
             from AssetsHeader 
             join FixedAssetsLine on FixedAssetsLine.documentno = AssetsHeader.documentno 
           where AssetsHeader.documentno='%s' 
           Group by 
             AssetsHeader.docdate
             ,AssetsHeader.documentno
             ,AssetsHeader.documenttype
             ,AssetsHeader.vendorcode
             ,AssetsHeader.currencycode
             ) ",
            $journal,$doc);
    
    return $sql;
}

?>