<?php

/* $Id: SQL_CommonFunctions.inc 4973 2012-02-26 09:19:38Z tim_schofield $*/

/* Common SQL Functions */


Function GetNextTransNo($TransType, &$odb){
    global $db;
    /* SQL to get the next transaction number these are maintained in the table SysTypes - Transaction Types
    Also updates the transaction number
    10 sales invoice
    11 sales credit note
    12 sales receipt
    etc
    *
    
    */
    $SQL = SPRINTF("UPDATE systypes_1 SET typeno = typeno + 1  WHERE typeid = '%s' ",$TransType);
    $ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The transaction number could not be incremented');
    $DbgMsg =  _('The following SQL to increment the transaction number was used');
    $UpdTransNoResult = DB_System($SQL,$db,$ErrMsg,$DbgMsg);
    
    
    $SQL = SPRINTF("SELECT IFNULL(prefix,'') as prefix , typeno FROM systypes_1 WHERE typeid='%s'",$TransType);
    $ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': <BR>' . _('The next transaction number could not be retrieved from the database because');
    $DbgMsg =  _('The following SQL to retrieve the transaction number was used');
    $GetTransNoResult = DB_System($SQL,$db,$ErrMsg,$DbgMsg);
    $myrow = DB_fetch_row($GetTransNoResult);

   $prefix = $myrow[0];
   $nexno  = $myrow[1];
    return $prefix . $nexno ;
}


Function GetTempNextNo($TransType){
    global $db;
    /* SQL to get the next transaction number these are maintained in the table SysTypes - Transaction Types
Also updates the transaction number

10 sales invoice
11 sales credit note
12 sales receipt
etc
*
*/
    $SQL = SPRINTF("SELECT IFNULL(prefix,'') as prefix , 1+typeno FROM systypes_1 WHERE typeid='%s'",$TransType);
    $ErrMsg =  _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': <BR>' . _('The next transaction number could not be retrieved from the database because');
    $DbgMsg =  _('The following SQL to retrieve the transaction number was used');
    $GetTransNoResult = DB_System($SQL,$db,$ErrMsg,$DbgMsg);
    $myrow = DB_fetch_row($GetTransNoResult);

   $prefix = trim($myrow[0]);
   $nexno  = trim($myrow[1]);
    return $prefix . $nexno ;
   
}



?>
