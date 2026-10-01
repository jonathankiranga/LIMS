<?php
/* $Id:  6190 2013-08-12 02:12:02Z rchacon $*/
/* webERP menus with Captions and URLs. */
$ModuleLink = array('Approval',
                    'Sales',
                    'AccountsReceivable',
                    'Inventory',
                    'cRM',
                    'Purchases',
                    'AccountsPayable',
                    'CashManagement',
                    'GeneralLedger',
                    'FixedAssets',
                    'system');

$ReportList = array('Approval'=>'apo',
                    'Sales'=>'so',
                    'AccountsReceivable'=>'ar',
                    'Inventory'=>'in',
                    'cRM'=>'CRM',
                    'Purchases'=>'po',
                    'AccountsPayable'=>'ap',
                    'CashManagement'=>'cm',
                    'GeneralLedger'=>'GL',
                    'FixedAssets'=>'fa',
                    'system'=>'sys');

/*The headings showing on the tabs accross the main index used also in WWW_Users for defining what should be visible to the user */
$ModuleList = array(_('Document Approvals'),
                    _('Sales'),
                    _('Accounts Receivable'),
                    _('Inventory'),
                    _('Customer Relations'),
                    _('Purchases'),
                    _('Accounts Payable'),
                    _('Cash Management'),
                    _('General Ledger'),
                    _('Fixed Assets'),
                    _('System Administrator'));


//******CRM***************//

$MenuItems['cRM']['Transactions']['Caption'] = array(_('New Contact'),
                                                     _('Modify Price list'),
                                                     _('Sample of Product'),
                                                     _('Sales Quotation'),
                                                     _('Sales Order'),
                                                     _('Print Sales Proforma'),
                                                     _('Print Commercial Invoice'));


$MenuItems['cRM']['Transactions']['URL'] = array('/customerReminders.php?new=yes',
                                                  '/PriceList.php?new=1',
                                                   '/salessamples.php',
                                                   '/SalesQuotation.php',
                                                    '/LaboratoryOder.php?new=1',
                                                    '/PDFPrintSalesprofoma.php',
                                                   '/PDFPrintCommercialInvoice.php');


$MenuItems['cRM']['Reports']['Caption'] = array(_("Print Price List"));

$MenuItems['cRM']['Reports']['URL'] = array('/PDFPricelist.php');

$MenuItems['cRM']['Maintenance']['Caption'] = array(_('New Sales Rep'),
                                                    _('Update Sales Rep Details'));


$MenuItems['cRM']['Maintenance']['URL'] = array('/SalesMan.php',
                                                '/SelectSalesRep.php');


//********************//
$MenuItems['CashManagement']['Transactions']['Caption'] = array(_('Cash Journal Entry'),
                                                                _('Request for Payment Voucher'),
                                                                _('Cheques for Payment Voucher'),
                                                                _('Cheques Remittance Advice'),
                                                                _('Receive Customer Deposits'),
                                                                _('Petty Cash Imprest Entry'),
                                                                _('Bank Reconciliation Entry'));

$MenuItems['CashManagement']['Transactions']['URL'] = array('/cashJournal.php',
                                                            '/PaymentVoucher.php',
                                                            '/WriteCheque.php', 
                                                            '/PDFcheque.php',
                                                            '/receiptsprepayment.php',
                                                            '/PetteyCash.php?New=1',
                                                            '/BankReconciliation.php?new=Yes');


$MenuItems['CashManagement']['Reports']['Caption'] = array(_('Cash Book Register' ),
                                                           _('Print Petty Cash Reports' ),
                                                           _('Print Customer Receipts' ),
                                                           _('Print Payment Vouchers'),
                                                           _('Print Cheque Remittance'),
                                                           _('Print Bank Reconciliation Reports'));

$MenuItems['CashManagement']['Reports']['URL'] = array('/bankregister.php',
                                                       '/PDFimprestReport.php',
                                                        '/PDFprintreceipt.php',
                                                        '/PDFpaymentvoucher.php',
                                                        '/PDFcheque.php',
                                                        '/PDFbankreconciliation.php');

$MenuItems['CashManagement']['Maintenance']['Caption'] = array(_('Create Banks'),
                                                               _('View Currency Trends'),
                                                               _('Exchange Rates'),
                                                               _('Manage Petty Cash Shift'),
                                                               _('Create Employee'));


$MenuItems['CashManagement']['Maintenance']['URL'] = array('/CreateBankAccount.php',
                                                           '/ExchangeRateTrend.php',
                                                           '/Currencies.php',
                                                           '/PettyCashUsers.php',
                                                           '/Employee.php');

//Budgets.php
//*********************//

$MenuItems['Approval']['Transactions']['Caption'] = array( _('Approve SalesReps Pricelist'),
                                                           _('Approve Supplier Documents'),
                                                           _('Approve Customer Documents'),
                                                           _('Day Close for Petty Cash Reports'),
                                                           _('Payment Voucher 1<sup>st</sup> Approval'),
                                                           _('Payment Voucher 2<sup>nd</sup> Approval'),
                                                            _('Main Price List'),
                                                            _('Pricing Calculator'),
                                                            _('Discount Setup'),
                                                            _('Bulk Import Mappings')
                                                          );

$MenuItems['Approval']['Transactions']['URL'] = array('/ApprovePriceList.php',
                                                        '/ApprovePurchase.php',
                                                        '/ApproveStcokissues.php',
                                                        '/PettyCashUsers.php',
                                                        '/PDFFAMpaymentvoucher.php',
                                                        '/PDFCEOpaymentvoucher.php',
                                                        '/DefaultPriceList.php?new=1',
                                                        '/PricingCalculator.php',
                                                        '/DiscountSetup.php',
                                                        '/BulkImportMappings.php');

$MenuItems['Approval']['Reports']['Caption'] = array('Payment Voucher',
                                                _('Sales Reps Commission Report by Month'),
                                                _('Sales Spoilage Stock Replacement'));

$MenuItems['Approval']['Reports']['URL'] = array('/PDFpaymentvoucher.php',
                                                '/PDFPrintSalesCommissions.php',
                                                '/PDFPrintReplacementSales.php');

$MenuItems['Approval']['Maintenance']['Caption'] = array(_('Create Main Price List'),
                                                         _('Create Dimensions'),
                                                         _('Create Budgets'),
                                                         _('Create Office Assistants'),
                                                         _('Create/Update Sales Comm Rates'));

$MenuItems['Approval']['Maintenance']['URL'] = array('/DefaultPriceList.php?new=1',
                                                      '/DimensionTypes.php',
                                                      '/Budgets.php',
                                                      '/productionEmployee.php','/SalesCommision.php');

/***********************************************/
 
$MenuItems['Sales']['Transactions']['Caption'] = array(
                                                       _('Sample of Product'),
                                                       _('Sales Quotation'),
                                                       _('Sales Orders'),
                                                       _('Sales Confirmation'),
                                                       _('Sales Invoice'),
                                                       _('Sales Credit Note'),
                                                       _('VAT Sales Credit Note'),
                                                       _('Sales Proforma'),
                                                       _('Commercial Invoice')  
                                                       );
//Salescreditnote
$MenuItems['Sales']['Transactions']['URL'] = array(
                                                   '/salessamples.php',
                                                   '/SalesQuotation.php',
                                                   '/LaboratoryOder.php?new=1',
                                                   '/SalesDelivery.php',
                                                   '/SalesInvoice.php',
                                                   '/SalesDeliveryList.php',
                                                   '/MultipleSalesselectList.php',
                                                   '/PDFPrintSalesprofoma.php',
                                                   '/PDFPrintCommercialInvoice.php'
                                                );

$MenuItems['Sales']['Reports']['Caption'] = array(_('Stock Sales Report'),
                                                  _('Print Price List'),
                                                  _('Print Sample Gatepass'),
                                                  _('Print Sales Quotations'),
                                                  _('Print Open Sales orders'),
                                                  _('Print Invoices last '. $_SESSION['DefaultDisplayRecordsMax'] ),
                                                  _('Print Credit Notes'),
                                                  _('Print Sales Returns Notes'),
                                                  _('Print Daily Sales'),
                                                  _('Print Daily Sales Credit Notes'),
                                                  _('Print VAT Credit Notes'));

$MenuItems['Sales']['Reports']['URL'] = array('/PDFPrintMonthlySales.php',
                                              '/PDFPricelist.php',
                                              '/PDFPrintSampleRequisition.php',
                                              '/PDFPrintSalesQuote.php',
                                              '/PDFPrintSalesOrder.php',
                                              '/PDFPrintSalesInvoice.php',
                                              '/PDFPrintcreditnote.php',
                                              '/PDFPrintReturnsList.php',
                                              '/PDFPrintDailySales.php',
                                              '/PDFPrintDailyCreditnotes.php',
                                              '/PDFPrintVATcreditnote.php');

$MenuItems['Sales']['Maintenance']['Caption'] = array(_('New Sales Rep'),_('Update Sales Rep Details'));


$MenuItems['Sales']['Maintenance']['URL'] = array('/SalesMan.php','/SelectSalesRep.php');

/***********************************************/
$MenuItems['AccountsReceivable']['Transactions']['Caption'] = array(_('Receipts'),
                                                                    _('Receipts Allocation'),
                                                                    _('Print Receipts'));

$MenuItems['AccountsReceivable']['Transactions']['URL'] = array('/receipts.php',
                                                                '/ReceitsAllocation.php',
                                                                '/PDFprintreceipt.php');

$MenuItems['AccountsReceivable']['Reports']['Caption'] = array(_('Customer Statements'),
                                                               _('Customer Ageing Analysis'));

$MenuItems['AccountsReceivable']['Reports']['URL'] = array('/PrintCustStatements.php'
                                                          ,'/PrintCustAgeing.php');

$MenuItems['AccountsReceivable']['Maintenance']['Caption'] = array(_('Customer Posting Groups'),
                                                                   _('Maintain Customer'));

$MenuItems['AccountsReceivable']['Maintenance']['URL'] = array('/CustomersPostingGroups.php',
                                                              '/Customer.php');

/***********************************************/
$MenuItems['Purchases']['Transactions']['Caption'] = array(_('Purchase\'s Order'),
                                                           _('Goods Received Note'),
                                                           _('Supplier\'s Purchase Invoice'),
                                                           _('Supplier\'s Return Stock'),
                                                           _('Supplier\'s Debit Note'),
                                                           _('Request for Assets'),
                                                           _('Receive for Assets'),
                                                           _('Fixed Assets Invoice'));


$MenuItems['Purchases']['Transactions']['URL'] = array('/PurchaseOder.php?new=YES',
                                                       '/PurchaseOrderList.php',
                                                       '/GoodsReceivedNote.php',
                                                       '/PurchaseOrderDBlist.php',
                                                       '/PurchasevendorDBlist.php',
                                                       '/PurchaseOderAssets.php',
                                                       '/FixedAssetsReceived.php',
                                                       '/FassetsReceivedNote.php');


$MenuItems['Purchases']['Reports']['Caption'] = array(_('Last '. $_SESSION['DefaultDisplayRecordsMax'].' Serial Numbers (Goods Received)'),
                                                      _('Last '. $_SESSION['DefaultDisplayRecordsMax'].' Goods Received Notes'),
                                                      _('Last '. $_SESSION['DefaultDisplayRecordsMax'].' Purchase Orders'),
                                                      _('Last '. $_SESSION['DefaultDisplayRecordsMax'].' Fixed assets GRN'),
                                                      _('Purchases Credit Notes-VAT Report'),
                                                      _('Purchases VAT Report'));
//PDFPrintSerialNumbers.php
$MenuItems['Purchases']['Reports']['URL'] = array('/PDFPrintSerialNumbers.php',
                                                  '/PDFPrintgrnNONStock.php',
                                                  '/PDFPrintPurchaseOrder.php',
                                                  '/PDFPrintAssetgrn.php',
                                                  '/PDFPurchaseCreditnotes.php',
                                                  '/PDFPrintDailyPurchases.php');

$MenuItems['Purchases']['Maintenance']['Caption'] = array(_('Vendor Posting Groups'),
                                                          _('Maintain Vendor'));


$MenuItems['Purchases']['Maintenance']['URL'] = array('/SupplierPostingGroups.php',
                                                      '/Supplier.php');


/***********************************************/
$MenuItems['Inventory']['Transactions']['Caption'] = array(_('Purchase Order'),
                                                           _('Goods Received Note'),
                                                           _('Record Expiry Dates'),
                                                           _('Inventory store Transfer'),
                                                           _('Issue Consumable Items'),
                                                           _('Approved Items Requests'));

$MenuItems['Inventory']['Transactions']['URL'] = array('/PurchaseOder.php?new=YES',
                                                       '/PurchaseOrderList.php',
                                                      '/stockExpiryTracker.php',
                                                       '/Stocktransfer.php',
                                                       '/StockConsuptionIssues.php',
                                                       '/Approvedstock.php');


$MenuItems['Inventory']['Reports']['Caption'] = array(_('Print Price List'),
                                                      _('Stock Summary'),
                                                      _('Closing stock Summary'),
                                                      _('Bin Card Summary'),
                                                      _('Inventory Spreadsheet')
                                                    );

$MenuItems['Inventory']['Reports']['URL'] = array( '/PDFPricelist.php',
                                                   '/PDFstockbalance.php',
                                                   '/PDFstockValue.php',
                                                   '/StockMovements.php',
                                                   '/InventorySpreadsheet.php'
                                                 );

$MenuItems['Inventory']['Maintenance']['Caption'] = array(_('New Stock'),
                                                          _('Stock Category'),
                                                          _('Stock Posting Groups'),
                                                          _('Create Units of measure'),
                                                          _('Create Store'));


$MenuItems['Inventory']['Maintenance']['URL'] = array('/Stocks.php',
                                                       '/stockitemcategories.php',
                                                       '/InventoryPostingGroups.php',
                                                       '/unitsofmesure.php',
                                                       '/stockstores.php');



/******************************************************************************************/
$MenuItems['AccountsPayable']['Transactions']['Caption'] = array( _('Enter Bills'),
                                                                  _('Payment Voucher'),
                                                                  _('Allocate Invoices')
    );

$MenuItems['AccountsPayable']['Transactions']['URL'] = array('/EnterBills.php',
                                                             '/PaymentVoucher.php',
                                                             '/PaymentsAllocation.php'
    );

$MenuItems['AccountsPayable']['Reports']['Caption'] = array(_('Vendor Statement'),
                                                            _('Vendor Ageing Analysis'),
                                                           _('View payment vouchers'),
                                                           _('Print Enter Bills') );

$MenuItems['AccountsPayable']['Reports']['URL'] = array('/PrintvendorStatements.php',
                                                        '/PrintSuppAgeing.php',
                                                        '/PDFpaymentvoucher.php','/PDFenterbills.php');

$MenuItems['AccountsPayable']['Maintenance']['Caption'] = array(_('Vendor Posting Groups'),
                                                                _('Maintain Supplier'));

$MenuItems['AccountsPayable']['Maintenance']['URL'] = array('/SupplierPostingGroups.php',
                                                            '/Supplier.php');

//*****************************************************************
$MenuItems['GeneralLedger']['Transactions']['Caption']=array(_('General Journals'),
                                                              _('Cash Journals'),
                                                              _('Ledger Reports'),
                                                              _('Modiy Ledger Details'),
                                                              _('Cost Allocation Rules'),
                                                              _('Run Cost Allocation'));

$MenuItems['GeneralLedger']['Transactions']['URL'] = array( '/Journal.php?new=1',
                                                             '/cashJournal.php',
                                                             '/selectReports.php',
                                                             '/Generalledgerist.php',
                                                             '/CostAllocation.php',
                                                             '/CostAllocation.php?action=run');


$MenuItems['GeneralLedger']['Reports']['Caption']=array(_('Trial Balance'),
                                                         _('Annual Income Statement'),
                                                         _('Annual Income Statement By Project'),
                                                         _('Monthly Income Statement'),
                                                         _('Balance Sheet'),
                                                         _('FundsFlow Summary Report'),
                                                         _('Budget Summary Report'),
                                                         _('Project Report'),
                                                         _('SPREADSHEETS'),
                                                         _('Cost Accounting Report'));

$MenuItems['GeneralLedger']['Reports']['URL'] = array('/PDFFinacials.php',
                                                       '/PDFprofitloss.php', 
                                                       '/PDFprofitlossByProject.php',
                                                       '/PDFprofitlossbymonth.php',
                                                       '/PDFbalanceSheet.php',
                                                       '/PDFfundsflow.php',
                                                       '/PDFbudgets.php',
                                                       '/PDFprojects.php',
                                                       '/AccountBalances.php',
                                                       '/CostAccountingReport.php');



$MenuItems['GeneralLedger']['Maintenance']['Caption'] = array(_('Company Preferences'),
                                                               _('System Parameters'),
                                                               _('Chart of Accounts'),
                                                               _('Create Dimension Types'),
                                                               _('Create/Close Financial Periods'),
                                                               _('Create GL Posting Groups'),
                                                               _('Create VAT Categories'),
                                                               _('Income statement setup'),
                                                               _('Balance Sheet $ Funds Flow setup'),
                                                               _('Setup Budgets'),
                                                               _('Test Labor Mapping'),
                                                               _('Test Equipment Mapping'));


$MenuItems['GeneralLedger']['Maintenance']['URL'] = array('/CompanyPreferences.php',
                                                           '/SystemParameters.php',
                                                           '/ChartofAccounts.php',
                                                           '/DimensionTypes.php',
                                                           '/FinancialPeriods.php?new=1',
                                                           '/GLPostingGroups.php',
                                                           '/vatcategory.php',
                                                           '/Setupincomestatement.php',
                                                           '/Setupbalancesheet.php',
                                                           '/Budgets.php',
                                                           '/TestLaborMapping.php',
                                                           '/TestEquipmentMapping.php');
//ChartofAccounts.php

//*************************************************************
$MenuItems['system']['Transactions']['Caption'] = array(_('Users Maintenance'),
                                                        _('Maintain Security Tokens'),
                                                        _('Access Permissions Maintenance'),
                                                        _('Page Security Settings'));

$MenuItems['system']['Transactions']['URL'] = array('/WWW_Users.php',
                                                    '/SecurityTokens.php',
                                                    '/WWW_Access.php',
                                                    '/PageSecurity.php');

$MenuItems['system']['Reports']['Caption'] = array(_('List Periods Defined'),
                                                   _('View Audit Trail'));

$MenuItems['system']['Reports']['URL'] = array('/PeriodsInquiry.php',
                                               '/AuditTrail.php');


$MenuItems['system']['Maintenance']['Caption'] = array(_('Company Preferences'),
                                                        _('System Parameters'),
                                                        _('Stock Adjustments'),
                                                        _('eTIMS Wizard'));


$MenuItems['system']['Maintenance']['URL'] = array('/CompanyPreferences.php',
                                                   '/SystemParameters.php',
                                                   '/StockAdjustments.php',
                                                   '/ETIMSWizard.php');

/***********************************************/

//*******//

$MenuItems['FixedAssets']['Transactions']['Caption'] = array(_('LPO for Assets'),
                                                             _('GRN for Assets'),
                                                             _('Fixed Assets Enter Bill'),
                                                             _('Depreciation Journal'),
                                                             _('Request Hire for Equipment/Assets'),
                                                             _('Issue Equipment/Assets for Hire'));

$MenuItems['FixedAssets']['Transactions']['URL'] = array('/PurchaseOderAssets.php',
                                                         '/PurchaseAssetsList.php',
                                                         '/FassetsReceivedNote.php',
                                                         '/FixedAssetDepreciation.php',
                                                         '/HireAssets.php',
                                                         '/HireOutAssetsList.php' );

$MenuItems['FixedAssets']['Reports']['Caption'] = array(_('Asset Register'));

$MenuItems['FixedAssets']['Reports']['URL'] = array('/FixedAssetRegister.php');

$MenuItems['FixedAssets']['Maintenance']['Caption'] = array(_('Select an Asset Item'),
                                                            _('Add or Maintain Asset Categories'),
                                                            _('Add or Maintain Asset Locations'),
                                                            _('Add or Maintain Asset Item'),
                                                            _('Change Asset Location'),
                                                            _('Dispose Asset'));

$MenuItems['FixedAssets']['Maintenance']['URL'] = array('/SelectAsset.php',
                                                        '/FixedAssetCategories.php',
                                                        '/FixedAssetLocations.php',
                                                        '/FixedAssetItems.php',
                                                        '/FixedAssetTransfer.php',
                                                        '/FixedAssetDisposal.php');

/***********************************************/

Cust();
Custurl();

Function Cust(){
    global $db,$MenuItems;
    
   $ResultIndex=DB_query('SELECT `id`,`Dimension_type` FROM `DimensionSetUp`', $db);
   while($rows=DB_fetch_array($ResultIndex)){
       $items = $rows['Dimension_type'];
       $MenuItems['Sales']['Maintenance']['Caption'][] = $items;
    }
  
}

Function Custurl(){
  global $db,$MenuItems;
    
   $ResultIndex=DB_query('SELECT `id`,`Dimension_type` FROM `DimensionSetUp`', $db);
   while($rows=DB_fetch_array($ResultIndex)){
       $items = '/Dimensionlist.php?id='.$rows['id'];
       $MenuItems['Sales']['Maintenance']['URL'][]= $items;
    }
   
}

?>
