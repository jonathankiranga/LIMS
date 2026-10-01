<?php


class Petteycash{
    var $Form;
    var $debitaccount;
    var $VATrate=0;
    var $CurrRate=0;
    var $VATamt=0;
    var $SQLARRAY=array();
    var $SupplierPG;
    var $SuppCurrency;
    var $VATGLaccount;
              
    function getrates($fromcurrency,$tocurrency){
        
        $FromRate =$this->ExtractRate($fromcurrency);
        $Torate =$this->ExtractRate($tocurrency);
        
        return ($Torate/$FromRate);
    }
    
    function ExtractRate($currbrieve){
        global $db;
        
        $SQLOldRate = "SELECT rate FROM currencies  WHERE currabrev = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];
                
        return $OldRate;
    }
    
    function Calculate(){
        $this->Form = $_POST;
        
        switch ($this->Form['accttype']) {
            case 0:
                if($this->Form['transtype']==1){
                   $this->getsupplier();
                }elseif($this->Form['transtype']==2){
                   $this->getbankgroups();
                }elseif($this->Form['transtype']==3){
                   $this->getdebtors();
                }
                break;
            default:
                $this->getglpostinggroups();
                break;
        }
        
      }
    
    function getdebtors(){
        Global $db,$PERIODNO;
        
         $sql="SELECT 
             debtors.curr_cod,
             postinggroups.debtorsaccount,
             currencies.rate,
             debtors.customerposting
           FROM debtors join postinggroups on code=customerposting
            join currencies on debtors.curr_cod=currencies.currabrev 
           where itemcode='".$this->Form['itemcode']."'" ;
          
            $resultindex= DB_query($sql,$db);
            $debtorsrow = DB_fetch_row($resultindex);
            $this->SuppCurrency = $debtorsrow[0];
            $this->debitaccount = $debtorsrow[1];
            $this->CurrRate = $debtorsrow[2];
            $this->SupplierPG = $debtorsrow[3];
            
            $this->VATamt = 0;
            $this->VATrate = 0; 
           
      }
    
    function getsupplier(){
        Global $db,$PERIODNO;
        
        
         $sql="SELECT 
             creditors.curr_cod,
             arpostinggroups.creditorsaccount,
             currencies.rate,
             creditors.supplierposting
           FROM creditors join arpostinggroups on code=supplierposting
            join currencies on creditors.curr_cod=currencies.currabrev 
           where itemcode='".$this->Form['itemcode']."'" ;
          
            $resultindex= DB_query($sql,$db);
            $debtorsrow = DB_fetch_row($resultindex);
            $this->SuppCurrency = $debtorsrow[0];
            $this->debitaccount = $debtorsrow[1];
            $this->CurrRate = $debtorsrow[2];
            $this->SupplierPG = $debtorsrow[3];
            
            $this->VATamt = 0;
            $this->VATrate = 0; 
           
      }
      
    function getglpostinggroups(){
       Global $db,$PERIODNO;
          
          $SQL="SELECT 
              accno,
              accdesc,
              postinggroup,
              GLpostinggroup.defaultgl_vat,
              vatcategory.vat
            FROM acct 
            join GLpostinggroup on acct.postinggroup=GLpostinggroup.code
            left join vatcategory on GLpostinggroup.vatcategory=vatcategory.vatc
            where acct.accno='".$this->Form['acctno']."'";
          
            $results = DB_query($SQL, $db);
            $debtorsrow = DB_fetch_row($results);
            $this->debitaccount = $this->Form['acctno'];
            $this->VATGLaccount = $debtorsrow[3];
            $this->VATrate =(float) $debtorsrow[4];
             
            if(isset($this->Form['Grossamount']) and $this->Form['Grossamount']>0){
               $this->VATamt = ($this->Form['Grossamount'])*($this->VATrate/(100+$this->VATrate));
            }else{
               $this->VATamt = ($this->Form['amount'])*($this->VATrate/100);
            }
      }
      
    function getbankgroups(){
       Global $db,$PERIODNO;
          
          $SQL="SELECT 
              accno,
              accdesc,
              postinggroup,
              GLpostinggroup.defaultgl_vat,
              vatcategory.vat
            FROM acct 
            join GLpostinggroup on acct.postinggroup=GLpostinggroup.code
            left join vatcategory on GLpostinggroup.vatcategory=vatcategory.vatc
            where acct.accno='".$this->Form['acctno']."'";
          
            $results = DB_query($SQL, $db);
            $debtorsrow = DB_fetch_row($results);
            $this->debitaccount = $this->Form['acctno'];
            $this->VATGLaccount = $debtorsrow[3];
            $this->VATrate = $debtorsrow[4];
             
            if(isset($this->Form['Grossamount']) and $this->Form['Grossamount']>0){
               $this->VATamt = ($this->Form['Grossamount'])*($this->VATrate/(100+$this->VATrate));
            }else{
               $this->VATamt = ($this->Form['amount'])*($this->VATrate/100);
            }
      }
      
    function POSTSupplier($JourNal){
       Global $db,$PERIODNO;
          
         $this->getsupplier();
       
         $translatedamount = ($this->Form['Grossamount']) * $this->getrates($this->SuppCurrency,$this->Form['currencycode']);
                      
          $this->SQLARRAY[]="INSERT INTO SupplierStatement (Date,Documentno,Documenttype,Accountno,Grossamount,JournalNo ,Currency)
           VALUES ('".FormatDateForSQL($this->Form['date']) ."','". $this->Form['documentno'] ."',2,'". $this->Form['itemcode']."',".
                  $translatedamount .",'".$JourNal."','".$this->Form['currencycode']."' )";
                    
          $this->SQLARRAY[]= sprintf("Insert into creditorsledger (date,details,flag,invref,acctfolio ,amount,pamount
               ,curr_cod,i_n_t,journal,typ,vatamt,systypes_1,ledger ,period) values ('%s','%s','%s','%s','%s',%f,%f,'%s','%s','%s','%s',%f,'%s','%s','%s')",
                FormatDateForSQL($this->Form['date']),'Paid By Pettey cash','CR',$this->Form['documentno'],$this->Form['itemcode'],
                $translatedamount,0,$this->Form['currencycode'],"Q", $JourNal, "Q", 0,2,$this->SupplierPG, $PERIODNO);
            
         $this->SQLARRAY[]= sprintf("INSERT INTO pettdoc
           (date,userid,moneyin,moneyout,balance,account,expensedetails,petteycashno,journal,transtype,shiftno)
            VALUES ('%s', '%s' ,0 , %f, 0,'%s', '%s', '%s','%s',4 ,'%s')",
            FormatDateForSQL($this->Form['date']),$_SESSION['UserID'],$this->Form['Grossamount']
            ,$this->Form['itemcode'],$this->Form['comments'],$this->Form['documentno'],$JourNal,$this->Form['shiftno']);
      
        $this->SQLARRAY[]= sprintf("INSERT INTO BankTransactions
           (bankcode,DocDate,doctype,DocumentNo,TransType,itemcode
           ,journal,amount,ClearedAmount,narrative,exchangerate)
            VALUES ('%s','%s',2 ,'%s','CR' ,'%s' ,'%s',%f ,%f ,'%s' ,%f )",$this->Form['Bank_Code'],
           FormatDateForSQL($this->Form['date']),$this->Form['documentno'],$this->Form['itemcode'],$JourNal  
         ,(0-$this->Form['Grossamount']),(0-$this->Form['Grossamount']),$this->Form['comments'],
          $this->ExtractRate($this->Form['currencycode']));
        
        
            $this->SQLARRAY[] = sprintf("INSERT INTO Generalledger
           (journalno,Docdate,period,DocumentNo,DocumentType
           ,accountcode,balaccountcode,amount,currencycode,ExchangeRate,suppliercode ,bankcode
           ,reconcilled,narration,ExchangeRateDiff,VATaccountcode,VATamount,dimension,dimension2)
           VALUES ('%s','%s' ,'%s','%s',2,'%s','%s' ,%f ,'%s' ,%f ,' ','%s' ,1
           ,'%s' ,0 ,' ' ,0 ,' ' ,' ')", $JourNal ,FormatDateForSQL($this->Form['date']), $PERIODNO ,
           $this->Form['documentno'],$this->debitaccount,$this->Form['BankPostingGL'],$this->Form['Grossamount'],
           $this->Form['currencycode'],$this->ExtractRate($this->Form['currencycode']),$this->Form['BankPostingGL'],
           $this->Form['comments']);
      
          
      }
    
    function POSTGL($JourNal){
        Global $db,$PERIODNO;
          
        if($this->Form['transtype']==4){    

                    $this->SQLARRAY[]= sprintf("INSERT INTO pettdoc
                      (date,userid,moneyin,moneyout,balance,account,expensedetails,petteycashno,journal,transtype,shiftno)
                       VALUES ('%s', '%s' ,0 , %f, 0,'%s', '%s', '%s','%s',5 ,'%s')",
                       FormatDateForSQL($this->Form['date']),$_SESSION['UserID'],$this->Form['Grossamount']
                       ,$this->Form['acctno'],$this->Form['comments'],$this->Form['documentno'],$JourNal,$this->Form['shiftno']);

                   $this->SQLARRAY[]= sprintf("INSERT INTO BankTransactions
                      (bankcode,DocDate,doctype,DocumentNo,TransType,itemcode
                      ,journal,amount,ClearedAmount,narrative,exchangerate)
                       VALUES ('%s','%s',2 ,'%s','GL' ,'%s' ,'%s',%f ,%f ,'%s' ,%f )",$this->Form['Bank_Code'],
                      FormatDateForSQL($this->Form['date']),$this->Form['documentno'],$this->Form['acctno'],$JourNal,  
                    (0-$this->Form['Grossamount']),(0-$this->Form['Grossamount']),$this->Form['comments'], $this->ExtractRate($this->Form['currencycode']));


                    $this->SQLARRAY[] = sprintf("INSERT INTO Generalledger
                     (journalno,Docdate,period,DocumentNo,DocumentType
                     ,accountcode,balaccountcode,amount,currencycode,ExchangeRate,suppliercode ,bankcode
                     ,reconcilled,narration,ExchangeRateDiff,VATaccountcode,VATamount,dimension,dimension2)
                     VALUES ('%s','%s' ,'%s','%s',2,'%s','%s' ,%f ,'%s' ,%f ,' ','%s' ,1,'%s' ,0 ,'%s' ,%f ,'%s' ,'%s')", $JourNal ,FormatDateForSQL($this->Form['date']), $PERIODNO ,
                     $this->Form['documentno'],$this->Form['acctno'],$this->Form['BankPostingGL'],$this->Form['Grossamount'],
                     $this->Form['currencycode'],$this->ExtractRate($this->Form['currencycode']),$this->Form['BankPostingGL'],
                     $this->Form['comments'],$this->Form['VATGLaccount'],$this->Form['VATamount'],$this->Form['DimensionOne'],$this->Form['DimensionTwo']);

                    if(mb_strlen($this->Form['VATGLaccount'])>0){
                        
                        $this->SQLARRAY[] = sprintf("INSERT INTO Generalledger
                        (journalno,Docdate,period,DocumentNo,DocumentType
                        ,accountcode,balaccountcode,amount,currencycode,ExchangeRate,suppliercode ,bankcode
                        ,reconcilled,narration,ExchangeRateDiff,VATaccountcode,VATamount,dimension,dimension2)
                        VALUES ('%s','%s' ,'%s','%s',2,'%s','%s' ,%f ,'%s' ,%f ,' ','%s' ,1,'%s' ,0 ,' ' ,0 ,' ' ,' ')", $JourNal ,FormatDateForSQL($this->Form['date']), $PERIODNO ,
                        $this->Form['documentno'],$this->Form['VATGLaccount'],$this->Form['acctno'],$this->Form['VATamount'],
                        $this->Form['currencycode'],$this->ExtractRate($this->Form['currencycode']),$this->Form['BankPostingGL'],
                        $this->Form['comments']);
                        
                    }
           
            }else{

                $this->SQLARRAY[]= sprintf("INSERT INTO pettdoc
                (date,userid,moneyin,moneyout,balance,account,expensedetails,petteycashno,journal,transtype,shiftno)
                VALUES ('%s', '%s' ,%f , 0, 0,'%s', '%s', '%s','%s',4 ,'%s')", FormatDateForSQL($this->Form['date']),$_SESSION['UserID'],
                $this->Form['Grossamount'] * $this->getrates($this->Form['currencycodeFrom'],$this->Form['currencycode'])
                ,$this->Form['itemcode'],$this->Form['comments'],$this->Form['documentno'],$JourNal,$this->Form['shiftno']);

                $this->SQLARRAY[]= sprintf("INSERT INTO BankTransactions
                (bankcode,DocDate,doctype,DocumentNo,TransType,itemcode,journal,amount,ClearedAmount,narrative,exchangerate)
                 VALUES ('%s','%s',2 ,'%s','CR' ,'%s' ,'%s',%f ,%f ,'%s' ,%f )",$this->Form['Bank_Code'],
                FormatDateForSQL($this->Form['date']),$this->Form['documentno'],$this->Form['itemcode'],$JourNal,  
                $this->Form['Grossamount'] * $this->getrates($this->Form['currencycodeFrom'],$this->Form['currencycode'])
                ,($this->Form['Grossamount']),$this->Form['comments'],$this->ExtractRate($this->Form['currencycode']));


                 $this->SQLARRAY[]= sprintf("INSERT INTO BankTransactions
                 (bankcode,DocDate,doctype,DocumentNo,TransType,itemcode,journal,amount,ClearedAmount,narrative,exchangerate)
                 VALUES ('%s','%s',2 ,'%s','CR' ,'%s' ,'%s',%f ,%f ,'%s' ,%f )",$this->Form['Bank_Code2'],
                 FormatDateForSQL($this->Form['date']),$this->Form['documentno'],$this->Form['itemcode'],$JourNal,  
                 (0-$this->Form['Grossamount']),(0-$this->Form['Grossamount']),$this->Form['comments'], $this->ExtractRate($this->Form['currencycodeFrom']));


                $this->SQLARRAY[] = sprintf("INSERT INTO Generalledger
                (journalno,Docdate,period,DocumentNo,DocumentType,accountcode,balaccountcode,amount,currencycode,ExchangeRate,suppliercode ,bankcode
                ,reconcilled,narration,ExchangeRateDiff,VATaccountcode,VATamount,dimension,dimension2)
                VALUES ('%s','%s' ,'%s','%s',2,'%s','%s' ,%f ,'%s' ,%f ,' ','%s' ,1,'%s' ,0 ,' ' ,0 ,' ' ,' ')", $JourNal ,FormatDateForSQL($this->Form['date']), $PERIODNO ,
                $this->Form['documentno'],$this->Form['BankPostingGL'],$this->Form['BankfromPostingGL'],
                $this->Form['Grossamount'] * $this->getrates($this->Form['currencycodeFrom'],$this->Form['currencycode']),
                $this->Form['currencycode'], $this->ExtractRate($this->Form['currencycode']),$this->Form['BankPostingGL'], $this->Form['comments']);

}
            
    }
        
    function POSTdebtor($JourNal){
       Global $db,$PERIODNO;
          
         $this->getdebtors();
       
         $translatedamount = ($this->Form['Grossamount']) * $this->getrates($this->SuppCurrency,$this->Form['currencycode']);
                      
          $this->SQLARRAY[]="INSERT INTO CustomerStatement (Date,Documentno,Documenttype,Accountno,Grossamount,JournalNo ,Currency)
           VALUES ('".FormatDateForSQL($this->Form['date']) ."','". $this->Form['documentno'] ."',2,'". $this->Form['itemcode']."',".
                  $translatedamount .",'".$JourNal."','".$this->Form['currencycode']."' )";
                    
          $this->SQLARRAY[]= sprintf("Insert into debtorsledger (date,details,flag,invref,acctfolio ,amount,pamount
               ,curr_cod,i_n_t,journal,typ,vatamt,systypes_1,ledger ,period) values ('%s','%s','%s','%s','%s',%f,%f,'%s','%s','%s','%s',%f,'%s','%s','%s')",
                FormatDateForSQL($this->Form['date']),'Paid By Pettey cash','CR',$this->Form['documentno'],$this->Form['itemcode'],
                $translatedamount,0,$this->Form['currencycode'],"Q", $JourNal, "Q", 0,2,$this->SupplierPG, $PERIODNO);
            
         $this->SQLARRAY[]= sprintf("INSERT INTO pettdoc
           (date,userid,moneyin,moneyout,balance,account,expensedetails,petteycashno,journal,transtype,shiftno)
            VALUES ('%s', '%s' ,0 , %f, 0,'%s', '%s', '%s','%s',4 ,'%s')",
            FormatDateForSQL($this->Form['date']),$_SESSION['UserID'],$this->Form['Grossamount']
            ,$this->Form['itemcode'],$this->Form['comments'],$this->Form['documentno'],$JourNal,$this->Form['shiftno']);
      
        $this->SQLARRAY[]= sprintf("INSERT INTO BankTransactions
           (bankcode,DocDate,doctype,DocumentNo,TransType,itemcode
           ,journal,amount,ClearedAmount,narrative,exchangerate)
            VALUES ('%s','%s',2 ,'%s','CR' ,'%s' ,'%s',%f ,%f ,'%s' ,%f )",$this->Form['Bank_Code'],
           FormatDateForSQL($this->Form['date']),$this->Form['documentno'],$this->Form['itemcode'],$JourNal  
         ,(0-$this->Form['Grossamount']),(0-$this->Form['Grossamount']),$this->Form['comments'],
          $this->ExtractRate($this->Form['currencycode']));
        
        
            $this->SQLARRAY[] = sprintf("INSERT INTO Generalledger
           (journalno,Docdate,period,DocumentNo,DocumentType
           ,accountcode,balaccountcode,amount,currencycode,ExchangeRate,suppliercode ,bankcode
           ,reconcilled,narration,ExchangeRateDiff,VATaccountcode,VATamount,dimension,dimension2)
           VALUES ('%s','%s' ,'%s','%s',2,'%s','%s' ,%f ,'%s' ,%f ,' ','%s' ,1
           ,'%s' ,0 ,' ' ,0 ,' ' ,' ')", $JourNal ,FormatDateForSQL($this->Form['date']), $PERIODNO ,
           $this->Form['documentno'],$this->debitaccount,$this->Form['BankPostingGL'],$this->Form['Grossamount'],
           $this->Form['currencycode'],$this->ExtractRate($this->Form['currencycode']),$this->Form['BankPostingGL'],
           $this->Form['comments']);
      
          
      }
      
    function POSTDeposit($JourNal){
        Global $db,$PERIODNO;
          
        if($this->Form['transtype']==5){    

                $this->SQLARRAY[]= sprintf("INSERT INTO pettdoc
                (date,userid,moneyin,moneyout,balance,account,expensedetails,petteycashno,journal,transtype,shiftno)
                VALUES ('%s', '%s' ,0 , %f, 0,'%s', '%s', '%s','%s',5 ,'%s')", FormatDateForSQL($this->Form['date']),$_SESSION['UserID'],
                $this->Form['Grossamount'] * $this->getrates($this->Form['currencycodeFrom'],$this->Form['currencycode'])
                ,$this->Form['itemcode'],$this->Form['comments'],$this->Form['documentno'],$JourNal,$this->Form['shiftno']);

                $this->SQLARRAY[]= sprintf("INSERT INTO BankTransactions
                (bankcode,DocDate,doctype,DocumentNo,TransType,itemcode,journal,amount,ClearedAmount,narrative,exchangerate)
                 VALUES ('%s','%s',2 ,'%s','CR' ,'%s' ,'%s',%f ,%f ,'%s' ,%f )",$this->Form['Bank_Code'],
                FormatDateForSQL($this->Form['date']),$this->Form['documentno'],$this->Form['itemcode'],$JourNal,  
                (0-$this->Form['Grossamount']) * $this->getrates($this->Form['currencycodeFrom'],$this->Form['currencycode'])
                ,(0-$this->Form['Grossamount']),$this->Form['comments'],$this->ExtractRate($this->Form['currencycode']));


                 $this->SQLARRAY[]= sprintf("INSERT INTO BankTransactions
                 (bankcode,DocDate,doctype,DocumentNo,TransType,itemcode,journal,amount,ClearedAmount,narrative,exchangerate)
                 VALUES ('%s','%s',2 ,'%s','CR' ,'%s' ,'%s',%f ,%f ,'%s' ,%f )",$this->Form['Bank_Code2'],
                 FormatDateForSQL($this->Form['date']),$this->Form['documentno'],$this->Form['itemcode'],$JourNal,  
                 ($this->Form['Grossamount']),($this->Form['Grossamount']),$this->Form['comments'], $this->ExtractRate($this->Form['currencycodeFrom']));


                $this->SQLARRAY[] = sprintf("INSERT INTO Generalledger
                (journalno,Docdate,period,DocumentNo,DocumentType,accountcode,balaccountcode,amount,currencycode,ExchangeRate,suppliercode ,bankcode
                ,reconcilled,narration,ExchangeRateDiff,VATaccountcode,VATamount,dimension,dimension2)
                VALUES ('%s','%s' ,'%s','%s',2,'%s','%s' ,%f ,'%s' ,%f ,' ','%s' ,1,'%s' ,0 ,' ' ,0 ,' ' ,' ')", 
                $JourNal ,FormatDateForSQL($this->Form['date']), $PERIODNO ,
                $this->Form['documentno'],$this->Form['BankfromPostingGL'],$this->Form['BankPostingGL'],
                $this->Form['Grossamount'] * $this->getrates($this->Form['currencycodeFrom'],$this->Form['currencycode']),
                $this->Form['currencycode'], $this->ExtractRate($this->Form['currencycode']),$this->Form['BankPostingGL'], $this->Form['comments']);

}
            
    }
       
    Function GetShiftno($usrid){
    global $db;
    
    $ResultIndex = DB_query(sprintf("Select IFNULL(Currentshiftno,0) FROM www_users where userid='%s'",$usrid), $db);
    $rows =DB_fetch_row($ResultIndex);
    
        return  $rows[0];
     }
}



