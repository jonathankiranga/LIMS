# SmartERP Technical Operations Manual

## 1. Objective

This manual documents how SmartERP operates technically in production: runtime stack, transaction posting behavior, key tables, controls, and operational support procedures.

## 2. Runtime Stack

## 2.1 Entry Sequence

1. `index.php` loads after login.
2. `includes/session.inc` enforces auth, permissions, and form-token checks.
3. Menu model is loaded from `includes/MainMenuLinksArray.php`.
4. `homepage.php` initializes the operational dashboard.

## 2.2 Session and Auth Internals

- Session cookie name: `ErpWithCRM`
- Session lifetime from config: `SessionLifeTime`
- Maximum execution time from config: `MaximumExecutionTime`
- Form anti-CSRF token: `$_SESSION['FormID']`, validated on POST.
- Password hashing controlled by `CryptFunction` (currently `sha1` in config).

## 2.3 DB Layer and Error Handling

- DB wrapper: `includes/ConnectDB_mysqli.inc`
- Query execution: `DB_query`, `DB_System`
- Transaction API:
  - `DB_Txn_Begin($db)`
  - `DB_Txn_Commit($db)`
  - `DB_Txn_Rollback($db)`
- Error path:
  - SQL error message from MySQLi
  - Optional debug SQL output for users with admin token (`WWW_Users.php` token)

## 2.4 Audit Behavior

For INSERT/UPDATE/DELETE statements with affected rows, `DB_query` writes to `audittrail`:

- `transactiondate`
- `userid`
- escaped `querystring`

This is conditional on configured audit retention settings.

---

## 3. Numbering and Transaction IDs

Implemented in `includes/SQL_CommonFunctions.inc` via `systypes_1`.

## 3.1 Functions

- `GetTempNextNo(typeid)`:
  - does not increment
  - used for UI preview numbers
- `GetNextTransNo(typeid)`:
  - increments `typeno`
  - returns prefix + new number

## 3.2 Common Type IDs (as used in code)

- `10`: Sales invoice
- `12`: Customer receipt
- `16`: Stock transfer
- `18`: Purchase order
- `23`: Enter bills
- `28`: Production batch
- `44`: Fixed asset depreciation

---

## 4. Security Architecture

## 4.1 Model

- `securityroles`: role definitions
- `securitytokens`: atomic permission tokens
- `securitygroups`: role-token links
- `scripts.pagesecurity`: page to required token
- `www_users.fullaccess`: user role assignment

## 4.2 Enforcement

On each request:

1. Script name is resolved.
2. Required token is fetched from session page-security map.
3. Token is checked in `$_SESSION['AllowedPageSecurityTokens']`.
4. Access denied page shown if missing.

## 4.3 Admin Surfaces

- `WWW_Users.php`: users, module visibility, language/theme/profile
- `WWW_Access.php`: roles and token links
- `PageSecurity.php`: script-token map

---

## 5. Data Domains and Table Responsibilities

## 5.1 Sales / AR

- `SalesHeader`: sales order/invoice headers
- `SalesLine`: line details
- `debtorsledger`: debtor postings
- `CustomerStatement`: customer statement entries
- `ReceiptsAllocation`: receipt allocations

## 5.2 Purchases / AP

- `PurchaseHeader`, `PurchaseLine`: PO + lines
- `SupplierStatement`: supplier statement rows
- `creditorsledger`: AP ledger posting target
- `PaymentsAllocation`: supplier payment allocations
- `paymentvoucherheader`, `paymentvoucherline`: vouchers

## 5.3 Finance / Cash / GL

- `Generalledger`: GL postings
- `BankTransactions`: cash/bank movements
- `BankAccounts`: bank master and reconciliation metadata
- `FinancialPeriods`: open/close period control
- `config`: system parameters

## 5.4 Inventory / Production

- `stockmaster`: item master
- `stockledger`: quantity/value movements
- `Stores`: storage master
- `ProductionMaster`: production batch master
- `LaboratoryStandards`: QC parameter definitions
- `LabPostingDetail`: QC results

## 5.5 Fixed Assets

- `fixedassets`: asset master and accum depreciation
- `fixedassettrans`: asset transaction history
- `fixedassetcategories`: category and account mapping

---

## 6. Critical Posting Flows (Technical)

## 6.1 Sales Invoice Posting

Triggered from `SalesInvoicefromorders.php` via `transactions/Saveinvoice.inc`.

### Writes

1. Insert invoice header (`SalesHeader`, type 10)
2. Insert invoice lines (`SalesLine`)
3. Insert debtor ledger (`debtorsledger`)
4. Insert GL sales leg (`Generalledger`)
5. Insert GL VAT leg (`Generalledger`)
6. Insert customer statement (`CustomerStatement`)
7. Update source order release status

### Transaction Control

- Wrapped in DB transaction
- Rollback on error number > 0

## 6.2 Receipt Posting

From `receipts.php`:

- Builds journal SQL array through class helper
- Posts in transaction block
- On success triggers auto-allocation through AJAX helper endpoint

## 6.3 Bill Posting

From `EnterBills.php`:

- Multi-line bill composition
- Saves journal and related postings in transaction
- Prints enter-bill document on success

## 6.4 Payment Voucher Processing

From `PaymentVoucher.php`:

- Create/edit/delete voucher header/line
- Amount-to-pay capture by supplier statement journal
- Save/update posting through class arrays
- Session lock to reduce duplicate submit risk

## 6.5 Stock Transfer Posting

From `Stocktransfer.php`:

- Validates stock availability
- For each line writes 2 `stockledger` rows:
  - source decrement
  - destination increment
- Uses transaction type 16

## 6.6 Depreciation Posting

From `FixedAssetDepreciation.php`:

- Calculates monthly depreciation by category and method
- Writes GL depreciation entries
- Writes `fixedassettrans`
- Updates `fixedassets.accumdepn`
- Advances company `last_depreciation` marker

---

## 7. Period and Close Operations

## 7.1 Month Close

In `FinancialPeriods.php`:

- `companies.PeriodRollover` advanced by 1 month
- periods with `end_date <= PeriodRollover` marked `closed = 1`

## 7.2 Financial Period Creation

- updates `config.FinancialYearBegins`
- runs rollover/create methods
- executes balancing journal routines

## 7.3 Technical Risks

- Closing before subledger reconciliation can lock inconsistencies.
- Manual updates to period/control tables can break posting assumptions.

---

## 8. System Parameter Surfaces

`SystemParameters.php` controls:

- Manual numbering mode
- Withholding tax account/rate
- Single-user mode
- Posting prohibition date
- DB maintenance behavior
- Default date format/theme
- Image/report directories
- SMTP usage
- Display limits
- Report logos
- payment terms text

Operational rule: change parameters under change control, then test at least one full transaction lifecycle.

---

## 9. Monitoring and Support Playbook

## 9.1 Daily Monitoring

1. Dashboard exception queues
2. Unposted document counts
3. Bank and cash exceptions
4. Failed postings and user-reported errors

## 9.2 Incident Triage Data

Collect:

- User ID
- Timestamp
- Page/script name
- Document/journal number
- Error text
- Input payload snapshot

## 9.3 Typical Root-Cause Categories

1. Security token mismatch
2. Closed period posting attempt
3. Missing setup master (posting groups, VAT config, bank account, dimension)
4. Duplicate submit/session lock collisions
5. Data integrity mismatch across source and statement tables

---

## 10. Hardening Recommendations

1. Replace raw SQL concatenation with parameterized query wrappers.
2. Centralize document-state machine validations (draft/released/posted/closed).
3. Add explicit idempotency keys on posting endpoints.
4. Expand structured event logging around transaction commits.
5. Add automated reconciliation checks for AR/AP/stock vs GL.

---

## 11. Source Reference Index

- `index.php`
- `homepage.php`
- `includes/session.inc`
- `includes/ConnectDB_mysqli.inc`
- `includes/SQL_CommonFunctions.inc`
- `includes/MainMenuLinksArray.php`
- `SalesOder.php`
- `SalesInvoice.php`
- `SalesInvoicefromorders.php`
- `transactions/Saveinvoice.inc`
- `PurchaseOder.php`
- `EnterBills.php`
- `PaymentVoucher.php`
- `receipts.php`
- `ReceitsAllocation.php`
- `Stocktransfer.php`
- `Production.php`
- `LaboratoryDataEntry.php`
- `Journal.php`
- `FinancialPeriods.php`
- `FixedAssetDepreciation.php`
- `WWW_Users.php`
- `WWW_Access.php`
- `PageSecurity.php`
- `SystemParameters.php`

