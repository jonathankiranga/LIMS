<?php

class DimensionsBudget {
    var $dimensionone;
    var $dimensiontwo;
    var $ResultsBudget=array();
    var $FinancialPeriod;
    Var $StartDate;
    var $EndDate;
    
    function __construct() {
        Global $db;
        $this->ResultsBudget['Budget']=0;
        
        $ResultIndex=DB_query("select Min(start_date),max(end_date),`periodno` "
                . "from FinancialPeriods where closed=0 Group by `periodno`",$db);
        $Rows=DB_fetch_row($ResultIndex);
        
        $this->StartDate = $Rows[0];
        $this->EndDate = $Rows[1];
        $this->FinancialPeriod = $Rows[2];
        
        
    }
    
    Function GetBudet($dim='',$dim2=''){
        Global $db;
        
        if(mb_strlen($dim)==0){
         $SQL="SELECT `amount` FROM `Budgets` "
            . "where  `periodno`='".$this->FinancialPeriod."' and `dimecode2`='".$dim2."'";
                
        }elseif(mb_strlen($dim2)==0){
         $SQL="SELECT `amount` FROM `Budgets` "
            . "where  `periodno`='".$this->FinancialPeriod."' and `dimecode`='".$dim."'";
         
        } else {
            $SQL="SELECT `amount` FROM `Budgets` "
               . " where  `periodno`='".$this->FinancialPeriod."' and `dimecode`='".$dim."' and `dimecode2`='".$dim2."'";
        }
        
        $ResultIndex=DB_query($SQL, $db);
        $rows = DB_fetch_row($ResultIndex);
    
    $this->ResultsBudget['Budget'] += $rows[0];
        
    }
       
    function getcommittedboth($dim1='',$dim2=''){
        Global $db;
        
        $SQL="SELECT 
                  sum(PurchaseLine.invoiceamount) as amount
              FROM `PurchaseHeader` join PurchaseLine on PurchaseHeader.documenttype=PurchaseLine.documenttype 
                  and PurchaseHeader.documentno=PurchaseLine.documentno
                  where PurchaseHeader.released=1 and PurchaseHeader.documenttype=18 
                  and (PurchaseHeader.Dimension_1='".$dim1."'
                  and PurchaseHeader.Dimension_2='".$dim2."')";
                
                $ResultIndex=DB_query($SQL,$db);
                $rows = DB_fetch_row($ResultIndex);
       
        $this->ResultsBudget['Committement'] = $rows[0];
    }
    
    function getcommitted1($dim1=''){
        Global $db;
        
        $SQL="SELECT 
                  sum(PurchaseLine.invoiceamount)as  amount
              FROM `PurchaseHeader` join PurchaseLine on PurchaseHeader.documenttype=PurchaseLine.documenttype 
                  and PurchaseHeader.documentno=PurchaseLine.documentno
                  where PurchaseHeader.released=1 and PurchaseHeader.documenttype=18 
                  and (PurchaseHeader.Dimension_1='".$dim1."')";
                
                $ResultIndex=DB_query($SQL,$db);
                $rows = DB_fetch_row($ResultIndex);
       
        $this->ResultsBudget['Committement'] = $rows[0];
    }
       
    function getcommitted2($dim2=''){
      Global $db;
      
        $SQL="SELECT 
                  sum(PurchaseLine.invoiceamount) as  amount
              FROM `PurchaseHeader` join PurchaseLine on PurchaseHeader.documenttype=PurchaseLine.documenttype 
                  and PurchaseHeader.documentno=PurchaseLine.documentno
                  where PurchaseHeader.released=1 and PurchaseHeader.documenttype=18 
                  and (PurchaseHeader.Dimension_2='".$dim2."')";
        
                $ResultIndex=DB_query($SQL,$db);
                $rows = DB_fetch_row($ResultIndex);
        
         $this->ResultsBudget['Committement'] = $rows[0];
    }
        
    function GetExpenses1($dime1=''){
        Global $db;
         $SQL="SELECT sum(`amount`) FROM `Generalledger` "
            . " where docdate between '".$this->StartDate."'  and '".$this->EndDate."' "
                 . " and `dimension`='".$dime1."' ";
          
          $ResultIndex=DB_query($SQL,$db);
          $rows = DB_fetch_row($ResultIndex); 
         
          $this->ResultsBudget['Expensed'] = $rows[0];
    }
       
    function GetExpenses2($dime2=''){
        Global $db;
          $SQL="SELECT sum(`amount`) FROM `Generalledger` "
             . " where docdate between '".$this->StartDate."'  and '".$this->EndDate."' "
                 . " and `dimension2`='".$dime2."'";
          
          $ResultIndex=DB_query($SQL,$db);
          $rows = DB_fetch_row($ResultIndex); 
         
          $this->ResultsBudget['Expensed'] = $rows[0];
        
    }
       
    function GetExpensesBoth($dime1='',$dime2=''){
        Global $db;
        
         $SQL="SELECT sum(`amount`) FROM `Generalledger` "
                . " where docdate between'".$this->StartDate."'  and '".$this->EndDate."' "
                 . " and `dimension`='".$dime1."' "
                 . " and `dimension2`='".$dime2."'";
         
          $ResultIndex=DB_query($SQL,$db);
          $rows = DB_fetch_row($ResultIndex); 
         
          $this->ResultsBudget['Expensed'] = $rows[0];
        
    }
      
    function Calculate($Dimension_1='',$Dimension_2=''){
        $this->ResultsBudget['Budget'] = 0;
        $this->ResultsBudget['Committement'] = 0;
        $this->ResultsBudget['Expensed'] = 0;
        
        if(mb_strlen($Dimension_1)>0 and mb_strlen($Dimension_2)>0){
            
            $this->GetBudet($Dimension_1,$Dimension_2);
            $this->getcommittedboth($Dimension_1,$Dimension_2);
            $this->GetExpensesBoth($Dimension_1,$Dimension_2);
            
        }elseif(mb_strlen($Dimension_1)>0 and mb_strlen($Dimension_2)==0){
            
            $this->GetBudet($Dimension_1);
            $this->getcommitted1($Dimension_1);
            $this->GetExpenses1($Dimension_1);
            
        }elseif(mb_strlen($Dimension_1)==0 and mb_strlen($Dimension_2)>0){
            
            $this->GetBudet('',$Dimension_2);
            $this->getcommitted2($Dimension_2);
            $this->GetExpenses2($Dimension_2);
            
        }
        
        return $this->ResultsBudget;
    }
    
    
}






?>