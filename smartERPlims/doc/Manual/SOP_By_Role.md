# SmartERP SOP By Role

## 1. Purpose

This SOP pack defines how each role should operate SmartERP consistently and safely.

Roles covered:

1. Sales Clerk
2. Accounts Receivable Accountant
3. Purchases/AP Accountant
4. Cash/Bank Accountant
5. General Ledger Accountant
6. Approver (Finance Head / CEO / Operations Approver)
7. System Administrator

---

## 2. Sales Clerk SOP

## 2.1 Daily Tasks

1. Create/maintain sales orders in `SalesOder.php`.
2. Validate customer, price, quantity, units, and pack conversions.
3. Submit orders for release/approval.
4. Track pending dispatch/invoice lists.

## 2.2 Invoice Preparation

1. Open `SalesInvoice.php`.
2. Select released order.
3. Confirm quantities, shipping, packaging charges in `SalesInvoicefromorders.php`.
4. Submit for posting (if role includes posting rights).

## 2.3 Required Checks Before Save

1. Customer and currency are correct.
2. Units/pack size match actual sale basis.
3. Line totals reconcile to expected gross.
4. Reference numbers are entered where required.

## 2.4 Exceptions

- If order does not appear for invoicing:
  1. Check release status.
  2. Check page permissions.
  3. Check prior posting status.

---

## 3. AR Accountant SOP

## 3.1 Receipt Processing

1. Use `receipts.php` to record receipt document.
2. Select customer and bank account.
3. Apply amounts to open statement lines.
4. Commit receipt once totals are verified.

## 3.2 Allocation

1. Open `ReceitsAllocation.php`.
2. Run auto-allocation where policy allows.
3. Review unmatched balances.
4. Perform manual adjustments only with evidence.

## 3.3 Daily Reconciliation

1. Match receipt postings with bank transaction feed.
2. Validate customer statements and ageing movements.
3. Report unapplied cash to GL/accounting lead.

## 3.4 Controls

1. Never over-allocate above unpaid amount.
2. Preserve references for traceability.
3. Escalate negative balance anomalies.

---

## 4. Purchases/AP Accountant SOP

## 4.1 Purchase Order and GRN Coordination

1. Confirm PO exists and is approved.
2. Confirm GRN/receipt quantities for invoice match.
3. Verify supplier, tax, and currency setup.

## 4.2 Enter Bills (`EnterBills.php`)

1. Create new bill number.
2. Select supplier.
3. Enter line-level account and amount detail.
4. Validate VAT and withholding setup.
5. Save bill and archive print output.

## 4.3 Payment Voucher (`PaymentVoucher.php`)

1. Create voucher number.
2. Select supplier and unpaid entries.
3. Enter amount-to-pay per invoice.
4. Submit voucher for approvals.
5. After approvals, proceed to payment execution.

## 4.4 Allocation (`PaymentsAllocation.php`)

1. Auto-allocate where allowed.
2. Review residual open balances.
3. Reset/rebuild allocations only with approval.

## 4.5 Controls

1. No payment without approved voucher.
2. No voucher without supporting bill/reference.
3. Amount paid cannot exceed net unpaid.

---

## 5. Cash and Bank Accountant SOP

## 5.1 Cash Journals

1. Enter cash movements in `cashJournal.php`.
2. Validate account and balancing side.
3. Post only in open periods.

## 5.2 Cheques and Remittance

1. Issue cheques from approved vouchers.
2. Print remittance and cheque advice.
3. File voucher-cheque-reference bundle.

## 5.3 Bank Reconciliation

1. Reconcile in `BankReconciliation.php`.
2. Mark cleared/reconciled items.
3. Investigate unreconciled and aged items.
4. Finalize month only after recon completion.

## 5.4 Controls

1. Segregate voucher preparation from cheque authorization.
2. Reconciliation sign-off by designated approver.
3. Maintain statement evidence and adjustments log.

---

## 6. GL Accountant SOP

## 6.1 Journal Entries (`Journal.php`)

1. Create new journal voucher.
2. Enter DR and CR legs with valid account combinations.
3. Add narration and dimensions.
4. Verify balance and save.

## 6.2 Financial Reports

1. Run trial balance.
2. Run P&L and balance sheet.
3. Run project and budget reports as required.
4. Investigate variances before period close.

## 6.3 Period Close (`FinancialPeriods.php`)

1. Confirm all subledgers reconciled.
2. Confirm approvals and postings complete.
3. Execute close month.
4. Execute close/create financial period when authorized.

## 6.4 Controls

1. No backdated postings in closed period.
2. Document all manual journals with support.
3. Maintain monthly close checklist sign-off.

---

## 7. Approver SOP

## 7.1 Approval Queues

Use approval pages and homepage counters to clear:

1. Supplier document approvals
2. Customer/store request approvals
3. Price list approvals
4. Payment voucher 1st/2nd approvals

## 7.2 Decision Criteria

1. Completeness of supporting docs
2. Budget and policy compliance
3. Correct vendor/customer/account context
4. Proper authorization chain

## 7.3 Controls

1. Do not approve own-created documents.
2. Reject items with missing references or mismatched totals.
3. Escalate repeated control failures.

---

## 8. System Administrator SOP

## 8.1 User and Role Management

1. Create/update users in `WWW_Users.php`.
2. Assign security roles per least privilege.
3. Restrict module access by function.

## 8.2 Role and Token Governance

1. Maintain role-token mappings in `WWW_Access.php`.
2. Maintain page-token mappings in `PageSecurity.php`.
3. Review high-risk pages quarterly.

## 8.3 Parameter Governance (`SystemParameters.php`)

1. Apply changes in controlled windows.
2. Record before/after values.
3. Run smoke-test transactions after changes.

## 8.4 Audit and Monitoring

1. Review audit trail regularly.
2. Track repeated failed login/security events.
3. Retain evidence for compliance/audits.

---

## 9. Month-End Cross-Role SOP

## 9.1 Pre-Close Checklist

1. Sales invoices posted and verified.
2. Receipts posted and allocated.
3. Bills and payment vouchers posted/approved.
4. Bank reconciliation complete.
5. Inventory exceptions investigated.
6. Fixed asset depreciation run (if in period scope).
7. GL trial balance reviewed.

## 9.2 Close Execution

1. GL accountant executes period close.
2. Approver signs off close pack.
3. Admin validates period security.

## 9.3 Post-Close Checklist

1. Financial statements archived.
2. Open-item carryover reviewed.
3. Next-period controls verified.

---

## 10. Incident Escalation SOP

## 10.1 Immediate Capture

1. User ID
2. Timestamp
3. Page name
4. Document/journal number
5. Error text screenshot
6. Input values used

## 10.2 Classification

1. Security/permission issue
2. Posting/period issue
3. Data integrity mismatch
4. Configuration/setup error
5. Performance/runtime issue

## 10.3 Escalation Routing

1. Functional issue -> module lead
2. Posting/reconciliation issue -> GL lead
3. Security issue -> system admin
4. Code defect -> development support

