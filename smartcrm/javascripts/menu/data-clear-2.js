/*
   Deluxe Menu Data File
   Created by Deluxe Tuner v3.2
   http://deluxe-menu.com
*/


// -- Deluxe Tuner Style Names
var itemStylesNames=[];
var menuStylesNames=[];
// -- End of Deluxe Tuner Style Names

//--- Common
var isHorizontal=1;
var smColumns=1;
var smOrientation=0;
var dmRTL=0;
var pressedItem=-2;
var itemCursor="pointer";
var itemTarget="mainContentIFrame";
var statusString="link";
var blankImage="images/blank.gif";
var pathPrefix_img="javascripts/menu/";
var pathPrefix_link="";

//--- Dimensions
var menuWidth="250px";
var menuHeight="";
var smWidth="";
var smHeight="";

//--- Positioning
var absolutePos=0;
var posX="10px";
var posY="10px";
var topDX=0;
var topDY=0;
var DX=0;
var DY=0;
var subMenuAlign="left";
var subMenuVAlign="top";

//--- Font
var fontStyle=["normal 14px Tahoma","normal 14px Tahoma"];
var fontColor=["#000000","#FFFFFF"];
var fontDecoration=["none","none"];
var fontColorDisabled="#AAAAAA";

//--- Appearance
var menuBackColor="#FCFCFC";
var menuBackImage="";
var menuBackRepeat="repeat";
var menuBorderColor="#55A1FF";
var menuBorderWidth=0;
var menuBorderStyle="solid";

//--- Item Appearance
var itemBackColor=["transparent","#1665CB"];
var itemBackImage=["images/sm_back_xp.gif","images/sm_back_xp2.gif"];
var beforeItemImage=["",""];
var afterItemImage=["",""];
var beforeItemImageW="";
var afterItemImageW="";
var beforeItemImageH="";
var afterItemImageH="";
var itemBorderWidth=0;
var itemBorderColor=["#FCEEB0","#4C99AB"];
var itemBorderStyle=["solid","solid"];
var itemSpacing=0;
var itemPadding="3px 3px 3px 5px";
var itemAlignTop="left";
var itemAlign="left";

//--- Icons
var iconTopWidth=20;
var iconTopHeight=16;
var iconWidth=20;
var iconHeight=16;
var arrowWidth=7;
var arrowHeight=7;
var arrowImageMain=["images/arr_black_2.gif","images/arr_white_2.gif"];
var arrowWidthSub=0;
var arrowHeightSub=0;
var arrowImageSub=["images/arr_black_2.gif","images/arr_white_2.gif"];

//--- Separators
var separatorImage="images/sep_xp.gif";
var separatorWidth="90%";
var separatorHeight="3px";
var separatorAlignment="center";
var separatorVImage="";
var separatorVWidth="3px";
var separatorVHeight="100%";
var separatorPadding="5px";

//--- Floatable Menu
var floatable=0;
var floatIterations=6;
var floatableX=1;
var floatableY=1;
var floatableDX=15;
var floatableDY=15;

//--- Movable Menu
var movable=0;
var moveWidth=12;
var moveHeight=20;
var moveColor="#DECA9A";
var moveImage="";
var moveCursor="move";
var smMovable=0;
var closeBtnW=15;
var closeBtnH=15;
var closeBtn="";

//--- Transitional Effects & Filters
var transparency="100";
var transition=24;
var transOptions="";
var transDuration=350;
var transDuration2=200;
var shadowLen=4;
var shadowColor="#B1B1B1";
var shadowTop=1;

//--- CSS Support (CSS-based Menu)
var cssStyle=0;
var cssSubmenu="";
var cssItem=["",""];
var cssItemText=["",""];

//--- Advanced
var dmObjectsCheck=0;
var saveNavigationPath=1;
var showByClick=0;
var noWrap=1;
var smShowPause=200;
var smHidePause=1000;
var smSmartScroll=1;
var topSmartScroll=0;
var smHideOnClick=1;
var dm_writeAll=0;
var useIFRAME=0;
var dmSearch=0;

//--- AJAX-like Technology
var dmAJAX=0;
var dmAJAXCount=0;
var ajaxReload=0;

//--- Dynamic Menu
var dynamic=0;

//--- Popup Menu
var popupMode=0;

//--- Keystrokes Support
var keystrokes=1;
var dm_focus=1;
var dm_actKey=113;

//--- Sound
var onOverSnd="";
var onClickSnd="";

var itemStyles = [
];
var menuStyles = [
];

var menuItems = [
       ["Customers","", "images/icon_xp2_7.gif", "images/icon_xp2_7o.gif", "", "", "", "", "", "", "", ],
        ["|New","", "", "", "", "", "", "", "", "", "", ],
            ["||New Customer","Customer.php", "", "", "", "", "", "", "", "", "", ],
            ["||Customer Posting Group","CustomersPostingGroups.php", "", "", "", "", "", "", "", "", "", ],
        ["|Items","", "", "", "", "", "", "", "", "", "", ],
            ["||Download Images From Firebase","Firebaseconnection.php?firebase=yes", "", "", "", "", "", "", "", "", "", ],
            ["||Upload from Computer Images","Firebaseconnection.php", "", "", "", "", "", "", "", "", "", ],
            ["||Quotation","SalesQuotation.php", "", "", "", "", "", "", "", "", "", ],
            ["||Sales Order","SalesOder.php", "", "", "", "", "", "", "", "", "", ],
            ["||Sales Delivery","SalesDelivery.php", "", "", "", "", "", "", "", "", "", ],
            ["||Sales Invoice","SalesInvoice.php", "", "", "", "", "", "", "", "", "", ],
            ["||Credit Note","SalesDeliveryList.php", "", "", "", "", "", "", "", "", "", ],
            ["||Request to Issue Sample","salessamples.php", "", "", "", "", "", "", "", "", "", ],
        ["|Service","", "", "", "", "", "", "", "", "", "", ],
             ["||Service Invoicing","EnterSalesBills.php", "", "", "", "", "", "", "", "", "", ],
             ["||Print Service Invoicing","PDFenterSalesbills.php", "", "", "", "", "", "", "", "", "", ],
        ["|Receive Payments","", "", "", "", "", "", "", "", "", "", ],
             ["||Receipts","receipts.php", "", "", "", "", "", "", "", "", "", ],    
             ["||Invoice Allocation","ReceitsAllocation.php", "", "", "", "", "", "", "", "", "", ],  
             ["||Auto Allocation","autoallocate.php", "", "", "", "", "", "", "", "", "", ],  
             
        ["|Print","", "", "", "", "", "", "", "", "", "", ],
            ["||Cash Receipts","PDFprintreceipt.php", "", "", "", "", "", "", "", "", "", ],
            ["||Customer Statements","PrintCustStatements.php", "", "", "", "", "", "", "", "", "", ],
            ["||Ageing Statements","PrintCustAgeing.php", "", "", "", "", "", "", "", "", "", ],
            ["||Sales Commision Report","PDFPrintSalesCommissions.php", "", "", "", "", "", "", "", "", "", ],
            ["||Stock Sales Report","PDFPrintMonthlySales.php", "", "", "", "", "", "", "", "", "", ],
            
            
    ["Suppliers","", "", "", "", "", "", "", "", "", "", ],
        ["|New","", "", "", "", "", "", "", "", "", "", ],
            ["||New Supplier","Supplier.php", "", "", "", "", "", "", "", "", "", ],
            ["||Supplier Posting Group","SupplierPostingGroups.php", "", "", "", "", "", "", "", "", "", ],
        ["|Post Items","", "", "", "", "", "", "", "", "", "", ],
            ["||Create Purchase order","PurchaseOder.php", "", "", "", "", "", "", "", "", "", ],
            ["||Receive Inventory from supplier","PurchaseOrderList.php", "", "", "", "", "", "", "", "", "", ],
            ["||Post vendor Invoices on items Received","GoodsReceivedNote.php", "", "", "", "", "", "", "", "", "", ],
            ["||Post vendor Invoices on Services","EnterBills.php", "", "", "", "", "", "", "", "", "", ],
         ["|Print","", "", "", "", "", "", "", "", "", "", ],
            ["||List of GRN","PDFPrintgrnNONStock.php", "", "", "", "", "", "", "", "", "", ],
            ["||List of LPO","PDFPrintPurchaseOrder.php", "", "", "", "", "", "", "", "", "", ],
            ["||Statement","PrintvendorStatements.php", "", "", "", "", "", "", "", "", "", ],
     ["Cash Accounts","", "", "", "", "", "", "", "", "", "", ],
        ["|New","", "", "", "", "", "", "", "", "", "", ],
            ["||New Bank/Cash Account","CreateBankAccount.php", "", "", "", "", "", "", "", "", "", ],
            ["||New Currency","Currencies.php", "", "", "", "", "", "", "", "", "", ],
            ["||New Customer","Customer.php", "", "", "", "", "", "", "", "", "", ],
        ["|Post items","", "", "", "", "", "", "", "", "", "", ],
            ["||Petty Cash/Imprest Module","PetteyCash.php?New=1", "", "", "", "", "", "", "", "", "", ],
            ["||Create Payment Voucher","PaymentVoucher.php", "", "", "", "", "", "", "", "", "", ],
            ["||Pay Approved Payment Vouchers","WriteCheque.php", "", "", "", "", "", "", "", "", "", ],
            ["||Debtors Reciepts","receiptsprepayment.php", "", "", "", "", "", "", "", "", "", ],
            ["||Bank Reconciliation","BankReconciliation.php?new=Yes", "", "", "", "", "", "", "", "", "", ],
         ["|Print","", "", "", "", "", "", "", "", "", "", ],
            ["||Petty Cash Summary","PDFimprestReport.php", "", "", "", "", "", "", "", "", "", ],
            ["||List of Payment Vouchers","PDFpaymentvoucher.php", "", "", "", "", "", "", "", "", "", ],
            ["||Debtors Statements","PrintCustStatements.php", "", "", "", "", "", "", "", "", "", ],
            ["||Cash Flow Report","CashFlowReport.php", "", "", "", "", "", "", "", "", "", ],
        ["Items and Products","", "", "", "", "", "", "", "", "", "", ],
            ["|New","", "", "", "", "", "", "", "", "", "", ],
                ["||New Product and Items","Stocks.php", "", "", "", "", "", "", "", "", "", ],
                ["||New Units of Measure","unitsofmesure.php", "", "", "", "", "", "", "", "", "", ],
                ["||New Store or Locaction","stockstores.php", "", "", "", "", "", "", "", "", "", ],
            ["|Post items","", "", "", "", "", "", "", "", "", "", ],
                ["||Stock Transfer Request","Stocktransfer.php", "", "", "", "", "", "", "", "", "", ],
                ["||Transfer Approved Transfer Requests","Approvedstock.php", "", "", "", "", "", "", "", "", "", ],
                ["||Goods Received Note","PurchaseOrderList.php", "", "", "", "", "", "", "", "", "", ],
             ["|Print","", "", "", "", "", "", "", "", "", "", ],
                ["||Stock Balance By Stores","PDFstockbalance.php", "", "", "", "", "", "", "", "", "", ],
                ["||Sample GatePass","PDFPrintSampleRequisition.php", "", "", "", "", "", "", "", "", "", ],
                
                
        ["General Ledger","", "", "", "", "", "", "", "", "", "", ],
            ["|New","", "", "", "", "", "", "", "", "", "", ],
                ["||New Account Item","ChartofAccounts.php", "", "", "", "", "", "", "", "", "", ],
                ["||New Project/Budget Item","DimensionTypes.php", "", "", "", "", "", "", "", "", "", ],
                ["||System Settings","", "", "", "", "", "", "", "", "", "", ],
                ["|||Company Parameters","CompanyPreferences.php", "", "", "", "", "", "", "", "", "", ],
                ["|||System Settings","SystemParameters.php", "", "", "", "", "", "", "", "", "", ],
                ["|||Manage Discrepancy","fellowgroups.php", "", "", "", "", "", "", "", "", "", ],
                ["|||Merge Inventory","mergestock.php", "", "", "", "", "", "", "", "", "", ],
                
            ["|Transactions","", "", "", "", "", "", "", "", "", "", ],
                ["||Create Budget","Budgets.php", "", "", "", "", "", "", "", "", "", ],
                ["||Post a General Journal","Journal.php?new=1", "", "", "", "", "", "", "", "", "", ],
                ["||Post a Cash Journal","cashJournal.php", "", "", "", "", "", "", "", "", "", ],
                ["||Modify Account","Generalledgerist.php", "", "", "", "", "", "", "", "", "", ],
                ["||Modify General Journal","EditJournal.php", "", "", "", "", "", "", "", "", "", ],
             ["|Reports","", "", "", "", "", "", "", "", "", "", ],
                ["||Ledger Quick Report","selectReports.php", "", "", "", "", "", "", "", "", "", ],
                ["||Trial Balance","PDFFinacials.php", "", "", "", "", "", "", "", "", "", ],
                ["||Income statement","PDFprofitloss.php", "", "", "", "", "", "", "", "", "", ],
                ["||Monthly Income statement","PDFprofitlossbymonth.php", "", "", "", "", "", "", "", "", "", ],
                ["||Balance Sheet","PDFbalanceSheet.php", "", "", "", "", "", "", "", "", "", ],
                ["||Funds Flow statement","PDFfundsflow.php", "", "", "", "", "", "", "", "", "", ],
                ["||Cash Flow Report","CashFlowReport.php", "", "", "", "", "", "", "", "", "", ],
         
          ["Dash Board","", "", "", "", "", "", "", "", "", "", ],
              ["|List of Suppliers","SelectSupplier.php?newsearch=yes", "", "", "", "", "", "", "", "", "", ],
              ["|List of Customers","SelectCustomer.php?newsearch=yes", "", "", "", "", "", "", "", "", "", ],
              ["|List of Items","SelectProduct.php?newsearch=yes", "", "", "", "", "", "", "", "", "", ],
              ["|Task Management Report","TaskManagementReport.php", "", "", "", "", "", "", "", "", "", ],
              ["|Activity Management Report","ActivityManagementReport.php", "", "", "", "", "", "", "", "", "", ],
              
];


dm_init();