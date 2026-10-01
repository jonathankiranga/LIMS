<?php

$deductionarray['0']='Deduction';
$deductionarray['1']='Allowance or Non-Cash Benefit';

$pensionarray['0']='Standard Item(eg NHIF,LOAN etc)';
$pensionarray['1']='TAX item(eg PAYE)';
$pensionarray['2']='Pension Scheme Item(eg NSSF)';
$pensionarray['3']='Non-Cash Item';

$hasatablearray['0']="NO" ;
$hasatablearray['1']="YES Graduated scale(eg PAYE)" ;
$hasatablearray['2']="YES as percent(eg NSSF)" ;

$reliefname['0']='Personal Relief';

if(isset($_POST['savenotable'])){
    
    DB_query("DELETE FROM prlitemaintenace WHERE code='".DB_escape_string($_POST['productcode'])."'",$db);
    
    if($_POST['savenotable']=='Save'){
        $sql = sprintf("INSERT INTO prlitemaintenace
           (code, description, percentageamount, taxableamount, ceiling)
           VALUES ('%s','%s',%f,%f,%f)", 
           DB_escape_string($_POST['productcode']),
           DB_escape_string($_POST['description']), 
           $_POST['percentageamount'],
           $_POST['taxableamount'], $_POST['ceiling']);
        
        DB_query($sql,$db);
    }
    unset($_GET['notable']);
}

if(isset($_POST['createtax'])){
    $sql = sprintf("INSERT INTO prlitemaintenace
    (code, description, taxband, taxableamount, percentageamount, ceiling)
    values ('%s','%s','1','%f','%f','f') ",
    DB_escape_string($_POST['code']),DB_escape_string($_POST['description']),$_POST['flatrate'],$_POST['percentage'],$_POST['ceiling']);

    DB_query($sql,$db);

    unset($_POST['createtax']);

} elseif (isset($_POST['edittax'])){
    $sql = sprintf("UPDATE prlitemaintenace SET taxableamount='%f',
     percentageamount='%f', ceiling='%f' WHERE code='%s' ",
     $_POST['flatrate'],$_POST['percentage'],$_POST['ceiling'],DB_escape_string($_POST['code']));

    DB_query($sql,$db);

    unset($_POST['edittax']);

} elseif(isset($_POST['createtax1'])){
    $sql = sprintf("INSERT INTO prlitemaintenace
    (code, description, taxband, lowerlimit, upperlimit, percentageamount, ceiling)
    values ('%s','%s','%s','%f','%f',%f,%f) ",
    DB_escape_string($_POST['code']),DB_escape_string($_POST['description']),$_POST['taxband'],
    $_POST['rangefrom'],$_POST['rangeto'],$_POST['percentage'],$_POST['ceiling']);

    DB_query($sql,$db);

    unset($_POST['createtax1']);

} elseif (isset($_POST['edittax1'])){
    $sql = sprintf("UPDATE prlitemaintenace SET
    lowerlimit='%f', upperlimit='%f', percentageamount='%f', taxband='%s', ceiling='%s'
    WHERE code='%s' AND taxband='%s' ",
    $_POST['rangefrom'],$_POST['rangeto'],
    $_POST['percentage'],$_POST['newtaxband'],$_POST['ceiling'],DB_escape_string($_POST['code']),$_POST['taxband']);

    DB_query($sql,$db);

    unset($_POST['edittax1']);

} elseif (isset($_POST['deletetax1'])){
    $sql = sprintf("DELETE FROM prlitemaintenace WHERE code='%s' AND taxband='%s' ", DB_escape_string($_POST['code']),$_POST['taxband']);
    DB_query($sql,$db);

    unset($_POST['deletetax1']);

} elseif (isset($_POST['createtaxrelief'])) {
    $sql = sprintf("INSERT INTO prlreliefs
       (productcode, name, reliefamount, reliefaspercent, maxrelief, type)
       VALUES ('%s','%s',%s,%s,%s,%s)",
        DB_escape_string($_POST['productcode']),
        DB_escape_string($_POST['name']),
        $_POST['reliefamount'],
        $_POST['reliefaspercent'],
        $_POST['maxrelief'],
        $_POST['typename']);

    DB_query($sql,$db);
    unset($_POST['createtaxrelief']);

} elseif (isset($_POST['edittaxrelief'])) {
    $sql = sprintf("UPDATE prlreliefs SET name='%s', type='%s',
    reliefamount=%s, reliefaspercent=%s, maxrelief=%s
    WHERE code='%s'",
    DB_escape_string($_POST['name']),$_POST['typename'], 
    $_POST['reliefamount'], $_POST['reliefaspercent'], $_POST['maxrelief'], 
    DB_escape_string($_POST['ReliefCode']));

    DB_query($sql,$db);
    unset($_POST['edittaxrelief']);
}

if(isset($_POST['submitproduct'])){
    $sql = sprintf("INSERT INTO prlproducts (description, glaccountlink, deduction, hastable,
        employerfactor, pens_tax, membership_no)
        VALUES ('%s','','%s','%s','%s','%s','%s')",
        DB_escape_string($_POST['description']), $_POST['deduction'],
        $_POST['hastable'],$_POST['employerfactor'],
        $_POST['pens_tax'],$_POST['membership_no']);
    DB_query($sql,$db);

    unset($_POST['description']);
    unset($_POST['glaccountlink']);
    unset($_POST['deduction']);
    unset($_POST['submitproduct']);
}

if(isset($_POST['editproduct'])){
    $sql = sprintf("UPDATE prlproducts SET
        description='%s',
        deduction='%s',
        hastable='%s',
        employerfactor='%s',
        pens_tax='%s',
        membership_no='%s'
        WHERE code='%s'",
        DB_escape_string($_POST['description']),
        $_POST['deduction'],
        $_POST['hastable'],
        $_POST['employerfactor'],
        $_POST['pens_tax'],
        DB_escape_string($_POST['membership_no']),
        DB_escape_string($_POST['code']));
    DB_query($sql,$db);

    unset($_POST['description']);
    unset($_POST['glaccountlink']);
    unset($_POST['deduction']);
    unset($_POST['code']);
    unset($_POST['editproduct']);
}

?>