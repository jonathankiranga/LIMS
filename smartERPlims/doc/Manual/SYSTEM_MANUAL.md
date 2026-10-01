# SmartERP System Manual (Current Codebase)

## 1. Purpose and Scope

This manual documents what the current SmartERP application **can do now** and what it **should do operationally** based on the implemented code paths.

Source baseline for this manual starts at:

- `index.php` (main entry/menu shell)
- `includes/MainMenuLinksArray.php` (module definition)
- `homepage.php` (dashboard/work queue)
- Core runtime files (`includes/session.inc`, `includes/ConnectDB_mysqli.inc`, `includes/SQL_CommonFunctions.inc`)

This is a **capability manual** and **operating-control manual**. It is written for:

- Business users
- Finance and operations leads
- System administrators
- Implementation/support teams

---

## 2. System Overview

SmartERP is a role-based ERP built around a single main menu shell and an iframe workspace. Users authenticate, receive permission-scoped modules, then execute transactions, reports, and setup actions from module trees.

### 2.1 Main Functional Domains

The system provides 12 module domains:

1. Document Approvals
2. Sales
3. Accounts Receivable
4. Inventory
5. Customer Relations (CRM)
6. Production
7. Purchases
8. Accounts Payable
9. Cash Management
10. General Ledger
11. Fixed Assets
12. System Administrator

### 2.2 Application Pattern

The application follows a consistent pattern:

1. Header/master data entry
2. Line entry grid
3. Recalculate/validate
4. Save/post/approve
5. Print/report

### 2.3 Current Database Mode

The current configuration is MySQLi with UTF-8MB4 charset.

- DB Type: `mysqli`
- Default database: `mozillaerpv2`
- Time zone: `Africa/Nairobi`

---

## 3. Runtime and Security Model

## 3.1 Session and Login

`includes/session.inc` handles:

- Configuration load
- Session initialization and lifetime controls
- Login/authentication via `includes/UserLogin.php`
- Language setup
- Config preload into session
- Permission checks for each page
- CSRF form token check via `FormID`

### 3.2 Input Handling

The system escapes scalar `$_POST` and `$_GET` values through `DB_escape_string` in-session.

### 3.3 Access Control

Security is token-based:

- `scripts.pagesecurity` maps each page to required token
- User roles map to token sets
- User access is denied if token not granted

Key administration pages:

- `WWW_Users.php`: user account maintenance
- `WWW_Access.php`: role to token assignment
- `PageSecurity.php`: page to token assignment

### 3.4 Audit Trail

`DB_query()` auto-inserts SQL text for INSERT/UPDATE/DELETE into `audittrail` when enabled (`MonthsAuditTrail`).

---

## 4. Navigation and UI Shell

## 4.1 Main Shell (`index.php`)

The main shell:

1. Builds module trees from `MainMenuLinksArray.php`
2. Filters links by security token
3. Splits each module into:
   - Transactions
   - Inquiries and Reports
   - Set Up
4. Loads `homepage.php` into `mainContentIFrame` by default

## 4.2 Dashboard (`homepage.php`)

The homepage acts as a live work queue with:

- Pending approval counts
- Operational to-do indicators
- Bank snapshots
- CRM tasks and activities
- Replenishment prompts from reorder logic

Examples of dashboard counters:

- Pending purchase approvals
- Pending store request approvals
- Pending payment voucher approvals
- Un-invoiced purchase orders
- Un-posted sales orders
- Un-posted cheques

---

## 5. Module-by-Module Capability Map

The following sections document implemented capability by module.

## 5.1 Document Approvals

### Transactions

- Approve SalesRep price list
- Approve supplier documents
- Approve customer/store documents
- Payment voucher stage approvals (Finance head and CEO)
- Main price list operations

### Reports

- Payment voucher views
- Sales commission by month
- Sales spoilage replacement

### Set Up

- Main price list management
- Dimensions
- Budgets
- Office assistants
- Sales commission rates

### Should Do (Control)

1. Enforce maker-checker sequence before financial posting.
2. Restrict bypass of second-level voucher approvals.
3. Use dashboard counters as daily exception queue.

---

## 5.2 Sales (Order-to-Cash Front Office)

### Transactions

- Samples
- Sales quotations
- Sales orders
- Loading orders
- Dispatch notes
- Sales invoice processing
- Credit note and VAT credit note flows
- Proforma and commercial invoice outputs

### Core Sales Order Capability (`SalesOder.php`)

The system supports:

- Customer selection and pricing context
- Barcode/item selection from inventory
- Unit/pack conversion logic
- Quantity and pricing capture
- Optional linked image/gallery metadata
- Save and print order
- Release flags for downstream invoicing

### Invoice Selection Capability (`SalesInvoice.php`)

- Lists released sales orders and toll blending references
- Provides drill-through to invoice posting page (`SalesInvoicefromorders.php`)

### Invoice Posting Capability (`SalesInvoicefromorders.php` + `transactions/Saveinvoice.inc`)

On confirmation, system:

1. Creates invoice header (`SalesHeader` type 10)
2. Creates invoice lines (`SalesLine`)
3. Posts debtor ledger (`debtorsledger`)
4. Posts GL entries (`Generalledger`) for sales and VAT legs
5. Posts customer statement (`CustomerStatement`)
6. Marks source order as released/posted state

### Reports

- Sales order prints
- Sales invoice prints
- Sales quotation print
- Credit note reports
- Daily and monthly sales summaries
- VAT credit note report

### Should Do (Control)

1. Ensure order release is mandatory before invoicing.
2. Validate posting period status before final invoice posting.
3. Reconcile invoice totals against customer statement and debtors ledger daily.

---

## 5.3 Accounts Receivable

### Transactions

- Customer receipts entry (`receipts.php`)
- Receipts allocation (`ReceitsAllocation.php`)
- Receipt print

### Receipts Capability (`receipts.php`)

- Creates receipt document number
- Selects customer and bank/currency context
- Captures amount allocations against open customer statement balances
- Posts receipt journal entries through class flow
- Calls auto-allocation AJAX helper after posting

### Allocation Capability (`ReceitsAllocation.php`)

- Shows unallocated customer balances
- Supports reset allocations for account
- Supports auto-allocation logic for receipts/invoices

### Reports

- Customer statements
- Customer ageing analysis

### Should Do (Control)

1. Apply receipts to oldest invoices first unless policy differs.
2. Reconcile receipts to bank transactions daily.
3. Lock/reset operations to authorized users only.

---

## 5.4 Purchases (Procure-to-Receive)

### Transactions

- Purchase order creation (`PurchaseOder.php`)
- Goods received note path (`PurchaseOrderList.php`, `GoodsReceivedNote.php`)
- Supplier purchase invoice and returns/debit note lists
- Fixed asset request/receive/invoice path integration

### Purchase Order Capability (`PurchaseOder.php`)

- Supplier-based PO header
- Multi-unit purchase and receiving quantity model
- Unit conversion fields
- VAT, discount, cost capture
- Dimension support
- Release and print workflow

### Reports

- Goods received note history
- Purchase order history
- Fixed asset GRN history
- Purchases VAT and credit note reports

### Should Do (Control)

1. Require approved PO before supplier invoice entry.
2. Match invoice quantity/cost against GRN and PO.
3. Validate conversion ratios for purchased vs received units.

---

## 5.5 Accounts Payable

### Transactions

- Enter bills (`EnterBills.php`)
- Payment voucher preparation (`PaymentVoucher.php`)
- Invoice/payment allocation (`PaymentsAllocation.php`)

### Enter Bills Capability (`EnterBills.php`)

- Journal-like multi-line expense entry
- Supplier and currency context
- Optional VAT auto calculation and withholding tax settings
- Saves bill and can print entered bill output
- Writes ledger postings via class SaveJournal flow

### Payment Voucher Capability (`PaymentVoucher.php`)

- Creates voucher headers and lines
- Supports edit/delete/update when session lock is active
- Selects unpaid supplier statement items
- Captures amount-to-pay per invoice line
- Saves voucher and supports print output

### Allocation Capability (`PaymentsAllocation.php`)

- Displays invoice and payment matching positions
- Auto-allocation logic for supplier invoices and payments
- Reset account allocations

### Reports

- Vendor statement
- Vendor ageing
- Payment voucher views
- Enter bill print

### Should Do (Control)

1. Use voucher approvals before cheque posting.
2. Prevent payment beyond unallocated open amount.
3. Monitor negative/over-allocation scenarios.

---

## 5.6 Cash Management

### Transactions

- Cash journals
- Payment voucher to cheque processing
- Remittance advice
- Customer deposit receipts
- Petty cash imprest entry
- Bank reconciliation

### Reports

- Cash book register
- Petty cash reports
- Customer receipts
- Payment vouchers
- Cheque remittance
- Bank reconciliation report

### Set Up

- Bank account maintenance
- Currency trend and rates
- Petty cash shift users
- Employee creation (used by some inventory transfer contexts)

### Should Do (Control)

1. Complete bank reconciliation before period close.
2. Restrict cheque posting to approved vouchers.
3. Separate cash handling and approval responsibilities.

---

## 5.7 Inventory

### Transactions

- Purchase order and GRN access points
- Store-to-decanting movement
- Store transfer (`Stocktransfer.php`)
- Consumable issues
- Approved request fulfilment
- Toll blending sales issues

### Stock Transfer Capability (`Stocktransfer.php`)

- Selects stock items and transfer quantities
- Validates source availability
- Blocks transfer from tank-only contexts where disallowed
- Writes two stockledger entries per transfer (out/in)
- Uses transaction no type 16

### Reports

- Price list
- BOM issues
- Stock summary
- Closing stock summary
- Bin card/stock movement
- Toll blending summary

### Set Up

- Stock master
- Stock categories
- Inventory posting groups
- Unit of measure
- Stores

### Should Do (Control)

1. Validate inventory balances before transfer commit.
2. Keep unit and pack conversion definitions consistent.
3. Investigate negative balances before close.

---

## 5.8 Production

### Transactions

- Production entry (`Production.php`)
- Production quality test (`LaboratoryDataEntry.php`)
- Final quality review (`LaboratoryFinalReview.php`)
- Production cutback

### Production Entry Capability (`Production.php`)

- Defines produced item and raw material consumption lines
- Supports VCF and temperature factors for bitumen workflows
- pH capture control
- Selects production store/tank and capacity constraints
- Computes production totals and unit cost
- Saves production batch and routes for lab testing

### Lab Data Entry Capability (`LaboratoryDataEntry.php`)

- Lists pending production batches requiring lab tests
- Loads test parameter standards by item
- Captures parameter results with min/max context
- Supports completion path for testing status

### Reports

- QC lab test reports
- Production summary

### Set Up

- Tank storage capacities
- Laboratory standards
- Production mix configuration

### Should Do (Control)

1. Do not release finished batch without required lab results.
2. Monitor tank capacity thresholds before saving production.
3. Keep laboratory standards synchronized with product codes.

---

## 5.9 CRM (Customer Relations)

### Transactions

- New contacts
- Task and activity scheduling
- Price list modifications
- Samples
- Sales quotation/order linkage
- Proforma/commercial invoice prints

### Reports

- Price lists
- Activity management
- Task management
- Sales loading

### Set Up

- Sales reps creation/maintenance

### Should Do (Control)

1. Keep CRM contacts linked to transactional customer records.
2. Use tasks/activities for follow-up on quotations and collections.

---

## 5.10 General Ledger (Record-to-Report)

### Transactions

- General journals (`Journal.php`)
- Cash journals
- Ledger reports and drilldowns
- Ledger detail maintenance

### Journal Capability (`Journal.php`)

- Balanced debit/credit entry model
- Supports personal account offsets (debtors/creditors/bank)
- Narration and dimensions
- Journal number generation and posting transaction
- Duplicate submit guard via session journal ID

### Financial Period Capability (`FinancialPeriods.php`)

- Shows current and recent periods
- Close month action updates rollover and marks closed periods
- Close/create financial period action with balancing journal logic
- Calls profit/loss carry-forward routines

### Financial Reporting Capability (`includes/AccountBalance.inc`)

Current implementation uses direct MySQL query functions (not MSSQL procs) for:

- Trial balance
- Balance sheet
- Profit/loss variants
- Project/dimension variants
- Budget and drilldown variants

### Set Up

- Company preferences
- System parameters
- Chart of accounts
- Dimension types
- Financial periods
- GL posting groups
- VAT categories
- Income statement setup
- Balance sheet/funds flow setup
- Budgets

### Should Do (Control)

1. Lock posting into closed periods.
2. Enforce dimension policy where required.
3. Reconcile subledgers (AR/AP/stock) with GL before close.

---

## 5.11 Fixed Assets

### Transactions

- Asset PO and GRN flows
- Fixed asset bill entry
- Depreciation journal run (`FixedAssetDepreciation.php`)
- Hire request/issue workflows

### Depreciation Capability (`FixedAssetDepreciation.php`)

- Computes depreciation by asset category
- Supports straight-line and diminishing value modes
- Generates GL depreciation entries
- Inserts fixed asset transaction records
- Updates asset accumulated depreciation
- Updates company last depreciation date

### Reports

- Asset register

### Set Up

- Asset categories
- Asset locations
- Asset item maintenance
- Asset transfer/location change

### Should Do (Control)

1. Run depreciation monthly with posted period controls.
2. Review asset category account mappings before commit.
3. Reconcile asset register totals to GL asset and accumulated depreciation accounts.

---

## 5.12 System Administrator

### Transactions

- Users maintenance (`WWW_Users.php`)
- Security token maintenance
- Access permission maintenance (`WWW_Access.php`)
- Page security settings (`PageSecurity.php`)
- SMTP server setup
- Mailing group maintenance

### Reports

- Defined periods list
- Audit trail review

### Maintenance

- Company preferences
- System parameters (`SystemParameters.php`)
- Stock adjustments

### User Administration Capability (`WWW_Users.php`)

- Create/edit/delete users
- Assign roles, module visibility, language/theme
- Assign debtor/supplier/salesman links
- Password policy checks and hashing (`sha1` from config)

### Access Administration Capability (`WWW_Access.php`)

- Create/edit/delete security roles
- Add/remove token permissions for roles

### Page Security Capability (`PageSecurity.php`)

- Map each script to required security token

### System Parameters Capability (`SystemParameters.php`)

Configurable controls include:

- Manual numbering behavior
- Withholding tax GL and rate
- Single-user mode
- Posting cut-off date
- DB maintenance mode
- Date format/theme defaults
- Image/report directories
- SMTP usage
- Display record limits
- Payment terms text and multilingual description settings
- Report logos

### Should Do (Control)

1. Maintain least-privilege access model.
2. Keep page-token matrix aligned with role policy.
3. Use audit trail to investigate privileged changes.

---

## 6. End-to-End Business Process Flows

## 6.1 Order-to-Cash

1. Create quotation/order.
2. Release approved order.
3. Create invoice from released order.
4. Post invoice to AR + GL + customer statement.
5. Receive payment.
6. Allocate receipts.
7. Run customer statements/ageing and reconcile.

## 6.2 Procure-to-Pay

1. Create purchase order.
2. Approve PO.
3. Receive goods.
4. Enter supplier bill/invoice.
5. Prepare payment voucher.
6. Run approval stages.
7. Issue cheque/payment.
8. Allocate supplier payments.
9. Reconcile bank/AP reports.

## 6.3 Record-to-Report

1. Post journals and subledger transactions.
2. Run trial balance and management reports.
3. Resolve variances and incomplete approvals.
4. Close month/year periods.
5. Carry forward balances and archive controls.

---

## 7. Core Data Objects (Operationally Important)

Frequently touched tables in current flows:

- `SalesHeader`, `SalesLine`
- `PurchaseHeader`, `PurchaseLine`
- `CustomerStatement`, `SupplierStatement`
- `ReceiptsAllocation`, `PaymentsAllocation`
- `debtorsledger`, `creditorsledger`
- `Generalledger`
- `BankTransactions`, `BankAccounts`
- `stockledger`, `stockmaster`, `Stores`
- `ProductionMaster`, `LabPostingDetail`, `LaboratoryStandards`
- `fixedassets`, `fixedassettrans`, `fixedassetcategories`
- `www_users`, `securityroles`, `securitytokens`, `securitygroups`, `scripts`
- `audittrail`, `config`, `FinancialPeriods`, `systypes_1`

---

## 8. Numbering and Transaction Identity

Document numbers are generated via `systypes_1`:

- `GetTempNextNo(typeid)` previews next number
- `GetNextTransNo(typeid)` increments and returns number

Examples in code:

- Sales invoice: type 10
- Receipt: type 12
- Stock transfer: type 16
- Purchase order: type 18
- Enter bills: type 23
- Production: type 28
- Depreciation: type 44

---

## 9. Operational Controls and Recommended SOP

## 9.1 Daily

1. Review homepage pending approvals.
2. Post/approve urgent operational documents.
3. Reconcile receipts/payments to statements.
4. Review inventory exceptions (reorder and negative risk).

## 9.2 Weekly

1. Review open order, open PO, and un-invoiced queues.
2. Validate ageing reports (customer and vendor).
3. Review audit trail for high-risk changes.

## 9.3 Month-End

1. Ensure all required documents are posted/approved.
2. Complete bank reconciliation.
3. Reconcile AR/AP/inventory with GL.
4. Run financial statements and variance checks.
5. Close month / period only after approvals.

---

## 10. Known Behavioral Caveats from Current Code

1. Many transactional pages build SQL via concatenation; this increases risk if input handling is bypassed.
2. Some pages rely on session lock flags (`$_SESSION['locked']`) to prevent duplicate submits; user workflow discipline matters.
3. Role and page-token configuration quality directly determines security boundaries.
4. Dashboard counters are informational and depend on data state consistency in transaction tables.

---

## 11. What the System Should Do (Target Operating Standard)

The current implementation should be operated with these standards:

1. **Approve before post** for financially impactful documents.
2. **Post once** using lock and journal/document number controls.
3. **Close only reconciled periods** after subledger and bank checks.
4. **Maintain role segregation** between creator, approver, and poster where possible.
5. **Retain auditability** by preserving document references and narratives in every posting.

---

## 12. Traceability to Source Files

Primary files used to produce this manual:

- `index.php`
- `homepage.php`
- `includes/MainMenuLinksArray.php`
- `includes/session.inc`
- `includes/ConnectDB_mysqli.inc`
- `includes/SQL_CommonFunctions.inc`
- `config.php`
- `SalesOder.php`
- `SalesInvoice.php`
- `SalesInvoicefromorders.php`
- `transactions/Saveinvoice.inc`
- `PurchaseOder.php`
- `PurchaseOrderList.php`
- `EnterBills.php`
- `PaymentVoucher.php`
- `receipts.php`
- `ReceitsAllocation.php`
- `Journal.php`
- `FinancialPeriods.php`
- `includes/AccountBalance.inc`
- `Stocktransfer.php`
- `Production.php`
- `LaboratoryDataEntry.php`
- `FixedAssetDepreciation.php`
- `WWW_Users.php`
- `WWW_Access.php`
- `SystemParameters.php`
- `PageSecurity.php`

